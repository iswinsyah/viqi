<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan koneksi DB tersedia. Diasumsikan file yang meng-include sidebar ini sudah memanggil koneksi.php
global $conn;

// Ambil role user dari database secara realtime agar perubahan langsung berefek tanpa logout-login
$user_roles = [];
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
    // Menu dasar yang wajib tampil untuk seluruh pegawai terdaftar (bahkan jika belum diset role-nya)
    if (in_array($menu_key, ['absensi_pegawai', 'perizinan_pegawai', 'ganti_password'])) return true;
    if (!isset($menu_permissions[$menu_key])) return false; // Default: sembunyikan jika belum diatur

    // Normalisasi allowed roles dari database
    $allowed_roles = array_map(function($r) {
        return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
    }, $menu_permissions[$menu_key]);

    foreach ($user_roles as $role) {
        $norm_role = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
        if (in_array($norm_role, $allowed_roles)) {
            return true;
        }
    }
    return false;
}

// Definisikan struktur menu untuk iterasi
$menu_structure = [
    'Menu Utama' => [
        'absensi_pegawai' => ['href' => 'admin-absensi-pegawai.php', 'icon' => 'fa-qrcode', 'title' => 'Absensi Kehadiran'],
        'perizinan_pegawai' => ['href' => 'admin-pegawai-perizinan.php', 'icon' => 'fa-calendar-check', 'title' => 'Pengajuan Izin / Cuti'],
        'peraturan_role' => ['href' => 'admin-ustadz.php?view=peraturan_role', 'icon' => 'fa-file-contract', 'title' => 'Peraturan Pegawai'],
        'kpi_ustadz' => ['href' => 'admin-pegawai-kpi.php', 'icon' => 'fa-chalkboard-teacher', 'title' => 'KPI Pegawai'],
        'ganti_password' => ['href' => 'ganti-password-ustadz.php', 'icon' => 'fa-key', 'title' => 'Ganti Password'],
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
        'kalender_akademik' => ['href' => 'kalender-akademik.php', 'icon' => 'fa-calendar-alt', 'title' => 'Kalender Akademik'],
        'jadwal_pelajaran' => ['href' => 'admin-jadwal-pelajaran.php', 'icon' => 'fa-calendar-alt', 'title' => 'Jadwal Pelajaran'],
        'master_silabus' => ['href' => 'admin-pegawai-silabus.php', 'icon' => 'fa-book-reader', 'title' => 'Master Silabus & CP'],
        'ai_rpp' => ['href' => 'admin-pegawai-rpp.php', 'icon' => 'fa-magic', 'title' => 'AI Generator RPP'],
        'jurnal_mengajar' => ['href' => 'admin-pegawai-jurnal.php', 'icon' => 'fa-book-open', 'title' => 'Jurnal Mengajar'],
        'bank_nilai' => ['href' => 'admin-pegawai-nilai.php', 'icon' => 'fa-star-half-alt', 'title' => 'Bank Nilai (Input)'],
        'master_kelas' => ['href' => 'admin-master-kelas.php', 'icon' => 'fa-school', 'title' => 'Master Kelas'],
        'master_mapel' => ['href' => 'admin-master-mapel.php', 'icon' => 'fa-book', 'title' => 'Master Mapel'],
        'kitab_rujukan' => ['href' => 'admin-kitab-rujukan.php', 'icon' => 'fa-book-open', 'title' => 'Master Kitab Rujukan'],
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
        'kpi_musyrif' => ['href' => 'admin-pegawai-kpi-musyrif.php', 'icon' => 'fa-user-shield', 'title' => 'KPI Musyrif'],
    ],
    'Keuangan Santri' => [
        'rekap_uang_saku_musyrif' => ['href' => 'admin-rekap-uang-saku-musyrif.php', 'icon' => 'fa-wallet', 'title' => 'Rekap Uang Saku Santri'],
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