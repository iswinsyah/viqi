<?php
session_start();
// Cek session login santri
if (!isset($_SESSION['santri_logged_in']) || $_SESSION['santri_logged_in'] !== true) {
    header("Location: login-santri.php");
    exit;
}
?>