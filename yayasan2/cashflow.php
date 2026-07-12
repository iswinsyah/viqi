<?php
require_once 'auth.php'; // Keamanan Ruang Yayasan
require_once '../koneksi.php';
require_once 'setup-pembukuan.php'; // Garansi tabel inisialisasi

$active_menu = 'cashflow';

// 1. Ambil data Pemasukan SPP
$res_spp = $conn->query("SELECT SUM(jumlah) as total FROM pembayaran_spp WHERE status = 'Berhasil'");
$pemasukan_spp = $res_spp ? (double)($res_spp->fetch_assoc()['total'] ?? 0.0) : 0.0;

// 2. Ambil data Jurnal Pendapatan
$res_jurnal_inc = $conn->query("SELECT SUM(d.kredit - d.debit) as total 
    FROM keuangan_jurnal_detail d 
    JOIN keuangan_akun a ON d.akun_id = a.id 
    WHERE a.tipe_akun = 'Pendapatan'");
$pemasukan_jurnal = $res_jurnal_inc ? (double)($res_jurnal_inc->fetch_assoc()['total'] ?? 0.0) : 0.0;

// 3. Ambil data Jurnal Beban/Pengeluaran
$res_jurnal_exp = $conn->query("SELECT SUM(d.debit - d.kredit) as total 
    FROM keuangan_jurnal_detail d 
    JOIN keuangan_akun a ON d.akun_id = a.id 
    WHERE a.tipe_akun = 'Beban'");
$pengeluaran_jurnal = $res_jurnal_exp ? (double)($res_jurnal_exp->fetch_assoc()['total'] ?? 0.0) : 0.0;

// 4. Hitung Gaji Ustadz Offline (Rumus: Total Kelas Sasaran Mapel Offline Non-Musyrif * Tarif Grade A * 4 Pekan)
$sql_offline = "SELECT id, nama_mapel, kategori_mapel, 
    (SELECT COUNT(kelas_id) FROM mapel_kelas_target WHERE mapel_id = master_mapel.id) as jumlah_kelas 
    FROM master_mapel 
    WHERE metode_belajar = 'offline' 
      AND id NOT IN (
          SELECT DISTINCT m_id FROM (
              SELECT COALESCE(mapel_1_id, 0) as m_id FROM kesediaan_mengajar k JOIN akun_ustadz u ON k.ustadz_id = u.id WHERE u.role LIKE '%musyrif%'
              UNION
              SELECT COALESCE(mapel_2_id, 0) as m_id FROM kesediaan_mengajar k JOIN akun_ustadz u ON k.ustadz_id = u.id WHERE u.role LIKE '%musyrif%'
              UNION
              SELECT COALESCE(mapel_3_id, 0) as m_id FROM kesediaan_mengajar k JOIN akun_ustadz u ON k.ustadz_id = u.id WHERE u.role LIKE '%musyrif%'
          ) as tmp
      )";
$res_mapel_offline = $conn->query($sql_offline);

$mapel_offline_count = 0;
$total_kelas_offline = 0;
$mapel_offline_list = [];

if ($res_mapel_offline) {
    $mapel_offline_count = $res_mapel_offline->num_rows;
    while ($row = $res_mapel_offline->fetch_assoc()) {
        $kelas_count = (int)$row['jumlah_kelas'];
        $total_kelas_offline += $kelas_count;
        $mapel_offline_list[] = $row;
    }
}

$res_rate = $conn->query("SELECT gaji_grade_a FROM pengaturan_gaji WHERE id = 1");
$rate_grade_a = 25000;
if ($res_rate && $res_rate->num_rows > 0) {
    $rate_grade_a = (double)$res_rate->fetch_assoc()['gaji_grade_a'];
}

$cost_gaji_offline = $total_kelas_offline * $rate_grade_a * 4;

// 5. Total Akumulasi
$total_pemasukan = $pemasukan_spp + $pemasukan_jurnal;
$total_pengeluaran = $pengeluaran_jurnal + $cost_gaji_offline;
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// Persentase Pengeluaran terhadap Pemasukan
$persen_pengeluaran = $total_pemasukan > 0 ? round(($total_pengeluaran / $total_pemasukan) * 100, 1) : 0;

// Query Jumlah Santri untuk default perencanaan
$res_count_santri = $conn->query("SELECT COUNT(*) as count FROM buku_induk_santri");
$default_santri_count = ($res_count_santri) ? (int)$res_count_santri->fetch_assoc()['count'] : 0;
if ($default_santri_count === 0) {
    $res_count_s = $conn->query("SELECT COUNT(*) as count FROM santri");
    $default_santri_count = ($res_count_s) ? (int)$res_count_s->fetch_assoc()['count'] : 120; // Default fallback
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashflow Terpadu | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <!-- INCLUDE SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-xs font-bold text-gray-400 bg-amber-50 border border-amber-200 px-2.5 py-1 rounded-full"><i class="fas fa-sync-alt mr-1 animate-spin text-amber-500"></i> Real-time Sync</span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-4">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center"><i class="fas fa-funnel-dollar text-amber-600 mr-2"></i>Cashflow Terpadu</h1>
                <p class="text-xs text-gray-500 mt-1">Perhitungan total operasional sekolah dan asrama beserta simulasi arus kas tahun ajaran baru.</p>
            </div>

            <!-- TABS NAVIGATION -->
            <div class="mb-6 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="switchTab('aktual')" id="tab-btn-aktual" class="border-amber-500 text-amber-600 whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm flex items-center transition-all focus:outline-none">
                        <i class="fas fa-chart-pie mr-2 text-amber-500"></i> Cashflow Aktual (Real-Time)
                    </button>
                    <button onclick="switchTab('proyeksi')" id="tab-btn-proyeksi" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap pb-4 px-1 border-b-2 font-semibold text-sm flex items-center transition-all focus:outline-none">
                        <i class="fas fa-sliders-h mr-2 text-gray-400"></i> Perencanaan & Proyeksi TA 2026/2027
                    </button>
                </nav>
            </div>

            <!-- ==================== CONTENT TAB 1: AKTUAL ==================== -->
            <div id="tab-content-aktual" class="block">
                <!-- RINGKASAN WIDGET CARD UTAMA -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- CARD PEMASUKAN -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex items-center justify-between relative overflow-hidden">
                        <div class="absolute right-0 bottom-0 opacity-5 text-emerald-600 text-8xl translate-x-4 translate-y-4"><i class="fas fa-arrow-alt-circle-down"></i></div>
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Pemasukan</span>
                            <span class="text-3xl font-black text-emerald-600">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></span>
                            <span class="text-[10px] text-gray-400 block mt-1.5"><i class="fas fa-info-circle mr-1"></i> SPP Terverifikasi + Jurnal Pendapatan</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-sm"><i class="fas fa-wallet"></i></div>
                    </div>

                    <!-- CARD PENGELUARAN -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex items-center justify-between relative overflow-hidden">
                        <div class="absolute right-0 bottom-0 opacity-5 text-rose-600 text-8xl translate-x-4 translate-y-4"><i class="fas fa-arrow-alt-circle-up"></i></div>
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Pengeluaran</span>
                            <span class="text-3xl font-black text-rose-600">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></span>
                            <span class="text-[10px] text-gray-400 block mt-1.5"><i class="fas fa-info-circle mr-1"></i> Beban Jurnal + Estimasi Gaji Offline</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl shadow-sm"><i class="fas fa-receipt"></i></div>
                    </div>

                    <!-- CARD SALDO BERSIH -->
                    <div class="bg-white p-6 rounded-2xl border border-gray-150 shadow-sm flex items-center justify-between relative overflow-hidden <?= $saldo_bersih >= 0 ? 'border-l-4 border-l-teal-500' : 'border-l-4 border-l-rose-500' ?>">
                        <div class="absolute right-0 bottom-0 opacity-5 text-amber-600 text-8xl translate-x-4 translate-y-4"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Sisa Saldo Bersih</span>
                            <span class="text-3xl font-black <?= $saldo_bersih >= 0 ? 'text-teal-600' : 'text-rose-700' ?>">
                                Rp <?= number_format($saldo_bersih, 0, ',', '.') ?>
                            </span>
                            <span class="text-[10px] text-gray-400 block mt-1.5"><i class="fas fa-info-circle mr-1"></i> Surplus / Defisit Operasional</span>
                        </div>
                        <div class="w-12 h-12 rounded-xl <?= $saldo_bersih >= 0 ? 'bg-teal-50 text-teal-600' : 'bg-rose-50 text-rose-700' ?> flex items-center justify-center text-xl shadow-sm"><i class="fas fa-scale-balanced"></i></div>
                    </div>
                </div>

                <!-- PROGRESS OPERASIONAL BAR -->
                <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm mb-6">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Rasio Penggunaan Dana Operasional</span>
                        <span class="text-xs font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded"><?= $persen_pengeluaran ?>% Terpakai</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3.5 overflow-hidden flex border border-gray-200">
                        <div class="bg-rose-500 h-full rounded-full transition-all duration-550" style="width: <?= min($persen_pengeluaran, 100) ?>%"></div>
                    </div>
                    <p class="text-[10px] text-gray-400 mt-2">
                        <?php if ($persen_pengeluaran > 100): ?>
                            <span class="text-rose-600 font-semibold"><i class="fas fa-exclamation-triangle mr-1"></i> Defisit! Pengeluaran operasional sekolah melebihi total pemasukan terdaftar.</span>
                        <?php else: ?>
                            <span class="text-teal-600 font-semibold"><i class="fas fa-check-circle mr-1"></i> Aman! Pemasukan terdaftar masih mencukupi biaya operasional.</span>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- DETAILED GRID -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- KIRI: RINCIAN PENGELUARAN (COSTS) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
                            <h2 class="font-bold text-rose-800 text-sm flex items-center"><i class="fas fa-arrow-circle-up mr-1.5 text-rose-600"></i>Rincian Biaya Operasional (Beban)</h2>
                            <span class="text-[10px] bg-rose-100 text-rose-800 font-bold px-2 py-0.5 rounded">Pengeluaran</span>
                        </div>
                        
                        <div class="p-6 space-y-6">
                            <!-- BOX ESTIMASI GAJI USTADZ OFFLINE (FORMULA DARI USER) -->
                            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-xl p-5 relative overflow-hidden">
                                <div class="absolute right-0 top-0 opacity-10 text-amber-500 text-7xl translate-x-2 -translate-y-2"><i class="fas fa-chalkboard-teacher"></i></div>
                                <h3 class="font-bold text-amber-900 text-sm mb-1.5 flex items-center">
                                    <i class="fas fa-calculator mr-1.5 text-amber-600"></i>Cost Gaji Ustadz Offline (Estimasi)
                                </h3>
                                <p class="text-[11px] text-amber-800 leading-relaxed mb-4">
                                    Dihitung dengan rumus: <strong>Total Kelas Sasaran Mapel Offline (selain diampu Musyrif)</strong> dikalikan <strong>Tarif Gaji Grade A</strong> dikalikan <strong>4 pekan</strong>. Mapel yang diampu oleh Musyrif dikecualikan karena sudah masuk dalam komponen gaji pokok bulanan mereka.
                                </p>

                                <div class="grid grid-cols-3 gap-2 bg-white/70 backdrop-blur-sm rounded-lg p-3.5 border border-amber-150 mb-3 text-center">
                                    <div>
                                        <span class="text-[9px] font-bold text-gray-500 uppercase block">Total Kelas Sasaran</span>
                                        <span class="text-sm font-black text-amber-800 block"><?= $total_kelas_offline ?> Kelas</span>
                                    </div>
                                    <div>
                                        <span class="text-[9px] font-bold text-gray-500 uppercase block">Tarif Grade A</span>
                                        <span class="text-sm font-black text-amber-800 block">Rp <?= number_format($rate_grade_a, 0, ',', '.') ?></span>
                                    </div>
                                    <div>
                                        <span class="text-[9px] font-bold text-gray-500 uppercase block">Pekan / Bulan</span>
                                        <span class="text-sm font-black text-amber-800 block">4 Pekan</span>
                                    </div>
                                </div>

                                <div class="flex justify-between items-center border-t border-amber-200/60 pt-3">
                                    <span class="text-xs font-bold text-amber-900">Total Biaya Estimasi:</span>
                                    <span class="text-xl font-black text-amber-600">Rp <?= number_format($cost_gaji_offline, 0, ',', '.') ?></span>
                                </div>

                                <?php if ($mapel_offline_count > 0): ?>
                                    <div class="mt-4 pt-3 border-t border-amber-200/40">
                                        <button onclick="toggleOfflineList()" class="text-[10px] font-bold text-amber-700 hover:text-amber-900 flex items-center focus:outline-none">
                                            <i class="fas fa-list mr-1"></i> Tampilkan Rincian Kelas Mapel Offline (<?= $mapel_offline_count ?>) <i class="fas fa-chevron-down ml-1 text-[8px]" id="arrow-list"></i>
                                        </button>
                                        <div id="offlineMapelList" class="hidden mt-2 space-y-1 max-h-40 overflow-y-auto pl-2">
                                            <?php foreach ($mapel_offline_list as $mo): ?>
                                                <div class="text-[11px] text-gray-600 flex justify-between items-center border-b border-gray-100 py-1">
                                                    <span><i class="fas fa-check text-[8px] text-amber-600 mr-1.5"></i><strong><?= htmlspecialchars($mo['nama_mapel']) ?></strong></span>
                                                    <span class="text-[9px] font-bold bg-amber-100 border border-amber-200 px-2.5 py-0.5 rounded text-amber-800"><?= (int)$mo['jumlah_kelas'] ?> Kelas</span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- RINCIAN DARI PEMBUKUAN BEBAN JURNAL -->
                            <div>
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2.5 flex items-center"><i class="fas fa-book-bookmark mr-1 text-rose-500"></i>Rincian Jurnal Beban Operasional</h3>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-150 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-bold text-gray-500">Akun Beban</th>
                                                <th class="px-4 py-2 text-right font-bold text-gray-500">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr>
                                                <td class="px-4 py-2.5 font-semibold text-gray-800 text-left">Beban Gaji Ustadz Offline (Estimasi Formula)</td>
                                                <td class="px-4 py-2.5 text-right font-bold text-slate-900">Rp <?= number_format($cost_gaji_offline, 0, ',', '.') ?></td>
                                            </tr>
                                            <?php
                                            $res_beban_list = $conn->query("SELECT a.nama_akun, a.kode_akun, SUM(d.debit - d.kredit) as total
                                                FROM keuangan_jurnal_detail d
                                                JOIN keuangan_akun a ON d.akun_id = a.id
                                                WHERE a.tipe_akun = 'Beban'
                                                GROUP BY a.id
                                                ORDER BY a.kode_akun ASC");
                                            
                                            $has_beban = false;
                                            if ($res_beban_list && $res_beban_list->num_rows > 0):
                                                while($row = $res_beban_list->fetch_assoc()):
                                                    if ($row['total'] == 0) continue;
                                                    $has_beban = true;
                                            ?>
                                                    <tr>
                                                        <td class="px-4 py-2.5 text-gray-600 text-left"><?= htmlspecialchars($row['nama_akun']) ?> (<?= $row['kode_akun'] ?>)</td>
                                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-900">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                                    </tr>
                                            <?php 
                                                endwhile;
                                            endif;
                                            
                                            if (!$has_beban && $cost_gaji_offline == 0):
                                            ?>
                                                <tr>
                                                    <td colspan="2" class="px-4 py-6 text-center text-gray-400 italic">Belum ada transaksi beban/pengeluaran terdaftar.</td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr class="bg-rose-50/50 font-bold border-t-2 border-rose-100">
                                                <td class="px-4 py-3 text-rose-900 text-left">Total Pengeluaran Operasional</td>
                                                <td class="px-4 py-3 text-right text-rose-700">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN: RINCIAN PEMASUKAN (REVENUES) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                        <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
                            <h2 class="font-bold text-emerald-800 text-sm flex items-center"><i class="fas fa-arrow-circle-down mr-1.5 text-emerald-600"></i>Rincian Pemasukan (Pendapatan)</h2>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">Pendapatan</span>
                        </div>

                        <div class="p-6 space-y-6 flex-1">
                            <div>
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2.5 flex items-center"><i class="fas fa-receipt mr-1 text-emerald-500"></i>Rincian Saldo Pemasukan Terdaftar</h3>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <table class="min-w-full divide-y divide-gray-150 text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left font-bold text-gray-500">Kategori Akun Pendapatan</th>
                                                <th class="px-4 py-2 text-right font-bold text-gray-500">Jumlah</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            <tr>
                                                <td class="px-4 py-2.5 font-semibold text-gray-800 text-left">Pendapatan SPP Santri (Tabel Pembayaran)</td>
                                                <td class="px-4 py-2.5 text-right font-bold text-slate-900">Rp <?= number_format($pemasukan_spp, 0, ',', '.') ?></td>
                                            </tr>
                                            
                                            <?php
                                            $res_inc_list = $conn->query("SELECT a.nama_akun, a.kode_akun, SUM(d.kredit - d.debit) as total
                                                FROM keuangan_jurnal_detail d
                                                JOIN keuangan_akun a ON d.akun_id = a.id
                                                WHERE a.tipe_akun = 'Pendapatan'
                                                GROUP BY a.id
                                                ORDER BY a.kode_akun ASC");
                                            
                                            $has_income = false;
                                            if ($res_inc_list && $res_inc_list->num_rows > 0):
                                                while($row = $res_inc_list->fetch_assoc()):
                                                    if ($row['total'] == 0) continue;
                                                    $has_income = true;
                                            ?>
                                                    <tr>
                                                        <td class="px-4 py-2.5 text-gray-600 text-left"><?= htmlspecialchars($row['nama_akun']) ?> (<?= $row['kode_akun'] ?>)</td>
                                                        <td class="px-4 py-2.5 text-right font-semibold text-slate-900">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                                    </tr>
                                            <?php 
                                                endwhile;
                                            endif;
                                            
                                            if (!$has_income && $pemasukan_spp == 0):
                                            ?>
                                                <tr>
                                                    <td colspan="2" class="px-4 py-6 text-center text-gray-400 italic">Belum ada transaksi pendapatan masuk.</td>
                                                </tr>
                                            <?php endif; ?>
                                            
                                            <tr class="bg-emerald-50/50 font-bold border-t-2 border-emerald-100">
                                                <td class="px-4 py-3 text-emerald-900 text-left">Total Pemasukan Operasional</td>
                                                <td class="px-4 py-3 text-right text-emerald-700">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4.5">
                                <h4 class="font-bold text-slate-700 text-xs mb-2 flex items-center"><i class="fas fa-info-circle mr-1 text-slate-500"></i> Catatan Evaluasi Finansial</h4>
                                <p class="text-[11px] text-gray-500 leading-relaxed">
                                    Laporan cashflow di atas menggabungkan transaksi real-time dari pembukuan AI serta modul pembayaran SPP santri. Estimasi biaya gaji ustadz offline dihitung secara dinamis berdasarkan data mata pelajaran online yang terdaftar saat ini. Hal ini mempermudah Yayasan dalam melakukan simulasi alokasi dana dan peramalan biaya operasional.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== CONTENT TAB 2: PROYEKSI & SIMULASI ==================== -->
            <div id="tab-content-proyeksi" class="hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- KIRI (Col-4): FORMULIR SIMULASI -->
                    <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-250 shadow-sm overflow-hidden p-6 space-y-5 text-left">
                        <div class="border-b border-gray-100 pb-3 mb-1">
                            <h3 class="font-bold text-slate-900 text-sm flex items-center"><i class="fas fa-sliders-h text-amber-500 mr-2"></i>Parameter Simulasi TA 26/27</h3>
                            <p class="text-[10px] text-gray-400 mt-1">Ubah nilai di bawah ini untuk melihat dampaknya pada arus kas.</p>
                        </div>

                        <!-- GRUP PEMASUKAN -->
                        <div class="space-y-3.5">
                            <h4 class="text-xs font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded flex items-center uppercase tracking-wider"><i class="fas fa-wallet mr-1.5"></i>Proyeksi Pemasukan</h4>
                            
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Kas Awal Tahun Ajaran</label>
                                <input type="number" id="sim_init_cash" value="120000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <!-- SPP BERDASARKAN 3 ANGKATAN -->
                            <div class="p-3 bg-emerald-50/30 rounded-xl border border-emerald-100 space-y-2">
                                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wide block"><i class="fas fa-users mr-1"></i>SPP 3 Angkatan Santri</span>
                                
                                <!-- Angkatan Terlama (2024) -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Santri Terlama ('24)</label>
                                        <input type="number" id="sim_santri_2024" value="35" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Tarif SPP / Bln</label>
                                        <input type="number" id="sim_spp_2024" value="1100000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>

                                <!-- Angkatan Tengah (2025) -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Santri Tengah ('25)</label>
                                        <input type="number" id="sim_santri_2025" value="40" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Tarif SPP / Bln</label>
                                        <input type="number" id="sim_spp_2025" value="1300000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>

                                <!-- Angkatan Baru (2026) -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Santri Baru ('26)</label>
                                        <input type="number" id="sim_santri_2026" value="45" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Tarif SPP / Bln</label>
                                        <input type="number" id="sim_spp_2026" value="1500000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                            </div>

                            <!-- SUBSIDI BEASISWA / KERINGANAN SPP -->
                            <div class="p-3 bg-sky-50/20 rounded-xl border border-sky-100 space-y-2">
                                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wide block"><i class="fas fa-hand-holding-hand mr-1"></i>Beasiswa & Keringanan SPP</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Penerima Beasiswa</label>
                                        <input type="number" id="sim_beasiswa_count" value="12" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Rata Potongan / Bln</label>
                                        <input type="number" id="sim_beasiswa_potongan" value="750000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 mb-1">Target Santri Baru</label>
                                    <input type="number" id="sim_new_santri" value="45" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 mb-1">Uang Pangkal / Anak</label>
                                    <input type="number" id="sim_uang_pangkal" value="12000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Estimasi Donasi Bulanan</label>
                                <input type="number" id="sim_donasi" value="7500000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>
                        </div>

                        <!-- GRUP PENGELUARAN -->
                        <div class="space-y-3.5 pt-3 border-t border-gray-100">
                            <h4 class="text-xs font-bold text-rose-800 bg-rose-50 px-2.5 py-1 rounded flex items-center uppercase tracking-wider"><i class="fas fa-receipt mr-1.5"></i>Proyeksi Pengeluaran</h4>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Gaji Pokok & Staff Tetap / Bulan</label>
                                <input type="number" id="sim_gaji_pokok" value="48000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Honor Mengajar Ustadz Offline / Bulan</label>
                                <input type="number" id="sim_honor_offline" value="<?= $cost_gaji_offline ?>" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Biaya Konsumsi / Anak / Hari</label>
                                <input type="number" id="sim_cost_makan" value="22000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200 space-y-2">
                                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wide block"><i class="fas fa-house-chimney mr-1"></i>Sewa Rumah Asrama</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-1">Tarif Sewa / Thn</label>
                                        <input type="number" id="sim_sewa_asrama" value="35000000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-1">Jumlah Unit Sewa</label>
                                        <input type="number" id="sim_jumlah_rumah" value="3" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-semibold text-gray-500 mb-1">Bulan Pembayaran Sewa</label>
                                    <select id="sim_rent_month" onchange="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                        <option value="0">Juli 2026</option>
                                        <option value="1">Agustus 2026</option>
                                        <option value="2">September 2026</option>
                                        <option value="5">Desember 2026</option>
                                        <option value="6">Januari 2027</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 mb-1">Utilitas / Bulan</label>
                                    <input type="number" id="sim_utilitas" value="6000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 mb-1">Biaya Ujian/Semester</label>
                                    <input type="number" id="sim_biaya_ujian" value="12000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Anggaran THR Pegawai (Bulan April 27)</label>
                                <input type="number" id="sim_anggaran_thr" value="38000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>
                        </div>

                        <!-- KARTU PANDUAN JURNAL AKUNTANSI -->
                        <div class="bg-blue-50/50 border border-blue-200 rounded-xl p-4 text-xs text-left space-y-2 mt-4">
                            <span class="font-bold text-blue-900 flex items-center"><i class="fas fa-book mr-1.5 text-blue-600"></i>Panduan Pembukuan Beasiswa</span>
                            <p class="text-[10px] text-blue-800 leading-relaxed">
                                Untuk santri penerima beasiswa / keringanan, catat transaksi di pembukuan menggunakan akun beban penyeimbang:
                            </p>
                            <div class="bg-white/80 rounded border border-blue-100 p-2 font-mono text-[9px] text-slate-700 space-y-1">
                                <div><strong class="text-blue-950">[D]</strong> Kas/Bank: Rp 900.000</div>
                                <div><strong class="text-blue-950">[D]</strong> Beban Keringanan/Beasiswa: Rp 600.000</div>
                                <div><strong class="text-rose-900">[K]</strong> Pendapatan SPP Santri: Rp 1.500.000</div>
                            </div>
                            <span class="text-[9px] text-gray-400 block italic font-semibold">Tujuan: Menjaga total kapasitas pendapatan kotor tetap tercatat & subsidi beasiswa terukur jelas.</span>
                        </div>
                    </div>

                    <!-- KANAN (Col-8): GRAFIK & TABEL HASIL SIMULASI -->
                    <div class="lg:col-span-8 space-y-6">
                        <!-- BANNER ALARM KESEHATAN KAS -->
                        <div id="projection-alert" class="bg-teal-100 text-teal-800 border border-teal-200 p-4 rounded-xl flex items-start text-xs text-left shadow-sm">
                            <i class="fas fa-check-circle text-teal-600 mr-2.5 text-base mt-0.5"></i>
                            <div>
                                <span class="font-bold">Menganalisis Proyeksi...</span> Harap isi parameter di sebelah kiri.
                            </div>
                        </div>

                        <!-- RINGKASAN OUTPUT PROYEKSI -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Total Pendapatan</span>
                                <span class="text-sm font-black text-slate-800 block mt-1" id="summary_total_rev">Rp 0</span>
                            </div>
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Total Pengeluaran</span>
                                <span class="text-sm font-black text-slate-800 block mt-1" id="summary_total_exp">Rp 0</span>
                            </div>
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Surplus / Defisit Kumulatif</span>
                                <span class="text-sm font-black text-teal-600 block mt-1" id="summary_surplus">Rp 0</span>
                            </div>
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center bg-amber-50 border-amber-200">
                                <span class="text-[9px] font-bold text-amber-700 uppercase tracking-wide">Estimasi Kas Akhir</span>
                                <span class="text-sm font-black text-amber-800 block mt-1" id="summary_ending_cash">Rp 0</span>
                            </div>
                        </div>

                        <!-- GRAFIK TREN KAS -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm text-left">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wide"><i class="fas fa-chart-line text-amber-500 mr-1.5"></i>Tren Saldo Kas Kumulatif (TA 2026/2027)</h3>
                                <div class="text-[10px] text-gray-400 space-x-3.5 font-medium">
                                    <span><i class="fas fa-arrow-down text-rose-500 mr-1"></i>Titik Terendah: <strong id="summary_lowest_cash" class="text-slate-700">Rp 0</strong></span>
                                    <span><i class="fas fa-arrow-up text-teal-500 mr-1"></i>Titik Tertinggi: <strong id="summary_peak_cash" class="text-slate-700">Rp 0</strong></span>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas id="chart-projection"></canvas>
                            </div>
                        </div>

                        <!-- TABEL DETAIL PROYEKSI BULANAN -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden text-left">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wide"><i class="fas fa-table mr-1.5 text-amber-500"></i>Tabel Proyeksi Finansial Bulanan</h3>
                                <span class="text-[9px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded">12 Bulan</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-center divide-y divide-gray-150">
                                    <thead class="bg-gray-50 text-[9px] uppercase tracking-wider text-gray-500 font-bold">
                                        <tr class="divide-x divide-gray-100">
                                            <th class="px-3 py-3 text-left w-[120px]" rowspan="2">Bulan</th>
                                            <th class="px-2 py-2" colspan="5">Pemasukan (Inflow)</th>
                                            <th class="px-2 py-2" colspan="7">Pengeluaran (Outflow)</th>
                                            <th class="px-2 py-3 w-[100px]" rowspan="2">Net Cashflow</th>
                                            <th class="px-3 py-3 w-[120px]" rowspan="2">Saldo Kas</th>
                                        </tr>
                                        <tr class="divide-x divide-gray-100 border-t border-gray-150 text-[8px]">
                                            <th class="px-2 py-1.5 text-right">SPP Kotor</th>
                                            <th class="px-2 py-1.5 text-right">Keringanan</th>
                                            <th class="px-2 py-1.5 text-right">U. Pangkal</th>
                                            <th class="px-2 py-1.5 text-right">Donasi</th>
                                            <th class="px-2 py-1.5 text-right bg-emerald-50/50">Total</th>
                                            <th class="px-2 py-1.5 text-right">Gaji Pokok</th>
                                            <th class="px-2 py-1.5 text-right">H. Offline</th>
                                            <th class="px-2 py-1.5 text-right">Makan</th>
                                            <th class="px-2 py-1.5 text-right">Sewa Asrama</th>
                                            <th class="px-2 py-1.5 text-right">Utilitas</th>
                                            <th class="px-2 py-1.5 text-right">Ujian/Keg</th>
                                            <th class="px-2 py-1.5 text-right">THR</th>
                                            <th class="px-2 py-1.5 text-right bg-rose-50/50">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="projection-table-body" class="divide-y divide-gray-100">
                                        <!-- Javascript will populate rows here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JS COLLAPSE & SIMULATION LOGIC -->
    <script>
        function toggleOfflineList() {
            const list = document.getElementById("offlineMapelList");
            const arrow = document.getElementById("arrow-list");
            if (list.classList.contains("hidden")) {
                list.classList.remove("hidden");
                arrow.className = "fas fa-chevron-up ml-1 text-[8px]";
            } else {
                list.classList.add("hidden");
                arrow.className = "fas fa-chevron-down ml-1 text-[8px]";
            }
        }

        // TABS SWITCHER
        function switchTab(tab) {
            const btnAktual = document.getElementById('tab-btn-aktual');
            const btnProyeksi = document.getElementById('tab-btn-proyeksi');
            const contentAktual = document.getElementById('tab-content-aktual');
            const contentProyeksi = document.getElementById('tab-content-proyeksi');

            if (tab === 'aktual') {
                btnAktual.className = "border-amber-500 text-amber-600 whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm flex items-center transition-all focus:outline-none";
                btnProyeksi.className = "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap pb-4 px-1 border-b-2 font-semibold text-sm flex items-center transition-all focus:outline-none";
                contentAktual.classList.remove('hidden');
                contentProyeksi.classList.add('hidden');
            } else {
                btnProyeksi.className = "border-amber-500 text-amber-600 whitespace-nowrap pb-4 px-1 border-b-2 font-bold text-sm flex items-center transition-all focus:outline-none";
                btnAktual.className = "border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap pb-4 px-1 border-b-2 font-semibold text-sm flex items-center transition-all focus:outline-none";
                contentAktual.classList.add('hidden');
                contentProyeksi.classList.remove('hidden');
                
                // Trigger calculation to draw the chart after container is visible
                setTimeout(calculateProjections, 100);
            }
        }

        // SIDEBAR TOGGLER
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-yayasan2');
            const openBtn = document.getElementById('open-sidebar-yayasan2');
            const closeBtn = document.getElementById('close-sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');

            function toggleSidebar() {
                if(sidebar && overlay) { 
                    sidebar.classList.toggle('hidden'); 
                    overlay.classList.toggle('hidden'); 
                }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);

            // Pre-calculate projection data
            calculateProjections();
        });

        // DEFINISI HARI DAN PARAMETER BULANAN 2026/2027
        const monthDefinitions = [
            { label: "Juli 2026", days: 31, isExam: false, isThr: false, isRent: true, upRatio: 0.60 },
            { label: "Agustus 2026", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.40 },
            { label: "September 2026", days: 30, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Oktober 2026", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "November 2026", days: 30, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Desember 2026", days: 31, isExam: true, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Januari 2027", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Februari 2027", days: 28, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Maret 2027", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "April 2027", days: 30, isExam: false, isThr: true, isRent: false, upRatio: 0.00 },
            { label: "Mei 2027", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Juni 2027", days: 30, isExam: true, isThr: false, isRent: false, upRatio: 0.00 }
        ];

        let projectionChart = null;

        function formatRupiah(val) {
            return "Rp " + Math.round(val).toLocaleString('id-ID');
        }

        function calculateProjections() {
            // Read input values
            const initCash = parseFloat(document.getElementById('sim_init_cash').value) || 0;
            
            // 3 Cohort SPP
            const santri_2024 = parseInt(document.getElementById('sim_santri_2024').value) || 0;
            const spp_2024 = parseFloat(document.getElementById('sim_spp_2024').value) || 0;
            const santri_2025 = parseInt(document.getElementById('sim_santri_2025').value) || 0;
            const spp_2025 = parseFloat(document.getElementById('sim_spp_2025').value) || 0;
            const santri_2026 = parseInt(document.getElementById('sim_santri_2026').value) || 0;
            const spp_2026 = parseFloat(document.getElementById('sim_spp_2026').value) || 0;

            // Scholarships
            const beasiswa_count = parseInt(document.getElementById('sim_beasiswa_count').value) || 0;
            const beasiswa_potongan = parseFloat(document.getElementById('sim_beasiswa_potongan').value) || 0;

            const newSantriCount = parseInt(document.getElementById('sim_new_santri').value) || 0;
            const uangPangkal = parseFloat(document.getElementById('sim_uang_pangkal').value) || 0;
            const donasi = parseFloat(document.getElementById('sim_donasi').value) || 0;
            
            const gajiPokok = parseFloat(document.getElementById('sim_gaji_pokok').value) || 0;
            const honorOffline = parseFloat(document.getElementById('sim_honor_offline').value) || 0;
            const costMakanHari = parseFloat(document.getElementById('sim_cost_makan').value) || 0;
            const sewaAsramaRumah = parseFloat(document.getElementById('sim_sewa_asrama').value) || 0;
            const jumlahRumahSewa = parseInt(document.getElementById('sim_jumlah_rumah').value) || 0;
            const rentMonthIndex = parseInt(document.getElementById('sim_rent_month').value); // 0-11
            
            const utilitas = parseFloat(document.getElementById('sim_utilitas').value) || 0;
            const biayaUjian = parseFloat(document.getElementById('sim_biaya_ujian').value) || 0;
            const anggaranThr = parseFloat(document.getElementById('sim_anggaran_thr').value) || 0;

            const total_santri = santri_2024 + santri_2025 + santri_2026;

            let currentCash = initCash;
            let totalRevAccum = 0;
            let totalExpAccum = 0;
            
            const tableBody = document.getElementById('projection-table-body');
            tableBody.innerHTML = '';

            const chartLabels = [];
            const chartData = [];

            monthDefinitions.forEach((month, index) => {
                // Pemasukan (Inflows)
                const spp_gross = (santri_2024 * spp_2024) + (santri_2025 * spp_2025) + (santri_2026 * spp_2026);
                const spp_discount = beasiswa_count * beasiswa_potongan;
                const spp_net = Math.max(0, spp_gross - spp_discount);

                const incUangPangkal = newSantriCount * uangPangkal * month.upRatio;
                const incDonasi = donasi;
                const totalInc = spp_net + incUangPangkal + incDonasi;

                // Pengeluaran (Outflows)
                const expGaji = gajiPokok;
                const expHonor = honorOffline;
                const expMakan = (total_santri + 8) * costMakanHari * month.days; // 8 staff/asatidz mukim
                const expSewa = (index === rentMonthIndex) ? (sewaAsramaRumah * jumlahRumahSewa) : 0;
                const expUtilitas = utilitas;
                const expUjian = month.isExam ? biayaUjian : 0;
                const expThr = month.isThr ? anggaranThr : 0;
                const totalExp = expGaji + expHonor + expMakan + expSewa + expUtilitas + expUjian + expThr;

                const netCash = totalInc - totalExp;
                currentCash += netCash;

                totalRevAccum += totalInc;
                totalExpAccum += totalExp;

                chartLabels.push(month.label);
                chartData.push(currentCash);

                // Append row to projections table
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-150 hover:bg-slate-50 transition-colors text-[10px]';
                row.innerHTML = `
                    <td class="px-3 py-2.5 font-bold text-slate-800 text-left text-xs bg-slate-50">${month.label}</td>
                    <td class="px-2 py-2.5 text-right text-emerald-700 font-medium">${formatRupiah(spp_gross)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-600 font-medium">${spp_discount > 0 ? '-' + formatRupiah(spp_discount) : 'Rp 0'}</td>
                    <td class="px-2 py-2.5 text-right text-emerald-700 font-medium">${formatRupiah(incUangPangkal)}</td>
                    <td class="px-2 py-2.5 text-right text-emerald-700 font-medium">${formatRupiah(incDonasi)}</td>
                    <td class="px-2 py-2.5 text-right font-bold text-emerald-800 bg-emerald-50/20">${formatRupiah(totalInc)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expGaji)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expHonor)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expMakan)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expSewa)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expUtilitas)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expUjian)}</td>
                    <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expThr)}</td>
                    <td class="px-2 py-2.5 text-right font-bold text-rose-800 bg-rose-50/20">${formatRupiah(totalExp)}</td>
                    <td class="px-2 py-2.5 text-right font-bold ${netCash >= 0 ? 'text-teal-600 bg-teal-50/10' : 'text-rose-600 bg-rose-50/10'}">${formatRupiah(netCash)}</td>
                    <td class="px-3 py-2.5 text-right font-black ${currentCash >= 0 ? 'text-slate-900 bg-slate-50' : 'text-rose-900 bg-rose-100'}">${formatRupiah(currentCash)}</td>
                `;
                tableBody.appendChild(row);
            });

            // Update Projections Summary Cards
            document.getElementById('summary_total_rev').innerText = formatRupiah(totalRevAccum);
            document.getElementById('summary_total_exp').innerText = formatRupiah(totalExpAccum);
            const totalSurplus = totalRevAccum - totalExpAccum;
            const surplusEl = document.getElementById('summary_surplus');
            surplusEl.innerText = formatRupiah(totalSurplus);
            if (totalSurplus >= 0) {
                surplusEl.className = 'text-base font-black text-teal-600 block mt-1';
            } else {
                surplusEl.className = 'text-base font-black text-rose-700 block mt-1';
            }
            document.getElementById('summary_ending_cash').innerText = formatRupiah(currentCash);

            const minCash = Math.min(...chartData);
            const maxCash = Math.max(...chartData);
            document.getElementById('summary_lowest_cash').innerText = formatRupiah(minCash);
            document.getElementById('summary_peak_cash').innerText = formatRupiah(maxCash);

            const alertBox = document.getElementById('projection-alert');
            if (minCash < 0) {
                alertBox.className = 'bg-rose-100 text-rose-800 border border-rose-200 p-4 rounded-xl flex items-start text-xs text-left shadow-sm';
                alertBox.innerHTML = `<i class="fas fa-exclamation-triangle text-rose-600 mr-2.5 text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold text-rose-900">Peringatan Defisit Kas!</span> Proyeksi menunjukkan saldo kas kumulatif Anda akan berada di bawah nol (defisit) di beberapa bulan tertentu. Harap kurangi belanja modal, kurangi unit sewa rumah asrama, atau lakukan penagihan SPP yang lebih ketat.
                    </div>`;
            } else {
                alertBox.className = 'bg-teal-100 text-teal-800 border border-teal-200 p-4 rounded-xl flex items-start text-xs text-left shadow-sm';
                alertBox.innerHTML = `<i class="fas fa-check-circle text-teal-600 mr-2.5 text-base mt-0.5"></i>
                    <div>
                        <span class="font-bold text-teal-900">Rencana Kas Aman!</span> Berdasarkan parameter simulasi saat ini, saldo kas kumulatif Yayasan diproyeksikan akan selalu berada dalam kondisi surplus positif sepanjang tahun ajaran 2026/2027.
                    </div>`;
            }

            // Update Chart.js Projections Line Chart
            if (projectionChart) {
                projectionChart.data.labels = chartLabels;
                projectionChart.data.datasets[0].data = chartData;
                projectionChart.update();
            } else {
                const ctx = document.getElementById('chart-projection').getContext('2d');
                projectionChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            label: 'Saldo Kas Kumulatif',
                            data: chartData,
                            borderColor: '#d97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.05)',
                            borderWidth: 3.5,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#b45309',
                            pointRadius: 4.5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                ticks: {
                                    callback: function(value) {
                                        return value >= 0 ? "Rp " + (value / 1000000) + "M" : "-Rp " + (Math.abs(value) / 1000000) + "M";
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
