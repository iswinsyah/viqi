<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Ambil 6 foto galeri terbaru
$result = $conn->query("SELECT * FROM galeri ORDER BY id DESC LIMIT 6");
$data = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode($data);
?>