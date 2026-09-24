<?php
// admin-brosur-settings.php
// Panel Pengaturan Brosur PSB Digital, Background Custom (URL & Upload), Drag Posisi Elemen, & Tema Warna
// Villa Quran Indonesia

require_once 'auth.php';
require_once 'koneksi.php';

// Inisialisasi self-healing tabel pengaturan_brosur
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Self-healing migration jika kolom baru belum ada di database lama
$cols_needed = [
    "body_bg_url"          => "ALTER TABLE pengaturan_brosur ADD COLUMN body_bg_url TEXT AFTER cover_overlay_opacity",
    "body_overlay_opacity" => "ALTER TABLE pengaturan_brosur ADD COLUMN body_overlay_opacity DECIMAL(3,2) DEFAULT 0.92 AFTER body_bg_url",
    "countdown_mode"       => "ALTER TABLE pengaturan_brosur ADD COLUMN countdown_mode VARCHAR(20) DEFAULT 'auto' AFTER diskon_gelombang",
    "countdown_target"     => "ALTER TABLE pengaturan_brosur ADD COLUMN countdown_target DATETIME DEFAULT '2026-12-31 23:59:59' AFTER countdown_mode",
    "countdown_title"      => "ALTER TABLE pengaturan_brosur ADD COLUMN countdown_title VARCHAR(150) DEFAULT '⏳ Sisa Waktu Pendaftaran Berakhir:' AFTER countdown_target",
    "show_countdown"       => "ALTER TABLE pengaturan_brosur ADD COLUMN show_countdown TINYINT(1) DEFAULT 1 AFTER countdown_title",
    "cover_elements_pos"   => "ALTER TABLE pengaturan_brosur ADD COLUMN cover_elements_pos TEXT AFTER show_countdown",
    "theme_color_mode"     => "ALTER TABLE pengaturan_brosur ADD COLUMN theme_color_mode VARCHAR(50) DEFAULT 'emerald_gold' AFTER cover_elements_pos",
    "text_color"           => "ALTER TABLE pengaturan_brosur ADD COLUMN text_color VARCHAR(30) DEFAULT '#ffffff' AFTER theme_color_mode",
    "accent_color"         => "ALTER TABLE pengaturan_brosur ADD COLUMN accent_color VARCHAR(30) DEFAULT '#fbbf24' AFTER text_color",
    "btn_bg_color"         => "ALTER TABLE pengaturan_brosur ADD COLUMN btn_bg_color VARCHAR(100) DEFAULT '#d97706' AFTER accent_color",
    "btn_text_color"       => "ALTER TABLE pengaturan_brosur ADD COLUMN btn_text_color VARCHAR(30) DEFAULT '#022d27' AFTER btn_bg_color",
    "card_bg_style"        => "ALTER TABLE pengaturan_brosur ADD COLUMN card_bg_style VARCHAR(30) DEFAULT 'glass_dark' AFTER btn_text_color"
];
$res_c = $conn->query("DESCRIBE pengaturan_brosur");
$curr_cols = [];
if ($res_c) {
    while ($r = $res_c->fetch_assoc()) $curr_cols[] = $r['Field'];
}
foreach ($cols_needed as $col => $sql) {
    if (!in_array($col, $curr_cols)) {
        $conn->query($sql);
    }
}

// Pastikan baris id=1 ada
$conn->query("INSERT IGNORE INTO pengaturan_brosur (id, tahun_ajaran, periode_gelombang, kuota_santri, cover_bg_url, cover_overlay_opacity, body_bg_url, body_overlay_opacity, theme_preset, music_url, biaya_pendaftaran, biaya_pangkal, biaya_tahunan, biaya_spp, diskon_gelombang, countdown_mode, countdown_target, countdown_title, show_countdown, cover_elements_pos, theme_color_mode, text_color, accent_color, btn_bg_color, btn_text_color, card_bg_style)
VALUES (1, '2026/2027', 'Gelombang 1 — Kuota Terbatas', 20, 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80', 0.85, 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1200&auto=format&fit=crop&q=80', 0.92, 'madinah', 'upload/backsound.mp3', 350000, 12500000, 2500000, 1650000, 2000000, 'auto', '2026-12-31 23:59:59', '⏳ Sisa Waktu Pendaftaran Berakhir:', 1, '{\"header_y\":12,\"guest_y\":45,\"btn_y\":82}', 'emerald_gold', '#ffffff', '#fbbf24', '#d97706', '#022d27', 'glass_dark')");

$pesan_sukses = '';
$pesan_error  = '';

// Proses Simpan Pengaturan
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $tahun_ajaran         = $conn->real_escape_string($_POST['tahun_ajaran'] ?? '2026/2027');
    $periode_gelombang    = $conn->real_escape_string($_POST['periode_gelombang'] ?? 'Gelombang 1');
    $kuota_santri         = (int)($_POST['kuota_santri'] ?? 20);
    $cover_bg_url         = $conn->real_escape_string(trim($_POST['cover_bg_url'] ?? ''));
    $cover_opacity        = (float)($_POST['cover_overlay_opacity'] ?? 0.85);
    $body_bg_url          = $conn->real_escape_string(trim($_POST['body_bg_url'] ?? ''));
    $body_overlay_opacity = (float)($_POST['body_overlay_opacity'] ?? 0.92);
    $theme_preset         = $conn->real_escape_string($_POST['theme_preset'] ?? 'madinah');
    $music_url            = $conn->real_escape_string(trim($_POST['music_url'] ?? 'upload/backsound.mp3'));
    $biaya_pendaftaran    = (int)str_replace(['.', ','], '', $_POST['biaya_pendaftaran'] ?? 350000);
    $biaya_pangkal        = (int)str_replace(['.', ','], '', $_POST['biaya_pangkal'] ?? 12500000);
    $biaya_tahunan        = (int)str_replace(['.', ','], '', $_POST['biaya_tahunan'] ?? 2500000);
    $biaya_spp            = (int)str_replace(['.', ','], '', $_POST['biaya_spp'] ?? 1650000);
    $diskon_gelombang     = (int)str_replace(['.', ','], '', $_POST['diskon_gelombang'] ?? 2000000);
    
    $countdown_mode       = $conn->real_escape_string($_POST['countdown_mode'] ?? 'auto');
    $countdown_target_raw = $_POST['countdown_target'] ?? '2026-12-31 23:59:59';
    $countdown_target     = $conn->real_escape_string(str_replace('T', ' ', $countdown_target_raw));
    if (strlen($countdown_target) == 16) $countdown_target .= ':00';
    $countdown_title      = $conn->real_escape_string($_POST['countdown_title'] ?? '⏳ Sisa Waktu Pendaftaran Berakhir:');
    $show_countdown       = isset($_POST['show_countdown']) ? 1 : 0;

    // 1. Handle Upload File Background Cover jika ada
    if (!empty($_FILES['cover_bg_file']['name']) && $_FILES['cover_bg_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_bg_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $new_cover = 'upload/bg_cover_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_bg_file']['tmp_name'], $new_cover)) {
                $cover_bg_url = $new_cover;
            }
        }
    }

    // 1. Handle Upload File Background Halaman Dalam jika ada
    if (!empty($_FILES['body_bg_file']['name']) && $_FILES['body_bg_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['body_bg_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $new_body = 'upload/bg_body_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['body_bg_file']['tmp_name'], $new_body)) {
                $body_bg_url = $new_body;
            }
        }
    }

    // 2. Handle Posisi Geser Elemen Cover
    $header_y = (int)($_POST['pos_header_y'] ?? 12);
    $guest_y  = (int)($_POST['pos_guest_y'] ?? 45);
    $btn_y    = (int)($_POST['pos_btn_y'] ?? 82);
    $cover_elements_pos = json_encode([
        'header_y' => $header_y,
        'guest_y'  => $guest_y,
        'btn_y'    => $btn_y
    ]);

    // 4. Handle Tema Warna & Sinkronisasi
    $theme_color_mode = $conn->real_escape_string($_POST['theme_color_mode'] ?? 'emerald_gold');
    $text_color       = $conn->real_escape_string($_POST['text_color'] ?? '#ffffff');
    $accent_color     = $conn->real_escape_string($_POST['accent_color'] ?? '#fbbf24');
    $btn_bg_color     = $conn->real_escape_string($_POST['btn_bg_color'] ?? '#d97706');
    $btn_text_color   = $conn->real_escape_string($_POST['btn_text_color'] ?? '#022d27');
    $card_bg_style    = $conn->real_escape_string($_POST['card_bg_style'] ?? 'glass_dark');

    $sql_update = "UPDATE pengaturan_brosur SET 
                    tahun_ajaran = '$tahun_ajaran',
                    periode_gelombang = '$periode_gelombang',
                    kuota_santri = $kuota_santri,
                    cover_bg_url = '$cover_bg_url',
                    cover_overlay_opacity = $cover_opacity,
                    body_bg_url = '$body_bg_url',
                    body_overlay_opacity = $body_overlay_opacity,
                    theme_preset = '$theme_preset',
                    music_url = '$music_url',
                    biaya_pendaftaran = $biaya_pendaftaran,
                    biaya_pangkal = $biaya_pangkal,
                    biaya_tahunan = $biaya_tahunan,
                    biaya_spp = $biaya_spp,
                    diskon_gelombang = $diskon_gelombang,
                    countdown_mode = '$countdown_mode',
                    countdown_target = '$countdown_target',
                    countdown_title = '$countdown_title',
                    show_countdown = $show_countdown,
                    cover_elements_pos = '$cover_elements_pos',
                    theme_color_mode = '$theme_color_mode',
                    text_color = '$text_color',
                    accent_color = '$accent_color',
                    btn_bg_color = '$btn_bg_color',
                    btn_text_color = '$btn_text_color',
                    card_bg_style = '$card_bg_style'
                   WHERE id = 1";

    if ($conn->query($sql_update)) {
        $pesan_sukses = "Alhamdulillah! Pengaturan Brosur PSB, Background Custom, Posisi Elemen & Warna berhasil disimpan.";
    } else {
        $pesan_error = "Gagal menyimpan: " . $conn->error;
    }
}

// Ambil Data Terkini
$q = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg = $q ? $q->fetch_assoc() : [];

// Decode posisi elemen cover
$pos = !empty($cfg['cover_elements_pos']) ? json_decode($cfg['cover_elements_pos'], true) : [];
$header_y = $pos['header_y'] ?? 12;
$guest_y  = $pos['guest_y'] ?? 45;
$btn_y    = $pos['btn_y'] ?? 82;

$active_menu = 'brosur_settings';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Brosur PSB Digital & Customizer | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Amiri:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .font-arabic { font-family: 'Amiri', serif; }
        
        /* Phone Mockup Frame */
        .phone-mockup {
            width: 320px;
            height: 640px;
            border: 12px solid #1e293b;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        .phone-speaker {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 5px;
            background: #334155;
            border-radius: 10px;
            z-index: 50;
        }

        /* Garis Bantuan & Grid Canvas Alignment */
        .canvas-grid-bg {
            background-size: 20px 20px;
            background-image: 
                linear-gradient(to right, rgba(245, 158, 11, 0.15) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(245, 158, 11, 0.15) 1px, transparent 1px);
        }
        .center-guide-line {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 1px;
            border-left: 1px dashed rgba(245, 158, 11, 0.85);
            pointer-events: none;
            z-index: 40;
        }
        .active-horizontal-guide {
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            border-top: 1px dashed #38bdf8;
            pointer-events: none;
            z-index: 40;
        }

        /* Draggable Element Box Hover */
        .drag-box {
            cursor: grab;
            transition: outline 0.15s ease, transform 0.05s ease;
            position: absolute;
            left: 12px;
            right: 12px;
            user-select: none;
        }
        .drag-box:hover {
            outline: 2px dashed #f59e0b;
            outline-offset: 4px;
        }
        .drag-box.dragging {
            cursor: grabbing;
            outline: 2px solid #38bdf8;
            outline-offset: 4px;
            z-index: 45 !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }
    </style>
</head>
<body class="flex min-h-screen bg-slate-50 text-slate-800">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto overflow-y-auto">
        
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fas fa-wand-magic-sparkles mr-1"></i> Brosur PSB Digital Customizer
                    </span>
                    <span class="text-xs text-slate-400">Visual Layout Drag & Color Harmonizer</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">Pengaturan Brosur & Background Gambar</h1>
                <p class="text-xs sm:text-sm text-slate-500">Upload background custom atau tempel URL Pinterest, atur letak tulisan/tombol dengan drag posisi & garis bantu, serta sinkronkan warna agar jelas terbaca.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="brosur.php" target="_blank" class="bg-[#0b8478] hover:bg-[#086a60] text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition">
                    <i class="fas fa-external-link-alt"></i> Buka Brosur Live
                </a>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (!empty($pesan_sukses)): ?>
            <div class="p-4 mb-6 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs sm:text-sm flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                <div class="font-bold"><?= $pesan_sukses ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="p-4 mb-6 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs sm:text-sm flex items-center gap-3 shadow-sm">
                <i class="fas fa-exclamation-triangle text-rose-600 text-xl"></i>
                <div class="font-bold"><?= $pesan_error ?></div>
            </div>
        <?php endif; ?>

        <!-- GRID UTAMA: FORM PENGATURAN (KIRI) + LIVE SMARTPHONE PREVIEW (KANAN) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- FORM PENGATURAN (8 KOLOM) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                
                <form method="POST" id="form-pengaturan-brosur" enctype="multipart/form-data" class="space-y-6">
                    
                    <!-- ============================================================ -->
                    <!-- KARTU 1: BACKGROUND COVER AMPLOP (URL & UPLOAD FILE)         -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                    <i class="fab fa-pinterest"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">1. Background Cover Amplop (URL & Upload File)</h2>
                                    <p class="text-xs text-slate-500">Tampilan saat pertama kali calon wali membuka link undangan</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <i class="fas fa-envelope-open-text mr-1"></i> Opening Cover
                            </span>
                        </div>

                        <!-- INPUT DIRECT URL -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Opsi A: Tempel Direct URL (Pinterest / Unsplash / Web):</label>
                            <div class="flex gap-2">
                                <input type="url" name="cover_bg_url" id="input-cover-bg-url" value="<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>" placeholder="https://i.pinimg.com/... atau https://images.unsplash.com/..." oninput="updateLivePreview()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#0b8478] focus:ring-1 focus:ring-[#0b8478] bg-slate-50 font-mono">
                                <button type="button" onclick="setPhoneTab('cover'); updateLivePreview();" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5">
                                    <i class="fas fa-eye text-amber-600"></i> Tes
                                </button>
                            </div>
                        </div>

                        <!-- INPUT UPLOAD FILE LOKAL -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 hover:border-amber-400 transition">
                            <label class="block text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fas fa-cloud-arrow-up text-amber-600"></i> Opsi B: Upload File Gambar Sendiri (Laptop/HP):
                            </label>
                            <input type="file" name="cover_bg_file" id="input-cover-bg-file" accept="image/png,image/jpeg,image/webp,image/jpg" onchange="previewUploadedFile(this, 'cover')" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-500 file:text-slate-950 hover:file:bg-amber-600 file:cursor-pointer cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, WEBP (maks. 5MB). Gambar otomatis tersimpan dan aktif seketika.</p>
                        </div>

                        <!-- PRESET CEPAT PILIHAN (COVER) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Atau Pilih Preset Gambar Siap Pakai (1-Klik):</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <button type="button" onclick="pilihPresetCover('https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Masjid Kubah</span>
                                        <span class="text-[9px] text-slate-400">Emerald Syahdu</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetCover('https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Alam Villa</span>
                                        <span class="text-[9px] text-slate-400">Pegunungan Pinus</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetCover('https://images.unsplash.com/photo-1564769625905-50e93615e769?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1564769625905-50e93615e769?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Madinah Emas</span>
                                        <span class="text-[9px] text-slate-400">Arabesque Luxury</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetCover('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Puncak Asri</span>
                                        <span class="text-[9px] text-slate-400">Udara Sejuk Villa</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetCover('https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Nabawi Twilight</span>
                                        <span class="text-[9px] text-slate-400">Senja Madinah</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetCover('https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Sage Botanical</span>
                                        <span class="text-[9px] text-slate-400">Eucalyptus Alami</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- SLIDER KEGELAPAN OVERLAY COVER (0% SAMPAI 100%) -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-bold text-slate-700">Tingkat Kegelapan Overlay Cover (Bisa 0% - Tanpa Digelapkan):</label>
                                <span id="cover-opacity-val" class="text-xs font-extrabold text-emerald-800"><?= round((float)($cfg['cover_overlay_opacity'] ?? 0.85) * 100) ?>%</span>
                            </div>
                            <input type="range" name="cover_overlay_opacity" id="input-cover-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>" oninput="updateLiveCoverOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-0.5">
                                <span>0% (Asli / Terang)</span>
                                <span>50% (Sedang)</span>
                                <span>100% (Gelap Solid)</span>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 2: BACKGROUND HALAMAN DALAM (URL & UPLOAD FILE)         -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">2. Background Laman Dalam Brosur (URL & Upload File)</h2>
                                    <p class="text-xs text-slate-500">Latar belakang seluruh konten brosur (kompetensi, biaya, countdown & formulir)</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                <i class="fas fa-layer-group mr-1"></i> Inner Wallpaper
                            </span>
                        </div>

                        <!-- INPUT DIRECT URL -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Opsi A: Tempel Direct URL (Pinterest / Tekstur Motif):</label>
                            <div class="flex gap-2">
                                <input type="url" name="body_bg_url" id="input-body-bg-url" value="<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>" placeholder="https://i.pinimg.com/... (pola wallpaper, tekstur marmer)" oninput="updateLiveBodyBg()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#0b8478] focus:ring-1 focus:ring-[#0b8478] bg-slate-50 font-mono">
                                <button type="button" onclick="setPhoneTab('body'); updateLiveBodyBg();" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5">
                                    <i class="fas fa-eye text-teal-700"></i> Tes
                                </button>
                            </div>
                        </div>

                        <!-- INPUT UPLOAD FILE LOKAL -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 hover:border-teal-400 transition">
                            <label class="block text-xs font-bold text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fas fa-cloud-arrow-up text-teal-700"></i> Opsi B: Upload File Wallpaper Laman Dalam (Laptop/HP):
                            </label>
                            <input type="file" name="body_bg_file" id="input-body-bg-file" accept="image/png,image/jpeg,image/webp,image/jpg" onchange="previewUploadedFile(this, 'body')" class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-teal-700 file:text-white hover:file:bg-teal-800 file:cursor-pointer cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">Upload foto gedung, alam asri, atau motif khusus sekolah dari laptop Anda.</p>
                        </div>

                        <!-- PRESET CEPAT PILIHAN (INNER BODY) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Atau Pilih Preset Tekstur Siap Pakai (1-Klik):</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                <button type="button" onclick="pilihPresetBody('https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-teal-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-teal-700">Arabesque Seni</span>
                                        <span class="text-[9px] text-slate-400">Tekstur Emas Lembut</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetBody('https://images.unsplash.com/photo-1533090161767-e6ffed986c88?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-teal-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1533090161767-e6ffed986c88?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-teal-700">Marmer Putih</span>
                                        <span class="text-[9px] text-slate-400">Clean & Mewah</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetBody('https://images.unsplash.com/photo-1511497584788-87676104235f?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-teal-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1511497584788-87676104235f?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-teal-700">Hutan Pinus</span>
                                        <span class="text-[9px] text-slate-400">Alam Asri Villa</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetBody('https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-teal-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-teal-700">Masjid Emerald</span>
                                        <span class="text-[9px] text-slate-400">Nuansa Syahdu</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetBody('https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-teal-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-teal-700">Kertas Mushaf</span>
                                        <span class="text-[9px] text-slate-400">Hangat Klasik</span>
                                    </div>
                                </button>
                                <button type="button" onclick="pilihPresetBody('https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-teal-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-teal-700">Senja Madinah</span>
                                        <span class="text-[9px] text-slate-400">Twilight Nabawi</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <!-- SLIDER KEGELAPAN OVERLAY HALAMAN DALAM (0% SAMPAI 100%) -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-bold text-slate-700">Transparansi Lapis Terang Laman Dalam (Bisa 0% - Tanpa Lapis):</label>
                                <span id="body-opacity-val" class="text-xs font-extrabold text-teal-800"><?= round((float)($cfg['body_overlay_opacity'] ?? 0.92) * 100) ?>%</span>
                            </div>
                            <input type="range" name="body_overlay_opacity" id="input-body-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['body_overlay_opacity'] ?? 0.92 ?>" oninput="updateLiveBodyOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                            <div class="flex justify-between text-[10px] text-slate-400 mt-0.5">
                                <span>0% (Asli Wallpaper)</span>
                                <span>50% (Transparan)</span>
                                <span>100% (Solid Putih/Krem)</span>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 3: SINKRONISASI WARNA TULISAN & TOMBOL DENGAN BACKGROUND-->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">3. Warna Tulisan, Tombol & Harmoni Kontras</h2>
                                    <p class="text-xs text-slate-500">Sesuaikan warna teks dan tombol agar harmonis dan jelas terbaca di atas background</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                <i class="fas fa-eye mr-1"></i> High Contrast
                            </span>
                        </div>

                        <!-- TOMBOL SINKRONISASI CEPAT (SMART AUTO SYNC) -->
                        <div class="p-3.5 bg-gradient-to-r from-amber-50 to-emerald-50 rounded-2xl border border-amber-200/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-black text-slate-900 block flex items-center gap-1.5">
                                    <i class="fas fa-wand-magic-sparkles text-amber-600"></i> Sinkronisasi Cerdas 1-Klik:
                                </span>
                                <span class="text-[11px] text-slate-600">Jika background terang (opacity 0%), gunakan mode terang agar tulisan hitam/zamrud pekat.</span>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" onclick="terapkanTemaWarna('light_ivory')" class="px-3 py-1.5 rounded-xl bg-white border border-slate-300 text-emerald-950 font-bold text-xs hover:bg-slate-100 shadow-sm flex items-center gap-1.5 transition">
                                    <i class="fas fa-sun text-amber-500"></i> Mode Latar Terang
                                </button>
                                <button type="button" onclick="terapkanTemaWarna('emerald_gold')" class="px-3 py-1.5 rounded-xl bg-emerald-950 text-amber-300 font-bold text-xs hover:bg-emerald-900 shadow-sm flex items-center gap-1.5 transition">
                                    <i class="fas fa-moon text-amber-400"></i> Mode Latar Gelap
                                </button>
                            </div>
                        </div>

                        <!-- PRESET TEMA WARNA HARMONIS -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Pilihan Tema Warna Harmonis:</label>
                            <input type="hidden" name="theme_color_mode" id="input-theme-color-mode" value="<?= htmlspecialchars($cfg['theme_color_mode'] ?? 'emerald_gold') ?>">
                            
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                                <button type="button" onclick="terapkanTemaWarna('emerald_gold')" class="p-2.5 rounded-2xl border-2 text-left transition <?= (($cfg['theme_color_mode'] ?? 'emerald_gold') === 'emerald_gold') ? 'border-[#0b8478] bg-teal-50/60' : 'border-slate-200' ?>" id="btn-theme-emerald_gold">
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#022c22] border border-amber-400 inline-block"></span>
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#fbbf24] inline-block"></span>
                                    </div>
                                    <span class="font-black text-xs text-slate-900 block">Emerald Gold</span>
                                    <span class="text-[9px] text-slate-500 block">Mewah syahdu</span>
                                </button>

                                <button type="button" onclick="terapkanTemaWarna('light_ivory')" class="p-2.5 rounded-2xl border-2 text-left transition <?= (($cfg['theme_color_mode'] ?? '') === 'light_ivory') ? 'border-[#0b8478] bg-teal-50/60' : 'border-slate-200' ?>" id="btn-theme-light_ivory">
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#ffffff] border border-slate-300 inline-block"></span>
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#064e45] inline-block"></span>
                                    </div>
                                    <span class="font-black text-xs text-slate-900 block">Light Ivory</span>
                                    <span class="text-[9px] text-slate-500 block">Latar Terang (0%)</span>
                                </button>

                                <button type="button" onclick="terapkanTemaWarna('midnight_luxe')" class="p-2.5 rounded-2xl border-2 text-left transition <?= (($cfg['theme_color_mode'] ?? '') === 'midnight_luxe') ? 'border-[#0b8478] bg-teal-50/60' : 'border-slate-200' ?>" id="btn-theme-midnight_luxe">
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#0f172a] inline-block"></span>
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#f8fafc] border border-slate-300 inline-block"></span>
                                    </div>
                                    <span class="font-black text-xs text-slate-900 block">Midnight Luxe</span>
                                    <span class="text-[9px] text-slate-500 block">Modern pekat</span>
                                </button>

                                <button type="button" onclick="terapkanTemaWarna('royal_maroon')" class="p-2.5 rounded-2xl border-2 text-left transition <?= (($cfg['theme_color_mode'] ?? '') === 'royal_maroon') ? 'border-[#0b8478] bg-teal-50/60' : 'border-slate-200' ?>" id="btn-theme-royal_maroon">
                                    <div class="flex items-center gap-1 mb-1">
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#7f1d1d] inline-block"></span>
                                        <span class="w-3.5 h-3.5 rounded-full bg-[#fbbf24] inline-block"></span>
                                    </div>
                                    <span class="font-black text-xs text-slate-900 block">Royal Maroon</span>
                                    <span class="text-[9px] text-slate-500 block">Klasik anggun</span>
                                </button>
                            </div>
                        </div>

                        <!-- PALET WARNA KUSTOM MANUAL -->
                        <div class="pt-3 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 mb-2">Penyesuaian Warna Mandiri (Color Picker):</label>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Teks Utama:</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="text_color" id="input-text-color" value="<?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>" oninput="updateLiveColors()" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200">
                                        <span id="label-text-color" class="text-xs font-mono font-bold"><?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?></span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Bismillah/Aksen:</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="accent_color" id="input-accent-color" value="<?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>" oninput="updateLiveColors()" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200">
                                        <span id="label-accent-color" class="text-xs font-mono font-bold"><?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?></span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Tombol Buka:</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="btn_bg_color" id="input-btn-bg-color" value="<?= htmlspecialchars($cfg['btn_bg_color'] ?? '#d97706') ?>" oninput="updateLiveColors()" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200">
                                        <span id="label-btn-bg-color" class="text-xs font-mono font-bold"><?= htmlspecialchars($cfg['btn_bg_color'] ?? '#d97706') ?></span>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Tulisan Tombol:</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" name="btn_text_color" id="input-btn-text-color" value="<?= htmlspecialchars($cfg['btn_text_color'] ?? '#022d27') ?>" oninput="updateLiveColors()" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200">
                                        <span id="label-btn-text-color" class="text-xs font-mono font-bold"><?= htmlspecialchars($cfg['btn_text_color'] ?? '#022d27') ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label class="block text-[11px] font-bold text-slate-600 mb-1">Gaya Latar Kartu Tamu:</label>
                                <select name="card_bg_style" id="input-card-bg-style" onchange="updateLiveColors()" class="w-full sm:w-64 px-3 py-2 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none">
                                    <option value="glass_dark" <?= (($cfg['card_bg_style'] ?? 'glass_dark') === 'glass_dark') ? 'selected' : '' ?>>Kaca Gelap (Glass Dark — Untuk Latar Gelap)</option>
                                    <option value="glass_light" <?= (($cfg['card_bg_style'] ?? '') === 'glass_light') ? 'selected' : '' ?>>Kaca Putih Bersih (Glass Light — Untuk Latar Terang)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 4: PENGATURAN POSISI GESER ELEMEN & GARIS BANTUAN      -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-lg">
                                    <i class="fas fa-arrows-up-down-left-right"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">4. Atur Posisi Tulisan, Gambar & Tombol (Drag & Slider)</h2>
                                    <p class="text-xs text-slate-500">Geser langsung di layar HP simulasi atau gunakan slider presisi di bawah</p>
                                </div>
                            </div>
                            
                            <!-- TOGGLE GARIS BANTUAN -->
                            <button type="button" id="btn-toggle-grid" onclick="toggleGuidelines()" class="px-3 py-1.5 rounded-xl font-bold text-xs bg-cyan-50 text-cyan-800 border border-cyan-300 hover:bg-cyan-100 transition flex items-center gap-1.5 shadow-sm">
                                <i class="fas fa-border-all"></i>
                                <span id="txt-toggle-grid">Garis Bantu: ON</span>
                            </button>
                        </div>

                        <!-- SLIDER POSISI TIGA BLOK UTAMA -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            
                            <!-- 1. Posisi Logo & Judul -->
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-bold text-slate-700"><i class="fas fa-image mr-1 text-amber-500"></i> Posisi Logo & Judul:</label>
                                    <span id="val-pos-header" class="text-xs font-mono font-bold text-slate-800"><?= $header_y ?>%</span>
                                </div>
                                <input type="range" name="pos_header_y" id="input-pos-header" min="2" max="35" step="1" value="<?= $header_y ?>" oninput="applyElementPosition('header', this.value)" class="w-full accent-amber-500 cursor-pointer">
                                <span class="text-[10px] text-slate-400">Jarak dari atas layar cover</span>
                            </div>

                            <!-- 2. Posisi Kartu Tamu -->
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-bold text-slate-700"><i class="fas fa-envelope mr-1 text-emerald-600"></i> Posisi Kartu Tamu:</label>
                                    <span id="val-pos-guest" class="text-xs font-mono font-bold text-slate-800"><?= $guest_y ?>%</span>
                                </div>
                                <input type="range" name="pos_guest_y" id="input-pos-guest" min="20" max="70" step="1" value="<?= $guest_y ?>" oninput="applyElementPosition('guest', this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                <span class="text-[10px] text-slate-400">Jarak vertikal kartu tamu</span>
                            </div>

                            <!-- 3. Posisi Tombol Buka -->
                            <div class="p-3 bg-slate-50 rounded-2xl border border-slate-200">
                                <div class="flex justify-between items-center mb-1">
                                    <label class="text-[11px] font-bold text-slate-700"><i class="fas fa-hand-pointer mr-1 text-rose-500"></i> Posisi Tombol Buka:</label>
                                    <span id="val-pos-btn" class="text-xs font-mono font-bold text-slate-800"><?= $btn_y ?>%</span>
                                </div>
                                <input type="range" name="pos_btn_y" id="input-pos-btn" min="60" max="95" step="1" value="<?= $btn_y ?>" oninput="applyElementPosition('btn', this.value)" class="w-full accent-rose-500 cursor-pointer">
                                <span class="text-[10px] text-slate-400">Jarak tombol buka dari atas</span>
                            </div>

                        </div>

                        <div class="flex items-center justify-between text-xs pt-1">
                            <span class="text-slate-400 text-[11px]">
                                <i class="fas fa-hand-back-fist text-amber-500 mr-1"></i> Tips: Anda juga bisa <strong>menekan & menggeser langsung</strong> elemen di dalam layar HP sebelah kanan.
                            </span>
                            <button type="button" onclick="resetDefaultPositions()" class="text-amber-700 font-bold hover:underline flex items-center gap-1 text-[11px]">
                                <i class="fas fa-rotate-left"></i> Reset Posisi Seimbang
                            </button>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 5: PENGATURAN COUNTDOWN TIMER (SAMA DENGAN WEB SEKOLAH) -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                                    <i class="fas fa-stopwatch"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">5. Pengaturan Countdown Timer SPMB</h2>
                                    <p class="text-xs text-slate-500">Hitung mundur sisa waktu pendaftaran (sama dengan beranda web sekolah)</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="show_countdown" value="1" <?= (!isset($cfg['show_countdown']) || $cfg['show_countdown'] == 1) ? 'checked' : '' ?> class="sr-only peer" onchange="toggleCountdownVisibility(this.checked)">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0b8478]"></div>
                                <span class="ml-2 text-xs font-bold text-slate-700">Aktif</span>
                            </label>
                        </div>

                        <!-- PILIHAN MODE COUNTDOWN -->
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-700">Mode Perhitungan Countdown:</label>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Opsi 1: Otomatis Sama dengan Web Sekolah -->
                                <label class="border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition <?= (($cfg['countdown_mode'] ?? 'auto') === 'auto') ? 'border-[#0b8478] bg-teal-50/50' : 'border-slate-200 bg-white' ?>" id="mode-label-auto">
                                    <input type="radio" name="countdown_mode" value="auto" <?= (($cfg['countdown_mode'] ?? 'auto') === 'auto') ? 'checked' : '' ?> onchange="changeCountdownMode('auto')" class="mt-1 text-[#0b8478] focus:ring-[#0b8478]">
                                    <div>
                                        <span class="font-bold text-xs text-slate-900 block flex items-center gap-1.5">
                                            <i class="fas fa-magic text-[#0b8478]"></i> Otomatis Siklus Gelombang
                                        </span>
                                        <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                            Sama persis dengan beranda web sekolah: <strong>Gelombang 1</strong> (s/d 31 Des), <strong>Gelombang 2</strong> (s/d 31 Mar), <strong>Gelombang 3</strong> (s/d 30 Jun).
                                        </span>
                                    </div>
                                </label>

                                <!-- Opsi 2: Kustom Tanggal Tertentu -->
                                <label class="border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition <?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? 'border-[#0b8478] bg-teal-50/50' : 'border-slate-200 bg-white' ?>" id="mode-label-custom">
                                    <input type="radio" name="countdown_mode" value="custom" <?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? 'checked' : '' ?> onchange="changeCountdownMode('custom')" class="mt-1 text-[#0b8478] focus:ring-[#0b8478]">
                                    <div>
                                        <span class="font-bold text-xs text-slate-900 block flex items-center gap-1.5">
                                            <i class="fas fa-calendar-day text-amber-600"></i> Kustom Batas Waktu
                                        </span>
                                        <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                            Tentukan tanggal & jam batas penutupan khusus secara manual (misal perpanjangan khusus).
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- INPUT TANGGAL KUSTOM -->
                        <div id="custom-target-group" class="<?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? '' : 'hidden' ?> bg-amber-50/70 p-4 rounded-2xl border border-amber-200">
                            <label class="block text-xs font-bold text-amber-900 mb-1">Pilih Tanggal & Waktu Batas Akhir:</label>
                            <input type="datetime-local" name="countdown_target" id="input-countdown-target" value="<?= date('Y-m-d\TH:i', strtotime($cfg['countdown_target'] ?? '2026-12-31 23:59:59')) ?>" oninput="updateAdminCountdownWidget()" class="w-full sm:w-72 px-4 py-2 rounded-xl border border-amber-300 bg-white text-xs font-mono font-bold text-slate-800">
                        </div>

                        <!-- JUDUL COUNTDOWN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Judul / Teks Countdown:</label>
                            <input type="text" name="countdown_title" id="input-countdown-title" value="<?= htmlspecialchars($cfg['countdown_title'] ?? '⏳ Sisa Waktu Pendaftaran Berakhir:') ?>" oninput="updateAdminCountdownWidget()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 6: TAHUN AJARAN & RINCIAN BIAYA PENDIDIKAN             -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-base text-slate-900">6. Periode SPMB & Biaya Pendidikan</h2>
                                <p class="text-xs text-slate-500">Data ini otomatis tampil pada teks amplop dan tabel biaya brosur</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran:</label>
                                <input type="text" name="tahun_ajaran" id="input-tahun" value="<?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>" oninput="updateLiveText()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Gelombang:</label>
                                <input type="text" name="periode_gelombang" id="input-gelombang" value="<?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?>" oninput="updateLiveText()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kuota Santri:</label>
                                <input type="number" name="kuota_santri" value="<?= $cfg['kuota_santri'] ?? 20 ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">1. Biaya Pendaftaran & Observasi (Rp):</label>
                                <input type="number" name="biaya_pendaftaran" value="<?= $cfg['biaya_pendaftaran'] ?? 350000 ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">2. Uang Pangkal Masuk (Rp):</label>
                                <input type="number" name="biaya_pangkal" value="<?= $cfg['biaya_pangkal'] ?? 12500000 ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">3. Biaya Tahunan (Rp):</label>
                                <input type="number" name="biaya_tahunan" value="<?= $cfg['biaya_tahunan'] ?? 2500000 ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">4. SPP All-in Bulanan (Rp):</label>
                                <input type="number" name="biaya_spp" value="<?= $cfg['biaya_spp'] ?? 1650000 ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="block text-xs font-bold text-amber-800 mb-1">Potongan / Diskon Khusus Gelombang (Rp):</label>
                            <input type="number" name="diskon_gelombang" value="<?= $cfg['diskon_gelombang'] ?? 2000000 ?>" required class="w-full px-4 py-2 rounded-xl border border-amber-300 bg-amber-50 text-xs font-bold text-amber-900">
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <div class="sticky bottom-4 z-20">
                        <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-[#0b8478] hover:bg-[#075f56] text-white font-black text-sm sm:text-base shadow-xl flex items-center justify-center gap-2 transform active:scale-95 transition">
                            <i class="fas fa-save text-lg"></i>
                            <span>Simpan Seluruh Pengaturan Brosur</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- SIMULASI SMARTPHONE LIVE PREVIEW & VISUAL CANVAS DRAG (4 KOLOM) -->
            <div class="lg:col-span-5 xl:col-span-4 sticky top-6 flex flex-col items-center">
                
                <!-- TAB SWITCHER: COVER VS INNER -->
                <div class="flex items-center gap-1 p-1 bg-slate-200/80 rounded-2xl mb-2.5 shadow-inner">
                    <button type="button" id="tab-btn-cover" onclick="setPhoneTab('cover')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white text-slate-900 shadow-sm transition flex items-center gap-1.5">
                        <i class="fas fa-envelope-open-text text-amber-600"></i> Cover Amplop
                    </button>
                    <button type="button" id="tab-btn-body" onclick="setPhoneTab('body')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                        <i class="fas fa-file-invoice text-teal-700"></i> Laman Dalam
                    </button>
                </div>

                <div class="text-[11px] text-slate-500 mb-2 flex items-center gap-1.5">
                    <i class="fas fa-arrows-up-down text-cyan-600"></i>
                    <span>Tarik elemen cover langsung untuk mengatur posisi Y</span>
                </div>

                <!-- PHONE MOCKUP -->
                <div class="phone-mockup bg-slate-900 text-white flex flex-col relative" id="phone-container">
                    <div class="phone-speaker"></div>

                    <!-- GARIS BANTUAN (GUIDELINES & GRID) -->
                    <div id="grid-overlay" class="absolute inset-0 canvas-grid-bg pointer-events-none z-30 transition-opacity duration-300">
                        <!-- Garis Tengah Presisi X=50% -->
                        <div class="center-guide-line"></div>
                        <!-- Garis Horizontal Aktif saat Dragging -->
                        <div id="guide-horizontal" class="active-horizontal-guide hidden"></div>
                        <!-- Indikator Y floating badge -->
                        <div id="guide-badge-y" class="absolute top-2 left-2 px-2 py-0.5 rounded bg-cyan-900/90 text-cyan-200 text-[9px] font-mono font-bold hidden border border-cyan-400/50 z-50">
                            Y: 0%
                        </div>
                    </div>
                    
                    <!-- 1. SCREEN VIEW: SIMULASI COVER AMPLOP INTERAKTIF -->
                    <div id="preview-screen-cover" class="relative w-full h-full p-4 text-center bg-cover bg-center overflow-hidden transition-all duration-500 select-none" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS COVER -->
                        <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>;"></div>

                        <!-- BLOK 1: HEADER LOGO & BISMILLAH (DRAGGABLE) -->
                        <div id="preview-elem-header" class="drag-box z-20 text-center" style="top: <?= $header_y ?>%;" data-elem="header">
                            <span class="absolute -top-3 right-0 text-[8px] bg-amber-500/90 text-slate-950 px-1.5 py-0.2 rounded font-extrabold shadow opacity-0 group-hover:opacity-100 hover:opacity-100">
                                <i class="fas fa-grip-vertical mr-0.5"></i>Logo
                            </span>
                            <p class="font-arabic text-sm transition-colors" id="view-bismillah" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
                            <img src="upload/logo-villa-quran.png" class="w-11 h-11 mx-auto mt-1 drop-shadow pointer-events-none">
                            <h3 class="font-extrabold text-xs mt-1 transition-colors" id="view-title" style="color: <?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>;">Villa Quran Indonesia</h3>
                            <p id="preview-sub" class="text-[9px] font-medium transition-colors" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;"><?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?></p>
                        </div>

                        <!-- BLOK 2: KARTU TAMU CALON WALI (DRAGGABLE) -->
                        <div id="preview-elem-guest" class="drag-box z-20 border rounded-2xl p-3 backdrop-blur-md shadow-lg transition-colors <?= (($cfg['card_bg_style'] ?? 'glass_dark') === 'glass_light') ? 'bg-white/85 border-emerald-600/40 text-slate-800' : 'bg-black/45 border-amber-400/40 text-white' ?>" style="top: <?= $guest_y ?>%;" data-elem="guest">
                            <span class="absolute -top-3 right-0 text-[8px] bg-teal-500/90 text-white px-1.5 py-0.2 rounded font-extrabold shadow">
                                <i class="fas fa-grip-vertical mr-0.5"></i>Tamu
                            </span>
                            <span class="text-[8px] uppercase tracking-wider font-bold block" id="view-guest-sub" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">Kepada Yth. Calon Wali:</span>
                            <div class="text-xs font-black mt-0.5 transition-colors" id="view-guest-name">Bpk. Hendy Pratama</div>
                            <p class="text-[8px] text-slate-300 mt-0.5 leading-tight opacity-90">Undangan Silaturahmi Mahabbah & Brosur Pendidikan Generasi Qur'ani.</p>
                            <div class="mt-1.5 pt-1.5 border-t border-white/10 text-[8px] font-semibold" id="preview-tahun-txt" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                Tahun Ajaran <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>
                            </div>
                        </div>

                        <!-- BLOK 3: TOMBOL BUKA UNDANGAN (DRAGGABLE) -->
                        <div id="preview-elem-btn" class="drag-box z-20" style="top: <?= $btn_y ?>%;" data-elem="btn">
                            <span class="absolute -top-3 right-0 text-[8px] bg-rose-500/90 text-white px-1.5 py-0.2 rounded font-extrabold shadow">
                                <i class="fas fa-grip-vertical mr-0.5"></i>Tombol
                            </span>
                            <button type="button" onclick="setPhoneTab('body')" id="view-btn-preview" class="w-full py-2.5 px-3 rounded-xl font-black text-xs flex items-center justify-center gap-1.5 shadow-lg active:scale-95 transition-all" style="background: <?= htmlspecialchars($cfg['btn_bg_color'] ?? '#d97706') ?>; color: <?= htmlspecialchars($cfg['btn_text_color'] ?? '#022d27') ?>;">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Buka Brosur & Undangan</span>
                            </button>
                            <p class="text-[8px] mt-1 text-center opacity-80" id="view-audio-note" style="color: <?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>;"><i class="fas fa-music mr-1"></i> Alunan Backsound Syahdu</p>
                        </div>
                    </div>

                    <!-- 2. SCREEN VIEW: SIMULASI HALAMAN DALAM -->
                    <div id="preview-screen-body" class="hidden relative w-full h-full p-4 overflow-y-auto bg-cover bg-center transition-all duration-500 select-none" style="background-image: url('<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS HALAMAN DALAM -->
                        <div id="preview-body-overlay" class="absolute inset-0 bg-[#f6f7f5] transition-all duration-300" style="opacity: <?= $cfg['body_overlay_opacity'] ?? 0.92 ?>;"></div>

                        <!-- KONTEN HALAMAN DALAM -->
                        <div class="relative z-10 space-y-3 pt-4 text-slate-800 text-left">
                            
                            <!-- Header Mini -->
                            <div class="text-center">
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-900 text-[9px] font-bold">
                                    TA <span id="preview-body-tahun"><?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?></span>
                                </span>
                                <h4 class="font-black text-sm text-emerald-950 mt-1">Villa Quran Indonesia</h4>
                                <p class="text-[9px] text-emerald-700">Pesantren Tahfidz Berasrama Nyaman Ala Villa</p>
                            </div>

                            <!-- Simulasi Countdown Timer di HP -->
                            <div id="preview-countdown-box" class="p-3 rounded-2xl bg-gradient-to-b from-white to-emerald-50/90 border border-amber-500/30 shadow-md text-center">
                                <span class="text-[8px] font-extrabold uppercase tracking-wider text-amber-800 block">⏳ Batas Akhir Pendaftaran</span>
                                <div class="flex justify-center items-center space-x-1.5 mt-2">
                                    <div class="bg-emerald-900 text-amber-300 font-black text-xs w-7 h-7 flex items-center justify-center rounded-lg font-mono" id="phone-cd-hari">00</div>
                                    <span class="text-[7px] text-emerald-900 font-bold">:</span>
                                    <div class="bg-emerald-900 text-amber-300 font-black text-xs w-7 h-7 flex items-center justify-center rounded-lg font-mono" id="phone-cd-jam">00</div>
                                    <span class="text-[7px] text-emerald-900 font-bold">:</span>
                                    <div class="bg-emerald-900 text-amber-300 font-black text-xs w-7 h-7 flex items-center justify-center rounded-lg font-mono" id="phone-cd-menit">00</div>
                                    <span class="text-[7px] text-emerald-900 font-bold">:</span>
                                    <div class="bg-white text-rose-600 font-black text-xs w-7 h-7 flex items-center justify-center rounded-lg border border-rose-200 font-mono shadow-sm" id="phone-cd-detik">00</div>
                                </div>
                            </div>

                            <!-- Kartu Muqaddimah -->
                            <div class="p-3 rounded-2xl bg-white/90 border border-emerald-100 shadow-sm text-[10px] leading-relaxed text-slate-700">
                                <span class="font-bold text-emerald-950 block text-xs mb-1">Target Kompetensi Santri:</span>
                                <p>• Tahfidz Mutqin 15-30 Juz Bersanad</p>
                                <p>• Ijazah Resmi Negara Setara SMP/SMA</p>
                                <p>• Digital Marketing, AI Terapan & Solopreneur</p>
                            </div>

                            <div class="text-center pt-2">
                                <button type="button" onclick="setPhoneTab('cover')" class="text-[10px] text-emerald-700 font-bold underline">
                                    &larr; Kembali ke Cover Amplop
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                <p class="text-[11px] text-slate-400 mt-3 text-center">
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Preview otomatis ter-update secara real-time saat gambar, warna, atau posisi diubah.
                </p>
            </div>

        </div>

    </main>

    <!-- SCRIPT INTERAKTIF DRAG & DROP, GUIDELINES, COLOR SYNC & REALTIME PREVIEW -->
    <script>
        let activePhoneTab = 'cover';
        let showGuidelines = true;

        function setPhoneTab(tab) {
            activePhoneTab = tab;
            const coverScreen = document.getElementById('preview-screen-cover');
            const bodyScreen  = document.getElementById('preview-screen-body');
            const btnCover    = document.getElementById('tab-btn-cover');
            const btnBody     = document.getElementById('tab-btn-body');

            if (tab === 'cover') {
                coverScreen.classList.remove('hidden');
                bodyScreen.classList.add('hidden');
                btnCover.className = 'px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white text-slate-900 shadow-sm transition flex items-center gap-1.5';
                btnBody.className  = 'px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5';
            } else {
                coverScreen.classList.add('hidden');
                bodyScreen.classList.remove('hidden');
                btnCover.className = 'px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5';
                btnBody.className  = 'px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white text-slate-900 shadow-sm transition flex items-center gap-1.5';
            }
        }

        // Toggle Grid & Alignment Guidelines
        function toggleGuidelines() {
            showGuidelines = !showGuidelines;
            const grid = document.getElementById('grid-overlay');
            const txt = document.getElementById('txt-toggle-grid');
            if (showGuidelines) {
                grid.style.opacity = '1';
                txt.innerText = 'Garis Bantu: ON';
            } else {
                grid.style.opacity = '0';
                txt.innerText = 'Garis Bantu: OFF';
            }
        }

        // 1. File Upload Instant Preview
        function previewUploadedFile(input, target) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    if (target === 'cover') {
                        document.getElementById('input-cover-bg-url').value = '';
                        document.getElementById('preview-screen-cover').style.backgroundImage = `url('${dataUrl}')`;
                        setPhoneTab('cover');
                    } else {
                        document.getElementById('input-body-bg-url').value = '';
                        document.getElementById('preview-screen-body').style.backgroundImage = `url('${dataUrl}')`;
                        setPhoneTab('body');
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function pilihPresetCover(url) {
            document.getElementById('input-cover-bg-url').value = url;
            setPhoneTab('cover');
            updateLivePreview();
        }

        function pilihPresetBody(url) {
            document.getElementById('input-body-bg-url').value = url;
            setPhoneTab('body');
            updateLiveBodyBg();
        }

        function updateLivePreview() {
            const url = document.getElementById('input-cover-bg-url').value.trim();
            const screen = document.getElementById('preview-screen-cover');
            if (url) {
                screen.style.backgroundImage = `url('${url}')`;
            }
        }

        // 3. Opacity Control (Bisa 0% - Tanpa Digelapkan)
        function updateLiveCoverOpacity(val) {
            const pct = Math.round(val * 100);
            document.getElementById('cover-opacity-val').innerText = (pct === 0) ? '0% (Tanpa Digelapkan)' : pct + '%';
            document.getElementById('preview-cover-overlay').style.opacity = val;
        }

        function updateLiveBodyBg() {
            const url = document.getElementById('input-body-bg-url').value.trim();
            const screen = document.getElementById('preview-screen-body');
            if (url) {
                screen.style.backgroundImage = `url('${url}')`;
            }
        }

        function updateLiveBodyOpacity(val) {
            const pct = Math.round(val * 100);
            document.getElementById('body-opacity-val').innerText = (pct === 0) ? '0% (Wallpaper Asli)' : pct + '%';
            document.getElementById('preview-body-overlay').style.opacity = val;
        }

        function updateLiveText() {
            const gelombang = document.getElementById('input-gelombang').value;
            const tahun = document.getElementById('input-tahun').value;
            
            document.getElementById('preview-sub').innerText = gelombang;
            document.getElementById('preview-tahun-txt').innerText = 'Tahun Ajaran ' + tahun;
            document.getElementById('preview-body-tahun').innerText = tahun;
        }

        // 4. Color Theme Harmonization & Sync
        function terapkanTemaWarna(mode) {
            document.getElementById('input-theme-color-mode').value = mode;
            
            // Set styles per theme
            if (mode === 'light_ivory') {
                // Untuk Latar Terang / Opacity 0%
                document.getElementById('input-text-color').value = '#064e45';
                document.getElementById('input-accent-color').value = '#b45309';
                document.getElementById('input-btn-bg-color').value = '#064e45';
                document.getElementById('input-btn-text-color').value = '#fcd34d';
                document.getElementById('input-card-bg-style').value = 'glass_light';
            } else if (mode === 'midnight_luxe') {
                document.getElementById('input-text-color').value = '#f8fafc';
                document.getElementById('input-accent-color').value = '#fcd34d';
                document.getElementById('input-btn-bg-color').value = '#0f172a';
                document.getElementById('input-btn-text-color').value = '#fbbf24';
                document.getElementById('input-card-bg-style').value = 'glass_dark';
            } else if (mode === 'royal_maroon') {
                document.getElementById('input-text-color').value = '#ffffff';
                document.getElementById('input-accent-color').value = '#fbbf24';
                document.getElementById('input-btn-bg-color').value = '#7f1d1d';
                document.getElementById('input-btn-text-color').value = '#fbbf24';
                document.getElementById('input-card-bg-style').value = 'glass_dark';
            } else {
                // Default: Emerald Gold
                document.getElementById('input-text-color').value = '#ffffff';
                document.getElementById('input-accent-color').value = '#fbbf24';
                document.getElementById('input-btn-bg-color').value = '#d97706';
                document.getElementById('input-btn-text-color').value = '#022d27';
                document.getElementById('input-card-bg-style').value = 'glass_dark';
            }

            // Update UI buttons active state
            ['emerald_gold', 'light_ivory', 'midnight_luxe', 'royal_maroon'].forEach(m => {
                const btn = document.getElementById('btn-theme-' + m);
                if (btn) {
                    if (m === mode) {
                        btn.className = 'p-2.5 rounded-2xl border-2 text-left transition border-[#0b8478] bg-teal-50/60';
                    } else {
                        btn.className = 'p-2.5 rounded-2xl border-2 text-left transition border-slate-200';
                    }
                }
            });

            updateLiveColors();
        }

        function updateLiveColors() {
            const textColor   = document.getElementById('input-text-color').value;
            const accentColor = document.getElementById('input-accent-color').value;
            const btnBgColor  = document.getElementById('input-btn-bg-color').value;
            const btnTxtColor = document.getElementById('input-btn-text-color').value;
            const cardStyle   = document.getElementById('input-card-bg-style').value;

            // Labels
            document.getElementById('label-text-color').innerText = textColor;
            document.getElementById('label-accent-color').innerText = accentColor;
            document.getElementById('label-btn-bg-color').innerText = btnBgColor;
            document.getElementById('label-btn-text-color').innerText = btnTxtColor;

            // Apply to Phone Mockup
            document.getElementById('view-title').style.color = textColor;
            document.getElementById('view-audio-note').style.color = textColor;
            document.getElementById('view-bismillah').style.color = accentColor;
            document.getElementById('preview-sub').style.color = accentColor;
            document.getElementById('view-guest-sub').style.color = accentColor;
            document.getElementById('preview-tahun-txt').style.color = accentColor;

            const btn = document.getElementById('view-btn-preview');
            btn.style.background = btnBgColor;
            btn.style.color = btnTxtColor;

            const guestCard = document.getElementById('preview-elem-guest');
            if (cardStyle === 'glass_light') {
                guestCard.className = 'drag-box z-20 border rounded-2xl p-3 backdrop-blur-md shadow-lg transition-colors bg-white/85 border-emerald-600/40 text-slate-900';
            } else {
                guestCard.className = 'drag-box z-20 border rounded-2xl p-3 backdrop-blur-md shadow-lg transition-colors bg-black/45 border-amber-400/40 text-white';
            }
        }

        // 2. Interactive Drag & Drop + Slider Positioning
        function applyElementPosition(elemKey, percentVal) {
            const elem = document.getElementById('preview-elem-' + elemKey);
            if (elem) {
                elem.style.top = percentVal + '%';
            }
            const label = document.getElementById('val-pos-' + elemKey);
            if (label) {
                label.innerText = percentVal + '%';
            }
            const input = document.getElementById('input-pos-' + elemKey);
            if (input && input.value != percentVal) {
                input.value = percentVal;
            }
        }

        function resetDefaultPositions() {
            applyElementPosition('header', 12);
            applyElementPosition('guest', 45);
            applyElementPosition('btn', 82);
        }

        // Enable Dragging directly inside Phone Mockup
        (function initDraggableElements() {
            const phoneScreen = document.getElementById('preview-screen-cover');
            const guideH = document.getElementById('guide-horizontal');
            const badgeY = document.getElementById('guide-badge-y');
            let currentDragElem = null;
            let startY = 0;
            let startTopPct = 0;

            ['header', 'guest', 'btn'].forEach(key => {
                const el = document.getElementById('preview-elem-' + key);
                if (!el) return;

                el.addEventListener('mousedown', function(e) {
                    if (e.target.tagName.toLowerCase() === 'button') return;
                    currentDragElem = el;
                    el.classList.add('dragging');
                    startY = e.clientY;
                    
                    const screenH = phoneScreen.clientHeight;
                    startTopPct = (el.offsetTop / screenH) * 100;

                    guideH.classList.remove('hidden');
                    badgeY.classList.remove('hidden');
                    e.preventDefault();
                });
            });

            window.addEventListener('mousemove', function(e) {
                if (!currentDragElem) return;
                const screenH = phoneScreen.clientHeight;
                const deltaY = e.clientY - startY;
                const deltaPct = (deltaY / screenH) * 100;
                let newPct = Math.round(startTopPct + deltaPct);

                // Batasan batas drag
                const key = currentDragElem.dataset.elem;
                if (key === 'header') newPct = Math.max(2, Math.min(38, newPct));
                if (key === 'guest')  newPct = Math.max(18, Math.min(72, newPct));
                if (key === 'btn')    newPct = Math.max(55, Math.min(95, newPct));

                applyElementPosition(key, newPct);

                // Update garis bantuan horizontal
                guideH.style.top = currentDragElem.offsetTop + 'px';
                badgeY.style.top = (currentDragElem.offsetTop - 20) + 'px';
                badgeY.innerText = 'Posisi Y: ' + newPct + '%';
            });

            window.addEventListener('mouseup', function() {
                if (currentDragElem) {
                    currentDragElem.classList.remove('dragging');
                    currentDragElem = null;
                    guideH.classList.add('hidden');
                    badgeY.classList.add('hidden');
                }
            });
        })();

        // Countdown Logic (Sync with Web Sekolah)
        function changeCountdownMode(mode) {
            const group = document.getElementById('custom-target-group');
            const lblAuto = document.getElementById('mode-label-auto');
            const lblCustom = document.getElementById('mode-label-custom');

            if (mode === 'custom') {
                group.classList.remove('hidden');
                lblCustom.className = 'border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition border-[#0b8478] bg-teal-50/50';
                lblAuto.className = 'border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition border-slate-200 bg-white';
            } else {
                group.classList.add('hidden');
                lblAuto.className = 'border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition border-[#0b8478] bg-teal-50/50';
                lblCustom.className = 'border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition border-slate-200 bg-white';
            }
            updateAdminCountdownWidget();
        }

        function toggleCountdownVisibility(isChecked) {
            const box = document.getElementById('preview-countdown-box');
            if (box) {
                box.style.display = isChecked ? 'block' : 'none';
            }
        }

        function updateAdminCountdownWidget() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth() + 1;
            
            let mode = 'auto';
            const radioCustom = document.querySelector('input[name="countdown_mode"][value="custom"]');
            if (radioCustom && radioCustom.checked) mode = 'custom';

            let endDate;
            if (mode === 'custom') {
                const targetVal = document.getElementById('input-countdown-target').value;
                endDate = targetVal ? new Date(targetVal) : new Date(year, 11, 31, 23, 59, 59);
            } else {
                if (month >= 7 && month <= 12) {
                    endDate = new Date(year, 11, 31, 23, 59, 59);
                } else if (month >= 1 && month <= 3) {
                    endDate = new Date(year, 2, 31, 23, 59, 59);
                } else {
                    endDate = new Date(year, 5, 30, 23, 59, 59);
                }
            }

            const diff = endDate.getTime() - now.getTime();
            if (diff > 0) {
                const d = Math.floor(diff / (1000 * 60 * 60 * 24));
                const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((diff % (1000 * 60)) / 1000);

                const dStr = d.toString().padStart(2, '0');
                const hStr = h.toString().padStart(2, '0');
                const mStr = m.toString().padStart(2, '0');
                const sStr = s.toString().padStart(2, '0');

                if (document.getElementById('phone-cd-hari')) document.getElementById('phone-cd-hari').innerText = dStr;
                if (document.getElementById('phone-cd-jam')) document.getElementById('phone-cd-jam').innerText = hStr;
                if (document.getElementById('phone-cd-menit')) document.getElementById('phone-cd-menit').innerText = mStr;
                if (document.getElementById('phone-cd-detik')) document.getElementById('phone-cd-detik').innerText = sStr;
            }
        }

        setInterval(updateAdminCountdownWidget, 1000);
        updateAdminCountdownWidget();
        updateLiveColors();
    </script>
</body>
</html>
