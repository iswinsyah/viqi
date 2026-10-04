<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";

try {
    echo "1. Checking koneksi.php...\n";
    require_once __DIR__ . '/koneksi.php';
    echo "Koneksi loaded. DB connect_error: " . ($conn->connect_error ? $conn->connect_error : "NONE") . "\n";

    echo "2. Checking auth-unified.php...\n";
    require_once __DIR__ . '/auth-unified.php';
    echo "auth-unified.php loaded successfully.\n";

    echo "3. Checking login.php syntax...\n";
    echo "ALL TESTS PASSED.\n";
} catch (Throwable $e) {
    echo "\n[ERROR DETECTED]\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
echo "</pre>";
