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
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-lg bg-indigo-600 flex items-center justify-center text-white mr-4 shadow-sm">
                        <i class="fas fa-robot text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">AI Agent HRD</h1>
                        <p class="text-sm text-gray-500 mt-1">Monitoring dan prediksi kinerja pegawai menggunakan kecerdasan buatan</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 bg-white">
                    <div class="flex flex-1 gap-2">
                        <div class="relative w-full sm:max-w-xs">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 p-2" placeholder="Cari pegawai...">
                        </div>
                        <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                            <option>Semua Departemen</option>
                            <option>Pendidikan</option>
                            <option>Pengasuhan</option>
                            <option>Keuangan</option>
                        </select>
                        <select class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2">
                            <option>Semua Status</option>
                            <option>Aktif</option>
                            <option>Cuti</option>
                        </select>
                    </div>
                    <div class="flex space-x-2">
                        <button class="bg-white text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center border border-gray-300 shadow-sm">
                            <i class="fas fa-download mr-2"></i> Export Report
                        </button>
                        <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center shadow-sm">
                            <i class="fas fa-magic mr-2"></i> Run AI Analysis
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-4 font-semibold">PEGAWAI</th>
                                <th scope="col" class="px-6 py-4 font-semibold">DEPARTEMEN</th>
                                <th scope="col" class="px-6 py-4 font-semibold">KEHADIRAN</th>
                                <th scope="col" class="px-6 py-4 font-semibold">SKOR KPI</th>
                                <th scope="col" class="px-6 py-4 font-semibold">PREDIKSI AI</th>
                                <th scope="col" class="px-6 py-4 font-semibold">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-sm">
                                            AF
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">Dr. Ahmad Fauzi</div>
                                            <div class="text-xs text-gray-500">NIP: 198203112005011001</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">Pendidikan</td>
                                <td class="px-6 py-4">
                                    <span class="text-emerald-600 font-medium flex items-center">
                                        <i class="fas fa-arrow-up text-xs mr-1"></i> 98%
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold text-gray-900">92</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-emerald-500 h-1.5 rounded-full" style="width: 92%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Kandidat Promosi
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="text-indigo-600 hover:text-indigo-900 font-medium text-sm transition-colors">Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm">
                                            BS
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">Budi Santoso</div>
                                            <div class="text-xs text-gray-500">NIP: 198507222010011003</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">Pengasuhan</td>
                                <td class="px-6 py-4">
                                    <span class="text-rose-600 font-medium flex items-center">
                                        <i class="fas fa-arrow-down text-xs mr-1"></i> 82%
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold text-gray-900">75</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-amber-500 h-1.5 rounded-full" style="width: 75%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-rose-100 text-rose-800">
                                        Beresiko Turun
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="text-indigo-600 hover:text-indigo-900 font-medium text-sm transition-colors">Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                                            SA
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900">Siti Aminah</div>
                                            <div class="text-xs text-gray-500">NIP: 199011052015042002</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">Keuangan</td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 font-medium flex items-center">
                                        <i class="fas fa-minus text-xs mr-1"></i> 95%
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold text-gray-900">88</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: 88%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800">
                                        Kinerja Stabil
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <button class="text-indigo-600 hover:text-indigo-900 font-medium text-sm transition-colors">Detail</button>
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