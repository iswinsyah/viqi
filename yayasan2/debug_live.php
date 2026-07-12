<?php
require_once '../koneksi.php';

echo "--- REMOTE ALL USERS COUNT ---\n";
$res_count = $conn->query("SELECT COUNT(*) as total FROM akun_ustadz");
echo "Total Users: " . ($res_count ? $res_count->fetch_assoc()['total'] : 0) . "\n";

echo "--- DETAILED USERS LIST ---\n";
$res_user = $conn->query("SELECT id, nama, username, role FROM akun_ustadz ORDER BY id ASC");
if ($res_user) {
    while ($row = $res_user->fetch_assoc()) {
        echo "ID:{$row['id']} | N:{$row['nama']} | U:{$row['username']} | R:{$row['role']}\n";
    }
} else {
    echo "Gagal: " . $conn->error;
}
?>
