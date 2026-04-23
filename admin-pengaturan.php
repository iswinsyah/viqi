<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat Tabel Jika Belum Ada
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_web (
    id INT PRIMARY KEY DEFAULT 1,
    nomor_wa VARCHAR(20),
    pesan_default TEXT
)");

// Insert default data jika masih kosong
$conn->query("INSERT IGNORE INTO pengaturan_web (id, nomor_wa, pesan_default) 
VALUES (1, '6281234567890', 'Assalamu\'alaikum Admin Villa Quran, saya ingin bertanya seputar pendaftaran santri baru.')");

// Injeksi penambahan kolom (Silent error kalau sudah ada)
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN nama_sekolah VARCHAR(150) AFTER id");
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN deskripsi_footer TEXT AFTER nama_sekolah");
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN alamat TEXT AFTER deskripsi_footer");
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN telepon VARCHAR(50) AFTER alamat");
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN link_portal VARCHAR(255)");
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN link_mutabaah VARCHAR(255)");
$conn->query("ALTER TABLE pengaturan_web ADD COLUMN logo_url VARCHAR(255) AFTER nama_sekolah");

// 2. Proses Simpan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $wa = $conn->real_escape_string($_POST['nomor_wa']);
    $nama = $conn->real_escape_string($_POST['nama_sekolah'] ?? 'Villa Quran Indonesia');
    $logo = $conn->real_escape_string($_POST['logo_url'] ?? '');
    $desc = $conn->real_escape_string($_POST['deskripsi_footer'] ?? '');
    $alamat = $conn->real_escape_string($_POST['alamat'] ?? '');
    $telepon = $conn->real_escape_string($_POST['telepon'] ?? '');
    $portal = $conn->real_escape_string($_POST['link_portal'] ?? '');
    $mutabaah = $conn->real_escape_string($_POST['link_mutabaah'] ?? '');
    $pesan = $conn->real_escape_string($_POST['pesan_default']);
    
    // Pembersihan format WA otomatis
    $wa = preg_replace('/[^0-9]/', '', $wa); // Hapus selain angka (spasi, strip, dll)
    if(substr($wa, 0, 1) == '0') $wa = '62' . substr($wa, 1); // Ganti awalan 0 ke 62
    if(substr($wa, 0, 3) == '+62') $wa = '62' . substr($wa, 3); // Ganti awalan +62 ke 62
    
    $sql = "UPDATE pengaturan_web SET 
            nama_sekolah='$nama', logo_url='$logo', deskripsi_footer='$desc', alamat='$alamat', telepon='$telepon',
            link_portal='$portal', link_mutabaah='$mutabaah',
            nomor_wa='$wa', pesan_default='$pesan' WHERE id=1";
            
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = "Pengaturan Website berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui: " . $conn->error;
    }
}

// 3. Ambil data
$res = $conn->query("SELECT * FROM pengaturan_web WHERE id=1");
$data = $res->fetch_assoc();
$active_menu = 'pengaturan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Web | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium flex items-center">
                <i class="fas fa-external-link-alt mr-2"></i> Lihat Web
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-cog text-gray-600 mr-2"></i>Pengaturan Website</h1>
                <p class="text-gray-500">Atur nomor kontak Customer Service dan konfigurasi dasar website lainnya.</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-red-100 text-red-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 max-w-4xl">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800"><i class="fas fa-edit text-blue-500 mr-2"></i>Form Identitas & Footer</h2></div>
                <div class="p-6">
                    <form action="" method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                            <!-- Kolom Kiri: Identitas -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Sekolah / Lembaga</label>
                                    <input type="text" name="nama_sekolah" value="<?= htmlspecialchars($data['nama_sekolah'] ?? 'Villa Quran Indonesia') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">URL Logo Sekolah</label>
                                <input type="text" name="logo_url" value="<?= htmlspecialchars($data['logo_url'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="https://contoh.com/logo.png">
                            </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi Singkat Footer</label>
                                    <textarea name="deskripsi_footer" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($data['deskripsi_footer'] ?? '') ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Lengkap</label>
                                    <textarea name="alamat" rows="2" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($data['alamat'] ?? '') ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nomor Telepon Kantor</label>
                                    <input type="text" name="telepon" value="<?= htmlspecialchars($data['telepon'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="(021) 1234-5678">
                                </div>
                            </div>
                            
                            <!-- Kolom Kanan: Link & WA -->
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Link Portal Orang Tua</label>
                                    <input type="text" name="link_portal" value="<?= htmlspecialchars($data['link_portal'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="https://...">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Link Laporan Mutaba'ah</label>
                                    <input type="text" name="link_mutabaah" value="<?= htmlspecialchars($data['link_mutabaah'] ?? '') ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="https://...">
                                </div>
                                <hr class="my-4 border-gray-200">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1"><i class="fab fa-whatsapp text-green-500 mr-1"></i> Nomor WhatsApp CS</label>
                                    <input type="text" name="nomor_wa" value="<?= htmlspecialchars($data['nomor_wa'] ?? '') ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="0812...">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Teks Sapaan WA Default</label>
                                    <textarea name="pesan_default" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($data['pesan_default'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-8 rounded-lg shadow-md transition flex items-center">
                                <i class="fas fa-save mr-2"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Area Petunjuk Scan Barcode -->
            <div class="bg-blue-50 rounded-xl border border-blue-100 p-6 max-w-3xl text-sm text-blue-800">
                <h3 class="font-bold mb-2"><i class="fas fa-lightbulb mr-2"></i> Tips Cepat:</h3>
                <p>Nomor CS yang dimasukkan di atas akan langsung mengubah semua tombol "Hubungi WhatsApp" serta nomor di area kaki (Footer) seluruh halaman website Anda secara otomatis (*real-time*).</p>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });
    </script>
</body>
</html>