<?php
require_once 'auth.php'; // Panggil satpam pengunci halaman
require_once 'koneksi.php';

$active_menu = 'dashboard';

// --- PENANGANAN FILTER WAKTU UNTUK GRAFIK & WIDGET ---
$range = $_GET['range'] ?? '7days';
$where_visitor = "";
$trend_title = "7 Hari Terakhir";
$format_trend = "d M";

if ($range === '1month') {
    $where_visitor = "WHERE created_at >= DATE(NOW()) - INTERVAL 1 MONTH";
    $trend_title = "1 Bulan Terakhir";
} elseif ($range === '1year') {
    $where_visitor = "WHERE created_at >= DATE(NOW()) - INTERVAL 1 YEAR";
    $trend_title = "1 Tahun Terakhir";
    $format_trend = "M Y"; // Format teks grafik menjadi: Jan 2026, Feb 2026
} elseif ($range === '3years') {
    $where_visitor = "WHERE created_at >= DATE(NOW()) - INTERVAL 3 YEAR";
    $trend_title = "3 Tahun Terakhir";
    $format_trend = "M Y";
} else {
    $where_visitor = "WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY";
}

// --- AMBIL DATA STATISTIK UNTUK WIDGET ---
$q_spmb = $conn->query("SELECT COUNT(id) AS tot FROM pendaftar_spmb $where_visitor");
$total_spmb = $q_spmb ? ($q_spmb->fetch_assoc()['tot'] ?? 0) : 0;

// --- AMBIL DATA TABEL PENDAFTAR TERBARU ---
$pendaftar_terbaru = [];
$q_pendaftar = $conn->query("SELECT * FROM pendaftar_spmb ORDER BY id DESC LIMIT 5");
if($q_pendaftar) { while($r = $q_pendaftar->fetch_assoc()) { $pendaftar_terbaru[] = $r; } }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Villa Quran Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="hidden sm:block">
                    <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
                </a>
                <button class="text-gray-400 hover:text-gray-600 relative">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 -mt-1 -mr-1 bg-rose-500 text-white w-4 h-4 rounded-full text-[10px] flex items-center justify-center font-bold border-2 border-white">3</span>
                </button>
                <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">
                    A
                </div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Utama</h1>
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <select onchange="window.location.href='?range='+this.value" class="bg-white border border-gray-300 text-gray-700 py-2 px-4 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-sm w-full sm:w-auto cursor-pointer">
                        <option value="7days" <?= $range == '7days' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="1month" <?= $range == '1month' ? 'selected' : '' ?>>1 Bulan Terakhir</option>
                        <option value="1year" <?= $range == '1year' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                        <option value="3years" <?= $range == '3years' ? 'selected' : '' ?>>3 Tahun Terakhir</option>
                    </select>
                    <a href="admin-parenting.php" class="bg-amber-500 hover:bg-amber-600 text-teal-900 px-4 py-2 rounded-lg text-sm font-bold transition shadow-sm flex items-center justify-center whitespace-nowrap">
                        <i class="fas fa-calendar-check mr-2"></i> Parenting School
                    </a>
                    <a href="export-spmb.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center whitespace-nowrap">
                        <i class="fas fa-file-excel mr-2"></i> Export
                    </a>
                </div>
            </div>

            <!-- WIDGET STATISTIK (Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Widget 1 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-3 rounded-full bg-emerald-100 text-emerald-600 mr-4">
                        <i class="fas fa-user-graduate text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pendaftar SPMB</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_spmb ?></p>
                    </div>
                </div>
                
                <!-- Widget Portal Marketing -->
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl shadow-sm border border-blue-600 p-6 flex flex-col justify-center text-white">
                    <h3 class="font-bold text-lg mb-1"><i class="fas fa-rocket mr-2"></i> Portal Marketing</h3>
                    <p class="text-blue-100 text-sm mb-4 line-clamp-2">Masuk ke pusat kendali aktivitas marketing dan AI.</p>
                    <a href="dashboard-marketing.php" class="inline-flex items-center justify-center w-full bg-white text-blue-600 hover:bg-blue-50 px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition">
                        Buka Marketing <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>

                <!-- Widget Portal Pegawai -->
                <div class="bg-gradient-to-r from-slate-600 to-slate-800 rounded-xl shadow-sm border border-slate-700 p-6 flex flex-col justify-center text-white">
                    <h3 class="font-bold text-lg mb-1"><i class="fas fa-users-cog mr-2"></i> Ruang Asatidz</h3>
                    <p class="text-slate-200 text-sm mb-4 line-clamp-2">Masuk ke manajemen asatidz & AI Agent HRD.</p>
                    <a href="admin-ustadz.php" class="inline-flex items-center justify-center w-full bg-cyan-400 text-slate-900 hover:bg-cyan-500 px-4 py-2 rounded-lg font-bold text-sm shadow-sm transition">
                        Buka Ruang Asatidz <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>

            <!-- TABEL PENDAFTAR TERBARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="font-bold text-gray-800">Pendaftar SPMB Terbaru</h2>
                    <a href="admin-spmb.php" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Calon Santri</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Asal Kota</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Daftar</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($pendaftar_terbaru) > 0): ?>
                                <?php foreach($pendaftar_terbaru as $p): 
                                    $bg_badge = 'bg-amber-100 text-amber-800';
                                    if($p['status'] == 'Lulus Seleksi') $bg_badge = 'bg-emerald-100 text-emerald-800';
                                    if($p['status'] == 'Ditolak') $bg_badge = 'bg-red-100 text-red-800';
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="font-medium text-gray-900"><?= htmlspecialchars($p['nama_lengkap']) ?></div><div class="text-sm text-gray-500 uppercase"><?= htmlspecialchars($p['jenjang']) ?></div></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($p['asal_sekolah']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $bg_badge ?>"><?= $p['status'] ?></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><a href="admin-spmb.php" class="text-emerald-600 hover:text-emerald-900 mr-3">Detail</a></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500 text-sm">Belum ada pendaftar terbaru.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- Script untuk Toggle Sidebar Mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('open-sidebar');
            const closeBtn = document.getElementById('close-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                if(sidebar && overlay) {
                    sidebar.classList.toggle('hidden');
                    overlay.classList.toggle('hidden');
                }
            }

            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>