<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php'; // DB connection

// Allow musyrif, kepala_asrama, kepala_asrama_rijal, super_admin
$user_roles = [];
if (isset($_SESSION['ustadz_role'])) {
    $user_roles = explode(',', $_SESSION['ustadz_role']);
}
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;

$is_authorized = false;
if ($ustadz_id === 9999) {
    $is_authorized = true;
}
foreach ($user_roles as $role) {
    $norm_role = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
    if (in_array($norm_role, ['super_admin', 'kepala_asrama', 'kepala_asrama_rijal', 'musyrif', 'kepala_mahad'])) {
        $is_authorized = true;
        break;
    }
}
if (!$is_authorized) {
    die('Akses ditolak. Halaman ini khusus untuk Kepala Asrama Rijal dan Pengurus Ma\'had.');
}

// Fetch Rijal Setoran Records (Laki-laki)
$query = "
    SELECT l.*, s.jenis_kelamin, s.kelas_sekarang 
    FROM laporan_setoran_hafalan l 
    LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) 
    WHERE (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL) 
    ORDER BY l.created_at DESC, l.id DESC
";
$res_data = $conn->query($query);

// Stats Rijal
$res_stat1 = $conn->query("
    SELECT COUNT(*) as total 
    FROM laporan_setoran_hafalan l 
    LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) 
    WHERE (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL)
");
$total_setoran = ($res_stat1) ? $res_stat1->fetch_assoc()['total'] : 0;

$res_stat2 = $conn->query("
    SELECT COUNT(*) as total 
    FROM laporan_setoran_hafalan l 
    LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) 
    WHERE (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL) AND DATE(l.created_at) = CURDATE()
");
$total_today = ($res_stat2) ? $res_stat2->fetch_assoc()['total'] : 0;

$res_stat3 = $conn->query("
    SELECT COUNT(*) as total 
    FROM laporan_setoran_hafalan l 
    LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) 
    WHERE (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL) AND l.grade LIKE '%Mutqin%'
");
$total_mumtaz = ($res_stat3) ? $res_stat3->fetch_assoc()['total'] : 0;

$active_menu = 'laporan_setoran_rijal';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Setoran Santri Rijal (Putra) | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-mars mr-1"></i>Kepala Asrama Rijal
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-blue-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-blue-200">
                        <i class="fas fa-male text-lg"></i>
                    </div>
                    <span>Tembusan Setoran Hafalan Santri Rijal (Putra)</span>
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-1">Rekapan otomatis laporan hafalan santri putra untuk pemantauan Kepala Asrama Rijal</p>
            </div>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_setoran) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Total Setoran Rijal</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_today) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Setoran Rijal Hari Ini</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_mumtaz) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Capaian Mutqin Rijal</div>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-bold text-slate-800 text-base flex items-center gap-2">
                        <i class="fas fa-list text-blue-600"></i>
                        <span>Daftar Setoran Santri Putra</span>
                    </h2>
                    <input type="text" id="search_input" onkeyup="filterTable()" placeholder="Cari santri Rijal..." 
                        class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-blue-500 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="setoran_table">
                        <thead>
                            <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Santri Rijal</th>
                                <th class="py-3.5 px-6">Surat & Ayat</th>
                                <th class="py-3.5 px-6">Halaman & Juz</th>
                                <th class="py-3.5 px-6">Grade</th>
                                <th class="py-3.5 px-6">Waktu Setor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <?php if ($res_data && $res_data->num_rows > 0): ?>
                                <?php while ($row = $res_data->fetch_assoc()): 
                                    $g = htmlspecialchars($row['grade']);
                                    $badge_class = "bg-slate-100 text-slate-700 border-slate-300";
                                    if (str_contains($g, 'Mutqin')) $badge_class = "bg-emerald-100 text-emerald-800 border-emerald-300 font-bold";
                                    else if (str_contains($g, 'Ziyadah') || str_contains($g, 'Jayid')) $badge_class = "bg-teal-100 text-teal-800 border-teal-300 font-semibold";
                                    else if (str_contains($g, 'Aslaha')) $badge_class = "bg-rose-100 text-rose-800 border-rose-300 font-bold";
                                ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-user-circle text-blue-600"></i>
                                                <span><?= htmlspecialchars($row['nama_santri']) ?></span>
                                            </div>
                                            <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-semibold mt-1 inline-block">Santri Rijal</span>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-semibold text-blue-700 text-sm">
                                                Surat <?= htmlspecialchars($row['nama_surat']) ?>
                                            </div>
                                            <div class="text-xs text-slate-600 mt-1">
                                                Ayat <b><?= (int)$row['ayat_mulai'] ?></b> s/d <b><?= (int)$row['ayat_sampai'] ?></b>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="flex items-center gap-1">
                                                <?php if (!empty($row['juz'])): ?><span class="bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded text-[11px]">Juz <?= (int)$row['juz'] ?></span><?php endif; ?>
                                                <?php if (!empty($row['halaman'])): ?><span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded text-[11px]">Hlm <?= htmlspecialchars($row['halaman']) ?></span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <span class="px-2.5 py-1 rounded-lg border text-xs <?= $badge_class ?>"><?= $g ?: '-' ?></span>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-xs text-slate-500">
                                            <i class="far fa-clock mr-1"></i><?= date('d M Y - H:i', strtotime($row['created_at'])) ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada rekapan setoran hafalan santri Rijal.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }
        function filterTable() {
            const filter = document.getElementById("search_input").value.toLowerCase();
            const trs = document.getElementById("setoran_table").getElementsByTagName("tr");
            for (let i = 1; i < trs.length; i++) {
                const text = trs[i].textContent || trs[i].innerText;
                trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    </script>
</body>
</html>
