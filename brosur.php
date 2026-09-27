<?php
// brosur.php
// Halaman Khusus Brosur & Undangan Digital Smartphone Villa Quran Indonesia
// 100% Sinkron & Presisi Sesuai Layar Simulasi Admin (Adaptive Canvas Scaling + Multi-Frame Dinamis)

require_once 'koneksi.php';

// Pastikan kolom untuk dynamic multi-frame tersedia di database
$columns_to_check = [
    'custom_text_items'        => "LONGTEXT",
    'custom_image_items'       => "LONGTEXT",
    'custom_video_items'       => "LONGTEXT",
    'custom_button_items'      => "LONGTEXT",
    'bottom_bar_bg_color'      => "VARCHAR(100) DEFAULT '#022d27'",
    'bottom_bar_text_color'    => "VARCHAR(30) DEFAULT '#ffffff'",
    'bottom_bar_active_color'  => "VARCHAR(30) DEFAULT '#fbbf24'",
    'prestasi_bg_url'          => "TEXT",
    'prestasi_overlay_opacity' => "DECIMAL(3,2) DEFAULT 0.88",
    'prestasi_text_items'      => "LONGTEXT",
    'prestasi_image_items'     => "LONGTEXT",
    'prestasi_video_items'     => "LONGTEXT",
    'prestasi_button_items'    => "LONGTEXT",
    'unggulan_bg_url'          => "TEXT",
    'unggulan_overlay_opacity' => "DECIMAL(3,2) DEFAULT 0.88",
    'unggulan_text_items'      => "LONGTEXT",
    'unggulan_image_items'     => "LONGTEXT",
    'unggulan_video_items'     => "LONGTEXT",
    'unggulan_button_items'    => "LONGTEXT",
    'pengajar_bg_url'          => "TEXT",
    'pengajar_overlay_opacity' => "DECIMAL(3,2) DEFAULT 0.88",
    'pengajar_text_items'      => "LONGTEXT",
    'pengajar_image_items'     => "LONGTEXT",
    'pengajar_video_items'     => "LONGTEXT",
    'pengajar_button_items'    => "LONGTEXT",
    'custom_slide_items'       => "LONGTEXT",
    'prestasi_slide_items'     => "LONGTEXT",
    'unggulan_slide_items'     => "LONGTEXT",
    'pengajar_slide_items'     => "LONGTEXT",
    'fasilitas_bg_url'          => "TEXT",
    'fasilitas_overlay_opacity' => "DECIMAL(3,2) DEFAULT 0.88",
    'fasilitas_text_items'      => "LONGTEXT",
    'fasilitas_image_items'     => "LONGTEXT",
    'fasilitas_video_items'     => "LONGTEXT",
    'fasilitas_button_items'    => "LONGTEXT",
    'fasilitas_slide_items'     => "LONGTEXT",
    'all_frames_json'           => "LONGTEXT"
];
foreach ($columns_to_check as $col => $type) {
    $res = $conn->query("SHOW COLUMNS FROM pengaturan_brosur LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        $conn->query("ALTER TABLE pengaturan_brosur ADD COLUMN $col $type");
    }
}

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

// 6. BUILD DYNAMIC FRAMES DICTIONARY
$all_frames_dict = [];
$raw_all_frames = $cfg['all_frames_json'] ?? null;
if (!empty($raw_all_frames)) {
    $decoded_all = json_decode($raw_all_frames, true);
    if (is_array($decoded_all) && !empty($decoded_all)) {
        $all_frames_dict = $decoded_all;
    }
}

// Fallback initial default frames jika belum ada di all_frames_json
if (empty($all_frames_dict)) {
    $all_frames_dict = [
        'home' => [
            'id'           => 'home',
            'name'         => 'Depan',
            'title'        => 'Frame: Depan',
            'subtitle'     => 'Frame ini mengatur tampilan layar pertama saat calon wali santri membuka brosur digital (Menu <strong>Depan</strong> pada Bottom Navigation Bar).',
            'badge'        => 'Cover / Halaman Depan',
            'menuPill'     => 'Menu #1 di Bottom Bar',
            'icon'         => 'fa-house',
            'theme'        => 'emerald',
            'iconGradient' => 'from-emerald-500 to-[#0b8478]',
            'badgeClass'   => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            'bgUrl'        => $cover_bg_url,
            'bgOpacity'    => $cover_overlay_opacity,
            'texts'        => $text_items,
            'images'       => $image_items,
            'videos'       => $video_items,
            'buttons'      => $button_items,
            'slides'       => $slide_items
        ],
        'prestasi' => [
            'id'           => 'prestasi',
            'name'         => 'Prestasi',
            'title'        => 'Frame: Prestasi',
            'subtitle'     => 'Frame ini mengatur tampilan galeri pencapaian, piala, medali & prestasi santri (Menu <strong>Prestasi</strong> pada Bottom Navigation Bar).',
            'badge'        => 'Menu Prestasi Brosur',
            'menuPill'     => 'Menu #2 di Bottom Bar',
            'icon'         => 'fa-trophy',
            'theme'        => 'amber',
            'iconGradient' => 'from-amber-500 to-amber-600',
            'badgeClass'   => 'bg-amber-100 text-amber-900 border-amber-300',
            'bgUrl'        => $prestasi_bg_url,
            'bgOpacity'    => (float)$prestasi_overlay_opacity,
            'texts'        => $prestasi_text_items,
            'images'       => $prestasi_image_items,
            'videos'       => $prestasi_video_items,
            'buttons'      => $prestasi_button_items,
            'slides'       => $prestasi_slide_items
        ],
        'unggulan' => [
            'id'           => 'unggulan',
            'name'         => 'Unggulan',
            'title'        => 'Frame: Unggulan',
            'subtitle'     => 'Frame ini mengatur tampilan program, fasilitas & keunggulan pesantren (Menu <strong>Unggulan</strong> pada Bottom Navigation Bar).',
            'badge'        => 'Program & Keunggulan',
            'menuPill'     => 'Menu #3 di Bottom Bar',
            'icon'         => 'fa-star',
            'theme'        => 'orange',
            'iconGradient' => 'from-amber-500 via-orange-500 to-amber-600',
            'badgeClass'   => 'bg-orange-100 text-orange-900 border-orange-300',
            'bgUrl'        => $unggulan_bg_url,
            'bgOpacity'    => (float)$unggulan_overlay_opacity,
            'texts'        => $unggulan_text_items,
            'images'       => $unggulan_image_items,
            'videos'       => $unggulan_video_items,
            'buttons'      => $unggulan_button_items,
            'slides'       => $unggulan_slide_items
        ],
        'pengajar' => [
            'id'           => 'pengajar',
            'name'         => 'Pengajar',
            'title'        => 'Frame: Pengajar',
            'subtitle'     => 'Frame ini mengatur tampilan profil asatidz, dewan guru & pengasuh (Menu <strong>Pengajar</strong> pada Bottom Navigation Bar).',
            'badge'        => 'Dewan Pengajar & Asatidz',
            'menuPill'     => 'Menu #4 di Bottom Bar',
            'icon'         => 'fa-chalkboard-user',
            'theme'        => 'teal',
            'iconGradient' => 'from-teal-500 via-emerald-600 to-cyan-600',
            'badgeClass'   => 'bg-teal-100 text-teal-900 border-teal-300',
            'bgUrl'        => $pengajar_bg_url,
            'bgOpacity'    => (float)$pengajar_overlay_opacity,
            'texts'        => $pengajar_text_items,
            'images'       => $pengajar_image_items,
            'videos'       => $pengajar_video_items,
            'buttons'      => $pengajar_button_items,
            'slides'       => $pengajar_slide_items
        ],
        'fasilitas' => [
            'id'           => 'fasilitas',
            'name'         => 'Fasilitas',
            'title'        => 'Frame: Fasilitas',
            'subtitle'     => 'Frame ini mengatur tampilan sarana, prasarana, asrama & fasilitas pesantren (Menu <strong>Fasilitas</strong> pada Bottom Navigation Bar).',
            'badge'        => 'Sarana & Fasilitas',
            'menuPill'     => 'Menu #5 di Bottom Bar',
            'icon'         => 'fa-building-columns',
            'theme'        => 'sky',
            'iconGradient' => 'from-sky-500 via-blue-600 to-indigo-600',
            'badgeClass'   => 'bg-sky-100 text-sky-900 border-sky-300',
            'bgUrl'        => $fasilitas_bg_url,
            'bgOpacity'    => (float)$fasilitas_overlay_opacity,
            'texts'        => $fasilitas_text_items,
            'images'       => $fasilitas_image_items,
            'videos'       => $fasilitas_video_items,
            'buttons'      => $fasilitas_button_items,
            'slides'       => $fasilitas_slide_items
        ]
    ];
} else {
    // Sinkronisasi data kolom terkini ke dalam frame standar
    $std_merge_map = [
        'home' => [
            'bgUrl'     => $cover_bg_url,
            'bgOpacity' => $cover_overlay_opacity,
            'texts'     => $text_items,
            'images'    => $image_items,
            'videos'    => $video_items,
            'buttons'   => $button_items,
            'slides'    => $slide_items
        ],
        'prestasi' => [
            'bgUrl'     => $prestasi_bg_url,
            'bgOpacity' => (float)$prestasi_overlay_opacity,
            'texts'     => $prestasi_text_items,
            'images'    => $prestasi_image_items,
            'videos'    => $prestasi_video_items,
            'buttons'   => $prestasi_button_items,
            'slides'    => $prestasi_slide_items
        ],
        'unggulan' => [
            'bgUrl'     => $unggulan_bg_url,
            'bgOpacity' => (float)$unggulan_overlay_opacity,
            'texts'     => $unggulan_text_items,
            'images'    => $unggulan_image_items,
            'videos'    => $unggulan_video_items,
            'buttons'   => $unggulan_button_items,
            'slides'    => $unggulan_slide_items
        ],
        'pengajar' => [
            'bgUrl'     => $pengajar_bg_url,
            'bgOpacity' => (float)$pengajar_overlay_opacity,
            'texts'     => $pengajar_text_items,
            'images'    => $pengajar_image_items,
            'videos'    => $pengajar_video_items,
            'buttons'   => $pengajar_button_items,
            'slides'    => $pengajar_slide_items
        ],
        'fasilitas' => [
            'bgUrl'     => $fasilitas_bg_url,
            'bgOpacity' => (float)$fasilitas_overlay_opacity,
            'texts'     => $fasilitas_text_items,
            'images'    => $fasilitas_image_items,
            'videos'    => $fasilitas_video_items,
            'buttons'   => $fasilitas_button_items,
            'slides'    => $fasilitas_slide_items
        ]
    ];

    foreach ($std_merge_map as $fKey => $fData) {
        if (!isset($all_frames_dict[$fKey])) {
            $all_frames_dict[$fKey] = [
                'id'       => $fKey,
                'name'     => ucfirst($fKey === 'home' ? 'Depan' : $fKey),
                'icon'     => ($fKey === 'home' ? 'fa-house' : ($fKey === 'prestasi' ? 'fa-trophy' : ($fKey === 'unggulan' ? 'fa-star' : ($fKey === 'pengajar' ? 'fa-chalkboard-user' : 'fa-building-columns')))),
                'theme'    => ($fKey === 'home' ? 'emerald' : ($fKey === 'prestasi' ? 'amber' : ($fKey === 'unggulan' ? 'orange' : ($fKey === 'pengajar' ? 'teal' : 'sky')))),
            ];
        }
        if (!empty($fData['texts']))   $all_frames_dict[$fKey]['texts']   = $fData['texts'];
        if (!empty($fData['images']))  $all_frames_dict[$fKey]['images']  = $fData['images'];
        if (!empty($fData['videos']))  $all_frames_dict[$fKey]['videos']  = $fData['videos'];
        if (!empty($fData['buttons'])) $all_frames_dict[$fKey]['buttons'] = $fData['buttons'];
        if (!empty($fData['slides']))  $all_frames_dict[$fKey]['slides']  = $fData['slides'];
        if (!empty($fData['bgUrl']))   $all_frames_dict[$fKey]['bgUrl']   = $fData['bgUrl'];
        if (isset($fData['bgOpacity'])) $all_frames_dict[$fKey]['bgOpacity'] = $fData['bgOpacity'];
    }
}

$active_frame_param = isset($_GET['frame']) ? trim($_GET['frame']) : 'home';
if (!isset($all_frames_dict[$active_frame_param])) {
    $active_frame_param = array_key_first($all_frames_dict) ?? 'home';
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
            width: 100%;
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

        /* Ambient luxury pattern on desktop backdrop */
        .desktop-backdrop {
            background-image: radial-gradient(rgba(14, 165, 233, 0.12) 1px, transparent 1px), radial-gradient(rgba(16, 185, 129, 0.08) 1px, transparent 1px);
            background-size: 32px 32px, 24px 24px;
            background-position: 0 0, 12px 12px;
        }

        /* Base Simulation Canvas: Persis 1:1 dengan .bg-simulation-canvas di admin-brosur-settings.php (340px x 600px) */
        #brosur-canvas-box {
            width: 340px;
            height: 600px;
            min-width: 340px;
            min-height: 600px;
            max-width: 340px;
            max-height: 600px;
            position: relative;
            overflow: hidden;
            background-color: #0f172a;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.85), 0 0 0 1px rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            transform-origin: center center;
            user-select: none;
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
        .shape-btn-persegipanjang { border-radius: 4px !important; }
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
            animation: fadeInLayer 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        @keyframes fadeInLayer {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="desktop-backdrop flex items-center justify-center min-h-screen w-screen overflow-hidden select-none">

    <!-- AUDIO BACKSOUND ELEMENT -->
    <audio id="audio-player" loop preload="auto">
        <source src="<?= htmlspecialchars($music_url) ?>" type="audio/mpeg">
        <source src="upload/backsound.mp3" type="audio/mpeg">
        <source src="https://archive.org/download/IslamicBackgroundSoundsAahat/28-ISLAMIC%20BACKGROUND%20SOUNDS.mp3" type="audio/mpeg">
    </audio>

    <!-- FULLSCREEN AMBIENT BACKGROUND SEAMLESS (PADA SMARTPHONE & DESKTOP) -->
    <div id="ambient-bg-screen" class="fixed inset-0 w-full h-full bg-cover bg-center transition-all duration-500 ease-out -z-10" style="background-image: url('<?= htmlspecialchars($cover_bg_url) ?>');">
        <div id="ambient-bg-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-opacity duration-500" style="opacity: <?= $cover_overlay_opacity ?>;"></div>
    </div>

    <!-- FLOATING TOP CONTROLS (MUSIC & SHARE) -->
    <div class="fixed top-3 left-3 right-3 z-50 flex items-center justify-between pointer-events-none max-w-lg mx-auto">
        <!-- Badge Brand -->
        <div class="pointer-events-auto flex items-center gap-1.5 px-3 py-1 rounded-full bg-black/50 backdrop-blur-md border border-white/10 text-white shadow-md">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-[10px] font-extrabold tracking-wider uppercase text-emerald-300">Villa Quran</span>
        </div>

        <!-- Action Buttons: Audio & Share -->
        <div class="pointer-events-auto flex items-center gap-2">
            <!-- Music Toggle Button -->
            <button type="button" id="btn-audio-toggle" onclick="toggleAudio()" class="w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 backdrop-blur-md border border-white/15 text-amber-300 flex items-center justify-center text-xs shadow-md transition active:scale-90 cursor-pointer" title="Putar / Hentikan Musik">
                <i id="audio-toggle-icon" class="fas fa-music text-[11px]"></i>
            </button>

            <!-- Share Button -->
            <button type="button" onclick="shareBrosur()" class="w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 backdrop-blur-md border border-white/15 text-sky-300 flex items-center justify-center text-xs shadow-md transition active:scale-90 cursor-pointer" title="Bagikan Brosur Ini">
                <i class="fas fa-share-nodes text-[11px]"></i>
            </button>
        </div>
    </div>

    <!-- WRAPPER KANVAS RESPONSIVE AUTO-SCALER (PERSIS 340px x 600px SIMULASI ADMIN) -->
    <div id="brosur-canvas-box" class="relative">

        <!-- GAMBAR BACKGROUND PORTRAIT -->
        <div id="preview-screen-cover" class="absolute inset-0 w-full h-full bg-cover bg-center transition-all duration-300" style="background-image: url('<?= htmlspecialchars($cover_bg_url) ?>');">
            
            <!-- LAPISAN OVERLAY DINAMIS -->
            <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cover_overlay_opacity ?>;"></div>

            <!-- CONTAINER LAYER GAMBAR SISIPAN DI LAYAR SIMULASI -->
            <div id="sim-image-layers-container" class="absolute inset-0 pointer-events-none z-20"></div>

            <!-- CONTAINER LAYER SLIDE SHOWCASE DI LAYAR SIMULASI -->
            <div id="sim-slide-layers-container" class="absolute inset-0 pointer-events-none z-22"></div>

            <!-- CONTAINER LAYER VIDEO SISIPAN DI LAYAR SIMULASI -->
            <div id="sim-video-layers-container" class="absolute inset-0 pointer-events-none z-25"></div>

            <!-- CONTAINER LAYER TULISAN DI LAYAR SIMULASI -->
            <div id="sim-text-layers-container" class="absolute inset-0 pointer-events-none z-30"></div>

            <!-- CONTAINER LAYER TOMBOL DI LAYAR SIMULASI -->
            <div id="sim-button-layers-container" class="absolute inset-0 pointer-events-none z-35"></div>

            <!-- DOCKED BOTTOM NAVIGATION BAR DI LAYAR SIMULASI (DINAMIS SEMUA FRAME) -->
            <div id="sim-bottom-bar" class="absolute bottom-0 left-0 right-0 z-40 pt-2.5 pb-2 px-2 border-t border-white/10 backdrop-blur-md flex items-center justify-around overflow-x-auto no-scrollbar shadow-[0_-8px_20px_rgba(0,0,0,0.4)] transition-all duration-200" style="background-color: <?= htmlspecialchars($bottom_bar_bg_color) ?>; color: <?= htmlspecialchars($bottom_bar_text_color) ?>;">
                <!-- Diisi dinamis oleh renderPublicBottomBar() -->
            </div>

        </div>

    </div>

    <!-- ======================================================= -->
    <!-- JAVASCRIPT LOGIKA SINKRONISASI 100% PERSIS SIMULASI     -->
    <!-- ======================================================= -->
    <script>
        // 1. DATA MASTER DARI DATABASE
        const framesData = <?= json_encode($all_frames_dict, JSON_UNESCAPED_UNICODE) ?>;
        const frameKeys = Object.keys(framesData);

        let currentFrame = '<?= $active_frame_param ?>';
        if (!framesData[currentFrame]) {
            currentFrame = frameKeys[0] || 'home';
        }

        const activeColor = '<?= htmlspecialchars($bottom_bar_active_color) ?>';
        const normalColor = '<?= htmlspecialchars($bottom_bar_text_color) ?>';
        const guestName = <?= json_encode($nama_tamu) ?>;

        // Slide State
        window.simSlideCurrentIndices = {};
        window.simSlideTimers = {};
        frameKeys.forEach(k => {
            window.simSlideCurrentIndices[k] = 0;
            window.simSlideTimers[k] = null;
        });

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
        // ADAPTIVE RESPONSIVE CANVAS RESIZER
        // =======================================================
        function resizeCanvas() {
            const box = document.getElementById('brosur-canvas-box');
            if (!box) return;

            const baseW = 340;
            const baseH = 600;
            const winW = window.innerWidth;
            const winH = window.innerHeight;

            let scale;
            if (winW <= 480) {
                const scaleW = winW / baseW;
                const scaleH = winH / baseH;
                scale = Math.min(scaleW, scaleH);
                box.style.borderRadius = (scaleH >= scaleW && winW <= 380) ? '0px' : '24px';
            } else {
                const maxH = winH * 0.94;
                const maxW = winW * 0.94;
                scale = Math.min(maxW / baseW, maxH / baseH, 1.28);
                box.style.borderRadius = '28px';
            }

            box.style.transform = `scale(${scale})`;
            box.style.transformOrigin = 'center center';
        }

        window.addEventListener('resize', resizeCanvas);
        window.addEventListener('orientationchange', resizeCanvas);

        // =======================================================
        // RENDER BOTTOM BAR DINAMIS
        // =======================================================
        function renderPublicBottomBar() {
            const container = document.getElementById('sim-bottom-bar');
            if (!container) return;
            container.innerHTML = '';

            frameKeys.forEach((fKey) => {
                const data = framesData[fKey];
                if (!data) return;

                const isActive = (fKey === currentFrame);
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.id = `sim-menu-${fKey}-btn`;
                btn.onclick = () => switchFrame(fKey);
                btn.className = `flex flex-col items-center justify-center text-center transform transition active:scale-95 group cursor-pointer py-0.5 px-1 flex-1 min-w-[48px] shrink-0 ${isActive ? '' : 'opacity-70 hover:opacity-100'}`;
                btn.style.color = isActive ? activeColor : normalColor;
                btn.title = `Menu ${data.name || fKey}`;

                btn.innerHTML = `
                    <i class="fas ${data.icon || 'fa-circle'} text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                    <span class="text-[9.5px] font-bold tracking-wider leading-none truncate max-w-[58px]">${escapeHtml(data.name || fKey)}</span>
                `;

                container.appendChild(btn);
            });
        }

        // =======================================================
        // SWITCH FRAME LOGIC
        // =======================================================
        function switchFrame(frame) {
            if (!framesData[frame]) frame = frameKeys[0] || 'home';
            currentFrame = frame;

            const data = framesData[frame];
            if (!data) return;

            // 1. Update Canvas Background & Overlay
            updateCanvasBackground(data.bgUrl, data.bgOpacity ?? 0.88);

            // 2. Update Bottom Bar
            renderPublicBottomBar();

            // 3. Render All Simulation Layers
            renderSimImageLayers(data.images || []);
            renderSimSlideLayers(data.slides || []);
            renderSimVideoLayers(data.videos || []);
            renderSimLayers(data.texts || []);
            renderSimButtonLayers(data.buttons || []);

            // 4. Update URL without reload
            if (history.replaceState) {
                const url = new URL(window.location.href);
                url.searchParams.set('frame', frame);
                history.replaceState(null, null, url.toString());
            }
        }

        function updateCanvasBackground(url, opacity) {
            const screenCover = document.getElementById('preview-screen-cover');
            const overlay = document.getElementById('preview-cover-overlay');
            const ambientScreen = document.getElementById('ambient-bg-screen');
            const ambientOverlay = document.getElementById('ambient-bg-overlay');

            if (screenCover) {
                if (url) {
                    screenCover.style.backgroundImage = `url('${url}')`;
                    screenCover.style.backgroundSize = 'cover';
                    screenCover.style.backgroundPosition = 'center';
                } else {
                    screenCover.style.backgroundImage = 'none';
                }
            }
            if (overlay) {
                overlay.style.opacity = opacity;
            }
            if (ambientScreen && url) {
                ambientScreen.style.backgroundImage = `url('${url}')`;
            }
            if (ambientOverlay) {
                ambientOverlay.style.opacity = opacity;
            }
        }

        // 1. RENDER GAMBAR
        function renderSimImageLayers(imageItems) {
            const container = document.getElementById('sim-image-layers-container');
            if (!container) return;
            container.innerHTML = '';

            imageItems.forEach((img, index) => {
                const box = document.createElement('div');
                box.id = `sim-img-box-${img.id}`;
                box.className = 'absolute pointer-events-auto transition-all fade-in-layer';
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
                    <div class="w-full h-full overflow-hidden ${shapeClass} ${shadowClass} transition-transform" style="${borderStyle}">
                        <img src="${escapeHtml(img.url)}" class="w-full h-full object-cover select-none pointer-events-none" loading="lazy" alt="Gambar Sisipan">
                    </div>
                `;
                container.appendChild(box);
            });
        }

        // 2. RENDER SLIDE
        function setSimActiveSlide(frame, index) {
            window.simSlideCurrentIndices[frame] = index;
            if (framesData[frame]) {
                renderSimSlideLayers(framesData[frame].slides || []);
            }
        }

        function nextSimSlide(frame) {
            const slides = (framesData[frame] && framesData[frame].slides) ? framesData[frame].slides : [];
            if (slides.length <= 1) return;
            let current = window.simSlideCurrentIndices[frame] || 0;
            current = (current + 1) % slides.length;
            setSimActiveSlide(frame, current);
        }

        function prevSimSlide(frame) {
            const slides = (framesData[frame] && framesData[frame].slides) ? framesData[frame].slides : [];
            if (slides.length <= 1) return;
            let current = window.simSlideCurrentIndices[frame] || 0;
            current = (current - 1 + slides.length) % slides.length;
            setSimActiveSlide(frame, current);
        }

        function renderSimSlideLayers(slideItems) {
            const container = document.getElementById('sim-slide-layers-container');
            if (!container) return;

            if (window.simSlideTimers[currentFrame]) {
                clearInterval(window.simSlideTimers[currentFrame]);
                window.simSlideTimers[currentFrame] = null;
            }

            container.innerHTML = '';
            if (!slideItems || slideItems.length === 0) return;

            const activeIndex = (window.simSlideCurrentIndices[currentFrame] || 0) % slideItems.length;
            const primarySlide = slideItems[0] || {};
            const currentSlide = slideItems[activeIndex] || primarySlide;

            const box = document.createElement('div');
            box.id = `sim-box-${primarySlide.id || 'slide_showcase'}`;
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
            slideItems.forEach((sld, sIdx) => {
                const isActive = (sIdx === activeIndex);
                slidesHtml += `
                    <div class="absolute inset-0 w-full h-full transition-opacity duration-700 ease-in-out ${isActive ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'}" style="background-image: url('${escapeHtml(sld.url || '')}'); background-size: cover; background-position: center;">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
                        
                        ${sld.badge ? `
                            <div class="absolute top-2.5 left-2.5 z-20">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-amber-400 text-teal-950 shadow-xs backdrop-blur-xs">
                                    ${escapeHtml(sld.badge)}
                                </span>
                            </div>
                        ` : ''}

                        <div class="absolute bottom-2.5 left-2.5 right-2.5 z-20 space-y-1">
                            ${sld.title ? `<h4 class="text-white font-black text-xs sm:text-[13px] leading-tight drop-shadow-md truncate">${escapeHtml(sld.title)}</h4>` : ''}
                            ${sld.subtitle ? `<p class="text-slate-200 text-[10px] leading-snug line-clamp-1 opacity-90">${escapeHtml(sld.subtitle)}</p>` : ''}
                            
                            ${sld.btn_text ? `
                                <div class="pt-0.5">
                                    <a href="${escapeHtml(sld.btn_url || '#')}" target="_blank" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[9.5px] font-black bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-xs hover:opacity-90 active:scale-95 transition">
                                        <span>${escapeHtml(sld.btn_text)}</span>
                                        <i class="fas fa-arrow-right text-[8px]"></i>
                                    </a>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            // Dots Nav
            let dotsHtml = '';
            if (primarySlide.show_dots !== 0 && slideItems.length > 1) {
                dotsHtml = `
                    <div class="absolute bottom-1.5 right-2.5 z-30 flex items-center gap-1 bg-black/40 px-1.5 py-0.5 rounded-full backdrop-blur-xs">
                        ${slideItems.map((_, i) => `
                            <button type="button" onclick="setSimActiveSlide('${currentFrame}', ${i})" class="w-1.5 h-1.5 rounded-full transition-all ${i === activeIndex ? 'bg-amber-400 w-3' : 'bg-white/50 hover:bg-white'}"></button>
                        `).join('')}
                    </div>
                `;
            }

            // Arrows Nav
            let arrowsHtml = '';
            if (primarySlide.show_arrows && slideItems.length > 1) {
                arrowsHtml = `
                    <button type="button" onclick="prevSimSlide('${currentFrame}')" class="absolute left-1.5 top-1/2 -translate-y-1/2 z-30 w-6 h-6 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center text-[10px] transition active:scale-90 cursor-pointer">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button type="button" onclick="nextSimSlide('${currentFrame}')" class="absolute right-1.5 top-1/2 -translate-y-1/2 z-30 w-6 h-6 rounded-full bg-black/40 hover:bg-black/70 text-white flex items-center justify-center text-[10px] transition active:scale-90 cursor-pointer">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                `;
            }

            box.innerHTML = slidesHtml + dotsHtml + arrowsHtml;
            container.appendChild(box);

            if (primarySlide.autoplay && slideItems.length > 1) {
                const intervalMs = Math.max(2, (currentSlide.interval || 4)) * 1000;
                window.simSlideTimers[currentFrame] = setInterval(() => {
                    nextSimSlide(currentFrame);
                }, intervalMs);
            }
        }

        // 3. RENDER VIDEO
        function renderSimVideoLayers(videoItems) {
            const container = document.getElementById('sim-video-layers-container');
            if (!container) return;
            container.innerHTML = '';

            videoItems.forEach((vid, index) => {
                const box = document.createElement('div');
                box.id = `sim-vid-box-${vid.id}`;
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

        // 4. RENDER TULISAN
        function renderSimLayers(textItems) {
            const container = document.getElementById('sim-text-layers-container');
            if (!container) return;
            container.innerHTML = '';

            textItems.forEach((item, index) => {
                const box = document.createElement('div');
                box.id = `sim-box-${item.id}`;
                box.className = 'absolute pointer-events-auto fade-in-layer';
                box.style.top = `${item.posY || 35}%`;
                box.style.left = `${item.posX || 50}%`;
                box.style.transform = 'translate(-50%, 0)';
                box.style.width = `${item.width || 85}%`;
                box.style.zIndex = 30 + index;

                box.innerHTML = `
                    <div style="color: ${item.color || '#ffffff'}; font-family: ${getFontFamily(item.font)}; text-align: ${item.align || 'center'}; font-size: ${item.size || 24}px; word-break: break-word;">
                        ${getFormatHtml(item.content, item.format)}
                    </div>
                `;
                container.appendChild(box);
            });
        }

        // 5. RENDER TOMBOL
        function renderSimButtonLayers(buttonItems) {
            const container = document.getElementById('sim-button-layers-container');
            if (!container) return;
            container.innerHTML = '';

            buttonItems.forEach((btn, index) => {
                const box = document.createElement('div');
                box.id = `sim-box-${btn.id}`;
                box.className = 'absolute pointer-events-auto transition-all fade-in-layer';
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

            if (Math.abs(diffX) > 50 && Math.abs(diffX) > Math.abs(diffY) * 1.4) {
                const currentIndex = frameKeys.indexOf(currentFrame);
                if (diffX < 0) {
                    const nextIndex = (currentIndex + 1) % frameKeys.length;
                    switchFrame(frameKeys[nextIndex]);
                } else {
                    const prevIndex = (currentIndex - 1 + frameKeys.length) % frameKeys.length;
                    switchFrame(frameKeys[prevIndex]);
                }
            }
        }, { passive: true });

        // INITIAL LOAD & SCALE
        document.addEventListener('DOMContentLoaded', () => {
            resizeCanvas();
            renderPublicBottomBar();
            switchFrame(currentFrame);
        });
    </script>
</body>
</html>
