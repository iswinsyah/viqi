<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../koneksi.php';

// Auth check for yayasan
if (!isset($_SESSION['yayasan_logged_in']) || $_SESSION['yayasan_logged_in'] !== true) {
    if (!isset($_SESSION['ustadz_id'])) {
        header("Location: ../login-yayasan.php");
        exit;
    }
}

// Fetch stats
$res_stat1 = $conn->query("SELECT COUNT(*) as total FROM ibadah_harian_santri WHERE DATE(created_at) = CURDATE()");
$total_today = ($res_stat1) ? $res_stat1->fetch_assoc()['total'] : 0;

$res_stat2 = $conn->query("
    SELECT COUNT(*) as total 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    WHERE (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL) AND i.status_validasi = 'Disetujui'
");
$total_rijal_valid = ($res_stat2) ? $res_stat2->fetch_assoc()['total'] : 0;

$res_stat3 = $conn->query("
    SELECT COUNT(*) as total 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    WHERE s.jenis_kelamin = 'Perempuan' AND i.status_validasi = 'Disetujui'
");
$total_nisa_valid = ($res_stat3) ? $res_stat3->fetch_assoc()['total'] : 0;

$active_menu = 'ibadah_harian_yayasan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Ibadah Harian Santri | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-amber-50/30 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-amber-200/80 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-amber-700 hover:text-amber-900 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-amber-950 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-amber-100 text-amber-900 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-building mr-1"></i>Ruang Yayasan
                    </span>
                    <span>Monitoring Terpusat Ibadah Harian</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-amber-800 bg-amber-100/60 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-amber-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-amber-50/30 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-950 tracking-tight flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-600 to-amber-800 flex items-center justify-center text-white shadow-md shadow-amber-200">
                        <i class="fas fa-mosque text-lg"></i>
                    </div>
                    <span>Tembusan Rekap Ibadah Harian Santri (Ruang Yayasan)</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Laporan eksekutif pemantauan disiplin ibadah harian santri yang telah divalidasi oleh Musyrif</p>
            </div>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-amber-950"><?= number_format($total_today) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Laporan Masuk Hari Ini</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-mars"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_rijal_valid) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Validated Rijal (Putra)</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-700 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-venus"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_nisa_valid) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Validated Nisa (Putri)</div>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl border border-amber-200/60 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-200/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-bold text-amber-950 text-base flex items-center gap-2">
                        <i class="fas fa-list text-amber-700"></i>
                        <span>Daftar Ibadah Seluruh Santri</span>
                    </h2>
                    <input type="text" id="search_input" onkeyup="filterTable()" placeholder="Cari santri/kategori..." 
                        class="px-3.5 py-1.5 border border-amber-300 rounded-lg text-xs focus:ring-2 focus:ring-amber-500 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="ibadah_table">
                        <thead>
                            <tr class="bg-amber-100/50 border-b border-amber-200 text-amber-900 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Santri & Kategori</th>
                                <th class="py-3.5 px-6">Sholat Wajib 5 Waktu</th>
                                <th class="py-3.5 px-6">Sunnah & Puasa</th>
                                <th class="py-3.5 px-6">Status Validasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100/60 text-xs sm:text-sm">
                            <?php
                            $query = "
                                SELECT i.*, s.nama_lengkap, s.jenis_kelamin 
                                FROM ibadah_harian_santri i 
                                JOIN buku_induk_santri s ON i.santri_id = s.id 
                                ORDER BY i.tanggal DESC, i.created_at DESC
                            ";
                            $res_data = $conn->query($query);
                            if ($res_data && $res_data->num_rows > 0):
                                while ($row = $res_data->fetch_assoc()):
                                    $jk = $row['jenis_kelamin'] ?? 'Laki-laki';
                                    $is_nisa = ($jk === 'Perempuan');
                            ?>
                                    <tr class="hover:bg-amber-50/40 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-user-circle <?= $is_nisa ? 'text-pink-600' : 'text-blue-600' ?>"></i>
                                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                            </div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-[10px] <?= $is_nisa ? 'bg-pink-50 text-pink-700' : 'bg-blue-50 text-blue-700' ?> px-2 py-0.5 rounded font-semibold">
                                                    <?= $is_nisa ? 'Nisa (Putri)' : 'Rijal (Putra)' ?>
                                                </span>
                                                <span class="text-[11px] text-slate-400">
                                                    <i class="far fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($row['tanggal'])) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-xs space-y-1">
                                            <div><b>Subuh:</b> <?= htmlspecialchars($row['sholat_subuh']) ?></div>
                                            <div><b>Dhuhur:</b> <?= htmlspecialchars($row['sholat_dhuhur']) ?></div>
                                            <div><b>Ashar:</b> <?= htmlspecialchars($row['sholat_ashar']) ?></div>
                                            <div><b>Maghrib:</b> <?= htmlspecialchars($row['sholat_maghrib']) ?></div>
                                            <div><b>Isya:</b> <?= htmlspecialchars($row['sholat_isya']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-xs">
                                            <div class="flex flex-wrap gap-1">
                                                <?php if($row['sholat_tahajud']): ?><span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-semibold text-[10px]">Tahajud</span><?php endif; ?>
                                                <?php if($row['sholat_dhuha']): ?><span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-semibold text-[10px]">Dhuha</span><?php endif; ?>
                                                <?php if($row['puasa_senin'] || $row['puasa_kamis']): ?><span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-semibold text-[10px]">Puasa</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <?php
                                            $st = $row['status_validasi'];
                                            if ($st === 'Disetujui') echo '<span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-check-circle mr-1"></i>Valid (Disetujui)</span>';
                                            elseif ($st === 'Ditolak') echo '<span class="bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-times-circle mr-1"></i>Ditolak</span>';
                                            else echo '<span class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-clock mr-1"></i>Pending</span>';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada data ibadah harian santri.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-yayasan2');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-yayasan2').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden');
            });
        }
        function filterTable() {
            const filter = document.getElementById("search_input").value.toLowerCase();
            const trs = document.getElementById("ibadah_table").getElementsByTagName("tr");
            for (let i = 1; i < trs.length; i++) {
                const text = trs[i].textContent || trs[i].innerText;
                trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    </script>
</body>
</html>
