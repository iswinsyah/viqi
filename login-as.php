<?php
session_start();
require_once 'koneksi.php';

// Pastikan yang mengakses adalah Super Admin atau user yang berhak
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_super_admin = (
    (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) ||
    (isset($_SESSION['yayasan_logged_in']) && $_SESSION['yayasan_logged_in'] === true) ||
    in_array('super_admin', $user_roles) ||
    (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true)
);

if (!$is_super_admin) {
    die("Akses ditolak: Hanya Super Admin yang diizinkan menggunakan fitur Login As.");
}

$target_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($target_id > 0) {
    $res = $conn->query("SELECT * FROM akun_ustadz WHERE id = $target_id LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();

        // Simpan sesi awal admin jika belum berada dalam mode impersonasi
        if (!isset($_SESSION['is_impersonating']) || $_SESSION['is_impersonating'] !== true) {
            $_SESSION['impersonator_admin_id'] = $_SESSION['ustadz_id'] ?? 9999;
            $_SESSION['impersonator_admin_nama'] = $_SESSION['ustadz_nama'] ?? 'Super Admin';
            $_SESSION['impersonator_yayasan_logged'] = $_SESSION['yayasan_logged_in'] ?? true;
        }

        // Aktifkan flag impersonasi
        $_SESSION['is_impersonating'] = true;

        // Switch sesi ke target user
        $_SESSION['ustadz_logged_in'] = true;
        $_SESSION['ustadz_id'] = $user['id'];
        $_SESSION['ustadz_nama'] = $user['nama'];
        $_SESSION['ustadz_role'] = $user['role'];

        header("Location: admin-ustadz.php?sukses=" . urlencode("Berhasil Login Sebagai: " . $user['nama']));
        exit;
    } else {
        die("User tidak ditemukan.");
    }
} else {
    header("Location: yayasan2/asatidz.php");
    exit;
}
?>
