<?php
// api/get-slot-data.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/auth.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
if (!$auth->is_logged_in() || !$auth->is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid animal ID']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn === null) {
        throw new Exception("Koneksi database gagal.");
    }
    
    $hewan_id = (int)$_GET['id'];
    
    // Ambil data pemesanan untuk bulan dan tahun saat ini
    $query = "SELECT DAY(created_at) as booking_day, SUM(jumlah_hewan) as total_hewan
              FROM pemesanan_kandang
              WHERE hewan_id = :hewan_id AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())
              GROUP BY booking_day
              ORDER BY booking_day ASC";

    $stmt = $conn->prepare($query); 
    $stmt->bindParam(':hewan_id', $hewan_id);
    $stmt->execute();
    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Siapkan array data untuk setiap hari di bulan ini
    $num_days_in_month = date('t');
    $chart_data_map = [];
    for ($i = 1; $i <= $num_days_in_month; $i++) {
        $chart_data_map[$i] = 0;
    }

    // Masukkan data dari database ke array
    foreach ($raw_data as $row) {
        $chart_data_map[(int)$row['booking_day']] = (int)$row['total_hewan'];
    }
    
    // Proses data menjadi format yang cocok untuk Chart.js
    $chart_labels = [];
    $chart_values = [];
    foreach ($chart_data_map as $day => $value) {
        $chart_labels[] = $day;
        $chart_values[] = $value;
    }

    echo json_encode([
        'success' => true,
        'chart_data' => [
            'labels' => $chart_labels,
            'data' => $chart_values
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>