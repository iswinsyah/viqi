<?php
session_start();
unset($_SESSION['ustadz_logged_in']);
unset($_SESSION['ustadz_id']);
unset($_SESSION['ustadz_nama']);
header("Location: login-ustadz.php");
exit;
?>