<?php
/**
 * Halaman Mandiri Slip Gaji Pegawai (Universal Employee Self-Service)
 * SADIGS 4.0 - Yayasan Villa Qur'an Baron Malang
 */
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';
require_once 'helper-payroll.php';

// Deteksi Login Pegawai atau Pengurus Yayasan
$is_yayasan_admin = (!empty($_SESSION['yayasan2_logged_in']) && $_SESSION['yayasan2_logged_in'] === true)
    || (!empty($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['yayasan', 'super_admin', 'pimpinan', 'bendahara']))
    || (!empty($_SESSION['app_user_role']) && in_array(strtolower($_SESSION['app_user_role']), ['super_admin', 'yayasan']));

$user_id = $_SESSION['ustadz_id'] ?? null;
if (!$user_id && !empty($_SESSION['app_user_id'])) {
    $uid = (int)$_SESSION['app_user_id'];
    $q_ref = $conn->query("SELECT ref_id FROM app_users WHERE id = $uid LIMIT 1");
    if ($q_ref && $row_ref = $q_ref->fetch_assoc()) {
        $user_id = $row_ref['ref_id'];
    }
}

// Jika Yayasan mengakses langsung dengan parameter pegawai_id
if (!$user_id && $is_yayasan_admin) {
    if (isset($_GET['pegawai_id'])) {
        $user_id = (int)$_GET['pegawai_id'];
    } else {
        $q_first = $conn->query("SELECT id FROM akun_ustadz WHERE status_pegawai != 'Nonaktif' ORDER BY nama ASC LIMIT 1");
        $user_id = $q_first ? (int)$q_first->fetch_assoc()['id'] : 1;
    }
}

if (!$user_id) {
    header("Location: login-ustadz.php");
    exit;
}

// Cek Role & Hak Akses
$user_id = (int)$user_id;
$res_curr = $conn->query("SELECT id, nama, role, status_pegawai FROM akun_ustadz WHERE id = $user_id LIMIT 1");
$current_user = $res_curr ? $res_curr->fetch_assoc() : null;

$roles_arr = [];
if ($current_user && !empty($current_user['role'])) {
    $roles_arr = array_map('trim', array_map('strtolower', explode(',', $current_user['role'])));
}
$is_pimpinan_or_admin = $is_yayasan_admin || ($user_id === 9999) || in_array('super_admin', $roles_arr) || in_array('ketua_yayasan', $roles_arr) || in_array('bendahara_yayasan', $roles_arr) || in_array('admin_sekolah', $roles_arr);

// Target Pegawai yang Dilihat
$target_ustadz_id = $user_id;
if ($is_pimpinan_or_admin && isset($_GET['pegawai_id'])) {
    $target_ustadz_id = (int)$_GET['pegawai_id'];
}

// Filter Periode
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$selected_month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selected_year  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Hitung Payroll Lengkap Pegawai
$payroll = hitung_payroll_pegawai($conn, $target_ustadz_id, $selected_month, $selected_year);

if (!$payroll) {
    die("Data pegawai tidak ditemukan.");
}

$active_menu = 'slip_gaji';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji Saya | Yayasan Villa Qur'an Baron Malang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            aside, header, nav, #sidebar-hr, .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                background: white !important;
                overflow: visible !important;
            }
            .print-area {
                border: 2px solid #333 !important;
                box-shadow: none !important;
                padding: 24px !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 flex h-screen overflow-hidden antialiased">

    <!-- SIDEBAR -->
    <?php if (file_exists('sidebar-hr.php')) include 'sidebar-hr.php'; ?>

    <!-- CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <!-- HEADER TOPBAR (NO-PRINT) -->
        <header class="h-16 bg-white border-b border-slate-200/80 shadow-xs flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center gap-3">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-base font-bold shadow-inner">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-sm sm:text-base text-slate-800 tracking-tight">Slip Gaji Pegawai</h2>
                    <p class="text-[11px] text-slate-400 font-medium hidden sm:block">Transparansi Penghasilan, Presensi & Penilaian Prestasi Kerja (KPI)</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <?php if ($is_pimpinan_or_admin): ?>
                <a href="yayasan2/gaji-pegawai.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1.5">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden sm:inline">Rekap Payroll Yayasan</span>
                </a>
                <?php endif; ?>
                <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-md shadow-amber-900/15 transition flex items-center gap-1.5">
                    <i class="fas fa-print"></i>
                    <span>Cetak Slip (PDF)</span>
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/70 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- CONTROLS & FILTER (NO-PRINT) -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-5 sm:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-amber-600 text-white flex items-center justify-center text-xl shadow-md shadow-amber-900/20">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-base sm:text-lg font-black text-slate-800"><?= htmlspecialchars($payroll['nama']) ?></h1>
                                <span class="bg-amber-50 text-amber-800 text-[10px] font-extrabold px-2 py-0.5 rounded-full border border-amber-200">
                                    <?= htmlspecialchars($payroll['status_pegawai']) ?>
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                Siklus Kerja: <strong class="text-slate-700"><?= date('d M Y', strtotime($payroll['start_date'])) ?> s/d <?= date('d M Y', strtotime($payroll['end_date'])) ?></strong>
                            </p>
                        </div>
                    </div>

                    <!-- Filter Form -->
                    <form method="GET" class="flex flex-wrap items-center gap-2">
                        <?php if ($is_pimpinan_or_admin): ?>
                            <select name="pegawai_id" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-amber-500">
                                <?php
                                $res_all_p = $conn->query("SELECT id, nama FROM akun_ustadz WHERE status_pegawai != 'Nonaktif' ORDER BY nama ASC");
                                if ($res_all_p) {
                                    while ($ap = $res_all_p->fetch_assoc()) {
                                        $sel = ($target_ustadz_id == $ap['id']) ? 'selected' : '';
                                        echo "<option value='{$ap['id']}' $sel>" . htmlspecialchars($ap['nama']) . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        <?php endif; ?>
                        <select name="bulan" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-amber-500">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:ring-2 focus:ring-amber-500">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="p-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition" title="Terapkan Filter">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>

                <!-- CARDS RINGKASAN METRIK (NO-PRINT) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 no-print">
                    <!-- THP CARD -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Take Home Pay (THP)</span>
                            <span class="text-2xl font-black text-amber-600 block mt-0.5">Rp <?= number_format($payroll['total_thp'], 0, ',', '.') ?></span>
                            <span class="text-[10px] text-slate-400 block mt-1">Gaji bersih yang diterima</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold border border-amber-100">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>

                    <!-- KPI & GRADE CARD -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Prestasi Kerja (KPI)</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-2xl font-black text-slate-800"><?= number_format($payroll['total_skor_kpi'], 1) ?></span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-black border <?= $payroll['grade_badge'] ?>">
                                    Grade <?= $payroll['grade'] ?>
                                </span>
                            </div>
                            <span class="text-[10px] text-slate-400 block mt-1">Menentukan persentase tunjangan</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold border border-blue-100">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>

                    <!-- PRESENSI CARD -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Rekap Presensi</span>
                            <div class="flex items-center gap-2 text-xs font-bold mt-1">
                                <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200"><?= $payroll['hadir_count'] ?> Hadir</span>
                                <span class="text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200"><?= $payroll['izin_count'] + $payroll['sakit_count'] ?> Izin/Skt</span>
                                <?php if ($payroll['alpa_count'] > 0): ?>
                                <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200"><?= $payroll['alpa_count'] ?> Alfa</span>
                                <?php else: ?>
                                <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">0 Alfa</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-[10px] text-slate-400 block mt-1">Dari target <?= $payroll['target_hari_kerja'] ?> hari kerja</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold border border-emerald-100">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>

                <!-- ============================================================ -->
                <!-- LEMBAR SLIP GAJI RESMI (PRINT AREA) -->
                <!-- ============================================================ -->
                <div class="bg-white rounded-3xl border border-slate-300 shadow-md p-6 sm:p-10 print-area space-y-6">
                    
                    <!-- KOP SLIP GAJI RESMI -->
                    <div class="border-b-2 border-slate-900 pb-5 text-center relative">
                        <div class="flex items-center justify-center gap-3 mb-1">
                            <div class="w-12 h-12 rounded-2xl bg-amber-600 text-white flex items-center justify-center text-2xl font-black shadow-sm">
                                <i class="fas fa-quran"></i>
                            </div>
                            <div class="text-left">
                                <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight uppercase leading-tight">YAYASAN VILLA QUR'AN BARON MALANG</h2>
                                <p class="text-[11px] text-slate-500 font-medium">Islamic Boarding School & Entrepreneur Institute • Malang, Jawa Timur</p>
                            </div>
                        </div>
                        <div class="mt-4 pt-2 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between text-xs">
                            <span class="font-extrabold text-amber-900 tracking-wider uppercase text-sm sm:text-base">SLIP GAJI & EVALUASI PRESTASI KERJA (PAYROLL SLIP)</span>
                            <span class="text-slate-500 font-bold mt-1 sm:mt-0">Periode: <?= $months[$selected_month] ?> <?= $selected_year ?></span>
                        </div>
                    </div>

                    <!-- IDENTITAS PEGAWAI & PERIODE -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Nama Pegawai</span>
                            <span class="font-black text-slate-900 block text-sm"><?= htmlspecialchars($payroll['nama']) ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Kepegawaian</span>
                            <span class="font-bold text-slate-800 block"><?= htmlspecialchars($payroll['status_pegawai']) ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Jabatan / Amanah</span>
                            <span class="font-bold text-slate-800 block"><?= ucwords(str_replace(['_', ','], [' ', ', '], $payroll['role_str'])) ?: 'Pegawai' ?></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Siklus Kerja</span>
                            <span class="font-bold text-slate-800 block"><?= date('d/m/Y', strtotime($payroll['start_date'])) ?> - <?= date('d/m/Y', strtotime($payroll['end_date'])) ?></span>
                        </div>
                    </div>

                    <!-- BAGIAN A: REKAPITULASI PENILAIAN PRESTASI KERJA (KPI) & PRESENSI -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-200 pb-2">
                            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-clipboard-check text-amber-600"></i>
                                A. Evaluasi Kinerja (KPI) & Kehadiran (Dasar Penetapan Tunjangan)
                            </h3>
                            <span class="text-[11px] font-extrabold px-3 py-0.5 rounded-full border <?= $payroll['grade_badge'] ?>">
                                Hasil: Skor <?= number_format($payroll['total_skor_kpi'], 1) ?> (Grade <?= $payroll['grade'] ?>)
                            </span>
                        </div>

                        <!-- Presensi Summary Box -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center text-xs">
                            <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-200">
                                <span class="text-[10px] font-bold text-emerald-800 block">Total Hadir</span>
                                <span class="text-base font-black text-emerald-700"><?= $payroll['hadir_count'] ?> Hari</span>
                                <span class="text-[9px] text-emerald-600 block">(Tepat: <?= $payroll['hadir_tepat'] ?> | Telat: <?= $payroll['hadir_terlambat'] ?>)</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-blue-50 border border-blue-200">
                                <span class="text-[10px] font-bold text-blue-800 block">Izin Resmi</span>
                                <span class="text-base font-black text-blue-700"><?= $payroll['izin_count'] ?> Hari</span>
                                <span class="text-[9px] text-blue-600 block">Surat izin disetujui</span>
                            </div>
                            <div class="p-2.5 rounded-xl bg-purple-50 border border-purple-200">
                                <span class="text-[10px] font-bold text-purple-800 block">Sakit (SKD)</span>
                                <span class="text-base font-black text-purple-700"><?= $payroll['sakit_count'] ?> Hari</span>
                                <span class="text-[9px] text-purple-600 block">Surat dokter / dispensasi</span>
                            </div>
                            <div class="p-2.5 rounded-xl <?= $payroll['alpa_count'] > 0 ? 'bg-rose-50 border border-rose-300' : 'bg-slate-50 border border-slate-200' ?>">
                                <span class="text-[10px] font-bold <?= $payroll['alpa_count'] > 0 ? 'text-rose-800' : 'text-slate-500' ?> block">Alfa (Tanpa Izin)</span>
                                <span class="text-base font-black <?= $payroll['alpa_count'] > 0 ? 'text-rose-600' : 'text-slate-400' ?>"><?= $payroll['alpa_count'] ?> Hari</span>
                                <span class="text-[9px] <?= $payroll['alpa_count'] > 0 ? 'text-rose-500 font-bold' : 'text-slate-400' ?> block">
                                    <?= $payroll['alpa_count'] > 0 ? 'Dikenakan denda potongan' : 'Disiplin sempurna' ?>
                                </span>
                            </div>
                        </div>

                        <!-- Tabel Detail Indikator KPI Sesuai Role -->
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="bg-slate-100 text-[10px] uppercase font-bold text-slate-600 tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Indikator Kinerja Tugas</th>
                                        <th class="px-3 py-2 text-left">Realisasi & Keterangan</th>
                                        <th class="px-3 py-2 text-center w-24">Bobot</th>
                                        <th class="px-3 py-2 text-right w-24">Skor Indikator</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php foreach ($payroll['kpi_items'] as $item): ?>
                                    <tr>
                                        <td class="px-3 py-2 font-bold text-slate-800"><?= htmlspecialchars($item['kategori']) ?></td>
                                        <td class="px-3 py-2 text-slate-600"><?= htmlspecialchars($item['detail']) ?></td>
                                        <td class="px-3 py-2 text-center text-slate-500 font-semibold"><?= htmlspecialchars($item['bobot']) ?></td>
                                        <td class="px-3 py-2 text-right font-black text-slate-900"><?= $item['skor'] ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-slate-50 font-black text-xs">
                                    <tr>
                                        <td colspan="3" class="px-3 py-2 text-right text-slate-700 uppercase">Total Akumulasi Skor KPI:</td>
                                        <td class="px-3 py-2 text-right text-amber-700 text-sm"><?= number_format($payroll['total_skor_kpi'], 1) ?> / 100</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <p class="text-[10px] text-slate-400 italic">
                            * Catatan Transparansi: Skor KPI menentukan besaran Tunjangan Jabatan/Amanah bulanan. Grade A (≥90) memperoleh tunjangan 100%, Grade B (80-89), Grade C (&lt;80).
                        </p>
                    </div>

                    <!-- BAGIAN B & C: RINCIAN PENERIMAAN & POTONGAN -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- BAGIAN B: PENERIMAAN (EARNINGS) -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-2 flex items-center gap-2">
                                <i class="fas fa-plus-circle text-emerald-600"></i>
                                B. Penghasilan (Penerimaan)
                            </h3>
                            <div class="rounded-xl border border-slate-200 overflow-hidden text-xs">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <!-- Gaji Pokok -->
                                        <tr>
                                            <td class="px-3 py-2.5">
                                                <span class="font-bold text-slate-800 block">Gaji Pokok</span>
                                                <span class="text-[10px] text-slate-400 block"><?= $payroll['status_pegawai'] ?></span>
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-black text-slate-800">
                                                Rp <?= number_format($payroll['gaji_pokok'], 0, ',', '.') ?>
                                            </td>
                                        </tr>

                                        <!-- Rincian Tunjangan -->
                                        <?php if (!empty($payroll['rincian_tunjangan'])): ?>
                                            <?php foreach ($payroll['rincian_tunjangan'] as $tj): ?>
                                            <tr>
                                                <td class="px-3 py-2.5">
                                                    <span class="font-bold text-slate-800 block"><?= htmlspecialchars($tj['nama']) ?></span>
                                                    <span class="text-[10px] text-emerald-600 font-semibold block"><?= htmlspecialchars($tj['keterangan']) ?></span>
                                                </td>
                                                <td class="px-3 py-2.5 text-right font-black text-slate-800">
                                                    Rp <?= number_format($tj['nominal'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td class="px-3 py-2.5 text-slate-400 italic">Tunjangan Jabatan / Struktural</td>
                                                <td class="px-3 py-2.5 text-right font-semibold text-slate-400">Rp 0</td>
                                            </tr>
                                        <?php endif; ?>

                                        <!-- Honor Mengajar -->
                                        <tr>
                                            <td class="px-3 py-2.5">
                                                <span class="font-bold text-slate-800 block">Honor KBM / Mengajar</span>
                                                <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($payroll['honor_note']) ?></span>
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-black text-slate-800">
                                                Rp <?= number_format($payroll['honor'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-emerald-50/50 font-black text-xs">
                                        <tr>
                                            <td class="px-3 py-2.5 text-slate-700">Subtotal Penerimaan:</td>
                                            <td class="px-3 py-2.5 text-right text-emerald-700 text-sm">
                                                Rp <?= number_format($payroll['total_penerimaan'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- BAGIAN C: POTONGAN (DEDUCTIONS) -->
                        <div class="space-y-3">
                            <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-2 flex items-center gap-2">
                                <i class="fas fa-minus-circle text-rose-600"></i>
                                C. Potongan (Deduction)
                            </h3>
                            <div class="rounded-xl border border-slate-200 overflow-hidden text-xs">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <?php if (!empty($payroll['rincian_potongan'])): ?>
                                            <?php foreach ($payroll['rincian_potongan'] as $pt): ?>
                                            <tr>
                                                <td class="px-3 py-2.5">
                                                    <span class="font-bold text-rose-700 block"><?= htmlspecialchars($pt['nama']) ?></span>
                                                    <span class="text-[10px] text-slate-500 block"><?= htmlspecialchars($pt['keterangan']) ?></span>
                                                </td>
                                                <td class="px-3 py-2.5 text-right font-black text-rose-600">
                                                    - Rp <?= number_format($pt['nominal'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td class="px-3 py-4 text-center text-slate-400 italic" colspan="2">
                                                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Tidak ada potongan bulan ini.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <tfoot class="bg-rose-50/50 font-black text-xs">
                                        <tr>
                                            <td class="px-3 py-2.5 text-slate-700">Total Potongan:</td>
                                            <td class="px-3 py-2.5 text-right text-rose-700 text-sm">
                                                - Rp <?= number_format($payroll['total_potongan'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                    </div>

                    <!-- TOTAL THP / GAJI BERSIH (TAKE HOME PAY) -->
                    <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-50 to-amber-100/70 border-2 border-amber-300 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <span class="text-xs font-black text-amber-900 uppercase tracking-wider block">TOTAL GAJI BERSIH DITERIMA (TAKE HOME PAY)</span>
                            <span class="text-xs font-bold text-slate-600 mt-1 block italic">
                                Terbilang: <span class="text-slate-900 font-extrabold">"<?= $payroll['terbilang'] ?>"</span>
                            </span>
                        </div>
                        <div class="text-left sm:text-right">
                            <span class="text-2xl sm:text-3xl font-black text-amber-900 block tracking-tight">
                                Rp <?= number_format($payroll['total_thp'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                    <!-- TANDA TANGAN RESMI -->
                    <div class="pt-6 border-t border-slate-300 grid grid-cols-2 text-center text-xs text-slate-700">
                        <div>
                            <p class="font-medium">Mengetahui,</p>
                            <p class="font-bold text-slate-900 mt-1">Bendahara Yayasan</p>
                            <div class="h-20 flex items-end justify-center">
                                <p class="font-bold border-b border-slate-400 pb-0.5 px-6">( ........................................ )</p>
                            </div>
                        </div>
                        <div>
                            <p class="font-medium">Malang, <?= date('d M Y') ?></p>
                            <p class="font-bold text-slate-900 mt-1">Penerima (Pegawai)</p>
                            <div class="h-20 flex items-end justify-center">
                                <p class="font-bold border-b border-slate-400 pb-0.5 px-6">
                                    ( <?= htmlspecialchars($payroll['nama']) ?> )
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- SCRIPT HAMBURGER -->
    <script>
        const btnSidebar = document.getElementById('open-sidebar-hr');
        if (btnSidebar) {
            btnSidebar.addEventListener('click', () => {
                const s = document.getElementById('sidebar-hr');
                if (s) s.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>

