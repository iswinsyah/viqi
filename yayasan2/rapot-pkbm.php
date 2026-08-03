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

// Stats
$total_santri_aktif = $conn->query("SELECT COUNT(*) as total FROM buku_induk_santri WHERE status_santri = 'Aktif'")->fetch_assoc()['total'] ?? 0;
$total_paket_b = $conn->query("SELECT COUNT(*) as total FROM buku_induk_santri WHERE status_santri = 'Aktif' AND (kelas_sekarang LIKE '%7%' OR kelas_sekarang LIKE '%8%' OR kelas_sekarang LIKE '%9%' OR kelas_sekarang LIKE '%VII%' OR kelas_sekarang LIKE '%VIII%' OR kelas_sekarang LIKE '%IX%' OR kelas_sekarang LIKE '%Paket B%')")->fetch_assoc()['total'] ?? 0;
$total_paket_c = $conn->query("SELECT COUNT(*) as total FROM buku_induk_santri WHERE status_santri = 'Aktif' AND (kelas_sekarang LIKE '%10%' OR kelas_sekarang LIKE '%11%' OR kelas_sekarang LIKE '%12%' OR kelas_sekarang LIKE '%X%' OR kelas_sekarang LIKE '%XI%' OR kelas_sekarang LIKE '%XII%' OR kelas_sekarang LIKE '%Paket C%')")->fetch_assoc()['total'] ?? 0;

$active_menu = 'rapot_pkbm_yayasan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Raport PKBM (Paket B & C) | Ruang Yayasan</title>
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
                    <span>Monitoring Raport Diknas PKBM</span>
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
                        <i class="fas fa-file-invoice text-lg"></i>
                    </div>
                    <span>Monitoring Raport Diknas PKBM (Paket B & Paket C)</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Laporan eksekutif capaian hasil belajar akademik peserta didik pendidikan kesetaraan Paket B dan Paket C</p>
            </div>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-amber-950"><?= number_format($total_santri_aktif) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Total Santri Aktif PKBM</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-700 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-book"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_paket_b) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Paket B (Setara SMP / 7-9)</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-amber-200/60 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_paket_c) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Paket C (Setara SMA / 10-12)</div>
                    </div>
                </div>
            </div>

            <!-- TABLE DATA SANTRI & AKSES RAPORT -->
            <div class="bg-white rounded-2xl border border-amber-200/60 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-amber-50/50 border-b border-amber-200/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-bold text-amber-950 text-base flex items-center gap-2">
                        <i class="fas fa-list text-amber-700"></i>
                        <span>Daftar Peserta Didik & Cetak Raport PKBM</span>
                    </h2>
                    <input type="text" id="search_input" onkeyup="filterTable()" placeholder="Cari santri/kelas..." 
                        class="px-3.5 py-1.5 border border-amber-300 rounded-lg text-xs focus:ring-2 focus:ring-amber-500 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="santri_table">
                        <thead>
                            <tr class="bg-amber-100/50 border-b border-amber-200 text-amber-900 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Peserta Didik</th>
                                <th class="py-3.5 px-6">Kelas & Program</th>
                                <th class="py-3.5 px-6">Kategori Paket</th>
                                <th class="py-3.5 px-6 text-center">Aksi Raport</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-amber-100/60 text-xs sm:text-sm">
                            <?php
                            $res_data = $conn->query("SELECT * FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
                            if ($res_data && $res_data->num_rows > 0):
                                while ($row = $res_data->fetch_assoc()):
                                    $k = strtolower($row['kelas_sekarang'] ?? '');
                                    $is_c = (str_contains($k, 'paket c') || str_contains($k, 'sma') || preg_match('/\b(10|11|12|x|xi|xii)\b/i', $k));
                                    $tipe = $is_c ? 'Paket C' : 'Paket B';
                            ?>
                                    <tr class="hover:bg-amber-50/40 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-user-circle text-amber-700"></i>
                                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5">NIS: <?= htmlspecialchars($row['nis'] ?? '-') ?></div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top font-semibold">
                                            <?= htmlspecialchars($row['kelas_sekarang'] ?? '-') ?>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold border <?= $is_c ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-blue-50 text-blue-700 border-blue-200' ?>">
                                                <?= $tipe ?> (<?= $is_c ? 'Setara SMA' : 'Setara SMP' ?>)
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-center">
                                            <a href="../admin-rapot-pkbm.php?santri_id=<?= $row['id'] ?>&kelas=<?= urlencode($row['kelas_sekarang']) ?>" 
                                                class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-3 py-1.5 rounded-lg text-xs transition inline-flex items-center gap-1">
                                                <i class="fas fa-print"></i> Lihat & Cetak
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada data santri aktif.</td></tr>
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
            const trs = document.getElementById("santri_table").getElementsByTagName("tr");
            for (let i = 1; i < trs.length; i++) {
                const text = trs[i].textContent || trs[i].innerText;
                trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    </script>
</body>
</html>
