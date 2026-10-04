<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'rekap_uang_saku';

// 1. Ambil Data yang HANYA berstatus 'Berhasil' (Sudah divalidasi Yayasan)
$sql = "SELECT u.*, s.nama_lengkap, s.kelas_sekarang 
        FROM uang_saku u 
        JOIN buku_induk_santri s ON u.santri_id = s.id 
        WHERE u.status = 'Berhasil'
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);
$data_us = ($result) ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Uang Saku Valid | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800">Rekap Data Uang Saku (Tervalidasi)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-wallet text-emerald-600 mr-2"></i>Data Uang Saku Berhasil</h1>
                <p class="text-sm text-gray-500 mt-1">Hanya menampilkan data yang telah disetujui (divalidasi) oleh pihak Yayasan.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Uang Saku Tervalidasi</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri & Kelas</th>
                                <th class="px-4 py-3">Tanggal Kirim</th>
                                <th class="px-4 py-3">Jumlah</th>
                                <th class="px-4 py-3 text-center">Bukti</th>
                                <th class="px-4 py-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if(empty($data_us)): ?><tr><td colspan="5" class="text-center py-10 text-gray-400 italic">Belum ada data uang saku yang tervalidasi.</td></tr><?php else: foreach($data_us as $r): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($r['kelas_sekarang']) ?></div>
                                    </td>
                                    <td class="px-4 py-3"><?= date('d/m/Y', strtotime($r['tanggal_bayar'])) ?></td>
                                    <td class="px-4 py-3 font-semibold text-emerald-700">Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center"><?php if($r['bukti_transfer']): ?><a href="uploads/uang_saku/<?= $r['bukti_transfer'] ?>" target="_blank" class="text-indigo-600 hover:text-indigo-800"><i class="fas fa-image text-lg"></i></a><?php else: ?>-<?php endif; ?></td>
                                    <td class="px-4 py-3 text-xs text-gray-600"><?= htmlspecialchars($r['catatan_admin'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
    </script>
</body>
</html>