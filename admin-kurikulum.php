<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS kurikulum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ikon VARCHAR(100),
    judul VARCHAR(150),
    deskripsi TEXT
)");

// 2. Insert data dummy jika tabel kosong
$cek = $conn->query("SELECT COUNT(*) as tot FROM kurikulum");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO kurikulum (ikon, judul, deskripsi) VALUES 
    ('fas fa-quran', 'Tahfidz & Tahsin', 'Fokus pada hafalan kuat (mutqin) dan perbaikan bacaan dengan standar tajwid bersanad.'),
    ('fas fa-book-reader', 'Adab & Tsaqofah Islam', 'Pendidikan karakter, fiqih dasar, dan aqidah sebelum menguasai ilmu lainnya.'),
    ('fas fa-school', 'Pengetahuan Umum (Dinas)', 'Kurikulum nasional (Matematika, IPA, dll) untuk bekal masa depan dan ujian negara.'),
    ('fas fa-bullseye', 'Olahraga Sunnah', 'Kegiatan memanah, berenang, dan bela diri untuk melatih ketangkasan dan fokus santri.'),
    ('fas fa-laptop-code', 'Digitalpreneur', 'Membekali santri dengan keterampilan bisnis dan teknologi di era digital.'),
    ('fas fa-robot', 'AI Terapan', 'Pemanfaatan kecerdasan buatan untuk meningkatkan produktivitas harian santri.'),
    ('fas fa-users-cog', 'Leadership', 'Latihan kepemimpinan, public speaking, dan kemandirian mengurus diri di asrama.')");
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM kurikulum WHERE id = $id");
    header("Location: admin-kurikulum.php");
    exit;
}

// 4. Proses Simpan/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $ikon = $conn->real_escape_string($_POST['ikon']);
    $judul = $conn->real_escape_string($_POST['judul']);
    $deskripsi = $conn->real_escape_string($_POST['deskripsi']);

    if ($id > 0) {
        $sql = "UPDATE kurikulum SET ikon='$ikon', judul='$judul', deskripsi='$deskripsi' WHERE id=$id";
        $pesan_sukses = "Program kurikulum berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO kurikulum (ikon, judul, deskripsi) VALUES ('$ikon', '$judul', '$deskripsi')";
        $pesan_sukses = "Program kurikulum baru berhasil ditambahkan!";
    }
    $conn->query($sql);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM kurikulum WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'kurikulum';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurikulum Terpadu | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <a href="index.html#kurikulum" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Web</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book text-rose-500 mr-2"></i>Pengaturan Kurikulum</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM INPUT (2 KOLOM) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-rose-50 border-b border-rose-100"><h2 class="font-bold text-rose-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Program Kurikulum' : 'Tambah Program Baru' ?></h2></div>
                <form action="admin-kurikulum.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-6">
                        
                        <!-- KOLOM 1: Ikon -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">1. Ikon Program</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kode Ikon FontAwesome <span class="text-red-500">*</span></label>
                                <div class="flex gap-2">
                                    <div class="w-12 h-10 bg-gray-100 border border-gray-300 rounded-lg flex items-center justify-center text-xl text-gray-600">
                                        <i id="preview-ikon" class="<?= $edit_mode ? htmlspecialchars($data_edit['ikon']) : 'fas fa-star' ?>"></i>
                                    </div>
                                    <input type="text" name="ikon" id="input-ikon" value="<?= $edit_mode ? htmlspecialchars($data_edit['ikon']) : 'fas fa-star' ?>" required class="flex-1 px-4 py-2 border rounded-lg focus:ring-rose-500 focus:border-rose-500" oninput="document.getElementById('preview-ikon').className=this.value">
                                    <button type="button" onclick="bukaModalIkon()" class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-4 py-2 rounded-lg font-bold transition">Pilih</button>
                                </div>
                            </div>
                        </div>

                        <!-- KOLOM 2: Keterangan (Judul & Paragraf) -->
                        <div class="space-y-4">
                            <h3 class="font-bold text-gray-800 border-b pb-2">2. Keterangan Program</h3>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Judul Program <span class="text-red-500">*</span></label>
                                <input type="text" name="judul" value="<?= $edit_mode ? htmlspecialchars($data_edit['judul']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-rose-500 focus:border-rose-500" placeholder="Contoh: Tahfidz & Tahsin">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-1">Paragraf Penjelasan</label>
                                <textarea name="deskripsi" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-rose-500 focus:border-rose-500" placeholder="Penjelasan singkat tentang program ini..."><?= $edit_mode ? htmlspecialchars($data_edit['deskripsi']) : '' ?></textarea>
                            </div>
                            <div class="pt-4 text-right">
                                <?php if($edit_mode) echo '<a href="admin-kurikulum.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                                <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update Program' : 'Simpan Program' ?></button>
                            </div>
                        </div>

                    </div>
                </form>
            </div>

            <!-- TABEL LIST KURIKULUM -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Kurikulum Terpadu</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase w-16">Ikon</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan Program</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM kurikulum ORDER BY id ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr>
                                    <td class="px-4 py-3 text-center text-2xl text-rose-500"><i class="<?= htmlspecialchars($row['ikon']) ?>"></i></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($row['deskripsi']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Yakin menghapus program ini?')" class="text-rose-600 hover:text-rose-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='3' class='text-center py-6 text-gray-500'>Belum ada data kurikulum.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL PEMILIH IKON -->
    <div id="icon-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl overflow-hidden">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800">Pilih Ikon Program</h3>
                <button type="button" onclick="tutupModalIkon()" class="text-gray-400 hover:text-red-500 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div id="icon-grid" class="p-6 h-96 overflow-y-auto grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-4 text-center">
                <!-- Ikon akan dimuat dengan Javascript -->
            </div>
        </div>
    </div>

    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });

        // Script Modal Ikon
        const icons = ['fas fa-quran', 'fas fa-book-reader', 'fas fa-school', 'fas fa-bullseye', 'fas fa-laptop-code', 'fas fa-robot', 'fas fa-users-cog', 'fas fa-leaf', 'fas fa-brain', 'fas fa-mosque', 'fas fa-graduation-cap', 'fas fa-chalkboard-teacher', 'fas fa-running', 'fas fa-swimmer', 'fas fa-heart', 'fas fa-hand-holding-heart', 'fas fa-seedling', 'fas fa-microscope', 'fas fa-chart-line', 'fas fa-cogs', 'fas fa-globe', 'fas fa-language', 'fas fa-pencil-alt', 'fas fa-medal'];
        const iconGrid = document.getElementById('icon-grid');
        icons.forEach(ic => {
            iconGrid.innerHTML += `<div onclick="pilihIkon('${ic}')" class="p-3 border border-gray-200 rounded hover:bg-rose-50 hover:border-rose-300 cursor-pointer transition text-gray-600 hover:text-rose-500 text-2xl flex items-center justify-center"><i class="${ic}"></i></div>`;
        });

        function bukaModalIkon() { document.getElementById('icon-modal').classList.remove('hidden'); }
        function tutupModalIkon() { document.getElementById('icon-modal').classList.add('hidden'); }
        function pilihIkon(iconClass) {
            document.getElementById('input-ikon').value = iconClass;
            document.getElementById('preview-ikon').className = iconClass;
            tutupModalIkon();
        }
    </script>
</body>
</html>