<?php
require_once 'koneksi.php';
echo "<h3>5 Artikel Terbaru di Database:</h3>";
$res = $conn->query("SELECT id, judul, created_at FROM artikel ORDER BY id DESC LIMIT 5");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Judul: " . htmlspecialchars($row['judul']) . " | Dibuat: " . $row['created_at'] . "<br>";
    }
} else {
    echo "Belum ada artikel di database.";
}
?>
