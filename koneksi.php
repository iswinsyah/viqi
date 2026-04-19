<?php
// Kredensial Database Hostinger
$host     = "localhost";
$username = "u829486010_viqi";
$password = "Khilafet@1924";
$database = "u829486010_viqi";

// Matikan mode strict exception PHP 8.1+ agar tidak Error 500 jika query gagal
mysqli_report(MYSQLI_REPORT_OFF);

// Membuat koneksi ke database
$conn = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>