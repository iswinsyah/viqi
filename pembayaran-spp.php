<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$active_menu = 'pembayaran_spp';

// 1. Buat Tabel Otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS pembayaran_spp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
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

// 2. Ambil daftar santri yang terhubung
$santri_list = [];
if ($orangtua_id == 9999) {
    $res_s = $conn->query("SELECT id, nama_lengkap FROM buku_induk_santri WHERE status_santri = 'Aktif' LIMIT 15");
} else {
    $res_s = $conn->query("SELECT id, nama_lengkap FROM buku_induk_santri WHERE id_orangtua = $orangtua_id");
}
if ($res_s) while($r = $res_s->fetch_assoc()) $santri_list[] = $r;

// 3. Proses Simpan Konfirmasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $santri_id_post = (int)$_POST['santri_id'];
    $bulan = $conn->real_escape_string($_POST['bulan']);
    $tahun = $conn->real_escape_string($_POST['tahun']);
    $jumlah = (int)$_POST['jumlah'];
    $tanggal_bayar = $conn->real_escape_string($_POST['tanggal_bayar']);
    
    $bukti_name = '';
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $upload_dir = 'uploads/spp/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
        $bukti_name = 'spp_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_dir . $bukti_name);
    }

    $sql = "INSERT INTO pembayaran_spp (santri_id, bulan, tahun, jumlah, tanggal_bayar, bukti_transfer) 
            VALUES ($santri_id_post, '$bulan', '$tahun', $jumlah, '$tanggal_bayar', '$bukti_name')";
    
    if ($conn->query($sql)) $pesan_sukses = "Konfirmasi pembayaran berhasil dikirim!";
    else $pesan_error = "Gagal mengirim konfirmasi: " . $conn->error;
}

// 4. Ambil Riwayat
if ($orangtua_id == 9999) {
    $sql_h = "SELECT p.*, s.nama_lengkap FROM pembayaran_spp p JOIN buku_induk_santri s ON p.santri_id = s.id ORDER BY p.created_at DESC LIMIT 50";
} else {
    $sql_h = "SELECT p.*, s.nama_lengkap FROM pembayaran_spp p 
              JOIN buku_induk_santri s ON p.santri_id = s.id 
              WHERE s.id_orangtua = $orangtua_id ORDER BY p.created_at DESC";
}
$riwayat = $conn->query($sql_h)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran SPP | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-orangtua" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800">Pembayaran SPP</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <!-- FORMULIR KONFIRMASI -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-purple-50 border-b border-purple-100"><h2 class="font-bold text-purple-800"><i class="fas fa-file-invoice-dollar mr-2"></i>Konfirmasi Pembayaran Baru</h2></div>
                <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Ananda</label>
                            <select name="santri_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500">
                                <?php foreach($santri_list as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_lengkap']) ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Untuk Bulan</label>
                            <select name="bulan" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500">
                                <?php $bln=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']; foreach($bln as $b): echo "<option value='$b' ".(date('F')==$b?'selected':'').">$b</option>"; endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                            <input type="number" name="tahun" value="<?= date('Y') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Bayar (Rp)</label>
                            <input type="number" name="jumlah" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500" placeholder="Contoh: 500000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                            <input type="date" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Bukti Transfer</label>
                            <input type="file" name="bukti_transfer" accept="image/*" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100">
                        </div>
                    </div>
                    <div class="text-right"><button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-paper-plane mr-2"></i> Kirim Konfirmasi</button></div>
                </form>
            </div>

            <!-- TABEL RIWAYAT -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Pembayaran</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri</th>
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
        </main>
    </div>
</body>
</html>