<?php
// api/update_booking_status.php
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['no_karantina']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}

$no_karantina = sanitize_input($_POST['no_karantina']);
$status = sanitize_input($_POST['status']);

$result = $animal_handler->update_booking_status($no_karantina, $status);

echo json_encode($result);
?>