<?php
// --- PENGATURAN KEAMANAN & LOKASI ---

// 1. PENGATURAN MULTI-LOKASI
// TODO: Ganti koordinat dan IP Address sesuai data masing-masing gedung.
$locations = [
    'kantor_utama' => [
        'nama' => 'Gedung B (Kantor Villa Quran)',
        'coords' => ['latitude' => -6.595038, 'longitude' => 106.800247],
        'allowed_ips' => ['111.111.111.1', '127.0.0.1', '::1'] // <-- GANTI IP
    ],
    'asrama_rijal' => [
        'nama' => 'Gedung A (Asrama Rijal)',
        'coords' => ['latitude' => -6.597638, 'longitude' => 106.79955],
        'allowed_ips' => ['222.222.222.2', '127.0.0.1', '::1'] // <-- GANTI IP
    ],
    'asrama_nisa' => [
        'nama' => 'Gedung C (Asrama Nisa)',
        'coords' => ['latitude' => -6.598333, 'longitude' => 106.801111],
        'allowed_ips' => ['333.333.333.3', '127.0.0.1', '::1'] // <-- GANTI IP
    ],
];

// 2. KUNCI ENKRIPSI (JANGAN DIUBAH)
define('ENCRYPTION_KEY', 'kunci-rahasia-absensi-viqi-2026');
define('ENCRYPTION_IV', '1234567890123456'); // 16 bytes IV

// --- LOGIKA PEMILIHAN LOKASI ---
$location_key = $_GET['lokasi'] ?? null;

// Jika tidak ada ?lokasi=... di URL, tampilkan halaman pemilihan
if (!$location_key || !isset($locations[$location_key])) {
    include 'template-pemilihan-lokasi-qr.php';
    exit;
}

$current_location = $locations[$location_key];

// --- VALIDASI AKSES BERDASARKAN ALAMAT IP LOKASI TERPILIH ---
$visitor_ip = $_SERVER['REMOTE_ADDR'];
if (!in_array($visitor_ip, $current_location['allowed_ips'])) {
    header("HTTP/1.1 403 Forbidden");
    die("<div style='font-family:sans-serif;text-align:center;padding:50px;'><h1>Akses Ditolak</h1><p>Halaman QR untuk <b>{$current_location['nama']}</b> hanya bisa diakses dari jaringan internet resmi lokasi tersebut.</p><p>IP Anda: $visitor_ip</p></div>");
}

// --- PEMBUATAN DATA QR CODE DINAMIS ---
// Data QR sekarang berisi 'lokasi' agar prosesor tahu harus validasi ke koordinat mana
$data_to_encrypt = json_encode([
    'lokasi' => $location_key,
    'time' => time() // Timestamp UNIX saat ini
]);

$encrypted_data = openssl_encrypt($data_to_encrypt, 'aes-256-cbc', ENCRYPTION_KEY, 0, ENCRYPTION_IV);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Kehadiran - <?= htmlspecialchars($current_location['nama']) ?></title>
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
        <p class="text-2xl font-semibold text-cyan-400 mt-4"><?= htmlspecialchars($current_location['nama']) ?></p>
        <p class="text-lg text-gray-400 mt-1">Silakan scan QR Code di atas untuk mencatat kehadiran.</p>
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