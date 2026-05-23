<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$active_menu = 'rapot_santri';

// --- PERSIAPAN DATA ---
// Ambil data santri yang login
$stmt_santri = $conn->prepare("SELECT * FROM buku_induk_santri WHERE id = ?");
$stmt_santri->bind_param("i", $santri_id);
$stmt_santri->execute();
$data_santri = $stmt_santri->get_result()->fetch_assoc();

$kelas_santri = $data_santri['kelas_sekarang'] ?? null;

// Ambil opsi filter dari database berdasarkan data santri
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

// --- PROSES PENGAMBILAN DATA RAPOT ---
$nilai_kelompok = [];
$summary = [
    'jumlah_nilai' => 0,
    'rata_rata' => 0,
    'peringkat' => 0,
    'total_siswa' => 0
];
$show_rapot = false;

if ($data_santri && !empty($filters['tahun_ajaran']) && !empty($filters['semester'])) {
    $show_rapot = true;

    // 1. Ambil semua nilai UAS di kelas & semester yang sama untuk perhitungan peringkat
    $sql_all_scores = "SELECT l.santri_id, s.nama_lengkap, l.mapel_id, m.nama_mapel, m.kategori_mapel, l.nilai
                       FROM leger_nilai l
                       JOIN buku_induk_santri s ON l.santri_id = s.id
                       JOIN master_mapel m ON l.mapel_id = m.id
                       WHERE l.kelas = ? AND l.tahun_ajaran = ? AND l.semester = ? AND l.jenis_ujian = 'Ujian Akhir Semester (UAS)'
                       ORDER BY s.nama_lengkap, m.id";
    
    $stmt_all = $conn->prepare($sql_all_scores);
    $stmt_all->bind_param("sss", $kelas_santri, $filters['tahun_ajaran'], $filters['semester']);
    $stmt_all->execute();
    $result_all = $stmt_all->get_result();

    $data_kelas_leger = [];
    if ($result_all && $result_all->num_rows > 0) {
        // 2. Olah data mentah menjadi format pivot (sama seperti di admin-leger.php)
        while ($row = $result_all->fetch_assoc()) {
            $current_santri_id = $row['santri_id'];
            if (!isset($data_kelas_leger[$current_santri_id])) {
                $data_kelas_leger[$current_santri_id] = ['nama' => $row['nama_lengkap'], 'nilai' => [], 'jumlah' => 0, 'rata_rata' => 0];
            }
            $data_kelas_leger[$current_santri_id]['nilai'][$row['mapel_id']] = $row['nilai'];
            
            // Jika ini adalah santri yang sedang login, kumpulkan nilainya untuk ditampilkan
            if ($current_santri_id == $santri_id) {
                $nilai_kelompok[$row['kategori_mapel']][] = [
                    'mapel' => $row['nama_mapel'],
                    'nilai' => $row['nilai']
                ];
            }
        }

        // 3. Hitung Jumlah & Rata-rata untuk semua siswa di kelas
        foreach ($data_kelas_leger as &$santri) {
            $total_nilai = array_sum($santri['nilai']);
            $jumlah_mapel = count($santri['nilai']);
            $santri['jumlah'] = $total_nilai;
            $santri['rata_rata'] = $jumlah_mapel > 0 ? round($total_nilai / $jumlah_mapel, 2) : 0;
        }
        unset($santri);

        // 4. Urutkan untuk menentukan peringkat
        uasort($data_kelas_leger, function($a, $b) {
            return $b['rata_rata'] <=> $a['rata_rata'];
        });

        // 5. Cari peringkat santri yang login
        $peringkat = 1;
        foreach ($data_kelas_leger as $id => $data) {
            if ($id == $santri_id) {
                $summary['peringkat'] = $peringkat;
                $summary['jumlah_nilai'] = $data['jumlah'];
                $summary['rata_rata'] = $data['rata_rata'];
                break;
            }
            $peringkat++;
        }
        $summary['total_siswa'] = count($data_kelas_leger);
    }
}

// Fungsi untuk deskripsi capaian
function getDeskripsiCapaian($nilai) {
    if ($nilai >= 90) return "Ananda menunjukkan penguasaan yang sangat baik pada seluruh kompetensi.";
    if ($nilai >= 80) return "Ananda menunjukkan penguasaan yang baik pada seluruh kompetensi.";
    if ($nilai >= 75) return "Ananda telah mencapai ketuntasan belajar dengan penguasaan yang cukup.";
    return "Ananda memerlukan bimbingan lebih lanjut untuk mencapai ketuntasan belajar.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Digital | <?= htmlspecialchars($santri_nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            #sidebar-santri, header, #form-filter, .no-print { display: none !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .rapot-container { box-shadow: none !important; border: none !important; }
        }
        .table-rapot { border-collapse: collapse; width: 100%; font-size: 11px; }
        .table-rapot th, .table-rapot td { border: 1px solid #333; padding: 6px 8px; }
        .table-rapot th { background-color: #e5e7eb; font-weight: bold; text-align: center; }
        .table-rapot .text-center { text-align: center; }
        .signature-box { display: inline-block; text-align: center; width: 200px; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-santri.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center"><button id="open-sidebar-santri" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 no-print">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book-reader text-indigo-600 mr-2"></i>Rapor Digital</h1>
                <p class="text-gray-500 mt-1">Lihat rekapitulasi hasil belajarmu di sini.</p>
            </div>

            <?php if (!$data_santri): ?>
                <div class="bg-rose-100 text-rose-800 p-6 rounded-xl shadow-sm border border-rose-200">
                    <h3 class="font-bold text-lg"><i class="fas fa-exclamation-triangle mr-2"></i> Kesalahan Data Santri</h3>
                    <p class="mt-2">Data santri dengan ID sesi <strong><?= htmlspecialchars($_SESSION['santri_id']) ?></strong> tidak ditemukan di database. Halaman rapor tidak dapat dimuat.</p>
                    <p class="mt-1 text-sm">Ini biasanya terjadi jika Anda login sebagai Super Admin. Silakan coba login sebagai akun santri biasa untuk melihat halaman ini dengan benar.</p>
                </div>
            <?php else: ?>

            <!-- FORM FILTER -->
            <div id="form-filter" class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 p-6 no-print">
                <?php if ($is_data_available_for_filter): ?>
                    <form action="santri-rapot.php" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                        <div class="flex-1 w-full"><label class="text-sm font-medium">Tahun Ajaran</label><select name="tahun_ajaran" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><?php foreach($opsi_ta as $o) echo "<option value='$o' ".($filters['tahun_ajaran']==$o?'selected':'').">".htmlspecialchars($o)."</option>"; ?></select></div>
                        <div class="flex-1 w-full"><label class="text-sm font-medium">Semester</label><select name="semester" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><?php foreach($opsi_semester as $o) echo "<option value='$o' ".($filters['semester']==$o?'selected':'').">".htmlspecialchars($o)."</option>"; ?></select></div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition w-full sm:w-auto"><i class="fas fa-eye mr-2"></i> Tampilkan Rapor</button>
                    </form>
                <?php else: ?>
                    <div class="text-center text-gray-500">
                        <i class="fas fa-info-circle text-2xl mb-2 text-gray-400"></i>
                        <p class="font-medium">Filter Rapor Belum Tersedia</p>
                        <p class="text-sm">Belum ada data nilai yang tercatat untuk Anda. Rapor akan dapat dilihat setelah Ustadz menginput nilai semester.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$is_data_available_for_filter && !$show_rapot): ?>
                <div class="bg-amber-100 text-amber-800 p-4 rounded-lg mb-6 text-sm no-print">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Mode Pratinjau:</strong> Data nilai belum tersedia. Tampilan rapor di bawah ini menggunakan data contoh untuk menunjukkan format.
                </div>
            <?php endif; ?>

            <!-- HASIL RAPOT -->
            <?php // Logic is changed to always show the report structure for preview purposes. ?>
            <?php if ($show_rapot || !$is_data_available_for_filter): ?>
                <div class="rapot-container bg-white rounded-xl shadow-lg border border-gray-200 p-8 max-w-4xl mx-auto">
                    <div class="text-right mb-6 no-print"><button onclick="window.print()" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center ml-auto"><i class="fas fa-print mr-2"></i> Cetak Rapor</button></div>
                    <div class="text-center border-b-4 border-black pb-2 mb-4"><h2 class="text-xl font-bold">LAPORAN HASIL BELAJAR SANTRI</h2><h3 class="text-2xl font-extrabold">VILLA QURAN INDONESIA</h3><p class="text-xs">Jl. Sejuk Asri No. 1, Kota Quran | Telp: (021) 1234-5678</p></div>
                    <table class="text-sm mb-6 w-full">
                        <tr><td class="font-bold w-1/4">Nama Santri</td><td class="w-1/2">: <?= htmlspecialchars($data_santri['nama_lengkap']) ?></td><td class="font-bold w-1/4">Kelas</td><td>: <?= htmlspecialchars($data_santri['kelas_sekarang'] ?? 'Contoh Kelas') ?></td></tr>
                        <tr><td class="font-bold">NIS / NISN</td><td>: <?= htmlspecialchars($data_santri['nis'] ?? '12345') ?> / <?= htmlspecialchars($data_santri['nisn'] ?? '0012345') ?></td><td class="font-bold">Semester</td><td>: <?= htmlspecialchars($filters['semester'] ?: 'Ganjil') ?></td></tr>
                        <tr><td class="font-bold">Nama Sekolah</td><td>: Villa Quran Indonesia</td><td class="font-bold">Tahun Ajaran</td><td>: <?= htmlspecialchars($filters['tahun_ajaran'] ?: date('Y').'/'.(date('Y')+1)) ?></td></tr>
                    </table>
                    <h4 class="font-bold text-sm mb-2">A. Sikap</h4><table class="table-rapot mb-6"><thead><tr><th>Predikat</th><th>Deskripsi</th></tr></thead><tbody><tr><td class="text-center">Sangat Baik</td><td>Ananda menunjukkan sikap spiritual dan sosial yang sangat baik, konsisten dalam menjalankan ibadah, serta memiliki kepedulian tinggi terhadap sesama.</td></tr></tbody></table>
                    <h4 class="font-bold text-sm mb-2">B. Pengetahuan dan Keterampilan</h4>
                    <table class="table-rapot mb-6">
                        <thead><tr><th class="w-8">No.</th><th>Mata Pelajaran</th><th class="w-20">Nilai Akhir</th><th>Capaian Kompetensi</th></tr></thead>
                        <tbody>
                            <?php if (!empty($nilai_kelompok)): ?>
                                <?php $kategori_mapel_order = ['Umum', 'Diniyah', 'Keterampilan']; $no_urut = 1; foreach($kategori_mapel_order as $kategori): if(isset($nilai_kelompok[$kategori])): ?>
                                <tr><td colspan="4" class="font-bold bg-gray-100"><?= htmlspecialchars($kategori) ?></td></tr>
                                <?php foreach($nilai_kelompok[$kategori] as $item): ?>
                                <tr><td class="text-center"><?= $no_urut++ ?></td><td><?= htmlspecialchars($item['mapel']) ?></td><td class="text-center font-bold"><?= $item['nilai'] ?></td><td class="text-xs"><?= getDeskripsiCapaian($item['nilai']) ?></td></tr>
                                <?php endforeach; endif; endforeach; ?>
                                <tr><td colspan="2" class="text-right font-bold">Jumlah Nilai</td><td class="text-center font-bold"><?= $summary['jumlah_nilai'] ?></td><td></td></tr>
                                <tr><td colspan="2" class="text-right font-bold">Rata-Rata Nilai</td><td class="text-center font-bold"><?= $summary['rata_rata'] ?></td><td></td></tr>
                                <tr><td colspan="2" class="text-right font-bold">Peringkat Kelas</td><td class="text-center font-bold"><?= $summary['peringkat'] ?> dari <?= $summary['total_siswa'] ?> siswa</td><td></td></tr>
                            <?php else: // JIKA DATA KOSONG, TAMPILKAN CONTOH FORMAT ?>
                                <tr><td colspan="4" class="font-bold bg-gray-100">Umum</td></tr>
                                <tr><td class="text-center">1</td><td>Matematika</td><td class="text-center font-bold">85</td><td class="text-xs">Ananda menunjukkan penguasaan yang baik pada seluruh kompetensi.</td></tr>
                                <tr><td class="text-center">2</td><td>Bahasa Indonesia</td><td class="text-center font-bold">92</td><td class="text-xs">Ananda menunjukkan penguasaan yang sangat baik pada seluruh kompetensi.</td></tr>
                                <tr><td colspan="4" class="font-bold bg-gray-100">Diniyah</td></tr>
                                <tr><td class="text-center">3</td><td>Fiqih Ibadah</td><td class="text-center font-bold">88</td><td class="text-xs">Ananda menunjukkan penguasaan yang baik pada seluruh kompetensi.</td></tr>
                                <tr><td class="text-center">4</td><td>Aqidah Akhlak</td><td class="text-center font-bold">90</td><td class="text-xs">Ananda menunjukkan penguasaan yang sangat baik pada seluruh kompetensi.</td></tr>
                                <tr><td colspan="2" class="text-right font-bold">Jumlah Nilai</td><td class="text-center font-bold">355</td><td></td></tr>
                                <tr><td colspan="2" class="text-right font-bold">Rata-Rata Nilai</td><td class="text-center font-bold">88.75</td><td></td></tr>
                                <tr><td colspan="2" class="text-right font-bold">Peringkat Kelas</td><td class="text-center font-bold">2 dari 25 siswa</td><td></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <h4 class="font-bold text-sm mb-2">C. Ekstrakurikuler</h4><table class="table-rapot mb-6"><thead><tr><th class="w-8">No.</th><th>Kegiatan Ekstrakurikuler</th><th>Keterangan</th></tr></thead><tbody><tr><td class="text-center">1</td><td>Panahan</td><td class="text-xs">Mengikuti kegiatan dengan sangat baik dan menunjukkan bakat yang menonjol.</td></tr><tr><td class="text-center">2</td><td>Pramuka</td><td class="text-xs">Aktif dalam setiap kegiatan kepramukaan.</td></tr></tbody></table>
                    <h4 class="font-bold text-sm mb-2">D. Ketidakhadiran</h4><table class="w-1/2 table-rapot mb-6"><tbody><tr><td class="w-2/3">Sakit</td><td>: 0 hari</td></tr><tr><td>Izin</td><td>: 1 hari</td></tr><tr><td>Tanpa Keterangan</td><td>: 0 hari</td></tr></tbody></table>
                    <h4 class="font-bold text-sm mb-2">E. Catatan Wali Kelas</h4><div class="border border-black p-3 text-sm min-h-[60px]">Alhamdulillah, Ananda menunjukkan perkembangan yang sangat positif pada semester ini. Pertahankan semangat belajar dan terus tingkatkan interaksi positif dengan teman-teman.</div>
                    <div class="flex justify-between mt-16 text-sm text-center"><div class="signature-box"><p>Mengetahui,</p><p>Orang Tua/Wali</p><br><br><br><p class="border-t border-black pt-1">(..............................)</p></div><div class="signature-box"><p>Kota Quran, <?= date('d F Y') ?></p><p>Wali Kelas</p><br><br><br><p class="border-t border-black pt-1"><b>Ust. Fulan, S.Pd.</b></p></div></div>
                    <div class="flex justify-center mt-8 text-sm text-center"><div class="signature-box"><p>Mengetahui,</p><p>Kepala Sekolah</p><br><br><br><p class="border-t border-black pt-1"><b>Ust. Abdullah, Lc.</b></p></div></div>
                </div>
            <?php elseif ($is_data_available_for_filter): ?>
                <div class="text-center py-16 text-gray-500 bg-white rounded-xl shadow-sm border no-print"><i class="fas fa-filter text-4xl mb-4 text-gray-300"></i><p class="font-medium">Silakan pilih Tahun Ajaran dan Semester di atas untuk melihat rapor.</p></div>
            <?php endif; ?>

            <?php endif; // End of check if $data_santri exists ?>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-santri');
            const openBtn = document.getElementById('open-sidebar-santri');
            const overlay = document.getElementById('sidebar-overlay-santri');
            if(openBtn) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
            if(overlay) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
        });
    </script>
</body>
</html>