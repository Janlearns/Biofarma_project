<?php
// auth/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class AuthHandler
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($username, $password, $user_type)
    {
        try {
            $table = ($user_type === 'admin') ? 'admin' : 'user';
            $query = "SELECT id, username, email, password FROM " . $table . " WHERE username = :username";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['logged_in'] = true;
                    return true;
                }
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }


    public function register($username, $email, $password)
    {
        try {
            // Check if username or email already exists
            $check_query = "SELECT id FROM user WHERE username = :username OR email = :email";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username atau email sudah digunakan'];
            }

            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO user (username, email, password) VALUES (:username, :email, :password)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Registrasi berhasil'];
            }
            return ['success' => false, 'message' => 'Gagal melakukan registrasi'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function create_admin($username, $email, $password)
    {
        try {
            // Cek apakah username atau email sudah ada di tabel admin
            $check_query = "SELECT id FROM admin WHERE username = :username OR email = :email";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':username', $username);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();

            if ($check_stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username atau email admin sudah digunakan'];
            }

            // Hash password dan masukkan data
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO admin (username, email, password) VALUES (:username, :email, :password)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Admin berhasil ditambahkan'];
            }

            return ['success' => false, 'message' => 'Gagal menambahkan admin: Query tidak berhasil dieksekusi.'];
        } catch (PDOException $e) {
            // PENTING: Tampilkan pesan error database untuk debugging
            error_log("Database Error in create_admin: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal menambahkan admin: ' . $e->getMessage()];
        }
    }

    // auth/auth.php
    // ...
    public function forgot_password($email, $user_type)
    {
        try {
            $table = ($user_type === 'admin') ? 'admin' : 'user';
            $check_query = "SELECT id FROM " . $table . " WHERE email = :email";
            $check_stmt = $this->conn->prepare($check_query);
            $check_stmt->bindParam(':email', $email);
            $check_stmt->execute();
            if ($check_stmt->rowCount() == 0) {
                return ['success' => false, 'message' => 'Email tidak ditemukan'];
            }

            $delete_old_codes = "DELETE FROM password_reset WHERE email = :email";
            $delete_stmt = $this->conn->prepare($delete_old_codes);
            $delete_stmt->bindParam(':email', $email);
            $delete_stmt->execute();

            $code = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Perbaikan: Hapus expires_at dari query
            $query = "INSERT INTO password_reset (email, code, user_type, used) VALUES (:email, :code, :user_type, 0)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':code', $code);
            $stmt->bindParam(':user_type', $user_type);

            if ($stmt->execute()) {
                // Kirim email ke pengguna
                $subject = "Kode Reset Password BioVet";
                $message_body = "Halo,\n\nKode verifikasi Anda untuk reset password adalah: " . $code . "";

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->SMTPDebug = 0; // Atur ke 0 saat sudah live
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'xwwx6468@gmail.com';
                $mail->Password = 'jiuhpsomkaqnfvon';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->setFrom('no-reply@biofarma.com', 'BioVet');
                $mail->addAddress($email);
                $mail->isHTML(false); // Mengirim email dalam format plain text
                $mail->Subject = $subject;
                $mail->Body = $message_body;
                $mail->send();

                return ['success' => true, 'message' => 'Kode verifikasi telah dikirim ke email Anda.'];
            }
            return ['success' => false, 'message' => 'Gagal memproses reset password'];
        } catch (Exception $e) {
            error_log("Gagal mengirim email: " . $e->getMessage());
            return ['success' => false, 'message' => 'Gagal mengirim email reset password. Silakan coba lagi.'];
        }
    }

    public function verify_reset_code($email, $code)
    {
        try {
            // Perbaikan: Hapus expires_at > NOW() dari query
            $query = "SELECT id, user_type FROM password_reset WHERE email = :email AND code = :code AND used = 0";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':code', $code);
            $stmt->execute();

            if ($stmt->rowCount() == 1) {
                $reset_data = $stmt->fetch(PDO::FETCH_ASSOC);

                // Tandai kode sebagai sudah digunakan
                $mark_used = "UPDATE password_reset SET used = 1 WHERE id = :id";
                $mark_stmt = $this->conn->prepare($mark_used);
                $mark_stmt->bindParam(':id', $reset_data['id']);
                $mark_stmt->execute();

                return ['success' => true, 'user_type' => $reset_data['user_type']];
            }
            return ['success' => false, 'message' => 'Kode tidak valid atau sudah kadaluarsa.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error'];
        }
    }

    public function reset_password($email, $new_password, $user_type)
    {
        try {
            $table = ($user_type === 'admin') ? 'admin' : 'user';
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update_query = "UPDATE " . $table . " SET password = :password WHERE email = :email";
            $update_stmt = $this->conn->prepare($update_query);
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':email', $email);

            if ($update_stmt->execute()) {
                return ['success' => true, 'message' => 'Password berhasil direset'];
            }
            return ['success' => false, 'message' => 'Gagal mereset password'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    public function logout()
    {
        session_destroy();
        return true;
    }

    public function is_logged_in()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function is_admin()
    {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }

    public function require_login()
    {
        if (!$this->is_logged_in()) {
            redirect('../login.php');
        }
    }

    public function require_admin()
    {
        $this->require_login();
        if (!$this->is_admin()) {
            redirect('../dashboard.php');
        }
    }
    public function get_admin_email()
    {
        try {
            $query = "SELECT email FROM admin LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error fetching admin email: " . $e->getMessage());
            return null;
        }
    }
}
