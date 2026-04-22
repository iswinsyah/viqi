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
$total_spmb = $conn->query("SELECT COUNT(id) AS tot FROM pendaftar_spmb $where_visitor")->fetch_assoc()['tot'] ?? 0;
$total_leads = $conn->query("SELECT COUNT(id) AS tot FROM leads $where_visitor")->fetch_assoc()['tot'] ?? 0;
$total_artikel = $conn->query("SELECT COUNT(id) AS tot FROM artikel")->fetch_assoc()['tot'] ?? 0;
$total_visitor = $conn->query("SELECT COUNT(id) AS tot FROM visitor_footprints $where_visitor")->fetch_assoc()['tot'] ?? 0;

// --- AMBIL DATA UNTUK GRAFIK VISUAL ---
// 1. Pengunjung
$trend_labels = []; $trend_data = [];
if ($range == '1year' || $range == '3years') {
    // Jika tahunan, kelompokkan per bulan agar grafik tidak terlalu padat
    $q_trend = $conn->query("SELECT MIN(created_at) as raw_date, COUNT(id) as jumlah FROM visitor_footprints $where_visitor GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at) ASC, MONTH(created_at) ASC");
} else {
    $q_trend = $conn->query("SELECT DATE(created_at) as raw_date, COUNT(id) as jumlah FROM visitor_footprints $where_visitor GROUP BY DATE(created_at) ORDER BY raw_date ASC");
}
if($q_trend) { while($r = $q_trend->fetch_assoc()) { $trend_labels[] = date($format_trend, strtotime($r['raw_date'])); $trend_data[] = $r['jumlah']; } }

// Beri data default (kosong) agar grafik tetap tergambar meski database belum ada isinya
if (empty($trend_labels)) {
    for ($i = 6; $i >= 0; $i--) {
        $trend_labels[] = date($format_trend, strtotime("-$i days"));
        $trend_data[] = 0;
    }
}

// 2. Sumber Traffic
$source_labels = []; $source_data = [];
$q_source = $conn->query("SELECT source, COUNT(id) as jumlah FROM visitor_footprints $where_visitor GROUP BY source ORDER BY jumlah DESC LIMIT 5");
if($q_source) { while($r = $q_source->fetch_assoc()) { $source_labels[] = $r['source']; $source_data[] = $r['jumlah']; } }

if (empty($source_labels)) {
    $source_labels = ['Belum ada data'];
    $source_data = [0];
}

// 3. Device
$device_labels = []; $device_data = [];
$q_device = $conn->query("SELECT device, COUNT(id) as jumlah FROM visitor_footprints $where_visitor GROUP BY device");
if($q_device) { while($r = $q_device->fetch_assoc()) { $device_labels[] = $r['device']; $device_data[] = $r['jumlah']; } }

if (empty($device_labels)) {
    $device_labels = ['Belum ada data'];
    $device_data = [0];
}

// 4. Status Pipeline Funnel
$status_labels = []; $status_data = [];
$q_status = $conn->query("SELECT status, COUNT(id) as jumlah FROM leads $where_visitor GROUP BY status ORDER BY status ASC");
if($q_status) { while($r = $q_status->fetch_assoc()) { $status_labels[] = $r['status']; $status_data[] = $r['jumlah']; } }

if (empty($status_labels)) {
    $status_labels = ['Belum ada data'];
    $status_data = [0];
}

// --- AMBIL DATA TABEL PENDAFTAR TERBARU ---
$pendaftar_terbaru = [];
$q_pendaftar = $conn->query("SELECT * FROM pendaftar_spmb ORDER BY id DESC LIMIT 5");
if($q_pendaftar) { while($r = $q_pendaftar->fetch_assoc()) { $pendaftar_terbaru[] = $r; } }

// --- AMBIL DATA TABEL AGEN (TOP 5) ---
$agen_top = [];
$q_agen = $conn->query("SELECT a.*, (SELECT COUNT(id) FROM leads l WHERE l.kode_ref = a.kode_ref OR l.kode_ref = a.whatsapp) AS total_leads FROM agen a ORDER BY total_leads DESC LIMIT 5");
if($q_agen) { while($r = $q_agen->fetch_assoc()) { $agen_top[] = $r; } }
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
                    <a href="export-spmb.php" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center justify-center whitespace-nowrap">
                        <i class="fas fa-file-excel mr-2"></i> Export
                    </a>
                </div>
            </div>

            <!-- WIDGET STATISTIK (Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                <!-- Widget 2 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-address-book text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Prospek (Leads)</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_leads ?></p>
                    </div>
                </div>
                <!-- Widget 3 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                        <i class="fas fa-eye text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Jejak Pengunjung</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_visitor ?></p>
                    </div>
                </div>
                <!-- Widget 4 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Artikel Dipublish</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_artikel ?></p>
                    </div>
                </div>
            </div>

            <!-- GRAFIK STATISTIK (MATA AI & PIPELINE) -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-line text-emerald-500 mr-2"></i>Tren Pengunjung (<?= $trend_title ?>)</h2>
                    <canvas id="trendChart" height="100"></canvas>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-filter text-amber-500 mr-2"></i>Status Prospek (Pipeline)</h2>
                    <canvas id="pipelineChart" height="200"></canvas>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                 <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-mobile-alt text-purple-500 mr-2"></i>Perangkat (Device)</h2>
                    <canvas id="deviceChart" height="200"></canvas>
                </div>
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-bullhorn text-blue-500 mr-2"></i>Sumber Traffic Teratas (Mata AI)</h2>
                    <canvas id="sourceChart" height="100"></canvas>
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

            <!-- TABEL DATA AGEN (REFERRAL) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mt-8 mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="font-bold text-gray-800">Top 5 Performa Agen</h2>
                    <a href="data-agen.php" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded text-sm font-medium transition shadow-sm">
                        <i class="fas fa-arrow-right mr-1"></i> Ke Data Agen
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Agen</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kode Referral</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Leads (Brosur)</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Konversi Daftar</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($agen_top) > 0): ?>
                                <?php foreach($agen_top as $a): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="font-medium text-gray-900"><?= htmlspecialchars($a['nama']) ?></div><div class="text-sm text-gray-500"><?= htmlspecialchars($a['whatsapp']) ?></div></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><span class="px-2 py-1 bg-gray-100 text-gray-700 rounded font-mono text-sm border border-gray-200"><?= htmlspecialchars($a['kode_ref']) ?></span></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold"><?= $a['total_leads'] ?> Orang</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-emerald-600 font-bold"> <a href="data-agen.php" class="text-blue-500 hover:underline">Lihat Detail</a> </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500 text-sm">Belum ada data agen.</td></tr>
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

            // --- INISIALISASI CHART.JS ---
            
            // 1. Grafik Trend Pengunjung (Line Chart)
            const ctxTrend = document.getElementById('trendChart').getContext('2d');
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: <?= json_encode($trend_labels) ?>,
                    datasets: [{
                        label: 'Jumlah Pengunjung',
                        data: <?= json_encode($trend_data) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });

            // 2. Grafik Sebaran Pipeline (Doughnut Chart)
            const ctxPipeline = document.getElementById('pipelineChart').getContext('2d');
            new Chart(ctxPipeline, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($status_labels) ?>,
                    datasets: [{ data: <?= json_encode($status_data) ?>, backgroundColor: ['#f1f5f9', '#bfdbfe', '#c7d2fe', '#fef3c7', '#ffedd5', '#d1fae5'] }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });

            // 3. Grafik Perangkat (Pie Chart)
            const ctxDevice = document.getElementById('deviceChart').getContext('2d');
            new Chart(ctxDevice, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($device_labels) ?>,
                    datasets: [{ data: <?= json_encode($device_data) ?>, backgroundColor: ['#8b5cf6', '#ec4899', '#f43f5e'] }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });

            // 4. Grafik Sumber Traffic (Bar Chart)
            const ctxSource = document.getElementById('sourceChart').getContext('2d');
            new Chart(ctxSource, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($source_labels) ?>,
                    datasets: [{ label: 'Jumlah Leads dari Sumber Ini', data: <?= json_encode($source_data) ?>, backgroundColor: '#3b82f6', borderRadius: 4 }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        });
    </script>
</body>
</html>