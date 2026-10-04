<?php
require 'koneksi.php';

$sql_create = "CREATE TABLE IF NOT EXISTS santri_orangtua_link (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    orangtua_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_santri_ortu (santri_id, orangtua_id),
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE,
    FOREIGN KEY (orangtua_id) REFERENCES akun_orangtua(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($conn->query($sql_create)) {
    echo "Tabel santri_orangtua_link berhasil dibuat atau sudah ada.\n";
} else {
    die("Gagal membuat tabel: " . $conn->error);
}

// Migrasi data
$res = $conn->query("SELECT id, id_orangtua FROM buku_induk_santri WHERE id_orangtua IS NOT NULL");
$count = 0;
while ($row = $res->fetch_assoc()) {
    $s_id = (int)$row['id'];
    $o_id = (int)$row['id_orangtua'];
    
    $sql_insert = "INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES ($s_id, $o_id)";
    if ($conn->query($sql_insert) && $conn->affected_rows > 0) {
        $count++;
    }
}
echo "Berhasil memigrasi $count data relasi santri-orangtua.\n";
