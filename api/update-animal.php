<?php
// api/update-animal.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/animal.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
$animal_handler = new AnimalHandler();

$auth->require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID hewan tidak valid.']);
    exit;
}

$id = (int)$_POST['id'];
$nama_hewan = sanitize_input($_POST['nama_hewan']);
$total_kandang = (int)$_POST['total_kandang'];
$kapasitas_per_kandang = (int)$_POST['kapasitas_per_kandang'];
$deskripsi = sanitize_input($_POST['deskripsi']);

if (empty($nama_hewan) || $total_kandang < 1 || $kapasitas_per_kandang < 1) {
    echo json_encode(['success' => false, 'message' => 'Semua field harus diisi dengan benar.']);
    exit;
}

$result = $animal_handler->update_animal($id, $nama_hewan, $total_kandang, $kapasitas_per_kandang, $deskripsi);

echo json_encode($result);
?>