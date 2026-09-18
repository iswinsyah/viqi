<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$active_menu = 'rapot_santri';

// Active Tab: 'pkbm' or 'diniyah'
$tab = $_GET['tab'] ?? 'pkbm';
if (!in_array($tab, ['pkbm', 'diniyah'])) {
    $tab = 'pkbm';
}

// 1. Data Santri
$stmt_santri = $conn->prepare("SELECT * FROM buku_induk_santri WHERE id = ?");
$stmt_santri->bind_param("i", $santri_id);
$stmt_santri->execute();
$data_santri = $stmt_santri->get_result()->fetch_assoc();

$kelas_santri = $data_santri['kelas_sekarang'] ?? 'Paket B';

// Tentukan Paket B vs Paket C
$paket_tipe = 'Paket B';
if (
    str_contains(strtolower($kelas_santri), 'paket c') || 
    str_contains(strtolower($kelas_santri), 'sma') || 
    preg_match('/\b(10|11|12|x|xi|xii)\b/i', strtolower($kelas_santri))
) {
    $paket_tipe = 'Paket C';
}

// 2. Filter Tahun Ajaran & Semester
$filters = [
    'tahun_ajaran' => $_GET['tahun_ajaran'] ?? '',
    'semester' => $_GET['semester'] ?? 'Ganjil'
];

$opsi_ta = [];
$res_ta = $conn->query("SELECT DISTINCT tahun_ajaran FROM leger_nilai WHERE santri_id = $santri_id ORDER BY tahun_ajaran DESC");
if ($res_ta && $res_ta->num_rows > 0) {
    while ($r = $res_ta->fetch_assoc()) $opsi_ta[] = $r['tahun_ajaran'];
} else {
    $opsi_ta = [date('Y') . '/' . (date('Y') + 1), (date('Y') - 1) . '/' . date('Y')];
}

if (empty($filters['tahun_ajaran']) && !empty($opsi_ta)) {
    $filters['tahun_ajaran'] = $opsi_ta[0];
}

$ta_esc = $conn->real_escape_string($filters['tahun_ajaran']);
$sem_esc = $conn->real_escape_string($filters['semester']);

// ==========================================
// A. LOGIC DATA TAB PKBM
// ==========================================
$nilai_pkbm = [];
$catatan_pkbm = null;

if ($data_santri && !empty($filters['tahun_ajaran'])) {
    $sql_pkbm = "
        SELECT m.id as mapel_id, m.nama_mapel, m.kode_mapel, 
               ROUND(AVG(l.nilai), 0) as nilai
        FROM leger_nilai l 
        JOIN master_mapel m ON l.mapel_id = m.id 
        WHERE l.santri_id = $santri_id AND l.tahun_ajaran = '$ta_esc' AND l.semester = '$sem_esc' 
        GROUP BY m.id
        ORDER BY m.kategori_mapel ASC, m.nama_mapel ASC
    ";
    $res_pkbm = $conn->query($sql_pkbm);
    if ($res_pkbm) {
        while ($r = $res_pkbm->fetch_assoc()) $nilai_pkbm[] = $r;
    }

    $res_c = $conn->query("SELECT * FROM raport_pkbm_catatan WHERE santri_id = $santri_id AND tahun_ajaran = '$ta_esc' AND semester = '$sem_esc'");
    if ($res_c && $res_c->num_rows > 0) {
        $catatan_pkbm = $res_c->fetch_assoc();
    }
}

function hitung_predikat_pkbm($nilai) {
    if ($nilai >= 88) return ['predikat' => 'A', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300'];
    if ($nilai >= 78) return ['predikat' => 'B', 'badge' => 'bg-teal-100 text-teal-800 border-teal-300'];
    if ($nilai >= 67) return ['predikat' => 'C', 'badge' => 'bg-amber-100 text-amber-800 border-amber-300'];
    return ['predikat' => 'D', 'badge' => 'bg-rose-100 text-rose-800 border-rose-300'];
}

// ==========================================
// B. LOGIC DATA TAB DINIYAH
// ==========================================
$nilai_diniyah = [];
$summary_diniyah = ['jumlah' => 0, 'rata' => 0];

if ($data_santri && !empty($filters['tahun_ajaran'])) {
    $sql_diniyah = "
        SELECT l.*, m.nama_mapel FROM leger_nilai l 
        JOIN master_mapel m ON l.mapel_id = m.id 
        WHERE l.santri_id = $santri_id AND l.tahun_ajaran = '$ta_esc' AND l.semester = '$sem_esc' 
        AND m.kategori_mapel = 'Diniyah' AND l.jenis_ujian = 'Ujian Akhir Semester (UAS)'
        ORDER BY m.nama_mapel ASC
    ";
    $res_d = $conn->query($sql_diniyah);
    if ($res_d) {
        while($r = $res_d->fetch_assoc()) {
            $nilai_diniyah[] = $r;
        }
    }
    
    if (count($nilai_diniyah) > 0) {
        $summary_diniyah['jumlah'] = array_sum(array_column($nilai_diniyah, 'nilai'));
        $summary_diniyah['rata'] = round($summary_diniyah['jumlah'] / count($nilai_diniyah), 2);
    }
}

function getDeskripsiDiniyah($nilai) {
    if ($nilai >= 90) return "Mumtaz (Sangat Baik) - Menguasai materi dengan sangat memuaskan.";
    if ($nilai >= 80) return "Jayyid Jiddan (Baik Sekali) - Menguasai materi dengan baik.";
    if ($nilai >= 70) return "Jayyid (Baik) - Memenuhi standar kelulusan minimal.";
    return "Maqbul (Cukup) - Memerlukan bimbingan dan muroja'ah lebih giat.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rapor Santri | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print, nav, header { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .print-area { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; }
            @page { size: A4; margin: 15mm; }
        }
    </style>
</head>
<body class="bg-[#e1f5f2] font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-[#0d8276] text-white shadow-md flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-santri" class="text-white hover:text-teal-200 md:hidden p-2 rounded-xl focus:outline-none transition">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-xl bg-white/20 flex items-center justify-center text-white text-sm shadow-inner">
                        <i class="fas fa-graduation-cap"></i>
                    </span>
                    <div>
                        <h2 class="font-extrabold text-sm sm:text-base leading-tight">Rapor Hasil Belajar</h2>
                        <p class="text-[10px] text-teal-100"><?= htmlspecialchars($santri_nama) ?></p>
                    </div>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <button onclick="window.print()" class="bg-white/20 hover:bg-white/30 text-white text-xs font-bold px-3 py-1.5 rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <i class="fas fa-print"></i> Cetak
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#e1f5f2] p-3.5 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-4xl mx-auto">
                
                <!-- 1. TAB SWITCHER (PKBM VS DINIYAH) -->
                <div class="no-print bg-white/90 backdrop-blur-md p-1.5 rounded-2xl border border-teal-100 shadow-sm flex items-center gap-2 mb-5">
                    <a href="santri-rapot.php?tab=pkbm&tahun_ajaran=<?= urlencode($filters['tahun_ajaran']) ?>&semester=<?= urlencode($filters['semester']) ?>" 
                       class="flex-1 py-2.5 px-3 rounded-xl text-xs sm:text-sm font-extrabold text-center transition-all flex items-center justify-center gap-2 <?= ($tab === 'pkbm') ? 'bg-[#0d8276] text-white shadow-md' : 'text-slate-600 hover:text-[#0d8276] hover:bg-teal-50/50' ?>">
                        <i class="fas fa-file-invoice text-sm"></i>
                        <span>Raport Diknas PKBM (<?= $paket_tipe ?>)</span>
                    </a>
                    <a href="santri-rapot.php?tab=diniyah&tahun_ajaran=<?= urlencode($filters['tahun_ajaran']) ?>&semester=<?= urlencode($filters['semester']) ?>" 
                       class="flex-1 py-2.5 px-3 rounded-xl text-xs sm:text-sm font-extrabold text-center transition-all flex items-center justify-center gap-2 <?= ($tab === 'diniyah') ? 'bg-[#0d8276] text-white shadow-md' : 'text-slate-600 hover:text-[#0d8276] hover:bg-teal-50/50' ?>">
                        <i class="fas fa-book-quran text-sm"></i>
                        <span>Rapor Diniyah (Kepesantrenan)</span>
                    </a>
                </div>

                <!-- 2. FILTER BAR (TAHUN AJARAN & SEMESTER) -->
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-teal-100 shadow-sm mb-6 no-print">
                    <form method="GET" action="santri-rapot.php" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Tahun Ajaran</label>
                            <select name="tahun_ajaran" class="w-full text-xs sm:text-sm border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-teal-500 bg-slate-50 focus:bg-white" required>
                                <?php foreach ($opsi_ta as $ta): ?>
                                    <option value="<?= htmlspecialchars($ta) ?>" <?= $filters['tahun_ajaran'] == $ta ? 'selected' : '' ?>><?= htmlspecialchars($ta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Semester</label>
                            <select name="semester" class="w-full text-xs sm:text-sm border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-teal-500 bg-slate-50 focus:bg-white" required>
                                <option value="Ganjil" <?= $filters['semester'] === 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                                <option value="Genap" <?= $filters['semester'] === 'Genap' ? 'selected' : '' ?>>Genap</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-[#0d8276] hover:bg-[#0b6f65] text-white font-bold py-2.5 px-4 rounded-xl text-xs sm:text-sm transition shadow-md shadow-teal-900/10 flex items-center justify-center gap-2">
                                <i class="fas fa-search"></i> Tampilkan Rapor
                            </button>
                        </div>
                    </form>
                </div>

                <!-- ================================================== -->
                <!-- 3. KONTEN TAB 1: RAPORT PKBM                       -->
                <!-- ================================================== -->
                <?php if ($tab === 'pkbm'): ?>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100 shadow-sm print-area">
                    
                    <!-- KOP RAPORT -->
                    <div class="border-b-2 border-slate-800 pb-4 mb-6 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-[#0d8276] text-2xl font-black shadow-inner">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div>
                                <h1 class="text-lg sm:text-xl font-black text-slate-900 uppercase tracking-tight">RAPOR HASIL BELAJAR PENDIDIKAN KESETARAAN</h1>
                                <h2 class="text-sm font-extrabold text-[#0d8276] uppercase">PROGRAM <?= strtoupper($paket_tipe) ?> (SETARA <?= ($paket_tipe === 'Paket C') ? 'SMA' : 'SMP' ?>)</h2>
                                <p class="text-[11px] text-slate-500">PKBM Villa Quran Indonesia • NPSN: P9996543 • Terakreditasi</p>
                            </div>
                        </div>
                    </div>

                    <!-- IDENTITAS SANTRI -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200 mb-6">
                        <div class="space-y-1.5">
                            <div class="flex"><span class="w-32 font-bold text-slate-500">Nama Santri:</span> <span class="font-extrabold text-slate-900"><?= htmlspecialchars($data_santri['nama_lengkap'] ?? $santri_nama) ?></span></div>
                            <div class="flex"><span class="w-32 font-bold text-slate-500">NIS / NISN:</span> <span class="font-semibold"><?= htmlspecialchars($data_santri['nis'] ?? '-') ?> / <?= htmlspecialchars($data_santri['nisn'] ?? '-') ?></span></div>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex"><span class="w-32 font-bold text-slate-500">Kelas / Tingkat:</span> <span class="font-extrabold text-[#0d8276]"><?= htmlspecialchars($data_santri['kelas_sekarang'] ?? '-') ?> (<?= $paket_tipe ?>)</span></div>
                            <div class="flex"><span class="w-32 font-bold text-slate-500">Semester / TA:</span> <span class="font-semibold"><?= htmlspecialchars($filters['semester']) ?> / <?= htmlspecialchars($filters['tahun_ajaran']) ?></span></div>
                        </div>
                    </div>

                    <!-- TABEL NILAI PKBM -->
                    <div class="mb-6">
                        <h3 class="font-extrabold text-xs sm:text-sm uppercase text-slate-900 mb-3 border-b-2 border-[#0d8276] pb-1 flex items-center justify-between">
                            <span>A. Capaian Hasil Belajar (Nilai Akademik Diknas)</span>
                            <span class="text-[11px] font-bold text-[#0d8276] normal-case">Standar Kurikulum Nasional</span>
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border border-slate-300 text-xs">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-10">No</th>
                                        <th class="py-2.5 px-4 border border-slate-300 text-left">Mata Pelajaran</th>
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-20">Nilai Akhir</th>
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-20">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($nilai_pkbm)): ?>
                                        <?php $no = 1; foreach ($nilai_pkbm as $nm): $val = (float)$nm['nilai']; $p_info = hitung_predikat_pkbm($val); ?>
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="py-2 px-3 border border-slate-300 text-center font-bold text-slate-500"><?= $no++ ?></td>
                                                <td class="py-2 px-4 border border-slate-300 font-bold text-slate-800"><?= htmlspecialchars($nm['nama_mapel']) ?></td>
                                                <td class="py-2 px-3 border border-slate-300 text-center font-black text-sm <?= $val >= 75 ? 'text-emerald-600' : 'text-amber-600' ?>"><?= number_format($val, 0) ?></td>
                                                <td class="py-2 px-3 border border-slate-300 text-center font-bold">
                                                    <span class="px-2 py-0.5 rounded border text-[10px] <?= $p_info['badge'] ?>"><?= $p_info['predikat'] ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="py-8 text-center text-slate-400 italic">Belum ada nilai akademik yang diterbitkan untuk semester ini.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- CATATAN WALI KELAS -->
                    <?php if (!empty($catatan_pkbm['catatan_wali_kelas'])): ?>
                        <div class="mb-6 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs">
                            <b class="text-slate-800">Catatan Wali Kelas / Pembina:</b>
                            <p class="italic text-slate-600 mt-1 leading-relaxed">"<?= htmlspecialchars($catatan_pkbm['catatan_wali_kelas']) ?>"</p>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- ================================================== -->
                <!-- 4. KONTEN TAB 2: RAPOR DINIYAH                     -->
                <!-- ================================================== -->
                <?php else: ?>
                <div class="bg-white rounded-3xl p-6 sm:p-8 border border-teal-100 shadow-sm print-area">
                    
                    <!-- KOP RAPOR DINIYAH -->
                    <div class="border-b-2 border-slate-800 pb-4 mb-6 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 text-2xl font-black shadow-inner">
                                <i class="fas fa-book-quran"></i>
                            </div>
                            <div>
                                <h1 class="text-lg sm:text-xl font-black text-slate-900 uppercase tracking-tight">RAPOR DINIYAH & KEPESANTRENAN</h1>
                                <h2 class="text-sm font-extrabold text-amber-600 uppercase">PONDOK PESANTREN VILLA QURAN INDONESIA</h2>
                                <p class="text-[11px] text-slate-500">Evaluasi Pembelajaran Kitab, Tajwid, Bahasa Arab, dan Adab</p>
                            </div>
                        </div>
                    </div>

                    <!-- SUMMARY CARDS -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="bg-amber-50/70 p-4 rounded-2xl border border-amber-100">
                            <div class="text-[10px] text-amber-700 font-bold uppercase tracking-wider">Total Nilai Diniyah</div>
                            <div class="text-2xl font-black text-amber-950 mt-0.5"><?= $summary_diniyah['jumlah'] ?></div>
                        </div>
                        <div class="bg-teal-50/70 p-4 rounded-2xl border border-teal-100">
                            <div class="text-[10px] text-[#0d8276] font-bold uppercase tracking-wider">Rata-Rata Nilai</div>
                            <div class="text-2xl font-black text-teal-950 mt-0.5"><?= $summary_diniyah['rata'] ?></div>
                        </div>
                    </div>

                    <!-- TABEL NILAI DINIYAH -->
                    <div class="mb-6">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px]">
                                        <th class="p-3 rounded-l-xl w-10 text-center">No</th>
                                        <th class="p-3">Mata Pelajaran Diniyah</th>
                                        <th class="p-3 text-center w-20">Nilai</th>
                                        <th class="p-3 rounded-r-xl">Keterangan / Capaian</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php if (!empty($nilai_diniyah)): ?>
                                        <?php $no = 1; foreach ($nilai_diniyah as $row): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="p-3 text-center text-slate-500 font-bold"><?= $no++ ?></td>
                                            <td class="p-3 font-bold text-slate-800"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                            <td class="p-3 text-center font-black text-sm <?= $row['nilai'] >= 75 ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $row['nilai'] ?></td>
                                            <td class="p-3 text-slate-600 text-[11px]"><?= getDeskripsiDiniyah($row['nilai']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" class="p-8 text-center text-slate-400 italic">Belum ada data nilai Diniyah untuk tahun ajaran & semester yang dipilih.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </main>

    </div>

    <!-- BOTTOM NAVBAR MOBILE -->
    <?php include 'bottombar-santri.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-santri');
            const openBtn = document.getElementById('open-sidebar-santri');
            const overlay = document.getElementById('sidebar-overlay-santri');
            if(openBtn && sidebar) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
            if(overlay && sidebar) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
        });
    </script>
</body>
</html>