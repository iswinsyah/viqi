<?php
session_start();
if (!isset($_SESSION['ustadz_logged_in']) || $_SESSION['ustadz_logged_in'] !== true) {
    header("Location: login-ustadz.php");
    exit;
}
?>