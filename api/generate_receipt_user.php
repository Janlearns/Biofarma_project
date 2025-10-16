<?php
// api/generate_receipt_user.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../handlers/animal.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['no_karantina']) || empty($_GET['no_karantina'])) {
    die("Nomor Karantina tidak ditemukan.");
}

$animal_handler = new AnimalHandler();
$no_karantina = sanitize_input($_GET['no_karantina']);

// Ambil data pemesanan dari database
$booking_data = $animal_handler->search_booking($no_karantina, 'no_karantina');

if (!$booking_data) {
    die("Data pemesanan tidak ditemukan.");
}

// Buat HTML dari resi
$html = '
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .receipt { width: 109%; max-width: 1000px;  padding: 25px; margin-left:-55px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            td, th { border: 1px solid #ccc; padding: 10px; text-align: left; }
            .header { background-color: #f0f0f0; font-weight: bold; }
            .centered { text-align: center; font-size:12px; }
        </style>
    </head>
    <body>
        <div class="receipt">
        <div style="text-align: right; margin-top:-100px; font-size:12px">
        <p> ' . htmlspecialchars($booking_data['no_order'] ?? '') . '</p>
        </div>
            <h2 style="text-align: center;">Bon Permintaan Antar Bagian</h2>
            <table style="border: none;">
    <tbody>
        <tr>
            <td style="width: 30%; border: none; padding-top: 2px; padding-bottom: 2px;font-size:12px;">Dari Divisi/Bagian/Seksi</td>
            <td style="border: none; padding-top: 2px; padding-bottom: 2px;font-size:12px;">: ' . htmlspecialchars($booking_data['divisi'] ?? '') . '</td>
        </tr>
        <tr>
            <td style="border: none; padding-top: 2px; padding-bottom: 2px; font-size:12px;">Kepada Divisi/Bagian/Seksi</td>
            <td style="border: none; padding-top: 2px; padding-bottom: 2px;font-size:12px;">: ' . htmlspecialchars($booking_data['kepala'] ?? '') . '</td>
        </tr>
        <tr>
            <td style="border: none; padding-top: 2px; padding-bottom: 2px;font-size:12px;">No. Permintaan</td>
            <td style="border: none; padding-top: 2px; padding-bottom: 2px;font-size:12px;">: ' . htmlspecialchars($booking_data['no_permintaan'] ?? '') . '</td>
        </tr>
    </tbody>
</table>
            <table style="border-collapse:collapse; width:100%; margin-left:-5px;">
                <thead>
                    <tr class="header">
                        <th rowspan="2" style="width: 5%; font-size:12px;">No urut</th>
                        <th rowspan="2" style="width: 25%;font-size:12px;">Nama Hewan</th>
                        <th rowspan="2" style="width: 15%;font-size:12px;">ukuran</th>
                        <th rowspan="2" style="width: 10%;font-size:12px;">satuan</th>
                        <th colspan="2" class="centered" style="width: 20%;font-size:12px;">Kwantum</th>
                        <th rowspan="2" style="width: 25%;font-size:12px;">keterangan</th>
                    </tr>
                    <tr class="header">
                        <th class="centered">Diminta</th>
                        <th class="centered">Diberi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td style="font-size:12px;">' . htmlspecialchars($booking_data['nama_hewan'] ?? '') . '</td>
                        <td style="font-size:12px;">' . htmlspecialchars($booking_data['berat'] ?? '') . '</td>
                        <td style="font-size:12px;">' . htmlspecialchars($booking_data['satuan'] ?? '') . '</td>
                        <td style="font-size:12px;">' . htmlspecialchars($booking_data['jumlah_hewan'] ?? '') . '</td>
                        <td style="font-size:12px;"></td>
                        <td style="font-size:12px;">' . htmlspecialchars($booking_data['keterangan']).'</td>
                    </tr>
                    ';
                    
                    for ($i = 2; $i <= 5; $i++) {
                        $html .= '
                        <tr>
                            <td style="font-size:12px;">' . $i . '</td>
                            <td style="font-size:12px;"></td>
                            <td style="font-size:12px;"></td>
                            <td style="font-size:12px;"></td>
                            <td style="font-size:12px;"></td>
                            <td style="font-size:12px;"></td>
                            <td style="font-size:12px;"></td>
                        </tr>
                        ';
                    }

                    $html .= '
                </tbody>
            </table>
            <table style="border-collapse:collapse; width:100%; text-align:center;">
  <tr>
    <td style="border:0px solid #000; padding:10px; text-align:left; vertical-align:middle; font-size:12px;">
      Di berikan Tgl. ............ 20 ......
    </td>
    <td style="border:none; padding:10px;"></td>
    <td style="border:0px solid #000; padding:10px; text-align:left; vertical-align:middle; font-size:12px;">
      Di berikan Tgl. ............ 20 ......
    </td>
  </tr>

  <tr>
    <td style="border:0px solid #000; padding:6px; text-align:left; vertical-align:middle; font-size:12px;">
      Yang memberi,
    </td>
    <td style="border:0px solid #000; padding:6px; text-align:center; vertical-align:middle; font-size:12px;">
      Yang menerima,
    </td>
    <td style="border:0px solid #000; padding:6px; text-align:left; vertical-align:middle; font-size:12px;">
      Yang Meminta,
    </td>
  </tr>

  <tr>
    <td style="border:0px solid #000; padding:10px; text-align:left; vertical-align:middle; font-size:12px;">
      Kep. Div/Bag/Sie ...................
    </td>
    <td style="border:0px solid #000; padding:10px;"></td>
    <td style="border:0px solid #000; padding:10px; text-align:left; vertical-align:middle; font-size:12px;">
      Kep. Div/Bag/Sie ...................
    </td>
  </tr>
  <br>
  <br>
  <tr>
    <td style="border:0px solid #000; padding:30px; text-align:left; vertical-align:bottom; font-size:14px;">
      (..........................................)
    </td>
    <td style="border:0px solid #000; padding:30px; text-align:center; vertical-align:bottom; font-size:14px;">
      (..........................................)
    </td>
    <td style="border:0px solid #000; padding:30px; text-align:left; vertical-align:bottom; font-size:14px;">
      (..........................................)
    </td>
  </tr>
</table>

        </div>
    </body>
    </html>
';

// Atur opsi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Muat HTML ke Dompdf dan render
$dompdf->loadHtml($html);
// Ukuran A5 (setengah A4) dalam milimeter
$dompdf->setPaper('A4', 'potrait');
$dompdf->render();

// Atur header HTTP untuk unduhan
$filename = 'resi_user' . $booking_data['no_karantina'] . '.pdf';
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($dompdf->output()));

// Kirimkan PDF ke browser
echo $dompdf->output();
exit;
?>