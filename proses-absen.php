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

// 1. Dekripsi data dari QR Code Statis
$encrypted_data = base64_decode($qr_data_base64);
$decrypted_json = openssl_decrypt($encrypted_data, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);
$qr_content = json_decode($decrypted_json, true);

if (!$qr_content || !isset($qr_content['lokasi'])) {
    json_response('error', 'QR Code tidak valid atau format salah.');
}

$qr_location_key = $qr_content['lokasi'];
$qr_jenis_absen = $qr_content['jenis'] ?? 'Harian';

// Cek apakah lokasi dari QR ada di konfigurasi kita
if (!isset($locations[$qr_location_key])) {
    json_response('error', 'Lokasi absensi dari QR Code tidak dikenali oleh sistem.');
}

// Ambil koordinat yang benar berdasarkan data dari QR
$qr_coords = $locations[$qr_location_key]['coords'];
$qr_lat = $qr_coords['latitude'];
$qr_lon = $qr_coords['longitude'];

// 2. Validasi Jarak Lokasi
$distance = haversine_distance($user_lat, $user_lon, $qr_lat, $qr_lon);
if ($distance > MAX_DISTANCE_METERS) {
    json_response('error', 'Anda berada di luar area absensi yang diizinkan. Jarak Anda: ' . round($distance) . ' meter.');
}

// 3. Tentukan Status Kehadiran (Masuk atau Pulang) berdasarkan absen hari ini
$today = date('Y-m-d');
$res_check = $conn->query("SELECT id, waktu_absen, status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = '$qr_jenis_absen' ORDER BY waktu_absen ASC");

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
            json_response('error', 'Scan terlalu cepat dari absen sebelumnya. Harap tunggu minimal 30 menit untuk absen Pulang.');
        }
        $status_kehadiran = 'Pulang';
    }
}

// --- JIKA SEMUA VALIDASI LOLOS, SIMPAN DATA ---
$waktu_sekarang = date('Y-m-d H:i:s');
$koordinat_pegawai = "$user_lat, $user_lon";

$stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $status_kehadiran, $koordinat_pegawai);

if ($stmt->execute()) {
    json_response('success', "Absensi $status_kehadiran berhasil dicatat!", ['waktu' => date('H:i:s', strtotime($waktu_sekarang))]);
} else {
    json_response('error', 'Gagal menyimpan data ke database: ' . $conn->error);
}

$stmt->close();
$conn->close();

?>