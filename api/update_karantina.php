<?php
// api/update_karantina.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/animal.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
$animal_handler = new AnimalHandler();

if (!$auth->is_logged_in() || !$auth->is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

$no_karantina = sanitize_input($_POST['no_karantina']);
$no_pengiriman = sanitize_input($_POST['no_pengiriman']);
$jumlah_hewan_datang = (int)$_POST['jumlah_hewan_datang'];
$lulus = (int)$_POST['lulus'];
$tidak_lulus = (int)$_POST['tidak_lulus'];

$result = $animal_handler->update_karantina_data($no_karantina, $no_pengiriman, $jumlah_hewan_datang, $lulus, $tidak_lulus);

echo json_encode($result);
?>