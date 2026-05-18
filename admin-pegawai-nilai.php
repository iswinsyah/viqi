<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS bank_nilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_santri VARCHAR(150),
    kelas VARCHAR(50),
    mata_pelajaran VARCHAR(100),
    jenis_ujian VARCHAR(50),
    nilai INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM bank_nilai WHERE id = $id");
    header("Location: admin-pegawai-nilai.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_santri = $conn->real_escape_string($_POST['nama_santri']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $mata_pelajaran = $conn->real_escape_string($_POST['mata_pelajaran']);
    $jenis_ujian = $conn->real_escape_string($_POST['jenis_ujian']);
    $nilai = (int)$_POST['nilai'];

    if ($id > 0) {
        $sql = "UPDATE bank_nilai SET nama_santri='$nama_santri', kelas='$kelas', mata_pelajaran='$mata_pelajaran', jenis_ujian='$jenis_ujian', nilai=$nilai WHERE id=$id";
        $pesan_sukses = "Data nilai berhasil diupdate!";
    } else {
        $sql = "INSERT INTO bank_nilai (nama_santri, kelas, mata_pelajaran, jenis_ujian, nilai) VALUES ('$nama_santri', '$kelas', '$mata_pelajaran', '$jenis_ujian', $nilai)";
        $pesan_sukses = "Data nilai baru berhasil dimasukkan!";
    }
    $conn->query($sql);
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM bank_nilai WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'bank_nilai';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Nilai | Portal Ustadz</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-star-half-alt text-amber-500 mr-2"></i>Bank Nilai Akademik</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Data Nilai' : 'Input Nilai Baru' ?></h2></div>
                <form action="admin-pegawai-nilai.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label>
                            <input type="text" name="nama_santri" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_santri']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas</label>
                            <select name="kelas" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $daftar_kelas = [
                                    'Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12',
                                    'Kelas A', 'Kelas B', 'Kelas C',
                                    'Kelas Rijal', 'Kelas Nisa'
                                ];
                                $kelas_tersimpan = $edit_mode ? $data_edit['kelas'] : '';
                                $ada_di_list = false;

                                foreach ($daftar_kelas as $nama_kelas) {
                                    $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                    if ($sel) $ada_di_list = true;
                                    echo "<option value=\"$nama_kelas\" $sel>$nama_kelas</option>";
                                }
                                
                                // Jaga-jaga jika data lama diketik manual dan tidak ada di daftar kombinasi baru
                                if ($edit_mode && !$ada_di_list && !empty($kelas_tersimpan)) {
                                    echo "<option value=\"".htmlspecialchars($kelas_tersimpan)."\" selected>".htmlspecialchars($kelas_tersimpan)." (Data Lama)</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" value="<?= $edit_mode ? htmlspecialchars($data_edit['mata_pelajaran']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Ujian/Tugas</label>
                            <select name="jenis_ujian" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <?php $jenis = ['Tugas Harian', 'Ulangan Harian (UH)', 'Ujian Tengah Semester (UTS)', 'Ujian Akhir Semester (UAS)', 'Praktek']; foreach($jenis as $j) { $sel = ($edit_mode && $data_edit['jenis_ujian'] == $j) ? 'selected' : ''; echo "<option value='$j' $sel>$j</option>"; } ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nilai Angka (0-100)</label>
                            <input type="number" name="nilai" min="0" max="100" value="<?= $edit_mode ? htmlspecialchars($data_edit['nilai']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 text-xl font-bold text-center">
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-pegawai-nilai.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Nilai' : 'Simpan Nilai' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Data Rekapitulasi Nilai</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Santri & Kelas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Mata Pelajaran</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori Ujian</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Nilai</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM bank_nilai ORDER BY id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $warna_nilai = $row['nilai'] >= 75 ? 'text-emerald-600' : 'text-red-500';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_santri']) ?></div><div class="text-xs text-gray-500">Kelas: <?= htmlspecialchars($row['kelas']) ?></div></td>
                                    <td class="px-4 py-3 text-sm text-cyan-700 font-medium"><?= htmlspecialchars($row['mata_pelajaran']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['jenis_ujian']) ?></td>
                                    <td class="px-4 py-3 text-center font-extrabold text-lg <?= $warna_nilai ?>"><?= htmlspecialchars($row['nilai']) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus data nilai ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='5' class='text-center py-6 text-gray-500 italic'>Belum ada rekapan nilai.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>