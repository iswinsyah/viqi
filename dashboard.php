<?php
// dashboard.php
// Router Terpusat Menuju Ruang Modern Sesuai Role (Tampilan Lama Dihapus)
require_once 'auth-unified.php';

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login.php");
    exit;
}

requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();

// Teruskan parameter URL (seperti ?sukses=... atau pesan notifikasi)
$query_string = '';
if (!empty($_SERVER['QUERY_STRING'])) {
    parse_str($_SERVER['QUERY_STRING'], $queryParams);
    unset($queryParams['action']);
    if (!empty($queryParams)) {
        $query_string = '?' . http_build_query($queryParams);
    }
}

// 1. Cek Mode Impersonasi
if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
    if (in_array('santri', $roles) || in_array('santri_rijal', $roles) || in_array('santri_nisa', $roles)) {
        header("Location: ruang-santri.php" . $query_string);
        exit;
    }
    if (in_array('orangtua', $roles) || in_array('wali', $roles) || in_array('walisantri', $roles)) {
        header("Location: dashboard-orangtua.php" . $query_string);
        exit;
    }
    header("Location: admin-absensi-pegawai.php" . $query_string);
    exit;
}

// 2. Ruang Santri
if (in_array('santri', $roles) || in_array('santri_rijal', $roles) || in_array('santri_nisa', $roles) || (isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true)) {
    header("Location: ruang-santri.php" . $query_string);
    exit;
}

// 3. Ruang Orang Tua
if (in_array('orangtua', $roles) || in_array('wali', $roles) || in_array('walisantri', $roles) || (isset($_SESSION['orangtua_logged_in']) && $_SESSION['orangtua_logged_in'] === true)) {
    header("Location: dashboard-orangtua.php" . $query_string);
    exit;
}

// 4. Ruang Yayasan (Super Admin / Pengurus Yayasan)
$is_yayasan_pengurus = $is_admin || !empty(array_intersect(['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan'], $roles));
if ($is_yayasan_pengurus) {
    header("Location: yayasan2/index.php" . $query_string);
    exit;
}

// 5. Ruang Marketing
if (in_array('marketing', $roles) || in_array('tim_marketing', $roles) || in_array('web', $roles)) {
    header("Location: dashboard-marketing.php" . $query_string);
    exit;
}

// 6. Ruang Pegawai (Default untuk Seluruh Asatidz / Staf)
header("Location: admin-absensi-pegawai.php" . $query_string);
exit;
