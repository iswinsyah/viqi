<?php
require_once 'koneksi.php';
$r = $conn->query('SELECT * FROM menu_permissions');
while($row = $r->fetch_assoc()) {
    echo $row['menu_key'] . " => " . $row['allowed_roles'] . "\n";
}
unlink(__FILE__); // Self-destruct after running to prevent security exposure
