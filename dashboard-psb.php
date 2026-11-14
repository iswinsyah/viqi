<?php
/**
 * DASHBOARD UTAMA RUANG PSB (PENERIMAAN SANTRI BARU)
 * Villa Quran Baron Malang - SADIGS 4.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';
require_once 'auth-unified.php';

// Validasi Akses
$user = getCurrentUser();
if (!$user) {
    if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['yayasan_logged_in']) && !isset($_SESSION['yayasan_user']) && !isset($_SESSION['app_user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Pastikan tabel pendaftar_spmb ada
$conn->query("CREATE TABLE IF NOT EXISTS pendaftar_spmb (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenjang VARCHAR(50),
    nama_lengkap VARCHAR(150),
    nik VARCHAR(20),
    nisn VARCHAR(20),
    whatsapp_ortu VARCHAR(20),
    asal_sekolah VARCHAR(150),
    status VARCHAR(50) DEFAULT 'Menunggu Tes',
    status_daftar_ulang VARCHAR(50) DEFAULT 'Belum',
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Filter Waktu
$range = $_GET['range'] ?? 'all';
$where_range = "";
if ($range === '7days') {
    $where_range = "WHERE created_at >= DATE(NOW()) - INTERVAL 7 DAY";
} elseif ($range === '1month') {
    $where_range = "WHERE created_at >= DATE(NOW()) - INTERVAL 1 MONTH";
} elseif ($range === '1year') {
    $where_range = "WHERE created_at >= DATE(NOW()) - INTERVAL 1 YEAR";
}

// 1. STATISTIK PENDAFTAR
$tot_pendaftar = 0;
$q1 = $conn->query("SELECT COUNT(id) as c FROM pendaftar_spmb $where_range");
if ($q1) { $tot_pendaftar = (int)$q1->fetch_assoc()['c']; }

$tot_lulus = 0;
$q2 = $conn->query("SELECT COUNT(id) as c FROM pendaftar_spmb WHERE status = 'Lulus Seleksi'");
if ($q2) { $tot_lulus = (int)$q2->fetch_assoc()['c']; }

$tot_menunggu = 0;
$q3 = $conn->query("SELECT COUNT(id) as c FROM pendaftar_spmb WHERE status = 'Menunggu Tes' OR status = 'Menunggu Verifikasi' OR status IS NULL OR status = ''");
if ($q3) { $tot_menunggu = (int)$q3->fetch_assoc()['c']; }

$tot_daftar_ulang = 0;
$q4 = $conn->query("SELECT COUNT(id) as c FROM pendaftar_spmb WHERE status_daftar_ulang = 'Sudah' OR status_daftar_ulang = 'Lunas'");
if ($q4) { $tot_daftar_ulang = (int)$q4->fetch_assoc()['c']; }

// 2. STATISTIK LEADS & MITRA
$tot_leads = 0;
$q_leads = $conn->query("SELECT COUNT(id) as c FROM leads");
if ($q_leads) { $tot_leads = (int)$q_leads->fetch_assoc()['c']; }

$tot_agen = 0;
$q_agen = $conn->query("SELECT COUNT(id) as c FROM agen");
if ($q_agen) { $tot_agen = (int)$q_agen->fetch_assoc()['c']; }

// 3. STATISTIK JENJANG
$q_jenjang = $conn->query("SELECT jenjang, COUNT(id) as jml FROM pendaftar_spmb GROUP BY jenjang");
$jenjang_labels = [];
$jenjang_data = [];
if ($q_jenjang) {
    while ($r = $q_jenjang->fetch_assoc()) {
        $jenjang_labels[] = !empty($r['jenjang']) ? strtoupper($r['jenjang']) : 'UMUM';
        $jenjang_data[] = (int)$r['jml'];
    }
}
if (empty($jenjang_labels)) {
    $jenjang_labels = ['SMP', 'SMA'];
    $jenjang_data = [0, 0];
}

// 4. PENDAFTAR TERBARU (5 TERAKHIR)
$pendaftar_terbaru = [];
$qp = $conn->query("SELECT * FROM pendaftar_spmb ORDER BY id DESC LIMIT 5");
if ($qp) {
    while ($row = $qp->fetch_assoc()) {
        $pendaftar_terbaru[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang PSB (Penerimaan Santri Baru) | Villa Quran Baron</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR PSB -->
    <?php include 'sidebar-psb.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP BAR -->
        <header class="h-16 bg-white shadow-xs border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-slate-500 hover:text-slate-700 focus:outline-none md:hidden p-1.5 rounded-lg hover:bg-slate-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-teal-100 text-[#0b8478] flex items-center justify-center font-bold text-sm">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-slate-800 text-sm sm:text-base leading-tight">Ruang PSB</h2>
                        <p class="text-[10px] text-slate-500 font-medium">Penerimaan Santri Baru Online</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2.5">
                <a href="daftar-spmb.html" target="_blank" class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-external-link-alt text-[10px]"></i> <span class="hidden sm:inline">Formulir SPMB</span>
                </a>
                <a href="brosur.php" target="_blank" class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 border border-amber-200 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-book-open text-[10px]"></i> <span class="hidden sm:inline">Brosur Digital</span>
                </a>
                <div class="h-8 w-8 rounded-full bg-[#0b8478] flex items-center justify-center text-white font-bold text-xs shadow-xs">
                    PSB
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 pb-20 md:pb-6 custom-scrollbar space-y-6">
            
            <!-- BANNER WELCOME & ACTIONS -->
            <div class="bg-gradient-to-r from-teal-800 via-[#0b8478] to-emerald-800 rounded-3xl p-5 sm:p-7 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="px-3 py-1 bg-white/20 text-teal-100 rounded-full text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1.5 mb-2">
                            <i class="fas fa-circle text-[8px] text-emerald-300 animate-pulse"></i> Panel Utama Panitia PSB
                        </span>
                        <h1 class="text-xl sm:text-2xl font-black text-white leading-tight">
                            Pusat Komando Penerimaan Santri Baru (PSB)
                        </h1>
                        <p class="text-xs sm:text-sm text-teal-100/90 mt-1 max-w-xl leading-relaxed">
                            Kelola pendaftaran santri baru, verifikasi berkas, pipeline prospek calon santri, seleksi, serta materi brosur digital sekolah.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="admin-spmb.php" class="px-4 py-2 bg-white hover:bg-teal-50 text-[#0b8478] rounded-xl text-xs font-black shadow-sm transition flex items-center gap-1.5">
                            <i class="fas fa-list-check"></i> Kelola Database PSB
                        </a>
                        <a href="export-spmb.php" class="px-4 py-2 bg-teal-900/60 hover:bg-teal-900 text-teal-100 rounded-xl text-xs font-bold transition flex items-center gap-1.5 border border-teal-600/50">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <!-- STATISTIK WIDGETS -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                <!-- 1. Total Pendaftar -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-lg font-bold flex-shrink-0">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pendaftar</p>
                        <h3 class="text-xl font-black text-slate-900 leading-none mt-0.5"><?= $tot_pendaftar ?></h3>
                    </div>
                </div>

                <!-- 2. Lulus Seleksi -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold flex-shrink-0">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Lulus Seleksi</p>
                        <h3 class="text-xl font-black text-emerald-700 leading-none mt-0.5"><?= $tot_lulus ?></h3>
                    </div>
                </div>

                <!-- 3. Menunggu Tes -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold flex-shrink-0">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Menunggu Tes</p>
                        <h3 class="text-xl font-black text-amber-700 leading-none mt-0.5"><?= $tot_menunggu ?></h3>
                    </div>
                </div>

                <!-- 4. Daftar Ulang -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold flex-shrink-0">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Daftar Ulang</p>
                        <h3 class="text-xl font-black text-indigo-700 leading-none mt-0.5"><?= $tot_daftar_ulang ?></h3>
                    </div>
                </div>

                <!-- 5. Leads Brosur -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3 col-span-2 lg:col-span-1">
                    <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold flex-shrink-0">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Leads Brosur</p>
                        <h3 class="text-xl font-black text-rose-700 leading-none mt-0.5"><?= $tot_leads ?></h3>
                    </div>
                </div>
            </div>

            <!-- SHORTCUT MODUL RUANG PSB -->
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-th-large text-[#0b8478]"></i> Menu & Modul Operasional PSB
                    </h2>
                    <span class="text-[11px] text-slate-400">Pilih modul operasional pendaftaran</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                    <!-- 1. Database PSB -->
                    <a href="admin-spmb.php" class="bg-white hover:bg-emerald-50/50 border border-slate-200 hover:border-emerald-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700">Database PSB</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">Data Calon Santri</span>
                    </a>

                    <!-- 2. Pipeline Prospek -->
                    <a href="data-pipeline.php" class="bg-white hover:bg-amber-50/50 border border-slate-200 hover:border-amber-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-columns"></i>
                        </div>
                        <span class="text-xs font-black text-slate-900 group-hover:text-amber-700">Pipeline Prospek</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">Kanban Follow Up</span>
                    </a>

                    <!-- 3. Brosur Digital -->
                    <a href="admin-brosur-settings.php" class="bg-white hover:bg-teal-50/50 border border-slate-200 hover:border-teal-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-teal-100 text-[#0b8478] flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <span class="text-xs font-black text-slate-900 group-hover:text-[#0b8478]">Brosur Digital</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">Pengaturan Materi</span>
                    </a>

                    <!-- 4. Siaran WA Mitra -->
                    <a href="admin-siaran-wa.php" class="bg-white hover:bg-emerald-50/50 border border-slate-200 hover:border-emerald-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-broadcast-tower"></i>
                        </div>
                        <span class="text-xs font-black text-slate-900 group-hover:text-emerald-700">Siaran WA Mitra</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">Blast Brosur & Info</span>
                    </a>

                    <!-- 5. Data Agen / Mitra -->
                    <a href="data-agen.php" class="bg-white hover:bg-blue-50/50 border border-slate-200 hover:border-blue-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="text-xs font-black text-slate-900 group-hover:text-blue-700">Data Agen Mitra</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">Jaringan Referral</span>
                    </a>

                    <!-- 6. Tes Kesiapan Santri -->
                    <a href="tes-kesiapan-anak.php" target="_blank" class="bg-white hover:bg-purple-50/50 border border-slate-200 hover:border-purple-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="text-xs font-black text-slate-900 group-hover:text-purple-700">Tes Kesiapan</span>
                        <span class="text-[10px] text-slate-500 mt-0.5">Asesmen Masuk <i class="fas fa-external-link-alt text-[8px]"></i></span>
                    </a>
                </div>
            </div>

            <!-- GRAFIK & TABEL PENDAFTAR TERBARU -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- GRAFIK JENJANG PENDAFTAR -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-black text-slate-800 text-sm flex items-center gap-2">
                            <i class="fas fa-chart-pie text-[#0b8478]"></i> Pendaftar per Jenjang
                        </h3>
                        <span class="text-[10px] text-slate-400 font-bold uppercase">Data Realtime</span>
                    </div>
                    <div class="flex-1 flex items-center justify-center">
                        <div class="w-48 h-48">
                            <canvas id="jenjangChart"></canvas>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100 text-center">
                        <p class="text-xs text-slate-500">Total data tercatat: <span class="font-bold text-slate-800"><?= $tot_pendaftar ?> calon santri</span></p>
                    </div>
                </div>

                <!-- TABEL PENDAFTAR TERBARU -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden lg:col-span-2 flex flex-col">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm flex items-center gap-2">
                                <i class="fas fa-user-plus text-emerald-600"></i> Pendaftar SPMB Terbaru
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-0.5">5 calon santri yang mendaftar paling baru via web</p>
                        </div>
                        <a href="admin-spmb.php" class="px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-[#0b8478] text-xs font-bold transition flex items-center gap-1">
                            Lihat Semua <i class="fas fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50/75">
                                <tr>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase">Calon Santri</th>
                                    <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase">Asal Sekolah</th>
                                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Status Seleksi</th>
                                    <th class="px-5 py-3 text-center text-xs font-bold text-slate-500 uppercase">Daftar Ulang</th>
                                    <th class="px-5 py-3 text-right text-xs font-bold text-slate-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <?php if (count($pendaftar_terbaru) > 0): ?>
                                    <?php foreach ($pendaftar_terbaru as $p): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-5 py-3.5 whitespace-nowrap">
                                            <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($p['nama_lengkap']) ?></div>
                                            <div class="text-[11px] text-teal-700 font-bold uppercase"><?= htmlspecialchars($p['jenjang']) ?></div>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-xs text-slate-600">
                                            <?= htmlspecialchars($p['asal_sekolah'] ?? '-') ?>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                            <?php 
                                            $st = $p['status'];
                                            $bg_badge = 'bg-amber-100 text-amber-800 border-amber-200';
                                            if ($st == 'Lulus Seleksi') $bg_badge = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                            if ($st == 'Ditolak') $bg_badge = 'bg-rose-100 text-rose-800 border-rose-200';
                                            ?>
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-black border <?= $bg_badge ?>">
                                                <?= htmlspecialchars($st ?: 'Menunggu Tes') ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-center">
                                            <?php 
                                            $sdu = $p['status_daftar_ulang'] ?? 'Belum';
                                            $badge_du = ($sdu === 'Sudah' || $sdu === 'Lunas') ? 'bg-indigo-100 text-indigo-800 border-indigo-200' : 'bg-slate-100 text-slate-600 border-slate-200';
                                            ?>
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= $badge_du ?>">
                                                <?= htmlspecialchars($sdu) ?>
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs font-bold">
                                            <a href="admin-spmb.php" class="text-[#0b8478] hover:underline">
                                                Detail <i class="fas fa-chevron-right text-[10px]"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-slate-400 text-xs">Belum ada data pendaftar baru.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Script Chart.js -->
    <script>
        const ctxJenjang = document.getElementById('jenjangChart').getContext('2d');
        new Chart(ctxJenjang, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($jenjang_labels) ?>,
                datasets: [{
                    data: <?= json_encode($jenjang_data) ?>,
                    backgroundColor: ['#0b8478', '#3b82f6', '#f59e0b', '#8b5cf6'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 11, weight: 'bold' } }
                    }
                },
                cutout: '70%'
            }
        });
    </script>
</body>
</html>
