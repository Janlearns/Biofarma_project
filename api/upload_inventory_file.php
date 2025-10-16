<?php
// api/upload_inventory_file.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/inventaris.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
$inventaris_handler = new InventarisHandler();

// Periksa apakah pengguna adalah admin
if (!$auth->is_logged_in() || !$auth->is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Metode request atau ID tidak valid.']);
    exit;
}

$id = (int)$_GET['id'];
$file = $_FILES['uji_organoleptik_file'] ?? null;

if (empty($file) || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'File tidak ditemukan atau terjadi kesalahan saat upload.']);
    exit;
}

try {
    $result = $inventaris_handler->update_inventaris_file($id, $file);
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
}
?>