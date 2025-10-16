<?php
// api/update_inventaris.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/inventaris.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
$inventaris_handler = new InventarisHandler();

$auth->require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : null;
$terpakai = isset($_POST['terpakai']) ? (float)$_POST['terpakai'] : null;

if (empty($id) || !isset($terpakai)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$result = $inventaris_handler->update_inventaris_terpakai($id, $terpakai);

echo json_encode($result);
?>