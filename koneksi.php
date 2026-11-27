<?php
// Atur zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// Sembunyikan pesan warning mentah di live production agar respon AJAX/JSON tidak terdistorsi
$is_local_dev = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost']);
if ($is_local_dev) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
    ini_set('display_errors', 0);
}

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

// Pastikan koneksi menggunakan utf8mb4 agar teks Al-Qur'an dan emoji tersimpan sempurna
if ($conn) {
    $conn->set_charset("utf8mb4");
    @$conn->query("SET time_zone = '+07:00'");
}
?>