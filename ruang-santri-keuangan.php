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
// Self-healing: Tambahkan kolom status jika belum ada
@$conn->query("ALTER TABLE penarikan_uang_saku ADD COLUMN status ENUM('Pengajuan', 'Disetujui', 'Ditolak') DEFAULT 'Pengajuan' AFTER keterangan");

// 2. Proses Pengajuan Penarikan oleh Santri
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajukan_penarikan'])) {
    $jumlah_ajukan = (int)$_POST['jumlah'];
    $tgl_ajukan = date('Y-m-d');
    $ket = $conn->real_escape_string($_POST['keterangan']);

    if ($jumlah_ajukan > 0) {
        $sql_ins = "INSERT INTO penarikan_uang_saku (santri_id, tanggal_penarikan, jumlah, keterangan, status) 
                    VALUES ($santri_id, '$tgl_ajukan', $jumlah_ajukan, '$ket', 'Pengajuan')";
        if ($conn->query($sql_ins)) {
            $pesan_sukses = "Pengajuan penarikan sebesar Rp " . number_format($jumlah_ajukan, 0, ',', '.') . " berhasil dikirim. Menunggu validasi Musyrif.";
        } else {
            $pesan_error = "Gagal mengirim pengajuan.";
        }
    }
}

// 3. Ambil data setoran uang saku dari orang tua (yang sudah divalidasi 'Berhasil')
$setoran_uang_saku = [];
$res_setoran = $conn->query("SELECT 'setoran' as jenis, tanggal_bayar as tanggal, jumlah, 'Setoran dari Orang Tua' as keterangan FROM uang_saku WHERE santri_id = $santri_id AND status = 'Berhasil'");
if ($res_setoran) while($r = $res_setoran->fetch_assoc()) $setoran_uang_saku[] = $r;

// 4. Ambil data penarikan uang saku oleh santri
$penarikan_uang_saku = [];
$res_penarikan = $conn->query("SELECT 'penarikan' as jenis, tanggal_penarikan as tanggal, jumlah, keterangan, status FROM penarikan_uang_saku WHERE santri_id = $santri_id");
if ($res_penarikan) while($r = $res_penarikan->fetch_assoc()) $penarikan_uang_saku[] = $r;

// 5. Gabungkan dan urutkan semua transaksi berdasarkan tanggal
$transaksi = array_merge($setoran_uang_saku, $penarikan_uang_saku);
usort($transaksi, function($a, $b) {
    return strtotime($a['tanggal']) - strtotime($b['tanggal']);
});

// 6. Hitung saldo (Hanya yang berstatus 'Berhasil' untuk setoran dan 'Disetujui' untuk penarikan)
$saldo_saat_ini = 0;
foreach ($transaksi as $t) {
    if ($t['jenis'] == 'setoran') {
        $saldo_saat_ini += $t['jumlah'];
    } elseif ($t['jenis'] == 'penarikan' && $t['status'] == 'Disetujui') {
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

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="font-bold text-gray-800">Saldo Uang Saku Saat Ini</h2>
                    <p class="text-3xl font-bold text-indigo-600">Rp <?= number_format($saldo_saat_ini, 0, ',', '.') ?></p>
                </div>
                <div class="border-t pt-4 mt-4">
                    <h3 class="text-sm font-bold text-gray-700 mb-3"><i class="fas fa-hand-holding-usd mr-2 text-indigo-500"></i>Ajukan Penarikan Uang Saku</h3>
                    <form action="" method="POST" class="flex flex-wrap gap-4 items-end">
                        <input type="hidden" name="ajukan_penarikan" value="1">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Jumlah (Rp)</label>
                            <input type="number" name="jumlah" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Contoh: 50000">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Keterangan / Keperluan</label>
                            <input type="text" name="keterangan" required class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Contoh: Beli perlengkapan mandi">
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition">
                            Kirim Pengajuan
                        </button>
                    </form>
                    <p class="text-[10px] text-gray-500 mt-2 italic">*Saldo Anda akan berkurang setelah disetujui oleh Musyrif.</p>
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
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Keterangan / Status</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase text-emerald-600">Debit (Masuk)</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase text-rose-600">Kredit (Keluar)</th>
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
                                $is_valid = ($is_setoran || ($t['jenis'] == 'penarikan' && $t['status'] == 'Disetujui'));
                                if ($is_valid) {
                                    $saldo_akumulatif += ($is_setoran ? $t['jumlah'] : -$t['jumlah']);
                                }
                                $status_badge = "";
                                if (!$is_setoran) {
                                    $clr = $t['status'] == 'Disetujui' ? 'bg-emerald-100 text-emerald-700' : ($t['status'] == 'Ditolak' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700');
                                    $status_badge = "<span class='px-2 py-0.5 rounded text-[10px] font-bold $clr'>{$t['status']}</span>";
                                }
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm"><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium text-gray-900"><?= htmlspecialchars($t['keterangan']) ?></div>
                                        <?= $status_badge ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-emerald-600">
                                        <?= $is_setoran ? 'Rp ' . number_format($t['jumlah'], 0, ',', '.') : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-semibold text-rose-600">
                                        <?= !$is_setoran ? 'Rp ' . number_format($t['jumlah'], 0, ',', '.') : '-' ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm font-bold <?= $is_valid ? 'text-gray-900' : 'text-gray-300' ?>">
                                        Rp <?= number_format($saldo_akumulatif, 0, ',', '.') ?>
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