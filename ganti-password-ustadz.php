<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'ganti_password';
$pesan_sukses = '';
$pesan_error = '';

$ustadz_id = $_SESSION['ustadz_id'];

// Ambil data ustadz yang sedang login
$res_ustadz = $conn->query("SELECT nama, password FROM akun_ustadz WHERE id = $ustadz_id");
$data_ustadz = $res_ustadz->fetch_assoc();
$current_db_password = $data_ustadz['password'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if ($password_lama !== $current_db_password) {
        $pesan_error = "Password lama tidak sesuai!";
    } elseif ($password_baru !== $konfirmasi_password) {
        $pesan_error = "Password baru dan konfirmasi password tidak cocok!";
    } elseif (strlen($password_baru) < 6) {
        $pesan_error = "Password baru minimal 6 karakter!";
    } else {
        // Update password di database
        $sql = "UPDATE akun_ustadz SET password = '$password_baru' WHERE id = $ustadz_id";
        if ($conn->query($sql) === TRUE) {
            $pesan_sukses = "Password berhasil diubah!";
            // Update password di sesi juga agar tidak perlu login ulang
            $data_ustadz['password'] = $password_baru;
        } else {
            $pesan_error = "Gagal mengubah password: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-key text-cyan-600 mr-2"></i>Ganti Password Akun</h1>
                <p class="text-gray-500 mt-1">Ubah password Anda secara berkala untuk menjaga keamanan akun.</p>
            </div>
            
            <?php if($pesan_sukses): ?><div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div><?php endif; ?>
            <?php if($pesan_error): ?><div class="bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div><?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 max-w-lg">
                <form action="" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label>
                        <input type="password" name="password_lama" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Masukkan password lama Anda">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                        <input type="password" name="password_baru" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Masukkan password baru">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label>
                        <input type="password" name="konfirmasi_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Ulangi password baru">
                    </div>
                    <div class="text-right">
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Password Baru</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>