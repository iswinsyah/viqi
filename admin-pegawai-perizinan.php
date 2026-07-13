<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$ustadz_id_aktif = $_SESSION['ustadz_id'];
$ustadz_nama = $_SESSION['ustadz_nama'] ?? 'Pegawai';
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

// Cek Otoritas Admin (untuk menyetujui/menolak izin)
$admin_roles = ['super_admin', 'kepala_sekolah', 'kepala_mahad', 'admin_sekolah'];
$is_admin = false;
foreach ($user_roles as $role) {
    if (in_array(trim($role), $admin_roles)) {
        $is_admin = true;
        break;
    }
}

// 1. Buat Tabel Perizinan Otomatis (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS kepegawaian_perizinan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_selesai DATE NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    keterangan TEXT NOT NULL,
    status ENUM('Pending', 'Disetujui', 'Ditolak') DEFAULT 'Pending',
    disetujui_oleh INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ustadz_id) REFERENCES akun_ustadz(id) ON DELETE CASCADE
)");

$pesan_sukses = "";
$pesan_error = "";

// 2. Handler Input Pengajuan Izin Baru (Untuk Pegawai)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_perizinan') {
    $tanggal_mulai = $conn->real_escape_string($_POST['tanggal_mulai']);
    $tanggal_selesai = $conn->real_escape_string($_POST['tanggal_selesai']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $keterangan = $conn->real_escape_string(trim($_POST['keterangan']));

    if (empty($tanggal_mulai) || empty($tanggal_selesai) || empty($kategori) || empty($keterangan)) {
        $pesan_error = "Harap lengkapi semua kolom pengajuan!";
    } elseif (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) {
        $pesan_error = "Tanggal mulai tidak boleh melebihi tanggal selesai!";
    } else {
        $stmt = $conn->prepare("INSERT INTO kepegawaian_perizinan (ustadz_id, tanggal_mulai, tanggal_selesai, kategori, keterangan) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $ustadz_id_aktif, $tanggal_mulai, $tanggal_selesai, $kategori, $keterangan);
        if ($stmt->execute()) {
            $pesan_sukses = "Pengajuan izin berhasil diajukan dan sedang menunggu persetujuan!";
        } else {
            $pesan_error = "Gagal mengajukan izin: " . $conn->error;
        }
        $stmt->close();
    }
}

// 3. Handler Persetujuan/Penolakan Izin (Untuk Admin/Atasan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status_perizinan' && $is_admin) {
    $izin_id = (int)$_POST['izin_id'];
    $status_baru = $_POST['status_baru']; // Disetujui atau Ditolak

    if (in_array($status_baru, ['Disetujui', 'Ditolak'])) {
        // Ambil info izin
        $res_izin = $conn->query("SELECT * FROM kepegawaian_perizinan WHERE id = $izin_id LIMIT 1");
        if ($res_izin && $res_izin->num_rows > 0) {
            $izin = $res_izin->fetch_assoc();
            $emp_id = $izin['ustadz_id'];
            $tgl_mulai = $izin['tanggal_mulai'];
            $tgl_selesai = $izin['tanggal_selesai'];
            $ket = $conn->real_escape_string($izin['kategori'] . " - " . $izin['keterangan']);

            // Update status perizinan
            $conn->query("UPDATE kepegawaian_perizinan SET status = '$status_baru', disetujui_oleh = $ustadz_id_aktif WHERE id = $izin_id");

            // Jika Disetujui, auto input ke tabel absensi_pegawai agar ter-bypass GPS
            if ($status_baru == 'Disetujui') {
                $begin = new DateTime($tgl_mulai);
                $end = new DateTime($tgl_selesai);
                $end = $end->modify('+1 day'); // inclusive

                $interval = new DateInterval('P1D');
                $daterange = new DatePeriod($begin, $interval, $end);

                foreach ($daterange as $date) {
                    $tgl = $date->format("Y-m-d");
                    $waktu_absen = $tgl . " 08:00:00"; // Default waktu ijin masuk pagi
                    
                    // Cek duplikasi absensi pada hari itu
                    $check = $conn->query("SELECT id FROM absensi_pegawai WHERE ustadz_id = $emp_id AND DATE(waktu_absen) = '$tgl' AND jenis_absen = 'Pegawai'");
                    if ($check && $check->num_rows == 0) {
                        $conn->query("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, keterangan) VALUES ($emp_id, '$waktu_absen', 'Pegawai', 'Izin', 'Disetujui: $ket')");
                    }
                }
            }
            $pesan_sukses = "Status izin berhasil diubah menjadi '$status_baru'!";
        } else {
            $pesan_error = "Data pengajuan izin tidak ditemukan.";
        }
    }
}

// 4. Load Data Izin
if ($is_admin) {
    // Admin melihat semua pengajuan dari seluruh pegawai
    $query_izin = "SELECT p.*, u.nama as nama_pegawai 
                   FROM kepegawaian_perizinan p 
                   JOIN akun_ustadz u ON p.ustadz_id = u.id 
                   ORDER BY p.id DESC";
} else {
    // Pegawai biasa hanya melihat history milik sendiri
    $query_izin = "SELECT p.*, '$ustadz_nama' as nama_pegawai 
                   FROM kepegawaian_perizinan p 
                   WHERE p.ustadz_id = $ustadz_id_aktif 
                   ORDER BY p.id DESC";
}
$list_perizinan = $conn->query($query_izin)->fetch_all(MYSQLI_ASSOC);

$active_menu = 'perizinan_pegawai';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengajuan Izin / Cuti Pegawai</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- HEADER -->
        <header class="bg-white border-b border-gray-150 h-16 flex items-center justify-between px-6 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="md:hidden text-gray-600 hover:text-gray-900 mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="text-sm font-bold text-gray-800">
                    Sistem Manajemen Kepegawaian & AI
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-cyan-50 text-cyan-700 border border-cyan-200"><?= htmlspecialchars($ustadz_nama) ?></span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <!-- NOTIFIKASI -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
                    <i class="fas fa-check-circle text-emerald-500 mr-3 text-lg"></i>
                    <span class="text-xs text-emerald-800 font-semibold"><?= $pesan_sukses ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mb-6 rounded-r-lg shadow-sm flex items-center">
                    <i class="fas fa-exclamation-circle text-rose-500 mr-3 text-lg"></i>
                    <span class="text-xs text-rose-800 font-semibold"><?= $pesan_error ?></span>
                </div>
            <?php endif; ?>

            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center"><i class="fas fa-calendar-check text-cyan-600 mr-2"></i>Pengajuan Izin / Cuti</h1>
                <p class="text-sm text-gray-500 mt-1">Mengelola cuti, sakit, dan izin pegawai dengan sinkronisasi langsung ke sistem absensi.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- FORM PENGAJUAN (Hanya Tampil untuk Pegawai / Non-Admin) -->
                <?php if (!$is_admin): ?>
                <div class="bg-white rounded-xl border border-gray-150 shadow-sm p-6 lg:col-span-1 h-fit">
                    <h2 class="font-bold text-gray-800 text-sm mb-4 pb-2 border-b border-gray-100"><i class="fas fa-edit text-cyan-600 mr-1.5"></i> Formulir Pengajuan</h2>
                    <form action="" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="tambah_perizinan">
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Kategori Izin</label>
                            <select name="kategori" required class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500 bg-white">
                                <option value="Sakit">Sakit</option>
                                <option value="Cuti Tahunan">Cuti Tahunan</option>
                                <option value="Izin Urusan Penting">Izin Urusan Penting</option>
                                <option value="Dinas Luar">Dinas Luar</option>
                                <option value="Lain-lain">Lain-lain</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Keterangan / Alasan</label>
                            <textarea name="keterangan" required rows="4" class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500" placeholder="Jelaskan alasan izin Anda secara jelas..."></textarea>
                        </div>

                        <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 rounded-lg text-xs shadow transition">
                            <i class="fas fa-paper-plane mr-1.5"></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- DAFTAR PENGAJUAN / HISTORI -->
                <div class="bg-white rounded-xl border border-gray-150 shadow-sm overflow-hidden <?= !$is_admin ? 'lg:col-span-2' : 'lg:col-span-3' ?>">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-150 flex justify-between items-center">
                        <h2 class="font-bold text-gray-800 text-sm"><i class="fas fa-history text-cyan-600 mr-1.5"></i> <?= $is_admin ? 'Daftar Pengajuan Seluruh Pegawai' : 'Riwayat Pengajuan Cuti Anda' ?></h2>
                        <span class="bg-cyan-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full"><?= count($list_perizinan) ?> Pengajuan</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-150">
                            <thead class="bg-gray-50/50 text-[10px] uppercase font-bold text-gray-500 tracking-wider">
                                <tr>
                                    <?php if ($is_admin): ?>
                                    <th class="px-6 py-3 text-left">Nama Pegawai</th>
                                    <?php endif; ?>
                                    <th class="px-6 py-3 text-left">Kategori</th>
                                    <th class="px-6 py-3 text-left">Periode Izin</th>
                                    <th class="px-6 py-3 text-left">Alasan/Keterangan</th>
                                    <th class="px-6 py-3 text-center">Status</th>
                                    <?php if ($is_admin): ?>
                                    <th class="px-6 py-3 text-center">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                                <?php if (empty($list_perizinan)): ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-gray-400 italic text-center">Belum ada pengajuan izin/cuti yang terdaftar.</td>
                                </tr>
                                <?php else: foreach ($list_perizinan as $row): 
                                    $st = $row['status'];
                                    if ($st == 'Pending') {
                                        $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    } elseif ($st == 'Disetujui') {
                                        $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } else {
                                        $badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <?php if ($is_admin): ?>
                                    <td class="px-6 py-4 font-bold text-gray-900"><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 font-semibold text-slate-800"><?= htmlspecialchars($row['kategori']) ?></td>
                                    <td class="px-6 py-4 font-medium text-gray-600">
                                        <?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?> 
                                        <span class="text-gray-400 mx-1">s/d</span> 
                                        <?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs truncate" title="<?= htmlspecialchars($row['keterangan']) ?>"><?= htmlspecialchars($row['keterangan']) ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>"><?= $st ?></span>
                                    </td>
                                    <?php if ($is_admin): ?>
                                    <td class="px-6 py-4 text-center space-x-1.5 whitespace-nowrap">
                                        <?php if ($st == 'Pending'): ?>
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status_perizinan">
                                            <input type="hidden" name="izin_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status_baru" value="Disetujui">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded text-[10px] shadow-sm transition">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                        </form>
                                        <form action="" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menolak pengajuan ini?');">
                                            <input type="hidden" name="action" value="update_status_perizinan">
                                            <input type="hidden" name="izin_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="status_baru" value="Ditolak">
                                            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1 rounded text-[10px] shadow-sm transition">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span class="text-gray-400 font-medium italic text-[10px]">Selesai diproses</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
