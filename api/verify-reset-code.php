<?php
// api/verify-reset-code.php
session_start();
require_once '../config/database.php';
require_once '../auth/auth.php';

header('Content-Type: application/json');

$auth = new AuthHandler();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

$email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
$code = isset($_POST['code']) ? sanitize_input($_POST['code']) : '';

$result = $auth->verify_reset_code($email, $code);
if ($result['success']) {
    $_SESSION['can_reset_password'] = true; // Set flag untuk akses halaman reset-password
    $_SESSION['reset_user_type'] = $result['user_type'];
}

echo json_encode($result);
?>