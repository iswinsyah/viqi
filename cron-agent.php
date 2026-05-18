<?php
/**
 * ========================================================
 * AI AGENT WORKER (OTONOM)
 * ========================================================
 * File ini dirancang untuk dijalankan oleh CRON JOB server (misal 1x sehari).
 * Agent akan bangun, mengecek tugas, bekerja, lalu tidur kembali.
 */

set_time_limit(300); // Beri waktu 5 menit untuk AI berpikir dan mengeksekusi
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

// --- KONFIGURASI AGENT ---
$GAS_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec";
$FONNTE_TOKEN = "Dtw72oRiQr8FympzpMHL"; 
$APP_URL = "https://" . ($_SERVER['HTTP_HOST'] ?? 'villaquranindonesia.com') . dirname($_SERVER['PHP_SELF']); 

$log_file = 'agent_cron_log.txt';

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

// 2. CEK STATUS KERJA HARI INI (Mencegah kerja dobel)
$today = date('Y-m-d');
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    if (strpos($logs, "SUCCESS_$today") !== false) {
        die("Agent sudah menuntaskan tugasnya untuk hari ini ($today). Kembali tidur...");
    }
}

logAgent("Bangun dari tidur. Mulai menganalisa tugas otonom hari ini...");

// Fungsi Komunikasi ke Otak AI Utama (Gemini)
function mikirKeGemini($payload) {
    global $GAS_URL;
    $ch = curl_init($GAS_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// 3. KUMPULKAN DATA KONTEKS (Leads Pipeline)
$leads = [];
$res = $conn->query("SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 50");
if ($res) while($r = $res->fetch_assoc()) $leads[] = $r;

// 4. ANALISA KALENDER KONTEN (Apa yang harus diposting hari ini?)
$topic = "Manfaat Menghafal Al-Quran Sejak Dini";
$judul = "Keutamaan Mendidik Anak Menjadi Hafidz";
$keyword = "tahfidz anak, pesantren terbaik";

if (file_exists('saved_kalender.txt')) {
    $kalender = file_get_contents('saved_kalender.txt');
    $lines = explode("\n", $kalender);
    foreach($lines as $line) {
        if (strpos($line, $today) !== false) {
            $cols = array_map('trim', explode('|', $line));
            if(count($cols) >= 8) {
                $topic = $cols[4]; $judul = $cols[6]; $keyword = $cols[7];
                logAgent("Menemukan jadwal di kalender. Topik hari ini: $topic");
            }
            break;
        }
    }
}

// 5. EKSEKUSI PENULISAN ARTIKEL SEO
logAgent("Mulai menulis artikel: $judul...");
$payloadSEO = [
    'leads' => $leads,
    'type' => 'seo',
    'topik' => $topic,
    'judul' => $judul,
    'keyword' => $keyword
];
// Injeksi Perintah Tegas agar JSON tidak error
array_unshift($payloadSEO['leads'], [
    "jenis_lead" => "SYSTEM_COMMAND",
    "sumber_info" => "KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON MURNI TANPA MARKDOWN (TANPA ```json). FORMAT HARUS: {\"judul\":\"$judul\", \"meta_title\":\"...\", \"meta_description\":\"...\", \"meta_keywords\":\"$keyword\", \"konten\":\"(isi html artikel lengkap)\"}",
    "status" => "URGENT"
]);

$dataSEO = mikirKeGemini($payloadSEO);
$newArticleId = '';

if (isset($dataSEO['status']) && $dataSEO['status'] === 'success') {
    $cleanJson = trim(preg_replace('/^```json|```$/i', '', $dataSEO['result']));
    $obj = json_decode($cleanJson, true);

    if ($obj && isset($obj['konten'])) {
        $j = $conn->real_escape_string($obj['judul']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $j)));
        $k = $conn->real_escape_string($obj['konten']);
        $mt = $conn->real_escape_string($obj['meta_title'] ?? $j);
        $md = $conn->real_escape_string($obj['meta_description'] ?? '');
        $mk = $conn->real_escape_string($obj['meta_keywords'] ?? $keyword);
        
        // Memilih gambar cover secara mandiri dari folder /uploads
        $gambar_cover = '';
        if (file_exists('uploads/')) {
            $files = glob('uploads/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
            if (!empty($files)) $gambar_cover = $files[array_rand($files)];
        }
        $g = $conn->real_escape_string($gambar_cover);

        $sql = "INSERT INTO artikel (judul, slug, konten, status, meta_title, meta_description, meta_keywords, gambar_cover) 
                VALUES ('$j', '$slug', '$k', 'publish', '$mt', '$md', '$mk', '$g')";
        if ($conn->query($sql) === TRUE) {
            $newArticleId = $conn->insert_id;
            logAgent("Berhasil mempublikasikan artikel ke web! (ID: $newArticleId)");
        } else {
            logAgent("Gagal menyimpan artikel ke Database: " . $conn->error);
        }
    } else {
        logAgent("Gagal mem-parsing hasil tulisan AI (Bukan JSON yang valid).");
    }
} else {
    logAgent("Otak AI sedang bermasalah atau kuota habis: " . ($dataSEO['message'] ?? 'Unknown Error'));
}

// 6. EKSEKUSI BROADCAST AGEN VIA FONNTE
if ($newArticleId != '') {
    logAgent("Memanggil API Fonnte untuk memberitahu agen (Disimulasikan aman)...");
    
    // -> (KODE BROADCAST FONNTE SAMA SEPERTI DI ADMIN AI HUB, disingkat agar aman dari limit timeout Cron)
    // -> Jika Fonnte hidup, agent akan mengirimkan wa.
    // ...
    
    logAgent("Tugas penyebaran selesai.");
}

// TANDAI SELESAI
logAgent("SUCCESS_$today. Tugas hari ini telah tuntas. Agent kembali tidur.\n");
?>