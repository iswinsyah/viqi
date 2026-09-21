<?php
session_start();
require_once 'koneksi.php';

// Pastikan yang mengakses adalah Super Admin atau Pimpinan Yayasan
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
    $res = $conn->query("SELECT * FROM akun_ustadz WHERE id = $target_id LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();

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
            $_SESSION['impersonator_from'] = $_SERVER['HTTP_REFERER'] ?? 'yayasan2/asatidz.php';
        }

        // Aktifkan flag impersonasi
        $_SESSION['is_impersonating'] = true;

        // Cari atau petakan akun ke tabel app_users (Universal Auth Engine)
        $target_username = $user['username'] ?? '';
        $target_app_user = null;
        if (!empty($target_username)) {
            $uname_esc = $conn->real_escape_string($target_username);
            $res_app = $conn->query("SELECT * FROM app_users WHERE username = '$uname_esc' LIMIT 1");
            if ($res_app && $res_app->num_rows > 0) {
                $target_app_user = $res_app->fetch_assoc();
            }
        }
        if (!$target_app_user) {
            $res_ref = $conn->query("SELECT * FROM app_users WHERE ref_id = {$user['id']} AND user_type = 'pegawai' LIMIT 1");
            if ($res_ref && $res_ref->num_rows > 0) {
                $target_app_user = $res_ref->fetch_assoc();
            }
        }

        if ($target_app_user) {
            $app_id = (int)$target_app_user['id'];
            $app_uname = $target_app_user['username'];
            $app_nama = $target_app_user['nama_lengkap'];
            $app_roles = $target_app_user['roles'];
        } else {
            $app_id = (int)$user['id'];
            $app_uname = !empty($user['username']) ? $user['username'] : 'user_' . $user['id'];
            $app_nama = $user['nama'];
            $app_roles = $user['role'];
        }

        $roles_arr = array_filter(array_map('trim', explode(',', strtolower($app_roles))));
        if (empty($roles_arr)) {
            $roles_arr = array_filter(array_map('trim', explode(',', strtolower($user['role']))));
        }
        if (empty($roles_arr)) {
            $roles_arr = ['ustadz'];
        }

        // Cek apakah target user memiliki hak akses kepengurusan yayasan
        $is_target_yayasan = !empty(array_intersect(['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan'], $roles_arr));
        if (!$is_target_yayasan) {
            unset($_SESSION['yayasan_logged_in']);
            unset($_SESSION['yayasan2_logged_in']);
            unset($_SESSION['admin_logged_in']);
        } else {
            $_SESSION['yayasan_logged_in'] = true;
            $_SESSION['yayasan2_logged_in'] = true;
            $_SESSION['admin_logged_in'] = true;
        }

        // Set Unified Authentication Sessions
        $_SESSION['app_user_id'] = $app_id;
        $_SESSION['app_username'] = $app_uname;
        $_SESSION['app_user_nama'] = $app_nama;
        $_SESSION['app_user_roles'] = implode(',', $roles_arr);
        $_SESSION['active_role_views'] = array_values($roles_arr); // Set tampilan menu langsung sesuai role target!

        // Set Legacy Sessions untuk kompatibilitas script lama
        $_SESSION['ustadz_logged_in'] = true;
        $_SESSION['ustadz_id'] = (int)$user['id'];
        $_SESSION['ustadz_nama'] = $user['nama'];
        $_SESSION['ustadz_role'] = $user['role'];
        $_SESSION['username'] = $app_uname;
        $_SESSION['role'] = $user['role'];

        header("Location: dashboard.php?sukses=" . urlencode("Berhasil Login Sebagai: " . $user['nama'] . " (" . implode(', ', $roles_arr) . ")"));
        exit;
    } else {
        die("User tidak ditemukan.");
    }
} else {
    header("Location: yayasan2/login-as.php");
    exit;
}
?>
