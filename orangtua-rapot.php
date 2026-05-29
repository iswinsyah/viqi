<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$santri_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_menu = 'dashboard_orangtua';

// 1. Keamanan: Cek apakah santri ini milik orang tua yang login
if ($orangtua_id != 9999) {
    $check = $conn->query("SELECT id FROM buku_induk_santri WHERE id = $santri_id AND id_orangtua = $orangtua_id");
    if (!$check || $check->num_rows == 0) die("Akses Ditolak: Anda tidak memiliki otoritas melihat data santri ini.");
}

// 2. Ambil data santri
$res_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id");
$data_santri = $res_s->fetch_assoc();

// 3. Filter Tahun & Semester
$ta = $_GET['tahun_ajaran'] ?? '';
$sem = $_GET['semester'] ?? '';

// 4. Ambil Opsi Filter
$opsi_ta = $conn->query("SELECT DISTINCT tahun_ajaran FROM leger_nilai WHERE santri_id = $santri_id")->fetch_all(MYSQLI_ASSOC);
$opsi_sem = $conn->query("SELECT DISTINCT semester FROM leger_nilai WHERE santri_id = $santri_id")->fetch_all(MYSQLI_ASSOC);

// 5. Fetch Nilai (Hanya Kategori Diknas)
$nilai_data = [];
$summary = ['jumlah' => 0, 'rata' => 0];
if ($ta && $sem) {
    $sql = "SELECT l.*, m.nama_mapel FROM leger_nilai l 
            JOIN master_mapel m ON l.mapel_id = m.id 
            WHERE l.santri_id = $santri_id AND l.tahun_ajaran = '$ta' AND l.semester = '$sem' 
            AND m.kategori_mapel = 'Diknas' AND l.jenis_ujian = 'Ujian Akhir Semester (UAS)'";
    $res_n = $conn->query($sql);
    if ($res_n) while($r = $res_n->fetch_assoc()) $nilai_data[] = $r;
    
    if (count($nilai_data) > 0) {
        $summary['jumlah'] = array_sum(array_column($nilai_data, 'nilai'));
        $summary['rata'] = round($summary['jumlah'] / count($nilai_data), 2);
    }
}

function getGrade($n) { return $n >= 90 ? 'A' : ($n >= 80 ? 'B' : ($n >= 75 ? 'C' : 'D')); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Akademik | Ruang Ortu</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>@media print { .no-print { display: none !important; } body { background: white; } }</style>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden font-sans">
    <?php include 'sidebar-orangtua.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 no-print">
            <div class="flex items-center"><button id="open-sidebar-orangtua" class="md:hidden mr-4"><i class="fas fa-bars"></i></button><h2 class="font-bold">Rapor Akademik (Diknas)</h2></div>
            <a href="dashboard-orangtua.php" class="text-sm text-purple-600 font-bold"><i class="fas fa-arrow-left"></i> Kembali</a>
        </header>
        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Filter -->
                <div class="bg-white p-6 rounded-xl shadow-sm mb-6 no-print">
                    <form class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="id" value="<?= $santri_id ?>">
                        <div class="flex-1 min-w-[150px]"><label class="text-xs font-bold">Tahun Ajaran</label><select name="tahun_ajaran" class="w-full border rounded-lg p-2"><?php foreach($opsi_ta as $o) echo "<option value='{$o['tahun_ajaran']}' ".($ta==$o['tahun_ajaran']?'selected':'').">{$o['tahun_ajaran']}</option>"; ?></select></div>
                        <div class="flex-1 min-w-[150px]"><label class="text-xs font-bold">Semester</label><select name="semester" class="w-full border rounded-lg p-2"><?php foreach($opsi_sem as $o) echo "<option value='{$o['semester']}' ".($sem==$o['semester']?'selected':'').">{$o['semester']}</option>"; ?></select></div>
                        <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg font-bold">Tampilkan</button>
                        <button type="button" onclick="window.print()" class="bg-emerald-500 text-white px-4 py-2 rounded-lg font-bold"><i class="fas fa-print"></i></button>
                    </form>
                </div>

                <?php if ($ta && $sem): ?>
                <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-200">
                    <div class="text-center border-b-4 border-double border-black pb-4 mb-6">
                        <h2 class="text-xl font-bold">LAPORAN HASIL BELAJAR (AKADEMIK)</h2>
                        <h1 class="text-2xl font-black">VILLA QURAN INDONESIA</h1>
                    </div>
                    <div class="grid grid-cols-2 gap-4 text-sm mb-6">
                        <div><b>Nama Santri:</b> <?= htmlspecialchars($santri_nama) ?></div>
                        <div><b>Kelas:</b> <?= htmlspecialchars($data_santri['kelas_sekarang']) ?></div>
                        <div><b>NIS / NISN:</b> <?= $data_santri['nis'] ?> / <?= $data_santri['nisn'] ?></div>
                        <div><b>Semester:</b> <?= $sem ?> (<?= $ta ?>)</div>
                    </div>
                    <table class="w-full border-collapse border border-black text-sm">
                        <thead><tr class="bg-gray-100">
                            <th class="border border-black p-2">No</th>
                            <th class="border border-black p-2 text-left">Mata Pelajaran Umum</th>
                            <th class="border border-black p-2">Nilai</th>
                            <th class="border border-black p-2">Predikat</th>
                        </tr></thead>
                        <tbody>
                            <?php if(empty($nilai_data)): ?><tr><td colspan="4" class="p-8 text-center italic text-gray-400">Data nilai belum tersedia.</td></tr>
                            <?php else: $n=1; foreach($nilai_data as $v): ?>
                            <tr>
                                <td class="border border-black p-2 text-center"><?= $n++ ?></td>
                                <td class="border border-black p-2"><?= htmlspecialchars($v['nama_mapel']) ?></td>
                                <td class="border border-black p-2 text-center font-bold"><?= $v['nilai'] ?></td>
                                <td class="border border-black p-2 text-center"><?= getGrade($v['nilai']) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                            <tr class="bg-gray-50 font-bold">
                                <td colspan="2" class="border border-black p-2 text-right uppercase">Jumlah Nilai</td>
                                <td class="border border-black p-2 text-center"><?= $summary['jumlah'] ?></td>
                                <td class="border border-black p-2"></td>
                            </tr>
                            <tr class="bg-gray-50 font-bold">
                                <td colspan="2" class="border border-black p-2 text-right uppercase">Rata-Rata</td>
                                <td class="border border-black p-2 text-center text-purple-700"><?= $summary['rata'] ?></td>
                                <td class="border border-black p-2 text-center"><?= getGrade($summary['rata']) ?></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-12 flex justify-between text-center text-sm">
                        <div><p>Orang Tua/Wali</p><br><br><br><p>( ........................ )</p></div>
                        <div><p>Kota Quran, <?= date('d M Y') ?></p><p>Wali Kelas</p><br><br><br><p><b>Ustadz Pembimbing</b></p></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-20 bg-white rounded-xl text-gray-400"><i class="fas fa-filter text-5xl mb-4"></i><p>Silakan pilih periode rapor untuk melihat data.</p></div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>