<?php
// includes/koneksi.php
// Konfigurasi Database Terpusat SADIGS 4.0 - Villa Quran Baron Malang

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kredensial Database Hostinger
$host     = "localhost";
$username = "u829486010_viqi";
$password = "Khilafet@1924";
$database = "u829486010_viqi";

// Matikan mode strict exception PHP 8.1+ agar tidak Error 500 jika query gagal
mysqli_report(MYSQLI_REPORT_OFF);

// Membuat koneksi ke database
$conn = @new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($conn->connect_error) {
    if ($host === 'localhost' || $host === '127.0.0.1') {
        // Fallback untuk local development di XAMPP
        $conn = @new mysqli($host, 'root', '', $database);
        if ($conn->connect_error) {
            die("Koneksi database gagal (termasuk fallback local): " . $conn->connect_error);
        }
    } else {
        die("Koneksi database gagal: " . $conn->connect_error);
    }
}

// Helper: Memastikan koneksi database tetap hidup sebelum query setelah proses panjang
if (!function_exists('pastikanKoneksiDb')) {
    function pastikanKoneksiDb(&$db_conn, $db_host, $db_user, $db_pass, $db_name) {
        if (!$db_conn || !@$db_conn->ping()) {
            @$db_conn->close();
            $db_conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
            if ($db_conn->connect_error && ($db_host === 'localhost' || $db_host === '127.0.0.1')) {
                $db_conn = @new mysqli($db_host, 'root', '', $db_name);
            }
        }
        return $db_conn;
    }
}
?>
