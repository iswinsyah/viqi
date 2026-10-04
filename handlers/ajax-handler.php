<?php
require_once 'auth.php';
require_once 'koneksi.php';

$user_id = $_SESSION['user_id'] ?? 1; // Asumsi ada user_id di session

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] == 'log_ai_activity') {
        $fitur = $conn->real_escape_string($_POST['fitur']);
        $detail = $conn->real_escape_string($_POST['detail']);

        $sql = "INSERT INTO log_aktivitas_ai (user_id, fitur, detail_prompt) VALUES ($user_id, '$fitur', '$detail')";
        $conn->query($sql);
        
        echo "OK";
        exit;
    }
}
?>