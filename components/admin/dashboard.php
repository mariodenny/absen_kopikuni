<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$today = date('Y-m-d');

// Stats
$statHadir    = $connect->query("SELECT COUNT(*) c FROM absensi WHERE tanggal='$today' AND jam_masuk IS NOT NULL")->fetch_assoc()['c'];
$statTelat    = $connect->query("SELECT COUNT(*) c FROM absensi WHERE tanggal='$today' AND status_masuk='terlambat'")->fetch_assoc()['c'];
$statIzin     = $connect->query("SELECT COUNT(*) c FROM permissions WHERE tanggal='$today' AND status='approved'")->fetch_assoc()['c'];
$statPending  = $connect->query("SELECT COUNT(*) c FROM absensi WHERE tanggal='$today' AND verified_by IS NULL AND jam_masuk IS NOT NULL")->fetch_assoc()['c'];
$statIzinPend = $connect->query("SELECT COUNT(*) c FROM permissions WHERE status='pending'")->fetch_assoc()['c'];
$totalKary    = $connect->query("SELECT COUNT(*) c FROM karyawan")->fetch_assoc()['c'];

// Recent absensi
$recentAbsen = $connect->query("
    SELECT a.*, k.fullName, j.nama as jabatan, s.nama_shift
    FROM absensi a
    JOIN karyawan k ON k.id = a.karyawan_id
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    LEFT JOIN jadwal_karyawan jk ON jk.id = a.jadwal_id
    LEFT JOIN shifts s ON s.id = jk.shift_id
    WHERE a.tanggal = '$today'
    ORDER BY a.jam_masuk DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_admin.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">🏠 Dashboard Admin</h1>
            <p class="kopi-page-sub"><?= date('l, d F Y') ?> — Selamat datang, <?= htmlspecialchars($_SESSION['user']['username']) ?></p>
        </div>

        <!-- Stats Row -->
        <div class="kopi-grid grid-4 mb-4">
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">✅</span>
                <div class="kopi-stat-label">Hadir Hari Ini</div>
                <div class="kopi-stat-value"><?= $statHadir ?></div>
                <div class="kopi-stat-sub">dari <?= $totalKary ?> karyawan</div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">⚠️</span>
                <div class="kopi-stat-label">Terlambat</div>
                <div class="kopi-stat-value" style="color:var(--kopi-warning)"><?= $statTelat ?></div>
                <div class="kopi-stat-sub">hari ini</div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">🔔</span>
                <div class="kopi-stat-label">Belum Diverifikasi</div>
                <div class="kopi-stat-value" style="color:var(--kopi-info)"><?= $statPending ?></div>
                <div class="kopi-stat-sub"><a href="index.php?page=admin/verifikasi" style="color:var(--kopi-gold); font-size:.75rem">→ Verifikasi</a></div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">📋</span>
                <div class="kopi-stat-label">Izin Pending</div>
                <div class="kopi-stat-value" style="color:var(--kopi-danger)"><?= $statIzinPend ?></div>
                <div class="kopi-stat-sub"><a href="index.php?page=admin/persetujuan_izin" style="color:var(--kopi-gold); font-size:.75rem">→ Setujui</a></div>
            </div>
        </div>

        <!-- Recent Absensi Table -->
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📝 Absensi Hari Ini</h3>
                <a href="index.php?page=admin/verifikasi" class="btn-kopi btn-kopi-ghost btn-kopi-sm">Lihat Semua →</a>
            </div>
            <?php if ($recentAbsen): ?>
            <div class="kopi-table-wrapper">
                <table class="kopi-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jabatan</th>
                            <th>Shift</th>
                            <th>Jam Masuk</th>
                            <th>Status</th>
                            <th>Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentAbsen as $row): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars($row['fullName']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                            <td class="text-muted"><?= htmlspecialchars($row['nama_shift'] ?? '-') ?></td>
                            <td><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                            <td>
                                <?php if ($row['status_masuk'] === 'tepat_waktu'): ?>
                                    <span class="kopi-badge badge-success">✅ Tepat Waktu</span>
                                <?php elseif ($row['status_masuk'] === 'terlambat'): ?>
                                    <span class="kopi-badge badge-warning">⚠️ Telat <?= $row['menit_terlambat'] ?>m</span>
                                <?php else: ?>
                                    <span class="kopi-badge badge-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['verified_by']): ?>
                                    <span class="kopi-badge badge-success">✅ Verified</span>
                                <?php else: ?>
                                    <span class="kopi-badge badge-warning">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="kopi-empty">
                <div class="kopi-empty-icon">📭</div>
                <div class="kopi-empty-text">Belum ada absensi hari ini</div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
