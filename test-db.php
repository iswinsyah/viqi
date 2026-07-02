<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$start = microtime(true);
require_once 'koneksi.php';
$duration = microtime(true) - $start;
echo "Connected in " . round($duration, 4) . " seconds!<br>";
if ($conn) {
    echo "Connection object exists!<br>";
    $res = $conn->query("SELECT DATABASE()");
    if ($res) {
        $row = $res->fetch_row();
        echo "Database Name: " . htmlspecialchars($row[0]) . "<br>";
    } else {
        echo "Query failed: " . htmlspecialchars($conn->error) . "<br>";
    }
} else {
    echo "No connection object!<br>";
}
?>
