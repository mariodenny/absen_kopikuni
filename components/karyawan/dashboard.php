<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$user        = $_SESSION['user'];
$karyawan_id = $user['karyawan_id'];
$today       = date('Y-m-d');

// Stats this month
$month = date('Y-m');
$stat = $connect->query("
    SELECT
        COUNT(*) as total,
        SUM(status_masuk = 'tepat_waktu') as tepat,
        SUM(status_masuk = 'terlambat')   as telat,
        SUM(status_pulang = 'lembur')     as lembur
    FROM absensi
    WHERE karyawan_id = $karyawan_id AND DATE_FORMAT(tanggal, '%Y-%m') = '$month'
")->fetch_assoc();

// Today's absensi
$absenToday = $connect->query("
    SELECT a.*, s.nama_shift, s.jam_masuk as shift_masuk, s.jam_pulang as shift_pulang
    FROM absensi a
    LEFT JOIN jadwal_karyawan jk ON jk.id = a.jadwal_id
    LEFT JOIN shifts s ON s.id = jk.shift_id
    WHERE a.karyawan_id = $karyawan_id AND a.tanggal = '$today'
    LIMIT 1
")->fetch_assoc();

// Today's jadwal
$jadwalToday = $connect->query("
    SELECT jk.*, s.nama_shift, s.jam_masuk, s.jam_pulang, s.toleransi_menit
    FROM jadwal_karyawan jk JOIN shifts s ON s.id = jk.shift_id
    WHERE jk.karyawan_id = $karyawan_id AND jk.tanggal = '$today'
    LIMIT 1
")->fetch_assoc();

// Pending izin
$pendingIzin = $connect->query("SELECT COUNT(*) c FROM permissions WHERE karyawan_id = $karyawan_id AND status = 'pending'")->fetch_assoc()['c'];
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_karyawan.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">🏠 Dashboard</h1>
            <p class="kopi-page-sub">Halo, <?= htmlspecialchars($user['fullName'] ?? $user['username']) ?>! 👋</p>
        </div>

        <!-- Today's Status -->
        <?php if ($jadwalToday): ?>
        <div class="kopi-alert kopi-alert-info mb-4">
            ⏰ <strong>Shift Hari Ini:</strong> <?= htmlspecialchars($jadwalToday['nama_shift']) ?>
            — <?= substr($jadwalToday['jam_masuk'],0,5) ?> s/d <?= substr($jadwalToday['jam_pulang'],0,5) ?>
            &nbsp;|&nbsp; Toleransi: <?= $jadwalToday['toleransi_menit'] ?> menit
        </div>
        <?php else: ?>
        <div class="kopi-alert kopi-alert-warning mb-4">
            ⚠️ Belum ada jadwal shift untuk hari ini. Hubungi admin.
        </div>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="kopi-grid grid-4 mb-4">
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">📅</span>
                <div class="kopi-stat-label">Hadir Bulan Ini</div>
                <div class="kopi-stat-value"><?= $stat['total'] ?></div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">✅</span>
                <div class="kopi-stat-label">Tepat Waktu</div>
                <div class="kopi-stat-value" style="color:var(--kopi-success)"><?= $stat['tepat'] ?></div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">⚠️</span>
                <div class="kopi-stat-label">Terlambat</div>
                <div class="kopi-stat-value" style="color:var(--kopi-warning)"><?= $stat['telat'] ?></div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">🔥</span>
                <div class="kopi-stat-label">Lembur</div>
                <div class="kopi-stat-value" style="color:var(--kopi-gold)"><?= $stat['lembur'] ?></div>
            </div>
        </div>

        <!-- Today Absen Status -->
        <div class="kopi-grid grid-2 mb-4">
            <div class="kopi-card">
                <div class="kopi-card-header">
                    <h3 class="kopi-card-title">📋 Status Absensi Hari Ini</h3>
                </div>
                <div class="kopi-card-body">
                    <?php if ($absenToday): ?>
                    <div style="display:grid; gap:.75rem">
                        <div class="flex-between">
                            <span class="text-muted">Jam Masuk</span>
                            <span class="fw-700"><?= $absenToday['jam_masuk'] ? date('H:i', strtotime($absenToday['jam_masuk'])) : '—' ?></span>
                        </div>
                        <div class="flex-between">
                            <span class="text-muted">Status Masuk</span>
                            <?php if ($absenToday['status_masuk'] === 'tepat_waktu'): ?>
                                <span class="kopi-badge badge-success">✅ Tepat Waktu</span>
                            <?php else: ?>
                                <span class="kopi-badge badge-warning">⚠️ Telat <?= $absenToday['menit_terlambat'] ?>m</span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-between">
                            <span class="text-muted">Jam Pulang</span>
                            <span class="fw-700"><?= ($absenToday['jam_keluar']) ? date('H:i', strtotime($absenToday['jam_keluar'])) : '—' ?></span>
                        </div>
                        <div class="flex-between">
                            <span class="text-muted">Verifikasi</span>
                            <?= $absenToday['verified_by'] ? '<span class="kopi-badge badge-success">✅ Verified</span>' : '<span class="kopi-badge badge-warning">⏳ Pending</span>' ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="kopi-empty" style="padding:1.5rem">
                        <div class="kopi-empty-icon">📭</div>
                        <div class="kopi-empty-text">Belum absen hari ini</div>
                        <a href="index.php?page=absen" class="btn-kopi btn-kopi-primary mt-3 btn-kopi-sm">📷 Absen Sekarang</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="kopi-card">
                <div class="kopi-card-header">
                    <h3 class="kopi-card-title">⚡ Menu Cepat</h3>
                </div>
                <div class="kopi-card-body" style="display:grid; gap:.75rem">
                    <a href="index.php?page=absen" class="btn-kopi btn-kopi-primary btn-kopi-full">
                        📷 Absen Sekarang
                    </a>
                    <a href="index.php?page=karyawan/history" class="btn-kopi btn-kopi-ghost btn-kopi-full">
                        📅 Lihat History Absensi
                    </a>
                    <a href="index.php?page=karyawan/izin" class="btn-kopi btn-kopi-ghost btn-kopi-full">
                        📝 Ajukan Izin / Sakit
                        <?php if ($pendingIzin > 0): ?>
                        <span class="kopi-badge badge-warning" style="margin-left:.5rem"><?= $pendingIzin ?> pending</span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>
