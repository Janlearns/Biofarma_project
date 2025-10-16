<?php
// handlers/inventaris.php
require_once __DIR__ . '/../config/database.php';

class InventarisHandler
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function add_inventaris($tanggal_datang, $kode_barang, $jenis_barang, $no_batch, $total_barang, $satuan, $expired_date, $uji_file)
    {
        try {
            $file_name = null; // PERBAIKAN: Set nilai awal variabel
            if ($uji_file && $uji_file['error'] == UPLOAD_ERR_OK) {
                $target_dir = __DIR__ . "/../uploads/";
                $file_name = uniqid() . '_' . basename($uji_file["name"]);
                $file_path = $target_dir . $file_name;

                if (!move_uploaded_file($uji_file["tmp_name"], $file_path)) {
                    return ['success' => false, 'message' => 'Gagal mengupload file.'];
                }
            }

            $query = "INSERT INTO inventaris (tanggal_datang, kode_barang, jenis_barang, no_batch, expired_date, total_barang, satuan, uji_organoleptik_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$tanggal_datang, $kode_barang, $jenis_barang, $no_batch, $expired_date, $total_barang, $satuan, $file_name]);

            return ['success' => true, 'message' => 'Barang inventaris berhasil ditambahkan.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function get_all_inventaris()
    {
        try {
            $query = "SELECT * FROM inventaris ORDER BY tanggal_datang DESC, created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function update_inventaris_terpakai($id, $terpakai)
    {
        try {
            $query = "UPDATE inventaris SET terpakai = ? WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$terpakai, $id]);
            return ['success' => true, 'message' => 'Data terpakai berhasil diperbarui.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    public function delete_inventaris($id)
    {
        try {
            $query = "SELECT uji_organoleptik_file FROM inventaris WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            $file_to_delete = $stmt->fetchColumn();

            $query = "DELETE FROM inventaris WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);

            if ($file_to_delete) {
                $file_path = __DIR__ . "/../uploads/" . $file_to_delete;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            return ['success' => true, 'message' => 'Item berhasil dihapus.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    public function get_all_inventaris_for_report()
    {
        try {
            $query = "SELECT tanggal_datang, kode_barang, jenis_barang, no_batch, expired_date, total_barang, satuan, terpakai, saldo_pakai FROM inventaris ORDER BY tanggal_datang ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    public function update_inventaris_file($id, $file)
    {
        try {
            $target_dir = __DIR__ . "/../uploads/";
            $file_name = uniqid() . '_' . basename($file["name"]);
            $file_path = $target_dir . $file_name;

            // Cek apakah direktori uploads ada dan bisa ditulis
            if (!is_dir($target_dir) || !is_writable($target_dir)) {
                return ['success' => false, 'message' => 'Direktori unggahan tidak ditemukan atau tidak dapat ditulis.'];
            }

            if (!move_uploaded_file($file["tmp_name"], $file_path)) {
                return ['success' => false, 'message' => 'Gagal mengunggah file. Cek izin direktori.'];
            }

            // Simpan nama file ke database
            $query = "UPDATE inventaris SET uji_organoleptik_file = :file_name WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':file_name', $file_name);
            $stmt->bindParam(':id', $id);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'File berhasil diperbarui.'];
            }

            return ['success' => false, 'message' => 'Gagal menyimpan nama file ke database.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    public function delete_inventory_file($id)
    {
        try {
            // Ambil nama file dari database
            $query = "SELECT uji_organoleptik_file FROM inventaris WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $file_to_delete = $stmt->fetchColumn();

            // Hapus file fisik jika ada
            if (!empty($file_to_delete)) {
                $file_path = __DIR__ . "/../uploads/" . $file_to_delete;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            // Perbarui kolom di database menjadi NULL
            $update_query = "UPDATE inventaris SET uji_organoleptik_file = NULL WHERE id = :id";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':id', $id);
            $update_stmt->execute();

            return ['success' => true, 'message' => 'File berhasil dihapus.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
