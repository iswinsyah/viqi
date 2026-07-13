<?php
require_once 'koneksi.php';

$today = date('Y-m-d');
$sql = "SELECT count(*) as total FROM absensi_pegawai WHERE DATE(waktu_absen) = '$today'";
$res = $conn->query($sql);
$row = $res->fetch_assoc();
echo "Total Rows Today: " . $row['total'] . "\n";

$sql_all = "SELECT a.*, u.nama 
            FROM absensi_pegawai a 
            LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
            WHERE DATE(a.waktu_absen) = '$today' 
            ORDER BY a.waktu_absen ASC";
$res_all = $conn->query($sql_all);
if ($res_all) {
    while ($r = $res_all->fetch_assoc()) {
        echo "ID: " . $r['id'] . "\n";
        echo "Ustadz ID: " . $r['ustadz_id'] . "\n";
        echo "Nama: " . $r['nama'] . "\n";
        echo "Waktu: " . $r['waktu_absen'] . "\n";
        // Check fields safely
        $jenis = isset($r['jenis_absen']) ? $r['jenis_absen'] : 'NULL';
        $status = isset($r['status_kehadiran']) ? $r['status_kehadiran'] : 'NULL';
        echo "Jenis: " . $jenis . "\n";
        echo "Status: " . $status . "\n";
        echo "----\n";
    }
}
?>
