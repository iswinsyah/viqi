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

    // 2. Daftar Mata Pelajaran / Modul Belajar (Sesuai Mockup Desain)
    $subjects = [
        ['name' => 'Bahasa', 'icon' => 'fas fa-book-open', 'desc' => 'Bahasa Indonesia & Literasi'],
        ['name' => 'Matematika', 'icon' => 'fas fa-square-root-variable', 'desc' => 'Matematika & Logika'],
        ['name' => 'IPA', 'icon' => 'fas fa-flask', 'desc' => 'Ilmu Pengetahuan Alam'],
        ['name' => 'Fisika', 'icon' => 'fas fa-atom', 'desc' => 'Fisika Terapan'],
        ['name' => 'Kimia', 'icon' => 'fas fa-vial', 'desc' => 'Kimia & Reaksi'],
        ['name' => 'Biologi', 'icon' => 'fas fa-dna', 'desc' => 'Biologi Sains'],
        ['name' => 'IPS', 'icon' => 'fas fa-globe-asia', 'desc' => 'Ilmu Pengetahuan Sosial'],
        ['name' => 'Ekonomi', 'icon' => 'fas fa-chart-line', 'desc' => 'Ekonomi & Manajemen'],
        ['name' => 'Geografi', 'icon' => 'fas fa-map-marked-alt', 'desc' => 'Geografi & Kebumian'],
        ['name' => 'Sejarah', 'icon' => 'fas fa-landmark', 'desc' => 'Sejarah Kebangsaan'],
        ['name' => 'Sosiologi', 'icon' => 'fas fa-users', 'desc' => 'Sosiologi & Masyarakat'],
        ['name' => 'English', 'icon' => 'fas fa-comments', 'desc' => 'Bahasa Inggris / English'],
        ['name' => 'Olahraga', 'icon' => 'fas fa-running', 'desc' => 'Pendidikan Jasmani & Olahraga'],
        ['name' => 'Solopreneur', 'icon' => 'fas fa-lightbulb', 'desc' => 'Kewirausahaan Mandiri']
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
<body class="bg-[#e1f5f2] font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php if ($view === 'ibadah_harian'): ?>
        <!-- ================================================== -->
        <!-- HEADER FORM IBADAH HARIAN                          -->
        <!-- ================================================== -->
        <header class="h-16 bg-[#0d8276] text-white shadow-md flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-santri" class="text-white hover:text-teal-200 md:hidden p-2 rounded-xl focus:outline-none transition">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <a href="ruang-santri.php" class="text-white hover:text-teal-200 text-sm font-bold flex items-center gap-1.5">
                        <i class="fas fa-arrow-left"></i> Beranda
                    </a>
                </div>
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
        <!-- TAMPILAN UTAMA RUANG SANTRI (SESUAI DESAIN USER)   -->
        <!-- ================================================== -->

        <!-- 1. TOP HEADER TEAL DENGAN LOGO SADIGS & PROFILE -->
        <header class="bg-[#0d8276] text-white pt-5 pb-7 px-4 sm:px-6 shadow-sm z-10 flex-shrink-0">
            <div class="max-w-md sm:max-w-xl mx-auto flex items-center justify-between">
                
                <!-- KIRI: LOGO SADIGS & SUBTITLE -->
                <div class="flex items-center space-x-3">
                    <button id="open-sidebar-santri" class="text-white hover:text-teal-200 md:hidden p-1 focus:outline-none transition">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <!-- Brand Icon (Sun/Book/Leaf) -->
                    <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full">
                            <!-- Radiant Sun Petals -->
                            <circle cx="50" cy="45" r="12" fill="#f59e0b" />
                            <path d="M50 12 L55 28 L45 28 Z" fill="#10b981" />
                            <path d="M72 20 L66 34 L58 28 Z" fill="#10b981" />
                            <path d="M84 40 L70 44 L68 36 Z" fill="#10b981" />
                            <path d="M28 20 L42 28 L34 34 Z" fill="#10b981" />
                            <path d="M16 40 L32 36 L30 44 Z" fill="#10b981" />
                            <!-- Open Book Leaves / Foundation -->
                            <path d="M22 64 C35 55, 48 60, 50 68 C52 60, 65 55, 78 64 C76 76, 52 82, 50 82 C48 82, 24 76, 22 64 Z" fill="#0d8276" />
                            <path d="M30 68 C40 62, 48 66, 50 72 C52 66, 60 62, 70 68 C68 76, 52 80, 50 80 C48 80, 32 76, 30 68 Z" fill="#f59e0b" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                        <p class="text-[11px] sm:text-xs text-teal-100 italic font-light tracking-tight mt-0.5">Sistem Administrasi<br class="sm:hidden"> Digital Sekolah</p>
                    </div>
                </div>

                <!-- KANAN: PROFILE CIRLE + LABEL PROFILE -->
                <a href="santri-profil.php" class="flex flex-col items-center group cursor-pointer">
                    <div class="w-12 h-12 rounded-full bg-white text-[#0d8276] flex items-center justify-center font-black text-lg shadow-md group-hover:scale-105 transition-transform overflow-hidden border-2 border-teal-200">
                        <?php if (!empty($foto_santri)): ?>
                            <img src="<?= htmlspecialchars($foto_santri) ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= strtoupper(substr($santri_nama, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs font-bold text-white mt-1 group-hover:text-teal-200 transition-colors">Profile</span>
                </a>

            </div>
        </header>

        <!-- 2. SCROLLABLE MAIN CONTENT BODY (MINT BACKGROUND) -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#e1f5f2] px-3.5 sm:px-6 pt-0 pb-24 md:pb-8">
            <div class="max-w-md sm:max-w-xl mx-auto -mt-3">
                
                <!-- A. KARTU MENU PUTIH (SQUIRCLE GRID 4 KOLOM) -->
                <div class="bg-white rounded-t-[32px] rounded-b-[24px] p-4 sm:p-6 shadow-sm border border-teal-100/60 mb-6">
                    <div class="grid grid-cols-4 gap-y-4 gap-x-2 sm:gap-4 items-start justify-items-center">
                        
                        <?php foreach ($subjects as $s): ?>
                        <div class="flex flex-col items-center group cursor-pointer w-full text-center" onclick="showSubjectModal('<?= addslashes($s['name']) ?>', '<?= addslashes($s['desc']) ?>', '<?= $s['icon'] ?>')">
                            <!-- Squircle Box Button (#0d8276) -->
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-[18px] bg-[#0d8276] group-hover:bg-[#0b6f65] text-white flex items-center justify-center text-xl sm:text-2xl shadow-md shadow-teal-900/10 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                                <i class="<?= $s['icon'] ?>"></i>
                            </div>
                            <!-- Subject Name -->
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0d8276] transition-colors leading-tight">
                                <?= htmlspecialchars($s['name']) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>

                    </div>
                </div>

                <!-- B. KALENDER AKADEMIK SECTION -->
                <div class="mb-6" id="kalender-section">
                    <h2 class="text-xs sm:text-sm font-extrabold text-slate-800 text-center tracking-wider uppercase mb-3">
                        KALENDER AKADEMIK
                    </h2>

                    <div class="bg-white rounded-2xl p-3 sm:p-4 shadow-sm border border-teal-100/80 overflow-x-auto">
                        <div class="min-w-[700px]">
                            <table class="w-full border-collapse border border-gray-300">
                                <thead>
                                    <!-- BARIS 1 (Header Utama Coklat Tua) -->
                                    <tr class="bg-amber-900 text-white font-bold text-[10px]">
                                        <th class="border border-amber-950 px-1 py-1.5 text-center" rowspan="2" style="width: 25px;">No</th>
                                        <th class="border border-amber-950 px-2 py-1.5 text-left" rowspan="2" style="width: 110px;">BULAN</th>
                                        <th class="border border-amber-950 py-1.5 text-center" colspan="31">TANGGAL</th>
                                    </tr>
                                    <!-- BARIS 2 (Angka 1 - 31) -->
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

                        <!-- Mini Legenda Kalender -->
                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-[10px] text-slate-500 font-semibold">
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-700 inline-block"></span> Ahad</div>
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-600 inline-block"></span> Libur Nasional</div>
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-green-600 inline-block"></span> Hari Besar Islam</div>
                            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-400 inline-block"></span> Agenda Akademik</div>
                            <a href="kalender-akademik.php" class="text-[#0d8276] font-bold hover:underline ml-auto flex items-center gap-1">
                                Full Screen <i class="fas fa-external-link-alt text-[9px]"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- SUBJECT DETAIL MODAL -->
        <div id="subjectModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4 transition-opacity">
            <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-teal-100 relative transform transition-all animate-in fade-in zoom-in duration-200">
                <button onclick="closeSubjectModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
                <div class="flex items-center space-x-4 mb-4">
                    <div id="modalIconBox" class="w-14 h-14 rounded-2xl bg-[#0d8276] text-white flex items-center justify-center text-2xl shadow-md">
                        <i id="modalIcon" class="fas fa-book"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-lg font-black text-slate-900">Mata Pelajaran</h3>
                        <p id="modalDesc" class="text-xs text-slate-500 mt-0.5">Deskripsi modul belajar</p>
                    </div>
                </div>
                <div class="bg-[#e1f5f2]/60 p-3.5 rounded-2xl border border-teal-100 text-xs text-slate-700 mb-4">
                    <p class="font-semibold text-[#0d8276] mb-1"><i class="fas fa-graduation-cap mr-1"></i> E-Learning & Bimbingan AI</p>
                    <p class="text-[11px] text-slate-600 leading-relaxed">Pelajari modul rangkuman, tonton video materi, kerjakan LKS & latihan soal, serta konsultasi 24 jam dengan Ustadz AI.</p>
                </div>
                <div class="space-y-2">
                    <a id="modalStudyBtn" href="santri-belajar.php?mapel=Sosiologi" class="w-full bg-[#0d8276] hover:bg-[#0b6f65] text-white text-xs font-black py-3 px-4 rounded-2xl text-center shadow-md shadow-teal-900/10 transition flex items-center justify-center gap-2">
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
            function showSubjectModal(title, desc, iconClass) {
                document.getElementById('modalTitle').innerText = title;
                document.getElementById('modalDesc').innerText = desc;
                document.getElementById('modalIcon').className = iconClass;
                document.getElementById('modalStudyBtn').href = 'santri-belajar.php?mapel=' + encodeURIComponent(title);
                document.getElementById('subjectModal').classList.remove('hidden');
            }
            function closeSubjectModal() {
                document.getElementById('subjectModal').classList.add('hidden');
            }
        </script>
        <?php endif; ?>

    </div>

    <!-- MOBILE BOTTOM NAVIGATION BAR (BERANDA, IBADAH, HAFALAN, KEUANGAN) -->
    <?php include 'bottombar-santri.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-santri');
            const openBtn = document.getElementById('open-sidebar-santri');
            const overlay = document.getElementById('sidebar-overlay-santri');
            if(openBtn && sidebar) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
            if(overlay && sidebar) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
        });
    </script>
</body>
</html>