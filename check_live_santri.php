<?php
header('Content-Type: application/json');
if (($_GET['key'] ?? '') !== 'Khilafet1924') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once 'koneksi.php';
require_once 'auth-unified.php';

ensureSantriDatabaseSchema();

$tables = [];
$r_tab = $conn->query("SHOW TABLES LIKE '%santri%'");
if ($r_tab) {
    while ($row = $r_tab->fetch_row()) $tables[] = $row[0];
}

$cols = [];
$r_col = $conn->query("SHOW COLUMNS FROM buku_induk_santri");
if ($r_col) {
    while ($c = $r_col->fetch_assoc()) $cols[] = $c['Field'];
}

$santri_list = [];
$r_s = $conn->query("SELECT id, nama_lengkap, nis, nisn, username, password FROM buku_induk_santri LIMIT 20");
if ($r_s) {
    while ($s = $r_s->fetch_assoc()) {
        $santri_list[] = [
            'id' => $s['id'],
            'nama_lengkap' => $s['nama_lengkap'],
            'nis' => $s['nis'] ?? null,
            'nisn' => $s['nisn'] ?? null,
            'username' => $s['username'] ?? null,
            'has_password' => !empty($s['password']),
            'password_val' => $s['password'] ?? null
        ];
    }
}

$app_users_santri = [];
$r_u = $conn->query("SELECT id, username, nama_lengkap, roles, user_type, ref_id FROM app_users WHERE roles LIKE '%santri%' OR user_type = 'santri'");
if ($r_u) {
    while ($u = $r_u->fetch_assoc()) {
        $app_users_santri[] = $u;
    }
}

echo json_encode([
    'db_name' => $database ?? '',
    'tables' => $tables,
    'cols' => $cols,
    'total_buku_induk' => count($santri_list),
    'santri_list' => $santri_list,
    'app_users_santri' => $app_users_santri
], JSON_PRETTY_PRINT);
