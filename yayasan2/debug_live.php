<?php
require_once '../koneksi.php';

echo "--- REMOTE ALL USERS ---\n";
$res_user = $conn->query("SELECT id, nama, username, role FROM akun_ustadz");
if ($res_user) {
    while ($row = $res_user->fetch_assoc()) {
        echo "ID: {$row['id']} | Nama: {$row['nama']} | Username: {$row['username']} | Role: {$row['role']}\n";
    }
} else {
    echo "Gagal query akun_ustadz: " . $conn->error . "\n";
}
?>
