<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_authenticated = (!empty($_SESSION['yayasan2_logged_in']) && $_SESSION['yayasan2_logged_in'] === true)
    || !empty($_SESSION['app_user_id'])
    || !empty($_SESSION['ustadz_id'])
    || !empty($_SESSION['user_id']);

if (!$is_authenticated) {
    header("Location: login.php");
    exit;
}
?>