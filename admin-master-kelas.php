<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Cek Otoritas Akses Menu
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

// Ambil permission allowed_roles dari db
$res_perm = $conn->query("SELECT allowed_roles FROM menu_permissions WHERE menu_key = 'master_kelas' LIMIT 1");
$allowed_roles = [];
if ($res_perm && $res_perm->num_rows > 0) {
    $row_p = $res_perm->fetch_assoc();
    $allowed_roles = !empty($row_p['allowed_roles']) ? explode(',', $row_p['allowed_roles']) : [];
}

// Normalisasi untuk pencocokan tangguh
$norm_user_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);
$norm_allowed_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $allowed_roles);
$is_super_admin = in_array('super_admin', $norm_user_roles);

if (!$is_super_admin && empty(array_intersect($norm_allowed_roles, $norm_user_roles))) {
    echo "<div style='color: red; padding: 20px; font-weight: bold; text-align: center; font-family: sans-serif; margin-top: 50px;'>
            Anda tidak memiliki hak akses untuk membuka halaman ini.
          </div>";
    exit;
}

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS master_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(100) UNIQUE NOT NULL,
    kategori_kelas ENUM('Diknas', 'Diniyah', 'Ekstrakurikuler', 'Lainnya') DEFAULT 'Lainnya',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM master_kelas WHERE id = $id");
    header("Location: admin-master-kelas.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_kelas = $conn->real_escape_string($_POST['nama_kelas']);
    $kategori_kelas = $conn->real_escape_string($_POST['kategori_kelas']);
    $keterangan = $conn->real_escape_string($_POST['keterangan']);

    if ($id > 0) {
        $sql = "UPDATE master_kelas SET nama_kelas='$nama_kelas', kategori_kelas='$kategori_kelas', keterangan='$keterangan' WHERE id=$id";
        $pesan_sukses = "Data kelas berhasil diupdate!";
    } else {
        $sql = "INSERT INTO master_kelas (nama_kelas, kategori_kelas, keterangan) VALUES ('$nama_kelas', '$kategori_kelas', '$keterangan')";
        $pesan_sukses = "Kelas baru berhasil ditambahkan!";
    }
    if (!$conn->query($sql)) {
        $pesan_error = "Gagal menyimpan: " . $conn->error;
    }
}

// Ambil data untuk mode edit
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM master_kelas WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'master_kelas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Kelas | Portal Ustadz</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-school text-cyan-600 mr-2"></i>Master Data Kelas</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Kelas' : 'Tambah Kelas Baru' ?></h2></div>
                <form action="admin-master-kelas.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kelas</label>
                            <input type="text" name="nama_kelas" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_kelas']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Kelas 7A">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Kelas</label>
                            <select name="kategori_kelas" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <?php $kategori_opsi = ['Diknas', 'Diniyah', 'Ekstrakurikuler', 'Lainnya']; foreach($kategori_opsi as $k) { $sel = ($edit_mode && $data_edit['kategori_kelas'] == $k) ? 'selected' : ''; echo "<option value='$k' $sel>$k</option>"; } ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" value="<?= $edit_mode ? htmlspecialchars($data_edit['keterangan']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Untuk santri putra">
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-master-kelas.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Kelas' : 'Simpan Kelas' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Kelas Tersedia</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Kelas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM master_kelas ORDER BY kategori_kelas, nama_kelas ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-gray-900"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                    <td class="px-4 py-3 text-sm"><span class="px-2 py-1 text-xs font-semibold rounded-full bg-cyan-100 text-cyan-800"><?= htmlspecialchars($row['kategori_kelas']) ?></span></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus kelas ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500 italic'>Belum ada data kelas. Silakan tambahkan kelas baru.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>