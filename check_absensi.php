<?php
require_once 'koneksi.php';

$today = date('Y-m-d');
$sql = "SELECT a.*, u.nama 
        FROM absensi_pegawai a 
        LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
        WHERE DATE(a.waktu_absen) = '$today' 
        ORDER BY a.waktu_absen ASC";

$res = $conn->query($sql);
echo "<h3>Absensi Pegawai Hari Ini ($today)</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr>
        <th>ID Absen</th>
        <th>Ustadz ID</th>
        <th>Nama</th>
        <th>Waktu Absen</th>
        <th>Jenis Absen</th>
        <th>Status Kehadiran</th>
        <th>Keterangan</th>
        <th>Koordinat</th>
      </tr>";

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['ustadz_id']}</td>";
        echo "<td>{$row['nama']}</td>";
        echo "<td>{$row['waktu_absen']}</td>";
        echo "<td>{$row['jenis_absen']}</td>";
        echo "<td>{$row['status_kehadiran']}</td>";
        echo "<td>{$row['keterangan']}</td>";
        echo "<td>{$row['koordinat_pegawai']}</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='8'>Tidak ada data absensi untuk hari ini.</td></tr>";
}
echo "</table>";
?>
