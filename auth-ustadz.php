<?php
session_start();
if (!isset($_SESSION['ustadz_logged_in']) || $_SESSION['ustadz_logged_in'] !== true) {
    header("Location: login-ustadz.php");
    exit;
}

// Cek jika akun dinonaktifkan / diblokir saat session berjalan
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] != 9999) {
    require_once 'koneksi.php';
    $chk_id = (int)$_SESSION['ustadz_id'];
    $res_chk = $conn->query("SELECT status_pegawai FROM akun_ustadz WHERE id = $chk_id LIMIT 1");
    if ($res_chk && $res_chk->num_rows > 0) {
        $st_peg = $res_chk->fetch_assoc()['status_pegawai'] ?? '';
        if ($st_peg === 'Nonaktif') {
            session_destroy();
            header("Location: login-ustadz.php");
            exit;
        }
    }
}
?>