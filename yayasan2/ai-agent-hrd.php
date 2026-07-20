<?php
require_once '../koneksi.php';
session_start();

$active_menu = 'ai_agent_hrd';

// Migration self-healing tabel SP-1
@$conn->query("CREATE TABLE IF NOT EXISTS surat_peringatan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    jenis_sp VARCHAR(20) DEFAULT 'SP-1',
    alasan TEXT NOT NULL,
    jumlah_alpa INT DEFAULT 3,
    semester VARCHAR(30) NOT NULL,
    tanggal_terbit DATE NOT NULL,
    status VARCHAR(20) DEFAULT 'Aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY (ustadz_id),
    KEY (semester)
)");

// Filter Bulan & Tahun
$selected_month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selected_year = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$selected_staf_id = isset($_GET['staf_id']) ? (int)$_GET['staf_id'] : 0; // 0 = Semua Pegawai

// Hitung Semester Berjalan
if ($selected_month >= 7) {
    $semester_str = $selected_year . '/' . ($selected_year + 1) . '-Ganjil';
    $start_sem_date = "$selected_year-07-01";
    $end_sem_date = "$selected_year-12-31";
} else {
    $semester_str = ($selected_year - 1) . '/' . $selected_year . '-Genap';
    $start_sem_date = "$selected_year-01-01";
    $end_sem_date = "$selected_year-06-30";
}

// Ambil list staf dari database
$res_staf = $conn->query("SELECT id, nama, role, status_pegawai FROM akun_ustadz ORDER BY nama ASC");
$staf_list = [];
if ($res_staf) {
    while ($r = $res_staf->fetch_assoc()) {
        $staf_list[] = $r;
    }
}

// Nama Bulan Indonesia
$nama_bulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Query Data Pegawai
$sql_where_staf = $selected_staf_id > 0 ? "WHERE id = $selected_staf_id" : "";
$res_pegawai_data = $conn->query("SELECT id, nama, role, status_pegawai, whatsapp FROM akun_ustadz $sql_where_staf ORDER BY nama ASC");

$pegawai_summary = [];
$sp1_list_all = [];

if ($res_pegawai_data) {
    while ($p = $res_pegawai_data->fetch_assoc()) {
        $p_id = (int)$p['id'];

        // --- A. ANALISIS JAM PELAJARAN (JP) ---
        $q_jurnal = $conn->query("SELECT j.*, m.kategori_mapel 
                                  FROM jurnal_mengajar j 
                                  LEFT JOIN master_mapel m ON j.mapel_id = m.id 
                                  WHERE j.ustadz_id = $p_id 
                                  AND MONTH(j.tanggal) = $selected_month 
                                  AND YEAR(j.tanggal) = $selected_year");
        
        $jp_diniyah_terlaksana = 0;
        $jp_diknas_terlaksana = 0;
        if ($q_jurnal) {
            while ($jr = $q_jurnal->fetch_assoc()) {
                if (($jr['kategori_mapel'] ?? '') === 'Diknas') {
                    $jp_diknas_terlaksana++;
                } else {
                    $jp_diniyah_terlaksana++;
                }
            }
        }
        
        $q_jadwal = $conn->query("SELECT COUNT(*) as total_jp FROM jadwal_pelajaran WHERE ustadz_id = $p_id");
        $jp_terjadwal_mingguan = $q_jadwal ? (int)($q_jadwal->fetch_assoc()['total_jp'] ?? 0) : 0;
        $jp_total_target = $jp_terjadwal_mingguan * 4;

        $jp_terlaksana = $jp_diniyah_terlaksana + $jp_diknas_terlaksana;
        $jp_kosong = max(0, $jp_total_target - $jp_terlaksana);

        // --- B. ANALISIS ABSENSI BULAN INI ---
        $q_absen = $conn->query("SELECT * FROM absensi_pegawai 
                                 WHERE ustadz_id = $p_id 
                                 AND MONTH(waktu_absen) = $selected_month 
                                 AND YEAR(waktu_absen) = $selected_year");
        
        $total_absen = 0;
        $hadir_tepat = 0;
        $hadir_terlambat = 0;
        $izin = 0;
        $sakit = 0;
        $alpa = 0;
        $array_menit_terlambat = [];

        if ($q_absen) {
            while ($ab = $q_absen->fetch_assoc()) {
                $total_absen++;
                $st_kehadiran = $ab['status_kehadiran'] ?? '';
                $ket = $ab['keterangan'] ?? '';

                if (strpos($st_kehadiran, 'Izin') !== false || strpos($ket, 'Izin') !== false) {
                    $izin++;
                } elseif (strpos($st_kehadiran, 'Sakit') !== false || strpos($ket, 'Sakit') !== false) {
                    $sakit++;
                } elseif (strpos($st_kehadiran, 'Alpa') !== false || strpos($ket, 'Alpa') !== false || strpos($ket, 'Tanpa Keterangan') !== false) {
                    $alpa++;
                } elseif (strpos($ket, 'Terlambat') !== false) {
                    $hadir_terlambat++;
                    if (preg_match('/Terlambat:\s*(\d+)\s*menit/i', $ket, $matches)) {
                        $array_menit_terlambat[] = (int)$matches[1];
                    } else {
                        $array_menit_terlambat[] = 15;
                    }
                } else {
                    $hadir_tepat++;
                }
            }
        }

        $avg_terlambat = !empty($array_menit_terlambat) ? round(array_sum($array_menit_terlambat) / count($array_menit_terlambat)) : 0;
        $max_terlambat = !empty($array_menit_terlambat) ? max($array_menit_terlambat) : 0;

        // --- C. ANALISIS ATURAN SP-1 (ALPA 3 HARI DALAM 1 SEMESTER) ---
        $q_alpa_sem = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as total_alpa 
                                    FROM absensi_pegawai 
                                    WHERE ustadz_id = $p_id 
                                    AND DATE(waktu_absen) BETWEEN '$start_sem_date' AND '$end_sem_date' 
                                    AND (status_kehadiran = 'Alpa' OR keterangan LIKE '%Alpa%' OR keterangan LIKE '%Tanpa Keterangan%')");
        $alpa_semester = $q_alpa_sem ? (int)($q_alpa_sem->fetch_assoc()['total_alpa'] ?? 0) : 0;

        // Auto check & terbitkan SP-1 jika alpa semester >= 3
        if ($alpa_semester >= 3) {
            $q_check_sp = $conn->query("SELECT * FROM surat_peringatan_pegawai WHERE ustadz_id = $p_id AND semester = '$semester_str' AND jenis_sp = 'SP-1' LIMIT 1");
            if ($q_check_sp && $q_check_sp->num_rows == 0) {
                $alasan_msg = "Terdeteksi tidak masuk kerja tanpa izin (Alpa) sebanyak $alpa_semester hari dalam semester $semester_str";
                $conn->query("INSERT INTO surat_peringatan_pegawai (ustadz_id, jenis_sp, alasan, jumlah_alpa, semester, tanggal_terbit, status) 
                              VALUES ($p_id, 'SP-1', '" . $conn->real_escape_string($alasan_msg) . "', $alpa_semester, '$semester_str', CURRENT_DATE(), 'Aktif')");
            }
        }

        // Ambil data SP-1 ustadz jika ada
        $q_sp1_cur = $conn->query("SELECT * FROM surat_peringatan_pegawai WHERE ustadz_id = $p_id AND semester = '$semester_str' AND jenis_sp = 'SP-1' LIMIT 1");
        $sp1_data = ($q_sp1_cur && $q_sp1_cur->num_rows > 0) ? $q_sp1_cur->fetch_assoc() : null;

        if ($sp1_data) {
            $sp1_list_all[] = [
                'nama' => $p['nama'],
                'role' => $p['role'],
                'alpa_sem' => $alpa_semester,
                'tanggal_sp' => $sp1_data['tanggal_terbit'],
                'status_sp' => $sp1_data['status'],
                'alasan' => $sp1_data['alasan']
            ];
        }

        $pegawai_summary[] = [
            'id' => $p['id'],
            'nama' => $p['nama'],
            'role' => $p['role'],
            'status_pegawai' => $p['status_pegawai'] ?? 'Pengabdian',
            'jp_diniyah' => $jp_diniyah_terlaksana,
            'jp_diknas' => $jp_diknas_terlaksana,
            'jp_terlaksana' => $jp_terlaksana,
            'jp_total' => $jp_total_target,
            'jp_kosong' => $jp_kosong,
            'total_absen' => $total_absen,
            'hadir_tepat' => $hadir_tepat,
            'hadir_terlambat' => $hadir_terlambat,
            'avg_terlambat' => $avg_terlambat,
            'max_terlambat' => $max_terlambat,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpa' => $alpa,
            'alpa_semester' => $alpa_semester,
            'sp1_data' => $sp1_data
        ];
    }
}

// Statistik Global Ringkasan
$tot_pegawai = count($pegawai_summary);
$tot_jp_terlaksana = array_sum(array_column($pegawai_summary, 'jp_terlaksana'));
$tot_hadir_tepat = array_sum(array_column($pegawai_summary, 'hadir_tepat'));
$tot_terlambat = array_sum(array_column($pegawai_summary, 'hadir_terlambat'));
$tot_sp1_aktif = count($sp1_list_all);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent HRD | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 z-10 flex-shrink-0 shadow-sm">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-slate-500 hover:text-slate-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-sm sm:text-base flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Panel Eksekutif Yayasan — AI Agent HRD
                </h2>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold px-3 py-1 bg-amber-50 text-amber-800 rounded-full border border-amber-200/60 hidden sm:inline-flex items-center gap-1.5">
                    <i class="fas fa-robot text-amber-600"></i> Auto-Pilot HRD & Monitoring SP-1
                </span>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-6 md:p-8 space-y-8">
            
            <!-- HEADER BANNER & FILTER -->
            <div class="bg-gradient-to-r from-slate-900 via-amber-950 to-slate-900 rounded-2xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
                <div class="absolute right-0 top-0 -mr-16 -mt-16 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 relative z-10">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold mb-3 border border-amber-500/30 backdrop-blur-md">
                            <i class="fas fa-microchip"></i> Sistem Pengawasan & Intelijen SDM (Semester: <?= $semester_str ?>)
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight flex items-center gap-3">
                            AI Agent HRD Pesantren
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-xl">
                            Monitoring jam mengajar (JP), presensi GPS, serta penegakan **Aturan SP-1 Otomatis (3 Hari Alpa per Semester)**.
                        </p>
                    </div>

                    <!-- FORM FILTER -->
                    <form action="" method="GET" class="bg-white/10 backdrop-blur-md border border-white/15 p-4 rounded-xl flex flex-wrap sm:flex-nowrap items-center gap-3">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-amber-200 mb-1">Pilih Pegawai</label>
                            <select name="staf_id" class="bg-slate-800 text-white text-xs border border-slate-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="0">Semua Pegawai & Asatidz (<?= count($staf_list) ?>)</option>
                                <?php foreach ($staf_list as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= $selected_staf_id == $s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-amber-200 mb-1">Bulan</label>
                            <select name="bulan" class="bg-slate-800 text-white text-xs border border-slate-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <?php for($m=1; $m<=12; $m++): ?>
                                    <option value="<?= $m ?>" <?= $selected_month == $m ? 'selected' : '' ?>>
                                        <?= $nama_bulan[$m] ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-amber-200 mb-1">Tahun</label>
                            <select name="tahun" class="bg-slate-800 text-white text-xs border border-slate-700 rounded-lg px-3 py-2 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <?php for($y=2025; $y<=2027; $y++): ?>
                                    <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="self-end">
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2 rounded-lg text-xs transition shadow-md flex items-center gap-1.5">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- CARDS METRIK EKSEKUTIF -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pegawai Evaluasi</p>
                        <h3 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $tot_pegawai ?> Orang</h3>
                        <p class="text-[11px] text-emerald-600 font-semibold mt-1"><i class="fas fa-check-circle mr-1"></i>Terdaftar & Terpantau</p>
                    </div>
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Jam Pelajaran Terlaksana</p>
                        <h3 class="text-2xl font-extrabold text-slate-800 mt-1"><?= $tot_jp_terlaksana ?> JP</h3>
                        <p class="text-[11px] text-indigo-600 font-semibold mt-1"><i class="fas fa-book-open mr-1"></i>Periode <?= $nama_bulan[$selected_month] ?> <?= $selected_year ?></p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Absensi Tepat Waktu</p>
                        <h3 class="text-2xl font-extrabold text-emerald-600 mt-1"><?= $tot_hadir_tepat ?> Absen</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-1">Status Disiplin</p>
                    </div>
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-sm flex items-center justify-between hover:shadow-md transition">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Surat Peringatan 1 (SP-1)</p>
                        <h3 class="text-2xl font-extrabold text-rose-600 mt-1"><?= $tot_sp1_aktif ?> Pegawai</h3>
                        <p class="text-[11px] text-rose-600 font-semibold mt-1"><i class="fas fa-file-contract mr-1"></i>Semester <?= $semester_str ?></p>
                    </div>
                    <div class="w-12 h-12 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center text-xl shadow-inner">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: MONITORING SURAT PERINGATAN (SP-1) PEGAWAI SEMESTER INI -->
            <div class="bg-white rounded-2xl shadow-sm border border-rose-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-rose-950 via-slate-900 to-rose-900 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center font-bold text-sm border border-rose-500/30">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white flex items-center gap-2">
                                Monitoring Penerbitan Surat Peringatan (SP-1)
                                <span class="bg-rose-500/30 text-rose-300 text-[10px] px-2 py-0.5 rounded border border-rose-500/30">Aturan: 3 Hari Alpa / Semester</span>
                            </h3>
                            <p class="text-[11px] text-slate-300">Daftar pegawai yang telah diterbitkan SP-1 otomatis karena akumulasi 3 hari Alpa tanpa izin di Semester <?= $semester_str ?></p>
                        </div>
                    </div>
                    <span class="text-xs bg-rose-500/20 text-rose-300 font-semibold px-3 py-1 rounded-full border border-rose-500/30">
                        <?= count($sp1_list_all) ?> Terbit SP-1
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-rose-50/70 border-b border-rose-100 text-rose-950 font-bold uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3">Nama Pegawai / Ustadz</th>
                                <th class="px-4 py-3">Jabatan / Role</th>
                                <th class="px-4 py-3 text-center">Akumulasi Alpa (Semester)</th>
                                <th class="px-4 py-3 text-center">Tanggal Terbit SP-1</th>
                                <th class="px-4 py-3 text-center">Status Sanksi</th>
                                <th class="px-4 py-3">Keterangan Alasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($sp1_list_all)): ?>
                                <?php $no=1; foreach ($sp1_list_all as $sp): ?>
                                    <tr class="hover:bg-rose-50/30 transition duration-150">
                                        <td class="px-4 py-3 text-center font-semibold text-slate-500"><?= $no++ ?></td>
                                        <td class="px-4 py-3 font-bold text-slate-800"><?= htmlspecialchars($sp['nama']) ?></td>
                                        <td class="px-4 py-3 text-slate-600 font-medium"><?= htmlspecialchars($sp['role'] ?: 'Pegawai') ?></td>
                                        <td class="px-4 py-3 text-center font-extrabold text-rose-600 bg-rose-50/50">
                                            <?= $sp['alpa_sem'] ?> Hari Alpa
                                        </td>
                                        <td class="px-4 py-3 text-center font-mono text-slate-600">
                                            <?= date('d M Y', strtotime($sp['tanggal_sp'])) ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">
                                                ⚠️ SP-1 Aktif
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 italic">
                                            <?= htmlspecialchars($sp['alasan']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-4 py-6 text-center text-slate-400 italic">
                                        <i class="fas fa-shield-check text-emerald-500 text-base mr-1"></i> Tidak ada pegawai yang terbit SP-1 pada Semester <?= $semester_str ?>. Seluruh kedisiplinan terjaga.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 2: TABEL REKAPITULASI JAM PELAJARAN (JP) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-amber-950 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm border border-amber-500/30">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white">Monitoring Jam Pelajaran (JP) Mengajar</h3>
                            <p class="text-[11px] text-slate-300">Rincian jam mengajar Diniyah & Diknas per-pegawai bulan <?= $nama_bulan[$selected_month] ?> <?= $selected_year ?></p>
                        </div>
                    </div>
                    <span class="text-xs bg-amber-500/20 text-amber-300 font-semibold px-3 py-1 rounded-full border border-amber-500/30">
                        <?= count($pegawai_summary) ?> Pegawai
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-12">No</th>
                                <th class="px-4 py-3">Nama Pegawai / Ustadz</th>
                                <th class="px-4 py-3">Role / Jabatan</th>
                                <th class="px-4 py-3 text-center">Diniyah</th>
                                <th class="px-4 py-3 text-center">Diknas</th>
                                <th class="px-4 py-3 text-center bg-emerald-50/50 text-emerald-900">Total Terlaksana</th>
                                <th class="px-4 py-3 text-center bg-amber-50/50 text-amber-900">Target JP</th>
                                <th class="px-4 py-3 text-center bg-rose-50/50 text-rose-900">Kosong / Belum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($pegawai_summary)): ?>
                                <?php $no=1; foreach ($pegawai_summary as $ps): ?>
                                    <tr class="hover:bg-slate-50/80 transition duration-150">
                                        <td class="px-4 py-3 text-center font-semibold text-slate-500"><?= $no++ ?></td>
                                        <td class="px-4 py-3 font-bold text-slate-800">
                                            <?= htmlspecialchars($ps['nama']) ?>
                                            <span class="block text-[10px] text-slate-400 font-normal">Status: <?= htmlspecialchars($ps['status_pegawai']) ?></span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 font-medium">
                                            <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[10px] font-semibold border border-slate-200">
                                                <?= htmlspecialchars($ps['role'] ?: 'Pegawai') ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center font-semibold text-cyan-700"><?= $ps['jp_diniyah'] ?> JP</td>
                                        <td class="px-4 py-3 text-center font-semibold text-indigo-700"><?= $ps['jp_diknas'] ?> JP</td>
                                        <td class="px-4 py-3 text-center font-extrabold text-emerald-700 bg-emerald-50/30">
                                            <?= $ps['jp_terlaksana'] ?> JP
                                        </td>
                                        <td class="px-4 py-3 text-center font-semibold text-amber-800 bg-amber-50/30">
                                            <?= $ps['jp_total'] > 0 ? $ps['jp_total'] . ' JP' : '-' ?>
                                        </td>
                                        <td class="px-4 py-3 text-center font-bold bg-rose-50/30 <?= $ps['jp_kosong'] > 0 ? 'text-rose-600' : 'text-slate-400' ?>">
                                            <?= $ps['jp_kosong'] > 0 ? $ps['jp_kosong'] . ' JP' : '0' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-slate-400 italic">Tidak ada data jam pelajaran untuk periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECTION 3: TABEL REKAPITULASI ABSENSI & KEDISIPLINAN PEGAWAI -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-950 text-white flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-300 flex items-center justify-center font-bold text-sm border border-indigo-500/30">
                            <i class="fas fa-id-card-clip"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-white">Monitoring Absensi & Status SP-1 Semester</h3>
                            <p class="text-[11px] text-slate-300">Rekap statistik kehadiran bulanan dan akumulasi Alpa Semester <?= $semester_str ?></p>
                        </div>
                    </div>
                    <span class="text-xs bg-indigo-500/20 text-indigo-300 font-semibold px-3 py-1 rounded-full border border-indigo-500/30">
                        Evaluasi Presensi
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-100/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider">
                                <th class="px-3 py-3 text-center w-10">No</th>
                                <th class="px-4 py-3">Nama Pegawai</th>
                                <th class="px-3 py-3 text-center">Total Absen</th>
                                <th class="px-3 py-3 text-center text-emerald-700">Hadir Tepat</th>
                                <th class="px-3 py-3 text-center text-amber-700">Hadir Terlambat</th>
                                <th class="px-3 py-3 text-center">Rata-rata Terlambat</th>
                                <th class="px-3 py-3 text-center text-blue-700">Izin</th>
                                <th class="px-3 py-3 text-center text-rose-700">Alpa Bulan Ini</th>
                                <th class="px-3 py-3 text-center bg-rose-50 text-rose-900">Alpa Semester</th>
                                <th class="px-3 py-3 text-center">Status SP-1</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if (!empty($pegawai_summary)): ?>
                                <?php $no=1; foreach ($pegawai_summary as $ps): ?>
                                    <tr class="hover:bg-slate-50/80 transition duration-150">
                                        <td class="px-3 py-3 text-center font-semibold text-slate-500"><?= $no++ ?></td>
                                        <td class="px-4 py-3 font-bold text-slate-800">
                                            <?= htmlspecialchars($ps['nama']) ?>
                                            <span class="block text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($ps['role'] ?: 'Pegawai') ?></span>
                                        </td>
                                        <td class="px-3 py-3 text-center font-bold text-slate-700"><?= $ps['total_absen'] ?> Hari</td>
                                        <td class="px-3 py-3 text-center font-extrabold text-emerald-600 bg-emerald-50/30">
                                            <?= $ps['hadir_tepat'] ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-extrabold <?= $ps['hadir_terlambat'] > 0 ? 'text-amber-600 bg-amber-50/30' : 'text-slate-400' ?>">
                                            <?= $ps['hadir_terlambat'] ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-medium <?= $ps['avg_terlambat'] > 0 ? 'text-amber-700' : 'text-slate-400' ?>">
                                            <?= $ps['avg_terlambat'] > 0 ? $ps['avg_terlambat'] . ' Menit' : '-' ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-semibold text-blue-600"><?= $ps['izin'] ?></td>
                                        <td class="px-3 py-3 text-center font-extrabold <?= $ps['alpa'] > 0 ? 'text-rose-600 bg-rose-50/40' : 'text-slate-400' ?>"><?= $ps['alpa'] ?></td>
                                        <td class="px-3 py-3 text-center font-extrabold bg-rose-50/50 <?= $ps['alpa_semester'] >= 3 ? 'text-rose-700' : 'text-slate-600' ?>">
                                            <?= $ps['alpa_semester'] ?> / 3 Hari
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <?php if (!empty($ps['sp1_data'])): ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-200">
                                                    ⚠️ Terbit SP-1
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    🟢 Aman
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="px-4 py-6 text-center text-slate-400 italic">Tidak ada data presensi absensi untuk periode ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- REKOMENDASI DAN INSIGHT AI HRD -->
            <div class="bg-gradient-to-r from-amber-900 via-slate-900 to-amber-950 rounded-2xl p-6 text-white shadow-md border border-amber-800/40">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-500 text-slate-950 flex items-center justify-center text-2xl font-bold flex-shrink-0 shadow-lg">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="space-y-1">
                        <h4 class="font-bold text-base text-amber-300 flex items-center gap-2">
                            Analisis & Catatan Intelijen AI Agent HRD (Pengawasan SP-1)
                        </h4>
                        <p class="text-xs text-slate-300 leading-relaxed">
                            <?php if ($tot_sp1_aktif > 0): ?>
                                Terdapat <span class="text-rose-400 font-bold"><?= $tot_sp1_aktif ?> pegawai</span> yang telah diterbitkan **Surat Peringatan 1 (SP-1)** pada semester <?= $semester_str ?> karena mencapai 3 hari Alpa tanpa izin. Notifikasi otomatis via WhatsApp telah dilesatkan ke Ybs & Bos.
                            <?php else: ?>
                                Kedisiplinan kehadiran pegawai semester <?= $semester_str ?> dalam kondisi **Sangat Baik**. Belum ada pegawai yang mencapai 3 hari Alpa tanpa izin.
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-yayasan2')?.addEventListener('click', () => {
            document.getElementById('sidebar-yayasan2')?.classList.toggle('hidden');
            document.getElementById('sidebar-overlay-yayasan2')?.classList.toggle('hidden');
        });
        document.getElementById('sidebar-overlay-yayasan2')?.addEventListener('click', () => {
            document.getElementById('sidebar-yayasan2')?.classList.toggle('hidden');
            document.getElementById('sidebar-overlay-yayasan2')?.classList.toggle('hidden');
        });
    </script>
</body>
</html>