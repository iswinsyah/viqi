<?php
/**
 * DATABASE PSB / SPMB - AKSES KHUSUS RUANG YAYASAN
 */
require_once 'auth.php';
require_once '../koneksi.php';

// Arahkan ke admin-spmb.php dengan membawa session yayasan
header("Location: ../admin-spmb.php");
exit;
