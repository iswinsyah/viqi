<?php
// admin-brosur-settings.php
// Halaman Simulasi Live Brosur & Undangan Digital Smartphone
// Villa Quran Indonesia

require_once 'auth.php';
require_once 'koneksi.php';

// Pastikan baris pengaturan_brosur ada di database
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_brosur (
    id INT PRIMARY KEY DEFAULT 1,
    tahun_ajaran VARCHAR(100) DEFAULT '2026/2027',
    periode_gelombang VARCHAR(100) DEFAULT 'Gelombang 1 — Kuota Terbatas',
    kuota_santri INT DEFAULT 20,
    cover_bg_url TEXT,
    cover_overlay_opacity DECIMAL(3,2) DEFAULT 0.85,
    body_bg_url TEXT,
    body_overlay_opacity DECIMAL(3,2) DEFAULT 0.92,
    theme_preset VARCHAR(50) DEFAULT 'madinah',
    music_url TEXT,
    biaya_pendaftaran INT DEFAULT 350000,
    biaya_pangkal INT DEFAULT 12500000,
    biaya_tahunan INT DEFAULT 2500000,
    biaya_spp INT DEFAULT 1650000,
    diskon_gelombang INT DEFAULT 2000000,
    countdown_mode VARCHAR(20) DEFAULT 'auto',
    countdown_target DATETIME DEFAULT '2026-12-31 23:59:59',
    countdown_title VARCHAR(150) DEFAULT '⏳ Sisa Waktu Pendaftaran Berakhir:',
    show_countdown TINYINT(1) DEFAULT 1,
    cover_elements_pos TEXT,
    theme_color_mode VARCHAR(50) DEFAULT 'emerald_gold',
    text_color VARCHAR(30) DEFAULT '#ffffff',
    accent_color VARCHAR(30) DEFAULT '#fbbf24',
    btn_bg_color VARCHAR(100) DEFAULT '#d97706',
    btn_text_color VARCHAR(30) DEFAULT '#022d27',
    card_bg_style VARCHAR(30) DEFAULT 'glass_dark',
    font_family VARCHAR(50) DEFAULT 'Plus Jakarta Sans',
    judul_utama VARCHAR(150) DEFAULT 'Villa Quran Indonesia',
    subjudul VARCHAR(200) DEFAULT 'Sekolah Tahfidz Berasrama Nyaman Ala Villa',
    bismillah_text VARCHAR(150) DEFAULT 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ',
    tamu_header_text VARCHAR(150) DEFAULT 'Kepada Yth. Calon Wali Santri:',
    tamu_sambutan_text TEXT,
    btn_text VARCHAR(100) DEFAULT 'Buka Brosur & Undangan',
    show_bismillah TINYINT(1) DEFAULT 1,
    show_subjudul TINYINT(1) DEFAULT 1,
    show_logo TINYINT(1) DEFAULT 1,
    show_sambutan TINYINT(1) DEFAULT 1,
    logo_size INT DEFAULT 80,
    card_width INT DEFAULT 100,
    card_padding INT DEFAULT 20,
    btn_width INT DEFAULT 100,
    btn_height INT DEFAULT 52,
    text_title_size INT DEFAULT 22,
    text_sub_size INT DEFAULT 12,
    show_video TINYINT(1) DEFAULT 1,
    video_url TEXT,
    video_width INT DEFAULT 100,
    video_height INT DEFAULT 240,
    show_maps TINYINT(1) DEFAULT 1,
    maps_url TEXT,
    maps_width INT DEFAULT 100,
    maps_height INT DEFAULT 220,
    bottom_bar_bg_color VARCHAR(100) DEFAULT '#022d27',
    bottom_bar_text_color VARCHAR(30) DEFAULT '#ffffff',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Pastikan kolom untuk custom text & multi-layer JSON tersedia di tabel pengaturan_brosur
$columns_to_check = [
    'custom_text_items'   => "LONGTEXT",
    'custom_text_content' => "TEXT",
    'custom_text_format'  => "VARCHAR(20) DEFAULT 'h2'",
    'custom_text_color'   => "VARCHAR(30) DEFAULT '#ffffff'",
    'custom_text_font'    => "VARCHAR(50) DEFAULT 'Plus Jakarta Sans'",
    'custom_text_align'   => "VARCHAR(20) DEFAULT 'center'",
    'custom_text_size'    => "INT DEFAULT 24",
    'custom_text_pos_x'   => "DECIMAL(5,2) DEFAULT 50.00",
    'custom_text_pos_y'   => "DECIMAL(5,2) DEFAULT 35.00",
    'custom_text_width'   => "INT DEFAULT 85"
];
foreach ($columns_to_check as $col => $type) {
    $res = $conn->query("SHOW COLUMNS FROM pengaturan_brosur LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE pengaturan_brosur ADD COLUMN $col $type");
    }
}

// Pastikan tabel koleksi_background ada di database
$conn->query("CREATE TABLE IF NOT EXISTS koleksi_background (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipe VARCHAR(20) DEFAULT 'cover',
    judul VARCHAR(150) DEFAULT 'Background Portrait',
    url TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Pre-seed preset koleksi awal jika masih kosong
$cnt_koleksi = $conn->query("SELECT COUNT(*) as total FROM koleksi_background");
$row_k = $cnt_koleksi ? $cnt_koleksi->fetch_assoc() : ['total' => 0];
if (($row_k['total'] ?? 0) == 0) {
    $conn->query("INSERT INTO koleksi_background (tipe, judul, url) VALUES 
        ('cover', 'Arsitektur Kubah Hijau Klasik', 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80'),
        ('cover', 'Masjid Nabawi Madinah', 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=1200&auto=format&fit=crop&q=80'),
        ('cover', 'Mihrab Qur\'ani Mewah', 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=1200&auto=format&fit=crop&q=80'),
        ('body', 'Tekstur Kanvas Halus', 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1200&auto=format&fit=crop&q=80'),
        ('body', 'Villa Tropis Asri Pegunungan', 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=1200&auto=format&fit=crop&q=80')
    ");
}

// Handler Hapus Item Koleksi
if (isset($_GET['action']) && $_GET['action'] === 'delete_koleksi') {
    $del_id = (int)($_GET['id'] ?? 0);
    if ($del_id > 0) {
        $conn->query("DELETE FROM koleksi_background WHERE id = $del_id");
        header("Location: admin-brosur-settings.php?msg=koleksi_deleted");
        exit;
    }
}

// Variabel Notifikasi
$pesan_sukses = (($_GET['msg'] ?? '') === 'koleksi_deleted') ? 'Background berhasil dihapus dari koleksi.' : '';
$pesan_error  = '';

// Proses Simpan Pengaturan (Background & Tulisan Dinamis Multi-Kolom)
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $action_type = $_POST['action_type'] ?? 'save_bg';

    if ($action_type === 'save_text') {
        $json_raw = $_POST['custom_text_items_json'] ?? '';
        $decoded = json_decode($json_raw, true);

        if (!is_array($decoded) || empty($decoded)) {
            // Fallback single item jika JSON kosong
            $decoded = [[
                'id'      => 'text_1',
                'content' => trim($_POST['custom_text_content'] ?? 'Villa Quran Indonesia'),
                'format'  => trim($_POST['custom_text_format'] ?? 'h2'),
                'color'   => trim($_POST['custom_text_color'] ?? '#ffffff'),
                'font'    => trim($_POST['custom_text_font'] ?? 'Plus Jakarta Sans'),
                'align'   => trim($_POST['custom_text_align'] ?? 'center'),
                'size'    => (int)($_POST['custom_text_size'] ?? 24),
                'posX'    => (float)($_POST['custom_text_pos_x'] ?? 50.0),
                'posY'    => (float)($_POST['custom_text_pos_y'] ?? 35.0),
                'width'   => (int)($_POST['custom_text_width'] ?? 85)
            ]];
        }

        $clean_items = [];
        foreach ($decoded as $idx => $it) {
            $clean_items[] = [
                'id'      => !empty($it['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $it['id']) : 'text_' . ($idx + 1),
                'content' => trim($it['content'] ?? ''),
                'format'  => in_array(strtolower($it['format'] ?? ''), ['h1','h2','h3','h4','h5','p']) ? strtolower($it['format']) : 'h2',
                'color'   => !empty($it['color']) ? $it['color'] : '#ffffff',
                'font'    => !empty($it['font']) ? trim($it['font']) : 'Plus Jakarta Sans',
                'align'   => in_array(strtolower($it['align'] ?? ''), ['left','center','right','justify']) ? strtolower($it['align']) : 'center',
                'size'    => max(8, min(120, (int)($it['size'] ?? 24))),
                'posX'    => round(max(0, min(100, (float)($it['posX'] ?? 50.0))), 2),
                'posY'    => round(max(0, min(100, (float)($it['posY'] ?? 35.0))), 2),
                'width'   => max(10, min(100, (int)($it['width'] ?? 85)))
            ];
        }

        $final_json = json_encode($clean_items, JSON_UNESCAPED_UNICODE);
        $final_json_esc = $conn->real_escape_string($final_json);

        // Update legacy columns dari item pertama sebagai fallback
        $first = $clean_items[0] ?? [
            'content' => 'Villa Quran Indonesia',
            'format' => 'h2',
            'color' => '#ffffff',
            'font' => 'Plus Jakarta Sans',
            'align' => 'center',
            'size' => 24,
            'posX' => 50,
            'posY' => 35,
            'width' => 85
        ];
        $c_content = $conn->real_escape_string($first['content']);
        $c_format  = $conn->real_escape_string($first['format']);
        $c_color   = $conn->real_escape_string($first['color']);
        $c_font    = $conn->real_escape_string($first['font']);
        $c_align   = $conn->real_escape_string($first['align']);
        $c_size    = (int)$first['size'];
        $c_pos_x   = (float)$first['posX'];
        $c_pos_y   = (float)$first['posY'];
        $c_width   = (int)$first['width'];

        $sql_text = "UPDATE pengaturan_brosur SET 
                        custom_text_items   = '$final_json_esc',
                        custom_text_content = '$c_content',
                        custom_text_format  = '$c_format',
                        custom_text_color   = '$c_color',
                        custom_text_font    = '$c_font',
                        custom_text_align   = '$c_align',
                        custom_text_size    = $c_size,
                        custom_text_pos_x   = $c_pos_x,
                        custom_text_pos_y   = $c_pos_y,
                        custom_text_width   = $c_width
                     WHERE id = 1";

        if ($conn->query($sql_text)) {
            $pesan_sukses = "Alhamdulillah! Pengaturan seluruh kolom tulisan (" . count($clean_items) . " kolom) berhasil disimpan.";
        } else {
            $pesan_error = "Gagal menyimpan tulisan: " . $conn->error;
        }
    } else {
        // Simpan Background
        $bg_url             = $conn->real_escape_string(trim($_POST['bg_url'] ?? ''));
        $bg_overlay_opacity = (float)($_POST['bg_overlay_opacity'] ?? 0.88);

        // Handle Upload File Background
        if (!empty($_FILES['bg_file']['name']) && $_FILES['bg_file']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['bg_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                if (!is_dir('upload')) mkdir('upload', 0755, true);
                $new_bg = 'upload/bg_brosur_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($_FILES['bg_file']['tmp_name'], $new_bg)) {
                    $bg_url = $new_bg;
                }
            }
        }

        // Terapkan ke cover dan body secara seragam
        $sql_update = "UPDATE pengaturan_brosur SET 
                        cover_bg_url = '$bg_url',
                        cover_overlay_opacity = $bg_overlay_opacity,
                        body_bg_url = '$bg_url',
                        body_overlay_opacity = $bg_overlay_opacity
                       WHERE id = 1";

        if ($conn->query($sql_update)) {
            if (!empty($bg_url)) {
                $check = $conn->query("SELECT id FROM koleksi_background WHERE url = '$bg_url' LIMIT 1");
                if ($check && $check->num_rows == 0) {
                    $judul = 'Background ' . date('d M Y');
                    $conn->query("INSERT INTO koleksi_background (tipe, judul, url) VALUES ('all', '$judul', '$bg_url')");
                }
            }

            $pesan_sukses = "Alhamdulillah! Background Brosur berhasil disimpan dan diterapkan ke semua halaman.";
        } else {
            $pesan_error = "Gagal menyimpan background: " . $conn->error;
        }
    }
}

// Ambil Data Koleksi Background
$koleksi_bg = [];
$q_koleksi = $conn->query("SELECT * FROM koleksi_background ORDER BY id DESC");
if ($q_koleksi && $q_koleksi->num_rows > 0) {
    while ($r = $q_koleksi->fetch_assoc()) $koleksi_bg[] = $r;
}

// Ambil Data Terkini dari Database
$q = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg = $q ? $q->fetch_assoc() : [];

// Ambil daftar text items atau inisialisasi default
$raw_items = $cfg['custom_text_items'] ?? '';
$text_items = !empty($raw_items) ? json_decode($raw_items, true) : null;
if (!is_array($text_items) || empty($text_items)) {
    $text_items = [
        [
            'id'      => 'text_' . time() . '_1',
            'content' => !empty($cfg['custom_text_content']) ? $cfg['custom_text_content'] : 'Villa Quran Indonesia',
            'format'  => !empty($cfg['custom_text_format']) ? $cfg['custom_text_format'] : 'h2',
            'color'   => !empty($cfg['custom_text_color']) ? $cfg['custom_text_color'] : '#ffffff',
            'font'    => !empty($cfg['custom_text_font']) ? $cfg['custom_text_font'] : 'Plus Jakarta Sans',
            'align'   => !empty($cfg['custom_text_align']) ? $cfg['custom_text_align'] : 'center',
            'size'    => !empty($cfg['custom_text_size']) ? (int)$cfg['custom_text_size'] : 24,
            'posX'    => isset($cfg['custom_text_pos_x']) ? (float)$cfg['custom_text_pos_x'] : 50.0,
            'posY'    => isset($cfg['custom_text_pos_y']) ? (float)$cfg['custom_text_pos_y'] : 35.0,
            'width'   => !empty($cfg['custom_text_width']) ? (int)$cfg['custom_text_width'] : 85
        ]
    ];
}

$active_menu = 'brosur_settings';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Brosur PSB Digital | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Lengkap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@500;700;900&family=Inter:wght@300;400;600;700&family=Outfit:wght@400;600;800;900&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }
        
        /* Clean Background Simulation Canvas (Portrait 9:16) */
        .bg-simulation-canvas {
            width: 340px;
            max-width: 100%;
            height: min(670px, calc(100vh - 110px));
            min-height: 520px;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.25), 0 0 0 1px rgba(0, 0, 0, 0.05);
            position: relative;
            background-color: #0f172a;
        }

        /* Ambient Background Pattern */
        .ambient-bg {
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
        }

        /* Draggable text container */
        .draggable-box {
            touch-action: none;
            cursor: grab;
            user-select: none;
        }
        .draggable-box:active {
            cursor: grabbing;
        }

        /* Compact Text Row Strip */
        .text-row-item {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .text-row-item:hover {
            border-color: #0b8478;
        }
        .text-row-item.active-layer {
            border-color: #0b8478;
            background-color: #f0fdfa;
            box-shadow: 0 4px 12px -2px rgba(11, 132, 120, 0.12);
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto ambient-bg">
        
        <!-- HEADER TOPBAR -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-xl shadow-xs">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Pengaturan Kolom Tulisan & Background</h1>
                    <p class="text-xs text-slate-500">Duplikasi kolom tulisan (H1-H5/P, font, warna, ukuran) & geser letak posisi di layar HP</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="brosur.php" target="_blank" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 text-teal-950 font-black text-xs shadow-md transition flex items-center gap-2 transform active:scale-95">
                    <i class="fas fa-external-link-alt text-xs"></i>
                    <span>Buka Brosur Publik</span>
                </a>
            </div>
        </header>

        <!-- WORKSPACE AREA: 2 KOLOM (PAPAN PENGATURAN KIRI & SIMULASI KANAN) -->
        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- PANEL KIRI: PENGATURAN TULISAN & BACKGROUND -->
            <div class="lg:col-span-7 xl:col-span-7 space-y-6">

                <!-- NOTIFIKASI SUKSES / ERROR -->
                <?php if (!empty($pesan_sukses)): ?>
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-xs">
                    <i class="fas fa-circle-check text-emerald-600 text-base"></i>
                    <span><?= htmlspecialchars($pesan_sukses) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($pesan_error)): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2.5 shadow-xs">
                    <i class="fas fa-circle-exclamation text-rose-600 text-base"></i>
                    <span><?= htmlspecialchars($pesan_error) ?></span>
                </div>
                <?php endif; ?>

                <!-- KARTU 1: PENGATURAN KOLOM TULISAN DINAMIS MULTI-ROW (BISA DIGANDAKAN / DIDUPLIKASI) -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-5">
                    
                    <!-- Header Kartu Tulisan & Tombol Tambah Kolom -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-lg shadow-2xs">
                                <i class="fas fa-font"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="font-black text-base sm:text-lg text-slate-900">Kolom Tulisan Brosur</h2>
                                    <span id="text-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-black bg-teal-100 text-teal-800">
                                        <?= count($text_items) ?> Kolom
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">Tampilan ringkas 1 baris per kolom. Tekan <strong>Duplikasi</strong> untuk menambah kolom baru.</p>
                            </div>
                        </div>
                        
                        <!-- Tombol Tambah Kolom Tulisan -->
                        <button type="button" onclick="addNewTextRow()" class="px-4 py-2.5 rounded-2xl bg-teal-50 hover:bg-teal-100 text-[#0b8478] border border-teal-200/80 font-black text-xs transition flex items-center gap-2 shrink-0 active:scale-95 cursor-pointer">
                            <i class="fas fa-plus text-xs"></i>
                            <span>Tambah Kolom Tulisan</span>
                        </button>
                    </div>

                    <!-- FORM UTAMA TULISAN DINAMIS -->
                    <form action="" method="POST" id="form-pengaturan-text" class="space-y-4">
                        <input type="hidden" name="action_type" value="save_text">
                        <input type="hidden" name="custom_text_items_json" id="input-text-items-json" value="">

                        <!-- DAFTAR BARIS KOLOM TULISAN (RINGKAS & SIMPEL 1 BARIS PER ITEM) -->
                        <div id="text-rows-container" class="space-y-3">
                            <!-- Diisi secara dinamis oleh Javascript renderRows() -->
                        </div>

                        <!-- PETUNJUK RINGKAS -->
                        <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/70 text-amber-900 text-[11px] flex items-center gap-2.5">
                            <i class="fas fa-arrows-up-down-left-right text-amber-600 text-sm shrink-0"></i>
                            <span><strong>Tips:</strong> Setiap kolom tulisan di atas dapat langsung <strong>diklik dan digeser (drag & drop)</strong> posisinya di layar simulasi HP sebelah kanan.</span>
                        </div>

                        <!-- TOMBOL AKSI BAWAH: TAMBAH & SIMPAN -->
                        <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <button type="button" onclick="addNewTextRow()" class="w-full sm:w-auto px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                                <i class="fas fa-plus text-xs text-teal-600"></i>
                                <span>Tambah Kolom Baru</span>
                            </button>

                            <button type="button" onclick="saveAllTextItems()" class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-gradient-to-r from-[#0b8478] to-[#075f56] hover:from-[#097368] hover:to-[#054a43] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                <i class="fas fa-save text-base"></i>
                                <span>Simpan Semua Kolom Tulisan</span>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- KARTU 2: PENGATURAN BACKGROUND (TUNGGAL) -->
                <form action="" method="POST" enctype="multipart/form-data" id="form-pengaturan-bg" class="space-y-6">
                    <input type="hidden" name="action_type" value="save_bg">

                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-6">
                        
                        <!-- Header Kartu -->
                        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-lg shadow-2xs">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div>
                                    <h2 class="font-black text-base sm:text-lg text-slate-900">Pengaturan Background Brosur</h2>
                                    <p class="text-xs text-slate-500">Sesuaikan foto background format portrait HP via Upload atau Link URL</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-teal-50 text-teal-800 border border-teal-200/60 flex items-center gap-1">
                                <i class="fas fa-mobile-screen-button text-teal-600"></i> Rasio Portrait HP (9:16)
                            </span>
                        </div>

                        <!-- KONTEN PENGATURAN BACKGROUND TUNGGAL -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                            
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center">
                                
                                <!-- Frame Portrait Preview Thumbnail (9:16) -->
                                <div class="sm:col-span-4 flex flex-col items-center">
                                    <div class="w-28 h-48 rounded-2xl border-4 border-slate-800 overflow-hidden shadow-md relative bg-slate-900 bg-cover bg-center transition-all" id="thumb-bg-box" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') ?>');">
                                        <!-- Overlay di Thumbnail -->
                                        <div class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all" id="thumb-bg-overlay" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>;"></div>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center text-white z-10 pointer-events-none">
                                            <span class="text-[8px] font-black uppercase tracking-wider text-amber-300">Live Preview</span>
                                            <span class="text-[7px] opacity-80 mt-0.5 leading-tight">Format 9:16 HP</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-1.5">Tampilan Foto Portrait</span>
                                </div>

                                <!-- Kontrol Input & Upload -->
                                <div class="sm:col-span-8 space-y-3.5">
                                    
                                    <!-- Input URL Gambar -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Link URL Gambar (Pinterest / Unsplash / Web):</label>
                                        <div class="relative">
                                            <input type="text" name="bg_url" id="input-bg-url" value="<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>" oninput="updateLiveBgUrl(this.value)" placeholder="https://images.unsplash.com/... atau link foto web" class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none bg-white">
                                            <i class="fas fa-link absolute left-2.5 top-3 text-slate-400 text-xs"></i>
                                        </div>
                                    </div>

                                    <!-- Upload File Gambar -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Atau Upload File Gambar (JPG, PNG, WEBP):</label>
                                        <label class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:border-teal-500 text-slate-700 font-bold text-xs flex items-center justify-between transition shadow-2xs group">
                                            <span class="flex items-center gap-2 text-slate-600 group-hover:text-teal-700 truncate">
                                                <i class="fas fa-cloud-arrow-up text-teal-600"></i>
                                                <span id="label-bg-file">Pilih file foto dari perangkat...</span>
                                            </span>
                                            <span class="text-[10px] bg-slate-100 group-hover:bg-teal-50 px-2 py-0.5 rounded text-slate-600 group-hover:text-teal-800">Browse</span>
                                            <input type="file" name="bg_file" id="input-bg-file" accept="image/*" class="hidden" onchange="previewBgFile(this)">
                                        </label>
                                    </div>

                                    <!-- Slider Tingkat Kegelapan Lapisan Overlay -->
                                    <div class="pt-2 border-t border-slate-200/60">
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="text-[11px] font-bold text-slate-700">Tingkat Kegelapan / Opasitas Lapis:</label>
                                            <span id="val-bg-opacity" class="text-xs font-black text-teal-800 font-mono"><?= round((float)($cfg['cover_overlay_opacity'] ?? 0.88) * 100) ?>%</span>
                                        </div>
                                        <input type="range" name="bg_overlay_opacity" id="input-bg-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>" oninput="updateLiveBgOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                        <span class="text-[10px] text-slate-400">Rekomendasi 80-90% agar tulisan brosur tetap tajam dan kontras.</span>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- TOMBOL SIMPAN PENGATURAN BACKGROUND -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-[#0b8478] to-[#075f56] hover:from-[#097368] hover:to-[#054a43] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                <i class="fas fa-save text-base"></i>
                                <span>Simpan Background Brosur</span>
                            </button>
                        </div>

                    </div>

                </form>

                <!-- KARTU 3: KOLEKSI BACKGROUND TERSIMPAN -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-5">
                    
                    <!-- Header Koleksi -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-2xs">
                                <i class="fas fa-photo-film"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-black text-base sm:text-lg text-slate-900">Koleksi Background Tersimpan</h3>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900">
                                        <?= count($koleksi_bg) ?> Item
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500">Pilih dari background tersimpan dengan 1 klik atau hapus yang tidak digunakan</p>
                            </div>
                        </div>
                    </div>

                    <!-- Grid Koleksi Background (Format Portrait HP 9:16) -->
                    <?php if (!empty($koleksi_bg)): ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3.5" id="grid-koleksi-bg">
                        <?php foreach ($koleksi_bg as $kb): ?>
                            <div class="item-koleksi-bg group relative rounded-2xl border border-slate-200/80 bg-slate-900 overflow-hidden shadow-sm hover:shadow-md transition-all flex flex-col">
                                
                                <!-- Portrait Preview Container (9:16 Aspect Ratio) -->
                                <div class="w-full aspect-[9/16] bg-cover bg-center relative" style="background-image: url('<?= htmlspecialchars($kb['url']) ?>');">
                                    
                                    <!-- Overlay Gradient -->
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-black/40 group-hover:from-black/90 group-hover:via-black/60 group-hover:to-black/60 transition-all"></div>
                                    
                                    <!-- Badges Atas & Tombol Hapus -->
                                    <div class="absolute top-2 left-2 right-2 flex items-center justify-between z-10">
                                        <span class="px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider bg-teal-400 text-teal-950">
                                            Portrait 9:16
                                        </span>
                                        
                                        <!-- Tombol Hapus dari Koleksi -->
                                        <a href="admin-brosur-settings.php?action=delete_koleksi&id=<?= $kb['id'] ?>" onclick="return confirm('Hapus background ini dari koleksi tersimpan?');" title="Hapus dari koleksi" class="w-6 h-6 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white flex items-center justify-center text-[10px] transition shadow-xs">
                                            <i class="fas fa-trash-can"></i>
                                        </a>
                                    </div>

                                    <!-- Tombol Terapkan Cepat (Hover Overlay Action) -->
                                    <div class="absolute inset-x-2 bottom-2 z-10 flex flex-col gap-1.5 opacity-90 group-hover:opacity-100 transition-opacity">
                                        <button type="button" onclick="terapkanKoleksi('<?= htmlspecialchars($kb['url'], ENT_QUOTES) ?>')" class="w-full py-2 px-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow-sm flex items-center justify-center gap-1.5 active:scale-95 transition cursor-pointer">
                                            <i class="fas fa-check-circle text-xs"></i> Gunakan Background Ini
                                        </button>
                                    </div>

                                </div>

                                <!-- Label Judul / Info -->
                                <div class="p-2.5 bg-slate-900 border-t border-slate-800 text-white">
                                    <p class="text-[10.5px] font-bold truncate text-slate-200" title="<?= htmlspecialchars($kb['judul']) ?>">
                                        <?= htmlspecialchars($kb['judul']) ?>
                                    </p>
                                    <span class="text-[8.5px] text-slate-400 font-mono block mt-0.5">
                                        <?= date('d M Y', strtotime($kb['created_at'])) ?>
                                    </span>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="p-8 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <i class="fas fa-images text-2xl text-slate-400 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Belum Ada Koleksi Background</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">Setiap background yang Anda simpan di atas akan otomatis terkumpul di sini.</p>
                    </div>
                    <?php endif; ?>

                </div>

            </div>

            <!-- PANEL KANAN: LAYAR SIMULASI INTERAKTIF (STICKY DI KANAN LAYAR PC) -->
            <div class="lg:col-span-5 xl:col-span-5 lg:sticky lg:top-6 self-start flex flex-col items-center lg:items-end">
                <div class="w-full max-w-[340px] flex flex-col items-center">
                    
                    <!-- KANVAS SIMULASI BACKGROUND PORTRAIT MURNI DENGAN KOLOM-KOLOM TULISAN DRAGGABLE -->
                    <div class="bg-simulation-canvas relative w-full overflow-hidden transition-all duration-300" id="phone-container">
                        
                        <!-- GAMBAR BACKGROUND PORTRAIT -->
                        <div id="preview-screen-cover" class="absolute inset-0 w-full h-full bg-cover bg-center transition-all duration-300" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') ?>');">
                            
                            <!-- LAPISAN OVERLAY DINAMIS -->
                            <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>;"></div>

                            <!-- CONTAINER SEMUA LAYER TULISAN DI LAYAR SIMULASI -->
                            <div id="sim-text-layers-container" class="absolute inset-0 pointer-events-none">
                                <!-- Diisi secara dinamis oleh JavaScript renderSimLayers() -->
                            </div>

                        </div>

                        <!-- HIDDEN BODY CONTAINER FOR SCRIPT COMPATIBILITY -->
                        <div id="preview-screen-body" class="hidden absolute inset-0 w-full h-full bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>');">
                            <div id="preview-body-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19]" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>;"></div>
                        </div>

                    </div>

                    <!-- Petunjuk Interaktif Drag -->
                    <p class="text-[10px] text-slate-500 mt-2.5 text-center flex items-center gap-1.5">
                        <i class="fas fa-hand-pointer text-amber-500"></i>
                        <span>Klik & geser tulisan manapun pada layar simulasi untuk memindahkan posisinya</span>
                    </p>

                </div>
            </div>

        </div>

    </main>

    <!-- SCRIPT INTERAKTIF PENGATURAN TULISAN MULTI-KOLOM & DRAGGABLE LOGIC -->
    <script>
        // State Array untuk Semua Kolom Tulisan
        let textItems = <?= json_encode($text_items, JSON_UNESCAPED_UNICODE) ?>;
        if (!Array.isArray(textItems) || textItems.length === 0) {
            textItems = [{
                id: 'text_' + Date.now(),
                content: 'Villa Quran Indonesia',
                format: 'h2',
                color: '#ffffff',
                font: 'Plus Jakarta Sans',
                align: 'center',
                size: 24,
                posX: 50,
                posY: 35,
                width: 85
            }];
        }

        // Pilihan Font Tersedia
        const availableFonts = [
            { id: 'Plus Jakarta Sans', name: 'Jakarta (Modern)' },
            { id: 'Amiri',             name: 'Amiri (Arab)' },
            { id: 'Cinzel',            name: 'Cinzel (Royal)' },
            { id: 'Playfair Display',  name: 'Playfair (Elegan)' },
            { id: 'Poppins',           name: 'Poppins (Bold)' },
            { id: 'Inter',             name: 'Inter (Clean)' },
            { id: 'Outfit',            name: 'Outfit (Trendy)' }
        ];

        // Format Helper HTML Generator
        function getFormatHtml(content, format) {
            let text = (content || '').trim();
            if (!text) text = 'Villa Quran Indonesia';

            // Escape HTML dan buat line-break
            const safeText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;").replace(/\n/g, "<br>");
            const fmt = (format || 'h2').toLowerCase();

            switch (fmt) {
                case 'h1':
                    return `<h1 class="font-black leading-tight tracking-tight">${safeText}</h1>`;
                case 'h3':
                    return `<h3 class="font-bold leading-snug">${safeText}</h3>`;
                case 'h4':
                    return `<h4 class="font-bold leading-normal">${safeText}</h4>`;
                case 'h5':
                    return `<h5 class="font-semibold uppercase tracking-wider leading-normal text-xs">${safeText}</h5>`;
                case 'p':
                    return `<p class="font-normal leading-relaxed text-sm">${safeText}</p>`;
                case 'h2':
                default:
                    return `<h2 class="font-extrabold leading-tight">${safeText}</h2>`;
            }
        }

        // Render Seluruh Baris Pengaturan di Papan Kiri (Ringkas & Simpel 1 Baris per Kolom)
        function renderRows() {
            const container = document.getElementById('text-rows-container');
            const badge = document.getElementById('text-count-badge');
            if (badge) badge.innerText = `${textItems.length} Kolom`;
            if (!container) return;

            container.innerHTML = '';

            textItems.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'text-row-item bg-white border border-slate-200/90 hover:border-teal-500 rounded-2xl p-2.5 sm:p-3 shadow-xs space-y-2';
                row.id = `row-item-${item.id}`;

                // Options Font HTML
                let fontOptionsHtml = '';
                availableFonts.forEach(f => {
                    const sel = (item.font === f.id) ? 'selected' : '';
                    fontOptionsHtml += `<option value="${f.id}" ${sel}>${f.name}</option>`;
                });

                row.innerHTML = `
                    <!-- BARIS UTAMA (1 BARIS RINGKAS) -->
                    <div class="flex flex-wrap items-center gap-2">
                        
                        <!-- Nomor Kolom -->
                        <div class="flex items-center justify-center w-7 h-7 rounded-xl bg-teal-50 text-teal-800 font-black text-xs shrink-0 border border-teal-100 shadow-2xs" title="Kolom #${index + 1}">
                            ${index + 1}
                        </div>

                        <!-- Input Teks Utama -->
                        <div class="flex-1 min-w-[150px]">
                            <input type="text" value="${escapeHtml(item.content)}" oninput="updateItemField('${item.id}', 'content', this.value)" placeholder="Ketik isi teks di sini..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold focus:border-[#0b8478] focus:outline-none bg-slate-50/60 focus:bg-white transition">
                        </div>

                        <!-- Format Tag (H1 - H5 / Paragraf) -->
                        <div class="shrink-0">
                            <select onchange="updateItemField('${item.id}', 'format', this.value)" class="px-2 py-1.5 rounded-xl border border-slate-200 text-xs font-black bg-white focus:border-[#0b8478] focus:outline-none cursor-pointer" title="Pilih Format Heading / Paragraf">
                                <option value="h1" ${item.format === 'h1' ? 'selected' : ''}>H1</option>
                                <option value="h2" ${item.format === 'h2' ? 'selected' : ''}>H2</option>
                                <option value="h3" ${item.format === 'h3' ? 'selected' : ''}>H3</option>
                                <option value="h4" ${item.format === 'h4' ? 'selected' : ''}>H4</option>
                                <option value="h5" ${item.format === 'h5' ? 'selected' : ''}>H5</option>
                                <option value="p"  ${item.format === 'p'  ? 'selected' : ''}>P (Paragraf)</option>
                            </select>
                        </div>

                        <!-- Pilihan Jenis Font -->
                        <div class="shrink-0 max-w-[125px]">
                            <select onchange="updateItemField('${item.id}', 'font', this.value)" class="w-full px-2 py-1.5 rounded-xl border border-slate-200 text-[11px] font-semibold bg-white focus:border-[#0b8478] focus:outline-none cursor-pointer truncate" title="Pilih Jenis Font">
                                ${fontOptionsHtml}
                            </select>
                        </div>

                        <!-- Ukuran Font (px) -->
                        <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl px-2 py-1 shrink-0 shadow-2xs" title="Ukuran Font">
                            <input type="number" min="8" max="90" value="${item.size || 24}" oninput="updateItemField('${item.id}', 'size', parseInt(this.value) || 16)" class="w-8 text-xs font-black text-teal-800 text-center focus:outline-none">
                            <span class="text-[10px] text-slate-400 font-mono">px</span>
                        </div>

                        <!-- Pilihan Warna Font -->
                        <div class="shrink-0" title="Pilih Warna Font">
                            <input type="color" value="${item.color || '#ffffff'}" onchange="updateItemField('${item.id}', 'color', this.value)" class="w-7 h-7 rounded-xl border border-slate-200 p-0.5 bg-white cursor-pointer shadow-2xs">
                        </div>

                        <!-- Pilihan Alignment -->
                        <div class="inline-flex rounded-xl border border-slate-200 bg-white p-0.5 shadow-2xs shrink-0" title="Alignment Teks">
                            <button type="button" onclick="updateItemField('${item.id}', 'align', 'left')" class="px-1.5 py-1 rounded-lg text-xs transition ${item.align === 'left' ? 'bg-[#0b8478] text-white' : 'text-slate-600 hover:bg-slate-100'}"><i class="fas fa-align-left text-[11px]"></i></button>
                            <button type="button" onclick="updateItemField('${item.id}', 'align', 'center')" class="px-1.5 py-1 rounded-lg text-xs transition ${item.align === 'center' ? 'bg-[#0b8478] text-white' : 'text-slate-600 hover:bg-slate-100'}"><i class="fas fa-align-center text-[11px]"></i></button>
                            <button type="button" onclick="updateItemField('${item.id}', 'align', 'right')" class="px-1.5 py-1 rounded-lg text-xs transition ${item.align === 'right' ? 'bg-[#0b8478] text-white' : 'text-slate-600 hover:bg-slate-100'}"><i class="fas fa-align-right text-[11px]"></i></button>
                            <button type="button" onclick="updateItemField('${item.id}', 'align', 'justify')" class="px-1.5 py-1 rounded-lg text-xs transition ${item.align === 'justify' ? 'bg-[#0b8478] text-white' : 'text-slate-600 hover:bg-slate-100'}"><i class="fas fa-align-justify text-[11px]"></i></button>
                        </div>

                        <!-- TOMBOL DUPLIKASI (GANDAKAN) & HAPUS -->
                        <div class="flex items-center gap-1 shrink-0 ml-auto sm:ml-0">
                            <!-- Tombol Duplikasi -->
                            <button type="button" onclick="duplicateRow('${item.id}')" title="Duplikasi / Gandakan Kolom Ini" class="px-2.5 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200/80 text-xs font-bold transition flex items-center gap-1 shadow-2xs active:scale-95 cursor-pointer">
                                <i class="fas fa-copy text-amber-600"></i>
                                <span class="hidden sm:inline text-[11px]">Duplikasi</span>
                            </button>

                            <!-- Toggle Slider Posisi Detail -->
                            <button type="button" onclick="toggleDetails('${item.id}')" title="Pengaturan Posisi Slider" class="w-7 h-7 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center text-xs transition shadow-2xs active:scale-95 cursor-pointer">
                                <i class="fas fa-sliders text-[11px]"></i>
                            </button>

                            <!-- Tombol Hapus -->
                            <button type="button" onclick="deleteRow('${item.id}')" title="Hapus Kolom Ini" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 flex items-center justify-center text-xs transition shadow-2xs active:scale-95 cursor-pointer">
                                <i class="fas fa-trash-can text-[11px]"></i>
                            </button>
                        </div>

                    </div>

                    <!-- PANEL DETAIL POSISI & LEBAR (OPSIONAL / EXPANDABLE) -->
                    <div id="details-${item.id}" class="hidden pt-2 mt-2 border-t border-slate-100 bg-slate-50/70 p-3 rounded-xl">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div>
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-600 mb-1">
                                    <span>Posisi Vertikal (Y):</span>
                                    <span id="label-posy-${item.id}" class="font-mono text-amber-800 font-bold">${Math.round(item.posY || 35)}%</span>
                                </div>
                                <input type="range" min="2" max="92" step="0.5" value="${item.posY || 35}" oninput="updateItemPosition('${item.id}', 'posY', this.value)" class="w-full accent-amber-500 cursor-pointer">
                            </div>
                            <div>
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-600 mb-1">
                                    <span>Posisi Horizontal (X):</span>
                                    <span id="label-posx-${item.id}" class="font-mono text-amber-800 font-bold">${Math.round(item.posX || 50)}%</span>
                                </div>
                                <input type="range" min="10" max="90" step="0.5" value="${item.posX || 50}" oninput="updateItemPosition('${item.id}', 'posX', this.value)" class="w-full accent-amber-500 cursor-pointer">
                            </div>
                            <div>
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-600 mb-1">
                                    <span>Lebar Kolom:</span>
                                    <span id="label-width-${item.id}" class="font-mono text-amber-800 font-bold">${item.width || 85}%</span>
                                </div>
                                <input type="range" min="30" max="100" step="1" value="${item.width || 85}" oninput="updateItemPosition('${item.id}', 'width', this.value)" class="w-full accent-amber-500 cursor-pointer">
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(row);
            });

            syncJsonInput();
        }

        // Escape HTML Utility
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        // Toggle Details Slider Posisi
        function toggleDetails(id) {
            const el = document.getElementById(`details-${id}`);
            if (el) el.classList.toggle('hidden');
        }

        // Update Nilai Field Tertentu
        function updateItemField(id, field, value) {
            const item = textItems.find(i => i.id === id);
            if (item) {
                item[field] = value;
                renderSimLayers();
                syncJsonInput();
            }
        }

        // Update Nilai Posisi Slider
        function updateItemPosition(id, field, value) {
            const item = textItems.find(i => i.id === id);
            if (item) {
                item[field] = parseFloat(value);
                const label = document.getElementById(`label-${field.toLowerCase()}-${id}`);
                if (label) {
                    label.innerText = Math.round(item[field]) + '%';
                }
                renderSimLayers();
                syncJsonInput();
            }
        }

        // 1. Tambah Kolom Tulisan Baru
        function addNewTextRow() {
            const newIndex = textItems.length + 1;
            const newPosY = Math.min(85, 20 + ((newIndex - 1) * 14));
            
            const newItem = {
                id: 'text_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                content: 'Teks Kolom ' + newIndex,
                format: newIndex === 1 ? 'h2' : (newIndex === 2 ? 'h3' : 'p'),
                color: '#ffffff',
                font: 'Plus Jakarta Sans',
                align: 'center',
                size: newIndex === 1 ? 24 : 16,
                posX: 50,
                posY: newPosY,
                width: 85
            };

            textItems.push(newItem);
            renderRows();
            renderSimLayers();

            // Scroll baris baru ke pandangan
            setTimeout(() => {
                const el = document.getElementById(`row-item-${newItem.id}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }

        // 2. Duplikasi / Gandakan Kolom Tertentu
        function duplicateRow(sourceId) {
            const source = textItems.find(i => i.id === sourceId);
            if (!source) return;

            const cloned = JSON.parse(JSON.stringify(source));
            cloned.id = 'text_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            cloned.content = source.content ? source.content + ' (Salinan)' : 'Salinan Teks';
            cloned.posY = Math.min(90, (source.posY || 35) + 9);

            // Sisipkan tepat di bawah baris yang digandakan
            const sourceIndex = textItems.findIndex(i => i.id === sourceId);
            if (sourceIndex >= 0) {
                textItems.splice(sourceIndex + 1, 0, cloned);
            } else {
                textItems.push(cloned);
            }

            renderRows();
            renderSimLayers();

            // Highlight baris baru
            setTimeout(() => {
                const el = document.getElementById(`row-item-${cloned.id}`);
                if (el) {
                    el.classList.add('active-layer');
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => el.classList.remove('active-layer'), 1500);
                }
            }, 100);
        }

        // 3. Hapus Kolom Tertentu
        function deleteRow(id) {
            if (textItems.length <= 1) {
                if (confirm('Ini adalah kolom terakhir. Apakah Anda ingin mengosongkan isinya?')) {
                    textItems[0].content = '';
                    renderRows();
                    renderSimLayers();
                }
                return;
            }

            if (confirm('Hapus kolom tulisan ini?')) {
                textItems = textItems.filter(i => i.id !== id);
                renderRows();
                renderSimLayers();
            }
        }

        // Sinkronisasi JSON ke Hidden Input Form
        function syncJsonInput() {
            const inp = document.getElementById('input-text-items-json');
            if (inp) {
                inp.value = JSON.stringify(textItems);
            }
        }

        // Simpan Semua Item Kolom Tulisan
        function saveAllTextItems() {
            syncJsonInput();
            document.getElementById('form-pengaturan-text').submit();
        }

        // Render Semua Layer Kolom Tulisan di Kanvas Simulasi HP (Sebelah Kanan)
        function renderSimLayers() {
            const container = document.getElementById('sim-text-layers-container');
            if (!container) return;

            container.innerHTML = '';

            textItems.forEach((item, index) => {
                const box = document.createElement('div');
                box.id = `sim-box-${item.id}`;
                box.className = 'draggable-box absolute pointer-events-auto transition-shadow group/drag';
                box.setAttribute('data-id', item.id);
                box.style.top = `${item.posY || 35}%`;
                box.style.left = `${item.posX || 50}%`;
                box.style.transform = 'translate(-50%, 0)';
                box.style.width = `${item.width || 85}%`;
                box.style.zIndex = 20 + index;

                box.innerHTML = `
                    <!-- Border indikator saat hover / drag -->
                    <div class="absolute -inset-1.5 border-2 border-dashed border-amber-400/80 rounded-xl pointer-events-none opacity-0 group-hover/drag:opacity-100 transition-opacity flex items-start justify-between p-1">
                        <span class="bg-amber-400 text-teal-950 text-[8px] font-black px-1.5 py-0.5 rounded shadow-xs">
                            #${index + 1}
                        </span>
                        <span class="bg-teal-950/90 text-amber-300 text-[8px] font-bold px-1.5 py-0.5 rounded shadow-xs flex items-center gap-1">
                            <i class="fas fa-up-down-left-right"></i> Geser
                        </span>
                    </div>

                    <!-- Konten Teks Terformat -->
                    <div style="color: ${item.color || '#ffffff'}; font-family: '${item.font || 'Plus Jakarta Sans'}', sans-serif; text-align: ${item.align || 'center'}; font-size: ${item.size || 24}px; word-break: break-word;">
                        ${getFormatHtml(item.content, item.format)}
                    </div>
                `;

                // Event listener drag untuk elemen ini
                initDragForItem(box, item);

                container.appendChild(box);
            });
        }

        // Logika Interaktif Drag & Drop untuk Setiap Box Tulisan di Layar HP
        function initDragForItem(box, item) {
            const container = document.getElementById('preview-screen-cover');
            if (!box || !container) return;

            let isDragging = false;
            let startX, startY;
            let initialLeftPct, initialTopPct;

            function startDrag(e) {
                if (e.type === 'mousedown' && e.button !== 0) return;
                
                isDragging = true;
                const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                const clientY = e.clientY || (e.touches && e.touches[0].clientY);

                startX = clientX;
                startY = clientY;

                initialLeftPct = item.posX || 50;
                initialTopPct  = item.posY || 35;

                box.style.transition = 'none';
                box.style.zIndex = 50; // Bawa ke paling atas saat di-drag

                // Highlight baris terkait di panel kiri
                const rowEl = document.getElementById(`row-item-${item.id}`);
                if (rowEl) rowEl.classList.add('active-layer');

                e.preventDefault();
            }

            function moveDrag(e) {
                if (!isDragging) return;

                const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                const clientY = e.clientY || (e.touches && e.touches[0].clientY);

                const rect = container.getBoundingClientRect();
                const deltaX = clientX - startX;
                const deltaY = clientY - startY;

                const deltaXPct = (deltaX / rect.width) * 100;
                const deltaYPct = (deltaY / rect.height) * 100;

                let newXPct = Math.min(Math.max(initialLeftPct + deltaXPct, 5), 95);
                let newYPct = Math.min(Math.max(initialTopPct + deltaYPct, 2), 92);

                item.posX = Math.round(newXPct * 10) / 10;
                item.posY = Math.round(newYPct * 10) / 10;

                box.style.left = `${item.posX}%`;
                box.style.top  = `${item.posY}%`;

                // Sync label & slider jika sedang terbuka
                const labelX = document.getElementById(`label-posx-${item.id}`);
                const labelY = document.getElementById(`label-posy-${item.id}`);
                if (labelX) labelX.innerText = Math.round(item.posX) + '%';
                if (labelY) labelY.innerText = Math.round(item.posY) + '%';
            }

            function endDrag() {
                if (!isDragging) return;
                isDragging = false;
                box.style.transition = '';
                box.style.zIndex = 20;

                const rowEl = document.getElementById(`row-item-${item.id}`);
                if (rowEl) {
                    setTimeout(() => rowEl.classList.remove('active-layer'), 800);
                }

                syncJsonInput();
            }

            box.addEventListener('mousedown', startDrag);
            window.addEventListener('mousemove', moveDrag);
            window.addEventListener('mouseup', endDrag);

            box.addEventListener('touchstart', startDrag, { passive: false });
            window.addEventListener('touchmove', moveDrag, { passive: false });
            window.addEventListener('touchend', endDrag);
        }

        // Live Background File Preview
        function previewBgFile(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById('label-bg-file');
                if (label) label.innerText = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    const thumbBox = document.getElementById('thumb-bg-box');
                    const coverScreen = document.getElementById('preview-screen-cover');
                    const bodyScreen = document.getElementById('preview-screen-body');

                    if (thumbBox) thumbBox.style.backgroundImage = `url('${dataUrl}')`;
                    if (coverScreen) coverScreen.style.backgroundImage = `url('${dataUrl}')`;
                    if (bodyScreen) bodyScreen.style.backgroundImage = `url('${dataUrl}')`;
                };
                reader.readAsDataURL(file);
            }
        }

        // Live Background URL Input
        function updateLiveBgUrl(url) {
            const trimmed = url.trim();
            const finalUrl = trimmed ? trimmed : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80';
            
            const thumbBox = document.getElementById('thumb-bg-box');
            const coverScreen = document.getElementById('preview-screen-cover');
            const bodyScreen = document.getElementById('preview-screen-body');

            if (thumbBox) thumbBox.style.backgroundImage = `url('${finalUrl}')`;
            if (coverScreen) coverScreen.style.backgroundImage = `url('${finalUrl}')`;
            if (bodyScreen) bodyScreen.style.backgroundImage = `url('${finalUrl}')`;
        }

        // Live Background Opacity Slider
        function updateLiveBgOpacity(val) {
            const pct = Math.round(val * 100);
            const valLabel = document.getElementById('val-bg-opacity');
            if (valLabel) valLabel.innerText = pct + '%';

            const thumbOverlay = document.getElementById('thumb-bg-overlay');
            const coverOverlay = document.getElementById('preview-cover-overlay');
            const bodyOverlay = document.getElementById('preview-body-overlay');

            if (thumbOverlay) thumbOverlay.style.opacity = val;
            if (coverOverlay) coverOverlay.style.opacity = val;
            if (bodyOverlay) bodyOverlay.style.opacity = val;
        }

        // Terapkan Background dari Koleksi
        function terapkanKoleksi(url) {
            const inp = document.getElementById('input-bg-url');
            if (inp) inp.value = url;
            updateLiveBgUrl(url);

            const formBg = document.getElementById('form-pengaturan-bg');
            if (formBg) formBg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Inisialisasi awal saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            renderRows();
            renderSimLayers();
        });
    </script>
</body>
</html>

