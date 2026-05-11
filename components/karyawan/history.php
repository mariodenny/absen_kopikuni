<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$user        = $_SESSION['user'];
$karyawan_id = $user['karyawan_id'];

$filterMonth = $_GET['bulan'] ?? date('Y-m');

$absenList = $connect->query("
    SELECT a.*, s.nama_shift, s.jam_masuk as shift_masuk, s.jam_pulang as shift_pulang,
           p.tipe as izin_tipe, p.status as izin_status
    FROM absensi a
    LEFT JOIN jadwal_karyawan jk ON jk.id = a.jadwal_id
    LEFT JOIN shifts s ON s.id = jk.shift_id
    LEFT JOIN permissions p ON p.karyawan_id = a.karyawan_id AND p.tanggal = a.tanggal
    WHERE a.karyawan_id = $karyawan_id
      AND DATE_FORMAT(a.tanggal, '%Y-%m') = '$filterMonth'
    ORDER BY a.tanggal DESC
")->fetch_all(MYSQLI_ASSOC);

// Monthly summary
$stat = $connect->query("
    SELECT
        COUNT(*) as total,
        SUM(status_masuk='tepat_waktu') as tepat,
        SUM(status_masuk='terlambat')   as telat,
        SUM(status_pulang='lembur')     as lembur,
        SUM(menit_terlambat)            as total_menit
    FROM absensi WHERE karyawan_id=$karyawan_id AND DATE_FORMAT(tanggal,'%Y-%m')='$filterMonth'
")->fetch_assoc();

$izinCount = $connect->query("
    SELECT COUNT(*) c FROM permissions
    WHERE karyawan_id=$karyawan_id AND DATE_FORMAT(tanggal,'%Y-%m')='$filterMonth'
")->fetch_assoc()['c'];
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_karyawan.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <div class="flex-between">
                <div>
                    <h1 class="kopi-page-title">📅 History Absensi</h1>
                    <p class="kopi-page-sub">Rekap kehadiran pribadi kamu</p>
                </div>
                <form method="GET" class="flex gap-1" style="align-items:flex-end">
                    <input type="hidden" name="page" value="karyawan/history">
                    <input type="month" name="bulan" class="kopi-input" value="<?= $filterMonth ?>" style="width:auto">
                    <button type="submit" class="btn-kopi btn-kopi-primary btn-kopi-sm">🔍</button>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="kopi-grid grid-4 mb-4">
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">📅</span>
                <div class="kopi-stat-label">Total Hadir</div>
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
                <div class="kopi-stat-sub"><?= $stat['total_menit'] ?> menit total</div>
            </div>
            <div class="kopi-stat-card">
                <span class="kopi-stat-icon">📝</span>
                <div class="kopi-stat-label">Izin/Sakit</div>
                <div class="kopi-stat-value" style="color:var(--kopi-info)"><?= $izinCount ?></div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">🗓 Riwayat Absensi</h3>
                <span class="kopi-badge badge-info"><?= date('F Y', strtotime($filterMonth . '-01')) ?></span>
            </div>
            <div class="kopi-card-body">
                <?php if ($absenList): ?>
                <div class="kopi-timeline">
                    <?php foreach ($absenList as $row):
                        // Determine icon & dot color
                        if ($row['status_masuk'] === 'tepat_waktu') {
                            $icon = '✅'; $dotBg = 'rgba(34,197,94,0.15)'; $titleColor = 'var(--kopi-success)'; $title = 'Tepat Waktu';
                        } elseif ($row['status_masuk'] === 'terlambat') {
                            $icon = '⚠️'; $dotBg = 'rgba(245,158,11,0.15)'; $titleColor = 'var(--kopi-warning)'; $title = 'Terlambat ' . $row['menit_terlambat'] . ' menit';
                        } else {
                            $icon = '❌'; $dotBg = 'rgba(239,68,68,0.15)'; $titleColor = 'var(--kopi-danger)'; $title = 'Tidak Hadir';
                        }
                    ?>
                    <div class="kopi-timeline-item">
                        <div class="kopi-timeline-dot" style="background:<?= $dotBg ?>">
                            <?= $icon ?>
                        </div>
                        <div class="kopi-timeline-body">
                            <div class="flex-between">
                                <div>
                                    <div class="kopi-timeline-title" style="color:<?= $titleColor ?>">
                                        <?= $title ?>
                                    </div>
                                    <div class="kopi-timeline-meta">
                                        <?= date('l, d F Y', strtotime($row['tanggal'])) ?>
                                        &nbsp;·&nbsp;
                                        <?= htmlspecialchars($row['nama_shift'] ?? 'Tanpa Shift') ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php if ($row['status_pulang'] === 'lembur'): ?>
                                        <span class="kopi-badge badge-gold">🔥 Lembur</span>
                                    <?php elseif ($row['status_pulang'] === 'lebih_awal'): ?>
                                        <span class="kopi-badge badge-warning">⬅ Lebih Awal</span>
                                    <?php endif; ?>
                                    <?php if (!$row['verified_by']): ?>
                                        <div class="kopi-badge badge-muted mt-1">⏳ Belum Diverifikasi</div>
                                    <?php else: ?>
                                        <div class="kopi-badge badge-success mt-1">✅ Verified</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Time Detail -->
                            <div style="display:flex; gap:1.5rem; margin-top:.5rem; font-size:.8rem; color:var(--kopi-muted)">
                                <span>🕐 Masuk: <strong style="color:var(--kopi-cream)"><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '—' ?></strong></span>
                                <span>🕔 Pulang: <strong style="color:var(--kopi-cream)"><?= $row['jam_keluar'] ? date('H:i', strtotime($row['jam_keluar'])) : '—' ?></strong></span>
                                <?php if ($row['shift_masuk']): ?>
                                <span>📋 Shift: <?= substr($row['shift_masuk'],0,5) ?> - <?= substr($row['shift_pulang'],0,5) ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Late departure reason -->
                            <?php if ($row['alasan_telat_pulang']): ?>
                            <div style="margin-top:.5rem; font-size:.8rem; background:rgba(212,168,83,0.08); padding:.5rem .75rem; border-radius:6px; color:var(--kopi-muted)">
                                📝 Alasan pulang telat: <?= htmlspecialchars($row['alasan_telat_pulang']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="kopi-empty">
                    <div class="kopi-empty-icon">📭</div>
                    <div class="kopi-empty-text">Tidak ada data absensi untuk bulan ini</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
