<?php
// --- PENGATURAN KEAMANAN & LOKASI ---

// 1. PENGATURAN MULTI-LOKASI
// TODO: Ganti koordinat dan IP Address sesuai data masing-masing gedung.
$locations = [
    'kantor_utama' => [
        'nama' => 'Gedung B (Kantor Villa Quran)',
        'coords' => ['latitude' => -7.9485768, 'longitude' => 112.5823352],
    ],
    'asrama_rijal' => [
        'nama' => 'Gedung A (Asrama Rijal)',
        'coords' => ['latitude' => -7.9480284, 'longitude' => 112.5796667],
    ],
    'asrama_nisa' => [
        'nama' => 'Gedung C (Asrama Nisa)',
        'coords' => ['latitude' => -7.9403686, 'longitude' => 112.5754103],
    ],
];

// 2. KUNCI ENKRIPSI (JANGAN DIUBAH)
define('ENCRYPTION_KEY', 'kunci-rahasia-absensi-viqi-2026');
define('ENCRYPTION_IV', '1234567890123456'); // 16 bytes IV

// --- LOGIKA PEMILIHAN LOKASI ---
$location_key = $_GET['lokasi'] ?? null;
$jenis_absen = $_GET['jenis'] ?? 'Harian'; // Default Harian

// Jika tidak ada ?lokasi=... di URL, tampilkan halaman pemilihan
if (!$location_key || !isset($locations[$location_key])) {
    if (file_exists('template-pemilihan-lokasi-qr.php')) {
        include 'template-pemilihan-lokasi-qr.php';
        exit;
    } else {
        die("<div style='font-family:sans-serif;text-align:center;padding:50px;'><h1>Pemilihan Lokasi & Jenis Absen</h1><p>Gunakan URL seperti: <code>?lokasi=kantor_utama&jenis=Harian</code> atau <code>?lokasi=kantor_utama&jenis=Rapat</code></p></div>");
    }
}

$current_location = $locations[$location_key];

// --- PEMBUATAN DATA QR CODE DINAMIS ---
// Data QR Statis (tanpa timestamp) agar bisa dicetak dan ditempel
$data_to_encrypt = json_encode([
    'lokasi' => $location_key,
    'jenis' => $jenis_absen
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
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen text-gray-900 font-sans">
    <div class="text-center">
        <div class="bg-white p-6 rounded-2xl shadow-2xl inline-block">
            <div id="qrcode"></div>
        </div>
        <h1 class="text-3xl font-bold mt-6"><?= htmlspecialchars($current_location['nama']) ?></h1>
        <p class="text-xl font-semibold text-cyan-600 mt-2">QR Absensi Statis - <?= htmlspecialchars($jenis_absen) ?></p>
        <p class="text-md text-gray-600 mt-2 max-w-md mx-auto">Silakan cetak dan tempel QR Code ini di lokasi. Pegawai dapat melakukan scan untuk mencatat kehadiran Masuk & Pulang.</p>
        
        <button onclick="window.print()" class="no-print mt-6 bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-6 rounded-lg shadow transition"><i class="fas fa-print mr-2"></i> Cetak QR Code</button>
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
    </script>
</body>
</html>