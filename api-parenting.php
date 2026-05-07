<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

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