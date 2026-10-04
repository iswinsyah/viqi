<?php
require_once __DIR__ . '/config-key.php';
$user_constants = get_defined_constants(true)['user'] ?? [];
echo "Defined user constants:\n";
foreach ($user_constants as $name => $val) {
    if (strpos($name, 'KEY') !== false || strpos($name, 'TOKEN') !== false || strpos($name, 'URL') !== false) {
        echo "- $name: [exists, length " . strlen($val) . "]\n";
    } else {
        echo "- $name: $val\n";
    }
}
unlink(__file__); // Delete itself after run for security
?>
