<?php
session_start();

// Proteksi halaman: Jika belum login, tendang kembali ke halaman login
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Villa Quran Baron</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- Navbar Admin -->
    <nav class="bg-[#064E3B] text-white p-4 shadow-md">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold tracking-wider">SUPER ADMIN AREA</h1>
            <a href="logout.php" class="bg-red-600 hover:bg-red-700 px-5 py-2 rounded-lg font-bold transition shadow">
                <i class="fa-solid fa-right-from-bracket mr-2"></i>Logout
            </a>
        </div>
    </nav>

    <!-- Konten Dashboard -->
    <div class="max-w-7xl mx-auto p-8 mt-8">
        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-200">
            <h2 class="text-3xl font-bold text-gray-800 mb-4">Selamat Datang, Komandan!</h2>
            <p class="text-gray-600 text-lg mb-6">Anda berhasil masuk dengan username <strong>winsyah</strong>.</p>
            
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-lg">
                Sistem keamanan Dashboard aktif. Area ini siap kita gunakan untuk mengelola data website.
            </div>
        </div>
    </div>

</body>
</html>