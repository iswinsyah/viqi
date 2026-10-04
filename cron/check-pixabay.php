<?php
require_once __DIR__ . '/config-key.php';
echo "Key exists: " . (defined('PIXABAY_API_KEY') ? 'YES' : 'NO') . "\n";
echo "Key value length: " . strlen(PIXABAY_API_KEY) . "\n";
if (defined('PIXABAY_API_KEY') && strlen(PIXABAY_API_KEY) > 0) {
    echo "First 4 chars: " . substr(PIXABAY_API_KEY, 0, 4) . "...\n";
}
unlink(__file__); // Delete itself after run for security
?>
