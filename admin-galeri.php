<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS galeri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(150),
    caption VARCHAR(255),
    gambar_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Insert data dummy jika tabel kosong (agar web tidak kosong)
$cek = $conn->query("SELECT COUNT(*) as tot FROM galeri");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO galeri (judul, caption, gambar_url) VALUES 
    ('Halaqah Tahfidz', 'Santri rutin menyetorkan hafalan bakda subuh.', 'https://images.unsplash.com/photo-1609802422036-7c152a5a5460?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('KBM di Kelas', 'Suasana belajar akademik yang interaktif dan nyaman.', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Ibadah Bersama', 'Shalat berjamaah 5 waktu di Masjid Jami Villa Quran.', 'https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Olahraga Sunnah', 'Kegiatan fisik sore hari untuk menjaga kebugaran santri.', 'https://images.unsplash.com/photo-1511649475669-e288648b2339?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Kenyamanan Asrama', 'Fasilitas asrama yang asri layaknya sedang berlibur.', 'https://images.unsplash.com/photo-1427504494785-3a9ca7044f45?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('Makan Bergizi', 'Asupan menu seimbang yang dirancang ahli gizi profesional.', 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')");
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM galeri WHERE id = $id");
    header("Location: admin-galeri.php");
    exit;
}

// 4. Proses Simpan/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul = $conn->real_escape_string($_POST['judul']);
    $caption = $conn->real_escape_string($_POST['caption']);
    $gambar_url = $conn->real_escape_string($_POST['gambar_url']);

    if ($id > 0) {
        $sql = "UPDATE galeri SET judul='$judul', caption='$caption', gambar_url='$gambar_url' WHERE id=$id";
        $pesan_sukses = "Data galeri berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO galeri (judul, caption, gambar_url) VALUES ('$judul', '$caption', '$gambar_url')";
        $pesan_sukses = "Foto baru berhasil ditambahkan ke galeri!";
    }
    $conn->query($sql);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM galeri WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'galeri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Kegiatan | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html#galeri" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Web</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-images text-indigo-600 mr-2"></i>Manajemen Galeri Kegiatan</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM INPUT (2 KOLOM) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100"><h2 class="font-bold text-indigo-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Foto Galeri' : 'Tambah Foto Baru' ?></h2></div>
                <form action="admin-galeri.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                        
                        <!-- KOLOM 1: Link Media -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">1. Media & URL Gambar</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar <span class="text-red-500">*</span></label>
                                <input type="text" name="gambar_url" id="img-url" value="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="https://..." oninput="document.getElementById('img-preview').src=this.value">
                            </div>
                            <div class="bg-gray-100 p-2 rounded-lg border border-gray-200 h-48 flex items-center justify-center overflow-hidden">
                                <img id="img-preview" src="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" class="max-h-full object-contain" onerror="this.src='https://via.placeholder.com/600x400?text=Preview+Gambar'" alt="Preview">
                            </div>
                        </div>

                        <!-- KOLOM 2: Judul & Caption -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">2. Informasi Kegiatan</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Judul Kegiatan <span class="text-red-500">*</span></label>
                                <input type="text" name="judul" value="<?= $edit_mode ? htmlspecialchars($data_edit['judul']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: Halaqah Tahfidz">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Caption / Keterangan Tambahan</label>
                                <textarea name="caption" rows="4" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 focus:border-indigo-500" placeholder="Deskripsi singkat kegiatan..."><?= $edit_mode ? htmlspecialchars($data_edit['caption']) : '' ?></textarea>
                            </div>
                            <div class="pt-4 text-right">
                                <?php if($edit_mode) echo '<a href="admin-galeri.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update Galeri' : 'Simpan ke Galeri' ?></button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- TABEL LIST GALERI -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Foto Galeri</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Thumbnail</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Judul Kegiatan & Caption</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM galeri ORDER BY id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr>
                                    <td class="px-4 py-3"><img src="<?= htmlspecialchars($row['gambar_url']) ?>" class="w-20 h-14 rounded object-cover shadow-sm"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div class="text-xs text-gray-500 line-clamp-1"><?= htmlspecialchars($row['caption']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus foto ini dari galeri?')" class="text-rose-600 hover:text-rose-900"><i class="fas fa-trash"></i> Hapus</a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500'>Belum ada data galeri.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });</script>
</body>
</html>