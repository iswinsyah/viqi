<?php
require_once '../koneksi.php';

echo "--- REMOTE MENU PERMISSIONS ---\n";
$res = $conn->query("SELECT * FROM menu_permissions");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "Key: {$row['menu_key']} | Roles: {$row['allowed_roles']}\n";
    }
} else {
    echo "Gagal query menu_permissions: " . $conn->error . "\n";
}

echo "\n--- REMOTE USER ANNISA ---\n";
$res_user = $conn->query("SELECT id, nama, username, role FROM akun_ustadz WHERE nama LIKE '%Annisa%'");
if ($res_user) {
    while ($row = $res_user->fetch_assoc()) {
        echo "ID: {$row['id']} | Nama: {$row['nama']} | Username: {$row['username']} | Role: {$row['role']}\n";
    }
} else {
    echo "Gagal query akun_ustadz: " . $conn->error . "\n";
}
?>
