<?php
require_once 'koneksi.php';

$today = date('Y-m-d');
$sql = "SELECT a.*, u.nama 
        FROM absensi_pegawai a 
        LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
        WHERE DATE(a.waktu_absen) = '$today' 
        ORDER BY a.waktu_absen ASC";

$res = $conn->query($sql);
echo "<pre>=== ABSENSI HARI INI ($today) ===\n";

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Ustadz ID: {$row['ustadz_id']} | Nama: {$row['nama']} | Waktu: {$row['waktu_absen']} | Jenis: {$row['jenis_absen']} | Status: {$row['status_kehadiran']} | Ket: {$row['keterangan']} | Coords: {$row['koordinat_pegawai']}\n";
    }
} else {
    echo "Tidak ada data absensi untuk hari ini.\n";
}
echo "</pre>";
?>
