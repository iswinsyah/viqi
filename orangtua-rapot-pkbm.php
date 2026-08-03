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
    while ($r = $res_s->fetch_assoc()) $santri_anak[] = $r;
}

$selected_santri_id = isset($_GET['santri_id']) ? (int)$_GET['santri_id'] : ($santri_anak[0]['id'] ?? 0);

// Selected santri info
$data_santri = null;
if ($selected_santri_id > 0) {
    $res_sel = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $selected_santri_id");
    if ($res_sel) $data_santri = $res_sel->fetch_assoc();
}

$kelas_santri = $data_santri['kelas_sekarang'] ?? 'Paket B';
$paket_tipe = 'Paket B';
if (
    str_contains(strtolower($kelas_santri), 'paket c') || 
    str_contains(strtolower($kelas_santri), 'sma') || 
    preg_match('/\b(10|11|12|x|xi|xii)\b/i', strtolower($kelas_santri))
) {
    $paket_tipe = 'Paket C';
}

$filters = [
    'tahun_ajaran' => $_GET['tahun_ajaran'] ?? '',
    'semester' => $_GET['semester'] ?? 'Ganjil'
];

$opsi_ta = [];
if ($selected_santri_id > 0) {
    $res_ta = $conn->query("SELECT DISTINCT tahun_ajaran FROM leger_nilai WHERE santri_id = $selected_santri_id ORDER BY tahun_ajaran DESC");
    if ($res_ta && $res_ta->num_rows > 0) {
        while ($r = $res_ta->fetch_assoc()) $opsi_ta[] = $r['tahun_ajaran'];
    }
}
if (empty($opsi_ta)) {
    $opsi_ta = [date('Y') . '/' . (date('Y') + 1), (date('Y') - 1) . '/' . date('Y')];
}

if (empty($filters['tahun_ajaran']) && !empty($opsi_ta)) {
    $filters['tahun_ajaran'] = $opsi_ta[0];
}

// Fetch grades
$nilai_mapel = [];
if ($selected_santri_id > 0 && !empty($filters['tahun_ajaran'])) {
    $ta_esc = $conn->real_escape_string($filters['tahun_ajaran']);
    $sem_esc = $conn->real_escape_string($filters['semester']);

    $sql_nilai = "
        SELECT l.*, m.nama_mapel, m.kode_mapel 
        FROM leger_nilai l 
        JOIN master_mapel m ON l.mapel_id = m.id 
        WHERE l.santri_id = $selected_santri_id AND l.tahun_ajaran = '$ta_esc' AND l.semester = '$sem_esc' 
        ORDER BY m.kategori_mapel ASC, m.nama_mapel ASC
    ";
    $res_n = $conn->query($sql_nilai);
    if ($res_n) {
        while ($r = $res_n->fetch_assoc()) $nilai_mapel[] = $r;
    }
}

// Fetch Catatan
$catatan_data = null;
if ($selected_santri_id > 0 && !empty($filters['tahun_ajaran'])) {
    $ta_esc = $conn->real_escape_string($filters['tahun_ajaran']);
    $sem_esc = $conn->real_escape_string($filters['semester']);

    $res_c = $conn->query("SELECT * FROM raport_pkbm_catatan WHERE santri_id = $selected_santri_id AND tahun_ajaran = '$ta_esc' AND semester = '$sem_esc'");
    if ($res_c && $res_c->num_rows > 0) {
        $catatan_data = $res_c->fetch_assoc();
    }
}

function hitung_predikat($nilai) {
    if ($nilai >= 88) return ['predikat' => 'A'];
    if ($nilai >= 78) return ['predikat' => 'B'];
    if ($nilai >= 67) return ['predikat' => 'C'];
    return ['predikat' => 'D'];
}

$active_menu = 'orangtua_rapot_pkbm';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Diknas PKBM Ananda | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; p: 0 !important; }
            .print-area { border: none !important; shadow: none !important; p: 0 !important; width: 100% !important; }
            @page { size: A4; margin: 15mm; }
        }
    </style>
</head>
<body class="bg-purple-50/30 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR ORANGTUA -->
    <div class="no-print">
        <?php include 'sidebar-orangtua.php'; ?>
    </div>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-purple-100 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
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
                <button onclick="window.print()" class="bg-purple-800 hover:bg-purple-900 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-md transition flex items-center gap-2">
                    <i class="fas fa-print"></i> Cetak Raport PDF (A4)
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-purple-50/20 p-4 sm:p-6 lg:p-8">
            
            <!-- FILTER BAR -->
            <div class="bg-white p-5 rounded-2xl border border-purple-100 shadow-sm mb-6 no-print max-w-4xl mx-auto">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <?php if (count($santri_anak) > 1): ?>
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-purple-900 mb-1.5">Pilih Ananda</label>
                            <select name="santri_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-purple-200 rounded-xl text-xs bg-white font-bold text-purple-950">
                                <?php foreach ($santri_anak as $sa): ?>
                                    <option value="<?= $sa['id'] ?>" <?= ($sa['id'] == $selected_santri_id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sa['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tahun Ajaran</label>
                        <select name="tahun_ajaran" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <?php foreach ($opsi_ta as $ta): ?>
                                <option value="<?= htmlspecialchars($ta) ?>" <?= ($filters['tahun_ajaran'] === $ta) ? 'selected' : '' ?>><?= htmlspecialchars($ta) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Semester</label>
                        <select name="semester" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <option value="Ganjil" <?= ($filters['semester'] === 'Ganjil') ? 'selected' : '' ?>>Ganjil (1)</option>
                            <option value="Genap" <?= ($filters['semester'] === 'Genap') ? 'selected' : '' ?>>Genap (2)</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- PRINT AREA / RAPORT SHEET -->
            <?php if ($data_santri): ?>
                <div class="print-area bg-white p-8 sm:p-12 rounded-2xl border border-slate-200/90 shadow-lg max-w-4xl mx-auto text-slate-900 text-xs sm:text-sm leading-relaxed">
                    
                    <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
                        <h2 class="font-extrabold text-base sm:text-lg uppercase tracking-wider text-slate-900">SATUAN PENDIDIKAN KESETARAAN (PKBM)</h2>
                        <h1 class="text-xl sm:text-2xl font-black uppercase text-purple-900 tracking-tight my-0.5">PKBM VILLA QUR'AN INDONESIA</h1>
                        <p class="text-xs text-slate-600 italic">Izin Operasional Dinas Pendidikan & Kebudayaan | Terakreditasi Standar Nasional</p>
                        <div class="mt-2 inline-block bg-slate-900 text-white font-extrabold px-4 py-1 rounded-full text-xs uppercase tracking-widest">
                            RAPOR PENDIDIKAN KESETARAAN <?= strtoupper($paket_tipe) ?> (<?= ($paket_tipe === 'Paket C') ? 'SETARA SMA' : 'SETARA SMP' ?>)
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6 bg-slate-50/80 p-4 rounded-xl border border-slate-200 text-xs">
                        <div class="space-y-1.5">
                            <div class="flex"><span class="w-32 font-bold text-slate-600">Nama Ananda:</span> <span class="font-black text-slate-900 uppercase"><?= htmlspecialchars($data_santri['nama_lengkap']) ?></span></div>
                            <div class="flex"><span class="w-32 font-bold text-slate-600">NIS / NISN:</span> <span class="font-mono font-bold"><?= htmlspecialchars($data_santri['nis'] ?? '-') ?> / <?= htmlspecialchars($data_santri['nisn'] ?? '-') ?></span></div>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex"><span class="w-32 font-bold text-slate-600">Kelas / Tingkat:</span> <span class="font-bold text-purple-900"><?= htmlspecialchars($data_santri['kelas_sekarang']) ?> (<?= $paket_tipe ?>)</span></div>
                            <div class="flex"><span class="w-32 font-bold text-slate-600">Semester / TA:</span> <span class="font-bold"><?= htmlspecialchars($filters['semester']) ?> / <?= htmlspecialchars($filters['tahun_ajaran']) ?></span></div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="font-extrabold text-sm uppercase text-slate-900 mb-3 border-b-2 border-purple-800 pb-1">A. Capaian Hasil Belajar (Nilai Akademik Diknas)</h3>
                        <table class="w-full border-collapse border border-slate-300 text-xs">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="py-2.5 px-3 border border-slate-300 text-center w-10">No</th>
                                    <th class="py-2.5 px-4 border border-slate-300 text-left">Mata Pelajaran</th>
                                    <th class="py-2.5 px-3 border border-slate-300 text-center w-20">Nilai Akhir</th>
                                    <th class="py-2.5 px-3 border border-slate-300 text-center w-16">Predikat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($nilai_mapel)): ?>
                                    <?php $no = 1; foreach ($nilai_mapel as $nm): $val = (float)$nm['nilai']; $p_info = hitung_predikat($val); ?>
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-2 px-3 border border-slate-300 text-center font-bold"><?= $no++ ?></td>
                                            <td class="py-2 px-4 border border-slate-300 font-semibold"><?= htmlspecialchars($nm['nama_mapel']) ?></td>
                                            <td class="py-2 px-3 border border-slate-300 text-center font-black text-sm"><?= number_format($val, 0) ?></td>
                                            <td class="py-2 px-3 border border-slate-300 text-center font-bold"><?= $p_info['predikat'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="py-6 text-center text-slate-400 italic">Belum ada nilai akademik yang diterbitkan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($catatan_data['catatan_wali_kelas'])): ?>
                        <div class="mb-6 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs italic">
                            <b>Catatan Wali Kelas / Pembina:</b> "<?= htmlspecialchars($catatan_data['catatan_wali_kelas']) ?>"
                        </div>
                    <?php endif; ?>

                </div>
            <?php endif; ?>

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
