<?php
require_once '../koneksi.php';

echo "--- REMOTE ANNISA PEEPS ---\n";
$res_user = $conn->query("SELECT id, nama, username, role FROM akun_ustadz WHERE nama LIKE '%Annisa%' OR username LIKE '%Annisa%'");
if ($res_user && $res_user->num_rows > 0) {
    while ($row = $res_user->fetch_assoc()) {
        echo "ID:{$row['id']} | N:{$row['nama']} | U:{$row['username']} | R:{$row['role']}\n";
    }
} else {
    echo "User Annisa tidak ditemukan di akun_ustadz.\n";
}
?>
