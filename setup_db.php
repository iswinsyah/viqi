<?php
// Panggil file koneksi
require_once 'koneksi.php';

echo "<h2>Memulai Setup Database...</h2>";

// 1. Membuat Tabel Agen
$sql_agen = "CREATE TABLE IF NOT EXISTS agen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    bank VARCHAR(50) NOT NULL,
    rekening VARCHAR(50) NOT NULL,
    kode_ref VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_agen) === TRUE) {
    echo "✅ Tabel <b>'agen'</b> berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel agen: " . $conn->error . "<br>";
}

// 2. Membuat Tabel Leads (Orang yang download brosur)
$sql_leads = "CREATE TABLE IF NOT EXISTS leads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    whatsapp VARCHAR(20) NOT NULL,
    kode_ref VARCHAR(50) DEFAULT 'organik',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_leads) === TRUE) {
    echo "✅ Tabel <b>'leads'</b> berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel leads: " . $conn->error . "<br>";
}

// 3. Membuat Tabel Pendaftar SPMB (Sederhana)
$sql_spmb = "CREATE TABLE IF NOT EXISTS pendaftar_spmb (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenjang VARCHAR(50),
    nama_lengkap VARCHAR(150),
    nik VARCHAR(20),
    nisn VARCHAR(20),
    whatsapp_ortu VARCHAR(20),
    status VARCHAR(50) DEFAULT 'Menunggu Tes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_spmb) === TRUE) {
    echo "✅ Tabel <b>'pendaftar_spmb'</b> berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel pendaftar_spmb: " . $conn->error . "<br>";
}

// 4. Membuat Tabel Pengaturan Pop-up
$sql_popup = "CREATE TABLE IF NOT EXISTS pengaturan_popup (
    id INT PRIMARY KEY,
    is_active TINYINT(1) DEFAULT 1,
    img_src VARCHAR(255),
    kiri_judul VARCHAR(100),
    kiri_sub VARCHAR(255),
    kanan_judul VARCHAR(255),
    kanan_desc TEXT,
    file_url VARCHAR(255)
)";

if ($conn->query($sql_popup) === TRUE) {
    echo "✅ Tabel <b>'pengaturan_popup'</b> berhasil dibuat.<br>";
    // Masukkan data default jika masih kosong
    $conn->query("INSERT IGNORE INTO pengaturan_popup (id, is_active, img_src, kiri_judul, kiri_sub, kanan_judul, kanan_desc, file_url) 
    VALUES (1, 1, 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', 'E-Book Spesial:', '\"Rahasia Mendidik Generasi Alpha Menjadi Hafidz Quran Berkarakter\"', 'Akses Parenting School <span class=\"text-amber-500\">& E-Book Gratis!</span>', 'Masukkan data Anda untuk mendapatkan tautan unduhan E-Book dan jadwal kelas langsung ke WhatsApp Anda.', 'ebook-parenting.pdf')");
}

echo "<br><b>Selesai!</b> Database Anda sudah siap digunakan.";

$conn->close();
?>