<?php
// brosur.php
// Halaman Khusus Brosur & Undangan Digital Smartphone Villa Quran Indonesia
// 100% Sinkron & Presisi Sesuai Layar Simulasi Admin

require_once 'koneksi.php';

// Ambil Konfigurasi Pengaturan Brosur dari Database
$q = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg = ($q && $q->num_rows > 0) ? $q->fetch_assoc() : [];

// Personalization & URL Parameter
$to_param   = isset($_GET['to']) ? trim($_GET['to']) : '';
$nama_tamu  = !empty($to_param) ? htmlspecialchars($to_param) : 'Bapak / Ibu Calon Wali Santri & Keluarga';
$kode_ref   = isset($_GET['ref']) ? trim(htmlspecialchars($_GET['ref'])) : 'organik';

// Cari nama agen pengundang jika ada ref
$nama_agen_pengundang = '';
if (!empty($kode_ref) && $kode_ref !== 'organik') {
    $q_ag = $conn->query("SELECT nama FROM agen WHERE kode_ref = '$kode_ref' OR whatsapp = '$kode_ref' LIMIT 1");
    if ($q_ag && $q_ag->num_rows > 0) {
        $row_ag = $q_ag->fetch_assoc();
        $nama_agen_pengundang = $row_ag['nama'];
    }
}

$active_frame_param = isset($_GET['frame']) ? trim(strtolower($_GET['frame'])) : 'home';
if (!in_array($active_frame_param, ['home', 'prestasi', 'unggulan', 'pengajar', 'fasilitas'])) {
    $active_frame_param = 'home';
}

// 1. DATA FRAME DEPAN (HOME)
$cover_bg_url = !empty($cfg['cover_bg_url']) ? $cfg['cover_bg_url'] : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80';
$cover_overlay_opacity = isset($cfg['cover_overlay_opacity']) ? (float)$cfg['cover_overlay_opacity'] : 0.88;

$raw_items = $cfg['custom_text_items'] ?? null;
if ($raw_items !== null && $raw_items !== '') {
    $text_items = json_decode($raw_items, true);
    if (!is_array($text_items)) $text_items = [];
} else {
    $text_items = [
        [
            'id'      => 'text_home_1',
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

$raw_images = $cfg['custom_image_items'] ?? null;
if ($raw_images !== null && $raw_images !== '') {
    $image_items = json_decode($raw_images, true);
    if (!is_array($image_items)) $image_items = [];
} else {
    $image_items = [];
}

$raw_videos = $cfg['custom_video_items'] ?? null;
if ($raw_videos !== null && $raw_videos !== '') {
    $video_items = json_decode($raw_videos, true);
    if (!is_array($video_items)) $video_items = [];
} else {
    $video_items = [];
}

$raw_buttons = $cfg['custom_button_items'] ?? null;
if ($raw_buttons !== null && $raw_buttons !== '') {
    $button_items = json_decode($raw_buttons, true);
    if (!is_array($button_items)) $button_items = [];
} else {
    $button_items = [
        [
            'id'            => 'btn_home_1',
            'text'          => 'Chat WhatsApp Panitia',
            'url'           => 'https://wa.me/6281234567890?text=Assalamu%27alaikum%2C%20saya%20ingin%20info%20pendaftaran%20Villa%20Quran',
            'icon'          => 'fab fa-whatsapp',
            'shape'         => 'rounded_pill',
            'bg_color'      => '#25d366',
            'text_color'    => '#ffffff',
            'border_enable' => 0,
            'border_width'  => 2,
            'border_color'  => '#ffffff',
            'shadow_style'  => 'glow_wa',
            'font_size'     => 13,
            'font'          => 'Plus Jakarta Sans',
            'posX'          => 50.0,
            'posY'          => 82.0,
            'width'         => 82,
            'height'        => 46,
            'target'        => '_blank'
        ]
    ];
}

$raw_slides = $cfg['custom_slide_items'] ?? null;
if ($raw_slides !== null && $raw_slides !== '') {
    $slide_items = json_decode($raw_slides, true);
    if (!is_array($slide_items)) $slide_items = [];
} else {
    $slide_items = [];
}

// 2. DATA FRAME PRESTASI
$prestasi_bg_url = !empty($cfg['prestasi_bg_url']) ? $cfg['prestasi_bg_url'] : 'https://images.unsplash.com/photo-1577896851231-70ef18881754?w=1200&auto=format&fit=crop&q=80';
$prestasi_overlay_opacity = isset($cfg['prestasi_overlay_opacity']) ? (float)$cfg['prestasi_overlay_opacity'] : 0.88;

$raw_prestasi_texts = $cfg['prestasi_text_items'] ?? null;
if ($raw_prestasi_texts !== null && $raw_prestasi_texts !== '') {
    $prestasi_text_items = json_decode($raw_prestasi_texts, true);
    if (!is_array($prestasi_text_items)) $prestasi_text_items = [];
} else {
    $prestasi_text_items = [
        [
            'id'      => 'text_prestasi_1',
            'content' => 'Prestasi Santri Villa Quran',
            'format'  => 'h2',
            'color'   => '#ffffff',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 24,
            'posX'    => 50.0,
            'posY'    => 18.0,
            'width'   => 88
        ],
        [
            'id'      => 'text_prestasi_2',
            'content' => 'Mencetak Generasi Qur\'ani Berprestasi Juara Nasional & Internasional',
            'format'  => 'h4',
            'color'   => '#fbbf24',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 13,
            'posX'    => 50.0,
            'posY'    => 28.0,
            'width'   => 90
        ]
    ];
}

$raw_prestasi_images = $cfg['prestasi_image_items'] ?? null;
if ($raw_prestasi_images !== null && $raw_prestasi_images !== '') {
    $prestasi_image_items = json_decode($raw_prestasi_images, true);
    if (!is_array($prestasi_image_items)) $prestasi_image_items = [];
} else {
    $prestasi_image_items = [
        [
            'id'            => 'img_prestasi_1',
            'url'           => 'https://images.unsplash.com/photo-1577896851231-70ef18881754?w=800&auto=format&fit=crop&q=80',
            'shape'         => 'rounded',
            'border_enable' => 1,
            'border_width'  => 3,
            'border_color'  => '#fbbf24',
            'border_style'  => 'solid',
            'shadow_style'  => 'glow_gold',
            'rotation'      => 0,
            'posX'          => 50.0,
            'posY'          => 53.0,
            'width'         => 72
        ]
    ];
}

$raw_prestasi_videos = $cfg['prestasi_video_items'] ?? null;
if ($raw_prestasi_videos !== null && $raw_prestasi_videos !== '') {
    $prestasi_video_items = json_decode($raw_prestasi_videos, true);
    if (!is_array($prestasi_video_items)) $prestasi_video_items = [];
} else {
    $prestasi_video_items = [];
}

$raw_prestasi_buttons = $cfg['prestasi_button_items'] ?? null;
if ($raw_prestasi_buttons !== null && $raw_prestasi_buttons !== '') {
    $prestasi_button_items = json_decode($raw_prestasi_buttons, true);
    if (!is_array($prestasi_button_items)) $prestasi_button_items = [];
} else {
    $prestasi_button_items = [
        [
            'id'            => 'btn_prestasi_1',
            'text'          => 'Lihat Galeri Prestasi Santri',
            'url'           => 'https://wa.me/6281234567890?text=Assalamu%27alaikum%2C%20saya%20ingin%20info%20prestasi%20santri%20Villa%20Quran',
            'icon'          => 'fas fa-trophy',
            'shape'         => 'rounded_pill',
            'bg_color'      => '#d97706',
            'text_color'    => '#ffffff',
            'border_enable' => 0,
            'border_width'  => 2,
            'border_color'  => '#ffffff',
            'shadow_style'  => 'glow_gold',
            'font_size'     => 13,
            'font'          => 'Plus Jakarta Sans',
            'posX'          => 50.0,
            'posY'          => 82.0,
            'width'         => 82,
            'height'        => 46,
            'target'        => '_blank'
        ]
    ];
}

$raw_prestasi_slides = $cfg['prestasi_slide_items'] ?? null;
if ($raw_prestasi_slides !== null && $raw_prestasi_slides !== '') {
    $prestasi_slide_items = json_decode($raw_prestasi_slides, true);
    if (!is_array($prestasi_slide_items)) $prestasi_slide_items = [];
} else {
    $prestasi_slide_items = [];
}

// 3. DATA FRAME UNGGULAN
$unggulan_bg_url = !empty($cfg['unggulan_bg_url']) ? $cfg['unggulan_bg_url'] : 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=1200&auto=format&fit=crop&q=80';
$unggulan_overlay_opacity = isset($cfg['unggulan_overlay_opacity']) ? (float)$cfg['unggulan_overlay_opacity'] : 0.88;

$raw_unggulan_texts = $cfg['unggulan_text_items'] ?? null;
if ($raw_unggulan_texts !== null && $raw_unggulan_texts !== '') {
    $unggulan_text_items = json_decode($raw_unggulan_texts, true);
    if (!is_array($unggulan_text_items)) $unggulan_text_items = [];
} else {
    $unggulan_text_items = [
        [
            'id'      => 'text_unggulan_1',
            'content' => 'Program Unggulan Pesantren',
            'format'  => 'h2',
            'color'   => '#ffffff',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 24,
            'posX'    => 50.0,
            'posY'    => 18.0,
            'width'   => 88
        ],
        [
            'id'      => 'text_unggulan_2',
            'content' => 'Tahfidz Mutqin 30 Juz, Sanad Qira\'ah, Bahasa Arab & Kurikulum Solopreneur AI',
            'format'  => 'h4',
            'color'   => '#f59e0b',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 13,
            'posX'    => 50.0,
            'posY'    => 28.0,
            'width'   => 90
        ]
    ];
}

$raw_unggulan_images = $cfg['unggulan_image_items'] ?? null;
if ($raw_unggulan_images !== null && $raw_unggulan_images !== '') {
    $unggulan_image_items = json_decode($raw_unggulan_images, true);
    if (!is_array($unggulan_image_items)) $unggulan_image_items = [];
} else {
    $unggulan_image_items = [
        [
            'id'            => 'img_unggulan_1',
            'url'           => 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=800&auto=format&fit=crop&q=80',
            'shape'         => 'rounded',
            'border_enable' => 1,
            'border_width'  => 3,
            'border_color'  => '#f59e0b',
            'border_style'  => 'solid',
            'shadow_style'  => 'glow_gold',
            'rotation'      => 0,
            'posX'          => 50.0,
            'posY'          => 53.0,
            'width'         => 72
        ]
    ];
}

$raw_unggulan_videos = $cfg['unggulan_video_items'] ?? null;
if ($raw_unggulan_videos !== null && $raw_unggulan_videos !== '') {
    $unggulan_video_items = json_decode($raw_unggulan_videos, true);
    if (!is_array($unggulan_video_items)) $unggulan_video_items = [];
} else {
    $unggulan_video_items = [];
}

$raw_unggulan_buttons = $cfg['unggulan_button_items'] ?? null;
if ($raw_unggulan_buttons !== null && $raw_unggulan_buttons !== '') {
    $unggulan_button_items = json_decode($raw_unggulan_buttons, true);
    if (!is_array($unggulan_button_items)) $unggulan_button_items = [];
} else {
    $unggulan_button_items = [
        [
            'id'            => 'btn_unggulan_1',
            'text'          => 'Konsultasi Program Unggulan',
            'url'           => 'https://wa.me/6281234567890?text=Assalamu%27alaikum%2C%20saya%20ingin%20tahu%20program%20unggulan%20Villa%20Quran',
            'icon'          => 'fab fa-whatsapp',
            'shape'         => 'rounded_pill',
            'bg_color'      => '#25d366',
            'text_color'    => '#ffffff',
            'border_enable' => 0,
            'border_width'  => 2,
            'border_color'  => '#ffffff',
            'shadow_style'  => 'glow_wa',
            'font_size'     => 13,
            'font'          => 'Plus Jakarta Sans',
            'posX'          => 50.0,
            'posY'          => 82.0,
            'width'         => 82,
            'height'        => 46,
            'target'        => '_blank'
        ]
    ];
}

$raw_unggulan_slides = $cfg['unggulan_slide_items'] ?? null;
if ($raw_unggulan_slides !== null && $raw_unggulan_slides !== '') {
    $unggulan_slide_items = json_decode($raw_unggulan_slides, true);
    if (!is_array($unggulan_slide_items)) $unggulan_slide_items = [];
} else {
    $unggulan_slide_items = [];
}

// 4. DATA FRAME PENGAJAR
$pengajar_bg_url = !empty($cfg['pengajar_bg_url']) ? $cfg['pengajar_bg_url'] : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80';
$pengajar_overlay_opacity = isset($cfg['pengajar_overlay_opacity']) ? (float)$cfg['pengajar_overlay_opacity'] : 0.88;

$raw_pengajar_texts = $cfg['pengajar_text_items'] ?? null;
if ($raw_pengajar_texts !== null && $raw_pengajar_texts !== '') {
    $pengajar_text_items = json_decode($raw_pengajar_texts, true);
    if (!is_array($pengajar_text_items)) $pengajar_text_items = [];
} else {
    $pengajar_text_items = [
        [
            'id'      => 'text_pengajar_1',
            'content' => 'Dewan Pengajar & Asatidz',
            'format'  => 'h2',
            'color'   => '#ffffff',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 24,
            'posX'    => 50.0,
            'posY'    => 18.0,
            'width'   => 88
        ],
        [
            'id'      => 'text_pengajar_2',
            'content' => 'Dibimbing oleh Asatidz & Ustadzah Berpengalaman, Hafidz 30 Juz & Bersanad',
            'format'  => 'h4',
            'color'   => '#5eead4',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 13,
            'posX'    => 50.0,
            'posY'    => 28.0,
            'width'   => 90
        ]
    ];
}

$raw_pengajar_images = $cfg['pengajar_image_items'] ?? null;
if ($raw_pengajar_images !== null && $raw_pengajar_images !== '') {
    $pengajar_image_items = json_decode($raw_pengajar_images, true);
    if (!is_array($pengajar_image_items)) $pengajar_image_items = [];
} else {
    $pengajar_image_items = [
        [
            'id'            => 'img_pengajar_1',
            'url'           => 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=800&auto=format&fit=crop&q=80',
            'shape'         => 'rounded',
            'border_enable' => 1,
            'border_width'  => 3,
            'border_color'  => '#2dd4bf',
            'border_style'  => 'solid',
            'shadow_style'  => 'glow_teal',
            'rotation'      => 0,
            'posX'          => 50.0,
            'posY'          => 53.0,
            'width'         => 72
        ]
    ];
}

$raw_pengajar_videos = $cfg['pengajar_video_items'] ?? null;
if ($raw_pengajar_videos !== null && $raw_videos !== '') {
    $pengajar_video_items = json_decode($raw_pengajar_videos, true);
    if (!is_array($pengajar_video_items)) $pengajar_video_items = [];
} else {
    $pengajar_video_items = [];
}

$raw_pengajar_buttons = $cfg['pengajar_button_items'] ?? null;
if ($raw_pengajar_buttons !== null && $raw_pengajar_buttons !== '') {
    $pengajar_button_items = json_decode($raw_pengajar_buttons, true);
    if (!is_array($pengajar_button_items)) $pengajar_button_items = [];
} else {
    $pengajar_button_items = [
        [
            'id'            => 'btn_pengajar_1',
            'text'          => 'Konsultasi Bersama Asatidz',
            'url'           => 'https://wa.me/6281234567890?text=Assalamu%27alaikum%2C%20saya%20ingin%20konsultasi%20dengan%20asatidz%20Villa%20Quran',
            'icon'          => 'fab fa-whatsapp',
            'shape'         => 'rounded_pill',
            'bg_color'      => '#25d366',
            'text_color'    => '#ffffff',
            'border_enable' => 0,
            'border_width'  => 2,
            'border_color'  => '#ffffff',
            'shadow_style'  => 'glow_wa',
            'font_size'     => 13,
            'font'          => 'Plus Jakarta Sans',
            'posX'          => 50.0,
            'posY'          => 82.0,
            'width'         => 82,
            'height'        => 46,
            'target'        => '_blank'
        ]
    ];
}

$raw_pengajar_slides = $cfg['pengajar_slide_items'] ?? null;
if ($raw_pengajar_slides !== null && $raw_pengajar_slides !== '') {
    $pengajar_slide_items = json_decode($raw_pengajar_slides, true);
    if (!is_array($pengajar_slide_items)) $pengajar_slide_items = [];
} else {
    $pengajar_slide_items = [];
}

// 5. DATA FRAME FASILITAS
$fasilitas_bg_url = !empty($cfg['fasilitas_bg_url']) ? $cfg['fasilitas_bg_url'] : 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=1200&auto=format&fit=crop&q=80';
$fasilitas_overlay_opacity = isset($cfg['fasilitas_overlay_opacity']) ? (float)$cfg['fasilitas_overlay_opacity'] : 0.88;

$raw_fasilitas_texts = $cfg['fasilitas_text_items'] ?? null;
if ($raw_fasilitas_texts !== null && $raw_fasilitas_texts !== '') {
    $fasilitas_text_items = json_decode($raw_fasilitas_texts, true);
    if (!is_array($fasilitas_text_items)) $fasilitas_text_items = [];
} else {
    $fasilitas_text_items = [
        [
            'id'      => 'text_fasilitas_1',
            'content' => 'Fasilitas & Sarana Pesantren',
            'format'  => 'h2',
            'color'   => '#ffffff',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 24,
            'posX'    => 50.0,
            'posY'    => 18.0,
            'width'   => 88
        ],
        [
            'id'      => 'text_fasilitas_2',
            'content' => 'Lingkungan Asri Pegunungan, Masjid Megah, Asrama Nyaman Ala Villa & Sarana Olahraga',
            'format'  => 'h4',
            'color'   => '#38bdf8',
            'font'    => 'Plus Jakarta Sans',
            'align'   => 'center',
            'size'    => 13,
            'posX'    => 50.0,
            'posY'    => 28.0,
            'width'   => 90
        ]
    ];
}

$raw_fasilitas_images = $cfg['fasilitas_image_items'] ?? null;
if ($raw_fasilitas_images !== null && $raw_fasilitas_images !== '') {
    $fasilitas_image_items = json_decode($raw_fasilitas_images, true);
    if (!is_array($fasilitas_image_items)) $fasilitas_image_items = [];
} else {
    $fasilitas_image_items = [
        [
            'id'            => 'img_fasilitas_1',
            'url'           => 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=800&auto=format&fit=crop&q=80',
            'shape'         => 'rounded',
            'border_enable' => 1,
            'border_width'  => 3,
            'border_color'  => '#38bdf8',
            'border_style'  => 'solid',
            'shadow_style'  => 'glow_teal',
            'rotation'      => 0,
            'posX'          => 50.0,
            'posY'          => 53.0,
            'width'         => 72
        ]
    ];
}

$raw_fasilitas_videos = $cfg['fasilitas_video_items'] ?? null;
if ($raw_fasilitas_videos !== null && $raw_fasilitas_videos !== '') {
    $fasilitas_video_items = json_decode($raw_fasilitas_videos, true);
    if (!is_array($fasilitas_video_items)) $fasilitas_video_items = [];
} else {
    $fasilitas_video_items = [];
}

$raw_fasilitas_buttons = $cfg['fasilitas_button_items'] ?? null;
if ($raw_fasilitas_buttons !== null && $raw_fasilitas_buttons !== '') {
    $fasilitas_button_items = json_decode($raw_fasilitas_buttons, true);
    if (!is_array($fasilitas_button_items)) $fasilitas_button_items = [];
} else {
    $fasilitas_button_items = [
        [
            'id'            => 'btn_fasilitas_1',
            'text'          => 'Lihat Virtual Tour Fasilitas',
            'url'           => 'https://wa.me/6281234567890?text=Assalamu%27alaikum%2C%20saya%20ingin%20info%20fasilitas%20Villa%20Quran',
            'icon'          => 'fas fa-building-columns',
            'shape'         => 'rounded_pill',
            'bg_color'      => '#0284c7',
            'text_color'    => '#ffffff',
            'border_enable' => 0,
            'border_width'  => 2,
            'border_color'  => '#ffffff',
            'shadow_style'  => 'glow_sky',
            'font_size'     => 13,
            'font'          => 'Plus Jakarta Sans',
            'posX'          => 50.0,
            'posY'          => 82.0,
            'width'         => 82,
            'height'        => 46,
            'target'        => '_blank'
        ]
    ];
}

$raw_fasilitas_slides = $cfg['fasilitas_slide_items'] ?? null;
if ($raw_fasilitas_slides !== null && $raw_fasilitas_slides !== '') {
    $fasilitas_slide_items = json_decode($raw_fasilitas_slides, true);
    if (!is_array($fasilitas_slide_items)) $fasilitas_slide_items = [];
} else {
    $fasilitas_slide_items = [];
}

$bottom_bar_bg_color     = !empty($cfg['bottom_bar_bg_color']) ? htmlspecialchars($cfg['bottom_bar_bg_color']) : '#022d27';
$bottom_bar_text_color   = !empty($cfg['bottom_bar_text_color']) ? htmlspecialchars($cfg['bottom_bar_text_color']) : '#ffffff';
$bottom_bar_active_color = !empty($cfg['bottom_bar_active_color']) ? htmlspecialchars($cfg['bottom_bar_active_color']) : '#fbbf24';
$music_url               = !empty($cfg['music_url']) ? $cfg['music_url'] : 'upload/backsound.mp3';
?>
<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Brosur Digital & Undangan PSB | Villa Quran Indonesia</title>

    <!-- Meta Tags & OpenGraph untuk WhatsApp Preview -->
    <meta name="description" content="Undangan Khusus Silaturahmi & Brosur Pendidikan Generasi Qur'ani. Tahfidz Mutqin 15-30 Juz Bersanad, Berijazah Resmi SMP-SMA, Digital Marketing & Solopreneur di Villa Quran Indonesia.">
    <meta property="og:title" content="Brosur Digital Villa Quran Indonesia - Khusus <?= htmlspecialchars($nama_tamu) ?>">
    <meta property="og:description" content="Pondok Pesantren Tahfidz Berasrama Nyaman Ala Villa. Tahfidz Mutqin Bersanad, Formal SMP-SMA & Skill Digital Solopreneur.">
    <meta property="og:image" content="https://villaquranindonesia.com/upload/logo-villa-quran.png">
    <meta property="og:type" content="website">

    <!-- Google Fonts Multi-Family -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@500;700;900&family=Inter:wght@300;400;600;700&family=Outfit:wght@400;600;800;900&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&family=Oswald:wght@400;600;700&family=Barlow+Condensed:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        @font-face {
            font-family: 'Marlin Condensed';
            src: local('Marlin Condensed'), local('MarlinCondensed'), local('Marlin-Condensed'), local('Marlin'), local('MarlinBold');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        * {
            -webkit-tap-highlight-color: transparent;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b0f19;
            color: #ffffff;
        }

        .font-marlin {
            font-family: 'Marlin Condensed', 'Barlow Condensed', 'Oswald', sans-serif;
        }

        /* Ambient luxury pattern on desktop background */
        .desktop-backdrop {
            background-image: radial-gradient(rgba(14, 165, 233, 0.12) 1px, transparent 1px), radial-gradient(rgba(16, 185, 129, 0.08) 1px, transparent 1px);
            background-size: 32px 32px, 24px 24px;
            background-position: 0 0, 12px 12px;
        }

        /* Canvas Frame: 100% Fullscreen on Mobile, Elegant Centered Smartphone on Desktop */
        .brosur-viewport {
            width: 100%;
            height: 100%;
            height: 100dvh;
            max-width: 480px;
            position: relative;
            overflow: hidden;
            background-color: #021a15;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 0 1px rgba(255, 255, 255, 0.08);
            margin: 0 auto;
            transition: all 0.3s ease;
        }

        @media (min-width: 640px) {
            .brosur-viewport {
                height: min(840px, calc(100vh - 32px));
                border-radius: 36px;
                margin: auto;
            }
        }

        /* Shape Classes (100% Persis Layar Simulasi) */
        .shape-kotak { border-radius: 0px; aspect-ratio: 1/1; }
        .shape-persegi_panjang { border-radius: 14px; aspect-ratio: 4/3; }
        .shape-persegi_panjang_wide { border-radius: 14px; aspect-ratio: 16/9; }
        .shape-rounded { border-radius: 24px; aspect-ratio: 1/1; }
        .shape-bulat { border-radius: 50%; aspect-ratio: 1/1; }
        .shape-oval { border-radius: 50%; aspect-ratio: 4/3; }
        .shape-kubah { border-radius: 120px 120px 16px 16px; aspect-ratio: 3/4; }
        .shape-perisai { border-radius: 16px 16px 50% 50%; aspect-ratio: 1/1; }
        .shape-bintang { clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%); aspect-ratio: 1/1; }

        /* Shadow Classes (100% Persis Layar Simulasi) */
        .shadow-none { filter: none !important; box-shadow: none !important; }
        .shadow-soft { filter: drop-shadow(0 6px 16px rgba(0,0,0,0.25)); }
        .shadow-medium { filter: drop-shadow(0 12px 28px rgba(0,0,0,0.45)); }
        .shadow-deep { filter: drop-shadow(0 20px 45px rgba(0,0,0,0.7)); }
        .shadow-floating { filter: drop-shadow(0 12px 24px rgba(0,0,0,0.35)) drop-shadow(0 4px 8px rgba(0,0,0,0.15)); }
        .shadow-glow_gold { filter: drop-shadow(0 0 18px rgba(251,191,36,0.75)) drop-shadow(0 4px 10px rgba(0,0,0,0.4)); }
        .shadow-glow_teal { filter: drop-shadow(0 0 18px rgba(11,132,120,0.85)) drop-shadow(0 4px 10px rgba(0,0,0,0.4)); }
        .shadow-glow_wa { filter: drop-shadow(0 0 18px rgba(37,211,102,0.8)) drop-shadow(0 4px 10px rgba(0,0,0,0.4)); }
        .shadow-glow_rose { filter: drop-shadow(0 0 18px rgba(244,63,94,0.8)) drop-shadow(0 4px 10px rgba(0,0,0,0.4)); }
        .shadow-glow_sky { filter: drop-shadow(0 0 18px rgba(56,189,248,0.8)) drop-shadow(0 4px 10px rgba(0,0,0,0.4)); }

        /* Button Shapes */
        .shape-btn-rounded_pill { border-radius: 9999px !important; }
        .shape-btn-persegipanjang { border-radius: 6px !important; }
        .shape-btn-bulat { border-radius: 50% !important; aspect-ratio: 1/1 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; }
        .shape-btn-rounded { border-radius: 14px !important; }

        /* Floating Audio Rotation */
        @keyframes spinVinyl {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .spin-vinyl {
            animation: spinVinyl 6s linear infinite;
        }

        /* Smooth Layer Fade Animation */
        .fade-in-layer {
            animation: fadeInLayer 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeInLayer {
            from { opacity: 0; transform: scale(0.98); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="desktop-backdrop flex items-center justify-center min-h-screen select-none">

    <!-- AUDIO BACKSOUND ELEMENT -->
    <audio id="audio-player" loop preload="auto">
        <source src="<?= htmlspecialchars($music_url) ?>" type="audio/mpeg">
        <source src="upload/backsound.mp3" type="audio/mpeg">
        <source src="https://archive.org/download/IslamicBackgroundSoundsAahat/28-ISLAMIC%20BACKGROUND%20SOUNDS.mp3" type="audio/mpeg">
    </audio>

    <!-- WRAPPER KANVAS BROSUR DIGITAL -->
    <div class="brosur-viewport relative w-full h-full" id="brosur-canvas">

        <!-- ======================================================= -->
        <!-- BACKGROUND LAYER & OVERLAY DINAMIS                      -->
        <!-- ======================================================= -->
        <div id="live-bg-screen" class="absolute inset-0 w-full h-full bg-cover bg-center transition-all duration-500 ease-out" style="background-image: url('<?= htmlspecialchars($cover_bg_url) ?>');">
            <!-- Overlay Gradient Syahdu -->
            <div id="live-bg-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-opacity duration-500" style="opacity: <?= $cover_overlay_opacity ?>;"></div>
        </div>

        <!-- ======================================================= -->
        <!-- FLOATING TOP CONTROLS (MUSIC & SHARE)                   -->
        <!-- ======================================================= -->
        <div class="absolute top-3.5 left-3.5 right-3.5 z-50 flex items-center justify-between pointer-events-none">
            <!-- Badge Brand -->
            <div class="pointer-events-auto flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/40 backdrop-blur-md border border-white/10 text-white shadow-md">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[10px] font-extrabold tracking-wider uppercase text-emerald-300">Villa Quran</span>
            </div>

            <!-- Action Buttons: Audio & Share -->
            <div class="pointer-events-auto flex items-center gap-2">
                <!-- Music Toggle Button -->
                <button type="button" id="btn-audio-toggle" onclick="toggleAudio()" class="w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md border border-white/15 text-amber-300 flex items-center justify-center text-xs shadow-md transition active:scale-90 cursor-pointer" title="Putar / Hentikan Musik">
                    <i id="audio-toggle-icon" class="fas fa-music text-[11px]"></i>
                </button>

                <!-- Share Button -->
                <button type="button" onclick="shareBrosur()" class="w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md border border-white/15 text-sky-300 flex items-center justify-center text-xs shadow-md transition active:scale-90 cursor-pointer" title="Bagikan Brosur Ini">
                    <i class="fas fa-share-nodes text-[11px]"></i>
                </button>
            </div>
        </div>

        <!-- ======================================================= -->
        <!-- DYNAMIC LAYERS CONTAINER (PERSIS 100% SIMULASI ADMIN)    -->
        <!-- ======================================================= -->

        <!-- 1. LAYER GAMBAR SISIPAN -->
        <div id="live-image-layers-container" class="absolute inset-0 pointer-events-none z-20"></div>

        <!-- 2. LAYER SLIDE SHOWCASE (CAROUSEL) -->
        <div id="live-slide-layers-container" class="absolute inset-0 pointer-events-none z-22"></div>

        <!-- 3. LAYER VIDEO SISIPAN (YOUTUBE / DIRECT MP4) -->
        <div id="live-video-layers-container" class="absolute inset-0 pointer-events-none z-25"></div>

        <!-- 4. LAYER TULISAN DINAMIS (H1-H5 & P DENGAN TYPOGRAPHY) -->
        <div id="live-text-layers-container" class="absolute inset-0 pointer-events-none z-30"></div>

        <!-- 5. LAYER TOMBOL AKSI INTERAKTIF (WHATSAPP, FORM, DLL) -->
        <div id="live-button-layers-container" class="absolute inset-0 pointer-events-none z-35"></div>

        <!-- ======================================================= -->
        <!-- DOCKED BOTTOM NAVIGATION BAR (5 MENU PERSIS SIMULASI)   -->
        <!-- ======================================================= -->
        <div id="live-bottom-bar" class="absolute bottom-0 left-0 right-0 z-40 pt-2.5 pb-3 sm:pb-3.5 px-2 border-t border-white/10 backdrop-blur-md flex items-center justify-around shadow-[0_-10px_25px_rgba(0,0,0,0.5)] transition-all duration-300" style="background-color: <?= $bottom_bar_bg_color ?>; color: <?= $bottom_bar_text_color ?>;">
            
            <!-- Menu 1: Depan (Home) -->
            <button type="button" onclick="switchFrame('home')" id="menu-btn-home" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 cursor-pointer py-0.5 px-2 group flex-1" style="color: <?= $bottom_bar_active_color ?>;" title="Halaman Depan">
                <i id="menu-icon-home" class="fas fa-house text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                <span id="menu-label-home" class="text-[9.5px] font-bold tracking-wider leading-none">Depan</span>
            </button>

            <!-- Menu 2: Prestasi -->
            <button type="button" onclick="switchFrame('prestasi')" id="menu-btn-prestasi" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 cursor-pointer py-0.5 px-2 group flex-1 opacity-70 hover:opacity-100" style="color: <?= $bottom_bar_text_color ?>;" title="Galeri Prestasi">
                <i id="menu-icon-prestasi" class="fas fa-trophy text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                <span id="menu-label-prestasi" class="text-[9.5px] font-bold tracking-wider leading-none">Prestasi</span>
            </button>

            <!-- Menu 3: Unggulan -->
            <button type="button" onclick="switchFrame('unggulan')" id="menu-btn-unggulan" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 cursor-pointer py-0.5 px-2 group flex-1 opacity-70 hover:opacity-100" style="color: <?= $bottom_bar_text_color ?>;" title="Program Unggulan">
                <i id="menu-icon-unggulan" class="fas fa-star text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                <span id="menu-label-unggulan" class="text-[9.5px] font-bold tracking-wider leading-none">Unggulan</span>
            </button>

            <!-- Menu 4: Pengajar -->
            <button type="button" onclick="switchFrame('pengajar')" id="menu-btn-pengajar" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 cursor-pointer py-0.5 px-2 group flex-1 opacity-70 hover:opacity-100" style="color: <?= $bottom_bar_text_color ?>;" title="Dewan Pengajar">
                <i id="menu-icon-pengajar" class="fas fa-chalkboard-user text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                <span id="menu-label-pengajar" class="text-[9.5px] font-bold tracking-wider leading-none">Pengajar</span>
            </button>

            <!-- Menu 5: Fasilitas -->
            <button type="button" onclick="switchFrame('fasilitas')" id="menu-btn-fasilitas" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 cursor-pointer py-0.5 px-2 group flex-1 opacity-70 hover:opacity-100" style="color: <?= $bottom_bar_text_color ?>;" title="Sarana & Fasilitas">
                <i id="menu-icon-fasilitas" class="fas fa-building-columns text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                <span id="menu-label-fasilitas" class="text-[9.5px] font-bold tracking-wider leading-none">Fasilitas</span>
            </button>

        </div>

    </div>

    <!-- ======================================================= -->
    <!-- JAVASCRIPT LOGIKA SINKRONISASI 100% PERSIS SIMULASI     -->
    <!-- ======================================================= -->
    <script>
        // 1. DATA MASTER DARI PHP (5 FRAME LENGKAP)
        const framesData = {
            home: {
                id: 'home',
                name: 'Depan',
                bgUrl: <?= json_encode($cover_bg_url) ?>,
                bgOpacity: <?= (float)$cover_overlay_opacity ?>,
                texts: <?= json_encode($text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($button_items, JSON_UNESCAPED_UNICODE) ?>,
                slides: <?= json_encode($slide_items, JSON_UNESCAPED_UNICODE) ?>
            },
            prestasi: {
                id: 'prestasi',
                name: 'Prestasi',
                bgUrl: <?= json_encode($prestasi_bg_url) ?>,
                bgOpacity: <?= (float)$prestasi_overlay_opacity ?>,
                texts: <?= json_encode($prestasi_text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($prestasi_image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($prestasi_video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($prestasi_button_items, JSON_UNESCAPED_UNICODE) ?>,
                slides: <?= json_encode($prestasi_slide_items, JSON_UNESCAPED_UNICODE) ?>
            },
            unggulan: {
                id: 'unggulan',
                name: 'Unggulan',
                bgUrl: <?= json_encode($unggulan_bg_url) ?>,
                bgOpacity: <?= (float)$unggulan_overlay_opacity ?>,
                texts: <?= json_encode($unggulan_text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($unggulan_image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($unggulan_video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($unggulan_button_items, JSON_UNESCAPED_UNICODE) ?>,
                slides: <?= json_encode($unggulan_slide_items, JSON_UNESCAPED_UNICODE) ?>
            },
            pengajar: {
                id: 'pengajar',
                name: 'Pengajar',
                bgUrl: <?= json_encode($pengajar_bg_url) ?>,
                bgOpacity: <?= (float)$pengajar_overlay_opacity ?>,
                texts: <?= json_encode($pengajar_text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($pengajar_image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($pengajar_video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($pengajar_button_items, JSON_UNESCAPED_UNICODE) ?>,
                slides: <?= json_encode($pengajar_slide_items, JSON_UNESCAPED_UNICODE) ?>
            },
            fasilitas: {
                id: 'fasilitas',
                name: 'Fasilitas',
                bgUrl: <?= json_encode($fasilitas_bg_url) ?>,
                bgOpacity: <?= (float)$fasilitas_overlay_opacity ?>,
                texts: <?= json_encode($fasilitas_text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($fasilitas_image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($fasilitas_video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($fasilitas_button_items, JSON_UNESCAPED_UNICODE) ?>,
                slides: <?= json_encode($fasilitas_slide_items, JSON_UNESCAPED_UNICODE) ?>
            }
        };

        const frameKeys = ['home', 'prestasi', 'unggulan', 'pengajar', 'fasilitas'];
        let currentFrame = '<?= $active_frame_param ?>';
        const activeNavColor = '<?= $bottom_bar_active_color ?>';
        const normalNavColor = '<?= $bottom_bar_text_color ?>';
        const guestName = <?= json_encode($nama_tamu) ?>;

        // Slide State
        window.liveSlideIndices = { home: 0, prestasi: 0, unggulan: 0, pengajar: 0, fasilitas: 0 };
        window.liveSlideTimers = {};

        // Helper Typography & Format
        function getFontFamily(fontName) {
            if (!fontName) return "'Plus Jakarta Sans', sans-serif";
            if (fontName === 'Marlin Condensed') {
                return "'Marlin Condensed', 'Barlow Condensed', 'Oswald', sans-serif";
            }
            return `'${fontName}', sans-serif`;
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function replacePlaceholders(text) {
            if (!text) return '';
            return text.replace(/\{nama_tamu\}/gi, guestName)
                       .replace(/\{tamu\}/gi, guestName);
        }

        function getFormatHtml(content, format) {
            let text = replacePlaceholders(content || '').trim();
            if (!text) text = 'Villa Quran Indonesia';

            const safeText = escapeHtml(text).replace(/\n/g, "<br>");
            const fmt = (format || 'h2').toLowerCase();

            switch (fmt) {
                case 'h1':
                    return `<h1 class="font-black leading-tight tracking-tight" style="font-size: inherit; color: inherit; line-height: inherit;">${safeText}</h1>`;
                case 'h3':
                    return `<h3 class="font-bold leading-snug" style="font-size: inherit; color: inherit; line-height: inherit;">${safeText}</h3>`;
                case 'h4':
                    return `<h4 class="font-bold leading-normal" style="font-size: inherit; color: inherit; line-height: inherit;">${safeText}</h4>`;
                case 'h5':
                    return `<h5 class="font-semibold uppercase tracking-wider leading-normal" style="font-size: inherit; color: inherit; line-height: inherit;">${safeText}</h5>`;
                case 'p':
                    return `<p class="font-normal leading-relaxed" style="font-size: inherit; color: inherit; line-height: inherit;">${safeText}</p>`;
                case 'h2':
                default:
                    return `<h2 class="font-extrabold leading-tight" style="font-size: inherit; color: inherit; line-height: inherit;">${safeText}</h2>`;
            }
        }

        function parseVideoSource(url, options = {}) {
            if (!url) return { type: 'empty', url: '' };
            url = url.trim();

            const ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
            if (ytMatch && ytMatch[1]) {
                const vidId = ytMatch[1];
                const auto = options.autoplay ? 1 : 0;
                const mute = options.muted ? 1 : 1;
                const loop = options.loop ? `1&playlist=${vidId}` : '0';
                const controls = options.controls ? 1 : 0;
                const embedUrl = `https://www.youtube.com/embed/${vidId}?autoplay=${auto}&mute=${mute}&loop=${loop}&controls=${controls}&playsinline=1&enablejsapi=1`;
                return { type: 'youtube', embedUrl, vidId };
            }

            return { type: 'direct', url: url };
        }

        // =======================================================
        // SWITCH FRAME LOGIC (SEAMLESS PERSIS SIMULASI)
        // =======================================================
        function switchFrame(frame) {
            if (!frameKeys.includes(frame)) frame = 'home';
            currentFrame = frame;

            const data = framesData[frame];
            if (!data) return;

            // 1. Update Background & Overlay
            const bgScreen = document.getElementById('live-bg-screen');
            const bgOverlay = document.getElementById('live-bg-overlay');
            if (bgScreen) {
                if (data.bgUrl) {
                    bgScreen.style.backgroundImage = `url('${data.bgUrl}')`;
                } else {
                    bgScreen.style.backgroundImage = 'none';
                }
            }
            if (bgOverlay) {
                bgOverlay.style.opacity = data.bgOpacity;
            }

            // 2. Update Bottom Bar Highlight
            frameKeys.forEach(f => {
                const btn = document.getElementById(`menu-btn-${f}`);
                if (btn) {
                    if (f === frame) {
                        btn.style.color = activeNavColor;
                        btn.classList.remove('opacity-70');
                    } else {
                        btn.style.color = normalNavColor;
                        btn.classList.add('opacity-70');
                    }
                }
            });

            // 3. Render All Layers for the Current Frame
            renderImages(data.images || []);
            renderSlides(data.slides || []);
            renderVideos(data.videos || []);
            renderTexts(data.texts || []);
            renderButtons(data.buttons || []);

            // 4. Update URL without reload
            if (history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('frame', frame);
                history.replaceState(null, null, url.toString());
            }
        }

        // =======================================================
        // RENDER 1: IMAGES
        // =======================================================
        function renderImages(items) {
            const container = document.getElementById('live-image-layers-container');
            if (!container) return;
            container.innerHTML = '';

            items.forEach((img, index) => {
                const box = document.createElement('div');
                box.className = 'absolute pointer-events-auto transition-transform fade-in-layer';
                box.style.top = `${img.posY || 50}%`;
                box.style.left = `${img.posX || 50}%`;
                box.style.transform = `translate(-50%, -50%) rotate(${img.rotation || 0}deg)`;
                box.style.width = `${img.width || 50}%`;
                box.style.zIndex = 20 + index;

                const shapeClass = `shape-${img.shape || 'rounded'}`;
                const shadowClass = (img.shadow_style && img.shadow_style !== 'none') ? `shadow-${img.shadow_style}` : '';
                
                let borderStyle = '';
                if (img.border_enable) {
                    const bw = img.border_width || 2;
                    const bc = img.border_color || '#ffffff';
                    const bs = img.border_style || 'solid';
                    borderStyle = `border: ${bw}px ${bs} ${bc};`;
                }

                box.innerHTML = `
                    <div class="w-full h-full overflow-hidden ${shapeClass} ${shadowClass}" style="${borderStyle}">
                        <img src="${escapeHtml(img.url)}" class="w-full h-full object-cover select-none pointer-events-none" loading="lazy" alt="Foto">
                    </div>
                `;
                container.appendChild(box);
            });
        }

        // =======================================================
        // RENDER 2: SLIDES SHOWCASE (CAROUSEL)
        // =======================================================
        function renderSlides(items) {
            const container = document.getElementById('live-slide-layers-container');
            if (!container) return;

            // Bersihkan timer lama
            if (window.liveSlideTimers[currentFrame]) {
                clearInterval(window.liveSlideTimers[currentFrame]);
                window.liveSlideTimers[currentFrame] = null;
            }

            container.innerHTML = '';
            if (!items || items.length === 0) return;

            const activeIndex = (window.liveSlideIndices[currentFrame] || 0) % items.length;
            const primarySlide = items[0] || {};

            const box = document.createElement('div');
            box.className = 'absolute pointer-events-auto transition-all overflow-hidden shadow-lg fade-in-layer';
            box.style.top = `${primarySlide.posY || 48}%`;
            box.style.left = `${primarySlide.posX || 50}%`;
            box.style.transform = 'translate(-50%, -50%)';
            box.style.width = `${primarySlide.width || 88}%`;
            box.style.height = `${primarySlide.height || 190}px`;
            box.style.zIndex = 22;

            let shapeBorderRadius = '16px';
            if (primarySlide.shape === 'kotak') shapeBorderRadius = '4px';
            else if (primarySlide.shape === 'kubah') shapeBorderRadius = '999px 999px 16px 16px';
            else if (primarySlide.shape === 'oval') shapeBorderRadius = '50%';
            box.style.borderRadius = shapeBorderRadius;

            if (primarySlide.border_enable) {
                box.style.border = `${primarySlide.border_width || 2}px solid ${primarySlide.border_color || '#ffffff'}`;
            }

            let slidesHtml = '';
            items.forEach((sld, sIdx) => {
                const isActive = (sIdx === activeIndex);
                slidesHtml += `
                    <div class="absolute inset-0 w-full h-full transition-all duration-700 ease-in-out ${isActive ? 'opacity-100 scale-100 z-10' : 'opacity-0 scale-95 z-0 pointer-events-none'}" style="background-image: url('${escapeHtml(sld.url)}'); background-size: cover; background-position: center;">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-black/10"></div>
                        <div class="absolute inset-0 p-3.5 flex flex-col justify-end text-white z-10">
                            ${sld.badge ? `<span class="self-start px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-500 text-teal-950 shadow-xs mb-1">${escapeHtml(sld.badge)}</span>` : ''}
                            ${sld.title ? `<h4 class="text-sm font-black leading-tight drop-shadow-md">${escapeHtml(sld.title)}</h4>` : ''}
                            ${sld.subtitle ? `<p class="text-[10px] text-slate-200 mt-0.5 line-clamp-2 leading-tight opacity-90">${escapeHtml(sld.subtitle)}</p>` : ''}
                            ${sld.btn_text ? `
                                <a href="${escapeHtml(sld.btn_url || '#')}" target="_blank" class="mt-2 self-start px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-[10.5px] shadow-md inline-flex items-center gap-1 active:scale-95 transition">
                                    <span>${escapeHtml(sld.btn_text)}</span>
                                    <i class="fas fa-chevron-right text-[8px]"></i>
                                </a>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            // Dots Nav
            let dotsHtml = '';
            if (primarySlide.show_dots !== 0 && items.length > 1) {
                dotsHtml = `<div class="absolute bottom-2 left-0 right-0 z-20 flex items-center justify-center gap-1.5 pointer-events-auto">`;
                items.forEach((_, dIdx) => {
                    dotsHtml += `
                        <button type="button" onclick="setLiveActiveSlide('${currentFrame}', ${dIdx})" class="w-2 h-2 rounded-full transition-all duration-300 ${dIdx === activeIndex ? 'bg-amber-400 w-5' : 'bg-white/50 hover:bg-white'}" title="Slide ${dIdx + 1}"></button>
                    `;
                });
                dotsHtml += `</div>`;
            }

            // Arrows Nav
            let arrowsHtml = '';
            if (primarySlide.show_arrows && items.length > 1) {
                arrowsHtml = `
                    <button type="button" onclick="prevLiveSlide('${currentFrame}')" class="absolute left-1.5 top-1/2 -translate-y-1/2 z-20 w-6 h-6 rounded-full bg-black/50 hover:bg-black/80 text-white text-[10px] flex items-center justify-center backdrop-blur-xs transition active:scale-90 pointer-events-auto cursor-pointer">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" onclick="nextLiveSlide('${currentFrame}')" class="absolute right-1.5 top-1/2 -translate-y-1/2 z-20 w-6 h-6 rounded-full bg-black/50 hover:bg-black/80 text-white text-[10px] flex items-center justify-center backdrop-blur-xs transition active:scale-90 pointer-events-auto cursor-pointer">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }

            box.innerHTML = slidesHtml + dotsHtml + arrowsHtml;
            container.appendChild(box);

            // Autoplay Timer
            if (primarySlide.autoplay !== 0 && items.length > 1) {
                const intervalSec = Math.max(2, parseInt(primarySlide.interval) || 4);
                window.liveSlideTimers[currentFrame] = setInterval(() => {
                    nextLiveSlide(currentFrame);
                }, intervalSec * 1000);
            }
        }

        function setLiveActiveSlide(frame, index) {
            window.liveSlideIndices[frame] = index;
            if (framesData[frame]) {
                renderSlides(framesData[frame].slides || []);
            }
        }

        function nextLiveSlide(frame) {
            const slides = (framesData[frame] && framesData[frame].slides) ? framesData[frame].slides : [];
            if (slides.length <= 1) return;
            let current = window.liveSlideIndices[frame] || 0;
            current = (current + 1) % slides.length;
            setLiveActiveSlide(frame, current);
        }

        function prevLiveSlide(frame) {
            const slides = (framesData[frame] && framesData[frame].slides) ? framesData[frame].slides : [];
            if (slides.length <= 1) return;
            let current = window.liveSlideIndices[frame] || 0;
            current = (current - 1 + slides.length) % slides.length;
            setLiveActiveSlide(frame, current);
        }

        // =======================================================
        // RENDER 3: VIDEOS
        // =======================================================
        function renderVideos(items) {
            const container = document.getElementById('live-video-layers-container');
            if (!container) return;
            container.innerHTML = '';

            items.forEach((vid, index) => {
                const box = document.createElement('div');
                box.className = 'absolute pointer-events-auto transition-transform fade-in-layer';
                box.style.top = `${vid.posY || 50}%`;
                box.style.left = `${vid.posX || 50}%`;
                box.style.transform = `translate(-50%, -50%) rotate(${vid.rotation || 0}deg)`;
                box.style.width = `${vid.width || 75}%`;
                box.style.zIndex = 25 + index;

                const shapeClass = `shape-${vid.shape || 'persegi_panjang_wide'}`;
                const shadowClass = (vid.shadow_style && vid.shadow_style !== 'none') ? `shadow-${vid.shadow_style}` : '';
                
                let borderStyle = '';
                if (vid.border_enable) {
                    const bw = vid.border_width || 2;
                    const bc = vid.border_color || '#ffffff';
                    const bs = vid.border_style || 'solid';
                    borderStyle = `border: ${bw}px ${bs} ${bc};`;
                }

                const parsed = parseVideoSource(vid.url, {
                    autoplay: vid.autoplay !== 0,
                    muted: vid.muted !== 0,
                    loop: vid.loop !== 0,
                    controls: vid.controls ? 1 : 0
                });

                let videoInnerHtml = '';
                if (parsed.type === 'youtube') {
                    videoInnerHtml = `
                        <iframe src="${parsed.embedUrl}" class="w-full h-full border-0 pointer-events-auto" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    `;
                } else if (parsed.type === 'direct' && parsed.url) {
                    const autoAttr = (vid.autoplay !== 0) ? 'autoplay' : '';
                    const loopAttr = (vid.loop !== 0) ? 'loop' : '';
                    const muteAttr = (vid.muted !== 0) ? 'muted' : '';
                    const ctrlAttr = (vid.controls) ? 'controls' : '';
                    videoInnerHtml = `
                        <video src="${escapeHtml(parsed.url)}" ${autoAttr} ${loopAttr} ${muteAttr} ${ctrlAttr} playsinline class="w-full h-full object-cover pointer-events-auto"></video>
                    `;
                }

                box.innerHTML = `
                    <div class="w-full h-full overflow-hidden ${shapeClass} ${shadowClass} relative bg-black" style="${borderStyle}">
                        ${videoInnerHtml}
                    </div>
                `;
                container.appendChild(box);
            });
        }

        // =======================================================
        // RENDER 4: TEXTS
        // =======================================================
        function renderTexts(items) {
            const container = document.getElementById('live-text-layers-container');
            if (!container) return;
            container.innerHTML = '';

            items.forEach((item, index) => {
                const box = document.createElement('div');
                box.className = 'absolute pointer-events-auto fade-in-layer';
                box.style.top = `${item.posY || 35}%`;
                box.style.left = `${item.posX || 50}%`;
                box.style.transform = 'translate(-50%, 0)';
                box.style.width = `${item.width || 85}%`;
                box.style.zIndex = 30 + index;

                box.innerHTML = `
                    <div style="color: ${item.color || '#ffffff'}; font-family: ${getFontFamily(item.font)}; text-align: ${item.align || 'center'}; font-size: ${item.size || 24}px; word-break: break-word; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                        ${getFormatHtml(item.content, item.format)}
                    </div>
                `;
                container.appendChild(box);
            });
        }

        // =======================================================
        // RENDER 5: BUTTONS
        // =======================================================
        function renderButtons(items) {
            const container = document.getElementById('live-button-layers-container');
            if (!container) return;
            container.innerHTML = '';

            items.forEach((btn, index) => {
                const box = document.createElement('div');
                box.className = 'absolute pointer-events-auto transition-transform active:scale-95 fade-in-layer';
                box.style.top = `${btn.posY || 80}%`;
                box.style.left = `${btn.posX || 50}%`;
                box.style.transform = 'translate(-50%, -50%)';

                const btnHeight = btn.height || (btn.shape === 'bulat' ? 48 : 46);
                box.style.width = (btn.shape === 'bulat') ? `${btnHeight}px` : `${btn.width || 80}%`;
                box.style.height = `${btnHeight}px`;
                box.style.zIndex = 35 + index;

                let shapeClass = 'shape-btn-rounded_pill';
                if (btn.shape === 'persegipanjang') shapeClass = 'shape-btn-persegipanjang';
                else if (btn.shape === 'bulat') shapeClass = 'shape-btn-bulat';
                else if (btn.shape === 'rounded') shapeClass = 'shape-btn-rounded';

                const shadowClass = btn.shadow_style && btn.shadow_style !== 'none' ? `shadow-${btn.shadow_style}` : '';
                const borderStyle = btn.border_enable ? `border: ${btn.border_width || 2}px solid ${btn.border_color || '#ffffff'};` : '';
                const fontFamily = getFontFamily(btn.font);

                let iconHtml = '';
                if (btn.icon && btn.icon !== 'none') {
                    iconHtml = `<i class="${btn.icon} ${btn.shape === 'bulat' ? 'text-lg' : 'text-base mr-2'}"></i>`;
                }

                const targetAttr = btn.target === '_self' ? '_self' : '_blank';
                const finalUrl = escapeHtml(replacePlaceholders(btn.url || '#'));

                let innerContent = '';
                if (btn.shape === 'bulat') {
                    innerContent = `
                        <a href="${finalUrl}" target="${targetAttr}" class="${shapeClass} ${shadowClass} w-full h-full flex items-center justify-center text-center font-bold transition transform hover:scale-105 active:scale-95 shadow-lg" style="background-color: ${btn.bg_color || '#25d366'}; color: ${btn.text_color || '#ffffff'}; font-size: ${btn.font_size || 16}px; ${borderStyle}">
                            ${iconHtml}
                        </a>
                    `;
                } else {
                    innerContent = `
                        <a href="${finalUrl}" target="${targetAttr}" class="${shapeClass} ${shadowClass} w-full h-full px-4 flex items-center justify-center text-center font-bold transition transform hover:scale-102 active:scale-95 shadow-lg select-none" style="background-color: ${btn.bg_color || '#25d366'}; color: ${btn.text_color || '#ffffff'}; font-size: ${btn.font_size || 14}px; font-family: ${fontFamily}; ${borderStyle}">
                            ${iconHtml}
                            <span class="tracking-wide truncate">${escapeHtml(btn.text || 'Tombol Aksi')}</span>
                        </a>
                    `;
                }

                box.innerHTML = innerContent;
                container.appendChild(box);
            });
        }

        // =======================================================
        // AUDIO & BACKSOUND CONTROLLER
        // =======================================================
        let isAudioPlaying = false;
        const audioPlayer = document.getElementById('audio-player');
        const audioIcon = document.getElementById('audio-toggle-icon');
        const audioBtn = document.getElementById('btn-audio-toggle');

        function toggleAudio() {
            if (!audioPlayer) return;
            if (isAudioPlaying) {
                audioPlayer.pause();
                isAudioPlaying = false;
                if (audioBtn) audioBtn.classList.remove('text-amber-400', 'ring-2', 'ring-amber-400/50');
                if (audioIcon) {
                    audioIcon.className = 'fas fa-volume-xmark text-[11px]';
                    audioIcon.classList.remove('spin-vinyl');
                }
            } else {
                audioPlayer.play().then(() => {
                    isAudioPlaying = true;
                    if (audioBtn) audioBtn.classList.add('text-amber-400', 'ring-2', 'ring-amber-400/50');
                    if (audioIcon) {
                        audioIcon.className = 'fas fa-compact-disc text-[11px] spin-vinyl';
                    }
                }).catch(e => {
                    console.log('Audio autoplay prevented by browser:', e);
                });
            }
        }

        // Auto try to play on first user interaction
        function autoPlayOnFirstTouch() {
            if (!isAudioPlaying && audioPlayer) {
                audioPlayer.play().then(() => {
                    isAudioPlaying = true;
                    if (audioBtn) audioBtn.classList.add('text-amber-400', 'ring-2', 'ring-amber-400/50');
                    if (audioIcon) {
                        audioIcon.className = 'fas fa-compact-disc text-[11px] spin-vinyl';
                    }
                }).catch(() => {});
            }
            document.removeEventListener('click', autoPlayOnFirstTouch);
            document.removeEventListener('touchstart', autoPlayOnFirstTouch);
        }
        document.addEventListener('click', autoPlayOnFirstTouch, { once: true });
        document.addEventListener('touchstart', autoPlayOnFirstTouch, { once: true });

        // =======================================================
        // SHARE BROSUR HANDLER
        // =======================================================
        function shareBrosur() {
            const shareData = {
                title: 'Brosur Digital Villa Quran Indonesia',
                text: 'Undangan Khusus & Brosur Informasi Pendidikan Santri Tahfidz Bersanad Villa Quran Indonesia.',
                url: window.location.href
            };
            if (navigator.share) {
                navigator.share(shareData).catch(() => {});
            } else if (navigator.clipboard) {
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link brosur berhasil disalin ke clipboard!');
                });
            }
        }

        // =======================================================
        // TOUCH SWIPE GESTURE SUPPORT FOR SMARTPHONE
        // =======================================================
        let touchStartX = 0;
        let touchStartY = 0;

        document.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            touchStartY = e.changedTouches[0].screenY;
        }, { passive: true });

        document.addEventListener('touchend', (e) => {
            const touchEndX = e.changedTouches[0].screenX;
            const touchEndY = e.changedTouches[0].screenY;
            const diffX = touchEndX - touchStartX;
            const diffY = touchEndY - touchStartY;

            // Pastikan swipe horizontal lebih dominan daripada vertical
            if (Math.abs(diffX) > 60 && Math.abs(diffX) > Math.abs(diffY) * 1.5) {
                const currentIndex = frameKeys.indexOf(currentFrame);
                if (diffX < 0) {
                    // Swipe ke Kiri -> Frame Berikutnya
                    const nextIndex = (currentIndex + 1) % frameKeys.length;
                    switchFrame(frameKeys[nextIndex]);
                } else {
                    // Swipe ke Kanan -> Frame Sebelumnya
                    const prevIndex = (currentIndex - 1 + frameKeys.length) % frameKeys.length;
                    switchFrame(frameKeys[prevIndex]);
                }
            }
        }, { passive: true });

        // INITIAL LOAD
        document.addEventListener('DOMContentLoaded', () => {
            switchFrame(currentFrame);
        });
    </script>
</body>
</html>
