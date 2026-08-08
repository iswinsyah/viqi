<?php
require_once 'auth.php';
require_once '../koneksi.php';

// 1. Definisikan semua menu dan role yang ada di Ruang Asatidz
// 1. Inisialisasi table menu_structure (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS menu_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_group VARCHAR(100) NOT NULL,
    menu_key VARCHAR(100) UNIQUE NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    icon VARCHAR(100) NOT NULL,
    href VARCHAR(255) NOT NULL
)");

// Cek dan seed jika kosong
$res_cnt = $conn->query("SELECT COUNT(*) as cnt FROM menu_structure");
$count_struct = $res_cnt ? (int)$res_cnt->fetch_assoc()['cnt'] : 0;
if ($count_struct === 0) {
    $default_structure = [
        'Menu Utama' => [
            'absensi_pegawai' => ['href' => 'admin-absensi-pegawai.php', 'icon' => 'fa-qrcode'],
            'perizinan_pegawai' => ['href' => 'admin-pegawai-perizinan.php', 'icon' => 'fa-calendar-check'],
            'peraturan_role' => ['href' => 'admin-ustadz.php?view=peraturan_role', 'icon' => 'fa-file-contract'],
            'kpi_ustadz' => ['href' => 'admin-pegawai-kpi.php', 'icon' => 'fa-chalkboard-teacher'],
            'kpi_kepsek' => ['href' => 'admin-kepsek-kpi.php', 'icon' => 'fa-chart-pie'],
            'supervisi_mengajar' => ['href' => 'admin-supervisi-mengajar.php', 'icon' => 'fa-clipboard-check'],
            'ganti_password' => ['href' => 'ganti-password-ustadz.php', 'icon' => 'fa-key'],
        ],
        'Administrasi' => [
            'buku_induk' => ['href' => 'admin-buku-induk.php', 'icon' => 'fa-book-user'],
            'santri_tidak_masuk' => ['href' => 'admin-santri-tidak-masuk.php', 'icon' => 'fa-user-slash'],
            'akun_orangtua' => ['href' => 'admin-akun-orangtua.php', 'icon' => 'fa-users'],
            'leger_nilai' => ['href' => 'admin-leger.php', 'icon' => 'fa-book-reader'],
            'rapot_pkbm' => ['href' => 'admin-rapot-pkbm.php', 'icon' => 'fa-file-invoice'],
            'counseling_karir' => ['href' => 'admin-counseling-karir.php', 'icon' => 'fa-graduation-cap'],
            'rekap_keuangan' => ['href' => 'admin-rekap-spp.php', 'icon' => 'fa-file-invoice-dollar'],
            'rekap_uang_saku' => ['href' => 'admin-rekap-uang-saku.php', 'icon' => 'fa-wallet'],
            'sekolah_pembukuan' => ['href' => 'sekolah-pembukuan.php', 'icon' => 'fa-book'],
        ],
        'Asatidz' => [
            'kesediaan_mengajar' => ['href' => 'admin-pegawai-kesediaan.php', 'icon' => 'fa-clock'],
            'kalender_akademik' => ['href' => 'kalender-akademik.php', 'icon' => 'fa-calendar-alt'],
            'jadwal_pelajaran' => ['href' => 'admin-jadwal-pelajaran.php', 'icon' => 'fa-calendar-alt'],
            'santri_tidak_masuk_asatidz' => ['href' => 'admin-santri-tidak-masuk.php', 'icon' => 'fa-user-slash'],
            'master_silabus' => ['href' => 'admin-pegawai-silabus.php', 'icon' => 'fa-book-reader'],
            'ai_rpp' => ['href' => 'admin-pegawai-rpp.php', 'icon' => 'fa-magic'],
            'jurnal_mengajar' => ['href' => 'admin-pegawai-jurnal.php', 'icon' => 'fa-book-open'],
            'bank_nilai' => ['href' => 'admin-pegawai-nilai.php', 'icon' => 'fa-star-half-alt'],
            'master_kelas' => ['href' => 'admin-master-kelas.php', 'icon' => 'fa-school'],
            'master_mapel' => ['href' => 'admin-master-mapel.php', 'icon' => 'fa-book'],
            'kitab_rujukan' => ['href' => 'admin-kitab-rujukan.php', 'icon' => 'fa-book-open'],
        ],
        'Asrama' => [
            'dashboard_asrama' => ['href' => 'admin-ustadz.php?view=dashboard_asrama', 'icon' => 'fa-home-user'],
            'manajemen_halaqoh' => ['href' => 'admin-ustadz.php?view=halaqoh', 'icon' => 'fa-layer-group'],
            'laporan_setoran_rijal' => ['href' => 'admin-laporan-setoran-rijal.php', 'icon' => 'fa-mars'],
            'laporan_setoran_nisa' => ['href' => 'admin-laporan-setoran-nisa.php', 'icon' => 'fa-venus'],
            'rekap_ibadah_rijal' => ['href' => 'admin-ibadah-rijal.php', 'icon' => 'fa-mosque'],
            'rekap_ibadah_nisa' => ['href' => 'admin-ibadah-nisa.php', 'icon' => 'fa-kaaba'],
            'rekap_ibadah_mahad' => ['href' => 'admin-ibadah-mahad.php', 'icon' => 'fa-clipboard-check'],
        ],
        'Musyrif' => [
            'validasi_ibadah_musyrif' => ['href' => 'admin-validasi-ibadah-musyrif.php', 'icon' => 'fa-tasks'],
            'kontak_orangtua' => ['href' => 'admin-kontak-orangtua.php', 'icon' => 'fa-comments'],
            'cek_belajar_mandiri' => ['href' => 'admin-cek-belajar-mandiri.php', 'icon' => 'fa-book-reader'],
            'cek_kesehatan_santri' => ['href' => 'admin-cek-kesehatan-santri.php', 'icon' => 'fa-notes-medical'],
            'rapot_pkbm_musyrif' => ['href' => 'admin-rapot-pkbm.php', 'icon' => 'fa-file-invoice'],
            'mutabaah' => ['href' => 'admin-pegawai-mutabaah.php', 'icon' => 'fa-clipboard-list'],
            'laporan_adab' => ['href' => 'admin-pegawai-laporan-adab.php', 'icon' => 'fa-balance-scale'],
            'laporan_setoran_hafalan' => ['href' => 'admin-laporan-setoran-hafalan.php', 'icon' => 'fa-file-alt'],
            'kpi_musyrif' => ['href' => 'admin-ustadz.php?view=kpi_musyrif', 'icon' => 'fa-chart-line'],
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

// Load defined_menus from database
$defined_menus = [];
$res_db_menus = $conn->query("SELECT * FROM menu_structure ORDER BY sort_order ASC");
if ($res_db_menus) {
    while ($row = $res_db_menus->fetch_assoc()) {
        $group = $row['menu_group'];
        $key = $row['menu_key'];
        
        $default_titles = [
            'absensi_pegawai' => 'Absensi Kehadiran',
            'perizinan_pegawai' => 'Pengajuan Izin / Cuti',
            'peraturan_role' => 'Peraturan Pegawai',
            'kpi_ustadz' => 'KPI Pegawai',
            'kpi_kepsek' => 'KPI Kepala Sekolah',
            'supervisi_mengajar' => 'Supervisi Mengajar',
            'ganti_password' => 'Ganti Password',
            'buku_induk' => 'Buku Induk Santri',
            'santri_tidak_masuk' => 'Daftar Santri Tidak Masuk',
            'akun_orangtua' => 'Akun Orang Tua',
            'leger_nilai' => 'Leger Nilai Digital',
            'rapot_pkbm' => 'Raport Diknas PKBM (B & C)',
            'counseling_karir' => 'Pemetaan Karir & PTN (AI)',
            'rekap_keuangan' => 'Rekap Pembayaran Keuangan',
            'rekap_uang_saku' => 'Rekap Data Uang Saku',
            'sekolah_pembukuan' => 'Buku Kas Sekolah',
            'kesediaan_mengajar' => 'Kesediaan Mengajar',
            'kalender_akademik' => 'Kalender Akademik',
            'jadwal_pelajaran' => 'Jadwal Pelajaran',
            'santri_tidak_masuk_asatidz' => 'Daftar Santri Tidak Masuk',
            'master_silabus' => 'Master Silabus & CP',
            'ai_rpp' => 'AI Generator RPP',
            'jurnal_mengajar' => 'Jurnal Mengajar',
            'bank_nilai' => 'Bank Nilai (Input)',
            'master_kelas' => 'Master Kelas',
            'master_mapel' => 'Master Mapel',
            'kitab_rujukan' => 'Master Kitab Rujukan',
            'dashboard_asrama' => 'Dashboard Asrama',
            'manajemen_halaqoh' => 'Manajemen Halaqoh',
            'laporan_setoran_rijal' => 'Rekap Setoran Rijal',
            'laporan_setoran_nisa' => 'Rekap Setoran Nisa',
            'rekap_ibadah_rijal' => 'Rekap Ibadah Rijal',
            'rekap_ibadah_nisa' => 'Rekap Ibadah Nisa',
            'rekap_ibadah_mahad' => 'Rekap Ibadah Ma\'had',
            'validasi_ibadah_musyrif' => 'Validasi Ibadah',
            'kontak_orangtua' => 'Kontak Walisantri',
            'cek_belajar_mandiri' => 'Chek Belajar Mandiri',
            'cek_kesehatan_santri' => 'Chek Kesehatan',
            'rapot_pkbm_musyrif' => 'Raport Diknas Santri Binaan',
            'mutabaah' => 'Buku Mutaba\'ah Santri',
            'laporan_adab' => 'Laporan Kedisiplinan',
            'laporan_setoran_hafalan' => 'Setoran Hafalan',
            'kpi_musyrif' => 'KPI Musyrif',
            'rekap_uang_saku_musyrif' => 'Rekap Uang Saku Santri',
            'kurikulum_solopreneur_trainer' => 'Inkubator Solopreneur (AI)'
        ];
        
        $title = $default_titles[$key] ?? ucwords(str_replace('_', ' ', $key));
        $defined_menus[$group][$key] = $title;
    }
}

$group_order = [
    'Menu Utama' => 1,
    'Administrasi' => 2,
    'Asatidz' => 3,
    'Asrama' => 4,
    'Musyrif' => 5,
    'Keuangan Santri' => 6,
    'Solopreneur & AI' => 7
];
uksort($defined_menus, function($a, $b) use ($group_order) {
    $order_a = $group_order[$a] ?? 99;
    $order_b = $group_order[$b] ?? 99;
    return $order_a <=> $order_b;
});

$defined_roles = [
    'kepala_sekolah' => 'Kepala Sekolah',
    'sekretaris_sekolah' => 'Sekretaris Sekolah',
    'bendahara_sekolah' => 'Bendahara Sekolah',
    'admin_sekolah' => 'Admin Sekolah',
    'kepala_mahad' => "Kepala Ma'had",
    'kepala_asrama_rijal' => 'Kepala Asrama Rijal',
    'kepala_asrama_nisa' => 'Kepala Asrama Nisa',
    'musyrif' => 'Musyrif',
    'musyrifah' => 'Musyrifah',
    'ustadz' => 'Ustadz',
    'ustadzah' => 'Ustadzah',
    'tutor' => 'Tutor',
    'trainer' => 'Trainer',
];

// 2. Buat tabel permissions & custom labels jika belum ada & seed default
$conn->query("CREATE TABLE IF NOT EXISTS menu_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(100) UNIQUE NOT NULL,
    allowed_roles TEXT
)");

$conn->query("CREATE TABLE IF NOT EXISTS menu_custom_labels (
    menu_key VARCHAR(100) PRIMARY KEY,
    custom_label VARCHAR(255) NOT NULL
)");

// Seed default permissions for Jadwal Pelajaran if not present
$conn->query("INSERT IGNORE INTO menu_permissions (menu_key, allowed_roles) VALUES ('jadwal_pelajaran', 'kepala_sekolah,sekretaris_sekolah,bendahara_sekolah,admin_sekolah,kepala_mahad,kepala_asrama,musyrif,ustadz')");

// 3. Proses penyimpanan data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // A. Simpan Custom Labels
    if (isset($_POST['custom_labels']) && is_array($_POST['custom_labels'])) {
        $stmt_lbl = $conn->prepare("INSERT INTO menu_custom_labels (menu_key, custom_label) VALUES (?, ?) ON DUPLICATE KEY UPDATE custom_label = ?");
        foreach ($_POST['custom_labels'] as $key => $lbl) {
            $lbl = trim($lbl);
            if ($lbl !== '') {
                $stmt_lbl->bind_param("sss", $key, $lbl, $lbl);
                $stmt_lbl->execute();
            } else {
                $conn->query("DELETE FROM menu_custom_labels WHERE menu_key = '" . $conn->real_escape_string($key) . "'");
            }
        }
        $stmt_lbl->close();
    }

    // B. Simpan Permissions
    foreach ($defined_menus as $group => $menus) {
        foreach ($menus as $key => $title) {
            $allowed_roles = isset($_POST['permissions'][$key]) ? implode(',', $_POST['permissions'][$key]) : '';
            
            $stmt = $conn->prepare("INSERT INTO menu_permissions (menu_key, allowed_roles) VALUES (?, ?) ON DUPLICATE KEY UPDATE allowed_roles = ?");
            $stmt->bind_param("sss", $key, $allowed_roles, $allowed_roles);
            $stmt->execute();
        }
    }
    // C. Simpan Tata Letak (Drag & Drop)
    if (isset($_POST['layout_json']) && trim($_POST['layout_json']) !== '') {
        $layout = json_decode($_POST['layout_json'], true);
        if (is_array($layout)) {
            foreach ($layout as $item) {
                $menu_key = $conn->real_escape_string($item['menu_key']);
                $menu_group = $conn->real_escape_string($item['menu_group']);
                $sort_order = (int)$item['sort_order'];
                $conn->query("UPDATE menu_structure SET menu_group = '$menu_group', sort_order = $sort_order WHERE menu_key = '$menu_key'");
            }
            $pesan_sukses = "Tata letak dan urutan menu berhasil diperbarui!";
            
            // Reload defined_menus after updating to ensure the page renders with the new order!
            $defined_menus = [];
            $res_db_menus = $conn->query("SELECT * FROM menu_structure ORDER BY sort_order ASC");
            if ($res_db_menus) {
                while ($row = $res_db_menus->fetch_assoc()) {
                    $group = $row['menu_group'];
                    $key = $row['menu_key'];
                    $title = $default_titles[$key] ?? ucwords(str_replace('_', ' ', $key));
                    $defined_menus[$group][$key] = $title;
                }
            }
            uksort($defined_menus, function($a, $b) use ($group_order) {
                $order_a = $group_order[$a] ?? 99;
                $order_b = $group_order[$b] ?? 99;
                return $order_a <=> $order_b;
            });
        }
    } else {
        $pesan_sukses = "Pengaturan menu dan hak akses berhasil disimpan!";
    }
}

// 4. Ambil data custom labels & permissions yang sudah ada
$custom_labels = [];
$res_lbls = $conn->query("SELECT * FROM menu_custom_labels");
if ($res_lbls) {
    while ($row = $res_lbls->fetch_assoc()) {
        $custom_labels[$row['menu_key']] = $row['custom_label'];
    }
}

$permissions = [];
$res = $conn->query("SELECT * FROM menu_permissions");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $permissions[$row['menu_key']] = !empty($row['allowed_roles']) ? explode(',', $row['allowed_roles']) : [];
    }
}

$active_menu = 'manajemen_menu';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Menu | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; // Ini akan memanggil sidebar.php dari folder yayasan2 ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center"><button id="open-sidebar-yayasan2" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2></div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-sitemap text-amber-500 mr-2"></i>Manajemen Menu Ruang Asatidz</h1>
                <p class="text-gray-500 mt-1">Atur menu apa saja yang bisa dilihat oleh setiap peran/jabatan.</p>
            </div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            
            <!-- TAB NAVIGATION -->
            <div class="mb-6 flex gap-4 border-b border-gray-200">
                <button type="button" id="tab-btn-hak-akses" onclick="switchTab('hak-akses')" class="px-5 py-2.5 font-bold text-sm text-amber-600 border-b-2 border-amber-500 transition-all flex items-center gap-1.5 focus:outline-none">
                    <i class="fas fa-key text-base"></i> Hak Akses Peran
                </button>
                <button type="button" id="tab-btn-tata-letak" onclick="switchTab('tata-letak')" class="px-5 py-2.5 font-bold text-sm text-gray-500 hover:text-amber-500 border-b-2 border-transparent transition-all flex items-center gap-1.5 focus:outline-none">
                    <i class="fas fa-arrows-alt text-base"></i> Tata Letak & Urutan Menu
                </button>
            </div>

            <!-- TAB 1: HAK AKSES PERAN -->
            <div id="tab-content-hak-akses" class="tab-pane">
                <form action="manajemen-menu.php" method="POST">
                    <div class="relative bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Floating Left Scroll Button (Sticky on top of content) -->
                        <button type="button" onclick="scrollMenuTable(-300)" class="absolute left-[260px] top-1/2 -translate-y-1/2 bg-amber-500 hover:bg-amber-600 text-gray-950 w-10 h-10 rounded-full flex items-center justify-center shadow-lg z-30 transition-all opacity-85 hover:opacity-100 border border-amber-300">
                            <i class="fas fa-chevron-left text-lg"></i>
                        </button>
                        
                        <!-- Floating Right Scroll Button -->
                        <button type="button" onclick="scrollMenuTable(300)" class="absolute right-4 top-1/2 -translate-y-1/2 bg-amber-500 hover:bg-amber-600 text-gray-955 w-10 h-10 rounded-full flex items-center justify-center shadow-lg z-30 transition-all opacity-85 hover:opacity-100 border border-amber-300">
                            <i class="fas fa-chevron-right text-lg"></i>
                        </button>

                        <div id="menu-table-wrapper" class="overflow-x-auto overflow-y-auto max-h-[70vh] scroll-smooth">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase sticky top-0 left-0 bg-gray-50 z-20 border-r border-gray-200 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.1)] w-[250px] min-w-[250px]">Nama Menu</th>
                                        <?php foreach ($defined_roles as $role_key => $role_label): ?>
                                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase sticky top-0 bg-gray-50 z-10"><?= $role_label ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($defined_menus as $group => $menus): ?>
                                        <tr class="bg-gray-100">
                                            <td colspan="<?= count($defined_roles) + 1 ?>" class="px-6 py-2 text-sm font-bold text-gray-600 uppercase sticky left-0 z-10 bg-gray-100 border-r border-gray-200"><?= $group ?></td>
                                        </tr>
                                        <?php foreach ($menus as $key => $title): 
                                            $display_title = isset($custom_labels[$key]) ? $custom_labels[$key] : $title;
                                        ?>
                                         <tr class="hover:bg-gray-50 <?= ($key === 'jadwal_pelajaran') ? 'bg-amber-50/50' : '' ?>">
                                             <td class="px-4 py-3 font-medium text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50 z-10 border-r border-gray-150 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)] w-[250px] min-w-[250px]">
                                                 <div class="flex flex-col gap-1">
                                                     <input type="text" name="custom_labels[<?= $key ?>]" value="<?= htmlspecialchars($display_title) ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-xs w-full focus:ring-2 focus:ring-amber-500 font-semibold" placeholder="<?= htmlspecialchars($title) ?>">
                                                     <span class="text-[9px] text-gray-400 font-normal">Default: <?= htmlspecialchars($title) ?></span>
                                                 </div>
                                             </td>
                                            <?php foreach ($defined_roles as $role_key => $role_label): 
                                                $checked = isset($permissions[$key]) && in_array($role_key, $permissions[$key]) ? 'checked' : '';
                                            ?>
                                                <td class="px-6 py-4 text-center">
                                                    <input type="checkbox" name="permissions[<?= $key ?>][]" value="<?= $role_key ?>" <?= $checked ?> class="w-5 h-5 text-amber-600 border-gray-300 rounded focus:ring-amber-500">
                                                </td>
                                            <?php endforeach; ?>
                                         </tr>
                                         <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-end">
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-3 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Hak Akses & Nama</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- TAB 2: TATA LETAK & DRAG AND DROP -->
            <div id="tab-content-tata-letak" class="tab-pane hidden">
                <p class="text-xs text-gray-500 mb-6 bg-amber-50 border border-amber-200 text-amber-900 p-3 rounded-xl flex items-center gap-1.5"><i class="fas fa-info-circle text-sm text-amber-600"></i> Seret kartu menu ke atas/bawah untuk mengurutkan, atau seret ke kolom lain untuk memindahkan kelompok/kategori menu.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                    <?php foreach ($defined_menus as $group => $menus): ?>
                        <div class="menu-column bg-gray-100 p-4 rounded-xl border border-gray-250 flex flex-col gap-3 min-h-[300px]" data-group="<?= htmlspecialchars($group) ?>">
                            <h3 class="font-bold text-gray-800 text-xs border-b pb-2 uppercase tracking-wider text-slate-700 flex items-center justify-between">
                                <span><?= $group ?></span>
                                <span class="bg-slate-200 text-slate-700 px-2 py-0.5 rounded-full text-[9px]"><?= count($menus) ?></span>
                            </h3>
                            <div class="sortable-list flex-1 space-y-2 pb-10" id="list-<?= str_replace([' ', '&'], ['-', 'and'], strtolower($group)) ?>">
                                <?php foreach ($menus as $key => $title): 
                                    $display_title = isset($custom_labels[$key]) ? $custom_labels[$key] : $title;
                                ?>
                                    <div class="menu-item-card bg-white p-3 rounded-lg border border-gray-200 shadow-sm flex items-center justify-between cursor-grab active:cursor-grabbing text-xs hover:border-amber-300 transition" data-key="<?= $key ?>">
                                        <span class="font-bold text-slate-750 flex items-center gap-2">
                                            <i class="fas fa-grip-vertical text-gray-400"></i>
                                            <span><?= htmlspecialchars($display_title) ?></span>
                                        </span>
                                        <span class="text-[9px] text-gray-450 font-mono bg-gray-50 px-1.5 py-0.5 rounded"><?= $key ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST" id="form-layout" class="mt-8 flex justify-end">
                    <input type="hidden" name="layout_json" id="layout-json-input">
                    <button type="submit" onclick="submitLayoutForm()" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-3 px-8 rounded-lg shadow-md transition flex items-center gap-1.5">
                        <i class="fas fa-save text-sm"></i> Simpan Urutan & Tata Letak
                    </button>
                </form>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        }); 
        document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        });

        function scrollMenuTable(amount) {
            const wrapper = document.getElementById('menu-table-wrapper');
            if (wrapper) {
                wrapper.scrollBy({ left: amount, behavior: 'smooth' });
            }
        }

        // TABS FUNCTIONALITY
        function switchTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            document.getElementById('tab-content-' + tabId).classList.remove('hidden');

            const btnAkses = document.getElementById('tab-btn-hak-akses');
            const btnTata = document.getElementById('tab-btn-tata-letak');

            if (tabId === 'hak-akses') {
                btnAkses.className = "px-5 py-2.5 font-bold text-sm text-amber-600 border-b-2 border-amber-500 transition-all flex items-center gap-1.5 focus:outline-none";
                btnTata.className = "px-5 py-2.5 font-bold text-sm text-gray-500 hover:text-amber-500 border-b-2 border-transparent transition-all flex items-center gap-1.5 focus:outline-none";
            } else {
                btnTata.className = "px-5 py-2.5 font-bold text-sm text-amber-600 border-b-2 border-amber-500 transition-all flex items-center gap-1.5 focus:outline-none";
                btnAkses.className = "px-5 py-2.5 font-bold text-sm text-gray-500 hover:text-amber-500 border-b-2 border-transparent transition-all flex items-center gap-1.5 focus:outline-none";
            }
        }

        // INITIALIZE SORTABLE JS
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.sortable-list').forEach(list => {
                new Sortable(list, {
                    group: 'shared-menu',
                    animation: 150,
                    ghostClass: 'bg-amber-50',
                    chosenClass: 'border-amber-500'
                });
            });
        });

        // GATHER AND SUBMIT LAYOUT
        function submitLayoutForm() {
            const layout = [];
            let order = 0;
            document.querySelectorAll('.menu-column').forEach(column => {
                const groupName = column.dataset.group;
                column.querySelectorAll('.menu-item-card').forEach(item => {
                    layout.push({
                        menu_key: item.dataset.key,
                        menu_group: groupName,
                        sort_order: order++
                    });
                });
            });
            document.getElementById('layout-json-input').value = JSON.stringify(layout);
        }
    </script>
</body>
</html>