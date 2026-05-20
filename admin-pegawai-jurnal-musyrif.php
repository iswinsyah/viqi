<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$ustadz_id = $_SESSION['ustadz_id'];

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_kegiatan_musyrif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    tanggal DATE NOT NULL,
    kategori_kegiatan VARCHAR(100),
    deskripsi_kegiatan TEXT,
    santri_terlibat TEXT,
    tindak_lanjut TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    // Hanya bisa hapus entri milik sendiri
    $conn->query("DELETE FROM jurnal_kegiatan_musyrif WHERE id = $id AND ustadz_id = $ustadz_id");
    header("Location: admin-pegawai-jurnal-musyrif.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $kategori = $conn->real_escape_string($_POST['kategori_kegiatan']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi_kegiatan']);
    $santri = $conn->real_escape_string($_POST['santri_terlibat']);
    $tindak_lanjut = $conn->real_escape_string($_POST['tindak_lanjut']);

    if ($id > 0) {
        // Hanya bisa update entri milik sendiri
        $sql = "UPDATE jurnal_kegiatan_musyrif SET tanggal='$tanggal', kategori_kegiatan='$kategori', deskripsi_kegiatan='$deskripsi', santri_terlibat='$santri', tindak_lanjut='$tindak_lanjut' WHERE id=$id AND ustadz_id = $ustadz_id";
        $pesan_sukses = "Jurnal kegiatan berhasil diupdate!";
    } else {
        $sql = "INSERT INTO jurnal_kegiatan_musyrif (ustadz_id, tanggal, kategori_kegiatan, deskripsi_kegiatan, santri_terlibat, tindak_lanjut) VALUES ($ustadz_id, '$tanggal', '$kategori', '$deskripsi', '$santri', '$tindak_lanjut')";
        $pesan_sukses = "Jurnal kegiatan baru berhasil disimpan!";
    }
    $conn->query($sql);
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM jurnal_kegiatan_musyrif WHERE id = $id AND ustadz_id = $ustadz_id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'jurnal_musyrif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Kegiatan Musyrif | Ruang Asatidz</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-shield text-cyan-600 mr-2"></i>Jurnal Kegiatan Musyrif</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Jurnal' : 'Catat Kegiatan Baru' ?></h2></div>
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="<?= $edit_mode ? $data_edit['tanggal'] : date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori Kegiatan</label>
                            <select name="kategori_kegiatan" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <?php $kategori_opsi = ['Pembinaan Akhlak', 'Problem Solving', 'Inspeksi Kebersihan', 'Sesi Motivasi', 'Lainnya']; foreach($kategori_opsi as $k) { $sel = ($edit_mode && $data_edit['kategori_kegiatan'] == $k) ? 'selected' : ''; echo "<option value='$k' $sel>$k</option>"; } ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Kegiatan</label>
                        <textarea name="deskripsi_kegiatan" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Jelaskan kegiatan yang dilakukan..."><?= $edit_mode ? htmlspecialchars($data_edit['deskripsi_kegiatan']) : '' ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Santri Terlibat (Opsional)</label>
                            <input type="text" name="santri_terlibat" value="<?= $edit_mode ? htmlspecialchars($data_edit['santri_terlibat']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Ahmad, Budi, Fulan">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Hasil / Tindak Lanjut</label>
                            <input type="text" name="tindak_lanjut" value="<?= $edit_mode ? htmlspecialchars($data_edit['tindak_lanjut']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Masalah selesai, perlu dipanggil orang tua, dll.">
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-pegawai-jurnal-musyrif.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Jurnal' : 'Simpan Jurnal' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Jurnal Kegiatan Anda</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal & Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi & Hasil</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM jurnal_kegiatan_musyrif WHERE ustadz_id = $ustadz_id ORDER BY tanggal DESC, id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-gray-900 whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                        <div class="text-sm text-cyan-700 font-semibold mt-1"><?= htmlspecialchars($row['kategori_kegiatan']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($row['deskripsi_kegiatan']) ?></div>
                                        <?php if(!empty($row['santri_terlibat'])): ?><div class="text-xs text-gray-500 mt-1"><i class="fas fa-users mr-1"></i><b>Santri:</b> <?= htmlspecialchars($row['santri_terlibat']) ?></div><?php endif; ?>
                                        <?php if(!empty($row['tindak_lanjut'])): ?><div class="text-xs text-emerald-600 mt-1"><i class="fas fa-check-double mr-1"></i><b>Hasil:</b> <?= htmlspecialchars($row['tindak_lanjut']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center align-top">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus jurnal ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500 italic'>Belum ada catatan jurnal kegiatan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>