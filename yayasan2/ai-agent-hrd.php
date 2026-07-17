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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900">AI Agent HRD</h1></div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">Daftar Pegawai</h2>
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <input type="text" placeholder="Cari..." class="border border-gray-300 rounded-md pl-3 pr-10 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <i class="fas fa-search absolute right-3 top-2 text-gray-400 text-sm"></i>
                        </div>
                        <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                            <i class="fas fa-plus mr-1"></i> Tambah
                        </button>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th scope="col" class="px-6 py-3 w-16 text-center">NO.</th>
                                <th scope="col" class="px-6 py-3">NAMA PEGAWAI</th>
                                <th scope="col" class="px-6 py-3">JABATAN</th>
                                <th scope="col" class="px-6 py-3 text-center">NILAI KPI</th>
                                <th scope="col" class="px-6 py-3 text-center">STATUS</th>
                                <th scope="col" class="px-6 py-3 text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center">1</td>
                                <td class="px-6 py-4">Ustadz Ahmad</td>
                                <td class="px-6 py-4">Guru Diniyah</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        85 (Memuaskan)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center">2</td>
                                <td class="px-6 py-4">Ustadz Budi</td>
                                <td class="px-6 py-4">Musyrif Asrama</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        90 (Sangat Baik)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-100 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center">3</td>
                                <td class="px-6 py-4">Ustadzah Fatimah</td>
                                <td class="px-6 py-4">Guru Tahfidz</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        75 (Cukup)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-white hover:bg-gray-50">
                                <td class="px-6 py-4 text-center">4</td>
                                <td class="px-6 py-4">Ustadzah Aisyah</td>
                                <td class="px-6 py-4">Admin</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        60 (Kurang)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Non-Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs transition-colors">Detail</button>
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