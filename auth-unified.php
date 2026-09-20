<?php
/**
 * UNIVERSAL AUTHENTICATION & ROLE ENGINE (SADIGS 4.0)
 * Menangani Session, Deteksi Multi-Role, dan Akses Terpadu
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';

function getCurrentUser() {
    global $conn;
    if (!isset($_SESSION['app_user_id'])) {
        return null;
    }
    $userId = (int)$_SESSION['app_user_id'];
    $res = $conn->query("SELECT * FROM app_users WHERE id = $userId AND status_aktif = 1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        $user['roles_array'] = array_map('trim', explode(',', strtolower($user['roles'])));
        return $user;
    }
    return null;
}

function getUserRoles() {
    $user = getCurrentUser();
    if (!$user) return [];
    return $user['roles_array'] ?? [];
}

function isSuperAdmin() {
    $roles = getUserRoles();
    return in_array('super_admin', $roles) || in_array('ketua_yayasan', $roles) || (isset($_SESSION['app_user_id']) && $_SESSION['app_user_id'] == 1);
}

function hasRole($role) {
    if (isSuperAdmin()) return true;
    $roles = getUserRoles();
    $norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
    foreach ($roles as $r) {
        $r_norm = str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
        if ($r_norm === $norm) return true;
    }
    return false;
}

function getActiveRoleView() {
    return $_SESSION['active_role_view'] ?? 'all';
}

function setActiveRoleView($roleView) {
    $_SESSION['active_role_view'] = $roleView;
}

function requireLogin() {
    if (!isset($_SESSION['app_user_id'])) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Sinkronisasi Sesi Lama (Backward Compatibility)
 * Memastikan semua halaman lama tetap berjalan tanpa modifikasi
 */
function syncLegacySessions($user) {
    $_SESSION['app_user_id'] = (int)$user['id'];
    $_SESSION['app_username'] = $user['username'];
    $_SESSION['app_user_nama'] = $user['nama_lengkap'];
    $_SESSION['app_user_roles'] = $user['roles'];

    $roles = array_map('trim', explode(',', strtolower($user['roles'])));

    // 1. Sesi Pegawai / Ustadz / Admin
    if ($user['user_type'] === 'pegawai' || in_array('super_admin', $roles) || in_array('ustadz', $roles) || in_array('tutor', $roles) || in_array('musyrif', $roles)) {
        $_SESSION['ustadz_logged_in'] = true;
        $_SESSION['ustadz_id'] = $user['ref_id'] ? (int)$user['ref_id'] : (int)$user['id'];
        $_SESSION['ustadz_nama'] = $user['nama_lengkap'];
        $_SESSION['ustadz_role'] = $user['roles'];
        
        if (in_array('super_admin', $roles)) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['yayasan2_logged_in'] = true;
        }
    }

    // 2. Sesi Santri
    if ($user['user_type'] === 'santri' || in_array('santri', $roles)) {
        $_SESSION['santri_logged_in'] = true;
        $_SESSION['santri_id'] = $user['ref_id'] ? (int)$user['ref_id'] : (int)$user['id'];
        $_SESSION['santri_nama'] = $user['nama_lengkap'];
    }

    // 3. Sesi Walisantri
    if ($user['user_type'] === 'walisantri' || in_array('walisantri', $roles)) {
        $_SESSION['orangtua_logged_in'] = true;
        $_SESSION['orangtua_id'] = $user['ref_id'] ? (int)$user['ref_id'] : (int)$user['id'];
        $_SESSION['orangtua_nama'] = $user['nama_lengkap'];
    }
}
