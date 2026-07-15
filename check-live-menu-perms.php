<?php
require_once 'koneksi.php';
echo "--- DESCRIBE AKUN USTADZ ---\n";
$r = $conn->query('DESCRIBE akun_ustadz');
while($row = $r->fetch_assoc()) {
    print_r($row);
}
unlink(__FILE__);
