<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Running cron-agent.php via CLI exec...</h3>";
$cmd = "php -d display_errors=1 cron-agent.php force=seo 2>&1";
exec($cmd, $output, $return_var);

echo "Return Code: $return_var<br>";
echo "<h4>Terminal Output:</h4>";
echo "<pre>" . htmlspecialchars(implode("\n", $output)) . "</pre>";
?>
