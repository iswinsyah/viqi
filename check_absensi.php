<?php
require_once 'koneksi.php';

$today = date('Y-m-d');
// Let's check Rakhman Wahyudi (ustadz_id = 3)
$res = $conn->query("SELECT status_kehadiran, waktu_absen, jenis_absen FROM absensi_pegawai WHERE ustadz_id = 3 AND DATE(waktu_absen) = '$today'");
echo "Rakhman Wahyudi: " . $res->num_rows . " rows today\n";
while ($row = $res->fetch_assoc()) {
    echo "- Time: " . $row['waktu_absen'] . " | Jenis: " . $row['jenis_absen'] . " | Status: " . $row['status_kehadiran'] . "\n";
}
?>
