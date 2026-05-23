<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$active_menu = 'ganti_password_santri';
$pesan_sukses = '';
$pesan_error = '';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];

// Ambil data santri yang sedang login
$res_santri = $conn->query("SELECT nama_lengkap, password FROM buku_induk_santri WHERE id = $santri_id");
$data_santri = $res_santri->fetch_assoc();
$current_db_password = $data_santri['password'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $pesan_error = "Semua kolom wajib diisi!";
    } elseif ($password_lama !== $current_db_password) {
        $pesan_error = "Password lama tidak sesuai!";
    } elseif ($password_baru !== $konfirmasi_password) {
        $pesan_error = "Password baru dan konfirmasi password tidak cocok!";
    } elseif (strlen($password_baru) < 6) {
        $pesan_error = "Password baru minimal harus 6 karakter!";
    } else {
        // Update password di database
        $stmt = $conn->prepare("UPDATE buku_induk_santri SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $password_baru, $santri_id);
        if ($stmt->execute()) {
            $pesan_sukses = "Password berhasil diubah! Silakan gunakan password baru saat login berikutnya.";
            // Update password di variabel lokal agar pengecekan berikutnya pakai password baru
            $current_db_password = $password_baru;
        } else {
            $pesan_error = "Gagal mengubah password: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password | <?= htmlspecialchars($santri_nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-santri.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-santri" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-key text-indigo-600 mr-2"></i>Ganti Password Akun</h1>
                <p class="text-gray-500 mt-1">Ubah password default Anda untuk mengamankan akun.</p>
            </div>
            
            <?php if($pesan_sukses): ?><div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div><?php endif; ?>
            <?php if($pesan_error): ?><div class="bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div><?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 max-w-lg mx-auto">
                <form action="santri-ganti-password.php" method="POST" class="space-y-5">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Password Lama</label><input type="password" name="password_lama" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Masukkan password lama Anda"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label><input type="password" name="password_baru" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Minimal 6 karakter"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Password Baru</label><input type="password" name="konfirmasi_password" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Ulangi password baru"></div>
                    <div class="text-right pt-2"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Password Baru</button></div>
                </form>
            </div>
        </main>
    </div>
    <script>document.addEventListener('DOMContentLoaded', function() { const sidebar = document.getElementById('sidebar-santri'); const openBtn = document.getElementById('open-sidebar-santri'); const overlay = document.getElementById('sidebar-overlay-santri'); if(openBtn) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }); if(overlay) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }); });</script>
</body>
</html>