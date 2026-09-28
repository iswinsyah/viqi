<?php
require_once 'koneksi.php';
header('Content-Type: application/json');

// Auto-update jika masih memakai teks lama
@$conn->query("UPDATE pengaturan_tentang SET judul = 'Tentang Sekolah Tahfidz Villa Quran Baron Malang' WHERE judul = 'Tentang Villa Quran Indonesia' OR judul = 'Tentang Sekolah Tahfidz Villa Quran Indonesia' OR judul = 'Tentang Villa Quran' OR judul = 'Tentang Villa Quran Baron Malang'");

// Ambil data profil tentang kami (ID = 1)
$result = $conn->query("SELECT * FROM pengaturan_tentang WHERE id = 1");

if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
    if (empty($data['judul']) || $data['judul'] === 'Tentang Villa Quran Indonesia' || $data['judul'] === 'Tentang Sekolah Tahfidz Villa Quran Indonesia' || $data['judul'] === 'Tentang Villa Quran Baron Malang' || $data['judul'] === 'Tentang Villa Quran') {
        $data['judul'] = 'Tentang Sekolah Tahfidz Villa Quran Baron Malang';
    }
    echo json_encode($data);
} else {
    // Fallback JSON jika tabel kosong
    echo json_encode([
        'id' => 1,
        'judul' => 'Tentang Sekolah Tahfidz Villa Quran Baron Malang',
        'konten' => '<p>Berawal dari cita-cita mulia untuk menghadirkan lingkungan tahfidz yang nyaman dan mendidik, Villa Quran Baron Malang hadir dengan konsep pendidikan modern memadukan kurikulum Islam, adab, dan keterampilan abad 21 di lingkungan yang membahagiakan layaknya sebuah villa.</p>',
        'gambar_url' => 'https://images.unsplash.com/photo-1577896851231-70ef18881754?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'
    ]); 
}
?>