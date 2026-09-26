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
    "maps_height"          => "ALTER TABLE pengaturan_brosur ADD COLUMN maps_height INT DEFAULT 220 AFTER maps_width",
    "bottom_bar_bg_color"  => "ALTER TABLE pengaturan_brosur ADD COLUMN bottom_bar_bg_color VARCHAR(100) DEFAULT '#022d27' AFTER maps_height",
    "bottom_bar_text_color"=> "ALTER TABLE pengaturan_brosur ADD COLUMN bottom_bar_text_color VARCHAR(30) DEFAULT '#ffffff' AFTER bottom_bar_bg_color"
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

// Bottom Bar Kustomisasi Warna
$bottom_bar_bg_color   = !empty($cfg_brosur['bottom_bar_bg_color']) ? htmlspecialchars($cfg_brosur['bottom_bar_bg_color']) : '#022d27';
$bottom_bar_text_color = !empty($cfg_brosur['bottom_bar_text_color']) ? htmlspecialchars($cfg_brosur['bottom_bar_text_color']) : '#ffffff';

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
$biaya_pangkal     = isset($cfg_brosur['biaya_pangkal']) ? number_format($cfg_brosur['biaya_pangkal'], 0, ',', '.') : '13.500.000';
$biaya_tahunan     = isset($cfg_brosur['biaya_tahunan']) ? number_format($cfg_brosur['biaya_tahunan'], 0, ',', '.') : '3.500.000';
$biaya_spp         = isset($cfg_brosur['biaya_spp']) ? number_format($cfg_brosur['biaya_spp'], 0, ',', '.') : '1.500.000';
$diskon_gelombang  = isset($cfg_brosur['diskon_gelombang']) ? number_format($cfg_brosur['diskon_gelombang'], 0, ',', '.') : '2.000.000';

// Ambil Data Sinkron dari Database Web (Fasilitas, Pengajar, Kegiatan Santri, Biaya, Testimoni)
// 1. Fasilitas
$data_fasilitas = [];
$q_fas = $conn->query("SELECT * FROM fasilitas ORDER BY id ASC");
if ($q_fas && $q_fas->num_rows > 0) while ($rf = $q_fas->fetch_assoc()) $data_fasilitas[] = $rf;

// 2. Dewan Pengasuh & Pengajar
$data_pengajar = [];
$q_peng = $conn->query("SELECT * FROM pengajar ORDER BY id ASC");
if ($q_peng && $q_peng->num_rows > 0) while ($rp = $q_peng->fetch_assoc()) $data_pengajar[] = $rp;

// 3. Kegiatan Santri (Galeri)
$data_kegiatan = [];
$q_keg = $conn->query("SELECT * FROM galeri ORDER BY id DESC LIMIT 8");
if ($q_keg && $q_keg->num_rows > 0) while ($rk = $q_keg->fetch_assoc()) $data_kegiatan[] = $rk;

// 4. Investasi / Komponen Biaya (Sinkron Langsung dari Tabel 'biaya' / Pengaturan Info Biaya)
$data_biaya = ['pendaftaran' => [], 'pangkal' => [], 'tahunan' => [], 'spp' => []];
$subtotal_biaya = ['pendaftaran' => 0, 'pangkal' => 0, 'tahunan' => 0, 'spp' => 0];
$q_b = $conn->query("SELECT * FROM biaya ORDER BY id ASC");
if ($q_b && $q_b->num_rows > 0) {
    while ($rb = $q_b->fetch_assoc()) {
        $k = strtolower(trim($rb['kategori']));
        if (isset($data_biaya[$k])) {
            $data_biaya[$k][] = $rb;
            $subtotal_biaya[$k] += (int)$rb['nominal'];
        }
    }
}

// Sinkronkan nominal biaya Brosur dengan total komponen di Pengaturan Info Biaya (sebagai acuan utama)
if ($subtotal_biaya['pendaftaran'] > 0) $biaya_pendaftaran = number_format($subtotal_biaya['pendaftaran'], 0, ',', '.');
if ($subtotal_biaya['pangkal'] > 0)     $biaya_pangkal     = number_format($subtotal_biaya['pangkal'], 0, ',', '.');
if ($subtotal_biaya['tahunan'] > 0)     $biaya_tahunan     = number_format($subtotal_biaya['tahunan'], 0, ',', '.');
if ($subtotal_biaya['spp'] > 0)         $biaya_spp         = number_format($subtotal_biaya['spp'], 0, ',', '.');

// Ringkasan nama komponen rincian dinamis
$desc_pendaftaran = !empty($data_biaya['pendaftaran']) ? implode(' • ', array_map(function($x){ return $x['nama_komponen']; }, $data_biaya['pendaftaran'])) : 'Formulir SPMB & observasi calon santri';
$desc_pangkal     = !empty($data_biaya['pangkal']) ? implode(' • ', array_map(function($x){ return $x['nama_komponen']; }, $data_biaya['pangkal'])) : 'Ranjang kasur empuk, lemari, seragam 4 stel & modul';
$desc_tahunan     = !empty($data_biaya['tahunan']) ? implode(' • ', array_map(function($x){ return $x['nama_komponen']; }, $data_biaya['tahunan'])) : 'Karantina tahfidz, ekstrakurikuler & sarana';
$desc_spp         = !empty($data_biaya['spp']) ? implode(' • ', array_map(function($x){ return $x['nama_komponen']; }, $data_biaya['spp'])) : 'Makan 3x sehari bergizi, asrama AC, laundry & bimbingan';

// 5. Testimoni
$data_testimoni = [];
$q_testi = $conn->query("SELECT * FROM testimoni ORDER BY id DESC");
if ($q_testi && $q_testi->num_rows > 0) while ($rt = $q_testi->fetch_assoc()) $data_testimoni[] = $rt;

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

        .bg-pattern {
            <?php if (!empty($body_bg_url)): ?>
            background-image: linear-gradient(rgba(246, 247, 245, <?= $body_overlay_opacity ?>), rgba(246, 247, 245, <?= $body_overlay_opacity ?>)), url('<?= $body_bg_url ?>');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            <?php endif; ?>
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
            cursor: pointer;
            user-select: none;
        }

        /* 1 Menu = 1 Halaman Ukuran Layar HP (Full Viewport Screen) */
        .page-screen-mobile {
            min-height: 100dvh;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            scroll-margin-top: 0;
            padding-top: 2rem;
            padding-bottom: 6rem;
            position: relative;
        }
    </style>
</head>
<body class="bg-pattern antialiased">

    <!-- ============================================================ -->
    <!-- 1. FULLSCREEN COVER AMPLOP DIGITAL (OPENING EXPERIENCE)      -->
    <!-- ============================================================ -->
    <div id="envelope-cover" class="fixed inset-0 z-50 flex items-center justify-center text-white overflow-hidden bg-cover bg-center transition-all duration-700 select-none" style="background-image: url('<?= $cover_bg_url ?>'); font-family: '<?= $font_family ?>', sans-serif;">
        
        <!-- Ornamen Latar & Overlay Dinamis Sesuai Persis Simulasi Admin -->
        <div class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300 pointer-events-none" style="opacity: <?= $cover_opacity ?>;"></div>

        <!-- Frame Proporsional Layar Cover (Sama Persis Tampilan Layar Simulasi HP 320px) -->
        <div class="relative w-full max-w-[320px] h-full mx-auto p-4 text-center overflow-hidden select-none">
            
            <!-- BLOK 1: HEADER LOGO, BISMILLAH, JUDUL (POSISI Y DINAMIS SESUAI SIMULASI) -->
            <div id="cover-elem-header" class="absolute left-1/2 -translate-x-1/2 z-20 text-center w-full px-2 transition-all duration-300" style="top: <?= $header_y ?>%;">
                
                <!-- Bismillah -->
                <?php if (!empty($show_bismillah)): ?>
                <p class="font-arabic text-sm transition-colors" style="color: <?= $accent_color ?>;">
                    <?= $bismillah_text ?>
                </p>
                <?php endif; ?>
                
                <!-- Gambar Logo (Bulat Sempurna sesuai Lingkaran Logo) -->
                <?php if (!empty($show_logo)): ?>
                <div class="my-1" id="cover-logo-block">
                    <div class="rounded-full p-0.5 bg-white border-2 border-emerald-600 shadow-md overflow-hidden aspect-square mx-auto flex items-center justify-center transition-all" style="width: <?= round($logo_size * 0.55) ?>px; height: <?= round($logo_size * 0.55) ?>px;">
                        <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-full h-full object-cover rounded-full pointer-events-none">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Judul Utama -->
                <h3 class="font-black mt-1 transition-all leading-tight" style="color: <?= $text_color ?>; font-size: <?= round($text_title_size * 0.7) ?>px;">
                    <?= $judul_utama ?>
                </h3>

                <!-- Subjudul -->
                <?php if (!empty($show_subjudul)): ?>
                <p class="text-[9px] font-medium transition-all" style="color: <?= $accent_color ?>; font-size: <?= round($text_sub_size * 0.8) ?>px;">
                    <?= $subjudul ?>
                </p>
                <?php endif; ?>

                <p class="text-[8px] font-bold uppercase tracking-wider mt-0.5 transition-colors" style="color: <?= $accent_color ?>;">
                    <?= $periode_gelombang ?>
                </p>
            </div>

            <!-- BLOK 2: KARTU TAMU CALON WALI (POSISI Y DINAMIS SESUAI SIMULASI) -->
            <div id="cover-elem-guest" class="absolute left-1/2 -translate-x-1/2 z-20 border rounded-2xl backdrop-blur-md shadow-lg transition-all text-center <?= ($card_bg_style === 'glass_light') ? 'bg-white/85 border-emerald-600/40 text-slate-800' : 'bg-black/45 border-amber-400/40 text-white' ?>" style="top: <?= $guest_y ?>%; width: <?= $card_width ?>%; padding: <?= round($card_padding * 0.6) ?>px;">
                <span class="text-[8px] uppercase tracking-wider font-bold block" style="color: <?= $accent_color ?>;">
                    <?= $tamu_header_text ?>
                </span>
                <div class="text-xs font-black mt-0.5 transition-colors" style="color: <?= ($card_bg_style === 'glass_light') ? '#0f172a' : '#ffffff' ?>;">
                    <?= $nama_tamu ?>
                </div>
                <?php if (!empty($nama_agen_pengundang)): ?>
                    <p class="text-[8px] mt-0.5 italic" style="color: <?= $accent_color ?>;"><i class="fas fa-hand-holding-heart mr-1"></i> Rekomendasi: <?= htmlspecialchars($nama_agen_pengundang) ?></p>
                <?php endif; ?>
                
                <!-- Teks Sambutan Tamu -->
                <?php if (!empty($show_sambutan) && !empty($tamu_sambutan_text)): ?>
                <p class="text-[8px] mt-0.5 leading-tight opacity-90 transition-all">
                    <?= nl2br(htmlspecialchars($tamu_sambutan_text)) ?>
                </p>
                <?php endif; ?>

                <div class="mt-1.5 pt-1.5 border-t border-white/10 text-[8px] font-semibold" style="color: <?= $accent_color ?>;">
                    Tahun Ajaran <?= $tahun_ajaran ?>
                </div>
            </div>

            <!-- BLOK 3: TOMBOL BUKA UNDANGAN (POSISI Y DINAMIS SESUAI SIMULASI) -->
            <div id="cover-elem-btn" class="absolute left-1/2 -translate-x-1/2 z-20 flex flex-col items-center w-full px-2 transition-all duration-300" style="top: <?= $btn_y ?>%;">
                <button type="button" onclick="bukaUndangan()" class="rounded-xl font-black text-xs flex items-center justify-center gap-1.5 shadow-lg active:scale-95 transition-all" style="width: <?= $btn_width ?>%; height: <?= round($btn_height * 0.8) ?>px; background: <?= $btn_bg_color ?>; color: <?= $btn_text_color ?>;">
                    <i class="fas fa-envelope-open-text text-[10px]"></i>
                    <span><?= $btn_text ?></span>
                </button>
                <p class="text-[8px] mt-1 text-center opacity-80" style="color: <?= $text_color ?>;">
                    <i class="fas fa-music mr-1"></i> Alunan Backsound Syahdu
                </p>
            </div>

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
    <div id="main-content" class="max-w-md mx-auto px-4 pt-2 pb-28 min-h-screen snap-y snap-mandatory scroll-smooth">

        <!-- ========================================== -->
        <!-- MENU 1: HOME (1 HALAMAN PENUH LAYAR HP)    -->
        <!-- ========================================== -->
        <section id="brosur-home" class="page-screen-mobile snap-start text-center">
            <div class="glass-card rounded-3xl p-6 sm:p-8 border border-emerald-100 shadow-xl my-auto text-center space-y-4">
                
                <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-black shadow-xs">
                    <i class="fas fa-certificate text-amber-600"></i>
                    <span>Tahun Ajaran <?= $tahun_ajaran ?> &bull; <?= $periode_gelombang ?></span>
                </div>
                
                <!-- Logo Bulat Lingkaran Sempurna -->
                <div class="w-20 h-20 rounded-full mx-auto p-1 bg-white border-2 border-emerald-600 shadow-lg overflow-hidden aspect-square flex items-center justify-center">
                    <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-full h-full object-cover rounded-full">
                </div>

                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-emerald-950 tracking-tight leading-tight"><?= $judul_utama ?></h1>
                    <p class="text-sm text-emerald-700 font-bold mt-1"><?= $subjudul ?></p>
                </div>

                <div class="w-20 h-1 bg-gradient-to-r from-amber-400 to-amber-600 mx-auto rounded-full"></div>

                <!-- Kartu Undangan Tamu -->
                <div class="p-4 rounded-2xl bg-emerald-50/80 border border-emerald-200/60 text-center">
                    <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider block"><?= $tamu_header_text ?></span>
                    <h3 class="text-base sm:text-lg font-black text-emerald-950 mt-0.5"><?= $nama_tamu ?></h3>
                    <p class="text-xs text-emerald-800/80 mt-1 leading-relaxed"><?= $tamu_sambutan_text ?></p>
                </div>

                <p class="text-xs text-slate-400 italic">
                    <i class="fas fa-arrow-down animate-bounce text-amber-500 mr-1"></i> Geser layar ke bawah atau sentuh menu di bawah untuk melihat halaman berikutnya
                </p>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 2: MENGAPA VQBM (1 HALAMAN PENUH HP)  -->
        <!-- ========================================== -->
        <section id="brosur-mengapa" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 border border-emerald-100 shadow-xl my-auto space-y-4">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-amber-500 text-emerald-950 flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-heart"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-700">Pilar Utama & Keunggulan</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Mengapa Villa Quran?</h2>
                    </div>
                </div>

                <!-- 3 Pilar Keunggulan -->
                <div class="space-y-3">
                    <div class="rounded-2xl p-4 bg-gradient-to-br from-emerald-900 to-emerald-950 text-white shadow-md border border-emerald-700/50 flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-400 text-emerald-950 flex items-center justify-center font-bold text-base flex-shrink-0 shadow">
                            <i class="fas fa-book-quran"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white">1. Tahfidz Al-Qur'an Bersanad & Mutqin</h3>
                            <p class="text-xs text-emerald-100/90 mt-0.5 leading-relaxed">Talaqqi harian intensif, sanad qira'ah bersambung, dan bimbingan muroja'ah disiplin asatidz mukim.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl p-4 bg-gradient-to-br from-[#064e45] to-teal-950 text-white shadow-md border border-emerald-700/50 flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-400 text-emerald-950 flex items-center justify-center font-bold text-base flex-shrink-0 shadow">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white">2. Kurikulum Formal & Ijazah Negara (SMP/SMA)</h3>
                            <p class="text-xs text-emerald-100/90 mt-0.5 leading-relaxed">Legalitas ijazah resmi negara terakreditasi untuk tembus PTN, kedinasan, atau studi luar negeri.</p>
                        </div>
                    </div>

                    <div class="rounded-2xl p-4 bg-gradient-to-br from-amber-700 to-amber-950 text-white shadow-md border border-amber-600/50 flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-300 text-amber-950 flex items-center justify-center font-bold text-base flex-shrink-0 shadow">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-white">3. Ekosistem Solopreneur & AI Terapan</h3>
                            <p class="text-xs text-amber-100/90 mt-0.5 leading-relaxed">Praktek digital marketing, konten kreatif dakwah, e-commerce, dan penerapan AI untuk produktivitas.</p>
                        </div>
                    </div>
                </div>

                <!-- Video Profil / Suasana Pesantren -->
                <?php if ($show_video && !empty($video_url)): ?>
                <div class="glass-card rounded-2xl p-3 shadow-md border border-emerald-100">
                    <span class="text-xs font-bold text-emerald-950 block mb-2"><i class="fab fa-youtube text-red-600 mr-1.5"></i> Video Profil Suasana Pesantren</span>
                    <div class="rounded-xl overflow-hidden shadow-inner border border-gray-200 transition-all mx-auto" style="width: <?= $video_width ?>%; height: <?= min(200, $video_height) ?>px; max-width: 100%;">
                        <iframe src="<?= htmlspecialchars($video_url) ?>" class="w-full h-full" style="border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 3: TARGET KOMPETENSI (1 HALAMAN PENUH)-->
        <!-- ========================================== -->
        <section id="brosur-kompetensi" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 border border-emerald-100 shadow-xl my-auto space-y-4">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-bullseye"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-blue-700">Standar Mutu Lulusan</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Target Kompetensi Santri</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg mb-2.5">
                            <i class="fas fa-quran"></i>
                        </div>
                        <h3 class="font-bold text-sm text-emerald-950 mb-1">Mutqin 15 s/d 30 Juz Bersanad</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">Hafal kuat dengan standar tahsin fashahah, tajwid mutqin, dan sanad qira'ah bersambung.</p>
                    </div>

                    <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg mb-2.5">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="font-bold text-sm text-emerald-950 mb-1">Ijazah Formal SMP/SMA</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">Legalitas resmi negara terakreditasi, bebas tembus PTN, kedinasan & kampus luar negeri.</p>
                    </div>

                    <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg mb-2.5">
                            <i class="fas fa-laptop-code"></i>
                        </div>
                        <h3 class="font-bold text-sm text-emerald-950 mb-1">Kecakapan Solopreneur & AI</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">Keterampilan digital marketing, content creator dakwah, serta AI untuk produktivitas mandiri.</p>
                    </div>

                    <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm hover:shadow-md transition">
                        <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-lg mb-2.5">
                            <i class="fas fa-hands-holding-child"></i>
                        </div>
                        <h3 class="font-bold text-sm text-emerald-950 mb-1">Adab Luhur & Mandiri</h3>
                        <p class="text-xs text-gray-600 leading-relaxed">Pribadi sholeh berkarakter mandiri, disiplin ibadah harian, dan santun berbakti kepada orang tua.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 4: FASILITAS (1 HALAMAN PENUH - SINKRON WEB) -->
        <!-- ========================================== -->
        <section id="brosur-fasilitas" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 border border-emerald-100 shadow-xl my-auto space-y-4">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-hotel"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-teal-700">Sarana Asri Ala Villa</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Fasilitas Pesantren</h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[50vh] overflow-y-auto pr-0.5 no-scrollbar">
                    <?php if (!empty($data_fasilitas)): ?>
                        <?php foreach ($data_fasilitas as $fas): ?>
                            <div class="glass-card rounded-2xl overflow-hidden border border-emerald-100 shadow-sm hover:shadow-md transition flex flex-col">
                                <?php if (!empty($fas['gambar_url'])): ?>
                                    <div class="h-28 w-full overflow-hidden bg-slate-100">
                                        <img src="<?= htmlspecialchars($fas['gambar_url']) ?>" alt="<?= htmlspecialchars($fas['judul']) ?>" class="w-full h-full object-cover">
                                    </div>
                                <?php endif; ?>
                                <div class="p-3 flex-1 flex flex-col justify-between">
                                    <div>
                                        <h3 class="font-bold text-xs sm:text-sm text-emerald-950 mb-1"><?= htmlspecialchars($fas['judul']) ?></h3>
                                        <p class="text-[11px] text-gray-600 leading-relaxed line-clamp-3"><?= htmlspecialchars($fas['deskripsi']) ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm">
                            <h3 class="font-bold text-sm text-emerald-950 mb-1">Asrama Representatif & Kasur Nyaman</h3>
                            <p class="text-xs text-gray-600">Kamar asrama berkapasitas seimbang dengan ranjang kasur empuk, lemari pribadi, sirkulasi udara pegunungan.</p>
                        </div>
                        <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm">
                            <h3 class="font-bold text-sm text-emerald-950 mb-1">Masjid & Mushola Talaqqi 24 Jam</h3>
                            <p class="text-xs text-gray-600">Pusat halaqoh tahfidz Al-Qur'an bersanad, sholat berjamaah lima waktu, dan qiyamul lail syahdu.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Google Maps Frame jika diaktifkan -->
                <?php if ($show_maps): ?>
                <div class="glass-card rounded-2xl p-2.5 shadow-sm border border-emerald-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-location-dot text-emerald-600 text-base"></i>
                        <span class="text-xs font-bold text-emerald-950">Lokasi Kampus Pegunungan Asri</span>
                    </div>
                    <a href="https://maps.google.com/maps?q=Villa+Quran+Indonesia" target="_blank" class="px-3 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold rounded-lg text-[10px] transition">
                        Buka Maps &rarr;
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 5: DEWAN PENGASUH (1 HALAMAN PENUH - SINKRON WEB) -->
        <!-- ========================================== -->
        <section id="brosur-pengasuh" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 border border-emerald-100 shadow-xl my-auto space-y-4">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-purple-600 text-white flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-user-graduate"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-purple-700">Keteladanan & Pembinaan 24 Jam</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Dewan Pengasuh & Asatidz</h2>
                    </div>
                </div>

                <div class="space-y-3 max-h-[52vh] overflow-y-auto pr-0.5 no-scrollbar">
                    <?php if (!empty($data_pengajar)): ?>
                        <?php foreach ($data_pengajar as $p): ?>
                            <div class="glass-card rounded-2xl p-4 border border-purple-100 shadow-sm flex items-start gap-3.5">
                                <?php if (!empty($p['gambar_url'])): ?>
                                    <img src="<?= htmlspecialchars($p['gambar_url']) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="w-14 h-14 rounded-2xl object-cover border-2 border-purple-200 shadow-sm flex-shrink-0">
                                <?php else: ?>
                                    <div class="w-14 h-14 rounded-2xl bg-purple-100 text-purple-800 flex items-center justify-center text-xl font-bold flex-shrink-0"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                                <div class="overflow-hidden flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="font-black text-sm text-emerald-950"><?= htmlspecialchars($p['nama']) ?></h3>
                                        <?php if (!empty($p['teks_badge'])): ?>
                                            <span class="text-[9px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">
                                                <i class="<?= htmlspecialchars(!empty($p['ikon_badge']) ? $p['ikon_badge'] : 'fas fa-star') ?> text-[8px] mr-0.5"></i>
                                                <?= htmlspecialchars($p['teks_badge']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-purple-700 font-bold mt-0.5"><?= htmlspecialchars($p['jabatan'] ?? '') ?></p>
                                    <p class="text-[11px] text-gray-500 mt-0.5"><?= htmlspecialchars($p['almamater'] ?? '') ?></p>
                                    <?php if (!empty($p['prestasi'])): ?>
                                        <p class="text-[10px] text-gray-600 mt-1 line-clamp-2 italic">"<?= htmlspecialchars($p['prestasi']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm">
                            <h3 class="font-black text-sm text-emerald-950">Tim Asatidz Hafizh Mukim 24 Jam</h3>
                            <p class="text-xs text-gray-600 mt-1">Didampingi asatidz mukim bersanad Al-Qur'an 30 Juz yang tinggal bersama santri untuk membina kedisiplinan adab dan muraja'ah.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 6: KEGIATAN SANTRI (1 HALAMAN PENUH - SINKRON WEB) -->
        <!-- ========================================== -->
        <section id="brosur-kegiatan" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 border border-emerald-100 shadow-xl my-auto space-y-4">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-pink-600 text-white flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-camera-retro"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-pink-700">Dokumentasi & Potret Harian</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Kegiatan Santri</h2>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5 max-h-[52vh] overflow-y-auto pr-0.5 no-scrollbar">
                    <?php if (!empty($data_kegiatan)): ?>
                        <?php foreach ($data_kegiatan as $keg): ?>
                            <div class="rounded-2xl overflow-hidden shadow-sm relative group bg-emerald-50 border border-emerald-100 aspect-[4/3]">
                                <img src="<?= htmlspecialchars(!empty($keg['gambar_url']) ? $keg['gambar_url'] : 'upload/logo-villa-quran.png') ?>" alt="<?= htmlspecialchars($keg['judul'] ?? '') ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/30 to-transparent flex flex-col justify-end p-2.5">
                                    <h4 class="text-white font-black text-xs leading-tight"><?= htmlspecialchars($keg['judul'] ?? '') ?></h4>
                                    <?php if (!empty($keg['caption'])): ?>
                                        <p class="text-emerald-200 text-[9px] line-clamp-1 mt-0.5"><?= htmlspecialchars($keg['caption']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-2 glass-card rounded-2xl p-4 text-center text-xs text-gray-500">
                            Potret kegiatan harian santri: Halaqah Tahfidz, KBM Kelas, Olahraga Sunnah, dan Qiyamul Lail.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 7: INVESTASI PENDIDIKAN (1 HALAMAN PENUH - SINKRON WEB) -->
        <!-- ========================================== -->
        <section id="brosur-biaya" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 shadow-xl border border-emerald-100 my-auto space-y-3.5">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-amber-600 text-white flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-receipt"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-amber-700">Transparan & Terjangkau</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Investasi Pendidikan</h2>
                    </div>
                </div>

                <div class="divide-y divide-gray-100 text-xs sm:text-sm">
                    <div class="py-2.5 flex justify-between items-center">
                        <div class="pr-2">
                            <span class="font-bold text-gray-900 block">1. Biaya Pendaftaran & Observasi</span>
                            <span class="text-[10px] text-gray-500 line-clamp-1"><?= htmlspecialchars($desc_pendaftaran) ?></span>
                        </div>
                        <span class="font-black text-emerald-800 text-sm whitespace-nowrap">Rp <?= $biaya_pendaftaran ?></span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <div class="pr-2">
                            <span class="font-bold text-gray-900 block">2. Uang Pangkal Masuk</span>
                            <span class="text-[10px] text-gray-500 line-clamp-1"><?= htmlspecialchars($desc_pangkal) ?></span>
                        </div>
                        <span class="font-black text-emerald-800 text-sm whitespace-nowrap">Rp <?= $biaya_pangkal ?></span>
                    </div>

                    <div class="py-2.5 flex justify-between items-center">
                        <div class="pr-2">
                            <span class="font-bold text-gray-900 block">3. Biaya Pengembangan Tahunan</span>
                            <span class="text-[10px] text-gray-500 line-clamp-1"><?= htmlspecialchars($desc_tahunan) ?></span>
                        </div>
                        <span class="font-black text-emerald-800 text-sm whitespace-nowrap">Rp <?= $biaya_tahunan ?></span>
                    </div>

                    <div class="py-3 bg-emerald-50/80 -mx-5 px-5 rounded-2xl border border-emerald-200/60 flex justify-between items-center mt-2">
                        <div class="pr-2">
                            <span class="font-black text-emerald-950 block text-xs sm:text-sm">4. SPP All-in Per Bulan</span>
                            <span class="text-[10px] text-emerald-800 line-clamp-1"><?= htmlspecialchars($desc_spp) ?></span>
                        </div>
                        <div class="text-right whitespace-nowrap">
                            <span class="font-black text-emerald-800 text-sm sm:text-base">Rp <?= $biaya_spp ?></span>
                            <span class="block text-[9px] text-emerald-600">/ bulan</span>
                        </div>
                    </div>
                </div>

                <!-- Accordion Rincian Tiap Komponen (Sinkron Menu Pengaturan Info Biaya) -->
                <div class="pt-1">
                    <button type="button" onclick="const r = document.getElementById('brosur-biaya-detail'); r.classList.toggle('hidden'); this.querySelector('.arrow-icon').classList.toggle('rotate-180');" class="w-full py-2 px-3 rounded-xl bg-emerald-50/80 hover:bg-emerald-100 text-emerald-900 text-xs font-bold flex items-center justify-between border border-emerald-200/80 transition">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-list-ul text-emerald-600"></i>
                            <span>Rincian Tiap Komponen Biaya</span>
                        </span>
                        <i class="fas fa-chevron-down arrow-icon text-[10px] text-emerald-600 transition-transform duration-200"></i>
                    </button>
                    
                    <div id="brosur-biaya-detail" class="hidden mt-2 p-3 bg-white/95 rounded-2xl border border-emerald-100 text-xs space-y-3 max-h-52 overflow-y-auto shadow-inner">
                        <?php 
                        $list_kategori_label = [
                            'pendaftaran' => '1. Biaya Pendaftaran',
                            'pangkal'     => '2. Uang Pangkal',
                            'tahunan'     => '3. Biaya Tahunan',
                            'spp'         => '4. SPP Bulanan'
                        ];
                        foreach ($list_kategori_label as $k_kat => $l_kat): 
                            if (!empty($data_biaya[$k_kat])): ?>
                            <div class="border-b border-gray-100 pb-2 last:border-0 last:pb-0">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="font-extrabold text-[11px] text-emerald-900"><?= $l_kat ?></span>
                                    <span class="font-bold text-[10px] text-emerald-700 font-mono">Total: Rp <?= number_format($subtotal_biaya[$k_kat], 0, ',', '.') ?></span>
                                </div>
                                <div class="space-y-1 pl-1 text-[11px]">
                                    <?php foreach ($data_biaya[$k_kat] as $item): ?>
                                    <div class="flex justify-between items-center text-gray-600">
                                        <span>• <?= htmlspecialchars($item['nama_komponen']) ?></span>
                                        <span class="font-mono text-gray-800 font-medium">Rp <?= number_format($item['nominal'], 0, ',', '.') ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; 
                        endforeach; ?>
                        <div class="pt-1 text-center border-t border-gray-100">
                            <a href="biaya.html" target="_blank" class="text-[10px] font-bold text-emerald-700 hover:text-emerald-950 underline inline-flex items-center gap-1">
                                Lihat Penjelasan Detail di Halaman Info Biaya &rarr;
                            </a>
                        </div>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between text-xs text-amber-900 bg-amber-50/80 p-2.5 rounded-xl border border-amber-200">
                    <span class="flex items-center gap-1.5"><i class="fas fa-gift text-amber-600"></i> <strong>Diskon <?= $periode_gelombang ?></strong>: Hemat Rp <?= $diskon_gelombang ?> Uang Pangkal</span>
                </div>

                <!-- Countdown Mini -->
                <?php if ($show_countdown): ?>
                <div class="p-3 rounded-2xl bg-gradient-to-r from-emerald-950 to-[#064e45] text-white text-center">
                    <span class="text-[10px] text-amber-300 font-extrabold uppercase tracking-wider block mb-1">⏳ <?= $countdown_title ?></span>
                    <div class="flex justify-center items-center gap-2 font-mono text-sm sm:text-base font-black text-amber-300">
                        <span class="bg-black/30 px-2 py-0.5 rounded-lg" id="cd-hari">00</span> Hari : 
                        <span class="bg-black/30 px-2 py-0.5 rounded-lg" id="cd-jam">00</span> Jam : 
                        <span class="bg-black/30 px-2 py-0.5 rounded-lg" id="cd-menit">00</span> Menit : 
                        <span class="bg-white text-rose-600 px-2 py-0.5 rounded-lg shadow-xs" id="cd-detik">00</span> Detik
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ========================================== -->
        <!-- MENU 8: TESTIMONI (1 HALAMAN PENUH - SINKRON WEB) -->
        <!-- ========================================== -->
        <section id="brosur-testimoni" class="page-screen-mobile snap-start">
            <div class="glass-card rounded-3xl p-5 sm:p-6 border border-emerald-100 shadow-xl my-auto space-y-4">
                <div class="flex items-center gap-2.5 pb-2.5 border-b border-emerald-100">
                    <span class="w-9 h-9 rounded-2xl bg-teal-700 text-white flex items-center justify-center font-black text-sm shadow">
                        <i class="fas fa-comments"></i>
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-teal-700">Kisah Nyata Transformasi</span>
                        <h2 class="text-xl sm:text-2xl font-black text-emerald-950">Testimoni Walisantri</h2>
                    </div>
                </div>

                <div class="space-y-3 max-h-[52vh] overflow-y-auto pr-0.5 no-scrollbar">
                    <?php if (!empty($data_testimoni)): ?>
                        <?php foreach ($data_testimoni as $t): ?>
                            <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm relative">
                                <div class="flex items-center gap-3 mb-2">
                                    <?php if (!empty($t['gambar_url'])): ?>
                                        <img src="<?= htmlspecialchars($t['gambar_url']) ?>" alt="<?= htmlspecialchars($t['nama']) ?>" class="w-10 h-10 rounded-full object-cover border border-emerald-200">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs"><i class="fas fa-user-check"></i></div>
                                    <?php endif; ?>
                                    <div class="flex-1 overflow-hidden">
                                        <h4 class="font-black text-xs sm:text-sm text-emerald-950 truncate"><?= htmlspecialchars($t['nama']) ?></h4>
                                        <p class="text-[10px] text-gray-500 truncate"><?= htmlspecialchars($t['jabatan'] ?? '') ?></p>
                                    </div>
                                    <div class="flex text-amber-400 text-xs">
                                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-700 italic leading-relaxed">
                                    "<?= htmlspecialchars($t['isi_testimoni']) ?>"
                                </p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm">
                            <h4 class="font-extrabold text-xs text-emerald-950">Bpk. Hendra Gunawan (Wali Santri SMP)</h4>
                            <p class="text-xs text-gray-700 italic mt-1">"Alhamdulillah anak kami betah sekali dan hafalan juz 30 hingga 6 sangat mutqin dengan bimbingan asatidz yang penuh kasih sayang."</p>
                        </div>
                    <?php endif; ?>
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
        <!-- MENU 9: FORMULIR PENDAFTARAN & RESERVASI   -->
        <!-- ========================================== -->
        <section id="brosur-formulir" class="page-screen-mobile snap-start">
            <div class="my-auto">
                <div class="rounded-3xl p-5 sm:p-7 bg-gradient-to-br from-[#064e45] via-[#043d36] to-[#022823] text-white shadow-2xl border border-amber-500/40 relative overflow-hidden">
                    <div class="absolute -top-12 -right-12 w-44 h-44 bg-amber-400/10 rounded-full blur-2xl"></div>

                    <div class="text-center mb-5">
                        <span class="inline-block px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[10px] font-extrabold uppercase tracking-wider mb-1.5">
                            Hal 9 &bull; Formulir SPMB
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black text-white">Amankan Kuota Santri Ananda</h2>
                        <p class="text-xs text-emerald-100/90 mt-1 max-w-sm mx-auto">
                            Kuota terbatas maksimal 20 santri/angkatan untuk menjaga kualitas talaqqi intensif.
                        </p>
                    </div>

                    <form id="form-reservasi" onsubmit="submitReservasi(event)" class="space-y-3.5 text-left">
                        <input type="hidden" name="kode_ref" id="input-ref" value="<?= $kode_ref ?>">

                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Nama Ayah / Ibu (Wali) <span class="text-red-400">*</span></label>
                            <input type="text" id="reg-nama-wali" name="nama_wali" required placeholder="Contoh: Bpk. Hendy Pratama" value="<?= ($nama_tamu !== 'Bapak / Ibu Calon Wali Santri & Keluarga') ? $nama_tamu : '' ?>" class="w-full px-3.5 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs sm:text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Nomor WhatsApp Aktif <span class="text-red-400">*</span></label>
                            <input type="tel" id="reg-wa" name="whatsapp" required placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs sm:text-sm">
                            <span class="text-[10px] text-gray-300 mt-0.5 block"><i class="fab fa-whatsapp text-emerald-400 mr-1"></i> Notifikasi otomatis & E-Brosur resmi dikirim ke nomor ini</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Nama Calon Santri (Ananda) <span class="text-red-400">*</span></label>
                            <input type="text" id="reg-nama-santri" name="nama_santri" required placeholder="Contoh: Muhammad Al-Fatih" class="w-full px-3.5 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs sm:text-sm">
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
                            <textarea id="reg-catatan" name="catatan" rows="2" placeholder="Contoh: Ingin menjadwalkan kunjungan survei pesantren hari Ahad ini..." class="w-full px-3 py-2 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs"></textarea>
                        </div>

                        <button type="submit" id="btn-submit-reservasi" class="w-full bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-emerald-950 font-black py-3 px-6 rounded-2xl shadow-xl transition-all transform active:scale-95 flex items-center justify-center gap-2 text-sm sm:text-base mt-2 gold-glow">
                            <i class="fas fa-paper-plane"></i>
                            <span>Kirim Reservasi & Dapatkan E-Brosur</span>
                        </button>
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
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="text-center text-xs text-gray-500 py-6 border-t border-gray-200 space-y-2">
            <p>&copy; <?= date('Y') ?> <strong><?= $judul_utama ?></strong>. All Rights Reserved.</p>
            <p class="text-[11px] text-gray-400">Mencetak Generasi Hafidz Mutqin Bersanad, Berijazah Resmi Negara & Berjiwa Solopreneur.</p>
        </footer>

    </div>

    <!-- ============================================================ -->
    <!-- STICKY MOBILE BOTTOM BAR: 9 CARD MENUS (WARNA KUSTOM DINAMIS) -->
    <!-- BEBAS FRAME KOTAK, 4 MENU SECARA DEFAULT, SWIPABLE KANAN-KIRI-->
    <!-- ============================================================ -->
    <div id="brosur-bottom-bar" class="fixed bottom-0 left-0 right-0 z-40 border-t border-white/10 shadow-[0_-8px_30px_rgba(0,0,0,0.3)] py-1.5 px-1 backdrop-blur-xl transition-colors duration-300" style="background-color: <?= $bottom_bar_bg_color ?>; color: <?= $bottom_bar_text_color ?>;">
        <div class="max-w-md mx-auto">
            
            <!-- CAROUSEL TRACK: 4 CARDS PER SCREEN BY DEFAULT, BEBAS FRAME KOTAK -->
            <div id="brosur-bottom-track" class="flex items-center overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory gap-1 px-1 py-0.5">
                
                <!-- 1. Home -->
                <a href="#brosur-home" onclick="navigasiKeSection('brosur-home', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 text-amber-300">
                    <i class="fas fa-house text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Home</span>
                </a>

                <!-- 2. Mengapa VQBM -->
                <a href="#brosur-mengapa" onclick="navigasiKeSection('brosur-mengapa', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-heart text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Mengapa</span>
                </a>

                <!-- 3. Target Kompetensi -->
                <a href="#brosur-kompetensi" onclick="navigasiKeSection('brosur-kompetensi', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-bullseye text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Kompetensi</span>
                </a>

                <!-- 4. Fasilitas -->
                <a href="#brosur-fasilitas" onclick="navigasiKeSection('brosur-fasilitas', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-hotel text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Fasilitas</span>
                </a>

                <!-- 5. Dewan Pengasuh -->
                <a href="#brosur-pengasuh" onclick="navigasiKeSection('brosur-pengasuh', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-user-graduate text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Pengasuh</span>
                </a>

                <!-- 6. Kegiatan Santri (NEW!) -->
                <a href="#brosur-kegiatan" onclick="navigasiKeSection('brosur-kegiatan', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-camera-retro text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Kegiatan</span>
                </a>

                <!-- 7. Investasi -->
                <a href="#brosur-biaya" onclick="navigasiKeSection('brosur-biaya', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-receipt text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Investasi</span>
                </a>

                <!-- 8. Testimoni -->
                <a href="#brosur-testimoni" onclick="navigasiKeSection('brosur-testimoni', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-comments text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Testimoni</span>
                </a>

                <!-- 9. Formulir -->
                <a href="#brosur-formulir" onclick="navigasiKeSection('brosur-formulir', event, this)" class="bottom-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                    <i class="fas fa-file-pen text-xl mb-0.5 group-hover:scale-110 transition"></i>
                    <span class="text-[9px] font-bold leading-tight truncate w-full">Formulir</span>
                </a>

            </div>

            <!-- CONTROLS GESER KANAN KIRI & INDIKATOR HALAMAN (4 MENU PER TAMPILAN) -->
            <div class="flex items-center justify-between px-3 pt-1 text-[8px] border-t border-white/10 mt-1 opacity-80">
                <button type="button" onclick="scrollBrosurBottomBar('left')" class="hover:opacity-100 font-bold flex items-center gap-1 transition py-0.5">
                    <i class="fas fa-chevron-left text-[7px]"></i> <span>Geser Kiri</span>
                </button>
                
                <div class="flex items-center gap-1.5" id="brosur-indicator-dots">
                    <span class="w-3 h-1 rounded-full bg-amber-400 transition-all" id="b-dot-1"></span>
                    <span class="w-1.5 h-1 rounded-full bg-white/40 transition-all" id="b-dot-2"></span>
                    <span class="w-1.5 h-1 rounded-full bg-white/40 transition-all" id="b-dot-3"></span>
                </div>

                <button type="button" onclick="scrollBrosurBottomBar('right')" class="hover:opacity-100 font-bold flex items-center gap-1 transition py-0.5">
                    <span>Geser Kanan</span> <i class="fas fa-chevron-right text-[7px]"></i>
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

        // Navigasi Smooth Scroll untuk 9 Menu Bottom Bar
        function navigasiKeSection(sectionId, e, elem) {
            if (e) e.preventDefault();
            const el = document.getElementById(sectionId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth' });
            }
            if (elem) {
                document.querySelectorAll('#brosur-bottom-track .bottom-card-menu-item').forEach(b => {
                    b.classList.remove('text-amber-300');
                    b.classList.add('opacity-80');
                });
                elem.classList.remove('opacity-80');
                elem.classList.add('text-amber-300');
            }
        }

        // Geser Bottom Bar Carousel Kanan / Kiri
        function scrollBrosurBottomBar(direction) {
            const track = document.getElementById('brosur-bottom-track');
            if (!track) return;
            const scrollDistance = track.clientWidth * 0.85;
            if (direction === 'left') {
                track.scrollBy({ left: -scrollDistance, behavior: 'smooth' });
            } else {
                track.scrollBy({ left: scrollDistance, behavior: 'smooth' });
            }
            setTimeout(updateBrosurDots, 300);
        }

        function updateBrosurDots() {
            const track = document.getElementById('brosur-bottom-track');
            const d1 = document.getElementById('b-dot-1');
            const d2 = document.getElementById('b-dot-2');
            const d3 = document.getElementById('b-dot-3');
            if (!track || !d1 || !d2 || !d3) return;
            const scrollLeft = track.scrollLeft;
            const maxScroll = track.scrollWidth - track.clientWidth;
            if (maxScroll <= 0) return;
            const ratio = scrollLeft / maxScroll;
            [d1, d2, d3].forEach(d => { d.className = 'w-1.5 h-1 rounded-full bg-white/40 transition-all'; });
            if (ratio < 0.35) {
                d1.className = 'w-3 h-1 rounded-full bg-amber-400 transition-all';
            } else if (ratio < 0.7) {
                d2.className = 'w-3 h-1 rounded-full bg-amber-400 transition-all';
            } else {
                d3.className = 'w-3 h-1 rounded-full bg-amber-400 transition-all';
            }
        }

        const bottomTrack = document.getElementById('brosur-bottom-track');
        if (bottomTrack) {
            bottomTrack.addEventListener('scroll', updateBrosurDots, { passive: true });
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
