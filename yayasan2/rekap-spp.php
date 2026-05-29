<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'rekap_keuangan';

// 1. Proses Validasi Pembayaran
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = $conn->real_escape_string($_POST['status']);
    $catatan = $conn->real_escape_string($_POST['catatan_admin']);
    
    $sql = "UPDATE pembayaran_spp SET status = '$status', catatan_admin = '$catatan' WHERE id = $id";
    if ($conn->query($sql)) {
        $pesan_sukses = "Validasi pembayaran berhasil diperbarui!";
    } else {
        $pesan_error = "Gagal memperbarui status: " . $conn->error;
    }
}

// 2. Ambil Seluruh Data Pembayaran
$sql = "SELECT p.*, s.nama_lengkap, s.kelas_sekarang 
        FROM pembayaran_spp p 
        JOIN buku_induk_santri s ON p.santri_id = s.id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
$data_spp = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Pembayaran Keuangan | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800">Rekap Data Pembayaran Keuangan</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-amber-50"><h2 class="font-bold text-amber-800">Semua Konfirmasi Pembayaran Keuangan</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr class="text-left text-xs font-bold text-gray-500 uppercase">
                                <th class="px-4 py-3">Santri & Kelas</th>
                                <th class="px-4 py-3">Jenis Pembayaran</th>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-4 py-3">Jumlah</th>
                                <th class="px-4 py-3 text-center">Bukti</th>
                                <th class="px-4 py-3">Status Saat Ini</th>
                                <th class="px-4 py-3 text-center">Validasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if(empty($data_spp)): ?><tr><td colspan="6" class="text-center py-10 text-gray-400 italic">Belum ada data pembayaran masuk.</td></tr><?php else: foreach($data_spp as $r): 
                                $clr = 'text-amber-600'; if($r['status']=='Berhasil') $clr='text-emerald-600'; if($r['status']=='Ditolak') $clr='text-rose-600';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($r['kelas_sekarang']) ?></div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-semibold"><?= htmlspecialchars($r['jenis_pembayaran'] ?? 'Infaq Bulanan (SPP)') ?></div>
                                        <?php if(($r['jenis_pembayaran'] ?? '') == 'lainnya'): ?><div class="text-[10px] text-gray-500 italic"><?= htmlspecialchars($r['keterangan_lainnya']) ?></div><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3"><?= $r['bulan'] ?> <?= $r['tahun'] ?></td>
                                    <td class="px-4 py-3 font-semibold">Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center"><?php if($r['bukti_transfer']): ?><a href="../uploads/spp/<?= $r['bukti_transfer'] ?>" target="_blank" class="text-amber-600 hover:text-amber-800"><i class="fas fa-image text-lg"></i></a><?php else: ?>-<?php endif; ?></td>
                                    <td class="px-4 py-3 font-bold <?= $clr ?>"><?= $r['status'] ?></td>
                                    <td class="px-4 py-3">
                                        <form action="" method="POST" class="flex items-center gap-2">
                                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                            <select name="status" class="text-xs border rounded p-1 focus:ring-amber-500">
                                                <option value="Menunggu Verifikasi" <?= $r['status']=='Menunggu Verifikasi'?'selected':'' ?>>Pending</option>
                                                <option value="Berhasil" <?= $r['status']=='Berhasil'?'selected':'' ?>>Berhasil</option>
                                                <option value="Ditolak" <?= $r['status']=='Ditolak'?'selected':'' ?>>Tolak</option>
                                            </select>
                                            <input type="text" name="catatan_admin" value="<?= htmlspecialchars($r['catatan_admin'] ?? '') ?>" placeholder="Catatan..." class="text-xs border rounded p-1 w-24">
                                            <button type="submit" name="update_status" class="bg-amber-500 text-white p-1.5 rounded hover:bg-amber-600" title="Simpan Validasi"><i class="fas fa-save"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); 
        document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });
    </script>
</body>
</html>