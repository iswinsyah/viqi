<?php
require_once 'auth-unified.php';
requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();
$is_yayasan_pengurus = $is_admin || !empty(array_intersect(['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'admin', 'yayasan'], $roles));

// Master 5 Kolom x 4 Baris Matrix Simulasi Role Lembaga (Total 20 Slot)
$simulation_roles_grid = [
    // Baris 1 (5 Kolom)
    'all'                => ['label' => 'Semua Role', 'action' => 'all'],
    'ketua_yayasan'      => ['label' => 'Ketua Yayasan'],
    'sekretaris_yayasan' => ['label' => 'Sekr. Yayasan'],
    'bendahara_yayasan'  => ['label' => 'Bend. Yayasan'],
    'kepala_sekolah'     => ['label' => 'Kepala Sekolah'],

    // Baris 2 (5 Kolom)
    'kepala_mahad'       => ['label' => 'Kepala Ma\'had'],
    'kepala_ldu'         => ['label' => 'Kepala LDU'],
    'sekretaris_sekolah' => ['label' => 'Sekr. Sekolah'],
    'bendahara_sekolah'  => ['label' => 'Bend. Sekolah'],
    'admin_sekolah'      => ['label' => 'Admin Sekolah'],

    // Baris 3 (5 Kolom)
    'kepala_asrama'      => ['label' => 'Kepala Asrama'],
    'tutor'              => ['label' => 'Tutor'],
    'musyrif'            => ['label' => 'Musyrif / Ah'],
    'ustadz'             => ['label' => 'Ustadz / Ah'],
    'trainer'            => ['label' => 'Trainer'],

    // Baris 4 (5 Kolom)
    'santri_rijal'       => ['label' => 'Santri Rijal'],
    'santri_nisa'        => ['label' => 'Santri Nisa'],
    'orangtua'           => ['label' => 'Orangtua / Wali'],
    'pilih_semua'        => ['label' => 'Pilih Semua', 'action' => 'all'],
    'lepas_semua'        => ['label' => 'Lepas Semua', 'action' => 'reset']
];

// Tangkap Multi-Role Toggle Cepat (Direct Checkbox Toggle)
if (isset($_GET['toggle_role'])) {
    $tr = strtolower(trim($_GET['toggle_role']));
    if ($tr === 'all' || $tr === 'pilih_semua') {
        $_SESSION['active_role_views'] = ['all'];
    } elseif ($tr === 'reset' || $tr === 'lepas_semua') {
        $_SESSION['active_role_views'] = ['none'];
    } else {
        $current_views = $_SESSION['active_role_views'] ?? ['all'];
        if (in_array('all', $current_views) || in_array('none', $current_views)) {
            $current_views = [];
        }
        if (in_array($tr, $current_views)) {
            $current_views = array_diff($current_views, [$tr]);
        } else {
            $current_views[] = $tr;
        }
        if (empty($current_views)) {
            $current_views = ['none'];
        }
        $_SESSION['active_role_views'] = array_values($current_views);
    }
    header("Location: dashboard.php");
    exit;
}
$active_views = $_SESSION['active_role_views'] ?? ['all'];
$is_all_view = in_array('all', $active_views);
$is_none_view = in_array('none', $active_views);

// Logout Action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

// =========================================================
// SINKRONISASI DINAMIS DENGAN MANAJEMEN MENU DATABASE
// =========================================================
$db_permissions = [];
$res_perm = $conn->query("SELECT menu_key, allowed_roles FROM menu_permissions");
if ($res_perm) {
    while ($r = $res_perm->fetch_assoc()) {
        $db_permissions[$r['menu_key']] = array_map('trim', explode(',', strtolower($r['allowed_roles'])));
    }
}

$db_custom_labels = [];
$res_lbl = $conn->query("SELECT menu_key, custom_label, short_label FROM menu_custom_labels");
if ($res_lbl) {
    while ($r = $res_lbl->fetch_assoc()) {
        $db_custom_labels[$r['menu_key']] = [
            'full' => $r['custom_label'],
            'short' => !empty($r['short_label']) ? $r['short_label'] : $r['custom_label']
        ];
    }
}

// Fallback label 1 kata default untuk keindahan UI
$default_1word_labels = [
    'emodul' => 'E-Modul',
    'promes' => 'Promes',
    'jurnal' => 'Jurnal',
    'silabus' => 'Silabus',
    'nilai' => 'Nilai',
    'raport' => 'Raport',
    'ibadah' => 'Ibadah',
    'hafalan' => 'Hafalan',
    'adab' => 'Adab',
    'kesehatan' => 'Kesehatan',
    'belajar' => 'Belajar',
    'spp' => 'SPP',
    'uangsaku' => 'Saku',
    'induk' => 'Induk',
    'kas' => 'Kas',
    'pegawai' => 'Pegawai'
];

$all_grid_items = [];
$res_struct = $conn->query("SELECT * FROM menu_structure ORDER BY sort_order ASC");
if ($res_struct && $res_struct->num_rows > 0) {
    while ($r = $res_struct->fetch_assoc()) {
        $k = $r['menu_key'];
        $label = $db_custom_labels[$k]['short'] ?? ($default_1word_labels[$k] ?? ucwords(str_replace('_', ' ', $k)));
        $roles_allowed = $db_permissions[$k] ?? ['super_admin', 'ketua_yayasan'];
        
        $icon = trim($r['icon'] ?? '');
        if (!empty($icon) && !str_starts_with($icon, 'fa')) {
            $icon = 'fas fa-' . $icon;
        } elseif (str_starts_with($icon, 'fa-')) {
            $icon = 'fas ' . $icon;
        }
        
        $all_grid_items[$k] = [
            'label' => $label,
            'icon'  => $icon,
            'href'  => $r['href'],
            'roles' => $roles_allowed,
            'group' => $r['menu_group']
        ];
    }
}

// Filter Item Sesuai Role Pengguna & Simulasi Checkbox Multi-Role
$visible_items = [];
foreach ($all_grid_items as $key => $item) {
    // 1. Cek hak akses dasar akun pengguna
    $has_access = $is_admin;
    if (!$has_access) {
        foreach ($item['roles'] as $r) {
            $norm_r = str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
            if (in_array($norm_r, $roles) || in_array($r, $roles)) {
                $has_access = true;
                break;
            }
        }
    }
    if (!$has_access) continue;

    // 2. Terapkan Filter Checkbox Multi-Role (Jika sedang simulasi)
    if (!$is_all_view) {
        if ($is_none_view) continue;

        $matches_active_filter = false;
        foreach ($active_views as $av) {
            // Pemetaan alias role untuk fleksibilitas maksimal
            $av_aliases = [$av];
            if ($av === 'musyrif') { $av_aliases[] = 'musyrifah'; $av_aliases[] = 'kepala_asrama'; }
            if ($av === 'ustadz') { $av_aliases[] = 'ustadzah'; $av_aliases[] = 'guru'; }
            if ($av === 'orangtua') { $av_aliases[] = 'walisantri'; }
            if ($av === 'ketua_yayasan') { $av_aliases[] = 'super_admin'; }
            if ($av === 'santri_rijal' || $av === 'santri_nisa') { $av_aliases[] = 'santri'; $av_aliases[] = $av; }

            foreach ($av_aliases as $alias) {
                $alias_norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($alias)));
                foreach ($item['roles'] as $ir) {
                    $ir_norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($ir)));
                    if ($alias_norm === $ir_norm || $alias === $ir) {
                        $matches_active_filter = true;
                        break 2;
                    }
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
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#dcf3ee] min-h-screen text-slate-800 flex flex-col md:flex-row antialiased selection:bg-[#0b8478] selection:text-white">

    <!-- ========================================================= -->
    <!-- DESKTOP SIDEBAR (HANYA TAMPIL DI LAYAR PC / TABLET md:)   -->
    <!-- ========================================================= -->
    <aside class="hidden md:flex flex-col w-64 lg:w-72 bg-[#0b8478] text-white min-h-screen sticky top-0 h-screen shadow-2xl z-30 flex-shrink-0 border-r border-teal-700/50">
        
        <!-- SIDEBAR TOP: BRAND LOGO SADIGS -->
        <div class="p-6 border-b border-teal-700/60 flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                    <circle cx="50" cy="46" r="10" fill="#f59e0b" />
                    <path d="M50 14 C47 24, 47 28, 50 32 C53 28, 53 24, 50 14 Z" fill="#10b981" />
                    <path d="M68 20 C61 28, 59 32, 60 36 C64 33, 68 31, 74 24 Z" fill="#10b981" />
                    <path d="M80 36 C71 40, 68 43, 67 48 C72 47, 76 46, 84 41 Z" fill="#10b981" />
                    <path d="M32 20 C39 28, 41 32, 40 36 C36 33, 32 31, 26 24 Z" fill="#10b981" />
                    <path d="M20 36 C29 40, 32 43, 33 48 C28 47, 24 46, 16 41 Z" fill="#10b981" />
                    <path d="M30 62 C42 56, 48 60, 50 66 C52 60, 58 56, 70 62 C68 70, 52 74, 50 74 C48 74, 32 70, 30 62 Z" fill="#f59e0b" />
                    <path d="M22 68 C36 58, 48 64, 50 72 C52 64, 64 58, 78 68 C75 80, 52 86, 50 86 C48 86, 25 80, 22 68 Z" fill="#0b8478" />
                </svg>
            </div>
            <div>
                <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </div>

        <!-- SIDEBAR USER PROFILE CARD -->
        <div class="px-5 py-4 border-b border-teal-700/40 bg-teal-900/30">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-base shadow-sm border-2 border-white/80 overflow-hidden flex-shrink-0">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-[#0b8478]"></i>
                    <?php endif; ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                    <p class="text-[10px] text-teal-200 truncate mt-0.5">@<?= htmlspecialchars($user['username']) ?></p>
                    <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                        <?= htmlspecialchars($user['roles']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- SIDEBAR NAVIGATION LINKS -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
            <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-white text-[#0b8478] font-black shadow-sm transition">
                <i class="fas fa-house w-4 text-center"></i>
                <span>Beranda</span>
            </a>
            <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-clock w-4 text-center"></i>
                <span>Jadwal</span>
            </a>
            <a href="pengumuman.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-bullhorn w-4 text-center"></i>
                <span>Info</span>
            </a>

            <!-- SIMULASI ROLE WIDGET DI SIDEBAR -->
            <?php if ($is_admin || count($roles) > 1): ?>
            <div class="pt-4 mt-4 border-t border-teal-700/60">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-teal-200">Simulasi Role</span>
                    <button type="button" onclick="toggleRoleModal()" class="px-2 py-0.5 rounded bg-amber-400 hover:bg-amber-300 text-teal-950 font-bold text-[9px] transition cursor-pointer">
                        16 Role
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-1.5 text-[10px]">
                    <a href="dashboard.php?toggle_role=all" class="p-1.5 rounded-lg text-center font-bold transition <?= $is_all_view ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Semua
                    </a>
                    <a href="dashboard.php?toggle_role=tutor" class="p-1.5 rounded-lg text-center font-bold transition <?= in_array('tutor', $active_views) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Tutor
                    </a>
                    <a href="dashboard.php?toggle_role=musyrif" class="p-1.5 rounded-lg text-center font-bold transition <?= in_array('musyrif', $active_views) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Musyrif
                    </a>
                    <a href="dashboard.php?toggle_role=santri" class="p-1.5 rounded-lg text-center font-bold transition <?= in_array('santri', $active_views) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Santri
                    </a>
                    <a href="dashboard.php?toggle_role=orangtua" class="col-span-2 p-1.5 rounded-lg text-center font-bold transition <?= (in_array('orangtua', $active_views) || in_array('walisantri', $active_views)) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Orangtua
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </nav>

        <!-- SIDEBAR FOOTER: LOGOUT -->
        <div class="p-4 border-t border-teal-700/60">
            <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
                <i class="fas fa-arrow-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- ========================================================= -->
    <!-- MAIN CONTENT CANVAS (RESPONSIVE: MOBILE & PC)             -->
    <!-- ========================================================= -->
    <div class="flex-1 min-h-screen flex flex-col relative bg-[#dcf3ee]">

        <!-- ========================================================= -->
        <!-- 1. TOP HERO TEAL (HEADER + BRANDING + PROFILE)            -->
        <!-- ========================================================= -->
        <div class="bg-[#0b8478] text-white pt-6 pb-20 px-6 relative rounded-b-[28px] md:rounded-b-[36px] shadow-md flex-shrink-0">
            <div class="max-w-4xl mx-auto flex items-center justify-between">
                
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

            <!-- MULTI-ROLE 5 KOLOM X 4 BARIS DIRECT CHECKBOX SIMULATION -->
            <?php if ($is_admin || count($roles) > 1): ?>
            <div class="max-w-4xl mx-auto mt-4 pt-3 border-t border-teal-600/60">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-teal-100 flex items-center gap-1.5">
                        <i class="fas fa-sliders text-[9px]"></i> Filter Simulasi Multi-Role (5 Kolom x 4 Baris)
                    </span>
                    <div class="text-[10px] text-teal-200">
                        Status: <span class="font-extrabold text-white"><?= $is_all_view ? 'Semua Role' : ($is_none_view ? 'Kosong' : count($active_views).' Role Aktif') ?></span>
                    </div>
                </div>

                <!-- 5 KOLOM X 4 BARIS MATRIX CHECKBOX -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-1.5 text-[10px]">
                    <?php foreach ($simulation_roles_grid as $r_key => $r_data): 
                        $is_action = isset($r_data['action']);
                        if ($is_action) {
                            if ($r_data['action'] === 'all') {
                                $is_checked = $is_all_view;
                            } else {
                                $is_checked = false;
                            }
                        } else {
                            $is_checked = in_array($r_key, $active_views) || $is_all_view;
                        }
                    ?>
                    <a href="dashboard.php?toggle_role=<?= urlencode($r_key) ?>" class="px-2 py-1 rounded-lg font-bold flex items-center gap-1.5 transition text-left truncate <?= $is_checked ? 'bg-white text-[#0b8478] shadow-xs' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        <?php if ($is_action && $r_data['action'] === 'reset'): ?>
                            <i class="fas fa-square-minus text-rose-300"></i>
                        <?php elseif ($is_action && $r_data['action'] === 'all'): ?>
                            <i class="fas <?= $is_all_view ? 'fa-circle-check text-[#0b8478]' : 'fa-circle text-white/40' ?>"></i>
                        <?php else: ?>
                            <i class="fas <?= $is_checked ? 'fa-square-check text-[#0b8478]' : 'fa-square text-white/40' ?>"></i>
                        <?php endif; ?>
                        <span class="truncate"><?= htmlspecialchars($r_data['label']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ========================================================= -->
        <!-- 2. MAIN BINGKAI: KARTU PUTIH MELEKUK INDAH SQUIRCLE GRID  -->
        <!-- ========================================================= -->
        <main class="flex-1 px-4 sm:px-8 pt-0 pb-24 md:pb-12 w-full max-w-4xl mx-auto -mt-14 z-20">
            
            <!-- KARTU PUTIH UTAMA DENGAN SUDUT MELENGKUNG (SQUIRCLE) -->
            <div class="bg-white rounded-[36px] md:rounded-[40px] p-6 sm:p-10 shadow-xl shadow-teal-950/10 border border-teal-50">
                <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-y-6 sm:gap-y-8 gap-x-2 sm:gap-x-6 items-start justify-items-center">
                    
                    <?php foreach ($visible_items as $key => $item): ?>
                    <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center group cursor-pointer w-full text-center tap-highlight-transparent">
                        
                        <!-- Squircle Box Button (#0b8478) -->
                        <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-[20px] sm:rounded-[22px] bg-[#0b8478] group-hover:bg-[#086a60] text-white flex items-center justify-center text-xl sm:text-2xl shadow-md shadow-teal-900/15 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                            <i class="<?= $item['icon'] ?>"></i>
                        </div>

                        <!-- 1 Kata Keterangan Menu -->
                        <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0b8478] transition-colors leading-tight">
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
        <!-- 3. BOTTOM NAVIGATION BAR (HANYA MUNCUL DI MOBILE / md:hidden) -->
        <!-- ========================================================= -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-teal-100 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] flex items-center justify-around z-40 max-w-[440px] mx-auto px-2">
            <a href="dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 text-[#0b8478] font-black text-[10px]">
                <div class="w-9 h-7 rounded-full bg-teal-50 flex items-center justify-center mb-0.5">
                    <i class="fas fa-house text-base text-[#0b8478]"></i>
                </div>
                <span>Beranda</span>
            </a>
            <a href="kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-[#0b8478] font-bold text-[10px] transition">
                <i class="fas fa-calendar-alt text-lg mb-0.5"></i>
                <span>Kalender</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-[#0b8478] font-bold text-[10px] transition">
                <i class="fas fa-clock text-lg mb-0.5"></i>
                <span>Jadwal</span>
            </a>
            <a href="pengumuman.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-[#0b8478] font-bold text-[10px] transition">
                <i class="fas fa-bullhorn text-lg mb-0.5"></i>
                <span>Info</span>
            </a>
            <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center flex-1 py-1 text-rose-500 hover:text-rose-700 font-bold text-[10px] transition">
                <i class="fas fa-arrow-right-from-bracket text-lg mb-0.5"></i>
                <span>Keluar</span>
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
