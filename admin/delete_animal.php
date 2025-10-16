<?php
// admin/delete_animal.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/animal.php';

$auth = new AuthHandler();
$animal_handler = new AnimalHandler();

// Membutuhkan akses admin
$auth->require_admin();

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $hewan_id = (int)$_GET['id'];
    $result = $animal_handler->delete_animal($hewan_id);
    
    // Redirect kembali ke halaman animals.php dengan pesan status
    $message = urlencode($result['message']);
    $type = $result['success'] ? 'success' : 'error';
    
    redirect("animals.php?message={$message}&type={$type}");
} else {
    redirect("animals.php?message=" . urlencode("ID hewan tidak valid.") . "&type=error");
}
?>