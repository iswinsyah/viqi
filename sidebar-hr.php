<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi DB tersedia. Diasumsikan file yang meng-include sidebar ini sudah memanggil koneksi.php
global $conn;

// Ambil role user dari session
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

// Super Admin dapat melihat semua menu
$is_super_admin = in_array('super_admin', $user_roles);

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
    $conn->query("INSERT INTO menu_permissions (menu_key, allowed_roles) 
                  VALUES ('jadwal_pelajaran', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')
                  ON DUPLICATE KEY UPDATE allowed_roles = 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz'");

    $res_perms = $conn->query("SELECT menu_key, allowed_roles FROM menu_permissions");
    if ($res_perms) {
        while ($row = $res_perms->fetch_assoc()) {
            $menu_permissions[$row['menu_key']] = !empty($row['allowed_roles']) ? explode(',', $row['allowed_roles']) : [];
        }
    }
}

// Fungsi helper untuk mengecek hak akses
function has_access($menu_key, $user_roles, $menu_permissions, $is_super_admin) {
    if ($is_super_admin) return true;
    if (!isset($menu_permissions[$menu_key])) return false; // Default: sembunyikan jika belum diatur

    foreach ($user_roles as $role) {
        if (in_array(trim($role), $menu_permissions[$menu_key])) {
            return true;
        }
    }
    return false;
}

// Definisikan struktur menu untuk iterasi
$menu_structure = [
    'Menu Utama' => [
        'jadwal_pelajaran' => ['href' => 'admin-jadwal-pelajaran.php', 'icon' => 'fa-calendar-alt', 'title' => 'Jadwal Pelajaran'],
        'kalender_akademik' => ['href' => 'kalender-akademik.php', 'icon' => 'fa-calendar-alt', 'title' => 'Kalender Akademik'],
        'dashboard_pegawai' => ['href' => 'admin-ustadz.php', 'icon' => 'fa-tachometer-alt', 'title' => 'Dashboard Pegawai'],
        'absensi_pegawai' => ['href' => 'admin-absensi-pegawai.php', 'icon' => 'fa-qrcode', 'title' => 'Absensi Kehadiran'],
    ],
    'Administrasi' => [
        'buku_induk' => ['href' => 'admin-buku-induk.php', 'icon' => 'fa-book-user', 'title' => 'Buku Induk Santri'],
        'akun_orangtua' => ['href' => 'admin-akun-orangtua.php', 'icon' => 'fa-users', 'title' => 'Akun Orang Tua'],
        'leger_nilai' => ['href' => 'admin-leger.php', 'icon' => 'fa-book-reader', 'title' => 'Leger Nilai Digital'],
        'counseling_karir' => ['href' => 'admin-counseling-karir.php', 'icon' => 'fa-graduation-cap', 'title' => 'Pemetaan Karir & PTN (AI)'],
        'rekap_keuangan' => ['href' => 'admin-rekap-spp.php', 'icon' => 'fa-file-invoice-dollar', 'title' => 'Rekap Pembayaran Keuangan'],
        'rekap_uang_saku' => ['href' => 'admin-rekap-uang-saku.php', 'icon' => 'fa-wallet', 'title' => 'Rekap Data Uang Saku'],
    ],
    'Asatidz' => [
        'kesediaan_mengajar' => ['href' => 'admin-pegawai-kesediaan.php', 'icon' => 'fa-clock', 'title' => 'Kesediaan Mengajar'],
        'master_silabus' => ['href' => 'admin-pegawai-silabus.php', 'icon' => 'fa-book-reader', 'title' => 'Master Silabus & CP'],
        'ai_rpp' => ['href' => 'admin-pegawai-rpp.php', 'icon' => 'fa-magic', 'title' => 'AI Generator RPP'],
        'jurnal_mengajar' => ['href' => 'admin-pegawai-jurnal.php', 'icon' => 'fa-book-open', 'title' => 'Jurnal Mengajar'],
        'bank_nilai' => ['href' => 'admin-pegawai-nilai.php', 'icon' => 'fa-star-half-alt', 'title' => 'Bank Nilai (Input)'],
        'peraturan_role' => ['href' => 'admin-ustadz.php?view=peraturan_role', 'icon' => 'fa-file-contract', 'title' => 'Peraturan Pegawai'],

    ],
    'Asrama' => [
        'dashboard_asrama' => ['href' => 'admin-ustadz.php?view=dashboard_asrama', 'icon' => 'fa-home-user', 'title' => 'Dashboard Asrama'],
        'manajemen_halaqoh' => ['href' => 'admin-ustadz.php?view=halaqoh', 'icon' => 'fa-layer-group', 'title' => 'Manajemen Halaqoh'],
    ],
    'Musyrif' => [
        'mutabaah' => ['href' => 'admin-pegawai-mutabaah.php', 'icon' => 'fa-clipboard-list', 'title' => 'Buku Mutaba\'ah Santri'],
        'setor_hafalan' => ['href' => 'admin-ustadz.php?view=setor_hafalan', 'icon' => 'fa-quran', 'title' => 'Setoran Hafalan'],
        'jurnal_musyrif' => ['href' => 'admin-pegawai-jurnal-musyrif.php', 'icon' => 'fa-user-shield', 'title' => 'Jurnal Kegiatan Musyrif'],
        'laporan_adab' => ['href' => 'admin-pegawai-laporan-adab.php', 'icon' => 'fa-balance-scale', 'title' => 'Laporan Kedisiplinan'],
        'penilaian_adab' => ['href' => 'admin-penilaian-adab.php', 'icon' => 'fa-heart-circle-check', 'title' => 'Penilaian Adab (Rapor)'],
    ],
    'Keuangan Santri' => [
        'rekap_uang_saku_musyrif' => ['href' => 'admin-rekap-uang-saku-musyrif.php', 'icon' => 'fa-wallet', 'title' => 'Rekap Uang Saku Santri'],
    ],
    'Kinerja & Akun' => [
        'amanah_asatidz' => ['href' => 'admin-ustadz.php?view=amanah', 'icon' => 'fa-id-card', 'title' => 'Menu Amanah'],
        'kpi_ustadz' => ['href' => 'admin-pegawai-kpi.php', 'icon' => 'fa-chalkboard-teacher', 'title' => 'KPI Ustadz'],
        'kpi_musyrif' => ['href' => 'admin-pegawai-kpi-musyrif.php', 'icon' => 'fa-user-shield', 'title' => 'KPI Musyrif'],
        'ganti_password' => ['href' => 'ganti-password-ustadz.php', 'icon' => 'fa-key', 'title' => 'Ganti Password'],
    ]
];

?>
<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-hr" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS KEPEGAWAIAN & AI HRD -->
<aside id="sidebar-hr" class="bg-slate-800 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-700 bg-slate-900">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-cyan-400">
            <i class="fas fa-users-cog mr-2"></i> RUANG ASATIDZ
        </h1>
        <button id="close-sidebar-hr" class="md:hidden text-slate-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <?php foreach ($menu_structure as $group_title => $menus): ?>
                <?php
                    // Cek apakah ada setidaknya satu menu dalam grup ini yang bisa diakses
                    $is_group_visible = false;
                    foreach ($menus as $key => $menu) {
                        if (has_access($key, $user_roles, $menu_permissions, $is_super_admin)) {
                            $is_group_visible = true;
                            break;
                        }
                    }
                ?>
                <?php if ($is_group_visible): ?>
                    <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-6"><?= $group_title ?></p>
                    <?php foreach ($menus as $key => $menu): ?>
                        <?php if (has_access($key, $user_roles, $menu_permissions, $is_super_admin)): ?>
                            <?php
                                $is_active = (isset($active_menu) && $active_menu == $key);
                                $class_a = $is_active ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white';
                                $class_i = $is_active ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white';
                            ?>
                            <a href="<?= $menu['href'] ?>" class="<?= $class_a ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                                <i class="fas <?= $menu['icon'] ?> w-6 text-center mr-2 <?= $class_i ?>"></i> <?= $menu['title'] ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    </div>

    <div class="p-4 border-t border-slate-700">
        <a href="logout-ustadz.php" class="flex items-center justify-center text-sm font-bold text-white hover:text-white transition-all bg-rose-500 hover:bg-rose-600 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
        </a>
    </div>
</aside>

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