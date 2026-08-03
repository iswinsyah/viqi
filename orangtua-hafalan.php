<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$orangtua_nama = $_SESSION['orangtua_nama'];

$is_super_admin = ($orangtua_id == 9999);

// Fetch children of this parent
$santri_anak = [];
if ($is_super_admin) {
    $res_s = $conn->query("SELECT id, nama_lengkap, kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC LIMIT 20");
} else {
    $res_s = $conn->query("
        SELECT s.id, s.nama_lengkap, s.kelas_sekarang 
        FROM buku_induk_santri s 
        LEFT JOIN santri_orangtua_link sol ON s.id = sol.santri_id 
        WHERE sol.orangtua_id = $orangtua_id OR s.id_orangtua = $orangtua_id 
        ORDER BY s.nama_lengkap ASC
    ");
}

if ($res_s) {
    while ($r = $res_s->fetch_assoc()) {
        $santri_anak[] = $r;
    }
}

// Active child filter
$selected_santri_id = isset($_GET['santri_id']) ? (int)$_GET['santri_id'] : ($santri_anak[0]['id'] ?? 0);

// Query Setoran Hafalan for selected child (or all children of this parent if no filter)
if ($selected_santri_id > 0) {
    $query = "SELECT l.*, s.nama_lengkap FROM laporan_setoran_hafalan l LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) WHERE (l.santri_id = $selected_santri_id OR s.id = $selected_santri_id) ORDER BY l.created_at DESC, l.id DESC";
} else {
    if (!empty($santri_anak)) {
        $ids = array_column($santri_anak, 'id');
        $id_list_str = implode(',', array_map('intval', $ids));
        $query = "SELECT l.*, s.nama_lengkap FROM laporan_setoran_hafalan l LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) WHERE (l.santri_id IN ($id_list_str) OR s.id IN ($id_list_str)) ORDER BY l.created_at DESC, l.id DESC";
    } else {
        $query = "SELECT * FROM laporan_setoran_hafalan WHERE 1=0";
    }
}

$res_data = $conn->query($query);

// Count Stats
$total_setoran = ($res_data) ? $res_data->num_rows : 0;

$active_menu = 'orangtua_hafalan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setoran Hafalan Ananda | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-purple-50/30 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-orangtua.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-purple-100 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-orangtua" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-purple-100 text-purple-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-users mr-1"></i>Ruang Orang Tua
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs text-slate-500 bg-purple-50 px-3 py-1 rounded-full font-semibold border border-purple-100">
                    <i class="fas fa-heart text-purple-600 mr-1"></i>Walisantri: <?= htmlspecialchars($orangtua_nama) ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-purple-50/20 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-purple-950 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-purple-200">
                            <i class="fas fa-book-quran text-lg"></i>
                        </div>
                        <span>Laporan Setoran Hafalan Ananda</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Laporan rekapan perkembangan hafalan Al-Qur'an putra-putri Anda dari Musyrif</p>
                </div>

                <!-- SANTRI SELECTOR IF MULTIPLE -->
                <?php if (count($santri_anak) > 1): ?>
                    <form method="GET" class="flex items-center gap-2">
                        <label class="text-xs font-bold text-purple-900 whitespace-nowrap">Pilih Ananda:</label>
                        <select name="santri_id" onchange="this.form.submit()" class="px-3 py-2 border border-purple-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-purple-500 font-semibold text-purple-950">
                            <?php foreach ($santri_anak as $sa): ?>
                                <option value="<?= $sa['id'] ?>" <?= ($sa['id'] == $selected_santri_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sa['nama_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                <?php endif; ?>
            </div>

            <!-- TABLE -->
            <div class="bg-white rounded-2xl border border-purple-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-purple-50/60 border-b border-purple-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 class="font-bold text-purple-950 text-base flex items-center gap-2">
                        <i class="fas fa-history text-purple-600"></i>
                        <span>Riwayat Setoran Hafalan</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-purple-100/50 border-b border-purple-200 text-purple-900 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Nama Ananda & Waktu</th>
                                <th class="py-3.5 px-6">Surat & Ayat</th>
                                <th class="py-3.5 px-6">Halaman & Juz</th>
                                <th class="py-3.5 px-6">Grade / Kualitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50 text-xs sm:text-sm">
                            <?php if ($res_data && $res_data->num_rows > 0): ?>
                                <?php while ($row = $res_data->fetch_assoc()): 
                                    $g = htmlspecialchars($row['grade']);
                                    $badge_class = "bg-slate-100 text-slate-700 border-slate-300";
                                    if (str_contains($g, 'Mutqin')) $badge_class = "bg-emerald-100 text-emerald-800 border-emerald-300 font-bold";
                                    else if (str_contains($g, 'Ziyadah') || str_contains($g, 'Jayid')) $badge_class = "bg-teal-100 text-teal-800 border-teal-300 font-semibold";
                                    else if (str_contains($g, 'Aslaha')) $badge_class = "bg-rose-100 text-rose-800 border-rose-300 font-bold";
                                ?>
                                    <tr class="hover:bg-purple-50/40 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-child text-purple-600"></i>
                                                <span><?= htmlspecialchars($row['nama_santri']) ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                                                <i class="far fa-clock"></i>
                                                <span><?= date('d M Y - H:i', strtotime($row['created_at'])) ?> WIB</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-semibold text-purple-900 text-sm">
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
                                <tr><td colspan="4" class="py-10 text-center text-slate-400 italic">Belum ada riwayat setoran hafalan yang tercatat untuk Ananda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-orangtua');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-orangtua').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-orangtua').classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
