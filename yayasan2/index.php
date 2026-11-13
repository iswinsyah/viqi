<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'dashboard_yayasan';

// 1. Total Asatidz / Pegawai
$total_asatidz = 0;
$q_asatidz = $conn->query("SELECT COUNT(id) AS tot FROM akun_ustadz");
if ($q_asatidz) {
    $total_asatidz = (int)($q_asatidz->fetch_assoc()['tot'] ?? 0);
}

// 2. Total Santri Aktif
$total_santri = 0;
$q_santri = $conn->query("SELECT COUNT(id) AS tot FROM buku_induk_santri WHERE status_santri = 'Aktif'");
if ($q_santri) {
    $total_santri = (int)($q_santri->fetch_assoc()['tot'] ?? 0);
} else {
    $q_santri_alt = $conn->query("SELECT COUNT(id) AS tot FROM buku_induk_santri");
    if ($q_santri_alt) {
        $total_santri = (int)($q_santri_alt->fetch_assoc()['tot'] ?? 0);
    }
}

// 3. Pendaftar PSB / SPMB Online
$total_spmb = 0;
$q_spmb = $conn->query("SELECT COUNT(id) AS tot FROM pendaftar_spmb");
if ($q_spmb) {
    $total_spmb = (int)($q_spmb->fetch_assoc()['tot'] ?? 0);
}

// 4. Saldo Kas Lembaga
$saldo_kas = 0;
$q_kas = $conn->query("SELECT (SUM(CASE WHEN jenis = 'masuk' THEN nominal ELSE 0 END) - SUM(CASE WHEN jenis = 'keluar' THEN nominal ELSE 0 END)) AS saldo FROM arus_kas");
if ($q_kas) {
    $row_kas = $q_kas->fetch_assoc();
    $saldo_kas = (float)($row_kas['saldo'] ?? 0);
}

// Definisi Semua Card Menu Ruang Yayasan (Dikelompokkan secara rapi)
$categories = [
    'all' => 'Semua Modul',
    'keuangan' => 'Keuangan & Payroll',
    'sdm' => 'SDM & Pegawai',
    'akademik' => 'Akademik & Kurikulum AI',
    'santri' => 'Santri & Asrama',
    'kelembagaan' => 'Kelembagaan & Sistem'
];

$yayasan_cards = [
    // --- KEUANGAN & PAYROLL ---
    [
        'category' => 'keuangan',
        'title' => 'Pembukuan Kas',
        'subtitle' => 'Pencatatan kas masuk, keluar, dan neraca keuangan lembaga.',
        'url' => 'pembukuan.php',
        'icon' => 'fas fa-calculator',
        'bg_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'icon_bg' => 'bg-emerald-600 text-white',
        'badge' => 'Kas & Buku'
    ],
    [
        'category' => 'keuangan',
        'title' => 'Cashflow & Proyeksi',
        'subtitle' => 'Perencanaan arus kas, target penerimaan dan proyeksi anggaran.',
        'url' => 'pembukuan.php?tab=proyeksi',
        'icon' => 'fas fa-funnel-dollar',
        'bg_color' => 'bg-teal-50 text-teal-700 border-teal-200',
        'icon_bg' => 'bg-teal-600 text-white',
        'badge' => 'Arus Kas'
    ],
    [
        'category' => 'keuangan',
        'title' => 'Rekap Gaji (Payroll)',
        'subtitle' => 'Slip gaji bulanan, transfer honor, dan rekap penggajian asatidz/pegawai.',
        'url' => 'gaji-pegawai.php',
        'icon' => 'fas fa-coins',
        'bg_color' => 'bg-amber-50 text-amber-700 border-amber-200',
        'icon_bg' => 'bg-amber-500 text-white',
        'badge' => 'Payroll'
    ],
    [
        'category' => 'keuangan',
        'title' => 'Pengaturan Tarif Gaji',
        'subtitle' => 'Standarisasi tarif honor per jam mengajar, transport, dan lembur.',
        'url' => 'gaji-asatidz.php',
        'icon' => 'fas fa-sliders',
        'bg_color' => 'bg-orange-50 text-orange-700 border-orange-200',
        'icon_bg' => 'bg-orange-500 text-white',
        'badge' => 'Tarif Honor'
    ],
    [
        'category' => 'keuangan',
        'title' => 'Pengaturan Tunjangan',
        'subtitle' => 'Manajemen tunjangan jabatan struktural, pengabdian, dan lembur.',
        'url' => 'tunjangan.php',
        'icon' => 'fas fa-award',
        'bg_color' => 'bg-yellow-50 text-yellow-800 border-yellow-200',
        'icon_bg' => 'bg-yellow-500 text-white',
        'badge' => 'Tunjangan'
    ],
    [
        'category' => 'keuangan',
        'title' => 'Rekap Pembayaran SPP',
        'subtitle' => 'Monitoring pembayaran SPP, status lunas, dan tagihan wali santri.',
        'url' => 'rekap-spp.php',
        'icon' => 'fas fa-file-invoice-dollar',
        'bg_color' => 'bg-green-50 text-green-700 border-green-200',
        'icon_bg' => 'bg-green-600 text-white',
        'badge' => 'SPP Santri'
    ],
    [
        'category' => 'keuangan',
        'title' => 'Rekap Uang Saku',
        'subtitle' => 'Mutasi transaksi uang saku santri, top-up saldo, dan pencairan kasir.',
        'url' => 'rekap-uang-saku.php',
        'icon' => 'fas fa-wallet',
        'bg_color' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'icon_bg' => 'bg-cyan-600 text-white',
        'badge' => 'Tabungan'
    ],

    // --- SDM & PEGAWAI ---
    [
        'category' => 'sdm',
        'title' => 'Pegawai & Asatidz',
        'subtitle' => 'Data personil guru, riwayat kepegawaian, dan otoritas akun login.',
        'url' => 'asatidz.php',
        'icon' => 'fas fa-users-gear',
        'bg_color' => 'bg-blue-50 text-blue-700 border-blue-200',
        'icon_bg' => 'bg-blue-600 text-white',
        'badge' => 'Data Asatidz'
    ],
    [
        'category' => 'sdm',
        'title' => 'AI Agent HRD',
        'subtitle' => 'Analisis kompetensi personil & asisten digital HRD bertenaga AI.',
        'url' => 'ai-agent-hrd.php',
        'icon' => 'fas fa-robot',
        'bg_color' => 'bg-purple-50 text-purple-700 border-purple-200',
        'icon_bg' => 'bg-purple-600 text-white',
        'badge' => 'AI HRD'
    ],
    [
        'category' => 'sdm',
        'title' => 'Monitoring KPI Pegawai',
        'subtitle' => 'Evaluasi capaian Key Performance Indicators staf & guru bulanan.',
        'url' => 'kpi.php',
        'icon' => 'fas fa-chart-bar',
        'bg_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'icon_bg' => 'bg-indigo-600 text-white',
        'badge' => 'KPI Pegawai'
    ],
    [
        'category' => 'sdm',
        'title' => 'KPI Kepala Sekolah',
        'subtitle' => 'Monitoring program kerja dan indikator capaian pimpinan unit.',
        'url' => 'kpi-kepala-sekolah.php',
        'icon' => 'fas fa-chart-pie',
        'bg_color' => 'bg-violet-50 text-violet-700 border-violet-200',
        'icon_bg' => 'bg-violet-600 text-white',
        'badge' => 'KPI Kepsek'
    ],
    [
        'category' => 'sdm',
        'title' => 'KPI Musyrif Asrama',
        'subtitle' => 'Penilaian pembinaan ketertiban, adab, dan kedisiplinan santri.',
        'url' => 'kpi-musyrif.php',
        'icon' => 'fas fa-chart-line',
        'bg_color' => 'bg-sky-50 text-sky-700 border-sky-200',
        'icon_bg' => 'bg-sky-600 text-white',
        'badge' => 'KPI Musyrif'
    ],
    [
        'category' => 'sdm',
        'title' => 'Supervisi Mengajar',
        'subtitle' => 'Instrumen penilaian kualitas KBM dan pedagogik guru di kelas.',
        'url' => '../admin-supervisi-mengajar.php',
        'icon' => 'fas fa-clipboard-check',
        'bg_color' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'icon_bg' => 'bg-emerald-700 text-white',
        'badge' => 'Supervisi'
    ],
    [
        'category' => 'sdm',
        'title' => 'Rekap KBM Bulanan',
        'subtitle' => 'Verifikasi kehadiran tatap muka guru dan rekapitulasi KBM bulanan.',
        'url' => 'rekap-kbm-yayasan.php',
        'icon' => 'fas fa-calendar-check',
        'bg_color' => 'bg-rose-50 text-rose-700 border-rose-200',
        'icon_bg' => 'bg-rose-600 text-white',
        'badge' => 'KBM Bulanan'
    ],

    // --- AKADEMIK & KURIKULUM AI ---
    [
        'category' => 'akademik',
        'title' => 'Kurikulum & AI E-Learning',
        'subtitle' => 'Generator modul ajar, materi, dan e-learning berbasis AI terintegrasi.',
        'url' => 'elearning-yayasan.php',
        'icon' => 'fas fa-brain',
        'bg_color' => 'bg-purple-50 text-purple-700 border-purple-200',
        'icon_bg' => 'bg-purple-600 text-white',
        'badge' => 'E-Learning AI'
    ],
    [
        'category' => 'akademik',
        'title' => 'Capaian Pembelajaran (CP AI)',
        'subtitle' => 'Pemetaan target capaian kompetensi dan asesmen materi via AI.',
        'url' => 'kurikulum-cp.php',
        'icon' => 'fas fa-dna',
        'bg_color' => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
        'icon_bg' => 'bg-fuchsia-600 text-white',
        'badge' => 'CP Kurikulum'
    ],
    [
        'category' => 'akademik',
        'title' => 'Inkubator Solopreneur',
        'subtitle' => 'Kurikulum kemandirian wirausaha dan bisnis digital santri.',
        'url' => 'kurikulum-solopreneur.php',
        'icon' => 'fas fa-rocket',
        'bg_color' => 'bg-rose-50 text-rose-700 border-rose-200',
        'icon_bg' => 'bg-rose-500 text-white',
        'badge' => 'Solopreneur'
    ],
    [
        'category' => 'akademik',
        'title' => 'Master Kitab Rujukan',
        'subtitle' => 'Database kitab rujukan dan silabus kepesantrenan.',
        'url' => 'kitab-rujukan.php',
        'icon' => 'fas fa-book-open',
        'bg_color' => 'bg-amber-50 text-amber-700 border-amber-200',
        'icon_bg' => 'bg-amber-600 text-white',
        'badge' => 'Kitab Rujukan'
    ],
    [
        'category' => 'akademik',
        'title' => 'Master Data Kelas',
        'subtitle' => 'Pengaturan rombel, tingkatan jenjang kelas, dan wali kelas.',
        'url' => 'master-kelas.php',
        'icon' => 'fas fa-school',
        'bg_color' => 'bg-blue-50 text-blue-700 border-blue-200',
        'icon_bg' => 'bg-blue-600 text-white',
        'badge' => 'Rombel Kelas'
    ],
    [
        'category' => 'akademik',
        'title' => 'Master Mata Pelajaran',
        'subtitle' => 'Katalog mapel dinas, keagamaan, dan muatan lokal pesantren.',
        'url' => 'master-mapel.php',
        'icon' => 'fas fa-book',
        'bg_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'icon_bg' => 'bg-indigo-600 text-white',
        'badge' => 'Mata Pelajaran'
    ],
    [
        'category' => 'akademik',
        'title' => 'Master Silabus',
        'subtitle' => 'Rencana pembelajaran terstruktur dan panduan pengajaran guru.',
        'url' => 'master-silabus.php',
        'icon' => 'fas fa-list-check',
        'bg_color' => 'bg-teal-50 text-teal-700 border-teal-200',
        'icon_bg' => 'bg-teal-600 text-white',
        'badge' => 'Silabus KBM'
    ],
    [
        'category' => 'akademik',
        'title' => 'Master Kalender Akademik',
        'subtitle' => 'Jadwal agenda pendidikan, kalender libur, dan ujian yayasan.',
        'url' => 'master-kalender.php',
        'icon' => 'fas fa-calendar-days',
        'bg_color' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'icon_bg' => 'bg-cyan-600 text-white',
        'badge' => 'Kalender'
    ],

    // --- SANTRI & ASRAMA ---
    [
        'category' => 'santri',
        'title' => 'Database PSB Online',
        'subtitle' => 'Monitoring data pendaftar santri baru, verifikasi dan ujian seleksi.',
        'url' => '../admin-spmb.php',
        'icon' => 'fas fa-user-graduate',
        'bg_color' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
        'icon_bg' => 'bg-emerald-600 text-white',
        'badge' => 'PSB / SPMB'
    ],
    [
        'category' => 'santri',
        'title' => 'Laporan Setoran Hafalan',
        'subtitle' => 'Progres hafalan Al-Qur\'an santri, target ziyadah, dan mutqin.',
        'url' => 'laporan-setoran-hafalan.php',
        'icon' => 'fas fa-book-quran',
        'bg_color' => 'bg-teal-50 text-teal-700 border-teal-200',
        'icon_bg' => 'bg-teal-700 text-white',
        'badge' => 'Tahfidz Qur\'an'
    ],
    [
        'category' => 'santri',
        'title' => 'Rekap Ibadah Harian',
        'subtitle' => 'Mutaba\'ah ibadah sholat 5 waktu, tahajud, shaum, dan wirid santri.',
        'url' => 'ibadah-harian-santri.php',
        'icon' => 'fas fa-mosque',
        'bg_color' => 'bg-green-50 text-green-700 border-green-200',
        'icon_bg' => 'bg-green-600 text-white',
        'badge' => 'Mutaba\'ah'
    ],
    [
        'category' => 'santri',
        'title' => 'Monitoring Raport PKBM',
        'subtitle' => 'Leger nilai kumulatif, evaluasi pembelajaran, dan cetak raport digital.',
        'url' => 'rapot-pkbm.php',
        'icon' => 'fas fa-file-invoice',
        'bg_color' => 'bg-blue-50 text-blue-700 border-blue-200',
        'icon_bg' => 'bg-blue-600 text-white',
        'badge' => 'Raport Santri'
    ],

    // --- KELEMBAGAAN & SISTEM ---
    [
        'category' => 'kelembagaan',
        'title' => 'Manajemen Menu & Akses',
        'subtitle' => 'Pengaturan struktur menu, visibilitas modul & hak akses per level akun.',
        'url' => 'manajemen-menu.php',
        'icon' => 'fas fa-sliders',
        'bg_color' => 'bg-slate-50 text-slate-700 border-slate-200',
        'icon_bg' => 'bg-slate-700 text-white',
        'badge' => 'Hak Akses'
    ],
    [
        'category' => 'kelembagaan',
        'title' => 'Struktur Organisasi',
        'subtitle' => 'Bagan organisasi kepengurusan yayasan dan rantai komando.',
        'url' => 'struktur-jobdesc.php',
        'icon' => 'fas fa-sitemap',
        'bg_color' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'icon_bg' => 'bg-indigo-600 text-white',
        'badge' => 'Bagan Yayasan'
    ],
    [
        'category' => 'kelembagaan',
        'title' => 'Job Description Pegawai',
        'subtitle' => 'Uraian tugas pokok dan wewenang tiap posisi struktural.',
        'url' => 'jobdesc.php',
        'icon' => 'fas fa-id-card',
        'bg_color' => 'bg-sky-50 text-sky-700 border-sky-200',
        'icon_bg' => 'bg-sky-600 text-white',
        'badge' => 'Tupoksi Kerja'
    ],
    [
        'category' => 'kelembagaan',
        'title' => 'SOP & Peraturan Yayasan',
        'subtitle' => 'Regulasi resmi lembaga, tata tertib, dan SOP kedinasan.',
        'url' => 'admin-peraturan.php',
        'icon' => 'fas fa-gavel',
        'bg_color' => 'bg-amber-50 text-amber-700 border-amber-200',
        'icon_bg' => 'bg-amber-600 text-white',
        'badge' => 'SOP & Tata Tertib'
    ],
    [
        'category' => 'kelembagaan',
        'title' => 'Analisis SWOT & Strategi',
        'subtitle' => 'Analisis kekuatan, peluang, strategi pertumbuhan, dan evaluasi ancaman.',
        'url' => 'analisis-swot.php',
        'icon' => 'fas fa-chart-line',
        'bg_color' => 'bg-violet-50 text-violet-700 border-violet-200',
        'icon_bg' => 'bg-violet-600 text-white',
        'badge' => 'Analisis SWOT'
    ],
    [
        'category' => 'kelembagaan',
        'title' => 'Data Center & Cadangan',
        'subtitle' => 'Pusat cadangan data terpadu, status backup, dan kesehatan sistem.',
        'url' => 'data-center.php',
        'icon' => 'fas fa-database',
        'bg_color' => 'bg-slate-50 text-slate-700 border-slate-200',
        'icon_bg' => 'bg-slate-800 text-white',
        'badge' => 'Data Center'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ruang Yayasan | SADIGS Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .card-menu {
            transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-menu:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -3px rgba(11, 132, 120, 0.15), 0 4px 6px -2px rgba(11, 132, 120, 0.05);
        }
        .card-menu:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- DESKTOP SIDEBAR (TAMPILAN PC) & MOBILE DRAWER -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN APP CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP BAR -->
        <header class="h-16 bg-white border-b border-slate-200/80 shadow-xs flex items-center justify-between px-4 md:px-8 z-20 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-yayasan2" class="text-slate-600 hover:text-[#0b8478] md:hidden p-2 rounded-xl hover:bg-slate-100 transition focus:outline-none" title="Buka Menu Ruangan">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h2 class="font-extrabold text-slate-900 text-base md:text-lg tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#0b8478] animate-pulse"></span>
                        Ruang Yayasan
                    </h2>
                    <p class="text-[11px] text-slate-500 hidden sm:block">Panel Eksekutif & Pengendalian Sistem Terpusat</p>
                </div>
            </div>

            <!-- ACTION CONTROLS -->
            <div class="flex items-center space-x-3">
                <a href="https://villaquranindonesia.com" target="_blank" class="text-xs text-[#0b8478] hover:text-teal-800 font-bold bg-teal-50 hover:bg-teal-100/70 border border-teal-200/60 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-globe"></i>
                    <span class="hidden sm:inline">Website Utama</span>
                </a>
                <div class="h-9 w-9 rounded-full bg-[#0b8478] text-white flex items-center justify-center font-black text-xs shadow-md border-2 border-white">
                    <?= strtoupper(substr($y_nama, 0, 2)) ?>
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE DASHBOARD (PB-24 FOR MOBILE BOTTOM BAR CLEARANCE) -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/70 px-4 py-5 md:p-8 pb-24 md:pb-12">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- WELCOME BANNER -->
                <div class="bg-gradient-to-r from-[#0b8478] via-teal-700 to-emerald-700 rounded-2xl p-6 md:p-8 text-white shadow-lg relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
                        <i class="fas fa-mosque text-9xl"></i>
                    </div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-white/20 text-teal-100 backdrop-blur-xs mb-2 border border-white/20">
                                <i class="fas fa-crown text-amber-300"></i> Ruang Eksekutif Yayasan
                            </span>
                            <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white">
                                Selamat Datang, <?= htmlspecialchars($y_nama) ?>
                            </h1>
                            <p class="text-teal-100 text-xs md:text-sm mt-1 max-w-2xl leading-relaxed">
                                Seluruh modul manajemen keuangan, operasional kepegawaian, kurikulum AI, dan kesantrian tersedia lengkap di menu Home ini.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="px-3.5 py-2 rounded-xl bg-black/20 backdrop-blur-xs border border-white/10 text-xs font-semibold text-teal-100 flex items-center gap-2">
                                <i class="fas fa-calendar-day text-amber-300"></i> <?= date('d M Y') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- STATISTIK RINGKAS LEMBAGA -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5">
                    <!-- Total Pegawai / Asatidz -->
                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Pegawai & Guru</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight"><?= number_format($total_asatidz, 0, ',', '.') ?></h3>
                        </div>
                    </div>

                    <!-- Total Santri Aktif -->
                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Santri Aktif</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight"><?= number_format($total_santri, 0, ',', '.') ?></h3>
                        </div>
                    </div>

                    <!-- Pendaftar SPMB Baru -->
                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Pendaftar PSB</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight"><?= number_format($total_spmb, 0, ',', '.') ?></h3>
                        </div>
                    </div>

                    <!-- Saldo Kas Lembaga -->
                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Saldo Kas</p>
                            <h3 class="text-lg md:text-xl font-black text-slate-800 tracking-tight truncate">Rp <?= number_format($saldo_kas, 0, ',', '.') ?></h3>
                        </div>
                    </div>
                </div>

                <!-- FILTER & PENCARIAN CEPAT CARD MENU -->
                <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs space-y-4">
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" id="menuSearchInput" placeholder="Cari card menu... (contoh: kas, spp, gaji, kpi, santri, raport)" 
                                   class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-slate-200 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-[#0b8478] focus:border-transparent transition bg-slate-50/50">
                        </div>
                        <div class="text-[11px] font-semibold text-slate-400 flex items-center gap-1.5 justify-end">
                            <span id="menuCount"><?= count($yayasan_cards) ?></span> Modul Tersedia
                        </div>
                    </div>

                    <!-- CATEGORY PILL TABS -->
                    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1 text-xs font-bold">
                        <?php foreach ($categories as $catKey => $catLabel): ?>
                            <button type="button" 
                                    class="category-tab px-3.5 py-1.5 rounded-xl transition-all whitespace-nowrap border flex-shrink-0 <?= ($catKey === 'all') ? 'bg-[#0b8478] text-white border-[#0b8478] shadow-xs' : 'bg-slate-100/80 text-slate-600 border-slate-200/60 hover:bg-slate-200/70' ?>" 
                                    data-category="<?= $catKey ?>">
                                <?= $catLabel ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- GRID SEMUA CARD MENU YAYASAN -->
                <div id="cardGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($yayasan_cards as $card): ?>
                        <a href="<?= htmlspecialchars($card['url']) ?>" 
                           class="card-menu group bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between relative overflow-hidden"
                           data-title="<?= strtolower(htmlspecialchars($card['title'])) ?>"
                           data-subtitle="<?= strtolower(htmlspecialchars($card['subtitle'])) ?>"
                           data-badge="<?= strtolower(htmlspecialchars($card['badge'])) ?>"
                           data-category="<?= htmlspecialchars($card['category']) ?>">
                            
                            <div>
                                <!-- HEADER CARD: ICON & BADGE -->
                                <div class="flex items-start justify-between gap-3 mb-3.5">
                                    <div class="w-12 h-12 rounded-xl <?= $card['icon_bg'] ?> flex items-center justify-center text-lg shadow-sm group-hover:scale-108 transition-transform">
                                        <i class="<?= $card['icon'] ?>"></i>
                                    </div>
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase tracking-wide border <?= $card['bg_color'] ?>">
                                        <?= htmlspecialchars($card['badge']) ?>
                                    </span>
                                </div>

                                <!-- TITLE & SUBTITLE -->
                                <h3 class="font-extrabold text-sm md:text-base text-slate-800 group-hover:text-[#0b8478] transition-colors leading-snug">
                                    <?= htmlspecialchars($card['title']) ?>
                                </h3>
                                <p class="text-slate-500 text-xs mt-1.5 leading-relaxed line-clamp-2">
                                    <?= htmlspecialchars($card['subtitle']) ?>
                                </p>
                            </div>

                            <!-- CARD FOOTER ACTION LINK -->
                            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-slate-400 group-hover:text-[#0b8478] transition-colors">
                                <span>Buka Modul</span>
                                <i class="fas fa-arrow-right text-[11px] transform group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- EMPTY STATE SEARCH -->
                <div id="noResultsState" class="hidden text-center py-12 bg-white rounded-2xl border border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4 class="font-bold text-slate-700 text-sm">Tidak ada menu yang sesuai</h4>
                    <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci pencarian yang lain.</p>
                </div>

            </div>
        </main>
    </div>

    <!-- CLIENT INTERACTIVE SCRIPT -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('menuSearchInput');
        const cards = document.querySelectorAll('.card-menu');
        const categoryTabs = document.querySelectorAll('.category-tab');
        const noResults = document.getElementById('noResultsState');
        const menuCountSpan = document.getElementById('menuCount');

        let activeCategory = 'all';

        function filterCards() {
            const query = searchInput.value.toLowerCase().trim();
            let visibleCount = 0;

            cards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const subtitle = card.getAttribute('data-subtitle') || '';
                const badge = card.getAttribute('data-badge') || '';
                const category = card.getAttribute('data-category') || '';

                const matchesQuery = !query || title.includes(query) || subtitle.includes(query) || badge.includes(query);
                const matchesCategory = (activeCategory === 'all') || (category === activeCategory);

                if (matchesQuery && matchesCategory) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (menuCountSpan) menuCountSpan.textContent = visibleCount;
            if (noResults) {
                if (visibleCount === 0) {
                    noResults.classList.remove('hidden');
                } else {
                    noResults.classList.add('hidden');
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', filterCards);
        }

        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                categoryTabs.forEach(t => {
                    t.classList.remove('bg-[#0b8478]', 'text-white', 'border-[#0b8478]', 'shadow-xs');
                    t.classList.add('bg-slate-100/80', 'text-slate-600', 'border-slate-200/60');
                });
                this.classList.add('bg-[#0b8478]', 'text-white', 'border-[#0b8478]', 'shadow-xs');
                this.classList.remove('bg-slate-100/80', 'text-slate-600', 'border-slate-200/60');

                activeCategory = this.getAttribute('data-category') || 'all';
                filterCards();
            });
        });
    });
    </script>
</body>
</html>
