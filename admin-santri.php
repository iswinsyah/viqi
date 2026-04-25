<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Buat tabel santri otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nis VARCHAR(50),
    nama VARCHAR(150),
    kamar VARCHAR(50),
    hafalan_terakhir VARCHAR(100),
    status VARCHAR(50) DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$active_menu = 'santri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Santri | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-users text-emerald-600 mr-2"></i>Data Santri Aktif</h1>
                    <p class="text-sm text-gray-500 mt-1">Manajemen data santri, asrama, dan perkembangan hafalan.</p>
                </div>
                <button class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Santri
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-12 text-center text-gray-500">
                    <i class="fas fa-tools text-6xl mb-4 text-gray-300"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">Kerangka Halaman Berhasil Dibuat!</h3>
                    <p>Halaman Data Santri sudah siap. Mau kita lengkapi fitur tabel, pencarian, dan form input datanya sekarang, Bos?</p>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.getElementById('open-sidebar');
            if(openBtn) openBtn.addEventListener('click', () => { 
                document.getElementById('sidebar').classList.toggle('hidden'); 
                document.getElementById('sidebar-overlay').classList.toggle('hidden'); 
            });
        });
    </script>
</body>
</html>