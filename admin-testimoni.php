<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS testimoni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150),
    jabatan VARCHAR(150),
    isi_testimoni TEXT,
    gambar_url VARCHAR(255)
)");

// 2. Insert data dummy jika tabel kosong
$cek = $conn->query("SELECT COUNT(*) as tot FROM testimoni");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO testimoni (nama, jabatan, isi_testimoni, gambar_url) VALUES 
    ('Bpk. Ahmad Fauzi', 'Orang tua santri Tahun Ke-2', 'Awalnya kami khawatir anak akan stres dipaksa menghafal. Ternyata di Villa Quran, anak saya sangat bahagia. Suasana asramanya bersih, makanannya bergizi penuh suplemen madu, dan alhamdulillah hafalannya sekarang sudah mutqin 10 Juz.', '')");
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM testimoni WHERE id = $id");
    header("Location: admin-testimoni.php");
    exit;
}

// 4. Proses Simpan/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = $conn->real_escape_string($_POST['nama']);
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    $isi_testimoni = $conn->real_escape_string($_POST['isi_testimoni']);
    $gambar_url = $conn->real_escape_string($_POST['gambar_url']);

    if ($id > 0) {
        $sql = "UPDATE testimoni SET nama='$nama', jabatan='$jabatan', isi_testimoni='$isi_testimoni', gambar_url='$gambar_url' WHERE id=$id";
        $pesan_sukses = "Data testimoni berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO testimoni (nama, jabatan, isi_testimoni, gambar_url) VALUES ('$nama', '$jabatan', '$isi_testimoni', '$gambar_url')";
        $pesan_sukses = "Testimoni baru berhasil ditambahkan!";
    }
    $conn->query($sql);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM testimoni WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'testimoni';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimoni | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Web</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-comments text-amber-500 mr-2"></i>Pengaturan Testimoni</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM INPUT (2 KOLOM) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-amber-50 border-b border-amber-100"><h2 class="font-bold text-amber-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Testimoni' : 'Tambah Testimoni Baru' ?></h2></div>
                <form action="admin-testimoni.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                        
                        <!-- KOLOM 1: URL Gambar -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">1. Foto Profil (Opsional)</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar</label>
                                <input type="text" name="gambar_url" id="img-url" value="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="https://..." oninput="document.getElementById('img-preview').src=this.value">
                                <p class="text-xs text-gray-500 mt-1">Jika dikosongkan, sistem akan otomatis membuat ikon lingkaran dengan inisial nama.</p>
                            </div>
                            <div class="bg-gray-100 p-2 rounded-lg border border-gray-200 h-32 flex items-center justify-center overflow-hidden">
                                <img id="img-preview" src="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" class="max-h-full object-contain" onerror="this.style.display='none'" onload="this.style.display='block'">
                            </div>
                        </div>

                        <!-- KOLOM 2: Keterangan -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">2. Keterangan Testimoni</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                                    <input type="text" name="nama" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Contoh: Bpk. Fulan">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Keterangan / Jabatan <span class="text-red-500">*</span></label>
                                    <input type="text" name="jabatan" value="<?= $edit_mode ? htmlspecialchars($data_edit['jabatan']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Contoh: Orang tua santri">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Isi Testimoni <span class="text-red-500">*</span></label>
                                <textarea name="isi_testimoni" rows="4" required class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 focus:border-amber-500" placeholder="Tuliskan ulasan di sini..."><?= $edit_mode ? htmlspecialchars($data_edit['isi_testimoni']) : '' ?></textarea>
                            </div>
                            <div class="pt-4 text-right">
                                <?php if($edit_mode) echo '<a href="admin-testimoni.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                                <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update Testimoni' : 'Simpan Testimoni' ?></button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- TABEL DAFTAR TESTIMONI -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Testimoni</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama & Profil</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Isi Testimoni</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM testimoni ORDER BY id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap"><div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama']) ?></div><div class="text-xs text-gray-500 mt-1"><?= htmlspecialchars($row['jabatan']) ?></div></td>
                                    <td class="px-4 py-3"><div class="text-sm text-gray-600 line-clamp-2 italic">"<?= htmlspecialchars($row['isi_testimoni']) ?>"</div></td>
                                    <td class="px-4 py-3 text-center font-medium"><a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></a> <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Yakin menghapus testimoni ini?')" class="text-rose-600 hover:text-rose-900" title="Hapus"><i class="fas fa-trash"></i></a></td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500'>Belum ada data testimoni.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });</script>
</body>
</html>