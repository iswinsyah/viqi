<?php
require_once 'koneksi.php';

header('Content-Type: application/json');

$out = [];

// 1. All santri in buku_induk_santri
$res = $conn->query("SELECT id, nama_lengkap, status_santri, id_orangtua FROM buku_induk_santri ORDER BY nama_lengkap ASC");
$out['santri_list'] = [];
while ($r = $res->fetch_assoc()) {
    $out['santri_list'][] = $r;
}

// 2. Count distinct santri_id and NULL count in laporan_setoran_hafalan
$c_null = $conn->query("SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE santri_id IS NULL OR santri_id = 0");
$out['hafalan_null_santri_id_count'] = $c_null ? (int)$c_null->fetch_row()[0] : 0;

$c_distinct = $conn->query("SELECT santri_id, nama_santri, COUNT(*) as cnt FROM laporan_setoran_hafalan GROUP BY santri_id, nama_santri ORDER BY cnt DESC LIMIT 15");
$out['top_hafalan_santri'] = [];
while ($r = $c_distinct->fetch_assoc()) {
    $out['top_hafalan_santri'][] = $r;
}

// 3. Test query when selecting santri_id = 9 (Afiya Shidqia Dylan)
$test_id = 9;
$q1 = "SELECT l.*, s.nama_lengkap FROM laporan_setoran_hafalan l LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) WHERE (l.santri_id = $test_id OR s.id = $test_id) ORDER BY l.created_at DESC, l.id DESC";
$res_q1 = $conn->query($q1);
$out['test_query_result_count_for_id_9'] = $res_q1 ? $res_q1->num_rows : 'err: ' . $conn->error;

// 4. Test query for other santri IDs
$out['test_counts_by_id'] = [];
foreach ($out['santri_list'] as $s) {
    $sid = (int)$s['id'];
    $sname = $s['nama_lengkap'];
    $q_old = "SELECT COUNT(*) FROM laporan_setoran_hafalan l LEFT JOIN buku_induk_santri s ON (l.santri_id = s.id OR l.nama_santri = s.nama_lengkap) WHERE (l.santri_id = $sid OR s.id = $sid)";
    $r_old = $conn->query($q_old);
    $cnt_old = $r_old ? (int)$r_old->fetch_row()[0] : -1;
    
    // Check how many match by name directly:
    $sname_esc = $conn->real_escape_string($sname);
    $q_name = "SELECT COUNT(*) FROM laporan_setoran_hafalan WHERE santri_id = $sid OR nama_santri = '$sname_esc' OR LOWER(TRIM(nama_santri)) = LOWER(TRIM('$sname_esc'))";
    $r_name = $conn->query($q_name);
    $cnt_name = $r_name ? (int)$r_name->fetch_row()[0] : -1;

    $out['test_counts_by_id'][] = [
        'id' => $sid,
        'nama' => $sname,
        'count_with_old_query' => $cnt_old,
        'count_with_name_match' => $cnt_name
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT);
