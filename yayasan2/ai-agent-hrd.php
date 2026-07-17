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
                <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-800"><i class="fas fa-list mr-2 text-indigo-500"></i>Daftar Pegawai</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-white uppercase bg-indigo-500">
                            <tr>
                                <th scope="col" class="px-6 py-3 w-16 text-center border-r border-indigo-400">No</th>
                                <th scope="col" class="px-6 py-3 border-r border-indigo-400">Nama Pegawai</th>
                                <th scope="col" class="px-6 py-3 border-r border-indigo-400">Jabatan</th>
                                <th scope="col" class="px-6 py-3 text-center border-r border-indigo-400">Nilai KPI</th>
                                <th scope="col" class="px-6 py-3 text-center border-r border-indigo-400">Status</th>
                                <th scope="col" class="px-6 py-3 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center border-r border-gray-200">1</td>
                                <td class="px-6 py-4 border-r border-gray-200 font-medium text-gray-900">Ustadz Ahmad</td>
                                <td class="px-6 py-4 border-r border-gray-200">Guru Diniyah</td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800">
                                        85 (Memuaskan)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors"><i class="fas fa-eye mr-1"></i> Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-gray-50 border-b border-gray-200 hover:bg-gray-100">
                                <td class="px-6 py-4 text-center border-r border-gray-200">2</td>
                                <td class="px-6 py-4 border-r border-gray-200 font-medium text-gray-900">Ustadz Budi</td>
                                <td class="px-6 py-4 border-r border-gray-200">Musyrif Asrama</td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                        90 (Sangat Baik)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors"><i class="fas fa-eye mr-1"></i> Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-white border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-6 py-4 text-center border-r border-gray-200">3</td>
                                <td class="px-6 py-4 border-r border-gray-200 font-medium text-gray-900">Ustadzah Fatimah</td>
                                <td class="px-6 py-4 border-r border-gray-200">Guru Tahfidz</td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-yellow-100 text-yellow-800">
                                        75 (Cukup)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-green-100 text-green-800">
                                        Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors"><i class="fas fa-eye mr-1"></i> Detail</button>
                                </td>
                            </tr>
                            <tr class="bg-gray-50 hover:bg-gray-100">
                                <td class="px-6 py-4 text-center border-r border-gray-200">4</td>
                                <td class="px-6 py-4 border-r border-gray-200 font-medium text-gray-900">Ustadzah Aisyah</td>
                                <td class="px-6 py-4 border-r border-gray-200">Admin</td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                        60 (Kurang)
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span class="inline-flex items-center justify-center px-3 py-1 rounded text-xs font-bold bg-red-100 text-red-800">
                                        Non-Aktif
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded text-xs font-medium transition-colors"><i class="fas fa-eye mr-1"></i> Detail</button>
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