<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'trend_scout';

$saved_macro = file_exists('saved_trends_macro.txt') ? file_get_contents('saved_trends_macro.txt') : '';
$saved_micro = file_exists('saved_trends_micro.txt') ? file_get_contents('saved_trends_micro.txt') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Trend Scout | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* Common markdown styles */
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
        /* Macro styles */
        .macro-body h2 { color: #4f46e5; }
        .macro-body h3 { color: #4338ca; }
        /* Micro styles */
        .micro-body h2 { color: #059669; }
        .micro-body h3 { color: #047857; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-marketing.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-line text-indigo-600 mr-2"></i>AI Trend & Keyword Scout</h1>
                <p class="text-gray-500 mt-1">Laporan otomatis dari AI tentang tren pasar dan kata kunci potensial.</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Laporan Makro (Bulanan) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[400px] flex flex-col overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-day mr-2 text-indigo-500"></i>Laporan Tren Makro (Bulanan)</h3>
                        <p class="text-xs text-gray-500">Dijalankan otomatis setiap tanggal 1 untuk menentukan tema besar.</p>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <?php if (empty($saved_macro)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-gray-400 text-center">
                                <i class="fas fa-moon text-5xl mb-4 opacity-50"></i>
                                <p>Belum ada laporan tren makro untuk bulan ini.</p>
                                <p class="text-xs">Agent akan bekerja pada tanggal 1 setiap bulan.</p>
                            </div>
                        <?php else: ?>
                            <div class="markdown-body macro-body" id="macro-result"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Laporan Mikro (Mingguan) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[400px] flex flex-col overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-week mr-2 text-emerald-500"></i>Laporan Tren Mikro (Mingguan)</h3>
                        <p class="text-xs text-gray-500">Dijalankan otomatis setiap hari Senin untuk mencari angle viral.</p>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <?php if (empty($saved_micro)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-gray-400 text-center">
                                <i class="fas fa-hourglass-half text-5xl mb-4 opacity-50"></i>
                                <p>Belum ada laporan tren mikro untuk minggu ini.</p>
                                <p class="text-xs">Agent akan bekerja pada hari Senin setiap minggu.</p>
                            </div>
                        <?php else: ?>
                            <div class="markdown-body micro-body" id="micro-result"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const macroContent = <?= json_encode($saved_macro) ?>;
            const microContent = <?= json_encode($saved_micro) ?>;
            if (macroContent) { document.getElementById('macro-result').innerHTML = marked.parse(macroContent); }
            if (microContent) { document.getElementById('micro-result').innerHTML = marked.parse(microContent); }
        });
    </script>
</body>
</html>