<?php
echo "<h3>Remote Root Directory Listing</h3>";
$files = scandir(__DIR__);
echo "<pre>";
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $is_dir = is_dir(__DIR__ . '/' . $file) ? '[DIR] ' : '      ';
    echo $is_dir . $file . " (" . (is_file(__DIR__ . '/' . $file) ? filesize(__DIR__ . '/' . $file) : '-') . " bytes)\n";
}
echo "</pre>";
?>
