<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$active_menu = 'rapot_diniyah';

// Ambil data santri yang login
$stmt_santri = $conn->prepare("SELECT * FROM buku_induk_santri WHERE id = ?");
$stmt_santri->bind_param("i", $santri_id);
$stmt_santri->execute();
$data_santri = $stmt_santri->get_result()->fetch_assoc();

$kelas_santri = $data_santri['kelas_sekarang'] ?? null;

// Filter
$filters = [
    'tahun_ajaran' => $_GET['tahun_ajaran'] ?? '',
    'semester' => $_GET['semester'] ?? ''
];

$opsi_filter = [];
if ($data_santri) {
    $stmt_opsi = $conn->prepare("SELECT DISTINCT tahun_ajaran, semester FROM leger_nilai WHERE santri_id = ? ORDER BY tahun_ajaran DESC, semester DESC");
    $stmt_opsi->bind_param("i", $santri_id);
    $stmt_opsi->execute();
    $opsi_filter = $stmt_opsi->get_result()->fetch_all(MYSQLI_ASSOC);
}

$opsi_ta = array_unique(array_column($opsi_filter, 'tahun_ajaran'));
$opsi_semester = array_unique(array_column($opsi_filter, 'semester'));
$is_data_available_for_filter = !empty($opsi_ta);

// Fetch Nilai Diniyah
$nilai_data = [];
$summary = ['jumlah' => 0, 'rata' => 0];
$show_rapot = false;

if ($data_santri && !empty($filters['tahun_ajaran']) && !empty($filters['semester'])) {
    $show_rapot = true;
    $ta = $conn->real_escape_string($filters['tahun_ajaran']);
    $sem = $conn->real_escape_string($filters['semester']);
    
    $sql = "SELECT l.*, m.nama_mapel FROM leger_nilai l 
            JOIN master_mapel m ON l.mapel_id = m.id 
            WHERE l.santri_id = $santri_id AND l.tahun_ajaran = '$ta' AND l.semester = '$sem' 
            AND m.kategori_mapel = 'Diniyah' AND l.jenis_ujian = 'Ujian Akhir Semester (UAS)'";
    $res_n = $conn->query($sql);
    if ($res_n) {
        while($r = $res_n->fetch_assoc()) {
            $nilai_data[] = $r;
        }
    }
    
    if (count($nilai_data) > 0) {
        $summary['jumlah'] = array_sum(array_column($nilai_data, 'nilai'));
        $summary['rata'] = round($summary['jumlah'] / count($nilai_data), 2);
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Diniyah | <?= htmlspecialchars($santri_nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            #sidebar-santri, header, #form-filter, .no-print, nav { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .rapot-container { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white border-b border-indigo-100 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-santri" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-book-quran mr-1"></i>Ruang Santri
                    </span>
                    <span>Rapor Diniyah</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs text-slate-500 bg-slate-100 px-3 py-1 rounded-full font-semibold">
                    <i class="fas fa-user text-indigo-600 mr-1"></i><?= htmlspecialchars($santri_nama) ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-4xl mx-auto">
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 no-print">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-md shadow-amber-200">
                                <i class="fas fa-book-quran text-lg"></i>
                            </div>
                            <span>Rapor Diniyah & Kepesantrenan</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">Capaian nilai kepesantrenan, tajwid, adab, dan diniyah santri</p>
                    </div>
                </div>

                <!-- FILTER FORM -->
                <div class="bg-white p-5 rounded-2xl border border-indigo-100 shadow-sm mb-6 no-print">
                    <form id="form-filter" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1.5">Tahun Ajaran</label>
                            <select name="tahun_ajaran" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 bg-slate-50 focus:bg-white" required>
                                <option value="">-- Pilih Tahun Ajaran --</option>
                                <?php foreach ($opsi_ta as $ta): ?>
                                    <option value="<?= htmlspecialchars($ta) ?>" <?= $filters['tahun_ajaran'] == $ta ? 'selected' : '' ?>><?= htmlspecialchars($ta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase mb-1.5">Semester</label>
                            <select name="semester" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 bg-slate-50 focus:bg-white" required>
                                <option value="">-- Pilih Semester --</option>
                                <?php foreach ($opsi_semester as $sem): ?>
                                    <option value="<?= htmlspecialchars($sem) ?>" <?= $filters['semester'] == $sem ? 'selected' : '' ?>><?= htmlspecialchars($sem) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-xl text-sm transition shadow-md shadow-indigo-100 flex items-center justify-center gap-2">
                                <i class="fas fa-search"></i> Tampilkan Rapor
                            </button>
                        </div>
                    </form>
                </div>

                <?php if ($show_rapot): ?>
                    <?php if (!empty($nilai_data)): ?>
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 mb-6 rapot-container">
                            <div class="flex justify-between items-center border-b pb-4 mb-4">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Hasil Pembelajaran Diniyah</h2>
                                    <p class="text-xs text-slate-500">Tahun Ajaran: <?= htmlspecialchars($filters['tahun_ajaran']) ?> | Semester: <?= htmlspecialchars($filters['semester']) ?></p>
                                </div>
                                <button onclick="window.print()" class="no-print bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                    <i class="fas fa-print"></i> Cetak
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="bg-amber-50/60 p-4 rounded-xl border border-amber-100">
                                    <div class="text-xs text-amber-700 font-bold uppercase">Total Nilai</div>
                                    <div class="text-2xl font-black text-amber-900"><?= $summary['jumlah'] ?></div>
                                </div>
                                <div class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-100">
                                    <div class="text-xs text-indigo-700 font-bold uppercase">Rata-Rata Nilai</div>
                                    <div class="text-2xl font-black text-indigo-900"><?= $summary['rata'] ?></div>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm border-collapse">
                                    <thead>
                                        <tr class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                                            <th class="p-3 rounded-l-xl">No</th>
                                            <th class="p-3">Mata Pelajaran Diniyah</th>
                                            <th class="p-3 text-center">Nilai</th>
                                            <th class="p-3 rounded-r-xl">Keterangan / Capaian</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php $no = 1; foreach ($nilai_data as $row): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="p-3 text-slate-500 font-medium"><?= $no++ ?></td>
                                            <td class="p-3 font-bold text-slate-800"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                            <td class="p-3 text-center font-black <?= $row['nilai'] >= 75 ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $row['nilai'] ?></td>
                                            <td class="p-3 text-xs text-slate-600"><?= getDeskripsiDiniyah($row['nilai']) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
                            <i class="fas fa-folder-open text-4xl text-slate-300 mb-3"></i>
                            <p class="font-bold text-slate-700">Data Nilai Belum Tersedia</p>
                            <p class="text-xs text-slate-400 mt-1">Belum ada nilai kategori Diniyah untuk tahun ajaran & semester yang dipilih.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-white rounded-2xl border border-slate-200 p-8 text-center text-slate-500">
                        <i class="fas fa-filter text-4xl text-indigo-300 mb-3"></i>
                        <p class="font-bold text-slate-700">Silakan Pilih Filter</p>
                        <p class="text-xs text-slate-400 mt-1">Pilih Tahun Ajaran dan Semester di atas untuk menampilkan Rapor Diniyah.</p>
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
