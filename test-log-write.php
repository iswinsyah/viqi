<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$log_file = __DIR__ . '/agent_cron_log.txt';
$test_msg = "[" . date('Y-m-d H:i:s') . "] TEST WRITE\n";
$res = file_put_contents($log_file, $test_msg, FILE_APPEND);
if ($res === false) {
    echo "Failed to write to $log_file!<br>";
} else {
    echo "Successfully wrote $res bytes to $log_file!<br>";
}
?>
