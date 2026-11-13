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

// MENU YAYASAN: HANYA ICON & 1-2 KATA CAPTION (SEPERTI ICON ANDROID)
$app_sections = [
    [
        'category' => 'Keuangan',
        'icon' => 'fas fa-wallet',
        'color' => 'text-emerald-700',
        'items' => [
            ['title' => 'Buku Kas', 'href' => 'pembukuan.php', 'icon' => 'fas fa-calculator', 'bg' => 'from-emerald-500 to-teal-700'],
            ['title' => 'Cashflow', 'href' => 'pembukuan.php?tab=proyeksi', 'icon' => 'fas fa-funnel-dollar', 'bg' => 'from-teal-500 to-emerald-700'],
            ['title' => 'Payroll', 'href' => 'gaji-pegawai.php', 'icon' => 'fas fa-coins', 'bg' => 'from-amber-500 to-amber-700'],
            ['title' => 'Tarif Gaji', 'href' => 'gaji-asatidz.php', 'icon' => 'fas fa-sliders', 'bg' => 'from-orange-500 to-amber-600'],
            ['title' => 'Tunjangan', 'href' => 'tunjangan.php', 'icon' => 'fas fa-award', 'bg' => 'from-yellow-500 to-amber-600'],
            ['title' => 'SPP', 'href' => 'rekap-spp.php', 'icon' => 'fas fa-file-invoice-dollar', 'bg' => 'from-green-600 to-emerald-700'],
            ['title' => 'Uang Saku', 'href' => 'rekap-uang-saku.php', 'icon' => 'fas fa-wallet', 'bg' => 'from-cyan-600 to-teal-700']
        ]
    ],
    [
        'category' => 'SDM & Pegawai',
        'icon' => 'fas fa-users-gear',
        'color' => 'text-blue-700',
        'items' => [
            ['title' => 'Data Guru', 'href' => 'asatidz.php', 'icon' => 'fas fa-users-gear', 'bg' => 'from-blue-600 to-indigo-700'],
            ['title' => 'AI HRD', 'href' => 'ai-agent-hrd.php', 'icon' => 'fas fa-robot', 'bg' => 'from-purple-600 to-indigo-700'],
            ['title' => 'KPI Staf', 'href' => 'kpi.php', 'icon' => 'fas fa-chart-bar', 'bg' => 'from-indigo-600 to-blue-700'],
            ['title' => 'KPI Kepsek', 'href' => 'kpi-kepala-sekolah.php', 'icon' => 'fas fa-chart-pie', 'bg' => 'from-violet-600 to-purple-700'],
            ['title' => 'KPI Musyrif', 'href' => 'kpi-musyrif.php', 'icon' => 'fas fa-chart-line', 'bg' => 'from-sky-500 to-blue-600'],
            ['title' => 'Supervisi', 'href' => '../admin-supervisi-mengajar.php', 'icon' => 'fas fa-clipboard-check', 'bg' => 'from-emerald-600 to-teal-700'],
            ['title' => 'Rekap KBM', 'href' => 'rekap-kbm-yayasan.php', 'icon' => 'fas fa-calendar-check', 'bg' => 'from-rose-500 to-red-600']
        ]
    ],
    [
        'category' => 'Kurikulum AI',
        'icon' => 'fas fa-brain',
        'color' => 'text-purple-700',
        'items' => [
            ['title' => 'E-Learning', 'href' => 'elearning-yayasan.php', 'icon' => 'fas fa-robot', 'bg' => 'from-purple-600 to-fuchsia-700'],
            ['title' => 'Capaian CP', 'href' => 'kurikulum-cp.php', 'icon' => 'fas fa-brain', 'bg' => 'from-fuchsia-600 to-purple-700'],
            ['title' => 'Solopreneur', 'href' => 'kurikulum-solopreneur.php', 'icon' => 'fas fa-rocket', 'bg' => 'from-rose-500 to-amber-600'],
            ['title' => 'Kitab', 'href' => 'kitab-rujukan.php', 'icon' => 'fas fa-book-open', 'bg' => 'from-amber-600 to-amber-800'],
            ['title' => 'Kelas', 'href' => 'master-kelas.php', 'icon' => 'fas fa-school', 'bg' => 'from-blue-500 to-indigo-600'],
            ['title' => 'Mapel', 'href' => 'master-mapel.php', 'icon' => 'fas fa-book', 'bg' => 'from-indigo-500 to-purple-600'],
            ['title' => 'Silabus', 'href' => 'master-silabus.php', 'icon' => 'fas fa-list-check', 'bg' => 'from-teal-600 to-emerald-700'],
            ['title' => 'Kalender', 'href' => 'master-kalender.php', 'icon' => 'fas fa-calendar-days', 'bg' => 'from-cyan-600 to-blue-700']
        ]
    ],
    [
        'category' => 'Santri & Asrama',
        'icon' => 'fas fa-mosque',
        'color' => 'text-teal-700',
        'items' => [
            ['title' => 'PSB Online', 'href' => '../admin-spmb.php', 'icon' => 'fas fa-user-graduate', 'bg' => 'from-emerald-600 to-teal-800'],
            ['title' => 'Tahfidz', 'href' => 'laporan-setoran-hafalan.php', 'icon' => 'fas fa-book-quran', 'bg' => 'from-teal-600 to-emerald-700'],
            ['title' => 'Ibadah', 'href' => 'ibadah-harian-santri.php', 'icon' => 'fas fa-mosque', 'bg' => 'from-green-600 to-emerald-700'],
            ['title' => 'Raport', 'href' => 'rapot-pkbm.php', 'icon' => 'fas fa-file-invoice', 'bg' => 'from-blue-600 to-cyan-700']
        ]
    ],
    [
        'category' => 'Tata Kelola',
        'icon' => 'fas fa-landmark',
        'color' => 'text-slate-700',
        'items' => [
            ['title' => 'Menu Akses', 'href' => 'manajemen-menu.php', 'icon' => 'fas fa-sliders', 'bg' => 'from-slate-600 to-slate-800'],
            ['title' => 'Struktur', 'href' => 'struktur-jobdesc.php', 'icon' => 'fas fa-sitemap', 'bg' => 'from-indigo-600 to-slate-700'],
            ['title' => 'Jobdesc', 'href' => 'jobdesc.php', 'icon' => 'fas fa-id-card', 'bg' => 'from-sky-600 to-slate-700'],
            ['title' => 'SOP', 'href' => 'admin-peraturan.php', 'icon' => 'fas fa-gavel', 'bg' => 'from-amber-600 to-orange-700'],
            ['title' => 'SWOT', 'href' => 'analisis-swot.php', 'icon' => 'fas fa-chart-line', 'bg' => 'from-violet-600 to-purple-800'],
            ['title' => 'Data Center', 'href' => 'data-center.php', 'icon' => 'fas fa-database', 'bg' => 'from-slate-700 to-zinc-900']
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
        /* Android Icon Style Interaction */
        .android-icon {
            transition: transform 0.18s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.18s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .android-icon:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.15);
        }
        .android-icon:active {
            transform: scale(0.92);
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
                </div>
            </div>

            <!-- ACTION CONTROLS -->
            <div class="flex items-center space-x-3">
                <a href="https://villaquranindonesia.com" target="_blank" class="text-xs text-[#0b8478] hover:text-teal-800 font-bold bg-teal-50 hover:bg-teal-100/70 border border-teal-200/60 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                    <i class="fas fa-globe"></i>
                    <span class="hidden sm:inline">Website</span>
                </a>
                <div class="h-9 w-9 rounded-full bg-[#0b8478] text-white flex items-center justify-center font-black text-xs shadow-md border-2 border-white">
                    <?= strtoupper(substr($y_nama, 0, 2)) ?>
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE DASHBOARD -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/70 px-4 py-4 md:p-8 pb-24 md:pb-12">
            <div class="max-w-6xl mx-auto space-y-4 md:space-y-6">

                <!-- SEARCH BAR MINIMALIS (ALA ANDROID SEARCH) -->
                <div class="relative w-full">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text" id="androidSearch" placeholder="Cari aplikasi..." 
                           class="w-full pl-11 pr-4 py-2.5 md:py-3 rounded-2xl border border-slate-200/90 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-[#0b8478] bg-white shadow-xs transition">
                </div>

                <!-- CARD SECTIONS (ANDROID APP LAUNCHER STYLE) -->
                <div id="androidLauncher" class="space-y-4 md:space-y-6">
                    <?php foreach ($app_sections as $sec): ?>
                    <div class="app-section bg-white rounded-3xl p-4 sm:p-6 border border-slate-200/70 shadow-xs">
                        
                        <!-- SECTION HEADER (1-2 WORDS ONLY) -->
                        <div class="flex items-center gap-2 mb-4 px-1">
                            <i class="<?= $sec['icon'] ?> text-sm <?= $sec['color'] ?>"></i>
                            <h3 class="font-extrabold text-xs sm:text-sm text-slate-700 uppercase tracking-wider">
                                <?= htmlspecialchars($sec['category']) ?>
                            </h3>
                        </div>

                        <!-- ANDROID SQUIRCLE ICON GRID (4 KOLOM DI HP, 6-7 KOLOM DI PC) -->
                        <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-y-5 sm:gap-y-6 gap-x-2 sm:gap-x-4 items-start justify-items-center">
                            <?php foreach ($sec['items'] as $item): ?>
                            <div class="app-item flex flex-col items-center w-full text-center select-none" data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>">
                                <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center w-full focus:outline-none group">
                                    
                                    <!-- SQUIRCLE ICON ANDROID -->
                                    <div class="android-icon relative w-14 h-14 sm:w-16 sm:h-16 rounded-[22px] sm:rounded-[24px] bg-gradient-to-br <?= $item['bg'] ?> text-white shadow-sm flex items-center justify-center text-xl sm:text-2xl border border-white/25">
                                        <i class="<?= $item['icon'] ?>"></i>
                                    </div>

                                    <!-- CAPTION 1-2 KATA SAJA -->
                                    <span class="text-[11px] sm:text-xs font-semibold text-slate-700 group-hover:text-[#0b8478] mt-2 tracking-tight text-center leading-tight line-clamp-1 max-w-[76px] sm:max-w-[85px] transition-colors">
                                        <?= htmlspecialchars($item['title']) ?>
                                    </span>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- EMPTY STATE PENCARIAN -->
                <div id="noAppFound" class="hidden text-center py-12 bg-white rounded-3xl border border-slate-200">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                        <i class="fas fa-search"></i>
                    </div>
                    <p class="font-bold text-slate-600 text-xs">Aplikasi tidak ditemukan</p>
                </div>

            </div>
        </main>
    </div>

    <!-- SCRIPT PENCARIAN INSTAN -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('androidSearch');
        const sections = document.querySelectorAll('.app-section');
        const emptyState = document.getElementById('noAppFound');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                let totalVisible = 0;

                sections.forEach(sec => {
                    let secVisible = 0;
                    const items = sec.querySelectorAll('.app-item');

                    items.forEach(item => {
                        const title = item.getAttribute('data-title') || '';
                        if (!query || title.includes(query)) {
                            item.classList.remove('hidden');
                            secVisible++;
                            totalVisible++;
                        } else {
                            item.classList.add('hidden');
                        }
                    });

                    if (secVisible === 0 && query !== '') {
                        sec.classList.add('hidden');
                    } else {
                        sec.classList.remove('hidden');
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
    });
    </script>
</body>
</html>
