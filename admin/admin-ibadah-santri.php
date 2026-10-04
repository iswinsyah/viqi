<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php';

// Check role: kepala_mahad, super_admin, admin_sekolah, kepala_sekolah, kepala_asrama, kepala_asrama_rijal, kepala_asrama_nisa, musyrif
$user_roles = [];
if (isset($_SESSION['ustadz_role'])) {
    $user_roles = explode(',', $_SESSION['ustadz_role']);
}
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_super_admin = ($ustadz_id === 9999) || in_array('super_admin', $norm_roles);
$is_pimpinan = $is_super_admin || !empty(array_intersect($norm_roles, ['kepala_mahad', 'admin_sekolah', 'kepala_sekolah', 'sekretaris_sekolah']));
$is_ka_rijal = in_array('kepala_asrama_rijal', $norm_roles) || in_array('kepala_asrama', $norm_roles);
$is_ka_nisa = in_array('kepala_asrama_nisa', $norm_roles);
$is_musyrif = in_array('musyrif', $norm_roles);

$is_authorized = $is_pimpinan || $is_ka_rijal || $is_ka_nisa || $is_musyrif;

if (!$is_authorized) {
    die('Akses ditolak. Halaman ini khusus untuk Pengurus Asrama, Musyrif, dan Pimpinan.');
}

// Tentukan filter gender awal berdasarkan role
$default_gender = 'semua';
if ($is_ka_rijal && !$is_pimpinan) {
    $default_gender = 'putra';
} elseif ($is_ka_nisa && !$is_pimpinan) {
    $default_gender = 'putri';
}

$gender_filter = isset($_GET['gender']) ? $_GET['gender'] : $default_gender;

// Batasi akses jika bukan pimpinan
if (!$is_pimpinan) {
    if ($is_ka_rijal && $gender_filter !== 'putra') {
        $gender_filter = 'putra';
    } elseif ($is_ka_nisa && $gender_filter !== 'putri') {
        $gender_filter = 'putri';
    }
}

// Bangun Query filter gender
$where_gender = "";
if ($gender_filter === 'putra') {
    $where_gender = "AND (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL)";
} elseif ($gender_filter === 'putri') {
    $where_gender = "AND s.jenis_kelamin = 'Perempuan'";
}

// Fetch Ibadah Records
$query = "
    SELECT i.*, s.nama_lengkap, s.kelas_sekarang, s.jenis_kelamin 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    WHERE 1=1 $where_gender 
    ORDER BY i.tanggal DESC, i.created_at DESC
";
$res_data = $conn->query($query);

// Stats
$res_stat1 = $conn->query("
    SELECT COUNT(*) as total 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    WHERE DATE(i.created_at) = CURDATE() $where_gender
");
$total_today = ($res_stat1) ? $res_stat1->fetch_assoc()['total'] : 0;

$res_stat2 = $conn->query("
    SELECT COUNT(*) as total 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    WHERE i.status_validasi = 'Disetujui' $where_gender
");
$total_valid = ($res_stat2) ? $res_stat2->fetch_assoc()['total'] : 0;

$res_stat3 = $conn->query("
    SELECT COUNT(*) as total 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    WHERE i.status_validasi = 'Pending' $where_gender
");
$total_pending = ($res_stat3) ? $res_stat3->fetch_assoc()['total'] : 0;

$active_menu = 'rekap_ibadah_santri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Ibadah Harian Santri | Ruang Asatidz</title>
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
                        <i class="fas fa-mosque mr-1"></i>Rekap Ibadah Santri
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
            
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-cyan-200">
                            <i class="fas fa-mosque text-lg"></i>
                        </div>
                        <span>Rekap Ibadah Harian Santri</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Rekapan ibadah harian seluruh santri yang divalidasi oleh Musyrif/Musyrifah.</p>
                </div>
            </div>

            <!-- TAB FILTERS (KHUSUS PIMPINAN) -->
            <?php if ($is_pimpinan): ?>
                <div class="flex border-b border-slate-200 mb-6 gap-2">
                    <a href="admin-ibadah-santri.php?gender=semua" class="py-2.5 px-4 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $gender_filter === 'semua' ? 'border-cyan-600 text-cyan-600 bg-cyan-50/50' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                        <i class="fas fa-users mr-1"></i> Semua Santri
                    </a>
                    <a href="admin-ibadah-santri.php?gender=putra" class="py-2.5 px-4 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $gender_filter === 'putra' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                        <i class="fas fa-mars mr-1"></i> Putra (Rijal)
                    </a>
                    <a href="admin-ibadah-santri.php?gender=putri" class="py-2.5 px-4 text-xs font-bold rounded-t-xl transition-all border-b-2 <?= $gender_filter === 'putri' ? 'border-pink-600 text-pink-600 bg-pink-50/50' : 'border-transparent text-slate-500 hover:text-slate-800' ?>">
                        <i class="fas fa-venus mr-1"></i> Putri (Nisa)
                    </a>
                </div>
            <?php endif; ?>

            <!-- STATS CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_today) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Laporan Masuk Hari Ini</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_valid) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Divalidasi Musyrif</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_pending) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Pending Validasi</div>
                    </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-bold text-slate-800 text-base flex items-center gap-2">
                        <i class="fas fa-list text-cyan-600"></i>
                        <span>Daftar Ibadah Harian Santri</span>
                    </h2>
                    <input type="text" id="search_input" onkeyup="filterTable()" placeholder="Cari nama santri..." 
                        class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-cyan-500 w-full sm:w-64">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="ibadah_table">
                        <thead>
                            <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Santri & Tanggal</th>
                                <th class="py-3.5 px-6">Sholat Wajib 5 Waktu</th>
                                <th class="py-3.5 px-6">Sunnah & Puasa</th>
                                <th class="py-3.5 px-6">Validasi Musyrif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <?php if ($res_data && $res_data->num_rows > 0): ?>
                                <?php while ($row = $res_data->fetch_assoc()): 
                                    $is_putri = ($row['jenis_kelamin'] === 'Perempuan');
                                ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-user-circle <?= $is_putri ? 'text-pink-500' : 'text-blue-500' ?>"></i>
                                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded font-extrabold uppercase <?= $is_putri ? 'bg-pink-100 text-pink-700' : 'bg-blue-100 text-blue-700' ?>">
                                                    <?= $is_putri ? 'Nisa' : 'Rijal' ?>
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-1">
                                                <i class="far fa-calendar-alt text-cyan-600 mr-1"></i><b><?= date('d M Y', strtotime($row['tanggal'])) ?></b>
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
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
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
