<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS fasilitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150),
    deskripsi TEXT,
    gambar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Insert data dummy jika tabel kosong
$cek = $conn->query("SELECT COUNT(*) as tot FROM fasilitas");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO fasilitas (judul, deskripsi, gambar_url) VALUES 
    ('Masjid Jami\' Pesantren', 'Masjid luas dan nyaman sebagai pusat ibadah dan halaqah tahfidz santri.', 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Kamar Asrama Full AC', 'Kamar santri yang bersih, nyaman, dan berpendingin ruangan maksimal 4 santri/kamar.', 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Ruang Kelas Interaktif', 'Dilengkapi smart TV dan bangku ergonomis untuk menjaga fokus belajar santri.', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Laboratorium Komputer', 'Fasilitas PC mumpuni untuk menunjang program keahlian Digitalpreneur & AI.', 'https://images.unsplash.com/photo-1531482615713-2afd69097998?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')");
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM fasilitas WHERE id = $id");
    header("Location: admin-fasilitas.php");
    exit;
}

// 4. Proses Simpan/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul = $conn->real_escape_string($_POST['judul']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);
    $gambar_url = $conn->real_escape_string($_POST['gambar_url']);

    if ($id > 0) {
        $sql = "UPDATE fasilitas SET judul='$judul', deskripsi='$deskripsi', gambar_url='$gambar_url' WHERE id=$id";
        $pesan_sukses = "Data fasilitas berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO fasilitas (judul, deskripsi, gambar_url) VALUES ('$judul', '$deskripsi', '$gambar_url')";
        $pesan_sukses = "Fasilitas baru berhasil ditambahkan!";
    }
    $conn->query($sql);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM fasilitas WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'fasilitas';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Fasilitas | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="fasilitas.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Web</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-building text-blue-600 mr-2"></i>Pengaturan Fasilitas Sekolah</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM INPUT (2 KOLOM) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-blue-50 border-b border-blue-100"><h2 class="font-bold text-blue-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Fasilitas' : 'Tambah Fasilitas Baru' ?></h2></div>
                <form action="admin-fasilitas.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                        
                        <!-- KOLOM 1: Gambar -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">1. Foto Fasilitas</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar <span class="text-red-500">*</span></label>
                                <input type="text" name="gambar_url" id="img-url" value="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="https://..." oninput="document.getElementById('img-preview').src=this.value">
                            </div>
                            <div class="bg-gray-100 p-2 rounded-lg border border-gray-200 h-48 flex items-center justify-center overflow-hidden">
                                <img id="img-preview" src="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" class="max-h-full object-contain" onerror="this.src='https://via.placeholder.com/600x400?text=Preview+Gambar'" alt="Preview">
                            </div>
                        </div>

                        <!-- KOLOM 2: Keterangan -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">2. Keterangan Fasilitas</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Judul Fasilitas <span class="text-red-500">*</span></label>
                                <input type="text" name="judul" value="<?= $edit_mode ? htmlspecialchars($data_edit['judul']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Masjid Jami' Pesantren">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi Singkat <span class="text-red-500">*</span></label>
                                <textarea name="deskripsi" rows="5" required class="w-full px-4 py-2 border rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Jelaskan kegunaan dan keunggulan fasilitas ini..."><?= $edit_mode ? htmlspecialchars($data_edit['deskripsi']) : '' ?></textarea>
                            </div>
                            <div class="pt-4 text-right">
                                <?php if($edit_mode) echo '<a href="admin-fasilitas.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update' : 'Simpan Fasilitas' ?></button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- TABEL LIST FASILITAS -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Fasilitas Tersedia</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-32">Foto</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM fasilitas ORDER BY id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr>
                                    <td class="px-4 py-3"><img src="<?= htmlspecialchars($row['gambar_url']) ?>" class="w-24 h-16 rounded object-cover shadow-sm"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($row['deskripsi']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Yakin menghapus data fasilitas ini?')" class="text-rose-600 hover:text-rose-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500'>Belum ada data fasilitas.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });</script>
</body>
</html>