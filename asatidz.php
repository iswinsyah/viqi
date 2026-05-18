<?php
require_once 'auth.php';
require_once '../koneksi.php';

// 1. Buat Tabel Otomatis
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