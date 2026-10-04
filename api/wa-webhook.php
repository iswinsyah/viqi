<?php
// api/wa-webhook.php
// Engine Webhook WhatsApp CS AI Konsultan Villa Quran Baron Malang
// Terintegrasi dengan Fonnte, Gemini AI, dan Tabel Training Knowledge Base

ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/config-key.php';

// Tangkap Payload Masuk (Support JSON & Form POST Fonnte)
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
if (!$inputData) $inputData = $_POST;

$sender = trim($inputData['sender'] ?? $inputData['from'] ?? '');
$message = trim($inputData['message'] ?? $inputData['text'] ?? '');
$name = trim($inputData['name'] ?? $inputData['pushname'] ?? 'Ayah/Bunda');
$is_test_mode = isset($_GET['test_mode']) || (isset($inputData['test_mode']) && $inputData['test_mode']);

if (empty($message)) {
    echo json_encode([
        'status' => 'ignored',
        'message' => 'Pesan kosong.'
    ]);
    exit;
}

// Normalisasi Format No WA
$clean_sender = preg_replace('/[^0-9]/', '', $sender);
if (strpos($clean_sender, '0') === 0) {
    $clean_sender = '62' . substr($clean_sender, 1);
}

// 1. CARI DATA LEAD WALI SANTRI DI DATABASE (Asesmen Kesiapan / SPMB)
$lead_info = null;
if (!empty($clean_sender)) {
    $stmt_lead = $conn->prepare("SELECT * FROM leads WHERE whatsapp = ? OR whatsapp LIKE ? ORDER BY id DESC LIMIT 1");
    $wa_like = "%" . substr($clean_sender, -9) . "%";
    $stmt_lead->bind_param("ss", $clean_sender, $wa_like);
    $stmt_lead->execute();
    $res_lead = $stmt_lead->get_result();
    if ($res_lead && $res_lead->num_rows > 0) {
        $lead_info = $res_lead->fetch_assoc();
    }
}

// 2. AMBIL SEMUA KNOWLEDGE BASE & SOP TRAINING CS AI YANG AKTIF
$knowledge_text = "";
$res_kb = $conn->query("SELECT * FROM ai_cs_knowledge WHERE status = 'aktif' ORDER BY prioritas DESC, id ASC");
if ($res_kb && $res_kb->num_rows > 0) {
    while ($kb = $res_kb->fetch_assoc()) {
        $knowledge_text .= "--- MATERI: " . strtoupper($kb['kategori']) . " (" . $kb['topik_pertanyaan'] . ") ---\n";
        $knowledge_text .= "Kata Kunci Relevan: " . $kb['kata_kunci'] . "\n";
        $knowledge_text .= "Instruksi/Fakta Resmi: " . $kb['instruksi_jawaban'] . "\n";
        if (!empty($kb['ajakan_open_house'])) {
            $knowledge_text .= "Arahan Closing Open House: " . $kb['ajakan_open_house'] . "\n";
        }
        $knowledge_text .= "\n";
    }
}

// 3. RANGKAI CONTEXT PERSONA & PROMPT GEMINI AI
$nama_wali = !empty($lead_info['nama']) ? $lead_info['nama'] : $name;
$nama_santri = !empty($lead_info['nama_santri']) ? $lead_info['nama_santri'] : 'Ananda';
$skor_kesiapan = !empty($lead_info['skor_kesiapan']) ? $lead_info['skor_kesiapan'] : null;
$kategori_kesiapan = !empty($lead_info['kategori_kesiapan']) ? $lead_info['kategori_kesiapan'] : null;
$detail_eval = !empty($lead_info['detail_evaluasi']) ? $lead_info['detail_evaluasi'] : null;
$jenjang = !empty($lead_info['jenjang']) ? $lead_info['jenjang'] : 'SMP/SMA';
$kota = !empty($lead_info['kota']) ? $lead_info['kota'] : 'Indonesia';

$context_prompt = "Kamu adalah 'Ustadzah Nurul / Tim Konsultan Pendidikan Villa Quran Baron Malang'.
Pondok Pesantren Villa Quran Baron Malang adalah pesantren bernuansa villa alam sejuk di lereng pegunungan Malang, ramah anak (bukan barak/militer), fokus pada Tahfidz Quran Joyful Learning, adab mandiri, dan sekolah formal terakreditasi.

DATA CALON WALI SANTRI:
- Nama Wali Santri: {$nama_wali}
- Nama Calon Santri: {$nama_santri}
- Jenjang Target: {$jenjang}
- Kota Asal: {$kota}";

if ($skor_kesiapan !== null) {
    $context_prompt .= "\n- Skor Asesmen Kesiapan Masuk Pondok: {$skor_kesiapan}% ({$kategori_kesiapan})";
    if ($detail_eval) {
        $context_prompt .= "\n- Detail Jawaban Asesmen: {$detail_eval}";
    }
}

$context_prompt .= "\n\nKNOWLEDGE BASE & SOP TRAINING RESMI PESANTREN:
{$knowledge_text}

ATURAN PERCAKAPAN & CLOSING:
1. Bersikaplah sangat santun, hangat, empatik, menggunakan sapaan islami ('Assalamu'alaikum Ayah/Bunda {$nama_wali}', 'Ananda {$nama_santri}').
2. Jika pesan adalah permintaan hasil asesmen ('Mohon dikirim analisa asesmen nya'), berikan intisari diagnosis psikologis kesiapan ananda secara positif, tunjukkan bagaimana sistem villa ramah anak di Villa Quran menjadi solusi terbaik untuk ananda.
3. Jawab pertanyaan mereka dengan ringkas, jelas, dan hindari paragraf yang terlalu panjang (gunakan format chat WhatsApp yang nyaman dibaca dengan bullet point dan emoji secukupnya).
4. MANDATORY CLOSING GOAL: Di akhir setiap jawaban, selipkan selalu ajakan hangat dan eksklusif untuk hadir ke agenda 'Open House & Kunjungan Santri (Survey Lokasi Villa Asrama & Tes Minat Bakat Gratis)' di akhir pekan ini (Sabtu / Ahad).
5. Jangan pernah menyebutkan bahwa kamu adalah program AI / Robot. Berperanlah seutuhnya sebagai Konsultan Pendidikan Villa Quran.";

$full_prompt = $context_prompt . "\n\nPESAN DARI WALI SANTRI:\n\"{$message}\"\n\nTuliskan balasan WhatsApp yang ideal:";

// 4. PANGGIL GEMINI AI ENGINE
$reply_text = "";
$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
$gasUrl = defined('GEMINI_GAS_URL') ? GEMINI_GAS_URL : '';

// Direct Gemini API
if (!empty($apiKey) && $apiKey !== 'YOUR_GEMINI_API_KEY') {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $full_prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 800
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $resData = json_decode($response, true);
        if (isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
            $reply_text = trim($resData['candidates'][0]['content']['parts'][0]['text']);
        }
    }
}

// Fallback via Google Apps Script (GAS) jika direct API gagal
if (empty($reply_text) && !empty($gasUrl)) {
    $ch = curl_init($gasUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prompt' => $full_prompt]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $resData = json_decode($response, true);
        if (isset($resData['result'])) {
            $reply_text = trim($resData['result']);
        } elseif (isset($resData['candidates'][0]['content']['parts'][0]['text'])) {
            $reply_text = trim($resData['candidates'][0]['content']['parts'][0]['text']);
        }
    }
}

// Fallback template cerdas jika AI offline
if (empty($reply_text)) {
    $reply_text = "🌿 *Assalamu'alaikum Warahmatullahi Wabarakatuh Ayah/Bunda {$nama_wali}*\n\n"
                . "Alhamdulillah, terima kasih telah menghubungi Tim Konsultan *Villa Quran Baron Malang*.\n\n"
                . "Mengenai kesiapan dan kenyamanan ananda tercinta *{$nama_santri}*, di Villa Quran kami membimbing santri dengan konsep asrama bernuansa villa alam sejuk di pegunungan Malang, sehingga anak-anak merasa nyaman seperti di rumah sendiri (*homey & joyful learning*).\n\n"
                . "🏠 *Undangan Eksklusif Open House:*\n"
                . "Cara terbaik memastikan ananda cocok adalah dengan melihat langsung suasananya. Kami mengundang Ayah/Bunda & Ananda untuk hadir di agenda *Open House & Tes Minat Bakat Santri* setiap Sabtu & Ahad.\n\n"
                . "Apakah berkenan kami buatkan reservasi seat undangan untuk akhir pekan ini?";
}

// 5. KIRIM JAWABAN BALIK KE WHATSAPP VIA FONNTE (Jika Bukan Test Simulator)
$fonnte_sent = false;
$token = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : '';

if (!$is_test_mode && !empty($clean_sender) && !empty($token) && $token !== 'YOUR_FONNTE_TOKEN_HERE') {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'target' => $clean_sender,
            'message' => $reply_text,
            'countryCode' => '62'
        ],
        CURLOPT_HTTPHEADER => [
            "Authorization: $token"
        ],
    ]);
    $res_fonnte = curl_exec($curl);
    curl_close($curl);
    $fonnte_sent = true;
}

// Output Respon JSON
echo json_encode([
    'status' => 'success',
    'sender' => $clean_sender,
    'reply' => $reply_text,
    'fonnte_sent' => $fonnte_sent,
    'is_test_mode' => $is_test_mode
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
