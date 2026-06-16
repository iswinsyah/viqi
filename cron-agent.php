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

// Self-healing: Pastikan kolom status_broadcast ada di tabel artikel
@$conn->query("ALTER TABLE artikel ADD COLUMN status_broadcast ENUM('menunggu', 'terkirim') DEFAULT 'menunggu' AFTER status");
// Self-healing untuk tabel leads & footprints agar query tidak crash
@$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
@$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");
@$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(100) DEFAULT '' AFTER jenis_lead");
@$conn->query("ALTER TABLE visitor_footprints ADD COLUMN campaign VARCHAR(100) AFTER source");

$APP_URL = "https://villaquranindonesia.com"; // Gunakan URL absolut agar link broadcast tidak pecah saat dijalankan via Cron
$GAS_URL = $APP_URL . "/api-gemini.php";
$FONNTE_TOKEN = "Dtw72oRiQr8FympzpMHL";

$log_file = __DIR__ . '/agent_cron_log.txt';
$monthly_log_file = __DIR__ . '/agent_monthly_log.txt';
$daily_log_file = __DIR__ . '/agent_daily_log.txt';

// Waktu saat ini bagi Sang Agent
$today = date('Y-m-d');
$current_month = date('Y-m');
$current_day = date('d');
$current_hour = date('H');

// Fungsi Logging Otonom
function logAgent($msg) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] 🤖 AGENT: $msg\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
    echo $log_entry . "<br>";
}

// 1. CEK OTORITAS DARI PUSAT KENDALI
$autopilot = file_exists(__DIR__ . '/autopilot_status.txt') ? file_get_contents(__DIR__ . '/autopilot_status.txt') : 'OFF';
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
    } else {
        logAgent("❌ Gagal merumuskan Persona.");
    }

    sleep(5); // Jeda nafas API

    // 1B. MIKIR TREND MAKRO (BULANAN)
    logAgent("Agent Trend Scout: Menganalisa tren besar (Makro) untuk bulan ini...");
    $prompt_trend_macro_default = "Anda adalah seorang SEO & Market Trend Analyst. Berdasarkan data persona yang tersimpan, tentukan 1 TEMA BESAR untuk konten marketing bulan ini. Lalu, buat laporan singkat dalam format Markdown yang berisi: 1. Tema Besar Bulan Ini. 2. Tiga Pilar Konten turunan dari tema tersebut. 3. Rekomendasi 5 long-tail keywords utama yang relevan dengan tema besar.";
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
        logAgent("✅ Laporan Tren Makro bulanan berhasil dibuat.");
    } else {
        logAgent("❌ Gagal membuat laporan Tren Makro.");
    }
    
    sleep(5);

    // 1C. MIKIR KALENDER
    logAgent("Agent Perencana: Menyusun Kalender Konten 30 Hari ke depan...");
    $payloadKalender = $leads;
    $prompt_kalender_default = "WAJIB BUAT DALAM BENTUK TABEL MARKDOWN. TANGGAL MULAI HARI 1: $today. BUAT FULL SAMPAI HARI KE-30. KOLOM TABEL: | Hari/Tanggal | Topik | Judul Artikel SEO | Keyword yang Disasar | Copywriting Singkat (untuk WA/FB) |. DILARANG memberikan teks pendahuluan! ACUAN UTAMA STRATEGI KONTEN ADALAH LAPORAN TREN BERIKUT: \n\n";
    $prompt_kalender = file_exists(__DIR__ . '/prompt_kalender.txt') ? file_get_contents(__DIR__ . '/prompt_kalender.txt') : $prompt_kalender_default;
    $trend_makro_report = file_exists(__DIR__ . '/saved_trends_macro.txt') ? file_get_contents(__DIR__ . '/saved_trends_macro.txt') : 'Tidak ada laporan tren.';
    array_unshift($payloadKalender, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => str_replace('{{DATE}}', $today, $prompt_kalender) . $trend_makro_report,
        "status" => "URGENT"
    ]);

    $dataKalender = mikirKeGemini(['leads' => $payloadKalender, 'type' => 'kalender', 'date' => $today]);
    if (isset($dataKalender['status']) && $dataKalender['status'] === 'success') {
        file_put_contents(__DIR__ . '/saved_kalender.txt', $dataKalender['result']);
        logAgent("✅ Kalender konten 30 hari berhasil disusun.");
        file_put_contents($monthly_log_file, "SUCCESS_$current_month\n", FILE_APPEND);
        logAgent("Tugas Bulanan Selesai dengan Sukses! Agent kembali istirahat.");
    } else {
        logAgent("❌ Gagal menyusun Kalender.");
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
    
    // 3. Cari topik hari ini di Kalender
    $topic = "Keistimewaan Menghafal Al-Quran"; // Fallback topic
    $judul = "Keutamaan Menjadi Hafidz Quran di Usia Belia"; // Fallback judul
    $keyword = "pesantren tahfidz, hafal quran"; // Fallback keyword
    
    if (file_exists(__DIR__ . '/saved_kalender.txt')) {
        $kalender = file_get_contents(__DIR__ . '/saved_kalender.txt');
        $lines = explode("\n", $kalender);
        foreach($lines as $line) {
            if (strpos($line, $today) !== false) {
                $cols = array_map('trim', explode('|', $line));
                if(count($cols) >= 6) { // Pastikan kolomnya lengkap (5 data + 2 empty dari | di awal & akhir)
                    $topic = $cols[2]; // Kolom ke-2: Topik
                    $judul = $cols[3]; // Kolom ke-3: Judul Artikel SEO
                    $keyword = $cols[4]; // Kolom ke-4: Keyword yang Disasar
                    logAgent("Agent Penulis: Menemukan topik hari ini dari kalender -> Judul: '$judul', Keyword: '$keyword'");
                    break; 
                }
            }
        }
    } else {
        logAgent("Agent Penulis: Kalender tidak ditemukan, menggunakan topik fallback.");
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
            if (file_exists('uploads/')) {
                $files = glob('uploads/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
                if (!empty($files)) $gambar_cover = $files[array_rand($files)];
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
                sleep($jeda);
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