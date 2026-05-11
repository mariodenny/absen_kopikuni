<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../php/connection.php';

$error   = '';
$success = '';

// Fetch jabatan list
$jabatanList = $connect->query("SELECT * FROM jabatan ORDER BY id")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username    = trim($_POST['username'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $password2   = $_POST['password2'] ?? '';
    $jabatan_id  = intval($_POST['jabatan_id'] ?? 0);
    $fullName    = trim($_POST['fullName'] ?? '');
    $no_hp       = trim($_POST['no_hp'] ?? '');

    if (!$username || !$email || !$password || !$jabatan_id || !$fullName) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password2) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Check email unique
        $chk = $connect->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = 'Email sudah terdaftar.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $connect->begin_transaction();
            try {
                $stmt = $connect->prepare("INSERT INTO users (email, password, username, role) VALUES (?, ?, ?, 'karyawan')");
                $stmt->bind_param("sss", $email, $hashed, $username);
                $stmt->execute();
                $user_id = $connect->insert_id;
                $stmt->close();

                // Generate QR token
                $qr_token = bin2hex(random_bytes(16));

                $stmt2 = $connect->prepare("INSERT INTO karyawan (user_id, jabatan_id, fullName, qr_code, no_hp) VALUES (?, ?, ?, ?, ?)");
                $stmt2->bind_param("iisss", $user_id, $jabatan_id, $fullName, $qr_token, $no_hp);
                $stmt2->execute();
                $stmt2->close();

                $connect->commit();
                header("Location: index.php?page=login&registered=1");
                exit;
            } catch (Exception $e) {
                $connect->rollback();
                $error = 'Terjadi kesalahan, silakan coba lagi.';
            }
        }
        $chk->close();
    }
}
?>

<div class="kopi-auth-wrapper">
    <div class="kopi-auth-card fade-in" style="max-width:500px">
        <div class="text-center mb-4">
            <div style="font-size:2rem; margin-bottom:.5rem">☕</div>
            <div style="font-size:1.4rem; font-weight:800; background:var(--grad-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text">Daftar Akun</div>
            <p class="text-muted text-sm mt-1">KopiKuni — Sistem Absensi Karyawan</p>
        </div>

        <?php if ($error): ?>
        <div class="kopi-alert kopi-alert-error mb-3">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="kopi-grid grid-2" style="gap:.75rem">
                <div class="kopi-form-group" style="margin-bottom:0">
                    <label class="kopi-label">Nama Lengkap *</label>
                    <input type="text" name="fullName" class="kopi-input" placeholder="John Doe"
                        value="<?= htmlspecialchars($_POST['fullName'] ?? '') ?>" required>
                </div>
                <div class="kopi-form-group" style="margin-bottom:0">
                    <label class="kopi-label">Username *</label>
                    <input type="text" name="username" class="kopi-input" placeholder="johndoe"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="kopi-form-group mt-2">
                <label class="kopi-label">Jabatan *</label>
                <select name="jabatan_id" class="kopi-select" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <?php foreach ($jabatanList as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= (($_POST['jabatan_id'] ?? '') == $j['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($j['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="kopi-form-group">
                <label class="kopi-label">Email *</label>
                <input type="email" name="email" class="kopi-input" placeholder="nama@kopikuni.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="kopi-form-group">
                <label class="kopi-label">No. HP</label>
                <input type="text" name="no_hp" class="kopi-input" placeholder="08xx-xxxx-xxxx"
                    value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>">
            </div>

            <div class="kopi-grid grid-2" style="gap:.75rem">
                <div class="kopi-form-group" style="margin-bottom:0">
                    <label class="kopi-label">Password *</label>
                    <input type="password" name="password" class="kopi-input" placeholder="Min. 6 karakter" required>
                </div>
                <div class="kopi-form-group" style="margin-bottom:0">
                    <label class="kopi-label">Konfirmasi Password *</label>
                    <input type="password" name="password2" class="kopi-input" placeholder="Ulangi password" required>
                </div>
            </div>

            <button type="submit" class="btn-kopi btn-kopi-primary btn-kopi-full btn-kopi-lg mt-3">
                Daftar Sekarang →
            </button>
        </form>

        <hr class="kopi-divider">
        <p class="text-center text-sm text-muted">
            Sudah punya akun?
            <a href="index.php?page=login" style="color:var(--kopi-gold); font-weight:600; text-decoration:none">Login</a>
        </p>
    </div>
</div>
