<?php
/**
 * Redirect Rekap KBM Lama ke Modul Rekap Jam Mengajar & Jam Kosong yang Telah Diperbarui
 */
require_once 'auth-ustadz.php';

$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: admin-kontrol-jam-kosong.php" . $queryString, true, 301);
exit;
