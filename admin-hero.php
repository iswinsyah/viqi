<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat Tabel Jika Belum Ada
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_hero (
    id INT PRIMARY KEY DEFAULT 1,
    hero_judul TEXT,
    hero_deskripsi TEXT,
    hero_cta_teks VARCHAR(100),
    hero_cta_url VARCHAR(255),
    hero_bg_url VARCHAR(255),
    usp1_ikon VARCHAR(50), usp1_judul VARCHAR(100), usp1_deskripsi VARCHAR(255),
    usp2_ikon VARCHAR(50), usp2_judul VARCHAR(100), usp2_deskripsi VARCHAR(255),
    usp3_ikon VARCHAR(50), usp3_judul VARCHAR(100), usp3_deskripsi VARCHAR(255)
)");

// Insert default data jika masih kosong
$conn->query("INSERT IGNORE INTO pengaturan_hero (id, hero_judul, hero_deskripsi, hero_cta_teks, hero_cta_url, hero_bg_url, usp1_ikon, usp1_judul, usp1_deskripsi, usp2_ikon, usp2_judul, usp2_deskripsi, usp3_ikon, usp3_judul, usp3_deskripsi) 
VALUES (1, 'Mencetak Hafidz Bersanad,<br class=\"hidden md:block\"> di Lingkungan Nyaman ala Villa', 'Sekolah tahfidz berasrama dengan metode terstruktur, pendampingan adab, dan nutrisi optimal untuk kebahagiaan putra-putri Anda dalam menghafal Al-Quran.', 'Informasi Pendaftaran', '#spmb', '', 'fas fa-leaf', 'Lingkungan ala Villa', 'Asri, bersih, dan anti-stres', 'fas fa-book-open', 'Tahfidz Bersanad', 'Target mutqin dibimbing ahlinya', 'fas fa-apple-alt', 'Gizi & Brain Food', 'Nutrisi khusus kecerdasan otak')");

// 2. Proses Simpan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $hj = $conn->real_escape_string($_POST['hero_judul']);
    $hd = $conn->real_escape_string($_POST['hero_deskripsi']);
    $hct = $conn->real_escape_string($_POST['hero_cta_teks']);
    $hcu = $conn->real_escape_string($_POST['hero_cta_url']);
    $hbg = $conn->real_escape_string($_POST['hero_bg_url']);
    
    $u1i = $conn->real_escape_string($_POST['usp1_ikon']);
    $u1j = $conn->real_escape_string($_POST['usp1_judul']);
    $u1d = $conn->real_escape_string($_POST['usp1_deskripsi']);
    
    $u2i = $conn->real_escape_string($_POST['usp2_ikon']);
    $u2j = $conn->real_escape_string($_POST['usp2_judul']);
    $u2d = $conn->real_escape_string($_POST['usp2_deskripsi']);
    
    $u3i = $conn->real_escape_string($_POST['usp3_ikon']);
    $u3j = $conn->real_escape_string($_POST['usp3_judul']);
    $u3d = $conn->real_escape_string($_POST['usp3_deskripsi']);

    $sql = "UPDATE pengaturan_hero SET 
            hero_judul='$hj', hero_deskripsi='$hd', hero_cta_teks='$hct', hero_cta_url='$hcu', hero_bg_url='$hbg',
            usp1_ikon='$u1i', usp1_judul='$u1j', usp1_deskripsi='$u1d',
            usp2_ikon='$u2i', usp2_judul='$u2j', usp2_deskripsi='$u2d',
            usp3_ikon='$u3i', usp3_judul='$u3j', usp3_deskripsi='$u3d' WHERE id=1";
            
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = "Pengaturan Hero dan Keunggulan berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui: " . $conn->error;
    }
}

// 3. Ambil data
$res = $conn->query("SELECT * FROM pengaturan_hero WHERE id=1");
$data = $res->fetch_assoc();
$active_menu = 'hero';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Hero | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium flex items-center">
                <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-home text-indigo-600 mr-2"></i>Pengaturan Hero & USP</h1>
                <p class="text-gray-500">Sesuaikan tampilan awal (Header Utama) dan 3 Kotak Keunggulan yang dilihat pengunjung.</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <form action="" method="POST">
                <!-- BAGIAN HERO (ATAS) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 py-4 bg-teal-50 border-b border-teal-100"><h2 class="font-bold text-teal-800"><i class="fas fa-desktop mr-2"></i>Seksi Hero (Banner Utama)</h2></div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Judul Utama (Headline)</label>
                            <textarea name="hero_judul" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500"><?= htmlspecialchars($data['hero_judul']) ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Gunakan tag <code>&lt;br&gt;</code> untuk pindah baris.</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Teks Paragraf Deskripsi</label>
                            <textarea name="hero_deskripsi" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500"><?= htmlspecialchars($data['hero_deskripsi']) ?></textarea>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Teks Tombol Aksi (CTA)</label>
                                <input type="text" name="hero_cta_teks" value="<?= htmlspecialchars($data['hero_cta_teks']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Tautan Tombol (URL)</label>
                                <input type="text" name="hero_cta_url" value="<?= htmlspecialchars($data['hero_cta_url']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500" placeholder="#spmb atau https://...">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">URL Gambar Background (Opsional)</label>
                            <input type="text" name="hero_bg_url" value="<?= htmlspecialchars($data['hero_bg_url']) ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500" placeholder="https://... (Kosongkan jika hanya ingin warna solid)">
                        </div>
                    </div>
                </div>

                <!-- BAGIAN USP (3 KOTAK KEUNGGULAN) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-100"><h2 class="font-bold text-amber-800"><i class="fas fa-th mr-2"></i>Seksi 3 Program Keunggulan (USP)</h2></div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                        
                        <!-- USP 1 -->
                        <div class="border border-emerald-200 rounded-lg p-4 bg-emerald-50/30">
                            <h3 class="font-bold text-emerald-700 mb-3 border-b border-emerald-100 pb-2">Kotak Kiri</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Ikon (Font Awesome)</label>
                                    <input type="text" name="usp1_ikon" value="<?= htmlspecialchars($data['usp1_ikon']) ?>" class="w-full px-3 py-1.5 border rounded mt-1 text-sm" placeholder="fas fa-leaf">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Judul</label>
                                    <input type="text" name="usp1_judul" value="<?= htmlspecialchars($data['usp1_judul']) ?>" class="w-full px-3 py-1.5 border rounded mt-1 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Deskripsi Singkat</label>
                                    <textarea name="usp1_deskripsi" rows="2" class="w-full px-3 py-1.5 border rounded mt-1 text-sm"><?= htmlspecialchars($data['usp1_deskripsi']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- USP 2 -->
                        <div class="border border-amber-200 rounded-lg p-4 bg-amber-50/30">
                            <h3 class="font-bold text-amber-700 mb-3 border-b border-amber-100 pb-2">Kotak Tengah</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Ikon (Font Awesome)</label>
                                    <input type="text" name="usp2_ikon" value="<?= htmlspecialchars($data['usp2_ikon']) ?>" class="w-full px-3 py-1.5 border rounded mt-1 text-sm" placeholder="fas fa-book-open">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Judul</label>
                                    <input type="text" name="usp2_judul" value="<?= htmlspecialchars($data['usp2_judul']) ?>" class="w-full px-3 py-1.5 border rounded mt-1 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Deskripsi Singkat</label>
                                    <textarea name="usp2_deskripsi" rows="2" class="w-full px-3 py-1.5 border rounded mt-1 text-sm"><?= htmlspecialchars($data['usp2_deskripsi']) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- USP 3 -->
                        <div class="border border-blue-200 rounded-lg p-4 bg-blue-50/30">
                            <h3 class="font-bold text-blue-700 mb-3 border-b border-blue-100 pb-2">Kotak Kanan</h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Ikon (Font Awesome)</label>
                                    <input type="text" name="usp3_ikon" value="<?= htmlspecialchars($data['usp3_ikon']) ?>" class="w-full px-3 py-1.5 border rounded mt-1 text-sm" placeholder="fas fa-apple-alt">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Judul</label>
                                    <input type="text" name="usp3_judul" value="<?= htmlspecialchars($data['usp3_judul']) ?>" class="w-full px-3 py-1.5 border rounded mt-1 text-sm">
                                </div>
                                <div>
                                    <label class="text-xs font-bold text-gray-700 block">Deskripsi Singkat</label>
                                    <textarea name="usp3_deskripsi" rows="2" class="w-full px-3 py-1.5 border rounded mt-1 text-sm"><?= htmlspecialchars($data['usp3_deskripsi']) ?></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                
                <div class="flex justify-end mb-10">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });
    </script>
</body>
</html>