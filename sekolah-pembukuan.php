<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'sekolah_pembukuan';

// 1. Self-Healing Database: Buat tabel jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS sekolah_kas_operasional (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    jenis ENUM('Pemasukan', 'Pengeluaran') NOT NULL,
    nominal DECIMAL(15,2) NOT NULL,
    keterangan TEXT NOT NULL,
    saldo DECIMAL(15,2) NOT NULL,
    input_by VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Proses Input Data
$pesan_sukses = '';
$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'simpan') {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $jenis = $conn->real_escape_string($_POST['jenis']);
    $keterangan = $conn->real_escape_string($_POST['keterangan']);
    // Hilangkan titik dari format rupiah
    $nominal = (float)str_replace('.', '', $_POST['nominal']);
    $input_by = $conn->real_escape_string($_SESSION['ustadz_nama'] ?? 'Admin Sekolah');

    if ($nominal > 0) {
        // Ambil saldo terakhir
        $res_saldo = $conn->query("SELECT saldo FROM sekolah_kas_operasional ORDER BY id DESC LIMIT 1");
        $saldo_terakhir = ($res_saldo && $res_saldo->num_rows > 0) ? (float)$res_saldo->fetch_assoc()['saldo'] : 0;

        // Hitung saldo baru
        $saldo_baru = ($jenis == 'Pemasukan') ? ($saldo_terakhir + $nominal) : ($saldo_terakhir - $nominal);

        $sql_insert = "INSERT INTO sekolah_kas_operasional (tanggal, jenis, nominal, keterangan, saldo, input_by) 
                       VALUES ('$tanggal', '$jenis', $nominal, '$keterangan', $saldo_baru, '$input_by')";
        if ($conn->query($sql_insert)) {
            $pesan_sukses = "Data kas berhasil ditambahkan!";
        } else {
            $pesan_error = "Gagal menyimpan data: " . $conn->error;
        }
    } else {
        $pesan_error = "Nominal harus lebih dari 0!";
    }
}

// 3. Proses Hapus Data (Opsional jika diperlukan, tapi merusak urutan saldo jika asal hapus di tengah)
if (isset($_GET['hapus']) && isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $id_hapus = (int)$_GET['hapus'];
    // Untuk keamanan saldo, sebaiknya yang bisa dihapus hanya transaksi TERAKHIR.
    $res_last = $conn->query("SELECT id FROM sekolah_kas_operasional ORDER BY id DESC LIMIT 1");
    $last_id = ($res_last && $res_last->num_rows > 0) ? (int)$res_last->fetch_assoc()['id'] : 0;
    
    if ($id_hapus == $last_id) {
        $conn->query("DELETE FROM sekolah_kas_operasional WHERE id = $id_hapus");
        $pesan_sukses = "Transaksi terakhir berhasil dihapus!";
    } else {
        $pesan_error = "Gagal menghapus! Demi akurasi saldo berjalan, Anda hanya diizinkan menghapus transaksi yang paling terakhir diinput.";
    }
}

// 4. Ambil Data Summary
$bulan_ini = date('Y-m');
$q_in = $conn->query("SELECT SUM(nominal) as total FROM sekolah_kas_operasional WHERE jenis='Pemasukan' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'");
$total_masuk_bulan = $q_in ? (float)$q_in->fetch_assoc()['total'] : 0;

$q_out = $conn->query("SELECT SUM(nominal) as total FROM sekolah_kas_operasional WHERE jenis='Pengeluaran' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'");
$total_keluar_bulan = $q_out ? (float)$q_out->fetch_assoc()['total'] : 0;

$res_saldo = $conn->query("SELECT saldo FROM sekolah_kas_operasional ORDER BY id DESC LIMIT 1");
$saldo_sekarang = ($res_saldo && $res_saldo->num_rows > 0) ? (float)$res_saldo->fetch_assoc()['saldo'] : 0;

// 5. Ambil Data Tabel (Bulan ini secara default)
$filter_bulan = isset($_GET['filter_bulan']) ? $conn->real_escape_string($_GET['filter_bulan']) : $bulan_ini;
$sql_history = "SELECT * FROM sekolah_kas_operasional WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$filter_bulan' ORDER BY id ASC";
$res_history = $conn->query($sql_history);
$history_data = $res_history ? $res_history->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Kas Sekolah | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Administrasi Digital Sekolah (SADIGS 4.0)</h2>
            </div>
            <div class="text-sm font-medium text-gray-600 bg-gray-100 px-3 py-1.5 rounded-full"><i class="fas fa-user-circle mr-2"></i><?= htmlspecialchars($_SESSION['ustadz_nama'] ?? 'Admin') ?></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book text-emerald-600 mr-2"></i>Buku Kas Sekolah</h1>
                    <p class="text-sm text-gray-500 mt-1">Pencatatan kas operasional harian khusus tingkat sekolah/admin.</p>
                </div>
            </div>

            <?php if(!empty($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(!empty($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <!-- KARTU SUMMARY -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-emerald-500">
                    <div class="text-gray-500 text-sm font-semibold mb-1">Total Pemasukan (Bulan Ini)</div>
                    <div class="text-2xl font-bold text-gray-800">Rp <?= number_format($total_masuk_bulan, 0, ',', '.') ?></div>
                </div>
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 border-l-4 border-l-rose-500">
                    <div class="text-gray-500 text-sm font-semibold mb-1">Total Pengeluaran (Bulan Ini)</div>
                    <div class="text-2xl font-bold text-gray-800">Rp <?= number_format($total_keluar_bulan, 0, ',', '.') ?></div>
                </div>
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-xl p-6 shadow-md text-white">
                    <div class="text-emerald-100 text-sm font-semibold mb-1">Saldo Kas Sekolah Saat Ini</div>
                    <div class="text-3xl font-extrabold">Rp <?= number_format($saldo_sekarang, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- FORM INPUT -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100">
                            <h2 class="font-bold text-emerald-800"><i class="fas fa-plus-circle mr-2"></i>Catat Transaksi</h2>
                        </div>
                        <form action="" method="POST" class="p-6">
                            <input type="hidden" name="action" value="simpan">
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-lg focus:ring-emerald-500">
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Transaksi</label>
                                <select name="jenis" required class="w-full px-3 py-2 border rounded-lg focus:ring-emerald-500">
                                    <option value="Pemasukan">Pemasukan (Kas Bertambah)</option>
                                    <option value="Pengeluaran">Pengeluaran (Kas Berkurang)</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nominal (Rp)</label>
                                <input type="text" name="nominal" required oninput="formatRupiah(this)" class="w-full px-3 py-2 border rounded-lg focus:ring-emerald-500" placeholder="Contoh: 150.000">
                            </div>
                            
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                                <textarea name="keterangan" rows="3" required class="w-full px-3 py-2 border rounded-lg focus:ring-emerald-500" placeholder="Contoh: Beli spidol dan kertas HVS..."></textarea>
                            </div>
                            
                            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 rounded-lg shadow-md transition">
                                Simpan Transaksi
                            </button>
                        </form>
                    </div>
                </div>

                <!-- TABEL RIWAYAT -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                            <h2 class="font-bold text-gray-800">Buku Kas - Riwayat Transaksi</h2>
                            <form action="" method="GET" class="flex gap-2">
                                <input type="month" name="filter_bulan" value="<?= htmlspecialchars($filter_bulan) ?>" class="text-sm px-2 py-1 border rounded" onchange="this.form.submit()">
                            </form>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white">
                                    <tr class="text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <th class="px-4 py-3">Tgl</th>
                                        <th class="px-4 py-3 w-1/3">Keterangan</th>
                                        <th class="px-4 py-3 text-right">Masuk</th>
                                        <th class="px-4 py-3 text-right">Keluar</th>
                                        <th class="px-4 py-3 text-right text-emerald-600">Saldo</th>
                                        <th class="px-4 py-3 text-center"><i class="fas fa-cog"></i></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    <?php if(empty($history_data)): ?>
                                        <tr><td colspan="6" class="text-center py-10 text-gray-400 italic">Tidak ada transaksi pada bulan ini.</td></tr>
                                    <?php else: ?>
                                        <?php 
                                        // Cari ID terakhir untuk fungsi hapus
                                        $last_row = end($history_data);
                                        $last_id = $last_row ? $last_row['id'] : 0;
                                        
                                        foreach($history_data as $r): 
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-800"><?= htmlspecialchars($r['keterangan']) ?></div>
                                                <div class="text-xs text-gray-400 mt-0.5"><i class="fas fa-user-edit mr-1"></i><?= htmlspecialchars($r['input_by']) ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-right text-emerald-600 font-medium">
                                                <?= $r['jenis'] == 'Pemasukan' ? number_format($r['nominal'], 0, ',', '.') : '-' ?>
                                            </td>
                                            <td class="px-4 py-3 text-right text-rose-500 font-medium">
                                                <?= $r['jenis'] == 'Pengeluaran' ? number_format($r['nominal'], 0, ',', '.') : '-' ?>
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-gray-800">
                                                <?= number_format($r['saldo'], 0, ',', '.') ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if($r['id'] == $last_id): ?>
                                                    <a href="?hapus=<?= $r['id'] ?>&confirm=yes" onclick="return confirm('Yakin ingin menghapus transaksi terakhir ini?')" class="text-rose-400 hover:text-rose-600 p-1" title="Hapus transaksi terakhir"><i class="fas fa-trash-alt"></i></a>
                                                <?php else: ?>
                                                    <button type="button" class="text-gray-300 cursor-not-allowed p-1" title="Hanya transaksi paling akhir yang bisa dihapus untuk menjaga urutan saldo"><i class="fas fa-trash-alt"></i></button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panduan -->
            <div class="mt-8 bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-4 text-sm text-blue-800">
                <i class="fas fa-info-circle text-blue-500 text-xl mt-0.5"></i>
                <div>
                    <strong>Penting:</strong>
                    <ul class="list-disc ml-4 mt-1 space-y-1">
                        <li>Buku kas ini berjalan menggunakan sistem <em>Running Balance</em>. Saldo akan otomatis bertambah atau berkurang mengikuti urutan input Anda.</li>
                        <li>Untuk menjaga akurasi pembukuan (agar saldo tidak berantakan), Anda <strong>hanya diizinkan menghapus data transaksi yang paling terakhir diinput</strong>.</li>
                        <li>Pastikan Anda menginput transaksi sesuai dengan urutan tanggal yang benar.</li>
                    </ul>
                </div>
            </div>

        </main>
    </div>

    <script>
    function formatRupiah(angka){
        let number_string = angka.value.replace(/[^,\d]/g, '').toString(),
        split   		= number_string.split(','),
        sisa     		= split[0].length % 3,
        rupiah     		= split[0].substr(0, sisa),
        ribuan     		= split[0].substr(sisa).match(/\d{3}/gi);

        if(ribuan){
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        angka.value = rupiah;
    }
    </script>
</body>
</html>
