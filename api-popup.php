<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Ambil data pengaturan pop-up baris pertama
$result = $conn->query("SELECT * FROM pengaturan_popup WHERE id = 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    // Default fallback jika tidak ada data di DB
    echo json_encode(['is_active' => 0]); 
}
?>