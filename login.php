<?php
session_start();
require_once '../koneksi.php';

if (isset($_SESSION['yayasan_logged_in']) && $_SESSION['yayasan_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

// 1. Buat tabel dan default PIN hash jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_yayasan (
    id INT PRIMARY KEY DEFAULT 1,
    pin_hash VARCHAR(255)
)");

$cek = $conn->query("SELECT pin_hash FROM pengaturan_yayasan WHERE id = 1");
if ($cek && $cek->num_rows == 0) {
    // Hash bawaan untuk PIN: BismillahYayasan
    $default_hash = password_hash('BismillahYayasan', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO pengaturan_yayasan (id, pin_hash) VALUES (1, '$default_hash')");
    $pin_tersimpan = $default_hash;
} else {
    $pin_tersimpan = $cek->fetch_assoc()['pin_hash'];
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pin = $_POST['pin'] ?? '';

    if (password_verify($pin, $pin_tersimpan)) {
        $_SESSION['yayasan_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'PIN akses tidak valid!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Yayasan | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen relative overflow-hidden">
    <div class="bg-gray-800 p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-sm border border-gray-700 relative z-10">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-900 text-indigo-400 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 border border-indigo-700"><i class="fas fa-building"></i></div>
            <h1 class="text-2xl font-bold text-white">Ruang Yayasan</h1>
            <p class="text-sm text-gray-400 mt-1">Area Khusus Pengurus</p>
        </div>

        <?php if($error): ?><div class="bg-red-900/50 text-red-400 border border-red-800 text-sm px-4 py-3 rounded-lg mb-6 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?></div><?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">PIN Rahasia</label>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-key text-gray-500"></i></div><input type="password" name="pin" required autofocus class="w-full pl-10 pr-4 py-2.5 bg-gray-700 border border-gray-600 text-white rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="••••••••"></div>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition shadow-md">Buka Brankas</button>
        </form>
    </div>
</body>
</html>