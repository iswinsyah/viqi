<?php
require_once '../koneksi.php';
$res = $conn->query("SELECT * FROM master_kelas");
if ($res) {
    echo "Total classes: " . $res->num_rows . "\n";
    while ($row = $res->fetch_assoc()) {
        echo $row['id'] . " | " . $row['nama_kelas'] . " | " . $row['kategori_kelas'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
