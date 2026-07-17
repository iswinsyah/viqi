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
            
            <div class="bg-white p-4">
                <!-- Tabel 1: 5 kolom 3 baris -->
                <h2 class="text-lg font-bold mb-2">Jam Pelajaran</h2>
                <table class="w-full border-collapse border border-gray-400 mb-8 text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-400 p-2 text-center w-12">No</th>
                            <th class="border border-gray-400 p-2 text-left">Keterangan</th>
                            <th class="border border-gray-400 p-2 text-center">Total</th>
                            <th class="border border-gray-400 p-2 text-center">Terlaksana</th>
                            <th class="border border-gray-400 p-2 text-center">Kosong</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Tabel 2: 10 kolom 3 baris -->
                <h2 class="text-lg font-bold mb-2">Absensi Pegawai</h2>
                <table class="w-full border-collapse border border-gray-400 text-sm text-center">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-400 p-2 w-12">No</th>
                            <th class="border border-gray-400 p-2 text-left">Keterangan</th>
                            <th class="border border-gray-400 p-2">Total</th>
                            <th class="border border-gray-400 p-2">Hadir Tepat</th>
                            <th class="border border-gray-400 p-2">Hadir Terlambat</th>
                            <th class="border border-gray-400 p-2">Rata-rata Keterlambatan</th>
                            <th class="border border-gray-400 p-2">Terlambat Max</th>
                            <th class="border border-gray-400 p-2">Ijin</th>
                            <th class="border border-gray-400 p-2">Sakit</th>
                            <th class="border border-gray-400 p-2">Alfa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                        </tr>
                        <tr>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                            <td class="border border-gray-400 h-10"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });
    </script>
</body>
</html>