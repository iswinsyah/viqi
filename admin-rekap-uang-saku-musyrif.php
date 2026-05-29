<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'rekap_uang_saku_musyrif';
$musyrif_id = $_SESSION['ustadz_id'];

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

// 2. Proses Simpan Penarikan Uang Saku
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tarik_uang_saku'])) {
    $santri_id_post = (int)$_POST['santri_id'];
    $jumlah = (int)$_POST['jumlah'];
    $tanggal_penarikan = $conn->real_escape_string($_POST['tanggal_penarikan']);
    $keterangan = $conn->real_escape_string($_POST['keterangan']);

    // Cek saldo santri sebelum penarikan
    $total_setoran = $conn->query("SELECT SUM(jumlah) FROM uang_saku WHERE santri_id = $santri_id_post AND status = 'Berhasil'")->fetch_row()[0] ?? 0;
    $total_penarikan = $conn->query("SELECT SUM(jumlah) FROM penarikan_uang_saku WHERE santri_id = $santri_id_post")->fetch_row()[0] ?? 0;
    $saldo_saat_ini = $total_setoran - $total_penarikan;

    if ($jumlah > $saldo_saat_ini) {
        $pesan_error = "Gagal: Jumlah penarikan melebihi saldo santri. Saldo saat ini: Rp " . number_format($saldo_saat_ini, 0, ',', '.');
    } else {
        $sql = "INSERT INTO penarikan_uang_saku (santri_id, musyrif_id, tanggal_penarikan, jumlah, keterangan) 
                VALUES ($santri_id_post, $musyrif_id, '$tanggal_penarikan', $jumlah, '$keterangan')";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Penarikan uang saku berhasil dicatat!";
        } else {
            $pesan_error = "Gagal mencatat penarikan: " . $conn->error;
        }
    }
}

// 3. Ambil daftar santri aktif untuk dropdown dan rekap
$santri_list_full = [];
$res_santri = $conn->query("SELECT id, nama_lengkap, kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
if ($res_santri) while($r = $res_santri->fetch_assoc()) $santri_list_full[] = $r;

// 4. Hitung rekap uang saku untuk setiap santri
$rekap_uang_saku = [];
foreach ($santri_list_full as $santri) {
    $santri_id = $santri['id'];
    $total_setoran = $conn->query("SELECT SUM(jumlah) FROM uang_saku WHERE santri_id = $santri_id AND status = 'Berhasil'")->fetch_row()[0] ?? 0;
    $total_penarikan = $conn->query("SELECT SUM(jumlah) FROM penarikan_uang_saku WHERE santri_id = $santri_id")->fetch_row()[0] ?? 0;
    $saldo = $total_setoran - $total_penarikan;

    $rekap_uang_saku[] = [
        'id' => $santri_id,
        'nama_lengkap' => $santri['nama_lengkap'],
        'kelas_sekarang' => $santri['kelas_sekarang'],
        'total_setoran' => $total_setoran,
        'total_penarikan' => $total_penarikan,
        'saldo' => $saldo
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Uang Saku Santri | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800">Rekap Uang Saku Santri</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-wallet text-cyan-600 mr-2"></i>Rekap Uang Saku Santri</h1>
                <p class="text-sm text-gray-500 mt-1">Kelola dan pantau riwayat uang saku seluruh santri.</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <!-- FORMULIR PENARIKAN UANG SAKU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas fa-hand-holding-usd mr-2"></i>Catat Penarikan Uang Saku</h2></div>
                <form action="" method="POST" class="p-6">
                    <input type="hidden" name="tarik_uang_saku" value="1">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Santri</label>
                            <select name="santri_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <option value="">-- Pilih Santri --</option>
                                <?php foreach($santri_list_full as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_lengkap']) ?> (<?= htmlspecialchars($s['kelas_sekarang']) ?>)</option><?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Penarikan (Rp)</label>
                            <input type="number" name="jumlah" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: 50000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Penarikan</label>
                            <input type="date" name="tanggal_penarikan" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                        </div>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan (Opsional)</label>
                        <input type="text" name="keterangan" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Beli buku, Jajan di kantin">
                    </div>
                    <div class="text-right">
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Catat Penarikan</button>
                    </div>
                </form>
            </div>

            <!-- TABEL REKAP UANG SAKU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Rekapitulasi Uang Saku Santri</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri & Kelas</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Setoran</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Penarikan</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Saldo Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            <?php if(empty($rekap_uang_saku)): ?>
                                <tr><td colspan="4" class="text-center py-10 text-gray-400 italic">Belum ada data santri aktif.</td></tr>
                            <?php else: foreach($rekap_uang_saku as $r): 
                                $saldo_color = $r['saldo'] >= 0 ? 'text-emerald-600' : 'text-rose-600';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($r['nama_lengkap']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($r['kelas_sekarang']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-emerald-700">Rp <?= number_format($r['total_setoran'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-right font-semibold text-rose-700">Rp <?= number_format($r['total_penarikan'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-right font-bold <?= $saldo_color ?>">Rp <?= number_format($r['saldo'], 0, ',', '.') ?></td>
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