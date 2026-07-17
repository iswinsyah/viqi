<?php
require_once 'auth.php';
require_once '../koneksi.php';
$active_menu = 'ai_agent_hrd';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent HRD | Yayasan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2></div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-robot text-emerald-500 mr-2"></i>AI Agent HRD</h1></div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white">
                    <h3 class="text-lg font-semibold text-gray-800">Monitoring Kinerja Pegawai</h3>
                    <div class="flex space-x-2">
                        <button class="bg-emerald-50 text-emerald-600 hover:bg-emerald-100 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center border border-emerald-200">
                            <i class="fas fa-file-excel mr-2"></i> Export
                        </button>
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center shadow-sm">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh AI Analysis
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 w-12 text-center">No</th>
                                <th scope="col" class="px-6 py-4">Nama Pegawai</th>
                                <th scope="col" class="px-6 py-4 text-center">Tugas Aktif</th>
                                <th scope="col" class="px-6 py-4 text-center">Tugas Selesai</th>
                                <th scope="col" class="px-6 py-4 w-48">Progress</th>
                                <th scope="col" class="px-6 py-4 text-center">Skor KPI</th>
                                <th scope="col" class="px-6 py-4">Insights</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-center font-medium text-gray-900">1</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">Dr. Ahmad Fauzi, Lc., M.A.</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">12</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">45</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-emerald-500 h-2 rounded-full" style="width: 75%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700 min-w-[32px]">75%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-bold text-gray-900">92.5</span>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-emerald-100 text-emerald-700 font-bold text-xs">A</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">Sangat Produktif</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">Target Tercapai</span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-center font-medium text-gray-900">2</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">Ustadz Budi Santoso</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">8</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">32</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: 80%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700 min-w-[32px]">80%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-bold text-gray-900">88.0</span>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-blue-100 text-blue-700 font-bold text-xs">B</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium bg-blue-50 text-blue-700 border border-blue-200">Produktif</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium bg-gray-100 text-gray-700 border border-gray-200">Stabil</span>
                                    </div>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 text-center font-medium text-gray-900">3</td>
                                <td class="px-6 py-4 font-semibold text-gray-800">Ustadzah Siti Aminah</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">15</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">28</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="bg-amber-400 h-2 rounded-full" style="width: 65%"></div>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-700 min-w-[32px]">65%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span class="font-bold text-gray-900">75.5</span>
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-amber-100 text-amber-700 font-bold text-xs">C</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200">Perlu Perhatian</span>
                                        <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-medium bg-rose-50 text-rose-700 border border-rose-200">Kurang Fokus</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });
    </script>
</body>
</html>