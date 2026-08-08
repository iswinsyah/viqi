<?php
session_start();
require_once 'koneksi.php';

// Pastikan yang mengakses adalah Super Admin atau user yang berhak
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_super_admin = (
    (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) ||
    (isset($_SESSION['yayasan_logged_in']) && $_SESSION['yayasan_logged_in'] === true) ||
    (isset($_SESSION['yayasan2_logged_in']) && $_SESSION['yayasan2_logged_in'] === true) ||
    in_array('super_admin', $user_roles) ||
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
            $_SESSION['impersonator_yayasan_logged'] = $_SESSION['yayasan_logged_in'] ?? true;
            $_SESSION['impersonator_yayasan2_logged'] = $_SESSION['yayasan2_logged_in'] ?? true;
            $_SESSION['impersonator_from'] = $_SERVER['HTTP_REFERER'] ?? 'admin-santri.php';
        }

        // Aktifkan flag impersonasi
        $_SESSION['is_impersonating'] = true;

        // Switch sesi ke target santri
        $_SESSION['santri_logged_in'] = true;
        $_SESSION['santri_id'] = $santri['id'];
        $_SESSION['santri_nama'] = $santri['nama_lengkap'];

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
