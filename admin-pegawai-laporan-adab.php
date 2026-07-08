<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$ustadz_id = $_SESSION['ustadz_id'];

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS laporan_adab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    tanggal DATE NOT NULL,
    nama_santri VARCHAR(150),
    kelas VARCHAR(50),
    jenis_laporan ENUM('Pelanggaran', 'Apresiasi'),
    deskripsi_kejadian TEXT,
    tindakan_diambil TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM laporan_adab WHERE id = $id AND ustadz_id = $ustadz_id");
    header("Location: admin-pegawai-laporan-adab.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $nama_santri = $conn->real_escape_string($_POST['nama_santri']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $jenis_laporan = $conn->real_escape_string($_POST['jenis_laporan']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi_kejadian']);
    $tindakan = $conn->real_escape_string($_POST['tindakan_diambil']);

    if ($id > 0) {
        $sql = "UPDATE laporan_adab SET tanggal='$tanggal', nama_santri='$nama_santri', kelas='$kelas', jenis_laporan='$jenis_laporan', deskripsi_kejadian='$deskripsi', tindakan_diambil='$tindakan' WHERE id=$id AND ustadz_id = $ustadz_id";
        $pesan_sukses = "Laporan berhasil diupdate!";
    } else {
        $sql = "INSERT INTO laporan_adab (ustadz_id, tanggal, nama_santri, kelas, jenis_laporan, deskripsi_kejadian, tindakan_diambil) VALUES ($ustadz_id, '$tanggal', '$nama_santri', '$kelas', '$jenis_laporan', '$deskripsi', '$tindakan')";
        $pesan_sukses = "Laporan baru berhasil disimpan!";
    }
    $conn->query($sql);
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM laporan_adab WHERE id = $id AND ustadz_id = $ustadz_id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'laporan_adab';

// Ambil daftar kelas dari Master Kelas (Ruang Yayasan)
$daftar_kelas = [];
$res_kelas = $conn->query("SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kelas && $res_kelas->num_rows > 0) {
    while($row = $res_kelas->fetch_assoc()) {
        $daftar_kelas[] = $row['nama_kelas'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Kedisiplinan & Adab | Ruang Asatidz</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-balance-scale text-cyan-600 mr-2"></i>Laporan Kedisiplinan & Adab Santri</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Laporan' : 'Buat Laporan Baru' ?></h2></div>
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-4">
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kejadian</label><input type="date" name="tanggal" value="<?= $edit_mode ? $data_edit['tanggal'] : date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label><input type="text" name="nama_santri" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_santri']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Nama santri terkait"></div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                            <select name="kelas" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white">
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $kelas_tersimpan = $edit_mode ? $data_edit['kelas'] : '';
                                $kelas_ada = false;
                                foreach ($daftar_kelas as $nama_kelas) {
                                    $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                    if ($sel) $kelas_ada = true;
                                    echo "<option value=\"".htmlspecialchars($nama_kelas)."\" $sel>".htmlspecialchars($nama_kelas)."</option>";
                                }
                                if ($edit_mode && !$kelas_ada && !empty($kelas_tersimpan)) {
                                    echo "<option value=\"".htmlspecialchars($kelas_tersimpan)."\" selected>".htmlspecialchars($kelas_tersimpan)." (Data Lama)</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Laporan</label>
                            <select name="jenis_laporan" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <option value="Pelanggaran" <?= ($edit_mode && $data_edit['jenis_laporan'] == 'Pelanggaran') ? 'selected' : '' ?>>Pelanggaran</option>
                                <option value="Apresiasi" <?= ($edit_mode && $data_edit['jenis_laporan'] == 'Apresiasi') ? 'selected' : '' ?>>Apresiasi</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Kejadian</label>
                        <textarea name="deskripsi_kejadian" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Jelaskan kejadian secara singkat dan jelas..."><?= $edit_mode ? htmlspecialchars($data_edit['deskripsi_kejadian']) : '' ?></textarea>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tindakan yang Diambil</label>
                        <input type="text" name="tindakan_diambil" value="<?= $edit_mode ? htmlspecialchars($data_edit['tindakan_diambil']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Diberi nasihat, Dihukum membersihkan kamar mandi, Diberi pujian, dll.">
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-pegawai-laporan-adab.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Laporan' : 'Simpan Laporan' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Laporan Anda</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri & Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi & Tindakan</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM laporan_adab WHERE ustadz_id = $ustadz_id ORDER BY tanggal DESC, id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $badge_class = $row['jenis_laporan'] == 'Apresiasi' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_santri']) ?></div>
                                        <div class="text-xs text-gray-500 mt-1"><?= date('d M Y', strtotime($row['tanggal'])) ?> &bull; <?= htmlspecialchars($row['kelas']) ?></div>
                                        <div class="mt-2"><span class="px-2 py-1 text-xs font-bold rounded-full <?= $badge_class ?>"><?= htmlspecialchars($row['jenis_laporan']) ?></span></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($row['deskripsi_kejadian']) ?></div>
                                        <?php if(!empty($row['tindakan_diambil'])): ?><div class="text-xs text-blue-600 mt-1"><i class="fas fa-gavel mr-1"></i><b>Tindakan:</b> <?= htmlspecialchars($row['tindakan_diambil']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center align-top">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus laporan ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500 italic'>Belum ada catatan laporan kedisiplinan.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>