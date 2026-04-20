<?php
session_start();

// Cek apakah sesi login admin sudah aktif
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>