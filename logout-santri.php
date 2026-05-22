<?php
session_start();
// Hapus semua session santri
unset($_SESSION['santri_logged_in']);
unset($_SESSION['santri_id']);
unset($_SESSION['santri_nama']);
header("Location: login-santri.php");
exit;
?>