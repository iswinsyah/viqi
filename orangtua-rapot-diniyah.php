<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$orangtua_nama = $_SESSION['orangtua_nama'];
$is_super_admin = ($orangtua_id == 9999);

// Ambil daftar ananda yang sah & tentukan Ananda Aktif (Persisten Session + Cookie 30 Hari)
$santri_anak = getOrangtuaSantriList($conn, $orangtua_id);
$selected_child = getOrangtuaActiveSantri($conn, $orangtua_id, $santri_anak);
$santri_id = $selected_child ? (int)$selected_child['id'] : 0;
$active_menu = 'orangtua_rapot_diniyah';

// Keamanan: Pastikan santri ini milik orang tua yang login
if (!$is_super_admin && $santri_id > 0) {
    $valid_ids = array_map('intval', array_column($santri_anak, 'id'));
    if (!in_array($santri_id, $valid_ids)) {
        die("Akses Ditolak: Anda tidak memiliki otoritas melihat data santri ini.");
    }
}

// 2. Ambil data santri
$data_santri = $selected_child;
$santri_nama = $data_santri['nama_lengkap'] ?? '';

// 3. Filter Tahun Ajaran & Semester
$ta = $_GET['tahun_ajaran'] ?? '';
$sem = $_GET['semester'] ?? '';
$res_ta = $conn->query("SELECT DISTINCT tahun_ajaran FROM leger_nilai WHERE santri_id = $santri_id ORDER BY tahun_ajaran DESC");
$opsi_ta = ($res_ta) ? $res_ta->fetch_all(MYSQLI_ASSOC) : [];
$res_sem = $conn->query("SELECT DISTINCT semester FROM leger_nilai WHERE santri_id = $santri_id ORDER BY semester DESC");
$opsi_sem = ($res_sem) ? $res_sem->fetch_all(MYSQLI_ASSOC) : [];

// Default otomatis ke TA & Semester terbaru jika belum dipilih
if (empty($ta) && !empty($opsi_ta)) $ta = $opsi_ta[0]['tahun_ajaran'];
if (empty($sem) && !empty($opsi_sem)) $sem = $opsi_sem[0]['semester'];

// 4. Fetch Nilai (Hanya Kategori Diniyah)
$nilai_data = [];
$summary = ['jumlah' => 0, 'rata' => 0];
if ($santri_id > 0 && $ta && $sem) {
    $sql = "SELECT l.*, m.nama_mapel FROM leger_nilai l 
            JOIN master_mapel m ON l.mapel_id = m.id 
            WHERE l.santri_id = $santri_id AND l.tahun_ajaran = '$ta' AND l.semester = '$sem' 
            AND m.kategori_mapel = 'Diniyah' AND l.jenis_ujian = 'Ujian Akhir Semester (UAS)'";
    $res_n = $conn->query($sql);
    if ($res_n) {
        while ($r = $res_n->fetch_assoc()) $nilai_data[] = $r;
    }
    
    if (count($nilai_data) > 0) {
        $summary['jumlah'] = array_sum(array_column($nilai_data, 'nilai'));
        $summary['rata'] = round($summary['jumlah'] / count($nilai_data), 2);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Diniyah Ananda | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-slate-50 flex h-screen overflow-hidden font-sans text-slate-800">
    
    <!-- SIDEBAR ORANG TUA (SERAGAM DENGAN DASHBOARD.PHP) -->
    <?php include 'sidebar-orangtua.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP -->
        <header class="h-16 bg-white border-b border-teal-100/80 shadow-xs flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-orangtua" class="text-slate-600 hover:text-teal-700 md:hidden mr-3 p-1.5 rounded-lg focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                    <span class="bg-teal-50 text-[#0b8478] border border-teal-200 px-2.5 py-0.5 rounded-lg text-[11px] font-extrabold uppercase tracking-wide">
                        <i class="fas fa-users mr-1"></i>Ruang Orang Tua
                    </span>
                    <span class="hidden sm:inline text-slate-400">|</span>
                    <span class="hidden sm:inline font-bold text-slate-700">Rapor Diniyah (Kepesantrenan)</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <a href="dashboard.php" class="text-xs text-teal-800 bg-teal-50 hover:bg-teal-100 px-3 py-1.5 rounded-xl font-bold border border-teal-200 flex items-center gap-1.5 transition">
                    <i class="fas fa-house"></i> Beranda
                </a>
            </div>
        </header>

        <!-- MAIN BODY -->
        <main class="flex-1 overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-4xl mx-auto">
                
                <!-- TITLE & ANANDA SELECTOR -->
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2.5">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-[#0b8478] to-[#065e55] flex items-center justify-center text-white shadow-md shadow-teal-900/20">
                                <i class="fas fa-book-open-reader text-lg"></i>
                            </div>
                            <span>Rapor Diniyah Ananda</span>
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">Capaian kurikulum kepesantrenan, kitab kuning, tajwid, dan ilmu syar'i</p>
                    </div>

                    <!-- SELECTOR ANANDA -->
                    <?php if (count($santri_anak) > 1): ?>
                        <form method="GET" class="flex items-center gap-2 bg-white px-3 py-2 rounded-2xl border border-teal-100 shadow-xs">
                            <label class="text-xs font-bold text-[#0b8478] whitespace-nowrap flex items-center gap-1.5">
                                <i class="fas fa-child"></i> Pilih Ananda:
                            </label>
                            <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($ta) ?>">
                            <input type="hidden" name="semester" value="<?= htmlspecialchars($sem) ?>">
                            <select name="santri_id" onchange="this.form.submit()" class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs bg-slate-50/50 focus:ring-2 focus:ring-[#0b8478] focus:border-[#0b8478] font-bold text-slate-800 transition">
                                <?php foreach ($santri_anak as $sa): ?>
                                    <option value="<?= $sa['id'] ?>" <?= ($sa['id'] == $santri_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sa['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php elseif (!empty($selected_child)): ?>
                        <div class="flex items-center gap-2 bg-teal-50/80 px-4 py-2 rounded-2xl border border-teal-200/80 text-xs font-bold text-teal-950 shadow-xs">
                            <i class="fas fa-child text-[#0b8478]"></i>
                            <span>Ananda: <strong class="text-[#0b8478]"><?= htmlspecialchars($selected_child['nama_lengkap']) ?></strong></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- FILTER FORM -->
                <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 mb-6 no-print">
                    <form method="GET" class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="santri_id" value="<?= $santri_id ?>">
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Tahun Ajaran</label>
                            <select name="tahun_ajaran" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-500">
                                <?php foreach($opsi_ta as $o): ?>
                                    <option value="<?= $o['tahun_ajaran'] ?>" <?= ($ta == $o['tahun_ajaran']) ? 'selected' : '' ?>><?= $o['tahun_ajaran'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[150px]">
                            <label class="block text-xs font-bold text-slate-600 mb-1.5">Semester</label>
                            <select name="semester" class="w-full border border-slate-300 rounded-xl p-2.5 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-teal-500">
                                <?php foreach($opsi_sem as $o): ?>
                                    <option value="<?= $o['semester'] ?>" <?= ($sem == $o['semester']) ? 'selected' : '' ?>><?= $o['semester'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="bg-[#0b8478] hover:bg-[#086a60] text-white px-5 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
                            <i class="fas fa-filter"></i> Tampilkan
                        </button>
                        <button type="button" onclick="window.print()" class="bg-slate-700 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-1.5 shadow-xs" title="Cetak Rapor">
                            <i class="fas fa-print"></i> Cetak
                        </button>
                    </form>
                </div>

                <!-- LEMBAR RAPOR DINIYAH -->
                <?php if ($ta && $sem && !empty($nilai_data)): ?>
                    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-sm border border-slate-200">
                        <!-- HEADER KOP MA'HAD -->
                        <div class="text-center border-b-4 border-double border-teal-900 pb-5 mb-6">
                            <h2 class="text-base sm:text-lg font-bold text-teal-800 tracking-wider uppercase">LAPORAN HASIL BELAJAR DINIYAH</h2>
                            <h1 class="text-2xl sm:text-3xl font-black text-teal-950 mt-1">MA'HAD VILLA QURAN INDONESIA</h1>
                            <p class="text-xs text-slate-500 mt-1">Sistem Administrasi Digital Kepesantrenan (SADIGS 4.0)</p>
                        </div>

                        <!-- DATA IDENTITAS SANTRI -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs sm:text-sm mb-6 bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <div><span class="text-slate-400 w-28 inline-block font-medium">Nama Santri:</span> <b class="text-slate-900"><?= htmlspecialchars($santri_nama) ?></b></div>
                            <div><span class="text-slate-400 w-28 inline-block font-medium">Kelas / Asrama:</span> <b class="text-slate-900"><?= htmlspecialchars($data_santri['kelas_sekarang'] ?? '-') ?></b></div>
                            <div><span class="text-slate-400 w-28 inline-block font-medium">Kamar:</span> <b class="text-slate-900"><?= htmlspecialchars($data_santri['kamar_asrama'] ?? '-') ?></b></div>
                            <div><span class="text-slate-400 w-28 inline-block font-medium">Periode:</span> <b class="text-teal-900"><?= htmlspecialchars($sem) ?> (<?= htmlspecialchars($ta) ?>)</b></div>
                        </div>

                        <!-- TABEL NILAI -->
                        <div class="overflow-x-auto mb-6">
                            <table class="w-full border-collapse border border-slate-300 text-xs sm:text-sm">
                                <thead class="bg-teal-50 text-teal-950">
                                    <tr>
                                        <th class="border border-slate-300 p-2.5 text-center w-12">No</th>
                                        <th class="border border-slate-300 p-2.5 text-left">Mata Pelajaran Diniyah</th>
                                        <th class="border border-slate-300 p-2.5 text-center w-24">KKM</th>
                                        <th class="border border-slate-300 p-2.5 text-center w-24">Nilai Akhir</th>
                                        <th class="border border-slate-300 p-2.5 text-center w-28">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach($nilai_data as $n): 
                                        $val = (float)$n['nilai'];
                                        $pred = ($val >= 90) ? 'Mumtaz (A)' : (($val >= 80) ? 'Jayid Jiddan (B)' : (($val >= 70) ? 'Jayid (C)' : 'Maqbul (D)'));
                                    ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="border border-slate-300 p-2.5 text-center font-bold text-slate-500"><?= $no++ ?></td>
                                            <td class="border border-slate-300 p-2.5 font-bold text-slate-900"><?= htmlspecialchars($n['nama_mapel']) ?></td>
                                            <td class="border border-slate-300 p-2.5 text-center text-slate-600 font-semibold">70</td>
                                            <td class="border border-slate-300 p-2.5 text-center font-black text-teal-800 text-base"><?= $n['nilai'] ?></td>
                                            <td class="border border-slate-300 p-2.5 text-center font-bold text-xs"><?= $pred ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="bg-slate-100 font-bold">
                                    <tr>
                                        <td colspan="3" class="border border-slate-300 p-2.5 text-right font-extrabold uppercase text-xs">Total Nilai</td>
                                        <td class="border border-slate-300 p-2.5 text-center font-black text-slate-900"><?= $summary['jumlah'] ?></td>
                                        <td class="border border-slate-300 p-2.5"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="border border-slate-300 p-2.5 text-right font-extrabold uppercase text-xs text-teal-900">Rata-rata Nilai</td>
                                        <td class="border border-slate-300 p-2.5 text-center font-black text-teal-700 text-base"><?= $summary['rata'] ?></td>
                                        <td class="border border-slate-300 p-2.5 text-center text-xs">
                                            <?= ($summary['rata'] >= 85) ? '<span class="text-emerald-700 font-bold">Sangat Baik</span>' : '<span class="text-teal-700 font-bold">Baik</span>' ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- CATATAN & TANDA TANGAN -->
                        <div class="mt-8 pt-4 border-t border-slate-200 text-xs text-slate-500 flex justify-between items-end">
                            <div>
                                <p class="italic">Dokumen ini diterbitkan resmi melalui Sistem SADIGS Ma'had Villa Quran.</p>
                            </div>
                            <div class="text-center font-bold text-slate-800">
                                <p>Bogor, <?= date('d F Y') ?></p>
                                <p class="mt-1 font-semibold text-slate-500">Mudir Ma'had,</p>
                                <div class="h-16"></div>
                                <p class="underline font-black">( Ustadz Pembina Diniyah )</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-white p-12 rounded-2xl shadow-xs border border-slate-200 text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400 text-lg">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="font-bold text-slate-700 text-base">Belum Ada Lembar Rapor Diniyah</h3>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                            <?php if (empty($opsi_ta)): ?>
                                Nilai Ujian Akhir Semester Diniyah ananda belum diinput atau belum dipublikasi oleh Ustadz Pengampu.
                            <?php else: ?>
                                Silakan pilih Tahun Ajaran dan Semester di atas lalu klik tombol <b>Tampilkan</b>.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>

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