<?php
require_once 'auth.php';
require_once '../koneksi.php';

// Inisialisasi Database (Self-Healing Migrations)
$conn->query("CREATE TABLE IF NOT EXISTS kalender_akademik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL,
    status_hari VARCHAR(10) DEFAULT 'efektif',
    keterangan VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$active_menu = 'master_kalender';

// A. Pilihan Tahun Ajaran
$ta = $_GET['ta'] ?? '2026/2027';

if ($ta === '2025/2026') {
    $start_year = 2025;
    $end_year = 2026;
} else {
    $ta = '2026/2027';
    $start_year = 2026;
    $end_year = 2027;
}

$start_date = "$start_year-07-01";
$end_date = "$end_year-07-31"; // Rentang s/d Juli tahun berikutnya (13 baris seperti PDF)

// B. Handler AJAX Single Day Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'single_update') {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $status_hari = $conn->real_escape_string($_POST['status_hari']);
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    
    if ($status_hari === 'efektif') {
        $conn->query("DELETE FROM kalender_akademik WHERE tanggal = '$tanggal'");
    } else {
        $conn->query("INSERT INTO kalender_akademik (tanggal, status_hari, keterangan) VALUES ('$tanggal', '$status_hari', '$keterangan')
                      ON DUPLICATE KEY UPDATE status_hari = '$status_hari', keterangan = '$keterangan'");
    }
    
    echo json_encode(['status' => 'success']);
    exit;
}

// C. Handler Bulk Date Range Update
$sukses_msg = '';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'bulk_update') {
    $tgl_mulai = $conn->real_escape_string($_POST['tgl_mulai']);
    $tgl_selesai = $conn->real_escape_string($_POST['tgl_selesai']);
    $status_hari = $conn->real_escape_string($_POST['status_hari']);
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    
    $start = strtotime($tgl_mulai);
    $end = strtotime($tgl_selesai);
    
    if ($start <= $end) {
        while ($start <= $end) {
            $curr_date = date('Y-m-d', $start);
            if ($status_hari === 'efektif') {
                $conn->query("DELETE FROM kalender_akademik WHERE tanggal = '$curr_date'");
            } else {
                $conn->query("INSERT INTO kalender_akademik (tanggal, status_hari, keterangan) VALUES ('$curr_date', '$status_hari', '$keterangan')
                              ON DUPLICATE KEY UPDATE status_hari = '$status_hari', keterangan = '$keterangan'");
            }
            $start = strtotime("+1 day", $start);
        }
        header("Location: master-kalender.php?ta=$ta&sukses=1");
        exit;
    }
}

// D. Handler Delete Specific Holiday
if (isset($_GET['delete_date'])) {
    $del_date = $conn->real_escape_string($_GET['delete_date']);
    $conn->query("DELETE FROM kalender_akademik WHERE tanggal = '$del_date'");
    header("Location: master-kalender.php?ta=$ta&sukses=2");
    exit;
}

if (isset($_GET['sukses'])) {
    if ($_GET['sukses'] == 1) $sukses_msg = "Pengaturan tanggal kalender berhasil disimpan!";
    elseif ($_GET['sukses'] == 2) $sukses_msg = "Libur khusus tanggal tersebut berhasil dihapus!";
}

// E. Ambil overrides dari database
$overrides = [];
$res = $conn->query("SELECT * FROM kalender_akademik WHERE tanggal BETWEEN '$start_date' AND '$end_date'");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $overrides[$row['tanggal']] = [
            'status' => $row['status_hari'],
            'keterangan' => $row['keterangan']
        ];
    }
}

// F. Inisialisasi daftar bulan (13 bulan seperti PDF)
$months_order = [];
$curr_month = 7;
$curr_year = $start_year;

for ($i = 0; $i < 13; $i++) {
    $month_num = $curr_month;
    $year_num = $curr_year;
    
    $dateObj = DateTime::createFromFormat('!m', $month_num);
    $month_name_id = '';
    switch ($month_num) {
        case 1: $month_name_id = 'JANUARI'; break;
        case 2: $month_name_id = 'FEBRUARI'; break;
        case 3: $month_name_id = 'MARET'; break;
        case 4: $month_name_id = 'APRIL'; break;
        case 5: $month_name_id = 'MEI'; break;
        case 6: $month_name_id = 'JUNI'; break;
        case 7: $month_name_id = 'JULI'; break;
        case 8: $month_name_id = 'AGUSTUS'; break;
        case 9: $month_name_id = 'SEPTEMBER'; break;
        case 10: $month_name_id = 'OKTOBER'; break;
        case 11: $month_name_id = 'NOVEMBER'; break;
        case 12: $month_name_id = 'DESEMBER'; break;
    }
    
    $months_order[] = [
        'month' => $month_num,
        'year' => $year_num,
        'name' => "$month_name_id $year_num"
    ];
    
    $curr_month++;
    if ($curr_month > 12) {
        $curr_month = 1;
        $curr_year++;
    }
}

// G. Kalkulasi kumulatif hari efektif belajar secara kronologis
$calendar_map = [];
$effective_counter = 1;

$semester_ganjil_efektif = 0;
$semester_genap_efektif = 0;
$kts_days = 0;

$list_holiday_large = [];

$calc_start = strtotime($start_date);
$calc_end = strtotime($end_date);

while ($calc_start <= $calc_end) {
    $date_str = date('Y-m-d', $calc_start);
    $day_of_week = date('N', $calc_start); // 1 (Senin) - 7 (Minggu)
    
    // Default: Sabtu & Minggu libur umum
    if ($day_of_week >= 6) {
        $status = 'LU';
        $ket = 'Libur Akhir Pekan';
    } else {
        $status = 'efektif';
        $ket = '';
    }
    
    // Check overrides
    if (isset($overrides[$date_str])) {
        $status = $overrides[$date_str]['status'];
        $ket = $overrides[$date_str]['keterangan'];
    }
    
    $display_text = '';
    if ($status === 'efektif') {
        $display_text = $effective_counter;
        
        $m_num = (int)date('m', $calc_start);
        // Semester Ganjil: Juli - Desember
        if ($m_num >= 7 && $m_num <= 12) {
            $semester_ganjil_efektif++;
        } else {
            $semester_genap_efektif++;
        }
        $effective_counter++;
    } else {
        $display_text = $status;
        if ($status === 'KTS') {
            $kts_days++;
        }
        
        // Simpan libur besar/kegiatan khusus (selain LU akhir pekan biasa)
        if ($status !== 'LU' || $day_of_week < 6) {
            $list_holiday_large[$date_str] = [
                'status' => $status,
                'keterangan' => $ket
            ];
        }
    }
    
    $calendar_map[$date_str] = [
        'status' => $status,
        'keterangan' => $ket,
        'display' => $display_text
    ];
    
    $calc_start = strtotime("+1 day", $calc_start);
}

// Urutkan Libur Besar kronologis
ksort($list_holiday_large);

// Mapping gaya warna status hari
$status_colors = [
    'efektif' => 'bg-white text-gray-800 border-gray-250',
    'LHB' => 'bg-red-600 text-white border-red-700 font-extrabold',
    'LU' => 'bg-red-600 text-white border-red-700 font-extrabold',
    'LS1' => 'bg-cyan-500 text-white border-cyan-600 font-extrabold',
    'LS2' => 'bg-cyan-500 text-white border-cyan-600 font-extrabold',
    'CB' => 'bg-slate-400 text-white border-slate-500 font-extrabold',
    'KPP' => 'bg-amber-500 text-white border-amber-600 font-extrabold',
    'LHR' => 'bg-rose-500 text-white border-rose-600 font-extrabold',
    'KTS' => 'bg-emerald-500 text-white border-emerald-600 font-extrabold',
    'KE' => 'bg-blue-500 text-white border-blue-600 font-extrabold'
];

$status_labels = [
    'efektif' => 'Hari Belajar Efektif',
    'LHB' => 'Libur Hari Besar',
    'LU' => 'Libur Umum',
    'LS1' => 'Libur Semester 1',
    'LS2' => 'Libur Semester 2',
    'CB' => 'Cuti Bersama',
    'KPP' => 'Kegiatan Permulaan Puasa',
    'LHR' => 'Libur Sekitar Hari Raya',
    'KTS' => 'Kegiatan Tengah Semester (KTS)',
    'KE' => 'Kegiatan Ekstrakurikuler'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Kalender Akademik | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .grid-cell {
            width: 26px;
            height: 26px;
            font-size: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #e2e8f0;
            user-select: none;
        }
        .invalid-day {
            background: repeating-linear-gradient(45deg, #f1f5f9, #f1f5f9 3px, #cbd5e1 3px, #cbd5e1 6px);
            color: transparent;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-gray-500">Tahun Ajaran:</label>
                <select onchange="location.href='?ta=' + this.value" class="px-3 py-1.5 border rounded-lg text-sm font-semibold bg-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                    <option value="2026/2027" <?= $ta === '2026/2027' ? 'selected' : '' ?>>2026/2027</option>
                    <option value="2025/2026" <?= $ta === '2025/2026' ? 'selected' : '' ?>>2025/2026</option>
                </select>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-calendar-alt text-amber-500 mr-2"></i>Master Kalender Akademik</h1>
                    <p class="text-gray-500 mt-1">Mengatur kalender operasional belajar mengajar, libur besar, dan libur umum.</p>
                </div>
            </div>

            <?php if (!empty($sukses_msg)): ?>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-check-circle mr-2"></i> <?= $sukses_msg ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8 items-start">
                
                <!-- GRID UTAMA KALENDER (3/4 Kolom) -->
                <div class="lg:col-span-3 bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-x-auto">
                    <div class="min-w-[960px]">
                        <div class="border-b border-gray-100 pb-3 mb-6 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800 text-base"><i class="fas fa-th mr-2 text-amber-500"></i>Grid Pendidikan Tahun Pelajaran <?= $ta ?></h3>
                            <span class="text-xs text-gray-400 font-medium">* Klik sel tanggal untuk mengubah status hari.</span>
                        </div>

                        <!-- TABEL GRID KALENDER -->
                        <table class="w-full border-collapse text-xs select-none">
                            <thead>
                                <tr class="bg-amber-900 text-white font-bold">
                                    <th class="border border-amber-950 px-2 py-2 text-center" style="width: 35px;">No</th>
                                    <th class="border border-amber-950 px-4 py-2 text-left" style="width: 140px;">BULAN</th>
                                    <th class="border border-amber-950 py-2 text-center" colspan="31">TANGGAL</th>
                                </tr>
                                <tr class="bg-amber-800 text-white font-bold">
                                    <th class="border border-amber-900"></th>
                                    <th class="border border-amber-900"></th>
                                    <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <th class="border border-amber-900 text-center text-[10px]" style="width: 26px;"><?= $d ?></th>
                                    <?php endfor; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php 
                                $row_no = 1;
                                foreach ($months_order as $m):
                                ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="border border-gray-200 text-center font-bold bg-amber-50 text-amber-900 py-1"><?= $row_no++ ?></td>
                                        <td class="border border-gray-200 font-bold bg-amber-50 text-amber-900 px-3 py-1 whitespace-nowrap text-left"><?= $m['name'] ?></td>
                                        
                                        <?php 
                                        for ($day = 1; $day <= 31; $day++):
                                            $curr_date_str = sprintf("%04d-%02d-%02d", $m['year'], $m['month'], $day);
                                            
                                            if (!checkdate($m['month'], $day, $m['year'])):
                                                echo '<td class="grid-cell invalid-day"></td>';
                                            else:
                                                $day_data = $calendar_map[$curr_date_str];
                                                $status = $day_data['status'];
                                                $color_class = $status_colors[$status] ?? 'bg-white';
                                                
                                                $tooltip = date('d M Y', strtotime($curr_date_str));
                                                if (!empty($day_data['keterangan'])) {
                                                    $tooltip .= " : " . $day_data['keterangan'];
                                                } else {
                                                    $tooltip .= " : " . ($status === 'efektif' ? 'Hari Efektif Belajar' : ($status_labels[$status] ?? $status));
                                                }
                                        ?>
                                                <td class="grid-cell <?= $color_class ?> border border-gray-200 cursor-pointer hover:scale-105 transition duration-100" 
                                                    title="<?= htmlspecialchars($tooltip) ?>"
                                                    data-date="<?= $curr_date_str ?>"
                                                    data-status="<?= $status ?>"
                                                    data-keterangan="<?= htmlspecialchars($day_data['keterangan'] ?? '') ?>"
                                                    onclick="openSingleEditModal(this)">
                                                    <?= $day_data['display'] ?>
                                                </td>
                                        <?php 
                                            endif;
                                        endfor; 
                                        ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <!-- LEGENDA KETERANGAN & RINGKASAN HARI EFEKTIF -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-8 border-t border-gray-100 pt-6">
                            <!-- Sisi Kiri: Legenda Warna -->
                            <div>
                                <h4 class="font-bold text-gray-800 text-sm mb-4"><i class="fas fa-palette text-amber-500 mr-2"></i>Keterangan Warna & Kode</h4>
                                <div class="grid grid-cols-2 gap-3 text-xs text-left">
                                    <?php foreach ($status_labels as $code => $lbl): 
                                        if ($code === 'efektif') continue;
                                        $bg = $status_colors[$code];
                                    ?>
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-6 rounded flex items-center justify-center text-[10px] shadow-sm border <?= $bg ?>"><?= $code ?></div>
                                            <span class="text-gray-600 font-medium"><?= $lbl ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Sisi Kanan: Ringkasan Hari Efektif -->
                            <div class="bg-amber-50 rounded-xl p-5 border border-amber-100 text-left">
                                <h4 class="font-bold text-amber-900 text-sm mb-4"><i class="fas fa-info-circle mr-2"></i>Ringkasan Hari Kerja Efektif</h4>
                                <table class="w-full text-xs font-semibold text-amber-900">
                                    <tbody>
                                        <tr class="border-b border-amber-100/50 pb-2">
                                            <td class="py-1">Hari Efektif Semester Ganjil</td>
                                            <td class="text-right py-1 font-bold"><?= $semester_ganjil_efektif ?> hari</td>
                                        </tr>
                                        <tr class="border-b border-amber-100/50 pb-2">
                                            <td class="py-2">Hari Efektif Semester Genap</td>
                                            <td class="text-right py-2 font-bold"><?= $semester_genap_efektif ?> hari</td>
                                        </tr>
                                        <tr class="border-b border-amber-100/50 pb-2">
                                            <td class="py-2">KTS (Kegiatan Tengah Semester)</td>
                                            <td class="text-right py-2 font-bold"><?= $kts_days ?> hari</td>
                                        </tr>
                                        <tr>
                                            <td class="py-2 text-[10px] text-amber-800 font-normal italic" colspan="2">* KTS dan kegiatan eksternal tidak dihitung ke akumulasi efektif wajib murid.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORM BULK SETTER (1/4 Kolom) -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-left">
                    <h3 class="font-bold text-gray-800 text-base mb-2"><i class="fas fa-calendar-plus text-amber-500 mr-2"></i>Bulk Date Setter</h3>
                    <p class="text-xs text-gray-500 mb-6">Gunakan form ini untuk menetapkan libur panjang atau rentang kegiatan belajar secara massal.</p>
                    
                    <form action="master-kalender.php?ta=<?= $ta ?>" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="bulk_update">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Mulai</label>
                            <input type="date" name="tgl_mulai" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Selesai</label>
                            <input type="date" name="tgl_selesai" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-amber-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Status Hari</label>
                            <select name="status_hari" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white focus:ring-1 focus:ring-amber-500 focus:outline-none">
                                <?php foreach ($status_labels as $code => $lbl): ?>
                                    <option value="<?= $code ?>"><?= $lbl ?> (<?= $code ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Keterangan / Kegiatan</label>
                            <input type="text" name="keterangan" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-1 focus:ring-amber-500 focus:outline-none" placeholder="Contoh: Libur Hari Raya Idul Fitri">
                        </div>
                        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2.5 px-4 rounded-lg text-xs shadow-md transition-all duration-200">
                            <i class="fas fa-save mr-1"></i> Terapkan Pengaturan
                        </button>
                    </form>
                </div>
            </div>

            <!-- DETAIL HARI LIBUR & BESAR NASIONAL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-4xl text-left mb-8">
                <div class="border-b border-gray-100 pb-3 mb-4 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800 text-base"><i class="fas fa-list-ul text-amber-500 mr-2"></i>Daftar Hari Libur & Kegiatan Khusus Terdaftar</h3>
                    <span class="text-xs bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full"><?= count($list_holiday_large) ?> Entri</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-150 text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 font-bold">
                                <th class="px-4 py-2.5 text-left">Tanggal</th>
                                <th class="px-4 py-2.5 text-left">Kode</th>
                                <th class="px-4 py-2.5 text-left">Status Hari</th>
                                <th class="px-4 py-2.5 text-left">Deskripsi / Kegiatan</th>
                                <th class="px-4 py-2.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($list_holiday_large) > 0): 
                                foreach ($list_holiday_large as $dt => $val):
                                    $col = $status_colors[$val['status']] ?? 'bg-white';
                            ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-4 py-2.5 font-semibold text-gray-800"><?= date('d F Y', strtotime($dt)) ?></td>
                                        <td class="px-4 py-2.5">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold border border-opacity-20 <?= $col ?>">
                                                <?= $val['status'] ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600 font-medium"><?= $status_labels[$val['status']] ?? $val['status'] ?></td>
                                        <td class="px-4 py-2.5 text-gray-500 italic"><?= htmlspecialchars($val['keterangan']) ?: '-' ?></td>
                                        <td class="px-4 py-2.5 text-center">
                                            <a href="?ta=<?= $ta ?>&delete_date=<?= $dt ?>" class="text-rose-600 hover:text-rose-800 font-bold text-xs bg-rose-50 hover:bg-rose-100 px-3 py-1 rounded-md transition duration-150" onclick="return confirm('Kembalikan tanggal ini ke Hari Efektif Belajar?')">
                                                Hapus Libur
                                            </a>
                                        </td>
                                    </tr>
                            <?php 
                                endforeach;
                            else: 
                            ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-400">Tidak ada hari libur nasional atau kegiatan khusus terdaftar. Seluruh hari kerja berjalan efektif.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL EDIT QUICK SINGLE DAY -->
    <div id="single-edit-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeSingleEditModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full p-6">
                <div class="border-b border-gray-150 pb-3 mb-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800" id="modal-date-title">Ubah Status Tanggal</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="closeSingleEditModal()"><i class="fas fa-times"></i></button>
                </div>
                
                <form id="single-edit-form" class="space-y-4">
                    <input type="hidden" id="modal-input-date" name="tanggal">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status Hari</label>
                        <select id="modal-input-status" name="status_hari" required class="w-full px-3 py-2 border rounded-lg text-sm bg-white focus:outline-none focus:ring-1 focus:ring-amber-500">
                            <?php foreach ($status_labels as $code => $lbl): ?>
                                <option value="<?= $code ?>"><?= $lbl ?> (<?= $code ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Keterangan / Nama Kegiatan</label>
                        <input type="text" id="modal-input-keterangan" name="keterangan" class="w-full px-3 py-2 border rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-amber-500" placeholder="Contoh: Maulid Nabi Muhammad SAW">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg text-xs" onclick="closeSingleEditModal()">Batal</button>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2 px-4 rounded-lg text-xs shadow-md">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // JS Modal Controls
        function openSingleEditModal(element) {
            const date = element.getAttribute('data-date');
            const status = element.getAttribute('data-status');
            const keterangan = element.getAttribute('data-keterangan');

            const options = { year: 'numeric', month: 'long', day: 'numeric' };
            const formattedDate = new Date(date).toLocaleDateString('id-ID', options);

            document.getElementById('modal-date-title').innerText = `Tanggal: ${formattedDate}`;
            document.getElementById('modal-input-date').value = date;
            document.getElementById('modal-input-status').value = status;
            document.getElementById('modal-input-keterangan').value = keterangan;

            document.getElementById('single-edit-modal').classList.remove('hidden');
        }

        function closeSingleEditModal() {
            document.getElementById('single-edit-modal').classList.add('hidden');
        }

        // AJAX Form Submit untuk Single Edit
        document.getElementById('single-edit-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const date = document.getElementById('modal-input-date').value;
            const status = document.getElementById('modal-input-status').value;
            const keterangan = document.getElementById('modal-input-keterangan').value;

            const formData = new FormData();
            formData.append('action', 'single_update');
            formData.append('tanggal', date);
            formData.append('status_hari', status);
            formData.append('keterangan', keterangan);

            fetch('master-kalender.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    closeSingleEditModal();
                    window.location.reload();
                } else {
                    alert('Gagal memperbarui tanggal.');
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi.');
            });
        });
    </script>
</body>
</html>
