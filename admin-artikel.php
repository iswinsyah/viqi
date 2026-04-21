<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Otomatis buat tabel jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS artikel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    kategori VARCHAR(100) DEFAULT 'Berita',
    gambar_cover VARCHAR(255),
    konten TEXT,
    status VARCHAR(50) DEFAULT 'publish',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Update otomatis struktur tabel jika belum ada kolom published_at
$conn->query("ALTER TABLE artikel ADD COLUMN published_at DATETIME NULL AFTER status");
$conn->query("ALTER TABLE artikel ADD COLUMN meta_title VARCHAR(255) AFTER published_at");
$conn->query("ALTER TABLE artikel ADD COLUMN meta_description TEXT AFTER meta_title");
$conn->query("ALTER TABLE artikel ADD COLUMN meta_keywords VARCHAR(255) AFTER meta_description");

// Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM artikel WHERE id = $hapus_id");
    header("Location: admin-artikel.php");
    exit;
}

// Ambil Data untuk Form Edit
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM artikel WHERE id = $edit_id");
    if ($res && $res->num_rows > 0) $data_edit = $res->fetch_assoc();
}

// Proses Simpan/Update Artikel
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = $conn->real_escape_string($_POST['judul']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $konten = $conn->real_escape_string($_POST['konten']);
    $status = $conn->real_escape_string($_POST['status']);
    $gambar_cover = $conn->real_escape_string($_POST['gambar_cover'] ?? '');
    $meta_title = $conn->real_escape_string($_POST['meta_title'] ?? '');
    $meta_description = $conn->real_escape_string($_POST['meta_description'] ?? '');
    $meta_keywords = $conn->real_escape_string($_POST['meta_keywords'] ?? '');
    // Generate URL Slug dari Judul
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));

    // Penanganan Tanggal Jadwal Terbit
    $published_at = !empty($_POST['published_at']) ? "'" . $conn->real_escape_string($_POST['published_at']) . "'" : "NULL";

    if (!empty($_POST['id'])) {
        $id_update = (int)$_POST['id'];
        $sql = "UPDATE artikel SET judul='$judul', slug='$slug', kategori='$kategori', konten='$konten', status='$status', published_at=$published_at, gambar_cover='$gambar_cover', meta_title='$meta_title', meta_description='$meta_description', meta_keywords='$meta_keywords' WHERE id=$id_update";
        $pesan = "Artikel berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO artikel (judul, slug, kategori, konten, status, published_at, gambar_cover, meta_title, meta_description, meta_keywords) VALUES ('$judul', '$slug', '$kategori', '$konten', '$status', $published_at, '$gambar_cover', '$meta_title', '$meta_description', '$meta_keywords')";
        $pesan = "Artikel baru berhasil dipublikasikan!";
    }
    
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = $pesan;
        $edit_mode = false; $data_edit = null; // Reset form
    } else {
        $pesan_error = "Gagal menyimpan artikel: " . $conn->error;
    }
}

$active_menu = 'artikel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Artikel | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <a href="artikel.php" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium flex items-center"><i class="fas fa-external-link-alt mr-2"></i> Lihat Blog Web</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-file-alt text-emerald-600 mr-2"></i>Manajemen Artikel & Berita</h1>
                <p class="text-gray-500 text-sm mt-1">Tulis dan terbitkan artikel SEO Anda di sini agar muncul di halaman depan website.</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <!-- FORM EDITOR ARTIKEL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 bg-emerald-50">
                    <h2 class="font-bold text-emerald-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-pen' ?> mr-2"></i><?= $edit_mode ? 'Edit Artikel' : 'Tulis Artikel Baru' ?></h2>
                </div>
                <div class="p-6">
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                        
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul Artikel <span class="text-red-500">*</span></label>
                                    <input type="text" name="judul" value="<?= $edit_mode ? htmlspecialchars($data_edit['judul']) : '' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: 5 Cara Menghafal Al-Quran Tanpa Stres">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Isi Konten Artikel <span class="text-red-500">*</span></label>
                                    <textarea id="konten-editor" name="konten" rows="15" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Salin (paste) hasil artikel ke sini..."><?= $edit_mode ? htmlspecialchars($data_edit['konten']) : '' ?></textarea>
                                </div>
                                
                                <!-- PENGATURAN SEO ALA YOAST -->
                                <div class="bg-gray-50 p-5 rounded-lg border border-gray-200 mt-6 shadow-sm">
                                    <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-200 pb-2"><i class="fas fa-search mr-2 text-blue-500"></i>Pengaturan SEO Meta</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">SEO Title (Judul Meta)</label>
                                            <input type="text" name="meta_title" value="<?= $edit_mode ? htmlspecialchars($data_edit['meta_title'] ?? '') : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Kosongkan jika sama dengan Judul Artikel">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description (150-160 karakter)</label>
                                            <textarea name="meta_description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Rangkuman artikel yang menarik untuk mengundang klik di Google..."><?= $edit_mode ? htmlspecialchars($data_edit['meta_description'] ?? '') : '' ?></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Focus Keyword (Kata Kunci Utama)</label>
                                            <input type="text" name="meta_keywords" value="<?= $edit_mode ? htmlspecialchars($data_edit['meta_keywords'] ?? '') : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: sekolah tahfidz, asrama nyaman, biaya pesantren">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                                    <select name="kategori" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                        <?php $cats = ['Berita', 'Tips Tahfidz', 'Edukasi', 'Kesehatan', 'Pengumuman']; foreach($cats as $c) { $sel = ($edit_mode && $data_edit['kategori'] == $c) ? 'selected' : ''; echo "<option value='$c' $sel>$c</option>"; } ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status Publikasi</label>
                                    <select name="status" id="status-select" onchange="toggleJadwal()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="publish" <?= ($edit_mode && $data_edit['status'] == 'publish') ? 'selected' : '' ?>>Publikasikan Sekarang</option>
                                        <option value="jadwalkan" <?= ($edit_mode && $data_edit['status'] == 'jadwalkan') ? 'selected' : '' ?>>Jadwalkan (Otomatis)</option>
                                        <option value="draft" <?= ($edit_mode && $data_edit['status'] == 'draft') ? 'selected' : '' ?>>Simpan Sbg Draft</option>
                                    </select>
                                </div>
                                <div id="wrap-jadwal" class="<?= ($edit_mode && $data_edit['status'] == 'jadwalkan') ? '' : 'hidden' ?>">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal & Waktu Terbit</label>
                                    <input type="datetime-local" name="published_at" value="<?= ($edit_mode && !empty($data_edit['published_at'])) ? date('Y-m-d\TH:i', strtotime($data_edit['published_at'])) : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Gambar Cover</label>
                                    <?php if($edit_mode && !empty($data_edit['gambar_cover'])) { echo "<img src='".$data_edit['gambar_cover']."' onerror=\"this.style.display='none'\" class='w-full h-32 object-cover rounded-lg mb-2 border border-gray-200'>"; } ?>
                                    <input type="text" name="gambar_cover" value="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_cover']) : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="https://... (Copas dari Media)">
                                </div>
                                
                                <div class="pt-4 border-t border-gray-100 flex gap-2">
                                    <?php if($edit_mode) echo '<a href="admin-artikel.php" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-bold">Batal</a>'; ?>
                                    <button type="submit" class="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-lg shadow-md transition"><i class="fas fa-paper-plane mr-1"></i> <?= $edit_mode ? 'Update' : 'Terbitkan' ?></button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL ARTIKEL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Artikel</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Judul & Kategori</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            $res = $conn->query("SELECT * FROM artikel ORDER BY id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $badge_class = 'bg-gray-100 text-gray-800';
                                    if ($row['status'] == 'publish') $badge_class = 'bg-green-100 text-green-800';
                                    if ($row['status'] == 'jadwalkan') $badge_class = 'bg-amber-100 text-amber-800';
                                    $tgl_tampil = date('d M Y', strtotime($row['created_at']));
                                    if ($row['status'] == 'jadwalkan' && !empty($row['published_at'])) $tgl_tampil = "<span class='text-amber-600' title='Akan Terbit'><i class='fas fa-clock mr-1'></i>" . date('d M Y H:i', strtotime($row['published_at'])) . "</span>";
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div class="text-xs text-emerald-600 font-semibold"><?= htmlspecialchars($row['kategori']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badge_class ?>"><?= $row['status'] ?></span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-500"><?= $tgl_tampil ?></td>
                                    <td class="px-6 py-4 text-center text-sm font-medium">
                                        <a href="artikel-detail.php?id=<?= $row['id'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 mr-2" title="Lihat Web"><i class="fas fa-eye"></i></a>
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Yakin hapus?')" class="text-rose-600 hover:text-rose-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-8 text-gray-500'>Belum ada artikel.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });
        
        function toggleJadwal() {
            const status = document.getElementById('status-select').value;
            const wrap = document.getElementById('wrap-jadwal');
            if (status === 'jadwalkan') {
                wrap.classList.remove('hidden');
            } else {
                wrap.classList.add('hidden');
            }
        }

        // Inisialisasi TinyMCE Editor
        tinymce.init({
            selector: '#konten-editor',
            plugins: 'lists link',
            toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist | link',
            menubar: false,
            height: 400,
            setup: function (editor) {
                editor.on('change', function () {
                    tinymce.triggerSave();
                });
            }
        });
    </script>
</body>
</html>