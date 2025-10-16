<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'u909857501_php_biovet';
    private $username = 'u909857501_php_biovet';
    private $password = 'Lupa1234567+';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}

// Helper functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}



function redirect($location) {
    header("Location: " . $location);
    exit();
}

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function format_date($date) {
    return date('d-m-Y', strtotime($date));
}

function get_roman_month($month) {
    $romans = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV',
        5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII',
        9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    return $romans[$month];
}
function format_datetime($datetime) {
    return date('d-m-Y H:i:s', strtotime($datetime));
}
?>