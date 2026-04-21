<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat Tabel Jika Belum Ada
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_tentang (
    id INT PRIMARY KEY DEFAULT 1,
    judul VARCHAR(255),
    konten TEXT,
    gambar_url VARCHAR(255)
)");

// Insert default data jika masih kosong
$conn->query("INSERT IGNORE INTO pengaturan_tentang (id, judul, konten, gambar_url) 
VALUES (1, 'Tentang Villa Quran Indonesia', '<p>Berawal dari cita-cita mulia untuk menghadirkan lingkungan tahfidz yang jauh dari kesan kumuh dan menekan, Villa Quran Indonesia hadir dengan konsep pendidikan modern. Kami memadukan kurikulum Islam, adab, dan keterampilan abad 21 di lingkungan yang membahagiakan layaknya sebuah villa.</p><ul><li>Terakreditasi & Berizin Resmi Kementerian</li><li>Didukung Asatidz Bersanad & Profesional IT</li></ul>', 'https://images.unsplash.com/photo-1577896851231-70ef18881754?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')");

// 2. Proses Simpan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $judul = $conn->real_escape_string($_POST['judul']);
    $konten = $conn->real_escape_string($_POST['konten']);
    $gambar_url = $conn->real_escape_string($_POST['gambar_url']);
    
    $sql = "UPDATE pengaturan_tentang SET judul='$judul', konten='$konten', gambar_url='$gambar_url' WHERE id=1";
            
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = "Profil Tentang Kami berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui: " . $conn->error;
    }
}

// 3. Ambil data
$res = $conn->query("SELECT * FROM pengaturan_tentang WHERE id=1");
$data = $res->fetch_assoc();
$active_menu = 'tentang';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- TinyMCE Rich Text Editor -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html#profil" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium flex items-center">
                <i class="fas fa-external-link-alt mr-2"></i> Lihat Web
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-info-circle text-blue-600 mr-2"></i>Pengaturan Tentang Kami</h1>
                <p class="text-gray-500">Sesuaikan teks profil dan gambar yang muncul di halaman depan (Seksi Profil/Tentang Kami).</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-blue-50 border-b border-blue-100"><h2 class="font-bold text-blue-800"><i class="fas fa-edit mr-2"></i>Editor Konten</h2></div>
                <div class="p-6">
                    <form action="" method="POST">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Judul Seksi</label>
                                <input type="text" name="judul" value="<?= htmlspecialchars($data['judul']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar Samping</label>
                                <input type="text" name="gambar_url" value="<?= htmlspecialchars($data['gambar_url']) ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="https://...">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Isi Konten Profil</label>
                                <textarea id="konten-editor" name="konten" rows="12" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($data['konten']) ?></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition flex items-center">
                                <i class="fas fa-save mr-2"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });
        
        // Inisialisasi TinyMCE Editor
        tinymce.init({ selector: '#konten-editor', plugins: 'lists link', toolbar: 'undo redo | bold italic underline | alignleft aligncenter alignright | bullist numlist | link', menubar: false, height: 350 });
    </script>
</body>
</html>