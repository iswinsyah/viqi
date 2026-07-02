<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "<h3>Executing cron-agent.php directly...</h3>";
ob_start();
$_GET['force'] = 'seo';
try {
    include 'cron-agent.php';
} catch (Exception $e) {
    echo "PHP Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
}
$output = ob_get_clean();

echo "<h4>Execution Log Output:</h4>";
echo "<div style='background:#f4f4f4; padding:15px; font-family:monospace; border:1px solid #ccc; max-height:400px; overflow-y:auto;'>";
echo nl2br(htmlspecialchars($output));
echo "</div>";
?>
