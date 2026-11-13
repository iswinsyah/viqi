<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';
$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$view = $_GET['view'] ?? 'default';

// --- TABLE CREATION & MIGRATIONS FOR IBADAH HARIAN ---
$conn->query("CREATE TABLE IF NOT EXISTS ibadah_harian_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    tanggal DATE NOT NULL,
    sholat_subuh ENUM('Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\\'i') DEFAULT 'Munfarid',
    sholat_dhuhur ENUM('Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\\'i') DEFAULT 'Munfarid',
    sholat_ashar ENUM('Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\\'i') DEFAULT 'Munfarid',
    sholat_maghrib ENUM('Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\\'i') DEFAULT 'Munfarid',
    sholat_isya ENUM('Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\\'i') DEFAULT 'Munfarid',
    sholat_tahajud TINYINT(1) DEFAULT 0,
    sholat_witir TINYINT(1) DEFAULT 0,
    sholat_qobliyah_subuh TINYINT(1) DEFAULT 0,
    sholat_dhuha TINYINT(1) DEFAULT 0,
    sholat_qobli_dhuhur TINYINT(1) DEFAULT 0,
    sholat_bakdiyah_dhuhur TINYINT(1) DEFAULT 0,
    sholat_qobliyah_ashar TINYINT(1) DEFAULT 0,
    sholat_bakdiyah_maghrib TINYINT(1) DEFAULT 0,
    sholat_qobliyah_isya TINYINT(1) DEFAULT 0,
    sholat_bakdiyah_isya TINYINT(1) DEFAULT 0,
    puasa_senin TINYINT(1) DEFAULT 0,
    puasa_kamis TINYINT(1) DEFAULT 0,
    is_haid TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (santri_id, tanggal)
)");

@$conn->query("ALTER TABLE ibadah_harian_santri ADD COLUMN is_haid TINYINT(1) DEFAULT 0 AFTER puasa_kamis");
@$conn->query("ALTER TABLE ibadah_harian_santri ADD COLUMN status_validasi ENUM('Pending', 'Disetujui', 'Ditolak') DEFAULT 'Pending' AFTER is_haid");
@$conn->query("ALTER TABLE ibadah_harian_santri ADD COLUMN catatan_musyrif TEXT NULL AFTER status_validasi");
@$conn->query("ALTER TABLE ibadah_harian_santri ADD COLUMN validated_by INT NULL AFTER catatan_musyrif");
@$conn->query("ALTER TABLE ibadah_harian_santri ADD COLUMN validated_at DATETIME NULL AFTER validated_by");

// Fetch profile data santri
$res_santri = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id LIMIT 1");
$santri_data = ($res_santri && $res_santri->num_rows > 0) ? $res_santri->fetch_assoc() : null;
$jenis_kelamin = $santri_data['jenis_kelamin'] ?? 'Laki-laki';
$is_female = (strcasecmp($jenis_kelamin, 'Perempuan') === 0);
$kelas_santri = $santri_data['kelas_sekarang'] ?? 'Santri';
$nisn_santri = $santri_data['nisn'] ?? '-';
$foto_santri = !empty($santri_data['foto_santri']) ? $santri_data['foto_santri'] : '';

// --- LOGIC FOR IBADAH HARIAN VIEW ---
if ($view === 'ibadah_harian') {
    $active_menu = 'ibadah_harian';
    $pesan_sukses = '';
    $pesan_error = '';

    // Handle Form Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tanggal = $conn->real_escape_string($_POST['tanggal']);
        $is_haid = (isset($_POST['is_haid']) && $is_female) ? 1 : 0;
        
        if ($is_haid) {
            $sholat_subuh = $conn->real_escape_string("Udzur Syar'i");
            $sholat_dhuhur = $conn->real_escape_string("Udzur Syar'i");
            $sholat_ashar = $conn->real_escape_string("Udzur Syar'i");
            $sholat_maghrib = $conn->real_escape_string("Udzur Syar'i");
            $sholat_isya = $conn->real_escape_string("Udzur Syar'i");
            
            $sholat_tahajud = 0;
            $sholat_witir = 0;
            $sholat_qobliyah_subuh = 0;
            $sholat_dhuha = 0;
            $sholat_qobli_dhuhur = 0;
            $sholat_bakdiyah_dhuhur = 0;
            $sholat_qobliyah_ashar = 0;
            $sholat_bakdiyah_maghrib = 0;
            $sholat_qobliyah_isya = 0;
            $sholat_bakdiyah_isya = 0;
            
            $puasa_senin = 0;
            $puasa_kamis = 0;
        } else {
            $sholat_subuh = $conn->real_escape_string($_POST['sholat_subuh']);
            $sholat_dhuhur = $conn->real_escape_string($_POST['sholat_dhuhur']);
            $sholat_ashar = $conn->real_escape_string($_POST['sholat_ashar']);
            $sholat_maghrib = $conn->real_escape_string($_POST['sholat_maghrib']);
            $sholat_isya = $conn->real_escape_string($_POST['sholat_isya']);
            
            $sholat_tahajud = isset($_POST['sholat_tahajud']) ? 1 : 0;
            $sholat_witir = isset($_POST['sholat_witir']) ? 1 : 0;
            $sholat_qobliyah_subuh = isset($_POST['sholat_qobliyah_subuh']) ? 1 : 0;
            $sholat_dhuha = isset($_POST['sholat_dhuha']) ? 1 : 0;
            $sholat_qobli_dhuhur = isset($_POST['sholat_qobli_dhuhur']) ? 1 : 0;
            $sholat_bakdiyah_dhuhur = isset($_POST['sholat_bakdiyah_dhuhur']) ? 1 : 0;
            $sholat_qobliyah_ashar = isset($_POST['sholat_qobliyah_ashar']) ? 1 : 0;
            $sholat_bakdiyah_maghrib = isset($_POST['sholat_bakdiyah_maghrib']) ? 1 : 0;
            $sholat_qobliyah_isya = isset($_POST['sholat_qobliyah_isya']) ? 1 : 0;
            $sholat_bakdiyah_isya = isset($_POST['sholat_bakdiyah_isya']) ? 1 : 0;

            $puasa_senin = isset($_POST['puasa_senin']) ? 1 : 0;
            $puasa_kamis = isset($_POST['puasa_kamis']) ? 1 : 0;
        }

        // Check if entry for this date already exists
        $check_sql = "SELECT id FROM ibadah_harian_santri WHERE santri_id = $santri_id AND tanggal = '$tanggal'";
        $check_res = $conn->query($check_sql);

        if ($check_res && $check_res->num_rows > 0) {
            $existing_id = $check_res->fetch_assoc()['id'];
            $sql = "UPDATE ibadah_harian_santri SET 
                    sholat_subuh='$sholat_subuh', sholat_dhuhur='$sholat_dhuhur', sholat_ashar='$sholat_ashar', sholat_maghrib='$sholat_maghrib', sholat_isya='$sholat_isya',
                    sholat_tahajud=$sholat_tahajud, sholat_witir=$sholat_witir, sholat_qobliyah_subuh=$sholat_qobliyah_subuh, sholat_dhuha=$sholat_dhuha, sholat_qobli_dhuhur=$sholat_qobli_dhuhur,
                    sholat_bakdiyah_dhuhur=$sholat_bakdiyah_dhuhur, sholat_qobliyah_ashar=$sholat_qobliyah_ashar, sholat_bakdiyah_maghrib=$sholat_bakdiyah_maghrib, sholat_qobliyah_isya=$sholat_qobliyah_isya, sholat_bakdiyah_isya=$sholat_bakdiyah_isya,
                    puasa_senin=$puasa_senin, puasa_kamis=$puasa_kamis, is_haid=$is_haid, status_validasi='Pending'
                    WHERE id = $existing_id";
            $pesan_sukses = "Laporan ibadah harian tanggal $tanggal berhasil diperbarui! Menunggu validasi Musyrif.";
        } else {
            $sql = "INSERT INTO ibadah_harian_santri (santri_id, tanggal, sholat_subuh, sholat_dhuhur, sholat_ashar, sholat_maghrib, sholat_isya,
                    sholat_tahajud, sholat_witir, sholat_qobliyah_subuh, sholat_dhuha, sholat_qobli_dhuhur, sholat_bakdiyah_dhuhur, sholat_qobliyah_ashar, sholat_bakdiyah_maghrib, sholat_qobliyah_isya, sholat_bakdiyah_isya,
                    puasa_senin, puasa_kamis, is_haid, status_validasi) VALUES (
                    $santri_id, '$tanggal', '$sholat_subuh', '$sholat_dhuhur', '$sholat_ashar', '$sholat_maghrib', '$sholat_isya',
                    $sholat_tahajud, $sholat_witir, $sholat_qobliyah_subuh, $sholat_dhuha, $sholat_qobli_dhuhur, $sholat_bakdiyah_dhuhur, $sholat_qobliyah_ashar, $sholat_bakdiyah_maghrib, $sholat_qobliyah_isya, $sholat_bakdiyah_isya,
                    $puasa_senin, $puasa_kamis, $is_haid, 'Pending')";
            $pesan_sukses = "Laporan ibadah harian tanggal $tanggal berhasil disimpan! Menunggu validasi Musyrif.";
        }

        if (!$conn->query($sql)) {
            $pesan_error = "Gagal menyimpan laporan: " . $conn->error;
        }
    }

    $today_date = date('Y-m-d');
    $current_report = null;
    $res_report = $conn->query("SELECT * FROM ibadah_harian_santri WHERE santri_id = $santri_id AND tanggal = '$today_date'");
    if ($res_report && $res_report->num_rows > 0) {
        $current_report = $res_report->fetch_assoc();
    }

    $past_reports = [];
    $res_past = $conn->query("SELECT * FROM ibadah_harian_santri WHERE santri_id = $santri_id ORDER BY tanggal DESC LIMIT 10");
    if ($res_past) {
        while($row = $res_past->fetch_assoc()) {
            $past_reports[] = $row;
        }
    }

} else {
    // --- LOGIC FOR DASHBOARD SANTRI (NEW MOBILE LAYOUT) ---
    $active_menu = 'dashboard_santri';

    // 1. Data Kalender Akademik untuk Embedded View
    $holiday_categories = [
        'Hari Besar Nasional' => ['HUT' => 'HUT RI', 'HBI' => 'Hari Buruh Internasional', 'PCS' => 'Lahir Pancasila'],
        'Hari Besar Islam' => ['MLD' => 'Maulud Nabi saw', 'IMN' => "Isro' Mi'roj Nabi saw", 'IDF' => 'Idul Fitri', 'IDA' => 'Idul Adha', 'TBI' => 'Tahun Baru Islam'],
        'Hari Besar Agama Lain' => ['TBM' => 'Tahun Baru Masehi', 'NTL' => 'Natal', 'IML' => 'Imlek', 'NYP' => 'Nyepi', 'WFT' => 'Kematian Yudas Escariot', 'PSK' => 'Paskah', 'ISA' => 'Kenaikan Isa as', 'WSK' => 'Waisak'],
        'Agenda Akademik' => ['KS1' => 'Kedatangan Santri Awal S1', 'KS2' => 'Kedatangan Santri Awal S2', 'AS1' => 'Awal Semester 1', 'AS2' => 'Awal Semester 2', 'UJK' => 'Ujian Kesetaraan', 'PLH' => 'Penjemputan Libur HR', 'KPH' => 'Kedatangan Pasca HR', 'RP1' => 'Raport S1', 'RP2' => 'Raport S2'],
        'Agenda Akademik Panjang' => ['KPP' => 'Permulaan Puasa', 'LHR' => 'Libur Hari Raya', 'KT1' => 'Tengah S1', 'KT2' => 'Tengah S2', 'UA1' => 'UAS 1', 'UA2' => 'UAS 2', 'LS1' => 'Libur S1', 'LS2' => 'Libur S2']
    ];

    $holiday_descriptions = ['AHD' => 'Hari Ahad 1 Bulan'];
    foreach ($holiday_categories as $cat => $items) {
        foreach ($items as $code => $desc) {
            $holiday_descriptions[$code] = $desc;
        }
    }

    $res_ahad = $conn->query("SELECT tanggal FROM kalender_akademik WHERE status_hari = 'AHD' LIMIT 1");
    $val_ahad = ($res_ahad && $res_ahad->num_rows > 0) ? $res_ahad->fetch_assoc()['tanggal'] : "";

    $overrides = [];
    $res_overrides = $conn->query("SELECT tanggal, status_hari FROM kalender_akademik");
    if ($res_overrides) {
        while ($row = $res_overrides->fetch_assoc()) {
            $overrides[$row['tanggal']] = $row['status_hari'];
        }
    }

    if (!empty($val_ahad)) {
        $start_date = "2026-07-01";
        $end_date = "2027-07-31";
        $target_ahad_ts = strtotime($val_ahad);
        $current_ts = strtotime($start_date);
        $end_ts = strtotime($end_date);
        
        while ($current_ts <= $end_ts) {
            $date_str = date('Y-m-d', $current_ts);
            $m_num = (int)date('m', $current_ts);
            $d_num = (int)date('d', $current_ts);
            $y_num = (int)date('Y', $current_ts);
            
            if (checkdate($m_num, $d_num, $y_num)) {
                $diff_seconds = $current_ts - $target_ahad_ts;
                $diff_days = round($diff_seconds / 86400);
                if ($diff_days >= 0 && $diff_days % 7 == 0) {
                    if (!isset($overrides[$date_str]) || $overrides[$date_str] === 'AHD') {
                        $overrides[$date_str] = 'AHD';
                    }
                }
            }
            $current_ts = strtotime("+1 day", $current_ts);
        }
    }

    $months = [
        'JULI 2026', 'AGUSTUS 2026', 'SEPTEMBER 2026', 'OKTOBER 2026', 'NOVEMBER 2026', 'DESEMBER 2026',
        'JANUARI 2027', 'FEBRUARI 2027', 'MARET 2027', 'APRIL 2027', 'MEI 2027', 'JUNI 2027', 'JULI 2027'
    ];

    $month_map = [
        'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
        'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6
    ];

    // 1. Layanan Utama Santri
    $services_santri = [
        ['title' => 'Ibadah Harian', 'href' => 'ruang-santri.php?view=ibadah_harian', 'icon' => 'fas fa-mosque', 'bg' => 'from-emerald-500 to-teal-700'],
        ['title' => 'Rapor PKBM', 'href' => 'santri-rapot.php?tab=pkbm', 'icon' => 'fas fa-file-invoice', 'bg' => 'from-blue-600 to-indigo-700'],
        ['title' => 'Rapor Diniyah', 'href' => 'santri-rapot.php?tab=diniyah', 'icon' => 'fas fa-book-quran', 'bg' => 'from-purple-600 to-indigo-700'],
        ['title' => 'Kalender', 'href' => 'kalender-akademik.php', 'icon' => 'fas fa-calendar-days', 'bg' => 'from-cyan-600 to-blue-700'],
        ['title' => 'Jadwal Ajar', 'href' => 'admin-jadwal-pelajaran.php', 'icon' => 'fas fa-clock', 'bg' => 'from-sky-500 to-blue-600'],
        ['title' => 'Info Santri', 'href' => 'pengumuman.php', 'icon' => 'fas fa-bullhorn', 'bg' => 'from-amber-500 to-orange-600']
    ];

    // 2. Daftar Mata Pelajaran / Modul Belajar E-Learning
    $subjects = [
        ['name' => 'B. Indonesia', 'param' => 'Bahasa Indonesia', 'icon' => 'fas fa-book-open', 'desc' => 'Bahasa Indonesia & Literasi', 'bg' => 'from-rose-500 to-red-700'],
        ['name' => 'Matematika', 'param' => 'Matematika', 'icon' => 'fas fa-square-root-variable', 'desc' => 'Matematika & Logika', 'bg' => 'from-blue-500 to-indigo-700'],
        ['name' => 'IPA', 'param' => 'IPA', 'icon' => 'fas fa-flask', 'desc' => 'Ilmu Pengetahuan Alam', 'bg' => 'from-emerald-500 to-teal-700'],
        ['name' => 'Fisika', 'param' => 'Fisika', 'icon' => 'fas fa-atom', 'desc' => 'Fisika Terapan', 'bg' => 'from-indigo-500 to-purple-700'],
        ['name' => 'Kimia', 'param' => 'Kimia', 'icon' => 'fas fa-vial', 'desc' => 'Kimia & Reaksi', 'bg' => 'from-violet-500 to-purple-700'],
        ['name' => 'Biologi', 'param' => 'Biologi', 'icon' => 'fas fa-dna', 'desc' => 'Biologi Sains', 'bg' => 'from-teal-500 to-emerald-700'],
        ['name' => 'IPS', 'param' => 'IPS', 'icon' => 'fas fa-globe-asia', 'desc' => 'Ilmu Pengetahuan Sosial', 'bg' => 'from-amber-500 to-orange-700'],
        ['name' => 'Ekonomi', 'param' => 'Ekonomi', 'icon' => 'fas fa-chart-line', 'desc' => 'Ekonomi & Manajemen', 'bg' => 'from-cyan-600 to-blue-700'],
        ['name' => 'Geografi', 'param' => 'Geografi', 'icon' => 'fas fa-map-marked-alt', 'desc' => 'Geografi & Kebumian', 'bg' => 'from-emerald-600 to-teal-800'],
        ['name' => 'Sejarah', 'param' => 'Sejarah', 'icon' => 'fas fa-landmark', 'desc' => 'Sejarah Kebangsaan', 'bg' => 'from-amber-600 to-amber-800'],
        ['name' => 'Sosiologi', 'param' => 'Sosiologi', 'icon' => 'fas fa-users', 'desc' => 'Sosiologi & Masyarakat', 'bg' => 'from-sky-500 to-blue-600'],
        ['name' => 'English', 'param' => 'Bahasa Inggris', 'icon' => 'fas fa-comments', 'desc' => 'Bahasa Inggris / English', 'bg' => 'from-blue-600 to-indigo-800'],
        ['name' => 'PPKn', 'param' => 'PPKn', 'icon' => 'fas fa-balance-scale', 'desc' => 'Pendidikan Pancasila & Kewarganegaraan', 'bg' => 'from-red-600 to-rose-700'],
        ['name' => 'Seni Budaya', 'param' => 'Seni Budaya', 'icon' => 'fas fa-palette', 'desc' => 'Seni Budaya & Apresiasi Karya', 'bg' => 'from-fuchsia-500 to-purple-700'],
        ['name' => 'Solopreneur', 'param' => 'Solopreneur', 'icon' => 'fas fa-lightbulb', 'desc' => 'Kewirausahaan Mandiri (AI)', 'bg' => 'from-rose-500 to-amber-600']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ruang Siswa / Santri | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .theme-teal { background-color: #0d8276; }
        .theme-teal-dark { background-color: #0b6f65; }
        .theme-bg-mint { background-color: #e1f5f2; }
        .grid-cell {
            width: 20px;
            height: 14px;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php if ($view === 'ibadah_harian'): ?>
        <!-- ================================================== -->
        <!-- HEADER FORM IBADAH HARIAN                          -->
        <!-- ================================================== -->
        <header class="h-16 bg-[#0b8478] text-white shadow-md flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <a href="ruang-santri.php" class="text-white hover:text-teal-200 text-xs sm:text-sm font-bold flex items-center gap-1.5 bg-white/15 px-3 py-1.5 rounded-xl transition">
                    <i class="fas fa-arrow-left"></i> Beranda Santri
                </a>
            </div>
            <h1 class="text-sm font-bold tracking-wide">Laporan Ibadah Harian</h1>
            <div class="w-8"></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#e1f5f2] p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-3xl mx-auto">
                
                <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl mb-5 shadow-sm flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_sukses ?></span>
                </div>
                <?php endif; ?>

                <?php if(!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl mb-5 shadow-sm flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-rose-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_error ?></span>
                </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl shadow-sm border border-teal-100 p-5 sm:p-6 mb-6">
                    <div class="border-b border-slate-100 pb-3 mb-5 flex items-center justify-between">
                        <div>
                            <h2 class="font-extrabold text-slate-800 text-base sm:text-lg">Form Ibadah Santri</h2>
                            <p class="text-xs text-slate-400">Tanggal: <?= date('d F Y') ?></p>
                        </div>
                        <span class="w-9 h-9 rounded-2xl bg-teal-50 text-[#0d8276] flex items-center justify-center text-lg">
                            <i class="fas fa-mosque"></i>
                        </span>
                    </div>

                    <form action="ruang-santri.php?view=ibadah_harian" method="POST" class="space-y-6">
                        <input type="hidden" name="tanggal" value="<?= date('Y-m-d') ?>">

                        <?php if ($is_female): ?>
                        <div class="bg-pink-50 border border-pink-100 rounded-2xl p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 bg-pink-100 rounded-xl flex items-center justify-center text-pink-600">
                                    <i class="fas fa-venus text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-pink-900">Sedang Haid / Uzur Syar'i</h3>
                                    <p class="text-[11px] text-pink-600">Aktifkan jika sedang dalam masa uzur sholat.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_haid_toggle" name="is_haid" value="1" <?= ($current_report && $current_report['is_haid'] == 1) ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-500"></div>
                            </label>
                        </div>
                        <?php endif; ?>

                        <div id="ibadah_fields_container" class="space-y-6">
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-3">
                                    <i class="fas fa-clock text-[#0d8276] mr-1.5"></i> Sholat Wajib 5 Waktu
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <?php 
                                    $sholat_wajib_opts = ['Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\'i'];
                                    $sholat_names = ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya'];
                                    foreach($sholat_names as $s_name): ?>
                                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-100">
                                        <label class="block text-xs font-bold text-slate-800 mb-1.5 capitalize">Sholat <?= $s_name ?></label>
                                        <select name="sholat_<?= $s_name ?>" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-teal-500">
                                            <?php foreach($sholat_wajib_opts as $opt): ?>
                                                <option value="<?= $opt ?>" <?= ($current_report && $current_report['sholat_'.$s_name] == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-3">
                                    <i class="fas fa-star text-amber-500 mr-1.5"></i> Sholat Sunnah
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                                    <?php 
                                    $sholat_sunnah_names = ['tahajud', 'witir', 'qobliyah_subuh', 'dhuha', 'qobli_dhuhur', 'bakdiyah_dhuhur', 'qobliyah_ashar', 'bakdiyah_maghrib', 'qobliyah_isya', 'bakdiyah_isya'];
                                    foreach($sholat_sunnah_names as $ss_name): ?>
                                    <label class="flex items-center p-2.5 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:bg-teal-50/50 transition">
                                        <input type="checkbox" name="sholat_<?= $ss_name ?>" value="1" <?= ($current_report && $current_report['sholat_'.$ss_name] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-[#0d8276] rounded focus:ring-teal-500 border-slate-300">
                                        <span class="text-xs font-semibold text-slate-700 ml-2"><?= ucwords(str_replace('_', ' ', $ss_name)) ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-3">
                                    <i class="fas fa-moon text-purple-500 mr-1.5"></i> Puasa Sunnah
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <label class="flex items-center px-4 py-2.5 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:bg-teal-50/50 transition">
                                        <input type="checkbox" name="puasa_senin" value="1" <?= ($current_report && $current_report['puasa_senin'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-[#0d8276] rounded focus:ring-teal-500 border-slate-300">
                                        <span class="text-xs font-bold text-slate-700 ml-2">Puasa Senin</span>
                                    </label>
                                    <label class="flex items-center px-4 py-2.5 bg-slate-50 rounded-2xl border border-slate-100 cursor-pointer hover:bg-teal-50/50 transition">
                                        <input type="checkbox" name="puasa_kamis" value="1" <?= ($current_report && $current_report['puasa_kamis'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-[#0d8276] rounded focus:ring-teal-500 border-slate-300">
                                        <span class="text-xs font-bold text-slate-700 ml-2">Puasa Kamis</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="w-full sm:w-auto bg-[#0d8276] hover:bg-[#0b6f65] text-white font-bold py-3 px-8 rounded-2xl shadow-md transition flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i> Simpan Laporan Ibadah
                            </button>
                        </div>
                    </form>
                </div>

                <!-- RIWAYAT LAPORAN -->
                <div class="bg-white rounded-3xl shadow-sm border border-teal-100 overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100">
                        <h2 class="font-bold text-xs sm:text-sm text-slate-800">Riwayat 10 Hari Terakhir</h2>
                    </div>
                    <div class="overflow-x-auto p-2">
                        <table class="w-full text-left text-xs divide-y divide-slate-100">
                            <thead>
                                <tr class="text-slate-400 uppercase font-black text-[10px]">
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Sholat Wajib</th>
                                    <th class="p-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($past_reports) > 0): ?>
                                    <?php foreach($past_reports as $report): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-3 font-bold text-slate-800 whitespace-nowrap"><?= date('d M Y', strtotime($report['tanggal'])) ?></td>
                                        <td class="p-3 text-slate-600">
                                            <?php if ($report['is_haid']): ?>
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-pink-100 text-pink-700">Uzur Haid</span>
                                            <?php else: ?>
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px]"><?= substr($report['sholat_subuh'], 0, 6) ?></span>
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px]"><?= substr($report['sholat_dhuhur'], 0, 6) ?></span>
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px]"><?= substr($report['sholat_ashar'], 0, 6) ?></span>
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px]"><?= substr($report['sholat_maghrib'], 0, 6) ?></span>
                                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px]"><?= substr($report['sholat_isya'], 0, 6) ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            <?php 
                                            $st = $report['status_validasi'] ?? 'Pending';
                                            $st_color = ($st === 'Disetujui') ? 'bg-emerald-100 text-emerald-800' : (($st === 'Ditolak') ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800');
                                            ?>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $st_color ?>"><?= $st ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan='3' class='text-center py-6 text-slate-400 italic'>Belum ada riwayat laporan ibadah.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>

        <?php else: ?>
        <!-- ================================================== -->
        <!-- TAMPILAN UTAMA RUANG SANTRI (SERAGAM DENGAN PEGAWAI)-->
        <!-- ================================================== -->

        <!-- TOP HEADER RUANG SANTRI (HIJAU SAMPAI DI BAWAH SEARCH BAR) -->
        <header class="bg-[#0b8478] text-white shadow-md z-20 flex-shrink-0 border-b border-teal-700/60">
            <!-- BARIS ATAS: TITLE RUANG SANTRI & PROFIL (TANPA HUMBURGER) -->
            <div class="h-16 flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center space-x-2">
                    <h2 class="font-extrabold text-white text-base sm:text-lg tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Ruang Santri
                    </h2>
                    <span class="hidden md:inline-block text-[11px] font-medium bg-teal-800/80 text-teal-200 px-2 py-0.5 rounded-full border border-teal-600/40">SADIGS 4.0</span>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if (isset($_SESSION['is_impersonating']) || isset($_SESSION['yayasan_logged_in']) || isset($_SESSION['yayasan2_logged_in'])): ?>
                    <a href="yayasan2/index.php" class="text-xs text-white hover:text-teal-100 font-bold bg-white/15 hover:bg-white/25 border border-white/20 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-xs">
                        <i class="fas fa-landmark text-teal-200"></i>
                        <span class="hidden sm:inline">Ruang Yayasan</span>
                    </a>
                    <?php endif; ?>
                    <a href="santri-profil.php" title="Profil Ananda (<?= htmlspecialchars($santri_nama) ?>)" class="flex items-center group focus:outline-none">
                        <div class="w-10 h-10 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-sm shadow-md border-2 border-white/90 overflow-hidden flex-shrink-0 group-hover:scale-105 transition-transform">
                            <?php if (!empty($foto_santri)): ?>
                                <img src="<?= htmlspecialchars($foto_santri) ?>" alt="Profil" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user-graduate text-base text-[#0b8478]"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            </div>

            <!-- BARIS BAWAH: SEARCH BAR LENGKAP -->
            <div class="pb-4 px-4 sm:px-6">
                <div class="relative w-full max-w-xl">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" id="appSearchInput" placeholder="Cari mata pelajaran / modul santri..." class="w-full pl-10 pr-4 py-2 bg-white text-slate-800 placeholder-slate-400 text-xs font-semibold rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-300 shadow-sm transition">
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE BODY -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- 1. KARTU MENU LAYANAN SANTRI (SQUIRCLE ANDROID) -->
                <div class="bg-white rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80">
                    <div class="flex items-center gap-2 mb-4 px-1">
                        <i class="fas fa-star text-sm text-teal-600"></i>
                        <h3 class="font-extrabold text-xs sm:text-sm text-slate-700 uppercase tracking-wider">
                            Layanan Utama Santri
                        </h3>
                    </div>

                    <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-y-5 sm:gap-y-6 gap-x-2 sm:gap-x-4 items-start justify-items-center">
                        <?php foreach ($services_santri as $item): ?>
                        <div class="app-item flex flex-col items-center w-full text-center select-none" data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>">
                            <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center w-full focus:outline-none group">
                                <div class="android-icon relative w-14 h-14 sm:w-16 sm:h-16 rounded-[22px] sm:rounded-[24px] bg-gradient-to-br <?= $item['bg'] ?> text-white shadow-sm flex items-center justify-center text-xl sm:text-2xl border border-white/25">
                                    <i class="<?= $item['icon'] ?>"></i>
                                </div>
                                <span class="text-[11px] sm:text-xs font-semibold text-slate-700 group-hover:text-[#0b8478] mt-2 tracking-tight text-center leading-tight line-clamp-2 max-w-[88px] sm:max-w-[105px] min-h-[28px] transition-colors">
                                    <?= htmlspecialchars($item['title']) ?>
                                </span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. KARTU MATA PELAJARAN / MODUL E-LEARNING (SQUIRCLE ANDROID) -->
                <div class="bg-white rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80">
                    <div class="flex items-center gap-2 mb-4 px-1">
                        <i class="fas fa-graduation-cap text-sm text-blue-600"></i>
                        <h3 class="font-extrabold text-xs sm:text-sm text-slate-700 uppercase tracking-wider">
                            Mata Pelajaran & E-Learning (AI)
                        </h3>
                    </div>

                    <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-y-5 sm:gap-y-6 gap-x-2 sm:gap-x-4 items-start justify-items-center">
                        <?php foreach ($subjects as $s): ?>
                        <div class="app-item flex flex-col items-center w-full text-center select-none cursor-pointer" data-title="<?= strtolower(htmlspecialchars($s['name'] . ' ' . $s['desc'])) ?>" onclick="showSubjectModal('<?= addslashes($s['name']) ?>', '<?= addslashes($s['desc']) ?>', '<?= $s['icon'] ?>', '<?= addslashes($s['param'] ?? $s['name']) ?>')">
                            <div class="flex flex-col items-center w-full focus:outline-none group">
                                <div class="android-icon relative w-14 h-14 sm:w-16 sm:h-16 rounded-[22px] sm:rounded-[24px] bg-gradient-to-br <?= $s['bg'] ?> text-white shadow-sm flex items-center justify-center text-xl sm:text-2xl border border-white/25">
                                    <i class="<?= $s['icon'] ?>"></i>
                                </div>
                                <span class="text-[11px] sm:text-xs font-semibold text-slate-700 group-hover:text-[#0b8478] mt-2 tracking-tight text-center leading-tight line-clamp-2 max-w-[88px] sm:max-w-[105px] min-h-[28px] transition-colors">
                                    <?= htmlspecialchars($s['name']) ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- EMPTY STATE PENCARIAN -->
                <div id="noAppFound" class="hidden text-center py-12 bg-white rounded-3xl border border-slate-200">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                        <i class="fas fa-search"></i>
                    </div>
                    <p class="font-bold text-slate-600 text-xs">Mata pelajaran atau menu tidak ditemukan</p>
                </div>

                <!-- 3. KALENDER AKADEMIK EMBEDDED -->
                <div class="bg-white rounded-3xl p-5 sm:p-7 shadow-sm border border-slate-200/80" id="kalender-section">
                    <div class="flex items-center justify-between mb-4 px-1">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-calendar-alt text-sm text-cyan-600"></i>
                            <h3 class="font-extrabold text-xs sm:text-sm text-slate-700 uppercase tracking-wider">
                                Kalender Akademik
                            </h3>
                        </div>
                        <a href="kalender-akademik.php" class="text-xs font-bold text-[#0b8478] hover:underline flex items-center gap-1">
                            Lihat Lengkap <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-200">
                        <div class="min-w-[700px]">
                            <table class="w-full border-collapse border border-gray-300">
                                <thead>
                                    <tr class="bg-amber-900 text-white font-bold text-[10px]">
                                        <th class="border border-amber-950 px-1 py-1.5 text-center" rowspan="2" style="width: 25px;">No</th>
                                        <th class="border border-amber-950 px-2 py-1.5 text-left" rowspan="2" style="width: 110px;">BULAN</th>
                                        <th class="border border-amber-950 py-1.5 text-center" colspan="31">TANGGAL</th>
                                    </tr>
                                    <tr class="bg-amber-800 text-white font-bold text-[9px]">
                                        <?php for ($d = 1; $d <= 31; $d++): ?>
                                            <th class="border border-amber-900 text-center py-1" style="width: 20px;"><?= $d ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach ($months as $m): 
                                        $parts = explode(' ', $m);
                                        $month_name = $parts[0];
                                        $year = (int)$parts[1];
                                        $month_num = $month_map[$month_name];
                                    ?>
                                        <tr>
                                            <td class="border border-gray-300 text-center font-bold bg-amber-50/80 text-amber-950 py-0.5 text-[9px]"><?= $no++ ?></td>
                                            <td class="border border-gray-300 font-bold bg-amber-50/80 text-amber-950 px-2 py-0.5 whitespace-nowrap text-left text-[9px]"><?= $m ?></td>
                                            <?php for ($day = 1; $day <= 31; $day++): 
                                                if (!checkdate($month_num, $day, $year)):
                                            ?>
                                                    <td class="border border-gray-300 grid-cell bg-black"></td>
                                            <?php 
                                                else:
                                                    $date_str = sprintf("%04d-%02d-%02d", $year, $month_num, $day);
                                                    if (isset($overrides[$date_str])):
                                                        $status_code = $overrides[$date_str];
                                                        $desc = $holiday_descriptions[$status_code] ?? 'Hari Libur/Agenda';
                                                        
                                                        $bg_color = 'bg-gray-400 text-white';
                                                        $show_code = true;
                                                        
                                                        if ($status_code === 'AHD') {
                                                            $bg_color = 'bg-red-700 text-white font-extrabold';
                                                        } elseif (in_array($status_code, ['HUT', 'HBI', 'PCS'])) {
                                                            $bg_color = 'bg-red-600 text-white font-bold';
                                                        } elseif (in_array($status_code, ['MLD', 'IMN', 'IDF', 'IDA', 'TBI'])) {
                                                            $bg_color = 'bg-green-600 text-white font-bold';
                                                        } elseif (in_array($status_code, ['KS1', 'KS2', 'AS1', 'AS2', 'UJK', 'PLH', 'KPH', 'RP1', 'RP2', 'KPP', 'LHR', 'KT1', 'KT2', 'UA1', 'UA2', 'LS1', 'LS2'])) {
                                                            $bg_color = 'bg-sky-400 text-black font-bold';
                                                        } elseif (in_array($status_code, ['TBM', 'NTL', 'IML', 'NYP', 'WFT', 'PSK', 'ISA', 'WSK'])) {
                                                            $bg_color = 'bg-gray-400 text-transparent';
                                                            $show_code = false;
                                                        }
                                            ?>
                                                        <td class="border border-gray-300 grid-cell <?= $bg_color ?> text-center align-middle text-[7px] cursor-help" title="<?= $status_code ?> - <?= htmlspecialchars($desc) ?>">
                                                            <?= $show_code ? $status_code : '' ?>
                                                        </td>
                                            <?php 
                                                    else:
                                            ?>
                                                        <td class="border border-gray-300 grid-cell bg-white"></td>
                                            <?php 
                                                    endif;
                                                endif;
                                            endfor; 
                                            ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- SUBJECT DETAIL MODAL -->
        <div id="subjectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 transition-opacity">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 relative transform transition-all animate-in fade-in zoom-in duration-200">
                <button onclick="closeSubjectModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
                <div class="flex items-center space-x-4 mb-4">
                    <div id="modalIconBox" class="w-14 h-14 rounded-2xl bg-[#0b8478] text-white flex items-center justify-center text-2xl shadow-md">
                        <i id="modalIcon" class="fas fa-book"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-lg font-black text-slate-900">Mata Pelajaran</h3>
                        <p id="modalDesc" class="text-xs text-slate-500 mt-0.5">Deskripsi modul belajar</p>
                    </div>
                </div>
                <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200 text-xs text-slate-700 mb-4">
                    <p class="font-semibold text-[#0b8478] mb-1"><i class="fas fa-graduation-cap mr-1"></i> E-Learning & Bimbingan AI</p>
                    <p class="text-[11px] text-slate-600 leading-relaxed">Pelajari modul rangkuman, tonton video materi, kerjakan LKS & latihan soal, serta konsultasi 24 jam dengan Ustadz AI.</p>
                </div>
                <div class="space-y-2">
                    <a id="modalStudyBtn" href="santri-belajar.php?mapel=Sosiologi" class="w-full bg-[#0b8478] hover:bg-teal-700 text-white text-xs font-black py-3 px-4 rounded-2xl text-center shadow-md transition flex items-center justify-center gap-2">
                        <i class="fas fa-book-reader text-sm"></i> Buka Ruang Belajar & Ustadz AI
                    </a>
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <a href="santri-rapot.php?tab=pkbm" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2 px-3 rounded-xl text-center transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-file-invoice"></i> Rapor PKBM
                        </a>
                        <a href="santri-rapot.php?tab=diniyah" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2 px-3 rounded-xl text-center transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-book-quran"></i> Rapor Diniyah
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function showSubjectModal(title, desc, iconClass, param) {
                const mapelTarget = param || title;
                document.getElementById('modalTitle').innerText = title;
                document.getElementById('modalDesc').innerText = desc;
                document.getElementById('modalIcon').className = iconClass;
                document.getElementById('modalStudyBtn').href = 'santri-belajar.php?mapel=' + encodeURIComponent(mapelTarget) + '&bab=1';
                document.getElementById('subjectModal').classList.remove('hidden');
            }
            function closeSubjectModal() {
                document.getElementById('subjectModal').classList.add('hidden');
            }

            // Live Search Filter
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('appSearchInput');
                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const q = this.value.toLowerCase().trim();
                        const appItems = document.querySelectorAll('.app-item');
                        let visibleCount = 0;

                        appItems.forEach(item => {
                            const title = item.getAttribute('data-title') || '';
                            if (title.includes(q)) {
                                item.style.display = '';
                                visibleCount++;
                            } else {
                                item.style.display = 'none';
                            }
                        });

                        const noAppFound = document.getElementById('noAppFound');
                        if (noAppFound) {
                            noAppFound.classList.toggle('hidden', visibleCount > 0);
                        }
                    });
                }
            });
        </script>
        <?php endif; ?>

    </div>

    <!-- MOBILE BOTTOM NAVIGATION BAR -->
    <?php include 'bottombar-santri.php'; ?>
</body>
</html>