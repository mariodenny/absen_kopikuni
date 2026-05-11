<?php
if (!defined("APP")) { header("Location: /absensi_kopikuni/"); exit; }
include_once __DIR__ . '/../php/connection.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email dan password wajib diisi.';
    } else {
        $stmt = $connect->prepare("SELECT u.*, k.id as karyawan_id, k.fullName FROM users u LEFT JOIN karyawan k ON k.user_id = u.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id'          => $user['id'],
                'email'       => $user['email'],
                'username'    => $user['username'],
                'fullName'    => $user['fullName'] ?? $user['username'],
                'role'        => $user['role'],
                'karyawan_id' => $user['karyawan_id'],
            ];
            if ($user['role'] === 'admin') {
                header("Location: index.php?page=admin/dashboard");
            } else {
                header("Location: index.php?page=karyawan/dashboard");
            }
            exit;
        } else {
            $error = 'Email atau password salah.';
        }
    }
}
?>

<div class="kopi-auth-wrapper">
    <div class="kopi-auth-card fade-in">
        <!-- Logo -->
        <div class="text-center mb-4">
            <div style="font-size:2.5rem; margin-bottom:.5rem">☕</div>
            <div style="font-size:1.5rem; font-weight:800; background:var(--grad-primary); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text">KopiKuni</div>
            <p class="text-muted text-sm mt-1">Sistem Absensi Karyawan</p>
        </div>

        <?php if ($error): ?>
        <div class="kopi-alert kopi-alert-error mb-3">
            <span>⚠️</span> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
        <div class="kopi-alert kopi-alert-success mb-3">
            <span>✅</span> Registrasi berhasil! Silakan login.
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="kopi-form-group">
                <label class="kopi-label">Email</label>
                <div class="kopi-input-group">
                    <span class="kopi-input-group-icon">✉️</span>
                    <input type="email" name="email" id="email" class="kopi-input"
                        placeholder="nama@kopikuni.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="kopi-form-group">
                <label class="kopi-label">Password</label>
                <div class="kopi-input-group">
                    <span class="kopi-input-group-icon">🔒</span>
                    <input type="password" name="password" id="password" class="kopi-input"
                        placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn-kopi btn-kopi-primary btn-kopi-full btn-kopi-lg mt-2">
                Masuk →
            </button>
        </form>

        <hr class="kopi-divider">

        <p class="text-center text-sm text-muted">
            Belum punya akun?
            <a href="index.php?page=register" style="color:var(--kopi-gold); font-weight:600; text-decoration:none">Daftar Sekarang</a>
        </p>

        <!-- Default credentials hint for dev -->
        <div style="margin-top:1.5rem; padding:.75rem 1rem; background:rgba(212,168,83,0.05); border:1px solid rgba(212,168,83,0.15); border-radius:8px; font-size:.75rem; color:var(--kopi-muted)">
            <strong style="color:var(--kopi-gold)">Demo Admin:</strong><br>
            Email: admin@kopikuni.com &nbsp;|&nbsp; Password: password
        </div>
    </div>
</div>