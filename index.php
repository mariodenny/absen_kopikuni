<?php
define("APP", true);
include 'partials/header.php';
include 'php/auth.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

$protected_pages = ['admin', 'karyawan'];

$admin_pages = ['admin'];

if (in_array($page, $protected_pages)) {
    checkAuth();
}

if (in_array($page, $admin_pages)) {
    checkRole('admin');
}

$path = "components/$page.php";

if (file_exists($path)) {
    include $path;
} else {
    include "components/404.php";
}
