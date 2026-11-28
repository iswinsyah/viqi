<?php
echo "Default Timezone: " . date_default_timezone_get() . "<br>";
echo "Current Date/Time: " . date('Y-m-d H:i:s') . "<br>";
date_default_timezone_set('Asia/Jakarta');
echo "Jakarta Date/Time: " . date('Y-m-d H:i:s') . "<br>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? '') . "<br>";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? '') . "<br>";
echo "DIR: " . __DIR__ . "<br>";
echo "REALPATH: " . realpath(__DIR__) . "<br>";
?>
