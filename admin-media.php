<?php
require_once 'koneksi.php';

// Buat folder uploads jika belum ada
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$pesan_sukses = "";
$pesan_error = "";

// PROSES UNGGAH FILE
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_media'])) {
    $file_name = $_FILES['file_media']['name'];
    $file_tmp = $_FILES['file_media']['tmp_name'];
    $file_error = $_FILES['file_media']['error'];
    $file_size = $_FILES['file_media']['size'];

    if ($file_error === 0) {
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        // Batasi ekstensi yang boleh diupload
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm'];

        if (in_array($file_ext, $allowed_ext)) {
            if ($file_size <= 10485760) { // Limit ukuran 10MB
                // Bersihkan nama file agar rapi dan tidak bentrok
                $new_file_name = uniqid('media_') . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp, $destination)) {
                    $pesan_sukses = "Media berhasil diunggah!";
                } else {
                    $pesan_error = "Gagal memindahkan file ke folder tujuan.";
                }
            } else {
                $pesan_error = "Ukuran file terlalu besar. Maksimal 10MB.";
            }
        } else {
            $pesan_error = "Ekstensi file tidak diizinkan. Hanya gambar dan video yang diperbolehkan.";
        }
    } else {
        $pesan_error = "Terjadi kesalahan saat mengunggah file. Pastikan Anda memilih file.";
    }
}

// PROSES HAPUS FILE
if (isset($_GET['hapus']) && !empty($_GET['hapus'])) {
    $file_to_delete = basename($_GET['hapus']); // Keamanan: hindari directory traversal
    $file_path = $upload_dir . $file_to_delete;

    if (file_exists($file_path) && is_file($file_path)) {
        if (unlink($file_path)) {
            $pesan_sukses = "File media berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus file.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penyimpanan Media | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php $active_menu = 'media'; include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-folder-open text-indigo-600 mr-2"></i>Penyimpanan Media Server</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola aset gambar dan video Anda secara mandiri di sini.</p>
                </div>
            </div>

            <?php if($pesan_sukses): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div>
            <?php endif; ?>
            <?php if($pesan_error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center shadow-sm"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div>
            <?php endif; ?>

            <!-- FORM UNGGAH MEDIA -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2"><i class="fas fa-cloud-upload-alt mr-2 text-indigo-500"></i>Unggah Aset Baru</h3>
                <form action="" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pilih File (JPG, PNG, WEBP, MP4 - Maks 10MB)</label>
                        <input type="file" name="file_media" required accept="image/*,video/mp4,video/webm" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-gray-200 rounded-full p-1 focus:outline-none">
                    </div>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-8 rounded-full transition shadow-md w-full md:w-auto flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i> Unggah File
                    </button>
                </form>
            </div>

            <!-- GALERI MEDIA GRID -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-th-large mr-2 text-indigo-500"></i>Galeri Aset Tersimpan</h3>
                    <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Folder: /uploads</span>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <?php
                        $files = scandir($upload_dir);
                        $has_files = false;
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..') {
                                $has_files = true;
                                $file_path = $upload_dir . $file;
                                $file_ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                $is_video = in_array($file_ext, ['mp4', 'webm']);
                                ?>
                                <div class="relative group rounded-lg overflow-hidden border border-gray-200 shadow-sm hover:shadow-md transition bg-gray-50">
                                    <?php if ($is_video): ?>
                                        <video src="<?= $file_path ?>" class="w-full h-32 object-cover"></video>
                                        <div class="absolute top-2 left-2 bg-black bg-opacity-60 text-white text-xs px-2 py-1 rounded-md shadow"><i class="fas fa-video"></i> Video</div>
                                    <?php else: ?>
                                        <img src="<?= $file_path ?>" alt="<?= $file ?>" class="w-full h-32 object-cover">
                                    <?php endif; ?>
                                    
                                    <!-- Tombol Aksi Melayang -->
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100 gap-2 backdrop-blur-[1px]">
                                        <a href="<?= $file_path ?>" target="_blank" class="bg-white text-gray-800 w-8 h-8 rounded-full flex items-center justify-center hover:bg-indigo-100 hover:text-indigo-600 transition shadow" title="Lihat"><i class="fas fa-eye"></i></a>
                                        <!-- Fitur Copy Link otomatis dengan Javascript -->
                                        <button onclick="navigator.clipboard.writeText('<?= 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/'.$file_path ?>'); alert('Link berhasil disalin!');" class="bg-white text-gray-800 w-8 h-8 rounded-full flex items-center justify-center hover:bg-emerald-100 hover:text-emerald-600 transition shadow" title="Salin Link (URL)"><i class="fas fa-link"></i></button>
                                        <a href="?hapus=<?= urlencode($file) ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus media ini secara permanen?');" class="bg-white text-gray-800 w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-100 hover:text-red-600 transition shadow" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </div>
                                    <!-- Nama File di Bawah -->
                                    <div class="p-2 bg-white text-[10px] truncate text-gray-500 font-mono" title="<?= $file ?>"><?= $file ?></div>
                                </div>
                                <?php
                            }
                        }
                        
                        if (!$has_files):
                        ?>
                            <div class="col-span-full text-center py-12 text-gray-400">
                                <i class="fas fa-folder-open text-5xl mb-4 opacity-30"></i>
                                <p>Belum ada media yang diunggah. Silakan unggah gambar atau video pertama Anda!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>