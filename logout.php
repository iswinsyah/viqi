<?php
session_start();

// Hapus semua data sesi keamanan
session_destroy();

// Arahkan kembali ke halaman utama (login)
header("Location: index.php");
exit;