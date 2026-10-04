<?php
// api-ai.php
// Proxy yang mencoba Gemma (Ollama) terlebih dahulu, jika gagal akan fallback ke api-gemini.php
ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
header('Content-Type: application/json');

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);
if (!$input) $input = $_POST;

// Build prompt using same logic as api-gemini
$prompt = '';
$systemCommand = '';
$leadsData = [];
if (isset($input['leads']) && is_array($input['leads'])) {
    foreach ($input['leads'] as $lead) {
        if (isset($lead['jenis_lead']) && $lead['jenis_lead'] === 'SYSTEM_COMMAND') {
            $systemCommand = $lead['sumber_info'] ?? '';
        } else {
            $leadsData[] = $lead;
        }
    }
}
$prompt = $input['prompt'] ?? $input['sumber_info'] ?? '';
if (!empty($systemCommand)) {
    $prompt = $systemCommand . "\n\n" . $prompt;
}
if (!empty($leadsData)) {
    $prompt .= "\n\nBerikut data leads untuk dianalisis:\n" . json_encode($leadsData, JSON_PRETTY_PRINT);
}
$prompt = trim($prompt);
if (empty($prompt)) {
    echo json_encode(['status' => 'error', 'message' => 'Prompt tidak ditemukan dalam request.']);
    exit;
}

// Try Ollama Gemma (local network). Adjust host/port if needed.
$ollama_host = 'http://192.168.1.10:11434';
$ollama_endpoint = $ollama_host . '/api/generate';
$ollama_payload = [
    'model' => 'gemma',
    'prompt' => $prompt
];

$ch = curl_init($ollama_endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($ollama_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if (!$curlError && $httpCode >= 200 && $httpCode < 300 && !empty($response)) {
    $decoded = json_decode($response, true);
    $text = '';
    // Try common response shapes for Ollama-like APIs
    if (is_array($decoded)) {
        // Try choices -> message/content/parts
        if (isset($decoded['choices'][0]['message']['content']['parts'][0])) {
            $text = $decoded['choices'][0]['message']['content']['parts'][0];
        } elseif (isset($decoded['choices'][0]['text'])) {
            $text = $decoded['choices'][0]['text'];
        } elseif (isset($decoded['output'][0]['content'][0]['text'])) {
            $text = $decoded['output'][0]['content'][0]['text'];
        } elseif (isset($decoded['results'][0]['output_text'])) {
            $text = $decoded['results'][0]['output_text'];
        } elseif (isset($decoded['text'])) {
            $text = $decoded['text'];
        }
    }

    if (!empty($text)) {
        echo json_encode(['status' => 'success', 'result' => $text]);
        exit;
    }
}

// Jika Ollama/Gemma gagal (timeout, error, atau tidak ada text), fallback ke api-gemini.php
// Include langsung agar api-gemini.php menangani dan meng-output respon JSON
if (file_exists(__DIR__ . '/api-gemini.php')) {
    include __DIR__ . '/api-gemini.php';
    // api-gemini.php biasanya akan exit setelah mengeluarkan JSON
}

// Jika tidak ditemukan api-gemini.php
echo json_encode(['status' => 'error', 'message' => 'Koneksi ke Gemma gagal dan fallback ke Gemini tidak tersedia.']);
exit;
