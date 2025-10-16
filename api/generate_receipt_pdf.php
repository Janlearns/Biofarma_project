<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../handlers/animal.php';


use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_GET['no_karantina']) || empty($_GET['no_karantina'])) {
    die("Nomor Karantina tidak ditemukan.");
}

$animal_handler = new AnimalHandler();
$no_karantina = $_GET['no_karantina'];

$logo_path = __DIR__ . '/../logo/logo.png';

$logo_base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logo_path));

// Ambil data pemesanan dari database
$booking_data = $animal_handler->search_booking($no_karantina, 'no_karantina');

if (!$booking_data) {
    die("Data pemesanan tidak ditemukan.");
}
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .receipt { width: 100%; max-width: 600px; padding: 25px; border-radius: 8px; line-height: 1.5; margin-top: -69px; }
        .receipt-header { text-align: center; margin-bottom: 10px; padding-bottom: 10px; }
        .receipt-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 5px; }
        .receipt-subtitle { color: #666; }
        .receipt-content  {
            font-family: Arial, sans-serif;
            font-size: 12px;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
        }
        .header-logo {
            text-align: left;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="receipt">
    <div class="header-logo">
                <img src="' . $logo_base64 . '" style="width: 150px; height: auto;">
            </div>
    <hr style="width: 100%; border: 0.5px solid #000000ff;">
        <div class="receipt-header">
            <div class="receipt-title">Surat Keterangan Karantina</div>
        </div>
        <div class="receipt-content">
            <table>
                <tr>
                    <td style="width: 40%;">1. Nomor Karantina</td>
                    <td>             : ' . htmlspecialchars($booking_data['no_karantina']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">2. Nomor Pemesanan</td>
                    <td>             : ' . htmlspecialchars($booking_data['no_pesanan']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">3. No pengiriman</td>
<td>             : ' . htmlspecialchars($booking_data['no_pengiriman'] ?? '') . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">4. Jenis Hewan</td>
                    <td>             : ' . htmlspecialchars($booking_data['nama_hewan']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">5. Jumlah Pesan Hewan</td>
                    <td>             : ' . htmlspecialchars($booking_data['jumlah_hewan']) . ' ekor</td>
                </tr>
                <tr>
                    <td style="width: 40%;">6. Jumlah Hewan Datang</td>
                    <td>             : ' . htmlspecialchars($booking_data['jumlah_hewan_datang']) . ' ekor </td>
                </tr>
                <tr>
                    <td style="width: 40%;">7. Jenis Uji/Eksperimen</td>
                    <td>             : ' . htmlspecialchars($booking_data['uji']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">8. Tanggal Datang</td>
                    <td>             : ' . format_date($booking_data['tanggal_datang']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">9. Tanggal/Masa Karantina</td>
                    <td>             : ' . format_date($booking_data['tanggal_datang']) . ' - ' . format_date($booking_data['tanggal_keluar']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">10. jumlah Lulus Karantina (ekor)</td>
                    <td>             : ' . htmlspecialchars($booking_data['lulus']) . '</td>
                </tr>
                <tr>
                    <td style="width: 40%;">11. jumlah Tidak Lulus Karantina (ekor)</td>
                    <td>             : ' . htmlspecialchars($booking_data['tidak_lulus']) . '</td>
                </tr>
            </table>
            <h4 style="margin-left: -15px; margin-top: -50px; margin-bottom: -50px;">
            12. Nomor Batch Sampel Uji/Eksperimen:
            </h4>

            </div>
            <table style="width: 100%; border: 1px solid #ccc; border-collapse: collapse;">
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; text-align: center; font-size:12px">Rencana</td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; text-align: center; font-size:12px">Realisasi*</td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                    <td style="width: 50%; border: 1px solid #ccc; padding: 8px; height: 15px;"></td>
                </tr>
            </table>
            
            <br>
            
            <table style="width: 100%; border: 0px solid #ccc; border-collapse: collapse; font-size: 12px; margin-top:-20px;">
                <tr>
                <td style="width: 50%; border: 0px solid #ccc; padding: 8px; text-align: left;">Bandung .......</td>
                </tr>
                <tr>
                    <td style="width: 30%; border: 0px solid #ccc; padding: 8px; text-align: left;">Kepala Bagian Uji Hewan</td>
                    <td style="width: 50%; border: 0px solid #ccc; padding: 8px; text-align: right;padding-right: 35px;">kepala Saksi*</td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 0px solid #ccc; padding: 8px; height: 50px;"></td>
                    <td style="width: 50%; border: 0px solid #ccc; padding: 8px; height: 50px;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; border: 0px solid #ccc; padding: 8px; text-align: left;">........................................</td>
                    <td style="width: 50%; border: 0px solid #ccc; padding: 8px; text-align: right;">.......................................</td>
                </tr>
                <hr style="width: 200%; border: 0px solid #000000ff; border-top: 0.5px solid #000000ff;">
                <p><i> Merujuk Dok.#:203K-ExpA-02 Rev.#:09 Lampiran 2</i></p>
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
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Atur header HTTP untuk unduhan
$filename = 'resi_admin' . $booking_data['no_order'] . '.pdf';
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
