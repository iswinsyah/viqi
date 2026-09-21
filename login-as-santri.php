<?php
session_start();
require_once 'koneksi.php';

// Pastikan yang mengakses adalah Super Admin atau user yang berhak
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$app_roles = isset($_SESSION['app_user_roles']) ? explode(',', $_SESSION['app_user_roles']) : [];
$all_roles = array_unique(array_merge($user_roles, $app_roles));

$is_super_admin = (
    (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) ||
    (isset($_SESSION['yayasan_logged_in']) && $_SESSION['yayasan_logged_in'] === true) ||
    (isset($_SESSION['yayasan2_logged_in']) && $_SESSION['yayasan2_logged_in'] === true) ||
    in_array('super_admin', $all_roles) ||
    in_array('ketua_yayasan', $all_roles) ||
    (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true)
);

if (!$is_super_admin) {
    die("Akses ditolak: Hanya Super Admin yang diizinkan menggunakan fitur Login As.");
}

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($target_id > 0) {
    $res = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $target_id LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $santri = $res->fetch_assoc();

        // Simpan sesi awal admin jika belum berada dalam mode impersonasi
        if (!isset($_SESSION['is_impersonating']) || $_SESSION['is_impersonating'] !== true) {
            $_SESSION['impersonator_admin_id'] = $_SESSION['ustadz_id'] ?? 9999;
            $_SESSION['impersonator_admin_nama'] = $_SESSION['ustadz_nama'] ?? 'Super Admin';
            $_SESSION['impersonator_admin_role'] = $_SESSION['ustadz_role'] ?? 'super_admin';
            $_SESSION['impersonator_app_user_id'] = $_SESSION['app_user_id'] ?? 1;
            $_SESSION['impersonator_app_username'] = $_SESSION['app_username'] ?? 'winsyah';
            $_SESSION['impersonator_app_user_nama'] = $_SESSION['app_user_nama'] ?? 'Super Admin';
            $_SESSION['impersonator_app_user_roles'] = $_SESSION['app_user_roles'] ?? 'super_admin';
            $_SESSION['impersonator_active_role_views'] = $_SESSION['active_role_views'] ?? ['all'];
            $_SESSION['impersonator_admin_logged'] = $_SESSION['admin_logged_in'] ?? true;
            $_SESSION['impersonator_yayasan_logged'] = $_SESSION['yayasan_logged_in'] ?? true;
            $_SESSION['impersonator_yayasan2_logged'] = $_SESSION['yayasan2_logged_in'] ?? true;
            $_SESSION['impersonator_from'] = $_SERVER['HTTP_REFERER'] ?? 'admin-santri.php';
        }

        // Aktifkan flag impersonasi
        $_SESSION['is_impersonating'] = true;

        // Nonaktifkan sementara flag sesi Yayasan/Super Admin/Pegawai
        unset($_SESSION['yayasan_logged_in']);
        unset($_SESSION['yayasan2_logged_in']);
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['ustadz_logged_in']);
        unset($_SESSION['ustadz_id']);
        unset($_SESSION['ustadz_nama']);
        unset($_SESSION['ustadz_role']);

        // Switch sesi ke target santri (Legacy)
        $_SESSION['santri_logged_in'] = true;
        $_SESSION['santri_id'] = (int)$santri['id'];
        $_SESSION['santri_nama'] = $santri['nama_lengkap'];

        // Switch Unified Authentication Sessions
        $_SESSION['app_user_id'] = (int)$santri['id'];
        $_SESSION['app_username'] = !empty($santri['nis']) ? $santri['nis'] : ('santri_' . $santri['id']);
        $_SESSION['app_user_nama'] = $santri['nama_lengkap'];
        $_SESSION['app_user_roles'] = 'santri';
        $_SESSION['active_role_views'] = ['santri'];

        header("Location: ruang-santri.php");
        exit;
    } else {
        die("Santri tidak ditemukan.");
    }
} else {
    header("Location: admin-santri.php");
    exit;
}
?>
