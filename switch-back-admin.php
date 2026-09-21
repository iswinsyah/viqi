<?php
session_start();

if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
    $admin_id = $_SESSION['impersonator_admin_id'] ?? 9999;
    $admin_nama = $_SESSION['impersonator_admin_nama'] ?? 'Super Admin';
    $admin_role = $_SESSION['impersonator_admin_role'] ?? 'super_admin';

    // Restore sesi Super Admin (Legacy)
    $_SESSION['ustadz_logged_in'] = true;
    $_SESSION['ustadz_id'] = $admin_id;
    $_SESSION['ustadz_nama'] = $admin_nama;
    $_SESSION['ustadz_role'] = $admin_role;
    $_SESSION['admin_logged_in'] = $_SESSION['impersonator_admin_logged'] ?? true;
    $_SESSION['yayasan_logged_in'] = $_SESSION['impersonator_yayasan_logged'] ?? true;
    $_SESSION['yayasan2_logged_in'] = $_SESSION['impersonator_yayasan2_logged'] ?? true;

    // Restore sesi Unified Authentication
    $_SESSION['app_user_id'] = $_SESSION['impersonator_app_user_id'] ?? 1;
    $_SESSION['app_username'] = $_SESSION['impersonator_app_username'] ?? 'winsyah';
    $_SESSION['app_user_nama'] = $_SESSION['impersonator_app_user_nama'] ?? $admin_nama;
    $_SESSION['app_user_roles'] = $_SESSION['impersonator_app_user_roles'] ?? 'super_admin,ketua_yayasan';
    $_SESSION['active_role_views'] = $_SESSION['impersonator_active_role_views'] ?? ['all'];

    // Bersihkan flag impersonasi & data impersonator
    unset($_SESSION['is_impersonating']);
    unset($_SESSION['impersonator_admin_id']);
    unset($_SESSION['impersonator_admin_nama']);
    unset($_SESSION['impersonator_admin_role']);
    unset($_SESSION['impersonator_admin_logged']);
    unset($_SESSION['impersonator_yayasan_logged']);
    unset($_SESSION['impersonator_yayasan2_logged']);
    unset($_SESSION['impersonator_app_user_id']);
    unset($_SESSION['impersonator_app_username']);
    unset($_SESSION['impersonator_app_user_nama']);
    unset($_SESSION['impersonator_app_user_roles']);
    unset($_SESSION['impersonator_active_role_views']);

    // Bersihkan sesi santri jika ada
    unset($_SESSION['santri_logged_in']);
    unset($_SESSION['santri_id']);
    unset($_SESSION['santri_nama']);

    $redirect_url = 'yayasan2/login-as.php';
    if (isset($_SESSION['impersonator_from']) && !empty($_SESSION['impersonator_from'])) {
        $redirect_url = $_SESSION['impersonator_from'];
    }
    unset($_SESSION['impersonator_from']);

    $separator = (strpos($redirect_url, '?') !== false) ? '&' : '?';
    header("Location: " . $redirect_url . $separator . "sukses=" . urlencode("Berhasil kembali ke sesi Super Admin."));
    exit;
} else {
    header("Location: yayasan2/login-as.php");
    exit;
}
?>
