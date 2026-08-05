<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Self-healing database tables
$conn->query("CREATE TABLE IF NOT EXISTS raport_pkbm_catatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    tahun_ajaran VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    sakit INT DEFAULT 0,
    izin INT DEFAULT 0,
    alpa INT DEFAULT 0,
    ekstrakurikuler TEXT NULL,
    catatan_wali_kelas TEXT NULL,
    tanggal_cetak DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_raport_santri (santri_id, kelas, tahun_ajaran, semester)
)");

// Check user role for binaan scoping
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$is_super_admin = ($ustadz_id === 9999);

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_admin_or_kepala = $is_super_admin || !empty(array_intersect($norm_roles, ['super_admin', 'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah']));
$is_ka_rijal = !empty(array_intersect($norm_roles, ['kepala_asrama', 'kepala_asrama_rijal']));
$is_ka_nisa = !empty(array_intersect($norm_roles, ['kepala_asrama_nisa']));

// Fetch santri binaan IDs if scoped
$santri_binaan_ids = [];
if (!$is_admin_or_kepala) {
    if ($is_ka_rijal) {
        $res_sb = $conn->query("SELECT id FROM buku_induk_santri WHERE status_santri = 'Aktif' AND (jenis_kelamin = 'Laki-laki' OR jenis_kelamin IS NULL)");
        if ($res_sb) {
            while ($r = $res_sb->fetch_assoc()) $santri_binaan_ids[] = $r['id'];
        }
    } elseif ($is_ka_nisa) {
        $res_sb = $conn->query("SELECT id FROM buku_induk_santri WHERE status_santri = 'Aktif' AND jenis_kelamin = 'Perempuan'");
        if ($res_sb) {
            while ($r = $res_sb->fetch_assoc()) $santri_binaan_ids[] = $r['id'];
        }
    } else {
        // Musyrif / Musyrifah
        $res_sb = $conn->query("
            SELECT DISTINCT s.id 
            FROM buku_induk_santri s 
            JOIN halaqoh_anggota a ON s.id = a.santri_id 
            JOIN halaqoh_grup g ON a.grup_id = g.id 
            WHERE g.musyrif_id = $ustadz_id AND s.status_santri = 'Aktif'
        ");
        if ($res_sb) {
            while ($r = $res_sb->fetch_assoc()) $santri_binaan_ids[] = $r['id'];
        }
    }
}

// Fetch filter options (Kelas)
$opsi_kelas = [];
if ($is_admin_or_kepala) {
    $res_k = $conn->query("SELECT DISTINCT kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' AND kelas_sekarang IS NOT NULL AND kelas_sekarang != '' ORDER BY kelas_sekarang ASC");
} else {
    if (!empty($santri_binaan_ids)) {
        $sb_str = implode(',', $santri_binaan_ids);
        $res_k = $conn->query("SELECT DISTINCT kelas_sekarang FROM buku_induk_santri WHERE id IN ($sb_str) AND status_santri = 'Aktif' AND kelas_sekarang IS NOT NULL AND kelas_sekarang != '' ORDER BY kelas_sekarang ASC");
    } else {
        $res_k = false;
    }
}
if ($res_k) {
    while ($r = $res_k->fetch_assoc()) $opsi_kelas[] = $r['kelas_sekarang'];
}

$opsi_ta = [];
$res_ta = $conn->query("SELECT DISTINCT tahun_ajaran FROM leger_nilai ORDER BY tahun_ajaran DESC");
if ($res_ta && $res_ta->num_rows > 0) {
    while ($r = $res_ta->fetch_assoc()) $opsi_ta[] = $r['tahun_ajaran'];
} else {
    $opsi_ta = [date('Y') . '/' . (date('Y') + 1), (date('Y') - 1) . '/' . date('Y')];
}

$mode = $_GET['mode'] ?? 'lembaran';

$filters = [
    'santri_id' => isset($_GET['santri_id']) ? (int)$_GET['santri_id'] : 0,
    'kelas' => $_GET['kelas'] ?? ($opsi_kelas[0] ?? ''),
    'tahun_ajaran' => $_GET['tahun_ajaran'] ?? ($opsi_ta[0] ?? ''),
    'semester' => $_GET['semester'] ?? 'Ganjil',
    'paket_type' => $_GET['paket_type'] ?? 'auto'
];

// Handle Save Catatan & Kehadiran Form
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_catatan_rapot'])) {
    $santri_id_post = (int)$_POST['santri_id'];
    $kelas_post = $conn->real_escape_string($_POST['kelas']);
    $ta_post = $conn->real_escape_string($_POST['tahun_ajaran']);
    $sem_post = $conn->real_escape_string($_POST['semester']);
    $sakit = (int)$_POST['sakit'];
    $izin = (int)$_POST['izin'];
    $alpa = (int)$_POST['alpa'];
    $ekstra = $conn->real_escape_string($_POST['ekstrakurikuler']);
    $catatan = $conn->real_escape_string($_POST['catatan_wali_kelas']);
    $tgl_cetak = !empty($_POST['tanggal_cetak']) ? "'" . $conn->real_escape_string($_POST['tanggal_cetak']) . "'" : "NULL";

    $sql_c = "INSERT INTO raport_pkbm_catatan (santri_id, kelas, tahun_ajaran, semester, sakit, izin, alpa, ekstrakurikuler, catatan_wali_kelas, tanggal_cetak)
              VALUES ($santri_id_post, '$kelas_post', '$ta_post', '$sem_post', $sakit, $izin, $alpa, '$ekstra', '$catatan', $tgl_cetak)
              ON DUPLICATE KEY UPDATE sakit=$sakit, izin=$izin, alpa=$alpa, ekstrakurikuler='$ekstra', catatan_wali_kelas='$catatan', tanggal_cetak=$tgl_cetak";
    
    if ($conn->query($sql_c)) {
        $pesan_sukses = "Catatan & Kehadiran Raport Diknas berhasil disimpan!";
    } else {
        $pesan_error = "Gagal menyimpan catatan: " . $conn->error;
    }
}

// Fetch list of santri based on filter kelas
$santri_list = [];
if (!empty($filters['kelas'])) {
    $k_esc = $conn->real_escape_string($filters['kelas']);
    if ($is_admin_or_kepala) {
        $res_s = $conn->query("SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, jenis_kelamin FROM buku_induk_santri WHERE kelas_sekarang = '$k_esc' ORDER BY nama_lengkap ASC");
    } else {
        if (!empty($santri_binaan_ids)) {
            $sb_str = implode(',', $santri_binaan_ids);
            $res_s = $conn->query("SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, jenis_kelamin FROM buku_induk_santri WHERE kelas_sekarang = '$k_esc' AND id IN ($sb_str) ORDER BY nama_lengkap ASC");
        } else {
            $res_s = false;
        }
    }
    if ($res_s) {
        while ($r = $res_s->fetch_assoc()) $santri_list[] = $r;
    }
}

if ($filters['santri_id'] == 0 && !empty($santri_list)) {
    $filters['santri_id'] = $santri_list[0]['id'];
}

// Fetch Rekap Data for Tab 1 (Rekapitulasi Leger Binaanku)
$rekap_binaan = [];
if (!empty($santri_list)) {
    $ta_esc = $conn->real_escape_string($filters['tahun_ajaran']);
    $sem_esc = $conn->real_escape_string($filters['semester']);
    
    foreach ($santri_list as $st) {
        $st_id = (int)$st['id'];
        $res_r = $conn->query("
            SELECT 
                ROUND(AVG(l.nilai), 1) as avg_nilai,
                MAX(l.nilai) as max_nilai,
                MIN(l.nilai) as min_nilai,
                COUNT(DISTINCT l.mapel_id) as total_mapel
            FROM leger_nilai l
            WHERE l.santri_id = $st_id AND l.tahun_ajaran = '$ta_esc' AND l.semester = '$sem_esc'
        ");
        $stat = ($res_r && $res_r->num_rows > 0) ? $res_r->fetch_assoc() : null;
        
        $rekap_binaan[] = [
            'id' => $st['id'],
            'nama_lengkap' => $st['nama_lengkap'],
            'nis' => $st['nis'],
            'jenis_kelamin' => $st['jenis_kelamin'],
            'kelas_sekarang' => $st['kelas_sekarang'],
            'avg_nilai' => $stat['avg_nilai'] ?? 0,
            'max_nilai' => $stat['max_nilai'] ?? 0,
            'min_nilai' => $stat['min_nilai'] ?? 0,
            'total_mapel' => $stat['total_mapel'] ?? 0,
        ];
    }
}

// Data Selected Santri for Tab 2
$selected_santri = null;
if ($filters['santri_id'] > 0) {
    // Security check for non-admin
    if (!$is_admin_or_kepala && !in_array($filters['santri_id'], $santri_binaan_ids)) {
        $selected_santri = null;
        $pesan_error = "Akses Ditolak: Anda hanya dapat mengakses Raport Diknas untuk santri binaan Anda di Manajemen Halaqoh.";
    } else {
        $res_sel = $conn->query("SELECT * FROM buku_induk_santri WHERE id = " . $filters['santri_id']);
        if ($res_sel) $selected_santri = $res_sel->fetch_assoc();
    }
}

// Determine Paket B vs Paket C
$paket_tipe = 'Paket B';
$kelas_check = strtolower($selected_santri['kelas_sekarang'] ?? $filters['kelas']);

if ($filters['paket_type'] === 'Paket C') {
    $paket_tipe = 'Paket C';
} elseif ($filters['paket_type'] === 'Paket B') {
    $paket_tipe = 'Paket B';
} else {
    if (
        str_contains($kelas_check, 'paket c') || 
        str_contains($kelas_check, 'sma') || 
        preg_match('/\b(10|11|12|x|xi|xii)\b/i', $kelas_check)
    ) {
        $paket_tipe = 'Paket C';
    } else {
        $paket_tipe = 'Paket B';
    }
}

// Fetch Grades for Selected Santri (Real-time Average per Subject)
$nilai_mapel = [];
if ($selected_santri) {
    $s_id = (int)$selected_santri['id'];
    $ta_esc = $conn->real_escape_string($filters['tahun_ajaran']);
    $sem_esc = $conn->real_escape_string($filters['semester']);

    $sql_nilai = "
        SELECT m.id as mapel_id, m.nama_mapel, m.kode_mapel, m.kategori_mapel, 
               ROUND(AVG(l.nilai), 0) as nilai
        FROM leger_nilai l 
        JOIN master_mapel m ON l.mapel_id = m.id 
        WHERE l.santri_id = $s_id AND l.tahun_ajaran = '$ta_esc' AND l.semester = '$sem_esc' 
        GROUP BY m.id
        ORDER BY m.kategori_mapel ASC, m.nama_mapel ASC
    ";
    $res_n = $conn->query($sql_nilai);
    if ($res_n) {
        while ($r = $res_n->fetch_assoc()) {
            $nilai_mapel[] = $r;
        }
    }
}

// Fetch Raport Catatan & Kehadiran
$catatan_data = null;
if ($selected_santri) {
    $s_id = (int)$selected_santri['id'];
    $ta_esc = $conn->real_escape_string($filters['tahun_ajaran']);
    $sem_esc = $conn->real_escape_string($filters['semester']);

    $res_c = $conn->query("SELECT * FROM raport_pkbm_catatan WHERE santri_id = $s_id AND tahun_ajaran = '$ta_esc' AND semester = '$sem_esc'");
    if ($res_c && $res_c->num_rows > 0) {
        $catatan_data = $res_c->fetch_assoc();
    }
}

// Helper Predikat & Deskripsi Capaian
function hitung_predikat($nilai) {
    if ($nilai >= 88) return ['predikat' => 'A', 'label' => 'Sangat Baik', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300'];
    if ($nilai >= 78) return ['predikat' => 'B', 'label' => 'Baik', 'badge' => 'bg-teal-100 text-teal-800 border-teal-300'];
    if ($nilai >= 67) return ['predikat' => 'C', 'label' => 'Cukup', 'badge' => 'bg-amber-100 text-amber-800 border-amber-300'];
    return ['predikat' => 'D', 'label' => 'Perlu Bimbingan', 'badge' => 'bg-rose-100 text-rose-800 border-rose-300'];
}

function buat_deskripsi_capaian($nama_mapel, $nilai) {
    $p = hitung_predikat($nilai)['predikat'];
    if ($p === 'A') {
        return "Menunjukkan penguasaan yang sangat baik dan konsisten dalam memahami seluruh materi pokok " . $nama_mapel . ".";
    } elseif ($p === 'B') {
        return "Menunjukkan penguasaan yang baik dalam mencapai tujuan pembelajaran " . $nama_mapel . ".";
    } elseif ($p === 'C') {
        return "Menunjukkan penguasaan yang cukup dalam kompetensi dasar " . $nama_mapel . ", perlu ditingkatkan latihan mandiri.";
    } else {
        return "Memerlukan bimbingan tambahan dan intervensi khusus untuk peningkatan capaian " . $nama_mapel . ".";
    }
}

$active_menu = 'rapot_pkbm';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raport Diknas PKBM <?= $paket_tipe ?> | SADIGS 4.0</title>
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
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR -->
    <div class="no-print">
        <?php include 'sidebar-hr.php'; ?>
    </div>

    <!-- MAIN BODY -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-file-alt mr-1"></i>Diknas PKBM
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <?php if ($mode === 'lembaran' && $selected_santri): ?>
                    <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-md transition flex items-center gap-2">
                        <i class="fas fa-print"></i> Cetak Raport PDF (A4)
                    </button>
                <?php endif; ?>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 lg:p-8">
            
            <!-- FILTER BAR -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-6 no-print">
                <form method="GET" id="filter_form" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 items-end">
                    <input type="hidden" name="mode" id="mode_input" value="<?= htmlspecialchars($mode) ?>">

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Kelas / Tingkat</label>
                        <select name="kelas" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <?php foreach ($opsi_kelas as $k): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= ($filters['kelas'] === $k) ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Santri (Lembaran)</label>
                        <select name="santri_id" onchange="document.getElementById('mode_input').value='lembaran'; this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <?php foreach ($santri_list as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($filters['santri_id'] == $s['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_lengkap']) ?> (<?= htmlspecialchars($s['jenis_kelamin'] ?? '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tipe Format Raport</label>
                        <select name="paket_type" onchange="this.form.submit()" class="w-full px-3 py-2 border border-blue-300 bg-blue-50/50 rounded-xl text-xs font-bold text-blue-900">
                            <option value="auto" <?= ($filters['paket_type'] === 'auto') ? 'selected' : '' ?>>✨ Auto (Berdasarkan Kelas)</option>
                            <option value="Paket B" <?= ($filters['paket_type'] === 'Paket B') ? 'selected' : '' ?>>📘 Paket B (Setara SMP / Kelas 7-9)</option>
                            <option value="Paket C" <?= ($filters['paket_type'] === 'Paket C') ? 'selected' : '' ?>>📙 Paket C (Setara SMA / Kelas 10-12)</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- MODE / TAB NAVIGATION BUTTONS -->
            <div class="flex items-center gap-3 mb-6 no-print">
                <a href="?mode=rekap&kelas=<?= urlencode($filters['kelas']) ?>&tahun_ajaran=<?= urlencode($filters['tahun_ajaran']) ?>&semester=<?= urlencode($filters['semester']) ?>" 
                   class="px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-2 <?= ($mode === 'rekap') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-blue-200' : 'bg-white text-slate-700 border hover:bg-slate-50' ?>">
                    <i class="fas fa-table text-sm"></i>
                    <span>Tab 1: Rekapitulasi Nilai Binaanku (Leger Matriks)</span>
                </a>
                <a href="?mode=lembaran&santri_id=<?= $filters['santri_id'] ?>&kelas=<?= urlencode($filters['kelas']) ?>&tahun_ajaran=<?= urlencode($filters['tahun_ajaran']) ?>&semester=<?= urlencode($filters['semester']) ?>" 
                   class="px-5 py-2.5 rounded-xl font-bold text-xs shadow-sm transition-all flex items-center gap-2 <?= ($mode === 'lembaran') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-emerald-200' : 'bg-white text-slate-700 border hover:bg-slate-50' ?>">
                    <i class="fas fa-file-pdf text-sm"></i>
                    <span>Tab 2: Lembar Raport Utuh (Siap Cetak A4)</span>
                </a>
            </div>

            <?= $pesan_sukses ? "<div class='no-print bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-check-circle mr-2 text-emerald-500'></i>$pesan_sukses</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>
            <?= $pesan_error ? "<div class='no-print bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-exclamation-triangle mr-2 text-rose-500'></i>$pesan_error</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>

            <?php if ($mode === 'rekap'): ?>
                <!-- TAB 1: REKAPITULASI NILAI BINAAN (LEGER MATRIKS) -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8 no-print">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h2 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                                <i class="fas fa-chart-line text-blue-600"></i>
                                <span>Rekapitulasi Hasil Belajar Santri Binaan (Real-Time Leger)</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Kelas: <b><?= htmlspecialchars($filters['kelas']) ?></b> | TA: <b><?= htmlspecialchars($filters['tahun_ajaran']) ?></b> (<?= htmlspecialchars($filters['semester']) ?>)</p>
                        </div>
                        <input type="text" id="search_rekap" onkeyup="filterRekapTable()" placeholder="Cari nama santri..." class="px-3.5 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 w-full sm:w-60">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="rekap_table">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 uppercase text-[11px] font-extrabold tracking-wider">
                                    <th class="py-3 px-4 text-center w-10">No</th>
                                    <th class="py-3 px-4">Nama Santri</th>
                                    <th class="py-3 px-4">NIS</th>
                                    <th class="py-3 px-4 text-center">Rata-Rata Nilai</th>
                                    <th class="py-3 px-4 text-center">Nilai Tertinggi</th>
                                    <th class="py-3 px-4 text-center">Nilai Terendah</th>
                                    <th class="py-3 px-4 text-center">Status Bimbingan</th>
                                    <th class="py-3 px-4 text-center">Aksi Raport</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                                <?php if (!empty($rekap_binaan)): ?>
                                    <?php $no = 1; foreach ($rekap_binaan as $rb): ?>
                                        <?php 
                                        $avg = (float)$rb['avg_nilai']; 
                                        $status_badge = '<span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-xs font-bold"><i class="fas fa-check-circle mr-1"></i>Sangat Baik</span>';
                                        if ($avg < 70) {
                                            $status_badge = '<span class="bg-rose-100 text-rose-800 px-2.5 py-1 rounded-full text-xs font-extrabold animate-pulse"><i class="fas fa-exclamation-triangle mr-1"></i>⚠️ Butuh Bimbingan</span>';
                                        } elseif ($avg < 78) {
                                            $status_badge = '<span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-xs font-bold"><i class="fas fa-info-circle mr-1"></i>Cukup</span>';
                                        }
                                        ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3.5 px-4 text-center font-bold text-slate-500"><?= $no++ ?></td>
                                            <td class="py-3.5 px-4">
                                                <div class="font-bold text-slate-900 flex items-center gap-2">
                                                    <span><?= htmlspecialchars($rb['nama_lengkap']) ?></span>
                                                    <?php if ($rb['jenis_kelamin'] === 'Laki-laki'): ?>
                                                        <span class="bg-blue-100 text-blue-800 text-[10px] px-2 py-0.5 rounded-full font-extrabold"><i class="fas fa-mars mr-1"></i>Laki-laki</span>
                                                    <?php else: ?>
                                                        <span class="bg-pink-100 text-pink-800 text-[10px] px-2 py-0.5 rounded-full font-extrabold"><i class="fas fa-venus mr-1"></i>Perempuan</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-4 font-mono font-semibold text-slate-600"><?= htmlspecialchars($rb['nis'] ?? '-') ?></td>
                                            <td class="py-3.5 px-4 text-center font-black text-sm text-slate-900"><?= number_format($avg, 1) ?></td>
                                            <td class="py-3.5 px-4 text-center font-bold text-emerald-700"><?= number_format($rb['max_nilai'], 0) ?></td>
                                            <td class="py-3.5 px-4 text-center font-bold text-rose-600"><?= number_format($rb['min_nilai'], 0) ?></td>
                                            <td class="py-3.5 px-4 text-center"><?= $status_badge ?></td>
                                            <td class="py-3.5 px-4 text-center">
                                                <a href="?mode=lembaran&santri_id=<?= $rb['id'] ?>&kelas=<?= urlencode($filters['kelas']) ?>&tahun_ajaran=<?= urlencode($filters['tahun_ajaran']) ?>&semester=<?= urlencode($filters['semester']) ?>" 
                                                   class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs shadow-sm transition inline-flex items-center gap-1.5">
                                                    <i class="fas fa-file-pdf"></i> Lihat Raport Utuh
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="py-8 text-center text-slate-400 italic">Belum ada data santri untuk kelas ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>

                <!-- TAB 2: PRINT AREA / RAPORT SHEET (LEMBARAN) -->
                <?php if ($selected_santri): ?>

                    <div class="print-area bg-white p-8 sm:p-12 rounded-2xl border border-slate-200/90 shadow-lg max-w-4xl mx-auto text-slate-900 text-xs sm:text-sm leading-relaxed">
                        
                        <!-- KOP RAPORT PKBM -->
                        <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
                            <h2 class="font-extrabold text-base sm:text-lg uppercase tracking-wider text-slate-900">SATUAN PENDIDIKAN KESETARAAN (PKBM)</h2>
                            <h1 class="text-xl sm:text-2xl font-black uppercase text-emerald-800 tracking-tight my-0.5">PKBM VILLA QUR'AN INDONESIA</h1>
                            <p class="text-xs text-slate-600 italic">Izin Operasional Dinas Pendidikan & Kebudayaan | Terakreditasi Standar Nasional</p>
                            <div class="mt-2 inline-block bg-slate-900 text-white font-extrabold px-4 py-1 rounded-full text-xs uppercase tracking-widest">
                                RAPOR PENDIDIKAN KESETARAAN <?= strtoupper($paket_tipe) ?> (<?= ($paket_tipe === 'Paket C') ? 'SETARA SMA' : 'SETARA SMP' ?>)
                            </div>
                        </div>

                        <!-- IDENTITAS PESERTA DIDIK -->
                        <div class="grid grid-cols-2 gap-4 mb-6 bg-slate-50/80 p-4 rounded-xl border border-slate-200 text-xs">
                            <div class="space-y-1.5">
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Nama Peserta Didik:</span> <span class="font-black text-slate-900 uppercase"><?= htmlspecialchars($selected_santri['nama_lengkap']) ?></span></div>
                                <div class="flex"><span class="w-32 font-bold text-slate-600">NIS / NISN:</span> <span class="font-mono font-bold"><?= htmlspecialchars($selected_santri['nis'] ?? '-') ?> / <?= htmlspecialchars($selected_santri['nisn'] ?? '-') ?></span></div>
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Jenis Kelamin:</span> <span class="font-bold"><?= htmlspecialchars($selected_santri['jenis_kelamin'] ?? '-') ?></span></div>
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Tempat, Tgl Lahir:</span> <span><?= htmlspecialchars($selected_santri['tempat_lahir'] ?? '-') ?>, <?= !empty($selected_santri['tanggal_lahir']) ? date('d F Y', strtotime($selected_santri['tanggal_lahir'])) : '-' ?></span></div>
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Satuan Pendidikan:</span> <span class="font-bold">PKBM Villa Qur'an Indonesia</span></div>
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Kelas / Tingkat:</span> <span class="font-bold text-emerald-800"><?= htmlspecialchars($selected_santri['kelas_sekarang']) ?> (<?= $paket_tipe ?>)</span></div>
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Semester:</span> <span class="font-bold"><?= htmlspecialchars($filters['semester']) ?></span></div>
                                <div class="flex"><span class="w-32 font-bold text-slate-600">Tahun Pelajaran:</span> <span class="font-bold"><?= htmlspecialchars($filters['tahun_ajaran']) ?></span></div>
                            </div>
                        </div>

                        <!-- CAPAIAN HASIL BELAJAR (NILAI DIKNAS) -->
                        <div class="mb-6">
                            <h3 class="font-extrabold text-sm uppercase text-slate-900 mb-3 flex items-center gap-2 border-b-2 border-emerald-600 pb-1">
                                <i class="fas fa-award text-emerald-600"></i>
                                <span>A. Capaian Hasil Belajar (Kurikulum Kesetaraan Diknas)</span>
                            </h3>

                            <table class="w-full border-collapse border border-slate-300 text-xs">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-10">No</th>
                                        <th class="py-2.5 px-4 border border-slate-300 text-left">Mata Pelajaran</th>
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-14">SKK</th>
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-20">Nilai Akhir</th>
                                        <th class="py-2.5 px-3 border border-slate-300 text-center w-16">Predikat</th>
                                        <th class="py-2.5 px-4 border border-slate-300 text-left">Capaian Pembelajaran (Deskripsi)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php if (!empty($nilai_mapel)): ?>
                                        <?php 
                                        $no = 1; 
                                        $total_nilai = 0;
                                        foreach ($nilai_mapel as $nm): 
                                            $val = (float)$nm['nilai'];
                                            $total_nilai += $val;
                                            $p_info = hitung_predikat($val);
                                            $desk = buat_deskripsi_capaian($nm['nama_mapel'], $val);
                                        ?>
                                            <tr class="hover:bg-slate-50/50">
                                                <td class="py-2.5 px-3 border border-slate-300 text-center font-bold"><?= $no++ ?></td>
                                                <td class="py-2.5 px-4 border border-slate-300 font-semibold text-slate-900">
                                                    <?= htmlspecialchars($nm['nama_mapel']) ?>
                                                    <?php if(!empty($nm['kode_mapel'])): ?>
                                                        <span class="text-[10px] text-slate-500 font-mono ml-1">(<?= htmlspecialchars($nm['kode_mapel']) ?>)</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="py-2.5 px-3 border border-slate-300 text-center font-mono font-bold">2</td>
                                                <td class="py-2.5 px-3 border border-slate-300 text-center font-black text-sm text-slate-900"><?= number_format($val, 0) ?></td>
                                                <td class="py-2.5 px-3 border border-slate-300 text-center">
                                                    <span class="px-2 py-0.5 rounded text-xs font-black border <?= $p_info['badge'] ?>">
                                                        <?= $p_info['predikat'] ?>
                                                    </span>
                                                </td>
                                                <td class="py-2.5 px-4 border border-slate-300 text-xs text-slate-700 leading-snug">
                                                    <?= htmlspecialchars($desk) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="bg-slate-100/80 font-bold border-t-2 border-slate-400">
                                            <td colspan="3" class="py-2.5 px-4 border border-slate-300 text-right uppercase">Rata-rata Nilai Akademik:</td>
                                            <td class="py-2.5 px-3 border border-slate-300 text-center text-sm font-black text-emerald-800">
                                                <?= count($nilai_mapel) > 0 ? number_format($total_nilai / count($nilai_mapel), 1) : '0' ?>
                                            </td>
                                            <td colspan="2" class="py-2.5 px-4 border border-slate-300 text-xs text-slate-500 italic">
                                                Skala Nilai 0 - 100 (A: ≥88, B: ≥78, C: ≥67, D: <67)
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="py-6 text-center text-slate-400 italic border border-slate-300">
                                                Belum ada data nilai akademik yang diinputkan untuk semester ini. Nilai dapat dimasukkan melalui menu Bank Nilai / Leger.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- KETRAMPILAN VOKASIONAL & EKSTRAKURIKULER -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                            <div>
                                <h3 class="font-extrabold text-xs uppercase text-slate-900 mb-2 flex items-center gap-1.5 border-b border-slate-300 pb-1">
                                    <i class="fas fa-rocket text-emerald-600"></i>
                                    <span>B. Kegiatan Ekstrakurikuler & Vokasional</span>
                                </h3>
                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs text-slate-700 min-h-[70px]">
                                    <?= !empty($catatan_data['ekstrakurikuler']) ? nl2br(htmlspecialchars($catatan_data['ekstrakurikuler'])) : '<span class="text-slate-400 italic">1. Inkubator Digital Solopreneurship (Baik)<br>2. Tahfidz & Khidmah Asrama (Sangat Baik)</span>' ?>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-extrabold text-xs uppercase text-slate-900 mb-2 flex items-center gap-1.5 border-b border-slate-300 pb-1">
                                    <i class="fas fa-calendar-minus text-emerald-600"></i>
                                    <span>C. Ketidakhadiran (Presensi)</span>
                                </h3>
                                <table class="w-full border-collapse border border-slate-300 text-xs">
                                    <thead>
                                        <tr class="bg-slate-100 text-center font-bold">
                                            <th class="py-1.5 px-2 border border-slate-300">Sakit</th>
                                            <th class="py-1.5 px-2 border border-slate-300">Izin</th>
                                            <th class="py-1.5 px-2 border border-slate-300">Tanpa Keterangan (Alpa)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="text-center font-bold">
                                            <td class="py-2 px-2 border border-slate-300"><?= $catatan_data['sakit'] ?? 0 ?> hari</td>
                                            <td class="py-2 px-2 border border-slate-300"><?= $catatan_data['izin'] ?? 0 ?> hari</td>
                                            <td class="py-2 px-2 border border-slate-300 text-rose-600"><?= $catatan_data['alpa'] ?? 0 ?> hari</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- CATATAN WALI KELAS -->
                        <div class="mb-8">
                            <h3 class="font-extrabold text-xs uppercase text-slate-900 mb-2 flex items-center gap-1.5 border-b border-slate-300 pb-1">
                                <i class="fas fa-comment-dots text-emerald-600"></i>
                                <span>D. Catatan Pembina / Wali Kelas</span>
                            </h3>
                            <div class="bg-slate-50 p-3.5 rounded-lg border border-slate-200 text-xs text-slate-800 italic leading-relaxed">
                                "<?= !empty($catatan_data['catatan_wali_kelas']) ? htmlspecialchars($catatan_data['catatan_wali_kelas']) : 'Tingkatkan terus semangat muraja\'ah hafalan Al-Qur\'an dan istiqomah dalam disiplin akademik. Semoga Allah mudahkan dalam menuntut ilmu.' ?>"
                            </div>
                        </div>

                        <!-- SIGNATURE BLOCK -->
                        <div class="pt-4 border-t border-slate-200">
                            <div class="text-right text-xs mb-8">
                                <span>Bogor, <?= !empty($catatan_data['tanggal_cetak']) ? date('d F Y', strtotime($catatan_data['tanggal_cetak'])) : date('d F Y') ?></span>
                            </div>
                            <div class="grid grid-cols-3 gap-4 text-center text-xs">
                                <div>
                                    <p class="font-bold text-slate-700">Orang Tua / Wali Santri,</p>
                                    <div class="h-16"></div>
                                    <p class="font-bold underline uppercase">( ........................................ )</p>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-700">Wali Kelas / Pembina,</p>
                                    <div class="h-16"></div>
                                    <p class="font-bold underline uppercase">( <?= htmlspecialchars($_SESSION['ustadz_nama'] ?? 'Pembina Asrama') ?> )</p>
                                </div>
                                <div>
                                    <p class="font-bold text-slate-700">Kepala PKBM / Sekolah,</p>
                                    <div class="h-16"></div>
                                    <p class="font-bold underline uppercase">( Ustadz Is Winsyah, M.Pd. )</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- FORM INPUT CATATAN & PRESENSI (NO PRINT) -->
                    <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm mt-8 max-w-4xl mx-auto no-print">
                        <h3 class="font-bold text-slate-900 text-base mb-4 flex items-center gap-2">
                            <i class="fas fa-edit text-emerald-600"></i>
                            <span>Input / Edit Catatan Raport & Presensi Santri Ini</span>
                        </h3>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="simpan_catatan_rapot" value="1">
                            <input type="hidden" name="santri_id" value="<?= $selected_santri['id'] ?>">
                            <input type="hidden" name="kelas" value="<?= htmlspecialchars($selected_santri['kelas_sekarang']) ?>">
                            <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($filters['tahun_ajaran']) ?>">
                            <input type="hidden" name="semester" value="<?= htmlspecialchars($filters['semester']) ?>">

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Sakit (Hari)</label>
                                    <input type="number" name="sakit" value="<?= $catatan_data['sakit'] ?? 0 ?>" min="0" class="w-full px-3 py-2 border rounded-xl text-xs">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Izin (Hari)</label>
                                    <input type="number" name="izin" value="<?= $catatan_data['izin'] ?? 0 ?>" min="0" class="w-full px-3 py-2 border rounded-xl text-xs">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Alpa (Hari)</label>
                                    <input type="number" name="alpa" value="<?= $catatan_data['alpa'] ?? 0 ?>" min="0" class="w-full px-3 py-2 border rounded-xl text-xs">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Kegiatan Ekstrakurikuler & Catatan Singkat</label>
                                <input type="text" name="ekstrakurikuler" value="<?= htmlspecialchars($catatan_data['ekstrakurikuler'] ?? '') ?>" placeholder="misal: 1. Solopreneur Digital (Baik), 2. Memanah (Sangat Baik)" class="w-full px-3 py-2 border rounded-xl text-xs">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Catatan Wali Kelas / Pembina</label>
                                <textarea name="catatan_wali_kelas" rows="3" class="w-full px-3 py-2 border rounded-xl text-xs" placeholder="Tuliskan motivasi & evaluasi pembelajaran santri..."><?= htmlspecialchars($catatan_data['catatan_wali_kelas'] ?? '') ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">Tanggal Cetak Raport</label>
                                    <input type="date" name="tanggal_cetak" value="<?= $catatan_data['tanggal_cetak'] ?? date('Y-m-d') ?>" class="w-full px-3 py-2 border rounded-xl text-xs">
                                </div>
                                <div class="text-right sm:pt-5">
                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-xl text-xs shadow-md transition">
                                        <i class="fas fa-save mr-1.5"></i> Simpan Catatan & Presensi
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="bg-white p-12 rounded-2xl text-center text-slate-400 italic max-w-2xl mx-auto no-print border border-slate-200">
                        <i class="fas fa-folder-open text-4xl mb-3 text-slate-300"></i>
                        <p>Silakan pilih Kelas dan Santri untuk menampilkan Raport Diknas PKBM Paket B / Paket C.</p>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }

        function filterRekapTable() {
            const filter = document.getElementById("search_rekap").value.toLowerCase();
            const trs = document.getElementById("rekap_table").getElementsByTagName("tr");
            for (let i = 1; i < trs.length; i++) {
                const text = trs[i].textContent || trs[i].innerText;
                trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    </script>
</body>
</html>
