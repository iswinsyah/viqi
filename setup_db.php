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

echo "<br><b>Selesai!</b> Database Anda sudah siap digunakan.";

$conn->close();
?>