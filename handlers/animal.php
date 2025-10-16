<?php
// handlers/animal.php
require_once __DIR__ . '/../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class AnimalHandler
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function add_animal($nama_hewan, $total_kandang, $kapasitas_per_kandang, $deskripsi, $foto_hewan)
    {
        try {
            // Generate kode awal dari nama hewan
            $kode_awal = strtoupper(substr($nama_hewan, 0, 1));

            // Check if animal with same name exists
            $check_query = "SELECT id FROM hewan WHERE nama_hewan = :nama_hewan";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':nama_hewan', $nama_hewan);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Hewan dengan nama tersebut sudah ada'];
            }

            $this->conn->beginTransaction();

            // Logika Upload File
            $target_dir = __DIR__ . "/../uploads/";
            $foto_filename = basename($foto_hewan["name"]);
            $target_file = $target_dir . $foto_filename;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Periksa apakah file adalah gambar
            $check = getimagesize($foto_hewan["tmp_name"]);
            if ($check === false) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'File bukan gambar.'];
            }

            // Periksa ukuran file (misal: maks 5MB)
            if ($foto_hewan["size"] > 5000000) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Ukuran file terlalu besar.'];
            }

            // Pindahkan file yang diunggah
            if (!move_uploaded_file($foto_hewan["tmp_name"], $target_file)) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Gagal mengunggah file foto.'];
            }

            // Insert animal
            $query = "INSERT INTO hewan (nama_hewan, kode_awal, total_kandang, kapasitas_per_kandang, deskripsi, foto_hewan) 
            VALUES (:nama_hewan, :kode_awal, :total_kandang, :kapasitas_per_kandang, :deskripsi, :foto_hewan)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_hewan', $nama_hewan);
            $stmt->bindParam(':kode_awal', $kode_awal);
            $stmt->bindParam(':total_kandang', $total_kandang);
            $stmt->bindParam(':kapasitas_per_kandang', $kapasitas_per_kandang);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':foto_hewan', $foto_filename);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Gagal menambahkan hewan'];
            }

            $hewan_id = $this->conn->lastInsertId();

            // Create kandang for this animal
            $kandang_query = "INSERT INTO kandang (hewan_id, nomor_kandang, kapasitas) VALUES (?, ?, ?)";
            $kandang_stmt = $this->conn->prepare($kandang_query);

            for ($i = 1; $i <= $total_kandang; $i++) {
                $kandang_stmt->execute([$hewan_id, $i, $kapasitas_per_kandang]);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Hewan berhasil ditambahkan'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function get_all_animals()
    {
        try {
            $query = "SELECT h.*, 
                    COUNT(k.id) as jumlah_kandang,
                    SUM(k.terisi) as total_terisi,
                    SUM(k.sisa_slot) as total_sisa_slot
                    FROM hewan h 
                    LEFT JOIN kandang k ON h.id = k.hewan_id 
                    GROUP BY h.id 
                    ORDER BY h.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_animal_by_id($id)
    {
        try {
            $query = "SELECT h.*, 
                     COUNT(k.id) as jumlah_kandang,
                     SUM(k.terisi) as total_terisi,
                     SUM(k.sisa_slot) as total_sisa_slot
                     FROM hewan h 
                     LEFT JOIN kandang k ON h.id = k.hewan_id 
                     WHERE h.id = :id
                     GROUP BY h.id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function get_kandang_by_animal_id($hewan_id)
    {
        try {
            $query = "SELECT * FROM kandang WHERE hewan_id = :hewan_id ORDER BY nomor_kandang";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':hewan_id', $hewan_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }


    public function book_animal($user_id, $hewan_id, $tanggal_datang, $tanggal_keluar, $uji, $jumlah_hewan, $berat, $satuan, $no_pesanan, $jenis_kelamin, $nama_user, $bagian, $unit)
    {
        try {
            $this->conn->beginTransaction();

            // Dapatkan info hewan
            $animal = $this->get_animal_by_id($hewan_id);
            if (!$animal) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Hewan tidak ditemukan'];
            }

            // Cek ketersediaan slot total sebelum memesan
            if (($animal['total_sisa_slot'] ?? $animal['total_slot']) < $jumlah_hewan) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Jumlah slot yang diminta melebihi kapasitas yang tersedia'];
            }

            // Find available kandang
            $kandang_query = "SELECT * FROM kandang WHERE hewan_id = :hewan_id AND sisa_slot >= :jumlah_hewan LIMIT 1";
            $kandang_stmt = $this->conn->prepare($kandang_query);
            $kandang_stmt->bindParam(':hewan_id', $hewan_id);
            $kandang_stmt->bindParam(':jumlah_hewan', $jumlah_hewan);
            $kandang_stmt->execute();

            $kandang = $kandang_stmt->fetch(PDO::FETCH_ASSOC);

            // Jika tidak ada satu kandang pun yang cukup, ambil kandang mana saja untuk booking
            if (!$kandang) {
                $kandang_query = "SELECT * FROM kandang WHERE hewan_id = :hewan_id AND sisa_slot > 0 ORDER BY sisa_slot DESC";
                $kandang_stmt = $this->conn->prepare($kandang_query);
                $kandang_stmt->bindParam(':hewan_id', $hewan_id);
                $kandang_stmt->execute();
                $kandang_list = $kandang_stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($kandang_list)) {
                    $this->conn->rollBack();
                    return ['success' => false, 'message' => 'Tidak ada kandang yang tersedia'];
                }

                $kandang_id = $kandang_list[0]['id'];

                // Simpan alokasi hewan secara logis
                $hewan_sisa = $jumlah_hewan;
                foreach ($kandang_list as $k) {
                    if ($hewan_sisa <= 0) break;

                    $hewan_masuk = min($hewan_sisa, $k['sisa_slot']);
                    $update_kandang = "UPDATE kandang SET terisi = terisi + ?, sisa_slot = sisa_slot - ? WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_kandang);
                    $update_stmt->execute([$hewan_masuk, $hewan_masuk, $k['id']]);

                    $hewan_sisa -= $hewan_masuk;
                }
            } else {
                $kandang_id = $kandang['id'];
                // Update kandang yang terpilih
                $update_kandang = "UPDATE kandang SET terisi = terisi + :jumlah_hewan, sisa_slot = sisa_slot - :jumlah_hewan WHERE id = :kandang_id";
                $update_stmt = $this->conn->prepare($update_kandang);
                $update_stmt->bindParam(':jumlah_hewan', $jumlah_hewan);
                $update_stmt->bindParam(':kandang_id', $kandang_id);
                $update_stmt->execute();
            }

            // Generate no_pemesanan
            $count_query = "SELECT COUNT(*) as total FROM pemesanan_kandang";
            $count_stmt = $this->conn->prepare($count_query);
            $count_stmt->execute();
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC);
            $no_order = 'ORD' . str_pad($count['total'] + 1, 6, '0', STR_PAD_LEFT);

            // PERBAIKAN: Generate no_karantina
            $date = new DateTime($tanggal_datang);
            $day = $date->format('j');
            $month = get_roman_month($date->format('n'));
            $year = $date->format('Y');

            // Kueri baru: Hitung jumlah pemesanan di tahun ini
            $seq_query = "SELECT COUNT(*) as total FROM pemesanan_kandang WHERE YEAR(tanggal_datang) = :year";
            $seq_stmt = $this->conn->prepare($seq_query);
            $seq_stmt->bindParam(':year', $year);
            $seq_stmt->execute();
            $seq = $seq_stmt->fetch(PDO::FETCH_ASSOC);
            $sequence = $seq['total'] + 1;

            $no_karantina = $sequence . '/Q/' . $animal['kode_awal'] . '/DUH/' . $day . '/' . $month . '/' . $year;

            $check_query = "SELECT id FROM pemesanan_kandang WHERE no_pesanan = :no_pesanan";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':no_pesanan', $no_pesanan);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Nomor pesanan sudah digunakan'];
            }

            // Insert booking, termasuk tanggal_keluar
            $insert_query = "INSERT INTO pemesanan_kandang (user_id, hewan_id, kandang_id, no_order, no_pesanan, no_karantina, tanggal_datang, tanggal_keluar, uji, jumlah_hewan, berat, satuan, jenis_kelamin, created_at, nama_user, bagian, unit) 
            VALUES (:user_id, :hewan_id, :kandang_id, :no_order,:no_pesanan, :no_karantina, :tanggal_datang, :tanggal_keluar, :uji, :jumlah_hewan, :berat, :satuan, :jenis_kelamin, NOW(), :nama_user, :bagian, :unit)";
            $insert_stmt = $this->conn->prepare($insert_query);
            $insert_stmt->bindParam(':user_id', $user_id);
            $insert_stmt->bindParam(':hewan_id', $hewan_id);
            $insert_stmt->bindParam(':kandang_id', $kandang_id);
            $insert_stmt->bindParam(':no_order', $no_order);
            $insert_stmt->bindParam(':no_pesanan', $no_pesanan);
            $insert_stmt->bindParam(':no_karantina', $no_karantina);
            $insert_stmt->bindParam(':tanggal_datang', $tanggal_datang);
            $insert_stmt->bindParam(':tanggal_keluar', $tanggal_keluar);
            $insert_stmt->bindParam(':uji', $uji);
            $insert_stmt->bindParam(':jumlah_hewan', $jumlah_hewan);
            $insert_stmt->bindParam(':berat', $berat);
            $insert_stmt->bindParam(':satuan', $satuan);
            $insert_stmt->bindParam(':jenis_kelamin', $jenis_kelamin);
            $insert_stmt->bindParam(':nama_user', $nama_user);
            $insert_stmt->bindParam(':bagian', $bagian);
            $insert_stmt->bindParam(':unit', $unit);
            if (!$insert_stmt->execute()) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Gagal melakukan pemesanan'];
            }

            $this->conn->commit();

            return [
                'success' => true,
                'message' => 'Pemesanan berhasil',
                'data' => [
                    'no_order' => $no_order,
                    'no_pesanan' => $no_pesanan,
                    'no_karantina' => $no_karantina,
                    'tanggal_datang' => $tanggal_datang,
                    'tanggal_keluar' => $tanggal_keluar,
                    'uji' => $uji,
                    'nama_hewan' => $animal['nama_hewan'],
                    'jumlah_hewan' => $jumlah_hewan,
                    'berat' => $berat,
                    'satuan' => $satuan,
                    'jenis_kelamin' => $jenis_kelamin,
                ]
            ];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    // handlers/animal.php
    // ...
    // handlers/animal.php
    // ...

    public function search_booking($term, $type = 'no_pesanan')
    {
        try {
            $query = "SELECT pk.*, h.nama_hewan, k.nomor_kandang
                  FROM pemesanan_kandang pk
                  JOIN hewan h ON pk.hewan_id = h.id
                  LEFT JOIN kandang k ON pk.kandang_id = k.id
                  WHERE ";

            if ($type === 'no_karantina') {
                $query .= "pk.no_karantina = ?";
            } else {
                $query .= "pk.no_pesanan = ?";
            }

            // Hanya tampilkan jika statusnya 'approved'
            $query .= " AND pk.status = 'approved'";

            $stmt = $this->conn->prepare($query);
            $stmt->execute([$term]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Perbaikan: tambahkan kolom nama_user, bagian, dan unit jika belum ada di result
            if ($result) {
                $result['nama_user'] = $result['nama_user'] ?? '';
                $result['bagian'] = $result['bagian'] ?? '';
                $result['unit'] = $result['unit'] ?? '';
            }

            // Tambahkan field status default jika tidak ada
            if ($result && !isset($result['status'])) {
                $result['status'] = 'aktif';
            }

            return $result ? $result : false;
        } catch (PDOException $e) {
            return false;
        }
    }
    // ...
    // ...

    public function get_user_bookings($user_id)
    {
        try {
            $query = "SELECT pk.*, h.nama_hewan, k.nomor_kandang 
                     FROM pemesanan_kandang pk 
                     JOIN hewan h ON pk.hewan_id = h.id 
                     JOIN kandang k ON pk.kandang_id = k.id 
                     WHERE pk.user_id = :user_id 
                     ORDER BY pk.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    // handlers/animal.php
    // ...
    public function delete_animal($hewan_id)
    {
        try {
            $this->conn->beginTransaction();

            // Hapus pemesanan terkait
            $delete_bookings_query = "DELETE FROM pemesanan_kandang WHERE hewan_id = :hewan_id";
            $delete_bookings_stmt = $this->conn->prepare($delete_bookings_query);
            $delete_bookings_stmt->bindParam(':hewan_id', $hewan_id);
            $delete_bookings_stmt->execute();

            // Hapus kandang terkait
            $delete_kandang_query = "DELETE FROM kandang WHERE hewan_id = :hewan_id";
            $delete_kandang_stmt = $this->conn->prepare($delete_kandang_query);
            $delete_kandang_stmt->bindParam(':hewan_id', $hewan_id);
            $delete_kandang_stmt->execute();

            // Hapus hewan itu sendiri
            $delete_hewan_query = "DELETE FROM hewan WHERE id = :hewan_id";
            $delete_hewan_stmt = $this->conn->prepare($delete_hewan_query);
            $delete_hewan_stmt->bindParam(':hewan_id', $hewan_id);
            $delete_hewan_stmt->execute();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Hewan dan data terkait berhasil dihapus.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Gagal menghapus hewan: ' . $e->getMessage()];
        }
    }
    // handlers/animal.php
    // ...
    // Tambahkan metode ini di dalam class AnimalHandler
    public function update_animal($id, $nama_hewan, $total_kandang, $kapasitas_per_kandang, $deskripsi)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Perbarui data hewan
            $query = "UPDATE hewan SET nama_hewan = :nama_hewan, total_kandang = :total_kandang, 
                  kapasitas_per_kandang = :kapasitas_per_kandang, deskripsi = :deskripsi
                  WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nama_hewan', $nama_hewan);
            $stmt->bindParam(':total_kandang', $total_kandang);
            $stmt->bindParam(':kapasitas_per_kandang', $kapasitas_per_kandang);
            $stmt->bindParam(':deskripsi', $deskripsi);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // 2. Sesuaikan jumlah kandang jika perlu
            $current_kandang_count_query = "SELECT COUNT(*) as current_count FROM kandang WHERE hewan_id = :id";
            $current_kandang_count_stmt = $this->conn->prepare($current_kandang_count_query);
            $current_kandang_count_stmt->bindParam(':id', $id);
            $current_kandang_count_stmt->execute();
            $current_count = $current_kandang_count_stmt->fetch(PDO::FETCH_ASSOC)['current_count'];

            if ($total_kandang > $current_count) {
                // Tambah kandang baru
                $kandang_query = "INSERT INTO kandang (hewan_id, nomor_kandang, kapasitas) VALUES (?, ?, ?)";
                $kandang_stmt = $this->conn->prepare($kandang_query);
                for ($i = $current_count + 1; $i <= $total_kandang; $i++) {
                    $kandang_stmt->execute([$id, $i, $kapasitas_per_kandang]);
                }
            } elseif ($total_kandang < $current_count) {
                // Hapus kandang yang berlebih
                $delete_query = "DELETE FROM kandang WHERE hewan_id = ? AND nomor_kandang > ?";
                $delete_stmt = $this->conn->prepare($delete_query);
                $delete_stmt->execute([$id, $total_kandang]);
            }

            // 3. Perbarui kapasitas semua kandang untuk hewan ini
            $update_kapasitas_query = "UPDATE kandang SET kapasitas = :kapasitas WHERE hewan_id = :id";
            $update_kapasitas_stmt = $this->conn->prepare($update_kapasitas_query);
            $update_kapasitas_stmt->bindParam(':kapasitas', $kapasitas_per_kandang);
            $update_kapasitas_stmt->bindParam(':id', $id);
            $update_kapasitas_stmt->execute();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Data hewan berhasil diperbarui.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    public function get_filled_cages_by_animal_id($hewan_id)
    {
        try {
            $query = "SELECT k.nomor_kandang, SUM(pk.jumlah_hewan) as jumlah_hewan_terisi
                 FROM pemesanan_kandang pk
                 JOIN kandang k ON pk.kandang_id = k.id
                 WHERE pk.hewan_id = :hewan_id
                 GROUP BY k.nomor_kandang
                 ORDER BY k.nomor_kandang";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':hewan_id', $hewan_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_bookings_by_animal($hewan_id = null, $tanggal = null, $unit = null, $tahun = null)
    {
        try {
            $query = "SELECT pk.*, h.nama_hewan, k.nomor_kandang, pk.status_update
                 FROM pemesanan_kandang pk 
                 JOIN hewan h ON pk.hewan_id = h.id 
                 JOIN kandang k ON pk.kandang_id = k.id 
                 WHERE 1=1";
            $params = [];

            if ($hewan_id) {
                $query .= " AND pk.hewan_id = :hewan_id";
                $params[':hewan_id'] = $hewan_id;
            }

            if ($tanggal) {
                // Mengubah filter tanggal menjadi berdasarkan tanggal datang
                $query .= " AND pk.tanggal_datang = :tanggal";
                $params[':tanggal'] = $tanggal;
            }

            if ($unit) {
                $query .= " AND pk.unit = :unit";
                $params[':unit'] = $unit;
            }

            // Tambahkan kondisi filter tahun
            if ($tahun) {
                $query .= " AND YEAR(pk.tanggal_datang) = :tahun";
                $params[':tahun'] = $tahun;
            }

            $query .= " GROUP BY pk.id ORDER BY pk.created_at DESC";

            $stmt = $this->conn->prepare($query);

            // Bind semua parameter secara dinamis
            foreach ($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }

            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function update_karantina_data($no_karantina, $no_pengiriman, $jumlah_hewan_datang, $lulus, $tidak_lulus)
    {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE pemesanan_kandang SET no_pengiriman = :no_pengiriman, 
                  jumlah_hewan_datang = :jumlah_hewan_datang, lulus = :lulus, tidak_lulus = :tidak_lulus, status_update = 1
                  WHERE no_karantina = :no_karantina";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':no_pengiriman', $no_pengiriman);
            $stmt->bindParam(':jumlah_hewan_datang', $jumlah_hewan_datang);
            $stmt->bindParam(':lulus', $lulus);
            $stmt->bindParam(':tidak_lulus', $tidak_lulus);
            $stmt->bindParam(':no_karantina', $no_karantina);
            $stmt->execute();

            $this->conn->commit();
            return ['success' => true, 'message' => 'Data karantina berhasil diperbarui.'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    public function update_bpab_data($no_karantina, $divisi, $kepala, $no_permintaan, $keterangan)
    {
        try {
            $query = "UPDATE pemesanan_kandang SET divisi = :divisi, kepala = :kepala, no_permintaan = :no_permintaan, keterangan = :keterangan WHERE no_karantina = :no_karantina";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':divisi', $divisi);
            $stmt->bindParam(':kepala', $kepala);
            $stmt->bindParam(':no_permintaan', $no_permintaan);
            $stmt->bindParam(':keterangan', $keterangan);
            $stmt->bindParam(':no_karantina', $no_karantina);
            $stmt->execute();

            return ['success' => true, 'message' => 'Data BPAB berhasil diperbarui.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    public function get_all_units()
    {
        try {
            $query = "SELECT DISTINCT unit FROM pemesanan_kandang WHERE unit IS NOT NULL AND unit != '' ORDER BY unit ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }
    // handlers/animal.php
    // ... (di dalam class AnimalHandler) ...

    public function get_report_data($tahun = null, $bulan = null, $unit = null)
    {
        try {
            $query = "SELECT pk.no_karantina, pk.jumlah_hewan
                  FROM pemesanan_kandang pk
                  WHERE 1=1";
            $params = [];

            if ($tahun) {
                $query .= " AND YEAR(pk.created_at) = ?";
                $params[] = $tahun;
            }

            if ($bulan) {
                $query .= " AND MONTH(pk.created_at) = ?";
                $params[] = $bulan;
            }

            if ($unit) {
                $query .= " AND pk.unit = ?";
                $params[] = $unit;
            }

            $query .= " ORDER BY pk.created_at DESC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_all_years()
    {
        try {
            // PERBAIKAN: Mengambil tahun dari kolom tanggal_datang
            $query = "SELECT DISTINCT YEAR(tanggal_datang) AS year FROM pemesanan_kandang ORDER BY year DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error fetching all years: " . $e->getMessage());
            return [];
        }
    }
    public function get_aggregated_report_data($tahun = null, $bulan = null, $unit = null)
    {
        try {
            $query = "SELECT pk.hewan_id, h.nama_hewan, pk.jumlah_hewan, pk.bagian
                  FROM pemesanan_kandang pk
                  LEFT JOIN hewan h ON pk.hewan_id = h.id
                  WHERE 1=1";
            $params = [];

            if ($tahun) {
                // Perbaikan: Filter berdasarkan TAHUN dari tanggal_datang
                $query .= " AND YEAR(pk.tanggal_datang) = ?";
                $params[] = $tahun;
            }

            if ($bulan) {
                // Perbaikan: Filter berdasarkan BULAN dari tanggal_datang
                $query .= " AND MONTH(pk.tanggal_datang) = ?";
                $params[] = $bulan;
            }

            if ($unit) {
                $query .= " AND pk.unit = ?";
                $params[] = $unit;
            }

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $divisi_unik = [];
            $animals_summary = [];

            foreach ($raw_data as $row) {
                $bagian = $row['bagian'];
                if (!empty($bagian) && !in_array($bagian, $divisi_unik)) {
                    $divisi_unik[] = $bagian;
                }

                if (!isset($animals_summary[$row['hewan_id']])) {
                    $animals_summary[$row['hewan_id']] = [
                        'nama_hewan' => $row['nama_hewan'],
                        'total_per_animal' => 0,
                        'divisi_counts' => []
                    ];
                }
                $animals_summary[$row['hewan_id']]['total_per_animal'] += $row['jumlah_hewan'];

                if (!empty($bagian)) {
                    if (!isset($animals_summary[$row['hewan_id']]['divisi_counts'][$bagian])) {
                        $animals_summary[$row['hewan_id']]['divisi_counts'][$bagian] = 0;
                    }
                    $animals_summary[$row['hewan_id']]['divisi_counts'][$bagian] += $row['jumlah_hewan'];
                }
            }

            sort($divisi_unik);

            return [
                'divisi_columns' => $divisi_unik,
                'animals' => $animals_summary
            ];
        } catch (PDOException $e) {
            return [
                'divisi_columns' => [],
                'animals' => []
            ];
        }
    }
    public function get_all_uji()
    {
        try {
            $query = "SELECT DISTINCT uji FROM pemesanan_kandang WHERE uji IS NOT NULL AND uji != '' ORDER BY uji ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_uji_hewan_summary($tahun = null, $bulan = null, $unit = null)
    {
        try {
            $query = "SELECT pk.hewan_id, h.nama_hewan, pk.uji, SUM(pk.jumlah_hewan) as total_hewan
                  FROM pemesanan_kandang pk
                  LEFT JOIN hewan h ON pk.hewan_id = h.id
                  WHERE 1=1";
            $params = [];

            if ($tahun) {
                $query .= " AND YEAR(pk.tanggal_datang) = ?";
                $params[] = $tahun;
            }

            if ($bulan) {
                $query .= " AND MONTH(pk.tanggal_datang) = ?";
                $params[] = $bulan;
            }

            if ($unit) {
                $query .= " AND pk.unit = ?";
                $params[] = $unit;
            }

            $query .= " GROUP BY pk.hewan_id, pk.uji ORDER BY h.nama_hewan ASC, pk.uji ASC";

            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $uji_unik = $this->get_all_uji();
            $hewan_unik = $this->get_all_animals();

            $summary = [];
            foreach ($hewan_unik as $hewan) {
                $summary[$hewan['id']] = [
                    'nama_hewan' => $hewan['nama_hewan'],
                ];
            }

            $result = [];
            foreach ($uji_unik as $uji_name) {
                $result[$uji_name] = [
                    'hewan_counts' => $summary,
                ];
            }

            foreach ($raw_data as $row) {
                if (isset($result[$row['uji']]['hewan_counts'][$row['hewan_id']])) {
                    $result[$row['uji']]['hewan_counts'][$row['hewan_id']]['total_hewan'] = (int)$row['total_hewan'];
                }
            }

            return $result;
        } catch (PDOException $e) {
            return [];
        }
    }

    // ... (Jangan lupa tambahin method get_all_animals() yang baru di sini juga,
    //      atau pastikan method get_all_animals() yang sudah ada tidak memfilter apapun)
    public function get_all_animals_unfiltered()
    {
        try {
            $query = "SELECT id, nama_hewan FROM hewan ORDER BY nama_hewan ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    public function update_booking_status($no_karantina, $status)
    {
        try {
            // Ambil data pemesanan sebelum diupdate
            $booking_data = $this->get_booking_by_no_karantina($no_karantina);

            if (!$booking_data) {
                return ['success' => false, 'message' => 'Pemesanan tidak ditemukan.'];
            }

            // Mulai transaksi database
            $this->conn->beginTransaction();

            $query = "UPDATE pemesanan_kandang SET status = :status WHERE no_karantina = :no_karantina";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':no_karantina', $no_karantina);

            if (!$stmt->execute()) {
                $this->conn->rollBack();
                return ['success' => false, 'message' => 'Gagal memperbarui status.'];
            }

            if ($status === 'approved' && $booking_data['status'] !== 'approved') {
                // Kirim email persetujuan ke pengguna
                $this->send_approval_email($booking_data);

                // Panggil fungsi baru untuk kirim notifikasi ke admin
                $this->send_admin_notification($booking_data);
            }

            $this->conn->commit();
            return ['success' => true, 'message' => 'Status berhasil diperbarui.'];

        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    private function get_booking_by_no_karantina($no_karantina)
    {
        try {
            $query = "SELECT pk.*, h.nama_hewan, k.nomor_kandang, u.email as user_email, u.username as nama_pemesan
                  FROM pemesanan_kandang pk
                  LEFT JOIN hewan h ON pk.hewan_id = h.id
                  LEFT JOIN kandang k ON pk.kandang_id = k.id
                  LEFT JOIN user u ON pk.user_id = u.id
                  WHERE pk.no_karantina = :no_karantina";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':no_karantina', $no_karantina);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching booking data: " . $e->getMessage());
            return null;
        }
    }

    // Fungsi ini akan mengambil email pengguna berdasarkan ID-nya
    private function get_user_email_by_id($user_id)
    {
        try {
            $query = "SELECT email FROM user WHERE id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching user email: " . $e->getMessage());
            return null;
        }
    }
    private function send_approval_email($booking_data)
    {
        $mail = new PHPMailer(true);
        $recipient_email = $booking_data['user_email'];

        if (empty($recipient_email)) {
            error_log("Gagal mengirim email: Alamat email pengguna tidak ditemukan.");
            return false;
        }


        try {
            // Konfigurasi Server SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username = 'xwwx6468@gmail.com';
            $mail->Password = 'jiuhpsomkaqnfvon'; // Ganti dengan kata sandi aplikasi Anda
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Penerima
            $mail->setFrom('no-reply@biofarma.com', 'BioVet');
            $mail->addAddress($recipient_email);

            // Konten Email
            $mail->isHTML(false); // Atur ke false untuk email teks biasa
            $mail->Subject = 'Pemesanan Anda Telah Disetujui';

            $email_body = "";
            $email_body .= "Send By System NO REPLY REQUIRED\n\n";
            $email_body .= "Pemesanan Hewan Laboratorium Anda telah disetujui oleh admin. Berikut adalah detailnya:\n\n";
            $email_body .= "No Order: " . htmlspecialchars($booking_data['no_order']) . "\n";
            $email_body .= "No Pesanan: " . htmlspecialchars($booking_data['no_pesanan']) . "\n";
            $email_body .= "Nama User: " . htmlspecialchars($booking_data['nama_user']) . "\n";
            $email_body .= "Bagian: " . htmlspecialchars($booking_data['bagian']) . "\n";
            $email_body .= "No Karantina: " . htmlspecialchars($booking_data['no_karantina']) . "\n";
            $email_body .= "Hewan: " . htmlspecialchars($booking_data['nama_hewan']) . "\n";
            $email_body .= "Jumlah Hewan: " . htmlspecialchars($booking_data['jumlah_hewan']) . " ekor\n";
            $email_body .= "Kandang: " . htmlspecialchars($booking_data['nomor_kandang']) . "\n";
            $email_body .= "Tanggal Datang: " . format_date($booking_data['tanggal_datang']) . "\n";
            $email_body .= "Masa karantina: " . format_date($booking_data['tanggal_datang']) . " - " . format_date($booking_data['tanggal_keluar']) . "\n";
            $email_body .= "Jenis Uji/Experimen: " . htmlspecialchars($booking_data['uji']) . "\n";
            $email_body .= "Status: Approved\n";
            $email_body .= "Unit Uji: " . htmlspecialchars($booking_data['unit']) . "\n";
            $email_body .= "Sex: " . htmlspecialchars($booking_data['jenis_kelamin']) . "\n";
            $email_body .= "Berat Hewan: " . htmlspecialchars($booking_data['berat']) . "\n";
            $email_body .= "Satuan: " . htmlspecialchars($booking_data['satuan']) . "\n\n";
            $email_body .= "Terima kasih.\nTim BioVet";

            $mail->Body = $email_body;
            $mail->send();
        } catch (Exception $e) {
            error_log("Gagal mengirim email persetujuan: {$mail->ErrorInfo}");
        }
    }
    private function send_admin_notification($booking_data)
    {
        $auth = new AuthHandler();
        $admin_email = $auth->get_admin_email(); // Ambil email admin

        if (!$admin_email) {
            error_log("Gagal mengirim email notifikasi ke admin: Alamat email admin tidak ditemukan.");
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // Konfigurasi Server SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username = 'xwwx6468@gmail.com';
            $mail->Password = 'jiuhpsomkaqnfvon'; // Ganti dengan kata sandi aplikasi Anda
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            // Penerima
            $mail->setFrom('no-reply@biovet.com', 'BioVet Notification');
            $mail->addAddress($admin_email); // Kirim ke email admin

            // Konten Email
            $mail->isHTML(false);
            $mail->Subject = 'Pemesanan Hewan Baru Telah Disetujui';

            $email_body = "";
            $email_body .= "Send By System NO REPLY REQUIRED\n\n";
            $email_body .= "Pemesanan Hewan Laboratorium Anda telah disetujui oleh admin. Berikut adalah detailnya:\n\n";
            $email_body .= "No Order: " . htmlspecialchars($booking_data['no_order']) . "\n";
            $email_body .= "No Pesanan: " . htmlspecialchars($booking_data['no_pesanan']) . "\n";
            $email_body .= "Nama User: " . htmlspecialchars($booking_data['nama_user']) . "\n";
            $email_body .= "Bagian: " . htmlspecialchars($booking_data['bagian']) . "\n";
            $email_body .= "No Karantina: " . htmlspecialchars($booking_data['no_karantina']) . "\n";
            $email_body .= "Hewan: " . htmlspecialchars($booking_data['nama_hewan']) . "\n";
            $email_body .= "Jumlah Hewan: " . htmlspecialchars($booking_data['jumlah_hewan']) . " ekor\n";
            $email_body .= "Kandang: " . htmlspecialchars($booking_data['nomor_kandang']) . "\n";
            $email_body .= "Tanggal Datang: " . format_date($booking_data['tanggal_datang']) . "\n";
            $email_body .= "Masa karantina: " . format_date($booking_data['tanggal_datang']) . " - " . format_date($booking_data['tanggal_keluar']) . "\n";
            $email_body .= "Jenis Uji/Experimen: " . htmlspecialchars($booking_data['uji']) . "\n";
            $email_body .= "Status: Approved\n";
            $email_body .= "Unit Uji: " . htmlspecialchars($booking_data['unit']) . "\n";
            $email_body .= "Sex: " . htmlspecialchars($booking_data['jenis_kelamin']) . "\n";
            $email_body .= "Berat Hewan: " . htmlspecialchars($booking_data['berat']) . "\n";
            $email_body .= "Satuan: " . htmlspecialchars($booking_data['satuan']) . "\n\n";
            $email_body .= "Terima kasih.\nTim BioVet";

            $mail->Body = $email_body;
            $mail->send();

            return true;
        } catch (Exception $e) {
            error_log("Gagal mengirim notifikasi ke admin: {$mail->ErrorInfo}");
            return false;
        }
    }
    public function delete_karantina($no_karantina)
    {
        try {
            $query = "DELETE FROM pemesanan_kandang WHERE no_karantina = :no_karantina";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':no_karantina', $no_karantina);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Pemesanan karantina berhasil dihapus.'];
            }

            return ['success' => false, 'message' => 'Gagal menghapus pemesanan.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
