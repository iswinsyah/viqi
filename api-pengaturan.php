<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Ambil data pengaturan utama web (ID = 1)
$result = $conn->query("SELECT nomor_wa, pesan_default FROM pengaturan_web WHERE id = 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    // Fallback JSON jika tabel kosong (default)
    echo json_encode(['nomor_wa' => '6281234567890', 'pesan_default' => "Assalamu'alaikum Admin Villa Quran, saya ingin bertanya seputar pendaftaran santri baru."]); 
}
?>