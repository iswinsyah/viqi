<?php
require_once 'auth-unified.php';
requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();

// Master 16 Daftar Role Resmi Lembaga
$master_roles_list = [
    'ketua_yayasan'      => '1. Ketua Yayasan (Super Admin)',
    'sekretaris_yayasan' => '2. Sekretaris Yayasan',
    'bendahara_yayasan'  => '3. Bendahara Yayasan',
    'kepala_sekolah'     => '4. Kepala Sekolah',
    'kepala_mahad'       => '5. Kepala Ma\'had',
    'kepala_ldu'         => '6. Kepala LDU',
    'sekretaris_sekolah' => '7. Sekretaris Sekolah',
    'bendahara_sekolah'  => '8. Bendahara Sekolah',
    'admin_sekolah'      => '9. Admin Sekolah',
    'kepala_asrama'      => '10. Kepala Asrama (Rijal/Nisa)',
    'tutor'              => '11. Tutor',
    'musyrif'            => '12. Musyrif / Musyrifah',
    'ustadz'             => '13. Ustadz / Ustadzah',
    'trainer'            => '14. Trainer',
    'santri'             => '15. Santri (Rijal/Nisa)',
    'orangtua'           => '16. Orangtua / Walisantri'
];

// Tangkap Form Multi-Role Checklist Modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_role_simulation'])) {
    $selected_roles = $_POST['roles_sim'] ?? [];
    if (empty($selected_roles) || in_array('all', $selected_roles)) {
        $_SESSION['active_role_views'] = ['all'];
    } else {
        $_SESSION['active_role_views'] = array_map('trim', $selected_roles);
    }
    header("Location: dashboard.php");
    exit;
}

// Tangkap Multi-Role Toggle Cepat (Quick Pill Toggle)
if (isset($_GET['toggle_role'])) {
    $tr = strtolower(trim($_GET['toggle_role']));
    if ($tr === 'all') {
        $_SESSION['active_role_views'] = ['all'];
    } else {
        $current_views = $_SESSION['active_role_views'] ?? ['all'];
        if (in_array('all', $current_views)) {
            $current_views = [];
        }
        if (in_array($tr, $current_views)) {
            $current_views = array_diff($current_views, [$tr]);
        } else {
            $current_views[] = $tr;
        }
        if (empty($current_views)) {
            $current_views = ['all'];
        }
        $_SESSION['active_role_views'] = array_values($current_views);
    }
    header("Location: dashboard.php");
    exit;
}
$active_views = $_SESSION['active_role_views'] ?? ['all'];
$is_all_view = in_array('all', $active_views);

// Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Master Definisi Semua Grid Cards dengan Hak Akses 16 Role Resmi
$all_grid_items = [
    // --- AKADEMIK & KURIKULUM ---
    'emodul' => [
        'label' => 'E-Modul',
        'icon' => 'fas fa-book-open',
        'href' => 'admin-elearning.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_sekolah', 'kepala_mahad', 'kepala_ldu', 'tutor', 'ustadz', 'ustadzah', 'trainer', 'admin_sekolah']
    ],
    'promes' => [
        'label' => 'Promes',
        'icon' => 'fas fa-calendar-check',
        'href' => 'admin-kurikulum-prota-promes.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_sekolah', 'kepala_mahad', 'kepala_ldu', 'tutor', 'ustadz', 'ustadzah', 'trainer', 'admin_sekolah']
    ],
    'jurnal' => [
        'label' => 'Jurnal',
        'icon' => 'fas fa-clipboard-list',
        'href' => 'admin-absensi-pegawai.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_sekolah', 'kepala_mahad', 'sekretaris_sekolah', 'admin_sekolah', 'tutor', 'ustadz', 'ustadzah', 'trainer', 'musyrif', 'musyrifah', 'kepala_asrama']
    ],
    'silabus' => [
        'label' => 'Silabus',
        'icon' => 'fas fa-file-invoice',
        'href' => 'admin-pegawai-silabus.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_sekolah', 'kepala_mahad', 'kepala_ldu', 'tutor', 'ustadz', 'ustadzah', 'trainer']
    ],
    'nilai' => [
        'label' => 'Nilai',
        'icon' => 'fas fa-chart-bar',
        'href' => 'admin-leger.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_sekolah', 'kepala_mahad', 'admin_sekolah', 'tutor', 'ustadz', 'ustadzah', 'trainer']
    ],
    'raport' => [
        'label' => 'Raport',
        'icon' => 'fas fa-graduation-cap',
        'href' => 'admin-rapot-pkbm.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_sekolah', 'kepala_mahad', 'admin_sekolah', 'sekretaris_sekolah', 'tutor', 'ustadz', 'ustadzah', 'trainer', 'musyrif', 'musyrifah', 'kepala_asrama', 'orangtua', 'walisantri', 'santri']
    ],

    // --- ASRAMA & KEPENGASUHAN ---
    'ibadah' => [
        'label' => 'Ibadah',
        'icon' => 'fas fa-mosque',
        'href' => ($is_admin || in_array('musyrif', $roles) || in_array('kepala_asrama', $roles)) ? 'admin-validasi-ibadah-musyrif.php' : 'ruang-santri.php?view=ibadah_harian',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_mahad', 'kepala_asrama', 'musyrif', 'musyrifah', 'santri', 'orangtua', 'walisantri']
    ],
    'hafalan' => [
        'label' => 'Hafalan',
        'icon' => 'fas fa-quran',
        'href' => ($is_admin || in_array('musyrif', $roles) || in_array('kepala_asrama', $roles)) ? 'admin-setoran-hafalan-santri.php' : 'santri-laporan-hafalan.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_mahad', 'kepala_asrama', 'musyrif', 'musyrifah', 'santri', 'orangtua', 'walisantri']
    ],
    'adab' => [
        'label' => 'Adab',
        'icon' => 'fas fa-scale-balanced',
        'href' => 'admin-pegawai-laporan-adab.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_mahad', 'kepala_sekolah', 'kepala_asrama', 'musyrif', 'musyrifah', 'orangtua', 'walisantri']
    ],
    'kesehatan' => [
        'label' => 'Kesehatan',
        'icon' => 'fas fa-notes-medical',
        'href' => 'admin-cek-kesehatan-santri.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'kepala_mahad', 'kepala_sekolah', 'kepala_asrama', 'musyrif', 'musyrifah', 'orangtua', 'walisantri']
    ],

    // --- SANTRI BELAJAR ---
    'belajar' => [
        'label' => 'Belajar',
        'icon' => 'fas fa-laptop-code',
        'href' => 'ruang-santri.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'santri', 'tutor', 'ustadz', 'ustadzah', 'trainer']
    ],

    // --- KEUANGAN & WALISANTRI ---
    'spp' => [
        'label' => 'SPP',
        'icon' => 'fas fa-wallet',
        'href' => 'admin-rekap-spp.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'bendahara_yayasan', 'bendahara_sekolah', 'admin_sekolah', 'kepala_sekolah', 'orangtua', 'walisantri']
    ],
    'uangsaku' => [
        'label' => 'Saku',
        'icon' => 'fas fa-coins',
        'href' => 'admin-rekap-uang-saku.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'musyrif', 'musyrifah', 'kepala_asrama', 'orangtua', 'walisantri']
    ],

    // --- KELEMBAGAAN & ADMIN ---
    'induk' => [
        'label' => 'Induk',
        'icon' => 'fas fa-address-book',
        'href' => 'admin-buku-induk.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'kepala_sekolah', 'kepala_mahad', 'sekretaris_sekolah', 'admin_sekolah']
    ],
    'kas' => [
        'label' => 'Kas',
        'icon' => 'fas fa-book-bookmark',
        'href' => 'sekolah-pembukuan.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'bendahara_yayasan', 'bendahara_sekolah', 'kepala_sekolah', 'kepala_mahad']
    ],
    'pegawai' => [
        'label' => 'Pegawai',
        'icon' => 'fas fa-users-gear',
        'href' => 'yayasan2/asatidz.php',
        'roles' => ['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'kepala_sekolah', 'kepala_mahad']
    ]
];

// Filter Item Sesuai Role Pengguna & Simulasi Checkbox Multi-Role
$visible_items = [];
foreach ($all_grid_items as $key => $item) {
    // 1. Cek hak akses dasar akun
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

    // 2. Terapkan Filter Checkbox Multi-Role (Jika bukan mode 'all')
    if (!$is_all_view) {
        $matches_active_filter = false;
        foreach ($active_views as $av) {
            // Mapping alias
            $av_aliases = [$av];
            if ($av === 'musyrif') $av_aliases[] = 'musyrifah';
            if ($av === 'ustadz') $av_aliases[] = 'ustadzah';
            if ($av === 'orangtua') $av_aliases[] = 'walisantri';
            if ($av === 'ketua_yayasan') $av_aliases[] = 'super_admin';

            foreach ($av_aliases as $alias) {
                if (in_array($alias, $item['roles'])) {
                    $matches_active_filter = true;
                    break 2;
                }
            }
        }
        if (!$matches_active_filter) continue;
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
<body class="bg-[#dcf3ee] min-h-screen text-slate-800 flex flex-col antialiased selection:bg-[#0b8478] selection:text-white">

    <!-- ========================================================= -->
    <!-- WRAPPER CONTAINER (ALIGNED TO MOBILE SUPER-APP VIEWPORT)   -->
    <!-- ========================================================= -->
    <div class="w-full max-w-[440px] mx-auto min-h-screen flex flex-col relative bg-[#dcf3ee] shadow-2xl">

        <!-- ========================================================= -->
        <!-- 1. TOP HERO TEAL (HEADER + BRANDING + PROFILE)            -->
        <!-- ========================================================= -->
        <div class="bg-[#0b8478] text-white pt-6 pb-20 px-6 relative rounded-b-[28px] shadow-md flex-shrink-0">
            <div class="flex items-center justify-between">
                
                <!-- KIRI: BRAND LOGO SADIGS + SUBTITLE 2 BARIS ITALIC -->
                <div class="flex items-center space-x-3.5">
                    <!-- Logo Circle Badge -->
                    <div class="w-13 h-13 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0">
                        <svg viewBox="0 0 100 100" class="w-10 h-10">
                            <!-- Golden sun -->
                            <circle cx="50" cy="46" r="10" fill="#f59e0b" />
                            <!-- 5 Radiating Petals -->
                            <path d="M50 14 C47 24, 47 28, 50 32 C53 28, 53 24, 50 14 Z" fill="#10b981" />
                            <path d="M68 20 C61 28, 59 32, 60 36 C64 33, 68 31, 74 24 Z" fill="#10b981" />
                            <path d="M80 36 C71 40, 68 43, 67 48 C72 47, 76 46, 84 41 Z" fill="#10b981" />
                            <path d="M32 20 C39 28, 41 32, 40 36 C36 33, 32 31, 26 24 Z" fill="#10b981" />
                            <path d="M20 36 C29 40, 32 43, 33 48 C28 47, 24 46, 16 41 Z" fill="#10b981" />
                            <!-- Golden lower base -->
                            <path d="M30 62 C42 56, 48 60, 50 66 C52 60, 58 56, 70 62 C68 70, 52 74, 50 74 C48 74, 32 70, 30 62 Z" fill="#f59e0b" />
                            <!-- Teal open book base -->
                            <path d="M22 68 C36 58, 48 64, 50 72 C52 64, 64 58, 78 68 C75 80, 52 86, 50 86 C48 86, 25 80, 22 68 Z" fill="#0b8478" />
                        </svg>
                    </div>

                    <!-- Typography Title & 2-Line Subtitle -->
                    <div class="flex flex-col justify-center">
                        <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                        <div class="text-[11px] text-teal-100 font-medium italic leading-tight mt-1">
                            <div>Sistem Administrasi</div>
                            <div>Digital Sekolah</div>
                        </div>
                    </div>
                </div>

                <!-- KANAN: PROFILE BUTTON (WHITE CIRCLE + LABEL PROFILE) -->
                <button type="button" onclick="toggleProfileModal()" class="flex flex-col items-center group cursor-pointer focus:outline-none">
                    <div class="w-12 h-12 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-lg shadow-md group-hover:scale-105 transition-transform overflow-hidden border-2 border-white/80">
                        <?php if (!empty($user['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Profile" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-[#0b8478] text-xl"></i>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs font-bold text-white mt-1 group-hover:text-teal-200 transition-colors">Profile</span>
                </button>

            </div>

            <!-- MULTI-ROLE CHECKBOX SIMULATION PILL (UNTUK SUPER ADMIN / MULTI-ROLE) -->
            <?php if ($is_admin || count($roles) > 1): ?>
            <div class="mt-4 pt-3 border-t border-teal-600/60 flex items-center justify-center gap-1.5 flex-wrap text-[10px]">
                
                <!-- Opsi Semua -->
                <a href="dashboard.php?toggle_role=all" class="px-2 py-0.5 rounded-full font-bold flex items-center gap-1 transition <?= $is_all_view ? 'bg-white text-[#0b8478] shadow-xs' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                    <i class="fas <?= $is_all_view ? 'fa-circle-check text-[#0b8478]' : 'fa-circle text-white/40' ?>"></i>
                    <span>Semua</span>
                </a>

                <!-- Checkbox Tutor / Guru -->
                <?php $is_tutor_active = in_array('tutor', $active_views); ?>
                <a href="dashboard.php?toggle_role=tutor" class="px-2 py-0.5 rounded-full font-bold flex items-center gap-1 transition <?= $is_tutor_active ? 'bg-white text-[#0b8478] shadow-xs' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                    <i class="fas <?= $is_tutor_active ? 'fa-square-check text-[#0b8478]' : 'fa-square text-white/40' ?>"></i>
                    <span>Tutor</span>
                </a>

                <!-- Checkbox Musyrif -->
                <?php $is_musyrif_active = in_array('musyrif', $active_views); ?>
                <a href="dashboard.php?toggle_role=musyrif" class="px-2 py-0.5 rounded-full font-bold flex items-center gap-1 transition <?= $is_musyrif_active ? 'bg-white text-[#0b8478] shadow-xs' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                    <i class="fas <?= $is_musyrif_active ? 'fa-square-check text-[#0b8478]' : 'fa-square text-white/40' ?>"></i>
                    <span>Musyrif</span>
                </a>

                <!-- Checkbox Santri -->
                <?php $is_santri_active = in_array('santri', $active_views); ?>
                <a href="dashboard.php?toggle_role=santri" class="px-2 py-0.5 rounded-full font-bold flex items-center gap-1 transition <?= $is_santri_active ? 'bg-white text-[#0b8478] shadow-xs' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                    <i class="fas <?= $is_santri_active ? 'fa-square-check text-[#0b8478]' : 'fa-square text-white/40' ?>"></i>
                    <span>Santri</span>
                </a>

                <!-- Checkbox Orangtua -->
                <?php $is_wali_active = in_array('orangtua', $active_views) || in_array('walisantri', $active_views); ?>
                <a href="dashboard.php?toggle_role=orangtua" class="px-2 py-0.5 rounded-full font-bold flex items-center gap-1 transition <?= $is_wali_active ? 'bg-white text-[#0b8478] shadow-xs' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                    <i class="fas <?= $is_wali_active ? 'fa-square-check text-[#0b8478]' : 'fa-square text-white/40' ?>"></i>
                    <span>Orangtua</span>
                </a>

                <!-- Tombol Buka 16 Role Lengkap -->
                <button type="button" onclick="toggleRoleModal()" class="px-2.5 py-0.5 rounded-full font-bold bg-amber-400 text-teal-950 hover:bg-amber-300 transition flex items-center gap-1 shadow-xs cursor-pointer">
                    <i class="fas fa-sliders text-[9px]"></i>
                    <span>16 Role</span>
                </button>

            </div>
            <?php endif; ?>
        </div>

        <!-- ========================================================= -->
        <!-- 2. MAIN BINGKAI: KARTU PUTIH MELEKUK INDAH SQUIRCLE GRID  -->
        <!-- ========================================================= -->
        <main class="flex-1 px-4 pt-0 pb-24 w-full -mt-14 z-20">
            
            <!-- KARTU PUTIH UTAMA DENGAN SUDUT MELENGKUNG (SQUIRCLE) -->
            <div class="bg-white rounded-[36px] p-6 shadow-xl shadow-teal-950/10 border border-teal-50">
                <div class="grid grid-cols-4 gap-y-6 gap-x-2 items-start justify-items-center">
                    
                    <?php foreach ($visible_items as $key => $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center group cursor-pointer w-full text-center tap-highlight-transparent">
                        
                        <!-- Squircle Box Button (#0b8478) -->
                        <div class="w-14 h-14 rounded-[20px] bg-[#0b8478] group-hover:bg-[#086a60] text-white flex items-center justify-center text-xl shadow-md shadow-teal-900/15 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                            <i class="<?= $item['icon'] ?>"></i>
                        </div>

                        <!-- 1 Kata Keterangan Menu -->
                        <span class="text-[11px] font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0b8478] transition-colors leading-tight">
                            <?= htmlspecialchars($item['label']) ?>
                        </span>
                    </a>
                    <?php endforeach; ?>

                </div>
            </div>

            <!-- FOOTER BRANDING RINGKAS -->
            <div class="mt-6 text-center text-[11px] text-teal-800 font-semibold opacity-70">
                Villa Quran Indonesia • SADIGS 4.0
            </div>

        </main>

        <!-- ========================================================= -->
        <!-- 3. BOTTOM NAVIGATION BAR UNIVERSAL                        -->
        <!-- ========================================================= -->
        <nav class="fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-teal-100 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] flex items-center justify-around z-40 max-w-[440px] mx-auto px-2">
            <a href="dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 text-[#0b8478] font-black text-[10px]">
                <div class="w-9 h-7 rounded-full bg-teal-50 flex items-center justify-center mb-0.5">
                    <i class="fas fa-house text-base text-[#0b8478]"></i>
                </div>
                <span>Beranda</span>
            </a>
            <a href="admin-validasi-ibadah-musyrif.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0b8478] font-bold text-[10px] transition">
                <i class="fas fa-mosque text-lg mb-0.5"></i>
                <span>Ibadah</span>
            </a>
            <a href="admin-setoran-hafalan-santri.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0b8478] font-bold text-[10px] transition">
                <i class="fas fa-quran text-lg mb-0.5"></i>
                <span>Hafalan</span>
            </a>
            <a href="admin-rekap-spp.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-400 hover:text-[#0b8478] font-bold text-[10px] transition">
                <i class="fas fa-wallet text-lg mb-0.5"></i>
                <span>Keuangan</span>
            </a>
        </nav>

    </div>

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

    <!-- ========================================================= -->
    <!-- 5. 16-ROLE CHECKLIST SIMULATION MODAL (KHUSUS SUPER ADMIN)-->
    <!-- ========================================================= -->
    <?php if ($is_admin || count($roles) > 1): ?>
    <div id="roleModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-teal-100 relative animate-in fade-in zoom-in duration-150 max-h-[90vh] flex flex-col">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h3 class="font-black text-base text-slate-900 leading-tight">Simulasi Multi-Role (16 Role)</h3>
                    <p class="text-[11px] text-slate-500 mt-0.5">Pilih kombinasi role yang ingin diuji coba</p>
                </div>
                <button type="button" onclick="toggleRoleModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <form method="POST" action="dashboard.php" class="flex-1 flex flex-col min-h-0 pt-3">
                <input type="hidden" name="save_role_simulation" value="1">
                
                <div class="flex items-center justify-between mb-2 px-1 flex-shrink-0">
                    <button type="button" onclick="selectAllRoles(true)" class="text-[11px] font-bold text-[#0b8478] hover:underline cursor-pointer">Pilih Semua</button>
                    <button type="button" onclick="selectAllRoles(false)" class="text-[11px] font-bold text-rose-600 hover:underline cursor-pointer">Lepas Semua</button>
                </div>

                <!-- Scrollable Checkbox List -->
                <div class="flex-1 overflow-y-auto pr-1 space-y-1.5 pb-2">
                    <?php foreach ($master_roles_list as $role_key => $role_label): 
                        $is_checked = in_array($role_key, $active_views) || (in_array('all', $active_views));
                    ?>
                    <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-100 hover:bg-teal-50/70 cursor-pointer transition select-none">
                        <input type="checkbox" name="roles_sim[]" value="<?= htmlspecialchars($role_key) ?>" <?= $is_checked ? 'checked' : '' ?> class="role-checkbox w-4 h-4 text-[#0b8478] rounded focus:ring-[#0b8478]">
                        <span class="text-xs font-semibold text-slate-800"><?= htmlspecialchars($role_label) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Fixed Footer Button -->
                <div class="pt-3 border-t border-slate-100 flex-shrink-0">
                    <button type="submit" class="w-full bg-[#0b8478] hover:bg-[#086a60] text-white font-extrabold py-2.5 px-4 rounded-xl text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fas fa-check-double"></i> Terapkan Simulasi Role
                    </button>
                </div>
            </form>

        </div>
    </div>
    <?php endif; ?>

    <script>
        function toggleProfileModal() {
            const modal = document.getElementById('profileModal');
            if (modal) modal.classList.toggle('hidden');
        }

        function toggleRoleModal() {
            const modal = document.getElementById('roleModal');
            if (modal) modal.classList.toggle('hidden');
        }

        function selectAllRoles(check) {
            const checkboxes = document.querySelectorAll('.role-checkbox');
            checkboxes.forEach(cb => cb.checked = check);
        }
    </script>
</body>
</html>
