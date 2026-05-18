<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Penanda menu aktif untuk sidebar (jika ditambahkan ke sidebar nantinya)
$active_menu = 'halaman_marketing';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Marketing | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING -->
    <?php include 'sidebar-marketing.php'; ?>

    <!-- AREA KONTEN UTAMA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <!-- Tombol Hamburger untuk Mobile -->
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="index.html" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-medium hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
                </a>
                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold shadow-sm">
                    M
                </div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-bullhorn text-blue-600 mr-2"></i>Halaman Marketing</h1>
                <p class="text-gray-500 mt-1">Halaman ini sudah dilengkapi dengan sidebar dan siap digunakan untuk fitur marketing baru.</p>
            </div>

            <!-- AREA KOSONG UNTUK KONTEN -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[400px]">
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-20">
                    <i class="fas fa-paint-roller text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Area Konten Kosong</p>
                    <p class="text-sm mt-2">Bos bisa memasukkan tabel, grafik, atau form baru di sini.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- SCRIPT UNTUK TOGGLE SIDEBAR DI MOBILE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('open-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>