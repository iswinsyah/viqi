<?php
require_once 'koneksi.php';

// 1. Proses Update Data Jika Form Disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $img_src = $conn->real_escape_string($_POST['img_src']);
    $kiri_judul = $conn->real_escape_string($_POST['kiri_judul']);
    $kiri_sub = $conn->real_escape_string($_POST['kiri_sub']);
    $kanan_judul = $conn->real_escape_string($_POST['kanan_judul']); // Membolehkan tag HTML span
    $kanan_desc = $conn->real_escape_string($_POST['kanan_desc']);
    $file_url = $conn->real_escape_string($_POST['file_url']);

    $sql = "UPDATE pengaturan_popup SET 
            is_active = $is_active,
            img_src = '$img_src',
            kiri_judul = '$kiri_judul',
            kiri_sub = '$kiri_sub',
            kanan_judul = '$kanan_judul',
            kanan_desc = '$kanan_desc',
            file_url = '$file_url'
            WHERE id = 1";
    
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = "Pengaturan Pop-up Lead Magnet berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal menyimpan pengaturan: " . $conn->error;
    }
}

// 2. Ambil Data Terkini
$data = [];
$result = $conn->query("SELECT * FROM pengaturan_popup WHERE id = 1");
if ($result && $result->num_rows > 0) {
    $data = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Pop-up | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-20 h-full absolute md:relative">
        <div class="h-16 flex items-center justify-center border-b border-gray-800 px-4">
            <span class="text-xl font-bold text-emerald-400">
                <i class="fas fa-leaf mr-2"></i>VQ Admin
            </span>
        </div>
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="space-y-1 px-2">
                <a href="admin.html" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-tachometer-alt w-6 text-center"></i><span class="ml-3 font-medium">Dashboard</span>
                </a>
                <div class="pt-4 pb-2"><p class="px-4 text-xs font-bold text-gray-500 uppercase">Marketing & Leads</p></div>
                <a href="data-pipeline.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-columns w-6 text-center"></i><span class="ml-3 font-medium">Pipeline Prospek</span>
                </a>
                <a href="data-agen.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition">
                    <i class="fas fa-handshake w-6 text-center"></i><span class="ml-3 font-medium">Data Agen</span>
                </a>
                <div class="pt-4 pb-2"><p class="px-4 text-xs font-bold text-gray-500 uppercase">Sistem</p></div>
                <a href="admin-popup.php" class="flex items-center px-4 py-3 bg-emerald-600 text-white rounded-lg">
                    <i class="fas fa-bullhorn w-6 text-center"></i><span class="ml-3 font-medium">Pengaturan Pop-up</span>
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-gray-800">
            <a href="index.html" class="flex items-center text-sm text-gray-400 hover:text-white transition"><i class="fas fa-sign-out-alt w-5"></i> Keluar</a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium flex items-center">
                <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Pop-up (Lead Magnet)</h1>
                <p class="text-gray-500">Sesuaikan teks, penawaran, dan file unduhan untuk memancing prospek di web.</p>
            </div>

            <?php if(isset($pesan_sukses)) { ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div>
            <?php } ?>
            <?php if(isset($pesan_error)) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div>
            <?php } ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden max-w-4xl">
                <div class="px-6 py-4 border-b border-gray-100 bg-emerald-50">
                    <h2 class="font-bold text-emerald-800"><i class="fas fa-edit mr-2"></i> Form Edit Konten Pop-up</h2>
                </div>
                <div class="p-6">
                    <form action="" method="POST">
                        
                        <!-- Status Aktif -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200 flex justify-between items-center">
                            <div>
                                <h4 class="font-bold text-gray-800">Status Tampil Pop-up</h4>
                                <p class="text-sm text-gray-500">Jika dimatikan, pop-up tidak akan muncul di web sama sekali.</p>
                            </div>
                            <label class="inline-flex relative items-center cursor-pointer">
                                <input type="checkbox" name="is_active" class="sr-only peer" <?= ($data['is_active'] == 1) ? 'checked' : '' ?>>
                                <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Sisi Kiri Pop-up -->
                            <div class="bg-teal-50 p-4 rounded-lg border border-teal-100">
                                <h3 class="font-bold text-teal-800 mb-4 border-b border-teal-200 pb-2">Bagian Kiri (Visual/Buku)</h3>
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar Cover</label>
                                    <input type="text" name="img_src" value="<?= htmlspecialchars($data['img_src'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="https://...">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Teks Judul Kecil</label>
                                    <input type="text" name="kiri_judul" value="<?= htmlspecialchars($data['kiri_judul'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Contoh: E-Book Spesial:">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Teks Sub-Judul (Nama Buku)</label>
                                    <input type="text" name="kiri_sub" value="<?= htmlspecialchars($data['kiri_sub'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Judul Buku...">
                                </div>
                            </div>

                            <!-- Sisi Kanan Pop-up -->
                            <div class="bg-amber-50 p-4 rounded-lg border border-amber-100">
                                <h3 class="font-bold text-amber-800 mb-4 border-b border-amber-200 pb-2">Bagian Kanan (Form Penawaran)</h3>
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Headline Penawaran Utama</label>
                                    <input type="text" name="kanan_judul" value="<?= htmlspecialchars($data['kanan_judul'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Teks Headline Utama">
                                    <p class="text-[10px] mt-1 text-gray-500">Mendukung tag HTML, contoh: <code>&lt;span class="text-amber-500"&gt;Gratis!&lt;/span&gt;</code></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi / Ajakan Mengisi Form</label>
                                    <textarea name="kanan_desc" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($data['kanan_desc'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Target File Unduhan -->
                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <label class="block text-sm font-bold text-gray-700 mb-1">Target File Unduhan (Nama File PDF / Link GDrive)</label>
                            <input type="text" name="file_url" value="<?= htmlspecialchars($data['file_url'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Contoh: ebook-parenting.pdf atau https://drive.google.com/...">
                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-lg transition shadow-md flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Pop-up
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>