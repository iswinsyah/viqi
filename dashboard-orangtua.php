<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$active_menu = 'dashboard_orangtua';

// Ambil data santri yang terhubung
$santri_list = [];
if ($orangtua_id == 9999) {
    // Jika Super Admin yang masuk, tampilkan 12 santri aktif terbaru sebagai pratinjau
    $res = $conn->query("SELECT * FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY created_at DESC LIMIT 12");
} else {
    // Jika Orang Tua asli, tampilkan anak-anak mereka saja
    $res = $conn->query("SELECT * FROM buku_induk_santri WHERE id_orangtua = $orangtua_id");
}
if ($res) while($r = $res->fetch_assoc()) $santri_list[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Orang Tua | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-orangtua" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 hidden md:block">Selamat Datang, <b><?= htmlspecialchars($_SESSION['orangtua_nama']) ?></b></span>
                <div class="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($_SESSION['orangtua_nama'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800">Daftar Ananda</h2>
            <?php if($orangtua_id == 9999): ?>
                <p class="text-purple-600 font-medium italic">Mode Super Admin: Menampilkan daftar seluruh santri aktif.</p>
            <?php else: ?>
                <p class="text-gray-500">Berikut adalah data putra-putri Anda yang terdaftar di Villa Quran.</p>
            <?php endif; ?>
        </div>

        <?php if(empty($santri_list)): ?>
            <div class="bg-white p-10 rounded-2xl shadow-sm text-center border border-gray-200">
                <i class="fas fa-user-graduate text-6xl text-gray-200 mb-4"></i>
                <p class="text-gray-500">Belum ada data santri yang dihubungkan dengan akun Anda.<br>Silakan hubungi admin kesantrian.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($santri_list as $s): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex items-center mb-4">
                                <img src="<?= !empty($s['foto_santri']) ? $s['foto_santri'] : 'https://via.placeholder.com/100' ?>" class="w-16 h-16 rounded-full object-cover border-2 border-purple-100 mr-4">
                                <div>
                                    <h3 class="font-bold text-gray-900 leading-tight"><?= htmlspecialchars($s['nama_lengkap']) ?></h3>
                                    <p class="text-xs text-purple-600 font-bold uppercase tracking-wider mt-1"><?= htmlspecialchars($s['kelas_sekarang']) ?></p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-gray-600 border-t pt-4">
                                <div class="flex justify-between"><span>NIS / NISN</span> <span class="font-mono"><?= $s['nis'] ?> / <?= $s['nisn'] ?></span></div>
                                <div class="flex justify-between"><span>Kamar</span> <span class="font-bold"><?= $s['kamar_asrama'] ?></span></div>
                                <div class="flex justify-between"><span>Status</span> <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-bold"><?= $s['status_santri'] ?></span></div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 flex justify-between items-center">
                            <div class="flex flex-col space-y-1">
                                <a href="orangtua-rapot.php?id=<?= $s['id'] ?>" class="text-purple-600 text-xs font-bold hover:underline">
                                    <i class="fas fa-graduation-cap mr-1"></i> Rapor Akademik
                                </a>
                                <a href="orangtua-rapot-diniyah.php?id=<?= $s['id'] ?>" class="text-purple-600 text-xs font-bold hover:underline">
                                    <i class="fas fa-book-quran mr-1"></i> Rapor Diniyah
                                </a>
                                <a href="orangtua-karir.php?id=<?= $s['id'] ?>" class="text-purple-600 text-xs font-bold hover:underline">
                                    <i class="fas fa-route mr-1"></i> Rencana Karir & PTN (AI)
                                </a>
                            </div>
                            <button class="text-purple-600 text-xs font-bold hover:underline">Mutaba'ah Harian</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    </div>
</body>
</html>