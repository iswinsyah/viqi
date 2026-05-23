<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
 
// Dapatkan ID ustadz yang sedang login
$ustadz_id = $_SESSION['ustadz_id'];

// --- DATA PREPARATION FOR FORMS ---
// 1. Ambil daftar santri aktif
$santri_list = [];
$res_santri = $conn->query("SELECT id, nama_lengkap FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
if ($res_santri) {
    while($row = $res_santri->fetch_assoc()) {
        $santri_list[] = $row;
    }
}

// 2. Ambil daftar mata pelajaran, dikelompokkan berdasarkan kategori
$mapel_list = [];
$res_mapel = $conn->query("SELECT id, nama_mapel, kategori_mapel FROM master_mapel ORDER BY kategori_mapel, nama_mapel ASC");
if ($res_mapel) {
    while($row = $res_mapel->fetch_assoc()) {
        $mapel_list[$row['kategori_mapel']][] = $row;
    }
}

// --- CRUD OPERATIONS ---

// 3. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    // Tambahkan pengecekan ustadz_id untuk keamanan, kecuali untuk super admin
    if ($_SESSION['ustadz_role'] === 'super_admin') {
        $stmt = $conn->prepare("DELETE FROM leger_nilai WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("DELETE FROM leger_nilai WHERE id = ? AND ustadz_id = ?");
        $stmt->bind_param("ii", $id, $ustadz_id);
    }
    $stmt->execute();
    header("Location: admin-pegawai-nilai.php");
    exit;
}

// 4. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $santri_id = (int)$_POST['santri_id'];
    $mapel_id = (int)$_POST['mapel_id'];
    $tahun_ajaran = $conn->real_escape_string($_POST['tahun_ajaran']);
    $semester = $conn->real_escape_string($_POST['semester']);
    $jenis_ujian = $conn->real_escape_string($_POST['jenis_ujian']);
    $nilai = (int)$_POST['nilai'];

    // Ambil kelas santri dari database untuk konsistensi
    $kelas_santri = '';
    $res_kelas = $conn->query("SELECT kelas_sekarang FROM buku_induk_santri WHERE id = $santri_id");
    if ($res_kelas) $kelas_santri = $res_kelas->fetch_assoc()['kelas_sekarang'];

    if ($id > 0) {
        // Update data
        $stmt = $conn->prepare("UPDATE leger_nilai SET santri_id=?, mapel_id=?, kelas=?, tahun_ajaran=?, semester=?, jenis_ujian=?, nilai=? WHERE id=?");
        $stmt->bind_param("iisssisi", $santri_id, $mapel_id, $kelas_santri, $tahun_ajaran, $semester, $jenis_ujian, $nilai, $id);
        $pesan_sukses = "Data nilai berhasil diupdate!";
    } else {
        // Insert data baru
        $stmt = $conn->prepare("INSERT INTO leger_nilai (santri_id, mapel_id, kelas, tahun_ajaran, semester, jenis_ujian, nilai, ustadz_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssisi", $santri_id, $mapel_id, $kelas_santri, $tahun_ajaran, $semester, $jenis_ujian, $nilai, $ustadz_id);
        $pesan_sukses = "Data nilai baru berhasil dimasukkan!";
    }
    $stmt->execute();
    $stmt->close();
}

// 5. Ambil data untuk mode Edit
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    // Ambil data (super admin bisa edit semua)
    if ($_SESSION['ustadz_role'] === 'super_admin') {
        $stmt = $conn->prepare("SELECT * FROM leger_nilai WHERE id = ?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("SELECT * FROM leger_nilai WHERE id = ? AND ustadz_id = ?");
        $stmt->bind_param("ii", $id, $ustadz_id);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $data_edit = $res->fetch_assoc();
    $stmt->close();
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-star-half-alt text-amber-500 mr-2"></i>Bank Nilai Akademik & Diniyah</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Data Nilai' : 'Input Nilai Baru' ?></h2></div>
                <form action="admin-pegawai-nilai.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label>
                            <select name="santri_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white">
                                <option value="">-- Pilih Santri --</option>
                                <?php foreach ($santri_list as $santri): ?>
                                    <option value="<?= $santri['id'] ?>" <?= ($edit_mode && $data_edit['santri_id'] == $santri['id']) ? 'selected' : '' ?>><?= htmlspecialchars($santri['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                            <select name="mapel_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white">
                                <option value="">-- Pilih Mata Pelajaran --</option>
                                <?php foreach ($mapel_list as $kategori => $mapels): ?>
                                    <optgroup label="<?= htmlspecialchars($kategori) ?>">
                                        <?php foreach ($mapels as $mapel): ?>
                                            <option value="<?= $mapel['id'] ?>" <?= ($edit_mode && $data_edit['mapel_id'] == $mapel['id']) ? 'selected' : '' ?>><?= htmlspecialchars($mapel['nama_mapel']) ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Ajaran</label>
                            <select name="tahun_ajaran" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white">
                                <?php 
                                $current_year = date('Y');
                                for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
                                    $ta = $i . '/' . ($i + 1);
                                    $selected = ($edit_mode && $data_edit['tahun_ajaran'] == $ta) ? 'selected' : (!$edit_mode && $i == $current_year ? 'selected' : '');
                                    echo "<option value='$ta' $selected>$ta</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                            <select name="semester" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white">
                                <option value="Ganjil" <?= ($edit_mode && $data_edit['semester'] == 'Ganjil') ? 'selected' : '' ?>>Ganjil</option>
                                <option value="Genap" <?= ($edit_mode && $data_edit['semester'] == 'Genap') ? 'selected' : '' ?>>Genap</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Ujian/Tugas</label>
                            <select name="jenis_ujian" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white">
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
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Detail Ujian</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Nilai</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $sql_select = "SELECT l.*, s.nama_lengkap, m.nama_mapel, u.nama as nama_ustadz 
                                           FROM leger_nilai l 
                                           JOIN buku_induk_santri s ON l.santri_id = s.id 
                                           JOIN master_mapel m ON l.mapel_id = m.id
                                           JOIN akun_ustadz u ON l.ustadz_id = u.id
                                           ORDER BY l.id DESC";
                            $res = $conn->query($sql_select);
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $warna_nilai = $row['nilai'] >= 75 ? 'text-emerald-600' : 'text-red-500';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3"><div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_lengkap']) ?></div><div class="text-xs text-gray-500">Kelas: <?= htmlspecialchars($row['kelas']) ?></div></td>
                                    <td class="px-4 py-3 text-sm text-cyan-700 font-medium"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div><?= htmlspecialchars($row['jenis_ujian']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($row['semester']) ?> <?= htmlspecialchars($row['tahun_ajaran']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-extrabold text-lg <?= $warna_nilai ?>"><?= htmlspecialchars($row['nilai']) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($row['ustadz_id'] == $ustadz_id || $_SESSION['ustadz_role'] === 'super_admin'): ?>
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus data nilai ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                        <?php else: ?>
                                            <span class="text-gray-400" title="Diinput oleh: <?= htmlspecialchars($row['nama_ustadz']) ?>"><i class="fas fa-lock"></i></span>
                                        <?php endif; ?>
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