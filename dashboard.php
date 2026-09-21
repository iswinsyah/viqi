<?php
require_once 'auth-unified.php';
requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();
$is_yayasan_pengurus = $is_admin || !empty(array_intersect(['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'admin', 'yayasan'], $roles));
$can_view_role_simulation = $is_admin || in_array('ketua_yayasan', $roles) || in_array('super_admin', $roles);

// Master Matrix Simulasi Role Lembaga (5 Kolom)
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
    'web'                => ['label' => 'Web'],
    'marketing'          => ['label' => 'Marketing'],

    // Baris 5: Aksi Cepat
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

// Tangkap Form Modal Checklist Simulasi Multi-Role (18 Role)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['save_role_simulation'])) {
    $selected_sim_roles = $_POST['roles_sim'] ?? [];
    if (empty($selected_sim_roles)) {
        $_SESSION['active_role_views'] = ['none'];
    } elseif (count($selected_sim_roles) >= 18) {
        $_SESSION['active_role_views'] = ['all'];
    } else {
        $_SESSION['active_role_views'] = array_values(array_map('strtolower', $selected_sim_roles));
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

// AJAX: Simpan & Reset Urutan Drag-and-Drop Menu Pengguna
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_user_menu_order') {
        header('Content-Type: application/json');
        $user_key = 'user_' . ($user['id'] ?? $user['username'] ?? 'default');
        $order_data = $_POST['menu_order'] ?? '[]';
        $decoded = json_decode($order_data, true);
        if (is_array($decoded)) {
            $conn->query("CREATE TABLE IF NOT EXISTS user_menu_preferences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_key VARCHAR(100) UNIQUE,
                menu_order_json LONGTEXT,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )");
            $stmt = $conn->prepare("INSERT INTO user_menu_preferences (user_key, menu_order_json) VALUES (?, ?) ON DUPLICATE KEY UPDATE menu_order_json = VALUES(menu_order_json)");
            if ($stmt) {
                $stmt->bind_param("ss", $user_key, $order_data);
                $stmt->execute();
                $stmt->close();
            }
            echo json_encode(['status' => 'success']);
            exit;
        }
        echo json_encode(['status' => 'error', 'message' => 'Format data tidak valid']);
        exit;
    } elseif ($_POST['action'] === 'reset_user_menu_order') {
        header('Content-Type: application/json');
        $user_key = 'user_' . ($user['id'] ?? $user['username'] ?? 'default');
        $conn->query("DELETE FROM user_menu_preferences WHERE user_key = '" . $conn->real_escape_string($user_key) . "'");
        echo json_encode(['status' => 'success']);
        exit;
    }
}

// =========================================================
// SELF-HEALING & SINKRONISASI MANAJEMEN MENU DATABASE
// =========================================================
if ($conn && $conn instanceof mysqli) {
    @$conn->query("CREATE TABLE IF NOT EXISTS menu_structure (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_group VARCHAR(100) NOT NULL,
        menu_key VARCHAR(100) UNIQUE NOT NULL,
        sort_order INT NOT NULL DEFAULT 0,
        icon VARCHAR(100) NOT NULL,
        href VARCHAR(255) NOT NULL
    )");
    @$conn->query("CREATE TABLE IF NOT EXISTS menu_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_key VARCHAR(100) UNIQUE NOT NULL,
        allowed_roles TEXT NOT NULL
    )");
    @$conn->query("CREATE TABLE IF NOT EXISTS menu_custom_labels (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_key VARCHAR(100) UNIQUE NOT NULL,
        custom_label VARCHAR(100) NOT NULL,
        short_label VARCHAR(50) NOT NULL
    )");
}

$yayasan_pengurus_roles = ['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan'];
$is_yayasan_pengurus = $is_admin || !empty(array_intersect($yayasan_pengurus_roles, $roles));

// Self-Healing Database: Pastikan seluruh menu Ruang Yayasan & Operasional terdaftar kuat
$res_cnt_check = $conn->query("SELECT COUNT(*) as cnt FROM menu_structure WHERE menu_group = 'Ruang Yayasan'");
$cnt_yayasan = $res_cnt_check ? (int)$res_cnt_check->fetch_assoc()['cnt'] : 0;
if ($cnt_yayasan < 20) {
    $master_seed_menus = [
        // Ruang Yayasan (Khusus Pengurus: Ketua, Sekretaris, Bendahara)
        ['yayasan_pegawai', 'Ruang Yayasan', 1, 'fas fa-users-gear', 'yayasan2/asatidz.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Pegawai', 'Daftar Pegawai & Asatidz'],
        ['yayasan_menu', 'Ruang Yayasan', 2, 'fas fa-sliders', 'yayasan2/manajemen-menu.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Manajemen Menu', 'Manajemen Menu & Hak Akses'],
        ['yayasan_kelas', 'Ruang Yayasan', 3, 'fas fa-school', 'yayasan2/master-kelas.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Kelas', 'Master Data Kelas'],
        ['yayasan_mapel', 'Ruang Yayasan', 4, 'fas fa-book', 'yayasan2/master-mapel.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Mapel', 'Master Mata Pelajaran'],
        ['yayasan_elearning', 'Ruang Yayasan', 5, 'fas fa-robot', 'yayasan2/elearning-yayasan.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'E-Learning', 'Kurikulum & E-Learning (AI)'],
        ['yayasan_kitab', 'Ruang Yayasan', 6, 'fas fa-book-open', 'yayasan2/kitab-rujukan.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Kitab', 'Master Kitab Rujukan'],
        ['yayasan_hafalan', 'Ruang Yayasan', 7, 'fas fa-book-quran', 'yayasan2/laporan-setoran-hafalan.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Hafalan', 'Laporan Setoran Hafalan'],
        ['yayasan_ibadah', 'Ruang Yayasan', 8, 'fas fa-mosque', 'yayasan2/ibadah-harian-santri.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Ibadah', 'Rekap Ibadah Harian Santri'],
        ['yayasan_raport', 'Ruang Yayasan', 9, 'fas fa-file-invoice', 'yayasan2/rapot-pkbm.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Raport', 'Monitoring Raport PKBM'],
        ['yayasan_kas', 'Ruang Yayasan', 10, 'fas fa-calculator', 'yayasan2/pembukuan.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Kas', 'Pembukuan Terpusat Lembaga'],
        ['yayasan_cashflow', 'Ruang Yayasan', 11, 'fas fa-funnel-dollar', 'yayasan2/pembukuan.php?tab=proyeksi', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Cashflow', 'Perencanaan & Cashflow Kas'],
        ['yayasan_kpi', 'Ruang Yayasan', 12, 'fas fa-chart-bar', 'yayasan2/kpi.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'KPI', 'Monitoring AI & Kinerja Pegawai'],
        ['yayasan_kpi_musyrif', 'Ruang Yayasan', 13, 'fas fa-chart-line', 'yayasan2/kpi-musyrif.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'KPI-Asrama', 'KPI Musyrif Asrama'],
        ['yayasan_kpi_kepsek', 'Ruang Yayasan', 14, 'fas fa-chart-pie', 'yayasan2/kpi-kepala-sekolah.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'KPI-Kepsek', 'KPI Kepala Sekolah'],
        ['yayasan_supervisi', 'Ruang Yayasan', 15, 'fas fa-clipboard-check', 'admin-supervisi-mengajar.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Supervisi', 'Supervisi Mengajar Asatidz'],
        ['yayasan_gaji', 'Ruang Yayasan', 16, 'fas fa-coins', 'yayasan2/gaji-pegawai.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Gaji', 'Rekap Gaji (Payroll)'],
        ['yayasan_tarif_gaji', 'Ruang Yayasan', 17, 'fas fa-sliders', 'yayasan2/gaji-asatidz.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Tarif', 'Pengaturan Tarif Gaji'],
        ['yayasan_ai_hrd', 'Ruang Yayasan', 18, 'fas fa-robot', 'yayasan2/ai-agent-hrd.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'AI-HRD', 'AI Agent HRD & Personalia'],
        ['yayasan_spp', 'Ruang Yayasan', 19, 'fas fa-file-invoice-dollar', 'yayasan2/rekap-spp.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'SPP', 'Rekap Pembayaran SPP/Keuangan'],
        ['yayasan_saku', 'Ruang Yayasan', 20, 'fas fa-wallet', 'yayasan2/rekap-uang-saku.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Saku', 'Rekap Uang Saku Santri'],
        ['yayasan_tunjangan', 'Ruang Yayasan', 21, 'fas fa-award', 'yayasan2/tunjangan.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Tunjangan', 'Pengaturan Tunjangan'],
        ['yayasan_swot', 'Ruang Yayasan', 22, 'fas fa-chart-line', 'yayasan2/analisis-swot.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'SWOT', 'Analisis SWOT & Strategi'],
        ['yayasan_struktur', 'Ruang Yayasan', 23, 'fas fa-sitemap', 'yayasan2/struktur-jobdesc.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Struktur', 'Struktur Organisasi'],
        ['yayasan_jobdesc', 'Ruang Yayasan', 24, 'fas fa-id-card', 'yayasan2/jobdesc.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Jobdesc', 'Job Description Pegawai'],
        ['yayasan_peraturan', 'Ruang Yayasan', 25, 'fas fa-gavel', 'yayasan2/admin-peraturan.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'SOP', 'SOP & Peraturan Yayasan'],
        ['yayasan_solopreneur', 'Ruang Yayasan', 26, 'fas fa-rocket', 'yayasan2/kurikulum-solopreneur.php', 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan', 'Solopreneur', 'Inkubator Kurikulum Solopreneur']
    ];
    foreach ($master_seed_menus as $m) {
        list($key, $grp, $ord, $ico, $hrf, $al_roles, $sh_lbl, $fl_lbl) = $m;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('$grp', '$key', $ord, '$ico', '$hrf') ON DUPLICATE KEY UPDATE menu_group='$grp', sort_order=$ord, icon='$ico', href='$hrf'");
        $conn->query("INSERT INTO menu_permissions (menu_key, allowed_roles) VALUES ('$key', '$al_roles') ON DUPLICATE KEY UPDATE allowed_roles='$al_roles'");
        $conn->query("INSERT INTO menu_custom_labels (menu_key, custom_label, short_label) VALUES ('$key', '$fl_lbl', '$sh_lbl') ON DUPLICATE KEY UPDATE custom_label='$fl_lbl', short_label='$sh_lbl'");
    }
}

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
    'yayasan_pegawai'    => 'Pegawai',
    'yayasan_menu'       => 'Manajemen Menu',
    'yayasan_kelas'      => 'Kelas',
    'yayasan_mapel'      => 'Mapel',
    'yayasan_elearning'  => 'E-Learning',
    'yayasan_kitab'      => 'Kitab',
    'yayasan_hafalan'    => 'Hafalan',
    'yayasan_ibadah'     => 'Ibadah',
    'yayasan_raport'     => 'Raport',
    'yayasan_kas'        => 'Kas',
    'yayasan_cashflow'   => 'Cashflow',
    'yayasan_kpi'        => 'KPI',
    'yayasan_kpi_musyrif'=> 'KPI Asrama',
    'yayasan_kpi_kepsek' => 'KPI Kepsek',
    'yayasan_supervisi'  => 'Supervisi',
    'yayasan_gaji'       => 'Gaji',
    'yayasan_tarif_gaji' => 'Tarif Gaji',
    'yayasan_ai_hrd'     => 'AI-HRD',
    'yayasan_spp'        => 'SPP Yayasan',
    'yayasan_saku'       => 'Saku Yayasan',
    'yayasan_tunjangan'  => 'Tunjangan',
    'yayasan_swot'       => 'SWOT',
    'yayasan_struktur'   => 'Struktur',
    'yayasan_jobdesc'    => 'Jobdesc',
    'yayasan_peraturan'  => 'SOP',
    'yayasan_solopreneur'=> 'Solopreneur',
    'emodul'             => 'E-Modul',
    'promes'             => 'Promes',
    'jurnal'             => 'Jurnal',
    'silabus'            => 'Silabus',
    'nilai'              => 'Nilai',
    'raport'             => 'Raport',
    'ibadah'             => 'Ibadah',
    'hafalan'            => 'Hafalan',
    'adab'               => 'Adab',
    'kesehatan'          => 'Kesehatan',
    'belajar'            => 'Belajar',
    'spp'                => 'SPP',
    'uangsaku'           => 'Saku',
    'induk'              => 'Induk',
    'kas'                => 'Kas',
    'pegawai'            => 'Pegawai',
    'rekap_ibadah_santri'=> 'Ibadah Asrama',
    'rekap_setoran_santri'=> 'Rekap Setoran',
    'setoran_hafalan_santri'=> 'Hafalan',
    'kontrol_jam_kosong' => 'Jam Kosong',
    'penagihan_spp'      => 'Tagihan SPP',
    'kpi_admin_sekolah'  => 'KPI Admin',
    'jadwal_rapat'       => 'Rapat',
    'salary_admin'       => 'Salary Admin',
    'manajemen_elearning'=> 'E-Learning Guru',
    'ruang_web'          => 'Web Admin',
    'ruang_marketing'    => 'Marketing' 
];

$all_grid_items = [];
$res_struct = $conn->query("SELECT * FROM menu_structure WHERE menu_key NOT IN ('emodul', 'hafalan', 'kalender', 'akunku', 'prota_promes', 'yayasan_update', 'update', 'absensi_pegawai', 'absensi', 'jurnal', 'jurnal_mengajar', 'ruang_web', 'ruang_marketing') AND href NOT LIKE '%admin-absensi-pegawai.php%' ORDER BY sort_order ASC");
if ($res_struct && $res_struct->num_rows > 0) {
    while ($r = $res_struct->fetch_assoc()) {
        $k = $r['menu_key'];
        $label = $db_custom_labels[$k]['short'] ?? ($default_1word_labels[$k] ?? ucwords(str_replace('_', ' ', $k)));
        $roles_allowed = $db_permissions[$k] ?? ['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan'];
        
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

// Cek Otoritas Visibilitas Frame Ruang Yayasan (Hanya Pengurus Yayasan: super_admin, ketua_yayasan, sekretaris_yayasan, bendahara_yayasan)
$show_yayasan_frame = false;
if ($is_yayasan_pengurus) {
    if ($is_all_view) {
        $show_yayasan_frame = true;
    } elseif (!$is_none_view) {
        if (!empty(array_intersect($yayasan_pengurus_roles, $active_views))) {
            $show_yayasan_frame = true;
        }
    }
}

// Pisahkan Item: Frame 1 (Ruang Yayasan) & Frame 2 (Menu Operasional)
$yayasan_items = [];
$operational_items = [];

foreach ($all_grid_items as $key => $item) {
    if ($item['group'] === 'Ruang Yayasan') {
        if ($show_yayasan_frame) {
            $yayasan_items[$key] = $item;
        }
    } else {
        // Filter Menu Operasional Sesuai Hak Akses Pengguna
        $has_access = $is_admin;
        if (!$has_access) {
            foreach ($item['roles'] as $r) {
                if (hasRole($r)) {
                    $has_access = true;
                    break;
                }
            }
        }
        if (!$has_access) continue;

        if (!$is_all_view) {
            if ($is_none_view) continue;

            $matches_active_filter = false;
            foreach ($active_views as $av) {
                $av_aliases = getRoleAliases($av);
                foreach ($item['roles'] as $ir) {
                    $ir_aliases = getRoleAliases($ir);
                    if (!empty(array_intersect($av_aliases, $ir_aliases))) {
                        $matches_active_filter = true;
                        break 2;
                    }
                }
            }
            if (!$matches_active_filter) continue;
        }

        $operational_items[$key] = $item;
    }
}

$visible_items = $operational_items;

// 1. Cek Visibilitas Absensi Pegawai (Semua Staf/Pegawai kecuali Santri & Walisantri)
$show_absensi_pegawai = false;
if ($is_admin) {
    $show_absensi_pegawai = true;
} else {
    foreach ($roles as $r) {
        $canon = normalizeCanonicalRole($r);
        if (!in_array($canon, ['santri', 'orangtua'])) {
            $show_absensi_pegawai = true;
            break;
        }
    }
}

// 2. Cek Otoritas Role Khusus Mengajar & Form Jurnal (Hanya: Tutor, Ustadz, Trainer)
$has_teaching_role = hasAnyRole(['tutor', 'ustadz', 'trainer']);
$show_absensi_mengajar = $has_teaching_role;
$show_jurnal_mengajar = $has_teaching_role;

// Penyesuaian Mode Simulasi Role (Matrix Filter di Header)
if (!$is_all_view) {
    if ($is_none_view) {
        $show_absensi_pegawai = false;
        $show_absensi_mengajar = false;
        $show_jurnal_mengajar = false;
    } else {
        $show_absensi_pegawai = false;
        $show_absensi_mengajar = false;
        $show_jurnal_mengajar = false;

        foreach ($active_views as $av) {
            $canon_av = normalizeCanonicalRole($av);
            if (!in_array($canon_av, ['santri', 'orangtua'])) {
                $show_absensi_pegawai = true;
            }
            if (in_array($canon_av, ['tutor', 'ustadz', 'trainer'])) {
                $show_absensi_mengajar = true;
                $show_jurnal_mengajar = true;
            }
        }
    }
} else {
    // Mode "Semua Role" (Matrix All View): jika akun super admin, tampilkan akses lengkap
    if ($is_admin) {
        $show_absensi_mengajar = true;
        $show_jurnal_mengajar = true;
    }
}

// =========================================================
// OTORITAS AKSES & DATA FRAME RUANG WEB DAN RUANG MARKETING
// Khusus: Ketua Yayasan, Sekretaris Yayasan, Bendahara Yayasan,
// serta Role Khusus Web dan Marketing (atau Super Admin)
// =========================================================
$yayasan_core_roles = ['ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan'];

// Role Web: bisa diakses oleh Ketua, Sekr, Bendahara Yayasan, Super Admin, atau role Web
$can_see_web = $is_admin;
if (!$can_see_web) {
    foreach ($roles as $r) {
        $r_norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
        if (in_array($r_norm, array_merge($yayasan_core_roles, ['web', 'admin_web', 'admin']))) {
            $can_see_web = true;
            break;
        }
    }
}

// Role Marketing: bisa diakses oleh Ketua, Sekr, Bendahara Yayasan, Super Admin, atau role Marketing
$can_see_marketing = $is_admin;
if (!$can_see_marketing) {
    foreach ($roles as $r) {
        $r_norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
        if (in_array($r_norm, array_merge($yayasan_core_roles, ['marketing']))) {
            $can_see_marketing = true;
            break;
        }
    }
}

// Sinkronisasi dengan Matrix Filter Simulasi Multi-Role di Header Dashboard
if (!$is_all_view) {
    if ($is_none_view) {
        $can_see_web = false;
        $can_see_marketing = false;
    } else {
        $can_see_web = false;
        $can_see_marketing = false;
        foreach ($active_views as $av) {
            $av_norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($av)));
            if (in_array($av_norm, $yayasan_core_roles) || ($av_norm === 'super_admin' && $is_admin)) {
                $can_see_web = true;
                $can_see_marketing = true;
                break;
            }
            if (in_array($av_norm, ['web', 'admin_web', 'admin'])) {
                $can_see_web = true;
            }
            if (in_array($av_norm, ['marketing'])) {
                $can_see_marketing = true;
            }
        }
    }
}

// Data Menu Grid Card Ruang Web (Pengaturan Web)
$ruang_web_cards = [
    ['label' => 'Dashboard Web', 'icon' => 'fas fa-tachometer-alt', 'href' => 'admin.php'],
    ['label' => 'Hero Banner', 'icon' => 'fas fa-home', 'href' => 'admin-hero.php'],
    ['label' => 'Tentang Kami', 'icon' => 'fas fa-info-circle', 'href' => 'admin-tentang.php'],
    ['label' => 'Pengajar', 'icon' => 'fas fa-chalkboard-teacher', 'href' => 'admin-pengajar.php'],
    ['label' => 'Fasilitas', 'icon' => 'fas fa-building', 'href' => 'admin-fasilitas.php'],
    ['label' => 'Kurikulum', 'icon' => 'fas fa-book', 'href' => 'admin-kurikulum.php'],
    ['label' => 'Galeri', 'icon' => 'fas fa-images', 'href' => 'admin-galeri.php'],
    ['label' => 'Testimoni', 'icon' => 'fas fa-comments', 'href' => 'admin-testimoni.php'],
    ['label' => 'Info Biaya', 'icon' => 'fas fa-money-bill-wave', 'href' => 'admin-biaya.php'],
    ['label' => 'Parenting', 'icon' => 'fas fa-calendar-check', 'href' => 'admin-parenting.php'],
    ['label' => 'Artikel Blog', 'icon' => 'fas fa-file-alt', 'href' => 'admin-artikel.php'],
    ['label' => 'Lead Magnet', 'icon' => 'fas fa-bullhorn', 'href' => 'admin-popup.php'],
    ['label' => 'Media', 'icon' => 'fas fa-folder-open', 'href' => 'admin-media.php'],
    ['label' => 'Pengaturan', 'icon' => 'fas fa-cog', 'href' => 'admin-pengaturan.php'],
];

// Data Menu Grid Card Ruang Marketing (AI & Prospek)
$ruang_marketing_cards = [
    ['label' => 'Dashboard Mkt', 'icon' => 'fas fa-tachometer-alt', 'href' => 'dashboard-marketing.php'],
    ['label' => 'Pipeline', 'icon' => 'fas fa-filter', 'href' => 'data-pipeline.php'],
    ['label' => 'Data Agen', 'icon' => 'fas fa-users', 'href' => 'data-agen.php'],
    ['label' => 'Pendaftar SPMB', 'icon' => 'fas fa-user-graduate', 'href' => 'admin-spmb.php'],
    ['label' => 'AI Control Hub', 'icon' => 'fas fa-robot', 'href' => 'admin-ai-hub.php'],
    ['label' => 'Analisa Persona', 'icon' => 'fas fa-brain', 'href' => 'admin-analisa.php'],
    ['label' => 'Trend Scout', 'icon' => 'fas fa-chart-line', 'href' => 'admin-trend-scout.php'],
    ['label' => 'Community Scout', 'icon' => 'fas fa-search-location', 'href' => 'admin-community-scout.php'],
    ['label' => 'Keyword Explorer', 'icon' => 'fas fa-search-dollar', 'href' => 'admin-kalender.php'],
    ['label' => 'Artikel SEO AI', 'icon' => 'fas fa-pen-nib', 'href' => 'admin-seo.php'],
    ['label' => 'Publisher AI', 'icon' => 'fas fa-paper-plane', 'href' => 'admin-publisher.php'],
    ['label' => 'Sosmed Workflow', 'icon' => 'fas fa-route', 'href' => 'admin-sosmed-workflow.php'],
];

// Sinkronisasi Sesi & Cek Status Realtime Absensi Hari Ini
if ($user) {
    syncLegacySessions($user);
}
$today_str = date('Y-m-d');
$current_ustadz_id = $_SESSION['ustadz_id'] ?? ((!empty($user['ref_id'])) ? (int)$user['ref_id'] : (int)($user['id'] ?? 1));

$res_peg_status = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $current_ustadz_id AND DATE(waktu_absen) = '$today_str' AND jenis_absen = 'Pegawai' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen ASC");
$dash_pegawai_status = 'belum_absen';
if ($res_peg_status) {
    $num_p = $res_peg_status->num_rows;
    if ($num_p >= 2) {
        $dash_pegawai_status = 'selesai';
    } elseif ($num_p == 1) {
        $dash_pegawai_status = 'datang';
    }
}

$res_meng_status = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $current_ustadz_id AND DATE(waktu_absen) = '$today_str' AND jenis_absen = 'Mengajar' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen ASC");
$dash_mengajar_status = 'belum_absen';
if ($res_meng_status) {
    $num_m = $res_meng_status->num_rows;
    if ($num_m >= 2) {
        $dash_mengajar_status = 'selesai';
    } elseif ($num_m == 1) {
        $dash_mengajar_status = 'datang';
    }
}

// --- Handler Simpan / Update / Hapus Jurnal Mengajar dari Dashboard ---
$pesan_jurnal_sukses = "";
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "POST" && isset($_POST['action']) && $_POST['action'] === 'simpan_jurnal') {
    $j_id = !empty($_POST['jurnal_id']) ? (int)$_POST['jurnal_id'] : 0;
    $tanggal = $conn->real_escape_string($_POST['tanggal'] ?? date('Y-m-d'));
    $kelas = $conn->real_escape_string($_POST['kelas'] ?? '');
    $mata_pelajaran = $conn->real_escape_string($_POST['mata_pelajaran'] ?? '');
    $materi = $conn->real_escape_string($_POST['materi'] ?? '');
    $absensi_notes = $conn->real_escape_string($_POST['absensi_notes'] ?? '');

    if ($j_id > 0) {
        $sql = "UPDATE jurnal_mengajar SET tanggal='$tanggal', kelas='$kelas', mata_pelajaran='$mata_pelajaran', materi='$materi', absensi='$absensi_notes' WHERE id=$j_id AND ustadz_id=$current_ustadz_id";
        $conn->query($sql);
        header("Location: dashboard.php?sukses_jurnal=updated#section-jurnal");
        exit;
    } else {
        $sql = "INSERT INTO jurnal_mengajar (ustadz_id, tanggal, kelas, mata_pelajaran, materi, absensi) VALUES ($current_ustadz_id, '$tanggal', '$kelas', '$mata_pelajaran', '$materi', '$absensi_notes')";
        $conn->query($sql);
        header("Location: dashboard.php?sukses_jurnal=created#section-jurnal");
        exit;
    }
}

if (isset($_GET['hapus_jurnal_id'])) {
    $j_id = (int)$_GET['hapus_jurnal_id'];
    $conn->query("DELETE FROM jurnal_mengajar WHERE id = $j_id AND ustadz_id = $current_ustadz_id");
    header("Location: dashboard.php?sukses_jurnal=deleted#section-jurnal");
    exit;
}

$edit_jurnal_mode = false;
$jurnal_edit_data = null;
if (isset($_GET['edit_jurnal_id'])) {
    $edit_jurnal_mode = true;
    $j_id = (int)$_GET['edit_jurnal_id'];
    $res_edit = $conn->query("SELECT * FROM jurnal_mengajar WHERE id = $j_id AND ustadz_id = $current_ustadz_id");
    if ($res_edit && $res_edit->num_rows > 0) {
        $jurnal_edit_data = $res_edit->fetch_assoc();
    }
}

if (isset($_GET['sukses_jurnal'])) {
    if ($_GET['sukses_jurnal'] === 'created') $pesan_jurnal_sukses = "Jurnal mengajar baru berhasil disimpan!";
    elseif ($_GET['sukses_jurnal'] === 'updated') $pesan_jurnal_sukses = "Jurnal mengajar berhasil diperbarui!";
    elseif ($_GET['sukses_jurnal'] === 'deleted') $pesan_jurnal_sukses = "Jurnal mengajar berhasil dihapus!";
}

// Ambil Master Kelas & Mapel untuk Dropdown Form Jurnal
$daftar_kelas = [];
$res_kelas = $conn->query("SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kelas && $res_kelas->num_rows > 0) {
    while($rk = $res_kelas->fetch_assoc()) {
        $daftar_kelas[] = $rk['nama_kelas'];
    }
} else {
    $daftar_kelas = ['Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12', 'Kelas Rijal', 'Kelas Nisa'];
}

$daftar_mapel = [];
$res_mapel = $conn->query("SELECT nama_mapel FROM master_mapel WHERE status_aktif = 1 ORDER BY nama_mapel ASC");
if ($res_mapel && $res_mapel->num_rows > 0) {
    while($rm = $res_mapel->fetch_assoc()) {
        $daftar_mapel[] = $rm['nama_mapel'];
    }
} else {
    $daftar_mapel = ['Tahfidz Al-Qur\'an', 'Aqidah Akhlak', 'Fiqih', 'Hadits', 'Bahasa Arab', 'Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Bahasa Inggris', 'PKn'];
}

// Urutkan $operational_items sesuai preferensi Custom Drag-and-Drop Pengguna
$user_key = 'user_' . ($user['id'] ?? $user['username'] ?? 'default');
$user_custom_order = [];
$res_pref = $conn->query("SELECT menu_order_json FROM user_menu_preferences WHERE user_key = '" . $conn->real_escape_string($user_key) . "' LIMIT 1");
if ($res_pref && $row_pref = $res_pref->fetch_assoc()) {
    $user_custom_order = json_decode($row_pref['menu_order_json'], true) ?: [];
}

if (!empty($user_custom_order) && is_array($user_custom_order)) {
    $sorted_op = [];
    foreach ($user_custom_order as $k) {
        if (isset($operational_items[$k])) {
            $sorted_op[$k] = $operational_items[$k];
            unset($operational_items[$k]);
        }
    }
    // Sisipkan menu baru yang belum tersimpan di order
    foreach ($operational_items as $k => $item) {
        $sorted_op[$k] = $item;
    }
    $operational_items = $sorted_op;
}
$visible_items = $operational_items; // Untuk kompatibilitas referensi lama
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
    <!-- SortableJS untuk Drag and Drop mirip Launcher Android -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <!-- HTML5 QR Code Scanner untuk Scan Ruang Kelas Jurnal -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Gaya Animasi Drag & Drop Android Launcher */
        .sortable-ghost {
            opacity: 0.3 !important;
            transform: scale(0.92) !important;
            border-radius: 24px !important;
        }
        .sortable-chosen {
            transform: scale(1.08) !important;
            z-index: 50 !important;
        }
        .sortable-chosen .squircle-icon {
            box-shadow: 0 16px 32px -4px rgba(11, 132, 120, 0.4), 0 0 0 3px rgba(11, 132, 120, 0.35) !important;
        }
        .sortable-drag {
            opacity: 0.95 !important;
            transform: rotate(2.5deg) scale(1.1) !important;
        }
    </style>
</head>
<body class="bg-[#dcf3ee] min-h-screen text-slate-800 flex flex-col antialiased selection:bg-[#0b8478] selection:text-white">

    <?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true): ?>
    <div class="w-full bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-900 text-white px-4 py-2.5 text-xs shadow-2xl flex items-center justify-between border-b border-purple-400 z-50 sticky top-0 flex-shrink-0">
        <div class="flex items-center space-x-2">
            <span class="animate-pulse text-amber-300 font-extrabold text-sm"><i class="fas fa-user-secret"></i> MODE IMPERSONASI</span>
            <span class="hidden sm:inline text-purple-200">|</span>
            <span class="text-purple-100">Anda sedang mengakses sistem sebagai: <strong class="text-amber-200 underline font-bold"><?= htmlspecialchars($user['nama_lengkap'] ?? $_SESSION['ustadz_nama'] ?? '') ?></strong></span>
            <span class="bg-purple-800 text-purple-200 px-2 py-0.5 rounded text-[10px] font-mono hidden md:inline-block"><?= htmlspecialchars(implode(', ', $roles)) ?></span>
        </div>
        <a href="switch-back-admin.php" class="bg-amber-400 hover:bg-amber-300 text-purple-950 font-extrabold px-3.5 py-1 rounded-full text-[11px] shadow transition flex items-center gap-1.5 whitespace-nowrap">
            <i class="fas fa-undo"></i> Kembali ke Super Admin
        </a>
    </div>
    <?php endif; ?>

    <!-- ROOT CONTAINER (FLEX-ROW PADA DESKTOP UNTUK SIDEBAR + MAIN CONTENT) -->
    <div class="flex-1 flex flex-col md:flex-row w-full min-h-screen">

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
                    <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($user['nama_lengkap'] ?? $_SESSION['app_user_nama'] ?? 'Admin') ?></h4>
                    <p class="text-[10px] text-teal-200 truncate mt-0.5">@<?= htmlspecialchars($user['username'] ?? $_SESSION['app_username'] ?? 'admin') ?></p>
                    <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                        <?= htmlspecialchars($user['roles'] ?? $_SESSION['app_user_roles'] ?? 'super_admin') ?>
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
            <?php if ($is_yayasan_pengurus): ?>
            <a href="yayasan2/manajemen-menu.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-amber-400/20 text-amber-200 hover:bg-amber-400/30 hover:text-white font-bold transition border border-amber-300/30 mt-1">
                <i class="fas fa-sliders w-4 text-center text-amber-300"></i>
                <span>Manajemen Menu</span>
            </a>
            <?php endif; ?>

            <!-- SIMULASI ROLE WIDGET DI SIDEBAR (KHUSUS KETUA YAYASAN / SUPER ADMIN) -->
            <?php if ($can_view_role_simulation): ?>
            <div class="pt-4 mt-4 border-t border-teal-700/60">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-teal-200">Simulasi Role</span>
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
                    <a href="dashboard.php?toggle_role=orangtua" class="p-1.5 rounded-lg text-center font-bold transition <?= (in_array('orangtua', $active_views) || in_array('walisantri', $active_views)) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Orangtua
                    </a>
                    <a href="dashboard.php?toggle_role=web" class="p-1.5 rounded-lg text-center font-bold transition <?= in_array('web', $active_views) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Web
                    </a>
                    <a href="dashboard.php?toggle_role=marketing" class="col-span-2 p-1.5 rounded-lg text-center font-bold transition <?= in_array('marketing', $active_views) ? 'bg-white text-[#0b8478]' : 'bg-teal-800/60 text-white/90 hover:bg-teal-700' ?>">
                        Marketing
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
            <?php if (isset($_GET['sukses']) && !empty($_GET['sukses'])): ?>
            <div class="max-w-4xl mx-auto mb-4">
                <div class="bg-white/95 text-teal-950 px-4 py-2.5 rounded-2xl shadow-lg flex items-center justify-between text-xs font-bold border border-teal-200">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-circle-check text-emerald-600 text-sm"></i>
                        <?= htmlspecialchars($_GET['sukses']) ?>
                    </span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 cursor-pointer ml-2"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <?php endif; ?>
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

            <!-- MULTI-ROLE 5 KOLOM X 4 BARIS DIRECT CHECKBOX SIMULATION (SPOILER / COLLAPSIBLE - KHUSUS KETUA YAYASAN / SUPER ADMIN) -->
            <?php if ($can_view_role_simulation): ?>
            <div class="max-w-4xl mx-auto mt-4 pt-3 border-t border-teal-600/60" id="simulasi-role-container">
                <!-- SPOILER TOGGLE HEADER -->
                <div class="flex items-center justify-between">
                    <button type="button" onclick="toggleSimulasiSpoiler()" class="flex items-center gap-2 group cursor-pointer focus:outline-none text-left py-1">
                        <span class="w-5 h-5 rounded-md bg-teal-800/80 group-hover:bg-teal-700 flex items-center justify-center text-teal-200 transition-colors shadow-xs">
                            <i id="simulasi-chevron" class="fas fa-chevron-down text-[10px] transition-transform duration-200"></i>
                        </span>
                        <span class="text-[11px] font-black uppercase tracking-wider text-teal-100 group-hover:text-white flex items-center gap-1.5 transition-colors">
                            <i class="fas fa-sliders text-[10px]"></i> Filter Simulasi Multi-Role (18 Role)
                        </span>
                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-teal-800/90 text-teal-200 border border-teal-600/40">
                            <?= $is_all_view ? 'Semua Role' : ($is_none_view ? 'Kosong' : count($active_views).' Role Aktif') ?>
                        </span>
                    </button>

                    <button type="button" onclick="toggleSimulasiSpoiler()" id="simulasi-toggle-btn" class="px-2.5 py-1 rounded-lg bg-teal-800/70 hover:bg-teal-700 text-[10px] font-bold text-teal-100 hover:text-white cursor-pointer transition flex items-center gap-1.5 shadow-xs">
                        <i class="fas fa-eye-slash text-[9px]"></i>
                        <span id="simulasi-toggle-text">Sembunyikan</span>
                    </button>
                </div>

                <!-- SPOILER CONTENT: 5 KOLOM X 4 BARIS MATRIX CHECKBOX -->
                <div id="simulasi-content" class="mt-2.5 transition-all duration-200">
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
            </div>
            <?php endif; ?>
        </div>

        <!-- ========================================================= -->
        <!-- 2. MAIN BINGKAI: KARTU PUTIH MELEKUK INDAH SQUIRCLE GRID  -->
        <!-- ========================================================= -->
        <main class="flex-1 px-4 sm:px-8 pt-0 pb-24 md:pb-12 w-full max-w-4xl mx-auto -mt-14 z-20">
            
            <?php if ($show_yayasan_frame && !empty($yayasan_items)): ?>
            <!-- ========================================================= -->
            <!-- FRAME 1: RUANG EKSEKUTIF YAYASAN (KHUSUS PENGURUS YAYASAN)-->
            <!-- (HANYA BISA DIAKSES & DILIHAT OLEH PENGURUS YAYASAN)      -->
            <!-- ========================================================= -->
            <div class="bg-white rounded-[36px] md:rounded-[40px] p-6 sm:p-10 shadow-xl shadow-amber-950/5 border-2 border-amber-300/80 mb-6 sm:mb-8 relative overflow-hidden">
                <!-- Watermark Background Decorative Icon -->
                <div class="absolute -right-6 -bottom-6 text-amber-100/30 pointer-events-none text-9xl">
                    <i class="fas fa-landmark"></i>
                </div>

                <!-- TOP HEADER FRAME RUANG YAYASAN -->
                <div class="flex flex-wrap items-center justify-between gap-3 mb-6 pb-4 border-b border-amber-100/80 relative z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 text-white flex items-center justify-center text-xl shadow-md shadow-amber-500/25 flex-shrink-0">
                            <i class="fas fa-landmark"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base sm:text-lg font-black text-slate-800 tracking-tight">Ruang Eksekutif Yayasan</h2>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300 flex items-center gap-1">
                                    <i class="fas fa-crown text-[9px] text-amber-600"></i> Khusus Pimpinan
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500 font-medium">Ketua Yayasan • Sekretaris Yayasan • Bendahara Yayasan</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="yayasan2/manajemen-menu.php" class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold text-[11px] flex items-center gap-1.5 shadow-sm shadow-amber-500/30 hover:scale-105 transition-all">
                            <i class="fas fa-sliders text-xs"></i> Atur Hak Akses Menu
                        </a>
                        <span class="text-[11px] font-bold px-3 py-1.5 rounded-xl bg-amber-50 text-amber-800 border border-amber-200">
                            <?= count($yayasan_items) ?> Menu
                        </span>
                    </div>
                </div>

                <!-- GRID CONTAINER RUANG YAYASAN -->
                <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-y-6 sm:gap-y-8 gap-x-2 sm:gap-x-6 items-start justify-items-center relative z-10">
                    <?php foreach ($yayasan_items as $y_key => $y_item): 
                        $is_key_menu = ($y_key === 'yayasan_menu');
                    ?>
                    <div data-id="<?= htmlspecialchars($y_key) ?>" class="flex flex-col items-center group w-full text-center tap-highlight-transparent select-none transition-transform duration-200">
                        <a href="<?= htmlspecialchars($y_item['href']) ?>" class="flex flex-col items-center w-full focus:outline-none" draggable="false">
                            <!-- Squircle Box Button Khusus Yayasan (Deep Teal Gradient + Gold Accent) -->
                            <div class="relative squircle-icon w-14 h-14 sm:w-16 sm:h-16 rounded-[20px] sm:rounded-[22px] <?= $is_key_menu ? 'bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 text-white ring-4 ring-amber-400/30 shadow-lg shadow-amber-600/30' : 'bg-gradient-to-br from-[#0b8478] to-[#064e46] text-amber-300 group-hover:text-white group-hover:from-[#097368] group-hover:to-[#043d37] border border-amber-300/30 shadow-md shadow-teal-900/15' ?> flex items-center justify-center text-xl sm:text-2xl group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                                <i class="<?= $is_key_menu ? 'fas fa-sliders' : $y_item['icon'] ?>"></i>
                                <?php if ($is_key_menu): ?>
                                <span class="absolute -top-1.5 -right-1.5 bg-rose-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-tighter shadow-sm">KUNCI</span>
                                <?php endif; ?>
                            </div>

                            <!-- Label Menu -->
                            <span class="text-[11px] sm:text-xs font-bold <?= $is_key_menu ? 'text-amber-800 font-extrabold' : 'text-slate-800' ?> mt-2 tracking-tight group-hover:text-[#0b8478] transition-colors leading-tight line-clamp-2 max-w-[85px] text-center">
                                <?= htmlspecialchars($y_item['label']) ?>
                            </span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- FRAME 2: TATA LETAK MENU OPERASIONAL (DRAGGABLE)          -->
            <!-- ========================================================= -->
            <div class="bg-white rounded-[36px] md:rounded-[40px] p-6 sm:p-10 shadow-xl shadow-teal-950/10 border border-teal-50">
                
                <!-- TOP HEADER DALAM KARTU DENGAN HINT & RESET -->
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-[11px] font-bold text-slate-500 flex items-center gap-1.5">
                            <i class="fas fa-grip-vertical text-[#0b8478]"></i> Tata Letak Menu Operasional
                        </span>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">
                            <?= count($visible_items) ?> Menu
                        </span>
                    </div>
                    <button type="button" onclick="resetMenuOrder()" class="text-[10px] font-bold text-teal-700 hover:text-[#086a60] hover:underline flex items-center gap-1 cursor-pointer transition">
                        <i class="fas fa-rotate-left text-[9px]"></i> Reset Posisi
                    </button>
                </div>

                <!-- DRAGGABLE GRID CONTAINER -->
                <div id="grid-menu-container" class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-y-6 sm:gap-y-8 gap-x-2 sm:gap-x-6 items-start justify-items-center">
                    
                    <?php foreach ($visible_items as $key => $item): ?>
                    <div data-id="<?= htmlspecialchars($key) ?>" class="grid-menu-card flex flex-col items-center group cursor-grab active:cursor-grabbing w-full text-center tap-highlight-transparent select-none transition-transform duration-200">
                        <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center w-full focus:outline-none" draggable="false">
                            <!-- Squircle Box Button (#0b8478) -->
                            <div class="squircle-icon w-14 h-14 sm:w-16 sm:h-16 rounded-[20px] sm:rounded-[22px] bg-[#0b8478] group-hover:bg-[#086a60] text-white flex items-center justify-center text-xl sm:text-2xl shadow-md shadow-teal-900/15 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                                <i class="<?= $item['icon'] ?>"></i>
                            </div>

                            <!-- 1 Kata Keterangan Menu -->
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0b8478] transition-colors leading-tight line-clamp-1">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>
                        </a>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <?php if ($can_see_web): ?>
            <!-- ========================================================= -->
            <!-- FRAME KHUSUS 1: RUANG WEB (PENGATURAN WEBSITE)            -->
            <!-- (Akses: Yayasan, Super Admin, dan Role Web)              -->
            <!-- ========================================================= -->
            <div class="bg-white rounded-[32px] md:rounded-[36px] p-6 sm:p-8 shadow-xl shadow-teal-950/5 border border-teal-50 mt-6 sm:mt-7 transition-all duration-200">
                <!-- HEADER FRAME RUANG WEB -->
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-teal-100/70">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-base font-black shadow-inner">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-sm sm:text-base tracking-tight flex items-center gap-2">
                                Ruang Web
                                <span class="text-[9px] px-2 py-0.5 rounded-full font-extrabold bg-teal-50 text-[#0b8478] border border-teal-200 uppercase tracking-wider">Pengaturan Website</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 font-medium">Kelola landing page, profil lembaga, brosur biaya, dan konten publik</p>
                        </div>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[10px] font-bold text-teal-800 bg-teal-50/90 px-3 py-1 rounded-xl border border-teal-200/80 shadow-xs">
                        <i class="fas fa-shield-halved text-[#0b8478]"></i> Role: Yayasan & Web
                    </span>
                </div>

                <!-- GRID CARD RUANG WEB -->
                <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-y-6 sm:gap-y-8 gap-x-2 sm:gap-x-6 items-start justify-items-center">
                    <?php foreach ($ruang_web_cards as $card): ?>
                    <div class="flex flex-col items-center group w-full text-center tap-highlight-transparent select-none transition-transform duration-200">
                        <a href="<?= htmlspecialchars($card['href']) ?>" class="flex flex-col items-center w-full focus:outline-none">
                            <div class="squircle-icon w-14 h-14 sm:w-16 sm:h-16 rounded-[20px] sm:rounded-[22px] bg-gradient-to-br from-[#0b8478] to-[#086a60] group-hover:from-[#086a60] group-hover:to-[#065049] text-white flex items-center justify-center text-xl sm:text-2xl shadow-md shadow-teal-900/15 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                                <i class="<?= htmlspecialchars($card['icon']) ?>"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-[#0b8478] transition-colors leading-tight line-clamp-1">
                                <?= htmlspecialchars($card['label']) ?>
                            </span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($can_see_marketing): ?>
            <!-- ========================================================= -->
            <!-- FRAME KHUSUS 2: RUANG MARKETING (AI & PROSPEK SPMB)       -->
            <!-- (Akses: Yayasan, Super Admin, dan Role Marketing)         -->
            <!-- ========================================================= -->
            <div class="bg-white rounded-[32px] md:rounded-[36px] p-6 sm:p-8 shadow-xl shadow-indigo-950/5 border border-indigo-50 mt-6 sm:mt-7 transition-all duration-200">
                <!-- HEADER FRAME RUANG MARKETING -->
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-indigo-100/70">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-base font-black shadow-inner">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <h3 class="font-black text-slate-800 text-sm sm:text-base tracking-tight flex items-center gap-2">
                                Ruang Marketing
                                <span class="text-[9px] px-2 py-0.5 rounded-full font-extrabold bg-indigo-50 text-indigo-600 border border-indigo-200 uppercase tracking-wider">AI & Prospek</span>
                            </h3>
                            <p class="text-[11px] text-slate-400 font-medium">Pusat kendali AI, otomatisasi leads SPMB, analisis pasar, dan sosmed</p>
                        </div>
                    </div>
                    <span class="hidden sm:inline-flex items-center gap-1.5 text-[10px] font-bold text-indigo-800 bg-indigo-50/90 px-3 py-1 rounded-xl border border-indigo-200/80 shadow-xs">
                        <i class="fas fa-robot text-indigo-600"></i> Role: Yayasan & Marketing
                    </span>
                </div>

                <!-- GRID CARD RUANG MARKETING -->
                <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-4 lg:grid-cols-4 gap-y-6 sm:gap-y-8 gap-x-2 sm:gap-x-6 items-start justify-items-center">
                    <?php foreach ($ruang_marketing_cards as $card): ?>
                    <div class="flex flex-col items-center group w-full text-center tap-highlight-transparent select-none transition-transform duration-200">
                        <a href="<?= htmlspecialchars($card['href']) ?>" class="flex flex-col items-center w-full focus:outline-none">
                            <div class="squircle-icon w-14 h-14 sm:w-16 sm:h-16 rounded-[20px] sm:rounded-[22px] bg-gradient-to-br from-indigo-600 to-indigo-800 group-hover:from-indigo-700 group-hover:to-indigo-900 text-white flex items-center justify-center text-xl sm:text-2xl shadow-md shadow-indigo-900/20 group-hover:scale-105 group-active:scale-95 transition-all duration-200">
                                <i class="<?= htmlspecialchars($card['icon']) ?>"></i>
                            </div>
                            <span class="text-[11px] sm:text-xs font-bold text-slate-800 mt-2 tracking-tight group-hover:text-indigo-600 transition-colors leading-tight line-clamp-1">
                                <?= htmlspecialchars($card['label']) ?>
                            </span>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($show_absensi_pegawai || $show_absensi_mengajar): ?>
            <!-- ========================================================= -->
            <!-- TOMBOL CEPAT ABSENSI PEGAWAI & MENGAJAR (LANGSUNG ABSEN)  -->
            <!-- ========================================================= -->
            <div class="mt-4 sm:mt-5 grid grid-cols-1 <?= ($show_absensi_pegawai && $show_absensi_mengajar) ? 'sm:grid-cols-2' : '' ?> gap-3 sm:gap-4">
                <?php if ($show_absensi_pegawai): ?>
                <!-- Tombol 1: Absensi Kehadiran / Kepulangan Pegawai -->
                <div class="group relative overflow-hidden bg-gradient-to-br from-[#0b8478] to-[#086a60] text-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-lg shadow-teal-950/15 border border-teal-400/20 transition-all duration-200 hover:shadow-xl flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3.5 sm:gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/20 flex items-center justify-center text-xl sm:text-2xl text-white flex-shrink-0">
                                <i class="fas fa-fingerprint"></i>
                            </div>
                            <div class="text-left">
                                <div class="text-[10px] font-bold text-teal-200 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>Presensi Harian</span>
                                    <span class="w-2 h-2 rounded-full <?= $dash_pegawai_status === 'selesai' ? 'bg-emerald-300' : ($dash_pegawai_status === 'datang' ? 'bg-amber-300 animate-ping' : 'bg-white/60') ?>"></span>
                                </div>
                                <h3 class="font-extrabold text-sm sm:text-base text-white tracking-tight leading-snug">
                                    <?php if ($dash_pegawai_status === 'belum_absen'): ?>
                                        Absensi Kedatangan (Masuk)
                                    <?php elseif ($dash_pegawai_status === 'datang'): ?>
                                        Absensi Kepulangan (Pulang)
                                    <?php else: ?>
                                        Absensi Pegawai Selesai ✓
                                    <?php endif; ?>
                                </h3>
                                <p class="text-[11px] text-teal-100/80 font-medium hidden sm:block mt-0.5">
                                    <?php if ($dash_pegawai_status === 'belum_absen'): ?>
                                        Langsung catat waktu check-in hadir via GPS
                                    <?php elseif ($dash_pegawai_status === 'datang'): ?>
                                        Sudah Masuk. Klik untuk catat waktu pulang
                                    <?php else: ?>
                                        Kehadiran & kepulangan hari ini telah tuntas
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="admin-absensi-pegawai.php?tipe=pegawai" title="Buka Halaman Lengkap Rekap & Jurnal" class="p-2 rounded-xl bg-white/10 hover:bg-white/25 text-teal-100 hover:text-white transition flex items-center justify-center flex-shrink-0" onclick="event.stopPropagation();">
                            <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    </div>

                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between">
                        <span class="text-[11px] text-teal-200/90 font-medium">
                            Status: <strong class="text-white"><?= $dash_pegawai_status === 'selesai' ? 'Lengkap (Pulang)' : ($dash_pegawai_status === 'datang' ? 'Sudah Masuk' : 'Belum Absen') ?></strong>
                        </span>
                        <button type="button" 
                                id="btn-dash-absen-pegawai" 
                                onclick="doDirectAbsensi('Pegawai', this)" 
                                <?= $dash_pegawai_status === 'selesai' ? 'disabled' : '' ?>
                                class="py-2 px-4 rounded-xl font-black text-xs transition-all duration-200 flex items-center gap-2 <?= $dash_pegawai_status === 'selesai' ? 'bg-white/20 text-white/70 cursor-not-allowed' : ($dash_pegawai_status === 'datang' ? 'bg-amber-400 hover:bg-amber-300 text-teal-950 shadow-md active:scale-95' : 'bg-white hover:bg-teal-50 text-[#086a60] shadow-md active:scale-95') ?>">
                            <i class="fas <?= $dash_pegawai_status === 'selesai' ? 'fa-check' : ($dash_pegawai_status === 'datang' ? 'fa-sign-out-alt' : 'fa-location-dot') ?>"></i>
                            <span><?= $dash_pegawai_status === 'selesai' ? 'Tuntas' : ($dash_pegawai_status === 'datang' ? 'Klik Absen Pulang' : 'Klik Absen Masuk') ?></span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($show_absensi_mengajar): ?>
                <!-- Tombol 2: Absensi Mulai Mengajar / Selesai KBM -->
                <div class="group relative overflow-hidden bg-gradient-to-br from-[#1b5e76] to-[#0e4457] text-white p-4 sm:p-5 rounded-2xl sm:rounded-3xl shadow-lg shadow-cyan-950/15 border border-cyan-400/20 transition-all duration-200 hover:shadow-xl flex flex-col justify-between">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3.5 sm:gap-4">
                            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/15 backdrop-blur-sm border border-white/20 flex items-center justify-center text-xl sm:text-2xl text-white flex-shrink-0">
                                <i class="fas fa-chalkboard-user"></i>
                            </div>
                            <div class="text-left">
                                <div class="text-[10px] font-bold text-cyan-200 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>KBM & Jurnal</span>
                                    <span class="w-2 h-2 rounded-full <?= $dash_mengajar_status === 'selesai' ? 'bg-emerald-300' : ($dash_mengajar_status === 'datang' ? 'bg-cyan-300 animate-ping' : 'bg-white/60') ?>"></span>
                                </div>
                                <h3 class="font-extrabold text-sm sm:text-base text-white tracking-tight leading-snug">
                                    <?php if ($dash_mengajar_status === 'belum_absen'): ?>
                                        Mulai Mengajar (Masuk KBM)
                                    <?php elseif ($dash_mengajar_status === 'datang'): ?>
                                        Selesai Mengajar (Akhiri KBM)
                                    <?php else: ?>
                                        Absensi Mengajar Selesai ✓
                                    <?php endif; ?>
                                </h3>
                                <p class="text-[11px] text-cyan-100/80 font-medium hidden sm:block mt-0.5">
                                    <?php if ($dash_mengajar_status === 'belum_absen'): ?>
                                        Langsung catat waktu mulai pelajaran via GPS
                                    <?php elseif ($dash_mengajar_status === 'datang'): ?>
                                        Sedang KBM. Klik untuk akhiri jam mengajar
                                    <?php else: ?>
                                        Selesai seluruh jam mengajar hari ini
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <a href="admin-absensi-pegawai.php?tipe=mengajar" title="Buka Halaman Lengkap Rekap & Jurnal" class="p-2 rounded-xl bg-white/10 hover:bg-white/25 text-cyan-100 hover:text-white transition flex items-center justify-center flex-shrink-0" onclick="event.stopPropagation();">
                            <i class="fas fa-arrow-up-right-from-square text-xs"></i>
                        </a>
                    </div>

                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between">
                        <span class="text-[11px] text-cyan-200/90 font-medium">
                            Status: <strong class="text-white"><?= $dash_mengajar_status === 'selesai' ? 'Selesai Mengajar' : ($dash_mengajar_status === 'datang' ? 'Sedang KBM' : 'Belum Mulai') ?></strong>
                        </span>
                        <button type="button" 
                                id="btn-dash-absen-mengajar" 
                                onclick="doDirectAbsensi('Mengajar', this)" 
                                <?= $dash_mengajar_status === 'selesai' ? 'disabled' : '' ?>
                                class="py-2 px-4 rounded-xl font-black text-xs transition-all duration-200 flex items-center gap-2 <?= $dash_mengajar_status === 'selesai' ? 'bg-white/20 text-white/70 cursor-not-allowed' : ($dash_mengajar_status === 'datang' ? 'bg-cyan-300 hover:bg-cyan-200 text-cyan-950 shadow-md active:scale-95' : 'bg-white hover:bg-cyan-50 text-[#0e4457] shadow-md active:scale-95') ?>">
                            <i class="fas <?= $dash_mengajar_status === 'selesai' ? 'fa-check' : ($dash_mengajar_status === 'datang' ? 'fa-door-closed' : 'fa-location-dot') ?>"></i>
                            <span><?= $dash_mengajar_status === 'selesai' ? 'Tuntas' : ($dash_mengajar_status === 'datang' ? 'Selesai Mengajar' : 'Mulai Mengajar') ?></span>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($show_jurnal_mengajar): ?>
            <!-- ========================================================= -->
            <!-- SECTION JURNAL MENGAJAR (DI BAWAH TOMBOL ABSENSI)         -->
            <!-- ========================================================= -->
            <div id="section-jurnal" class="mt-5 sm:mt-6 space-y-5 text-left">
                
                <?php if (!empty($pesan_jurnal_sukses)): ?>
                <!-- Notifikasi Pesan Sukses Jurnal -->
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 px-4 py-3 rounded-2xl shadow-sm flex items-center gap-2.5 text-xs">
                    <i class="fas fa-check-circle text-emerald-600 text-base flex-shrink-0"></i>
                    <span class="font-medium"><?= htmlspecialchars($pesan_jurnal_sukses) ?></span>
                </div>
                <?php endif; ?>

                <!-- KARTU 1: FORM INPUT / EDIT JURNAL MENGAJAR -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-sm font-bold shadow-inner">
                                <i class="fas <?= $edit_jurnal_mode ? 'fa-edit' : 'fa-pen-to-square' ?>"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-sm tracking-tight">
                                    <?= $edit_jurnal_mode ? 'Edit Jurnal Mengajar' : 'Form Jurnal Mengajar KBM' ?>
                                </h3>
                                <p class="text-[11px] text-slate-500 font-medium">
                                    <?= $edit_jurnal_mode ? 'Perbarui data jurnal pembelajaran yang dipilih' : 'Catat jurnal pelajaran, materi pokok, dan kehadiran santri hari ini' ?>
                                </p>
                            </div>
                        </div>
                        <?php if ($edit_jurnal_mode): ?>
                            <a href="dashboard.php#section-jurnal" class="text-xs text-rose-600 hover:text-rose-700 font-bold flex items-center gap-1 bg-rose-50 px-3 py-1.5 rounded-xl transition hover:bg-rose-100">
                                <i class="fas fa-times"></i> Batal Edit
                            </a>
                        <?php endif; ?>
                    </div>

                    <form action="dashboard.php#section-jurnal" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="simpan_jurnal">
                        <input type="hidden" name="jurnal_id" value="<?= $edit_jurnal_mode ? (int)$jurnal_edit_data['id'] : '' ?>">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal KBM</label>
                                <input type="date" name="tanggal" value="<?= $edit_jurnal_mode ? htmlspecialchars($jurnal_edit_data['tanggal']) : date('Y-m-d') ?>" required class="w-full px-3.5 py-2.5 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none transition">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kelas / Rombel</label>
                                <div class="flex gap-1.5">
                                    <select name="kelas" id="input-kelas-jurnal" required class="w-full px-3.5 py-2.5 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none transition">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php
                                        $kelas_tersimpan = $edit_jurnal_mode ? $jurnal_edit_data['kelas'] : '';
                                        $ada_di_list = false;
                                        foreach ($daftar_kelas as $nama_kelas) {
                                            $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                            if ($sel) $ada_di_list = true;
                                            echo "<option value=\"".htmlspecialchars($nama_kelas)."\" $sel>".htmlspecialchars($nama_kelas)."</option>";
                                        }
                                        if ($edit_jurnal_mode && !$ada_di_list && !empty($kelas_tersimpan)) {
                                            echo "<option value=\"".htmlspecialchars($kelas_tersimpan)."\" selected>".htmlspecialchars($kelas_tersimpan)." (Data Lama)</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="button" onclick="bukaScannerKelas()" class="bg-cyan-50 text-cyan-700 hover:bg-cyan-100 px-3 py-2.5 rounded-xl border border-cyan-200 transition flex items-center justify-center flex-shrink-0" title="Scan QR Stiker Kelas">
                                        <i class="fas fa-qrcode text-sm"></i>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Mata Pelajaran</label>
                                <select name="mata_pelajaran" required class="w-full px-3.5 py-2.5 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none transition">
                                    <option value="">-- Pilih Mata Pelajaran --</option>
                                    <?php
                                    $mapel_tersimpan = $edit_jurnal_mode ? $jurnal_edit_data['mata_pelajaran'] : '';
                                    $mapel_ada = false;
                                    foreach ($daftar_mapel as $nama_mapel) {
                                        $sel = ($mapel_tersimpan == $nama_mapel) ? 'selected' : '';
                                        if ($sel) $mapel_ada = true;
                                        echo "<option value=\"".htmlspecialchars($nama_mapel)."\" $sel>".htmlspecialchars($nama_mapel)."</option>";
                                    }
                                    if ($edit_jurnal_mode && !$mapel_ada && !empty($mapel_tersimpan)) {
                                        echo "<option value=\"".htmlspecialchars($mapel_tersimpan)."\" selected>".htmlspecialchars($mapel_tersimpan)." (Data Lama)</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Materi / Bahasan Pembelajaran</label>
                                <textarea name="materi" rows="3" required class="w-full px-3.5 py-2.5 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none transition" placeholder="Ringkasan materi atau capaian KBM hari ini..."><?= $edit_jurnal_mode ? htmlspecialchars($jurnal_edit_data['materi']) : '' ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Catatan Absensi Santri / Kendala</label>
                                <textarea name="absensi_notes" rows="3" class="w-full px-3.5 py-2.5 bg-slate-50/80 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:ring-2 focus:ring-cyan-500 focus:outline-none transition" placeholder="Contoh: Ahmad (Sakit), Faris (Izin), kondisi kelas tertib kondusif..."><?= $edit_jurnal_mode ? htmlspecialchars($jurnal_edit_data['absensi']) : '' ?></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2.5 pt-1">
                            <?php if ($edit_jurnal_mode): ?>
                                <a href="dashboard.php#section-jurnal" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-xl text-xs transition">
                                    Batal
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 text-white font-black px-5 py-2.5 rounded-xl text-xs shadow-md shadow-teal-900/10 hover:shadow-lg transition flex items-center gap-2 active:scale-95">
                                <i class="fas fa-floppy-disk"></i>
                                <span><?= $edit_jurnal_mode ? 'Simpan Perubahan' : 'Simpan Jurnal KBM' ?></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- KARTU 2: TABEL RIWAYAT JURNAL MENGAJAR -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:shadow-md">
                    <div class="flex items-center justify-between pb-3.5 mb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-2xl bg-teal-50 text-teal-700 flex items-center justify-center text-sm font-bold shadow-inner">
                                <i class="fas fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-800 text-sm tracking-tight">Riwayat Jurnal Mengajar</h3>
                                <p class="text-[11px] text-slate-500 font-medium">Daftar jurnal KBM yang pernah Anda inputkan sebelumnya</p>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-2xl border border-slate-100">
                        <table class="min-w-full divide-y divide-slate-100 text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 font-bold">
                                    <th class="px-3.5 py-3 text-left">Tanggal</th>
                                    <th class="px-3.5 py-3 text-left">Kelas & Mapel</th>
                                    <th class="px-3.5 py-3 text-left">Materi KBM</th>
                                    <th class="px-3.5 py-3 text-left">Absensi / Catatan</th>
                                    <th class="px-3.5 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php
                                $res_jurnal = $conn->query("SELECT * FROM jurnal_mengajar WHERE ustadz_id = $current_ustadz_id ORDER BY tanggal DESC, id DESC LIMIT 15");
                                if ($res_jurnal && $res_jurnal->num_rows > 0):
                                    while ($rj = $res_jurnal->fetch_assoc()):
                                ?>
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-3.5 py-3 text-slate-700 font-medium whitespace-nowrap">
                                        <?= date('d M Y', strtotime($rj['tanggal'])) ?>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <span class="font-bold text-teal-800 block"><?= htmlspecialchars($rj['kelas']) ?></span>
                                        <span class="text-slate-500 text-[10px] font-semibold"><?= htmlspecialchars($rj['mata_pelajaran']) ?></span>
                                    </td>
                                    <td class="px-3.5 py-3 text-slate-600 font-medium max-w-xs truncate" title="<?= htmlspecialchars($rj['materi']) ?>">
                                        <?= htmlspecialchars($rj['materi']) ?>
                                    </td>
                                    <td class="px-3.5 py-3 text-slate-600 font-medium max-w-xs truncate" title="<?= htmlspecialchars($rj['absensi'] ?? '') ?>">
                                        <?= empty($rj['absensi']) ? '<span class="text-slate-400 italic">Nihil</span>' : htmlspecialchars($rj['absensi']) ?>
                                    </td>
                                    <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                        <a href="dashboard.php?edit_jurnal_id=<?= $rj['id'] ?>#section-jurnal" class="text-blue-600 hover:text-blue-800 p-1.5 inline-block transition" title="Edit Jurnal">
                                            <i class="fas fa-edit text-xs"></i>
                                        </a>
                                        <a href="dashboard.php?hapus_jurnal_id=<?= $rj['id'] ?>#section-jurnal" onclick="return confirm('Hapus jurnal mengajar ini?')" class="text-rose-600 hover:text-rose-800 p-1.5 inline-block transition" title="Hapus Jurnal">
                                            <i class="fas fa-trash text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-slate-400 italic">
                                        Belum ada riwayat jurnal mengajar yang tersimpan.
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <?php endif; ?>

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
    </div>

    <!-- FLOATING TOAST NOTIFICATION (ANDROID-LIKE) -->
    <div id="saveToast" class="fixed bottom-20 md:bottom-8 left-1/2 -translate-x-1/2 z-50 bg-slate-900/90 text-white backdrop-blur-md px-4 py-2.5 rounded-full shadow-2xl flex items-center gap-2.5 text-xs font-semibold border border-teal-500/30 transition-all duration-300 opacity-0 pointer-events-none translate-y-4">
        <div class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></div>
        <span id="toastMsg">Tata letak menu tersimpan ✨</span>
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
                        <?= strtoupper(substr($user['nama_lengkap'] ?? $_SESSION['app_user_nama'] ?? 'A', 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h3 class="font-black text-base text-slate-900 leading-tight"><?= htmlspecialchars($user['nama_lengkap'] ?? $_SESSION['app_user_nama'] ?? 'Admin') ?></h3>
                <p class="text-xs text-slate-400 mt-0.5">@<?= htmlspecialchars($user['username'] ?? $_SESSION['app_username'] ?? 'admin') ?></p>
                <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[10px] font-black bg-teal-50 text-[#0d8276] border border-teal-200">
                    <i class="fas fa-id-badge"></i> Role: <?= htmlspecialchars($user['roles'] ?? $_SESSION['app_user_roles'] ?? 'super_admin') ?>
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

        // Spoiler toggle untuk Filter Simulasi Multi-Role
        function toggleSimulasiSpoiler() {
            const content = document.getElementById('simulasi-content');
            const chevron = document.getElementById('simulasi-chevron');
            const btn = document.getElementById('simulasi-toggle-btn');
            if (!content) return;
            
            const isHidden = content.classList.contains('hidden');
            if (isHidden) {
                content.classList.remove('hidden');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                if (btn) btn.innerHTML = '<i class="fas fa-eye-slash text-[9px]"></i> <span id="simulasi-toggle-text">Sembunyikan</span>';
                localStorage.setItem('sadigs_simulasi_hidden', 'false');
            } else {
                content.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(-90deg)';
                if (btn) btn.innerHTML = '<i class="fas fa-eye text-[9px]"></i> <span id="simulasi-toggle-text">Tampilkan</span>';
                localStorage.setItem('sadigs_simulasi_hidden', 'true');
            }
        }

        // Restore status spoiler simulasi dari localStorage saat halaman dibuka
        document.addEventListener('DOMContentLoaded', function() {
            if (localStorage.getItem('sadigs_simulasi_hidden') === 'true') {
                const content = document.getElementById('simulasi-content');
                const chevron = document.getElementById('simulasi-chevron');
                const btn = document.getElementById('simulasi-toggle-btn');
                if (content) content.classList.add('hidden');
                if (chevron) chevron.style.transform = 'rotate(-90deg)';
                if (btn) btn.innerHTML = '<i class="fas fa-eye text-[9px]"></i> <span id="simulasi-toggle-text">Tampilkan</span>';
            }
        });

        // =========================================================
        // INISIALISASI SORTABLEJS DRAG & DROP MIRIP ANDROID LAUNCHER
        // =========================================================
        document.addEventListener('DOMContentLoaded', function() {
            const gridContainer = document.getElementById('grid-menu-container');
            if (!gridContainer) return;

            let toastTimer = null;
            function showToast(msg) {
                const toast = document.getElementById('saveToast');
                const text = document.getElementById('toastMsg');
                if (!toast) return;
                if (text) text.innerText = msg;
                toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
                toast.classList.add('opacity-100', 'translate-y-0');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => {
                    toast.classList.remove('opacity-100', 'translate-y-0');
                    toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
                }, 2200);
            }

            new Sortable(gridContainer, {
                animation: 250, // Reordering animation speed (ms)
                delay: 100, // 100ms delay on touch to prevent conflicts with normal clicks
                delayOnTouchOnly: true,
                touchStartThreshold: 5,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onEnd: function(evt) {
                    if (evt.oldIndex === evt.newIndex) return;

                    const cards = Array.from(gridContainer.querySelectorAll('.grid-menu-card'));
                    const order = cards.map(c => c.getAttribute('data-id')).filter(Boolean);

                    localStorage.setItem('sadigs_menu_order', JSON.stringify(order));

                    const formData = new FormData();
                    formData.append('action', 'save_user_menu_order');
                    formData.append('menu_order', JSON.stringify(order));

                    fetch('dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showToast('Tata letak menu tersimpan ✨');
                        }
                    })
                    .catch(err => {
                        console.error('Gagal menyimpan tata letak menu:', err);
                    });
                }
            });

            window.resetMenuOrder = function() {
                if (!confirm('Kembalikan tata letak menu ke urutan bawaan sistem?')) return;

                localStorage.removeItem('sadigs_menu_order');
                const formData = new FormData();
                formData.append('action', 'reset_user_menu_order');

                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    showToast('Tata letak di-reset 🔄');
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                });
            };
        });

        // =========================================================
        // DIRECT ATTENDANCE HANDLER (LANGSUNG DARI DASHBOARD)
        // =========================================================
        function showDashAbsenModal(title, message, type = 'info', autoReload = false) {
            const modal = document.getElementById('dash-absen-modal');
            const iconContainer = document.getElementById('dash-modal-icon');
            const titleEl = document.getElementById('dash-modal-title');
            const msgEl = document.getElementById('dash-modal-message');
            const btn = document.getElementById('dash-modal-btn');
            if (!modal) return;

            titleEl.innerText = title;
            msgEl.innerHTML = message;

            if (type === 'loading') {
                iconContainer.className = "w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center text-3xl bg-teal-50 text-teal-600";
                iconContainer.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.classList.add('hidden');
            } else if (type === 'success') {
                iconContainer.className = "w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center text-3xl bg-emerald-50 text-emerald-600 animate-bounce";
                iconContainer.innerHTML = '<i class="fas fa-check-circle"></i>';
                btn.className = "w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-md";
                btn.classList.remove('hidden');
                if (autoReload) {
                    setTimeout(() => window.location.reload(), 2500);
                }
            } else if (type === 'rejected') {
                iconContainer.className = "w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center text-3xl bg-amber-50 text-amber-600";
                iconContainer.innerHTML = '<i class="fas fa-location-crosshairs"></i>';
                btn.className = "w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-amber-600 hover:bg-amber-700 transition shadow-md";
                btn.classList.remove('hidden');
            } else { // error
                iconContainer.className = "w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center text-3xl bg-rose-50 text-rose-600";
                iconContainer.innerHTML = '<i class="fas fa-circle-exclamation"></i>';
                btn.className = "w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-rose-600 hover:bg-rose-700 transition shadow-md";
                btn.classList.remove('hidden');
            }

            modal.classList.remove('hidden');
        }

        function closeDashAbsenModal() {
            const modal = document.getElementById('dash-absen-modal');
            if (modal) modal.classList.add('hidden');
        }

        function doDirectAbsensi(jenisAbsen, btnElement) {
            if (btnElement && btnElement.hasAttribute('disabled')) return;

            showDashAbsenModal('Mendeteksi Lokasi GPS', 'Mohon izinkan akses lokasi perangkat untuk verifikasi radius absensi...', 'loading');

            if (!navigator.geolocation) {
                showDashAbsenModal('GPS Tidak Didukung', 'Browser perangkat ini tidak mendukung fitur deteksi lokasi.', 'error');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;

                    showDashAbsenModal('Menyimpan Absensi...', `Koordinat terdeteksi (${userLat.toFixed(4)}, ${userLon.toFixed(4)}). Sedang mencatat ke database...`, 'loading');

                    const fd = new FormData();
                    fd.append('user_lat', userLat);
                    fd.append('user_lon', userLon);
                    fd.append('jenis_absen', jenisAbsen);

                    fetch('proses-absen.php', {
                        method: 'POST',
                        body: fd
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.status === 'success') {
                            const warnNote = data.warning_msg ? `<div class="mt-2 text-amber-600 bg-amber-50 p-2 rounded-lg text-xs font-semibold">${data.warning_msg}</div>` : '';
                            showDashAbsenModal('🎉 Absensi Berhasil!', `<span class="font-bold text-slate-800">${data.message}</span>${warnNote}<div class="mt-2 text-[11px] text-slate-400">Halaman akan otomatis dimuat ulang...</div>`, 'success', true);
                        } else if (data.status === 'rejected') {
                            showDashAbsenModal('⚠️ Di Luar Jangkauan', data.message, 'rejected');
                        } else {
                            showDashAbsenModal('Gagal Absensi', data.message || 'Terjadi kendala saat memproses absensi.', 'error');
                        }
                    })
                    .catch(err => {
                        console.error('Absensi Error:', err);
                        showDashAbsenModal('Kesalahan Server', 'Gagal menghubungi server absensi. Periksa koneksi internet Anda.', 'error');
                    });
                },
                (err) => {
                    let msg = 'Pastikan GPS perangkat Anda telah AKTIF dan berikan izin akses lokasi pada peramban web ini.';
                    if (err.code === err.PERMISSION_DENIED) {
                        msg = 'Akses GPS ditolak. Silakan aktifkan izin lokasi di ikon gembok sebelah URL browser Anda.';
                    } else if (err.code === err.TIMEOUT) {
                        msg = 'Waktu permintaan sinyal GPS habis. Silakan coba klik sekali lagi.';
                    }
                    showDashAbsenModal('Akses GPS Dibutuhkan', msg, 'error');
                },
                { enableHighAccuracy: true, timeout: 9000, maximumAge: 0 }
            );
        }

        // ==========================================
        // SCANNER QR RUANG KELAS JURNAL
        // ==========================================
        let html5QrcodeScannerJurnal = null;

        window.bukaScannerKelas = function() {
            const modal = document.getElementById('qr-modal-jurnal');
            if (!modal) return;
            modal.classList.remove('hidden');
            html5QrcodeScannerJurnal = new Html5QrcodeScanner(
                "reader-jurnal", { fps: 10, qrbox: {width: 250, height: 250} }, false
            );
            html5QrcodeScannerJurnal.render(onScanSuccessJurnal);
        };

        window.tutupScannerKelas = function() {
            const modal = document.getElementById('qr-modal-jurnal');
            if (modal) modal.classList.add('hidden');
            if (html5QrcodeScannerJurnal) {
                html5QrcodeScannerJurnal.clear().catch(error => console.error("Gagal mematikan scanner.", error));
            }
        };

        function onScanSuccessJurnal(decodedText, decodedResult) {
            const selectKelas = document.getElementById('input-kelas-jurnal');
            if (!selectKelas) return;
            let found = false;
            
            for (let i = 0; i < selectKelas.options.length; i++) {
                if (selectKelas.options[i].value === decodedText) {
                    selectKelas.selectedIndex = i;
                    found = true; break;
                }
            }
            tutupScannerKelas();
            if (found) {
                alert("Berhasil! Kamera mendeteksi Anda berada di kelas " + decodedText);
            } else {
                alert("QR Code (" + decodedText + ") tidak dikenali oleh sistem kelas. Pastikan Anda men-scan QR yang benar.");
            }
        }
    </script>

    <!-- MODAL POPUP FEEDBACK ABSENSI LANGSUNG -->
    <div id="dash-absen-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full p-6 text-center transform transition-all border border-slate-100">
            <div id="dash-modal-icon" class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center text-3xl bg-teal-50 text-teal-600">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <h3 id="dash-modal-title" class="text-base sm:text-lg font-black text-slate-800 mb-2">Memproses Absensi</h3>
            <div id="dash-modal-message" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-5">
                Sedang mendeteksi koordinat GPS...
            </div>
            <button id="dash-modal-btn" onclick="closeDashAbsenModal()" class="w-full py-2.5 px-4 rounded-xl font-bold text-xs text-white bg-teal-600 hover:bg-teal-700 transition shadow-md">
                Tutup
            </button>
        </div>
    </div>

    <!-- MODAL SCANNER QR RUANG KELAS -->
    <div id="qr-modal-jurnal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden border border-slate-100">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                    <i class="fas fa-qrcode text-emerald-600"></i> Scan QR Ruang Kelas
                </h3>
                <button type="button" onclick="tutupScannerKelas()" class="w-7 h-7 rounded-full bg-slate-200/60 hover:bg-rose-100 hover:text-rose-600 transition flex items-center justify-center text-slate-600 font-bold">&times;</button>
            </div>
            <div class="p-6 text-center">
                <p class="text-xs text-slate-500 mb-4">Arahkan kamera ke stiker QR Code yang tertempel di dinding kelas.</p>
                <div id="reader-jurnal" class="w-full bg-black rounded-2xl overflow-hidden min-h-[280px] shadow-inner"></div>
            </div>
        </div>
    </div>
</body>
</html>
