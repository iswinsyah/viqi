<?php
// Halaman Dashboard Utama Yayasan
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'dashboard';

// --- SETUP & AMBIL PENGATURAN GAJI ---
$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_c INT DEFAULT 2000000");
$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_b INT DEFAULT 2500000");
$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_a INT DEFAULT 3000000");

$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_gaji (id INT PRIMARY KEY DEFAULT 1, gaji_grade_c INT DEFAULT 2000000, gaji_grade_b INT DEFAULT 2500000, gaji_grade_a INT DEFAULT 3000000)");
$conn->query("INSERT IGNORE INTO pengaturan_gaji (id, gaji_grade_c, gaji_grade_b, gaji_grade_a) VALUES (1, 2000000, 2500000, 3000000)");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_gaji'])) {
    $gaji_c = (int)$_POST['gaji_grade_c'];
    $gaji_b = (int)$_POST['gaji_grade_b'];
    $gaji_a = (int)$_POST['gaji_grade_a'];
    $conn->query("UPDATE pengaturan_gaji SET gaji_grade_c=$gaji_c, gaji_grade_b=$gaji_b, gaji_grade_a=$gaji_a WHERE id=1");
    $pesan_sukses = "Pengaturan Gaji Ustadz berdasarkan Grade berhasil diperbarui!";
}

$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji->fetch_assoc();
$gaji_grade_c = $data_gaji['gaji_grade_c'] ?? 2000000;
$gaji_grade_b = $data_gaji['gaji_grade_b'] ?? 2500000;
$gaji_grade_a = $data_gaji['gaji_grade_a'] ?? 3000000;

// Hitung total asatidz terdaftar
$q_asatidz = $conn->query("SELECT COUNT(id) AS tot FROM akun_ustadz");
$total_asatidz = $q_asatidz ? ($q_asatidz->fetch_assoc()['tot'] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Yayasan | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../index.html" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium hidden sm:flex items-center"><i class="fas fa-external-link-alt mr-2"></i> Lihat Website</a>
                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shadow-sm">Y</div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Selamat Datang di Ruang Yayasan</h1>
                <p class="text-gray-500 mt-1">Area eksklusif untuk memantau dan mengatur kebijakan inti pesantren.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center md:col-span-3">
                    <div class="p-4 rounded-full bg-indigo-100 text-indigo-600 mr-4"><i class="fas fa-users-cog text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Akun Asatidz</p><p class="text-2xl font-bold text-gray-900"><?= $total_asatidz ?></p></div>
                </div>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM PENGATURAN GAJI & BONUS -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="font-bold text-gray-800 mb-4 border-b pb-2"><i class="fas fa-money-bill-wave text-indigo-500 mr-2"></i>Pengaturan Gaji & Bonus Ustadz</h2>
                <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="update_gaji" value="1">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Gaji Pokok Grade C (Rp)</label>
                        <input type="number" name="gaji_grade_c" value="<?= $gaji_grade_c ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Gaji Grade B (Rp)</label>
                        <input type="number" name="gaji_grade_b" value="<?= $gaji_grade_b ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Gaji Grade A (Rp)</label>
                        <input type="number" name="gaji_grade_a" value="<?= $gaji_grade_a ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-lg transition shadow-sm flex justify-center items-center"><i class="fas fa-save mr-2"></i> Simpan Setting</button>
                    </div>
                </form>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-indigo-800 max-w-3xl">
                <h3 class="font-bold mb-2 flex items-center"><i class="fas fa-shield-alt mr-2 text-indigo-600"></i>Otoritas Akses Ruang Asatidz</h3>
                <p class="text-sm leading-relaxed mb-4">Hanya Asatidz (Guru/Pegawai) yang telah Anda buatkan akunnya di menu <b>Daftar Asatidz</b> yang dapat melakukan Login ke dalam Ruang Asatidz dari halaman depan website.</p>
                <a href="asatidz.php" class="inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition font-medium text-sm">Kelola Akun Asatidz Sekarang <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); }); document.getElementById('close-sidebar-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); });</script>
</body>
</html>