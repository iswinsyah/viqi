<?php
// Prevent caching (Bypass Litespeed/Cloudflare/Browser Caches)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../koneksi.php';

// Self-healing: Buat tabel penyimpanan evaluasi KPI Musyrif jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS evaluasi_kpi_musyrif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    musyrif_id INT NOT NULL,
    bulan INT NOT NULL,
    tahun INT NOT NULL,
    skor_kpi DECIMAL(5,2) DEFAULT 0,
    predikat VARCHAR(50),
    evaluasi_ai LONGTEXT,
    catatan_pimpinan TEXT,
    saved_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_musyrif_period (musyrif_id, bulan, tahun)
)");

// Handler Simpan Evaluasi Kinerja (Save Button)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_evaluasi') {
    $m_id = (int)$_POST['musyrif_id'];
    $bln = (int)$_POST['bulan'];
    $thn = (int)$_POST['tahun'];
    $skor = (float)$_POST['skor_kpi'];
    $pred = $conn->real_escape_string($_POST['predikat'] ?? '');
    $ai_text = $_POST['evaluasi_ai'] ?? '';
    $s_by = $_SESSION['app_user_nama'] ?? ($_SESSION['nama_lengkap'] ?? ($_SESSION['ustadz_nama'] ?? ($_SESSION['username'] ?? 'Pimpinan / Yayasan')));

    $stmt = $conn->prepare("INSERT INTO evaluasi_kpi_musyrif (musyrif_id, bulan, tahun, skor_kpi, predikat, evaluasi_ai, saved_by) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE skor_kpi = VALUES(skor_kpi), predikat = VALUES(predikat), evaluasi_ai = VALUES(evaluasi_ai), saved_by = VALUES(saved_by), updated_at = NOW()");
    $stmt->bind_param("iiidsss", $m_id, $bln, $thn, $skor, $pred, $ai_text, $s_by);
    $ok = $stmt->execute();
    
    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $ok ? 'success' : 'error', 
            'message' => $ok ? 'Hasil evaluasi kinerja berhasil disimpan ke database!' : 'Gagal menyimpan evaluasi: ' . $conn->error,
            'updated_at' => date('d/m/Y H:i'),
            'saved_by' => $s_by
        ]);
        exit;
    }
    header("Location: kpi-musyrif.php?musyrif_id=$m_id&bulan=$bln&tahun=$thn&sukses=" . urlencode("Hasil evaluasi kinerja berhasil disimpan!"));
    exit;
}

// Validasi akses (Super Admin / Yayasan / Kepala Sekolah / Kepala Ma'had / Musyrif)
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (empty($user_roles) && isset($_SESSION['app_user_roles'])) {
    $user_roles = explode(',', $_SESSION['app_user_roles']);
}
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : (isset($_SESSION['app_user_id']) ? (int)$_SESSION['app_user_id'] : 0);
$session_username = $_SESSION['app_username'] ?? ($_SESSION['username'] ?? '');
$is_super_admin = ($ustadz_id === 9999) || (!empty($_SESSION['app_user_id']) && (int)$_SESSION['app_user_id'] === 1) || (!empty($session_username) && in_array(strtolower($session_username), ['viqi', 'winsyah']));

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_authorized = $is_super_admin || !empty(array_intersect($norm_roles, [
    'super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan',
    'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah',
    'kepala_asrama', 'musyrif', 'musyrifah'
]));

if (!$is_authorized) {
    die("Akses ditolak. Menu ini hanya dapat diakses oleh Yayasan, Kepala Ma'had, dan Musyrif.");
}

$is_pimpinan = $is_super_admin || !empty(array_intersect($norm_roles, [
    'super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan',
    'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah', 'kepala_asrama'
]));

// 1. Ambil daftar Musyrif & Musyrifah
$musyrif_list = [];
$res_m = $conn->query("SELECT id, nama FROM akun_ustadz WHERE role LIKE '%musyrif%' OR role LIKE '%kepala_asrama%' ORDER BY nama ASC");
if ($res_m) {
    while ($r = $res_m->fetch_assoc()) {
        $musyrif_list[] = $r;
    }
}

// Filter parameters
if (!$is_pimpinan && $ustadz_id > 0) {
    $selected_musyrif_id = $ustadz_id;
} else {
    $selected_musyrif_id = isset($_GET['musyrif_id']) ? (int)$_GET['musyrif_id'] : ($musyrif_list[0]['id'] ?? 0);
}
$selected_month = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$selected_year = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$start_date = date('Y-m-d', mktime(0, 0, 0, $selected_month - 1, 27, $selected_year));
$end_date   = date('Y-m-d', mktime(0, 0, 0, $selected_month, 26, $selected_year));

// Fetch Musyrif Detail
$staf = null;
if ($selected_musyrif_id > 0) {
    $res_det = $conn->query("SELECT * FROM akun_ustadz WHERE id = $selected_musyrif_id LIMIT 1");
    if ($res_det) $staf = $res_det->fetch_assoc();
}

// Ambil Evaluasi Tersimpan (Jika ada)
$saved_eval = null;
if ($selected_musyrif_id > 0) {
    $res_ev = $conn->query("SELECT * FROM evaluasi_kpi_musyrif WHERE musyrif_id = $selected_musyrif_id AND bulan = $selected_month AND tahun = $selected_year LIMIT 1");
    if ($res_ev && $res_ev->num_rows > 0) {
        $saved_eval = $res_ev->fetch_assoc();
    }
}

$kpi_data = [];
$predikat = "Belum Ternilai";
$predikat_class = "bg-slate-100 text-slate-700";
$total_kpi = 0;

$skor_validasi_ibadah = 100;
$skor_kontak_walisantri = 100;
$skor_belajar_mandiri = 100;
$skor_kesehatan = 100;
$skor_setoran_hafalan = 100;
$skor_mutabaah = 100;
$skor_absensi_kerja = 100;
$skor_absensi_rapat = 100;

$details = [];

if ($staf) {
    // Get Santri Binaan IDs in halaqoh group
    $santri_ids = [];
    $res_sb = $conn->query("
        SELECT DISTINCT s.id 
        FROM buku_induk_santri s 
        JOIN halaqoh_anggota a ON s.id = a.santri_id 
        JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE g.musyrif_id = $selected_musyrif_id AND s.status_santri = 'Aktif'
    ");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_ids[] = (int)$r['id'];
    }
    $total_santri_binaan = count($santri_ids);
    $santri_list_str = !empty($santri_ids) ? implode(',', $santri_ids) : '0';

    // 1. Validasi Ibadah (Bobot: 15%)
    if ($total_santri_binaan > 0) {
        $res_ib = $conn->query("
            SELECT COUNT(*) as total, 
                   SUM(CASE WHEN status_validasi IN ('Disetujui', 'Ditolak') THEN 1 ELSE 0 END) as divalidasi
            FROM ibadah_harian_santri
            WHERE santri_id IN ($santri_list_str) 
              AND tanggal BETWEEN '$start_date' AND '$end_date'
        ");
        $row_ib = $res_ib ? $res_ib->fetch_assoc() : ['total' => 0, 'divalidasi' => 0];
        $total_ib = (int)($row_ib['total'] ?? 0);
        $dival_ib = (int)($row_ib['divalidasi'] ?? 0);
        
        $kepatuhan_klik = $total_ib > 0 ? ($dival_ib / $total_ib) * 100 : 100;
        
        // Hitung bimbingan aktif (santri malas ibadah / munfarid & non-haid)
        $res_bim = $conn->query("
            SELECT COUNT(*) as total_perlu,
                   SUM(CASE WHEN catatan_musyrif IS NOT NULL AND TRIM(catatan_musyrif) != '' THEN 1 ELSE 0 END) as dibimbing
            FROM ibadah_harian_santri
            WHERE santri_id IN ($santri_list_str)
              AND tanggal BETWEEN '$start_date' AND '$end_date'
              AND is_haid = 0
              AND (sholat_subuh = 'Munfarid' OR sholat_dhuhur = 'Munfarid' OR sholat_ashar = 'Munfarid' OR sholat_maghrib = 'Munfarid' OR sholat_isya = 'Munfarid')
        ");
        $row_bim = $res_bim ? $res_bim->fetch_assoc() : ['total_perlu' => 0, 'dibimbing' => 0];
        $total_perlu_bim = (int)($row_bim['total_perlu'] ?? 0);
        $total_dibimbing = (int)($row_bim['dibimbing'] ?? 0);
        
        $kepatuhan_bimbingan = $total_perlu_bim > 0 ? ($total_dibimbing / $total_perlu_bim) * 100 : 100;
        
        // Gabungan: 80% Kepatuhan Klik Validasi + 20% Kepatuhan Bimbingan Aktif
        $skor_validasi_ibadah = (0.8 * $kepatuhan_klik) + (0.2 * $kepatuhan_bimbingan);
        
        $details['validasi_ibadah'] = "$dival_ib dari $total_ib divalidasi (" . number_format($kepatuhan_klik, 0) . "%), bimbingan: $total_dibimbing dari $total_perlu_bim diisi";
    } else {
        $details['validasi_ibadah'] = "Tidak ada santri binaan";
    }

    // 2. Kontak Walisantri (Bobot: 15%)
    if ($total_santri_binaan > 0) {
        $res_kon = $conn->query("
            SELECT COUNT(DISTINCT santri_id) as total_kontak
            FROM jurnal_kontak_orangtua
            WHERE ustadz_id = $selected_musyrif_id 
              AND santri_id IN ($santri_list_str)
              AND tanggal BETWEEN '$start_date' AND '$end_date'
        ");
        $kontak_cnt = $res_kon ? (int)($res_kon->fetch_assoc()['total_kontak'] ?? 0) : 0;
        $skor_kontak_walisantri = min(100, ($kontak_cnt / $total_santri_binaan) * 100);
        $details['kontak_walisantri'] = "$kontak_cnt dari $total_santri_binaan walisantri dihubungi";
    } else {
        $details['kontak_walisantri'] = "Tidak ada santri binaan";
    }

    // 3. Chek Belajar Mandiri (Bobot: 10%)
    $res_bel = $conn->query("
        SELECT COUNT(*) as total 
        FROM jurnal_belajar_mandiri
        WHERE ustadz_id = $selected_musyrif_id 
          AND tanggal BETWEEN '$start_date' AND '$end_date'
    ");
    $total_bel = $res_bel ? (int)($res_bel->fetch_assoc()['total'] ?? 0) : 0;
    $skor_belajar_mandiri = min(100, ($total_bel / 20) * 100);
    $details['belajar_mandiri'] = "$total_bel kali pengisian jurnal belajar";

    // 4. Chek Kesehatan (Bobot: 10%)
    if ($total_santri_binaan > 0) {
        $res_kes = $conn->query("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN status_kesehatan = 'Sehat / Sembuh' OR status_izin_sekolah = 'Selesai / Sembuh' THEN 1 ELSE 0 END) as sembuh
            FROM jurnal_kesehatan_santri
            WHERE santri_id IN ($santri_list_str)
              AND tanggal BETWEEN '$start_date' AND '$end_date'
        ");
        $row_kes = $res_kes ? $res_kes->fetch_assoc() : ['total' => 0, 'sembuh' => 0];
        $total_kes = (int)($row_kes['total'] ?? 0);
        $sembuh_kes = (int)($row_kes['sembuh'] ?? 0);
        $skor_kesehatan = $total_kes > 0 ? ($sembuh_kes / $total_kes) * 100 : 100;
        $details['kesehatan'] = "$sembuh_kes dari $total_kes kasus sakit diselesaikan";
    } else {
        $details['kesehatan'] = "Tidak ada santri binaan";
    }

    // 5. Setoran Hafalan (Bobot: 15%)
    if ($total_santri_binaan > 0) {
        $res_haf = $conn->query("
            SELECT COUNT(*) as total 
            FROM laporan_setoran_hafalan
            WHERE santri_id IN ($santri_list_str)
              AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
        ");
        $total_haf = $res_haf ? (int)($res_haf->fetch_assoc()['total'] ?? 0) : 0;
        $target_haf = $total_santri_binaan * 4;
        $skor_setoran_hafalan = $target_haf > 0 ? min(100, ($total_haf / $target_haf) * 100) : 100;
        $details['setoran_hafalan'] = "$total_haf kali setoran diinput (Target: $target_haf)";
    } else {
        $details['setoran_hafalan'] = "Tidak ada santri binaan";
    }

    // 6. Buku Mutabaah Santri (Bobot: 15%)
    if ($total_santri_binaan > 0) {
        $res_mut = $conn->query("
            SELECT COUNT(*) as total 
            FROM buku_mutabaah
            WHERE musyrif_id = $selected_musyrif_id 
              AND tanggal BETWEEN '$start_date' AND '$end_date'
        ");
        $total_mut = $res_mut ? (int)($res_mut->fetch_assoc()['total'] ?? 0) : 0;
        $target_mut = $total_santri_binaan * 2;
        $skor_mutabaah = $target_mut > 0 ? min(100, ($total_mut / $target_mut) * 100) : 100;
        $details['mutabaah'] = "$total_mut laporan mental diinput (Target: $target_mut)";
    } else {
        $details['mutabaah'] = "Tidak ada santri binaan";
    }

    // 7. Absensi Jam Kerja (Bobot: 10%)
    $res_abs = $conn->query("
        SELECT COUNT(DISTINCT DATE(waktu_absen)) as total_absen
        FROM absensi_pegawai
        WHERE ustadz_id = $selected_musyrif_id 
          AND jenis_absen IN ('Pegawai', 'Harian')
          AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'
          AND status_kehadiran = 'Masuk'
    ");
    $total_absen = $res_abs ? (int)($res_abs->fetch_assoc()['total_absen'] ?? 0) : 0;
    $skor_absensi_kerja = min(100, ($total_absen / 26) * 100);
    $details['absensi_kerja'] = "$total_absen hari hadir kerja (Target: 26)";

    // 8. Absensi Kehadiran Rapat (Bobot: 10%)
    $res_rpt_hadir = $conn->query("
        SELECT COUNT(DISTINCT rapat_id) as total_hadir
        FROM absensi_pegawai
        WHERE ustadz_id = $selected_musyrif_id 
          AND jenis_absen = 'Rapat'
          AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'
          AND status_kehadiran = 'Masuk'
    ");
    $hadir_rapat = $res_rpt_hadir ? (int)$res_rpt_hadir->fetch_assoc()['total_hadir'] : 0;

    $res_tot_rapat = $conn->query("
        SELECT COUNT(*) as total 
        FROM jadwal_rapat 
        WHERE DATE(waktu_mulai) BETWEEN '$start_date' AND '$end_date'
    ");
    $total_rapat_bln = $res_tot_rapat ? (int)$res_tot_rapat->fetch_assoc()['total'] : 0;
    $skor_absensi_rapat = $total_rapat_bln > 0 ? min(100, ($hadir_rapat / $total_rapat_bln) * 100) : 100;
    $details['absensi_rapat'] = "$hadir_rapat dari $total_rapat_bln rapat dihadiri";

    // Total KPI Score
    $total_kpi = ($skor_validasi_ibadah * 0.15) + 
                 ($skor_kontak_walisantri * 0.15) + 
                 ($skor_belajar_mandiri * 0.10) + 
                 ($skor_kesehatan * 0.10) + 
                 ($skor_setoran_hafalan * 0.15) + 
                 ($skor_mutabaah * 0.15) + 
                 ($skor_absensi_kerja * 0.10) + 
                 ($skor_absensi_rapat * 0.10);

    // Hitung Predikat
    if ($total_kpi >= 90) {
        $predikat = "Mumtaz (Grade A)";
        $predikat_class = "bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-sm";
    } elseif ($total_kpi >= 80) {
        $predikat = "Jayyid Jiddan (Grade B)";
        $predikat_class = "bg-cyan-100 text-cyan-800 border border-cyan-200 shadow-sm";
    } elseif ($total_kpi >= 70) {
        $predikat = "Jayyid (Grade C)";
        $predikat_class = "bg-amber-100 text-amber-800 border border-amber-200 shadow-sm";
    } elseif ($total_kpi >= 60) {
        $predikat = "Maqbul (Grade D)";
        $predikat_class = "bg-orange-100 text-orange-800 border border-orange-200 shadow-sm";
    } else {
        $predikat = "Dhaif (Grade E)";
        $predikat_class = "bg-rose-100 text-rose-800 border border-rose-200 shadow-sm animate-pulse";
    }
    // Ambil Data Rinci Per-Individu Santri Binaan untuk Tabel & Grafik Perkembangan
    $santri_binaan_detail = [];
    if (!empty($santri_ids)) {
        $res_all_santri = $conn->query("
            SELECT s.id, s.nama_santri, s.kamar, s.jenjang, s.foto, s.nomor_wa_ortu, s.nama_ayah, s.nama_ibu
            FROM buku_induk_santri s
            WHERE s.id IN ($santri_list_str) AND s.status_santri = 'Aktif'
            ORDER BY s.nama_santri ASC
        ");
        if ($res_all_santri) {
            while ($s = $res_all_santri->fetch_assoc()) {
                $sid = (int)$s['id'];
                
                // 1. Ibadah Santri Bulan Ini
                $res_ib_s = $conn->query("
                    SELECT COUNT(*) as total_hari,
                           AVG(total_persen_ibadah) as rata_persen,
                           SUM(CASE WHEN sholat_subuh='Berjamaah' AND sholat_dhuhur='Berjamaah' AND sholat_ashar='Berjamaah' AND sholat_maghrib='Berjamaah' AND sholat_isya='Berjamaah' THEN 1 ELSE 0 END) as sholat_5_jamaah
                    FROM ibadah_harian_santri
                    WHERE santri_id = $sid AND tanggal BETWEEN '$start_date' AND '$end_date'
                ");
                $data_ib_s = $res_ib_s ? $res_ib_s->fetch_assoc() : [];
                $total_hari_ib = (int)($data_ib_s['total_hari'] ?? 0);
                $rata_ibadah = round((float)($data_ib_s['rata_persen'] ?? 0), 1);
                $jamaah_5 = (int)($data_ib_s['sholat_5_jamaah'] ?? 0);
                
                // 2. Setoran Hafalan Bulan Ini & Terakhir
                $res_haf_s = $conn->query("
                    SELECT COUNT(*) as total_setoran,
                           SUM(CASE WHEN grade LIKE '%Mutqin%' OR grade LIKE '%Mumtaz%' THEN 1 ELSE 0 END) as setoran_mutqin
                    FROM laporan_setoran_hafalan
                    WHERE santri_id = $sid AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'
                ");
                $data_haf_s = $res_haf_s ? $res_haf_s->fetch_assoc() : [];
                $total_setoran_bln = (int)($data_haf_s['total_setoran'] ?? 0);
                $mutqin_cnt = (int)($data_haf_s['setoran_mutqin'] ?? 0);
                
                // Setoran Terakhir
                $res_last_haf = $conn->query("
                    SELECT nama_surat, ayat_mulai, ayat_sampai, juz, grade, created_at
                    FROM laporan_setoran_hafalan
                    WHERE santri_id = $sid
                    ORDER BY created_at DESC, id DESC LIMIT 1
                ");
                $last_haf = ($res_last_haf && $res_last_haf->num_rows > 0) ? $res_last_haf->fetch_assoc() : null;
                
                // 3. Catatan Kesehatan & Mutabaah
                $res_sakit = $conn->query("
                    SELECT COUNT(*) as total_sakit
                    FROM jurnal_kesehatan_santri
                    WHERE santri_id = $sid AND tanggal BETWEEN '$start_date' AND '$end_date'
                ");
                $total_sakit = $res_sakit ? (int)$res_sakit->fetch_assoc()['total_sakit'] : 0;
                
                $res_mut = $conn->query("
                    SELECT kondisi_mental, catatan, created_at
                    FROM mutabaah_santri
                    WHERE santri_id = $sid
                    ORDER BY tanggal DESC, id DESC LIMIT 1
                ");
                $last_mut = ($res_mut && $res_mut->num_rows > 0) ? $res_mut->fetch_assoc() : null;
                
                // 4. Kontak Orang Tua
                $res_ortu = $conn->query("
                    SELECT COUNT(*) as total_kontak, MAX(tanggal) as tanggal_terakhir
                    FROM jurnal_kontak_orangtua
                    WHERE santri_id = $sid AND ustadz_id = $selected_musyrif_id AND tanggal BETWEEN '$start_date' AND '$end_date'
                ");
                $data_ortu = $res_ortu ? $res_ortu->fetch_assoc() : [];
                $total_kontak = (int)($data_ortu['total_kontak'] ?? 0);
                
                $santri_binaan_detail[] = [
                    'id' => $sid,
                    'nama' => $s['nama_santri'],
                    'kamar' => $s['kamar'] ?? '-',
                    'jenjang' => $s['jenjang'] ?? '-',
                    'foto' => $s['foto'] ?? '',
                    'nomor_wa' => $s['nomor_wa_ortu'] ?? '',
                    'nama_ortu' => $s['nama_ayah'] ?? ($s['nama_ibu'] ?? 'Walisantri'),
                    'total_hari_ib' => $total_hari_ib,
                    'rata_ibadah' => $rata_ibadah,
                    'jamaah_5' => $jamaah_5,
                    'total_setoran' => $total_setoran_bln,
                    'mutqin_cnt' => $mutqin_cnt,
                    'last_haf' => $last_haf,
                    'total_sakit' => $total_sakit,
                    'last_mut' => $last_mut,
                    'total_kontak' => $total_kontak
                ];
            }
        }
    }
}

$active_menu = 'kpi_musyrif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI & Evaluasi Kinerja Musyrif | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .markdown-body { font-size: 0.85rem; line-height: 1.6; }
        .markdown-body h1, .markdown-body h2, .markdown-body h3 { font-weight: 700; color: #1e1b4b; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body h1 { font-size: 1.2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem; }
        .markdown-body p { margin-bottom: 0.75rem; text-align: justify; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.25rem; margin-bottom: 0.75rem; }
        .markdown-body strong { color: #0f172a; }
        .markdown-body blockquote {
            border-left: 4px solid #f59e0b;
            padding-left: 0.75rem;
            color: #4b5563;
            font-style: italic;
            background: #fffbeb;
            margin: 0.75rem 0;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
    </style>
</head>
<body class="bg-amber-50/20 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-amber-100">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-amber-800 hover:text-amber-950 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 hidden sm:block">KPI & Evaluasi Kinerja Musyrif Asrama</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="../dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    <i class="fas fa-arrow-left text-[11px]"></i> Ke Dashboard
                </a>
                <span class="text-xs bg-amber-50 text-amber-800 font-bold px-3 py-1.5 rounded-full border border-amber-200">
                    <i class="fas fa-crown mr-1"></i> Yayasan Boardroom
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-amber-50/10 p-6">
            
            <div class="mb-6">
                <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                    <i class="fas fa-chart-line text-amber-700"></i> KPI & Penilaian Kinerja Musyrif
                </h1>
                <p class="text-xs text-slate-500 mt-1">Perhitungan otomatis kepatuhan administrasi kontrol santri (halaqoh/asrama) dan absensi kerja.</p>
            </div>

            <!-- FILTER PENILAIAN -->
            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-5 mb-6">
                <form action="kpi-musyrif.php" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Pilih Musyrif / Musyrifah</label>
                        <?php if ($is_pimpinan): ?>
                        <select name="musyrif_id" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 bg-white font-semibold">
                            <option value="">-- Pilih --</option>
                            <?php foreach ($musyrif_list as $m): ?>
                                <option value="<?= $m['id'] ?>" <?= $selected_musyrif_id == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <div class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs bg-slate-50 font-bold text-slate-700">
                            <?= htmlspecialchars($staf['nama'] ?? 'Kinerja Saya') ?>
                        </div>
                        <input type="hidden" name="musyrif_id" value="<?= $selected_musyrif_id ?>">
                        <?php endif; ?>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Bulan</label>
                        <select name="bulan" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 bg-white">
                            <?php
                            $months = [
                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                            ];
                            foreach ($months as $num => $name) {
                                $sel = ($selected_month == $num) ? 'selected' : '';
                                echo "<option value=\"$num\" $sel>$name</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tahun</label>
                        <select name="tahun" required class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500 bg-white">
                            <?php
                            $curr_yr = (int)date('Y');
                            for ($y = $curr_yr - 2; $y <= $curr_yr + 1; $y++) {
                                $sel = ($selected_year == $y) ? 'selected' : '';
                                echo "<option value=\"$y\" $sel>$y</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-amber-800 hover:bg-amber-900 text-white font-bold py-2.5 px-6 rounded-xl text-xs shadow-sm transition w-full"><i class="fas fa-filter mr-2"></i> Filter Kinerja</button>
                </form>
            </div>

            <?php if ($staf): ?>
                <!-- CARD UTAMA KINERJA -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    
                    <!-- SKOR UTAMA -->
                    <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 flex flex-col items-center justify-center text-center">
                        <div class="w-24 h-24 rounded-full bg-amber-50 flex items-center justify-center text-amber-700 mb-4 border border-amber-200 shadow-inner">
                            <i class="fas fa-award text-4xl"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-800 mb-1"><?= htmlspecialchars($staf['nama']) ?></h2>
                        <span class="text-[10px] bg-slate-100 text-slate-500 font-bold px-2.5 py-1 rounded-full uppercase tracking-wider border mb-4">
                            <?= htmlspecialchars(str_replace('_', ' ', $staf['role'])) ?>
                        </span>

                        <div class="w-full py-4 border-t border-b border-slate-100 mb-4">
                            <span class="text-4xl font-extrabold text-slate-900"><?= number_format($total_kpi, 1) ?></span>
                            <span class="text-xs text-slate-400 block mt-1">Skor KPI Akumulatif</span>
                        </div>

                        <span class="px-4 py-2 rounded-xl text-xs font-bold <?= $predikat_class ?>">
                            <?= $predikat ?>
                        </span>
                        
                        <p class="text-[10px] text-slate-400 text-center mt-4 italic"><i class="fas fa-info-circle mr-1"></i>Skor KPI dihitung secara adil berdasarkan ketersediaan logs administrasi di database pondok.</p>
                    </div>

                    <!-- PROGRESS BAR RADAR 8 METRICS -->
                    <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 lg:col-span-2 space-y-4">
                        <h3 class="font-bold text-slate-800 text-sm pb-2 border-b"><i class="fas fa-tasks mr-2 text-indigo-600"></i>Pencapaian 8 Indikator Kinerja Musyrif</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            
                            <!-- 1. Validasi Ibadah -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>1. Validasi Ibadah (15%)</span>
                                    <span class="text-indigo-600"><?= number_format($skor_validasi_ibadah, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" style="width: <?= $skor_validasi_ibadah ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['validasi_ibadah'] ?></span>
                            </div>

                            <!-- 2. Kontak Walisantri -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>2. Kontak Walisantri (15%)</span>
                                    <span class="text-emerald-600"><?= number_format($skor_kontak_walisantri, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" style="width: <?= $skor_kontak_walisantri ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['kontak_walisantri'] ?></span>
                            </div>

                            <!-- 3. Chek Belajar Mandiri -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>3. Chek Belajar Mandiri (10%)</span>
                                    <span class="text-cyan-600"><?= number_format($skor_belajar_mandiri, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-cyan-600 h-full rounded-full transition-all duration-500" style="width: <?= $skor_belajar_mandiri ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['belajar_mandiri'] ?></span>
                            </div>

                            <!-- 4. Chek Kesehatan -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>4. Chek Kesehatan (10%)</span>
                                    <span class="text-rose-600"><?= number_format($skor_kesehatan, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: <?= $skor_kesehatan ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['kesehatan'] ?></span>
                            </div>

                            <!-- 5. Setoran Hafalan -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>5. Setoran Hafalan (15%)</span>
                                    <span class="text-amber-600"><?= number_format($skor_setoran_hafalan, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-amber-500 h-full rounded-full transition-all duration-500" style="width: <?= $skor_setoran_hafalan ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['setoran_hafalan'] ?></span>
                            </div>

                            <!-- 6. Buku Mutabaah Santri -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>6. Buku Mutabaah Santri (15%)</span>
                                    <span class="text-teal-600"><?= number_format($skor_mutabaah, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-teal-600 h-full rounded-full transition-all duration-500" style="width: <?= $skor_mutabaah ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['mutabaah'] ?></span>
                            </div>

                            <!-- 7. Absensi Jam Kerja -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>7. Absensi Jam Kerja (10%)</span>
                                    <span class="text-pink-600"><?= number_format($skor_absensi_kerja, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-pink-500 h-full rounded-full transition-all duration-500" style="width: <?= $skor_absensi_kerja ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['absensi_kerja'] ?></span>
                            </div>

                            <!-- 8. Absensi Kehadiran Rapat -->
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-700 mb-1">
                                    <span>8. Absensi Kehadiran Rapat (10%)</span>
                                    <span class="text-violet-600"><?= number_format($skor_absensi_rapat, 0) ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                                    <div class="bg-violet-600 h-full rounded-full transition-all duration-500" style="width: <?= $skor_absensi_rapat ?>%"></div>
                                </div>
                                <span class="text-[10px] text-slate-400 block mt-0.5"><?= $details['absensi_rapat'] ?></span>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ============================================================== -->
                <!-- TABEL & GRAFIK PERKEMBANGAN PER-INDIVIDU SANTRI BINAAN         -->
                <!-- ============================================================== -->
                <div class="bg-white rounded-2xl shadow-sm border border-amber-200/80 p-6 mb-8 overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-amber-100 pb-4 mb-6 gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                <i class="fas fa-users-viewfinder text-amber-700"></i> Rekap Perkembangan Per-Individu Santri Binaan
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Monitoring mendalam progress hafalan, kepatuhan ibadah, mutaba'ah, dan kesehatan untuk setiap santri binaan <?= htmlspecialchars($staf['nama'] ?? '') ?> (Periode: <?= $months[$selected_month] ?> <?= $selected_year ?>).</p>
                        </div>
                        <span class="px-3.5 py-1.5 bg-amber-50 text-amber-900 border border-amber-200 rounded-xl text-xs font-bold self-start sm:self-auto flex items-center gap-1.5 shadow-xs">
                            <i class="fas fa-user-graduate text-amber-600"></i> Total: <?= count($santri_binaan_detail) ?> Santri Binaan
                        </span>
                    </div>

                    <?php if (!empty($santri_binaan_detail)): ?>
                        <!-- GRAFIK STATISTIK SANTRI BINAAN -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                            <!-- Grafik 1: Rata-Rata Skor Ibadah -->
                            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/70">
                                <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center justify-between">
                                    <span><i class="fas fa-mosque text-emerald-600 mr-1.5"></i> Kepatuhan Ibadah Santri Binaan (%)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Target: 100%</span>
                                </h4>
                                <div class="h-48">
                                    <canvas id="chartIbadahSantri"></canvas>
                                </div>
                            </div>

                            <!-- Grafik 2: Total Setoran Hafalan -->
                            <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/70">
                                <h4 class="text-xs font-bold text-slate-800 mb-3 flex items-center justify-between">
                                    <span><i class="fas fa-quran text-amber-600 mr-1.5"></i> Progress Setoran Hafalan (Bulan Ini)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Target: 4x Setoran</span>
                                </h4>
                                <div class="h-48">
                                    <canvas id="chartHafalanSantri"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- TABEL DETAIL SANTRI BINAAN -->
                        <div class="overflow-x-auto -mx-6 px-6">
                            <table class="min-w-full divide-y divide-slate-100 text-xs">
                                <thead>
                                    <tr class="bg-amber-50/60 text-slate-600 text-[11px] font-bold uppercase tracking-wider">
                                        <th class="py-3 px-3 text-left">Santri</th>
                                        <th class="py-3 px-3 text-left">Kamar / Jenjang</th>
                                        <th class="py-3 px-3 text-left">Prestasi Ibadah (Bulan Ini)</th>
                                        <th class="py-3 px-3 text-left">Progress Hafalan</th>
                                        <th class="py-3 px-3 text-left">Mutaba'ah & Kesehatan</th>
                                        <th class="py-3 px-3 text-center">Kontak Walisantri</th>
                                        <th class="py-3 px-3 text-center">Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 font-sans">
                                    <?php foreach ($santri_binaan_detail as $sb): ?>
                                    <?php
                                        // Status Ibadah Badge
                                        $ib_score = $sb['rata_ibadah'];
                                        $ib_badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                        if ($ib_score >= 85) $ib_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        elseif ($ib_score >= 70) $ib_badge = 'bg-amber-50 text-amber-700 border-amber-200';

                                        // Status Hafalan Badge
                                        $haf_cnt = $sb['total_setoran'];
                                        $haf_badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                        if ($haf_cnt >= 4) $haf_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        elseif ($haf_cnt >= 2) $haf_badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                    ?>
                                    <tr class="hover:bg-amber-50/30 transition">
                                        <td class="py-3 px-3 whitespace-nowrap font-bold text-slate-900">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs shadow-xs border border-amber-200 overflow-hidden flex-shrink-0">
                                                    <?php if (!empty($sb['foto'])): ?>
                                                        <img src="../upload/santri/<?= htmlspecialchars($sb['foto']) ?>" alt="" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <?= strtoupper(substr($sb['nama'], 0, 1)) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="text-slate-900 font-bold"><?= htmlspecialchars($sb['nama']) ?></span>
                                                    <span class="block text-[10px] text-slate-400 font-normal">ID: #<?= $sb['id'] ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 whitespace-nowrap text-slate-600">
                                            <span class="font-semibold text-slate-700 block"><?= htmlspecialchars($sb['kamar']) ?></span>
                                            <span class="text-[10px] text-slate-400"><?= htmlspecialchars($sb['jenjang']) ?></span>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="w-36">
                                                <div class="flex items-center justify-between text-[11px] mb-1">
                                                    <span class="font-bold text-slate-700"><?= $sb['rata_ibadah'] ?>%</span>
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded border font-semibold <?= $ib_badge ?>">
                                                        <?= $sb['rata_ibadah'] >= 85 ? 'Mumtaz' : ($sb['rata_ibadah'] >= 70 ? 'Jayyid' : 'Perlu Bimbingan') ?>
                                                    </span>
                                                </div>
                                                <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                                    <div class="bg-emerald-500 h-full rounded-full" style="width: <?= min(100, $sb['rata_ibadah']) ?>%"></div>
                                                </div>
                                                <span class="text-[9px] text-slate-400 block mt-0.5">Shalat 5 Waktu Jamaah: <strong><?= $sb['jamaah_5'] ?> hari</strong></span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="w-44">
                                                <div class="flex items-center justify-between text-[11px] mb-1">
                                                    <span class="font-bold text-slate-800"><?= $sb['total_setoran'] ?>x Setoran</span>
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded border font-semibold <?= $haf_badge ?>">
                                                        <?= $sb['total_setoran'] >= 4 ? 'Target Tercapai' : ($sb['total_setoran'] > 0 ? 'Kurang ' . (4 - $sb['total_setoran']) . 'x' : 'Belum Setor') ?>
                                                    </span>
                                                </div>
                                                <?php if (!empty($sb['last_haf'])): ?>
                                                    <div class="text-[10px] text-slate-600 bg-amber-50/60 px-2 py-1 rounded border border-amber-100">
                                                        <span class="font-bold text-amber-900"><?= htmlspecialchars($sb['last_haf']['nama_surat'] ?? '') ?></span>: Ayat <?= $sb['last_haf']['ayat_mulai'] ?>-<?= $sb['last_haf']['ayat_sampai'] ?> (Juz <?= $sb['last_haf']['juz'] ?>)
                                                        <span class="block text-[9px] text-amber-700 font-medium">Grade: <?= htmlspecialchars($sb['last_haf']['grade'] ?? 'Mutqin') ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-[10px] text-slate-400 italic">Belum ada riwayat setoran</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="text-[11px] space-y-1">
                                                <div>
                                                    <span class="text-slate-500">Kesehatan:</span>
                                                    <?php if ($sb['total_sakit'] > 0): ?>
                                                        <span class="font-bold text-rose-600"><?= $sb['total_sakit'] ?>x Sakit / Izin</span>
                                                    <?php else: ?>
                                                        <span class="font-bold text-emerald-600">Sehat Walafiat</span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($sb['last_mut'])): ?>
                                                    <div class="text-[10px] text-slate-600 truncate max-w-[160px]" title="<?= htmlspecialchars($sb['last_mut']['catatan'] ?? '') ?>">
                                                        <span class="text-slate-500">Mental:</span> <strong class="text-slate-700"><?= htmlspecialchars($sb['last_mut']['kondisi_mental'] ?? 'Stabil') ?></strong>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-center whitespace-nowrap">
                                            <?php if ($sb['total_kontak'] > 0): ?>
                                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <i class="fas fa-check"></i> <?= $sb['total_kontak'] ?>x Dikontak
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                                    <i class="fas fa-clock"></i> Belum Dikontak
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 px-3 text-center whitespace-nowrap">
                                            <?php if (!empty($sb['nomor_wa'])): ?>
                                                <?php 
                                                    $clean_wa = preg_replace('/[^0-9]/', '', $sb['nomor_wa']);
                                                    if (substr($clean_wa, 0, 1) === '0') $clean_wa = '62' . substr($clean_wa, 1);
                                                    $wa_msg = urlencode("Assalamu'alaikum Warahmatullah Bapak/Ibu {$sb['nama_ortu']}, kami dari Musyrif Villa Quran ingin mengabarkan perkembangan ananda {$sb['nama']}. Skor kepatuhan ibadah: {$sb['rata_ibadah']}%, total setoran hafalan: {$sb['total_setoran']}x bulan ini.");
                                                ?>
                                                <a href="https://wa.me/<?= $clean_wa ?>?text=<?= $wa_msg ?>" target="_blank" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[11px] font-bold inline-flex items-center gap-1 shadow-xs transition" title="Kirim Pesan WA ke Orang Tua">
                                                    <i class="fab fa-whatsapp"></i> WA Ortu
                                                </a>
                                            <?php else: ?>
                                                <span class="text-slate-300 text-[10px] italic">No WA -</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-8 text-center text-slate-400 bg-slate-50/50 rounded-xl border border-dashed border-slate-200">
                            <i class="fas fa-user-slash text-3xl mb-2 text-slate-300 block"></i>
                            Musyrif ini belum memiliki santri binaan yang terdaftar di halaqoh asrama.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- ANALISA STRATEGIS AI HRD YAYASAN -->
                <div class="bg-white rounded-2xl shadow-sm border border-amber-200 p-6 mb-8 flex flex-col md:flex-row gap-6 items-start">
                    <div class="w-full md:w-1/3 border-b md:border-b-0 md:border-r border-slate-100 pb-4 md:pb-0 md:pr-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                <i class="fas fa-brain text-amber-700"></i> Laporan Evaluasi Kinerja
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500 mb-4">Laporan evaluasi mendalam tentang kontribusi Musyrif ini beserta rekomendasi pembinaan HRD dan dalil Sharia Islam.</p>
                        
                        <?php if ($is_pimpinan): ?>
                            <div class="space-y-2.5">
                                <button type="button" id="btn-analisa-kpi" class="w-full bg-gradient-to-r from-amber-700 to-amber-800 hover:from-amber-800 hover:to-amber-900 text-white font-bold py-3 px-4 rounded-xl text-xs shadow-md transition inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-magic"></i> <span id="label-btn-analisa"><?= !empty($saved_eval['evaluasi_ai']) ? 'Generate Ulang Evaluasi AI' : 'Evaluasi Kinerja Musyrif' ?></span>
                                </button>
                                
                                <button type="button" id="btn-simpan-evaluasi" class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold py-3 px-4 rounded-xl text-xs shadow-md transition inline-flex items-center justify-center gap-2">
                                    <i class="fas fa-save"></i> <span>Simpan Hasil Evaluasi</span>
                                </button>
                            </div>

                            <div id="eval-status-badge" class="<?= empty($saved_eval['evaluasi_ai']) ? 'hidden' : '' ?> mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-bold flex flex-col gap-0.5">
                                <div class="flex items-center gap-1.5 text-emerald-700">
                                    <i class="fas fa-check-circle"></i> <span id="status-badge-title">Evaluasi Tersimpan</span>
                                </div>
                                <span class="text-[10px] text-emerald-600 font-normal" id="status-badge-meta">
                                    <?= !empty($saved_eval['updated_at']) ? 'Disimpan oleh: ' . htmlspecialchars($saved_eval['saved_by'] ?? 'Pimpinan') . ' (' . date('d/m/Y H:i', strtotime($saved_eval['updated_at'])) . ')' : '' ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="p-3 bg-cyan-50 border border-cyan-200 rounded-xl text-xs text-cyan-900 flex items-start gap-2">
                                <i class="fas fa-info-circle text-cyan-600 mt-0.5"></i>
                                <span>Hasil evaluasi resmi dari Pimpinan Pesantren / Yayasan untuk pembinaan dan peningkatan mutu pendampingan santri.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1 w-full min-h-[150px]">
                        <div id="ai-kpi-result" class="text-xs text-slate-700 markdown-body">
                            <?php if (!empty($saved_eval['evaluasi_ai'])): ?>
                                <!-- Konten akan dirender otomatis via marked.js saat DOM ready -->
                            <?php else: ?>
                                <div class="text-slate-400 italic py-6 text-center">
                                    <i class="fas fa-comment-dots text-3xl mb-2 text-slate-200 block"></i>
                                    <?= $is_pimpinan ? 'Klik tombol <strong>Evaluasi Kinerja Musyrif</strong> di sebelah kiri untuk menghasilkan laporan evaluasi HRD dari AI Gemini, kemudian klik tombol <strong>Simpan</strong> agar tersimpan permanen.' : 'Belum ada catatan evaluasi khusus yang diterbitkan oleh Pimpinan untuk periode bulan ini.' ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div id="ai-kpi-loading" class="hidden text-center text-slate-500 py-6">
                            <i class="fas fa-circle-notch fa-spin text-3xl text-amber-700 mb-2"></i>
                            <span class="text-xs font-bold text-slate-700 block">Menganalisis Data Kontribusi Musyrif...</span>
                            <span class="text-[9px] text-slate-400 block mt-1">Mengintegrasikan 8 parameter dan dalil pembinaan...</span>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-12 text-center text-slate-400">
                    <i class="fas fa-chart-bar text-6xl text-slate-200 mb-4 block"></i>
                    <span class="text-sm font-semibold">Belum Ada Musyrif Terdaftar</span>
                    <p class="text-xs text-slate-400 mt-1">Silakan tambahkan data Musyrif di menu Manajemen Asatidz terlebih dahulu.</p>
                </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <script>
        // Toggle Sidebar Mobile
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        });

        // Toast Notification Function
        function showToast(message, isSuccess = true) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `px-4 py-3 rounded-xl shadow-2xl text-xs font-bold flex items-center gap-2 transform transition-all duration-300 translate-y-4 opacity-0 pointer-events-auto ${isSuccess ? 'bg-emerald-800 text-emerald-100 border border-emerald-600' : 'bg-rose-800 text-rose-100 border border-rose-600'}`;
            toast.innerHTML = `<i class="fas ${isSuccess ? 'fa-check-circle text-emerald-300 text-sm' : 'fa-exclamation-circle text-rose-300 text-sm'}"></i> <div>${message}</div>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-4', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-y-2');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        }

        // Simpan markdown di memory
        let currentEvaluasiMarkdown = <?= json_encode($saved_eval['evaluasi_ai'] ?? '') ?>;

        // Render jika ada evaluasi tersimpan saat pertama kali dibuka
        document.addEventListener('DOMContentLoaded', () => {
            if (currentEvaluasiMarkdown && currentEvaluasiMarkdown.trim() !== '') {
                const resultDiv = document.getElementById('ai-kpi-result');
                if (resultDiv && typeof marked !== 'undefined') {
                    resultDiv.innerHTML = marked.parse(currentEvaluasiMarkdown);
                }
            }
        });

        // Trigger AI Evaluation Generator
        const btnAnalisa = document.getElementById('btn-analisa-kpi');
        const btnSimpan = document.getElementById('btn-simpan-evaluasi');

        if (btnAnalisa) {
            btnAnalisa.addEventListener('click', function() {
                const resultDiv = document.getElementById('ai-kpi-result');
                const loadingDiv = document.getElementById('ai-kpi-loading');
                
                resultDiv.classList.add('hidden');
                loadingDiv.classList.remove('hidden');
                btnAnalisa.disabled = true;

                const name = "<?= addslashes($staf['nama'] ?? '') ?>";
                const kpi = "<?= number_format($total_kpi, 1) ?>";
                const pred = "<?= addslashes($predikat) ?>";
                const monthName = "<?= $months[$selected_month] ?>";
                const year = "<?= $selected_year ?>";
                
                const scores = {
                    "Validasi Ibadah": "<?= number_format($skor_validasi_ibadah, 0) ?>%",
                    "Kontak Walisantri": "<?= number_format($skor_kontak_walisantri, 0) ?>%",
                    "Belajar Mandiri": "<?= number_format($skor_belajar_mandiri, 0) ?>%",
                    "Check Kesehatan": "<?= number_format($skor_kesehatan, 0) ?>%",
                    "Setoran Hafalan": "<?= number_format($skor_setoran_hafalan, 0) ?>%",
                    "Buku Mutabaah": "<?= number_format($skor_mutabaah, 0) ?>%",
                    "Absen Kerja": "<?= number_format($skor_absensi_kerja, 0) ?>%",
                    "Absen Rapat": "<?= number_format($skor_absensi_rapat, 0) ?>%"
                };

                const promptText = `Anda adalah seorang HRD & Legal Konsultan Pesantren Senior dan juga seorang Konselor Syariah.
Tugas Anda adalah menganalisis hasil pencapaian KPI bulanan seorang Musyrif asrama, kemudian memberikan laporan evaluasi kinerja yang seimbang (HRD modern & Sharia Islam).

Berikut profil pencapaian Musyrif:
- Nama: ${name}
- Bulan Penilaian: ${monthName} ${year}
- Skor Akhir KPI: ${kpi} (${pred})

Rincian Nilai 8 Parameter Kepatuhan:
- Validasi Ibadah: ${scores["Validasi Ibadah"]}
- Kontak Walisantri: ${scores["Kontak Walisantri"]}
- Check Belajar Mandiri: ${scores["Belajar Mandiri"]}
- Check Kesehatan: ${scores["Check Kesehatan"]}
- Setoran Hafalan: ${scores["Setoran Hafalan"]}
- Buku Mutabaah (Kondisi Mental): ${scores["Buku Mutabaah"]}
- Absensi Jam Kerja: ${scores["Absen Kerja"]}
- Absensi Rapat: ${scores["Absen Rapat"]}

Berikan laporan evaluasi terstruktur dalam format Markdown yang premium:
1. **Analisa Kinerja**: Evaluasi singkat mengenai kelebihan dan area kelemahan administrasi yang paling menonjol.
2. **Rekomendasi Tindakan HRD**: Langkah-langkah konkrit dan taktis untuk membimbing Musyrif ini agar kinerjanya meningkat bulan depan.
3. **Perspektif Sharia & Dalil Pembinaan**: Berikan nasihat moral/amanah kepemimpinan (amanah asrama) berbasis ayat Al-Qur'an, Hadits shahih, atau pesan ulama klasik tentang pentingnya tanggung jawab atas santri titipan (ra'iyah).

Gunakan gaya bahasa yang formal, bijak, mendalam, dan inspiratif untuk membantu Yayasan membina asatidz.`;

                // Fetch Gemini
                fetch('../api-gemini.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: promptText })
                })
                .then(res => res.json())
                .then(data => {
                    loadingDiv.classList.add('hidden');
                    resultDiv.classList.remove('hidden');
                    btnAnalisa.disabled = false;
                    
                    if (data.status === 'success') {
                        currentEvaluasiMarkdown = data.result;
                        resultDiv.innerHTML = marked.parse(data.result);
                        if (btnSimpan) {
                            btnSimpan.classList.remove('hidden');
                            btnSimpan.innerHTML = '<i class="fas fa-save"></i> Simpan Hasil Evaluasi';
                        }
                        showToast('Laporan evaluasi berhasil di-generate! Klik tombol Simpan agar tersimpan permanen.', true);
                    } else {
                        resultDiv.innerHTML = `<div class="text-rose-600 bg-rose-50 p-4 border rounded-xl font-bold"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal menganalisis KPI: ${data.message}</div>`;
                    }
                })
                .catch(err => {
                    loadingDiv.classList.add('hidden');
                    resultDiv.classList.remove('hidden');
                    btnAnalisa.disabled = false;
                    resultDiv.innerHTML = `<div class="text-rose-600 bg-rose-50 p-4 border rounded-xl font-bold"><i class="fas fa-exclamation-triangle mr-2"></i>Terjadi kesalahan koneksi: ${err.message}</div>`;
                });
            });
        }

        // Save Evaluation Handler
        if (btnSimpan) {
            btnSimpan.addEventListener('click', function() {
                if (!currentEvaluasiMarkdown || currentEvaluasiMarkdown.trim() === '') {
                    showToast('Silakan klik tombol "Evaluasi Kinerja Musyrif" terlebih dahulu untuk menghasilkan laporan AI sebelum menyimpan.', false);
                    return;
                }

                btnSimpan.disabled = true;
                btnSimpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

                const formData = new URLSearchParams();
                formData.append('action', 'save_evaluasi');
                formData.append('ajax', '1');
                formData.append('musyrif_id', '<?= (int)$selected_musyrif_id ?>');
                formData.append('bulan', '<?= (int)$selected_month ?>');
                formData.append('tahun', '<?= (int)$selected_year ?>');
                formData.append('skor_kpi', '<?= (float)$total_kpi ?>');
                formData.append('predikat', '<?= addslashes($predikat) ?>');
                formData.append('evaluasi_ai', currentEvaluasiMarkdown);

                fetch('kpi-musyrif.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData.toString()
                })
                .then(res => res.json())
                .then(data => {
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = '<i class="fas fa-save"></i> <span>Simpan Hasil Evaluasi</span>';
                    
                    if (data.status === 'success') {
                        showToast(data.message, true);
                        const statusBadge = document.getElementById('eval-status-badge');
                        const statusMeta = document.getElementById('status-badge-meta');
                        if (statusBadge) {
                            statusBadge.classList.remove('hidden');
                            if (statusMeta) {
                                statusMeta.innerText = `Disimpan oleh: ${data.saved_by} (${data.updated_at})`;
                            }
                        }
                        const labelAnalisa = document.getElementById('label-btn-analisa');
                        if (labelAnalisa) {
                            labelAnalisa.innerText = 'Generate Ulang Evaluasi AI';
                        }
                    } else {
                        showToast(data.message, false);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btnSimpan.disabled = false;
                    btnSimpan.innerHTML = '<i class="fas fa-save"></i> <span>Simpan Hasil Evaluasi</span>';
                    showToast('Terjadi kesalahan saat menyimpan evaluasi.', false);
                });
            });
        }

        // Chart Initialization untuk Santri Binaan
        document.addEventListener('DOMContentLoaded', () => {
            const santriNames = <?= json_encode(array_column($santri_binaan_detail, 'nama')) ?>;
            const ibadahScores = <?= json_encode(array_column($santri_binaan_detail, 'rata_ibadah')) ?>;
            const hafalanCounts = <?= json_encode(array_column($santri_binaan_detail, 'total_setoran')) ?>;

            if (document.getElementById('chartIbadahSantri') && santriNames.length > 0) {
                new Chart(document.getElementById('chartIbadahSantri'), {
                    type: 'bar',
                    data: {
                        labels: santriNames.map(n => n.length > 12 ? n.substring(0, 10) + '...' : n),
                        datasets: [{
                            label: 'Kepatuhan Ibadah (%)',
                            data: ibadahScores,
                            backgroundColor: 'rgba(16, 185, 129, 0.75)',
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1.5,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
                            x: { ticks: { font: { size: 9 } } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            if (document.getElementById('chartHafalanSantri') && santriNames.length > 0) {
                new Chart(document.getElementById('chartHafalanSantri'), {
                    type: 'bar',
                    data: {
                        labels: santriNames.map(n => n.length > 12 ? n.substring(0, 10) + '...' : n),
                        datasets: [{
                            label: 'Jumlah Setoran (Kali)',
                            data: hafalanCounts,
                            backgroundColor: 'rgba(245, 158, 11, 0.75)',
                            borderColor: 'rgb(245, 158, 11)',
                            borderWidth: 1.5,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { min: 0, ticks: { stepSize: 1 } },
                            x: { ticks: { font: { size: 9 } } }
                        },
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
