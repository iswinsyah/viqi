<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Ambil seluruh data testimoni
$result = $conn->query("SELECT * FROM testimoni ORDER BY id DESC");
$data = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode($data);
?>