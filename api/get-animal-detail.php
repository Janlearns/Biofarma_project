<?php
// api/get-animal-detail.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../handlers/animal.php';

header('Content-Type: application/json');

$auth = new AuthHandler();

// Check if user is logged in
if (!$auth->is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid animal ID']);
    exit;
}

$animal_handler = new AnimalHandler();
$animal_id = (int)$_GET['id'];

try {
    $animal = $animal_handler->get_animal_by_id($animal_id);
    
    if (!$animal) {
        echo json_encode(['success' => false, 'message' => 'Animal not found']);
        exit;
    }
    
    // Get kandang details
    $kandang_list = $animal_handler->get_kandang_by_animal_id($animal_id);
    
    echo json_encode([
        'success' => true,
        'animal' => $animal,
        'kandang' => $kandang_list
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>