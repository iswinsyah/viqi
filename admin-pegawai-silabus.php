<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS master_silabus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mata_pelajaran VARCHAR(150),
    kelas VARCHAR(50),
    deskripsi_mapel TEXT,
    capaian_pembelajaran TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM master_silabus WHERE id = $id");
    header("Location: admin-pegawai-silabus.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $mata_pelajaran = $conn->real_escape_string($_POST['mata_pelajaran']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $deskripsi_mapel = $conn->real_escape_string($_POST['deskripsi_mapel']);

    $elemen = $_POST['elemen'] ?? [];
    $cp_elemen = $_POST['cp_elemen'] ?? [];
    $cp_array = [];
    for($i=0; $i<count($elemen); $i++) {
        if(!empty($elemen[$i]) || !empty($cp_elemen[$i])) {
            $cp_array[] = ['elemen' => $elemen[$i], 'cp' => $cp_elemen[$i]];
        }
    }
    
    // Simpan array sebagai JSON agar terstruktur. Fallback jika kosong tetap simpan raw string
    $capaian_pembelajaran = !empty($cp_array) ? $conn->real_escape_string(json_encode($cp_array, JSON_UNESCAPED_UNICODE)) : $conn->real_escape_string($_POST['capaian_pembelajaran'] ?? '');

    if ($id > 0) {
        $sql = "UPDATE master_silabus SET mata_pelajaran='$mata_pelajaran', kelas='$kelas', deskripsi_mapel='$deskripsi_mapel', capaian_pembelajaran='$capaian_pembelajaran' WHERE id=$id";
        $pesan_sukses = "Silabus berhasil diupdate!";
    } else {
        $sql = "INSERT INTO master_silabus (mata_pelajaran, kelas, deskripsi_mapel, capaian_pembelajaran) VALUES ('$mata_pelajaran', '$kelas', '$deskripsi_mapel', '$capaian_pembelajaran')";
        $pesan_sukses = "Silabus baru berhasil disimpan!";
    }
    $conn->query($sql);
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM master_silabus WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'master_silabus';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Silabus & CP | Portal Ustadz</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book-reader text-purple-600 mr-2"></i>Master Silabus & Capaian Pembelajaran (CP)</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Silabus' : 'Buat Silabus Baru' ?></h2></div>
                <form action="admin-pegawai-silabus.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" value="<?= $edit_mode ? htmlspecialchars($data_edit['mata_pelajaran']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500" placeholder="Contoh: Digital Marketing, AI Terapan">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas / Jenjang</label>
                            <input type="text" name="kelas" value="<?= $edit_mode ? htmlspecialchars($data_edit['kelas']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500" placeholder="Contoh: Kelas 11 & 12">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Singkat Mata Pelajaran</label>
                        <textarea name="deskripsi_mapel" rows="2" class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500" placeholder="Jelaskan tujuan utama dari mata pelajaran ini..."><?= $edit_mode ? htmlspecialchars($data_edit['deskripsi_mapel']) : '' ?></textarea>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Capaian Pembelajaran (CP) per Elemen <span class="text-xs text-gray-500 font-normal">(Format Kurikulum Merdeka)</span></label>
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-1/3">Elemen</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi Capaian Pembelajaran</th>
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-16">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="cp-container" class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $cp_data = [];
                                    if ($edit_mode) {
                                        $cp_decoded = json_decode($data_edit['capaian_pembelajaran'], true);
                                        if (is_array($cp_decoded)) $cp_data = $cp_decoded;
                                        else $cp_data = [['elemen' => 'Umum', 'cp' => $data_edit['capaian_pembelajaran']]];
                                    } else { $cp_data = [['elemen' => '', 'cp' => '']]; }
                                    foreach($cp_data as $index => $item): ?>
                                    <tr class="cp-row">
                                        <td class="p-2 align-top"><input type="text" name="elemen[]" value="<?= htmlspecialchars($item['elemen']) ?>" class="w-full px-3 py-2 border rounded focus:ring-purple-500 text-sm font-semibold" placeholder="Cth: Menyimak / Fiqih" required></td>
                                        <td class="p-2 align-top"><textarea name="cp_elemen[]" rows="3" class="w-full px-3 py-2 border rounded focus:ring-purple-500 text-sm" placeholder="Peserta didik mampu..." required><?= htmlspecialchars($item['cp']) ?></textarea></td>
                                        <td class="p-2 align-top text-center"><button type="button" onclick="hapusBarisCP(this)" class="mt-1 text-red-500 hover:text-red-700 p-2"><i class="fas fa-trash"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="p-3 bg-gray-50 border-t border-gray-200">
                                <button type="button" onclick="tambahBarisCP()" class="text-sm font-bold text-purple-600 hover:text-purple-800"><i class="fas fa-plus-circle mr-1"></i> Tambah Elemen CP</button>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-pegawai-silabus.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Silabus' : 'Simpan Silabus' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Silabus Tersimpan</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Mapel & Kelas</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Deskripsi & Capaian</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM master_silabus ORDER BY id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-purple-700"><?= htmlspecialchars($row['mata_pelajaran']) ?></div>
                                        <div class="text-sm text-gray-600">Kelas: <?= htmlspecialchars($row['kelas']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($row['deskripsi_mapel']) ?></div>
                                        <div class="text-xs text-gray-500 mt-2">
                                            <b>Capaian Pembelajaran:</b>
                                            <?php
                                            $cp_json = json_decode($row['capaian_pembelajaran'], true);
                                            if(is_array($cp_json)) {
                                                echo '<table class="min-w-full mt-1 border border-gray-200"><thead class="bg-gray-100"><tr><th class="border border-gray-200 px-2 py-1 text-left">Elemen</th><th class="border border-gray-200 px-2 py-1 text-left">Deskripsi</th></tr></thead><tbody>';
                                                foreach($cp_json as $item) { echo '<tr><td class="border border-gray-200 px-2 py-1 font-semibold align-top w-1/3">'.htmlspecialchars($item['elemen']).'</td><td class="border border-gray-200 px-2 py-1">'.htmlspecialchars($item['cp']).'</td></tr>'; }
                                                echo '</tbody></table>';
                                            } else {
                                                echo '<div class="mt-1 whitespace-pre-line">'.htmlspecialchars($row['capaian_pembelajaran']).'</div>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center align-top">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus silabus ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500 italic'>Belum ada master silabus yang dibuat.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
    <script>
        function tambahBarisCP() {
            const tbody = document.getElementById('cp-container');
            const tr = document.createElement('tr');
            tr.className = 'cp-row';
            tr.innerHTML = `
                <td class="p-2 align-top"><input type="text" name="elemen[]" class="w-full px-3 py-2 border rounded focus:ring-purple-500 text-sm font-semibold" placeholder="Cth: Menulis / Akhlak" required></td>
                <td class="p-2 align-top"><textarea name="cp_elemen[]" rows="3" class="w-full px-3 py-2 border rounded focus:ring-purple-500 text-sm" placeholder="Peserta didik mampu..." required></textarea></td>
                <td class="p-2 align-top text-center"><button type="button" onclick="hapusBarisCP(this)" class="mt-1 text-red-500 hover:text-red-700 p-2" title="Hapus Baris"><i class="fas fa-trash"></i></button></td>
            `;
            tbody.appendChild(tr);
        }
        function hapusBarisCP(btn) {
            const row = btn.closest('tr');
            if (document.querySelectorAll('.cp-row').length > 1) { row.remove(); } else { alert('Minimal harus ada 1 elemen Capaian Pembelajaran.'); }
        }
    </script>
</body>
</html>