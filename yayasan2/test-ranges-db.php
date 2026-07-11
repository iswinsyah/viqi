<?php
require_once '../koneksi.php';
$res = $conn->query("SELECT * FROM kalender_akademik ORDER BY tanggal ASC");
if ($res) {
    echo "Total records: " . $res->num_rows . "\n";
    while ($row = $res->fetch_assoc()) {
        echo $row['tanggal'] . " | " . $row['status_hari'] . " | " . $row['keterangan'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
