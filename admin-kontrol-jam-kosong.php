<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'kontrol_jam_kosong';
$ustadz_id = (int)($_SESSION['ustadz_id'] ?? 0);
$today = date('Y-m-d');
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}
$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

// Hak akses & wewenang spesifik
$is_super_admin = in_array('super_admin', $norm_roles) || (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999);
$is_yayasan = $is_super_admin || in_array('ketua_yayasan', $norm_roles) || in_array('sekretaris_yayasan', $norm_roles) || in_array('bendahara_yayasan', $norm_roles) || in_array('pengurus_yayasan', $norm_roles) || in_array('yayasan', $norm_roles) || in_array('yayasan2', $norm_roles);
$is_kepsek = $is_super_admin || in_array('kepala_sekolah', $norm_roles) || in_array('admin_sekolah', $norm_roles);
$is_kepala_mahad = $is_super_admin || in_array('kepala_mahad', $norm_roles) || in_array('kepala_asrama', $norm_roles) || in_array('kepala_asrama_rijal', $norm_roles) || in_array('kepala_asrama_nisa', $norm_roles);
$is_kepala_ldu = $is_super_admin || in_array('kepala_ldu', $norm_roles) || in_array('direktur_ldu', $norm_roles) || in_array('staff_ldu', $norm_roles);

// Cek apakah punya akses ke modul Rekap Ajar
if (!$is_super_admin && !$is_yayasan && !$is_kepsek && !$is_kepala_mahad && !$is_kepala_ldu) {
    die("Akses ditolak: Menu Rekap Ajar hanya dapat diakses oleh Kepala Sekolah, Kepala Ma'had, Kepala LDU, dan Pengurus Yayasan.");
}

// Database self-healing
$conn->query("CREATE TABLE IF NOT EXISTS validasi_rekap_ajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode VARCHAR(7) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    status_validasi ENUM('draft', 'validated') DEFAULT 'draft',
    validator_id INT NULL,
    validator_role VARCHAR(100) NULL,
    validator_nama VARCHAR(150) NULL,
    catatan_validasi TEXT NULL,
    total_jam_terjadwal INT DEFAULT 0,
    total_jam_terisi INT DEFAULT 0,
    total_guru_aktif INT DEFAULT 0,
    validated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_periode_kategori (periode, kategori)
)");

$conn->query("CREATE TABLE IF NOT EXISTS kontrol_jam_kosong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    mapel VARCHAR(100) NOT NULL,
    guru_utama_id INT NOT NULL,
    guru_pengganti_id INT NULL,
    status_kontrol ENUM('Perlu Pengganti', 'Terisi', 'Batal') DEFAULT 'Perlu Pengganti',
    catatan TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS master_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_mapel VARCHAR(50) DEFAULT NULL,
    nama_mapel VARCHAR(150) UNIQUE NOT NULL,
    kategori_mapel VARCHAR(50) DEFAULT 'Lainnya',
    metode_belajar ENUM('offline', 'online', 'ai_agentic') DEFAULT 'offline',
    status_aktif TINYINT(1) DEFAULT 1,
    pengampu_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS jadwal_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hari VARCHAR(20) NOT NULL,
    jam_ke INT NOT NULL,
    kelas_id INT NOT NULL,
    mapel_id INT NOT NULL,
    ustadz_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (hari, jam_ke, kelas_id)
)");

// Filter Periode
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$periode_key = sprintf('%04d-%02d', $filter_tahun, $filter_bulan);

$nama_bulan_indo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$pesan_sukses = '';
$pesan_error = '';

// Helper Nama Validator
$nama_user_aktif = $_SESSION['nama'] ?? ($_SESSION['nama_lengkap'] ?? 'Pimpinan');

// Handle Validasi & Aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'validasi_kategori') {
        $kat = strtolower(trim($_POST['kategori'] ?? ''));
        $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');
        $can_val = false;
        $val_role = '';

        if ($kat === 'diknas' && ($is_kepsek || $is_super_admin)) {
            $can_val = true;
            $val_role = 'Kepala Sekolah';
        } elseif ($kat === 'diniyah' && ($is_kepala_mahad || $is_super_admin)) {
            $can_val = true;
            $val_role = 'Kepala Ma\'had';
        } elseif ($kat === 'solopreneur' && ($is_kepala_ldu || $is_super_admin)) {
            $can_val = true;
            $val_role = 'Kepala LDU';
        }

        if ($can_val) {
            $v_nama = $conn->real_escape_string($nama_user_aktif);
            $v_role = $conn->real_escape_string($val_role);
            $total_jadwal = (int)($_POST['total_jadwal'] ?? 0);
            $total_terisi = (int)($_POST['total_terisi'] ?? 0);
            $total_guru = (int)($_POST['total_guru'] ?? 0);

            $sql_val = "INSERT INTO validasi_rekap_ajar (periode, kategori, status_validasi, validator_id, validator_role, validator_nama, catatan_validasi, total_jam_terjadwal, total_jam_terisi, total_guru_aktif, validated_at)
                        VALUES ('$periode_key', '$kat', 'validated', $ustadz_id, '$v_role', '$v_nama', '$catatan', $total_jadwal, $total_terisi, $total_guru, NOW())
                        ON DUPLICATE KEY UPDATE 
                        status_validasi='validated', validator_id=$ustadz_id, validator_role='$v_role', validator_nama='$v_nama', catatan_validasi='$catatan', total_jam_terjadwal=$total_jadwal, total_jam_terisi=$total_terisi, total_guru_aktif=$total_guru, validated_at=NOW()";
            if ($conn->query($sql_val)) {
                $pesan_sukses = "✅ Tab Rekap Mapel " . ucfirst($kat) . " berhasil divalidasi dan disahkan!";
            } else {
                $pesan_error = "Gagal memvalidasi: " . $conn->error;
            }
        } else {
            $pesan_error = "Akses ditolak: Anda tidak memiliki wewenang untuk memvalidasi pilar " . ucfirst($kat) . ".";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'buka_kunci_validasi') {
        $kat = strtolower(trim($_POST['kategori'] ?? ''));
        $can_unlock = false;

        if ($kat === 'diknas' && ($is_kepsek || $is_super_admin)) $can_unlock = true;
        elseif ($kat === 'diniyah' && ($is_kepala_mahad || $is_super_admin)) $can_unlock = true;
        elseif ($kat === 'solopreneur' && ($is_kepala_ldu || $is_super_admin)) $can_unlock = true;

        if ($can_unlock) {
            $conn->query("UPDATE validasi_rekap_ajar SET status_validasi = 'draft', catatan_validasi = CONCAT(COALESCE(catatan_validasi, ''), ' [Kunci dibuka untuk revisi]') WHERE periode = '$periode_key' AND kategori = '$kat'");
            $pesan_sukses = "Kunci validasi pilar " . ucfirst($kat) . " dibuka untuk revisi.";
        } else {
            $pesan_error = "Akses ditolak untuk membuka kunci validasi.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'tambah_jam_kosong') {
        if ($is_super_admin || $is_kepsek || $is_kepala_mahad || $is_yayasan) {
            $tanggal = $conn->real_escape_string($_POST['tanggal']);
            $kelas = $conn->real_escape_string($_POST['kelas']);
            $mapel = $conn->real_escape_string($_POST['mapel']);
            $guru_utama_id = (int)$_POST['guru_utama_id'];
            $guru_pengganti_id = !empty($_POST['guru_pengganti_id']) ? (int)$_POST['guru_pengganti_id'] : 'NULL';
            $status_kontrol = $guru_pengganti_id !== 'NULL' ? 'Terisi' : 'Perlu Pengganti';
            $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');

            $sql = "INSERT INTO kontrol_jam_kosong (tanggal, kelas, mapel, guru_utama_id, guru_pengganti_id, status_kontrol, catatan, created_by)
                    VALUES ('$tanggal', '$kelas', '$mapel', $guru_utama_id, $guru_pengganti_id, '$status_kontrol', '$catatan', $ustadz_id)";
            if ($conn->query($sql)) {
                $pesan_sukses = "Log jam kosong baru berhasil dicatat!";
            }
        }
    }
}

// Menentukan tab yang diizinkan untuk dibuka user
$allowed_tabs = [];
if ($is_yayasan || $is_super_admin) {
    $allowed_tabs = ['yayasan', 'diknas', 'diniyah', 'solopreneur', 'jam_kosong'];
} else {
    if ($is_kepsek) $allowed_tabs[] = 'diknas';
    if ($is_kepala_mahad) $allowed_tabs[] = 'diniyah';
    if ($is_kepala_ldu) $allowed_tabs[] = 'solopreneur';
    if ($is_kepsek || $is_kepala_mahad) $allowed_tabs[] = 'jam_kosong';
}

// Default tab yang aktif
$default_active_tab = $allowed_tabs[0] ?? 'diknas';
$current_tab = $_GET['tab'] ?? $default_active_tab;
if (!in_array($current_tab, $allowed_tabs)) {
    $current_tab = $default_active_tab;
}

// Load data status validasi periode ini
$status_validasi = [
    'diknas' => null,
    'diniyah' => null,
    'solopreneur' => null
];
$res_v = $conn->query("SELECT * FROM validasi_rekap_ajar WHERE periode = '$periode_key'");
if ($res_v) {
    while ($r = $res_v->fetch_assoc()) {
        $status_validasi[$r['kategori']] = $r;
    }
}

// Hitung hari dalam bulan untuk pengali jadwal pekanan
$total_hari_bulan = cal_days_in_month(CAL_GREGORIAN, $filter_bulan, $filter_tahun);
$hari_count = ['Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0, 'Sabtu' => 0, 'Ahad' => 0];
$map_day_en_id = ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Ahad'];
for ($d = 1; $d <= $total_hari_bulan; $d++) {
    $time = mktime(0, 0, 0, $filter_bulan, $d, $filter_tahun);
    $day_en = date('l', $time);
    if (isset($map_day_en_id[$day_en])) {
        $hari_count[$map_day_en_id[$day_en]]++;
    }
}

// Fetch all Guru / Ustadz
$asatidz_list = [];
$asatidz_map = [];
$res_ast = $conn->query("SELECT id, nama FROM akun_ustadz ORDER BY nama ASC");
if ($res_ast) {
    while ($row = $res_ast->fetch_assoc()) {
        $asatidz_list[] = $row;
        $asatidz_map[$row['id']] = $row['nama'];
    }
}

// Fetch Master Mapel Lookup (Khusus Mapel Offline)
$mapel_cat_map = [];
$res_mpl = $conn->query("SELECT id, nama_mapel, kategori_mapel, metode_belajar, pengampu_id FROM master_mapel WHERE status_aktif = 1 AND (metode_belajar = 'offline' OR (metode_belajar != 'ai_agentic' AND pengampu_id IS NOT NULL) OR metode_belajar IS NULL)");
if ($res_mpl) {
    while ($row = $res_mpl->fetch_assoc()) {
        $kat = trim($row['kategori_mapel'] ?? '');
        $kat_lower = strtolower($kat);
        $nm_lower = strtolower(trim($row['nama_mapel'] ?? ''));
        
        $norm_cat = 'diknas';
        if (strpos($kat_lower, 'diniyah') !== false || strpos($kat_lower, 'tahfidz') !== false || strpos($kat_lower, 'pesantren') !== false ||
            strpos($nm_lower, 'tahfidz') !== false || strpos($nm_lower, 'fiqih') !== false || strpos($nm_lower, 'hadits') !== false || strpos($nm_lower, 'arab') !== false || strpos($nm_lower, 'aqidah') !== false || strpos($nm_lower, 'tajwid') !== false || strpos($nm_lower, 'kitab') !== false) {
            $norm_cat = 'diniyah';
        } elseif (strpos($kat_lower, 'solo') !== false || strpos($kat_lower, 'bisnis') !== false || strpos($kat_lower, 'entrepreneur') !== false || strpos($kat_lower, 'vokasi') !== false || strpos($kat_lower, 'trainer') !== false ||
                  strpos($nm_lower, 'solo') !== false || strpos($nm_lower, 'bisnis') !== false || strpos($nm_lower, 'marketing') !== false || strpos($nm_lower, 'coding') !== false || strpos($nm_lower, 'desain') !== false) {
            $norm_cat = 'solopreneur';
        } elseif (strpos($kat_lower, 'diknas') !== false || strpos($kat_lower, 'pkbm') !== false || strpos($kat_lower, 'nasional') !== false || strpos($kat_lower, 'umum') !== false ||
                  strpos($nm_lower, 'matematika') !== false || strpos($nm_lower, 'indonesia') !== false || strpos($nm_lower, 'inggris') !== false || strpos($nm_lower, 'ipa') !== false || strpos($nm_lower, 'ips') !== false || strpos($nm_lower, 'biologi') !== false || strpos($nm_lower, 'fisika') !== false || strpos($nm_lower, 'kimia') !== false || strpos($nm_lower, 'sosiologi') !== false || strpos($nm_lower, 'geografi') !== false || strpos($nm_lower, 'ekonomi') !== false || strpos($nm_lower, 'sejarah') !== false || strpos($nm_lower, 'pkn') !== false || strpos($nm_lower, 'pancasila') !== false) {
            $norm_cat = 'diknas';
        }
        $mapel_cat_map[$row['id']] = $norm_cat;
        $mapel_cat_map[$row['nama_mapel']] = $norm_cat;
    }
}

// Inisialisasi Rekap Per Guru per 3 Pilar
$rekap_data = [
    'diknas' => [],
    'diniyah' => [],
    'solopreneur' => []
];

foreach (['diknas', 'diniyah', 'solopreneur'] as $k_pilar) {
    foreach ($asatidz_list as $ast) {
        $gid = (int)$ast['id'];
        $rekap_data[$k_pilar][$gid] = [
            'id' => $gid,
            'nama' => $ast['nama'],
            'mapel_diampu' => [],
            'pekan_jp' => 0,
            'bulan_jp_target' => 0,
            'jurnal_jp_terisi' => 0,
            'total_scan_absen' => 0,
            'jam_kosong' => 0,
            'jam_inval' => 0,
            'detail_jurnal' => [],
            'detail_jadwal' => []
        ];
    }
}

// 0. Tarik Guru Pengampu Resmi dari Master Mapel (master_mapel)
$sql_pengampu = "SELECT m.id, m.nama_mapel, m.kategori_mapel, m.pengampu_id, u.nama as nama_guru 
                 FROM master_mapel m 
                 JOIN akun_ustadz u ON m.pengampu_id = u.id 
                 WHERE m.status_aktif = 1 AND m.pengampu_id IS NOT NULL AND m.pengampu_id > 0";
$res_pmp = $conn->query($sql_pengampu);
if ($res_pmp) {
    while ($pm = $res_pmp->fetch_assoc()) {
        $gid = (int)$pm['pengampu_id'];
        $mpl_nama = $pm['nama_mapel'];
        $mpl_id = (int)$pm['id'];
        $pilar = $mapel_cat_map[$mpl_id] ?? ($mapel_cat_map[$mpl_nama] ?? 'diknas');
        if (!in_array($pilar, ['diknas', 'diniyah', 'solopreneur'])) $pilar = 'diknas';

        if (isset($rekap_data[$pilar][$gid])) {
            if (!in_array($mpl_nama, $rekap_data[$pilar][$gid]['mapel_diampu'])) {
                $rekap_data[$pilar][$gid]['mapel_diampu'][] = $mpl_nama;
            }
        }
    }
}

// 1. Tarik Data Jadwal Pelajaran (jadwal_pelajaran)
$sql_jadwal = "SELECT j.*, m.nama_mapel, m.kategori_mapel, m.metode_belajar, k.nama_kelas, u.nama as nama_guru
               FROM jadwal_pelajaran j
               LEFT JOIN master_mapel m ON j.mapel_id = m.id
               LEFT JOIN master_kelas k ON j.kelas_id = k.id
               LEFT JOIN akun_ustadz u ON j.ustadz_id = u.id
               WHERE j.ustadz_id IS NOT NULL AND j.ustadz_id > 0
               AND (m.metode_belajar IS NULL OR m.metode_belajar = 'offline' OR m.metode_belajar != 'ai_agentic')";
$res_j = $conn->query($sql_jadwal);
if ($res_j) {
    while ($j = $res_j->fetch_assoc()) {
        $gid = (int)($j['ustadz_id'] ?? 0);
        $hari = trim($j['hari']);
        $mpl_id = (int)$j['mapel_id'];
        $mpl_nama = $j['nama_mapel'] ?? 'Mapel';
        $multiplier = $hari_count[$hari] ?? 4;
        
        $pilar = $mapel_cat_map[$mpl_id] ?? ($mapel_cat_map[$mpl_nama] ?? 'diknas');
        if (!in_array($pilar, ['diknas', 'diniyah', 'solopreneur'])) $pilar = 'diknas';

        if (isset($rekap_data[$pilar][$gid])) {
            $rekap_data[$pilar][$gid]['pekan_jp']++;
            $rekap_data[$pilar][$gid]['bulan_jp_target'] += $multiplier;
            if (!in_array($mpl_nama, $rekap_data[$pilar][$gid]['mapel_diampu'])) {
                $rekap_data[$pilar][$gid]['mapel_diampu'][] = $mpl_nama;
            }
            $rekap_data[$pilar][$gid]['detail_jadwal'][] = [
                'hari' => $hari,
                'jam_ke' => $j['jam_ke'],
                'kelas' => $j['nama_kelas'] ?? 'Kelas',
                'mapel' => $mpl_nama
            ];
        }
    }
}

// 2. Tarik Data Jurnal Mengajar (jurnal_mengajar)
$sql_jurnal = "SELECT jm.*, u.nama as u_nama 
               FROM jurnal_mengajar jm
               LEFT JOIN akun_ustadz u ON jm.ustadz_id = u.id
               WHERE MONTH(jm.tanggal) = $filter_bulan AND YEAR(jm.tanggal) = $filter_tahun
               ORDER BY jm.tanggal DESC, jm.id DESC";
$res_jur = $conn->query($sql_jurnal);
if ($res_jur) {
    while ($jm = $res_jur->fetch_assoc()) {
        $gid = (int)($jm['ustadz_id'] ?? 0);
        $mpl = $jm['mata_pelajaran'];
        $pilar = $mapel_cat_map[$mpl] ?? 'diknas';
        if (!in_array($pilar, ['diknas', 'diniyah', 'solopreneur'])) $pilar = 'diknas';

        if (isset($rekap_data[$pilar][$gid])) {
            $rekap_data[$pilar][$gid]['jurnal_jp_terisi']++;
            if (!in_array($mpl, $rekap_data[$pilar][$gid]['mapel_diampu'])) {
                $rekap_data[$pilar][$gid]['mapel_diampu'][] = $mpl;
            }
            $rekap_data[$pilar][$gid]['detail_jurnal'][] = [
                'tanggal' => $jm['tanggal'],
                'kelas' => $jm['kelas'],
                'mapel' => $jm['mata_pelajaran'],
                'materi' => $jm['materi'],
                'absensi' => $jm['absensi']
            ];
        }
    }
}

// 3. Tarik Presensi Absensi Mengajar (absensi_pegawai)
$sql_absen = "SELECT ustadz_id, DATE(waktu_absen) as tgl, COUNT(*) as jml
              FROM absensi_pegawai 
              WHERE MONTH(waktu_absen) = $filter_bulan AND YEAR(waktu_absen) = $filter_tahun 
              AND jenis_absen = 'Mengajar' AND status_kehadiran IN ('Masuk', 'Hadir', 'Pulang')
              GROUP BY ustadz_id, DATE(waktu_absen)";
$res_abs = $conn->query($sql_absen);
if ($res_abs) {
    while ($ab = $res_abs->fetch_assoc()) {
        $gid = (int)$ab['ustadz_id'];
        foreach (['diknas', 'diniyah', 'solopreneur'] as $p) {
            if (isset($rekap_data[$p][$gid])) {
                $rekap_data[$p][$gid]['total_scan_absen']++;
            }
        }
    }
}

// 4. Tarik Log Jam Kosong & Inval
$sql_logs = "SELECT k.*, u1.nama as nama_guru_utama, u2.nama as nama_guru_pengganti, uc.nama as nama_creator
             FROM kontrol_jam_kosong k
             JOIN akun_ustadz u1 ON k.guru_utama_id = u1.id
             LEFT JOIN akun_ustadz u2 ON k.guru_pengganti_id = u2.id
             LEFT JOIN akun_ustadz uc ON k.created_by = uc.id
             WHERE MONTH(k.tanggal) = $filter_bulan AND YEAR(k.tanggal) = $filter_tahun
             ORDER BY k.tanggal DESC, k.created_at DESC";
$res_logs = $conn->query($sql_logs);
$logs_jam_kosong = ($res_logs) ? $res_logs->fetch_all(MYSQLI_ASSOC) : [];

foreach ($logs_jam_kosong as $l) {
    if ($l['status_kontrol'] !== 'Batal') {
        $u_id = (int)$l['guru_utama_id'];
        $p_id = (int)$l['guru_pengganti_id'];
        $mpl_name = $l['mapel'];
        $pilar = $mapel_cat_map[$mpl_name] ?? 'diknas';
        if (!in_array($pilar, ['diknas', 'diniyah', 'solopreneur'])) $pilar = 'diknas';

        if (isset($rekap_data[$pilar][$u_id])) {
            $rekap_data[$pilar][$u_id]['jam_kosong']++;
        }
        if ($p_id > 0 && isset($rekap_data[$pilar][$p_id]) && $l['status_kontrol'] === 'Terisi') {
            $rekap_data[$pilar][$p_id]['jam_inval']++;
        }
    }
}

// Ringkasan Statistik 3 Pilar
$stats_pilar = [];
foreach (['diknas', 'diniyah', 'solopreneur'] as $p) {
    $tot_jadwal = 0;
    $tot_jurnal = 0;
    $tot_kosong = 0;
    $guru_aktif = 0;

    foreach ($rekap_data[$p] as $gid => $g) {
        if ($g['bulan_jp_target'] > 0 || $g['jurnal_jp_terisi'] > 0 || !empty($g['mapel_diampu'])) {
            $guru_aktif++;
            $tot_jadwal += $g['bulan_jp_target'];
            $tot_jurnal += $g['jurnal_jp_terisi'];
            $tot_kosong += $g['jam_kosong'];
        }
    }

    $pct = ($tot_jadwal > 0) ? round(($tot_jurnal / $tot_jadwal) * 100, 1) : ($tot_jurnal > 0 ? 100 : 0);
    $stats_pilar[$p] = [
        'guru_aktif' => $guru_aktif,
        'tot_jadwal' => $tot_jadwal,
        'tot_jurnal' => $tot_jurnal,
        'tot_kosong' => $tot_kosong,
        'persentase' => $pct
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Ajar (3 Tab Validasi) - Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .glass-card { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(12px); }
        .tab-btn.active {
            color: #0f766e;
            border-bottom: 3px solid #0f766e;
            font-weight: 700;
        }
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen">
    <div class="flex flex-col md:flex-row min-h-screen">
        <!-- SIDEBAR -->
        <div class="w-full md:w-64 flex-shrink-0 no-print">
            <?php include 'sidebar-hr.php'; ?>
        </div>

        <!-- CONTENT -->
        <main class="flex-1 p-4 md:p-8 max-w-7xl mx-auto w-full">
            
            <!-- HEADER SECTION -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
                <div>
                    <div class="flex items-center gap-2 text-xs text-teal-700 font-bold uppercase tracking-wider mb-1">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Sistem Rekapitulasi & Validasi KBM Offline</span>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                        Rekap Ajar & Jam Kosong
                    </h1>
                    <p class="text-xs md:text-sm text-slate-500 mt-0.5">
                        Alur Terintegrasi: Jurnal KBM Guru ➔ Riwayat Mengajar ➔ Rekap 3 Tab ➔ Validasi Pimpinan (Kepsek, Kepala Ma'had, Kepala LDU) ➔ Laporan Yayasan
                    </p>
                </div>

                <!-- FILTER BULAN & TAHUN -->
                <form method="GET" class="flex items-center gap-2 bg-white p-2 rounded-2xl shadow-sm border border-slate-200 no-print">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
                    <div class="flex items-center gap-1.5 pl-2">
                        <i class="fas fa-calendar-alt text-teal-600 text-sm"></i>
                        <span class="text-xs font-bold text-slate-600 hidden sm:inline">Periode:</span>
                    </div>
                    <select name="bulan" class="text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <?php foreach ($nama_bulan_indo as $num => $nama): ?>
                            <option value="<?= $num ?>" <?= ($filter_bulan == $num) ? 'selected' : '' ?>><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" class="text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl px-2.5 py-1.5 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= ($filter_tahun == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1.5 rounded-xl text-xs font-bold transition shadow-sm">
                        Filter
                    </button>
                </form>
            </div>

            <!-- NOTIFIKASI SUKSES / ERROR -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl mb-6 shadow-sm flex items-center gap-2 text-xs font-semibold">
                    <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                    <span><?= $pesan_sukses ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-900 px-4 py-3 rounded-2xl mb-6 shadow-sm flex items-center gap-2 text-xs font-semibold">
                    <i class="fas fa-exclamation-triangle text-rose-600 text-base"></i>
                    <span><?= $pesan_error ?></span>
                </div>
            <?php endif; ?>

            <!-- KARTU STATUS VALIDASI 3 PILAR (UNTUK YAYASAN & EXECUTIVE OVERVIEW) -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                        <i class="fas fa-shield-halved text-teal-600"></i> Status Validasi Rekap Ajar Periode <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>
                    </h2>
                    <?php if ($is_yayasan): ?>
                        <span class="bg-teal-100 text-teal-800 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-teal-200">
                            Akses Pengurus Yayasan (Semua Tab Terbuka)
                        </span>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    
                    <!-- 1. STATUS TAB DIKNAS (KEPALA SEKOLAH) -->
                    <?php 
                    $v_diknas = $status_validasi['diknas'];
                    $is_diknas_valid = ($v_diknas && $v_diknas['status_validasi'] === 'validated');
                    ?>
                    <div class="bg-white rounded-2xl p-4 border transition hover:shadow-md <?= $is_diknas_valid ? 'border-blue-200 shadow-sm' : 'border-slate-200' ?>">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-extrabold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
                                Tab 1: Mapel Diknas
                            </span>
                            <?php if ($is_diknas_valid): ?>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <i class="fas fa-circle-check text-emerald-600"></i> Tervalidasi
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    <i class="fas fa-clock text-amber-600"></i> Menunggu Validasi
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm font-bold text-slate-800 mb-1">
                            Validasi: <span class="text-blue-900">Kepala Sekolah</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">
                            <?php if ($is_diknas_valid): ?>
                                Disahkan oleh <b><?= htmlspecialchars($v_diknas['validator_nama']) ?></b> pada <?= date('d M Y H:i', strtotime($v_diknas['validated_at'])) ?>
                            <?php else: ?>
                                Rekap jam mengajar Diknas belum divalidasi resmi.
                            <?php endif; ?>
                        </p>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-600">
                            <span>Realisasi KBM:</span>
                            <span class="text-blue-700 font-extrabold"><?= $stats_pilar['diknas']['tot_jurnal'] ?> / <?= $stats_pilar['diknas']['tot_jadwal'] ?> JP (<?= $stats_pilar['diknas']['persentase'] ?>%)</span>
                        </div>
                    </div>

                    <!-- 2. STATUS TAB DINIYAH (KEPALA MA'HAD) -->
                    <?php 
                    $v_diniyah = $status_validasi['diniyah'];
                    $is_diniyah_valid = ($v_diniyah && $v_diniyah['status_validasi'] === 'validated');
                    ?>
                    <div class="bg-white rounded-2xl p-4 border transition hover:shadow-md <?= $is_diniyah_valid ? 'border-emerald-200 shadow-sm' : 'border-slate-200' ?>">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-extrabold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                                Tab 2: Mapel Diniyah
                            </span>
                            <?php if ($is_diniyah_valid): ?>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <i class="fas fa-circle-check text-emerald-600"></i> Tervalidasi
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    <i class="fas fa-clock text-amber-600"></i> Menunggu Validasi
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm font-bold text-slate-800 mb-1">
                            Validasi: <span class="text-emerald-900">Kepala Ma'had</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">
                            <?php if ($is_diniyah_valid): ?>
                                Disahkan oleh <b><?= htmlspecialchars($v_diniyah['validator_nama']) ?></b> pada <?= date('d M Y H:i', strtotime($v_diniyah['validated_at'])) ?>
                            <?php else: ?>
                                Rekap jam mengajar Diniyah belum divalidasi resmi.
                            <?php endif; ?>
                        </p>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-600">
                            <span>Realisasi KBM:</span>
                            <span class="text-emerald-700 font-extrabold"><?= $stats_pilar['diniyah']['tot_jurnal'] ?> / <?= $stats_pilar['diniyah']['tot_jadwal'] ?> JP (<?= $stats_pilar['diniyah']['persentase'] ?>%)</span>
                        </div>
                    </div>

                    <!-- 3. STATUS TAB SOLOPRENEUR (KEPALA LDU) -->
                    <?php 
                    $v_solo = $status_validasi['solopreneur'];
                    $is_solo_valid = ($v_solo && $v_solo['status_validasi'] === 'validated');
                    ?>
                    <div class="bg-white rounded-2xl p-4 border transition hover:shadow-md <?= $is_solo_valid ? 'border-amber-200 shadow-sm' : 'border-slate-200' ?>">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-extrabold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                Tab 3: Mapel Solopreneur
                            </span>
                            <?php if ($is_solo_valid): ?>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    <i class="fas fa-circle-check text-emerald-600"></i> Tervalidasi
                                </span>
                            <?php else: ?>
                                <span class="flex items-center gap-1 text-[11px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                    <i class="fas fa-clock text-amber-600"></i> Menunggu Validasi
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-sm font-bold text-slate-800 mb-1">
                            Validasi: <span class="text-amber-900">Kepala LDU</span>
                        </div>
                        <p class="text-[11px] text-slate-500 mb-3">
                            <?php if ($is_solo_valid): ?>
                                Disahkan oleh <b><?= htmlspecialchars($v_solo['validator_nama']) ?></b> pada <?= date('d M Y H:i', strtotime($v_solo['validated_at'])) ?>
                            <?php else: ?>
                                Rekap jam mengajar Solopreneur belum divalidasi resmi.
                            <?php endif; ?>
                        </p>
                        <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-600">
                            <span>Realisasi KBM:</span>
                            <span class="text-amber-700 font-extrabold"><?= $stats_pilar['solopreneur']['tot_jurnal'] ?> / <?= $stats_pilar['solopreneur']['tot_jadwal'] ?> JP (<?= $stats_pilar['solopreneur']['persentase'] ?>%)</span>
                        </div>
                    </div>

                </div>
            </div>

            <!-- NAVIGATION TAB BAR -->
            <div class="bg-white rounded-2xl border border-slate-200 p-1.5 mb-6 shadow-sm no-print flex flex-wrap items-center gap-1">
                
                <?php if ($is_yayasan || $is_super_admin): ?>
                    <a href="?tab=yayasan&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" 
                       class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 <?= ($current_tab === 'yayasan') ? 'bg-teal-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fas fa-building-columns"></i>
                        <span>Ringkasan Lengkap Yayasan</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('diknas', $allowed_tabs)): ?>
                    <a href="?tab=diknas&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" 
                       class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 <?= ($current_tab === 'diknas') ? 'bg-blue-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Tab 1: Rekap Mapel Diknas (Kepala Sekolah)</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('diniyah', $allowed_tabs)): ?>
                    <a href="?tab=diniyah&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" 
                       class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 <?= ($current_tab === 'diniyah') ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fas fa-book-quran"></i>
                        <span>Tab 2: Rekap Mapel Diniyah (Kepala Ma'had)</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('solopreneur', $allowed_tabs)): ?>
                    <a href="?tab=solopreneur&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" 
                       class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 <?= ($current_tab === 'solopreneur') ? 'bg-amber-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fas fa-rocket"></i>
                        <span>Tab 3: Rekap Mapel Solopreneur (Kepala LDU)</span>
                    </a>
                <?php endif; ?>

                <?php if (in_array('jam_kosong', $allowed_tabs)): ?>
                    <a href="?tab=jam_kosong&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" 
                       class="px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 <?= ($current_tab === 'jam_kosong') ? 'bg-rose-600 text-white shadow-md' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <i class="fas fa-person-chalkboard"></i>
                        <span>Log Jam Kosong & Guru Inval</span>
                    </a>
                <?php endif; ?>

            </div>

            <!-- ======================================================== -->
            <!-- CONTENT PER TAB -->
            <!-- ======================================================== -->

            <!-- ---------------------------------------------------- -->
            <!-- TAB 1: REKAP MAPEL DIKNAS (KEPALA SEKOLAH) -->
            <!-- ---------------------------------------------------- -->
            <?php if ($current_tab === 'diknas'): ?>
                <div class="space-y-6">
                    <!-- Banner Validasi Tab Diknas -->
                    <div class="bg-white rounded-2xl border <?= $is_diknas_valid ? 'border-emerald-200 bg-emerald-50/40' : 'border-blue-200 bg-blue-50/30' ?> p-5 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-blue-600 text-white">
                                        Wewenang: Kepala Sekolah
                                    </span>
                                    <h3 class="text-base font-black text-slate-800">
                                        Validasi Rekapitulasi Mapel Diknas - <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>
                                    </h3>
                                </div>
                                <p class="text-xs text-slate-600">
                                    <?php if ($is_diknas_valid): ?>
                                        <span class="text-emerald-700 font-bold">✓ Data telah divalidasi resmi oleh <?= htmlspecialchars($v_diknas['validator_nama']) ?> (<?= htmlspecialchars($v_diknas['validator_role']) ?>)</span> pada <?= date('d M Y H:i', strtotime($v_diknas['validated_at'])) ?>. Catatan: "<?= htmlspecialchars($v_diknas['catatan_validasi'] ?: 'Tidak ada catatan khusus.') ?>"
                                    <?php else: ?>
                                        Kepala Sekolah memeriksa log jurnal mengajar pengajar Mapel Diknas offline dan menekan tombol validasi untuk mengesahkan laporan bulan ini.
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Tombol Aksi Validasi Diknas -->
                            <div class="flex items-center gap-2 no-print flex-shrink-0">
                                <?php if ($is_kepsek || $is_super_admin): ?>
                                    <?php if (!$is_diknas_valid): ?>
                                        <button onclick="bukaModalValidasi('diknas', 'Kepala Sekolah', <?= $stats_pilar['diknas']['tot_jadwal'] ?>, <?= $stats_pilar['diknas']['tot_jurnal'] ?>, <?= $stats_pilar['diknas']['guru_aktif'] ?>)" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                                            <i class="fas fa-check-double"></i> Validasi Rekap Diknas
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Buka kunci validasi untuk revisi?')">
                                            <input type="hidden" name="action" value="buka_kunci_validasi">
                                            <input type="hidden" name="kategori" value="diknas">
                                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-2 rounded-xl text-xs shadow transition flex items-center gap-1">
                                                <i class="fas fa-unlock"></i> Buka Kunci Revisi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs transition">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Guru Mapel Diknas -->
                    <?php render_table_rekap($rekap_data['diknas'], 'Diknas', 'blue', $filter_bulan, $filter_tahun); ?>
                </div>
            <?php endif; ?>

            <!-- ---------------------------------------------------- -->
            <!-- TAB 2: REKAP MAPEL DINIYAH (KEPALA MA'HAD) -->
            <!-- ---------------------------------------------------- -->
            <?php if ($current_tab === 'diniyah'): ?>
                <div class="space-y-6">
                    <!-- Banner Validasi Tab Diniyah -->
                    <div class="bg-white rounded-2xl border <?= $is_diniyah_valid ? 'border-emerald-200 bg-emerald-50/40' : 'border-emerald-200 bg-emerald-50/30' ?> p-5 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-emerald-600 text-white">
                                        Wewenang: Kepala Ma'had
                                    </span>
                                    <h3 class="text-base font-black text-slate-800">
                                        Validasi Rekapitulasi Mapel Diniyah - <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>
                                    </h3>
                                </div>
                                <p class="text-xs text-slate-600">
                                    <?php if ($is_diniyah_valid): ?>
                                        <span class="text-emerald-700 font-bold">✓ Data telah divalidasi resmi oleh <?= htmlspecialchars($v_diniyah['validator_nama']) ?> (<?= htmlspecialchars($v_diniyah['validator_role']) ?>)</span> pada <?= date('d M Y H:i', strtotime($v_diniyah['validated_at'])) ?>. Catatan: "<?= htmlspecialchars($v_diniyah['catatan_validasi'] ?: 'Tidak ada catatan khusus.') ?>"
                                    <?php else: ?>
                                        Kepala Ma'had memeriksa log jurnal mengajar pengampu Mapel Diniyah/Tahfidz offline dan menekan tombol validasi untuk mengesahkan laporan bulan ini.
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Tombol Aksi Validasi Diniyah -->
                            <div class="flex items-center gap-2 no-print flex-shrink-0">
                                <?php if ($is_kepala_mahad || $is_super_admin): ?>
                                    <?php if (!$is_diniyah_valid): ?>
                                        <button onclick="bukaModalValidasi('diniyah', 'Kepala Ma\'had', <?= $stats_pilar['diniyah']['tot_jadwal'] ?>, <?= $stats_pilar['diniyah']['tot_jurnal'] ?>, <?= $stats_pilar['diniyah']['guru_aktif'] ?>)" 
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                                            <i class="fas fa-check-double"></i> Validasi Rekap Diniyah
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Buka kunci validasi untuk revisi?')">
                                            <input type="hidden" name="action" value="buka_kunci_validasi">
                                            <input type="hidden" name="kategori" value="diniyah">
                                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-2 rounded-xl text-xs shadow transition flex items-center gap-1">
                                                <i class="fas fa-unlock"></i> Buka Kunci Revisi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs transition">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Guru Mapel Diniyah -->
                    <?php render_table_rekap($rekap_data['diniyah'], 'Diniyah', 'emerald', $filter_bulan, $filter_tahun); ?>
                </div>
            <?php endif; ?>

            <!-- ---------------------------------------------------- -->
            <!-- TAB 3: REKAP MAPEL SOLOPRENEUR (KEPALA LDU) -->
            <!-- ---------------------------------------------------- -->
            <?php if ($current_tab === 'solopreneur'): ?>
                <div class="space-y-6">
                    <!-- Banner Validasi Tab Solopreneur -->
                    <div class="bg-white rounded-2xl border <?= $is_solo_valid ? 'border-emerald-200 bg-emerald-50/40' : 'border-amber-200 bg-amber-50/30' ?> p-5 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-lg text-xs font-extrabold bg-amber-600 text-white">
                                        Wewenang: Kepala LDU
                                    </span>
                                    <h3 class="text-base font-black text-slate-800">
                                        Validasi Rekapitulasi Mapel Solopreneur - <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>
                                    </h3>
                                </div>
                                <p class="text-xs text-slate-600">
                                    <?php if ($is_solo_valid): ?>
                                        <span class="text-emerald-700 font-bold">✓ Data telah divalidasi resmi oleh <?= htmlspecialchars($v_solo['validator_nama']) ?> (<?= htmlspecialchars($v_solo['validator_role']) ?>)</span> pada <?= date('d M Y H:i', strtotime($v_solo['validated_at'])) ?>. Catatan: "<?= htmlspecialchars($v_solo['catatan_validasi'] ?: 'Tidak ada catatan khusus.') ?>"
                                    <?php else: ?>
                                        Kepala LDU memeriksa log jurnal mengajar trainer/tutor Solopreneur offline dan menekan tombol validasi untuk mengesahkan laporan bulan ini.
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Tombol Aksi Validasi Solopreneur -->
                            <div class="flex items-center gap-2 no-print flex-shrink-0">
                                <?php if ($is_kepala_ldu || $is_super_admin): ?>
                                    <?php if (!$is_solo_valid): ?>
                                        <button onclick="bukaModalValidasi('solopreneur', 'Kepala LDU', <?= $stats_pilar['solopreneur']['tot_jadwal'] ?>, <?= $stats_pilar['solopreneur']['tot_jurnal'] ?>, <?= $stats_pilar['solopreneur']['guru_aktif'] ?>)" 
                                                class="bg-amber-600 hover:bg-amber-700 text-white font-extrabold px-4 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-1.5">
                                            <i class="fas fa-check-double"></i> Validasi Rekap Solopreneur
                                        </button>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Buka kunci validasi untuk revisi?')">
                                            <input type="hidden" name="action" value="buka_kunci_validasi">
                                            <input type="hidden" name="kategori" value="solopreneur">
                                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-3 py-2 rounded-xl text-xs shadow transition flex items-center gap-1">
                                                <i class="fas fa-unlock"></i> Buka Kunci Revisi
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs transition">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Guru Mapel Solopreneur -->
                    <?php render_table_rekap($rekap_data['solopreneur'], 'Solopreneur', 'amber', $filter_bulan, $filter_tahun); ?>
                </div>
            <?php endif; ?>

            <!-- ---------------------------------------------------- -->
            <!-- TAB YAYASAN: RINGKASAN LENGKAP 3 PILAR & VALIDASI -->
            <!-- ---------------------------------------------------- -->
            <?php if ($current_tab === 'yayasan' && ($is_yayasan || $is_super_admin)): ?>
                <div class="space-y-8">
                    
                    <div class="bg-gradient-to-r from-teal-800 to-teal-950 rounded-3xl p-6 text-white shadow-xl relative overflow-hidden">
                        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <span class="bg-teal-500/30 text-teal-200 text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-wider border border-teal-400/30">
                                    Laporan Konsolidasi Yayasan
                                </span>
                                <h2 class="text-xl md:text-2xl font-black mt-2 tracking-tight">
                                    Rekapitulasi KBM Terpadu (3 Pilar Pembelajaran)
                                </h2>
                                <p class="text-xs text-teal-100/80 mt-1 max-w-2xl">
                                    Ringkasan monitoring jam ajar offline santri Villa Quran Indonesia periode <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?> yang siap dievaluasi Pengurus Yayasan.
                                </p>
                            </div>
                            <button onclick="window.print()" class="bg-white hover:bg-teal-50 text-teal-900 font-extrabold px-4 py-2.5 rounded-2xl text-xs transition shadow-lg flex items-center gap-2 self-start md:self-auto flex-shrink-0">
                                <i class="fas fa-file-pdf text-rose-600 text-sm"></i>
                                <span>Cetak Laporan Lengkap Yayasan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Ringkasan Tab 1 (Diknas) -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4 border-b pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-600"></span>
                                <h3 class="font-extrabold text-slate-800 text-sm">Pilar 1: Mapel Diknas & PKBM (Validasi Kepala Sekolah)</h3>
                            </div>
                            <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-lg">
                                <?= $is_diknas_valid ? '✓ Telah Divalidasi Kepsek' : '⏳ Menunggu Validasi Kepsek' ?>
                            </span>
                        </div>
                        <?php render_table_rekap($rekap_data['diknas'], 'Diknas', 'blue', $filter_bulan, $filter_tahun); ?>
                    </div>

                    <!-- Ringkasan Tab 2 (Diniyah) -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4 border-b pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                                <h3 class="font-extrabold text-slate-800 text-sm">Pilar 2: Mapel Diniyah & Tahfidz (Validasi Kepala Ma'had)</h3>
                            </div>
                            <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg">
                                <?= $is_diniyah_valid ? '✓ Telah Divalidasi Kepala Ma\'had' : '⏳ Menunggu Validasi Kepala Ma\'had' ?>
                            </span>
                        </div>
                        <?php render_table_rekap($rekap_data['diniyah'], 'Diniyah', 'emerald', $filter_bulan, $filter_tahun); ?>
                    </div>

                    <!-- Ringkasan Tab 3 (Solopreneur) -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4 border-b pb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-amber-600"></span>
                                <h3 class="font-extrabold text-slate-800 text-sm">Pilar 3: Mapel Solopreneur & Vokasi (Validasi Kepala LDU)</h3>
                            </div>
                            <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg">
                                <?= $is_solo_valid ? '✓ Telah Divalidasi Kepala LDU' : '⏳ Menunggu Validasi Kepala LDU' ?>
                            </span>
                        </div>
                        <?php render_table_rekap($rekap_data['solopreneur'], 'Solopreneur', 'amber', $filter_bulan, $filter_tahun); ?>
                    </div>

                </div>
            <?php endif; ?>

            <!-- ---------------------------------------------------- -->
            <!-- TAB JAM KOSONG & INVAL -->
            <!-- ---------------------------------------------------- -->
            <?php if ($current_tab === 'jam_kosong'): ?>
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6 border-b pb-4">
                            <div>
                                <h3 class="font-black text-slate-900 text-base flex items-center gap-2">
                                    <i class="fas fa-person-chalkboard text-rose-600"></i>
                                    <span>Log Guru Izin / Jam Kosong & Guru Pengganti (Inval)</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Mencatat guru yang berhalangan hadir dan asatidz pengganti yang mengisi jam tersebut pada periode <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>.
                                </p>
                            </div>
                            <button onclick="document.getElementById('modal-tambah-kosong').classList.remove('hidden')" 
                                    class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow flex items-center gap-1.5 self-start md:self-auto">
                                <i class="fas fa-plus"></i> Catat Jam Kosong
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-600">
                                        <th class="px-3 py-3 text-left font-bold">Tanggal</th>
                                        <th class="px-3 py-3 text-left font-bold">Kelas & Mapel</th>
                                        <th class="px-3 py-3 text-left font-bold">Guru Utama</th>
                                        <th class="px-3 py-3 text-left font-bold">Guru Pengganti (Inval)</th>
                                        <th class="px-3 py-3 text-center font-bold">Status</th>
                                        <th class="px-3 py-3 text-left font-bold">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (!empty($logs_jam_kosong)): ?>
                                        <?php foreach ($logs_jam_kosong as $l): ?>
                                            <tr class="hover:bg-slate-50 transition">
                                                <td class="px-3 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                                    <?= date('d M Y', strtotime($l['tanggal'])) ?>
                                                </td>
                                                <td class="px-3 py-3">
                                                    <span class="font-bold text-slate-800 block"><?= htmlspecialchars($l['kelas']) ?></span>
                                                    <span class="text-slate-500 text-[11px]"><?= htmlspecialchars($l['mapel']) ?></span>
                                                </td>
                                                <td class="px-3 py-3 text-rose-700 font-bold whitespace-nowrap">
                                                    <?= htmlspecialchars($l['nama_guru_utama']) ?>
                                                </td>
                                                <td class="px-3 py-3 font-bold whitespace-nowrap">
                                                    <?php if (!empty($l['nama_guru_pengganti'])): ?>
                                                        <span class="text-emerald-700 font-bold"><?= htmlspecialchars($l['nama_guru_pengganti']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-slate-400 italic">Belum ada pengganti</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= ($l['status_kontrol'] === 'Terisi') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' ?>">
                                                        <?= htmlspecialchars($l['status_kontrol']) ?>
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3 text-slate-500 max-w-xs truncate">
                                                    <?= htmlspecialchars($l['catatan'] ?: '-') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-slate-400 italic">
                                                Alhamdulillah, tidak ada catatan jam kosong pada bulan ini.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL FORM VALIDASI REKAP PILAR -->
    <!-- ======================================================== -->
    <div id="modal-validasi" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center font-bold">
                        <i class="fas fa-signature"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-slate-800 text-sm" id="modal-val-title">Pengesahan & Validasi Rekap</h3>
                        <p class="text-[10px] text-slate-500" id="modal-val-subtitle">Wewenang Pimpinan</p>
                    </div>
                </div>
                <button type="button" onclick="tutupModalValidasi()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" action="admin-kontrol-jam-kosong.php?tab=<?= htmlspecialchars($current_tab) ?>&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="space-y-4">
                <input type="hidden" name="action" value="validasi_kategori">
                <input type="hidden" name="kategori" id="form-val-kategori" value="">
                <input type="hidden" name="total_jadwal" id="form-val-jadwal" value="0">
                <input type="hidden" name="total_terisi" id="form-val-terisi" value="0">
                <input type="hidden" name="total_guru" id="form-val-guru" value="0">

                <div class="bg-slate-50 rounded-2xl p-3.5 border border-slate-200/80 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Periode:</span>
                        <span class="font-bold text-slate-800"><?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Validator:</span>
                        <span class="font-bold text-teal-700"><?= htmlspecialchars($nama_user_aktif) ?> (<span id="txt-val-role">Pimpinan</span>)</span>
                    </div>
                    <div class="flex justify-between border-t pt-1.5">
                        <span class="text-slate-500">Total KBM Terlaksana:</span>
                        <span class="font-extrabold text-slate-900" id="txt-val-ringkasan">0 JP</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Validasi (Opsional)</label>
                    <textarea name="catatan" rows="3" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="Tuliskan catatan evaluasi KBM atau instruksi tindak lanjut..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="tutupModalValidasi()" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-extrabold bg-teal-600 hover:bg-teal-700 text-white shadow-md transition flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i>
                        <span>Sahkan & Validasi</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL DETAIL JURNAL MENGAJAR GURU -->
    <!-- ======================================================== -->
    <div id="modal-detail-jurnal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[85vh] flex flex-col p-6 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4 flex-shrink-0">
                <div>
                    <h3 class="font-extrabold text-slate-900 text-base" id="modal-guru-nama">Rincian Jurnal KBM</h3>
                    <p class="text-xs text-slate-500" id="modal-guru-subtitle">Periode: <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?></p>
                </div>
                <button type="button" onclick="tutupModalJurnal()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 space-y-3 pr-1" id="modal-jurnal-content">
                <!-- Diisi via Javascript -->
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end flex-shrink-0 mt-4">
                <button type="button" onclick="tutupModalJurnal()" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL TAMBAH JAM KOSONG -->
    <!-- ======================================================== -->
    <div id="modal-tambah-kosong" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-extrabold text-slate-900 text-sm">Catat Jam Kosong / Izin</h3>
                <button type="button" onclick="document.getElementById('modal-tambah-kosong').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" action="admin-kontrol-jam-kosong.php?tab=jam_kosong&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="space-y-3 text-xs">
                <input type="hidden" name="action" value="tambah_jam_kosong">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Kelas</label>
                    <input type="text" name="kelas" placeholder="Contoh: Kelas 10 / Rijal" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Mata Pelajaran</label>
                    <input type="text" name="mapel" placeholder="Contoh: Matematika" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-rose-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Guru Utama (Yang Izin/Kosong)</label>
                    <select name="guru_utama_id" required class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="">-- Pilih Guru --</option>
                        <?php foreach ($asatidz_list as $ast): ?>
                            <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Guru Pengganti / Inval (Jika ada)</label>
                    <select name="guru_pengganti_id" class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-rose-500 bg-white">
                        <option value="">-- Belum Ada Pengganti --</option>
                        <?php foreach ($asatidz_list as $ast): ?>
                            <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Catatan / Keterangan</label>
                    <textarea name="catatan" rows="2" placeholder="Alasan izin / kendala..." class="w-full px-3 py-2 border rounded-xl focus:ring-2 focus:ring-rose-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('modal-tambah-kosong').classList.add('hidden')" class="px-4 py-2 rounded-xl font-bold bg-slate-100 hover:bg-slate-200 text-slate-700">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl font-extrabold bg-rose-600 hover:bg-rose-700 text-white shadow-md">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- JAVASCRIPT LOGIC -->
    <!-- ======================================================== -->
    <script>
        const allRekapData = <?= json_encode($rekap_data) ?>;

        function bukaModalValidasi(kategori, roleLabel, totalJadwal, totalTerisi, totalGuru) {
            document.getElementById('form-val-kategori').value = kategori;
            document.getElementById('form-val-jadwal').value = totalJadwal;
            document.getElementById('form-val-terisi').value = totalTerisi;
            document.getElementById('form-val-guru').value = totalGuru;

            document.getElementById('modal-val-title').innerText = `Validasi Rekap Mapel ${kategori.toUpperCase()}`;
            document.getElementById('modal-val-subtitle').innerText = `Wewenang: ${roleLabel}`;
            document.getElementById('txt-val-role').innerText = roleLabel;
            document.getElementById('txt-val-ringkasan').innerText = `${totalTerisi} JP Terisi dari ${totalJadwal} JP Target (${totalGuru} Pengampu)`;

            document.getElementById('modal-validasi').classList.remove('hidden');
        }

        function tutupModalValidasi() {
            document.getElementById('modal-validasi').classList.add('hidden');
        }

        function tampilkanDetailJurnal(pilar, guruId) {
            const dataPilar = allRekapData[pilar] || {};
            const guru = dataPilar[guruId];
            if (!guru) return;

            document.getElementById('modal-guru-nama').innerText = `Riwayat Mengajar: ${guru.nama}`;
            document.getElementById('modal-guru-subtitle').innerText = `Pilar ${pilar.toUpperCase()} • Mapel: ${guru.mapel_diampu.join(', ') || '-'}`;

            const container = document.getElementById('modal-jurnal-content');
            container.innerHTML = '';

            if (guru.detail_jurnal && guru.detail_jurnal.length > 0) {
                guru.detail_jurnal.forEach((j, idx) => {
                    const card = document.createElement('div');
                    card.className = 'bg-slate-50 border border-slate-200/80 rounded-2xl p-4 space-y-2 text-xs';
                    card.innerHTML = `
                        <div class="flex items-center justify-between border-b pb-2">
                            <span class="font-extrabold text-teal-800">${j.tanggal}</span>
                            <span class="px-2 py-0.5 rounded-md font-bold bg-white text-slate-700 border text-[11px]">${j.kelas} • ${j.mapel}</span>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-slate-400">Materi Pembelajaran</div>
                            <div class="font-semibold text-slate-800 mt-0.5">${j.materi || '-'}</div>
                        </div>
                        ${j.absensi ? `
                        <div class="pt-1 text-[11px] text-rose-600 font-medium">
                            <i class="fas fa-info-circle mr-1"></i> ${j.absensi}
                        </div>
                        ` : ''}
                    `;
                    container.appendChild(card);
                });
            } else {
                container.innerHTML = `
                    <div class="p-8 text-center text-slate-400 italic">
                        <i class="fas fa-clipboard text-3xl mb-2 text-slate-300 block"></i>
                        Belum ada entri jurnal KBM yang tercatat untuk bulan ini.
                    </div>
                `;
            }

            document.getElementById('modal-detail-jurnal').classList.remove('hidden');
        }

        function tutupModalJurnal() {
            document.getElementById('modal-detail-jurnal').classList.add('hidden');
        }
    </script>
</body>
</html>

<?php
/**
 * Helper function untuk merender tabel rekap per pilar
 */
function render_table_rekap($data_list, $pilar_nama, $theme_color, $filter_bulan, $filter_tahun) {
    // Filter guru yang memiliki jam terjadwal, telah mengisi jurnal, ada jam kosong, atau pengampu resmi mapel di pilar ini
    $filtered = array_filter($data_list, function($g) {
        return ($g['bulan_jp_target'] > 0 || $g['jurnal_jp_terisi'] > 0 || $g['jam_kosong'] > 0 || !empty($g['mapel_diampu']));
    });
?>
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-600">
                        <th class="px-3 py-3 text-center font-bold w-10">No</th>
                        <th class="px-3 py-3 text-left font-bold">Nama Guru / Tutor</th>
                        <th class="px-3 py-3 text-left font-bold">Mapel Diampu</th>
                        <th class="px-3 py-3 text-center font-bold">JP/Pekan</th>
                        <th class="px-3 py-3 text-center font-bold">Target Bulan</th>
                        <th class="px-3 py-3 text-center font-bold text-teal-800">Jurnal KBM (JP)</th>
                        <th class="px-3 py-3 text-center font-bold">Scan Presensi</th>
                        <th class="px-3 py-3 text-center font-bold text-rose-600">Jam Kosong</th>
                        <th class="px-3 py-3 text-center font-bold">Realisasi %</th>
                        <th class="px-3 py-3 text-center font-bold no-print">Rincian Jurnal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($filtered)): ?>
                        <?php $no = 1; foreach ($filtered as $g): 
                            $target = $g['bulan_jp_target'];
                            $terisi = $g['jurnal_jp_terisi'];
                            $pct = ($target > 0) ? round(($terisi / $target) * 100, 1) : ($terisi > 0 ? 100 : 0);
                            $pct_color = ($pct >= 90) ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : (($pct >= 70) ? 'text-amber-700 bg-amber-50 border-amber-200' : 'text-rose-700 bg-rose-50 border-rose-200');
                        ?>
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="px-3 py-3 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                <td class="px-3 py-3 font-bold text-slate-800 whitespace-nowrap">
                                    <?= htmlspecialchars($g['nama']) ?>
                                </td>
                                <td class="px-3 py-3 font-medium text-slate-600 max-w-xs">
                                    <?= !empty($g['mapel_diampu']) ? htmlspecialchars(implode(', ', $g['mapel_diampu'])) : '<span class="text-slate-400 italic">-</span>' ?>
                                </td>
                                <td class="px-3 py-3 text-center font-bold text-slate-700"><?= $g['pekan_jp'] ?> JP</td>
                                <td class="px-3 py-3 text-center font-bold text-slate-800"><?= $g['bulan_jp_target'] ?> JP</td>
                                <td class="px-3 py-3 text-center font-black text-teal-700 bg-teal-50/30"><?= $g['jurnal_jp_terisi'] ?> JP</td>
                                <td class="px-3 py-3 text-center font-semibold text-slate-600"><?= $g['total_scan_absen'] ?>x</td>
                                <td class="px-3 py-3 text-center font-bold <?= ($g['jam_kosong'] > 0) ? 'text-rose-600 bg-rose-50/30' : 'text-slate-400' ?>"><?= $g['jam_kosong'] ?></td>
                                <td class="px-3 py-3 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-full font-black border text-[11px] <?= $pct_color ?>">
                                        <?= $pct ?>%
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-center whitespace-nowrap no-print">
                                    <button type="button" onclick="tampilkanDetailJurnal('<?= strtolower($pilar_nama) ?>', <?= $g['id'] ?>)" 
                                            class="bg-slate-100 hover:bg-teal-50 hover:text-teal-700 text-slate-700 font-bold px-2.5 py-1.5 rounded-xl transition text-[11px] inline-flex items-center gap-1 border border-slate-200 shadow-sm">
                                        <i class="fas fa-eye text-teal-600"></i>
                                        <span>Lihat Log Jurnal</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="px-3 py-8 text-center text-slate-400 italic">
                                Belum ada jadwal atau jurnal mengajar yang tercatat untuk pilar <?= htmlspecialchars($pilar_nama) ?> pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php
}
?>
