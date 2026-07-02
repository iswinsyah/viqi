<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists('config-key.php')) {
    require_once 'config-key.php';
}

echo "=== DIAGNOSTIC START ===<br>";
echo "GEMINI_API_KEY defined: " . (defined('GEMINI_API_KEY') ? 'YES (' . strlen(GEMINI_API_KEY) . ' chars)' : 'NO') . "<br>";
echo "GEMINI_GAS_URL defined: " . (defined('GEMINI_GAS_URL') ? 'YES (' . strlen(GEMINI_GAS_URL) . ' chars)' : 'NO') . "<br>";

$prompt = "Tulis kata 'Halo Dunia' saja.";

if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
    echo "<br>Testing Gemini Direct...<br>";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . GEMINI_API_KEY;
    $payload = [
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    echo "Direct HTTP Code: $code<br>";
    if ($err) echo "Direct Curl Error: $err<br>";
    echo "Direct Response: " . htmlspecialchars($response) . "<br>";
}

if (defined('GEMINI_GAS_URL') && !empty(GEMINI_GAS_URL)) {
    echo "<br>Testing Gemini GAS Tunnel...<br>";
    $payload = [
        "prompt" => $prompt,
        "apiKey" => GEMINI_API_KEY
    ];
    $ch = curl_init(GEMINI_GAS_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    
    echo "GAS HTTP Code: $code<br>";
    if ($err) echo "GAS Curl Error: $err<br>";
    echo "GAS Response: " . htmlspecialchars($response) . "<br>";
}
?>
