<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
require_once __DIR__ . '/includes/services/WaNotificationService.php';

// Hak akses untuk Musyrif, Kepala Asrama, Admin Sekolah, Kepala Sekolah, Super Admin
$user_roles_check = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_super_admin = (
    (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) ||
    in_array('super_admin', $user_roles_check) ||
    (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi']))
);

$active_menu = 'perizinan_santri';
$pesan_sukses = "";
$pesan_error = "";

$nama_petugas = $_SESSION['ustadz_nama'] ?? ($_SESSION['app_user_nama'] ?? 'Pengasuhan Pesantren');

// 1. Inisialisasi Tabel Perizinan Santri jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS perizinan_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    orangtua_id INT NULL,
    nama_pemohon VARCHAR(150) NOT NULL,
    no_whatsapp_pemohon VARCHAR(25) NOT NULL,
    hubungan_pemohon ENUM('Ayah', 'Ibu', 'Wali', 'Keluarga') DEFAULT 'Ayah',
    jenis_izin ENUM('Menjenguk / Sambangan', 'Mengajak Keluar Sementara', 'Menjemput Pulang ke Rumah', 'Izin Sakit / Berobat') NOT NULL,
    tanggal_mulai DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    tanggal_selesai DATE NOT NULL,
    jam_selesai TIME NOT NULL,
    penjemput_nama VARCHAR(150) NOT NULL,
    penjemput_kontak VARCHAR(25) NULL,
    alasan_keperluan TEXT NOT NULL,
    status ENUM('Menunggu Persetujuan', 'Disetujui', 'Ditolak', 'Sedang Berjalan', 'Selesai / Kembali') DEFAULT 'Menunggu Persetujuan',
    catatan_musyrif TEXT NULL,
    disetujui_oleh VARCHAR(150) NULL,
    disetujui_pada DATETIME NULL,
    waktu_keluar_aktual DATETIME NULL,
    waktu_kembali_aktual DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_santri (santri_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. Action Handler Persetujuan / Penolakan / Status Gerbang
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    $izin_id = (int)($_POST['izin_id'] ?? 0);
    $action = $_POST['action'];

    // Ambil data izin dulu untuk WhatsApp
    $res_iz = $conn->query("
        SELECT p.*, s.nama_lengkap as nama_santri, s.kelas_sekarang 
        FROM perizinan_santri p 
        LEFT JOIN buku_induk_santri s ON p.santri_id = s.id 
        WHERE p.id = $izin_id LIMIT 1
    ");
    $data_iz = $res_iz ? $res_iz->fetch_assoc() : null;

    if ($data_iz) {
        $nama_santri = $data_iz['nama_santri'];
        $target_wa = $data_iz['no_whatsapp_pemohon'];

        if ($action === 'setujui') {
            $catatan = trim($conn->real_escape_string($_POST['catatan_musyrif'] ?? ''));
            $conn->query("UPDATE perizinan_santri SET 
                status = 'Disetujui', 
                catatan_musyrif = '$catatan', 
                disetujui_oleh = '$nama_petugas', 
                disetujui_pada = NOW() 
                WHERE id = $izin_id");
            $pesan_sukses = "Perizinan untuk santri $nama_santri berhasil DISETUJUI!";

            // Notifikasi WhatsApp ke Wali Santri
            $wa_msg = "🕌 *PERIZINAN SANTRI DISETUJUI*\n"
                    . "Villa Quran Baron Malang\n\n"
                    . "Yth. {$data_iz['nama_pemohon']},\n"
                    . "Alhamdulillah, pengajuan izin ananda *{$nama_santri}* ({$data_iz['jenis_izin']}) telah *DISETUJUI* oleh pengasuhan pesantren.\n\n"
                    . "📅 *Jadwal*: " . date('d/m/Y', strtotime($data_iz['tanggal_mulai'])) . " " . substr($data_iz['jam_mulai'], 0, 5) . " s/d " . date('d/m/Y', strtotime($data_iz['tanggal_selesai'])) . " " . substr($data_iz['jam_selesai'], 0, 5) . "\n"
                    . (!empty($catatan) ? "📝 *Catatan Pengasuhan*: {$catatan}\n\n" : "\n")
                    . "📱 Silakan buka Ruang Orang Tua dan tunjukkan *E-Pass Izin Resmi* di layar HP kepada petugas gerbang saat menjemput ananda:\n"
                    . "https://villaquranbaronmalang.com/orangtua-perizinan.php";
            WaNotificationService::sendMessage($target_wa, $wa_msg, $conn);

        } elseif ($action === 'tolak') {
            $alasan_tolak = trim($conn->real_escape_string($_POST['alasan_tolak'] ?? 'Mohon maaf, belum dapat diizinkan karena agenda pesantren.'));
            $conn->query("UPDATE perizinan_santri SET 
                status = 'Ditolak', 
                catatan_musyrif = '$alasan_tolak', 
                disetujui_oleh = '$nama_petugas', 
                disetujui_pada = NOW() 
                WHERE id = $izin_id");
            $pesan_sukses = "Perizinan santri telah DITOLAK dengan catatan yang disampaikan ke wali santri.";

            // Notifikasi WhatsApp Penolakan
            $wa_msg = "🕌 *PEMBERITAHUAN PERIZINAN SANTRI*\n"
                    . "Villa Quran Baron Malang\n\n"
                    . "Yth. {$data_iz['nama_pemohon']},\n"
                    . "Mohon maaf, pengajuan izin ananda *{$nama_santri}* ({$data_iz['jenis_izin']}) saat ini *BELUM DAPAT DISETUJUI* oleh pengasuhan pesantren.\n\n"
                    . "📝 *Alasan*: {$alasan_tolak}\n\n"
                    . "Untuk informasi lebih lanjut silakan hubungi bagian pengasuhan/asrama.";
            WaNotificationService::sendMessage($target_wa, $wa_msg, $conn);

        } elseif ($action === 'catat_keluar') {
            $conn->query("UPDATE perizinan_santri SET status = 'Sedang Berjalan', waktu_keluar_aktual = NOW() WHERE id = $izin_id");
            $pesan_sukses = "Santri $nama_santri dicatat KELUAR gerbang asrama.";

        } elseif ($action === 'catat_kembali') {
            $conn->query("UPDATE perizinan_santri SET status = 'Selesai / Kembali', waktu_kembali_aktual = NOW() WHERE id = $izin_id");
            $pesan_sukses = "Santri $nama_santri dicatat telah KEMBALI ke asrama dengan selamat.";
        }
    }
}

// 3. Query Statistik Perizinan
$q_menunggu = $conn->query("SELECT COUNT(*) as total FROM perizinan_santri WHERE status = 'Menunggu Persetujuan'");
$total_menunggu = $q_menunggu ? (int)$q_menunggu->fetch_assoc()['total'] : 0;

$q_sedang_keluar = $conn->query("SELECT COUNT(*) as total FROM perizinan_santri WHERE status IN ('Disetujui', 'Sedang Berjalan')");
$total_sedang_keluar = $q_sedang_keluar ? (int)$q_sedang_keluar->fetch_assoc()['total'] : 0;

$q_selesai_bulan_ini = $conn->query("SELECT COUNT(*) as total FROM perizinan_santri WHERE status = 'Selesai / Kembali' AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$total_selesai = $q_selesai_bulan_ini ? (int)$q_selesai_bulan_ini->fetch_assoc()['total'] : 0;

// Query Daftar Perizinan Lengkap
$query_all = "
    SELECT p.*, s.nama_lengkap as nama_santri, s.kelas_sekarang, s.kamar_asrama, s.foto_santri
    FROM perizinan_santri p
    LEFT JOIN buku_induk_santri s ON p.santri_id = s.id
    ORDER BY 
        CASE 
            WHEN p.status = 'Menunggu Persetujuan' THEN 1 
            WHEN p.status = 'Sedang Berjalan' THEN 2 
            WHEN p.status = 'Disetujui' THEN 3 
            ELSE 4 
        END ASC, p.id DESC
";
$res_data = $conn->query($query_all);
$list_izin = [];
if ($res_data) {
    while ($r = $res_data->fetch_assoc()) $list_izin[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Perizinan Santri | Ruang Pengasuhan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-slate-200">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-sm shadow-xs">
                        <i class="fas fa-door-open"></i>
                    </span>
                    <div>
                        <h2 class="font-black text-sm sm:text-base leading-tight">Perizinan Santri & Keluar Asrama</h2>
                        <p class="text-[10px] text-slate-400 font-medium">Validasi Izin Menjenguk, Menjemput, & Keluar Sementara</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500 hidden sm:inline">Petugas:</span>
                <span class="px-2.5 py-1 bg-teal-50 text-teal-800 border border-teal-200 rounded-xl text-xs font-bold"><?= htmlspecialchars($nama_petugas) ?></span>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- NOTIFIKASI -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                            <span><?= htmlspecialchars($pesan_sukses) ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <!-- STATISTIK KARTU -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Menunggu Persetujuan</p>
                            <h3 class="text-2xl font-black <?= $total_menunggu > 0 ? 'text-amber-600' : 'text-slate-800' ?> mt-1"><?= $total_menunggu ?></h3>
                            <p class="text-[10px] text-slate-500 font-bold mt-1">Perlu ditinjau segera</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                            <i class="fas fa-hourglass-half"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Disetujui / Sedang Keluar</p>
                            <h3 class="text-2xl font-black text-blue-700 mt-1"><?= $total_sedang_keluar ?></h3>
                            <p class="text-[10px] text-blue-600 font-bold mt-1">Santri berizin aktif</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                            <i class="fas fa-person-walking-luggage"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kembali / Selesai</p>
                            <h3 class="text-2xl font-black text-emerald-700 mt-1"><?= $total_selesai ?></h3>
                            <p class="text-[10px] text-emerald-600 font-bold mt-1">Bulan ini</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                            <i class="fas fa-house-circle-check"></i>
                        </div>
                    </div>
                </div>

                <!-- FILTER & TABEL DAFTAR PERIZINAN -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 bg-slate-50/50">
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                            <input type="text" id="searchInput" oninput="filterTable()" placeholder="Cari nama santri, penjemput, atau jenis izin..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                        </div>
                        <div class="flex items-center gap-2">
                            <select id="statusFilter" onchange="filterTable()" class="bg-white border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                                <option value="">Semua Status</option>
                                <option value="Menunggu Persetujuan">Menunggu Persetujuan</option>
                                <option value="Disetujui">Disetujui</option>
                                <option value="Sedang Berjalan">Sedang Berjalan</option>
                                <option value="Selesai / Kembali">Selesai / Kembali</option>
                                <option value="Ditolak">Ditolak</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse" id="perizinanTable">
                            <thead>
                                <tr class="bg-slate-50 text-[11px] font-black text-slate-600 uppercase tracking-wider border-b border-slate-200">
                                    <th class="py-3.5 px-4">Santri & Kelas</th>
                                    <th class="py-3.5 px-4">Jenis Izin</th>
                                    <th class="py-3.5 px-4">Jadwal & Penjemput</th>
                                    <th class="py-3.5 px-4">Alasan Keperluan</th>
                                    <th class="py-3.5 px-4 text-center">Status</th>
                                    <th class="py-3.5 px-4 text-center">Aksi Petugas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs">
                                <?php if (empty($list_izin)): ?>
                                    <tr>
                                        <td colspan="6" class="py-12 text-center text-slate-400 font-bold">Belum ada data perizinan santri.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($list_izin as $row): 
                                        $st = $row['status'];
                                        $badge_class = 'bg-amber-50 text-amber-800 border-amber-200';
                                        if ($st === 'Disetujui') $badge_class = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                        elseif ($st === 'Sedang Berjalan') $badge_class = 'bg-blue-50 text-blue-800 border-blue-200';
                                        elseif ($st === 'Selesai / Kembali') $badge_class = 'bg-slate-100 text-slate-700 border-slate-300';
                                        elseif ($st === 'Ditolak') $badge_class = 'bg-rose-50 text-rose-800 border-rose-200';
                                    ?>
                                    <tr class="izin-row hover:bg-slate-50/80 transition" data-search="<?= strtolower(htmlspecialchars($row['nama_santri'] . ' ' . $row['penjemput_nama'] . ' ' . $row['nama_pemohon'] . ' ' . $row['jenis_izin'])) ?>" data-status="<?= htmlspecialchars($st) ?>">
                                        <!-- SANTRI -->
                                        <td class="py-3.5 px-4 align-top">
                                            <div class="flex items-center gap-3">
                                                <img src="<?= !empty($row['foto_santri']) ? htmlspecialchars($row['foto_santri']) : 'upload/default-avatar.png' ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($row['nama_santri'] ?? 'Santri') ?>&background=0b8478&color=fff'" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-xs flex-shrink-0">
                                                <div>
                                                    <h4 class="font-black text-slate-900 leading-tight"><?= htmlspecialchars($row['nama_santri'] ?? 'Santri') ?></h4>
                                                    <p class="text-[11px] text-[#0b8478] font-bold mt-0.5"><?= htmlspecialchars($row['kelas_sekarang'] ?? 'Santri') ?> • Kamar <?= htmlspecialchars($row['kamar_asrama'] ?? '-') ?></p>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- JENIS IZIN -->
                                        <td class="py-3.5 px-4 align-top">
                                            <span class="font-bold text-slate-900 block"><?= htmlspecialchars($row['jenis_izin']) ?></span>
                                            <span class="text-[10px] text-slate-400">Diajukan: <?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></span>
                                        </td>

                                        <!-- JADWAL & PENJEMPUT -->
                                        <td class="py-3.5 px-4 align-top space-y-1">
                                            <div class="text-[11px] font-semibold text-slate-700">
                                                <i class="fas fa-calendar-day text-teal-600 mr-1"></i>
                                                <?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?> <?= substr($row['jam_mulai'], 0, 5) ?> s/d <?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?> <?= substr($row['jam_selesai'], 0, 5) ?>
                                            </div>
                                            <div class="text-[11px] text-slate-600">
                                                <i class="fas fa-user-group text-slate-400 mr-1"></i>
                                                <?= htmlspecialchars($row['penjemput_nama'] ?: $row['nama_pemohon']) ?> (<?= htmlspecialchars($row['hubungan_pemohon']) ?>)
                                                <?php if (!empty($row['no_whatsapp_pemohon'])): ?>
                                                    <a href="https://wa.me/<?= preg_replace('/\D/', '', $row['no_whatsapp_pemohon']) ?>" target="_blank" class="text-emerald-600 font-mono text-[10px] hover:underline ml-1"><i class="fab fa-whatsapp"></i> Chat</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- ALASAN -->
                                        <td class="py-3.5 px-4 align-top max-w-xs">
                                            <p class="text-[11px] text-slate-700 leading-relaxed"><?= htmlspecialchars($row['alasan_keperluan']) ?></p>
                                            <?php if (!empty($row['catatan_musyrif'])): ?>
                                                <p class="text-[10px] text-teal-800 bg-teal-50 border border-teal-200 rounded p-1 mt-1 font-semibold">
                                                    Catatan: <?= htmlspecialchars($row['catatan_musyrif']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- STATUS -->
                                        <td class="py-3.5 px-4 align-top text-center">
                                            <span class="px-2.5 py-1 rounded-full border text-[10px] font-black inline-block whitespace-nowrap <?= $badge_class ?>">
                                                <?= htmlspecialchars($st) ?>
                                            </span>
                                        </td>

                                        <!-- AKSI PETUGAS -->
                                        <td class="py-3.5 px-4 align-top text-center space-y-1">
                                            <?php if ($st === 'Menunggu Persetujuan'): ?>
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <form action="" method="POST" onsubmit="return confirm('Setujui perizinan santri ini? Notifikasi persetujuan otomatis terkirim via WhatsApp ke wali santri.');">
                                                        <input type="hidden" name="action" value="setujui">
                                                        <input type="hidden" name="izin_id" value="<?= $row['id'] ?>">
                                                        <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold shadow-xs">
                                                            <i class="fas fa-check mr-1"></i> Setujui
                                                        </button>
                                                    </form>

                                                    <button type="button" onclick="openModalTolak(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['nama_santri'] ?? 'Santri')) ?>')" class="px-2.5 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-[10px] font-bold shadow-xs">
                                                        <i class="fas fa-times mr-1"></i> Tolak
                                                    </button>
                                                </div>
                                            <?php elseif ($st === 'Disetujui'): ?>
                                                <form action="" method="POST" onsubmit="return confirm('Catat santri keluar gerbang asrama sekarang?');">
                                                    <input type="hidden" name="action" value="catat_keluar">
                                                    <input type="hidden" name="izin_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[10px] font-bold shadow-xs">
                                                        <i class="fas fa-arrow-right-from-bracket mr-1"></i> Catat Keluar
                                                    </button>
                                                </form>
                                            <?php elseif ($st === 'Sedang Berjalan'): ?>
                                                <form action="" method="POST" onsubmit="return confirm('Konfirmasi santri telah kembali ke asrama dengan selamat?');">
                                                    <input type="hidden" name="action" value="catat_kembali">
                                                    <input type="hidden" name="izin_id" value="<?= $row['id'] ?>">
                                                    <button type="submit" class="px-3 py-1 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-[10px] font-bold shadow-xs">
                                                        <i class="fas fa-house-circle-check mr-1"></i> Catat Kembali
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-[10px] text-slate-400 font-semibold italic">Selesai</span>
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
        </main>
    </div>

    <!-- MODAL PENOLAKAN IZIN -->
    <div id="modalTolak" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-rose-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-150">
            <div class="px-6 py-4 bg-rose-50 border-b border-rose-100 flex items-center justify-between">
                <h3 class="font-black text-rose-900 text-sm">Tolak Pengajuan Izin</h3>
                <button type="button" onclick="closeModalTolak()" class="text-rose-400 hover:text-rose-600"><i class="fas fa-times"></i></button>
            </div>
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="action" value="tolak">
                <input type="hidden" name="izin_id" id="tolakIzinId" value="">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Santri:</label>
                    <p class="font-black text-slate-900 text-sm" id="tolakSantriNama">-</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penolakan <span class="text-rose-500">*</span></label>
                    <textarea name="alasan_tolak" rows="3" required placeholder="Tuliskan alasan perizinan belum dapat disetujui..." class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Alasan ini akan dikirimkan otomatis via WhatsApp ke nomor orang tua.</p>
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" onclick="closeModalTolak()" class="px-4 py-2 text-xs font-bold text-slate-600 hover:text-slate-800">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold shadow-xs">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function filterTable() {
            const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
            const status = document.getElementById('statusFilter')?.value || '';
            const rows = document.querySelectorAll('.izin-row');

            rows.forEach(r => {
                const searchTxt = r.getAttribute('data-search') || '';
                const rStatus = r.getAttribute('data-status') || '';
                const matchQ = !query || searchTxt.includes(query);
                const matchS = !status || rStatus === status;

                r.style.display = (matchQ && matchS) ? '' : 'none';
            });
        }

        function openModalTolak(izinId, santriNama) {
            document.getElementById('tolakIzinId').value = izinId;
            document.getElementById('tolakSantriNama').innerText = santriNama;
            document.getElementById('modalTolak').classList.remove('hidden');
        }

        function closeModalTolak() {
            document.getElementById('modalTolak').classList.add('hidden');
        }
    </script>
</body>
</html>