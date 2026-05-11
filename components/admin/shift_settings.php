<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../../php/connection.php';

$msg   = '';
$error = '';

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama     = $connect->real_escape_string(trim($_POST['nama_shift'] ?? ''));
        $masuk    = $connect->real_escape_string($_POST['jam_masuk'] ?? '');
        $pulang   = $connect->real_escape_string($_POST['jam_pulang'] ?? '');
        $toleransi = intval($_POST['toleransi_menit'] ?? 15);
        $overnight = isset($_POST['is_overnight']) ? 1 : 0;

        if ($nama && $masuk && $pulang) {
            $connect->query("INSERT INTO shifts (nama_shift, jam_masuk, toleransi_menit, jam_pulang, is_overnight)
                             VALUES ('$nama', '$masuk', $toleransi, '$pulang', $overnight)");
            $msg = 'Shift berhasil ditambahkan!';
        } else {
            $error = 'Semua field wajib diisi.';
        }
    } elseif ($action === 'delete') {
        $id = intval($_POST['shift_id']);
        $connect->query("DELETE FROM shifts WHERE id = $id");
        $msg = 'Shift berhasil dihapus.';
    } elseif ($action === 'edit') {
        $id       = intval($_POST['shift_id']);
        $nama     = $connect->real_escape_string(trim($_POST['nama_shift'] ?? ''));
        $masuk    = $connect->real_escape_string($_POST['jam_masuk'] ?? '');
        $pulang   = $connect->real_escape_string($_POST['jam_pulang'] ?? '');
        $toleransi = intval($_POST['toleransi_menit'] ?? 15);
        $overnight = isset($_POST['is_overnight']) ? 1 : 0;
        $connect->query("UPDATE shifts SET nama_shift='$nama', jam_masuk='$masuk', jam_pulang='$pulang', toleransi_menit=$toleransi, is_overnight=$overnight WHERE id=$id");
        $msg = 'Shift berhasil diperbarui!';
    }
}

$shifts = $connect->query("SELECT * FROM shifts ORDER BY jam_masuk, nama_shift")->fetch_all(MYSQLI_ASSOC);
?>

<div class="kopi-layout">
    <?php include __DIR__ . '/../../partials/sidebar_admin.php'; ?>

    <main class="kopi-content fade-in">
        <div class="kopi-page-header">
            <h1 class="kopi-page-title">⏰ Setting Shift</h1>
            <p class="kopi-page-sub">Kelola jam masuk, jam pulang, dan toleransi per shift</p>
        </div>

        <?php if ($msg): ?>
        <div class="kopi-alert kopi-alert-success mb-3">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="kopi-alert kopi-alert-error mb-3">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="kopi-grid grid-2 mb-4" style="align-items:start">
            <!-- Add Shift Form -->
            <div class="kopi-card">
                <div class="kopi-card-header">
                    <h3 class="kopi-card-title">➕ Tambah Shift Baru</h3>
                </div>
                <div class="kopi-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="kopi-form-group">
                            <label class="kopi-label">Nama Shift *</label>
                            <input type="text" name="nama_shift" class="kopi-input"
                                placeholder="Contoh: Pagi - Barista" required>
                        </div>
                        <div class="kopi-grid grid-2" style="gap:.75rem">
                            <div class="kopi-form-group" style="margin-bottom:0">
                                <label class="kopi-label">Jam Masuk *</label>
                                <input type="time" name="jam_masuk" class="kopi-input" required>
                            </div>
                            <div class="kopi-form-group" style="margin-bottom:0">
                                <label class="kopi-label">Jam Pulang *</label>
                                <input type="time" name="jam_pulang" class="kopi-input" required>
                            </div>
                        </div>
                        <div class="kopi-form-group mt-2">
                            <label class="kopi-label">Toleransi Keterlambatan (menit)</label>
                            <input type="number" name="toleransi_menit" class="kopi-input"
                                value="15" min="0" max="60">
                        </div>
                        <label style="display:flex; align-items:center; gap:.5rem; cursor:pointer; font-size:.875rem; color:var(--kopi-muted); margin-bottom:1rem">
                            <input type="checkbox" name="is_overnight"> Shift melewati tengah malam
                        </label>
                        <button type="submit" class="btn-kopi btn-kopi-primary btn-kopi-full">➕ Tambah Shift</button>
                    </form>
                </div>
            </div>

            <!-- Info Box -->
            <div class="kopi-card">
                <div class="kopi-card-header">
                    <h3 class="kopi-card-title">📌 Ketentuan Shift Kopi Kuni</h3>
                </div>
                <div class="kopi-card-body">
                    <div style="font-size:.8rem; color:var(--kopi-muted); line-height:1.8">
                        <div style="margin-bottom:1rem">
                            <span class="kopi-badge badge-gold mb-1">Shift Pagi</span>
                            <table style="width:100%; font-size:.8rem; margin-top:.5rem">
                                <tr><td>Cleaning Service</td><td>07.00 (±15 mnt)</td><td>Pulang 15.00</td></tr>
                                <tr><td>Server</td><td>08.00 (±15 mnt)</td><td>Pulang 16.00</td></tr>
                                <tr><td>Barista</td><td>08.00 (±15 mnt)</td><td>Pulang 16.00</td></tr>
                            </table>
                        </div>
                        <div>
                            <span class="kopi-badge badge-info mb-1">Shift Siang</span>
                            <table style="width:100%; font-size:.8rem; margin-top:.5rem">
                                <tr><td>Cleaning Service</td><td>15.00 (±15 mnt)</td><td>Pulang 23.00</td></tr>
                                <tr><td>Server</td><td>15.00 (±15 mnt)</td><td>Pulang 23.00</td></tr>
                                <tr><td>Barista</td><td>15.00 (±15 mnt)</td><td>Pulang 23.00</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shifts Table -->
        <div class="kopi-card">
            <div class="kopi-card-header">
                <h3 class="kopi-card-title">📋 Daftar Shift</h3>
                <span class="kopi-badge badge-info"><?= count($shifts) ?> shift</span>
            </div>
            <div class="kopi-table-wrapper">
                <table class="kopi-table">
                    <thead>
                        <tr>
                            <th>Nama Shift</th>
                            <th>Jam Masuk</th>
                            <th>Toleransi</th>
                            <th>Jam Pulang</th>
                            <th>Overnight</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $sh): ?>
                        <tr>
                            <td class="fw-600"><?= htmlspecialchars($sh['nama_shift']) ?></td>
                            <td><span class="kopi-badge badge-success"><?= substr($sh['jam_masuk'],0,5) ?></span></td>
                            <td><span class="kopi-badge badge-warning"><?= $sh['toleransi_menit'] ?> mnt</span></td>
                            <td><span class="kopi-badge badge-info"><?= substr($sh['jam_pulang'],0,5) ?></span></td>
                            <td><?= $sh['is_overnight'] ? '<span class="kopi-badge badge-muted">Ya</span>' : '—' ?></td>
                            <td>
                                <div class="flex gap-1">
                                    <button class="btn-kopi btn-kopi-ghost btn-kopi-sm"
                                        onclick="editShift(<?= htmlspecialchars(json_encode($sh)) ?>)">✏️ Edit</button>
                                    <form method="POST" onsubmit="return confirm('Hapus shift ini?')" style="display:inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="shift_id" value="<?= $sh['id'] ?>">
                                        <button type="submit" class="btn-kopi btn-kopi-danger btn-kopi-sm">🗑 Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- Edit Modal -->
<div class="kopi-modal-overlay" id="edit-modal">
    <div class="kopi-modal">
        <h3 class="kopi-modal-title">✏️ Edit Shift</h3>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="shift_id" id="edit-id">
            <div class="kopi-form-group">
                <label class="kopi-label">Nama Shift</label>
                <input type="text" name="nama_shift" id="edit-nama" class="kopi-input" required>
            </div>
            <div class="kopi-grid grid-2" style="gap:.75rem">
                <div class="kopi-form-group" style="margin-bottom:0">
                    <label class="kopi-label">Jam Masuk</label>
                    <input type="time" name="jam_masuk" id="edit-masuk" class="kopi-input" required>
                </div>
                <div class="kopi-form-group" style="margin-bottom:0">
                    <label class="kopi-label">Jam Pulang</label>
                    <input type="time" name="jam_pulang" id="edit-pulang" class="kopi-input" required>
                </div>
            </div>
            <div class="kopi-form-group mt-2">
                <label class="kopi-label">Toleransi (menit)</label>
                <input type="number" name="toleransi_menit" id="edit-toleransi" class="kopi-input" min="0" max="60">
            </div>
            <div class="flex gap-1 mt-3">
                <button type="submit" class="btn-kopi btn-kopi-primary">💾 Simpan</button>
                <button type="button" class="btn-kopi btn-kopi-ghost" onclick="document.getElementById('edit-modal').classList.remove('active')">Batal</button>
            </div>
        </form>
    </div>
</div>

<script>
function editShift(data) {
    document.getElementById('edit-id').value       = data.id;
    document.getElementById('edit-nama').value     = data.nama_shift;
    document.getElementById('edit-masuk').value    = data.jam_masuk.substring(0,5);
    document.getElementById('edit-pulang').value   = data.jam_pulang.substring(0,5);
    document.getElementById('edit-toleransi').value = data.toleransi_menit;
    document.getElementById('edit-modal').classList.add('active');
}
</script>
