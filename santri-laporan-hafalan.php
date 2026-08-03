<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];

$is_super_admin = ($santri_id == 9999);

// Fetch logged in santri info
$santri_info = null;
if (!$is_super_admin) {
    $res_info = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id LIMIT 1");
    if ($res_info && $res_info->num_rows > 0) {
        $santri_info = $res_info->fetch_assoc();
    }
}

// Query Setoran
if ($is_super_admin) {
    $filter_santri_id = isset($_GET['santri_id']) ? (int)$_GET['santri_id'] : 0;
    if ($filter_santri_id > 0) {
        $query = "SELECT * FROM laporan_setoran_hafalan WHERE santri_id = $filter_santri_id ORDER BY created_at DESC, id DESC";
    } else {
        $query = "SELECT * FROM laporan_setoran_hafalan ORDER BY created_at DESC, id DESC LIMIT 50";
    }
} else {
    // Match by santri_id OR nama_santri
    $s_nama_escaped = $conn->real_escape_string($santri_nama);
    $query = "SELECT * FROM laporan_setoran_hafalan WHERE santri_id = $santri_id OR nama_santri = '$s_nama_escaped' ORDER BY created_at DESC, id DESC";
}

$res_data = $conn->query($query);

// Statistics
if ($is_super_admin) {
    $res_stat1 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan");
    $total_setoran = ($res_stat1) ? $res_stat1->fetch_assoc()['total'] : 0;

    $res_stat2 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan WHERE grade = 'Mumtaz'");
    $total_mumtaz = ($res_stat2) ? $res_stat2->fetch_assoc()['total'] : 0;
} else {
    $s_nama_escaped = $conn->real_escape_string($santri_nama);
    $res_stat1 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan WHERE santri_id = $santri_id OR nama_santri = '$s_nama_escaped'");
    $total_setoran = ($res_stat1) ? $res_stat1->fetch_assoc()['total'] : 0;

    $res_stat2 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan WHERE (santri_id = $santri_id OR nama_santri = '$s_nama_escaped') AND grade = 'Mumtaz'");
    $total_mumtaz = ($res_stat2) ? $res_stat2->fetch_assoc()['total'] : 0;
}

$active_menu = 'santri_hafalan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setoran Hafalan Saya | Ruang Santri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-indigo-100 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-santri" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-indigo-100 text-indigo-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-user-graduate mr-1"></i>Ruang Santri
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full font-semibold">
                    <i class="fas fa-user text-indigo-600 mr-1"></i><?= htmlspecialchars($santri_nama) ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-200">
                            <i class="fas fa-quran text-lg"></i>
                        </div>
                        <span>Setoran Hafalan Saya</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Catatan riwayat setoran hafalan Al-Qur'an Anda yang dicatat oleh Musyrif</p>
                </div>
            </div>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-indigo-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_setoran) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Total Setoran Hafalan Anda</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-indigo-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-award"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_mumtaz) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Capaian Grade Mumtaz</div>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl border border-indigo-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-indigo-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-bold text-slate-800 text-base flex items-center gap-2">
                        <i class="fas fa-history text-indigo-600"></i>
                        <span>Riwayat Setoran Hafalan</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-indigo-50/50 border-b border-indigo-100 text-indigo-900 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Tanggal</th>
                                <th class="py-3.5 px-6">Surat & Ayat</th>
                                <th class="py-3.5 px-6">Halaman & Juz</th>
                                <th class="py-3.5 px-6">Grade / Kualitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-indigo-50/60 text-xs sm:text-sm">
                            <?php if ($res_data && $res_data->num_rows > 0): ?>
                                <?php while ($row = $res_data->fetch_assoc()): 
                                    $g = htmlspecialchars($row['grade']);
                                    $badge_class = "bg-slate-100 text-slate-700 border-slate-300";
                                    if (str_contains($g, 'Mumtaz')) $badge_class = "bg-emerald-100 text-emerald-800 border-emerald-300 font-bold";
                                    else if (str_contains($g, 'Jayid')) $badge_class = "bg-teal-100 text-teal-800 border-teal-300 font-semibold";
                                    else if (str_contains($g, 'Aadiy')) $badge_class = "bg-amber-100 text-amber-800 border-amber-300";
                                    else if (str_contains($g, 'Aslaha')) $badge_class = "bg-rose-100 text-rose-800 border-rose-300 font-semibold";
                                ?>
                                    <tr class="hover:bg-indigo-50/30 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm">
                                                <i class="far fa-calendar-alt text-indigo-600 mr-1.5"></i>
                                                <?= date('d M Y', strtotime($row['created_at'])) ?>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-0.5"><?= date('H:i', strtotime($row['created_at'])) ?> WIB</div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-semibold text-indigo-700 text-sm">
                                                Surat <?= htmlspecialchars($row['nama_surat']) ?>
                                            </div>
                                            <div class="text-xs text-slate-600 mt-1">
                                                Ayat <b><?= (int)$row['ayat_mulai'] ?></b> s/d <b><?= (int)$row['ayat_sampai'] ?></b>
                                                <span class="text-slate-400"> (<?= ((int)$row['ayat_sampai'] - (int)$row['ayat_mulai'] + 1) ?> ayat)</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="flex items-center gap-1.5">
                                                <?php if (!empty($row['juz'])): ?><span class="bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded text-[11px]">Juz <?= (int)$row['juz'] ?></span><?php endif; ?>
                                                <?php if (!empty($row['halaman'])): ?><span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded text-[11px]">Hlm <?= htmlspecialchars($row['halaman']) ?></span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <span class="px-2.5 py-1 rounded-lg border text-xs <?= $badge_class ?>"><?= $g ?: '-' ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="py-10 text-center text-slate-400 italic">Belum ada riwayat setoran hafalan yang tercatat untuk Anda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-santri');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-santri').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-santri').classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
