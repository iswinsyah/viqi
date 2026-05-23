<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'leger_nilai';

// --- PERSIAPAN DATA UNTUK FILTER ---
$filters = [
    'kelas' => $_GET['kelas'] ?? '',
    'tahun_ajaran' => $_GET['tahun_ajaran'] ?? '',
    'semester' => $_GET['semester'] ?? '',
    'jenis_ujian' => $_GET['jenis_ujian'] ?? 'Ujian Akhir Semester (UAS)' // Default ke UAS
];

// Ambil opsi filter dari database
$opsi_kelas = $conn->query("SELECT DISTINCT kelas FROM leger_nilai ORDER BY kelas ASC")->fetch_all(MYSQLI_ASSOC);
$opsi_ta = $conn->query("SELECT DISTINCT tahun_ajaran FROM leger_nilai ORDER BY tahun_ajaran DESC")->fetch_all(MYSQLI_ASSOC);
$opsi_semester = $conn->query("SELECT DISTINCT semester FROM leger_nilai ORDER BY semester ASC")->fetch_all(MYSQLI_ASSOC);
$opsi_ujian = $conn->query("SELECT DISTINCT jenis_ujian FROM leger_nilai ORDER BY jenis_ujian ASC")->fetch_all(MYSQLI_ASSOC);

$data_leger = [];
$mapel_header = [];
$show_table = false;

// --- PROSES PENGAMBILAN & PENGOLAHAN DATA LEGER ---
if (!empty($filters['kelas']) && !empty($filters['tahun_ajaran']) && !empty($filters['semester']) && !empty($filters['jenis_ujian'])) {
    $show_table = true;

    // 1. Bangun query dengan filter
    $sql = "SELECT l.santri_id, s.nama_lengkap, l.mapel_id, m.nama_mapel, m.kategori_mapel, l.nilai
            FROM leger_nilai l
            JOIN buku_induk_santri s ON l.santri_id = s.id
            JOIN master_mapel m ON l.mapel_id = m.id
            WHERE l.kelas = ? AND l.tahun_ajaran = ? AND l.semester = ? AND l.jenis_ujian = ?
            ORDER BY s.nama_lengkap, m.kategori_mapel, m.nama_mapel";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $filters['kelas'], $filters['tahun_ajaran'], $filters['semester'], $filters['jenis_ujian']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // 2. Olah data mentah menjadi format pivot (matriks)
        while ($row = $result->fetch_assoc()) {
            $santri_id = $row['santri_id'];
            $mapel_id = $row['mapel_id'];

            if (!isset($data_leger[$santri_id])) {
                $data_leger[$santri_id] = [
                    'nama' => $row['nama_lengkap'],
                    'nilai' => [],
                    'jumlah' => 0,
                    'rata_rata' => 0
                ];
            }
            $data_leger[$santri_id]['nilai'][$mapel_id] = $row['nilai'];

            if (!isset($mapel_header[$mapel_id])) {
                $mapel_header[$mapel_id] = $row['nama_mapel'];
            }
        }

        // 3. Hitung Jumlah & Rata-rata
        foreach ($data_leger as $santri_id => &$santri) {
            $total_nilai = array_sum($santri['nilai']);
            $jumlah_mapel = count($santri['nilai']);
            $santri['jumlah'] = $total_nilai;
            $santri['rata_rata'] = $jumlah_mapel > 0 ? round($total_nilai / $jumlah_mapel, 2) : 0;
        }
        unset($santri); // Hapus referensi

        // 4. Urutkan berdasarkan rata-rata untuk menentukan peringkat
        uasort($data_leger, function($a, $b) {
            return $b['rata_rata'] <=> $a['rata_rata'];
        });
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leger Nilai | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book-reader text-purple-600 mr-2"></i>Leger Nilai Digital</h1></div>

            <!-- FORM FILTER -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas fa-filter mr-2"></i>Filter Tampilan Leger</h2></div>
                <form action="admin-leger.php" method="GET" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div><label class="text-sm font-medium">Kelas</label><select name="kelas" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><?php foreach($opsi_kelas as $o) echo "<option value='{$o['kelas']}' ".($filters['kelas']==$o['kelas']?'selected':'').">".htmlspecialchars($o['kelas'])."</option>"; ?></select></div>
                        <div><label class="text-sm font-medium">Tahun Ajaran</label><select name="tahun_ajaran" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><?php foreach($opsi_ta as $o) echo "<option value='{$o['tahun_ajaran']}' ".($filters['tahun_ajaran']==$o['tahun_ajaran']?'selected':'').">".htmlspecialchars($o['tahun_ajaran'])."</option>"; ?></select></div>
                        <div><label class="text-sm font-medium">Semester</label><select name="semester" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><?php foreach($opsi_semester as $o) echo "<option value='{$o['semester']}' ".($filters['semester']==$o['semester']?'selected':'').">".htmlspecialchars($o['semester'])."</option>"; ?></select></div>
                        <div class="lg:col-span-2"><label class="text-sm font-medium">Jenis Ujian</label><select name="jenis_ujian" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><?php foreach($opsi_ujian as $o) echo "<option value='{$o['jenis_ujian']}' ".($filters['jenis_ujian']==$o['jenis_ujian']?'selected':'').">".htmlspecialchars($o['jenis_ujian'])."</option>"; ?></select></div>
                    </div>
                    <div class="mt-4 text-right"><button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-eye mr-2"></i> Tampilkan Leger</button></div>
                </form>
            </div>

            <!-- HASIL LEGER -->
            <?php if ($show_table): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800">Hasil Leger: Kelas <?= htmlspecialchars($filters['kelas']) ?> - <?= htmlspecialchars($filters['semester']) ?> <?= htmlspecialchars($filters['tahun_ajaran']) ?></h2>
                    <button onclick="window.print()" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                        <i class="fas fa-print mr-2"></i> Cetak
                    </button>
                </div>
                <div class="overflow-x-auto p-4">
                    <?php if (!empty($data_leger)): ?>
                    <table class="min-w-full divide-y divide-gray-200 border border-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr class="text-left text-xs font-bold text-gray-600 uppercase">
                                <th class="px-3 py-3 border-r">Peringkat</th>
                                <th class="px-3 py-3 border-r">Nama Santri</th>
                                <?php foreach ($mapel_header as $nama_mapel): ?>
                                    <th class="px-3 py-3 border-r whitespace-nowrap"><?= htmlspecialchars($nama_mapel) ?></th>
                                <?php endforeach; ?>
                                <th class="px-3 py-3 border-r bg-gray-200">Jumlah</th>
                                <th class="px-3 py-3 border-r bg-gray-200">Rata-Rata</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php $peringkat = 1; foreach ($data_leger as $santri): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 border-r text-center font-bold"><?= $peringkat++ ?></td>
                                <td class="px-3 py-2 border-r font-medium text-gray-900 whitespace-nowrap"><?= htmlspecialchars($santri['nama']) ?></td>
                                <?php foreach ($mapel_header as $mapel_id => $nama_mapel): 
                                    $nilai = $santri['nilai'][$mapel_id] ?? '-';
                                    $warna = is_numeric($nilai) ? ($nilai >= 75 ? 'text-emerald-700' : 'text-red-600') : 'text-gray-400';
                                ?>
                                    <td class="px-3 py-2 border-r text-center font-bold <?= $warna ?>"><?= $nilai ?></td>
                                <?php endforeach; ?>
                                <td class="px-3 py-2 border-r text-center font-bold bg-gray-50"><?= $santri['jumlah'] ?></td>
                                <td class="px-3 py-2 border-r text-center font-bold bg-gray-50 <?= $santri['rata_rata'] >= 75 ? 'text-emerald-700' : 'text-red-600' ?>"><?= $santri['rata_rata'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-folder-open text-4xl mb-4"></i>
                            <p class="font-medium">Data tidak ditemukan.</p>
                            <p class="text-sm">Pastikan filter yang Anda pilih sudah benar dan data nilai sudah diinput untuk kombinasi tersebut.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
                <div class="text-center py-16 text-gray-500 bg-white rounded-xl shadow-sm border">
                    <i class="fas fa-filter text-4xl mb-4 text-gray-300"></i>
                    <p class="font-medium">Silakan pilih filter di atas untuk menampilkan data leger.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
    document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
        document.getElementById('sidebar-hr').classList.toggle('hidden'); 
        document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
    });
    </script>
</body>
</html>

<style>
@media print {
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    #sidebar-hr, header, form, .no-print {
        display: none !important;
    }
    main {
        padding: 0 !important;
        margin: 0 !important;
        overflow: visible !important;
    }
    .overflow-x-auto {
        overflow: visible !important;
    }
    table {
        font-size: 10px;
    }
    th, td {
        padding: 4px 6px !important;
    }
}
</style>

```

#### 2. Tambahkan Menu Leger di Sidebar

Sekarang, buka file `sidebar-hr.php` dan tambahkan satu baris kode menu baru untuk halaman leger ini. Letakkan di bawah menu "Bank Nilai".

```html
<!-- Tambahkan baris ini di dalam file sidebar-hr.php -->
<a href="admin-leger.php" class="<?= $active_menu == 'leger_nilai' ? $active_class : $inactive_class ?>">
    <i class="fas fa-book-reader w-6"></i> Leger Nilai Digital
</a>
```

### Penjelasan Fitur Halaman Leger

1.  **Filter Lengkap:** Anda bisa memfilter tampilan leger berdasarkan **Kelas**, **Tahun Ajaran**, **Semester**, dan **Jenis Ujian**.
2.  **Tabel Pivot Otomatis:** Kode ini secara cerdas mengubah data nilai yang tadinya vertikal menjadi tabel horizontal (matriks), di mana baris adalah nama santri dan kolom adalah mata pelajaran.
3.  **Kalkulasi & Peringkat:** Sistem akan otomatis menghitung **Jumlah Nilai**, **Nilai Rata-Rata**, dan yang terpenting, **mengurutkan santri berdasarkan peringkat** nilai rata-rata tertinggi.
4.  **Cetak Laporan:** Terdapat tombol "Cetak" yang akan membuka dialog print dan secara otomatis menyembunyikan elemen-elemen yang tidak perlu (seperti sidebar dan form filter) untuk hasil cetak yang bersih.
5.  **Penanganan Data Kosong:** Jika data tidak ditemukan sesuai filter, halaman akan menampilkan pesan yang informatif, bukan halaman kosong atau error.

Sekarang, Anda memiliki sistem pelaporan nilai yang sangat kuat, bos. Silakan jalankan `git push` untuk mendeploy perubahan ini.

<!--
[PROMPT_SUGGESTION]Buatkan halaman Rapor Digital per santri yang bisa dicetak.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Bagaimana cara memindahkan data dari tabel `bank_nilai` yang lama ke `leger_nilai` yang baru?[/PROMPT_SUGGESTION]
-->