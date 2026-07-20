<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

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

// Self-healing: Tambahkan kolom ditujukan_ke jika belum ada
$res_tgt = $conn->query("SHOW COLUMNS FROM kepegawaian_perizinan LIKE 'ditujukan_ke'");
if ($res_tgt && $res_tgt->num_rows == 0) {
    $conn->query("ALTER TABLE kepegawaian_perizinan ADD COLUMN ditujukan_ke VARCHAR(50) NOT NULL DEFAULT 'kepala_sekolah' AFTER kategori");
}

// Self-healing: Update ENUM status dan tambah kolom persetujuan sebagian
$conn->query("ALTER TABLE kepegawaian_perizinan MODIFY COLUMN status ENUM('Pending', 'Disetujui', 'Disetujui Sebagian', 'Ditolak') DEFAULT 'Pending'");

$res_app_m = $conn->query("SHOW COLUMNS FROM kepegawaian_perizinan LIKE 'tanggal_disetujui_mulai'");
if ($res_app_m && $res_app_m->num_rows == 0) {
    $conn->query("ALTER TABLE kepegawaian_perizinan ADD COLUMN tanggal_disetujui_mulai DATE NULL AFTER status");
    $conn->query("ALTER TABLE kepegawaian_perizinan ADD COLUMN tanggal_disetujui_selesai DATE NULL AFTER tanggal_disetujui_mulai");
    $conn->query("ALTER TABLE kepegawaian_perizinan ADD COLUMN catatan_admin TEXT NULL AFTER tanggal_disetujui_selesai");
}

// Helper untuk mengirim WhatsApp Fonnte
function kirim_notifikasi_wa($target, $pesan) {
    if (empty($target)) return;
    $target = preg_replace('/[^0-9]/', '', $target);
    if (strpos($target, '0') === 0) {
        $target = '62' . substr($target, 1);
    }
    
    $FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";
    $waFd = ['target' => $target, 'message' => $pesan];
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.fonnte.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($waFd),
        CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
        CURLOPT_TIMEOUT => 15
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$pesan_sukses = "";
$pesan_error = "";

// 2. Handler Input Pengajuan Izin Baru (Untuk Pegawai)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tambah_perizinan') {
    $tanggal_mulai = $conn->real_escape_string($_POST['tanggal_mulai']);
    $tanggal_selesai = $conn->real_escape_string($_POST['tanggal_selesai']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $ditujukan_ke = $conn->real_escape_string($_POST['ditujukan_ke'] ?? 'kepala_sekolah');
    $keterangan = $conn->real_escape_string(trim($_POST['keterangan']));

    if (empty($tanggal_mulai) || empty($tanggal_selesai) || empty($kategori) || empty($ditujukan_ke) || empty($keterangan)) {
        $pesan_error = "Harap lengkapi semua kolom pengajuan!";
    } elseif (strtotime($tanggal_mulai) > strtotime($tanggal_selesai)) {
        $pesan_error = "Tanggal mulai tidak boleh melebihi tanggal selesai!";
    } else {
        $stmt = $conn->prepare("INSERT INTO kepegawaian_perizinan (ustadz_id, tanggal_mulai, tanggal_selesai, kategori, ditujukan_ke, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $ustadz_id_aktif, $tanggal_mulai, $tanggal_selesai, $kategori, $ditujukan_ke, $keterangan);
        if ($stmt->execute()) {
            $pesan_sukses = "Pengajuan izin berhasil diajukan dan sedang menunggu persetujuan!";
            
            // Logika Notifikasi WhatsApp
            $no_tujuan = "";
            $nama_tujuan = "";
            if ($ditujukan_ke === 'kepala_sekolah') {
                $res_sup = $conn->query("SELECT whatsapp, nama FROM akun_ustadz WHERE role LIKE '%kepala_sekolah%' AND whatsapp IS NOT NULL AND whatsapp != '' LIMIT 1");
                $sup = ($res_sup && $res_sup->num_rows > 0) ? $res_sup->fetch_assoc() : null;
                $no_tujuan = $sup ? $sup['whatsapp'] : '';
                $nama_tujuan = $sup ? $sup['nama'] : 'Kepala Sekolah';
            } elseif ($ditujukan_ke === 'kepala_mahad') {
                $res_sup = $conn->query("SELECT whatsapp, nama FROM akun_ustadz WHERE role LIKE '%kepala_mahad%' AND whatsapp IS NOT NULL AND whatsapp != '' LIMIT 1");
                $sup = ($res_sup && $res_sup->num_rows > 0) ? $res_sup->fetch_assoc() : null;
                $no_tujuan = $sup ? $sup['whatsapp'] : '';
                $nama_tujuan = $sup ? $sup['nama'] : "Kepala Ma'had";
            } elseif ($ditujukan_ke === 'ketua_yayasan') {
                $no_tujuan = defined('YAYASAN_WA_RECIPIENT') ? YAYASAN_WA_RECIPIENT : '6285196572223';
                $nama_tujuan = 'Ketua Yayasan';
            }
            
            // Format Pesan WA
            $kategori_label = $kategori;
            if ($kategori === 'Pulang') $kategori_label = "Pulang Cepat";
            
            $pesan_wa = "🔔 *PENGAJUAN IZIN PEGAWAI BARU*\n\n"
                      . "Yth. *$nama_tujuan*,\n"
                      . "Ada pengajuan izin baru yang membutuhkan persetujuan Anda:\n\n"
                      . "• Pegawai: *$ustadz_nama*\n"
                      . "• Kategori: *$kategori_label*\n"
                      . "• Periode: " . date('d/m/Y', strtotime($tanggal_mulai)) . " s/d " . date('d/m/Y', strtotime($tanggal_selesai)) . "\n"
                      . "• Alasan: _\"$keterangan\"_\n\n"
                      . "Silakan login ke Ruang Asatidz untuk meninjau pengajuan ini.\n"
                      . "-- SIM Yayasan Villa Quran --";
            
            // Kirim ke pejabat yang dituju
            if (!empty($no_tujuan)) {
                kirim_notifikasi_wa($no_tujuan, $pesan_wa);
            }
            
            // Tembusan ke Ketua Yayasan jika ditujukan ke Kepala Sekolah / Kepala Ma'had
            if ($ditujukan_ke === 'kepala_sekolah' || $ditujukan_ke === 'kepala_mahad') {
                $no_ketua = defined('YAYASAN_WA_RECIPIENT') ? YAYASAN_WA_RECIPIENT : '6285196572223';
                $pesan_cc = "📢 *TEMBUSAN PENGAJUAN IZIN PEGAWAI*\n"
                          . "(Tembusan dikirim ke: $nama_tujuan)\n\n"
                          . "• Pegawai: *$ustadz_nama*\n"
                          . "• Kategori: *$kategori_label*\n"
                          . "• Periode: " . date('d/m/Y', strtotime($tanggal_mulai)) . " s/d " . date('d/m/Y', strtotime($tanggal_selesai)) . "\n"
                          . "• Alasan: _\"$keterangan\"_\n\n"
                          . "-- SIM Yayasan Villa Quran --";
                kirim_notifikasi_wa($no_ketua, $pesan_cc);
            }
        } else {
            $pesan_error = "Gagal mengajukan izin: " . $conn->error;
        }
        $stmt->close();
    }
}

// 3. Handler Persetujuan/Penolakan Izin (Untuk Admin/Atasan)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status_perizinan' && $is_admin) {
    $izin_id = (int)$_POST['izin_id'];
    $status_baru = $_POST['status_baru']; // Disetujui, Disetujui Sebagian, atau Ditolak

    if (in_array($status_baru, ['Disetujui', 'Disetujui Sebagian', 'Ditolak'])) {
        // Ambil info izin
        $res_izin = $conn->query("SELECT * FROM kepegawaian_perizinan WHERE id = $izin_id LIMIT 1");
        if ($res_izin && $res_izin->num_rows > 0) {
            $izin = $res_izin->fetch_assoc();
            $emp_id = $izin['ustadz_id'];
            $tgl_mulai_awal = $izin['tanggal_mulai'];
            $tgl_selesai_awal = $izin['tanggal_selesai'];
            $ket = $conn->real_escape_string($izin['kategori'] . " - " . $izin['keterangan']);
            
            $catatan_admin = isset($_POST['catatan_admin']) ? $conn->real_escape_string(trim($_POST['catatan_admin'])) : '';

            // Hapus absensi izin/alpa lama untuk rentang ini agar dapat disinkronkan ulang secara bersih
            $conn->query("DELETE FROM absensi_pegawai 
                          WHERE ustadz_id = $emp_id 
                          AND jenis_absen = 'Pegawai' 
                          AND (DATE(waktu_absen) BETWEEN '$tgl_mulai_awal' AND '$tgl_selesai_awal')");

            if ($status_baru == 'Disetujui' || $status_baru == 'Disetujui Sebagian') {
                $tgl_app_mulai = !empty($_POST['tanggal_disetujui_mulai']) ? $conn->real_escape_string($_POST['tanggal_disetujui_mulai']) : $tgl_mulai_awal;
                $tgl_app_selesai = !empty($_POST['tanggal_disetujui_selesai']) ? $conn->real_escape_string($_POST['tanggal_disetujui_selesai']) : $tgl_selesai_awal;

                // Validasi tanggal disetujui tidak boleh keluar dari rentang awal
                if (strtotime($tgl_app_mulai) < strtotime($tgl_mulai_awal)) $tgl_app_mulai = $tgl_mulai_awal;
                if (strtotime($tgl_app_selesai) > strtotime($tgl_selesai_awal)) $tgl_app_selesai = $tgl_selesai_awal;

                // Tentukan status persetujuan (Penuh vs Sebagian)
                if ($tgl_app_mulai != $tgl_mulai_awal || $tgl_app_selesai != $tgl_selesai_awal) {
                    $status_simpan = 'Disetujui Sebagian';
                } else {
                    $status_simpan = 'Disetujui';
                }

                // Update status perizinan di database
                $conn->query("UPDATE kepegawaian_perizinan 
                              SET status = '$status_simpan', 
                                  tanggal_disetujui_mulai = '$tgl_app_mulai', 
                                  tanggal_disetujui_selesai = '$tgl_app_selesai', 
                                  catatan_admin = '$catatan_admin', 
                                  disetujui_oleh = $ustadz_id_aktif 
                              WHERE id = $izin_id");

                // Loop seluruh tanggal pengajuan awal:
                // 1. Jika masuk rentang disetujui -> input 'Izin'
                // 2. Jika di luar rentang disetujui & tanggal <= hari ini -> input 'Alpa'
                $begin_all = new DateTime($tgl_mulai_awal);
                $end_all = new DateTime($tgl_selesai_awal);
                $end_all->modify('+1 day');
                $period_all = new DatePeriod($begin_all, new DateInterval('P1D'), $end_all);

                foreach ($period_all as $date) {
                    $tgl = $date->format("Y-m-d");
                    $waktu_absen = $tgl . " 08:00:00";

                    $is_approved_day = (strtotime($tgl) >= strtotime($tgl_app_mulai) && strtotime($tgl) <= strtotime($tgl_app_selesai));

                    if ($is_approved_day) {
                        $conn->query("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, keterangan) 
                                      VALUES ($emp_id, '$waktu_absen', 'Pegawai', 'Izin', '$status_simpan: $ket')");
                    } elseif ($tgl <= date('Y-m-d')) {
                        $conn->query("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, keterangan) 
                                      VALUES ($emp_id, '$waktu_absen', 'Pegawai', 'Alpa', 'Alpa (Izin Tidak Disetujui Atasan)')");
                    }
                }

                // Kirim Notifikasi WhatsApp ke Pegawai
                $res_emp = $conn->query("SELECT whatsapp, nama FROM akun_ustadz WHERE id = $emp_id LIMIT 1");
                if ($res_emp && $res_emp->num_rows > 0) {
                    $emp = $res_emp->fetch_assoc();
                    if (!empty($emp['whatsapp'])) {
                        $label_st = ($status_simpan === 'Disetujui Sebagian') ? 'DISETUJUI SEBAGIAN' : 'DISETUJUI';
                        $pesan_wa = "📢 *KOREKSI / STATUS PENGAJUAN IZIN PEGAWAI*\n\n"
                                  . "Yth. *$emp[nama]*,\n"
                                  . "Pengajuan izin Anda telah *$label_st* oleh atasan.\n\n"
                                  . "• Kategori: *" . htmlspecialchars($izin['kategori']) . "*\n"
                                  . "• Diajukan: " . date('d/m/Y', strtotime($tgl_mulai_awal)) . " s/d " . date('d/m/Y', strtotime($tgl_selesai_awal)) . "\n"
                                  . "• Disetujui: *" . date('d/m/Y', strtotime($tgl_app_mulai)) . " s/d " . date('d/m/Y', strtotime($tgl_app_selesai)) . "*\n";
                        if (!empty($catatan_admin)) {
                            $pesan_wa .= "• Catatan Atasan: _\"$catatan_admin\"_\n";
                        }
                        $pesan_wa .= "\n-- SIM Yayasan Villa Quran --";
                        kirim_notifikasi_wa($emp['whatsapp'], $pesan_wa);
                    }
                }

                $pesan_sukses = "Koreksi status izin berhasil disimpan menjadi '$status_simpan'!";
            } else {
                // Ditolak / Dibatalkan
                $conn->query("UPDATE kepegawaian_perizinan 
                              SET status = 'Ditolak', 
                                  catatan_admin = '$catatan_admin', 
                                  disetujui_oleh = $ustadz_id_aktif 
                              WHERE id = $izin_id");

                // Untuk semua tanggal yang ditolak & <= hari ini, input Alpa ke absensi_pegawai
                $begin_all = new DateTime($tgl_mulai_awal);
                $end_all = new DateTime($tgl_selesai_awal);
                $end_all->modify('+1 day');
                $period_all = new DatePeriod($begin_all, new DateInterval('P1D'), $end_all);

                foreach ($period_all as $date) {
                    $tgl = $date->format("Y-m-d");
                    if ($tgl <= date('Y-m-d')) {
                        $waktu_absen = $tgl . " 08:00:00";
                        $conn->query("INSERT INTO absensi_pegawai (ustadz_id, waktu_absen, jenis_absen, status_kehadiran, keterangan) 
                                      VALUES ($emp_id, '$waktu_absen', 'Pegawai', 'Alpa', 'Alpa (Izin Ditolak Atasan)')");
                    }
                }

                // Kirim Notifikasi Penolakan ke Pegawai via WA
                $res_emp = $conn->query("SELECT whatsapp, nama FROM akun_ustadz WHERE id = $emp_id LIMIT 1");
                if ($res_emp && $res_emp->num_rows > 0) {
                    $emp = $res_emp->fetch_assoc();
                    if (!empty($emp['whatsapp'])) {
                        $pesan_wa = "❌ *PEMBATALAN / PENOLAKAN IZIN PEGAWAI*\n\n"
                                  . "Yth. *$emp[nama]*,\n"
                                  . "Pengajuan izin Anda ( Periode " . date('d/m/Y', strtotime($tgl_mulai_awal)) . " s/d " . date('d/m/Y', strtotime($tgl_selesai_awal)) . " ) *DITOLAK / DIBATALKAN* oleh atasan.\n";
                        if (!empty($catatan_admin)) {
                            $pesan_wa .= "• Alasan Atasan: _\"$catatan_admin\"_\n";
                        }
                        $pesan_wa .= "\n-- SIM Yayasan Villa Quran --";
                        kirim_notifikasi_wa($emp['whatsapp'], $pesan_wa);
                    }
                }

                $pesan_sukses = "Pengajuan izin telah ditolak / dibatalkan.";
            }
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
                                <option value="Pulang">Pulang Cepat</option>
                                <option value="Lain-lain">Lain-lain</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Ditujukan Ke</label>
                            <select name="ditujukan_ke" required class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500 bg-white">
                                <option value="kepala_sekolah">Kepala Sekolah</option>
                                <option value="kepala_mahad">Kepala Ma'had</option>
                                <option value="ketua_yayasan">Ketua Yayasan</option>
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
                                    <th class="px-6 py-3 text-left">Ditujukan Ke</th>
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
                                    <td colspan="7" class="py-12 text-gray-400 italic text-center">Belum ada pengajuan izin/cuti yang terdaftar.</td>
                                </tr>
                                <?php else: foreach ($list_perizinan as $row): 
                                    $st = $row['status'];
                                    if ($st == 'Pending') {
                                        $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    } elseif ($st == 'Disetujui') {
                                        $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } elseif ($st == 'Disetujui Sebagian') {
                                        $badge = 'bg-purple-50 text-purple-700 border-purple-200';
                                    } else {
                                        $badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <?php if ($is_admin): ?>
                                    <td class="px-6 py-4 font-bold text-gray-900"><?= htmlspecialchars($row['nama_pegawai']) ?></td>
                                    <?php endif; ?>
                                    <td class="px-6 py-4 font-semibold text-slate-800"><?= htmlspecialchars($row['kategori'] === 'Pulang' ? 'Pulang Cepat' : $row['kategori']) ?></td>
                                    <td class="px-6 py-4 font-medium text-slate-700">
                                        <?php
                                        $tuj = $row['ditujukan_ke'] ?? 'kepala_sekolah';
                                        if ($tuj === 'kepala_sekolah') echo 'Kepala Sekolah';
                                        elseif ($tuj === 'kepala_mahad') echo "Kepala Ma'had";
                                        elseif ($tuj === 'ketua_yayasan') echo 'Ketua Yayasan';
                                        else echo ucwords(str_replace('_', ' ', $tuj));
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-600">
                                        <div>
                                            <span class="text-gray-400 text-[10px] uppercase block font-semibold">Diajukan:</span>
                                            <?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?> 
                                            <span class="text-gray-400 mx-1">s/d</span> 
                                            <?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?>
                                        </div>
                                        <?php if (($st == 'Disetujui' || $st == 'Disetujui Sebagian') && !empty($row['tanggal_disetujui_mulai'])): ?>
                                        <div class="mt-1 pt-1 border-t border-dashed border-gray-200 text-emerald-700 font-semibold text-[11px]">
                                            <span class="text-emerald-500 text-[10px] uppercase block font-semibold">Disetujui:</span>
                                            <?= date('d/m/Y', strtotime($row['tanggal_disetujui_mulai'])) ?> 
                                            <span class="text-emerald-400 mx-1">s/d</span> 
                                            <?= date('d/m/Y', strtotime($row['tanggal_disetujui_selesai'])) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 max-w-xs">
                                        <div class="truncate text-gray-800" title="<?= htmlspecialchars($row['keterangan']) ?>"><?= htmlspecialchars($row['keterangan']) ?></div>
                                        <?php if (!empty($row['catatan_admin'])): ?>
                                        <div class="text-[10px] text-gray-500 italic mt-1 bg-gray-50 p-1.5 rounded border border-gray-100" title="Catatan Atasan">
                                            <i class="fas fa-comment-dots text-cyan-600 mr-1"></i> <span class="font-semibold text-gray-600">Atasan:</span> <?= htmlspecialchars($row['catatan_admin']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border <?= $badge ?>"><?= $st ?></span>
                                    </td>
                                    <?php if ($is_admin): 
                                        $tgl_def_m = !empty($row['tanggal_disetujui_mulai']) ? $row['tanggal_disetujui_mulai'] : $row['tanggal_mulai'];
                                        $tgl_def_s = !empty($row['tanggal_disetujui_selesai']) ? $row['tanggal_disetujui_selesai'] : $row['tanggal_selesai'];
                                        $cat_adm = htmlspecialchars(addslashes($row['catatan_admin'] ?? ''), ENT_QUOTES);
                                    ?>
                                    <td class="px-6 py-4 text-center space-x-1.5 whitespace-nowrap">
                                        <?php if ($st == 'Pending'): ?>
                                        <button type="button" 
                                                onclick="openApproveModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pegawai']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($row['kategori']), ENT_QUOTES) ?>', '<?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?> s/d <?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?>', '<?= htmlspecialchars(addslashes($row['keterangan']), ENT_QUOTES) ?>', '<?= $tgl_def_m ?>', '<?= $tgl_def_s ?>', '<?= $cat_adm ?>')" 
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-2.5 py-1 rounded text-[10px] shadow-sm transition">
                                            <i class="fas fa-check mr-1"></i> Setujui / Ubah
                                        </button>
                                        <button type="button" 
                                                onclick="openRejectModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pegawai']), ENT_QUOTES) ?>', '<?= $cat_adm ?>')" 
                                                class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1 rounded text-[10px] shadow-sm transition">
                                            <i class="fas fa-times mr-1"></i> Tolak
                                        </button>
                                        <?php else: ?>
                                        <button type="button" 
                                                onclick="openApproveModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pegawai']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($row['kategori']), ENT_QUOTES) ?>', '<?= date('d/m/Y', strtotime($row['tanggal_mulai'])) ?> s/d <?= date('d/m/Y', strtotime($row['tanggal_selesai'])) ?>', '<?= htmlspecialchars(addslashes($row['keterangan']), ENT_QUOTES) ?>', '<?= $tgl_def_m ?>', '<?= $tgl_def_s ?>', '<?= $cat_adm ?>')" 
                                                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-2.5 py-1 rounded text-[10px] shadow-sm transition" title="Koreksi Tanggal / Status">
                                            <i class="fas fa-edit mr-1"></i> Koreksi Izin
                                        </button>
                                        <button type="button" 
                                                onclick="openRejectModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pegawai']), ENT_QUOTES) ?>', '<?= $cat_adm ?>')" 
                                                class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-2.5 py-1 rounded text-[10px] shadow-sm transition" title="Batalkan / Tolak Izin Ini">
                                            <i class="fas fa-ban mr-1"></i> Batalkan
                                        </button>
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

    <!-- MODAL SETUJUI / MODIFIKASI PERIZINAN -->
    <div id="modalApprove" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="font-bold text-gray-800 text-base flex items-center">
                    <i class="fas fa-calendar-check text-emerald-600 mr-2"></i> Persetujuan / Koreksi Izin
                </h3>
                <button onclick="closeApproveModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_status_perizinan">
                <input type="hidden" name="izin_id" id="approve_izin_id">
                <input type="hidden" name="status_baru" value="Disetujui">

                <div class="bg-cyan-50/70 border border-cyan-100 rounded-xl p-3 text-xs space-y-1 text-cyan-900">
                    <div><span class="font-semibold text-cyan-700">Pegawai:</span> <span id="approve_nama_pegawai" class="font-bold"></span></div>
                    <div><span class="font-semibold text-cyan-700">Kategori:</span> <span id="approve_kategori"></span></div>
                    <div><span class="font-semibold text-cyan-700">Diajukan:</span> <span id="approve_periode_awal" class="font-bold"></span></div>
                    <div><span class="font-semibold text-cyan-700">Alasan:</span> <span id="approve_alasan" class="italic"></span></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">Tanggal Disetujui (Dapat Disesuaikan)</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-[10px] text-gray-500 font-medium block mb-1">Tgl Mulai Disetujui</span>
                            <input type="date" name="tanggal_disetujui_mulai" id="approve_tgl_mulai" required class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500">
                        </div>
                        <div>
                            <span class="text-[10px] text-gray-500 font-medium block mb-1">Tgl Selesai Disetujui</span>
                            <input type="date" name="tanggal_disetujui_selesai" id="approve_tgl_selesai" required class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500">
                        </div>
                    </div>
                    <p class="text-[10px] text-amber-600 mt-1"><i class="fas fa-info-circle mr-1"></i>Ubah tanggal selesai jika menyetujui sebagian hari saja (misal 2 dari 3 hari).</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Catatan / Alasan Atasan (Opsional)</label>
                    <textarea name="catatan_admin" id="approve_catatan_admin" rows="2" class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-cyan-500" placeholder="Misal: Koreksi izin disetujui 2 hari saja (20/07 s/d 21/07)..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeApproveModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-xs">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-xs shadow-sm">
                        <i class="fas fa-check mr-1"></i> Simpan Persetujuan / Koreksi
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL TOLAK / BATALKAN PERIZINAN -->
    <div id="modalReject" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100">
            <div class="flex justify-between items-center pb-3 border-b border-gray-100 mb-4">
                <h3 class="font-bold text-gray-800 text-base flex items-center">
                    <i class="fas fa-times-circle text-rose-600 mr-2"></i> Penolakan / Pembatalan Izin
                </h3>
                <button onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="update_status_perizinan">
                <input type="hidden" name="izin_id" id="reject_izin_id">
                <input type="hidden" name="status_baru" value="Ditolak">

                <div class="bg-rose-50 border border-rose-100 rounded-xl p-3 text-xs space-y-1 text-rose-900">
                    <div><span class="font-semibold text-rose-700">Pegawai:</span> <span id="reject_nama_pegawai" class="font-bold"></span></div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Alasan Penolakan / Pembatalan</label>
                    <textarea name="catatan_admin" id="reject_catatan_admin" required rows="3" class="w-full px-3 py-2 border rounded-lg text-xs focus:ring-rose-500" placeholder="Jelaskan alasan penolakan/pembatalan pengajuan izin ini..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg text-xs">Batal</button>
                    <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs shadow-sm">
                        <i class="fas fa-times mr-1"></i> Konfirmasi Pembatalan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openApproveModal(id, nama, kategori, periode, alasan, tglMulai, tglSelesai, catatanAdmin = '') {
        document.getElementById('approve_izin_id').value = id;
        document.getElementById('approve_nama_pegawai').innerText = nama;
        document.getElementById('approve_kategori').innerText = kategori;
        document.getElementById('approve_periode_awal').innerText = periode;
        document.getElementById('approve_alasan').innerText = alasan;
        document.getElementById('approve_tgl_mulai').value = tglMulai;
        document.getElementById('approve_tgl_selesai').value = tglSelesai;
        document.getElementById('approve_catatan_admin').value = catatanAdmin;
        document.getElementById('modalApprove').classList.remove('hidden');
    }

    function closeApproveModal() {
        document.getElementById('modalApprove').classList.add('hidden');
    }

    function openRejectModal(id, nama, catatanAdmin = '') {
        document.getElementById('reject_izin_id').value = id;
        document.getElementById('reject_nama_pegawai').innerText = nama;
        document.getElementById('reject_catatan_admin').value = catatanAdmin;
        document.getElementById('modalReject').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('modalReject').classList.add('hidden');
    }
    </script>
</body>
</html>
