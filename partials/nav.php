<?php
// Nav is included inside header.php after session_start() is called in index.php
$currentPage = isset($_GET['page']) ? $_GET['page'] : 'home';
$isLoggedIn  = isset($_SESSION['user']);
$role        = $isLoggedIn ? $_SESSION['user']['role'] : null;
$username    = $isLoggedIn ? $_SESSION['user']['username'] : '';
$initials    = $isLoggedIn ? strtoupper(substr($username, 0, 2)) : '';
?>
<nav class="kopi-navbar" id="kopi-navbar">
    <!-- Brand -->
    <a href="index.php?page=home" class="kopi-brand">
        <div class="kopi-brand-icon">☕</div>
        <span class="kopi-brand-name">KopiKuni</span>
    </a>

    <!-- Right side -->
    <div class="kopi-nav-right">
        <?php if ($isLoggedIn): ?>
            <!-- Desktop user info -->
            <div class="kopi-nav-user kopi-desktop-only">
                <div class="kopi-avatar"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <div class="kopi-nav-username"><?= htmlspecialchars($username) ?></div>
                    <div class="kopi-nav-role"><?= $role === 'admin' ? '👑 Admin' : '👤 Karyawan' ?></div>
                </div>
                <a href="index.php?page=logout" class="btn-kopi btn-kopi-ghost btn-kopi-sm">Keluar</a>
            </div>

            <!-- Mobile hamburger -->
            <button id="sidebar-toggle" class="kopi-hamburger kopi-mobile-only" type="button" aria-label="Menu">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
        <?php else: ?>
            <a href="index.php?page=login" class="btn-kopi btn-kopi-primary btn-kopi-sm">Login</a>
        <?php endif; ?>
    </div>
</nav>

<!-- Sidebar Overlay (mobile) -->
<div class="kopi-sidebar-overlay" id="sidebar-overlay"></div>

<?php if ($isLoggedIn): ?>
<!-- Mobile Bottom Navigation -->
<nav class="kopi-bottom-nav kopi-mobile-only" id="kopi-bottom-nav">
    <?php if ($role === 'karyawan'): ?>
        <a href="index.php?page=karyawan/dashboard" class="kopi-bottom-nav-item <?= $currentPage === 'karyawan/dashboard' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">🏠</span>
            <span class="kopi-bottom-nav-label">Home</span>
        </a>
        <a href="index.php?page=absen" class="kopi-bottom-nav-item <?= $currentPage === 'absen' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon kopi-bottom-nav-scan">📷</span>
            <span class="kopi-bottom-nav-label">Absen</span>
        </a>
        <a href="index.php?page=karyawan/history" class="kopi-bottom-nav-item <?= $currentPage === 'karyawan/history' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">📅</span>
            <span class="kopi-bottom-nav-label">History</span>
        </a>
        <a href="index.php?page=karyawan/izin" class="kopi-bottom-nav-item <?= $currentPage === 'karyawan/izin' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">📝</span>
            <span class="kopi-bottom-nav-label">Izin</span>
        </a>
    <?php elseif ($role === 'admin'): ?>
        <a href="index.php?page=admin/dashboard" class="kopi-bottom-nav-item <?= $currentPage === 'admin/dashboard' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">🏠</span>
            <span class="kopi-bottom-nav-label">Home</span>
        </a>
        <a href="index.php?page=admin/verifikasi" class="kopi-bottom-nav-item <?= $currentPage === 'admin/verifikasi' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">✅</span>
            <span class="kopi-bottom-nav-label">Verifikasi</span>
        </a>
        <a href="index.php?page=admin/rekap" class="kopi-bottom-nav-item <?= $currentPage === 'admin/rekap' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">📊</span>
            <span class="kopi-bottom-nav-label">Rekap</span>
        </a>
        <a href="index.php?page=admin/shift_settings" class="kopi-bottom-nav-item <?= str_starts_with($currentPage, 'admin/shift') || str_starts_with($currentPage, 'admin/karyawan') || $currentPage === 'admin/persetujuan_izin' ? 'active' : '' ?>">
            <span class="kopi-bottom-nav-icon">⚙️</span>
            <span class="kopi-bottom-nav-label">Lainnya</span>
        </a>
    <?php endif; ?>
</nav>
<?php endif; ?>