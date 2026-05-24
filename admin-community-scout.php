<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'community_scout';

$saved_communities = file_exists('saved_communities.txt') ? file_get_contents('saved_communities.txt') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Community Scout | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.75rem; text-align: left; vertical-align: top; }
        .markdown-body th { background-color: #f8fafc; font-weight: bold; color: #0f172a; white-space: nowrap; }
        .markdown-body tr:nth-child(even) { background-color: #f1f5f9; }
        .markdown-body a { color: #2563eb; text-decoration: underline; }
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
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-search-location text-rose-600 mr-2"></i>Community Scout</h1>
                <p class="text-gray-500 mt-1">Laporan harian berisi daftar grup komunitas potensial untuk dijangkau.</p>
            </div>
            <div class="bg-rose-50 rounded-xl shadow-sm border border-rose-100 p-6 mb-6 flex items-start">
                <div class="bg-white p-3 rounded-full shadow-sm mr-4 flex-shrink-0">
                    <i class="fas fa-user-secret text-2xl text-rose-500"></i>
                </div>
                <div>
                    <h3 class="font-bold text-rose-900 mb-1">Strategi: AI sebagai Intel, Manusia sebagai Diplomat</h3>
                    <p class="text-sm text-rose-800 leading-relaxed">AI bertugas mencari "kolam ikan" yang potensial. Tugas Anda sebagai staf marketing adalah masuk ke kolam tersebut, berinteraksi secara natural, dan membangun kepercayaan sebelum membagikan link.</p>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[500px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-table mr-2"></i> Daftar Grup Komunitas Potensial</h3>
                    <p class="text-xs text-gray-500">Dijalankan otomatis setiap hari jam 07:00.</p>
                </div>
                <div class="p-6 flex-1 overflow-y-auto">
                    <?php if (empty($saved_communities)): ?>
                        <div class="flex flex-col items-center justify-center h-full text-gray-400 text-center">
                            <i class="fas fa-satellite-dish text-5xl mb-4 opacity-50"></i>
                            <p>Belum ada laporan pencarian komunitas untuk hari ini.</p>
                            <p class="text-xs">Agent akan bekerja pada jam 07:00 setiap hari.</p>
                        </div>
                    <?php else: ?>
                        <div class="markdown-body min-w-[800px]" id="community-result"></div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const communityContent = <?= json_encode($saved_communities) ?>;
            if (communityContent) {
                document.getElementById('community-result').innerHTML = marked.parse(communityContent);
            }
        });
    </script>
</body>
</html>