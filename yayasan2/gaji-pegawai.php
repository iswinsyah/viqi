<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'gaji_pegawai';

// --- FETCH CONFIGURATION ---
$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji ? $res_gaji->fetch_assoc() : [];
$gaji_grade_a = $data_gaji['gaji_grade_a'] ?? 25000;
$gaji_grade_b = $data_gaji['gaji_grade_b'] ?? 22500;
$gaji_grade_c = $data_gaji['gaji_grade_c'] ?? 20000;
$gaji_pokok_muda = $data_gaji['gaji_pokok_muda'] ?? 2500000;
$gaji_pokok_utama = $data_gaji['gaji_pokok_utama'] ?? 3500000;
$tunj_kepsek_a = $data_gaji['tunj_kepsek_a'] ?? 1500000;
$tunj_kepsek_b = $data_gaji['tunj_kepsek_b'] ?? 1000000;
$tunj_kepsek_c = $data_gaji['tunj_kepsek_c'] ?? 500000;

$tunj_mahad_a = $data_gaji['tunj_mahad_a'] ?? 1500000;
$tunj_mahad_b = $data_gaji['tunj_mahad_b'] ?? 1000000;
$tunj_mahad_c = $data_gaji['tunj_mahad_c'] ?? 500000;

$tunj_asrama_a = $data_gaji['tunj_asrama_a'] ?? 1200000;
$tunj_asrama_b = $data_gaji['tunj_asrama_b'] ?? 800000;
$tunj_asrama_c = $data_gaji['tunj_asrama_c'] ?? 400000;

$tunj_admin_a = $data_gaji['tunj_admin_a'] ?? 1000000;
$tunj_admin_b = $data_gaji['tunj_admin_b'] ?? 700000;
$tunj_admin_c = $data_gaji['tunj_admin_c'] ?? 400000;

// --- PERIOD SELECTION ---
$selected_month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selected_year = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$start_date = date('Y-m-d', mktime(0, 0, 0, $selected_month - 1, 27, $selected_year));
$end_date   = date('Y-m-d', mktime(0, 0, 0, $selected_month, 26, $selected_year));

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

function getUstadzGradeRateForPeriod($conn, $ust_id, $role_str, $gaji_grade_a, $gaji_grade_b, $gaji_grade_c, $start_date, $end_date) {
    $user_roles = !empty($role_str) ? array_map('trim', array_map('strtolower', explode(',', $role_str))) : [];
    $is_teacher = in_array('ustadz', $user_roles) || in_array('ustadzah', $user_roles) || in_array('guru', $user_roles) || in_array('tutor', $user_roles);
    $eligible_roles_pegawai = ['super_admin', 'kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'kepala_asrama_rijal', 'kepala_asrama_nisa', 'musyrif'];
    $is_daily_worker = !empty(array_intersect($eligible_roles_pegawai, $user_roles));

    // A. Jurnal Periode Ini
    $res_jurnal = $conn->query("SELECT 
        COUNT(*) as total_jurnal, 
        SUM(CASE WHEN DATE(created_at) = tanggal THEN 1 ELSE 0 END) as tepat_waktu 
        FROM jurnal_mengajar 
        WHERE ustadz_id = $ust_id AND tanggal BETWEEN '$start_date' AND '$end_date'");
    $data_jurnal = $res_jurnal ? $res_jurnal->fetch_assoc() : ['total_jurnal' => 0, 'tepat_waktu' => 0];
    $jumlah_pertemuan = (int)($data_jurnal['total_jurnal'] ?? 0);
    $tepat_waktu = (int)($data_jurnal['tepat_waktu'] ?? 0);

    // B. Hitung skor jurnal
    if ($is_teacher) {
        $skor_jurnal = $jumlah_pertemuan > 0 ? ($tepat_waktu / $jumlah_pertemuan) * 100 : 100;
    } else {
        $skor_jurnal = 100;
    }

    // C. Kehadiran Harian/Pegawai
    $res_hadir = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen IN ('Pegawai', 'Harian') AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
    $jml_hadir = $res_hadir ? (int)($res_hadir->fetch_assoc()['jml'] ?? 0) : 0;

    if ($is_daily_worker) {
        $skor_kehadiran = $jml_hadir > 0 ? min(100, ($jml_hadir / 20) * 100) : 0;
    } else {
        // Jika ustadz honorer saja
        $res_hadir_mengajar = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen = 'Mengajar' AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
        $jml_hadir_mengajar = $res_hadir_mengajar ? (int)($res_hadir_mengajar->fetch_assoc()['jml'] ?? 0) : 0;
        
        $res_total_teaching_days = $conn->query("SELECT COUNT(DISTINCT tanggal) as total_days FROM jurnal_mengajar WHERE ustadz_id = $ust_id AND tanggal BETWEEN '$start_date' AND '$end_date'");
        $total_teaching_days = $res_total_teaching_days ? (int)($res_total_teaching_days->fetch_assoc()['total_days'] ?? 0) : 0;
        
        $skor_kehadiran = $total_teaching_days > 0 ? min(100, ($jml_hadir_mengajar / $total_teaching_days) * 100) : 100;
    }

    // D. Kehadiran Rapat
    $res_rapat = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen = 'Rapat' AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
    $jml_rapat = $res_rapat ? (int)($res_rapat->fetch_assoc()['jml'] ?? 0) : 0;

    $res_total_rapat = $conn->query("SELECT COUNT(*) as total FROM jadwal_rapat WHERE DATE(waktu_mulai) BETWEEN '$start_date' AND '$end_date'");
    $total_rapat = $res_total_rapat ? (int)($res_total_rapat->fetch_assoc()['total'] ?? 0) : 0;
    $skor_kehadiran_rapat = $total_rapat > 0 ? min(100, ($jml_rapat / $total_rapat) * 100) : 100;

    $skor_administrasi = (($skor_jurnal * 0.4) + ($skor_kehadiran * 0.4) + ($skor_kehadiran_rapat * 0.2));

    // E. Kualitas Pengajaran
    $res_ai = $conn->query("SELECT COUNT(*) as pemakaian FROM log_aktivitas_ai WHERE user_id = $ust_id AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");
    $jumlah_pakai_ai = $res_ai ? (int)($res_ai->fetch_assoc()['pemakaian'] ?? 0) : 0;
    $skor_penggunaan_ai = $jumlah_pakai_ai >= 5 ? 100 : ($jumlah_pakai_ai > 0 ? 85 : 70);

    if ($is_teacher) {
        $res_sup = $conn->query("SELECT skor FROM supervisi_mengajar WHERE user_id = $ust_id ORDER BY tanggal_supervisi DESC LIMIT 1");
        $skor_supervisi = $res_sup && $res_sup->num_rows > 0 ? (int)($res_sup->fetch_assoc()['skor']) : 85;
    } else {
        $skor_supervisi = 100;
    }
    $skor_kualitas_pengajaran = (($skor_penggunaan_ai * 0.4) + ($skor_supervisi * 0.6));

    // F. Capaian Santri
    $res_leger = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE created_by = $ust_id AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");
    $avg_nilai = $res_leger ? (float)($res_leger->fetch_assoc()['rata_rata'] ?? 0) : 0;
    if ($avg_nilai <= 0) {
        $res_fb = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE created_by = $ust_id");
        $avg_nilai = $res_fb ? (float)($res_fb->fetch_assoc()['rata_rata'] ?? 0) : 0;
    }
    $skor_rata_nilai = $avg_nilai >= 75 ? 100 : ($avg_nilai > 0 ? ($avg_nilai / 75) * 100 : 0);

    $res_koreksi = $conn->query("SELECT SUM(CASE WHEN status_koreksi = 'Tuntas' THEN 1 ELSE 0 END) as tuntas, COUNT(*) as total FROM leger_koreksi WHERE ustadz_id = $ust_id AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");
    $data_koreksi = $res_koreksi ? $res_koreksi->fetch_assoc() : ['tuntas' => 0, 'total' => 0];
    $tuntas_koreksi = (int)($data_koreksi['tuntas'] ?? 0);
    $total_koreksi = (int)($data_koreksi['total'] ?? 0);
    $skor_pertumbuhan = $total_koreksi > 0 ? ($tuntas_koreksi / $total_koreksi) * 100 : 100;

    $skor_capaian_santri = (($skor_rata_nilai * 0.5) + ($skor_pertumbuhan * 0.5));

    // G. Pengembangan Diri
    $skor_pengembangan_diri = 100;

    // Total KPI
    $total_skor_kpi = ($skor_administrasi * 0.20) + ($skor_kualitas_pengajaran * 0.40) + ($skor_capaian_santri * 0.30) + ($skor_pengembangan_diri * 0.10);

    if ($total_skor_kpi >= 90) {
        return [$gaji_grade_a, 'A', $total_skor_kpi];
    } elseif ($total_skor_kpi >= 80) {
        return [$gaji_grade_b, 'B', $total_skor_kpi];
    } else {
        return [$gaji_grade_c, 'C', $total_skor_kpi];
    }
}

// Compile payroll list
$payroll_list = [];
$res_pegawai = $conn->query("SELECT * FROM akun_ustadz WHERE status_pegawai != 'Nonaktif' ORDER BY nama ASC");
if ($res_pegawai && $res_pegawai->num_rows > 0) {
    while ($row = $res_pegawai->fetch_assoc()) {
        $ust_id = (int)$row['id'];
        $status_display = htmlspecialchars($row['status_pegawai'] ?? 'Pengabdian');
        
        // 1. Gaji Pokok
        $gaji_pokok = 0;
        if ($status_display === 'Pegawai Muda') {
            $gaji_pokok = $gaji_pokok_muda;
        } elseif ($status_display === 'Pegawai Utama') {
            $gaji_pokok = $gaji_pokok_utama;
        }

        // 3. Honor Mengajar (Kombinasi Jurnal Mengajar & Absensi QR Mengajar)
        $res_jurnal = $conn->query("SELECT COUNT(*) as total FROM jurnal_mengajar WHERE ustadz_id = $ust_id AND tanggal BETWEEN '$start_date' AND '$end_date'");
        $cnt_jurnal = $res_jurnal ? (int)$res_jurnal->fetch_assoc()['total'] : 0;
        
        $res_abs = $conn->query("SELECT COUNT(*) as total FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen = 'Mengajar' AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
        $cnt_abs = $res_abs ? (int)$res_abs->fetch_assoc()['total'] : 0;

        $total_pertemuan = max($cnt_jurnal, $cnt_abs);
        
        list($active_rate, $active_grade, $kpi_score) = getUstadzGradeRateForPeriod($conn, $ust_id, $row['role'], $gaji_grade_a, $gaji_grade_b, $gaji_grade_c, $start_date, $end_date);

        $roles_arr = !empty($row['role']) ? array_map('trim', array_map('strtolower', explode(',', $row['role']))) : [];
        $has_ustadz_role = in_array('ustadz', $roles_arr) || in_array('ustadzah', $roles_arr) || in_array('tutor', $roles_arr) || in_array('guru', $roles_arr);
        $is_utama_or_muda = ($status_display === 'Pegawai Utama' || $status_display === 'Pegawai Muda');
        
        if ($is_utama_or_muda && $has_ustadz_role) {
            $kelebihan_jam = max(0, $total_pertemuan - 12);
            $honor = $kelebihan_jam * $active_rate;
            $honor_note = "Kelebihan: {$kelebihan_jam}x (Grade {$active_grade})";
        } else {
            $honor = $total_pertemuan * $active_rate;
            $honor_note = "{$total_pertemuan}x (Grade {$active_grade})";
        }

        // 2. Tunjangan (Disesuaikan dengan KPI Grade bulan ini)
        $tunjangan = 0;
        if (!empty($row['role'])) {
            $role_list = explode(',', $row['role']);
            foreach ($role_list as $r) {
                if ($r === 'kepala_sekolah') {
                    if ($active_grade === 'A') $tunjangan += $tunj_kepsek_a;
                    elseif ($active_grade === 'B') $tunjangan += $tunj_kepsek_b;
                    else $tunjangan += $tunj_kepsek_c;
                }
                elseif ($r === 'kepala_mahad') {
                    if ($active_grade === 'A') $tunjangan += $tunj_mahad_a;
                    elseif ($active_grade === 'B') $tunjangan += $tunj_mahad_b;
                    else $tunjangan += $tunj_mahad_c;
                }
                elseif ($r === 'kepala_asrama' || $r === 'kepala_asrama_rijal' || $r === 'kepala_asrama_nisa') {
                    if ($active_grade === 'A') $tunjangan += $tunj_asrama_a;
                    elseif ($active_grade === 'B') $tunjangan += $tunj_asrama_b;
                    else $tunjangan += $tunj_asrama_c;
                }
                elseif ($r === 'admin_sekolah') {
                    if ($active_grade === 'A') $tunjangan += $tunj_admin_a;
                    elseif ($active_grade === 'B') $tunjangan += $tunj_admin_b;
                    else $tunjangan += $tunj_admin_c;
                }
            }
        }

        $total_thp = $gaji_pokok + $tunjangan + $honor;

        $payroll_list[] = [
            'nama' => $row['nama'],
            'status' => $status_display,
            'role' => $row['role'],
            'gaji_pokok' => $gaji_pokok,
            'tunjangan' => $tunjangan,
            'honor' => $honor,
            'honor_note' => $honor_note,
            'total_thp' => $total_thp,
            'kpi_score' => $kpi_score,
            'active_grade' => $active_grade
        ];
    }
}

// Summary Statistics
$total_payroll = array_sum(array_column($payroll_list, 'total_thp'));
$avg_payroll = count($payroll_list) > 0 ? $total_payroll / count($payroll_list) : 0;
$total_employees = count($payroll_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Gaji (Payroll) | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            aside, header, .no-print {
                display: none !important;
            }
            main {
                padding: 0 !important;
                background: white !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="gaji-asatidz.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2 rounded-lg text-xs shadow-sm flex items-center gap-2 border border-slate-200 transition">
                    <i class="fas fa-cog text-slate-500"></i> Pengaturan Gaji & Tunjangan
                </a>
                <button onclick="window.print()" class="bg-amber-500 hover:bg-amber-600 text-amber-950 font-bold px-4 py-2 rounded-lg text-xs shadow-sm flex items-center gap-2 transition">
                    <i class="fas fa-print"></i> Cetak Payroll
                </button>
            </div>
        </header>

        <!-- MAIN CONTAINER -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <!-- PAGE TITLE / PERIOD FILTER -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-coins text-amber-600"></i> Rekap Gaji Bulanan (Payroll)
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">Laporan total pengeluaran Take Home Pay (THP) pegawai berdasarkan status, peran, dan penilaian KPI.</p>
                </div>
                <!-- Filter Form -->
                <form method="GET" class="flex items-center gap-2">
                    <select name="bulan" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold text-gray-750 shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" onchange="this.form.submit()" class="bg-white border border-gray-300 rounded-lg px-3 py-2 text-xs font-bold text-gray-750 shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500">
                        <?php 
                        $curr_yr = (int)date('Y');
                        for ($y = $curr_yr - 2; $y <= $curr_yr + 1; $y++):
                        ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <!-- PRINT-ONLY HEADER -->
            <div class="hidden print:block text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">REKAPITULASI DAFTAR GAJI PEGAWAI (PAYROLL)</h1>
                <p class="text-sm text-gray-600 font-bold mt-1">Siklus Kerja: <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?> (Periode <?= $months[$selected_month] ?> <?= $selected_year ?>)</p>
                <hr class="border-gray-300 mt-4">
            </div>

            <!-- STATISTICS CARDS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Card 1 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex items-center justify-between print-card">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Pengeluaran Gaji</span>
                        <span class="text-2xl font-black text-amber-600 block">Rp <?= number_format($total_payroll, 0, ',', '.') ?></span>
                        <span class="text-[10px] text-gray-400 block mt-1">Akumulasi seluruh pegawai aktif</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100 no-print">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex items-center justify-between print-card">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Rata-rata Gaji Pegawai</span>
                        <span class="text-2xl font-black text-indigo-600 block">Rp <?= number_format($avg_payroll, 0, ',', '.') ?></span>
                        <span class="text-[10px] text-gray-400 block mt-1">Rata-rata THP per orang</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-650 flex items-center justify-center text-xl font-bold border border-indigo-100 no-print">
                        <i class="fas fa-hand-holding-dollar"></i>
                    </div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex items-center justify-between print-card">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Pegawai Terima Gaji</span>
                        <span class="text-2xl font-black text-emerald-600 block"><?= $total_employees ?> Orang</span>
                        <span class="text-[10px] text-gray-400 block mt-1">Jumlah pegawai aktif bulan ini</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-650 flex items-center justify-center text-xl font-bold border border-emerald-100 no-print">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>

            <!-- PAYROLL TABLE CARD -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden print-card">
                <div class="px-6 py-4 bg-gray-55/70 border-b border-gray-200 flex justify-between items-center no-print">
                    <h3 class="font-extrabold text-sm text-gray-800 flex items-center gap-2">
                        <i class="fas fa-list-check text-slate-500"></i> Rincian Payroll Pegawai
                    </h3>
                    <span class="text-[10px] bg-slate-100 text-slate-600 border border-slate-200 px-3 py-1 rounded-full font-bold">
                        Siklus: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-150">
                        <thead class="bg-gray-50/50 text-[10px] uppercase font-bold text-gray-500 tracking-wider">
                            <tr>
                                <th class="px-4 py-3.5 text-center w-10">No</th>
                                <th class="px-4 py-3.5 text-left">Nama Pegawai</th>
                                <th class="px-4 py-3.5 text-left">Status</th>
                                <th class="px-4 py-3.5 text-left">Jabatan / Peran</th>
                                <th class="px-4 py-3.5 text-right">Gaji Pokok</th>
                                <th class="px-4 py-3.5 text-right">Tunjangan</th>
                                <th class="px-4 py-3.5 text-right">Honor Mengajar</th>
                                <th class="px-4 py-3.5 text-right bg-amber-50/50 text-amber-950 font-black">Total THP</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-150 text-xs">
                            <?php if (empty($payroll_list)): ?>
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-400 italic">Tidak ada data pegawai aktif.</td>
                            </tr>
                            <?php else: $no=1; foreach ($payroll_list as $p): ?>
                                <?php 
                                $status_color = 'bg-gray-100 text-gray-800 border-gray-200';
                                if ($p['status'] === 'Pengurus Yayasan') $status_color = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                                if ($p['status'] === 'Staff LDU') $status_color = 'bg-cyan-50 text-cyan-700 border-cyan-200';
                                if ($p['status'] === 'Pengabdian') $status_color = 'bg-blue-50 text-blue-700 border-blue-200';
                                if ($p['status'] === 'Honorer') $status_color = 'bg-amber-50 text-amber-700 border-amber-200';
                                if ($p['status'] === 'Pegawai Muda') $status_color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                if ($p['status'] === 'Pegawai Utama') $status_color = 'bg-purple-50 text-purple-700 border-purple-200';

                                $roles_display_html = '';
                                if (!empty($p['role'])) {
                                    $role_list = explode(',', $p['role']);
                                    foreach ($role_list as $r) {
                                        $label = ucwords(str_replace('_', ' ', $r));
                                        $roles_display_html .= "<span class='inline-block bg-amber-50 text-amber-800 border border-amber-200/50 rounded px-1.5 py-0.5 text-[9px] font-semibold mr-1 mb-1'>$label</span>";
                                    }
                                } else {
                                    $roles_display_html = "<span class='text-gray-400 italic'>-</span>";
                                }
                                ?>
                                <tr class="hover:bg-gray-50/50 transition">
                                    <td class="px-4 py-3.5 text-center font-bold text-gray-500"><?= $no++ ?></td>
                                    <td class="px-4 py-3.5 font-bold text-gray-900"><?= htmlspecialchars($p['nama']) ?></td>
                                    <td class="px-4 py-3.5">
                                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold border <?= $status_color ?>"><?= $p['status'] ?></span>
                                    </td>
                                    <td class="px-4 py-3.5"><?= $roles_display_html ?></td>
                                    <td class="px-4 py-3.5 text-right font-semibold text-slate-700">Rp <?= number_format($p['gaji_pokok'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3.5 text-right font-semibold text-slate-700">Rp <?= number_format($p['tunjangan'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3.5 text-right">
                                        <span class="font-semibold text-slate-700">Rp <?= number_format($p['honor'], 0, ',', '.') ?></span>
                                        <span class="text-[9px] text-gray-400 block font-normal mt-0.5"><?= $p['honor_note'] ?></span>
                                        <span class="text-[9px] text-emerald-600 block font-bold mt-0.5">KPI: <?= number_format($p['kpi_score'], 1) ?> Poin</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-extrabold text-amber-600 bg-amber-50/10">Rp <?= number_format($p['total_thp'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => {
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden');
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden');
        });
        document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => {
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden');
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden');
        });
    </script>
</body>
</html>