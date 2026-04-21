<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS pengajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150),
    jabatan VARCHAR(100),
    gambar_url VARCHAR(255),
    ikon_badge VARCHAR(50),
    teks_badge VARCHAR(100),
    almamater VARCHAR(255),
    spesialisasi VARCHAR(255),
    bidang_mengajar VARCHAR(255),
    prestasi TEXT
)");

// 2. Insert data dummy jika tabel masih kosong (untuk menjaga tampilan awal)
$cek = $conn->query("SELECT COUNT(*) as tot FROM pengajar");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO pengajar (nama, jabatan, gambar_url, ikon_badge, teks_badge, almamater, spesialisasi, bidang_mengajar, prestasi) VALUES 
    ('Ust. Abdullah, Lc.', 'Kepala Tahfidz', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80', 'fas fa-certificate', 'Hafidz 30 Juz Bersanad', 'Universitas Al-Azhar Kairo, Mesir (Lulus Tahun 2018)', 'Tahsin, Ilmu Tajwid, dan Qiro\\'at Sab\\'ah', 'Ziyadah & Muraja\\'ah Hafalan Al-Quran Utama', 'Pemegang Sanad Muttashil Hafalan 30 Juz ke-32, Juara 1 MHQ Nasional 2019.'),
    ('Ust. Abdurrahman, M.Pd.', 'Musyrif Asrama', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80', 'fas fa-book-reader', 'Pakar Adab & Karakter', 'Universitas Islam Madinah & S2 UPI (Lulus Tahun 2020)', 'Pendidikan Karakter (Character Building) & Psikologi Anak', 'Adab Harian Santri, Bahasa Arab, dan Tsaqofah Islam.', 'Penulis buku best-seller \"Mendidik Anak Generasi Alpha dengan Sunnah\".'),
    ('Bpk. Fulan Pratama, S.Kom.', 'Guru Digitalpreneur', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80', 'fas fa-laptop-code', 'AI & Programming', 'Fasilkom Universitas Indonesia (Lulus Tahun 2019)', 'Pemrograman Web, Desain UI/UX, dan Praktik AI', 'Kelas Digitalpreneur dan Life Skills Abad 21.', 'Mantan Tech Lead di Startup Nasional, mendedikasikan diri untuk mencetak santri melek teknologi.'),
    ('Dr. Zaid, Sp.GK.', 'Konsultan Gizi', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80', 'fas fa-apple-alt', 'Brain Food Expert', 'Fakultas Kedokteran Universitas Indonesia', 'Gizi Klinik Anak dan Remaja', 'Edukasi Pola Makan Sehat Santri', 'Merancang menu khusus untuk meningkatkan daya ingat santri dan menghindari kantuk berlebih.')");
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM pengajar WHERE id = $id");
    header("Location: admin-pengajar.php");
    exit;
}

// 4. Proses Simpan/Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = $conn->real_escape_string($_POST['nama']);
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    $gambar_url = $conn->real_escape_string($_POST['gambar_url']);
    $ikon_badge = $conn->real_escape_string($_POST['ikon_badge']);
    $teks_badge = $conn->real_escape_string($_POST['teks_badge']);
    $almamater = $conn->real_escape_string($_POST['almamater']);
    $spesialisasi = $conn->real_escape_string($_POST['spesialisasi']);
    $bidang_mengajar = $conn->real_escape_string($_POST['bidang_mengajar']);
    $prestasi = $conn->real_escape_string($_POST['prestasi']);

    if ($id > 0) {
        $sql = "UPDATE pengajar SET nama='$nama', jabatan='$jabatan', gambar_url='$gambar_url', ikon_badge='$ikon_badge', teks_badge='$teks_badge', almamater='$almamater', spesialisasi='$spesialisasi', bidang_mengajar='$bidang_mengajar', prestasi='$prestasi' WHERE id=$id";
        $pesan_sukses = "Data pengajar berhasil diupdate!";
    } else {
        $sql = "INSERT INTO pengajar (nama, jabatan, gambar_url, ikon_badge, teks_badge, almamater, spesialisasi, bidang_mengajar, prestasi) VALUES ('$nama', '$jabatan', '$gambar_url', '$ikon_badge', '$teks_badge', '$almamater', '$spesialisasi', '$bidang_mengajar', '$prestasi')";
        $pesan_sukses = "Pengajar baru berhasil ditambahkan!";
    }
    $conn->query($sql);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM pengajar WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'pengajar';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengajar | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="profil-pengajar.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Web</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chalkboard-teacher text-purple-600 mr-2"></i>Manajemen Pengajar</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM INPUT -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-100"><h2 class="font-bold text-purple-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Data Pengajar' : 'Tambah Pengajar Baru' ?></h2></div>
                <form action="admin-pengajar.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">Informasi Dasar (Tampil di Beranda)</h3>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap & Gelar</label><input type="text" name="nama" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama']) : '' ?>" required class="w-full px-3 py-2 border rounded-lg focus:ring-purple-500"></div>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">Jabatan (Contoh: Kepala Tahfidz)</label><input type="text" name="jabatan" value="<?= $edit_mode ? htmlspecialchars($data_edit['jabatan']) : '' ?>" required class="w-full px-3 py-2 border rounded-lg focus:ring-purple-500"></div>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">URL Gambar Foto</label><input type="text" name="gambar_url" value="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url']) : '' ?>" required class="w-full px-3 py-2 border rounded-lg focus:ring-purple-500" placeholder="https://..."></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Ikon (FontAwesome)</label><input type="text" name="ikon_badge" value="<?= $edit_mode ? htmlspecialchars($data_edit['ikon_badge']) : '' ?>" class="w-full px-3 py-2 border rounded-lg" placeholder="fas fa-star"></div>
                                <div><label class="block text-xs font-bold text-gray-700 mb-1">Teks Ikon (Sorotan)</label><input type="text" name="teks_badge" value="<?= $edit_mode ? htmlspecialchars($data_edit['teks_badge']) : '' ?>" class="w-full px-3 py-2 border rounded-lg" placeholder="Hafidz 30 Juz"></div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">Detail Lengkap (Tampil di Halaman Profil)</h3>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">Almamater / Lulusan</label><input type="text" name="almamater" value="<?= $edit_mode ? htmlspecialchars($data_edit['almamater']) : '' ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">Spesialisasi Ilmu</label><input type="text" name="spesialisasi" value="<?= $edit_mode ? htmlspecialchars($data_edit['spesialisasi']) : '' ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">Bidang Mengajar</label><input type="text" name="bidang_mengajar" value="<?= $edit_mode ? htmlspecialchars($data_edit['bidang_mengajar']) : '' ?>" class="w-full px-3 py-2 border rounded-lg"></div>
                            <div><label class="block text-xs font-bold text-gray-700 mb-1">Prestasi / Pengalaman</label><textarea name="prestasi" rows="2" class="w-full px-3 py-2 border rounded-lg"><?= $edit_mode ? htmlspecialchars($data_edit['prestasi']) : '' ?></textarea></div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <?php if($edit_mode) echo '<a href="admin-pengajar.php" class="bg-gray-200 px-6 py-2 rounded-lg font-bold">Batal</a>'; ?>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update Data' : 'Simpan Pengajar' ?></button>
                    </div>
                </form>
            </div>

            <!-- TABEL LIST PENGAJAR -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Pengajar Saat Ini</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Foto</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama & Jabatan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Spesialisasi</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM pengajar ORDER BY id ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr>
                                    <td class="px-4 py-3"><img src="<?= htmlspecialchars($row['gambar_url']) ?>" class="w-12 h-12 rounded-full object-cover"></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama']) ?></div>
                                        <div class="text-xs text-purple-600"><?= htmlspecialchars($row['jabatan']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['spesialisasi']) ?></td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus pengajar ini?')" class="text-rose-600 hover:text-rose-900"><i class="fas fa-trash"></i> Hapus</a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500'>Belum ada data pengajar.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });
    </script>
</body>
</html>