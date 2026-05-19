<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS buku_mutabaah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_santri VARCHAR(150),
    kelas VARCHAR(50),
    tanggal DATE,
    shalat_berjamaah INT DEFAULT 0,
    tilawah_harian INT DEFAULT 0,
    ziyadah_hafalan INT DEFAULT 0,
    murajaah INT DEFAULT 0,
    catatan_musyrif TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM buku_mutabaah WHERE id = $id");
    header("Location: admin-pegawai-mutabaah.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_santri = $conn->real_escape_string($_POST['nama_santri']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $shalat = isset($_POST['shalat_berjamaah']) ? 1 : 0;
    $tilawah = isset($_POST['tilawah_harian']) ? 1 : 0;
    $ziyadah = isset($_POST['ziyadah_hafalan']) ? 1 : 0;
    $murajaah = isset($_POST['murajaah']) ? 1 : 0;
    $catatan = $conn->real_escape_string($_POST['catatan_musyrif']);

    if ($id > 0) {
        $sql = "UPDATE buku_mutabaah SET nama_santri='$nama_santri', kelas='$kelas', tanggal='$tanggal', shalat_berjamaah=$shalat, tilawah_harian=$tilawah, ziyadah_hafalan=$ziyadah, murajaah=$murajaah, catatan_musyrif='$catatan' WHERE id=$id";
        $pesan_sukses = "Data Mutaba'ah berhasil diupdate!";
    } else {
        $sql = "INSERT INTO buku_mutabaah (nama_santri, kelas, tanggal, shalat_berjamaah, tilawah_harian, ziyadah_hafalan, murajaah, catatan_musyrif) VALUES ('$nama_santri', '$kelas', '$tanggal', $shalat, $tilawah, $ziyadah, $murajaah, '$catatan')";
        $pesan_sukses = "Data Mutaba'ah harian berhasil disimpan!";
    }
    $conn->query($sql);
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM buku_mutabaah WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'mutabaah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Mutaba'ah Santri | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-clipboard-list text-emerald-600 mr-2"></i>Buku Mutaba'ah Santri</h1>
                <p class="text-gray-500 mt-1">Catat aktivitas ibadah harian dan hafalan santri di sini.</p>
            </div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100"><h2 class="font-bold text-emerald-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Mutaba\'ah' : 'Input Mutaba\'ah Baru' ?></h2></div>
                <form action="admin-pegawai-mutabaah.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kegiatan</label>
                            <input type="date" name="tanggal" value="<?= $edit_mode ? $data_edit['tanggal'] : date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label>
                            <input type="text" name="nama_santri" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_santri']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500" placeholder="Contoh: Ahmad">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas Asrama</label>
                            <select name="kelas" required class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500">
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $daftar_kelas = ['Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12', 'Kelas Rijal', 'Kelas Nisa'];
                                $kelas_tersimpan = $edit_mode ? $data_edit['kelas'] : '';
                                foreach ($daftar_kelas as $nama_kelas) {
                                    $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                    echo "<option value=\"$nama_kelas\" $sel>$nama_kelas</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 class="font-bold text-gray-800 mb-4 text-sm uppercase tracking-wider">Ceklis Ibadah & Hafalan</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="shalat_berjamaah" value="1" <?= ($edit_mode && $data_edit['shalat_berjamaah'] == 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-700">Shalat 5 Waktu Berjamaah</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="tilawah_harian" value="1" <?= ($edit_mode && $data_edit['tilawah_harian'] == 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-700">Tilawah 1 Juz / Hari</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="ziyadah_hafalan" value="1" <?= ($edit_mode && $data_edit['ziyadah_hafalan'] == 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-700">Ziyadah (Tambah Hafalan)</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" name="murajaah" value="1" <?= ($edit_mode && $data_edit['murajaah'] == 1) ? 'checked' : '' ?> class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                                <span class="text-sm font-medium text-gray-700">Muraja'ah (Mengulang)</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Musyrif / Pesan untuk Orang Tua</label>
                        <textarea name="catatan_musyrif" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-emerald-500" placeholder="Alhamdulillah ananda hari ini semangat menghafal..."><?= $edit_mode ? htmlspecialchars($data_edit['catatan_musyrif']) : '' ?></textarea>
                    </div>

                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-pegawai-mutabaah.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Data' : 'Simpan Mutaba\'ah' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Mutaba'ah Terbaru</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri & Tanggal</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Shalat</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Tilawah</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Ziyadah</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Muraja'ah</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM buku_mutabaah ORDER BY tanggal DESC, id DESC LIMIT 50");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_santri']) ?></div>
                                        <div class="text-xs text-gray-500"><?= date('d M Y', strtotime($row['tanggal'])) ?> &bull; <?= htmlspecialchars($row['kelas']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center"><?= $row['shalat_berjamaah'] ? '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>' : '<i class="fas fa-times-circle text-red-400 text-lg"></i>' ?></td>
                                    <td class="px-4 py-3 text-center"><?= $row['tilawah_harian'] ? '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>' : '<i class="fas fa-times-circle text-red-400 text-lg"></i>' ?></td>
                                    <td class="px-4 py-3 text-center"><?= $row['ziyadah_hafalan'] ? '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>' : '<i class="fas fa-times-circle text-red-400 text-lg"></i>' ?></td>
                                    <td class="px-4 py-3 text-center"><?= $row['murajaah'] ? '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>' : '<i class="fas fa-times-circle text-red-400 text-lg"></i>' ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus data ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='6' class='text-center py-6 text-gray-500 italic'>Belum ada rekapan mutaba'ah.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>