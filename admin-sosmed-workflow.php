<?php
session_start();
$is_authorized = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $is_authorized = true;
} elseif (isset($_SESSION['ustadz_logged_in']) && $_SESSION['ustadz_logged_in'] === true) {
    $is_authorized = true;
}

if (!$is_authorized) {
    header("Location: login-ustadz.php");
    exit;
}

require_once 'koneksi.php';

// Path konfigurasi JSON
$config_path = 'config-sosmed-workflow.json';
$config = [
    'telegram_token' => '',
    'telegram_channel_id' => '',
    'fb_page_id' => '',
    'fb_access_token' => '',
    'frame_path' => ''
];
if (file_exists($config_path)) {
    $config = array_merge($config, json_decode(file_get_contents($config_path), true));
}

// Self-healing: Table sosmed_campaign
$conn->query("CREATE TABLE IF NOT EXISTS sosmed_campaign (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL,
    santri_id INT NOT NULL,
    pilar_konten VARCHAR(100) NOT NULL,
    tema_foto VARCHAR(255) NOT NULL,
    foto_mentah VARCHAR(255) NULL,
    foto_jadi VARCHAR(255) NULL,
    quote_text TEXT NULL,
    status_proses ENUM('menunggu_foto', 'siap_desain', 'selesai') DEFAULT 'menunggu_foto',
    status_kirim_ortu ENUM('menunggu', 'terkirim') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE
)");

// --- HANDLE POST ACTIONS ---
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // 1. Simpan Konfigurasi API & Bingkai
    if ($_POST['action'] === 'save_settings') {
        $config['telegram_token'] = $_POST['telegram_token'] ?? '';
        $config['telegram_channel_id'] = $_POST['telegram_channel_id'] ?? '';
        $config['fb_page_id'] = $_POST['fb_page_id'] ?? '';
        $config['fb_access_token'] = $_POST['fb_access_token'] ?? '';
        
        // Handle upload frame
        if (isset($_FILES['frame_file']) && $_FILES['frame_file']['error'] === UPLOAD_ERR_OK) {
            $frame_ext = strtolower(pathinfo($_FILES['frame_file']['name'], PATHINFO_EXTENSION));
            if ($frame_ext === 'png') {
                $upload_dir = 'uploads/sosmed/';
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                
                $frame_path = $upload_dir . 'frame_template.png';
                if (move_uploaded_file($_FILES['frame_file']['tmp_name'], $frame_path)) {
                    $config['frame_path'] = $frame_path;
                }
            } else {
                $error = "Format frame harus PNG transparan.";
            }
        }
        
        file_put_contents($config_path, json_encode($config, JSON_PRETTY_PRINT));
        $message = "Pengaturan berhasil disimpan.";
    }

    // 2. Jadwalkan Kampanye Baru
    if ($_POST['action'] === 'add_campaign') {
        $tanggal = $conn->real_escape_string($_POST['tanggal']);
        $santri_id = (int)$_POST['santri_id'];
        $pilar_konten = $conn->real_escape_string($_POST['pilar_konten']);
        $tema_foto = $conn->real_escape_string($_POST['tema_foto']);
        
        if ($santri_id > 0 && !empty($tanggal)) {
            $sql = "INSERT INTO sosmed_campaign (tanggal, santri_id, pilar_konten, tema_foto, status_proses)
                    VALUES ('$tanggal', $santri_id, '$pilar_konten', '$tema_foto', 'menunggu_foto')
                    ON DUPLICATE KEY UPDATE 
                        santri_id = $santri_id, 
                        pilar_konten = '$pilar_konten', 
                        tema_foto = '$tema_foto',
                        status_proses = 'menunggu_foto'";
            if ($conn->query($sql)) {
                $message = "Kampanye berhasil dijadwalkan.";
            } else {
                $error = "Gagal menjadwalkan kampanye: " . $conn->error;
            }
        } else {
            $error = "Data tanggal dan santri wajib diisi.";
        }
    }

    // 3. Hapus Kampanye
    if ($_POST['action'] === 'delete_campaign') {
        $id = (int)$_POST['campaign_id'];
        // Hapus file fisik jika ada
        $res = $conn->query("SELECT foto_mentah, foto_jadi FROM sosmed_campaign WHERE id = $id");
        if ($res && $row = $res->fetch_assoc()) {
            if (!empty($row['foto_mentah']) && file_exists($row['foto_mentah'])) unlink($row['foto_mentah']);
            if (!empty($row['foto_jadi']) && file_exists($row['foto_jadi'])) unlink($row['foto_jadi']);
        }
        $conn->query("DELETE FROM sosmed_campaign WHERE id = $id");
        $message = "Kampanye berhasil dihapus.";
    }

    // 4. Generate AI Quote & Desain Poster GD
    if ($_POST['action'] === 'generate_design') {
        $id = (int)$_POST['campaign_id'];
        
        // Ambil Data Kampanye
        $res = $conn->query("SELECT c.*, s.nama_lengkap FROM sosmed_campaign c JOIN buku_induk_santri s ON c.santri_id = s.id WHERE c.id = $id");
        $c = $res->fetch_assoc();
        
        if ($c && !empty($c['foto_mentah']) && file_exists($c['foto_mentah'])) {
            // Load key
            if (file_exists('config-key.php')) {
                require_once 'config-key.php';
            }
            $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
            $gasUrl = defined('GEMINI_GAS_URL') ? GEMINI_GAS_URL : '';
            
            if (empty($apiKey)) {
                $error = "API Key Gemini belum diset di config-key.php.";
            } else {
                // Tanya Gemini untuk Quote Harian
                $prompt = "Buat sebuah quote motivasi islami atau nasihat karakter singkat (maksimal 20 kata) dalam Bahasa Indonesia. Quote ini harus disesuaikan dengan nama santri bernama '{$c['nama_lengkap']}' yang difoto sedang melakukan kegiatan '{$c['tema_foto']}' di bawah pilar pendidikan '{$c['pilar_konten']}'. Gunakan nada hangat, puitis, dan membanggakan untuk dibaca orang tuanya.";
                
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
                $useTunnel = !empty($gasUrl);
                $ch = curl_init();
                
                if ($useTunnel) {
                    curl_setopt($ch, CURLOPT_URL, $gasUrl);
                    $payload = ["prompt" => $prompt, "apiKey" => $apiKey];
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                } else {
                    curl_setopt($ch, CURLOPT_URL, $url);
                    $payload = [
                        "contents" => [["parts" => [["text" => $prompt]]]]
                    ];
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                }
                
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $quote = '';
                if ($useTunnel) {
                    $res_j = json_decode($response, true);
                    $quote = $res_j['result'] ?? '';
                } else {
                    if ($httpCode === 200) {
                        $result = json_decode($response, true);
                        $quote = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    }
                }
                
                $quote = trim(str_replace(['"', '“', '”'], '', $quote));
                
                if (empty($quote)) {
                    $quote = "Barakallah ananda {$c['nama_lengkap']}, teruslah istiqomah dalam kebaikan di pondok tercinta.";
                }
                
                // --- PROSES DESAIN PHP GD ---
                // 1. Download Font Montserrat jika belum ada
                $font_path = 'uploads/sosmed/Montserrat-Regular.ttf';
                if (!file_exists($font_path)) {
                    if (!file_exists('uploads/sosmed/')) mkdir('uploads/sosmed/', 0777, true);
                    $font_url = 'https://github.com/google/fonts/raw/main/ofl/montserrat/Montserrat-Regular.ttf';
                    $font_data = @file_get_contents($font_url);
                    if ($font_data) file_put_contents($font_path, $font_data);
                }
                
                // 2. Load Foto Mentah
                $info = getimagesize($c['foto_mentah']);
                $raw_img = null;
                if ($info['mime'] === 'image/jpeg') {
                    $raw_img = imagecreatefromjpeg($c['foto_mentah']);
                } elseif ($info['mime'] === 'image/png') {
                    $raw_img = imagecreatefrompng($c['foto_mentah']);
                } elseif ($info['mime'] === 'image/webp') {
                    $raw_img = imagecreatefromwebp($c['foto_mentah']);
                }
                
                if ($raw_img) {
                    $src_w = imagesx($raw_img);
                    $src_h = imagesy($raw_img);
                    $square_size = 1080;
                    $canvas = imagecreatetruecolor($square_size, $square_size);
                    
                    // Crop center square
                    if ($src_w > $src_h) {
                        $src_x = ($src_w - $src_h) / 2;
                        $src_y = 0;
                        $src_side = $src_h;
                    } else {
                        $src_x = 0;
                        $src_y = ($src_h - $src_w) / 2;
                        $src_side = $src_w;
                    }
                    imagecopyresampled($canvas, $raw_img, 0, 0, $src_x, $src_y, $square_size, $square_size, $src_side, $src_side);
                    
                    // 3. Draw Dark Gradient Box at bottom
                    $dark_overlay = imagecolorallocatealpha($canvas, 15, 23, 42, 40); // Indigo-950, ~70% opacity
                    imagefilledrectangle($canvas, 0, 750, 1080, 1080, $dark_overlay);
                    
                    // 4. Overlay Bingkai PNG jika ada
                    if (!empty($config['frame_path']) && file_exists($config['frame_path'])) {
                        $frame = imagecreatefrompng($config['frame_path']);
                        imagecopyresampled($canvas, $frame, 0, 0, 0, 0, 1080, 1080, imagesx($frame), imagesy($frame));
                        imagedestroy($frame);
                    } else {
                        // Watermark Teks default jika tidak ada bingkai
                        $color_gold = imagecolorallocate($canvas, 251, 191, 36);
                        if (file_exists($font_path)) {
                            imagettftext($canvas, 16, 0, 40, 60, $color_gold, $font_path, "VILLA QURAN INDONESIA");
                        }
                    }
                    
                    // 5. Draw Teks (Quote, Nama Santri, Pilar)
                    $color_white = imagecolorallocate($canvas, 255, 255, 255);
                    $color_gold = imagecolorallocate($canvas, 251, 191, 36);
                    
                    if (file_exists($font_path)) {
                        // Bungkus/Wrap Quote Teks
                        $words = explode(' ', $quote);
                        $lines = [];
                        $current_line = '';
                        foreach ($words as $word) {
                            $test = $current_line . ($current_line ? ' ' : '') . $word;
                            $box = imagettfbbox(18, 0, $font_path, $test);
                            if (($box[2] - $box[0]) > 900) {
                                $lines[] = $current_line;
                                $current_line = $word;
                            } else {
                                $current_line = $test;
                            }
                        }
                        if ($current_line) $lines[] = $current_line;
                        
                        // Gambar baris quote
                        $start_y = 820;
                        foreach ($lines as $line) {
                            imagettftext($canvas, 18, 0, 50, $start_y, $color_white, $font_path, $line);
                            $start_y += 32;
                        }
                        
                        // Gambar Nama & Pilar
                        imagettftext($canvas, 14, 0, 50, 1010, $color_gold, $font_path, $c['nama_lengkap'] . " (" . $c['pilar_konten'] . ")");
                    } else {
                        // Fallback jika font gagal diunduh
                        imagestring($canvas, 5, 50, 800, $quote, $color_white);
                        imagestring($canvas, 4, 50, 950, $c['nama_lengkap'] . " (" . $c['pilar_konten'] . ")", $color_gold);
                    }
                    
                    // Save file jadi
                    $output_filename = 'jadi_' . $c['tanggal'] . '_' . uniqid() . '.jpg';
                    $output_path = 'uploads/sosmed/' . $output_filename;
                    
                    if (imagejpeg($canvas, $output_path, 90)) {
                        $quote_esc = $conn->real_escape_string($quote);
                        $conn->query("UPDATE sosmed_campaign SET foto_jadi = '$output_path', quote_text = '$quote_esc', status_proses = 'selesai' WHERE id = $id");
                        $message = "Desain poster berhasil di-generate.";
                    } else {
                        $error = "Gagal memproses gambar GD.";
                    }
                    
                    imagedestroy($canvas);
                    imagedestroy($raw_img);
                } else {
                    $error = "Format file foto mentah tidak valid atau rusak.";
                }
            }
        } else {
            $error = "Foto mentah tidak ditemukan.";
        }
    }

    // 5. Kirim via WhatsApp Fonnte (ke Orang Tua)
    if ($_POST['action'] === 'send_whatsapp') {
        $id = (int)$_POST['campaign_id'];
        
        $res = $conn->query("SELECT c.*, s.nama_lengkap, s.no_hp_ayah, s.no_hp_ibu 
                             FROM sosmed_campaign c 
                             JOIN buku_induk_santri s ON c.santri_id = s.id 
                             WHERE c.id = $id");
        $c = $res->fetch_assoc();
        
        if ($c && !empty($c['foto_jadi'])) {
            // Ambil nomor HP yang ada
            $no_hp = !empty($c['no_hp_ayah']) ? $c['no_hp_ayah'] : (!empty($c['no_hp_ibu']) ? $c['no_hp_ibu'] : '');
            
            if (empty($no_hp)) {
                $error = "Nomor WhatsApp orang tua tidak terdaftar di buku induk.";
            } else {
                if (file_exists('config-key.php')) require 'config-key.php';
                $fonnte_token = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : '';
                
                if (empty($fonnte_token)) {
                    $error = "Token Fonnte belum dikonfigurasi di config-key.php.";
                } else {
                    // Dapatkan URL absolut untuk foto
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                    $img_url = $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/' . $c['foto_jadi'];
                    
                    $caption = "Assalamu'alaikum Ayah/Bunda {$c['nama_lengkap']}.\n\nMasya Allah, berikut kiriman dokumentasi ananda hari ini di Villa Quran:\n\n*\"{$c['quote_text']}\"*\n\nSilakan unduh gambar di atas untuk dijadikan status WhatsApp atau dibagikan ke media sosial Ayah/Bunda ya sebagai bentuk apresiasi untuk ananda. Terima kasih atas dukungannya! 🌸";
                    
                    // Kirim pesan Fonnte
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                      CURLOPT_URL => 'https://api.fonnte.com/send',
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => '',
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_FOLLOWLOCATION => true,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => 'POST',
                      CURLOPT_POSTFIELDS => array(
                        'target' => $no_hp,
                        'message' => $caption,
                        'url' => $img_url,
                        'delay' => '2',
                      ),
                      CURLOPT_HTTPHEADER => array(
                        "Authorization: $fonnte_token"
                      ),
                    ));
                    
                    $response = curl_exec($curl);
                    curl_close($curl);
                    
                    $res_arr = json_decode($response, true);
                    if (isset($res_arr['status']) && $res_arr['status'] === true) {
                        $conn->query("UPDATE sosmed_campaign SET status_kirim_ortu = 'terkirim' WHERE id = $id");
                        $message = "WhatsApp berhasil dikirim ke orang tua!";
                    } else {
                        $error = "Fonnte API Error: " . ($res_arr['reason'] ?? $response);
                    }
                }
            }
        }
    }

    // 6. Posting ke Telegram Channel
    if ($_POST['action'] === 'send_telegram') {
        $id = (int)$_POST['campaign_id'];
        
        $res = $conn->query("SELECT c.*, s.nama_lengkap FROM sosmed_campaign c JOIN buku_induk_santri s ON c.santri_id = s.id WHERE c.id = $id");
        $c = $res->fetch_assoc();
        
        if ($c && !empty($c['foto_jadi']) && !empty($config['telegram_token']) && !empty($config['telegram_channel_id'])) {
            $filepath = realpath($c['foto_jadi']);
            $caption = "Apresiasi Harian Santri Villa Quran 🌸\n\n*\"{$c['quote_text']}\"*\n\nAnanda: {$c['nama_lengkap']}\nPilar: {$c['pilar_konten']}";
            
            $url = "https://api.telegram.org/bot" . $config['telegram_token'] . "/sendPhoto";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'chat_id' => $config['telegram_channel_id'],
                'photo' => new CURLFile($filepath),
                'caption' => $caption,
                'parse_mode' => 'Markdown'
            ]);
            
            $res_tg = curl_exec($ch);
            curl_close($ch);
            
            $res_arr = json_decode($res_tg, true);
            if (isset($res_arr['ok']) && $res_arr['ok'] === true) {
                $message = "Poster berhasil diposting ke Channel Telegram!";
            } else {
                $error = "Telegram API Error: " . ($res_arr['description'] ?? $res_tg);
            }
        } else {
            $error = "Token Telegram atau ID Channel belum disetting.";
        }
    }

    // 7. Posting ke Facebook Page
    if ($_POST['action'] === 'send_facebook') {
        $id = (int)$_POST['campaign_id'];
        $res = $conn->query("SELECT c.*, s.nama_lengkap FROM sosmed_campaign c JOIN buku_induk_santri s ON c.santri_id = s.id WHERE c.id = $id");
        $c = $res->fetch_assoc();
        
        if ($c && !empty($c['foto_jadi']) && !empty($config['fb_page_id']) && !empty($config['fb_access_token'])) {
            $filepath = realpath($c['foto_jadi']);
            $caption = "Apresiasi Harian Santri Villa Quran 🌸\n\n\"{$c['quote_text']}\"\n\nAnanda: {$c['nama_lengkap']}\nPilar: {$c['pilar_konten']}";
            
            $url = "https://graph.facebook.com/v18.0/" . $config['fb_page_id'] . "/photos";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'source' => new CURLFile($filepath),
                'message' => $caption,
                'access_token' => $config['fb_access_token']
            ]);
            
            $res_fb = curl_exec($ch);
            curl_close($ch);
            
            $res_arr = json_decode($res_fb, true);
            if (isset($res_arr['id'])) {
                $message = "Poster berhasil diposting ke Halaman Facebook!";
            } else {
                $error = "Facebook API Error: " . ($res_arr['error']['message'] ?? $res_fb);
            }
        } else {
            $error = "Facebook Page ID atau Access Token belum disetting.";
        }
    }
}

// --- FETCH FORMS DATA ---
// List santri aktif
$santri_list = [];
$res_santri = $conn->query("SELECT id, nama_lengkap, kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
if ($res_santri) {
    while ($row = $res_santri->fetch_assoc()) $santri_list[] = $row;
}

// List pilar konten
$pilars = [
    '1. Kemandirian',
    '2. Karya Dakwah Digital',
    '3. Persaudaraan',
    '4. Kedekatan dengan Guru',
    '5. Hafalan (Tahfidz)',
    '6. Prestasi Akademik dan Non Akademik',
    '7. Kegiatan Belajar Mengajar'
];

// Query daftar kampanye
$campaigns = [];
$res_c = $conn->query("SELECT c.*, s.nama_lengkap, s.no_hp_ayah, s.no_hp_ibu 
                       FROM sosmed_campaign c 
                       JOIN buku_induk_santri s ON c.santri_id = s.id 
                       ORDER BY c.tanggal DESC");
if ($res_c) {
    while($row = $res_c->fetch_assoc()) $campaigns[] = $row;
}

$active_tab = $_GET['tab'] ?? 'jadwal';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Kendali Sosmed Workflow | Villa Quran</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <?php $active_menu = 'sosmed_workflow'; include 'sidebar-marketing.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200">
            <div class="flex items-center space-x-3">
                <i class="fas fa-route text-2xl text-indigo-600"></i>
                <h2 class="font-bold text-slate-800 text-lg">Sosmed Autopilot Workflow</h2>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 bg-indigo-100 text-indigo-800 rounded-full">
                Kempen Apresiasi Ortu
            </span>
        </header>

        <!-- MAIN -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
            
            <!-- Alert Status -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 bg-emerald-100 border-l-4 border-emerald-500 text-emerald-800 text-xs font-semibold rounded-lg flex items-center shadow-sm">
                    <i class="fas fa-check-circle mr-2 text-base"></i> <?= $message ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-rose-100 border-l-4 border-rose-500 text-rose-800 text-xs font-semibold rounded-lg flex items-center shadow-sm">
                    <i class="fas fa-exclamation-triangle mr-2 text-base"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- TABS NAVIGATION -->
            <div class="flex border-b border-slate-200 mb-6 space-x-2">
                <a href="?tab=jadwal" class="px-4 py-2 text-sm font-bold border-b-2 transition <?= $active_tab === 'jadwal' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                    <i class="fas fa-calendar-alt mr-1.5"></i> 1. Jadwal Kampanye
                </a>
                <a href="?tab=desain" class="px-4 py-2 text-sm font-bold border-b-2 transition <?= $active_tab === 'desain' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                    <i class="fas fa-magic mr-1.5"></i> 2. Proses & Desain AI
                </a>
                <a href="?tab=kirim" class="px-4 py-2 text-sm font-bold border-b-2 transition <?= $active_tab === 'kirim' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                    <i class="fas fa-paper-plane mr-1.5"></i> 3. Publikasi & Logs
                </a>
                <a href="?tab=api" class="px-4 py-2 text-sm font-bold border-b-2 transition <?= $active_tab === 'api' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600' ?>">
                    <i class="fas fa-cog mr-1.5"></i> 4. API & Bingkai
                </a>
            </div>

            <!-- TABS CONTENT -->
            <?php if ($active_tab === 'jadwal'): ?>
                <!-- TAB 1: SCHEDULE -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Penjadwal Baru -->
                    <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-5 shadow-sm h-fit">
                        <h4 class="font-bold text-slate-700 text-sm border-b pb-3 mb-4"><i class="fas fa-calendar-plus text-indigo-600 mr-1.5"></i>Jadwalkan Kampanye Baru</h4>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="add_campaign">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Pilih Santri Terjepret</label>
                                <select name="santri_id" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <option value="">-- Pilih Santri --</option>
                                    <?php foreach ($santri_list as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_lengkap']) ?> (Kelas <?= htmlspecialchars($s['kelas_sekarang']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Pilar Konten VQ</label>
                                <select name="pilar_konten" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <?php foreach ($pilars as $p): ?>
                                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Deskripsi Tema Foto (Tugas Musyrif)</label>
                                <textarea name="tema_foto" rows="2" placeholder="Contoh: Sedang memegang mikrofon mempraktikkan cara podcast dakwah..." class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 rounded-lg shadow transition">
                                Jadwalkan Tugas
                            </button>
                        </form>
                    </div>

                    <!-- Daftar Tugas -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                        <h4 class="font-bold text-slate-700 text-sm p-5 border-b bg-slate-50/50 flex justify-between items-center">
                            <span><i class="fas fa-list text-slate-500 mr-1.5"></i>Log Agenda Kampanye</span>
                            <span class="text-xs bg-indigo-100 text-indigo-800 font-bold px-2 py-0.5 rounded-full">Total: <?= count($campaigns) ?></span>
                        </h4>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs divide-y divide-slate-200">
                                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                    <tr>
                                        <th class="px-5 py-3">Tanggal</th>
                                        <th class="px-5 py-3">Santri</th>
                                        <th class="px-5 py-3">Pilar / Tugas Tema</th>
                                        <th class="px-5 py-3 text-center">Status Foto</th>
                                        <th class="px-5 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                                    <?php foreach ($campaigns as $c): ?>
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="px-5 py-4 font-bold text-indigo-900"><?= htmlspecialchars($c['tanggal']) ?></td>
                                            <td class="px-5 py-4">
                                                <div class="font-bold text-slate-700"><?= htmlspecialchars($c['nama_lengkap']) ?></div>
                                            </td>
                                            <td class="px-5 py-4 space-y-1">
                                                <span class="text-[10px] bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded-full inline-block"><?= htmlspecialchars($c['pilar_konten']) ?></span>
                                                <p class="text-[10px] text-slate-400 italic"><?= htmlspecialchars($c['tema_foto']) ?></p>
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <?php if($c['status_proses'] === 'menunggu_foto'): ?>
                                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 font-bold rounded-full text-[10px]">Menunggu Musyrif</span>
                                                <?php elseif($c['status_proses'] === 'siap_desain'): ?>
                                                    <span class="px-2.5 py-1 bg-blue-100 text-blue-800 font-bold rounded-full text-[10px]">Terkirim (Siap Desain)</span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-bold rounded-full text-[10px]">Selesai (Desain Jadi)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-5 py-4 text-center">
                                                <form method="POST" onsubmit="return confirm('Hapus tugas kampanye ini?');">
                                                    <input type="hidden" name="action" value="delete_campaign">
                                                    <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                                                    <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-bold">
                                                        <i class="fas fa-trash-alt"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($active_tab === 'desain'): ?>
                <!-- TAB 2: AI PROCESS & GD DESIGN -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    $siap_desain = array_filter($campaigns, function($item) { return $item['status_proses'] === 'siap_desain'; });
                    if (!empty($siap_desain)): 
                        foreach ($siap_desain as $c): 
                    ?>
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col justify-between">
                            <div class="p-5 space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($c['nama_lengkap']) ?></h4>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Tanggal: <?= htmlspecialchars($c['tanggal']) ?></p>
                                    </div>
                                    <span class="text-[9px] bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"><?= htmlspecialchars($c['pilar_konten']) ?></span>
                                </div>

                                <div class="aspect-video w-full rounded-xl overflow-hidden border border-slate-100 bg-slate-100">
                                    <img src="<?= htmlspecialchars($c['foto_mentah']) ?>" class="w-full h-full object-cover">
                                </div>

                                <div class="bg-slate-50 p-3 rounded-lg border border-slate-150 text-[10px] text-slate-500 italic">
                                    Tema Penugasan: "<?= htmlspecialchars($c['tema_foto']) ?>"
                                </div>
                            </div>
                            
                            <div class="bg-slate-50 p-4 border-t border-slate-100 flex gap-2">
                                <form method="POST" class="w-full">
                                    <input type="hidden" name="action" value="generate_design">
                                    <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 rounded-lg transition shadow-sm flex items-center justify-center space-x-1.5">
                                        <i class="fas fa-magic"></i> <span>Buat Poster & Quote AI</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-10 text-center text-slate-400">
                            <i class="fas fa-images text-4xl text-slate-300 mb-2"></i>
                            <p class="text-xs">Tidak ada tugas foto mentah dari Musyrif yang siap di-desain saat ini.</p>
                            <p class="text-[10px] text-slate-400/80 mt-1">Gunakan tab 1 untuk menjadwalkan, lalu Musyrif akan mengambil foto via PWA.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($active_tab === 'kirim'): ?>
                <!-- TAB 3: PUBLICATION & LOGS -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    $selesai_camp = array_filter($campaigns, function($item) { return $item['status_proses'] === 'selesai'; });
                    if (!empty($selesai_camp)): 
                        foreach ($selesai_camp as $c): 
                    ?>
                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col justify-between">
                            <div class="p-5 space-y-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($c['nama_lengkap']) ?></h4>
                                        <p class="text-[10px] text-slate-400 mt-0.5">Tanggal: <?= htmlspecialchars($c['tanggal']) ?></p>
                                    </div>
                                    <span class="text-[9px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded-full uppercase tracking-wider"><?= htmlspecialchars($c['pilar_konten']) ?></span>
                                </div>

                                <div class="aspect-square w-full rounded-xl overflow-hidden border border-slate-100 shadow-sm relative">
                                    <img src="<?= htmlspecialchars($c['foto_jadi']) ?>" class="w-full h-full object-cover">
                                </div>

                                <div class="bg-indigo-50/50 p-3 rounded-lg border border-indigo-100 text-[10px] text-indigo-900 leading-relaxed font-semibold">
                                    Quote AI: "<?= htmlspecialchars($c['quote_text']) ?>"
                                </div>
                            </div>
                            
                            <!-- Aksi Kirim -->
                            <div class="bg-slate-50 p-4 border-t border-slate-100 flex flex-col gap-2">
                                <!-- WhatsApp to Parent -->
                                <div class="flex justify-between items-center border-b pb-2 mb-2">
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">WA Ortu</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full <?= $c['status_kirim_ortu'] == 'terkirim' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                        <?= $c['status_kirim_ortu'] == 'terkirim' ? 'Terkirim' : 'Menunggu' ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-3 gap-2">
                                    <!-- WA -->
                                    <form method="POST">
                                        <input type="hidden" name="action" value="send_whatsapp">
                                        <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[10px] py-2 rounded-lg transition shadow-sm flex items-center justify-center space-x-1">
                                            <i class="fab fa-whatsapp"></i> <span>Kirim WA</span>
                                        </button>
                                    </form>
                                    <!-- Telegram -->
                                    <form method="POST">
                                        <input type="hidden" name="action" value="send_telegram">
                                        <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="w-full bg-sky-500 hover:bg-sky-600 text-white font-bold text-[10px] py-2 rounded-lg transition shadow-sm flex items-center justify-center space-x-1">
                                            <i class="fab fa-telegram-plane"></i> <span>Telegram</span>
                                        </button>
                                    </form>
                                    <!-- FB -->
                                    <form method="POST">
                                        <input type="hidden" name="action" value="send_facebook">
                                        <input type="hidden" name="campaign_id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold text-[10px] py-2 rounded-lg transition shadow-sm flex items-center justify-center space-x-1">
                                            <i class="fab fa-facebook-f"></i> <span>FB Page</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else: 
                    ?>
                        <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-10 text-center text-slate-400">
                            <i class="fas fa-check-double text-4xl text-slate-300 mb-2"></i>
                            <p class="text-xs">Belum ada tugas poster yang selesai di-desain.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($active_tab === 'api'): ?>
                <!-- TAB 4: API & FRAME SETTINGS -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- API Tokens Form -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                        <h4 class="font-bold text-slate-700 text-sm border-b pb-3 mb-4"><i class="fas fa-key text-indigo-600 mr-1.5"></i>Kredensial API & Token Saluran</h4>
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="save_settings">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Token Telegram Bot</label>
                                <input type="text" name="telegram_token" value="<?= htmlspecialchars($config['telegram_token']) ?>" placeholder="Bot Token dari @BotFather" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">ID Channel Telegram</label>
                                <input type="text" name="telegram_channel_id" value="<?= htmlspecialchars($config['telegram_channel_id']) ?>" placeholder="Contoh: @channel_villaquran" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Facebook Page ID</label>
                                <input type="text" name="fb_page_id" value="<?= htmlspecialchars($config['fb_page_id']) ?>" placeholder="Masukkan ID Page Facebook Sekolah" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Facebook Page Access Token</label>
                                <textarea name="fb_access_token" rows="3" placeholder="Page Token dari Facebook Developer App" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= htmlspecialchars($config['fb_access_token']) ?></textarea>
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 rounded-lg shadow transition">
                                Simpan API Token
                            </button>
                        </form>
                    </div>

                    <!-- Frame Overlay Config -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                        <h4 class="font-bold text-slate-700 text-sm border-b pb-3"><i class="fas fa-image text-indigo-600 mr-1.5"></i>Bingkai / Watermark Frame</h4>
                        <form method="POST" enctype="multipart/form-data" class="space-y-4 border-b pb-4 mb-4">
                            <input type="hidden" name="action" value="save_settings">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Unggah Bingkai Template PNG (1080x1080 - Transparan)</label>
                                <input type="file" name="frame_file" accept="image/png" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <p class="text-[9px] text-slate-400 mt-1">Harus berupa PNG transparan agar foto mentah di belakangnya kelihatan.</p>
                            </div>
                            <button type="submit" class="bg-slate-700 hover:bg-slate-800 text-white text-xs font-bold px-4 py-2 rounded-lg shadow transition">
                                Upload & Pasang Frame
                            </button>
                        </form>

                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pratinjau Bingkai Saat Ini</span>
                            <div class="aspect-square w-48 rounded-xl border border-slate-200 bg-slate-100 overflow-hidden flex items-center justify-center">
                                <?php if (!empty($config['frame_path']) && file_exists($config['frame_path'])): ?>
                                    <img src="<?= htmlspecialchars($config['frame_path']) ?>" class="w-full h-full object-contain">
                                <?php else: ?>
                                    <span class="text-xs text-slate-400 italic">Belum ada bingkai kustom (default teks aktif)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>

</body>
</html>
