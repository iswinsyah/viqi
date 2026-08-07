<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'kpi_kepsek';

// --- DATABASE SELF-HEALING ---
$conn->query("CREATE TABLE IF NOT EXISTS kpi_kepala_sekolah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    periode VARCHAR(7) NOT NULL, -- Format: YYYY-MM
    total_kbm_terjadwal INT DEFAULT 0,
    total_kbm_terlaksana INT DEFAULT 0,
    rpp_diknas_total INT DEFAULT 0,
    rpp_diknas_dikontrol INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_ustadz_periode (ustadz_id, periode)
)");

// Fetch all staff members that have the 'kepala_sekolah' role
$res_kepsek = $conn->query("SELECT id, nama, role FROM akun_ustadz WHERE role LIKE '%kepala_sekolah%' ORDER BY nama ASC");
$kepsek_list = [];
if ($res_kepsek) {
    while ($row = $res_kepsek->fetch_assoc()) {
        $kepsek_list[] = $row;
    }
}

// Get current user details from session
$current_ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$current_user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_current_kepsek = in_array('kepala_sekolah', array_map('trim', $current_user_roles));

// Set selected Kepala Sekolah ID
if ($is_current_kepsek) {
    // If logged in as Kepala Sekolah, lock to their own ID
    $selected_kepsek_id = $current_ustadz_id;
} else {
    // Otherwise, allow selecting from list (fall back to first one in list or default to 0)
    $selected_kepsek_id = isset($_GET['kepsek_id']) ? (int)$_GET['kepsek_id'] : ($kepsek_list[0]['id'] ?? 0);
}

// Set selected month and year
$selected_period = $_GET['periode'] ?? date('Y-m');
list($selected_year, $selected_month) = explode('-', $selected_period);
$selected_month = (int)$selected_month;
$selected_year = (int)$selected_year;

$pesan_sukses = '';
$pesan_error = '';

// --- HANDLE SELF-REPORTING FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_laporan_kepsek'])) {
    $target_ustadz_id = (int)$_POST['ustadz_id'];
    $periode = $conn->real_escape_string($_POST['periode']);
    $kbm_terjadwal = max(0, (int)$_POST['total_kbm_terjadwal']);
    $kbm_terlaksana = max(0, (int)$_POST['total_kbm_terlaksana']);
    $rpp_total = max(0, (int)$_POST['rpp_diknas_total']);
    $rpp_dikontrol = max(0, (int)$_POST['rpp_diknas_dikontrol']);

    // Ensure users can only edit their own report unless they are Admin/Executive
    if ($is_current_kepsek && $target_ustadz_id !== $current_ustadz_id) {
        $pesan_error = "Akses ditolak. Anda hanya dapat menyimpan laporan Anda sendiri.";
    } else {
        $sql = "INSERT INTO kpi_kepala_sekolah (ustadz_id, periode, total_kbm_terjadwal, total_kbm_terlaksana, rpp_diknas_total, rpp_diknas_dikontrol) 
                VALUES ($target_ustadz_id, '$periode', $kbm_terjadwal, $kbm_terlaksana, $rpp_total, $rpp_dikontrol)
                ON DUPLICATE KEY UPDATE 
                total_kbm_terjadwal = $kbm_terjadwal,
                total_kbm_terlaksana = $kbm_terlaksana,
                rpp_diknas_total = $rpp_total,
                rpp_diknas_dikontrol = $rpp_dikontrol";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Laporan kinerja bulanan berhasil disimpan!";
        } else {
            $pesan_error = "Gagal menyimpan laporan: " . $conn->error;
        }
    }
}

// --- FETCH CURRENT REPORT DETAILS ---
$report_data = null;
if ($selected_kepsek_id > 0) {
    $res_rep = $conn->query("SELECT * FROM kpi_kepala_sekolah WHERE ustadz_id = $selected_kepsek_id AND periode = '$selected_period'");
    if ($res_rep && $res_rep->num_rows > 0) {
        $report_data = $res_rep->fetch_assoc();
    }
}

// --- CALCULATE KPI METRICS ---
$score_kbm = 0;
$score_supervisi = 0;
$score_rpp = 0;
$score_rapat = 0;
$score_nilai = 0;

$kbm_terjadwal = $report_data['total_kbm_terjadwal'] ?? 0;
$kbm_terlaksana = $report_data['total_kbm_terlaksana'] ?? 0;
$rpp_total = $report_data['rpp_diknas_total'] ?? 0;
$rpp_dikontrol = $report_data['rpp_diknas_dikontrol'] ?? 0;

// 1. Mengontrol KBM (Bobot 20%)
// Target: minimal 90% KBM terlaksana
if ($kbm_terjadwal > 0) {
    $persen_kbm = ($kbm_terlaksana / $kbm_terjadwal) * 100;
    if ($persen_kbm >= 90) {
        $score_kbm = 100;
    } else {
        $score_kbm = ($persen_kbm / 90) * 100;
    }
} else {
    $persen_kbm = 0;
    $score_kbm = 100; // default clean starting point
}

// 2. Supervisi KBM (Bobot 20%)
// Target: minimal 2 kali supervisi per bulan
$total_supervisi = 0;
if ($selected_kepsek_id > 0) {
    $res_sup = $conn->query("SELECT COUNT(*) as total FROM supervisi_mengajar WHERE supervisor_id = $selected_kepsek_id AND MONTH(tanggal_supervisi) = $selected_month AND YEAR(tanggal_supervisi) = $selected_year");
    $total_supervisi = $res_sup ? (int)$res_sup->fetch_assoc()['total'] : 0;
}
$score_supervisi = min(100, ($total_supervisi / 2) * 100);

// 3. Mengontrol pengadaan RPP (Bobot 20%)
// Target: 100% RPP diknas diadakan & dikontrol
if ($rpp_total > 0) {
    $score_rpp = min(100, ($rpp_dikontrol / $rpp_total) * 100);
} else {
    $score_rpp = 100;
}

// 4. Hadir Rapat Koordinasi (Bobot 20%)
// Target: 2 kali rapat koordinasi 2-pekanan per bulan
$total_rapat_hadir = 0;
if ($selected_kepsek_id > 0) {
    $res_rapat = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as total FROM absensi_pegawai WHERE ustadz_id = $selected_kepsek_id AND jenis_absen = 'Rapat' AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year AND status_kehadiran = 'Masuk'");
    $total_rapat_hadir = $res_rapat ? (int)$res_rapat->fetch_assoc()['total'] : 0;
}
$score_rapat = min(100, ($total_rapat_hadir / 2) * 100);

// 5. Standarisasi Capaian Rata-rata Nilai Santri di atas KKM (Bobot 20%)
// Target: Nilai rata-rata di atas KKM (75) pada bulan ujian (Maret (3), Juni (6), Oktober (10), Desember (12))
$is_exam_month = in_array($selected_month, [3, 6, 10, 12]);
$avg_nilai = 0;

if ($is_exam_month) {
    $res_nilai = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE MONTH(created_at) = $selected_month AND YEAR(created_at) = $selected_year");
    $avg_nilai = $res_nilai ? (float)($res_nilai->fetch_assoc()['rata_rata'] ?? 0) : 0;
    
    // Fallback to overall database average if no records found in this specific month/year
    if ($avg_nilai <= 0) {
        $res_nilai_fb = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai");
        $avg_nilai = $res_nilai_fb ? (float)($res_nilai_fb->fetch_assoc()['rata_rata'] ?? 0) : 0;
    }
    
    if ($avg_nilai >= 75) {
        $score_nilai = 100;
    } else {
        $score_nilai = $avg_nilai > 0 ? ($avg_nilai / 75) * 100 : 0;
    }
} else {
    $score_nilai = 100; // auto points for non-exam months
}

// Calculate Total KPI Score
$total_kpi = ($score_kbm + $score_supervisi + $score_rpp + $score_rapat + $score_nilai) / 5;

// Determine Grade Predicate
if ($total_kpi >= 90) {
    $predikat = "Mumtaz (Grade A)";
    $color_predikat = "bg-emerald-100 text-emerald-800 border-emerald-250";
} elseif ($total_kpi >= 80) {
    $predikat = "Jayid (Grade B)";
    $color_predikat = "bg-blue-100 text-blue-800 border-blue-250";
} else {
    $predikat = "Aslha (Grade C)";
    $color_predikat = "bg-rose-100 text-rose-800 border-rose-250";
}

// Fetch selected Kepala Sekolah details
$selected_kepsek_name = "Belum Terpilih";
if ($selected_kepsek_id > 0) {
    $res_det = $conn->query("SELECT nama FROM akun_ustadz WHERE id = $selected_kepsek_id");
    if ($res_det) {
        $selected_kepsek_name = $res_det->fetch_assoc()['nama'] ?? 'Belum Terpilih';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Kepala Sekolah | Panel Eksekutif Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 text-left">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-pie text-amber-600 mr-2.5"></i>KPI Kepala Sekolah</h1>
                    <p class="text-xs text-gray-500 mt-1">Laporan kinerja bulanan Kepala Sekolah berdasarkan target manajerial, supervisi KBM, kontrol RPP, kehadiran rapat, dan standarisasi nilai KKM.</p>
                </div>
                <!-- Month Filter -->
                <form method="GET" class="flex flex-wrap items-center gap-2">
                    <?php if (!$is_current_kepsek && !empty($kepsek_list)): ?>
                        <select name="kepsek_id" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <?php foreach ($kepsek_list as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= $k['id'] == $selected_kepsek_id ? 'selected' : '' ?>><?= htmlspecialchars($k['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                    <input type="month" name="periode" value="<?= $selected_period ?>" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500">
                </form>
            </div>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-sm text-rose-600"></i> <?= $pesan_error ?>
                </div>
            <?php endif; ?>

            <?php if ($selected_kepsek_id <= 0): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-8 text-center text-gray-500 italic text-sm">
                    <i class="fas fa-info-circle text-amber-500 text-2xl mb-2"></i><br>
                    Belum ada guru dengan peran Kepala Sekolah terdaftar di sistem.
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- KPI Summary Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center text-center lg:col-span-1">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Nilai KPI</span>
                        <div class="text-5xl font-black text-amber-600 mb-2"><?= number_format($total_kpi, 1) ?><span class="text-lg text-gray-400">/100</span></div>
                        <span class="px-3 py-1 text-xs font-bold rounded-full border <?= $color_predikat ?> mb-4"><?= $predikat ?></span>
                        <p class="text-[10px] text-gray-400">Periode: <b><?= date('F Y', strtotime($selected_period . '-01')) ?></b><br>Kepala Sekolah: <b><?= htmlspecialchars($selected_kepsek_name) ?></b></p>
                    </div>

                    <!-- Input Form Laporan (Only visible to the active Principal or admin roles) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
                        <h2 class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-1.5 border-b pb-2">
                            <i class="fas fa-edit text-amber-600"></i>
                            <span>Form Pengisian Mandiri Kepala Sekolah</span>
                        </h2>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="ustadz_id" value="<?= $selected_kepsek_id ?>">
                            <input type="hidden" name="periode" value="<?= $selected_period ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total Jam KBM Formal Terjadwal (Bulan Ini)</label>
                                    <input type="number" name="total_kbm_terjadwal" value="<?= $kbm_terjadwal ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" required placeholder="Contoh: 120">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total Jam KBM Terlaksana (Bulan Ini)</label>
                                    <input type="number" name="total_kbm_terlaksana" value="<?= $kbm_terlaksana ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" required placeholder="Contoh: 114">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total RPP Pelajaran Diknas Wajib (Bulan Ini)</label>
                                    <input type="number" name="rpp_diknas_total" value="<?= $rpp_total ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" required placeholder="Contoh: 15">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total RPP yang Dikontrol & Disetujui (Bulan Ini)</label>
                                    <input type="number" name="rpp_diknas_dikontrol" value="<?= $rpp_dikontrol ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" required placeholder="Contoh: 15">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" name="save_laporan_kepsek" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold px-5 py-2.5 rounded-xl transition text-xs flex items-center gap-1.5 shadow-md">
                                    <i class="fas fa-save text-sm"></i> Simpan Laporan Kinerja
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- KPI Detail Component Cards -->
                <div class="space-y-4">
                    <h2 class="font-bold text-gray-800 text-base mb-2"><i class="fas fa-list-check text-amber-600 mr-1.5"></i>Rincian 5 Aspek Penilaian KPI</h2>
                    
                    <!-- 1. KBM Control -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded">1</span>
                                Kontrol KBM Formal (Dinas/Akademik)
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Mengontrol pelaksanaan KBM harian formal dengan target keaktifan minimal **90%**. Jika keaktifan $\ge 90\%$, mendapat poin penuh.</p>
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-150 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-amber-500 h-2.5 rounded-full" style="width: <?= min(100, $score_kbm) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Kehadiran KBM: <b><?= $kbm_terlaksana ?></b> dari <b><?= $kbm_terjadwal ?></b> jam terjadwal (<b><?= number_format($persen_kbm, 1) ?>%</b>)</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_kbm, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 2. KBM Supervision -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded">2</span>
                                Supervisi Klinis Kelas (KBM)
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Melakukan supervisi pengajaran ustadz/ustadzah formal secara langsung. Target minimal **2 kali supervisi per bulan**.</p>
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-150 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?= min(100, $score_supervisi) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Supervisi dilakukan: <b><?= $total_supervisi ?></b> kali dari target <b>2</b> kali sebulan (data terhitung otomatis)</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_supervisi, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 3. RPP Control -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded">3</span>
                                Kontrol Pengadaan Administrasi RPP Dinas
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Mengontrol dan menyetujui pengadaan RPP (Rencana Pelaksanaan Pembelajaran) guru untuk mapel diknas. Target **100%**.</p>
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-150 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-purple-500 h-2.5 rounded-full" style="width: <?= min(100, $score_rpp) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">RPP Dikontrol: <b><?= $rpp_dikontrol ?></b> dari <b><?= $rpp_total ?></b> wajib dikontrol</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_rpp, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 4. Rapat Koordinasi -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded">4</span>
                                Kehadiran Rapat Koordinasi Yayasan
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Menghadiri rapat koordinasi 2-pekanan bersama Yayasan/Pimpinan Ma'had. Target minimal **2 kali rapat per bulan**.</p>
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-150 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-500 h-2.5 rounded-full" style="width: <?= min(100, $score_rapat) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Kehadiran Rapat: <b><?= $total_rapat_hadir ?></b> kali dari target <b>2</b> kali rapat (data terhitung otomatis)</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_rapat, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 5. KKM Standarisasi -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded">5</span>
                                Standarisasi Capaian Rata-rata Nilai di atas KKM (75)
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Memastikan nilai akademis rata-rata santri di atas standar KKM (75) pada tengah semester (Oktober, Maret) dan akhir semester (Juni, Desember).</p>
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-150 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-teal-500 h-2.5 rounded-full" style="width: <?= min(100, $score_nilai) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">
                                <?php if ($is_exam_month): ?>
                                    Bulan Ujian Aktif. Rata-rata Nilai Leger saat ini: <b><?= number_format($avg_nilai, 1) ?></b> (Standard KKM: 75)
                                <?php else: ?>
                                    Bukan Bulan Ujian (Maret, Juni, Oktober, Desember). Skor diset otomatis **100%** (Udzur Syar'i).
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_nilai, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- JS SIDEBAR COLLAPSE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-yayasan2');
            const openBtn = document.getElementById('open-sidebar-yayasan2');
            const closeBtn = document.getElementById('close-sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');

            function toggleSidebar() {
                if(sidebar && overlay) { 
                    sidebar.classList.toggle('hidden'); 
                    overlay.classList.toggle('hidden'); 
                }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>
