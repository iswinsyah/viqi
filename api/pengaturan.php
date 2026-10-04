<?php
require_once __DIR__ . '/../includes/koneksi.php';
header('Content-Type: application/json');

// Auto-update branding di database Hostinger jika masih menggunakan nama lama
@$conn->query("UPDATE pengaturan_web SET nama_sekolah = 'Villa Quran Baron Malang' WHERE nama_sekolah = 'Villa Quran Indonesia' OR nama_sekolah = 'Villa Quran'");

// Ambil data pengaturan utama web (ID = 1)
$result = $conn->query("SELECT * FROM pengaturan_web WHERE id = 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    if (empty($data['nama_sekolah']) || $data['nama_sekolah'] === 'Villa Quran Indonesia' || $data['nama_sekolah'] === 'Villa Quran') {
        $data['nama_sekolah'] = 'Villa Quran Baron Malang';
    }
    echo json_encode($data);
} else {
    // Fallback JSON jika tabel kosong (default)
    echo json_encode([
        'nama_sekolah' => 'Villa Quran Baron Malang',
        'nomor_wa' => '6285189918115',
        'pesan_default' => "Assalamu'alaikum Admin Villa Quran Baron Malang, saya ingin bertanya seputar pendaftaran santri baru."
    ]); 
}
?>