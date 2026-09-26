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

// Variabel Notifikasi
$pesan_sukses = '';
$pesan_error  = '';

// Proses Simpan Pengaturan Background
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $cover_bg_url         = $conn->real_escape_string(trim($_POST['cover_bg_url'] ?? ''));
    $cover_overlay_opacity= (float)($_POST['cover_overlay_opacity'] ?? 0.85);
    $body_bg_url          = $conn->real_escape_string(trim($_POST['body_bg_url'] ?? ''));
    $body_overlay_opacity = (float)($_POST['body_overlay_opacity'] ?? 0.92);

    // 1. Handle Upload File Background Cover
    if (!empty($_FILES['cover_bg_file']['name']) && $_FILES['cover_bg_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['cover_bg_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (!is_dir('upload')) mkdir('upload', 0755, true);
            $new_cover = 'upload/bg_cover_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_bg_file']['tmp_name'], $new_cover)) {
                $cover_bg_url = $new_cover;
            }
        }
    }

    // 2. Handle Upload File Background Laman Dalam
    if (!empty($_FILES['body_bg_file']['name']) && $_FILES['body_bg_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['body_bg_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            if (!is_dir('upload')) mkdir('upload', 0755, true);
            $new_body = 'upload/bg_body_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['body_bg_file']['tmp_name'], $new_body)) {
                $body_bg_url = $new_body;
            }
        }
    }

    $sql_update = "UPDATE pengaturan_brosur SET 
                    cover_bg_url = '$cover_bg_url',
                    cover_overlay_opacity = $cover_overlay_opacity,
                    body_bg_url = '$body_bg_url',
                    body_overlay_opacity = $body_overlay_opacity
                   WHERE id = 1";

    if ($conn->query($sql_update)) {
        $pesan_sukses = "Alhamdulillah! Background Brosur & Tingkat Opasitas berhasil diperbarui.";
    } else {
        $pesan_error = "Gagal menyimpan background: " . $conn->error;
    }
}

// Ambil Data Terkini dari Database
$q = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg = $q ? $q->fetch_assoc() : [];

// Decode posisi elemen cover
$pos = !empty($cfg['cover_elements_pos']) ? json_decode($cfg['cover_elements_pos'], true) : [];
$header_y = $pos['header_y'] ?? 12;
$guest_y  = $pos['guest_y'] ?? 45;
$btn_y    = $pos['btn_y'] ?? 82;

// Ambil Data Real Sinkron Web (Fasilitas, Pengajar, Galeri, Biaya, Testimoni)
$web_fasilitas = [];
$q_f = $conn->query("SELECT * FROM fasilitas ORDER BY id ASC");
if ($q_f && $q_f->num_rows > 0) while ($r = $q_f->fetch_assoc()) $web_fasilitas[] = $r;

$web_pengajar = [];
$q_p = $conn->query("SELECT * FROM pengajar ORDER BY id ASC");
if ($q_p && $q_p->num_rows > 0) while ($r = $q_p->fetch_assoc()) $web_pengajar[] = $r;

$web_galeri = [];
$q_g = $conn->query("SELECT * FROM galeri ORDER BY id DESC LIMIT 8");
if ($q_g && $q_g->num_rows > 0) while ($r = $q_g->fetch_assoc()) $web_galeri[] = $r;

$web_biaya = ['pendaftaran' => [], 'pangkal' => [], 'tahunan' => [], 'spp' => []];
$web_biaya_subtotal = ['pendaftaran' => 0, 'pangkal' => 0, 'tahunan' => 0, 'spp' => 0];
$q_b = $conn->query("SELECT * FROM biaya ORDER BY id ASC");
if ($q_b && $q_b->num_rows > 0) {
    while ($r = $q_b->fetch_assoc()) {
        $k = strtolower(trim($r['kategori']));
        if (isset($web_biaya[$k])) {
            $web_biaya[$k][] = $r;
            $web_biaya_subtotal[$k] += (int)$r['nominal'];
        }
    }
}

// Acuan Utama dari Pengaturan Info Biaya
$biaya_pendaftaran_val = ($web_biaya_subtotal['pendaftaran'] > 0) ? $web_biaya_subtotal['pendaftaran'] : ($cfg['biaya_pendaftaran'] ?? 350000);
$biaya_pangkal_val     = ($web_biaya_subtotal['pangkal'] > 0) ? $web_biaya_subtotal['pangkal'] : ($cfg['biaya_pangkal'] ?? 12500000);
$biaya_tahunan_val     = ($web_biaya_subtotal['tahunan'] > 0) ? $web_biaya_subtotal['tahunan'] : ($cfg['biaya_tahunan'] ?? 2500000);
$biaya_spp_val         = ($web_biaya_subtotal['spp'] > 0) ? $web_biaya_subtotal['spp'] : ($cfg['biaya_spp'] ?? 1650000);

$web_testimoni = [];
$q_t = $conn->query("SELECT * FROM testimoni ORDER BY id DESC");
if ($q_t && $q_t->num_rows > 0) while ($r = $q_t->fetch_assoc()) $web_testimoni[] = $r;

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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cinzel:wght@500;700;900&family=Inter:wght@400;600;700&family=Outfit:wght@400;600;800&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }
        .font-arabic { font-family: 'Amiri', serif; }
        
        /* Modern Smartphone Mockup Frame */
        .phone-mockup {
            width: 350px;
            height: 700px;
            border: 12px solid #0f172a;
            border-radius: 46px;
            overflow: hidden;
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
        }
        .phone-speaker {
            position: absolute;
            top: 12px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 5px;
            background: #334155;
            border-radius: 10px;
            z-index: 50;
        }
        .phone-camera {
            position: absolute;
            top: 11px;
            right: 105px;
            width: 8px;
            height: 8px;
            background: #1e293b;
            border-radius: 50%;
            z-index: 50;
        }

        /* Scrollbar Hide */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Simulasi Phone Bottom Bar Menu Items */
        .sim-card-menu-item {
            flex: 0 0 calc(25% - 4.5px);
            min-width: calc(25% - 4.5px);
            max-width: calc(25% - 4.5px);
            cursor: pointer;
            user-select: none;
        }

        /* Ambient Background Pattern */
        .ambient-bg {
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 24px 24px;
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
                    <i class="fas fa-mobile-screen-button"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Simulasi Layar Brosur PSB Digital</h1>
                    <p class="text-xs text-slate-500">Live preview interaktif tampilan brosur digital smartphone calon santri</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="brosur.php" target="_blank" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-500 hover:to-amber-600 text-teal-950 font-black text-xs shadow-md transition flex items-center gap-2 transform active:scale-95">
                    <i class="fas fa-external-link-alt text-xs"></i>
                    <span>Buka Halaman Brosur Publik</span>
                </a>
            </div>
        </header>

        <!-- WORKSPACE AREA: 2 KOLOM (PAPAN PENGATURAN KIRI & SIMULASI KANAN) -->
        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- PANEL KIRI: PAPAN PENGATURAN (TAHAP 1: PENGATURAN BACKGROUND PORTRAIT) -->
            <div class="lg:col-span-7 xl:col-span-7 space-y-6">
                
                <form action="" method="POST" enctype="multipart/form-data" id="form-pengaturan-bg" class="space-y-6">

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

                    <!-- KARTU UTAMA: PENGATURAN BACKGROUND -->
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

                        <!-- 1. BACKGROUND COVER AMPLOP -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                                <span class="font-extrabold text-xs sm:text-sm text-slate-900 flex items-center gap-2">
                                    <i class="fas fa-envelope-open-text text-amber-500"></i> 1. Background Cover Amplop
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200/60">
                                    Layar Pembuka
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                
                                <!-- Frame Portrait Preview Thumbnail (9:16) -->
                                <div class="sm:col-span-4 flex flex-col items-center">
                                    <div class="w-28 h-48 rounded-2xl border-4 border-slate-800 overflow-hidden shadow-md relative bg-slate-900 bg-cover bg-center transition-all" id="thumb-cover-box" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') ?>');">
                                        <!-- Overlay di Thumbnail -->
                                        <div class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all" id="thumb-cover-overlay" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>;"></div>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center text-white z-10 pointer-events-none">
                                            <span class="text-[8px] font-black uppercase tracking-wider text-amber-300">Preview</span>
                                            <span class="text-[7px] opacity-80 mt-0.5 leading-tight">Cover Portrait</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-1.5">Format Portrait (9:16)</span>
                                </div>

                                <!-- Kontrol Input & Upload -->
                                <div class="sm:col-span-8 space-y-3">
                                    
                                    <!-- Input URL Gambar -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Link URL Gambar Cover (Pinterest / Web / Unsplash):</label>
                                        <div class="relative">
                                            <input type="text" name="cover_bg_url" id="input-cover-bg-url" value="<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>" oninput="updateLiveBgUrl('cover', this.value)" placeholder="https://images.unsplash.com/... atau link gambar web" class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none bg-white">
                                            <i class="fas fa-link absolute left-2.5 top-3 text-slate-400 text-xs"></i>
                                        </div>
                                    </div>

                                    <!-- Upload File Gambar -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Atau Upload File Gambar Langsung (JPG, PNG, WEBP):</label>
                                        <label class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:border-teal-500 text-slate-700 font-bold text-xs flex items-center justify-between transition shadow-2xs group">
                                            <span class="flex items-center gap-2 text-slate-600 group-hover:text-teal-700 truncate">
                                                <i class="fas fa-cloud-arrow-up text-teal-600"></i>
                                                <span id="label-cover-file">Pilih file foto dari komputer/HP...</span>
                                            </span>
                                            <span class="text-[10px] bg-slate-100 group-hover:bg-teal-50 px-2 py-0.5 rounded text-slate-600 group-hover:text-teal-800">Browse</span>
                                            <input type="file" name="cover_bg_file" id="input-cover-bg-file" accept="image/*" class="hidden" onchange="previewBgFile(this, 'cover')">
                                        </label>
                                    </div>

                                    <!-- Slider Tingkat Kegelapan Lapis Cover -->
                                    <div class="pt-2 border-t border-slate-200/60">
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="text-[11px] font-bold text-slate-700">Tingkat Kegelapan Lapis Cover:</label>
                                            <span id="val-cover-opacity" class="text-xs font-black text-teal-800 font-mono"><?= round((float)($cfg['cover_overlay_opacity'] ?? 0.85) * 100) ?>%</span>
                                        </div>
                                        <input type="range" name="cover_overlay_opacity" id="input-cover-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>" oninput="updateLiveBgOpacity('cover', this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                        <span class="text-[10px] text-slate-400">Semakin tinggi (80-90%), tulisan di cover semakin kontras dan mudah dibaca.</span>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- 2. BACKGROUND LAMAN DALAM (WALLPAPER ISI) -->
                        <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                            <div class="flex items-center justify-between border-b border-slate-200/60 pb-2.5">
                                <span class="font-extrabold text-xs sm:text-sm text-slate-900 flex items-center gap-2">
                                    <i class="fas fa-file-invoice text-teal-600"></i> 2. Background Halaman Dalam (Isi Brosur)
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700 bg-teal-50 px-2 py-0.5 rounded-md border border-teal-200/60">
                                    Laman Isi
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                                
                                <!-- Frame Portrait Preview Thumbnail (9:16) -->
                                <div class="sm:col-span-4 flex flex-col items-center">
                                    <div class="w-28 h-48 rounded-2xl border-4 border-slate-800 overflow-hidden shadow-md relative bg-slate-100 bg-cover bg-center transition-all" id="thumb-body-box" style="background-image: url('<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>');">
                                        <!-- Overlay di Thumbnail -->
                                        <div class="absolute inset-0 bg-[#f6f7f5] transition-all" id="thumb-body-overlay" style="opacity: <?= $cfg['body_overlay_opacity'] ?? 0.92 ?>;"></div>
                                        <div class="absolute inset-0 flex flex-col items-center justify-center p-2 text-center text-slate-800 z-10 pointer-events-none">
                                            <span class="text-[8px] font-black uppercase tracking-wider text-teal-800">Preview</span>
                                            <span class="text-[7px] opacity-70 mt-0.5 leading-tight">Wallpaper Isi</span>
                                        </div>
                                    </div>
                                    <span class="text-[10px] text-slate-400 font-semibold mt-1.5">Format Portrait (9:16)</span>
                                </div>

                                <!-- Kontrol Input & Upload -->
                                <div class="sm:col-span-8 space-y-3">
                                    
                                    <!-- Input URL Gambar -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Link URL Wallpaper Halaman Dalam:</label>
                                        <div class="relative">
                                            <input type="text" name="body_bg_url" id="input-body-bg-url" value="<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>" oninput="updateLiveBgUrl('body', this.value)" placeholder="Kosongkan jika ingin putih polos atau masukkan URL" class="w-full pl-8 pr-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none bg-white">
                                            <i class="fas fa-link absolute left-2.5 top-3 text-slate-400 text-xs"></i>
                                        </div>
                                    </div>

                                    <!-- Upload File Gambar -->
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Atau Upload File Wallpaper (JPG, PNG, WEBP):</label>
                                        <label class="cursor-pointer px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:border-teal-500 text-slate-700 font-bold text-xs flex items-center justify-between transition shadow-2xs group">
                                            <span class="flex items-center gap-2 text-slate-600 group-hover:text-teal-700 truncate">
                                                <i class="fas fa-cloud-arrow-up text-teal-600"></i>
                                                <span id="label-body-file">Pilih file wallpaper...</span>
                                            </span>
                                            <span class="text-[10px] bg-slate-100 group-hover:bg-teal-50 px-2 py-0.5 rounded text-slate-600 group-hover:text-teal-800">Browse</span>
                                            <input type="file" name="body_bg_file" id="input-body-bg-file" accept="image/*" class="hidden" onchange="previewBgFile(this, 'body')">
                                        </label>
                                    </div>

                                    <!-- Slider Transparansi Lapis Terang Laman Dalam -->
                                    <div class="pt-2 border-t border-slate-200/60">
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="text-[11px] font-bold text-slate-700">Transparansi Lapis Terang Laman Dalam:</label>
                                            <span id="val-body-opacity" class="text-xs font-black text-teal-800 font-mono"><?= round((float)($cfg['body_overlay_opacity'] ?? 0.92) * 100) ?>%</span>
                                        </div>
                                        <input type="range" name="body_overlay_opacity" id="input-body-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['body_overlay_opacity'] ?? 0.92 ?>" oninput="updateLiveBgOpacity('body', this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                        <span class="text-[10px] text-slate-400">Nilai 90-95% membuat wallpaper tampil halus di balik konten teks brosur.</span>
                                    </div>

                                </div>

                            </div>
                        </div>

                        <!-- TOMBOL SIMPAN PENGATURAN BACKGROUND -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                            <button type="submit" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-[#0b8478] to-[#075f56] hover:from-[#097368] hover:to-[#054a43] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                <i class="fas fa-save text-base"></i>
                                <span>Simpan Pengaturan Background</span>
                            </button>
                        </div>

                    </div>

                </form>

            </div>

            <!-- PANEL KANAN: LAYAR SIMULASI SMARTPHONE -->
            <div class="lg:col-span-5 xl:col-span-5 sticky top-24 flex flex-col items-center">
                
                <!-- TOP CONTROLS: TAB SWITCHER & NAVIGATION SHORTCUTS -->
                <div class="w-full max-w-[350px] flex items-center justify-between gap-2 mb-3 px-1">
                    <div class="flex items-center gap-1 p-1 bg-white border border-slate-200 rounded-2xl shadow-xs">
                        <button type="button" id="tab-btn-cover" onclick="setPhoneTab('cover')" class="px-4 py-1.5 rounded-xl font-bold text-xs bg-[#0b8478] text-white shadow-sm transition flex items-center gap-1.5">
                            <i class="fas fa-envelope-open-text text-amber-300"></i>
                            <span>Cover Amplop</span>
                        </button>
                        <button type="button" id="tab-btn-body" onclick="setPhoneTab('body')" class="px-4 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                            <i class="fas fa-file-invoice text-teal-700"></i>
                            <span>Laman Dalam</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="location.reload()" title="Refresh Tampilan" class="w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-600 hover:text-[#0b8478] hover:border-teal-300 flex items-center justify-center text-xs shadow-xs transition active:scale-90">
                            <i class="fas fa-rotate"></i>
                        </button>
                    </div>
                </div>

                <!-- PHONE MOCKUP CONTAINER -->
                <div class="phone-mockup bg-slate-900 text-white flex flex-col relative" id="phone-container" style="font-family: '<?= htmlspecialchars($cfg['font_family'] ?? 'Plus Jakarta Sans') ?>', sans-serif;">
                    
                    <!-- Speaker & Camera -->
                    <div class="phone-speaker"></div>
                    <div class="phone-camera"></div>

                    <!-- 1. SCREEN VIEW: SIMULASI COVER AMPLOP -->
                    <div id="preview-screen-cover" class="relative w-full h-full p-4 text-center bg-cover bg-center overflow-hidden transition-all duration-500 select-none flex flex-col justify-between" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') ?>');">
                        
                        <!-- OVERLAY DINAMIS COVER -->
                        <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>;"></div>

                        <!-- BLOK 1: HEADER LOGO, BISMILLAH, JUDUL -->
                        <div id="preview-elem-header" class="relative z-20 text-center w-full px-2 pt-6">
                            
                            <!-- Bismillah -->
                            <p class="font-arabic text-sm transition-colors <?= (isset($cfg['show_bismillah']) && $cfg['show_bismillah'] == 0) ? 'hidden' : '' ?>" id="view-bismillah" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['bismillah_text'] ?? 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ') ?>
                            </p>
                            
                            <!-- Gambar Logo -->
                            <div id="view-logo-container" class="my-2 <?= (isset($cfg['show_logo']) && $cfg['show_logo'] == 0) ? 'hidden' : '' ?>">
                                <div id="preview-logo-wrapper" class="rounded-full p-0.5 bg-white border-2 border-emerald-600 shadow-md overflow-hidden aspect-square mx-auto flex items-center justify-center transition-all" style="width: <?= round(($cfg['logo_size'] ?? 80) * 0.6) ?>px; height: <?= round(($cfg['logo_size'] ?? 80) * 0.6) ?>px;">
                                    <img src="upload/logo-villa-quran.png" class="w-full h-full object-cover rounded-full pointer-events-none" onerror="this.src='https://via.placeholder.com/80?text=VQ'">
                                </div>
                            </div>

                            <!-- Judul Utama -->
                            <h3 class="font-black mt-1 transition-all leading-tight text-base" id="view-title" style="color: <?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>;">
                                <?= htmlspecialchars($cfg['judul_utama'] ?? 'Villa Quran Indonesia') ?>
                            </h3>

                            <!-- Subjudul -->
                            <p id="view-subjudul" class="text-[10px] font-medium transition-all mt-0.5 <?= (isset($cfg['show_subjudul']) && $cfg['show_subjudul'] == 0) ? 'hidden' : '' ?>" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['subjudul'] ?? 'Sekolah Tahfidz Berasrama Nyaman Ala Villa') ?>
                            </p>

                            <span id="preview-sub" class="inline-block text-[8px] font-bold uppercase tracking-wider mt-1 px-2.5 py-0.5 rounded-full bg-white/10 backdrop-blur-xs transition-colors" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?>
                            </span>
                        </div>

                        <!-- BLOK 2: KARTU TAMU CALON WALI -->
                        <div id="preview-elem-guest" class="relative z-20 border rounded-2xl backdrop-blur-md shadow-lg transition-all mx-auto w-full p-3.5 <?= (($cfg['card_bg_style'] ?? 'glass_dark') === 'glass_light') ? 'bg-white/85 border-emerald-600/40 text-slate-800' : 'bg-black/45 border-amber-400/40 text-white' ?>">
                            <span class="text-[8.5px] uppercase tracking-wider font-bold block" id="view-guest-sub" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['tamu_header_text'] ?? 'Kepada Yth. Calon Wali Santri:') ?>
                            </span>
                            <div class="text-xs font-black mt-0.5 transition-colors" id="view-guest-name">Bpk. Hendy Pratama</div>
                            
                            <!-- Teks Sambutan Tamu -->
                            <p class="text-[8.5px] mt-1 leading-relaxed opacity-90 transition-all <?= (isset($cfg['show_sambutan']) && $cfg['show_sambutan'] == 0) ? 'hidden' : '' ?>" id="view-tamu-sambutan">
                                <?= htmlspecialchars($cfg['tamu_sambutan_text'] ?? 'Undangan Mahabbah Silaturahmi & Brosur Informasi Pendidikan Putra-Putri Generasi Qur\'ani.') ?>
                            </p>

                            <div class="mt-2 pt-1.5 border-t border-white/15 text-[8px] font-semibold" id="preview-tahun-txt" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                Tahun Ajaran <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>
                            </div>
                        </div>

                        <!-- BLOK 3: TOMBOL BUKA UNDANGAN -->
                        <div id="preview-elem-btn" class="relative z-20 flex flex-col items-center pb-4 w-full">
                            <button type="button" onclick="setPhoneTab('body')" id="view-btn-preview" class="w-full py-3 rounded-xl font-black text-xs flex items-center justify-center gap-2 shadow-xl active:scale-95 transition-all cursor-pointer" style="background: <?= htmlspecialchars($cfg['btn_bg_color'] ?? '#d97706') ?>; color: <?= htmlspecialchars($cfg['btn_text_color'] ?? '#022d27') ?>;">
                                <i class="fas fa-envelope-open-text text-xs"></i>
                                <span id="view-btn-label"><?= htmlspecialchars($cfg['btn_text'] ?? 'Buka Brosur & Undangan') ?></span>
                            </button>
                            <p class="text-[8.5px] mt-1.5 text-center opacity-80" id="view-audio-note" style="color: <?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>;">
                                <i class="fas fa-music mr-1"></i> Dilengkapi Audio Backsound Syahdu
                            </p>
                        </div>
                    </div>

                    <!-- 2. SCREEN VIEW: SIMULASI HALAMAN DALAM DENGAN 9 MENU INTERAKTIF & BOTTOM BAR -->
                    <div id="preview-screen-body" class="hidden relative w-full h-full flex flex-col bg-cover bg-center transition-all duration-500 select-none text-slate-800 overflow-hidden" style="background-image: url('<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS HALAMAN DALAM -->
                        <div id="preview-body-overlay" class="absolute inset-0 bg-[#f6f7f5] transition-all duration-300" style="opacity: <?= $cfg['body_overlay_opacity'] ?? 0.92 ?>;"></div>

                        <!-- KONTEN AREA HALAMAN DALAM (SNAP SCROLLING 9 SECTION) -->
                        <div id="sim-scroll-body" class="relative z-10 flex-1 overflow-y-auto p-3.5 space-y-3.5 text-left scroll-smooth snap-y snap-mandatory no-scrollbar pt-6">
                            
                            <!-- 1. SECTION: HOME -->
                            <div id="sim-sec-home" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-emerald-100 shadow-sm text-center space-y-3">
                                <div class="w-14 h-14 rounded-full mx-auto p-0.5 bg-white border-2 border-emerald-600 shadow-sm overflow-hidden aspect-square flex items-center justify-center">
                                    <img src="upload/logo-villa-quran.png" class="w-full h-full object-cover rounded-full" onerror="this.src='https://via.placeholder.com/80?text=VQ'">
                                </div>
                                <div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[8.5px] font-black bg-emerald-100 text-emerald-900 uppercase tracking-wider mb-1">
                                        Hal 1 &bull; Profil Sekolah
                                    </span>
                                    <h4 class="font-black text-sm text-emerald-950 leading-tight" id="sim-view-title"><?= htmlspecialchars($cfg['judul_utama'] ?? 'Villa Quran Indonesia') ?></h4>
                                    <p class="text-[9.5px] text-emerald-700 font-medium mt-0.5" id="sim-view-subjudul"><?= htmlspecialchars($cfg['subjudul'] ?? 'Pesantren Tahfidz Berasrama Nyaman Ala Villa') ?></p>
                                </div>

                                <div class="p-2.5 rounded-xl bg-emerald-50/80 border border-emerald-200/60 text-[8.5px] text-emerald-900 leading-relaxed font-semibold">
                                    <i class="fas fa-certificate text-amber-500 mr-1"></i> TA <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?> &bull; <?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1') ?>
                                </div>

                                <p class="text-[8px] text-slate-400 italic">Geser ke bawah atau gunakan menu di bawah untuk berpindah halaman</p>
                            </div>

                            <!-- 2. SECTION: MENGAPA VQBM -->
                            <div id="sim-sec-mengapa" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-amber-100 shadow-sm space-y-2.5">
                                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                    <span class="w-7 h-7 rounded-lg bg-amber-500 text-white flex items-center justify-center text-xs shadow-xs">
                                        <i class="fas fa-heart"></i>
                                    </span>
                                    <div>
                                        <span class="text-[8px] uppercase font-bold text-amber-600">Hal 2</span>
                                        <h5 class="font-black text-xs text-slate-900 leading-tight">Mengapa Villa Quran?</h5>
                                    </div>
                                </div>
                                <div class="space-y-2 text-[8.5px]">
                                    <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-950">
                                        <strong>1. Tahfidz Mutqin 30 Juz Bersanad</strong>
                                        <p class="text-slate-600 text-[8px] mt-0.5">Talaqqi harian asatidz mukim dan sanad muttashil.</p>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-teal-50 border border-teal-100 text-teal-950">
                                        <strong>2. Ijazah Formal Resmi SMP/SMA</strong>
                                        <p class="text-slate-600 text-[8px] mt-0.5">Legalitas ijazah negara terakreditasi untuk PTN & kedinasan.</p>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-100 text-amber-950">
                                        <strong>3. Solopreneur & AI Terapan</strong>
                                        <p class="text-slate-600 text-[8px] mt-0.5">Literasi digital, prompt AI, dan kemandirian wirausaha.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. SECTION: TARGET KOMPETENSI -->
                            <div id="sim-sec-kompetensi" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-cyan-100 shadow-sm space-y-2.5">
                                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                                    <span class="w-7 h-7 rounded-lg bg-cyan-600 text-white flex items-center justify-center text-xs shadow-xs">
                                        <i class="fas fa-bullseye"></i>
                                    </span>
                                    <div>
                                        <span class="text-[8px] uppercase font-bold text-cyan-600">Hal 3</span>
                                        <h5 class="font-black text-xs text-slate-900 leading-tight">Target Kompetensi</h5>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 text-[8px]">
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-quran text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8.5px]">Hafal 30 Juz</strong>
                                        <span class="text-slate-600">Tahsin fashahah & sanad mutqin</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-graduation-cap text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8.5px]">Ijazah SMP/SMA</strong>
                                        <span class="text-slate-600">Kurikulum Diknas & Diniyah</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-comments text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8.5px]">Bahasa Aktif</strong>
                                        <span class="text-slate-600">Percakapan Arab & Inggris harian</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-laptop-code text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8.5px]">Solopreneur AI</strong>
                                        <span class="text-slate-600">Skill abad 21 & digital dakwah</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. SECTION: FASILITAS KAMPUS -->
                            <div id="sim-sec-fasilitas" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-teal-100 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-teal-600 text-white flex items-center justify-center text-xs shadow-xs">
                                            <i class="fas fa-hotel"></i>
                                        </span>
                                        <div>
                                            <span class="text-[8px] uppercase font-bold text-teal-600">Hal 4</span>
                                            <h5 class="font-black text-xs text-slate-900 leading-tight">Fasilitas Kampus</h5>
                                        </div>
                                    </div>
                                    <a href="admin-fasilitas.php" class="text-[8px] text-teal-700 underline font-bold">Edit Fasilitas &rarr;</a>
                                </div>

                                <div class="space-y-2 max-h-[310px] overflow-y-auto pr-0.5 no-scrollbar text-[8px]">
                                    <?php if (!empty($web_fasilitas)): ?>
                                        <?php foreach (array_slice($web_fasilitas, 0, 4) as $wf): ?>
                                            <div class="p-2 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2">
                                                <?php if (!empty($wf['gambar_url'])): ?>
                                                    <img src="<?= htmlspecialchars($wf['gambar_url']) ?>" class="w-10 h-10 rounded-lg object-cover flex-shrink-0" onerror="this.src='https://via.placeholder.com/80?text=Fasilitas'">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs flex-shrink-0"><i class="fas fa-building"></i></div>
                                                <?php endif; ?>
                                                <div class="overflow-hidden">
                                                    <strong class="text-slate-900 block truncate text-[8.5px]"><?= htmlspecialchars($wf['judul']) ?></strong>
                                                    <span class="text-slate-500 line-clamp-2"><?= htmlspecialchars($wf['deskripsi']) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-2.5 rounded-xl bg-teal-50 text-teal-900">Asrama AC, Masjid Jami', Kolam Renang, Resto Santri.</div>
                                    <?php endif; ?>
                                </div>

                                <div class="rounded-xl p-2 bg-emerald-50 border border-emerald-200 text-center text-[8px]">
                                    <span class="font-bold text-emerald-900"><i class="fas fa-map-marker-alt text-emerald-600 mr-1"></i> Google Maps Lokasi Kampus</span>
                                </div>
                            </div>

                            <!-- 5. SECTION: DEWAN PENGASUH -->
                            <div id="sim-sec-pengasuh" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-purple-100 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-purple-600 text-white flex items-center justify-center text-xs shadow-xs">
                                            <i class="fas fa-user-graduate"></i>
                                        </span>
                                        <div>
                                            <span class="text-[8px] uppercase font-bold text-purple-600">Hal 5</span>
                                            <h5 class="font-black text-xs text-slate-900 leading-tight">Dewan Pengasuh</h5>
                                        </div>
                                    </div>
                                    <a href="admin-pengajar.php" class="text-[8px] text-purple-700 underline font-bold">Edit Pengajar &rarr;</a>
                                </div>

                                <div class="space-y-2 max-h-[310px] overflow-y-auto pr-0.5 no-scrollbar text-[8px]">
                                    <?php if (!empty($web_pengajar)): ?>
                                        <?php foreach (array_slice($web_pengajar, 0, 3) as $wp): ?>
                                            <div class="p-2 rounded-xl bg-purple-50/60 border border-purple-100 flex items-center gap-2">
                                                <?php if (!empty($wp['gambar_url'])): ?>
                                                    <img src="<?= htmlspecialchars($wp['gambar_url']) ?>" class="w-10 h-10 rounded-full object-cover border border-purple-300 flex-shrink-0" onerror="this.src='https://via.placeholder.com/80?text=Ustadz'">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-full bg-purple-200 text-purple-800 flex items-center justify-center text-xs flex-shrink-0 font-bold"><i class="fas fa-user"></i></div>
                                                <?php endif; ?>
                                                <div class="overflow-hidden">
                                                    <strong class="text-slate-900 block truncate text-[8.5px]"><?= htmlspecialchars($wp['nama']) ?></strong>
                                                    <span class="text-purple-700 font-bold block text-[7.5px] truncate"><?= htmlspecialchars($wp['jabatan'] ?? '') ?></span>
                                                    <span class="text-slate-500 line-clamp-1"><?= htmlspecialchars($wp['almamater'] ?? '') ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-2.5 rounded-xl bg-purple-50 text-purple-900">Dr. KH. Pembina Tahfidz, Lc. MA & Dewan Asatidz Mukim 24 Jam.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 6. SECTION: KEGIATAN SANTRI -->
                            <div id="sim-sec-kegiatan" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-pink-100 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-pink-600 text-white flex items-center justify-center text-xs shadow-xs">
                                            <i class="fas fa-camera-retro"></i>
                                        </span>
                                        <div>
                                            <span class="text-[8px] uppercase font-bold text-pink-600">Hal 6</span>
                                            <h5 class="font-black text-xs text-slate-900 leading-tight">Kegiatan Santri</h5>
                                        </div>
                                    </div>
                                    <a href="admin-galeri.php" class="text-[8px] text-pink-700 underline font-bold">Edit Galeri &rarr;</a>
                                </div>

                                <div class="grid grid-cols-2 gap-2 text-[8px] max-h-[310px] overflow-y-auto no-scrollbar">
                                    <?php if (!empty($web_galeri)): ?>
                                        <?php foreach (array_slice($web_galeri, 0, 4) as $wg): ?>
                                            <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-2xs">
                                                <img src="<?= htmlspecialchars($wg['gambar_url'] ?? '') ?>" class="w-full h-16 object-cover" onerror="this.src='https://via.placeholder.com/150?text=Kegiatan'">
                                                <div class="p-1">
                                                    <strong class="block truncate text-[8px] text-slate-900"><?= htmlspecialchars($wg['judul'] ?? '') ?></strong>
                                                    <span class="text-slate-500 text-[7px] line-clamp-1"><?= htmlspecialchars($wg['caption'] ?? '') ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-span-2 p-2.5 rounded-xl bg-pink-50 text-pink-900">Halaqah Tahfidz, KBM Kelas, Olahraga Sunnah, Shalat Berjamaah.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 7. SECTION: INVESTASI PENDIDIKAN -->
                            <div id="sim-sec-investasi" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-amber-200 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-amber-600 text-white flex items-center justify-center text-xs shadow-xs">
                                            <i class="fas fa-receipt"></i>
                                        </span>
                                        <div>
                                            <span class="text-[8px] uppercase font-bold text-amber-600">Hal 7</span>
                                            <h5 class="font-black text-xs text-slate-900 leading-tight">Investasi Pendidikan</h5>
                                        </div>
                                    </div>
                                    <a href="admin-biaya.php" class="text-[8px] text-amber-700 underline font-bold">Edit Biaya &rarr;</a>
                                </div>

                                <div class="space-y-1.5 text-[8px]">
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-500">1. Pendaftaran:</span>
                                        <span class="font-bold text-slate-800">Rp <?= number_format($biaya_pendaftaran_val, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-500">2. Uang Pangkal:</span>
                                        <span class="font-bold text-emerald-700">Rp <?= number_format($biaya_pangkal_val, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-500">3. Biaya Tahunan:</span>
                                        <span class="font-bold text-slate-800">Rp <?= number_format($biaya_tahunan_val, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1.5 bg-amber-50 rounded-lg px-2">
                                        <span class="font-bold text-amber-950">4. SPP Bulanan (All-in):</span>
                                        <span class="font-black text-amber-700">Rp <?= number_format($biaya_spp_val, 0, ',', '.') ?>/bln</span>
                                    </div>
                                    <div class="p-2 bg-amber-50/90 rounded-lg border border-amber-200 text-amber-900 flex items-center justify-between text-[7.5px]">
                                        <span><i class="fas fa-gift text-amber-600 mr-1"></i> Diskon <?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1') ?>:</span>
                                        <strong>Hemat Rp <?= number_format($cfg['diskon_gelombang'] ?? 2000000, 0, ',', '.') ?></strong>
                                    </div>
                                </div>

                                <!-- Countdown Widget Mini -->
                                <div id="preview-countdown-box" class="p-2 rounded-xl bg-emerald-900 text-white text-center">
                                    <span class="text-[7.5px] text-amber-300 font-bold block mb-0.5">⏳ Sisa Waktu Pendaftaran Gelombang</span>
                                    <div class="flex justify-center items-center gap-1 font-mono text-[9.5px] font-black text-amber-300">
                                        <span id="phone-cd-hari">00</span>h : <span id="phone-cd-jam">00</span>j : <span id="phone-cd-menit">00</span>m : <span id="phone-cd-detik" class="text-rose-400">00</span>s
                                    </div>
                                </div>
                            </div>

                            <!-- 8. SECTION: TESTIMONI -->
                            <div id="sim-sec-testimoni" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-white/95 border border-teal-100 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                    <div class="flex items-center gap-2">
                                        <span class="w-7 h-7 rounded-lg bg-teal-600 text-white flex items-center justify-center text-xs shadow-xs">
                                            <i class="fas fa-comments"></i>
                                        </span>
                                        <div>
                                            <span class="text-[8px] uppercase font-bold text-teal-600">Hal 8</span>
                                            <h5 class="font-black text-xs text-slate-900 leading-tight">Testimoni Walisantri</h5>
                                        </div>
                                    </div>
                                    <a href="admin-testimoni.php" class="text-[8px] text-teal-700 underline font-bold">Edit Testimoni &rarr;</a>
                                </div>

                                <div class="space-y-2 max-h-[310px] overflow-y-auto no-scrollbar text-[8px]">
                                    <?php if (!empty($web_testimoni)): ?>
                                        <?php foreach (array_slice($web_testimoni, 0, 2) as $wt): ?>
                                            <div class="p-2 rounded-xl bg-teal-50/60 border border-teal-100">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <strong class="text-emerald-950 block text-[8.5px]"><?= htmlspecialchars($wt['nama']) ?></strong>
                                                    <span class="text-amber-500 text-[7px]"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span>
                                                </div>
                                                <p class="text-slate-600 italic line-clamp-3 leading-relaxed">"<?= htmlspecialchars($wt['isi_testimoni']) ?>"</p>
                                                <span class="text-[7px] text-slate-400 font-bold block mt-0.5"><?= htmlspecialchars($wt['jabatan'] ?? '') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-2.5 rounded-xl bg-teal-50 text-teal-900 italic">"Anak kami betah sekali dan hafalan juz 30 hingga 5 sangat mutqin." — Bpk. Hendrawan</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 9. SECTION: FORMULIR -->
                            <div id="sim-sec-formulir" class="min-h-[500px] snap-start flex flex-col justify-center p-4 rounded-2xl bg-gradient-to-br from-amber-50 via-white to-emerald-50 border border-amber-300 shadow-sm space-y-2.5 text-slate-800">
                                <div class="flex items-center gap-2 border-b border-amber-200/80 pb-2">
                                    <span class="w-7 h-7 rounded-lg bg-amber-500 text-emerald-950 flex items-center justify-center text-xs shadow-xs font-black">
                                        <i class="fas fa-file-pen"></i>
                                    </span>
                                    <div>
                                        <span class="text-[8px] uppercase font-bold text-amber-700">Hal 9</span>
                                        <h5 class="font-black text-xs text-slate-900 leading-tight">Formulir Pendaftaran</h5>
                                    </div>
                                </div>

                                <div class="space-y-2 text-[8px]">
                                    <input type="text" placeholder="Nama Orang Tua..." disabled class="w-full p-2 bg-white rounded-lg border border-slate-200 text-slate-400">
                                    <input type="text" placeholder="Nomor WhatsApp..." disabled class="w-full p-2 bg-white rounded-lg border border-slate-200 text-slate-400">
                                    <input type="text" placeholder="Nama Calon Santri..." disabled class="w-full p-2 bg-white rounded-lg border border-slate-200 text-slate-400">
                                    <div class="p-2.5 bg-gradient-to-r from-amber-400 to-amber-600 text-emerald-950 font-black text-center rounded-xl shadow-xs">
                                        Kirim Reservasi Kuota Santri
                                    </div>
                                </div>

                                <div class="text-center pt-2">
                                    <button type="button" onclick="setPhoneTab('cover')" class="text-[9px] text-emerald-700 font-bold underline hover:text-emerald-900 transition">
                                        &larr; Kembali ke Cover Amplop
                                    </button>
                                </div>
                            </div>

                        </div>

                        <!-- BOTTOM BAR SIMULASI: 9 MENU TANPA FRAME KOTAK -->
                        <div id="sim-bottom-bar" class="relative z-30 shadow-2xl p-1 pb-2 rounded-b-[34px] border-t border-white/10 backdrop-blur-md transition-colors duration-300" style="background-color: <?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?>; color: <?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>;">
                            
                            <!-- CAROUSEL TRACK -->
                            <div id="sim-bottom-track" class="flex items-center overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory gap-1 px-1 py-1">
                                
                                <button type="button" onclick="navigasiSimulasi('sim-sec-home', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 text-amber-300">
                                    <i class="fas fa-house text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Home</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-mengapa', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-heart text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Mengapa</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-kompetensi', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-bullseye text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Kompetensi</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-fasilitas', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-hotel text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Fasilitas</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-pengasuh', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-user-graduate text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Pengasuh</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-kegiatan', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-camera-retro text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Kegiatan</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-investasi', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-receipt text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Investasi</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-testimoni', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-comments text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Testimoni</span>
                                </button>

                                <button type="button" onclick="navigasiSimulasi('sim-sec-formulir', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-file-pen text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Formulir</span>
                                </button>

                            </div>

                            <!-- CONTROLS GESER KANAN KIRI & INDIKATOR -->
                            <div class="flex items-center justify-between px-2 pt-1 text-[8px] border-t border-white/10 mt-0.5 opacity-80">
                                <button type="button" onclick="scrollSimulasiBottomBar('left')" class="hover:opacity-100 font-bold flex items-center gap-1 transition p-0.5">
                                    <i class="fas fa-chevron-left text-[7px]"></i> <span>Geser Kiri</span>
                                </button>
                                
                                <div class="flex items-center gap-1" id="sim-indicator-dots">
                                    <span class="w-2.5 h-1 rounded-full bg-amber-400 transition-all" id="sim-dot-1"></span>
                                    <span class="w-1.5 h-1 rounded-full bg-white/40 transition-all" id="sim-dot-2"></span>
                                    <span class="w-1.5 h-1 rounded-full bg-white/40 transition-all" id="sim-dot-3"></span>
                                </div>

                                <button type="button" onclick="scrollSimulasiBottomBar('right')" class="hover:opacity-100 font-bold flex items-center gap-1 transition p-0.5">
                                    <span>Geser Kanan</span> <i class="fas fa-chevron-right text-[7px]"></i>
                                </button>
                            </div>

                        </div>

                    </div>
                </div>

                <!-- FOOTER INFO BADGE -->
                <div class="mt-4 flex items-center gap-2 text-xs text-slate-500 bg-white/80 backdrop-blur-xs px-4 py-2 rounded-full border border-slate-200 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Tersinkronisasi otomatis dengan data Fasilitas, Pengajar, Galeri, Biaya & Testimoni</span>
                </div>

            </div>

        </div>

    </main>

    <!-- SCRIPT INTERAKTIF SIMULASI -->
    <script>
        function setPhoneTab(tab) {
            const coverScreen = document.getElementById('preview-screen-cover');
            const bodyScreen  = document.getElementById('preview-screen-body');
            const btnCover    = document.getElementById('tab-btn-cover');
            const btnBody     = document.getElementById('tab-btn-body');

            if (tab === 'cover') {
                coverScreen.classList.remove('hidden');
                bodyScreen.classList.add('hidden');
                btnCover.className = 'px-4 py-1.5 rounded-xl font-bold text-xs bg-[#0b8478] text-white shadow-sm transition flex items-center gap-1.5';
                btnBody.className  = 'px-4 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5';
            } else {
                coverScreen.classList.add('hidden');
                bodyScreen.classList.remove('hidden');
                btnCover.className = 'px-4 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5';
                btnBody.className  = 'px-4 py-1.5 rounded-xl font-bold text-xs bg-[#0b8478] text-white shadow-sm transition flex items-center gap-1.5';
            }
        }

        // 1. Live Background File Preview (Cover & Body)
        function previewBgFile(input, target) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const label = document.getElementById(`label-${target}-file`);
                if (label) label.innerText = file.name;

                const reader = new FileReader();
                reader.onload = function(e) {
                    const dataUrl = e.target.result;
                    if (target === 'cover') {
                        document.getElementById('thumb-cover-box').style.backgroundImage = `url('${dataUrl}')`;
                        document.getElementById('preview-screen-cover').style.backgroundImage = `url('${dataUrl}')`;
                        setPhoneTab('cover');
                    } else {
                        document.getElementById('thumb-body-box').style.backgroundImage = `url('${dataUrl}')`;
                        document.getElementById('preview-screen-body').style.backgroundImage = `url('${dataUrl}')`;
                        setPhoneTab('body');
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        // 2. Live Background URL Input (Cover & Body)
        function updateLiveBgUrl(target, url) {
            const trimmed = url.trim();
            if (target === 'cover') {
                const finalUrl = trimmed ? trimmed : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80';
                document.getElementById('thumb-cover-box').style.backgroundImage = `url('${finalUrl}')`;
                document.getElementById('preview-screen-cover').style.backgroundImage = `url('${finalUrl}')`;
                setPhoneTab('cover');
            } else {
                const finalUrl = trimmed ? `url('${trimmed}')` : 'none';
                document.getElementById('thumb-body-box').style.backgroundImage = finalUrl;
                document.getElementById('preview-screen-body').style.backgroundImage = finalUrl;
                setPhoneTab('body');
            }
        }

        // 3. Live Background Opacity Slider (Cover & Body)
        function updateLiveBgOpacity(target, val) {
            const pct = Math.round(val * 100);
            if (target === 'cover') {
                document.getElementById('val-cover-opacity').innerText = pct + '%';
                document.getElementById('thumb-cover-overlay').style.opacity = val;
                document.getElementById('preview-cover-overlay').style.opacity = val;
            } else {
                document.getElementById('val-body-opacity').innerText = pct + '%';
                document.getElementById('thumb-body-overlay').style.opacity = val;
                document.getElementById('preview-body-overlay').style.opacity = val;
            }
        }

        // Navigasi & Kontrol Bottom Bar Simulasi Smartphone
        function navigasiSimulasi(targetSecId, btnElem) {
            const target = document.getElementById(targetSecId);
            const scrollContainer = document.getElementById('sim-scroll-body');
            if (target && scrollContainer) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (btnElem) {
                document.querySelectorAll('.sim-card-menu-item').forEach(b => {
                    b.classList.remove('ring-2', 'ring-emerald-500');
                });
                btnElem.classList.add('ring-2', 'ring-emerald-500');
            }
        }

        function scrollSimulasiBottomBar(direction) {
            const track = document.getElementById('sim-bottom-track');
            if (!track) return;
            const scrollAmount = track.clientWidth * 0.9;
            if (direction === 'left') {
                track.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            } else {
                track.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            }
            setTimeout(updateSimDots, 350);
        }

        function updateSimDots() {
            const track = document.getElementById('sim-bottom-track');
            const dot1 = document.getElementById('sim-dot-1');
            const dot2 = document.getElementById('sim-dot-2');
            const dot3 = document.getElementById('sim-dot-3');
            if (!track || !dot1 || !dot2) return;
            const scrollLeft = track.scrollLeft;
            const maxScroll = track.scrollWidth - track.clientWidth;
            if (maxScroll <= 0) return;
            const ratio = scrollLeft / maxScroll;
            
            [dot1, dot2, dot3].forEach(d => { if (d) d.className = 'w-1.5 h-1 rounded-full bg-white/40 transition-all'; });
            if (ratio < 0.35) {
                if (dot1) dot1.className = 'w-2.5 h-1 rounded-full bg-amber-400 transition-all';
            } else if (ratio < 0.7) {
                if (dot2) dot2.className = 'w-2.5 h-1 rounded-full bg-amber-400 transition-all';
            } else {
                if (dot3) dot3.className = 'w-2.5 h-1 rounded-full bg-amber-400 transition-all';
            }
        }

        // Countdown Timer Logic
        function updateAdminCountdownWidget() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth() + 1;
            
            let endDate;
            if (month >= 7 && month <= 12) {
                endDate = new Date(year, 11, 31, 23, 59, 59);
            } else if (month >= 1 && month <= 3) {
                endDate = new Date(year, 2, 31, 23, 59, 59);
            } else {
                endDate = new Date(year, 5, 30, 23, 59, 59);
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

        // Mouse swipe on bottom bar track
        (function initBottomBarMouseSwipe() {
            const track = document.getElementById('sim-bottom-track');
            if (!track) return;
            track.addEventListener('scroll', updateSimDots, { passive: true });
            
            let isDown = false;
            let startX, scrollLeft;
            track.addEventListener('mousedown', (e) => {
                isDown = true;
                startX = e.pageX - track.offsetLeft;
                scrollLeft = track.scrollLeft;
            });
            track.addEventListener('mouseleave', () => { isDown = false; });
            track.addEventListener('mouseup', () => { isDown = false; });
            track.addEventListener('mousemove', (e) => {
                if (!isDown) return;
                e.preventDefault();
                const x = e.pageX - track.offsetLeft;
                const walk = (x - startX) * 1.5;
                track.scrollLeft = scrollLeft - walk;
            });
        })();
    </script>
</body>
</html>
