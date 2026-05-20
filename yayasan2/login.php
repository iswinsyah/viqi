<?php
// AKTIFKAN ERROR REPORTING SECARA PAKSA UNTUK DEBUGGING
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (isset($_SESSION['yayasan2_logged_in']) && $_SESSION['yayasan2_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === 'lduai' && $password === '1924') {
        $_SESSION['yayasan2_logged_in'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = 'Username atau Password salah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Yayasan 2 | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 flex items-center justify-center min-h-screen relative overflow-hidden">
    <div class="bg-gray-800 p-8 md:p-10 rounded-2xl shadow-2xl w-full max-w-sm border border-gray-700 relative z-10">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-amber-900 text-amber-400 rounded-full flex items-center justify-center text-3xl mx-auto mb-4 border border-amber-700"><i class="fas fa-building"></i></div>
            <h1 class="text-2xl font-bold text-white">Ruang Yayasan 2</h1>
            <p class="text-sm text-gray-400 mt-1">Area Khusus Pengurus Baru</p>
        </div>
        <?php if($error): ?><div class="bg-red-900/50 text-red-400 border border-red-800 text-sm px-4 py-3 rounded-lg mb-6 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?></div><?php endif; ?>
        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Username</label>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-user text-gray-500"></i></div><input type="text" name="username" required autofocus class="w-full pl-10 pr-4 py-2.5 bg-gray-700 border border-gray-600 text-white rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Username..."></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-500"></i></div><input type="password" name="password" required class="w-full pl-10 pr-4 py-2.5 bg-gray-700 border border-gray-600 text-white rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="••••••••"></div>
            </div>
            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-gray-900 font-bold py-3 rounded-lg transition shadow-md">Buka Brankas</button>
        </form>
    </div>
</body>
</html>