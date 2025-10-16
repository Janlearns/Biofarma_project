<?php
// api/delete_inventory_file.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/inventaris.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
$inventaris_handler = new InventarisHandler();

if (!$auth->is_logged_in() || !$auth->is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID inventaris tidak valid.']);
    exit;
}

$id = (int)$_POST['id']; // Ambil ID dari $_POST
$result = $inventaris_handler->delete_inventory_file($id);

echo json_encode($result);
?>