<?php
session_start();
require_once 'koneksi.php';

// Jika sudah login, langsung arahkan ke dashboard santri
if (isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true) {
    header("Location: ruang-santri.php");
    exit;
}

// Pastikan tabel buku_induk_santri ada sebelum digunakan untuk login
$conn->query("CREATE TABLE IF NOT EXISTS buku_induk_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(150) NOT NULL,
    nis VARCHAR(50) UNIQUE,
    nisn VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE,
    id_orangtua INT NULL,
    password VARCHAR(255),
    nik VARCHAR(50),
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan'),
    alamat_lengkap TEXT,
    foto_santri VARCHAR(255),
    tanggal_masuk DATE,
    asal_sekolah VARCHAR(150),
    status_santri ENUM('Aktif', 'Lulus', 'Pindah', 'Dikeluarkan', 'Mengundurkan Diri') DEFAULT 'Aktif',
    kelas_sekarang VARCHAR(50),
    kamar_asrama VARCHAR(50),
    nama_ayah VARCHAR(150),
    pekerjaan_ayah VARCHAR(100),
    no_whatsapp_ayah VARCHAR(20),
    alamat_ayah TEXT,
    nama_ibu VARCHAR(150),
    pekerjaan_ibu VARCHAR(100),
    no_whatsapp_ibu VARCHAR(20),
    alamat_ibu TEXT,
    nama_wali VARCHAR(150),
    pekerjaan_wali VARCHAR(100),
    alamat_wali TEXT,
    no_whatsapp_wali VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password tidak boleh kosong!';
    } else {
        // Cari santri berdasarkan username di tabel buku_induk_santri
        $stmt = $conn->prepare("SELECT id, nama_lengkap, password FROM buku_induk_santri WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verifikasi password (saat ini masih plain text)
            if ($password === $user['password']) {
                $_SESSION['santri_logged_in'] = true;
                $_SESSION['santri_id'] = $user['id'];
                $_SESSION['santri_nama'] = $user['nama_lengkap'];
                header("Location: ruang-santri.php");
                exit;
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username belum terdaftar atau tidak aktif!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Santri | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen relative overflow-hidden">
    <div class="absolute top-0 right-0 -mt-20 -mr-20 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
    <div class="absolute bottom-0 left-0 -mb-20 -ml-20 w-80 h-80 bg-emerald-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>

    <div class="bg-white p-8 md:p-10 rounded-2xl shadow-xl w-full max-w-md border border-gray-100 relative z-10">
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-user-graduate"></i></div>
            <h1 class="text-2xl font-bold text-gray-900">Ruang Santri</h1>
            <p class="text-sm text-gray-500 mt-1">Portal Informasi & Akademik Santri</p>
        </div>

        <?php if($error): ?><div class="bg-red-50 text-red-600 border border-red-200 text-sm px-4 py-3 rounded-lg mb-6 flex items-center"><i class="fas fa-exclamation-triangle mr-2"></i> <?= $error ?></div><?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Username</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-user text-gray-400"></i></div><input type="text" name="username" required class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Masukkan username"></div></div>
            <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><div class="relative"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="fas fa-lock text-gray-400"></i></div><input type="password" name="password" required class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="••••••••"></div></div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition shadow-md">Masuk Ruang Santri</button>
        </form>
        <div class="mt-6 text-center"><a href="index.html" class="text-sm text-gray-500 hover:text-indigo-600"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda</a></div>
    </div>
</body>
</html>