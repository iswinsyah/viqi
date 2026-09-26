<?php
// admin-brosur-settings.php
// Halaman Simulasi Live Brosur & Undangan Digital Smartphone
// Villa Quran Indonesia

require_once 'auth.php';
require_once 'koneksi.php';

// Handler AJAX Upload Gambar Sisipan (Instant Upload)
if (isset($_FILES['ajax_image_file']) && $_FILES['ajax_image_file']['error'] === UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    $ext = strtolower(pathinfo($_FILES['ajax_image_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'])) {
        if (!is_dir('upload')) mkdir('upload', 0755, true);
        $filename = 'upload/img_layer_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['ajax_image_file']['tmp_name'], $filename)) {
            echo json_encode(['success' => true, 'url' => $filename]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Format file tidak didukung atau gagal upload.']);
    exit;
}

// Handler AJAX Upload Video Sisipan (Instant Upload)
if (isset($_FILES['ajax_video_file']) && $_FILES['ajax_video_file']['error'] === UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    $ext = strtolower(pathinfo($_FILES['ajax_video_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov', 'mkv'])) {
        if (!is_dir('upload')) mkdir('upload', 0755, true);
        $filename = 'upload/vid_layer_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($_FILES['ajax_video_file']['tmp_name'], $filename)) {
            echo json_encode(['success' => true, 'url' => $filename]);
            exit;
        }
    }
    echo json_encode(['success' => false, 'error' => 'Format file video tidak didukung atau gagal upload.']);
    exit;
}

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

// Pastikan kolom untuk custom text, images, videos, buttons, bottom bar, dan Frame Prestasi tersedia di tabel pengaturan_brosur
$columns_to_check = [
    'custom_text_items'        => "LONGTEXT",
    'custom_image_items'       => "LONGTEXT",
    'custom_video_items'       => "LONGTEXT",
    'custom_button_items'      => "LONGTEXT",
    'custom_text_content'      => "TEXT",
    'custom_text_format'       => "VARCHAR(20) DEFAULT 'h2'",
    'custom_text_color'        => "VARCHAR(30) DEFAULT '#ffffff'",
    'custom_text_font'         => "VARCHAR(50) DEFAULT 'Plus Jakarta Sans'",
    'custom_text_align'        => "VARCHAR(20) DEFAULT 'center'",
    'custom_text_size'         => "INT DEFAULT 24",
    'custom_text_pos_x'        => "DECIMAL(5,2) DEFAULT 50.00",
    'custom_text_pos_y'        => "DECIMAL(5,2) DEFAULT 35.00",
    'custom_text_width'        => "INT DEFAULT 85",
    'bottom_bar_bg_color'      => "VARCHAR(100) DEFAULT '#022d27'",
    'bottom_bar_text_color'    => "VARCHAR(30) DEFAULT '#ffffff'",
    'bottom_bar_active_color'  => "VARCHAR(30) DEFAULT '#fbbf24'",
    'prestasi_bg_url'          => "TEXT",
    'prestasi_overlay_opacity' => "DECIMAL(3,2) DEFAULT 0.88",
    'prestasi_text_items'      => "LONGTEXT",
    'prestasi_image_items'     => "LONGTEXT",
    'prestasi_video_items'     => "LONGTEXT",
    'prestasi_button_items'    => "LONGTEXT"
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

// Helper simpan dataURL base64 menjadi file fisik di upload/
function saveBase64ImageIfAny($data_url) {
    if (strpos($data_url, 'data:image/') === 0) {
        if (preg_match('/^data:image\/(\w+);base64,/', $data_url, $type)) {
            $data = substr($data_url, strpos($data_url, ',') + 1);
            $type = strtolower($type[1]);
            if ($type === 'jpeg') $type = 'jpg';
            if (in_array($type, ['jpg', 'png', 'gif', 'webp', 'svg'])) {
                $decoded = base64_decode($data);
                if ($decoded !== false) {
                    if (!is_dir('upload')) mkdir('upload', 0755, true);
                    $file_name = 'upload/layer_img_' . time() . '_' . rand(1000, 9999) . '.' . $type;
                    if (file_put_contents($file_name, $decoded)) {
                        return $file_name;
                    }
                }
            }
        }
    }
    return $data_url;
}

// Handler Direct AJAX Image Upload
if (!empty($_FILES['ajax_image_file']['name'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_FILES['ajax_image_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['ajax_image_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'])) {
            if (!is_dir('upload')) mkdir('upload', 0755, true);
            $dest = 'upload/layer_img_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['ajax_image_file']['tmp_name'], $dest)) {
                echo json_encode(['success' => true, 'url' => $dest]);
                exit;
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Gagal upload file gambar']);
    exit;
}

// Handler Direct AJAX Video Upload
if (!empty($_FILES['ajax_video_file']['name'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_FILES['ajax_video_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['ajax_video_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['mp4', 'webm', 'ogg', 'mov'])) {
            if (!is_dir('upload')) mkdir('upload', 0755, true);
            $dest = 'upload/layer_vid_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            if (move_uploaded_file($_FILES['ajax_video_file']['tmp_name'], $dest)) {
                echo json_encode(['success' => true, 'url' => $dest]);
                exit;
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Gagal upload file video']);
    exit;
}

// Variabel Notifikasi
$pesan_sukses = (($_GET['msg'] ?? '') === 'koleksi_deleted') ? 'Background berhasil dihapus dari koleksi.' : '';
$pesan_error  = '';

// Tab Aktif & Frame Aktif
$active_frame = $_POST['active_frame'] ?? $_GET['frame'] ?? 'home';
if (!in_array($active_frame, ['home', 'prestasi'])) {
    $active_frame = 'home';
}

$current_tab = $_POST['active_tab'] ?? $_GET['tab'] ?? 'bg';
if (!in_array($current_tab, ['bg', 'text', 'image', 'video', 'button'])) {
    $current_tab = 'bg';
}

// Proses Simpan Pengaturan (Background, Tulisan Dinamis, Gambar Sisipan, Video Sisipan & Tombol)
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $action_type = $_POST['action_type'] ?? 'save_bg';

    if ($action_type === 'save_all') {
        // Simpan Seluruh Pengaturan Sekaligus (Background, Warna Bottom Bar, Tulisan, Gambar, Video & Tombol)
        $bg_url                  = $conn->real_escape_string(trim($_POST['bg_url'] ?? ''));
        $bg_overlay_opacity      = (float)($_POST['bg_overlay_opacity'] ?? 0.88);
        $bottom_bar_bg_color     = $conn->real_escape_string(trim($_POST['bottom_bar_bg_color'] ?? '#022d27'));
        $bottom_bar_text_color   = $conn->real_escape_string(trim($_POST['bottom_bar_text_color'] ?? '#ffffff'));
        $bottom_bar_active_color = $conn->real_escape_string(trim($_POST['bottom_bar_active_color'] ?? '#fbbf24'));

        // Handle upload BG jika ada
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

        // 1. Text Items
        $text_raw = $_POST['custom_text_items_json'] ?? '[]';
        $decoded_text = json_decode($text_raw, true);
        if (!is_array($decoded_text)) $decoded_text = [];
        $clean_text_items = [];
        foreach ($decoded_text as $idx => $it) {
            $clean_text_items[] = [
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
        $final_text_json_esc = $conn->real_escape_string(json_encode($clean_text_items, JSON_UNESCAPED_UNICODE));

        // 2. Image Items (dengan filter base64)
        $img_raw = $_POST['custom_image_items_json'] ?? '[]';
        $decoded_img = json_decode($img_raw, true);
        if (!is_array($decoded_img)) $decoded_img = [];
        $clean_img_items = [];
        foreach ($decoded_img as $idx => $img) {
            if (empty($img['url'])) continue;
            $saved_url = saveBase64ImageIfAny(trim($img['url']));
            $clean_img_items[] = [
                'id'            => !empty($img['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $img['id']) : 'img_' . ($idx + 1),
                'url'           => $saved_url,
                'shape'         => in_array($img['shape'] ?? '', ['kotak', 'persegi_panjang', 'persegi_panjang_wide', 'rounded', 'bulat', 'oval', 'kubah', 'perisai', 'bintang']) ? $img['shape'] : 'rounded',
                'border_enable' => !empty($img['border_enable']) ? 1 : 0,
                'border_width'  => max(0, min(20, (int)($img['border_width'] ?? 2))),
                'border_color'  => !empty($img['border_color']) ? $img['border_color'] : '#ffffff',
                'border_style'  => in_array($img['border_style'] ?? '', ['solid', 'dashed', 'double']) ? $img['border_style'] : 'solid',
                'shadow_style'  => in_array($img['shadow_style'] ?? '', ['none', 'soft', 'medium', 'deep', 'glow_gold', 'glow_teal']) ? $img['shadow_style'] : 'soft',
                'rotation'      => max(-180, min(180, (float)($img['rotation'] ?? 0))),
                'posX'          => round(max(0, min(100, (float)($img['posX'] ?? 50.0))), 2),
                'posY'          => round(max(0, min(100, (float)($img['posY'] ?? 50.0))), 2),
                'width'         => max(10, min(100, (int)($img['width'] ?? 50)))
            ];
        }
        $final_img_json_esc = $conn->real_escape_string(json_encode($clean_img_items, JSON_UNESCAPED_UNICODE));

        // 3. Video Items
        $vid_raw = $_POST['custom_video_items_json'] ?? '[]';
        $decoded_vid = json_decode($vid_raw, true);
        if (!is_array($decoded_vid)) $decoded_vid = [];
        $clean_vid_items = [];
        foreach ($decoded_vid as $idx => $vid) {
            if (empty($vid['url'])) continue;
            $clean_vid_items[] = [
                'id'            => !empty($vid['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $vid['id']) : 'vid_' . ($idx + 1),
                'url'           => trim($vid['url']),
                'shape'         => in_array($vid['shape'] ?? '', ['kotak', 'persegi_panjang', 'persegi_panjang_wide', 'rounded', 'bulat', 'oval', 'kubah', 'perisai', 'bintang']) ? $vid['shape'] : 'persegi_panjang_wide',
                'border_enable' => !empty($vid['border_enable']) ? 1 : 0,
                'border_width'  => max(0, min(20, (int)($vid['border_width'] ?? 2))),
                'border_color'  => !empty($vid['border_color']) ? $vid['border_color'] : '#ffffff',
                'border_style'  => in_array($vid['border_style'] ?? '', ['solid', 'dashed', 'double']) ? $vid['border_style'] : 'solid',
                'shadow_style'  => in_array($vid['shadow_style'] ?? '', ['none', 'soft', 'medium', 'deep', 'glow_gold', 'glow_teal']) ? $vid['shadow_style'] : 'soft',
                'rotation'      => max(-180, min(180, (float)($vid['rotation'] ?? 0))),
                'posX'          => round(max(0, min(100, (float)($vid['posX'] ?? 50.0))), 2),
                'posY'          => round(max(0, min(100, (float)($vid['posY'] ?? 50.0))), 2),
                'width'         => max(10, min(100, (int)($vid['width'] ?? 75))),
                'autoplay'      => !empty($vid['autoplay']) ? 1 : 0,
                'loop'          => !empty($vid['loop']) ? 1 : 0,
                'muted'         => !empty($vid['muted']) ? 1 : 0,
                'controls'      => !empty($vid['controls']) ? 1 : 0
            ];
        }
        $final_vid_json_esc = $conn->real_escape_string(json_encode($clean_vid_items, JSON_UNESCAPED_UNICODE));

        // 4. Button Items
        $btn_raw = $_POST['custom_button_items_json'] ?? '[]';
        $decoded_btn = json_decode($btn_raw, true);
        if (!is_array($decoded_btn)) $decoded_btn = [];
        $clean_btn_items = [];
        foreach ($decoded_btn as $idx => $btn) {
            $clean_btn_items[] = [
                'id'            => !empty($btn['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $btn['id']) : 'btn_' . ($idx + 1),
                'text'          => trim($btn['text'] ?? 'Tombol Aksi'),
                'url'           => trim($btn['url'] ?? '#'),
                'icon'          => trim($btn['icon'] ?? 'fab fa-whatsapp'),
                'shape'         => in_array($btn['shape'] ?? '', ['rounded_pill', 'persegipanjang', 'bulat', 'rounded']) ? $btn['shape'] : 'rounded_pill',
                'bg_color'      => !empty($btn['bg_color']) ? $btn['bg_color'] : '#25d366',
                'text_color'    => !empty($btn['text_color']) ? $btn['text_color'] : '#ffffff',
                'border_enable' => !empty($btn['border_enable']) ? 1 : 0,
                'border_width'  => max(0, min(20, (int)($btn['border_width'] ?? 2))),
                'border_color'  => !empty($btn['border_color']) ? $btn['border_color'] : '#ffffff',
                'shadow_style'  => in_array($btn['shadow_style'] ?? '', ['none', 'soft', 'medium', 'deep', 'floating', 'glow_gold', 'glow_teal', 'glow_wa', 'glow_rose', 'glow_sky']) ? $btn['shadow_style'] : 'soft',
                'font_size'     => max(9, min(40, (int)($btn['font_size'] ?? 14))),
                'font'          => !empty($btn['font']) ? $btn['font'] : 'Plus Jakarta Sans',
                'posX'          => round(max(0, min(100, (float)($btn['posX'] ?? 50.0))), 2),
                'posY'          => round(max(0, min(100, (float)($btn['posY'] ?? 80.0))), 2),
                'width'         => max(10, min(100, (int)($btn['width'] ?? 80))),
                'height'        => max(24, min(150, (int)($btn['height'] ?? 46))),
                'target'        => ($btn['target'] ?? '_blank') === '_self' ? '_self' : '_blank'
            ];
        }
        $final_btn_json_esc = $conn->real_escape_string(json_encode($clean_btn_items, JSON_UNESCAPED_UNICODE));

        if ($active_frame === 'prestasi') {
            $sql_master = "UPDATE pengaturan_brosur SET 
                            prestasi_bg_url = '$bg_url',
                            prestasi_overlay_opacity = $bg_overlay_opacity,
                            prestasi_text_items = '$final_text_json_esc',
                            prestasi_image_items = '$final_img_json_esc',
                            prestasi_video_items = '$final_vid_json_esc',
                            prestasi_button_items = '$final_btn_json_esc',
                            bottom_bar_bg_color = '$bottom_bar_bg_color',
                            bottom_bar_text_color = '$bottom_bar_text_color',
                            bottom_bar_active_color = '$bottom_bar_active_color'
                           WHERE id = 1";
        } else {
            $first = $clean_text_items[0] ?? [
                'content' => '', 'format' => 'h2', 'color' => '#ffffff',
                'font' => 'Plus Jakarta Sans', 'align' => 'center', 'size' => 24,
                'posX' => 50, 'posY' => 35, 'width' => 85
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

            $sql_master = "UPDATE pengaturan_brosur SET 
                            cover_bg_url = '$bg_url',
                            cover_overlay_opacity = $bg_overlay_opacity,
                            body_bg_url = '$bg_url',
                            body_overlay_opacity = $bg_overlay_opacity,
                            custom_text_items = '$final_text_json_esc',
                            custom_text_content = '$c_content',
                            custom_text_format  = '$c_format',
                            custom_text_color   = '$c_color',
                            custom_text_font    = '$c_font',
                            custom_text_align   = '$c_align',
                            custom_text_size    = $c_size,
                            custom_text_pos_x   = $c_pos_x,
                            custom_text_pos_y   = $c_pos_y,
                            custom_text_width   = $c_width,
                            custom_image_items = '$final_img_json_esc',
                            custom_video_items = '$final_vid_json_esc',
                            custom_button_items = '$final_btn_json_esc',
                            bottom_bar_bg_color = '$bottom_bar_bg_color',
                            bottom_bar_text_color = '$bottom_bar_text_color',
                            bottom_bar_active_color = '$bottom_bar_active_color'
                           WHERE id = 1";
        }

        $ok = $conn->query($sql_master);
        $frame_label = ($active_frame === 'prestasi') ? 'Frame Prestasi' : 'Frame Home';
        $msg = $ok ? "Alhamdulillah! Seluruh pengaturan {$frame_label} berhasil disimpan." : "Gagal menyimpan: " . $conn->error;

        if (isset($_POST['ajax_mode']) && $_POST['ajax_mode'] == '1') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => (bool)$ok,
                'message' => $msg,
                'frame'   => $active_frame,
                'clean_images' => $clean_img_items
            ]);
            exit;
        }

        if ($ok) {
            $pesan_sukses = $msg;
        } else {
            $pesan_error = $msg;
        }
    } else if ($action_type === 'save_text') {
        $current_tab = 'text';
        $json_raw = $_POST['custom_text_items_json'] ?? '[]';
        $decoded = json_decode($json_raw, true);

        if (!is_array($decoded)) {
            $decoded = [];
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

        if ($active_frame === 'prestasi') {
            $sql_text = "UPDATE pengaturan_brosur SET prestasi_text_items = '$final_json_esc' WHERE id = 1";
        } else {
            // Update legacy columns dari item pertama sebagai fallback jika ada
            $first = $clean_items[0] ?? [
                'content' => '', 'format' => 'h2', 'color' => '#ffffff',
                'font' => 'Plus Jakarta Sans', 'align' => 'center', 'size' => 24,
                'posX' => 50, 'posY' => 35, 'width' => 85
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
        }

        if ($conn->query($sql_text)) {
            $frame_label = ($active_frame === 'prestasi') ? 'Frame Prestasi' : 'Frame Home';
            $pesan_sukses = "Alhamdulillah! Pengaturan kolom tulisan {$frame_label} (" . count($clean_items) . " kolom) berhasil disimpan.";
        } else {
            $pesan_error = "Gagal menyimpan tulisan: " . $conn->error;
        }
    } else if ($action_type === 'save_images') {
        $current_tab = 'image';
        // Simpan Gambar Sisipan (Multi-Layer Images)
        $json_raw = $_POST['custom_image_items_json'] ?? '[]';
        $decoded = json_decode($json_raw, true);
        if (!is_array($decoded)) $decoded = [];

        $clean_img_items = [];
        foreach ($decoded as $idx => $img) {
            if (empty($img['url'])) continue;
            $saved_url = saveBase64ImageIfAny(trim($img['url']));
            $clean_img_items[] = [
                'id'            => !empty($img['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $img['id']) : 'img_' . ($idx + 1),
                'url'           => $saved_url,
                'shape'         => in_array($img['shape'] ?? '', ['kotak', 'persegi_panjang', 'persegi_panjang_wide', 'rounded', 'bulat', 'oval', 'kubah', 'perisai', 'bintang']) ? $img['shape'] : 'rounded',
                'border_enable' => !empty($img['border_enable']) ? 1 : 0,
                'border_width'  => max(0, min(20, (int)($img['border_width'] ?? 2))),
                'border_color'  => !empty($img['border_color']) ? $img['border_color'] : '#ffffff',
                'border_style'  => in_array($img['border_style'] ?? '', ['solid', 'dashed', 'double']) ? $img['border_style'] : 'solid',
                'shadow_style'  => in_array($img['shadow_style'] ?? '', ['none', 'soft', 'medium', 'deep', 'glow_gold', 'glow_teal']) ? $img['shadow_style'] : 'soft',
                'rotation'      => max(-180, min(180, (float)($img['rotation'] ?? 0))),
                'posX'          => round(max(0, min(100, (float)($img['posX'] ?? 50.0))), 2),
                'posY'          => round(max(0, min(100, (float)($img['posY'] ?? 50.0))), 2),
                'width'         => max(10, min(100, (int)($img['width'] ?? 50)))
            ];
        }

        $final_img_json = json_encode($clean_img_items, JSON_UNESCAPED_UNICODE);
        $final_img_json_esc = $conn->real_escape_string($final_img_json);

        if ($active_frame === 'prestasi') {
            $sql_img = "UPDATE pengaturan_brosur SET prestasi_image_items = '$final_img_json_esc' WHERE id = 1";
        } else {
            $sql_img = "UPDATE pengaturan_brosur SET custom_image_items = '$final_img_json_esc' WHERE id = 1";
        }

        if ($conn->query($sql_img)) {
            $frame_label = ($active_frame === 'prestasi') ? 'Frame Prestasi' : 'Frame Home';
            $pesan_sukses = "Alhamdulillah! Pengaturan gambar sisipan {$frame_label} (" . count($clean_img_items) . " gambar) berhasil disimpan.";
        } else {
            $pesan_error = "Gagal menyimpan gambar: " . $conn->error;
        }
    } else if ($action_type === 'save_videos') {
        $current_tab = 'video';
        // Simpan Video Sisipan (Multi-Layer Videos)
        $json_raw = $_POST['custom_video_items_json'] ?? '[]';
        $decoded = json_decode($json_raw, true);
        if (!is_array($decoded)) $decoded = [];

        $clean_vid_items = [];
        foreach ($decoded as $idx => $vid) {
            if (empty($vid['url'])) continue;
            $clean_vid_items[] = [
                'id'            => !empty($vid['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $vid['id']) : 'vid_' . ($idx + 1),
                'url'           => trim($vid['url']),
                'shape'         => in_array($vid['shape'] ?? '', ['kotak', 'persegi_panjang', 'persegi_panjang_wide', 'rounded', 'bulat', 'oval', 'kubah', 'perisai', 'bintang']) ? $vid['shape'] : 'persegi_panjang_wide',
                'border_enable' => !empty($vid['border_enable']) ? 1 : 0,
                'border_width'  => max(0, min(20, (int)($vid['border_width'] ?? 2))),
                'border_color'  => !empty($vid['border_color']) ? $vid['border_color'] : '#ffffff',
                'border_style'  => in_array($vid['border_style'] ?? '', ['solid', 'dashed', 'double']) ? $vid['border_style'] : 'solid',
                'shadow_style'  => in_array($vid['shadow_style'] ?? '', ['none', 'soft', 'medium', 'deep', 'glow_gold', 'glow_teal']) ? $vid['shadow_style'] : 'soft',
                'rotation'      => max(-180, min(180, (float)($vid['rotation'] ?? 0))),
                'posX'          => round(max(0, min(100, (float)($vid['posX'] ?? 50.0))), 2),
                'posY'          => round(max(0, min(100, (float)($vid['posY'] ?? 50.0))), 2),
                'width'         => max(10, min(100, (int)($vid['width'] ?? 75))),
                'autoplay'      => !empty($vid['autoplay']) ? 1 : 0,
                'loop'          => !empty($vid['loop']) ? 1 : 0,
                'muted'         => !empty($vid['muted']) ? 1 : 0,
                'controls'      => !empty($vid['controls']) ? 1 : 0
            ];
        }

        $final_vid_json = json_encode($clean_vid_items, JSON_UNESCAPED_UNICODE);
        $final_vid_json_esc = $conn->real_escape_string($final_vid_json);

        if ($active_frame === 'prestasi') {
            $sql_vid = "UPDATE pengaturan_brosur SET prestasi_video_items = '$final_vid_json_esc' WHERE id = 1";
        } else {
            $sql_vid = "UPDATE pengaturan_brosur SET custom_video_items = '$final_vid_json_esc' WHERE id = 1";
        }

        if ($conn->query($sql_vid)) {
            $frame_label = ($active_frame === 'prestasi') ? 'Frame Prestasi' : 'Frame Home';
            $pesan_sukses = "Alhamdulillah! Pengaturan video sisipan {$frame_label} (" . count($clean_vid_items) . " video) berhasil disimpan.";
        } else {
            $pesan_error = "Gagal menyimpan video: " . $conn->error;
        }
    } else if ($action_type === 'save_buttons') {
        $current_tab = 'button';
        // Simpan Data Tombol Aksi (Buttons)
        $json_raw = $_POST['custom_button_items_json'] ?? '[]';
        $decoded = json_decode($json_raw, true);
        if (!is_array($decoded)) $decoded = [];

        $clean_btn_items = [];
        foreach ($decoded as $idx => $btn) {
            $clean_btn_items[] = [
                'id'            => !empty($btn['id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $btn['id']) : 'btn_' . ($idx + 1),
                'text'          => trim($btn['text'] ?? 'Tombol Aksi'),
                'url'           => trim($btn['url'] ?? '#'),
                'icon'          => trim($btn['icon'] ?? 'fab fa-whatsapp'),
                'shape'         => in_array($btn['shape'] ?? '', ['rounded_pill', 'persegipanjang', 'bulat', 'rounded']) ? $btn['shape'] : 'rounded_pill',
                'bg_color'      => !empty($btn['bg_color']) ? $btn['bg_color'] : '#25d366',
                'text_color'    => !empty($btn['text_color']) ? $btn['text_color'] : '#ffffff',
                'border_enable' => !empty($btn['border_enable']) ? 1 : 0,
                'border_width'  => max(0, min(20, (int)($btn['border_width'] ?? 2))),
                'border_color'  => !empty($btn['border_color']) ? $btn['border_color'] : '#ffffff',
                'shadow_style'  => in_array($btn['shadow_style'] ?? '', ['none', 'soft', 'medium', 'deep', 'floating', 'glow_gold', 'glow_teal', 'glow_wa', 'glow_rose', 'glow_sky']) ? $btn['shadow_style'] : 'soft',
                'font_size'     => max(9, min(40, (int)($btn['font_size'] ?? 14))),
                'font'          => !empty($btn['font']) ? $btn['font'] : 'Plus Jakarta Sans',
                'posX'          => round(max(0, min(100, (float)($btn['posX'] ?? 50.0))), 2),
                'posY'          => round(max(0, min(100, (float)($btn['posY'] ?? 80.0))), 2),
                'width'         => max(10, min(100, (int)($btn['width'] ?? 80))),
                'height'        => max(24, min(150, (int)($btn['height'] ?? 46))),
                'target'        => ($btn['target'] ?? '_blank') === '_self' ? '_self' : '_blank'
            ];
        }

        $final_btn_json = json_encode($clean_btn_items, JSON_UNESCAPED_UNICODE);
        $final_btn_json_esc = $conn->real_escape_string($final_btn_json);

        if ($active_frame === 'prestasi') {
            $sql_btn = "UPDATE pengaturan_brosur SET prestasi_button_items = '$final_btn_json_esc' WHERE id = 1";
        } else {
            $sql_btn = "UPDATE pengaturan_brosur SET custom_button_items = '$final_btn_json_esc' WHERE id = 1";
        }

        if ($conn->query($sql_btn)) {
            $frame_label = ($active_frame === 'prestasi') ? 'Frame Prestasi' : 'Frame Home';
            $pesan_sukses = "Alhamdulillah! Pengaturan tombol {$frame_label} (" . count($clean_btn_items) . " tombol) berhasil disimpan.";
        } else {
            $pesan_error = "Gagal menyimpan tombol: " . $conn->error;
        }
    } else {
        $current_tab = 'bg';
        // Simpan Background & Warna Bottom Bar
        $bg_url                  = $conn->real_escape_string(trim($_POST['bg_url'] ?? ''));
        $bg_overlay_opacity      = (float)($_POST['bg_overlay_opacity'] ?? 0.88);
        $bottom_bar_bg_color     = $conn->real_escape_string(trim($_POST['bottom_bar_bg_color'] ?? '#022d27'));
        $bottom_bar_text_color   = $conn->real_escape_string(trim($_POST['bottom_bar_text_color'] ?? '#ffffff'));
        $bottom_bar_active_color = $conn->real_escape_string(trim($_POST['bottom_bar_active_color'] ?? '#fbbf24'));

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

        if ($active_frame === 'prestasi') {
            $sql_update = "UPDATE pengaturan_brosur SET 
                            prestasi_bg_url = '$bg_url',
                            prestasi_overlay_opacity = $bg_overlay_opacity,
                            bottom_bar_bg_color = '$bottom_bar_bg_color',
                            bottom_bar_text_color = '$bottom_bar_text_color',
                            bottom_bar_active_color = '$bottom_bar_active_color'
                           WHERE id = 1";
        } else {
            // Terapkan ke cover, body, dan bottom bar
            $sql_update = "UPDATE pengaturan_brosur SET 
                            cover_bg_url = '$bg_url',
                            cover_overlay_opacity = $bg_overlay_opacity,
                            body_bg_url = '$bg_url',
                            body_overlay_opacity = $bg_overlay_opacity,
                            bottom_bar_bg_color = '$bottom_bar_bg_color',
                            bottom_bar_text_color = '$bottom_bar_text_color',
                            bottom_bar_active_color = '$bottom_bar_active_color'
                           WHERE id = 1";
        }

        if ($conn->query($sql_update)) {
            if (!empty($bg_url)) {
                $check = $conn->query("SELECT id FROM koleksi_background WHERE url = '$bg_url' LIMIT 1");
                if ($check && $check->num_rows == 0) {
                    $judul = 'Background ' . date('d M Y');
                    $conn->query("INSERT INTO koleksi_background (tipe, judul, url) VALUES ('all', '$judul', '$bg_url')");
                }
            }

            $frame_label = ($active_frame === 'prestasi') ? 'Frame Prestasi' : 'Frame Home';
            $pesan_sukses = "Alhamdulillah! Pengaturan Background {$frame_label} & Bottom Bar berhasil disimpan.";
        } else {
            $pesan_error = "Gagal menyimpan background & bottom bar: " . $conn->error;
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

// ==============================================================
// 1. DATA FRAME HOME (COVER / HALAMAN DEPAN)
// ==============================================================
$raw_items = $cfg['custom_text_items'] ?? null;
if ($raw_items !== null && $raw_items !== '') {
    $text_items = json_decode($raw_items, true);
    if (!is_array($text_items)) $text_items = [];
} else {
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
            'id'            => 'btn_' . time() . '_1',
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

// ==============================================================
// 2. DATA FRAME PRESTASI (PENCAPAIAN & PRESTASI SANTRI)
// ==============================================================
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
            'text'          => 'Konsultasi Beasiswa Prestasi',
            'url'           => 'https://wa.me/6281234567890?text=Assalamu%27alaikum%2C%20saya%20ingin%20info%20program%20prestasi%20Villa%20Quran',
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
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cinzel:wght@500;700;900&family=Inter:wght@300;400;600;700&family=Outfit:wght@400;600;800;900&family=Playfair+Display:ital,wght@0,600;0,800;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;600;700;800&family=Oswald:wght@400;600;700&family=Barlow+Condensed:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        @font-face {
            font-family: 'Marlin Condensed';
            src: local('Marlin Condensed'), local('MarlinCondensed'), local('Marlin-Condensed'), local('Marlin'), local('MarlinBold');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; }
        
        /* Font Utility Classes */
        .font-marlin { font-family: 'Marlin Condensed', 'Barlow Condensed', 'Oswald', sans-serif; }
        
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

        /* Draggable item container */
        .draggable-box {
            touch-action: none;
            cursor: grab;
            user-select: none;
        }
        .draggable-box:active {
            cursor: grabbing;
        }

        /* Compact Row Strip */
        .item-row-strip {
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .item-row-strip:hover {
            border-color: #0b8478;
        }
        .item-row-strip.active-layer {
            border-color: #0b8478;
            background-color: #f0fdfa;
            box-shadow: 0 4px 12px -2px rgba(11, 132, 120, 0.12);
        }

        /* Shape Styles */
        .shape-kotak { border-radius: 0px; aspect-ratio: 1/1; }
        .shape-persegi_panjang { border-radius: 14px; aspect-ratio: 4/3; }
        .shape-persegi_panjang_wide { border-radius: 14px; aspect-ratio: 16/9; }
        .shape-rounded { border-radius: 24px; aspect-ratio: 1/1; }
        .shape-bulat { border-radius: 50%; aspect-ratio: 1/1; }
        .shape-oval { border-radius: 50%; aspect-ratio: 4/3; }
        .shape-kubah { border-radius: 120px 120px 16px 16px; aspect-ratio: 3/4; }
        .shape-perisai { border-radius: 16px 16px 50% 50%; aspect-ratio: 1/1; }
        .shape-bintang { clip-path: polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%); aspect-ratio: 1/1; }

        /* Shadow Styles */
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

        /* Button Shape Helpers */
        .shape-btn-rounded_pill { border-radius: 9999px !important; }
        .shape-btn-persegipanjang { border-radius: 4px !important; }
        .shape-btn-bulat { border-radius: 50% !important; aspect-ratio: 1/1 !important; display: inline-flex !important; align-items: center !important; justify-content: center !important; }
        .shape-btn-rounded { border-radius: 14px !important; }

        /* Guidelines & Smart Snap Lines */
        .snap-guide-line-x {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 1.5px;
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
            pointer-events: none;
            z-index: 60;
            display: none;
        }
        .snap-guide-line-y {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1.5px;
            background: #38bdf8;
            box-shadow: 0 0 8px #38bdf8;
            pointer-events: none;
            z-index: 60;
            display: none;
        }
        .grid-guide-line {
            pointer-events: none;
            position: absolute;
            z-index: 55;
            transition: opacity 0.2s;
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
                    <h1 class="text-lg sm:text-xl font-black text-slate-900 leading-tight">Pengaturan Brosur & Undangan Digital</h1>
                    <p class="text-xs text-slate-500">Sisipkan gambar frame (kotak, bulat, kubah, rotasi), kolom tulisan & background dengan garis bantu presisi</p>
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
            
            <!-- PANEL KIRI: PENGATURAN BERBASIS TAB (TAB 1: BACKGROUND, TAB 2: TULISAN, TAB 3: GAMBAR) -->
            <div class="lg:col-span-7 xl:col-span-7 space-y-5">

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

                <!-- ============================================================== -->
                <!-- FRAME SWITCHER BAR: PILIH ANTARA FRAME HOME & FRAME PRESTASI   -->
                <!-- ============================================================== -->
                <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 p-2 sm:p-2.5 rounded-3xl border border-slate-700/80 shadow-md flex items-center gap-2">
                    <div class="text-white text-xs font-black uppercase tracking-wider px-2 shrink-0 flex items-center gap-1.5">
                        <i class="fas fa-layer-group text-amber-400"></i>
                        <span class="hidden sm:inline">Pilih Frame:</span>
                    </div>
                    
                    <!-- Tombol Frame 1: Home -->
                    <button type="button" id="frame-btn-home" onclick="switchFrame('home')" class="flex-1 py-2.5 px-3 rounded-2xl font-black text-xs sm:text-sm transition flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md ring-2 ring-emerald-400 cursor-pointer">
                        <i class="fas fa-house text-xs"></i>
                        <span>1. Frame: Home</span>
                        <span class="hidden md:inline px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-900/80 text-emerald-200">Cover</span>
                    </button>

                    <!-- Tombol Frame 2: Prestasi -->
                    <button type="button" id="frame-btn-prestasi" onclick="switchFrame('prestasi')" class="flex-1 py-2.5 px-3 rounded-2xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 text-slate-300 hover:text-white hover:bg-white/10 cursor-pointer">
                        <i class="fas fa-trophy text-xs text-amber-400"></i>
                        <span>2. Frame: Prestasi</span>
                        <span class="hidden md:inline px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-900/60 text-amber-200">Menu #2</span>
                    </button>
                </div>

                <!-- ============================================================== -->
                <!-- MASTER CONTAINER: 5 TAB PENGATURAN FRAME (HOME / PRESTASI)    -->
                <!-- ============================================================== -->
                <div class="bg-white/90 backdrop-blur-md rounded-3xl p-5 sm:p-6 border-2 border-emerald-500/30 shadow-sm space-y-5">
                    
                    <!-- FRAME HEADER: DINAMIS SESUAI FRAME TERPILIH -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200/80 pb-4">
                        <div class="flex items-center gap-3.5">
                            <div id="frame-header-icon-box" class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-[#0b8478] text-white flex items-center justify-center text-xl shadow-md shadow-emerald-600/20 shrink-0">
                                <i id="frame-header-icon" class="fas fa-house"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 id="frame-header-title" class="font-black text-lg sm:text-xl text-slate-900 tracking-tight">Frame: Home</h2>
                                    <span id="frame-header-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        Cover / Halaman Depan
                                    </span>
                                </div>
                                <p id="frame-header-desc" class="text-xs text-slate-500 mt-0.5">
                                    Frame ini mengatur tampilan layar pertama saat calon wali santri membuka brosur digital (Menu <strong>Home</strong> pada Bottom Navigation Bar).
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
                            <span id="frame-header-menu-pill" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-amber-50 text-amber-900 border border-amber-200/80 flex items-center gap-1.5 shadow-2xs">
                                <i class="fas fa-compass text-amber-600"></i>
                                <span id="frame-header-menu-text">Menu #1 di Bottom Bar</span>
                            </span>

                            <!-- TOMBOL MASTER SAVE UNTUK SELURUH PENGATURAN -->
                            <button type="button" id="btn-master-save" onclick="saveAllSettings()" class="px-4 sm:px-5 py-2.5 rounded-2xl bg-gradient-to-r from-[#0b8478] via-emerald-600 to-teal-700 hover:from-[#086b61] hover:to-teal-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2 transform active:scale-95 cursor-pointer ring-2 ring-emerald-300" title="Simpan Semua Pengaturan (Background, Tulisan, Gambar, Video, Tombol) dalam 1 Klik">
                                <i class="fas fa-floppy-disk text-amber-300 text-base"></i>
                                <span>Simpan Seluruh Pengaturan</span>
                            </button>
                        </div>
                    </div>

                    <!-- NAVIGASI 5 SUB-TAB DALAM FRAME AKTIF (TAB 1: BACKGROUND, TAB 2: TULISAN, TAB 3: GAMBAR, TAB 4: VIDEO, TAB 5: TOMBOL) -->
                    <div class="bg-slate-100/90 p-1.5 rounded-2xl border border-slate-200/90 shadow-inner flex items-center gap-1 sm:gap-1.5 sticky top-20 z-20 overflow-x-auto">
                        
                        <!-- TAB 1: BACKGROUND BROSUR -->
                        <button type="button" id="tab-btn-bg" onclick="switchTab('bg')" class="tab-nav-btn flex-1 min-w-[90px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-black text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 bg-gradient-to-r from-[#0b8478] to-[#075f56] text-white shadow-md cursor-pointer">
                            <i class="fas fa-image text-sm sm:text-base"></i>
                            <span>1. Background</span>
                        </button>

                        <!-- TAB 2: KOLOM TULISAN -->
                        <button type="button" id="tab-btn-text" onclick="switchTab('text')" class="tab-nav-btn flex-1 min-w-[85px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 cursor-pointer">
                            <i class="fas fa-font text-sm sm:text-base"></i>
                            <span>2. Tulisan</span>
                            <span id="tab-badge-text" class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] font-black bg-teal-100 text-teal-800">
                                <?= count($text_items) ?>
                            </span>
                        </button>

                        <!-- TAB 3: SISIPKAN GAMBAR -->
                        <button type="button" id="tab-btn-image" onclick="switchTab('image')" class="tab-nav-btn flex-1 min-w-[85px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 cursor-pointer">
                            <i class="fas fa-shapes text-sm sm:text-base"></i>
                            <span>3. Gambar</span>
                            <span id="tab-badge-image" class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] font-black bg-sky-100 text-sky-800">
                                <?= count($image_items) ?>
                            </span>
                        </button>

                        <!-- TAB 4: SISIPKAN VIDEO -->
                        <button type="button" id="tab-btn-video" onclick="switchTab('video')" class="tab-nav-btn flex-1 min-w-[85px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 cursor-pointer">
                            <i class="fas fa-video text-sm sm:text-base"></i>
                            <span>4. Video</span>
                            <span id="tab-badge-video" class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">
                                <?= count($video_items) ?>
                            </span>
                        </button>

                        <!-- TAB 5: PENGATURAN TOMBOL -->
                        <button type="button" id="tab-btn-button" onclick="switchTab('button')" class="tab-nav-btn flex-1 min-w-[85px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 cursor-pointer">
                            <i class="fas fa-hand-pointer text-sm sm:text-base"></i>
                            <span>5. Tombol</span>
                            <span id="tab-badge-button" class="px-1.5 sm:px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                <?= count($button_items) ?>
                            </span>
                        </button>
                    </div>

                <!-- ========================================== -->
                <!-- KONTEN TAB 1: PENGATURAN BACKGROUND BROSUR -->
                <!-- ========================================== -->
                <div id="tab-content-bg" class="tab-pane space-y-6">
                    
                    <!-- KARTU FORM UTAMA BACKGROUND -->
                    <form action="" method="POST" enctype="multipart/form-data" id="form-pengaturan-bg" class="space-y-6">
                        <input type="hidden" name="action_type" value="save_bg">
                        <input type="hidden" name="active_tab" value="bg">
                        <input type="hidden" name="active_frame" value="<?= $active_frame ?>" class="input-active-frame">

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
                                    <i class="fas fa-mobile-screen-button text-teal-600"></i> Portrait HP (9:16)
                                </span>
                            </div>

                            <!-- KONTEN PENGATURAN BACKGROUND -->
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

                            <!-- KONTEN PENGATURAN WARNA BOTTOM BAR (NAVIGASI BAWAH) -->
                            <div class="p-4 sm:p-5 rounded-2xl bg-slate-50/70 border border-slate-200/80 space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm shadow-2xs">
                                            <i class="fas fa-palette"></i>
                                        </div>
                                        <div>
                                            <h3 class="font-black text-sm text-slate-900">Pengaturan Warna Bottom Bar (Navigasi Bawah)</h3>
                                            <p class="text-[11px] text-slate-500">Sesuaikan kombinasi warna bar menu bawah yang tampil di layar brosur digital</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900">
                                        Live Sync
                                    </span>
                                </div>

                                <!-- 3 Input Warna: Background Bar, Teks/Icon Normal, Menu Aktif -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                    
                                    <!-- Warna Background Bar -->
                                    <div class="bg-white p-3 rounded-xl border border-slate-200/80 space-y-1.5 shadow-2xs">
                                        <label class="block text-[11px] font-bold text-slate-700">Warna Latar Bar:</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" id="input-bbar-bg-picker" value="<?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?>" oninput="syncBBarColor('bg', this.value)" class="w-8 h-8 rounded-lg border border-slate-300 cursor-pointer p-0 bg-transparent shrink-0">
                                            <input type="text" name="bottom_bar_bg_color" id="input-bbar-bg" value="<?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?>" oninput="syncBBarColor('bg_text', this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono focus:border-teal-600 focus:outline-none bg-slate-50">
                                        </div>
                                    </div>

                                    <!-- Warna Teks / Icon Normal -->
                                    <div class="bg-white p-3 rounded-xl border border-slate-200/80 space-y-1.5 shadow-2xs">
                                        <label class="block text-[11px] font-bold text-slate-700">Warna Teks & Icon Normal:</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" id="input-bbar-text-picker" value="<?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>" oninput="syncBBarColor('text', this.value)" class="w-8 h-8 rounded-lg border border-slate-300 cursor-pointer p-0 bg-transparent shrink-0">
                                            <input type="text" name="bottom_bar_text_color" id="input-bbar-text" value="<?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>" oninput="syncBBarColor('text_text', this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono focus:border-teal-600 focus:outline-none bg-slate-50">
                                        </div>
                                    </div>

                                    <!-- Warna Menu Aktif / Aksen -->
                                    <div class="bg-white p-3 rounded-xl border border-slate-200/80 space-y-1.5 shadow-2xs">
                                        <label class="block text-[11px] font-bold text-slate-700">Warna Menu Aktif (Aksen):</label>
                                        <div class="flex items-center gap-2">
                                            <input type="color" id="input-bbar-active-picker" value="<?= htmlspecialchars($cfg['bottom_bar_active_color'] ?? '#fbbf24') ?>" oninput="syncBBarColor('active', this.value)" class="w-8 h-8 rounded-lg border border-slate-300 cursor-pointer p-0 bg-transparent shrink-0">
                                            <input type="text" name="bottom_bar_active_color" id="input-bbar-active" value="<?= htmlspecialchars($cfg['bottom_bar_active_color'] ?? '#fbbf24') ?>" oninput="syncBBarColor('active_text', this.value)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono focus:border-teal-600 focus:outline-none bg-slate-50">
                                        </div>
                                    </div>

                                </div>

                                <!-- Preset Tema Warna Cepat -->
                                <div class="pt-2 border-t border-slate-200/60">
                                    <label class="block text-[11px] font-bold text-slate-700 mb-2">Preset Tema Warna Cepat (1-Klik):</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" onclick="applyBBarPreset('#022d27', '#ffffff', '#fbbf24')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold flex items-center gap-2 border border-slate-700 transition cursor-pointer shadow-2xs">
                                            <span class="w-3 h-3 rounded-full bg-[#022d27] border border-amber-400"></span>
                                            <span>Dark Emerald & Gold</span>
                                        </button>
                                        <button type="button" onclick="applyBBarPreset('#0f172a', '#94a3b8', '#38bdf8')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold flex items-center gap-2 border border-slate-700 transition cursor-pointer shadow-2xs">
                                            <span class="w-3 h-3 rounded-full bg-[#0f172a] border border-sky-400"></span>
                                            <span>Midnight Sky</span>
                                        </button>
                                        <button type="button" onclick="applyBBarPreset('#0c1a30', '#cbd5e1', '#34d399')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold flex items-center gap-2 border border-slate-700 transition cursor-pointer shadow-2xs">
                                            <span class="w-3 h-3 rounded-full bg-[#0c1a30] border border-emerald-400"></span>
                                            <span>Royal Navy</span>
                                        </button>
                                        <button type="button" onclick="applyBBarPreset('#18181b', '#d4d4d8', '#f59e0b')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold flex items-center gap-2 border border-slate-700 transition cursor-pointer shadow-2xs">
                                            <span class="w-3 h-3 rounded-full bg-[#18181b] border border-amber-500"></span>
                                            <span>Luxury Charcoal</span>
                                        </button>
                                        <button type="button" onclick="applyBBarPreset('#2a0812', '#fecdd3', '#fb7185')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold flex items-center gap-2 border border-slate-700 transition cursor-pointer shadow-2xs">
                                            <span class="w-3 h-3 rounded-full bg-[#2a0812] border border-rose-400"></span>
                                            <span>Velvet Maroon</span>
                                        </button>
                                        <button type="button" onclick="applyBBarPreset('#050505', '#e2e8f0', '#60a5fa')" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-bold flex items-center gap-2 border border-slate-700 transition cursor-pointer shadow-2xs">
                                            <span class="w-3 h-3 rounded-full bg-[#050505] border border-blue-400"></span>
                                            <span>Deep Obsidian</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TOMBOL SIMPAN PENGATURAN BACKGROUND & BOTTOM BAR -->
                            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                                <button type="submit" class="w-full sm:w-auto px-6 py-3.5 rounded-2xl bg-gradient-to-r from-[#0b8478] to-[#075f56] hover:from-[#097368] hover:to-[#054a43] text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                    <i class="fas fa-save text-base"></i>
                                    <span>Simpan Pengaturan Home & Bottom Bar</span>
                                </button>
                            </div>

                        </div>

                    </form>

                    <!-- KARTU KOLEKSI BACKGROUND TERSIMPAN -->
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
                                            <a href="admin-brosur-settings.php?action=delete_koleksi&id=<?= $kb['id'] ?>&tab=bg" onclick="return confirm('Hapus background ini dari koleksi tersimpan?');" title="Hapus dari koleksi" class="w-6 h-6 rounded-lg bg-rose-600/80 hover:bg-rose-600 text-white flex items-center justify-center text-[10px] transition shadow-xs">
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

                <!-- ========================================== -->
                <!-- KONTEN TAB 2: PENGATURAN KOLOM TULISAN     -->
                <!-- ========================================== -->
                <div id="tab-content-text" class="tab-pane hidden space-y-6">
                    
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
                            
                            <!-- Tombol Tambah & Hapus Semua Tulisan -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" onclick="clearAllTextRows()" id="btn-clear-all-text" class="px-3 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 font-bold text-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer <?= empty($text_items) ? 'hidden' : '' ?>" title="Hapus semua kolom tulisan">
                                    <i class="fas fa-trash-can text-xs"></i>
                                    <span>Hapus Semua</span>
                                </button>
                                <button type="button" onclick="addNewTextRow()" class="px-4 py-2.5 rounded-2xl bg-teal-50 hover:bg-teal-100 text-[#0b8478] border border-teal-200/80 font-black text-xs transition flex items-center gap-2 active:scale-95 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>+ Tambah Kolom Tulisan</span>
                                </button>
                            </div>
                        </div>

                        <!-- FORM UTAMA TULISAN DINAMIS -->
                        <form action="" method="POST" id="form-pengaturan-text" class="space-y-4">
                            <input type="hidden" name="action_type" value="save_text">
                            <input type="hidden" name="active_tab" value="text">
                            <input type="hidden" name="active_frame" value="<?= $active_frame ?>" class="input-active-frame">
                            <input type="hidden" name="custom_text_items_json" id="input-text-items-json" value="">

                            <!-- DAFTAR BARIS KOLOM TULISAN (RINGKAS & SIMPEL 1 BARIS PER ITEM) -->
                            <div id="text-rows-container" class="space-y-3">
                                <!-- Diisi secara dinamis oleh Javascript renderRows() -->
                            </div>

                            <!-- PETUNJUK RINGKAS -->
                            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-200/70 text-amber-900 text-[11px] flex items-center gap-2.5">
                                <i class="fas fa-arrows-up-down-left-right text-amber-600 text-sm shrink-0"></i>
                                <span><strong>Tips:</strong> Setiap kolom tulisan dapat langsung <strong>diklik dan digeser (drag & drop)</strong> posisinya di layar simulasi HP sebelah kanan. Garis bantu tengah akan menyala otomatis saat presisi!</span>
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

                </div>

                <!-- ========================================== -->
                <!-- KONTEN TAB 3: PENGATURAN SISIPKAN GAMBAR   -->
                <!-- ========================================== -->
                <div id="tab-content-image" class="tab-pane hidden space-y-6">
                    
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-5">
                        
                        <!-- Header Kartu Gambar -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shadow-2xs">
                                    <i class="fas fa-image"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-black text-base sm:text-lg text-slate-900">Sisipkan Gambar / Foto Frame</h2>
                                        <span id="img-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-black bg-sky-100 text-sky-800">
                                            <?= count($image_items) ?> Gambar
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">Bentuk bingkai (kotak, bulat, kubah, dll), garis tepi, bayangan, rotasi miring & drag bebas</p>
                                </div>
                            </div>
                            
                            <!-- Tombol Tambah & Hapus Semua Gambar -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" onclick="clearAllImageRows()" id="btn-clear-all-images" class="px-3 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 font-bold text-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer <?= empty($image_items) ? 'hidden' : '' ?>" title="Hapus semua gambar yang disisipkan">
                                    <i class="fas fa-trash-can text-xs"></i>
                                    <span>Hapus Semua</span>
                                </button>
                                <button type="button" onclick="addNewImageRow()" class="px-4 py-2.5 rounded-2xl bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200/80 font-black text-xs transition flex items-center gap-2 active:scale-95 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>+ Sisipkan Gambar</span>
                                </button>
                            </div>
                        </div>

                        <!-- FORM UTAMA GAMBAR SISIPAN -->
                        <form action="" method="POST" id="form-pengaturan-images" class="space-y-4">
                            <input type="hidden" name="action_type" value="save_images">
                            <input type="hidden" name="active_tab" value="image">
                            <input type="hidden" name="active_frame" value="<?= $active_frame ?>" class="input-active-frame">
                            <input type="hidden" name="custom_image_items_json" id="input-image-items-json" value="">

                            <!-- DAFTAR BARIS GAMBAR SISIPAN (RINGKAS & LENGKAP) -->
                            <div id="image-rows-container" class="space-y-3">
                                <!-- Diisi secara dinamis oleh Javascript renderImageRows() -->
                            </div>

                            <!-- TOMBOL AKSI BAWAH GAMBAR -->
                            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <button type="button" onclick="addNewImageRow()" class="w-full sm:w-auto px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                                    <i class="fas fa-plus text-xs text-sky-600"></i>
                                    <span>Tambah Gambar Lain</span>
                                </button>

                                <button type="button" onclick="saveAllImageItems()" class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-gradient-to-r from-sky-600 to-teal-700 hover:from-sky-700 hover:to-teal-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                    <i class="fas fa-save text-base"></i>
                                    <span>Simpan Semua Gambar</span>
                                </button>
                            </div>

                        </form>
                    </div>

                </div>

                <!-- ========================================== -->
                <!-- KONTEN TAB 4: PENGATURAN SISIPKAN VIDEO   -->
                <!-- ========================================== -->
                <div id="tab-content-video" class="tab-pane hidden space-y-6">
                    
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-5">
                        
                        <!-- Header Kartu Video -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shadow-2xs">
                                    <i class="fas fa-video"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-black text-base sm:text-lg text-slate-900">Sisipkan Video Frame Player</h2>
                                        <span id="vid-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-black bg-rose-100 text-rose-800">
                                            <?= count($video_items) ?> Video
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">Bisa upload file MP4/WebM atau pasang link YouTube / Shorts / Direct Video dengan bingkai dan drag bebas</p>
                                </div>
                            </div>
                            
                            <!-- Tombol Tambah & Hapus Semua Video -->
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" onclick="clearAllVideoRows()" id="btn-clear-all-videos" class="px-3 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 font-bold text-xs transition flex items-center gap-1.5 active:scale-95 cursor-pointer <?= empty($video_items) ? 'hidden' : '' ?>" title="Hapus semua video yang disisipkan">
                                    <i class="fas fa-trash-can text-xs"></i>
                                    <span>Hapus Semua</span>
                                </button>
                                <button type="button" onclick="addNewVideoRow()" class="px-4 py-2.5 rounded-2xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 font-black text-xs transition flex items-center gap-2 active:scale-95 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>+ Sisipkan Video</span>
                                </button>
                            </div>
                        </div>

                        <!-- FORM UTAMA VIDEO SISIPAN -->
                        <form action="" method="POST" id="form-pengaturan-videos" class="space-y-4">
                            <input type="hidden" name="action_type" value="save_videos">
                            <input type="hidden" name="active_tab" value="video">
                            <input type="hidden" name="active_frame" value="<?= $active_frame ?>" class="input-active-frame">
                            <input type="hidden" name="custom_video_items_json" id="input-video-items-json" value="">

                            <!-- DAFTAR BARIS VIDEO SISIPAN (RINGKAS & LENGKAP) -->
                            <div id="video-rows-container" class="space-y-3">
                                <!-- Diisi secara dinamis oleh Javascript renderVideoRows() -->
                            </div>

                            <!-- TOMBOL AKSI BAWAH VIDEO -->
                            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <button type="button" onclick="addNewVideoRow()" class="w-full sm:w-auto px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                                    <i class="fas fa-plus text-xs text-rose-600"></i>
                                    <span>Tambah Video Lain</span>
                                </button>

                                <button type="button" onclick="saveAllVideoItems()" class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-gradient-to-r from-rose-600 via-rose-700 to-amber-700 hover:from-rose-700 hover:to-amber-800 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                    <i class="fas fa-save text-base"></i>
                                    <span>Simpan Semua Video</span>
                                </button>
                            </div>

                        </form>
                    </div>

                </div>

                <!-- ========================================== -->
                <!-- KONTEN TAB 5: PENGATURAN SISIPKAN TOMBOL   -->
                <!-- ========================================== -->
                <div id="tab-content-button" class="tab-pane hidden space-y-6">
                    
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm space-y-5">
                        
                        <!-- Header Kartu Tombol -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shadow-2xs">
                                    <i class="fas fa-hand-pointer"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="font-black text-base sm:text-lg text-slate-900">Sisipkan Tombol Aksi (Call To Action)</h2>
                                        <span id="btn-count-badge" class="px-2 py-0.5 rounded-full text-[11px] font-black bg-amber-100 text-amber-800">
                                            <?= count($button_items) ?> Tombol
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500">Atur bentuk (Bulat, Persegi Panjang, Ujung Tumpul), warna, teks, icon (WA, IG, YouTube, Web), dan link tujuan</p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="addNewButtonRow()" class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-black text-xs shadow-sm transition flex items-center gap-1.5 transform active:scale-95 cursor-pointer">
                                    <i class="fas fa-plus text-xs"></i>
                                    <span>Tambah Tombol</span>
                                </button>
                                
                                <button type="button" id="btn-clear-all-buttons" onclick="clearAllButtonRows()" class="<?= empty($button_items) ? 'hidden' : '' ?> px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200/80 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer" title="Hapus semua tombol">
                                    <i class="fas fa-trash-can text-xs"></i>
                                    <span class="hidden sm:inline">Hapus Semua</span>
                                </button>
                            </div>
                        </div>

                        <!-- Form Pengaturan Tombol Aksi -->
                        <form action="" method="POST" id="form-pengaturan-button" class="space-y-4">
                            <input type="hidden" name="action_type" value="save_buttons">
                            <input type="hidden" name="active_tab" value="button">
                            <input type="hidden" name="active_frame" value="<?= $active_frame ?>" class="input-active-frame">
                            <input type="hidden" name="custom_button_items_json" id="input-button-items-json" value="">

                            <!-- DAFTAR BARIS TOMBOL (RINGKAS & LENGKAP) -->
                            <div id="button-rows-container" class="space-y-3">
                                <!-- Diisi secara dinamis oleh JavaScript renderButtonRows() -->
                            </div>

                            <!-- TOMBOL AKSI BAWAH TOMBOL -->
                            <div class="pt-3 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                                <button type="button" onclick="addNewButtonRow()" class="w-full sm:w-auto px-4 py-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                                    <i class="fas fa-plus text-xs text-amber-600"></i>
                                    <span>Tambah Tombol Lain</span>
                                </button>

                                <button type="button" onclick="saveAllButtonItems()" class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-black text-xs sm:text-sm shadow-md hover:shadow-lg transition flex items-center justify-center gap-2 transform active:scale-95 cursor-pointer">
                                    <i class="fas fa-save text-base"></i>
                                    <span>Simpan Semua Tombol</span>
                                </button>
                            </div>

                        </form>
                    </div>

                </div>

                </div>
                <!-- AKHIR MASTER FRAME: HOME -->

            </div>

            <!-- PANEL KANAN: LAYAR SIMULASI INTERAKTIF (STICKY DI KANAN LAYAR PC) -->
            <div class="lg:col-span-5 xl:col-span-5 lg:sticky lg:top-6 self-start flex flex-col items-center lg:items-end">
                <div class="w-full max-w-[340px] flex flex-col items-center">
                    
                    <!-- TOOLBAR ATAS SIMULASI: TOGGLE GARIS BANTU PRESISI -->
                    <div class="w-full flex items-center justify-between mb-2.5 px-2">
                        <span class="text-[11px] font-black text-slate-700 flex items-center gap-1.5">
                            <i class="fas fa-mobile-screen text-teal-600"></i> Layar Simulasi HP
                        </span>
                        
                        <div class="flex items-center gap-1.5">
                            <!-- Toggle Garis Bantu (Guidelines) -->
                            <button type="button" id="btn-toggle-guides" onclick="togglePersistentGuides()" class="px-2.5 py-1 rounded-xl text-[10px] font-black transition flex items-center gap-1.5 bg-sky-100 text-sky-800 border border-sky-200 hover:bg-sky-200 active:scale-95 shadow-2xs cursor-pointer" title="Nyalakan/Matikan Garis Bantu Presisi">
                                <i class="fas fa-ruler-combined text-sky-600"></i>
                                <span id="label-guides-state">Garis Bantu: ON</span>
                            </button>
                        </div>
                    </div>

                    <!-- KANVAS SIMULASI BACKGROUND PORTRAIT MURNI DENGAN GAMBAR, VIDEO, TOMBOL & TULISAN DRAGGABLE -->
                    <div class="bg-simulation-canvas relative w-full overflow-hidden transition-all duration-300" id="phone-container">
                        
                        <!-- GAMBAR BACKGROUND PORTRAIT -->
                        <div id="preview-screen-cover" class="absolute inset-0 w-full h-full bg-cover bg-center transition-all duration-300" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80') ?>');">
                            
                            <!-- LAPISAN OVERLAY DINAMIS -->
                            <div id="preview-cover-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>;"></div>

                            <!-- GARIS BANTU PRESISI (GUIDELINES & SMART SNAP ASSIST) -->
                            <div id="sim-guidelines-layer" class="absolute inset-0 pointer-events-none z-50">
                                <!-- Garis Bantu Tengah X (Vertikal Center) -->
                                <div id="snap-guide-x" class="snap-guide-line-x">
                                    <span class="absolute top-2 left-1 bg-sky-500 text-white text-[8px] font-mono font-black px-1 rounded shadow-xs">Center X 50%</span>
                                </div>
                                <!-- Garis Bantu Tengah Y (Horizontal Center) -->
                                <div id="snap-guide-y" class="snap-guide-line-y">
                                    <span class="absolute left-2 top-1 bg-sky-500 text-white text-[8px] font-mono font-black px-1 rounded shadow-xs">Middle Y 50%</span>
                                </div>
                                
                                <!-- Persistent Grid Lines (Rule-of-Thirds) -->
                                <div class="persistent-grid-line grid-guide-line top-0 bottom-0 left-1/3 w-[1px] border-r border-dashed border-sky-400/30"></div>
                                <div class="persistent-grid-line grid-guide-line top-0 bottom-0 left-2/3 w-[1px] border-r border-dashed border-sky-400/30"></div>
                                <div class="persistent-grid-line grid-guide-line left-0 right-0 top-1/3 h-[1px] border-b border-dashed border-sky-400/30"></div>
                                <div class="persistent-grid-line grid-guide-line left-0 right-0 top-2/3 h-[1px] border-b border-dashed border-sky-400/30"></div>
                            </div>

                            <!-- CONTAINER LAYER GAMBAR SISIPAN DI LAYAR SIMULASI -->
                            <div id="sim-image-layers-container" class="absolute inset-0 pointer-events-none z-20">
                                <!-- Diisi secara dinamis oleh JavaScript renderSimImageLayers() -->
                            </div>

                            <!-- CONTAINER LAYER VIDEO SISIPAN DI LAYAR SIMULASI -->
                            <div id="sim-video-layers-container" class="absolute inset-0 pointer-events-none z-25">
                                <!-- Diisi secara dinamis oleh JavaScript renderSimVideoLayers() -->
                            </div>

                            <!-- CONTAINER LAYER TULISAN DI LAYAR SIMULASI -->
                            <div id="sim-text-layers-container" class="absolute inset-0 pointer-events-none z-30">
                                <!-- Diisi secara dinamis oleh JavaScript renderSimLayers() -->
                            </div>

                            <!-- CONTAINER LAYER TOMBOL DI LAYAR SIMULASI -->
                            <div id="sim-button-layers-container" class="absolute inset-0 pointer-events-none z-35">
                                <!-- Diisi secara dinamis oleh JavaScript renderSimButtonLayers() -->
                            </div>

                            <!-- DOCKED BOTTOM NAVIGATION BAR DI LAYAR SIMULASI (MENU HOME & PRESTASI) -->
                            <div id="sim-bottom-bar" class="absolute bottom-0 left-0 right-0 z-40 pt-2.5 pb-2 px-3 border-t border-white/10 backdrop-blur-md flex items-center justify-around shadow-[0_-8px_20px_rgba(0,0,0,0.4)] transition-all duration-200" style="background-color: <?= htmlspecialchars($cfg['bottom_bar_bg_color'] ?? '#022d27') ?>; color: <?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>;">
                                <!-- Menu 1: Home -->
                                <button type="button" onclick="switchFrame('home')" id="sim-menu-home-btn" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 group cursor-pointer py-0.5" style="color: <?= htmlspecialchars($cfg['bottom_bar_active_color'] ?? '#fbbf24') ?>;" title="Menu Home (Frame Home)">
                                    <i id="sim-menu-home-icon" class="fas fa-house text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                                    <span id="sim-menu-home-label" class="text-[9.5px] font-bold tracking-wider leading-none">Home</span>
                                </button>

                                <!-- Menu 2: Prestasi -->
                                <button type="button" onclick="switchFrame('prestasi')" id="sim-menu-prestasi-btn" class="flex flex-col items-center justify-center text-center transform transition active:scale-95 group cursor-pointer py-0.5 opacity-70 hover:opacity-100" style="color: <?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>;" title="Menu Prestasi (Frame Prestasi)">
                                    <i id="sim-menu-prestasi-icon" class="fas fa-trophy text-lg mb-0.5 transition-transform group-hover:scale-115"></i>
                                    <span id="sim-menu-prestasi-label" class="text-[9.5px] font-bold tracking-wider leading-none">Prestasi</span>
                                </button>
                            </div>

                        </div>

                        <!-- HIDDEN BODY CONTAINER FOR SCRIPT COMPATIBILITY -->
                        <div id="preview-screen-body" class="hidden absolute inset-0 w-full h-full bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>');">
                            <div id="preview-body-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19]" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.88 ?>;"></div>
                        </div>

                    </div>

                    <!-- Petunjuk Interaktif Drag & Snap -->
                    <p class="text-[10px] text-slate-500 mt-2.5 text-center flex items-center gap-1.5">
                        <i class="fas fa-magnet text-sky-500"></i>
                        <span>Smart Snap: Garis bantu biru menyala otomatis saat digeser tepat ke tengah (50%)</span>
                    </p>

                </div>
            </div>

        </div>

    </main>

    <!-- SCRIPT INTERAKTIF PENGATURAN GAMBAR, TULISAN & SMART DRAGGABLE GUIDELINES -->
    <script>
        // ==========================================
        // 1. STATE & KONFIGURASI MULTI-FRAME (HOME & PRESTASI)
        // ==========================================
        
        let framesData = {
            home: {
                id: 'home',
                name: 'Home',
                title: 'Frame: Home',
                subtitle: 'Frame ini mengatur tampilan layar pertama saat calon wali santri membuka brosur digital (Menu <strong>Home</strong> pada Bottom Navigation Bar).',
                badge: 'Cover / Halaman Depan',
                menuPill: 'Menu #1 di Bottom Bar',
                icon: 'fa-house',
                iconGradient: 'from-emerald-500 to-[#0b8478]',
                badgeClass: 'bg-emerald-100 text-emerald-900 border-emerald-300',
                bgUrl: <?= json_encode($cfg['cover_bg_url'] ?? '') ?>,
                bgOpacity: <?= (float)($cfg['cover_overlay_opacity'] ?? 0.88) ?>,
                texts: <?= json_encode($text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($button_items, JSON_UNESCAPED_UNICODE) ?>
            },
            prestasi: {
                id: 'prestasi',
                name: 'Prestasi',
                title: 'Frame: Prestasi',
                subtitle: 'Frame ini mengatur tampilan galeri pencapaian, piala, medali & prestasi santri (Menu <strong>Prestasi</strong> pada Bottom Navigation Bar).',
                badge: 'Menu Prestasi Brosur',
                menuPill: 'Menu #2 di Bottom Bar',
                icon: 'fa-trophy',
                iconGradient: 'from-amber-500 to-amber-600',
                badgeClass: 'bg-amber-100 text-amber-900 border-amber-300',
                bgUrl: <?= json_encode($prestasi_bg_url) ?>,
                bgOpacity: <?= (float)$prestasi_overlay_opacity ?>,
                texts: <?= json_encode($prestasi_text_items, JSON_UNESCAPED_UNICODE) ?>,
                images: <?= json_encode($prestasi_image_items, JSON_UNESCAPED_UNICODE) ?>,
                videos: <?= json_encode($prestasi_video_items, JSON_UNESCAPED_UNICODE) ?>,
                buttons: <?= json_encode($prestasi_button_items, JSON_UNESCAPED_UNICODE) ?>
            }
        };

        let currentActiveFrame = '<?= $active_frame ?>';
        let currentActiveTab   = '<?= $current_tab ?>';

        // Pointer item aktif sesuai frame yang sedang dibuka
        let textItems   = framesData[currentActiveFrame].texts;
        let imageItems  = framesData[currentActiveFrame].images;
        let videoItems  = framesData[currentActiveFrame].videos;
        let buttonItems = framesData[currentActiveFrame].buttons;

        function switchFrame(frame) {
            if (!['home', 'prestasi'].includes(frame)) frame = 'home';
            currentActiveFrame = frame;

            const data = framesData[frame];
            textItems   = data.texts;
            imageItems  = data.images;
            videoItems  = data.videos;
            buttonItems = data.buttons;

            // 1. Update Tombol Switcher Frame
            const btnHome = document.getElementById('frame-btn-home');
            const btnPrestasi = document.getElementById('frame-btn-prestasi');
            if (btnHome && btnPrestasi) {
                if (frame === 'home') {
                    btnHome.className = 'flex-1 py-2.5 px-3 rounded-2xl font-black text-xs sm:text-sm transition flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-700 text-white shadow-md ring-2 ring-emerald-400 cursor-pointer';
                    btnPrestasi.className = 'flex-1 py-2.5 px-3 rounded-2xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 text-slate-300 hover:text-white hover:bg-white/10 cursor-pointer';
                } else {
                    btnPrestasi.className = 'flex-1 py-2.5 px-3 rounded-2xl font-black text-xs sm:text-sm transition flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md ring-2 ring-amber-400 cursor-pointer';
                    btnHome.className = 'flex-1 py-2.5 px-3 rounded-2xl font-bold text-xs sm:text-sm transition flex items-center justify-center gap-2 text-slate-300 hover:text-white hover:bg-white/10 cursor-pointer';
                }
            }

            // 2. Update Header Info Frame
            const headerTitle    = document.getElementById('frame-header-title');
            const headerBadge    = document.getElementById('frame-header-badge');
            const headerDesc     = document.getElementById('frame-header-desc');
            const headerIcon     = document.getElementById('frame-header-icon');
            const headerIconBox  = document.getElementById('frame-header-icon-box');
            const headerMenuText = document.getElementById('frame-header-menu-text');

            if (headerTitle) headerTitle.innerText = data.title;
            if (headerBadge) {
                headerBadge.innerText = data.badge;
                headerBadge.className = `px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider ${data.badgeClass}`;
            }
            if (headerDesc) headerDesc.innerHTML = data.subtitle;
            if (headerIcon) headerIcon.className = `fas ${data.icon}`;
            if (headerIconBox) headerIconBox.className = `w-12 h-12 rounded-2xl bg-gradient-to-br ${data.iconGradient} text-white flex items-center justify-center text-xl shadow-md shrink-0`;
            if (headerMenuText) headerMenuText.innerText = data.menuPill;

            // 3. Update Hidden Inputs active_frame di semua Form
            document.querySelectorAll('.input-active-frame').forEach(inp => inp.value = frame);

            // 4. Update Input Tab Background
            const bgUrlInput = document.getElementById('input-bg-url');
            const bgOpacityInput = document.getElementById('input-bg-opacity');
            const bgOpacityLabel = document.getElementById('val-bg-opacity');
            const thumbBgBox = document.getElementById('thumb-bg-box');
            const thumbBgOverlay = document.getElementById('thumb-bg-overlay');

            if (bgUrlInput) bgUrlInput.value = data.bgUrl || '';
            if (bgOpacityInput) bgOpacityInput.value = data.bgOpacity;
            if (bgOpacityLabel) bgOpacityLabel.innerText = Math.round(data.bgOpacity * 100) + '%';
            if (thumbBgBox && data.bgUrl) thumbBgBox.style.backgroundImage = `url('${data.bgUrl}')`;
            if (thumbBgOverlay) thumbBgOverlay.style.opacity = data.bgOpacity;

            // 5. Update Background Canvas Simulasi
            updateCanvasBackground(data.bgUrl, data.bgOpacity);

            // 6. Update Highlight Menu Aktif di Bottom Bar Simulasi
            const navHome = document.getElementById('sim-menu-home-btn');
            const navPrestasi = document.getElementById('sim-menu-prestasi-btn');
            const activeColor = '<?= htmlspecialchars($cfg['bottom_bar_active_color'] ?? '#fbbf24') ?>';
            const normalColor = '<?= htmlspecialchars($cfg['bottom_bar_text_color'] ?? '#ffffff') ?>';

            if (navHome && navPrestasi) {
                if (frame === 'home') {
                    navHome.style.color = activeColor;
                    navHome.classList.remove('opacity-70');
                    navPrestasi.style.color = normalColor;
                    navPrestasi.classList.add('opacity-70');
                } else {
                    navPrestasi.style.color = activeColor;
                    navPrestasi.classList.remove('opacity-70');
                    navHome.style.color = normalColor;
                    navHome.classList.add('opacity-70');
                }
            }

            // 7. Re-render Semua Tab Rows & Layar Simulasi
            renderRows();
            renderImageRows();
            renderVideoRows();
            renderButtonRows();
            renderSimLayers();
            renderSimImageLayers();
            renderSimVideoLayers();
            renderSimButtonLayers();
        }

        function updateCanvasBackground(url, opacity) {
            const screenCover = document.getElementById('preview-screen-cover');
            const overlay = document.getElementById('preview-cover-overlay');
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
        }

        function switchTab(tab) {
            if (!['bg', 'text', 'image', 'video', 'button'].includes(tab)) tab = 'bg';
            currentActiveTab = tab;

            const tabs = ['bg', 'text', 'image', 'video', 'button'];
            tabs.forEach(t => {
                const btn = document.getElementById(`tab-btn-${t}`);
                const pane = document.getElementById(`tab-content-${t}`);
                if (btn) {
                    if (t === tab) {
                        btn.className = 'tab-nav-btn flex-1 min-w-[85px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-black text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 bg-gradient-to-r from-[#0b8478] to-[#075f56] text-white shadow-md cursor-pointer';
                    } else {
                        btn.className = 'tab-nav-btn flex-1 min-w-[85px] py-2.5 sm:py-3 px-2 sm:px-3 rounded-xl font-bold text-xs sm:text-sm flex items-center justify-center gap-1.5 sm:gap-2 transition-all duration-200 text-slate-600 hover:text-slate-900 hover:bg-slate-100 cursor-pointer';
                    }
                }
                if (pane) {
                    if (t === tab) {
                        pane.classList.remove('hidden');
                    } else {
                        pane.classList.add('hidden');
                    }
                }
            });

            document.querySelectorAll('input[name="active_tab"]').forEach(inp => inp.value = tab);

            if (history.replaceState) {
                history.replaceState(null, null, '#' + tab);
            }
        }

        // Persistent Guide Lines State
        let showPersistentGuides = true;

        // Pilihan Font Tersedia
        const availableFonts = [
            { id: 'Marlin Condensed',  name: 'Marlin Condensed (Brand Nasional)' },
            { id: 'Plus Jakarta Sans', name: 'Jakarta (Modern)' },
            { id: 'Amiri',             name: 'Amiri (Arab)' },
            { id: 'Cinzel',            name: 'Cinzel (Royal)' },
            { id: 'Playfair Display',  name: 'Playfair (Elegan)' },
            { id: 'Poppins',           name: 'Poppins (Bold)' },
            { id: 'Inter',             name: 'Inter (Clean)' },
            { id: 'Outfit',            name: 'Outfit (Trendy)' },
            { id: 'Oswald',            name: 'Oswald (Condensed Bold)' }
        ];

        // Pilihan Bentuk Bingkai Gambar & Video
        const frameShapes = [
            { id: 'persegi_panjang_wide',  name: 'Persegi Panjang 16:9 (Cinema/Video)', icon: 'fa-tv' },
            { id: 'persegi_panjang',       name: 'Persegi Panjang 4:3', icon: 'fa-rectangle-ad' },
            { id: 'rounded',               name: 'Sudut Lengkung (Squircle)', icon: 'fa-square' },
            { id: 'bulat',                 name: 'Bulat Lingkaran (Circle)', icon: 'fa-circle' },
            { id: 'kotak',                 name: 'Kotak Persegi 1:1', icon: 'fa-square-full' },
            { id: 'oval',                  name: 'Oval / Elips', icon: 'fa-egg' },
            { id: 'kubah',                 name: 'Kubah Lengkung Islami', icon: 'fa-mosque' },
            { id: 'perisai',               name: 'Perisai / Shield', icon: 'fa-shield-halved' },
            { id: 'bintang',               name: 'Bintang / Octagon Badge', icon: 'fa-certificate' }
        ];

        // Pilihan Bentuk Tombol Aksi (Sesuai Permintaan: Bulat, Persegi Panjang, Ujung Tumpul / Pill, Sudut Lengkung)
        const buttonShapes = [
            { id: 'rounded_pill',    name: 'Persegi Panjang Ujung Tumpul (Pill / Kapsul)', icon: 'fa-capsules' },
            { id: 'persegipanjang',  name: 'Persegi Panjang (Kotak Tegas)', icon: 'fa-rectangle-ad' },
            { id: 'bulat',           name: 'Bulat Lingkaran (Circle / Icon Button)', icon: 'fa-circle' },
            { id: 'rounded',         name: 'Sudut Lengkung (Squircle)', icon: 'fa-square' }
        ];

        // Pilihan Icon Tombol Populer
        const buttonIcons = [
            { id: 'fab fa-whatsapp',       name: 'WhatsApp', icon: 'fab fa-whatsapp' },
            { id: 'fas fa-globe',          name: 'Website / Browser', icon: 'fas fa-globe' },
            { id: 'fab fa-instagram',      name: 'Instagram', icon: 'fab fa-instagram' },
            { id: 'fab fa-tiktok',         name: 'TikTok', icon: 'fab fa-tiktok' },
            { id: 'fab fa-youtube',        name: 'YouTube', icon: 'fab fa-youtube' },
            { id: 'fas fa-phone',          name: 'Telepon / Call Center', icon: 'fas fa-phone' },
            { id: 'fas fa-paper-plane',    name: 'Pendaftaran / Form PSB', icon: 'fas fa-paper-plane' },
            { id: 'fas fa-download',       name: 'Download E-Brosur / PDF', icon: 'fas fa-download' },
            { id: 'fas fa-location-dot',   name: 'Google Maps / Lokasi', icon: 'fas fa-location-dot' },
            { id: 'fas fa-comment-dots',   name: 'Chat / Diskusi', icon: 'fas fa-comment-dots' },
            { id: 'fas fa-link',           name: 'Link Tautan Lain', icon: 'fas fa-link' },
            { id: 'none',                  name: 'Tanpa Icon (Hanya Teks)', icon: 'fas fa-ban' }
        ];

        // Pilihan Efek Bayangan & Glow Tombol
        const buttonShadows = [
            { id: 'none',        name: 'Tanpa Bayangan (Flat)', icon: 'fa-ban' },
            { id: 'soft',        name: 'Bayangan Halus (Soft Natural)', icon: 'fa-cloud' },
            { id: 'medium',      name: 'Bayangan Sedang (Medium 3D)', icon: 'fa-cubes' },
            { id: 'deep',        name: 'Bayangan Tebal (Deep 3D)', icon: 'fa-cube' },
            { id: 'floating',    name: 'Bayangan Melayang (Floating)', icon: 'fa-paper-plane' },
            { id: 'glow_wa',     name: 'Glow WhatsApp Green', icon: 'fab fa-whatsapp' },
            { id: 'glow_gold',   name: 'Glow Gold Luxury (Emas)', icon: 'fa-crown' },
            { id: 'glow_teal',   name: 'Glow Emerald Islami (Teal)', icon: 'fa-mosque' },
            { id: 'glow_rose',   name: 'Glow Neon Rose / Pink', icon: 'fa-heart' },
            { id: 'glow_sky',    name: 'Glow Sky Blue (Biru Neon)', icon: 'fa-gem' }
        ];

        // Helper YouTube / Video URL Detection
        function parseVideoSource(url, options = {}) {
            if (!url) return { type: 'empty', url: '' };
            url = url.trim();

            const ytMatch = url.match(/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=|shorts\/))([\w-]{11})/);
            if (ytMatch && ytMatch[1]) {
                const vidId = ytMatch[1];
                const auto = options.autoplay ? 1 : 0;
                const mute = options.muted ? 1 : 1; // youtube butuh mute untuk autoplay
                const loop = options.loop ? `1&playlist=${vidId}` : '0';
                const controls = options.controls ? 1 : 0;
                const embedUrl = `https://www.youtube.com/embed/${vidId}?autoplay=${auto}&mute=${mute}&loop=${loop}&controls=${controls}&playsinline=1&enablejsapi=1`;
                return { type: 'youtube', embedUrl, vidId };
            }

            return { type: 'direct', url: url };
        }

        // Format Helper HTML Generator
        function getFormatHtml(content, format) {
            let text = (content || '').trim();
            if (!text) text = 'Villa Quran Indonesia';

            const safeText = text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;").replace(/\n/g, "<br>");
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

        // Font Family Helper
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

        // ==========================================
        // 2. LOGIKA GAMBAR SISIPAN (MULTI-LAYER IMAGES)
        // ==========================================

        function renderImageRows() {
            const container = document.getElementById('image-rows-container');
            const badge = document.getElementById('img-count-badge');
            const tabBadge = document.getElementById('tab-badge-image');
            const clearBtn = document.getElementById('btn-clear-all-images');

            if (badge) badge.innerText = `${imageItems.length} Gambar`;
            if (tabBadge) tabBadge.innerText = imageItems.length;
            if (clearBtn) {
                if (imageItems.length > 0) clearBtn.classList.remove('hidden');
                else clearBtn.classList.add('hidden');
            }
            if (!container) return;

            container.innerHTML = '';

            if (imageItems.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <i class="fas fa-image text-3xl text-slate-300 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Belum ada gambar yang disisipkan</p>
                        <p class="text-[11px] text-slate-500 mt-0.5 mb-3">Klik tombol <strong>+ Sisipkan Gambar</strong> untuk menambahkan foto/logo baru dengan bingkai.</p>
                        <button type="button" onclick="addNewImageRow()" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-black text-xs transition inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fas fa-plus text-xs"></i>
                            <span>Sisipkan Gambar Sekarang</span>
                        </button>
                    </div>
                `;
                syncImageJsonInput();
                return;
            }

            imageItems.forEach((img, index) => {
                const row = document.createElement('div');
                row.className = 'item-row-strip bg-white border border-slate-200/90 hover:border-sky-500 rounded-2xl p-2.5 sm:p-3 shadow-xs space-y-2';
                row.id = `img-row-item-${img.id}`;

                let shapeOptionsHtml = '';
                frameShapes.forEach(s => {
                    const sel = (img.shape === s.id) ? 'selected' : '';
                    shapeOptionsHtml += `<option value="${s.id}" ${sel}>${s.name}</option>`;
                });

                row.innerHTML = `
                    <!-- BARIS UTAMA (1 BARIS RINGKAS) -->
                    <div class="flex flex-wrap items-center gap-2">
                        
                        <!-- Nomor Gambar & Thumbnail Kecil -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <div class="w-6 h-6 rounded-lg bg-sky-50 text-sky-800 font-black text-[11px] flex items-center justify-center border border-sky-100 shadow-2xs" title="Gambar #${index + 1}">
                                ${index + 1}
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-slate-900 overflow-hidden border border-slate-200 shrink-0">
                                <img src="${escapeHtml(img.url)}" id="thumb-row-${img.id}" class="w-full h-full object-cover">
                            </div>
                        </div>

                        <!-- Input URL Gambar -->
                        <div class="flex-1 min-w-[140px]">
                            <input type="text" value="${escapeHtml(img.url)}" oninput="updateImageField('${img.id}', 'url', this.value)" placeholder="Link URL Gambar (https://...)" class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:border-sky-500 focus:outline-none bg-slate-50/60 focus:bg-white transition">
                        </div>

                        <!-- Tombol Upload File Gambar -->
                        <div class="shrink-0">
                            <label class="cursor-pointer px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-sky-50 hover:text-sky-700 text-slate-700 border border-slate-200 text-xs font-bold transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                                <i class="fas fa-cloud-arrow-up text-sky-600 text-xs"></i>
                                <span class="hidden sm:inline text-[11px]">Upload</span>
                                <input type="file" accept="image/*" class="hidden" onchange="uploadLayerImage(this, '${img.id}')">
                            </label>
                        </div>

                        <!-- Pilihan Bentuk Bingkai (Shape) -->
                        <div class="shrink-0 max-w-[130px]">
                            <select onchange="updateImageField('${img.id}', 'shape', this.value)" class="w-full px-2 py-1.5 rounded-xl border border-slate-200 text-[11px] font-bold bg-white focus:border-sky-500 focus:outline-none cursor-pointer truncate" title="Pilih Bentuk Bingkai">
                                ${shapeOptionsHtml}
                            </select>
                        </div>

                        <!-- Rotasi Ringkas (Deg) -->
                        <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl px-2 py-1 shrink-0 shadow-2xs" title="Rotasi Kemiringan (-180° s/d 180°)">
                            <i class="fas fa-rotate text-sky-600 text-[10px]"></i>
                            <input type="number" min="-180" max="180" value="${img.rotation || 0}" oninput="updateImageField('${img.id}', 'rotation', parseFloat(this.value) || 0)" class="w-9 text-xs font-black text-sky-800 text-center focus:outline-none">
                            <span class="text-[10px] text-slate-400 font-mono">°</span>
                        </div>

                        <!-- TOMBOL DUPLIKASI, SETTING DETAIL & HAPUS -->
                        <div class="flex items-center gap-1 shrink-0 ml-auto sm:ml-0">
                            <!-- Duplikasi -->
                            <button type="button" onclick="duplicateImageRow('${img.id}')" title="Duplikasi Gambar Ini" class="w-7 h-7 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200/80 flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-copy text-[11px]"></i>
                            </button>

                            <!-- Toggle Setting Lengkap (Garis Tepi, Bayangan, Ukuran) -->
                            <button type="button" onclick="toggleImageDetails('${img.id}')" title="Pengaturan Bingkai, Garis Tepi & Bayangan" class="w-7 h-7 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 border border-sky-200 flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-sliders text-[11px]"></i>
                            </button>

                            <!-- Hapus Gambar -->
                            <button type="button" onclick="deleteImageRow('${img.id}')" title="Hapus Gambar Ini" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-trash-can text-[11px]"></i>
                            </button>
                        </div>

                    </div>

                    <!-- PANEL DETAIL BINGKAI, GARIS TEPI, BAYANGAN & ROTASI (EXPANDABLE) -->
                    <div id="img-details-${img.id}" class="hidden pt-2.5 mt-2 border-t border-slate-100 bg-slate-50/70 p-3.5 rounded-xl space-y-3">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            
                            <!-- KONTROL 1: GARIS TEPI (BORDER ON/OFF & COLOR) -->
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700 text-[11px] flex items-center gap-1.5">
                                        <input type="checkbox" ${img.border_enable ? 'checked' : ''} onchange="updateImageField('${img.id}', 'border_enable', this.checked ? 1 : 0)" class="rounded text-sky-600">
                                        <span>Garis Tepi (Border)</span>
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-mono">${img.border_width || 2}px</span>
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <input type="color" value="${img.border_color || '#ffffff'}" onchange="updateImageField('${img.id}', 'border_color', this.value)" class="w-6 h-6 rounded-lg border border-slate-200 cursor-pointer p-0.5" title="Warna Garis">
                                    <input type="range" min="1" max="12" step="1" value="${img.border_width || 2}" oninput="updateImageField('${img.id}', 'border_width', parseInt(this.value))" class="flex-1 accent-sky-600 cursor-pointer">
                                </div>
                            </div>

                            <!-- KONTROL 2: BAYANGAN (SHADOW EFFECT) -->
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 space-y-1.5">
                                <label class="block font-bold text-slate-700 text-[11px]">Efek Bayangan (Shadow):</label>
                                <select onchange="updateImageField('${img.id}', 'shadow_style', this.value)" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:outline-none">
                                    <option value="none" ${img.shadow_style === 'none' ? 'selected' : ''}>Tanpa Bayangan</option>
                                    <option value="soft" ${img.shadow_style === 'soft' ? 'selected' : ''}>Bayangan Halus (Soft)</option>
                                    <option value="medium" ${img.shadow_style === 'medium' ? 'selected' : ''}>Bayangan Sedang</option>
                                    <option value="deep" ${img.shadow_style === 'deep' ? 'selected' : ''}>Bayangan 3D Dalam</option>
                                    <option value="glow_gold" ${img.shadow_style === 'glow_gold' ? 'selected' : ''}>Glow Cahaya Emas</option>
                                    <option value="glow_teal" ${img.shadow_style === 'glow_teal' ? 'selected' : ''}>Glow Cahaya Emerald</option>
                                </select>
                            </div>

                            <!-- KONTROL 3: ROTASI & UKURAN LEBAR -->
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 space-y-1.5">
                                <div class="flex justify-between items-center text-[11px] font-bold text-slate-700">
                                    <span>Ukuran Lebar:</span>
                                    <span class="font-mono text-sky-800">${img.width || 50}%</span>
                                </div>
                                <input type="range" min="15" max="100" step="1" value="${img.width || 50}" oninput="updateImageField('${img.id}', 'width', parseInt(this.value))" class="w-full accent-sky-600 cursor-pointer">
                            </div>

                        </div>

                        <!-- PRESET ROTASI CEPAT -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10.5px] font-bold text-slate-500">Preset Rotasi:</span>
                                <button type="button" onclick="updateImageField('${img.id}', 'rotation', 0)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">0° (Tegak)</button>
                                <button type="button" onclick="updateImageField('${img.id}', 'rotation', -15)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">-15° (Miring Kiri)</button>
                                <button type="button" onclick="updateImageField('${img.id}', 'rotation', 15)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">+15° (Miring Kanan)</button>
                                <button type="button" onclick="updateImageField('${img.id}', 'rotation', 45)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">45° (Wajik)</button>
                            </div>

                            <button type="button" onclick="resetImageCenter('${img.id}')" class="text-[10px] text-sky-700 hover:underline font-bold">
                                <i class="fas fa-crosshairs mr-1"></i>Reset Posisi Tengah (50%)
                            </button>
                        </div>

                    </div>
                `;

                container.appendChild(row);
            });

            syncImageJsonInput();
        }

        function toggleImageDetails(id) {
            const el = document.getElementById(`img-details-${id}`);
            if (el) el.classList.toggle('hidden');
        }

        function updateImageField(id, field, value) {
            const img = imageItems.find(i => i.id === id);
            if (img) {
                img[field] = value;
                renderSimImageLayers();
                syncImageJsonInput();
            }
        }

        function resetImageCenter(id) {
            const img = imageItems.find(i => i.id === id);
            if (img) {
                img.posX = 50;
                img.posY = 50;
                renderSimImageLayers();
                syncImageJsonInput();
            }
        }

        function addNewImageRow() {
            const newIndex = imageItems.length + 1;
            const sampleUrls = [
                'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=600&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1542838132-92c53300491e?w=600&auto=format&fit=crop&q=80',
                'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=600&auto=format&fit=crop&q=80'
            ];
            const chosenUrl = sampleUrls[(newIndex - 1) % sampleUrls.length];

            const newImg = {
                id: 'img_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                url: chosenUrl,
                shape: newIndex === 1 ? 'rounded' : (newIndex === 2 ? 'bulat' : 'kubah'),
                border_enable: 1,
                border_width: 3,
                border_color: '#fbbf24',
                border_style: 'solid',
                shadow_style: 'medium',
                rotation: 0,
                posX: 50,
                posY: Math.min(80, 25 + ((newIndex - 1) * 20)),
                width: 50
            };

            imageItems.push(newImg);
            renderImageRows();
            renderSimImageLayers();

            setTimeout(() => {
                const el = document.getElementById(`img-row-item-${newImg.id}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }

        function duplicateImageRow(sourceId) {
            const source = imageItems.find(i => i.id === sourceId);
            if (!source) return;

            const cloned = JSON.parse(JSON.stringify(source));
            cloned.id = 'img_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            cloned.posY = Math.min(88, (source.posY || 50) + 10);
            cloned.posX = Math.min(88, (source.posX || 50) + 5);

            const sourceIndex = imageItems.findIndex(i => i.id === sourceId);
            if (sourceIndex >= 0) {
                imageItems.splice(sourceIndex + 1, 0, cloned);
            } else {
                imageItems.push(cloned);
            }

            renderImageRows();
            renderSimImageLayers();

            setTimeout(() => {
                const el = document.getElementById(`img-row-item-${cloned.id}`);
                if (el) {
                    el.classList.add('active-layer');
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => el.classList.remove('active-layer'), 1500);
                }
            }, 100);
        }

        function deleteImageRow(id) {
            if (confirm('Hapus gambar yang disisipkan ini?')) {
                imageItems = imageItems.filter(i => i.id !== id);
                renderImageRows();
                renderSimImageLayers();
            }
        }

        function clearAllImageRows() {
            if (imageItems.length === 0) return;
            if (confirm(`Yakin ingin menghapus semua (${imageItems.length}) gambar yang disisipkan?`)) {
                imageItems = [];
                renderImageRows();
                renderSimImageLayers();
            }
        }

        // Instant Upload Layer Image via AJAX
        function uploadLayerImage(input, imgId) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            // 1. Instant Live Preview via FileReader
            const reader = new FileReader();
            reader.onload = function(e) {
                const dataUrl = e.target.result;
                updateImageField(imgId, 'url', dataUrl);
                const thumb = document.getElementById(`thumb-row-${imgId}`);
                if (thumb) thumb.src = dataUrl;
            };
            reader.readAsDataURL(file);

            // 2. Upload async ke server
            const formData = new FormData();
            formData.append('ajax_image_file', file);

            fetch('admin-brosur-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.url) {
                    updateImageField(imgId, 'url', res.url);
                    const inp = document.querySelector(`#img-row-item-${imgId} input[type="text"]`);
                    if (inp) inp.value = res.url;
                }
            })
            .catch(err => {
                console.error('Upload layer error:', err);
            });
        }

        function syncImageJsonInput() {
            if (typeof framesData !== 'undefined' && framesData[currentActiveFrame]) {
                framesData[currentActiveFrame].images = imageItems;
            }
            const inp = document.getElementById('input-image-items-json');
            if (inp) {
                inp.value = JSON.stringify(imageItems);
            }
        }

        function saveAllImageItems() {
            syncImageJsonInput();
            document.getElementById('form-pengaturan-images').submit();
        }

        // ==========================================
        // 3. RENDER SIMULASI GAMBAR SISIPAN & SMART DRAG
        // ==========================================

        function renderSimImageLayers() {
            const container = document.getElementById('sim-image-layers-container');
            if (!container) return;

            container.innerHTML = '';

            imageItems.forEach((img, index) => {
                const box = document.createElement('div');
                box.id = `sim-img-box-${img.id}`;
                box.className = 'draggable-box absolute pointer-events-auto transition-shadow group/imgdrag';
                box.setAttribute('data-id', img.id);
                box.style.top = `${img.posY || 50}%`;
                box.style.left = `${img.posX || 50}%`;
                box.style.transform = `translate(-50%, -50%) rotate(${img.rotation || 0}deg)`;
                box.style.width = `${img.width || 50}%`;
                box.style.zIndex = 20 + index;

                // Frame styling
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
                    <!-- Border indikator saat hover / drag -->
                    <div class="absolute -inset-2 border-2 border-dashed border-sky-400 rounded-2xl pointer-events-none opacity-0 group-hover/imgdrag:opacity-100 transition-opacity flex items-start justify-between p-1 z-30" style="transform: rotate(0deg);">
                        <span class="bg-sky-500 text-white text-[8px] font-black px-1.5 py-0.5 rounded shadow-xs">
                            Img #${index + 1}
                        </span>
                        <div class="flex items-center gap-1 pointer-events-auto">
                            <span class="bg-slate-950/90 text-sky-300 text-[8px] font-bold px-1.5 py-0.5 rounded shadow-xs flex items-center gap-1">
                                <i class="fas fa-arrows-up-down-left-right"></i> Geser
                            </span>
                            <button type="button" onmousedown="event.stopPropagation()" onclick="event.stopPropagation(); deleteImageRow('${img.id}')" title="Hapus Gambar Ini" class="w-5 h-5 rounded bg-rose-600 hover:bg-rose-700 text-white text-[9px] flex items-center justify-center shadow-xs cursor-pointer active:scale-90 transition">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Inner Frame dengan Shape & Shadow -->
                    <div class="w-full h-full overflow-hidden ${shapeClass} ${shadowClass} transition-transform" style="${borderStyle}">
                        <img src="${escapeHtml(img.url)}" class="w-full h-full object-cover select-none pointer-events-none" loading="lazy" alt="Gambar Sisipan">
                    </div>
                `;

                // Pasang Event Dragging dengan Snap Guidelines
                initDragForLayer(box, img, 'image');

                container.appendChild(box);
            });
        }

        // ==========================================
        // 4. LOGIKA VIDEO SISIPAN (MULTI-LAYER VIDEOS)
        // ==========================================

        function renderVideoRows() {
            const container = document.getElementById('video-rows-container');
            const badge = document.getElementById('vid-count-badge');
            const tabBadge = document.getElementById('tab-badge-video');
            const clearBtn = document.getElementById('btn-clear-all-videos');

            if (badge) badge.innerText = `${videoItems.length} Video`;
            if (tabBadge) tabBadge.innerText = videoItems.length;
            if (clearBtn) {
                if (videoItems.length > 0) clearBtn.classList.remove('hidden');
                else clearBtn.classList.add('hidden');
            }
            if (!container) return;

            container.innerHTML = '';

            if (videoItems.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <i class="fas fa-video text-3xl text-slate-300 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Belum ada video yang disisipkan</p>
                        <p class="text-[11px] text-slate-500 mt-0.5 mb-3">Klik tombol <strong>+ Sisipkan Video</strong> untuk memasang video MP4 atau YouTube ke brosur.</p>
                        <button type="button" onclick="addNewVideoRow()" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs transition inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fas fa-plus text-xs"></i>
                            <span>Sisipkan Video Sekarang</span>
                        </button>
                    </div>
                `;
                syncVideoJsonInput();
                return;
            }

            videoItems.forEach((vid, index) => {
                const row = document.createElement('div');
                row.className = 'item-row-strip bg-white border border-slate-200/90 hover:border-rose-500 rounded-2xl p-2.5 sm:p-3 shadow-xs space-y-2';
                row.id = `vid-row-item-${vid.id}`;

                let shapeOptionsHtml = '';
                frameShapes.forEach(s => {
                    const sel = (vid.shape === s.id) ? 'selected' : '';
                    shapeOptionsHtml += `<option value="${s.id}" ${sel}>${s.name}</option>`;
                });

                row.innerHTML = `
                    <!-- BARIS UTAMA (1 BARIS RINGKAS) -->
                    <div class="flex flex-wrap items-center gap-2">
                        
                        <!-- Nomor Video & Icon Play -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <div class="w-6 h-6 rounded-lg bg-rose-50 text-rose-800 font-black text-[11px] flex items-center justify-center border border-rose-100 shadow-2xs" title="Video #${index + 1}">
                                ${index + 1}
                            </div>
                            <div class="w-8 h-8 rounded-lg bg-rose-950 text-rose-300 flex items-center justify-center border border-rose-800 shrink-0">
                                <i class="fas fa-play text-xs"></i>
                            </div>
                        </div>

                        <!-- Input URL Video (YouTube / MP4) -->
                        <div class="flex-1 min-w-[140px]">
                            <input type="text" value="${escapeHtml(vid.url)}" oninput="updateVideoField('${vid.id}', 'url', this.value)" placeholder="Link YouTube / Direct MP4 URL..." class="w-full px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:border-rose-500 focus:outline-none bg-slate-50/60 focus:bg-white transition">
                        </div>

                        <!-- Tombol Upload File Video MP4/WebM -->
                        <div class="shrink-0">
                            <label class="cursor-pointer px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-700 border border-slate-200 text-xs font-bold transition flex items-center gap-1.5 active:scale-95 shadow-2xs">
                                <i class="fas fa-cloud-arrow-up text-rose-600 text-xs"></i>
                                <span class="hidden sm:inline text-[11px]">Upload MP4</span>
                                <input type="file" accept="video/mp4,video/webm,video/ogg" class="hidden" onchange="uploadLayerVideo(this, '${vid.id}')">
                            </label>
                        </div>

                        <!-- Pilihan Bentuk Bingkai (Shape) -->
                        <div class="shrink-0 max-w-[130px]">
                            <select onchange="updateVideoField('${vid.id}', 'shape', this.value)" class="w-full px-2 py-1.5 rounded-xl border border-slate-200 text-[11px] font-bold bg-white focus:border-rose-500 focus:outline-none cursor-pointer truncate" title="Pilih Bentuk Bingkai">
                                ${shapeOptionsHtml}
                            </select>
                        </div>

                        <!-- Rotasi Ringkas (Deg) -->
                        <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl px-2 py-1 shrink-0 shadow-2xs" title="Rotasi Kemiringan (-180° s/d 180°)">
                            <i class="fas fa-rotate text-rose-600 text-[10px]"></i>
                            <input type="number" min="-180" max="180" value="${vid.rotation || 0}" oninput="updateVideoField('${vid.id}', 'rotation', parseFloat(this.value) || 0)" class="w-9 text-xs font-black text-rose-800 text-center focus:outline-none">
                            <span class="text-[10px] text-slate-400 font-mono">°</span>
                        </div>

                        <!-- TOMBOL DUPLIKASI, SETTING DETAIL & HAPUS -->
                        <div class="flex items-center gap-1 shrink-0 ml-auto sm:ml-0">
                            <!-- Duplikasi -->
                            <button type="button" onclick="duplicateVideoRow('${vid.id}')" title="Duplikasi Video Ini" class="w-7 h-7 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200/80 flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-copy text-[11px]"></i>
                            </button>

                            <!-- Toggle Setting Lengkap (Garis Tepi, Bayangan, Playback, Ukuran) -->
                            <button type="button" onclick="toggleVideoDetails('${vid.id}')" title="Pengaturan Bingkai, Garis Tepi & Kontrol Playback" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-sliders text-[11px]"></i>
                            </button>

                            <!-- Hapus Video -->
                            <button type="button" onclick="deleteVideoRow('${vid.id}')" title="Hapus Video Ini" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 flex items-center justify-center text-xs transition active:scale-95 cursor-pointer">
                                <i class="fas fa-trash-can text-[11px]"></i>
                            </button>
                        </div>

                    </div>

                    <!-- PANEL DETAIL BINGKAI, GARIS TEPI, BAYANGAN, PLAYBACK & ROTASI (EXPANDABLE) -->
                    <div id="vid-details-${vid.id}" class="hidden pt-2.5 mt-2 border-t border-slate-100 bg-slate-50/70 p-3.5 rounded-xl space-y-3">
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            
                            <!-- KONTROL 1: GARIS TEPI (BORDER ON/OFF & COLOR) -->
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-700 text-[11px] flex items-center gap-1.5">
                                        <input type="checkbox" ${vid.border_enable ? 'checked' : ''} onchange="updateVideoField('${vid.id}', 'border_enable', this.checked ? 1 : 0)" class="rounded text-rose-600">
                                        <span>Garis Tepi (Border)</span>
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-mono">${vid.border_width || 2}px</span>
                                </div>
                                <div class="flex items-center gap-2 pt-1">
                                    <input type="color" value="${vid.border_color || '#ffffff'}" onchange="updateVideoField('${vid.id}', 'border_color', this.value)" class="w-6 h-6 rounded-lg border border-slate-200 cursor-pointer p-0.5" title="Warna Garis">
                                    <input type="range" min="1" max="12" step="1" value="${vid.border_width || 2}" oninput="updateVideoField('${vid.id}', 'border_width', parseInt(this.value))" class="flex-1 accent-rose-600 cursor-pointer">
                                </div>
                            </div>

                            <!-- KONTROL 2: BAYANGAN (SHADOW EFFECT) -->
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 space-y-1.5">
                                <label class="block font-bold text-slate-700 text-[11px]">Efek Bayangan (Shadow):</label>
                                <select onchange="updateVideoField('${vid.id}', 'shadow_style', this.value)" class="w-full px-2 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white focus:outline-none">
                                    <option value="none" ${vid.shadow_style === 'none' ? 'selected' : ''}>Tanpa Bayangan</option>
                                    <option value="soft" ${vid.shadow_style === 'soft' ? 'selected' : ''}>Bayangan Halus (Soft)</option>
                                    <option value="medium" ${vid.shadow_style === 'medium' ? 'selected' : ''}>Bayangan Sedang</option>
                                    <option value="deep" ${vid.shadow_style === 'deep' ? 'selected' : ''}>Bayangan 3D Dalam</option>
                                    <option value="glow_gold" ${vid.shadow_style === 'glow_gold' ? 'selected' : ''}>Glow Cahaya Emas</option>
                                    <option value="glow_teal" ${vid.shadow_style === 'glow_teal' ? 'selected' : ''}>Glow Cahaya Emerald</option>
                                </select>
                            </div>

                            <!-- KONTROL 3: UKURAN LEBAR -->
                            <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 space-y-1.5">
                                <div class="flex justify-between items-center text-[11px] font-bold text-slate-700">
                                    <span>Ukuran Lebar:</span>
                                    <span class="font-mono text-rose-800">${vid.width || 75}%</span>
                                </div>
                                <input type="range" min="20" max="100" step="1" value="${vid.width || 75}" oninput="updateVideoField('${vid.id}', 'width', parseInt(this.value))" class="w-full accent-rose-600 cursor-pointer">
                            </div>

                        </div>

                        <!-- KONTROL PLAYBACK / AUDIO OPTIONS -->
                        <div class="p-2.5 rounded-xl bg-white border border-slate-200/80 flex flex-wrap items-center gap-4 text-[11px] font-bold text-slate-700">
                            <span class="text-slate-500 font-bold flex items-center gap-1"><i class="fas fa-sliders text-rose-600"></i> Kontrol Pemutar:</span>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" ${vid.autoplay !== 0 ? 'checked' : ''} onchange="updateVideoField('${vid.id}', 'autoplay', this.checked ? 1 : 0)" class="rounded text-rose-600">
                                <span>Autoplay</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" ${vid.loop !== 0 ? 'checked' : ''} onchange="updateVideoField('${vid.id}', 'loop', this.checked ? 1 : 0)" class="rounded text-rose-600">
                                <span>Loop (Ulang Terus)</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" ${vid.muted !== 0 ? 'checked' : ''} onchange="updateVideoField('${vid.id}', 'muted', this.checked ? 1 : 0)" class="rounded text-rose-600">
                                <span>Muted (Bisukan Awal)</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" ${vid.controls ? 'checked' : ''} onchange="updateVideoField('${vid.id}', 'controls', this.checked ? 1 : 0)" class="rounded text-rose-600">
                                <span>Tombol Controls</span>
                            </label>
                        </div>

                        <!-- PRESET ROTASI CEPAT & RESET -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                            <div class="flex items-center gap-1.5">
                                <span class="text-[10.5px] font-bold text-slate-500">Preset Rotasi:</span>
                                <button type="button" onclick="updateVideoField('${vid.id}', 'rotation', 0)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">0° (Tegak)</button>
                                <button type="button" onclick="updateVideoField('${vid.id}', 'rotation', -15)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">-15° (Miring Kiri)</button>
                                <button type="button" onclick="updateVideoField('${vid.id}', 'rotation', 15)" class="px-2 py-0.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-[10px] font-bold">+15° (Miring Kanan)</button>
                            </div>

                            <button type="button" onclick="resetVideoCenter('${vid.id}')" class="text-[10px] text-rose-700 hover:underline font-bold">
                                <i class="fas fa-crosshairs mr-1"></i>Reset Posisi Tengah (50%)
                            </button>
                        </div>

                    </div>
                `;

                container.appendChild(row);
            });

            syncVideoJsonInput();
        }

        function toggleVideoDetails(id) {
            const el = document.getElementById(`vid-details-${id}`);
            if (el) el.classList.toggle('hidden');
        }

        function updateVideoField(id, field, value) {
            const vid = videoItems.find(v => v.id === id);
            if (vid) {
                vid[field] = value;
                renderSimVideoLayers();
                syncVideoJsonInput();
            }
        }

        function resetVideoCenter(id) {
            const vid = videoItems.find(v => v.id === id);
            if (vid) {
                vid.posX = 50;
                vid.posY = 50;
                renderSimVideoLayers();
                syncVideoJsonInput();
            }
        }

        function addNewVideoRow() {
            const newIndex = videoItems.length + 1;
            const sampleUrls = [
                'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4'
            ];
            const chosenUrl = sampleUrls[(newIndex - 1) % sampleUrls.length];

            const newVid = {
                id: 'vid_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                url: chosenUrl,
                shape: 'persegi_panjang_wide',
                border_enable: 1,
                border_width: 3,
                border_color: '#fbbf24',
                border_style: 'solid',
                shadow_style: 'medium',
                rotation: 0,
                posX: 50,
                posY: Math.min(80, 30 + ((newIndex - 1) * 22)),
                width: 75,
                autoplay: 1,
                loop: 1,
                muted: 1,
                controls: 1
            };

            videoItems.push(newVid);
            renderVideoRows();
            renderSimVideoLayers();

            setTimeout(() => {
                const el = document.getElementById(`vid-row-item-${newVid.id}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }

        function duplicateVideoRow(sourceId) {
            const source = videoItems.find(v => v.id === sourceId);
            if (!source) return;

            const cloned = JSON.parse(JSON.stringify(source));
            cloned.id = 'vid_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            cloned.posY = Math.min(88, (source.posY || 50) + 10);
            cloned.posX = Math.min(88, (source.posX || 50) + 5);

            const sourceIndex = videoItems.findIndex(v => v.id === sourceId);
            if (sourceIndex >= 0) {
                videoItems.splice(sourceIndex + 1, 0, cloned);
            } else {
                videoItems.push(cloned);
            }

            renderVideoRows();
            renderSimVideoLayers();

            setTimeout(() => {
                const el = document.getElementById(`vid-row-item-${cloned.id}`);
                if (el) {
                    el.classList.add('active-layer');
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => el.classList.remove('active-layer'), 1500);
                }
            }, 100);
        }

        function deleteVideoRow(id) {
            if (confirm('Hapus video yang disisipkan ini?')) {
                videoItems = videoItems.filter(v => v.id !== id);
                renderVideoRows();
                renderSimVideoLayers();
            }
        }

        function clearAllVideoRows() {
            if (videoItems.length === 0) return;
            if (confirm(`Yakin ingin menghapus semua (${videoItems.length}) video yang disisipkan?`)) {
                videoItems = [];
                renderVideoRows();
                renderSimVideoLayers();
            }
        }

        // Instant Upload Layer Video via AJAX
        function uploadLayerVideo(input, vidId) {
            if (!input.files || !input.files[0]) return;
            const file = input.files[0];

            // 1. Instant Live Preview via Object URL
            const previewUrl = URL.createObjectURL(file);
            updateVideoField(vidId, 'url', previewUrl);

            // 2. Upload async ke server
            const formData = new FormData();
            formData.append('ajax_video_file', file);

            fetch('admin-brosur-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.url) {
                    updateVideoField(vidId, 'url', res.url);
                    const inp = document.querySelector(`#vid-row-item-${vidId} input[type="text"]`);
                    if (inp) inp.value = res.url;
                }
            })
            .catch(err => {
                console.error('Upload video layer error:', err);
            });
        }

        function syncVideoJsonInput() {
            if (typeof framesData !== 'undefined' && framesData[currentActiveFrame]) {
                framesData[currentActiveFrame].videos = videoItems;
            }
            const inp = document.getElementById('input-video-items-json');
            if (inp) {
                inp.value = JSON.stringify(videoItems);
            }
        }

        function saveAllVideoItems() {
            syncVideoJsonInput();
            document.getElementById('form-pengaturan-videos').submit();
        }

        // ==========================================
        // 5. RENDER SIMULASI VIDEO SISIPAN & SMART DRAG
        // ==========================================

        function renderSimVideoLayers() {
            const container = document.getElementById('sim-video-layers-container');
            if (!container) return;

            container.innerHTML = '';

            videoItems.forEach((vid, index) => {
                const box = document.createElement('div');
                box.id = `sim-vid-box-${vid.id}`;
                box.className = 'draggable-box absolute pointer-events-auto transition-shadow group/viddrag';
                box.setAttribute('data-id', vid.id);
                box.style.top = `${vid.posY || 50}%`;
                box.style.left = `${vid.posX || 50}%`;
                box.style.transform = `translate(-50%, -50%) rotate(${vid.rotation || 0}deg)`;
                box.style.width = `${vid.width || 75}%`;
                box.style.zIndex = 25 + index;

                // Frame styling
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
                } else {
                    videoInnerHtml = `
                        <div class="w-full h-full bg-slate-900 flex flex-col items-center justify-center text-rose-400 p-2 text-center">
                            <i class="fas fa-video-slash text-lg mb-1"></i>
                            <span class="text-[9px] font-bold">Video Kosong / Link Belum Diisi</span>
                        </div>
                    `;
                }

                box.innerHTML = `
                    <!-- Border indikator saat hover / drag -->
                    <div class="absolute -inset-2 border-2 border-dashed border-rose-400 rounded-2xl pointer-events-none opacity-0 group-hover/viddrag:opacity-100 transition-opacity flex items-start justify-between p-1 z-30" style="transform: rotate(0deg);">
                        <span class="bg-rose-600 text-white text-[8px] font-black px-1.5 py-0.5 rounded shadow-xs">
                            Video #${index + 1}
                        </span>
                        <div class="flex items-center gap-1 pointer-events-auto">
                            <span class="bg-slate-950/90 text-rose-300 text-[8px] font-bold px-1.5 py-0.5 rounded shadow-xs flex items-center gap-1">
                                <i class="fas fa-arrows-up-down-left-right"></i> Geser
                            </span>
                            <button type="button" onmousedown="event.stopPropagation()" onclick="event.stopPropagation(); deleteVideoRow('${vid.id}')" title="Hapus Video Ini" class="w-5 h-5 rounded bg-rose-600 hover:bg-rose-700 text-white text-[9px] flex items-center justify-center shadow-xs cursor-pointer active:scale-90 transition">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Inner Frame dengan Shape & Shadow -->
                    <div class="w-full h-full overflow-hidden ${shapeClass} ${shadowClass} transition-transform relative bg-black" style="${borderStyle}">
                        ${videoInnerHtml}
                    </div>
                `;

                // Pasang Event Dragging dengan Snap Guidelines
                initDragForLayer(box, vid, 'video');

                container.appendChild(box);
            });
        }

        // ==========================================
        // 6. LOGIKA KOLOM TULISAN DINAMIS (TEXT ROWS)
        // ==========================================

        function renderRows() {
            const container = document.getElementById('text-rows-container');
            const badge = document.getElementById('text-count-badge');
            const tabBadge = document.getElementById('tab-badge-text');
            const clearBtn = document.getElementById('btn-clear-all-text');

            if (badge) badge.innerText = `${textItems.length} Kolom`;
            if (tabBadge) tabBadge.innerText = textItems.length;
            if (clearBtn) {
                if (textItems.length > 0) clearBtn.classList.remove('hidden');
                else clearBtn.classList.add('hidden');
            }
            if (!container) return;

            container.innerHTML = '';

            if (textItems.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <i class="fas fa-font text-3xl text-slate-300 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Belum ada kolom tulisan</p>
                        <p class="text-[11px] text-slate-500 mt-0.5 mb-3">Klik tombol <strong>+ Tambah Kolom Tulisan</strong> untuk menambahkan teks baru ke brosur.</p>
                        <button type="button" onclick="addNewTextRow()" class="px-4 py-2 rounded-xl bg-[#0b8478] hover:bg-[#08635a] text-white font-black text-xs transition inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fas fa-plus text-xs"></i>
                            <span>Tambah Kolom Tulisan Pertama</span>
                        </button>
                    </div>
                `;
                syncJsonInput();
                return;
            }

            textItems.forEach((item, index) => {
                const row = document.createElement('div');
                row.className = 'item-row-strip bg-white border border-slate-200/90 hover:border-teal-500 rounded-2xl p-2.5 sm:p-3 shadow-xs space-y-2';
                row.id = `row-item-${item.id}`;

                let fontOptionsHtml = '';
                availableFonts.forEach(f => {
                    const sel = (item.font === f.id) ? 'selected' : '';
                    fontOptionsHtml += `<option value="${f.id}" ${sel}>${f.name}</option>`;
                });

                row.innerHTML = `
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

                        <!-- TOMBOL DUPLIKASI, SETTING POSISI/LEBAR & HAPUS -->
                        <div class="flex items-center gap-1 shrink-0 ml-auto sm:ml-0">
                            <button type="button" onclick="toggleDetails('${item.id}')" title="Pengaturan Posisi & Lebar Bidang Tulis" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-teal-50 hover:text-teal-800 text-slate-700 flex items-center gap-1 text-xs font-bold transition shadow-2xs active:scale-95 cursor-pointer">
                                <i class="fas fa-sliders text-[11px] text-teal-600"></i>
                                <span class="text-[11px]">Posisi & Lebar</span>
                            </button>

                            <button type="button" onclick="duplicateRow('${item.id}')" title="Duplikasi / Gandakan Kolom Ini" class="w-7 h-7 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200/80 flex items-center justify-center text-xs transition shadow-2xs active:scale-95 cursor-pointer">
                                <i class="fas fa-copy text-amber-600 text-[11px]"></i>
                            </button>

                            <button type="button" onclick="deleteRow('${item.id}')" title="Hapus Kolom Ini" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 flex items-center justify-center text-xs transition shadow-2xs active:scale-95 cursor-pointer">
                                <i class="fas fa-trash-can text-[11px]"></i>
                            </button>
                        </div>

                    </div>

                    <!-- PANEL DETAIL PENGATURAN LEBAR & POSISI BIDANG TULIS (EXPANDABLE) -->
                    <div id="details-${item.id}" class="hidden pt-3 mt-2 border-t border-slate-100 bg-slate-50/80 p-3.5 rounded-2xl space-y-3">
                        
                        <div class="flex items-center justify-between border-b border-slate-200/60 pb-2">
                            <span class="text-xs font-black text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-arrows-up-down-left-right text-[#0b8478]"></i>
                                <span>Pengaturan Lebar Bidang Tulis & Posisi:</span>
                            </span>
                            <span class="text-[10px] text-slate-400 font-semibold">Tersedia fitur Drag & Drop langsung di layar simulasi</span>
                        </div>

                        <!-- 3 SLIDER: LEBAR (HORIZONTAL), POSISI Y (VERTIKAL), POSISI X (HORIZONTAL) -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <!-- 1. Lebar Bidang Tulis (Horizontal) -->
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-700 mb-1">
                                    <span class="flex items-center gap-1"><i class="fas fa-arrows-left-right text-teal-600"></i> Lebar Bidang (Horisontal):</span>
                                    <span id="label-width-${item.id}" class="font-mono text-teal-800 font-bold">${item.width || 85}%</span>
                                </div>
                                <input type="range" min="15" max="100" step="1" value="${item.width || 85}" oninput="updateItemPosition('${item.id}', 'width', this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                <span class="text-[9.5px] text-slate-400">Atur lebar area teks (15% - 100%)</span>
                            </div>

                            <!-- 2. Posisi Vertikal Y (Atas - Bawah) -->
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-700 mb-1">
                                    <span class="flex items-center gap-1"><i class="fas fa-arrows-up-down text-teal-600"></i> Posisi Vertikal (Y):</span>
                                    <span id="label-posy-${item.id}" class="font-mono text-teal-800 font-bold">${Math.round(item.posY || 35)}%</span>
                                </div>
                                <input type="range" min="2" max="95" step="0.5" value="${item.posY || 35}" oninput="updateItemPosition('${item.id}', 'posY', this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                <span class="text-[9.5px] text-slate-400">Jarak vertikal dari atas layar (2% - 95%)</span>
                            </div>

                            <!-- 3. Posisi Horizontal X (Kiri - Kanan) -->
                            <div class="bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-2xs">
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-700 mb-1">
                                    <span class="flex items-center gap-1"><i class="fas fa-arrows-left-right-to-line text-teal-600"></i> Posisi Horizontal (X):</span>
                                    <span id="label-posx-${item.id}" class="font-mono text-teal-800 font-bold">${Math.round(item.posX || 50)}%</span>
                                </div>
                                <input type="range" min="5" max="95" step="0.5" value="${item.posX || 50}" oninput="updateItemPosition('${item.id}', 'posX', this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                                <span class="text-[9.5px] text-slate-400">Pusat posisi horizontal (50% = pas tengah)</span>
                            </div>
                        </div>

                        <!-- PRESET CEPAT LEBAR & RESET POSISI -->
                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[10px]">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="font-bold text-slate-500">Preset Lebar:</span>
                                <button type="button" onclick="updateItemPosition('${item.id}', 'width', 100)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-teal-50 text-slate-700 font-bold active:scale-95 cursor-pointer">100% (Penuh)</button>
                                <button type="button" onclick="updateItemPosition('${item.id}', 'width', 85)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-teal-50 text-slate-700 font-bold active:scale-95 cursor-pointer">85% (Standar)</button>
                                <button type="button" onclick="updateItemPosition('${item.id}', 'width', 65)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-teal-50 text-slate-700 font-bold active:scale-95 cursor-pointer">65% (Sedang)</button>
                                <button type="button" onclick="updateItemPosition('${item.id}', 'width', 45)" class="px-2 py-0.5 rounded-lg bg-white border border-slate-200 hover:bg-teal-50 text-slate-700 font-bold active:scale-95 cursor-pointer">45% (Ramping)</button>
                            </div>

                            <button type="button" onclick="updateItemPosition('${item.id}', 'posX', 50)" class="text-teal-700 hover:underline font-bold flex items-center gap-1 active:scale-95 cursor-pointer">
                                <i class="fas fa-crosshairs"></i>
                                <span>Reset Posisi Tengah X (50%)</span>
                            </button>
                        </div>

                    </div>
                `;

                container.appendChild(row);
            });

            syncJsonInput();
        }

        function toggleDetails(id) {
            const el = document.getElementById(`details-${id}`);
            if (el) el.classList.toggle('hidden');
        }

        function updateItemField(id, field, value) {
            const item = textItems.find(i => i.id === id);
            if (item) {
                item[field] = value;
                renderSimLayers();
                syncJsonInput();
            }
        }

        function updateItemPosition(id, field, value) {
            const item = textItems.find(i => i.id === id);
            if (item) {
                item[field] = parseFloat(value);
                const label = document.getElementById(`label-${field.toLowerCase()}-${id}`);
                if (label) label.innerText = Math.round(item[field]) + '%';
                
                const inputEl = document.querySelector(`#details-${id} input[oninput*="'${field}'"]`);
                if (inputEl) inputEl.value = item[field];

                renderSimLayers();
                syncJsonInput();
            }
        }

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

            setTimeout(() => {
                const el = document.getElementById(`row-item-${newItem.id}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }

        function duplicateRow(sourceId) {
            const source = textItems.find(i => i.id === sourceId);
            if (!source) return;

            const cloned = JSON.parse(JSON.stringify(source));
            cloned.id = 'text_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            cloned.content = source.content ? source.content + ' (Salinan)' : 'Salinan Teks';
            cloned.posY = Math.min(90, (source.posY || 35) + 9);

            const sourceIndex = textItems.findIndex(i => i.id === sourceId);
            if (sourceIndex >= 0) {
                textItems.splice(sourceIndex + 1, 0, cloned);
            } else {
                textItems.push(cloned);
            }

            renderRows();
            renderSimLayers();

            setTimeout(() => {
                const el = document.getElementById(`row-item-${cloned.id}`);
                if (el) {
                    el.classList.add('active-layer');
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => el.classList.remove('active-layer'), 1500);
                }
            }, 100);
        }

        function deleteRow(id) {
            const item = textItems.find(i => i.id === id);
            const itemName = item && item.content ? `"${item.content.substring(0, 25)}..."` : 'kolom ini';
            if (confirm(`Hapus kolom tulisan ${itemName}?`)) {
                textItems = textItems.filter(i => i.id !== id);
                renderRows();
                renderSimLayers();
            }
        }

        function clearAllTextRows() {
            if (textItems.length === 0) return;
            if (confirm(`Yakin ingin menghapus semua (${textItems.length}) kolom tulisan?`)) {
                textItems = [];
                renderRows();
                renderSimLayers();
            }
        }

        // ==========================================
        // 5. LOGIKA TOMBOL AKSI (MULTI-LAYER BUTTONS)
        // ==========================================

        function renderButtonRows() {
            const container = document.getElementById('button-rows-container');
            const badge = document.getElementById('btn-count-badge');
            const tabBadge = document.getElementById('tab-badge-button');
            const clearBtn = document.getElementById('btn-clear-all-buttons');

            if (badge) badge.innerText = `${buttonItems.length} Tombol`;
            if (tabBadge) tabBadge.innerText = buttonItems.length;
            if (clearBtn) {
                if (buttonItems.length > 0) clearBtn.classList.remove('hidden');
                else clearBtn.classList.add('hidden');
            }
            if (!container) return;

            container.innerHTML = '';

            if (buttonItems.length === 0) {
                container.innerHTML = `
                    <div class="p-6 text-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50">
                        <i class="fas fa-hand-pointer text-3xl text-slate-300 mb-2"></i>
                        <p class="text-xs font-bold text-slate-700">Belum ada tombol aksi</p>
                        <p class="text-[11px] text-slate-500 mt-0.5 mb-3">Klik tombol <strong>+ Tambah Tombol</strong> untuk menambahkan tombol interaktif seperti WhatsApp, Website, atau Form PSB.</p>
                        <button type="button" onclick="addNewButtonRow()" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs transition inline-flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fas fa-plus text-xs"></i>
                            <span>Tambah Tombol Pertama</span>
                        </button>
                    </div>
                `;
                syncButtonJsonInput();
                return;
            }

            buttonItems.forEach((btn, index) => {
                const row = document.createElement('div');
                row.className = 'item-row-strip bg-white border border-slate-200/90 hover:border-amber-500 rounded-2xl p-2.5 sm:p-3 shadow-xs space-y-2';
                row.id = `btn-row-item-${btn.id}`;

                // Options Bentuk Tombol
                let shapeOptionsHtml = '';
                buttonShapes.forEach(s => {
                    const sel = (btn.shape === s.id) ? 'selected' : '';
                    shapeOptionsHtml += `<option value="${s.id}" ${sel}>${s.name}</option>`;
                });

                // Options Icon Tombol
                let iconOptionsHtml = '';
                buttonIcons.forEach(ic => {
                    const sel = (btn.icon === ic.id) ? 'selected' : '';
                    iconOptionsHtml += `<option value="${ic.id}" ${sel}>${ic.name}</option>`;
                });

                // Options Bayangan Tombol
                let shadowOptionsHtml = '';
                buttonShadows.forEach(sh => {
                    const sel = (btn.shadow_style === sh.id) ? 'selected' : '';
                    shadowOptionsHtml += `<option value="${sh.id}" ${sel}>${sh.name}</option>`;
                });

                // Quick Chips Bayangan Tombol
                let shadowQuickChipsHtml = '';
                buttonShadows.forEach(sh => {
                    const isActive = (btn.shadow_style === sh.id) || (!btn.shadow_style && sh.id === 'none');
                    const activeClass = isActive 
                        ? 'bg-amber-600 text-white font-black ring-2 ring-amber-400 shadow-xs' 
                        : 'bg-white text-slate-700 hover:bg-amber-50 border border-slate-200 font-semibold';
                    shadowQuickChipsHtml += `
                        <button type="button" onclick="updateButtonField('${btn.id}', 'shadow_style', '${sh.id}')" class="px-2 py-1 rounded-lg text-[10px] transition flex items-center gap-1 active:scale-95 cursor-pointer ${activeClass}" title="Terapkan ${sh.name}">
                            <i class="fas ${sh.icon} text-[9px]"></i>
                            <span>${sh.name}</span>
                        </button>
                    `;
                });

                // Options Font
                let fontOptionsHtml = '';
                availableFonts.forEach(f => {
                    const sel = (btn.font === f.id) ? 'selected' : '';
                    fontOptionsHtml += `<option value="${f.id}" ${sel}>${f.name}</option>`;
                });

                row.innerHTML = `
                    <!-- BARIS UTAMA (1 BARIS RINGKAS) -->
                    <div class="flex flex-wrap items-center gap-2">
                        
                        <!-- Nomor Tombol & Icon Preview -->
                        <div class="flex items-center gap-1.5 shrink-0">
                            <div class="w-6 h-6 rounded-lg bg-amber-50 text-amber-800 font-black text-[11px] flex items-center justify-center border border-amber-200/80 shadow-2xs" title="Tombol #${index + 1}">
                                ${index + 1}
                            </div>
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shadow-2xs shrink-0 transition" style="background-color: ${btn.bg_color || '#25d366'}; color: ${btn.text_color || '#ffffff'};">
                                <i class="${btn.icon && btn.icon !== 'none' ? btn.icon : 'fas fa-link'}"></i>
                            </div>
                        </div>

                        <!-- Input Teks Tombol -->
                        <div class="flex-1 min-w-[130px]">
                            <input type="text" value="${escapeHtml(btn.text || '')}" oninput="updateButtonField('${btn.id}', 'text', this.value)" placeholder="Tulisan pada Tombol (mis: Chat WhatsApp)" class="w-full px-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-bold focus:border-amber-500 focus:outline-none bg-slate-50/60 focus:bg-white transition" title="Tulisan / Label Tombol">
                        </div>

                        <!-- Input Link Tujuan -->
                        <div class="flex-1 min-w-[140px]">
                            <div class="relative">
                                <input type="text" value="${escapeHtml(btn.url || '')}" oninput="updateButtonField('${btn.id}', 'url', this.value)" placeholder="Link URL: https://wa.me/..." class="w-full pl-7 pr-2.5 py-1.5 rounded-xl border border-slate-200 text-xs font-medium focus:border-amber-500 focus:outline-none bg-slate-50/60 focus:bg-white transition" title="Link URL Tujuan">
                                <i class="fas fa-link absolute left-2.5 top-2 text-slate-400 text-[10px]"></i>
                            </div>
                        </div>

                        <!-- Pilihan Bentuk Tombol (Bulat, Persegi Panjang, Ujung Tumpul / Pill) -->
                        <div class="shrink-0 max-w-[130px]">
                            <select onchange="updateButtonField('${btn.id}', 'shape', this.value)" class="w-full px-2 py-1.5 rounded-xl border border-slate-200 text-[11px] font-bold bg-white focus:border-amber-500 focus:outline-none cursor-pointer truncate" title="Pilih Bentuk Tombol">
                                ${shapeOptionsHtml}
                            </select>
                        </div>

                        <!-- Pilihan Icon -->
                        <div class="shrink-0 max-w-[110px]">
                            <select onchange="updateButtonField('${btn.id}', 'icon', this.value)" class="w-full px-2 py-1.5 rounded-xl border border-slate-200 text-[11px] font-semibold bg-white focus:border-amber-500 focus:outline-none cursor-pointer truncate" title="Pilih Icon Tombol">
                                ${iconOptionsHtml}
                            </select>
                        </div>

                        <!-- Warna Background Tombol -->
                        <div class="shrink-0 flex items-center gap-1" title="Pilih Warna Background Tombol">
                            <input type="color" value="${btn.bg_color || '#25d366'}" onchange="updateButtonField('${btn.id}', 'bg_color', this.value)" class="w-7 h-7 rounded-xl border border-slate-200 p-0.5 bg-white cursor-pointer shadow-2xs">
                        </div>

                        <!-- Warna Teks / Icon Tombol -->
                        <div class="shrink-0 flex items-center gap-1" title="Pilih Warna Teks & Icon Tombol">
                            <input type="color" value="${btn.text_color || '#ffffff'}" onchange="updateButtonField('${btn.id}', 'text_color', this.value)" class="w-7 h-7 rounded-xl border border-slate-200 p-0.5 bg-white cursor-pointer shadow-2xs">
                        </div>

                        <!-- Tombol Aksi Row (Detail, Duplikasi, Hapus) -->
                        <div class="flex items-center gap-1 shrink-0 ml-auto">
                            <button type="button" onclick="toggleButtonDetails('${btn.id}')" title="Pengaturan Lanjutan Tombol (Bayangan, Ukuran & Posisi)" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center gap-1 text-xs font-bold transition cursor-pointer">
                                <i class="fas fa-sliders text-[11px]"></i>
                                <span class="text-[11px]">Setting & Bayangan</span>
                            </button>
                            <button type="button" onclick="duplicateButtonRow('${btn.id}')" title="Gandakan / Duplikasi Tombol" class="w-7 h-7 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 flex items-center justify-center text-xs transition cursor-pointer">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button type="button" onclick="deleteButtonRow('${btn.id}')" title="Hapus Tombol" class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 flex items-center justify-center text-xs transition cursor-pointer">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>

                    </div>

                    <!-- PANEL PENGATURAN LANJUTAN TOMBOL -->
                    <div id="btn-details-${btn.id}" class="pt-3 border-t border-slate-100 space-y-3 bg-slate-50/70 p-3 rounded-xl">
                        
                        <!-- 1. PENGATURAN BAYANGAN TOMBOL (SHADOW & GLOW) -->
                        <div class="p-3 bg-amber-500/10 border border-amber-300/80 rounded-xl space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-[11.5px] font-black text-amber-950 flex items-center gap-1.5">
                                    <i class="fas fa-cube text-amber-600"></i>
                                    <span>Pengaturan Bayangan & Efek 3D Tombol (Shadow & Glow):</span>
                                </span>
                                <div class="shrink-0 min-w-[180px]">
                                    <select onchange="updateButtonField('${btn.id}', 'shadow_style', this.value)" class="w-full px-2.5 py-1 rounded-lg border border-amber-300 text-xs font-bold bg-white focus:outline-none cursor-pointer">
                                        ${shadowOptionsHtml}
                                    </select>
                                </div>
                            </div>

                            <!-- Preset Chips Bayangan Cepat (1-Click Apply) -->
                            <div>
                                <span class="block text-[10px] font-bold text-slate-600 mb-1">Pilih Cepat Efek Bayangan / Glow:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    ${shadowQuickChipsHtml}
                                </div>
                            </div>
                        </div>

                        <!-- 2. PRESET WARNA CEPAT TOMBOL -->
                        <div>
                            <span class="block text-[10.5px] font-bold text-slate-700 mb-1.5">Preset Tema & Warna Cepat:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" onclick="applyBtnPreset('${btn.id}', '#25d366', '#ffffff', 'glow_wa')" class="px-2 py-1 rounded-lg bg-[#25d366] text-white text-[10px] font-black shadow-2xs flex items-center gap-1 active:scale-95 transition cursor-pointer">
                                    <i class="fab fa-whatsapp"></i> WA Green
                                </button>
                                <button type="button" onclick="applyBtnPreset('${btn.id}', '#e1306c', '#ffffff', 'glow_rose')" class="px-2 py-1 rounded-lg bg-gradient-to-r from-purple-600 via-pink-600 to-amber-500 text-white text-[10px] font-black shadow-2xs flex items-center gap-1 active:scale-95 transition cursor-pointer">
                                    <i class="fab fa-instagram"></i> Instagram
                                </button>
                                <button type="button" onclick="applyBtnPreset('${btn.id}', '#ff0000', '#ffffff', 'glow_rose')" class="px-2 py-1 rounded-lg bg-[#ff0000] text-white text-[10px] font-black shadow-2xs flex items-center gap-1 active:scale-95 transition cursor-pointer">
                                    <i class="fab fa-youtube"></i> YouTube Red
                                </button>
                                <button type="button" onclick="applyBtnPreset('${btn.id}', '#f59e0b', '#022d27', 'glow_gold')" class="px-2 py-1 rounded-lg bg-[#f59e0b] text-[#022d27] text-[10px] font-black shadow-2xs flex items-center gap-1 active:scale-95 transition cursor-pointer">
                                    <i class="fas fa-crown"></i> Gold Luxury
                                </button>
                                <button type="button" onclick="applyBtnPreset('${btn.id}', '#0b8478', '#ffffff', 'glow_teal')" class="px-2 py-1 rounded-lg bg-[#0b8478] text-white text-[10px] font-black shadow-2xs flex items-center gap-1 active:scale-95 transition cursor-pointer">
                                    <i class="fas fa-mosque"></i> Emerald VQ
                                </button>
                                <button type="button" onclick="applyBtnPreset('${btn.id}', '#0f172a', '#38bdf8', 'glow_sky')" class="px-2 py-1 rounded-lg bg-[#0f172a] text-[#38bdf8] text-[10px] font-black shadow-2xs flex items-center gap-1 active:scale-95 transition cursor-pointer">
                                    <i class="fas fa-moon"></i> Midnight Sky
                                </button>
                            </div>
                        </div>

                        <!-- 3. PENGATURAN UKURAN TOMBOL (PANJANG & TINGGI) -->
                        <div class="p-3 bg-white border border-slate-200 rounded-xl space-y-2.5 shadow-2xs">
                            <span class="block text-[11px] font-black text-slate-800 flex items-center gap-1.5">
                                <i class="fas fa-up-right-and-down-left-from-center text-amber-600"></i>
                                <span>Pengaturan Ukuran Tombol (Panjang & Tinggi):</span>
                            </span>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Panjang / Lebar Tombol -->
                                <div>
                                    <div class="flex justify-between text-[10.5px] font-bold text-slate-700 mb-1">
                                        <span>Panjang / Lebar Tombol:</span>
                                        <span id="btn-label-width-${btn.id}" class="font-mono text-amber-800 font-bold">${btn.width || 80}%</span>
                                    </div>
                                    <input type="range" min="15" max="100" step="1" value="${btn.width || 80}" oninput="updateButtonPosition('${btn.id}', 'width', this.value)" class="w-full accent-amber-500 cursor-pointer">
                                    <span class="text-[9.5px] text-slate-400">Atur panjang tombol (15% - 100% layar)</span>
                                </div>

                                <!-- Tinggi Tombol -->
                                <div>
                                    <div class="flex justify-between text-[10.5px] font-bold text-slate-700 mb-1">
                                        <span>Tinggi / Ketebalan Tombol:</span>
                                        <span id="btn-label-height-${btn.id}" class="font-mono text-amber-800 font-bold">${btn.height || 46}px</span>
                                    </div>
                                    <input type="range" min="24" max="120" step="1" value="${btn.height || 46}" oninput="updateButtonPosition('${btn.id}', 'height', this.value)" class="w-full accent-amber-500 cursor-pointer">
                                    <span class="text-[9.5px] text-slate-400">Atur tinggi / diameter tombol (24px - 120px)</span>
                                </div>
                            </div>
                        </div>

                        <!-- 4. GARIS TEPI (BORDER), TIPOGRAFI & TARGET LINK -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                            <!-- Garis Tepi (Border) -->
                            <div class="p-2 bg-white border border-slate-200 rounded-xl space-y-1">
                                <label class="flex items-center gap-1.5 text-[10.5px] font-bold text-slate-700 cursor-pointer">
                                    <input type="checkbox" ${btn.border_enable ? 'checked' : ''} onchange="updateButtonField('${btn.id}', 'border_enable', this.checked ? 1 : 0)" class="rounded text-amber-600 focus:ring-0">
                                    <span>Garis Tepi (Border)</span>
                                </label>
                                <div class="flex items-center gap-1.5 pt-1">
                                    <input type="color" value="${btn.border_color || '#ffffff'}" onchange="updateButtonField('${btn.id}', 'border_color', this.value)" class="w-6 h-6 rounded border border-slate-200 p-0.5 bg-white cursor-pointer shrink-0" title="Warna Garis Tepi">
                                    <div class="flex items-center gap-1 flex-1">
                                        <input type="number" min="1" max="10" value="${btn.border_width || 2}" oninput="updateButtonField('${btn.id}', 'border_width', parseInt(this.value) || 1)" class="w-full text-xs font-black text-amber-800 text-center border border-slate-200 rounded px-1 py-0.5" title="Tebal Border">
                                        <span class="text-[10px] text-slate-400">px</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Ukuran & Font Tulisan -->
                            <div class="p-2 bg-white border border-slate-200 rounded-xl space-y-1">
                                <label class="block text-[10.5px] font-bold text-slate-700">Jenis Font & Ukuran:</label>
                                <div class="flex items-center gap-1.5">
                                    <select onchange="updateButtonField('${btn.id}', 'font', this.value)" class="w-full px-1.5 py-1 rounded border border-slate-200 text-[10px] font-semibold bg-white focus:outline-none truncate">
                                        ${fontOptionsHtml}
                                    </select>
                                    <div class="flex items-center gap-0.5 shrink-0">
                                        <input type="number" min="9" max="40" value="${btn.font_size || 14}" oninput="updateButtonField('${btn.id}', 'font_size', parseInt(this.value) || 14)" class="w-8 text-xs font-black text-amber-800 text-center border border-slate-200 rounded py-0.5" title="Ukuran Font">
                                        <span class="text-[9px] text-slate-400">px</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Target Buka Link -->
                            <div class="p-2 bg-white border border-slate-200 rounded-xl space-y-1">
                                <label class="block text-[10.5px] font-bold text-slate-700">Target Buka Link:</label>
                                <select onchange="updateButtonField('${btn.id}', 'target', this.value)" class="w-full px-2 py-1 rounded border border-slate-200 text-[11px] font-semibold bg-white focus:outline-none">
                                    <option value="_blank" ${btn.target === '_blank' ? 'selected' : ''}>Tab Baru (_blank)</option>
                                    <option value="_self" ${btn.target === '_self' ? 'selected' : ''}>Halaman Ini (_self)</option>
                                </select>
                            </div>
                        </div>

                        <!-- 5. SLIDER POSISI X & Y TOMBOL -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200/60">
                            <div>
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-600 mb-1">
                                    <span>Posisi Vertikal (Y):</span>
                                    <span id="btn-label-posy-${btn.id}" class="font-mono text-amber-800 font-bold">${Math.round(btn.posY || 80)}%</span>
                                </div>
                                <input type="range" min="5" max="95" step="0.5" value="${btn.posY || 80}" oninput="updateButtonPosition('${btn.id}', 'posY', this.value)" class="w-full accent-amber-500 cursor-pointer">
                            </div>
                            <div>
                                <div class="flex justify-between text-[10.5px] font-bold text-slate-600 mb-1">
                                    <span>Posisi Horizontal (X):</span>
                                    <span id="btn-label-posx-${btn.id}" class="font-mono text-amber-800 font-bold">${Math.round(btn.posX || 50)}%</span>
                                </div>
                                <input type="range" min="10" max="90" step="0.5" value="${btn.posX || 50}" oninput="updateButtonPosition('${btn.id}', 'posX', this.value)" class="w-full accent-amber-500 cursor-pointer">
                            </div>
                        </div>

                    </div>
                `;

                container.appendChild(row);
            });

            syncButtonJsonInput();
        }

        function toggleButtonDetails(id) {
            const el = document.getElementById(`btn-details-${id}`);
            if (el) el.classList.toggle('hidden');
        }

        function updateButtonField(id, field, value) {
            const btn = buttonItems.find(b => b.id === id);
            if (btn) {
                btn[field] = value;
                if (['shadow_style', 'border_enable', 'shape'].includes(field)) {
                    renderButtonRows();
                }
                renderSimButtonLayers();
                syncButtonJsonInput();
            }
        }

        function updateButtonPosition(id, field, value) {
            const btn = buttonItems.find(b => b.id === id);
            if (btn) {
                btn[field] = parseFloat(value);
                const label = document.getElementById(`btn-label-${field.toLowerCase()}-${id}`);
                if (label) {
                    if (field === 'height') {
                        label.innerText = Math.round(btn[field]) + 'px';
                    } else {
                        label.innerText = Math.round(btn[field]) + '%';
                    }
                }
                renderSimButtonLayers();
                syncButtonJsonInput();
            }
        }

        function applyBtnPreset(id, bg, text, shadow) {
            const btn = buttonItems.find(b => b.id === id);
            if (btn) {
                btn.bg_color = bg;
                btn.text_color = text;
                btn.shadow_style = shadow;
                renderButtonRows();
                renderSimButtonLayers();
                syncButtonJsonInput();
            }
        }

        function addNewButtonRow() {
            const newIndex = buttonItems.length + 1;
            const newPosY = Math.min(88, 70 + ((newIndex - 1) * 10));

            const newBtn = {
                id: 'btn_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                text: newIndex === 1 ? 'Chat WhatsApp Panitia' : (newIndex === 2 ? 'Kunjungi Website' : 'Daftar Sekarang'),
                url: newIndex === 1 ? 'https://wa.me/6281234567890' : 'https://villaquranindonesia.com',
                icon: newIndex === 1 ? 'fab fa-whatsapp' : (newIndex === 2 ? 'fas fa-globe' : 'fas fa-paper-plane'),
                shape: 'rounded_pill',
                bg_color: newIndex === 1 ? '#25d366' : (newIndex === 2 ? '#0b8478' : '#f59e0b'),
                text_color: '#ffffff',
                border_enable: 0,
                border_width: 2,
                border_color: '#ffffff',
                shadow_style: newIndex === 1 ? 'glow_wa' : 'glow_teal',
                font_size: 13,
                font: 'Plus Jakarta Sans',
                posX: 50.0,
                posY: newPosY,
                width: 82,
                height: 46,
                target: '_blank'
            };

            buttonItems.push(newBtn);
            renderButtonRows();
            renderSimButtonLayers();

            setTimeout(() => {
                const el = document.getElementById(`btn-row-item-${newBtn.id}`);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
        }

        function duplicateButtonRow(sourceId) {
            const source = buttonItems.find(b => b.id === sourceId);
            if (!source) return;

            const cloned = JSON.parse(JSON.stringify(source));
            cloned.id = 'btn_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            cloned.text = source.text ? source.text + ' (Salinan)' : 'Salinan Tombol';
            cloned.posY = Math.min(92, (source.posY || 80) + 8);
            cloned.height = source.height || 46;

            const sourceIndex = buttonItems.findIndex(b => b.id === sourceId);
            if (sourceIndex >= 0) {
                buttonItems.splice(sourceIndex + 1, 0, cloned);
            } else {
                buttonItems.push(cloned);
            }

            renderButtonRows();
            renderSimButtonLayers();

            setTimeout(() => {
                const el = document.getElementById(`btn-row-item-${cloned.id}`);
                if (el) {
                    el.classList.add('active-layer');
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    setTimeout(() => el.classList.remove('active-layer'), 1500);
                }
            }, 100);
        }

        function deleteButtonRow(id) {
            const btn = buttonItems.find(b => b.id === id);
            const btnName = btn && btn.text ? `"${btn.text.substring(0, 25)}..."` : 'tombol ini';
            if (confirm(`Hapus ${btnName}?`)) {
                buttonItems = buttonItems.filter(b => b.id !== id);
                renderButtonRows();
                renderSimButtonLayers();
            }
        }

        function clearAllButtonRows() {
            if (buttonItems.length === 0) return;
            if (confirm(`Yakin ingin menghapus semua (${buttonItems.length}) tombol?`)) {
                buttonItems = [];
                renderButtonRows();
                renderSimButtonLayers();
            }
        }

        function syncButtonJsonInput() {
            if (typeof framesData !== 'undefined' && framesData[currentActiveFrame]) {
                framesData[currentActiveFrame].buttons = buttonItems;
            }
            const inp = document.getElementById('input-button-items-json');
            if (inp) inp.value = JSON.stringify(buttonItems);
        }

        function saveAllButtonItems() {
            syncButtonJsonInput();
            document.getElementById('form-pengaturan-button').submit();
        }

        function renderSimButtonLayers() {
            const container = document.getElementById('sim-button-layers-container');
            if (!container) return;

            container.innerHTML = '';

            buttonItems.forEach((btn, index) => {
                const box = document.createElement('div');
                box.id = `sim-box-${btn.id}`;
                box.className = 'draggable-box absolute pointer-events-auto transition-all group/btn';
                box.setAttribute('data-id', btn.id);
                box.style.top = `${btn.posY || 80}%`;
                box.style.left = `${btn.posX || 50}%`;
                box.style.transform = 'translate(-50%, -50%)';

                const btnHeight = btn.height || (btn.shape === 'bulat' ? 48 : 46);
                box.style.width = (btn.shape === 'bulat') ? `${btnHeight}px` : `${btn.width || 80}%`;
                box.style.height = `${btnHeight}px`;
                box.style.zIndex = 35 + index;

                // Tentukan class bentuk tombol
                let shapeClass = 'shape-btn-rounded_pill';
                if (btn.shape === 'persegipanjang') shapeClass = 'shape-btn-persegipanjang';
                else if (btn.shape === 'bulat') shapeClass = 'shape-btn-bulat';
                else if (btn.shape === 'rounded') shapeClass = 'shape-btn-rounded';

                // Tentukan class bayangan / shadow
                const shadowClass = btn.shadow_style && btn.shadow_style !== 'none' ? `shadow-${btn.shadow_style}` : '';

                // Border style
                const borderStyle = btn.border_enable ? `border: ${btn.border_width || 2}px solid ${btn.border_color || '#ffffff'};` : '';

                // Font family
                const fontFamily = getFontFamily(btn.font);

                // Icon HTML
                let iconHtml = '';
                if (btn.icon && btn.icon !== 'none') {
                    iconHtml = `<i class="${btn.icon} ${btn.shape === 'bulat' ? 'text-lg' : 'text-base mr-2'}"></i>`;
                }

                // Bulat vs Persegi / Pill content
                let innerContent = '';
                if (btn.shape === 'bulat') {
                    innerContent = `
                        <div class="${shapeClass} ${shadowClass} w-full h-full flex items-center justify-center text-center font-bold transition transform group-hover/btn:scale-105 active:scale-95 cursor-pointer" style="background-color: ${btn.bg_color || '#25d366'}; color: ${btn.text_color || '#ffffff'}; font-size: ${btn.font_size || 16}px; ${borderStyle}">
                            ${iconHtml}
                        </div>
                    `;
                } else {
                    innerContent = `
                        <div class="${shapeClass} ${shadowClass} w-full h-full px-4 flex items-center justify-center text-center font-bold transition transform group-hover/btn:scale-102 active:scale-95 cursor-pointer select-none" style="background-color: ${btn.bg_color || '#25d366'}; color: ${btn.text_color || '#ffffff'}; font-size: ${btn.font_size || 14}px; font-family: ${fontFamily}; ${borderStyle}">
                            ${iconHtml}
                            <span class="tracking-wide truncate">${escapeHtml(btn.text || 'Tombol Aksi')}</span>
                        </div>
                    `;
                }

                box.innerHTML = `
                    <div class="absolute -inset-1.5 border-2 border-dashed border-amber-400/80 rounded-2xl pointer-events-none opacity-0 group-hover/btn:opacity-100 transition-opacity flex items-start justify-between p-1 z-20">
                        <span class="bg-amber-400 text-teal-950 text-[8px] font-black px-1.5 py-0.5 rounded shadow-xs">
                            #${index + 1}
                        </span>
                        <div class="flex items-center gap-1 pointer-events-auto">
                            <span class="bg-teal-950/90 text-amber-300 text-[8px] font-bold px-1.5 py-0.5 rounded shadow-xs flex items-center gap-1">
                                <i class="fas fa-up-down-left-right"></i> Geser
                            </span>
                            <button type="button" onmousedown="event.stopPropagation()" onclick="event.stopPropagation(); deleteButtonRow('${btn.id}')" title="Hapus Tombol Ini" class="w-5 h-5 rounded bg-rose-600 hover:bg-rose-700 text-white text-[9px] flex items-center justify-center shadow-xs cursor-pointer active:scale-90 transition">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                    ${innerContent}
                `;

                initDragForLayer(box, btn, 'button');
                container.appendChild(box);
            });
        }

        // ==========================================
        // 6. LOGIKA KOLOM TULISAN
        // ==========================================

        function syncJsonInput() {
            if (typeof framesData !== 'undefined' && framesData[currentActiveFrame]) {
                framesData[currentActiveFrame].texts = textItems;
            }
            const inp = document.getElementById('input-text-items-json');
            if (inp) inp.value = JSON.stringify(textItems);
        }

        function saveAllTextItems() {
            syncJsonInput();
            document.getElementById('form-pengaturan-text').submit();
        }

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
                box.style.zIndex = 30 + index;

                box.innerHTML = `
                    <div class="absolute -inset-1.5 border-2 border-dashed border-amber-400/80 rounded-xl pointer-events-none opacity-0 group-hover/drag:opacity-100 transition-opacity flex items-start justify-between p-1">
                        <span class="bg-amber-400 text-teal-950 text-[8px] font-black px-1.5 py-0.5 rounded shadow-xs">
                            #${index + 1}
                        </span>
                        <div class="flex items-center gap-1 pointer-events-auto">
                            <span class="bg-teal-950/90 text-amber-300 text-[8px] font-bold px-1.5 py-0.5 rounded shadow-xs flex items-center gap-1">
                                <i class="fas fa-up-down-left-right"></i> Geser
                            </span>
                            <button type="button" onmousedown="event.stopPropagation()" onclick="event.stopPropagation(); deleteRow('${item.id}')" title="Hapus Kolom Ini" class="w-5 h-5 rounded bg-rose-600 hover:bg-rose-700 text-white text-[9px] flex items-center justify-center shadow-xs cursor-pointer active:scale-90 transition">
                                <i class="fas fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <div style="color: ${item.color || '#ffffff'}; font-family: ${getFontFamily(item.font)}; text-align: ${item.align || 'center'}; font-size: ${item.size || 24}px; word-break: break-word;">
                        ${getFormatHtml(item.content, item.format)}
                    </div>
                `;

                initDragForLayer(box, item, 'text');
                container.appendChild(box);
            });
        }

        // ==========================================
        // 7. DRAGGABLE ENGINE DENGAN SMART SNAP & GARIS BANTU
        // ==========================================

        function initDragForLayer(box, item, layerType) {
            const container = document.getElementById('preview-screen-cover');
            const guideX = document.getElementById('snap-guide-x');
            const guideY = document.getElementById('snap-guide-y');
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
                initialTopPct  = item.posY || (layerType === 'text' ? 35 : (layerType === 'button' ? 80 : 50));

                // Auto switch to respective tab when interacting with element on canvas
                if (layerType === 'button' && currentActiveTab !== 'button') {
                    switchTab('button');
                } else if (layerType === 'video' && currentActiveTab !== 'video') {
                    switchTab('video');
                } else if (layerType === 'image' && currentActiveTab !== 'image') {
                    switchTab('image');
                } else if (layerType === 'text' && currentActiveTab !== 'text') {
                    switchTab('text');
                }

                box.style.transition = 'none';
                box.style.zIndex = 70;

                // Highlight baris
                let rowElId = `row-item-${item.id}`;
                if (layerType === 'image') rowElId = `img-row-item-${item.id}`;
                if (layerType === 'video') rowElId = `vid-row-item-${item.id}`;
                if (layerType === 'button') rowElId = `btn-row-item-${item.id}`;
                
                const rowEl = document.getElementById(rowElId);
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
                let newYPct = Math.min(Math.max(initialTopPct + deltaYPct, 2), 95);

                // SMART MAGNETIC SNAP KE CENTER (50% X dan 50% Y)
                const snapThreshold = 2.0; // Toleransi snap 2%
                let isSnappedX = false;
                let isSnappedY = false;

                if (Math.abs(newXPct - 50.0) < snapThreshold) {
                    newXPct = 50.0;
                    isSnappedX = true;
                }
                if (Math.abs(newYPct - 50.0) < snapThreshold) {
                    newYPct = 50.0;
                    isSnappedY = true;
                }

                // Tampilkan garis bantu snap saat mendekati / tepat di tengah
                if (guideX) guideX.style.display = isSnappedX ? 'block' : 'none';
                if (guideY) guideY.style.display = isSnappedY ? 'block' : 'none';

                item.posX = Math.round(newXPct * 10) / 10;
                item.posY = Math.round(newYPct * 10) / 10;

                box.style.left = `${item.posX}%`;
                box.style.top  = `${item.posY}%`;

                if (layerType === 'text') {
                    const labelX = document.getElementById(`label-posx-${item.id}`);
                    const labelY = document.getElementById(`label-posy-${item.id}`);
                    if (labelX) labelX.innerText = Math.round(item.posX) + '%';
                    if (labelY) labelY.innerText = Math.round(item.posY) + '%';
                } else if (layerType === 'button') {
                    const labelX = document.getElementById(`btn-label-posx-${item.id}`);
                    const labelY = document.getElementById(`btn-label-posy-${item.id}`);
                    if (labelX) labelX.innerText = Math.round(item.posX) + '%';
                    if (labelY) labelY.innerText = Math.round(item.posY) + '%';
                }
            }

            function endDrag() {
                if (!isDragging) return;
                isDragging = false;
                box.style.transition = '';
                box.style.zIndex = layerType === 'image' ? 20 : (layerType === 'video' ? 25 : (layerType === 'button' ? 35 : 30));

                // Sembunyikan garis snap
                if (guideX) guideX.style.display = 'none';
                if (guideY) guideY.style.display = 'none';

                let rowElId = `row-item-${item.id}`;
                if (layerType === 'image') rowElId = `img-row-item-${item.id}`;
                if (layerType === 'video') rowElId = `vid-row-item-${item.id}`;
                if (layerType === 'button') rowElId = `btn-row-item-${item.id}`;
                
                const rowEl = document.getElementById(rowElId);
                if (rowEl) {
                    setTimeout(() => rowEl.classList.remove('active-layer'), 800);
                }

                if (layerType === 'button') {
                    syncButtonJsonInput();
                } else if (layerType === 'video') {
                    syncVideoJsonInput();
                } else if (layerType === 'image') {
                    syncImageJsonInput();
                } else {
                    syncJsonInput();
                }
            }

            box.addEventListener('mousedown', startDrag);
            window.addEventListener('mousemove', moveDrag);
            window.addEventListener('mouseup', endDrag);

            box.addEventListener('touchstart', startDrag, { passive: false });
            window.addEventListener('touchmove', moveDrag, { passive: false });
            window.addEventListener('touchend', endDrag);
        }

        // Toggle Persistent Guidelines (Garis Bantu Kisi-Kisi)
        function togglePersistentGuides() {
            showPersistentGuides = !showPersistentGuides;
            const gridLines = document.querySelectorAll('.persistent-grid-line');
            const label = document.getElementById('label-guides-state');
            const btn = document.getElementById('btn-toggle-guides');

            gridLines.forEach(l => {
                l.style.opacity = showPersistentGuides ? '1' : '0';
            });

            if (label && btn) {
                if (showPersistentGuides) {
                    label.innerText = 'Garis Bantu: ON';
                    btn.className = 'px-2.5 py-1 rounded-xl text-[10px] font-black transition flex items-center gap-1.5 bg-sky-100 text-sky-800 border border-sky-200 hover:bg-sky-200 active:scale-95 shadow-2xs cursor-pointer';
                } else {
                    label.innerText = 'Garis Bantu: OFF';
                    btn.className = 'px-2.5 py-1 rounded-xl text-[10px] font-black transition flex items-center gap-1.5 bg-slate-100 text-slate-600 border border-slate-200 hover:bg-slate-200 active:scale-95 shadow-2xs cursor-pointer';
                }
            }
        }

        // ==========================================
        // 8. LOGIKA BACKGROUND BROSUR
        // ==========================================

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

        function updateLiveBgUrl(url) {
            const trimmed = url.trim();
            const finalUrl = trimmed ? trimmed : 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80';
            if (typeof framesData !== 'undefined' && framesData[currentActiveFrame]) {
                framesData[currentActiveFrame].bgUrl = finalUrl;
            }
            
            const thumbBox = document.getElementById('thumb-bg-box');
            const coverScreen = document.getElementById('preview-screen-cover');
            const bodyScreen = document.getElementById('preview-screen-body');

            if (thumbBox) thumbBox.style.backgroundImage = `url('${finalUrl}')`;
            if (coverScreen) coverScreen.style.backgroundImage = `url('${finalUrl}')`;
            if (bodyScreen) bodyScreen.style.backgroundImage = `url('${finalUrl}')`;
        }

        function updateLiveBgOpacity(val) {
            const pct = Math.round(val * 100);
            const valLabel = document.getElementById('val-bg-opacity');
            if (valLabel) valLabel.innerText = pct + '%';
            if (typeof framesData !== 'undefined' && framesData[currentActiveFrame]) {
                framesData[currentActiveFrame].bgOpacity = parseFloat(val);
            }

            const thumbOverlay = document.getElementById('thumb-bg-overlay');
            const coverOverlay = document.getElementById('preview-cover-overlay');
            const bodyOverlay = document.getElementById('preview-body-overlay');

            if (thumbOverlay) thumbOverlay.style.opacity = val;
            if (coverOverlay) coverOverlay.style.opacity = val;
            if (bodyOverlay) bodyOverlay.style.opacity = val;
        }

        function terapkanKoleksi(url) {
            switchTab('bg');
            const inp = document.getElementById('input-bg-url');
            if (inp) inp.value = url;
            updateLiveBgUrl(url);

            const formBg = document.getElementById('form-pengaturan-bg');
            if (formBg) formBg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // ==========================================
        // 9. LOGIKA SINKRONISASI WARNA BOTTOM BAR
        // ==========================================

        function syncBBarColor(type, value) {
            if (type === 'bg') {
                const textInput = document.getElementById('input-bbar-bg');
                if (textInput) textInput.value = value;
            } else if (type === 'bg_text') {
                const pickerInput = document.getElementById('input-bbar-bg-picker');
                if (pickerInput && value.startsWith('#') && (value.length === 4 || value.length === 7)) {
                    pickerInput.value = value;
                }
            } else if (type === 'text') {
                const textInput = document.getElementById('input-bbar-text');
                if (textInput) textInput.value = value;
            } else if (type === 'text_text') {
                const pickerInput = document.getElementById('input-bbar-text-picker');
                if (pickerInput && value.startsWith('#') && (value.length === 4 || value.length === 7)) {
                    pickerInput.value = value;
                }
            } else if (type === 'active') {
                const textInput = document.getElementById('input-bbar-active');
                if (textInput) textInput.value = value;
            } else if (type === 'active_text') {
                const pickerInput = document.getElementById('input-bbar-active-picker');
                if (pickerInput && value.startsWith('#') && (value.length === 4 || value.length === 7)) {
                    pickerInput.value = value;
                }
            }
            updateLiveBottomBarColors();
        }

        function updateLiveBottomBarColors() {
            const bg = document.getElementById('input-bbar-bg')?.value || '#022d27';
            const text = document.getElementById('input-bbar-text')?.value || '#ffffff';
            const active = document.getElementById('input-bbar-active')?.value || '#fbbf24';

            const bar = document.getElementById('sim-bottom-bar');
            const homeBtn = document.getElementById('sim-menu-home-btn');

            if (bar) {
                bar.style.backgroundColor = bg;
                bar.style.color = text;
            }
            if (homeBtn) {
                homeBtn.style.color = active;
            }
        }

        function applyBBarPreset(bg, text, active) {
            const bgInput = document.getElementById('input-bbar-bg');
            const bgPicker = document.getElementById('input-bbar-bg-picker');
            const textInput = document.getElementById('input-bbar-text');
            const textPicker = document.getElementById('input-bbar-text-picker');
            const activeInput = document.getElementById('input-bbar-active');
            const activePicker = document.getElementById('input-bbar-active-picker');

            if (bgInput) bgInput.value = bg;
            if (bgPicker && bg.startsWith('#')) bgPicker.value = bg;

            if (textInput) textInput.value = text;
            if (textPicker && text.startsWith('#')) textPicker.value = text;

            if (activeInput) activeInput.value = active;
            if (activePicker && active.startsWith('#')) activePicker.value = active;

            updateLiveBottomBarColors();
        }

        // ==========================================
        // 10. LOGIKA MASTER SAVE SELURUH PENGATURAN & TOAST
        // ==========================================

        function showToast(title, msg, isSuccess = true) {
            const toast = document.getElementById('brosur-toast');
            const toastCard = document.getElementById('brosur-toast-card');
            const toastTitle = document.getElementById('brosur-toast-title');
            const toastMsg = document.getElementById('brosur-toast-msg');
            const toastIcon = document.getElementById('brosur-toast-icon');

            if (!toast) return;

            if (toastTitle) toastTitle.innerText = title;
            if (toastMsg) toastMsg.innerText = msg;
            
            if (toastIcon) {
                if (isSuccess) {
                    toastIcon.className = 'w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 text-base';
                    toastIcon.innerHTML = '<i class="fas fa-check"></i>';
                    if (toastCard) toastCard.className = 'px-4 py-3 rounded-2xl bg-slate-900 text-white border border-emerald-500/50 shadow-2xl flex items-center gap-3';
                } else {
                    toastIcon.className = 'w-8 h-8 rounded-xl bg-rose-500/20 text-rose-400 flex items-center justify-center shrink-0 text-base';
                    toastIcon.innerHTML = '<i class="fas fa-circle-exclamation"></i>';
                    if (toastCard) toastCard.className = 'px-4 py-3 rounded-2xl bg-slate-900 text-white border border-rose-500/50 shadow-2xl flex items-center gap-3';
                }
            }

            toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
            }, 4000);
        }

        function saveAllSettings() {
            // 1. Sinkronkan semua data aktif
            syncJsonInput();
            syncImageJsonInput();
            syncVideoJsonInput();
            syncButtonJsonInput();

            const btn = document.getElementById('btn-master-save');
            const originalHtml = btn ? btn.innerHTML : '';
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin text-amber-300"></i> <span>Menyimpan...</span>';
            }

            const bgUrl = document.getElementById('input-bg-url')?.value || '';
            const bgOpacity = document.getElementById('input-bg-opacity')?.value || '0.88';
            const bbarBg = document.getElementById('input-bbar-bg')?.value || '#022d27';
            const bbarText = document.getElementById('input-bbar-text')?.value || '#ffffff';
            const bbarActive = document.getElementById('input-bbar-active')?.value || '#fbbf24';

            const formData = new FormData();
            formData.append('action_type', 'save_all');
            formData.append('ajax_mode', '1');
            formData.append('active_frame', currentActiveFrame);
            formData.append('active_tab', currentActiveTab);
            formData.append('bg_url', bgUrl);
            formData.append('bg_overlay_opacity', bgOpacity);
            formData.append('bottom_bar_bg_color', bbarBg);
            formData.append('bottom_bar_text_color', bbarText);
            formData.append('bottom_bar_active_color', bbarActive);
            formData.append('custom_text_items_json', JSON.stringify(textItems));
            formData.append('custom_image_items_json', JSON.stringify(imageItems));
            formData.append('custom_video_items_json', JSON.stringify(videoItems));
            formData.append('custom_button_items_json', JSON.stringify(buttonItems));

            // Upload bg_file jika ada
            const bgFileInput = document.getElementById('input-bg-file');
            if (bgFileInput && bgFileInput.files && bgFileInput.files[0]) {
                formData.append('bg_file', bgFileInput.files[0]);
            }

            fetch('admin-brosur-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
                if (res.success) {
                    if (res.clean_images && Array.isArray(res.clean_images)) {
                        imageItems = res.clean_images;
                        if (framesData[currentActiveFrame]) framesData[currentActiveFrame].images = imageItems;
                        renderImageRows();
                        renderSimImageLayers();
                    }
                    showToast('Alhamdulillah Berhasil!', res.message || 'Seluruh pengaturan frame berhasil disimpan.', true);
                } else {
                    showToast('Gagal Menyimpan', res.message || 'Terjadi kesalahan saat menyimpan.', false);
                }
            })
            .catch(err => {
                console.error('AJAX save error, submitting form fallback:', err);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
                // Fallback ke form submit standar
                const formFallback = document.getElementById('form-master-save');
                if (formFallback) {
                    document.getElementById('master-input-frame').value = currentActiveFrame;
                    document.getElementById('master-input-tab').value = currentActiveTab;
                    document.getElementById('master-input-bg-url').value = bgUrl;
                    document.getElementById('master-input-bg-opacity').value = bgOpacity;
                    document.getElementById('master-input-bbar-bg').value = bbarBg;
                    document.getElementById('master-input-bbar-text').value = bbarText;
                    document.getElementById('master-input-bbar-active').value = bbarActive;
                    document.getElementById('master-input-text-json').value = JSON.stringify(textItems);
                    document.getElementById('master-input-img-json').value = JSON.stringify(imageItems);
                    document.getElementById('master-input-vid-json').value = JSON.stringify(videoItems);
                    document.getElementById('master-input-btn-json').value = JSON.stringify(buttonItems);
                    formFallback.submit();
                }
            });
        }

        // Inisialisasi awal saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            // Cek hash URL jika ada (#bg, #text, #image, #video, #button)
            const hash = window.location.hash.replace('#', '');
            if (['bg', 'text', 'image', 'video', 'button'].includes(hash)) {
                currentActiveTab = hash;
            }
            switchFrame(currentActiveFrame);
            switchTab(currentActiveTab);
        });
    </script>

    <!-- FLOATING TOAST NOTIFICATION -->
    <div id="brosur-toast" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 pointer-events-none max-w-sm">
        <div id="brosur-toast-card" class="px-4 py-3 rounded-2xl bg-slate-900 text-white border border-slate-700 shadow-2xl flex items-center gap-3">
            <div id="brosur-toast-icon" class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 text-base">
                <i class="fas fa-check"></i>
            </div>
            <div class="flex-1">
                <p id="brosur-toast-title" class="text-xs font-black text-white">Berhasil Disimpan</p>
                <p id="brosur-toast-msg" class="text-[11px] text-slate-300 mt-0.5 leading-snug">Pengaturan brosur berhasil diperbarui.</p>
            </div>
        </div>
    </div>

    <!-- HIDDEN FORM FOR MASTER POST FALLBACK -->
    <form id="form-master-save" method="POST" action="" style="display:none;">
        <input type="hidden" name="action_type" value="save_all">
        <input type="hidden" name="active_frame" id="master-input-frame" value="<?= $active_frame ?>">
        <input type="hidden" name="active_tab" id="master-input-tab" value="<?= $current_tab ?>">
        <input type="hidden" name="bg_url" id="master-input-bg-url" value="">
        <input type="hidden" name="bg_overlay_opacity" id="master-input-bg-opacity" value="">
        <input type="hidden" name="bottom_bar_bg_color" id="master-input-bbar-bg" value="">
        <input type="hidden" name="bottom_bar_text_color" id="master-input-bbar-text" value="">
        <input type="hidden" name="bottom_bar_active_color" id="master-input-bbar-active" value="">
        <input type="hidden" name="custom_text_items_json" id="master-input-text-json" value="">
        <input type="hidden" name="custom_image_items_json" id="master-input-img-json" value="">
        <input type="hidden" name="custom_video_items_json" id="master-input-vid-json" value="">
        <input type="hidden" name="custom_button_items_json" id="master-input-btn-json" value="">
    </form>
</body>
</html>


