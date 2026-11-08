<?php
require_once __DIR__ . '/koneksi.php';

header('Content-Type: application/json');

$found = [];
$res_tables = $conn->query("SHOW TABLES");
if ($res_tables) {
    while ($t = $res_tables->fetch_array()) {
        $table = $t[0];
        $res_cols = $conn->query("SHOW COLUMNS FROM `$table`");
        $cols = [];
        if ($res_cols) {
            while ($c = $res_cols->fetch_assoc()) {
                $type = strtolower($c['Type']);
                if (strpos($type, 'char') !== false || strpos($type, 'text') !== false) {
                    $cols[] = $c['Field'];
                }
            }
        }
        foreach ($cols as $col) {
            $sql = "SELECT * FROM `$table` WHERE `$col` LIKE '%Hendra%' OR `$col` LIKE '%Gunawan%' LIMIT 5";
            $res_search = @$conn->query($sql);
            if ($res_search && $res_search->num_rows > 0) {
                while ($r = $res_search->fetch_assoc()) {
                    $found[] = [
                        'table' => $table,
                        'column' => $col,
                        'data' => $r
                    ];
                }
            }
        }
    }
}

echo json_encode($found, JSON_PRETTY_PRINT);
