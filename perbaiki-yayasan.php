<?php
// Script Jalur Cepat untuk memperbaiki fitur Ubah PIN Yayasan di Server Hostinger
$dir = __DIR__ . '/yayasan';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

$login_code = <<<'EOD'
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
EOD;
file_put_contents($dir . '/login.php', $login_code);

$index_code = <<<'EOD'
<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'dashboard';

// Proses Ubah PIN
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pin_baru'])) {
    $pin_baru = $_POST['pin_baru'];
    $pin_hash = password_hash($pin_baru, PASSWORD_DEFAULT);
    $conn->query("UPDATE pengaturan_yayasan SET pin_hash='$pin_hash' WHERE id=1");
    $pesan_sukses = "PIN Rahasia Yayasan berhasil diubah! Gunakan PIN ini untuk login berikutnya.";
}

// Hitung total asatidz terdaftar
$q_asatidz = $conn->query("SELECT COUNT(id) AS tot FROM akun_ustadz");
$total_asatidz = $q_asatidz ? ($q_asatidz->fetch_assoc()['tot'] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Yayasan | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-4">
                <a href="../index.html" target="_blank" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium hidden sm:flex items-center"><i class="fas fa-external-link-alt mr-2"></i> Lihat Website</a>
                <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shadow-sm">Y</div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Selamat Datang di Ruang Yayasan</h1>
                <p class="text-gray-500 mt-1">Area eksklusif untuk memantau dan mengatur kebijakan inti pesantren.</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-4 rounded-full bg-indigo-100 text-indigo-600 mr-4"><i class="fas fa-users-cog text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Akun Asatidz</p><p class="text-2xl font-bold text-gray-900"><?= $total_asatidz ?></p></div>
                </div>

                <!-- FORM UBAH PIN YANG HILANG SEBELUMNYA -->
                <div class="bg-gray-800 rounded-xl shadow-lg border border-gray-700 p-6 md:col-span-2 flex flex-col justify-center">
                    <h3 class="font-bold text-white mb-3"><i class="fas fa-key text-amber-500 mr-2"></i>Ubah PIN Keamanan</h3>
                    <form action="" method="POST" class="flex flex-col sm:flex-row gap-3">
                        <input type="password" name="pin_baru" required placeholder="Ketik PIN Baru Anda di sini..." class="flex-1 px-4 py-2 border border-gray-600 bg-gray-700 text-white rounded-lg focus:ring-amber-500 focus:border-amber-500">
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 px-6 py-2 rounded-lg font-bold transition shadow-sm whitespace-nowrap"><i class="fas fa-save mr-2"></i> Simpan PIN Baru</button>
                    </form>
                    <p class="text-xs text-gray-400 mt-3">PIN akan langsung dienkripsi (Hash) secara permanen. Bahkan admin web sekalipun tidak bisa melihat PIN asli Anda.</p>
                </div>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-indigo-800 max-w-3xl">
                <h3 class="font-bold mb-2 flex items-center"><i class="fas fa-shield-alt mr-2 text-indigo-600"></i>Otoritas Akses Ruang Asatidz</h3>
                <p class="text-sm leading-relaxed mb-4">Hanya Asatidz (Guru/Pegawai) yang telah Anda buatkan akunnya di menu <b>Daftar Asatidz</b> yang dapat melakukan Login ke dalam Ruang Asatidz dari halaman depan website.</p>
                <a href="asatidz.php" class="inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition font-medium text-sm">Kelola Akun Asatidz Sekarang <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); });</script>
</body>
</html>
EOD;
file_put_contents($dir . '/index.php', $index_code);

echo "<h2 style='font-family:sans-serif; color:green;'>✅ PERBAIKAN SELESAI! Kotak Ubah PIN sudah berhasil ditanamkan ke server.</h2>";
echo "<p style='font-family:sans-serif;'>Silakan langsung akses: <a href='yayasan/index.php' style='color:blue; font-weight:bold;'>Dashboard Yayasan</a></p>";
?>