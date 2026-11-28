<?php
echo "Default Timezone: " . date_default_timezone_get() . "<br>";
echo "Current Date/Time: " . date('Y-m-d H:i:s') . "<br>";
date_default_timezone_set('Asia/Jakarta');
echo "Jakarta Date/Time: " . date('Y-m-d H:i:s') . "<br>";
?>
