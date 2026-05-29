<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$active_menu = 'tabel_keuangan';

// 1. Buat Tabel penarikan_uang_saku jika belum ada (self-healing)
$conn->query("CREATE TABLE IF NOT EXISTS penarikan_uang_saku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    musyrif_id INT NULL, -- Musyrif yang mencairkan uang saku
    tanggal_penarikan DATE NOT NULL,
    jumlah INT NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE,
    FOREIGN KEY (musyrif_id) REFERENCES akun_ustadz(id) ON DELETE SET NULL
)");

// 2. Ambil data setoran uang saku dari orang tua (yang sudah divalidasi 'Berhasil')
$setoran_uang_saku = [];
$res_setoran = $conn->query("SELECT 'setoran' as jenis, tanggal_bayar as tanggal, jumlah, 'Setoran dari Orang Tua' as keterangan FROM uang_saku WHERE santri_id = $santri_id AND status = 'Berhasil'");
if ($res_setoran) while($r = $res_setoran->fetch_assoc()) $setoran_uang_saku[] = $r;

// 3. Ambil data penarikan uang saku oleh santri
$penarikan_uang_saku = [];
$res_penarikan = $conn->query("SELECT 'penarikan' as jenis, tanggal_penarikan as tanggal, jumlah, keterangan FROM penarikan_uang_saku WHERE santri_id = $santri_id");
if ($res_penarikan) while($r = $res_penarikan->fetch_assoc()) $penarikan_uang_saku[] = $r;

// 4. Gabungkan dan urutkan semua transaksi berdasarkan tanggal
$transaksi = array_merge($setoran_uang_saku, $penarikan_uang_saku);
usort($transaksi, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});

// 5. Hitung saldo
$saldo_saat_ini = 0;
foreach ($transaksi as $t) {
    if ($t['jenis'] == 'setoran') {
        $saldo_saat_ini += $t['jumlah'];
    } else {
        $saldo_saat_ini -= $t['jumlah'];
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabel Keuangan | Ruang Santri</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-santri" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="font-semibold text-sm text-gray-700 hidden sm:block">Selamat Datang, <?= htmlspecialchars($santri_nama) ?></span>
                <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($santri_nama, 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-money-check-alt text-indigo-600 mr-2"></i>Tabel Keuangan Uang Saku</h1>
                <p class="text-gray-500 mt-1">Pantau riwayat setoran dan penarikan uang sakumu di sini.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold text-gray-800">Saldo Uang Saku Saat Ini</h2>
                    <p class="text-3xl font-bold text-indigo-600">Rp <?= number_format($saldo_saat_ini, 0, ',', '.') ?></p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                    <h2 class="font-bold text-gray-800">Riwayat Transaksi</h2>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jenis Transaksi</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Saldo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            $saldo_akumulatif = 0;
                            if (empty($transaksi)): ?>
                                <tr><td colspan="5" class="text-center py-6 text-gray-400 italic">Belum ada riwayat transaksi uang saku.</td></tr>
                            <?php else: foreach($transaksi as $t): 
                                $is_setoran = ($t['jenis'] == 'setoran');
                                $saldo_akumulatif += ($is_setoran ? $t['jumlah'] : -$t['jumlah']);
                                $color_class = $is_setoran ? 'text-emerald-600' : 'text-rose-600';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                    <td class="px-4 py-3 text-sm font-bold <?= $color_class ?>"><?= htmlspecialchars(ucfirst($t['jenis'])) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($t['keterangan']) ?></td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold <?= $color_class ?>">
                                        <?= $is_setoran ? '+' : '-' ?> Rp <?= number_format($t['jumlah'], 0, ',', '.') ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-bold">Rp <?= number_format($saldo_akumulatif, 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-santri');
            const openBtn = document.getElementById('open-sidebar-santri');
            const overlay = document.getElementById('sidebar-overlay-santri');
            if(openBtn) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
            if(overlay) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); });
        });
    </script>
</body>
</html>