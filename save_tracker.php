<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Panggil file koneksi utama agar kredensial database otomatis sama dengan web
require_once 'koneksi.php';

try {
    // Gunakan variabel $host, $database, $username, dan $password dari koneksi.php
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data) {
        $sql = "INSERT INTO visitor_footprints 
                (device, os_browser, language, source, campaign, traffic_type, location, isp, visit_time, timezone, page_viewed) 
                VALUES 
                (:device, :os_browser, :language, :source, :campaign, :traffic_type, :location, :isp, :time, :timezone, :page_viewed)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':device' => $data['device'] ?? 'Unknown',
            ':os_browser' => $data['os_browser'] ?? 'Unknown',
            ':language' => $data['language'] ?? 'Unknown',
            ':source' => $data['source'] ?? 'Unknown',
            ':campaign' => $data['campaign'] ?? 'Unknown',
            ':traffic_type' => $data['traffic_type'] ?? 'Unknown',
            ':location' => $data['location'] ?? 'Unknown',
            ':isp' => $data['isp'] ?? 'Unknown',
            ':time' => $data['time'] ?? 'Unknown',
            ':timezone' => $data['timezone'] ?? 'Unknown',
            ':page_viewed' => $data['page_viewed'] ?? 'Unknown'
        ]);
        echo json_encode(["status" => "success", "message" => "Jejak terekam"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Data kosong"]);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "DB error"]);
}
?>