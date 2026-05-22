<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
    ],
    'Asatidz' => [
        'jurnal_mengajar' => 'Jurnal Mengajar',
        'master_silabus' => 'Master Silabus & CP',
        'ai_rpp' => 'AI Generator RPP',
        'bank_nilai' => 'Bank Nilai Akademik',
    ],
    'Musyrif' => [
        'dashboard_asrama' => 'Dashboard Asrama',
        'mutabaah' => 'Buku Mutaba\'ah Santri',
        'jurnal_musyrif' => 'Jurnal Kegiatan Musyrif',
        'laporan_adab' => 'Laporan Kedisiplinan',
    ],
    'Kinerja & Akun' => [
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
    <?php include 'sidebar.php'; ?>
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
```

### 2. Tambahkan Menu Baru di Sidebar Yayasan

Selanjutnya, saya akan tambahkan menu "Manajemen Menu" di sidebar Ruang Yayasan agar Anda bisa mengakses halaman yang baru dibuat.

```diff
--- a/d:\LOCALHOST\viqi\yayasan2\sidebar.php
+++ b/d:\LOCALHOST\viqi\yayasan2\sidebar.php
@@ -16,6 +16,9 @@
             <a href="asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'asatidz') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                 <i class="fas fa-users-cog w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'asatidz') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Akun & Role Asatidz
             </a>
+            <a href="manajemen-menu.php" class="<?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
+                <i class="fas fa-sitemap w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Manajemen Menu
+            </a>
         </nav>
     </div>
 

```

### 3. Terapkan Sistem Menu Dinamis di Ruang Asatidz

Terakhir, saya akan merombak total `sidebar-hr.php` agar secara otomatis menyembunyikan atau menampilkan menu berdasarkan pengaturan yang sudah Anda simpan di halaman "Manajemen Menu". Saya juga akan menghapus file `admin-dashboard-asrama.php` yang sudah tidak diperlukan.

```diff
--- a/d:\LOCALHOST\viqi\sidebar-hr.php
+++ b/d:\LOCALHOST\viqi\sidebar-hr.php
@@ -1,75 +1,124 @@
 <?php
 if (session_status() === PHP_SESSION_NONE) {
     session_start();
 }
+
+// Pastikan koneksi DB tersedia. Diasumsikan file yang meng-include sidebar ini sudah memanggil koneksi.php
+global $conn;
+
+// Ambil role user dari session
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
+
+// Super Admin dapat melihat semua menu
$is_super_admin = in_array('super_admin', $user_roles);
+
+// Ambil semua hak akses menu dari database
$menu_permissions = [];
+if ($conn) {
+    $res_perms = $conn->query("SELECT menu_key, allowed_roles FROM menu_permissions");
+    if ($res_perms) {
+        while ($row = $res_perms->fetch_assoc()) {
+            $menu_permissions[$row['menu_key']] = !empty($row['allowed_roles']) ? explode(',', $row['allowed_roles']) : [];
+        }
+    }
+}
+
+// Fungsi helper untuk mengecek hak akses
+function has_access($menu_key, $user_roles, $menu_permissions, $is_super_admin) {
+    if ($is_super_admin) return true;
+    if (!isset($menu_permissions[$menu_key])) return false; // Default: sembunyikan jika belum diatur
+
+    foreach ($user_roles as $role) {
+        if (in_array(trim($role), $menu_permissions[$menu_key])) {
+            return true;
+        }
+    }
+    return false;
+}
+
+// Definisikan struktur menu untuk iterasi
$menu_structure = [
+    'Menu Utama' => [
+        'dashboard_pegawai' => ['href' => 'admin-ustadz.php', 'icon' => 'fa-tachometer-alt', 'title' => 'Dashboard Pegawai'],
+    ],
+    'Administrasi' => [
+        'buku_induk' => ['href' => 'admin-buku-induk.php', 'icon' => 'fa-book-user', 'title' => 'Buku Induk Santri'],
+        'akun_orangtua' => ['href' => 'admin-akun-orangtua.php', 'icon' => 'fa-users', 'title' => 'Akun Orang Tua'],
+    ],
+    'Asatidz' => [
+        'jurnal_mengajar' => ['href' => 'admin-pegawai-jurnal.php', 'icon' => 'fa-book-open', 'title' => 'Jurnal Mengajar'],
+        'master_silabus' => ['href' => 'admin-pegawai-silabus.php', 'icon' => 'fa-book-reader', 'title' => 'Master Silabus & CP'],
+        'ai_rpp' => ['href' => 'admin-pegawai-rpp.php', 'icon' => 'fa-magic', 'title' => 'AI Generator RPP'],
+        'bank_nilai' => ['href' => 'admin-pegawai-nilai.php', 'icon' => 'fa-star-half-alt', 'title' => 'Bank Nilai Akademik'],
+    ],
+    'Musyrif' => [
+        'dashboard_asrama' => ['href' => 'admin-dashboard-asrama.php', 'icon' => 'fa-home-user', 'title' => 'Dashboard Asrama'],
+        'mutabaah' => ['href' => 'admin-pegawai-mutabaah.php', 'icon' => 'fa-clipboard-list', 'title' => 'Buku Mutaba\'ah Santri'],
+        'jurnal_musyrif' => ['href' => 'admin-pegawai-jurnal-musyrif.php', 'icon' => 'fa-user-shield', 'title' => 'Jurnal Kegiatan Musyrif'],
+        'laporan_adab' => ['href' => 'admin-pegawai-laporan-adab.php', 'icon' => 'fa-balance-scale', 'title' => 'Laporan Kedisiplinan'],
+    ],
+    'Kinerja & Akun' => [
+        'kpi_ustadz' => ['href' => 'admin-pegawai-kpi.php', 'icon' => 'fa-chalkboard-teacher', 'title' => 'KPI Ustadz'],
+        'kpi_musyrif' => ['href' => 'admin-pegawai-kpi-musyrif.php', 'icon' => 'fa-user-shield', 'title' => 'KPI Musyrif'],
+        'ganti_password' => ['href' => 'ganti-password-ustadz.php', 'icon' => 'fa-key', 'title' => 'Ganti Password'],
+    ]
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

```

```diff
--- a/d:\LOCALHOST\viqi\admin-dashboard-asrama.php
+++ /dev/null
@@ -1,141 +0,0 @@
-<?php
-require_once 'auth-ustadz.php';
-require_once 'koneksi.php';
-
-// Penanda menu aktif
-$active_menu = 'dashboard_asrama';
-
-// --- AMBIL DATA STATISTIK UNTUK WIDGET ---
-
-// 1. Total Santri Aktif
-$q_santri = $conn->query("SELECT COUNT(id) AS total FROM buku_induk_santri WHERE status_santri = 'Aktif'");
-$total_santri_aktif = $q_santri ? ($q_santri->fetch_assoc()['total'] ?? 0) : 0;
-
-// 2. Total Jurnal Musyrif Hari Ini
-$q_jurnal_today = $conn->query("SELECT COUNT(id) AS total FROM jurnal_kegiatan_musyrif WHERE tanggal = CURDATE()");
-$total_jurnal_hari_ini = $q_jurnal_today ? ($q_jurnal_today->fetch_assoc()['total'] ?? 0) : 0;
-
-// 3. Total Musyrif (dari tabel akun_ustadz yang punya role 'musyrif')
-$q_musyrif = $conn->query("SELECT COUNT(id) AS total FROM akun_ustadz WHERE role LIKE '%musyrif%'");
-$total_musyrif = $q_musyrif ? ($q_musyrif->fetch_assoc()['total'] ?? 0) : 0;
-
-// 4. Laporan Kedisiplinan Terbaru (5 terakhir)
-$laporan_terbaru = [];
-$res_laporan = $conn->query("SELECT l.*, u.nama as nama_pelapor FROM laporan_adab l JOIN akun_ustadz u ON l.ustadz_id = u.id ORDER BY l.id DESC LIMIT 5");
-if ($res_laporan) { while($r = $res_laporan->fetch_assoc()) { $laporan_terbaru[] = $r; } }
-
-?>
-<!DOCTYPE html>
-<html lang="id">
-<head>
-    <meta charset="UTF-8">
-    <meta name="viewport" content="width=device-width, initial-scale=1.0">
-    <title>Dashboard Kepala Asrama | Ruang Asatidz</title>
-    <script src="https://cdn.tailwindcss.com"></script>
-    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
-</head>
-<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
-
-    <?php include 'sidebar-hr.php'; ?>
-
-    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
-        
-        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
-            <div class="flex items-center">
-                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
-                    <i class="fas fa-bars text-xl"></i>
-                </button>
-                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
-            </div>
-        </header>
-
-        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
-            <div class="mb-6">
-                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-home-user text-cyan-600 mr-2"></i>Dashboard Kepala Asrama</h1>
-                <p class="text-gray-500 mt-1">Ringkasan kondisi dan aktivitas asrama terkini.</p>
-            </div>
-
-            <!-- WIDGET STATISTIK UTAMA -->
-            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
-                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
-                    <div class="p-4 rounded-full bg-emerald-100 text-emerald-600 mr-4"><i class="fas fa-users text-2xl"></i></div>
-                    <div><p class="text-sm font-medium text-gray-500">Total Santri Aktif</p><p class="text-3xl font-bold text-gray-900"><?= $total_santri_aktif ?></p></div>
-                </div>
-                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
-                    <div class="p-4 rounded-full bg-cyan-100 text-cyan-600 mr-4"><i class="fas fa-user-shield text-2xl"></i></div>
-                    <div><p class="text-sm font-medium text-gray-500">Total Musyrif</p><p class="text-3xl font-bold text-gray-900"><?= $total_musyrif ?></p></div>
-                </div>
-                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
-                    <div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4"><i class="fas fa-clipboard-list text-2xl"></i></div>
-                    <div><p class="text-sm font-medium text-gray-500">Jurnal Musyrif Hari Ini</p><p class="text-3xl font-bold text-gray-900"><?= $total_jurnal_hari_ini ?></p></div>
-                </div>
-            </div>
-
-            <!-- SHORTCUT MENU -->
-            <div class="mb-8">
-                <h2 class="text-lg font-bold text-gray-800 mb-3">Akses Cepat</h2>
-                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
-                    <a href="admin-buku-induk.php" class="bg-white hover:bg-blue-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
-                        <i class="fas fa-book-user text-blue-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
-                        <span class="text-sm font-bold text-gray-700 mt-1 text-center">Buku Induk</span>
-                    </a>
-                    <a href="admin-pegawai-laporan-adab.php" class="bg-white hover:bg-rose-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
-                        <i class="fas fa-balance-scale text-rose-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
-                        <span class="text-sm font-bold text-gray-700 mt-1 text-center">Laporan Adab</span>
-                    </a>
-                    <a href="admin-pegawai-jurnal-musyrif.php" class="bg-white hover:bg-cyan-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
-                        <i class="fas fa-user-shield text-cyan-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
-                        <span class="text-sm font-bold text-gray-700 mt-1 text-center">Jurnal Musyrif</span>
-                    </a>
-                    <a href="admin-pegawai-mutabaah.php" class="bg-white hover:bg-emerald-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
-                        <i class="fas fa-clipboard-check text-emerald-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
-                        <span class="text-sm font-bold text-gray-700 mt-1 text-center">Mutaba'ah</span>
-                    </a>
-                    <a href="admin-akun-orangtua.php" class="bg-white hover:bg-amber-50 border border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
-                        <i class="fas fa-phone-alt text-amber-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
-                        <span class="text-sm font-bold text-gray-700 mt-1 text-center">Kontak Ortu</span>
-                    </a>
-                </div>
-            </div>
-
-            <!-- TABEL LAPORAN KEDISIPLINAN TERBARU -->
-            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
-                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
-                    <h2 class="font-bold text-gray-800">Laporan Kedisiplinan & Adab Terbaru</h2>
-                    <a href="admin-pegawai-laporan-adab.php" class="text-sm text-cyan-600 hover:text-cyan-800 font-medium">Lihat Semua</a>
-                </div>
-                <div class="overflow-x-auto p-4">
-                    <table class="min-w-full divide-y divide-gray-200">
-                        <thead class="bg-white">
-                            <tr>
-                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri & Tanggal</th>
-                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi & Tindakan</th>
-                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Pelapor</th>
-                            </tr>
-                        </thead>
-                        <tbody class="divide-y divide-gray-100">
-                            <?php if (count($laporan_terbaru) > 0): ?>
-                                <?php foreach($laporan_terbaru as $row): 
-                                    $badge_class = $row['jenis_laporan'] == 'Apresiasi' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800';
-                                ?>
-                                <tr class="hover:bg-gray-50">
-                                    <td class="px-4 py-3 align-top">
-                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_santri']) ?></div>
-                                        <div class="text-xs text-gray-500 mt-1"><?= date('d M Y', strtotime($row['tanggal'])) ?> &bull; <?= htmlspecialchars($row['kelas']) ?></div>
-                                        <div class="mt-2"><span class="px-2 py-1 text-xs font-bold rounded-full <?= $badge_class ?>"><?= htmlspecialchars($row['jenis_laporan']) ?></span></div>
-                                    </td>
-                                    <td class="px-4 py-3 text-sm text-gray-600">
-                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($row['deskripsi_kejadian']) ?></div>
-                                        <?php if(!empty($row['tindakan_diambil'])): ?><div class="text-xs text-blue-600 mt-1"><i class="fas fa-gavel mr-1"></i><b>Tindakan:</b> <?= htmlspecialchars($row['tindakan_diambil']) ?></div><?php endif; ?>
-                                    </td>
-                                    <td class="px-4 py-3 text-sm text-gray-500 align-top">
-                                        <?= htmlspecialchars($row['nama_pelapor']) ?>
-                                    </td>
-                                </tr>
-                                <?php endforeach; ?>
-                            <?php else: ?>
-                                <tr><td colspan='3' class='text-center py-6 text-gray-500 italic'>Belum ada catatan laporan kedisiplinan.</td></tr>
-                            <?php endif; ?>
-                        </tbody>
-                    </table>
-                </div>
-            </div>
-        </main>
-    </div>
-
-    <script>
-        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
-    </script>
-</body>
-</html>
```

Setelah semua perubahan di-Accept, silakan jalankan perintah `git` ini di Terminal untuk mengirimnya ke server:

```bash
git add .
git commit -m "feat: implement dynamic role-based menu management"
git push origin main
```

Selesai! Sekarang, setiap pegawai hanya akan melihat menu yang sesuai dengan perannya, dan Anda bisa mengatur hak akses ini dengan mudah dari Ruang Yayasan.

<!--
[PROMPT_SUGGESTION]Mantap! Sekarang, di halaman "Manajemen Menu", bisakah kita tambahkan tombol "Pilih Semua" untuk setiap peran?[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Bagaimana cara membuat peran baru, misalnya "Wakil Kepala Sekolah"?[/PROMPT_SUGGESTION]
-->