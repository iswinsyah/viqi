<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$active_menu = 'dashboard_santri';
$santri_nama = $_SESSION['santri_nama'] ?? 'Santri';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Santri | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-santri" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="font-semibold text-sm text-gray-700 hidden sm:block">Selamat Datang, <?= htmlspecialchars($santri_nama) ?></span>
                <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($santri_nama, 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Ahlan Wa Sahlan, <?= htmlspecialchars($santri_nama) ?>!</h1>
                <p class="text-gray-500 mt-1">Selamat datang di Ruang Santri. Ini adalah halaman dashboard pribadimu.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[400px]">
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-20">
                    <i class="fas fa-book-reader text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Menu Santri Segera Hadir</p>
                    <p class="text-sm mt-2">Halaman ini akan segera diisi dengan informasi akademik, jadwal, dan lainnya.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-santri');
            const openBtn = document.getElementById('open-sidebar-santri');
            const overlay = document.getElementById('sidebar-overlay-santri');
            if(openBtn) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
            if(overlay) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
        });
    </script>
</body>
</html>