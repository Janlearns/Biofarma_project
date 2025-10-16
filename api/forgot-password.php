<?php
// api/forgot-password.php
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
$user_type = isset($_POST['user_type']) ? sanitize_input($_POST['user_type']) : 'user';

// Memanggil fungsi forgot_password()
$result = $auth->forgot_password($email, $user_type);

if ($result['success']) {
    $_SESSION['reset_email'] = $email;
}

echo json_encode($result);
?>