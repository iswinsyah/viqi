<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'rekap_kbm';

// --- SELF-HEALING DATABASE: TABEL LAPORAN KBM BULANAN ---
$conn->query("CREATE TABLE IF NOT EXISTS laporan_kbm_bulanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    periode VARCHAR(7) NOT NULL,
    kepala_sekolah_id INT NOT NULL,
    status ENUM('draft', 'dikirim', 'disetujui_yayasan') DEFAULT 'draft',
    catatan_umum_kepsek TEXT NULL,
    catatan_yayasan TEXT NULL,
    tanggal_validasi_kepsek DATETIME NULL,
    tanggal_review_yayasan DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_periode (periode)
)");

$conn->query("CREATE TABLE IF NOT EXISTS laporan_kbm_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    laporan_id INT NOT NULL,
    ustadz_id INT NOT NULL,
    mapel_nama VARCHAR(100) NOT NULL,
    kelas_nama VARCHAR(100) NOT NULL DEFAULT '',
    jam_terjadwal INT DEFAULT 0,
    jam_sistem INT DEFAULT 0,
    jam_penyesuaian INT DEFAULT 0,
    jam_final INT DEFAULT 0,
    persen_kehadiran DECIMAL(5,2) DEFAULT 0.00,
    alasan_penyesuaian TEXT NULL,
    ringkasan_materi TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_laporan (laporan_id),
    INDEX idx_ustadz (ustadz_id)
)");

// User Session Info
$current_ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$current_user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $current_user_roles);

$is_super_admin = ($current_ustadz_id === 9999) || in_array('super_admin', $norm_roles);
$is_kepsek = $is_super_admin || in_array('kepala_sekolah', $norm_roles) || in_array('admin_sekolah', $norm_roles) || in_array('ketua_yayasan', $norm_roles);

if (!$is_kepsek) {
    die("Akses ditolak. Halaman ini hanya dapat diakses oleh Kepala Sekolah, Admin Sekolah, dan Yayasan.");
}

// Period Selection
$selected_period = $_GET['periode'] ?? date('Y-m');
list($selected_year, $selected_month) = explode('-', $selected_period);
$selected_month = (int)$selected_month;
$selected_year = (int)$selected_year;

$start_date = "$selected_year-" . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "-01";
$last_day = date('t', strtotime($start_date));
$end_date = "$selected_year-" . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "-$last_day";

// Days in Indonesian mapping
$indo_days = [
    'Sunday' => 'Ahad',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

// --- AJAX & POST HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'simpan_penyesuaian') {
        $periode = $conn->real_escape_string($_POST['periode'] ?? $selected_period);
        $details = json_decode($_POST['details'] ?? '[]', true);
        $catatan_umum = $conn->real_escape_string($_POST['catatan_umum'] ?? '');

        // 1. Dapatkan / Buat Master Header Laporan
        $res_head = $conn->query("SELECT id, status FROM laporan_kbm_bulanan WHERE periode = '$periode'");
        if ($res_head && $res_head->num_rows > 0) {
            $head = $res_head->fetch_assoc();
            if ($head['status'] !== 'draft' && !$is_super_admin) {
                echo json_encode(['status' => 'error', 'message' => 'Laporan sudah divalidasi dan terkunci!']);
                exit;
            }
            $laporan_id = (int)$head['id'];
            $conn->query("UPDATE laporan_kbm_bulanan SET catatan_umum_kepsek = '$catatan_umum' WHERE id = $laporan_id");
        } else {
            $conn->query("INSERT INTO laporan_kbm_bulanan (periode, kepala_sekolah_id, status, catatan_umum_kepsek) 
                          VALUES ('$periode', $current_ustadz_id, 'draft', '$catatan_umum')");
            $laporan_id = $conn->insert_id;
        }

        // 2. Simpan / Refresh Detail
        $conn->query("DELETE FROM laporan_kbm_detail WHERE laporan_id = $laporan_id");
        if (!empty($details)) {
            $stmt = $conn->prepare("INSERT INTO laporan_kbm_detail 
                (laporan_id, ustadz_id, mapel_nama, kelas_nama, jam_terjadwal, jam_sistem, jam_penyesuaian, jam_final, persen_kehadiran, alasan_penyesuaian, ringkasan_materi) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            foreach ($details as $d) {
                $u_id = (int)$d['ustadz_id'];
                $m_nama = $d['mapel_nama'];
                $k_nama = $d['kelas_nama'] ?? '';
                $j_target = (int)$d['jam_terjadwal'];
                $j_sis = (int)$d['jam_sistem'];
                $j_adj = (int)$d['jam_penyesuaian'];
                $j_fin = max(0, $j_sis + $j_adj);
                $persen = $j_target > 0 ? min(100, round(($j_fin / $j_target) * 100, 2)) : 100.00;
                $alasan = $d['alasan_penyesuaian'] ?? '';
                $materi = $d['ringkasan_materi'] ?? '';

                $stmt->bind_param("iissiiiidss", $laporan_id, $u_id, $m_nama, $k_nama, $j_target, $j_sis, $j_adj, $j_fin, $persen, $alasan, $materi);
                $stmt->execute();
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Draf penyesuaian rekap KBM berhasil disimpan!']);
        exit;
    }

    if ($action === 'kirim_ke_yayasan') {
        $periode = $conn->real_escape_string($_POST['periode'] ?? $selected_period);
        $res_head = $conn->query("SELECT id FROM laporan_kbm_bulanan WHERE periode = '$periode'");
        if ($res_head && $row = $res_head->fetch_assoc()) {
            $laporan_id = $row['id'];
            $conn->query("UPDATE laporan_kbm_bulanan 
                          SET status = 'dikirim', tanggal_validasi_kepsek = NOW(), kepala_sekolah_id = $current_ustadz_id 
                          WHERE id = $laporan_id");
            echo json_encode(['status' => 'success', 'message' => '✅ Laporan Rekap KBM berhasil divalidasi dan resmi dikirim ke Ketua Yayasan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Laporan belum disimpan sebagai draf.']);
        }
        exit;
    }

    if ($action === 'buka_kunci_laporan') {
        if (!$is_super_admin && !in_array('ketua_yayasan', $norm_roles)) {
            echo json_encode(['status' => 'error', 'message' => 'Hanya Yayasan / Super Admin yang berwenang membuka kunci laporan!']);
            exit;
        }
        $periode = $conn->real_escape_string($_POST['periode'] ?? $selected_period);
        $conn->query("UPDATE laporan_kbm_bulanan SET status = 'draft' WHERE periode = '$periode'");
        echo json_encode(['status' => 'success', 'message' => 'Kunci laporan berhasil dibuka kembali untuk revisi Kepala Sekolah.']);
        exit;
    }
}

// --- FETCH / GENERATE LAPORAN DATA ---
$res_laporan = $conn->query("SELECT l.*, u.nama as nama_kepsek 
    FROM laporan_kbm_bulanan l 
    LEFT JOIN akun_ustadz u ON l.kepala_sekolah_id = u.id 
    WHERE l.periode = '$selected_period'");
$laporan_header = $res_laporan ? $res_laporan->fetch_assoc() : null;
$is_locked = ($laporan_header && $laporan_header['status'] !== 'draft');

// Ambil data detail jika sudah ada di database
$rekap_data = [];
if ($laporan_header) {
    $lap_id = $laporan_header['id'];
    $res_det = $conn->query("SELECT d.*, u.nama as nama_tutor 
        FROM laporan_kbm_detail d 
        JOIN akun_ustadz u ON d.ustadz_id = u.id 
        WHERE d.laporan_id = $lap_id 
        ORDER BY u.nama ASC, d.mapel_nama ASC");
    if ($res_det && $res_det->num_rows > 0) {
        while ($r = $res_det->fetch_assoc()) {
            $rekap_data[] = $r;
        }
    }
}

// Jika belum pernah disimpan / draf kosong, hitung otomatis dari data mentah
if (empty($rekap_data)) {
    // 1. Ambil daftar tutor/guru yang memiliki jadwal atau mengajar
    $res_tutors = $conn->query("SELECT DISTINCT u.id, u.nama, u.role 
        FROM akun_ustadz u 
        WHERE u.role LIKE '%tutor%' OR u.role LIKE '%ustadz%' OR u.role LIKE '%guru%'
        ORDER BY u.nama ASC");
    
    $tutors = [];
    if ($res_tutors) {
        while ($t = $res_tutors->fetch_assoc()) {
            $tutors[$t['id']] = $t;
        }
    }

    // 2. Hitung Jam Terjadwal dari jadwal_pelajaran
    // Hitung kemunculan tiap hari dalam bulan berjalan
    $day_counts = ['Ahad' => 0, 'Senin' => 0, 'Selasa' => 0, 'Rabu' => 0, 'Kamis' => 0, 'Jumat' => 0, 'Sabtu' => 0];
    for ($d = 1; $d <= $last_day; $d++) {
        $cur_date = "$selected_year-" . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "-" . str_pad($d, 2, '0', STR_PAD_LEFT);
        $day_name = date('l', strtotime($cur_date));
        $indo_day = $indo_days[$day_name] ?? 'Senin';
        if (isset($day_counts[$indo_day])) $day_counts[$indo_day]++;
    }

    // 3. Loop untuk setiap tutor
    foreach ($tutors as $u_id => $tut) {
        // Ambil mapel & jadwal yang diampu
        $res_jdwl = $conn->query("SELECT jp.mapel_id, jp.kelas_id, jp.hari, mm.nama_mapel, mk.nama_kelas, COUNT(*) as jml_jp_per_hari 
            FROM jadwal_pelajaran jp 
            JOIN master_mapel mm ON jp.mapel_id = mm.id 
            LEFT JOIN master_kelas mk ON jp.kelas_id = mk.id 
            WHERE jp.ustadz_id = $u_id 
            GROUP BY jp.mapel_id, jp.kelas_id, jp.hari");
        
        $mapel_assigned = [];
        if ($res_jdwl && $res_jdwl->num_rows > 0) {
            while ($j = $res_jdwl->fetch_assoc()) {
                $key = $j['nama_mapel'] . ' - ' . ($j['nama_kelas'] ?? 'Umum');
                if (!isset($mapel_assigned[$key])) {
                    $mapel_assigned[$key] = [
                        'mapel_nama' => $j['nama_mapel'],
                        'kelas_nama' => $j['nama_kelas'] ?? '',
                        'jam_terjadwal' => 0
                    ];
                }
                $hari = $j['hari'];
                $occurrences = $day_counts[$hari] ?? 4;
                $mapel_assigned[$key]['jam_terjadwal'] += ((int)$j['jml_jp_per_hari'] * $occurrences);
            }
        }

        // Ambil data realisasi KBM dari jurnal_mengajar
        $res_jurnal = $conn->query("SELECT mata_pelajaran, kelas, COUNT(*) as jml_hadir, GROUP_CONCAT(CONCAT('• ', materi) SEPARATOR '\n') as materi_list 
            FROM jurnal_mengajar 
            WHERE ustadz_id = $u_id AND tanggal BETWEEN '$start_date' AND '$end_date'
            GROUP BY mata_pelajaran, kelas");
        
        $jurnal_mapel = [];
        if ($res_jurnal && $res_jurnal->num_rows > 0) {
            while ($jm = $res_jurnal->fetch_assoc()) {
                $key = $jm['mata_pelajaran'] . ' - ' . ($jm['kelas'] ?: 'Umum');
                $jurnal_mapel[$key] = [
                    'mapel_nama' => $jm['mata_pelajaran'],
                    'kelas_nama' => $jm['kelas'] ?: '',
                    'jam_sistem' => (int)$jm['jml_hadir'],
                    'ringkasan_materi' => $jm['materi_list']
                ];
            }
        }

        // Gabungkan
        $all_keys = array_unique(array_merge(array_keys($mapel_assigned), array_keys($jurnal_mapel)));
        if (!empty($all_keys)) {
            foreach ($all_keys as $k) {
                $target = $mapel_assigned[$k]['jam_terjadwal'] ?? 0;
                $sistem = $jurnal_mapel[$k]['jam_sistem'] ?? 0;
                $m_nama = $mapel_assigned[$k]['mapel_nama'] ?? ($jurnal_mapel[$k]['mapel_nama'] ?? 'Mapel');
                $k_nama = $mapel_assigned[$k]['kelas_nama'] ?? ($jurnal_mapel[$k]['kelas_nama'] ?? '');
                $materi = $jurnal_mapel[$k]['ringkasan_materi'] ?? '';

                if ($target === 0 && $sistem > 0) $target = $sistem; // Fallback jika jadwal belum diset tapi guru aktif mengajar

                $rekap_data[] = [
                    'ustadz_id' => $u_id,
                    'nama_tutor' => $tut['nama'],
                    'mapel_nama' => $m_nama,
                    'kelas_nama' => $k_nama,
                    'jam_terjadwal' => $target,
                    'jam_sistem' => $sistem,
                    'jam_penyesuaian' => 0,
                    'jam_final' => $sistem,
                    'persen_kehadiran' => $target > 0 ? min(100, round(($sistem / $target) * 100, 2)) : 100.00,
                    'alasan_penyesuaian' => '',
                    'ringkasan_materi' => $materi
                ];
            }
        }
    }
}

// Summary Metrics
$total_jam_terjadwal = 0;
$total_jam_sistem = 0;
$total_jam_penyesuaian = 0;
$total_jam_final = 0;

foreach ($rekap_data as $row) {
    $total_jam_terjadwal += (int)$row['jam_terjadwal'];
    $total_jam_sistem += (int)$row['jam_sistem'];
    $total_jam_penyesuaian += (int)$row['jam_penyesuaian'];
    $total_jam_final += (int)$row['jam_final'];
}
$overall_persen = $total_jam_terjadwal > 0 ? min(100, round(($total_jam_final / $total_jam_terjadwal) * 100, 2)) : 100.00;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi KBM Bulanan | Validasi Kepala Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 hidden sm:block">Panel Validasi KBM & Kurikulum</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    <i class="fas fa-home text-[11px]"></i> Dashboard
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6 text-left">
            <!-- Header Title & Month Filter -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                        <i class="fas fa-clipboard-user text-indigo-600"></i>
                        <span>Rekapitulasi KBM & Pengajaran Bulanan</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Laporan otomatis absensi & jurnal KBM Tutor untuk divalidasi Kepala Sekolah dan dikirimkan ke Yayasan.</p>
                </div>
                <!-- Period Filter -->
                <form method="GET" class="flex items-center gap-2">
                    <input type="month" name="periode" value="<?= $selected_period ?>" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-xs">
                </form>
            </div>

            <!-- STATUS BANNER -->
            <?php if ($laporan_header && $laporan_header['status'] === 'disetujui_yayasan'): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 mb-6 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-xs text-emerald-900">LAPORAN KBM TELAH DISETUJUI OLEH YAYASAN</h3>
                            <p class="text-[11px] text-emerald-700 mt-0.5">Divalidasi oleh: <b><?= htmlspecialchars($laporan_header['nama_kepsek'] ?? 'Kepala Sekolah') ?></b> • Disetujui Yayasan pada: <?= date('d M Y H:i', strtotime($laporan_header['tanggal_review_yayasan'] ?? $laporan_header['updated_at'])) ?></p>
                            <?php if (!empty($laporan_header['catatan_yayasan'])): ?>
                                <p class="text-[11px] text-emerald-800 bg-emerald-100/60 rounded-lg p-2 mt-1 italic">"<?= htmlspecialchars($laporan_header['catatan_yayasan']) ?>"</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button onclick="cetakLaporanKBM()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-print"></i> Cetak Berita Acara
                    </button>
                </div>
            <?php elseif ($laporan_header && $laporan_header['status'] === 'dikirim'): ?>
                <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 mb-6 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-xs text-indigo-900">LAPORAN KBM TELAH DIVALIDASI & TERKIRIM KE YAYASAN</h3>
                            <p class="text-[11px] text-indigo-700 mt-0.5">Dikirim pada: <?= date('d M Y H:i', strtotime($laporan_header['tanggal_validasi_kepsek'])) ?> • Status: <b>Menunggu Review Yayasan</b></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($is_super_admin || in_array('ketua_yayasan', $norm_roles)): ?>
                            <button onclick="bukaKunciLaporan()" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold rounded-xl text-xs transition">
                                <i class="fas fa-lock-open mr-1"></i> Buka Kunci
                            </button>
                        <?php endif; ?>
                        <button onclick="cetakLaporanKBM()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-print"></i> Cetak Laporan
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-6 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            <i class="fas fa-edit"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-xs text-amber-900">STATUS: DRAF REKAPITULASI KBM (BELUM DIKIRIM KE YAYASAN)</h3>
                            <p class="text-[11px] text-amber-700 mt-0.5">Silakan periksa jam realisasi sistem, isi kolom penyesuaian jika ada jam tambahan/kendala teknis, lalu klik <b>Validasi & Kirim</b>.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-amber-200 text-amber-900 text-[10px] font-black rounded-lg uppercase">Draft Mode</span>
                </div>
            <?php endif; ?>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Target Terjadwal</span>
                    <div class="text-2xl font-black text-slate-800 mt-1"><?= $total_jam_terjadwal ?> <span class="text-xs font-normal text-slate-400">JP</span></div>
                    <span class="text-[10px] text-slate-400">Berdasarkan jadwal mingguan</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Realisasi Sistem</span>
                    <div class="text-2xl font-black text-cyan-600 mt-1"><?= $total_jam_sistem ?> <span class="text-xs font-normal text-slate-400">JP</span></div>
                    <span class="text-[10px] text-slate-400">Absen GPS & Jurnal KBM</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Penyesuaian Kepsek</span>
                    <div class="text-2xl font-black <?= $total_jam_penyesuaian >= 0 ? 'text-indigo-600' : 'text-rose-600' ?> mt-1">
                        <?= ($total_jam_penyesuaian > 0 ? '+' : '') . $total_jam_penyesuaian ?> <span class="text-xs font-normal text-slate-400">JP</span>
                    </div>
                    <span class="text-[10px] text-slate-400">Adjustment berita acara</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Keaktifan KBM Final</span>
                    <div class="text-2xl font-black <?= $overall_persen >= 90 ? 'text-emerald-600' : 'text-amber-600' ?> mt-1">
                        <?= $overall_persen ?>%
                    </div>
                    <span class="text-[10px] text-slate-400">Total Final: <b><?= $total_jam_final ?> JP</b></span>
                </div>
            </div>

            <!-- REKAP TABLE CARD -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                            <i class="fas fa-table-list text-indigo-600"></i>
                            <span>Rincian Pembelajaran per Tutor & Mata Pelajaran</span>
                        </h2>
                        <p class="text-[11px] text-slate-400">Periode: <b><?= date('F Y', strtotime($start_date)) ?></b> (<?= date('d M', strtotime($start_date)) ?> s/d <?= date('d M Y', strtotime($end_date)) ?>)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="cetakLaporanKBM()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center gap-1">
                            <i class="fas fa-print"></i> Cetak Tabel
                        </button>
                    </div>
                </div>

                <?php if (empty($rekap_data)): ?>
                    <div class="py-12 text-center text-slate-400">
                        <i class="fas fa-calendar-xmark text-4xl mb-2 text-slate-300"></i>
                        <p class="text-xs">Tidak ada data jadwal atau jurnal mengajar pada periode ini.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                    <th class="px-3 py-3">No</th>
                                    <th class="px-4 py-3">Nama Tutor / Asatidz</th>
                                    <th class="px-4 py-3">Mata Pelajaran & Kelas</th>
                                    <th class="px-3 py-3 text-center">Terjadwal</th>
                                    <th class="px-3 py-3 text-center bg-cyan-50/50 text-cyan-800">Sistem (GPS)</th>
                                    <th class="px-3 py-3 text-center bg-indigo-50/50 text-indigo-800">Penyesuaian (+/-)</th>
                                    <th class="px-3 py-3 text-center bg-emerald-50/50 text-emerald-800">Final (JP)</th>
                                    <th class="px-3 py-3 text-center">Kehadiran</th>
                                    <th class="px-4 py-3">Alasan Penyesuaian *(Wajib jika diubah)*</th>
                                    <th class="px-3 py-3 text-center">Materi KBM</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($rekap_data as $idx => $r): 
                                ?>
                                    <tr class="border-b hover:bg-slate-50/50 row-rekap" data-idx="<?= $idx ?>">
                                        <td class="px-3 py-3 text-slate-400 font-bold"><?= $no++ ?></td>
                                        <td class="px-4 py-3 font-bold text-slate-800">
                                            <?= htmlspecialchars($r['nama_tutor']) ?>
                                            <input type="hidden" class="inp-ustadz-id" value="<?= $r['ustadz_id'] ?>">
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <span class="font-bold text-indigo-700 inp-mapel"><?= htmlspecialchars($r['mapel_nama']) ?></span>
                                            <?php if (!empty($r['kelas_nama'])): ?>
                                                <br><span class="text-[10px] text-slate-400 inp-kelas"><?= htmlspecialchars($r['kelas_nama']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-semibold text-slate-600">
                                            <span class="val-terjadwal"><?= (int)$r['jam_terjadwal'] ?></span> JP
                                        </td>
                                        <td class="px-3 py-3 text-center font-bold text-cyan-700 bg-cyan-50/30">
                                            <span class="val-sistem"><?= (int)$r['jam_sistem'] ?></span> JP
                                        </td>
                                        <td class="px-3 py-3 text-center bg-indigo-50/30">
                                            <?php if ($is_locked): ?>
                                                <span class="font-bold <?= $r['jam_penyesuaian'] >= 0 ? 'text-indigo-600' : 'text-rose-600' ?>">
                                                    <?= ($r['jam_penyesuaian'] > 0 ? '+' : '') . (int)$r['jam_penyesuaian'] ?>
                                                </span>
                                            <?php else: ?>
                                                <input type="number" step="1" class="inp-adj w-16 px-2 py-1 text-center font-bold border rounded-lg text-xs <?= $r['jam_penyesuaian'] != 0 ? 'border-indigo-400 bg-indigo-50 text-indigo-800' : 'border-slate-200' ?>" value="<?= (int)$r['jam_penyesuaian'] ?>" oninput="hitungRow(this)">
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-black text-emerald-700 bg-emerald-50/30">
                                            <span class="val-final"><?= (int)$r['jam_final'] ?></span> JP
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="badge-persen px-2 py-0.5 rounded-full text-[10px] font-bold <?= $r['persen_kehadiran'] >= 90 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                                <?= $r['persen_kehadiran'] ?>%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-slate-600">
                                            <?php if ($is_locked): ?>
                                                <span class="text-[11px] italic text-slate-500"><?= htmlspecialchars($r['alasan_penyesuaian'] ?: '-') ?></span>
                                            <?php else: ?>
                                                <input type="text" class="inp-alasan w-full px-2.5 py-1 border border-slate-200 rounded-lg text-xs placeholder-slate-300 focus:ring-1 focus:ring-indigo-500" value="<?= htmlspecialchars($r['alasan_penyesuaian'] ?? '') ?>" placeholder="Tulis alasan jika ada perubahan jam...">
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="hidden" class="inp-materi" value="<?= htmlspecialchars($r['ringkasan_materi'] ?? '') ?>">
                                            <?php if (!empty($r['ringkasan_materi'])): ?>
                                                <button type="button" onclick="lihatMateri('<?= htmlspecialchars(addslashes($r['nama_tutor'])) ?>', '<?= htmlspecialchars(addslashes($r['mapel_nama'])) ?>', <?= htmlspecialchars(json_encode($r['ringkasan_materi'])) ?>)" class="text-cyan-600 hover:text-cyan-800 text-xs font-bold" title="Lihat Jurnal Materi">
                                                    <i class="fas fa-book-open"></i>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-slate-300">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- CATATAN UMUM KEPALA SEKOLAH -->
                    <div class="mt-6 pt-4 border-t border-slate-100">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            <i class="fas fa-comment-dots text-indigo-600 mr-1"></i>
                            Catatan Umum & Kesimpulan Kepala Sekolah (Akan tampil pada Lembar Laporan Yayasan)
                        </label>
                        <?php if ($is_locked): ?>
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-700 italic">
                                <?= nl2br(htmlspecialchars($laporan_header['catatan_umum_kepsek'] ?: 'Tidak ada catatan khusus.')) ?>
                            </div>
                        <?php else: ?>
                            <textarea id="catatan-umum" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Tulis evaluasi umum pelaksanaan KBM bulan ini..."><?= htmlspecialchars($laporan_header['catatan_umum_kepsek'] ?? '') ?></textarea>
                        <?php endif; ?>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <?php if (!$is_locked): ?>
                        <div class="mt-6 flex flex-col sm:flex-row items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" onclick="simpanDraf()" class="w-full sm:w-auto px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center justify-center gap-1.5">
                                <i class="fas fa-save"></i> Simpan Draf
                            </button>
                            <button type="button" onclick="validasiDanKirim()" class="w-full sm:w-auto px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black rounded-xl text-xs transition shadow-sm flex items-center justify-center gap-2">
                                <i class="fas fa-lock"></i>
                                <span>🔒 Validasi & Kirim Laporan ke Yayasan</span>
                            </button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL LIHAT MATERI JURNAL -->
    <div id="modal-materi" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                <div>
                    <h3 id="modal-materi-title" class="font-bold text-slate-900 text-sm">Ringkasan Materi KBM</h3>
                    <p id="modal-materi-sub" class="text-[11px] text-slate-400">Tutor: -</p>
                </div>
                <button onclick="tutupMateri()" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
            </div>
            <div id="modal-materi-content" class="text-xs text-slate-700 whitespace-pre-wrap max-h-60 overflow-y-auto bg-slate-50 p-3 rounded-xl border border-slate-200"></div>
            <div class="mt-4 flex justify-end">
                <button onclick="tutupMateri()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition">Tutup</button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar-hr');
            const overlay = document.getElementById('sidebar-overlay-hr');
            if(sidebar) sidebar.classList.toggle('hidden');
            if(overlay) overlay.classList.toggle('hidden');
        });

        function hitungRow(input) {
            const row = input.closest('tr');
            const terjadwal = parseInt(row.querySelector('.val-terjadwal').innerText) || 0;
            const sistem = parseInt(row.querySelector('.val-sistem').innerText) || 0;
            const adj = parseInt(input.value) || 0;
            
            const fin = Math.max(0, sistem + adj);
            row.querySelector('.val-final').innerText = fin;

            let persen = 100.00;
            if (terjadwal > 0) {
                persen = Math.min(100, Math.round((fin / terjadwal) * 100 * 100) / 100);
            }
            const badge = row.querySelector('.badge-persen');
            badge.innerText = persen + '%';
            if (persen >= 90) {
                badge.className = "badge-persen px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800";
            } else {
                badge.className = "badge-persen px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800";
            }

            if (adj !== 0) {
                input.className = "inp-adj w-16 px-2 py-1 text-center font-bold border rounded-lg text-xs border-indigo-400 bg-indigo-50 text-indigo-800";
            } else {
                input.className = "inp-adj w-16 px-2 py-1 text-center font-bold border rounded-lg text-xs border-slate-200";
            }
        }

        function collectDetails() {
            const rows = document.querySelectorAll('.row-rekap');
            const details = [];
            rows.forEach(row => {
                const u_id = row.querySelector('.inp-ustadz-id').value;
                const m_nama = row.querySelector('.inp-mapel').innerText.trim();
                const k_el = row.querySelector('.inp-kelas');
                const k_nama = k_el ? k_el.innerText.trim() : '';
                const j_target = parseInt(row.querySelector('.val-terjadwal').innerText) || 0;
                const j_sis = parseInt(row.querySelector('.val-sistem').innerText) || 0;
                const inpAdj = row.querySelector('.inp-adj');
                const j_adj = inpAdj ? (parseInt(inpAdj.value) || 0) : 0;
                const inpAlasan = row.querySelector('.inp-alasan');
                const alasan = inpAlasan ? inpAlasan.value.trim() : '';
                const materi = row.querySelector('.inp-materi').value;

                details.push({
                    ustadz_id: u_id,
                    mapel_nama: m_nama,
                    kelas_nama: k_nama,
                    jam_terjadwal: j_target,
                    jam_sistem: j_sis,
                    jam_penyesuaian: j_adj,
                    alasan_penyesuaian: alasan,
                    ringkasan_materi: materi
                });
            });
            return details;
        }

        function simpanDraf(callback) {
            const details = collectDetails();
            const catatan = document.getElementById('catatan-umum') ? document.getElementById('catatan-umum').value.trim() : '';

            const formData = new FormData();
            formData.append('action', 'simpan_penyesuaian');
            formData.append('periode', '<?= $selected_period ?>');
            formData.append('catatan_umum', catatan);
            formData.append('details', JSON.stringify(details));

            Swal.fire({ title: 'Menyimpan Draf...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('admin-rekap-kbm.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        if (typeof callback === 'function') {
                            callback();
                        } else {
                            Swal.fire({ icon: 'success', title: 'Tersimpan!', text: res.message, timer: 1500, showConfirmButton: false })
                                .then(() => window.location.reload());
                        }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                    }
                })
                .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
        }

        function validasiDanKirim() {
            // Cek jika ada penyesuaian tanpa alasan
            const rows = document.querySelectorAll('.row-rekap');
            let adaErrorAlasan = false;
            rows.forEach(row => {
                const inpAdj = row.querySelector('.inp-adj');
                const adj = inpAdj ? (parseInt(inpAdj.value) || 0) : 0;
                const inpAlasan = row.querySelector('.inp-alasan');
                const alasan = inpAlasan ? inpAlasan.value.trim() : '';
                if (adj !== 0 && !alasan) {
                    adaErrorAlasan = true;
                    inpAlasan.focus();
                }
            });

            if (adaErrorAlasan) {
                Swal.fire({ icon: 'warning', title: 'Alasan Wajib Diisi', text: 'Ada jam penyesuaian (+/-) yang belum dilengkapi alasan tertulis!', confirmButtonColor: '#4f46e5' });
                return;
            }

            Swal.fire({
                title: 'Validasi & Kirim ke Yayasan?',
                text: 'Setelah dikirim, data rekapitulasi KBM bulan <?= date('F Y', strtotime($start_date)) ?> ini akan TERKUNCI dan resmi diserahkan ke Ketua Yayasan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Validasi & Kirim!',
                cancelButtonText: 'Cek Lagi'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Simpan draf terlebih dahulu lalu kunci
                    simpanDraf(() => {
                        const formData = new FormData();
                        formData.append('action', 'kirim_ke_yayasan');
                        formData.append('periode', '<?= $selected_period ?>');

                        fetch('admin-rekap-kbm.php', { method: 'POST', body: formData })
                            .then(res => res.json())
                            .then(res => {
                                if (res.status === 'success') {
                                    Swal.fire({ icon: 'success', title: 'Berhasil Terkirim!', text: res.message, confirmButtonColor: '#10b981' })
                                        .then(() => window.location.reload());
                                } else {
                                    Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                                }
                            })
                            .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
                    });
                }
            });
        }

        function bukaKunciLaporan() {
            Swal.fire({
                title: 'Buka Kunci Laporan?',
                text: 'Laporan akan kembali ke status Draf agar Kepala Sekolah bisa melakukan revisi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Buka Kunci!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'buka_kunci_laporan');
                    formData.append('periode', '<?= $selected_period ?>');

                    fetch('admin-rekap-kbm.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.fire({ icon: 'success', title: 'Kunci Dibuka', text: res.message, timer: 1500, showConfirmButton: false })
                                    .then(() => window.location.reload());
                            } else {
                                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                            }
                        });
                }
            });
        }

        function lihatMateri(tutor, mapel, materi) {
            document.getElementById('modal-materi-title').innerText = `Materi: ${mapel}`;
            document.getElementById('modal-materi-sub').innerText = `Tutor: ${tutor}`;
            document.getElementById('modal-materi-content').innerText = materi || 'Tidak ada catatan materi rinci.';
            document.getElementById('modal-materi').classList.remove('hidden');
        }

        function tutupMateri() {
            document.getElementById('modal-materi').classList.add('hidden');
        }

        function cetakLaporanKBM() {
            window.print();
        }
    </script>
</body>
</html>
