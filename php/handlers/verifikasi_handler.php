<?php
session_start();
include_once __DIR__ . '/../../php/connection.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['verify_id'])) {
    echo json_encode(['success' => false]); exit;
}

$id      = intval($_POST['verify_id']);
$adminId = intval($_SESSION['user']['id']);
$catatan = $connect->real_escape_string($_POST['catatan'] ?? '');
$now     = date('Y-m-d H:i:s');

$connect->query("UPDATE absensi SET verified_by=$adminId, verified_at='$now', catatan_admin='$catatan' WHERE id=$id");
echo json_encode(['success' => true]);
