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
 * Mengambil daftar anak yang SAH milik orang tua yang sedang login.
 * Jika akun Orang Tua asli: HANYA mengambil santri yang terhubung dengan akunnya via santri_orangtua_link atau id_orangtua.
 * Jika Super Admin (id 9999): Mengambil daftar santri aktif untuk keperluan supervisi/bimbingan.
 */
function getOrangtuaSantriList($conn, $orangtua_id) {
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
    }
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $santri[] = $r;
        }
    }
    return $santri;
}

/**
 * Menentukan dan mengunci Ananda Aktif secara PERSISTEN (Session + Cookie 30 Hari).
 * Orang tua cukup memilih 1 kali, dan pilihan tersebut akan otomatis diterapkan di SEMUA menu:
 * Setoran Hafalan, Ibadah Harian, Rapor Diniyah, Raport PKBM, Bimbingan Karir, Pembayaran SPP, Uang Saku, dll.
 * 
 * Privasi terjaga: Jika Orang Tua biasa, sistem memastikan santri yang dipilih wajib ada dalam daftar hak miliknya.
 */
function getOrangtuaActiveSantri($conn, $orangtua_id, $santri_list) {
    if (empty($santri_list)) {
        return null;
    }

    $valid_ids = array_map('intval', array_column($santri_list, 'id'));

    // 1. Cek jika user secara eksplisit memilih santri baru melalui URL parameter (?santri_id= atau ?id=)
    $req_id = 0;
    if (isset($_GET['santri_id']) && (int)$_GET['santri_id'] > 0) {
        $req_id = (int)$_GET['santri_id'];
    } elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
        $req_id = (int)$_GET['id'];
    }

    $selected_id = 0;
    if ($req_id > 0 && in_array($req_id, $valid_ids)) {
        $selected_id = $req_id;
    } elseif (isset($_SESSION['orangtua_active_santri_id']) && in_array((int)$_SESSION['orangtua_active_santri_id'], $valid_ids)) {
        // 2. Ambil dari Session yang sudah tersimpan
        $selected_id = (int)$_SESSION['orangtua_active_santri_id'];
    } elseif (isset($_COOKIE['orangtua_active_santri_id']) && in_array((int)$_COOKIE['orangtua_active_santri_id'], $valid_ids)) {
        // 3. Ambil dari Cookie browser (jika user sempat keluar / logout lalu login kembali)
        $selected_id = (int)$_COOKIE['orangtua_active_santri_id'];
    } else {
        // 4. Default ke anak pertama dari daftar hak miliknya
        $selected_id = $valid_ids[0];
    }

    // Perbarui Session & Cookie agar tersimpan secara permanen untuk semua menu
    $_SESSION['orangtua_active_santri_id'] = $selected_id;
    if (!headers_sent()) {
        setcookie('orangtua_active_santri_id', $selected_id, time() + (86400 * 30), "/");
    }

    // Kembalikan data lengkap santri yang sedang aktif
    foreach ($santri_list as $s) {
        if ((int)$s['id'] === $selected_id) {
            return $s;
        }
    }
    return $santri_list[0];
}