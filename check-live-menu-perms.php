<?php
require_once 'koneksi.php';
echo "--- MENU PERMISSIONS ---\n";
$r = $conn->query('SELECT * FROM menu_permissions');
while($row = $r->fetch_assoc()) {
    echo $row['menu_key'] . " => " . $row['allowed_roles'] . "\n";
}
echo "\n--- AKUN USTADZ ROLES ---\n";
$r = $conn->query('SELECT id, nama, role FROM akun_ustadz');
while($row = $r->fetch_assoc()) {
    echo $row['id'] . " | " . $row['nama'] . " | " . $row['role'] . "\n";
}
unlink(__FILE__); // Self-destruct
