<?php
session_start();

function checkAuth()
{
    if (!isset($_SESSION['user'])) {
        header("Location: index.php?page=login");
        exit;
    }
}

function checkRole($role)
{
    if ($_SESSION['user']['role'] !== $role) {
        include "components/403.php";
        exit;
    }
}