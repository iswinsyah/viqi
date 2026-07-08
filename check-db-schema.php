<?php
require_once 'koneksi.php';
$res = $conn->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_row()) {
        $table = $row[0];
        echo "<h4>Table: $table</h4>";
        $col_res = $conn->query("DESCRIBE $table");
        if ($col_res) {
            echo "<ul>";
            while ($col = $col_res->fetch_assoc()) {
                echo "<li>{$col['Field']} - {$col['Type']}</li>";
            }
            echo "</ul>";
        }
    }
} else {
    echo "No tables found.";
}
?>
