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

// Master Definisi Semua Grid Cards dengan 1 KATA KETERANGAN
$all_grid_items = [
    // --- GURU / TUTOR / AKADEMIK ---
    'emodul' => [
        'label' => 'E-Modul',
        'icon' => 'fas fa-book-open',
        'href' => 'admin-elearning.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'promes' => [
        'label' => 'Promes',
        'icon' => 'fas fa-calendar-check',
        'href' => 'admin-kurikulum-prota-promes.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'jurnal' => [
        'label' => 'Jurnal',
        'icon' => 'fas fa-clipboard-list',
        'href' => 'admin-absensi-pegawai.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'silabus' => [
        'label' => 'Silabus',
        'icon' => 'fas fa-file-invoice',
        'href' => 'admin-pegawai-silabus.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'nilai' => [
        'label' => 'Nilai',
        'icon' => 'fas fa-chart-bar',
        'href' => 'admin-leger.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah']
    ],
    'raport' => [
        'label' => 'Raport',
        'icon' => 'fas fa-graduation-cap',
        'href' => 'admin-rapot-pkbm.php',
        'roles' => ['super_admin', 'tutor', 'ustadz', 'guru', 'kepala_sekolah', 'walisantri', 'santri']
    ],

    // --- ASRAMA & MUSYRIF ---
    'ibadah' => [
        'label' => 'Ibadah',
        'icon' => 'fas fa-mosque',
        'href' => ($is_admin || in_array('musyrif', $roles)) ? 'admin-validasi-ibadah-musyrif.php' : 'ruang-santri.php?view=ibadah_harian',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama', 'santri', 'walisantri']
    ],
    'hafalan' => [
        'label' => 'Hafalan',
        'icon' => 'fas fa-quran',
        'href' => ($is_admin || in_array('musyrif', $roles)) ? 'admin-setoran-hafalan-santri.php' : 'santri-laporan-hafalan.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama', 'santri', 'walisantri']
    ],
    'adab' => [
        'label' => 'Adab',
        'icon' => 'fas fa-scale-balanced',
        'href' => 'admin-pegawai-laporan-adab.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama', 'walisantri']
    ],
    'kesehatan' => [
        'label' => 'Kesehatan',
        'icon' => 'fas fa-notes-medical',
        'href' => 'admin-cek-kesehatan-santri.php',
        'roles' => ['super_admin', 'musyrif', 'musyrifah', 'kepala_asrama', 'walisantri']
    ],

    // --- SANTRI BELAJAR ---
    'belajar' => [
        'label' => 'Belajar',
        'icon' => 'fas fa-laptop-code',
        'href' => 'ruang-santri.php',
        'roles' => ['super_admin', 'santri', 'tutor', 'ustadz']
    ],

    // --- KEUANGAN & WALISANTRI ---
    'spp' => [
        'label' => 'SPP',
        'icon' => 'fas fa-wallet',
        'href' => 'admin-rekap-spp.php',
        'roles' => ['super_admin', 'walisantri', 'bendahara_sekolah']
    ],
    'uangsaku' => [
        'label' => 'Saku',
        'icon' => 'fas fa-coins',
        'href' => 'admin-rekap-uang-saku.php',
        'roles' => ['super_admin', 'walisantri', 'musyrif']
    ],

    // --- KELEMBAGAAN & ADMIN ---
    'induk' => [
        'label' => 'Induk',
        'icon' => 'fas fa-address-book',
        'href' => 'admin-buku-induk.php',
        'roles' => ['super_admin', 'admin_sekolah', 'kepala_sekolah']
    ],
    'kas' => [
        'label' => 'Kas',
        'icon' => 'fas fa-book-bookmark',
        'href' => 'sekolah-pembukuan.php',
        'roles' => ['super_admin', 'bendahara_sekolah', 'kepala_sekolah']
    ],
    'pegawai' => [
        'label' => 'Pegawai',
        'icon' => 'fas fa-users-gear',
        'href' => 'yayasan2/asatidz.php',
        'roles' => ['super_admin', 'kepala_sekolah']
    ]
];

// Filter Item Sesuai Role & Role Switcher
$visible_items = [];
foreach ($all_grid_items as $key => $item) {
    $has_access = $is_admin;
    if (!$has_access) {
        foreach ($item['roles'] as $r) {
            if (in_array(strtolower($r), $roles)) {
                $has_access = true;
                break;
            }
        }
    }
    if (!$has_access) continue;

    // Filter Sudut Pandang
    if ($active_view !== 'all') {
        if ($active_view === 'tutor' && !in_array('tutor', $item['roles'])) continue;
        if ($active_view === 'musyrif' && !in_array('musyrif', $item['roles'])) continue;
        if ($active_view === 'santri' && !in_array('santri', $item['roles'])) continue;
        if ($active_view === 'walisantri' && !in_array('walisantri', $item['roles'])) continue;
    }

    $visible_items[$key] = $item;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>SADIGS 4.0 | Villa Quran Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
    </style>
</head>
<body class="bg-[#e1f5f2] min-h-screen text-slate-800 flex flex-col antialiased overflow-x-hidden">

    <!-- ========================================================= -->
    <!-- 1. TOP HEADER TEAL DENGAN LOGO SADIGS & PROFILE BUTTON    -->
    <!-- ========================================================= -->
    <header class="bg-[#0d8276] text-white pt-6 pb-10 px-5 sm:px-6 shadow-sm z-10 flex-shrink-0">
        <div class="max-w-md sm:max-w-lg mx-auto flex items-center justify-between">
            
            <!-- KIRI: BRAND LOGO SADIGS -->
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0">
                    <svg viewBox="0 0 100 100" class="w-full h-full">
                        <circle cx="50" cy="45" r="12" fill="#f59e0b" />
                        <path d="M50 12 L55 28 L45 28 Z" fill="#10b981" />
                        <path d="M72 20 L66 34 L58 28 Z" fill="#10b981" />
                        <path d="M84 40 L70 44 L68 36 Z" fill="#10b981" />
                        <path d="M28 20 L42 28 L34 34 Z" fill="#10b981" />
                        <path d="M16 40 L32 36 L30 44 Z" fill="#10b981" />
                        <path d="M22 64 C35 55, 48 60, 50 68 C52 60, 65 55, 78 64 C76 76, 52 82, 50 82 C48 82, 24 76, 22 64 Z" fill="#0d8276" />
                        <path d="M30 68 C40 62, 48 66, 50 72 C52 66, 60 62, 70 68 C68 76, 52 80, 50 80 C48 80, 32 76, 30 68 Z" fill="#f59e0b" />
                    </svg>
                </div>
                <div>
                    <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                    <p class="text-[11px] sm:text-xs text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital Sekolah</p>
                </div>
            </div>

            <!-- KANAN: PROFILE BUTTON (CIRCLE + LABEL PROFILE) -->
            <button type="button" onclick="toggleProfileModal()" class="flex flex-col items-center group cursor-pointer focus:outline-none">
                <div class="w-12 h-12 rounded-full bg-white text-[#0d8276] flex items-center justify-center font-black text-lg shadow-md group-hover:scale-105 transition-transform overflow-hidden border-2 border-teal-200">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Profile" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <span class="text-xs font-bold text-white mt-1 group-hover:text-teal-200 transition-colors">Profile</span>
            </button>

        </div>

        <!-- ROLE SWITCHER PILL (DI DALAM HEADER SUPAYA RAPI) -->
        <?php if ($is_admin || count($roles) > 1): ?>
        <div class="max-w-md sm:max-w-lg mx-auto mt-4 pt-3 border-t border-teal-600/50 flex items-center justify-center gap-1.5 flex-wrap">
            <span class="text-[10px] text-teal-100 font-semibold mr-1">Filter:</span>
            <a href="dashboard.php?view_role=all" class="px-3 py-0.5 rounded-full text-[10px] font-extrabold transition <?= ($active_view === 'all') ? 'bg-white text-[#0d8276] shadow-xs' : 'bg-teal-800/60 text-white hover:bg-teal-700' ?>">
                Semua
            </a>
            <a href="dashboard.php?view_role=tutor" class="px-3 py-0.5 rounded-full text-[10px] font-extrabold transition <?= ($active_view === 'tutor') ? 'bg-white text-[#0d8276] shadow-xs' : 'bg-teal-800/60 text-white hover:bg-teal-700' ?>">
                Guru
            </a>
            <a href="dashboard.php?view_role=musyrif" class="px-3 py-0.5 rounded-full text-[10px] font-extrabold transition <?= ($active_view === 'musyrif') ? 'bg-white text-[#0d8276] shadow-xs' : 'bg-teal-800/60 text-white hover:bg-teal-700' ?>">
                Asrama
            </a>
            <a href="dashboard.php?view_role=santri" class="px-3 py-0.5 rounded-full text-[10px] font-extrabold transition <?= ($active_view === 'santri') ? 'bg-white text-[#0d8276] shadow-xs' : 'bg-teal-800/60 text-white hover:bg-teal-700' ?>">
                Santri
            </a>
            <a href="dashboard.php?view_role=walisantri" class="px-3 py-0.5 rounded-full text-[10px] font-extrabold transition <?= ($active_view === 'walisantri') ? 'bg-white text-[#0d8276] shadow-xs' : 'bg-teal-800/60 text-white hover:bg-teal-700' ?>">
                Wali
            </a>
        </div>
        <?php endif; ?>
    </header>

    <!-- ========================================================= -->
    <!-- 2. MAIN BODY: WHITE SQUIRCLE CARD WITH 4-COLUMN GRID     -->
    <!-- ========================================================= -->
    <main class="flex-1 px-4 sm:px-6 pt-0 pb-24 max-w-md sm:max-w-lg mx-auto w-full -mt-6">
        
        <!-- KARTU UTAMA PUTIH DENGAN GRID 4 KOLOM SQUIRCLE IKON -->
        <div class="bg-white rounded-[32px] p-5 sm:p-7 shadow-lg shadow-teal-900/5 border border-teal-100/60">
            <div class="grid grid-cols-4 gap-y-5 gap-x-2 sm:gap-x-4 items-start justify-items-center">
                
                <?php foreach ($visible_items as $key => $item): ?>
                <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center group cursor-pointer w-full text-center tap-highlight-transparent">
                    
                    <!-- Squircle Box Button (#0d8276) -->
                    <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-[18px] bg-[#0d8276] group-hover:bg-[#0b6f65] text-white flex items-center justify-center text-xl sm:text-2xl shadow-md shadow-teal-900/10 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                        <i class="<?= $item['icon'] ?>"></i>
                    </div>

                    <!-- 1 Kata Keterangan Menu -->
                    <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0d8276] transition-colors leading-tight">
                        <?= htmlspecialchars($item['label']) ?>
                    </span>
                </a>
                <?php endforeach; ?>

            </div>
        </div>

        <!-- FOOTER BRANDING RINGKAS -->
        <div class="mt-6 text-center text-[11px] text-teal-800 font-semibold opacity-75">
            Villa Quran Indonesia • SADIGS 4.0
        </div>

    </main>

    <!-- ========================================================= -->
    <!-- 3. BOTTOM NAVIGATION BAR UNIVERSAL (FIXED DI BAWAH)       -->
    <!-- ========================================================= -->
    <nav class="fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-teal-100 shadow-lg flex items-center justify-around z-40 max-w-md sm:max-w-lg mx-auto px-2">
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
        <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center flex-1 py-1 text-rose-500 hover:text-rose-700 font-bold text-[10px] transition">
            <i class="fas fa-arrow-right-from-bracket text-lg mb-0.5"></i>
            <span>Keluar</span>
        </a>
    </nav>

    <!-- ========================================================= -->
    <!-- 4. PROFILE & ACCOUNT MODAL (KLIK FOTO POJOK KANAN ATAS)   -->
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

    <script>
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal) modal.classList.toggle('hidden');
        }
    </script>
</body>
</html>
