<?php
// Tampilkan pesan error di layar jika ada masalah (Debugging)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'koneksi.php';

// 1. Proses Hapus Data Agen
if (isset($_GET['hapus_id'])) {
    $hapus_id = (int)$_GET['hapus_id'];
    $sql_hapus = "DELETE FROM agen WHERE id = $hapus_id";
    if ($conn->query($sql_hapus) === TRUE) {
        $pesan_sukses = "Data agen berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus data: " . $conn->error;
    }
}

// 2. Ambil Data untuk Form Edit
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = (int)$_GET['edit_id'];
    $result = $conn->query("SELECT * FROM agen WHERE id = $edit_id");
    if ($result && $result->num_rows > 0) {
        $data_edit = $result->fetch_assoc();
    }
}

// 3. Proses Simpan / Update Data Agen jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Gunakan isset() untuk mencegah Fatal Error (TypeError) di PHP 8+
    $nama = isset($_POST['nama']) ? $conn->real_escape_string($_POST['nama']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? $conn->real_escape_string($_POST['whatsapp']) : '';
    $bank = isset($_POST['bank']) ? $conn->real_escape_string($_POST['bank']) : '';
    $rekening = isset($_POST['rekening']) ? $conn->real_escape_string($_POST['rekening']) : '';
    
    // Generate Kode Referral jika kolom tidak diisi
    if (!empty($_POST['kode_ref'])) {
        $kode_ref = $conn->real_escape_string($_POST['kode_ref']);
    } else {
        // Format otomatis: nama huruf kecil tanpa spasi + angka random
        $kode_ref = strtolower(str_replace(' ', '', $nama)) . rand(10,99);
    }

    // Cek apakah ini mode Update atau Insert baru
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id_update = (int)$_POST['id'];
        $sql = "UPDATE agen SET nama='$nama', whatsapp='$whatsapp', bank='$bank', rekening='$rekening', kode_ref='$kode_ref' WHERE id=$id_update";
        $pesan_berhasil = "Data agen berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO agen (nama, whatsapp, bank, rekening, kode_ref) VALUES ('$nama', '$whatsapp', '$bank', '$rekening', '$kode_ref')";
        $pesan_berhasil = "Data agen berhasil disimpan!";
    }
    
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = $pesan_berhasil;
        // Reset form edit setelah sukses
        $edit_mode = false;
        $data_edit = null;
    } else {
        $pesan_error = "Gagal menyimpan data: " . $conn->error;
    }
}

$active_menu = 'agen';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Agen | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
                </a>
                <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Manajemen Agen Referral</h1>
            </div>

            <!-- Notifikasi Sukses / Error -->
            <?php if(isset($pesan_sukses)) { ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative mb-6 shadow-sm flex items-center">
                    <i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?>
                </div>
            <?php } ?>
            
            <?php if(isset($pesan_error)) { ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg relative mb-6 shadow-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?>
                </div>
            <?php } ?>

            <!-- FORM INPUT AGEN BARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 bg-emerald-50">
                    <h2 class="font-bold text-emerald-800">
                        <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus-circle' ?> mr-2"></i>
                        <?= $edit_mode ? 'Edit Data Agen' : 'Tambah Agen Baru' ?>
                    </h2>
                </div>
                <div class="p-6">
                    <form action="" method="POST">
                        <!-- Hidden ID untuk mode Edit -->
                        <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Nama Agen -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap Agen <span class="text-red-500">*</span></label>
                                <input type="text" name="nama" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama']) : '' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: Ustadz Budi / Bpk. Fulan">
                            </div>
                            <!-- Nomor WA -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp <span class="text-red-500">*</span></label>
                                <input type="number" name="whatsapp" value="<?= $edit_mode ? htmlspecialchars($data_edit['whatsapp']) : '' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Contoh: 081234567890">
                            </div>
                            <!-- Nama Bank -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank <span class="text-red-500">*</span></label>
                                <select name="bank" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="">-- Pilih Bank --</option>
                                    <?php
                                    $banks = ['BSI (Bank Syariah Indonesia)' => 'BSI', 'Bank Mandiri' => 'Mandiri', 'BCA' => 'BCA', 'BRI' => 'BRI', 'BNI' => 'BNI', 'Lainnya...' => 'Lainnya'];
                                    foreach($banks as $label => $val) {
                                        $selected = ($edit_mode && $data_edit['bank'] == $val) ? 'selected' : '';
                                        echo "<option value=\"$val\" $selected>$label</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <!-- Nomor Rekening -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening <span class="text-red-500">*</span></label>
                                <input type="number" name="rekening" value="<?= $edit_mode ? htmlspecialchars($data_edit['rekening']) : '' ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500" placeholder="Nomor Rekening Tujuan Komisi">
                            </div>
                            <!-- Kode Referral Kustom -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Kode Referral (Opsional)</label>
                                <input type="text" name="kode_ref" value="<?= $edit_mode ? htmlspecialchars($data_edit['kode_ref']) : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-emerald-500 focus:border-emerald-500 bg-gray-50" placeholder="Biarkan kosong untuk generate otomatis, atau ketik: ustadzbudi">
                                <p class="text-xs text-gray-500 mt-1">Kode ini akan digunakan di link: <code>villaquranindonesia.com/?ref=kode</code></p>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end space-x-3">
                            <?php if($edit_mode) { ?>
                                <a href="data-agen.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2.5 px-6 rounded-lg transition shadow-sm flex items-center">
                                    Batal
                                </a>
                            <?php } ?>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-lg transition shadow-md flex items-center">
                                <i class="fas <?= $edit_mode ? 'fa-save' : 'fa-paper-plane' ?> mr-2"></i> <?= $edit_mode ? 'Update Data Agen' : 'Simpan Data Agen' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TABEL DATA AGEN -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                    <h2 class="font-bold text-gray-800">Daftar Agen Aktif</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Info Agen</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Rekening Bank</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Link Referral</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Leads</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- SCRIPT PHP UNTUK MENAMPILKAN DATA ASLI -->
                            <?php
                            $sql_tampil = "SELECT a.*, (SELECT COUNT(id) FROM leads l WHERE l.kode_ref = a.kode_ref OR l.kode_ref = a.whatsapp) AS total_leads FROM agen a ORDER BY a.id DESC";
                            $result = $conn->query($sql_tampil);

                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama']) ?></div>
                                        <div class="text-sm text-gray-500"><i class="fab fa-whatsapp text-green-500 mr-1"></i> <?= htmlspecialchars($row['whatsapp']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($row['bank']) ?></div>
                                        <div class="text-sm text-gray-500"><?= htmlspecialchars($row['rekening']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded font-mono text-xs border border-gray-200">?ref=<?= htmlspecialchars($row['whatsapp']) ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold"><?= $row['total_leads'] ?> Orang</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="data-agen.php?edit_id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="data-agen.php?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus agen <?= addslashes($row['nama']) ?>?');" class="text-rose-600 hover:text-rose-900" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php
                                }
                            } else {
                            ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 italic">Belum ada data agen yang terdaftar. Silakan input di atas.</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('open-sidebar');
            const closeBtn = document.getElementById('close-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                sidebar.classList.toggle('hidden');
                overlay.classList.toggle('hidden');
            }

            openBtn.addEventListener('click', toggleSidebar);
            closeBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>