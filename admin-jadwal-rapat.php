<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'jadwal_rapat';
$ustadz_id = $_SESSION['ustadz_id'];
$today = date('Y-m-d');
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}
$user_roles = array_map('trim', $user_roles);

// Self-healing migration untuk tempat_rapat dan notulensi
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN tempat_rapat VARCHAR(100) DEFAULT NULL AFTER agenda");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN notulensi TEXT DEFAULT NULL AFTER tempat_rapat");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN notulensi_updated_at DATETIME DEFAULT NULL AFTER notulensi");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN notulensi_by INT DEFAULT NULL AFTER notulensi_updated_at");

// Handler AJAX Rincian Presensi Rapat Selesai
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'get_rapat_presensi') {
    header('Content-Type: application/json');
    $r_id = (int)($_GET['rapat_id'] ?? 0);
    $q_r = $conn->query("SELECT * FROM jadwal_rapat WHERE id = $r_id LIMIT 1");
    if (!$q_r || $q_r->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Rapat tidak ditemukan.']);
        exit;
    }
    $rapat = $q_r->fetch_assoc();
    
    $q_p = $conn->query("SELECT a.*, u.nama, u.role FROM absensi_pegawai a JOIN akun_ustadz u ON a.ustadz_id = u.id WHERE a.jenis_absen = 'Rapat' AND a.rapat_id = $r_id ORDER BY a.waktu_absen ASC");
    $presensi = [];
    if ($q_p) {
        while ($row = $q_p->fetch_assoc()) {
            $presensi[] = $row;
        }
    }
    
    echo json_encode(['status' => 'success', 'rapat' => $rapat, 'presensi' => $presensi]);
    exit;
}

// Fungsi Broadcast WA Undangan Rapat Resmi
function broadcast_undangan_rapat_wa($conn, $agenda, $tempat_rapat, $pengundang_label, $waktu_rapat, $target_roles, $target_ids, $jenis_rutin = 'tidak_rutin', $hari_rutin = '', $tanggal_rutin = null, $tgl_penyesuaian_libur = null, $target_ortu_ids = []) {
    $FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";
    if (file_exists(__DIR__ . '/config-key.php')) {
        require_once __DIR__ . '/config-key.php';
        if (defined('FONNTE_TOKEN')) {
            $FONNTE_TOKEN = FONNTE_TOKEN;
        }
    }
    
    $res_pegawai = $conn->query("SELECT id, nama, role, whatsapp FROM akun_ustadz WHERE whatsapp IS NOT NULL AND whatsapp != ''");
    if (!$res_pegawai || $res_pegawai->num_rows == 0) return;
    
    $days = ['Sunday'=>'Ahad', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
    $day_name = $days[date('l', strtotime($waktu_rapat))] ?? date('l', strtotime($waktu_rapat));
    $waktu_formatted = $day_name . ', ' . date('d M Y - H:i', strtotime($waktu_rapat)) . ' WIB';
    
    while ($p = $res_pegawai->fetch_assoc()) {
        $p_id = (int)$p['id'];
        $p_roles = array_map('trim', explode(',', $p['role'] ?? ''));
        $no_wa = preg_replace('/[^0-9]/', '', $p['whatsapp']);
        if (empty($no_wa)) continue;
        if (substr($no_wa, 0, 1) === '0') {
            $no_wa = '62' . substr($no_wa, 1);
        } elseif (substr($no_wa, 0, 2) !== '62') {
            $no_wa = '62' . $no_wa;
        }
        
        $is_target = false;
        
        if (in_array($p_id, array_map('intval', $target_ids))) {
            $is_target = true;
        }
        
        if (!$is_target && !empty($target_roles)) {
            if (in_array('semua_pegawai', $target_roles)) {
                $is_target = true;
            } else {
                foreach ($target_roles as $tr) {
                    if ($tr === 'musyrif' && (in_array('musyrif', $p_roles) || in_array('kepala_asrama', $p_roles))) {
                        $is_target = true; break;
                    }
                    if ($tr === 'admin_sekolah' && (in_array('admin_sekolah', $p_roles) || in_array('sekretaris_sekolah', $p_roles) || in_array('bendahara_sekolah', $p_roles))) {
                        $is_target = true; break;
                    }
                    if ($tr === 'kepala_sekolah' && in_array('kepala_sekolah', $p_roles)) {
                        $is_target = true; break;
                    }
                    if ($tr === 'kepala_mahad' && in_array('kepala_mahad', $p_roles)) {
                        $is_target = true; break;
                    }
                    if ($tr === 'ustadz_diknas' && in_array('ustadz', $p_roles)) {
                        $check_d = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $p_id AND m.kategori_mapel = 'Diknas' LIMIT 1");
                        if ($check_d && $check_d->num_rows > 0) { $is_target = true; break; }
                    }
                    if ($tr === 'ustadz_diniyah' && in_array('ustadz', $p_roles)) {
                        $check_dn = $conn->query("SELECT m.id FROM master_mapel m WHERE m.pengampu_id = $p_id AND m.kategori_mapel = 'Diniyah' LIMIT 1");
                        if ($check_dn && $check_dn->num_rows > 0) { $is_target = true; break; }
                    }
                }
            }
        }
        
        if ($is_target) {
            $pesan = "📢 *UNDANGAN RAPAT RESMI*\n"
                   . "-- SIM Yayasan Villa Quran --\n\n"
                   . "Kepada Yth. *" . $p['nama'] . "*\n\n"
                   . "Anda diundang untuk menghadiri rapat berikut:\n"
                   . "📌 *Agenda*: " . $agenda . "\n"
                   . "📍 *Tempat*: " . $tempat_rapat . "\n"
                   . "👤 *Penyelenggara*: " . $pengundang_label . "\n"
                   . "🕒 *Waktu Mulai*: " . $waktu_formatted . "\n\n"
                   . "Diharapkan hadir tepat waktu dan melakukan absensi rapat melalui sistem:\n"
                   . "🔗 https://villaquranindonesia.com/admin-absensi-pegawai.php\n\n"
                   . "-- SIM Yayasan Villa Quran --";
                   
            $waFd = ['target' => $no_wa, 'message' => $pesan];
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => "https://api.fonnte.com/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($waFd),
                CURLOPT_HTTPHEADER => ["Authorization: $FONNTE_TOKEN"],
                CURLOPT_TIMEOUT => 10
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}

// Handler Pembuatan Rapat Baru
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'buat_rapat') {
    $agenda = $conn->real_escape_string($_POST['agenda']);
    $pengundang = $conn->real_escape_string($_POST['pengundang'] ?? 'kepala_sekolah');
    $jenis_rutin = $conn->real_escape_string($_POST['jenis_rutin'] ?? 'insidental');
    $hari_rutin = $conn->real_escape_string($_POST['hari_rutin'] ?? '');
    
    $jam_rapat = !empty($_POST['jam_rapat']) ? $_POST['jam_rapat'] : '08:00';
    $tanggal_rutin = "NULL";
    
    if (!empty($_POST['tanggal_lengkap'])) {
        $tgl_l = $_POST['tanggal_lengkap'];
        $waktu_rapat = $conn->real_escape_string($tgl_l . ' ' . $jam_rapat . ':00');
        $tanggal_rutin = (int)date('d', strtotime($tgl_l));
        if (empty($hari_rutin)) {
            $days_map = ['Sunday'=>'Ahad', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
            $hari_rutin = $days_map[date('l', strtotime($tgl_l))] ?? '';
        }
    } elseif (!empty($_POST['waktu_rapat'])) {
        $waktu_rapat = $conn->real_escape_string($_POST['waktu_rapat']);
        if (!empty($_POST['tanggal_rutin'])) $tanggal_rutin = (int)$_POST['tanggal_rutin'];
    } else {
        $map_hari = ['Senin'=>'Monday', 'Selasa'=>'Tuesday', 'Rabu'=>'Wednesday', 'Kamis'=>'Thursday', 'Jumat'=>'Friday', 'Sabtu'=>'Saturday', 'Ahad'=>'Sunday'];
        $day_en = $map_hari[$hari_rutin] ?? 'Monday';
        $calc_date = date('Y-m-d', strtotime("next $day_en"));
        if (date('l') === $day_en) {
            $calc_date = date('Y-m-d');
        }
        $waktu_rapat = $conn->real_escape_string($calc_date . ' ' . $jam_rapat . ':00');
        $tanggal_rutin = (int)date('d', strtotime($calc_date));
    }
    
    $tgl_penyesuaian_libur_val = !empty($_POST['tgl_penyesuaian_libur']) ? $_POST['tgl_penyesuaian_libur'] : null;
    $tgl_penyesuaian_libur = !empty($tgl_penyesuaian_libur_val) ? "'" . $conn->real_escape_string($tgl_penyesuaian_libur_val) . "'" : "NULL";
    
    $target_roles = $_POST['target_roles'] ?? [];
    $target_ids = array_map('intval', $_POST['target_ids'] ?? []);
    $target_ortu_ids = array_map('intval', $_POST['target_ortu_ids'] ?? []);
    
    $peserta_terundang = json_encode([
        'roles' => $target_roles,
        'ids' => $target_ids,
        'ortu_ids' => $target_ortu_ids
    ]);
    $peserta_terundang_escaped = $conn->real_escape_string($peserta_terundang);
    
    $is_authorized = false;
    if ($pengundang === 'kepala_sekolah' || $pengundang === 'kepala_mahad' || $pengundang === 'ketua_yayasan') $is_authorized = true;
    if (in_array('kepala_sekolah', $user_roles) || in_array('kepala_mahad', $user_roles) || in_array('admin_sekolah', $user_roles) || in_array('musyrif', $user_roles) || in_array('super_admin', $user_roles)) $is_authorized = true;

    if ($is_authorized) {
        $tempat_rapat = $conn->real_escape_string($_POST['tempat_rapat'] ?? '');
        
        $sql_ins = "INSERT INTO jadwal_rapat (agenda, tempat_rapat, pengundang, peserta_terundang, waktu_mulai, jenis_rutin, hari_rutin, tanggal_rutin, tgl_penyesuaian_libur, status, created_by) VALUES ('$agenda', " . ($tempat_rapat ? "'$tempat_rapat'" : "NULL") . ", '$pengundang', '$peserta_terundang_escaped', '$waktu_rapat', '$jenis_rutin', " . ($hari_rutin ? "'$hari_rutin'" : "NULL") . ", $tanggal_rutin, $tgl_penyesuaian_libur, 'aktif', $ustadz_id)";
        $conn->query($sql_ins);
        
        $lbl_peng = 'Ketua Yayasan';
        if ($pengundang === 'kepala_sekolah') {
            $lbl_peng = 'Kepala Sekolah';
        } elseif ($pengundang === 'kepala_mahad') {
            $lbl_peng = "Kepala Ma'had";
        }
        
        broadcast_undangan_rapat_wa($conn, $_POST['agenda'], $tempat_rapat, $lbl_peng, $waktu_rapat, $target_roles, $target_ids, $jenis_rutin, $hari_rutin, $tanggal_rutin, $tgl_penyesuaian_libur_val, $target_ortu_ids);
        
        header("Location: admin-jadwal-rapat.php?sukses_rapat=1");
        exit;
    } else {
        header("Location: admin-jadwal-rapat.php?gagal_rapat=1");
        exit;
    }
}

// Handler Selesaikan Rapat
if (isset($_GET['selesaikan_rapat_id'])) {
    $r_id = (int)$_GET['selesaikan_rapat_id'];
    $check_r = $conn->query("SELECT created_by FROM jadwal_rapat WHERE id = $r_id")->fetch_assoc();
    if ($check_r && ($check_r['created_by'] == $ustadz_id || in_array('super_admin', $user_roles))) {
        $conn->query("UPDATE jadwal_rapat SET status = 'selesai' WHERE id = $r_id");
        header("Location: admin-jadwal-rapat.php?sukses_rapat=2");
        exit;
    }
}

// Handler Simpan Notulensi Rapat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_notulensi'])) {
    $r_id = (int)($_POST['rapat_id'] ?? 0);
    $notulensi = $conn->real_escape_string($_POST['notulensi'] ?? '');
    
    $check_r = $conn->query("SELECT created_by FROM jadwal_rapat WHERE id = $r_id")->fetch_assoc();
    $is_admin = in_array('admin_sekolah', $user_roles) || in_array('super_admin', $user_roles);
    
    if ($check_r && ($check_r['created_by'] == $ustadz_id || $is_admin)) {
        $conn->query("UPDATE jadwal_rapat SET 
            notulensi = '$notulensi', 
            notulensi_updated_at = NOW(), 
            notulensi_by = $ustadz_id 
            WHERE id = $r_id");
        header("Location: admin-jadwal-rapat.php?sukses_notulensi=1");
        exit;
    } else {
        header("Location: admin-jadwal-rapat.php?gagal_notulensi=1");
        exit;
    }
}

// Pengecekan Rapat Aktif
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
                if ($tr === 'ustadz' && in_array('ustadz', $user_roles)) {
                    $is_invited_rapat = true; break;
                }
            }
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

// Otoritas membuat rapat
$can_create_rapat = in_array('kepala_sekolah', $user_roles) || in_array('kepala_mahad', $user_roles) || in_array('admin_sekolah', $user_roles) || in_array('super_admin', $user_roles) || in_array('ketua_yayasan', $user_roles) || in_array('kepala_ldu', $user_roles) || in_array('direktur_ldu', $user_roles) || in_array('staff_ldu', $user_roles) || in_array('musyrif', $user_roles) || in_array('musyrifah', $user_roles);

// Fetch List Ustadz/Pegawai & Orangtua untuk Undangan Khusus
$all_ustadz = [];
$res_u = $conn->query("SELECT id, nama, role FROM akun_ustadz WHERE status_pegawai != 'Nonaktif' ORDER BY nama ASC");
if ($res_u) {
    while ($row = $res_u->fetch_assoc()) {
        $all_ustadz[] = $row;
    }
}

$all_ortu = [];
$res_o = $conn->query("SELECT id, nama_orangtua FROM akun_orangtua ORDER BY nama_orangtua ASC");
if ($res_o) {
    while ($row = $res_o->fetch_assoc()) {
        $all_ortu[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Rapat & Presensi | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Administrasi Digital Sekolah (SADIGS 4.0)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-handshake text-cyan-600 mr-2"></i>Jadwal & Presensi Rapat</h1>
                    <p class="text-gray-500 text-xs mt-1">Lakukan absensi kehadiran rapat aktif serta jadwalkan rapat koordinasi baru di sini.</p>
                </div>
            </div>

            <!-- NOTIFICATION MESSAGES -->
            <?php if (isset($_GET['sukses_notulensi'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> Notulensi rapat berhasil disimpan!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['gagal_notulensi'])): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-sm text-rose-600"></i> Gagal menyimpan notulensi. Anda tidak memiliki akses wewenang.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['sukses_rapat']) && $_GET['sukses_rapat'] == 1): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> Jadwal rapat berhasil dibuat dan broadcast undangan WhatsApp telah dikirim!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['sukses_rapat']) && $_GET['sukses_rapat'] == 2): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> Rapat berhasil diselesaikan dan diarsipkan!
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['gagal_rapat'])): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-sm text-rose-600"></i> Gagal membuat rapat. Anda tidak memiliki wewenang untuk pengundang tersebut.
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- COLUMN 1: TOMBOL ABSENSI RAPAT AKTIF -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 flex flex-col justify-between text-center">
                        <div>
                            <div class="w-14 h-14 bg-indigo-50 text-indigo-650 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-inner border border-indigo-100">
                                <i class="fas fa-users-rectangle"></i>
                            </div>
                            <h2 class="text-base font-bold text-gray-800 mb-2">Absensi Kehadiran Rapat</h2>
                            
                            <?php if (!$rapat_aktif): ?>
                                <p class="text-xs text-gray-400 mb-6 leading-relaxed">
                                    Tidak ada jadwal rapat aktif saat ini.
                                </p>
                                </div>
                                <button disabled class="w-full py-3.5 px-6 font-bold rounded-xl shadow-md bg-gray-150 text-gray-400 cursor-not-allowed flex items-center justify-center gap-2 text-xs">
                                    <i class="fas fa-calendar-xmark text-sm"></i>
                                    <span>Rapat Tidak Tersedia</span>
                                </button>
                            <?php elseif (!$is_invited_rapat): ?>
                                <div class="text-[11px] text-gray-500 mb-6 text-left bg-slate-50 border border-slate-100 rounded-xl p-3 leading-relaxed">
                                    <span class="block font-bold text-indigo-800 mb-1"><i class="fas fa-circle-info mr-1"></i> Rapat Aktif: <?= htmlspecialchars($rapat_aktif['agenda']) ?></span>
                                    Anda tidak terdaftar sebagai peserta wajib untuk rapat ini.
                                </div>
                                </div>
                                <button disabled class="w-full py-3.5 px-6 font-bold rounded-xl shadow-md bg-gray-150 text-gray-400 cursor-not-allowed flex items-center justify-center gap-2 text-xs">
                                    <i class="fas fa-user-slash text-sm"></i>
                                    <span>Tidak Diundang</span>
                                </button>
                            <?php else: ?>
                                <?php
                                $lbl_peng = '';
                                if ($rapat_aktif['pengundang'] === 'kepala_sekolah') $lbl_peng = 'Kepala Sekolah';
                                elseif ($rapat_aktif['pengundang'] === 'kepala_mahad') $lbl_peng = "Kepala Ma'had";
                                else $lbl_peng = 'Ketua Yayasan';
                                ?>
                                <div class="text-left bg-indigo-50 border border-indigo-100 rounded-xl p-3 mb-6 text-xs text-indigo-900 leading-relaxed">
                                    <p class="font-bold text-xs text-indigo-950 mb-1"><i class="fas fa-bullhorn mr-1 text-cyan-600"></i> <?= htmlspecialchars($rapat_aktif['agenda']) ?></p>
                                    <p class="mb-0.5"><span class="font-semibold text-indigo-700">Penyelenggara:</span> <?= $lbl_peng ?></p>
                                    <p class="mb-0.5"><span class="font-semibold text-indigo-700">Tempat:</span> <span class="text-amber-800 font-bold"><?= empty($rapat_aktif['tempat_rapat']) ? '-' : htmlspecialchars($rapat_aktif['tempat_rapat']) ?></span></p>
                                    <p><span class="font-semibold text-indigo-700">Waktu:</span> <?= date('H:i', strtotime($rapat_aktif['waktu_mulai'])) ?> WIB</p>
                                </div>
                                <p class="text-xs text-gray-500 mb-6">
                                    <?php if ($rapat_status === 'belum_absen'): ?>
                                        Belum absen hadir rapat.
                                    <?php elseif ($rapat_status === 'hadir'): ?>
                                        Sudah absen hadir. Klik jika rapat selesai.
                                    <?php else: ?>
                                        Selesai absensi hadir dan pulang rapat.
                                    <?php endif; ?>
                                </p>
                                </div>
                                
                                <button id="btn-absen-rapat" 
                                        data-rapat-id="<?= $rapat_id ?>"
                                        data-status="<?= $rapat_status ?>"
                                        <?= ($rapat_status === 'selesai') ? 'disabled' : '' ?>
                                        class="w-full py-3.5 px-6 font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2 text-xs <?= ($rapat_status === 'selesai') ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : (($rapat_status === 'belum_absen') ? 'bg-indigo-600 hover:bg-indigo-700 text-white hover:shadow-lg active:scale-95' : 'bg-rose-600 hover:bg-rose-700 text-white hover:shadow-lg active:scale-95') ?>">
                                    <i class="fas <?= $rapat_btn_icon ?> text-sm"></i>
                                    <span><?= $rapat_btn_text ?></span>
                                </button>
                            <?php endif; ?>
                    </div>

                    <!-- GPS Location Status -->
                    <div id="gps-status-card" class="bg-white border border-gray-200 rounded-xl p-4 text-xs text-gray-600 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-location-dot text-cyan-600 text-sm"></i>
                            <div>
                                <span class="font-bold text-gray-800">Status GPS Perangkat</span>
                                <span id="gps-coords-label" class="block text-[10px] text-gray-400 mt-0.5">Mendeteksi koordinat...</span>
                            </div>
                        </div>
                        <button id="btn-refresh-gps" class="text-[10px] font-bold text-cyan-600 hover:text-cyan-700 flex items-center gap-1">
                            <i class="fas fa-rotate text-xs"></i> Perbarui GPS
                        </button>
                    </div>
                </div>

                <!-- COLUMN 2: PANEL FORM & DAFTAR JADWAL RAPAT -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- PANEL 1: PEMBUATAN RAPAT BARU (JIKA BERWENANG) -->
                    <?php if ($can_create_rapat): ?>
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                            <div class="flex items-center justify-between mb-4 border-b pb-2">
                                <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider flex items-center gap-1.5">
                                    <i class="fas fa-calendar-plus text-cyan-600"></i> Buat Jadwal Rapat Baru
                                </h3>
                                <!-- Form Selector tabs based on user roles -->
                                <div class="flex gap-1.5 flex-wrap">
                                    <?php if (in_array('kepala_sekolah', $user_roles) || in_array('super_admin', $user_roles) || in_array('admin_sekolah', $user_roles)): ?>
                                        <button onclick="switchFormTab('sekolah')" id="tab-btn-sekolah" class="py-1 px-2.5 font-bold text-[10px] rounded-lg bg-cyan-600 text-white shadow-sm transition-all">Sekolah</button>
                                    <?php endif; ?>
                                    <?php if (in_array('kepala_mahad', $user_roles) || in_array('super_admin', $user_roles) || in_array('musyrif', $user_roles) || in_array('musyrifah', $user_roles)): ?>
                                        <button onclick="switchFormTab('mahad')" id="tab-btn-mahad" class="py-1 px-2.5 font-bold text-[10px] rounded-lg <?= (!in_array('kepala_sekolah', $user_roles) && !in_array('admin_sekolah', $user_roles) && !in_array('super_admin', $user_roles)) ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> shadow-sm transition-all">Ma'had</button>
                                    <?php endif; ?>
                                    <?php if (in_array('ketua_yayasan', $user_roles) || in_array('super_admin', $user_roles)): ?>
                                        <button onclick="switchFormTab('yayasan')" id="tab-btn-yayasan" class="py-1 px-2.5 font-bold text-[10px] rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 shadow-sm transition-all">Yayasan</button>
                                    <?php endif; ?>
                                    <?php if (in_array('kepala_ldu', $user_roles) || in_array('direktur_ldu', $user_roles) || in_array('staff_ldu', $user_roles) || in_array('ketua_yayasan', $user_roles) || in_array('super_admin', $user_roles)): ?>
                                        <button onclick="switchFormTab('ldu')" id="tab-btn-ldu" class="py-1 px-2.5 font-bold text-[10px] rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 shadow-sm transition-all">LDU</button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 1. FORM RAPAT SEKOLAH -->
                            <?php if (in_array('kepala_sekolah', $user_roles) || in_array('super_admin', $user_roles) || in_array('admin_sekolah', $user_roles)): ?>
                            <div id="form-container-sekolah" class="block">
                                <form action="admin-jadwal-rapat.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="kepala_sekolah">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Agenda / Nama Rapat</label>
                                            <textarea name="agenda" required rows="2" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Contoh: Rapat Koordinasi Kurikulum Diknas Bulanan"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Sifat Rapat</label>
                                            <select name="jenis_rutin" id="select_jenis_rutin_sekolah" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                                <option value="pekanan">Pekanan</option>
                                                <option value="bulanan">Bulanan</option>
                                                <option value="insidental" selected>Insidental (Sekali Jalan)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Tempat Rapat</label>
                                            <select name="tempat_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white font-semibold">
                                                <option value="">-- Pilih Tempat Rapat --</option>
                                                <option value="Gedung A">Gedung A</option>
                                                <option value="Gedung B">Gedung B</option>
                                                <option value="Gedung C">Gedung C</option>
                                                <option value="Masjid Taqwa">Masjid Taqwa</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai</label>
                                            <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" value="08:00">
                                        </div>
                                        <div id="wrapper_hari_sekolah" class="hidden">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Hari Rutin</label>
                                            <select name="hari_rutin" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                                <option value="Senin">Senin</option>
                                                <option value="Selasa">Selasa</option>
                                                <option value="Rabu">Rabu</option>
                                                <option value="Kamis">Kamis</option>
                                                <option value="Jumat">Jumat</option>
                                                <option value="Sabtu">Sabtu</option>
                                                <option value="Ahad">Ahad</option>
                                            </select>
                                        </div>
                                        <div id="wrapper_tanggal_sekolah" class="block md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Tanggal</label>
                                            <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_sekolah" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div id="wrapper_penyesuaian_libur_sekolah" class="hidden md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Alternatif (Jika Hari Libur)</label>
                                            <input type="date" name="tgl_penyesuaian_libur" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                        </div>
                                        
                                        <!-- Target Peserta Roles -->
                                        <div class="md:col-span-2 border-t pt-3">
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Target Peserta (Berdasarkan Jabatan)</label>
                                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs">
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="admin_sekolah" class="rounded text-cyan-600 focus:ring-cyan-500">
                                                    <span>Admin Sekolah</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="tutor" class="rounded text-cyan-600 focus:ring-cyan-500">
                                                    <span>Tutor</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="orang_tua" class="rounded text-cyan-600 focus:ring-cyan-500">
                                                    <span>Orangtua / Walisantri</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Undangan Khusus Per Nama -->
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-user-check text-cyan-600 mr-1"></i> Undangan Khusus Tutor / Pegawai (Per Nama)
                                                </label>
                                                <select name="target_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-cyan-500 h-24 bg-white">
                                                    <?php foreach ($all_ustadz as $u): ?>
                                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-users text-cyan-600 mr-1"></i> Undangan Khusus Orangtua (Per Nama)
                                                </label>
                                                <select name="target_ortu_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-cyan-500 h-24 bg-white">
                                                    <?php foreach ($all_ortu as $o): ?>
                                                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nama_orangtua']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-2">
                                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md flex items-center gap-1.5">
                                            <i class="fas fa-paper-plane text-xs"></i> Publikasikan Rapat Sekolah
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- 2. FORM RAPAT MA'HAD -->
                            <?php if (in_array('kepala_mahad', $user_roles) || in_array('super_admin', $user_roles) || in_array('musyrif', $user_roles) || in_array('musyrifah', $user_roles)): ?>
                            <div id="form-container-mahad" class="<?= (!in_array('kepala_sekolah', $user_roles) && !in_array('admin_sekolah', $user_roles)) ? 'block' : 'hidden' ?>">
                                <form action="admin-jadwal-rapat.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="kepala_mahad">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Agenda / Nama Rapat</label>
                                            <textarea name="agenda" required rows="2" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" placeholder="Contoh: Rapat Koordinasi Halaqoh / Kepengasuhan Bulanan"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Sifat Rapat</label>
                                            <select name="jenis_rutin" id="select_jenis_rutin_mahad" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                                                <option value="pekanan">Pekanan</option>
                                                <option value="bulanan">Bulanan</option>
                                                <option value="insidental" selected>Insidental (Sekali Jalan)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Tempat Rapat</label>
                                            <select name="tempat_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 bg-white font-semibold">
                                                <option value="">-- Pilih Tempat Rapat --</option>
                                                <option value="Gedung A">Gedung A</option>
                                                <option value="Gedung B">Gedung B</option>
                                                <option value="Gedung C">Gedung C</option>
                                                <option value="Masjid Taqwa">Masjid Taqwa</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai</label>
                                            <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" value="08:00">
                                        </div>
                                        <div id="wrapper_hari_mahad" class="hidden">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Hari Rutin</label>
                                            <select name="hari_rutin" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-emerald-500">
                                                <option value="Senin">Senin</option>
                                                <option value="Selasa">Selasa</option>
                                                <option value="Rabu">Rabu</option>
                                                <option value="Kamis">Kamis</option>
                                                <option value="Jumat">Jumat</option>
                                                <option value="Sabtu">Sabtu</option>
                                                <option value="Ahad">Ahad</option>
                                            </select>
                                        </div>
                                        <div id="wrapper_tanggal_mahad" class="block md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Tanggal</label>
                                            <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_mahad" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        
                                        <!-- Target Peserta Roles -->
                                        <div class="md:col-span-2 border-t pt-3">
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Target Peserta (Berdasarkan Jabatan)</label>
                                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs">
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="kepala_asrama" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span>Kepala Asrama</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="musyrif" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span>Musyrif</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="musyrifah" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span>Musyrifah</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="ustadzah" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span>Ustadzah</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="orang_tua" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                    <span>Orangtua / Walisantri</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Undangan Khusus Per Nama -->
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-user-check text-emerald-600 mr-1"></i> Undangan Khusus Ustadz, Ustadzah, Musyrif & Musyrifah (Per Nama)
                                                </label>
                                                <select name="target_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 h-24 bg-white">
                                                    <?php foreach ($all_ustadz as $u): ?>
                                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-users text-emerald-600 mr-1"></i> Undangan Khusus Orangtua (Per Nama)
                                                </label>
                                                <select name="target_ortu_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 h-24 bg-white">
                                                    <?php foreach ($all_ortu as $o): ?>
                                                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nama_orangtua']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-2">
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md flex items-center gap-1.5">
                                            <i class="fas fa-paper-plane text-xs"></i> Publikasikan Rapat Ma'had
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- 3. FORM RAPAT YAYASAN -->
                            <?php if (in_array('ketua_yayasan', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <div id="form-container-yayasan" class="<?= (!in_array('kepala_sekolah', $user_roles) && !in_array('admin_sekolah', $user_roles) && !in_array('kepala_mahad', $user_roles)) ? 'block' : 'hidden' ?>">
                                <form action="admin-jadwal-rapat.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="ketua_yayasan">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Agenda / Nama Rapat</label>
                                            <textarea name="agenda" required rows="2" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" placeholder="Contoh: Rapat Koordinasi Evaluasi Yayasan Lintas Unit"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Sifat Rapat</label>
                                            <select name="jenis_rutin" id="select_jenis_rutin_yayasan" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500">
                                                <option value="pekanan">Pekanan</option>
                                                <option value="bulanan">Bulanan</option>
                                                <option value="insidental" selected>Insidental (Sekali Jalan)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Tempat Rapat</label>
                                            <select name="tempat_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500 bg-white font-semibold">
                                                <option value="">-- Pilih Tempat Rapat --</option>
                                                <option value="Gedung A">Gedung A</option>
                                                <option value="Gedung B">Gedung B</option>
                                                <option value="Gedung C">Gedung C</option>
                                                <option value="Masjid Taqwa">Masjid Taqwa</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai</label>
                                            <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" value="09:00">
                                        </div>
                                        <div id="wrapper_hari_yayasan" class="hidden">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Hari Rutin</label>
                                            <select name="hari_rutin" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500">
                                                <option value="Senin">Senin</option>
                                                <option value="Selasa">Selasa</option>
                                                <option value="Rabu">Rabu</option>
                                                <option value="Kamis">Kamis</option>
                                                <option value="Jumat">Jumat</option>
                                                <option value="Sabtu">Sabtu</option>
                                                <option value="Ahad">Ahad</option>
                                            </select>
                                        </div>
                                        <div id="wrapper_tanggal_yayasan" class="block md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Tanggal</label>
                                            <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_yayasan" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-amber-500" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        
                                        <!-- Target Peserta Roles -->
                                        <div class="md:col-span-2 border-t pt-3">
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Target Peserta (Berdasarkan Jabatan)</label>
                                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs">
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="semua_pegawai" class="rounded text-amber-600 focus:ring-amber-500">
                                                    <span>Semua Pegawai & Asatidz</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="kepala_mahad" class="rounded text-amber-600 focus:ring-amber-500">
                                                    <span>Kepala Ma'had</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="kepala_sekolah" class="rounded text-amber-600 focus:ring-amber-500">
                                                    <span>Kepala Sekolah</span>
                                                </label>
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="kepala_ldu" class="rounded text-amber-600 focus:ring-amber-500">
                                                    <span>Kepala LDU</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Undangan Khusus Per Nama -->
                                        <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-user-check text-amber-600 mr-1"></i> Undangan Khusus Semua Pegawai (Per Nama)
                                                </label>
                                                <select name="target_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-amber-500 h-24 bg-white">
                                                    <?php foreach ($all_ustadz as $u): ?>
                                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                            </div>
                                            <div>
                                                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                    <i class="fas fa-users text-amber-600 mr-1"></i> Undangan Khusus Orangtua (Per Nama)
                                                </label>
                                                <select name="target_ortu_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-amber-500 h-24 bg-white">
                                                    <?php foreach ($all_ortu as $o): ?>
                                                        <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nama_orangtua']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-2">
                                        <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md flex items-center gap-1.5">
                                            <i class="fas fa-paper-plane text-xs"></i> Publikasikan Rapat Yayasan
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- 4. FORM RAPAT LDU (BARU) -->
                            <?php if (in_array('kepala_ldu', $user_roles) || in_array('direktur_ldu', $user_roles) || in_array('staff_ldu', $user_roles) || in_array('ketua_yayasan', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <div id="form-container-ldu" class="<?= (!in_array('kepala_sekolah', $user_roles) && !in_array('admin_sekolah', $user_roles) && !in_array('kepala_mahad', $user_roles) && !in_array('ketua_yayasan', $user_roles)) ? 'block' : 'hidden' ?>">
                                <form action="admin-jadwal-rapat.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="kepala_ldu">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Agenda / Nama Rapat</label>
                                            <textarea name="agenda" required rows="2" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Rapat Koordinasi Program LDU Bulanan"></textarea>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Sifat Rapat</label>
                                            <select name="jenis_rutin" id="select_jenis_rutin_ldu" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500">
                                                <option value="pekanan">Pekanan</option>
                                                <option value="bulanan">Bulanan</option>
                                                <option value="insidental" selected>Insidental (Sekali Jalan)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Tempat Rapat</label>
                                            <select name="tempat_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white font-semibold">
                                                <option value="">-- Pilih Tempat Rapat --</option>
                                                <option value="Gedung A">Gedung A</option>
                                                <option value="Gedung B">Gedung B</option>
                                                <option value="Gedung C">Gedung C</option>
                                                <option value="Masjid Taqwa">Masjid Taqwa</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Jam Mulai</label>
                                            <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" value="08:30">
                                        </div>
                                        <div id="wrapper_hari_ldu" class="hidden">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Hari Rutin</label>
                                            <select name="hari_rutin" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500">
                                                <option value="Senin">Senin</option>
                                                <option value="Selasa">Selasa</option>
                                                <option value="Rabu">Rabu</option>
                                                <option value="Kamis">Kamis</option>
                                                <option value="Jumat">Jumat</option>
                                                <option value="Sabtu">Sabtu</option>
                                                <option value="Ahad">Ahad</option>
                                            </select>
                                        </div>
                                        <div id="wrapper_tanggal_ldu" class="block md:col-span-2">
                                            <label class="block text-xs font-bold text-gray-700 mb-1">Pilih Tanggal</label>
                                            <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_ldu" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" value="<?= date('Y-m-d') ?>">
                                        </div>
                                        
                                        <!-- Target Peserta Roles -->
                                        <div class="md:col-span-2 border-t pt-3">
                                            <label class="block text-xs font-bold text-gray-700 mb-2">Target Peserta (Berdasarkan Jabatan)</label>
                                            <div class="flex flex-wrap gap-x-4 gap-y-2 text-xs">
                                                <label class="flex items-center space-x-1.5 cursor-pointer">
                                                    <input type="checkbox" name="target_roles[]" value="staff_ldu" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                    <span>Staff LDU</span>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- Undangan Khusus Per Nama -->
                                        <div class="md:col-span-2 bg-slate-50 p-3 rounded-xl border border-slate-200">
                                            <label class="block text-[11px] font-bold text-gray-700 mb-1">
                                                <i class="fas fa-user-check text-indigo-600 mr-1"></i> Undangan Khusus Staff LDU / Pegawai (Per Nama)
                                            </label>
                                            <select name="target_ids[]" multiple class="w-full px-2.5 py-1.5 border rounded-lg text-xs focus:ring-2 focus:ring-indigo-500 h-24 bg-white">
                                                <?php foreach ($all_ustadz as $u): ?>
                                                    <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['role']) ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                            <span class="text-[9px] text-gray-400 block mt-0.5">*Tahan Ctrl / Cmd untuk memilih beberapa nama</span>
                                        </div>
                                    </div>
                                    <div class="flex justify-end pt-2">
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md flex items-center gap-1.5">
                                            <i class="fas fa-paper-plane text-xs"></i> Publikasikan Rapat LDU
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>

                    <!-- PANEL 2: DAFTAR RAPAT AKTIF -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4 flex items-center gap-1.5 border-b pb-2">
                            <i class="fas fa-play text-cyan-600"></i> Rapat Aktif Saat Ini
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-150 text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500">
                                        <th class="px-3 py-2 text-left font-bold">Agenda</th>
                                        <th class="px-3 py-2 text-left font-bold">Penyelenggara</th>
                                        <th class="px-3 py-2 text-left font-bold">Tempat</th>
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
                                            <tr class="hover:bg-slate-50/55 transition">
                                                <td class="px-3 py-3 font-semibold text-gray-800"><?= htmlspecialchars($r['agenda']) ?></td>
                                                <td class="px-3 py-3 text-gray-500 font-medium"><?= $lbl_role ?></td>
                                                <td class="px-3 py-3 text-amber-700 font-bold"><?= empty($r['tempat_rapat']) ? '-' : htmlspecialchars($r['tempat_rapat']) ?></td>
                                                <td class="px-3 py-3 text-gray-500"><?= date('d M Y H:i', strtotime($r['waktu_mulai'])) ?> WIB</td>
                                                <td class="px-3 py-3 text-center">
                                                    <?php if ($r['created_by'] == $ustadz_id || in_array('super_admin', $user_roles)): ?>
                                                        <a href="admin-jadwal-rapat.php?selesaikan_rapat_id=<?= $r['id'] ?>" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-2.5 py-1 rounded-lg font-bold transition duration-150 text-[10px]" onclick="return confirm('Selesaikan rapat ini? Pegawai tidak akan bisa absen rapat ini lagi.')">
                                                            Selesaikan Rapat
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

                    <!-- PANEL 3: ARSIP & RIWAYAT RAPAT SELESAI -->
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                        <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-4 flex items-center gap-1.5 border-b pb-2">
                            <i class="fas fa-box-archive text-cyan-600"></i> Arsip & Riwayat Rapat Selesai
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-150 text-xs">
                                                          <tr class="bg-gray-50 text-gray-500">
                                        <th class="px-3 py-2 text-left font-bold">Agenda</th>
                                        <th class="px-3 py-2 text-left font-bold">Penyelenggara</th>
                                        <th class="px-3 py-2 text-left font-bold">Tempat</th>
                                        <th class="px-3 py-2 text-left font-bold">Waktu Mulai</th>
                                        <th class="px-3 py-2 text-center font-bold">Hadir</th>
                                        <th class="px-3 py-2 text-center font-bold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php
                                    $res_finished = $conn->query("SELECT * FROM jadwal_rapat WHERE status = 'selesai' ORDER BY waktu_mulai DESC LIMIT 20");
                                    if ($res_finished && $res_finished->num_rows > 0):
                                        while ($fr = $res_finished->fetch_assoc()):
                                            $fr_id = (int)$fr['id'];
                                            $lbl_role = '';
                                            if ($fr['pengundang'] === 'kepala_sekolah') $lbl_role = 'Kepala Sekolah';
                                            elseif ($fr['pengundang'] === 'kepala_mahad') $lbl_role = "Kepala Ma'had";
                                            else $lbl_role = 'Ketua Yayasan';
 
                                            $q_count_hadir = $conn->query("SELECT COUNT(DISTINCT ustadz_id) as jml FROM absensi_pegawai WHERE jenis_absen = 'Rapat' AND rapat_id = $fr_id AND status_kehadiran IN ('Masuk', 'Pulang', 'Hadir')");
                                            $jml_hadir_rapat = $q_count_hadir ? (int)($q_count_hadir->fetch_assoc()['jml'] ?? 0) : 0;
                                    ?>
                                            <tr class="hover:bg-slate-50/55 transition">
                                                <td class="px-3 py-3 font-semibold text-gray-800"><?= htmlspecialchars($fr['agenda']) ?></td>
                                                <td class="px-3 py-3 text-gray-500 font-medium"><?= $lbl_role ?></td>
                                                <td class="px-3 py-3 text-amber-700 font-bold"><?= empty($fr['tempat_rapat']) ? '-' : htmlspecialchars($fr['tempat_rapat']) ?></td>
                                                <td class="px-3 py-3 text-gray-500"><?= date('d M Y H:i', strtotime($fr['waktu_mulai'])) ?> WIB</td>
                                                <td class="px-3 py-3 text-center">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                        <?= $jml_hadir_rapat ?> Hadir
                                                    </span>
                                                </td>
                                                <td class="px-3 py-3 text-center">
                                                    <button type="button" onclick="showModalPresensiRapat(<?= $fr_id ?>, '<?= htmlspecialchars(addslashes($fr['agenda'])) ?>')" class="bg-cyan-50 hover:bg-cyan-100 text-cyan-700 font-bold px-2 py-1 rounded text-[10px] transition border border-cyan-200">
                                                        Lihat Presensi
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="5" class="px-3 py-4 text-center text-gray-400">Belum ada riwayat rapat yang diselesaikan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL DETAIL PRESENSI RAPAT -->
            <div id="modal-presensi-rapat" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="hideModalPresensiRapat()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                        <div class="flex justify-between items-start border-b pb-2 mb-4">
                            <h3 class="text-sm font-bold text-gray-800" id="modal-title-agenda">Rincian Kehadiran Rapat</h3>
                            <button type="button" onclick="hideModalPresensiRapat()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-base"></i></button>
                        </div>
                        <div class="max-h-72 overflow-y-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500">
                                        <th class="px-3 py-2 text-left font-semibold">Nama Peserta</th>
                                        <th class="px-3 py-2 text-left font-semibold">Waktu Presensi</th>
                                        <th class="px-3 py-2 text-center font-semibold">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="modal-body-presensi" class="divide-y divide-gray-100">
                                    <!-- Dynamic Rows -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- NOTULENSI SECTION -->
                        <div id="modal-notulensi-section" class="mt-4 pt-4 border-t border-gray-150 text-xs">
                            <!-- Form Edit Notulensi (Khusus Admin / Pembuat Rapat) -->
                            <div id="notulensi-form-container" class="hidden">
                                <h4 class="font-bold text-gray-800 mb-2 flex items-center gap-1.5">
                                    <i class="fas fa-edit text-cyan-600"></i>
                                    <span>Tulis / Edit Notulensi Rapat</span>
                                </h4>
                                <form method="POST" action="admin-jadwal-rapat.php">
                                    <input type="hidden" name="rapat_id" id="notulensi-rapat-id">
                                    <textarea name="notulensi" id="notulensi-text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 mb-2 focus:border-cyan-500" placeholder="Tulis hasil keputusan rapat, saran, dan langkah berikutnya di sini..."></textarea>
                                    <div class="text-right">
                                        <button type="submit" name="simpan_notulensi" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-3 py-1.5 rounded-lg transition text-[10px] shadow">
                                            Simpan Notulensi
                                        </button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Tampilan Static Notulensi (Untuk Peserta Lain) -->
                            <div id="notulensi-static-container" class="hidden">
                                <h4 class="font-bold text-gray-800 mb-1.5 flex items-center gap-1.5">
                                    <i class="fas fa-file-invoice text-cyan-600"></i>
                                    <span>Notulensi Hasil Rapat</span>
                                </h4>
                                <div id="notulensi-static-text" class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-gray-700 leading-relaxed whitespace-pre-wrap italic"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GPS ALERT MODAL -->
            <div id="alert-modal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div id="modal-card" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                        <div class="flex items-start gap-4">
                            <div id="modal-icon-container" class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:h-10 sm:w-10">
                                <!-- Dynamic Icon -->
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-sm font-bold text-gray-900" id="alert-modal-title">Checking GPS</h3>
                                <p class="text-xs text-gray-500 mt-2" id="alert-modal-desc">Meminta koordinat GPS...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
    
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
            document.getElementById('sidebar-hr').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
        });

        // GPS Logics
        let userLatitude = null;
        let userLongitude = null;

        function updateGPSStatus() {
            const gpsLabel = document.getElementById('gps-coords-label');
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        userLatitude = position.coords.latitude;
                        userLongitude = position.coords.longitude;
                        gpsLabel.innerHTML = `Lat: <b>${userLatitude.toFixed(6)}</b>, Lon: <b>${userLongitude.toFixed(6)}</b>`;
                        gpsLabel.className = "block text-[10px] text-emerald-600 font-semibold mt-0.5";
                    },
                    (error) => {
                        gpsLabel.innerText = "Gagal mendeteksi lokasi (Izin ditolak/GPS nonaktif).";
                        gpsLabel.className = "block text-[10px] text-rose-500 font-semibold mt-0.5";
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                gpsLabel.innerText = "Browser tidak mendukung geolocation.";
                gpsLabel.className = "block text-[10px] text-rose-500 font-semibold mt-0.5";
            }
        }

        // Initialize GPS
        updateGPSStatus();

        document.getElementById('btn-refresh-gps').addEventListener('click', (e) => {
            e.preventDefault();
            updateGPSStatus();
        });

        // Trigger absensi
        function doAbsensi(jenisAbsen) {
            const btn = document.getElementById('btn-absen-rapat');
            if (!btn) return;
            if (userLatitude === null || userLongitude === null) {
                showAlertModal('Akses GPS Dibutuhkan', 'Gagal mendapatkan lokasi Anda. Pastikan GPS aktif dan izinkan lokasi di pengaturan browser.', 'error');
                return;
            }
            sendAbsensiRequest(jenisAbsen, btn);
        }

        function sendAbsensiRequest(jenisAbsen, btnElement) {
            btnElement.disabled = true;
            const originalHTML = btnElement.innerHTML;
            btnElement.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Memproses...</span>`;

            const formData = new FormData();
            formData.append('user_lat', userLatitude);
            formData.append('user_lon', userLongitude);
            formData.append('jenis_absen', jenisAbsen);
            
            const rId = btnElement.getAttribute('data-rapat-id');
            if (rId) formData.append('rapat_id', rId);

            fetch('proses-absen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlertModal('Absensi Berhasil', data.message, 'success');
                    setTimeout(() => window.location.reload(), 3000);
                } else {
                    showAlertModal('Absensi Ditolak', data.message, 'error');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                showAlertModal('Kesalahan Koneksi', 'Terjadi kesalahan saat menghubungi server.', 'error');
                btnElement.disabled = false;
                btnElement.innerHTML = originalHTML;
            });
        }

        const btnRapat = document.getElementById('btn-absen-rapat');
        if (btnRapat) {
            btnRapat.addEventListener('click', () => {
                doAbsensi('Rapat');
            });
        }

        // Modal Helpers
        function showAlertModal(title, desc, status) {
            const modal = document.getElementById('alert-modal');
            document.getElementById('alert-modal-title').innerText = title;
            document.getElementById('alert-modal-desc').innerText = desc;
            
            const iconContainer = document.getElementById('modal-icon-container');
            iconContainer.className = "flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:h-10 sm:w-10 " + 
                (status === 'success' ? 'bg-emerald-100 text-emerald-600' : (status === 'error' ? 'bg-rose-100 text-rose-600' : 'bg-amber-100 text-amber-600'));
            
            iconContainer.innerHTML = status === 'success' ? '<i class="fas fa-circle-check text-xl"></i>' : '<i class="fas fa-triangle-exclamation text-xl"></i>';
            
            modal.classList.remove('hidden');
            if (status === 'success' || status === 'warning') {
                // Auto hide
                setTimeout(() => modal.classList.add('hidden'), 3500);
            } else {
                setTimeout(() => modal.classList.add('hidden'), 5000);
            }
        }

        const currentUstadzId = <?= (int)$ustadz_id ?>;
        const isAdmin = <?= json_encode(in_array('admin_sekolah', $user_roles) || in_array('super_admin', $user_roles)) ?>;

        window.showModalPresensiRapat = function(rapatId, agenda) {
            document.getElementById('modal-title-agenda').innerText = "Rincian Kehadiran: " + agenda;
            const body = document.getElementById('modal-body-presensi');
            body.innerHTML = '<tr><td colspan="3" class="px-3 py-4 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-1"></i> Memuat data...</td></tr>';
            
            // Hide notulensi containers initially
            document.getElementById('notulensi-form-container').classList.add('hidden');
            document.getElementById('notulensi-static-container').classList.add('hidden');
            
            document.getElementById('modal-presensi-rapat').classList.remove('hidden');
            
            fetch('admin-jadwal-rapat.php?ajax_action=get_rapat_presensi&rapat_id=' + rapatId)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.presensi.length === 0) {
                        body.innerHTML = '<tr><td colspan="3" class="px-3 py-4 text-center text-gray-400">Belum ada peserta yang hadir.</td></tr>';
                    } else {
                        let html = '';
                        data.presensi.forEach(p => {
                            html += `<tr>
                                <td class="px-3 py-2 text-gray-800 font-semibold">${p.nama} <span class="text-[9px] text-gray-400">(${p.role})</span></td>
                                <td class="px-3 py-2 text-gray-500">${p.waktu_absen}</td>
                                <td class="px-3 py-2 text-center"><span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">${p.status_kehadiran}</span></td>
                            </tr>`;
                        });
                        body.innerHTML = html;
                    }
                    
                    // Render Notulensi
                    const canEdit = isAdmin || (data.rapat.created_by == currentUstadzId);
                    const notulensiText = data.rapat.notulensi || '';
                    
                    if (canEdit) {
                        document.getElementById('notulensi-rapat-id').value = rapatId;
                        document.getElementById('notulensi-text').value = notulensiText;
                        document.getElementById('notulensi-form-container').classList.remove('hidden');
                    } else {
                        document.getElementById('notulensi-static-text').innerText = notulensiText || 'Belum ada notulensi hasil rapat yang diisi.';
                        document.getElementById('notulensi-static-container').classList.remove('hidden');
                    }
                } else {
                    body.innerHTML = `<tr><td colspan="3" class="px-3 py-4 text-center text-rose-500 font-semibold">${data.message}</td></tr>`;
                }
            })
            .catch(err => {
                body.innerHTML = '<tr><td colspan="3" class="px-3 py-4 text-center text-rose-500 font-semibold">Gagal memuat data dari server.</td></tr>';
            });
        }

        window.hideModalPresensiRapat = function() {
            document.getElementById('modal-presensi-rapat').classList.add('hidden');
        }

        // Form tabs
        window.switchFormTab = function(tabName) {
            const formSekolah = document.getElementById('form-container-sekolah');
            const formMahad = document.getElementById('form-container-mahad');
            const formYayasan = document.getElementById('form-container-yayasan');
            const formLdu = document.getElementById('form-container-ldu');

            const btnSekolah = document.getElementById('tab-btn-sekolah');
            const btnMahad = document.getElementById('tab-btn-mahad');
            const btnYayasan = document.getElementById('tab-btn-yayasan');
            const btnLdu = document.getElementById('tab-btn-ldu');

            if (formSekolah) formSekolah.classList.add('hidden');
            if (formMahad) formMahad.classList.add('hidden');
            if (formYayasan) formYayasan.classList.add('hidden');
            if (formLdu) formLdu.classList.add('hidden');

            const defaultClass = 'py-1 px-2.5 font-bold text-[10px] rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 shadow-sm transition-all';
            if (btnSekolah) btnSekolah.className = defaultClass;
            if (btnMahad) btnMahad.className = defaultClass;
            if (btnYayasan) btnYayasan.className = defaultClass;
            if (btnLdu) btnLdu.className = defaultClass;

            if (tabName === 'sekolah' && formSekolah) {
                formSekolah.classList.remove('hidden');
                if (btnSekolah) btnSekolah.className = 'py-1 px-2.5 font-bold text-[10px] rounded-lg bg-cyan-600 text-white shadow-sm transition-all';
            } else if (tabName === 'mahad' && formMahad) {
                formMahad.classList.remove('hidden');
                if (btnMahad) btnMahad.className = 'py-1 px-2.5 font-bold text-[10px] rounded-lg bg-emerald-600 text-white shadow-sm transition-all';
            } else if (tabName === 'yayasan' && formYayasan) {
                formYayasan.classList.remove('hidden');
                if (btnYayasan) btnYayasan.className = 'py-1 px-2.5 font-bold text-[10px] rounded-lg bg-amber-600 text-white shadow-sm transition-all';
            } else if (tabName === 'ldu' && formLdu) {
                formLdu.classList.remove('hidden');
                if (btnLdu) btnLdu.className = 'py-1 px-2.5 font-bold text-[10px] rounded-lg bg-indigo-600 text-white shadow-sm transition-all';
            }
        }

        // Wire Rapat type selectors
        const selSekolah = document.getElementById('select_jenis_rutin_sekolah');
        if (selSekolah) {
            selSekolah.addEventListener('change', function() {
                const isRutin = this.value !== 'insidental';
                document.getElementById('wrapper_hari_sekolah').style.display = isRutin ? 'block' : 'none';
                document.getElementById('wrapper_penyesuaian_libur_sekolah').style.display = isRutin ? 'block' : 'none';
            });
        }
        const selMahad = document.getElementById('select_jenis_rutin_mahad');
        if (selMahad) {
            selMahad.addEventListener('change', function() {
                const isRutin = this.value !== 'insidental';
                document.getElementById('wrapper_hari_mahad').style.display = isRutin ? 'block' : 'none';
            });
        }
        const selYayasan = document.getElementById('select_jenis_rutin_yayasan');
        if (selYayasan) {
            selYayasan.addEventListener('change', function() {
                const isRutin = this.value !== 'insidental';
                document.getElementById('wrapper_hari_yayasan').style.display = isRutin ? 'block' : 'none';
            });
        }
        const selLdu = document.getElementById('select_jenis_rutin_ldu');
        if (selLdu) {
            selLdu.addEventListener('change', function() {
                const isRutin = this.value !== 'insidental';
                document.getElementById('wrapper_hari_ldu').style.display = isRutin ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
