<?php
// admin-brosur-settings.php
// Panel Pengaturan Brosur PSB Digital, Background Pinterest & Countdown Timer
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Self-healing migration jika kolom baru belum ada di database lama
$cols_needed = [
    "body_bg_url" => "ALTER TABLE pengaturan_brosur ADD COLUMN body_bg_url TEXT AFTER cover_overlay_opacity",
    "body_overlay_opacity" => "ALTER TABLE pengaturan_brosur ADD COLUMN body_overlay_opacity DECIMAL(3,2) DEFAULT 0.92 AFTER body_bg_url",
    "countdown_mode" => "ALTER TABLE pengaturan_brosur ADD COLUMN countdown_mode VARCHAR(20) DEFAULT 'auto' AFTER diskon_gelombang",
    "countdown_target" => "ALTER TABLE pengaturan_brosur ADD COLUMN countdown_target DATETIME DEFAULT '2026-12-31 23:59:59' AFTER countdown_mode",
    "countdown_title" => "ALTER TABLE pengaturan_brosur ADD COLUMN countdown_title VARCHAR(150) DEFAULT '⏳ Sisa Waktu Pendaftaran Berakhir:' AFTER countdown_target",
    "show_countdown" => "ALTER TABLE pengaturan_brosur ADD COLUMN show_countdown TINYINT(1) DEFAULT 1 AFTER countdown_title"
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

$conn->query("INSERT IGNORE INTO pengaturan_brosur (id, tahun_ajaran, periode_gelombang, kuota_santri, cover_bg_url, cover_overlay_opacity, body_bg_url, body_overlay_opacity, theme_preset, music_url, biaya_pendaftaran, biaya_pangkal, biaya_tahunan, biaya_spp, diskon_gelombang, countdown_mode, countdown_target, countdown_title, show_countdown)
VALUES (1, '2026/2027', 'Gelombang 1 — Kuota Terbatas', 20, 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80', 0.85, 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1200&auto=format&fit=crop&q=80', 0.92, 'madinah', 'upload/backsound.mp3', 350000, 12500000, 2500000, 1650000, 2000000, 'auto', '2026-12-31 23:59:59', '⏳ Sisa Waktu Pendaftaran Berakhir:', 1)");

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
    // Format datetime-local jika perlu
    $countdown_target     = $conn->real_escape_string(str_replace('T', ' ', $countdown_target_raw));
    if (strlen($countdown_target) == 16) $countdown_target .= ':00';
    $countdown_title      = $conn->real_escape_string($_POST['countdown_title'] ?? '⏳ Sisa Waktu Pendaftaran Berakhir:');
    $show_countdown       = isset($_POST['show_countdown']) ? 1 : 0;

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
                    show_countdown = $show_countdown
                   WHERE id = 1";

    if ($conn->query($sql_update)) {
        $pesan_sukses = "Alhamdulillah! Pengaturan Brosur PSB, Background Laman Dalam & Countdown berhasil disimpan.";
    } else {
        $pesan_error = "Gagal menyimpan: " . $conn->error;
    }
}

// Ambil Data Terkini
$q = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg = $q ? $q->fetch_assoc() : [];

$active_menu = 'brosur_settings';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Brosur PSB Digital & Background | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Amiri:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .font-arabic { font-family: 'Amiri', serif; }
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
                        <i class="fas fa-sliders mr-1"></i> Brosur PSB Digital Engine
                    </span>
                    <span class="text-xs text-slate-400">Model Undangan Digital & Countdown</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">Pengaturan Brosur & Background Gambar</h1>
                <p class="text-xs sm:text-sm text-slate-500">Ganti gambar cover & background halaman dalam (Pinterest URL), aktifkan countdown SPMB yang sinkron dengan web sekolah, serta kelola biaya secara instan.</p>
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
                
                <form method="POST" id="form-pengaturan-brosur" class="space-y-6">
                    
                    <!-- ============================================================ -->
                    <!-- KARTU 1: BACKGROUND COVER AMPLOP (PINTEREST / UNSPLASH)      -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                    <i class="fab fa-pinterest"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">1. Background Cover Amplop (URL Pinterest)</h2>
                                    <p class="text-xs text-slate-500">Tampilan saat pertama kali calon wali membuka link sebelum menekan tombol buka</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                <i class="fas fa-envelope-open-text mr-1"></i> Opening Cover
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Direct URL Gambar Background Cover:</label>
                            <div class="flex gap-2">
                                <input type="url" name="cover_bg_url" id="input-cover-bg-url" value="<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>" placeholder="https://i.pinimg.com/... atau https://images.unsplash.com/..." oninput="updateLivePreview()" required class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#0b8478] focus:ring-1 focus:ring-[#0b8478] bg-slate-50 font-mono">
                                <button type="button" onclick="setPhoneTab('cover'); updateLivePreview();" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5">
                                    <i class="fas fa-eye text-amber-600"></i> Tes
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                <i class="fas fa-info-circle text-amber-500 mr-1"></i> Cara ambil link Pinterest: Buka gambar di Pinterest &rarr; Klik Kanan &rarr; <strong>"Salin Alamat Gambar" (Copy Image Address)</strong> lalu tempel di sini.
                            </p>
                        </div>

                        <!-- PRESET CEPAT PILIHAN (COVER) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Pilihan Cepat Gambar Cover Siap Pakai (1-Klik):</label>
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

                        <!-- SLIDER KEGELAPAN OVERLAY COVER -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-bold text-slate-700">Tingkat Kegelapan Overlay Cover Amplop:</label>
                                <span id="cover-opacity-val" class="text-xs font-extrabold text-emerald-800"><?= round((float)($cfg['cover_overlay_opacity'] ?? 0.85) * 100) ?>%</span>
                            </div>
                            <input type="range" name="cover_overlay_opacity" id="input-cover-opacity" min="0.30" max="0.95" step="0.05" value="<?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>" oninput="updateLiveCoverOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">Geser ke kanan agar teks amplop tetap sangat terbaca jelas dan mewah di atas gambar apapun.</p>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 2: BACKGROUND HALAMAN DALAM (INNER PAGE BACKGROUND)     -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg">
                                    <i class="fas fa-file-invoice"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">2. Background Halaman Dalam Brosur (URL Pinterest)</h2>
                                    <p class="text-xs text-slate-500">Latar belakang seluruh konten brosur (kompetensi, fasilitas, kurikulum & formulir)</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                <i class="fas fa-layer-group mr-1"></i> Inner Wallpaper
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Direct URL Gambar / Tekstur Laman Dalam:</label>
                            <div class="flex gap-2">
                                <input type="url" name="body_bg_url" id="input-body-bg-url" value="<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>" placeholder="https://i.pinimg.com/... (pola wallpaper, tekstur marmer atau pemandangan)" oninput="updateLiveBodyBg()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#0b8478] focus:ring-1 focus:ring-[#0b8478] bg-slate-50 font-mono">
                                <button type="button" onclick="setPhoneTab('body'); updateLiveBodyBg();" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5">
                                    <i class="fas fa-eye text-teal-700"></i> Tes
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                <i class="fas fa-lightbulb text-amber-500 mr-1"></i> Rekomendasi Pinterest: cari keyword <em>"Islamic geometric texture"</em>, <em>"Cream marble wallpaper"</em>, atau <em>"Pine forest morning aesthetic"</em>.
                            </p>
                        </div>

                        <!-- PRESET CEPAT PILIHAN (INNER BODY) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Pilihan Cepat Background Laman Dalam (1-Klik):</label>
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

                        <!-- SLIDER KEGELAPAN OVERLAY HALAMAN DALAM -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-bold text-slate-700">Transparansi Lapis Terang Laman Dalam (Overlay):</label>
                                <span id="body-opacity-val" class="text-xs font-extrabold text-teal-800"><?= round((float)($cfg['body_overlay_opacity'] ?? 0.92) * 100) ?>%</span>
                            </div>
                            <input type="range" name="body_overlay_opacity" id="input-body-opacity" min="0.70" max="0.98" step="0.02" value="<?= $cfg['body_overlay_opacity'] ?? 0.92 ?>" oninput="updateLiveBodyOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">Disarankan 88% - 94% agar tekstur tetap terlihat anggun di balik kartu brosur tanpa mengganggu keterbacaan teks.</p>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 3: PENGATURAN COUNTDOWN TIMER (SAMA DENGAN WEB SEKOLAH) -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                                    <i class="fas fa-stopwatch"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">3. Pengaturan Countdown Timer SPMB</h2>
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
                                            Sama persis dengan beranda web sekolah. Menghitung otomatis: <strong>Gelombang 1</strong> (s/d 31 Des), <strong>Gelombang 2</strong> (s/d 31 Mar), <strong>Gelombang 3</strong> (s/d 30 Jun).
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
                                            Tentukan tanggal & jam batas penutupan khusus secara manual (misal: perpanjangan promo atau early bird).
                                        </span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- INPUT TANGGAL KUSTOM (HANYA AKTIF JIKA MODE CUSTOM) -->
                        <div id="custom-target-group" class="<?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? '' : 'hidden' ?> bg-amber-50/70 p-4 rounded-2xl border border-amber-200">
                            <label class="block text-xs font-bold text-amber-900 mb-1">Pilih Tanggal & Waktu Batas Akhir:</label>
                            <input type="datetime-local" name="countdown_target" id="input-countdown-target" value="<?= date('Y-m-d\TH:i', strtotime($cfg['countdown_target'] ?? '2026-12-31 23:59:59')) ?>" oninput="updateAdminCountdownWidget()" class="w-full sm:w-72 px-4 py-2 rounded-xl border border-amber-300 bg-white text-xs font-mono font-bold text-slate-800">
                            <p class="text-[10px] text-amber-800 mt-1">Timer di brosur akan menghitung mundur menuju tanggal & jam yang Anda tentukan di atas.</p>
                        </div>

                        <!-- JUDUL COUNTDOWN -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Judul / Teks Countdown:</label>
                            <input type="text" name="countdown_title" id="input-countdown-title" value="<?= htmlspecialchars($cfg['countdown_title'] ?? '⏳ Sisa Waktu Pendaftaran Berakhir:') ?>" oninput="updateAdminCountdownWidget()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            <p class="text-[10px] text-slate-400 mt-1">Pada mode otomatis, sistem akan melengkapi nama gelombang secara cerdas.</p>
                        </div>

                        <!-- LIVE ADMIN COUNTDOWN WIDGET -->
                        <div class="bg-gradient-to-br from-emerald-950 via-[#043d35] to-emerald-950 p-4 rounded-2xl text-center text-white border border-amber-500/30 shadow-inner">
                            <span class="text-[10px] font-extrabold text-amber-300 uppercase tracking-widest block mb-1">Simulasi Countdown Berjalan Live:</span>
                            <div class="text-xs font-bold text-emerald-100 mb-2" id="adm-cd-text">⏳ Sisa Waktu Pendaftaran Gelombang 1 Berakhir:</div>
                            
                            <div class="flex justify-center items-center space-x-2">
                                <div class="flex flex-col items-center">
                                    <div class="bg-white text-emerald-950 font-black text-lg w-10 h-10 flex items-center justify-center rounded-xl shadow font-mono" id="adm-cd-hari">00</div>
                                    <span class="text-[8px] text-emerald-200 mt-1 uppercase font-bold tracking-widest">Hari</span>
                                </div>
                                <div class="text-white font-bold text-sm -mt-3">:</div>
                                <div class="flex flex-col items-center">
                                    <div class="bg-white text-emerald-950 font-black text-lg w-10 h-10 flex items-center justify-center rounded-xl shadow font-mono" id="adm-cd-jam">00</div>
                                    <span class="text-[8px] text-emerald-200 mt-1 uppercase font-bold tracking-widest">Jam</span>
                                </div>
                                <div class="text-white font-bold text-sm -mt-3">:</div>
                                <div class="flex flex-col items-center">
                                    <div class="bg-white text-emerald-950 font-black text-lg w-10 h-10 flex items-center justify-center rounded-xl shadow font-mono" id="adm-cd-menit">00</div>
                                    <span class="text-[8px] text-emerald-200 mt-1 uppercase font-bold tracking-widest">Menit</span>
                                </div>
                                <div class="text-white font-bold text-sm -mt-3">:</div>
                                <div class="flex flex-col items-center">
                                    <div class="bg-white text-rose-600 font-black text-lg w-10 h-10 flex items-center justify-center rounded-xl shadow font-mono" id="adm-cd-detik">00</div>
                                    <span class="text-[8px] text-rose-300 mt-1 uppercase font-bold tracking-widest">Detik</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 4: INFORMASI TAHUN AJARAN & KUOTA SANTRI               -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-base text-slate-900">4. Periode SPMB & Tahun Ajaran</h2>
                                <p class="text-xs text-slate-500">Ubah tahun ajaran dan nama gelombang yang aktif</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran:</label>
                                <input type="text" name="tahun_ajaran" id="input-tahun" value="<?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>" oninput="updateLiveText()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Gelombang:</label>
                                <input type="text" name="periode_gelombang" id="input-gelombang" value="<?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?>" oninput="updateLiveText()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kuota Santri Dibuka:</label>
                                <input type="number" name="kuota_santri" value="<?= $cfg['kuota_santri'] ?? 20 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 5: RINCIAN INVESTASI PENDIDIKAN (BIAYA)                -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-base text-slate-900">5. Rincian Investasi Pendidikan (Biaya)</h2>
                                <p class="text-xs text-slate-500">Angka ini otomatis tampil di tabel biaya brosur digital</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">1. Biaya Pendaftaran & Observasi (Rp):</label>
                                <input type="number" name="biaya_pendaftaran" value="<?= $cfg['biaya_pendaftaran'] ?? 350000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">2. Uang Pangkal / Sarana Masuk (Rp):</label>
                                <input type="number" name="biaya_pangkal" value="<?= $cfg['biaya_pangkal'] ?? 12500000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">3. Biaya Tahunan (Rp):</label>
                                <input type="number" name="biaya_tahunan" value="<?= $cfg['biaya_tahunan'] ?? 2500000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">4. SPP All-in Bulanan (Rp):</label>
                                <input type="number" name="biaya_spp" value="<?= $cfg['biaya_spp'] ?? 1650000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="block text-xs font-bold text-amber-800 mb-1">Potongan / Diskon Khusus Gelombang (Rp):</label>
                            <input type="number" name="diskon_gelombang" value="<?= $cfg['diskon_gelombang'] ?? 2000000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-amber-300 bg-amber-50 text-xs sm:text-sm focus:border-amber-500 focus:outline-none font-bold text-amber-900">
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <div class="sticky bottom-4 z-20">
                        <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-[#0b8478] hover:bg-[#075f56] text-white font-black text-sm sm:text-base shadow-xl flex items-center justify-center gap-2 transform active:scale-95 transition">
                            <i class="fas fa-save text-lg"></i>
                            <span>Simpan Seluruh Pengaturan Brosur & Countdown</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- SIMULASI SMARTPHONE LIVE PREVIEW (4 KOLOM) -->
            <div class="lg:col-span-5 xl:col-span-4 sticky top-6 flex flex-col items-center">
                
                <!-- TAB SWITCHER: COVER VS INNER -->
                <div class="flex items-center gap-1 p-1 bg-slate-200/80 rounded-2xl mb-3 shadow-inner">
                    <button type="button" id="tab-btn-cover" onclick="setPhoneTab('cover')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs bg-white text-slate-900 shadow-sm transition flex items-center gap-1.5">
                        <i class="fas fa-envelope-open-text text-amber-600"></i> Cover Amplop
                    </button>
                    <button type="button" id="tab-btn-body" onclick="setPhoneTab('body')" class="px-3.5 py-1.5 rounded-xl font-bold text-xs text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                        <i class="fas fa-file-invoice text-teal-700"></i> Laman Dalam
                    </button>
                </div>

                <!-- PHONE MOCKUP -->
                <div class="phone-mockup bg-slate-900 text-white flex flex-col">
                    <div class="phone-speaker"></div>
                    
                    <!-- 1. SCREEN VIEW: SIMULASI COVER AMPLOP -->
                    <div id="preview-screen-cover" class="relative w-full h-full flex flex-col justify-between p-6 text-center bg-cover bg-center overflow-hidden transition-all duration-500" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS COVER -->
                        <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>;"></div>

                        <!-- KONTEN PREVIEW ATAS -->
                        <div class="relative z-10 pt-4">
                            <p class="font-arabic text-amber-300 text-sm">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
                            <img src="upload/logo-villa-quran.png" class="w-12 h-12 mx-auto mt-2 drop-shadow">
                            <h3 class="font-extrabold text-sm text-white mt-1">Villa Quran Indonesia</h3>
                            <p id="preview-sub" class="text-[9px] text-amber-300 font-medium"><?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?></p>
                        </div>

                        <!-- KONTEN PREVIEW TENGAH (KARTU TAMU) -->
                        <div class="relative z-10 bg-black/40 border border-amber-400/40 rounded-2xl p-4 my-auto backdrop-blur-md shadow-lg">
                            <span class="text-[9px] uppercase tracking-wider text-amber-300 font-bold block">Kepada Yth. Calon Wali:</span>
                            <div class="text-sm font-black text-white mt-0.5">Bpk. Hendy Pratama</div>
                            <p class="text-[9px] text-slate-300 mt-1 leading-relaxed">Undangan Silaturahmi Mahabbah & Brosur Pendidikan Generasi Qur'ani.</p>
                            <div class="mt-2 pt-2 border-t border-white/10 text-[9px] text-emerald-300 font-semibold" id="preview-tahun-txt">
                                Tahun Ajaran <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>
                            </div>
                        </div>

                        <!-- KONTEN PREVIEW BAWAH (TOMBOL BUKA) -->
                        <div class="relative z-10 pb-2">
                            <button type="button" onclick="setPhoneTab('body')" class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-amber-400 to-amber-600 text-emerald-950 font-black text-xs flex items-center justify-center gap-1.5 shadow-lg active:scale-95 transition">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Buka Brosur & Undangan</span>
                            </button>
                            <p class="text-[8px] text-slate-400 mt-2"><i class="fas fa-music mr-1 text-amber-400"></i> Alunan Backsound Syahdu</p>
                        </div>
                    </div>

                    <!-- 2. SCREEN VIEW: SIMULASI HALAMAN DALAM -->
                    <div id="preview-screen-body" class="hidden relative w-full h-full p-4 overflow-y-auto bg-cover bg-center transition-all duration-500" style="background-image: url('<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>');">
                        
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
                                <span class="font-bold text-emerald-950 block text-xs mb-1">Profil Kelulusan Santri</span>
                                <p>• Tahfidz Mutqin 15-30 Juz Bersanad</p>
                                <p>• Ijazah Resmi SMP & SMA</p>
                                <p>• Solopreneur & AI Terapan Modern</p>
                            </div>

                            <!-- Kartu Formulir Cepat -->
                            <div class="p-3 rounded-2xl bg-emerald-950 text-white shadow-md text-[10px]">
                                <span class="text-amber-300 font-extrabold block text-xs">Formulir Silaturahmi</span>
                                <p class="text-slate-300 mt-0.5">Amankan kuota ananda sebelum penutupan gelombang.</p>
                                <div class="mt-2 py-1.5 bg-amber-500 text-emerald-950 text-center font-bold rounded-lg text-[10px]">
                                    Kirim Reservasi Sekarang
                                </div>
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
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Preview otomatis ter-update saat URL, opacity, atau teks diubah.
                </p>
            </div>

        </div>

    </main>

    <!-- SCRIPT REALTIME PREVIEW & COUNTDOWN -->
    <script>
        let activePhoneTab = 'cover';

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

        function updateLiveCoverOpacity(val) {
            const pct = Math.round(val * 100);
            document.getElementById('cover-opacity-val').innerText = pct + '%';
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
            document.getElementById('body-opacity-val').innerText = pct + '%';
            document.getElementById('preview-body-overlay').style.opacity = val;
        }

        function updateLiveText() {
            const gelombang = document.getElementById('input-gelombang').value;
            const tahun = document.getElementById('input-tahun').value;
            
            document.getElementById('preview-sub').innerText = gelombang;
            document.getElementById('preview-tahun-txt').innerText = 'Tahun Ajaran ' + tahun;
            document.getElementById('preview-body-tahun').innerText = tahun;
        }

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

        // Live Countdown Logic in Admin Panel (Sync with index.html)
        function updateAdminCountdownWidget() {
            const now = new Date();
            const year = now.getFullYear();
            const month = now.getMonth() + 1;
            
            let mode = 'auto';
            const radioCustom = document.querySelector('input[name="countdown_mode"][value="custom"]');
            if (radioCustom && radioCustom.checked) mode = 'custom';

            let endDate, waveText;

            if (mode === 'custom') {
                const targetVal = document.getElementById('input-countdown-target').value;
                if (targetVal) {
                    endDate = new Date(targetVal);
                } else {
                    endDate = new Date(year, 11, 31, 23, 59, 59);
                }
                waveText = document.getElementById('input-countdown-title').value || '⏳ Sisa Waktu Pendaftaran Berakhir:';
            } else {
                // Siklus gelombang persis index.html
                if (month >= 7 && month <= 12) {
                    endDate = new Date(year, 11, 31, 23, 59, 59);
                    waveText = "⏳ Sisa Waktu Pendaftaran Gelombang 1 Berakhir:";
                } else if (month >= 1 && month <= 3) {
                    endDate = new Date(year, 2, 31, 23, 59, 59);
                    waveText = "⏳ Sisa Waktu Pendaftaran Gelombang 2 Berakhir:";
                } else {
                    endDate = new Date(year, 5, 30, 23, 59, 59);
                    waveText = "⏳ Sisa Waktu Pendaftaran Gelombang 3 Berakhir:";
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

                if (document.getElementById('adm-cd-hari')) document.getElementById('adm-cd-hari').innerText = dStr;
                if (document.getElementById('adm-cd-jam')) document.getElementById('adm-cd-jam').innerText = hStr;
                if (document.getElementById('adm-cd-menit')) document.getElementById('adm-cd-menit').innerText = mStr;
                if (document.getElementById('adm-cd-detik')) document.getElementById('adm-cd-detik').innerText = sStr;
                if (document.getElementById('adm-cd-text')) document.getElementById('adm-cd-text').innerText = waveText;

                // Mini phone countdown
                if (document.getElementById('phone-cd-hari')) document.getElementById('phone-cd-hari').innerText = dStr;
                if (document.getElementById('phone-cd-jam')) document.getElementById('phone-cd-jam').innerText = hStr;
                if (document.getElementById('phone-cd-menit')) document.getElementById('phone-cd-menit').innerText = mStr;
                if (document.getElementById('phone-cd-detik')) document.getElementById('phone-cd-detik').innerText = sStr;
            }
        }

        // Jalankan loop per detik
        setInterval(updateAdminCountdownWidget, 1000);
        updateAdminCountdownWidget();
    </script>
</body>
</html>
