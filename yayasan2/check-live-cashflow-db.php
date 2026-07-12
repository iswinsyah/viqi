<?php
require_once '../koneksi.php';

function show_live_table($conn, $table) {
    echo "\n=== Columns in $table ===\n";
    $res = $conn->query("SHOW COLUMNS FROM $table");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "  Table missing or error: " . $conn->error . "\n";
    }
}

echo "--- LIST OF ALL LIVE TABLES ---\n";
$res_t = $conn->query("SHOW TABLES");
if ($res_t) {
    while ($row = $res_t->fetch_row()) {
        echo $row[0] . "\n";
    }
}

show_live_table($conn, 'buku_induk_santri');
show_live_table($conn, 'keuangan_akun');
show_live_table($conn, 'keuangan_jurnal_detail');
show_live_table($conn, 'keuangan_jurnal');
show_live_table($conn, 'pembayaran_spp');
show_live_table($conn, 'pendaftar_spmb');
show_live_table($conn, 'pengaturan_spp'); // Let's check if there is an SPP settings table!

echo "\n--- ROW COUNTS ---\n";
$tables = ['buku_induk_santri', 'keuangan_akun', 'keuangan_jurnal_detail', 'keuangan_jurnal', 'pembayaran_spp', 'pendaftar_spmb'];
foreach ($tables as $t) {
    $res = $conn->query("SELECT COUNT(*) FROM $t");
    if ($res) {
        echo "  $t: " . $res->fetch_row()[0] . " rows\n";
    } else {
        echo "  $t: missing\n";
    }
}
?>
