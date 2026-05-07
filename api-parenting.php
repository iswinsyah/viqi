<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Buat tabel jika belum ada secara otomatis
$conn->query("CREATE TABLE IF NOT EXISTS jadwal_parenting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATETIME NOT NULL,
    tema VARCHAR(255) NOT NULL,
    pemateri VARCHAR(150) NOT NULL,
    lokasi VARCHAR(100) DEFAULT 'Online (Zoom)',
    status ENUM('Selesai', 'Akan Datang') DEFAULT 'Akan Datang'
)");

// Ambil seluruh jadwal dengan urutan tanggal
$result = $conn->query("SELECT * FROM jadwal_parenting ORDER BY tanggal ASC");
$data = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode($data);
?>