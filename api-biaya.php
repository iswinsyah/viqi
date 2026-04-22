<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Ambil seluruh data komponen biaya
$result = $conn->query("SELECT * FROM biaya ORDER BY id ASC");
$data = [
    'pendaftaran' => [],
    'pangkal' => [],
    'spp' => []
];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $data[$row['kategori']][] = $row;
    }
}
echo json_encode($data);
?>