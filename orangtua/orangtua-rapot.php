<?php
// Sesuai instruksi: Rapor Akademik difungsikan dan disatukan penuh ke Rapor PKBM
$target_url = 'orangtua-rapot-pkbm.php';
if (!empty($_SERVER['QUERY_STRING'])) {
    $target_url .= '?' . $_SERVER['QUERY_STRING'];
}
header("Location: $target_url");
exit;