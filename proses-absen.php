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
            'coords' => ['latitude' => (float) $dl['latitude'], 'longitude' => (float) $dl['longitude']],
            'radius' => (int) ($dl['radius_meter'] ?? MAX_DISTANCE_METERS)
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

// Self-healing: Tambahkan kolom status_kehadiran & shift jika belum ada
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN status_kehadiran VARCHAR(20) DEFAULT 'Masuk' AFTER jenis_absen");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN shift VARCHAR(20) DEFAULT 'Shift 1' AFTER status_kehadiran");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN keterangan VARCHAR(255) DEFAULT NULL AFTER koordinat_pegawai");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN durasi_kerja VARCHAR(50) DEFAULT NULL AFTER keterangan");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN jadwal_id INT NULL AFTER shift");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN mapel_id INT NULL AFTER jadwal_id");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN kelas_id INT NULL AFTER mapel_id");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN jam_ke INT NULL AFTER kelas_id");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN nama_mapel VARCHAR(150) NULL AFTER jam_ke");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN nama_kelas VARCHAR(100) NULL AFTER nama_mapel");

function json_response($status, $message, $data = [])
{
    die(json_encode(['status' => $status, 'message' => $message] + $data));
}

function haversine_distance($lat1, $lon1, $lat2, $lon2)
{
    $earth_radius = 6371000; // Radius bumi dalam meter
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earth_radius * $c;
}

function kirim_notifikasi_wa_yayasan($pesan, $target_custom = null)
{
    global $conn;
    require_once __DIR__ . '/includes/services/WaNotificationService.php';
    $target = !empty($target_custom) ? $target_custom : WaNotificationService::getAdminNumber($conn);
    return WaNotificationService::send($target, $pesan, $conn);
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
$user_lat = (float) ($_POST['user_lat'] ?? 0);
$user_lon = (float) ($_POST['user_lon'] ?? 0);
$gps_accuracy = isset($_POST['gps_accuracy']) ? (float) $_POST['gps_accuracy'] : null;
$is_mock_client = (int) ($_POST['is_mock'] ?? 0);
$req_jenis_absen = $_POST['jenis_absen'] ?? 'Harian';

// Parameter Jadwal Pelajaran (Sinkronisasi KBM Ustadz, Tutor, Trainer)
$req_jadwal_id = (int)($_POST['jadwal_id'] ?? 0);
$req_jam_ke = (int)($_POST['jam_ke'] ?? 0);
$req_mapel_id = (int)($_POST['mapel_id'] ?? 0);
$req_kelas_id = (int)($_POST['kelas_id'] ?? 0);
$req_nama_mapel = trim($_POST['nama_mapel'] ?? '');
$req_nama_kelas = trim($_POST['nama_kelas'] ?? '');

// Pengaturan Shift Kerja (Shift 1: 07.00 - 13.00, Shift 2: 15.00 - 21.00)
$req_shift_raw = trim($_POST['shift'] ?? '');
$current_h = (int) date('H');
$current_m = (int) date('i');
$current_min = ($current_h * 60) + $current_m;
if (strpos($req_shift_raw, '2') !== false) {
    $shift = 'Shift 2';
} elseif (strpos($req_shift_raw, '1') !== false) {
    $shift = 'Shift 1';
} else {
    $shift = ($current_min >= 840) ? 'Shift 2' : 'Shift 1'; // Jam 14.00 ke atas default Shift 2
}

// F. Validasi Hak Akses Role Khusus Harian/Pegawai & Mengajar
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
    // Hak Akses Mengajar: Wajib dan Terbuka untuk Ustadz, Tutor, Trainer, dan Super Admin
    $is_eligible = in_array('ustadz', $user_roles) || in_array('tutor', $user_roles) || in_array('trainer', $user_roles) || in_array('super_admin', $user_roles);
    if (!$is_eligible) {
        foreach ($user_roles as $ur) {
            $ur_clean = strtolower(trim($ur));
            if (strpos($ur_clean, 'guru') !== false || strpos($ur_clean, 'tutor') !== false || strpos($ur_clean, 'trainer') !== false || strpos($ur_clean, 'ustadz') !== false) {
                $is_eligible = true;
                break;
            }
        }
    }
    if (!$is_eligible) {
        json_response('error', 'Anda tidak memiliki hak akses untuk melakukan Absensi Mengajar.');
    }
}

// G. Validasi Hak Akses & Status Rapat untuk Absensi Rapat
$rapat_id = NULL;
if ($req_jenis_absen === 'Rapat') {
    $rapat_id = (int) ($_POST['rapat_id'] ?? 0);
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

        if (in_array((int) $ustadz_id, $t_ids)) {
            $is_invited = true;
        } elseif (in_array('semua_pegawai', $t_roles)) {
            $is_invited = true;
        } else {
            foreach ($t_roles as $tr) {
                if ($tr === 'sekretaris_yayasan' && in_array('sekretaris_yayasan', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'bendahara_yayasan' && in_array('bendahara_yayasan', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'staff_ldu' && in_array('staff_ldu', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'musyrif' && (in_array('musyrif', $user_roles) || in_array('kepala_asrama', $user_roles))) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'admin_sekolah' && (in_array('admin_sekolah', $user_roles) || in_array('sekretaris_sekolah', $user_roles) || in_array('bendahara_sekolah', $user_roles))) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'kepala_sekolah' && in_array('kepala_sekolah', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'kepala_mahad' && in_array('kepala_mahad', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'tutor' && in_array('tutor', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'ustadz' && in_array('ustadz', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'trainer' && in_array('trainer', $user_roles)) {
                    $is_invited = true;
                    break;
                }
                if ($tr === 'ustadz_diknas' && in_array('ustadz', $user_roles)) {
                    $check_diknas = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diknas' LIMIT 1");
                    if ($check_diknas && $check_diknas->num_rows > 0) {
                        $is_invited = true;
                        break;
                    }
                }
                if ($tr === 'ustadz_diniyah' && in_array('ustadz', $user_roles)) {
                    $check_diniyah = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diniyah' LIMIT 1");
                    if ($check_diniyah && $check_diniyah->num_rows > 0) {
                        $is_invited = true;
                        break;
                    }
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
} elseif ($qr_jenis_absen === 'Pegawai' || $qr_jenis_absen === 'Harian') {
    // Absensi Harian Pegawai Tetap: Dibuat 2 kali check log (Awal: Masuk, Akhir: Pulang) per Shift
    $res_check = $conn->query("SELECT id, waktu_absen, status_kehadiran, shift FROM absensi_pegawai 
        WHERE ustadz_id = $ustadz_id 
        AND DATE(waktu_absen) = '$today' 
        AND jenis_absen = 'Pegawai' 
        AND shift = '$shift' 
        AND status_kehadiran IN ('Masuk', 'Pulang') 
        ORDER BY waktu_absen ASC");

    $status_kehadiran = 'Masuk';
    $num_logs = $res_check ? $res_check->num_rows : 0;

    if ($num_logs == 0) {
        $status_kehadiran = 'Masuk';
    } elseif ($num_logs == 1) {
        $first_log = $res_check->fetch_assoc();
        if ($first_log['status_kehadiran'] === 'Masuk') {
            $waktu_terakhir = strtotime($first_log['waktu_absen']);
            if (time() - $waktu_terakhir < 180) { // 3 menit jeda
                json_response('error', 'Anda baru saja absen Masuk. Harap tunggu minimal 3 menit sebelum absen Pulang.');
            }
            $status_kehadiran = 'Pulang';
            $last_row = $first_log;
        } else {
            $status_kehadiran = 'Masuk';
        }
    } else {
        json_response('error', "Anda sudah menyelesaikan 2 kali check log (Masuk dan Pulang) untuk $shift hari ini.");
    }
} else {
    // Absensi Mengajar (Sinkronisasi Presisi dengan Jadwal Pelajaran / Slot Jam Ke)
    $where_kbm = "1=1";
    if ($req_jadwal_id > 0) {
        $where_kbm = "jadwal_id = $req_jadwal_id";
    } elseif ($req_jam_ke > 0) {
        $where_kbm = "jam_ke = $req_jam_ke";
    }

    $res_check = $conn->query("SELECT id, waktu_absen, status_kehadiran FROM absensi_pegawai 
        WHERE ustadz_id = $ustadz_id 
        AND DATE(waktu_absen) = '$today' 
        AND jenis_absen = 'Mengajar' 
        AND $where_kbm 
        AND status_kehadiran IN ('Masuk', 'Pulang') 
        ORDER BY waktu_absen ASC");
    
    $status_kehadiran = 'Masuk';
    $num_logs_kbm = $res_check ? $res_check->num_rows : 0;
    
    if ($num_logs_kbm == 0) {
        $status_kehadiran = 'Masuk';
    } elseif ($num_logs_kbm == 1) {
        $last_row = $res_check->fetch_assoc();
        if ($last_row['status_kehadiran'] === 'Masuk') {
            $waktu_terakhir = strtotime($last_row['waktu_absen']);
            if (time() - $waktu_terakhir < 180) { // 3 menit jeda
                json_response('error', 'Anda baru saja absen Masuk Mengajar. Harap tunggu minimal 3 menit sebelum menyelesaikan sesi mengajar.');
            }
            $status_kehadiran = 'Pulang';
        } else {
            $status_kehadiran = 'Masuk';
        }
    } else {
        json_response('error', "Anda sudah menyelesaikan 2 kali check log (Mulai dan Selesai) untuk sesi jadwal mengajar ini hari ini.");
    }
}

// B. Pengecekan Izin Resmi dari Ketua Yayasan
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
$keterangan = NULL;
$warning_msg = null;
$is_pelanggaran = false;
$pesan_evaluasi = "";
$is_terlambat = false;
$is_pulang_awal = false;
$selisih_menit = 0;

// Cari profil pegawai & panggilan sopan (Ustadz / Ustadzah)
$nama_pegawai = $_SESSION['ustadz_nama'] ?? 'Pegawai';
$no_wa_pegawai = '';
$panggilan_pegawai = 'Ustadz';

$q_prof = $conn->query("SELECT nama, whatsapp, role FROM akun_ustadz WHERE id = $ustadz_id LIMIT 1");
if ($q_prof && $r_prof = $q_prof->fetch_assoc()) {
    if (!empty($r_prof['nama'])) {
        $nama_pegawai = $r_prof['nama'];
    }
    $no_wa_pegawai = $r_prof['whatsapp'] ?? '';
    $r_role = strtolower($r_prof['role'] ?? '');
    if (strpos($r_role, 'musyrifah') !== false || strpos($r_role, 'nisa') !== false || stripos($nama_pegawai, 'ustadzah') !== false) {
        $panggilan_pegawai = 'Ustadzah';
    }
}
if (empty($no_wa_pegawai)) {
    $res_col = $conn->query("SHOW COLUMNS FROM akun_ustadz LIKE 'no_hp'");
    if ($res_col && $res_col->num_rows > 0) {
        $res_hp = $conn->query("SELECT no_hp FROM akun_ustadz WHERE id = $ustadz_id LIMIT 1");
        if ($res_hp && $r_hp = $res_hp->fetch_assoc()) {
            $no_wa_pegawai = $r_hp['no_hp'] ?? '';
        }
    }
}
if (empty($no_wa_pegawai)) {
    $q_usr = $conn->query("SELECT no_wa FROM app_users WHERE ref_id = $ustadz_id LIMIT 1");
    if ($q_usr && $r_usr = $q_usr->fetch_assoc()) {
        $no_wa_pegawai = $r_usr['no_wa'] ?? '';
    }
}

if ($qr_jenis_absen === 'Harian' || $qr_jenis_absen === 'Pegawai') {
    $current_time = date('H:i');

    // Ketentuan Jam Kerja:
    // Shift 1: 07:00 s.d. 13:00 (6 jam)
    // Shift 2: 15:00 s.d. 21:00 (6 jam)
    $target_masuk = ($shift === 'Shift 2') ? '15:00' : '07:00';
    $target_pulang = ($shift === 'Shift 2') ? '21:00' : '13:00';

    if ($status_kehadiran === 'Masuk') {
        $target_ts = strtotime(date('Y-m-d') . " $target_masuk:00");
        if (time() <= $target_ts) {
            // Tepat waktu (sebelum atau tepat jam kerja)
            $keterangan = "Tepat Waktu ($shift)";
            $is_terlambat = false;
            $selisih_menit = 0;
            $pesan_evaluasi = "Terimakasih $panggilan_pegawai $nama_pegawai sudah melakukan absensi masuk kerja dengan baik hari ini, Barokallah.";
        } else {
            // Terlambat
            $diff_sec = time() - $target_ts;
            $selisih_menit = max(1, floor($diff_sec / 60));
            $is_terlambat = true;
            $keterangan = "Terlambat $shift ($selisih_menit menit)";
            $pesan_evaluasi = "Catatan: Hari ini $panggilan_pegawai $nama_pegawai terlambat $selisih_menit menit dari jam masuk ($target_masuk WIB).";

            if ($is_izin_yayasan) {
                $keterangan .= " [Izin Yayasan: $keterangan_izin]";
                $warning_msg = "Anda terdeteksi terlambat $selisih_menit menit masuk kerja $shift (dengan izin resmi Ketua Yayasan: $keterangan_izin).";
            } else {
                $is_pelanggaran = true;
                $warning_msg = "⚠️ Catatan: Anda terdeteksi terlambat $selisih_menit menit masuk kerja $shift (Jadwal masuk: $target_masuk WIB).";
            }
        }
    } elseif ($status_kehadiran === 'Pulang') {
        $target_ts = strtotime(date('Y-m-d') . " $target_pulang:00");
        if (time() >= $target_ts) {
            // Tepat waktu (setelah atau tepat jam kepulangan)
            $keterangan = "Tepat Waktu (Selesai $shift)";
            $is_pulang_awal = false;
            $selisih_menit = 0;
            $pesan_evaluasi = "Terimakasih $panggilan_pegawai $nama_pegawai sudah melakukan absensi pulang kerja dengan baik hari ini, Barokallah.";
        } else {
            // Pulang lebih awal
            $diff_sec = $target_ts - time();
            $selisih_menit = max(1, floor($diff_sec / 60));
            $is_pulang_awal = true;
            $keterangan = "Pulang Awal $shift ($selisih_menit menit)";
            $pesan_evaluasi = "Catatan: Hari ini $panggilan_pegawai $nama_pegawai pulang awal $selisih_menit menit dari jam pulang ($target_pulang WIB).";

            if ($is_izin_yayasan) {
                $keterangan .= " [Izin Yayasan: $keterangan_izin]";
                $warning_msg = "Anda pulang lebih awal $selisih_menit menit sebelum jam kepulangan $shift (dengan izin resmi Ketua Yayasan: $keterangan_izin).";
            } else {
                $is_pelanggaran = true;
                $warning_msg = "⚠️ Catatan: Anda pulang awal $selisih_menit menit sebelum jam kepulangan $shift ($target_pulang WIB).";
            }
        }
    }
} elseif ($qr_jenis_absen === 'Mengajar') {
    // Lookup detail jadwal jika jadwal_id dikirimkan dan data nama_mapel/nama_kelas belum lengkap
    if ($req_jadwal_id > 0 && (empty($req_nama_mapel) || empty($req_nama_kelas) || $req_jam_ke == 0)) {
        $q_jdw = $conn->query("SELECT j.*, m.nama_mapel, k.nama_kelas 
            FROM jadwal_pelajaran j 
            LEFT JOIN master_mapel m ON j.mapel_id = m.id 
            LEFT JOIN master_kelas k ON j.kelas_id = k.id 
            WHERE j.id = $req_jadwal_id LIMIT 1");
        if ($q_jdw && $r_jdw = $q_jdw->fetch_assoc()) {
            $req_jam_ke = (int)$r_jdw['jam_ke'];
            $req_mapel_id = (int)$r_jdw['mapel_id'];
            $req_kelas_id = (int)$r_jdw['kelas_id'];
            $req_nama_mapel = $r_jdw['nama_mapel'] ?? $req_nama_mapel;
            $req_nama_kelas = $r_jdw['nama_kelas'] ?? $req_nama_kelas;
        }
    }

    $jam_slot_lookup = [
        1 => ['mulai' => '05:00', 'selesai' => '05:45'],
        2 => ['mulai' => '05:45', 'selesai' => '06:30'],
        3 => ['mulai' => '07:30', 'selesai' => '08:15'],
        4 => ['mulai' => '08:15', 'selesai' => '09:00'],
        5 => ['mulai' => '09:00', 'selesai' => '09:45'],
        6 => ['mulai' => '09:45', 'selesai' => '10:30'],
        7 => ['mulai' => '10:30', 'selesai' => '11:15'],
        8 => ['mulai' => '11:15', 'selesai' => '12:00'],
        9 => ['mulai' => '16:00', 'selesai' => '16:45'],
        10 => ['mulai' => '16:45', 'selesai' => '17:30'],
        12 => ['mulai' => '19:30', 'selesai' => '20:15'],
        13 => ['mulai' => '20:15', 'selesai' => '21:00'],
    ];

    $nama_hari_arr = ['Sunday' => 'Ahad', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
    $nama_bln_arr = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
    $hari_ini_indo = $nama_hari_arr[date('l')] ?? 'Senin';
    $tgl_indo = $hari_ini_indo . ', ' . date('d') . ' ' . ($nama_bln_arr[(int)date('m')] ?? date('F')) . ' ' . date('Y');

    $label_mapel_kelas = (!empty($req_nama_mapel) ? $req_nama_mapel : 'KBM');
    if (!empty($req_nama_kelas)) {
        $label_mapel_kelas .= " ($req_nama_kelas)";
    }

    if ($status_kehadiran === 'Masuk') {
        // Cek ketepatan waktu mulai jam pelajaran jika slot jam_ke terdaftar
        $slot_info = isset($jam_slot_lookup[$req_jam_ke]) ? $jam_slot_lookup[$req_jam_ke] : null;
        if ($slot_info) {
            $target_ts = strtotime(date('Y-m-d') . " " . $slot_info['mulai'] . ":00");
            if (time() <= $target_ts) {
                // Tepat waktu / hadir sebelum bel
                $keterangan = "Tepat Waktu Mulai KBM: $label_mapel_kelas";
                $pesan_evaluasi = "Alhamdulillah hari ini tanggal $tgl_indo pukul " . date('H:i') . " WIB, $panggilan_pegawai $nama_pegawai telah melakukan absensi Mulai Mengajar tepat waktu pada mata pelajaran $label_mapel_kelas. Mohon mengajar dengan optimal dan tuntas.";
            } else {
                // Terlambat mulai jam mengajar
                $diff_sec = time() - $target_ts;
                $selisih_menit = max(1, floor($diff_sec / 60));
                $is_terlambat = true;
                $keterangan = "Terlambat Mulai KBM ($selisih_menit menit): $label_mapel_kelas";
                $pesan_evaluasi = "Catatan: Hari ini tanggal $tgl_indo, $panggilan_pegawai $nama_pegawai terlambat $selisih_menit menit memulai KBM ($label_mapel_kelas) dari jadwal masuk (" . $slot_info['mulai'] . " WIB).";
                
                if ($is_izin_yayasan) {
                    $keterangan .= " [Izin Yayasan: $keterangan_izin]";
                    $warning_msg = "Anda terdeteksi terlambat $selisih_menit menit memulai KBM (dengan izin resmi Ketua Yayasan: $keterangan_izin).";
                } else {
                    $is_pelanggaran = true;
                    $warning_msg = "⚠️ Catatan: Terlambat $selisih_menit menit memulai KBM ($label_mapel_kelas) dari jadwal (" . $slot_info['mulai'] . " WIB).";
                }
            }
        } else {
            $keterangan = "Mulai Mengajar: $label_mapel_kelas";
            $pesan_evaluasi = "Alhamdulillah hari ini tanggal $tgl_indo pukul " . date('H:i') . " WIB, $panggilan_pegawai $nama_pegawai telah melakukan absensi Mulai Mengajar pada mata pelajaran $label_mapel_kelas.";
        }
    } elseif ($status_kehadiran === 'Pulang') {
        // Hitung durasi mengajar riil
        $waktu_masuk_ajar = ($last_row && !empty($last_row['waktu_absen'])) ? strtotime($last_row['waktu_absen']) : (time() - 5400);
        $durasi_sec = time() - $waktu_masuk_ajar;
        $durasi_menit = max(1, round($durasi_sec / 60));
        $durasi_str = "$durasi_menit menit";

        // Standar Alokasi Jam Mengajar adalah 90 Menit
        if ($durasi_menit >= 90) {
            $keterangan = "Selesai KBM: $label_mapel_kelas ($durasi_str - Tepat Waktu)";
            $pesan_evaluasi = "Alhamdulillah hari ini tanggal $tgl_indo $panggilan_pegawai $nama_pegawai telah mengajar tepat waktu selama $durasi_str pada mata pelajaran $label_mapel_kelas. Barokallahu fiikum.";
        } else {
            // Durasi kurang dari 90 menit: Diberikan peringatan halus dan tetap sopan sebagai barang bukti konfirmasi
            if ($is_izin_yayasan) {
                $keterangan = "Selesai KBM: $label_mapel_kelas ($durasi_str) [Disetujui Izin Yayasan: $keterangan_izin]";
                $pesan_evaluasi = "Maaf $panggilan_pegawai $nama_pegawai, hari ini $panggilan_pegawai mengajar hanya $durasi_str (kurang dari standar alokasi 90 menit) pada mata pelajaran $label_mapel_kelas dengan izin resmi Ketua Yayasan ($keterangan_izin).";
                $warning_msg = "Durasi mengajar Anda tercatat $durasi_str (di bawah standar 90 menit) dengan izin resmi Ketua Yayasan.";
            } else {
                $is_pelanggaran = true;
                $keterangan = "Catatan: Durasi KBM Kurang Dari 90 Menit ($label_mapel_kelas, $durasi_str)";
                $pesan_evaluasi = "Maaf $panggilan_pegawai $nama_pegawai, hari ini $panggilan_pegawai mengajar hanya $durasi_str (kurang dari standar alokasi 90 menit) pada mata pelajaran $label_mapel_kelas. Mohon untuk selalu memastikan durasi KBM terlaksana secara optimal sesuai alokasi waktu yang ditentukan.";
                $warning_msg = "⚠️ Catatan Kedisiplinan: Durasi mengajar Anda tercatat $durasi_str (kurang dari standar 90 menit). Catatan ini disimpan sebagai barang bukti konfirmasi absensi.";
            }
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
        $target_lat = (float) $rapat['lokasi_latitude'];
        $target_lon = (float) $rapat['lokasi_longitude'];
        $target_radius = !empty($rapat['lokasi_radius']) ? (int) $rapat['lokasi_radius'] : MAX_DISTANCE_METERS;
    }

    // 2. Cek dari tabel master_lokasi di database berdasarkan tempat_rapat
    if ($target_lat === null || $target_lon === null) {
        $tempat_esc = $conn->real_escape_string($rapat['tempat_rapat'] ?? '');
        $q_lok = $conn->query("SELECT * FROM master_lokasi WHERE nama_lokasi LIKE '%$tempat_esc%' OR alias_nama LIKE '%$tempat_esc%' OR kode_lokasi LIKE '%$tempat_esc%' LIMIT 1");
        if ($q_lok && $q_lok->num_rows > 0) {
            $row_lok = $q_lok->fetch_assoc();
            $target_lat = (float) $row_lok['latitude'];
            $target_lon = (float) $row_lok['longitude'];
            $target_radius = (int) ($row_lok['radius_meter'] ?? MAX_DISTANCE_METERS);
            $target_location_name = $row_lok['nama_lokasi'];
        }
    }

    // 3. Fallback jika nama gedung belum ada di database
    if ($target_lat === null || $target_lon === null) {
        $tempat_norm = strtolower(trim($rapat['tempat_rapat'] ?? ''));
        if (strpos($tempat_norm, 'gedung a') !== false || strpos($tempat_norm, 'rijal') !== false) {
            $target_lat = -7.9480257;
            $target_lon = 112.5822426;
            $target_location_name = 'Gedung A (Asrama Rijal)';
        } elseif (strpos($tempat_norm, 'gedung b') !== false || strpos($tempat_norm, 'kantor') !== false) {
            $target_lat = -7.9485768;
            $target_lon = 112.5823352;
            $target_location_name = 'Gedung B (Kantor Villa Quran)';
        } elseif (strpos($tempat_norm, 'gedung c') !== false || strpos($tempat_norm, 'nisa') !== false) {
            $target_lat = -7.9405464;
            $target_lon = 112.5791353;
            $target_location_name = 'Gedung C (Asrama Nisa)';
        } elseif (strpos($tempat_norm, 'masjid') !== false || strpos($tempat_norm, 'taqwa') !== false) {
            $target_lat = -7.9464649;
            $target_lon = 112.5822123;
            $target_location_name = 'Masjid At Taqwa VBT';
        } else {
            $target_lat = -7.9485768;
            $target_lon = 112.5823352;
            $target_location_name = 'Gedung B (Kantor Utama)';
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
            $closest_radius = isset($loc['radius']) ? (int) $loc['radius'] : MAX_DISTANCE_METERS;
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
$durasi_str_final = ($status_kehadiran === 'Pulang' && !empty($durasi_str)) ? $durasi_str : null;
$durasi_null = null;

if ($distance > $max_distance_allowed && !$is_izin_approved) {
    // --- DI LUAR JANGKAUAN DAN TIDAK ADA IZIN ---
    // Tetap catat ke database dengan status "Ditolak ([Status])"
    $status_ditolak = 'Ditolak (' . $status_kehadiran . ')';
    $waktu_sekarang = date('Y-m-d H:i:s');
    $koordinat_pegawai = "$user_lat, $user_lon";
    $keterangan_ditolak = 'Ditolak (Di luar radius ' . $target_location_name . ': ' . round($distance) . 'm)';

    $stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, shift, status_kehadiran, koordinat_pegawai, keterangan, durasi_kerja, rapat_id, jadwal_id, mapel_id, kelas_id, jam_ke, nama_mapel, nama_kelas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssiiiiiss", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $shift, $status_ditolak, $koordinat_pegawai, $keterangan_ditolak, $durasi_null, $rapat_id, $req_jadwal_id, $req_mapel_id, $req_kelas_id, $req_jam_ke, $req_nama_mapel, $req_nama_kelas);
    $stmt->execute();
    $stmt->close();

    // Kirim notifikasi WA penolakan ke Pegawai dan Ketua Yayasan
    $pesan_wa = "⚠️ *UPAYA ABSENSI DI LUAR JANGKAUAN*\n\n"
        . "Pegawai: *$nama_pegawai*\n"
        . "Waktu: " . date('H:i:s') . " WIB\n"
        . "Jenis Absen: *$qr_jenis_absen* ($status_kehadiran - $shift)\n"
        . "Status: *DITOLAK (Di luar jangkauan)*\n"
        . "Jarak: " . round($distance) . " meter dari $target_location_name\n"
        . "Koordinat: `$user_lat, $user_lon`\n\n"
        . "-- SADIGS 4.0 Villa Quran --";
    kirim_notifikasi_wa_yayasan($pesan_wa);
    if (!empty($no_wa_pegawai)) {
        $clean_wa_p = WaNotificationService::formatNumber($no_wa_pegawai);
        if (!empty($clean_wa_p)) {
            WaNotificationService::send($clean_wa_p, $pesan_wa, $conn);
        }
    }

    $pesan_ditolak = ($qr_jenis_absen === 'Rapat')
        ? 'Absensi ditolak karena Anda berada di luar jangkauan lokasi rapat (' . $target_location_name . '). Jarak Anda: ' . round($distance) . ' meter dari lokasi (maksimal ' . $max_distance_allowed . ' meter). Anda hanya dapat absen sesuai lokasi yang telah ditentukan di undangan.'
        : 'Absensi ditolak karena Anda berada di luar jangkauan gedung. Jarak Anda: ' . round($distance) . ' meter dari ' . $target_location_name . '. Pastikan Anda berada di Gedung A, B, C, atau Masjid.';

    // Kirim response khusus
    die(json_encode([
        'status' => 'rejected',
        'message' => $pesan_ditolak
    ]));
}

// --- JIKA DALAM JANGKAUAN ATAU ADA IZIN, SIMPAN SUKSES ---
$waktu_sekarang = date('Y-m-d H:i:s');
$koordinat_pegawai = "$user_lat, $user_lon";

$stmt = $conn->prepare("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, shift, status_kehadiran, koordinat_pegawai, keterangan, durasi_kerja, rapat_id, jadwal_id, mapel_id, kelas_id, jam_ke, nama_mapel, nama_kelas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssssssiiiiiss", $ustadz_id, $waktu_sekarang, $qr_jenis_absen, $shift, $status_kehadiran, $koordinat_pegawai, $keterangan, $durasi_str_final, $rapat_id, $req_jadwal_id, $req_mapel_id, $req_kelas_id, $req_jam_ke, $req_nama_mapel, $req_nama_kelas);

if ($stmt->execute()) {
    $res_data = [
        'waktu' => date('H:i:s', strtotime($waktu_sekarang)),
        'shift' => $shift,
        'status_kehadiran' => $status_kehadiran,
        'jenis_absen' => $qr_jenis_absen,
        'nama_mapel' => $req_nama_mapel,
        'nama_kelas' => $req_nama_kelas,
        'jam_ke' => $req_jam_ke,
        'durasi_kerja' => $durasi_str_final
    ];
    if ($warning_msg !== null) {
        $res_data['warning_msg'] = $warning_msg;
    }

    // Susun Format Pesan WhatsApp Sesuai Ketentuan Yayasan
    if ($qr_jenis_absen === 'Pegawai' || $qr_jenis_absen === 'Harian') {
        $shift_jam_label = ($shift === 'Shift 2') ? '15.00 - 21.00 WIB' : '07.00 - 13.00 WIB';

        $pesan_wa = "📢 *SADIGS - ABSENSI PEGAWAI*\n"
            . "🕌 *Villa Quran Baron Malang*\n"
            . "━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👤 Pegawai: *$nama_pegawai*\n"
            . "📋 Log Absensi: *Absensi $status_kehadiran ($shift)*\n"
            . "⏰ Waktu: *" . date('H:i') . " WIB*\n"
            . "📍 Lokasi: *$target_location_name*\n\n"
            . "💬 *Catatan / Apresiasi:*\n"
            . "$pesan_evaluasi\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "Jadwal Kerja: $shift ($shift_jam_label) • 6 Jam Kerja\n"
            . "-- Sistem Absensi Otomatis SADIGS 4.0 --";
    } elseif ($qr_jenis_absen === 'Mengajar') {
        $info_slot = ($req_jam_ke > 0) ? "Jam Ke-$req_jam_ke" : "Sesi KBM";
        if (isset($jam_slot_lookup[$req_jam_ke])) {
            $info_slot .= " (" . $jam_slot_lookup[$req_jam_ke]['mulai'] . " - " . $jam_slot_lookup[$req_jam_ke]['selesai'] . " WIB)";
        }
        $log_action_title = ($status_kehadiran === 'Masuk') ? "Mulai Mengajar (Check-In)" : "Selesai Mengajar (Check-Out)";
        $jam_mulai_kbm = ($last_row && !empty($last_row['waktu_absen'])) ? date('H:i', strtotime($last_row['waktu_absen'])) . ' WIB' : date('H:i') . ' WIB';
        $jam_selesai_kbm = date('H:i') . ' WIB';

        $pesan_wa = "📑 *BUKTI KONFIRMASI ABSENSI KBM*\n"
                  . "🕌 *Villa Quran Baron Malang*\n"
                  . "━━━━━━━━━━━━━━━━━━━━\n\n"
                  . "👤 *Pengajar:* $panggilan_pegawai $nama_pegawai\n"
                  . "📅 *Hari / Tanggal:* $tgl_indo\n"
                  . "📚 *Mata Pelajaran:* " . (!empty($req_nama_mapel) ? $req_nama_mapel : 'Umum/Diniyyah') . "\n"
                  . "🏫 *Kelas / Rombel:* " . (!empty($req_nama_kelas) ? $req_nama_kelas : 'Reguler') . "\n"
                  . "🕒 *Sesi Pelajaran:* $info_slot\n"
                  . "📋 *Log Kehadiran:* $log_action_title\n"
                  . "⏰ *Waktu Log:* " . date('H:i:s') . " WIB\n"
                  . ($status_kehadiran === 'Pulang' ? "⏱ *Waktu Sesi:* $jam_mulai_kbm s/d $jam_selesai_kbm\n⏱ *Durasi Mengajar Riil:* *$durasi_str_final* (Standar Alokasi: 90 Menit)\n" : "")
                  . "📍 *Lokasi:* $target_location_name\n\n"
                  . "💬 *Catatan / Konfirmasi Kehadiran:*\n"
                  . "$pesan_evaluasi\n\n"
                  . "━━━━━━━━━━━━━━━━━━━━\n"
                  . "📌 _Pesan ini diterbitkan otomatis oleh sistem SADIGS 4.0 sebagai dokumen barang bukti konfirmasi absensi jam mengajar yang sah (arsip tembusan Ketua Yayasan)._";
    } else {
        $wa_header = !empty($is_pelanggaran) ? "⚠️ *PERINGATAN: PELANGGARAN KEDISIPLINAN (TANPA IZIN YAYASAN)*" : "✅ *LAPORAN ABSENSI $qr_jenis_absen*";
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
    }

    // 1. Kirim pesan WA ke Pegawai / Pengajar yang bersangkutan
    if (!empty($no_wa_pegawai)) {
        $clean_wa_pegawai = WaNotificationService::formatNumber($no_wa_pegawai);
        if (!empty($clean_wa_pegawai)) {
            WaNotificationService::send($clean_wa_pegawai, $pesan_wa, $conn);
        }
    }

    // 2. Kirim pesan WA ke Nomor WhatsApp Ketua Yayasan (Pesan identik sebagai tembusan forward barang bukti)
    $target_yayasan = WaNotificationService::getAdminNumber($conn);
    if (!empty($target_yayasan)) {
        $pesan_yayasan = ($qr_jenis_absen === 'Mengajar')
            ? "📋 *[TEMBUSAN BUKTI ARSIP KETUA YAYASAN]*\n" . $pesan_wa
            : $pesan_wa;
        WaNotificationService::send($target_yayasan, $pesan_yayasan, $conn);
    }

    $sukses_text = ($qr_jenis_absen === 'Mengajar')
        ? ($status_kehadiran === 'Masuk' ? "Absensi Mulai Mengajar ($label_mapel_kelas) berhasil dicatat!" : "Absensi Selesai Mengajar ($label_mapel_kelas - $durasi_str_final) berhasil dicatat!")
        : "Absensi $status_kehadiran ($shift) berhasil dicatat!";
    json_response('success', $sukses_text, $res_data);
} else {
    json_response('error', 'Gagal menyimpan data ke database: ' . $conn->error);
}

$stmt->close();
$conn->close();
?>