<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

header('Content-Type: application/json');

// --- PENGATURAN ---
define('ENCRYPTION_KEY', 'kunci-rahasia-absensi-viqi-2026');
define('ENCRYPTION_IV', '1234567890123456');
define('MAX_QR_AGE_SECONDS', 120); // Umur maksimal QR Code (2 menit)
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
    koordinat_pegawai VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

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
$ustadz_id = $_SESSION['ustadz_id'];
$qr_data_base64 = $_POST['qr_data'] ?? '';
$user_lat = (float)($_POST['user_lat'] ?? 0);
$user_lon = (float)($_POST['user_lon'] ?? 0);

if (empty($qr_data_base64) || $user_lat == 0 || $user_lon == 0) {
    json_response('error', 'Data tidak lengkap dari perangkat Anda.');
}

// 1. Cek apakah sudah absen hari ini
$today = date('Y-m-d');
$res_check = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today'");
if ($res_check && $res_check->num_rows > 0) {
    json_response('error', 'Anda sudah melakukan absensi hari ini.');
}

// 2. Dekripsi data dari QR Code
$encrypted_data = base64_decode($qr_data_base64);
$decrypted_json = openssl_decrypt($encrypted_data, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
$qr_content = json_decode($decrypted_json, true);

if (!$qr_content || !isset($qr_content['lokasi'], $qr_content['time'])) {
    json_response('error', 'QR Code tidak valid atau format salah.');
}

$qr_location_key = $qr_content['lokasi'];
$qr_time = $qr_content['time'];

// Cek apakah lokasi dari QR ada di konfigurasi kita
if (!isset($locations[$qr_location_key])) {
    json_response('error', 'Lokasi absensi dari QR Code tidak dikenali oleh sistem.');
}

// Ambil koordinat yang benar berdasarkan data dari QR
$qr_coords = $locations[$qr_location_key]['coords'];
$qr_lat = $qr_coords['latitude'];
$qr_lon = $qr_coords['longitude'];

// 3. Validasi Umur QR Code
$current_time = time();
if (($current_time - $qr_time) > MAX_QR_AGE_SECONDS) {
    json_response('error', 'QR Code sudah kedaluwarsa. Silakan scan ulang QR Code yang baru.');
}

// 4. Validasi Jarak Lokasi
$distance = haversine_distance($user_lat, $user_lon, $qr_lat, $qr_lon);
if ($distance > MAX_DISTANCE_METERS) {
    json_response('error', 'Anda berada di luar area absensi yang diizinkan. Jarak Anda: ' . round($distance) . ' meter.');
}

// 5. Validasi Rentang Waktu Absen (Contoh: 06:45 - 07:15)
$current_hour_minute = date('Hi');
if ($current_hour_minute < '0645' || $current_hour_minute > '0715') {
    json_response('error', 'Waktu absensi hanya dibuka antara pukul 06:45 - 07:15 WIB.');
}

// --- JIKA SEMUA VALIDASI LOLOS, SIMPAN DATA ---
$waktu_sekarang = date('Y-m-d H:i:s');
$koordinat_pegawai = "$user_lat, $user_lon";

$stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, koordinat_pegawai) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $ustadz_id, $waktu_sekarang, $koordinat_pegawai);

if ($stmt->execute()) {
    json_response('success', 'Absensi berhasil!', ['waktu' => date('H:i:s', strtotime($waktu_sekarang))]);
} else {
    json_response('error', 'Gagal menyimpan data ke database: ' . $conn->error);
}

$stmt->close();
$conn->close();

?>