<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

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
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN peserta_terundang TEXT DEFAULT NULL AFTER pengundang");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN jenis_rutin VARCHAR(20) DEFAULT 'tidak_rutin' AFTER waktu_mulai");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN hari_rutin VARCHAR(20) DEFAULT NULL AFTER jenis_rutin");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN tanggal_rutin INT DEFAULT NULL AFTER hari_rutin");
@$conn->query("ALTER TABLE jadwal_rapat ADD COLUMN tgl_penyesuaian_libur DATE DEFAULT NULL AFTER tanggal_rutin");

function broadcast_undangan_rapat_wa($conn, $agenda, $pengundang_label, $waktu_rapat, $target_roles, $target_ids, $jenis_rutin = 'tidak_rutin', $hari_rutin = '', $tanggal_rutin = null, $tgl_penyesuaian_libur = null, $target_ortu_ids = []) {
    $FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "Dtw72oRiQr8FympzpMHL";
    if (file_exists(__DIR__ . '/config-key.php')) {
        require_once __DIR__ . '/config-key.php';
        if (defined('FONNTE_TOKEN')) {
            $FONNTE_TOKEN = FONNTE_TOKEN;
        }
    }
    
    $days = ['Sunday'=>'Ahad', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
    $day_name = $days[date('l', strtotime($waktu_rapat))] ?? date('l', strtotime($waktu_rapat));
    $waktu_formatted = $day_name . ', ' . date('d M Y - H:i', strtotime($waktu_rapat)) . ' WIB';
    
    $rutin_desc = "Tidak Rutin (Sekali Jalan)";
    if ($jenis_rutin === 'pekanan') {
        $rutin_desc = "Rutin Pekanan (Setiap Hari " . ucfirst($hari_rutin) . ")";
    } elseif ($jenis_rutin === 'bulanan') {
        $rutin_desc = "Rutin Bulanan (Setiap Tanggal " . $tanggal_rutin . ")";
    }
    
    $libur_info = "";
    if (!empty($tgl_penyesuaian_libur)) {
        $libur_info = "\n⚠️ *Penyesuaian Hari Libur*: Tanggal dipindah ke *" . date('d M Y', strtotime($tgl_penyesuaian_libur)) . "*";
    }
    
    // 1. Broadcast WA ke Pegawai & Asatidz terundang
    $res_pegawai = $conn->query("SELECT id, nama, role, whatsapp FROM akun_ustadz WHERE whatsapp IS NOT NULL AND whatsapp != ''");
    if ($res_pegawai && $res_pegawai->num_rows > 0) {
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
                foreach ($target_roles as $tr) {
                    if ($tr === 'admin_sekolah' && (in_array('admin_sekolah', $p_roles) || in_array('sekretaris_sekolah', $p_roles) || in_array('bendahara_sekolah', $p_roles))) {
                        $is_target = true; break;
                    }
                    if ($tr === 'tutor' && in_array('tutor', $p_roles)) {
                        $is_target = true; break;
                    }
                    if ($tr === 'kepala_sekolah' && in_array('kepala_sekolah', $p_roles)) {
                        $is_target = true; break;
                    }
                    if ($tr === 'musyrif' && (in_array('musyrif', $p_roles) || in_array('kepala_asrama', $p_roles))) {
                        $is_target = true; break;
                    }
                    if ($tr === 'ustadz' && in_array('ustadz', $p_roles)) {
                        $is_target = true; break;
                    }
                    if ($tr === 'trainer' && in_array('trainer', $p_roles)) {
                        $is_target = true; break;
                    }
                }
            }
            
            if ($is_target) {
                $pesan = "📢 *UNDANGAN RAPAT RESMI*\n"
                       . "-- SIM Yayasan Villa Quran --\n\n"
                       . "Kepada Yth. *" . $p['nama'] . "*\n\n"
                       . "Anda diundang untuk menghadiri rapat sekolah berikut:\n"
                       . "📌 *Agenda*: " . $agenda . "\n"
                       . "👤 *Penyelenggara*: " . $pengundang_label . "\n"
                       . "🔄 *Sifat Rapat*: " . $rutin_desc . "\n"
                       . "🕒 *Waktu Pelaksanaan*: " . $waktu_formatted . $libur_info . "\n\n"
                       . "Diharapkan hadir tepat waktu.\n\n"
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

    // 2. Broadcast WA ke Orangtua / Walisantri
    if (in_array('orangtua', $target_roles) || in_array('walisantri', $target_roles) || !empty($target_ortu_ids)) {
        $res_ortu = $conn->query("SELECT id, nama_orangtua, no_whatsapp FROM akun_orangtua WHERE no_whatsapp IS NOT NULL AND no_whatsapp != ''");
        if ($res_ortu && $res_ortu->num_rows > 0) {
            while ($o = $res_ortu->fetch_assoc()) {
                $o_id = (int)$o['id'];
                $no_wa = preg_replace('/[^0-9]/', '', $o['no_whatsapp']);
                if (empty($no_wa)) continue;
                if (substr($no_wa, 0, 1) === '0') {
                    $no_wa = '62' . substr($no_wa, 1);
                } elseif (substr($no_wa, 0, 2) !== '62') {
                    $no_wa = '62' . $no_wa;
                }
                
                $is_target_ortu = false;
                if (in_array('orangtua', $target_roles) || in_array('walisantri', $target_roles)) {
                    $is_target_ortu = true;
                } elseif (in_array($o_id, array_map('intval', $target_ortu_ids))) {
                    $is_target_ortu = true;
                }
                
                if ($is_target_ortu) {
                    $pesan_ortu = "📢 *UNDANGAN RAPAT ORANG TUA / WALISANTRI*\n"
                               . "-- SIM Yayasan Villa Quran --\n\n"
                               . "Kepada Yth. Bapak/Ibu *" . $o['nama_orangtua'] . "* (Walisantri)\n\n"
                               . "Bapak/Ibu diundang untuk menghadiri rapat sekolah berikut:\n"
                               . "📌 *Agenda*: " . $agenda . "\n"
                               . "👤 *Penyelenggara*: " . $pengundang_label . "\n"
                               . "🔄 *Sifat Rapat*: " . $rutin_desc . "\n"
                               . "🕒 *Waktu Pelaksanaan*: " . $waktu_formatted . $libur_info . "\n\n"
                               . "Kehadiran Bapak/Ibu sangat diharapkan demi kelancaran kegiatan belajar mengajar ananda.\n\n"
                               . "-- SIM Yayasan Villa Quran --";
                               
                    $waFd = ['target' => $no_wa, 'message' => $pesan_ortu];
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
    }
}

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
        // Fallback untuk Pekanan jika hanya memilih Hari & Jam: hitung tanggal terdekat hari tersebut
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
        $sql_ins = "INSERT INTO jadwal_rapat (agenda, pengundang, peserta_terundang, waktu_mulai, jenis_rutin, hari_rutin, tanggal_rutin, tgl_penyesuaian_libur, status, created_by) VALUES ('$agenda', '$pengundang', '$peserta_terundang_escaped', '$waktu_rapat', '$jenis_rutin', " . ($hari_rutin ? "'$hari_rutin'" : "NULL") . ", $tanggal_rutin, $tgl_penyesuaian_libur, 'aktif', $ustadz_id)";
        $conn->query($sql_ins);
        
        $lbl_peng = 'Ketua Yayasan';
        if ($pengundang === 'kepala_sekolah') {
            $lbl_peng = 'Kepala Sekolah';
        } elseif ($pengundang === 'kepala_mahad') {
            $lbl_peng = "Kepala Ma'had";
        }
        
        // Broadcast WA otomatis ke seluruh peserta yang diundang
        broadcast_undangan_rapat_wa($conn, $_POST['agenda'], $lbl_peng, $waktu_rapat, $target_roles, $target_ids, $jenis_rutin, $hari_rutin, $tanggal_rutin, $tgl_penyesuaian_libur_val, $target_ortu_ids);
        
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
$res_pegawai = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Pegawai' AND status_kehadiran IN ('Masuk', 'Pulang') ORDER BY waktu_absen ASC");
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
// Semua ustadz / pegawai / staf yang terdaftar dan login berhak melakukan Absensi Pegawai
$is_eligible_pegawai = true;

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

// H. Pengecekan Absensi Mengajar & Otoritas Role
$res_mengajar = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Mengajar' ORDER BY waktu_absen ASC");
$mengajar_status = 'belum_absen';
if ($res_mengajar) {
    $num = $res_mengajar->num_rows;
    if ($num >= 2) {
        $mengajar_status = 'selesai';
    } elseif ($num == 1) {
        $mengajar_status = 'datang';
    }
}

$is_eligible_mengajar = in_array('ustadz', $user_roles) || in_array('super_admin', $user_roles);

$mengajar_btn_text = '';
$mengajar_btn_icon = '';
if ($mengajar_status === 'belum_absen') {
    $mengajar_btn_text = 'Mulai Mengajar (Masuk)';
    $mengajar_btn_icon = 'fa-door-open';
} elseif ($mengajar_status === 'datang') {
    $mengajar_btn_text = 'Selesai Mengajar (Pulang)';
    $mengajar_btn_icon = 'fa-door-closed';
} else {
    $mengajar_btn_text = 'Absensi Mengajar Selesai';
    $mengajar_btn_icon = 'fa-check-double';
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
$q_jadwal = $conn->query("SELECT jp.id, mk.nama_kelas, mm.nama_mapel 
                          FROM jadwal_pelajaran jp 
                          JOIN master_kelas mk ON jp.kelas_id = mk.id 
                          JOIN master_mapel mm ON jp.mapel_id = mm.id 
                          WHERE jp.ustadz_id = $ustadz_id AND jp.hari = '$hari_ini'");
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
            $cols_count = 1; // Rapat is always shown
            if ($is_eligible_pegawai) $cols_count++;
            if ($is_eligible_mengajar) $cols_count++;
            
            $grid_cols_class = 'flex justify-center max-w-md';
            if ($cols_count == 2) {
                $grid_cols_class = 'grid grid-cols-1 md:grid-cols-2';
            } elseif ($cols_count == 3) {
                $grid_cols_class = 'grid grid-cols-1 md:grid-cols-3';
            }
            ?>

            <!-- GRID KARTU ABSENSI -->
            <div class="<?= $grid_cols_class ?> gap-6 max-w-4xl mx-auto mb-8">
                
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

                <?php if ($is_eligible_mengajar): ?>
                    <!-- KARTU 2: ABSENSI MENGAJAR -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg w-full">
                        <div>
                            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                <i class="fas fa-chalkboard-user"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Mengajar</h2>
                            <p class="text-sm text-gray-500 mb-6 font-medium">
                                <?php if ($mengajar_status === 'belum_absen'): ?>
                                    Belum absen masuk mengajar.
                                <?php elseif ($mengajar_status === 'datang'): ?>
                                    Sudah absen masuk. Klik untuk absen selesai.
                                <?php else: ?>
                                    Selesai absensi mengajar hari ini.
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <button id="btn-absen-mengajar" 
                                data-status="<?= $mengajar_status ?>" 
                                data-jenis="Mengajar"
                                <?= ($mengajar_status === 'selesai') ? 'disabled' : '' ?>
                                class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($mengajar_status === 'selesai') ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : (($mengajar_status === 'belum_absen') ? 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-lg active:scale-95' : 'bg-rose-600 hover:bg-rose-700 text-white hover:shadow-lg active:scale-95') ?>">
                            <i class="fas <?= $mengajar_btn_icon ?> text-xl"></i>
                            <span><?= $mengajar_btn_text ?></span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- KARTU 3: ABSENSI RAPAT -->
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

            <?php if (in_array('kepala_sekolah', $user_roles) || in_array('kepala_mahad', $user_roles) || in_array('ketua_yayasan', $user_roles) || in_array('super_admin', $user_roles)): ?>
                <!-- PANEL MANAJEMEN RAPAT -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-4xl mx-auto mb-8 text-left">
                    <div class="border-b border-gray-100 pb-3 mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-calendar-plus text-indigo-600 mr-2"></i>Panel Pembuatan Jadwal Rapat</h2>
                            <p class="text-xs text-gray-500 mt-1">Pilih form rapat sesuai wewenang role Anda untuk menentukan agenda dan target undangan.</p>
                        </div>
                    </div>

                    <!-- Tab Switcher -->
                    <div class="flex border-b border-gray-200 mb-6 space-x-2">
                        <?php if (in_array('kepala_sekolah', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <button id="tab-btn-sekolah" onclick="switchFormTab('sekolah')" type="button" class="py-2.5 px-4 font-bold text-xs rounded-t-lg bg-indigo-600 text-white shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-school"></i> Form Rapat Sekolah
                            </button>
                        <?php endif; ?>
                        <?php if (in_array('kepala_mahad', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <button id="tab-btn-mahad" onclick="switchFormTab('mahad')" type="button" class="py-2.5 px-4 font-bold text-xs rounded-t-lg <?= (!in_array('kepala_sekolah', $user_roles) && !in_array('super_admin', $user_roles)) ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-mosque"></i> Form Rapat Ma'had
                            </button>
                        <?php endif; ?>
                        <?php if (in_array('ketua_yayasan', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <button id="tab-btn-yayasan" onclick="switchFormTab('yayasan')" type="button" class="py-2.5 px-4 font-bold text-xs rounded-t-lg <?= (!in_array('kepala_sekolah', $user_roles) && !in_array('kepala_mahad', $user_roles) && !in_array('super_admin', $user_roles)) ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?> shadow-sm transition-all flex items-center gap-2">
                                <i class="fas fa-landmark"></i> Form Rapat Yayasan
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Form Area (Col 1) -->
                        <div class="md:col-span-1 border-r border-gray-100 pr-0 md:pr-6">
                            
                            <!-- 1. FORM RAPAT SEKOLAH -->
                            <?php if (in_array('kepala_sekolah', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <div id="form-container-sekolah" class="block">
                                <h3 class="text-sm font-bold text-indigo-900 mb-4 pb-2 border-b border-indigo-100 flex items-center"><i class="fas fa-school mr-2 text-indigo-600"></i>Form Rapat Sekolah</h3>
                                <form action="admin-absensi-pegawai.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="kepala_sekolah">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Agenda / Nama Rapat</label>
                                        <textarea name="agenda" required rows="3" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" placeholder="Contoh: Rapat Evaluasi Kurikulum & Program Sekolah"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Sifat Rapat</label>
                                        <select name="jenis_rutin" id="select_jenis_rutin_sekolah" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                            <option value="pekanan">Pekanan</option>
                                            <option value="bulanan">Bulanan</option>
                                            <option value="insidental">Insidental</option>
                                        </select>
                                    </div>
                                    <div id="wrapper_hari_sekolah" class="block">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Hari</label>
                                        <select name="hari_rutin" id="select_hari_sekolah" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                            <option value="Senin">Senin</option>
                                            <option value="Selasa">Selasa</option>
                                            <option value="Rabu">Rabu</option>
                                            <option value="Kamis">Kamis</option>
                                            <option value="Jumat">Jumat</option>
                                            <option value="Sabtu">Sabtu</option>
                                            <option value="Ahad">Ahad</option>
                                        </select>
                                    </div>
                                    <div id="wrapper_tanggal_sekolah" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                                        <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_sekolah" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <input type="hidden" name="tanggal_rutin" id="input_tanggal_rutin_sekolah" value="">
                                    </div>
                                    <div id="wrapper_jam_sekolah" class="block">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Jam</label>
                                        <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500" value="08:00">
                                    </div>
                                    <div id="wrapper_penyesuaian_libur_sekolah" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Penyesuaian Hari Libur (Opsional)</label>
                                        <input type="date" name="tgl_penyesuaian_libur" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                                        <p class="text-[10px] text-gray-400 mt-1">* Isi jika jadwal rutin bertepatan dengan libur dan perlu disetting ulang ke tanggal lain.</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tujuan Undangan (Role / Grup Wajib)</label>
                                        <div class="space-y-1 bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700">
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="admin_sekolah" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                <span class="font-semibold text-gray-800">Admin Sekolah</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="tutor" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                <span class="font-semibold text-gray-800">Tutor</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="orangtua" class="rounded text-purple-600 focus:ring-purple-500">
                                                <span class="font-semibold text-purple-800"><i class="fas fa-users mr-1"></i> Orangtua / Walisantri</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Pegawai Spesifik (Admin & Tutor)</label>
                                        <div class="max-h-28 overflow-y-auto border border-gray-200 rounded-lg p-2.5 bg-gray-50 space-y-1 text-xs text-gray-700 mb-3">
                                            <?php
                                            $res_all_ustadz = $conn->query("SELECT id, nama, role FROM akun_ustadz ORDER BY nama ASC");
                                            if ($res_all_ustadz && $res_all_ustadz->num_rows > 0):
                                                while ($u = $res_all_ustadz->fetch_assoc()):
                                                    $r_list = array_map('trim', explode(',', strtolower($u['role'] ?? '')));
                                                    $is_admin_or_tutor = false;
                                                    foreach ($r_list as $r) {
                                                        if ($r === 'tutor' || str_contains($r, 'admin')) {
                                                            $is_admin_or_tutor = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$is_admin_or_tutor) continue;
                                            ?>
                                                    <label class="flex items-center space-x-2 hover:bg-gray-100 p-0.5 rounded cursor-pointer">
                                                        <input type="checkbox" name="target_ids[]" value="<?= $u['id'] ?>" class="rounded text-indigo-600 focus:ring-indigo-500">
                                                        <span><?= htmlspecialchars($u['nama']) ?> <span class="text-[10px] text-indigo-600 font-semibold">(<?= htmlspecialchars($u['role'] ?? 'pegawai') ?>)</span></span>
                                                    </label>
                                            <?php
                                                endwhile;
                                            endif;
                                            ?>
                                        </div>

                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Atau Pilih Orangtua / Walisantri Spesifik</label>
                                        <div class="max-h-32 overflow-y-auto border border-purple-200 rounded-lg p-2.5 bg-purple-50/40 space-y-1 text-xs text-gray-700">
                                            <?php
                                            $res_all_ortu = $conn->query("SELECT id, nama_orangtua, no_whatsapp FROM akun_orangtua ORDER BY nama_orangtua ASC");
                                            if ($res_all_ortu && $res_all_ortu->num_rows > 0):
                                                while ($o = $res_all_ortu->fetch_assoc()):
                                            ?>
                                                    <label class="flex items-center space-x-2 hover:bg-purple-100/60 p-0.5 rounded cursor-pointer">
                                                        <input type="checkbox" name="target_ortu_ids[]" value="<?= $o['id'] ?>" class="rounded text-purple-600 focus:ring-purple-500">
                                                        <span><?= htmlspecialchars($o['nama_orangtua']) ?> <span class="text-[10px] text-purple-700 font-semibold">(Walisantri)</span></span>
                                                    </label>
                                            <?php
                                                endwhile;
                                            else:
                                                echo '<p class="text-[10px] text-gray-400 italic">Belum ada data akun orang tua.</p>';
                                            endif;
                                            ?>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">* Notifikasi WhatsApp otomatis dikirimkan langsung ke peserta terundang saat rapat dipublikasikan.</p>
                                    </div>
                                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow-md transition-all duration-200">
                                        <i class="fas fa-paper-plane mr-1"></i> Publikasikan Rapat Sekolah (WA)
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- 2. FORM RAPAT MA'HAD -->
                            <?php if (in_array('kepala_mahad', $user_roles) || in_array('super_admin', $user_roles)): ?>
                            <div id="form-container-mahad" class="<?= (in_array('kepala_sekolah', $user_roles) || in_array('super_admin', $user_roles)) ? 'hidden' : 'block' ?>">
                                <h3 class="text-sm font-bold text-emerald-900 mb-4 pb-2 border-b border-emerald-100 flex items-center"><i class="fas fa-mosque mr-2 text-emerald-600"></i>Form Rapat Ma'had</h3>
                                <form action="admin-absensi-pegawai.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="kepala_mahad">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Agenda / Nama Rapat</label>
                                        <textarea name="agenda" required rows="3" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500" placeholder="Contoh: Rapat Keta'liman & Kebersihan Asrama Ma'had"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Sifat Rapat</label>
                                        <select name="jenis_rutin" id="select_jenis_rutin_mahad" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                                            <option value="pekanan">Pekanan</option>
                                            <option value="bulanan">Bulanan</option>
                                            <option value="insidental">Insidental</option>
                                        </select>
                                    </div>
                                    <div id="wrapper_hari_mahad" class="block">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Hari</label>
                                        <select name="hari_rutin" id="select_hari_mahad" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                                            <option value="Senin">Senin</option>
                                            <option value="Selasa">Selasa</option>
                                            <option value="Rabu">Rabu</option>
                                            <option value="Kamis">Kamis</option>
                                            <option value="Jumat">Jumat</option>
                                            <option value="Sabtu">Sabtu</option>
                                            <option value="Ahad">Ahad</option>
                                        </select>
                                    </div>
                                    <div id="wrapper_tanggal_mahad" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                                        <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_mahad" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                                        <input type="hidden" name="tanggal_rutin" id="input_tanggal_rutin_mahad" value="">
                                    </div>
                                    <div id="wrapper_jam_mahad" class="block">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Jam</label>
                                        <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500" value="20:00">
                                    </div>
                                    <div id="wrapper_penyesuaian_libur_mahad" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Penyesuaian Hari Libur (Opsional)</label>
                                        <input type="date" name="tgl_penyesuaian_libur" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
                                        <p class="text-[10px] text-gray-400 mt-1">* Isi jika jadwal rutin bertepatan dengan libur dan perlu disetting ulang ke tanggal lain.</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tujuan Undangan (Role / Grup Wajib)</label>
                                        <div class="space-y-1 bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700">
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="kepala_asrama" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                <span class="font-semibold text-gray-800">Kepala Asrama</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="musyrif" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                <span class="font-semibold text-gray-800">Musyrif</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="ustadz" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                <span class="font-semibold text-gray-800">Ustadz</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="orangtua" class="rounded text-purple-600 focus:ring-purple-500">
                                                <span class="font-semibold text-purple-800"><i class="fas fa-users mr-1"></i> Orangtua / Walisantri</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Pegawai Spesifik (Kepala Asrama, Musyrif, Ustadz)</label>
                                        <div class="max-h-28 overflow-y-auto border border-gray-200 rounded-lg p-2.5 bg-gray-50 space-y-1 text-xs text-gray-700 mb-3">
                                            <?php
                                            $res_mahad_ustadz = $conn->query("SELECT id, nama, role FROM akun_ustadz ORDER BY nama ASC");
                                            if ($res_mahad_ustadz && $res_mahad_ustadz->num_rows > 0):
                                                while ($u = $res_mahad_ustadz->fetch_assoc()):
                                                    $r_list = array_map('trim', explode(',', strtolower($u['role'] ?? '')));
                                                    $is_mahad_role = false;
                                                    foreach ($r_list as $r) {
                                                        if ($r === 'kepala_asrama' || $r === 'musyrif' || $r === 'ustadz') {
                                                            $is_mahad_role = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$is_mahad_role) continue;
                                            ?>
                                                    <label class="flex items-center space-x-2 hover:bg-emerald-50 p-0.5 rounded cursor-pointer">
                                                        <input type="checkbox" name="target_ids[]" value="<?= $u['id'] ?>" class="rounded text-emerald-600 focus:ring-emerald-500">
                                                        <span><?= htmlspecialchars($u['nama']) ?> <span class="text-[10px] text-emerald-700 font-semibold">(<?= htmlspecialchars($u['role'] ?? 'pegawai') ?>)</span></span>
                                                    </label>
                                            <?php
                                                endwhile;
                                            endif;
                                            ?>
                                        </div>

                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Atau Pilih Orangtua / Walisantri Spesifik</label>
                                        <div class="max-h-32 overflow-y-auto border border-purple-200 rounded-lg p-2.5 bg-purple-50/40 space-y-1 text-xs text-gray-700">
                                            <?php
                                            $res_all_ortu_m = $conn->query("SELECT id, nama_orangtua, no_whatsapp FROM akun_orangtua ORDER BY nama_orangtua ASC");
                                            if ($res_all_ortu_m && $res_all_ortu_m->num_rows > 0):
                                                while ($o = $res_all_ortu_m->fetch_assoc()):
                                            ?>
                                                    <label class="flex items-center space-x-2 hover:bg-purple-100/60 p-0.5 rounded cursor-pointer">
                                                        <input type="checkbox" name="target_ortu_ids[]" value="<?= $o['id'] ?>" class="rounded text-purple-600 focus:ring-purple-500">
                                                        <span><?= htmlspecialchars($o['nama_orangtua']) ?> <span class="text-[10px] text-purple-700 font-semibold">(Walisantri)</span></span>
                                                    </label>
                                            <?php
                                                endwhile;
                                            else:
                                                echo '<p class="text-[10px] text-gray-400 italic">Belum ada data akun orang tua.</p>';
                                            endif;
                                            ?>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">* Notifikasi WhatsApp otomatis dikirimkan langsung ke peserta terundang saat rapat dipublikasikan.</p>
                                    </div>
                                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow-md transition-all duration-200">
                                        <i class="fas fa-paper-plane mr-1"></i> Publikasikan Rapat Ma'had (WA)
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>

                            <!-- 3. FORM RAPAT YAYASAN -->
                            <?php if (in_array('super_admin', $user_roles) || in_array('ketua_yayasan', $user_roles)): ?>
                            <div id="form-container-yayasan" class="<?= (!in_array('kepala_sekolah', $user_roles) && !in_array('kepala_mahad', $user_roles)) ? 'block' : 'hidden' ?>">
                                <h3 class="text-sm font-bold text-amber-900 mb-4 pb-2 border-b border-amber-100 flex items-center"><i class="fas fa-landmark mr-2 text-amber-600"></i>Form Rapat Yayasan</h3>
                                <form action="admin-absensi-pegawai.php" method="POST" class="space-y-4">
                                    <input type="hidden" name="action" value="buat_rapat">
                                    <input type="hidden" name="pengundang" value="ketua_yayasan">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Agenda / Nama Rapat</label>
                                        <textarea name="agenda" required rows="3" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500" placeholder="Contoh: Rapat Pleno Koordinasi Lintas Unit & Pengurus Yayasan"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Sifat Rapat</label>
                                        <select name="jenis_rutin" id="select_jenis_rutin_yayasan" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                                            <option value="pekanan">Pekanan</option>
                                            <option value="bulanan">Bulanan</option>
                                            <option value="insidental">Insidental</option>
                                        </select>
                                    </div>
                                    <div id="wrapper_hari_yayasan" class="block">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Hari</label>
                                        <select name="hari_rutin" id="select_hari_yayasan" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                                            <option value="Senin">Senin</option>
                                            <option value="Selasa">Selasa</option>
                                            <option value="Rabu">Rabu</option>
                                            <option value="Kamis">Kamis</option>
                                            <option value="Jumat">Jumat</option>
                                            <option value="Sabtu">Sabtu</option>
                                            <option value="Ahad">Ahad</option>
                                        </select>
                                    </div>
                                    <div id="wrapper_tanggal_yayasan" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal</label>
                                        <input type="date" name="tanggal_lengkap" id="input_tanggal_lengkap_yayasan" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                                        <input type="hidden" name="tanggal_rutin" id="input_tanggal_rutin_yayasan" value="">
                                    </div>
                                    <div id="wrapper_jam_yayasan" class="block">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Jam</label>
                                        <input type="time" name="jam_rapat" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500" value="09:00">
                                    </div>
                                    <div id="wrapper_penyesuaian_libur_yayasan" class="hidden">
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Penyesuaian Hari Libur (Opsional)</label>
                                        <input type="date" name="tgl_penyesuaian_libur" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                                        <p class="text-[10px] text-gray-400 mt-1">* Isi jika jadwal rutin bertepatan dengan libur dan perlu disetting ulang ke tanggal lain.</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tujuan Undangan (Role / Grup Wajib)</label>
                                        <div class="space-y-1 bg-gray-50 border border-gray-200 rounded-lg p-2.5 text-xs text-gray-700">
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="semua_pegawai" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span class="font-semibold text-amber-900">Semua Pegawai & Asatidz</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="sekretaris_yayasan" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Sekretaris Yayasan</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="bendahara_yayasan" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Bendahara Yayasan</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="staff_ldu" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Staff LDU</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="kepala_sekolah" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Kepala Sekolah</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="admin_sekolah" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Admin Sekolah</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="tutor" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Tutor</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="kepala_mahad" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Kepala Ma'had</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="kepala_asrama" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Kepala Asrama</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="musyrif" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Musyrif</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="ustadz" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Ustadz</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="trainer" class="rounded text-amber-600 focus:ring-amber-500">
                                                <span>Trainer</span>
                                            </label>
                                            <label class="flex items-center space-x-2 cursor-pointer">
                                                <input type="checkbox" name="target_roles[]" value="orangtua" class="rounded text-purple-600 focus:ring-purple-500">
                                                <span class="font-semibold text-purple-800"><i class="fas fa-users mr-1"></i> Orangtua / Walisantri</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Pilih Pegawai Spesifik (Seluruh Pegawai)</label>
                                        <div class="max-h-28 overflow-y-auto border border-gray-200 rounded-lg p-2.5 bg-gray-50 space-y-1 text-xs text-gray-700 mb-3">
                                            <?php
                                            $res_all_y_ustadz = $conn->query("SELECT id, nama, role FROM akun_ustadz ORDER BY nama ASC");
                                            if ($res_all_y_ustadz && $res_all_y_ustadz->num_rows > 0):
                                                while ($u = $res_all_y_ustadz->fetch_assoc()):
                                            ?>
                                                    <label class="flex items-center space-x-2 hover:bg-amber-50 p-0.5 rounded cursor-pointer">
                                                        <input type="checkbox" name="target_ids[]" value="<?= $u['id'] ?>" class="rounded text-amber-600 focus:ring-amber-500">
                                                        <span><?= htmlspecialchars($u['nama']) ?> <span class="text-[10px] text-amber-700 font-semibold">(<?= htmlspecialchars($u['role'] ?? 'pegawai') ?>)</span></span>
                                                    </label>
                                            <?php
                                                endwhile;
                                            endif;
                                            ?>
                                        </div>

                                        <label class="block text-xs font-semibold text-gray-600 mb-1">Atau Pilih Orangtua / Walisantri Spesifik</label>
                                        <div class="max-h-32 overflow-y-auto border border-purple-200 rounded-lg p-2.5 bg-purple-50/40 space-y-1 text-xs text-gray-700">
                                            <?php
                                            $res_all_ortu_y = $conn->query("SELECT id, nama_orangtua, no_whatsapp FROM akun_orangtua ORDER BY nama_orangtua ASC");
                                            if ($res_all_ortu_y && $res_all_ortu_y->num_rows > 0):
                                                while ($o = $res_all_ortu_y->fetch_assoc()):
                                            ?>
                                                    <label class="flex items-center space-x-2 hover:bg-purple-100/60 p-0.5 rounded cursor-pointer">
                                                        <input type="checkbox" name="target_ortu_ids[]" value="<?= $o['id'] ?>" class="rounded text-purple-600 focus:ring-purple-500">
                                                        <span><?= htmlspecialchars($o['nama_orangtua']) ?> <span class="text-[10px] text-purple-700 font-semibold">(Walisantri)</span></span>
                                                    </label>
                                            <?php
                                                endwhile;
                                            else:
                                                echo '<p class="text-[10px] text-gray-400 italic">Belum ada data akun orang tua.</p>';
                                            endif;
                                            ?>
                                        </div>
                                        <p class="text-[10px] text-gray-400 mt-1">* Notifikasi WhatsApp otomatis dikirimkan langsung ke peserta terundang saat rapat dipublikasikan.</p>
                                    </div>
                                    <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow-md transition-all duration-200">
                                        <i class="fas fa-paper-plane mr-1"></i> Publikasikan Rapat Yayasan (WA)
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>

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
                                            <th class="px-3 py-2 text-left font-bold">Sifat Rapat</th>
                                            <th class="px-3 py-2 text-left font-bold">Target Peserta</th>
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
                                                
                                                $rutin_badge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Sekali Jalan</span>';
                                                if (($r['jenis_rutin'] ?? '') === 'pekanan') {
                                                    $rutin_badge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">Pekanan (' . htmlspecialchars($r['hari_rutin']) . ')</span>';
                                                } elseif (($r['jenis_rutin'] ?? '') === 'bulanan') {
                                                    $rutin_badge = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">Bulanan (Tgl ' . htmlspecialchars($r['tanggal_rutin']) . ')</span>';
                                                }

                                                if (!empty($r['tgl_penyesuaian_libur'])) {
                                                    $rutin_badge .= '<div class="text-[10px] text-amber-600 font-semibold mt-0.5"><i class="fas fa-calendar-day mr-1"></i>Libur -> ' . date('d M Y', strtotime($r['tgl_penyesuaian_libur'])) . '</div>';
                                                }
                                                
                                                $peserta_desc = 'Wajib Default (' . $lbl_role . ')';
                                                if (!empty($r['peserta_terundang'])) {
                                                    $pj = json_decode($r['peserta_terundang'], true);
                                                    $r_list = $pj['roles'] ?? [];
                                                    $i_list = $pj['ids'] ?? [];
                                                    $items = [];
                                                    if (in_array('semua_pegawai', $r_list)) $items[] = 'Semua Pegawai';
                                                    else {
                                                        if (in_array('sekretaris_yayasan', $r_list)) $items[] = 'Sekretaris Yayasan';
                                                        if (in_array('bendahara_yayasan', $r_list)) $items[] = 'Bendahara Yayasan';
                                                        if (in_array('staff_ldu', $r_list)) $items[] = 'Staff LDU';
                                                        if (in_array('admin_sekolah', $r_list)) $items[] = 'Admin Sekolah';
                                                        if (in_array('musyrif', $r_list)) $items[] = 'Musyrif';
                                                        if (in_array('ustadz_diknas', $r_list)) $items[] = 'Ustadz Diknas';
                                                        if (in_array('ustadz_diniyah', $r_list)) $items[] = 'Ustadz Diniyah';
                                                    }
                                                    if (!empty($i_list)) {
                                                        $items[] = count($i_list) . ' Pegawai Spesifik';
                                                    }
                                                    if (!empty($items)) $peserta_desc = implode(', ', $items);
                                                }
                                        ?>
                                                <tr>
                                                    <td class="px-3 py-2 font-semibold text-gray-800"><?= htmlspecialchars($r['agenda']) ?></td>
                                                    <td class="px-3 py-2 text-gray-500"><?= $lbl_role ?></td>
                                                    <td class="px-3 py-2"><?= $rutin_badge ?></td>
                                                    <td class="px-3 py-2 text-indigo-700 font-medium"><?= htmlspecialchars($peserta_desc) ?></td>
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
                                                <td colspan="6" class="px-3 py-4 text-center text-gray-400">Tidak ada rapat aktif saat ini.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- TABEL ARSIP & RIWAYAT RAPAT SELESAI -->
                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-box-archive text-amber-600"></i> Arsip & Riwayat Rapat Selesai
                                </h3>
                                <p class="text-[11px] text-gray-500">Daftar rapat yang telah diselesaikan beserta persentase kehadiran peserta.</p>
                            </div>
                            <span class="text-xs bg-amber-50 text-amber-800 font-semibold px-2.5 py-1 rounded-full border border-amber-200">
                                Arsip Permanen
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500">
                                        <th class="px-3 py-2 text-left font-bold">Agenda Rapat</th>
                                        <th class="px-3 py-2 text-left font-bold">Penyelenggara</th>
                                        <th class="px-3 py-2 text-left font-bold">Sifat Rapat</th>
                                        <th class="px-3 py-2 text-left font-bold">Waktu Mulai</th>
                                        <th class="px-3 py-2 text-center font-bold">Peserta Hadir</th>
                                        <th class="px-3 py-2 text-center font-bold">Rincian</th>
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

                                            $fr_rutin = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">Sekali Jalan</span>';
                                            if (($fr['jenis_rutin'] ?? '') === 'pekanan') {
                                                $fr_rutin = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700">Pekanan (' . htmlspecialchars($fr['hari_rutin']) . ')</span>';
                                            } elseif (($fr['jenis_rutin'] ?? '') === 'bulanan') {
                                                $fr_rutin = '<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700">Bulanan (Tgl ' . htmlspecialchars($fr['tanggal_rutin']) . ')</span>';
                                            }

                                            $q_count_hadir = $conn->query("SELECT COUNT(DISTINCT ustadz_id) as jml FROM absensi_pegawai WHERE jenis_absen = 'Rapat' AND rapat_id = $fr_id AND status_kehadiran IN ('Masuk', 'Pulang', 'Hadir')");
                                            $jml_hadir_rapat = $q_count_hadir ? (int)($q_count_hadir->fetch_assoc()['jml'] ?? 0) : 0;
                                    ?>
                                            <tr class="hover:bg-slate-50/50 transition">
                                                <td class="px-3 py-2 font-semibold text-gray-800"><?= htmlspecialchars($fr['agenda']) ?></td>
                                                <td class="px-3 py-2 text-gray-500"><?= $lbl_role ?></td>
                                                <td class="px-3 py-2"><?= $fr_rutin ?></td>
                                                <td class="px-3 py-2 text-gray-500"><?= date('d M Y H:i', strtotime($fr['waktu_mulai'])) ?> WIB</td>
                                                <td class="px-3 py-2 text-center">
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <i class="fas fa-users-check mr-1"></i><?= $jml_hadir_rapat ?> Hadir
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <button type="button" onclick="showModalPresensiRapat(<?= $fr_id ?>, '<?= htmlspecialchars(addslashes($fr['agenda'])) ?>')" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-2.5 py-1 rounded text-[10px] transition shadow-sm border border-indigo-200">
                                                        <i class="fas fa-list-check mr-1"></i> Lihat Presensi
                                                    </button>
                                                </td>
                                            </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                        <tr>
                                            <td colspan="6" class="px-3 py-4 text-center text-gray-400">Belum ada riwayat rapat yang diselesaikan.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

            <!-- KOTAK HASIL/STATUS UTAMA -->
            <div id="scan-result" class="max-w-4xl mx-auto mb-8 text-center">
                <!-- Status akan diisi secara dinamis -->
            </div>

            <!-- MODAL DETAIL RINCIAN PRESENSI RAPAT -->
            <div id="modal-presensi-rapat" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title-presensi" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div id="overlay-presensi-rapat" class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full p-6 relative">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold shadow-inner">
                                    <i class="fas fa-clipboard-user"></i>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-gray-900" id="modal-presensi-agenda">Detail Presensi Rapat</h3>
                                    <p class="text-xs text-gray-500">Daftar kehadiran Asatidz & Pegawai pada rapat ini.</p>
                                </div>
                            </div>
                            <button type="button" id="btn-close-modal-presensi" class="text-gray-400 hover:text-gray-600 text-xl font-bold p-1">
                                &times;
                            </button>
                        </div>

                        <div id="modal-presensi-content" class="max-h-96 overflow-y-auto">
                            <!-- Konten AJAX presensi -->
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2 text-indigo-500"></i>
                                <p class="text-xs">Memuat data presensi...</p>
                            </div>
                        </div>

                        <div class="mt-6 text-right border-t border-gray-100 pt-3">
                            <button type="button" id="btn-close-modal-presensi-2" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2 rounded-xl text-xs transition">
                                Tutup
                            </button>
                        </div>
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
            }
        };

        const MAX_DISTANCE_METERS = 75;

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

        const btnMengajar = document.getElementById('btn-absen-mengajar');
        if (btnMengajar) {
            btnMengajar.addEventListener('click', () => {
                doAbsensi('Mengajar');
            });
        }

        // Event listener refresh GPS
        document.getElementById('btn-refresh-gps').addEventListener('click', (e) => {
            e.preventDefault();
            updateGPSStatus();
        });

        // Function Switch Tab Form Rapat (Sekolah / Ma'had / Yayasan)
        window.switchFormTab = function(tabName) {
            const formSekolah = document.getElementById('form-container-sekolah');
            const formMahad = document.getElementById('form-container-mahad');
            const formYayasan = document.getElementById('form-container-yayasan');
            const btnSekolah = document.getElementById('tab-btn-sekolah');
            const btnMahad = document.getElementById('tab-btn-mahad');
            const btnYayasan = document.getElementById('tab-btn-yayasan');

            if (formSekolah) formSekolah.classList.add('hidden');
            if (formMahad) formMahad.classList.add('hidden');
            if (formYayasan) formYayasan.classList.add('hidden');

            if (btnSekolah) btnSekolah.className = 'py-2.5 px-4 font-bold text-xs rounded-t-lg bg-gray-100 text-gray-600 hover:bg-gray-200 shadow-sm transition-all flex items-center gap-2';
            if (btnMahad) btnMahad.className = 'py-2.5 px-4 font-bold text-xs rounded-t-lg bg-gray-100 text-gray-600 hover:bg-gray-200 shadow-sm transition-all flex items-center gap-2';
            if (btnYayasan) btnYayasan.className = 'py-2.5 px-4 font-bold text-xs rounded-t-lg bg-gray-100 text-gray-600 hover:bg-gray-200 shadow-sm transition-all flex items-center gap-2';

            if (tabName === 'sekolah') {
                if (formSekolah) formSekolah.classList.remove('hidden');
                if (btnSekolah) btnSekolah.className = 'py-2.5 px-4 font-bold text-xs rounded-t-lg bg-indigo-600 text-white shadow-sm transition-all flex items-center gap-2';
            } else if (tabName === 'mahad') {
                if (formMahad) formMahad.classList.remove('hidden');
                if (btnMahad) btnMahad.className = 'py-2.5 px-4 font-bold text-xs rounded-t-lg bg-emerald-600 text-white shadow-sm transition-all flex items-center gap-2';
            } else if (tabName === 'yayasan') {
                if (formYayasan) formYayasan.classList.remove('hidden');
                if (btnYayasan) btnYayasan.className = 'py-2.5 px-4 font-bold text-xs rounded-t-lg bg-amber-600 text-white shadow-sm transition-all flex items-center gap-2';
            }
        };

        // Toggle input sifat rapat (Sekolah)
        const selectJenisRutinSekolah = document.getElementById('select_jenis_rutin_sekolah');
        const inputTanggalSekolah = document.getElementById('input_tanggal_lengkap_sekolah');

        function updateSifatFormSekolah() {
            if (!selectJenisRutinSekolah) return;
            const val = selectJenisRutinSekolah.value;
            const wHari = document.getElementById('wrapper_hari_sekolah');
            const wTgl = document.getElementById('wrapper_tanggal_sekolah');
            const wLibur = document.getElementById('wrapper_penyesuaian_libur_sekolah');
            
            if (val === 'pekanan') {
                wHari?.classList.remove('hidden');
                wTgl?.classList.add('hidden');
                wLibur?.classList.remove('hidden');
                if (inputTanggalSekolah) inputTanggalSekolah.required = false;
            } else if (val === 'bulanan' || val === 'insidental') {
                wHari?.classList.remove('hidden');
                wTgl?.classList.remove('hidden');
                wLibur?.classList.remove('hidden');
                if (inputTanggalSekolah) inputTanggalSekolah.required = true;
            }
        }

        if (selectJenisRutinSekolah) {
            selectJenisRutinSekolah.addEventListener('change', updateSifatFormSekolah);
            updateSifatFormSekolah();
        }

        if (inputTanggalSekolah) {
            inputTanggalSekolah.addEventListener('change', function() {
                if (!this.value) return;
                const dt = new Date(this.value);
                if (!isNaN(dt.getTime())) {
                    const daysIndo = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const selectHari = document.getElementById('select_hari_sekolah');
                    const inputTglRutin = document.getElementById('input_tanggal_rutin_sekolah');
                    if (selectHari) selectHari.value = daysIndo[dt.getDay()];
                    if (inputTglRutin) inputTglRutin.value = dt.getDate();
                }
            });
        }

        // Toggle input sifat rapat (Ma'had)
        const selectJenisRutinMahad = document.getElementById('select_jenis_rutin_mahad');
        const inputTanggalMahad = document.getElementById('input_tanggal_lengkap_mahad');

        function updateSifatFormMahad() {
            if (!selectJenisRutinMahad) return;
            const val = selectJenisRutinMahad.value;
            const wHari = document.getElementById('wrapper_hari_mahad');
            const wTgl = document.getElementById('wrapper_tanggal_mahad');
            const wLibur = document.getElementById('wrapper_penyesuaian_libur_mahad');
            
            if (val === 'pekanan') {
                wHari?.classList.remove('hidden');
                wTgl?.classList.add('hidden');
                wLibur?.classList.remove('hidden');
                if (inputTanggalMahad) inputTanggalMahad.required = false;
            } else if (val === 'bulanan' || val === 'insidental') {
                wHari?.classList.remove('hidden');
                wTgl?.classList.remove('hidden');
                wLibur?.classList.remove('hidden');
                if (inputTanggalMahad) inputTanggalMahad.required = true;
            }
        }

        if (selectJenisRutinMahad) {
            selectJenisRutinMahad.addEventListener('change', updateSifatFormMahad);
            updateSifatFormMahad();
        }

        if (inputTanggalMahad) {
            inputTanggalMahad.addEventListener('change', function() {
                if (!this.value) return;
                const dt = new Date(this.value);
                if (!isNaN(dt.getTime())) {
                    const daysIndo = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const selectHari = document.getElementById('select_hari_mahad');
                    const inputTglRutin = document.getElementById('input_tanggal_rutin_mahad');
                    if (selectHari) selectHari.value = daysIndo[dt.getDay()];
                    if (inputTglRutin) inputTglRutin.value = dt.getDate();
                }
            });
        }

        // Toggle input sifat rapat (Yayasan)
        const selectJenisRutinYayasan = document.getElementById('select_jenis_rutin_yayasan');
        const inputTanggalYayasan = document.getElementById('input_tanggal_lengkap_yayasan');

        function updateSifatFormYayasan() {
            if (!selectJenisRutinYayasan) return;
            const val = selectJenisRutinYayasan.value;
            const wHari = document.getElementById('wrapper_hari_yayasan');
            const wTgl = document.getElementById('wrapper_tanggal_yayasan');
            const wLibur = document.getElementById('wrapper_penyesuaian_libur_yayasan');
            
            if (val === 'pekanan') {
                wHari?.classList.remove('hidden');
                wTgl?.classList.add('hidden');
                wLibur?.classList.remove('hidden');
                if (inputTanggalYayasan) inputTanggalYayasan.required = false;
            } else if (val === 'bulanan' || val === 'insidental') {
                wHari?.classList.remove('hidden');
                wTgl?.classList.remove('hidden');
                wLibur?.classList.remove('hidden');
                if (inputTanggalYayasan) inputTanggalYayasan.required = true;
            }
        }

        if (selectJenisRutinYayasan) {
            selectJenisRutinYayasan.addEventListener('change', updateSifatFormYayasan);
            updateSifatFormYayasan();
        }

        if (inputTanggalYayasan) {
            inputTanggalYayasan.addEventListener('change', function() {
                if (!this.value) return;
                const dt = new Date(this.value);
                if (!isNaN(dt.getTime())) {
                    const daysIndo = ['Ahad', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const selectHari = document.getElementById('select_hari_yayasan');
                    const inputTglRutin = document.getElementById('input_tanggal_rutin_yayasan');
                    if (selectHari) selectHari.value = daysIndo[dt.getDay()];
                    if (inputTglRutin) inputTglRutin.value = dt.getDate();
                }
            });
        }

        // Fungsi Tampilkan Modal Rincian Presensi Rapat Selesai
        function showModalPresensiRapat(rapatId, agendaName) {
            const modal = document.getElementById('modal-presensi-rapat');
            const titleEl = document.getElementById('modal-presensi-agenda');
            const contentEl = document.getElementById('modal-presensi-content');

            titleEl.innerText = `Rincian Presensi: ${agendaName}`;
            contentEl.innerHTML = `<div class="text-center py-8 text-gray-400"><i class="fas fa-spinner fa-spin text-2xl mb-2 text-indigo-500"></i><p class="text-xs">Memuat data presensi...</p></div>`;
            modal.classList.remove('hidden');

            fetch(`admin-absensi-pegawai.php?ajax_action=get_rapat_presensi&rapat_id=${rapatId}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.presensi.length === 0) {
                        contentEl.innerHTML = `<div class="text-center py-8 text-gray-400 italic text-xs">Belum ada catatan presensi yang masuk untuk rapat ini.</div>`;
                    } else {
                        let html = `
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-100 text-gray-500">
                                    <th class="p-2.5 text-center">No</th>
                                    <th class="p-2.5">Nama Pegawai</th>
                                    <th class="p-2.5 text-center">Waktu Absen</th>
                                    <th class="p-2.5 text-center">Status Kehadiran</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                        `;
                        data.presensi.forEach((p, idx) => {
                            let badgeClass = 'bg-emerald-100 text-emerald-800';
                            let statusText = p.status_kehadiran;
                            if (p.keterangan && p.keterangan.includes('Terlambat')) {
                                badgeClass = 'bg-amber-100 text-amber-800';
                                statusText = p.keterangan;
                            } else if (p.status_kehadiran.includes('Ditolak')) {
                                badgeClass = 'bg-rose-100 text-rose-800';
                            }
                            
                            html += `
                            <tr class="hover:bg-gray-50/50">
                                <td class="p-2.5 text-center font-semibold text-gray-400">${idx + 1}</td>
                                <td class="p-2.5 font-bold text-gray-800">${p.nama} <span class="block text-[10px] text-gray-400 font-normal">${p.role || ''}</span></td>
                                <td class="p-2.5 text-center text-gray-500 font-mono text-[11px]">${p.waktu_absen}</td>
                                <td class="p-2.5 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${badgeClass}">
                                        ${statusText}
                                    </span>
                                </td>
                            </tr>
                            `;
                        });
                        html += `</tbody></table>`;
                        contentEl.innerHTML = html;
                    }
                } else {
                    contentEl.innerHTML = `<div class="text-center py-8 text-rose-500 italic text-xs">${data.message || 'Gagal memuat data presensi.'}</div>`;
                }
            })
            .catch(err => {
                contentEl.innerHTML = `<div class="text-center py-8 text-rose-500 italic text-xs">Terjadi kesalahan koneksi saat memuat data presensi.</div>`;
            });
        }

        document.getElementById('btn-close-modal-presensi')?.addEventListener('click', () => {
            document.getElementById('modal-presensi-rapat').classList.add('hidden');
        });
        document.getElementById('btn-close-modal-presensi-2')?.addEventListener('click', () => {
            document.getElementById('modal-presensi-rapat').classList.add('hidden');
        });
        document.getElementById('overlay-presensi-rapat')?.addEventListener('click', () => {
            document.getElementById('modal-presensi-rapat').classList.add('hidden');
        });

        // Jalankan deteksi GPS saat halaman dimuat
        updateGPSStatus();
    </script>
</body>
</html>