<?php
// admin-brosur-settings.php
// Panel Pengaturan Brosur PSB Digital: Custom Sizing (Lebar/Tinggi), Tipografi & Redaksi, Tema Warna, Drag Elemen & Multimedia
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Self-healing migration jika kolom baru belum ada di database
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
foreach ($cols_needed as $col => $sql) {
    if (!in_array($col, $curr_cols)) {
        $conn->query($sql);
    }
}

// Pastikan baris id=1 ada
$conn->query("INSERT IGNORE INTO pengaturan_brosur (id, tahun_ajaran, periode_gelombang, kuota_santri, cover_bg_url, cover_overlay_opacity, body_bg_url, body_overlay_opacity, theme_preset, music_url, biaya_pendaftaran, biaya_pangkal, biaya_tahunan, biaya_spp, diskon_gelombang, countdown_mode, countdown_target, countdown_title, show_countdown, cover_elements_pos, theme_color_mode, text_color, accent_color, btn_bg_color, btn_text_color, card_bg_style, font_family, judul_utama, subjudul, bismillah_text, tamu_header_text, tamu_sambutan_text, btn_text, show_bismillah, show_subjudul, show_logo, show_sambutan, logo_size, card_width, card_padding, btn_width, btn_height, text_title_size, text_sub_size, show_video, video_url, video_width, video_height, show_maps, maps_url, maps_width, maps_height)
VALUES (1, '2026/2027', 'Gelombang 1 — Kuota Terbatas', 20, 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80', 0.85, 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=1200&auto=format&fit=crop&q=80', 0.92, 'madinah', 'upload/backsound.mp3', 350000, 12500000, 2500000, 1650000, 2000000, 'auto', '2026-12-31 23:59:59', '⏳ Sisa Waktu Pendaftaran Berakhir:', 1, '{\"header_y\":12,\"guest_y\":45,\"btn_y\":82}', 'emerald_gold', '#ffffff', '#fbbf24', '#d97706', '#022d27', 'glass_dark', 'Plus Jakarta Sans', 'Villa Quran Indonesia', 'Sekolah Tahfidz Berasrama Nyaman Ala Villa', 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ', 'Kepada Yth. Calon Wali Santri:', 'Undangan Mahabbah Silaturahmi & Brosur Informasi Pendidikan Putra-Putri Generasi Qur\'ani.', 'Buka Brosur & Undangan', 1, 1, 1, 1, 80, 100, 20, 100, 52, 22, 12, 1, 'https://www.youtube.com/embed/dQw4w9WgXcQ', 100, 240, 1, 'https://maps.google.com/maps?q=Villa+Quran+Indonesia&t=&z=14&ie=UTF8&iwloc=&output=embed', 100, 220)");

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

    // 3. Handle Tema Warna & Kontras
    $theme_color_mode = $conn->real_escape_string($_POST['theme_color_mode'] ?? 'emerald_gold');
    $text_color       = $conn->real_escape_string($_POST['text_color'] ?? '#ffffff');
    $accent_color     = $conn->real_escape_string($_POST['accent_color'] ?? '#fbbf24');
    $btn_bg_color     = $conn->real_escape_string($_POST['btn_bg_color'] ?? '#d97706');
    $btn_text_color   = $conn->real_escape_string($_POST['btn_text_color'] ?? '#022d27');
    $card_bg_style    = $conn->real_escape_string($_POST['card_bg_style'] ?? 'glass_dark');

    // 4. Handle Redaksi Teks, Font & Visibilitas (Bisa Diedit, Ganti Font, Sembunyikan/Hapus)
    $font_family        = $conn->real_escape_string($_POST['font_family'] ?? 'Plus Jakarta Sans');
    $judul_utama        = $conn->real_escape_string($_POST['judul_utama'] ?? 'Villa Quran Indonesia');
    $subjudul           = $conn->real_escape_string($_POST['subjudul'] ?? 'Sekolah Tahfidz Berasrama Nyaman Ala Villa');
    $bismillah_text     = $conn->real_escape_string($_POST['bismillah_text'] ?? 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ');
    $tamu_header_text   = $conn->real_escape_string($_POST['tamu_header_text'] ?? 'Kepada Yth. Calon Wali Santri:');
    $tamu_sambutan_text = $conn->real_escape_string($_POST['tamu_sambutan_text'] ?? 'Undangan Mahabbah Silaturahmi & Brosur Informasi Pendidikan Putra-Putri Generasi Qur\'ani.');
    $btn_text           = $conn->real_escape_string($_POST['btn_text'] ?? 'Buka Brosur & Undangan');

    $show_bismillah     = isset($_POST['show_bismillah']) ? 1 : 0;
    $show_subjudul      = isset($_POST['show_subjudul']) ? 1 : 0;
    $show_logo          = isset($_POST['show_logo']) ? 1 : 0;
    $show_sambutan      = isset($_POST['show_sambutan']) ? 1 : 0;

    // 5. Handle Sizing: Lebar & Tinggi (Dilebarkan / Disempitkan)
    $logo_size       = max(40, min(160, (int)($_POST['logo_size'] ?? 80)));
    $card_width      = max(50, min(100, (int)($_POST['card_width'] ?? 100)));
    $card_padding    = max(8, min(50, (int)($_POST['card_padding'] ?? 20)));
    $btn_width       = max(40, min(100, (int)($_POST['btn_width'] ?? 100)));
    $btn_height      = max(34, min(80, (int)($_POST['btn_height'] ?? 52)));
    $text_title_size = max(14, min(36, (int)($_POST['text_title_size'] ?? 22)));
    $text_sub_size   = max(9, min(22, (int)($_POST['text_sub_size'] ?? 12)));

    // 6. Handle Video & Maps Sizing
    $show_video   = isset($_POST['show_video']) ? 1 : 0;
    $video_url    = $conn->real_escape_string(trim($_POST['video_url'] ?? ''));
    $video_width  = max(40, min(100, (int)($_POST['video_width'] ?? 100)));
    $video_height = max(140, min(500, (int)($_POST['video_height'] ?? 240)));

    $show_maps    = isset($_POST['show_maps']) ? 1 : 0;
    $maps_url     = $conn->real_escape_string(trim($_POST['maps_url'] ?? ''));
    $maps_width   = max(40, min(100, (int)($_POST['maps_width'] ?? 100)));
    $maps_height  = max(140, min(500, (int)($_POST['maps_height'] ?? 220)));

    $bottom_bar_bg_color   = $conn->real_escape_string($_POST['bottom_bar_bg_color'] ?? '#022d27');
    $bottom_bar_text_color = $conn->real_escape_string($_POST['bottom_bar_text_color'] ?? '#ffffff');

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
                    card_bg_style = '$card_bg_style',
                    font_family = '$font_family',
                    judul_utama = '$judul_utama',
                    subjudul = '$subjudul',
                    bismillah_text = '$bismillah_text',
                    tamu_header_text = '$tamu_header_text',
                    tamu_sambutan_text = '$tamu_sambutan_text',
                    btn_text = '$btn_text',
                    show_bismillah = $show_bismillah,
                    show_subjudul = $show_subjudul,
                    show_logo = $show_logo,
                    show_sambutan = $show_sambutan,
                    logo_size = $logo_size,
                    card_width = $card_width,
                    card_padding = $card_padding,
                    btn_width = $btn_width,
                    btn_height = $btn_height,
                    text_title_size = $text_title_size,
                    text_sub_size = $text_sub_size,
                    show_video = $show_video,
                    video_url = '$video_url',
                    video_width = $video_width,
                    video_height = $video_height,
                    show_maps = $show_maps,
                    maps_url = '$maps_url',
                    maps_width = $maps_width,
                    maps_height = $maps_height,
                    bottom_bar_bg_color = '$bottom_bar_bg_color',
                    bottom_bar_text_color = '$bottom_bar_text_color'
                   WHERE id = 1";

    if ($conn->query($sql_update)) {
        $pesan_sukses = "Alhamdulillah! Pengaturan Brosur PSB, Dimensi Ukuran, Warna Bottom Bar, Redaksi Teks, Font & Warna berhasil diperbarui.";
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

// Ambil Data Pengaturan Web Sinkron (Fasilitas, Pengajar, Galeri/Kegiatan, Biaya, Testimoni)
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
$biaya_pangkal_val     = ($web_biaya_subtotal['pangkal'] > 0) ? $web_biaya_subtotal['pangkal'] : ($cfg['biaya_pangkal'] ?? 13500000);
$biaya_tahunan_val     = ($web_biaya_subtotal['tahunan'] > 0) ? $web_biaya_subtotal['tahunan'] : ($cfg['biaya_tahunan'] ?? 3500000);
$biaya_spp_val         = ($web_biaya_subtotal['spp'] > 0) ? $web_biaya_subtotal['spp'] : ($cfg['biaya_spp'] ?? 1500000);

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
    <title>Pengaturan Brosur PSB Digital & Customizer | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts Multi-Family untuk Customizer -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Cinzel:wght@500;700;900&family=Inter:wght@400;600;700&family=Outfit:wght@400;600;800&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
    
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
            background-image: linear-gradient(to right, rgba(14, 165, 233, 0.12) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(14, 165, 233, 0.12) 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .center-guide-line {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 1px;
            background: rgba(239, 68, 68, 0.6);
            pointer-events: none;
            z-index: 40;
        }
        .active-horizontal-guide {
            position: absolute;
            left: 0;
            right: 0;
            height: 1px;
            background: rgba(14, 165, 233, 0.9);
            box-shadow: 0 0 6px rgba(14, 165, 233, 0.8);
            pointer-events: none;
            z-index: 45;
        }

        .drag-box {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            cursor: grab;
            user-select: none;
            transition: box-shadow 0.2s;
        }
        .drag-box:active, .drag-box.dragging {
            cursor: grabbing;
            box-shadow: 0 0 0 2px #0ea5e9, 0 10px 20px -5px rgba(0,0,0,0.5);
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

        /* Simulasi Phone Bottom Bar: 4 Card Menu Tampil Default */
        .sim-card-menu-item {
            flex: 0 0 calc(25% - 4.5px);
            min-width: calc(25% - 4.5px);
            max-width: calc(25% - 4.5px);
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden text-slate-800">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 flex flex-col h-screen overflow-y-auto">
        
        <!-- HEADER TOPBAR -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between sticky top-0 z-30 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-xl shadow-xs">
                    <i class="fas fa-sliders"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Pengaturan Brosur & Undangan Digital</h1>
                    <p class="text-xs text-slate-500">Atur Lebar, Tinggi, Redaksi, Font, Warna & Posisi Visual Interaktif</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="brosur.php" target="_blank" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow-md transition flex items-center gap-1.5">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Buka Brosur Publik</span>
                </a>
            </div>
        </header>

        <!-- NOTIFIKASI SUKSES / ERROR -->
        <div class="px-6 pt-5">
            <?php if (!empty($pesan_sukses)): ?>
            <div class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-xs">
                <i class="fas fa-circle-check text-emerald-600 text-base"></i>
                <span><?= htmlspecialchars($pesan_sukses) ?></span>
            </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
            <div class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2.5 shadow-xs">
                <i class="fas fa-circle-exclamation text-rose-600 text-base"></i>
                <span><?= htmlspecialchars($pesan_error) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- FORM & INTERACTIVE VISUAL WORKSPACE (SPLIT LAYOUT) -->
        <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- PANEL KIRI: FORM PENGATURAN LENGKAP (7 - 8 KOLOM) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">

                    <!-- ============================================================ -->
                    <!-- KARTU 1: BACKGROUND COVER & LAMAN DALAM (PINTEREST / UPLOAD) -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">1. Background Cover & Halaman Dalam</h2>
                                    <p class="text-xs text-slate-500">Gunakan link Pinterest, Unsplash, atau upload foto sendiri</p>
                                </div>
                            </div>
                        </div>

                        <!-- 1.1 BACKGROUND COVER AMPLOP -->
                        <div class="space-y-2">
                            <label class="block text-xs font-bold text-slate-700">Link URL Background Cover (Pinterest / Web):</label>
                            <div class="flex gap-2">
                                <input type="text" name="cover_bg_url" id="input-cover-bg-url" value="<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>" oninput="updateLivePreview()" placeholder="https://images.unsplash.com/... atau link Pinterest" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                                <label class="cursor-pointer px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition">
                                    <i class="fas fa-upload"></i>
                                    <span>Upload</span>
                                    <input type="file" name="cover_bg_file" accept="image/*" class="hidden" onchange="previewUploadedFile(this, 'cover')">
                                </label>
                            </div>
                        </div>

                        <!-- SLIDER KEGELAPAN OVERLAY COVER -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-bold text-slate-700">Tingkat Kegelapan Lapis Cover (Bisa 0% - Tanpa Lapis):</label>
                                <span id="cover-opacity-val" class="text-xs font-extrabold text-teal-800"><?= round((float)($cfg['cover_overlay_opacity'] ?? 0.85) * 100) ?>%</span>
                            </div>
                            <input type="range" name="cover_overlay_opacity" id="input-cover-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>" oninput="updateLiveCoverOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                        </div>

                        <!-- 1.2 BACKGROUND LAMAN DALAM -->
                        <div class="pt-3 border-t border-slate-100 space-y-2">
                            <label class="block text-xs font-bold text-slate-700">Link URL Wallpaper Halaman Dalam (Pinterest / Web):</label>
                            <div class="flex gap-2">
                                <input type="text" name="body_bg_url" id="input-body-bg-url" value="<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>" oninput="updateLiveBodyBg()" placeholder="Kosongkan jika ingin putih polos" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                                <label class="cursor-pointer px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition">
                                    <i class="fas fa-upload"></i>
                                    <span>Upload</span>
                                    <input type="file" name="body_bg_file" accept="image/*" class="hidden" onchange="previewUploadedFile(this, 'body')">
                                </label>
                            </div>

                            <div class="flex justify-between items-center mb-1 pt-2">
                                <label class="text-xs font-bold text-slate-700">Transparansi Lapis Terang Laman Dalam (0% = Asli Wallpaper):</label>
                                <span id="body-opacity-val" class="text-xs font-extrabold text-teal-800"><?= round((float)($cfg['body_overlay_opacity'] ?? 0.92) * 100) ?>%</span>
                            </div>
                            <input type="range" name="body_overlay_opacity" id="input-body-opacity" min="0.00" max="1.00" step="0.01" value="<?= $cfg['body_overlay_opacity'] ?? 0.92 ?>" oninput="updateLiveBodyOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 2: TIPOGRAFI, REDAKSI TULISAN & VISIBILITAS (EDIT/HAPUS) -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                                    <i class="fas fa-font"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">2. Tipografi & Redaksi Tulisan</h2>
                                    <p class="text-xs text-slate-500">Edit kata-kata, ubah jenis font Google Fonts, atau sembunyikan/hapus tulisan</p>
                                </div>
                            </div>
                        </div>

                        <!-- 2.1 PILIHAN JENIS FONT -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilihan Jenis Font (Typography):</label>
                            <select name="font_family" id="input-font-family" onchange="updateLiveFont(this.value)" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold text-slate-800 focus:border-[#0b8478] focus:outline-none">
                                <?php
                                $fonts = [
                                    'Plus Jakarta Sans' => 'Plus Jakarta Sans (Modern, Bersih, Elegan)',
                                    'Amiri'             => 'Amiri (Klasik Kaligrafi Islami & Pesantren)',
                                    'Playfair Display'  => 'Playfair Display (Mewah, Elegan, Royal Undangan)',
                                    'Outfit'            => 'Outfit (Modern Geometris & Segar)',
                                    'Poppins'           => 'Poppins (Ramah, Kokoh & Populer)',
                                    'Cinzel'            => 'Cinzel (Klasik Monumental & Berwibawa)',
                                    'Inter'             => 'Inter (Minimalis Digital & Tech)'
                                ];
                                $cur_font = $cfg['font_family'] ?? 'Plus Jakarta Sans';
                                foreach ($fonts as $fk => $flabel) {
                                    $sel = ($cur_font === $fk) ? 'selected' : '';
                                    echo "<option value='$fk' $sel>$flabel</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- 2.2 BISMILLAH -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fas fa-quran text-amber-500"></i> Tulisan Bismillah:
                                </label>
                                <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-600 cursor-pointer">
                                    <input type="checkbox" name="show_bismillah" value="1" <?= (!isset($cfg['show_bismillah']) || $cfg['show_bismillah'] == 1) ? 'checked' : '' ?> onchange="toggleElemVisibility('bismillah', this.checked)" class="rounded text-[#0b8478] focus:ring-0">
                                    <span>Tampilkan (Uncheck untuk Hapus)</span>
                                </label>
                            </div>
                            <input type="text" name="bismillah_text" id="input-bismillah" value="<?= htmlspecialchars($cfg['bismillah_text'] ?? 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ') ?>" oninput="updateLiveTextRedaksi()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 font-arabic text-sm text-center bg-white focus:outline-none focus:border-[#0b8478]">
                        </div>

                        <!-- 2.3 JUDUL UTAMA & SUBJUDUL -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Judul Utama Sekolah:</label>
                                <input type="text" name="judul_utama" id="input-judul-utama" value="<?= htmlspecialchars($cfg['judul_utama'] ?? 'Villa Quran Indonesia') ?>" oninput="updateLiveTextRedaksi()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs sm:text-sm font-black focus:outline-none focus:border-[#0b8478]">
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-xs font-bold text-slate-700">Subjudul / Slogan:</label>
                                    <label class="flex items-center gap-1 text-[10px] font-bold text-slate-500 cursor-pointer">
                                        <input type="checkbox" name="show_subjudul" value="1" <?= (!isset($cfg['show_subjudul']) || $cfg['show_subjudul'] == 1) ? 'checked' : '' ?> onchange="toggleElemVisibility('subjudul', this.checked)" class="rounded text-[#0b8478] focus:ring-0">
                                        <span>Aktif</span>
                                    </label>
                                </div>
                                <input type="text" name="subjudul" id="input-subjudul" value="<?= htmlspecialchars($cfg['subjudul'] ?? 'Sekolah Tahfidz Berasrama Nyaman Ala Villa') ?>" oninput="updateLiveTextRedaksi()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-[#0b8478]">
                            </div>
                        </div>

                        <!-- 2.4 KARTU TAMU & SAMBUTAN -->
                        <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                    <i class="fas fa-envelope-open text-emerald-600"></i> Redaksi Kartu Undangan Tamu:
                                </label>
                                <label class="flex items-center gap-1 text-[11px] font-bold text-slate-600 cursor-pointer">
                                    <input type="checkbox" name="show_sambutan" value="1" <?= (!isset($cfg['show_sambutan']) || $cfg['show_sambutan'] == 1) ? 'checked' : '' ?> onchange="toggleElemVisibility('sambutan', this.checked)" class="rounded text-[#0b8478] focus:ring-0">
                                    <span>Tampilkan Teks Sambutan</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-600 font-semibold mb-1">Header Tamu:</label>
                                <input type="text" name="tamu_header_text" id="input-tamu-header" value="<?= htmlspecialchars($cfg['tamu_header_text'] ?? 'Kepada Yth. Calon Wali Santri:') ?>" oninput="updateLiveTextRedaksi()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-600 font-semibold mb-1">Teks Sambutan / Catatan:</label>
                                <textarea name="tamu_sambutan_text" id="input-tamu-sambutan" rows="2" oninput="updateLiveTextRedaksi()" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:border-[#0b8478]"><?= htmlspecialchars($cfg['tamu_sambutan_text'] ?? 'Undangan Mahabbah Silaturahmi & Brosur Informasi Pendidikan Putra-Putri Generasi Qur\'ani.') ?></textarea>
                            </div>
                        </div>

                        <!-- 2.5 REDAKSI TOMBOL BUKA -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Redaksi Tulisan Tombol Buka Undangan:</label>
                            <input type="text" name="btn_text" id="input-btn-text" value="<?= htmlspecialchars($cfg['btn_text'] ?? 'Buka Brosur & Undangan') ?>" oninput="updateLiveTextRedaksi()" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-bold focus:border-[#0b8478] focus:outline-none">
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 3: PENGATURAN UKURAN (LEBAR & TINGGI TULISAN, GAMBAR,  -->
                    <!--          TOMBOL, VIDEO & MAPS - DILEBARKAN / DILEMPITKAN)    -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                                    <i class="fas fa-up-right-and-down-left-from-center"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">3. Atur Tinggi & Lebar (Dilebarkan / Disempitkan)</h2>
                                    <p class="text-xs text-slate-500">Sesuaikan ukuran logo, kartu tulisan, tombol, video, dan Google Maps secara leluasa</p>
                                </div>
                            </div>
                        </div>

                        <!-- 3.1 GAMBAR LOGO (BULAT SESUAI LINGKARAN LOGO) -->
                        <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-white p-0.5 border border-emerald-600 shadow-xs overflow-hidden aspect-square">
                                        <img src="upload/logo-villa-quran.png" class="w-full h-full object-cover rounded-full">
                                    </div>
                                    <span class="text-xs font-black text-emerald-950">Gambar Logo (Bulat Sempurna Lingkaran):</span>
                                </div>
                                <label class="flex items-center gap-1.5 text-xs font-bold text-emerald-800 cursor-pointer">
                                    <input type="checkbox" name="show_logo" value="1" <?= (!isset($cfg['show_logo']) || $cfg['show_logo'] == 1) ? 'checked' : '' ?> onchange="toggleElemVisibility('logo', this.checked)" class="rounded text-[#0b8478] focus:ring-0">
                                    <span>Tampilkan Logo</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Ukuran Logo (Lebar & Tinggi Proporsional):</label>
                                        <span id="val-logo-size" class="text-xs font-mono font-bold text-emerald-800"><?= $cfg['logo_size'] ?? 80 ?> px</span>
                                    </div>
                                    <input type="range" name="logo_size" id="input-logo-size" min="40" max="150" step="2" value="<?= $cfg['logo_size'] ?? 80 ?>" oninput="updateLiveLogoSize(this.value)" class="w-full accent-emerald-600 cursor-pointer">
                                </div>
                                <p class="text-[11px] text-emerald-900 leading-relaxed bg-white/70 p-2.5 rounded-xl border border-emerald-200">
                                    <i class="fas fa-check-circle text-emerald-600 mr-1"></i> Logo otomatis dipotong bulat rapi mengikuti lingkaran hijau logo resmi Villa Quran tanpa bingkai kotak.
                                </p>
                            </div>
                        </div>

                        <!-- 3.2 TULISAN JUDUL & KARTU TAMU (LEBAR & TINGGI) -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <span class="text-xs font-black text-slate-800 block flex items-center gap-1.5">
                                <i class="fas fa-text-width text-blue-600"></i> Dimensi Tulisan & Kartu Tamu:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Lebar Kartu Tamu -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Lebar Kartu Tamu:</label>
                                        <span id="val-card-width" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['card_width'] ?? 100 ?>%</span>
                                    </div>
                                    <input type="range" name="card_width" id="input-card-width" min="60" max="100" step="1" value="<?= $cfg['card_width'] ?? 100 ?>" oninput="updateLiveCardWidth(this.value)" class="w-full accent-blue-600 cursor-pointer">
                                    <div class="flex justify-between text-[9px] text-slate-400 mt-0.5">
                                        <span>60% (Sempit)</span>
                                        <span>100% (Lebar Penuh)</span>
                                    </div>
                                </div>

                                <!-- Tinggi / Padding Kartu Tamu -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Tinggi / Padding Kartu Tamu:</label>
                                        <span id="val-card-padding" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['card_padding'] ?? 20 ?> px</span>
                                    </div>
                                    <input type="range" name="card_padding" id="input-card-padding" min="10" max="40" step="1" value="<?= $cfg['card_padding'] ?? 20 ?>" oninput="updateLiveCardPadding(this.value)" class="w-full accent-blue-600 cursor-pointer">
                                    <div class="flex justify-between text-[9px] text-slate-400 mt-0.5">
                                        <span>10px (Tipis)</span>
                                        <span>40px (Tinggi/Tebal)</span>
                                    </div>
                                </div>

                                <!-- Ukuran Font Judul -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Ukuran Huruf Judul Utama:</label>
                                        <span id="val-text-title-size" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['text_title_size'] ?? 22 ?> px</span>
                                    </div>
                                    <input type="range" name="text_title_size" id="input-text-title-size" min="14" max="32" step="1" value="<?= $cfg['text_title_size'] ?? 22 ?>" oninput="updateLiveTitleSize(this.value)" class="w-full accent-blue-600 cursor-pointer">
                                </div>

                                <!-- Ukuran Font Subjudul -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Ukuran Huruf Subjudul:</label>
                                        <span id="val-text-sub-size" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['text_sub_size'] ?? 12 ?> px</span>
                                    </div>
                                    <input type="range" name="text_sub_size" id="input-text-sub-size" min="9" max="18" step="1" value="<?= $cfg['text_sub_size'] ?? 12 ?>" oninput="updateLiveSubSize(this.value)" class="w-full accent-blue-600 cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <!-- 3.3 TOMBOL (LEBAR & TINGGI) -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <span class="text-xs font-black text-slate-800 block flex items-center gap-1.5">
                                <i class="fas fa-hand-pointer text-rose-500"></i> Dimensi Tombol Buka Undangan:
                            </span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Lebar Tombol -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Lebar Tombol:</label>
                                        <span id="val-btn-width" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['btn_width'] ?? 100 ?>%</span>
                                    </div>
                                    <input type="range" name="btn_width" id="input-btn-width" min="50" max="100" step="1" value="<?= $cfg['btn_width'] ?? 100 ?>" oninput="updateLiveBtnWidth(this.value)" class="w-full accent-rose-500 cursor-pointer">
                                    <div class="flex justify-between text-[9px] text-slate-400 mt-0.5">
                                        <span>50% (Sempit Tengah)</span>
                                        <span>100% (Lebar Penuh)</span>
                                    </div>
                                </div>

                                <!-- Tinggi Tombol -->
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Tinggi Tombol:</label>
                                        <span id="val-btn-height" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['btn_height'] ?? 52 ?> px</span>
                                    </div>
                                    <input type="range" name="btn_height" id="input-btn-height" min="36" max="70" step="1" value="<?= $cfg['btn_height'] ?? 52 ?>" oninput="updateLiveBtnHeight(this.value)" class="w-full accent-rose-500 cursor-pointer">
                                    <div class="flex justify-between text-[9px] text-slate-400 mt-0.5">
                                        <span>36px (Ramping)</span>
                                        <span>70px (Tinggi/Tebal)</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3.4 VIDEO PROFIL PESANTREN (LEBAR & TINGGI) -->
                        <div class="p-4 bg-rose-50/50 rounded-2xl border border-rose-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-rose-950 flex items-center gap-1.5">
                                    <i class="fab fa-youtube text-red-600 text-sm"></i> Video Profil / Kegiatan Santri:
                                </span>
                                <label class="flex items-center gap-1.5 text-xs font-bold text-rose-900 cursor-pointer">
                                    <input type="checkbox" name="show_video" value="1" <?= (!isset($cfg['show_video']) || $cfg['show_video'] == 1) ? 'checked' : '' ?> onchange="toggleElemVisibility('video', this.checked)" class="rounded text-rose-600 focus:ring-0">
                                    <span>Tampilkan Video di Brosur</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Link Embed Video (YouTube / MP4):</label>
                                <input type="text" name="video_url" id="input-video-url" value="<?= htmlspecialchars($cfg['video_url'] ?? 'https://www.youtube.com/embed/dQw4w9WgXcQ') ?>" oninput="updateLiveVideoUrl(this.value)" placeholder="https://www.youtube.com/embed/..." class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:border-rose-500">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Lebar Video:</label>
                                        <span id="val-video-width" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['video_width'] ?? 100 ?>%</span>
                                    </div>
                                    <input type="range" name="video_width" id="input-video-width" min="50" max="100" step="1" value="<?= $cfg['video_width'] ?? 100 ?>" oninput="updateLiveVideoWidth(this.value)" class="w-full accent-red-600 cursor-pointer">
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Tinggi Video:</label>
                                        <span id="val-video-height" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['video_height'] ?? 240 ?> px</span>
                                    </div>
                                    <input type="range" name="video_height" id="input-video-height" min="150" max="450" step="5" value="<?= $cfg['video_height'] ?? 240 ?>" oninput="updateLiveVideoHeight(this.value)" class="w-full accent-red-600 cursor-pointer">
                                </div>
                            </div>
                        </div>

                        <!-- 3.5 GOOGLE MAPS LOKASI (LEBAR & TINGGI) -->
                        <div class="p-4 bg-teal-50/50 rounded-2xl border border-teal-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-teal-950 flex items-center gap-1.5">
                                    <i class="fas fa-map-location-dot text-[#0b8478] text-sm"></i> Google Maps Frame Lokasi:
                                </span>
                                <label class="flex items-center gap-1.5 text-xs font-bold text-teal-900 cursor-pointer">
                                    <input type="checkbox" name="show_maps" value="1" <?= (!isset($cfg['show_maps']) || $cfg['show_maps'] == 1) ? 'checked' : '' ?> onchange="toggleElemVisibility('maps', this.checked)" class="rounded text-[#0b8478] focus:ring-0">
                                    <span>Tampilkan Maps di Brosur</span>
                                </label>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">URL Iframe / Lokasi Maps:</label>
                                <input type="text" name="maps_url" id="input-maps-url" value="<?= htmlspecialchars(!empty($cfg['maps_url']) ? $cfg['maps_url'] : 'https://maps.google.com/maps?q=Villa+Quran+Indonesia&t=&z=14&ie=UTF8&iwloc=&output=embed') ?>" oninput="updateLiveMapsUrl(this.value)" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white focus:outline-none focus:border-[#0b8478]">
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Lebar Maps:</label>
                                        <span id="val-maps-width" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['maps_width'] ?? 100 ?>%</span>
                                    </div>
                                    <input type="range" name="maps_width" id="input-maps-width" min="50" max="100" step="1" value="<?= $cfg['maps_width'] ?? 100 ?>" oninput="updateLiveMapsWidth(this.value)" class="w-full accent-teal-600 cursor-pointer">
                                </div>
                                <div>
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="text-[11px] font-bold text-slate-700">Tinggi Maps:</label>
                                        <span id="val-maps-height" class="text-xs font-mono font-bold text-slate-800"><?= $cfg['maps_height'] ?? 220 ?> px</span>
                                    </div>
                                    <input type="range" name="maps_height" id="input-maps-height" min="140" max="450" step="5" value="<?= $cfg['maps_height'] ?? 220 ?>" oninput="updateLiveMapsHeight(this.value)" class="w-full accent-teal-600 cursor-pointer">
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 4: SINKRONISASI WARNA TULISAN & PENYESUAIAN OTOMATIS   -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-lg">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">4. Warna Tulisan, Tombol & Penyesuaian Otomatis</h2>
                                    <p class="text-xs text-slate-500">Sesuaikan warna teks secara manual atau gunakan tombol pintar otomatis</p>
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL SINKRONISASI CEPAT (SMART AUTO SYNC) -->
                        <div class="p-3.5 bg-gradient-to-r from-amber-50 to-emerald-50 rounded-2xl border border-amber-200/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <div>
                                <span class="text-xs font-black text-slate-900 block flex items-center gap-1.5">
                                    <i class="fas fa-wand-magic-sparkles text-amber-600"></i> Penyesuaian Otomatis Cerdas (Smart Auto):
                                </span>
                                <span class="text-[11px] text-slate-600">Otomatis tentukan kontras ideal agar tulisan terbaca sangat jelas di atas background.</span>
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

                        <!-- PALET WARNA KUSTOM MANUAL -->
                        <div class="pt-3 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 mb-2">Penyesuaian Warna Mandiri (Color Picker):</label>
                            <input type="hidden" name="theme_color_mode" id="input-theme-color-mode" value="<?= htmlspecialchars($cfg['theme_color_mode'] ?? 'emerald_gold') ?>">
                            
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

                            <!-- 4.5 KUSTOMISASI WARNA BOTTOM BAR & MENU -->
                            <div class="mt-4 pt-4 border-t border-slate-200">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-black text-slate-800 flex items-center gap-1.5">
                                        <i class="fas fa-bars text-[#0b8478]"></i> Kustomisasi Warna Bottom Bar (Menu HP):
                                    </label>
                                    <button type="button" onclick="autoContrastBottomBar()" class="text-[10px] text-teal-800 font-bold bg-teal-50 hover:bg-teal-100 px-2.5 py-1 rounded-lg border border-teal-200 transition flex items-center gap-1">
                                        <i class="fas fa-wand-magic-sparkles"></i> Kontras Pintar
                                    </button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Background Bottom Bar:</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" name="bottom_bar_bg_color" id="input-bottom-bar-bg-color" value="<?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?>" oninput="updateLiveBottomBarColors()" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200">
                                            <span id="label-bottom-bar-bg-color" class="text-xs font-mono font-bold"><?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?></span>
                                        </div>
                                        <!-- Quick Presets -->
                                        <div class="flex items-center gap-1.5 mt-2">
                                            <button type="button" onclick="setBottomBarColorPreset('#022d27', '#ffffff')" class="w-6 h-6 rounded-lg bg-[#022d27] border border-white/60 shadow-xs hover:scale-110 transition" title="Hijau Gelap"></button>
                                            <button type="button" onclick="setBottomBarColorPreset('#064e45', '#ffffff')" class="w-6 h-6 rounded-lg bg-[#064e45] border border-white/60 shadow-xs hover:scale-110 transition" title="Emerald"></button>
                                            <button type="button" onclick="setBottomBarColorPreset('#0f172a', '#ffffff')" class="w-6 h-6 rounded-lg bg-[#0f172a] border border-white/60 shadow-xs hover:scale-110 transition" title="Slate Gelap"></button>
                                            <button type="button" onclick="setBottomBarColorPreset('#1e1b4b', '#ffffff')" class="w-6 h-6 rounded-lg bg-[#1e1b4b] border border-white/60 shadow-xs hover:scale-110 transition" title="Midnight Navy"></button>
                                            <button type="button" onclick="setBottomBarColorPreset('#ffffff', '#0f172a')" class="w-6 h-6 rounded-lg bg-white border border-slate-400 shadow-xs hover:scale-110 transition" title="Putih Bersih"></button>
                                            <button type="button" onclick="setBottomBarColorPreset('#78350f', '#ffffff')" class="w-6 h-6 rounded-lg bg-[#78350f] border border-white/60 shadow-xs hover:scale-110 transition" title="Emas Amber"></button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] font-bold text-slate-600 mb-1">Warna Icon & Tulisan Menu:</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" name="bottom_bar_text_color" id="input-bottom-bar-text-color" value="<?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>" oninput="updateLiveBottomBarColors()" class="w-8 h-8 rounded-lg cursor-pointer border border-slate-200">
                                            <span id="label-bottom-bar-text-color" class="text-xs font-mono font-bold"><?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 mt-2">
                                            <button type="button" onclick="setBottomBarTextColor('#ffffff')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-900 text-white border border-slate-700 hover:scale-105 transition">Putih</button>
                                            <button type="button" onclick="setBottomBarTextColor('#fbbf24')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-400 text-slate-950 hover:scale-105 transition">Emas</button>
                                            <button type="button" onclick="setBottomBarTextColor('#34d399')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-400 text-slate-950 hover:scale-105 transition">Emerald</button>
                                            <button type="button" onclick="setBottomBarTextColor('#0f172a')" class="px-2 py-0.5 rounded text-[10px] font-bold bg-white text-slate-900 border border-slate-300 hover:scale-105 transition">Gelap</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 5: PENGATURAN POSISI GESER ELEMEN (DRAG & SLIDER)      -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-cyan-50 text-cyan-700 flex items-center justify-center text-lg">
                                    <i class="fas fa-arrows-up-down-left-right"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">5. Atur Posisi Tulisan, Gambar & Tombol (Drag & Slider)</h2>
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
                                <i class="fas fa-hand-back-fist text-amber-500 mr-1"></i> Tips: Anda juga bisa <strong>menekan & menggeser langsung</strong> elemen di dalam layar HP simulasi.
                            </span>
                            <button type="button" onclick="resetDefaultPositions()" class="text-amber-700 font-bold hover:underline flex items-center gap-1 text-[11px]">
                                <i class="fas fa-rotate-left"></i> Reset Posisi Seimbang
                            </button>
                        </div>
                    </div>

                    <!-- ============================================================ -->
                    <!-- KARTU 6: COUNTDOWN TIMER & PERIODE BIAYA SPMB                -->
                    <!-- ============================================================ -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg">
                                    <i class="fas fa-stopwatch"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">6. Countdown Timer & Periode Biaya SPMB</h2>
                                    <p class="text-xs text-slate-500">Hitung mundur sisa waktu pendaftaran & rincian biaya pendidikan</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="show_countdown" value="1" <?= (!isset($cfg['show_countdown']) || $cfg['show_countdown'] == 1) ? 'checked' : '' ?> class="sr-only peer" onchange="toggleCountdownVisibility(this.checked)">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0b8478]"></div>
                                <span class="ml-2 text-xs font-bold text-slate-700">Aktif</span>
                            </label>
                        </div>

                        <!-- PILIHAN MODE COUNTDOWN -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition <?= (($cfg['countdown_mode'] ?? 'auto') === 'auto') ? 'border-[#0b8478] bg-teal-50/50' : 'border-slate-200 bg-white' ?>" id="mode-label-auto">
                                <input type="radio" name="countdown_mode" value="auto" <?= (($cfg['countdown_mode'] ?? 'auto') === 'auto') ? 'checked' : '' ?> onchange="changeCountdownMode('auto')" class="mt-1 text-[#0b8478] focus:ring-[#0b8478]">
                                <div>
                                    <span class="font-bold text-xs text-slate-900 block flex items-center gap-1.5">
                                        <i class="fas fa-magic text-[#0b8478]"></i> Otomatis Siklus Gelombang
                                    </span>
                                    <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                        Sama persis web sekolah: Gelombang 1 (s/d 31 Des), Gelombang 2 (s/d 31 Mar), Gelombang 3 (s/d 30 Jun).
                                    </span>
                                </div>
                            </label>

                            <label class="border-2 rounded-2xl p-3.5 cursor-pointer flex items-start gap-3 transition <?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? 'border-[#0b8478] bg-teal-50/50' : 'border-slate-200 bg-white' ?>" id="mode-label-custom">
                                <input type="radio" name="countdown_mode" value="custom" <?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? 'checked' : '' ?> onchange="changeCountdownMode('custom')" class="mt-1 text-[#0b8478] focus:ring-[#0b8478]">
                                <div>
                                    <span class="font-bold text-xs text-slate-900 block flex items-center gap-1.5">
                                        <i class="fas fa-calendar-day text-amber-600"></i> Kustom Batas Waktu
                                    </span>
                                    <span class="text-[11px] text-slate-500 leading-relaxed block mt-0.5">
                                        Tentukan tanggal & jam batas penutupan khusus secara manual.
                                    </span>
                                </div>
                            </label>
                        </div>

                        <!-- INPUT TANGGAL KUSTOM -->
                        <div id="custom-target-group" class="<?= (($cfg['countdown_mode'] ?? 'auto') === 'custom') ? '' : 'hidden' ?> bg-amber-50/70 p-4 rounded-2xl border border-amber-200">
                            <label class="block text-xs font-bold text-amber-900 mb-1">Pilih Tanggal & Waktu Batas Akhir:</label>
                            <input type="datetime-local" name="countdown_target" id="input-countdown-target" value="<?= date('Y-m-d\TH:i', strtotime($cfg['countdown_target'] ?? '2026-12-31 23:59:59')) ?>" oninput="updateAdminCountdownWidget()" class="w-full sm:w-72 px-4 py-2 rounded-xl border border-amber-300 bg-white text-xs font-mono font-bold text-slate-800">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-100">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran:</label>
                                <input type="text" name="tahun_ajaran" id="input-tahun" value="<?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>" oninput="updateLiveTextRedaksi()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Gelombang:</label>
                                <input type="text" name="periode_gelombang" id="input-gelombang" value="<?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?>" oninput="updateLiveTextRedaksi()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kuota Santri:</label>
                                <input type="number" name="kuota_santri" value="<?= $cfg['kuota_santri'] ?? 20 ?>" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478] focus:outline-none">
                            </div>
                        </div>

                        <div class="mt-4 p-3.5 bg-emerald-50/80 rounded-2xl border border-emerald-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div>
                                <span class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                                    <i class="fas fa-link text-emerald-600"></i>
                                    Sinkron Otomatis dari Pengaturan Info Biaya
                                </span>
                                <p class="text-[11px] text-emerald-700 mt-0.5">Jumlah biaya di bawah ini otomatis dihitung dari akumulasi komponen di menu <strong>Info Biaya</strong>.</p>
                            </div>
                            <a href="admin-biaya.php" class="text-xs bg-white text-emerald-800 hover:text-emerald-950 font-bold px-3 py-1.5 rounded-xl border border-emerald-300 shadow-xs flex items-center gap-1 whitespace-nowrap transition">
                                <i class="fas fa-edit"></i> Kelola Rincian Biaya &rarr;
                            </a>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">1. Biaya Pendaftaran & Observasi (Rp):</label>
                                <input type="number" name="biaya_pendaftaran" id="input-biaya-pendaftaran" value="<?= $biaya_pendaftaran_val ?>" oninput="updateLiveInvestasi()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">2. Uang Pangkal Masuk (Rp):</label>
                                <input type="number" name="biaya_pangkal" id="input-biaya-pangkal" value="<?= $biaya_pangkal_val ?>" oninput="updateLiveInvestasi()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">3. Biaya Tahunan (Rp):</label>
                                <input type="number" name="biaya_tahunan" id="input-biaya-tahunan" value="<?= $biaya_tahunan_val ?>" oninput="updateLiveInvestasi()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">4. SPP All-in Bulanan (Rp):</label>
                                <input type="number" name="biaya_spp" id="input-biaya-spp" value="<?= $biaya_spp_val ?>" oninput="updateLiveInvestasi()" required class="w-full px-4 py-2 rounded-xl border border-slate-200 text-xs focus:border-[#0b8478]">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-amber-800 mb-1"><i class="fas fa-gift text-amber-500 mr-1"></i> Diskon Khusus Gelombang Uang Pangkal (Rp):</label>
                                <input type="number" name="diskon_gelombang" id="input-diskon-gelombang" value="<?= $cfg['diskon_gelombang'] ?? 2000000 ?>" oninput="updateLiveInvestasi()" required class="w-full px-4 py-2 rounded-xl border border-amber-300 bg-amber-50/40 text-xs focus:border-[#0b8478]">
                            </div>
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN UTAMA -->
                    <div class="sticky bottom-4 z-20">
                        <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-[#0b8478] hover:bg-[#075f56] text-white font-black text-sm sm:text-base shadow-xl flex items-center justify-center gap-2 transform active:scale-95 transition">
                            <i class="fas fa-save text-lg"></i>
                            <span>Simpan Seluruh Pengaturan Brosur</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- SIMULASI SMARTPHONE LIVE PREVIEW & VISUAL CANVAS (4 - 5 KOLOM) -->
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
                <div class="phone-mockup bg-slate-900 text-white flex flex-col relative" id="phone-container" style="font-family: '<?= htmlspecialchars($cfg['font_family'] ?? 'Plus Jakarta Sans') ?>', sans-serif;">
                    <div class="phone-speaker"></div>

                    <!-- GARIS BANTUAN (GUIDELINES & GRID) -->
                    <div id="grid-overlay" class="absolute inset-0 canvas-grid-bg pointer-events-none z-30 transition-opacity duration-300">
                        <div class="center-guide-line"></div>
                        <div id="guide-horizontal" class="active-horizontal-guide hidden"></div>
                        <div id="guide-badge-y" class="absolute top-2 left-2 px-2 py-0.5 rounded bg-cyan-900/90 text-cyan-200 text-[9px] font-mono font-bold hidden border border-cyan-400/50 z-50">
                            Y: 0%
                        </div>
                    </div>
                    
                    <!-- 1. SCREEN VIEW: SIMULASI COVER AMPLOP INTERAKTIF -->
                    <div id="preview-screen-cover" class="relative w-full h-full p-4 text-center bg-cover bg-center overflow-hidden transition-all duration-500 select-none" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS COVER -->
                        <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>;"></div>

                        <!-- BLOK 1: HEADER LOGO, BISMILLAH, JUDUL (DRAGGABLE) -->
                        <div id="preview-elem-header" class="drag-box z-20 text-center w-full px-2" style="top: <?= $header_y ?>%;" data-elem="header">
                            
                            <!-- Bismillah -->
                            <p class="font-arabic text-sm transition-colors <?= (isset($cfg['show_bismillah']) && $cfg['show_bismillah'] == 0) ? 'hidden' : '' ?>" id="view-bismillah" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['bismillah_text'] ?? 'بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ') ?>
                            </p>
                            
                            <!-- Gambar Logo (Bulat Sempurna sesuai Lingkaran Logo) -->
                            <div id="view-logo-container" class="my-1 <?= (isset($cfg['show_logo']) && $cfg['show_logo'] == 0) ? 'hidden' : '' ?>">
                                <div id="preview-logo-wrapper" class="rounded-full p-0.5 bg-white border-2 border-emerald-600 shadow-md overflow-hidden aspect-square mx-auto flex items-center justify-center transition-all" style="width: <?= round(($cfg['logo_size'] ?? 80) * 0.55) ?>px; height: round(($cfg['logo_size'] ?? 80) * 0.55)px;">
                                    <img src="upload/logo-villa-quran.png" class="w-full h-full object-cover rounded-full pointer-events-none">
                                </div>
                            </div>

                            <!-- Judul Utama -->
                            <h3 class="font-black mt-1 transition-all leading-tight" id="view-title" style="color: <?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>; font-size: <?= round(($cfg['text_title_size'] ?? 22) * 0.7) ?>px;">
                                <?= htmlspecialchars($cfg['judul_utama'] ?? 'Villa Quran Indonesia') ?>
                            </h3>

                            <!-- Subjudul -->
                            <p id="view-subjudul" class="text-[9px] font-medium transition-all <?= (isset($cfg['show_subjudul']) && $cfg['show_subjudul'] == 0) ? 'hidden' : '' ?>" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>; font-size: <?= round(($cfg['text_sub_size'] ?? 12) * 0.8) ?>px;">
                                <?= htmlspecialchars($cfg['subjudul'] ?? 'Sekolah Tahfidz Berasrama Nyaman Ala Villa') ?>
                            </p>

                            <p id="preview-sub" class="text-[8px] font-bold uppercase tracking-wider mt-0.5 transition-colors" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?>
                            </p>
                        </div>

                        <!-- BLOK 2: KARTU TAMU CALON WALI (DRAGGABLE & RESIZABLE) -->
                        <div id="preview-elem-guest" class="drag-box z-20 border rounded-2xl backdrop-blur-md shadow-lg transition-all <?= (($cfg['card_bg_style'] ?? 'glass_dark') === 'glass_light') ? 'bg-white/85 border-emerald-600/40 text-slate-800' : 'bg-black/45 border-amber-400/40 text-white' ?>" style="top: <?= $guest_y ?>%; width: <?= $cfg['card_width'] ?? 100 ?>%; padding: <?= round(($cfg['card_padding'] ?? 20) * 0.6) ?>px;" data-elem="guest">
                            <span class="text-[8px] uppercase tracking-wider font-bold block" id="view-guest-sub" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                <?= htmlspecialchars($cfg['tamu_header_text'] ?? 'Kepada Yth. Calon Wali:') ?>
                            </span>
                            <div class="text-xs font-black mt-0.5 transition-colors" id="view-guest-name">Bpk. Hendy Pratama</div>
                            
                            <!-- Teks Sambutan Tamu -->
                            <p class="text-[8px] mt-0.5 leading-tight opacity-90 transition-all <?= (isset($cfg['show_sambutan']) && $cfg['show_sambutan'] == 0) ? 'hidden' : '' ?>" id="view-tamu-sambutan">
                                <?= htmlspecialchars($cfg['tamu_sambutan_text'] ?? 'Undangan Silaturahmi Mahabbah & Brosur Pendidikan Generasi Qur\'ani.') ?>
                            </p>

                            <div class="mt-1.5 pt-1.5 border-t border-white/10 text-[8px] font-semibold" id="preview-tahun-txt" style="color: <?= htmlspecialchars($cfg['accent_color'] ?? '#fbbf24') ?>;">
                                Tahun Ajaran <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>
                            </div>
                        </div>

                        <!-- BLOK 3: TOMBOL BUKA UNDANGAN (DRAGGABLE & RESIZABLE) -->
                        <div id="preview-elem-btn" class="drag-box z-20 flex flex-col items-center" style="top: <?= $btn_y ?>%; width: 100%;" data-elem="btn">
                            <button type="button" onclick="setPhoneTab('body')" id="view-btn-preview" class="rounded-xl font-black text-xs flex items-center justify-center gap-1.5 shadow-lg active:scale-95 transition-all" style="width: <?= $cfg['btn_width'] ?? 100 ?>%; height: <?= round(($cfg['btn_height'] ?? 52) * 0.8) ?>px; background: <?= htmlspecialchars($cfg['btn_bg_color'] ?? '#d97706') ?>; color: <?= htmlspecialchars($cfg['btn_text_color'] ?? '#022d27') ?>;">
                                <i class="fas fa-envelope-open-text text-[10px]"></i>
                                <span id="view-btn-label"><?= htmlspecialchars($cfg['btn_text'] ?? 'Buka Brosur & Undangan') ?></span>
                            </button>
                            <p class="text-[8px] mt-1 text-center opacity-80" id="view-audio-note" style="color: <?= htmlspecialchars($cfg['text_color'] ?? '#ffffff') ?>;"><i class="fas fa-music mr-1"></i> Alunan Backsound Syahdu</p>
                        </div>
                    </div>

                    <!-- 2. SCREEN VIEW: SIMULASI HALAMAN DALAM DENGAN 9 MENU SATU LAYAR FULL & BOTTOM BAR INTERAKTIF -->
                    <div id="preview-screen-body" class="hidden relative w-full h-full flex flex-col bg-cover bg-center transition-all duration-500 select-none text-slate-800 overflow-hidden" style="background-image: url('<?= htmlspecialchars($cfg['body_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS HALAMAN DALAM -->
                        <div id="preview-body-overlay" class="absolute inset-0 bg-[#f6f7f5] transition-all duration-300" style="opacity: <?= $cfg['body_overlay_opacity'] ?? 0.92 ?>;"></div>

                        <!-- KONTEN AREA HALAMAN DALAM (SNAP SCROLLING 9 SECTION: 1 SECTION = 1 LAYAR HP) -->
                        <div id="sim-scroll-body" class="relative z-10 flex-1 overflow-y-auto p-3 space-y-3 text-left scroll-smooth snap-y snap-mandatory no-scrollbar">
                            
                            <!-- 1. SECTION: HOME (1 HALAMAN PENUH HP) -->
                            <div id="sim-sec-home" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-emerald-100 shadow-sm text-center space-y-2.5">
                                <div class="w-12 h-12 rounded-full mx-auto p-0.5 bg-white border-2 border-emerald-600 shadow-sm overflow-hidden aspect-square flex items-center justify-center">
                                    <img src="upload/logo-villa-quran.png" class="w-full h-full object-cover rounded-full">
                                </div>
                                <div>
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[8px] font-black bg-emerald-100 text-emerald-900 uppercase tracking-wider mb-1">
                                        Hal 1 &bull; Home Profil
                                    </span>
                                    <h4 class="font-black text-sm text-emerald-950 leading-tight" id="sim-view-title"><?= htmlspecialchars($cfg['judul_utama'] ?? 'Villa Quran Indonesia') ?></h4>
                                    <p class="text-[9px] text-emerald-700 font-medium mt-0.5" id="sim-view-subjudul"><?= htmlspecialchars($cfg['subjudul'] ?? 'Pesantren Tahfidz Berasrama Nyaman Ala Villa') ?></p>
                                </div>

                                <div class="p-2 rounded-xl bg-emerald-50/70 border border-emerald-200/60 text-[8px] text-emerald-900 leading-relaxed font-semibold">
                                    <i class="fas fa-certificate text-amber-500 mr-1"></i> TA <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?> &bull; <?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1') ?>
                                </div>

                                <p class="text-[7.5px] text-slate-400 italic">Geser ke bawah atau klik tombol menu untuk melihat halaman berikutnya</p>
                            </div>

                            <!-- 2. SECTION: MENGAPA VQBM (1 HALAMAN PENUH HP) -->
                            <div id="sim-sec-mengapa" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-amber-100 shadow-sm space-y-2">
                                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-1.5">
                                    <span class="w-6 h-6 rounded-lg bg-amber-500 text-white flex items-center justify-center text-[10px] shadow-xs">
                                        <i class="fas fa-heart"></i>
                                    </span>
                                    <div>
                                        <span class="text-[7.5px] uppercase font-bold text-amber-600">Hal 2</span>
                                        <h5 class="font-black text-[11px] text-slate-900 leading-tight">Mengapa Villa Quran?</h5>
                                    </div>
                                </div>
                                <div class="space-y-1.5 text-[8px]">
                                    <div class="p-2 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-950">
                                        <strong>1. Tahfidz Mutqin 30 Juz Bersanad</strong>
                                        <p class="text-slate-600 text-[7.5px] mt-0.5">Talaqqi harian asatidz mukim dan sanad muttashil.</p>
                                    </div>
                                    <div class="p-2 rounded-xl bg-teal-50 border border-teal-100 text-teal-950">
                                        <strong>2. Ijazah Formal Resmi SMP/SMA</strong>
                                        <p class="text-slate-600 text-[7.5px] mt-0.5">Legalitas ijazah negara terakreditasi untuk PTN & kedinasan.</p>
                                    </div>
                                    <div class="p-2 rounded-xl bg-amber-50 border border-amber-100 text-amber-950">
                                        <strong>3. Solopreneur & AI Terapan</strong>
                                        <p class="text-slate-600 text-[7.5px] mt-0.5">Literasi digital, prompt AI, dan kemandirian wirausaha.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. SECTION: TARGET KOMPETENSI (1 HALAMAN PENUH HP) -->
                            <div id="sim-sec-kompetensi" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-cyan-100 shadow-sm space-y-2">
                                <div class="flex items-center gap-1.5 border-b border-slate-100 pb-1.5">
                                    <span class="w-6 h-6 rounded-lg bg-cyan-600 text-white flex items-center justify-center text-[10px] shadow-xs">
                                        <i class="fas fa-bullseye"></i>
                                    </span>
                                    <div>
                                        <span class="text-[7.5px] uppercase font-bold text-cyan-600">Hal 3</span>
                                        <h5 class="font-black text-[11px] text-slate-900 leading-tight">Target Kompetensi</h5>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-1.5 text-[7.5px]">
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-quran text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8px]">Hafal 30 Juz</strong>
                                        <span class="text-slate-600">Tahsin fashahah & sanad mutqin</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-graduation-cap text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8px]">Ijazah SMP/SMA</strong>
                                        <span class="text-slate-600">Kurikulum Diknas & Diniyah</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-comments text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8px]">Bahasa Aktif</strong>
                                        <span class="text-slate-600">Percakapan Arab & Inggris harian</span>
                                    </div>
                                    <div class="p-2 rounded-xl bg-cyan-50 border border-cyan-100 text-cyan-950">
                                        <i class="fas fa-laptop-code text-cyan-600 mb-1 text-sm block"></i>
                                        <strong class="block text-[8px]">Solopreneur AI</strong>
                                        <span class="text-slate-600">Skill abad 21 & digital dakwah</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. SECTION: FASILITAS (1 HALAMAN PENUH HP - SINKRON WEB) -->
                            <div id="sim-sec-fasilitas" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-teal-100 shadow-sm space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-lg bg-teal-600 text-white flex items-center justify-center text-[10px] shadow-xs">
                                            <i class="fas fa-hotel"></i>
                                        </span>
                                        <div>
                                            <span class="text-[7.5px] uppercase font-bold text-teal-600">Hal 4</span>
                                            <h5 class="font-black text-[11px] text-slate-900 leading-tight">Fasilitas Kampus</h5>
                                        </div>
                                    </div>
                                    <a href="admin-fasilitas.php" target="_blank" class="text-[7.5px] text-teal-700 underline font-bold">Edit Web &rarr;</a>
                                </div>

                                <div class="space-y-1.5 max-h-[300px] overflow-y-auto pr-0.5 no-scrollbar text-[7.5px]">
                                    <?php if (!empty($web_fasilitas)): ?>
                                        <?php foreach (array_slice($web_fasilitas, 0, 4) as $wf): ?>
                                            <div class="p-1.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center gap-2">
                                                <?php if (!empty($wf['gambar_url'])): ?>
                                                    <img src="<?= htmlspecialchars($wf['gambar_url']) ?>" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center text-xs flex-shrink-0"><i class="fas fa-building"></i></div>
                                                <?php endif; ?>
                                                <div class="overflow-hidden">
                                                    <strong class="text-slate-900 block truncate"><?= htmlspecialchars($wf['judul']) ?></strong>
                                                    <span class="text-slate-500 line-clamp-2"><?= htmlspecialchars($wf['deskripsi']) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-2 rounded-xl bg-teal-50 text-teal-900">Asrama AC, Masjid Jami', Kolam Renang, Resto Santri.</div>
                                    <?php endif; ?>
                                </div>

                                <!-- Simulasi Maps / Video Link -->
                                <div id="preview-maps-container" class="rounded-xl p-1.5 bg-emerald-50 border border-emerald-200 text-center text-[7.5px] <?= (isset($cfg['show_maps']) && $cfg['show_maps'] == 0) ? 'hidden' : '' ?>">
                                    <span class="font-bold text-emerald-900"><i class="fas fa-map-marker-alt text-emerald-600 mr-1"></i> Google Maps Lokasi Kampus</span>
                                </div>
                            </div>

                            <!-- 5. SECTION: DEWAN PENGASUH (1 HALAMAN PENUH HP - SINKRON WEB) -->
                            <div id="sim-sec-pengasuh" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-purple-100 shadow-sm space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-lg bg-purple-600 text-white flex items-center justify-center text-[10px] shadow-xs">
                                            <i class="fas fa-user-graduate"></i>
                                        </span>
                                        <div>
                                            <span class="text-[7.5px] uppercase font-bold text-purple-600">Hal 5</span>
                                            <h5 class="font-black text-[11px] text-slate-900 leading-tight">Dewan Pengasuh</h5>
                                        </div>
                                    </div>
                                    <a href="admin-pengajar.php" target="_blank" class="text-[7.5px] text-purple-700 underline font-bold">Edit Web &rarr;</a>
                                </div>

                                <div class="space-y-1.5 max-h-[300px] overflow-y-auto pr-0.5 no-scrollbar text-[7.5px]">
                                    <?php if (!empty($web_pengajar)): ?>
                                        <?php foreach (array_slice($web_pengajar, 0, 3) as $wp): ?>
                                            <div class="p-2 rounded-xl bg-purple-50/60 border border-purple-100 flex items-center gap-2">
                                                <?php if (!empty($wp['gambar_url'])): ?>
                                                    <img src="<?= htmlspecialchars($wp['gambar_url']) ?>" class="w-10 h-10 rounded-full object-cover border border-purple-300 flex-shrink-0">
                                                <?php else: ?>
                                                    <div class="w-10 h-10 rounded-full bg-purple-200 text-purple-800 flex items-center justify-center text-xs flex-shrink-0 font-bold"><i class="fas fa-user"></i></div>
                                                <?php endif; ?>
                                                <div class="overflow-hidden">
                                                    <strong class="text-slate-900 block truncate"><?= htmlspecialchars($wp['nama']) ?></strong>
                                                    <span class="text-purple-700 font-bold block text-[7px] truncate"><?= htmlspecialchars($wp['jabatan'] ?? '') ?></span>
                                                    <span class="text-slate-500 line-clamp-1"><?= htmlspecialchars($wp['almamater'] ?? '') ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-2 rounded-xl bg-purple-50 text-purple-900">Dr. KH. Pembina Tahfidz, Lc. MA & Dewan Asatidz Mukim 24 Jam.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 6. SECTION: KEGIATAN SANTRI (1 HALAMAN PENUH HP - SINKRON WEB GALERI) -->
                            <div id="sim-sec-kegiatan" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-pink-100 shadow-sm space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-lg bg-pink-600 text-white flex items-center justify-center text-[10px] shadow-xs">
                                            <i class="fas fa-camera-retro"></i>
                                        </span>
                                        <div>
                                            <span class="text-[7.5px] uppercase font-bold text-pink-600">Hal 6</span>
                                            <h5 class="font-black text-[11px] text-slate-900 leading-tight">Kegiatan Santri</h5>
                                        </div>
                                    </div>
                                    <a href="admin-galeri.php" target="_blank" class="text-[7.5px] text-pink-700 underline font-bold">Edit Web &rarr;</a>
                                </div>

                                <div class="grid grid-cols-2 gap-1.5 text-[7.5px] max-h-[300px] overflow-y-auto no-scrollbar">
                                    <?php if (!empty($web_galeri)): ?>
                                        <?php foreach (array_slice($web_galeri, 0, 4) as $wg): ?>
                                            <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shadow-2xs">
                                                <img src="<?= htmlspecialchars($wg['gambar_url'] ?? '') ?>" class="w-full h-16 object-cover">
                                                <div class="p-1">
                                                    <strong class="block truncate text-[7.5px] text-slate-900"><?= htmlspecialchars($wg['judul'] ?? '') ?></strong>
                                                    <span class="text-slate-500 text-[6.5px] line-clamp-1"><?= htmlspecialchars($wg['caption'] ?? '') ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-span-2 p-2 rounded-xl bg-pink-50 text-pink-900">Halaqah Tahfidz, KBM Kelas, Olahraga Sunnah, Shalat Berjamaah.</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 7. SECTION: INVESTASI PENDIDIKAN (1 HALAMAN PENUH HP - SINKRON WEB BIAYA) -->
                            <div id="sim-sec-investasi" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-amber-200 shadow-sm space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-lg bg-amber-600 text-white flex items-center justify-center text-[10px] shadow-xs">
                                            <i class="fas fa-receipt"></i>
                                        </span>
                                        <div>
                                            <span class="text-[7.5px] uppercase font-bold text-amber-600">Hal 7</span>
                                            <h5 class="font-black text-[11px] text-slate-900 leading-tight">Investasi Pendidikan</h5>
                                        </div>
                                    </div>
                                    <a href="admin-biaya.php" target="_blank" class="text-[7.5px] text-amber-700 underline font-bold">Edit Web &rarr;</a>
                                </div>

                                <div class="space-y-1 text-[7.5px]">
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-500">1. Pendaftaran:</span>
                                        <span class="font-bold text-slate-800" id="sim-val-pendaftaran">Rp <?= number_format($biaya_pendaftaran_val, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-500">2. Uang Pangkal:</span>
                                        <span class="font-bold text-emerald-700" id="sim-val-pangkal">Rp <?= number_format($biaya_pangkal_val, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1 border-b border-slate-100">
                                        <span class="text-slate-500">3. Biaya Tahunan:</span>
                                        <span class="font-bold text-slate-800" id="sim-val-tahunan">Rp <?= number_format($biaya_tahunan_val, 0, ',', '.') ?></span>
                                    </div>
                                    <div class="flex justify-between items-center py-1.5 bg-amber-50 rounded-lg px-2">
                                        <span class="font-bold text-amber-950">4. SPP Bulanan (All-in):</span>
                                        <span class="font-black text-amber-700" id="sim-val-spp">Rp <?= number_format($biaya_spp_val, 0, ',', '.') ?>/bln</span>
                                    </div>
                                    <div class="p-1.5 bg-amber-50/90 rounded-lg border border-amber-200 text-amber-900 flex items-center justify-between text-[7px] mt-1">
                                        <span><i class="fas fa-gift text-amber-600 mr-1"></i> Diskon <?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1') ?>:</span>
                                        <strong id="sim-val-diskon">Hemat Rp <?= number_format($cfg['diskon_gelombang'] ?? 2000000, 0, ',', '.') ?></strong>
                                    </div>
                                </div>

                                <!-- Countdown Mini di Layar HP -->
                                <div id="preview-countdown-box" class="p-1.5 rounded-xl bg-emerald-900 text-white text-center">
                                    <span class="text-[7px] text-amber-300 font-bold block mb-0.5">⏳ Sisa Waktu Pendaftaran Gelombang</span>
                                    <div class="flex justify-center items-center gap-1 font-mono text-[9px] font-black text-amber-300">
                                        <span id="phone-cd-hari">00</span>h : <span id="phone-cd-jam">00</span>j : <span id="phone-cd-menit">00</span>m : <span id="phone-cd-detik" class="text-rose-400">00</span>s
                                    </div>
                                </div>
                            </div>

                            <!-- 8. SECTION: TESTIMONI (1 HALAMAN PENUH HP - SINKRON WEB) -->
                            <div id="sim-sec-testimoni" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-white/95 border border-teal-100 shadow-sm space-y-2">
                                <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-6 h-6 rounded-lg bg-teal-600 text-white flex items-center justify-center text-[10px] shadow-xs">
                                            <i class="fas fa-comments"></i>
                                        </span>
                                        <div>
                                            <span class="text-[7.5px] uppercase font-bold text-teal-600">Hal 8</span>
                                            <h5 class="font-black text-[11px] text-slate-900 leading-tight">Testimoni Walisantri</h5>
                                        </div>
                                    </div>
                                    <a href="admin-testimoni.php" target="_blank" class="text-[7.5px] text-teal-700 underline font-bold">Edit Web &rarr;</a>
                                </div>

                                <div class="space-y-1.5 max-h-[300px] overflow-y-auto no-scrollbar text-[7.5px]">
                                    <?php if (!empty($web_testimoni)): ?>
                                        <?php foreach (array_slice($web_testimoni, 0, 2) as $wt): ?>
                                            <div class="p-2 rounded-xl bg-teal-50/60 border border-teal-100">
                                                <div class="flex items-center justify-between mb-0.5">
                                                    <strong class="text-emerald-950 block"><?= htmlspecialchars($wt['nama']) ?></strong>
                                                    <span class="text-amber-500 text-[7px]"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></span>
                                                </div>
                                                <p class="text-slate-600 italic line-clamp-3">"<?= htmlspecialchars($wt['isi_testimoni']) ?>"</p>
                                                <span class="text-[6.5px] text-slate-400 font-bold block mt-0.5"><?= htmlspecialchars($wt['jabatan'] ?? '') ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="p-2 rounded-xl bg-teal-50 text-teal-900 italic">"Anak kami betah sekali dan hafalan juz 30 hingga 5 sangat mutqin." — Bpk. Hendrawan</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 9. SECTION: FORMULIR (1 HALAMAN PENUH HP) -->
                            <div id="sim-sec-formulir" class="min-h-[480px] snap-start flex flex-col justify-center p-3.5 rounded-2xl bg-gradient-to-br from-amber-50 via-white to-emerald-50 border border-amber-300 shadow-sm space-y-2 text-slate-800">
                                <div class="flex items-center gap-1.5 border-b border-amber-200/80 pb-1.5">
                                    <span class="w-6 h-6 rounded-lg bg-amber-500 text-emerald-950 flex items-center justify-center text-[10px] shadow-xs font-black">
                                        <i class="fas fa-file-pen"></i>
                                    </span>
                                    <div>
                                        <span class="text-[7.5px] uppercase font-bold text-amber-700">Hal 9</span>
                                        <h5 class="font-black text-[11px] text-slate-900 leading-tight">Formulir Pendaftaran</h5>
                                    </div>
                                </div>

                                <div class="space-y-1.5 text-[7.5px]">
                                    <input type="text" placeholder="Nama Orang Tua..." disabled class="w-full p-1.5 bg-white rounded-lg border border-slate-200 text-slate-400">
                                    <input type="text" placeholder="Nomor WhatsApp..." disabled class="w-full p-1.5 bg-white rounded-lg border border-slate-200 text-slate-400">
                                    <input type="text" placeholder="Nama Calon Santri..." disabled class="w-full p-1.5 bg-white rounded-lg border border-slate-200 text-slate-400">
                                    <div class="p-2 bg-gradient-to-r from-amber-400 to-amber-600 text-emerald-950 font-black text-center rounded-xl shadow-xs">
                                        Kirim Reservasi Sekarang
                                    </div>
                                </div>

                                <div class="text-center pt-2">
                                    <button type="button" onclick="setPhoneTab('cover')" class="text-[8.5px] text-emerald-700 font-bold underline hover:text-emerald-900 transition">
                                        &larr; Kembali ke Cover Amplop
                                    </button>
                                </div>
                            </div>

                        </div>

                        <!-- ============================================================ -->
                        <!-- BOTTOM BAR SIMULASI: 9 MENU TANPA FRAME KOTAK (WARNA KUSTOM)  -->
                        <!-- 4 MENU TAMPIL SECARA DEFAULT & BISA DIGESER KANAN-KIRI        -->
                        <!-- ============================================================ -->
                        <div id="sim-bottom-bar" class="relative z-30 shadow-2xl p-1 pb-1.5 rounded-b-[28px] border-t border-white/10 backdrop-blur-md transition-colors duration-300" style="background-color: <?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?>; color: <?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>;">
                            
                            <!-- CAROUSEL TRACK: 4 MENU SECARA DEFAULT, BEBAS FRAME KOTAK -->
                            <div id="sim-bottom-track" class="flex items-center overflow-x-auto no-scrollbar scroll-smooth snap-x snap-mandatory gap-1 px-1 py-1">
                                
                                <!-- 1. Home -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-home', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 text-amber-300">
                                    <i class="fas fa-house text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Home</span>
                                </button>

                                <!-- 2. Mengapa VQBM -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-mengapa', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-heart text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Mengapa</span>
                                </button>

                                <!-- 3. Target Kompetensi -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-kompetensi', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-bullseye text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Kompetensi</span>
                                </button>

                                <!-- 4. Fasilitas -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-fasilitas', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-hotel text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Fasilitas</span>
                                </button>

                                <!-- 5. Dewan Pengasuh -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-pengasuh', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-user-graduate text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Pengasuh</span>
                                </button>

                                <!-- 6. Kegiatan Santri (NEW!) -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-kegiatan', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-camera-retro text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Kegiatan</span>
                                </button>

                                <!-- 7. Investasi -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-investasi', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-receipt text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Investasi</span>
                                </button>

                                <!-- 8. Testimoni -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-testimoni', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-comments text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Testimoni</span>
                                </button>

                                <!-- 9. Formulir -->
                                <button type="button" onclick="navigasiSimulasi('sim-sec-formulir', this)" class="sim-card-menu-item snap-start py-1 px-0.5 flex flex-col items-center justify-center text-center transition group active:scale-95 opacity-80 hover:opacity-100">
                                    <i class="fas fa-file-pen text-lg mb-0.5 group-hover:scale-110 transition"></i>
                                    <span class="text-[8px] font-bold leading-tight truncate w-full">Formulir</span>
                                </button>

                            </div>

                            <!-- CONTROLS GESER KANAN KIRI & INDIKATOR HALAMAN (4 MENU PER TAMPILAN) -->
                            <div class="flex items-center justify-between px-2 pt-1 text-[7.5px] border-t border-white/10 mt-0.5 opacity-80">
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

                <p class="text-[11px] text-slate-400 mt-3 text-center">
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Dimensi lebar, tinggi, font & warna ter-update secara real-time.
                </p>
            </div>

        </div>

    </main>

    <!-- SCRIPT INTERAKTIF REALTIME WORKSPACE -->
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

        function updateLivePreview() {
            const url = document.getElementById('input-cover-bg-url').value.trim();
            const screen = document.getElementById('preview-screen-cover');
            if (url) screen.style.backgroundImage = `url('${url}')`;
        }

        function updateLiveCoverOpacity(val) {
            const pct = Math.round(val * 100);
            document.getElementById('cover-opacity-val').innerText = (pct === 0) ? '0% (Tanpa Digelapkan)' : pct + '%';
            document.getElementById('preview-cover-overlay').style.opacity = val;
        }

        function updateLiveBodyBg() {
            const url = document.getElementById('input-body-bg-url').value.trim();
            const screen = document.getElementById('preview-screen-body');
            if (url) screen.style.backgroundImage = `url('${url}')`;
        }

        function updateLiveBodyOpacity(val) {
            const pct = Math.round(val * 100);
            document.getElementById('body-opacity-val').innerText = (pct === 0) ? '0% (Wallpaper Asli)' : pct + '%';
            document.getElementById('preview-body-overlay').style.opacity = val;
        }

        // 2. Realtime Font & Redaksi Teks
        function updateLiveFont(fontFamily) {
            document.getElementById('phone-container').style.fontFamily = `'${fontFamily}', sans-serif`;
        }

        function updateLiveTextRedaksi() {
            const bismillah = document.getElementById('input-bismillah').value;
            const judul = document.getElementById('input-judul-utama').value;
            const subjudul = document.getElementById('input-subjudul').value;
            const gelombang = document.getElementById('input-gelombang').value;
            const tahun = document.getElementById('input-tahun').value;
            const headerTamu = document.getElementById('input-tamu-header').value;
            const sambutan = document.getElementById('input-tamu-sambutan').value;
            const btnText = document.getElementById('input-btn-text').value;

            document.getElementById('view-bismillah').innerText = bismillah;
            document.getElementById('view-title').innerText = judul;
            document.getElementById('view-subjudul').innerText = subjudul;
            const simTitle = document.getElementById('sim-view-title');
            if (simTitle) simTitle.innerText = judul;
            const simSub = document.getElementById('sim-view-subjudul');
            if (simSub) simSub.innerText = subjudul;
            document.getElementById('preview-sub').innerText = gelombang;
            document.getElementById('preview-tahun-txt').innerText = 'Tahun Ajaran ' + tahun;
            document.getElementById('view-guest-sub').innerText = headerTamu;
            document.getElementById('view-tamu-sambutan').innerText = sambutan;
            document.getElementById('view-btn-label').innerText = btnText;
        }

        // 3. Realtime Sizing: Lebar & Tinggi (Dilebarkan / Disempitkan)
        function updateLiveLogoSize(val) {
            document.getElementById('val-logo-size').innerText = val + ' px';
            const scaled = Math.round(val * 0.55);
            const wrapper = document.getElementById('preview-logo-wrapper');
            if (wrapper) {
                wrapper.style.width = scaled + 'px';
                wrapper.style.height = scaled + 'px';
            }
        }

        function updateLiveCardWidth(val) {
            document.getElementById('val-card-width').innerText = val + '%';
            const card = document.getElementById('preview-elem-guest');
            if (card) card.style.width = val + '%';
        }

        function updateLiveCardPadding(val) {
            document.getElementById('val-card-padding').innerText = val + ' px';
            const scaled = Math.round(val * 0.6);
            const card = document.getElementById('preview-elem-guest');
            if (card) card.style.padding = scaled + 'px';
        }

        function updateLiveBtnWidth(val) {
            document.getElementById('val-btn-width').innerText = val + '%';
            const btn = document.getElementById('view-btn-preview');
            if (btn) btn.style.width = val + '%';
        }

        function updateLiveBtnHeight(val) {
            document.getElementById('val-btn-height').innerText = val + ' px';
            const scaled = Math.round(val * 0.8);
            const btn = document.getElementById('view-btn-preview');
            if (btn) btn.style.height = scaled + 'px';
        }

        function updateLiveTitleSize(val) {
            document.getElementById('val-text-title-size').innerText = val + ' px';
            const scaled = Math.round(val * 0.7);
            document.getElementById('view-title').style.fontSize = scaled + 'px';
        }

        function updateLiveSubSize(val) {
            document.getElementById('val-text-sub-size').innerText = val + ' px';
            const scaled = Math.round(val * 0.8);
            document.getElementById('view-subjudul').style.fontSize = scaled + 'px';
        }

        function updateLiveVideoWidth(val) {
            document.getElementById('val-video-width').innerText = val + '%';
            const box = document.getElementById('preview-video-box');
            if (box) box.style.width = val + '%';
        }

        function updateLiveVideoHeight(val) {
            document.getElementById('val-video-height').innerText = val + ' px';
            const scaled = Math.round(val * 0.45);
            const box = document.getElementById('preview-video-box');
            if (box) box.style.height = scaled + 'px';
        }

        function updateLiveMapsWidth(val) {
            document.getElementById('val-maps-width').innerText = val + '%';
            const box = document.getElementById('preview-maps-box');
            if (box) box.style.width = val + '%';
        }

        function updateLiveMapsHeight(val) {
            document.getElementById('val-maps-height').innerText = val + ' px';
            const scaled = Math.round(val * 0.45);
            const box = document.getElementById('preview-maps-box');
            if (box) box.style.height = scaled + 'px';
        }

        // Toggle Sembunyikan / Hapus Elemen
        function toggleElemVisibility(key, isVisible) {
            let targetEl = null;
            if (key === 'bismillah') targetEl = document.getElementById('view-bismillah');
            if (key === 'logo')      targetEl = document.getElementById('view-logo-container');
            if (key === 'subjudul')  targetEl = document.getElementById('view-subjudul');
            if (key === 'sambutan')  targetEl = document.getElementById('view-tamu-sambutan');
            if (key === 'video')     targetEl = document.getElementById('preview-video-container');
            if (key === 'maps')      targetEl = document.getElementById('preview-maps-container');

            if (targetEl) {
                if (isVisible) {
                    targetEl.classList.remove('hidden');
                } else {
                    targetEl.classList.add('hidden');
                }
            }
        }

        // 4. Penyesuaian Warna Cerdas (Smart Auto Contrast) & Color Picker
        function terapkanTemaWarna(mode) {
            document.getElementById('input-theme-color-mode').value = mode;
            if (mode === 'light_ivory') {
                document.getElementById('input-text-color').value = '#064e45';
                document.getElementById('input-accent-color').value = '#b45309';
                document.getElementById('input-btn-bg-color').value = '#064e45';
                document.getElementById('input-btn-text-color').value = '#fcd34d';
                document.getElementById('input-card-bg-style').value = 'glass_light';
            } else {
                // Default: Emerald Gold (Dark Mode)
                document.getElementById('input-text-color').value = '#ffffff';
                document.getElementById('input-accent-color').value = '#fbbf24';
                document.getElementById('input-btn-bg-color').value = '#d97706';
                document.getElementById('input-btn-text-color').value = '#022d27';
                document.getElementById('input-card-bg-style').value = 'glass_dark';
            }
            updateLiveColors();
        }

        function updateLiveColors() {
            const textColor   = document.getElementById('input-text-color').value;
            const accentColor = document.getElementById('input-accent-color').value;
            const btnBgColor  = document.getElementById('input-btn-bg-color').value;
            const btnTxtColor = document.getElementById('input-btn-text-color').value;
            const cardStyle   = document.getElementById('input-card-bg-style').value;

            document.getElementById('label-text-color').innerText = textColor;
            document.getElementById('label-accent-color').innerText = accentColor;
            document.getElementById('label-btn-bg-color').innerText = btnBgColor;
            document.getElementById('label-btn-text-color').innerText = btnTxtColor;

            document.getElementById('view-title').style.color = textColor;
            document.getElementById('view-audio-note').style.color = textColor;
            document.getElementById('view-bismillah').style.color = accentColor;
            document.getElementById('view-subjudul').style.color = accentColor;
            document.getElementById('preview-sub').style.color = accentColor;
            document.getElementById('view-guest-sub').style.color = accentColor;
            document.getElementById('preview-tahun-txt').style.color = accentColor;

            const btn = document.getElementById('view-btn-preview');
            btn.style.background = btnBgColor;
            btn.style.color = btnTxtColor;

            const guestCard = document.getElementById('preview-elem-guest');
            if (cardStyle === 'glass_light') {
                guestCard.className = 'drag-box z-20 border rounded-2xl backdrop-blur-md shadow-lg transition-all bg-white/85 border-emerald-600/40 text-slate-900';
            } else {
                guestCard.className = 'drag-box z-20 border rounded-2xl backdrop-blur-md shadow-lg transition-all bg-black/45 border-amber-400/40 text-white';
            }
        }

        // 5. Interactive Drag & Drop + Slider Positioning
        function applyElementPosition(elemKey, percentVal) {
            const elem = document.getElementById('preview-elem-' + elemKey);
            if (elem) elem.style.top = percentVal + '%';
            const label = document.getElementById('val-pos-' + elemKey);
            if (label) label.innerText = percentVal + '%';
            const input = document.getElementById('input-pos-' + elemKey);
            if (input && input.value != percentVal) input.value = percentVal;
        }

        function resetDefaultPositions() {
            applyElementPosition('header', 12);
            applyElementPosition('guest', 45);
            applyElementPosition('btn', 82);
        }

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

                const key = currentDragElem.dataset.elem;
                if (key === 'header') newPct = Math.max(2, Math.min(38, newPct));
                if (key === 'guest')  newPct = Math.max(18, Math.min(72, newPct));
                if (key === 'btn')    newPct = Math.max(55, Math.min(95, newPct));

                applyElementPosition(key, newPct);

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
            if (box) box.style.display = isChecked ? 'block' : 'none';
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

        // 6. Navigasi & Kontrol Bottom Bar Simulasi Smartphone
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

        // Live Customizer Bottom Bar
        function updateLiveBottomBarColors() {
            const bgColor = document.getElementById('input-bottom-bar-bg-color').value;
            const textColor = document.getElementById('input-bottom-bar-text-color').value;
            
            document.getElementById('label-bottom-bar-bg-color').innerText = bgColor;
            document.getElementById('label-bottom-bar-text-color').innerText = textColor;
            
            const simBar = document.getElementById('sim-bottom-bar');
            if (simBar) {
                simBar.style.backgroundColor = bgColor;
                simBar.style.color = textColor;
            }
        }

        function setBottomBarColorPreset(bg, text) {
            document.getElementById('input-bottom-bar-bg-color').value = bg;
            document.getElementById('input-bottom-bar-text-color').value = text;
            updateLiveBottomBarColors();
        }

        function setBottomBarTextColor(text) {
            document.getElementById('input-bottom-bar-text-color').value = text;
            updateLiveBottomBarColors();
        }

        function autoContrastBottomBar() {
            const bgColor = document.getElementById('input-bottom-bar-bg-color').value;
            const c = bgColor.replace('#', '');
            const rgb = parseInt(c, 16);
            const r = (rgb >> 16) & 0xff;
            const g = (rgb >>  8) & 0xff;
            const b = (rgb >>  0) & 0xff;
            const luma = 0.2126 * r + 0.7152 * g + 0.0722 * b;
            const newText = (luma > 160) ? '#0f172a' : '#ffffff';
            setBottomBarTextColor(newText);
        }

        function updateLiveInvestasi() {
            const pendaftaran = parseInt(document.getElementById('input-biaya-pendaftaran')?.value) || 0;
            const pangkal = parseInt(document.getElementById('input-biaya-pangkal')?.value) || 0;
            const tahunan = parseInt(document.getElementById('input-biaya-tahunan')?.value) || 0;
            const spp = parseInt(document.getElementById('input-biaya-spp')?.value) || 0;
            const diskon = parseInt(document.getElementById('input-diskon-gelombang')?.value) || 0;

            const elPendaftaran = document.getElementById('sim-val-pendaftaran');
            const elPangkal = document.getElementById('sim-val-pangkal');
            const elTahunan = document.getElementById('sim-val-tahunan');
            const elSpp = document.getElementById('sim-val-spp');
            const elDiskon = document.getElementById('sim-val-diskon');

            if (elPendaftaran) elPendaftaran.innerText = 'Rp ' + pendaftaran.toLocaleString('id-ID');
            if (elPangkal) elPangkal.innerText = 'Rp ' + pangkal.toLocaleString('id-ID');
            if (elTahunan) elTahunan.innerText = 'Rp ' + tahunan.toLocaleString('id-ID');
            if (elSpp) elSpp.innerText = 'Rp ' + spp.toLocaleString('id-ID') + '/bln';
            if (elDiskon) elDiskon.innerText = 'Hemat Rp ' + diskon.toLocaleString('id-ID');
        }

        // Draggable / Swipable Track dengan Mouse
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
