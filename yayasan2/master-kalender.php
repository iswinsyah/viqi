<?php
// Prevent caching (Bypass Litespeed/Cloudflare/Browser Caches)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth.php';
require_once '../koneksi.php';

// A. Inisialisasi Database (Self-Healing Migrations)
$conn->query("CREATE TABLE IF NOT EXISTS kalender_akademik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL,
    status_hari VARCHAR(10) DEFAULT 'efektif',
    keterangan VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$active_menu = 'master_kalender';

// B. Definisi Kategori dan Deskripsi Hari Libur / Agenda
$holiday_categories = [
    'Hari Besar Nasional' => [
        'HUT' => 'HUT RI',
        'HBI' => 'Hari Buruh Internasional',
        'PCS' => 'Lahir Pancasila'
    ],
    'Hari Besar Islam' => [
        'MLD' => 'Maulud Nabi saw',
        'IMN' => "Isro' Mi'roj Nabi saw",
        'IDF' => 'Idul Fitri',
        'IDA' => 'Idul Adha',
        'TBI' => 'Tahun Baru Islam'
    ],
    'Hari Besar Agama Lain' => [
        'TBM' => 'Tahun Baru Masehi',
        'NTL' => 'Natal',
        'IML' => 'Imlek',
        'NYP' => 'Nyepi',
        'WFT' => 'Kematian Yudas Escariot',
        'PSK' => 'Paskah',
        'ISA' => 'Kenaikan Isa as',
        'WSK' => 'Waisak'
    ],
    'Agenda Akademik' => [
        'KS1' => 'Kedatangan Santri Awal Semester 1',
        'KS2' => 'Kedatangan Santri Awal Semester 2',
        'AS1' => 'Awal Semester 1',
        'AS2' => 'Awal Semester 2',
        'RMD' => 'Remidi',
        'UJK' => 'Ujian Kesetaraan',
        'LS1' => 'Libur Semester 1',
        'LS2' => 'Libur Semester 2',
        'CTB' => 'Cuti Bersama'
    ],
    'Agenda Akademik Panjang' => [
        'KPP' => 'Kegiatan Permulaan Puasa',
        'LHR' => 'Libur Hari Raya',
        'KT1' => 'Kegiatan Tengah Semester 1',
        'KT2' => 'Kegiatan Tengah Semester 2',
        'UA1' => 'Ujian Akhir Semester 1',
        'UA2' => 'Ujian Akhir Semester 2'
    ]
];

$holiday_descriptions = ['AHD' => 'Hari Ahad 1 Bulan'];
foreach ($holiday_categories as $cat => $items) {
    foreach ($items as $code => $desc) {
        $holiday_descriptions[$code] = $desc;
    }
}

// C. Handler POST Simpan Pengaturan
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Handle Hari Ahad 1 Bulan
    if (isset($_POST['hari_ahad_1_bulan'])) {
        $hari_ahad = $conn->real_escape_string($_POST['hari_ahad_1_bulan']);
        if (!empty($hari_ahad)) {
            $conn->query("INSERT INTO kalender_akademik (tanggal, status_hari, keterangan) 
                          VALUES ('$hari_ahad', 'AHD', 'Hari Ahad 1 Bulan')
                          ON DUPLICATE KEY UPDATE status_hari = 'AHD', keterangan = 'Hari Ahad 1 Bulan'");
        } else {
            $conn->query("DELETE FROM kalender_akademik WHERE status_hari = 'AHD'");
        }
    }
    
    // 2. Handle Kode Hari Besar & Agenda Lainya (Single Date)
    if (isset($_POST['kode']) && is_array($_POST['kode'])) {
        foreach ($_POST['kode'] as $code => $date) {
            $code = $conn->real_escape_string($code);
            $date = $conn->real_escape_string($date);
            
            if (isset($holiday_descriptions[$code])) {
                $desc = $conn->real_escape_string($holiday_descriptions[$code]);
                
                // Hapus entri lama untuk kode ini
                $conn->query("DELETE FROM kalender_akademik WHERE status_hari = '$code'");
                
                if (!empty($date)) {
                    // Simpan entri baru
                    $conn->query("INSERT INTO kalender_akademik (tanggal, status_hari, keterangan) 
                                  VALUES ('$date', '$code', '$desc')
                                  ON DUPLICATE KEY UPDATE status_hari = '$code', keterangan = '$desc'");
                }
            }
        }
    }
    
    // 3. Handle Agenda Akademik Panjang (Date Ranges)
    if (isset($_POST['range_start']) && is_array($_POST['range_start']) && isset($_POST['range_end']) && is_array($_POST['range_end'])) {
        foreach ($holiday_categories['Agenda Akademik Panjang'] as $code => $desc) {
            $start = $_POST['range_start'][$code] ?? '';
            $end = $_POST['range_end'][$code] ?? '';
            
            // Hapus entri lama untuk kode ini
            $code_escaped = $conn->real_escape_string($code);
            $conn->query("DELETE FROM kalender_akademik WHERE status_hari = '$code_escaped'");
            
            if (!empty($start) || !empty($end)) {
                $start_date = !empty($start) ? $start : $end;
                $end_date = !empty($end) ? $end : $start;
                
                // Pastikan start_date <= end_date
                if (strtotime($start_date) > strtotime($end_date)) {
                    $tmp = $start_date;
                    $start_date = $end_date;
                    $end_date = $tmp;
                }
                
                $desc_escaped = $conn->real_escape_string($desc);
                
                $curr_ts = strtotime($start_date);
                $end_ts = strtotime($end_date);
                while ($curr_ts <= $end_ts) {
                    $curr_date = date('Y-m-d', $curr_ts);
                    $conn->query("INSERT INTO kalender_akademik (tanggal, status_hari, keterangan) 
                                  VALUES ('$curr_date', '$code_escaped', '$desc_escaped')
                                  ON DUPLICATE KEY UPDATE status_hari = '$code_escaped', keterangan = '$desc_escaped'");
                    $curr_ts = strtotime("+1 day", $curr_ts);
                }
            }
        }
    }
    
    header("Location: master-kalender.php?sukses=1");
    exit;
}

// D. Ambil data Hari Ahad 1 Bulan yang sudah tersimpan
$res_ahad = $conn->query("SELECT tanggal FROM kalender_akademik WHERE status_hari = 'AHD' LIMIT 1");
$val_ahad = "";
if ($res_ahad && $res_ahad->num_rows > 0) {
    $row_ahad = $res_ahad->fetch_assoc();
    $val_ahad = $row_ahad['tanggal'];
}

// E. Ambil semua data tanggal libur dari database
$overrides = [];
$code_to_date = [];
$code_ranges = [];
$res_overrides = $conn->query("SELECT tanggal, status_hari FROM kalender_akademik");
if ($res_overrides) {
    while ($row = $res_overrides->fetch_assoc()) {
        $overrides[$row['tanggal']] = $row['status_hari'];
        
        $code = $row['status_hari'];
        $date = $row['tanggal'];
        
        $code_to_date[$code] = $date;
        
        if (!isset($code_ranges[$code])) {
            $code_ranges[$code] = ['start' => $date, 'end' => $date];
        } else {
            if ($date < $code_ranges[$code]['start']) {
                $code_ranges[$code]['start'] = $date;
            }
            if ($date > $code_ranges[$code]['end']) {
                $code_ranges[$code]['end'] = $date;
            }
        }
    }
}

// F. Jika ada Ahad acuan yang sudah diset, propagasikan setiap 7 hari ke depan
if (!empty($val_ahad)) {
    $start_date = "2026-07-01";
    $end_date = "2027-07-31"; // Rentang kalender akademik
    
    $target_ahad_ts = strtotime($val_ahad);
    $current_ts = strtotime($start_date);
    $end_ts = strtotime($end_date);
    
    while ($current_ts <= $end_ts) {
        $date_str = date('Y-m-d', $current_ts);
        $m_num = (int)date('m', $current_ts);
        $d_num = (int)date('d', $current_ts);
        $y_num = (int)date('Y', $current_ts);
        
        // Hanya propagasikan ke tanggal-tanggal yang valid
        if (checkdate($m_num, $d_num, $y_num)) {
            $diff_seconds = $current_ts - $target_ahad_ts;
            $diff_days = round($diff_seconds / 86400);
            
            if ($diff_days >= 0 && $diff_days % 7 == 0) {
                // Jangan override jika tanggal tersebut sudah diset sebagai hari khusus di DB
                if (!isset($overrides[$date_str]) || $overrides[$date_str] === 'AHD') {
                    $overrides[$date_str] = 'AHD';
                }
            }
        }
        $current_ts = strtotime("+1 day", $current_ts);
    }
}

$months = [
    'JULI 2026', 'AGUSTUS 2026', 'SEPTEMBER 2026', 'OKTOBER 2026', 'NOVEMBER 2026', 'DESEMBER 2026',
    'JANUARI 2027', 'FEBRUARI 2027', 'MARET 2027', 'APRIL 2027', 'MEI 2027', 'JUNI 2027', 'JULI 2027'
];

$month_map = [
    'JULI' => 7, 'AGUSTUS' => 8, 'SEPTEMBER' => 9, 'OKTOBER' => 10, 'NOVEMBER' => 11, 'DESEMBER' => 12,
    'JANUARI' => 1, 'FEBRUARI' => 2, 'MARET' => 3, 'APRIL' => 4, 'MEI' => 5, 'JUNI' => 6
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
            width: 28px;
            height: 28px;
            border: 1px solid #d1d5db;
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
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-calendar-alt text-amber-500 mr-2"></i>Master Kalender Akademik</h1>
                <p class="text-gray-500 mt-1">Format tabel kosong 33 kolom dan 15 baris (termasuk header).</p>
            </div>

            <?php if (isset($_GET['sukses'])): ?>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center max-w-7xl mx-auto text-left text-sm">
                    <i class="fas fa-check-circle mr-2"></i> Pengaturan Hari Ahad berhasil disimpan!
                </div>
            <?php endif; ?>

            <!-- CARD WIDGET UNTUK TABEL GRID -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-x-auto max-w-7xl mx-auto mb-8">
                <div class="min-w-[1020px]">
                    <table class="w-full border-collapse border border-gray-300">
                        <thead>
                            <!-- BARIS 1 (Header Utama) -->
                            <tr class="bg-amber-900 text-white font-bold text-xs">
                                <th class="border border-amber-950 px-2 py-2 text-center" rowspan="2" style="width: 35px;">No</th>
                                <th class="border border-amber-950 px-4 py-2 text-left" rowspan="2" style="width: 150px;">BULAN</th>
                                <th class="border border-amber-950 py-2 text-center" colspan="31">TANGGAL</th>
                            </tr>
                            <!-- BARIS 2 (Header Angka Tanggal) -->
                            <tr class="bg-amber-800 text-white font-bold text-[10px]">
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <th class="border border-amber-900 text-center py-1.5" style="width: 28px;"><?= $d ?></th>
                                <?php endfor; ?>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <!-- BARIS 3 s/d 15 (13 baris bulan kosong / terisi AHD) -->
                            <?php 
                            $no = 1;
                            foreach ($months as $m): 
                                $parts = explode(' ', $m);
                                $month_name = $parts[0];
                                $year = (int)$parts[1];
                                $month_num = $month_map[$month_name];
                            ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="border border-gray-300 text-center font-bold bg-amber-50 text-amber-900 py-2"><?= $no++ ?></td>
                                    <td class="border border-gray-300 font-bold bg-amber-50 text-amber-900 px-3 py-2 whitespace-nowrap text-left text-xs"><?= $m ?></td>
                                    <?php for ($day = 1; $day <= 31; $day++): 
                                        if (!checkdate($month_num, $day, $year)):
                                    ?>
                                            <td class="border border-gray-300 grid-cell bg-black"></td>
                                    <?php 
                                        else:
                                            $date_str = sprintf("%04d-%02d-%02d", $year, $month_num, $day);
                                            if (isset($overrides[$date_str])):
                                                $status_code = $overrides[$date_str];
                                                $desc = $holiday_descriptions[$status_code] ?? 'Hari Libur/Agenda';
                                                
                                                // Tentukan warna cell berdasarkan status_code/kategori
                                                $bg_color = 'bg-gray-400 text-white';
                                                $show_code = true;
                                                
                                                if ($status_code === 'AHD') {
                                                    $bg_color = 'bg-red-700 text-white font-extrabold';
                                                } elseif (in_array($status_code, ['HUT', 'HBI', 'PCS'])) {
                                                    $bg_color = 'bg-red-600 text-white font-bold'; // Hari Besar Nasional (merah)
                                                } elseif (in_array($status_code, ['MLD', 'IMN', 'IDF', 'IDA', 'TBI'])) {
                                                    $bg_color = 'bg-green-600 text-white font-bold'; // Hari Besar Islam (hijau)
                                                } elseif (in_array($status_code, ['KS1', 'KS2', 'AS1', 'AS2', 'RMD', 'UJK', 'LS1', 'LS2', 'CTB', 'KPP', 'LHR', 'KT1', 'KT2', 'UA1', 'UA2'])) {
                                                    $bg_color = 'bg-sky-300 text-black font-bold'; // Agenda Akademik & Panjang (biru terang)
                                                } elseif (in_array($status_code, ['TBM', 'NTL', 'IML', 'NYP', 'WFT', 'PSK', 'ISA', 'WSK'])) {
                                                    $bg_color = 'bg-gray-400 text-transparent font-normal'; // Hari Besar Agama Lain (abu-abu, tanpa kode)
                                                    $show_code = false;
                                                }
                                    ?>
                                                <td class="border border-gray-300 grid-cell <?= $bg_color ?> text-center align-middle text-[9px] cursor-help" title="<?= $status_code ?> - <?= htmlspecialchars($desc) ?>"><?= $show_code ? $status_code : '' ?></td>
                                    <?php 
                                            else:
                                    ?>
                                                <td class="border border-gray-300 grid-cell bg-white"></td>
                                    <?php 
                                            endif;
                                        endif;
                                    endfor; 
                                    ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- LEGENDA KALENDER AKADEMIK -->
                <div class="mt-6 border-t border-gray-100 pt-4">
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Legenda Kalender</h4>
                    <div class="flex flex-wrap gap-4 text-xs font-medium">
                        <div class="flex items-center"><span class="w-6 h-6 rounded bg-red-700 text-white flex items-center justify-center font-extrabold mr-2 text-[9px]">AHD</span> Ahad (Mingguan)</div>
                        <div class="flex items-center"><span class="w-6 h-6 rounded bg-red-600 text-white flex items-center justify-center font-bold mr-2 text-[9px]">HUT/HBI...</span> Hari Besar Nasional</div>
                        <div class="flex items-center"><span class="w-6 h-6 rounded bg-green-600 text-white flex items-center justify-center font-bold mr-2 text-[9px]">MLD/IDF...</span> Hari Besar Islam</div>
                        <div class="flex items-center"><span class="w-6 h-6 rounded bg-sky-300 text-black flex items-center justify-center font-bold mr-2 text-[9px]">AS1/KS1...</span> Agenda Akademik</div>
                        <div class="flex items-center"><span class="w-6 h-6 rounded bg-gray-400 mr-2 text-[9px]"></span> Hari Besar Agama Lain (Marker Saja)</div>
                    </div>
                </div>
            </div>

            <!-- FORM PENGATURAN KALENDER AKADEMIK -->
            <form action="" method="POST" class="max-w-7xl mx-auto space-y-6 text-left mb-8">
                <!-- GRID FOR THE CARDS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    
                    <!-- CARD 1: HARI BESAR NASIONAL -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center">
                                <i class="fas fa-flag text-red-500 mr-2"></i> Hari Besar Nasional
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1" for="hari_ahad_1_bulan">
                                        Hari Ahad 1 Bulan
                                    </label>
                                    <input type="date" name="hari_ahad_1_bulan" id="hari_ahad_1_bulan" value="<?= $val_ahad ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-ahd transition-colors duration-200 <?= !empty($val_ahad) ? 'bg-red-700 text-white font-extrabold' : 'bg-white text-gray-800' ?>">
                                </div>
                                <hr class="border-gray-100">
                                <?php foreach ($holiday_categories['Hari Besar Nasional'] as $code => $desc): ?>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1" for="kode_<?= $code ?>">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700 font-bold mr-1.5"><?= $code ?></span><?= $desc ?>
                                        </label>
                                        <input type="date" name="kode[<?= $code ?>]" id="kode_<?= $code ?>" value="<?= $code_to_date[$code] ?? '' ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-nasional transition-colors duration-200 <?= !empty($code_to_date[$code]) ? 'bg-red-600 text-white font-bold' : 'bg-white text-gray-800' ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: HARI BESAR ISLAM -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center">
                                <i class="fas fa-moon text-emerald-500 mr-2"></i> Hari Besar Islam
                            </h3>
                            <div class="space-y-4">
                                <?php foreach ($holiday_categories['Hari Besar Islam'] as $code => $desc): ?>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1" for="kode_<?= $code ?>">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700 font-bold mr-1.5"><?= $code ?></span><?= $desc ?>
                                        </label>
                                        <input type="date" name="kode[<?= $code ?>]" id="kode_<?= $code ?>" value="<?= $code_to_date[$code] ?? '' ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-islam transition-colors duration-200 <?= !empty($code_to_date[$code]) ? 'bg-green-600 text-white font-bold' : 'bg-white text-gray-800' ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 3: HARI BESAR AGAMA LAIN -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center">
                                <i class="fas fa-star-of-david text-amber-500 mr-2"></i> Hari Besar Agama Lain
                            </h3>
                            <div class="space-y-4">
                                <?php foreach ($holiday_categories['Hari Besar Agama Lain'] as $code => $desc): ?>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1" for="kode_<?= $code ?>">
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 font-bold mr-1.5"><?= $code ?></span><?= $desc ?>
                                        </label>
                                        <input type="date" name="kode[<?= $code ?>]" id="kode_<?= $code ?>" value="<?= $code_to_date[$code] ?? '' ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-lain transition-colors duration-200 <?= !empty($code_to_date[$code]) ? 'bg-gray-400 text-white font-bold' : 'bg-white text-gray-800' ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- CARD 4: AGENDA AKADEMIK (GRID LAYOUT) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center">
                        <i class="fas fa-graduation-cap text-indigo-500 mr-2"></i> Agenda Akademik
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <?php foreach ($holiday_categories['Agenda Akademik'] as $code => $desc): ?>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1 truncate" for="kode_<?= $code ?>" title="<?= $desc ?>">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-indigo-100 text-indigo-700 font-bold mr-1.5"><?= $code ?></span><?= $desc ?>
                                </label>
                                <input type="date" name="kode[<?= $code ?>]" id="kode_<?= $code ?>" value="<?= $code_to_date[$code] ?? '' ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-akademik transition-colors duration-200 <?= !empty($code_to_date[$code]) ? 'bg-sky-300 text-black font-bold' : 'bg-white text-gray-800' ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- CARD 5: AGENDA AKADEMIK PANJANG (GRID LAYOUT) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 text-base mb-4 flex items-center">
                        <i class="fas fa-calendar-alt text-indigo-500 mr-2"></i> Agenda Akademik Panjang
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        <?php foreach ($holiday_categories['Agenda Akademik Panjang'] as $code => $desc): ?>
                            <div class="border border-gray-100 rounded-lg p-3 bg-slate-50/50">
                                <label class="block text-xs font-bold text-gray-700 mb-2 truncate" for="kode_<?= $code ?>_start" title="<?= $desc ?>">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-indigo-200 text-indigo-800 font-bold mr-1.5"><?= $code ?></span><?= $desc ?>
                                </label>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <span class="text-[9px] text-gray-500 block mb-0.5">Tanggal Awal</span>
                                        <input type="date" name="range_start[<?= $code ?>]" id="kode_<?= $code ?>_start" value="<?= $code_ranges[$code]['start'] ?? '' ?>" class="w-full px-2 py-1 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-akademik transition-colors duration-200 <?= !empty($code_ranges[$code]['start']) ? 'bg-sky-300 text-black font-bold' : 'bg-white text-gray-800' ?>">
                                    </div>
                                    <div>
                                        <span class="text-[9px] text-gray-500 block mb-0.5">Tanggal Akhir</span>
                                        <input type="date" name="range_end[<?= $code ?>]" id="kode_<?= $code ?>_end" value="<?= $code_ranges[$code]['end'] ?? '' ?>" class="w-full px-2 py-1 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 date-input-akademik transition-colors duration-200 <?= !empty($code_ranges[$code]['end']) ? 'bg-sky-300 text-black font-bold' : 'bg-white text-gray-800' ?>">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex justify-end">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2.5 px-8 rounded-lg text-sm shadow-md transition-all duration-200 hover:-translate-y-0.5 flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Pengaturan Kalender
                    </button>
                </div>
            </form>
        </main>
    </div>

    <!-- JS DYNAMIC COLOR-CODING FOR DATE INPUTS -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function updateInputStyle(input, type) {
                // Reset classes
                input.classList.remove(
                    'bg-red-700', 'bg-red-600', 'bg-green-600', 'bg-gray-400', 'bg-sky-300', 
                    'text-white', 'text-black', 'text-gray-800', 'font-extrabold', 'font-bold', 'bg-white'
                );
                
                if (input.value) {
                    if (type === 'ahd') {
                        input.classList.add('bg-red-700', 'text-white', 'font-extrabold');
                    } else if (type === 'nasional') {
                        input.classList.add('bg-red-600', 'text-white', 'font-bold');
                    } else if (type === 'islam') {
                        input.classList.add('bg-green-600', 'text-white', 'font-bold');
                    } else if (type === 'lain') {
                        input.classList.add('bg-gray-400', 'text-white', 'font-bold');
                    } else if (type === 'akademik') {
                        input.classList.add('bg-sky-300', 'text-black', 'font-bold');
                    }
                } else {
                    input.classList.add('bg-white', 'text-gray-800');
                }
            }

            // Bind events for ahd
            document.querySelectorAll('.date-input-ahd').forEach(el => {
                el.addEventListener('change', () => updateInputStyle(el, 'ahd'));
            });

            // Bind events for nasional
            document.querySelectorAll('.date-input-nasional').forEach(el => {
                el.addEventListener('change', () => updateInputStyle(el, 'nasional'));
            });

            // Bind events for islam
            document.querySelectorAll('.date-input-islam').forEach(el => {
                el.addEventListener('change', () => updateInputStyle(el, 'islam'));
            });

            // Bind events for lain
            document.querySelectorAll('.date-input-lain').forEach(el => {
                el.addEventListener('change', () => updateInputStyle(el, 'lain'));
            });

            // Bind events for akademik
            document.querySelectorAll('.date-input-akademik').forEach(el => {
                el.addEventListener('change', () => updateInputStyle(el, 'akademik'));
            });
        });
    </script>
</body>
</html>
