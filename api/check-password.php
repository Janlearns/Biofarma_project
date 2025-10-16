<?php
// api/check-password.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
if (!$auth->is_logged_in() || !$auth->is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password_input = $input['password'] ?? '';

if (empty($password_input)) {
    echo json_encode(['success' => false, 'message' => 'Password harus diisi.']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn === null) {
        throw new PDOException("Koneksi database gagal.");
    }
    
    $query = "SELECT password FROM password LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $stored_password = $stmt->fetchColumn();

    if ($stored_password && password_verify($password_input, $stored_password)) {
        // Tambahkan variabel session untuk mengizinkan akses ke halaman tambah admin
        $_SESSION['can_add_admin'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Password salah.']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Kesalahan database: ' . $e->getMessage()]);
}
?>