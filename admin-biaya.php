<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat tabel otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS biaya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori VARCHAR(50),
    nama_komponen VARCHAR(255),
    nominal INT
)");

// 2. Insert data dummy jika tabel kosong
$cek = $conn->query("SELECT COUNT(*) as tot FROM biaya");
if ($cek && $cek->fetch_assoc()['tot'] == 0) {
    $conn->query("INSERT INTO biaya (kategori, nama_komponen, nominal) VALUES 
    ('pendaftaran', 'Formulir & Administrasi SPMB', 350000),
    ('pangkal', 'Uang Pembangunan (Gedung & Fasilitas)', 8000000),
    ('pangkal', 'Ranjang, Kasur, Lemari, dll (Hak Pakai)', 2500000),
    ('pangkal', 'Seragam (4 Stel) & Modul Tahun Pertama', 3000000),
    ('tahunan', 'Biaya Sewa Asrama Tahunan', 2000000),
    ('tahunan', 'Biaya Kegiatan Tahunan', 1500000),
    ('spp', 'Makan 3x Sehari (Termasuk Suplemen/Brain Food)', 800000),
    ('spp', 'Pendidikan, Asrama, & Ekstrakurikuler', 600000),
    ('spp', 'Laundry Pakaian (Standar)', 100000)");
}

// Fungsi Sinkronisasi Otomatis ke Pengaturan Brosur Digital
function syncBiayaKeBrosur($conn) {
    $check = $conn->query("SHOW TABLES LIKE 'pengaturan_brosur'");
    if ($check && $check->num_rows > 0) {
        $r_tot = $conn->query("SELECT kategori, SUM(nominal) as total FROM biaya GROUP BY kategori");
        $tot = ['pendaftaran' => 0, 'pangkal' => 0, 'tahunan' => 0, 'spp' => 0];
        if ($r_tot) {
            while ($row = $r_tot->fetch_assoc()) {
                $k = strtolower(trim($row['kategori']));
                if (isset($tot[$k])) $tot[$k] = (int)$row['total'];
            }
        }
        $conn->query("UPDATE pengaturan_brosur SET 
            biaya_pendaftaran = {$tot['pendaftaran']},
            biaya_pangkal = {$tot['pangkal']},
            biaya_tahunan = {$tot['tahunan']},
            biaya_spp = {$tot['spp']}
            WHERE id = 1");
    }
}

// 3. Proses Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM biaya WHERE id = $id");
    syncBiayaKeBrosur($conn);
    header("Location: admin-biaya.php");
    exit;
}

// 4. Proses Simpan/Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $nama_komponen = $conn->real_escape_string($_POST['nama_komponen']);
    $nominal = (int)$_POST['nominal'];

    if ($id > 0) {
        $sql = "UPDATE biaya SET kategori='$kategori', nama_komponen='$nama_komponen', nominal=$nominal WHERE id=$id";
        $pesan_sukses = "Komponen biaya berhasil diperbarui dan tersinkron ke Brosur!";
    } else {
        $sql = "INSERT INTO biaya (kategori, nama_komponen, nominal) VALUES ('$kategori', '$nama_komponen', $nominal)";
        $pesan_sukses = "Komponen biaya baru berhasil ditambahkan dan tersinkron ke Brosur!";
    }
    $conn->query($sql);
    syncBiayaKeBrosur($conn);
}

// 5. Ambil data edit jika ada
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM biaya WHERE id = $id");
    if($res) $data_edit = $res->fetch_assoc();
}

// Sinkronkan ke brosur jika perlu
syncBiayaKeBrosur($conn);

// Hitung total akumulasi per kategori untuk tampilan ringkasan
$totals_kategori = ['pendaftaran' => 0, 'pangkal' => 0, 'tahunan' => 0, 'spp' => 0];
$counts_kategori = ['pendaftaran' => 0, 'pangkal' => 0, 'tahunan' => 0, 'spp' => 0];
$r_sub = $conn->query("SELECT kategori, COUNT(*) as jml, SUM(nominal) as total FROM biaya GROUP BY kategori");
if ($r_sub) {
    while($row = $r_sub->fetch_assoc()) {
        $k = strtolower(trim($row['kategori']));
        if (isset($totals_kategori[$k])) {
            $totals_kategori[$k] = (int)$row['total'];
            $counts_kategori[$k] = (int)$row['jml'];
        }
    }
}

$active_menu = 'biaya';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Biaya | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Administrasi Digital Sekolah (SADIGS 4.0)</h2>
            <a href="biaya.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"><i class="fas fa-external-link-alt mr-1"></i> Lihat Halaman Biaya</a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-money-bill-wave text-green-600 mr-2"></i>Pengaturan Biaya Pendidikan</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-green-100 text-green-700 px-4 py-3 rounded-lg mb-6 shadow-sm'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- KARTU RINGKASAN AKUMULASI BIAYA & STATUS SINKRONISASI BROSUR -->
            <div class="mb-8 bg-white p-6 rounded-2xl shadow-sm border border-emerald-100/80">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-gray-100 gap-3">
                    <div>
                        <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-calculator text-emerald-600"></i>
                            Total Akumulasi Biaya Pendidikan (Acuan Resmi Sistem)
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Jumlah akumulasi dari rincian di bawah ini otomatis menjadi acuan tampilan <strong>Brosur Digital (Menu Investasi Pendidikan)</strong> dan <strong>Halaman Publik Info Biaya</strong>.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                            Tersinkron Otomatis ke Brosur
                        </span>
                        <a href="brosur.php#brosur-biaya" target="_blank" class="text-xs bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-bold shadow-xs flex items-center gap-1.5 transition">
                            <i class="fas fa-external-link-alt"></i> Cek Tampilan Brosur
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-5">
                    <!-- 1. Pendaftaran -->
                    <div class="bg-emerald-50/50 rounded-xl p-4 border border-emerald-200">
                        <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-emerald-800">
                            <span>1. Biaya Pendaftaran</span>
                            <span class="bg-emerald-200/80 text-emerald-800 px-1.5 py-0.5 rounded text-[10px]"><?= $counts_kategori['pendaftaran'] ?> item</span>
                        </div>
                        <div class="text-xl font-black text-emerald-950 mt-1.5 font-mono">Rp <?= number_format($totals_kategori['pendaftaran'], 0, ',', '.') ?></div>
                        <span class="text-[10px] text-emerald-600 mt-1 block">Tampil di Brosur Hal 7</span>
                    </div>

                    <!-- 2. Uang Pangkal -->
                    <div class="bg-amber-50/50 rounded-xl p-4 border border-amber-200">
                        <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-amber-800">
                            <span>2. Uang Pangkal Masuk</span>
                            <span class="bg-amber-200/80 text-amber-800 px-1.5 py-0.5 rounded text-[10px]"><?= $counts_kategori['pangkal'] ?> item</span>
                        </div>
                        <div class="text-xl font-black text-amber-950 mt-1.5 font-mono">Rp <?= number_format($totals_kategori['pangkal'], 0, ',', '.') ?></div>
                        <span class="text-[10px] text-amber-600 mt-1 block">Tampil di Brosur Hal 7</span>
                    </div>

                    <!-- 3. Biaya Tahunan -->
                    <div class="bg-purple-50/50 rounded-xl p-4 border border-purple-200">
                        <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-purple-800">
                            <span>3. Biaya Tahunan</span>
                            <span class="bg-purple-200/80 text-purple-800 px-1.5 py-0.5 rounded text-[10px]"><?= $counts_kategori['tahunan'] ?> item</span>
                        </div>
                        <div class="text-xl font-black text-purple-950 mt-1.5 font-mono">Rp <?= number_format($totals_kategori['tahunan'], 0, ',', '.') ?></div>
                        <span class="text-[10px] text-purple-600 mt-1 block">Tampil di Brosur Hal 7</span>
                    </div>

                    <!-- 4. SPP Bulanan -->
                    <div class="bg-blue-50/50 rounded-xl p-4 border border-blue-200">
                        <div class="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-blue-800">
                            <span>4. SPP All-in Bulanan</span>
                            <span class="bg-blue-200/80 text-blue-800 px-1.5 py-0.5 rounded text-[10px]"><?= $counts_kategori['spp'] ?> item</span>
                        </div>
                        <div class="text-xl font-black text-blue-950 mt-1.5 font-mono">Rp <?= number_format($totals_kategori['spp'], 0, ',', '.') ?> <span class="text-xs font-normal text-blue-700">/bln</span></div>
                        <span class="text-[10px] text-blue-600 mt-1 block">Tampil di Brosur Hal 7</span>
                    </div>
                </div>
            </div>

            <!-- FORM INPUT -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-green-50 border-b border-green-100"><h2 class="font-bold text-green-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Komponen Biaya' : 'Tambah Komponen Biaya' ?></h2></div>
                <form action="admin-biaya.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kategori Tabel <span class="text-red-500">*</span></label>
                            <select name="kategori" required class="w-full px-4 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500">
                                <option value="pendaftaran" <?= ($edit_mode && $data_edit['kategori'] == 'pendaftaran') ? 'selected' : '' ?>>1. Biaya Pendaftaran</option>
                                <option value="pangkal" <?= ($edit_mode && $data_edit['kategori'] == 'pangkal') ? 'selected' : '' ?>>2. Uang Pangkal</option>
                                <option value="tahunan" <?= ($edit_mode && $data_edit['kategori'] == 'tahunan') ? 'selected' : '' ?>>3. Biaya Tahunan</option>
                                <option value="spp" <?= ($edit_mode && $data_edit['kategori'] == 'spp') ? 'selected' : '' ?>>4. SPP Bulanan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Komponen <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_komponen" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_komponen']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: Uang Gedung">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nominal (Rp) <span class="text-red-500">*</span></label>
                            <input type="number" name="nominal" value="<?= $edit_mode ? htmlspecialchars($data_edit['nominal']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: 1500000">
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-biaya.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-1"></i> <?= $edit_mode ? 'Update' : 'Simpan' ?></button>
                    </div>
                </form>
            </div>

            <!-- TABEL LIST BIAYA -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h2 class="font-bold text-gray-800">Daftar Rincian Biaya Saat Ini</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kategori</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Komponen</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Nominal (Rp)</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $badge_map = [
                                'pendaftaran' => ['bg' => 'bg-emerald-100 text-emerald-800 border-emerald-200', 'label' => '1. Pendaftaran'],
                                'pangkal'     => ['bg' => 'bg-amber-100 text-amber-800 border-amber-200',     'label' => '2. Uang Pangkal'],
                                'tahunan'     => ['bg' => 'bg-purple-100 text-purple-800 border-purple-200',   'label' => '3. Tahunan'],
                                'spp'         => ['bg' => 'bg-blue-100 text-blue-800 border-blue-200',       'label' => '4. SPP Bulanan']
                            ];
                            $res = $conn->query("SELECT * FROM biaya ORDER BY FIELD(kategori, 'pendaftaran', 'pangkal', 'tahunan', 'spp'), id ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $kat = strtolower(trim($row['kategori']));
                                    $badge = $badge_map[$kat] ?? ['bg' => 'bg-gray-100 text-gray-800 border-gray-200', 'label' => $kat];
                                ?>
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="px-4 py-3 text-xs">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md font-bold border <?= $badge['bg'] ?>">
                                            <?= $badge['label'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-bold text-gray-900 text-sm"><?= htmlspecialchars($row['nama_komponen']) ?></td>
                                    <td class="px-4 py-3 text-right font-mono font-bold text-gray-800 text-sm">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center font-medium">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3 inline-flex items-center gap-1 font-bold text-xs" title="Edit"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Yakin menghapus komponen biaya ini? Nilai total pada Brosur dan Website akan otomatis terupdate.')" class="text-rose-600 hover:text-rose-900 inline-flex items-center gap-1 font-bold text-xs" title="Hapus"><i class="fas fa-trash"></i> Hapus</a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500'>Belum ada data biaya.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>document.getElementById('open-sidebar').addEventListener('click', () => { document.getElementById('sidebar').classList.toggle('hidden'); document.getElementById('sidebar-overlay').classList.toggle('hidden'); });</script>
</body>
</html>