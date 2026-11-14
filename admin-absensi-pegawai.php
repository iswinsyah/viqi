<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'absensi_pegawai';
$ustadz_id = $_SESSION['ustadz_id'];
$today = date('Y-m-d');
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}

// --- Jurnal Mengajar Table Initialization ---
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_mengajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NULL,
    tanggal DATE,
    kelas VARCHAR(50),
    mata_pelajaran VARCHAR(100),
    materi TEXT,
    absensi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
@$conn->query("ALTER TABLE jurnal_mengajar ADD COLUMN ustadz_id INT NULL AFTER id");

// --- Handler Hapus Jurnal ---
if (isset($_GET['hapus_jurnal_id'])) {
    $id = (int)$_GET['hapus_jurnal_id'];
    $conn->query("DELETE FROM jurnal_mengajar WHERE id = $id AND ustadz_id = $ustadz_id");
    header("Location: admin-absensi-pegawai.php?view=absensi&sukses_jurnal=deleted");
    exit;
}

// --- Handler Simpan / Update Jurnal ---
$pesan_jurnal_sukses = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'simpan_jurnal') {
    $id = !empty($_POST['jurnal_id']) ? (int)$_POST['jurnal_id'] : 0;
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $mata_pelajaran = $conn->real_escape_string($_POST['mata_pelajaran']);
    $materi = $conn->real_escape_string($_POST['materi']);
    $absensi_notes = $conn->real_escape_string($_POST['absensi_notes']);

    if ($id > 0) {
        $sql = "UPDATE jurnal_mengajar SET tanggal='$tanggal', kelas='$kelas', mata_pelajaran='$mata_pelajaran', materi='$materi', absensi='$absensi_notes' WHERE id=$id AND ustadz_id=$ustadz_id";
        $conn->query($sql);
        header("Location: admin-absensi-pegawai.php?view=absensi&sukses_jurnal=updated");
        exit;
    } else {
        $sql = "INSERT INTO jurnal_mengajar (ustadz_id, tanggal, kelas, mata_pelajaran, materi, absensi) VALUES ($ustadz_id, '$tanggal', '$kelas', '$mata_pelajaran', '$materi', '$absensi_notes')";
        $conn->query($sql);
        header("Location: admin-absensi-pegawai.php?view=absensi&sukses_jurnal=created");
        exit;
    }
}

$edit_jurnal_mode = false;
$jurnal_edit_data = null;
if (isset($_GET['edit_jurnal_id'])) {
    $edit_jurnal_mode = true;
    $id = (int)$_GET['edit_jurnal_id'];
    $res = $conn->query("SELECT * FROM jurnal_mengajar WHERE id = $id AND ustadz_id = $ustadz_id");
    if ($res) $jurnal_edit_data = $res->fetch_assoc();
}

if (isset($_GET['sukses_jurnal'])) {
    if ($_GET['sukses_jurnal'] === 'created') $pesan_jurnal_sukses = "Jurnal baru berhasil disimpan!";
    elseif ($_GET['sukses_jurnal'] === 'updated') $pesan_jurnal_sukses = "Jurnal berhasil diupdate!";
    elseif ($_GET['sukses_jurnal'] === 'deleted') $pesan_jurnal_sukses = "Jurnal berhasil dihapus!";
}

// Ambil daftar kelas dari Master Kelas (Ruang Yayasan)
$daftar_kelas = [];
$res_kelas = $conn->query("SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kelas && $res_kelas->num_rows > 0) {
    while($row = $res_kelas->fetch_assoc()) {
        $daftar_kelas[] = $row['nama_kelas'];
    }
} else {
    $daftar_kelas = [
        'Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12',
        'Kelas A', 'Kelas B', 'Kelas C',
        'Kelas Rijal', 'Kelas Nisa'
    ];
}

// Ambil daftar mapel dari Master Mapel beserta kategorinya
$daftar_mapel_grouped = ['Diknas' => [], 'Diniyah' => [], 'Solopreneur' => [], 'Lainnya' => []];
$mapel_kategori_lookup = [];
$res_mapel = $conn->query("SELECT nama_mapel, kategori_mapel FROM master_mapel WHERE status_aktif = 1 ORDER BY kategori_mapel ASC, nama_mapel ASC");
if ($res_mapel && $res_mapel->num_rows > 0) {
    while($row = $res_mapel->fetch_assoc()) {
        $kat = trim($row['kategori_mapel'] ?? '');
        $kat_lower = strtolower($kat);
        if (strpos($kat_lower, 'diknas') !== false || strpos($kat_lower, 'pkbm') !== false) {
            $group_key = 'Diknas';
        } elseif (strpos($kat_lower, 'diniyah') !== false || strpos($kat_lower, 'tahfidz') !== false || strpos($kat_lower, 'pesantren') !== false) {
            $group_key = 'Diniyah';
        } elseif (strpos($kat_lower, 'solo') !== false || strpos($kat_lower, 'bisnis') !== false || strpos($kat_lower, 'entrepreneur') !== false || strpos($kat_lower, 'vokasi') !== false || strpos($kat_lower, 'trainer') !== false) {
            $group_key = 'Solopreneur';
        } else {
            $group_key = 'Lainnya';
        }
        $daftar_mapel_grouped[$group_key][] = $row['nama_mapel'];
        $mapel_kategori_lookup[$row['nama_mapel']] = $group_key;
    }
} else {
    $daftar_mapel_grouped['Diniyah'] = ['Tahfidz Al-Qur\'an', 'Aqidah Akhlak', 'Fiqih', 'Hadits', 'Bahasa Arab'];
    $daftar_mapel_grouped['Diknas'] = ['Matematika', 'Bahasa Indonesia', 'IPA', 'IPS', 'Bahasa Inggris', 'PKn'];
    $daftar_mapel_grouped['Solopreneur'] = ['Inkubator Bisnis', 'Digital Marketing', 'Coding & AI', 'Public Speaking'];
    foreach ($daftar_mapel_grouped as $g => $list) {
        foreach ($list as $m) $mapel_kategori_lookup[$m] = $g;
    }
}

// Query santri tidak masuk hari ini
$daftar_santri_tidak_masuk_hari_ini = [];
$query_santri_absen = "
    SELECT k.*, s.nama_lengkap, s.nis, s.kelas_sekarang, s.kamar_asrama, s.jenis_kelamin, u.nama as nama_musyrif, u.no_whatsapp 
    FROM jurnal_kesehatan_santri k 
    JOIN buku_induk_santri s ON k.santri_id = s.id 
    LEFT JOIN akun_ustadz u ON k.ustadz_id = u.id 
    WHERE k.tanggal = '$today' 
    ORDER BY s.kelas_sekarang ASC, s.nama_lengkap ASC
";
$res_santri_absen = $conn->query($query_santri_absen);
if ($res_santri_absen) {
    while ($r = $res_santri_absen->fetch_assoc()) {
        $daftar_santri_tidak_masuk_hari_ini[] = $r;
    }
}
// Query update aplikasi terbaru
$res_latest_update = $conn->query("SELECT * FROM app_updates ORDER BY created_at DESC LIMIT 1");
$latest_update = $res_latest_update ? $res_latest_update->fetch_assoc() : null;

// D. Cek absensi pegawai (harian kerja) hari ini - Mendukung Fleksibilitas Shift Kerja & Giliran Piket
$res_pegawai = $conn->query("SELECT status_kehadiran, waktu_absen FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen DESC LIMIT 1");
$pegawai_status = 'belum_absen';
$last_pegawai_record = null;
if ($res_pegawai && $res_pegawai->num_rows > 0) {
    $last_pegawai_record = $res_pegawai->fetch_assoc();
    if ($last_pegawai_record['status_kehadiran'] === 'Masuk') {
        $pegawai_status = 'datang';
    } else {
        $pegawai_status = 'pulang_bisa_masuk_lagi';
    }
}

// E. Cek Otoritas Role untuk Absensi Pegawai (Harian)
// Semua ustadz / pegawai / staf yang terdaftar dan login berhak melakukan Absensi Pegawai
$is_eligible_pegawai = true;

// F. Persiapan Teks, Icon, & Keterangan Tombol Pegawai (Mendukung Shift/Piket, Bebas Jam Kerja)
$pegawai_btn_text = '';
$pegawai_btn_icon = '';
$pegawai_keterangan_status = '';

if ($pegawai_status === 'belum_absen') {
    $pegawai_btn_text = 'Absensi Masuk / Piket';
    $pegawai_btn_icon = 'fa-sign-in-alt';
    $pegawai_keterangan_status = 'Belum absen masuk hari ini. Pastikan berada di dalam lokasi resmi yang telah ditetapkan.';
} elseif ($pegawai_status === 'datang') {
    $jam_masuk_str = date('H:i', strtotime($last_pegawai_record['waktu_absen']));
    $pegawai_btn_text = 'Absensi Kepulangan / Selesai';
    $pegawai_btn_icon = 'fa-sign-out-alt';
    $pegawai_keterangan_status = "Sedang bertugas (Absen Masuk: $jam_masuk_str WIB). Klik tombol ini bila jam piket/shift telah selesai.";
} else {
    $jam_pulang_str = date('H:i', strtotime($last_pegawai_record['waktu_absen']));
    $pegawai_btn_text = 'Absensi Masuk (Shift/Piket Baru)';
    $pegawai_btn_icon = 'fa-sign-in-alt';
    $pegawai_keterangan_status = "Shift sebelumnya selesai pukul $jam_pulang_str WIB. Klik untuk absen masuk giliran shift/piket berikutnya.";
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
    $peserta_json = $rapat_aktif['peserta_terundang'] ?? null;
    
    if (in_array('super_admin', $user_roles)) {
        $is_invited_rapat = true;
    } elseif (!empty($peserta_json)) {
        $target_data = json_decode($peserta_json, true);
        $t_roles = $target_data['roles'] ?? [];
        $t_ids = array_map('intval', $target_data['ids'] ?? []);
        
        if (in_array((int)$ustadz_id, $t_ids)) {
            $is_invited_rapat = true;
        } elseif (in_array('semua_pegawai', $t_roles)) {
            $is_invited_rapat = true;
        } else {
            foreach ($t_roles as $tr) {
                if ($tr === 'musyrif' && (in_array('musyrif', $user_roles) || in_array('kepala_asrama', $user_roles))) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'admin_sekolah' && (in_array('admin_sekolah', $user_roles) || in_array('sekretaris_sekolah', $user_roles) || in_array('bendahara_sekolah', $user_roles))) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'kepala_sekolah' && in_array('kepala_sekolah', $user_roles)) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'kepala_mahad' && in_array('kepala_mahad', $user_roles)) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'tutor' && in_array('tutor', $user_roles)) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'ustadz' && in_array('ustadz', $user_roles)) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'trainer' && in_array('trainer', $user_roles)) {
                    $is_invited_rapat = true; break;
                }
                if ($tr === 'ustadz_diknas' && in_array('ustadz', $user_roles)) {
                    $check_diknas = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diknas' LIMIT 1");
                    if ($check_diknas && $check_diknas->num_rows > 0) { $is_invited_rapat = true; break; }
                }
                if ($tr === 'ustadz_diniyah' && in_array('ustadz', $user_roles)) {
                    $check_diniyah = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $ustadz_id AND m.kategori_mapel = 'Diniyah' LIMIT 1");
                    if ($check_diniyah && $check_diniyah->num_rows > 0) { $is_invited_rapat = true; break; }
                }
            }
        }
    } else {
        // Fallback aturan default lama
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

// H. Pengecekan Absensi Mengajar & Otoritas Role (Mendukung Sesi Mengajar Berulang)
$res_mengajar = $conn->query("SELECT status_kehadiran, waktu_absen FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Mengajar' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen DESC LIMIT 1");
$mengajar_status = 'belum_absen';
$last_mengajar_record = null;
if ($res_mengajar && $res_mengajar->num_rows > 0) {
    $last_mengajar_record = $res_mengajar->fetch_assoc();
    if ($last_mengajar_record['status_kehadiran'] === 'Masuk') {
        $mengajar_status = 'datang';
    } else {
        $mengajar_status = 'pulang_bisa_masuk_lagi';
    }
}

$is_eligible_mengajar = in_array('ustadz', $user_roles) || in_array('super_admin', $user_roles);

$mengajar_btn_text = '';
$mengajar_btn_icon = '';
$mengajar_keterangan_status = '';

if ($mengajar_status === 'belum_absen') {
    $mengajar_btn_text = 'Mulai Mengajar (Masuk)';
    $mengajar_btn_icon = 'fa-door-open';
    $mengajar_keterangan_status = 'Belum absen mengajar hari ini.';
} elseif ($mengajar_status === 'datang') {
    $jam_ajar_masuk = date('H:i', strtotime($last_mengajar_record['waktu_absen']));
    $mengajar_btn_text = 'Selesai Mengajar (Selesai)';
    $mengajar_btn_icon = 'fa-door-closed';
    $mengajar_keterangan_status = "Sedang sesi mengajar (Mulai: $jam_ajar_masuk WIB). Klik saat sesi kelas selesai.";
} else {
    $jam_ajar_selesai = date('H:i', strtotime($last_mengajar_record['waktu_absen']));
    $mengajar_btn_text = 'Mulai Mengajar (Sesi Baru)';
    $mengajar_btn_icon = 'fa-door-open';
    $mengajar_keterangan_status = "Sesi sebelumnya selesai pukul $jam_ajar_selesai WIB. Klik untuk mulai sesi kelas berikutnya.";
}

// I. Jadwal Mengajar Hari Ini
$days_id = [
    'Sunday' => 'Ahad',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];
$hari_ini = $days_id[date('l')] ?? 'Senin';
$q_jadwal = $conn->query("SELECT jp.id, jp.jam_ke, mk.nama_kelas, mm.nama_mapel 
                          FROM jadwal_pelajaran jp 
                          JOIN master_kelas mk ON jp.kelas_id = mk.id 
                          JOIN master_mapel mm ON jp.mapel_id = mm.id 
                          WHERE jp.ustadz_id = $ustadz_id AND jp.hari = '$hari_ini'
                          ORDER BY jp.jam_ke ASC");
$jadwal_hari_ini = [];
if ($q_jadwal) {
    while ($r = $q_jadwal->fetch_assoc()) {
        $jadwal_hari_ini[] = $r;
    }
}
$has_schedule_today = !empty($jadwal_hari_ini);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Pegawai | SADIGS Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Library untuk scan QR Code -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        /* Android Icon Style Interaction */
        .android-icon {
            transition: transform 0.18s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.18s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .android-icon:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.15);
        }
        .android-icon:active {
            transform: scale(0.92);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <?php
    require_once 'menu-helper.php';

    $active_view = $_GET['view'] ?? 'beranda';
    if (isset($_GET['sukses_jurnal']) || isset($_GET['edit_jurnal_id']) || isset($_GET['hapus_jurnal_id'])) {
        $active_view = 'absensi';
    }
    if (!in_array($active_view, ['beranda', 'kbm', 'asrama', 'absensi'])) {
        $active_view = 'beranda';
    }

    // DEFINISI KARTU MENU MENGIKUTI PENAMAAN DI MENU MANAJEMEN MENU RUANG YAYASAN
    // a. Menu Beranda (18 Card Menu):
    $cards_beranda = [
        ['key' => 'absensi_pegawai', 'title' => get_menu_label('absensi_pegawai', 'Absensi & Jurnal KBM'), 'href' => 'admin-absensi-pegawai.php?view=absensi', 'icon' => 'fas fa-qrcode', 'bg' => 'from-emerald-500 to-teal-700'],
        ['key' => 'perizinan_pegawai', 'title' => get_menu_label('perizinan_pegawai', 'Pengajuan Izin / Cuti'), 'href' => 'admin-pegawai-perizinan.php', 'icon' => 'fas fa-calendar-check', 'bg' => 'from-teal-500 to-emerald-700'],
        ['key' => 'slip_gaji', 'title' => get_menu_label('slip_gaji', 'Slip Gaji'), 'href' => 'slip-gaji.php', 'icon' => 'fas fa-file-invoice-dollar', 'bg' => 'from-amber-500 to-amber-700'],
        ['key' => 'kpi_ustadz', 'title' => get_menu_label('kpi_ustadz', 'KPI Pegawai'), 'href' => 'admin-pegawai-kpi.php', 'icon' => 'fas fa-chart-line', 'bg' => 'from-indigo-600 to-blue-700'],
        ['key' => 'jadwal_rapat', 'title' => get_menu_label('jadwal_rapat', 'Jadwal Rapat'), 'href' => 'admin-jadwal-rapat.php', 'icon' => 'fas fa-handshake', 'bg' => 'from-violet-600 to-purple-700'],
        ['key' => 'jadwal_pelajaran', 'title' => get_menu_label('jadwal_pelajaran', 'Jadwal Pelajaran'), 'href' => 'admin-jadwal-pelajaran.php', 'icon' => 'fas fa-clock', 'bg' => 'from-sky-500 to-blue-600'],
        ['key' => 'kalender_akademik', 'title' => get_menu_label('kalender_akademik', 'Kalender Akademik'), 'href' => 'kalender-akademik.php', 'icon' => 'fas fa-calendar-days', 'bg' => 'from-cyan-600 to-teal-700'],
        ['key' => 'kpi_musyrif', 'title' => get_menu_label('kpi_musyrif', 'KPI Musyrif'), 'href' => 'admin-ustadz.php?view=kpi_musyrif', 'icon' => 'fas fa-chart-pie', 'bg' => 'from-purple-600 to-indigo-700'],
        ['key' => 'buku_induk', 'title' => get_menu_label('buku_induk', 'Buku Induk Santri'), 'href' => 'admin-buku-induk.php', 'icon' => 'fas fa-address-book', 'bg' => 'from-slate-600 to-slate-800'],
        ['key' => 'akun_orangtua', 'title' => get_menu_label('akun_orangtua', 'Akun Orang Tua'), 'href' => 'admin-akun-orangtua.php', 'icon' => 'fas fa-users', 'bg' => 'from-indigo-600 to-slate-700'],
        ['key' => 'leger_nilai', 'title' => get_menu_label('leger_nilai', 'Leger Nilai Digital'), 'href' => 'admin-leger.php', 'icon' => 'fas fa-table-list', 'bg' => 'from-blue-600 to-indigo-700'],
        ['key' => 'rapot_pkbm', 'title' => get_menu_label('rapot_pkbm', 'Raport Diknas PKBM (B & C)'), 'href' => 'admin-rapot-pkbm.php', 'icon' => 'fas fa-file-invoice', 'bg' => 'from-cyan-600 to-blue-700'],
        ['key' => 'counseling_karir', 'title' => get_menu_label('counseling_karir', 'Pemetaan Karir & PTN (AI)'), 'href' => 'admin-counseling-karir.php', 'icon' => 'fas fa-graduation-cap', 'bg' => 'from-purple-600 to-violet-700'],
        ['key' => 'kontrol_jam_kosong', 'title' => get_menu_label('kontrol_jam_kosong', 'Rekap Ajar'), 'href' => 'admin-kontrol-jam-kosong.php', 'icon' => 'fas fa-chalkboard-user', 'bg' => 'from-amber-600 to-orange-700'],
        ['key' => 'rekap_keuangan', 'title' => get_menu_label('rekap_keuangan', 'Rekap Pembayaran Keuangan'), 'href' => 'admin-rekap-spp.php', 'icon' => 'fas fa-file-invoice-dollar', 'bg' => 'from-emerald-600 to-teal-700'],
        ['key' => 'penagihan_spp', 'title' => get_menu_label('penagihan_spp', 'Penagihan SPP'), 'href' => 'admin-penagihan-spp.php', 'icon' => 'fas fa-comment-dollar', 'bg' => 'from-teal-600 to-emerald-700'],
        ['key' => 'rekap_uang_saku', 'title' => get_menu_label('rekap_uang_saku', 'Validasi Uang Saku'), 'href' => 'admin-rekap-uang-saku.php', 'icon' => 'fas fa-wallet', 'bg' => 'from-cyan-600 to-teal-700'],
        ['key' => 'sekolah_pembukuan', 'title' => get_menu_label('sekolah_pembukuan', 'Buku Kas Sekolah'), 'href' => 'sekolah-pembukuan.php', 'icon' => 'fas fa-cash-register', 'bg' => 'from-slate-700 to-zinc-900']
    ];

    // b. Menu KBM (7 Card Menu):
    $cards_kbm = [
        ['key' => 'kesediaan_mengajar', 'title' => get_menu_label('kesediaan_mengajar', 'Kesediaan Mengajar'), 'href' => 'admin-pegawai-kesediaan.php', 'icon' => 'fas fa-user-check', 'bg' => 'from-teal-600 to-emerald-700'],
        ['key' => 'master_silabus', 'title' => get_menu_label('master_silabus', 'Master Silabus & CP'), 'href' => 'admin-pegawai-silabus.php', 'icon' => 'fas fa-book-reader', 'bg' => 'from-indigo-500 to-purple-600'],
        ['key' => 'ai_rpp', 'title' => get_menu_label('ai_rpp', 'AI Generator RPP'), 'href' => 'admin-pegawai-rpp.php', 'icon' => 'fas fa-wand-magic-sparkles', 'bg' => 'from-purple-600 to-fuchsia-700'],
        ['key' => 'bank_nilai', 'title' => get_menu_label('bank_nilai', 'Bank Nilai (Input)'), 'href' => 'admin-pegawai-nilai.php', 'icon' => 'fas fa-star-half-alt', 'bg' => 'from-amber-500 to-orange-600'],
        ['key' => 'manajemen_elearning', 'title' => get_menu_label('manajemen_elearning', 'E-Modul & Flipbook Belajar'), 'href' => 'admin-elearning.php', 'icon' => 'fas fa-laptop-code', 'bg' => 'from-violet-600 to-indigo-700'],
        ['key' => 'prota_promes', 'title' => get_menu_label('prota_promes', 'Pekan Efektif, Prota & Promes'), 'href' => 'admin-kurikulum-prota-promes.php', 'icon' => 'fas fa-calendar-alt', 'bg' => 'from-rose-500 to-pink-600'],
        ['key' => 'kurikulum_solopreneur_trainer', 'title' => get_menu_label('kurikulum_solopreneur_trainer', 'Inkubator Solopreneur (AI)'), 'href' => 'trainer-kurikulum-solopreneur.php', 'icon' => 'fas fa-rocket', 'bg' => 'from-purple-600 to-fuchsia-700']
    ];

    // c. Menu Asrama (11 Card Menu):
    $cards_asrama = [
        ['key' => 'dashboard_asrama', 'title' => get_menu_label('dashboard_asrama', 'Dashboard Asrama'), 'href' => 'admin-ustadz.php?view=dashboard_asrama', 'icon' => 'fas fa-house-user', 'bg' => 'from-cyan-600 to-blue-700'],
        ['key' => 'manajemen_halaqoh', 'title' => get_menu_label('manajemen_halaqoh', 'Manajemen Halaqoh'), 'href' => 'admin-ustadz.php?view=halaqoh', 'icon' => 'fas fa-layer-group', 'bg' => 'from-teal-600 to-emerald-700'],
        ['key' => 'validasi_ibadah_musyrif', 'title' => get_menu_label('validasi_ibadah_musyrif', 'Validasi Ibadah'), 'href' => 'admin-validasi-ibadah-musyrif.php', 'icon' => 'fas fa-tasks', 'bg' => 'from-green-600 to-emerald-700'],
        ['key' => 'setoran_hafalan_santri', 'title' => get_menu_label('setoran_hafalan_santri', 'Setoran Hafalan Santri'), 'href' => 'admin-setoran-hafalan-santri.php', 'icon' => 'fas fa-quran', 'bg' => 'from-emerald-600 to-teal-800'],
        ['key' => 'rekap_setoran_santri', 'title' => get_menu_label('rekap_setoran_santri', 'Rekap Setoran Santri'), 'href' => 'admin-laporan-setoran-hafalan.php', 'icon' => 'fas fa-file-alt', 'bg' => 'from-teal-500 to-cyan-700'],
        ['key' => 'mutabaah', 'title' => get_menu_label('mutabaah', 'Buku Mutaba\'ah Santri'), 'href' => 'admin-pegawai-mutabaah.php', 'icon' => 'fas fa-clipboard-list', 'bg' => 'from-amber-500 to-orange-600'],
        ['key' => 'laporan_adab', 'title' => get_menu_label('laporan_adab', 'Laporan Kedisiplinan'), 'href' => 'admin-pegawai-laporan-adab.php', 'icon' => 'fas fa-balance-scale', 'bg' => 'from-rose-500 to-red-600'],
        ['key' => 'cek_belajar_mandiri', 'title' => get_menu_label('cek_belajar_mandiri', 'Chek Belajar Mandiri'), 'href' => 'admin-cek-belajar-mandiri.php', 'icon' => 'fas fa-book-reader', 'bg' => 'from-blue-600 to-indigo-700'],
        ['key' => 'cek_kesehatan_santri', 'title' => get_menu_label('cek_kesehatan_santri', 'Chek Kesehatan'), 'href' => 'admin-cek-kesehatan-santri.php', 'icon' => 'fas fa-notes-medical', 'bg' => 'from-red-500 to-rose-600'],
        ['key' => 'kontak_orangtua', 'title' => get_menu_label('kontak_orangtua', 'Kontak Walisantri'), 'href' => 'admin-kontak-orangtua.php', 'icon' => 'fas fa-comments', 'bg' => 'from-emerald-500 to-teal-700'],
        ['key' => 'rekap_uang_saku_musyrif', 'title' => get_menu_label('rekap_uang_saku_musyrif', 'Rekap Uang Saku Santri'), 'href' => 'admin-rekap-uang-saku-musyrif.php', 'icon' => 'fas fa-wallet', 'bg' => 'from-amber-600 to-yellow-600']
    ];

    // Filter kartu menu secara dinamis sesuai hak akses akun pegawai (berdasarkan Manajemen Menu)
    $cards_beranda = array_values(array_filter($cards_beranda, function($c) use ($user_roles, $menu_permissions, $is_super_admin) {
        return has_access($c['key'], $user_roles, $menu_permissions, $is_super_admin);
    }));
    $cards_kbm = array_values(array_filter($cards_kbm, function($c) use ($user_roles, $menu_permissions, $is_super_admin) {
        return has_access($c['key'], $user_roles, $menu_permissions, $is_super_admin);
    }));
    $cards_asrama = array_values(array_filter($cards_asrama, function($c) use ($user_roles, $menu_permissions, $is_super_admin) {
        return has_access($c['key'], $user_roles, $menu_permissions, $is_super_admin);
    }));

    // Tentukan info view aktif
    $current_view_meta = [
        'beranda' => [
            'title' => 'Menu Beranda',
            'desc' => 'Layanan utama presensi harian, administrasi, dan keuangan pegawai',
            'icon' => 'fas fa-house',
            'badge' => count($cards_beranda) . ' Menu',
            'cards' => $cards_beranda
        ],
        'kbm' => [
            'title' => 'Menu KBM',
            'desc' => 'Perangkat ajar guru, kurikulum, silabus, RPP AI, dan modul belajar',
            'icon' => 'fas fa-graduation-cap',
            'badge' => count($cards_kbm) . ' Menu',
            'cards' => $cards_kbm
        ],
        'asrama' => [
            'title' => 'Menu Asrama',
            'desc' => 'Aktivitas kesantrian, halaqoh tahfidz, ibadah santri, dan pengasuhan musyrif',
            'icon' => 'fas fa-mosque',
            'badge' => count($cards_asrama) . ' Menu',
            'cards' => $cards_asrama
        ],
        'absensi' => [
            'title' => 'Absensi & Jurnal KBM',
            'desc' => 'Presensi harian kehadiran pegawai, piket, dan formulir jurnal KBM',
            'icon' => 'fas fa-qrcode',
            'badge' => 'Presensi Harian',
            'cards' => []
        ]
    ];

    $active_meta = $current_view_meta[$active_view];
    ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER RUANG PEGAWAI (HIJAU SAMPAI DI BAWAH SEARCH BAR) -->
        <header class="bg-[#0b8478] text-white shadow-md z-20 flex-shrink-0 border-b border-teal-700/60">
            <!-- BARIS ATAS: TITLE RUANG PEGAWAI & PROFIL (TANPA HUMBURGER) -->
            <div class="h-16 flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center space-x-2">
                    <h2 class="font-extrabold text-white text-base sm:text-lg tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Ruang Pegawai
                    </h2>
                    <span class="hidden md:inline-block text-[11px] font-medium bg-teal-800/80 text-teal-200 px-2 py-0.5 rounded-full border border-teal-600/40">SADIGS 4.0</span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="akunku.php" title="Halaman Akun (<?= htmlspecialchars($u_nama) ?>)" class="flex items-center group focus:outline-none">
                        <div class="w-10 h-10 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-sm shadow-md border-2 border-white/90 overflow-hidden flex-shrink-0 group-hover:scale-105 transition-transform">
                            <?php if (!empty($u_foto)): ?>
                                <img src="<?= htmlspecialchars($u_foto) ?>" alt="Profil" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-base text-[#0b8478]"></i>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            </div>

            <?php if ($active_view !== 'absensi'): ?>
            <!-- BARIS BAWAH: SEARCH BAR DALAM AREA HEADER HIJAU -->
            <div class="px-4 sm:px-6 pb-4 pt-1">
                <div class="max-w-6xl mx-auto">
                    <div class="relative w-full">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        <input type="text" id="androidSearch" placeholder="Cari menu pegawai..." 
                               class="w-full pl-11 pr-4 py-2.5 md:py-3 rounded-2xl border border-teal-600/40 text-xs md:text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white shadow-md transition placeholder:text-slate-400">
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 md:p-6 pb-24 md:pb-12">
            
            <?php if ($active_view !== 'absensi'): ?>
            <!-- CARD SECTIONS LAUNCHER (ANDROID SQUIRCLE ICON STYLE) -->
            <div id="androidLauncher" class="max-w-6xl mx-auto mb-10 pt-1">
                <div class="app-section bg-white rounded-3xl p-4 sm:p-6 border border-slate-200/70 shadow-xs">
                    
                    <!-- ANDROID SQUIRCLE ICON GRID (4 KOLOM DI HP, 6-7 KOLOM DI PC) -->
                    <?php if (!empty($active_meta['cards'])): ?>
                    <div class="grid grid-cols-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7 gap-y-5 sm:gap-y-6 gap-x-2 sm:gap-x-4 items-start justify-items-center">
                        <?php foreach ($active_meta['cards'] as $item): ?>
                        <div class="app-item flex flex-col items-center w-full text-center select-none" data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>">
                            <a href="<?= htmlspecialchars($item['href']) ?>" class="flex flex-col items-center w-full focus:outline-none group">
                                
                                <!-- SQUIRCLE ICON ANDROID -->
                                <div class="android-icon relative w-14 h-14 sm:w-16 sm:h-16 rounded-[22px] sm:rounded-[24px] bg-gradient-to-br <?= $item['bg'] ?> text-white shadow-sm flex items-center justify-center text-xl sm:text-2xl border border-white/25">
                                    <i class="<?= $item['icon'] ?>"></i>
                                </div>

                                <!-- CAPTION MENU -->
                                <span class="text-[11px] sm:text-xs font-semibold text-slate-700 group-hover:text-[#0b8478] mt-2 tracking-tight text-center leading-tight line-clamp-2 max-w-[88px] sm:max-w-[105px] min-h-[28px] transition-colors">
                                    <?= htmlspecialchars($item['title']) ?>
                                </span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="py-10 px-4 text-center">
                        <div class="w-14 h-14 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-2xl mx-auto mb-3 shadow-xs">
                            <i class="fas fa-user-lock"></i>
                        </div>
                        <h4 class="font-bold text-slate-700 text-sm">Tidak Ada Akses Menu</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Tidak ada menu pada kategori ini yang diizinkan untuk hak akses akun Anda.</p>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- EMPTY STATE PENCARIAN -->
            <div id="noAppFound" class="hidden text-center py-12 bg-white rounded-3xl border border-slate-200 max-w-6xl mx-auto mb-10">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                    <i class="fas fa-search"></i>
                </div>
                <p class="font-bold text-slate-600 text-xs">Menu tidak ditemukan</p>
            </div>

            <!-- SCRIPT PENCARIAN INSTAN -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('androidSearch');
                const sections = document.querySelectorAll('.app-section');
                const emptyState = document.getElementById('noAppFound');

                if (searchInput) {
                    searchInput.addEventListener('input', function() {
                        const query = this.value.toLowerCase().trim();
                        let totalVisible = 0;

                        sections.forEach(sec => {
                            let secVisible = 0;
                            const items = sec.querySelectorAll('.app-item');

                            items.forEach(item => {
                                const title = item.getAttribute('data-title') || '';
                                if (!query || title.includes(query)) {
                                    item.classList.remove('hidden');
                                    secVisible++;
                                    totalVisible++;
                                } else {
                                    item.classList.add('hidden');
                                }
                            });

                            if (secVisible === 0 && query !== '') {
                                sec.classList.add('hidden');
                            } else {
                                sec.classList.remove('hidden');
                            }
                        });

                        if (emptyState) {
                            if (totalVisible === 0 && query !== '') {
                                emptyState.classList.remove('hidden');
                            } else {
                                emptyState.classList.add('hidden');
                            }
                        }
                    });
                }
            });
            </script>
            <?php else: ?>
            <!-- NAVIGATION BAR KEMBALI KE BERANDA KHUSUS VIEW ABSENSI -->
            <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between">
                <a href="admin-absensi-pegawai.php?view=beranda" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white hover:bg-slate-100 text-[#0b8478] text-xs font-bold rounded-2xl border border-slate-200 shadow-sm transition">
                    <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                </a>
                <span class="text-xs font-bold text-teal-800 bg-teal-50 px-3.5 py-1.5 rounded-full border border-teal-200">
                    <i class="fas fa-qrcode text-[#0b8478] mr-1.5"></i> Presensi & Jurnal KBM
                </span>
            </div>
            <?php endif; ?>

            <?php if ($active_view === 'absensi'): ?>
            <!-- ================================================== -->
            <!-- SECTION FORM ABSENSI & JURNAL KBM                  -->
            <!-- ================================================== -->
            <div id="section-absensi" class="max-w-6xl mx-auto">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-gray-900"><i class="fas fa-calendar-check text-[#0b8478] mr-2"></i>Formulir Absensi & Jurnal KBM Harian</h2>
                    <p class="text-gray-500 text-xs mt-1">Verifikasi lokasi GPS Anda untuk melakukan absensi harian (Shift 1 & 2) serta lengkapi jurnal mengajar Anda.</p>
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

            <?php if ($latest_update): ?>
                <!-- BANNER UPDATE APLIKASI -->
                <div id="app-update-banner" class="hidden bg-gradient-to-r from-red-500 to-rose-600 rounded-2xl shadow-md p-5 mb-6 max-w-4xl mx-auto text-white relative transition-all duration-350">
                    <div class="flex items-start gap-4 pr-8 text-left">
                        <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center text-2xl flex-shrink-0">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="bg-white/30 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full tracking-wider">Update Fitur Baru</span>
                                <span class="text-[10px] text-white/80 font-mono"><?= date('d F Y', strtotime($latest_update['created_at'])) ?></span>
                            </div>
                            <h3 class="font-bold text-sm sm:text-base mt-1.5"><?= htmlspecialchars($latest_update['judul']) ?></h3>
                            <p class="text-xs text-white/90 mt-1 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($latest_update['konten']) ?></p>
                        </div>
                    </div>
                    <button onclick="dismissUpdateBanner(<?= $latest_update['id'] ?>)" class="absolute top-4 right-4 text-white/70 hover:text-white hover:bg-white/10 w-8 h-8 rounded-full flex items-center justify-center transition" title="Tutup & Jangan tampilkan lagi">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const latestUpdateId = <?= $latest_update['id'] ?>;
                        const dismissedId = localStorage.getItem('dismissed_update_id');
                        if (dismissedId !== String(latestUpdateId)) {
                            document.getElementById('app-update-banner').classList.remove('hidden');
                        }
                    });

                    function dismissUpdateBanner(id) {
                        localStorage.setItem('dismissed_update_id', String(id));
                        document.getElementById('app-update-banner').classList.add('hidden');
                    }
                </script>
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

            <?php if ($is_eligible_mengajar && $has_schedule_today): ?>
                <!-- Jadwal Mengajar Hari Ini -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-150 p-5 mb-6 max-w-4xl mx-auto text-left">
                    <h3 class="font-bold text-gray-800 text-sm mb-3.5 flex items-center"><i class="fas fa-calendar-day mr-2 text-cyan-600"></i>Jadwal Mengajar Anda Hari Ini (<?= $hari_ini ?>)</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <?php foreach ($jadwal_hari_ini as $j): ?>
                            <div class="p-3 bg-cyan-50/50 rounded-xl border border-cyan-100 flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-cyan-500 text-white flex items-center justify-center font-bold text-xs">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800"><?= htmlspecialchars($j['nama_mapel']) ?></p>
                                    <p class="text-[10px] text-gray-500 font-medium">Kelas: <?= htmlspecialchars($j['nama_kelas']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $cols_count = 0;
            if ($is_eligible_pegawai) $cols_count++;
            if ($is_eligible_mengajar) $cols_count++;
            
            $grid_cols_class = 'flex justify-center max-w-md';
            if ($cols_count == 2) {
                $grid_cols_class = 'grid grid-cols-1 md:grid-cols-2';
            }
            ?>

            <!-- GRID KARTU ABSENSI -->
            <div class="<?= $grid_cols_class ?> gap-6 max-w-4xl mx-auto mb-8">
                
                <?php if ($is_eligible_pegawai): ?>
                    <!-- KARTU 1: ABSENSI PEGAWAI (MENDUKUNG SHIFT/PIKET TANPA BATASAN JAM) -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg w-full">
                        <div>
                            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Pegawai & Piket</h2>
                            <p class="text-xs text-gray-500 mb-3 font-medium leading-relaxed">
                                <?= htmlspecialchars($pegawai_keterangan_status) ?>
                            </p>
                            <div class="mb-4 flex items-center justify-center gap-1.5 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-lg bg-cyan-50 border border-cyan-200 text-cyan-800 text-[10px] font-bold">Shift 1: 07.00 - 13.00</span>
                                <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-800 text-[10px] font-bold">Shift 2: 15.00 - 21.00</span>
                            </div>
                        </div>
                        
                        <button id="btn-absen-pegawai" 
                                data-status="<?= $pegawai_status ?>" 
                                data-jenis="Pegawai"
                                class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($pegawai_status === 'datang') ? 'bg-rose-600 hover:bg-rose-700 text-white hover:shadow-lg active:scale-95' : 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-lg active:scale-95' ?>">
                            <i class="fas <?= $pegawai_btn_icon ?> text-xl"></i>
                            <span><?= $pegawai_btn_text ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($is_eligible_mengajar): ?>
                    <!-- KARTU 2: ABSENSI MENGAJAR (FLEKSIBEL PER SESI KELAS) -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg w-full">
                        <div>
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                <i class="fas fa-chalkboard-user"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Mengajar</h2>
                            <p class="text-xs text-gray-500 mb-3 font-medium leading-relaxed">
                                <?= htmlspecialchars($mengajar_keterangan_status) ?>
                            </p>
                            <div class="mb-4 flex items-center justify-center">
                                <span class="px-2.5 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-bold">Standar KBM: Min. 35 Menit / Sesi</span>
                            </div>
                        </div>
                        
                        <button id="btn-absen-mengajar" 
                                data-status="<?= $mengajar_status ?>" 
                                data-jenis="Mengajar"
                                class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($mengajar_status === 'datang') ? 'bg-rose-600 hover:bg-rose-700 text-white hover:shadow-lg active:scale-95' : 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-lg active:scale-95' ?>">
                            <i class="fas <?= $mengajar_btn_icon ?> text-xl"></i>
                            <span><?= $mengajar_btn_text ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                
            </div>

            <!-- KOTAK HASIL/STATUS UTAMA -->
            <div id="scan-result" class="max-w-4xl mx-auto mb-8 text-center">
                <!-- Status akan diisi secara dinamis -->
            </div>

            <!-- NOTIFIKASI JURNAL -->
            <?php if (!empty($pesan_jurnal_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-850 px-4 py-3 rounded-xl mb-6 shadow-sm max-w-4xl mx-auto flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i>
                    <span><?= $pesan_jurnal_sukses ?></span>
                </div>
            <?php endif; ?>

            <!-- JURNAL MENGAJAR SECTION -->
            <div class="max-w-4xl mx-auto space-y-6 mb-8">
                
                <!-- 1. FORM INPUT/EDIT JURNAL -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                    <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4 flex items-center gap-1.5 border-b pb-2">
                        <i class="fas <?= $edit_jurnal_mode ? 'fa-edit' : 'fa-plus' ?> text-cyan-600"></i>
                        <?= $edit_jurnal_mode ? 'Edit Jurnal Mengajar' : 'Isi Jurnal Mengajar Baru' ?>
                    </h3>
                    
                    <form action="admin-absensi-pegawai.php?view=absensi" method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="simpan_jurnal">
                        <input type="hidden" name="jurnal_id" value="<?= $edit_jurnal_mode ? $jurnal_edit_data['id'] : '' ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal KBM</label>
                                <input type="date" name="tanggal" value="<?= $edit_jurnal_mode ? $jurnal_edit_data['tanggal'] : date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Kelas / Rombel</label>
                                <div class="flex gap-1.5">
                                    <select name="kelas" id="input-kelas-jurnal" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php
                                        $kelas_tersimpan = $edit_jurnal_mode ? $jurnal_edit_data['kelas'] : '';
                                        $ada_di_list = false;
                                        foreach ($daftar_kelas as $nama_kelas) {
                                            $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                            if ($sel) $ada_di_list = true;
                                            echo "<option value=\"".htmlspecialchars($nama_kelas)."\" $sel>".htmlspecialchars($nama_kelas)."</option>";
                                        }
                                        if ($edit_jurnal_mode && !$ada_di_list && !empty($kelas_tersimpan)) {
                                            echo "<option value=\"".htmlspecialchars($kelas_tersimpan)."\" selected>".htmlspecialchars($kelas_tersimpan)." (Data Lama)</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="button" onclick="bukaScannerKelas()" class="bg-cyan-50 text-cyan-700 hover:bg-cyan-100 px-3 py-2 rounded-xl border border-cyan-200 transition flex items-center justify-center" title="Scan QR Kelas">
                                        <i class="fas fa-qrcode text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Mata Pelajaran</label>
                                <select name="mata_pelajaran" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                    <option value="">-- Pilih Mapel --</option>
                                    <?php
                                    $mapel_tersimpan = $edit_jurnal_mode ? $jurnal_edit_data['mata_pelajaran'] : '';
                                    $mapel_ada = false;
                                    foreach ($daftar_mapel_grouped as $grp_name => $m_list) {
                                        if (empty($m_list)) continue;
                                        echo "<optgroup label=\"Mapel ".htmlspecialchars($grp_name)."\">";
                                        foreach ($m_list as $nama_mapel) {
                                            $sel = ($mapel_tersimpan == $nama_mapel) ? 'selected' : '';
                                            if ($sel) $mapel_ada = true;
                                            echo "<option value=\"".htmlspecialchars($nama_mapel)."\" $sel>".htmlspecialchars($nama_mapel)." (".htmlspecialchars($grp_name).")</option>";
                                        }
                                        echo "</optgroup>";
                                    }
                                    if ($edit_jurnal_mode && !$mapel_ada && !empty($mapel_tersimpan)) {
                                        echo "<option value=\"".htmlspecialchars($mapel_tersimpan)."\" selected>".htmlspecialchars($mapel_tersimpan)." (Data Lainnya)</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Materi / Bahasan Pembelajaran</label>
                                <textarea name="materi" rows="3" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Tulis ringkasan materi/kompetensi yang diajarkan hari ini..."><?= $edit_jurnal_mode ? htmlspecialchars($jurnal_edit_data['materi']) : '' ?></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Catatan Absensi Siswa / Kendala Kelas</label>
                                <textarea name="absensi_notes" rows="3" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Contoh: Ahmad (sakit), Budi (izin), kondisi kelas kondusif..."><?= $edit_jurnal_mode ? htmlspecialchars($jurnal_edit_data['absensi']) : '' ?></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-end gap-2 pt-2">
                            <?php if ($edit_jurnal_mode): ?>
                                <a href="admin-absensi-pegawai.php?view=absensi" class="bg-gray-100 text-gray-700 font-bold px-4 py-2 rounded-xl text-xs transition hover:bg-gray-200">
                                    Batal
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md flex items-center gap-1.5">
                                <i class="fas fa-save text-xs"></i>
                                <span><?= $edit_jurnal_mode ? 'Update Jurnal' : 'Simpan Jurnal' ?></span>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- 2. RIWAYAT JURNAL MENGAJAR -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-2 mb-4 border-b pb-2">
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fas fa-history text-cyan-600"></i> Riwayat Jurnal Mengajar
                        </h3>
                        <span class="text-[11px] text-gray-500 font-medium">Tercatat di form dashboard pengajar</span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-150 text-xs">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500">
                                    <th class="px-3 py-2 text-left font-bold">Tanggal</th>
                                    <th class="px-3 py-2 text-left font-bold">Kelas & Mapel</th>
                                    <th class="px-3 py-2 text-left font-bold">Kategori</th>
                                    <th class="px-3 py-2 text-left font-bold">Materi Pembelajaran</th>
                                    <th class="px-3 py-2 text-left font-bold">Absensi/Kendala</th>
                                    <th class="px-3 py-2 text-center font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php
                                $res_jurnal = $conn->query("SELECT * FROM jurnal_mengajar WHERE ustadz_id = $ustadz_id ORDER BY tanggal DESC, id DESC LIMIT 50");
                                if ($res_jurnal && $res_jurnal->num_rows > 0):
                                    while ($row = $res_jurnal->fetch_assoc()):
                                        $mpl_name = $row['mata_pelajaran'];
                                        $kat_jurnal = $mapel_kategori_lookup[$mpl_name] ?? 'Lainnya';
                                        
                                        $badge_class = "bg-gray-100 text-gray-700 border-gray-200";
                                        if ($kat_jurnal === 'Diknas') $badge_class = "bg-blue-50 text-blue-700 border-blue-200";
                                        elseif ($kat_jurnal === 'Diniyah') $badge_class = "bg-emerald-50 text-emerald-700 border-emerald-200";
                                        elseif ($kat_jurnal === 'Solopreneur') $badge_class = "bg-amber-50 text-amber-700 border-amber-200";
                                ?>
                                        <tr class="hover:bg-slate-50/55 transition">
                                            <td class="px-3 py-3 text-gray-700 font-medium whitespace-nowrap">
                                                <?= date('d M Y', strtotime($row['tanggal'])) ?>
                                            </td>
                                            <td class="px-3 py-3">
                                                <span class="font-bold text-cyan-700 block"><?= htmlspecialchars($row['kelas']) ?></span>
                                                <span class="text-gray-600 text-[11px] font-semibold"><?= htmlspecialchars($row['mata_pelajaran']) ?></span>
                                            </td>
                                            <td class="px-3 py-3 whitespace-nowrap">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border <?= $badge_class ?>">
                                                    <?= htmlspecialchars($kat_jurnal) ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-3 text-gray-600 font-medium max-w-xs truncate" title="<?= htmlspecialchars($row['materi']) ?>">
                                                <?= htmlspecialchars($row['materi']) ?>
                                            </td>
                                            <td class="px-3 py-3 text-rose-500 font-medium max-w-xs truncate" title="<?= htmlspecialchars($row['absensi'] ?? '') ?>">
                                                <?= empty($row['absensi']) ? '<span class="text-gray-400 italic">Nihil</span>' : htmlspecialchars($row['absensi']) ?>
                                            </td>
                                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                                <a href="admin-absensi-pegawai.php?view=absensi&edit_jurnal_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2.5" title="Edit"><i class="fas fa-edit"></i></a>
                                                <a href="admin-absensi-pegawai.php?view=absensi&hapus_jurnal_id=<?= $row['id'] ?>" onclick="return confirm('Hapus jurnal ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                            </td>
                                        </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                    <tr>
                                        <td colspan="6" class="px-3 py-4 text-center text-gray-400 italic">Belum ada riwayat jurnal mengajar.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. DAFTAR SANTRI TIDAK MASUK HARI INI -->
                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                    <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4 flex items-center gap-1.5 border-b pb-2">
                        <i class="fas fa-user-slash text-rose-600"></i> Daftar Santri Tidak Masuk Sekolah Hari Ini
                    </h3>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-150 text-xs">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500">
                                    <th class="px-3 py-2 text-center font-bold w-10">No</th>
                                    <th class="px-3 py-2 text-left font-bold">Nama Santri</th>
                                    <th class="px-3 py-2 text-left font-bold">Kelas & Kamar</th>
                                    <th class="px-3 py-2 text-left font-bold">Keluhan Sakit</th>
                                    <th class="px-3 py-2 text-left font-bold">Musyrif PJ</th>
                                    <th class="px-3 py-2 text-center font-bold">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (!empty($daftar_santri_tidak_masuk_hari_ini)): ?>
                                    <?php $no = 1; foreach ($daftar_santri_tidak_masuk_hari_ini as $st): ?>
                                        <tr class="hover:bg-slate-50/55 transition">
                                            <td class="px-3 py-3 text-center font-bold text-slate-500"><?= $no++ ?></td>
                                            <td class="px-3 py-3">
                                                <span class="font-bold text-slate-800 block"><?= htmlspecialchars($st['nama_lengkap']) ?></span>
                                                <span class="text-gray-500 text-[10px]"><?= htmlspecialchars($st['jenis_kelamin'] === 'Laki-laki' ? 'Laki-laki' : 'Perempuan') ?></span>
                                            </td>
                                            <td class="px-3 py-3 font-semibold text-slate-800">
                                                <div>Kelas: <span class="text-rose-800 font-bold"><?= htmlspecialchars($st['kelas_sekarang']) ?></span></div>
                                                <div class="text-[10px] text-gray-500">Kamar: <?= htmlspecialchars($st['kamar_asrama'] ?? 'Asrama') ?></div>
                                            </td>
                                            <td class="px-3 py-3">
                                                <div class="font-bold text-rose-800"><?= htmlspecialchars($st['gejala_keluhan']) ?></div>
                                                <div class="text-[10px] text-amber-700 font-medium"><?= htmlspecialchars($st['status_kesehatan']) ?></div>
                                            </td>
                                            <td class="px-3 py-3 font-semibold text-slate-700">
                                                <div><?= htmlspecialchars($st['nama_musyrif'] ?? 'Musyrif') ?></div>
                                                <?php if (!empty($st['no_whatsapp'])): ?>
                                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $st['no_whatsapp']) ?>" target="_blank" class="text-[10px] text-emerald-600 font-bold hover:underline flex items-center gap-0.5 mt-0.5">
                                                        <i class="fab fa-whatsapp"></i> WA Musyrif
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                                <a href="admin-cek-kesehatan-santri.php?print_surat_id=<?= $st['id'] ?>" target="_blank" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-2.5 py-1.5 rounded-lg text-[10px] transition inline-flex items-center gap-1 shadow-sm">
                                                    <i class="fas fa-file-invoice text-[10px]"></i>
                                                    <span>Surat Sakit</span>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="px-3 py-4 text-center text-gray-400 italic">Alhamdulillah, tidak ada catatan santri sakit/izin tidak masuk hari ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- MODAL SCANNER QR RUANG KELAS -->
            <div id="qr-modal-jurnal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-gray-100">
                    <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                        <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider flex items-center gap-1.5"><i class="fas fa-qrcode text-emerald-600"></i> Scan QR Ruang Kelas</h3>
                        <button type="button" onclick="tutupScannerKelas()" class="text-gray-400 hover:text-red-500 text-lg">&times;</button>
                    </div>
                    <div class="p-6 text-center">
                        <p class="text-xs text-gray-500 mb-4">Arahkan kamera ke stiker QR Code yang tertempel di dinding kelas.</p>
                        <div id="reader-jurnal" class="w-full bg-black rounded-xl overflow-hidden min-h-[300px] shadow-inner"></div>
                    </div>
                </div>
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
                lat: -7.9485768,
                lon: 112.5823352
            },
            'asrama_rijal': {
                nama: 'Gedung A (Asrama Rijal)',
                lat: -7.9480257,
                lon: 112.5822426
            },
            'asrama_nisa': {
                nama: 'Gedung C (Asrama Nisa)',
                lat: -7.9405464,
                lon: 112.5791353
            },
            'masjid_taqwa': {
                nama: 'Masjid At Taqwa VBT',
                lat: -7.9464649,
                lon: 112.5822123
            }
        };

        const MAX_DISTANCE_METERS = 75;

        let userLatitude = null;
        let userLongitude = null;
        let isLocationValid = false;

        const openSidebarBtn = document.getElementById('open-sidebar-hr');
        if (openSidebarBtn) {
            openSidebarBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }

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

            if (!title || !desc || !loadingIcon || !successIcon || !errorIcon) return;

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
            const btnId = 'btn-absen-' + jenisAbsen.toLowerCase();
            const btn = document.getElementById(btnId);

            if (userLatitude === null || userLongitude === null) {
                // GPS belum terdeteksi, coba ambil paksa sekali lagi
                if (navigator.geolocation) {
                    showAlertModal('Mendeteksi GPS...', 'Sistem sedang meminta koordinat GPS perangkat Anda. Harap tunggu...', 'warning');
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            userLatitude = position.coords.latitude;
                            userLongitude = position.coords.longitude;
                            window.userAccuracy = position.coords.accuracy || 0;
                            window.isMocked = (position.coords.isMock || position.mocked) ? 1 : 0;
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
            formData.append('gps_accuracy', window.userAccuracy || 0);
            formData.append('is_mock', window.isMocked || 0);
            formData.append('jenis_absen', jenisAbsen);

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
                    if (data.account_disabled) {
                        showAlertModal('🚨 AKUN DINONAKTIFKAN', data.message, 'rejected');
                        setTimeout(() => {
                            window.location.href = 'login.php?blocked=1';
                        }, 7000);
                    } else {
                        showAlertModal('Absensi Ditolak', data.message, 'rejected');
                        btnElement.disabled = false;
                        btnElement.innerHTML = originalHTML;
                    }
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

        const btnMengajar = document.getElementById('btn-absen-mengajar');
        if (btnMengajar) {
            btnMengajar.addEventListener('click', () => {
                doAbsensi('Mengajar');
            });
        }

        // Event listener refresh GPS
        const btnRefreshGps = document.getElementById('btn-refresh-gps');
        if (btnRefreshGps) {
            btnRefreshGps.addEventListener('click', (e) => {
                e.preventDefault();
                updateGPSStatus();
            });
        }

        // ==========================================
        // FUNGSI SCANNER QR CODE UNTUK KELAS JURNAL
        // ==========================================
        let html5QrcodeScannerJurnal = null;

        window.bukaScannerKelas = function() {
            document.getElementById('qr-modal-jurnal').classList.remove('hidden');
            html5QrcodeScannerJurnal = new Html5QrcodeScanner(
                "reader-jurnal", { fps: 10, qrbox: {width: 250, height: 250} }, false
            );
            html5QrcodeScannerJurnal.render(onScanSuccessJurnal);
        }

        window.tutupScannerKelas = function() {
            document.getElementById('qr-modal-jurnal').classList.add('hidden');
            if (html5QrcodeScannerJurnal) {
                html5QrcodeScannerJurnal.clear().catch(error => console.error("Gagal mematikan scanner.", error));
            }
        }

        function onScanSuccessJurnal(decodedText, decodedResult) {
            const selectKelas = document.getElementById('input-kelas-jurnal');
            let found = false;
            
            for (let i = 0; i < selectKelas.options.length; i++) {
                if (selectKelas.options[i].value === decodedText) {
                    selectKelas.selectedIndex = i;
                    found = true; break;
                }
            }
            tutupScannerKelas();
            if (found) {
                alert("Berhasil! Kamera mendeteksi Anda berada di kelas " + decodedText);
            } else {
                alert("QR Code (" + decodedText + ") tidak dikenali oleh sistem kelas. Pastikan Anda men-scan QR yang benar.");
            }
        }

        // Jalankan deteksi GPS saat halaman dimuat jika elemen tersedia
        if (document.getElementById('gps-status-title')) {
            updateGPSStatus();
        }
    </script>

    <!-- Modal Alarm Absen Mengajar & Selesai Mengajar -->
    <div id="alarm-mengajar-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm hidden">
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6 text-center transform transition-all scale-100 relative overflow-hidden mx-4">
            <div id="alarm-header-bar" class="absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-emerald-500 to-teal-600"></div>
            
            <div id="alarm-icon-box" class="mx-auto w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mb-4 shadow-inner">
                <i id="alarm-icon" class="fas fa-bell"></i>
            </div>
            
            <h3 id="alarm-title" class="text-xl font-black text-gray-900 mb-1">⏰ Waktunya Absen Mengajar!</h3>
            <p id="alarm-subtitle" class="text-xs text-gray-500 mb-4">Anda memiliki jadwal mengajar hari ini:</p>

            <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 mb-5 text-left space-y-1.5 text-xs">
                <div class="flex justify-between text-gray-600">
                    <span class="font-medium"><i class="fas fa-book text-emerald-600 mr-1"></i> Mata Pelajaran:</span>
                    <span id="alarm-mapel" class="font-bold text-gray-900"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span class="font-medium"><i class="fas fa-door-open text-emerald-600 mr-1"></i> Kelas:</span>
                    <span id="alarm-kelas" class="font-bold text-gray-900"></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span class="font-medium"><i class="fas fa-clock text-emerald-600 mr-1"></i> Jam Ke / Waktu:</span>
                    <span id="alarm-waktu" class="font-bold text-emerald-600"></span>
                </div>
            </div>

            <div class="flex gap-2 justify-center">
                <button id="alarm-btn-action" onclick="handleAlarmClick()" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-lg transition-all flex items-center justify-center gap-2">
                    <i id="alarm-btn-icon" class="fas fa-qrcode text-sm"></i> <span id="alarm-btn-text">Absen Mengajar Sekarang</span>
                </button>
                <button onclick="closeAlarmModal()" class="py-3 px-4 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        // ==========================================
        // SMART ALARM ABSEN MENGAJAR & JURNAL KBM
        // ==========================================
        const userSchedules = <?= json_encode($jadwal_hari_ini) ?>;
        const slotTimes = {
            1: { start: "05:00", end: "05:45" },
            2: { start: "05:45", end: "06:30" },
            3: { start: "07:30", end: "08:15" },
            4: { start: "08:15", end: "09:00" },
            5: { start: "09:00", end: "09:45" },
            6: { start: "09:45", end: "10:30" },
            7: { start: "10:30", end: "11:15" },
            8: { start: "11:15", end: "12:00" },
            9: { start: "16:00", end: "16:45" },
            10: { start: "16:45", end: "17:30" },
            12: { start: "19:30", end: "20:15" },
            13: { start: "20:15", end: "21:00" }
        };

        let currentAlarmType = null;
        let firedAlarms = {};

        // Web Audio Synthesizer Alarm Chime
        function playAlarmSound(type) {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (!AudioCtx) return;
                const ctx = new AudioCtx();
                const frequencies = type === 'start' ? [523.25, 659.25, 783.99, 1046.50] : [1046.50, 783.99, 659.25, 523.25];
                
                frequencies.forEach((freq, i) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    gain.gain.setValueAtTime(0.25, ctx.currentTime + i * 0.22);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + i * 0.22 + 0.5);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + i * 0.22);
                    osc.stop(ctx.currentTime + i * 0.22 + 0.5);
                });
            } catch(e) { console.error("Web Audio alarm fail", e); }
        }

        // Web Notification API
        if ("Notification" in window && Notification.permission !== "granted" && Notification.permission !== "denied") {
            Notification.requestPermission();
        }

        function sendWebPushNotification(title, body) {
            if ("Notification" in window && Notification.permission === "granted") {
                new Notification(title, {
                    body: body,
                    icon: 'https://cdn-icons-png.flaticon.com/512/3602/3602145.png'
                });
            }
        }

        function triggerAlarm(type, sched, slot) {
            currentAlarmType = type;
            playAlarmSound(type);

            const modal = document.getElementById('alarm-mengajar-modal');
            const headerBar = document.getElementById('alarm-header-bar');
            const iconBox = document.getElementById('alarm-icon-box');
            const icon = document.getElementById('alarm-icon');
            const title = document.getElementById('alarm-title');
            const mapel = document.getElementById('alarm-mapel');
            const kelas = document.getElementById('alarm-kelas');
            const waktu = document.getElementById('alarm-waktu');
            const btnAction = document.getElementById('alarm-btn-action');
            const btnIcon = document.getElementById('alarm-btn-icon');
            const btnText = document.getElementById('alarm-btn-text');

            mapel.innerText = sched.nama_mapel || 'Mata Pelajaran';
            kelas.innerText = sched.nama_kelas || '-';
            waktu.innerText = `Jam ke-${sched.jam_ke} (${slot.start} - ${slot.end} WIB)`;

            if (type === 'start') {
                headerBar.className = 'absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-emerald-500 to-teal-600';
                iconBox.className = 'mx-auto w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mb-4 shadow-inner animate-bounce';
                icon.className = 'fas fa-bell';
                title.innerText = '⏰ Waktunya Absen Mengajar!';
                btnAction.className = 'w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs shadow-lg transition-all flex items-center justify-center gap-2';
                btnIcon.className = 'fas fa-qrcode text-sm';
                btnText.innerText = 'Absen Mengajar Sekarang';
                sendWebPushNotification("⏰ Waktunya Absen Mengajar!", `Mapel: ${sched.nama_mapel} (${sched.nama_kelas}) jam ke-${sched.jam_ke}`);
            } else {
                headerBar.className = 'absolute top-0 left-0 right-0 h-3 bg-gradient-to-r from-amber-500 to-orange-600';
                iconBox.className = 'mx-auto w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-3xl mb-4 shadow-inner animate-pulse';
                icon.className = 'fas fa-flag-checkered';
                title.innerText = '🔔 Jam Mengajar Telah Selesai!';
                btnAction.className = 'w-full py-3 px-4 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded-xl text-xs shadow-lg transition-all flex items-center justify-center gap-2';
                btnIcon.className = 'fas fa-pen-to-square text-sm';
                btnText.innerText = 'Isi Jurnal & Selesai Mengajar';
                sendWebPushNotification("🔔 Jam Mengajar Selesai!", `Segera isi Jurnal Mengajar untuk kelas ${sched.nama_kelas}`);
            }

            modal.classList.remove('hidden');
        }

        window.closeAlarmModal = function() {
            document.getElementById('alarm-mengajar-modal').classList.add('hidden');
        }

        window.handleAlarmClick = function() {
            closeAlarmModal();
            if (currentAlarmType === 'start') {
                const qrBox = document.getElementById('qr-scanner-section') || document.querySelector('form');
                if (qrBox) qrBox.scrollIntoView({ behavior: 'smooth' });
            } else {
                const jurnalForm = document.getElementById('form-jurnal-kbm') || document.querySelector('form[action*="jurnal"]');
                if (jurnalForm) jurnalForm.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Loop checker setiap 10 detik
        setInterval(() => {
            if (!userSchedules || userSchedules.length === 0) return;
            const now = new Date();
            const currentHM = String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
            const todayStr = now.toISOString().split('T')[0];

            userSchedules.forEach(sched => {
                const slot = slotTimes[sched.jam_ke];
                if (!slot) return;

                const startKey = `${todayStr}_start_${sched.id}_${slot.start}`;
                if (currentHM === slot.start && !firedAlarms[startKey]) {
                    firedAlarms[startKey] = true;
                    triggerAlarm('start', sched, slot);
                }

                const endKey = `${todayStr}_end_${sched.id}_${slot.end}`;
                if (currentHM === slot.end && !firedAlarms[endKey]) {
                    firedAlarms[endKey] = true;
                    triggerAlarm('end', sched, slot);
                }
            });
        }, 10000);

        // Auto focus / highlight based on ?tipe= query parameter
        document.addEventListener("DOMContentLoaded", function() {
            const params = new URLSearchParams(window.location.search);
            const tipe = params.get('tipe');
            if (tipe === 'pegawai') {
                const btn = document.getElementById('btn-absen-pegawai');
                if (btn) {
                    btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    btn.classList.add('ring-4', 'ring-teal-400', 'animate-pulse');
                    setTimeout(() => btn.classList.remove('animate-pulse'), 2500);
                }
            } else if (tipe === 'mengajar') {
                const btn = document.getElementById('btn-absen-mengajar');
                if (btn) {
                    btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    btn.classList.add('ring-4', 'ring-cyan-400', 'animate-pulse');
                    setTimeout(() => btn.classList.remove('animate-pulse'), 2500);
                }
            }
        });
    </script>
</body>
</html>