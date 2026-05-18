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

require_once 'koneksi.php';

// --- KONFIGURASI AGENT ---
$GAS_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec";
$FONNTE_TOKEN = "Dtw72oRiQr8FympzpMHL"; 
$APP_URL = "https://" . ($_SERVER['HTTP_HOST'] ?? 'villaquranindonesia.com') . dirname($_SERVER['PHP_SELF']); 

$log_file = 'agent_cron_log.txt';
$monthly_log_file = 'agent_monthly_log.txt';
$daily_log_file = 'agent_daily_log.txt';

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
$autopilot = file_exists('autopilot_status.txt') ? file_get_contents('autopilot_status.txt') : 'OFF';
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
// TUGAS BULANAN (Tiap Tanggal 1, Jam 06:00) : PERSONA & KALENDER 30 HARI
// =========================================================================================
$monthly_done = false;
if (file_exists($monthly_log_file)) {
    if (strpos(file_get_contents($monthly_log_file), "SUCCESS_$current_month") !== false) $monthly_done = true;
}

if ($current_day == '01' && $current_hour >= '06' && !$monthly_done) {
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
    if (count($footprints) > 0) array_unshift($payloadPersona, ["jenis_lead" => "DATA_JEJAK_PENGUNJUNG_MATA_AI", "sumber_info" => json_encode($footprints), "status" => "TOLONG_ANALISA_JUGA"]);
    
    array_unshift($payloadPersona, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => "Buat laporan analisa Buyer Persona terstruktur (TOFU, MOFU, BOFU) dalam format Markdown.",
        "status" => "URGENT"
    ]);

    $dataPersona = mikirKeGemini(['leads' => $payloadPersona, 'type' => 'persona']);
    if (isset($dataPersona['status']) && $dataPersona['status'] === 'success') {
        file_put_contents('saved_persona.txt', $dataPersona['result']);
        logAgent("✅ Persona bulan ini berhasil dirumuskan.");
    } else {
        logAgent("❌ Gagal merumuskan Persona.");
    }

    sleep(5); // Jeda nafas API

    // 1B. MIKIR KALENDER
    logAgent("Agent Perencana: Menyusun Kalender Konten 30 Hari ke depan...");
    $payloadKalender = $leads;
    array_unshift($payloadKalender, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => "WAJIB BUAT DALAM BENTUK TABEL MARKDOWN. TANGGAL MULAI HARI 1: $today. BUAT FULL SAMPAI HARI KE-30. KOLOM TABEL: | Hari/Tanggal | Platform | Format | Topik/Ide Konten | Copywriting Singkat | Judul Artikel SEO | Keyword yang Disasar |. DILARANG memberikan teks pendahuluan!",
        "status" => "URGENT"
    ]);

    $dataKalender = mikirKeGemini(['leads' => $payloadKalender, 'type' => 'kalender', 'date' => $today]);
    if (isset($dataKalender['status']) && $dataKalender['status'] === 'success') {
        file_put_contents('saved_kalender.txt', $dataKalender['result']);
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
    
    // 2A. Cari topik hari ini di Kalender
    $topic = "Keistimewaan Menghafal Al-Quran";
    $judul = "Keutamaan Menjadi Hafidz Quran di Usia Belia";
    $keyword = "pesantren tahfidz, hafal quran";
    
    if (file_exists('saved_kalender.txt')) {
        $kalender = file_get_contents('saved_kalender.txt');
        $lines = explode("\n", $kalender);
        foreach($lines as $line) {
            if (strpos($line, $today) !== false) {
                $cols = array_map('trim', explode('|', $line));
                if(count($cols) >= 8) { $topic = $cols; $judul = $cols; $keyword = $cols; break; }
            }
        }
        logAgent("Agent Penulis: Menemukan topik hari ini dari kalender -> $topic");
    }

    // 2B. MIKIR ARTIKEL SEO
    logAgent("Mulai menulis draf artikel: $judul...");
    $leads = []; $resL = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 50");
    if($resL) while($r = $resL->fetch_assoc()) $leads[] = $r;

    $payloadSEO = $leads;
    array_unshift($payloadSEO, [
        "jenis_lead" => "SYSTEM_COMMAND",
        "sumber_info" => "ATURAN WAJIB: KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON MURNI TANPA MARKDOWN (TANPA ```json). FORMAT: {\"judul\":\"$judul\", \"meta_title\":\"...\", \"meta_description\":\"...\", \"meta_keywords\":\"$keyword\", \"konten\":\"(isi html artikel lengkap)\"}. Bahas topik: $topic",
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

    // 2C. BROADCAST KURIR KE AGEN (Jika artikel berhasil terbit)
    if ($newArticleId != '') {
        logAgent("Agent Kurir: Bersiap menyebarkan link artikel ke para Agen via WA...");
        $agen_data = [];
        $resA = $conn->query("SELECT nama, whatsapp FROM agen");
        if($resA) while($r = $resA->fetch_assoc()) $agen_data[] = $r;

        if (count($agen_data) > 0 && $FONNTE_TOKEN !== "TOKEN_API_FONNTE_ANDA") {
            foreach ($agen_data as $agen) {
                $link = $APP_URL . "/artikel-detail.php?id=" . $newArticleId . "&ref=" . $agen['whatsapp'];
                $pesan = "Assalamu'alaikum {$agen['nama']}, artikel terbaru Villa Quran udah rilis pagi ini. \n\nMonggo di-share pake link ini ya, biar leadnya kecatat masuk ke njenengan: \n$link \n\nSemoga hari ini closing banyak, Aamiin!";
                
                $waFd = array('target' => $agen['whatsapp'], 'message' => $pesan);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.fonnte.com/send");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $waFd);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: $FONNTE_TOKEN"));
                curl_exec($ch);
                curl_close($ch);
                
                logAgent("-> Pesan WA dilesatkan ke: " . $agen['nama']);
                sleep(1); // Jeda anti blokir ringan
            }
        }
    }

    // TANDAI SELESAI
    file_put_contents($daily_log_file, "SUCCESS_$today\n", FILE_APPEND);
    logAgent("🎉 Tugas Harian ($today) Tuntas! Agent kembali tidur.\n");
    exit;
}
?>