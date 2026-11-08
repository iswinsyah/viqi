<?php
/**
 * MENU DOMPET NUSUK - DIGITAL WALLET & DOKUMEN JAMAAH
 * SADIGS 4.0 - Villa Quran Baron Malang
 * 
 * Menampilkan 7 Berkas Resmi Perjalanan Jamaah:
 * a. Foto KTP
 * b. Foto Kartu Keluarga
 * c. Foto Buku Nikah
 * d. e-Visa Saudi
 * f. Foto Ticket & Hotel
 * g. Paspor Digital
 * h. Kartu Nusuk
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth-unified.php';

// Validasi Login
$user = getCurrentUser();
if (!$user) {
    if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['yayasan_logged_in']) && !isset($_SESSION['yayasan_user']) && !isset($_SESSION['app_user_id']) && !isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

// Folder uploads
$upload_dir = __DIR__ . '/uploads/jamaah/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Ambil Semua Jamaah dari data_center_users
$search_jamaah = trim($_GET['search'] ?? '');
$where_j = "status = 'Jamaah'";
if (!empty($search_jamaah)) {
    $s_esc = $conn->real_escape_string($search_jamaah);
    $where_j .= " AND (nama_lengkap LIKE '%$s_esc%' OR no_whatsapp LIKE '%$s_esc%' OR nomor_paspor LIKE '%$s_esc%' OR nomor_nusuk LIKE '%$s_esc%')";
}

$res_jamaah = $conn->query("SELECT * FROM data_center_users WHERE $where_j ORDER BY id DESC");
$list_jamaah = [];
if ($res_jamaah && $res_jamaah->num_rows > 0) {
    while ($j = $res_jamaah->fetch_assoc()) {
        $list_jamaah[] = $j;
    }
}

// Tentukan Jamaah Aktif yang sedang dibuka
$selected_id = isset($_GET['jamaah_id']) ? (int)$_GET['jamaah_id'] : 0;
$active_jamaah = null;

if ($selected_id > 0) {
    foreach ($list_jamaah as $j) {
        if ((int)$j['id'] === $selected_id) {
            $active_jamaah = $j;
            break;
        }
    }
    // Jika tidak ada di filter pencarian, cari langsung ke DB
    if (!$active_jamaah) {
        $q_act = $conn->query("SELECT * FROM data_center_users WHERE id = $selected_id AND status = 'Jamaah' LIMIT 1");
        if ($q_act && $q_act->num_rows > 0) {
            $active_jamaah = $q_act->fetch_assoc();
        }
    }
}

// Fallback ke jamaah pertama jika belum ada yang dipilih
if (!$active_jamaah && !empty($list_jamaah)) {
    $active_jamaah = $list_jamaah[0];
}

// Hitung dokumen yang lengkap untuk jamaah aktif
$active_docs_count = 0;
if ($active_jamaah) {
    $keys_doc = ['foto_ktp', 'foto_kk', 'foto_buku_nikah', 'evisa_saudi', 'foto_tiket_hotel', 'paspor_digital', 'kartu_nusuk'];
    foreach ($keys_doc as $kd) {
        if (!empty($active_jamaah[$kd])) {
            $active_docs_count++;
        }
    }
}

$active_menu = 'dompet_nusuk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Dompet Nusuk | SADIGS 4.0 - Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .gold-shimmer {
            background: linear-gradient(135deg, #1e3a2b 0%, #0d2318 50%, #163625 100%);
            box-shadow: 0 10px 30px -10px rgba(13, 35, 24, 0.5);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 flex h-screen overflow-hidden antialiased">

    <!-- SIDEBAR -->
    <?php 
    if (file_exists(__DIR__ . '/sidebar-marketing.php')) {
        include __DIR__ . '/sidebar-marketing.php';
    } elseif (file_exists(__DIR__ . '/sidebar.php')) {
        include __DIR__ . '/sidebar.php';
    }
    ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative bg-slate-900">
        
        <!-- HEADER TOP BAR (NUSUK GOLD & EMERALD) -->
        <header class="h-16 bg-slate-950/80 backdrop-blur-md border-b border-amber-500/20 flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-amber-400 hover:text-amber-200 md:hidden p-1.5 rounded-lg focus:outline-none">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-600 text-slate-950 flex items-center justify-center font-black shadow-md shadow-amber-500/20">
                        <i class="fas fa-wallet text-sm"></i>
                    </div>
                    <div>
                        <h1 class="font-extrabold text-amber-300 text-sm sm:text-base flex items-center gap-2">
                            <span>Menu Dompet Nusuk</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-bold hidden sm:inline-block">
                                Saudi Pilgrim Wallet
                            </span>
                        </h1>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2 sm:gap-3">
                <a href="admin-data-center.php?status=Jamaah" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl border border-slate-700 transition">
                    <i class="fas fa-database text-emerald-400 text-xs"></i>
                    <span>Menu Data Center</span>
                </a>
                
                <a href="dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl transition">
                    <i class="fas fa-house text-slate-400"></i>
                    <span class="hidden sm:inline">Beranda</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
            
            <!-- SIDEBAR LIST JAMAAH (KIRI) -->
            <aside class="w-full lg:w-80 bg-slate-950/60 border-b lg:border-b-0 lg:border-r border-slate-800 flex flex-col flex-shrink-0">
                <!-- Search Jamaah -->
                <div class="p-3.5 border-b border-slate-800">
                    <form method="GET" class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs"></i>
                        <input type="text" name="search" value="<?= htmlspecialchars($search_jamaah) ?>" placeholder="Cari nama jamaah, paspor..." class="w-full pl-8 pr-3 py-1.5 bg-slate-900 border border-slate-800 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-amber-500 transition">
                        <?php if ($active_jamaah): ?>
                            <input type="hidden" name="jamaah_id" value="<?= $active_jamaah['id'] ?>">
                        <?php endif; ?>
                    </form>
                </div>

                <!-- List Items (Scrollable) -->
                <div class="flex-1 overflow-y-auto p-2 space-y-1 no-scrollbar">
                    <?php if (!empty($list_jamaah)): ?>
                        <?php foreach ($list_jamaah as $jm): 
                            $is_sel = ($active_jamaah && (int)$active_jamaah['id'] === (int)$jm['id']);
                            $jm_docs = (!empty($jm['foto_ktp']) ? 1 : 0) +
                                       (!empty($jm['foto_kk']) ? 1 : 0) +
                                       (!empty($jm['foto_buku_nikah']) ? 1 : 0) +
                                       (!empty($jm['evisa_saudi']) ? 1 : 0) +
                                       (!empty($jm['foto_tiket_hotel']) ? 1 : 0) +
                                       (!empty($jm['paspor_digital']) ? 1 : 0) +
                                       (!empty($jm['kartu_nusuk']) ? 1 : 0);
                        ?>
                            <a href="admin-dompet-nusuk.php?jamaah_id=<?= $jm['id'] ?><?= !empty($search_jamaah) ? '&search=' . urlencode($search_jamaah) : '' ?>" class="block p-3 rounded-xl transition <?= $is_sel ? 'bg-gradient-to-r from-amber-500/20 to-emerald-500/10 border border-amber-500/40 text-white' : 'hover:bg-slate-900/80 text-slate-300 border border-transparent' ?>">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="font-bold text-xs truncate <?= $is_sel ? 'text-amber-300' : 'text-slate-200' ?>">
                                        <?= htmlspecialchars($jm['nama_lengkap']) ?>
                                    </h4>
                                    <span class="text-[9px] font-black px-1.5 py-0.2 rounded-full <?= $jm_docs === 7 ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' ?>">
                                        <?= $jm_docs ?>/7 Berkas
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                    <span><?= $jm['no_whatsapp'] ?></span>
                                    <span class="font-mono text-slate-500"><?= $jm['nomor_paspor'] ? 'Paspor: ' . $jm['nomor_paspor'] : '-' ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="py-8 px-4 text-center text-slate-500">
                            <i class="fas fa-users-slash text-2xl mb-2 text-slate-600"></i>
                            <p class="text-xs">Belum ada jamaah terdaftar.</p>
                            <a href="admin-data-center.php" class="mt-3 inline-block px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold transition">
                                + Tambah di Data Center
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-3 border-t border-slate-800 text-center">
                    <a href="admin-data-center.php" class="text-xs text-emerald-400 hover:underline font-bold inline-flex items-center gap-1">
                        <i class="fas fa-plus-circle"></i> Tambah Jamaah Baru
                    </a>
                </div>
            </aside>

            <!-- AREA DOMPET DIGITAL UTAMA (KANAN) -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 no-scrollbar bg-gradient-to-b from-slate-900 via-slate-900 to-slate-950">
                
                <?php if ($active_jamaah): ?>

                    <!-- 1. KARTU IDENTITAS DIGITAL NUSUK (BOARDING PASS / GOLD WALLET CARD) -->
                    <div class="gold-shimmer rounded-3xl p-5 sm:p-7 border border-amber-500/30 text-white relative overflow-hidden mb-8 shadow-2xl">
                        <!-- Pola Dekoratif Saudi / Kaaba Pattern -->
                        <div class="absolute -right-12 -top-12 w-48 h-48 rounded-full bg-amber-500/10 blur-2xl pointer-events-none"></div>
                        <div class="absolute right-6 top-6 text-amber-400/20 text-7xl font-black pointer-events-none">
                            <i class="fas fa-kaaba"></i>
                        </div>

                        <!-- Header Kartu -->
                        <div class="flex items-center justify-between border-b border-amber-500/20 pb-4 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500/20 border border-amber-400/40 flex items-center justify-center text-amber-300 text-lg">
                                    <i class="fas fa-id-badge"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] uppercase tracking-widest text-amber-400 font-extrabold">Official Saudi Nusuk Card</p>
                                    <h3 class="font-black text-base sm:text-lg text-white">Dompet Digital Jamaah Umrah</h3>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-400/30 text-emerald-300 text-xs font-black">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                <span>Status: Jamaah Terverifikasi</span>
                            </span>
                        </div>

                        <!-- Identitas Jamaah -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6 text-xs">
                            <div>
                                <p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Nama Jamaah</p>
                                <h4 class="text-white text-base font-black mt-0.5"><?= htmlspecialchars($active_jamaah['nama_lengkap']) ?></h4>
                                <p class="text-amber-300/80 text-[11px] mt-0.5"><?= $active_jamaah['jenis_kelamin'] ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Nomor Paspor</p>
                                <p class="text-amber-200 font-mono text-sm font-black mt-0.5"><?= htmlspecialchars($active_jamaah['nomor_paspor'] ?: 'Belum Diisi') ?></p>
                                <p class="text-slate-400 text-[10px] mt-0.5">NIK: <?= htmlspecialchars($active_jamaah['nik'] ?: '-') ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Nusuk Account ID</p>
                                <p class="text-amber-200 font-mono text-sm font-black mt-0.5"><?= htmlspecialchars($active_jamaah['nomor_nusuk'] ?: 'Belum Terhubung') ?></p>
                                <p class="text-slate-400 text-[10px] mt-0.5">KK: <?= htmlspecialchars($active_jamaah['nomor_kk'] ?: '-') ?></p>
                            </div>
                            <div>
                                <p class="text-slate-400 text-[10px] uppercase font-bold tracking-wider">Kelengkapan Berkas</p>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="flex-1 bg-slate-800 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-gradient-to-r from-amber-400 to-emerald-400 h-2.5 rounded-full" style="width: <?= round(($active_docs_count / 7) * 100) ?>%"></div>
                                    </div>
                                    <span class="font-extrabold text-amber-300 text-xs"><?= $active_docs_count ?>/7</span>
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1"><?= round(($active_docs_count / 7) * 100) ?>% siap berangkat</p>
                            </div>
                        </div>

                        <!-- Footer Kartu: Action Buttons -->
                        <div class="border-t border-amber-500/20 pt-4 mt-5 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex items-center gap-2 text-slate-300">
                                <i class="fab fa-whatsapp text-emerald-400 text-base"></i>
                                <span>No. Kontak: <strong><?= htmlspecialchars($active_jamaah['no_whatsapp']) ?></strong></span>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Bagikan Link Dompet ke Jamaah via WA -->
                                <?php 
                                    $wa_clean = preg_replace('/[^0-9]/', '', (substr($active_jamaah['no_whatsapp'], 0, 1) === '0' ? '62'.substr($active_jamaah['no_whatsapp'], 1) : $active_jamaah['no_whatsapp']));
                                    $share_text = "Assalamu'alaikum Bpk/Ibu " . $active_jamaah['nama_lengkap'] . ".\n\nBerikut adalah tautan verifikasi Dompet Nusuk & Berkas Perjalanan Umrah Anda:\nhttps://villaquranbaronmalang.com/dompet-nusuk.php?jamaah_id=" . $active_jamaah['id'] . "\n\nMohon pastikan 7 berkas persyaratan sudah lengkap dan sesuai. Terima kasih.\n- Villa Quran Travel";
                                    $wa_link = "https://api.whatsapp.com/send?phone=" . $wa_clean . "&text=" . urlencode($share_text);
                                ?>
                                <a href="<?= $wa_link ?>" target="_blank" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold transition flex items-center gap-1.5 shadow-sm">
                                    <i class="fab fa-whatsapp"></i> Kirim ke WA Jamaah
                                </a>

                                <a href="admin-data-center.php?search=<?= urlencode($active_jamaah['nama_lengkap']) ?>" class="px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 font-bold border border-amber-500/30 transition flex items-center gap-1.5">
                                    <i class="fas fa-edit"></i> Edit di Data Center
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- 2. GRID 7 DOKUMEN UTAMA JAMAAH NUSUK -->
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="font-extrabold text-white text-base flex items-center gap-2">
                                <i class="fas fa-folder-open text-amber-400"></i>
                                <span>7 Dokumen Persyaratan Jamaah (Nusuk & Saudi Travel)</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Seluruh berkas digital tersimpan aman dan siap diverifikasi kapan saja.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        
                        <?php
                        $dokumen_items = [
                            [
                                'huruf' => 'a',
                                'label' => 'Foto KTP',
                                'sub'   => 'Identitas Kependudukan Resmi',
                                'icon'  => 'fa-id-card',
                                'color' => 'emerald',
                                'field' => 'foto_ktp',
                                'file'  => $active_jamaah['foto_ktp'] ?? null
                            ],
                            [
                                'huruf' => 'b',
                                'label' => 'Foto Kartu Keluarga',
                                'sub'   => 'Data Keluarga & Mahram',
                                'icon'  => 'fa-users',
                                'color' => 'blue',
                                'field' => 'foto_kk',
                                'file'  => $active_jamaah['foto_kk'] ?? null
                            ],
                            [
                                'huruf' => 'c',
                                'label' => 'Foto Buku Nikah',
                                'sub'   => 'Dokumen Bukti Mahram Pasutri',
                                'icon'  => 'fa-ring',
                                'color' => 'pink',
                                'field' => 'foto_buku_nikah',
                                'file'  => $active_jamaah['foto_buku_nikah'] ?? null
                            ],
                            [
                                'huruf' => 'd',
                                'label' => 'e-Visa Saudi',
                                'sub'   => 'Visa Resmi Kerajaan Arab Saudi',
                                'icon'  => 'fa-passport',
                                'color' => 'amber',
                                'field' => 'evisa_saudi',
                                'file'  => $active_jamaah['evisa_saudi'] ?? null
                            ],
                            [
                                'huruf' => 'f',
                                'label' => 'Foto Ticket & Hotel',
                                'sub'   => 'Voucher Penerbangan & Akomodasi',
                                'icon'  => 'fa-plane-departure',
                                'color' => 'indigo',
                                'field' => 'foto_tiket_hotel',
                                'file'  => $active_jamaah['foto_tiket_hotel'] ?? null
                            ],
                            [
                                'huruf' => 'g',
                                'label' => 'Paspor Digital',
                                'sub'   => 'Scan Paspor Republik Indonesia',
                                'icon'  => 'fa-book-atlas',
                                'color' => 'teal',
                                'field' => 'paspor_digital',
                                'file'  => $active_jamaah['paspor_digital'] ?? null
                            ],
                            [
                                'huruf' => 'h',
                                'label' => 'Kartu Nusuk',
                                'sub'   => 'Digital Nusuk Pilgrim Card / Permit',
                                'icon'  => 'fa-kaaba',
                                'color' => 'yellow',
                                'field' => 'kartu_nusuk',
                                'file'  => $active_jamaah['kartu_nusuk'] ?? null
                            ]
                        ];

                        foreach ($dokumen_items as $d):
                            $ada_file = !empty($d['file']);
                            $file_url = $ada_file ? 'uploads/jamaah/' . htmlspecialchars($d['file']) : '#';
                            $is_pdf = $ada_file && (strtolower(pathinfo($d['file'], PATHINFO_EXTENSION)) === 'pdf');
                        ?>
                        <!-- CARD DOKUMEN <?= strtoupper($d['huruf']) ?> -->
                        <div class="bg-slate-950/80 rounded-2xl border <?= $ada_file ? 'border-slate-800 hover:border-amber-500/40' : 'border-dashed border-slate-800' ?> p-4 flex flex-col justify-between transition-all duration-300 hover:shadow-xl group">
                            
                            <div>
                                <!-- Top Bar Dokumen -->
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-amber-500/10 text-amber-400 font-extrabold text-[11px] flex items-center justify-center border border-amber-500/20">
                                            <?= $d['huruf'] ?>
                                        </span>
                                        <h4 class="font-extrabold text-white text-xs truncate max-w-[130px]"><?= $d['label'] ?></h4>
                                    </div>
                                    <span class="text-[9px] font-black px-1.5 py-0.5 rounded-full <?= $ada_file ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-slate-800 text-slate-500' ?>">
                                        <?= $ada_file ? 'Tersedia' : 'Belum Ada' ?>
                                    </span>
                                </div>

                                <!-- Box Preview Gambar / PDF -->
                                <div class="w-full h-36 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden relative group/img">
                                    <?php if ($ada_file): ?>
                                        <?php if ($is_pdf): ?>
                                            <div class="flex flex-col items-center justify-center text-rose-400 p-3 text-center">
                                                <i class="fas fa-file-pdf text-3xl mb-1"></i>
                                                <span class="text-[10px] font-bold text-slate-300 truncate max-w-[150px]"><?= htmlspecialchars($d['file']) ?></span>
                                                <span class="text-[9px] text-slate-500 uppercase mt-0.5">Dokumen PDF</span>
                                            </div>
                                        <?php else: ?>
                                            <img src="<?= $file_url ?>" alt="<?= $d['label'] ?>" class="w-full h-full object-cover group-hover/img:scale-105 transition-transform duration-300">
                                        <?php endif; ?>

                                        <!-- Overlay Hover Aksi Cepat -->
                                        <div class="absolute inset-0 bg-slate-950/80 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center gap-2 p-2">
                                            <button onclick="bukaPreviewModal('<?= $file_url ?>', '<?= $d['label'] ?>', <?= $is_pdf ? 'true' : 'false' ?>)" class="w-8 h-8 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 flex items-center justify-center text-xs font-bold transition shadow-sm" title="Lihat Penuh">
                                                <i class="fas fa-search-plus"></i>
                                            </button>
                                            <a href="<?= $file_url ?>" download class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-white flex items-center justify-center text-xs font-bold transition" title="Unduh File">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="flex flex-col items-center justify-center text-slate-600 p-3 text-center">
                                            <i class="fas <?= $d['icon'] ?> text-2xl mb-1 text-slate-700"></i>
                                            <span class="text-[10px] text-slate-500 font-bold"><?= $d['sub'] ?></span>
                                            <span class="text-[9px] text-slate-600 mt-1">Belum diunggah</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Footer Card Dokumen -->
                            <div class="pt-3 border-t border-slate-900 mt-3 flex items-center justify-between text-xs">
                                <?php if ($ada_file): ?>
                                    <button onclick="bukaPreviewModal('<?= $file_url ?>', '<?= $d['label'] ?>', <?= $is_pdf ? 'true' : 'false' ?>)" class="text-amber-400 hover:text-amber-300 text-[11px] font-extrabold flex items-center gap-1">
                                        <i class="fas fa-eye text-[10px]"></i> Buka Berkas
                                    </button>
                                    <a href="<?= $file_url ?>" download class="text-slate-400 hover:text-white text-[11px] flex items-center gap-1 font-bold">
                                        <i class="fas fa-download text-[10px]"></i> Unduh
                                    </a>
                                <?php else: ?>
                                    <a href="admin-data-center.php?search=<?= urlencode($active_jamaah['nama_lengkap']) ?>" class="text-emerald-400 hover:underline text-[11px] font-bold inline-flex items-center gap-1">
                                        <i class="fas fa-upload text-[10px]"></i> Upload di Data Center
                                    </a>
                                <?php endif; ?>
                            </div>

                        </div>
                        <?php endforeach; ?>

                    </div>

                <?php else: ?>

                    <!-- Empty State jika belum ada jamaah -->
                    <div class="py-20 text-center max-w-md mx-auto text-slate-400">
                        <div class="w-20 h-20 rounded-3xl bg-slate-800 text-amber-400 flex items-center justify-center text-3xl mx-auto mb-4 border border-slate-700">
                            <i class="fas fa-kaaba"></i>
                        </div>
                        <h3 class="text-lg font-black text-white">Belum Ada Jamaah di Dompet Nusuk</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Buka <strong>Menu Data Center</strong>, lalu ubah status user menjadi <strong>Jamaah</strong> dan unggah 7 data tambahan (KTP, KK, Buku Nikah, Visa, Tiket, Paspor, Kartu Nusuk).
                        </p>
                        <a href="admin-data-center.php" class="mt-5 inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-extrabold text-xs rounded-xl shadow-lg transition">
                            <i class="fas fa-database"></i> Buka Menu Data Center
                        </a>
                    </div>

                <?php endif; ?>

            </main>
        </div>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL LIGHTBOX / FULLSCREEN DOCUMENT VIEWER               -->
    <!-- ========================================================= -->
    <div id="modalLightbox" class="fixed inset-0 bg-slate-950/90 backdrop-blur-md z-50 hidden flex flex-col p-4 sm:p-6">
        <!-- Top Toolbar -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-3">
            <div class="flex items-center gap-2">
                <i class="fas fa-file-invoice text-amber-400"></i>
                <h3 id="lightboxTitle" class="font-bold text-white text-sm">Pratinjau Dokumen</h3>
            </div>
            <div class="flex items-center gap-2">
                <a id="lightboxDownload" href="#" download class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-download"></i> Unduh
                </a>
                <button onclick="tutupLightbox()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Viewer Container -->
        <div class="flex-1 overflow-auto flex items-center justify-center p-2" id="lightboxContent">
            <!-- Dinamis Gambar atau Iframe PDF -->
        </div>
    </div>

    <script>
        function bukaPreviewModal(fileUrl, docTitle, isPdf) {
            document.getElementById('lightboxTitle').textContent = docTitle;
            document.getElementById('lightboxDownload').href = fileUrl;
            const container = document.getElementById('lightboxContent');

            if (isPdf) {
                container.innerHTML = `<iframe src="${fileUrl}" class="w-full h-full rounded-xl border border-slate-800 shadow-2xl bg-white" frameborder="0"></iframe>`;
            } else {
                container.innerHTML = `<img src="${fileUrl}" alt="${docTitle}" class="max-h-full max-w-full object-contain rounded-xl shadow-2xl border border-slate-800">`;
            }

            document.getElementById('modalLightbox').classList.remove('hidden');
        }

        function tutupLightbox() {
            document.getElementById('modalLightbox').classList.add('hidden');
            document.getElementById('lightboxContent').innerHTML = '';
        }

        // Toggle Sidebar Mobile
        document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.getElementById('open-sidebar');
            const closeBtn = document.getElementById('close-sidebar');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (openBtn && sidebar) {
                openBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    if (overlay) overlay.classList.toggle('hidden');
                });
            }
            if (closeBtn && sidebar) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.add('hidden');
                    if (overlay) overlay.classList.add('hidden');
                });
            }
            if (overlay) {
                overlay.addEventListener('click', function() {
                    if (sidebar) sidebar.classList.add('hidden');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>

