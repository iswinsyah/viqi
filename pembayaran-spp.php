<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$active_menu = 'pembayaran_keuangan';

// 1. Buat Tabel Otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS pembayaran_spp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    jenis_pembayaran VARCHAR(100) DEFAULT 'Infaq Bulanan (SPP)',
    keterangan_lainnya TEXT,
    bulan VARCHAR(20),
    tahun VARCHAR(4),
    jumlah INT,
    tanggal_bayar DATE,
    bukti_transfer VARCHAR(255),
    status ENUM('Menunggu Verifikasi', 'Berhasil', 'Ditolak') DEFAULT 'Menunggu Verifikasi',
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE
)");

// Tambahkan kolom baru jika belum ada (self-healing)
@$conn->query("ALTER TABLE pembayaran_spp ADD COLUMN jenis_pembayaran VARCHAR(100) AFTER santri_id");
@$conn->query("ALTER TABLE pembayaran_spp ADD COLUMN keterangan_lainnya TEXT AFTER jenis_pembayaran");
// Tambahkan kolom baru jika belum ada (self-healing)
@$conn->query("ALTER TABLE pembayaran_spp ADD COLUMN jenis_pembayaran VARCHAR(100) AFTER santri_id");
@$conn->query("ALTER TABLE pembayaran_spp ADD COLUMN keterangan_lainnya TEXT AFTER jenis_pembayaran");

// 2. Ambil daftar santri yang terhubung
$santri_list = [];
if ($orangtua_id == 9999) {
    $res_s = $conn->query("SELECT id, nama_lengkap FROM buku_induk_santri WHERE status_santri = 'Aktif' LIMIT 15");
} else {
    $res_s = $conn->query("SELECT s.id, s.nama_lengkap FROM buku_induk_santri s JOIN santri_orangtua_link sol ON s.id = sol.santri_id WHERE sol.orangtua_id = $orangtua_id");
}
if ($res_s) while($r = $res_s->fetch_assoc()) $santri_list[] = $r;

// 3. Proses Simpan Konfirmasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tanggal_bayar = $conn->real_escape_string($_POST['tanggal_bayar']);
    
    $bukti_name = '';
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $upload_dir = 'uploads/spp/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
        $bukti_name = 'spp_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_dir . $bukti_name);
    }

    $santri_ids = $_POST['santri_id'] ?? [];
    $jenis_pembayarans = $_POST['jenis_pembayaran'] ?? [];
    $keterangans = $_POST['keterangan_lainnya'] ?? [];
    $bulans = $_POST['bulan'] ?? [];
    $tahuns = $_POST['tahun'] ?? [];
    $jumlahs = $_POST['jumlah'] ?? [];

    $success_count = 0;
    $error_msg = "";

    for ($i = 0; $i < count($santri_ids); $i++) {
        $sid = (int)$santri_ids[$i];
        $jenis = $conn->real_escape_string($jenis_pembayarans[$i]);
        $ket = $conn->real_escape_string($keterangans[$i] ?? '');
        $bln = $conn->real_escape_string($bulans[$i]);
        $thn = $conn->real_escape_string($tahuns[$i]);
        $jml = (int)str_replace('.', '', $jumlahs[$i]); // Remove any dot formatting

        if ($sid > 0 && $jml > 0) {
            $sql = "INSERT INTO pembayaran_spp (santri_id, jenis_pembayaran, keterangan_lainnya, bulan, tahun, jumlah_bayar, tanggal_bayar, bukti_transfer, status) 
                    VALUES ($sid, '$jenis', '$ket', '$bln', '$thn', $jml, '$tanggal_bayar', '$bukti_name', 'Menunggu Verifikasi')";
            if ($conn->query($sql)) {
                $success_count++;
            } else {
                $error_msg = $conn->error;
            }
        }
    }

    if ($success_count > 0) {
        $pesan_sukses = "$success_count rincian pembayaran berhasil dikirim dan menunggu verifikasi bendahara.";
    } else {
        $pesan_error = "Gagal mengirim konfirmasi. " . $error_msg;
    }
}

// 4. Ambil Riwayat
if ($orangtua_id == 9999) {
    $sql_h = "SELECT p.*, s.nama_lengkap FROM pembayaran_spp p JOIN buku_induk_santri s ON p.santri_id = s.id ORDER BY p.created_at DESC LIMIT 50";
} else {
    $sql_h = "SELECT p.*, s.nama_lengkap FROM pembayaran_spp p 
              JOIN buku_induk_santri s ON p.santri_id = s.id 
              JOIN santri_orangtua_link sol ON s.id = sol.santri_id
              WHERE sol.orangtua_id = $orangtua_id ORDER BY p.created_at DESC";
}
$riwayat = $conn->query($sql_h)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Keuangan | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-orangtua" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800">Pembayaran Keuangan</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <!-- TAB NAVIGATION -->
            <div class="flex border-b border-gray-200 gap-3 mb-6 bg-white px-6 pt-4 rounded-xl shadow-sm">
                <button onclick="switchTab('form')" id="btn-tab-form" class="pb-3 px-4 text-sm font-bold border-b-2 border-purple-600 text-purple-600 flex items-center gap-2 transition">
                    <i class="fas fa-file-invoice-dollar text-purple-500"></i> Form Konfirmasi
                </button>
                <button onclick="switchTab('riwayat')" id="btn-tab-riwayat" class="pb-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-800 flex items-center gap-2 transition">
                    <i class="fas fa-history text-gray-400"></i> Riwayat Pembayaran
                </button>
            </div>

            <!-- FORMULIR KONFIRMASI -->
            <div id="tab-content-form" class="block">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-100"><h2 class="font-bold text-purple-800"><i class="fas fa-file-invoice-dollar mr-2"></i>Konfirmasi Pembayaran Keuangan</h2></div>
                <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                    <div id="rincian-container" class="space-y-4 mb-6">
                        <!-- Baris Rincian 1 -->
                        <div class="rincian-row relative grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 p-4 border border-gray-200 rounded-lg bg-white shadow-sm">
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Ananda</label>
                                <select name="santri_id[]" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-purple-500">
                                    <?php foreach($santri_list as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_lengkap']) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Pembayaran</label>
                                <select name="jenis_pembayaran[]" required onchange="toggleLainnyaRow(this)" class="jenis-select w-full px-3 py-2 border rounded-lg text-sm focus:ring-purple-500">
                                    <option value="Infaq Bulanan (SPP)">Infaq Bulanan (SPP)</option>
                                    <option value="Wakaf Pesantren">Wakaf Pesantren</option>
                                    <option value="Uang Kegiatan">Uang Kegiatan</option>
                                    <option value="Uang Asrama">Uang Asrama</option>
                                    <option value="Uang Seragam">Uang Seragam</option>
                                    <option value="Uang Buku">Uang Buku</option>
                                    <option value="lainnya">Lainnya...</option>
                                </select>
                                <input type="text" name="keterangan_lainnya[]" class="keterangan-input hidden w-full mt-2 px-3 py-2 border rounded-lg text-sm focus:ring-purple-500" placeholder="Sebutkan...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Bulan</label>
                                <select name="bulan[]" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-purple-500">
                                    <?php $bln=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; foreach($bln as $b): echo "<option value='$b' ".(date('F')==$b?'selected':'').">$b</option>"; endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                                <input type="number" name="tahun[]" value="<?= date('Y') ?>" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-purple-500">
                            </div>
                            <div class="lg:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Jumlah Bayar (Rp)</label>
                                <input type="text" name="jumlah[]" required oninput="formatRupiah(this); hitungTotal();" class="jumlah-input w-full px-3 py-2 border rounded-lg text-sm focus:ring-purple-500" placeholder="Contoh: 500.000">
                            </div>
                            <!-- Hapus Button for additional rows -->
                            <div class="lg:col-span-4 flex items-end justify-end">
                                <button type="button" onclick="hapusBaris(this)" class="btn-hapus hidden text-red-500 hover:text-red-700 text-sm font-semibold"><i class="fas fa-trash mr-1"></i> Hapus Rincian</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-8 flex justify-between items-center border-b pb-4">
                        <button type="button" onclick="tambahBaris()" class="bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold py-2 px-4 rounded-lg transition"><i class="fas fa-plus mr-1"></i> Tambah Rincian Lain</button>
                        <div class="text-right">
                            <span class="text-gray-500 text-sm">Total Keseluruhan:</span>
                            <div class="text-2xl font-bold text-purple-700">Rp <span id="total_tagihan">0</span></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 bg-purple-50/50 p-5 rounded-lg border border-purple-100">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                            <input type="date" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Bukti Transfer (Satu untuk semua rincian di atas)</label>
                            <input type="file" name="bukti_transfer" accept="image/*" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-600 file:text-white hover:file:bg-purple-700">
                        </div>
                    </div>
                    <div class="text-right"><button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-paper-plane mr-2"></i> Kirim Konfirmasi</button></div>
                </form>
            </div>

            </div> <!-- End Tab Form -->

            <!-- TABEL RIWAYAT -->
            <div id="tab-content-riwayat" class="hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Pembayaran</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jenis</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Periode</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Bukti</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($riwayat)): ?><tr><td colspan="5" class="text-center py-6 text-gray-400 italic">Belum ada riwayat pembayaran.</td></tr><?php else: foreach($riwayat as $r): 
                                $clr = 'text-amber-600'; if($r['status']=='Berhasil') $clr='text-emerald-600'; if($r['status']=='Ditolak') $clr='text-rose-600';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-gray-900"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td class="px-4 py-3 text-xs">
                                        <div class="font-semibold"><?= htmlspecialchars($r['jenis_pembayaran'] ?? 'Infaq Bulanan (SPP)') ?></div>
                                        <?php if(($r['jenis_pembayaran'] ?? '') == 'lainnya'): ?><div class="text-gray-500 italic"><?= htmlspecialchars($r['keterangan_lainnya']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?= $r['bulan'] ?> <?= $r['tahun'] ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold">Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center"><?php if($r['bukti_transfer']): ?><a href="uploads/spp/<?= $r['bukti_transfer'] ?>" target="_blank" class="text-purple-600 hover:text-purple-800"><i class="fas fa-image"></i></a><?php else: ?>-<?php endif; ?></td>
                                    <td class="px-4 py-3 text-center"><span class="text-xs font-bold <?= $clr ?>"><?= $r['status'] ?></span></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            </div> <!-- End Tab Riwayat -->
        </main>
    </div>
    <script>
    function toggleLainnyaRow(selectElement) {
        const inputLainnya = selectElement.nextElementSibling;
        if (selectElement.value === 'lainnya') {
            inputLainnya.classList.remove('hidden');
            inputLainnya.setAttribute('required', 'required');
        } else {
            inputLainnya.classList.add('hidden');
            inputLainnya.removeAttribute('required');
            inputLainnya.value = '';
        }
    }

    function tambahBaris() {
        const container = document.getElementById('rincian-container');
        const firstRow = container.querySelector('.rincian-row');
        const newRow = firstRow.cloneNode(true);
        
        // Reset values
        const inputs = newRow.querySelectorAll('input[type="text"], input[type="number"]');
        inputs.forEach(input => {
            if(input.name === 'tahun[]') input.value = '<?= date('Y') ?>';
            else input.value = '';
        });
        
        // Reset select "lainnya"
        const inputLainnya = newRow.querySelector('.keterangan-input');
        inputLainnya.classList.add('hidden');
        inputLainnya.removeAttribute('required');
        
        // Show hapus button
        const btnHapus = newRow.querySelector('.btn-hapus');
        btnHapus.classList.remove('hidden');
        
        container.appendChild(newRow);
        hitungTotal();
    }

    function hapusBaris(button) {
        const row = button.closest('.rincian-row');
        row.remove();
        hitungTotal();
    }

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

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        angka.value = rupiah;
    }

    function hitungTotal() {
        const inputs = document.querySelectorAll('.jumlah-input');
        let total = 0;
        inputs.forEach(input => {
            let val = input.value.replace(/\./g, '');
            if(val) total += parseInt(val);
        });
        
        if (total > 0) {
            let reverse = total.toString().split('').reverse().join(''),
                ribuan  = reverse.match(/\d{1,3}/g);
            let formatted = ribuan.join('.').split('').reverse().join('');
            document.getElementById('total_tagihan').innerText = formatted;
        } else {
            document.getElementById('total_tagihan').innerText = '0';
        }
    }

    function switchTab(tabName) {
        const btnForm = document.getElementById('btn-tab-form');
        const btnRiwayat = document.getElementById('btn-tab-riwayat');
        const contentForm = document.getElementById('tab-content-form');
        const contentRiwayat = document.getElementById('tab-content-riwayat');

        const activeClass = "pb-3 px-4 text-sm font-bold border-b-2 border-purple-600 text-purple-600 flex items-center gap-2 transition";
        const inactiveClass = "pb-3 px-4 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-gray-800 flex items-center gap-2 transition";

        if (tabName === 'form') {
            btnForm.className = activeClass;
            btnForm.querySelector('i').className = "fas fa-file-invoice-dollar text-purple-500";
            btnRiwayat.className = inactiveClass;
            btnRiwayat.querySelector('i').className = "fas fa-history text-gray-400";
            contentForm.classList.remove('hidden');
            contentForm.classList.add('block');
            contentRiwayat.classList.remove('block');
            contentRiwayat.classList.add('hidden');
        } else {
            btnRiwayat.className = activeClass;
            btnRiwayat.querySelector('i').className = "fas fa-history text-purple-500";
            btnForm.className = inactiveClass;
            btnForm.querySelector('i').className = "fas fa-file-invoice-dollar text-gray-400";
            contentRiwayat.classList.remove('hidden');
            contentRiwayat.classList.add('block');
            contentForm.classList.remove('block');
            contentForm.classList.add('hidden');
        }
    }
    </script>
</body>
</html>