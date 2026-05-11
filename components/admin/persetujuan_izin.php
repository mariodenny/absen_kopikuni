<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$msg = $error = '';

// Handle approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = intval($_POST['izin_id']);
    $action  = $_POST['action'] ?? '';
    $catatan = $connect->real_escape_string($_POST['catatan_admin'] ?? '');
    $adminId = intval($_SESSION['user']['id']);
    $now     = date('Y-m-d H:i:s');

    if (in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $connect->query("UPDATE permissions SET status='$status', approved_by=$adminId, approved_at='$now', catatan_admin='$catatan' WHERE id=$id");
        $msg = $action === 'approve' ? '✅ Izin berhasil disetujui.' : '❌ Izin ditolak.';
    }
}

$filterStatus = $_GET['status'] ?? 'pending';
$where = $filterStatus !== 'all' ? "WHERE p.status='$filterStatus'" : "WHERE 1=1";

$izinList = $connect->query("
    SELECT p.*, k.fullName, j.nama as jabatan, u.username as disetujui_oleh
    FROM permissions p
    JOIN karyawan k ON k.id = p.karyawan_id
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    LEFT JOIN users u ON u.id = p.approved_by
    $where
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_admin.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">📋 Persetujuan Izin</h1>
            <p class="kopi-page-sub">Review pengajuan izin, sakit, dan cuti karyawan</p>
        </div>

        <?php if ($msg): ?>
        <div class="kopi-alert kopi-alert-success mb-3"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Tab Filters -->
        <div class="flex gap-1 mb-4">
            <?php
            $tabs = ['pending' => '⏳ Pending', 'approved' => '✅ Disetujui', 'rejected' => '❌ Ditolak', 'all' => '📋 Semua'];
            foreach ($tabs as $key => $label):
                $active = ($filterStatus === $key) ? 'btn-kopi-primary' : 'btn-kopi-ghost';
            ?>
            <a href="?page=admin/persetujuan_izin&status=<?= $key ?>" class="btn-kopi <?= $active ?> btn-kopi-sm">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($izinList): ?>
        <div style="display:flex; flex-direction:column; gap:1rem">
            <?php foreach ($izinList as $izin): ?>
            <div class="kopi-card">
                <div class="kopi-card-body">
                    <div class="flex-between mb-3">
                        <div>
                            <div class="fw-700" style="color:var(--kopi-cream); font-size:1rem">
                                <?= htmlspecialchars($izin['fullName']) ?>
                            </div>
                            <div class="text-muted text-sm"><?= htmlspecialchars($izin['jabatan'] ?? '-') ?></div>
                        </div>
                        <div class="flex gap-1">
                            <?php
                            $tipeColor = ['izin' => 'badge-info', 'sakit' => 'badge-danger', 'cuti' => 'badge-gold'];
                            $statusColor = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'];
                            ?>
                            <span class="kopi-badge <?= $tipeColor[$izin['tipe']] ?? 'badge-muted' ?>">
                                <?= strtoupper($izin['tipe']) ?>
                            </span>
                            <span class="kopi-badge <?= $statusColor[$izin['status']] ?? 'badge-muted' ?>">
                                <?= strtoupper($izin['status']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="kopi-grid grid-2" style="gap:1rem; margin-bottom:1rem">
                        <div>
                            <div class="text-muted text-sm">📅 Tanggal Izin</div>
                            <div class="fw-600"><?= date('d M Y', strtotime($izin['tanggal'])) ?></div>
                        </div>
                        <div>
                            <div class="text-muted text-sm">🕐 Diajukan</div>
                            <div class="fw-600"><?= date('d M Y H:i', strtotime($izin['created_at'])) ?></div>
                        </div>
                    </div>

                    <div style="background:var(--kopi-surface2); padding:1rem; border-radius:var(--radius-md); margin-bottom:1rem">
                        <div class="text-muted text-sm mb-1">📝 Alasan:</div>
                        <div style="color:var(--kopi-cream)"><?= nl2br(htmlspecialchars($izin['alasan'])) ?></div>
                    </div>

                    <!-- Bukti File -->
                    <?php if ($izin['bukti_file']): ?>
                    <div class="mb-3">
                        <div class="text-muted text-sm mb-1">📎 Bukti:</div>
                        <?php
                        $ext = strtolower(pathinfo($izin['bukti_file'], PATHINFO_EXTENSION));
                        $fileUrl = '/absensi_kopikuni/uploads/bukti/' . basename($izin['bukti_file']);
                        if (in_array($ext, ['jpg','jpeg','png'])):
                        ?>
                        <img src="<?= htmlspecialchars($fileUrl) ?>" alt="Bukti"
                             style="max-height:200px; border-radius:var(--radius-md); border:1px solid var(--kopi-border)">
                        <?php else: ?>
                        <a href="<?= htmlspecialchars($fileUrl) ?>" target="_blank"
                           class="btn-kopi btn-kopi-ghost btn-kopi-sm">📄 Lihat Dokumen (PDF)</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Admin catatan if approved/rejected -->
                    <?php if ($izin['status'] !== 'pending' && $izin['catatan_admin']): ?>
                    <div class="kopi-alert kopi-alert-info mb-3">
                        💬 Catatan Admin: <?= htmlspecialchars($izin['catatan_admin']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($izin['status'] !== 'pending'): ?>
                    <div class="text-muted text-sm">
                        Diproses oleh <?= htmlspecialchars($izin['disetujui_oleh'] ?? 'Admin') ?>
                        pada <?= $izin['approved_at'] ? date('d M Y H:i', strtotime($izin['approved_at'])) : '-' ?>
                    </div>
                    <?php else: ?>
                    <!-- Action Buttons -->
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="izin_id" value="<?= $izin['id'] ?>">
                        <div class="kopi-form-group mb-2">
                            <input type="text" name="catatan_admin" class="kopi-input"
                                placeholder="Catatan admin (opsional)...">
                        </div>
                        <div class="flex gap-1">
                            <button type="submit" name="action" value="approve" class="btn-kopi btn-kopi-success btn-kopi-sm">
                                ✅ Setujui
                            </button>
                            <button type="submit" name="action" value="reject"
                                class="btn-kopi btn-kopi-danger btn-kopi-sm"
                                onclick="return confirm('Tolak izin ini?')">
                                ❌ Tolak
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="kopi-card">
            <div class="kopi-empty">
                <div class="kopi-empty-icon">📭</div>
                <div class="kopi-empty-text">Tidak ada pengajuan izin untuk status ini</div>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>
