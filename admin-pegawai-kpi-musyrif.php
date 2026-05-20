<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Penanda menu aktif untuk sidebar
$active_menu = 'kpi_musyrif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Musyrif | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-shield text-cyan-600 mr-2"></i>KPI Musyrif</h1>
                <p class="text-gray-500 mt-1">Halaman ini akan berisi dashboard penilaian kinerja khusus untuk para Musyrif.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 min-h-[400px]">
                <div class="flex flex-col items-center justify-center h-full text-gray-400 py-20">
                    <i class="fas fa-tools text-6xl mb-4 opacity-30"></i>
                    <p class="text-lg font-medium">Fitur Segera Hadir</p>
                    <p class="text-sm mt-2">Halaman ini sedang dalam tahap pembangunan.</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
    </script>
</body>
</html>