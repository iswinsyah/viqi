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
        user_key VARCHAR(100) NOT NULL,
        santri_id INT NOT NULL,
        locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_santri (user_key, santri_id),
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
 * Mengambil daftar ID santri yang telah dikunci oleh orang tua ini (Bisa 1 atau Lebih jika bersaudara).
 * @return int[] Array ID santri terkunci
 */
function getLockedSantriIds($conn, $orangtua_id) {
    initOrangtuaLockTable($conn);

    // 1. Cek Session
    if (!empty($_SESSION['orangtua_locked_santri_ids']) && is_array($_SESSION['orangtua_locked_santri_ids'])) {
        return array_map('intval', $_SESSION['orangtua_locked_santri_ids']);
    }

    // 2. Cek Database
    $user_key = $conn->real_escape_string(getOrangtuaUserKey($orangtua_id));
    $res = $conn->query("SELECT santri_id FROM orangtua_locked_santri WHERE user_key = '$user_key'");
    if ($res && $res->num_rows > 0) {
        $ids = [];
        while ($r = $res->fetch_assoc()) {
            $ids[] = (int)$r['santri_id'];
        }
        $_SESSION['orangtua_locked_santri_ids'] = $ids;
        if (!headers_sent()) {
            setcookie('_vqi_locked_santri_ids', implode(',', $ids), time() + (86400 * 365 * 10), "/");
        }
        return $ids;
    }

    // 3. Cek Cookie 10 Tahun (Multi-IDs)
    if (!empty($_COOKIE['_vqi_locked_santri_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_COOKIE['_vqi_locked_santri_ids'])));
        if (!empty($ids)) {
            foreach ($ids as $id) {
                $conn->query("INSERT IGNORE INTO orangtua_locked_santri (user_key, santri_id) VALUES ('$user_key', $id)");
            }
            $_SESSION['orangtua_locked_santri_ids'] = $ids;
            return $ids;
        }
    }

    // 4. Fallback legacy single-cookie
    if (!empty($_COOKIE['_vqi_locked_santri_id']) && (int)$_COOKIE['_vqi_locked_santri_id'] > 0) {
        $sid = (int)$_COOKIE['_vqi_locked_santri_id'];
        $conn->query("INSERT IGNORE INTO orangtua_locked_santri (user_key, santri_id) VALUES ('$user_key', $sid)");
        $_SESSION['orangtua_locked_santri_ids'] = [$sid];
        return [$sid];
    }

    return [];
}

/**
 * Mengunci santri yang dipilih oleh orang tua SECARA PERMANEN (Mendukung 1 atau Banyak Anak).
 * @param mysqli $conn
 * @param int $orangtua_id
 * @param int|int[] $santri_ids
 */
function lockSantriForOrangtua($conn, $orangtua_id, $santri_ids) {
    if (!is_array($santri_ids)) {
        $santri_ids = [(int)$santri_ids];
    }
    $santri_ids = array_unique(array_filter(array_map('intval', $santri_ids)));
    if (empty($santri_ids)) return false;

    initOrangtuaLockTable($conn);
    $user_key = $conn->real_escape_string(getOrangtuaUserKey($orangtua_id));

    // Reset dan simpan daftar ananda yang dikunci untuk akun ini
    $conn->query("DELETE FROM orangtua_locked_santri WHERE user_key = '$user_key'");
    foreach ($santri_ids as $sid) {
        $conn->query("INSERT IGNORE INTO orangtua_locked_santri (user_key, santri_id) VALUES ('$user_key', $sid)");
        
        // Simpan relasi resmi jika akun orang tua biasa
        if ((int)$orangtua_id > 0 && (int)$orangtua_id !== 9999) {
            @$conn->query("INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES ($sid, ".(int)$orangtua_id.")");
        }
    }

    // Simpan ke Session & Cookie 10 Tahun
    $_SESSION['orangtua_locked_santri_ids'] = $santri_ids;
    $_SESSION['orangtua_active_santri_id'] = $santri_ids[0];
    if (!headers_sent()) {
        setcookie('_vqi_locked_santri_ids', implode(',', $santri_ids), time() + (86400 * 365 * 10), "/");
        setcookie('orangtua_active_santri_id', $santri_ids[0], time() + (86400 * 365 * 10), "/");
    }

    return true;
}

/**
 * Membuka / Reset kunci ananda
 */
function unlockSantriForOrangtua($conn, $orangtua_id) {
    initOrangtuaLockTable($conn);
    $user_key = $conn->real_escape_string(getOrangtuaUserKey($orangtua_id));
    $conn->query("DELETE FROM orangtua_locked_santri WHERE user_key = '$user_key'");
    unset($_SESSION['orangtua_locked_santri_ids']);
    unset($_SESSION['orangtua_locked_santri_id']);
    unset($_SESSION['orangtua_active_santri_id']);
    if (!headers_sent()) {
        setcookie('_vqi_locked_santri_ids', '', time() - 3600, "/");
        setcookie('_vqi_locked_santri_id', '', time() - 3600, "/");
        setcookie('orangtua_active_santri_id', '', time() - 3600, "/");
    }
    return true;
}

// 1. Handler Form Kunci Multi-Ananda (POST dari Modal / Checkbox)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'lock_multiple_ananda') {
    $p_ids = array_filter(array_map('intval', $_POST['santri_ids'] ?? []));
    if (!empty($p_ids)) {
        lockSantriForOrangtua($conn, $_SESSION['orangtua_id'] ?? 0, $p_ids);
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }
}

// 2. Handler Reset Kunci khusus Super Admin
if (isset($_GET['action']) && $_GET['action'] === 'unlock_ananda') {
    $is_admin = (
        ((int)($_SESSION['orangtua_id'] ?? 0) === 9999) ||
        (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi']))
    );
    if ($is_admin) {
        unlockSantriForOrangtua($conn, $_SESSION['orangtua_id'] ?? 9999);
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Mengambil daftar santri yang SAH milik orang tua yang sedang login.
 * JIKA SUDAH DIKUNCI: HANYA mengembalikan anak-anak yang dikunci tersebut (1 atau Banyak Bersaudara).
 * JIKA BELUM DIKUNCI: Mengembalikan daftar pilihan santri untuk pemilihan awal.
 */
function getOrangtuaSantriList($conn, $orangtua_id) {
    // 1. Cek apakah orang tua sudah pernah memilih dan terkunci permanen
    $locked_ids = getLockedSantriIds($conn, $orangtua_id);
    if (!empty($locked_ids)) {
        $in = implode(',', $locked_ids);
        $res = $conn->query("SELECT id, nama_lengkap, kelas_sekarang, kamar_asrama, foto_santri FROM buku_induk_santri WHERE id IN ($in) ORDER BY nama_lengkap ASC");
        if ($res && $res->num_rows > 0) {
            $list = [];
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
            return $list; // Kembalikan HANYA anak-anak mereka saja selamanya!
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
 * - Jika memilih dari URL (?santri_id=...):
 *   - Jika belum dikunci -> langsung kunci anak ini dan reload.
 *   - Jika sudah dikunci -> pastikan santri tersebut ada di daftar ananda sahnya (tamper-proof).
 * - Mengembalikan data lengkap ananda yang sedang aktif.
 */
function getOrangtuaActiveSantri($conn, $orangtua_id, $santri_list) {
    if (empty($santri_list)) {
        return null;
    }

    $valid_ids = array_map('intval', array_column($santri_list, 'id'));
    $locked_ids = getLockedSantriIds($conn, $orangtua_id);

    // Ambil parameter jika ada yang memilih dari URL
    $req_id = 0;
    if (isset($_GET['santri_id']) && (int)$_GET['santri_id'] > 0) {
        $req_id = (int)$_GET['santri_id'];
    } elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $req_id = (int)$_GET['id'];
    }

    // Jika BELUM dikunci dan orang tua memilih 1 anak via GET: Langsung kunci 1 anak ini
    if (empty($locked_ids) && $req_id > 0) {
        lockSantriForOrangtua($conn, $orangtua_id, [$req_id]);
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }

    // Tentukan ananda yang sedang aktif di antara ananda sah
    $active_id = 0;
    if ($req_id > 0 && in_array($req_id, $valid_ids)) {
        $active_id = $req_id;
    } elseif (isset($_SESSION['orangtua_active_santri_id']) && in_array((int)$_SESSION['orangtua_active_santri_id'], $valid_ids)) {
        $active_id = (int)$_SESSION['orangtua_active_santri_id'];
    } elseif (isset($_COOKIE['orangtua_active_santri_id']) && in_array((int)$_COOKIE['orangtua_active_santri_id'], $valid_ids)) {
        $active_id = (int)$_COOKIE['orangtua_active_santri_id'];
    } else {
        $active_id = $valid_ids[0];
    }

    // Perbarui session & cookie active child
    $_SESSION['orangtua_active_santri_id'] = $active_id;
    if (!headers_sent()) {
        setcookie('orangtua_active_santri_id', $active_id, time() + (86400 * 30), "/");
    }

    // Kembalikan objek data santri aktif
    foreach ($santri_list as $s) {
        if ((int)$s['id'] === $active_id) {
            return $s;
        }
    }

    return $santri_list[0];
}