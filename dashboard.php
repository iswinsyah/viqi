<?php
require_once 'auth-unified.php';
requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();

// Tangkap Filter Sudut Pandang (Role Switcher)
if (isset($_GET['view_role'])) {
    $vr = strtolower(trim($_GET['view_role']));
    setActiveRoleView($vr);
}
$active_view = getActiveRoleView();

// Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Master Definisi Semua Grid Cards Layanan
$all_cards = [
    // 1. KELOMPOK PEMBELAJARAN & GURU (TUTOR / ASATIDZ)
    'emodul' => [
        'title' => 'E-Modul & Flipbook',
        'desc' => '138 Modul PDF Resmi PKBM & Flipbook 3D',
        'icon' => 'fas fa-book-open',
        'color' => 'from-rose-500 to-rose-600',
        'bg_light' => 'bg-rose-50 text-rose-700 border-rose-200',
        'href' => 'admin-elearning.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'prota_promes' => [
        'title' => 'Pekan Efektif & Promes',
        'desc' => 'Distribusi 41 Pekan KBM, Prota & Promes',
        'icon' => 'fas fa-calendar-check',
        'color' => 'from-indigo-500 to-indigo-600',
        'bg_light' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'href' => 'admin-kurikulum-prota-promes.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'jurnal_kbm' => [
        'title' => 'Jurnal KBM & Presensi',
        'desc' => 'Agenda Mengajar & Kehadiran Santri',
        'icon' => 'fas fa-clipboard-list',
        'color' => 'from-teal-500 to-teal-600',
        'bg_light' => 'bg-teal-50 text-teal-700 border-teal-200',
        'href' => 'admin-absensi-pegawai.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'silabus_cp' => [
        'title' => 'Master Silabus & CP',
        'desc' => 'Capaian Pembelajaran Kurikulum Merdeka',
        'icon' => 'fas fa-file-invoice',
        'color' => 'from-blue-500 to-blue-600',
        'bg_light' => 'bg-blue-50 text-blue-700 border-blue-200',
        'href' => 'admin-pegawai-silabus.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'leger_nilai' => [
        'title' => 'Bank Nilai & Leger',
        'desc' => 'Rekap Nilai Tugas, Kuis, PTS & PAS',
        'icon' => 'fas fa-chart-bar',
        'color' => 'from-amber-500 to-amber-600',
        'bg_light' => 'bg-amber-50 text-amber-700 border-amber-200',
        'href' => 'admin-leger.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'rapot_pkbm' => [
        'title' => 'Raport Diknas PKBM',
        'desc' => 'e-Raport Paket B & Paket C Resmi',
        'icon' => 'fas fa-graduation-cap',
        'color' => 'from-emerald-500 to-emerald-600',
        'bg_light' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'href' => 'admin-rapot-pkbm.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],

    // 2. KELOMPOK ASRAMA & MUSYRIF
    'validasi_ibadah' => [
        'title' => 'Validasi Ibadah Santri',
        'desc' => 'Approval Sholat 5 Waktu & Ibadah Sunnah',
        'icon' => 'fas fa-mosque',
        'color' => 'from-teal-600 to-emerald-700',
        'bg_light' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'href' => 'admin-validasi-ibadah-musyrif.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama']
    ],
    'setoran_hafalan' => [
        'title' => 'Setoran Hafalan Quran',
        'desc' => 'Input Ziyadah & Murojaah Santri',
        'icon' => 'fas fa-quran',
        'color' => 'from-cyan-600 to-blue-600',
        'bg_light' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'href' => 'admin-setoran-hafalan-santri.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama']
    ],
    'laporan_adab' => [
        'title' => 'Disiplin & Adab Santri',
        'desc' => 'Pencatatan Karakter & Kedisiplinan',
        'icon' => 'fas fa-scale-balanced',
        'color' => 'from-violet-500 to-purple-600',
        'bg_light' => 'bg-purple-50 text-purple-700 border-purple-200',
        'href' => 'admin-pegawai-laporan-adab.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama']
    ],
    'cek_kesehatan' => [
        'title' => 'Cek Kesehatan Santri',
        'desc' => 'Monitoring Kondisi Fisik & Medis',
        'icon' => 'fas fa-notes-medical',
        'color' => 'from-rose-600 to-pink-600',
        'bg_light' => 'bg-pink-50 text-pink-700 border-pink-200',
        'href' => 'admin-cek-kesehatan-santri.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama']
    ],

    // 3. KELOMPOK RUANG BELAJAR SANTRI
    'santri_portal' => [
        'title' => 'Ruang Santri Belajar',
        'desc' => 'Akses 14 Mapel, Flipbook & Ustadz AI',
        'icon' => 'fas fa-laptop-code',
        'color' => 'from-[#0d8276] to-teal-500',
        'bg_light' => 'bg-teal-50 text-[#0d8276] border-teal-200',
        'href' => 'ruang-santri.php',
        'roles' => ['super_admin', 'santri']
    ],
    'santri_ibadah' => [
        'title' => 'Lapor Ibadah Harian',
        'desc' => 'Input Sholat Berjamaah & Tilawah Mandiri',
        'icon' => 'fas fa-pray',
        'color' => 'from-emerald-500 to-green-600',
        'bg_light' => 'bg-green-50 text-green-700 border-green-200',
        'href' => 'ruang-santri.php?view=ibadah_harian',
        'roles' => ['super_admin', 'santri']
    ],

    // 4. KELOMPOK WALISANTRI
    'tagihan_spp' => [
        'title' => 'SPP & Keuangan Santri',
        'desc' => 'Rekap Pembayaran & Tagihan Bulanan',
        'icon' => 'fas fa-wallet',
        'color' => 'from-emerald-600 to-teal-700',
        'bg_light' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'href' => 'admin-rekap-spp.php',
        'roles' => ['super_admin', 'walisantri', 'bendahara_sekolah']
    ],
    'uang_saku' => [
        'title' => 'Rekap Uang Saku',
        'desc' => 'Catatan Saldo & Penyaluran Uang Saku',
        'icon' => 'fas fa-coins',
        'color' => 'from-amber-500 to-yellow-600',
        'bg_light' => 'bg-amber-50 text-amber-700 border-amber-200',
        'href' => 'admin-rekap-uang-saku.php',
        'roles' => ['super_admin', 'walisantri', 'musyrif']
    ],

    // 5. KELOMPOK KELEMBAGAAN & YAYASAN (SUPER ADMIN)
    'buku_induk' => [
        'title' => 'Buku Induk Santri',
        'desc' => 'Master Data Santri, NISN & Orang Tua',
        'icon' => 'fas fa-address-book',
        'color' => 'from-blue-600 to-indigo-700',
        'bg_light' => 'bg-blue-50 text-blue-700 border-blue-200',
        'href' => 'admin-buku-induk.php',
        'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah']
    ],
    'pembukuan_sekolah' => [
        'title' => 'Buku Kas Sekolah',
        'desc' => 'Arus Kas Masuk, Keluar & Laporan Laba Rugi',
        'icon' => 'fas fa-book-bookmark',
        'color' => 'from-slate-700 to-slate-800',
        'bg_light' => 'bg-slate-100 text-slate-800 border-slate-300',
        'href' => 'sekolah-pembukuan.php',
        'roles' => ['super_admin', 'bendahara_sekolah', 'kepala_sekolah']
    ],
    'kelola_asatidz' => [
        'title' => 'Kelola Asatidz & Pegawai',
        'desc' => 'Master Akun, Hak Akses & Amanah Pegawai',
        'icon' => 'fas fa-users-gear',
        'color' => 'from-purple-600 to-indigo-800',
        'bg_light' => 'bg-purple-50 text-purple-700 border-purple-200',
        'href' => 'yayasan2/asatidz.php',
        'roles' => ['super_admin', 'kepala_sekolah']
    ]
];

// Filter Kartu Berdasarkan Hak Akses & Filter Sudut Pandang
$visible_cards = [];
foreach ($all_cards as $key => $card) {
    // 1. Cek Hak Akses
    $has_access = $is_admin;
    if (!$has_access) {
        foreach ($card['roles'] as $r) {
            if (in_array(strtolower($r), $roles)) {
                $has_access = true;
                break;
            }
        }
    }
    if (!$has_access) continue;

    // 2. Cek Filter Sudut Pandang
    if ($active_view !== 'all') {
        if ($active_view === 'tutor' && !in_array('tutor', $card['roles'])) continue;
        if ($active_view === 'musyrif' && !in_array('musyrif', $card['roles'])) continue;
        if ($active_view === 'santri' && !in_array('santri', $card['roles'])) continue;
        if ($active_view === 'walisantri' && !in_array('walisantri', $card['roles'])) continue;
    }

    $visible_cards[$key] = $card;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard Utama | SADIGS 4.0 - Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-[#f0f9f8] min-h-screen text-slate-800 flex flex-col md:flex-row antialiased overflow-x-hidden">

    <!-- ========================================================= -->
    <!-- DESKTOP SIDEBAR (TAMPIL DI PC/LAPTOP, TERSEMBUNYI DI HP)  -->
    <!-- ========================================================= -->
    <aside class="hidden md:flex flex-col w-64 bg-white border-r border-teal-100/80 p-5 shadow-xs flex-shrink-0 min-h-screen">
        <div class="flex items-center gap-3 pb-6 border-b border-slate-100">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#0d8276] to-teal-500 text-white flex items-center justify-center font-black text-lg shadow-md shadow-teal-900/10">
                <i class="fas fa-cubes-stacked"></i>
            </div>
            <div>
                <h2 class="font-black text-base text-slate-900 leading-none">SADIGS</h2>
                <p class="text-[10px] text-teal-600 font-bold mt-0.5">Villa Quran Indonesia</p>
            </div>
        </div>

        <!-- MENU UNIVERSAL DESKTOP -->
        <nav class="mt-6 space-y-1.5 flex-1">
            <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl font-bold text-xs bg-[#0d8276] text-white shadow-sm shadow-teal-900/10 transition">
                <i class="fas fa-house text-sm"></i> <span>Beranda Utama</span>
            </a>
            <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl font-bold text-xs text-slate-600 hover:bg-teal-50/70 hover:text-[#0d8276] transition">
                <i class="fas fa-calendar-alt text-sm"></i> <span>Kalender Akademik</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl font-bold text-xs text-slate-600 hover:bg-teal-50/70 hover:text-[#0d8276] transition">
                <i class="fas fa-clock text-sm"></i> <span>Jadwal Pelajaran</span>
            </a>
            <a href="artikel.php" class="flex items-center gap-3 px-3.5 py-3 rounded-2xl font-bold text-xs text-slate-600 hover:bg-teal-50/70 hover:text-[#0d8276] transition">
                <i class="fas fa-bullhorn text-sm"></i> <span>Informasi & Berita</span>
            </a>
        </nav>

        <!-- USER INFO BOTTOM DESKTOP -->
        <div class="pt-4 border-t border-slate-100">
            <a href="dashboard.php?action=logout" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-bold text-xs text-rose-600 hover:bg-rose-50 transition" onclick="return confirm('Yakin ingin keluar?');">
                <span class="flex items-center gap-2"><i class="fas fa-arrow-right-from-bracket"></i> Keluar</span>
            </a>
        </div>
    </aside>

    <!-- ========================================================= -->
    <!-- KONTEN UTAMA (HEADER + HERO + GRID CARDS)                 -->
    <!-- ========================================================= -->
    <div class="flex-1 flex flex-col min-h-screen pb-24 md:pb-10 overflow-x-hidden">
        
        <!-- 1. TOP HEADER APP (MOBILE & PC) -->
        <header class="h-16 bg-white border-b border-teal-100/60 shadow-2xs flex items-center justify-between px-4 sm:px-8 z-20 flex-shrink-0">
            
            <div class="flex items-center gap-2.5">
                <div class="md:hidden w-8 h-8 rounded-xl bg-[#0d8276] text-white flex items-center justify-center font-black text-sm shadow-sm">
                    <i class="fas fa-cubes-stacked"></i>
                </div>
                <div>
                    <h1 class="font-black text-sm sm:text-base text-slate-900 leading-tight">SADIGS 4.0</h1>
                    <p class="text-[10px] text-slate-400 hidden sm:block">Portal Sistem Administrasi Digital Sekolah Terpadu</p>
                </div>
            </div>

            <!-- KANAN: PROFIL DROPDOWN TRIGGER -->
            <div class="relative">
                <button type="button" onclick="toggleProfileModal()" class="flex items-center gap-2.5 bg-slate-50 hover:bg-teal-50/80 p-1.5 sm:px-3 sm:py-1.5 rounded-full sm:rounded-2xl border border-slate-200 transition focus:outline-none">
                    <div class="w-8 h-8 rounded-full bg-[#0d8276] text-white flex items-center justify-center font-bold text-xs shadow-2xs overflow-hidden">
                        <?php if (!empty($user['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-left hidden sm:block">
                        <div class="text-xs font-black text-slate-900 leading-tight truncate max-w-[130px]"><?= htmlspecialchars($user['nama_lengkap']) ?></div>
                        <div class="text-[9px] text-[#0d8276] font-extrabold uppercase"><?= $is_admin ? 'Super Admin' : htmlspecialchars($user['roles']) ?></div>
                    </div>
                    <i class="fas fa-chevron-down text-[10px] text-slate-400 hidden sm:inline ml-1"></i>
                </button>
            </div>
        </header>

        <!-- 2. BODY CONTENT -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-6xl w-full mx-auto space-y-6">
            
            <!-- HERO BANNER GREETING -->
            <div class="bg-gradient-to-r from-[#0d8276] via-teal-700 to-emerald-800 rounded-3xl p-5 sm:p-7 text-white shadow-md shadow-teal-900/10 relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 relative z-10">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-black bg-white/20 text-white backdrop-blur-xs mb-2">
                            <i class="fas fa-star text-amber-300"></i> Member Area Terpadu
                        </div>
                        <h2 class="text-lg sm:text-2xl font-black tracking-tight leading-snug">
                            Assalamu'alaikum, <?= htmlspecialchars($user['nama_lengkap']) ?>! 👋
                        </h2>
                        <p class="text-xs sm:text-sm text-teal-100 mt-1 max-w-xl font-light">
                            Selamat datang di portal SADIGS 4.0. Seluruh layanan akademik, asrama, dan belajar mandiri terintegrasi di sini.
                        </p>
                    </div>

                    <div class="flex items-center gap-2 bg-white/10 backdrop-blur-xs px-4 py-2.5 rounded-2xl border border-white/20 self-start sm:self-auto text-xs">
                        <i class="fas fa-calendar-day text-amber-300 text-base"></i>
                        <div>
                            <div class="font-black text-white"><?= date('d F Y') ?></div>
                            <div class="text-[10px] text-teal-200">Tahun Ajaran 2026/2027</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILTER SUDUT PANDANG (ROLE SWITCHER) - KHUSUS MULTI-ROLE -->
            <?php if ($is_admin || count($roles) > 1): ?>
            <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-teal-100/80 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2 text-xs font-black text-slate-700">
                    <i class="fas fa-sliders text-[#0d8276]"></i>
                    <span>Tampilan Layanan:</span>
                </div>
                
                <div class="flex flex-wrap items-center gap-1.5 text-xs">
                    <a href="dashboard.php?view_role=all" class="px-3 py-1.5 rounded-xl font-bold transition <?= ($active_view === 'all') ? 'bg-[#0d8276] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                        Semua Layanan
                    </a>
                    <a href="dashboard.php?view_role=tutor" class="px-3 py-1.5 rounded-xl font-bold transition <?= ($active_view === 'tutor') ? 'bg-[#0d8276] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                        👨‍🏫 Mode Guru / Tutor
                    </a>
                    <a href="dashboard.php?view_role=musyrif" class="px-3 py-1.5 rounded-xl font-bold transition <?= ($active_view === 'musyrif') ? 'bg-[#0d8276] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                        🕌 Mode Asrama
                    </a>
                    <a href="dashboard.php?view_role=santri" class="px-3 py-1.5 rounded-xl font-bold transition <?= ($active_view === 'santri') ? 'bg-[#0d8276] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                        🎒 Mode Santri
                    </a>
                    <a href="dashboard.php?view_role=walisantri" class="px-3 py-1.5 rounded-xl font-bold transition <?= ($active_view === 'walisantri') ? 'bg-[#0d8276] text-white shadow-2xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                        👨‍👩‍👧 Mode Orang Tua
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 3. GRID CARDS MENU LAYANAN UTAMA (ALA ANDROID APPS)      -->
            <!-- ========================================================= -->
            <div>
                <div class="flex items-center justify-between mb-3 px-1">
                    <h3 class="font-black text-sm text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-grid-2 text-[#0d8276]"></i>
                        <span>Menu & Layanan Tersedia</span>
                    </h3>
                    <span class="text-[11px] font-bold text-slate-400"><?= count($visible_cards) ?> Aplikasi</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    <?php foreach ($visible_cards as $key => $card): ?>
                    <a href="<?= htmlspecialchars($card['href']) ?>" class="bg-white hover:bg-teal-50/40 p-4 sm:p-5 rounded-3xl border border-teal-100/70 shadow-2xs hover:shadow-md hover:border-teal-300 transition-all duration-200 flex flex-col justify-between group">
                        
                        <!-- ICON SQUIRCLE -->
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr <?= $card['color'] ?> text-white flex items-center justify-center text-xl shadow-md group-hover:scale-108 transition-transform mb-3.5">
                            <i class="<?= $card['icon'] ?>"></i>
                        </div>

                        <!-- TITLE & DESC -->
                        <div>
                            <h4 class="font-extrabold text-xs sm:text-sm text-slate-900 leading-snug group-hover:text-[#0d8276] transition-colors">
                                <?= htmlspecialchars($card['title']) ?>
                            </h4>
                            <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 line-clamp-2 leading-relaxed">
                                <?= htmlspecialchars($card['desc']) ?>
                            </p>
                        </div>

                        <div class="mt-3.5 pt-2 border-t border-slate-50 flex items-center justify-between text-[10px] font-bold text-[#0d8276]">
                            <span>Buka Layanan</span>
                            <i class="fas fa-arrow-right text-[9px] group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================= -->
    <!-- PROFIL & PENGATURAN MODAL (KLIK FOTO POJOK KANAN ATAS)    -->
    <!-- ========================================================= -->
    <div id="profileModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-teal-100 relative animate-in fade-in zoom-in duration-150">
            <button onclick="toggleProfileModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fas fa-times text-xs"></i>
            </button>

            <div class="text-center pb-4 border-b border-slate-100">
                <div class="w-16 h-16 rounded-full bg-[#0d8276] text-white flex items-center justify-center font-black text-2xl mx-auto mb-3 shadow-md border-2 border-teal-100 overflow-hidden">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h3 class="font-black text-base text-slate-900 leading-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                <p class="text-xs text-slate-400 mt-0.5">@<?= htmlspecialchars($user['username']) ?></p>
                <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-black bg-teal-50 text-[#0d8276] border border-teal-200">
                    <i class="fas fa-id-badge"></i> Role: <?= htmlspecialchars($user['roles']) ?>
                </div>
            </div>

            <div class="py-3 space-y-1.5 text-xs">
                <a href="akunku.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-700 hover:bg-teal-50 font-bold transition">
                    <i class="fas fa-user-pen text-[#0d8276] w-4"></i> Edit Profil & Biodata
                </a>
                <a href="akunku.php?tab=password" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-700 hover:bg-teal-50 font-bold transition">
                    <i class="fas fa-key text-amber-500 w-4"></i> Ganti Password
                </a>
            </div>

            <div class="pt-3 border-t border-slate-100">
                <a href="dashboard.php?action=logout" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-700 font-extrabold py-2.5 px-4 rounded-xl text-xs transition flex items-center justify-center gap-2" onclick="return confirm('Yakin ingin keluar?');">
                    <i class="fas fa-arrow-right-from-bracket"></i> Keluar dari Aplikasi
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- 4. BOTTOM NAVIGATION BAR (KHUSUS HP / MOBILE VIEW)        -->
    <!-- ========================================================= -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-teal-100 shadow-lg flex items-center justify-around z-40 px-2">
        <a href="dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 text-[#0d8276] font-black text-[10px]">
            <i class="fas fa-house text-lg mb-0.5"></i>
            <span>Beranda</span>
        </a>
        <a href="kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0d8276] font-bold text-[10px] transition">
            <i class="fas fa-calendar-alt text-lg mb-0.5"></i>
            <span>Kalender</span>
        </a>
        <a href="admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0d8276] font-bold text-[10px] transition">
            <i class="fas fa-clock text-lg mb-0.5"></i>
            <span>Jadwal</span>
        </a>
        <a href="artikel.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0d8276] font-bold text-[10px] transition">
            <i class="fas fa-bullhorn text-lg mb-0.5"></i>
            <span>Informasi</span>
        </a>
        <button type="button" onclick="toggleProfileModal()" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0d8276] font-bold text-[10px] transition">
            <i class="fas fa-user text-lg mb-0.5"></i>
            <span>Akun</span>
        </button>
    </nav>

    <script>
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal) modal.classList.toggle('hidden');
        }
    </script>
</body>
</html>
