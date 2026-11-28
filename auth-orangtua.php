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
    $existing_locked = getLockedSantriIds($conn, $_SESSION['orangtua_id'] ?? 0);
    $is_admin = (
        ((int)($_SESSION['orangtua_id'] ?? 0) === 9999) ||
        (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi']))
    );
    // Jika BUKAN Super Admin dan SUDAH pernah mengunci ananda: Tolak perubahan mandiri! (Harus lapor ke Super Admin)
    if (!empty($existing_locked) && !$is_admin) {
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }

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
        unset($_SESSION['orangtua_admin_preview_id']);
        unset($_SESSION['orangtua_active_santri_id']);
        $redirect = strtok($_SERVER["REQUEST_URI"], '?');
        header("Location: " . $redirect);
        exit;
    }
}

/**
 * Mengambil daftar santri yang SAH milik orang tua yang sedang login.
 * JIKA SUDAH DIKUNCI: HANYA mengembalikan anak-anak yang dikunci tersebut (1 atau Banyak Bersaudara).
 * JIKA SUPER ADMIN: Mengembalikan HANYA 1 santri yang sedang ditinjau (bisa diganti via switcher), BUKAN seluruh sekolah!
 * JIKA BELUM DIKUNCI: Mengembalikan ananda yang terhubung resmi, atau [] jika belum ada.
 */
function getOrangtuaSantriList($conn, $orangtua_id) {
    if (!isset($conn) || !($conn instanceof mysqli)) {
        return [];
    }

    $is_super_admin = ((int)$orangtua_id === 9999 || (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi'])));

    // Request ganti santri lewat parameter URL (?santri_id=... atau ?id=...)
    $req_id = 0;
    if (isset($_GET['santri_id']) && (int)$_GET['santri_id'] > 0) {
        $req_id = (int)$_GET['santri_id'];
    } elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $req_id = (int)$_GET['id'];
    }

    // ==========================================
    // KHUSUS SUPER ADMIN / MODE PRATINJAU WALI
    // ==========================================
    if ($is_super_admin) {
        // Jika Super Admin memilih santri lewat URL, simpan ke session
        if ($req_id > 0) {
            $_SESSION['orangtua_admin_preview_id'] = $req_id;
            $_SESSION['orangtua_active_santri_id'] = $req_id;
            $_SESSION['orangtua_locked_santri_ids'] = [$req_id];
        }

        $admin_selected_id = $req_id > 0 ? $req_id : (int)($_SESSION['orangtua_admin_preview_id'] ?? ($_SESSION['orangtua_active_santri_id'] ?? 0));

        // Jika sudah ada santri yang dipilih oleh Super Admin
        if ($admin_selected_id > 0) {
            $stmt = $conn->prepare("SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, kamar_asrama, foto_santri, status_santri FROM buku_induk_santri WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $admin_selected_id);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    return [$res->fetch_assoc()];
                }
            }
        }

        // Cek jika ada yang terkunci di database untuk Super Admin
        $locked_ids = getLockedSantriIds($conn, $orangtua_id);
        if (!empty($locked_ids)) {
            $in = implode(',', array_map('intval', $locked_ids));
            $res = $conn->query("SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, kamar_asrama, foto_santri, status_santri FROM buku_induk_santri WHERE id IN ($in) ORDER BY nama_lengkap ASC");
            if ($res && $res->num_rows > 0) {
                $list = [];
                while ($r = $res->fetch_assoc()) $list[] = $r;
                return $list;
            }
        }

        // Jika belum ada santri terpilih sama sekali:
        // Ambil 1 SANTRI AKTIF PERTAMA sebagai pratinjau tunggal (JANGAN dump 50 santri!)
        $res = $conn->query("SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, kamar_asrama, foto_santri, status_santri FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $first = $res->fetch_assoc();
            $_SESSION['orangtua_admin_preview_id'] = (int)$first['id'];
            $_SESSION['orangtua_active_santri_id'] = (int)$first['id'];
            return [$first];
        }
        return [];
    }

    // ==========================================
    // UNTUK WALI SANTRI RESMI
    // ==========================================
    // 1. Cek apakah orang tua sudah pernah memilih dan terkunci permanen
    $locked_ids = getLockedSantriIds($conn, $orangtua_id);
    if (!empty($locked_ids)) {
        $in = implode(',', array_map('intval', $locked_ids));
        $res = $conn->query("SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, kamar_asrama, foto_santri, status_santri FROM buku_induk_santri WHERE id IN ($in) ORDER BY nama_lengkap ASC");
        if ($res && $res->num_rows > 0) {
            $list = [];
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
            return $list; // Kembalikan HANYA anak-anak mereka saja selamanya!
        }
    }

    // 2. Jika belum dikunci, cari relasi resmi di database:
    // a. santri_orangtua_link atau buku_induk_santri.id_orangtua
    $list = [];
    $stmt = $conn->prepare("
        SELECT DISTINCT s.id, s.nama_lengkap, s.nis, s.nisn, s.kelas_sekarang, s.kamar_asrama, s.foto_santri, s.status_santri 
        FROM buku_induk_santri s 
        LEFT JOIN santri_orangtua_link sol ON s.id = sol.santri_id 
        WHERE (sol.orangtua_id = ? OR s.id_orangtua = ?) 
          AND s.status_santri = 'Aktif' 
        ORDER BY s.nama_lengkap ASC
    ");
    if ($stmt) {
        $oid = (int)$orangtua_id;
        $stmt->bind_param("ii", $oid, $oid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            while ($r = $res->fetch_assoc()) {
                $list[] = $r;
            }
        }
    }

    // b. Jika belum ketemu, coba Auto-Match cerdas via No. WhatsApp di akun_orangtua
    if (empty($list) && (int)$orangtua_id > 0) {
        $res_ortu = $conn->query("SELECT username, no_whatsapp FROM akun_orangtua WHERE id = " . (int)$orangtua_id . " LIMIT 1");
        if ($res_ortu && $row_ortu = $res_ortu->fetch_assoc()) {
            $wa_raw = !empty($row_ortu['no_whatsapp']) ? $row_ortu['no_whatsapp'] : $row_ortu['username'];
            $clean_digits = preg_replace('/\D/', '', $wa_raw);
            if (strlen($clean_digits) >= 8) {
                // Ambil 8 digit terakhir untuk matching fleksibel (misal 0812... vs 62812...)
                $suffix = substr($clean_digits, -8);
                $stmt_m = $conn->prepare("
                    SELECT id, nama_lengkap, nis, nisn, kelas_sekarang, kamar_asrama, foto_santri, status_santri 
                    FROM buku_induk_santri 
                    WHERE (no_whatsapp_ayah LIKE ? OR no_whatsapp_ibu LIKE ? OR no_whatsapp_wali LIKE ?) 
                      AND status_santri = 'Aktif' 
                    ORDER BY nama_lengkap ASC
                ");
                if ($stmt_m) {
                    $like_param = '%' . $suffix . '%';
                    $stmt_m->bind_param("sss", $like_param, $like_param, $like_param);
                    $stmt_m->execute();
                    $res_m = $stmt_m->get_result();
                    if ($res_m && $res_m->num_rows > 0) {
                        while ($r = $res_m->fetch_assoc()) {
                            $list[] = $r;
                            @$conn->query("INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES (".(int)$r['id'].", ".(int)$orangtua_id.")");
                        }
                    }
                }
            }
        }
    }

    // Jika anak terhubung ditemukan (1 atau bersaudara), auto-lock untuk kenyamanan sesi berikutnya
    if (!empty($list)) {
        $child_ids = array_column($list, 'id');
        lockSantriForOrangtua($conn, $orangtua_id, $child_ids);
        return $list;
    }

    // PENTING: JIKA TIDAK ADA ANAK TERHUBUNG, KEMBALIKAN KOSONG []!
    // JANGAN PERNAH MENAMPILKAN SELURUH DATA SANTRI KEPADA ORANG TUA LAIN!
    return [];
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
    $is_super_admin = ((int)$orangtua_id === 9999 || (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi'])));

    // Ambil parameter jika ada yang memilih dari URL
    $req_id = 0;
    if (isset($_GET['santri_id']) && (int)$_GET['santri_id'] > 0) {
        $req_id = (int)$_GET['santri_id'];
    } elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $req_id = (int)$_GET['id'];
    }

    // Khusus Super Admin
    if ($is_super_admin && $req_id > 0) {
        $_SESSION['orangtua_admin_preview_id'] = $req_id;
        $_SESSION['orangtua_active_santri_id'] = $req_id;
        foreach ($santri_list as $s) {
            if ((int)$s['id'] === $req_id) return $s;
        }
    }

    // Jika BELUM dikunci dan orang tua memilih 1 anak via GET: Langsung kunci 1 anak ini
    if (empty($locked_ids) && $req_id > 0 && in_array($req_id, $valid_ids) && !$is_super_admin) {
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