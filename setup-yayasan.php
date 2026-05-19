<?php
// Script khusus untuk meng-generate folder Yayasan beserta file-filenya dengan aman.
$dir = __DIR__ . '/yayasan';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

$login = <<<'EOD'
<?php
session_start();

// Cek session login Yayasan
if (isset($_SESSION['yayasan_logged_in']) && $_SESSION['yayasan_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pin = $_POST['pin'] ?? '';

    // PIN khusus Pengurus Yayasan (Bisa diubah sesuai kebijakan)
    if ($pin === 'BismillahYayasan') {
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
file_put_contents($dir . '/login.php', $login);

$auth = <<<'EOD'
<?php
session_start();
// Validasi akses khusus Yayasan
if (!isset($_SESSION['yayasan_logged_in']) || $_SESSION['yayasan_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
?>
EOD;
file_put_contents($dir . '/auth.php', $auth);

$logout = <<<'EOD'
<?php
session_start();
// Hapus session yayasan
unset($_SESSION['yayasan_logged_in']);
header("Location: login.php");
exit;
?>
EOD;
file_put_contents($dir . '/logout.php', $logout);

$sidebar = <<<'EOD'
<!-- SIDEBAR RUANG YAYASAN -->
<div id="sidebar-overlay-yayasan" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>
<aside id="sidebar-yayasan" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl border-r border-gray-800">
    <div class="h-16 flex items-center justify-between px-6 border-b border-gray-800 bg-black/20">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-400">
            <i class="fas fa-building mr-2"></i> YAYASAN
        </h1>
        <button id="close-sidebar-yayasan" class="md:hidden text-gray-400 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 mt-2">Menu Pengurus</p>
            <a href="index.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard') ? 'text-indigo-400' : 'text-gray-500 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'asatidz') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all mt-1">
                <i class="fas fa-users-cog w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'asatidz') ? 'text-indigo-400' : 'text-gray-500 group-hover:text-white' ?>"></i> Daftar Asatidz
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-gray-800">
        <a href="logout.php" class="flex items-center justify-center text-sm font-bold text-white transition-all bg-rose-600 hover:bg-rose-700 px-4 py-2.5 rounded-lg shadow-sm"><i class="fas fa-sign-out-alt mr-2"></i> Kunci Ruangan</a>
    </div>
</aside>
EOD;
file_put_contents($dir . '/sidebar.php', $sidebar);

$index = <<<'EOD'
<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'dashboard';

// Hitung total akun asatidz terdaftar
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-4 rounded-full bg-indigo-100 text-indigo-600 mr-4"><i class="fas fa-users-cog text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Akun Asatidz</p><p class="text-2xl font-bold text-gray-900"><?= $total_asatidz ?></p></div>
                </div>
            </div>

            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-indigo-800 max-w-3xl">
                <h3 class="font-bold mb-2 flex items-center"><i class="fas fa-shield-alt mr-2 text-indigo-600"></i>Otoritas Akses Ruang Asatidz</h3>
                <p class="text-sm leading-relaxed mb-4">Hanya Asatidz (Guru/Pegawai) yang telah Anda buatkan akunnya di menu <b>Daftar Asatidz</b> yang dapat melakukan Login ke dalam Ruang Asatidz dari halaman depan website.</p>
                <a href="asatidz.php" class="inline-flex items-center bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700 transition font-medium text-sm">Kelola Akun Asatidz Sekarang <i class="fas fa-arrow-right ml-2"></i></a>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); }); document.getElementById('close-sidebar-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); });</script>
</body>
</html>
EOD;
file_put_contents($dir . '/index.php', $index);

$asatidz = <<<'EOD'
<?php
require_once 'auth.php';
require_once '../koneksi.php';

// 1. Buat Tabel Database Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS akun_ustadz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM akun_ustadz WHERE id = $id");
    header("Location: asatidz.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = $conn->real_escape_string($_POST['nama']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);

    // Cek apakah username sudah dipakai oleh orang lain
    $cek = $conn->query("SELECT id FROM akun_ustadz WHERE username = '$username' AND id != $id");
    if ($cek && $cek->num_rows > 0) {
        $pesan_error = "Username '$username' sudah terpakai! Silakan gunakan username lain.";
    } else {
        if ($id > 0) {
            $sql = "UPDATE akun_ustadz SET nama='$nama', username='$username', password='$password' WHERE id=$id";
            $pesan_sukses = "Akun ustadz berhasil diupdate!";
        } else {
            $sql = "INSERT INTO akun_ustadz (nama, username, password) VALUES ('$nama', '$username', '$password')";
            $pesan_sukses = "Akun ustadz baru berhasil ditambahkan!";
        }
        $conn->query($sql);
    }
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM akun_ustadz WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'asatidz';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Asatidz | Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center"><button id="open-sidebar-yayasan" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2></div>
            <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shadow-sm">Y</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-users-cog text-indigo-600 mr-2"></i>Hak Akses Ruang Asatidz</h1><p class="text-sm text-gray-500 mt-1">Tentukan siapa saja Ustadz/Ustadzah yang diizinkan masuk ke Ruang Asatidz.</p></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100"><h2 class="font-bold text-indigo-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-user-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Akun Asatidz' : 'Buat Akun Baru' ?></h2></div>
                <form action="asatidz.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label><input type="text" name="nama" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Contoh: Ust. Ahmad"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Username Login</label><input type="text" name="username" value="<?= $edit_mode ? htmlspecialchars($data_edit['username']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Contoh: ahmad123"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><input type="text" name="password" value="<?= $edit_mode ? htmlspecialchars($data_edit['password']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Kata sandi..."></div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="asatidz.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Akun' : 'Simpan Akun' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Akun Terdaftar</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white"><tr><th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Ustadz</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Username</th><th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Password</th><th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM akun_ustadz ORDER BY nama ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50"><td class="px-6 py-4 font-bold text-gray-900"><?= htmlspecialchars($row['nama']) ?></td><td class="px-6 py-4"><span class="px-2 py-1 bg-gray-100 rounded text-sm font-mono text-gray-700"><?= htmlspecialchars($row['username']) ?></span></td><td class="px-6 py-4"><span class="text-sm text-gray-500 italic"><?= htmlspecialchars($row['password']) ?></span></td><td class="px-6 py-4 text-center"><a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-edit"></i></a><a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus akses login untuk ustadz ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a></td></tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500 italic'>Belum ada akun Asatidz yang didaftarkan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan').addEventListener('click', () => { document.getElementById('sidebar-yayasan').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan').classList.toggle('hidden'); });</script>
</body>
</html>
EOD;
file_put_contents($dir . '/asatidz.php', $asatidz);

echo "<h2 style='font-family:sans-serif; color:green;'>✅ Mantap! Folder 'yayasan' beserta seluruh isinya telah berhasil di-generate secara otomatis!</h2>";
echo "<p style='font-family:sans-serif;'>Silakan hapus file <b>setup-yayasan.php</b> ini dan akses web Anda melalui: <a href='yayasan/login.php'>/yayasan</a></p>";
?>