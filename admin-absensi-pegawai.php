<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// A. Inisialisasi Database (Self-Healing Migrations)
$conn->query("CREATE TABLE IF NOT EXISTS jadwal_rapat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agenda VARCHAR(255) NOT NULL,
    pengundang VARCHAR(50) NOT NULL,
    waktu_mulai DATETIME NOT NULL,
    status VARCHAR(20) DEFAULT 'aktif',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
@$conn->query("ALTER TABLE absensi_pegawai ADD COLUMN rapat_id INT DEFAULT NULL AFTER jenis_absen");

$active_menu = 'absensi_pegawai';
$ustadz_id = $_SESSION['ustadz_id'];
$today = date('Y-m-d');
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}

// B. Handler Pembuatan Rapat Baru oleh Kepala Sekolah, Kepala Ma'had, atau Super Admin
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'buat_rapat') {
    $agenda = $conn->real_escape_string($_POST['agenda']);
    $waktu_rapat = $conn->real_escape_string($_POST['waktu_rapat']);
    $pengundang = $conn->real_escape_string($_POST['pengundang']);
    
    $is_authorized = false;
    if ($pengundang === 'kepala_sekolah' && in_array('kepala_sekolah', $user_roles)) $is_authorized = true;
    if ($pengundang === 'kepala_mahad' && in_array('kepala_mahad', $user_roles)) $is_authorized = true;
    if ($pengundang === 'ketua_yayasan' && in_array('super_admin', $user_roles)) $is_authorized = true;
    if (in_array('super_admin', $user_roles)) $is_authorized = true; // Super Admin can do anything

    if ($is_authorized) {
        $conn->query("INSERT INTO jadwal_rapat (agenda, pengundang, waktu_mulai, status, created_by) VALUES ('$agenda', '$pengundang', '$waktu_rapat', 'aktif', $ustadz_id)");
        header("Location: admin-absensi-pegawai.php?sukses_rapat=1");
        exit;
    } else {
        header("Location: admin-absensi-pegawai.php?gagal_rapat=1");
        exit;
    }
}

// C. Handler Selesaikan Rapat
if (isset($_GET['selesaikan_rapat_id'])) {
    $r_id = (int)$_GET['selesaikan_rapat_id'];
    $check_r = $conn->query("SELECT created_by FROM jadwal_rapat WHERE id = $r_id")->fetch_assoc();
    if ($check_r && ($check_r['created_by'] == $ustadz_id || in_array('super_admin', $user_roles))) {
        $conn->query("UPDATE jadwal_rapat SET status = 'selesai' WHERE id = $r_id");
        header("Location: admin-absensi-pegawai.php?sukses_rapat=2");
        exit;
    }
}

// D. Cek absensi pegawai (harian kerja) hari ini
$res_pegawai = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' ORDER BY waktu_absen ASC");
$pegawai_status = 'belum_absen';
if ($res_pegawai) {
    $num = $res_pegawai->num_rows;
    if ($num >= 2) {
        $pegawai_status = 'selesai';
    } elseif ($num == 1) {
        $pegawai_status = 'datang';
    }
}

// E. Cek Otoritas Role untuk Absensi Pegawai (Harian)
$eligible_roles = ['super_admin', 'kepala_sekolah', 'kepala_mahad', 'admin_sekolah', 'musyrif'];
$is_eligible_pegawai = false;
foreach ($user_roles as $role) {
    if (in_array(trim($role), $eligible_roles)) {
        $is_eligible_pegawai = true;
        break;
    }
}

// F. Persiapan Teks, Icon, & Class Tombol Pegawai
$pegawai_btn_text = '';
$pegawai_btn_icon = '';
if ($pegawai_status === 'belum_absen') {
    $pegawai_btn_text = 'Absensi Kedatangan';
    $pegawai_btn_icon = 'fa-sign-in-alt';
} elseif ($pegawai_status === 'datang') {
    $pegawai_btn_text = 'Absensi Kepulangan';
    $pegawai_btn_icon = 'fa-sign-out-alt';
} else {
    $pegawai_btn_text = 'Absensi Pegawai Selesai';
    $pegawai_btn_icon = 'fa-check-double';
}

// G. Pengecekan Rapat Aktif & Pengecekan Hak Undang/Peserta Wajib
$res_rapat_aktif = $conn->query("SELECT * FROM jadwal_rapat WHERE status = 'aktif' ORDER BY waktu_mulai DESC LIMIT 1");
$rapat_aktif = ($res_rapat_aktif && $res_rapat_aktif->num_rows > 0) ? $res_rapat_aktif->fetch_assoc() : null;

$is_invited_rapat = false;
$rapat_status = 'tidak_ada';
$rapat_btn_text = 'Hadir Rapat';
$rapat_btn_icon = 'fa-handshake';

if ($rapat_aktif) {
    $rapat_id = $rapat_aktif['id'];
    $pengundang = $rapat_aktif['pengundang'];
    
    // Tentukan kelompok peserta wajib
    if ($pengundang === 'kepala_sekolah') {
        $is_admin_sekolah = in_array('admin_sekolah', $user_roles);
        $is_ustadz = in_array('ustadz', $user_roles);
        $is_ustadz_diknas = false;
        if ($is_ustadz) {
            $check_diknas = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diknas' LIMIT 1");
            $is_ustadz_diknas = ($check_diknas && $check_diknas->num_rows > 0);
        }
        $is_invited_rapat = ($is_admin_sekolah || $is_ustadz_diknas || in_array('super_admin', $user_roles));
    } elseif ($pengundang === 'kepala_mahad') {
        $is_musyrif = in_array('musyrif', $user_roles);
        $is_ustadz = in_array('ustadz', $user_roles);
        $is_ustadz_diniyah = false;
        if ($is_ustadz) {
            $check_diniyah = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diniyah' LIMIT 1");
            $is_ustadz_diniyah = ($check_diniyah && $check_diniyah->num_rows > 0);
        }
        $is_invited_rapat = ($is_musyrif || $is_ustadz_diniyah || in_array('super_admin', $user_roles));
    } elseif ($pengundang === 'ketua_yayasan') {
        $is_invited_rapat = true; // Siapa saja boleh
    }
    
    if ($is_invited_rapat) {
        $res_check_rapat = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND jenis_absen = 'Rapat' AND rapat_id = $rapat_id ORDER BY waktu_absen ASC");
        $rapat_status = 'belum_absen';
        if ($res_check_rapat) {
            $num = $res_check_rapat->num_rows;
            if ($num >= 2) {
                $rapat_status = 'selesai';
            } elseif ($num == 1) {
                $rapat_status = 'hadir';
            }
        }
        
        if ($rapat_status === 'belum_absen') {
            $rapat_btn_text = 'Hadir Rapat';
            $rapat_btn_icon = 'fa-handshake';
        } elseif ($rapat_status === 'hadir') {
            $rapat_btn_text = 'Selesai Rapat';
            $rapat_btn_icon = 'fa-door-open';
        } else {
            $rapat_btn_text = 'Absensi Rapat Selesai';
            $rapat_btn_icon = 'fa-calendar-check';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Kehadiran | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Library untuk scan QR Code -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-map-marker-alt text-cyan-600 mr-2"></i>Absensi Kehadiran Pegawai</h1>
                <p class="text-gray-500 mt-1">Verifikasi lokasi GPS Anda untuk melakukan absensi harian maupun kehadiran rapat.</p>
            </div>

            <?php if (isset($_GET['sukses_rapat']) && $_GET['sukses_rapat'] == 1): ?>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm max-w-4xl mx-auto flex items-center">
                    <i class="fas fa-check-circle mr-2 text-lg flex-shrink-0"></i> 
                    <span>Jadwal rapat baru berhasil dipublikasikan!</span>
                </div>
            <?php elseif (isset($_GET['sukses_rapat']) && $_GET['sukses_rapat'] == 2): ?>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm max-w-4xl mx-auto flex items-center">
                    <i class="fas fa-check-circle mr-2 text-lg flex-shrink-0"></i> 
                    <span>Rapat berhasil diselesaikan dan dinonaktifkan!</span>
                </div>
            <?php elseif (isset($_GET['gagal_rapat'])): ?>
                <div class="bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm max-w-4xl mx-auto flex items-center">
                    <i class="fas fa-circle-exclamation mr-2 text-lg flex-shrink-0"></i> 
                    <span>Gagal: Anda tidak memiliki wewenang untuk membuat rapat dengan pengundang tersebut.</span>
                </div>
            <?php endif; ?>

            <!-- Tampilan Status GPS (Global) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 max-w-4xl mx-auto">
                <div id="gps-status-card" class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-sm text-gray-600 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center text-left">
                        <i class="fas fa-circle-notch fa-spin text-cyan-500 text-2xl mr-3 flex-shrink-0" id="gps-loading-icon"></i>
                        <i class="fas fa-location-dot text-emerald-500 text-2xl mr-3 flex-shrink-0 hidden" id="gps-success-icon"></i>
                        <i class="fas fa-circle-exclamation text-rose-500 text-2xl mr-3 flex-shrink-0 hidden" id="gps-error-icon"></i>
                        <div>
                            <p class="font-semibold text-gray-800" id="gps-status-title">Mendeteksi Lokasi Anda...</p>
                            <p class="text-xs text-gray-500" id="gps-status-desc">Izinkan akses GPS jika diminta oleh browser.</p>
                        </div>
                    </div>
                    <button id="btn-refresh-gps" class="w-full sm:w-auto text-xs font-semibold text-cyan-600 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 px-4 py-2 rounded-lg transition-all duration-200">
                        <i class="fas fa-sync-alt mr-2"></i>Segarkan Lokasi
                    </button>
                </div>
            </div>

            <!-- GRID KARTU ABSENSI -->
            <div class="<?= $is_eligible_pegawai ? 'grid grid-cols-1 md:grid-cols-2' : 'flex justify-center max-w-md' ?> gap-6 max-w-4xl mx-auto mb-8">
                
                <?php if ($is_eligible_pegawai): ?>
                    <!-- KARTU 1: ABSENSI PEGAWAI -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg w-full">
                        <div>
                            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Pegawai</h2>
                            <p class="text-sm text-gray-500 mb-6 font-medium">
                                <?php if ($pegawai_status === 'belum_absen'): ?>
                                    Belum absen masuk hari ini.
                                <?php elseif ($pegawai_status === 'datang'): ?>
                                    Sudah absen masuk. Klik untuk absen pulang.
                                <?php else: ?>
                                    Selesai absen masuk dan pulang hari ini.
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <button id="btn-absen-pegawai" 
                                data-status="<?= $pegawai_status ?>" 
                                data-jenis="Pegawai"
                                <?= ($pegawai_status === 'selesai') ? 'disabled' : '' ?>
                                class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($pegawai_status === 'selesai') ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : (($pegawai_status === 'belum_absen') ? 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-lg active:scale-95' : 'bg-rose-600 hover:bg-rose-700 text-white hover:shadow-lg active:scale-95') ?>">
                            <i class="fas <?= $pegawai_btn_icon ?> text-xl"></i>
                            <span><?= $pegawai_btn_text ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- KARTU 2: ABSENSI RAPAT -->
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg w-full">
                    <div>
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                            <i class="fas fa-users-rectangle"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Rapat</h2>
                        
                        <?php if (!$rapat_aktif): ?>
                            <p class="text-sm text-gray-400 mb-6 font-medium leading-relaxed">
                                Tidak ada jadwal rapat aktif saat ini.
                            </p>
                            </div> <!-- Close text div -->
                            <button disabled class="w-full py-4 px-6 font-bold rounded-xl shadow-md bg-gray-200 text-gray-450 cursor-not-allowed flex items-center justify-center gap-3">
                                <i class="fas fa-calendar-xmark text-xl"></i>
                                <span>Rapat Tidak Tersedia</span>
                            </button>
                        <?php elseif (!$is_invited_rapat): ?>
                            <div class="text-xs text-gray-500 mb-6 font-semibold text-left bg-slate-50 border border-slate-100 rounded-lg p-3 leading-relaxed">
                                <span class="block font-bold text-indigo-800 mb-1"><i class="fas fa-circle-info mr-1"></i> Rapat Aktif: <?= htmlspecialchars($rapat_aktif['agenda']) ?></span>
                                Anda tidak terdaftar sebagai peserta wajib untuk rapat ini.
                            </div>
                            </div> <!-- Close text div -->
                            <button disabled class="w-full py-4 px-6 font-bold rounded-xl shadow-md bg-gray-200 text-gray-450 cursor-not-allowed flex items-center justify-center gap-3">
                                <i class="fas fa-user-slash text-xl"></i>
                                <span>Tidak Diundang</span>
                            </button>
                        <?php else: ?>
                            <?php
                            $lbl_peng = '';
                            if ($rapat_aktif['pengundang'] === 'kepala_sekolah') $lbl_peng = 'Kepala Sekolah';
                            elseif ($rapat_aktif['pengundang'] === 'kepala_mahad') $lbl_peng = "Kepala Ma'had";
                            else $lbl_peng = 'Ketua Yayasan';
                            ?>
                            <div class="text-left bg-indigo-50 border border-indigo-100 rounded-xl p-3 mb-6 text-xs text-indigo-900">
                                <p class="font-bold text-sm text-indigo-950 mb-1"><i class="fas fa-bullhorn mr-1"></i> <?= htmlspecialchars($rapat_aktif['agenda']) ?></p>
                                <p class="mb-1"><span class="font-semibold text-indigo-700">Pengundang:</span> <?= $lbl_peng ?></p>
                                <p><span class="font-semibold text-indigo-700">Waktu Mulai:</span> <?= date('H:i', strtotime($rapat_aktif['waktu_mulai'])) ?> WIB</p>
                            </div>
                            <p class="text-sm text-gray-500 mb-6 font-medium">
                                <?php if ($rapat_status === 'belum_absen'): ?>
                                    Belum absen hadir rapat.
                                <?php elseif ($rapat_status === 'hadir'): ?>
                                    Sudah absen hadir. Klik jika rapat selesai.
                                <?php else: ?>
                                    Selesai absensi hadir dan pulang rapat.
                                <?php endif; ?>
                            </p>
                            </div> <!-- Close text div -->
                            
                            <button id="btn-absen-rapat" 
                                    data-status="<?= $rapat_status ?>" 
                                    data-jenis="Rapat"
                                    data-rapat-id="<?= $rapat_aktif['id'] ?>"
                                    <?= ($rapat_status === 'selesai') ? 'disabled' : '' ?>
                                    class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($rapat_status === 'selesai') ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : (($rapat_status === 'belum_absen') ? 'bg-indigo-600 hover:bg-indigo-700 text-white hover:shadow-lg active:scale-95' : 'bg-amber-500 hover:bg-amber-600 text-white hover:shadow-lg active:scale-95') ?>">
                                <i class="fas <?= $rapat_btn_icon ?> text-xl"></i>
                                <span><?= $rapat_btn_text ?></span>
                            </button>
                        <?php endif; ?>
                </div>

            </div>

            <?php if (in_array('kepala_sekolah', $user_roles) || in_array('kepala_mahad', $user_roles) || in_array('super_admin', $user_roles)): ?>
                <!-- PANEL MANAJEMEN RAPAT -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-4xl mx-auto mb-8 text-left">
                    <div class="border-b border-gray-100 pb-3 mb-6">
                        <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-calendar-plus text-indigo-600 mr-2"></i>Panel Pembuatan Jadwal Rapat</h2>
                        <p class="text-xs text-gray-500 mt-1">Gunakan panel ini untuk menjadwalkan rapat dan menentukan target peserta wajib.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Form Buat Rapat -->
                        <div class="md:col-span-1 border-r border-gray-100 pr-0 md:pr-6">
                            <h3 class="text-sm font-bold text-gray-700 mb-4">Buat Rapat Baru</h3>
                            <form action="admin-absensi-pegawai.php" method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="buat_rapat">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Agenda / Nama Rapat</label>
                                    <input type="text" name="agenda" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Rapat Kurikulum">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Pengundang / Penyelenggara</label>
                                    <select name="pengundang" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <?php if (in_array('kepala_sekolah', $user_roles) || in_array('super_admin', $user_roles)): ?>
                                            <option value="kepala_sekolah">Kepala Sekolah (Wajib: Admin & Ustadz Diknas)</option>
                                        <?php endif; ?>
                                        <?php if (in_array('kepala_mahad', $user_roles) || in_array('super_admin', $user_roles)): ?>
                                            <option value="kepala_mahad">Kepala Ma'had (Wajib: Musyrif & Ustadz Diniyah)</option>
                                        <?php endif; ?>
                                        <?php if (in_array('super_admin', $user_roles)): ?>
                                            <option value="ketua_yayasan">Ketua Yayasan (Wajib: Semua Pegawai)</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Waktu Mulai</label>
                                    <input type="datetime-local" name="waktu_rapat" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg text-xs shadow-md transition-all duration-200">
                                    <i class="fas fa-save mr-1"></i> Publikasikan Rapat
                                </button>
                            </form>
                        </div>

                        <!-- Daftar Rapat Aktif -->
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-bold text-gray-700 mb-4">Rapat Aktif Saat Ini</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100 text-xs">
                                    <thead>
                                        <tr class="bg-gray-50 text-gray-500">
                                            <th class="px-3 py-2 text-left font-bold">Agenda</th>
                                            <th class="px-3 py-2 text-left font-bold">Penyelenggara</th>
                                            <th class="px-3 py-2 text-left font-bold">Waktu Mulai</th>
                                            <th class="px-3 py-2 text-center font-bold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <?php
                                        $res_active = $conn->query("SELECT * FROM jadwal_rapat WHERE status = 'aktif' ORDER BY waktu_mulai ASC");
                                        if ($res_active && $res_active->num_rows > 0):
                                            while ($r = $res_active->fetch_assoc()):
                                                $lbl_role = '';
                                                if ($r['pengundang'] === 'kepala_sekolah') $lbl_role = 'Kepala Sekolah';
                                                elseif ($r['pengundang'] === 'kepala_mahad') $lbl_role = "Kepala Ma'had";
                                                else $lbl_role = 'Ketua Yayasan';
                                        ?>
                                                <tr>
                                                    <td class="px-3 py-2 font-semibold text-gray-800"><?= htmlspecialchars($r['agenda']) ?></td>
                                                    <td class="px-3 py-2 text-gray-500"><?= $lbl_role ?></td>
                                                    <td class="px-3 py-2 text-gray-500"><?= date('d M Y H:i', strtotime($r['waktu_mulai'])) ?> WIB</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <?php if ($r['created_by'] == $ustadz_id || in_array('super_admin', $user_roles)): ?>
                                                            <a href="admin-absensi-pegawai.php?selesaikan_rapat_id=<?= $r['id'] ?>" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1 rounded-md font-bold transition duration-150" onclick="return confirm('Selesaikan rapat ini? Pegawai tidak akan bisa absen rapat ini lagi.')">
                                                                Selesaikan
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-gray-400">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                        <?php
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="4" class="px-3 py-4 text-center text-gray-400">Tidak ada rapat aktif saat ini.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- KOTAK HASIL/STATUS UTAMA -->
            <div id="scan-result" class="max-w-4xl mx-auto mb-8 text-center">
                <!-- Status akan diisi secara dinamis -->
            </div>

            <!-- Custom Alert Modal -->
            <div id="alert-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div id="modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div id="modal-card" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                        <div class="sm:flex sm:items-start">
                            <div id="modal-icon-bg" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <i id="modal-icon" class="fas text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Judul Notifikasi</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 leading-relaxed" id="modal-message">Isi pesan peringatan.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 sm:flex sm:flex-row-reverse">
                            <button type="button" id="btn-close-modal" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-md px-4 py-2.5 text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition duration-200">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Data Lokasi
        const locations = {
            'kantor_utama': {
                nama: 'Gedung B (Kantor Villa Quran)',
                lat: -6.595038,
                lon: 106.800247
            },
            'asrama_rijal': {
                nama: 'Gedung A (Asrama Rijal)',
                lat: -6.597638,
                lon: 106.79955
            },
            'asrama_nisa': {
                nama: 'Gedung C (Asrama Nisa)',
                lat: -6.598333,
                lon: 106.801111
            }
        };

        const MAX_DISTANCE_METERS = 50;

        let userLatitude = null;
        let userLongitude = null;
        let isLocationValid = false;

        document.getElementById('open-sidebar-hr').addEventListener('click', () => {
            document.getElementById('sidebar-hr').classList.toggle('hidden');
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
        });

        // Hitung jarak Haversine
        function haversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Radius bumi dalam meter
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // Tampilkan Modal Peringatan Premium
        function showAlertModal(title, message, type = 'success') {
            const modal = document.getElementById('alert-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            const modalIcon = document.getElementById('modal-icon');
            const modalIconBg = document.getElementById('modal-icon-bg');
            const closeBtn = document.getElementById('btn-close-modal');

            modalTitle.innerText = title;
            modalMessage.innerText = message;

            // Reset Class
            modalIconBg.className = "mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10 ";
            closeBtn.className = "w-full inline-flex justify-center rounded-xl border border-transparent shadow-md px-4 py-2.5 text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition duration-200 ";

            if (type === 'success') {
                modalIcon.className = "fas fa-check-circle text-emerald-600 text-lg";
                modalIconBg.classList.add("bg-emerald-100");
                closeBtn.classList.add("bg-emerald-600", "hover:bg-emerald-700", "focus:ring-emerald-500");
            } else if (type === 'warning') {
                modalIcon.className = "fas fa-exclamation-triangle text-amber-600 text-lg";
                modalIconBg.classList.add("bg-amber-100");
                closeBtn.classList.add("bg-amber-500", "hover:bg-amber-600", "focus:ring-amber-500");
            } else { // error / rejected
                modalIcon.className = "fas fa-circle-xmark text-rose-600 text-lg";
                modalIconBg.classList.add("bg-rose-100");
                closeBtn.classList.add("bg-rose-600", "hover:bg-rose-700", "focus:ring-rose-500");
            }

            modal.classList.remove('hidden');
        }

        // Tutup Modal
        document.getElementById('btn-close-modal').addEventListener('click', () => {
            document.getElementById('alert-modal').classList.add('hidden');
        });
        document.getElementById('modal-overlay').addEventListener('click', () => {
            document.getElementById('alert-modal').classList.add('hidden');
        });

        // Ambil data lokasi user untuk visualisasi status GPS
        function updateGPSStatus() {
            const title = document.getElementById('gps-status-title');
            const desc = document.getElementById('gps-status-desc');
            const loadingIcon = document.getElementById('gps-loading-icon');
            const successIcon = document.getElementById('gps-success-icon');
            const errorIcon = document.getElementById('gps-error-icon');

            // Reset UI
            loadingIcon.classList.remove('hidden');
            successIcon.classList.add('hidden');
            errorIcon.classList.add('hidden');

            if (!navigator.geolocation) {
                loadingIcon.classList.add('hidden');
                errorIcon.classList.remove('hidden');
                title.innerText = "GPS Tidak Didukung";
                desc.innerText = "Browser Anda tidak mendukung deteksi lokasi.";
                return;
            }

            title.innerText = "Mendeteksi Lokasi Anda...";
            desc.innerText = "Mengambil koordinat GPS Anda...";

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLatitude = position.coords.latitude;
                    userLongitude = position.coords.longitude;

                    let closestLocation = null;
                    let minDistance = null;

                    for (const key in locations) {
                        const loc = locations[key];
                        const dist = haversineDistance(userLatitude, userLongitude, loc.lat, loc.lon);
                        if (minDistance === null || dist < minDistance) {
                            minDistance = dist;
                            closestLocation = loc;
                        }
                    }

                    loadingIcon.classList.add('hidden');
                    if (minDistance !== null && minDistance <= MAX_DISTANCE_METERS) {
                        successIcon.classList.remove('hidden');
                        title.innerText = `Terdeteksi di dekat ${closestLocation.nama}`;
                        desc.innerText = `Akurasi baik. Jarak Anda: ${Math.round(minDistance)} meter.`;
                        isLocationValid = true;
                    } else {
                        errorIcon.classList.remove('hidden');
                        title.innerText = "Di Luar Jangkauan Gedung";
                        if (closestLocation) {
                            desc.innerText = `Terdekat ke ${closestLocation.nama} (Jarak: ${Math.round(minDistance)}m). Toleransi: ${MAX_DISTANCE_METERS}m.`;
                        } else {
                            desc.innerText = "Jauh dari semua lokasi absensi.";
                        }
                        isLocationValid = false;
                    }
                },
                (error) => {
                    loadingIcon.classList.add('hidden');
                    errorIcon.classList.remove('hidden');
                    title.innerText = "Gagal Mendeteksi Lokasi";
                    
                    let errMsg = "Pastikan GPS aktif dan izin lokasi diberikan.";
                    if (error.code === error.PERMISSION_DENIED) {
                        errMsg = "Izin lokasi ditolak browser. Harap izinkan akses lokasi.";
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errMsg = "Informasi lokasi tidak tersedia di perangkat Anda.";
                    } else if (error.code === error.TIMEOUT) {
                        errMsg = "Permintaan lokasi melebihi batas waktu.";
                    }
                    desc.innerText = errMsg;
                    isLocationValid = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // Jalankan absensi via AJAX
        function doAbsensi(jenisAbsen) {
            const btnId = jenisAbsen === 'Pegawai' ? 'btn-absen-pegawai' : 'btn-absen-rapat';
            const btn = document.getElementById(btnId);

            if (userLatitude === null || userLongitude === null) {
                // GPS belum terdeteksi, coba ambil paksa sekali lagi
                if (navigator.geolocation) {
                    showAlertModal('Mendeteksi GPS...', 'Sistem sedang meminta koordinat GPS perangkat Anda. Harap tunggu...', 'warning');
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            userLatitude = position.coords.latitude;
                            userLongitude = position.coords.longitude;
                            document.getElementById('alert-modal').classList.add('hidden'); // Tutup modal
                            sendAbsensiRequest(jenisAbsen, btn);
                        },
                        (error) => {
                            showAlertModal('Akses GPS Dibutuhkan', 'Gagal mendapatkan lokasi Anda. Pastikan GPS aktif dan izinkan akses lokasi di pengaturan browser.', 'error');
                        },
                        { enableHighAccuracy: true, timeout: 5000 }
                    );
                } else {
                    showAlertModal('GPS Tidak Didukung', 'Browser Anda tidak mendukung deteksi lokasi.', 'error');
                }
                return;
            }

            sendAbsensiRequest(jenisAbsen, btn);
        }

        function sendAbsensiRequest(jenisAbsen, btnElement) {
            // Nonaktifkan tombol sementara untuk mencegah double click
            btnElement.disabled = true;
            const originalHTML = btnElement.innerHTML;
            btnElement.innerHTML = `<i class="fas fa-spinner fa-spin text-xl"></i> <span>Memproses...</span>`;

            const formData = new FormData();
            formData.append('user_lat', userLatitude);
            formData.append('user_lon', userLongitude);
            formData.append('jenis_absen', jenisAbsen);
            
            if (jenisAbsen === 'Rapat') {
                const rId = btnElement.getAttribute('data-rapat-id');
                if (rId) {
                    formData.append('rapat_id', rId);
                }
            }

            fetch('proses-absen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.warning_msg) {
                        showAlertModal('Absensi Berhasil (Catatan)', data.warning_msg, 'warning');
                    } else {
                        showAlertModal('Absensi Berhasil', data.message, 'success');
                    }
                    setTimeout(() => window.location.reload(), 4000);
                } else if (data.status === 'rejected') {
                    showAlertModal('Absensi Ditolak', data.message, 'rejected');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalHTML;
                } else {
                    showAlertModal('Gagal Absensi', data.message, 'error');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                showAlertModal('Kesalahan Koneksi', 'Terjadi kesalahan saat menghubungi server.', 'error');
                console.error('Error:', error);
                btnElement.disabled = false;
                btnElement.innerHTML = originalHTML;
            });
        }

        // Event listener click tombol absensi (dengan check existence)
        const btnPegawai = document.getElementById('btn-absen-pegawai');
        if (btnPegawai) {
            btnPegawai.addEventListener('click', () => {
                doAbsensi('Pegawai');
            });
        }

        const btnRapat = document.getElementById('btn-absen-rapat');
        if (btnRapat) {
            btnRapat.addEventListener('click', () => {
                doAbsensi('Rapat');
            });
        }

        // Event listener refresh GPS
        document.getElementById('btn-refresh-gps').addEventListener('click', (e) => {
            e.preventDefault();
            updateGPSStatus();
        });

        // Jalankan deteksi GPS saat halaman dimuat
        updateGPSStatus();
    </script>
</body>
</html>