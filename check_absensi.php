<?php
require_once 'koneksi.php';

$today = date('Y-m-d');
$sql = "SELECT a.*, u.nama 
        FROM absensi_pegawai a 
        LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
        WHERE DATE(a.waktu_absen) = '$today' 
        ORDER BY a.waktu_absen ASC";

$res = $conn->query($sql);
$data = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
}
echo json_encode($data);
?>
