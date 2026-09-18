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
    // --- LOGIC FOR DASHBOARD SANTRI (MOBILE HOME) ---
    $active_menu = 'dashboard_santri';
    $today_date = date('Y-m-d');

    // 1. Status Ibadah Hari Ini
    $today_ibadah = null;
    $res_tibadah = $conn->query("SELECT * FROM ibadah_harian_santri WHERE santri_id = $santri_id AND tanggal = '$today_date'");
    if ($res_tibadah && $res_tibadah->num_rows > 0) {
        $today_ibadah = $res_tibadah->fetch_assoc();
    }

    // 2. Statistik Hafalan
    $s_nama_escaped = $conn->real_escape_string($santri_nama);
    $res_stat_hafalan = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN grade = 'Mumtaz' THEN 1 ELSE 0 END) as mumtaz FROM laporan_setoran_hafalan WHERE santri_id = $santri_id OR nama_santri = '$s_nama_escaped'");
    $stat_hafalan = $res_stat_hafalan ? $res_stat_hafalan->fetch_assoc() : ['total' => 0, 'mumtaz' => 0];
    $total_setoran = $stat_hafalan['total'] ?? 0;
    $total_mumtaz = $stat_hafalan['mumtaz'] ?? 0;

    // Setoran terakhir
    $res_last_hafalan = $conn->query("SELECT * FROM laporan_setoran_hafalan WHERE santri_id = $santri_id OR nama_santri = '$s_nama_escaped' ORDER BY created_at DESC, id DESC LIMIT 1");
    $last_hafalan = ($res_last_hafalan && $res_last_hafalan->num_rows > 0) ? $res_last_hafalan->fetch_assoc() : null;

    // 3. Saldo Uang Saku
    $res_setoran_saku = $conn->query("SELECT SUM(jumlah) as total FROM uang_saku WHERE santri_id = $santri_id AND status = 'Berhasil'");
    $total_masuk_saku = ($res_setoran_saku && $r = $res_setoran_saku->fetch_assoc()) ? (int)$r['total'] : 0;

    $res_penarikan_saku = $conn->query("SELECT SUM(jumlah) as total FROM penarikan_uang_saku WHERE santri_id = $santri_id AND status = 'Disetujui'");
    $total_keluar_saku = ($res_penarikan_saku && $r = $res_penarikan_saku->fetch_assoc()) ? (int)$r['total'] : 0;
    $saldo_saku = $total_masuk_saku - $total_keluar_saku;

    // 4. Riwayat 5 Ibadah Terakhir
    $recent_ibadah = [];
    $res_recent = $conn->query("SELECT * FROM ibadah_harian_santri WHERE santri_id = $santri_id ORDER BY tanggal DESC LIMIT 5");
    if ($res_recent) {
        while($r = $res_recent->fetch_assoc()) {
            $recent_ibadah[] = $r;
        }
    }
}

// Format Salam Berdasarkan Jam
$hour = (int)date('H');
if ($hour < 11) {
    $greeting = "Selamat Pagi";
    $greeting_sub = "Awali harimu dengan tilawah & doa";
} elseif ($hour < 15) {
    $greeting = "Selamat Siang";
    $greeting_sub = "Jangan lupa sholat Dzuhur berjamaah";
} elseif ($hour < 18) {
    $greeting = "Selamat Sore";
    $greeting_sub = "Waktunya muraja'ah & istirahat sejenak";
} else {
    $greeting = "Selamat Malam";
    $greeting_sub = "Tutup harimu dengan evaluasi & witir";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ruang Santri | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP APP HEADER -->
        <header class="h-16 bg-white border-b border-indigo-100 shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-santri" class="text-slate-600 hover:text-indigo-600 md:hidden p-2 rounded-xl hover:bg-slate-100 focus:outline-none transition">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-200">
                        <i class="fas fa-user-graduate text-sm"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-slate-900 text-sm sm:text-base leading-tight">RUANG SANTRI</h2>
                        <p class="text-[10px] font-semibold text-slate-400">SADIGS 4.0 Villa Quran</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-2 sm:space-x-3">
                <a href="santri-profil.php" class="flex items-center space-x-2 p-1.5 sm:px-3 sm:py-1.5 rounded-full bg-slate-50 hover:bg-indigo-50 border border-slate-200 transition">
                    <div class="w-7 h-7 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shadow-sm">
                        <?= strtoupper(substr($santri_nama, 0, 1)) ?>
                    </div>
                    <span class="text-xs font-bold text-slate-700 hidden sm:inline max-w-[120px] truncate"><?= htmlspecialchars($santri_nama) ?></span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-3.5 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-4xl mx-auto">
                
                <?php if ($view === 'ibadah_harian'): ?>
                <!-- ================================================== -->
                <!-- TAMPILAN FORM IBADAH HARIAN                        -->
                <!-- ================================================== -->
                <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <a href="ruang-santri.php" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 hover:text-indigo-800 mb-1.5 transition">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">
                                <i class="fas fa-mosque"></i>
                            </span>
                            Laporan Ibadah Harian
                        </h1>
                        <p class="text-xs text-slate-500 mt-0.5">Catat pelaksanaan sholat wajib, sunnah, dan puasa harianmu</p>
                    </div>
                    <div class="bg-indigo-50 text-indigo-700 px-3 py-1.5 rounded-xl text-xs font-bold self-start sm:self-auto border border-indigo-100">
                        <i class="fas fa-calendar-day mr-1"></i> <?= date('d M Y') ?>
                    </div>
                </div>

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

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-6 mb-6">
                    <form action="ruang-santri.php?view=ibadah_harian" method="POST" class="space-y-6">
                        <input type="hidden" name="tanggal" value="<?= date('Y-m-d') ?>">

                        <?php if ($is_female): ?>
                        <!-- Uzur Syar'i (Haid) Toggle -->
                        <div class="bg-pink-50 border border-pink-100 rounded-2xl p-4 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 bg-pink-100 rounded-xl flex items-center justify-center text-pink-600">
                                    <i class="fas fa-venus text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-pink-900">Sedang Haid / Uzur Syar'i</h3>
                                    <p class="text-[11px] text-pink-600">Aktifkan jika sedang dalam masa uzur sholat bulanan.</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_haid_toggle" name="is_haid" value="1" <?= ($current_report && $current_report['is_haid'] == 1) ? 'checked' : '' ?> class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-500"></div>
                            </label>
                        </div>
                        <?php endif; ?>

                        <div id="ibadah_fields_container" class="space-y-6">
                            <!-- Sholat Wajib -->
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-3">
                                    <i class="fas fa-clock text-indigo-500 mr-1.5"></i> Sholat Wajib 5 Waktu
                                </label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <?php 
                                    $sholat_wajib_opts = ['Jamaah di Masjid', 'Jamaah di Mushola Asrama', 'Munfarid', 'Udzur Syar\'i'];
                                    $sholat_names = ['subuh', 'dhuhur', 'ashar', 'maghrib', 'isya'];
                                    foreach($sholat_names as $s_name): ?>
                                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                                        <label class="block text-xs font-bold text-slate-800 mb-1.5 capitalize">Sholat <?= $s_name ?></label>
                                        <select name="sholat_<?= $s_name ?>" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-medium focus:ring-2 focus:ring-indigo-500">
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
                                <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-3">
                                    <i class="fas fa-star text-amber-500 mr-1.5"></i> Sholat Sunnah (Centang yang dikerjakan)
                                </label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                                    <?php 
                                    $sholat_sunnah_names = ['tahajud', 'witir', 'qobliyah_subuh', 'dhuha', 'qobli_dhuhur', 'bakdiyah_dhuhur', 'qobliyah_ashar', 'bakdiyah_maghrib', 'qobliyah_isya', 'bakdiyah_isya'];
                                    foreach($sholat_sunnah_names as $ss_name): ?>
                                    <label class="flex items-center p-2.5 bg-slate-50 rounded-xl border border-slate-100 cursor-pointer hover:bg-indigo-50/50 transition">
                                        <input type="checkbox" name="sholat_<?= $ss_name ?>" value="1" <?= ($current_report && $current_report['sholat_'.$ss_name] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500 border-slate-300">
                                        <span class="text-xs font-semibold text-slate-700 ml-2"><?= ucwords(str_replace('_', ' ', $ss_name)) ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Puasa Sunnah -->
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-3">
                                    <i class="fas fa-moon text-purple-500 mr-1.5"></i> Puasa Sunnah
                                </label>
                                <div class="flex flex-wrap gap-3">
                                    <label class="flex items-center px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-100 cursor-pointer hover:bg-purple-50/50 transition">
                                        <input type="checkbox" name="puasa_senin" value="1" <?= ($current_report && $current_report['puasa_senin'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500 border-slate-300">
                                        <span class="text-xs font-bold text-slate-700 ml-2">Puasa Senin</span>
                                    </label>
                                    <label class="flex items-center px-4 py-2.5 bg-slate-50 rounded-xl border border-slate-100 cursor-pointer hover:bg-purple-50/50 transition">
                                        <input type="checkbox" name="puasa_kamis" value="1" <?= ($current_report && $current_report['puasa_kamis'] == 1) ? 'checked' : '' ?> class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500 border-slate-300">
                                        <span class="text-xs font-bold text-slate-700 ml-2">Puasa Kamis</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end">
                            <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold py-3 px-8 rounded-xl shadow-md shadow-emerald-200 transition flex items-center justify-center gap-2">
                                <i class="fas fa-save"></i> Simpan Laporan Ibadah
                            </button>
                        </div>
                    </form>

                    <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            const toggle = document.getElementById('is_haid_toggle');
                            const container = document.getElementById('ibadah_fields_container');
                            if (toggle && container) {
                                function handleToggle() {
                                    if (toggle.checked) {
                                        container.style.display = 'none';
                                    } else {
                                        container.style.display = 'block';
                                    }
                                }
                                toggle.addEventListener('change', handleToggle);
                                handleToggle();
                            }
                        });
                    </script>
                </div>

                <!-- RIWAYAT LAPORAN IBADAH -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
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

                <?php else: ?>
                <!-- ================================================== -->
                <!-- TAMPILAN BERANDA UTAMA (MOBILE-FIRST DASHBOARD)    -->
                <!-- ================================================== -->

                <!-- 1. HERO PROFILE CARD -->
                <div class="relative overflow-hidden bg-gradient-to-r from-indigo-900 via-indigo-800 to-purple-900 rounded-3xl p-5 sm:p-6 text-white shadow-xl shadow-indigo-900/10 mb-5">
                    <!-- Background Pattern -->
                    <div class="absolute -right-8 -bottom-8 opacity-10 text-white pointer-events-none">
                        <i class="fas fa-quran text-9xl"></i>
                    </div>

                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center space-x-3.5">
                            <div class="relative">
                                <div class="w-14 h-14 rounded-2xl bg-amber-400/20 border-2 border-amber-400/60 p-0.5 flex items-center justify-center text-amber-300 font-extrabold text-2xl shadow-inner">
                                    <?= strtoupper(substr($santri_nama, 0, 1)) ?>
                                </div>
                                <span class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-indigo-900 rounded-full"></span>
                            </div>
                            <div>
                                <span class="text-[11px] font-bold tracking-wider text-amber-300 uppercase"><?= $greeting ?> 👋</span>
                                <h1 class="text-lg sm:text-xl font-extrabold text-white leading-tight tracking-tight mt-0.5">
                                    <?= htmlspecialchars($santri_nama) ?>
                                </h1>
                                <div class="flex items-center gap-2 mt-1 text-xs text-indigo-200">
                                    <span class="bg-indigo-700/80 px-2.5 py-0.5 rounded-full font-semibold text-[11px]">Kelas: <?= htmlspecialchars($kelas_santri) ?></span>
                                    <span>•</span>
                                    <span class="text-[11px]">NISN: <?= htmlspecialchars($nisn_santri) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Date Badge -->
                        <div class="self-start sm:self-auto bg-white/10 backdrop-blur-md border border-white/15 px-3.5 py-1.5 rounded-xl text-left sm:text-right">
                            <div class="text-[10px] uppercase font-bold text-indigo-200">Hari Ini</div>
                            <div class="text-xs font-extrabold text-white"><?= date('l, d M Y') ?></div>
                        </div>
                    </div>
                </div>

                <!-- 2. QUICK STATS SUMMARY CARDS (3 COLUMNS) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 mb-6">
                    
                    <!-- KARTU 1: STATUS IBADAH HARI INI -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Ibadah Hari Ini</span>
                                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                    <i class="fas fa-mosque"></i>
                                </span>
                            </div>
                            <?php if ($today_ibadah): ?>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-xs font-bold text-emerald-700">Sudah Tercatat</span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1">Status: <strong class="text-slate-700"><?= $today_ibadah['status_validasi'] ?? 'Pending' ?></strong></p>
                            <?php else: ?>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span class="text-xs font-bold text-amber-700">Belum Mengisi</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1">Yuk catat sholatmu hari ini</p>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100">
                            <a href="ruang-santri.php?view=ibadah_harian" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center justify-between">
                                <span><?= $today_ibadah ? 'Edit Laporan' : 'Catat Sekarang' ?></span>
                                <i class="fas fa-chevron-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- KARTU 2: HAFALAN QUR'AN -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hafalan Al-Qur'an</span>
                                <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                                    <i class="fas fa-quran"></i>
                                </span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <div class="text-2xl font-black text-slate-900"><?= number_format($total_setoran) ?></div>
                                <span class="text-xs text-slate-500 font-semibold">Kali Setor</span>
                            </div>
                            <p class="text-[11px] text-purple-600 font-bold mt-0.5"><?= number_format($total_mumtaz) ?> Predikat Mumtaz</p>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100">
                            <a href="santri-laporan-hafalan.php" class="text-xs font-bold text-purple-600 hover:text-purple-700 flex items-center justify-between">
                                <span>Lihat Riwayat</span>
                                <i class="fas fa-chevron-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- KARTU 3: SALDO UANG SAKU -->
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm relative overflow-hidden flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Saldo Uang Saku</span>
                                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                                    <i class="fas fa-wallet"></i>
                                </span>
                            </div>
                            <div class="text-2xl font-black text-emerald-600 tracking-tight">
                                Rp <?= number_format(max(0, $saldo_saku), 0, ',', '.') ?>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-0.5">Saldo aktif di dompet santri</p>
                        </div>
                        <div class="mt-3 pt-2.5 border-t border-slate-100">
                            <a href="ruang-santri-keuangan.php" class="text-xs font-bold text-amber-600 hover:text-amber-700 flex items-center justify-between">
                                <span>Ajukan Penarikan</span>
                                <i class="fas fa-chevron-right text-[10px]"></i>
                            </a>
                        </div>
                    </div>

                </div>

                <!-- 3. GRID CARDS / MENU ICON SHORTCUTS (8 MENU) -->
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fas fa-th-large text-indigo-600"></i> Menu Utama Santri
                        </h2>
                        <span class="text-[11px] text-slate-400 font-semibold">Pintasan Layanan</span>
                    </div>

                    <div class="grid grid-cols-4 sm:grid-cols-4 gap-2.5 sm:gap-4">
                        
                        <!-- 1. Ibadah Harian -->
                        <a href="ruang-santri.php?view=ibadah_harian" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-emerald-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 text-white flex items-center justify-center text-lg shadow-md shadow-emerald-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-mosque"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-emerald-700">Ibadah Harian</span>
                        </a>

                        <!-- 2. Setoran Hafalan -->
                        <a href="santri-laporan-hafalan.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-purple-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-600 text-white flex items-center justify-center text-lg shadow-md shadow-purple-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-quran"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-purple-700">Setoran Quran</span>
                        </a>

                        <!-- 3. Kalender Akademik -->
                        <a href="kalender-akademik.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-sky-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-sky-500 to-blue-600 text-white flex items-center justify-center text-lg shadow-md shadow-sky-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-sky-700">Kalender</span>
                        </a>

                        <!-- 4. Keuangan & Uang Saku -->
                        <a href="ruang-santri-keuangan.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-amber-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center text-lg shadow-md shadow-amber-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-amber-700">Keuangan</span>
                        </a>

                        <!-- 5. Rapor Akademik -->
                        <a href="santri-rapot.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-indigo-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-500 to-violet-600 text-white flex items-center justify-center text-lg shadow-md shadow-indigo-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-indigo-700">Rapor Sekolah</span>
                        </a>

                        <!-- 6. Rapor Diniyah -->
                        <a href="santri-rapot-diniyah.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-orange-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-600 text-white flex items-center justify-center text-lg shadow-md shadow-orange-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-book-quran"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-orange-700">Rapor Diniyah</span>
                        </a>

                        <!-- 7. Rapor PKBM -->
                        <a href="santri-rapot-pkbm.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-rose-50/50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-rose-500 to-pink-600 text-white flex items-center justify-center text-lg shadow-md shadow-rose-200 group-hover:scale-110 transition-transform">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-rose-700">Rapor PKBM</span>
                        </a>

                        <!-- 8. Profil & Akun -->
                        <a href="santri-profil.php" class="group flex flex-col items-center justify-center p-3 sm:p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95">
                            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-slate-600 to-slate-800 text-white flex items-center justify-center text-lg shadow-md shadow-slate-300 group-hover:scale-110 transition-transform">
                                <i class="fas fa-user-cog"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 text-center mt-2.5 leading-tight group-hover:text-slate-900">Profil & Akun</span>
                        </a>

                    </div>
                </div>

                <!-- 4. BOTTOM SECTION: SETORAN HAFALAN TERAKHIR & PENGUMUMAN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Setoran Terakhir Card -->
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-xs font-bold uppercase text-slate-500 tracking-wider flex items-center gap-1.5">
                                <i class="fas fa-history text-purple-600"></i> Setoran Hafalan Terakhir
                            </h3>
                            <a href="santri-laporan-hafalan.php" class="text-xs font-bold text-purple-600 hover:text-purple-800">Semua</a>
                        </div>
                        <?php if ($last_hafalan): ?>
                        <div class="bg-purple-50/60 rounded-xl p-3.5 border border-purple-100">
                            <div class="flex items-center justify-between">
                                <span class="font-extrabold text-sm text-purple-950"><?= htmlspecialchars($last_hafalan['nama_surat'] ?? 'Surat Al-Qur\'an') ?></span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-200 text-purple-800"><?= htmlspecialchars($last_hafalan['grade'] ?? 'Mumtaz') ?></span>
                            </div>
                            <div class="text-xs text-purple-700 mt-1">Ayat: <?= htmlspecialchars($last_hafalan['ayat_dari'] ?? '1') ?> - <?= htmlspecialchars($last_hafalan['ayat_sampai'] ?? 'Akhir') ?></div>
                            <div class="text-[10px] text-purple-500 mt-2 flex items-center gap-1">
                                <i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($last_hafalan['tanggal_setoran'] ?? $last_hafalan['created_at'])) ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-6 text-slate-400">
                            <i class="fas fa-book-open text-3xl mb-2 opacity-40"></i>
                            <p class="text-xs">Belum ada catatan setoran hafalan.</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Informasi / Info Pondok -->
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs font-bold uppercase text-slate-500 tracking-wider mb-3 flex items-center gap-1.5">
                                <i class="fas fa-bullhorn text-amber-500"></i> Pengingat Santri
                            </h3>
                            <div class="space-y-2.5">
                                <div class="flex items-start gap-2.5 bg-amber-50/60 p-3 rounded-xl border border-amber-100/80">
                                    <i class="fas fa-heart text-amber-600 text-xs mt-0.5"></i>
                                    <p class="text-xs text-amber-900 leading-relaxed">
                                        Jangan lupa mengisi <strong>Laporan Ibadah Harian</strong> sebelum pukul 21:00 WIB setiap malam.
                                    </p>
                                </div>
                                <div class="flex items-start gap-2.5 bg-sky-50/60 p-3 rounded-xl border border-sky-100/80">
                                    <i class="fas fa-info-circle text-sky-600 text-xs mt-0.5"></i>
                                    <p class="text-xs text-sky-900 leading-relaxed">
                                        Selalu cek <strong>Kalender Akademik</strong> untuk melihat jadwal ujian dan libur pondok.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- MOBILE BOTTOM NAVIGATION BAR -->
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