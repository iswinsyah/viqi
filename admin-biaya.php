<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS biaya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori VARCHAR(50),
    nama_komponen VARCHAR(255),
    nominal INT
)");

// 2. Insert data dummy jika tabel kosong
$cek = $conn->query("SELECT COUNT(*) as tot FROM biaya");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO biaya (kategori, nama_komponen, nominal) VALUES 
    ('pendaftaran', 'Formulir & Administrasi SPMB', 350000),
    ('pangkal', 'Uang Pembangunan (Gedung & Fasilitas)', 8000000),
    ('pangkal', 'Ranjang, Kasur, Lemari, dll (Hak Pakai)', 2500000),
    ('pangkal', 'Seragam (4 Stel) & Modul Tahun Pertama', 3000000),
    ('pangkal', 'Kegiatan Tahunan (Outbound dll)', 1500000),
    ('spp', 'Makan 3x Sehari (Termasuk Suplemen/Brain Food)', 800000),
    ('spp', 'Pendidikan, Asrama, & Ekstrakurikuler', 600000),
    ('spp', 'Laundry Pakaian (Standar)', 100000)");
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM biaya WHERE id = $id");
    header("Location: admin-biaya.php");
    exit;
}

// 4. Proses Simpan/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $nama_komponen = $conn->real_escape_string($_POST['nama_komponen']);
    $nominal = (int)$_POST['nominal'];

    if ($id > 0) {
        $sql = "UPDATE biaya SET kategori='$kategori', nama_komponen='$nama_komponen', nominal=$nominal WHERE id=$id";
        $pesan_sukses = "Komponen biaya berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO biaya (kategori, nama_komponen, nominal) VALUES ('$kategori', '$nama_komponen', $nominal)";
        $pesan_sukses = "Komponen biaya baru berhasil ditambahkan!";
    }
    $conn->query($sql);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM biaya WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'biaya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Biaya | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="biaya.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Halaman Biaya</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Pengaturan Biaya Pendidikan</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM INPUT -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-green-50 border-b border-green-100"><h2 class="font-bold text-green-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Komponen Biaya' : 'Tambah Komponen Biaya' ?></h2></div>
                <form action="admin-biaya.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kategori Tabel <span class="text-red-500">*</span></label>
                            <select name="kategori" required class="w-full px-4 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500">
                                <option value="pendaftaran" <?= ($edit_mode && $data_edit['kategori'] == 'pendaftaran') ? 'selected' : '' ?>>1. Biaya Pendaftaran</option>
                                <option value="pangkal" <?= ($edit_mode && $data_edit['kategori'] == 'pangkal') ? 'selected' : '' ?>>2. Uang Pangkal</option>
                                <option value="spp" <?= ($edit_mode && $data_edit['kategori'] == 'spp') ? 'selected' : '' ?>>3. SPP Bulanan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Komponen <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_komponen" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_komponen']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: Uang Gedung">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nominal (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="nominal" value="<?= $edit_mode ? htmlspecialchars($data_edit['nominal']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: 1500000">
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-biaya.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update' : 'Simpan' ?></button>
                    </div>
                </form>
            </div>

            <!-- TABEL LIST BIAYA -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Rincian Biaya Saat Ini</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Komponen</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Nominal (Rp)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM biaya ORDER BY FIELD(kategori, 'pendaftaran', 'pangkal', 'spp'), id ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold uppercase text-gray-500"><?= $row['kategori'] ?></td>
                                    <td class="px-4 py-3 font-bold text-gray-900"><?= htmlspecialchars($row['nama_komponen']) ?></td>
                                    <td class="px-4 py-3 text-right font-mono text-gray-700">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Yakin menghapus komponen biaya ini?')" class="text-rose-600 hover:text-rose-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500'>Belum ada data biaya.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });</script>
</body>
</html>