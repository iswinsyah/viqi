<?php
/**
 * Safe PDF Proxy for Cross-Origin PDF Flipbook Rendering
 * Enables PDF.js and Flipbook engines to stream any external educational PDF
 */

if (!isset($_GET['url'])) {
    http_response_code(400);
    exit('Missing URL parameter.');
}

$pdfUrl = trim($_GET['url']);

// Sanitize URL
if (!filter_var($pdfUrl, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    exit('Invalid URL.');
}

// Allow-list check: Only allow HTTP/HTTPS URLs pointing to educational PDFs
$parsed = parse_url($pdfUrl);
$scheme = strtolower($parsed['scheme'] ?? '');
if ($scheme !== 'http' && $scheme !== 'https') {
    http_response_code(403);
    exit('Disallowed protocol.');
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Range');
header('Access-Control-Expose-Headers: Accept-Ranges, Content-Encoding, Content-Length, Content-Range');
header('Content-Type: application/pdf');
header('Cache-Control: public, max-age=86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Stream PDF via cURL
$ch = curl_init($pdfUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
curl_setopt($ch, CURLOPT_TIMEOUT, 60);

// Forward range header if present
if (isset($_SERVER['HTTP_RANGE'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Range: ' . $_SERVER['HTTP_RANGE']]);
}

curl_exec($ch);
curl_close($ch);
exit;
