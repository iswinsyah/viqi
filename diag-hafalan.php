<?php
require_once 'koneksi.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SUMMARY TEST ===\n";

// 1. Santri with old query vs direct match
$res = $conn->query("SELECT id, nama_lengkap FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC LIMIT 15");
while ($s = $res->fetch_assoc()) {
    $sid = (int)$s['id'];
    $sname = $s['nama_lengkap'];
    $sname_esc = $conn->real_escape_string($sname);

    // OLD QUERY from orangtua-hafalan.php:
    $q_old = "SELECT COUNT(*) FROM laporan_setoran_hafalan l LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) WHERE (l.santri_id = $sid OR s.id = $sid)";
    $r_old = $conn->query($q_old);
    $c_old = $r_old ? $r_old->fetch_row()[0] : 'ERR';

    // DIRECT ID:
    $r_id = $conn->query("SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE santri_id = $sid");
    $c_id = $r_id ? $r_id->fetch_row()[0] : 0;

    // DIRECT NAME:
    $r_name = $conn->query("SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE nama_santri = '$sname_esc'");
    $c_name = $r_name ? $r_name->fetch_row()[0] : 0;

    // LIKE NAME:
    $first_word = explode(' ', trim($sname))[0];
    $r_like = $conn->query("SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE nama_santri LIKE '%$first_word%'");
    $c_like = $r_like ? $r_like->fetch_row()[0] : 0;

    echo "ID: $sid | $sname | OldQ: $c_old | ByID: $c_id | ByExactName: $c_name | ByFirstWord($first_word): $c_like\n";
}

echo "\n=== TOP 10 NAMA IN LAPORAN_SETORAN_HAFALAN ===\n";
$r_dist = $conn->query("SELECT santri_id, nama_santri, COUNT(*) as cnt FROM laporan_setoran_hafalan GROUP BY santri_id, nama_santri ORDER BY cnt DESC LIMIT 10");
while ($r = $r_dist->fetch_assoc()) {
    echo "santri_id: [{$r['santri_id']}] | nama_santri: [{$r['nama_santri']}] | count: {$r['cnt']}\n";
}
