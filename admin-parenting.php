<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Pastikan tabel ada
$conn->query("CREATE TABLE IF NOT EXISTS jadwal_parenting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATETIME NOT NULL,
    tema VARCHAR(255) NOT NULL,
    pemateri TEXT NOT NULL,
    lokasi VARCHAR(100) DEFAULT 'Online (Zoom)',
    gambar_url VARCHAR(255),
    status ENUM('Selesai', 'Akan Datang') DEFAULT 'Akan Datang'
)");

// Proses Hapus Jadwal
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM jadwal_parenting WHERE id = $hapus_id");
    $pesan_sukses = "Jadwal berhasil dihapus!";
}

// Proses Simpan Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $tema = $conn->real_escape_string($_POST['tema']);
    $pemateri = $conn->real_escape_string($_POST['pemateri']);
    $lokasi = $conn->real_escape_string($_POST['lokasi']);
    $status = $conn->real_escape_string($_POST['status']);
    $gambar_url = isset($_POST['gambar_url']) ? $conn->real_escape_string($_POST['gambar_url']) : '';
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $sql = "UPDATE jadwal_parenting SET tanggal='$tanggal', tema='$tema', pemateri='$pemateri', lokasi='$lokasi', status='$status', gambar_url='$gambar_url' WHERE id=$id";
    } else {
        $sql = "INSERT INTO jadwal_parenting (tanggal, tema, pemateri, lokasi, status, gambar_url) VALUES ('$tanggal', '$tema', '$pemateri', '$lokasi', '$status', '$gambar_url')";
    }
    
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = "Jadwal berhasil disimpan!";
    } else {
        $pesan_error = "Gagal menyimpan jadwal: " . $conn->error;
    }
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM jadwal_parenting WHERE id = $edit_id");
    if($res && $res->num_rows > 0) {
        $data_edit = $res->fetch_assoc();
    }
}

$active_menu = 'parenting';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Parenting | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Manajemen Jadwal Parenting School</h1>

            <?php if(isset($pesan_sukses)) { ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-sm"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div>
            <?php } ?>
            <?php if(isset($pesan_error)) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-sm"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div>
            <?php } ?>

            <!-- FORM JADWAL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <h2 class="font-bold text-gray-800 mb-4"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-calendar-plus' ?> mr-2"></i> <?= $edit_mode ? 'Edit Jadwal' : 'Tambah Jadwal Baru' ?></h2>
                <form action="admin-parenting.php" method="POST">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal & Waktu</label>
                            <input type="datetime-local" name="tanggal" value="<?= $edit_mode ? date('Y-m-d\TH:i', strtotime($data_edit['tanggal'])) : '' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tema / Materi</label>
                            <input type="text" name="tema" value="<?= $edit_mode ? htmlspecialchars($data_edit['tema']) : '' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500" placeholder="Cth: Seni Mendidik Generasi Z">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pemateri</label>
                            <textarea name="pemateri" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500" placeholder="Cth: Ustadz Budi Hafidzahullah&#10;Ustadz Fulan (Pisahkan dengan baris baru)"><?= $edit_mode ? htmlspecialchars($data_edit['pemateri']) : '' ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">URL Gambar (Opsional)</label>
                            <input type="text" name="gambar_url" value="<?= $edit_mode ? htmlspecialchars($data_edit['gambar_url'] ?? '') : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500" placeholder="https://...">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi / Platform</label>
                            <input type="text" name="lokasi" value="<?= $edit_mode ? htmlspecialchars($data_edit['lokasi']) : 'Online (Zoom)' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500">
                                <option value="Akan Datang" <?= ($edit_mode && $data_edit['status']=='Akan Datang')?'selected':'' ?>>Akan Datang</option>
                                <option value="Selesai" <?= ($edit_mode && $data_edit['status']=='Selesai')?'selected':'' ?>>Selesai</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex space-x-3">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-lg transition"><i class="fas fa-save mr-2"></i> Simpan Jadwal</button>
                        <?php if($edit_mode): ?>
                        <a href="admin-parenting.php" class="bg-gray-300 text-gray-800 hover:bg-gray-400 font-bold py-2 px-6 rounded-lg">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- TABEL JADWAL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Jadwal Parenting</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Waktu</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tema & Pemateri</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Lokasi</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <?php
                            $res = $conn->query("SELECT * FROM jadwal_parenting ORDER BY tanggal DESC");
                            if($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) {
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm whitespace-nowrap text-gray-900">
                                    <?= date('d M Y H:i', strtotime($row['tanggal'])) ?>
                                    <?php if(!empty($row['gambar_url'])): ?>
                                        <div class="mt-2"><img src="<?= htmlspecialchars($row['gambar_url']) ?>" class="w-16 h-12 object-cover rounded shadow-sm"></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4"><div class="text-sm font-bold text-gray-900"><?= htmlspecialchars($row['tema']) ?></div><div class="text-xs text-gray-500 whitespace-pre-line mt-1"><?= htmlspecialchars($row['pemateri']) ?></div></td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($row['lokasi']) ?></td>
                                <td class="px-6 py-4 text-sm"><?= ($row['status'] == 'Selesai') ? '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">Selesai</span>' : '<span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700">Akan Datang</span>' ?></td>
                                <td class="px-6 py-4 text-sm whitespace-nowrap"><a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></a><a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus jadwal ini?')" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></a></td>
                            </tr>
                            <?php } } else { echo "<tr><td colspan='5' class='px-6 py-8 text-center text-gray-500'>Belum ada jadwal.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>