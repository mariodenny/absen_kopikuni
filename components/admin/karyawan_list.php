<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$msg = $error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'assign_shift') {
        $karyawan_id = intval($_POST['karyawan_id']);
        $shift_id    = intval($_POST['shift_id']);
        $tanggal     = $connect->real_escape_string($_POST['tanggal'] ?? '');
        // Check existing
        $ex = $connect->query("SELECT id FROM jadwal_karyawan WHERE karyawan_id=$karyawan_id AND tanggal='$tanggal'");
        if ($ex->num_rows > 0) {
            $connect->query("UPDATE jadwal_karyawan SET shift_id=$shift_id WHERE karyawan_id=$karyawan_id AND tanggal='$tanggal'");
        } else {
            $now = date('Y-m-d H:i:s');
            $connect->query("INSERT INTO jadwal_karyawan (karyawan_id, shift_id, tanggal, created_at) VALUES ($karyawan_id, $shift_id, '$tanggal', '$now')");
        }
        $msg = 'Shift berhasil diassign!';
    }
}

$karyawanList = $connect->query("
    SELECT k.*, j.nama as jabatan_nama, u.email
    FROM karyawan k
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    LEFT JOIN users u ON u.id = k.user_id
    ORDER BY j.nama, k.fullName
")->fetch_all(MYSQLI_ASSOC);

$shiftList = $connect->query("SELECT * FROM shifts ORDER BY jam_masuk")->fetch_all(MYSQLI_ASSOC);
$today = date('Y-m-d');
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_admin.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">👥 Data Karyawan</h1>
            <p class="kopi-page-sub">Daftar karyawan dan penugasan shift harian</p>
        </div>

        <?php if ($msg): ?>
        <div class="kopi-alert kopi-alert-success mb-3">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- Assign Shift Card -->
        <div class="kopi-card mb-4">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📅 Assign Shift Harian</h3>
            </div>
            <div class="kopi-card-body">
                <form method="POST" class="flex gap-2" style="flex-wrap:wrap; align-items:flex-end">
                    <input type="hidden" name="action" value="assign_shift">
                    <div class="kopi-form-group" style="margin-bottom:0; flex:2; min-width:180px">
                        <label class="kopi-label">Karyawan</label>
                        <select name="karyawan_id" class="kopi-select" required>
                            <option value="">-- Pilih Karyawan --</option>
                            <?php foreach ($karyawanList as $k): ?>
                            <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['fullName']) ?> (<?= htmlspecialchars($k['jabatan_nama'] ?? '-') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="kopi-form-group" style="margin-bottom:0; flex:2; min-width:180px">
                        <label class="kopi-label">Shift</label>
                        <select name="shift_id" class="kopi-select" required>
                            <option value="">-- Pilih Shift --</option>
                            <?php foreach ($shiftList as $sh): ?>
                            <option value="<?= $sh['id'] ?>"><?= htmlspecialchars($sh['nama_shift']) ?> (<?= substr($sh['jam_masuk'],0,5) ?>-<?= substr($sh['jam_pulang'],0,5) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:140px">
                        <label class="kopi-label">Tanggal</label>
                        <input type="date" name="tanggal" class="kopi-input" value="<?= $today ?>" required>
                    </div>
                    <button type="submit" class="btn-kopi btn-kopi-primary" style="align-self:flex-end">✅ Assign</button>
                </form>
            </div>
        </div>

        <!-- Karyawan Table -->
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📋 Daftar Karyawan</h3>
                <span class="kopi-badge badge-info"><?= count($karyawanList) ?> orang</span>
            </div>
            <?php if ($karyawanList): ?>
            <div class="kopi-table-wrapper">
                <table class="kopi-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>Jabatan</th>
                            <th>Email</th>
                            <th>No. HP</th>
                            <th>QR Code</th>
                            <th>Shift Hari Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($karyawanList as $i => $k): ?>
                        <?php
                        // Get today's shift
                        $todayShift = $connect->query("
                            SELECT s.nama_shift FROM jadwal_karyawan jk
                            JOIN shifts s ON s.id = jk.shift_id
                            WHERE jk.karyawan_id={$k['id']} AND jk.tanggal='$today'
                            LIMIT 1
                        ")->fetch_assoc();
                        ?>
                        <tr>
                            <td class="text-muted"><?= $i+1 ?></td>
                            <td class="fw-600"><?= htmlspecialchars($k['fullName']) ?></td>
                            <td><span class="kopi-badge badge-gold"><?= htmlspecialchars($k['jabatan_nama'] ?? '-') ?></span></td>
                            <td class="text-muted text-sm"><?= htmlspecialchars($k['email'] ?? '-') ?></td>
                            <td class="text-muted text-sm"><?= htmlspecialchars($k['no_hp'] ?? '-') ?></td>
                            <td>
                                <?php if ($k['qr_code']): ?>
                                <a href="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($k['qr_code']) ?>"
                                   target="_blank" class="btn-kopi btn-kopi-ghost btn-kopi-sm">🔲 Lihat QR</a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($todayShift): ?>
                                    <span class="kopi-badge badge-info"><?= htmlspecialchars($todayShift['nama_shift']) ?></span>
                                <?php else: ?>
                                    <span class="kopi-badge badge-muted">Belum di-assign</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="kopi-empty">
                <div class="kopi-empty-icon">👥</div>
                <div class="kopi-empty-text">Belum ada karyawan terdaftar</div>
                <a href="index.php?page=register" class="btn-kopi btn-kopi-primary mt-3 btn-kopi-sm">+ Tambah Karyawan</a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
