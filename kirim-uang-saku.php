<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$is_super_admin = ($orangtua_id == 9999 || (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi'])));
$active_menu = 'kirim_uang_saku';

// 1. Buat Tabel Otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS uang_saku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    jumlah INT,
    tanggal_bayar DATE,
    bukti_transfer VARCHAR(255),
    status ENUM('Menunggu Verifikasi', 'Berhasil', 'Ditolak') DEFAULT 'Menunggu Verifikasi',
    catatan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE
)");

// 2. Ambil daftar santri yang terhubung & santri aktif persisten
$santri_list = getOrangtuaSantriList($conn, $orangtua_id);
$active_santri = getOrangtuaActiveSantri($conn, $orangtua_id, $santri_list);
$selected_santri_id = $active_santri ? (int)$active_santri['id'] : 0;

// 3. Proses Simpan Konfirmasi
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $santri_id_post = (int)$_POST['santri_id'];
    $jumlah = (int)$_POST['jumlah'];
    $tanggal_bayar = $conn->real_escape_string($_POST['tanggal_bayar']);
    
    $bukti_name = '';
    if (isset($_FILES['bukti_transfer']) && $_FILES['bukti_transfer']['error'] == 0) {
        $upload_dir = 'uploads/uang_saku/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION);
        $bukti_name = 'us_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $upload_dir . $bukti_name);
    }

    $sql = "INSERT INTO uang_saku (santri_id, jumlah, tanggal_bayar, bukti_transfer) 
            VALUES ($santri_id_post, $jumlah, '$tanggal_bayar', '$bukti_name')";
    
    if ($conn->query($sql)) $pesan_sukses = "Konfirmasi kirim uang saku berhasil dikirim!";
    else $pesan_error = "Gagal mengirim konfirmasi: " . $conn->error;
}

// 4. Ambil Riwayat
if ($orangtua_id == 9999) {
    if ($selected_santri_id > 0) {
        $sql_h = "SELECT u.*, s.nama_lengkap FROM uang_saku u JOIN buku_induk_santri s ON u.santri_id = s.id WHERE u.santri_id = $selected_santri_id ORDER BY u.created_at DESC";
    } else {
        $sql_h = "SELECT u.*, s.nama_lengkap FROM uang_saku u JOIN buku_induk_santri s ON u.santri_id = s.id ORDER BY u.created_at DESC LIMIT 50";
    }
} else {
    if ($selected_santri_id > 0) {
        $sql_h = "SELECT u.*, s.nama_lengkap FROM uang_saku u 
                  JOIN buku_induk_santri s ON u.santri_id = s.id 
                  JOIN santri_orangtua_link sol ON s.id = sol.santri_id
                  WHERE sol.orangtua_id = $orangtua_id AND u.santri_id = $selected_santri_id ORDER BY u.created_at DESC";
    } else {
        $sql_h = "SELECT u.*, s.nama_lengkap FROM uang_saku u 
                  JOIN buku_induk_santri s ON u.santri_id = s.id 
                  JOIN santri_orangtua_link sol ON s.id = sol.santri_id
                  WHERE sol.orangtua_id = $orangtua_id ORDER BY u.created_at DESC";
    }
}
$riwayat = $conn->query($sql_h)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kirim Uang Saku | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-orangtua" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800">Kirim Uang Saku</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <!-- HEADER CARD: INFO & SELECTOR ANANDA -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-sm border border-teal-100 flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-200 flex items-center justify-center text-[#0b8478] shadow-xs">
                        <i class="fas fa-wallet text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-gray-900 leading-tight">Kirim Uang Saku Ananda</h1>
                        <p class="text-xs text-gray-500">Konfirmasi top up saldo & rekap transaksi uang saku santri</p>
                    </div>
                </div>
                
                <!-- SELECTOR ANANDA (MENDUKUNG 1 ATAU BANYAK ANAK BERSAUDARA) -->
                <div>
                    <?php 
                    $is_locked = !empty(getLockedSantriIds($conn, $orangtua_id));
                    ?>
                    <?php if ($is_locked && count($santri_list) > 1): ?>
                        <div class="flex items-center gap-2 flex-wrap">
                            <form method="GET" class="flex items-center gap-2">
                                <span class="text-xs font-bold text-teal-800 uppercase tracking-wider flex items-center gap-1.5 whitespace-nowrap">
                                    <i class="fas fa-child text-[#0b8478]"></i> Ananda:
                                </span>
                                <select name="santri_id" onchange="this.form.submit()" class="bg-teal-50/50 border border-teal-300 text-teal-950 font-bold text-xs rounded-xl px-3.5 py-2 focus:ring-2 focus:ring-teal-500 shadow-xs cursor-pointer">
                                    <?php foreach ($santri_list as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($s['id'] == $selected_santri_id) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['nama_lengkap']) ?> (<?= htmlspecialchars($s['kelas_sekarang'] ?? 'Santri') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                            <button type="button" onclick="openPilihAnandaModal()" class="text-[10px] text-teal-800 hover:text-teal-950 bg-teal-50 hover:bg-teal-100 border border-teal-200 px-2.5 py-1.5 rounded-xl font-bold flex items-center gap-1 transition" title="Kelola Ananda Bersaudara">
                                <i class="fas fa-users text-[10px]"></i> Kelola Saudara
                            </button>
                            <?php if ($is_super_admin): ?>
                                <a href="?action=unlock_ananda" onclick="return confirm('Reset kunci ananda? Anda akan bisa memilih ananda kembali (Khusus Super Admin).')" class="text-[10px] text-rose-600 hover:text-rose-800 underline font-semibold ml-1" title="Reset Kunci (Khusus Super Admin)">
                                    <i class="fas fa-key text-[9px]"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($is_locked && $active_santri): ?>
                        <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-teal-50 border border-teal-200 text-teal-900 font-bold text-xs shadow-xs flex-wrap">
                            <i class="fas fa-lock text-[#0b8478]"></i>
                            <span>Ananda: <strong class="text-teal-950"><?= htmlspecialchars($active_santri['nama_lengkap']) ?></strong></span>
                            <span class="text-[10px] bg-teal-200/80 text-teal-900 px-2 py-0.5 rounded-full font-bold">Terkunci</span>
                            <button type="button" onclick="openPilihAnandaModal()" class="text-[10px] text-teal-700 hover:text-teal-900 underline font-semibold ml-1.5 flex items-center gap-1" title="Tambah Saudara Kandung">
                                <i class="fas fa-user-plus text-[9px]"></i> Tambah Saudara
                            </button>
                            <?php if ($is_super_admin): ?>
                                <a href="?action=unlock_ananda" onclick="return confirm('Reset kunci ananda? Anda akan bisa memilih ananda kembali (Khusus Super Admin).')" class="text-[10px] text-rose-600 hover:text-rose-800 underline font-semibold ml-1" title="Reset Kunci (Khusus Super Admin)">
                                    <i class="fas fa-key text-[9px]"></i> Reset
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- BELUM TERKUNCI: 1 KOLOM / TOMBOL PILIH ANANDA (BUKA MODAL PILIH 1 ATAU LEBIH) -->
                        <button type="button" onclick="openPilihAnandaModal()" class="px-4 py-2 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-2 transition cursor-pointer">
                            <i class="fas fa-child"></i>
                            <span>Pilih Ananda</span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FORMULIR KONFIRMASI -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-teal-50/70 border-b border-teal-100"><h2 class="font-bold text-teal-900"><i class="fas fa-wallet text-[#0b8478] mr-2"></i>Konfirmasi Kirim Uang Saku</h2></div>
                <form action="" method="POST" enctype="multipart/form-data" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ananda</label>
                            <?php if (count($santri_list) === 1): ?>
                                <input type="hidden" name="santri_id" value="<?= $santri_list[0]['id'] ?>">
                                <div class="w-full px-4 py-2 bg-teal-50/70 border border-teal-200 rounded-lg text-teal-950 font-bold text-sm flex items-center gap-2">
                                    <i class="fas fa-lock text-[#0b8478] text-xs"></i>
                                    <span><?= htmlspecialchars($santri_list[0]['nama_lengkap']) ?></span>
                                </div>
                            <?php else: ?>
                                <select name="santri_id" required class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500 font-bold text-sm">
                                    <?php foreach($santri_list as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($s['id'] == $selected_santri_id) ? 'selected' : '' ?>><?= htmlspecialchars($s['nama_lengkap']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Uang Saku (Rp)</label>
                            <input type="number" name="jumlah" required class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500" placeholder="Contoh: 100000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Transfer</label>
                            <input type="date" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Bukti Transfer</label>
                            <input type="file" name="bukti_transfer" accept="image/*" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#0b8478] file:text-white hover:file:bg-teal-700">
                        </div>
                    </div>
                    <div class="text-right"><button type="submit" class="bg-[#0b8478] hover:bg-teal-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-paper-plane mr-2"></i> Kirim Konfirmasi</button></div>
                </form>
            </div>

            <!-- TABEL RIWAYAT -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Kirim Uang Saku</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jumlah</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Bukti</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($riwayat)): ?><tr><td colspan="5" class="text-center py-6 text-gray-400 italic">Belum ada riwayat kirim uang saku.</td></tr><?php else: foreach($riwayat as $r): 
                                $clr = 'text-amber-600'; if($r['status']=='Berhasil') $clr='text-emerald-600'; if($r['status']=='Ditolak') $clr='text-rose-600';
                            ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-gray-900"><?= htmlspecialchars($r['nama_lengkap']) ?></td>
                                    <td class="px-4 py-3 text-sm"><?= date('d/m/Y', strtotime($r['tanggal_bayar'])) ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold">Rp <?= number_format($r['jumlah'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-3 text-center"><?php if($r['bukti_transfer']): ?><a href="uploads/uang_saku/<?= $r['bukti_transfer'] ?>" target="_blank" class="text-[#0b8478] hover:text-teal-800"><i class="fas fa-image"></i></a><?php else: ?>-<?php endif; ?></td>
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