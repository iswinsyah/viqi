<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi orang tua
if (!isset($_SESSION['orangtua_logged_in']) || $_SESSION['orangtua_logged_in'] !== true) {
    // Cek jika Super Admin sedang aktif via unified session
    if (isset($_SESSION['app_user_id']) && (
        (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) ||
        (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi']))
    )) {
        $_SESSION['orangtua_logged_in'] = true;
        $_SESSION['orangtua_id'] = 9999;
        $_SESSION['orangtua_nama'] = $_SESSION['app_user_nama'] ?? 'Super Admin';
    } else {
        header("Location: login-orangtua.php");
        exit;
    }
}

/**
 * Inisialisasi tabel kunci permanen jika belum ada
 */
function initOrangtuaLockTable($conn) {
    static $done = false;
    if ($done) return;
    $conn->query("CREATE TABLE IF NOT EXISTS orangtua_locked_santri (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_key VARCHAR(100) NOT NULL UNIQUE,
        santri_id INT NOT NULL,
        locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_santri (santri_id)
    )");
    $done = true;
}

/**
 * Mendapatkan identitas unik (user_key) untuk penguncian ananda.
 */
function getOrangtuaUserKey($orangtua_id) {
    if (isset($_SESSION['app_username']) && !empty($_SESSION['app_username'])) {
        return 'user_' . strtolower(trim($_SESSION['app_username']));
    }
    if ((int)$orangtua_id > 0) {
        return 'ortu_' . (int)$orangtua_id;
    }
    if (!isset($_COOKIE['_vqi_ortu_device']) || empty($_COOKIE['_vqi_ortu_device'])) {
        $dev = bin2hex(random_bytes(16));
        if (!headers_sent()) {
            setcookie('_vqi_ortu_device', $dev, time() + (86400 * 365 * 10), "/");
        }
        return 'device_' . $dev;
    }
    return 'device_' . trim($_COOKIE['_vqi_ortu_device']);
}

/**
 * Cek apakah user/orangtua ini sudah mengunci Ananda miliknya.
 * Mengembalikan ID santri jika sudah terkunci, atau 0 jika belum.
 */
function getLockedSantriId($conn, $orangtua_id) {
    initOrangtuaLockTable($conn);

    // 1. Cek Session
    if (!empty($_SESSION['orangtua_locked_santri_id']) && (int)$_SESSION['orangtua_locked_santri_id'] > 0) {
        return (int)$_SESSION['orangtua_locked_santri_id'];
    }

    // 2. Cek Database
    $user_key = $conn->real_escape_string(getOrangtuaUserKey($orangtua_id));
    $res = $conn->query("SELECT santri_id FROM orangtua_locked_santri WHERE user_key = '$user_key' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        $sid = (int)$row['santri_id'];
        $_SESSION['orangtua_locked_santri_id'] = $sid;
        $_SESSION['orangtua_active_santri_id'] = $sid;
        if (!headers_sent()) {
            setcookie('_vqi_locked_santri_id', $sid, time() + (86400 * 365 * 10), "/");
            setcookie('orangtua_active_santri_id', $sid, time() + (86400 * 365 * 10), "/");
        }
        return $sid;
    }

    // 3. Cek Cookie 10 Tahun
    if (!empty($_COOKIE['_vqi_locked_santri_id']) && (int)$_COOKIE['_vqi_locked_santri_id'] > 0) {
        $sid = (int)$_COOKIE['_vqi_locked_santri_id'];
        $conn->query("INSERT INTO orangtua_locked_santri (user_key, santri_id) VALUES ('$user_key', $sid) ON DUPLICATE KEY UPDATE santri_id = $sid");
        $_SESSION['orangtua_locked_santri_id'] = $sid;
        $_SESSION['orangtua_active_santri_id'] = $sid;
        return $sid;
    }

    return 0;
}

/**
 * Mengunci santri yang dipilih oleh orang tua SECARA PERMANEN (SELAMANYA).
 */
function lockSantriForOrangtua($conn, $orangtua_id, $santri_id) {
    $santri_id = (int)$santri_id;
    if ($santri_id <= 0) return false;

    initOrangtuaLockTable($conn);
    $user_key = $conn->real_escape_string(getOrangtuaUserKey($orangtua_id));

    // Simpan ke DB secara permanen
    $conn->query("INSERT INTO orangtua_locked_santri (user_key, santri_id) VALUES ('$user_key', $santri_id) ON DUPLICATE KEY UPDATE santri_id = $santri_id");

    // Simpan relasi resmi jika orang tua biasa
    if ((int)$orangtua_id > 0 && (int)$orangtua_id !== 9999) {
        @$conn->query("INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES ($santri_id, ".(int)$orangtua_id.")");
    }

    // Simpan ke Session & Cookie 10 Tahun
    $_SESSION['orangtua_locked_santri_id'] = $santri_id;
    $_SESSION['orangtua_active_santri_id'] = $santri_id;
    if (!headers_sent()) {
        setcookie('_vqi_locked_santri_id', $santri_id, time() + (86400 * 365 * 10), "/");
        setcookie('orangtua_active_santri_id', $santri_id, time() + (86400 * 365 * 10), "/");
    }

    return true;
}

// Handler Reset Kunci khusus Super Admin
if (isset($_GET['action']) && $_GET['action'] === 'unlock_ananda') {
    $is_admin = (
        ((int)($_SESSION['orangtua_id'] ?? 0) === 9999) ||
        (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi']))
    );
    if ($is_admin) {
        initOrangtuaLockTable($conn);
        $user_key = $conn->real_escape_string(getOrangtuaUserKey($_SESSION['orangtua_id'] ?? 9999));
        $conn->query("DELETE FROM orangtua_locked_santri WHERE user_key = '$user_key'");
        unset($_SESSION['orangtua_locked_santri_id']);
        unset($_SESSION['orangtua_active_santri_id']);
        if (!headers_sent()) {
            setcookie('_vqi_locked_santri_id', '', time() - 3600, "/");
            setcookie('orangtua_active_santri_id', '', time() - 3600, "/");
        }
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Mengambil daftar santri yang SAH milik orang tua yang sedang login.
 * JIKA SUDAH DIKUNCI: HANYA mengembalikan 1 santri tersebut selamanya!
 * JIKA BELUM DIKUNCI: Mengembalikan daftar pilihan santri untuk pemilihan 1x.
 */
function getOrangtuaSantriList($conn, $orangtua_id) {
    // 1. Cek apakah orang tua sudah pernah memilih dan terkunci permanen
    $locked_id = getLockedSantriId($conn, $orangtua_id);
    if ($locked_id > 0) {
        $res = $conn->query("SELECT id, nama_lengkap, kelas_sekarang, kamar_asrama, foto_santri FROM buku_induk_santri WHERE id = $locked_id");
        if ($res && $r = $res->fetch_assoc()) {
            return [$r]; // Kembalikan HANYA 1 santri ini selamanya!
        }
    }

    // 2. Jika belum dikunci (Pertama kali memilih)
    $santri = [];
    if ((int)$orangtua_id === 9999) {
        $res = $conn->query("SELECT id, nama_lengkap, kelas_sekarang, kamar_asrama, foto_santri FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
    } else {
        $res = $conn->query("
            SELECT DISTINCT s.id, s.nama_lengkap, s.kelas_sekarang, s.kamar_asrama, s.foto_santri 
            FROM buku_induk_santri s 
            LEFT JOIN santri_orangtua_link sol ON s.id = sol.santri_id 
            WHERE (sol.orangtua_id = ".(int)$orangtua_id." OR s.id_orangtua = ".(int)$orangtua_id.") 
              AND s.status_santri = 'Aktif' 
            ORDER BY s.nama_lengkap ASC
        ");
        if (!$res || $res->num_rows === 0) {
            $res = $conn->query("SELECT id, nama_lengkap, kelas_sekarang, kamar_asrama, foto_santri FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
        }
    }
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $santri[] = $r;
        }
    }
    return $santri;
}

/**
 * Menentukan Ananda Aktif:
 * - Jika ada pilihan baru saat belum dikunci -> langsung kunci sekarang selamanya & reload.
 * - Jika sudah dikunci -> kunci permanen santri tersebut dan abaikan manipulasi URL.
 */
function getOrangtuaActiveSantri($conn, $orangtua_id, $santri_list) {
    if (empty($santri_list)) {
        return null;
    }

    $locked_id = getLockedSantriId($conn, $orangtua_id);

    // Ambil parameter jika ada yang memilih dari URL
    $req_id = 0;
    if (isset($_GET['santri_id']) && (int)$_GET['santri_id'] > 0) {
        $req_id = (int)$_GET['santri_id'];
    } elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $req_id = (int)$_GET['id'];
    }

    // Jika BELUM dikunci dan orang tua memilih salah satu anak: KUNCI SEKARANG SELAMANYA!
    if ($locked_id <= 0 && $req_id > 0) {
        lockSantriForOrangtua($conn, $orangtua_id, $req_id);
        $locked_id = $req_id;
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }

    // Jika SUDAH DIKUNCI: Selalu gunakan santri yang terkunci (Tamper-proof)
    if ($locked_id > 0) {
        foreach ($santri_list as $s) {
            if ((int)$s['id'] === $locked_id) {
                return $s;
            }
        }
        $res = $conn->query("SELECT id, nama_lengkap, kelas_sekarang, kamar_asrama, foto_santri FROM buku_induk_santri WHERE id = $locked_id");
        if ($res && $r = $res->fetch_assoc()) {
            return $r;
        }
    }

    // Fallback jika belum pernah memilih sama sekali
    return $santri_list[0];
}