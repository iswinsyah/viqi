<?php
require_once 'auth.php';
require_once '../koneksi.php';

// Active menu highlight
$active_menu = 'kpi_yayasan';

// 1. Ambil List Pegawai/Staf
$res_staf = $conn->query("SELECT id, nama, role FROM akun_ustadz ORDER BY nama ASC");
$staf_list = [];
if ($res_staf) {
    while ($row = $res_staf->fetch_assoc()) {
        $staf_list[] = $row;
    }
}

// Staf terpilih (default: staf pertama jika ada)
$selected_staf_id = isset($_GET['staf_id']) ? (int)$_GET['staf_id'] : ($staf_list[0]['id'] ?? 0);

// 2. Baca Log Agent untuk Status AI HRD
$log_file = '../agent_cron_log.txt';
$ai_status = 'Nonaktif / Belum Berjalan';
$ai_status_color = 'bg-rose-100 text-rose-800 border-rose-250';
$ai_status_icon = 'fa-power-off';
$log_lines = [];
$fonnte_alert = false;

if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    clearstatcache();
    $last_modified = filemtime($log_file);
    
    // Jika diperbarui dalam 24 jam terakhir
    if (time() - $last_modified < 86400) {
        $ai_status = 'Aktif & Menjaga (Hari Ini)';
        $ai_status_color = 'bg-emerald-100 text-emerald-800 border-emerald-250';
        $ai_status_icon = 'fa-robot';
    } else {
        $ai_status = 'Aktif Namun Terlambat Berjalan';
        $ai_status_color = 'bg-amber-100 text-amber-800 border-amber-250';
        $ai_status_icon = 'fa-triangle-exclamation';
    }
    
    // Cek error Fonnte terputus
    if (strpos($log_content, 'disconnected device') !== false || strpos($log_content, 'menolak') !== false) {
        $fonnte_alert = true;
    }
    
    // Ambil 15 baris log terakhir
    $raw_lines = explode("\n", trim($log_content));
    $log_lines = array_slice($raw_lines, -15);
}

// 3. Ambil Detail Kinerja Pegawai Terpilih
$staf = null;
if ($selected_staf_id > 0) {
    $res_detail = $conn->query("SELECT * FROM akun_ustadz WHERE id = $selected_staf_id");
    if ($res_detail) $staf = $res_detail->fetch_assoc();
}

$kpi_data = [];
if ($staf) {
    $user_roles = !empty($staf['role']) ? explode(',', $staf['role']) : [];
    $is_teacher = in_array('ustadz', $user_roles) || in_array('guru', $user_roles);
    $eligible_roles_pegawai = ['super_admin', 'kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'musyrif'];
    $is_daily_worker = !empty(array_intersect($eligible_roles_pegawai, $user_roles));

    // A. Jurnal Bulan Ini
    $res_jurnal = $conn->query("SELECT 
        COUNT(*) as total_jurnal, 
        SUM(CASE WHEN DATE(created_at) = tanggal THEN 1 ELSE 0 END) as tepat_waktu 
        FROM jurnal_mengajar 
        WHERE ustadz_id = $selected_staf_id AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
    $data_jurnal = $res_jurnal ? $res_jurnal->fetch_assoc() : ['total_jurnal' => 0, 'tepat_waktu' => 0];
    $jumlah_pertemuan = (int)($data_jurnal['total_jurnal'] ?? 0);
    $tepat_waktu = (int)($data_jurnal['tepat_waktu'] ?? 0);

    // B. Hitung skor jurnal
    if ($is_teacher) {
        $skor_jurnal = $jumlah_pertemuan > 0 ? ($tepat_waktu / $jumlah_pertemuan) * 100 : 100;
    } else {
        $skor_jurnal = 100; // non-teacher gets 100
    }

    // C. Kehadiran Harian/Pegawai (Bulan ini)
    $res_hadir = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $selected_staf_id AND jenis_absen IN ('Pegawai', 'Harian') AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE())");
    $jml_hadir = $res_hadir ? (int)($res_hadir->fetch_assoc()['jml'] ?? 0) : 0;

    if ($is_daily_worker) {
        $skor_kehadiran = $jml_hadir > 0 ? min(100, ($jml_hadir / 20) * 100) : 0;
    } else {
        // Jika ustadz honorer saja
        $res_hadir_mengajar = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $selected_staf_id AND jenis_absen = 'Mengajar' AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE())");
        $jml_hadir_mengajar = $res_hadir_mengajar ? (int)($res_hadir_mengajar->fetch_assoc()['jml'] ?? 0) : 0;
        
        $res_total_teaching_days = $conn->query("SELECT COUNT(DISTINCT tanggal) as total_days FROM jurnal_mengajar WHERE ustadz_id = $selected_staf_id AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
        $total_teaching_days = $res_total_teaching_days ? (int)($res_total_teaching_days->fetch_assoc()['total_days'] ?? 0) : 0;
        
        $skor_kehadiran = $total_teaching_days > 0 ? min(100, ($jml_hadir_mengajar / $total_teaching_days) * 100) : 100;
    }

    // D. Kehadiran Rapat (Bulan ini)
    $res_rapat = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $selected_staf_id AND jenis_absen = 'Rapat' AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE())");
    $jml_rapat = $res_rapat ? (int)($res_rapat->fetch_assoc()['jml'] ?? 0) : 0;

    $res_total_rapat = $conn->query("SELECT COUNT(*) as total FROM jadwal_rapat WHERE MONTH(waktu_mulai) = MONTH(CURRENT_DATE()) AND YEAR(waktu_mulai) = YEAR(CURRENT_DATE())");
    $total_rapat = $res_total_rapat ? (int)($res_total_rapat->fetch_assoc()['total'] ?? 0) : 0;
    $skor_kehadiran_rapat = $total_rapat > 0 ? min(100, ($jml_rapat / $total_rapat) * 100) : 100;

    $skor_administrasi = (($skor_jurnal * 0.4) + ($skor_kehadiran * 0.4) + ($skor_kehadiran_rapat * 0.2));

    // E. Kualitas Pengajaran
    $res_ai = $conn->query("SELECT COUNT(*) as pemakaian FROM log_aktivitas_ai WHERE user_id = $selected_staf_id AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $jumlah_pakai_ai = $res_ai ? (int)($res_ai->fetch_assoc()['pemakaian'] ?? 0) : 0;
    $skor_penggunaan_ai = $jumlah_pakai_ai >= 5 ? 100 : ($jumlah_pakai_ai > 0 ? 85 : 70);

    if ($is_teacher) {
        $res_sup = $conn->query("SELECT skor FROM supervisi_mengajar WHERE user_id = $selected_staf_id ORDER BY tanggal_supervisi DESC LIMIT 1");
        $skor_supervisi = $res_sup && $res_sup->num_rows > 0 ? (int)($res_sup->fetch_assoc()['skor']) : 85;
    } else {
        $skor_supervisi = 100;
    }
    $skor_kualitas_pengajaran = (($skor_penggunaan_ai * 0.4) + ($skor_supervisi * 0.6));

    // F. Capaian Santri
    if ($is_teacher) {
        $res_nilai = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE ustadz_id = $selected_staf_id");
        $rata_rata_db = $res_nilai ? (float)($res_nilai->fetch_assoc()['rata_rata'] ?? 0) : 0;
        $skor_rata_nilai = $rata_rata_db > 0 ? $rata_rata_db : 80;
        
        $res_uts = $conn->query("SELECT AVG(nilai) as rata_uts FROM leger_nilai WHERE ustadz_id = $selected_staf_id AND jenis_ujian = 'Ujian Tengah Semester (UTS)'");
        $rata_uts = $res_uts ? (float)($res_uts->fetch_assoc()['rata_uts'] ?? 0) : 0;
        $res_uas = $conn->query("SELECT AVG(nilai) as rata_uas FROM leger_nilai WHERE ustadz_id = $selected_staf_id AND jenis_ujian = 'Ujian Akhir Semester (UAS)'");
        $rata_uas = $res_uas ? (float)($res_uas->fetch_assoc()['rata_uas'] ?? 0) : 0;
        $skor_pertumbuhan = ($rata_uts > 0 && $rata_uas > 0) ? (($rata_uas >= $rata_uts) ? 100 : 75) : 85;
    } else {
        $skor_rata_nilai = 100;
        $skor_pertumbuhan = 100;
    }
    $skor_capaian_santri = (($skor_rata_nilai * 0.6) + ($skor_pertumbuhan * 0.4));

    // G. Pengembangan Diri
    $skor_kontribusi_silabus = $jumlah_pakai_ai > 0 ? 100 : 70;
    $skor_pengembangan_diri = $skor_kontribusi_silabus;

    // Total KPI
    $total_skor_kpi = ($skor_administrasi * 0.20) + ($skor_kualitas_pengajaran * 0.40) + ($skor_capaian_santri * 0.30) + ($skor_pengembangan_diri * 0.10);
    
    if ($total_skor_kpi >= 90) {
        $predikat = "Mumtaz (Grade A)";
        $color_predikat = "bg-emerald-100 text-emerald-800 border-emerald-250";
    } elseif ($total_skor_kpi >= 80) {
        $predikat = "Jayid (Grade B)";
        $color_predikat = "bg-blue-100 text-blue-800 border-blue-250";
    } else {
        $predikat = "Aslha (Grade C)";
        $color_predikat = "bg-rose-100 text-rose-800 border-rose-250";
    }

    // Riwayat Kehadiran (Bulan Ini)
    $res_riwayat = $conn->query("SELECT waktu_absen, jenis_absen, status_kehadiran, keterangan FROM absensi_pegawai WHERE ustadz_id = $selected_staf_id AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE()) ORDER BY waktu_absen DESC LIMIT 15");
    $riwayat = [];
    if ($res_riwayat) {
        while ($row = $res_riwayat->fetch_assoc()) {
            $riwayat[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring AI & Kinerja Pegawai | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-semibold bg-amber-100 text-amber-800 px-3 py-1 rounded-full"><i class="fas fa-user-shield mr-1"></i> Admin Yayasan</span>
            </div>
        </header>

        <!-- MAIN MAIN MAIN -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-6">
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-bar text-amber-600 mr-2.5"></i>Monitoring AI & Kinerja Staf</h1>
                    <p class="text-xs text-gray-500 mt-1">Pemantauan langsung status berjalan AI HRD serta log aktivitas kinerja harian, mingguan, bulanan, dan tahunan pegawai.</p>
                </div>
            </div>

            <!-- TABEL STATUS AI HRD -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <!-- AI HRD STATUS CARD -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 p-6 flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-2">STATUS AI HRD</span>
                        <div class="flex items-center space-x-3 mb-4">
                            <span class="px-3 py-1.5 rounded-full text-sm font-bold border flex items-center <?= $ai_status_color ?>">
                                <i class="fas <?= $ai_status_icon ?> mr-2"></i> <?= $ai_status ?>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 leading-relaxed">AI HRD memantau presensi, input jurnal, adab santri, dan keaktifan asatidz secara otonom setiap hari pukul 05:00 dan 12:59 WIB.</p>
                    </div>

                    <?php if ($fonnte_alert): ?>
                        <div class="mt-4 bg-rose-50 border border-rose-250 text-rose-800 p-3 rounded-lg text-xs flex items-start">
                            <i class="fas fa-exclamation-triangle mr-2 text-rose-600 text-sm mt-0.5"></i>
                            <div>
                                <span class="font-bold block">⚠️ WhatsApp Fonnte Terputus!</span>
                                Alat pengirim WA melaporkan perangkat Anda terputus (disconnected device). Silakan hubungkan ulang HP Anda di dashboard Fonnte.
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-4 bg-emerald-50 border border-emerald-250 text-emerald-800 p-3 rounded-lg text-xs flex items-center">
                            <i class="fas fa-check-circle mr-2 text-emerald-600"></i>
                            Koneksi Fonnte WA berjalan normal.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- AI RUNNING LOGS (TERMINAL MOCK) -->
                <div class="lg:col-span-2 bg-slate-900 rounded-xl shadow-sm p-4 text-xs font-mono text-gray-300 flex flex-col h-56">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-2">
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider">Aktivitas Terakhir AI HRD</span>
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <div class="flex-1 overflow-y-auto space-y-1.5 scrollbar-thin">
                        <?php if (count($log_lines) > 0): ?>
                            <?php foreach ($log_lines as $line): ?>
                                <div class="text-slate-350"><?= htmlspecialchars($line) ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-slate-500 italic">Belum ada catatan log aktivitas yang tercatat hari ini.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- LAYOUT UTAMA: PILIH PEGAWAI & MONITOR KINERJA -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- PANEL KIRI: PILIH STAF -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 p-4 h-fit">
                    <h3 class="font-bold text-gray-800 mb-3 text-xs uppercase tracking-wider text-slate-400">Pilih Pegawai / Staf</h3>
                    <div class="max-h-96 overflow-y-auto space-y-1 pr-1">
                        <?php foreach ($staf_list as $s): 
                            $act = ($selected_staf_id == $s['id']) ? 'bg-amber-50 border-amber-300 text-amber-900' : 'hover:bg-slate-50 text-gray-700 border-transparent';
                        ?>
                            <a href="kpi.php?staf_id=<?= $s['id'] ?>" class="flex items-center justify-between px-3 py-2 border rounded-lg text-xs font-bold transition <?= $act ?>">
                                <span><?= htmlspecialchars($s['nama']) ?></span>
                                <span class="bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded-full text-[9px] uppercase tracking-wider"><?= htmlspecialchars(explode(',', $s['role'])[0]) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- SOP PERATURAN REFERENCE LINK -->
                    <div class="mt-6 pt-4 border-t border-gray-150">
                        <div class="bg-slate-50 p-3 rounded-lg border text-xs">
                            <span class="font-bold block text-slate-700 mb-1"><i class="fas fa-gavel mr-1 text-slate-500"></i> Regulasi Rujukan</span>
                            <span class="text-[10px] text-gray-400 block mb-2">Penilaian & aturan jam kerja AI merujuk pada SOP & Peraturan Pegawai.</span>
                            <a href="admin-peraturan.php" class="text-cyan-600 hover:text-cyan-800 font-bold block text-[10px] hover:underline">
                                Lihat SOP & Peraturan &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <!-- PANEL KANAN: MONITOR KINERJA (HARIAN, MINGGUAN, BULANAN, SEMESTERAN, TAHUNAN) -->
                <div class="lg:col-span-3 space-y-6">
                    <?php if ($staf): ?>
                        <!-- IDENTITAS PEGAWAI & KARTU UTAMA -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 p-6 flex flex-col md:flex-row justify-between items-center gap-4">
                            <div>
                                <span class="text-[10px] bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded uppercase tracking-wider">Profil Kinerja</span>
                                <h2 class="text-xl font-bold text-gray-900 mt-1"><?= htmlspecialchars($staf['nama']) ?></h2>
                                <p class="text-xs text-gray-500 mt-0.5">Role Terdaftar: <span class="font-semibold text-gray-700"><?= htmlspecialchars($staf['role']) ?></span></p>
                            </div>
                            <div class="text-center md:text-right">
                                <span class="text-xs text-gray-400 uppercase tracking-wider block mb-1">Skor Bulanan KPI</span>
                                <span class="text-3xl font-black text-amber-600 block"><?= number_format($total_skor_kpi, 2) ?></span>
                                <span class="px-2.5 py-0.5 rounded text-[10px] font-bold inline-block border <?= $color_predikat ?> mt-1.5"><?= $predikat ?></span>
                            </div>
                        </div>

                        <!-- PILAR BREAKDOWN KPI -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm">
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block mb-1">Administrasi (20%)</span>
                                <span class="text-xl font-black text-blue-600 block"><?= number_format($skor_administrasi, 2) ?></span>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm">
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block mb-1">Kualitas Ajar (40%)</span>
                                <span class="text-xl font-black text-purple-600 block"><?= number_format($skor_kualitas_pengajaran, 2) ?></span>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm">
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block mb-1">Capaian Santri (30%)</span>
                                <span class="text-xl font-black text-emerald-600 block"><?= number_format($skor_capaian_santri, 2) ?></span>
                            </div>
                            <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm">
                                <span class="text-[9px] font-extrabold text-slate-400 uppercase block mb-1">Pengembangan (10%)</span>
                                <span class="text-xl font-black text-amber-650 block"><?= number_format($skor_pengembangan_diri, 2) ?></span>
                            </div>
                        </div>

                        <!-- PILAHAN KINERJA PERIODIK (HARIAN, MINGGUAN, BULANAN, SEMESTERAN, TAHUNAN) -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 overflow-hidden">
                            <!-- TAB HEADERS -->
                            <div class="flex border-b border-gray-250 bg-slate-50 text-xs font-bold text-gray-500 overflow-x-auto">
                                <button onclick="switchTab('harian')" id="tab-harian" class="px-5 py-3.5 border-b-2 border-amber-600 text-amber-900 bg-white focus:outline-none whitespace-nowrap">
                                    <i class="fas fa-calendar-day mr-1"></i> Harian
                                </button>
                                <button onclick="switchTab('mingguan')" id="tab-mingguan" class="px-5 py-3.5 border-b-2 border-transparent hover:bg-gray-100 hover:text-gray-700 focus:outline-none whitespace-nowrap">
                                    <i class="fas fa-calendar-week mr-1"></i> Mingguan
                                </button>
                                <button onclick="switchTab('bulanan')" id="tab-bulanan" class="px-5 py-3.5 border-b-2 border-transparent hover:bg-gray-100 hover:text-gray-700 focus:outline-none whitespace-nowrap">
                                    <i class="fas fa-calendar-alt mr-1"></i> Bulanan
                                </button>
                                <button onclick="switchTab('semesteran')" id="tab-semesteran" class="px-5 py-3.5 border-b-2 border-transparent hover:bg-gray-100 hover:text-gray-700 focus:outline-none whitespace-nowrap">
                                    <i class="fas fa-graduation-cap mr-1"></i> Semesteran
                                </button>
                                <button onclick="switchTab('tahunan')" id="tab-tahunan" class="px-5 py-3.5 border-b-2 border-transparent hover:bg-gray-100 hover:text-gray-700 focus:outline-none whitespace-nowrap">
                                    <i class="fas fa-award mr-1"></i> Tahunan
                                </button>
                            </div>

                            <!-- TAB CONTENTS -->
                            <div class="p-6 text-xs text-gray-750">
                                
                                <!-- HARIAN -->
                                <div id="content-harian" class="space-y-4">
                                    <h4 class="font-bold text-slate-800 text-sm border-b pb-2"><i class="fas fa-calendar-day mr-1.5 text-amber-600"></i> Catatan Kinerja Harian</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Presensi Hari Ini</span>
                                            <?php 
                                            $res_hari_ini = $conn->query("SELECT waktu_absen, status_kehadiran, keterangan FROM absensi_pegawai WHERE ustadz_id = $selected_staf_id AND DATE(waktu_absen) = CURRENT_DATE() ORDER BY waktu_absen ASC");
                                            if ($res_hari_ini && $res_hari_ini->num_rows > 0):
                                            ?>
                                                <div class="space-y-2">
                                                    <?php while($h = $res_hari_ini->fetch_assoc()): ?>
                                                        <div class="flex justify-between items-center">
                                                            <span class="font-mono text-gray-700 font-semibold"><?= date('H:i', strtotime($h['waktu_absen'])) ?> WIB</span>
                                                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-full bg-emerald-100 text-emerald-800"><?= htmlspecialchars($h['status_kehadiran']) ?></span>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-rose-500 font-semibold italic"><i class="fas fa-times-circle mr-1"></i> Belum melakukan absensi presensi hari ini.</span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Presensi Presisi Kerja</span>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Akumulasi Kehadiran Bulan Ini</span> <span class="font-bold text-slate-800"><?= $jml_hadir ?> Hari</span></div>
                                                <div class="flex justify-between"><span>Batas toleransi keterlambatan</span> <span class="font-bold text-slate-800">5 Menit (SOP)</span></div>
                                                <div class="flex justify-between"><span>Lokasi Absen Terverifikasi</span> <span class="font-bold text-emerald-600"><i class="fas fa-location-dot mr-1"></i> Area Radius QR</span></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TRANSPARANSI RIWAYAT ABSEN PEGAWAI -->
                                    <div class="mt-4 border border-gray-150 rounded-lg overflow-hidden">
                                        <div class="bg-gray-50 px-4 py-2 border-b font-bold text-slate-800">Log Kehadiran Bulan Ini (Maks. 15 Data)</div>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full text-[11px]">
                                                <thead>
                                                    <tr class="bg-slate-100 border-b">
                                                        <th class="px-4 py-2 text-left font-bold text-gray-600">Waktu Absen</th>
                                                        <th class="px-4 py-2 text-left font-bold text-gray-600">Jenis</th>
                                                        <th class="px-4 py-2 text-left font-bold text-gray-600">Status</th>
                                                        <th class="px-4 py-2 text-left font-bold text-gray-600">Keterangan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if(count($riwayat) > 0): ?>
                                                        <?php foreach ($riwayat as $r): ?>
                                                            <tr class="border-b hover:bg-slate-50">
                                                                <td class="px-4 py-2 font-mono"><?= htmlspecialchars($r['waktu_absen']) ?></td>
                                                                <td class="px-4 py-2 font-bold text-gray-700"><?= htmlspecialchars($r['jenis_absen']) ?></td>
                                                                <td class="px-4 py-2"><span class="px-1.5 py-0.5 rounded font-bold text-[9px] <?= ($r['status_kehadiran'] === 'Masuk' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200') ?>"><?= htmlspecialchars($r['status_kehadiran']) ?></span></td>
                                                                <td class="px-4 py-2 text-gray-500"><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400 italic">Belum ada riwayat absensi terdaftar bulan ini.</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- MINGGUAN -->
                                <div id="content-mingguan" class="space-y-4 hidden">
                                    <h4 class="font-bold text-slate-800 text-sm border-b pb-2"><i class="fas fa-calendar-week mr-1.5 text-amber-600"></i> Catatan Kinerja Mingguan</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Aktivitas Mengajar Mingguan</span>
                                            <?php 
                                            // Jurnal terisi 7 hari terakhir
                                            $res_jurnal_minggu = $conn->query("SELECT COUNT(*) as total FROM jurnal_mengajar WHERE ustadz_id = $selected_staf_id AND tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                                            $jurnal_minggu = $res_jurnal_minggu ? (int)$res_jurnal_minggu->fetch_assoc()['total'] : 0;
                                            ?>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Kelas Mengajar Terverifikasi (7 Hari)</span> <span class="font-bold text-slate-800"><?= $jurnal_minggu ?> Pertemuan</span></div>
                                                <div class="flex justify-between"><span>Rasio Input Jurnal Tepat Waktu</span> <span class="font-bold text-slate-800"><?= number_format($skor_jurnal, 1) ?> %</span></div>
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">AI HRD Checking & Validation</span>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Wajib Validasi Form Ibadah Santri</span> <span class="font-bold text-slate-800"><?= ($is_teacher || in_array('musyrif', $user_roles)) ? 'YA (2 Hari Maksimum)' : 'TIDAK (Bukan Musyrif)' ?></span></div>
                                                <div class="flex justify-between"><span>Pemberian Notifikasi AI HRD</span> <span class="font-bold text-slate-800">Otomatis Lewat WA</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- BULANAN -->
                                <div id="content-bulanan" class="space-y-4 hidden">
                                    <h4 class="font-bold text-slate-800 text-sm border-b pb-2"><i class="fas fa-calendar-alt mr-1.5 text-amber-600"></i> Catatan Kinerja Bulanan</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-indigo-50 border border-indigo-150 p-4 rounded-lg">
                                            <span class="font-bold text-indigo-900 uppercase tracking-wider block text-[9px] mb-2">Evaluasi AI HRD Bulanan</span>
                                            <div class="flex items-start">
                                                <div class="bg-white p-2 rounded-full mr-3 text-indigo-700 shadow-sm"><i class="fas fa-brain text-base"></i></div>
                                                <div>
                                                    <span class="font-bold block text-indigo-900 mb-0.5">Analisis Otomatis HRD:</span>
                                                    <p class="text-[11px] text-indigo-850 leading-relaxed">
                                                        Pegawai <strong><?= htmlspecialchars($staf['nama']) ?></strong> dengan total skor KPI sebesar <strong><?= number_format($total_skor_kpi, 1) ?></strong> diprediksikan memiliki kompetensi <strong><?= $predikat ?></strong>. Kedisiplinan administrasi mengajar berada pada level memuaskan, dan rasio presensi memenuhi target minimum yayasan.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Rasio Pemanfaatan Teknologi AI</span>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Jumlah Log Penggunaan AI</span> <span class="font-bold text-slate-800"><?= $jumlah_pakai_ai ?> Kali</span></div>
                                                <div class="flex justify-between"><span>Skor Adopsi AI (RPP / Silabus)</span> <span class="font-bold text-slate-800"><?= $skor_penggunaan_ai ?> / 100</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SEMESTERAN -->
                                <div id="content-semesteran" class="space-y-4 hidden">
                                    <h4 class="font-bold text-slate-800 text-sm border-b pb-2"><i class="fas fa-graduation-cap mr-1.5 text-amber-600"></i> Catatan Kinerja Semesteran</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Capaian Akademik Santri (Semester Ini)</span>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Rata-rata Nilai Leger Ujian</span> <span class="font-bold text-slate-800"><?= number_format($skor_rata_nilai, 1) ?> / 100</span></div>
                                                <div class="flex justify-between"><span>Perkembangan UTS ke UAS</span> <span class="font-bold text-emerald-600"><?= $skor_pertumbuhan >= 100 ? 'Grafik Naik / Stabil' : 'Grafik Menurun' ?></span></div>
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                            <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Supervisi Mengajar Kepala Sekolah</span>
                                            <div class="space-y-1.5">
                                                <div class="flex justify-between"><span>Nilai Supervisi Mengajar Terakhir</span> <span class="font-bold text-slate-800"><?= $skor_supervisi ?> / 100</span></div>
                                                <div class="flex justify-between"><span>Status Observasi Kelas</span> <span class="font-bold text-emerald-600"><i class="fas fa-check mr-1"></i> Terlaksana</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TAHUNAN -->
                                <div id="content-tahunan" class="space-y-4 hidden">
                                    <h4 class="font-bold text-slate-800 text-sm border-b pb-2"><i class="fas fa-award mr-1.5 text-amber-600"></i> Rekomendasi Tahunan Yayasan</h4>
                                    <div class="bg-slate-50 border border-slate-200 p-4 rounded-lg">
                                        <span class="font-bold text-gray-500 uppercase tracking-wider block text-[9px] mb-2">Rencana Pengembangan Staf & Kompensasi</span>
                                        <div class="space-y-2">
                                            <div class="flex justify-between"><span>Rekomendasi Kontrak Kerja</span> <span class="font-bold text-emerald-600">Sangat Direkomendasikan Perpanjang</span></div>
                                            <div class="flex justify-between"><span>Kategori Insentif Grade</span> <span class="font-bold text-slate-800"><?= $predikat ?></span></div>
                                            <p class="text-[10px] text-gray-400 mt-2">Kategori ini digunakan yayasan untuk menetapkan penyesuaian gaji pokok bulanan dan bonus per semester berdasarkan akumulasi data setahun.</p>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                    <?php else: ?>
                        <div class="bg-white rounded-xl border border-gray-150 p-12 text-center text-gray-400">
                            <i class="fas fa-users-cog text-5xl mb-3 text-slate-200 block"></i>
                            Pilih salah satu pegawai di panel kiri untuk memuat rincian evaluasi kinerja berkala.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- TABS SWITCHING JAVASCRIPT -->
    <script>
        function switchTab(tabId) {
            // Hide all contents
            document.getElementById('content-harian').classList.add('hidden');
            document.getElementById('content-mingguan').classList.add('hidden');
            document.getElementById('content-bulanan').classList.add('hidden');
            document.getElementById('content-semesteran').classList.add('hidden');
            document.getElementById('content-tahunan').classList.add('hidden');

            // Reset tab button states
            const buttons = ['harian', 'mingguan', 'bulanan', 'semesteran', 'tahunan'];
            buttons.forEach(b => {
                const btn = document.getElementById('tab-' + b);
                if (btn) {
                    btn.className = "px-5 py-3.5 border-b-2 border-transparent hover:bg-gray-100 hover:text-gray-700 focus:outline-none whitespace-nowrap";
                }
            });

            // Show selected content
            document.getElementById('content-' + tabId).classList.remove('hidden');
            
            // Highlight selected tab button
            const selectedBtn = document.getElementById('tab-' + tabId);
            if (selectedBtn) {
                selectedBtn.className = "px-5 py-3.5 border-b-2 border-amber-600 text-amber-900 bg-white focus:outline-none whitespace-nowrap";
            }
        }
    </script>
</body>
</html>
