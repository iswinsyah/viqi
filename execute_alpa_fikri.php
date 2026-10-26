<?php
require_once __DIR__ . '/koneksi.php';

$token = $_GET['token'] ?? '';
if ($token !== 'viqi_alpa_fikri_2026_ok') {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

// 1. Dapatkan user ID Mochammad Zainul Fikri
$res_u = $conn->query("SELECT id, nama, role, status_pegawai FROM akun_ustadz WHERE nama LIKE '%Zainul Fikri%' OR nama LIKE '%Fikri%' LIMIT 1");
if (!$res_u || $res_u->num_rows == 0) {
    die(json_encode(['status' => 'error', 'message' => 'Pegawai tidak ditemukan']));
}
$fikri = $res_u->fetch_assoc();
$ust_id = (int)$fikri['id'];

// 2. Cari semua record absensi Fikri yang menggunakan Fake GPS atau pada hari terindikasi manipulasi
$dates_target = ["'2026-10-26'", "'2026-10-24'", "'2026-10-13'", "'2026-10-02'", "'2026-10-01'"];
$dates_str = implode(',', $dates_target);

// Ambil data sebelum diupdate untuk laporan
$before_records = [];
$q_b = $conn->query("SELECT id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai, keterangan 
    FROM absensi_pegawai 
    WHERE ustadz_id = $ust_id 
    AND (
        DATE(waktu_absen) IN ($dates_str) 
        OR koordinat_pegawai LIKE '-7.9480025437284%' 
        OR koordinat_pegawai LIKE '-7.9480118412809%'
    )
    ORDER BY waktu_absen ASC");
if ($q_b) {
    while ($r = $q_b->fetch_assoc()) {
        $before_records[] = $r;
    }
}

// 3. Update status_kehadiran menjadi 'Alpa' dan beri keterangan pembatalan resmi
$keterangan_batal = 'Alpa (Dibatalkan Ketua Yayasan: Terdeteksi Fake GPS / Berada di Luar Kota)';
$sql_update = "UPDATE absensi_pegawai 
    SET status_kehadiran = 'Alpa', 
        keterangan = '$keterangan_batal' 
    WHERE ustadz_id = $ust_id 
    AND (
        DATE(waktu_absen) IN ($dates_str) 
        OR koordinat_pegawai LIKE '-7.9480025437284%' 
        OR koordinat_pegawai LIKE '-7.9480118412809%'
    )";
$conn->query($sql_update);
$affected_rows = $conn->affected_rows;

// 4. Nonaktifkan akun Fikri per instruksi Ketua Yayasan
$conn->query("UPDATE akun_ustadz SET status_pegawai = 'Nonaktif' WHERE id = $ust_id");
$conn->query("UPDATE app_users SET status_aktif = 0 WHERE ref_id = $ust_id OR username LIKE '%fikri%'");

// 5. Ambil data setelah update untuk konfirmasi
$after_records = [];
$q_a = $conn->query("SELECT id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai, keterangan 
    FROM absensi_pegawai 
    WHERE ustadz_id = $ust_id 
    AND (
        DATE(waktu_absen) IN ($dates_str) 
        OR koordinat_pegawai LIKE '-7.9480025437284%' 
        OR koordinat_pegawai LIKE '-7.9480118412809%'
    )
    ORDER BY waktu_absen ASC");
if ($q_a) {
    while ($r = $q_a->fetch_assoc()) {
        $after_records[] = $r;
    }
}

echo json_encode([
    'status' => 'success',
    'employee' => $fikri,
    'affected_rows' => $affected_rows,
    'before_records' => $before_records,
    'after_records' => $after_records
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
