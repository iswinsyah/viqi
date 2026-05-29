<?php
session_start();
if (!isset($_SESSION['orangtua_logged_in']) || $_SESSION['orangtua_logged_in'] !== true) {
    header("Location: login-orangtua.php");
    exit;
}
?>