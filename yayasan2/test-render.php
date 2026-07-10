<?php
// Mock session variables to bypass login check
session_start();
$_SESSION['yayasan2_logged_in'] = true;

// Include the target page to render it
include 'master-kalender.php';
?>
