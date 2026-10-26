<?php
require_once __DIR__ . '/koneksi.php';

$token = $_GET['token'] ?? '';
if ($token !== 'viqi_audit_fikri_8899') {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

header('Content-Type: application/json');

// 1. Cari user
$users = [];
$q_u = $conn->query("SELECT id, nama, role, status_pegawai FROM akun_ustadz WHERE nama LIKE '%Zainul%' OR nama LIKE '%Fikri%' OR nama LIKE '%Mochammad%'");
if ($q_u) {
    while ($r = $q_u->fetch_assoc()) {
        $users[] = $r;
    }
}

// Cek juga di app_users jika belum ketemu
$app_users = [];
$q_app = $conn->query("SELECT id, username, nama_lengkap, roles, ref_id FROM app_users WHERE nama_lengkap LIKE '%Zainul%' OR nama_lengkap LIKE '%Fikri%' OR username LIKE '%fikri%'");
if ($q_app) {
    while ($r = $q_app->fetch_assoc()) {
        $app_users[] = $r;
    }
}

// 2. Ambil absensi bulan ini & 60 hari terakhir untuk user terkait
$ustadz_ids = array_map(function($u) { return (int)$u['id']; }, $users);
foreach ($app_users as $au) {
    if (!empty($au['ref_id'])) $ustadz_ids[] = (int)$au['ref_id'];
    $ustadz_ids[] = (int)$au['id'];
}
$ustadz_ids = array_unique(array_filter($ustadz_ids));

$absensi_records = [];
if (!empty($ustadz_ids)) {
    $ids_str = implode(',', $ustadz_ids);
    $q_abs = $conn->query("SELECT a.*, u.nama 
        FROM absensi_pegawai a 
        LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
        WHERE a.ustadz_id IN ($ids_str) 
        ORDER BY a.waktu_absen DESC 
        LIMIT 100");
    if ($q_abs) {
        while ($r = $q_abs->fetch_assoc()) {
            $absensi_records[] = $r;
        }
    }
}

// 3. Cek juga jika ada record di absensi_pegawai secara umum dengan keterangan atau nama Fikri
$general_fikri_abs = [];
$q_gen = $conn->query("SELECT a.*, u.nama 
    FROM absensi_pegawai a 
    LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
    WHERE u.nama LIKE '%Fikri%' OR u.nama LIKE '%Zainul%' OR a.keterangan LIKE '%Fikri%'
    ORDER BY a.waktu_absen DESC LIMIT 50");
if ($q_gen) {
    while ($r = $q_gen->fetch_assoc()) {
        $general_fikri_abs[] = $r;
    }
}

// 4. Cek semua record absensi_pegawai bulan ini (Oktober 2026 atau bulan berjalan)
$all_recent_abs = [];
$q_rec = $conn->query("SELECT a.id, a.ustadz_id, u.nama, a.waktu_absen, a.jenis_absen, a.status_kehadiran, a.koordinat_pegawai, a.keterangan 
    FROM absensi_pegawai a 
    LEFT JOIN akun_ustadz u ON a.ustadz_id = u.id 
    ORDER BY a.waktu_absen DESC LIMIT 30");
if ($q_rec) {
    while ($r = $q_rec->fetch_assoc()) {
        $all_recent_abs[] = $r;
    }
}

echo json_encode([
    'status' => 'success',
    'users' => $users,
    'app_users' => $app_users,
    'absensi_records' => $absensi_records,
    'general_fikri_abs' => $general_fikri_abs,
    'all_recent_abs' => $all_recent_abs
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
