<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// 1. Buat Tabel Otomatis untuk Akun Orang Tua
$conn->query("CREATE TABLE IF NOT EXISTS akun_orangtua (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_orangtua VARCHAR(150) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_whatsapp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM akun_orangtua WHERE id = $id");
    header("Location: admin-akun-orangtua.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_orangtua = $conn->real_escape_string($_POST['nama_orangtua']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']);
    $no_whatsapp = $conn->real_escape_string($_POST['no_whatsapp']);

    $cek = $conn->query("SELECT id FROM akun_orangtua WHERE username = '$username' AND id != $id");
    if ($cek && $cek->num_rows > 0) {
        $pesan_error = "Username '$username' sudah terpakai!";
    } else {
        if ($id > 0) {
            $sql = "UPDATE akun_orangtua SET nama_orangtua='$nama_orangtua', username='$username', password='$password', no_whatsapp='$no_whatsapp' WHERE id=$id";
            $pesan_sukses = "Akun orang tua berhasil diupdate!";
        } else {
            $sql = "INSERT INTO akun_orangtua (nama_orangtua, username, password, no_whatsapp) VALUES ('$nama_orangtua', '$username', '$password', '$no_whatsapp')";
            $pesan_sukses = "Akun orang tua baru berhasil ditambahkan!";
        }
        $conn->query($sql);
    }
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM akun_orangtua WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'akun_orangtua';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Orang Tua | Ruang Staf</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-users text-cyan-600 mr-2"></i>Manajemen Akun Orang Tua</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-user-edit' : 'fa-user-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Akun Orang Tua' : 'Buat Akun Orang Tua Baru' ?></h2></div>
                <form action="admin-akun-orangtua.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Orang Tua</label><input type="text" name="nama_orangtua" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_orangtua']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Bpk. Fulan"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Username Login</label><input type="text" name="username" value="<?= $edit_mode ? htmlspecialchars($data_edit['username']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: fulan_ortu"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Password</label><input type="text" name="password" value="<?= $edit_mode ? htmlspecialchars($data_edit['password']) : '12345678' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Kata sandi..."></div>
                        <div class="md:col-span-3"><label class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp</label><input type="text" name="no_whatsapp" value="<?= $edit_mode ? htmlspecialchars($data_edit['no_whatsapp']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: 6281234567890"></div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-akun-orangtua.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Akun' : 'Simpan Akun' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Akun Orang Tua</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Orang Tua</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Username</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Password</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">No. WhatsApp</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM akun_orangtua ORDER BY nama_orangtua ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-gray-900"><?= htmlspecialchars($row['nama_orangtua']) ?></td>
                                    <td class="px-4 py-3"><span class="px-2 py-1 bg-gray-100 rounded text-sm font-mono text-gray-700"><?= htmlspecialchars($row['username']) ?></span></td>
                                    <td class="px-4 py-3"><span class="text-sm text-gray-500 italic"><?= htmlspecialchars($row['password']) ?></span></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['no_whatsapp']) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-3"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus akun ini?')" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='5' class='text-center py-6 text-gray-500 italic'>Belum ada akun orang tua yang didaftarkan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>