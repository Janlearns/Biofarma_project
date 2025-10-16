<?php
// api/generate_report.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../handlers/animal.php';
require_once __DIR__ . '/../handlers/inventaris.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil parameter dari URL, pastikan nilainya nggak kosong
$tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : null;
$bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : null;
$unit = isset($_GET['unit']) && $_GET['unit'] !== '' ? sanitize_input($_GET['unit']) : null;

// Buat instance AnimalHandler dan InventarisHandler
$animal_handler = new AnimalHandler();
$inventaris_handler = new InventarisHandler();

// Ambil data laporan yang sudah diagregasi dari database
$report_summary = $animal_handler->get_aggregated_report_data($tahun, $bulan, $unit);
$all_hewan_unfiltered = $animal_handler->get_all_animals_unfiltered();
$uji_hewan_summary = $animal_handler->get_uji_hewan_summary($tahun, $bulan, $unit);

// Ambil data inventaris (tanpa filter, sesuai permintaan)
$inventaris_data = $inventaris_handler->get_all_inventaris_for_report();

// --------- Logic untuk Header Laporan ---------
$report_title = "REPORT";
$unit_info = "Unit: " . (empty($unit) ? "Semua Unit" : htmlspecialchars($unit));
$secondary_header_text = "1. Rekapitulasi Pemakaian Hewan Lab Per Bagian";
$third_header_text = "2. Rekapitulasi Pemakaian Hewan Lab Per Produk";
$fourth_header_text = "3. Rekapitulasi Pemakaian Inventory";

$date_info = "";
if ($bulan && $tahun) {
    $monthName = date("F", mktime(0, 0, 0, $bulan, 10));
    $date_info = " Bulan " . $monthName . " Tahun " . $tahun;
} elseif ($bulan) {
    $monthName = date("F", mktime(0, 0, 0, $bulan, 10));
    $date_info = " Bulan " . $monthName;
} elseif ($tahun) {
    $date_info = " Tahun " . $tahun;
} else {
    $date_info = " Semua Tanggal";
}

$secondary_header_text .= " ($date_info)";
$third_header_text .= " ($date_info)";

// --------- Buat HTML untuk dokumen PDF ---------
$html = '<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 11px; }
        .report-header { text-align: center; margin-bottom: 20px; }
        .report-title { font-size: 18px; font-weight: bold; }
        .unit-info { font-size: 10px; color: #555; }
        .secondary-header { font-size: 14px; font-weight: bold; text-align: left; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        td { border: 1px solid #000; padding: 8px; text-align: center; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .header-cell { font-size: 12px; }
    </style>
</head>
<body>';

// Bagian Header Laporan
$html .= '<div class="report-header">';
$html .= '<div class="report-title">' . htmlspecialchars($report_title) . '</div>';
$html .= '<div class="unit-info">' . htmlspecialchars($unit_info) . '</div>';
$html .= '</div>';

$html .= '<div class="secondary-header">';
$html .= htmlspecialchars($secondary_header_text);
$html .= '</div>';

// Tabel Pertama: Rekap Pesanan
$divisi_count = count($report_summary['divisi_columns']);
$html .= '<table>';
$html .= '<tr>';
$html .= '<td rowspan="2" class="bold text-left header-cell">Rekap Pesanan</td>';
$html .= '<td rowspan="2" class="bold header-cell">total</td>';
$html .= '<td colspan="' . $divisi_count . '" class="bold header-cell">Divisi/Bagian</td>';
$html .= '</tr>';
$html .= '<tr>';
foreach ($report_summary['divisi_columns'] as $divisi_name) {
    $html .= '<td class="bold">' . htmlspecialchars($divisi_name) . '</td>';
}
$html .= '</tr>';
$no = 1;
foreach ($report_summary['animals'] as $animal_data) {
    $html .= '<tr>';
    $html .= '<td class="text-left">' . $no++ . '. ' . htmlspecialchars($animal_data['nama_hewan']) . '</td>';
    $html .= '<td>' . htmlspecialchars($animal_data['total_per_animal']) . ' ekor</td>';
    foreach ($report_summary['divisi_columns'] as $divisi_name) {
        $html .= '<td>' . (isset($animal_data['divisi_counts'][$divisi_name]) ? htmlspecialchars($animal_data['divisi_counts'][$divisi_name]) : '0') . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</table>';

$html .= '<div style="page-break-before: always;"></div>';

$html .= '<div class="secondary-header">';
$html .= htmlspecialchars($third_header_text);
$html .= '</div>';

// Tabel Kedua: Uji vs Hewan
$html .= '<table>';
$html .= '<tr>';
$html .= '<td rowspan="2" class="bold text-left header-cell">Produk</td>';
$html .= '<td colspan="' . count($all_hewan_unfiltered) . '" class="bold header-cell">Hewan</td>';
$html .= '</tr>';
$html .= '<tr>';
foreach ($all_hewan_unfiltered as $hewan) {
    $html .= '<td class="bold">' . htmlspecialchars($hewan['nama_hewan']) . '</td>';
}
$no_uji = 1;
$html .= '</tr>';
foreach ($uji_hewan_summary as $uji_name => $uji_data) {
    $html .= '<tr>';
    $html .= '<td class="text-left">' . $no_uji++ . '. ' . htmlspecialchars($uji_name) . '</td>';
    foreach ($all_hewan_unfiltered as $hewan) {
        $total = $uji_data['hewan_counts'][$hewan['id']]['total_hewan'] ?? 0;
        $html .= '<td>' . $total . '</td>';
    }
    $html .= '</tr>';
}
$html .= '</table>';

$html .= '<div style="page-break-before: always;"></div>';

$html .= '<div class="secondary-header">';
$html .= htmlspecialchars($fourth_header_text);
$html .= '</div>';

// Tabel Ketiga: Data Inventaris
$html .= '<table>';
$html .= '<thead>';
$html .= '<tr>';
$html .= '<th class="bold header-cell">No</th>';
$html .= '<th class="bold header-cell">Tanggal Datang</th>';
$html .= '<th class="bold header-cell">Kode Barang</th>';
$html .= '<th class="bold header-cell">Jenis Barang</th>';
$html .= '<th class="bold header-cell">No Batch</th>';
$html .= '<th class="bold header-cell">ED</th>';
$html .= '<th class="bold header-cell">Jumlah</th>';
$html .= '<th class="bold header-cell">Terpakai</th>';
$html .= '<th class="bold header-cell">Saldo Akhir</th>';
$html .= '</tr>';
$html .= '</thead>';
$html .= '<tbody>';

if (!empty($inventaris_data)) {
    $no = 1;
    foreach ($inventaris_data as $item) {
        $html .= '<tr>';
        $html .= '<td>' . $no++ . '</td>';
        $html .= '<td>' . htmlspecialchars($item['tanggal_datang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['kode_barang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['jenis_barang']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['no_batch']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['expired_date']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['total_barang']) . ' ' . htmlspecialchars($item['satuan']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['terpakai']) . ' ' . htmlspecialchars($item['satuan']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['saldo_pakai']) . ' ' . htmlspecialchars($item['satuan']) . '</td>';
        $html .= '</tr>';
    }
} else {
    $html .= '<tr><td colspan="8" style="text-align: center;">Tidak ada data inventaris.</td></tr>';
}

$html .= '</tbody>';
$html .= '</table>';

$html .= '</body></html>';

// Atur opsi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Muat HTML ke Dompdf dan render
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'potrait');
$dompdf->render();

// Atur header HTTP untuk unduhan dan kirimkan file
$filename = 'rekap_pesanan_' . date('Ymd_His') . '.pdf';
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($dompdf->output()));

echo $dompdf->output();
exit;
