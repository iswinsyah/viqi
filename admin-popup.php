<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Tambahkan kolom tanggal_acara otomatis jika belum ada (Bypass error jika sudah ada)
$conn->query("ALTER TABLE pengaturan_popup ADD COLUMN tanggal_acara DATETIME NULL AFTER file_url");

// 1. Proses Update Data Jika Form Disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $img_src = $conn->real_escape_string($_POST['img_src']);
    $kiri_judul = $conn->real_escape_string($_POST['kiri_judul']);
    $kiri_sub = $conn->real_escape_string($_POST['kiri_sub']);
    $kanan_judul = $conn->real_escape_string($_POST['kanan_judul']); // Membolehkan tag HTML span
    $kanan_desc = $conn->real_escape_string($_POST['kanan_desc']);
    $file_url = $conn->real_escape_string($_POST['file_url']);
    $tanggal_acara = !empty($_POST['tanggal_acara']) ? "'" . date('Y-m-d H:i:s', strtotime($_POST['tanggal_acara'])) . "'" : "NULL";

    // Gunakan INSERT ... ON DUPLICATE KEY UPDATE agar anti-gagal meskipun data kosong
    $sql = "INSERT INTO pengaturan_popup (id, is_active, img_src, kiri_judul, kiri_sub, kanan_judul, kanan_desc, file_url, tanggal_acara) 
            VALUES (1, $is_active, '$img_src', '$kiri_judul', '$kiri_sub', '$kanan_judul', '$kanan_desc', '$file_url', $tanggal_acara)
            ON DUPLICATE KEY UPDATE 
            is_active = VALUES(is_active), img_src = VALUES(img_src), kiri_judul = VALUES(kiri_judul), 
            kiri_sub = VALUES(kiri_sub), kanan_judul = VALUES(kanan_judul), kanan_desc = VALUES(kanan_desc), 
            file_url = VALUES(file_url), tanggal_acara = VALUES(tanggal_acara)";
    
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

$active_menu = 'popup';
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

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

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
            <div class="px-6 py-4 border-b border-gray-100 bg-emerald-50 flex flex-col sm:flex-row justify-between items-center gap-3">
                <h2 class="font-bold text-emerald-800 flex-shrink-0"><i class="fas fa-edit mr-2"></i> Form Edit Konten Pop-up</h2>
                <div class="flex space-x-2">
                    <button type="button" onclick="setTemplateEbook()" class="text-xs bg-white text-emerald-700 border border-emerald-200 px-3 py-1.5 rounded hover:bg-emerald-100 transition shadow-sm font-medium"><i class="fas fa-book mr-1"></i> Template E-Book</button>
                    <button type="button" onclick="setTemplateOpenHouse()" class="text-xs bg-white text-amber-700 border border-amber-200 px-3 py-1.5 rounded hover:bg-amber-50 transition shadow-sm font-medium"><i class="fas fa-home mr-1"></i> Template Open House</button>
                </div>
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
                            <h3 class="font-bold text-teal-800 mb-4 border-b border-teal-200 pb-2">Bagian Kiri (Visual/Buku/Acara)</h3>
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar Cover</label>
                                <input type="text" id="img_src" name="img_src" value="<?= htmlspecialchars($data['img_src'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="https://...">
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Teks Judul Kecil</label>
                                <input type="text" id="kiri_judul" name="kiri_judul" value="<?= htmlspecialchars($data['kiri_judul'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Contoh: E-Book Spesial: atau Undangan Open House:">
                                </div>
                                <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Teks Sub-Judul (Nama Buku / Acara)</label>
                                <input type="text" id="kiri_sub" name="kiri_sub" value="<?= htmlspecialchars($data['kiri_sub'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Judul Buku atau Nama Acara...">
                                </div>
                            </div>

                            <!-- Sisi Kanan Pop-up -->
                            <div class="bg-amber-50 p-4 rounded-lg border border-amber-100">
                                <h3 class="font-bold text-amber-800 mb-4 border-b border-amber-200 pb-2">Bagian Kanan (Form Penawaran)</h3>
                                <div class="mb-4">
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Headline Penawaran Utama</label>
                                <input type="text" id="kanan_judul" name="kanan_judul" value="<?= htmlspecialchars($data['kanan_judul'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg text-sm" placeholder="Teks Headline Utama">
                                    <p class="text-[10px] mt-1 text-gray-500">Mendukung tag HTML, contoh: <code>&lt;span class="text-amber-500"&gt;Gratis!&lt;/span&gt;</code></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi / Ajakan Mengisi Form</label>
                                <textarea id="kanan_desc" name="kanan_desc" rows="3" class="w-full px-3 py-2 border rounded-lg text-sm"><?= htmlspecialchars($data['kanan_desc'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                    <!-- Tanggal Acara & Target Action -->
                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Tanggal & Jam Acara (Opsional)</label>
                                    <input type="datetime-local" id="tanggal_acara" name="tanggal_acara" value="<?= !empty($data['tanggal_acara']) ? date('Y-m-d\TH:i', strtotime($data['tanggal_acara'])) : '' ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Target Action (Nama File PDF / Link Grup WA)</label>
                                    <input type="text" id="file_url" name="file_url" value="<?= htmlspecialchars($data['file_url'] ?? '') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: ebook.pdf atau https://chat.whatsapp.com/...">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 flex justify-end space-x-4">
                            <a href="index.html?preview_popup=true" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center">
                                <i class="fas fa-eye mr-2"></i> Lihat Hasil Pop-up
                            </a>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-lg transition shadow-md flex items-center">
                                <i class="fas fa-save mr-2"></i> Update Pop-up
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
<script>
    function setTemplateOpenHouse() {
        document.getElementById('kiri_judul').value = 'Undangan Eksklusif:';
        document.getElementById('kiri_sub').value = 'Open House Villa Quran';
        document.getElementById('kanan_judul').value = '<span class="text-amber-500">Gratis!</span> Hadiri Open House Kami';
        document.getElementById('kanan_desc').value = 'Daftarkan diri Anda untuk melihat langsung fasilitas dan metode tahfidz di Villa Quran. Dapatkan berbagai insight menarik. Tempat terbatas!';
        document.getElementById('file_url').placeholder = 'https://chat.whatsapp.com/... (Contoh: Link Grup WA Open House)';
        document.getElementById('tanggal_acara').focus();
    }

    function setTemplateEbook() {
        document.getElementById('kiri_judul').value = 'E-Book Spesial:';
        document.getElementById('kiri_sub').value = 'Panduan Orang Tua';
        document.getElementById('kanan_judul').value = '<span class="text-amber-500">Gratis!</span> Download E-Book';
        document.getElementById('kanan_desc').value = 'Tinggalkan nomor WA Anda dan kami akan mengirimkan link download E-Book langsung ke WhatsApp Anda sekarang juga.';
        document.getElementById('file_url').placeholder = 'Contoh: ebook-parenting.pdf atau link Google Drive';
        document.getElementById('tanggal_acara').value = '';
    }
</script>
</body>
</html>