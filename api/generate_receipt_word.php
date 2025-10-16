<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../handlers/animal.php';

// Cek nomor karantina
if (!isset($_GET['no_karantina']) || empty($_GET['no_karantina'])) {
    die("Nomor Pemesanan tidak ditemukan.");
}

$animal_handler = new AnimalHandler();
$no_karantina = $_GET['no_karantina'];
// Ambil data pemesanan dari database
$booking_data = $animal_handler->search_booking($no_karantina, 'no_karantina');

if (!$booking_data) {
    die("Data pemesanan tidak ditemukan.");
}

// Buat PHPWord
$phpWord = new \PhpOffice\PhpWord\PhpWord();
$section = $phpWord->addSection();

$section->addText('Pemesanan Berhasil!', ['size' => 16, 'bold' => true]);
$section->addText('Resi Pemesanan', ['size' => 12]);
$section->addTextBreak(1);

$section->addText('No Pemesanan: ' . $booking_data['no_order']);
$section->addText('No Karantina: ' . $booking_data['no_karantina']);
$section->addText('Hewan: ' . $booking_data['nama_hewan']);
$section->addText('Tanggal Datang: ' . date('d-m-Y', strtotime($booking_data['tanggal_datang'])));
$section->addText('Uji: ' . $booking_data['uji']);
$section->addText('Status: ' . ucfirst($booking_data['status']));

// Header untuk download Word
$filename = 'resi_' . $booking_data['no_order'] . '.docx';
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Save langsung ke browser
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save('php://output');
exit;
