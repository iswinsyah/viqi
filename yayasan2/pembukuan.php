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
           
    $FONNTE_TOKEN = "Dtw72oRiQr8FympzpMHL"; // Sesuai token di cron-agent.php
    
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
            <div class="flex space-x-2 border-b border-gray-200 mb-6 flex-shrink-0">
                <button onclick="switchTab('tab-jurnal')" id="btn-tab-jurnal" class="px-4 py-2 text-sm font-bold border-b-2 border-amber-600 text-amber-700 focus:outline-none transition">
                    <i class="fas fa-book-open mr-1"></i> Pencatatan & Jurnal
                </button>
                <button onclick="switchTab('tab-ai-auditor')" id="btn-tab-ai-auditor" class="px-4 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition flex items-center">
                    <i class="fas fa-user-shield mr-1"></i> AI Auditor & Konsultan
                </button>
                <button onclick="switchTab('tab-reminders')" id="btn-tab-reminders" class="px-4 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition relative">
                    <i class="fas fa-bell mr-1"></i> Pengingat Tagihan SPP
                    <?php if(count($overdue_santri) > 0): ?>
                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full animate-bounce"><?= count($overdue_santri) ?></span>
                    <?php endif; ?>
                </button>
            </div>

            <!-- CARDS SUMMARY (CASHFLOW HEALTH) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                    <span class="text-xs uppercase tracking-wider text-emerald-100 font-semibold">Pemasukan (<?= $bulan_sekarang ?>)</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format($pendapatan_bulanan, 0, ',', '.') ?></h2>
                    <div class="text-xs text-emerald-200 mt-3 pt-2 border-t border-white/10 flex items-center">
                        <i class="fas fa-arrow-up mr-1"></i> Semua Penerimaan Unit
                    </div>
                </div>

                <!-- Pengeluaran Bulan Ini -->
                <div class="bg-gradient-to-r from-rose-600 to-rose-700 text-white p-5 rounded-xl shadow-md border border-rose-500/20 relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition"></div>
                    <span class="text-xs uppercase tracking-wider text-rose-100 font-semibold">Pengeluaran (<?= $bulan_sekarang ?>)</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format($beban_bulanan, 0, ',', '.') ?></h2>
                    <div class="text-xs text-rose-200 mt-3 pt-2 border-t border-white/10 flex items-center">
                        <i class="fas fa-arrow-down mr-1"></i> Semua Beban Operasional
                    </div>
                </div>

                <!-- Cashflow Surplus/Defisit -->
                <?php 
                $surplus = $pendapatan_bulanan - $beban_bulanan;
                $bg_c = $surplus >= 0 ? 'from-indigo-600 to-indigo-700 border-indigo-500/20' : 'from-red-600 to-red-700 border-red-500/20';
                $txt_sub = $surplus >= 0 ? 'Surplus Cashflow' : 'Defisit Cashflow';
                ?>
                <div class="bg-gradient-to-r <?= $bg_c ?> text-white p-5 rounded-xl shadow-md relative overflow-hidden group">
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-white/10 rounded-full blur-xl group-hover:scale-125 transition"></div>
                    <span class="text-xs uppercase tracking-wider text-indigo-100 font-semibold">Status Arus Kas</span>
                    <h2 class="text-2xl font-black mt-2">Rp <?= number_format(abs($surplus), 0, ',', '.') ?></h2>
                    <div class="text-xs text-indigo-200 mt-3 pt-2 border-t border-white/10 flex items-center">
                        <i class="fas <?= $surplus >= 0 ? 'fa-smile-beam' : 'fa-sad-tear' ?> mr-1.5"></i> <?= $txt_sub ?> Bulan Ini
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT: JURNAL & PENCATATAN -->
            <div id="tab-jurnal" class="tab-pane">
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
        </main>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Data Keuangan untuk AI Auditor
        const totalKasBank = <?= $total_kas_bank ?>;
        const kasUtama = <?= $kas_utama ?>;
        const bankBsi = <?= $bank_bsi ?>;
        const pendapatanBulanIni = <?= $pendapatan_bulanan ?>;
        const bebanBulanIni = <?= $beban_bulanan ?>;
        const overdueSppCount = <?= count($overdue_santri) ?>;
        const coaBalances = <?= json_encode($ringkasan) ?>;
        const recentTransactions = <?= json_encode($recent_transactions) ?>;
        const currentBulan = <?= json_encode($bulan_sekarang) ?>;
        const currentTahun = <?= json_encode($tahun_sekarang) ?>;

        // Switch Tab
        function switchTab(tabId) {
            // Hide all
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            // Remove active classes from buttons
            document.querySelectorAll('[id^="btn-tab-"]').forEach(btn => {
                btn.className = "px-4 py-2 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-amber-700 focus:outline-none transition";
            });

            // Show active
            document.getElementById(tabId).classList.remove('hidden');
            // Add active class
            document.getElementById('btn-' + tabId).className = "px-4 py-2 text-sm font-bold border-b-2 border-amber-600 text-amber-700 focus:outline-none transition";
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
                recent_transactions: recentTransactions
            };

            const prompt = "Anda adalah AI Agent Auditor & Konsultan Keuangan Yayasan Villa Quran (Sekolah Tahfidz, Filantropi, dan Unit Usaha).\n"
                + "Tugas Anda adalah menganalisa data keuangan Yayasan berikut, memberikan laporan audit kepatuhan, mendeteksi jika ada anomali atau potensi kebocoran dana, serta memberikan saran praktis agar cashflow tetap sehat atau surplus.\n\n"
                + "Berikut Ringkasan Keuangan Bulan Ini (" + currentBulan + " " + currentTahun + "):\n"
                + "- Saldo Terpusat Kas & Bank: Rp " + formatRupiah(payloadData.total_kas_bank) + "\n"
                + "  * Kas Utama: Rp " + formatRupiah(payloadData.kas_utama) + "\n"
                + "  * Bank BSI: Rp " + formatRupiah(payloadData.bank_bsi) + "\n"
                + "- Pemasukan Bulan Ini: Rp " + formatRupiah(payloadData.pendapatan_bulan_ini) + "\n"
                + "- Pengeluaran Bulan Ini: Rp " + formatRupiah(payloadData.beban_bulan_ini) + "\n"
                + "- Wali Santri Belum Bayar SPP: " + payloadData.overdue_spp_count + " orang\n\n"
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
