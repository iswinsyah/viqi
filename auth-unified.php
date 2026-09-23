<?php
/**
 * UNIVERSAL AUTHENTICATION & ROLE ENGINE (SADIGS 4.0)
 * Menangani Session, Deteksi Multi-Role, dan Akses Terpadu
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';

function getRoleAliases($role) {
    $r = str_replace([" ", "'", "/", "-"], ["_", "", "_", "_"], strtolower(trim($role)));
    $map = [
        'musyrif'          => ['musyrif', 'musyrifah', 'musyirfah', 'musyifah', 'kepala_asrama', 'asrama'],
        'musyrifah'        => ['musyrif', 'musyrifah', 'musyirfah', 'musyifah', 'kepala_asrama', 'asrama'],
        'musyirfah'        => ['musyrif', 'musyrifah', 'musyirfah', 'musyifah', 'kepala_asrama', 'asrama'],
        'kepala_asrama'    => ['musyrif', 'musyrifah', 'musyirfah', 'musyifah', 'kepala_asrama', 'asrama'],
        'ustadz'           => ['ustadz', 'ustadzah', 'guru', 'pengajar', 'asatidz', 'ustadz_ah'],
        'ustadzah'         => ['ustadz', 'ustadzah', 'guru', 'pengajar', 'asatidz', 'ustadz_ah'],
        'ustadz_ah'        => ['ustadz', 'ustadzah', 'guru', 'pengajar', 'asatidz', 'ustadz_ah'],
        'guru'             => ['ustadz', 'ustadzah', 'guru', 'pengajar', 'asatidz'],
        'santri'           => ['santri', 'santri_rijal', 'santri_nisa'],
        'santri_rijal'     => ['santri', 'santri_rijal'],
        'santri_nisa'      => ['santri', 'santri_nisa'],
        'orangtua'         => ['orangtua', 'walisantri', 'wali_santri', 'wali'],
        'walisantri'       => ['orangtua', 'walisantri', 'wali_santri', 'wali'],
        'marketing'        => ['marketing', 'tim_marketing'],
        'tim_marketing'    => ['marketing', 'tim_marketing'],
        'web'              => ['web', 'admin_web'],
        'admin_web'        => ['web', 'admin_web'],
        'ketua_yayasan'    => ['ketua_yayasan', 'super_admin'],
        'super_admin'      => ['super_admin', 'superadmin'],
    ];
    return $map[$r] ?? [$r];
}

function normalizeCanonicalRole($role) {
    $r = str_replace([" ", "'", "/", "-"], ["_", "", "_", "_"], strtolower(trim($role)));
    if (in_array($r, ['musyrif', 'musyrifah', 'musyirfah', 'musyifah', 'kepala_asrama', 'asrama'])) return 'musyrif';
    if (in_array($r, ['ustadz', 'ustadzah', 'guru', 'pengajar', 'asatidz', 'ustadz_ah'])) return 'ustadz';
    if (in_array($r, ['santri', 'santri_rijal', 'santri_nisa'])) return 'santri';
    if (in_array($r, ['orangtua', 'walisantri', 'wali_santri', 'wali'])) return 'orangtua';
    if (in_array($r, ['marketing', 'tim_marketing'])) return 'marketing';
    if (in_array($r, ['web', 'admin_web'])) return 'web';
    if (in_array($r, ['super_admin', 'superadmin'])) return 'super_admin';
    return $r;
}

function ensureSantriDatabaseSchema() {
    global $conn;
    if (!$conn || !($conn instanceof mysqli)) return;
    
    // 1. Cek apakah buku_induk_santri ada
    $check_table = $conn->query("SHOW TABLES LIKE 'buku_induk_santri'");
    if (!$check_table || $check_table->num_rows === 0) return;

    // 2. Cek kolom-kolom penting
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM buku_induk_santri");
    if ($r) {
        while ($c = $r->fetch_assoc()) $cols[] = $c['Field'];
    }

    if (!in_array('nis', $cols)) {
        @$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN nis VARCHAR(50) NULL AFTER nama_lengkap");
    }
    if (!in_array('nisn', $cols)) {
        @$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN nisn VARCHAR(50) NULL AFTER nis");
    }
    if (!in_array('username', $cols)) {
        @$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN username VARCHAR(50) NULL AFTER nisn");
    }
    if (!in_array('password', $cols)) {
        @$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN password VARCHAR(255) NULL AFTER username");
    }

    // 3. Pastikan username & password default jika kosong
    @$conn->query("UPDATE buku_induk_santri SET 
        username = CASE 
            WHEN (nisn IS NOT NULL AND nisn != '') THEN nisn 
            WHEN (nis IS NOT NULL AND nis != '') THEN nis 
            ELSE CONCAT('santri_', id) 
        END 
        WHERE username IS NULL OR username = ''");
        
    @$conn->query("UPDATE buku_induk_santri SET password = '123456' WHERE password IS NULL OR password = ''");
}

function bridgeActiveLegacySessions() {
    global $conn;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Auto-bridge Sesi Santri (misal login dari login-santri.php atau login-as-santri.php)
    if ((!isset($_SESSION['app_user_id']) || empty($_SESSION['app_user_id'])) && isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true) {
        $santri_id = (int)($_SESSION['santri_id'] ?? 0);
        if ($santri_id > 0 && $conn && $conn instanceof mysqli) {
            ensureSantriDatabaseSchema();
            $r_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id LIMIT 1");
            if ($r_s && $r_s->num_rows > 0) {
                $s_row = $r_s->fetch_assoc();
                $s_gender = strtolower(trim($s_row['jenis_kelamin'] ?? ''));
                $s_role = ($s_gender === 'perempuan') ? 'santri_nisa,santri' : 'santri_rijal,santri';
                $s_uname = !empty($s_row['username']) ? $s_row['username'] : (!empty($s_row['nisn']) ? $s_row['nisn'] : (!empty($s_row['nis']) ? $s_row['nis'] : 'santri_' . $santri_id));
                
                $_SESSION['app_user_id'] = $santri_id;
                $_SESSION['app_username'] = $s_uname;
                $_SESSION['app_user_nama'] = $s_row['nama_lengkap'];
                $_SESSION['app_user_roles'] = $s_role;
                if (!isset($_SESSION['active_role_views']) || empty($_SESSION['active_role_views'])) {
                    $_SESSION['active_role_views'] = ['santri'];
                }
            }
        }
    }

    // Auto-bridge Sesi Walisantri
    if ((!isset($_SESSION['app_user_id']) || empty($_SESSION['app_user_id'])) && isset($_SESSION['orangtua_logged_in']) && $_SESSION['orangtua_logged_in'] === true) {
        $orangtua_id = (int)($_SESSION['orangtua_id'] ?? 0);
        if ($orangtua_id > 0 && $conn && $conn instanceof mysqli) {
            $r_o = $conn->query("SELECT * FROM akun_orangtua WHERE id = $orangtua_id LIMIT 1");
            if ($r_o && $r_o->num_rows > 0) {
                $o_row = $r_o->fetch_assoc();
                $o_uname = !empty($o_row['username']) ? $o_row['username'] : 'orangtua_' . $orangtua_id;
                $o_nama = $o_row['nama_orangtua'] ?? $o_row['nama_lengkap'] ?? 'Orang Tua / Wali';
                
                $_SESSION['app_user_id'] = $orangtua_id;
                $_SESSION['app_username'] = $o_uname;
                $_SESSION['app_user_nama'] = $o_nama;
                $_SESSION['app_user_roles'] = 'orangtua,walisantri';
                if (!isset($_SESSION['active_role_views']) || empty($_SESSION['active_role_views'])) {
                    $_SESSION['active_role_views'] = ['orangtua'];
                }
            }
        }
    }
}

// Inisialisasi schema & bridge otomatis saat file auth dimuat
ensureSantriDatabaseSchema();
bridgeActiveLegacySessions();

function getCurrentUser() {
    global $conn;
    bridgeActiveLegacySessions();
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
    
    // 2. Fallback tangguh dari SESSION jika record DB belum sinkron / id 9999 / santri
    if (!empty($sessionUsername)) {
        $session_roles = $_SESSION['app_user_roles'] ?? '';
        if (empty($_SESSION['is_impersonating']) && in_array(strtolower($sessionUsername), ['viqi', 'winsyah'])) {
            $session_roles = 'super_admin,ketua_yayasan,sekretaris_yayasan,bendahara_yayasan,kepala_sekolah,tutor,musyrif,ustadz,walisantri,web,marketing';
        }
        $user_type = 'pegawai';
        if (isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true) {
            $user_type = 'santri';
        } elseif (isset($_SESSION['orangtua_logged_in']) && $_SESSION['orangtua_logged_in'] === true) {
            $user_type = 'walisantri';
        }
        return [
            'id' => $userId > 0 ? $userId : 1,
            'ref_id' => isset($_SESSION['santri_id']) ? (int)$_SESSION['santri_id'] : (isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : ($userId > 0 ? $userId : 1)),
            'username' => $sessionUsername,
            'nama_lengkap' => $_SESSION['app_user_nama'] ?? $sessionUsername,
            'roles' => $session_roles,
            'roles_array' => array_map('trim', explode(',', strtolower($session_roles))),
            'foto_profil' => '',
            'user_type' => $user_type,
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
    if (empty($_SESSION['is_impersonating']) && in_array($uname, ['viqi', 'winsyah'])) {
        return ['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'kepala_sekolah', 'tutor', 'musyrif', 'ustadz', 'walisantri', 'web', 'marketing'];
    }
    return [];
}

function isSuperAdmin() {
    // Santri murni dan Walisantri murni BUKAN Super Admin
    if ((isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true) && empty($_SESSION['is_impersonating'])) {
        $roles = getUserRoles();
        return in_array('super_admin', $roles);
    }
    if ((isset($_SESSION['orangtua_logged_in']) && $_SESSION['orangtua_logged_in'] === true) && empty($_SESSION['is_impersonating'])) {
        $roles = getUserRoles();
        return in_array('super_admin', $roles);
    }

    $roles = getUserRoles();
    if (in_array('super_admin', $roles)) {
        return true;
    }
    // Jika dalam mode impersonasi (Login As), jangan fallback ke privilege admin
    if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
        return false;
    }
    if (in_array('ketua_yayasan', $roles)) {
        return true;
    }
    $uname = strtolower($_SESSION['app_username'] ?? '');
    if (in_array($uname, ['viqi', 'winsyah'])) {
        return true;
    }
    $curr_user = getCurrentUser();
    if ($curr_user && ($curr_user['user_type'] === 'santri' || $curr_user['user_type'] === 'walisantri')) {
        return false;
    }
    if (isset($_SESSION['app_user_id']) && in_array((int)$_SESSION['app_user_id'], [1, 9999])) {
        if (!$curr_user || ($curr_user['user_type'] ?? '') === 'pegawai') {
            return true;
        }
    }
    return false;
}

function hasRole($role) {
    if (isSuperAdmin()) return true;
    $roles = getUserRoles();
    $targetAliases = getRoleAliases($role);
    foreach ($roles as $r) {
        $userAliases = getRoleAliases($r);
        if (!empty(array_intersect($targetAliases, $userAliases))) {
            return true;
        }
    }
    return false;
}

function hasAnyRole($roleList) {
    if (isSuperAdmin()) return true;
    foreach ($roleList as $r) {
        if (hasRole($r)) return true;
    }
    return false;
}

function getActiveRoleView() {
    $views = $_SESSION['active_role_views'] ?? ['all'];
    return is_array($views) ? ($views[0] ?? 'all') : ($views ?? 'all');
}

function setActiveRoleView($roleView) {
    $_SESSION['active_role_views'] = [$roleView];
    $_SESSION['active_role_view'] = $roleView;
}

function getActiveRoleViews() {
    return $_SESSION['active_role_views'] ?? ['all'];
}

function setActiveRoleViews($roleViews) {
    $_SESSION['active_role_views'] = is_array($roleViews) ? $roleViews : [$roleViews];
}

function requireLogin() {
    bridgeActiveLegacySessions();
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
    if (!$user) return;
    $_SESSION['app_user_id'] = (int)($user['id'] ?? 1);
    $_SESSION['app_username'] = $user['username'] ?? '';
    $_SESSION['app_user_nama'] = $user['nama_lengkap'] ?? '';
    $_SESSION['app_user_roles'] = $user['roles'] ?? '';
    if (!isset($_SESSION['active_role_views']) || empty($_SESSION['active_role_views'])) {
        $_SESSION['active_role_views'] = ['all']; // Default 'all' hanya jika belum ada sesi simulasi
    }

    $roles = !empty($user['roles']) ? array_map('trim', explode(',', strtolower($user['roles']))) : [];
    $user_type = $user['user_type'] ?? 'pegawai';
    $ref_id = !empty($user['ref_id']) ? (int)$user['ref_id'] : (int)($user['id'] ?? 1);

    // 1. Sesi Pegawai / Ustadz / Admin
    $is_pegawai = ($user_type === 'pegawai') || hasAnyRole(['super_admin', 'ustadz', 'tutor', 'musyrif', 'kepala_sekolah', 'trainer']);
    if ($is_pegawai) {
        $_SESSION['ustadz_logged_in'] = true;
        $_SESSION['ustadz_id'] = $ref_id;
        $_SESSION['ustadz_nama'] = $user['nama_lengkap'] ?? '';
        $_SESSION['ustadz_role'] = $user['roles'] ?? '';
        
        if (empty($_SESSION['is_impersonating']) && hasAnyRole(['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'web', 'marketing'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['yayasan2_logged_in'] = true;
        } else {
            unset($_SESSION['admin_logged_in']);
            unset($_SESSION['yayasan_logged_in']);
            unset($_SESSION['yayasan2_logged_in']);
        }
    }

    // 2. Sesi Santri
    if ($user_type === 'santri' || hasRole('santri')) {
        $_SESSION['santri_logged_in'] = true;
        $_SESSION['santri_id'] = $ref_id;
        $_SESSION['santri_nama'] = $user['nama_lengkap'] ?? '';
    }

    // 3. Sesi Walisantri
    if ($user_type === 'walisantri' || hasRole('orangtua')) {
        $_SESSION['orangtua_logged_in'] = true;
        $_SESSION['orangtua_id'] = (isSuperAdmin() && empty($_SESSION['is_impersonating'])) ? 9999 : $ref_id;
        $_SESSION['orangtua_nama'] = $user['nama_lengkap'] ?? 'Orang Tua';
    }
}
?>
