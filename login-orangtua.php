<?php
session_start();
require_once 'koneksi.php';

if (isset($_SESSION['orangtua_logged_in']) && $_SESSION['orangtua_logged_in'] === true) {
    header("Location: dashboard-orangtua.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password wajib diisi!';
    } else {
        // Master Key: Akses Super Admin (Bos)
        if ($username === 'winsyah' && $password === 'Khilafet@1924') {
            $_SESSION['orangtua_logged_in'] = true;
            $_SESSION['orangtua_id'] = 9999; // ID Khusus Super Admin
            $_SESSION['orangtua_nama'] = 'Super Admin (Parent View)';
            header("Location: dashboard-orangtua.php");
            exit;
        }

        $stmt = $conn->prepare("SELECT id, nama_orangtua, password FROM akun_orangtua WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($password === $user['password']) {
                $_SESSION['orangtua_logged_in'] = true;
                $_SESSION['orangtua_id'] = $user['id'];
                $_SESSION['orangtua_nama'] = $user['nama_orangtua'];
                header("Location: dashboard-orangtua.php");
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Orang Tua | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen relative overflow-hidden">
    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl w-full max-w-md border border-gray-100 relative z-10">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-user-shield"></i></div>
            <h1 class="text-2xl font-bold text-gray-900">Ruang Orang Tua</h1>
            <p class="text-sm text-gray-500 mt-1">Portal Pemantauan Ananda</p>
        </div>
        <?php if($error): ?><div class="bg-red-50 text-red-600 border border-red-200 text-sm px-4 py-3 rounded-lg mb-6 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?></div><?php endif; ?>
        <form action="" method="POST" class="space-y-6">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Username</label><input type="text" name="username" required autofocus class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" placeholder="Username wali santri"></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><input type="password" name="password" required class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" placeholder="••••••••"></div>
            <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg transition shadow-md">Masuk Portal</button>
        </form>
    </div>
</body>
</html>