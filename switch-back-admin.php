<?php
session_start();

if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
    $admin_id = $_SESSION['impersonator_admin_id'] ?? 9999;
    $admin_nama = $_SESSION['impersonator_admin_nama'] ?? 'Super Admin';

    // Restore sesi Super Admin
    $_SESSION['ustadz_logged_in'] = true;
    $_SESSION['ustadz_id'] = $admin_id;
    $_SESSION['ustadz_nama'] = $admin_nama;
    $_SESSION['ustadz_role'] = 'super_admin';
    $_SESSION['yayasan_logged_in'] = true;

    // Bersihkan flag impersonasi
    unset($_SESSION['is_impersonating']);
    unset($_SESSION['impersonator_admin_id']);
    unset($_SESSION['impersonator_admin_nama']);
    unset($_SESSION['impersonator_yayasan_logged']);

    header("Location: yayasan2/asatidz.php?sukses=" . urlencode("Berhasil kembali ke sesi Super Admin."));
    exit;
} else {
    header("Location: yayasan2/asatidz.php");
    exit;
}
?>
