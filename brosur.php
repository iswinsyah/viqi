<?php
// brosur.php
// Halaman Khusus Brosur Sekolah Model Undangan Digital Interaktif
// Villa Quran Indonesia - Mobile-First Luxury Edition

require_once 'koneksi.php';

// Ambil Pengaturan Brosur Dinamis dari Database
$cols_brosur_check = [
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
    "card_bg_style"        => "ALTER TABLE pengaturan_brosur ADD COLUMN card_bg_style VARCHAR(30) DEFAULT 'glass_dark' AFTER btn_text_color",
    "font_family"          => "ALTER TABLE pengaturan_brosur ADD COLUMN font_family VARCHAR(50) DEFAULT 'Plus Jakarta Sans' AFTER card_bg_style",
    "judul_utama"          => "ALTER TABLE pengaturan_brosur ADD COLUMN judul_utama VARCHAR(150) DEFAULT 'Villa Quran Indonesia' AFTER font_family",
    "subjudul"             => "ALTER TABLE pengaturan_brosur ADD COLUMN subjudul VARCHAR(200) DEFAULT 'Sekolah Tahfidz Berasrama Nyaman Ala Villa' AFTER judul_utama",
    "bismillah_text"       => "ALTER TABLE pengaturan_brosur ADD COLUMN bismillah_text VARCHAR(150) DEFAULT 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ' AFTER subjudul",
    "tamu_header_text"     => "ALTER TABLE pengaturan_brosur ADD COLUMN tamu_header_text VARCHAR(150) DEFAULT 'Kepada Yth. Calon Wali Santri:' AFTER bismillah_text",
    "tamu_sambutan_text"   => "ALTER TABLE pengaturan_brosur ADD COLUMN tamu_sambutan_text TEXT AFTER tamu_header_text",
    "btn_text"             => "ALTER TABLE pengaturan_brosur ADD COLUMN btn_text VARCHAR(100) DEFAULT 'Buka Brosur & Undangan' AFTER tamu_sambutan_text",
    "show_bismillah"       => "ALTER TABLE pengaturan_brosur ADD COLUMN show_bismillah TINYINT(1) DEFAULT 1 AFTER btn_text",
    "show_subjudul"        => "ALTER TABLE pengaturan_brosur ADD COLUMN show_subjudul TINYINT(1) DEFAULT 1 AFTER show_bismillah",
    "show_logo"            => "ALTER TABLE pengaturan_brosur ADD COLUMN show_logo TINYINT(1) DEFAULT 1 AFTER show_subjudul",
    "show_sambutan"        => "ALTER TABLE pengaturan_brosur ADD COLUMN show_sambutan TINYINT(1) DEFAULT 1 AFTER show_logo",
    "logo_size"            => "ALTER TABLE pengaturan_brosur ADD COLUMN logo_size INT DEFAULT 80 AFTER show_sambutan",
    "card_width"           => "ALTER TABLE pengaturan_brosur ADD COLUMN card_width INT DEFAULT 100 AFTER logo_size",
    "card_padding"         => "ALTER TABLE pengaturan_brosur ADD COLUMN card_padding INT DEFAULT 20 AFTER card_width",
    "btn_width"            => "ALTER TABLE pengaturan_brosur ADD COLUMN btn_width INT DEFAULT 100 AFTER card_padding",
    "btn_height"           => "ALTER TABLE pengaturan_brosur ADD COLUMN btn_height INT DEFAULT 52 AFTER btn_width",
    "text_title_size"      => "ALTER TABLE pengaturan_brosur ADD COLUMN text_title_size INT DEFAULT 22 AFTER btn_height",
    "text_sub_size"        => "ALTER TABLE pengaturan_brosur ADD COLUMN text_sub_size INT DEFAULT 12 AFTER text_title_size",
    "show_video"           => "ALTER TABLE pengaturan_brosur ADD COLUMN show_video TINYINT(1) DEFAULT 1 AFTER text_sub_size",
    "video_url"            => "ALTER TABLE pengaturan_brosur ADD COLUMN video_url TEXT AFTER show_video",
    "video_width"          => "ALTER TABLE pengaturan_brosur ADD COLUMN video_width INT DEFAULT 100 AFTER video_url",
    "video_height"         => "ALTER TABLE pengaturan_brosur ADD COLUMN video_height INT DEFAULT 240 AFTER video_width",
    "show_maps"            => "ALTER TABLE pengaturan_brosur ADD COLUMN show_maps TINYINT(1) DEFAULT 1 AFTER video_height",
    "maps_url"             => "ALTER TABLE pengaturan_brosur ADD COLUMN maps_url TEXT AFTER show_maps",
    "maps_width"           => "ALTER TABLE pengaturan_brosur ADD COLUMN maps_width INT DEFAULT 100 AFTER maps_url",
    "maps_height"          => "ALTER TABLE pengaturan_brosur ADD COLUMN maps_height INT DEFAULT 220 AFTER maps_width"
];
$res_c = $conn->query("DESCRIBE pengaturan_brosur");
$curr_cols = [];
if ($res_c) {
    while ($r = $res_c->fetch_assoc()) $curr_cols[] = $r['Field'];
}
foreach ($cols_brosur_check as $col => $sql) {
    if (!in_array($col, $curr_cols)) {
        $conn->query($sql);
    }
}

$q_brosur = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg_brosur = ($q_brosur && $q_brosur->num_rows > 0) ? $q_brosur->fetch_assoc() : [];

$tahun_ajaran      = !empty($cfg_brosur['tahun_ajaran']) ? htmlspecialchars($cfg_brosur['tahun_ajaran']) : '2026/2027';
$periode_gelombang = !empty($cfg_brosur['periode_gelombang']) ? htmlspecialchars($cfg_brosur['periode_gelombang']) : 'Gelombang 1 — Kuota Terbatas';
$cover_bg_url      = !empty($cfg_brosur['cover_bg_url']) ? htmlspecialchars($cfg_brosur['cover_bg_url']) : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80';
$cover_opacity     = isset($cfg_brosur['cover_overlay_opacity']) ? (float)$cfg_brosur['cover_overlay_opacity'] : 0.85;

// Background Laman Dalam Dinamis
$body_bg_url          = !empty($cfg_brosur['body_bg_url']) ? htmlspecialchars($cfg_brosur['body_bg_url']) : '';
$body_overlay_opacity = isset($cfg_brosur['body_overlay_opacity']) ? (float)$cfg_brosur['body_overlay_opacity'] : 0.92;

// Pengaturan Countdown Dinamis
$countdown_mode       = !empty($cfg_brosur['countdown_mode']) ? $cfg_brosur['countdown_mode'] : 'auto';
$countdown_target     = !empty($cfg_brosur['countdown_target']) ? $cfg_brosur['countdown_target'] : '2026-12-31 23:59:59';
$countdown_title      = !empty($cfg_brosur['countdown_title']) ? htmlspecialchars($cfg_brosur['countdown_title']) : '⏳ Sisa Waktu Pendaftaran Berakhir:';
$show_countdown       = isset($cfg_brosur['show_countdown']) ? (int)$cfg_brosur['show_countdown'] : 1;

// Tipografi & Redaksi Teks
$font_family        = !empty($cfg_brosur['font_family']) ? $cfg_brosur['font_family'] : 'Plus Jakarta Sans';
$judul_utama        = !empty($cfg_brosur['judul_utama']) ? htmlspecialchars($cfg_brosur['judul_utama']) : 'Villa Quran Indonesia';
$subjudul           = !empty($cfg_brosur['subjudul']) ? htmlspecialchars($cfg_brosur['subjudul']) : 'Sekolah Tahfidz Berasrama Nyaman Ala Villa';
$bismillah_text     = !empty($cfg_brosur['bismillah_text']) ? htmlspecialchars($cfg_brosur['bismillah_text']) : 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ';
$tamu_header_text   = !empty($cfg_brosur['tamu_header_text']) ? htmlspecialchars($cfg_brosur['tamu_header_text']) : 'Kepada Yth. Calon Wali Santri:';
$tamu_sambutan_text = !empty($cfg_brosur['tamu_sambutan_text']) ? $cfg_brosur['tamu_sambutan_text'] : 'Undangan Mahabbah Silaturahmi & Brosur Informasi Pendidikan Putra-Putri Generasi Qur\'ani.';
$btn_text           = !empty($cfg_brosur['btn_text']) ? htmlspecialchars($cfg_brosur['btn_text']) : 'Buka Brosur & Undangan';

$show_bismillah     = isset($cfg_brosur['show_bismillah']) ? (int)$cfg_brosur['show_bismillah'] : 1;
$show_subjudul      = isset($cfg_brosur['show_subjudul']) ? (int)$cfg_brosur['show_subjudul'] : 1;
$show_logo          = isset($cfg_brosur['show_logo']) ? (int)$cfg_brosur['show_logo'] : 1;
$show_sambutan      = isset($cfg_brosur['show_sambutan']) ? (int)$cfg_brosur['show_sambutan'] : 1;

// Dimensi Ukuran (Lebar & Tinggi)
$logo_size       = isset($cfg_brosur['logo_size']) ? (int)$cfg_brosur['logo_size'] : 80;
$card_width      = isset($cfg_brosur['card_width']) ? (int)$cfg_brosur['card_width'] : 100;
$card_padding    = isset($cfg_brosur['card_padding']) ? (int)$cfg_brosur['card_padding'] : 20;
$btn_width       = isset($cfg_brosur['btn_width']) ? (int)$cfg_brosur['btn_width'] : 100;
$btn_height      = isset($cfg_brosur['btn_height']) ? (int)$cfg_brosur['btn_height'] : 52;
$text_title_size = isset($cfg_brosur['text_title_size']) ? (int)$cfg_brosur['text_title_size'] : 22;
$text_sub_size   = isset($cfg_brosur['text_sub_size']) ? (int)$cfg_brosur['text_sub_size'] : 12;

// Warna & Kontras
$text_color     = !empty($cfg_brosur['text_color']) ? htmlspecialchars($cfg_brosur['text_color']) : '#ffffff';
$accent_color   = !empty($cfg_brosur['accent_color']) ? htmlspecialchars($cfg_brosur['accent_color']) : '#fbbf24';
$btn_bg_color   = !empty($cfg_brosur['btn_bg_color']) ? htmlspecialchars($cfg_brosur['btn_bg_color']) : '#d97706';
$btn_text_color = !empty($cfg_brosur['btn_text_color']) ? htmlspecialchars($cfg_brosur['btn_text_color']) : '#022d27';
$card_bg_style  = !empty($cfg_brosur['card_bg_style']) ? $cfg_brosur['card_bg_style'] : 'glass_dark';

// Video & Maps
$show_video   = isset($cfg_brosur['show_video']) ? (int)$cfg_brosur['show_video'] : 1;
$video_url    = !empty($cfg_brosur['video_url']) ? $cfg_brosur['video_url'] : 'https://www.youtube.com/embed/dQw4w9WgXcQ';
$video_width  = isset($cfg_brosur['video_width']) ? (int)$cfg_brosur['video_width'] : 100;
$video_height = isset($cfg_brosur['video_height']) ? (int)$cfg_brosur['video_height'] : 240;

$show_maps    = isset($cfg_brosur['show_maps']) ? (int)$cfg_brosur['show_maps'] : 1;
$maps_url     = !empty($cfg_brosur['maps_url']) ? $cfg_brosur['maps_url'] : 'https://maps.google.com/maps?q=Villa+Quran+Indonesia&t=&z=14&ie=UTF8&iwloc=&output=embed';
$maps_width   = isset($cfg_brosur['maps_width']) ? (int)$cfg_brosur['maps_width'] : 100;
$maps_height  = isset($cfg_brosur['maps_height']) ? (int)$cfg_brosur['maps_height'] : 220;

// Posisi Elemen Cover
$pos_cover = !empty($cfg_brosur['cover_elements_pos']) ? json_decode($cfg_brosur['cover_elements_pos'], true) : [];
$header_y  = $pos_cover['header_y'] ?? 12;
$guest_y   = $pos_cover['guest_y'] ?? 45;
$btn_y     = $pos_cover['btn_y'] ?? 82;

$biaya_pendaftaran = isset($cfg_brosur['biaya_pendaftaran']) ? number_format($cfg_brosur['biaya_pendaftaran'], 0, ',', '.') : '350.000';
$biaya_pangkal     = isset($cfg_brosur['biaya_pangkal']) ? number_format($cfg_brosur['biaya_pangkal'], 0, ',', '.') : '12.500.000';
$biaya_tahunan     = isset($cfg_brosur['biaya_tahunan']) ? number_format($cfg_brosur['biaya_tahunan'], 0, ',', '.') : '2.500.000';
$biaya_spp         = isset($cfg_brosur['biaya_spp']) ? number_format($cfg_brosur['biaya_spp'], 0, ',', '.') : '1.650.000';
$diskon_gelombang  = isset($cfg_brosur['diskon_gelombang']) ? number_format($cfg_brosur['diskon_gelombang'], 0, ',', '.') : '2.000.000';

// Ambil parameter personalisasi & afiliasi
$to_param   = isset($_GET['to']) ? trim($_GET['to']) : '';
$nama_tamu  = !empty($to_param) ? htmlspecialchars($to_param) : 'Bapak / Ibu Calon Wali Santri & Keluarga';
$kode_ref   = isset($_GET['ref']) ? trim(htmlspecialchars($_GET['ref'])) : 'organik';

// Cari nama agen jika ref cocok di tabel agen
$nama_agen_pengundang = '';
if (!empty($kode_ref) && $kode_ref !== 'organik') {
    $q_ag = $conn->query("SELECT nama FROM agen WHERE kode_ref = '$kode_ref' OR whatsapp = '$kode_ref' LIMIT 1");
    if ($q_ag && $q_ag->num_rows > 0) {
        $row_ag = $q_ag->fetch_assoc();
        $nama_agen_pengundang = $row_ag['nama'];
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Undangan Silaturahmi & Brosur Digital | Villa Quran Indonesia</title>

    <!-- Meta Tags & OpenGraph untuk Thumbnail WhatsApp -->
    <meta name="description" content="Undangan Khusus Silaturahmi & Brosur Pendidikan Generasi Qur'ani. Tahfidz Mutqin 15-30 Juz Bersanad, Berijazah Resmi SMP-SMA, Digital Marketing & Solopreneur di Villa Quran Indonesia.">
    <meta property="og:title" content="Undangan Khusus untuk <?= htmlspecialchars($nama_tamu) ?> - Villa Quran Indonesia">
    <meta property="og:description" content="Mencetak Hafidz Bersanad di Lingkungan Asri ala Villa. Tahfidz Mutqin, Ijazah Resmi Negara & Skill Solopreneur Masa Depan.">
    <meta property="og:image" content="https://villaquranindonesia.com/upload/logo-villa-quran.png">
    <meta property="og:type" content="website">

    <!-- Google Fonts Multi-Family: Dynamic Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@500;700;900&family=Inter:wght@400;600;700&family=Outfit:wght@400;600;800&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            850: '#064e45',
                            950: '#022d27',
                        },
                        gold: {
                            100: '#fef3c7',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        }
                    },
                    fontFamily: {
                        sans: ['"<?= $font_family ?>"', '"Plus Jakarta Sans"', 'sans-serif'],
                        arabic: ['"Amiri"', 'serif'],
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: '<?= $font_family ?>', 'Plus Jakarta Sans', sans-serif;
            background-color: #f6f7f5;
            color: #1e293b;
            overflow-x: hidden;
        }

        .font-arabic {
            font-family: 'Amiri', serif;
        }

        /* Gold Glow Effect */
        .gold-glow {
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.25);
        }

        /* Cover Envelope Animation */
        #envelope-cover {
            transition: transform 0.8s cubic-bezier(0.77, 0, 0.175, 1), opacity 0.8s ease;
        }

        .cover-hidden {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }

        /* Floating Audio Vinyl Rotation */
        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spinSlow 8s linear infinite;
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(11, 132, 120, 0.12);
        }

        .glass-dark {
            background: rgba(4, 51, 44, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(245, 158, 11, 0.25);
        }

        /* Hide Scrollbar for Horizontal Card Menu Carousel */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Card Menu Item: Default View 4 Cards Across Width */
        .bottom-card-menu-item {
            flex: 0 0 calc(25% - 6px);
            min-width: calc(25% - 6px);
            max-width: calc(25% - 6px);
        }
    </style>
</head>
<body class="bg-pattern antialiased">

    <!-- ============================================================ -->
    <!-- 1. FULLSCREEN COVER AMPLOP DIGITAL (OPENING EXPERIENCE)      -->
    <!-- ============================================================ -->
    <div id="envelope-cover" class="fixed inset-0 z-50 flex items-center justify-center text-white p-4 overflow-y-auto bg-cover bg-center transition-all duration-700 select-none" style="background-image: url('<?= $cover_bg_url ?>');">
        
        <!-- Ornamen Latar & Overlay Dinamis -->
        <div class="absolute inset-0 bg-gradient-to-br from-[#022c22] via-[#043d35] to-[#021d19]" style="opacity: <?= $cover_opacity ?>;"></div>
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: url('https://www.transparenttextures.com/patterns/arabesque.png');"></div>
        <div class="absolute -top-24 -left-24 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Kartu Amplop Mewah -->
        <div class="relative w-full max-w-md my-auto rounded-3xl p-6 sm:p-8 text-center border border-amber-500/30 shadow-2xl glass-dark backdrop-blur-2xl">
            
            <!-- Ornamen Bismillah -->
            <?php if ($show_bismillah): ?>
            <p class="font-arabic text-xl sm:text-2xl mb-3 tracking-wide transition-colors" style="color: <?= $accent_color ?>;">
                <?= $bismillah_text ?>
            </p>
            <?php endif; ?>
            
            <!-- Logo Villa Quran (Bulat Sempurna sesuai Lingkaran Logo) -->
            <?php if ($show_logo): ?>
            <div class="relative inline-block mb-4" id="cover-logo-block">
                <div class="mx-auto rounded-full p-1 bg-white border-2 border-emerald-600 shadow-xl overflow-hidden aspect-square flex items-center justify-center transition-all" style="width: <?= $logo_size ?>px; height: <?= $logo_size ?>px;">
                    <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-full h-full object-cover rounded-full">
                </div>
                <span class="absolute -bottom-2 left-1/2 -translate-x-1/2 bg-gradient-to-r from-amber-500 to-amber-600 text-[10px] font-extrabold uppercase px-3 py-0.5 rounded-full text-emerald-950 tracking-wider shadow whitespace-nowrap">Resmi SPMB</span>
            </div>
            <?php endif; ?>

            <!-- Judul Utama & Subjudul -->
            <h1 class="font-black tracking-tight leading-tight transition-colors" style="color: <?= $text_color ?>; font-size: <?= $text_title_size ?>px;">
                <?= $judul_utama ?>
            </h1>

            <?php if ($show_subjudul): ?>
            <p class="font-medium mb-1 transition-colors leading-relaxed" style="color: <?= $accent_color ?>; font-size: <?= $text_sub_size ?>px;">
                <?= $subjudul ?>
            </p>
            <?php endif; ?>

            <p class="text-[11px] font-extrabold uppercase tracking-wider mb-6 transition-colors" style="color: <?= $accent_color ?>;">
                <?= $periode_gelombang ?> &bull; TA <?= $tahun_ajaran ?>
            </p>

            <!-- Segel Nama Tamu Calon Wali Santri -->
            <div class="mx-auto rounded-2xl mb-6 shadow-inner relative overflow-hidden transition-all <?= ($card_bg_style === 'glass_light') ? 'bg-white/85 border border-emerald-600/40 text-slate-800' : 'bg-black/35 border border-amber-500/30 text-white' ?>" style="width: <?= $card_width ?>%; padding: <?= $card_padding ?>px;">
                <div class="absolute top-0 right-0 transform translate-x-3 -translate-y-3 w-12 h-12 bg-amber-400/10 rounded-full blur-lg"></div>
                
                <p class="text-[11px] uppercase tracking-widest font-semibold mb-1" style="color: <?= $accent_color ?>;">
                    <?= $tamu_header_text ?>
                </p>
                <div class="text-lg sm:text-xl font-extrabold leading-snug py-1" style="color: <?= ($card_bg_style === 'glass_light') ? '#022d27' : '#ffffff' ?>;">
                    <?= $nama_tamu ?>
                </div>
                <?php if (!empty($nama_agen_pengundang)): ?>
                    <p class="text-[11px] mt-1 italic" style="color: <?= $accent_color ?>;"><i class="fas fa-hand-holding-heart mr-1"></i> Rekomendasi: <?= htmlspecialchars($nama_agen_pengundang) ?></p>
                <?php endif; ?>
                
                <?php if ($show_sambutan && !empty($tamu_sambutan_text)): ?>
                <div class="mt-3 pt-3 border-t border-white/10 text-[11px] leading-relaxed <?= ($card_bg_style === 'glass_light') ? 'text-slate-600' : 'text-gray-300' ?>">
                    <?= nl2br(htmlspecialchars($tamu_sambutan_text)) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tombol Buka Undangan -->
            <div class="flex justify-center w-full">
                <button onclick="bukaUndangan()" class="rounded-2xl shadow-xl transition-all transform active:scale-95 flex items-center justify-center gap-3 gold-glow group font-black" style="width: <?= $btn_width ?>%; height: <?= $btn_height ?>px; background: <?= $btn_bg_color ?>; color: <?= $btn_text_color ?>;">
                    <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs group-hover:rotate-12 transition" style="background: <?= $btn_text_color ?>; color: <?= $btn_bg_color ?>;">
                        <i class="fas fa-envelope-open-text"></i>
                    </span>
                    <span class="text-sm sm:text-base"><?= $btn_text ?></span>
                </button>
            </div>
            
            <p class="text-[11px] mt-4 flex items-center justify-center gap-1.5 opacity-80" style="color: <?= $text_color ?>;">
                <i class="fas fa-volume-up text-xs" style="color: <?= $accent_color ?>;"></i> <span>Dilengkapi alunan backsound syahdu</span>
            </p>
        </div>
    </div>

    <!-- Audio Element untuk Backsound (Multi-source Fallback) -->
    <audio id="audio-backsound" loop preload="auto">
        <source src="upload/backsound.mp3" type="audio/mpeg">
        <source src="https://archive.org/download/IslamicBackgroundSoundsAahat/28-ISLAMIC%20BACKGROUND%20SOUNDS.mp3" type="audio/mpeg">
    </audio>

    <!-- Floating Audio Control Button -->
    <div id="audio-control" class="fixed top-4 right-4 z-40 hidden">
        <button onclick="toggleAudio()" class="flex items-center gap-2 bg-emerald-900/90 text-amber-300 px-3.5 py-2 rounded-full border border-amber-500/40 shadow-xl backdrop-blur-md text-xs font-bold transition hover:bg-emerald-800">
            <span id="audio-icon" class="w-5 h-5 rounded-full bg-amber-400 text-emerald-950 flex items-center justify-center animate-spin-slow">
                <i class="fas fa-music text-[10px]"></i>
            </span>
            <span id="audio-label" class="hidden sm:inline">Audio On</span>
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- BACKGROUND HALAMAN DALAM DINAMIS (PINTEREST / WALLPAPER)    -->
    <!-- ============================================================ -->
    <?php if (!empty($body_bg_url)): ?>
    <div id="inner-bg-layer" class="fixed inset-0 pointer-events-none -z-10 bg-cover bg-center bg-fixed transition-all duration-700" style="background-image: url('<?= $body_bg_url ?>');">
        <div class="absolute inset-0 bg-[#f6f7f5]" style="opacity: <?= $body_overlay_opacity ?>;"></div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT CONTAINER (MOBILE FIRST LAYOUT)                  -->
    <!-- ============================================================ -->
    <div id="main-content" class="max-w-xl mx-auto px-4 pt-6 pb-32 min-h-screen">

        <!-- ========================================== -->
        <!-- MENU 1: HOME (HEADER BRANDING & LOGO)      -->
        <!-- ========================================== -->
        <header id="brosur-home" class="text-center mb-8 scroll-mt-6">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-bold mb-3 shadow-sm">
                <i class="fas fa-star text-amber-600"></i>
                <span>Tahun Ajaran <?= $tahun_ajaran ?> &bull; <?= $periode_gelombang ?></span>
            </div>
            
            <!-- Logo Bulat Lingkaran Sempurna -->
            <div class="w-16 h-16 rounded-full mx-auto mb-2 p-1 bg-white border-2 border-emerald-600 shadow-md overflow-hidden aspect-square flex items-center justify-center">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-full h-full object-cover rounded-full">
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-emerald-950 tracking-tight"><?= $judul_utama ?></h1>
            <p class="text-sm text-emerald-700 font-semibold"><?= $subjudul ?></p>
            <div class="w-16 h-1 bg-gradient-to-r from-amber-400 to-amber-600 mx-auto rounded-full mt-3"></div>
        </header>

        <!-- ========================================== -->
        <!-- MENU 2: MENGAPA VQBM (MUQADDIMAH & PILAR)  -->
        <!-- ========================================== -->
        <section id="brosur-mengapa" class="scroll-mt-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-300 flex items-center justify-center font-bold text-sm shadow">
                    <i class="fas fa-heart text-xs"></i>
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Mengapa Villa Quran (VQBM)?</h2>
                    <p class="text-xs text-gray-500">Mencetak generasi ahlul Qur'an dengan keunggulan masa depan</p>
                </div>
            </div>

            <!-- Muqaddimah Card -->
            <div class="glass-card rounded-3xl p-6 sm:p-7 shadow-lg mb-4 border border-emerald-100 relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-amber-400/10 rounded-full blur-2xl"></div>
                
                <p class="text-center font-arabic text-2xl text-emerald-900 font-bold mb-2 leading-loose">السَّلاَمُ عَلَيْكُمْ وَرَحْمَةُ اللهِ وَبَرَكَاتُهُ</p>
                
                <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 mb-4 text-center">
                    <p class="text-xs uppercase tracking-wider font-bold text-emerald-800"><?= $tamu_header_text ?></p>
                    <p class="text-lg font-black text-emerald-950 mt-0.5"><?= $nama_tamu ?></p>
                </div>

                <p class="text-sm text-gray-700 leading-relaxed mb-4 text-justify">
                    Segala puji bagi Allah Subhanahu wa Ta'ala. Merupakan impian terindah setiap orang tua muslim kelak di yaumil akhir disematkan <strong>Mahkota Kehormatan yang cahayanya lebih terang dari matahari</strong> karena ananda menghafal Al-Qur'an.
                </p>

                <div class="border-l-4 border-amber-500 pl-4 py-2 bg-amber-50/60 rounded-r-xl italic text-xs text-amber-950 leading-relaxed mb-4">
                    "Siapa yang membaca Al-Qur'an, mempelajarinya, dan mengamalkannya, maka pada hari kiamat dipakaikan kepada kedua orang tuanya mahkota dari cahaya..." 
                    <span class="block font-bold mt-1 text-amber-800">— HR. Al-Hakim</span>
                </div>

                <p class="text-xs text-gray-600 leading-relaxed">
                    Kami hadir untuk membersamai ikhtiar mulia Bapak/Ibu melalui kurikulum terpadu: <strong>Ketinggian Al-Qur'an, Legalitas Ijazah Resmi Negara, serta Penguasaan Teknologi & Wirausaha Modern.</strong>
                </p>
            </div>

            <!-- Tiga Pilar Utama -->
            <div class="space-y-3">
                <div class="rounded-2xl p-4 bg-gradient-to-br from-emerald-900 to-emerald-950 text-white shadow-md border border-emerald-700/50 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400 text-emerald-950 flex items-center justify-center font-bold text-base flex-shrink-0 shadow">
                        <i class="fas fa-book-quran"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white">1. Tahfidz Al-Qur'an Bersanad & Mutqin</h3>
                        <p class="text-xs text-emerald-100/90 mt-0.5 leading-relaxed">Talaqqi harian intensif, sanad qira'ah bersambung, dan bimbingan muroja'ah disiplin.</p>
                    </div>
                </div>

                <div class="rounded-2xl p-4 bg-gradient-to-br from-[#064e45] to-teal-950 text-white shadow-md border border-emerald-700/50 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-400 text-emerald-950 flex items-center justify-center font-bold text-base flex-shrink-0 shadow">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white">2. Kurikulum Nasional & Ijazah Resmi (SMP/SMA)</h3>
                        <p class="text-xs text-emerald-100/90 mt-0.5 leading-relaxed">Legalitas ijazah negara terakreditasi untuk tembus PTN, kedinasan, atau studi luar negeri.</p>
                    </div>
                </div>

                <div class="rounded-2xl p-4 bg-gradient-to-br from-amber-700 to-amber-950 text-white shadow-md border border-amber-600/50 flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-300 text-amber-950 flex items-center justify-center font-bold text-base flex-shrink-0 shadow">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-white">3. Ekosistem Solopreneur & AI Terapan</h3>
                        <p class="text-xs text-amber-100/90 mt-0.5 leading-relaxed">Praktek digital marketing, e-commerce, content creator, dan penerapan prompt AI.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Video Profil / Suasana Pesantren -->
        <?php if ($show_video && !empty($video_url)): ?>
        <section class="mb-8">
            <div class="glass-card rounded-3xl p-4 shadow-lg border border-emerald-100 flex flex-col items-center">
                <div class="flex items-center gap-2 mb-3 self-start">
                    <i class="fab fa-youtube text-red-600 text-lg"></i>
                    <h3 class="text-xs sm:text-sm font-bold text-emerald-950">Video Profil & Suasana Pesantren</h3>
                </div>
                <div class="rounded-2xl overflow-hidden shadow-md border border-gray-200 transition-all mx-auto" style="width: <?= $video_width ?>%; height: <?= $video_height ?>px; max-width: 100%;">
                    <iframe src="<?= htmlspecialchars($video_url) ?>" class="w-full h-full" style="border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- MENU 3: TARGET KOMPETENSI                  -->
        <!-- ========================================== -->
        <section id="brosur-kompetensi" class="scroll-mt-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow">
                    <i class="fas fa-bullseye text-xs"></i>
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Target Kompetensi Lulusan</h2>
                    <p class="text-xs text-gray-500">Standar mutu & profil lulusan santri Villa Quran</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-quran"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Mutqin 15 s/d 30 Juz Bersanad</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Hafal kuat dengan standar tahsin fashahah, tajwid mutqin, dan sanad qira'ah bersambung.</p>
                </div>

                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Ijazah Resmi Setara SMP/SMA</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Legalitas ijazah negara terakreditasi, bebas tembus PTN, kedinasan & kampus luar negeri.</p>
                </div>

                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Kecakapan Solopreneur & AI</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Keterampilan digital marketing, content creator dakwah, serta AI untuk produktivitas.</p>
                </div>

                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-hands-holding-child"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Adab Luhur & Mandiri</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Pribadi sholeh berkarakter mandiri, disiplin ibadah harian, dan santun berbakti.</p>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 4: FASILITAS PESANTREN ALA VILLA      -->
        <!-- ========================================== -->
        <section id="brosur-fasilitas" class="scroll-mt-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-teal-600 text-white flex items-center justify-center font-bold text-sm shadow">
                    <i class="fas fa-hotel text-xs"></i>
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Fasilitas Pesantren Nyaman</h2>
                    <p class="text-xs text-gray-500">Sarana asri ala villa yang menunjang kenyamanan belajar santri</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <!-- Fasilitas 1 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg mb-2.5">
                        <i class="fas fa-bed"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Asrama Representatif & Kasur Nyaman</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Kamar asrama berkapasitas seimbang dengan ranjang kasur empuk, lemari pribadi, sirkulasi udara pegunungan yang sejuk dan bersih.</p>
                </div>

                <!-- Fasilitas 2 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg mb-2.5">
                        <i class="fas fa-mosque"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Masjid & Mushola Talaqqi 24 Jam</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Pusat halaqoh tahfidz Al-Qur'an bersanad, sholat berjamaah lima waktu, dan qiyamul lail syahdu berhawa sejuk asri.</p>
                </div>

                <!-- Fasilitas 3 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg mb-2.5">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Ruang Belajar & Lab Solopreneur AI</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Didukung akses internet terbimbing untuk praktek digital marketing, modul AI Dewan Tutor Diknas, dan pembelajaran modern.</p>
                </div>

                <!-- Fasilitas 4 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center text-lg mb-2.5">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Nutrisi Sehat 3x Sehari & Laundry</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Menu makanan bergizi 3 kali sehari yang disukai santri, layanan laundry pakaian bersih terjadwal, dan kesehatan terpantau.</p>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 5: DEWAN PENGASUH & ASATIDZ           -->
        <!-- ========================================== -->
        <section id="brosur-pengasuh" class="scroll-mt-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-purple-600 text-white flex items-center justify-center font-bold text-sm shadow">
                    <i class="fas fa-user-graduate text-xs"></i>
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Dewan Pengasuh & Asatidz</h2>
                    <p class="text-xs text-gray-500">Membina 24 jam dengan penuh kasih sayang dan keteladanan</p>
                </div>
            </div>

            <div class="space-y-3.5">
                <!-- Pengasuh Mukim -->
                <div class="glass-card rounded-2xl p-5 border border-emerald-100 shadow-sm flex items-start gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-800 text-amber-300 flex items-center justify-center text-xl flex-shrink-0 font-bold shadow-md">
                        <i class="fas fa-users-gear"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Talaqqi Bersanad</span>
                        <h3 class="font-black text-sm sm:text-base text-emerald-950 mt-1">Tim Asatidz Hafizh Mukim 24 Jam</h3>
                        <p class="text-xs text-gray-600 leading-relaxed mt-1">
                            Didampingi asatidz mukim bersanad Al-Qur'an 30 Juz yang tinggal bersama santri untuk membina kedisiplinan adab, qiyamul lail, dan kehangatan keluarga.
                        </p>
                    </div>
                </div>

                <!-- Kolaborasi Dewan Tutor AI -->
                <div class="rounded-2xl p-5 bg-gradient-to-br from-emerald-950 via-[#064e45] to-teal-950 text-white shadow-xl border border-amber-400/40 relative overflow-hidden">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-emerald-950 text-[10px] font-extrabold uppercase shadow">Inovasi Pesantren</span>
                        <span class="text-xs text-amber-300 font-bold"><i class="fas fa-robot mr-1"></i> Dewan Tutor AI Diknas</span>
                    </div>
                    <h3 class="text-sm sm:text-base font-extrabold text-white mb-1">Sinergi Asatidz Manusia & AI Interaktif</h3>
                    <p class="text-xs text-emerald-100 leading-relaxed">
                        Untuk pelajaran umum Diknas, santri didampingi Dewan Tutor AI berkepribadian ilmuwan Muslim ternama (seperti Ustadz Ibnu Khaldun untuk Sosiologi). Santri dapat berdiskusi dan tanya jawab pelajaran 24/7 kapan pun.
                    </p>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 6: INVESTASI PENDIDIKAN (BIAYA)       -->
        <!-- ========================================== -->
        <section id="brosur-biaya" class="scroll-mt-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center font-bold text-sm shadow">
                    <i class="fas fa-receipt text-xs"></i>
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Investasi Pendidikan (Biaya)</h2>
                    <p class="text-xs text-gray-500">Transparan, terjangkau, dan sepadan dengan mutu</p>
                </div>
            </div>

            <div class="glass-card rounded-3xl p-5 shadow-lg border border-emerald-100">
                <div class="divide-y divide-gray-100 text-xs sm:text-sm">
                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-900 block">1. Biaya Pendaftaran & Observasi</span>
                            <span class="text-[11px] text-gray-500">Pemeriksaan kesehatan, tes minat & bakat</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-sm">Rp <?= $biaya_pendaftaran ?></span>
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-900 block">2. Uang Pangkal / Sarana Masuk</span>
                            <span class="text-[11px] text-gray-500">Lemari, ranjang kasur, seragam, modul</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-sm">Rp <?= $biaya_pangkal ?></span>
                    </div>

                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-900 block">3. Biaya Pengembangan Tahunan</span>
                            <span class="text-[11px] text-gray-500">Karantina tahfidz, ekstrakurikuler & rihlah</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-sm">Rp <?= $biaya_tahunan ?></span>
                    </div>

                    <div class="py-3.5 bg-emerald-50/80 -mx-5 px-5 rounded-2xl border border-emerald-200/60 flex justify-between items-center mt-2">
                        <div>
                            <span class="font-extrabold text-emerald-950 block text-sm">4. SPP All-in Per Bulan</span>
                            <span class="text-[11px] text-emerald-800">Makan 3x/hari bergizi, asrama, laundry & bimbingan</span>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-emerald-800 text-base">Rp <?= $biaya_spp ?></span>
                            <span class="block text-[10px] text-emerald-600">/ bulan</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-amber-800 bg-amber-50/70 p-3 rounded-xl border border-amber-200">
                    <span class="flex items-center gap-1.5"><i class="fas fa-gift text-amber-600"></i> <strong>Diskon <?= $periode_gelombang ?></strong> potongan Rp <?= $diskon_gelombang ?> Uang Pangkal</span>
                </div>
            </div>
        </section>

        <!-- Countdown Timer SPMB -->
        <?php if ($show_countdown): ?>
        <section class="mb-8 text-center" id="brosur-countdown-section">
            <div class="glass-card rounded-3xl p-5 sm:p-7 border border-amber-500/30 shadow-xl relative overflow-hidden bg-gradient-to-b from-white/95 to-emerald-50/90 backdrop-blur-xl">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-amber-400/15 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

                <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-100 border border-amber-300 text-amber-900 text-[11px] font-extrabold uppercase tracking-wider mb-2 shadow-sm">
                    <i class="fas fa-hourglass-half text-amber-600 animate-pulse"></i>
                    <span>Batas Akhir <?= $periode_gelombang ?></span>
                </div>

                <h3 id="cd-text" class="text-emerald-950 font-black text-base sm:text-lg mb-4 tracking-tight">
                    <?= $countdown_title ?>
                </h3>

                <!-- KOTAK COUNTDOWN -->
                <div class="flex justify-center items-center space-x-2 sm:space-x-4">
                    <div class="flex flex-col items-center">
                        <div class="bg-emerald-900 text-amber-300 font-black text-2xl sm:text-3xl w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center rounded-2xl shadow-[0_6px_0_0_rgba(6,78,69,1)] border-2 border-emerald-700/60 font-mono tracking-tight" id="cd-hari">
                            00
                        </div>
                        <span class="text-emerald-950 text-[10px] sm:text-xs mt-2.5 font-extrabold uppercase tracking-widest">Hari</span>
                    </div>

                    <div class="text-emerald-800 font-black text-xl sm:text-2xl -mt-5">:</div>

                    <div class="flex flex-col items-center">
                        <div class="bg-emerald-900 text-amber-300 font-black text-2xl sm:text-3xl w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center rounded-2xl shadow-[0_6px_0_0_rgba(6,78,69,1)] border-2 border-emerald-700/60 font-mono tracking-tight" id="cd-jam">
                            00
                        </div>
                        <span class="text-emerald-950 text-[10px] sm:text-xs mt-2.5 font-extrabold uppercase tracking-widest">Jam</span>
                    </div>

                    <div class="text-emerald-800 font-black text-xl sm:text-2xl -mt-5">:</div>

                    <div class="flex flex-col items-center">
                        <div class="bg-emerald-900 text-amber-300 font-black text-2xl sm:text-3xl w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center rounded-2xl shadow-[0_6px_0_0_rgba(6,78,69,1)] border-2 border-emerald-700/60 font-mono tracking-tight" id="cd-menit">
                            00
                        </div>
                        <span class="text-emerald-950 text-[10px] sm:text-xs mt-2.5 font-extrabold uppercase tracking-widest">Menit</span>
                    </div>

                    <div class="text-emerald-800 font-black text-xl sm:text-2xl -mt-5">:</div>

                    <div class="flex flex-col items-center">
                        <div class="bg-white text-rose-600 font-black text-2xl sm:text-3xl w-14 h-14 sm:w-16 sm:h-16 flex items-center justify-center rounded-2xl shadow-[0_6px_0_0_rgba(225,29,72,1)] border-2 border-rose-200 font-mono tracking-tight animate-pulse" id="cd-detik">
                            00
                        </div>
                        <span class="text-rose-700 text-[10px] sm:text-xs mt-2.5 font-extrabold uppercase tracking-widest">Detik</span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-emerald-100/80 flex items-center justify-center gap-1.5 text-[11px] text-gray-500">
                    <i class="fas fa-bolt text-amber-500"></i>
                    <span>Kuota santri terbatas. Segera amankan pendaftaran sebelum periode ini ditutup.</span>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- MENU 7: TESTIMONI WALISANTRI               -->
        <!-- ========================================== -->
        <section id="brosur-testimoni" class="scroll-mt-6 mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-700 text-white flex items-center justify-center font-bold text-sm shadow">
                    <i class="fas fa-comments text-xs"></i>
                </span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Testimoni Walisantri</h2>
                    <p class="text-xs text-gray-500">Kisah nyata transformasi putra-putri di Villa Quran</p>
                </div>
            </div>

            <div class="space-y-3.5">
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm relative">
                    <div class="flex items-center gap-3 mb-2.5">
                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-xs sm:text-sm text-emerald-950">Bpk. Hendra Gunawan</h4>
                            <p class="text-[10px] text-gray-500">Wali Santri SMP — Asal Surabaya</p>
                        </div>
                        <div class="ml-auto flex text-amber-400 text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-700 italic leading-relaxed">
                        "Alhamdulillah baru 8 bulan di Villa Quran, anak saya sudah menyelesaikan 6 juz mutqin dengan tajwid yang sangat rapi. Saat pulang liburan dia selalu bangun qiyamul lail sendiri tanpa perlu dibangunkan."
                    </p>
                </div>

                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm relative">
                    <div class="flex items-center gap-3 mb-2.5">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-xs sm:text-sm text-emerald-950">Ibu dr. Nurul Aini</h4>
                            <p class="text-[10px] text-gray-500">Wali Santri SMA — Asal Jakarta</p>
                        </div>
                        <div class="ml-auto flex text-amber-400 text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-700 italic leading-relaxed">
                        "Konsepnya sangat solutif. Anak kami tidak hanya hafal Al-Qur'an dan mengantongi ijazah resmi negara, tapi juga diajari AI dan digital marketing. Karakternya mandiri dan siap menghadapi tantangan zaman modern."
                    </p>
                </div>
            </div>
        </section>

        <!-- Google Maps Lokasi -->
        <?php if ($show_maps): ?>
        <section class="mb-8" id="brosur-maps-section">
            <div class="glass-card rounded-3xl p-5 shadow-lg border border-emerald-100 flex flex-col items-center">
                <div class="flex items-start gap-3 mb-4 w-full">
                    <i class="fas fa-map-marker-alt text-amber-600 text-xl flex-shrink-0 mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-xs sm:text-sm text-emerald-950">Kampus Villa Quran Indonesia</h4>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            Kawasan Asri Pegunungan, Suasana Sejuk & Nyaman ala Villa (Akses kendaraan roda 2 dan roda 4 mudah dijangkau).
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl overflow-hidden border border-gray-200 mb-3 shadow-inner transition-all mx-auto" style="width: <?= $maps_width ?>%; height: <?= $maps_height ?>px; max-width: 100%;">
                    <iframe src="<?= htmlspecialchars(!empty($maps_url) ? $maps_url : 'https://maps.google.com/maps?q=Villa+Quran+Indonesia&t=&z=14&ie=UTF8&iwloc=&output=embed') ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>

                <a href="https://maps.google.com/maps?q=Villa+Quran+Indonesia" target="_blank" class="w-full bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i class="fas fa-location-arrow text-emerald-700"></i> Buka Rute di Google Maps
                </a>
            </div>
        </section>
        <?php endif; ?>

        <!-- ========================================== -->
        <!-- MENU 8: FORMULIR PENDAFTARAN & RESERVASI   -->
        <!-- ========================================== -->
        <section id="brosur-formulir" class="scroll-mt-6 mb-12">
            <div class="rounded-3xl p-6 sm:p-7 bg-gradient-to-br from-[#064e45] via-[#043d36] to-[#022823] text-white shadow-2xl border border-amber-500/40 relative overflow-hidden">
                <div class="absolute -top-12 -right-12 w-44 h-44 bg-amber-400/10 rounded-full blur-2xl"></div>

                <div class="text-center mb-6">
                    <span class="inline-block px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[11px] font-extrabold uppercase tracking-wider mb-2">
                        Formulir Silaturahmi & Reservasi
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Amankan Kuota Santri Ananda</h2>
                    <p class="text-xs text-emerald-100/90 mt-1 max-w-sm mx-auto">
                        Kuota santri dibatasi maksimal 20 santri per angkatan untuk menjaga kualitas talaqqi intensif.
                    </p>
                </div>

                <form id="form-reservasi" onsubmit="submitReservasi(event)" class="space-y-4 text-left">
                    <input type="hidden" name="kode_ref" id="input-ref" value="<?= $kode_ref ?>">

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Nama Ayah / Ibu (Wali) <span class="text-red-400">*</span></label>
                        <input type="text" id="reg-nama-wali" name="nama_wali" required placeholder="Contoh: Bpk. Hendy Pratama" value="<?= ($nama_tamu !== 'Bapak / Ibu Calon Wali Santri & Keluarga') ? $nama_tamu : '' ?>" class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Nomor WhatsApp Aktif <span class="text-red-400">*</span></label>
                        <input type="tel" id="reg-wa" name="whatsapp" required placeholder="Contoh: 081234567890" class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 text-sm">
                        <span class="text-[10px] text-gray-300 mt-0.5 block"><i class="fab fa-whatsapp text-emerald-400 mr-1"></i> Notifikasi otomatis & E-Brosur resmi dikirim ke nomor ini</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Nama Calon Santri (Ananda) <span class="text-red-400">*</span></label>
                        <input type="text" id="reg-nama-santri" name="nama_santri" required placeholder="Contoh: Muhammad Al-Fatih" class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Pilihan Jenjang <span class="text-red-400">*</span></label>
                            <select id="reg-jenjang" name="jenjang" required class="w-full px-3 py-2.5 rounded-xl bg-emerald-950 border border-white/20 text-white text-xs sm:text-sm focus:outline-none focus:border-amber-400">
                                <option value="SMP Tahfidz">SMP (Setara)</option>
                                <option value="SMA Tahfidz & Solopreneur">SMA (Setara)</option>
                                <option value="Takhassus 30 Juz">Takhassus 30 Juz</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Kota Asal / Domisili <span class="text-red-400">*</span></label>
                            <input type="text" id="reg-kota" name="kota" required placeholder="Contoh: Surabaya" class="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Catatan / Rencana Silaturahmi (Opsional)</label>
                        <textarea id="reg-catatan" name="catatan" rows="2" placeholder="Contoh: Ingin menjadwalkan kunjungan survei pesantren hari Ahad ini..." class="w-full px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs"></textarea>
                    </div>

                    <button type="submit" id="btn-submit-reservasi" class="w-full bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-emerald-950 font-black py-3.5 px-6 rounded-2xl shadow-xl transition-all transform active:scale-95 flex items-center justify-center gap-2 text-sm sm:text-base mt-2 gold-glow">
                        <i class="fas fa-paper-plane"></i>
                        <span>Kirim Reservasi & Dapatkan E-Brosur</span>
                    </button>
                    
                    <p class="text-[10px] text-gray-300 text-center mt-2 flex items-center justify-center gap-1">
                        <i class="fas fa-lock text-amber-400"></i> Data terlindungi & otomatis tersinkron ke WA Panitia SPMB
                    </p>
                </form>

                <div id="sukses-reservasi-modal" class="hidden bg-emerald-900 border border-amber-400/50 rounded-2xl p-5 text-center mt-4 animate-fade-in">
                    <div class="w-14 h-14 rounded-full bg-amber-400 text-emerald-950 flex items-center justify-center mx-auto mb-3 text-2xl shadow-lg">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-amber-300 mb-1">Alhamdulillah, Data Diterima!</h3>
                    <p class="text-xs text-emerald-100 leading-relaxed mb-4" id="sukses-modal-pesan">
                        Terima kasih Bapak/Ibu. Notifikasi konfirmasi dan link e-brosur resmi telah kami kirimkan ke nomor WhatsApp Anda.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-2.5">
                        <a id="btn-wa-direct" href="#" target="_blank" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 shadow">
                            <i class="fab fa-whatsapp text-sm"></i> Buka WhatsApp Sekarang
                        </a>
                        <a href="upload/logo-villa-quran.png" download class="flex-1 bg-white/10 hover:bg-white/20 text-amber-300 py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 border border-white/20">
                            <i class="fas fa-file-download"></i> Unduh E-Brosur
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- FOOTER -->
        <footer class="text-center text-xs text-gray-500 pt-6 border-t border-gray-200 space-y-3">
            <p>&copy; <?= date('Y') ?> <strong><?= $judul_utama ?></strong>. All Rights Reserved.</p>
            <p class="text-[11px] text-gray-400">Mencetak Generasi Hafidz Mutqin Bersanad, Berijazah Resmi Negara & Berjiwa Solopreneur.</p>
        </footer>

    </div>

    <!-- ============================================================ -->
    <!-- STICKY MOBILE BOTTOM BAR: 8 CARD MENUS (4 MENUS DEFAULT)    -->
    <!-- DAPAT DIGESER KANAN-KIRI DENGAN HALUS                        -->
    <!-- ============================================================ -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-emerald-100 shadow-[0_-8px_30px_rgba(0,0,0,0.12)] py-2 px-1">
        <div class="max-w-xl mx-auto">
            
            <!-- CAROUSEL TRACK: 4 CARDS PER SCREEN BY DEFAULT -->
            <div id="brosur-bottom-track" class="flex items-center overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory gap-1.5 px-1 py-0.5">
                
                <!-- 1. Home -->
                <a href="#brosur-home" onclick="navigasiKeSection('brosur-home', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-950 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-emerald-700 text-amber-300 flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-house"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Home</span>
                </a>

                <!-- 2. Mengapa VQBM -->
                <a href="#brosur-mengapa" onclick="navigasiKeSection('brosur-mengapa', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 text-slate-800 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-amber-500 text-emerald-950 flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-heart"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Mengapa VQBM</span>
                </a>

                <!-- 3. Target Kompetensi -->
                <a href="#brosur-kompetensi" onclick="navigasiKeSection('brosur-kompetensi', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 text-slate-800 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Target Kompetensi</span>
                </a>

                <!-- 4. Fasilitas -->
                <a href="#brosur-fasilitas" onclick="navigasiKeSection('brosur-fasilitas', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 text-slate-800 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-teal-600 text-white flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-hotel"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Fasilitas</span>
                </a>

                <!-- 5. Dewan Pengasuh -->
                <a href="#brosur-pengasuh" onclick="navigasiKeSection('brosur-pengasuh', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 text-slate-800 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Dewan Pengasuh</span>
                </a>

                <!-- 6. Investasi -->
                <a href="#brosur-biaya" onclick="navigasiKeSection('brosur-biaya', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 text-slate-800 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-amber-600 text-white flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Investasi</span>
                </a>

                <!-- 7. Testimoni -->
                <a href="#brosur-testimoni" onclick="navigasiKeSection('brosur-testimoni', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-white hover:bg-emerald-50 border border-slate-200 text-slate-800 flex flex-col items-center justify-center text-center transition group active:scale-95 shadow-xs">
                    <div class="w-7 h-7 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-comments"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Testimoni</span>
                </a>

                <!-- 8. Formulir -->
                <a href="#brosur-formulir" onclick="navigasiKeSection('brosur-formulir', event)" class="bottom-card-menu-item snap-start p-1.5 rounded-2xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 text-emerald-950 border border-amber-300 flex flex-col items-center justify-center text-center transition group shadow-md active:scale-95">
                    <div class="w-7 h-7 rounded-xl bg-emerald-950 text-amber-300 flex items-center justify-center text-xs mb-1 shadow-xs group-hover:scale-110 transition">
                        <i class="fas fa-file-pen"></i>
                    </div>
                    <span class="text-[9px] font-black leading-tight truncate w-full">Formulir</span>
                </a>

            </div>

            <!-- CONTROLS GESER & INDIKATOR -->
            <div class="flex items-center justify-between px-3 pt-1 text-[9px] text-slate-400 border-t border-slate-100 mt-1">
                <button type="button" onclick="scrollBrosurBottomBar('left')" class="hover:text-emerald-700 font-bold flex items-center gap-1 transition">
                    <i class="fas fa-chevron-left text-[8px]"></i> <span>Geser Kiri</span>
                </button>
                <div class="flex items-center gap-1" id="indicator-dots">
                    <span class="w-2.5 h-1 rounded-full bg-emerald-600"></span>
                    <span class="w-1.5 h-1 rounded-full bg-slate-300"></span>
                </div>
                <button type="button" onclick="scrollBrosurBottomBar('right')" class="hover:text-emerald-700 font-bold flex items-center gap-1 transition">
                    <span>Geser Kanan</span> <i class="fas fa-chevron-right text-[8px]"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- JAVASCRIPT LOGIC                                             -->
    <!-- ============================================================ -->
    <script>
        const audio = document.getElementById('audio-backsound');
        const audioControl = document.getElementById('audio-control');
        const audioIcon = document.getElementById('audio-icon');
        const audioLabel = document.getElementById('audio-label');
        let isAudioPlaying = false;

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const refFromUrl = urlParams.get('ref');
            if (refFromUrl) {
                localStorage.setItem('agen_ref', refFromUrl);
                const inputRef = document.getElementById('input-ref');
                if (inputRef) inputRef.value = refFromUrl;
            } else {
                const storedRef = localStorage.getItem('agen_ref');
                if (storedRef && document.getElementById('input-ref')) {
                    document.getElementById('input-ref').value = storedRef;
                }
            }
        });

        function bukaUndangan() {
            const cover = document.getElementById('envelope-cover');
            cover.classList.add('cover-hidden');

            audio.play().then(() => {
                isAudioPlaying = true;
                audioControl.classList.remove('hidden');
                updateAudioUI();
            }).catch(e => {
                console.log("Audio autoplay prevented, user can click floating button:", e);
                audioControl.classList.remove('hidden');
            });
        }

        function toggleAudio() {
            if (isAudioPlaying) {
                audio.pause();
                isAudioPlaying = false;
            } else {
                audio.play();
                isAudioPlaying = true;
            }
            updateAudioUI();
        }

        function updateAudioUI() {
            if (isAudioPlaying) {
                audioIcon.classList.add('animate-spin-slow');
                audioIcon.innerHTML = '<i class="fas fa-music text-[10px]"></i>';
                audioLabel.innerText = "Audio On";
            } else {
                audioIcon.classList.remove('animate-spin-slow');
                audioIcon.innerHTML = '<i class="fas fa-volume-mute text-[10px]"></i>';
                audioLabel.innerText = "Audio Off";
            }
        }

        // Navigasi Smooth Scroll untuk 8 Menu Bottom Bar
        function navigasiKeSection(sectionId, e) {
            if (e) e.preventDefault();
            const el = document.getElementById(sectionId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Geser Bottom Bar Carousel Kanan / Kiri
        function scrollBrosurBottomBar(direction) {
            const track = document.getElementById('brosur-bottom-track');
            if (!track) return;
            const scrollDistance = track.clientWidth * 0.75;
            if (direction === 'left') {
                track.scrollBy({ left: -scrollDistance, behavior: 'smooth' });
            } else {
                track.scrollBy({ left: scrollDistance, behavior: 'smooth' });
            }
        }

        // Handle AJAX Submit Reservasi
        function submitReservasi(e) {
            e.preventDefault();
            const form = document.getElementById('form-reservasi');
            const btn = document.getElementById('btn-submit-reservasi');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim Data...';

            const formData = new FormData(form);

            fetch('simpan-brosur.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Kirim Reservasi & Dapatkan E-Brosur';

                if (data.status === 'success') {
                    document.getElementById('sukses-modal-pesan').innerText = data.message;
                    if (data.wa_link) {
                        document.getElementById('btn-wa-direct').href = data.wa_link;
                    }
                    document.getElementById('sukses-reservasi-modal').classList.remove('hidden');
                    form.reset();
                } else {
                    alert("Mohon maaf: " + (data.message || 'Terjadi kesalahan sistem'));
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Kirim Reservasi & Dapatkan E-Brosur';
                alert("Terjadi kesalahan koneksi internet.");
            });
        }

        // ==========================================
        // COUNTDOWN TIMER LOGIC
        // ==========================================
        <?php if ($show_countdown): ?>
        (function() {
            const mode = "<?= $countdown_mode ?>";
            let endDate;

            if (mode === 'custom') {
                endDate = new Date("<?= str_replace(' ', 'T', $countdown_target) ?>");
            } else {
                const now = new Date();
                const year = now.getFullYear();
                const month = now.getMonth() + 1;
                if (month >= 7 && month <= 12) {
                    endDate = new Date(year, 11, 31, 23, 59, 59);
                } else if (month >= 1 && month <= 3) {
                    endDate = new Date(year, 2, 31, 23, 59, 59);
                } else {
                    endDate = new Date(year, 5, 30, 23, 59, 59);
                }
            }

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = endDate.getTime() - now;

                if (distance < 0) {
                    if (document.getElementById('cd-hari')) document.getElementById('cd-hari').innerText = "00";
                    if (document.getElementById('cd-jam')) document.getElementById('cd-jam').innerText = "00";
                    if (document.getElementById('cd-menit')) document.getElementById('cd-menit').innerText = "00";
                    if (document.getElementById('cd-detik')) document.getElementById('cd-detik').innerText = "00";
                    return;
                }

                const d = Math.floor(distance / (1000 * 60 * 60 * 24));
                const h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const s = Math.floor((distance % (1000 * 60)) / 1000);

                if (document.getElementById('cd-hari')) document.getElementById('cd-hari').innerText = d.toString().padStart(2, '0');
                if (document.getElementById('cd-jam')) document.getElementById('cd-jam').innerText = h.toString().padStart(2, '0');
                if (document.getElementById('cd-menit')) document.getElementById('cd-menit').innerText = m.toString().padStart(2, '0');
                if (document.getElementById('cd-detik')) document.getElementById('cd-detik').innerText = s.toString().padStart(2, '0');
            }

            setInterval(updateCountdown, 1000);
            updateCountdown();
        })();
        <?php endif; ?>
    </script>
</body>
</html>
