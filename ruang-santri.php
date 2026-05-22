<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';
$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$view = $_GET['view'] ?? 'default';

// --- TABLE CREATION FOR IBADAH HARIAN ---
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
    setor_surat_id INT NULL,
    setor_ayat_dari INT NULL,
    setor_ayat_sampai INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (santri_id, tanggal)
)");

// --- SURAH LIST (HARDCODED FOR NOW) ---
$surah_list = [
    1 => "Al-Fatihah", 2 => "Al-Baqarah", 3 => "Ali 'Imran", 4 => "An-Nisa'", 5 => "Al-Ma'idah",
    6 => "Al-An'am", 7 => "Al-A'raf", 8 => "Al-Anfal", 9 => "At-Taubah", 10 => "Yunus",
    11 => "Hud", 12 => "Yusuf", 13 => "Ar-Ra'd", 14 => "Ibrahim", 15 => "Al-Hijr",
    16 => "An-Nahl", 17 => "Al-Isra'", 18 => "Al-Kahf", 19 => "Maryam", 20 => "Taha",
    21 => "Al-Anbiya'", 22 => "Al-Hajj", 23 => "Al-Mu'minun", 24 => "An-Nur", 25 => "Al-Furqan",
    26 => "Asy-Syu'ara'", 27 => "An-Naml", 28 => "Al-Qasas", 29 => "Al-'Ankabut", 30 => "Ar-Rum",
    31 => "Luqman", 32 => "As-Sajdah", 33 => "Al-Ahzab", 34 => "Saba'", 35 => "Fatir",
    36 => "Ya-Sin", 37 => "As-Saffat", 38 => "Sad", 39 => "Az-Zumar", 40 => "Ghafir",
    41 => "Fussilat", 42 => "Asy-Syura", 43 => "Az-Zukhruf", 44 => "Ad-Dukhan", 45 => "Al-Jathiyah",
    46 => "Al-Ahqaf", 47 => "Muhammad", 48 => "Al-Fath", 49 => "Al-Hujurat", 50 => "Qaf",
    51 => "Az-Zariyat", 52 => "At-Tur", 53 => "An-Najm", 54 => "Al-Qamar", 55 => "Ar-Rahman",
    56 => "Al-Waqi'ah", 57 => "Al-Hadid", 58 => "Al-Mujadilah", 59 => "Al-Hasyr", 60 => "Al-Mumtahanah",
    61 => "As-Saff", 62 => "Al-Jumu'ah", 63 => "Al-Munafiqun", 64 => "At-Taghabun", 65 => "At-Talaq",
    66 => "At-Tahrim", 67 => "Al-Mulk", 68 => "Al-Qalam", 69 => "Al-Haqqah", 70 => "Al-Ma'arij",
    71 => "Nuh", 72 => "Al-Jinn", 73 => "Al-Muzzammil", 74 => "Al-Muddaththir", 75 => "Al-Qiyamah",
    76 => "Al-Insan", 77 => "Al-Mursalat", 78 => "An-Naba'", 79 => "An-Nazi'at", 80 => "'Abasa",
    81 => "At-Takwir", 82 => "Al-Infitar", 83 => "Al-Mutaffifin", 84 => "Al-Insyiqaq", 85 => "Al-Buruj",
    86 => "At-Tariq", 87 => "Al-A'la", 88 => "Al-Ghasyiyah", 89 => "Al-Fajr", 90 => "Al-Balad",
    91 => "Asy-Syams", 92 => "Al-Layl", 93 => "Ad-Duha", 94 => "Al-Insyirah", 95 => "At-Tin",
    96 => "Al-'Alaq", 97 => "Al-Qadr", 98 => "Al-Bayyinah", 99 => "Az-Zalzalah", 100 => "Al-'Adiyat",
    101 => "Al-Qari'ah", 102 => "At-Takasur", 103 => "Al-'Asr", 104 => "Al-Humazah", 105 => "Al-Fil",
    106 => "Quraisy", 107 => "Al-Ma'un", 108 => "Al-Kausar", 109 => "Al-Kafirun", 110 => "An-Nasr",
    111 => "Al-Masad", 112 => "Al-Ikhlas", 113 => "Al-Falaq", 114 => "An-Nas"
];

// --- LOGIC FOR IBADAH HARIAN VIEW ---
if ($view === 'ibadah_harian') {
    $active_menu = 'ibadah_harian';
    $pesan_sukses = '';
    $pesan_error = '';

    // Handle Form Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $tanggal = $conn->real_escape_string($_POST['tanggal']);
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

        $setor_surat_id = !empty($_POST['setor_surat_id']) ? (int)$_POST['setor_surat_id'] : 'NULL';
        $setor_ayat_dari = !empty($_POST['setor_ayat_dari']) ? (int)$_POST['setor_ayat_dari'] : 'NULL';
        $setor_ayat_sampai = !empty($_POST['setor_ayat_sampai']) ? (int)$_POST['setor_ayat_sampai'] : 'NULL';

        // Check if entry for this date already exists
        $check_sql = "SELECT id FROM ibadah_harian_santri WHERE santri_id = $santri_id AND tanggal = '$tanggal'";
        $check_res = $conn->query($check_sql);

        if ($check_res && $check_res->num_rows > 0) {
            $existing_id = $check_res->fetch_assoc()['id'];
            $sql = "UPDATE ibadah_harian_santri SET 
                    sholat_subuh='$sholat_subuh', sholat_dhuhur='$sholat_dhuhur', sholat_ashar='$sholat_ashar', sholat_maghrib='$sholat_maghrib', sholat_isya='$sholat_isya',
                    sholat_tahajud=$sholat_tahajud, sholat_witir=$sholat_witir, sholat_qobliyah_subuh=$sholat_qobliyah_subuh, sholat_dhuha=$sholat_dhuha, sholat_qobli_dhuhur=$sholat_qobli_dhuhur,
                    sholat_bakdiyah_dhuhur=$sholat_bakdiyah_dhuhur, sholat_qobliyah_ashar=$sholat_qobliyah_ashar, sholat_bakdiyah_maghrib=$sholat_bakdiyah_maghrib, sholat_qobliyah_isya=$sholat_qobliyah_isya, sholat_bakdiyah_isya=$sholat_bakdiyah_isya,
                    puasa_senin=$puasa_senin, puasa_kamis=$puasa_kamis,
                    setor_surat_id=$setor_surat_id, setor_ayat_dari=$setor_ayat_dari, setor_ayat_sampai=$setor_ayat_sampai
                    WHERE id = $existing_id";
            $pesan_sukses = "Laporan ibadah harian tanggal $tanggal berhasil diperbarui!";
        } else {
            $sql = "INSERT INTO ibadah_harian_santri (santri_id, tanggal, sholat_subuh, sholat_dhuhur, sholat_ashar, sholat_maghrib, sholat_isya,
                    sholat_tahajud, sholat_witir, sholat_qobliyah_subuh, sholat_dhuha, sholat_qobli_dhuhur, sholat_bakdiyah_dhuhur, sholat_qobliyah_ashar, sholat_bakdiyah_maghrib, sholat_qobliyah_isya, sholat_bakdiyah_isya,
                    puasa_senin, puasa_kamis, setor_surat_id, setor_ayat_dari, setor_ayat_sampai) VALUES (
                    $santri_id, '$tanggal', '$sholat_subuh', '$sholat_dhuhur', '$sholat_ashar', '$sholat_maghrib', '$sholat_isya',
                    $sholat_tahajud, $sholat_witir, $sholat_qobliyah_subuh, $sholat_dhuha, $sholat_qobli_dhuhur, $sholat_bakdiyah_dhuhur, $sholat_qobliyah_ashar, $sholat_bakdiyah_maghrib, $sholat_qobliyah_isya, $sholat_bakdiyah_isya,
                    $puasa_senin, $puasa_kamis, $setor_surat_id, $setor_ayat_dari, $setor_ayat_sampai)";
            $pesan_sukses = "Laporan ibadah harian tanggal $tanggal berhasil disimpan!";
        }

        if (!$conn->query($sql)) {
            $pesan_error = "Gagal menyimpan laporan: " . $conn->error;
        }
    }

    // Fetch current day's report for editing or pre-filling
    $today_date = date('Y-m-d');
    $current_report = null;
    $res_report = $conn->query("SELECT * FROM ibadah_harian_santri WHERE santri_id = $santri_id AND tanggal = '$today_date'");
    if ($res_report && $res_report->num_rows > 0) {
        $current_report = $res_report->fetch_assoc();
    }

    // Fetch past reports
    $past_reports = [];
    $res_past = $conn->query("SELECT * FROM ibadah_harian_santri WHERE santri_id = $santri_id ORDER BY tanggal DESC LIMIT 10");
    if ($res_past) {
        while($row = $res_past->fetch_assoc()) {
            $past_reports[] = $row;
        }
    }

} else { // Default view (Dashboard Santri)
    $active_menu = 'dashboard_santri';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Santri | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-santri" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="font-semibold text-sm text-gray-700 hidden sm:block">Selamat Datang, <?= htmlspecialchars($santri_nama) ?></span>
                <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($santri_nama, 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if ($view === 'ibadah_harian'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN IBADAH HARIAN                             -->
            <!-- ================================================== -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-mosque text-indigo-600 mr-2"></i>Laporan Ibadah Harian</h1>
                <p class="text-gray-500 mt-1">Catat kegiatan ibadah dan hafalanmu setiap hari.</p>
            </div>

            <?php if($pesan_sukses): ?><div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div><?php endif; ?>
            <?php if($pesan_error): ?><div class="bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div><?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <h2 class="font-bold text-gray-800 mb-4 border-b pb-2">Form Ibadah Harian (<?= date('d M Y') ?>)</h2>
                <form action="ruang-santri.php?view=ibadah_harian" method="POST" class="space-y-6">
                    <input type="hidden" name="tanggal" value="<?= date('Y-m-d') ?>">

                    <!-- Sholat Wajib -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Sholat Wajib</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php 
                            $sholat_wajib_opts = ['Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\'i'];
                            $sholat_names = ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya'];
                            foreach($sholat_names as $s_name): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sholat <?= ucfirst($s_name) ?></label>
                                <select name="sholat_<?= $s_name ?>" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500">
                                    <?php foreach($sholat_wajib_opts as $opt): ?>
                                        <option value="<?= $opt ?>" <?= ($current_report && $current_report['sholat_'.$s_name] == $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Sholat Sunnah -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Sholat Sunnah</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <?php 
                            $sholat_sunnah_names = ['tahajud', 'witir', 'qobliyah_subuh', 'dhuha', 'qobli_dhuhur', 'bakdiyah_dhuhur', 'qobliyah_ashar', 'bakdiyah_maghrib', 'qobliyah_isya', 'bakdiyah_isya'];
                            foreach($sholat_sunnah_names as $ss_name): ?>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="sholat_<?= $ss_name ?>" value="1" <?= ($current_report && $current_report['sholat_'.$ss_name] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700"><?= ucwords(str_replace('_', ' ', $ss_name)) ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Puasa Sunnah -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Puasa Sunnah</label>
                        <div class="flex flex-wrap gap-4">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="puasa_senin" value="1" <?= ($current_report && $current_report['puasa_senin'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Senin</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="puasa_kamis" value="1" <?= ($current_report && $current_report['puasa_kamis'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500">
                                <span class="text-sm text-gray-700">Kamis</span>
                            </label>
                        </div>
                    </div>

                    <!-- Setor Hafalan -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Setor Hafalan</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Surat</label>
                                <select name="setor_surat_id" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500">
                                    <option value="">-- Pilih Surat --</option>
                                    <?php foreach($surah_list as $id => $name): ?>
                                        <option value="<?= $id ?>" <?= ($current_report && $current_report['setor_surat_id'] == $id) ? 'selected' : '' ?>><?= $id ?>. <?= $name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ayat Dari</label>
                                <input type="number" name="setor_ayat_dari" value="<?= $current_report['setor_ayat_dari'] ?? '' ?>" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500" placeholder="Contoh: 1">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sampai Ayat</label>
                                <input type="number" name="setor_ayat_sampai" value="<?= $current_report['setor_ayat_sampai'] ?? '' ?>" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500" placeholder="Contoh: 10">
                            </div>
                        </div>
                    </div>

                    <div class="text-right pt-4 border-t border-gray-100">
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition">
                            <i class="fas fa-save mr-2"></i> Simpan Laporan
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800">Riwayat Laporan Ibadah Harian</h2>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Sholat Wajib</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Sholat Sunnah</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Puasa</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Hafalan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($past_reports) > 0): ?>
                                <?php foreach($past_reports as $report): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-bold text-gray-900"><?= date('d M Y', strtotime($report['tanggal'])) ?></td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <ul class="list-disc list-inside space-y-0.5">
                                            <li>Subuh: <?= $report['sholat_subuh'] ?></li>
                                            <li>Dhuhur: <?= $report['sholat_dhuhur'] ?></li>
                                            <li>Ashar: <?= $report['sholat_ashar'] ?></li>
                                            <li>Maghrib: <?= $report['sholat_maghrib'] ?></li>
                                            <li>Isya: <?= $report['sholat_isya'] ?></li>
                                        </ul>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <ul class="list-disc list-inside space-y-0.5">
                                            <?php if($report['sholat_tahajud']) echo '<li>Tahajud</li>'; ?>
                                            <?php if($report['sholat_witir']) echo '<li>Witir</li>'; ?>
                                            <?php if($report['sholat_qobliyah_subuh']) echo '<li>Qobliyah Subuh</li>'; ?>
                                            <?php if($report['sholat_dhuha']) echo '<li>Dhuha</li>'; ?>
                                            <?php if($report['sholat_qobli_dhuhur']) echo '<li>Qobli Dhuhur</li>'; ?>
                                            <?php if($report['sholat_bakdiyah_dhuhur']) echo '<li>Bakdiyah Dhuhur</li>'; ?>
                                            <?php if($report['sholat_qobliyah_ashar']) echo '<li>Qobliyah Ashar</li>'; ?>
                                            <?php if($report['sholat_bakdiyah_maghrib']) echo '<li>Bakdiyah Maghrib</li>'; ?>
                                            <?php if($report['sholat_qobliyah_isya']) echo '<li>Qobliyah Isya</li>'; ?>
                                            <?php if($report['sholat_bakdiyah_isya']) echo '<li>Bakdiyah Isya</li>'; ?>
                                            <?php if(!$report['sholat_tahajud'] && !$report['sholat_witir'] && !$report['sholat_qobliyah_subuh'] && !$report['sholat_dhuha'] && !$report['sholat_qobli_dhuhur'] && !$report['sholat_bakdiyah_dhuhur'] && !$report['sholat_qobliyah_ashar'] && !$report['sholat_bakdiyah_maghrib'] && !$report['sholat_qobliyah_isya'] && !$report['sholat_bakdiyah_isya']) echo '<li>-</li>'; ?>
                                        </ul>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <ul class="list-disc list-inside space-y-0.5">
                                            <?php if($report['puasa_senin']) echo '<li>Senin</li>'; ?>
                                            <?php if($report['puasa_kamis']) echo '<li>Kamis</li>'; ?>
                                            <?php if(!$report['puasa_senin'] && !$report['puasa_kamis']) echo '<li>-</li>'; ?>
                                        </ul>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-600">
                                        <?php if($report['setor_surat_id'] && $report['setor_ayat_dari'] && $report['setor_ayat_sampai']): ?>
                                            Surat: <?= $surah_list[$report['setor_surat_id']] ?><br>
                                            Ayat: <?= $report['setor_ayat_dari'] ?> - <?= $report['setor_ayat_sampai'] ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan='5' class='text-center py-6 text-gray-500 italic'>Belum ada laporan ibadah harian.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php else: ?>
            <!-- ================================================== -->
            <!-- TAMPILAN DEFAULT (DASHBOARD SANTRI)                -->
            <!-- ================================================== -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Ahlan Wa Sahlan, <?= htmlspecialchars($santri_nama) ?>!</h1>
                <p class="text-gray-500 mt-1">Selamat datang di Ruang Santri. Ini adalah halaman dashboard pribadimu.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[400px]">
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-20">
                    <i class="fas fa-book-reader text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Menu Santri Segera Hadir</p>
                    <p class="text-sm mt-2">Halaman ini akan segera diisi dengan informasi akademik, jadwal, dan lainnya.</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-santri');
            const openBtn = document.getElementById('open-sidebar-santri');
            const overlay = document.getElementById('sidebar-overlay-santri');
            if(openBtn) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
            if(overlay) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
        });
    </script>
</body>
</html>