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
    if (!isset($_SESSION['app_user_id']) && !isset($_SESSION['app_username'])) {
        return null;
    }
    $userId = (int)($_SESSION['app_user_id'] ?? 0);
    $sessionUsername = $_SESSION['app_username'] ?? '';
    
    // 1. Coba query dari database
    if ($conn && $conn instanceof mysqli) {
        $username_esc = $conn->real_escape_string($sessionUsername);
        $where = "id = $userId";
        if (!empty($sessionUsername)) {
            $where .= " OR (username = '$username_esc' AND username != '')";
        }
        $res = $conn->query("SELECT * FROM app_users WHERE ($where) AND status_aktif = 1 LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $user['roles_array'] = array_map('trim', explode(',', strtolower($user['roles'] ?? '')));
            return $user;
        }
    }
    
    // 2. Fallback tangguh dari SESSION jika record DB belum sinkron / id 9999
    if (!empty($sessionUsername)) {
        $session_roles = $_SESSION['app_user_roles'] ?? '';
        if (in_array(strtolower($sessionUsername), ['viqi', 'winsyah'])) {
            $session_roles = 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan,kepala_sekolah,tutor,musyrif,ustadz,walisantri,web,marketing';
        }
        return [
            'id' => $userId > 0 ? $userId : 1,
            'username' => $sessionUsername,
            'nama_lengkap' => $_SESSION['app_user_nama'] ?? $sessionUsername,
            'roles' => $session_roles,
            'roles_array' => array_map('trim', explode(',', strtolower($session_roles))),
            'foto_profil' => '',
            'user_type' => 'pegawai',
            'status_aktif' => 1
        ];
    }
    
    return null;
}

function getUserRoles() {
    $user = getCurrentUser();
    if ($user && !empty($user['roles_array'])) {
        return $user['roles_array'];
    }
    if (!empty($_SESSION['app_user_roles'])) {
        return array_map('trim', explode(',', strtolower($_SESSION['app_user_roles'])));
    }
    $uname = strtolower($_SESSION['app_username'] ?? '');
    if (in_array($uname, ['viqi', 'winsyah'])) {
        return ['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'kepala_sekolah', 'tutor', 'musyrif', 'ustadz', 'walisantri', 'web', 'marketing'];
    }
    return [];
}

function isSuperAdmin() {
    $roles = getUserRoles();
    $uname = strtolower($_SESSION['app_username'] ?? '');
    if (in_array('super_admin', $roles) || in_array('ketua_yayasan', $roles)) {
        return true;
    }
    if (in_array($uname, ['viqi', 'winsyah'])) {
        return true;
    }
    if (isset($_SESSION['app_user_id']) && in_array((int)$_SESSION['app_user_id'], [1, 9999])) {
        return true;
    }
    return false;
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
    if (!isset($_SESSION['app_user_id']) && !isset($_SESSION['app_username'])) {
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
        
        if (in_array('super_admin', $roles) || in_array('ketua_yayasan', $roles) || in_array('sekretaris_yayasan', $roles) || in_array('bendahara_yayasan', $roles) || in_array('web', $roles) || in_array('marketing', $roles)) {
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
