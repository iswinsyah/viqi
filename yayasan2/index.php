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

// 5 PILAR / KLASTER CARD MENU YAYASAN (Menyederhanakan Alur Berpikir)
$pilar_clusters = [
    'keuangan' => [
        'id' => 'pilar-keuangan',
        'title' => 'Sentra Keuangan & Kas',
        'subtitle' => 'Pengelolaan pembukuan kas, arus dana, rekap gaji, SPP santri, dan tunjangan.',
        'icon' => 'fas fa-wallet',
        'theme_color' => 'from-emerald-600 to-teal-700',
        'accent_border' => 'border-emerald-200',
        'badge' => 'Keuangan Lembaga',
        'badge_color' => 'bg-emerald-100 text-emerald-800',
        'items' => [
            ['title' => 'Pembukuan Kas', 'href' => 'pembukuan.php', 'icon' => 'fas fa-calculator', 'gradient' => 'from-emerald-600 to-teal-700', 'text_accent' => 'text-emerald-100'],
            ['title' => 'Cashflow Kas', 'href' => 'pembukuan.php?tab=proyeksi', 'icon' => 'fas fa-funnel-dollar', 'gradient' => 'from-teal-600 to-emerald-700', 'text_accent' => 'text-teal-100'],
            ['title' => 'Payroll Gaji', 'href' => 'gaji-pegawai.php', 'icon' => 'fas fa-coins', 'gradient' => 'from-amber-500 to-amber-700', 'text_accent' => 'text-amber-100'],
            ['title' => 'Tarif Gaji', 'href' => 'gaji-asatidz.php', 'icon' => 'fas fa-sliders', 'gradient' => 'from-orange-500 to-amber-600', 'text_accent' => 'text-orange-100'],
            ['title' => 'Tunjangan', 'href' => 'tunjangan.php', 'icon' => 'fas fa-award', 'gradient' => 'from-yellow-500 to-amber-600', 'text_accent' => 'text-yellow-100'],
            ['title' => 'Rekap SPP', 'href' => 'rekap-spp.php', 'icon' => 'fas fa-file-invoice-dollar', 'gradient' => 'from-green-600 to-emerald-700', 'text_accent' => 'text-green-100'],
            ['title' => 'Uang Saku', 'href' => 'rekap-uang-saku.php', 'icon' => 'fas fa-wallet', 'gradient' => 'from-cyan-600 to-teal-700', 'text_accent' => 'text-cyan-100']
        ]
    ],
    'sdm' => [
        'id' => 'pilar-sdm',
        'title' => 'Sentra SDM & Pegawai',
        'subtitle' => 'Data personil guru, monitoring KPI, supervisi kelas, dan asisten digital AI-HRD.',
        'icon' => 'fas fa-users-gear',
        'theme_color' => 'from-blue-600 to-indigo-700',
        'accent_border' => 'border-blue-200',
        'badge' => 'Personalia & Guru',
        'badge_color' => 'bg-blue-100 text-blue-800',
        'items' => [
            ['title' => 'Data Asatidz', 'href' => 'asatidz.php', 'icon' => 'fas fa-users-gear', 'gradient' => 'from-blue-600 to-indigo-700', 'text_accent' => 'text-blue-100'],
            ['title' => 'AI Agent HRD', 'href' => 'ai-agent-hrd.php', 'icon' => 'fas fa-robot', 'gradient' => 'from-purple-600 to-indigo-700', 'text_accent' => 'text-purple-100'],
            ['title' => 'KPI Pegawai', 'href' => 'kpi.php', 'icon' => 'fas fa-chart-bar', 'gradient' => 'from-indigo-600 to-blue-700', 'text_accent' => 'text-indigo-100'],
            ['title' => 'KPI Kepsek', 'href' => 'kpi-kepala-sekolah.php', 'icon' => 'fas fa-chart-pie', 'gradient' => 'from-violet-600 to-purple-700', 'text_accent' => 'text-violet-100'],
            ['title' => 'KPI Musyrif', 'href' => 'kpi-musyrif.php', 'icon' => 'fas fa-chart-line', 'gradient' => 'from-sky-500 to-blue-600', 'text_accent' => 'text-sky-100'],
            ['title' => 'Supervisi KBM', 'href' => '../admin-supervisi-mengajar.php', 'icon' => 'fas fa-clipboard-check', 'gradient' => 'from-emerald-600 to-teal-700', 'text_accent' => 'text-emerald-100'],
            ['title' => 'Rekap KBM', 'href' => 'rekap-kbm-yayasan.php', 'icon' => 'fas fa-calendar-check', 'gradient' => 'from-rose-500 to-red-600', 'text_accent' => 'text-rose-100']
        ]
    ],
    'akademik' => [
        'id' => 'pilar-akademik',
        'title' => 'Sentra Kurikulum & Inovasi AI',
        'subtitle' => 'E-learning AI, capaian pembelajaran (CP-AI), solopreneur, dan master data KBM.',
        'icon' => 'fas fa-brain',
        'theme_color' => 'from-purple-600 to-fuchsia-700',
        'accent_border' => 'border-purple-200',
        'badge' => 'Akademik & Kurikulum',
        'badge_color' => 'bg-purple-100 text-purple-800',
        'items' => [
            ['title' => 'E-Learning AI', 'href' => 'elearning-yayasan.php', 'icon' => 'fas fa-robot', 'gradient' => 'from-purple-600 to-fuchsia-700', 'text_accent' => 'text-purple-100'],
            ['title' => 'Capaian (CP-AI)', 'href' => 'kurikulum-cp.php', 'icon' => 'fas fa-brain', 'gradient' => 'from-fuchsia-600 to-purple-700', 'text_accent' => 'text-fuchsia-100'],
            ['title' => 'Solopreneur', 'href' => 'kurikulum-solopreneur.php', 'icon' => 'fas fa-rocket', 'gradient' => 'from-rose-500 to-amber-600', 'text_accent' => 'text-rose-100'],
            ['title' => 'Kitab Rujukan', 'href' => 'kitab-rujukan.php', 'icon' => 'fas fa-book-open', 'gradient' => 'from-amber-600 to-amber-800', 'text_accent' => 'text-amber-100'],
            ['title' => 'Master Kelas', 'href' => 'master-kelas.php', 'icon' => 'fas fa-school', 'gradient' => 'from-blue-500 to-indigo-600', 'text_accent' => 'text-blue-100'],
            ['title' => 'Master Mapel', 'href' => 'master-mapel.php', 'icon' => 'fas fa-book', 'gradient' => 'from-indigo-500 to-purple-600', 'text_accent' => 'text-indigo-100'],
            ['title' => 'Master Silabus', 'href' => 'master-silabus.php', 'icon' => 'fas fa-list-check', 'gradient' => 'from-teal-600 to-emerald-700', 'text_accent' => 'text-teal-100'],
            ['title' => 'Kalender Agenda', 'href' => 'master-kalender.php', 'icon' => 'fas fa-calendar-days', 'gradient' => 'from-cyan-600 to-blue-700', 'text_accent' => 'text-cyan-100']
        ]
    ],
    'santri' => [
        'id' => 'pilar-santri',
        'title' => 'Sentra Santri, Asrama & PSB',
        'subtitle' => 'Pendaftaran santri baru online, mutaba\'ah ibadah, setoran Al-Qur\'an, dan raport.',
        'icon' => 'fas fa-mosque',
        'theme_color' => 'from-teal-600 to-emerald-800',
        'accent_border' => 'border-teal-200',
        'badge' => 'Kesantrian & Asrama',
        'badge_color' => 'bg-teal-100 text-teal-800',
        'items' => [
            ['title' => 'Database PSB', 'href' => '../admin-spmb.php', 'icon' => 'fas fa-user-graduate', 'gradient' => 'from-emerald-600 to-teal-800', 'text_accent' => 'text-emerald-100'],
            ['title' => 'Setoran Tahfidz', 'href' => 'laporan-setoran-hafalan.php', 'icon' => 'fas fa-book-quran', 'gradient' => 'from-teal-600 to-emerald-700', 'text_accent' => 'text-teal-100'],
            ['title' => 'Ibadah Harian', 'href' => 'ibadah-harian-santri.php', 'icon' => 'fas fa-mosque', 'gradient' => 'from-green-600 to-emerald-700', 'text_accent' => 'text-green-100'],
            ['title' => 'Raport PKBM', 'href' => 'rapot-pkbm.php', 'icon' => 'fas fa-file-invoice', 'gradient' => 'from-blue-600 to-cyan-700', 'text_accent' => 'text-blue-100']
        ]
    ],
    'kelembagaan' => [
        'id' => 'pilar-kelembagaan',
        'title' => 'Sentra Tata Kelola & Sistem',
        'subtitle' => 'Struktur organisasi, uraian jobdesc, SOP peraturan yayasan, SWOT, dan data center.',
        'icon' => 'fas fa-landmark',
        'theme_color' => 'from-slate-700 to-slate-900',
        'accent_border' => 'border-slate-300',
        'badge' => 'Legalitas & Tata Kelola',
        'badge_color' => 'bg-slate-200 text-slate-800',
        'items' => [
            ['title' => 'Hak Akses Menu', 'href' => 'manajemen-menu.php', 'icon' => 'fas fa-sliders', 'gradient' => 'from-slate-600 to-slate-800', 'text_accent' => 'text-slate-100'],
            ['title' => 'Bagan Struktur', 'href' => 'struktur-jobdesc.php', 'icon' => 'fas fa-sitemap', 'gradient' => 'from-indigo-600 to-slate-700', 'text_accent' => 'text-indigo-100'],
            ['title' => 'Job Description', 'href' => 'jobdesc.php', 'icon' => 'fas fa-id-card', 'gradient' => 'from-sky-600 to-slate-700', 'text_accent' => 'text-sky-100'],
            ['title' => 'SOP & Regulasi', 'href' => 'admin-peraturan.php', 'icon' => 'fas fa-gavel', 'gradient' => 'from-amber-600 to-orange-700', 'text_accent' => 'text-amber-100'],
            ['title' => 'Analisis SWOT', 'href' => 'analisis-swot.php', 'icon' => 'fas fa-chart-line', 'gradient' => 'from-violet-600 to-purple-800', 'text_accent' => 'text-violet-100'],
            ['title' => 'Data Center', 'href' => 'data-center.php', 'icon' => 'fas fa-database', 'gradient' => 'from-slate-700 to-zinc-900', 'text_accent' => 'text-slate-100']
        ]
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
        .squircle-card {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .squircle-card:hover {
            transform: translateY(-4px) scale(1.03);
        }
        .squircle-card:active {
            transform: scale(0.95);
        }
        .pilar-card {
            transition: all 0.25s ease;
        }
        .pilar-card:hover {
            box-shadow: 0 12px 28px -6px rgba(11, 132, 120, 0.12), 0 4px 10px -2px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- DESKTOP SIDEBAR (TAMPILAN PC) & MOBILE DRAWER -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN APP WRAPPER -->
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

        <!-- MAIN SCROLLABLE DASHBOARD -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/70 px-4 py-5 md:p-8 pb-24 md:pb-12">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- WELCOME BANNER EKSEKUTIF -->
                <div class="bg-gradient-to-r from-[#0b8478] via-teal-700 to-emerald-700 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
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
                                Sistem Administrasi Digital (SADIGS) disederhanakan ke dalam 5 Sentra Utama untuk alur operasional yang lebih cepat, rapi, dan terarah.
                            </p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <span class="px-3.5 py-2 rounded-xl bg-black/20 backdrop-blur-xs border border-white/10 text-xs font-semibold text-teal-100 flex items-center gap-2">
                                <i class="fas fa-calendar-day text-amber-300"></i> <?= date('d M Y') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- METRIK STATISTIK CEPAT -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-5">
                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Pegawai & Guru</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight"><?= number_format($total_asatidz, 0, ',', '.') ?></h3>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Santri Aktif</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight"><?= number_format($total_santri, 0, ',', '.') ?></h3>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-4 md:p-5 border border-slate-200/80 shadow-xs flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Pendaftar PSB</p>
                            <h3 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight"><?= number_format($total_spmb, 0, ',', '.') ?></h3>
                        </div>
                    </div>

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

                <!-- ALUR BERPIKIR CEPAT: 5 KARTU SENTRA UTAMA (QUICK JUMP / FILTER) -->
                <div class="bg-white rounded-3xl p-5 md:p-6 border border-slate-200/80 shadow-xs">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="font-extrabold text-base md:text-lg text-slate-900 tracking-tight flex items-center gap-2">
                                <i class="fas fa-compass text-[#0b8478]"></i>
                                Alur Berpikir 5 Sentra Yayasan
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Pilih sentra bidang kerja di bawah untuk navigasi cepat:</p>
                        </div>
                        <!-- SEARCH BAR CEPAT -->
                        <div class="relative w-full md:w-72">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="pilarSearchInput" placeholder="Cari menu instan..." 
                                   class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:ring-2 focus:ring-[#0b8478] bg-slate-50/50">
                        </div>
                    </div>

                    <!-- 5 SHORTCUT CARD ALUR BERPIKIR -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5">
                        <a href="#pilar-keuangan" class="pilar-shortcut flex flex-col p-3 rounded-2xl bg-emerald-50/70 hover:bg-emerald-100/70 border border-emerald-200/70 transition group">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fas fa-wallet"></i>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-200 text-emerald-800">7 Menu</span>
                            </div>
                            <span class="font-extrabold text-xs text-slate-800 group-hover:text-emerald-800">1. Keuangan</span>
                            <span class="text-[10px] text-slate-500 truncate">Kas, Gaji & SPP</span>
                        </a>

                        <a href="#pilar-sdm" class="pilar-shortcut flex flex-col p-3 rounded-2xl bg-blue-50/70 hover:bg-blue-100/70 border border-blue-200/70 transition group">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fas fa-users-gear"></i>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-blue-200 text-blue-800">7 Menu</span>
                            </div>
                            <span class="font-extrabold text-xs text-slate-800 group-hover:text-blue-800">2. SDM Pegawai</span>
                            <span class="text-[10px] text-slate-500 truncate">Guru, KPI & HRD</span>
                        </a>

                        <a href="#pilar-akademik" class="pilar-shortcut flex flex-col p-3 rounded-2xl bg-purple-50/70 hover:bg-purple-100/70 border border-purple-200/70 transition group">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-purple-200 text-purple-800">8 Menu</span>
                            </div>
                            <span class="font-extrabold text-xs text-slate-800 group-hover:text-purple-800">3. Kurikulum AI</span>
                            <span class="text-[10px] text-slate-500 truncate">E-Learning & KBM</span>
                        </a>

                        <a href="#pilar-santri" class="pilar-shortcut flex flex-col p-3 rounded-2xl bg-teal-50/70 hover:bg-teal-100/70 border border-teal-200/70 transition group">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="w-8 h-8 rounded-xl bg-[#0b8478] text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fas fa-mosque"></i>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-teal-200 text-teal-800">4 Menu</span>
                            </div>
                            <span class="font-extrabold text-xs text-slate-800 group-hover:text-teal-800">4. Santri & Asrama</span>
                            <span class="text-[10px] text-slate-500 truncate">PSB, Tahfidz & Raport</span>
                        </a>

                        <a href="#pilar-kelembagaan" class="pilar-shortcut flex flex-col p-3 rounded-2xl bg-slate-100/80 hover:bg-slate-200/80 border border-slate-300/70 transition group col-span-2 sm:col-span-1">
                            <div class="flex items-center justify-between mb-1.5">
                                <div class="w-8 h-8 rounded-xl bg-slate-800 text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fas fa-landmark"></i>
                                </div>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-slate-200 text-slate-800">6 Menu</span>
                            </div>
                            <span class="font-extrabold text-xs text-slate-800 group-hover:text-slate-900">5. Tata Kelola</span>
                            <span class="text-[10px] text-slate-500 truncate">SOP, Struktur & Data</span>
                        </a>
                    </div>
                </div>

                <!-- DAFTAR CARD MENU BESAR BERDASARKAN 5 SENTRA (KLASTER) -->
                <div class="space-y-6">
                    <?php foreach ($pilar_clusters as $c_key => $cluster): ?>
                    <section id="<?= $cluster['id'] ?>" class="pilar-card bg-white rounded-3xl p-5 md:p-7 border <?= $cluster['accent_border'] ?> shadow-sm relative overflow-hidden">
                        
                        <!-- HEADER KARTU KLASTER -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-100">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br <?= $cluster['theme_color'] ?> text-white flex items-center justify-center text-xl shadow-md flex-shrink-0">
                                    <i class="<?= $cluster['icon'] ?>"></i>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 text-base md:text-lg tracking-tight">
                                        <?= htmlspecialchars($cluster['title']) ?>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">
                                        <?= htmlspecialchars($cluster['subtitle']) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 self-start sm:self-auto">
                                <span class="text-[11px] font-extrabold px-3 py-1 rounded-full <?= $cluster['badge_color'] ?>">
                                    <?= htmlspecialchars($cluster['badge']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- GRID SQUIRCLE CARD MENU (RAPI DI PC MAUPUN MOBILE) -->
                        <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-y-5 sm:gap-y-6 gap-x-2 sm:gap-x-4 items-start justify-items-center">
                            <?php foreach ($cluster['items'] as $item): ?>
                            <div class="squircle-item flex flex-col items-center w-full text-center group select-none" data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>">
                                <a href="<?= htmlspecialchars($item['href']) ?>" class="squircle-card flex flex-col items-center w-full focus:outline-none">
                                    
                                    <!-- Squircle Box Button (#0b8478 Gradient Theme) -->
                                    <div class="relative w-14 h-14 sm:w-16 sm:h-16 rounded-[20px] sm:rounded-[22px] bg-gradient-to-br <?= $item['gradient'] ?> text-white shadow-md flex items-center justify-center text-xl sm:text-2xl border border-white/20">
                                        <i class="<?= $item['icon'] ?>"></i>
                                    </div>

                                    <!-- Label Menu -->
                                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0b8478] transition-colors leading-tight line-clamp-2 max-w-[85px]">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </span>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    </section>
                    <?php endforeach; ?>
                </div>

                <!-- EMPTY STATE JIKA PENCARIAN TIDAK DITEMUKAN -->
                <div id="searchNoMatch" class="hidden text-center py-12 bg-white rounded-3xl border border-slate-200">
                    <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                        <i class="fas fa-search"></i>
                    </div>
                    <h4 class="font-bold text-slate-700 text-sm">Menu Tidak Ditemukan</h4>
                    <p class="text-xs text-slate-400 mt-1">Coba ketik kata kunci lain pada kolom pencarian.</p>
                </div>

            </div>
        </main>
    </div>

    <!-- SCRIPT FILTER / PENCARIAN & SMOOTH SCROLL -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('pilarSearchInput');
        const squircleItems = document.querySelectorAll('.squircle-item');
        const pilarSections = document.querySelectorAll('.pilar-card');
        const emptyState = document.getElementById('searchNoMatch');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let totalVisible = 0;

                pilarSections.forEach(section => {
                    let sectionVisibleCount = 0;
                    const itemsInSection = section.querySelectorAll('.squircle-item');

                    itemsInSection.forEach(item => {
                        const title = item.getAttribute('data-title') || '';
                        if (!query || title.includes(query)) {
                            item.classList.remove('hidden');
                            sectionVisibleCount++;
                            totalVisible++;
                        } else {
                            item.classList.add('hidden');
                        }
                    });

                    // Sembunyikan section jika tidak ada menu yang cocok di dalamnya
                    if (sectionVisibleCount === 0 && query !== '') {
                        section.classList.add('hidden');
                    } else {
                        section.classList.remove('hidden');
                    }
                });

                if (emptyState) {
                    if (totalVisible === 0) {
                        emptyState.classList.remove('hidden');
                    } else {
                        emptyState.classList.add('hidden');
                    }
                }
            });
        }

        // Smooth scroll untuk shortcut alur berpikir
        document.querySelectorAll('a[href^="#pilar-"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    });
    </script>
</body>
</html>
