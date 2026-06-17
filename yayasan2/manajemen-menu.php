<?php
require_once 'auth.php';
require_once '../koneksi.php';

// 1. Definisikan semua menu dan role yang ada di Ruang Asatidz
$defined_menus = [
    'Menu Utama' => [
        'dashboard_pegawai' => 'Dashboard Pegawai',
    ],
    'Administrasi' => [
        'buku_induk' => 'Buku Induk Santri',
        'akun_orangtua' => 'Akun Orang Tua',
        'leger_nilai' => 'Leger Nilai Digital',
        'rekap_keuangan' => 'Rekap Pembayaran Keuangan',
        'rekap_uang_saku' => 'Rekap Uang Saku',
    ],
    'Asatidz' => [
        'jurnal_mengajar' => 'Jurnal Mengajar',
        'master_silabus' => 'Master Silabus & CP',
        'kesediaan_mengajar' => 'Kesediaan Mengajar',
        'ai_rpp' => 'AI Generator RPP',
        'bank_nilai' => 'Bank Nilai Akademik',
    ],
    'Musyrif' => [
        'dashboard_asrama' => 'Dashboard Asrama',
        'mutabaah' => 'Buku Mutaba\'ah Santri',
        'setor_hafalan' => 'Setoran Hafalan',
        'manajemen_halaqoh' => 'Manajemen Halaqoh',
        'jurnal_musyrif' => 'Jurnal Kegiatan Musyrif',
        'laporan_adab' => 'Laporan Kedisiplinan',
        'penilaian_adab' => 'Penilaian Adab (Rapor)',
        'absensi_pegawai' => 'Absensi Kehadiran',
    ],
    'Keuangan Santri' => [
        'rekap_uang_saku_musyrif' => 'Rekap Uang Saku Santri',
    ],
    'Kinerja & Akun' => [
        'amanah_asatidz' => 'Menu Amanah',
        'kpi_ustadz' => 'KPI Ustadz',
        'kpi_musyrif' => 'KPI Musyrif',
        'ganti_password' => 'Ganti Password',
    ]
];

$defined_roles = [
    'kepala_sekolah' => 'Kepala Sekolah',
    'sekretaris_sekolah' => 'Sekretaris Sekolah',
    'bendahara_sekolah' => 'Bendahara Sekolah',
    'admin_sekolah' => 'Admin Sekolah',
    'kepala_asrama' => 'Kepala Asrama',
    'musyrif' => 'Musyrif',
    'ustadz' => 'Ustadz',
];

// 2. Buat tabel permissions jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS menu_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_key VARCHAR(100) UNIQUE NOT NULL,
    allowed_roles TEXT
)");

// 3. Proses penyimpanan data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['permissions'])) {
    foreach ($defined_menus as $group => $menus) {
        foreach ($menus as $key => $title) {
            $allowed_roles = isset($_POST['permissions'][$key]) ? implode(',', $_POST['permissions'][$key]) : '';
            
            $stmt = $conn->prepare("INSERT INTO menu_permissions (menu_key, allowed_roles) VALUES (?, ?) ON DUPLICATE KEY UPDATE allowed_roles = ?");
            $stmt->bind_param("sss", $key, $allowed_roles, $allowed_roles);
            $stmt->execute();
        }
    }
    $pesan_sukses = "Pengaturan hak akses menu berhasil disimpan!";
}

// 4. Ambil data permissions yang sudah ada
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
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase sticky left-0 bg-gray-50 z-10">Nama Menu</th>
                                    <?php foreach ($defined_roles as $role_key => $role_label): ?>
                                        <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase"><?= $role_label ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($defined_menus as $group => $menus): ?>
                                    <tr class="bg-gray-100">
                                        <td colspan="<?= count($defined_roles) + 1 ?>" class="px-6 py-2 text-sm font-bold text-gray-600 uppercase"><?= $group ?></td>
                                    </tr>
                                    <?php foreach ($menus as $key => $title): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-medium text-gray-900 sticky left-0 bg-white group-hover:bg-gray-50 z-10"><?= $title ?></td>
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
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-3 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Pengaturan Akses</button>
                    </div>
                </div>
            </form>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });</script>
</body>
</html>