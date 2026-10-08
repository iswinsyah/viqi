<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php';
require_once 'auth-unified.php';

// Validasi Akses: Boleh diakses jika logged in
$user = getCurrentUser();
if (!$user && !isset($_SESSION['admin_logged_in']) && !isset($_SESSION['yayasan_logged_in']) && !isset($_SESSION['yayasan_user']) && !isset($_SESSION['app_user_id'])) {
    header("Location: login.php");
    exit;
}

// Perintahkan browser untuk mendownload sebagai file Excel (.xls)
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Data_Pendaftar_SPMB_VQ_" . date('Y-m-d') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil data dari database
$sql = "SELECT * FROM pendaftar_spmb ORDER BY id DESC";
$result = $conn->query($sql);
?>
<!-- Kita cetak menggunakan tabel HTML, Excel akan otomatis membacanya dengan rapi -->
<table border="1">
    <thead>
        <tr>
            <th style="background-color: #10b981; color: white;">No</th>
            <th style="background-color: #10b981; color: white;">Tanggal Daftar</th>
            <th style="background-color: #10b981; color: white;">Jenjang</th>
            <th style="background-color: #10b981; color: white;">Nama Lengkap</th>
            <th style="background-color: #10b981; color: white;">NIK</th>
            <th style="background-color: #10b981; color: white;">NISN</th>
            <th style="background-color: #10b981; color: white;">No. WhatsApp Ortu</th>
            <th style="background-color: #10b981; color: white;">Asal Sekolah</th>
            <th style="background-color: #10b981; color: white;">Status Seleksi</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $no = 1;
        if ($result && $result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "<td>" . strtoupper($row['jenjang']) . "</td>";
                echo "<td>" . $row['nama_lengkap'] . "</td>";
                echo "<td style='mso-number-format:\"\\@\";'>" . $row['nik'] . "</td>"; // mso-number-format menjaga format text (0 di depan tidak hilang)
                echo "<td style='mso-number-format:\"\\@\";'>" . $row['nisn'] . "</td>";
                echo "<td style='mso-number-format:\"\\@\";'>" . $row['whatsapp_ortu'] . "</td>";
                echo "<td>" . $row['asal_sekolah'] . "</td>";
                echo "<td>" . $row['status'] . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </tbody>
</table>