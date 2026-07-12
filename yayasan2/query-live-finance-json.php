<?php
header('Content-Type: application/json');
require_once '../koneksi.php';

$out = [];

// Akun list
$res = $conn->query("SELECT * FROM keuangan_akun");
$out['akun'] = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out['akun'][] = $row;
    }
}

// Counts
$tables = ['keuangan_jurnal', 'keuangan_jurnal_detail', 'pembayaran_spp', 'pendaftar_spmb', 'buku_induk_santri', 'akun_ustadz'];
$out['counts'] = [];
foreach ($tables as $t) {
    $res = $conn->query("SELECT COUNT(*) FROM $t");
    $out['counts'][$t] = $res ? (int)$res->fetch_row()[0] : -1;
}

// Lembaga
$res = $conn->query("SELECT * FROM keuangan_lembaga");
$out['lembaga'] = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $out['lembaga'][] = $row;
    }
}

echo json_encode($out, JSON_PRETTY_PRINT);
?>
