<?php
// --- PENGATURAN KEAMANAN & LOKASI ---

// 1. DAFTAR ALAMAT IP YANG DIIZINKAN
// TODO: Ganti dengan Alamat IP Publik dari jaringan internet sekolah Anda.
// Anda bisa menambahkan lebih dari satu jika setiap gedung punya IP berbeda.
$allowed_ips = [
    '127.0.0.1',       // IP untuk development di localhost
    '::1',             // IP untuk development di localhost (IPv6)
    '103.152.45.XX',   // <-- GANTI DENGAN IP PUBLIK SEKOLAH ANDA
    '114.122.8.XX'     // <-- GANTI DENGAN IP PUBLIK GEDUNG LAIN (JIKA BEDA)
];

// 2. KOORDINAT TITIK ABSENSI
// TODO: Ganti dengan koordinat GPS dari lokasi komputer penampil QR.
// Anda bisa mendapatkan koordinat ini dari Google Maps.
$location_coords = [
    'latitude' => -6.595038, // Contoh: Koordinat Bogor
    'longitude' => 106.800247
];

// 3. KUNCI ENKRIPSI (JANGAN DIUBAH)
define('ENCRYPTION_KEY', 'kunci-rahasia-absensi-viqi-2026');
define('ENCRYPTION_IV', '1234567890123456'); // 16 bytes IV

// --- VALIDASI AKSES BERDASARKAN ALAMAT IP ---
$visitor_ip = $_SERVER['REMOTE_ADDR'];
if (!in_array($visitor_ip, $allowed_ips)) {
    header("HTTP/1.1 403 Forbidden");
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'><h1>Akses Ditolak</h1><p>Halaman ini hanya bisa diakses dari jaringan internet resmi sekolah.</p><p>IP Anda: $visitor_ip</p></div>");
}

// --- PEMBUATAN DATA QR CODE DINAMIS ---
$data_to_encrypt = json_encode([
    'lat' => $location_coords['latitude'],
    'lon' => $location_coords['longitude'],
    'time' => time() // Timestamp UNIX saat ini
]);

$encrypted_data = openssl_encrypt($data_to_encrypt, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Kehadiran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Library untuk generate QR Code di browser -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <!-- Refresh halaman setiap 60 detik untuk QR baru -->
    <meta http-equiv="refresh" content="60">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen text-white font-sans">
    <div class="text-center">
        <div class="bg-white p-6 rounded-2xl shadow-2xl inline-block">
            <div id="qrcode"></div>
        </div>
        <h1 id="clock" class="text-6xl font-bold mt-6"></h1>
        <p class="text-xl text-gray-400 mt-2">Silakan scan QR Code di atas untuk mencatat kehadiran.</p>
        <p class="text-sm text-gray-500 mt-4"><i class="fas fa-sync-alt fa-spin mr-2"></i> QR Code ini akan diperbarui secara otomatis.</p>
    </div>

    <script>
        // Generate QR Code dari data terenkripsi
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= base64_encode($encrypted_data) ?>", // Encode base64 agar aman di URL/JS
            width: 300,
            height: 300,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        // Jam Digital
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>