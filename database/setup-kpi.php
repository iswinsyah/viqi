<?php
require_once 'koneksi.php';

echo "<h2>Memulai Setup Database untuk Fitur KPI...</h2>";

// 1. Tambah kolom 'created_by_user_id' di master_silabus
$sql1 = "ALTER TABLE master_silabus ADD COLUMN created_by_user_id INT NULL AFTER capaian_pembelajaran";
if ($conn->query($sql1) === TRUE) {
    echo "✅ Kolom 'created_by_user_id' ditambahkan ke tabel 'master_silabus'.<br>";
} else {
    echo "⚠️ Gagal/sudah ada kolom 'created_by_user_id' di 'master_silabus'.<br>";
}

// 2. Buat tabel untuk mencatat aktivitas AI
$sql2 = "CREATE TABLE IF NOT EXISTS log_aktivitas_ai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fitur VARCHAR(50) NOT NULL,
    detail_prompt TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql2) === TRUE) {
    echo "✅ Tabel 'log_aktivitas_ai' berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel log_aktivitas_ai: " . $conn->error . "<br>";
}

// 3. Buat tabel untuk skor supervisi dari Kepala Sekolah
$sql3 = "CREATE TABLE IF NOT EXISTS supervisi_mengajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal_supervisi DATE,
    skor INT,
    catatan TEXT,
    supervisor_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql3) === TRUE) {
    echo "✅ Tabel 'supervisi_mengajar' berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel supervisi_mengajar: " . $conn->error . "<br>";
}

// 4. Buat tabel rekapitulasi skor KPI bulanan
$sql4 = "CREATE TABLE IF NOT EXISTS kpi_skor_ustadz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    periode VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    skor_total DECIMAL(5,2),
    detail_skor JSON,
    UNIQUE KEY (user_id, periode)
)";
if ($conn->query($sql4) === TRUE) {
    echo "✅ Tabel 'kpi_skor_ustadz' berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel kpi_skor_ustadz: " . $conn->error . "<br>";
}

// 5. Buat tabel untuk log kehadiran rapat
$sql5 = "CREATE TABLE IF NOT EXISTS log_kehadiran_rapat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal_rapat DATE NOT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql5) === TRUE) {
    echo "✅ Tabel 'log_kehadiran_rapat' berhasil dibuat.<br>";
} else {
    echo "❌ Error membuat tabel log_kehadiran_rapat: " . $conn->error . "<br>";
}

echo "<br><b>Selesai!</b> Database Anda sudah siap untuk fitur KPI.";

$conn->close();
?>