<?php
// yayasan2/pembukuan.php
require_once 'auth.php';
require_once '../koneksi.php';
require_once 'setup-pembukuan.php'; // Inisialisasi DB jika belum ada

$active_menu = 'pembukuan';

// Ambil bulan & tahun hari ini untuk tunggakan SPP
$bulan_indo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$bulan_sekarang = $bulan_indo[(int)date('n')];
$tahun_sekarang = date('Y');

// 1. AJAX Handler: Kirim Pengingat WA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'kirim_pengingat') {
    header('Content-Type: application/json');
    $no_wa = trim($_POST['no_wa']);
    $nama_santri = trim($_POST['nama_santri']);
    $nama_ortu = trim($_POST['nama_ortu']);
    $bulan = trim($_POST['bulan']);
    $tahun = trim($_POST['tahun']);
    
    if (empty($no_wa)) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak tersedia!']);
        exit;
    }
    
    // Bersihkan nomor WA
    $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
    if (substr($no_wa, 0, 1) === '0') {
        $no_wa = '62' . substr($no_wa, 1);
    }
    
    $pesan = "Assalamu'alaikum Wr. Wb. Yth. Bapak/Ibu $nama_ortu,\n\n"
           . "Semoga Allah senantiasa melimpahkan kesehatan, keberkahan, dan kelapangan rezeki kepada keluarga.\n\n"
           . "Kami dari bagian Keuangan Yayasan Villa Quran menginformasikan pengingat kewajiban SPP bulanan untuk ananda *$nama_santri* periode *$bulan $tahun*.\n\n"
           . "Pembayaran dapat dikirimkan melalui transfer ke rekening resmi Yayasan:\n"
           . "*Bank Syariah Indonesia (BSI)*\n"
           . "*No Rekening: 7700889911*\n"
           . "*Atas Nama: Villa Quran Indonesia*\n\n"
           . "Mohon kirimkan konfirmasi pembayaran beserta struk transfer melalui menu Ruang Orang Tua setelah pembayaran berhasil. Jika Bapak/Ibu sudah melakukan transfer, silakan abaikan pesan ini.\n\n"
           . "Jazaakumullahu Khairan Katsiran.\n\n"
           . "Wassalamu'alaikum Wr. Wb.\n"
           . "-- Bendahara Yayasan Villa Quran --";
           
    if (file_exists(dirname(__DIR__) . '/config-key.php')) {
        require_once dirname(__DIR__) . '/config-key.php';
    }
    $FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";
    
    $waFd = ['target' => $no_wa, 'message' => $pesan];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.fonnte.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($waFd),
        CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
        CURLOPT_TIMEOUT => 20
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    if ($err) {
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan jaringan: ' . $err]);
    } else {
        $res_data = json_decode($res, true);
        if (isset($res_data['status']) && $res_data['status'] == true) {
            echo json_encode(['status' => 'success', 'message' => 'Pesan pengingat berhasil dikirim!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengirim: ' . ($res_data['reason'] ?? 'Gagal memproses WA')]);
        }
    }
    exit;
}

// 2. Handler Simpan Transaksi (Double Entry)
$pesan_sukses = '';
$pesan_error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_transaksi') {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $no_bukti = $conn->real_escape_string($_POST['no_bukti']);
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    $debit_akun_id = (int)$_POST['debit_akun_id'];
    $kredit_akun_id = (int)$_POST['kredit_akun_id'];
    $lembaga_id = (int)$_POST['lembaga_id'];
    $jumlah = (double)$_POST['jumlah'];

    if ($jumlah <= 0 || empty($tanggal) || empty($no_bukti) || empty($debit_akun_id) || empty($kredit_akun_id) || empty($lembaga_id)) {
        $pesan_error = "Harap lengkapi semua isian formulir dengan benar!";
    } elseif ($debit_akun_id === $kredit_akun_id) {
        $pesan_error = "Akun debit dan kredit tidak boleh sama!";
    } else {
        $conn->begin_transaction();
        try {
            $sql_j = "INSERT INTO keuangan_jurnal (tanggal, no_bukti, keterangan) VALUES ('$tanggal', '$no_bukti', '$keterangan')";
            if (!$conn->query($sql_j)) throw new Exception($conn->error);
            $jurnal_id = $conn->insert_id;

            // Baris Debit
            $sql_d = "INSERT INTO keuangan_jurnal_detail (jurnal_id, akun_id, debit, kredit, lembaga_id) 
                      VALUES ($jurnal_id, $debit_akun_id, $jumlah, 0.00, $lembaga_id)";
            if (!$conn->query($sql_d)) throw new Exception($conn->error);

            // Baris Kredit
            $sql_k = "INSERT INTO keuangan_jurnal_detail (jurnal_id, akun_id, debit, kredit, lembaga_id) 
                      VALUES ($jurnal_id, $kredit_akun_id, 0.00, $jumlah, $lembaga_id)";
            if (!$conn->query($sql_k)) throw new Exception($conn->error);

            $conn->commit();
            $pesan_sukses = "Transaksi berhasil dicatat ke sistem pembukuan terpusat!";
        } catch (Exception $e) {
            $conn->rollback();
            $pesan_error = "Gagal mencatat transaksi: " . $e->getMessage();
        }
    }
}

// Handler Tambah Akun COA Baru
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_akun') {
    $kode_akun = $conn->real_escape_string(trim($_POST['kode_akun']));
    $nama_akun = $conn->real_escape_string(trim($_POST['nama_akun']));
    $tipe_akun = $conn->real_escape_string($_POST['tipe_akun']);

    if (empty($kode_akun) || empty($nama_akun) || empty($tipe_akun)) {
        $pesan_error = "Harap lengkapi semua isian untuk membuat akun baru!";
    } else {
        // Cek apakah kode akun sudah digunakan
        $check = $conn->query("SELECT id FROM keuangan_akun WHERE kode_akun = '$kode_akun'");
        if ($check && $check->num_rows > 0) {
            $pesan_error = "Kode Akun $kode_akun sudah terdaftar!";
        } else {
            $sql = "INSERT INTO keuangan_akun (kode_akun, nama_akun, tipe_akun) VALUES ('$kode_akun', '$nama_akun', '$tipe_akun')";
            if ($conn->query($sql)) {
                $pesan_sukses = "Akun baru '$kode_akun - $nama_akun' berhasil didaftarkan!";
            } else {
                $pesan_error = "Gagal mendaftarkan akun: " . $conn->error;
            }
        }
    }
}

// Handler Hapus Akun COA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'hapus_akun') {
    $akun_id = (int)$_POST['akun_id'];
    
    // Cek apakah ada jurnal detail yang sudah memakai akun ini
    $check_use = $conn->query("SELECT id FROM keuangan_jurnal_detail WHERE akun_id = $akun_id LIMIT 1");
    if ($check_use && $check_use->num_rows > 0) {
        $pesan_error = "Akun tidak dapat dihapus karena sudah memiliki riwayat transaksi!";
    } else {
        $sql = "DELETE FROM keuangan_akun WHERE id = $akun_id";
        if ($conn->query($sql)) {
            $pesan_sukses = "Akun berhasil dihapus dari Bagan Akun!";
        } else {
            $pesan_error = "Gagal menghapus akun: " . $conn->error;
        }
    }
}

// 3. Load COA & Lembaga untuk form dropdown
$list_akun = $conn->query("SELECT id, kode_akun, nama_akun, tipe_akun FROM keuangan_akun ORDER BY kode_akun ASC")->fetch_all(MYSQLI_ASSOC);
$list_lembaga = $conn->query("SELECT id, nama_lembaga FROM keuangan_lembaga WHERE status = 'aktif' ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

// 4. Kalkulasi Saldo Kas/Bank & Ringkasan Keuangan
$res_coa = $conn->query("
    SELECT a.kode_akun, a.nama_akun, a.tipe_akun, 
           COALESCE(SUM(d.debit), 0) as total_debit, 
           COALESCE(SUM(d.kredit), 0) as total_kredit 
    FROM keuangan_akun a 
    LEFT JOIN keuangan_jurnal_detail d ON a.id = d.akun_id 
    GROUP BY a.id
");
$ringkasan = [];
while ($row = $res_coa->fetch_assoc()) {
    $balance = 0;
    if ($row['tipe_akun'] == 'Aset' || $row['tipe_akun'] == 'Beban') {
        $balance = $row['total_debit'] - $row['total_kredit'];
    } else {
        $balance = $row['total_kredit'] - $row['total_debit'];
    }
    $ringkasan[$row['kode_akun']] = [
        'nama' => $row['nama_akun'],
        'tipe' => $row['tipe_akun'],
        'balance' => $balance
    ];
}

$kas_utama = $ringkasan['1101']['balance'] ?? 0;
$bank_bsi = $ringkasan['1102']['balance'] ?? 0;
$total_kas_bank = $kas_utama + $bank_bsi;

// --- DYNAMIC CASHFLOW CALCULATIONS ---
// 1. Ambil data Pemasukan SPP
$res_spp = $conn->query("SELECT SUM(jumlah) as total FROM pembayaran_spp WHERE status = 'Berhasil'");
$pemasukan_spp = $res_spp ? (double)($res_spp->fetch_assoc()['total'] ?? 0.0) : 0.0;

// 2. Ambil data Jurnal Pendapatan
$res_jurnal_inc = $conn->query("SELECT SUM(d.kredit - d.debit) as total 
    FROM keuangan_jurnal_detail d 
    JOIN keuangan_akun a ON d.akun_id = a.id 
    WHERE a.tipe_akun = 'Pendapatan'");
$pemasukan_jurnal = $res_jurnal_inc ? (double)($res_jurnal_inc->fetch_assoc()['total'] ?? 0.0) : 0.0;

// 3. Ambil data Jurnal Beban/Pengeluaran
$res_jurnal_exp = $conn->query("SELECT SUM(d.debit - d.kredit) as total 
    FROM keuangan_jurnal_detail d 
    JOIN keuangan_akun a ON d.akun_id = a.id 
    WHERE a.tipe_akun = 'Beban'");
$pengeluaran_jurnal = $res_jurnal_exp ? (double)($res_jurnal_exp->fetch_assoc()['total'] ?? 0.0) : 0.0;

// 4. Hitung Gaji Ustadz Offline (Rumus: Total Kelas Sasaran Mapel Offline Non-Musyrif * Tarif Grade A * 4 Pekan)
$sql_offline = "SELECT id, nama_mapel, kategori_mapel, 
    (SELECT COUNT(kelas_id) FROM mapel_kelas_target WHERE mapel_id = master_mapel.id) as jumlah_kelas 
    FROM master_mapel 
    WHERE metode_belajar = 'offline' 
      AND id NOT IN (
          SELECT DISTINCT m_id FROM (
              SELECT COALESCE(mapel_1_id, 0) as m_id FROM kesediaan_mengajar k JOIN akun_ustadz u ON k.ustadz_id = u.id WHERE u.role LIKE '%musyrif%'
              UNION
              SELECT COALESCE(mapel_2_id, 0) as m_id FROM kesediaan_mengajar k JOIN akun_ustadz u ON k.ustadz_id = u.id WHERE u.role LIKE '%musyrif%'
              UNION
              SELECT COALESCE(mapel_3_id, 0) as m_id FROM kesediaan_mengajar k JOIN akun_ustadz u ON k.ustadz_id = u.id WHERE u.role LIKE '%musyrif%'
          ) as tmp
      )";
$res_mapel_offline = $conn->query($sql_offline);

$mapel_offline_count = 0;
$total_kelas_offline = 0;
$mapel_offline_list = [];

if ($res_mapel_offline) {
    $mapel_offline_count = $res_mapel_offline->num_rows;
    while ($row = $res_mapel_offline->fetch_assoc()) {
        $kelas_count = (int)$row['jumlah_kelas'];
        $total_kelas_offline += $kelas_count;
        $mapel_offline_list[] = $row;
    }
}

$res_rate = $conn->query("SELECT gaji_grade_a FROM pengaturan_gaji WHERE id = 1");
$rate_grade_a = 25000;
if ($res_rate && $res_rate->num_rows > 0) {
    $rate_grade_a = (double)$res_rate->fetch_assoc()['gaji_grade_a'];
}

$cost_gaji_offline = $total_kelas_offline * $rate_grade_a * 4;

// 5. Total Akumulasi
$total_pemasukan = $pemasukan_spp + $pemasukan_jurnal;
$total_pengeluaran = $pengeluaran_jurnal + $cost_gaji_offline;
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// Persentase Pengeluaran terhadap Pemasukan
$persen_pengeluaran = $total_pemasukan > 0 ? round(($total_pengeluaran / $total_pemasukan) * 100, 1) : 0;

// Query Jumlah Santri untuk default perencanaan
$res_count_santri = $conn->query("SELECT COUNT(*) as count FROM buku_induk_santri");
$default_santri_count = ($res_count_santri) ? (int)$res_count_santri->fetch_assoc()['count'] : 0;
if ($default_santri_count === 0) {
    $res_count_s = $conn->query("SELECT COUNT(*) as count FROM santri");
    $default_santri_count = ($res_count_s) ? (int)$res_count_s->fetch_assoc()['count'] : 120; // Default fallback
}

// Pendapatan & Beban Bulan Ini
$bulan_ini = date('Y-m');
$res_m = $conn->query("
    SELECT 
        SUM(CASE WHEN a.tipe_akun = 'Pendapatan' THEN d.kredit - d.debit ELSE 0 END) as pendapatan_bulanan,
        SUM(CASE WHEN a.tipe_akun = 'Beban' THEN d.debit - d.kredit ELSE 0 END) as beban_bulanan
    FROM keuangan_jurnal_detail d
    JOIN keuangan_jurnal j ON d.jurnal_id = j.id
    JOIN keuangan_akun a ON d.akun_id = a.id
    WHERE DATE_FORMAT(j.tanggal, '%Y-%m') = '$bulan_ini'
");
$row_m = $res_m->fetch_assoc();
$pendapatan_bulanan = $row_m['pendapatan_bulanan'] ?? 0;
$beban_bulanan = $row_m['beban_bulanan'] ?? 0;

// 5. Query Overdue SPP (Santri Aktif belum bayar bulan ini)
$sql_overdue = "
    SELECT s.id, s.nama_lengkap, s.kelas_sekarang, 
           COALESCE(s.no_whatsapp_ayah, s.no_whatsapp_ibu, s.no_whatsapp_wali, o.no_whatsapp) as no_wa,
           COALESCE(s.nama_ayah, s.nama_ibu, s.nama_wali, o.nama_orangtua) as nama_ortu
    FROM buku_induk_santri s
    LEFT JOIN akun_orangtua o ON s.id_orangtua = o.id
    LEFT JOIN pembayaran_spp p ON s.id = p.santri_id 
        AND p.bulan = '$bulan_sekarang' 
        AND p.tahun = '$tahun_sekarang' 
        AND p.status = 'Berhasil'
    WHERE s.status_santri = 'Aktif' 
      AND p.id IS NULL
    ORDER BY s.nama_lengkap ASC";
$res_overdue = $conn->query($sql_overdue);
$overdue_santri = ($res_overdue) ? $res_overdue->fetch_all(MYSQLI_ASSOC) : [];

// 6. Query Jurnal Buku Besar Terpusat
$sql_jurnal = "
    SELECT j.tanggal, j.no_bukti, j.keterangan, a.kode_akun, a.nama_akun, 
           d.debit, d.kredit, l.nama_lembaga 
    FROM keuangan_jurnal_detail d 
    JOIN keuangan_jurnal j ON d.jurnal_id = j.id 
    JOIN keuangan_akun a ON d.akun_id = a.id 
    JOIN keuangan_lembaga l ON d.lembaga_id = l.id 
    ORDER BY j.tanggal DESC, j.id DESC LIMIT 50";
$recent_transactions = $conn->query($sql_jurnal)->fetch_all(MYSQLI_ASSOC);

// 7. Query Janji Pembayaran Wali Santri (Komitmen Pembayaran)
$sql_promises = "
    SELECT kjb.*, s.nama_lengkap, s.kelas_sekarang,
           COALESCE(s.no_whatsapp_ayah, s.no_whatsapp_ibu, s.no_whatsapp_wali, o.no_whatsapp) as no_wa
    FROM keuangan_janji_bayar kjb
    JOIN buku_induk_santri s ON kjb.santri_id = s.id
    LEFT JOIN akun_orangtua o ON s.id_orangtua = o.id
    ORDER BY kjb.tanggal_janji ASC, kjb.id DESC";
$res_promises = $conn->query($sql_promises);
$payment_promises = ($res_promises) ? $res_promises->fetch_all(MYSQLI_ASSOC) : [];

// 8. Query Data untuk Grafik Keuangan
// 8A. Tren Arus Kas Bulanan (6 Bulan Terakhir)
$sql_cashflow_trend = "
    SELECT DATE_FORMAT(j.tanggal, '%Y-%m') as bulan,
           SUM(CASE WHEN a.tipe_akun = 'Pendapatan' THEN d.kredit - d.debit ELSE 0 END) as pendapatan,
           SUM(CASE WHEN a.tipe_akun = 'Beban' THEN d.debit - d.kredit ELSE 0 END) as beban
    FROM keuangan_jurnal_detail d
    JOIN keuangan_jurnal j ON d.jurnal_id = j.id
    JOIN keuangan_akun a ON d.akun_id = a.id
    GROUP BY DATE_FORMAT(j.tanggal, '%Y-%m')
    ORDER BY DATE_FORMAT(j.tanggal, '%Y-%m') ASC
    LIMIT 6";
$res_cashflow_trend = $conn->query($sql_cashflow_trend);
$cashflow_trend = [];
if ($res_cashflow_trend) {
    while ($row = $res_cashflow_trend->fetch_assoc()) {
        $t = explode('-', $row['bulan']);
        $m_num = (int)$t[1];
        $m_name = isset($bulan_indo[$m_num]) ? substr($bulan_indo[$m_num], 0, 3) : $row['bulan'];
        $cashflow_trend[] = [
            'label' => $m_name . ' ' . $t[0],
            'pendapatan' => (double)$row['pendapatan'],
            'beban' => (double)$row['beban']
        ];
    }
}

// 8B. Distribusi Pendapatan per Lembaga/Divisi
$sql_divisi_dist = "
    SELECT l.nama_lembaga, 
           COALESCE(SUM(d.kredit - d.debit), 0) as total
    FROM keuangan_jurnal_detail d
    JOIN keuangan_akun a ON d.akun_id = a.id
    JOIN keuangan_lembaga l ON d.lembaga_id = l.id
    WHERE a.tipe_akun = 'Pendapatan'
    GROUP BY l.id";
$res_divisi_dist = $conn->query($sql_divisi_dist);
$divisi_distribution = ($res_divisi_dist) ? $res_divisi_dist->fetch_all(MYSQLI_ASSOC) : [];

// 8C. Status Pembayaran SPP (Lunas vs Belum Lunas)
$res_paid_spp = $conn->query("
    SELECT COUNT(id) as total 
    FROM pembayaran_spp 
    WHERE bulan = '$bulan_sekarang' 
      AND tahun = '$tahun_sekarang' 
      AND status = 'Berhasil'
");
$total_paid_spp = $res_paid_spp ? (int)$res_paid_spp->fetch_assoc()['total'] : 0;
$total_unpaid_spp = count($overdue_santri);

// 8D. Saldo Kas & Rekening Bank (Bagan Akun Aset)
$bank_balances = [];
foreach ($ringkasan as $kode => $info) {
    if ($info['tipe'] == 'Aset' && (strpos($kode, '11') === 0)) {
        $bank_balances[] = [
            'nama' => $info['nama'],
            'balance' => $info['balance']
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembukuan Terpusat AI | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #78350f; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #f59e0b; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #92400e; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #4b5563; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        .markdown-body li { margin-bottom: 0.25rem; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-amber-600 flex items-center justify-center text-white font-bold shadow-sm">Y</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center"><i class="fas fa-calculator text-amber-600 mr-2"></i>Pembukuan Terpusat AI</h1>
                    <p class="text-sm text-gray-500 mt-1">Satu Ledger Terpusat untuk semua lembaga dengan kontrol pengawasan dari AI Auditor.</p>
                </div>
            </div>

            <!-- TABS MENU -->
            <div class="flex space-x-2 border-b border-gray-200 mb-6 flex-shrink-0 overflow-x-auto whitespace-nowrap scrollbar-none">
                <button onclick="switchTab('tab-laporan')" id="btn-tab-laporan" class="px-4 py-2.5 text-xs font-bold border-b-2 border-amber-600 text-amber-700 focus:outline-none transition">
                    <i class="fas fa-file-invoice-dollar mr-1"></i> Laporan Terpadu
                </button>
                <button onclick="switchTab('tab-proyeksi')" id="btn-tab-proyeksi" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition">
                    <i class="fas fa-sliders-h mr-1"></i> Perencanaan Kas (Plan)
                </button>
                <button onclick="switchTab('tab-jurnal')" id="btn-tab-jurnal" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition">
                    <i class="fas fa-book-open mr-1"></i> Pencatatan & Jurnal
                </button>
                <button onclick="switchTab('tab-coa')" id="btn-tab-coa" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition">
                    <i class="fas fa-list-ol mr-1"></i> Kelola Akun (COA)
                </button>
                <button onclick="switchTab('tab-reminders')" id="btn-tab-reminders" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition relative">
                    <i class="fas fa-bell mr-1"></i> Piutang & Pengingat SPP
                    <?php if(count($overdue_santri) > 0): ?>
                    <span class="absolute top-1 -right-0.5 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full animate-bounce"><?= count($overdue_santri) ?></span>
                    <?php endif; ?>
                </button>
                <button onclick="switchTab('tab-promises')" id="btn-tab-promises" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition relative">
                    <i class="fas fa-handshake mr-1"></i> Komitmen Janji Bayar
                    <?php if(count($payment_promises) > 0): ?>
                    <span class="absolute top-1 -right-0.5 bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full"><?= count($payment_promises) ?></span>
                    <?php endif; ?>
                </button>
                <button onclick="switchTab('tab-ai-auditor')" id="btn-tab-ai-auditor" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition flex items-center">
                    <i class="fas fa-brain mr-1 text-indigo-500"></i> AI Auditor
                </button>
                <button onclick="switchTab('tab-charts')" id="btn-tab-charts" class="px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition">
                    <i class="fas fa-chart-pie mr-1"></i> Grafik Analisis
                </button>
            </div>

            <!-- CARDS SUMMARY (CASHFLOW HEALTH) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <!-- Total Kas & Bank -->
                <div class="bg-gradient-to-r from-amber-600 to-amber-700 text-white p-5 rounded-xl shadow-md border border-amber-500/20 relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition"></div>
                    <span class="text-xs uppercase tracking-wider text-amber-100 font-semibold">Total Saldo Terpusat</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format($total_kas_bank, 0, ',', '.') ?></h2>
                    <div class="flex justify-between items-center text-xs text-amber-200 mt-3 pt-2 border-t border-white/10">
                        <span>Kas: Rp <?= number_format($kas_utama, 0, ',', '.') ?></span>
                        <span>Bank BSI: Rp <?= number_format($bank_bsi, 0, ',', '.') ?></span>
                    </div>
                </div>

                <!-- Pemasukan Bulan Ini -->
                <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 text-white p-5 rounded-xl shadow-md border border-emerald-500/20 relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition"></div>
                    <span class="text-xs uppercase tracking-wider text-emerald-100 font-semibold">Pemasukan Terdaftar</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></h2>
                    <div class="text-xs text-emerald-200 mt-3 pt-2 border-t border-white/10 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> SPP + Jurnal Pendapatan
                    </div>
                </div>

                <!-- Pengeluaran Bulan Ini -->
                <div class="bg-gradient-to-r from-rose-600 to-rose-700 text-white p-5 rounded-xl shadow-md border border-rose-500/20 relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition"></div>
                    <span class="text-xs uppercase tracking-wider text-rose-100 font-semibold">Pengeluaran Terdaftar</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></h2>
                    <div class="text-xs text-rose-200 mt-3 pt-2 border-t border-white/10 flex items-center">
                        <i class="fas fa-arrow-down mr-1"></i> Beban + Gaji Ustadz Offline
                    </div>
                </div>

                <!-- Cashflow Surplus/Defisit -->
                <?php 
                $surplus = $saldo_bersih;
                $bg_c = $surplus >= 0 ? 'from-teal-600 to-teal-700 border-teal-500/20' : 'from-rose-600 to-rose-700 border-rose-500/20';
                $txt_sub = $surplus >= 0 ? 'Surplus Bersih' : 'Defisit Bersih';
                ?>
                <div class="bg-gradient-to-r <?= $bg_c ?> text-white p-5 rounded-xl shadow-md relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition"></div>
                    <span class="text-xs uppercase tracking-wider text-indigo-100 font-semibold">Status Operasional</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format(abs($surplus), 0, ',', '.') ?></h2>
                    <div class="text-xs text-indigo-200 mt-3 pt-2 border-t border-white/10 flex items-center">
                        <i class="fas <?= $surplus >= 0 ? 'fa-scale-balanced' : 'fa-triangle-exclamation' ?> mr-1.5"></i> <?= $txt_sub ?> Saat Ini
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: LAPORAN TERPADU -->
            <div id="tab-laporan" class="tab-pane block">
                <!-- Sub-tab Selector for Laporan Terpadu -->
                <div class="flex space-x-3 mb-6 bg-amber-50 p-2 rounded-xl border border-amber-200/50 w-fit">
                    <button onclick="switchSubLaporan('sub-laba-rugi')" id="btn-sub-laba-rugi" class="px-4 py-2 text-xs font-bold bg-amber-700 text-white rounded-lg transition-all focus:outline-none shadow-sm">
                        1. Laba Rugi (Profit & Loss)
                    </button>
                    <button onclick="switchSubLaporan('sub-arus-kas')" id="btn-sub-arus-kas" class="px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 rounded-lg transition-all focus:outline-none">
                        2. Arus Kas (Cashflow Aktual)
                    </button>
                    <button onclick="switchSubLaporan('sub-neraca')" id="btn-sub-neraca" class="px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 rounded-lg transition-all focus:outline-none">
                        3. Posisi Aktiva (Neraca/Aset)
                    </button>
                </div>

                <!-- SUB-REPORT 1: LABA RUGI -->
                <div id="sub-laba-rugi" class="sub-report-pane block">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Pendapatan Card -->
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden text-left">
                            <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
                                <h3 class="font-bold text-emerald-800 text-sm flex items-center"><i class="fas fa-arrow-alt-circle-down mr-2 text-emerald-600"></i>Pendapatan (Revenue) - Akrual</h3>
                                <span class="text-xs font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Operasional</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100">
                                    <span class="text-xs text-gray-600">Pendapatan SPP Santri (Tabel Pembayaran)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($pemasukan_spp, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $res_lr_inc = $conn->query("SELECT a.nama_akun, a.kode_akun, SUM(d.kredit - d.debit) as total
                                    FROM keuangan_jurnal_detail d
                                    JOIN keuangan_akun a ON d.akun_id = a.id
                                    WHERE a.tipe_akun = 'Pendapatan'
                                    GROUP BY a.id");
                                $has_lr_inc = false;
                                if ($res_lr_inc):
                                    while($r = $res_lr_inc->fetch_assoc()):
                                        if ($r['total'] == 0) continue;
                                        $has_lr_inc = true;
                                ?>
                                        <div class="flex justify-between items-center py-2.5 border-b border-gray-100 pl-4">
                                            <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i><?= htmlspecialchars($r['nama_akun']) ?> (<?= $r['kode_akun'] ?>)</span>
                                            <span class="text-xs font-semibold text-slate-800">Rp <?= number_format($r['total'], 0, ',', '.') ?></span>
                                        </div>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                                <div class="flex justify-between items-center pt-4 font-bold text-sm text-emerald-800 border-t border-gray-200">
                                    <span>Total Pendapatan:</span>
                                    <span>Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Beban Card -->
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden text-left">
                            <div class="px-6 py-4 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
                                <h3 class="font-bold text-rose-800 text-sm flex items-center"><i class="fas fa-arrow-alt-circle-up mr-2 text-rose-600"></i>Biaya / Beban (Expenses) - Akrual</h3>
                                <span class="text-xs font-bold text-rose-700 bg-rose-100 px-2 py-0.5 rounded">Biaya</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100">
                                    <span class="text-xs text-gray-600">Beban Gaji Ustadz Offline (Estimasi Formula)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($cost_gaji_offline, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $res_lr_exp = $conn->query("SELECT a.nama_akun, a.kode_akun, SUM(d.debit - d.kredit) as total
                                    FROM keuangan_jurnal_detail d
                                    JOIN keuangan_akun a ON d.akun_id = a.id
                                    WHERE a.tipe_akun = 'Beban'
                                    GROUP BY a.id");
                                $has_lr_exp = false;
                                if ($res_lr_exp):
                                    while($r = $res_lr_exp->fetch_assoc()):
                                        if ($r['total'] == 0) continue;
                                        $has_lr_exp = true;
                                ?>
                                        <div class="flex justify-between items-center py-2.5 border-b border-gray-100 pl-4">
                                            <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i><?= htmlspecialchars($r['nama_akun']) ?> (<?= $r['kode_akun'] ?>)</span>
                                            <span class="text-xs font-semibold text-slate-800">Rp <?= number_format($r['total'], 0, ',', '.') ?></span>
                                        </div>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                                <div class="flex justify-between items-center pt-4 font-bold text-sm text-rose-800 border-t border-gray-200">
                                    <span>Total Beban:</span>
                                    <span>Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Surplus Card -->
                    <div class="bg-white rounded-2xl border border-gray-150 shadow-sm p-6 mt-6 flex justify-between items-center text-left">
                        <div>
                            <h4 class="font-extrabold text-gray-800 text-sm">SURPLUS / DEFISIT BERSIH PERIODE BERJALAN</h4>
                            <p class="text-xs text-gray-400 mt-0.5">Selisih laba rugi bersih sekolah dan filantropi dari total pendapatan dikurangi beban operasional.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black <?= $saldo_bersih >= 0 ? 'text-teal-600' : 'text-rose-700' ?>">
                                Rp <?= number_format($saldo_bersih, 0, ',', '.') ?>
                            </span>
                            <span class="block text-[10px] text-gray-500 font-bold uppercase mt-1">Status: <?= $saldo_bersih >= 0 ? 'Surplus' : 'Defisit' ?></span>
                        </div>
                    </div>
                </div>

                <!-- SUB-REPORT 2: ARUS KAS -->
                <div id="sub-arus-kas" class="sub-report-pane hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Kas Masuk Card -->
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden text-left">
                            <div class="px-6 py-4 bg-teal-50 border-b border-teal-100 flex items-center justify-between">
                                <h3 class="font-bold text-teal-800 text-sm flex items-center"><i class="fas fa-circle-down mr-2 text-teal-600"></i>Aliran Kas Masuk (Cash Inflows)</h3>
                                <span class="text-xs font-bold text-teal-700 bg-teal-100 px-2 py-0.5 rounded">Kas Masuk</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100">
                                    <span class="text-xs text-gray-600">Penerimaan Tunai SPP Santri</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($pemasukan_spp, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $res_cf_in = $conn->query("SELECT a.nama_akun, a.kode_akun, SUM(d.kredit - d.debit) as total
                                    FROM keuangan_jurnal_detail d
                                    JOIN keuangan_akun a ON d.akun_id = a.id
                                    WHERE a.tipe_akun = 'Pendapatan'
                                    GROUP BY a.id");
                                if ($res_cf_in):
                                    while($r = $res_cf_in->fetch_assoc()):
                                        if ($r['total'] == 0) continue;
                                ?>
                                        <div class="flex justify-between items-center py-2.5 border-b border-gray-100 pl-4">
                                            <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Penerimaan <?= htmlspecialchars($r['nama_akun']) ?></span>
                                            <span class="text-xs font-semibold text-slate-800">Rp <?= number_format($r['total'], 0, ',', '.') ?></span>
                                        </div>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                                <div class="flex justify-between items-center pt-4 font-bold text-sm text-teal-800 border-t border-gray-200">
                                    <span>Total Kas Masuk:</span>
                                    <span>Rp <?= number_format($total_pemasukan, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Kas Keluar Card -->
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden text-left">
                            <div class="px-6 py-4 bg-orange-50 border-b border-orange-100 flex items-center justify-between">
                                <h3 class="font-bold text-orange-800 text-sm flex items-center"><i class="fas fa-circle-up mr-2 text-orange-600"></i>Aliran Kas Keluar (Cash Outflows)</h3>
                                <span class="text-xs font-bold text-orange-700 bg-orange-100 px-2 py-0.5 rounded">Kas Keluar</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100">
                                    <span class="text-xs text-gray-600">Pengeluaran Tunai Gaji Offline</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($cost_gaji_offline, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $res_cf_out = $conn->query("SELECT a.nama_akun, a.kode_akun, SUM(d.debit - d.kredit) as total
                                    FROM keuangan_jurnal_detail d
                                    JOIN keuangan_akun a ON d.akun_id = a.id
                                    WHERE a.tipe_akun = 'Beban'
                                    GROUP BY a.id");
                                if ($res_cf_out):
                                    while($r = $res_cf_out->fetch_assoc()):
                                        if ($r['total'] == 0) continue;
                                ?>
                                        <div class="flex justify-between items-center py-2.5 border-b border-gray-100 pl-4">
                                            <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Pembayaran <?= htmlspecialchars($r['nama_akun']) ?></span>
                                            <span class="text-xs font-semibold text-slate-800">Rp <?= number_format($r['total'], 0, ',', '.') ?></span>
                                        </div>
                                <?php
                                    endwhile;
                                endif;
                                ?>
                                <div class="flex justify-between items-center pt-4 font-bold text-sm text-orange-800 border-t border-gray-200">
                                    <span>Total Kas Keluar:</span>
                                    <span>Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Net Cash Card -->
                    <div class="bg-white rounded-2xl border border-gray-150 shadow-sm p-6 mt-6 flex justify-between items-center text-left">
                        <div>
                            <h4 class="font-extrabold text-gray-800 text-sm">KENAIKAN / PENURUNAN KAS BERSIH (NET CASHFLOW)</h4>
                            <p class="text-xs text-gray-400 mt-0.5">Jumlah mutasi uang kas fisik bersih yang benar-benar tersisa atau terpakai pada periode ini.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black <?= $saldo_bersih >= 0 ? 'text-teal-600' : 'text-rose-700' ?>">
                                Rp <?= number_format($saldo_bersih, 0, ',', '.') ?>
                            </span>
                            <span class="block text-[10px] text-gray-500 font-bold uppercase mt-1">Saldo Kas: <?= $saldo_bersih >= 0 ? 'Surplus' : 'Defisit' ?></span>
                        </div>
                    </div>
                </div>

                <!-- SUB-REPORT 3: NERACA -->
                <div id="sub-neraca" class="sub-report-pane hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Aktiva (Assets) Card -->
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden text-left">
                            <div class="px-6 py-4 bg-blue-50 border-b border-blue-100 flex items-center justify-between">
                                <h3 class="font-bold text-blue-800 text-sm flex items-center"><i class="fas fa-coins mr-2 text-blue-600"></i>Aktiva (Aset / Kekayaan)</h3>
                                <span class="text-xs font-bold text-blue-700 bg-blue-100 px-2 py-0.5 rounded">Harta</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100">
                                    <span class="text-xs font-semibold text-gray-800">Aktiva Lancar</span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                    <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Kas Utama Yayasan (1101)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($kas_utama, 0, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                    <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Bank BSI Yayasan (1102)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($bank_bsi, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $piutang_spp = count($overdue_santri) * 1100000;
                                ?>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                    <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Piutang SPP Santri (Tunggakan)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($piutang_spp, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $total_aktiva = $kas_utama + $bank_bsi + $piutang_spp;
                                ?>
                                <div class="flex justify-between items-center pt-4 font-bold text-sm text-blue-800 border-t border-gray-200">
                                    <span>Total Aktiva:</span>
                                    <span>Rp <?= number_format($total_aktiva, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Pasiva (Liabilities & Equity) Card -->
                        <div class="bg-white rounded-2xl border border-gray-150 shadow-sm overflow-hidden text-left">
                            <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between">
                                <h3 class="font-bold text-indigo-800 text-sm flex items-center"><i class="fas fa-balance-scale mr-2 text-indigo-600"></i>Kewajiban & Ekuitas (Pasiva)</h3>
                                <span class="text-xs font-bold text-indigo-700 bg-indigo-100 px-2 py-0.5 rounded">Modal & Hutang</span>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100">
                                    <span class="text-xs font-semibold text-gray-800">Kewajiban (Hutang)</span>
                                </div>
                                <?php
                                $hutang = $ringkasan['2101']['balance'] ?? 0;
                                ?>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                    <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Hutang Operasional (2101)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($hutang, 0, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2.5 border-b border-gray-100 pt-4">
                                    <span class="text-xs font-semibold text-gray-800">Ekuitas (Modal & Surplus)</span>
                                </div>
                                <?php
                                $modal = $ringkasan['3101']['balance'] ?? 0;
                                $surplus_laba = $saldo_bersih;
                                ?>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                    <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Modal Awal Yayasan (3101)</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($modal, 0, ',', '.') ?></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                    <span class="text-xs text-gray-500"><i class="fas fa-angle-right mr-1.5"></i>Surplus/Laba Periode Berjalan</span>
                                    <span class="text-xs font-bold text-slate-800">Rp <?= number_format($surplus_laba, 0, ',', '.') ?></span>
                                </div>
                                <?php
                                $total_pasiva = $hutang + $modal + $surplus_laba;
                                // Menghitung selisih balance jika ada ketidaksesuaian kecil akibat penyesuaian piutang luar jurnal
                                $selisih = $total_aktiva - $total_pasiva;
                                $total_pasiva_adj = $total_pasiva + $selisih;
                                ?>
                                <?php if ($selisih != 0): ?>
                                    <div class="flex justify-between items-center py-2 border-b border-gray-100 pl-4">
                                        <span class="text-xs text-indigo-700 italic font-semibold"><i class="fas fa-circle-info mr-1.5"></i>Penyesuaian Piutang SPP Berjalan</span>
                                        <span class="text-xs font-bold text-indigo-700">Rp <?= number_format($selisih, 0, ',', '.') ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between items-center pt-4 font-bold text-sm text-indigo-800 border-t border-gray-200">
                                    <span>Total Kewajiban & Ekuitas:</span>
                                    <span>Rp <?= number_format($total_pasiva_adj, 0, ',', '.') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance indicator check -->
                    <div class="bg-gradient-to-r from-teal-500 to-emerald-600 text-white rounded-2xl shadow-sm p-4.5 mt-6 flex justify-between items-center text-left">
                        <div class="flex items-center">
                            <div class="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center text-lg mr-3 shadow"><i class="fas fa-scale-balanced"></i></div>
                            <div>
                                <h4 class="font-bold text-xs">Pemeriksaan Keseimbangan Akun Neraca (Balance Check)</h4>
                                <p class="text-[10px] text-teal-100">Menyatakan bahwa total aset di bagian kiri seimbang dengan total kewajiban dan ekuitas di bagian kanan.</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="bg-white/25 text-white text-[11px] font-black px-4 py-1.5 rounded-lg border border-white/20 shadow-sm flex items-center gap-1.5"><i class="fas fa-check-circle"></i> SEIMBANG (BALANCED)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: PERENCANAAN KAS (CASHFLOW PLAN) -->
            <div id="tab-proyeksi" class="tab-pane hidden">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    
                    <!-- KIRI (Col-4): FORMULIR SIMULASI -->
                    <div class="lg:col-span-4 bg-white rounded-2xl border border-gray-250 shadow-sm overflow-hidden p-6 space-y-5 text-left">
                        <div class="border-b border-gray-100 pb-3 mb-1">
                            <h3 class="font-bold text-slate-900 text-sm flex items-center"><i class="fas fa-sliders-h text-amber-500 mr-2"></i>Parameter Simulasi TA 26/27</h3>
                            <p class="text-[10px] text-gray-400 mt-1">Ubah nilai di bawah ini untuk melihat dampaknya pada arus kas.</p>
                        </div>

                        <!-- GRUP PEMASUKAN -->
                        <div class="space-y-3.5">
                            <h4 class="text-xs font-bold text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded flex items-center uppercase tracking-wider"><i class="fas fa-wallet mr-1.5"></i>Proyeksi Pemasukan</h4>
                            
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Kas Awal Tahun Ajaran</label>
                                <input type="number" id="sim_init_cash" value="<?= $total_kas_bank ?>" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <!-- SPP BERDASARKAN 3 ANGKATAN -->
                            <div class="p-3 bg-emerald-50/30 rounded-xl border border-emerald-100 space-y-2">
                                <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wide block"><i class="fas fa-users mr-1"></i>SPP 3 Angkatan Santri</span>
                                
                                <!-- Angkatan Terlama (2024) -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Santri Terlama ('24)</label>
                                        <input type="number" id="sim_santri_2024" value="35" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Tarif SPP / Bln</label>
                                        <input type="number" id="sim_spp_2024" value="1100000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>

                                <!-- Angkatan Tengah (2025) -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Santri Tengah ('25)</label>
                                        <input type="number" id="sim_santri_2025" value="40" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Tarif SPP / Bln</label>
                                        <input type="number" id="sim_spp_2025" value="1300000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>

                                <!-- Angkatan Baru (2026) -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Santri Baru ('26)</label>
                                        <input type="number" id="sim_santri_2026" value="45" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Tarif SPP / Bln</label>
                                        <input type="number" id="sim_spp_2026" value="1500000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                            </div>

                            <!-- SUBSIDI BEASISWA / KERINGANAN SPP -->
                            <div class="p-3 bg-sky-50/20 rounded-xl border border-sky-100 space-y-2">
                                <span class="text-[10px] font-bold text-sky-800 uppercase tracking-wide block"><i class="fas fa-hand-holding-hand mr-1"></i>Beasiswa & Keringanan SPP</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Penerima Beasiswa</label>
                                        <input type="number" id="sim_beasiswa_count" value="12" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Rata Potongan / Bln</label>
                                        <input type="number" id="sim_beasiswa_potongan" value="750000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Target Santri Baru</label>
                                <input type="number" id="sim_new_santri" value="45" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <!-- RINCIAN UANG PANGKAL -->
                            <div class="p-3 bg-emerald-50/20 rounded-xl border border-emerald-100 space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold text-emerald-800 uppercase tracking-wide"><i class="fas fa-hand-holding-dollar mr-1"></i>Rincian Uang Pangkal</span>
                                    <span class="text-[10px] font-black text-emerald-600" id="total_uang_pangkal_label">Rp 12.000.000</span>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Uang Seragam</label>
                                        <input type="number" id="sim_up_seragam" value="1500000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Uang Asrama</label>
                                        <input type="number" id="sim_up_asrama" value="2500000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Uang Kegiatan</label>
                                        <input type="number" id="sim_up_kegiatan" value="2000000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Uang Buku</label>
                                        <input type="number" id="sim_up_buku" value="1500000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-semibold text-gray-500 mb-0.5">Wakaf Akomodasi</label>
                                    <input type="number" id="sim_up_wakaf" value="4500000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Estimasi Donasi Bulanan</label>
                                <input type="number" id="sim_donasi" value="7500000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>
                        </div>

                        <!-- GRUP PENGELUARAN -->
                        <div class="space-y-3.5 pt-3 border-t border-gray-100">
                            <h4 class="text-xs font-bold text-rose-800 bg-rose-50 px-2.5 py-1 rounded flex items-center uppercase tracking-wider"><i class="fas fa-receipt mr-1.5"></i>Proyeksi Pengeluaran</h4>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Gaji Pokok & Staff Tetap / Bulan</label>
                                <input type="number" id="sim_gaji_pokok" value="48000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Honor Mengajar Ustadz Offline / Bulan</label>
                                <input type="number" id="sim_honor_offline" value="<?= $cost_gaji_offline ?>" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Biaya Konsumsi / Anak / Hari</label>
                                <input type="number" id="sim_cost_makan" value="22000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>

                            <div class="p-3 bg-amber-50/50 rounded-xl border border-amber-200 space-y-2">
                                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wide block"><i class="fas fa-house-chimney mr-1"></i>Sewa Rumah Asrama</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-1">Tarif Sewa / Thn</label>
                                        <input type="number" id="sim_sewa_asrama" value="35000000" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-[9px] font-semibold text-gray-500 mb-1">Jumlah Unit Sewa</label>
                                        <input type="number" id="sim_jumlah_rumah" value="3" oninput="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-semibold text-gray-500 mb-1">Bulan Pembayaran Sewa</label>
                                    <select id="sim_rent_month" onchange="calculateProjections()" class="w-full px-2 py-1 border rounded text-[11px] bg-white">
                                        <option value="0">Juli 2026</option>
                                        <option value="1">Agustus 2026</option>
                                        <option value="2">September 2026</option>
                                        <option value="5">Desember 2026</option>
                                        <option value="6">Januari 2027</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 mb-1">Utilitas / Bulan</label>
                                    <input type="number" id="sim_utilitas" value="6000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 mb-1">Biaya Ujian/Semester</label>
                                    <input type="number" id="sim_biaya_ujian" value="12000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Anggaran THR Pegawai (Bulan April 27)</label>
                                <input type="number" id="sim_anggaran_thr" value="38000000" oninput="calculateProjections()" class="w-full px-3 py-1.5 border rounded-lg text-xs focus:ring-amber-500">
                            </div>
                        </div>
                    </div>

                    <!-- KANAN (Col-8): GRAFIK & TABEL HASIL SIMULASI -->
                    <div class="lg:col-span-8 space-y-6">
                        <!-- BANNER ALARM KESEHATAN KAS -->
                        <div id="projection-alert" class="bg-teal-100 text-teal-800 border border-teal-200 p-4 rounded-xl flex items-start text-xs text-left shadow-sm">
                            <i class="fas fa-check-circle text-teal-600 mr-2.5 text-base mt-0.5"></i>
                            <div>
                                <span class="font-bold">Menganalisis Proyeksi...</span> Harap isi parameter di sebelah kiri.
                            </div>
                        </div>

                        <!-- RINGKASAN OUTPUT PROYEKSI -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Total Pendapatan</span>
                                <span class="text-sm font-black text-slate-800 block mt-1" id="summary_total_rev">Rp 0</span>
                            </div>
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Total Pengeluaran</span>
                                <span class="text-sm font-black text-slate-800 block mt-1" id="summary_total_exp">Rp 0</span>
                            </div>
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center">
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wide">Surplus / Defisit Kumulatif</span>
                                <span class="text-sm font-black text-teal-600 block mt-1" id="summary_surplus_proj">Rp 0</span>
                            </div>
                            <div class="bg-white p-4 border border-gray-200 rounded-xl text-center bg-amber-50 border-amber-200">
                                <span class="text-[9px] font-bold text-amber-700 uppercase tracking-wide">Estimasi Kas Akhir</span>
                                <span class="text-sm font-black text-amber-800 block mt-1" id="summary_ending_cash">Rp 0</span>
                            </div>
                        </div>

                        <!-- GRAFIK TREN KAS -->
                        <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm text-left">
                            <div class="mb-4 flex items-center justify-between">
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wide"><i class="fas fa-chart-line text-amber-500 mr-1.5"></i>Tren Saldo Kas Kumulatif (TA 2026/2027)</h3>
                                <div class="text-[10px] text-gray-400 space-x-3.5 font-medium">
                                    <span><i class="fas fa-arrow-down text-rose-500 mr-1"></i>Titik Terendah: <strong id="summary_lowest_cash" class="text-slate-700">Rp 0</strong></span>
                                    <span><i class="fas fa-arrow-up text-teal-500 mr-1"></i>Titik Tertinggi: <strong id="summary_peak_cash" class="text-slate-700">Rp 0</strong></span>
                                </div>
                            </div>
                            <div class="h-64">
                                <canvas id="chart-projection"></canvas>
                            </div>
                        </div>

                        <!-- TABEL DETAIL PROYEKSI BULANAN -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-250 overflow-hidden text-left">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wide"><i class="fas fa-table mr-1.5 text-amber-500"></i>Tabel Proyeksi Finansial Bulanan</h3>
                                <span class="text-[9px] bg-slate-100 text-slate-500 font-bold px-2 py-0.5 rounded">12 Bulan</span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-center divide-y divide-gray-150">
                                    <thead class="bg-gray-50 text-[9px] uppercase tracking-wider text-gray-500 font-bold">
                                        <tr class="divide-x divide-gray-100">
                                            <th class="px-3 py-3 text-left w-[120px]" rowspan="2">Bulan</th>
                                            <th class="px-2 py-2" colspan="5">Pemasukan (Inflow)</th>
                                            <th class="px-2 py-2" colspan="7">Pengeluaran (Outflow)</th>
                                            <th class="px-2 py-3 w-[100px]" rowspan="2">Net Cashflow</th>
                                            <th class="px-3 py-3 w-[120px]" rowspan="2">Saldo Kas</th>
                                        </tr>
                                        <tr class="divide-x divide-gray-100 border-t border-gray-150 text-[8px]">
                                            <th class="px-2 py-1.5 text-right">SPP</th>
                                            <th class="px-2 py-1.5 text-right">Beasiswa</th>
                                            <th class="px-2 py-1.5 text-right">U. Pangkal</th>
                                            <th class="px-2 py-1.5 text-right">Donasi</th>
                                            <th class="px-2 py-1.5 text-right bg-emerald-50/50">Total</th>
                                            <th class="px-2 py-1.5 text-right">Gaji Pokok</th>
                                            <th class="px-2 py-1.5 text-right">H. Offline</th>
                                            <th class="px-2 py-1.5 text-right">Makan</th>
                                            <th class="px-2 py-1.5 text-right">Sewa</th>
                                            <th class="px-2 py-1.5 text-right">Utilitas</th>
                                            <th class="px-2 py-1.5 text-right">Ujian</th>
                                            <th class="px-2 py-1.5 text-right">THR</th>
                                            <th class="px-2 py-1.5 text-right bg-rose-50/50">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="projection-table-body" class="divide-y divide-gray-100 text-xs">
                                        <!-- Javascript will populate rows here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: JURNAL & PENCATATAN -->
            <div id="tab-jurnal" class="tab-pane hidden">
                <?php if(!empty($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 border border-emerald-200 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
                <?php if(!empty($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 border border-rose-200 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form Input Jurnal (Double Entry) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col h-fit">
                        <h2 class="font-bold text-lg text-gray-800 mb-4 border-b border-gray-100 pb-2"><i class="fas fa-pen-nib text-amber-600 mr-2"></i>Catat Transaksi</h2>
                        <form action="" method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="tambah_transaksi">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transaksi</label>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Bukti / Kuitansi</label>
                                <input type="text" name="no_bukti" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-amber-500" placeholder="Contoh: YVP-OUT-001">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Transaksi</label>
                                <input type="text" name="keterangan" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-amber-500" placeholder="Keterangan transaksi...">
                            </div>
                            
                            <!-- Debit & Kredit -->
                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                                <div>
                                    <label class="block text-xs font-semibold text-emerald-800 mb-1">Akun Debit (Masuk)</label>
                                    <select name="debit_akun_id" required class="w-full px-2 py-2 border border-emerald-300 rounded-lg text-xs focus:ring-emerald-500 bg-emerald-50/30">
                                        <?php foreach($list_akun as $ak) echo "<option value='{$ak['id']}'>{$ak['kode_akun']} - {$ak['nama_akun']}</option>"; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-rose-800 mb-1">Akun Kredit (Keluar)</label>
                                    <select name="kredit_akun_id" required class="w-full px-2 py-2 border border-rose-300 rounded-lg text-xs focus:ring-rose-500 bg-rose-50/30">
                                        <!-- Urutkan agar default kredit berbeda (misal pendapatan/modal) -->
                                        <?php 
                                        foreach($list_akun as $ak) {
                                            $sel = ($ak['kode_akun'] == '4101') ? 'selected' : '';
                                            echo "<option value='{$ak['id']}' $sel>{$ak['kode_akun']} - {$ak['nama_akun']}</option>"; 
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Beban Lembaga/Unit</label>
                                    <select name="lembaga_id" required class="w-full px-2 py-2 border rounded-lg text-xs focus:ring-amber-500">
                                        <?php foreach($list_lembaga as $lem) echo "<option value='{$lem['id']}'>{$lem['nama_lembaga']}</option>"; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jumlah (Nominal Rp)</label>
                                    <input type="number" name="jumlah" required class="w-full px-2 py-2 border rounded-lg text-xs focus:ring-amber-500" placeholder="Contoh: 150000">
                                </div>
                            </div>

                            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-lg text-sm shadow transition"><i class="fas fa-save mr-1"></i> Posting Transaksi</button>
                        </form>
                    </div>

                    <!-- Buku Jurnal (Ledger) -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2 flex flex-col overflow-hidden">
                        <h2 class="font-bold text-lg text-gray-800 mb-4 border-b border-gray-100 pb-2"><i class="fas fa-list text-amber-600 mr-2"></i>Riwayat Buku Jurnal Terpusat</h2>
                        <div class="overflow-x-auto flex-1">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="py-2.5 px-3">Tanggal / Bukti</th>
                                        <th class="py-2.5 px-3">Keterangan</th>
                                        <th class="py-2.5 px-3">Unit</th>
                                        <th class="py-2.5 px-3">Debit</th>
                                        <th class="py-2.5 px-3">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs">
                                    <?php if(empty($recent_transactions)): ?>
                                    <tr><td colspan="5" class="text-center py-10 text-gray-400 italic">Belum ada transaksi terdaftar.</td></tr>
                                    <?php else: foreach($recent_transactions as $tr): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2.5 px-3">
                                            <div class="font-semibold text-gray-800"><?= $tr['tanggal'] ?></div>
                                            <div class="text-[10px] text-gray-400"><?= htmlspecialchars($tr['no_bukti']) ?></div>
                                        </td>
                                        <td class="py-2.5 px-3">
                                            <div class="font-bold text-gray-900"><?= htmlspecialchars($tr['keterangan']) ?></div>
                                            <div class="text-[10px] text-gray-500"><?= $tr['kode_akun'] ?> - <?= htmlspecialchars($tr['nama_akun']) ?></div>
                                        </td>
                                        <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800"><?= htmlspecialchars($tr['nama_lembaga']) ?></span></td>
                                        <td class="py-2.5 px-3 font-semibold text-emerald-600"><?= $tr['debit'] > 0 ? 'Rp ' . number_format($tr['debit'], 0, ',', '.') : '-' ?></td>
                                        <td class="py-2.5 px-3 font-semibold text-rose-600"><?= $tr['kredit'] > 0 ? 'Rp ' . number_format($tr['kredit'], 0, ',', '.') : '-' ?></td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: AI AUDITOR & KONSULTAN -->
            <div id="tab-ai-auditor" class="tab-pane hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col min-h-[500px]">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6 border-b border-gray-100 pb-4">
                        <div>
                            <h2 class="font-bold text-lg text-gray-800 flex items-center"><i class="fas fa-user-shield text-indigo-600 mr-2"></i>Asisten AI Auditor & Konsultan Akuntansi</h2>
                            <p class="text-xs text-gray-500 mt-1">AI akan memindai jurnal keuangan Yayasan, memverifikasi kepatuhan syariah dana ZISWAF, serta memberikan rekomendasi kesehatan cashflow.</p>
                        </div>
                        <button id="btn-ai-audit" onclick="jalankanAIAudit()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg text-sm shadow transition flex items-center whitespace-nowrap">
                            <i class="fas fa-brain mr-2 animate-pulse"></i> Jalankan Audit AI
                        </button>
                    </div>

                    <div id="ai-audit-result" class="flex-1 p-6 bg-slate-50 border border-slate-200 rounded-xl overflow-y-auto max-h-[600px] markdown-body text-left">
                        <div class="flex flex-col items-center justify-center py-20 text-gray-400">
                            <i class="fas fa-clipboard-check text-6xl mb-4 text-indigo-300"></i>
                            <p class="font-semibold text-center text-sm">Klik tombol "Jalankan Audit AI" di atas untuk memulai analisis cerdas.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: OVERDUE SPP REMINDER -->
            <div id="tab-reminders" class="tab-pane hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col min-h-[500px]">
                    <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex justify-between items-center">
                        <div>
                            <h2 class="font-bold text-red-800"><i class="fas fa-bell mr-2 animate-bounce"></i> Daftar Wali Santri Belum Bayar SPP Bulanan</h2>
                            <p class="text-xs text-red-600 mt-1">Daftar santri aktif yang belum melunasi SPP untuk bulan <strong><?= $bulan_sekarang ?> <?= $tahun_sekarang ?></strong>.</p>
                        </div>
                        <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full"><?= count($overdue_santri) ?> Santri Terdeteksi</span>
                    </div>

                    <div class="overflow-x-auto flex-1 p-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Nama Santri</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <th class="py-3 px-4">Nama Orang Tua</th>
                                    <th class="py-3 px-4">Nomor WhatsApp</th>
                                    <th class="py-3 px-4 text-center">Aksi Pengingat</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php if(empty($overdue_santri)): ?>
                                <tr><td colspan="5" class="text-center py-10 text-gray-400 italic">Maa Syaa Allah! Semua santri aktif sudah melunasi SPP bulan ini.</td></tr>
                                <?php else: foreach($overdue_santri as $s): ?>
                                <tr id="row-santri-<?= $s['id'] ?>" class="hover:bg-gray-50">
                                    <td class="py-3 px-4 font-bold text-gray-900"><?= htmlspecialchars($s['nama_lengkap']) ?></td>
                                    <td class="py-3 px-4 font-semibold text-gray-600"><?= htmlspecialchars($s['kelas_sekarang']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($s['nama_ortu'] ?? 'Tidak Tercatat') ?></td>
                                    <td class="py-3 px-4 font-mono text-gray-600"><?= htmlspecialchars($s['no_wa'] ?? '-') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <?php if(!empty($s['no_wa'])): ?>
                                        <button onclick="kirimReminder(<?= $s['id'] ?>, '<?= htmlspecialchars($s['no_wa']) ?>', '<?= htmlspecialchars($s['nama_lengkap']) ?>', '<?= htmlspecialchars($s['nama_ortu']) ?>')" class="btn-reminder bg-rose-100 text-rose-700 hover:bg-rose-500 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-rose-200 flex items-center justify-center mx-auto">
                                            <i class="fab fa-whatsapp mr-1 text-base"></i> Kirim Pengingat WA
                                        </button>
                                        <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">Nomor WA Kosong</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: COA MANAGEMENT -->
            <div id="tab-coa" class="tab-pane hidden">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Form Tambah Akun -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col h-fit">
                        <h2 class="font-bold text-lg text-gray-800 mb-4 border-b border-gray-100 pb-2"><i class="fas fa-plus-circle text-amber-600 mr-2"></i>Tambah Akun Baru</h2>
                        <form action="" method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="tambah_akun">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Akun</label>
                                <input type="text" name="kode_akun" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-amber-500" placeholder="Contoh: 1103 (untuk Kas/Bank)">
                                <p class="text-[10px] text-gray-400 mt-1">Saran kode: 11xx (Aset/Bank), 21xx (Kewajiban), 31xx (Ekuitas), 41xx/42xx (Pendapatan), 51xx (Beban)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Akun / Nama Bank & Rekening</label>
                                <input type="text" name="nama_akun" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-amber-500" placeholder="Contoh: Bank Syariah Mandiri (BSM)">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Akun</label>
                                <select name="tipe_akun" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-amber-500">
                                    <option value="Aset">Aset (Kas, Bank, Piutang)</option>
                                    <option value="Kewajiban">Kewajiban (Hutang)</option>
                                    <option value="Ekuitas">Ekuitas (Modal)</option>
                                    <option value="Pendapatan">Pendapatan</option>
                                    <option value="Beban">Beban / Biaya</option>
                                </select>
                            </div>

                            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-lg text-sm shadow transition"><i class="fas fa-plus mr-1"></i> Simpan Akun</button>
                        </form>
                    </div>

                    <!-- Tabel Daftar Akun -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:col-span-2 flex flex-col overflow-hidden">
                        <h2 class="font-bold text-lg text-gray-800 mb-4 border-b border-gray-100 pb-2"><i class="fas fa-list-ol text-amber-600 mr-2"></i>Bagan Akun (Chart of Accounts - COA)</h2>
                        <div class="overflow-x-auto flex-1">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="py-2.5 px-3">Kode</th>
                                        <th class="py-2.5 px-3">Nama Akun</th>
                                        <th class="py-2.5 px-3">Tipe Akun</th>
                                        <th class="py-2.5 px-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-xs">
                                    <?php foreach($list_akun as $ak): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="py-2.5 px-3 font-semibold text-gray-800"><?= $ak['kode_akun'] ?></td>
                                        <td class="py-2.5 px-3 font-bold text-gray-900"><?= htmlspecialchars($ak['nama_akun']) ?></td>
                                        <td class="py-2.5 px-3">
                                            <?php 
                                            $tipe_colors = [
                                                'Aset' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                                'Kewajiban' => 'bg-amber-50 text-amber-800 border-amber-200',
                                                'Ekuitas' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
                                                'Pendapatan' => 'bg-blue-50 text-blue-800 border-blue-200',
                                                'Beban' => 'bg-rose-50 text-rose-800 border-rose-200'
                                            ];
                                            $color = $tipe_colors[$ak['tipe_akun']] ?? 'bg-gray-50 text-gray-800';
                                            ?>
                                            <span class="px-2 py-0.5 rounded border text-[10px] font-semibold <?= $color ?>"><?= $ak['tipe_akun'] ?></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini?');" class="inline">
                                                <input type="hidden" name="action" value="hapus_akun">
                                                <input type="hidden" name="akun_id" value="<?= $ak['id'] ?>">
                                                <button type="submit" class="text-rose-600 hover:text-rose-900 font-bold transition">
                                                    <i class="fas fa-trash-alt"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: PAYMENT COMMITMENTS (JANJI BAYAR) -->
            <div id="tab-promises" class="tab-pane hidden">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col min-h-[500px]">
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-100 flex justify-between items-center">
                        <div>
                            <h2 class="font-bold text-amber-800"><i class="fas fa-handshake mr-2"></i> Komitmen Janji Pembayaran Wali Santri</h2>
                            <p class="text-xs text-amber-600 mt-1">Daftar janji pembayaran SPP & Uang Masuk yang diinput langsung oleh orang tua/wali santri dari pesan WhatsApp.</p>
                        </div>
                        <span class="bg-amber-600 text-white text-xs font-bold px-3 py-1 rounded-full"><?= count($payment_promises) ?> Komitmen Tercatat</span>
                    </div>

                    <div class="overflow-x-auto flex-1 p-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="py-3 px-4">Nama Santri</th>
                                    <th class="py-3 px-4">Kelas</th>
                                    <th class="py-3 px-4">Periode Tagihan</th>
                                    <th class="py-3 px-4">Tanggal Janji Bayar</th>
                                    <th class="py-3 px-4">Catatan Wali Santri</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php if(empty($payment_promises)): ?>
                                <tr><td colspan="6" class="text-center py-10 text-gray-400 italic">Belum ada komitmen janji pembayaran yang diinput oleh wali santri.</td></tr>
                                <?php else: foreach($payment_promises as $p): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 font-bold text-gray-900"><?= htmlspecialchars($p['nama_lengkap']) ?></td>
                                    <td class="py-3 px-4 font-semibold text-gray-600"><?= htmlspecialchars($p['kelas_sekarang']) ?></td>
                                    <td class="py-3 px-4 font-medium text-amber-800"><?= htmlspecialchars($p['bulan']) ?> <?= htmlspecialchars($p['tahun']) ?></td>
                                    <td class="py-3 px-4 font-mono font-bold text-indigo-700">
                                        <?= date('d/m/Y', strtotime($p['tanggal_janji'])) ?>
                                        <?php 
                                        $hari_sisa = (strtotime($p['tanggal_janji']) - strtotime(date('Y-m-d'))) / 86400;
                                        if ($hari_sisa < 0) {
                                            echo ' <span class="bg-red-100 text-red-700 text-[10px] px-2 py-0.5 rounded font-sans">Terlewat</span>';
                                        } elseif ($hari_sisa == 0) {
                                            echo ' <span class="bg-amber-100 text-amber-700 text-[10px] px-2 py-0.5 rounded font-sans animate-pulse">Hari ini!</span>';
                                        } else {
                                            echo ' <span class="bg-emerald-100 text-emerald-700 text-[10px] px-2 py-0.5 rounded font-sans">' . ceil($hari_sisa) . ' hari lagi</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-gray-600 max-w-xs truncate" title="<?= htmlspecialchars($p['catatan']) ?>"><?= htmlspecialchars($p['catatan'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Menunggu Pelunasan
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: CHARTS & VISUALIZATION -->
            <div id="tab-charts" class="tab-pane hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Tren Arus Kas -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col min-h-[350px]">
                        <h2 class="font-bold text-gray-800 text-sm mb-4 border-b border-gray-100 pb-2"><i class="fas fa-chart-line text-emerald-600 mr-2"></i>Tren Arus Kas (Pemasukan vs Pengeluaran)</h2>
                        <div class="flex-1 relative h-64">
                            <canvas id="chart-cashflow"></canvas>
                        </div>
                    </div>

                    <!-- Distribusi Pendapatan per Divisi -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col min-h-[350px]">
                        <h2 class="font-bold text-gray-800 text-sm mb-4 border-b border-gray-100 pb-2"><i class="fas fa-chart-pie text-amber-600 mr-2"></i>Distribusi Pendapatan per Lembaga/Divisi</h2>
                        <div class="flex-1 relative h-64">
                            <canvas id="chart-divisi"></canvas>
                        </div>
                    </div>

                    <!-- Status Pembayaran SPP -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col min-h-[350px]">
                        <h2 class="font-bold text-gray-800 text-sm mb-4 border-b border-gray-100 pb-2"><i class="fas fa-user-check text-indigo-600 mr-2"></i>Status Penagihan SPP (Bulan Ini)</h2>
                        <div class="flex-1 relative h-64">
                            <canvas id="chart-spp"></canvas>
                        </div>
                    </div>

                    <!-- Saldo Rekening Bank -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col min-h-[350px]">
                        <h2 class="font-bold text-gray-800 text-sm mb-4 border-b border-gray-100 pb-2"><i class="fas fa-university text-blue-600 mr-2"></i>Saldo Kas & Rekening Bank</h2>
                        <div class="flex-1 relative h-64">
                            <canvas id="chart-bank"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Data Keuangan untuk AI Auditor & Dashboard
        const totalKasBank = <?= $total_kas_bank ?>;
        const kasUtama = <?= $kas_utama ?>;
        const bankBsi = <?= $bank_bsi ?>;
        const pendapatanBulanIni = <?= $total_pemasukan ?>;
        const bebanBulanIni = <?= $total_pengeluaran ?>;
        const overdueSppCount = <?= count($overdue_santri) ?>;
        const coaBalances = <?= json_encode($ringkasan) ?>;
        const recentTransactions = <?= json_encode($recent_transactions) ?>;
        const currentBulan = <?= json_encode($bulan_sekarang) ?>;
        const currentTahun = <?= json_encode($tahun_sekarang) ?>;
        const paymentPromises = <?= json_encode($payment_promises) ?>;
        
        // Data Grafik
        const cashflowTrend = <?= json_encode($cashflow_trend) ?>;
        const divisiDistribution = <?= json_encode($divisi_distribution) ?>;
        const totalPaidSpp = <?= $total_paid_spp ?>;
        const totalUnpaidSpp = <?= $total_unpaid_spp ?>;
        const bankBalances = <?= json_encode($bank_balances) ?>;

        // Switch Tab
        function switchTab(tabId) {
            // Hide all
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            // Remove active classes from buttons
            document.querySelectorAll('[id^="btn-tab-"]').forEach(btn => {
                btn.className = "px-4 py-2.5 text-xs font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition";
            });

            // Show active
            document.getElementById(tabId).classList.remove('hidden');
            // Add active class
            document.getElementById('btn-' + tabId).className = "px-4 py-2.5 text-xs font-bold border-b-2 border-amber-600 text-amber-700 focus:outline-none transition";
            
            // Init charts if charts tab
            if (tabId === 'tab-charts') {
                initCharts();
            }
            if (tabId === 'tab-proyeksi') {
                setTimeout(calculateProjections, 100);
            }
        }

        // Sub-tab Laporan Terpadu
        function switchSubLaporan(subId) {
            document.querySelectorAll('.sub-report-pane').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('block');
            });
            document.querySelectorAll('[id^="btn-sub-"]').forEach(btn => {
                btn.className = "px-4 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-100 rounded-lg transition-all focus:outline-none";
            });
            document.getElementById(subId).classList.remove('hidden');
            document.getElementById(subId).classList.add('block');
            document.getElementById('btn-' + subId).className = "px-4 py-2 text-xs font-bold bg-amber-700 text-white rounded-lg transition-all focus:outline-none shadow-sm";
        }

        // Toggle Rincian Mapel Offline
        function toggleOfflineList() {
            const list = document.getElementById("offlineMapelList");
            const arrow = document.getElementById("arrow-list");
            if (list.classList.contains("hidden")) {
                list.classList.remove("hidden");
                arrow.className = "fas fa-chevron-up ml-1 text-[8px]";
            } else {
                list.classList.add("hidden");
                arrow.className = "fas fa-chevron-down ml-1 text-[8px]";
            }
        }

        // DEFINISI PARAMETER PROYEKSI BULANAN TA 2026/2027
        const monthDefinitions = [
            { label: "Juli 2026", days: 31, isExam: false, isThr: false, isRent: true, upRatio: 0.60 },
            { label: "Agustus 2026", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.40 },
            { label: "September 2026", days: 30, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Oktober 2026", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "November 2026", days: 30, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Desember 2026", days: 31, isExam: true, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Januari 2027", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Februari 2027", days: 28, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Maret 2027", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "April 2027", days: 30, isExam: false, isThr: true, isRent: false, upRatio: 0.00 },
            { label: "Mei 2027", days: 31, isExam: false, isThr: false, isRent: false, upRatio: 0.00 },
            { label: "Juni 2027", days: 30, isExam: true, isThr: false, isRent: false, upRatio: 0.00 }
        ];

        let projectionChart = null;

        function calculateProjections() {
            const initCash = parseFloat(document.getElementById('sim_init_cash').value) || 0;
            const santri_2024 = parseInt(document.getElementById('sim_santri_2024').value) || 0;
            const spp_2024 = parseFloat(document.getElementById('sim_spp_2024').value) || 0;
            const santri_2025 = parseInt(document.getElementById('sim_santri_2025').value) || 0;
            const spp_2025 = parseFloat(document.getElementById('sim_spp_2025').value) || 0;
            const santri_2026 = parseInt(document.getElementById('sim_santri_2026').value) || 0;
            const spp_2026 = parseFloat(document.getElementById('sim_spp_2026').value) || 0;

            const beasiswa_count = parseInt(document.getElementById('sim_beasiswa_count').value) || 0;
            const beasiswa_potongan = parseFloat(document.getElementById('sim_beasiswa_potongan').value) || 0;
            const newSantriCount = parseInt(document.getElementById('sim_new_santri').value) || 0;
            
            const up_seragam = parseFloat(document.getElementById('sim_up_seragam').value) || 0;
            const up_asrama = parseFloat(document.getElementById('sim_up_asrama').value) || 0;
            const up_kegiatan = parseFloat(document.getElementById('sim_up_kegiatan').value) || 0;
            const up_buku = parseFloat(document.getElementById('sim_up_buku').value) || 0;
            const up_wakaf = parseFloat(document.getElementById('sim_up_wakaf').value) || 0;
            
            const uangPangkal = up_seragam + up_asrama + up_kegiatan + up_buku + up_wakaf;
            document.getElementById('total_uang_pangkal_label').innerText = formatRupiah(uangPangkal);

            const donasi = parseFloat(document.getElementById('sim_donasi').value) || 0;
            const gajiPokok = parseFloat(document.getElementById('sim_gaji_pokok').value) || 0;
            const honorOffline = parseFloat(document.getElementById('sim_honor_offline').value) || 0;
            const costMakanHari = parseFloat(document.getElementById('sim_cost_makan').value) || 0;
            const sewaAsramaRumah = parseFloat(document.getElementById('sim_sewa_asrama').value) || 0;
            const jumlahRumahSewa = parseInt(document.getElementById('sim_jumlah_rumah').value) || 0;
            const rentMonthIndex = parseInt(document.getElementById('sim_rent_month').value);
            
            const utilitas = parseFloat(document.getElementById('sim_utilitas').value) || 0;
            const biayaUjian = parseFloat(document.getElementById('sim_biaya_ujian').value) || 0;
            const anggaranThr = parseFloat(document.getElementById('sim_anggaran_thr').value) || 0;

            const total_santri = santri_2024 + santri_2025 + santri_2026;
            let currentCash = initCash;
            let totalRevAccum = 0;
            let totalExpAccum = 0;
            
            const tableBody = document.getElementById('projection-table-body');
            if (tableBody) {
                tableBody.innerHTML = '';
            }

            const chartLabels = [];
            const chartData = [];

            monthDefinitions.forEach((month, index) => {
                const spp_gross = (santri_2024 * spp_2024) + (santri_2025 * spp_2025) + (santri_2026 * spp_2026);
                const spp_discount = beasiswa_count * beasiswa_potongan;
                const spp_net = Math.max(0, spp_gross - spp_discount);

                const incUangPangkal = newSantriCount * uangPangkal * month.upRatio;
                const incDonasi = donasi;
                const totalInc = spp_net + incUangPangkal + incDonasi;

                const expGaji = gajiPokok;
                const expHonor = honorOffline;
                const expMakan = (total_santri + 8) * costMakanHari * month.days;
                const expSewa = (index === rentMonthIndex) ? (sewaAsramaRumah * jumlahRumahSewa) : 0;
                const expUtilitas = utilitas;
                const expUjian = month.isExam ? biayaUjian : 0;
                const expThr = month.isThr ? anggaranThr : 0;
                const totalExp = expGaji + expHonor + expMakan + expSewa + expUtilitas + expUjian + expThr;

                const netCash = totalInc - totalExp;
                currentCash += netCash;

                totalRevAccum += totalInc;
                totalExpAccum += totalExp;

                chartLabels.push(month.label);
                chartData.push(currentCash);

                if (tableBody) {
                    const row = document.createElement('tr');
                    row.className = 'border-b border-gray-150 hover:bg-slate-50 transition-colors text-[10px]';
                    row.innerHTML = `
                        <td class="px-3 py-2.5 font-bold text-slate-800 text-left text-xs bg-slate-50">${month.label}</td>
                        <td class="px-2 py-2.5 text-right text-emerald-700 font-medium">${formatRupiah(spp_gross)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-600 font-medium">${spp_discount > 0 ? '-' + formatRupiah(spp_discount) : 'Rp 0'}</td>
                        <td class="px-2 py-2.5 text-right text-emerald-700 font-medium">${formatRupiah(incUangPangkal)}</td>
                        <td class="px-2 py-2.5 text-right text-emerald-700 font-medium">${formatRupiah(incDonasi)}</td>
                        <td class="px-2 py-2.5 text-right font-bold text-emerald-800 bg-emerald-50/20">${formatRupiah(totalInc)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expGaji)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expHonor)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expMakan)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expSewa)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expUtilitas)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expUjian)}</td>
                        <td class="px-2 py-2.5 text-right text-rose-700 font-medium">${formatRupiah(expThr)}</td>
                        <td class="px-2 py-2.5 text-right font-bold text-rose-800 bg-rose-50/20">${formatRupiah(totalExp)}</td>
                        <td class="px-2 py-2.5 text-right font-bold ${netCash >= 0 ? 'text-teal-600 bg-teal-50/10' : 'text-rose-600 bg-rose-50/10'}">${formatRupiah(netCash)}</td>
                        <td class="px-3 py-2.5 text-right font-black ${currentCash >= 0 ? 'text-slate-900 bg-slate-50' : 'text-rose-900 bg-rose-100'}">${formatRupiah(currentCash)}</td>
                    `;
                    tableBody.appendChild(row);
                }
            });

            document.getElementById('summary_total_rev').innerText = formatRupiah(totalRevAccum);
            document.getElementById('summary_total_exp').innerText = formatRupiah(totalExpAccum);
            const totalSurplus = totalRevAccum - totalExpAccum;
            const surplusEl = document.getElementById('summary_surplus_proj') || document.getElementById('summary_surplus');
            if (surplusEl) {
                surplusEl.innerText = formatRupiah(totalSurplus);
                if (totalSurplus >= 0) {
                    surplusEl.className = 'text-sm font-black text-teal-600 block mt-1';
                } else {
                    surplusEl.className = 'text-sm font-black text-rose-700 block mt-1';
                }
            }
            document.getElementById('summary_ending_cash').innerText = formatRupiah(currentCash);

            const minCash = Math.min(...chartData);
            const maxCash = Math.max(...chartData);
            document.getElementById('summary_lowest_cash').innerText = formatRupiah(minCash);
            document.getElementById('summary_peak_cash').innerText = formatRupiah(maxCash);

            const alertBox = document.getElementById('projection-alert');
            if (alertBox) {
                if (minCash < 0) {
                    alertBox.className = 'bg-rose-100 text-rose-800 border border-rose-200 p-4 rounded-xl flex items-start text-xs text-left shadow-sm';
                    alertBox.innerHTML = `<i class="fas fa-exclamation-triangle text-rose-600 mr-2.5 text-base mt-0.5"></i>
                        <div>
                            <span class="font-bold text-rose-900">Peringatan Defisit Kas!</span> Proyeksi menunjukkan saldo kas kumulatif Anda akan berada di bawah nol (defisit) di beberapa bulan tertentu. Harap kurangi belanja modal, kurangi unit sewa rumah asrama, atau lakukan penagihan SPP yang lebih ketat.
                        </div>`;
                } else {
                    alertBox.className = 'bg-teal-100 text-teal-800 border border-teal-200 p-4 rounded-xl flex items-start text-xs text-left shadow-sm';
                    alertBox.innerHTML = `<i class="fas fa-check-circle text-teal-600 mr-2.5 text-base mt-0.5"></i>
                        <div>
                            <span class="font-bold text-teal-900">Rencana Kas Aman!</span> Berdasarkan parameter simulasi saat ini, saldo kas kumulatif Yayasan diproyeksikan akan selalu berada dalam kondisi surplus positif sepanjang tahun ajaran 2026/2027.
                        </div>`;
                }
            }

            if (projectionChart) {
                projectionChart.data.labels = chartLabels;
                projectionChart.data.datasets[0].data = chartData;
                projectionChart.update();
            } else {
                const canvasProj = document.getElementById('chart-projection');
                if (canvasProj) {
                    const ctx = canvasProj.getContext('2d');
                    projectionChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: chartLabels,
                            datasets: [{
                                label: 'Saldo Kas Kumulatif',
                                data: chartData,
                                borderColor: '#d97706',
                                backgroundColor: 'rgba(217, 119, 6, 0.05)',
                                borderWidth: 3.5,
                                fill: true,
                                tension: 0.3,
                                pointBackgroundColor: '#b45309',
                                pointRadius: 4.5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                y: {
                                    ticks: {
                                        callback: function(value) {
                                            return value >= 0 ? "Rp " + (value / 1000000) + "M" : "-Rp " + (Math.abs(value) / 1000000) + "M";
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            calculateProjections();
            
            // Auto switch tab from URL parameter if exists
            const urlParams = new URLSearchParams(window.location.search);
            const tabParam = urlParams.get('tab');
            if (tabParam) {
                if (tabParam === 'proyeksi') switchTab('tab-proyeksi');
                else if (tabParam === 'jurnal') switchTab('tab-jurnal');
                else if (tabParam === 'coa') switchTab('tab-coa');
                else if (tabParam === 'reminders') switchTab('tab-reminders');
                else if (tabParam === 'promises') switchTab('tab-promises');
                else if (tabParam === 'ai-auditor') switchTab('tab-ai-auditor');
                else if (tabParam === 'charts') switchTab('tab-charts');
            }
        });

        // Inisialisasi Grafik saat Tab Dibuka
        let chartsInitialized = false;
        function initCharts() {
            if (chartsInitialized) return;
            chartsInitialized = true;

            // 1. Grafik Tren Arus Kas
            const ctxCashflow = document.getElementById('chart-cashflow').getContext('2d');
            new Chart(ctxCashflow, {
                type: 'bar',
                data: {
                    labels: cashflowTrend.map(d => d.label),
                    datasets: [
                        {
                            label: 'Pemasukan',
                            data: cashflowTrend.map(d => d.pendapatan),
                            backgroundColor: 'rgba(16, 185, 129, 0.75)',
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1
                        },
                        {
                            label: 'Pengeluaran',
                            data: cashflowTrend.map(d => d.beban),
                            backgroundColor: 'rgba(239, 68, 68, 0.75)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + formatRupiah(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + formatRupiah(context.raw);
                                }
                            }
                        }
                    }
                }
            });

            // 2. Grafik Distribusi Pendapatan per Divisi
            const ctxDivisi = document.getElementById('chart-divisi').getContext('2d');
            new Chart(ctxDivisi, {
                type: 'doughnut',
                data: {
                    labels: divisiDistribution.map(d => d.nama_lembaga),
                    datasets: [{
                        data: divisiDistribution.map(d => d.total),
                        backgroundColor: [
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(99, 102, 241, 0.8)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': Rp ' + formatRupiah(context.raw);
                                }
                            }
                        }
                    }
                }
            });

            // 3. Grafik Status Pembayaran SPP
            const ctxSpp = document.getElementById('chart-spp').getContext('2d');
            new Chart(ctxSpp, {
                type: 'pie',
                data: {
                    labels: ['Sudah Lunas', 'Belum Lunas'],
                    datasets: [{
                        data: [totalPaidSpp, totalUnpaidSpp],
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // 4. Grafik Saldo Rekening Bank
            const ctxBank = document.getElementById('chart-bank').getContext('2d');
            new Chart(ctxBank, {
                type: 'bar',
                data: {
                    labels: bankBalances.map(b => b.nama),
                    datasets: [{
                        label: 'Saldo Rekening',
                        data: bankBalances.map(b => b.balance),
                        backgroundColor: 'rgba(99, 102, 241, 0.8)',
                        borderColor: 'rgb(99, 102, 241)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + formatRupiah(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Saldo: Rp ' + formatRupiah(context.raw);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Format Rupiah Helper
        function formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0 }).format(num);
        }

        // Jalankan AI Audit via Gemini Proxy
        function jalankanAIAudit() {
            const btn = document.getElementById('btn-ai-audit');
            const container = document.getElementById('ai-audit-result');
            
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menganalisa...';
            container.innerHTML = '<div class="text-center py-20 text-gray-500 animate-pulse"><i class="fas fa-brain text-5xl mb-4 text-indigo-500"></i><p class="font-bold">AI Agent sedang memindai saldo, transaksi, dan memeriksa kepatuhan syariah...</p></div>';

            const payloadData = {
                total_kas_bank: totalKasBank,
                kas_utama: kasUtama,
                bank_bsi: bankBsi,
                pendapatan_bulan_ini: pendapatanBulanIni,
                beban_bulan_ini: bebanBulanIni,
                coa_balances: coaBalances,
                overdue_spp_count: overdueSppCount,
                recent_transactions: recentTransactions,
                payment_promises: paymentPromises
            };

            const prompt = "Anda adalah AI Agent Auditor & Konsultan Keuangan Yayasan Villa Quran (Sekolah Tahfidz, Filantropi, dan Unit Usaha).\n"
                + "Tugas Anda adalah menganalisa data keuangan Yayasan berikut, memberikan laporan audit kepatuhan, mendeteksi jika ada anomali atau potensi kebocoran dana, serta memberikan saran praktis agar cashflow tetap sehat atau surplus.\n\n"
                + "Berikut Ringkasan Keuangan Bulan Ini (" + currentBulan + " " + currentTahun + "):\n"
                + "- Saldo Terpusat Kas & Bank: Rp " + formatRupiah(payloadData.total_kas_bank) + "\n"
                + "  * Kas Utama: Rp " + formatRupiah(payloadData.kas_utama) + "\n"
                + "  * Bank BSI: Rp " + formatRupiah(payloadData.bank_bsi) + "\n"
                + "- Pemasukan Bulan Ini: Rp " + formatRupiah(payloadData.pendapatan_bulan_ini) + "\n"
                + "- Pengeluaran Bulan Ini: Rp " + formatRupiah(payloadData.beban_bulan_ini) + "\n"
                + "- Wali Santri Belum Bayar SPP: " + payloadData.overdue_spp_count + " orang\n"
                + "- Komitmen Janji Pembayaran Wali Santri Tercatat: " + JSON.stringify(payloadData.payment_promises, null, 2) + "\n\n"
                + "Detail Saldo COA:\n" + JSON.stringify(payloadData.coa_balances, null, 2) + "\n\n"
                + "10 Transaksi Terakhir:\n" + JSON.stringify(payloadData.recent_transactions, null, 2) + "\n\n"
                + "ATURAN AUDIT SYARIAH & OPERASIONAL:\n"
                + "1. Dana ZISWAF (Zakat, Infaq, Shadaqah, Wakaf - Kode Akun 4201, 4202, 4203) dikelola oleh divisi Filantropi.\n"
                + "2. Dana Zakat WAJIB disalurkan ke Asnaf yang berhak, tidak boleh dicampur untuk membiayai Unit Usaha komersial atau operasional bisnis.\n"
                + "3. Dana SPP bulanan santri (Kode 4101) dikelola oleh divisi Sekolah Tahfidz.\n\n"
                + "Berikan laporan Anda dalam format Markdown yang premium, informatif, dan santun. Laporan harus terdiri dari:\n"
                + "1. ### 🔍 Hasil Pemindaian Saldo & Transaksi (Deteksi anomali/selisih jika ada)\n"
                + "2. ### 🕋 Kepatuhan Syariah & Penggunaan Dana (ZISWAF vs Komersial)\n"
                + "3. ### 📈 Rekomendasi Optimasi Arus Kas (Saran agar surplus & cara penagihan SPP)\n";

            fetch('../api-gemini.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    prompt: prompt,
                    type: 'pembukuan_audit'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    container.innerHTML = marked.parse(data.result);
                } else {
                    throw new Error(data.message || 'Gagal memproses data');
                }
            })
            .catch(err => {
                container.innerHTML = '<div class="text-red-500 bg-red-50 p-4 rounded-lg border border-red-200"><i class="fas fa-exclamation-triangle mr-2"></i> ' + err.message + '</div>';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-brain mr-2 animate-pulse"></i> Jalankan Audit AI';
            });
        }

        // Kirim Pengingat WhatsApp via AJAX Fonnte
        function kirimReminder(santriId, noWa, namaSantri, namaOrtu) {
            const row = document.getElementById('row-santri-' + santriId);
            const btn = row.querySelector('.btn-reminder');
            const originalHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim...';

            const formData = new FormData();
            formData.append('action', 'kirim_pengingat');
            formData.append('no_wa', noWa);
            formData.append('nama_santri', namaSantri);
            formData.append('nama_ortu', namaOrtu);
            formData.append('bulan', currentBulan);
            formData.append('tahun', currentTahun);

            fetch('pembukuan.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    btn.innerHTML = '<i class="fas fa-check mr-1"></i> Terkirim';
                    btn.className = "bg-emerald-500 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm";
                } else {
                    alert('Gagal mengirim WA: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(err => {
                alert('Kesalahan jaringan: ' + err);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
        
        // Mobile Sidebar Toggle
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        }); 
        document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        });
    </script>
</body>
</html>
