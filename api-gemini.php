<?php
// api-gemini.php
// Proxy untuk membelokkan request dari GAS langsung ke Gemini API di Hostinger
// Dilengkapi fitur fallback Tunneling via GAS apabila Hostinger memblokir port outbound ke Google API.

// Matikan error display agar warnings/notices tidak merusak format JSON
ini_set('display_errors', 0);
error_reporting(0);

if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

// Atur CORS agar aman
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

header('Content-Type: application/json');

// Tangkap request body (JSON murni dari fetch)
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    // Cek jika dikirim via form biasa (bukan JSON)
    $input = $_POST;
}

// Ekstrak prompt dari struktur payload yang biasa dikirim ke GAS
$prompt = '';
if (isset($input['leads']) && is_array($input['leads'])) {
    foreach ($input['leads'] as $lead) {
        if (isset($lead['jenis_lead']) && $lead['jenis_lead'] === 'SYSTEM_COMMAND') {
            $prompt = $lead['sumber_info'] ?? '';
            break;
        }
    }
}

// Jika tidak ada di leads, coba langsung dari parameter 'prompt' atau 'sumber_info'
if (empty($prompt)) {
    $prompt = $input['prompt'] ?? $input['sumber_info'] ?? '';
}

if (empty($prompt)) {
    echo json_encode(['status' => 'error', 'message' => 'Prompt tidak ditemukan dalam request.']);
    exit;
}

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
$gasUrl = defined('GEMINI_GAS_URL') ? GEMINI_GAS_URL : '';

// JIKA GAS_URL DITENTUKAN, GUNAKAN SEBAGAI TUNNEL (Mengatasi Blokir Firewall Outbound Hostinger)
if (!empty($gasUrl)) {
    $ch = curl_init($gasUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Kirim payload asli + injeksi apiKey agar diproses oleh GAS
    $payload = $input;
    $payload['apiKey'] = $apiKey;
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Ikuti redirect dari Google Apps Script

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['status' => 'error', 'message' => 'cURL Tunneling Error via GAS: ' . $curlError]);
        exit;
    }
    
    // Teruskan output JSON secara langsung
    echo $response;
    exit;
}

// FALLBACK: KONEKSI LANGSUNG JIKA GAS URL KOSONG
if (empty($apiKey)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'API Key Gemini belum dikonfigurasi di server. Silakan buat file "config-key.php" di root direktori proyek Anda di Hostinger (sejajar dengan api-gemini.php, bukan di dalam folder yayasan2).'
    ]);
    exit;
}

// Panggil Gemini API secara langsung (menggunakan model gemini-2.5-flash)
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

$payload = [
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
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['status' => 'error', 'message' => 'Gemini API Error (HTTP ' . $httpCode . '): ' . $response]);
    exit;
}

$result = json_decode($response, true);
$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

if (empty($text)) {
    echo json_encode(['status' => 'error', 'message' => 'Respon AI kosong atau format tidak sesuai.']);
    exit;
}

echo json_encode(['status' => 'success', 'result' => $text]);
exit;
?>
