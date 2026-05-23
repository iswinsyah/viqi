<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'dashboard_marketing';

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
    $format_trend = "M Y";
} elseif ($range === '3years') {
    $where_visitor = "WHERE created_at >= DATE(NOW()) - INTERVAL 3 YEAR";
    $trend_title = "3 Tahun Terakhir";
    $format_trend = "M Y";
} else {
    $where_visitor = "WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY";
}

// --- AMBIL DATA STATISTIK UNTUK WIDGET ---
$q_leads = $conn->query("SELECT COUNT(id) AS tot FROM leads $where_visitor");
$total_leads = $q_leads ? ($q_leads->fetch_assoc()['tot'] ?? 0) : 0;

$q_artikel = $conn->query("SELECT COUNT(id) AS tot FROM artikel");
$total_artikel = $q_artikel ? ($q_artikel->fetch_assoc()['tot'] ?? 0) : 0;

$q_visitor = $conn->query("SELECT COUNT(id) AS tot FROM visitor_footprints $where_visitor");
$total_visitor = $q_visitor ? ($q_visitor->fetch_assoc()['tot'] ?? 0) : 0;

// --- AMBIL DATA UNTUK GRAFIK VISUAL ---
// 1. Pengunjung
$trend_labels = []; $trend_data = [];
if ($range == '1year' || $range == '3years') {
    $q_trend = $conn->query("SELECT MIN(created_at) as raw_date, COUNT(id) as jumlah FROM visitor_footprints $where_visitor GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at) ASC, MONTH(created_at) ASC");
} else {
    $q_trend = $conn->query("SELECT DATE(created_at) as raw_date, COUNT(id) as jumlah FROM visitor_footprints $where_visitor GROUP BY DATE(created_at) ORDER BY raw_date ASC");
}
if($q_trend) { while($r = $q_trend->fetch_assoc()) { $trend_labels[] = date($format_trend, strtotime($r['raw_date'])); $trend_data[] = $r['jumlah']; } }

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
    <title>Dashboard Marketing & AI | Villa Quran Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING -->
    <?php include 'sidebar-marketing.php'; ?>

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
                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold shadow-sm">
                    M
                </div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-900">Dashboard Marketing & AI</h1>
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <select onchange="window.location.href='?range='+this.value" class="bg-white border border-gray-300 text-gray-700 py-2 px-4 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium text-sm w-full sm:w-auto cursor-pointer">
                        <option value="7days" <?= $range == '7days' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="1month" <?= $range == '1month' ? 'selected' : '' ?>>1 Bulan Terakhir</option>
                        <option value="1year" <?= $range == '1year' ? 'selected' : '' ?>>1 Tahun Terakhir</option>
                        <option value="3years" <?= $range == '3years' ? 'selected' : '' ?>>3 Tahun Terakhir</option>
                    </select>
                </div>
            </div>
            
            <!-- SHORTCUTS AI AGENTS -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <a href="admin-ai-hub.php" class="bg-white hover:bg-indigo-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-robot text-indigo-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Pusat Kendali AI</span>
                </a>
                <a href="data-pipeline.php" class="bg-white hover:bg-blue-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-columns text-blue-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Pipeline Prospek</span>
                </a>
                <a href="data-agen.php" class="bg-white hover:bg-emerald-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-handshake text-emerald-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Data Agen</span>
                </a>
                <a href="admin-analisa.php" class="bg-white hover:bg-purple-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-brain text-purple-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Analisa Persona</span>
                </a>
                <a href="admin-kalender.php" class="bg-white hover:bg-sky-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-calendar-alt text-sky-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Kalender Konten AI</span>
                </a>
                <a href="admin-seo.php" class="bg-white hover:bg-teal-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-pen-nib text-teal-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Penulis Artikel SEO</span>
                </a>
                <a href="admin-publisher.php" class="bg-white hover:bg-emerald-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-paper-plane text-emerald-500 text-2xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">AI Publisher</span>
                </a>
            </div>

            <!-- WIDGET STATISTIK (Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-address-book text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Prospek (Leads)</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_leads ?></p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                        <i class="fas fa-eye text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Jejak Pengunjung</p>
                        <p class="text-2xl font-bold text-gray-900"><?= $total_visitor ?></p>
                    </div>
                </div>
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
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-chart-line text-blue-500 mr-2"></i>Tren Pengunjung (<?= $trend_title ?>)</h2>
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
                    <h2 class="font-bold text-gray-800 mb-4"><i class="fas fa-bullhorn text-emerald-500 mr-2"></i>Sumber Traffic Teratas (Mata AI)</h2>
                    <canvas id="sourceChart" height="100"></canvas>
                </div>
            </div>

            <!-- TABEL DATA AGEN (REFERRAL) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h2 class="font-bold text-gray-800">Top 5 Performa Agen</h2>
                    <a href="data-agen.php" class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1.5 rounded text-sm font-medium transition shadow-sm">
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600 font-bold"> <a href="data-agen.php" class="text-blue-500 hover:underline">Lihat Detail</a> </td>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('open-sidebar');
            const closeBtn = document.getElementById('close-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            function toggleSidebar() { if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); } }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);

            // INISIALISASI CHART.JS
            const ctxTrend = document.getElementById('trendChart').getContext('2d');
            new Chart(ctxTrend, { type: 'line', data: { labels: <?= json_encode($trend_labels) ?>, datasets: [{ label: 'Jumlah Pengunjung', data: <?= json_encode($trend_data) ?>, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', borderWidth: 2, fill: true, tension: 0.4 }] }, options: { responsive: true, plugins: { legend: { display: false } } } });

            const ctxPipeline = document.getElementById('pipelineChart').getContext('2d');
            new Chart(ctxPipeline, { type: 'doughnut', data: { labels: <?= json_encode($status_labels) ?>, datasets: [{ data: <?= json_encode($status_data) ?>, backgroundColor: ['#f1f5f9', '#bfdbfe', '#c7d2fe', '#fef3c7', '#ffedd5', '#d1fae5'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });

            const ctxDevice = document.getElementById('deviceChart').getContext('2d');
            new Chart(ctxDevice, { type: 'pie', data: { labels: <?= json_encode($device_labels) ?>, datasets: [{ data: <?= json_encode($device_data) ?>, backgroundColor: ['#8b5cf6', '#ec4899', '#f43f5e'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });

            const ctxSource = document.getElementById('sourceChart').getContext('2d');
            new Chart(ctxSource, { type: 'bar', data: { labels: <?= json_encode($source_labels) ?>, datasets: [{ label: 'Jumlah Leads dari Sumber Ini', data: <?= json_encode($source_data) ?>, backgroundColor: '#10b981', borderRadius: 4 }] }, options: { responsive: true, plugins: { legend: { display: false } } } });
        });
    </script>
</body>
</html>