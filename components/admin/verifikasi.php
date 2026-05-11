<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

// Handle AJAX verify
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_id'])) {
    header('Content-Type: application/json');
    $id      = intval($_POST['verify_id']);
    $adminId = intval($_SESSION['user']['id']);
    $catatan = $connect->real_escape_string($_POST['catatan'] ?? '');
    $now     = date('Y-m-d H:i:s');
    $connect->query("UPDATE absensi SET verified_by=$adminId, verified_at='$now', catatan_admin='$catatan' WHERE id=$id");
    echo json_encode(['success' => true]);
    exit;
}

$filterDate = $_GET['tanggal'] ?? date('Y-m-d');
$filterShift = $_GET['shift_id'] ?? '';
$filterStatus = $_GET['status'] ?? '';

// Build query
$where = ["a.tanggal = '$filterDate'"];
$joins  = "";
if ($filterShift) {
    $joins .= " JOIN jadwal_karyawan jk2 ON jk2.id = a.jadwal_id";
    $where[] = "jk2.shift_id = " . intval($filterShift);
}
if ($filterStatus === 'pending')   $where[] = "a.verified_by IS NULL";
if ($filterStatus === 'verified')  $where[] = "a.verified_by IS NOT NULL";
if ($filterStatus === 'terlambat') $where[] = "a.status_masuk = 'terlambat'";

$whereStr = implode(' AND ', $where);

$absenList = $connect->query("
    SELECT a.*, k.fullName, k.no_hp, j.nama as jabatan,
           s.nama_shift, s.jam_masuk as shift_jam_masuk, s.jam_pulang as shift_jam_pulang,
           u.username as verified_by_name
    FROM absensi a
    JOIN karyawan k ON k.id = a.karyawan_id
    LEFT JOIN jabatan j ON j.id = k.jabatan_id
    LEFT JOIN jadwal_karyawan jk ON jk.id = a.jadwal_id
    LEFT JOIN shifts s ON s.id = jk.shift_id
    LEFT JOIN users u ON u.id = a.verified_by
    $joins
    WHERE $whereStr
    ORDER BY a.jam_masuk ASC
")->fetch_all(MYSQLI_ASSOC);

$shifts = $connect->query("SELECT * FROM shifts ORDER BY jam_masuk")->fetch_all(MYSQLI_ASSOC);
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_admin.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">✅ Verifikasi Absensi</h1>
            <p class="kopi-page-sub">Review dan verifikasi kehadiran karyawan</p>
        </div>

        <!-- Alert messages -->
        <div id="verify-alert" style="display:none" class="mb-3"></div>

        <!-- Filters -->
        <div class="kopi-card mb-4">
            <div class="kopi-card-body">
                <form method="GET" class="flex gap-2" style="flex-wrap:wrap; align-items:flex-end">
                    <input type="hidden" name="page" value="admin/verifikasi">
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:150px">
                        <label class="kopi-label">Tanggal</label>
                        <input type="date" name="tanggal" class="kopi-input" value="<?= htmlspecialchars($filterDate) ?>">
                    </div>
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:160px">
                        <label class="kopi-label">Shift</label>
                        <select name="shift_id" class="kopi-select">
                            <option value="">Semua Shift</option>
                            <?php foreach ($shifts as $sh): ?>
                            <option value="<?= $sh['id'] ?>" <?= $filterShift == $sh['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sh['nama_shift']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="kopi-form-group" style="margin-bottom:0; flex:1; min-width:140px">
                        <label class="kopi-label">Status</label>
                        <select name="status" class="kopi-select">
                            <option value="">Semua</option>
                            <option value="pending"   <?= $filterStatus==='pending'?'selected':'' ?>>⏳ Belum Diverifikasi</option>
                            <option value="verified"  <?= $filterStatus==='verified'?'selected':'' ?>>✅ Sudah Diverifikasi</option>
                            <option value="terlambat" <?= $filterStatus==='terlambat'?'selected':'' ?>>⚠️ Terlambat</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-kopi btn-kopi-primary" style="margin-bottom:0; align-self:flex-end">🔍 Filter</button>
                    <a href="index.php?page=admin/verifikasi" class="btn-kopi btn-kopi-ghost" style="align-self:flex-end">Reset</a>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">Daftar Absensi — <?= date('d M Y', strtotime($filterDate)) ?></h3>
                <span class="kopi-badge badge-info"><?= count($absenList) ?> data</span>
            </div>
            <?php if ($absenList): ?>
            <div class="kopi-table-wrapper">
                <table class="kopi-table" id="verify-table">
                    <thead>
                        <tr>
                            <th>Karyawan</th>
                            <th>Jabatan</th>
                            <th>Shift</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status Masuk</th>
                            <th>Pulang</th>
                            <th>Verifikasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($absenList as $row): ?>
                        <tr id="row-<?= $row['id'] ?>">
                            <td>
                                <div class="fw-600"><?= htmlspecialchars($row['fullName']) ?></div>
                                <?php if ($row['no_hp']): ?>
                                <div class="text-muted text-sm"><?= htmlspecialchars($row['no_hp']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                            <td>
                                <div style="font-size:.8rem"><?= htmlspecialchars($row['nama_shift'] ?? '-') ?></div>
                                <?php if ($row['shift_jam_masuk']): ?>
                                <div class="text-muted text-sm"><?= substr($row['shift_jam_masuk'],0,5) ?> - <?= substr($row['shift_jam_pulang'],0,5) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-' ?></td>
                            <td><?= $row['jam_keluar'] ? date('H:i', strtotime($row['jam_keluar'])) : '—' ?></td>
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
                                <?php if ($row['status_pulang'] === 'lembur'): ?>
                                    <span class="kopi-badge badge-info">🔥 Lembur</span>
                                    <?php if ($row['alasan_telat_pulang']): ?>
                                    <div class="text-muted text-sm mt-1" title="<?= htmlspecialchars($row['alasan_telat_pulang']) ?>">
                                        📝 Ada alasan
                                    </div>
                                    <?php endif; ?>
                                <?php elseif ($row['status_pulang'] === 'lebih_awal'): ?>
                                    <span class="kopi-badge badge-warning">⬅ Lebih Awal</span>
                                <?php elseif ($row['status_pulang'] === 'tepat_waktu'): ?>
                                    <span class="kopi-badge badge-success">✅ Tepat</span>
                                <?php else: ?>
                                    <span class="kopi-badge badge-muted">Belum Pulang</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['verified_by']): ?>
                                    <div><span class="kopi-badge badge-success">✅ Verified</span></div>
                                    <div class="text-muted text-sm"><?= htmlspecialchars($row['verified_by_name']) ?></div>
                                    <div class="text-muted text-sm"><?= date('H:i', strtotime($row['verified_at'])) ?></div>
                                <?php else: ?>
                                    <span class="kopi-badge badge-warning">⏳ Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$row['verified_by']): ?>
                                <button class="btn-kopi btn-kopi-success btn-kopi-sm"
                                    onclick="verifyAbsen(<?= $row['id'] ?>)">
                                    ✅ Verifikasi
                                </button>
                                <?php else: ?>
                                <span class="text-muted text-sm">—</span>
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
                <div class="kopi-empty-text">Tidak ada data absensi untuk filter ini</div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function verifyAbsen(id) {
    if (!confirm('Verifikasi absensi ini?')) return;
    fetch('php/handlers/verifikasi_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'verify_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const btn = document.querySelector(`#row-${id} button`);
            if (btn) {
                btn.closest('td').innerHTML = '<span class="kopi-badge badge-success">✅ Verified</span>';
            }
            showAlert('success', '✅ Absensi berhasil diverifikasi');
        }
    });
}

function showAlert(type, msg) {
    const el = document.getElementById('verify-alert');
    const cls = { success: 'kopi-alert-success', error: 'kopi-alert-error' };
    el.className = 'kopi-alert ' + (cls[type] || 'kopi-alert-info');
    el.innerHTML = msg;
    el.style.display = '';
    setTimeout(() => el.style.display = 'none', 4000);
}
</script>
