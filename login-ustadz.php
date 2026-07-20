<?php
// Paksa sinkronisasi file ke server Hostinger
session_start();
require_once 'koneksi.php';

// Tambahkan kolom 'role' ke tabel akun_ustadz jika belum ada (seharusnya sudah ada dari yayasan2/asatidz.php)
@$conn->query("ALTER TABLE akun_ustadz ADD COLUMN role VARCHAR(255) DEFAULT 'ustadz' AFTER password");

// Cek session ustadz
// Jika sudah login, langsung arahkan ke dashboard ustadz
if (isset($_SESSION['ustadz_logged_in']) && $_SESSION['ustadz_logged_in'] === true) {
    header("Location: admin-ustadz.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Master Key: Akses Super Admin (Ketua Yayasan / Pemilik Aplikasi)
    if ($username === 'winsyah' && $password === 'Khilafet@1924') {
        $_SESSION['ustadz_logged_in'] = true;
        $_SESSION['ustadz_id'] = 9999; // ID Khusus Super Admin
        $_SESSION['ustadz_nama'] = 'Super Admin (Bos)';
        $_SESSION['ustadz_role'] = 'super_admin'; // Beri peran super_admin
        header("Location: admin-ustadz.php");
        exit;
    }

    // Ambil juga kolom 'role' dan 'status_pegawai' saat login
    $res = $conn->query("SELECT id, nama, password, role, status_pegawai FROM akun_ustadz WHERE username = '$username'");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if (($user['status_pegawai'] ?? '') === 'Nonaktif') {
            $error = '⛔ Akun Anda DIBLOKIR / DINONAKTIFKAN oleh sistem karena akumulasi pelanggaran presensi (Alpa). Harap hubungi Super Admin untuk pembukaan blokir.';
        } elseif ($password === $user['password']) {
            $_SESSION['ustadz_logged_in'] = true;
            $_SESSION['ustadz_id'] = $user['id'];
            $_SESSION['ustadz_nama'] = $user['nama'];
            $_SESSION['ustadz_role'] = $user['role']; // Simpan peran user ke sesi
            header("Location: admin-ustadz.php");
            exit;
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username belum terdaftar!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Asatidz | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen relative overflow-hidden">
    <!-- Background Accents -->
    <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-cyan-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
    <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>

    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl w-full max-w-md border border-gray-100 relative z-10">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-cyan-100 text-cyan-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-chalkboard-teacher"></i></div>
            <h1 class="text-2xl font-bold text-gray-900">Ruang Asatidz</h1>
            <p class="text-sm text-gray-500 mt-1">Sistem Informasi Akademik & Kinerja</p>
        </div>

        <?php if($error): ?><div class="bg-red-50 text-red-600 border border-red-200 text-sm px-4 py-3 rounded-lg mb-6 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?></div><?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-user text-gray-400"></i></div><input type="text" name="username" required class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-cyan-500 focus:border-cyan-500" placeholder="Masukkan username"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-400"></i></div><input type="password" id="password" name="password" required class="w-full pl-10 pr-12 py-2.5 border border-gray-300 rounded-lg focus:ring-cyan-500 focus:border-cyan-500" placeholder="••••••••"><button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-cyan-600 focus:outline-none"><i class="fas fa-eye" id="eyeIcon"></i></button></div>
            </div>
            <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 rounded-lg transition shadow-md">Masuk Ruang Asatidz</button>
        </form>
        <div class="mt-6 text-center"><a href="index.html" class="text-sm text-gray-500 hover:text-cyan-600"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda</a></div>
    </div>

    <script>const togglePassword = document.getElementById('togglePassword'); const password = document.getElementById('password'); const eyeIcon = document.getElementById('eyeIcon'); togglePassword.addEventListener('click', function () { const type = password.getAttribute('type') === 'password' ? 'text' : 'password'; password.setAttribute('type', type); eyeIcon.classList.toggle('fa-eye'); eyeIcon.classList.toggle('fa-eye-slash'); });</script>
</body>
</html>