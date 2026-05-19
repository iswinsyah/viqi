<?php
session_start();
// Cek session login ustadz
if (!isset($_SESSION['ustadz_logged_in']) || $_SESSION['ustadz_logged_in'] !== true) {
    header("Location: login-ustadz.php");
    exit;
}
?>