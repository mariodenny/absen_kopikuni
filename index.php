<?php
define("APP", true);
session_start();

include __DIR__ . '/php/connection.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Sanitize page param
$page = preg_replace('/[^a-zA-Z0-9_\/]/', '', $page);

// Protected pages (must be logged in)
$protected = ['absen', 'karyawan/dashboard', 'karyawan/history', 'karyawan/izin',
              'admin/dashboard', 'admin/verifikasi', 'admin/rekap',
              'admin/shift_settings', 'admin/karyawan_list', 'admin/persetujuan_izin'];

// Admin-only pages
$adminOnly = ['admin/dashboard', 'admin/verifikasi', 'admin/rekap',
              'admin/shift_settings', 'admin/karyawan_list', 'admin/persetujuan_izin'];

// Karyawan-only pages
$karyawanOnly = ['karyawan/dashboard', 'karyawan/history', 'karyawan/izin'];

function checkAuth() {
    if (!isset($_SESSION['user'])) {
        header("Location: index.php?page=login");
        exit;
    }
}

function checkRole($role) {
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== $role) {
        header("Location: index.php?page=403");
        exit;
    }
}

if (in_array($page, $protected)) checkAuth();
if (in_array($page, $adminOnly)) checkRole('admin');
if (in_array($page, $karyawanOnly)) checkRole('karyawan');

// Handle logout
if ($page === 'logout') {
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

$path = "components/{$page}.php";

include 'partials/header.php';

if (file_exists($path)) {
    include $path;
} else {
    include "components/404.php";
}

include 'partials/footer.php';
?>
