<?php
session_start();
session_destroy(); // Hancurkan semua data sesi

header("Location: login.php");
exit;
?>