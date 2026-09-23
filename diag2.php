<?php
require_once 'koneksi.php';

header('Content-Type: text/plain');

echo "=== 31 SANTRI IN BUKU INDUK ===\n";
$res = $conn->query("SELECT id, nama_lengkap, status_santri, id_orangtua FROM buku_induk_santri ORDER BY nama_lengkap ASC");
$santri_map = [];
while ($r = $res->fetch_assoc()) {
    $sid = (int)$r['id'];
    $sname = $r['nama_lengkap'];
    $santri_map[$sid] = $sname;
    
    // Check old query result count:
    $q_old = "SELECT COUNT(*) FROM laporan_setoran_hafalan l LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) WHERE (l.santri_id = $sid OR s.id = $sid)";
    $r_old = $conn->query($q_old);
    $c_old = $r_old ? $r_old->fetch_row()[0] : 'err: ' . $conn->error;

    // Check simple santri_id match:
    $r_sid = $conn->query("SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE santri_id = $sid");
    $c_sid = $r_sid ? $r_sid->fetch_row()[0] : 0;

    // Check name match:
    $sname_esc = $conn->real_escape_string($sname);
    $r_name = $conn->query("SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE nama_santri = '$sname_esc' OR LOWER(TRIM(nama_santri)) = LOWER(TRIM('$sname_esc'))");
    $c_name = $r_name ? $r_name->fetch_row()[0] : 0;

    echo "ID: $sid | Name: $sname | OldQ: $c_old | ByID: $c_sid | ByName: $c_name\n";
}

echo "\n=== DISTINCT NAMA IN LAPORAN_SETORAN_HAFALAN (TOP 20) ===\n";
$r_dist = $conn->query("SELECT santri_id, nama_santri, COUNT(*) as cnt FROM laporan_setoran_hafalan GROUP BY santri_id, nama_santri ORDER BY cnt DESC LIMIT 20");
while ($r = $r_dist->fetch_assoc()) {
    echo "santri_id: {$r['santri_id']} | nama_santri: '{$r['nama_santri']}' | count: {$r['cnt']}\n";
}
