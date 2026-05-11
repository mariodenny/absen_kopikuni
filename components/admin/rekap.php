<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$filterFrom   = $_GET['dari'] ?? date('Y-m-01');
$filterTo     = $_GET['sampai'] ?? date('Y-m-d');
$filterJabatan = intval($_GET['jabatan_id'] ?? 0);

$jabatanList = $connect->query("SELECT * FROM jabatan ORDER BY id")->fetch_all(MYSQLI_ASSOC);

// Build filter
$where = ["a.tanggal BETWEEN '$filterFrom' AND '$filterTo'"];
if ($filterJabatan) $where[] = "k.jabatan_id = $filterJabatan";
$whereStr = implode(' AND ', $where);

// Summary per karyawan
$rekap = $connect->query("
    SELECT
        k.id, k.fullName, j.nama as jabatan,
        COUNT(a.id) as total_hadir,
        SUM(a.status_masuk = 'tepat_waktu') as tepat_waktu,
        SUM(a.status_masuk = 'terlambat')  as terlambat,
        SUM(a.status_pulang = 'lembur')    as lembur,
        SUM(a.jam_masuk IS NULL)            as alpha,
        SUM(a.menit_terlambat)              as total_menit_telat
    FROM karyawan k
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    LEFT JOIN absensi a ON a.karyawan_id = k.id AND $whereStr
    " . ($filterJabatan ? "WHERE k.jabatan_id = $filterJabatan" : "") . "
    GROUP BY k.id
    ORDER BY k.fullName
")->fetch_all(MYSQLI_ASSOC);

// Detailed list
$detail = $connect->query("
    SELECT a.*, k.fullName, j.nama as jabatan,
           s.nama_shift, s.jam_masuk as shift_masuk, s.jam_pulang as shift_pulang
    FROM absensi a
    JOIN karyawan k ON k.id = a.karyawan_id
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    LEFT JOIN jadwal_karyawan jk ON jk.id = a.jadwal_id
    LEFT JOIN shifts s ON s.id = jk.shift_id
    WHERE $whereStr
    ORDER BY a.tanggal DESC, a.jam_masuk ASC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_admin.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">📊 Rekap Data Absensi</h1>
            <p class="kopi-page-sub">Laporan kehadiran dan performa karyawan</p>
        </div>

        <!-- Filters -->
        <div class="kopi-card mb-4">
            <div class="kopi-card-body">
                <form method="GET" class="flex gap-2" style="flex-wrap:wrap; align-items:flex-end">
                    <input type="hidden" name="page" value="admin/rekap">
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:140px">
                        <label class="kopi-label">Dari Tanggal</label>
                        <input type="date" name="dari" class="kopi-input" value="<?= $filterFrom ?>">
                    </div>
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:140px">
                        <label class="kopi-label">Sampai Tanggal</label>
                        <input type="date" name="sampai" class="kopi-input" value="<?= $filterTo ?>">
                    </div>
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:160px">
                        <label class="kopi-label">Jabatan</label>
                        <select name="jabatan_id" class="kopi-select">
                            <option value="">Semua Jabatan</option>
                            <?php foreach ($jabatanList as $j): ?>
                            <option value="<?= $j['id'] ?>" <?= $filterJabatan == $j['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($j['nama']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-kopi btn-kopi-primary" style="align-self:flex-end">🔍 Filter</button>
                    <a href="index.php?page=admin/rekap" class="btn-kopi btn-kopi-ghost" style="align-self:flex-end">Reset</a>
                </form>
            </div>
        </div>

        <!-- Summary Per Karyawan -->
        <div class="kopi-card mb-4">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📋 Ringkasan Per Karyawan</h3>
                <span class="kopi-badge badge-info"><?= date('d M', strtotime($filterFrom)) ?> — <?= date('d M Y', strtotime($filterTo)) ?></span>
            </div>
            <div class="kopi-table-wrapper">
                <table class="kopi-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jabatan</th>
                            <th>Total Hadir</th>
                            <th>Tepat Waktu</th>
                            <th>Terlambat</th>
                            <th>Lembur</th>
                            <th>Total Menit Telat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rekap as $r): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars($r['fullName']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($r['jabatan'] ?? '-') ?></td>
                            <td><span class="kopi-badge badge-info"><?= $r['total_hadir'] ?></span></td>
                            <td><span class="kopi-badge badge-success"><?= $r['tepat_waktu'] ?></span></td>
                            <td>
                                <?php if ($r['terlambat'] > 0): ?>
                                <span class="kopi-badge badge-warning"><?= $r['terlambat'] ?></span>
                                <?php else: ?>
                                <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['lembur'] > 0): ?>
                                <span class="kopi-badge badge-gold"><?= $r['lembur'] ?></span>
                                <?php else: ?>
                                <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['total_menit_telat'] > 0): ?>
                                <span style="color:var(--kopi-warning); font-weight:600"><?= $r['total_menit_telat'] ?> menit</span>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail Absensi -->
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📝 Detail Absensi</h3>
                <span class="kopi-badge badge-muted">Maks. 100 data</span>
            </div>
            <?php if ($detail): ?>
            <div class="kopi-table-wrapper">
                <table class="kopi-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Karyawan</th>
                            <th>Jabatan</th>
                            <th>Shift</th>
                            <th>Masuk</th>
                            <th>Pulang</th>
                            <th>Status Masuk</th>
                            <th>Status Pulang</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detail as $d): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($d['tanggal'])) ?></td>
                            <td class="fw-600"><?= htmlspecialchars($d['fullName']) ?></td>
                            <td class="text-muted"><?= htmlspecialchars($d['jabatan'] ?? '-') ?></td>
                            <td class="text-sm text-muted"><?= htmlspecialchars($d['nama_shift'] ?? '-') ?></td>
                            <td><?= $d['jam_masuk'] ? date('H:i', strtotime($d['jam_masuk'])) : '—' ?></td>
                            <td><?= $d['jam_keluar'] ? date('H:i', strtotime($d['jam_keluar'])) : '—' ?></td>
                            <td>
                                <?php if ($d['status_masuk'] === 'tepat_waktu'): ?>
                                    <span class="kopi-badge badge-success">✅ Tepat</span>
                                <?php elseif ($d['status_masuk'] === 'terlambat'): ?>
                                    <span class="kopi-badge badge-warning">⚠️ Telat <?= $d['menit_terlambat'] ?>m</span>
                                <?php else: ?>
                                    <span class="kopi-badge badge-danger">❌ Alpha</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($d['status_pulang'] === 'tepat_waktu'): ?>
                                    <span class="kopi-badge badge-success">✅</span>
                                <?php elseif ($d['status_pulang'] === 'lembur'): ?>
                                    <span class="kopi-badge badge-gold">🔥 Lembur</span>
                                <?php elseif ($d['status_pulang'] === 'lebih_awal'): ?>
                                    <span class="kopi-badge badge-warning">⬅ Awal</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
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
                <div class="kopi-empty-text">Tidak ada data untuk filter ini</div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
