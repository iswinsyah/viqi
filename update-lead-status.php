<?php
require_once 'koneksi.php';

// Menerima data dari Fetch API (Drag & Drop Kanban)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_POST['status'])) {
    $id = (int)$_POST['id'];
    $status = $conn->real_escape_string($_POST['status']);

    // Update status / level terbaru ke database
    $sql = "UPDATE leads SET status = '$status' WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo "Sukses";
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "Akses tidak valid";
}
?>