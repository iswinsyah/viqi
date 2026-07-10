<?php
// Connect to database and show records
require_once '../koneksi.php';
$res = $conn->query("SELECT * FROM kalender_akademik ORDER BY tanggal ASC");
if ($res) {
    if ($res->num_rows > 0) {
        echo "Found " . $res->num_rows . " records:\n";
        while($row = $res->fetch_assoc()) {
            echo "Date: " . $row['tanggal'] . " | Code: " . $row['status_hari'] . " | Desc: " . $row['keterangan'] . "\n";
        }
    } else {
        echo "Table is empty.\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
