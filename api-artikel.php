<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
$sql = "SELECT id, judul, kategori, gambar_cover, konten, created_at, published_at FROM artikel WHERE status='publish' OR (status='jadwalkan' AND published_at <= NOW()) ORDER BY COALESCE(published_at, created_at) DESC LIMIT $limit";
$result = $conn->query($sql);
$data = [];

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $clean_text = strip_tags($row['konten']);
        // Batasi teks maksimal 120 karakter untuk cuplikan (snippet)
        $row['cuplikan'] = mb_strimwidth($clean_text, 0, 120, '...');
        // Timpa created_at dengan jadwal rilis jika ada
        $row['created_at'] = (!empty($row['published_at'])) ? $row['published_at'] : $row['created_at'];
        unset($row['konten']); // Sembunyikan konten penuh agar API tetap ringan
        $data[] = $row;
    }
}
echo json_encode($data);
?>