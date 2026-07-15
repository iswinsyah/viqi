<?php
require_once 'koneksi.php';
echo "--- USER WITH ID 1 ---\n";
$r = $conn->query('SELECT * FROM akun_ustadz WHERE id = 1');
if ($r && $r->num_rows > 0) {
    print_r($r->fetch_assoc());
} else {
    echo "ID 1 not found in akun_ustadz\n";
}
echo "\n--- USERS WITH ROLE LIKE MAHAD ---\n";
$r = $conn->query("SELECT * FROM akun_ustadz WHERE role LIKE '%mahad%'");
while($row = $r->fetch_assoc()) {
    print_r($row);
}
unlink(__FILE__);
