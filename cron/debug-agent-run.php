<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Loading koneksi and config...<br>";
require_once 'koneksi.php';
require_once 'config-key.php';

echo "Database connection ok!<br>";

function testMikir($prompt) {
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $gasUrl = defined('GEMINI_GAS_URL') ? GEMINI_GAS_URL : '';
    
    echo "Using API Key: " . (!empty($apiKey) ? "YES" : "NO") . "<br>";
    echo "Using GAS URL: " . (!empty($gasUrl) ? "YES" : "NO") . "<br>";
    
    // 2. COBA KONEKSI LANGSUNG
    if (!empty($apiKey)) {
        echo "Trying direct connection...<br>";
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        echo "Direct HTTP Code: $httpCode<br>";
        if ($curlError) echo "Direct Curl Error: $curlError<br>";
        
        if (!$curlError && $httpCode === 200) {
            $res_arr = json_decode($response, true);
            $text = $res_arr['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!empty($text)) {
                return ['status' => 'success', 'result' => $text];
            }
        }
    }
    
    // 3. FALLBACK
    if (!empty($gasUrl)) {
        echo "Trying GAS tunnel fallback...<br>";
        $ch = curl_init($gasUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        
        $payload_gas = [
            'apiKey' => $apiKey,
            'prompt' => $prompt
        ];
        
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload_gas));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        echo "GAS HTTP Code: $httpCode<br>";
        if ($curlError) echo "GAS Curl Error: $curlError<br>";

        if (!$curlError && $httpCode === 200) {
            $res_arr = json_decode($response, true);
            $text = $res_arr['result'] ?? $response;
            return ['status' => 'success', 'result' => $text];
        }
    }
    
    return ['status' => 'error', 'message' => 'All connections failed'];
}

$start = microtime(true);
$res = testMikir("Tulis satu kata 'BINGO' saja.");
$duration = microtime(true) - $start;

echo "Duration: " . round($duration, 4) . " seconds<br>";
echo "Result Status: " . $res['status'] . "<br>";
echo "Result content: <pre>" . htmlspecialchars($res['result'] ?? $res['message']) . "</pre>";
?>
