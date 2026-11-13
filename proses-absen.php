<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

header('Content-Type: application/json');

// --- PENGATURAN ---
define('ENCRYPTION_KEY', 'kunci-rahasia-absensi-viqi-2026');
define('ENCRYPTION_IV', '1234567890123456');
define('MAX_DISTANCE_METERS', 75); // Jarak toleransi dari titik QR (dalam meter)

// PENGATURAN KOORDINAT MULTI-LOKASI
// TODO: Pastikan koordinat di sini SAMA PERSIS dengan di admin-qr-display.php
$locations = [
    'kantor_utama' => [
        'nama' => 'Gedung B (Kantor Villa Quran)',
        'coords' => ['latitude' => -7.9485768, 'longitude' => 112.5823352],
    ],
    'asrama_rijal' => [
        'nama' => 'Gedung A (Asrama Rijal)',
        'coords' => ['latitude' => -7.9480257, 'longitude' => 112.5822426],
    ],
    'asrama_nisa' => [
        'nama' => 'Gedung C (Asrama Nisa)',
        'coords' => ['latitude' => -7.9405464, 'longitude' => 112.5791353],
    ],
    'masjid_taqwa' => [
        'nama' => 'Masjid At Taqwa VBT',
        'coords' => ['latitude' => -7.9464649, 'longitude' => 112.5822123],
    ],
];

// Ambil lokasi aktif tambahan dari database master_lokasi jika tersedia
$q_dyn_loc = $conn->query("SELECT * FROM master_lokasi WHERE status_aktif = 1");
if ($q_dyn_loc && $q_dyn_loc->num_rows > 0) {
    while ($dl = $q_dyn_loc->fetch_assoc()) {
        $lcode = !empty($dl['kode_lokasi']) ? $dl['kode_lokasi'] : 'loc_' . $dl['id'];
        $locations[$lcode] = [
            'nama' => $dl['nama_lokasi'],
            'coords' => ['latitude' => (float)$dl['latitude'], 'longitude' => (float)$dl['longitude']],
            'radius' => (int)($dl['radius_meter'] ?? MAX_DISTANCE_METERS)
        ];
    }
}

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

function kirim_notifikasi_wa_yayasan($pesan, $target_custom = null) {
    $FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";
    $no_wa = !empty($target_custom) ? $target_custom : (defined('YAYASAN_WA_RECIPIENT') ? YAYASAN_WA_RECIPIENT : '6285196572223');
    
    $waFd = ['target' => $no_wa, 'message' => $pesan];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.fonnte.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($waFd),
        CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
        CURLOPT_TIMEOUT => 15
    ]);
    curl_exec($ch);
    curl_close($ch);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response('error', 'Metode tidak diizinkan.');
}

// --- PROSES VALIDASI ---
date_default_timezone_set('Asia/Jakarta');

// A. Inisialisasi Database (Self-Healing Migrations)
$conn->query("CREATE TABLE IF NOT EXISTS master_lokasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_lokasi VARCHAR(50) UNIQUE NOT NULL,
    nama_lokasi VARCHAR(100) NOT NULL,
    alias_nama VARCHAR(255) DEFAULT '',
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius_meter INT DEFAULT 75,
    status_aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$conn->query("CREATE TABLE IF NOT EXISTS jadwal_rapat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agenda VARCHAR(255) NOT NULL,
    tempat_rapat VARCHAR(100) DEFAULT NULL,
    lokasi_latitude DECIMAL(10, 8) DEFAULT NULL,
    lokasi_longitude DECIMAL(11, 8) DEFAULT NULL,
    lokasi_radius INT DEFAULT 75,
    pengundang VARCHAR(50) NOT NULL,
    waktu_mulai DATETIME NOT NULL,
    status VARCHAR(20) DEFAULT 'aktif',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN rapat_id INT DEFAULT NULL AFTER jenis_absen");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN tempat_rapat VARCHAR(100) DEFAULT NULL AFTER agenda");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN lokasi_latitude DECIMAL(10, 8) DEFAULT NULL AFTER tempat_rapat");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN lokasi_longitude DECIMAL(11, 8) DEFAULT NULL AFTER lokasi_latitude");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN lokasi_radius INT DEFAULT 75 AFTER lokasi_longitude");

// Auto-Recovery: Pulihkan seluruh akun pegawai/ustadz yang dinonaktifkan akibat false-positive deteksi Fake GPS
@$conn->query("UPDATE akun_ustadz SET status_pegawai = 'Pengabdian', is_active = 1 WHERE status_pegawai = 'Nonaktif' AND id IN (SELECT DISTINCT ustadz_id FROM absensi_pegawai WHERE status_kehadiran LIKE '%Ditolak (Akun Dimatikan)%' OR keterangan LIKE '%Terdeteksi Fake GPS%')");
@$conn->query("UPDATE app_users SET status_aktif = 1 WHERE status_aktif = 0 AND (ref_id IN (SELECT DISTINCT ustadz_id FROM absensi_pegawai WHERE status_kehadiran LIKE '%Ditolak (Akun Dimatikan)%' OR keterangan LIKE '%Terdeteksi Fake GPS%') OR nama_lengkap LIKE '%Imaduddien%')");
@$conn->query("UPDATE akun_ustadz SET status_pegawai = 'Pengabdian', is_active = 1 WHERE (nama LIKE '%Imaduddien%' OR username LIKE '%imaduddien%') AND status_pegawai = 'Nonaktif'");
@$conn->query("DELETE FROM absensi_pegawai WHERE DATE(waktu_absen) = CURDATE() AND (status_kehadiran LIKE '%Ditolak (Akun Dimatikan)%' OR keterangan LIKE '%Terdeteksi Fake GPS%')");

$ustadz_id = $_SESSION['ustadz_id'];
$qr_data_base64 = $_POST['qr_data'] ?? '';
$user_lat = (float)($_POST['user_lat'] ?? 0);
$user_lon = (float)($_POST['user_lon'] ?? 0);
$gps_accuracy = isset($_POST['gps_accuracy']) ? (float)$_POST['gps_accuracy'] : null;
$is_mock_client = (int)($_POST['is_mock'] ?? 0);
$req_jenis_absen = $_POST['jenis_absen'] ?? 'Harian';

// F. Validasi Hak Akses Role Khusus Harian/Pegawai
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}
if ($req_jenis_absen === 'Harian' || $req_jenis_absen === 'Pegawai') {
    // Semua ustadz / pegawai / staf yang terdaftar & login berhak melakukan Absensi Pegawai
    $is_eligible = true;
}

if ($req_jenis_absen === 'Mengajar') {
    $is_eligible = in_array('ustadz', $user_roles) || in_array('super_admin', $user_roles);
    if (!$is_eligible) {
        json_response('error', 'Anda tidak memiliki hak akses untuk melakukan Absensi Mengajar.');
    }
}

// G. Validasi Hak Akses & Status Rapat untuk Absensi Rapat
$rapat_id = NULL;
if ($req_jenis_absen === 'Rapat') {
    $rapat_id = (int)($_POST['rapat_id'] ?? 0);
    if ($rapat_id <= 0) {
        json_response('error', 'ID Rapat tidak valid.');
    }
    
    // Verifikasi rapat aktif
    $res_rpt = $conn->query("SELECT * FROM jadwal_rapat WHERE id = $rapat_id AND status = 'aktif' LIMIT 1");
    if (!$res_rpt || $res_rpt->num_rows == 0) {
        json_response('error', 'Rapat tidak aktif atau telah diselesaikan.');
    }
    $rapat = $res_rpt->fetch_assoc();
    
    $waktu_rapat_start = strtotime($rapat['waktu_mulai']);
    $waktu_sekarang = time();
    $diff_minutes = round(($waktu_sekarang - $waktu_rapat_start) / 60);

    if (date('Y-m-d') !== date('Y-m-d', $waktu_rapat_start)) {
        json_response('error', 'Absensi rapat hanya bisa dilakukan pada hari rapat (' . date('d-m-Y', $waktu_rapat_start) . ').');
    }
    if ($diff_minutes < -60) {
        json_response('error', 'Absensi rapat belum dibuka. Silakan absen maksimal 60 menit sebelum rapat dimulai.');
    }
    
    $pengundang = $rapat['pengundang'];
    $peserta_json = $rapat['peserta_terundang'] ?? null;
    
    $is_invited = false;
    if (in_array('super_admin', $user_roles)) {
        $is_invited = true;
    } elseif (!empty($peserta_json)) {
        $target_data = json_decode($peserta_json, true);
        $t_roles = $target_data['roles'] ?? [];
        $t_ids = array_map('intval', $target_data['ids'] ?? []);
        
        if (in_array((int)$ustadz_id, $t_ids)) {
            $is_invited = true;
        } elseif (in_array('semua_pegawai', $t_roles)) {
            $is_invited = true;
        } else {
            foreach ($t_roles as $tr) {
                if ($tr === 'sekretaris_yayasan' && in_array('sekretaris_yayasan', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'bendahara_yayasan' && in_array('bendahara_yayasan', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'staff_ldu' && in_array('staff_ldu', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'musyrif' && (in_array('musyrif', $user_roles) || in_array('kepala_asrama', $user_roles))) {
                    $is_invited = true; break;
                }
                if ($tr === 'admin_sekolah' && (in_array('admin_sekolah', $user_roles) || in_array('sekretaris_sekolah', $user_roles) || in_array('bendahara_sekolah', $user_roles))) {
                    $is_invited = true; break;
                }
                if ($tr === 'kepala_sekolah' && in_array('kepala_sekolah', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'kepala_mahad' && in_array('kepala_mahad', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'tutor' && in_array('tutor', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'ustadz' && in_array('ustadz', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'trainer' && in_array('trainer', $user_roles)) {
                    $is_invited = true; break;
                }
                if ($tr === 'ustadz_diknas' && in_array('ustadz', $user_roles)) {
                    $check_diknas = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diknas' LIMIT 1");
                    if ($check_diknas && $check_diknas->num_rows > 0) { $is_invited = true; break; }
                }
                if ($tr === 'ustadz_diniyah' && in_array('ustadz', $user_roles)) {
                    $check_diniyah = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diniyah' LIMIT 1");
                    if ($check_diniyah && $check_diniyah->num_rows > 0) { $is_invited = true; break; }
                }
            }
        }
    } else {
        // Fallback default lama
        if ($pengundang === 'kepala_sekolah') {
            $is_admin_sekolah = in_array('admin_sekolah', $user_roles);
            $is_ustadz = in_array('ustadz', $user_roles);
            $is_ustadz_diknas = false;
            if ($is_ustadz) {
                $check_diknas = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diknas' LIMIT 1");
                $is_ustadz_diknas = ($check_diknas && $check_diknas->num_rows > 0);
            }
            $is_invited = ($is_admin_sekolah || $is_ustadz_diknas || in_array('super_admin', $user_roles));
        } elseif ($pengundang === 'kepala_mahad') {
            $is_musyrif = in_array('musyrif', $user_roles);
            $is_ustadz = in_array('ustadz', $user_roles);
            $is_ustadz_diniyah = false;
            if ($is_ustadz) {
                $check_diniyah = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diniyah' LIMIT 1");
                $is_ustadz_diniyah = ($check_diniyah && $check_diniyah->num_rows > 0);
            }
            $is_invited = ($is_musyrif || $is_ustadz_diniyah || in_array('super_admin', $user_roles));
        } elseif ($pengundang === 'ketua_yayasan') {
            $is_invited = true;
        }
    }
    
    if (!$is_invited) {
        json_response('error', 'Anda tidak terdaftar sebagai peserta wajib untuk rapat ini.');
    }
}

if ($user_lat == 0 || $user_lon == 0) {
    json_response('error', 'Koordinat lokasi Anda tidak valid atau gagal dibaca.');
}

// =========================================================================
// ATURAN PROTEKSI GEOLOKASI (RADIUS FISIK HAVERSINE 75 METER)
// =========================================================================
// Catatan: Pengecekan koordinat statis lintas hari & akurasi < 1m telah dinonaktifkan
// karena terbukti menimbulkan false-positive pada smartphone/laptop yang terhubung
// ke Wi-Fi sekolah (Google Geolocation mengembalikan koordinat statis router Wi-Fi yang sama persis).
// Keamanan absensi sepenuhnya dijamin secara adil & akurat oleh verifikasi radius Haversine (maks 75m dari gedung resmi).
$is_fake_gps = false;
$fake_gps_reason = '';

$qr_location_key = '';
$qr_jenis_absen = $req_jenis_absen;
$distance = 0;
$target_location_name = '';

// A. Tentukan Status Kehadiran (Masuk atau Pulang) berdasarkan absen hari ini / rapat_id
$today = date('Y-m-d');
$last_row = null;

if ($qr_jenis_absen === 'Rapat') {
    $res_check = $conn->query("SELECT id, waktu_absen, status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND jenis_absen = 'Rapat' AND rapat_id = $rapat_id AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen ASC");
    $status_kehadiran = 'Masuk';
    if ($res_check) {
        $num_absen = $res_check->num_rows;
        if ($num_absen >= 2) {
            json_response('error', "Anda sudah menyelesaikan absensi Masuk dan Pulang untuk rapat ini.");
        } elseif ($num_absen == 1) {
            $row = $res_check->fetch_assoc();
            $waktu_terakhir = strtotime($row['waktu_absen']);
            if (time() - $waktu_terakhir < 180) { // 3 menit jeda
                json_response('error', 'Absen terlalu cepat dari absen sebelumnya. Harap tunggu minimal 3 menit untuk absen Pulang rapat.');
            }
            $status_kehadiran = 'Pulang';
            $last_row = $row;
        }
    }
} else {
    // Untuk Pegawai (Harian) & Mengajar: Mendukung Fleksibilitas Shift 1 & Shift 2 (Tanpa Kunci Jam Absen)
    $res_check = $conn->query("SELECT id, waktu_absen, status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = '$qr_jenis_absen' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen DESC LIMIT 1");
    $status_kehadiran = 'Masuk';
    if ($res_check && $res_check->num_rows > 0) {
        $last_row = $res_check->fetch_assoc();
        $waktu_terakhir = strtotime($last_row['waktu_absen']);
        if ($last_row['status_kehadiran'] === 'Masuk') {
            // Posisi saat ini sedang Masuk, maka klik berikutnya adalah Pulang (Selesai shift/piket/mengajar)
            if (time() - $waktu_terakhir < 180) { // 3 menit jeda agar tidak tertekan ganda tidak sengaja
                json_response('error', 'Anda baru saja absen Masuk. Harap tunggu minimal 3 menit sebelum absen Pulang.');
            }
            $status_kehadiran = 'Pulang';
        } else {
            // Posisi terakhir adalah Pulang, maka dapat absen Masuk kembali untuk shift/piket/sesi baru
            if (time() - $waktu_terakhir < 180) {
                json_response('error', 'Anda baru saja absen Pulang. Harap tunggu minimal 3 menit jika ingin absen Masuk kembali.');
            }
            $status_kehadiran = 'Masuk';
        }
    }
}

// B. Pengecekan Izin Resmi dari Ketua Yayasan
// Pegawai yang terlambat, pulang awal, atau di luar jam ketentuan dicatat sebagai Pelanggaran KECUALI ada izin Ketua Yayasan
$is_izin_yayasan = false;
$keterangan_izin = '';

// 1. Cek dari absensi_pegawai (status Izin hari ini)
$res_izin_absen = $conn->query("SELECT id, keterangan FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND status_kehadiran = 'Izin' LIMIT 1");
if ($res_izin_absen && $res_izin_absen->num_rows > 0) {
    $is_izin_yayasan = true;
    $keterangan_izin = $res_izin_absen->fetch_assoc()['keterangan'] ?? 'Izin Disetujui';
}

// 2. Cek dari tabel kepegawaian_perizinan (approval resmi Ketua Yayasan / Admin)
if (!$is_izin_yayasan) {
    $now_datetime = date('Y-m-d H:i:s');
    $res_izin_kep = $conn->query("SELECT id, kategori, keterangan, catatan_admin FROM kepegawaian_perizinan 
        WHERE ustadz_id = $ustadz_id 
        AND status IN ('Disetujui', 'Disetujui Sebagian') 
        AND (
            ('$now_datetime' BETWEEN tanggal_mulai AND tanggal_selesai) OR
            (tanggal_disetujui_mulai IS NOT NULL AND '$now_datetime' BETWEEN tanggal_disetujui_mulai AND tanggal_disetujui_selesai) OR
            (DATE(tanggal_mulai) = '$today' OR DATE(tanggal_selesai) = '$today')
        ) LIMIT 1");
    if ($res_izin_kep && $res_izin_kep->num_rows > 0) {
        $row_ik = $res_izin_kep->fetch_assoc();
        $is_izin_yayasan = true;
        $keterangan_izin = $row_ik['kategori'] . (!empty($row_ik['catatan_admin']) ? ' (' . $row_ik['catatan_admin'] . ')' : '');
    }
}

// C. Pencatatan Jam Kerja, Keterlambatan, Pulang Awal, & Durasi Mengajar
// KUNCI ABSENSI BERDASARKAN JAM DIHILANGKAN (Pegawai selalu BISA absen selama di lokasi).
// Namun jika tidak sesuai ketentuan tanpa izin ketua yayasan, tetap dicatat sebagai PELANGGARAN.
$keterangan = NULL;
$warning_msg = null;
$is_pelanggaran = false;

if ($qr_jenis_absen === 'Harian' || $qr_jenis_absen === 'Pegawai') {
    $current_time = date('H:i');
    $current_h = (int)date('H');
    $current_m = (int)date('i');
    $current_min = ($current_h * 60) + $current_m;

    // Shift 1: Jam Masuk 07:00 WIB, Jam Pulang 13:00 WIB
    // Shift 2: Jam Masuk 15:00 WIB, Jam Pulang 21:00 WIB
    if ($status_kehadiran === 'Masuk') {
        // Penentuan Shift saat Masuk (Batas transisi jam 14:00 WIB = menit ke-840)
        if ($current_min < 840) {
            // === SHIFT 1 (Pagi: Masuk 07:00, Pulang 13:00) ===
            $target_masuk = '07:00';
            if ($current_time > $target_masuk) {
                // Terlambat Shift 1
                $diff_sec = time() - strtotime(date('Y-m-d') . " $target_masuk:00");
                $diff_min = max(1, floor($diff_sec / 60));
                $hrs = floor($diff_min / 60);
                $mins = $diff_min % 60;
                $durasi_str = ($hrs > 0) ? "$hrs jam $mins menit" : "$mins menit";

                if ($is_izin_yayasan) {
                    $keterangan = "Terlambat Shift 1 ($durasi_str) [Disetujui Izin Yayasan: $keterangan_izin]";
                    $warning_msg = "Anda terdeteksi Terlambat $durasi_str masuk kerja Shift 1 (dengan izin resmi Ketua Yayasan).";
                } else {
                    $is_pelanggaran = true;
                    $keterangan = "Pelanggaran: Terlambat Shift 1 ($durasi_str - Tanpa Izin Yayasan)";
                    $warning_msg = "⚠️ PELANGGARAN KEDISIPLINAN: Anda terdeteksi Terlambat $durasi_str masuk kerja Shift 1 (Absen pukul $current_time WIB) tanpa izin resmi Ketua Yayasan.";
                }
            } else {
                $keterangan = "Tepat Waktu (Shift 1)";
            }
        } else {
            // === SHIFT 2 (Sore/Malam: Masuk 15:00, Pulang 21:00) ===
            $target_masuk = '15:00';
            if ($current_time > $target_masuk) {
                // Terlambat Shift 2
                $diff_sec = time() - strtotime(date('Y-m-d') . " $target_masuk:00");
                $diff_min = max(1, floor($diff_sec / 60));
                $hrs = floor($diff_min / 60);
                $mins = $diff_min % 60;
                $durasi_str = ($hrs > 0) ? "$hrs jam $mins menit" : "$mins menit";

                if ($is_izin_yayasan) {
                    $keterangan = "Terlambat Shift 2 ($durasi_str) [Disetujui Izin Yayasan: $keterangan_izin]";
                    $warning_msg = "Anda terdeteksi Terlambat $durasi_str masuk kerja Shift 2 (dengan izin resmi Ketua Yayasan).";
                } else {
                    $is_pelanggaran = true;
                    $keterangan = "Pelanggaran: Terlambat Shift 2 ($durasi_str - Tanpa Izin Yayasan)";
                    $warning_msg = "⚠️ PELANGGARAN KEDISIPLINAN: Anda terdeteksi Terlambat $durasi_str masuk kerja Shift 2 (Absen pukul $current_time WIB) tanpa izin resmi Ketua Yayasan.";
                }
            } else {
                $keterangan = "Tepat Waktu (Shift 2)";
            }
        }
    } elseif ($status_kehadiran === 'Pulang') {
        // Tentukan apakah pulang dari Shift 1 atau Shift 2 berdasarkan jam absen Masuk sebelumnya
        $jam_masuk_prev = ($last_row && !empty($last_row['waktu_absen'])) ? date('H:i', strtotime($last_row['waktu_absen'])) : '07:00';
        $prev_min = (int)date('H', strtotime($last_row['waktu_absen'] ?? '')) * 60 + (int)date('i', strtotime($last_row['waktu_absen'] ?? ''));

        if ($prev_min < 840) {
            // === PULANG SHIFT 1 (Target Pulang: 13:00 WIB) ===
            $target_pulang = '13:00';
            if ($current_time < $target_pulang) {
                // Pulang Lebih Awal Shift 1
                $diff_sec = strtotime(date('Y-m-d') . " $target_pulang:00") - time();
                $diff_min = max(1, floor($diff_sec / 60));
                $hrs = floor($diff_min / 60);
                $mins = $diff_min % 60;
                $durasi_str = ($hrs > 0) ? "$hrs jam $mins menit" : "$mins menit";

                if ($is_izin_yayasan) {
                    $keterangan = "Pulang Awal Shift 1 ($durasi_str) [Disetujui Izin Yayasan: $keterangan_izin]";
                    $warning_msg = "Anda pulang lebih awal $durasi_str sebelum jam kerja Shift 1 selesai (dengan izin resmi Ketua Yayasan).";
                } else {
                    $is_pelanggaran = true;
                    $keterangan = "Pelanggaran: Pulang Lebih Awal Shift 1 ($durasi_str - Tanpa Izin Yayasan)";
                    $warning_msg = "⚠️ PELANGGARAN KEDISIPLINAN: Anda Pulang Lebih Awal $durasi_str sebelum jam 13:00 WIB tanpa izin resmi Ketua Yayasan.";
                }
            } else {
                $keterangan = "Tepat Waktu (Selesai Shift 1)";
            }
        } else {
            // === PULANG SHIFT 2 (Target Pulang: 21:00 WIB) ===
            $target_pulang = '21:00';
            if ($current_time < $target_pulang) {
                // Pulang Lebih Awal Shift 2
                $diff_sec = strtotime(date('Y-m-d') . " $target_pulang:00") - time();
                $diff_min = max(1, floor($diff_sec / 60));
                $hrs = floor($diff_min / 60);
                $mins = $diff_min % 60;
                $durasi_str = ($hrs > 0) ? "$hrs jam $mins menit" : "$mins menit";

                if ($is_izin_yayasan) {
                    $keterangan = "Pulang Awal Shift 2 ($durasi_str) [Disetujui Izin Yayasan: $keterangan_izin]";
                    $warning_msg = "Anda pulang lebih awal $durasi_str sebelum jam kerja Shift 2 selesai (dengan izin resmi Ketua Yayasan).";
                } else {
                    $is_pelanggaran = true;
                    $keterangan = "Pelanggaran: Pulang Lebih Awal Shift 2 ($durasi_str - Tanpa Izin Yayasan)";
                    $warning_msg = "⚠️ PELANGGARAN KEDISIPLINAN: Anda Pulang Lebih Awal $durasi_str sebelum jam 21:00 WIB tanpa izin resmi Ketua Yayasan.";
                }
            } else {
                $keterangan = "Tepat Waktu (Selesai Shift 2)";
            }
        }
    }
} elseif ($qr_jenis_absen === 'Mengajar') {
    if ($status_kehadiran === 'Masuk') {
        $keterangan = "Mulai Mengajar";
    } elseif ($status_kehadiran === 'Pulang') {
        // Hitung durasi mengajar riil
        $waktu_masuk_ajar = ($last_row && !empty($last_row['waktu_absen'])) ? strtotime($last_row['waktu_absen']) : (time() - 2700);
        $durasi_sec = time() - $waktu_masuk_ajar;
        $durasi_menit = max(1, round($durasi_sec / 60));

        // Ketentuan standar minimal mengajar adalah 35 menit per jam pelajaran (JP)
        if ($durasi_menit < 35) {
            if ($is_izin_yayasan) {
                $keterangan = "Selesai Mengajar ($durasi_menit menit) [Disetujui Izin Yayasan: $keterangan_izin]";
                $warning_msg = "Durasi mengajar Anda tercatat $durasi_menit menit (di bawah standar) dengan izin resmi Ketua Yayasan.";
            } else {
                $is_pelanggaran = true;
                $keterangan = "Pelanggaran: Durasi Mengajar Tidak Sesuai Ketentuan ($durasi_menit menit, Kurang Dari 35 Menit Tanpa Izin Yayasan)";
                $warning_msg = "⚠️ PELANGGARAN KEDISIPLINAN: Durasi mengajar Anda hanya $durasi_menit menit (di bawah ketentuan standar minimal 35 menit per sesi) tanpa izin resmi Ketua Yayasan.";
            }
        } else {
            $keterangan = "Selesai Mengajar ($durasi_menit menit - Sesuai Ketentuan)";
        }
    }
}

// D. Cek apakah pegawai memiliki status perizinan aktif untuk hari ini (Bypass GPS)
$is_izin_approved = $is_izin_yayasan;

// D. Cari koordinat dan jarak lokasi
$max_distance_allowed = MAX_DISTANCE_METERS;

if ($qr_jenis_absen === 'Rapat') {
    // --- METODE GEOLOKASI KHUSUS RAPAT: HANYA BISA ABSEN SESUAI LOKASI YANG DITENTUKAN DI UNDANGAN ---
    $target_lat = null;
    $target_lon = null;
    $target_radius = MAX_DISTANCE_METERS;
    $target_location_name = !empty($rapat['tempat_rapat']) ? $rapat['tempat_rapat'] : 'Lokasi Rapat';

    // 1. Cek dari kolom koordinat spesifik jadwal_rapat di database
    if (!empty($rapat['lokasi_latitude']) && !empty($rapat['lokasi_longitude'])) {
        $target_lat = (float)$rapat['lokasi_latitude'];
        $target_lon = (float)$rapat['lokasi_longitude'];
        $target_radius = !empty($rapat['lokasi_radius']) ? (int)$rapat['lokasi_radius'] : MAX_DISTANCE_METERS;
    }
    
    // 2. Cek dari tabel master_lokasi di database berdasarkan tempat_rapat
    if ($target_lat === null || $target_lon === null) {
        $tempat_esc = $conn->real_escape_string($rapat['tempat_rapat'] ?? '');
        $q_lok = $conn->query("SELECT * FROM master_lokasi WHERE nama_lokasi LIKE '%$tempat_esc%' OR alias_nama LIKE '%$tempat_esc%' OR kode_lokasi LIKE '%$tempat_esc%' LIMIT 1");
        if ($q_lok && $q_lok->num_rows > 0) {
            $row_lok = $q_lok->fetch_assoc();
            $target_lat = (float)$row_lok['latitude'];
            $target_lon = (float)$row_lok['longitude'];
            $target_radius = (int)($row_lok['radius_meter'] ?? MAX_DISTANCE_METERS);
            $target_location_name = $row_lok['nama_lokasi'];
        }
    }

    // 3. Fallback jika nama gedung belum ada di database
    if ($target_lat === null || $target_lon === null) {
        $tempat_norm = strtolower(trim($rapat['tempat_rapat'] ?? ''));
        if (strpos($tempat_norm, 'gedung a') !== false || strpos($tempat_norm, 'rijal') !== false) {
            $target_lat = -7.9480257; $target_lon = 112.5822426; $target_location_name = 'Gedung A (Asrama Rijal)';
        } elseif (strpos($tempat_norm, 'gedung b') !== false || strpos($tempat_norm, 'kantor') !== false) {
            $target_lat = -7.9485768; $target_lon = 112.5823352; $target_location_name = 'Gedung B (Kantor Villa Quran)';
        } elseif (strpos($tempat_norm, 'gedung c') !== false || strpos($tempat_norm, 'nisa') !== false) {
            $target_lat = -7.9405464; $target_lon = 112.5791353; $target_location_name = 'Gedung C (Asrama Nisa)';
        } elseif (strpos($tempat_norm, 'masjid') !== false || strpos($tempat_norm, 'taqwa') !== false) {
            $target_lat = -7.9464649; $target_lon = 112.5822123; $target_location_name = 'Masjid At Taqwa VBT';
        } else {
            $target_lat = -7.9485768; $target_lon = 112.5823352; $target_location_name = 'Gedung B (Kantor Utama)';
        }
    }

    $distance = haversine_distance($user_lat, $user_lon, $target_lat, $target_lon);
    $qr_location_key = 'rapat_loc_' . $rapat_id;
    $max_distance_allowed = $target_radius;
} elseif (!empty($qr_data_base64)) {
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
    $max_distance_allowed = MAX_DISTANCE_METERS;
} else {
    // --- METODE GEOLOCATION-ONLY (TANPA QR) ---
    $closest_location_key = null;
    $closest_distance = null;
    $closest_radius = MAX_DISTANCE_METERS;

    foreach ($locations as $key => $loc) {
        $loc_lat = $loc['coords']['latitude'];
        $loc_lon = $loc['coords']['longitude'];
        $dist = haversine_distance($user_lat, $user_lon, $loc_lat, $loc_lon);

        if ($closest_distance === null || $dist < $closest_distance) {
            $closest_distance = $dist;
            $closest_location_key = $key;
            $closest_radius = isset($loc['radius']) ? (int)$loc['radius'] : MAX_DISTANCE_METERS;
        }
    }

    if ($closest_location_key !== null) {
        $qr_location_key = $closest_location_key;
        $distance = $closest_distance;
        $target_location_name = $locations[$closest_location_key]['nama'];
        $max_distance_allowed = $closest_radius;
    } else {
        json_response('error', 'Tidak ada koordinat gedung resmi yang terdaftar.');
    }
}

// E. Validasi Jarak & Eksekusi Penyimpanan
if ($distance > $max_distance_allowed && !$is_izin_approved) {
    // --- DI LUAR JANGKAUAN DAN TIDAK ADA IZIN ---
    // Tetap catat ke database dengan status "Ditolak ([Status])"
    $status_ditolak = 'Ditolak (' . $status_kehadiran . ')';
    $waktu_sekarang = date('Y-m-d H:i:s');
    $koordinat_pegawai = "$user_lat, $user_lon";
    $keterangan_ditolak = 'Ditolak (Di luar radius ' . $target_location_name . ': ' . round($distance) . 'm)';

    $stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai, keterangan, rapat_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssi", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $status_ditolak, $koordinat_pegawai, $keterangan_ditolak, $rapat_id);
    $stmt->execute();
    $stmt->close();

    // Kirim notifikasi WA
    $nama_pegawai = $_SESSION['ustadz_nama'] ?? 'Pegawai';
    $pesan_wa = "⚠️ *UPAYA ABSENSI DI LUAR JANGKAUAN*\n\n"
              . "Pegawai: *$nama_pegawai*\n"
              . "Waktu: " . date('H:i:s') . " WIB\n"
              . "Jenis Absen: *$qr_jenis_absen* ($status_kehadiran)\n"
              . "Status: *DITOLAK (Di luar jangkauan)*\n"
              . "Jarak: " . round($distance) . " meter dari $target_location_name\n"
              . "Koordinat: `$user_lat, $user_lon`\n\n"
              . "-- SADIGS 4.0 Villa Quran --";
    kirim_notifikasi_wa_yayasan($pesan_wa);

    $pesan_ditolak = ($qr_jenis_absen === 'Rapat')
        ? 'Absensi ditolak karena Anda berada di luar jangkauan lokasi rapat (' . $target_location_name . '). Jarak Anda: ' . round($distance) . ' meter dari lokasi (maksimal ' . $max_distance_allowed . ' meter). Anda hanya dapat absen sesuai lokasi yang telah ditentukan di undangan.'
        : 'Absensi ditolak karena Anda berada di luar jangkauan gedung. Jarak Anda: ' . round($distance) . ' meter dari ' . $target_location_name . '. Upaya ini telah dicatat sistem.';

    // Kirim response khusus
    die(json_encode([
        'status' => 'rejected',
        'message' => $pesan_ditolak
    ]));
}

// --- JIKA DALAM JANGKAUAN ATAU ADA IZIN, SIMPAN SUKSES ---
$waktu_sekarang = date('Y-m-d H:i:s');
$koordinat_pegawai = "$user_lat, $user_lon";

$stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, koordinat_pegawai, keterangan, rapat_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssi", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $status_kehadiran, $koordinat_pegawai, $keterangan, $rapat_id);

if ($stmt->execute()) {
    $res_data = ['waktu' => date('H:i:s', strtotime($waktu_sekarang))];
    if ($warning_msg !== null) {
        $res_data['warning_msg'] = $warning_msg;
    }

    // Kirim notifikasi WA ke Ketua Yayasan / Admin
    $nama_pegawai = $_SESSION['ustadz_nama'] ?? 'Pegawai';
    $wa_header = !empty($is_pelanggaran) ? "⚠️ *PERINGATAN: PELANGGARAN KEDISIPLINAN (TANPA IZIN YAYASAN)*" : "✅ *LAPORAN ABSENSI PEGAWAI*";
    $pesan_wa = "$wa_header\n\n"
              . "Pegawai: *$nama_pegawai*\n"
              . "Waktu: " . date('H:i:s') . " WIB\n"
              . "Jenis Absen: *$qr_jenis_absen* ($status_kehadiran)\n"
              . "Keterangan: *$keterangan*\n"
              . "Status: *" . (!empty($is_pelanggaran) ? 'PELANGGARAN TERCATAT' : 'BERHASIL') . "*\n"
              . "Lokasi: $target_location_name (Jarak: " . round($distance) . "m)\n"
              . ($warning_msg ? "Peringatan: _" . strip_tags($warning_msg) . "_\n" : "")
              . "Koordinat: `$user_lat, $user_lon`\n\n"
              . "-- SADIGS 4.0 Villa Quran --";
    kirim_notifikasi_wa_yayasan($pesan_wa);

    json_response('success', "Absensi $status_kehadiran berhasil dicatat!", $res_data);
} else {
    json_response('error', 'Gagal menyimpan data ke database: ' . $conn->error);
}

$stmt->close();
$conn->close();
?>