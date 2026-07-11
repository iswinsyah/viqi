<?php
// Prevent caching (Bypass Litespeed/Cloudflare/Browser Caches)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
$is_logged_in = false;
$sidebar = 'sidebar.php';
$active_menu = 'kalender_akademik'; // to highlight the menu item in sidebar

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $is_logged_in = true;
    $sidebar = 'sidebar.php';
} elseif (isset($_SESSION['ustadz_logged_in']) && $_SESSION['ustadz_logged_in'] === true) {
    $is_logged_in = true;
    $sidebar = 'sidebar-hr.php';
} elseif (isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true) {
    $is_logged_in = true;
    $sidebar = 'sidebar-santri.php';
} elseif (isset($_SESSION['orangtua_logged_in']) && $_SESSION['orangtua_logged_in'] === true) {
    $is_logged_in = true;
    $sidebar = 'sidebar-orangtua.php';
} elseif (isset($_SESSION['yayasan2_logged_in']) && $_SESSION['yayasan2_logged_in'] === true) {
    $is_logged_in = true;
    $sidebar = 'yayasan2/sidebar.php';
}

if (!$is_logged_in) {
    header("Location: login.php");
    exit;
}

require_once 'koneksi.php';

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
        'UJK' => 'Ujian Kesetaraan',
        'PLH' => 'Penjemputan Santri Libur Hari Raya',
        'KPH' => 'Kedatangan Santri Pasca Hari Raya',
        'RP1' => 'Terima Rapot & Penjemputan Semester 1',
        'RP2' => 'Terima Rapot & Penjemputan Semester 2'
    ],
    'Agenda Akademik Panjang' => [
        'KPP' => 'Kegiatan Permulaan Puasa',
        'LHR' => 'Libur Hari Raya',
        'KT1' => 'Kegiatan Tengah Semester 1',
        'KT2' => 'Kegiatan Tengah Semester 2',
        'UA1' => 'Ujian Akhir Semester 1',
        'UA2' => 'Ujian Akhir Semester 2',
        'LS1' => 'Libur Semester 1',
        'LS2' => 'Libur Semester 2'
    ]
];

$holiday_descriptions = ['AHD' => 'Hari Ahad 1 Bulan'];
foreach ($holiday_categories as $cat => $items) {
    foreach ($items as $code => $desc) {
        $holiday_descriptions[$code] = $desc;
    }
}

// B. Ambil data Hari Ahad 1 Bulan yang sudah tersimpan
$res_ahad = $conn->query("SELECT tanggal FROM kalender_akademik WHERE status_hari = 'AHD' LIMIT 1");
$val_ahad = "";
if ($res_ahad && $res_ahad->num_rows > 0) {
    $row_ahad = $res_ahad->fetch_assoc();
    $val_ahad = $row_ahad['tanggal'];
}

// C. Ambil semua data tanggal libur dari database
$overrides = [];
$res_overrides = $conn->query("SELECT tanggal, status_hari FROM kalender_akademik");
if ($res_overrides) {
    while ($row = $res_overrides->fetch_assoc()) {
        $overrides[$row['tanggal']] = $row['status_hari'];
    }
}

// D. Jika ada Ahad acuan yang sudah diset, propagasikan setiap 7 hari ke depan
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
    <title>Kalender Akademik Pondok | Villa Quran</title>
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
    <?php include $sidebar; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <?php 
                // Tentukan button toggler sidebar berdasarkan sidebar yang di-include
                $btn_id = 'open-sidebar';
                if ($sidebar == 'sidebar-hr.php') $btn_id = 'open-sidebar-hr';
                elseif ($sidebar == 'sidebar-santri.php') $btn_id = 'open-sidebar-santri';
                elseif ($sidebar == 'sidebar-orangtua.php') $btn_id = 'open-sidebar-orangtua';
                ?>
                <button id="<?= $btn_id ?>" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Kalender Akademik</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-calendar-alt text-amber-500 mr-2"></i>Kalender Akademik</h1>
                <p class="text-gray-500 mt-1">Rentang jadwal agenda dan hari besar akademik selama periode 2026/2027.</p>
            </div>

            <!-- CARD WIDGET UNTUK TABEL GRID -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-x-auto max-w-7xl mx-auto mb-8 text-left">
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
                                                } elseif (in_array($status_code, ['KS1', 'KS2', 'AS1', 'AS2', 'UJK', 'PLH', 'KPH', 'RP1', 'RP2', 'KPP', 'LHR', 'KT1', 'KT2', 'UA1', 'UA2', 'LS1', 'LS2'])) {
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
        </main>
    </div>
</body>
</html>
