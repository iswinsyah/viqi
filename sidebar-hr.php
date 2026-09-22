<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi DB tersedia. Diasumsikan file yang meng-include sidebar ini sudah memanggil koneksi.php
global $conn;

// Ambil role user dari database secara realtime agar perubahan langsung berefek tanpa logout-login
$user_roles = [];
if (isset($conn) && $conn) {
    // Self-healing icon for buku_induk
    @$conn->query("UPDATE menu_structure SET icon = 'fa-address-book' WHERE menu_key = 'buku_induk' AND icon = 'fa-book-user'");
    
    // Self-healing database cleanup and setup for consolidated menus
    $conn->query("DELETE FROM menu_structure WHERE menu_key IN ('rekap_ibadah_rijal', 'rekap_ibadah_nisa', 'rekap_ibadah_mahad', 'laporan_setoran_rijal', 'laporan_setoran_nisa', 'laporan_setoran_hafalan')");
    
    $res_chk_ib = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'rekap_ibadah_santri'");
    if ($res_chk_ib && $res_chk_ib->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Asrama', 'rekap_ibadah_santri', $new_ord, 'fa-mosque', 'admin-ibadah-santri.php')");
    }
    
    $res_chk_set = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'rekap_setoran_santri'");
    if ($res_chk_set && $res_chk_set->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Musyrif', 'rekap_setoran_santri', $new_ord, 'fa-file-alt', 'admin-laporan-setoran-hafalan.php')");
    }

    $res_chk_setin = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'setoran_hafalan_santri'");
    if ($res_chk_setin && $res_chk_setin->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Musyrif', 'setoran_hafalan_santri', $new_ord, 'fa-quran', 'admin-setoran-hafalan-santri.php')");
    }

    $res_chk_jk = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'kontrol_jam_kosong'");
    if ($res_chk_jk && $res_chk_jk->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Administrasi', 'kontrol_jam_kosong', $new_ord, 'fa-calendar-times', 'admin-kontrol-jam-kosong.php')");
    }

    $res_chk_pspp = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'penagihan_spp'");
    if ($res_chk_pspp && $res_chk_pspp->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Administrasi', 'penagihan_spp', $new_ord, 'fa-comment-dollar', 'admin-penagihan-spp.php')");
    }

    $res_chk_el = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'manajemen_elearning'");
    if ($res_chk_el && $res_chk_el->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Asatidz', 'manajemen_elearning', $new_ord, 'fa-laptop-code', 'admin-elearning.php')");
    }

    $res_chk_pp = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'prota_promes'");
    if ($res_chk_pp && $res_chk_pp->num_rows === 0) {
        $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
        $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
        $new_ord = $max_ord + 1;
        $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Asatidz', 'prota_promes', $new_ord, 'fa-calendar-alt', 'admin-kurikulum-prota-promes.php')");
    }
}

if (isset($_SESSION['ustadz_id']) && isset($conn) && $conn) {
    $ustadz_id = (int)$_SESSION['ustadz_id'];
    if ($ustadz_id === 9999) {
        $user_roles = ['super_admin'];
    } else {
        $res_u = $conn->query("SELECT role FROM akun_ustadz WHERE id = $ustadz_id LIMIT 1");
        if ($res_u && $res_u->num_rows > 0) {
            $row_u = $res_u->fetch_assoc();
            $_SESSION['ustadz_role'] = $row_u['role']; // Sinkronisasikan ke sesi
            $user_roles = !empty($row_u['role']) ? explode(',', $row_u['role']) : [];
        }
    }
}
if (empty($user_roles) && isset($_SESSION['ustadz_role'])) {
    $user_roles = explode(',', $_SESSION['ustadz_role']);
}

// Super Admin dapat melihat semua menu
$is_super_admin = false;
foreach ($user_roles as $role) {
    $norm_r = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
    if ($norm_r === 'super_admin') {
        $is_super_admin = true;
        break;
    }
}

// Ambil semua hak akses menu dari database
$menu_permissions = [];
if ($conn) {
    // Pastikan tabel menu_permissions ada (Self-Healing)
    $conn->query("CREATE TABLE IF NOT EXISTS menu_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        menu_key VARCHAR(100) UNIQUE NOT NULL,
        allowed_roles TEXT
    )");
    
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('amanah_asatidz', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('peraturan_role', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('counseling_karir', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kalender_akademik', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('jadwal_pelajaran', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kurikulum_solopreneur_trainer', 'trainer,ustadz,kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kpi_ustadz', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('validasi_ibadah_musyrif', 'musyrif,musyrifah,kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('rekap_ibadah_santri', 'kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,admin_sekolah,kepala_sekolah,super_admin,musyrif,musyrifah')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('rekap_setoran_santri', 'kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,super_admin,musyrif,musyrifah')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('setoran_hafalan_santri', 'kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,super_admin,musyrif,musyrifah')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kontrol_jam_kosong', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('penagihan_spp', 'admin_sekolah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kpi_musyrif', 'musyrif,musyrifah,kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,admin_sekolah,kepala_sekolah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('sekolah_pembukuan', 'kepala_sekolah,admin_sekolah')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('rapot_pkbm', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama_rijal,kepala_asrama_nisa,musyrif,musyrifah,ustadz,ustadzah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('rapot_pkbm_musyrif', 'musyrif,musyrifah,kepala_asrama_rijal,kepala_asrama_nisa,kepala_asrama,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('cek_belajar_mandiri', 'musyrif,musyrifah,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('cek_kesehatan_santri', 'musyrif,musyrifah,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,admin_sekolah,kepala_sekolah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('santri_tidak_masuk', 'ustadz,ustadzah,tutor,trainer,admin_sekolah,kepala_sekolah,kepala_mahad,kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,musyrif,musyrifah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('santri_tidak_masuk_asatidz', 'ustadz,ustadzah,tutor,trainer,admin_sekolah,kepala_sekolah,kepala_mahad,kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,musyrif,musyrifah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kontak_orangtua', 'musyrif,musyrifah,kepala_asrama,kepala_asrama_rijal,kepala_asrama_nisa,kepala_mahad,admin_sekolah,kepala_sekolah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('manajemen_elearning', 'ustadz,ustadzah,guru,tutor,trainer,kepala_sekolah,admin_sekolah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('prota_promes', 'ustadz,ustadzah,guru,tutor,trainer,kepala_sekolah,admin_sekolah,super_admin')");

    $res_perms = $conn->query("SELECT menu_key, allowed_roles FROM menu_permissions");
    if ($res_perms) {
        while ($row = $res_perms->fetch_assoc()) {
            $menu_permissions[$row['menu_key']] = !empty($row['allowed_roles']) ? explode(',', $row['allowed_roles']) : [];
        }
    }
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kpi_kepsek', 'kepala_sekolah,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('supervisi_mengajar', 'kepala_sekolah,kepala_mahad,super_admin')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('kpi_admin_sekolah', 'admin_sekolah')");
    $conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('salary_admin', 'admin_sekolah')");
}

// Load custom menu labels from database
$custom_menu_labels = [];
if ($conn) {
    $res_custom_lbls = $conn->query("SELECT menu_key, custom_label FROM menu_custom_labels");
    if ($res_custom_lbls) {
        while ($row = $res_custom_lbls->fetch_assoc()) {
            $custom_menu_labels[$row['menu_key']] = $row['custom_label'];
        }
    }
}

// Fungsi helper untuk mengecek hak akses
function has_access($menu_key, $user_roles, $menu_permissions, $is_super_admin) {
    if ($is_super_admin) return true;
    // Menu dasar yang wajib tampil untuk seluruh pegawai terdaftar (bahkan jika belum diset role-nya)
    if (in_array($menu_key, ['absensi_pegawai', 'perizinan_pegawai', 'ganti_password'])) return true;
    if (!isset($menu_permissions[$menu_key])) return false; // Default: sembunyikan jika belum diatur

    // Normalisasi allowed roles dari database
    $allowed_roles = array_map(function($r) {
        return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
    }, $menu_permissions[$menu_key]);

    foreach ($user_roles as $role) {
        $norm_role = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
        if ($norm_role === 'kepala_asrama') $norm_role = 'kepala_asrama_rijal';
        if (in_array($norm_role, $allowed_roles)) {
            return true;
        }
    }
    return false;
}

// Definisikan urutan grup default agar urutannya konsisten
$group_order = [
    'Menu Utama' => 1,
    'Administrasi' => 2,
    'Asatidz' => 3,
    'Asrama' => 4,
    'Musyrif' => 5,
    'Keuangan Santri' => 6,
    'Solopreneur & AI' => 7
];

$menu_structure = [];

// Pastikan tabel menu_structure dibuat dan di-seed jika kosong (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS menu_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_group VARCHAR(100) NOT NULL,
    menu_key VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    icon VARCHAR(100) NOT NULL,
    href VARCHAR(255) NOT NULL
)");

// Pembersihan paksa untuk penggabungan menu KPI, Akunku, Jurnal Mengajar, Santri Tidak Masuk & Duplikat E-Modul/Hafalan
$conn->query("DELETE FROM menu_structure WHERE menu_key IN ('emodul', 'hafalan', 'kpi_kepsek', 'kpi_musyrif', 'ganti_password', 'jurnal', 'jurnal_mengajar', 'absensi', 'absensi_pegawai', 'santri_tidak_masuk', 'santri_tidak_masuk_asatidz')");
$conn->query("DELETE FROM menu_permissions WHERE menu_key IN ('emodul', 'hafalan')");
$conn->query("DELETE FROM menu_custom_labels WHERE menu_key IN ('emodul', 'hafalan')");

// Pastikan menu 'akunku' terdaftar jika belum ada (Self-Healing)
$res_chk_akunku = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'akunku'");
if ($res_chk_akunku && $res_chk_akunku->num_rows === 0) {
    $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
    $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
    $new_ord = $max_ord + 1;
    $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Menu Utama', 'akunku', $new_ord, 'fa-user-cog', 'akunku.php')");
}

// Pastikan menu 'jadwal_rapat' terdaftar jika belum ada (Self-Healing)
$res_chk_rapat = $conn->query("SELECT id FROM menu_structure WHERE menu_key = 'jadwal_rapat'");
if ($res_chk_rapat && $res_chk_rapat->num_rows === 0) {
    $res_ord = $conn->query("SELECT MAX(sort_order) as max_ord FROM menu_structure");
    $max_ord = $res_ord ? (int)$res_ord->fetch_assoc()['max_ord'] : 0;
    $new_ord = $max_ord + 1;
    $conn->query("INSERT INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('Menu Utama', 'jadwal_rapat', $new_ord, 'fa-handshake', 'admin-jadwal-rapat.php')");
}

// Cek apakah data sudah ada
$res_cnt = $conn->query("SELECT COUNT(*) as cnt FROM menu_structure");
$count_struct = $res_cnt ? (int)$res_cnt->fetch_assoc()['cnt'] : 0;
if ($count_struct === 0) {
    // Seed data awal jika kosong
    $default_structure = [
        'Menu Utama' => [
            'absensi_pegawai' => ['href' => 'admin-absensi-pegawai.php', 'icon' => 'fa-qrcode'],
            'perizinan_pegawai' => ['href' => 'admin-pegawai-perizinan.php', 'icon' => 'fa-calendar-check'],
            'peraturan_role' => ['href' => 'admin-ustadz.php?view=peraturan_role', 'icon' => 'fa-file-contract'],
            'kpi_ustadz' => ['href' => 'admin-pegawai-kpi.php', 'icon' => 'fa-chalkboard-teacher'],
            'supervisi_mengajar' => ['href' => 'admin-supervisi-mengajar.php', 'icon' => 'fa-clipboard-check'],
            'jadwal_rapat' => ['href' => 'admin-jadwal-rapat.php', 'icon' => 'fa-handshake'],
            'akunku' => ['href' => 'akunku.php', 'icon' => 'fa-user-cog'],
        ],
        'Administrasi' => [
            'buku_induk' => ['href' => 'admin-buku-induk.php', 'icon' => 'fa-book-user'],
            'akun_orangtua' => ['href' => 'admin-akun-orangtua.php', 'icon' => 'fa-users'],
            'leger_nilai' => ['href' => 'admin-leger.php', 'icon' => 'fa-book-reader'],
            'rapot_pkbm' => ['href' => 'admin-rapot-pkbm.php', 'icon' => 'fa-file-invoice'],
            'counseling_karir' => ['href' => 'admin-counseling-karir.php', 'icon' => 'fa-graduation-cap'],
            'rekap_keuangan' => ['href' => 'admin-rekap-spp.php', 'icon' => 'fa-file-invoice-dollar'],
            'penagihan_spp' => ['href' => 'admin-penagihan-spp.php', 'icon' => 'fa-comment-dollar'],
            'rekap_uang_saku' => ['href' => 'admin-rekap-uang-saku.php', 'icon' => 'fa-wallet'],
            'sekolah_pembukuan' => ['href' => 'sekolah-pembukuan.php', 'icon' => 'fa-book'],
            'kontrol_jam_kosong' => ['href' => 'admin-kontrol-jam-kosong.php', 'icon' => 'fa-calendar-times'],
        ],
        'Asatidz' => [
            'kesediaan_mengajar' => ['href' => 'admin-pegawai-kesediaan.php', 'icon' => 'fa-clock'],
            'kalender_akademik' => ['href' => 'kalender-akademik.php', 'icon' => 'fa-calendar-alt'],
            'jadwal_pelajaran' => ['href' => 'admin-jadwal-pelajaran.php', 'icon' => 'fa-calendar-alt'],
            'master_silabus' => ['href' => 'admin-pegawai-silabus.php', 'icon' => 'fa-book-reader'],
            'ai_rpp' => ['href' => 'admin-pegawai-rpp.php', 'icon' => 'fa-magic'],
            'bank_nilai' => ['href' => 'admin-pegawai-nilai.php', 'icon' => 'fa-star-half-alt'],
            'master_kelas' => ['href' => 'admin-master-kelas.php', 'icon' => 'fa-school'],
            'master_mapel' => ['href' => 'admin-master-mapel.php', 'icon' => 'fa-book'],
            'kitab_rujukan' => ['href' => 'admin-kitab-rujukan.php', 'icon' => 'fa-book-open'],
        ],
        'Asrama' => [
            'dashboard_asrama' => ['href' => 'admin-ustadz.php?view=dashboard_asrama', 'icon' => 'fa-home-user'],
            'manajemen_halaqoh' => ['href' => 'admin-ustadz.php?view=halaqoh', 'icon' => 'fa-layer-group'],
            'rekap_ibadah_santri' => ['href' => 'admin-ibadah-santri.php', 'icon' => 'fa-mosque'],
        ],
        'Musyrif' => [
            'validasi_ibadah_musyrif' => ['href' => 'admin-validasi-ibadah-musyrif.php', 'icon' => 'fa-tasks'],
            'kontak_orangtua' => ['href' => 'admin-kontak-orangtua.php', 'icon' => 'fa-comments'],
            'cek_belajar_mandiri' => ['href' => 'admin-cek-belajar-mandiri.php', 'icon' => 'fa-book-reader'],
            'cek_kesehatan_santri' => ['href' => 'admin-cek-kesehatan-santri.php', 'icon' => 'fa-notes-medical'],
            'rapot_pkbm_musyrif' => ['href' => 'admin-rapot-pkbm.php', 'icon' => 'fa-file-invoice'],
            'mutabaah' => ['href' => 'admin-pegawai-mutabaah.php', 'icon' => 'fa-clipboard-list'],
            'laporan_adab' => ['href' => 'admin-pegawai-laporan-adab.php', 'icon' => 'fa-balance-scale'],
            'setoran_hafalan_santri' => ['href' => 'admin-setoran-hafalan-santri.php', 'icon' => 'fa-quran'],
            'rekap_setoran_santri' => ['href' => 'admin-laporan-setoran-hafalan.php', 'icon' => 'fa-file-alt'],
        ],
        'Keuangan Santri' => [
            'rekap_uang_saku_musyrif' => ['href' => 'admin-rekap-uang-saku-musyrif.php', 'icon' => 'fa-wallet'],
        ],
        'Solopreneur & AI' => [
            'kurikulum_solopreneur_trainer' => ['href' => 'trainer-kurikulum-solopreneur.php', 'icon' => 'fa-rocket']
        ]
    ];
    
    $order = 0;
    foreach ($default_structure as $group => $menus) {
        foreach ($menus as $key => $meta) {
            $href = $conn->real_escape_string($meta['href']);
            $icon = $conn->real_escape_string($meta['icon']);
            $conn->query("INSERT IGNORE INTO menu_structure (menu_group, menu_key, sort_order, icon, href) VALUES ('$group', '$key', $order, '$icon', '$href')");
            $order++;
        }
    }
}

// Ambil struktur menu dari database
$res_db_struct = $conn->query("SELECT * FROM menu_structure ORDER BY sort_order ASC");
if ($res_db_struct) {
    while ($row = $res_db_struct->fetch_assoc()) {
        $group = $row['menu_group'];
        $key = $row['menu_key'];
        
        $default_titles = [
            'absensi_pegawai' => 'Absensi & Jurnal KBM',
            'perizinan_pegawai' => 'Pengajuan Izin / Cuti',
            'peraturan_role' => 'Peraturan Pegawai',
            'kpi_ustadz' => 'KPI Pegawai',
            'kpi_kepsek' => 'KPI Kepala Sekolah',
            'supervisi_mengajar' => 'Supervisi Mengajar',
            'jadwal_rapat' => 'Jadwal Rapat',
            'akunku' => 'Akunku',
            'buku_induk' => 'Buku Induk Santri',
            'akun_orangtua' => 'Akun Orang Tua',
            'leger_nilai' => 'Leger Nilai Digital',
            'rapot_pkbm' => 'Raport Diknas PKBM (B & C)',
            'counseling_karir' => 'Pemetaan Karir & PTN (AI)',
            'rekap_keuangan' => 'Rekap Pembayaran Keuangan',
            'penagihan_spp' => 'Penagihan SPP',
            'yayasan_saku' => 'Validasi Uang Saku',
            'uangsaku' => 'Saldo Uang Saku Santri',
            'rekap_uang_saku' => 'Validasi Uang Saku',
            'sekolah_pembukuan' => 'Buku Kas Sekolah',
            'kontrol_jam_kosong' => 'Kontrol Jam Kosong',
            'kpi_admin_sekolah' => 'KPI Admin Sekolah',
            'salary_admin' => 'Salary Admin Sekolah',
            'kesediaan_mengajar' => 'Kesediaan Mengajar',
            'kalender_akademik' => 'Kalender Akademik',
            'jadwal_pelajaran' => 'Jadwal Pelajaran',
            'master_silabus' => 'Master Silabus & CP',
            'ai_rpp' => 'AI Generator RPP',
            'bank_nilai' => 'Bank Nilai (Input)',
            'master_kelas' => 'Master Kelas',
            'master_mapel' => 'Master Mapel',
            'kitab_rujukan' => 'Master Kitab Rujukan',
            'dashboard_asrama' => 'Dashboard Asrama',
            'manajemen_halaqoh' => 'Manajemen Halaqoh',
            'yayasan_ibadah' => 'Rekap Ibadah Yayasan',
            'rekap_ibadah_santri' => 'Rekap Ibadah Asrama',
            'ibadah' => 'Validasi Ibadah Santri',
            'setoran_hafalan_santri' => 'Setoran Hafalan Santri',
            'rekap_setoran_santri' => 'Rekap Setoran Santri',
            'validasi_ibadah_musyrif' => 'Validasi Ibadah',
            'kontak_orangtua' => 'Kontak Walisantri',
            'cek_belajar_mandiri' => 'Chek Belajar Mandiri',
            'cek_kesehatan_santri' => 'Chek Kesehatan',
            'rapot_pkbm_musyrif' => 'Raport Diknas Santri Binaan',
            'mutabaah' => 'Buku Mutaba\'ah Santri',
            'laporan_adab' => 'Laporan Kedisiplinan',
            'kpi_musyrif' => 'KPI Musyrif',
            'rekap_uang_saku_musyrif' => 'Rekap Uang Saku Santri',
            'kurikulum_solopreneur_trainer' => 'Inkubator Solopreneur (AI)',
            'manajemen_elearning' => 'E-Modul & Flipbook Belajar',
            'prota_promes' => 'Pekan Efektif, Prota & Promes'
        ];
        
        $title = $default_titles[$key] ?? ucwords(str_replace('_', ' ', $key));
        
        $menu_structure[$group][$key] = [
            'href' => $row['href'],
            'icon' => $row['icon'],
            'title' => $title
        ];
    }
}

// Urutkan grup sesuai $group_order
uksort($menu_structure, function($a, $b) use ($group_order) {
    $order_a = $group_order[$a] ?? 99;
    $order_b = $group_order[$b] ?? 99;
    return $order_a <=> $order_b;
});

// Terapkan label kustom secara dinamis
foreach ($menu_structure as $group_title => &$menus) {
    foreach ($menus as $key => &$menu) {
        if (isset($custom_menu_labels[$key])) {
            $menu['title'] = $custom_menu_labels[$key];
        }
    }
}
unset($menu); // Bersihkan referensi

// Data Pengguna untuk Kartu Profil Sidebar
$u_nama = $_SESSION['app_user_nama'] ?? ($_SESSION['ustadz_nama'] ?? ($_SESSION['nama_lengkap'] ?? ($_SESSION['nama'] ?? 'Asatidz')));
$u_user = $_SESSION['app_username'] ?? ($_SESSION['ustadz_username'] ?? ($_SESSION['username'] ?? 'asatidz'));
$u_role = $_SESSION['app_user_roles'] ?? ($_SESSION['ustadz_role'] ?? ($_SESSION['role'] ?? 'Asatidz'));
$u_foto = $_SESSION['foto_profil'] ?? '';

if (empty($u_foto) && isset($_SESSION['ustadz_id']) && isset($conn) && $conn) {
    $uid = (int)$_SESSION['ustadz_id'];
    $res_pic = @$conn->query("SELECT foto FROM akun_ustadz WHERE id = $uid LIMIT 1");
    if ($res_pic && $res_pic->num_rows > 0) {
        $u_foto = $res_pic->fetch_assoc()['foto'] ?? '';
    }
}

$curr_file = basename($_SERVER['PHP_SELF']);
?>
<!-- SIDEBAR OVERLAY UNTUK MOBILE -->
<div id="sidebar-overlay-hr" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR RUANG ASATIDZ (#0b8478 TEAL THEME - SAMA DENGAN DASHBOARD) -->
<aside id="sidebar-hr" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">
    
    <!-- SIDEBAR HEADER: BRAND LOGO SADIGS -->
    <div class="p-6 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard.php" class="flex items-center space-x-3.5 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </a>
        <button id="close-sidebar-hr" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR PROFILE CARD -->
    <div class="px-5 py-4 border-b border-teal-700/40 bg-teal-900/30 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-11 h-11 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-base shadow-sm border-2 border-white/80 overflow-hidden flex-shrink-0">
                <?php if (!empty($u_foto)): ?>
                    <img src="<?= htmlspecialchars($u_foto) ?>" alt="Avatar" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="fas fa-user text-[#0b8478]"></i>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden flex-1">
                <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($u_nama) ?></h4>
                <p class="text-[10px] text-teal-200 truncate mt-0.5">@<?= htmlspecialchars($u_user) ?></p>
                <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                    <?= htmlspecialchars($u_role) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
        <!-- 1. MENU UTAMA (SAMA DENGAN DASHBOARD) -->
        <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_file === 'dashboard.php') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-house w-4 text-center"></i>
            <span>Beranda</span>
        </a>
        <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_file === 'kalender-akademik.php') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-calendar-alt w-4 text-center"></i>
            <span>Kalender</span>
        </a>
        <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_file === 'admin-jadwal-pelajaran.php') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-clock w-4 text-center"></i>
            <span>Jadwal</span>
        </a>
        <a href="pengumuman.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_file === 'pengumuman.php') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-bullhorn w-4 text-center"></i>
            <span>Info</span>
        </a>

        <!-- 2. MODUL RUANG ASATIDZ -->
        <div class="pt-4 mt-3 border-t border-teal-700/60">
            <div class="flex items-center justify-between px-2 mb-2">
                <span class="text-[10px] font-black uppercase tracking-wider text-teal-200 flex items-center gap-1.5">
                    <i class="fas fa-folder-open text-[9px]"></i> Modul Asatidz
                </span>
            </div>
            <div class="space-y-1">
                <?php foreach ($menu_structure as $group_title => $menus): ?>
                    <?php
                        $is_group_visible = false;
                        foreach ($menus as $key => $menu) {
                            if (has_access($key, $user_roles, $menu_permissions, $is_super_admin)) {
                                $is_group_visible = true;
                                break;
                            }
                        }
                    ?>
                    <?php if ($is_group_visible): ?>
                        <p class="px-2 text-[9px] font-black text-teal-300/80 uppercase tracking-wider mt-3 mb-1"><?= htmlspecialchars($group_title) ?></p>
                        <?php foreach ($menus as $key => $menu): ?>
                            <?php if (has_access($key, $user_roles, $menu_permissions, $is_super_admin)): ?>
                                <?php
                                    $is_active = (isset($active_menu) && $active_menu == $key) || ($curr_file === basename($menu['href']));
                                    $class_a = $is_active ? 'bg-white text-[#0b8478] font-bold shadow-xs' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-medium';
                                    $class_i = $is_active ? 'text-[#0b8478]' : 'text-teal-200 group-hover:text-white';
                                ?>
                                <a href="<?= htmlspecialchars($menu['href']) ?>" class="<?= $class_a ?> group flex items-center px-3 py-2 text-xs rounded-xl transition-all">
                                    <i class="fas <?= htmlspecialchars($menu['icon']) ?> w-5 text-center mr-2 <?= $class_i ?>"></i> 
                                    <span class="truncate"><?= htmlspecialchars($menu['title']) ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </nav>

    <!-- SIDEBAR FOOTER: KELUAR -->
    <div class="p-4 border-t border-teal-700/60 flex-shrink-0">
        <a href="logout-ustadz.php" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>

<!-- ========================================================= -->
<!-- BOTTOM NAVIGATION BAR (HANYA MUNCUL DI MOBILE / md:hidden) -->
<!-- ========================================================= -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] flex items-center justify-around z-40 max-w-[440px] mx-auto px-2">
    <a href="dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'dashboard.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
        <div class="w-9 h-7 rounded-full <?= ($curr_file === 'dashboard.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
            <i class="fas fa-house text-base <?= ($curr_file === 'dashboard.php') ? 'text-white' : 'text-teal-100' ?>"></i>
        </div>
        <span class="text-white">Beranda</span>
    </a>
    <a href="kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'kalender-akademik.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
        <div class="w-9 h-7 rounded-full <?= ($curr_file === 'kalender-akademik.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
            <i class="fas fa-calendar-alt text-base <?= ($curr_file === 'kalender-akademik.php') ? 'text-white' : 'text-teal-100' ?>"></i>
        </div>
        <span class="text-white">Kalender</span>
    </a>
    <a href="admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'admin-jadwal-pelajaran.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
        <div class="w-9 h-7 rounded-full <?= ($curr_file === 'admin-jadwal-pelajaran.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
            <i class="fas fa-clock text-base <?= ($curr_file === 'admin-jadwal-pelajaran.php') ? 'text-white' : 'text-teal-100' ?>"></i>
        </div>
        <span class="text-white">Jadwal</span>
    </a>
    <a href="pengumuman.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'pengumuman.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
        <div class="w-9 h-7 rounded-full <?= ($curr_file === 'pengumuman.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
            <i class="fas fa-bullhorn text-base <?= ($curr_file === 'pengumuman.php') ? 'text-white' : 'text-teal-100' ?>"></i>
        </div>
        <span class="text-white">Info</span>
    </a>
    <a href="logout-ustadz.php" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-rose-200 font-bold text-[10px] transition">
        <div class="w-9 h-7 rounded-full flex items-center justify-center mb-0.5">
            <i class="fas fa-arrow-right-from-bracket text-base text-teal-100 hover:text-rose-200"></i>
        </div>
        <span class="text-white">Keluar</span>
    </a>
</nav>

<style>
@media (max-width: 767px) {
    main, .overflow-y-auto {
        padding-bottom: 5rem !important;
    }
}
</style>

<?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true): ?>
<div class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-900 text-white px-4 py-2 text-xs shadow-2xl flex items-center justify-between border-b border-purple-400">
    <div class="flex items-center space-x-2">
        <span class="animate-pulse text-amber-300 font-extrabold text-sm"><i class="fas fa-user-secret"></i> MODE IMPERSONASI</span>
        <span class="hidden sm:inline text-purple-200">|</span>
        <span class="text-purple-100">Anda sedang mengakses sistem sebagai: <strong class="text-amber-200 underline font-bold"><?= htmlspecialchars($_SESSION['ustadz_nama'] ?? '') ?></strong></span>
    </div>
    <a href="switch-back-admin.php" class="bg-amber-400 hover:bg-amber-300 text-purple-950 font-extrabold px-3.5 py-1 rounded-full text-[11px] shadow transition flex items-center gap-1.5 whitespace-nowrap">
        <i class="fas fa-undo"></i> Kembali ke Super Admin
    </a>
</div>
<style>
/* Geser layout sedikit jika banner impersonasi aktif */
body { padding-top: 36px !important; }
</style>
<?php endif; ?>

<script>
document.addEventListener('click', function(event) {
    const openBtn = event.target.closest('#open-sidebar-hr');
    const closeBtn = event.target.closest('#close-sidebar-hr');
    const overlay = event.target.closest('#sidebar-overlay-hr');

    if (openBtn || closeBtn || overlay) {
        event.stopImmediatePropagation();
        event.preventDefault();
        
        const sidebar = document.getElementById('sidebar-hr');
        const overlayEl = document.getElementById('sidebar-overlay-hr');
        if (sidebar && overlayEl) {
            sidebar.classList.toggle('hidden');
            overlayEl.classList.toggle('hidden');
        }
    }
}, true);
</script>