<?php
require_once '../koneksi.php';

function run_query($conn, $sql, $label) {
    echo "=== $label ===\n";
    $res = $conn->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
    } else {
        echo "Error: " . $conn->error . "\n";
    }
    echo "\n";
}

run_query($conn, "SELECT * FROM keuangan_akun", "KEUANGAN AKUN");
run_query($conn, "SELECT * FROM keuangan_lembaga", "KEUANGAN LEMBAGA");
run_query($conn, "SELECT COUNT(*) as count FROM keuangan_jurnal", "COUNT JURNAL");
run_query($conn, "SELECT COUNT(*) as count FROM keuangan_jurnal_detail", "COUNT JURNAL DETAIL");
run_query($conn, "SELECT COUNT(*) as count FROM pembayaran_spp", "COUNT PEMBAYARAN SPP");
run_query($conn, "SELECT COUNT(*) as count FROM pendaftar_spmb", "COUNT PENDAFTAR SPMB");
run_query($conn, "SELECT * FROM pengaturan_gaji", "PENGATURAN GAJI");
?>
