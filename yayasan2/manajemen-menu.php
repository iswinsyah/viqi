<?php
require_once 'auth.php';
require_once '../koneksi.php';

// 1. Definisikan semua menu dan role yang ada di Ruang Asatidz
$defined_menus = [
    'Menu Utama' => [
        'absensi_pegawai' => 'Absensi Kehadiran',
        'perizinan_pegawai' => 'Pengajuan Izin / Cuti',
        'peraturan_role' => 'Peraturan Pegawai',
        'kpi_ustadz' => 'KPI Pegawai',
        'ganti_password' => 'Ganti Password',
    ],
    'Administrasi' => [
        'buku_induk' => 'Buku Induk Santri',
        'akun_orangtua' => 'Akun Orang Tua',
        'leger_nilai' => 'Leger Nilai Digital',
        'counseling_karir' => 'Pemetaan Karir & PTN (AI)',
        'rekap_keuangan' => 'Rekap Pembayaran Keuangan',
        'rekap_uang_saku' => 'Rekap Uang Saku',
        'sekolah_pembukuan' => 'Buku Kas Sekolah',
        'rapot_pkbm' => 'Raport Diknas PKBM (B & C)',
        'santri_tidak_masuk' => 'Daftar Santri Tidak Masuk',
    ],
    'Asatidz' => [
        'kesediaan_mengajar' => 'Kesediaan Mengajar',
        'kalender_akademik' => 'Kalender Akademik',
        'jadwal_pelajaran' => 'Jadwal Pelajaran',
        'master_silabus' => 'Master Silabus & CP',
        'ai_rpp' => 'AI Generator RPP',
        'jurnal_mengajar' => 'Jurnal Mengajar',
        'bank_nilai' => 'Bank Nilai Akademik',
        'master_kelas' => 'Master Kelas',
        'master_mapel' => 'Master Mapel',
        'kitab_rujukan' => 'Master Kitab Rujukan',
    ],
    'Asrama' => [
        'dashboard_asrama' => 'Dashboard Asrama',
        'manajemen_halaqoh' => 'Manajemen Halaqoh',
        'laporan_setoran_rijal' => 'Rekap Setoran Rijal',
        'laporan_setoran_nisa' => 'Rekap Setoran Nisa',
        'rekap_ibadah_rijal' => 'Rekap Ibadah Rijal',
        'rekap_ibadah_nisa' => 'Rekap Ibadah Nisa',
        'rekap_ibadah_mahad' => 'Rekap Ibadah Ma\'had',
    ],
    'Musyrif' => [
        'validasi_ibadah_musyrif' => 'Validasi Ibadah',
        'kontak_orangtua' => 'Kontak Walisantri',
        'cek_belajar_mandiri' => 'Chek Belajar Mandiri',
        'cek_kesehatan_santri' => 'Chek Kesehatan',
        'rapot_pkbm_musyrif' => 'Raport Diknas Santri Binaan',
        'mutabaah' => 'Buku Mutaba\'ah Santri',
        'jurnal_pagi_musyrif' => 'Jurnal Piket Pagi (07.00-13.00)',
        'jurnal_musyrif' => 'Jurnal Kegiatan Musyrif',
        'laporan_adab' => 'Laporan Kedisiplinan',
        'laporan_setoran_hafalan' => 'Setoran Hafalan',
        'kpi_musyrif' => 'KPI Musyrif',
    ],
    'Keuangan Santri' => [
        'rekap_uang_saku_musyrif' => 'Rekap Uang Saku Santri',
    ],
    'Solopreneur & AI' => [
        'kurikulum_solopreneur_trainer' => 'Inkubator Solopreneur (AI)',
    ]
];

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
    $pesan_sukses = "Pengaturan menu dan hak akses berhasil disimpan!";
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
            
            <form action="manajemen-menu.php" method="POST">
                <div class="relative bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    
                    <!-- Floating Left Scroll Button (Sticky on top of content) -->
                    <button type="button" onclick="scrollMenuTable(-300)" class="absolute left-[260px] top-1/2 -translate-y-1/2 bg-amber-500 hover:bg-amber-600 text-gray-955 w-10 h-10 rounded-full flex items-center justify-center shadow-lg z-30 transition-all opacity-85 hover:opacity-100 no-print border border-amber-300">
                        <i class="fas fa-chevron-left text-lg"></i>
                    </button>
                    
                    <!-- Floating Right Scroll Button -->
                    <button type="button" onclick="scrollMenuTable(300)" class="absolute right-4 top-1/2 -translate-y-1/2 bg-amber-500 hover:bg-amber-600 text-gray-955 w-10 h-10 rounded-full flex items-center justify-center shadow-lg z-30 transition-all opacity-85 hover:opacity-100 no-print border border-amber-300">
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
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-3 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Pengaturan Menu</button>
                    </div>
                </div>
            </form>
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
    </script>
</body>
</html>