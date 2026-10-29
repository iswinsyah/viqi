<?php
require_once 'auth.php';
require_once '../koneksi.php';
require_once '../helper-payroll.php';

$active_menu = 'gaji_pegawai';

// --- PERIOD SELECTION (SYNC WITH SIKLUS PENGGAJIAN 27-26) ---
$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$current_day = (int)date('d');
if ($current_day >= 27) {
    $default_month = (int)date('m', strtotime('+1 month'));
    $default_year  = (int)date('Y', strtotime('+1 month'));
} else {
    $default_month = (int)date('m');
    $default_year  = (int)date('Y');
}

$selected_month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : $default_month;
$selected_year  = isset($_GET['tahun']) ? (int)$_GET['tahun'] : $default_year;

$start_date = date('Y-m-d', mktime(0, 0, 0, $selected_month - 1, 27, $selected_year));
$end_date   = date('Y-m-d', mktime(0, 0, 0, $selected_month, 26, $selected_year));

// --- COMPILE PAYROLL LIST WITH COMPREHENSIVE KPI & ATTENDANCE BREAKDOWN ---
$payroll_list = [];
$res_pegawai = $conn->query("SELECT id FROM akun_ustadz WHERE status_pegawai != 'Nonaktif' ORDER BY nama ASC");
if ($res_pegawai && $res_pegawai->num_rows > 0) {
    while ($row = $res_pegawai->fetch_assoc()) {
        $p = hitung_payroll_pegawai($conn, (int)$row['id'], $selected_month, $selected_year);
        if ($p) {
            $payroll_list[] = $p;
        }
    }
}

// Summary Statistics
$total_gaji_pokok = array_sum(array_column($payroll_list, 'gaji_pokok'));
$total_tunjangan  = array_sum(array_column($payroll_list, 'tunjangan_total'));
$total_honor      = array_sum(array_column($payroll_list, 'honor'));
$total_payroll    = array_sum(array_column($payroll_list, 'total_thp'));
$avg_payroll      = count($payroll_list) > 0 ? $total_payroll / count($payroll_list) : 0;
$total_employees  = count($payroll_list);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Gaji & Transparansi KPI Pegawai | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            aside, header, #sidebar-yayasan2, #sidebar-overlay-yayasan2, .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
            main {
                padding: 0 !important;
                background: white !important;
                overflow: visible !important;
            }
            .print-card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
            #slipModal {
                display: none !important;
            }
        }
        @media print {
            body.printing-slip > *:not(#slipPrintContainer) {
                display: none !important;
            }
            #slipPrintContainer {
                display: block !important;
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: white !important;
                padding: 20px !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>

    <!-- CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">

        <!-- HEADER TOPBAR -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500 text-amber-950 flex items-center justify-center font-bold">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-900 leading-tight">Panel Eksekutif Yayasan</h2>
                        <p class="text-[10px] text-gray-400">Payroll, Gaji Pokok, Tunjangan & Honor KBM Pegawai</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="asatidz.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs shadow-xs flex items-center gap-1.5 border border-slate-200 transition">
                    <i class="fas fa-users-cog text-slate-500"></i>
                    <span class="hidden sm:inline">Data Akun Asatidz</span>
                </a>
                <a href="gaji-asatidz.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-2 rounded-xl text-xs shadow-xs flex items-center gap-1.5 border border-slate-200 transition">
                    <i class="fas fa-cog text-slate-500"></i>
                    <span class="hidden sm:inline">Pengaturan Gaji & Tunjangan</span>
                </a>
                <button onclick="window.print()" class="bg-amber-500 hover:bg-amber-600 text-amber-950 font-bold px-4 py-2 rounded-xl text-xs shadow-sm flex items-center gap-1.5 transition">
                    <i class="fas fa-print"></i>
                    <span>Cetak Rekap</span>
                </button>
            </div>
        </header>

        <!-- MAIN CONTAINER -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- PAGE TITLE & PERIOD FILTER -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
                    <div>
                        <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2.5 tracking-tight">
                            <i class="fas fa-money-check-dollar text-amber-600"></i> Rekap Gaji & Payroll Pegawai
                        </h1>
                        <p class="text-xs text-gray-500 mt-1">
                            Perhitungan transparan: <strong>Gaji Pokok</strong> + <strong>Tunjangan (KPI & Presensi)</strong> + <strong>Honor KBM</strong> = <strong>Take Home Pay (THP)</strong>.
                        </p>
                    </div>
                    <!-- Filter Form -->
                    <form method="GET" class="flex items-center gap-2 bg-white p-1.5 rounded-2xl border border-gray-200 shadow-xs">
                        <select name="bulan" onchange="this.form.submit()" class="bg-gray-50 border-0 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-amber-500">
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" onchange="this.form.submit()" class="bg-gray-50 border-0 rounded-xl px-3 py-2 text-xs font-bold text-gray-700 focus:bg-white focus:ring-2 focus:ring-amber-500">
                            <?php 
                            $curr_yr = (int)date('Y');
                            for ($y = $curr_yr - 2; $y <= $curr_yr + 1; $y++):
                            ?>
                                <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="p-2 bg-gray-800 text-white rounded-xl text-xs hover:bg-gray-900 transition">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>

                <!-- FORMULA PERHITUNGAN THP BANNER (NO-PRINT) -->
                <div class="p-4 bg-gradient-to-r from-amber-50 via-white to-amber-50/70 border border-amber-200/90 rounded-2xl flex flex-wrap items-center justify-between text-xs text-amber-950 font-bold gap-3 shadow-xs no-print">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-500 text-amber-950 flex items-center justify-center font-black">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <span class="text-slate-800">Rumus Kalkulasi Take Home Pay (THP):</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="bg-white px-3 py-1.5 rounded-xl border border-slate-200 text-slate-800 shadow-xs font-black">Gaji Pokok</span>
                        <span class="text-amber-600 font-black text-sm">+</span>
                        <span class="bg-white px-3 py-1.5 rounded-xl border border-amber-300 text-amber-900 shadow-xs font-black">Tunjangan (KPI & Grade)</span>
                        <span class="text-amber-600 font-black text-sm">+</span>
                        <span class="bg-white px-3 py-1.5 rounded-xl border border-indigo-200 text-indigo-900 shadow-xs font-black">Honor Mengajar</span>
                        <span class="text-amber-600 font-black text-sm">=</span>
                        <span class="bg-gradient-to-r from-amber-600 to-amber-700 text-white px-3.5 py-1.5 rounded-xl font-black shadow-sm">Take Home Pay</span>
                    </div>
                </div>

                <!-- PRINT-ONLY HEADER -->
                <div class="hidden print:block text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 uppercase">YAYASAN VILLA QUR'AN BARON MALANG</h1>
                    <h2 class="text-lg font-bold text-gray-800 mt-0.5">REKAPITULASI DAFTAR GAJI PEGAWAI (PAYROLL)</h2>
                    <p class="text-xs text-gray-600 font-bold mt-1">
                        Siklus Kerja: <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?> (Periode <?= $months[$selected_month] ?> <?= $selected_year ?>)
                    </p>
                    <hr class="border-gray-400 mt-3">
                </div>

                <!-- STATISTICS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 no-print">
                    <!-- Total THP -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Pengeluaran THP</span>
                            <span class="text-2xl font-black text-amber-600 block">Rp <?= number_format($total_payroll, 0, ',', '.') ?></span>
                            <span class="text-[10px] text-gray-400 block mt-1">Akumulasi seluruh THP bersih</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold border border-amber-100">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>

                    <!-- Gaji Pokok Total -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Gaji Pokok</span>
                            <span class="text-xl font-black text-slate-800 block">Rp <?= number_format($total_gaji_pokok, 0, ',', '.') ?></span>
                            <span class="text-[10px] text-gray-400 block mt-1">Komponen tetap pegawai</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg font-bold border border-slate-200">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>

                    <!-- Tunjangan & Honor Total -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Tunjangan & Honor</span>
                            <span class="text-xl font-black text-indigo-600 block">Rp <?= number_format($total_tunjangan + $total_honor, 0, ',', '.') ?></span>
                            <span class="text-[10px] text-gray-400 block mt-1">Tunj: Rp <?= number_format($total_tunjangan, 0, ',', '.') ?> | Hnr: Rp <?= number_format($total_honor, 0, ',', '.') ?></span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold border border-indigo-100">
                            <i class="fas fa-hand-holding-dollar"></i>
                        </div>
                    </div>

                    <!-- Penerima Gaji -->
                    <div class="bg-white rounded-2xl shadow-xs border border-gray-200/80 p-5 flex items-center justify-between">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Penerima Gaji Aktif</span>
                            <span class="text-2xl font-black text-emerald-600 block"><?= $total_employees ?> Orang</span>
                            <span class="text-[10px] text-gray-400 block mt-1">Rata-rata: Rp <?= number_format($avg_payroll, 0, ',', '.') ?>/staf</span>
                        </div>
                        <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold border border-emerald-100">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <!-- PAYROLL TABLE CARD -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden print-card">
                    <div class="px-6 py-4 bg-gray-50/70 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 no-print">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-table-list text-slate-500"></i>
                            <h3 class="font-extrabold text-sm text-gray-900">Daftar Payroll, Gaji Pokok, Tunjangan & Honor Pegawai</h3>
                        </div>
                        <span class="text-[11px] bg-slate-100 text-slate-700 border border-slate-200 px-3 py-1 rounded-full font-bold">
                            Siklus: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50/90 text-[10px] uppercase font-extrabold text-gray-600 tracking-wider">
                                <tr>
                                    <th class="px-3 py-3.5 text-center w-8">No</th>
                                    <th class="px-3 py-3.5 text-left">Nama Pegawai</th>
                                    <th class="px-3 py-3.5 text-left">Jabatan & Status</th>
                                    <th class="px-3 py-3.5 text-center bg-blue-50/40 text-blue-900">Presensi (Hadir/Alfa)</th>
                                    <th class="px-3 py-3.5 text-center bg-purple-50/40 text-purple-900">Prestasi KPI & Grade</th>
                                    <th class="px-3 py-3.5 text-right bg-slate-100/60 text-slate-800 font-extrabold">Gaji Pokok</th>
                                    <th class="px-3 py-3.5 text-right bg-amber-50/50 text-amber-900 font-extrabold">Tunjangan (KPI)</th>
                                    <th class="px-3 py-3.5 text-right bg-indigo-50/40 text-indigo-900 font-extrabold">Honor KBM</th>
                                    <th class="px-3 py-3.5 text-right bg-amber-100/70 text-amber-950 font-black">Take Home Pay</th>
                                    <th class="px-3 py-3.5 text-center no-print">Slip Gaji</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <?php if (empty($payroll_list)): ?>
                                <tr>
                                    <td colspan="10" class="px-4 py-12 text-center text-gray-400 italic">Tidak ada data pegawai aktif pada periode ini.</td>
                                </tr>
                                <?php else: $no=1; foreach ($payroll_list as $idx => $p): ?>
                                    <?php 
                                    $status_color = 'bg-gray-100 text-gray-800 border-gray-200';
                                    if ($p['status_pegawai'] === 'Pengurus Yayasan') $status_color = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                                    if ($p['status_pegawai'] === 'Staff LDU') $status_color = 'bg-cyan-50 text-cyan-700 border-cyan-200';
                                    if ($p['status_pegawai'] === 'Pengabdian') $status_color = 'bg-blue-50 text-blue-700 border-blue-200';
                                    if ($p['status_pegawai'] === 'Honorer') $status_color = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($p['status_pegawai'] === 'Pegawai Muda') $status_color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($p['status_pegawai'] === 'Pegawai Utama') $status_color = 'bg-purple-50 text-purple-700 border-purple-200';

                                    $roles_display_html = '';
                                    if (!empty($p['role_str'])) {
                                        $role_list = explode(',', $p['role_str']);
                                        foreach ($role_list as $r) {
                                            $label = ucwords(str_replace('_', ' ', trim($r)));
                                            $roles_display_html .= "<span class='inline-block bg-amber-50 text-amber-800 border border-amber-200/50 rounded px-1.5 py-0.5 text-[9px] font-semibold mr-1 mb-0.5'>$label</span>";
                                        }
                                    } else {
                                        $roles_display_html = "<span class='text-gray-400 italic'>-</span>";
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50/70 transition">
                                        <!-- No -->
                                        <td class="px-3 py-3 text-center font-bold text-gray-400"><?= $no++ ?></td>
                                        
                                        <!-- Nama -->
                                        <td class="px-3 py-3">
                                            <span class="font-black text-gray-900 block"><?= htmlspecialchars($p['nama']) ?></span>
                                            <span class="text-[10px] text-gray-400 block">ID: UST-<?= str_pad($p['ustadz_id'], 3, '0', STR_PAD_LEFT) ?></span>
                                        </td>
                                        
                                        <!-- Jabatan & Status -->
                                        <td class="px-3 py-3">
                                            <div class="mb-1"><?= $roles_display_html ?></div>
                                            <span class="inline-block px-2 py-0.5 rounded text-[9px] font-bold border <?= $status_color ?>"><?= $p['status_pegawai'] ?></span>
                                        </td>

                                        <!-- Presensi Kerja (Hadir / Izin / Alfa) -->
                                        <td class="px-3 py-3 text-center bg-blue-50/15">
                                            <div class="flex flex-col items-center gap-1">
                                                <div class="flex items-center gap-1">
                                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-1.5 py-0.5 rounded text-[10px] font-bold" title="Hadir Tepat: <?= $p['hadir_tepat'] ?>, Telat: <?= $p['hadir_terlambat'] ?>">
                                                        <?= $p['hadir_count'] ?> Hadir
                                                    </span>
                                                    <span class="bg-slate-100 text-slate-600 border border-slate-200 px-1.5 py-0.5 rounded text-[10px] font-semibold">
                                                        <?= $p['izin_count'] + $p['sakit_count'] ?> Izin
                                                    </span>
                                                </div>
                                                <?php if ($p['alpa_count'] > 0): ?>
                                                <span class="bg-rose-100 text-rose-800 border border-rose-300 px-2 py-0.5 rounded text-[10px] font-extrabold" title="Alpa: <?= implode(', ', $p['alpa_dates']) ?>">
                                                    ⚠️ <?= $p['alpa_count'] ?> Hari Alfa
                                                </span>
                                                <?php else: ?>
                                                <span class="text-emerald-600 text-[10px] font-semibold">
                                                    <i class="fas fa-check-circle text-[9px]"></i> 0 Alfa
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Prestasi KPI & Grade -->
                                        <td class="px-3 py-3 text-center bg-purple-50/15">
                                            <span class="text-xs font-black text-gray-900 block"><?= number_format($p['total_skor_kpi'], 1) ?> Poin</span>
                                            <span class="inline-block text-[10px] font-extrabold px-2 py-0.5 rounded-full border <?= $p['grade_badge'] ?> mt-0.5">
                                                Grade <?= $p['grade'] ?>
                                            </span>
                                        </td>

                                        <!-- Gaji Pokok -->
                                        <td class="px-3 py-3 text-right font-black text-slate-800 bg-slate-50/40">
                                            Rp <?= number_format($p['gaji_pokok'], 0, ',', '.') ?>
                                            <span class="text-[9px] text-gray-400 block font-normal mt-0.5"><?= $p['status_pegawai'] ?></span>
                                        </td>

                                        <!-- Rincian Tunjangan -->
                                        <td class="px-3 py-3 text-right bg-amber-50/20">
                                            <span class="font-black text-amber-900 block">Rp <?= number_format($p['tunjangan_total'], 0, ',', '.') ?></span>
                                            <?php if (!empty($p['rincian_tunjangan'])): ?>
                                                <div class="mt-0.5 flex flex-col items-end gap-0.5">
                                                    <?php foreach ($p['rincian_tunjangan'] as $rt): ?>
                                                        <span class="text-[9px] text-gray-500 block">
                                                            <?= htmlspecialchars($rt['nama']) ?> (Grade <?= $rt['grade'] ?>)
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-[9px] text-gray-400 italic block">-</span>
                                            <?php endif; ?>

                                            <?php if ($p['total_potongan'] > 0): ?>
                                                <span class="text-[9px] font-bold text-rose-600 block mt-0.5">
                                                    Denda Alfa: -Rp <?= number_format($p['total_potongan'], 0, ',', '.') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Honor Mengajar -->
                                        <td class="px-3 py-3 text-right bg-indigo-50/15">
                                            <span class="font-black text-indigo-900 block">Rp <?= number_format($p['honor'], 0, ',', '.') ?></span>
                                            <span class="text-[9px] text-gray-500 block mt-0.5"><?= htmlspecialchars($p['honor_note']) ?></span>
                                        </td>

                                        <!-- Total Take Home Pay -->
                                        <td class="px-3 py-3 text-right font-black text-amber-900 bg-amber-100/50 text-sm">
                                            Rp <?= number_format($p['total_thp'], 0, ',', '.') ?>
                                            <span class="text-[9px] text-amber-700/80 block font-normal mt-0.5">Pokok + Tunj + Hnr</span>
                                        </td>

                                        <!-- Aksi / Slip Gaji -->
                                        <td class="px-3 py-3 text-center no-print">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="openSlipModal(<?= $idx ?>)" class="bg-amber-500 hover:bg-amber-600 text-amber-950 font-bold px-2.5 py-1.5 rounded-lg text-xs shadow-xs transition flex items-center gap-1" title="Lihat Slip Gaji & Detail Penilaian">
                                                    <i class="fas fa-file-invoice-dollar"></i>
                                                    <span>Slip</span>
                                                </button>
                                                <a href="../slip-gaji.php?pegawai_id=<?= $p['ustadz_id'] ?>&bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>" target="_blank" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs transition" title="Buka Slip di Tab Baru">
                                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                            <tfoot class="bg-gray-50/90 font-black text-xs border-t-2 border-gray-300">
                                <tr>
                                    <td colspan="5" class="px-3 py-3.5 text-right uppercase text-gray-700">Akumulasi Total:</td>
                                    <td class="px-3 py-3.5 text-right text-slate-900">Rp <?= number_format($total_gaji_pokok, 0, ',', '.') ?></td>
                                    <td class="px-3 py-3.5 text-right text-amber-900">Rp <?= number_format($total_tunjangan, 0, ',', '.') ?></td>
                                    <td class="px-3 py-3.5 text-right text-indigo-900">Rp <?= number_format($total_honor, 0, ',', '.') ?></td>
                                    <td class="px-3 py-3.5 text-right text-amber-950 text-base bg-amber-100">Rp <?= number_format($total_payroll, 0, ',', '.') ?></td>
                                    <td class="no-print"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL SLIP GAJI INTERAKTIF -->
    <!-- ============================================================ -->
    <div id="slipModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white w-full max-w-3xl max-h-[92vh] rounded-3xl shadow-2xl flex flex-col overflow-hidden border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between no-print">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-amber-500 text-amber-950 flex items-center justify-center font-bold">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm text-slate-800">Preview Slip Gaji & Evaluasi Transparan</h3>
                        <p class="text-[10px] text-slate-400">Yayasan Villa Qur'an Baron Malang</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="printSingleSlip()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-amber-950 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5">
                        <i class="fas fa-print"></i>
                        <span>Cetak Slip Ini</span>
                    </button>
                    <button onclick="closeSlipModal()" class="w-8 h-8 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center transition">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body (Printable Area) -->
            <div id="modalSlipContent" class="flex-1 overflow-y-auto p-6 sm:p-8 space-y-5 text-slate-800">
                <!-- Diisi via JavaScript berdasarkan baris data pegawai terpilih -->
            </div>
        </div>
    </div>

    <!-- PRINT CONTAINER KHUSUS SATU SLIP -->
    <div id="slipPrintContainer" class="hidden"></div>

    <!-- DATA JSON UNTUK JAVASCRIPT MODAL -->
    <script>
        const payrollData = <?= json_encode($payroll_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const namaBulan = "<?= $months[$selected_month] ?>";
        const tahunPilih = "<?= $selected_year ?>";

        function openSlipModal(index) {
            const p = payrollData[index];
            if (!p) return;

            const formatRupiah = (num) => 'Rp ' + Number(num).toLocaleString('id-ID');

            let kpiRowsHtml = '';
            if (p.kpi_items && p.kpi_items.length > 0) {
                p.kpi_items.forEach(item => {
                    kpiRowsHtml += `
                        <tr>
                            <td class="px-3 py-2 font-bold text-slate-800">${item.kategori}</td>
                            <td class="px-3 py-2 text-slate-600">${item.detail}</td>
                            <td class="px-3 py-2 text-center text-slate-500 font-semibold">${item.bobot}</td>
                            <td class="px-3 py-2 text-right font-black text-slate-900">${item.skor}</td>
                        </tr>
                    `;
                });
            }

            let tunjanganRowsHtml = '';
            if (p.rincian_tunjangan && p.rincian_tunjangan.length > 0) {
                p.rincian_tunjangan.forEach(t => {
                    tunjanganRowsHtml += `
                        <tr>
                            <td class="px-3 py-2">
                                <span class="font-bold text-slate-800 block">${t.nama}</span>
                                <span class="text-[10px] text-emerald-600 font-semibold block">${t.keterangan}</span>
                            </td>
                            <td class="px-3 py-2 text-right font-black text-slate-800">${formatRupiah(t.nominal)}</td>
                        </tr>
                    `;
                });
            } else {
                tunjanganRowsHtml = `
                    <tr>
                        <td class="px-3 py-2 text-slate-400 italic">Tunjangan Jabatan / Kinerja</td>
                        <td class="px-3 py-2 text-right font-semibold text-slate-400">Rp 0</td>
                    </tr>
                `;
            }

            let potonganRowsHtml = '';
            if (p.rincian_potongan && p.rincian_potongan.length > 0) {
                p.rincian_potongan.forEach(pt => {
                    potonganRowsHtml += `
                        <tr>
                            <td class="px-3 py-2">
                                <span class="font-bold text-rose-700 block">${pt.nama}</span>
                                <span class="text-[10px] text-slate-500 block">${pt.keterangan}</span>
                            </td>
                            <td class="px-3 py-2 text-right font-black text-rose-600">- ${formatRupiah(pt.nominal)}</td>
                        </tr>
                    `;
                });
            } else {
                potonganRowsHtml = `
                    <tr>
                        <td class="px-3 py-3 text-center text-slate-400 italic" colspan="2">
                            <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Tidak ada potongan denda bulan ini.
                        </td>
                    </tr>
                `;
            }

            const slipHtml = `
                <!-- KOP SLIP RESMI -->
                <div class="border-b-2 border-slate-900 pb-4 text-center">
                    <div class="flex items-center justify-center gap-3 mb-1">
                        <div class="w-10 h-10 rounded-xl bg-amber-600 text-white flex items-center justify-center text-xl font-black">
                            <i class="fas fa-quran"></i>
                        </div>
                        <div class="text-left">
                            <h2 class="text-lg font-black text-slate-900 uppercase tracking-tight">YAYASAN VILLA QUR'AN BARON MALANG</h2>
                            <p class="text-[10px] text-slate-500 font-medium">Islamic Boarding School & Entrepreneur Institute • Malang, Jawa Timur</p>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-t border-slate-200 flex items-center justify-between text-xs font-bold">
                        <span class="text-amber-900 uppercase tracking-wider">SLIP GAJI & EVALUASI PRESTASI KERJA (PAYROLL SLIP)</span>
                        <span class="text-slate-500">Periode: ${namaBulan} ${tahunPilih}</span>
                    </div>
                </div>

                <!-- IDENTITAS PEGAWAI -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Nama Pegawai</span>
                        <span class="font-black text-slate-900 block">${p.nama}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Pegawai</span>
                        <span class="font-bold text-slate-800 block">${p.status_pegawai}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Jabatan / Peran</span>
                        <span class="font-bold text-slate-800 block">${p.role_str || 'Pegawai'}</span>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Siklus Kerja</span>
                        <span class="font-bold text-slate-800 block">${p.start_date} s/d ${p.end_date}</span>
                    </div>
                </div>

                <!-- PRESENSI & KPI METRICS -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between border-b border-slate-200 pb-1.5">
                        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-clipboard-check text-amber-600"></i>
                            A. Presensi & Evaluasi Prestasi Kerja (KPI)
                        </h4>
                        <span class="text-[11px] font-extrabold px-2.5 py-0.5 rounded-full border ${p.grade_badge}">
                            Total Skor: ${Number(p.total_skor_kpi).toFixed(1)} (Grade ${p.grade})
                        </span>
                    </div>

                    <!-- Presensi Counter -->
                    <div class="grid grid-cols-4 gap-2 text-center text-xs">
                        <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-200">
                            <span class="text-[9px] font-bold text-emerald-800 block">Total Hadir</span>
                            <span class="text-sm font-black text-emerald-700">${p.hadir_count} Hari</span>
                        </div>
                        <div class="p-2 rounded-xl bg-blue-50 border border-blue-200">
                            <span class="text-[9px] font-bold text-blue-800 block">Izin Resmi</span>
                            <span class="text-sm font-black text-blue-700">${p.izin_count} Hari</span>
                        </div>
                        <div class="p-2 rounded-xl bg-purple-50 border border-purple-200">
                            <span class="text-[9px] font-bold text-purple-800 block">Sakit (SKD)</span>
                            <span class="text-sm font-black text-purple-700">${p.sakit_count} Hari</span>
                        </div>
                        <div class="p-2 rounded-xl ${p.alpa_count > 0 ? 'bg-rose-50 border border-rose-300' : 'bg-slate-50 border border-slate-200'}">
                            <span class="text-[9px] font-bold ${p.alpa_count > 0 ? 'text-rose-800' : 'text-slate-500'} block">Alfa / Tanpa Izin</span>
                            <span class="text-sm font-black ${p.alpa_count > 0 ? 'text-rose-600' : 'text-slate-400'}">${p.alpa_count} Hari</span>
                        </div>
                    </div>

                    <!-- KPI Breakdown Table -->
                    <div class="overflow-x-auto rounded-xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead class="bg-slate-100 text-[10px] uppercase font-bold text-slate-600 tracking-wider">
                                <tr>
                                    <th class="px-3 py-1.5 text-left">Indikator Tugas</th>
                                    <th class="px-3 py-1.5 text-left">Realisasi</th>
                                    <th class="px-3 py-1.5 text-center w-20">Bobot</th>
                                    <th class="px-3 py-1.5 text-right w-20">Skor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                ${kpiRowsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RINCIAN PENERIMAAN & POTONGAN -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <!-- Penerimaan -->
                    <div class="space-y-2">
                        <h4 class="font-black text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1 flex items-center gap-1.5">
                            <i class="fas fa-plus-circle text-emerald-600"></i> B. Penerimaan (Earnings)
                        </h4>
                        <table class="min-w-full border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
                            <tbody class="bg-white">
                                <tr>
                                    <td class="px-3 py-2">
                                        <span class="font-bold text-slate-800 block">Gaji Pokok</span>
                                        <span class="text-[10px] text-slate-400">${p.status_pegawai}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-black text-slate-800">${formatRupiah(p.gaji_pokok)}</td>
                                </tr>
                                ${tunjanganRowsHtml}
                                <tr>
                                    <td class="px-3 py-2">
                                        <span class="font-bold text-slate-800 block">Honor KBM</span>
                                        <span class="text-[10px] text-slate-400">${p.honor_note}</span>
                                    </td>
                                    <td class="px-3 py-2 text-right font-black text-slate-800">${formatRupiah(p.honor)}</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-emerald-50 font-black">
                                <tr>
                                    <td class="px-3 py-2 text-slate-700">Subtotal Penerimaan:</td>
                                    <td class="px-3 py-2 text-right text-emerald-700">${formatRupiah(p.total_penerimaan)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Potongan -->
                    <div class="space-y-2">
                        <h4 class="font-black text-slate-900 uppercase tracking-wider border-b border-slate-200 pb-1 flex items-center gap-1.5">
                            <i class="fas fa-minus-circle text-rose-600"></i> C. Potongan (Deduction)
                        </h4>
                        <table class="min-w-full border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
                            <tbody class="bg-white">
                                ${potonganRowsHtml}
                            </tbody>
                            <tfoot class="bg-rose-50 font-black">
                                <tr>
                                    <td class="px-3 py-2 text-slate-700">Total Potongan:</td>
                                    <td class="px-3 py-2 text-right text-rose-700">- ${formatRupiah(p.total_potongan)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- TOTAL TAKE HOME PAY -->
                <div class="p-4 rounded-2xl bg-amber-50 border-2 border-amber-300 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-black text-amber-900 uppercase tracking-wider block">TOTAL GAJI BERSIH (TAKE HOME PAY)</span>
                        <span class="text-[11px] font-bold text-slate-600 block italic">"${p.terbilang}"</span>
                    </div>
                    <span class="text-xl sm:text-2xl font-black text-amber-900">${formatRupiah(p.total_thp)}</span>
                </div>

                <!-- TANDA TANGAN -->
                <div class="pt-4 border-t border-slate-300 grid grid-cols-2 text-center text-xs text-slate-700">
                    <div>
                        <p class="font-medium">Mengetahui,</p>
                        <p class="font-bold text-slate-900 mt-0.5">Bendahara Yayasan</p>
                        <div class="h-16 flex items-end justify-center">
                            <p class="border-b border-slate-400 pb-0.5 px-6 font-bold">( ........................................ )</p>
                        </div>
                    </div>
                    <div>
                        <p class="font-medium">Malang, <?= date('d M Y') ?></p>
                        <p class="font-bold text-slate-900 mt-0.5">Penerima (Pegawai)</p>
                        <div class="h-16 flex items-end justify-center">
                            <p class="border-b border-slate-400 pb-0.5 px-6 font-bold">( ${p.nama} )</p>
                        </div>
                    </div>
                </div>
            `;

            document.getElementById('modalSlipContent').innerHTML = slipHtml;
            document.getElementById('slipPrintContainer').innerHTML = slipHtml;
            document.getElementById('slipModal').classList.remove('hidden');
        }

        function closeSlipModal() {
            document.getElementById('slipModal').classList.add('hidden');
        }

        function printSingleSlip() {
            document.body.classList.add('printing-slip');
            window.print();
            setTimeout(() => {
                document.body.classList.remove('printing-slip');
            }, 500);
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSlipModal();
        });

        const openBtn = document.getElementById('open-sidebar-yayasan2');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                const sb = document.getElementById('sidebar-yayasan2');
                const ov = document.getElementById('sidebar-overlay-yayasan2');
                if (sb) sb.classList.toggle('hidden');
                if (ov) ov.classList.toggle('hidden');
            });
        }
        const ovBtn = document.getElementById('sidebar-overlay-yayasan2');
        if (ovBtn) {
            ovBtn.addEventListener('click', () => {
                const sb = document.getElementById('sidebar-yayasan2');
                const ov = document.getElementById('sidebar-overlay-yayasan2');
                if (sb) sb.classList.add('hidden');
                if (ov) ov.classList.add('hidden');
            });
        }
    </script>
</body>
</html>
