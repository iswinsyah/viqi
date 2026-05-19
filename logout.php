<?php
session_start();
unset($_SESSION['yayasan_logged_in']);
header("Location: login.php");
exit;
?>