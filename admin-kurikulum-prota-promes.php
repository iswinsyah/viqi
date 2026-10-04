<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
require_once 'pkbm_modul_catalog.php';
require_once 'kurikulum_pekan_efektif.php';

$active_menu = 'prota_promes';
$ustadz_id = (int)($_SESSION['ustadz_id'] ?? 0);
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

$norm_user_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);
$is_super_admin = in_array('super_admin', $norm_user_roles) || in_array('kepala_sekolah', $norm_user_roles) || in_array('admin_sekolah', $norm_user_roles) || $ustadz_id === 9999;

// Analisis Pekan Efektif dari Kalender Akademik
$analisis_pekan = getAnalisisPekanEfektif($conn, 2026);

// Filter Mapel & Semester
$res_mapels = $conn->query("SELECT DISTINCT mapel_nama FROM kurikulum_prota_promes ORDER BY mapel_nama ASC");
$all_mapels = [];
if ($res_mapels) {
    while ($r = $res_mapels->fetch_assoc()) {
        $all_mapels[] = $r['mapel_nama'];
    }
}
$selected_mapel = $_GET['mapel'] ?? ($all_mapels[0] ?? 'Sosiologi');
$selected_mapel_esc = $conn->real_escape_string($selected_mapel);

$selected_sem = (int)($_GET['sem'] ?? 1);

// Query Data Prota & Promes untuk Mapel Terpilih
$res_promes = $conn->query("SELECT * FROM kurikulum_prota_promes WHERE mapel_nama = '$selected_mapel_esc' AND semester = $selected_sem ORDER BY bab_nomor ASC");
$list_promes = $res_promes ? $res_promes->fetch_all(MYSQLI_ASSOC) : [];

// Total Jam Pelajaran
$tot_jp = 0;
foreach ($list_promes as $p) {
    $tot_jp += (int)$p['alokasi_jp'];
}

// Handler Re-Sync / Generate Ulang jika diperlukan
$pesan_sukses = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'resync_prota_promes') {
    $cnt = autoGenerateProtaPromes($conn, '2026/2027');
    $pesan_sukses = "Berhasil menyinkronkan $cnt distribusi materi ke Kalender Akademik & Pekan Efektif!";
    // Refresh query
    $res_promes = $conn->query("SELECT * FROM kurikulum_prota_promes WHERE mapel_nama = '$selected_mapel_esc' AND semester = $selected_sem ORDER BY bab_nomor ASC");
    $list_promes = $res_promes ? $res_promes->fetch_all(MYSQLI_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pekan Efektif, Prota & Promes | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
            .print-full { width: 100% !important; margin: 0 !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <div class="no-print">
        <?php include 'sidebar-hr.php'; ?>
    </div>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative print-full">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-hr" class="text-slate-600 hover:text-indigo-600 md:hidden p-2 rounded-xl focus:outline-none">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-sky-500 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-100">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h1 class="font-extrabold text-sm sm:text-base text-slate-900 leading-tight">
                            Perangkat Kurikulum: Pekan Efektif, Prota & Promes
                        </h1>
                        <p class="text-[10px] text-slate-400">Sinkronisasi Kalender Akademik ➔ Program Tahunan ➔ Program Semester Otomatis</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 border border-slate-300 shadow-2xs">
                    <i class="fas fa-print"></i> <span>Cetak / PDF</span>
                </button>
                <form method="POST" class="inline">
                    <input type="hidden" name="action" value="resync_prota_promes">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3.5 py-2 rounded-xl text-xs transition flex items-center gap-1.5 shadow-sm">
                        <i class="fas fa-sync-alt"></i> <span>Sinkron Ulang AI</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- MAIN SCROLLABLE -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl shadow-sm flex items-center gap-3 no-print">
                    <i class="fas fa-check-circle text-emerald-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_sukses ?></span>
                </div>
                <?php endif; ?>

                <!-- KARTU RINGKASAN PEKAN EFEKTIF (KALENDER AKADEMIK) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    
                    <!-- SEMESTER 1 -->
                    <div class="bg-gradient-to-br from-indigo-50/80 via-white to-sky-50/50 border border-indigo-100 rounded-3xl p-5 shadow-sm">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-indigo-100">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-indigo-600 text-white font-black text-xs flex items-center justify-center">S1</span>
                                <div>
                                    <h3 class="font-extrabold text-sm text-slate-900"><?= $analisis_pekan['semester_ganjil']['nama'] ?></h3>
                                    <p class="text-[10px] text-slate-400"><?= $analisis_pekan['semester_ganjil']['rentang'] ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-black bg-indigo-100 text-indigo-800">
                                <?= $analisis_pekan['semester_ganjil']['pekan_efektif'] ?> Pekan Efektif
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            <?php foreach ($analisis_pekan['semester_ganjil']['bulan'] as $bln => $d): ?>
                            <div class="bg-white p-2.5 rounded-xl border border-slate-100 shadow-2xs">
                                <div class="flex items-center justify-between font-bold text-[11px] text-slate-800">
                                    <span><?= $bln ?></span>
                                    <span class="text-indigo-600 font-extrabold"><?= $d['efektif'] ?> mgg</span>
                                </div>
                                <p class="text-[9px] text-slate-400 mt-0.5 truncate" title="<?= $d['kegiatan'] ?>"><?= $d['kegiatan'] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- SEMESTER 2 -->
                    <div class="bg-gradient-to-br from-emerald-50/80 via-white to-teal-50/50 border border-emerald-100 rounded-3xl p-5 shadow-sm">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-emerald-100">
                            <div class="flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-xl bg-emerald-600 text-white font-black text-xs flex items-center justify-center">S2</span>
                                <div>
                                    <h3 class="font-extrabold text-sm text-slate-900"><?= $analisis_pekan['semester_genap']['nama'] ?></h3>
                                    <p class="text-[10px] text-slate-400"><?= $analisis_pekan['semester_genap']['rentang'] ?></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800">
                                <?= $analisis_pekan['semester_genap']['pekan_efektif'] ?> Pekan Efektif
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 text-xs">
                            <?php foreach ($analisis_pekan['semester_genap']['bulan'] as $bln => $d): ?>
                            <div class="bg-white p-2.5 rounded-xl border border-slate-100 shadow-2xs">
                                <div class="flex items-center justify-between font-bold text-[11px] text-slate-800">
                                    <span><?= $bln ?></span>
                                    <span class="text-emerald-600 font-extrabold"><?= $d['efektif'] ?> mgg</span>
                                </div>
                                <p class="text-[9px] text-slate-400 mt-0.5 truncate" title="<?= $d['kegiatan'] ?>"><?= $d['kegiatan'] ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>

                <!-- CONTROLLER FILTER MAPEL & SEMESTER -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm no-print">
                    <form method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex bg-slate-100 p-1 rounded-2xl border border-slate-200">
                                <a href="admin-kurikulum-prota-promes.php?mapel=<?= urlencode($selected_mapel) ?>&sem=1" class="px-4 py-2 rounded-xl text-xs font-black transition <?= ($selected_sem === 1) ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                                    Semester 1 (Ganjil)
                                </a>
                                <a href="admin-kurikulum-prota-promes.php?mapel=<?= urlencode($selected_mapel) ?>&sem=2" class="px-4 py-2 rounded-xl text-xs font-black transition <?= ($selected_sem === 2) ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                                    Semester 2 (Genap)
                                </a>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="text-xs font-bold text-slate-500 whitespace-nowrap"><i class="fas fa-book mr-1"></i> Pilih Pelajaran:</label>
                            <select name="mapel" onchange="this.form.submit()" class="px-3.5 py-2 bg-slate-50 border-2 border-indigo-200 rounded-xl text-xs sm:text-sm font-extrabold text-slate-900 focus:ring-2 focus:ring-indigo-500 min-w-[200px]">
                                <?php foreach ($all_mapels as $m): ?>
                                    <option value="<?= htmlspecialchars($m) ?>" <?= ($selected_mapel === $m) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="sem" value="<?= $selected_sem ?>">
                        </div>
                    </form>
                </div>

                <!-- TABEL PROGRAM SEMESTER (PROMES) -->
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden p-6">
                    <div class="border-b pb-4 mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h2 class="font-black text-base text-slate-900 flex items-center gap-2">
                                Program Semester (Promes) — <?= htmlspecialchars($selected_mapel) ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-indigo-100 text-indigo-800">Semester <?= $selected_sem ?></span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">Tahun Ajaran 2026/2027 • Jenjang Kesetaraan Paket C / Paket B</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-extrabold bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200 text-slate-700">
                                Total Alokasi: <b><?= $tot_jp ?> Jam Pelajaran (JP)</b>
                            </span>
                            <a href="santri-belajar.php?mapel=<?= urlencode($selected_mapel) ?>&bab=1" target="_blank" class="bg-teal-50 hover:bg-teal-100 text-[#0d8276] border border-teal-200 font-bold px-3.5 py-1.5 rounded-xl text-xs transition flex items-center gap-1.5 no-print">
                                <i class="fas fa-book-reader"></i> <span>Buka di Ruang Santri</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-y border-slate-200 text-slate-600 font-black uppercase text-[10px] tracking-wider">
                                    <th class="py-3 px-4 w-12 text-center">Bab</th>
                                    <th class="py-3 px-4">Pokok Bahasan / Materi Modul</th>
                                    <th class="py-3 px-4 w-24 text-center">Alokasi JP</th>
                                    <th class="py-3 px-4 w-32 text-center">Distribusi Pekan</th>
                                    <th class="py-3 px-4">Model & Media Belajar</th>
                                    <th class="py-3 px-4 text-center w-28 no-print">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php if (count($list_promes) > 0): ?>
                                    <?php foreach ($list_promes as $p): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-4 text-center font-black">
                                            <span class="w-6 h-6 rounded-lg bg-indigo-100 text-indigo-800 text-[11px] inline-flex items-center justify-center"><?= $p['bab_nomor'] ?></span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-extrabold text-slate-900"><?= htmlspecialchars($p['judul_materi']) ?></div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">Kurikulum Merdeka PKBM • Standar Kemendikdasmen</div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center font-bold text-indigo-700">
                                            <?= $p['alokasi_jp'] ?> JP
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span class="px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-slate-100 text-slate-800 border border-slate-200">
                                                Minggu ke-<?= $p['minggu_ke_mulai'] ?> s/d <?= $p['minggu_ke_selesai'] ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold"><i class="fas fa-file-pdf"></i> 3D Flipbook</span>
                                                <span class="bg-red-50 text-red-700 border border-red-200 px-2 py-0.5 rounded text-[10px] font-bold"><i class="fab fa-youtube"></i> Video</span>
                                                <span class="bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold"><i class="fas fa-pencil-alt"></i> LKS Mandiri</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-center no-print">
                                            <a href="santri-belajar.php?mapel=<?= urlencode($selected_mapel) ?>&bab=<?= $p['bab_nomor'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs inline-flex items-center gap-1">
                                                <i class="fas fa-eye"></i> Pratinjau
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400">
                                            <i class="fas fa-folder-open text-2xl mb-2 opacity-50"></i>
                                            <p>Belum ada data distribusi Promes untuk mata pelajaran ini.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

</body>
</html>
