<?php
/**
 * ========================================================
 * AI AGENT WORKER (FULL OTONOM)
 * ========================================================
 * File ini dirancang untuk dijalankan oleh CRON JOB server setiap jam (0 * * * *).
 * Agent akan mengecek waktu dan tanggal secara mandiri untuk memutuskan tugas apa yang harus dikerjakan.
 */

set_time_limit(600); // Beri waktu 10 menit karena tugas bulanan lumayan panjang
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Disable output buffering to support real-time log streaming
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no'); // Disable buffering for Nginx/LiteSpeed
    @ini_set('zlib.output_compression', 0);
    @ini_set('implicit_flush', 1);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
}

// Atur zona waktu ke Waktu Indonesia Barat (WIB) agar jadwal akurat
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/koneksi.php';
if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

// Helper function to prevent "MySQL server has gone away" during long-running API tasks
function pastikanKoneksiDb() {
    global $conn, $host, $username, $password, $database;
    if (!$conn || !@$conn->ping()) {
        if ($conn) { @$conn->close(); }
        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            if ($host === 'localhost' || $host === '127.0.0.1') {
                $conn = new mysqli($host, 'root', '', $database);
                if ($conn->connect_error) {
                    logAgent("Re-koneksi database gagal (fallback local): " . $conn->connect_error);
                }
            } else {
                logAgent("Re-koneksi database gagal: " . $conn->connect_error);
            }
        }
        // Matikan mode strict exception PHP 8.1+ agar tidak Error 500 jika query gagal
        mysqli_report(MYSQLI_REPORT_OFF);
    }
}

pastikanKoneksiDb();
// Self-healing: Pastikan kolom pendukung ada di tabel artikel
@$conn->query("ALTER TABLE artikel ADD COLUMN status_broadcast ENUM('menunggu', 'terkirim') DEFAULT 'menunggu' AFTER status");
@$conn->query("ALTER TABLE artikel ADD COLUMN published_at DATETIME NULL AFTER status");
@$conn->query("ALTER TABLE artikel ADD COLUMN meta_title VARCHAR(255) AFTER published_at");
@$conn->query("ALTER TABLE artikel ADD COLUMN meta_description TEXT AFTER meta_title");
@$conn->query("ALTER TABLE artikel ADD COLUMN meta_keywords VARCHAR(255) AFTER meta_description");
@$conn->query("ALTER TABLE artikel ADD COLUMN copywriting_promo TEXT AFTER meta_keywords");

// Self-healing untuk tabel leads & footprints agar query tidak crash
@$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
@$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");
@$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(100) DEFAULT '' AFTER jenis_lead");
@$conn->query("ALTER TABLE visitor_footprints ADD COLUMN campaign VARCHAR(100) AFTER source");

$APP_URL = "https://villaquranindonesia.com"; // Gunakan URL absolut agar link broadcast tidak pecah saat dijalankan via Cron

// Tentukan GAS_URL secara dinamis untuk meminimalkan kegagalan loopback cURL
if (php_sapi_name() === 'cli') {
    $GAS_URL = $APP_URL . "/api-gemini.php";
} else {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $http_host = $_SERVER['HTTP_HOST'];
    $uri_dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    $GAS_URL = $protocol . $http_host . $uri_dir . '/api-gemini.php';
}

$FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";

$log_file = __DIR__ . '/agent_cron_log.txt';
$monthly_log_file = __DIR__ . '/agent_monthly_log.txt';
$daily_log_file = __DIR__ . '/agent_daily_log.txt';

// Waktu saat ini bagi Sang Agent
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_day = date('d');
$current_hour = date('H');
$current_minute = date('i');
$current_time_hm = date('H:i');

// Fungsi Logging Otonom dengan real-time flush
function logAgent($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] 🤖 AGENT: $msg\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry . "<br>";
    if (php_sapi_name() !== 'cli') {
        flush();
    }
}

// Fungsi Jeda Khusus untuk menghindari Gemini API Rate Limit & Web Timeout
function jedaAgent($detik) {
    if (php_sapi_name() === 'cli') {
        sleep($detik);
    }
}

// 1. CEK OTORITAS DARI PUSAT KENDALI
$autopilot = file_exists(__DIR__ . '/autopilot_status.txt') ? trim(file_get_contents(__DIR__ . '/autopilot_status.txt')) : 'OFF';
$force = isset($_GET['force']) ? $_GET['force'] : '';
if ($autopilot !== 'ON' && empty($force)) {
    die("Agent sedang dinonaktifkan (OFF) dari Pusat Kendali. Menunggu izin Bos...");
}

// Fungsi Komunikasi ke Otak AI Utama (Gemini)
function mikirKeGemini($payload) {
    global $conn;
    
    // Tutup koneksi database sebelum long-running cURL request
    if (isset($conn) && $conn) {
        @$conn->close();
        $conn = null;
    }
    
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $gasUrl = defined('GEMINI_GAS_URL') ? GEMINI_GAS_URL : '';
    
    // 1. Ekstrak prompt dari payload
    $prompt = '';
    $systemCommand = '';
    $leadsData = [];
    
    if (isset($payload['leads']) && is_array($payload['leads'])) {
        foreach ($payload['leads'] as $lead) {
            if (isset($lead['jenis_lead']) && $lead['jenis_lead'] === 'SYSTEM_COMMAND') {
                $systemCommand = $lead['sumber_info'] ?? '';
            } else {
                $leadsData[] = $lead;
            }
        }
    }
    
    $prompt = $payload['prompt'] ?? $payload['sumber_info'] ?? '';
    if (!empty($systemCommand)) {
        $prompt = $systemCommand . "\n\n" . $prompt;
    }
    if (!empty($leadsData)) {
        $prompt .= "\n\nBerikut data leads untuk dianalisis:\n" . json_encode($leadsData, JSON_PRETTY_PRINT);
    }
    $prompt = trim($prompt);
    
    $resultText = '';
    
    // 2. COBA KONEKSI LANGSUNG TERLEBIH DAHULU (Lebih Cepat & Handal)
    if (!empty($apiKey)) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        $gemini_payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gemini_payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if (!$curlError && $httpCode === 200) {
            $res_arr = json_decode($response, true);
            $resultText = $res_arr['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
    }
    
    // 3. FALLBACK: JIKA GAGAL/BLOKIR, COBA TUNNELING VIA GAS (JIKA DIKONFIGURASI)
    if (empty($resultText) && !empty($gasUrl)) {
        $ch = curl_init($gasUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        // Kirim payload asli + injeksi apiKey
        $payload_gas = $payload;
        $payload_gas['apiKey'] = $apiKey;
        $payload_gas['prompt'] = $prompt;
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_gas));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$curlError && $httpCode === 200) {
            $res_arr = json_decode($response, true);
            $resultText = $res_arr['result'] ?? '';
            if (empty($resultText)) {
                $resultText = $response;
            }
        }
    }
    
    // Buka kembali koneksi database setelah request selesai
    pastikanKoneksiDb();
    
    if (!empty($resultText)) {
        return ['status' => 'success', 'result' => $resultText];
    } else {
        return ['status' => 'error', 'message' => 'Gagal mendapatkan respon dari Gemini'];
    }
}

// Fungsi pembantu untuk mengunduh dan menyimpan gambar secara lokal
function unduhDanSimpanGambarLokal($url) {
    if (empty($url)) return '';
    
    // Pastikan folder uploads ada
    $target_dir = __DIR__ . '/uploads/';
    if (!file_exists($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }
    
    // Tentukan ekstensi file
    $ext = 'jpg';
    if (preg_match('/\.(png|gif|jpeg|webp)/i', $url, $matches)) {
        $ext = strtolower($matches[1]);
    }
    
    $filename = 'cover_' . md5($url) . '.' . $ext;
    $target_path = $target_dir . $filename;
    
    // Jika file sudah ada, langsung gunakan
    if (file_exists($target_path)) {
        return 'uploads/' . $filename;
    }
    
    // Unduh gambar
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $img_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($img_data && $http_code == 200) {
        if (@file_put_contents($target_path, $img_data)) {
            return 'uploads/' . $filename;
        }
    }
    
    return $url;
}

function dapatkanGambarPixabay($keyword) {
    $pixabay_key = defined('PIXABAY_API_KEY') ? PIXABAY_API_KEY : '';
    if (empty($pixabay_key)) {
        return '';
    }
    
    // Ambil kata kunci pencarian utama (sebelum koma)
    $clean_keywords = explode(',', $keyword);
    $primary_keyword = trim($clean_keywords[0]);
    if (empty($primary_keyword)) {
        return '';
    }
    
    // Tambahkan embel-embel bernuansa Islami jika belum ada kata kunci Islami/religius
    $query_string = $primary_keyword;
    if (!preg_match('/(muslim|islam|hijab|mosque|quran|ramadan|allah|indonesia)/i', $query_string)) {
        $query_string .= " muslim islamic";
    }
    $query = urlencode($query_string);
    
    $url = "https://pixabay.com/api/?key=" . $pixabay_key . "&q=" . $query . "&image_type=photo&orientation=horizontal&safesearch=true&per_page=5";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['hits']) && count($data['hits']) > 0) {
            // Ambil acak dari 5 teratas agar bervariasi
            $idx = rand(0, min(count($data['hits']) - 1, 4));
            $raw_url = $data['hits'][$idx]['webformatURL'] ?? '';
            return unduhDanSimpanGambarLokal($raw_url);
        } else {
            // Fallback: Jika tidak ditemukan dengan query gabungan, cari dengan kata kunci umum Islami
            $fallback_url = "https://pixabay.com/api/?key=" . $pixabay_key . "&q=" . urlencode("muslim islamic") . "&image_type=photo&orientation=horizontal&safesearch=true&per_page=5";
            $ch = curl_init($fallback_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            $res_fallback = curl_exec($ch);
            curl_close($ch);
            
            if ($res_fallback) {
                $data_fallback = json_decode($res_fallback, true);
                if (isset($data_fallback['hits']) && count($data_fallback['hits']) > 0) {
                    $idx = rand(0, min(count($data_fallback['hits']) - 1, 4));
                    $raw_url = $data_fallback['hits'][$idx]['webformatURL'] ?? '';
                    return unduhDanSimpanGambarLokal($raw_url);
                }
            }
        }
    }
    return '';
}

// Self-healing: Perbaiki gambar cover artikel yang kosong, rusak, atau get temporer
function perbaikiGambarCoverRusak() {
    global $conn;
    pastikanKoneksiDb();
    
    $res = $conn->query("SELECT id, judul, gambar_cover FROM artikel WHERE gambar_cover LIKE '%pixabay.com/get/%' OR gambar_cover = '' OR gambar_cover IS NULL");
    if ($res && $res->num_rows > 0) {
        logAgent("Menemukan " . $res->num_rows . " artikel dengan gambar cover kosong atau Pixabay get temporer. Memulai perbaikan...");
        while ($row = $res->fetch_assoc()) {
            $id = $row['id'];
            $judul = $row['judul'];
            logAgent("Memperbaiki gambar cover untuk artikel ID $id: '$judul'...");
            
            // Ambil kata kunci dari judul
            $clean_title = preg_replace('/[^A-Za-z0-9\s]+/', '', $judul);
            $words = explode(' ', $clean_title);
            $keywords = [];
            foreach ($words as $w) {
                if (strlen($w) > 3) $keywords[] = $w;
                if (count($keywords) >= 3) break;
            }
            $query = implode(' ', $keywords);
            if (empty($query)) {
                $query = "islamic parenting";
            }
            
            // Dapatkan gambar baru dari Pixabay (yang akan diunduh lokal oleh fungsi baru)
            $new_cover = dapatkanGambarPixabay($query);
            
            // Fallback jika gagal
            if (empty($new_cover)) {
                $new_cover = "https://loremflickr.com/800/600/islamic,parenting";
                $new_cover = unduhDanSimpanGambarLokal($new_cover);
            }
            
            if (!empty($new_cover)) {
                $new_cover_esc = $conn->real_escape_string($new_cover);
                $conn->query("UPDATE artikel SET gambar_cover = '$new_cover_esc' WHERE id = $id");
                logAgent("-> Gambar cover diperbaiki menjadi: '$new_cover'");
            }
        }
    }
}
perbaikiGambarCoverRusak();

// =========================================================================================
// TUGAS BULANAN (Tiap Tanggal 1, Jam 05:00) : PERSONA, TREND MAKRO & KALENDER 30 HARI
// =========================================================================================
$monthly_done = false;
if (file_exists($monthly_log_file)) {
    if (strpos(file_get_contents($monthly_log_file), "SUCCESS_$current_month") !== false) $monthly_done = true;
}

if ($current_day == '01' && $current_hour >= '05' && !$monthly_done) {
    logAgent("======= MEMULAI TUGAS BULANAN ($current_month) =======");
    
    // Kumpulkan Data (Maks 100 terbaru)
    $leads = []; $footprints = [];
    pastikanKoneksiDb();
    $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 100");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;
    
    pastikanKoneksiDb();
    $resF = $conn->query("SELECT device, location, source, campaign FROM visitor_footprints ORDER BY id DESC LIMIT 100");
    if($resF) while($r = $resF->fetch_assoc()) $footprints[] = $r;

    // 1A. MIKIR PERSONA
    logAgent("Agent Analis: Merumuskan Buyer Persona dari jejak pengunjung...");
    $payloadPersona = $leads;
    $prompt_persona_default = "Buat laporan analisa Buyer Persona terstruktur (TOFU, MOFU, BOFU) dalam format Markdown.";
    $prompt_persona = file_exists('prompt_persona.txt') ? file_get_contents('prompt_persona.txt') : $prompt_persona_default;
    if (count($footprints) > 0) array_unshift($payloadPersona, ["jenis_lead" => "DATA_JEJAK_PENGUNJUNG_MATA_AI", "sumber_info" => json_encode($footprints), "status" => "TOLONG_ANALISA_JUGA"]);
    
    array_unshift($payloadPersona, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => $prompt_persona,
        "status" => "URGENT"
    ]);

    $dataPersona = mikirKeGemini(['leads' => $payloadPersona, 'type' => 'persona']);
    if (isset($dataPersona['status']) && $dataPersona['status'] === 'success') {
        file_put_contents(__DIR__ . '/saved_persona.txt', $dataPersona['result']);
        logAgent("✅ Persona bulan ini berhasil dirumuskan.");
        file_put_contents($monthly_log_file, "SUCCESS_$current_month\n", FILE_APPEND);
        logAgent("Tugas Bulanan Selesai dengan Sukses! Agent kembali istirahat.");
    } else {
        logAgent("❌ Gagal merumuskan Persona.");
    }
    
    exit; 
}

// =========================================================================================
// TUGAS HARIAN (Tiap Hari, Jam 07:00) : ARTIKEL SEO & BROADCAST KURIR
// =========================================================================================
$daily_done = false;
if (file_exists($daily_log_file)) {
    if (strpos(file_get_contents($daily_log_file), "SUCCESS_$today") !== false) $daily_done = true;
}

$force_seo = ($force === 'seo');
if (($current_hour >= '07' || $force_seo) && (!$daily_done || $force_seo)) {
    logAgent("======= MEMULAI TUGAS HARIAN ($today) =======");

    // 0. MIKIR TREND HARIAN
    logAgent("Agent Trend Scout: Menganalisa tren harian...");
    $prompt_trend_macro_default = "Anda adalah seorang SEO & Market Trend Analyst. Tugas Anda adalah memberikan analisis trend konten parenting Islam untuk anak remaja usia 10 sampai dengan 15 tahun dari satu hari terakhir disemua plaform sosmed dan google search. Analisis harus meliputi: 1. Tema yang paling trending, 2. Angel/sudut pandang konten, 4. Hashtag yang digunakan, 5. Keyword Google Search yang sedang tren, serta hal penting lainnya yang relevan. Sajikan dalam format Markdown.";
    $prompt_trend_macro = file_exists('prompt_trend_macro.txt') ? file_get_contents('prompt_trend_macro.txt') : $prompt_trend_macro_default;
    $payloadTrendMakro = [
        [
            "jenis_lead" => "SYSTEM_COMMAND",
            "sumber_info" => $prompt_trend_macro,
            "status" => "URGENT"
        ]
    ];
    $dataTrendMakro = mikirKeGemini(['leads' => $payloadTrendMakro, 'type' => 'trend_macro']);
    if (isset($dataTrendMakro['status']) && $dataTrendMakro['status'] === 'success') {
        file_put_contents(__DIR__ . '/saved_trends_macro.txt', $dataTrendMakro['result']);
        logAgent("✅ Laporan Tren Harian berhasil dibuat.");
    } else {
        logAgent("❌ Gagal membuat laporan Tren Harian.");
    }

    jedaAgent(5);

    // 1. MIKIR TREND MIKRO (HARIAN)
    logAgent("Agent Trend Scout: Menganalisa tren mikro untuk hari ini...");
    $tema_bulanan = file_exists(__DIR__ . '/saved_trends_macro.txt') ? file_get_contents(__DIR__ . '/saved_trends_macro.txt') : 'Pendidikan Anak Islami';
    $prompt_trend_micro_default = "Anda adalah seorang SEO & Content Strategist. Tema besar bulan ini adalah: \n\n{{THEME}}\n\n Tugas Anda adalah mencari 1 SUDUT PANDANG (angle) atau topik spesifik yang sedang hangat dibicarakan dalam 24 jam terakhir terkait tema tersebut. Berikan 1 rekomendasi judul artikel yang viral dan 3 keyword turunan yang relevan. Sajikan dalam format Markdown.";
    $prompt_trend_micro_raw = file_exists(__DIR__ . '/prompt_trend_micro.txt') ? file_get_contents(__DIR__ . '/prompt_trend_micro.txt') : $prompt_trend_micro_default;
    $prompt_trend_micro = str_replace('{{THEME}}', $tema_bulanan, $prompt_trend_micro_raw);
    $payloadTrendMikro = [
        [
            "jenis_lead" => "SYSTEM_COMMAND",
            "sumber_info" => $prompt_trend_micro,
            "status" => "URGENT"
        ]
    ];
    $dataTrendMikro = mikirKeGemini(['leads' => $payloadTrendMikro, 'type' => 'trend_micro']);
    if (isset($dataTrendMikro['status']) && $dataTrendMikro['status'] === 'success') {
        file_put_contents(__DIR__ . '/saved_trends_micro.txt', $dataTrendMikro['result']);
        logAgent("✅ Laporan Tren Mikro harian berhasil dibuat.");
    } else {
        logAgent("❌ Gagal membuat laporan Tren Mikro.");
    }

    jedaAgent(5);

    // 2. MIKIR HOOK & KEYWORD EXPLORER (HARIAN)
    logAgent("Agent Hook & Keyword Explorer: Meriset opsi judul hook viral dan keyword...");
    $trend_macro = file_exists(__DIR__ . '/saved_trends_macro.txt') ? file_get_contents(__DIR__ . '/saved_trends_macro.txt') : 'Tidak ada laporan tren makro.';
    $leads = [];
    pastikanKoneksiDb();
    $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 50");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;
    
    $prompt_hook_explorer_default = "Anda adalah AI Agent Riset Hook & Keyword SEO. Tugas Anda adalah meriset dan memilih judul hook yang bisa viral serta keyword yang tepat sesuai algoritma Google Search terbaru, berdasarkan hasil riset Trend Scout berikut:\n\n{{TREND_SCOUT}}\n\nKetentuan:\n1. Target audiens: Orang tua dengan anak remaja usia 10-15 tahun, dalam konteks Islamic Parenting / Pendidikan Remaja Muslim.\n2. Riset 5 opsi judul hook viral yang memicu rasa penasaran/emosi (menggunakan formula hook seperti pengakuan, kontradiktif, pertanyaan retoris, dsb).\n3. Tentukan keyword utama & turunan yang memiliki potensi trafik tinggi dan relevan sesuai algoritma Google Search terbaru.\n4. Pilih 1 kombinasi terbaik yang paling berpotensi viral dan memiliki search intent yang kuat untuk ditulis hari ini.\n5. Berikan output dalam format JSON murni tanpa markdown (tanpa ```json). Format JSON harus tepat seperti ini:\n{\n  \"selected_topic\": \"Topik singkat dari judul terpilih\",\n  \"selected_title\": \"Judul Hook Terpilih yang Bisa Viral\",\n  \"selected_keyword\": \"keyword utama, keyword turunan 1, keyword turunan 2\",\n  \"pixabay_search_query\": \"1-3 English keywords for Pixabay image search (e.g. 'muslim teen', 'islamic family', 'stressed student' related to the topic)\",\n  \"report\": \"# Laporan Riset Hook & Keyword\\n\\n(Sajikan laporan lengkap riset Anda dalam format markdown di sini. Laporkan 5 opsi judul hook beserta keyword masing-masing, analisis kecocokan algoritma Google Search, serta alasan kuat pemilihan 1 judul terbaik untuk hari ini.)\"\n}";
    
    $prompt_hook_explorer_raw = file_exists(__DIR__ . '/prompt_hook_explorer.txt') ? file_get_contents(__DIR__ . '/prompt_hook_explorer.txt') : $prompt_hook_explorer_default;
    $prompt_hook_explorer = str_replace('{{TREND_SCOUT}}', $trend_macro, $prompt_hook_explorer_raw);
    
    $payloadExplorer = $leads;
    array_unshift($payloadExplorer, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => $prompt_hook_explorer,
        "status" => "URGENT"
    ]);

    $dataExplorer = mikirKeGemini(['leads' => $payloadExplorer, 'type' => 'hook_explorer']);
    if (isset($dataExplorer['status']) && $dataExplorer['status'] === 'success') {
        $rawResult = trim($dataExplorer['result'] ?? '');
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $rawResult, $matches)) {
            $cleanJson = trim($matches[1]);
        } else {
            $cleanJson = $rawResult;
        }
        $obj = json_decode($cleanJson, true);
        if ($obj && isset($obj['selected_title'])) {
            $selected_keyword = $obj['selected_keyword'] ?? '';
            $pixabay_query = $obj['pixabay_search_query'] ?? $selected_keyword;
            $selected_image = '';
            if (!empty($pixabay_query)) {
                $selected_image = dapatkanGambarPixabay($pixabay_query);
            }
            file_put_contents(__DIR__ . '/today_seo_task.json', json_encode([
                'selected_topic' => $obj['selected_topic'] ?? '',
                'selected_title' => $obj['selected_title'] ?? '',
                'selected_keyword' => $selected_keyword,
                'pixabay_search_query' => $obj['pixabay_search_query'] ?? '',
                'selected_image' => $selected_image
            ], JSON_PRETTY_PRINT));
            file_put_contents(__DIR__ . '/saved_kalender.txt', $obj['report'] ?? $dataExplorer['result']);
            logAgent("✅ Riset Hook & Keyword harian berhasil disimpan.");
        } else {
            logAgent("⚠️ Format JSON Hook Explorer tidak sesuai. Menyimpan hasil mentah ke saved_kalender.txt.");
            file_put_contents(__DIR__ . '/saved_kalender.txt', $dataExplorer['result']);
        }
    } else {
        logAgent("❌ Gagal menjalankan Hook & Keyword Explorer.");
    }

    jedaAgent(5);

    // 3. Ambil topik/judul/keyword hari ini dari Hook & Keyword Explorer atau fallback
    $topic = "Keistimewaan Menghafal Al-Quran"; // Fallback topic
    $judul = "Keutamaan Menjadi Hafidz Quran di Usia Belia"; // Fallback judul
    $keyword = "pesantren tahfidz, hafal quran"; // Fallback keyword

    if (file_exists(__DIR__ . '/today_seo_task.json')) {
        $seoTaskData = json_decode(file_get_contents(__DIR__ . '/today_seo_task.json'), true);
        if ($seoTaskData && !empty($seoTaskData['selected_title'])) {
            $topic = $seoTaskData['selected_topic'] ?? $topic;
            $judul = $seoTaskData['selected_title'] ?? $judul;
            $keyword = $seoTaskData['selected_keyword'] ?? $keyword;
            logAgent("Agent Penulis: Menemukan data SEO hari ini dari today_seo_task.json -> Judul: '$judul', Keyword: '$keyword'");
        }
    } else {
        // Fallback jika json tidak ditemukan, coba cari di saved_kalender.txt (jika masih ada format baris tanggal, demi backward compatibility)
        if (file_exists(__DIR__ . '/saved_kalender.txt')) {
            $kalender = file_get_contents(__DIR__ . '/saved_kalender.txt');
            $lines = explode("\n", $kalender);
            foreach($lines as $line) {
                if (strpos($line, $today) !== false) {
                    $cols = array_map('trim', explode('|', $line));
                    if(count($cols) >= 6) {
                        $topic = $cols[2];
                        $judul = $cols[3];
                        $keyword = $cols[4];
                        logAgent("Agent Penulis: Menemukan topik hari ini dari kalender -> Judul: '$judul', Keyword: '$keyword'");
                        break;
                    }
                }
            }
        } else {
            logAgent("Agent Penulis: Data target SEO tidak ditemukan, menggunakan fallback.");
        }
    }

    // 4. MIKIR ARTIKEL SEO
    logAgent("Mulai menulis draf artikel: $judul...");
    $leads = []; 
    pastikanKoneksiDb();
    $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 50");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;

    $payloadSEO = $leads;
    $prompt_seo_default = "ATURAN WAJIB: KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON MURNI TANPA MARKDOWN (TANPA ```json). FORMAT: {\"judul\":\"{{JUDUL}}\", \"meta_title\":\"...\", \"meta_description\":\"...\", \"meta_keywords\":\"{{KEYWORD}}\", \"copywriting_promo\":\"(Buat 1 postingan copywriting promosi WhatsApp/sosmed yang persuasif, memicu rasa penasaran, menggunakan formula viral hook, dan diakhiri dengan placeholder {{LINK_AFILIASI}})\", \"konten\":\"(isi html artikel lengkap)\"}. Bahas topik: {{TOPIK}}. PERTIMBANGKAN JUGA insight dari laporan tren terbaru berikut: \n\n{{TREND_MIKRO}}";
    $prompt_seo_raw = file_exists(__DIR__ . '/prompt_seo.txt') ? file_get_contents(__DIR__ . '/prompt_seo.txt') : $prompt_seo_default;
    $trend_mikro_report = file_exists(__DIR__ . '/saved_trends_micro.txt') ? file_get_contents(__DIR__ . '/saved_trends_micro.txt') : 'Tidak ada laporan tren mikro.';
    $replacements = ['{{JUDUL}}' => $judul, '{{KEYWORD}}' => $keyword, '{{TOPIK}}' => $topic, '{{TREND_MIKRO}}' => $trend_mikro_report];
    $prompt_seo = str_replace(array_keys($replacements), array_values($replacements), $prompt_seo_raw);

    array_unshift($payloadSEO, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => $prompt_seo,
        "status" => "URGENT"
    ]);

    $dataSEO = mikirKeGemini(['leads' => $payloadSEO, 'type' => 'seo', 'topik' => $topic, 'judul' => $judul, 'keyword' => $keyword]);
    $newArticleId = '';

    if (isset($dataSEO['status']) && $dataSEO['status'] === 'success') {
        $rawResult = trim($dataSEO['result'] ?? '');
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $rawResult, $matches)) {
            $cleanJson = trim($matches[1]);
        } else {
            $cleanJson = $rawResult;
        }
        $obj = json_decode($cleanJson, true);

        $konten = $obj['konten'] ?? $obj['content'] ?? $obj['isi'] ?? '';
        $judul_art = $obj['judul'] ?? $obj['title'] ?? $judul;

        if ($obj && !empty($konten)) {
            pastikanKoneksiDb();
            $j = $conn->real_escape_string($judul_art);
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $j)));
            $k = $conn->real_escape_string($konten);
            
            // Auto Cover Gambar
            $gambar_cover = '';
            
            // Opsi 1: Coba gunakan gambar terpilih dari today_seo_task.json (Pixabay)
            if (isset($seoTaskData) && !empty($seoTaskData['selected_image'])) {
                $gambar_cover = $seoTaskData['selected_image'];
                logAgent("Menggunakan gambar cover dari riset Hook & Keyword: '$gambar_cover'");
            }
            
            // Opsi 2: Coba ambil langsung dari Pixabay jika today_seo_task tidak ada tapi ada keyword
            if (empty($gambar_cover) && !empty($keyword)) {
                logAgent("Mencoba mengambil gambar cover baru dari Pixabay untuk kata kunci: '$keyword'...");
                $pixabay_q = $seoTaskData['pixabay_search_query'] ?? $keyword;
                $gambar_cover = dapatkanGambarPixabay($pixabay_q);
            }
            
            // Fallback: Jika gagal atau Pixabay API Key kosong, gunakan generator gambar bebas hak cipta LoremFlickr
            // (Kita hindari scanning folder uploads/ umum agar tidak memunculkan gambar internal yang tidak nyambung seperti struk atau toilet)
            if (empty($gambar_cover)) {
                logAgent("Menggunakan fallback generator gambar bebas hak cipta LoremFlickr...");
                $gambar_cover = "https://loremflickr.com/800/600/islamic,parenting";
            }
            
            // Escape optional meta fields to prevent SQL injection or syntax breakages
            $meta_title = $conn->real_escape_string($obj['meta_title'] ?? $obj['judul'] ?? $judul);
            $meta_description = $conn->real_escape_string($obj['meta_description'] ?? '');
            $meta_keywords = $conn->real_escape_string($obj['meta_keywords'] ?? $keyword);
            $gambar_cover = $conn->real_escape_string($gambar_cover);
            $copywriting_promo = $obj['copywriting_promo'] ?? $obj['copywriting'] ?? '';
            $copywriting_promo_esc = $conn->real_escape_string($copywriting_promo);

            $sql = "INSERT INTO artikel (judul, slug, konten, status, meta_title, meta_description, meta_keywords, gambar_cover, copywriting_promo) 
                    VALUES ('$j', '$slug', '$k', 'publish', '$meta_title', '$meta_description', '$meta_keywords', '$gambar_cover', '$copywriting_promo_esc')";
            if ($conn->query($sql) === TRUE) {
                $newArticleId = $conn->insert_id;
                logAgent("✅ Artikel otomatis dipublikasikan! (ID: $newArticleId)");
            } else {
                logAgent("❌ Gagal menyimpan artikel ke database: " . $conn->error);
                logAgent("Query: " . $sql);
            }
        } else {
            logAgent("❌ Gagal men-decode JSON artikel dari Gemini atau field 'konten' kosong.");
            logAgent("Raw Result (200 char): " . substr($dataSEO['result'] ?? '', 0, 200));
        }
    } else {
        logAgent("❌ Gagal mendapatkan respon sukses dari Gemini untuk pembuatan artikel.");
        if (isset($dataSEO['message'])) {
            logAgent("Pesan error Gemini: " . $dataSEO['message']);
        }
    }

    // 5. BROADCAST PUBLISHER (SEKARANG LEBIH PINTAR)
    logAgent("Agent Publisher: Mencari artikel yang belum disebar...");
    pastikanKoneksiDb();
    $res_artikel_kirim = $conn->query("SELECT id, judul, konten, copywriting_promo FROM artikel WHERE status = 'publish' AND status_broadcast = 'menunggu' ORDER BY COALESCE(published_at, created_at) ASC LIMIT 1");
    
    if ($res_artikel_kirim && $res_artikel_kirim->num_rows > 0) {
        $artikel_kirim = $res_artikel_kirim->fetch_assoc();
        $artikel_id_kirim = $artikel_kirim['id'];
        $artikel_judul_kirim = $artikel_kirim['judul'];

        logAgent("Menemukan artikel (ID: $artikel_id_kirim) '$artikel_judul_kirim'. Memulai proses broadcast...");

        $copywriting_template = trim($artikel_kirim['copywriting_promo'] ?? '');

        if (empty($copywriting_template)) {
            // Buat copywriting persuasif dengan AI berdasarkan isi artikel (jika belum ada)
            $artikel_konten_kirim = $artikel_kirim['konten'] ?? '';
            $konten_plain = strip_tags($artikel_konten_kirim);
            $konten_teaser = (mb_strlen($konten_plain) > 2500) ? mb_substr($konten_plain, 0, 2500) . '...' : $konten_plain;

            logAgent("Agent Publisher: Merumuskan copywriting persuasif pembuka (viral hook) via AI...");
            $prompt_copywriting = "Anda adalah seorang Copywriter & Publisher Specialist. Tugas Anda adalah membuat 1 postingan copywriting WhatsApp/sosmed (micro-copywriting) untuk mempromosikan artikel berikut:\n\n"
                . "Judul Artikel: $artikel_judul_kirim\n"
                . "Isi Artikel (ringkasan): \n$konten_teaser\n\n"
                . "Ketentuan Copywriting:\n"
                . "1. Gunakan formula HOOK yang sangat menarik, menantang, kontradiktif, atau memicu emosi/keingintahuan pembaca (terutama kalangan orang tua Muslim dengan anak usia 10-15 tahun).\n"
                . "2. Berikan cuplikan/teaser berupa pertanyaan menarik atau 1 solusi penting dari artikel tersebut, namun JANGAN berikan seluruh isi solusi agar pembaca penasaran dan terdorong mengklik tautan artikel.\n"
                . "3. Gunakan sapaan yang sopan, bersahabat, dibumbui emoji yang relevan dan proporsional (jangan berlebihan).\n"
                . "4. Tata letak penulisan harus rapi dengan paragraf renggang (gunakan enter ganda) agar mudah dibaca di layar HP/WhatsApp.\n"
                . "5. Berikan CTA (Call to Action) yang jelas untuk mengklik tautan selengkapnya.\n"
                . "6. Di akhir tulisan, sertakan placeholder untuk link afiliasi dalam bentuk: {{LINK_AFILIASI}}\n"
                . "7. Output HANYA berupa teks copywriting siap kirim tanpa penjelasan tambahan, tanpa tanda kutip pembungkus, dan tanpa format markdown ```.";

            $payload_copy = [
                [
                    "jenis_lead" => "SYSTEM_COMMAND",
                    "sumber_info" => $prompt_copywriting,
                    "status" => "URGENT"
                ]
            ];

            $dataCopywriting = mikirKeGemini(['leads' => $payload_copy, 'type' => 'copywriting_publisher']);
            
            if (isset($dataCopywriting['status']) && $dataCopywriting['status'] === 'success') {
                $copywriting_template = trim($dataCopywriting['result'] ?? '');
                logAgent("✅ Copywriting promosi berhasil dibuat oleh AI.");
                
                // Simpan copywriting yang baru dibuat ke database agar bisa digunakan nanti
                $copy_esc = $conn->real_escape_string($copywriting_template);
                $conn->query("UPDATE artikel SET copywriting_promo = '$copy_esc' WHERE id = $artikel_id_kirim");
            } else {
                logAgent("⚠️ Gagal membuat copywriting promosi via AI. Menggunakan template fallback.");
            }
        } else {
            logAgent("Menggunakan copywriting promo dari database (pre-generated).");
        }

        // Jika gagal atau kosong, gunakan template fallback yang lebih dinamis dan persuasif
        if (empty($copywriting_template)) {
            $copywriting_template = "Banyak orang tua yang belum tahu rahasia ini... 😱\n\n"
                . "Telah terbit artikel penting: *\"$artikel_judul_kirim\"*\n\n"
                . "Ingin tahu bagaimana cara mengatasinya secara Islami? Yuk baca selengkapnya di link berikut:\n"
                . "{{LINK_AFILIASI}}";
        }

        $agen_data = [];
        pastikanKoneksiDb();
        $resA = $conn->query("SELECT nama, whatsapp, kode_ref FROM agen");
        if($resA) while($r = $resA->fetch_assoc()) $agen_data[] = $r;

        if (count($agen_data) > 0 && $FONNTE_TOKEN !== "TOKEN_API_FONNTE_ANDA") {
            $prompt_publisher_default = "Assalamu'alaikum Kak {{NAMA_AGEN}},\n\n"
                . "Artikel baru Villa Quran sudah rilis: *{{JUDUL_ARTIKEL}}* 🚀\n\n"
                . "Silakan sebarkan pesan copywriting di bawah ini ke grup-grup sosial media dan WhatsApp Kakak untuk menarik minat calon pendaftar:\n\n"
                . "--------------------------------------------------\n"
                . "{{COPYWRITING_AI}}\n"
                . "--------------------------------------------------\n\n"
                . "Semoga hari ini dimudahkan closing-nya, Aamiin!";
            $prompt_publisher_raw = file_exists(__DIR__ . '/prompt_publisher.txt') ? file_get_contents(__DIR__ . '/prompt_publisher.txt') : $prompt_publisher_default;

            foreach ($agen_data as $agen) {
                $link = $APP_URL . "/artikel-detail.php?id=" . $artikel_id_kirim . "&ref=" . $agen['kode_ref'];
                
                // Ganti {{LINK_AFILIASI}} di dalam copywriting template dengan link unik agen
                $copywriting_personal = str_replace('{{LINK_AFILIASI}}', $link, $copywriting_template);
                
                $replacements_wa = [
                    '{{NAMA_AGEN}}' => $agen['nama'],
                    '{{JUDUL_ARTIKEL}}' => $artikel_judul_kirim,
                    '{{LINK_AFILIASI}}' => $link,
                    '{{COPYWRITING_AI}}' => $copywriting_personal
                ];
                $pesan = str_replace(array_keys($replacements_wa), array_values($replacements_wa), $prompt_publisher_raw);

                $no_wa = preg_replace('/[^0-9]/', '', $agen['whatsapp']);
                if (substr($no_wa, 0, 1) === '0') {
                    $no_wa = '62' . substr($no_wa, 1);
                }

                $waFd = ['target' => $no_wa, 'message' => $pesan];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.fonnte.com/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($waFd),
                    CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                    CURLOPT_TIMEOUT => 30
                ]);
                $response = curl_exec($ch);
                $curl_error = curl_error($ch);
                curl_close($ch);
                
                if ($curl_error) {
                    logAgent("-> Gagal mengirim WA ke {$agen['nama']} ({$no_wa}): " . $curl_error);
                } else {
                    $res_obj = json_decode($response, true);
                    if (isset($res_obj['status']) && $res_obj['status'] == true) {
                        logAgent("-> Pesan WA (ID: $artikel_id_kirim) dilesatkan ke: {$agen['nama']}.");
                    } else {
                        logAgent("-> Fonnte menolak pengiriman ke {$agen['nama']} ({$no_wa}): " . ($res_obj['reason'] ?? $response));
                    }
                }
                
                $jeda = (php_sapi_name() === 'cli') ? rand(60, 180) : 1;
                logAgent("-> Jeda {$jeda} detik...");
                sleep($jeda);
            }

            // --- BROADCAST KE GRUP WA AUTO BROADCAST ---
            pastikanKoneksiDb();
            $resG = $conn->query("SELECT id, nama_grup, whatsapp_group_jid FROM grup_komunitas WHERE platform = 'WhatsApp' AND auto_broadcast = 1 AND whatsapp_group_jid IS NOT NULL AND whatsapp_group_jid != ''");
            $group_targets = [];
            if ($resG) {
                while ($rg = $resG->fetch_assoc()) {
                    $group_targets[] = $rg;
                }
            }

            if (count($group_targets) > 0 && $FONNTE_TOKEN !== "TOKEN_API_FONNTE_ANDA") {
                logAgent("Menemukan " . count($group_targets) . " grup WA untuk Auto Broadcast. Mengirim...");
                foreach ($group_targets as $gTarget) {
                    $link = $APP_URL . "/artikel-detail.php?id=" . $artikel_id_kirim . "&ref=organik";
                    
                    // Ganti {{LINK_AFILIASI}} di dalam copywriting template dengan link organik
                    $pesan_grup = str_replace('{{LINK_AFILIASI}}', $link, $copywriting_template);
                    
                    $waFd = ['target' => $gTarget['whatsapp_group_jid'], 'message' => $pesan_grup];
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => "https://api.fonnte.com/send",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query($waFd),
                        CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                        CURLOPT_TIMEOUT => 30
                    ]);
                    $response = curl_exec($ch);
                    $curl_error = curl_error($ch);
                    curl_close($ch);
                    
                    if ($curl_error) {
                        logAgent("-> Gagal mengirim WA ke grup {$gTarget['nama_grup']} ({$gTarget['whatsapp_group_jid']}): " . $curl_error);
                    } else {
                        $res_obj = json_decode($response, true);
                        if (isset($res_obj['status']) && $res_obj['status'] == true) {
                            logAgent("-> Pesan WA dilesatkan ke grup: {$gTarget['nama_grup']}.");
                        } else {
                            logAgent("-> Fonnte menolak pengiriman ke grup {$gTarget['nama_grup']} ({$gTarget['whatsapp_group_jid']}): " . ($res_obj['reason'] ?? $response));
                        }
                    }
                    
                    $jeda = (php_sapi_name() === 'cli') ? rand(10, 30) : 1;
                    logAgent("-> Jeda {$jeda} detik...");
                    sleep($jeda);
                }
            }

            // Setelah selesai broadcast, update status artikel
            pastikanKoneksiDb();
            $conn->query("UPDATE artikel SET status_broadcast = 'terkirim' WHERE id = $artikel_id_kirim");
            logAgent("✅ Broadcast untuk artikel ID $artikel_id_kirim selesai. Status diupdate.");
        }
    } else {
        logAgent("Tidak ada artikel baru untuk disebar. Semua sudah terkirim.");
    }

    // TANDAI SELESAI
    file_put_contents($daily_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Tugas Harian ($today) Tuntas!\n");
}

// =========================================================================================
// AGENT COMMUNITY SCOUT MANDIRI (Tiap Hari, Jam 07:00)
// =========================================================================================
$community_done = false;
$community_log_file = __DIR__ . '/agent_community_log.txt';
if (file_exists($community_log_file)) {
    if (strpos(file_get_contents($community_log_file), "SUCCESS_$today") !== false) $community_done = true;
}

$force_community = ($force === 'community');
if (($current_hour >= '07' || $force_community) && (!$community_done || $force_community)) {
    logAgent("======= MEMULAI AGENT COMMUNITY SCOUT ($today) =======");

    // Self-healing: Pastikan tabel grup_komunitas ada
    pastikanKoneksiDb();
    $conn->query("CREATE TABLE IF NOT EXISTS grup_komunitas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama_grup VARCHAR(255) NOT NULL,
        platform VARCHAR(50) NOT NULL,
        link_gabung VARCHAR(255) NOT NULL UNIQUE,
        whatsapp_group_jid VARCHAR(100) DEFAULT NULL,
        analisa_relevansi TEXT,
        skor_kualitas INT DEFAULT 5,
        saran_pembuka TEXT,
        status VARCHAR(50) DEFAULT 'Belum Dihubungi',
        auto_broadcast TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Self-healing migrations for JID and auto_broadcast columns
    $res_jid_col = $conn->query("SHOW COLUMNS FROM grup_komunitas LIKE 'whatsapp_group_jid'");
    if ($res_jid_col && $res_jid_col->num_rows == 0) {
        $conn->query("ALTER TABLE grup_komunitas ADD COLUMN whatsapp_group_jid VARCHAR(100) DEFAULT NULL AFTER link_gabung");
    }
    $res_auto_col = $conn->query("SHOW COLUMNS FROM grup_komunitas LIKE 'auto_broadcast'");
    if ($res_auto_col && $res_auto_col->num_rows == 0) {
        $conn->query("ALTER TABLE grup_komunitas ADD COLUMN auto_broadcast TINYINT(1) DEFAULT 0 AFTER status");
    }

    $persona = file_exists(__DIR__ . '/saved_persona.txt') ? file_get_contents(__DIR__ . '/saved_persona.txt') : 'Orang tua yang mencari pesantren untuk anak.';
    
    // Ambil beberapa link/nama grup yang sudah ada di database untuk dikecualikan agar AI mencari yang baru
    $exclusions = [];
    pastikanKoneksiDb();
    $res_ex = $conn->query("SELECT nama_grup, link_gabung FROM grup_komunitas ORDER BY id DESC LIMIT 20");
    if ($res_ex && $res_ex->num_rows > 0) {
        while ($row_ex = $res_ex->fetch_assoc()) {
            $exclusions[] = "- " . $row_ex['nama_grup'] . " (" . $row_ex['link_gabung'] . ")";
        }
    }
    
    $exclusion_text = "";
    if (count($exclusions) > 0) {
        $exclusion_text = "\n\nHINDARI grup berikut karena sudah ditemukan sebelumnya:\n" . implode("\n", $exclusions);
    }

    $prompt_community_default = "Anda adalah seorang Digital Community Specialist. Target audiens kita adalah: \n\n{{PERSONA}}\n\n Tugas Anda adalah mencari link grup WhatsApp, Telegram, dan Facebook yang relevan dengan target audiens tersebut.\n"
        . "Aturan Wajib:\n"
        . "1. Grup yang dicari harus bersifat terbuka/publik (siapa saja boleh bergabung).\n"
        . "2. Grup tersebut BUKAN merupakan grup kolam marketing milik sekolah/pesantren kompetitor lain.\n"
        . "3. Grup yang dicari harus AKTIF dengan tingkat interaksi tinggi. Khusus untuk Facebook Group, pastikan grup tersebut memiliki tingkat postingan harian yang ramai (minimal 5-10 postingan baru per hari). Hindari grup pasif, mati, atau sepi.\n"
        . "4. Kembalikan output HANYA dalam format JSON array murni tanpa markdown (tanpa ```json dan tanpa penjelasan lain). Format JSON harus tepat seperti ini:\n"
        . "[\n"
        . "  {\n"
        . "    \"nama_grup\": \"Nama Grup Komunitas\",\n"
        . "    \"platform\": \"WhatsApp/Telegram/Facebook\",\n"
        . "    \"link_gabung\": \"URL Link Gabung Grup\",\n"
        . "    \"analisa_relevansi\": \"Penjelasan mengapa grup ini sangat relevan untuk prospek/leads kita\",\n"
        . "    \"skor_kualitas\": 8,\n"
        . "    \"saran_pembuka\": \"Kalimat pembuka diskusi yang natural, sopan, dan tidak bernuansa hard selling\"\n"
        . "  }\n"
        . "]\n"
        . "Cari minimal 5 grup baru.";

    $prompt_community_raw = file_exists(__DIR__ . '/prompt_community_scout.txt') ? file_get_contents(__DIR__ . '/prompt_community_scout.txt') : $prompt_community_default;
    // Enforce JSON format requirements by appending them
    $prompt_community_enforced = $prompt_community_raw . "\n\nATURAN WAJIB FORMAT OUTPUT: Output harus berupa JSON array murni tanpa pembungkus markdown (tanpa ```json). Setiap item dalam array harus berupa objek dengan key: 'nama_grup', 'platform', 'link_gabung', 'analisa_relevansi', 'skor_kualitas', 'saran_pembuka'. Grup yang dicari harus merupakan grup terbuka/publik, AKTIF dengan interaksi ramai (minimal 5-10 post baru per hari khususnya di Facebook), dan BUKAN milik sekolah/pesantren kompetitor." . $exclusion_text;

    $prompt_community = str_replace('{{PERSONA}}', $persona, $prompt_community_enforced);

    $payloadCommunity = [
        [
            "jenis_lead" => "SYSTEM_COMMAND",
            "sumber_info" => $prompt_community,
            "status" => "URGENT"
        ]
    ];

    $dataCommunity = mikirKeGemini(['leads' => $payloadCommunity, 'type' => 'community_scout']);
    if (isset($dataCommunity['status']) && $dataCommunity['status'] === 'success') {
        $rawResult = trim($dataCommunity['result'] ?? '');
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $rawResult, $matches)) {
            $cleanJson = trim($matches[1]);
        } else {
            $cleanJson = $rawResult;
        }
        
        // Simpan backup raw
        file_put_contents(__DIR__ . '/saved_communities.txt', $rawResult);

        $groups = json_decode($cleanJson, true);
        if (is_array($groups)) {
            $new_count = 0;
            $exist_count = 0;
            foreach ($groups as $g) {
                if (isset($g['link_gabung']) && !empty($g['link_gabung'])) {
                    pastikanKoneksiDb();
                    $link = $conn->real_escape_string($g['link_gabung']);
                    
                    // Cek duplikasi
                    $check = $conn->query("SELECT id FROM grup_komunitas WHERE link_gabung = '$link'");
                    if ($check && $check->num_rows > 0) {
                        $exist_count++;
                        continue;
                    }
                    
                    $nama = $conn->real_escape_string($g['nama_grup'] ?? 'Grup Relevan');
                    $plat = $conn->real_escape_string($g['platform'] ?? 'Unknown');
                    $analisa = $conn->real_escape_string($g['analisa_relevansi'] ?? '');
                    $skor = (int)($g['skor_kualitas'] ?? 5);
                    $saran = $conn->real_escape_string($g['saran_pembuka'] ?? '');
                    
                    $sql_ins = "INSERT INTO grup_komunitas (nama_grup, platform, link_gabung, analisa_relevansi, skor_kualitas, saran_pembuka)
                                VALUES ('$nama', '$plat', '$link', '$analisa', $skor, '$saran')";
                    if ($conn->query($sql_ins) === TRUE) {
                        $new_count++;
                    }
                }
            }
            logAgent("✅ Sukses mencari grup komunitas. Berhasil menyimpan $new_count grup baru ke database. ($exist_count grup sudah terdaftar sebelumnya).");
        } else {
            logAgent("⚠️ Hasil dari Gemini bukan merupakan array JSON valid. Gagal memproses data grup.");
            logAgent("Raw result (200 char): " . substr($rawResult, 0, 200));
        }
    } else {
        logAgent("❌ Gagal membuat laporan pencarian grup komunitas.");
    }

    // TANDAI SELESAI
    file_put_contents($community_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Tugas Community Scout ($today) Selesai dilakukan.\n");
    
    jedaAgent(5);
}

// =========================================================================================
// AGENT PENAGIHAN OTOMATIS (Setiap tanggal 1, 3, 6, 10 jam 08:00 WIB)
// =========================================================================================
$billing_done = false;
$billing_log_file = __DIR__ . '/agent_billing_log.txt';
if (file_exists($billing_log_file)) {
    if (strpos(file_get_contents($billing_log_file), "SUCCESS_$today") !== false) $billing_done = true;
}

// Hanya jalan jika jam >= 08 pagi, status belum done hari ini, dan tanggal adalah 1, 3, 6, atau 10
$allowed_days = ['01', '03', '06', '10'];
$force_billing = ($force === 'billing');
if (($current_hour >= '08' || $force_billing) && (!$billing_done || $force_billing) && (in_array($current_day, $allowed_days) || $force_billing)) {
    
    $simulated_day = $current_day;
    if ($force_billing && !in_array($current_day, $allowed_days)) {
        $simulated_day = '01';
        logAgent("======= MEMULAI AGENT PENAGIHAN OTOMATIS (FORCED) =======");
        logAgent("Dijalankan manual diluar tanggal penagihan resmi (1, 3, 6, 10). Mensimulasikan sebagai penagihan Tanggal 01.");
    } else {
        logAgent("======= MEMULAI AGENT PENAGIHAN OTOMATIS ($today) =======");
    }
    
    // Pastikan database terinisialisasi
    require_once __DIR__ . '/yayasan2/setup-pembukuan.php';
    
    $bulan_indo = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $bulan_sekarang = $bulan_indo[(int)date('n')];
    $tahun_sekarang = date('Y');
    
    // 1. Query Santri Aktif yang memiliki kewajiban (belum bayar SPP bulan ini ATAU memiliki sisa cicilan uang masuk > 0)
    $sql_santri = "
        SELECT s.id, s.nama_lengkap, s.kelas_sekarang, s.sisa_uang_masuk, p.id as spp_bayar_id,
               COALESCE(s.no_whatsapp_ayah, s.no_whatsapp_ibu, s.no_whatsapp_wali, o.no_whatsapp) as no_wa,
               COALESCE(s.nama_ayah, s.nama_ibu, s.nama_wali, o.nama_orangtua) as nama_ortu
        FROM buku_induk_santri s
        LEFT JOIN akun_orangtua o ON s.id_orangtua = o.id
        LEFT JOIN pembayaran_spp p ON s.id = p.santri_id 
            AND p.bulan = '$bulan_sekarang' 
            AND p.tahun = '$tahun_sekarang' 
            AND p.status = 'Berhasil'
        WHERE s.status_santri = 'Aktif' 
          AND (p.id IS NULL OR s.sisa_uang_masuk > 0)
        ORDER BY s.id ASC";
        
    pastikanKoneksiDb();
    $res_santri = $conn->query($sql_santri);
    $overdue_list = [];
    if ($res_santri) {
        while ($row = $res_santri->fetch_assoc()) {
            $row['spp_belum_lunas'] = is_null($row['spp_bayar_id']);
            $overdue_list[] = $row;
        }
    }
    
    if (count($overdue_list) > 0) {
        logAgent("Menemukan " . count($overdue_list) . " santri dengan kewajiban keuangan aktif.");
        
        foreach ($overdue_list as $s) {
            if (empty($s['no_wa'])) {
                logAgent("Wali santri dari {$s['nama_lengkap']} tidak memiliki nomor WA. Skip.");
                continue;
            }
            
            // Bersihkan nomor WA
            $no_wa = preg_replace('/[^0-9]/', '', $s['no_wa']);
            if (substr($no_wa, 0, 1) === '0') {
                $no_wa = '62' . substr($no_wa, 1);
            }
            
            // Susun rincian tagihan
            $rincian = "";
            if ($s['spp_belum_lunas']) {
                $rincian .= "- SPP Bulanan periode $bulan_sekarang $tahun_sekarang\n";
            }
            if ($s['sisa_uang_masuk'] > 0) {
                $rincian .= "- Cicilan Uang Masuk (Sisa tagihan: Rp " . number_format($s['sisa_uang_masuk'], 0, ',', '.') . ")\n";
            }
            
            if (empty($rincian)) continue; // Jika ternyata tidak ada tagihan
            
            $pesan = "";
            
            // Kirim pesan sesuai tanggal
            if ($simulated_day == '01') {
                // Tanggal 1: Pengingat awal
                $pesan = "Assalamu'alaikum Wr. Wb. Yth. Bapak/Ibu {$s['nama_ortu']},\n\n"
                       . "Semoga Allah SWT melimpahkan kesehatan dan berkah bagi keluarga.\n\n"
                       . "Kami menginformasikan bahwa tagihan keuangan untuk ananda *{$s['nama_lengkap']}* kelas *{$s['kelas_sekarang']}* telah diterbitkan:\n"
                       . $rincian . "\n"
                       . "Mohon dapat menyalurkan pembayaran melalui transfer ke rekening resmi Yayasan:\n"
                       . "*Bank Syariah Indonesia (BSI)*\n"
                       . "*No Rekening: 7700889911*\n"
                       . "*Atas Nama: Villa Quran Indonesia*\n\n"
                       . "Silakan upload bukti bayar di Ruang Orang Tua jika transfer telah selesai dilakukan. Abaikan pesan ini jika baru saja melakukan pembayaran.\n\n"
                       . "Jazaakumullahu Khairan.\n"
                       . "-- Bendahara Yayasan Villa Quran --";
            } 
            elseif ($simulated_day == '03') {
                // Tanggal 3: Konfirmasi pertama
                $pesan = "Assalamu'alaikum Wr. Wb. Yth. Bapak/Ibu {$s['nama_ortu']},\n\n"
                       . "Mohon konfirmasinya terkait pembayaran tagihan ananda *{$s['nama_lengkap']}*:\n"
                       . $rincian . "\n"
                       . "Hingga hari ini kami belum mencatat konfirmasi pembayaran tersebut. Jika Bapak/Ibu sudah melakukan transfer, silakan konfirmasi melalui Ruang Orang Tua dengan melampirkan bukti transfer agar segera kami verifikasi.\n\n"
                       . "Jika belum, pembayaran dapat ditransfer ke *BSI Rekening 7700889911 a.n. Villa Quran Indonesia*.\n\n"
                       . "Jazaakumullahu Khairan.\n"
                       . "-- Bendahara Yayasan Villa Quran --";
            } 
            elseif ($simulated_day == '06') {
                // Tanggal 6: Konfirmasi kedua (lebih tegas)
                $pesan = "Assalamu'alaikum Wr. Wb. Yth. Bapak/Ibu {$s['nama_ortu']},\n\n"
                       . "Pengingat ulang terkait konfirmasi pembayaran tagihan ananda *{$s['nama_lengkap']}*:\n"
                       . $rincian . "\n"
                       . "Mohon dibantu untuk melunasi kewajiban tersebut sebelum pertengahan bulan demi kelancaran operasional pendidikan santri. Pembayaran dapat dikirim ke *BSI 7700889911 a.n. Villa Quran Indonesia*.\n\n"
                       . "Jika Bapak/Ibu mengalami kendala, silakan hubungi bagian keuangan Yayasan untuk berkonsultasi.\n\n"
                       . "Jazaakumullahu Khairan.\n"
                       . "-- Bendahara Yayasan Villa Quran --";
            } 
            elseif ($simulated_day == '10') {
                // Tanggal 10: Tanya kapan melunasi (Link Janji Bayar)
                $secret_token = md5($s['id'] . 'viqi_billing_secret');
                $link_promise = $APP_URL . "/konfirmasi-janji-bayar.php?s=" . $s['id'] . "&t=" . $secret_token;
                
                $pesan = "Assalamu'alaikum Wr. Wb. Yth. Bapak/Ibu {$s['nama_ortu']},\n\n"
                       . "Mohon maaf mengganggu waktu Bapak/Ibu. Terkait kewajiban tagihan ananda *{$s['nama_lengkap']}*:\n"
                       . $rincian . "\n"
                       . "Hingga tanggal 10 ini pembayaran belum lunas. Agar kami dapat menjadwalkan kebutuhan anggaran Yayasan, bolehkah kami mengetahui perkiraan tanggal Bapak/Ibu dapat melunasi tagihan tersebut?\n\n"
                       . "Silakan isi tanggal perkiraan bayar melalui link konfirmasi berikut:\n"
                       . $link_promise . "\n\n"
                       . "Terima kasih banyak atas perhatian dan kerja samanya.\n"
                       . "-- Bendahara Yayasan Villa Quran --";
            }
            
            if (!empty($pesan)) {
                $waFd = ['target' => $no_wa, 'message' => $pesan];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.fonnte.com/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($waFd),
                    CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                    CURLOPT_TIMEOUT => 20
                ]);
                curl_exec($ch);
                curl_close($ch);
                
                logAgent("-> Penagihan otomatis tanggal $simulated_day dikirim ke {$s['nama_lengkap']} ({$no_wa})");
                
                // Jeda acak antarkirim 1-3 detik untuk menghindari blokir spam
                sleep(rand(1, 3));
            }
        }
    } else {
        logAgent("Maa Syaa Allah, seluruh santri aktif telah melunasi kewajiban keuangan bulan ini!");
    }
    
    // TANDAI SELESAI HARI INI
    file_put_contents($billing_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Penagihan Otomatis ($today) Selesai dilakukan.");
}

// =========================================================================================
// AI HRD AGENT helper & job blocks
function generate_hrd_pesan_ai($tipe_reminder, $detail_tambahan = '') {
    static $cache = [];
    $cache_key = $tipe_reminder . '_' . md5($detail_tambahan);
    if (isset($cache[$cache_key])) {
        return $cache[$cache_key];
    }
    
    $prompt = "";
    $fallback = "";
    
    if ($tipe_reminder === 'absen_datang') {
        $prompt = "Tulis template pesan WhatsApp singkat, ramah, dan bernuansa Islami untuk mengingatkan staf/pimpinan bernama {{NAMA}} agar melakukan absen masuk/kedatangan pagi ini sebelum batas waktu pukul 07.00 WIB. Gunakan sapaan Assalamu'alaikum, ingatkan secara santun tapi jelas. Berikan placeholder {{NAMA}} dalam template Anda. Jangan gunakan format markdown tebal miring selain tanda * untuk bold.";
        $fallback = "Assalamu'alaikum Wr. Wb. Yth. *Ustadz/Ustadzah {{NAMA}}* 🙏\n\nMengingatkan bahwa 5 menit lagi (pukul 07.00 WIB) batas waktu absensi datang dimulai. Silakan lakukan absensi kedatangan (check-in) di Ruang Asatidz sebelum terlambat.\n\n-- AI HRD Yayasan Villa Quran --";
    } elseif ($tipe_reminder === 'absen_pulang') {
        $prompt = "Tulis template pesan WhatsApp singkat, ramah, dan bernuansa Islami untuk mengingatkan staf/pimpinan bernama {{NAMA}} agar melakukan absen pulang/check-out siang ini karena jam kerja berakhir pukul 13.00 WIB. Berikan ucapan terima kasih atas kerja keras dan dedikasinya hari ini. Berikan placeholder {{NAMA}} dalam template Anda.";
        $fallback = "Assalamu'alaikum Wr. Wb. Yth. *Ustadz/Ustadzah {{NAMA}}* 🙏\n\nMengingatkan bahwa 1 menit lagi (pukul 13.00 WIB) jam kerja berakhir. Silakan lakukan absensi kepulangan (check-out) di Ruang Asatidz.\n\n-- AI HRD Yayasan Villa Quran --";
    } elseif ($tipe_reminder === 'jurnal_mengajar') {
        $prompt = "Tulis template pesan WhatsApp singkat, ramah, dan bernuansa Islami untuk mengingatkan ustadz/ustadzah bernama {{NAMA}} yang akan mengajar $detail_tambahan dalam 5 menit. Ingatkan untuk melakukan absen Mengajar and mengisi Jurnal Mengajar setelah selesai mengajar nanti. Berikan placeholder {{NAMA}} dalam template Anda.";
        $fallback = "Assalamu'alaikum Wr. Wb. Yth. *Ustadz/Ustadzah {{NAMA}}* 🙏\n\nMengingatkan bahwa 5 menit lagi Anda dijadwalkan mengajar $detail_tambahan. Mohon tidak lupa melakukan absen Mengajar dan melengkapi Jurnal Mengajar setelah selesai nanti.\n\n-- AI HRD Yayasan Villa Quran --";
    } elseif ($tipe_reminder === 'musyrif_remind') {
        $prompt = "Tulis template pesan WhatsApp singkat, santun, ramah, dan bernuansa Islami untuk mengingatkan Musyrif bernama {{NAMA}} agar memeriksa dan memvalidasi Buku Mutaba'ah harian bagi santri binaannya di Ruang Asatidz hari ini. Gunakan sapaan Assalamu'alaikum. Berikan placeholder {{NAMA}}.";
        $fallback = "Assalamu'alaikum Wr. Wb. Yth. *Ustadz {{NAMA}}* 🙏\n\nMengingatkan untuk memeriksa dan menginput Buku Mutaba'ah harian bagi santri binaan Anda di Ruang Asatidz hari ini.\n\nMari dukung keistiqomahan ibadah santri kita dengan verifikasi yang disiplin. Terima kasih!\n\n-- AI HRD Yayasan Villa Quran --";
    } elseif ($tipe_reminder === 'musyrif_scold') {
        $prompt = "Tulis template pesan WhatsApp singkat, tegas, bernuansa Islami untuk menegur Musyrif bernama {{NAMA}} karena terdeteksi belum memvalidasi Buku Mutaba'ah santri selama 2 hari berturut-turut (kemarin dan hari ini), padahal santri telah mengisi data ibadah harian. Sebutkan secara jelas bahwa kelalaian ini akan berdampak langsung pada pengurangan penilaian kinerja (KPI) bulanan mereka. Gunakan sapaan Assalamu'alaikum. Berikan placeholder {{NAMA}}.";
        $fallback = "Assalamu'alaikum Wr. Wb. Yth. *Ustadz {{NAMA}}* ⚠️\n\nPeringatan Keras dari AI HRD: Anda terdeteksi belum melakukan verifikasi/input Buku Mutaba'ah santri binaan Anda selama 2 hari berturut-turut (kemarin dan hari ini), padahal santri telah mengisi data ibadah harian.\n\nKelalaian ini akan berdampak langsung pada pengurangan nilai penilaian kinerja (KPI) bulanan Anda. Mohon segera lakukan validasi di Ruang Asatidz sekarang juga.\n\n-- AI HRD Yayasan Villa Quran --";
    } elseif ($tipe_reminder === 'musyrif_performance_drop') {
        $prompt = "Tulis template pesan WhatsApp singkat, bernuansa Islami dan peduli untuk memberi tahu Musyrif bernama {{NAMA}} agar membimbing dan memotivasi santri binaannya yang mengalami penurunan skor performa ibadah harian. Detail santri yang mengalami penurunan adalah sebagai berikut:\n$detail_tambahan\n\nBuat agar pesan tersebut mendorong Musyrif memikirkan strategi/cara agar performa ibadah santri tersebut naik kembali. Gunakan sapaan Assalamu'alaikum. Berikan placeholder {{NAMA}}.";
        $fallback = "Assalamu'alaikum Wr. Wb. Yth. *Ustadz {{NAMA}}* 🙏\n\nAI HRD mendeteksi adanya penurunan performa ibadah harian pada santri binaan Anda:\n$detail_tambahan\n\nMohon perhatiannya untuk mendekati, membimbing, dan memikirkan cara terbaik agar ananda kembali semangat beribadah.\n\n-- AI HRD Yayasan Villa Quran --";
    }
    
    $res = mikirKeGemini([
        'leads' => [
            ['jenis_lead' => 'SYSTEM_COMMAND', 'sumber_info' => $prompt]
        ]
    ]);
    
    $msg = $fallback;
    if (isset($res['status']) && $res['status'] === 'success' && !empty($res['result'])) {
        $msg = trim($res['result']);
    }
    
    $cache[$cache_key] = $msg;
    return $msg;
}

// 1. ABSEN DATANG REMINDER (Pukul 06:55 WIB)
$hrd_datang_done = false;
$hrd_datang_log_file = __DIR__ . '/agent_hrd_datang_log.txt';
if (file_exists($hrd_datang_log_file)) {
    if (strpos(file_get_contents($hrd_datang_log_file), "SUCCESS_$today") !== false) $hrd_datang_done = true;
}

$force_hrd_datang = ($force === 'hrd_datang');
if (($current_time_hm === '06:55' || $force_hrd_datang) && (!$hrd_datang_done || $force_hrd_datang)) {
    $day_num = (int)date('N');
    if ($day_num != 7 || $force_hrd_datang) { // Skip Minggu
        logAgent("======= MEMULAI AGENT HRD PREVENTIF: REMINDER ABSEN DATANG ($today) =======");
        
        pastikanKoneksiDb();
        $res_ust = $conn->query("SELECT id, nama, whatsapp, role FROM akun_ustadz WHERE whatsapp IS NOT NULL AND whatsapp != ''");
        
        $eligible_roles = ['kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'musyrif'];
        
        if ($res_ust && $res_ust->num_rows > 0) {
            $template_pesan = generate_hrd_pesan_ai('absen_datang');
            while ($row = $res_ust->fetch_assoc()) {
                $u_roles = explode(',', $row['role'] ?? '');
                $intersect = array_intersect($eligible_roles, $u_roles);
                if (empty($intersect)) continue; // Skip jika bukan staf/pimpinan kerja harian
                
                $ust_id = (int)$row['id'];
                $ust_nama = $row['nama'];
                $no_wa = preg_replace('/[^0-9]/', '', $row['whatsapp']);
                
                // Cek apakah sudah absen masuk/izin hari ini
                $res_chk = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $ust_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' AND status_kehadiran IN ('Masuk', 'Izin') LIMIT 1");
                
                if ($res_chk && $res_chk->num_rows == 0) {
                    $pesan = str_replace('{{NAMA}}', $ust_nama, $template_pesan);
                    
                    $waFd = ['target' => $no_wa, 'message' => $pesan];
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => "https://api.fonnte.com/send",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query($waFd),
                        CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                        CURLOPT_TIMEOUT => 15
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                    
                    logAgent("-> Pengingat absen datang dikirim ke staf: $ust_nama ($no_wa)");
                    sleep(rand(1, 2));
                }
            }
        }
        
        file_put_contents($hrd_datang_log_file, "SUCCESS_$today\n", FILE_APPEND);
        logAgent("🎉 Agent HRD: Absen Datang Selesai.");
    }
}

// 2. ABSEN PULANG REMINDER (Pukul 12:59 WIB)
$hrd_pulang_done = false;
$hrd_pulang_log_file = __DIR__ . '/agent_hrd_pulang_log.txt';
if (file_exists($hrd_pulang_log_file)) {
    if (strpos(file_get_contents($hrd_pulang_log_file), "SUCCESS_$today") !== false) $hrd_pulang_done = true;
}

$force_hrd_pulang = ($force === 'hrd_pulang');
if (($current_time_hm === '12:59' || $force_hrd_pulang) && (!$hrd_pulang_done || $force_hrd_pulang)) {
    $day_num = (int)date('N');
    if ($day_num != 7 || $force_hrd_pulang) { // Skip Minggu
        logAgent("======= MEMULAI AGENT HRD PREVENTIF: REMINDER ABSEN PULANG ($today) =======");
        
        pastikanKoneksiDb();
        $res_ust = $conn->query("SELECT id, nama, whatsapp, role FROM akun_ustadz WHERE whatsapp IS NOT NULL AND whatsapp != ''");
        
        $eligible_roles = ['kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'musyrif'];
        
        if ($res_ust && $res_ust->num_rows > 0) {
            $template_pesan = generate_hrd_pesan_ai('absen_pulang');
            while ($row = $res_ust->fetch_assoc()) {
                $u_roles = explode(',', $row['role'] ?? '');
                $intersect = array_intersect($eligible_roles, $u_roles);
                if (empty($intersect)) continue; // Skip jika bukan staf/pimpinan
                
                $ust_id = (int)$row['id'];
                $ust_nama = $row['nama'];
                $no_wa = preg_replace('/[^0-9]/', '', $row['whatsapp']);
                
                // Cek apakah sudah absen datang
                $res_in = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $ust_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' AND status_kehadiran = 'Masuk' LIMIT 1");
                
                if ($res_in && $res_in->num_rows > 0) {
                    // Sudah absen datang, cek apakah sudah absen pulang
                    $res_out = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $ust_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' AND status_kehadiran = 'Pulang' LIMIT 1");
                    
                    if ($res_out && $res_out->num_rows == 0) {
                        $pesan = str_replace('{{NAMA}}', $ust_nama, $template_pesan);
                        
                        $waFd = ['target' => $no_wa, 'message' => $pesan];
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => "https://api.fonnte.com/send",
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_POST => true,
                            CURLOPT_POSTFIELDS => http_build_query($waFd),
                            CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                            CURLOPT_TIMEOUT => 15
                        ]);
                        curl_exec($ch);
                        curl_close($ch);
                        
                        logAgent("-> Pengingat absen pulang dikirim ke staf: $ust_nama ($no_wa)");
                        sleep(rand(1, 2));
                    }
                }
            }
        }
        
        file_put_contents($hrd_pulang_log_file, "SUCCESS_$today\n", FILE_APPEND);
        logAgent("🎉 Agent HRD: Absen Pulang Selesai.");
    }
}

// 3. JURNAL MENGAJAR & ABSEN MENGAJAR REMINDER (5 Menit Sebelum Kelas)
$hrd_jurnal_log_file = __DIR__ . '/agent_hrd_jurnal_log.txt';

$remind_slots = [
    '04:55' => ['jam_ke' => 1, 'start' => '05:00'],
    '05:40' => ['jam_ke' => 2, 'start' => '05:45'],
    '07:25' => ['jam_ke' => 3, 'start' => '07:30'],
    '08:10' => ['jam_ke' => 4, 'start' => '08:15'],
    '08:55' => ['jam_ke' => 5, 'start' => '09:00'],
    '09:40' => ['jam_ke' => 6, 'start' => '09:45'],
    '10:25' => ['jam_ke' => 7, 'start' => '10:30'],
    '11:10' => ['jam_ke' => 8, 'start' => '11:15'],
    '15:55' => ['jam_ke' => 9, 'start' => '16:00'],
    '16:40' => ['jam_ke' => 10, 'start' => '16:45'],
    '19:25' => ['jam_ke' => 12, 'start' => '19:30'],
    '20:10' => ['jam_ke' => 13, 'start' => '20:15']
];

$force_hrd_jurnal = ($force === 'hrd_jurnal');
$slots_to_check = [];

if (isset($remind_slots[$current_time_hm])) {
    $slots_to_check[] = $remind_slots[$current_time_hm];
} elseif ($force_hrd_jurnal) {
    $slots_to_check = array_values($remind_slots);
}

if (!empty($slots_to_check)) {
    $days_indo = [
        1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Ahad'
    ];
    $today_day_name = $days_indo[date('N')];
    
    logAgent("======= MEMULAI AGENT HRD PREVENTIF: CHECKER JURNAL & ABSEN MENGAJAR ($today_day_name, $today) =======");
    
    pastikanKoneksiDb();
    
    foreach ($slots_to_check as $slot) {
        $jk = $slot['jam_ke'];
        $pukul_start = $slot['start'];
        
        $sql_sched = "
            SELECT j.ustadz_id, j.kelas_id, j.mapel_id, u.nama as ustadz_nama, u.whatsapp, c.nama_kelas, m.nama_mapel 
            FROM jadwal_pelajaran j 
            JOIN akun_ustadz u ON j.ustadz_id = u.id 
            JOIN master_kelas c ON j.kelas_id = c.id
            LEFT JOIN master_mapel m ON j.mapel_id = m.id
            WHERE j.hari = '$today_day_name' 
              AND j.jam_ke = $jk
              AND u.whatsapp IS NOT NULL 
              AND u.whatsapp != ''";
              
        $res_sched = $conn->query($sql_sched);
        
        if ($res_sched && $res_sched->num_rows > 0) {
            while ($row = $res_sched->fetch_assoc()) {
                $ust_id = (int)$row['ustadz_id'];
                $kelas_id = (int)$row['kelas_id'];
                $mapel_id = (int)$row['mapel_id'];
                $ust_nama = $row['ustadz_nama'];
                $nama_kelas = $row['nama_kelas'];
                $nama_mapel = $row['nama_mapel'] ?? 'Mata Pelajaran';
                $no_wa = preg_replace('/[^0-9]/', '', $row['whatsapp']);
                
                $preceding_jk = $jk - 1;
                $res_preceding = $conn->query("
                    SELECT id 
                    FROM jadwal_pelajaran 
                    WHERE hari = '$today_day_name' 
                      AND ustadz_id = $ust_id 
                      AND kelas_id = $kelas_id 
                      AND mapel_id = $mapel_id 
                      AND jam_ke = $preceding_jk 
                    LIMIT 1");
                
                $is_start_of_block = ($res_preceding && $res_preceding->num_rows == 0);
                
                if (!$is_start_of_block) {
                    logAgent("-> Skip jam ke-$jk untuk $ust_nama (lanjutan dari jam ke-$preceding_jk)");
                    continue;
                }
                
                $res_chk = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $ust_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Mengajar' AND status_kehadiran = 'Masuk' LIMIT 1");
                
                $res_jur = $conn->query("SELECT id FROM jurnal_mengajar WHERE ustadz_id = $ust_id AND tanggal = '$today' AND kelas = '$nama_kelas' AND mata_pelajaran = '$nama_mapel' LIMIT 1");
                
                if (($res_chk && $res_chk->num_rows == 0) || ($res_jur && $res_jur->num_rows == 0)) {
                    $detail_tambahan = "*$nama_mapel* di kelas *$nama_kelas* pukul *$pukul_start* WIB";
                    $template_pesan = generate_hrd_pesan_ai('jurnal_mengajar', $detail_tambahan);
                    $pesan = str_replace('{{NAMA}}', $ust_nama, $template_pesan);
                    
                    $waFd = ['target' => $no_wa, 'message' => $pesan];
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => "https://api.fonnte.com/send",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query($waFd),
                        CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                        CURLOPT_TIMEOUT => 15
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                    
                    logAgent("-> Pengingat KBM jam ke-$jk dikirim ke: $ust_nama ($no_wa) - Mapel: $nama_mapel, Kelas: $nama_kelas");
                    sleep(rand(1, 2));
                }
            }
        }
    }
    logAgent("🎉 Agent HRD: Pengecekan Jurnal & Absen Mengajar Selesai.");
}

// 4. CHECKER VALIDASI MUTABAAH MUSYRIF (Pukul 20:30 WIB)
$hrd_mutabaah_done = false;
$hrd_mutabaah_log_file = __DIR__ . '/agent_hrd_mutabaah_log.txt';
if (file_exists($hrd_mutabaah_log_file)) {
    if (strpos(file_get_contents($hrd_mutabaah_log_file), "SUCCESS_$today") !== false) $hrd_mutabaah_done = true;
}

$force_hrd_mutabaah = ($force === 'hrd_mutabaah');
if (($current_time_hm === '20:30' || $force_hrd_mutabaah) && (!$hrd_mutabaah_done || $force_hrd_mutabaah)) {
    logAgent("======= MEMULAI AGENT HRD: PENGECEKAN VALIDASI MUTABAAH MUSYRIF ($today) =======");
    
    pastikanKoneksiDb();
    
    // Ambil daftar musyrif
    $res_musyrif = $conn->query("SELECT id, nama, whatsapp FROM akun_ustadz WHERE role LIKE '%musyrif%' AND whatsapp IS NOT NULL AND whatsapp != ''");
    
    if ($res_musyrif && $res_musyrif->num_rows > 0) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        while ($musyrif = $res_musyrif->fetch_assoc()) {
            $m_id = (int)$musyrif['id'];
            $m_nama = $musyrif['nama'];
            $no_wa = preg_replace('/[^0-9]/', '', $musyrif['whatsapp']);
            if (substr($no_wa, 0, 1) === '0') {
                $no_wa = '62' . substr($no_wa, 1);
            }
            
            // Dapatkan santri_id dalam halaqoh musyrif ini
            $res_santri = $conn->query("
                SELECT a.santri_id, s.nama_lengkap 
                FROM halaqoh_anggota a 
                JOIN halaqoh_grup g ON a.grup_id = g.id 
                JOIN buku_induk_santri s ON a.santri_id = s.id
                WHERE g.musyrif_id = $m_id
            ");
            
            if (!$res_santri || $res_santri->num_rows == 0) {
                logAgent("-> Musyrif $m_nama tidak memiliki santri binaan. Skip.");
                continue;
            }
            
            $santri_ids = [];
            $santri_names = [];
            while ($s = $res_santri->fetch_assoc()) {
                $santri_ids[] = (int)$s['santri_id'];
                $santri_names[] = $conn->real_escape_string($s['nama_lengkap']);
            }
            
            $santri_ids_str = implode(',', $santri_ids);
            
            // --- CEK DATA KEMARIN (YESTERDAY) ---
            // 1. Apakah ada santri binaan yang mengisi ibadah harian kemarin?
            $res_ih_y = $conn->query("SELECT count(*) as total FROM ibadah_harian_santri WHERE tanggal = '$yesterday' AND santri_id IN ($santri_ids_str)");
            $row_ih_y = $res_ih_y ? $res_ih_y->fetch_assoc() : ['total' => 0];
            $santri_has_data_y = ($row_ih_y['total'] > 0);
            
            // 2. Apakah musyrif menginput data mutaba'ah kemarin?
            $res_bm_y = $conn->query("SELECT count(*) as total FROM buku_mutabaah WHERE tanggal = '$yesterday' AND musyrif_id = $m_id");
            $row_bm_y = $res_bm_y ? $res_bm_y->fetch_assoc() : ['total' => 0];
            $musyrif_checked_y = ($row_bm_y['total'] > 0);
            
            $missed_yesterday = ($santri_has_data_y && !$musyrif_checked_y);
            
            // --- CEK DATA HARI INI (TODAY) ---
            // 1. Apakah ada santri binaan yang mengisi ibadah harian hari ini?
            $res_ih_t = $conn->query("SELECT count(*) as total FROM ibadah_harian_santri WHERE tanggal = '$today' AND santri_id IN ($santri_ids_str)");
            $row_ih_t = $res_ih_t ? $res_ih_t->fetch_assoc() : ['total' => 0];
            $santri_has_data_t = ($row_ih_t['total'] > 0);
            
            // 2. Apakah musyrif menginput data mutaba'ah hari ini?
            $res_bm_t = $conn->query("SELECT count(*) as total FROM buku_mutabaah WHERE tanggal = '$today' AND musyrif_id = $m_id");
            $row_bm_t = $res_bm_t ? $res_bm_t->fetch_assoc() : ['total' => 0];
            $musyrif_checked_t = ($row_bm_t['total'] > 0);
            
            $missed_today = ($santri_has_data_t && !$musyrif_checked_t);
            
            if ($missed_yesterday && $missed_today) {
                // Teguran Keras: 2 hari tidak mengecek
                $template_pesan = generate_hrd_pesan_ai('musyrif_scold');
                $pesan = str_replace('{{NAMA}}', $m_nama, $template_pesan);
                
                $waFd = ['target' => $no_wa, 'message' => $pesan];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.fonnte.com/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($waFd),
                    CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                    CURLOPT_TIMEOUT => 15
                ]);
                curl_exec($ch);
                curl_close($ch);
                
                logAgent("⚠️ TEGURAN KPI (2 hari bolos): Dikirim ke Musyrif $m_nama ($no_wa)");
                sleep(rand(1, 2));
            } elseif ($missed_today) {
                // Pengingat Harian (hari pertama)
                $template_pesan = generate_hrd_pesan_ai('musyrif_remind');
                $pesan = str_replace('{{NAMA}}', $m_nama, $template_pesan);
                
                $waFd = ['target' => $no_wa, 'message' => $pesan];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.fonnte.com/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($waFd),
                    CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                    CURLOPT_TIMEOUT => 15
                ]);
                curl_exec($ch);
                curl_close($ch);
                
                logAgent("✉️ Pengingat harian mutaba'ah: Dikirim ke Musyrif $m_nama ($no_wa)");
                sleep(rand(1, 2));
            } else {
                logAgent("-> Musyrif $m_nama tertib (sudah memeriksa/tidak ada data baru).");
            }
            
            // --- PENGECEKAN PENURUNAN PERFORMA IBADAH SANTRI ---
            $res_ibadah_logs = $conn->query("
                SELECT santri_id, tanggal, sholat_subuh, sholat_dhuhur, sholat_ashar, sholat_maghrib, sholat_isya,
                       sholat_tahajud, sholat_witir, sholat_qobliyah_subuh, sholat_dhuha, sholat_qobli_dhuhur, 
                       sholat_bakdiyah_dhuhur, sholat_qobliyah_ashar, sholat_bakdiyah_maghrib, sholat_qobliyah_isya, 
                       sholat_bakdiyah_isya, puasa_senin, puasa_kamis
                FROM ibadah_harian_santri 
                WHERE santri_id IN ($santri_ids_str) 
                  AND tanggal >= DATE_SUB('$today', INTERVAL 5 DAY)
                ORDER BY tanggal DESC
            ");
            
            $santri_worship_data = [];
            if ($res_ibadah_logs && $res_ibadah_logs->num_rows > 0) {
                while ($log = $res_ibadah_logs->fetch_assoc()) {
                    $s_id = (int)$log['santri_id'];
                    $log_date = $log['tanggal'];
                    
                    $skor = 0;
                    $wajib = ['sholat_subuh', 'sholat_dhuhur', 'sholat_ashar', 'sholat_maghrib', 'sholat_isya'];
                    foreach ($wajib as $w) {
                        $val = $log[$w] ?? '';
                        if (strpos($val, 'Jamaah') !== false || $val === 'Udzur Syar\'i') {
                            $skor += 2;
                        } elseif ($val === 'Munfarid') {
                            $skor += 1;
                        }
                    }
                    
                    $sunnah = [
                        'sholat_tahajud', 'sholat_witir', 'sholat_qobliyah_subuh', 'sholat_dhuha', 
                        'sholat_qobli_dhuhur', 'sholat_bakdiyah_dhuhur', 'sholat_qobliyah_ashar', 
                        'sholat_bakdiyah_maghrib', 'sholat_qobliyah_isya', 'sholat_bakdiyah_isya',
                        'puasa_senin', 'puasa_kamis'
                    ];
                    foreach ($sunnah as $su) {
                        if (!empty($log[$su])) {
                            $skor += 1;
                        }
                    }
                    
                    $santri_worship_data[$s_id][$log_date] = $skor;
                }
            }
            
            $days_recent = [];
            for ($i = 0; $i < 3; $i++) {
                $days_recent[] = date('Y-m-d', strtotime("-$i day"));
            }
            $days_past = [];
            for ($i = 3; $i < 6; $i++) {
                $days_past[] = date('Y-m-d', strtotime("-$i day"));
            }
            
            $dropped_students = [];
            $santri_names_map = [];
            $res_santri_names = $conn->query("SELECT id, nama_lengkap FROM buku_induk_santri WHERE id IN ($santri_ids_str)");
            if ($res_santri_names) {
                while ($sn = $res_santri_names->fetch_assoc()) {
                    $santri_names_map[(int)$sn['id']] = $sn['nama_lengkap'];
                }
            }
            
            foreach ($santri_ids as $s_id) {
                $scores = $santri_worship_data[$s_id] ?? [];
                if (empty($scores)) continue;
                
                $sum_recent = 0;
                $count_recent = 0;
                foreach ($days_recent as $d) {
                    if (isset($scores[$d])) {
                        $sum_recent += $scores[$d];
                        $count_recent++;
                    }
                }
                
                $sum_past = 0;
                $count_past = 0;
                foreach ($days_past as $d) {
                    if (isset($scores[$d])) {
                        $sum_past += $scores[$d];
                        $count_past++;
                    }
                }
                
                if ($count_recent > 0 && $count_past > 0) {
                    $avg_recent = $sum_recent / $count_recent;
                    $avg_past = $sum_past / $count_past;
                    
                    if ($avg_past >= 4.0 && ($avg_past - $avg_recent) >= 2.0) {
                        $pct_drop = round((($avg_past - $avg_recent) / $avg_past) * 100);
                        $s_name = $santri_names_map[$s_id] ?? "Santri ID $s_id";
                        $dropped_students[] = "- *$s_name*: Skor turun dari " . round($avg_past, 1) . " ke " . round($avg_recent, 1) . " (Turun {$pct_drop}%)";
                    }
                }
            }
            
            if (count($dropped_students) > 0) {
                $detail_drop = implode("\n", $dropped_students);
                $template_pesan = generate_hrd_pesan_ai('musyrif_performance_drop', $detail_drop);
                $pesan = str_replace('{{NAMA}}', $m_nama, $template_pesan);
                
                $waFd = ['target' => $no_wa, 'message' => $pesan];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.fonnte.com/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($waFd),
                    CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                    CURLOPT_TIMEOUT => 15
                ]);
                curl_exec($ch);
                curl_close($ch);
                
                logAgent("📈 NOTIFIKASI PENURUNAN PERFORMA IBADAH: Dikirim ke Musyrif $m_nama ($no_wa)");
                sleep(rand(1, 2));
            }
        }
    }
    
    file_put_contents($hrd_mutabaah_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Agent HRD: Pengecekan Mutaba'ah Musyrif Selesai.");
}

// =========================================================================================
// 5. AGENT HRD: LAPORAN RESUME AKTIFITAS PEGAWAI HARIAN (Pukul 21:00 WIB)
// =========================================================================================
$hrd_laporan_done = false;
$hrd_laporan_log_file = __DIR__ . '/agent_hrd_laporan_log.txt';
if (file_exists($hrd_laporan_log_file)) {
    if (strpos(file_get_contents($hrd_laporan_log_file), "SUCCESS_$today") !== false) $hrd_laporan_done = true;
}

$force_hrd_laporan = ($force === 'hrd_laporan');
if (($current_hour >= '21' || $force_hrd_laporan) && (!$hrd_laporan_done || $force_hrd_laporan)) {
    logAgent("======= MEMULAI AGENT HRD: LAPORAN RESUME AKTIFITAS PEGAWAI HARIAN ($today) =======");
    
    pastikanKoneksiDb();
    
    // Ambil daftar seluruh pegawai aktif
    $res_pegawai = $conn->query("SELECT id, nama, role FROM akun_ustadz WHERE status_pegawai = 'Aktif' ORDER BY nama ASC");
    
    if ($res_pegawai && $res_pegawai->num_rows > 0) {
        $hari_indo = [
            'Sunday' => 'Ahad', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $day_name_eng = date('l');
        $day_name_indo = $hari_indo[$day_name_eng] ?? $day_name_eng;
        $formatted_date = date('d-m-Y');
        
        $report_msg = "🤖 *LAPORAN HARIAN AKTIVITAS PEGAWAI*\n";
        $report_msg .= "📅 Hari/Tanggal: $day_name_indo, $formatted_date\n";
        $report_msg .= "⏰ Waktu Laporan: 21:00 WIB\n\n";
        $report_msg .= "Berikut adalah resume aktivitas seluruh pegawai hari ini:\n\n";
        
        $total_pegawai = 0;
        $total_hadir = 0;
        $total_izin = 0;
        $total_belum_absen = 0;
        $total_kbm = 0;
        $total_mutabaah = 0;
        
        $idx = 1;
        while ($pegawai = $res_pegawai->fetch_assoc()) {
            $peg_id = (int)$pegawai['id'];
            $peg_nama = $pegawai['nama'];
            $peg_role = $pegawai['role'] ?? '';
            
            // Parse role labels
            $roles_arr = explode(',', $peg_role);
            $roles_labeled = array_map(function($r) {
                $r = trim($r);
                return ucwords(str_replace('_', ' ', $r));
            }, $roles_arr);
            $roles_str = implode(', ', $roles_labeled);
            
            // 1. Absensi (jenis_absen = 'Pegawai')
            $res_abs = $conn->query("SELECT waktu_absen, status_kehadiran, keterangan FROM absensi_pegawai WHERE ustadz_id = $peg_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' ORDER BY waktu_absen ASC");
            
            $check_in = '';
            $check_out = '';
            $status_kehadiran = 'Belum Absen';
            $ket_izin = '';
            
            if ($res_abs && $res_abs->num_rows > 0) {
                while ($abs = $res_abs->fetch_assoc()) {
                    $status_k = $abs['status_kehadiran'];
                    $waktu = date('H:i', strtotime($abs['waktu_absen']));
                    
                    if ($status_k === 'Masuk') {
                        $check_in = $waktu;
                        $status_kehadiran = 'Hadir';
                    } elseif ($status_k === 'Pulang') {
                        $check_out = $waktu;
                    } elseif ($status_k === 'Izin') {
                        $status_kehadiran = 'Izin';
                        $ket_izin = $abs['keterangan'] ?? '';
                    }
                }
            }
            
            // Format text absensi harian
            $absen_text = '';
            if ($status_kehadiran === 'Hadir') {
                $absen_text = "Hadir (" . ($check_in ? "$check_in WIB" : "-");
                $absen_text .= " s/d " . ($check_out ? "$check_out WIB" : "Belum Check-out");
                $absen_text .= ")";
                $total_hadir++;
            } elseif ($status_kehadiran === 'Izin') {
                $absen_text = "Izin" . ($ket_izin ? " ($ket_izin)" : "");
                $total_izin++;
            } else {
                $absen_text = "Belum Absen / Alpha ⚠️";
                $total_belum_absen++;
            }
            
            // 2. KBM / Jurnal Mengajar
            $res_kbm = $conn->query("SELECT kelas, mata_pelajaran, materi FROM jurnal_mengajar WHERE ustadz_id = $peg_id AND tanggal = '$today'");
            $kbm_details = [];
            if ($res_kbm && $res_kbm->num_rows > 0) {
                while ($kbm = $res_kbm->fetch_assoc()) {
                    $kbm_details[] = "{$kbm['kelas']} ({$kbm['mata_pelajaran']} - {$kbm['materi']})";
                    $total_kbm++;
                }
            }
            $kbm_text = !empty($kbm_details) ? implode(', ', $kbm_details) : '-';
            
            // 3. Mutaba'ah (Musyrif)
            $res_mut = $conn->query("SELECT COUNT(id) as total FROM buku_mutabaah WHERE musyrif_id = $peg_id AND tanggal = '$today'");
            $mut_count = 0;
            if ($res_mut) {
                $mut_row = $res_mut->fetch_assoc();
                $mut_count = (int)$mut_row['total'];
                $total_mutabaah += $mut_count;
            }
            
            $mut_text = '-';
            if (strpos($peg_role, 'musyrif') !== false) {
                if ($mut_count > 0) {
                    $mut_text = "$mut_count Santri (Divalidasi)";
                } else {
                    $mut_text = "Belum Input ⚠️";
                }
            }
            
            // Susun ke pesan laporan
            $report_msg .= "$idx. *{$peg_nama}* (Role: {$roles_str})\n";
            $report_msg .= "   - Absen: $absen_text\n";
            $report_msg .= "   - KBM: $kbm_text\n";
            $report_msg .= "   - Mutaba'ah: $mut_text\n\n";
            
            $total_pegawai++;
            $idx++;
        }
        
        $report_msg .= "------------------------------------------\n";
        $report_msg .= "Total Pegawai Aktif: $total_pegawai Orang\n";
        $report_msg .= "Hadir: $total_hadir | Izin: $total_izin | Belum Absen: $total_belum_absen\n";
        $report_msg .= "KBM Terlaksana: $total_kbm Sesi\n";
        $report_msg .= "Mutaba'ah Diinput: $total_mutabaah Santri";
        
        // Kirim WhatsApp ke Bos
        $bos_wa = '6285196572223';
        $waFd = ['target' => $bos_wa, 'message' => $report_msg];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api.fonnte.com/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($waFd),
            CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            logAgent("-> Gagal mengirim Laporan Harian WA ke Bos ($bos_wa): " . $curl_error);
        } else {
            $res_obj = json_decode($response, true);
            if (isset($res_obj['status']) && $res_obj['status'] == true) {
                logAgent("-> Laporan Harian WA dilesatkan ke Bos ($bos_wa).");
            } else {
                logAgent("-> Fonnte menolak pengiriman Laporan Harian ke Bos ($bos_wa): " . ($res_obj['reason'] ?? $response));
            }
        }
    } else {
        logAgent("-> Tidak ada pegawai aktif untuk dilaporkan.");
    }
    
    file_put_contents($hrd_laporan_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Agent HRD: Laporan Resume Aktifitas Pegawai Harian Selesai.");
}
?>