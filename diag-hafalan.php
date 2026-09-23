<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

$out = [];

// 1. Check Tables
$tables = [];
$res = $conn->query("SHOW TABLES");
if ($res) {
    while ($r = $res->fetch_row()) {
        $tables[] = $r[0];
    }
}
$out['matching_tables'] = array_values(array_filter($tables, function($t) {
    return stripos($t, 'hafalan') !== false || stripos($t, 'mutabaah') !== false || stripos($t, 'santri') !== false || stripos($t, 'orangtua') !== false;
}));

// 2. Counts
$check_counts = ['laporan_setoran_hafalan', 'setoran_hafalan', 'buku_mutabaah', 'buku_induk_santri', 'santri_orangtua_link', 'akun_orangtua'];
foreach ($check_counts as $tbl) {
    if (in_array($tbl, $tables)) {
        $c = $conn->query("SELECT COUNT(*) FROM `$tbl`");
        $out['counts'][$tbl] = $c ? (int)$c->fetch_row()[0] : 'err: ' . $conn->error;
    } else {
        $out['counts'][$tbl] = 'TABLE_NOT_FOUND';
    }
}

// 3. Samples from laporan_setoran_hafalan
if (in_array('laporan_setoran_hafalan', $tables)) {
    $s = $conn->query("SELECT * FROM laporan_setoran_hafalan ORDER BY id DESC LIMIT 5");
    $out['sample_laporan_setoran_hafalan'] = [];
    if ($s) {
        while ($row = $s->fetch_assoc()) $out['sample_laporan_setoran_hafalan'][] = $row;
    }
}

// 4. Samples from setoran_hafalan
if (in_array('setoran_hafalan', $tables)) {
    $s = $conn->query("SELECT * FROM setoran_hafalan ORDER BY id DESC LIMIT 5");
    $out['sample_setoran_hafalan'] = [];
    if ($s) {
        while ($row = $s->fetch_assoc()) $out['sample_setoran_hafalan'][] = $row;
    }
}

// 5. Sample santri from buku_induk_santri
if (in_array('buku_induk_santri', $tables)) {
    $s = $conn->query("SELECT id, nama_lengkap, status_santri, id_orangtua FROM buku_induk_santri WHERE status_santri = 'Aktif' LIMIT 5");
    $out['sample_santri'] = [];
    if ($s) {
        while ($row = $s->fetch_assoc()) $out['sample_santri'][] = $row;
    }
}

echo json_encode($out, JSON_PRETTY_PRINT);
