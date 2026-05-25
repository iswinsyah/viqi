<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'dashboard_yayasan';

$q_asatidz = $conn->query("SELECT COUNT(id) AS tot FROM akun_ustadz");
$total_asatidz = $q_asatidz ? ($q_asatidz->fetch_assoc()['tot'] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Yayasan 2 Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2></div>
            <div class="flex items-center space-x-4">
                <a href="../index.html" target="_blank" class="text-sm text-amber-600 hover:text-amber-800 font-medium hidden sm:flex items-center"><i class="fas fa-external-link-alt mr-2"></i> Lihat Website</a>
                <div class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center text-gray-900 font-bold shadow-sm">Y2</div>
            </div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Selamat Datang di Ruang Yayasan 2</h1>
                <p class="text-gray-500 mt-1">Area baru yang lebih segar, cepat, dan terorganisir.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center md:col-span-3">
                    <div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4"><i class="fas fa-users-cog text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Akun Asatidz</p><p class="text-2xl font-bold text-gray-900"><?= $total_asatidz ?></p></div>
                </div>
            </div>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-900 max-w-3xl">
                <h3 class="font-bold mb-2 flex items-center"><i class="fas fa-shield-alt mr-2 text-amber-600"></i>Otoritas Akses Ruang Asatidz</h3>
                <p class="text-sm leading-relaxed mb-4">Hanya Asatidz yang telah Anda buatkan akunnya di menu <b>Daftar Asatidz</b> yang dapat melakukan Login ke dalam Ruang Asatidz dari halaman depan website.</p>
                <a href="asatidz.php" class="inline-flex items-center bg-amber-500 text-gray-900 px-4 py-2 rounded shadow hover:bg-amber-600 transition font-bold text-sm">Kelola Akun Asatidz Sekarang <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); document.getElementById('close-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });
    </script>
</body>
</html>