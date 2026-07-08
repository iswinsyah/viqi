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

// 4. Hitung Gaji Ustadz Offline (Rumus: Jumlah Mapel Offline * Tarif Grade A)
$res_mapel_offline = $conn->query("SELECT id, nama_mapel, kategori_mapel FROM master_mapel WHERE metode_belajar = 'offline'");
$mapel_offline_count = $res_mapel_offline ? $res_mapel_offline->num_rows : 0;
$mapel_offline_list = [];
if ($res_mapel_offline) {
    while ($row = $res_mapel_offline->fetch_assoc()) {
        $mapel_offline_list[] = $row;
    }
}

$res_rate = $conn->query("SELECT gaji_grade_a FROM pengaturan_gaji WHERE id = 1");
$rate_grade_a = 25000;
if ($res_rate && $res_rate->num_rows > 0) {
    $rate_grade_a = (double)$res_rate->fetch_assoc()['gaji_grade_a'];
}

$cost_gaji_offline = $mapel_offline_count * $rate_grade_a;

// 5. Total Akumulasi
$total_pemasukan = $pemasukan_spp + $pemasukan_jurnal;
$total_pengeluaran = $pengeluaran_jurnal + $cost_gaji_offline;
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// Persentase Pengeluaran terhadap Pemasukan
$persen_pengeluaran = $total_pemasukan > 0 ? round(($total_pengeluaran / $total_pemasukan) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashflow Terpadu | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-funnel-dollar text-amber-600 mr-2"></i>Cashflow Terpadu</h1>
                    <p class="text-xs text-gray-500 mt-1">Perhitungan total operasional sekolah dan asrama beserta pemasukan SPP secara langsung.</p>
                </div>
            </div>

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
                        <h2 class="font-bold text-rose-800 text-sm"><i class="fas fa-arrow-circle-up mr-1.5 text-rose-600"></i>Rincian Biaya Operasional (Beban)</h2>
                        <span class="text-[10px] bg-rose-150 text-rose-800 font-bold px-2 py-0.5 rounded">Pengeluaran</span>
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <!-- BOX ESTIMASI GAJI USTADZ OFFLINE (FORMULA DARI USER) -->
                        <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/80 rounded-xl p-5 relative overflow-hidden">
                            <div class="absolute right-0 top-0 opacity-10 text-amber-500 text-7xl translate-x-2 -translate-y-2"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h3 class="font-bold text-amber-900 text-sm mb-1.5 flex items-center">
                                <i class="fas fa-calculator mr-1.5 text-amber-600"></i>Cost Gaji Ustadz Offline (Estimasi)
                            </h3>
                            <p class="text-[11px] text-amber-800 leading-relaxed mb-4">
                                Dihitung dengan rumus: <strong>Jumlah mata pelajaran Offline</strong> dikalikan dengan <strong>Tarif Gaji Asatidz per pertemuan Grade A</strong>.
                            </p>

                            <!-- RINCIAN NILAI FORMULA -->
                            <div class="grid grid-cols-2 gap-4 bg-white/70 backdrop-blur-sm rounded-lg p-3.5 border border-amber-150 mb-3">
                                <div>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase block">Jumlah Mapel Offline</span>
                                    <span class="text-lg font-black text-amber-800 block"><?= $mapel_offline_count ?> Mapel</span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase block">Tarif Grade A (Sesi)</span>
                                    <span class="text-lg font-black text-amber-800 block">Rp <?= number_format($rate_grade_a, 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <!-- HASIL PERKALIAN -->
                            <div class="flex justify-between items-center border-t border-amber-200/60 pt-3">
                                <span class="text-xs font-bold text-amber-900">Total Biaya Estimasi:</span>
                                <span class="text-xl font-black text-amber-600">Rp <?= number_format($cost_gaji_offline, 0, ',', '.') ?></span>
                            </div>

                            <!-- DAFTAR MAPEL OFFLINE DETAIL -->
                            <?php if ($mapel_offline_count > 0): ?>
                                <div class="mt-4 pt-3 border-t border-amber-200/40">
                                    <button onclick="toggleOfflineList()" class="text-[10px] font-bold text-amber-700 hover:text-amber-900 flex items-center">
                                        <i class="fas fa-list mr-1"></i> Tampilkan Daftar Mapel Offline (<?= $mapel_offline_count ?>) <i class="fas fa-chevron-down ml-1 text-[8px]" id="arrow-list"></i>
                                    </button>
                                    <div id="offlineMapelList" class="hidden mt-2 space-y-1 max-h-40 overflow-y-auto pl-2">
                                        <?php foreach ($mapel_offline_list as $mo): ?>
                                            <div class="text-[11px] text-gray-600 flex justify-between items-center border-b border-gray-100 py-1">
                                                <span><i class="fas fa-check text-[8px] text-amber-600 mr-1.5"></i><?= htmlspecialchars($mo['nama_mapel']) ?></span>
                                                <span class="text-[9px] font-semibold bg-gray-100 px-1 py-0.5 rounded text-gray-500"><?= $mo['kategori_mapel'] ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- RINCIAN DARI PEMBUKUAN BEBAN JURNAL -->
                        <div>
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2.5"><i class="fas fa-book-bookmark mr-1 text-rose-500"></i>Rincian Jurnal Beban Operasional</h3>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-150 text-xs">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 font-bold text-gray-500">Akun Beban</th>
                                            <th class="px-4 py-2 text-right font-bold text-gray-500">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <!-- Estimasi Gaji Offline Row -->
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-gray-800">Beban Gaji Ustadz Offline (Estimasi Formula)</td>
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
                                                    <td class="px-4 py-2.5 text-gray-600"><?= htmlspecialchars($row['nama_akun']) ?> (<?= $row['kode_akun'] ?>)</td>
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
                                            <td class="px-4 py-3 text-rose-900">Total Pengeluaran Operasional</td>
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
                        <h2 class="font-bold text-emerald-800 text-sm"><i class="fas fa-arrow-circle-down mr-1.5 text-emerald-600"></i>Rincian Pemasukan (Pendapatan)</h2>
                        <span class="text-[10px] bg-emerald-150 text-emerald-800 font-bold px-2 py-0.5 rounded">Pendapatan</span>
                    </div>

                    <div class="p-6 space-y-6 flex-1">
                        <!-- RINGKASAN INCOME TABLE -->
                        <div>
                            <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-2.5"><i class="fas fa-receipt mr-1 text-emerald-500"></i>Rincian Saldo Pemasukan Terdaftar</h3>
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-150 text-xs">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 font-bold text-gray-500">Kategori Akun Pendapatan</th>
                                            <th class="px-4 py-2 text-right font-bold text-gray-500">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <!-- Row Pemasukan SPP -->
                                        <tr>
                                            <td class="px-4 py-2.5 font-semibold text-gray-800">
                                                Pendapatan SPP Santri (Tabel Pembayaran)
                                            </td>
                                            <td class="px-4 py-2.5 text-right font-bold text-slate-900">
                                                Rp <?= number_format($pemasukan_spp, 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Pendapatan Jurnal List -->
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
                                                    <td class="px-4 py-2.5 text-gray-600"><?= htmlspecialchars($row['nama_akun']) ?> (<?= $row['kode_akun'] ?>)</td>
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
                                            <td class="px-4 py-3 text-emerald-900">Total Pemasukan Operasional</td>
                                            <td class="px-4 py-3 text-right text-emerald-700">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- KARTU PREDIKSI CASHFLOW -->
                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4.5">
                            <h4 class="font-bold text-slate-700 text-xs mb-2 flex items-center"><i class="fas fa-info-circle mr-1 text-slate-500"></i> Catatan Evaluasi Finansial</h4>
                            <p class="text-[11px] text-gray-500 leading-relaxed">
                                Laporan cashflow di atas menggabungkan transaksi real-time dari pembukuan AI serta modul pembayaran SPP santri. Estimasi biaya gaji ustadz offline dihitung secara dinamis berdasarkan data mata pelajaran online yang terdaftar saat ini. Hal ini mempermudah Yayasan dalam melakukan simulasi alokasi dana dan peramalan biaya operasional.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- JS COLLAPSE -->
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

        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar trigger
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
        });
    </script>
</body>
</html>
