<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Get logged-in user details
$user_id = $_SESSION['ustadz_id'] ?? 1;

$res_user = $conn->query("SELECT role, nama FROM akun_ustadz WHERE id = $user_id");
$user_data = $res_user ? $res_user->fetch_assoc() : null;
$user_roles = isset($user_data['role']) ? explode(',', $user_data['role']) : [];
$user_roles_trimmed = array_map('trim', $user_roles);
$is_super_admin = ($user_id === 9999);

$can_see_pegawai = true;
$can_see_musyrif = in_array('musyrif', $user_roles_trimmed) || in_array('musyrifah', $user_roles_trimmed) || $is_super_admin;
$can_see_kepsek = in_array('kepala_sekolah', $user_roles_trimmed) || $is_super_admin;

// Default view selection
$default_view = 'pegawai';
if (in_array('kepala_sekolah', $user_roles_trimmed)) {
    $default_view = 'kepsek';
} elseif (in_array('musyrif', $user_roles_trimmed) || in_array('musyrifah', $user_roles_trimmed)) {
    $default_view = 'musyrif';
}

$view = $_GET['view'] ?? $default_view;

// Enforce view authorization
if ($view === 'kepsek' && !$can_see_kepsek) {
    $view = 'pegawai';
} elseif ($view === 'musyrif' && !$can_see_musyrif) {
    $view = 'pegawai';
}

$active_menu = 'kpi_ustadz';

$months = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$selected_month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selected_year = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$selected_period = sprintf('%04d-%02d', $selected_year, $selected_month);

$pesan_sukses = '';
$pesan_error = '';

// --- POST SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_laporan_kepsek']) && $view === 'kepsek') {
        $target_ustadz_id = (int)$_POST['ustadz_id'];
        $periode = $conn->real_escape_string($_POST['periode']);
        $kbm_terjadwal = max(0, (int)$_POST['total_kbm_terjadwal']);
        $kbm_terlaksana = max(0, (int)$_POST['total_kbm_terlaksana']);
        $rpp_total = max(0, (int)$_POST['rpp_diknas_total']);
        $rpp_dikontrol = max(0, (int)$_POST['rpp_diknas_dikontrol']);

        if (!$is_super_admin && $target_ustadz_id !== $user_id) {
            $pesan_error = "Akses ditolak. Anda hanya dapat menyimpan laporan Anda sendiri.";
        } else {
            $sql = "INSERT INTO kpi_kepala_sekolah (ustadz_id, periode, total_kbm_terjadwal, total_kbm_terlaksana, rpp_diknas_total, rpp_diknas_dikontrol) 
                    VALUES ($target_ustadz_id, '$periode', $kbm_terjadwal, $kbm_terlaksana, $rpp_total, $rpp_dikontrol)
                    ON DUPLICATE KEY UPDATE 
                    total_kbm_terjadwal = $kbm_terjadwal,
                    total_kbm_terlaksana = $kbm_terlaksana,
                    rpp_diknas_total = $rpp_total,
                    rpp_diknas_dikontrol = $rpp_dikontrol";
            
            if ($conn->query($sql)) {
                $pesan_sukses = "Laporan kinerja bulanan berhasil disimpan!";
            } else {
                $pesan_error = "Gagal menyimpan laporan: " . $conn->error;
            }
        }
    }
}

// --- KPI CALCULATIONS ---
if ($view === 'kepsek') {
    // --- KEPALA SEKOLAH KPI LOGIC ---
    $report_data = null;
    $res_rep = $conn->query("SELECT * FROM kpi_kepala_sekolah WHERE ustadz_id = $user_id AND periode = '$selected_period'");
    if ($res_rep && $res_rep->num_rows > 0) {
        $report_data = $res_rep->fetch_assoc();
    }

    $kbm_terjadwal = $report_data['total_kbm_terjadwal'] ?? 0;
    $kbm_terlaksana = $report_data['total_kbm_terlaksana'] ?? 0;
    $rpp_total = $report_data['rpp_diknas_total'] ?? 0;
    $rpp_dikontrol = $report_data['rpp_diknas_dikontrol'] ?? 0;

    // 1. Mengontrol KBM
    $score_kbm = 0;
    if ($kbm_terjadwal > 0) {
        $persen_kbm = ($kbm_terlaksana / $kbm_terjadwal) * 100;
        $score_kbm = $persen_kbm >= 90 ? 100 : ($persen_kbm / 90) * 100;
    } else {
        $persen_kbm = 0;
        $score_kbm = 100;
    }

    // 2. Supervisi KBM (minimal 2 kali sebulan)
    $res_sup = $conn->query("SELECT COUNT(*) as total FROM supervisi_mengajar WHERE supervisor_id = $user_id AND MONTH(tanggal_supervisi) = $selected_month AND YEAR(tanggal_supervisi) = $selected_year");
    $total_supervisi = $res_sup ? (int)$res_sup->fetch_assoc()['total'] : 0;
    $score_supervisi = min(100, ($total_supervisi / 2) * 100);

    // 3. RPP Diknas
    $score_rpp = $rpp_total > 0 ? min(100, ($rpp_dikontrol / $rpp_total) * 100) : 100;

    // 4. Rapat Koordinasi (target 2 per bulan)
    $res_rapat = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as total FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen = 'Rapat' AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year AND status_kehadiran = 'Masuk'");
    $total_rapat_hadir = $res_rapat ? (int)$res_rapat->fetch_assoc()['total'] : 0;
    $score_rapat = min(100, ($total_rapat_hadir / 2) * 100);

    // 5. Capaian Nilai Santri > KKM
    $is_exam_month = in_array($selected_month, [3, 6, 10, 12]);
    $score_nilai = 100;
    $avg_nilai = 0;
    if ($is_exam_month) {
        $res_nilai = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE MONTH(created_at) = $selected_month AND YEAR(created_at) = $selected_year");
        $avg_nilai = $res_nilai ? (float)($res_nilai->fetch_assoc()['rata_rata'] ?? 0) : 0;
        if ($avg_nilai <= 0) {
            $res_nilai_fb = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai");
            $avg_nilai = $res_nilai_fb ? (float)($res_nilai_fb->fetch_assoc()['rata_rata'] ?? 0) : 0;
        }
        $score_nilai = $avg_nilai >= 75 ? 100 : ($avg_nilai > 0 ? ($avg_nilai / 75) * 100 : 0);
    }

    $total_skor_kpi = ($score_kbm + $score_supervisi + $score_rpp + $score_rapat + $score_nilai) / 5;

    if ($total_skor_kpi >= 90) {
        $predikat = "Mumtaz (Grade A)";
        $color_predikat = "bg-emerald-100 text-emerald-800 border-emerald-250";
        $pesan_evaluasi = "Alhamdulillah, jazakumullah khairan atas kepemimpinan Antum! Manajemen KBM, supervisi asatidz, dan rapat koordinasi berjalan sangat baik.";
        $ikon_evaluasi = "fa-star text-amber-400";
    } elseif ($total_skor_kpi >= 80) {
        $predikat = "Jayid (Grade B)";
        $color_predikat = "bg-blue-100 text-blue-800 border-blue-250";
        $pesan_evaluasi = "Kepemimpinan Antum berjalan baik. Mari kita optimalkan lagi pelaksanaan supervisi dan koordinasi adiminstrasi pengajaran.";
        $ikon_evaluasi = "fa-thumbs-up text-blue-500";
    } else {
        $predikat = "Aslha (Grade C)";
        $color_predikat = "bg-rose-100 text-rose-800 border-rose-250";
        $pesan_evaluasi = "Kinerja manajemen sekolah perlu ditingkatkan secara menyeluruh, terutama kontrol KBM harian dan pemenuhan target supervisi mengajar.";
        $ikon_evaluasi = "fa-exclamation-triangle text-rose-500";
    }

} elseif ($view === 'musyrif') {
    // --- MUSYRIF KPI LOGIC ---
    $santri_ids = [];
    $res_sb = $conn->query("
        SELECT DISTINCT s.id 
        FROM buku_induk_santri s 
        JOIN halaqoh_anggota a ON s.id = a.santri_id 
        JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE g.musyrif_id = $user_id AND s.status_santri = 'Aktif'
    ");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_ids[] = (int)$r['id'];
    }
    $total_santri_binaan = count($santri_ids);
    $santri_list_str = !empty($santri_ids) ? implode(',', $santri_ids) : '0';

    $skor_validasi_ibadah = 100;
    $skor_kontak_walisantri = 100;
    $skor_belajar_mandiri = 100;
    $skor_kesehatan = 100;
    $skor_setoran_hafalan = 100;
    $skor_mutabaah = 100;
    $skor_absensi_kerja = 100;
    $skor_absensi_rapat = 100;
    $details = [];

    if ($total_santri_binaan > 0) {
        // 1. Validasi Ibadah
        $res_ib = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status_validasi IN ('Disetujui', 'Ditolak') THEN 1 ELSE 0 END) as divalidasi FROM ibadah_harian_santri WHERE santri_id IN ($santri_list_str) AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
        $row_ib = $res_ib ? $res_ib->fetch_assoc() : ['total' => 0, 'divalidasi' => 0];
        $total_ib = (int)($row_ib['total'] ?? 0);
        $dival_ib = (int)($row_ib['divalidasi'] ?? 0);
        $kepatuhan_klik = $total_ib > 0 ? ($dival_ib / $total_ib) * 100 : 100;

        $res_bim = $conn->query("SELECT COUNT(*) as total_perlu, SUM(CASE WHEN catatan_musyrif IS NOT NULL AND TRIM(catatan_musyrif) != '' THEN 1 ELSE 0 END) as dibimbing FROM ibadah_harian_santri WHERE santri_id IN ($santri_list_str) AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year AND is_haid = 0 AND (sholat_subuh = 'Munfarid' OR sholat_dhuhur = 'Munfarid' OR sholat_ashar = 'Munfarid' OR sholat_maghrib = 'Munfarid' OR sholat_isya = 'Munfarid')");
        $row_bim = $res_bim ? $res_bim->fetch_assoc() : ['total_perlu' => 0, 'dibimbing' => 0];
        $total_perlu_bim = (int)($row_bim['total_perlu'] ?? 0);
        $total_dibimbing = (int)($row_bim['dibimbing'] ?? 0);
        $kepatuhan_bimbingan = $total_perlu_bim > 0 ? ($total_dibimbing / $total_perlu_bim) * 100 : 100;

        $skor_validasi_ibadah = (0.8 * $kepatuhan_klik) + (0.2 * $kepatuhan_bimbingan);
        $details['validasi_ibadah'] = "$dival_ib dari $total_ib divalidasi, bimbingan: $total_dibimbing dari $total_perlu_bim diisi";

        // 2. Kontak Walisantri
        $res_kon = $conn->query("SELECT COUNT(DISTINCT santri_id) as total_kontak FROM jurnal_kontak_orangtua WHERE ustadz_id = $user_id AND santri_id IN ($santri_list_str) AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
        $kontak_cnt = $res_kon ? (int)($res_kon->fetch_assoc()['total_kontak'] ?? 0) : 0;
        $skor_kontak_walisantri = min(100, ($kontak_cnt / $total_santri_binaan) * 100);
        $details['kontak_walisantri'] = "$kontak_cnt dari $total_santri_binaan walisantri dihubungi";

        // 4. Kesehatan
        $res_kes = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status_kesehatan = 'Sehat / Sembuh' OR status_izin_sekolah = 'Selesai / Sembuh' THEN 1 ELSE 0 END) as sembuh FROM jurnal_kesehatan_santri WHERE santri_id IN ($santri_list_str) AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
        $row_kes = $res_kes ? $res_kes->fetch_assoc() : ['total' => 0, 'sembuh' => 0];
        $total_kes = (int)($row_kes['total'] ?? 0);
        $sembuh_kes = (int)($row_kes['sembuh'] ?? 0);
        $skor_kesehatan = $total_kes > 0 ? ($sembuh_kes / $total_kes) * 100 : 100;
        $details['kesehatan'] = "$sembuh_kes dari $total_kes kasus diselesaikan";

        // 5. Setoran Hafalan
        $res_haf = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan WHERE santri_id IN ($santri_list_str) AND MONTH(created_at) = $selected_month AND YEAR(created_at) = $selected_year");
        $total_haf = $res_haf ? (int)($res_haf->fetch_assoc()['total'] ?? 0) : 0;
        $target_haf = $total_santri_binaan * 4;
        $skor_setoran_hafalan = $target_haf > 0 ? min(100, ($total_haf / $target_haf) * 100) : 100;
        $details['setoran_hafalan'] = "$total_haf kali setoran (Target: $target_haf)";

        // 6. Mutabaah
        $res_mut = $conn->query("SELECT COUNT(*) as total FROM buku_mutabaah WHERE musyrif_id = $user_id AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
        $total_mut = $res_mut ? (int)($res_mut->fetch_assoc()['total'] ?? 0) : 0;
        $target_mut = $total_santri_binaan * 2;
        $skor_mutabaah = $target_mut > 0 ? min(100, ($total_mut / $target_mut) * 100) : 100;
        $details['mutabaah'] = "$total_mut laporan mental (Target: $target_mut)";
    } else {
        $details['validasi_ibadah'] = $details['kontak_walisantri'] = $details['kesehatan'] = $details['setoran_hafalan'] = $details['mutabaah'] = "Tidak ada santri binaan";
    }

    // 3. Belajar Mandiri
    $res_bel = $conn->query("SELECT COUNT(*) as total FROM jurnal_belajar_mandiri WHERE ustadz_id = $user_id AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
    $total_bel = $res_bel ? (int)($res_bel->fetch_assoc()['total'] ?? 0) : 0;
    $skor_belajar_mandiri = min(100, ($total_bel / 20) * 100);
    $details['belajar_mandiri'] = "$total_bel kali pengisian jurnal";

    // 7. Absensi Kerja
    $res_abs = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as total_absen FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen IN ('Pegawai', 'Harian') AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year AND status_kehadiran = 'Masuk'");
    $total_absen = $res_abs ? (int)($res_abs->fetch_assoc()['total_absen'] ?? 0) : 0;
    $skor_absensi_kerja = min(100, ($total_absen / 26) * 100);
    $details['absensi_kerja'] = "$total_absen hari hadir kerja (Target: 26)";

    // 8. Absensi Rapat
    $res_rpt_hadir = $conn->query("SELECT COUNT(DISTINCT rapat_id) as total_hadir FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen = 'Rapat' AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year AND status_kehadiran = 'Masuk'");
    $hadir_rapat = $res_rpt_hadir ? (int)$res_rpt_hadir->fetch_assoc()['total_hadir'] : 0;
    $res_tot_rapat = $conn->query("SELECT COUNT(*) as total FROM jadwal_rapat WHERE MONTH(waktu_mulai) = $selected_month AND YEAR(waktu_mulai) = $selected_year");
    $total_rapat_bln = $res_tot_rapat ? (int)$res_tot_rapat->fetch_assoc()['total'] : 0;
    $skor_absensi_rapat = $total_rapat_bln > 0 ? min(100, ($hadir_rapat / $total_rapat_bln) * 100) : 100;
    $details['absensi_rapat'] = "$hadir_rapat dari $total_rapat_bln rapat dihadiri";

    $total_skor_kpi = ($skor_validasi_ibadah * 0.15) + 
                 ($skor_kontak_walisantri * 0.15) + 
                 ($skor_belajar_mandiri * 0.10) + 
                 ($skor_kesehatan * 0.10) + 
                 ($skor_setoran_hafalan * 0.15) + 
                 ($skor_mutabaah * 0.15) + 
                 ($skor_absensi_kerja * 0.10) + 
                 ($skor_absensi_rapat * 0.10);

    if ($total_skor_kpi >= 90) {
        $predikat = "Mumtaz (Grade A)";
        $pesan_evaluasi = "Alhamdulillah, dedikasi Antum mengawal santri binaan sangat luar biasa. Pertahankan kedisiplinan pengawalan ibadah dan adab.";
        $ikon_evaluasi = "fa-star text-amber-400";
    } elseif ($total_skor_kpi >= 80) {
        $predikat = "Jayid (Grade B)";
        $pesan_evaluasi = "Performa kepengasuhan Antum sudah baik. Mari kita tingkatkan lagi intensitas kontak walisantri dan pelaporan kesehatan santri.";
        $ikon_evaluasi = "fa-thumbs-up text-blue-500";
    } else {
        $predikat = "Aslha (Grade C)";
        $pesan_evaluasi = "Fokus kepengasuhan perlu ditingkatkan, mohon pastikan validasi ibadah harian santri binaan dan mutabaah diisi tepat waktu.";
        $ikon_evaluasi = "fa-exclamation-triangle text-rose-500";
    }

} else {
    // --- STANDARD EMPLOYEE / USTADZ KPI LOGIC ---
    $res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
    $data_gaji = $res_gaji ? $res_gaji->fetch_assoc() : null;
    $tarif_grade_c = $data_gaji['gaji_grade_c'] ?? 20000;
    $tarif_grade_b = $data_gaji['gaji_grade_b'] ?? 22500;
    $tarif_grade_a = $data_gaji['gaji_grade_a'] ?? 25000;

    $is_teacher = in_array('ustadz', $user_roles_trimmed) || in_array('guru', $user_roles_trimmed) || in_array('ustadzah', $user_roles_trimmed);
    $eligible_roles_pegawai = ['super_admin', 'kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'musyrif'];
    $is_daily_worker = !empty(array_intersect($eligible_roles_pegawai, $user_roles_trimmed));

    $res_jurnal_kpi = $conn->query("SELECT 
        COUNT(*) as total_jurnal, 
        SUM(CASE WHEN DATE(created_at) = tanggal THEN 1 ELSE 0 END) as tepat_waktu 
        FROM jurnal_mengajar 
        WHERE ustadz_id = $user_id AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
    $data_jurnal_kpi = $res_jurnal_kpi ? $res_jurnal_kpi->fetch_assoc() : ['total_jurnal' => 0, 'tepat_waktu' => 0];
    $jumlah_pertemuan = (int)($data_jurnal_kpi['total_jurnal'] ?? 0);
    $tepat_waktu = (int)($data_jurnal_kpi['tepat_waktu'] ?? 0);

    $skor_jurnal = ($is_teacher && $jumlah_pertemuan > 0) ? ($tepat_waktu / $jumlah_pertemuan) * 100 : 100;

    $res_hadir = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen IN ('Pegawai', 'Harian') AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year AND status_kehadiran = 'Masuk'");
    $jml_hadir = $res_hadir ? (int)($res_hadir->fetch_assoc()['jml'] ?? 0) : 0;

    if ($is_daily_worker) {
        $skor_kehadiran = $jml_hadir > 0 ? min(100, ($jml_hadir / 20) * 100) : 0;
    } else {
        $res_hadir_mengajar = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen = 'Mengajar' AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year AND status_kehadiran = 'Masuk'");
        $jml_hadir_mengajar = $res_hadir_mengajar ? (int)($res_hadir_mengajar->fetch_assoc()['jml'] ?? 0) : 0;
        
        $res_total_teaching_days = $conn->query("SELECT COUNT(DISTINCT tanggal) as total_days FROM jurnal_mengajar WHERE ustadz_id = $user_id AND MONTH(tanggal) = $selected_month AND YEAR(tanggal) = $selected_year");
        $total_teaching_days = $res_total_teaching_days ? (int)($res_total_teaching_days->fetch_assoc()['total_days'] ?? 0) : 0;
        
        $skor_kehadiran = $total_teaching_days > 0 ? min(100, ($jml_hadir_mengajar / $total_teaching_days) * 100) : 100;
    }

    $res_rapat_attended = $conn->query("SELECT COUNT(DISTINCT rapat_id) as jml FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen = 'Rapat' AND status_kehadiran IN ('Masuk', 'Pulang', 'Hadir') AND MONTH(waktu_absen) = $selected_month AND YEAR(waktu_absen) = $selected_year");
    $jml_rapat = $res_rapat_attended ? (int)($res_rapat_attended->fetch_assoc()['jml'] ?? 0) : 0;

    $res_all_rapat_month = $conn->query("SELECT * FROM jadwal_rapat WHERE MONTH(waktu_mulai) = $selected_month AND YEAR(waktu_mulai) = $selected_year");
    $total_rapat_invited = 0;
    if ($res_all_rapat_month && $res_all_rapat_month->num_rows > 0) {
        while ($r = $res_all_rapat_month->fetch_assoc()) {
            $p_json = $r['peserta_terundang'] ?? null;
            $is_inv = false;
            if (in_array('super_admin', $user_roles_trimmed)) {
                $is_inv = true;
            } elseif (!empty($p_json)) {
                $tg = json_decode($p_json, true);
                $t_r = $tg['roles'] ?? [];
                $t_i = array_map('intval', $tg['ids'] ?? []);
                if (in_array((int)$user_id, $t_i) || in_array('semua_pegawai', $t_r)) {
                    $is_inv = true;
                } else {
                    foreach ($t_r as $tr) {
                        if ($tr === 'musyrif' && ($is_daily_worker || in_array('musyrif', $user_roles_trimmed))) { $is_inv = true; break; }
                        if ($tr === 'admin_sekolah' && in_array('admin_sekolah', $user_roles_trimmed)) { $is_inv = true; break; }
                        if ($tr === 'ustadz_diknas' && $is_teacher) { $is_inv = true; break; }
                        if ($tr === 'ustadz_diniyah' && $is_teacher) { $is_inv = true; break; }
                    }
                }
            } else {
                $is_inv = true;
            }
            if ($is_inv) $total_rapat_invited++;
        }
    }

    $skor_kehadiran_rapat = $total_rapat_invited > 0 ? min(100, round(($jml_rapat / $total_rapat_invited) * 100)) : 100;
    $skor_administrasi = (($skor_jurnal * 0.4) + ($skor_kehadiran * 0.4) + ($skor_kehadiran_rapat * 0.2));

    $res_ai = $conn->query("SELECT COUNT(*) as pemakaian FROM log_aktivitas_ai WHERE user_id = $user_id AND MONTH(created_at) = $selected_month AND YEAR(created_at) = $selected_year");
    $jumlah_pakai_ai = $res_ai ? (int)($res_ai->fetch_assoc()['pemakaian'] ?? 0) : 0;
    $skor_penggunaan_ai = $jumlah_pakai_ai >= 5 ? 100 : ($jumlah_pakai_ai > 0 ? 85 : 70);

    if ($is_teacher) {
        $res_sup = $conn->query("SELECT skor FROM supervisi_mengajar WHERE user_id = $user_id ORDER BY tanggal_supervisi DESC LIMIT 1");
        $skor_supervisi = $res_sup && $res_sup->num_rows > 0 ? (int)($res_sup->fetch_assoc()['skor']) : 85;
    } else {
        $skor_supervisi = 100;
    }
    $skor_kualitas_pengajaran = (($skor_penggunaan_ai * 0.4) + ($skor_supervisi * 0.6));

    if ($is_teacher) {
        $res_nilai = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE ustadz_id = $user_id");
        $rata_rata_db = $res_nilai ? (float)($res_nilai->fetch_assoc()['rata_rata'] ?? 0) : 0;
        $skor_rata_nilai = $rata_rata_db > 0 ? $rata_rata_db : 80;
        
        $res_uts = $conn->query("SELECT AVG(nilai) as rata_uts FROM leger_nilai WHERE ustadz_id = $user_id AND jenis_ujian = 'Ujian Tengah Semester (UTS)'");
        $rata_uts = $res_uts ? (float)($res_uts->fetch_assoc()['rata_uts'] ?? 0) : 0;
        $res_uas = $conn->query("SELECT AVG(nilai) as rata_uas FROM leger_nilai WHERE ustadz_id = $user_id AND jenis_ujian = 'Ujian Akhir Semester (UAS)'");
        $rata_uas = $res_uas ? (float)($res_uas->fetch_assoc()['rata_uas'] ?? 0) : 0;
        $skor_pertumbuhan = ($rata_uts > 0 && $rata_uas > 0) ? (($rata_uas >= $rata_uts) ? 100 : 75) : 85;
    } else {
        $skor_rata_nilai = 100;
        $skor_pertumbuhan = 100;
    }
    $skor_capaian_santri = (($skor_rata_nilai * 0.6) + ($skor_pertumbuhan * 0.4));

    $skor_kontribusi_silabus = $jumlah_pakai_ai > 0 ? 100 : 70;
    $skor_pengembangan_diri = $skor_kontribusi_silabus;

    $total_skor_kpi = ($skor_administrasi * 0.20) + ($skor_kualitas_pengajaran * 0.40) + ($skor_capaian_santri * 0.30) + ($skor_pengembangan_diri * 0.10);

    $gaji_per_pertemuan = 0;
    if ($total_skor_kpi >= 90) {
        $gaji_per_pertemuan = $tarif_grade_a;
        $predikat = "Mumtaz (Grade A)";
        $pesan_evaluasi = "Alhamdulillah, jazakumullah khairan atas dedikasi Antum! Performa bulan ini sangat luar biasa. Pertahankan kedisiplinan administrasi dan inovasi mengajar Antum.";
        $ikon_evaluasi = "fa-star text-amber-400";
    } elseif ($total_skor_kpi >= 80) {
        $gaji_per_pertemuan = $tarif_grade_b;
        $predikat = "Jayid (Grade B)";
        $pesan_evaluasi = "Performa Antum sudah baik, namun masih ada ruang untuk ditingkatkan. Mari fokus pada perbaikan kualitas pengajaran dan pendampingan santri di bulan depan.";
        $ikon_evaluasi = "fa-thumbs-up text-blue-500";
    } else {
        $gaji_per_pertemuan = $tarif_grade_c;
        $predikat = "Aslha (Grade C)";
        $pesan_evaluasi = "Performa Antum bulan ini berada di bawah target yang diharapkan. Kami mohon kerjasamanya untuk lebih disiplin dalam mengisi jurnal dan mengawal target hafalan santri.";
        $ikon_evaluasi = "fa-exclamation-triangle text-rose-500";
    }
    $gaji_total = $gaji_per_pertemuan * $jumlah_pertemuan;
}

// Common queries like presence history or SP configuration
$res_riwayat = $conn->query("SELECT waktu_absen, jenis_absen, status_kehadiran, keterangan FROM absensi_pegawai WHERE ustadz_id = $user_id ORDER BY waktu_absen DESC LIMIT 30");
$riwayat_kehadiran = [];
if ($res_riwayat) {
    while ($row = $res_riwayat->fetch_assoc()) $riwayat_kehadiran[] = $row;
}

$cur_m = $selected_month;
$cur_y = $selected_year;
$sem_kpi = ($cur_m >= 7) ? "$cur_y/" . ($cur_y+1) . "-Ganjil" : ($cur_y-1) . "/$cur_y-Genap";
$res_sp_kpi = $conn->query("SELECT * FROM surat_peringatan_pegawai WHERE ustadz_id = $user_id AND semester = '$sem_kpi' ORDER BY id DESC LIMIT 1");
$sp_kpi_latest = ($res_sp_kpi && $res_sp_kpi->num_rows > 0) ? $res_sp_kpi->fetch_assoc() : null;

// Build tab mappings
$available_views = [];
if ($can_see_pegawai) $available_views['pegawai'] = ['title' => 'KPI Pegawai / Ustadz', 'icon' => 'fa-chalkboard-teacher'];
if ($can_see_musyrif) $available_views['musyrif'] = ['title' => 'KPI Musyrif Asrama', 'icon' => 'fa-home-user'];
if ($can_see_kepsek) $available_views['kepsek'] = ['title' => 'KPI Kepala Sekolah', 'icon' => 'fa-user-tie'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Pegawai | Ruang Asatidz</title>
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
            <!-- HEADER KPI -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-chart-line text-cyan-600"></i>
                        <span>Capaian Kinerja Pegawai (KPI)</span>
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">Hasil rekapitulasi penilaian kinerja berdasarkan kontribusi kehadiran, kedisiplinan administrasi, dan target peran Anda.</p>
                </div>
                <!-- Periode & Filter Dropdown -->
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
                    <select name="bulan" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-cyan-500">
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $selected_month == $num ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="tahun" onchange="this.form.submit()" class="px-3 py-1.5 bg-white border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-cyan-500">
                        <?php
                        $curr_yr = (int)date('Y');
                        for ($y = $curr_yr - 2; $y <= $curr_yr + 1; $y++):
                        ?>
                            <option value="<?= $y ?>" <?= $selected_year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </form>
            </div>

            <!-- TAB PENGALIH VIEW PERAN JIKA PENGGUNA MEMILIKI LEBIH DARI 1 PERAN -->
            <?php if (count($available_views) > 1): ?>
                <div class="mb-6 flex gap-4 border-b border-gray-200">
                    <?php foreach ($available_views as $v_key => $v_data): 
                        $is_act = ($view === $v_key);
                        $btn_cls = $is_act 
                            ? 'px-5 py-2.5 font-bold text-sm text-cyan-600 border-b-2 border-cyan-600 transition-all flex items-center gap-1.5' 
                            : 'px-5 py-2.5 font-bold text-sm text-gray-500 hover:text-cyan-600 border-b-2 border-transparent transition-all flex items-center gap-1.5';
                    ?>
                        <a href="admin-pegawai-kpi.php?view=<?= $v_key ?>&bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>" class="<?= $btn_cls ?>">
                            <i class="fas <?= $v_data['icon'] ?> text-base"></i> <?= $v_data['title'] ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- ALERTS -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-sm text-rose-600"></i> <?= $pesan_error ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($sp_kpi_latest)): ?>
                <?php 
                $sp_type = $sp_kpi_latest['jenis_sp'];
                $bg_card = ($sp_type === 'BLOKIR') ? 'bg-rose-100 border-rose-700' : (($sp_type === 'SP-2') ? 'bg-amber-50 border-amber-600' : 'bg-rose-50 border-rose-500');
                $txt_color = ($sp_type === 'BLOKIR') ? 'text-rose-950' : (($sp_type === 'SP-2') ? 'text-amber-950' : 'text-rose-900');
                ?>
                <div class="<?= $bg_card ?> border-l-4 p-4 rounded-r-xl shadow-sm mb-6 flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-white rounded-lg font-bold text-lg text-rose-600 shadow-sm">
                            <i class="fas <?= ($sp_type === 'BLOKIR') ? 'fa-ban' : 'fa-triangle-exclamation' ?>"></i>
                        </div>
                        <div>
                            <h4 class="font-bold <?= $txt_color ?> text-sm">
                                <?= ($sp_type === 'BLOKIR') ? 'AKUN DINONAKTIFKAN / DIBLOKIR' : 'SURAT PERINGATAN (' . htmlspecialchars($sp_type) . ') DITERBITKAN' ?>
                            </h4>
                            <p class="text-xs <?= $txt_color ?> opacity-90 mt-0.5">
                                <?= htmlspecialchars($sp_kpi_latest['alasan']) ?> (Terbit: <?= date('d M Y', strtotime($sp_kpi_latest['tanggal_terbit'])) ?>).
                                <?php if ($sp_type === 'BLOKIR'): ?>
                                    Akun Anda tidak dapat digunakan untuk presensi dan hanya dapat diaktifkan kembali oleh Super Admin.
                                <?php else: ?>
                                    Harap meningkatkan kedisiplinan dan berkoordinasi dengan pihak Manajemen Yayasan.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-rose-600 text-white font-extrabold text-[10px] rounded-full uppercase tracking-wider">
                        <?= htmlspecialchars($sp_type) ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- DYNAMIC RENDER OF SUB-VIEW -->
            <?php if ($view === 'kepsek'): ?>
                <!-- ============================================== -->
                <!-- TAMPILAN KPI KEPALA SEKOLAH                    -->
                <!-- ============================================== -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- KPI Summary Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col items-center justify-center text-center lg:col-span-1">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Nilai KPI Kepala Sekolah</span>
                        <div class="text-5xl font-black text-emerald-600 mb-2"><?= number_format($total_skor_kpi, 1) ?><span class="text-lg text-gray-400">/100</span></div>
                        <span class="px-3 py-1 text-xs font-bold rounded-full border <?= $color_predikat ?> mb-4"><?= $predikat ?></span>
                        <p class="text-[10px] text-gray-400 font-medium">Periode: <b><?= $months[$selected_month] ?> <?= $selected_year ?></b><br>Kepala Sekolah: <b><?= htmlspecialchars($user_data['nama'] ?? '') ?></b></p>
                    </div>

                    <!-- Input Form Laporan Mandiri -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
                        <h2 class="font-bold text-gray-800 text-sm mb-3 flex items-center gap-1.5 border-b pb-2">
                            <i class="fas fa-edit text-emerald-600"></i>
                            <span>Form Pengisian Mandiri Kepala Sekolah</span>
                        </h2>
                        <form method="POST" class="space-y-4" action="admin-pegawai-kpi.php?view=kepsek&bulan=<?= $selected_month ?>&tahun=<?= $selected_year ?>">
                            <input type="hidden" name="ustadz_id" value="<?= $user_id ?>">
                            <input type="hidden" name="periode" value="<?= $selected_period ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total Jam KBM Formal Terjadwal (Bulan Ini)</label>
                                    <input type="number" name="total_kbm_terjadwal" value="<?= $kbm_terjadwal ?>" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" required placeholder="Contoh: 120">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total Jam KBM Terlaksana (Bulan Ini)</label>
                                    <input type="number" name="total_kbm_terlaksana" value="<?= $kbm_terlaksana ?>" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" required placeholder="Contoh: 114">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total RPP Pelajaran Diknas Wajib (Bulan Ini)</label>
                                    <input type="number" name="rpp_diknas_total" value="<?= $rpp_total ?>" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" required placeholder="Contoh: 15">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Total RPP yang Dikontrol & Disetujui (Bulan Ini)</label>
                                    <input type="number" name="rpp_diknas_dikontrol" value="<?= $rpp_dikontrol ?>" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500" required placeholder="Contoh: 15">
                                </div>
                            </div>

                            <div class="flex justify-end pt-2">
                                <button type="submit" name="save_laporan_kepsek" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs flex items-center gap-1.5 shadow-md">
                                    <i class="fas fa-save text-sm"></i> Simpan Laporan Kinerja
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- KOTAK EVALUASI KEPSEK -->
                <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-100 p-6 mb-6 flex items-start">
                    <div class="bg-white p-3 rounded-full shadow-sm mr-4 flex-shrink-0">
                        <i class="fas <?= $ikon_evaluasi ?> text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-indigo-900 mb-1">Catatan Evaluasi Kinerja Khas (Auto-Generated)</h3>
                        <p class="text-sm text-indigo-800 leading-relaxed"><?= $pesan_evaluasi ?></p>
                    </div>
                </div>

                <!-- KPI Detail Component Cards -->
                <div class="space-y-4">
                    <h2 class="font-bold text-gray-800 text-base mb-2"><i class="fas fa-list-check text-emerald-600 mr-1.5"></i>Rincian 5 Aspek Penilaian KPI</h2>
                    
                    <!-- 1. KBM Control -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded">1</span>
                                Kontrol KBM Formal (Dinas/Akademik)
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Mengontrol pelaksanaan KBM harian formal dengan target keaktifan minimal **90%**. Jika keaktifan $\ge 90\%$, mendapat poin penuh.</p>
                            <div class="w-full bg-gray-100 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-600 h-2.5 rounded-full" style="width: <?= min(100, $score_kbm) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Kehadiran KBM: <b><?= $kbm_terlaksana ?></b> dari <b><?= $kbm_terjadwal ?></b> jam terjadwal (<b><?= number_format($persen_kbm, 1) ?>%</b>)</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_kbm, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 2. KBM Supervision -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded">2</span>
                                Supervisi KBM Asatidz
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Melakukan supervisi pengajaran diknas/diniyah minimal **2 kali supervisi sebulan**. Terintegrasi dengan sistem jurnal pengajaran.</p>
                            <div class="w-full bg-gray-100 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-600 h-2.5 rounded-full" style="width: <?= min(100, $score_supervisi) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Jumlah Supervisi Terlaksana: <b><?= $total_supervisi ?></b> dari <b>2</b> target bulanan.</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_supervisi, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 3. RPP Control -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded">3</span>
                                Kontrol Pengadaan Administrasi RPP Dinas
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Mengontrol dan menyetujui pengadaan administrasi mengajar RPP guru pelajaran diknas dengan target **100%**.</p>
                            <div class="w-full bg-gray-100 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-600 h-2.5 rounded-full" style="width: <?= min(100, $score_rpp) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">RPP Terkontrol: <b><?= $rpp_dikontrol ?></b> dari <b><?= $rpp_total ?></b> RPP wajib bulan ini.</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_rpp, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 4. Rapat Koordinasi -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded">4</span>
                                Hadir Rapat Koordinasi Yayasan
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Kehadiran Kepala Sekolah pada rapat koordinasi rutin dua pekanan dengan pengurus yayasan (Target: **2 kali rapat/bulan**).</p>
                            <div class="w-full bg-gray-100 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-600 h-2.5 rounded-full" style="width: <?= min(100, $score_rapat) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Kehadiran Rapat: <b><?= $total_rapat_hadir ?></b> dari <b>2</b> kali rapat bulan ini.</span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_rapat, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>

                    <!-- 5. Standarisasi Nilai Santri -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2 py-0.5 rounded">5</span>
                                Standarisasi Rata-rata Nilai Santri di atas KKM
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Evaluasi mutu akademik di mana nilai rata-rata santri di atas batas KKM (75) pada bulan-bulan ujian (Maret, Juni, Oktober, Desember). Diambil secara otomatis dari leger nilai digital.</p>
                            <div class="w-full bg-gray-100 h-2.5 rounded-full mt-3 overflow-hidden">
                                <div class="bg-emerald-600 h-2.5 rounded-full" style="width: <?= min(100, $score_nilai) ?>%"></div>
                            </div>
                            <span class="text-[10px] text-gray-400 block mt-1">Bulan Ujian: <b><?= $is_exam_month ? 'Aktif' : 'Non-Aktif (Auto 100 Poin)' ?></b>. Rata-rata Leger Sekolah: <b><?= number_format($avg_nilai, 1) ?></b></span>
                        </div>
                        <div class="text-right flex flex-col justify-center min-w-[80px]">
                            <span class="text-2xl font-bold text-gray-800"><?= number_format($score_nilai, 1) ?></span>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">Skor Aspek</span>
                        </div>
                    </div>
                </div>

            <?php elseif ($view === 'musyrif'): ?>
                <!-- ============================================== -->
                <!-- TAMPILAN KPI MUSYRIF ASRAMA                    -->
                <!-- ============================================== -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 font-outfit">
                    <!-- Skor Akhir Card -->
                    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-sm p-6 flex flex-col items-center justify-center text-center">
                        <div class="w-20 h-20 rounded-full bg-cyan-50 flex items-center justify-center text-cyan-600 mb-4 border shadow-inner">
                            <i class="fas fa-award text-3xl"></i>
                        </div>
                        <h2 class="font-bold text-slate-800 text-base"><?= htmlspecialchars($user_data['nama'] ?? '') ?></h2>
                        <span class="text-[9px] bg-slate-100 text-slate-500 font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider mb-4 border font-mono">
                            <?= htmlspecialchars(implode(', ', $user_roles_trimmed)) ?>
                        </span>

                        <div class="w-full py-4 border-t border-b border-slate-100 mb-4">
                            <span class="text-4xl font-extrabold text-slate-900"><?= number_format($total_skor_kpi, 1) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Skor Akhir KPI Musyrif</span>
                        </div>

                        <span class="px-4 py-1.5 rounded-xl text-xs font-bold bg-cyan-100 text-cyan-800 border border-cyan-200 shadow-sm">
                            <?= $predikat ?>
                        </span>
                    </div>

                    <!-- Progress Bars -->
                    <div class="bg-white rounded-2xl border border-gray-200/60 shadow-sm p-6 lg:col-span-2 space-y-4 text-left">
                        <h3 class="font-bold text-slate-800 text-xs pb-2 border-b uppercase tracking-wider"><i class="fas fa-tasks mr-2 text-cyan-600"></i>Capaian 8 Indikator Kepatuhan Kepengasuhan</h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>1. Validasi Ibadah Santri (15%)</span>
                                    <span><?= number_format($skor_validasi_ibadah, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_validasi_ibadah ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['validasi_ibadah'] ?></span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>2. Hubungan Walisantri (15%)</span>
                                    <span><?= number_format($skor_kontak_walisantri, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_kontak_walisantri ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['kontak_walisantri'] ?></span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>3. Chek Belajar Mandiri (10%)</span>
                                    <span><?= number_format($skor_belajar_mandiri, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_belajar_mandiri ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['belajar_mandiri'] ?> (Target: 20)</span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>4. Layanan Kesehatan (10%)</span>
                                    <span><?= number_format($skor_kesehatan, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_kesehatan ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['kesehatan'] ?></span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>5. Laporan Setoran Hafalan (15%)</span>
                                    <span><?= number_format($skor_setoran_hafalan, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_setoran_hafalan ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['setoran_hafalan'] ?></span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>6. Buku Mutaba'ah Santri (15%)</span>
                                    <span><?= number_format($skor_mutabaah, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_mutabaah ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['mutabaah'] ?></span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>7. Presensi Kehadiran Kerja (10%)</span>
                                    <span><?= number_format($skor_absensi_kerja, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_absensi_kerja ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['absensi_kerja'] ?></span>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs mb-1 font-semibold text-slate-700">
                                    <span>8. Kehadiran Rapat (10%)</span>
                                    <span><?= number_format($skor_absensi_rapat, 0) ?>/100</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                    <div class="bg-cyan-500 h-2 rounded-full" style="width: <?= $skor_absensi_rapat ?>%"></div>
                                </div>
                                <span class="text-[8px] text-slate-400 block mt-0.5"><?= $details['absensi_rapat'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KOTAK EVALUASI MUSYRIF -->
                <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-100 p-6 mb-6 flex items-start text-left">
                    <div class="bg-white p-3 rounded-full shadow-sm mr-4 flex-shrink-0">
                        <i class="fas <?= $ikon_evaluasi ?> text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-indigo-900 mb-1">Catatan Evaluasi Musyrif (Auto-Generated)</h3>
                        <p class="text-sm text-indigo-800 leading-relaxed"><?= $pesan_evaluasi ?></p>
                    </div>
                </div>

            <?php else: ?>
                <!-- ============================================== -->
                <!-- TAMPILAN KPI PEGAWAI / USTADZ (DEFAULT)        -->
                <!-- ============================================== -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <div class="md:col-span-1 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-center items-center text-center">
                        <h3 class="font-semibold opacity-80">Total Skor Kinerja Anda</h3>
                        <p class="text-6xl font-bold my-2"><?= number_format($total_skor_kpi, 2) ?></p>
                        <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium border border-white/30 mb-3"><?= $predikat ?></span>
                        <div class="w-full h-1 bg-white/30 rounded-full mt-2"><div class="h-1 bg-white rounded-full" style="width: <?= $total_skor_kpi ?>%;"></div></div>
                    </div>
                    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center text-left">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Simulasi Gaji & Bonus Kinerja</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center mb-4">
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <p class="text-xs text-gray-500 mb-1">Total Jam/Pertemuan</p>
                                <p class="text-lg font-bold text-gray-800"><?= $jumlah_pertemuan ?> Kali</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                                <p class="text-xs text-gray-500 mb-1">Predikat Kinerja</p>
                                <p class="text-lg font-bold text-gray-800"><?= $predikat ?></p>
                            </div>
                            <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                                <p class="text-xs text-blue-600 mb-1">Tarif Gaji (<?= $predikat ?>)</p>
                                <p class="text-lg font-bold text-blue-700">Rp <?= number_format($gaji_per_pertemuan, 0, ',', '.') ?> / Pertemuan</p>
                            </div>
                        </div>
                        <div class="flex justify-between items-center bg-gray-900 text-white p-4 rounded-lg">
                            <div>
                                <p class="text-sm text-gray-300">Take Home Pay (Bulan Ini)</p>
                                <p class="text-xs text-gray-400 mt-1"><?= $jumlah_pertemuan ?> Pertemuan x Tarif Gaji <?= $predikat ?></p>
                            </div>
                            <p class="text-3xl font-bold text-amber-400">Rp <?= number_format($gaji_total, 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>

                <!-- KOTAK EVALUASI USTADZ -->
                <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-100 p-6 mb-6 flex items-start text-left">
                    <div class="bg-white p-3 rounded-full shadow-sm mr-4 flex-shrink-0">
                        <i class="fas <?= $ikon_evaluasi ?> text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-indigo-900 mb-1">Catatan Evaluasi Kinerja (Auto-Generated)</h3>
                        <p class="text-sm text-indigo-800 leading-relaxed"><?= $pesan_evaluasi ?></p>
                    </div>
                </div>

                <!-- DETAIL SKOR PER PILAR -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 text-left">
                    <!-- Pilar 1 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-clipboard-check text-blue-500 mr-2"></i> Administrasi (20%)</h4>
                        <p class="text-3xl font-bold text-blue-600 my-3"><?= number_format($skor_administrasi, 2) ?></p>
                        <ul class="text-xs space-y-2 text-gray-600">
                            <li class="flex justify-between"><span>Ketepatan Jurnal</span> <span class="font-bold"><?= number_format($skor_jurnal, 0) ?></span></li>
                            <li class="flex justify-between"><span>Kehadiran (QR)</span> <span class="font-bold"><?= number_format($skor_kehadiran, 0) ?></span></li>
                            <li class="flex justify-between"><span>Kehadiran Rapat</span> <span class="font-bold"><?= number_format($skor_kehadiran_rapat, 0) ?></span></li>
                        </ul>
                    </div>
                    <!-- Pilar 2 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-chalkboard-teacher text-purple-500 mr-2"></i> Kualitas Ajar (40%)</h4>
                        <p class="text-3xl font-bold text-purple-600 my-3"><?= number_format($skor_kualitas_pengajaran, 2) ?></p>
                        <ul class="text-xs space-y-2 text-gray-600">
                            <li class="flex justify-between"><span>Inovasi (Pakai AI)</span> <span class="font-bold"><?= number_format($skor_penggunaan_ai, 0) ?></span></li>
                            <li class="flex justify-between"><span>Supervisi Kepsek</span> <span class="font-bold"><?= number_format($skor_supervisi, 0) ?></span></li>
                        </ul>
                    </div>
                    <!-- Pilar 3 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-graduation-cap text-emerald-500 mr-2"></i> Capaian Santri (30%)</h4>
                        <p class="text-3xl font-bold text-emerald-600 my-3"><?= number_format($skor_capaian_santri, 2) ?></p>
                        <ul class="text-xs space-y-2 text-gray-600">
                            <li class="flex justify-between"><span>Rata-rata Nilai</span> <span class="font-bold"><?= number_format($skor_rata_nilai, 0) ?></span></li>
                            <li class="flex justify-between"><span>Pertumbuhan Nilai</span> <span class="font-bold"><?= number_format($skor_pertumbuhan, 0) ?></span></li>
                        </ul>
                    </div>
                    <!-- Pilar 4 -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-rocket text-amber-500 mr-2"></i> Pengembangan Diri (10%)</h4>
                        <p class="text-3xl font-bold text-amber-600 my-3"><?= number_format($skor_pengembangan_diri, 2) ?></p>
                        <ul class="text-xs space-y-2 text-gray-600">
                            <li class="flex justify-between"><span>Kontribusi Silabus</span> <span class="font-bold"><?= number_format($skor_kontribusi_silabus, 0) ?></span></li>
                            <li class="flex justify-between"><span>Upload Sertifikat</span> <span class="font-bold">0</span></li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
            document.getElementById('sidebar-hr').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
        });
    </script>
</body>
</html>