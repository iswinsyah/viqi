<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

// --- PENGATURAN ---
define('ENCRYPTION_KEY', 'kunci-rahasia-absensi-viqi-2026');
define('ENCRYPTION_IV', '1234567890123456');
define('MAX_DISTANCE_METERS', 50); // Jarak toleransi dari titik QR (dalam meter)

// PENGATURAN KOORDINAT MULTI-LOKASI
// TODO: Pastikan koordinat di sini SAMA PERSIS dengan di admin-qr-display.php
$locations = [
    'kantor_utama' => [
        'nama' => 'Gedung B (Kantor Villa Quran)',
        'coords' => ['latitude' => -6.595038, 'longitude' => 106.800247],
    ],
    'asrama_rijal' => [
        'nama' => 'Gedung A (Asrama Rijal)',
        'coords' => ['latitude' => -6.597638, 'longitude' => 106.79955],
    ],
    'asrama_nisa' => [
        'nama' => 'Gedung C (Asrama Nisa)',
        'coords' => ['latitude' => -6.598333, 'longitude' => 106.801111],
    ],
];

// --- BUAT TABEL OTOMATIS ---
$conn->query("CREATE TABLE IF NOT EXISTS absensi_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    waktu_absen DATETIME NOT NULL,
    jenis_absen VARCHAR(50) DEFAULT 'Harian',
    status_kehadiran VARCHAR(20) DEFAULT 'Masuk',
    koordinat_pegawai VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Self-healing: Tambahkan kolom status_kehadiran jika belum ada
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN status_kehadiran VARCHAR(20) DEFAULT 'Masuk' AFTER jenis_absen");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN keterangan VARCHAR(100) DEFAULT NULL AFTER koordinat_pegawai");

function json_response($status, $message, $data = []) {
    die(json_encode(['status' => $status, 'message' => $message] + $data));
}

function haversine_distance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // Radius bumi dalam meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response('error', 'Metode tidak diizinkan.');
}

// --- PROSES VALIDASI ---
date_default_timezone_set('Asia/Jakarta');

$ustadz_id = $_SESSION['ustadz_id'];
$qr_data_base64 = $_POST['qr_data'] ?? '';
$user_lat = (float)($_POST['user_lat'] ?? 0);
$user_lon = (float)($_POST['user_lon'] ?? 0);
$req_jenis_absen = $_POST['jenis_absen'] ?? 'Harian';

// F. Validasi Hak Akses Role Khusus Harian
if ($req_jenis_absen === 'Harian') {
    $user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
    $eligible_roles = ['kepala_sekolah', 'kepala_mahad', 'admin_sekolah', 'musyrif'];
    $is_eligible = false;
    foreach ($user_roles as $role) {
        if (in_array(trim($role), $eligible_roles)) {
            $is_eligible = true;
            break;
        }
    }
    if (!$is_eligible) {
        json_response('error', 'Anda tidak memiliki hak akses untuk melakukan Absensi Harian.');
    }
}

if ($user_lat == 0 || $user_lon == 0) {
    json_response('error', 'Koordinat lokasi Anda tidak valid atau gagal dibaca.');
}

$qr_location_key = '';
$qr_jenis_absen = $req_jenis_absen;
$distance = 0;
$target_location_name = '';

// A. Tentukan Status Kehadiran (Masuk atau Pulang) berdasarkan absen hari ini
$today = date('Y-m-d');
// Pengecekan riwayat absensi sukses untuk hari ini
$res_check = $conn->query("SELECT id, waktu_absen, status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = '$qr_jenis_absen' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen ASC");

$status_kehadiran = 'Masuk';
if ($res_check) {
    $num_absen = $res_check->num_rows;
    if ($num_absen >= 2) {
        json_response('error', "Anda sudah menyelesaikan absensi Masuk dan Pulang untuk $qr_jenis_absen hari ini.");
    } elseif ($num_absen == 1) {
        $row = $res_check->fetch_assoc();
        // Cek jeda waktu untuk menghindari scan ganda tanpa sengaja (misal jeda < 30 menit ditolak)
        $waktu_terakhir = strtotime($row['waktu_absen']);
        if (time() - $waktu_terakhir < 1800) { // 1800 detik = 30 menit
            json_response('error', 'Absen terlalu cepat dari absen sebelumnya. Harap tunggu minimal 30 menit untuk absen Pulang.');
        }
        $status_kehadiran = 'Pulang';
    }
}

// B. Validasi Waktu Khusus Absensi Harian/Pegawai & Kalkulasi Kedisiplinan Kerja
$keterangan = NULL;
$warning_msg = null;

if ($qr_jenis_absen === 'Harian' || $qr_jenis_absen === 'Pegawai') {
    $current_time = date('H:i');
    if ($status_kehadiran === 'Masuk') {
        if ($current_time < '07:00' || $current_time > '13:00') {
            json_response('error', 'Absen Kedatangan harian hanya dibuka antara pukul 07:00 s/d 13:00 WIB.');
        }
        
        // Pengecekan Keterlambatan (> 07:00 WIB)
        if ($current_time > '07:00') {
            $work_start = strtotime(date('Y-m-d') . ' 07:00:00');
            $absen_time = time();
            $diff_seconds = $absen_time - $work_start;
            $diff_minutes = max(1, floor($diff_seconds / 60));
            
            $keterangan = "Terlambat: $diff_minutes menit";
            $warning_msg = "Anda terdeteksi Terlambat masuk kerja selama $diff_minutes menit (Absen dilakukan pukul " . date('H:i') . "). Catatan ini telah disimpan di database.";
        } else {
            $keterangan = 'Tepat Waktu';
        }
    } elseif ($status_kehadiran === 'Pulang') {
        // Pengecekan Pulang Lebih Awal (Absen Pulang diperbolehkan kapan saja, tetapi jika sebelum 13:00 dicatat sebagai pelanggaran)
        if ($current_time < '13:00') {
            $work_end = strtotime(date('Y-m-d') . ' 13:00:00');
            $absen_time = time();
            $diff_seconds = $work_end - $absen_time;
            $diff_minutes = max(1, floor($diff_seconds / 60));
            
            if ($diff_minutes >= 60) {
                $hours = floor($diff_minutes / 60);
                $mins = $diff_minutes % 60;
                $time_str = "$hours jam $mins menit";
            } else {
                $time_str = "$diff_minutes menit";
            }
            
            $keterangan = "Pulang Lebih Awal: $time_str";
            $warning_msg = "Anda terdeteksi Pulang Awal selama $time_str sebelum jam pulang (Absen dilakukan pukul " . date('H:i') . "). Catatan ini telah disimpan di database.";
        } else {
            $keterangan = 'Tepat Waktu';
        }
    }
}

// C. Cek apakah pegawai memiliki status perizinan aktif untuk hari ini (Bypass GPS)
$res_izin = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND status_kehadiran = 'Izin'");
$is_izin_approved = ($res_izin && $res_izin->num_rows > 0);

// D. Cari koordinat dan jarak lokasi
if (!empty($qr_data_base64)) {
    // --- METODE HYBRID (DENGAN QR) ---
    $encrypted_data = base64_decode($qr_data_base64);
    $decrypted_json = openssl_decrypt($encrypted_data, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
    $qr_content = json_decode($decrypted_json, true);

    if (!$qr_content || !isset($qr_content['lokasi'])) {
        json_response('error', 'QR Code tidak valid atau format salah.');
    }

    $qr_location_key = $qr_content['lokasi'];
    $qr_jenis_absen = $qr_content['jenis'] ?? $req_jenis_absen;

    if (!isset($locations[$qr_location_key])) {
        json_response('error', 'Lokasi absensi dari QR Code tidak dikenali oleh sistem.');
    }

    $qr_coords = $locations[$qr_location_key]['coords'];
    $qr_lat = $qr_coords['latitude'];
    $qr_lon = $qr_coords['longitude'];
    $target_location_name = $locations[$qr_location_key]['nama'];

    $distance = haversine_distance($user_lat, $user_lon, $qr_lat, $qr_lon);
} else {
    // --- METODE GEOLOCATION-ONLY (TANPA QR) ---
    $closest_location_key = null;
    $closest_distance = null;

    foreach ($locations as $key => $loc) {
        $loc_lat = $loc['coords']['latitude'];
        $loc_lon = $loc['coords']['longitude'];
        $dist = haversine_distance($user_lat, $user_lon, $loc_lat, $loc_lon);

        if ($closest_distance === null || $dist < $closest_distance) {
            $closest_distance = $dist;
            $closest_location_key = $key;
        }
    }

    if ($closest_location_key !== null) {
        $qr_location_key = $closest_location_key;
        $distance = $closest_distance;
        $target_location_name = $locations[$closest_location_key]['nama'];
    } else {
        json_response('error', 'Tidak ada koordinat gedung resmi yang terdaftar.');
    }
}

// E. Validasi Jarak & Eksekusi Penyimpanan
if ($distance > MAX_DISTANCE_METERS && !$is_izin_approved) {
    // --- DI LUAR JANGKAUAN DAN TIDAK ADA IZIN ---
    // Tetap catat ke database dengan status "Ditolak ([Status])"
    $status_ditolak = 'Ditolak (' . $status_kehadiran . ')';
    $waktu_sekarang = date('Y-m-d H:i:s');
    $koordinat_pegawai = "$user_lat, $user_lon";
    $keterangan_ditolak = 'Ditolak (Di luar jangkauan)';

    $stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $status_ditolak, $koordinat_pegawai, $keterangan_ditolak);
    $stmt->execute();
    $stmt->close();

    // Kirim response khusus
    die(json_encode([
        'status' => 'rejected',
        'message' => 'Absensi ditolak karena Anda berada di luar jangkauan gedung. Jarak Anda: ' . round($distance) . ' meter dari ' . $target_location_name . '. Upaya ini telah dicatat sistem.'
    ]));
}

// --- JIKA DALAM JANGKAUAN ATAU ADA IZIN, SIMPAN SUKSES ---
$waktu_sekarang = date('Y-m-d H:i:s');
$koordinat_pegawai = "$user_lat, $user_lon";

$stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssss", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $status_kehadiran, $koordinat_pegawai, $keterangan);

if ($stmt->execute()) {
    $res_data = ['waktu' => date('H:i:s', strtotime($waktu_sekarang))];
    if ($warning_msg !== null) {
        $res_data['warning_msg'] = $warning_msg;
    }
    json_response('success', "Absensi $status_kehadiran berhasil dicatat!", $res_data);
} else {
    json_response('error', 'Gagal menyimpan data ke database: ' . $conn->error);
}

$stmt->close();
$conn->close();
?>