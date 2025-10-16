<?php
// api/delete_inventaris.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/inventaris.php'; // Pastikan handler ini di-include

header('Content-Type: application/json');

$auth = new AuthHandler();
$inventaris_handler = new InventarisHandler();

if (!$auth->is_logged_in() || !$auth->is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inventaris tidak valid.']);
    exit;
}

$id = (int)$_GET['id'];
$result = $inventaris_handler->delete_inventaris($id);

echo json_encode($result);
?>