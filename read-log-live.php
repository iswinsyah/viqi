<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$log_file = __DIR__ . '/agent_cron_log.txt';
clearstatcache();
if (file_exists($log_file)) {
    $content = file_get_contents($log_file);
    echo "Total size: " . strlen($content) . " bytes\n";
    echo "=====================================\n";
    $lines = explode("\n", $content);
    $last_lines = array_slice($lines, -50);
    echo implode("\n", $last_lines);
} else {
    echo "Log file not found.";
}
?>
