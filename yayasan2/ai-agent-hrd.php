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
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-white p-6">
            
            <div class="w-full">
                <h2 class="text-xl font-bold mb-2">Jam Pelajaran</h2>
                <div class="overflow-x-auto mb-8">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="bg-black text-white">
                                <th class="border border-gray-300 p-2 text-center w-12">No</th>
                                <th class="border border-gray-300 p-2 text-left">Keterangan</th>
                                <th class="border border-gray-300 p-2 text-center">Total</th>
                                <th class="border border-gray-300 p-2 text-center">Terlaksana</th>
                                <th class="border border-gray-300 p-2 text-center">Kosong</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 p-2 text-center font-bold bg-gray-100" colspan="5">Nama Pegawai</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 text-center">1</td>
                                <td class="border border-gray-300 p-2">Mengajar Diniyah</td>
                                <td class="border border-gray-300 p-2 text-center">36 JP</td>
                                <td class="border border-gray-300 p-2 text-center">34 JP</td>
                                <td class="border border-gray-300 p-2 text-center">2 JP</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h2 class="text-xl font-bold mb-2">Absensi Pegawai</h2>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm text-center">
                        <thead>
                            <tr class="bg-black text-white">
                                <th class="border border-gray-300 p-2 w-12">No</th>
                                <th class="border border-gray-300 p-2 text-left">Keterangan</th>
                                <th class="border border-gray-300 p-2">Total</th>
                                <th class="border border-gray-300 p-2">Hadir Tepat</th>
                                <th class="border border-gray-300 p-2">Hadir Terlambat</th>
                                <th class="border border-gray-300 p-2">Rata-rata Keterlambatan</th>
                                <th class="border border-gray-300 p-2">Terlambat Max</th>
                                <th class="border border-gray-300 p-2">Ijin</th>
                                <th class="border border-gray-300 p-2">Sakit</th>
                                <th class="border border-gray-300 p-2">Alfa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="border border-gray-300 p-2 text-center font-bold bg-gray-100" colspan="10">Nama Pegawai</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2">1</td>
                                <td class="border border-gray-300 p-2 text-left">Absensi Kehadiran</td>
                                <td class="border border-gray-300 p-2">26</td>
                                <td class="border border-gray-300 p-2">20</td>
                                <td class="border border-gray-300 p-2">6</td>
                                <td class="border border-gray-300 p-2">15 Menit</td>
                                <td class="border border-gray-300 p-2">45 Menit</td>
                                <td class="border border-gray-300 p-2">0</td>
                                <td class="border border-gray-300 p-2">0</td>
                                <td class="border border-gray-300 p-2">0</td>
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