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

// Query Ibadah Harian Records for selected child
if ($selected_santri_id > 0) {
    $query = "SELECT i.*, s.nama_lengkap FROM ibadah_harian_santri i JOIN buku_induk_santri s ON i.santri_id = s.id WHERE i.santri_id = $selected_santri_id ORDER BY i.tanggal DESC, i.created_at DESC";
} else {
    if (!empty($santri_anak)) {
        $ids = array_column($santri_anak, 'id');
        $id_list_str = implode(',', array_map('intval', $ids));
        $query = "SELECT i.*, s.nama_lengkap FROM ibadah_harian_santri i JOIN buku_induk_santri s ON i.santri_id = s.id WHERE i.santri_id IN ($id_list_str) ORDER BY i.tanggal DESC, i.created_at DESC";
    } else {
        $query = "SELECT * FROM ibadah_harian_santri WHERE 1=0";
    }
}

$res_data = $conn->query($query);

$active_menu = 'orangtua_ibadah_harian';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ibadah Harian Ananda | Ruang Orang Tua</title>
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
                            <i class="fas fa-mosque text-lg"></i>
                        </div>
                        <span>Monitoring Ibadah Harian Ananda</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Pantau disiplin ibadah harian putra-putri Anda yang telah divalidasi oleh Musyrif Pembina</p>
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
                        <span>Riwayat Ibadah Harian</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-purple-100/50 border-b border-purple-200 text-purple-900 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Tanggal & Santri</th>
                                <th class="py-3.5 px-6">Sholat Wajib 5 Waktu</th>
                                <th class="py-3.5 px-6">Sunnah & Puasa</th>
                                <th class="py-3.5 px-6">Validasi Musyrif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-purple-50 text-xs sm:text-sm">
                            <?php if ($res_data && $res_data->num_rows > 0): ?>
                                <?php while ($row = $res_data->fetch_assoc()): ?>
                                    <tr class="hover:bg-purple-50/40 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-child text-purple-600"></i>
                                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-1">
                                                <i class="far fa-calendar-alt text-purple-600 mr-1"></i><b><?= date('d M Y', strtotime($row['tanggal'])) ?></b>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-xs space-y-1">
                                            <div><b>Subuh:</b> <span class="text-purple-900 font-medium"><?= htmlspecialchars($row['sholat_subuh']) ?></span></div>
                                            <div><b>Dhuhur:</b> <span class="text-purple-900 font-medium"><?= htmlspecialchars($row['sholat_dhuhur']) ?></span></div>
                                            <div><b>Ashar:</b> <span class="text-purple-900 font-medium"><?= htmlspecialchars($row['sholat_ashar']) ?></span></div>
                                            <div><b>Maghrib:</b> <span class="text-purple-900 font-medium"><?= htmlspecialchars($row['sholat_maghrib']) ?></span></div>
                                            <div><b>Isya:</b> <span class="text-purple-900 font-medium"><?= htmlspecialchars($row['sholat_isya']) ?></span></div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-xs">
                                            <div class="flex flex-wrap gap-1">
                                                <?php if($row['sholat_tahajud']): ?><span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-semibold text-[10px]">Tahajud</span><?php endif; ?>
                                                <?php if($row['sholat_witir']): ?><span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded font-semibold text-[10px]">Witir</span><?php endif; ?>
                                                <?php if($row['sholat_dhuha']): ?><span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-semibold text-[10px]">Dhuha</span><?php endif; ?>
                                                <?php if($row['puasa_senin'] || $row['puasa_kamis']): ?><span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-semibold text-[10px]">Puasa Sunnah</span><?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <?php
                                            $st = $row['status_validasi'];
                                            if ($st === 'Disetujui') echo '<span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-check-circle mr-1"></i>Telah Divalidasi Musyrif</span>';
                                            elseif ($st === 'Ditolak') echo '<span class="bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-exclamation-circle mr-1"></i>Perlu Diperbaiki</span>';
                                            else echo '<span class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-clock mr-1"></i>Proses Validasi Musyrif</span>';
                                            ?>
                                            <?php if(!empty($row['catatan_musyrif'])): ?>
                                                <div class="text-[11px] text-purple-700 mt-1 italic">Catatan: "<?= htmlspecialchars($row['catatan_musyrif']) ?>"</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" class="py-10 text-center text-slate-400 italic">Belum ada data laporan ibadah harian yang dicatat untuk Ananda.</td></tr>
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
