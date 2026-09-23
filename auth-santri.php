<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auto-bridge dari Unified Auth Session jika ada
if ((!isset($_SESSION['santri_logged_in']) || $_SESSION['santri_logged_in'] !== true) && isset($_SESSION['app_user_id'])) {
    $roles_str = $_SESSION['app_user_roles'] ?? '';
    if (strpos($roles_str, 'santri') !== false || in_array(strtolower($_SESSION['app_username'] ?? ''), ['winsyah', 'viqi'])) {
        $_SESSION['santri_logged_in'] = true;
        $_SESSION['santri_id'] = (int)($_SESSION['app_user_id']);
        $_SESSION['santri_nama'] = $_SESSION['app_user_nama'] ?? 'Santri';
    }
}

// Cek session login santri
if (!isset($_SESSION['santri_logged_in']) || $_SESSION['santri_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>