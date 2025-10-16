<?php
// api/update-bpab.php
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
$divisi = sanitize_input($_POST['divisi']);
$kepala = sanitize_input($_POST['kepala']);
$no_permintaan = sanitize_input($_POST['no_permintaan']);
$keterangan = sanitize_input($_POST['keterangan']);

// Tambahkan metode update_bpab_data di class AnimalHandler
$result = $animal_handler->update_bpab_data($no_karantina, $divisi, $kepala, $no_permintaan, $keterangan);

echo json_encode($result);
?>