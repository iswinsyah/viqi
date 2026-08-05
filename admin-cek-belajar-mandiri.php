<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Self-healing tables for Belajar Mandiri Monitoring
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_belajar_mandiri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    tanggal DATE NOT NULL,
    sesi_belajar VARCHAR(100) DEFAULT 'Malam (19.30 - 21.00 WIB)',
    lokasi_belajar VARCHAR(150) DEFAULT 'Ruang Asrama / Kelas',
    catatan_musyrif TEXT NULL,
    foto_bukti VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS jurnal_belajar_mandiri_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jurnal_id INT NOT NULL,
    santri_id INT NOT NULL,
    status_kehadiran ENUM('Hadir & Fokus', 'Izin / Sakit', 'Mengantuk / Lelah', 'Tidak Hadir') DEFAULT 'Hadir & Fokus',
    materi_dipelajari VARCHAR(255) NULL,
    catatan_santri TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jurnal (jurnal_id),
    KEY idx_santri (santri_id)
)");

// Check user role
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$is_super_admin = ($ustadz_id === 9999);

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_admin_or_kepala = $is_super_admin || !empty(array_intersect($norm_roles, ['super_admin', 'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah']));
$is_ka_rijal = !empty(array_intersect($norm_roles, ['kepala_asrama', 'kepala_asrama_rijal']));
$is_ka_nisa = !empty(array_intersect($norm_roles, ['kepala_asrama_nisa']));

// Fetch santri binaan
$santri_binaan = [];
if ($is_admin_or_kepala) {
    // Admin / Super Admin can see all active santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, jenis_kelamin FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} elseif ($is_ka_rijal) {
    // Kepala Asrama Rijal: can see all active male santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, jenis_kelamin FROM buku_induk_santri WHERE status_santri = 'Aktif' AND (jenis_kelamin = 'Laki-laki' OR jenis_kelamin IS NULL) ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} elseif ($is_ka_nisa) {
    // Kepala Asrama Nisa: can see all active female santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, jenis_kelamin FROM buku_induk_santri WHERE status_santri = 'Aktif' AND jenis_kelamin = 'Perempuan' ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} else {
    // Musyrif / Musyrifah: see only their assigned halaqoh group members
    $res_sb = $conn->query("
        SELECT DISTINCT s.id, s.nama_lengkap, s.nis, s.kelas_sekarang, s.jenis_kelamin 
        FROM buku_induk_santri s 
        JOIN halaqoh_anggota a ON s.id = a.santri_id 
        JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE g.musyrif_id = $ustadz_id AND s.status_santri = 'Aktif'
        ORDER BY s.nama_lengkap ASC
    ");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
}

$pesan_sukses = '';
$pesan_error = '';

// Handle Form Submit (Simpan Laporan Cek Belajar Mandiri)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_cek_belajar'])) {
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $sesi_belajar = $conn->real_escape_string($_POST['sesi_belajar']);
    $lokasi_belajar = $conn->real_escape_string($_POST['lokasi_belajar']);
    $catatan_musyrif = $conn->real_escape_string($_POST['catatan_musyrif'] ?? '');
    $foto_bukti = $conn->real_escape_string($_POST['foto_bukti'] ?? '');

    if (empty($tanggal) || empty($sesi_belajar)) {
        $pesan_error = "Tanggal dan Sesi Belajar wajib diisi!";
    } else {
        // Insert Header
        $sql_h = "INSERT INTO jurnal_belajar_mandiri (ustadz_id, tanggal, sesi_belajar, lokasi_belajar, catatan_musyrif, foto_bukti)
                  VALUES ($ustadz_id, '$tanggal', '$sesi_belajar', '$lokasi_belajar', '$catatan_musyrif', '$foto_bukti')";
        
        if ($conn->query($sql_h)) {
            $jurnal_id = $conn->insert_id;
            
            // Insert Details per santri
            $status_arr = $_POST['status_kehadiran'] ?? [];
            $materi_arr = $_POST['materi_dipelajari'] ?? [];

            foreach ($santri_binaan as $st) {
                $sid = $st['id'];
                $st_val = isset($status_arr[$sid]) ? $conn->real_escape_string($status_arr[$sid]) : 'Hadir & Fokus';
                $mat_val = isset($materi_arr[$sid]) ? $conn->real_escape_string($materi_arr[$sid]) : '';

                $conn->query("INSERT INTO jurnal_belajar_mandiri_detail (jurnal_id, santri_id, status_kehadiran, materi_dipelajari)
                              VALUES ($jurnal_id, $sid, '$st_val', '$mat_val')");
            }

            $pesan_sukses = "Laporan Cek Santri Belajar Mandiri berhasil disimpan & dicatat!";
        } else {
            $pesan_error = "Gagal menyimpan laporan: " . $conn->error;
        }
    }
}

// Fetch History Logs
$history_logs = [];
$where_hist = $is_admin_or_kepala ? "1=1" : "j.ustadz_id = $ustadz_id";

$res_hist = $conn->query("
    SELECT j.*, u.nama as nama_musyrif, 
           COUNT(d.id) as total_santri_dicek,
           SUM(CASE WHEN d.status_kehadiran = 'Hadir & Fokus' THEN 1 ELSE 0 END) as total_hadir,
           SUM(CASE WHEN d.status_kehadiran = 'Mengantuk / Lelah' THEN 1 ELSE 0 END) as total_mengantuk,
           SUM(CASE WHEN d.status_kehadiran = 'Tidak Hadir' THEN 1 ELSE 0 END) as total_alpa
    FROM jurnal_belajar_mandiri j 
    LEFT JOIN akun_ustadz u ON j.ustadz_id = u.id 
    LEFT JOIN jurnal_belajar_mandiri_detail d ON j.id = d.jurnal_id 
    WHERE $where_hist 
    GROUP BY j.id 
    ORDER BY j.tanggal DESC, j.created_at DESC 
    LIMIT 30
");

if ($res_hist) {
    while ($r = $res_hist->fetch_assoc()) $history_logs[] = $r;
}

$active_menu = 'cek_belajar_mandiri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Santri Belajar Mandiri | Ruang Musyrif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-book-reader mr-1"></i>Belajar Mandiri
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-amber-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-md shadow-amber-200">
                            <i class="fas fa-clipboard-check text-lg"></i>
                        </div>
                        <span>Formulir Cek Santri Belajar Mandiri</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Pemantauan dan penegakan kedisiplinan belajar mandiri santri binaan oleh Musyrif Pembina</p>
                </div>
            </div>

            <?= $pesan_sukses ? "<div class='bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-check-circle mr-2 text-emerald-500'></i>$pesan_sukses</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>
            <?= $pesan_error ? "<div class='bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-exclamation-triangle mr-2 text-rose-500'></i>$pesan_error</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>

            <!-- FORMULIR CHEKING -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-orange-50 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <i class="fas fa-edit text-amber-600"></i>
                        <span>Input Sesi Pemantauan Hari Ini</span>
                    </h2>
                    <span class="text-xs font-bold text-amber-800 bg-amber-100 px-3 py-1 rounded-full">
                        Total Santri Binaanku: <?= count($santri_binaan) ?> Santri
                    </span>
                </div>

                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="simpan_cek_belajar" value="1">

                    <!-- INFORMASI SESI -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Tanggal Pemantauan</label>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-xl text-xs font-bold bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Sesi Belajar Mandiri</label>
                            <select name="sesi_belajar" class="w-full px-3 py-2 border rounded-xl text-xs font-bold bg-white">
                                <option value="Malam (19.30 - 21.00 WIB)">🌙 Belajar Malam (19.30 - 21.00 WIB)</option>
                                <option value="Sore (16.00 - 17.30 WIB)">☀️ Belajar Sore (16.00 - 17.30 WIB)</option>
                                <option value="Subuh (05.30 - 06.30 WIB)">🌅 Muraja'ah Subuh (05.30 - 06.30 WIB)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Lokasi Pemantauan</label>
                            <input type="text" name="lokasi_belajar" value="Ruang Kelas / Asrama" placeholder="contoh: Gedung A Kelas 8, Perpustakaan" class="w-full px-3 py-2 border rounded-xl text-xs bg-white">
                        </div>
                    </div>

                    <!-- DAFTAR SANTRI BINAAN -->
                    <div>
                        <h3 class="font-extrabold text-sm uppercase text-slate-900 mb-3 flex items-center justify-between">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-users text-amber-600"></i>
                                <span>Status Kedisiplinan Santri Binaan</span>
                            </span>
                            <span class="text-xs text-slate-500 font-normal">Pilih status & materi yang dipelajari santri</span>
                        </h3>

                        <?php if (!empty($santri_binaan)): ?>
                            <div class="space-y-3 max-h-[450px] overflow-y-auto pr-2 border rounded-xl p-3 bg-slate-50/50">
                                <?php foreach ($santri_binaan as $st): ?>
                                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs flex-shrink-0">
                                                <i class="fas fa-user-graduate"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                    <span><?= htmlspecialchars($st['nama_lengkap']) ?></span>
                                                    <?php if ($st['jenis_kelamin'] === 'Laki-laki'): ?>
                                                        <span class="bg-blue-100 text-blue-800 text-[10px] px-2 py-0.5 rounded-full font-bold">Laki-laki</span>
                                                    <?php else: ?>
                                                        <span class="bg-pink-100 text-pink-800 text-[10px] px-2 py-0.5 rounded-full font-bold">Perempuan</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-xs text-slate-500">Kelas: <span class="font-semibold text-slate-700"><?= htmlspecialchars($st['kelas_sekarang']) ?></span> | NIS: <?= htmlspecialchars($st['nis'] ?? '-') ?></div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 flex-1 md:max-w-xl">
                                            <div class="sm:w-48">
                                                <select name="status_kehadiran[<?= $st['id'] ?>]" class="w-full px-3 py-1.5 border rounded-lg text-xs font-bold bg-white focus:ring-2 focus:ring-amber-500">
                                                    <option value="Hadir & Fokus" selected>✅ Hadir & Fokus Belajar</option>
                                                    <option value="Mengantuk / Lelah">😴 Mengantuk / Lelah</option>
                                                    <option value="Izin / Sakit">🏥 Izin / Sakit</option>
                                                    <option value="Tidak Hadir">❌ Tidak Hadir / Membolos</option>
                                                </select>
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" name="materi_dipelajari[<?= $st['id'] ?>]" placeholder="Materi / Kitab / PR (misal: Muraja'ah Juz 29, PR MTK)" class="w-full px-3 py-1.5 border rounded-lg text-xs bg-white">
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="p-8 text-center bg-slate-50 rounded-xl border text-slate-400 italic">
                                Belum ada santri binaan yang terdaftar di kelompok halaqoh Anda.
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- CATATAN & FOTO BUKTI -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Catatan Evaluasi Musyrif (Umum)</label>
                            <textarea name="catatan_musyrif" rows="2" class="w-full px-3 py-2 border rounded-xl text-xs bg-white" placeholder="Tuliskan suasana ketertiban belajar mandiri hari ini..."></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">URL Foto Bukti Pemantauan (Opsional)</label>
                            <input type="text" name="foto_bukti" placeholder="https://..." class="w-full px-3 py-2 border rounded-xl text-xs bg-white mb-2">
                            <p class="text-[11px] text-slate-400">Tempelkan link foto atau screenshot pemantauan belajar mandiri.</p>
                        </div>
                    </div>

                    <div class="text-right pt-4 border-t">
                        <button type="submit" class="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-extrabold px-6 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-2 ml-auto">
                            <i class="fas fa-save"></i> Simpan Laporan Pemantauan
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIWAYAT LOG CHEKING -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <i class="fas fa-history text-amber-600"></i>
                        <span>Riwayat Pemantauan Santri Belajar Mandiri</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 uppercase text-[11px] font-extrabold tracking-wider">
                                <th class="py-3.5 px-6">Tanggal & Sesi</th>
                                <th class="py-3.5 px-6">Musyrif Pembina</th>
                                <th class="py-3.5 px-6">Lokasi</th>
                                <th class="py-3.5 px-6 text-center">Rekap Kehadiran</th>
                                <th class="py-3.5 px-6">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <?php if (!empty($history_logs)): ?>
                                <?php foreach ($history_logs as $hl): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900"><?= date('d F Y', strtotime($hl['tanggal'])) ?></div>
                                            <div class="text-xs text-amber-700 font-semibold mt-0.5"><?= htmlspecialchars($hl['sesi_belajar']) ?></div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top font-semibold text-slate-800">
                                            <?= htmlspecialchars($hl['nama_musyrif'] ?? 'Musyrif') ?>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-slate-600">
                                            <?= htmlspecialchars($hl['lokasi_belajar']) ?>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-center">
                                            <div class="flex items-center justify-center gap-2 text-xs font-extrabold">
                                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full" title="Hadir"><?= $hl['total_hadir'] ?> Hadir</span>
                                                <?php if ($hl['total_mengantuk'] > 0): ?>
                                                    <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full" title="Mengantuk"><?= $hl['total_mengantuk'] ?> Mengantuk</span>
                                                <?php endif; ?>
                                                <?php if ($hl['total_alpa'] > 0): ?>
                                                    <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full animate-pulse" title="Tidak Hadir"><?= $hl['total_alpa'] ?> Tidak Hadir</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-xs text-slate-600 italic">
                                            "<?= htmlspecialchars($hl['catatan_musyrif'] ?? 'Belajar berjalan tertib.') ?>"
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada riwayat pemantauan belajar mandiri.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
