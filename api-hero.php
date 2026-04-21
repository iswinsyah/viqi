<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Ambil data pengaturan hero (ID = 1)
$result = $conn->query("SELECT * FROM pengaturan_hero WHERE id = 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    // Fallback JSON jika tabel kosong
    echo json_encode(['error' => 'Data tidak ditemukan']); 
}
?>