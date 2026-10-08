<?php
/**
 * DATABASE & MANAJEMEN PENDAFTAR PSB / SPMB ONLINE
 * Villa Quran Baron Malang (SADIGS 4.0)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';
require_once 'auth-unified.php';

// Validasi Akses: Boleh diakses jika logged in (Admin, Yayasan, Marketing, Kepala Sekolah, dll.)
$user = getCurrentUser();
if (!$user) {
    // Cek fallback session lama jika belum migrasi auth
    if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['yayasan_logged_in']) && !isset($_SESSION['yayasan_user']) && !isset($_SESSION['app_user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Pastikan struktur tabel pendaftar_spmb lengkap
$conn->query("CREATE TABLE IF NOT EXISTS pendaftar_spmb (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenjang VARCHAR(50),
    nama_lengkap VARCHAR(150),
    nik VARCHAR(20),
    nisn VARCHAR(20),
    whatsapp_ortu VARCHAR(20),
    asal_sekolah VARCHAR(150),
    berkas_foto VARCHAR(255),
    berkas_akta VARCHAR(255),
    berkas_kk VARCHAR(255),
    berkas_ktp VARCHAR(255),
    berkas_transfer VARCHAR(255),
    status VARCHAR(50) DEFAULT 'Menunggu Tes',
    status_daftar_ulang VARCHAR(50) DEFAULT 'Belum',
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Cek dan tambahkan kolom jika belum ada
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS asal_sekolah VARCHAR(150) AFTER whatsapp_ortu");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS berkas_foto VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS berkas_akta VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS berkas_kk VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS berkas_ktp VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS berkas_transfer VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS status_daftar_ulang VARCHAR(50) DEFAULT 'Belum' AFTER status");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN IF NOT EXISTS catatan_admin TEXT AFTER status_daftar_ulang");

// Fitur Hapus Data Pendaftar
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM pendaftar_spmb WHERE id = $id");
    header("Location: admin-spmb.php?pesan=" . urlencode("Data pendaftar berhasil dihapus."));
    exit;
}

// Fitur Ubah Status Seleksi
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE pendaftar_spmb SET status = '$status' WHERE id = $id");
    header("Location: admin-spmb.php?pesan=" . urlencode("Status pendaftar berhasil diperbarui menjadi $status."));
    exit;
}

// Fitur Ubah Status Daftar Ulang
if (isset($_GET['daftar_ulang']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $val = $conn->real_escape_string($_GET['daftar_ulang']);
    $conn->query("UPDATE pendaftar_spmb SET status_daftar_ulang = '$val' WHERE id = $id");
    header("Location: admin-spmb.php?pesan=" . urlencode("Status daftar ulang diperbarui."));
    exit;
}

// Ambil Statistik
$q_total = $conn->query("SELECT COUNT(*) as c FROM pendaftar_spmb");
$total_pendaftar = $q_total ? (int)$q_total->fetch_assoc()['c'] : 0;

$q_menunggu = $conn->query("SELECT COUNT(*) as c FROM pendaftar_spmb WHERE status = 'Menunggu Tes' OR status IS NULL OR status = ''");
$total_menunggu = $q_menunggu ? (int)$q_menunggu->fetch_assoc()['c'] : 0;

$q_lulus = $conn->query("SELECT COUNT(*) as c FROM pendaftar_spmb WHERE status = 'Lulus Seleksi'");
$total_lulus = $q_lulus ? (int)$q_lulus->fetch_assoc()['c'] : 0;

$q_tolak = $conn->query("SELECT COUNT(*) as c FROM pendaftar_spmb WHERE status = 'Ditolak'");
$total_tolak = $q_tolak ? (int)$q_tolak->fetch_assoc()['c'] : 0;

// Ambil Semua Data
$pendaftar = [];
$res = $conn->query("SELECT * FROM pendaftar_spmb ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pendaftar[] = $row;
    }
}

$active_menu = 'spmb';
$nama_user = $user['nama_lengkap'] ?? ($_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'Pengurus'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database PSB & SPMB Online | Villa Quran Baron Malang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden antialiased">

    <!-- SIDEBAR -->
    <?php 
    if (file_exists('sidebar-marketing.php')) {
        include 'sidebar-marketing.php';
    }
    ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP BAR -->
        <header class="h-16 bg-white border-b border-slate-200/80 flex items-center justify-between px-6 z-10 flex-shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-slate-500 hover:text-slate-700 md:hidden p-1.5 rounded-lg focus:outline-none">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h2 class="font-bold text-slate-800 text-sm sm:text-base">Database PSB Online (SPMB)</h2>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="daftar-spmb.html" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold text-xs rounded-xl border border-emerald-200 transition">
                    <i class="fas fa-external-link-alt text-[10px]"></i> Buka Form Pendaftaran
                </a>
                <a href="dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                    <i class="fas fa-arrow-left text-[10px]"></i> Ke Dashboard
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE AREA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 space-y-6">
            
            <!-- ALERT NOTIFIKASI -->
            <?php if (isset($_GET['pesan'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2 text-xs sm:text-sm font-semibold">
                        <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                        <span><?= htmlspecialchars($_GET['pesan']) ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- HEADER BANNER & ACTION -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-teal-800 via-emerald-800 to-teal-900 p-6 rounded-2xl text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-white/5 rounded-full blur-2xl"></div>
                <div class="relative z-10">
                    <span class="px-2.5 py-0.5 bg-white/20 text-teal-100 rounded-full text-[10px] font-bold uppercase tracking-wider inline-block mb-1.5">
                        Penerimaan Santri Baru (PSB)
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        Database Calon Santri & Walisantri
                    </h1>
                    <p class="text-xs sm:text-sm text-teal-100/90 mt-1 max-w-2xl leading-relaxed">
                        Pusat monitoring data pendaftaran online dari link <code class="bg-black/30 px-2 py-0.5 rounded text-amber-300 font-mono text-xs">/daftar-spmb.html</code>. Kelola verifikasi berkas, status seleksi, hingga daftar ulang.
                    </p>
                </div>
                <div class="relative z-10 flex flex-wrap gap-2.5 flex-shrink-0">
                    <a href="export-spmb.php" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                        <i class="fas fa-file-excel"></i> Export Data (Excel)
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode("Formulir Pendaftaran Santri Baru Villa Quran Baron Malang dapat diakses di https://villaquranbaronmalang.com/daftar-spmb.html") ?>" target="_blank" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-2">
                        <i class="fab fa-whatsapp"></i> Bagikan Link PSB
                    </a>
                </div>
            </div>

            <!-- 4 SUMMARY STAT CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <!-- Total -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Pendaftar</span>
                        <h3 class="text-2xl font-black text-slate-900 mt-0.5"><?= $total_pendaftar ?></h3>
                        <span class="text-[10px] text-slate-500 mt-0.5 block">Semua gelombang</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-xl font-bold shadow-xs">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <!-- Menunggu Tes -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Menunggu Tes</span>
                        <h3 class="text-2xl font-black text-amber-700 mt-0.5"><?= $total_menunggu ?></h3>
                        <span class="text-[10px] text-amber-500 mt-0.5 block">Perlu dijadwalkan</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center text-xl font-bold shadow-xs">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>

                <!-- Lulus Seleksi -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Lulus Seleksi</span>
                        <h3 class="text-2xl font-black text-emerald-700 mt-0.5"><?= $total_lulus ?></h3>
                        <span class="text-[10px] text-emerald-500 mt-0.5 block">Calon santri diterima</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-xl font-bold shadow-xs">
                        <i class="fas fa-check-double"></i>
                    </div>
                </div>

                <!-- Ditolak -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-rose-500 uppercase tracking-wider block">Tidak Lolos</span>
                        <h3 class="text-2xl font-black text-rose-700 mt-0.5"><?= $total_tolak ?></h3>
                        <span class="text-[10px] text-rose-400 mt-0.5 block">Ditolak / Batal</span>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold shadow-xs">
                        <i class="fas fa-user-xmark"></i>
                    </div>
                </div>
            </div>

            <!-- SEARCH & FILTER TOOLBAR -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-3 items-center justify-between">
                <div class="relative w-full md:w-80">
                    <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama santri, WA, atau sekolah..." 
                           class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-emerald-500 focus:outline-none transition">
                </div>

                <div class="flex flex-wrap items-center gap-2.5 w-full md:w-auto">
                    <!-- Filter Jenjang -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 uppercase">Jenjang:</span>
                        <select id="jenjangFilter" onchange="filterTable()" class="bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                            <option value="semua">Semua Jenjang</option>
                            <option value="SMP">SMP</option>
                            <option value="SMA">SMA</option>
                            <option value="Takhosus">Takhosus</option>
                        </select>
                    </div>

                    <!-- Filter Status -->
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-slate-400 uppercase">Status:</span>
                        <select id="statusFilter" onchange="filterTable()" class="bg-slate-50 border border-slate-200 text-xs font-bold text-slate-700 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
                            <option value="semua">Semua Status</option>
                            <option value="Menunggu Tes">Menunggu Tes</option>
                            <option value="Lulus Seleksi">Lulus Seleksi</option>
                            <option value="Ditolak">Ditolak</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TABEL DATA PENDAFTAR -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-list-check text-emerald-600"></i>
                        <h3 class="font-bold text-slate-800 text-sm">Daftar Formulir Masuk</h3>
                    </div>
                    <span class="text-xs font-bold text-slate-500 bg-slate-200/70 px-2.5 py-0.5 rounded-full" id="countDisplay">
                        <?= count($pendaftar) ?> Pendaftar
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-xs" id="tablePendaftar">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="py-3.5 px-4 text-left">No & Tanggal</th>
                                <th class="py-3.5 px-4 text-left">Identitas Santri</th>
                                <th class="py-3.5 px-4 text-left">Jenjang & Asal Sekolah</th>
                                <th class="py-3.5 px-4 text-left">Kontak Walisantri</th>
                                <th class="py-3.5 px-4 text-center">Berkas & Bukti</th>
                                <th class="py-3.5 px-4 text-center">Status Seleksi</th>
                                <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-sans">
                            <?php if (!empty($pendaftar)): ?>
                                <?php foreach ($pendaftar as $idx => $p): ?>
                                <?php
                                    $st = $p['status'] ?? 'Menunggu Tes';
                                    $bg_badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($st === 'Lulus Seleksi') $bg_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    elseif ($st === 'Ditolak') $bg_badge = 'bg-rose-50 text-rose-700 border-rose-200';

                                    $clean_wa = preg_replace('/[^0-9]/', '', $p['whatsapp_ortu'] ?? '');
                                    if (substr($clean_wa, 0, 1) === '0') $clean_wa = '62' . substr($clean_wa, 1);
                                    
                                    $wa_text = urlencode("Assalamu'alaikum Warahmatullahi Wabarakatuh.\n" .
                                        "Bapak/Ibu wali dari Ananda *{$p['nama_lengkap']}*,\n" .
                                        "Terima kasih telah mengisi Formulir Pendaftaran Santri Baru (PSB) Villa Quran Baron Malang untuk jenjang *" . strtoupper($p['jenjang']) . "*.\n\n" .
                                        "Status berkas Ananda saat ini: *{$st}*.\n" .
                                        "Ada hal yang ingin kami konfirmasikan mengenai jadwal observasi/tes masuk. Apakah waktu luang Bapak/Ibu bersedia untuk dihubungi?");
                                ?>
                                <tr class="hover:bg-slate-50/80 transition" data-nama="<?= strtolower($p['nama_lengkap']) ?>" data-jenjang="<?= strtoupper($p['jenjang']) ?>" data-status="<?= $st ?>" data-sekolah="<?= strtolower($p['asal_sekolah'] ?? '') ?>" data-wa="<?= $clean_wa ?>">
                                    <!-- No & Tgl -->
                                    <td class="py-3 px-4 whitespace-nowrap text-slate-500">
                                        <div class="font-bold text-slate-800">#<?= $p['id'] ?></div>
                                        <div class="text-[10.5px] text-slate-400">
                                            <?= !empty($p['created_at']) ? date('d M Y, H:i', strtotime($p['created_at'])) : '-' ?>
                                        </div>
                                    </td>

                                    <!-- Identitas Santri -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-800 font-bold text-xs flex items-center justify-center border border-emerald-200 flex-shrink-0">
                                                <?= strtoupper(substr($p['nama_lengkap'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($p['nama_lengkap']) ?></span>
                                                <div class="text-[10px] text-slate-400 space-x-1">
                                                    <?php if (!empty($p['nik'])): ?><span>NIK: <?= htmlspecialchars($p['nik']) ?></span><?php endif; ?>
                                                    <?php if (!empty($p['nisn'])): ?><span>• NISN: <?= htmlspecialchars($p['nisn']) ?></span><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Jenjang & Asal Sekolah -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded font-bold text-[10.5px] uppercase inline-block">
                                            <?= htmlspecialchars($p['jenjang']) ?>
                                        </span>
                                        <span class="block text-[11px] text-slate-500 font-medium mt-0.5">
                                            <?= !empty($p['asal_sekolah']) ? htmlspecialchars($p['asal_sekolah']) : '<i class="text-slate-300">Asal sekolah -</i>' ?>
                                        </span>
                                    </td>

                                    <!-- Kontak Ortu -->
                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <?php if (!empty($p['whatsapp_ortu'])): ?>
                                            <a href="https://wa.me/<?= $clean_wa ?>?text=<?= $wa_text ?>" target="_blank" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-bold rounded-lg border border-emerald-200 transition text-[11px]" title="Chat WhatsApp Walisantri">
                                                <i class="fab fa-whatsapp text-emerald-600"></i> <?= htmlspecialchars($p['whatsapp_ortu']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-slate-300 italic">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Berkas & Bukti -->
                                    <td class="py-3 px-4 whitespace-nowrap text-center">
                                        <button type="button" 
                                                onclick="openBerkasModal(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg border border-slate-200 transition text-[11px] inline-flex items-center gap-1">
                                            <i class="fas fa-folder-open text-amber-600"></i> Lihat Berkas
                                        </button>
                                    </td>

                                    <!-- Status Seleksi -->
                                    <td class="py-3 px-4 whitespace-nowrap text-center">
                                        <div class="inline-block relative">
                                            <span class="px-2.5 py-1 rounded-full text-[10.5px] font-bold border <?= $bg_badge ?> inline-flex items-center gap-1">
                                                <?php if ($st === 'Lulus Seleksi'): ?>
                                                    <i class="fas fa-check"></i>
                                                <?php elseif ($st === 'Ditolak'): ?>
                                                    <i class="fas fa-times"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-clock"></i>
                                                <?php endif; ?>
                                                <?= $st ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Aksi Cepat -->
                                    <td class="py-3 px-4 whitespace-nowrap text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <!-- Tombol Lulus -->
                                            <a href="?status=Lulus Seleksi&id=<?= $p['id'] ?>" class="w-7 h-7 rounded-lg bg-emerald-50 hover:bg-emerald-600 text-emerald-600 hover:text-white border border-emerald-200 flex items-center justify-center transition" title="Luluskan / Terima">
                                                <i class="fas fa-check text-xs"></i>
                                            </a>
                                            <!-- Tombol Pending -->
                                            <a href="?status=Menunggu Tes&id=<?= $p['id'] ?>" class="w-7 h-7 rounded-lg bg-amber-50 hover:bg-amber-600 text-amber-600 hover:text-white border border-amber-200 flex items-center justify-center transition" title="Set Status Menunggu Tes">
                                                <i class="fas fa-hourglass text-xs"></i>
                                            </a>
                                            <!-- Tombol Tolak -->
                                            <a href="?status=Ditolak&id=<?= $p['id'] ?>" class="w-7 h-7 rounded-lg bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white border border-rose-200 flex items-center justify-center transition" title="Tolak / Tidak Lolos">
                                                <i class="fas fa-times text-xs"></i>
                                            </a>
                                            <!-- Tombol Hapus -->
                                            <a href="?hapus_id=<?= $p['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus data pendaftar ananda <?= addslashes($p['nama_lengkap']) ?>?')" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-800 text-slate-500 hover:text-white border border-slate-200 flex items-center justify-center transition" title="Hapus Data">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-12 text-center text-slate-400 bg-slate-50/50">
                                        <div class="flex flex-col items-center justify-center">
                                            <i class="fas fa-user-graduate text-4xl text-slate-300 mb-2"></i>
                                            <p class="font-bold text-slate-600">Belum Ada Data Pendaftar Masuk</p>
                                            <p class="text-xs text-slate-400 mt-0.5">Calon walisantri yang mengisi form di link <span class="text-emerald-600">/daftar-spmb.html</span> akan otomatis tampil di sini.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL POPUP LIHAT DETAIL & BERKAS PENDAFTAR -->
    <div id="modal-berkas" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4 transition-all">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full border border-slate-100 overflow-hidden transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-emerald-700 to-teal-800 px-6 py-4 text-white flex items-center justify-between shadow-sm">
                <div>
                    <h3 class="font-bold text-base" id="mb-nama">Detail Berkas Santri</h3>
                    <p class="text-xs text-emerald-100" id="mb-meta">Jenjang • Asal Sekolah</p>
                </div>
                <button onclick="closeBerkasModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 space-y-4 max-h-[75vh] overflow-y-auto">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">No WhatsApp Ortu:</span>
                        <span class="font-bold text-slate-800" id="mb-wa">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Status Pendaftaran:</span>
                        <span class="font-bold text-emerald-700" id="mb-status">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">NIK Santri:</span>
                        <span class="font-semibold text-slate-800" id="mb-nik">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">NISN Santri:</span>
                        <span class="font-semibold text-slate-800" id="mb-nisn">-</span>
                    </div>
                </div>

                <h4 class="font-bold text-xs text-slate-700 uppercase tracking-wider pt-2">Dokumen & Berkas Terlampir:</h4>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="mb-files-grid">
                    <!-- Dynamic Files will be injected by JavaScript -->
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-50 px-6 py-3 border-t border-slate-100 flex items-center justify-between">
                <a id="mb-wa-btn" href="#" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl inline-flex items-center gap-1.5 shadow-xs transition">
                    <i class="fab fa-whatsapp"></i> Hubungi via WA
                </a>
                <button onclick="closeBerkasModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Modal Handlers
        function openBerkasModal(p) {
            if (!p) return;

            document.getElementById('mb-nama').innerText = p.nama_lengkap || 'Calon Santri';
            document.getElementById('mb-meta').innerText = `Jenjang: ${(p.jenjang || '-').toUpperCase()} • Asal: ${p.asal_sekolah || '-'}`;
            document.getElementById('mb-wa').innerText = p.whatsapp_ortu || '-';
            document.getElementById('mb-status').innerText = p.status || 'Menunggu Tes';
            document.getElementById('mb-nik').innerText = p.nik || '-';
            document.getElementById('mb-nisn').innerText = p.nisn || '-';

            // WA Link
            let cleanWa = (p.whatsapp_ortu || '').replace(/[^0-9]/g, '');
            if (cleanWa.startsWith('0')) cleanWa = '62' + cleanWa.substring(1);
            const waMsg = encodeURIComponent(`Assalamu'alaikum Warahmatullahi Wabarakatuh Bapak/Ibu wali dari Ananda ${p.nama_lengkap}, kami dari Tim SPMB Villa Quran Baron Malang ingin mengonfirmasi pendaftaran Ananda.`);
            document.getElementById('mb-wa-btn').href = `https://wa.me/${cleanWa}?text=${waMsg}`;

            // Berkas Grid
            const grid = document.getElementById('mb-files-grid');
            grid.innerHTML = '';

            const berkasItems = [
                { title: 'Foto Santri', file: p.berkas_foto, icon: 'fa-id-badge', color: 'text-blue-600 bg-blue-50 border-blue-200' },
                { title: 'Akta Kelahiran', file: p.berkas_akta, icon: 'fa-file-lines', color: 'text-emerald-600 bg-emerald-50 border-emerald-200' },
                { title: 'Kartu Keluarga', file: p.berkas_kk, icon: 'fa-users', color: 'text-indigo-600 bg-indigo-50 border-indigo-200' },
                { title: 'KTP Orang Tua', file: p.berkas_ktp, icon: 'fa-address-card', color: 'text-purple-600 bg-purple-50 border-purple-200' },
                { title: 'Bukti Transfer', file: p.berkas_transfer, icon: 'fa-receipt', color: 'text-amber-600 bg-amber-50 border-amber-200' }
            ];

            let hasFiles = false;
            berkasItems.forEach(b => {
                if (b.file && b.file.trim() !== '') {
                    hasFiles = true;
                    const card = document.createElement('a');
                    card.href = 'uploads/spmb/' + encodeURIComponent(b.file);
                    card.target = '_blank';
                    card.className = `p-3 rounded-xl border flex flex-col items-center justify-center text-center gap-1.5 hover:shadow-md transition ${b.color}`;
                    card.innerHTML = `
                        <i class="fas ${b.icon} text-2xl"></i>
                        <span class="font-bold text-[11px] block">${b.title}</span>
                        <span class="text-[9px] underline opacity-80">Lihat File &rarr;</span>
                    `;
                    grid.appendChild(card);
                }
            });

            if (!hasFiles) {
                grid.innerHTML = '<div class="col-span-full py-4 text-center text-slate-400 italic text-xs">Pendaftar belum mengunggah berkas digital.</div>';
            }

            const modal = document.getElementById('modal-berkas');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeBerkasModal() {
            const modal = document.getElementById('modal-berkas');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Filter Table Function
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const jenjang = document.getElementById('jenjangFilter').value.toUpperCase();
            const status = document.getElementById('statusFilter').value;
            
            const rows = document.querySelectorAll('#tablePendaftar tbody tr');
            let visibleCount = 0;

            rows.forEach(row => {
                if (!row.dataset.nama) return;

                const rNama = row.dataset.nama || '';
                const rSekolah = row.dataset.sekolah || '';
                const rWa = row.dataset.wa || '';
                const rJenjang = row.dataset.jenjang || '';
                const rStatus = row.dataset.status || '';

                const matchSearch = rNama.includes(search) || rSekolah.includes(search) || rWa.includes(search);
                const matchJenjang = (jenjang === 'SEMUA') || (rJenjang === jenjang);
                const matchStatus = (status === 'semua') || (rStatus === status);

                if (matchSearch && matchJenjang && matchStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('countDisplay').innerText = `${visibleCount} Pendaftar`;
        }

        // Sidebar Toggle
        const openBtn = document.getElementById('open-sidebar');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');

        if (openBtn && sidebar) {
            openBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                if (overlay) overlay.classList.toggle('hidden');
            });
        }
        if (overlay && sidebar) {
            overlay.addEventListener('click', () => {
                sidebar.classList.add('hidden');
                overlay.classList.add('hidden');
            });
        }
    </script>
</body>
</html>