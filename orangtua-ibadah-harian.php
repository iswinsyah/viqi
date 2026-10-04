<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$orangtua_nama = $_SESSION['orangtua_nama'];
$is_super_admin = ($orangtua_id == 9999);

// Ambil daftar ananda yang sah & tentukan Ananda Aktif (Persisten Session + Cookie 30 Hari)
$santri_anak = getOrangtuaSantriList($conn, $orangtua_id);
$selected_child = getOrangtuaActiveSantri($conn, $orangtua_id, $santri_anak);
$selected_santri_id = $selected_child ? (int)$selected_child['id'] : 0;

// Query Ibadah Harian Records for selected child
if ($selected_santri_id > 0) {
    $query = "SELECT i.*, s.nama_lengkap 
              FROM ibadah_harian_santri i 
              JOIN buku_induk_santri s ON i.santri_id = s.id 
              WHERE i.santri_id = $selected_santri_id 
              ORDER BY i.tanggal DESC, i.created_at DESC";
} elseif (!empty($santri_anak)) {
    $ids = array_map('intval', array_column($santri_anak, 'id'));
    $id_list_str = implode(',', $ids);
    $query = "SELECT i.*, s.nama_lengkap 
              FROM ibadah_harian_santri i 
              JOIN buku_induk_santri s ON i.santri_id = s.id 
              WHERE i.santri_id IN ($id_list_str) 
              ORDER BY i.tanggal DESC, i.created_at DESC";
} else {
    $query = "SELECT * FROM ibadah_harian_santri WHERE 1=0";
}

$res_data = $conn->query($query);

// Hitung Statistik Ibadah
$total_ibadah = 0;
$total_valid = 0;
$last_ibadah_info = null;
$ibadah_list = [];

if ($res_data && $res_data->num_rows > 0) {
    while ($row = $res_data->fetch_assoc()) {
        $ibadah_list[] = $row;
        $total_ibadah++;
        if (($row['status_validasi'] ?? '') === 'Disetujui') {
            $total_valid++;
        }
    }
    $last_ibadah_info = $ibadah_list[0] ?? null;
}

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR ORANG TUA (SERAGAM DENGAN DASHBOARD.PHP) -->
    <?php include 'sidebar-orangtua.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP -->
        <header class="h-16 bg-white border-b border-teal-100/80 shadow-xs flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-orangtua" class="text-slate-600 hover:text-teal-700 md:hidden mr-3 p-1.5 rounded-lg focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                    <span class="bg-teal-50 text-[#0b8478] border border-teal-200 px-2.5 py-0.5 rounded-lg text-[11px] font-extrabold uppercase tracking-wide">
                        <i class="fas fa-users mr-1"></i>Ruang Orang Tua
                    </span>
                    <span class="hidden sm:inline text-slate-400">|</span>
                    <span class="hidden sm:inline font-bold text-slate-700">Monitoring Ibadah Harian</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs text-teal-800 bg-teal-50/80 px-3 py-1 rounded-full font-semibold border border-teal-100 flex items-center gap-1.5 shadow-xs">
                    <i class="fas fa-heart text-[#0b8478]"></i>
                    <span class="truncate max-w-[140px] sm:max-w-[200px]"><?= htmlspecialchars($orangtua_nama) ?></span>
                </span>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            
            <!-- PAGE TITLE & ANANDA SELECTOR -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-[#0b8478] to-[#065e55] flex items-center justify-center text-white shadow-md shadow-teal-900/20">
                            <i class="fas fa-mosque text-lg"></i>
                        </div>
                        <span>Monitoring Ibadah Harian Ananda</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">
                        Pantau kedisiplinan ibadah shalat wajib, tahajjud, dhuha, dan shaum putra-putri Anda yang divalidasi Musyrif
                    </p>
                </div>

                <!-- SELECTOR ANANDA (MENDUKUNG 1 ATAU BANYAK ANAK BERSAUDARA) -->
                <?php 
                $is_locked = !empty(getLockedSantriIds($conn, $orangtua_id));
                ?>
                <?php if ($is_locked && count($santri_anak) > 1): ?>
                    <form method="GET" class="flex items-center gap-2 bg-white px-3 py-2 rounded-2xl border border-teal-100 shadow-xs flex-wrap">
                        <label class="text-xs font-bold text-[#0b8478] whitespace-nowrap flex items-center gap-1.5">
                            <i class="fas fa-child"></i> Pilih Ananda:
                        </label>
                        <select name="santri_id" onchange="this.form.submit()" class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs bg-slate-50/50 focus:ring-2 focus:ring-[#0b8478] focus:border-[#0b8478] font-bold text-slate-800 transition">
                            <?php foreach ($santri_anak as $sa): ?>
                                <option value="<?= $sa['id'] ?>" <?= ($sa['id'] == $selected_santri_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sa['nama_lengkap']) ?> (<?= htmlspecialchars($sa['kelas_sekarang'] ?? 'Santri') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="openPilihAnandaModal()" class="text-[10px] text-teal-800 hover:text-teal-950 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2.5 py-1 rounded-xl font-bold ml-1 flex items-center gap-1 transition" title="Kelola Ananda Bersaudara">
                            <i class="fas fa-users text-[10px]"></i> Kelola Saudara
                        </button>
                        <?php if ($is_super_admin): ?>
                            <a href="?action=unlock_ananda" onclick="return confirm('Reset kunci ananda? Anda akan bisa memilih ananda kembali (Khusus Super Admin).')" class="text-[10px] text-rose-600 hover:text-rose-800 underline font-semibold ml-1" title="Reset Kunci (Khusus Super Admin)">
                                <i class="fas fa-key text-[9px]"></i> Reset
                            </a>
                        <?php endif; ?>
                    </form>
                <?php elseif ($is_locked && !empty($selected_child)): ?>
                    <div class="flex items-center gap-2 bg-teal-50/80 px-4 py-2 rounded-2xl border border-teal-200/80 text-xs font-bold text-teal-950 shadow-xs flex-wrap">
                        <i class="fas fa-lock text-[#0b8478]"></i>
                        <span>Ananda: <strong class="text-[#0b8478]"><?= htmlspecialchars($selected_child['nama_lengkap']) ?></strong></span>
                        <span class="text-[10px] bg-teal-200/80 text-teal-900 px-2 py-0.5 rounded-full font-bold">Terkunci</span>
                        <button type="button" onclick="openPilihAnandaModal()" class="text-[10px] text-teal-700 hover:text-teal-900 underline font-semibold ml-1.5 flex items-center gap-1" title="Tambah Saudara Kandung">
                            <i class="fas fa-user-plus text-[9px]"></i> Tambah Saudara
                        </button>
                        <?php if ($is_super_admin): ?>
                            <a href="?action=unlock_ananda" onclick="return confirm('Reset kunci ananda? Anda akan bisa memilih ananda kembali (Khusus Super Admin).')" class="text-[10px] text-rose-600 hover:text-rose-800 underline font-semibold ml-1.5" title="Reset Kunci (Khusus Super Admin)">
                                <i class="fas fa-key text-[9px]"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <!-- BELUM TERKUNCI: 1 KOLOM / TOMBOL PILIH ANANDA (BUKA MODAL PILIH 1 ATAU LEBIH) -->
                    <button type="button" onclick="openPilihAnandaModal()" class="px-4 py-2 bg-[#0b8478] hover:bg-teal-700 text-white rounded-2xl text-xs font-bold shadow-xs flex items-center gap-2 transition cursor-pointer">
                        <i class="fas fa-child"></i>
                        <span>Pilih Ananda</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- STATISTIC SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <!-- TOTAL CHECKLIST -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($total_ibadah) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Hari Tercatat di Asrama</div>
                    </div>
                </div>

                <!-- VALIDASI MUSYRIF -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($total_valid) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Telah Divalidasi Musyrif</div>
                    </div>
                </div>

                <!-- TANGGAL TERAKHIR -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="overflow-hidden">
                        <?php if ($last_ibadah_info): ?>
                            <div class="text-sm font-black text-slate-900 truncate">
                                <?= date('d M Y', strtotime($last_ibadah_info['tanggal'])) ?>
                            </div>
                            <div class="text-xs font-semibold text-slate-500 truncate">
                                Status: <b><?= htmlspecialchars($last_ibadah_info['status_validasi'] ?? 'Diproses') ?></b>
                            </div>
                        <?php else: ?>
                            <div class="text-sm font-bold text-slate-400 italic">Belum ada data</div>
                            <div class="text-xs font-semibold text-slate-400">Catatan Terakhir</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TABLE RIWAYAT IBADAH -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-800 text-base flex items-center gap-2">
                            <i class="fas fa-history text-[#0b8478]"></i>
                            <span>Riwayat Mutaba'ah Ibadah</span>
                        </h2>
                        <?php if ($selected_child): ?>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Menampilkan mutaba'ah ananda: <strong class="text-teal-900"><?= htmlspecialchars($selected_child['nama_lengkap']) ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="text-xs text-slate-400 font-medium">
                        Total: <b><?= count($ibadah_list) ?></b> hari
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Tanggal & Santri</th>
                                <th class="py-3.5 px-6">Sholat Wajib 5 Waktu</th>
                                <th class="py-3.5 px-6">Sunnah & Puasa</th>
                                <th class="py-3.5 px-6">Validasi Musyrif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <?php if (!empty($ibadah_list)): ?>
                                <?php foreach ($ibadah_list as $row): ?>
                                    <tr class="hover:bg-teal-50/20 transition">
                                        <!-- KOLOM TANGGAL & SANTRI -->
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-child text-[#0b8478]"></i>
                                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
                                                <i class="far fa-calendar-alt text-teal-600"></i>
                                                <span><b><?= date('d M Y', strtotime($row['tanggal'])) ?></b></span>
                                            </div>
                                        </td>

                                        <!-- KOLOM 5 WAKTU -->
                                        <td class="py-3.5 px-6 align-top text-xs space-y-1">
                                            <div class="flex items-center gap-2"><span class="text-slate-400 w-14 font-medium">Subuh:</span> <span class="text-slate-800 font-bold"><?= htmlspecialchars($row['sholat_subuh'] ?? '-') ?></span></div>
                                            <div class="flex items-center gap-2"><span class="text-slate-400 w-14 font-medium">Dhuhur:</span> <span class="text-slate-800 font-bold"><?= htmlspecialchars($row['sholat_dhuhur'] ?? '-') ?></span></div>
                                            <div class="flex items-center gap-2"><span class="text-slate-400 w-14 font-medium">Ashar:</span> <span class="text-slate-800 font-bold"><?= htmlspecialchars($row['sholat_ashar'] ?? '-') ?></span></div>
                                            <div class="flex items-center gap-2"><span class="text-slate-400 w-14 font-medium">Maghrib:</span> <span class="text-slate-800 font-bold"><?= htmlspecialchars($row['sholat_maghrib'] ?? '-') ?></span></div>
                                            <div class="flex items-center gap-2"><span class="text-slate-400 w-14 font-medium">Isya:</span> <span class="text-slate-800 font-bold"><?= htmlspecialchars($row['sholat_isya'] ?? '-') ?></span></div>
                                        </td>

                                        <!-- KOLOM SUNNAH -->
                                        <td class="py-3.5 px-6 align-top text-xs">
                                            <div class="flex flex-wrap gap-1">
                                                <?php if(!empty($row['sholat_tahajud'])): ?><span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded font-semibold text-[10px]">Tahajud</span><?php endif; ?>
                                                <?php if(!empty($row['sholat_witir'])): ?><span class="bg-teal-50 text-teal-700 border border-teal-200 px-2 py-0.5 rounded font-semibold text-[10px]">Witir</span><?php endif; ?>
                                                <?php if(!empty($row['sholat_dhuha'])): ?><span class="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded font-semibold text-[10px]">Dhuha</span><?php endif; ?>
                                                <?php if(!empty($row['puasa_senin']) || !empty($row['puasa_kamis'])): ?><span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 rounded font-semibold text-[10px]">Puasa Sunnah</span><?php endif; ?>
                                                <?php if(empty($row['sholat_tahajud']) && empty($row['sholat_witir']) && empty($row['sholat_dhuha']) && empty($row['puasa_senin']) && empty($row['puasa_kamis'])): ?>
                                                    <span class="text-slate-400 text-xs italic">-</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- KOLOM VALIDASI -->
                                        <td class="py-3.5 px-6 align-top">
                                            <?php
                                            $st = $row['status_validasi'] ?? '';
                                            if ($st === 'Disetujui') {
                                                echo '<span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1"><i class="fas fa-check-circle"></i>Telah Divalidasi Musyrif</span>';
                                            } elseif ($st === 'Ditolak') {
                                                echo '<span class="bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1"><i class="fas fa-exclamation-circle"></i>Perlu Diperbaiki</span>';
                                            } else {
                                                echo '<span class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-lg text-xs font-bold inline-flex items-center gap-1"><i class="fas fa-clock"></i>Proses Validasi Musyrif</span>';
                                            }
                                            ?>
                                            <?php if(!empty($row['catatan_musyrif'])): ?>
                                                <div class="text-[11px] text-teal-800 mt-1 italic">Catatan: "<?= htmlspecialchars($row['catatan_musyrif']) ?>"</div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center text-slate-400">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                                            <i class="fas fa-mosque"></i>
                                        </div>
                                        <p class="font-semibold text-slate-600 text-sm">Belum ada data laporan ibadah harian yang dicatat untuk Ananda.</p>
                                        <p class="text-xs text-slate-400 mt-1">Data ibadah harian santri dicatat dan divalidasi berkala oleh Musyrif Pembina Asrama.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- SCRIPT DRAWER TOGGLE SIDEBAR MOBILE -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const openBtn = document.getElementById('open-sidebar-orangtua');
        const closeBtn = document.getElementById('close-sidebar-orangtua');
        const sidebar = document.getElementById('sidebar-orangtua');
        const overlay = document.getElementById('sidebar-overlay-orangtua');

        function toggleSidebar() {
            if (sidebar && overlay) {
                sidebar.classList.toggle('hidden');
                overlay.classList.toggle('hidden');
            }
        }

        if (openBtn) openBtn.addEventListener('click', toggleSidebar);
        if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
        if (overlay) overlay.addEventListener('click', toggleSidebar);
    });
    </script>
</body>
</html>
