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

// Atur zona waktu ke Waktu Indonesia Barat (WIB) agar jadwal akurat
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/koneksi.php';
if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

// Self-healing: Pastikan kolom status_broadcast ada di tabel artikel
@$conn->query("ALTER TABLE artikel ADD COLUMN status_broadcast ENUM('menunggu', 'terkirim') DEFAULT 'menunggu' AFTER status");
// Self-healing untuk tabel leads & footprints agar query tidak crash
@$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
@$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");
@$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(100) DEFAULT '' AFTER jenis_lead");
@$conn->query("ALTER TABLE visitor_footprints ADD COLUMN campaign VARCHAR(100) AFTER source");

$APP_URL = "https://villaquranindonesia.com"; // Gunakan URL absolut agar link broadcast tidak pecah saat dijalankan via Cron
$GAS_URL = $APP_URL . "/api-gemini.php";
$FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";

$log_file = __DIR__ . '/agent_cron_log.txt';
$monthly_log_file = __DIR__ . '/agent_monthly_log.txt';
$daily_log_file = __DIR__ . '/agent_daily_log.txt';

// Waktu saat ini bagi Sang Agent
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_day = date('d');
$current_hour = '07';

// Fungsi Logging Otonom
function logAgent($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] 🤖 AGENT: $msg\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry . "<br>";
}

// 1. CEK OTORITAS DARI PUSAT KENDALI
$autopilot = file_exists(__DIR__ . '/autopilot_status.txt') ? trim(file_get_contents(__DIR__ . '/autopilot_status.txt')) : 'OFF';
if ($autopilot !== 'ON') {
    die("Agent sedang dinonaktifkan (OFF) dari Pusat Kendali. Menunggu izin Bos...");
}

// Fungsi Komunikasi ke Otak AI Utama (Gemini)
function mikirKeGemini($payload) {
    global $GAS_URL;
    $ch = curl_init($GAS_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // Maksimal 2 menit nunggu balasan Google
    $response = curl_exec($ch);
    if(curl_errno($ch)) logAgent("Error koneksi ke Otak AI: " . curl_error($ch));
    curl_close($ch);
    return json_decode($response, true);
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
            return $data['hits'][$idx]['webformatURL'] ?? '';
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
                    return $data_fallback['hits'][$idx]['webformatURL'] ?? '';
                }
            }
        }
    }
    return '';
}

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
    $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 100");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;
    
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
    
    // Keluar agar tugas harian (jika kebetulan jam 07:00 juga) diproses di eksekusi Cron berikutnya (Mencegah PHP timeout)
    exit; 
}

// =========================================================================================
// TUGAS HARIAN (Tiap Hari, Jam 07:00) : ARTIKEL SEO & BROADCAST KURIR
// =========================================================================================
$daily_done = false;
if (file_exists($daily_log_file)) {
    if (strpos(file_get_contents($daily_log_file), "SUCCESS_$today") !== false) $daily_done = true;
}

if ($current_hour >= '07' && !$daily_done) {
    logAgent("======= MEMULAI TUGAS HARIAN ($today) =======");

    // 0. MIKIR TREND HARIAN (Dulu Bulanan)
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

    sleep(5);

    // 1. MIKIR TREND MIKRO (SEKARANG HARIAN)
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

    sleep(5);

    // 2. MIKIR COMMUNITY SCOUT (SEKARANG HARIAN)
    logAgent("Agent Community Scout: Mencari grup komunitas potensial hari ini...");
    $persona = file_exists(__DIR__ . '/saved_persona.txt') ? file_get_contents(__DIR__ . '/saved_persona.txt') : 'Orang tua yang mencari pesantren untuk anak.';
    $prompt_community_default = "Anda adalah seorang Digital Community Specialist. Target audiens kita adalah: \n\n{{PERSONA}}\n\n Tugas Anda adalah mencari link grup WhatsApp, Telegram, dan Facebook yang relevan dengan target audiens tersebut. Buat laporan dalam bentuk TABEL MARKDOWN dengan kolom: | Nama Grup | Platform | Link Gabung | Analisa Relevansi | Skor Kualitas (1-10) | Saran Pembuka Diskusi |. Cari minimal 5 grup.";
    $prompt_community_raw = file_exists(__DIR__ . '/prompt_community_scout.txt') ? file_get_contents(__DIR__ . '/prompt_community_scout.txt') : $prompt_community_default;
    $prompt_community = str_replace('{{PERSONA}}', $persona, $prompt_community_raw);
    $payloadCommunity = [
        [
            "jenis_lead" => "SYSTEM_COMMAND",
            "sumber_info" => $prompt_community,
            "status" => "URGENT"
        ]
    ];
    $dataCommunity = mikirKeGemini(['leads' => $payloadCommunity, 'type' => 'community_scout']);
    if (isset($dataCommunity['status']) && $dataCommunity['status'] === 'success') {
        file_put_contents(__DIR__ . '/saved_communities.txt', $dataCommunity['result']);
        logAgent("✅ Laporan pencarian komunitas berhasil dibuat.");
    } else {
        logAgent("❌ Gagal membuat laporan pencarian komunitas.");
    }

    sleep(5);
    // 3. MIKIR HOOK & KEYWORD EXPLORER (HARIAN)
    logAgent("Agent Hook & Keyword Explorer: Meriset opsi judul hook viral dan keyword...");
    $trend_macro = file_exists(__DIR__ . '/saved_trends_macro.txt') ? file_get_contents(__DIR__ . '/saved_trends_macro.txt') : 'Tidak ada laporan tren makro.';
    $leads = [];
    $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 50");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;
    
    $prompt_hook_explorer_default = "Anda adalah AI Agent Riset Hook & Keyword SEO. Tugas Anda adalah meriset dan memilih judul hook yang bisa viral serta keyword yang tepat sesuai algoritma Google Search terbaru, berdasarkan hasil riset Trend Scout berikut:\n\n{{TREND_SCOUT}}\n\nKetentuan:\n1. Target audiens: Orang tua dengan anak remaja usia 10-15 tahun, dalam konteks Islamic Parenting / Pendidikan Remaja Muslim.\n2. Riset 5 opsi judul hook viral yang memicu rasa penasaran/emosi (menggunakan formula hook seperti pengakuan, kontradiktif, pertanyaan retoris, dsb).\n3. Tentukan keyword utama & turunan yang memiliki potensi trafik tinggi dan relevan sesuai algoritma Google Search terbaru.\n4. Pilih 1 kombinasi terbaik yang paling berpotensi viral dan memiliki search intent yang kuat untuk ditulis hari ini.\n5. Berikan output dalam format JSON murni tanpa markdown (tanpa ```json). Format JSON harus tepat seperti ini:\n{\n  \"selected_topic\": \"Topik singkat dari judul terpilih\",\n  \"selected_title\": \"Judul Hook Terpilih yang Bisa Viral\",\n  \"selected_keyword\": \"keyword utama, keyword turunan 1, keyword turunan 2\",\n  \"report\": \"# Laporan Riset Hook & Keyword\\n\\n(Sajikan laporan lengkap riset Anda dalam format markdown di sini. Laporkan 5 opsi judul hook beserta keyword masing-masing, analisis kecocokan algoritma Google Search, serta alasan kuat pemilihan 1 judul terbaik untuk hari ini.)\"\n}";
    
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
        $cleanJson = trim(preg_replace('/^```json|```$/i', '', $dataExplorer['result']));
        $obj = json_decode($cleanJson, true);
        if ($obj && isset($obj['selected_title'])) {
            $selected_keyword = $obj['selected_keyword'] ?? '';
            $selected_image = '';
            if (!empty($selected_keyword)) {
                $selected_image = dapatkanGambarPixabay($selected_keyword);
            }
            file_put_contents(__DIR__ . '/today_seo_task.json', json_encode([
                'selected_topic' => $obj['selected_topic'] ?? '',
                'selected_title' => $obj['selected_title'] ?? '',
                'selected_keyword' => $selected_keyword,
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

    sleep(5);

    // 4. Ambil topik/judul/keyword hari ini dari Hook & Keyword Explorer atau fallback
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
    $leads = []; $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 50");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;

    $payloadSEO = $leads;
    $prompt_seo_default = "ATURAN WAJIB: KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON MURNI TANPA MARKDOWN (TANPA ```json). FORMAT: {\"judul\":\"{{JUDUL}}\", \"meta_title\":\"...\", \"meta_description\":\"...\", \"meta_keywords\":\"{{KEYWORD}}\", \"konten\":\"(isi html artikel lengkap)\"}. Bahas topik: {{TOPIK}}. PERTIMBANGKAN JUGA insight dari laporan tren terbaru berikut: \n\n{{TREND_MIKRO}}";
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
        $cleanJson = trim(preg_replace('/^```json|```$/i', '', $dataSEO['result']));
        $obj = json_decode($cleanJson, true);

        if ($obj && isset($obj['konten'])) {
            $j = $conn->real_escape_string($obj['judul']);
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $j)));
            $k = $conn->real_escape_string($obj['konten']);
            
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
            
            $sql = "INSERT INTO artikel (judul, slug, konten, status, meta_title, meta_description, meta_keywords, gambar_cover) 
                    VALUES ('$j', '$slug', '$k', 'publish', '{$obj['meta_title']}', '{$obj['meta_description']}', '{$obj['meta_keywords']}', '$gambar_cover')";
            if ($conn->query($sql) === TRUE) {
                $newArticleId = $conn->insert_id;
                logAgent("✅ Artikel otomatis dipublikasikan! (ID: $newArticleId)");
            }
        }
    }

    // 5. BROADCAST PUBLISHER (SEKARANG LEBIH PINTAR)
    logAgent("Agent Publisher: Mencari artikel yang belum disebar...");
    $res_artikel_kirim = $conn->query("SELECT id, judul FROM artikel WHERE status = 'publish' AND status_broadcast = 'menunggu' ORDER BY COALESCE(published_at, created_at) ASC LIMIT 1");
    
    if ($res_artikel_kirim && $res_artikel_kirim->num_rows > 0) {
        $artikel_kirim = $res_artikel_kirim->fetch_assoc();
        $artikel_id_kirim = $artikel_kirim['id'];
        $artikel_judul_kirim = $artikel_kirim['judul'];

        logAgent("Menemukan artikel (ID: $artikel_id_kirim) '$artikel_judul_kirim'. Memulai proses broadcast...");

        $agen_data = [];
        $resA = $conn->query("SELECT nama, whatsapp, kode_ref FROM agen");
        if($resA) while($r = $resA->fetch_assoc()) $agen_data[] = $r;

        if (count($agen_data) > 0 && $FONNTE_TOKEN !== "TOKEN_API_FONNTE_ANDA") {
            $prompt_publisher_default = "Assalamu'alaikum Kak {{NAMA_AGEN}}, artikel terbaru Villa Quran udah rilis pagi ini lho. \n\nJudul: *{{JUDUL_ARTIKEL}}* \n\nMonggo di-share pake link afiliasi khusus Kakak di bawah ini ya, biar komisinya kecatat otomatis: \n{{LINK_AFILIASI}} \n\nSemoga hari ini closing banyak, Aamiin!";
            $prompt_publisher_raw = file_exists(__DIR__ . '/prompt_publisher.txt') ? file_get_contents(__DIR__ . '/prompt_publisher.txt') : $prompt_publisher_default;

            foreach ($agen_data as $agen) {
                $link = $APP_URL . "/artikel-detail.php?id=" . $artikel_id_kirim . "&ref=" . $agen['kode_ref'];
                $replacements_wa = ['{{NAMA_AGEN}}' => $agen['nama'], '{{JUDUL_ARTIKEL}}' => $artikel_judul_kirim, '{{LINK_AFILIASI}}' => $link];
                $pesan = str_replace(array_keys($replacements_wa), array_values($replacements_wa), $prompt_publisher_raw);

                $waFd = ['target' => $agen['whatsapp'], 'message' => $pesan];
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => "https://api.fonnte.com/send",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => http_build_query($waFd),
                    CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                    CURLOPT_TIMEOUT => 30
                ]);
                curl_exec($ch);
                curl_close($ch);
                
                $jeda = rand(60, 180); // Jeda acak 1-3 menit
                logAgent("-> Pesan WA (ID: $artikel_id_kirim) dilesatkan ke: {$agen['nama']}. Jeda {$jeda} detik...");
                sleep(1);
            }

            // Setelah selesai broadcast, update status artikel
            $conn->query("UPDATE artikel SET status_broadcast = 'terkirim' WHERE id = $artikel_id_kirim");
            logAgent("✅ Broadcast untuk artikel ID $artikel_id_kirim selesai. Status diupdate.");
        }
    } else {
        logAgent("Tidak ada artikel baru untuk disebar. Semua sudah terkirim.");
    }

    // TANDAI SELESAI
    file_put_contents($daily_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Tugas Harian ($today) Tuntas! Agent kembali tidur.\n");
    exit;
}
?>