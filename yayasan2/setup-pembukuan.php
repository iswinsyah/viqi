<?php
// yayasan2/setup-pembukuan.php
// Script inisialisasi database pembukuan terpusat Yayasan Villa Quran

require_once __DIR__ . '/../koneksi.php';

// 1. Buat Tabel Master Lembaga
$conn->query("CREATE TABLE IF NOT EXISTS keuangan_lembaga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lembaga VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif'
)");

// Seed Lembaga jika masih kosong
$check_lembaga = $conn->query("SELECT COUNT(id) as total FROM keuangan_lembaga");
$total_lembaga = $check_lembaga->fetch_assoc()['total'] ?? 0;
if ($total_lembaga == 0) {
    $conn->query("INSERT INTO keuangan_lembaga (nama_lembaga, status) VALUES 
        ('Sekolah Tahfidz', 'aktif'),
        ('Filantropi', 'aktif'),
        ('Unit Usaha', 'aktif')
    ");
}

// 2. Buat Tabel Bagan Akun (Chart of Accounts - COA)
$conn->query("CREATE TABLE IF NOT EXISTS keuangan_akun (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_akun VARCHAR(20) NOT NULL UNIQUE,
    nama_akun VARCHAR(150) NOT NULL,
    tipe_akun ENUM('Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban') NOT NULL
)");

// Seed Akun COA default jika masih kosong
$check_akun = $conn->query("SELECT COUNT(id) as total FROM keuangan_akun");
$total_akun = $check_akun->fetch_assoc()['total'] ?? 0;
if ($total_akun == 0) {
    $conn->query("INSERT INTO keuangan_akun (kode_akun, nama_akun, tipe_akun) VALUES 
        ('1101', 'Kas Utama Yayasan', 'Aset'),
        ('1102', 'Bank BSI Yayasan', 'Aset'),
        ('2101', 'Hutang Operasional', 'Kewajiban'),
        ('3101', 'Modal Awal Yayasan', 'Ekuitas'),
        ('4101', 'Pendapatan SPP Santri', 'Pendapatan'),
        ('4201', 'Penerimaan Zakat', 'Pendapatan'),
        ('4202', 'Penerimaan Infaq/Shadaqah', 'Pendapatan'),
        ('4203', 'Penerimaan Wakaf', 'Pendapatan'),
        ('4301', 'Pendapatan Unit Usaha', 'Pendapatan'),
        ('5101', 'Beban Gaji Asatidz & Staf', 'Beban'),
        ('5102', 'Beban Makan & Dapur Asrama', 'Beban'),
        ('5103', 'Beban Pemeliharaan Gedung', 'Beban'),
        ('5104', 'Beban Listrik & Internet', 'Beban')
    ");
}

// 3. Buat Tabel Jurnal Transaksi (Parent)
$conn->query("CREATE TABLE IF NOT EXISTS keuangan_jurnal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    no_bukti VARCHAR(50) NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 4. Buat Tabel Jurnal Detail (Debit/Kredit - Double Entry)
$conn->query("CREATE TABLE IF NOT EXISTS keuangan_jurnal_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurnal_id INT NOT NULL,
    akun_id INT NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0.00,
    kredit DECIMAL(15,2) DEFAULT 0.00,
    lembaga_id INT NOT NULL,
    FOREIGN KEY (jurnal_id) REFERENCES keuangan_jurnal(id) ON DELETE CASCADE,
    FOREIGN KEY (akun_id) REFERENCES keuangan_akun(id) ON DELETE RESTRICT,
    FOREIGN KEY (lembaga_id) REFERENCES keuangan_lembaga(id) ON DELETE RESTRICT
)");

?>
