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

// Hak akses & wewenang: Pengurus Yayasan, Kepala Sekolah, Kepala Ma'had, Admin Sekolah, Super Admin
$is_yayasan = in_array('ketua_yayasan', $norm_roles) || in_array('sekretaris_yayasan', $norm_roles) || in_array('bendahara_yayasan', $norm_roles) || in_array('yayasan', $norm_roles) || in_array('yayasan2', $norm_roles);
$is_kepsek = in_array('kepala_sekolah', $norm_roles);
$is_kepala_mahad = in_array('kepala_mahad', $norm_roles) || in_array('kepala_asrama', $norm_roles) || in_array('kepala_asrama_rijal', $norm_roles) || in_array('kepala_asrama_nisa', $norm_roles);
$is_super_admin = in_array('super_admin', $norm_roles) || (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999);
$is_admin_sekolah = in_array('admin_sekolah', $norm_roles) || in_array('sekretaris_sekolah', $norm_roles) || in_array('bendahara_sekolah', $norm_roles);

$is_admin = $is_yayasan || $is_kepsek || $is_kepala_mahad || $is_admin_sekolah || $is_super_admin;

// Database self-healing
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

$pesan_sukses = '';
$pesan_error = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        $pesan_error = "Akses ditolak: Hanya Manajemen & Admin Sekolah yang berhak mengubah data.";
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
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
            } else {
                $pesan_error = "Gagal menyimpan data: " . $conn->error;
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_pengganti') {
            $id = (int)$_POST['id'];
            $guru_pengganti_id = !empty($_POST['guru_pengganti_id']) ? (int)$_POST['guru_pengganti_id'] : 'NULL';
            $status_kontrol = $guru_pengganti_id !== 'NULL' ? 'Terisi' : 'Perlu Pengganti';
            $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');

            $sql = "UPDATE kontrol_jam_kosong SET 
                    guru_pengganti_id = $guru_pengganti_id, 
                    status_kontrol = '$status_kontrol', 
                    catatan = '$catatan' 
                    WHERE id = $id";
            if ($conn->query($sql)) {
                $pesan_sukses = "Data guru pengganti (inval) berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal memperbarui data: " . $conn->error;
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'batal') {
            $id = (int)$_POST['id'];
            $sql = "UPDATE kontrol_jam_kosong SET status_kontrol = 'Batal' WHERE id = $id";
            if ($conn->query($sql)) {
                $pesan_sukses = "Log jam kosong dibatalkan.";
            } else {
                $pesan_error = "Gagal memperbarui status: " . $conn->error;
            }
        }
    }
}

// Filter Periode
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$current_tab = $_GET['tab'] ?? 'rekap';

$nama_bulan_indo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Hitung jumlah kemunculan hari dalam bulan & tahun yang dipilih
$total_hari_bulan = cal_days_in_month(CAL_GREGORIAN, $filter_bulan, $filter_tahun);
$hari_count = [
    'Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0, 'Sabtu' => 0, 'Ahad' => 0
];
$map_day_en_id = [
    'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Ahad'
];

for ($d = 1; $d <= $total_hari_bulan; $d++) {
    $time = mktime(0, 0, 0, $filter_bulan, $d, $filter_tahun);
    $day_en = date('l', $time);
    if (isset($map_day_en_id[$day_en])) {
        $hari_count[$map_day_en_id[$day_en]]++;
    }
}

// Fetch all Ustadz / Guru
$asatidz_list = [];
$asatidz_map = [];
$res_ast = $conn->query("SELECT id, nama FROM akun_ustadz ORDER BY nama ASC");
if ($res_ast) {
    while ($row = $res_ast->fetch_assoc()) {
        $asatidz_list[] = $row;
        $asatidz_map[$row['id']] = $row['nama'];
    }
}

// Fetch classes
$kelas_list = [];
$res_kls = $conn->query("SELECT id, nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kls && $res_kls->num_rows > 0) {
    while ($row = $res_kls->fetch_assoc()) {
        $kelas_list[] = $row;
    }
} else {
    $kelas_list = [
        ['id' => 1, 'nama_kelas' => 'E4 406'],
        ['id' => 2, 'nama_kelas' => 'E4 402'],
        ['id' => 3, 'nama_kelas' => 'E4 157'],
        ['id' => 4, 'nama_kelas' => 'E4.2']
    ];
}

// Fetch master mapel list (Khusus Mapel Offline / Tatap Muka)
$mapel_list = [];
$mapel_cat_map = [];
$res_mpl = $conn->query("SELECT id, nama_mapel, kategori_mapel, metode_belajar, pengampu_id FROM master_mapel WHERE status_aktif = 1 AND (metode_belajar = 'offline' OR (metode_belajar != 'ai_agentic' AND pengampu_id IS NOT NULL) OR metode_belajar IS NULL) ORDER BY nama_mapel ASC");
if ($res_mpl && $res_mpl->num_rows > 0) {
    while ($row = $res_mpl->fetch_assoc()) {
        $mapel_list[] = $row;
        $mapel_cat_map[$row['id']] = $row['kategori_mapel'];
        $mapel_cat_map[$row['nama_mapel']] = $row['kategori_mapel'];
    }
}

// ----------------------------------------------------
// 1. REKAPITULASI JAM MENGAJAR PER GURU (KHUSUS MAPEL OFFLINE / TATAP MUKA)
// ----------------------------------------------------
// Inisialisasi struktur rekap untuk SEMUA Ustadz / Guru
$rekap_guru = [];
foreach ($asatidz_list as $ast) {
    $gid = (int)$ast['id'];
    $rekap_guru[$gid] = [
        'id' => $gid,
        'nama' => $ast['nama'],
        // JP per pekan
        'pekan_diknas' => 0,
        'pekan_diniyah' => 0,
        'pekan_solopreneur' => 0,
        'pekan_lainnya' => 0,
        'pekan_total' => 0,
        // JP Terjadwal Bulan ini
        'bulan_diknas' => 0,
        'bulan_diniyah' => 0,
        'bulan_solopreneur' => 0,
        'bulan_lainnya' => 0,
        'bulan_total' => 0,
        // Data Jurnal KBM Mengajar Terisi (jurnal_mengajar)
        'jurnal_diknas' => 0,
        'jurnal_diniyah' => 0,
        'jurnal_solopreneur' => 0,
        'jurnal_lainnya' => 0,
        'jurnal_total' => 0,
        // Data Presensi Scan Absen KBM (absensi_pegawai)
        'total_scan_absen' => 0,
        // Jam Kosong / Izin Bulan Ini
        'jam_kosong_diknas' => 0,
        'jam_kosong_diniyah' => 0,
        'jam_kosong_solopreneur' => 0,
        'jam_kosong_total' => 0,
        // Jam Menggantikan / Inval
        'jam_inval' => 0,
        // Realisasi & Detail
        'realisasi_jam' => 0,
        'persen_kehadiran' => 100,
        'detail_mapel' => [],
        'detail_jurnal' => []
    ];
}

// A. Tarik Jam dari Jadwal Pelajaran (jadwal_pelajaran)
$sql_jadwal = "SELECT j.*, m.nama_mapel, m.kategori_mapel, m.metode_belajar, k.nama_kelas, u.nama as nama_guru
               FROM jadwal_pelajaran j
               LEFT JOIN master_mapel m ON j.mapel_id = m.id
               LEFT JOIN master_kelas k ON j.kelas_id = k.id
               LEFT JOIN akun_ustadz u ON j.ustadz_id = u.id
               WHERE j.ustadz_id IS NOT NULL AND j.ustadz_id > 0
               AND (m.metode_belajar IS NULL OR m.metode_belajar = 'offline' OR m.metode_belajar != 'ai_agentic')";
$res_j = $conn->query($sql_jadwal);
$all_jadwal = ($res_j) ? $res_j->fetch_all(MYSQLI_ASSOC) : [];

foreach ($all_jadwal as $j) {
    $gid = (int)($j['ustadz_id'] ?? 0);
    if ($gid > 0 && isset($rekap_guru[$gid])) {
        $hari = trim($j['hari']);
        $kat = strtolower(trim($j['kategori_mapel'] ?? ''));
        $multiplier = $hari_count[$hari] ?? 4;

        if (strpos($kat, 'diknas') !== false || strpos($kat, 'pkbm') !== false) {
            $rekap_guru[$gid]['pekan_diknas']++;
            $rekap_guru[$gid]['bulan_diknas'] += $multiplier;
        } elseif (strpos($kat, 'diniyah') !== false || strpos($kat, 'tahfidz') !== false || strpos($kat, 'pesantren') !== false) {
            $rekap_guru[$gid]['pekan_diniyah']++;
            $rekap_guru[$gid]['bulan_diniyah'] += $multiplier;
        } elseif (strpos($kat, 'solo') !== false || strpos($kat, 'bisnis') !== false || strpos($kat, 'entrepreneur') !== false || strpos($kat, 'skill') !== false) {
            $rekap_guru[$gid]['pekan_solopreneur']++;
            $rekap_guru[$gid]['bulan_solopreneur'] += $multiplier;
        } else {
            $rekap_guru[$gid]['pekan_lainnya']++;
            $rekap_guru[$gid]['bulan_lainnya'] += $multiplier;
        }

        $rekap_guru[$gid]['pekan_total']++;
        $rekap_guru[$gid]['bulan_total'] += $multiplier;

        $rekap_guru[$gid]['detail_mapel'][] = [
            'hari' => $hari,
            'jam_ke' => $j['jam_ke'],
            'kelas' => $j['nama_kelas'] ?? 'Kelas',
            'mapel' => $j['nama_mapel'] ?? 'Mapel',
            'kategori' => $j['kategori_mapel'] ?? 'Umum'
        ];
    }
}

// B. Sinkronisasi Data Jurnal Mengajar Nyata (jurnal_mengajar)
$sql_jurnal = "SELECT jm.*, u.id as u_id, u.nama as u_nama 
               FROM jurnal_mengajar jm
               LEFT JOIN akun_ustadz u ON jm.ustadz_id = u.id
               WHERE MONTH(jm.tanggal) = $filter_bulan AND YEAR(jm.tanggal) = $filter_tahun
               ORDER BY jm.tanggal DESC, jm.id DESC";
$res_jur = $conn->query($sql_jurnal);
if ($res_jur && $res_jur->num_rows > 0) {
    while ($jm = $res_jur->fetch_assoc()) {
        $u_id = (int)($jm['ustadz_id'] ?? 0);
        if ($u_id > 0 && isset($rekap_guru[$u_id])) {
            $mpl = $jm['mata_pelajaran'];
            $kat = strtolower($mapel_cat_map[$mpl] ?? '');

            if (strpos($kat, 'diknas') !== false || strpos($kat, 'pkbm') !== false) {
                $rekap_guru[$u_id]['jurnal_diknas']++;
            } elseif (strpos($kat, 'diniyah') !== false || strpos($kat, 'tahfidz') !== false || strpos($kat, 'pesantren') !== false) {
                $rekap_guru[$u_id]['jurnal_diniyah']++;
            } elseif (strpos($kat, 'solo') !== false || strpos($kat, 'bisnis') !== false || strpos($kat, 'entrepreneur') !== false || strpos($kat, 'skill') !== false) {
                $rekap_guru[$u_id]['jurnal_solopreneur']++;
            } else {
                $rekap_guru[$u_id]['jurnal_lainnya']++;
            }
            $rekap_guru[$u_id]['jurnal_total']++;
            $rekap_guru[$u_id]['detail_jurnal'][] = [
                'tanggal' => $jm['tanggal'],
                'kelas' => $jm['kelas'],
                'mapel' => $jm['mata_pelajaran'],
                'materi' => $jm['materi'],
                'absensi' => $jm['absensi']
            ];
        }
    }
}

// C. Sinkronisasi Data Presensi Absensi Mengajar (absensi_pegawai)
$sql_absen = "SELECT ustadz_id, DATE(waktu_absen) as tgl, status_kehadiran, COUNT(*) as jml
              FROM absensi_pegawai 
              WHERE MONTH(waktu_absen) = $filter_bulan AND YEAR(waktu_absen) = $filter_tahun 
              AND jenis_absen = 'Mengajar' AND status_kehadiran IN ('Masuk', 'Hadir', 'Pulang')
              GROUP BY ustadz_id, DATE(waktu_absen)";
$res_abs = $conn->query($sql_absen);
if ($res_abs && $res_abs->num_rows > 0) {
    while ($ab = $res_abs->fetch_assoc()) {
        $u_id = (int)$ab['ustadz_id'];
        if ($u_id > 0 && isset($rekap_guru[$u_id])) {
            $rekap_guru[$u_id]['total_scan_absen']++;
        }
    }
}

// ----------------------------------------------------
// 2. DATA LOG JAM KOSONG BULAN INI (kontrol_jam_kosong)
// ----------------------------------------------------
$sql_logs = "SELECT k.*, u1.nama as nama_guru_utama, u2.nama as nama_guru_pengganti, uc.nama as nama_creator
             FROM kontrol_jam_kosong k
             JOIN akun_ustadz u1 ON k.guru_utama_id = u1.id
             LEFT JOIN akun_ustadz u2 ON k.guru_pengganti_id = u2.id
             LEFT JOIN akun_ustadz uc ON k.created_by = uc.id
             WHERE MONTH(k.tanggal) = $filter_bulan AND YEAR(k.tanggal) = $filter_tahun
             ORDER BY k.tanggal DESC, k.created_at DESC";
$res_logs = $conn->query($sql_logs);
$logs = ($res_logs) ? $res_logs->fetch_all(MYSQLI_ASSOC) : [];

// Aggregate Jam Kosong & Inval ke Rekap Guru
foreach ($logs as $l) {
    if ($l['status_kontrol'] !== 'Batal') {
        $u_id = (int)$l['guru_utama_id'];
        $p_id = (int)$l['guru_pengganti_id'];
        $mpl_name = $l['mapel'];
        $kat = strtolower($mapel_cat_map[$mpl_name] ?? '');

        if (isset($rekap_guru[$u_id])) {
            $rekap_guru[$u_id]['jam_kosong_total']++;
            if (strpos($kat, 'diknas') !== false) {
                $rekap_guru[$u_id]['jam_kosong_diknas']++;
            } elseif (strpos($kat, 'diniyah') !== false) {
                $rekap_guru[$u_id]['jam_kosong_diniyah']++;
            } elseif (strpos($kat, 'solo') !== false) {
                $rekap_guru[$u_id]['jam_kosong_solopreneur']++;
            }
        }

        if ($p_id > 0 && isset($rekap_guru[$p_id]) && $l['status_kontrol'] === 'Terisi') {
            $rekap_guru[$p_id]['jam_inval']++;
        }
    }
}

// Hitung Final Realisasi & Persentase Kehadiran
$grand_total_diknas = 0;
$grand_total_diniyah = 0;
$grand_total_solopreneur = 0;
$grand_total_terjadwal = 0;
$grand_total_kosong = 0;
$grand_total_inval = 0;
$grand_total_realisasi = 0;
$grand_total_jurnal = 0;
$grand_total_absen = 0;

foreach ($rekap_guru as $gid => &$rg) {
    // Jika belum diplot slot di jadwal tetapi guru sudah mengisi jurnal / absen mengajar
    if ($rg['bulan_total'] === 0 && ($rg['jurnal_total'] > 0 || $rg['total_scan_absen'] > 0)) {
        $rg['bulan_diknas'] = $rg['jurnal_diknas'];
        $rg['bulan_diniyah'] = $rg['jurnal_diniyah'];
        $rg['bulan_solopreneur'] = $rg['jurnal_solopreneur'];
        $rg['bulan_lainnya'] = $rg['jurnal_lainnya'];
        $rg['bulan_total'] = max($rg['jurnal_total'], $rg['total_scan_absen']);
    }

    // Realisasi jam mengajar = realisasi jurnal terisi ATAU jam terjadwal dikurangi jam kosong ditambah jam inval
    if ($rg['jurnal_total'] > 0) {
        $rg['realisasi_jam'] = $rg['jurnal_total'] + $rg['jam_inval'];
    } else {
        $rg['realisasi_jam'] = max(0, $rg['bulan_total'] - $rg['jam_kosong_total'] + $rg['jam_inval']);
    }

    // Persentase kehadiran
    if ($rg['bulan_total'] > 0) {
        $rg['persen_kehadiran'] = round((($rg['bulan_total'] - $rg['jam_kosong_total']) / $rg['bulan_total']) * 100, 1);
        if ($rg['jurnal_total'] > 0 && $rg['persen_kehadiran'] < 100) {
            $rg['persen_kehadiran'] = min(100, round(($rg['jurnal_total'] / $rg['bulan_total']) * 100, 1));
        }
    } else {
        $rg['persen_kehadiran'] = 100;
    }

    $grand_total_diknas += $rg['bulan_diknas'];
    $grand_total_diniyah += $rg['bulan_diniyah'];
    $grand_total_solopreneur += $rg['bulan_solopreneur'];
    $grand_total_terjadwal += $rg['bulan_total'];
    $grand_total_kosong += $rg['jam_kosong_total'];
    $grand_total_inval += $rg['jam_inval'];
    $grand_total_realisasi += $rg['realisasi_jam'];
    $grand_total_jurnal += $rg['jurnal_total'];
    $grand_total_absen += $rg['total_scan_absen'];
}
unset($rg);

// Semua ustadz tampil lengkap agar Yayasan & Kepsek dapat memantau seluruh staf
$rekap_guru_aktif = $rekap_guru;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Jam Mengajar & Kontrol Jam Kosong | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            .print-full { width: 100% !important; margin: 0 !important; padding: 0 !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <div class="no-print">
        <?php include 'sidebar-hr.php'; ?>
    </div>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center gap-3">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden p-2 rounded-xl hover:bg-slate-100 transition">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-md bg-teal-50 text-[#0b8478] text-[11px] font-black uppercase tracking-wider border border-teal-200">KBM & Kurikulum</span>
                    <span class="text-slate-400 text-xs">•</span>
                    <h2 class="font-bold text-slate-800 text-sm hidden sm:inline-block">Rekapitulasi Jam Mengajar & Jam Kosong</h2>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="px-3.5 py-1.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-100 font-bold text-xs flex items-center gap-2 transition shadow-xs">
                    <i class="fas fa-print text-teal-600"></i>
                    <span>Cetak Laporan</span>
                </button>
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-[#0b8478] to-teal-500 flex items-center justify-center text-white font-black text-sm shadow-md shadow-teal-100">
                    <?= strtoupper(substr($_SESSION['ustadz_nama'] ?? 'A', 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 print-full">
            
            <!-- HEADER BANNER -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#0b8478] to-teal-600 flex items-center justify-center text-white shadow-lg shadow-teal-600/20">
                            <i class="fas fa-chalkboard-teacher text-xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Rekapitulasi Jam Mengajar & Jam Kosong</h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-teal-100 text-teal-800 border border-teal-300">Khusus Mapel Offline</span>
                            </div>
                            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Rekapitulasi beban jam tatap muka langsung (Luring / Offline) yang diampu oleh <strong>Tutor Diknas</strong>, <strong>Ustadz Diniyah</strong>, dan <strong>Trainer Solopreneur</strong>.</p>
                        </div>
                    </div>
                </div>

                <!-- FILTER BULAN & TAHUN -->
                <form method="GET" class="no-print flex items-center gap-2 bg-white p-2 rounded-2xl border border-slate-200 shadow-xs">
                    <input type="hidden" name="tab" value="<?= htmlspecialchars($current_tab) ?>">
                    <div class="flex items-center gap-1 text-xs font-bold text-slate-500 pl-2">
                        <i class="fas fa-calendar-alt text-teal-600"></i>
                        <span>Periode:</span>
                    </div>
                    <select name="bulan" class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs bg-slate-50 font-bold text-slate-700 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <?php for ($m=1; $m<=12; $m++): ?>
                            <option value="<?= $m ?>" <?= $filter_bulan === $m ? 'selected' : '' ?>><?= $nama_bulan_indo[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="tahun" class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs bg-slate-50 font-bold text-slate-700 focus:ring-2 focus:ring-teal-500 focus:outline-none">
                        <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
                            <option value="<?= $y ?>" <?= $filter_tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="bg-[#0b8478] hover:bg-teal-700 text-white font-extrabold px-4 py-1.5 rounded-xl text-xs transition shadow-xs">
                        Terapkan
                    </button>
                </form>
            </div>

            <!-- MESSAGES -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl mb-6 shadow-xs flex items-center justify-between text-xs font-bold">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-check-circle text-base text-emerald-600"></i>
                        <span><?= $pesan_sukses ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl mb-6 shadow-xs flex items-center justify-between text-xs font-bold">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-exclamation-circle text-base text-rose-600"></i>
                        <span><?= $pesan_error ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- STATISTIC SUMMARY CARDS (4 PILAR OFFLINE) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Diknas Card -->
                <div class="bg-white rounded-2xl p-5 border border-blue-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-md border border-blue-200">Tutor Diknas (Offline)</span>
                            <h3 class="text-2xl font-black text-slate-900 mt-2"><?= number_format($grand_total_diknas) ?> <span class="text-xs font-bold text-slate-400">JP/Bln</span></h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Tatap Muka PKBM / Nasional</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-blue-600 h-full rounded-full" style="width: <?= $grand_total_terjadwal > 0 ? min(100, round(($grand_total_diknas/$grand_total_terjadwal)*100)) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Diniyah Card -->
                <div class="bg-white rounded-2xl p-5 border border-emerald-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-md border border-emerald-200">Ustadz Diniyah (Offline)</span>
                            <h3 class="text-2xl font-black text-slate-900 mt-2"><?= number_format($grand_total_diniyah) ?> <span class="text-xs font-bold text-slate-400">JP/Bln</span></h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Tatap Muka Tahfidz & Kitab</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                            <i class="fas fa-quran"></i>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-emerald-600 h-full rounded-full" style="width: <?= $grand_total_terjadwal > 0 ? min(100, round(($grand_total_diniyah/$grand_total_terjadwal)*100)) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Solopreneur Card -->
                <div class="bg-white rounded-2xl p-5 border border-purple-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 px-2.5 py-0.5 rounded-md border border-purple-200">Trainer Solopreneur (Offline)</span>
                            <h3 class="text-2xl font-black text-slate-900 mt-2"><?= number_format($grand_total_solopreneur) ?> <span class="text-xs font-bold text-slate-400">JP/Bln</span></h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Praktik Kemandirian & Skill</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                            <i class="fas fa-rocket"></i>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-purple-600 h-full rounded-full" style="width: <?= $grand_total_terjadwal > 0 ? min(100, round(($grand_total_solopreneur/$grand_total_terjadwal)*100)) : 0 ?>%"></div>
                    </div>
                </div>

                <!-- Jam Kosong & Inval Card -->
                <div class="bg-white rounded-2xl p-5 border border-amber-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-md border border-amber-200">Jam Kosong & Inval</span>
                            <h3 class="text-2xl font-black text-slate-900 mt-2"><?= number_format($grand_total_kosong) ?> <span class="text-xs font-bold text-slate-400">Kasus</span></h3>
                            <p class="text-[11px] text-slate-500 font-medium mt-0.5">Terisi Inval: <strong class="text-emerald-700"><?= number_format($grand_total_inval) ?> JP</strong></p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl group-hover:scale-110 transition">
                            <i class="fas fa-user-clock"></i>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-amber-500 h-full rounded-full" style="width: <?= $grand_total_kosong > 0 ? min(100, round(($grand_total_inval/max(1, $grand_total_kosong))*100)) : 100 ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- NAVIGATION TABS -->
            <div class="no-print flex items-center gap-2 border-b border-slate-200 mb-6 pb-2">
                <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&tab=rekap" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition <?= $current_tab === 'rekap' ? 'bg-[#0b8478] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' ?>">
                    <i class="fas fa-table text-sm"></i>
                    <span>Rekap Jam Mengajar Guru</span>
                </a>
                <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&tab=jadwal" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition <?= $current_tab === 'jadwal' ? 'bg-[#0b8478] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' ?>">
                    <i class="fas fa-calendar-week text-sm"></i>
                    <span>Sebaran Jadwal per Kategori</span>
                </a>
                <a href="?bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>&tab=jam_kosong" class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 transition <?= $current_tab === 'jam_kosong' ? 'bg-[#0b8478] text-white shadow-sm' : 'text-slate-600 hover:bg-slate-200/70' ?>">
                    <i class="fas fa-calendar-times text-sm"></i>
                    <span>Log & Kontrol Jam Kosong</span>
                    <?php if ($grand_total_kosong > 0): ?>
                        <span class="px-1.5 py-0.5 rounded-full bg-amber-400 text-slate-900 text-[10px] font-black"><?= $grand_total_kosong ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- TAB 1: REKAPITULASI JAM MENGAJAR GURU -->
            <?php if ($current_tab === 'rekap'): ?>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                    <div class="px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i class="fas fa-file-invoice text-teal-600"></i>
                                <span>Rekap Beban Mengajar Asatidz (Periode <?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>)</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Rincian akumulasi jam mengajar terjadwal (Diknas, Diniyah, Solopreneur) dan realisasi kehadiran.</p>
                        </div>
                        <div class="flex items-center gap-2 no-print">
                            <span class="text-xs font-bold text-slate-500">Total Pengajar Aktif: <strong class="text-teal-700"><?= count($rekap_guru_aktif) ?></strong></span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold uppercase tracking-wider text-[11px]">
                                    <th class="py-3.5 px-4 text-center w-12">No</th>
                                    <th class="py-3.5 px-4">Nama Guru / Ustadz</th>
                                    <th class="py-3.5 px-3 text-center bg-blue-50/50 text-blue-800 border-x border-blue-100">Diknas<br><span class="text-[9px] font-medium text-blue-600">(Pekan / Bulan)</span></th>
                                    <th class="py-3.5 px-3 text-center bg-emerald-50/50 text-emerald-800 border-r border-emerald-100">Diniyah<br><span class="text-[9px] font-medium text-emerald-600">(Pekan / Bulan)</span></th>
                                    <th class="py-3.5 px-3 text-center bg-purple-50/50 text-purple-800 border-r border-purple-100">Solopreneur<br><span class="text-[9px] font-medium text-purple-600">(Pekan / Bulan)</span></th>
                                    <th class="py-3.5 px-3 text-center bg-slate-100/80 font-black text-slate-800">Total Terjadwal<br><span class="text-[9px] font-medium text-slate-600">(Bulan Ini)</span></th>
                                    <th class="py-3.5 px-3 text-center text-rose-700 bg-rose-50/40">Jam Kosong<br><span class="text-[9px] font-medium text-rose-500">(Izin/Absen)</span></th>
                                    <th class="py-3.5 px-3 text-center text-teal-700 bg-teal-50/40">Inval<br><span class="text-[9px] font-medium text-teal-600">(Pengganti)</span></th>
                                    <th class="py-3.5 px-4 text-center font-black bg-slate-800 text-white">Realisasi Jam<br><span class="text-[9px] font-normal text-slate-300">(Total JP)</span></th>
                                    <th class="py-3.5 px-4 text-center">Kehadiran</th>
                                    <th class="py-3.5 px-4 text-center no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php $no = 1; foreach ($rekap_guru_aktif as $rg): ?>
                                    <tr class="hover:bg-teal-50/30 transition">
                                        <td class="py-3 px-4 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                        <td class="py-3 px-4">
                                            <div class="font-extrabold text-slate-900"><?= htmlspecialchars($rg['nama']) ?></div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Beban: <?= $rg['pekan_total'] ?> JP / Pekan</div>
                                        </td>
                                        
                                        <!-- Diknas -->
                                        <td class="py-3 px-3 text-center bg-blue-50/30 font-bold text-blue-900 border-x border-blue-50">
                                            <span><?= $rg['pekan_diknas'] ?> JP</span>
                                            <div class="text-[10px] text-blue-600 font-semibold"><?= $rg['bulan_diknas'] ?> JP/bln</div>
                                        </td>

                                        <!-- Diniyah -->
                                        <td class="py-3 px-3 text-center bg-emerald-50/30 font-bold text-emerald-900 border-r border-emerald-50">
                                            <span><?= $rg['pekan_diniyah'] ?> JP</span>
                                            <div class="text-[10px] text-emerald-600 font-semibold"><?= $rg['bulan_diniyah'] ?> JP/bln</div>
                                        </td>

                                        <!-- Solopreneur -->
                                        <td class="py-3 px-3 text-center bg-purple-50/30 font-bold text-purple-900 border-r border-purple-50">
                                            <span><?= $rg['pekan_solopreneur'] ?> JP</span>
                                            <div class="text-[10px] text-purple-600 font-semibold"><?= $rg['bulan_solopreneur'] ?> JP/bln</div>
                                        </td>

                                        <!-- Total Terjadwal -->
                                        <td class="py-3 px-3 text-center bg-slate-50 font-black text-slate-900 text-sm">
                                            <?= $rg['bulan_total'] ?> <span class="text-[10px] text-slate-400 font-bold">JP</span>
                                        </td>

                                        <!-- Jam Kosong -->
                                        <td class="py-3 px-3 text-center font-bold text-rose-700 bg-rose-50/20">
                                            <?php if ($rg['jam_kosong_total'] > 0): ?>
                                                <span class="px-2 py-0.5 rounded-full bg-rose-100 text-rose-800 text-[10px] font-extrabold">-<?= $rg['jam_kosong_total'] ?> JP</span>
                                            <?php else: ?>
                                                <span class="text-slate-300">0</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Jam Inval -->
                                        <td class="py-3 px-3 text-center font-bold text-teal-700 bg-teal-50/20">
                                            <?php if ($rg['jam_inval'] > 0): ?>
                                                <span class="px-2 py-0.5 rounded-full bg-teal-100 text-teal-800 text-[10px] font-extrabold">+<?= $rg['jam_inval'] ?> JP</span>
                                            <?php else: ?>
                                                <span class="text-slate-300">0</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Realisasi Jam -->
                                        <td class="py-3 px-4 text-center font-black bg-slate-800 text-amber-400 text-sm">
                                            <?= $rg['realisasi_jam'] ?> <span class="text-[10px] text-slate-300 font-medium">JP</span>
                                        </td>

                                        <!-- Kehadiran -->
                                        <td class="py-3 px-4 text-center">
                                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold <?= $rg['persen_kehadiran'] >= 95 ? 'bg-emerald-100 text-emerald-800' : ($rg['persen_kehadiran'] >= 80 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') ?>">
                                                <i class="fas <?= $rg['persen_kehadiran'] >= 95 ? 'fa-check-circle' : 'fa-exclamation-triangle' ?>"></i>
                                                <span><?= $rg['persen_kehadiran'] ?>%</span>
                                            </div>
                                        </td>

                                        <!-- Aksi -->
                                        <td class="py-3 px-4 text-center no-print">
                                            <button type="button" onclick="bukaModalDetailGuru(<?= htmlspecialchars(json_encode($rg)) ?>)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-teal-50 text-slate-700 hover:text-[#0b8478] font-bold text-[10px] border border-slate-200 transition">
                                                <i class="fas fa-eye mr-1"></i> Rincian
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-900 text-white font-black text-xs border-t-2 border-slate-700">
                                <tr>
                                    <td colspan="2" class="py-4 px-4 text-center uppercase tracking-wider text-amber-300">TOTAL AKUMULASI KESELURUHAN</td>
                                    <td class="py-4 px-3 text-center text-blue-300"><?= number_format($grand_total_diknas) ?> JP</td>
                                    <td class="py-4 px-3 text-center text-emerald-300"><?= number_format($grand_total_diniyah) ?> JP</td>
                                    <td class="py-4 px-3 text-center text-purple-300"><?= number_format($grand_total_solopreneur) ?> JP</td>
                                    <td class="py-4 px-3 text-center text-white"><?= number_format($grand_total_terjadwal) ?> JP</td>
                                    <td class="py-4 px-3 text-center text-rose-400">-<?= number_format($grand_total_kosong) ?> JP</td>
                                    <td class="py-4 px-3 text-center text-teal-300">+<?= number_format($grand_total_inval) ?> JP</td>
                                    <td class="py-4 px-4 text-center text-amber-400 text-base"><?= number_format($grand_total_realisasi) ?> JP</td>
                                    <td colspan="2" class="py-4 px-4 text-center text-slate-400 text-[10px] font-normal">Tercatat di SADIGS 4.0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB 2: SEBARAN JADWAL PELAJARAN PER KATEGORI -->
            <?php if ($current_tab === 'jadwal'): ?>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6 mb-8">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 border-b border-slate-100 pb-4">
                        <div>
                            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                                <i class="fas fa-calendar-alt text-teal-600"></i>
                                <span>Distribusi Jadwal Pelajaran (Diknas, Diniyah, Solopreneur)</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Pemetaan seluruh jadwal belajar santri berdasarkan kategori kurikulum.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="admin-jadwal-pelajaran.php" class="px-3.5 py-1.5 bg-[#0b8478] hover:bg-teal-700 text-white font-bold text-xs rounded-xl transition shadow-xs flex items-center gap-1.5">
                                <i class="fas fa-cog"></i>
                                <span>Kelola Jadwal Pelajaran</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold uppercase tracking-wider text-[11px]">
                                    <th class="py-3 px-4">Hari</th>
                                    <th class="py-3 px-3 text-center">Jam Ke</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <th class="py-3 px-4">Mata Pelajaran</th>
                                    <th class="py-3 px-4 text-center">Kategori</th>
                                    <th class="py-3 px-4">Guru Pengampu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (empty($all_jadwal)): ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400 italic">Belum ada data jadwal pelajaran yang tersimpan.</td>
                                    </tr>
                                <?php else: foreach ($all_jadwal as $j): 
                                    $kat = strtolower(trim($j['kategori_mapel'] ?? ''));
                                    $badge_class = 'bg-slate-100 text-slate-700 border-slate-200';
                                    if (strpos($kat, 'diknas') !== false) {
                                        $badge_class = 'bg-blue-50 text-blue-700 border-blue-200';
                                    } elseif (strpos($kat, 'diniyah') !== false || strpos($kat, 'tahfidz') !== false) {
                                        $badge_class = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } elseif (strpos($kat, 'solo') !== false) {
                                        $badge_class = 'bg-purple-50 text-purple-700 border-purple-200';
                                    }
                                ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3 px-4 font-bold text-slate-800"><?= htmlspecialchars($j['hari']) ?></td>
                                        <td class="py-3 px-3 text-center font-extrabold text-teal-700">Jam ke-<?= $j['jam_ke'] ?></td>
                                        <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($j['nama_kelas'] ?? 'Kelas') ?></td>
                                        <td class="py-3 px-4 font-extrabold text-slate-900"><?= htmlspecialchars($j['nama_mapel'] ?? 'Mapel') ?></td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border <?= $badge_class ?>">
                                                <?= htmlspecialchars($j['kategori_mapel'] ?? 'Lainnya') ?>
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 font-bold text-slate-700">
                                            <i class="fas fa-chalkboard-teacher text-teal-600 mr-1.5"></i>
                                            <?= htmlspecialchars($j['nama_guru'] ?? 'Belum diplot') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB 3: LOG & KONTROL JAM KOSONG (INVAL) -->
            <?php if ($current_tab === 'jam_kosong'): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start mb-8">
                    
                    <!-- FORM CARD (Admin Only) -->
                    <div class="lg:col-span-1 no-print">
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-[#0b8478] to-teal-600 text-white flex items-center justify-between">
                                <h2 class="font-extrabold text-sm flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-teal-200"></i>
                                    <span>Catat Log Jam Kosong Baru</span>
                                </h2>
                            </div>
                            
                            <?php if ($is_admin): ?>
                                <form method="POST" class="p-6 space-y-4">
                                    <input type="hidden" name="action" value="tambah">
                                    
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Kejadian <span class="text-rose-500">*</span></label>
                                        <input type="date" name="tanggal" value="<?= $today ?>" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Kelas <span class="text-rose-500">*</span></label>
                                        <select name="kelas" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                            <option value="">-- Pilih Kelas --</option>
                                            <?php foreach ($kelas_list as $kls): ?>
                                                <option value="<?= htmlspecialchars($kls['nama_kelas']) ?>"><?= htmlspecialchars($kls['nama_kelas']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran <span class="text-rose-500">*</span></label>
                                        <select name="mapel" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                            <option value="">-- Pilih Mapel --</option>
                                            <?php foreach ($mapel_list as $mpl): ?>
                                                <option value="<?= htmlspecialchars($mpl['nama_mapel']) ?>">
                                                    <?= htmlspecialchars($mpl['nama_mapel']) ?> (<?= htmlspecialchars($mpl['kategori_mapel']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Guru Utama (Absen/Izin) <span class="text-rose-500">*</span></label>
                                        <select name="guru_utama_id" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                            <option value="">-- Pilih Guru Utama --</option>
                                            <?php foreach ($asatidz_list as $ast): ?>
                                                <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Guru Pengganti (Inval)</label>
                                        <select name="guru_pengganti_id" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                            <option value="">-- Pilih Guru Pengganti (Opsional) --</option>
                                            <?php foreach ($asatidz_list as $ast): ?>
                                                <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="text-[10px] text-slate-400 mt-1">Kosongkan jika guru pengganti belum ditentukan.</p>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Alasan</label>
                                        <textarea name="catatan" rows="3" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="Contoh: Izin dinas luar, materi halaman 24..."></textarea>
                                    </div>

                                    <button type="submit" class="w-full bg-[#0b8478] hover:bg-teal-700 text-white font-extrabold py-3 px-4 rounded-xl transition text-xs shadow-md shadow-teal-700/20 flex items-center justify-center gap-2">
                                        <i class="fas fa-save"></i>
                                        <span>Simpan Log Jam Kosong</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="p-6 text-center text-xs text-slate-400 italic">
                                    Mode baca data aktif. Hubungi Admin Sekolah untuk mencatat jam kosong baru.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- LIST CARD -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6">
                            <div class="flex items-center justify-between gap-4 mb-6 border-b border-slate-100 pb-4">
                                <h3 class="font-black text-slate-900 text-sm uppercase tracking-wider flex items-center gap-2">
                                    <i class="fas fa-history text-teal-600"></i>
                                    <span>Riwayat Jam Kosong (<?= $nama_bulan_indo[$filter_bulan] ?> <?= $filter_tahun ?>)</span>
                                </h3>
                                <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">Total: <?= count($logs) ?> Log</span>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold uppercase tracking-wider text-[11px]">
                                            <th class="py-3 px-4">Tanggal & Kelas</th>
                                            <th class="py-3 px-4">Mata Pelajaran</th>
                                            <th class="py-3 px-4">Guru Utama</th>
                                            <th class="py-3 px-4">Guru Pengganti (Inval)</th>
                                            <th class="py-3 px-3 text-center">Status</th>
                                            <?php if ($is_admin): ?>
                                                <th class="py-3 px-4 text-center no-print">Aksi</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php if (empty($logs)): ?>
                                            <tr>
                                                <td colspan="<?= $is_admin ? 6 : 5 ?>" class="py-8 text-center text-slate-400 italic">Alhamdulillah, tidak ada data jam kosong pada bulan ini.</td>
                                            </tr>
                                        <?php else: foreach ($logs as $l): ?>
                                            <tr class="hover:bg-slate-50/60 transition">
                                                <td class="py-3 px-4">
                                                    <div class="font-extrabold text-slate-900"><?= date('d/m/Y', strtotime($l['tanggal'])) ?></div>
                                                    <div class="text-[10px] text-teal-700 font-bold"><?= htmlspecialchars($l['kelas']) ?></div>
                                                </td>
                                                <td class="py-3 px-4 font-bold text-slate-800"><?= htmlspecialchars($l['mapel']) ?></td>
                                                <td class="py-3 px-4 font-bold text-rose-700">
                                                    <i class="fas fa-user-times mr-1 text-rose-500"></i>
                                                    <?= htmlspecialchars($l['nama_guru_utama']) ?>
                                                </td>
                                                <td class="py-3 px-4">
                                                    <?php if ($l['guru_pengganti_id']): ?>
                                                        <span class="font-extrabold text-emerald-700"><i class="fas fa-user-check mr-1"></i><?= htmlspecialchars($l['nama_guru_pengganti']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-amber-600 font-bold italic"><i class="fas fa-spinner fa-spin mr-1 text-[10px]"></i>Menunggu Plot Inval</span>
                                                    <?php endif; ?>
                                                    <?php if(!empty($l['catatan'])): ?>
                                                        <div class="text-[10px] text-slate-400 mt-0.5 italic"><?= htmlspecialchars($l['catatan']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-3 px-3 text-center">
                                                    <?php if ($l['status_kontrol'] === 'Terisi'): ?>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">Terisi Inval</span>
                                                    <?php elseif ($l['status_kontrol'] === 'Batal'): ?>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">Batal</span>
                                                    <?php else: ?>
                                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 animate-pulse">Butuh Pengganti</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($is_admin): ?>
                                                    <td class="py-3 px-4 text-center no-print whitespace-nowrap">
                                                        <?php if ($l['status_kontrol'] !== 'Batal'): ?>
                                                            <button type="button" onclick="bukaModalEditPengganti(<?= $l['id'] ?>, <?= (int)$l['guru_pengganti_id'] ?>, '<?= htmlspecialchars(addslashes($l['catatan'] ?? '')) ?>')" class="px-2.5 py-1 rounded-lg bg-teal-50 hover:bg-teal-100 text-[#0b8478] font-extrabold text-[10px] border border-teal-200 transition">
                                                                Plot Inval
                                                            </button>
                                                            <button type="button" onclick="if(confirm('Batalkan log jam kosong ini?')) { document.getElementById('form-batal-<?= $l['id'] ?>').submit(); }" class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold text-[10px] border border-rose-200 transition">
                                                                Batal
                                                            </button>
                                                            <form id="form-batal-<?= $l['id'] ?>" method="POST" action="admin-kontrol-jam-kosong.php?tab=jam_kosong&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="hidden">
                                                                <input type="hidden" name="action" value="batal">
                                                                <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-slate-300">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MODAL DETAIL JADWAL & JURNAL GURU -->
            <div id="modal-detail-guru" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4 text-center">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="tutupModalDetailGuru()"></div>
                    <div class="inline-block bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-2xl sm:w-full p-6 relative z-10 border border-slate-100">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
                            <div>
                                <h3 id="modal-guru-nama" class="text-base font-black text-slate-900">Rincian Aktivitas Mengajar</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Rincian jadwal terjadwal & riwayat pengisian Jurnal KBM ustadz.</p>
                            </div>
                            <button type="button" onclick="tutupModalDetailGuru()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100"><i class="fas fa-times text-base"></i></button>
                        </div>
                        
                        <!-- TAB NAV MODAL -->
                        <div class="flex items-center gap-2 mb-4 border-b border-slate-100 pb-2">
                            <button type="button" id="btn-tab-jadwal" onclick="switchModalTab('jadwal')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#0b8478] text-white">
                                <i class="fas fa-calendar-alt mr-1"></i> Slot Jadwal Terjadwal (<span id="modal-count-jadwal">0</span>)
                            </button>
                            <button type="button" id="btn-tab-jurnal" onclick="switchModalTab('jurnal')" class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                                <i class="fas fa-book-open mr-1"></i> Riwayat Jurnal KBM (<span id="modal-count-jurnal">0</span>)
                            </button>
                        </div>

                        <!-- SECTION JADWAL -->
                        <div id="section-modal-jadwal" class="overflow-x-auto max-h-80">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px]">
                                        <th class="py-2.5 px-3">Hari</th>
                                        <th class="py-2.5 px-2 text-center">Jam</th>
                                        <th class="py-2.5 px-3">Kelas</th>
                                        <th class="py-2.5 px-3">Mapel</th>
                                        <th class="py-2.5 px-3 text-center">Kategori</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-guru-tbody" class="divide-y divide-slate-100">
                                </tbody>
                            </table>
                        </div>

                        <!-- SECTION JURNAL -->
                        <div id="section-modal-jurnal" class="overflow-x-auto max-h-80 hidden">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-extrabold uppercase text-[10px]">
                                        <th class="py-2.5 px-3">Tanggal</th>
                                        <th class="py-2.5 px-3">Kelas</th>
                                        <th class="py-2.5 px-3">Mata Pelajaran</th>
                                        <th class="py-2.5 px-4">Materi Pembelajaran</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-jurnal-tbody" class="divide-y divide-slate-100">
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end">
                            <button type="button" onclick="tutupModalDetailGuru()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-extrabold px-5 py-2.5 rounded-xl text-xs transition">
                                Tutup Rincian
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL EDIT GURU PENGGANTI (INVAL) -->
            <div id="modal-edit-pengganti" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen p-4 text-center">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="tutupModalEditPengganti()"></div>
                    <div class="inline-block bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-lg sm:w-full p-6 relative z-10 border border-slate-100">
                        <div class="flex justify-between items-start border-b border-slate-100 pb-4 mb-4">
                            <div>
                                <h3 class="text-base font-black text-slate-900">Plot Guru Pengganti (Inval)</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Tugaskan ustadz pengganti untuk mengisi jam pelajaran kosong.</p>
                            </div>
                            <button type="button" onclick="tutupModalEditPengganti()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-xl hover:bg-slate-100"><i class="fas fa-times text-base"></i></button>
                        </div>
                        <form method="POST" action="admin-kontrol-jam-kosong.php?tab=jam_kosong&bulan=<?= $filter_bulan ?>&tahun=<?= $filter_tahun ?>" class="space-y-4">
                            <input type="hidden" name="action" value="update_pengganti">
                            <input type="hidden" name="id" id="edit-log-id">
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Guru Pengganti (Inval) <span class="text-rose-500">*</span></label>
                                <select name="guru_pengganti_id" id="edit-guru-pengganti-id" required class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none bg-white">
                                    <option value="">-- Pilih Guru Pengganti --</option>
                                    <?php foreach ($asatidz_list as $ast): ?>
                                        <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                                <textarea name="catatan" id="edit-catatan" rows="3" class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="Catatan tugas inval..."></textarea>
                            </div>
                            
                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100">
                                <button type="button" onclick="tutupModalEditPengganti()" class="bg-slate-100 text-slate-700 font-extrabold px-4 py-2.5 rounded-xl text-xs hover:bg-slate-200 transition">
                                    Batal
                                </button>
                                <button type="submit" class="bg-[#0b8478] hover:bg-teal-700 text-white font-extrabold px-5 py-2.5 rounded-xl transition text-xs shadow-md shadow-teal-700/20">
                                    Tugaskan Pengganti
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr')?.addEventListener('click', () => { 
            document.getElementById('sidebar-hr')?.classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr')?.classList.toggle('hidden'); 
        });

        function bukaModalEditPengganti(id, penggantiId, catatan) {
            document.getElementById('edit-log-id').value = id;
            document.getElementById('edit-guru-pengganti-id').value = penggantiId || '';
            document.getElementById('edit-catatan').value = catatan || '';
            document.getElementById('modal-edit-pengganti').classList.remove('hidden');
        }

        function tutupModalEditPengganti() {
            document.getElementById('modal-edit-pengganti').classList.add('hidden');
        }

        function switchModalTab(tab) {
            const btnJadwal = document.getElementById('btn-tab-jadwal');
            const btnJurnal = document.getElementById('btn-tab-jurnal');
            const secJadwal = document.getElementById('section-modal-jadwal');
            const secJurnal = document.getElementById('section-modal-jurnal');

            if (tab === 'jadwal') {
                btnJadwal.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#0b8478] text-white';
                btnJurnal.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100';
                secJadwal.classList.remove('hidden');
                secJurnal.classList.add('hidden');
            } else {
                btnJurnal.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold bg-[#0b8478] text-white';
                btnJadwal.className = 'px-3.5 py-1.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100';
                secJurnal.classList.remove('hidden');
                secJadwal.classList.add('hidden');
            }
        }

        function bukaModalDetailGuru(guru) {
            document.getElementById('modal-guru-nama').innerText = 'Rincian Aktivitas: ' + guru.nama;
            
            // 1. Render Jadwal
            const tbodyJadwal = document.getElementById('modal-guru-tbody');
            tbodyJadwal.innerHTML = '';
            const jmlJadwal = guru.detail_mapel ? guru.detail_mapel.length : 0;
            document.getElementById('modal-count-jadwal').innerText = jmlJadwal;

            if (jmlJadwal === 0) {
                tbodyJadwal.innerHTML = '<tr><td colspan="5" class="py-4 text-center text-slate-400 italic">Belum ada slot jadwal yang diplot pada Jadwal Pelajaran.</td></tr>';
            } else {
                guru.detail_mapel.forEach(d => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50 transition';
                    tr.innerHTML = `
                        <td class="py-2.5 px-3 font-bold text-slate-800">${d.hari}</td>
                        <td class="py-2.5 px-2 text-center font-bold text-teal-700">Jam ke-${d.jam_ke}</td>
                        <td class="py-2.5 px-3 font-medium text-slate-700">${d.kelas}</td>
                        <td class="py-2.5 px-3 font-extrabold text-slate-900">${d.mapel}</td>
                        <td class="py-2.5 px-3 text-center"><span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-slate-100 text-slate-700">${d.kategori}</span></td>
                    `;
                    tbodyJadwal.appendChild(tr);
                });
            }

            // 2. Render Jurnal Mengajar
            const tbodyJurnal = document.getElementById('modal-jurnal-tbody');
            tbodyJurnal.innerHTML = '';
            const jmlJurnal = guru.detail_jurnal ? guru.detail_jurnal.length : 0;
            document.getElementById('modal-count-jurnal').innerText = jmlJurnal;

            if (jmlJurnal === 0) {
                tbodyJurnal.innerHTML = '<tr><td colspan="4" class="py-4 text-center text-slate-400 italic">Belum ada pengisian Jurnal KBM pada bulan ini.</td></tr>';
            } else {
                guru.detail_jurnal.forEach(j => {
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-slate-50 transition';
                    tr.innerHTML = `
                        <td class="py-2.5 px-3 font-bold text-slate-800">${j.tanggal}</td>
                        <td class="py-2.5 px-3 font-medium text-teal-700 font-bold">${j.kelas}</td>
                        <td class="py-2.5 px-3 font-extrabold text-slate-900">${j.mapel}</td>
                        <td class="py-2.5 px-4 text-slate-600 text-[11px]">${j.materi || '-'}</td>
                    `;
                    tbodyJurnal.appendChild(tr);
                });
            }

            // Reset tab ke jadwal
            switchModalTab('jadwal');
            document.getElementById('modal-detail-guru').classList.remove('hidden');
        }

        function tutupModalDetailGuru() {
            document.getElementById('modal-detail-guru').classList.add('hidden');
        }
    </script>

</body>
</html>
