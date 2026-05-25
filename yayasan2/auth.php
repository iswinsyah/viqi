<?php
session_start();
if (!isset($_SESSION['yayasan2_logged_in']) || $_SESSION['yayasan2_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>