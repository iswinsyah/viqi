<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'master_kalender';
// Trigger deploy rerun - 1


$months = [
    'JULI 2026', 'AGUSTUS 2026', 'SEPTEMBER 2026', 'OKTOBER 2026', 'NOVEMBER 2026', 'DESEMBER 2026',
    'JANUARI 2027', 'FEBRUARI 2027', 'MARET 2027', 'APRIL 2027', 'MEI 2027', 'JUNI 2027', 'JULI 2027'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Kalender Akademik | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .grid-cell {
            width: 28px;
            height: 28px;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-calendar-alt text-amber-500 mr-2"></i>Master Kalender Akademik</h1>
                <p class="text-gray-500 mt-1">Format tabel kosong 33 kolom dan 15 baris (termasuk header).</p>
            </div>

            <!-- CARD WIDGET UNTUK TABEL GRID -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-x-auto max-w-7xl mx-auto mb-8">
                <div class="min-w-[1020px]">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <!-- BARIS 1 (Header Utama) -->
                            <tr class="bg-amber-900 text-white font-bold text-xs">
                                <th class="border border-amber-950 px-2 py-2 text-center" rowspan="2" style="width: 35px;">No</th>
                                <th class="border border-amber-950 px-4 py-2 text-left" rowspan="2" style="width: 150px;">BULAN</th>
                                <th class="border border-amber-950 py-2 text-center" colspan="31">TANGGAL</th>
                            </tr>
                            <!-- BARIS 2 (Header Angka Tanggal) -->
                            <tr class="bg-amber-800 text-white font-bold text-[10px]">
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <th class="border border-amber-900 text-center py-1.5" style="width: 28px;"><?= $d ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- BARIS 3 s/d 15 (13 baris bulan kosong) -->
                            <?php 
                            $no = 1;
                            foreach ($months as $m): 
                            ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="border border-gray-300 text-center font-bold bg-amber-50 text-amber-900 py-2"><?= $no++ ?></td>
                                    <td class="border border-gray-300 font-bold bg-amber-50 text-amber-900 px-3 py-2 whitespace-nowrap text-left text-xs"><?= $m ?></td>
                                    <?php for ($day = 1; $day <= 31; $day++): ?>
                                        <td class="border border-gray-300 grid-cell bg-white"></td>
                                    <?php endfor; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM PENGATURAN KALENDER AKADEMIK -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-7xl mx-auto mb-8 text-left">
                <h3 class="font-bold text-gray-800 text-base mb-4"><i class="fas fa-cog text-amber-500 mr-2"></i>Form Pengaturan Kalender Akademik</h3>
                <form action="" method="POST" class="space-y-4">
                    <div class="max-w-md">
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2" for="hari_ahad_1_bulan">Hari Ahad 1 Bulan</label>
                        <input type="date" name="hari_ahad_1_bulan" id="hari_ahad_1_bulan" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-amber-500 bg-white">
                    </div>
                    <div class="pt-2">
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2 px-6 rounded-lg text-sm shadow-md transition-all duration-200">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
