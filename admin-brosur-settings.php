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

// Pastikan kolom untuk custom text tersedia di tabel pengaturan_brosur
$columns_to_check = [
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

// Proses Simpan Pengaturan (Background & Tulisan Dinamis)
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $action_type = $_POST['action_type'] ?? 'save_bg';

    if ($action_type === 'save_text') {
        $custom_text_content = $conn->real_escape_string(trim($_POST['custom_text_content'] ?? ''));
        $custom_text_format  = $conn->real_escape_string(trim($_POST['custom_text_format'] ?? 'h2'));
        $custom_text_color   = $conn->real_escape_string(trim($_POST['custom_text_color'] ?? '#ffffff'));
        $custom_text_font    = $conn->real_escape_string(trim($_POST['custom_text_font'] ?? 'Plus Jakarta Sans'));
        $custom_text_align   = $conn->real_escape_string(trim($_POST['custom_text_align'] ?? 'center'));
        $custom_text_size    = (int)($_POST['custom_text_size'] ?? 24);
        $custom_text_pos_x   = (float)($_POST['custom_text_pos_x'] ?? 50.00);
        $custom_text_pos_y   = (float)($_POST['custom_text_pos_y'] ?? 35.00);
        $custom_text_width   = (int)($_POST['custom_text_width'] ?? 85);

        $sql_text = "UPDATE pengaturan_brosur SET 
                        custom_text_content = '$custom_text_content',
                        custom_text_format  = '$custom_text_format',
                        custom_text_color   = '$custom_text_color',
                        custom_text_font    = '$custom_text_font',
                        custom_text_align   = '$custom_text_align',
                        custom_text_size    = $custom_text_size,
                        custom_text_pos_x   = $custom_text_pos_x,
                        custom_text_pos_y   = $custom_text_pos_y,
                        custom_text_width   = $custom_text_width
                     WHERE id = 1";

        if ($conn->query($sql_text)) {
            $pesan_sukses = "Alhamdulillah! Pengaturan dan posisi kolom tulisan berhasil disimpan.";
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

// Helper Render Teks HTML Berdasarkan Format
function renderCustomTextPreview($content, $format = 'h2') {
    $text = htmlspecialchars($content);
    if (empty($text)) $text = 'Villa Quran Indonesia';
    $format = strtolower(trim($format));
    switch ($format) {
        case 'h1':
            return '<h1 class="font-black leading-tight tracking-tight">' . nl2br($text) . '</h1>';
        case 'h3':
            return '<h3 class="font-bold leading-snug">' . nl2br($text) . '</h3>';
        case 'h4':
            return '<h4 class="font-bold leading-normal">' . nl2br($text) . '</h4>';
        case 'h5':
            return '<h5 class="font-semibold uppercase tracking-wider leading-normal text-xs">' . nl2br($text) . '</h5>';
        case 'p':
            return '<p class="font-normal leading-relaxed text-sm">' . nl2br($text) . '</p>';
        case 'h2':
        default:
            return '<h2 class="font-extrabold leading-tight">' . nl2br($text) . '</h2>';
    }
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
                    <i class="fas fa-pen-nib"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Pengaturan Tulisan & Background Brosur</h1>
                    <p class="text-xs text-slate-500">Kustomisasi kolom tulisan (H1-H5, font, warna, ukuran) & drag posisi di atas background</p>
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

                <!-- KARTU 1: PENGATURAN KOLOM TULISAN DINAMIS (6 FITUR LENGKAP) -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-6">
                    
                    <!-- Header Kartu Tulisan -->
                    <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-2xs">
                                <i class="fas fa-font"></i>
                            </div>
                            <div>
                                <h2 class="font-black text-base sm:text-lg text-slate-900">Pengaturan Kolom Tulisan</h2>
                                <p class="text-xs text-slate-500">Format, warna, jenis font, alignment, ukuran & drag letak posisi</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-900 border border-amber-200/60 flex items-center gap-1.5">
                            <i class="fas fa-hand-pointer text-amber-600"></i> Bisa Digeser di Layar
                        </span>
                    </div>

                    <form action="" method="POST" id="form-pengaturan-text" class="space-y-5">
                        <input type="hidden" name="action_type" value="save_text">

                        <!-- INPUT ISI TULISAN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">
                                Isi Tulisan / Teks:
                            </label>
                            <textarea name="custom_text_content" id="input-text-content" rows="3" oninput="updateLiveText(this.value)" placeholder="Tulis teks atau judul yang ingin ditampilkan..." class="w-full p-3.5 rounded-2xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none bg-slate-50/50 hover:bg-white transition leading-relaxed font-medium"><?= htmlspecialchars($cfg['custom_text_content'] ?? 'Villa Quran Indonesia') ?></textarea>
                        </div>

                        <!-- GRID 2 KOLOM KONTROL UTAMA (FITUR 1 S/D FITUR 5) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            
                            <!-- FITUR 1: PILIHAN FORMAT (H1, H2, H3, H4, H5, Paragraf) -->
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                                <label class="block text-[11px] font-bold text-slate-700 flex items-center justify-between">
                                    <span>1. Format Teks:</span>
                                    <span id="label-format-badge" class="font-mono text-[10px] text-teal-800 font-bold uppercase"><?= htmlspecialchars($cfg['custom_text_format'] ?? 'h2') ?></span>
                                </label>
                                <div class="grid grid-cols-3 gap-1.5" id="group-format-buttons">
                                    <?php 
                                    $formats = [
                                        'h1' => 'H1',
                                        'h2' => 'H2',
                                        'h3' => 'H3',
                                        'h4' => 'H4',
                                        'h5' => 'H5',
                                        'p'  => 'Paragraf'
                                    ];
                                    $current_format = strtolower($cfg['custom_text_format'] ?? 'h2');
                                    foreach ($formats as $fmt_val => $fmt_label): 
                                        $is_active = ($current_format === $fmt_val);
                                    ?>
                                        <button type="button" onclick="setFormatOption('<?= $fmt_val ?>')" class="btn-fmt-opt py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center <?= $is_active ? 'bg-[#0b8478] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100' ?>" data-format="<?= $fmt_val ?>">
                                            <?= $fmt_label ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="custom_text_format" id="input-text-format" value="<?= htmlspecialchars($cfg['custom_text_format'] ?? 'h2') ?>">
                            </div>

                            <!-- FITUR 2: PILIHAN WARNA FONT -->
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                                <label class="block text-[11px] font-bold text-slate-700">2. Pilihan Warna Font:</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" id="input-text-color-picker" value="<?= htmlspecialchars($cfg['custom_text_color'] ?? '#ffffff') ?>" onchange="setColorOption(this.value)" class="w-10 h-10 rounded-xl cursor-pointer border border-slate-200 p-0.5 bg-white shadow-2xs">
                                    <input type="text" name="custom_text_color" id="input-text-color-hex" value="<?= htmlspecialchars($cfg['custom_text_color'] ?? '#ffffff') ?>" oninput="setColorOption(this.value)" class="flex-1 px-3 py-2 rounded-xl border border-slate-200 text-xs font-mono font-bold uppercase bg-white">
                                </div>
                                <!-- Preset Warna Cepat -->
                                <div class="flex items-center gap-1.5 pt-1">
                                    <?php 
                                    $color_presets = ['#ffffff', '#fbbf24', '#10b981', '#38bdf8', '#fb7185', '#0f172a'];
                                    foreach ($color_presets as $cp): 
                                    ?>
                                        <button type="button" onclick="setColorOption('<?= $cp ?>')" class="w-6 h-6 rounded-full border border-slate-300 shadow-2xs transition transform hover:scale-110" style="background-color: <?= $cp ?>;" title="<?= $cp ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- FITUR 3: PILIHAN JENIS FONT -->
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                                <label class="block text-[11px] font-bold text-slate-700">3. Pilihan Jenis Font:</label>
                                <select name="custom_text_font" id="input-text-font" onchange="setFontOption(this.value)" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold focus:border-[#0b8478] focus:outline-none bg-white">
                                    <?php 
                                    $font_options = [
                                        'Plus Jakarta Sans' => 'Plus Jakarta Sans (Modern Clean)',
                                        'Amiri'             => 'Amiri (Arab & Kaligrafi Islami)',
                                        'Cinzel'            => 'Cinzel (Royal & Klasik Mewah)',
                                        'Playfair Display'  => 'Playfair Display (Elegan Editorial)',
                                        'Poppins'           => 'Poppins (Rounded Modern)',
                                        'Inter'             => 'Inter (Sleek Interface)',
                                        'Outfit'            => 'Outfit (Trendy & Bold)'
                                    ];
                                    $current_font = $cfg['custom_text_font'] ?? 'Plus Jakarta Sans';
                                    foreach ($font_options as $f_val => $f_name): 
                                    ?>
                                        <option value="<?= $f_val ?>" <?= ($current_font === $f_val) ? 'selected' : '' ?> style="font-family: '<?= $f_val ?>', sans-serif;">
                                            <?= $f_name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- FITUR 4: PILIHAN ALIGNMENT -->
                            <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                                <label class="block text-[11px] font-bold text-slate-700 flex items-center justify-between">
                                    <span>4. Pilihan Alignment:</span>
                                    <span id="label-align-badge" class="font-mono text-[10px] text-teal-800 font-bold uppercase"><?= htmlspecialchars($cfg['custom_text_align'] ?? 'center') ?></span>
                                </label>
                                <div class="grid grid-cols-4 gap-1.5" id="group-align-buttons">
                                    <?php 
                                    $alignments = [
                                        'left'    => 'fa-align-left',
                                        'center'  => 'fa-align-center',
                                        'right'   => 'fa-align-right',
                                        'justify' => 'fa-align-justify'
                                    ];
                                    $current_align = strtolower($cfg['custom_text_align'] ?? 'center');
                                    foreach ($alignments as $alg_val => $alg_icon): 
                                        $is_act = ($current_align === $alg_val);
                                    ?>
                                        <button type="button" onclick="setAlignOption('<?= $alg_val ?>')" class="btn-alg-opt py-2 px-2 rounded-xl text-xs transition flex items-center justify-center <?= $is_act ? 'bg-[#0b8478] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100' ?>" data-align="<?= $alg_val ?>" title="Align <?= ucfirst($alg_val) ?>">
                                            <i class="fas <?= $alg_icon ?>"></i>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="custom_text_align" id="input-text-align" value="<?= htmlspecialchars($cfg['custom_text_align'] ?? 'center') ?>">
                            </div>

                        </div>

                        <!-- FITUR 5: PILIHAN UKURAN FONT -->
                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-2">
                            <div class="flex justify-between items-center">
                                <label class="text-[11px] font-bold text-slate-700">5. Pilihan Ukuran Font:</label>
                                <span id="val-text-size" class="text-xs font-black text-teal-800 font-mono"><?= (int)($cfg['custom_text_size'] ?? 24) ?>px</span>
                            </div>
                            <input type="range" name="custom_text_size" id="input-text-size" min="10" max="56" step="1" value="<?= (int)($cfg['custom_text_size'] ?? 24) ?>" oninput="setSizeOption(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                            <div class="flex justify-between text-[9px] text-slate-400">
                                <span>10px (Kecil)</span>
                                <span>24px (Sedang)</span>
                                <span>56px (Besar)</span>
                            </div>
                        </div>

                        <!-- FITUR 6: PENGATURAN POSISI (GESER-GESER LETAK TULISAN) -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-amber-50/60 border border-amber-200/80 space-y-3.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-amber-400 text-teal-950 flex items-center justify-center text-xs font-black">
                                        <i class="fas fa-arrows-up-down-left-right"></i>
                                    </span>
                                    <label class="text-xs font-black text-amber-950">6. Pengaturan Posisi (Bisa Digeser Langsung):</label>
                                </div>
                                <button type="button" onclick="resetPosisiTengah()" class="text-[10px] text-amber-900 underline font-bold hover:text-amber-700">
                                    Reset Tengah
                                </button>
                            </div>
                            
                            <p class="text-[11px] text-amber-900/80 leading-relaxed">
                                <i class="fas fa-circle-info text-amber-600 mr-1"></i> Anda bisa <strong>klik & drag/geser langsung</strong> kotak tulisan pada layar preview di sebelah kanan, atau atur slider posisi di bawah ini:
                            </p>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-1">
                                <!-- Posisi Vertikal (Top Y) -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[10.5px] font-bold text-slate-700">Posisi Vertikal (Y):</span>
                                        <span id="val-pos-y" class="text-xs font-mono font-black text-amber-900"><?= round((float)($cfg['custom_text_pos_y'] ?? 35)) ?>%</span>
                                    </div>
                                    <input type="range" name="custom_text_pos_y" id="input-pos-y" min="2" max="92" step="0.5" value="<?= (float)($cfg['custom_text_pos_y'] ?? 35) ?>" oninput="setPosYOption(this.value)" class="w-full accent-amber-500 cursor-pointer">
                                </div>

                                <!-- Posisi Horizontal (Left X) -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[10.5px] font-bold text-slate-700">Posisi Horizontal (X):</span>
                                        <span id="val-pos-x" class="text-xs font-mono font-black text-amber-900"><?= round((float)($cfg['custom_text_pos_x'] ?? 50)) ?>%</span>
                                    </div>
                                    <input type="range" name="custom_text_pos_x" id="input-pos-x" min="10" max="90" step="0.5" value="<?= (float)($cfg['custom_text_pos_x'] ?? 50) ?>" oninput="setPosXOption(this.value)" class="w-full accent-amber-500 cursor-pointer">
                                </div>

                                <!-- Lebar Kolom (Width %) -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-[10.5px] font-bold text-slate-700">Lebar Kolom:</span>
                                        <span id="val-pos-w" class="text-xs font-mono font-black text-amber-900"><?= (int)($cfg['custom_text_width'] ?? 85) ?>%</span>
                                    </div>
                                    <input type="range" name="custom_text_width" id="input-pos-w" min="30" max="100" step="1" value="<?= (int)($cfg['custom_text_width'] ?? 85) ?>" oninput="setWidthOption(this.value)" class="w-full accent-amber-500 cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL SIMPAN PENGATURAN TULISAN -->
                        <div class="pt-2 flex items-center justify-end">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-[#0b8478] to-[#075f56] hover:from-[#097368] hover:to-[#054a43] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                <i class="fas fa-save text-base"></i>
                                <span>Simpan Pengaturan Tulisan</span>
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
                    
                    <!-- KANVAS SIMULASI BACKGROUND PORTRAIT MURNI DENGAN KOLOM TULISAN DRAGGABLE -->
                    <div class="bg-simulation-canvas relative w-full overflow-hidden transition-all duration-300" id="phone-container">
                        
                        <!-- GAMBAR BACKGROUND PORTRAIT -->
                        <div id="preview-screen-cover" class="absolute inset-0 w-full h-full bg-cover bg-center transition-all duration-300" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') ?>');">
                            
                            <!-- LAPISAN OVERLAY DINAMIS -->
                            <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>;"></div>

                            <!-- KOLOM TULISAN DRAGGABLE INTERAKTIF (BISA DIGESER-GESER) -->
                            <div id="sim-text-box" class="draggable-box absolute transition-shadow group/drag" style="top: <?= (float)($cfg['custom_text_pos_y'] ?? 35) ?>%; left: <?= (float)($cfg['custom_text_pos_x'] ?? 50) ?>%; transform: translate(-50%, 0); width: <?= (int)($cfg['custom_text_width'] ?? 85) ?>%; z-index: 25;">
                                
                                <!-- Bounding indicator saat hover / dragging -->
                                <div class="absolute -inset-2 border-2 border-dashed border-amber-400/80 rounded-xl pointer-events-none opacity-0 group-hover/drag:opacity-100 transition-opacity flex items-start justify-end p-1">
                                    <span class="bg-amber-400 text-teal-950 text-[8px] font-black px-1.5 py-0.5 rounded shadow-xs flex items-center gap-1">
                                        <i class="fas fa-up-down-left-right"></i> Geser
                                    </span>
                                </div>

                                <!-- Konten Teks Sesuai Format (H1-H5 / Paragraf) -->
                                <div id="sim-text-wrapper" style="color: <?= htmlspecialchars($cfg['custom_text_color'] ?? '#ffffff') ?>; font-family: '<?= htmlspecialchars($cfg['custom_text_font'] ?? 'Plus Jakarta Sans') ?>', sans-serif; text-align: <?= htmlspecialchars($cfg['custom_text_align'] ?? 'center') ?>; font-size: <?= (int)($cfg['custom_text_size'] ?? 24) ?>px;">
                                    <?= renderCustomTextPreview($cfg['custom_text_content'] ?? 'Villa Quran Indonesia', $cfg['custom_text_format'] ?? 'h2') ?>
                                </div>

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
                        <span>Klik & geser tulisan di atas untuk memindahkan posisinya</span>
                    </p>

                </div>
            </div>

        </div>

    </main>

    <!-- SCRIPT INTERAKTIF PENGATURAN TULISAN & BACKGROUND & DRAGGABLE LOGIC -->
    <script>
        // State Konfigurasi Teks
        let currentTextState = {
            content: <?= json_encode($cfg['custom_text_content'] ?? 'Villa Quran Indonesia') ?>,
            format: <?= json_encode($cfg['custom_text_format'] ?? 'h2') ?>,
            color: <?= json_encode($cfg['custom_text_color'] ?? '#ffffff') ?>,
            font: <?= json_encode($cfg['custom_text_font'] ?? 'Plus Jakarta Sans') ?>,
            align: <?= json_encode($cfg['custom_text_align'] ?? 'center') ?>,
            size: <?= (int)($cfg['custom_text_size'] ?? 24) ?>,
            posX: <?= (float)($cfg['custom_text_pos_x'] ?? 50.00) ?>,
            posY: <?= (float)($cfg['custom_text_pos_y'] ?? 35.00) ?>,
            width: <?= (int)($cfg['custom_text_width'] ?? 85) ?>
        };

        // Render Ulang Konten Teks di Kanvas Simulasi
        function renderSimText() {
            const wrapper = document.getElementById('sim-text-wrapper');
            const box = document.getElementById('sim-text-box');
            if (!wrapper || !box) return;

            let text = currentTextState.content.trim();
            if (!text) text = 'Villa Quran Indonesia';

            // Escape HTML dan buat line-break
            const safeText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;").replace(/\n/g, "<br>");

            let tagHtml = '';
            const fmt = currentTextState.format.toLowerCase();
            switch (fmt) {
                case 'h1':
                    tagHtml = `<h1 class="font-black leading-tight tracking-tight">${safeText}</h1>`;
                    break;
                case 'h3':
                    tagHtml = `<h3 class="font-bold leading-snug">${safeText}</h3>`;
                    break;
                case 'h4':
                    tagHtml = `<h4 class="font-bold leading-normal">${safeText}</h4>`;
                    break;
                case 'h5':
                    tagHtml = `<h5 class="font-semibold uppercase tracking-wider leading-normal text-xs">${safeText}</h5>`;
                    break;
                case 'p':
                    tagHtml = `<p class="font-normal leading-relaxed text-sm">${safeText}</p>`;
                    break;
                case 'h2':
                default:
                    tagHtml = `<h2 class="font-extrabold leading-tight">${safeText}</h2>`;
                    break;
            }

            wrapper.innerHTML = tagHtml;
            wrapper.style.color = currentTextState.color;
            wrapper.style.fontFamily = `'${currentTextState.font}', sans-serif`;
            wrapper.style.textAlign = currentTextState.align;
            wrapper.style.fontSize = `${currentTextState.size}px`;

            box.style.left = `${currentTextState.posX}%`;
            box.style.top = `${currentTextState.posY}%`;
            box.style.width = `${currentTextState.width}%`;
        }

        // 1. Live Text Input
        function updateLiveText(val) {
            currentTextState.content = val;
            renderSimText();
        }

        // 2. Set Format Option (H1 - H5, p)
        function setFormatOption(fmt) {
            currentTextState.format = fmt;
            document.getElementById('input-text-format').value = fmt;
            document.getElementById('label-format-badge').innerText = fmt.toUpperCase();

            document.querySelectorAll('.btn-fmt-opt').forEach(btn => {
                if (btn.getAttribute('data-format') === fmt) {
                    btn.className = 'btn-fmt-opt py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center bg-[#0b8478] text-white shadow-xs';
                } else {
                    btn.className = 'btn-fmt-opt py-2 px-2 rounded-xl text-xs font-bold transition flex items-center justify-center bg-white border border-slate-200 text-slate-700 hover:bg-slate-100';
                }
            });

            renderSimText();
        }

        // 3. Set Color Option
        function setColorOption(color) {
            currentTextState.color = color;
            document.getElementById('input-text-color-picker').value = color;
            document.getElementById('input-text-color-hex').value = color;
            renderSimText();
        }

        // 4. Set Font Option
        function setFontOption(font) {
            currentTextState.font = font;
            document.getElementById('input-text-font').value = font;
            renderSimText();
        }

        // 5. Set Alignment Option
        function setAlignOption(align) {
            currentTextState.align = align;
            document.getElementById('input-text-align').value = align;
            document.getElementById('label-align-badge').innerText = align.toUpperCase();

            document.querySelectorAll('.btn-alg-opt').forEach(btn => {
                if (btn.getAttribute('data-align') === align) {
                    btn.className = 'btn-alg-opt py-2 px-2 rounded-xl text-xs transition flex items-center justify-center bg-[#0b8478] text-white shadow-xs';
                } else {
                    btn.className = 'btn-alg-opt py-2 px-2 rounded-xl text-xs transition flex items-center justify-center bg-white border border-slate-200 text-slate-700 hover:bg-slate-100';
                }
            });

            renderSimText();
        }

        // 6. Set Font Size Option
        function setSizeOption(size) {
            currentTextState.size = parseInt(size);
            document.getElementById('val-text-size').innerText = size + 'px';
            renderSimText();
        }

        // 7. Set Posisi Vertikal Y
        function setPosYOption(val) {
            currentTextState.posY = parseFloat(val);
            document.getElementById('val-pos-y').innerText = Math.round(val) + '%';
            renderSimText();
        }

        // 8. Set Posisi Horizontal X
        function setPosXOption(val) {
            currentTextState.posX = parseFloat(val);
            document.getElementById('val-pos-x').innerText = Math.round(val) + '%';
            renderSimText();
        }

        // 9. Set Lebar Kolom
        function setWidthOption(val) {
            currentTextState.width = parseInt(val);
            document.getElementById('val-pos-w').innerText = val + '%';
            renderSimText();
        }

        // 10. Reset Posisi Tengah
        function resetPosisiTengah() {
            setPosXOption(50);
            setPosYOption(35);
            document.getElementById('input-pos-x').value = 50;
            document.getElementById('input-pos-y').value = 35;
        }

        // 11. Interactive Drag-and-Drop Logic di Atas Layar Simulasi
        (function initDraggableTextBox() {
            const box = document.getElementById('sim-text-box');
            const container = document.getElementById('preview-screen-cover');
            if (!box || !container) return;

            let isDragging = false;
            let startX, startY;
            let initialLeftPct, initialTopPct;

            function startDrag(e) {
                // Hanya tangani klik mouse kiri atau single touch
                if (e.type === 'mousedown' && e.button !== 0) return;
                
                isDragging = true;
                const clientX = e.clientX || (e.touches && e.touches[0].clientX);
                const clientY = e.clientY || (e.touches && e.touches[0].clientY);

                startX = clientX;
                startY = clientY;

                initialLeftPct = currentTextState.posX;
                initialTopPct  = currentTextState.posY;

                box.style.transition = 'none';
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

                currentTextState.posX = Math.round(newXPct * 10) / 10;
                currentTextState.posY = Math.round(newYPct * 10) / 10;

                // Sync ke input slider dan label
                const sliderX = document.getElementById('input-pos-x');
                const sliderY = document.getElementById('input-pos-y');
                const labelX = document.getElementById('val-pos-x');
                const labelY = document.getElementById('val-pos-y');

                if (sliderX) sliderX.value = currentTextState.posX;
                if (sliderY) sliderY.value = currentTextState.posY;
                if (labelX) labelX.innerText = Math.round(currentTextState.posX) + '%';
                if (labelY) labelY.innerText = Math.round(currentTextState.posY) + '%';

                box.style.left = `${currentTextState.posX}%`;
                box.style.top  = `${currentTextState.posY}%`;
            }

            function endDrag() {
                if (!isDragging) return;
                isDragging = false;
                box.style.transition = '';
            }

            box.addEventListener('mousedown', startDrag);
            window.addEventListener('mousemove', moveDrag);
            window.addEventListener('mouseup', endDrag);

            box.addEventListener('touchstart', startDrag, { passive: false });
            window.addEventListener('touchmove', moveDrag, { passive: false });
            window.addEventListener('touchend', endDrag);
        })();

        // 12. Live Background File Preview (Menyeluruh)
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

        // 13. Live Background URL Input (Menyeluruh)
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

        // 14. Live Background Opacity Slider (Menyeluruh)
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

        // 15. Terapkan Background dari Koleksi ke Form & Layar Simulasi
        function terapkanKoleksi(url) {
            const inp = document.getElementById('input-bg-url');
            if (inp) inp.value = url;
            updateLiveBgUrl(url);

            const formBg = document.getElementById('form-pengaturan-bg');
            if (formBg) formBg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    </script>
</body>
</html>

