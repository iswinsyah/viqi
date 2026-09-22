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

    // Master Key: Akses Super Admin untuk melihat Ruang Santri
    if ($username === 'winsyah' && $password === 'Khilafet@1924') {
        $_SESSION['santri_logged_in'] = true;
        $_SESSION['santri_id'] = 9999; // ID Khusus Super Admin
        $_SESSION['santri_nama'] = 'Super Admin (Santri View)';
        header("Location: ruang-santri.php");
        exit;
    }

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
<body class="bg-gradient-to-br from-[#0b8478]/10 via-slate-50 to-teal-50 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Radiant Aura Background -->
    <div class="absolute -top-32 -right-32 w-96 h-96 bg-teal-400/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="bg-white/95 backdrop-blur-md p-6 sm:p-10 rounded-3xl shadow-2xl w-full max-w-md border border-teal-100 relative z-10">
        <div class="text-center mb-8">
            <a href="index.html" class="inline-block group mb-3">
                <div class="w-20 h-20 rounded-full bg-white flex items-center justify-center p-2 mx-auto shadow-xl border-2 border-teal-100 group-hover:scale-105 transition-transform duration-300">
                    <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran Indonesia" class="w-16 h-16 object-contain">
                </div>
            </a>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Ruang Santri</h1>
            <p class="text-xs text-slate-500 mt-1">Portal Informasi & Pembelajaran • Villa Quran Indonesia</p>
        </div>

        <?php if($error): ?>
        <div class="bg-rose-50 text-rose-700 border border-rose-200 text-xs px-4 py-3 rounded-2xl mb-5 flex items-center gap-2.5 shadow-xs">
            <i class="fas fa-exclamation-circle text-rose-500 text-base flex-shrink-0"></i>
            <span class="font-semibold"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-black uppercase text-slate-600 mb-1.5 tracking-wider">Username / NIS</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-user text-xs"></i>
                    </div>
                    <input type="text" name="username" required autofocus class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#0b8478] focus:bg-white focus:outline-none transition" placeholder="Masukkan username santri">
                </div>
            </div>
            <div>
                <label class="block text-xs font-black uppercase text-slate-600 mb-1.5 tracking-wider">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-lock text-xs"></i>
                    </div>
                    <input type="password" name="password" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#0b8478] focus:bg-white focus:outline-none transition" placeholder="••••••••">
                </div>
            </div>
            <button type="submit" class="w-full py-3.5 px-4 rounded-2xl bg-gradient-to-r from-[#0b8478] to-[#086a60] hover:from-[#097368] hover:to-[#06534b] text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-teal-900/20 hover:shadow-teal-900/30 transform hover:-translate-y-0.5 active:translate-y-0 transition duration-150">
                Masuk Ruang Santri
            </button>
        </form>
        <div class="mt-6 text-center">
            <a href="index.html" class="text-xs font-bold text-slate-500 hover:text-[#0b8478] transition flex items-center justify-center gap-1.5">
                <i class="fas fa-arrow-left text-[10px]"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>
</html>