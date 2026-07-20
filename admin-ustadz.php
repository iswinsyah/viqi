<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$view = $_GET['view'] ?? 'default';
$ustadz_nama = $_SESSION['ustadz_nama'] ?? 'Ustadz';
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$allowed_peraturan_roles = ['super_admin','kepala_sekolah','sekretaris_sekolah','bendahara_sekolah','admin_sekolah','kepala_mahad','kepala_asrama','musyrif','ustadz'];
$has_peraturan_menu = false;
foreach ($user_roles as $role) {
    if (in_array(trim($role), $allowed_peraturan_roles, true)) {
        $has_peraturan_menu = true;
        break;
    }
}

// --- LOGIC & DATA FETCHING BERDASARKAN VIEW ---
if ($view === 'dashboard_asrama') {
    $active_menu = 'dashboard_asrama';
    // --- LOGIC UNTUK DASHBOARD ASRAMA ---
    $total_santri_aktif = $conn->query("SELECT COUNT(id) as total FROM buku_induk_santri WHERE status_santri = 'Aktif'")->fetch_assoc()['total'] ?? 0;
    $total_musyrif = $conn->query("SELECT COUNT(id) as total FROM akun_ustadz WHERE role LIKE '%musyrif%'")->fetch_assoc()['total'] ?? 0;
    $total_laporan_minggu_ini = $conn->query("SELECT COUNT(id) as total FROM laporan_adab WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;

    $laporan_adab_data = []; $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $days[] = date('d M', strtotime($date));
        $laporan_adab_data['labels'][] = date('d M', strtotime($date));
        $laporan_adab_data['pelanggaran'][] = 0;
        $laporan_adab_data['apresiasi'][] = 0;
    }
    $res_adab = $conn->query("SELECT DATE(tanggal) as tgl, SUM(CASE WHEN jenis_laporan = 'Pelanggaran' THEN 1 ELSE 0 END) as total_pelanggaran, SUM(CASE WHEN jenis_laporan = 'Apresiasi' THEN 1 ELSE 0 END) as total_apresiasi FROM laporan_adab WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY tgl ORDER BY tgl ASC");
    if ($res_adab) { while ($row = $res_adab->fetch_assoc()) { $index = array_search(date('d M', strtotime($row['tgl'])), $laporan_adab_data['labels']); if ($index !== false) { $laporan_adab_data['pelanggaran'][$index] = (int)$row['total_pelanggaran']; $laporan_adab_data['apresiasi'][$index] = (int)$row['total_apresiasi']; } } }

    $jurnal_musyrif_data = ['labels' => $days, 'data' => array_fill(0, 7, 0)];
    $res_jurnal = $conn->query("SELECT DATE(tanggal) as tgl, COUNT(id) as total FROM jurnal_kegiatan_musyrif WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY tgl ORDER BY tgl ASC");
    if ($res_jurnal) { while ($row = $res_jurnal->fetch_assoc()) { $index = array_search(date('d M', strtotime($row['tgl'])), $jurnal_musyrif_data['labels']); if ($index !== false) { $jurnal_musyrif_data['data'][$index] = (int)$row['total']; } } }

} elseif ($view === 'halaqoh') {
    $active_menu = 'manajemen_halaqoh';
    // --- LOGIC UNTUK MANAJEMEN HALAQOH ---
    $conn->query("CREATE TABLE IF NOT EXISTS halaqoh_grup (id INT AUTO_INCREMENT PRIMARY KEY, nama_grup VARCHAR(150) NOT NULL, musyrif_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->query("CREATE TABLE IF NOT EXISTS halaqoh_anggota (id INT AUTO_INCREMENT PRIMARY KEY, grup_id INT NOT NULL, santri_id INT NOT NULL, UNIQUE KEY (grup_id, santri_id), FOREIGN KEY (grup_id) REFERENCES halaqoh_grup(id) ON DELETE CASCADE)");
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['simpan_grup'])) {
            $id = (int)($_POST['id'] ?? 0); $nama_grup = $conn->real_escape_string($_POST['nama_grup']); $musyrif_id = (int)$_POST['musyrif_id'];
            if ($id > 0) { $conn->query("UPDATE halaqoh_grup SET nama_grup='$nama_grup', musyrif_id=$musyrif_id WHERE id=$id"); } else { $conn->query("INSERT INTO halaqoh_grup (nama_grup, musyrif_id) VALUES ('$nama_grup', $musyrif_id)"); }
            header("Location: admin-ustadz.php?view=halaqoh"); exit;
        }
        if (isset($_POST['simpan_anggota'])) {
            $grup_id = (int)$_POST['grup_id']; $anggota_ids = $_POST['anggota'] ?? [];
            $conn->query("DELETE FROM halaqoh_anggota WHERE grup_id = $grup_id");
            if (!empty($anggota_ids)) {
                $stmt = $conn->prepare("INSERT INTO halaqoh_anggota (grup_id, santri_id) VALUES (?, ?)");
                foreach ($anggota_ids as $santri_id) { $s_id = (int)$santri_id; $stmt->bind_param("ii", $grup_id, $s_id); $stmt->execute(); }
            }
            header("Location: admin-ustadz.php?view=halaqoh&grup_id=$grup_id"); exit;
        }
    }
    if (isset($_GET['hapus_grup_id'])) { $id = (int)$_GET['hapus_grup_id']; $conn->query("DELETE FROM halaqoh_grup WHERE id = $id"); header("Location: admin-ustadz.php?view=halaqoh"); exit; }
    $musyrif_list = []; $res_m = $conn->query("SELECT id, nama FROM akun_ustadz WHERE role LIKE '%musyrif%' OR role LIKE '%kepala_asrama%' ORDER BY nama ASC"); if($res_m) while($r = $res_m->fetch_assoc()) $musyrif_list[] = $r;
    $grup_list = []; $res_g = $conn->query("SELECT g.*, u.nama as nama_musyrif, COUNT(a.id) as jumlah_anggota FROM halaqoh_grup g JOIN akun_ustadz u ON g.musyrif_id = u.id LEFT JOIN halaqoh_anggota a ON g.id = a.grup_id GROUP BY g.id ORDER BY g.nama_grup ASC"); if($res_g) while($r = $res_g->fetch_assoc()) $grup_list[] = $r;
    $active_grup_id = $_GET['grup_id'] ?? ($grup_list[0]['id'] ?? 0);
    $active_grup = null; if ($active_grup_id > 0) { foreach($grup_list as $g) { if ($g['id'] == $active_grup_id) $active_grup = $g; } }
    $santri_tersedia = []; $anggota_sekarang_ids = [];
    if ($active_grup_id > 0) { $res_a = $conn->query("SELECT santri_id FROM halaqoh_anggota WHERE grup_id = $active_grup_id"); if($res_a) while($r = $res_a->fetch_assoc()) $anggota_sekarang_ids[] = $r['santri_id']; }
    $res_s = $conn->query("SELECT id, nama_lengkap, kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC"); if($res_s) while($r = $res_s->fetch_assoc()) $santri_tersedia[] = $r;

} elseif ($view === 'setor_hafalan') {
    $active_menu = 'setor_hafalan';
    $ustadz_id = $_SESSION['ustadz_id'];
    $conn->query("CREATE TABLE IF NOT EXISTS setoran_hafalan ( id INT AUTO_INCREMENT PRIMARY KEY, ustadz_id INT NOT NULL, nama_santri VARCHAR(150) NOT NULL, tanggal DATE NOT NULL, jenis_setoran ENUM('Ziyadah', 'Murajaah') NOT NULL, surat_id INT NOT NULL, ayat_dari INT NOT NULL, ayat_sampai INT NOT NULL, penilaian ENUM('Lancar', 'Kurang Lancar', 'Perlu Diulang') NOT NULL, catatan TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP )");
    if (isset($_GET['hapus_id'])) {
        $id = (int)$_GET['hapus_id'];
        $conn->query("DELETE FROM setoran_hafalan WHERE id = $id AND ustadz_id = $ustadz_id");
        header("Location: admin-ustadz.php?view=setor_hafalan"); exit;
    }
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_setoran'])) {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $nama_santri = $conn->real_escape_string($_POST['nama_santri']);
        $tanggal = $conn->real_escape_string($_POST['tanggal']);
        $jenis_setoran = $conn->real_escape_string($_POST['jenis_setoran']);
        $surat_id = (int)$_POST['surat_id'];
        $ayat_dari = (int)$_POST['ayat_dari'];
        $ayat_sampai = (int)$_POST['ayat_sampai'];
        $penilaian = $conn->real_escape_string($_POST['penilaian']);
        $catatan = $conn->real_escape_string($_POST['catatan']);
        if ($id > 0) {
            $sql = "UPDATE setoran_hafalan SET nama_santri='$nama_santri', tanggal='$tanggal', jenis_setoran='$jenis_setoran', surat_id=$surat_id, ayat_dari=$ayat_dari, ayat_sampai=$ayat_sampai, penilaian='$penilaian', catatan='$catatan' WHERE id=$id AND ustadz_id = $ustadz_id";
            $pesan_sukses_setoran = "Data setoran hafalan berhasil diupdate!";
        } else {
            $sql = "INSERT INTO setoran_hafalan (ustadz_id, nama_santri, tanggal, jenis_setoran, surat_id, ayat_dari, ayat_sampai, penilaian, catatan) VALUES ($ustadz_id, '$nama_santri', '$tanggal', '$jenis_setoran', $surat_id, $ayat_dari, $ayat_sampai, '$penilaian', '$catatan')";
            $pesan_sukses_setoran = "Setoran hafalan baru berhasil dicatat!";
        }
        $conn->query($sql);
    }
    $edit_mode_setoran = false;
    $data_edit_setoran = null;
    if (isset($_GET['edit_id'])) {
        $edit_mode_setoran = true;
        $id = (int)$_GET['edit_id'];
        $res = $conn->query("SELECT * FROM setoran_hafalan WHERE id = $id AND ustadz_id = $ustadz_id");
        if ($res) $data_edit_setoran = $res->fetch_assoc();
    }
    $surah_list = [
        1 => "Al-Fatihah", 2 => "Al-Baqarah", 3 => "Ali 'Imran", 4 => "An-Nisa'", 5 => "Al-Ma'idah", 6 => "Al-An'am", 7 => "Al-A'raf", 8 => "Al-Anfal", 9 => "At-Taubah", 10 => "Yunus", 11 => "Hud", 12 => "Yusuf", 13 => "Ar-Ra'd", 14 => "Ibrahim", 15 => "Al-Hijr",
        16 => "An-Nahl", 17 => "Al-Isra'", 18 => "Al-Kahf", 19 => "Maryam", 20 => "Taha", 21 => "Al-Anbiya'", 22 => "Al-Hajj", 23 => "Al-Mu'minun", 24 => "An-Nur", 25 => "Al-Furqan", 26 => "Asy-Syu'ara'", 27 => "An-Naml", 28 => "Al-Qasas", 29 => "Al-'Ankabut", 30 => "Ar-Rum",
        31 => "Luqman", 32 => "As-Sajdah", 33 => "Al-Ahzab", 34 => "Saba'", 35 => "Fatir", 36 => "Ya-Sin", 37 => "As-Saffat", 38 => "Sad", 39 => "Az-Zumar", 40 => "Ghafir", 41 => "Fussilat", 42 => "Asy-Syura", 43 => "Az-Zukhruf", 44 => "Ad-Dukhan", 45 => "Al-Jathiyah",
        46 => "Al-Ahqaf", 47 => "Muhammad", 48 => "Al-Fath", 49 => "Al-Hujurat", 50 => "Qaf", 51 => "Az-Zariyat", 52 => "At-Tur", 53 => "An-Najm", 54 => "Al-Qamar", 55 => "Ar-Rahman", 56 => "Al-Waqi'ah", 57 => "Al-Hadid", 58 => "Al-Mujadilah", 59 => "Al-Hasyr", 60 => "Al-Mumtahanah",
        61 => "As-Saff", 62 => "Al-Jumu'ah", 63 => "Al-Munafiqun", 64 => "At-Taghabun", 65 => "At-Talaq", 66 => "At-Tahrim", 67 => "Al-Mulk", 68 => "Al-Qalam", 69 => "Al-Haqqah", 70 => "Al-Ma'arij", 71 => "Nuh", 72 => "Al-Jinn", 73 => "Al-Muzzammil", 74 => "Al-Muddaththir", 75 => "Al-Qiyamah",
        76 => "Al-Insan", 77 => "Al-Mursalat", 78 => "An-Naba'", 79 => "An-Nazi'at", 80 => "'Abasa", 81 => "At-Takwir", 82 => "Al-Infitar", 83 => "Al-Mutaffifin", 84 => "Al-Insyiqaq", 85 => "Al-Buruj", 86 => "At-Tariq", 87 => "Al-A'la", 88 => "Al-Ghasyiyah", 89 => "Al-Fajr", 90 => "Al-Balad",
        91 => "Asy-Syams", 92 => "Al-Layl", 93 => "Ad-Duha", 94 => "Al-Insyirah", 95 => "At-Tin", 96 => "Al-'Alaq", 97 => "Al-Qadr", 98 => "Al-Bayyinah", 99 => "Az-Zalzalah", 100 => "Al-'Adiyat", 101 => "Al-Qari'ah", 102 => "At-Takasur", 103 => "Al-'Asr", 104 => "Al-Humazah", 105 => "Al-Fil",
        106 => "Quraisy", 107 => "Al-Ma'un", 108 => "Al-Kausar", 109 => "Al-Kafirun", 110 => "An-Nasr", 111 => "Al-Masad", 112 => "Al-Ikhlas", 113 => "Al-Falaq", 114 => "An-Nas"
    ];

} elseif ($view === 'peraturan_role') {
    $active_menu = 'peraturan_role';
    $conn->query("CREATE TABLE IF NOT EXISTS peraturan_pegawai (
        id INT AUTO_INCREMENT PRIMARY KEY,
        jabatan VARCHAR(255) UNIQUE NOT NULL,
        konten TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
    $role_keywords = [
        'kepala_sekolah' => ['kepala sekolah'],
        'sekretaris_sekolah' => ['sekretaris sekolah'],
        'bendahara_sekolah' => ['bendahara sekolah'],
        'admin_sekolah' => ['admin sekolah', 'staf administrasi', 'tata usaha', 'administrasi', 'keuangan sekolah'],
        'kepala_mahad' => ["kepala ma'had", 'kepala mahad', 'kepala mahad'],
        'kepala_asrama' => ['kepala asrama', 'mudir', 'ka asrama'],
        'musyrif' => ['musyrif', 'musyrifah'],
        'ustadz' => ['ustadz', 'guru pengampu', 'pengajar', 'guru'],
        'tutor' => ['tutor', 'tentor', 'pendamping'],
        'trainer' => ['trainer', 'pelatih', 'instruktur'],
        'super_admin' => ['']
    ];

    $all_peraturan = [];
    $res = $conn->query("SELECT * FROM peraturan_pegawai ORDER BY jabatan ASC");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $all_peraturan[] = $row;
        }
    }

    $matched_peraturan = [];
    foreach ($all_peraturan as $row) {
        $jabatan_lower = strtolower($row['jabatan']);
        $match = false;
        if (in_array('super_admin', $user_roles, true)) {
            $match = true;
        } else {
            foreach ($user_roles as $role) {
                $role = trim($role);
                if ($role === '') continue;
                $keywords = $role_keywords[$role] ?? [$role];
                foreach ($keywords as $keyword) {
                    if ($keyword !== '' && str_contains($jabatan_lower, strtolower($keyword))) {
                        $match = true;
                        break 2;
                    }
                }
            }
        }
        if ($match) {
            $matched_peraturan[] = $row;
        }
    }

    $user_roles_label = [];
    foreach ($user_roles as $role) {
        if (!$role) continue;
        $user_roles_label[] = ucwords(str_replace(['_', 'mahad'], [' ', 'mahad'], $role));
    }
    $user_roles_label = array_unique($user_roles_label);

} elseif ($view === 'amanah') {
    $active_menu = 'amanah_asatidz';

    // Buat tabel penyimpanan amanah per role jika belum ada
    $conn->query("CREATE TABLE IF NOT EXISTS amanah_role (
        id INT AUTO_INCREMENT PRIMARY KEY,
        role_key VARCHAR(100) UNIQUE NOT NULL,
        role_label VARCHAR(150) NOT NULL,
        konten TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    // Definisikan daftar role yang digunakan di sistem
    $defined_roles = [
        'kepala_sekolah' => 'Kepala Sekolah',
        'sekretaris_sekolah' => 'Sekretaris Sekolah',
        'bendahara_sekolah' => 'Bendahara Sekolah',
        'admin_sekolah' => 'Admin Sekolah',
        'kepala_mahad' => "Kepala Ma'had",
        'kepala_asrama' => 'Kepala Asrama',
        'musyrif' => 'Musyrif',
        'ustadz' => 'Ustadz',
        'tutor' => 'Tutor',
        'trainer' => 'Trainer'
    ];

    // Ambil konten amanah per role dari DB
    $amanah_roles = [];
    $stmt = $conn->prepare("SELECT konten, updated_at FROM amanah_role WHERE role_key = ?");
    foreach ($defined_roles as $rk => $rl) {
        $konten = '';
        $updated = null;
        $stmt->bind_param('s', $rk);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $konten = $row['konten'];
            $updated = $row['updated_at'];
        }
        $amanah_roles[$rk] = ['label' => $rl, 'konten' => $konten, 'updated_at' => $updated];
    }
    $stmt->close();

    // Tentukan tab awal: pilih role pertama yang dimiliki user, jika ada
    $user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
    $active_tab = array_key_first($defined_roles);
    foreach ($user_roles as $ur) {
        $ur = trim($ur);
        if (isset($defined_roles[$ur])) { $active_tab = $ur; break; }
    }

} else { // default view
    header("Location: admin-absensi-pegawai.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Asatidz | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php if ($view === 'dashboard_asrama'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
    <?php if ($view === 'amanah' || $view === 'peraturan_role'): ?>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }
        /* Premium markdown body styling */
        .markdown-body h1 { 
            font-size: 1.5rem; 
            font-weight: 800; 
            color: #1e293b; 
            margin-top: 1.75rem; 
            margin-bottom: 0.75rem; 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 0.35rem; 
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .markdown-body h1::before {
            content: "\f2c2";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #0891b2; /* Cyan theme for Asatidz */
            font-size: 1.25rem;
        }
        .markdown-body h2 { 
            font-size: 1.2rem; 
            font-weight: 700; 
            color: #0891b2; 
            margin-top: 1.25rem; 
            margin-bottom: 0.5rem; 
        }
        .markdown-body h3 { 
            font-size: 1.05rem; 
            font-weight: 600; 
            color: #475569; 
            margin-top: 1rem; 
            margin-bottom: 0.25rem; 
        }
        .markdown-body p { 
            margin-bottom: 0.75rem; 
            line-height: 1.625; 
            color: #475569; 
            text-align: justify;
        }
        .markdown-body ul, .markdown-body ol { 
            margin-left: 1.5rem; 
            margin-bottom: 0.75rem; 
        }
        .markdown-body ul { 
            list-style-type: disc; 
        }
        .markdown-body ol { 
            list-style-type: decimal; 
        }
        .markdown-body li { 
            margin-bottom: 0.35rem; 
            color: #475569; 
        }
        .markdown-body strong { 
            color: #0f172a; 
            font-weight: 700; 
        }
        .markdown-body blockquote { 
            border-left: 4px solid #06b6d4; 
            padding-left: 1rem; 
            color: #64748b; 
            font-style: italic; 
            margin: 1rem 0; 
            background: #ecfeff; 
            padding-top: 0.5rem; 
            padding-bottom: 0.5rem; 
        }
        .markdown-body hr {
            border: 0;
            border-top: 2px dashed #e2e8f0;
            margin: 2rem 0;
        }
        
        /* Print layout optimizations */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            #sidebar-hr, header, .no-print {
                display: none !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                overflow: visible !important;
                height: auto !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .markdown-body h1 {
                border-bottom: 2px solid #000;
            }
        }
    </style>
    <?php endif; ?>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR KEPEGAWAIAN -->
    <?php include 'sidebar-hr.php'; ?>

    <!-- AREA KONTEN UTAMA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <!-- Tombol Hamburger untuk Mobile -->
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="index.html" target="_blank" class="text-sm text-cyan-600 hover:text-cyan-800 font-medium hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
                </a>
                <div class="h-8 w-8 rounded-full bg-cyan-500 flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($ustadz_nama, 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if ($view === 'dashboard_asrama'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN DASHBOARD KEPALA ASRAMA                   -->
            <!-- ================================================== -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-home-user text-cyan-600 mr-2"></i>Dashboard Kepala Asrama</h1>
                <p class="text-gray-500 mt-1">Grafik pemantauan aktivitas musyrif dan santri secara visual.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center"><div class="p-4 rounded-full bg-emerald-100 text-emerald-600 mr-4"><i class="fas fa-users text-2xl"></i></div><div><p class="text-sm font-medium text-gray-500">Total Santri Aktif</p><p class="text-3xl font-bold text-gray-900"><?= $total_santri_aktif ?></p></div></div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center"><div class="p-4 rounded-full bg-cyan-100 text-cyan-600 mr-4"><i class="fas fa-user-shield text-2xl"></i></div><div><p class="text-sm font-medium text-gray-500">Total Musyrif</p><p class="text-3xl font-bold text-gray-900"><?= $total_musyrif ?></p></div></div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center"><div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4"><i class="fas fa-balance-scale text-2xl"></i></div><div><p class="text-sm font-medium text-gray-500">Laporan Adab (7 Hari)</p><p class="text-3xl font-bold text-gray-900"><?= $total_laporan_minggu_ini ?></p></div></div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Grafik Laporan Kedisiplinan (7 Hari)</h3>
                    <canvas id="grafikLaporanAdab"></canvas>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Grafik Aktivitas Jurnal Musyrif (7 Hari)</h3>
                    <canvas id="grafikJurnalMusyrif"></canvas>
                </div>
            </div>

            <?php elseif ($view === 'halaqoh'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN MANAJEMEN HALAQOH                        -->
            <!-- ================================================== -->
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-layer-group text-cyan-600 mr-2"></i>Manajemen Halaqoh Santri</h1></div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-4 border-b bg-slate-50"><h3 class="font-bold text-slate-800">Daftar Grup Halaqoh</h3></div>
                        <div class="p-2 space-y-1">
                            <?php foreach($grup_list as $g): ?>
                            <a href="?view=halaqoh&grup_id=<?= $g['id'] ?>" class="<?= $g['id'] == $active_grup_id ? 'bg-cyan-100 text-cyan-800' : 'hover:bg-gray-100' ?> block p-3 rounded-lg transition">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold"><?= htmlspecialchars($g['nama_grup']) ?></span>
                                    <span class="text-xs font-bold bg-white px-2 py-1 rounded-full border"><?= $g['jumlah_anggota'] ?> Santri</span>
                                </div>
                                <div class="text-xs mt-1 opacity-70"><i class="fas fa-user-shield mr-1"></i> Musyrif: <?= htmlspecialchars($g['nama_musyrif']) ?></div>
                            </a>
                            <?php endforeach; ?>
                            <?php if(empty($grup_list)): ?><p class="p-4 text-center text-sm text-gray-500 italic">Belum ada grup.</p><?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-4 border-b bg-slate-50"><h3 class="font-bold text-slate-800"><i class="fas fa-plus-circle mr-2 text-cyan-600"></i>Buat Grup Baru</h3></div>
                        <form action="admin-ustadz.php?view=halaqoh" method="POST" class="p-4 space-y-3">
                            <input type="hidden" name="simpan_grup" value="1">
                            <div><label class="text-sm font-medium">Nama Grup</label><input type="text" name="nama_grup" required class="w-full mt-1 px-3 py-2 border rounded-lg" placeholder="Cth: Halaqoh Abu Bakar"></div>
                            <div><label class="text-sm font-medium">Pilih Musyrif</label><select name="musyrif_id" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><option value="">-- Pilih --</option><?php foreach($musyrif_list as $m) echo "<option value='{$m['id']}'>".htmlspecialchars($m['nama'])."</option>"; ?></select></div>
                            <div class="text-right"><button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-5 rounded-lg shadow-md transition">Simpan Grup</button></div>
                        </form>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
                    <?php if($active_grup): ?>
                    <div class="p-4 border-b bg-slate-50 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-slate-800">Kelola Anggota: <span class="text-cyan-600"><?= htmlspecialchars($active_grup['nama_grup']) ?></span></h3>
                            <p class="text-xs text-gray-500 mt-1">Pilih santri dari daftar di bawah untuk dimasukkan ke dalam grup ini.</p>
                        </div>
                        <a href="?view=halaqoh&hapus_grup_id=<?= $active_grup['id'] ?>" onclick="return confirm('Yakin ingin menghapus grup ini?')" class="text-red-500 hover:text-red-700 text-xs font-bold"><i class="fas fa-trash mr-1"></i> Hapus Grup</a>
                    </div>
                    <form action="admin-ustadz.php?view=halaqoh" method="POST">
                        <input type="hidden" name="simpan_anggota" value="1">
                        <input type="hidden" name="grup_id" value="<?= $active_grup_id ?>">
                        <div class="p-4 h-[60vh] overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach($santri_tersedia as $s): 
                                    $checked = in_array($s['id'], $anggota_sekarang_ids) ? 'checked' : '';
                                ?>
                                <label class="flex items-center space-x-3 p-3 rounded-lg border hover:bg-gray-50 cursor-pointer <?= $checked ? 'bg-cyan-50 border-cyan-200' : '' ?>">
                                    <input type="checkbox" name="anggota[]" value="<?= $s['id'] ?>" <?= $checked ?> class="w-5 h-5 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500">
                                    <div>
                                        <span class="font-medium text-gray-800"><?= htmlspecialchars($s['nama_lengkap']) ?></span>
                                        <span class="text-xs text-gray-500 block">Kelas: <?= htmlspecialchars($s['kelas_sekarang']) ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="p-4 border-t bg-slate-50 text-right">
                            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Perubahan Anggota</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="p-4 h-full flex flex-col items-center justify-center text-center text-gray-500">
                        <i class="fas fa-mouse-pointer text-5xl mb-4 text-gray-300"></i>
                        <h3 class="font-bold text-lg">Pilih Grup Halaqoh</h3>
                        <p>Silakan pilih grup di sebelah kiri untuk mulai mengelola anggotanya, atau buat grup baru.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($view === 'peraturan_role'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN PERATURAN ROLE                           -->
            <!-- ================================================== -->
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-file-contract text-cyan-600 mr-2"></i>Peraturan Pegawai</h1>
                    <p class="text-gray-500 mt-1">Lihat peraturan dan SOP yang relevan dengan peran Anda.</p>
                    <?php if (!empty($user_roles_label)): ?>
                        <p class="text-sm text-gray-500 mt-2">Peran Anda: <?= htmlspecialchars(implode(', ', $user_roles_label)) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (count($matched_peraturan) > 0): ?>
                <div class="grid grid-cols-1 gap-6">
                    <?php foreach ($matched_peraturan as $rule): ?>
                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-4">
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($rule['jabatan']) ?></h2>
                                    <p class="text-xs text-gray-500 mt-1">Terakhir diperbarui <?= date('d M Y H:i', strtotime($rule['updated_at'])) ?></p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-cyan-100 text-cyan-800 px-3 py-1 text-xs font-semibold">Read-only</span>
                            </div>
                            <div class="markdown-body peraturan-view">
                                <div class="hidden peraturan-md"><?= htmlspecialchars($rule['konten']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-10 text-center text-gray-600">
                    <i class="fas fa-exclamation-circle text-4xl mb-4 text-amber-500"></i>
                    <h3 class="text-lg font-bold text-gray-900 mb-2">Belum ada peraturan untuk peran Anda.</h3>
                    <p class="text-sm">Silakan minta admin Yayasan untuk menambahkan SOP & Peraturan pada menu <strong>SOP & Peraturan</strong>.</p>
                </div>
            <?php endif; ?>

            <?php elseif ($view === 'setor_hafalan'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN SETORAN HAFALAN                          -->
            <!-- ================================================== -->
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-quran text-emerald-600 mr-2"></i>Pencatatan Setoran Hafalan</h1></div>
            <?php if(isset($pesan_sukses_setoran)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses_setoran</div>"; ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode_setoran ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode_setoran ? 'Edit Catatan Setoran' : 'Input Setoran Baru' ?></h2></div>
                <form action="admin-ustadz.php?view=setor_hafalan" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode_setoran ? $data_edit_setoran['id'] : '' ?>">
                    <input type="hidden" name="simpan_setoran" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label><input type="text" name="nama_santri" value="<?= $edit_mode_setoran ? htmlspecialchars($data_edit_setoran['nama_santri']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500" placeholder="Nama lengkap santri"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Setoran</label><input type="date" name="tanggal" value="<?= $edit_mode_setoran ? $data_edit_setoran['tanggal'] : date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Jenis Setoran</label><select name="jenis_setoran" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500"><option value="Ziyadah" <?= ($edit_mode_setoran && $data_edit_setoran['jenis_setoran'] == 'Ziyadah') ? 'selected' : '' ?>>Ziyadah (Hafalan Baru)</option><option value="Murajaah" <?= ($edit_mode_setoran && $data_edit_setoran['jenis_setoran'] == 'Murajaah') ? 'selected' : '' ?>>Muraja'ah (Mengulang)</option></select></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Surat</label><select name="surat_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500"><?php foreach($surah_list as $id => $name) { $sel = ($edit_mode_setoran && $data_edit_setoran['surat_id'] == $id) ? 'selected' : ''; echo "<option value='$id' $sel>$id. $name</option>"; } ?></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Dari Ayat</label><input type="number" name="ayat_dari" value="<?= $edit_mode_setoran ? $data_edit_setoran['ayat_dari'] : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Sampai Ayat</label><input type="number" name="ayat_sampai" value="<?= $edit_mode_setoran ? $data_edit_setoran['ayat_sampai'] : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500"></div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Penilaian</label><select name="penilaian" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500"><?php $penilaian_opsi = ['Lancar', 'Kurang Lancar', 'Perlu Diulang']; foreach($penilaian_opsi as $p) { $sel = ($edit_mode_setoran && $data_edit_setoran['penilaian'] == $p) ? 'selected' : ''; echo "<option value='$p' $sel>$p</option>"; } ?></select></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label><input type="text" name="catatan" value="<?= $edit_mode_setoran ? htmlspecialchars($data_edit_setoran['catatan']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500" placeholder="Contoh: Makhraj huruf 'ain perlu dilatih"></div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode_setoran) echo '<a href="admin-ustadz.php?view=setor_hafalan" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode_setoran ? 'Update Catatan' : 'Simpan Setoran' ?></button>
                    </div>
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Setoran Hafalan (Milik Anda)</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri & Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Setoran</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Penilaian & Catatan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res_setoran = $conn->query("SELECT * FROM setoran_hafalan WHERE ustadz_id = $ustadz_id ORDER BY tanggal DESC, id DESC");
                            if ($res_setoran && $res_setoran->num_rows > 0) {
                                while($row = $res_setoran->fetch_assoc()) { 
                                    $badge_color = 'bg-gray-100 text-gray-700';
                                    if ($row['penilaian'] == 'Lancar') $badge_color = 'bg-emerald-100 text-emerald-800';
                                    if ($row['penilaian'] == 'Perlu Diulang') $badge_color = 'bg-red-100 text-red-800';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 align-top"><div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_santri']) ?></div><div class="text-xs text-gray-500 mt-1"><?= date('d M Y', strtotime($row['tanggal'])) ?></div></td>
                                    <td class="px-4 py-3 align-top"><div class="font-semibold text-gray-800"><?= htmlspecialchars($row['jenis_setoran']) ?>: <span class="text-emerald-700">QS. <?= $surah_list[$row['surat_id']] ?> [<?= $row['surat_id'] ?>]: <?= $row['ayat_dari'] ?>-<?= $row['ayat_sampai'] ?></span></div></td>
                                    <td class="px-4 py-3 align-top"><span class="px-2 py-1 text-xs font-bold rounded-full <?= $badge_color ?>"><?= htmlspecialchars($row['penilaian']) ?></span><?php if(!empty($row['catatan'])): ?><p class="text-xs text-gray-500 mt-2 italic">"<?= htmlspecialchars($row['catatan']) ?>"</p><?php endif; ?></td>
                                    <td class="px-4 py-3 text-center align-top"><a href="?view=setor_hafalan&edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a><a href="?view=setor_hafalan&hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus catatan setoran ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a></td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500 italic'>Belum ada catatan setoran hafalan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($view === 'amanah'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN MENU AMANAH                              -->
            <!-- ================================================== -->
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-id-card text-cyan-600 mr-2"></i>Menu Amanah</h1>
                    <p class="text-gray-500 mt-1">Daftar wewenang, rincian tugas berkala, dan Key Performance Indicators (KPI) Anda.</p>
                </div>
                <?php $has_any_amanah = false; foreach($amanah_roles as $r) { if (!empty(trim($r['konten']))) { $has_any_amanah = true; break; } } ?>
                <?php if ($has_any_amanah): ?>
                <div class="flex items-center gap-2">
                    <button id="btn-print-amanah" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-sm">
                        <i class="fas fa-print"></i>
                        <span>Cetak PDF</span>
                    </button>
                    <button id="btn-copy-amanah" class="bg-white hover:bg-gray-100 border border-gray-200 text-gray-750 px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 shadow-sm">
                        <i class="fas fa-copy"></i>
                        <span id="copy-text-btn">Salin Teks</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- CARD UTAMA AMANAH: TAB PER ROLE -->
            <div class="bg-white rounded-2xl border border-gray-200/60 shadow-md p-6 md:p-8 print-card font-outfit">
                <div class="mb-4">
                    <div id="amanah-tabs" class="flex space-x-2 overflow-x-auto">
                        <?php foreach ($amanah_roles as $rk => $r): ?>
                            <button data-role="<?= $rk ?>" class="px-3 py-2 rounded-lg border text-sm <?= ($rk === $active_tab) ? 'bg-cyan-600 text-white' : 'bg-white' ?>"><?= htmlspecialchars($r['label']) ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="amanah-panels" class="space-y-6">
                    <?php foreach ($amanah_roles as $rk => $r): ?>
                        <div class="amanah-panel <?= ($rk === $active_tab) ? '' : 'hidden' ?>" data-role="<?= $rk ?>">
                            <div id="amanah-render-<?= $rk ?>" class="markdown-body text-sm leading-relaxed font-outfit"></div>
                            <textarea id="amanah-raw-<?= $rk ?>" class="hidden"><?= htmlspecialchars($r['konten']) ?></textarea>
                            <?php if (!empty($r['updated_at'])): ?><p class="text-xs text-gray-500 mt-2">Terakhir diperbarui <?= date('d M Y H:i', strtotime($r['updated_at'])) ?></p><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const tabs = document.querySelectorAll('#amanah-tabs button');
                        let activeRole = '<?= $active_tab ?>';

                        function renderAll() {
                            document.querySelectorAll('[id^="amanah-raw-"]').forEach(el => {
                                const id = el.id.replace('amanah-raw-', '');
                                const htmlTarget = document.getElementById('amanah-render-' + id);
                                if (htmlTarget) htmlTarget.innerHTML = marked.parse(el.value || '');
                            });
                        }

                        function showRole(role) {
                            activeRole = role;
                            document.querySelectorAll('.amanah-panel').forEach(p => p.classList.toggle('hidden', p.dataset.role !== role));
                            tabs.forEach(b => b.classList.toggle('bg-cyan-600', b.dataset.role === role));
                            tabs.forEach(b => b.classList.toggle('text-white', b.dataset.role === role));
                        }

                        tabs.forEach(btn => btn.addEventListener('click', function(){ showRole(this.dataset.role); }));
                        renderAll();
                        showRole(activeRole);

                        const copyBtn = document.getElementById('btn-copy-amanah');
                        if (copyBtn) copyBtn.addEventListener('click', function(){
                            const raw = document.getElementById('amanah-raw-' + activeRole).value || '';
                            navigator.clipboard.writeText(raw).then(() => {
                                const original = copyBtn.innerHTML;
                                copyBtn.innerHTML = '<i class="fas fa-check text-emerald-500"></i> <span class="text-emerald-600 font-bold">Tersalin!</span>';
                                setTimeout(()=> copyBtn.innerHTML = original, 1800);
                            }).catch(err => console.error(err));
                        });

                        const printBtn = document.getElementById('btn-print-amanah');
                        if (printBtn) printBtn.addEventListener('click', function(){
                            const panel = document.querySelector('.amanah-panel[data-role="' + activeRole + '"]');
                            if (!panel) return window.print();
                            const w = window.open('', '_blank');
                            w.document.write('<html><head><title>Amanah - ' + activeRole + '</title>');
                            w.document.write('<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">');
                            w.document.write('<style>body{font-family:Arial, Helvetica, sans-serif;padding:20px;color:#111}</style></head><body>');
                            w.document.write('<h2>' + panel.querySelector('h2')?.textContent || '' + '</h2>');
                            w.document.write(panel.innerHTML);
                            w.document.write('</body></html>');
                            w.document.close();
                            w.print();
                        });
                    });
                </script>
            </div>

            <?php else: ?>
            <!-- ================================================== -->
            <!-- TAMPILAN DEFAULT (DASHBOARD PEGAWAI)              -->
            <!-- ================================================== -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chalkboard-teacher text-cyan-600 mr-2"></i>Ahlan Wa Sahlan, <?= htmlspecialchars($ustadz_nama) ?>!</h1>
                <p class="text-gray-500 mt-1">Selamat datang di Ruang Asatidz. Gunakan menu-menu di bawah ini untuk mengelola kegiatan akademik.</p>
            </div>

            <!-- WIDGET SHORTCUT -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <a href="admin-pegawai-jurnal.php" class="bg-white hover:bg-cyan-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-book-open text-cyan-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Isi Jurnal</span>
                </a>
                <a href="admin-pegawai-mutabaah.php" class="bg-white hover:bg-emerald-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-clipboard-list text-emerald-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Buku Mutaba'ah</span>
                </a>
                <a href="admin-pegawai-rpp.php" class="bg-white hover:bg-blue-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-magic text-blue-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">AI RPP</span>
                </a>
                <?php if ($has_peraturan_menu): ?>
                <a href="admin-ustadz.php?view=peraturan_role" class="bg-white hover:bg-slate-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-file-contract text-slate-700 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Peraturan Pegawai</span>
                </a>
                <?php endif; ?>
            </div>

            <!-- WIDGET STATISTIK -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-4 rounded-full bg-cyan-100 text-cyan-600 mr-4"><i class="fas fa-book-open text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Jurnal Anda</p><p class="text-2xl font-bold text-gray-900"><?= $total_jurnal ?></p></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4"><i class="fas fa-star-half-alt text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Nilai Diinput</p><p class="text-2xl font-bold text-gray-900"><?= $total_nilai ?></p></div>
                </div>
            </div>

            <!-- TABEL JURNAL TERBARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800">Jurnal Mengajar Terakhir</h2>
                    <a href="admin-pegawai-jurnal.php" class="text-sm text-cyan-600 hover:text-cyan-800 font-medium">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kelas & Mapel</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Materi Pokok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($jurnal_terbaru) > 0): ?>
                                <?php foreach($jurnal_terbaru as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700 font-medium whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-cyan-700"><?= htmlspecialchars($row['kelas']) ?></div>
                                        <div class="text-sm text-gray-600"><?= htmlspecialchars($row['mata_pelajaran']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium"><?= htmlspecialchars($row['materi']) ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan='3' class='text-center py-6 text-gray-500 italic'>Belum ada catatan jurnal mengajar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- SCRIPT UNTUK TOGGLE SIDEBAR DI MOBILE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-hr');
            const openBtn = document.getElementById('open-sidebar-hr');
            const overlay = document.getElementById('sidebar-overlay-hr');
            const closeBtn = document.getElementById('close-sidebar-hr');

            function toggleSidebar() {
                if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);

            <?php if ($view === 'dashboard_asrama'): ?>
            // Grafik Laporan Adab
            const ctxAdab = document.getElementById('grafikLaporanAdab').getContext('2d');
            new Chart(ctxAdab, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($laporan_adab_data['labels']) ?>,
                    datasets: [{
                        label: 'Pelanggaran', data: <?= json_encode($laporan_adab_data['pelanggaran']) ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.6)', borderColor: 'rgba(239, 68, 68, 1)', borderWidth: 1
                    }, {
                        label: 'Apresiasi', data: <?= json_encode($laporan_adab_data['apresiasi']) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)', borderColor: 'rgba(16, 185, 129, 1)', borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });

            // Grafik Jurnal Musyrif
            const ctxJurnal = document.getElementById('grafikJurnalMusyrif').getContext('2d');
            new Chart(ctxJurnal, {
                type: 'line',
                data: {
                    labels: <?= json_encode($jurnal_musyrif_data['labels']) ?>,
                    datasets: [{
                        label: 'Jumlah Jurnal Masuk', data: <?= json_encode($jurnal_musyrif_data['data']) ?>,
                        fill: true, backgroundColor: 'rgba(6, 182, 212, 0.2)',
                        borderColor: 'rgba(6, 182, 212, 1)', tension: 0.3
                    }]
                },
                options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
            <?php endif; ?>
            <?php if ($view === 'peraturan_role'): ?>
                document.querySelectorAll('.peraturan-view').forEach(card => {
                    const rawContainer = card.querySelector('.peraturan-md');
                    const raw = rawContainer ? rawContainer.textContent.trim() : '';
                    if (raw.length > 0) {
                        card.innerHTML = marked.parse(raw);
                    } else {
                        card.innerHTML = '<div class="text-sm text-gray-500">Konten peraturan tidak tersedia.</div>';
                    }
                });
            <?php endif; ?>
        });
    </script>
</body>
</html>