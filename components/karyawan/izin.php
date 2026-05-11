<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$user        = $_SESSION['user'];
$karyawan_id = $user['karyawan_id'];
$msg = $error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe    = $connect->real_escape_string($_POST['tipe'] ?? '');
    $tanggal = $connect->real_escape_string($_POST['tanggal'] ?? '');
    $alasan  = $connect->real_escape_string(trim($_POST['alasan'] ?? ''));
    $now     = date('Y-m-d H:i:s');

    if (!$tipe || !$tanggal || !$alasan) {
        $error = 'Semua field wajib diisi.';
    } else {
        $bukti_file = null;

        // Handle file upload
        if (isset($_FILES['bukti']) && $_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg','jpeg','png','pdf'];
            $ext     = strtolower(pathinfo($_FILES['bukti']['name'], PATHINFO_EXTENSION));
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($ext, $allowed)) {
                $error = 'Format file harus JPG, PNG, atau PDF.';
            } elseif ($_FILES['bukti']['size'] > $maxSize) {
                $error = 'Ukuran file maksimal 5MB.';
            } else {
                $uploadDir = __DIR__ . '/../../uploads/bukti/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $filename  = 'bukti_' . $karyawan_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['bukti']['tmp_name'], $uploadDir . $filename)) {
                    $bukti_file = $filename;
                } else {
                    $error = 'Gagal upload file.';
                }
            }
        }

        if (!$error) {
            // Check existing izin on same date
            $existing = $connect->query("SELECT id FROM permissions WHERE karyawan_id=$karyawan_id AND tanggal='$tanggal'");
            if ($existing->num_rows > 0) {
                $error = 'Kamu sudah mengajukan izin untuk tanggal tersebut.';
            } else {
                $bukti_esc = $bukti_file ? "'$bukti_file'" : "NULL";
                $connect->query("
                    INSERT INTO permissions (karyawan_id, tanggal, tipe, alasan, bukti_file, created_at, status)
                    VALUES ($karyawan_id, '$tanggal', '$tipe', '$alasan', $bukti_esc, '$now', 'pending')
                ");
                $msg = 'Pengajuan izin berhasil dikirim! Tunggu persetujuan admin.';
            }
        }
    }
}

// Get izin history
$izinHistory = $connect->query("
    SELECT * FROM permissions WHERE karyawan_id=$karyawan_id ORDER BY created_at DESC LIMIT 20
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_karyawan.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">📝 Pengajuan Izin</h1>
            <p class="kopi-page-sub">Ajukan izin, sakit, atau cuti</p>
        </div>

        <div class="kopi-grid grid-2" style="align-items:start; gap:1.5rem">
            <!-- Form -->
            <div class="kopi-card">
                <div class="kopi-card-header">
                    <h3 class="kopi-card-title">➕ Ajukan Izin Baru</h3>
                </div>
                <div class="kopi-card-body">
                    <?php if ($msg): ?>
                    <div class="kopi-alert kopi-alert-success mb-3">✅ <?= htmlspecialchars($msg) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                    <div class="kopi-alert kopi-alert-error mb-3">⚠️ <?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="kopi-form-group">
                            <label class="kopi-label">Jenis Ketidakhadiran *</label>
                            <select name="tipe" class="kopi-select" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="izin"  <?= ($_POST['tipe']??'') === 'izin'  ? 'selected' : '' ?>>📄 Izin</option>
                                <option value="sakit" <?= ($_POST['tipe']??'') === 'sakit' ? 'selected' : '' ?>>🤒 Sakit</option>
                                <option value="cuti"  <?= ($_POST['tipe']??'') === 'cuti'  ? 'selected' : '' ?>>🌴 Cuti</option>
                            </select>
                        </div>

                        <div class="kopi-form-group">
                            <label class="kopi-label">Tanggal Tidak Hadir *</label>
                            <input type="date" name="tanggal" class="kopi-input"
                                value="<?= htmlspecialchars($_POST['tanggal'] ?? date('Y-m-d')) ?>"
                                min="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="kopi-form-group">
                            <label class="kopi-label">Alasan / Keterangan *</label>
                            <textarea name="alasan" class="kopi-textarea"
                                placeholder="Tulis alasan ketidakhadiranmu secara detail..."
                                required><?= htmlspecialchars($_POST['alasan'] ?? '') ?></textarea>
                        </div>

                        <div class="kopi-form-group">
                            <label class="kopi-label">Bukti Pendukung (Opsional)</label>
                            <label class="kopi-file-upload" id="file-drop-zone">
                                <input type="file" name="bukti" id="bukti-file"
                                    accept=".jpg,.jpeg,.png,.pdf"
                                    onchange="updateFileLabel(this)">
                                <div id="file-label">
                                    <div style="font-size:2rem; margin-bottom:.5rem">📎</div>
                                    <div style="font-size:.875rem; color:var(--kopi-muted)">
                                        Klik atau seret file ke sini<br>
                                        <span style="font-size:.75rem">JPG, PNG, PDF — Maks. 5MB</span>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="btn-kopi btn-kopi-primary btn-kopi-full btn-kopi-lg">
                            📤 Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>

            <!-- History -->
            <div class="kopi-card">
                <div class="kopi-card-header">
                    <h3 class="kopi-card-title">📋 Riwayat Pengajuan</h3>
                </div>
                <div class="kopi-card-body" style="padding:0">
                    <?php if ($izinHistory): ?>
                    <div class="kopi-timeline" style="padding:0 1.5rem">
                        <?php foreach ($izinHistory as $izin):
                            $statusColor = ['pending' => 'badge-warning', 'approved' => 'badge-success', 'rejected' => 'badge-danger'];
                            $tipeIcon = ['izin' => '📄', 'sakit' => '🤒', 'cuti' => '🌴'];
                        ?>
                        <div class="kopi-timeline-item">
                            <div class="kopi-timeline-dot" style="background:var(--kopi-surface2); font-size:.9rem">
                                <?= $tipeIcon[$izin['tipe']] ?? '📄' ?>
                            </div>
                            <div class="kopi-timeline-body">
                                <div class="flex-between">
                                    <div class="kopi-timeline-title"><?= strtoupper($izin['tipe']) ?></div>
                                    <span class="kopi-badge <?= $statusColor[$izin['status']] ?? 'badge-muted' ?>">
                                        <?= strtoupper($izin['status']) ?>
                                    </span>
                                </div>
                                <div class="kopi-timeline-meta">
                                    <?= date('d M Y', strtotime($izin['tanggal'])) ?>
                                </div>
                                <div style="font-size:.8rem; color:var(--kopi-muted); margin-top:.3rem">
                                    <?= mb_strimwidth(htmlspecialchars($izin['alasan']), 0, 80, '...') ?>
                                </div>
                                <?php if ($izin['catatan_admin']): ?>
                                <div style="font-size:.75rem; color:var(--kopi-gold); margin-top:.3rem">
                                    💬 <?= htmlspecialchars($izin['catatan_admin']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="kopi-empty">
                        <div class="kopi-empty-icon">📭</div>
                        <div class="kopi-empty-text">Belum ada riwayat pengajuan izin</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
function updateFileLabel(input) {
    const label = document.getElementById('file-label');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const size = (file.size / 1024).toFixed(1);
        label.innerHTML = `
            <div style="font-size:2rem; margin-bottom:.5rem">✅</div>
            <div style="font-size:.875rem; color:var(--kopi-gold); font-weight:600">${file.name}</div>
            <div style="font-size:.75rem; color:var(--kopi-muted)">${size} KB</div>
        `;
    }
}
</script>
