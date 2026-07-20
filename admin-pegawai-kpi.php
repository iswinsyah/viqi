<?php
// Pastikan bersih dari form gaji
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'kpi_ustadz';

// Mengambil ID Ustadz yang sedang login
$user_id = $_SESSION['ustadz_id'] ?? 1; 

// --- AMBIL PENGATURAN GAJI DARI YAYASAN ---
$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji ? $res_gaji->fetch_assoc() : null;

// Ambil settingan gaji per pertemuan berdasarkan Grade yang sudah dipindah ke Yayasan
$tarif_grade_c = $data_gaji['gaji_grade_c'] ?? 20000;
$tarif_grade_b = $data_gaji['gaji_grade_b'] ?? 22500;
$tarif_grade_a = $data_gaji['gaji_grade_a'] ?? 25000;

// --- LOGIC PERHITUNGAN KPI (REAL & PERAN SPESIFIK) ---

// Ambil data detail akun ustadz/pegawai yang login untuk mendapatkan rolenya
$res_user = $conn->query("SELECT role FROM akun_ustadz WHERE id = $user_id");
$user_data = $res_user ? $res_user->fetch_assoc() : null;
$user_roles = isset($user_data['role']) ? explode(',', $user_data['role']) : [];

$is_teacher = in_array('ustadz', $user_roles) || in_array('guru', $user_roles);
$eligible_roles_pegawai = ['super_admin', 'kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'musyrif'];
$is_daily_worker = !empty(array_intersect($eligible_roles_pegawai, $user_roles));

// Ambil data jurnal bulan ini untuk efisiensi query Pilar 1 dan Perhitungan Gaji
$res_jurnal_kpi = $conn->query("SELECT 
    COUNT(*) as total_jurnal, 
    SUM(CASE WHEN DATE(created_at) = tanggal THEN 1 ELSE 0 END) as tepat_waktu 
    FROM jurnal_mengajar 
    WHERE ustadz_id = $user_id AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
$data_jurnal_kpi = $res_jurnal_kpi ? $res_jurnal_kpi->fetch_assoc() : ['total_jurnal' => 0, 'tepat_waktu' => 0];
$jumlah_pertemuan = (int)($data_jurnal_kpi['total_jurnal'] ?? 0);
$tepat_waktu = (int)($data_jurnal_kpi['tepat_waktu'] ?? 0);

// 1. Administrasi & Disiplin (Bobot 20%)
if ($is_teacher) {
    $skor_jurnal = $jumlah_pertemuan > 0 ? ($tepat_waktu / $jumlah_pertemuan) * 100 : 100; // 100 jika tidak ada kelas mengajar terjadwal
} else {
    $skor_jurnal = 100; // Non-guru otomatis mendapatkan nilai 100 untuk jurnal
}

// Hitung kehadiran harian dari tabel absensi_pegawai (Pegawai & Harian)
$res_hadir = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen IN ('Pegawai', 'Harian') AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE())");
$jml_hadir = $res_hadir ? (int)($res_hadir->fetch_assoc()['jml'] ?? 0) : 0;

if ($is_daily_worker) {
    $skor_kehadiran = $jml_hadir > 0 ? min(100, ($jml_hadir / 20) * 100) : 0; // Asumsi 20 hari kerja sebulan
} else {
    // Jika bukan pekerja harian (ustadz honorer saja), hitung kehadiran mengajarnya dibanding jadwal mengajar bulanan
    $res_hadir_mengajar = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen = 'Mengajar' AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE())");
    $jml_hadir_mengajar = $res_hadir_mengajar ? (int)($res_hadir_mengajar->fetch_assoc()['jml'] ?? 0) : 0;
    
    $res_total_teaching_days = $conn->query("SELECT COUNT(DISTINCT tanggal) as total_days FROM jurnal_mengajar WHERE ustadz_id = $user_id AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
    $total_teaching_days = $res_total_teaching_days ? (int)($res_total_teaching_days->fetch_assoc()['total_days'] ?? 0) : 0;
    
    $skor_kehadiran = $total_teaching_days > 0 ? min(100, ($jml_hadir_mengajar / $total_teaching_days) * 100) : 100;
}

// Hitung kehadiran rapat dari tabel absensi_pegawai dan jadwal_rapat (peserta terundang)
$res_rapat_attended = $conn->query("SELECT COUNT(DISTINCT rapat_id) as jml FROM absensi_pegawai WHERE ustadz_id = $user_id AND jenis_absen = 'Rapat' AND status_kehadiran IN ('Masuk', 'Pulang', 'Hadir') AND MONTH(waktu_absen) = MONTH(CURRENT_DATE()) AND YEAR(waktu_absen) = YEAR(CURRENT_DATE())");
$jml_rapat = $res_rapat_attended ? (int)($res_rapat_attended->fetch_assoc()['jml'] ?? 0) : 0;

$res_all_rapat_month = $conn->query("SELECT * FROM jadwal_rapat WHERE MONTH(waktu_mulai) = MONTH(CURRENT_DATE()) AND YEAR(waktu_mulai) = YEAR(CURRENT_DATE())");
$total_rapat_invited = 0;
if ($res_all_rapat_month && $res_all_rapat_month->num_rows > 0) {
    while ($r = $res_all_rapat_month->fetch_assoc()) {
        $p_json = $r['peserta_terundang'] ?? null;
        $is_inv = false;
        if (in_array('super_admin', $user_roles)) {
            $is_inv = true;
        } elseif (!empty($p_json)) {
            $tg = json_decode($p_json, true);
            $t_r = $tg['roles'] ?? [];
            $t_i = array_map('intval', $tg['ids'] ?? []);
            if (in_array((int)$user_id, $t_i) || in_array('semua_pegawai', $t_r)) {
                $is_inv = true;
            } else {
                foreach ($t_r as $tr) {
                    if ($tr === 'musyrif' && ($is_daily_worker || in_array('musyrif', $user_roles))) { $is_inv = true; break; }
                    if ($tr === 'admin_sekolah' && in_array('admin_sekolah', $user_roles)) { $is_inv = true; break; }
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

// 2. Kualitas Pengajaran (Bobot 40%)
$res_ai = $conn->query("SELECT COUNT(*) as pemakaian FROM log_aktivitas_ai WHERE user_id = $user_id AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$jumlah_pakai_ai = $res_ai ? (int)($res_ai->fetch_assoc()['pemakaian'] ?? 0) : 0;
$skor_penggunaan_ai = $jumlah_pakai_ai >= 5 ? 100 : ($jumlah_pakai_ai > 0 ? 85 : 70);

if ($is_teacher) {
    $res_sup = $conn->query("SELECT skor FROM supervisi_mengajar WHERE user_id = $user_id ORDER BY tanggal_supervisi DESC LIMIT 1");
    $skor_supervisi = $res_sup && $res_sup->num_rows > 0 ? (int)($res_sup->fetch_assoc()['skor']) : 85;
} else {
    $skor_supervisi = 100; // Non-guru otomatis mendapatkan nilai 100
}

$skor_kualitas_pengajaran = (($skor_penggunaan_ai * 0.4) + ($skor_supervisi * 0.6));

// 3. Capaian Santri (Bobot 30%)
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

// 4. Pengembangan Diri (Bobot 10%)
$skor_kontribusi_silabus = $jumlah_pakai_ai > 0 ? 100 : 70;
$skor_pengembangan_diri = $skor_kontribusi_silabus;

// Total Skor KPI
$total_skor_kpi = ($skor_administrasi * 0.20) + ($skor_kualitas_pengajaran * 0.40) + ($skor_capaian_santri * 0.30) + ($skor_pengembangan_diri * 0.10);

// Variabel Penampung Gaji Final
$gaji_per_pertemuan = 0;

if ($total_skor_kpi >= 90) { // Grade A: 90 sd 100
    $gaji_per_pertemuan = $tarif_grade_a;
    $predikat = "Mumtaz (Grade A)";
    $pesan_evaluasi = "Alhamdulillah, jazakumullah khairan atas dedikasi Antum! Performa bulan ini sangat luar biasa. Pertahankan kedisiplinan administrasi dan inovasi mengajar Antum.";
    $ikon_evaluasi = "fa-star text-amber-400";
} elseif ($total_skor_kpi >= 80) { // Grade B: 80 sd 89
    $gaji_per_pertemuan = $tarif_grade_b;
    $predikat = "Jayid (Grade B)";
    $pesan_evaluasi = "Performa Antum sudah baik, namun masih ada ruang untuk ditingkatkan. Mari fokus pada perbaikan kualitas pengajaran dan pendampingan santri di bulan depan.";
    $ikon_evaluasi = "fa-thumbs-up text-blue-500";
} else { // Grade C: di bawah 80
    $gaji_per_pertemuan = $tarif_grade_c;
    $predikat = "Aslha (Grade C)";
    $pesan_evaluasi = "Performa Antum bulan ini berada di bawah target yang diharapkan. Kami mohon kerjasamanya untuk lebih disiplin dalam mengisi jurnal dan mengawal target hafalan santri.";
    $ikon_evaluasi = "fa-exclamation-triangle text-rose-500";
}

$gaji_total = $gaji_per_pertemuan * $jumlah_pertemuan;

// --- AMBIL REKAMAN KEHADIRAN TERAKHIR ---
$res_riwayat = $conn->query("
    SELECT waktu_absen, jenis_absen, status_kehadiran, keterangan 
    FROM absensi_pegawai 
    WHERE ustadz_id = $user_id 
    ORDER BY waktu_absen DESC 
    LIMIT 30
");
$riwayat_kehadiran = [];
if ($res_riwayat) {
    while ($row = $res_riwayat->fetch_assoc()) {
        $riwayat_kehadiran[] = $row;
    }
}

// Cek status SP pegawai di semester ini (SP-1, SP-2, BLOKIR)
$cur_m = (int)date('m');
$cur_y = (int)date('Y');
$sem_kpi = ($cur_m >= 7) ? "$cur_y/" . ($cur_y+1) . "-Ganjil" : ($cur_y-1) . "/$cur_y-Genap";

$res_sp_kpi = $conn->query("SELECT * FROM surat_peringatan_pegawai WHERE ustadz_id = $user_id AND semester = '$sem_kpi' ORDER BY id DESC LIMIT 1");
$sp_kpi_latest = ($res_sp_kpi && $res_sp_kpi->num_rows > 0) ? $res_sp_kpi->fetch_assoc() : null;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Pegawai | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chalkboard-teacher text-cyan-600 mr-2"></i>Key Performance Indicator (KPI) Pegawai</h1>
                <select class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium">
                    <option>Periode: Mei 2026</option>
                    <option>Periode: April 2026</option>
                </select>
            </div>

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

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- AREA BERSIH: FORM PENGATURAN GAJI SUDAH DIPINDAHKAN KE RUANG YAYASAN -->
            <!-- WIDGET UTAMA SKOR & INSENTIF -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="md:col-span-1 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-center items-center text-center">
                    <h3 class="font-semibold opacity-80">Total Skor Kinerja Anda</h3>
                    <p class="text-6xl font-bold my-2"><?= number_format($total_skor_kpi, 2) ?></p>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium border border-white/30 mb-3"><?= $predikat ?></span>
                    <div class="w-full h-1 bg-white/30 rounded-full mt-2"><div class="h-1 bg-white rounded-full" style="width: <?= $total_skor_kpi ?>%;"></div></div>
                </div>
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center">
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

            <!-- KOTAK EVALUASI DIRI -->
            <div class="bg-indigo-50 rounded-xl shadow-sm border border-indigo-100 p-6 mb-6 flex items-start">
                <div class="bg-white p-3 rounded-full shadow-sm mr-4 flex-shrink-0">
                    <i class="fas <?= $ikon_evaluasi ?> text-2xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-indigo-900 mb-1">Catatan Evaluasi Kinerja (Auto-Generated)</h3>
                    <p class="text-sm text-indigo-800 leading-relaxed"><?= $pesan_evaluasi ?></p>
                </div>
            </div>

            <!-- DETAIL SKOR PER PILAR -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pilar 1 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-clipboard-check text-blue-500 mr-2"></i> Administrasi (20%)</h4>
                    <p class="text-3xl font-bold text-blue-600 my-3"><?= number_format($skor_administrasi, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Ketepatan Jurnal</span> <span class="font-bold"><?= $skor_jurnal ?></span></li>
                        <li class="flex justify-between"><span>Kehadiran (QR)</span> <span class="font-bold"><?= $skor_kehadiran ?></span></li>
                        <li class="flex justify-between"><span>Kehadiran Rapat</span> <span class="font-bold"><?= $skor_kehadiran_rapat ?></span></li>
                    </ul>
                </div>
                <!-- Pilar 2 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-chalkboard-teacher text-purple-500 mr-2"></i> Kualitas Ajar (40%)</h4>
                    <p class="text-3xl font-bold text-purple-600 my-3"><?= number_format($skor_kualitas_pengajaran, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Inovasi (Pakai AI)</span> <span class="font-bold"><?= $skor_penggunaan_ai ?></span></li>
                        <li class="flex justify-between"><span>Supervisi Kepsek</span> <span class="font-bold"><?= $skor_supervisi ?></span></li>
                    </ul>
                </div>
                <!-- Pilar 3 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-graduation-cap text-emerald-500 mr-2"></i> Capaian Santri (30%)</h4>
                    <p class="text-3xl font-bold text-emerald-600 my-3"><?= number_format($skor_capaian_santri, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Rata-rata Nilai</span> <span class="font-bold"><?= $skor_rata_nilai ?></span></li>
                        <li class="flex justify-between"><span>Pertumbuhan Nilai</span> <span class="font-bold"><?= $skor_pertumbuhan ?></span></li>
                    </ul>
                </div>
                <!-- Pilar 4 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-rocket text-amber-500 mr-2"></i> Pengembangan Diri (10%)</h4>
                    <p class="text-3xl font-bold text-amber-600 my-3"><?= number_format($skor_pengembangan_diri, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Kontribusi Silabus</span> <span class="font-bold"><?= $skor_kontribusi_silabus ?></span></li>
                        <li class="flex justify-between"><span>Upload Sertifikat</span> <span class="font-bold">0</span></li>
                    </ul>
                </div>
            </div>

        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>