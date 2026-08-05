<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Self-healing database tables for Santri Health Checking & Official Sick Permits
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_kesehatan_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    ustadz_id INT NOT NULL,
    tanggal DATE NOT NULL,
    suhu_tubuh DECIMAL(4,1) NULL,
    gejala_keluhan TEXT NOT NULL,
    tindakan_musyrif TEXT NOT NULL,
    status_kesehatan ENUM('Sakit (Istirahat Asrama/UKS)', 'Dirawat (Klinik/RS)', 'Pulang (Izin Ortu)', 'Sehat / Sembuh') DEFAULT 'Sakit (Istirahat Asrama/UKS)',
    minta_izin_sekolah TINYINT(1) DEFAULT 1,
    alasan_izin_sekolah TEXT NULL,
    status_izin_sekolah ENUM('Disetujui Musyrif', 'Ditolak', 'Selesai / Sembuh') DEFAULT 'Disetujui Musyrif',
    foto_bukti_sakit VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_santri (santri_id),
    KEY idx_ustadz (ustadz_id),
    KEY idx_tanggal (tanggal)
)");

// Check user role
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$is_super_admin = ($ustadz_id === 9999);

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_admin_or_kepala = $is_super_admin || !empty(array_intersect($norm_roles, ['super_admin', 'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah']));
$is_ka_rijal = !empty(array_intersect($norm_roles, ['kepala_asrama', 'kepala_asrama_rijal']));
$is_ka_nisa = !empty(array_intersect($norm_roles, ['kepala_asrama_nisa']));

// Fetch santri binaan
$santri_binaan = [];
if ($is_admin_or_kepala) {
    // Admin / Super Admin can see all active santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, kamar_asrama, jenis_kelamin FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} elseif ($is_ka_rijal) {
    // Kepala Asrama Rijal: can see all active male santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, kamar_asrama, jenis_kelamin FROM buku_induk_santri WHERE status_santri = 'Aktif' AND (jenis_kelamin = 'Laki-laki' OR jenis_kelamin IS NULL) ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} elseif ($is_ka_nisa) {
    // Kepala Asrama Nisa: can see all active female santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, kamar_asrama, jenis_kelamin FROM buku_induk_santri WHERE status_santri = 'Aktif' AND jenis_kelamin = 'Perempuan' ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} else {
    // Musyrif / Musyrifah: see only their assigned halaqoh group members
    $res_sb = $conn->query("
        SELECT DISTINCT s.id, s.nama_lengkap, s.nis, s.kelas_sekarang, s.kamar_asrama, s.jenis_kelamin 
        FROM buku_induk_santri s 
        JOIN halaqoh_anggota a ON s.id = a.santri_id 
        JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE g.musyrif_id = $ustadz_id AND s.status_santri = 'Aktif'
        ORDER BY s.nama_lengkap ASC
    ");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
}

$pesan_sukses = '';
$pesan_error = '';

// Handle Form Submission (Input Laporan Kesehatan & Surat Izin Sakit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_laporan_kesehatan'])) {
    $santri_id = (int)$_POST['santri_id'];
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $suhu_tubuh = !empty($_POST['suhu_tubuh']) ? (float)$_POST['suhu_tubuh'] : "NULL";
    $gejala_keluhan = $conn->real_escape_string($_POST['gejala_keluhan']);
    $tindakan_musyrif = $conn->real_escape_string($_POST['tindakan_musyrif']);
    $status_kesehatan = $conn->real_escape_string($_POST['status_kesehatan']);
    $minta_izin_sekolah = isset($_POST['minta_izin_sekolah']) ? 1 : 0;
    $alasan_izin_sekolah = $conn->real_escape_string($_POST['alasan_izin_sekolah'] ?? '');
    $foto_bukti_sakit = $conn->real_escape_string($_POST['foto_bukti_sakit'] ?? '');

    if ($santri_id <= 0 || empty($tanggal) || empty($gejala_keluhan) || empty($tindakan_musyrif)) {
        $pesan_error = "Nama Santri, Tanggal, Gejala Keluhan, dan Tindakan Penanganan Musyrif wajib diisi!";
    } else {
        $sql = "INSERT INTO jurnal_kesehatan_santri (santri_id, ustadz_id, tanggal, suhu_tubuh, gejala_keluhan, tindakan_musyrif, status_kesehatan, minta_izin_sekolah, alasan_izin_sekolah, status_izin_sekolah, foto_bukti_sakit)
                VALUES ($santri_id, $ustadz_id, '$tanggal', $suhu_tubuh, '$gejala_keluhan', '$tindakan_musyrif', '$status_kesehatan', $minta_izin_sekolah, '$alasan_izin_sekolah', 'Disetujui Musyrif', '$foto_bukti_sakit')";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Laporan Cek Kesehatan & Pengajuan Surat Izin Sakit Resmi berhasil diterbitkan oleh Musyrif!";
        } else {
            $pesan_error = "Gagal menyimpan laporan: " . $conn->error;
        }
    }
}

// Handle Update Status Sembuh
if (isset($_GET['action']) && $_GET['action'] === 'sembuh' && isset($_GET['id'])) {
    $hid = (int)$_GET['id'];
    $conn->query("UPDATE jurnal_kesehatan_santri SET status_kesehatan = 'Sehat / Sembuh', status_izin_sekolah = 'Selesai / Sembuh' WHERE id = $hid");
    $pesan_sukses = "Status kesehatan santri berhasil diperbarui menjadi Sehat / Sembuh!";
}

// Mode Cetak Surat Izin Sakit
$view_surat_id = isset($_GET['print_surat_id']) ? (int)$_GET['print_surat_id'] : 0;
$surat_data = null;
if ($view_surat_id > 0) {
    $res_surat = $conn->query("
        SELECT k.*, s.nama_lengkap, s.nis, s.kelas_sekarang, s.kamar_asrama, s.jenis_kelamin, u.nama as nama_musyrif 
        FROM jurnal_kesehatan_santri k 
        JOIN buku_induk_santri s ON k.santri_id = s.id 
        LEFT JOIN akun_ustadz u ON k.ustadz_id = u.id 
        WHERE k.id = $view_surat_id
    ");
    if ($res_surat && $res_surat->num_rows > 0) {
        $surat_data = $res_surat->fetch_assoc();
    }
}

// Fetch History & Active Sick Logs
$where_hist = $is_admin_or_kepala ? "1=1" : "k.ustadz_id = $ustadz_id";
$res_logs = $conn->query("
    SELECT k.*, s.nama_lengkap, s.nis, s.kelas_sekarang, s.kamar_asrama, s.jenis_kelamin, u.nama as nama_musyrif 
    FROM jurnal_kesehatan_santri k 
    JOIN buku_induk_santri s ON k.santri_id = s.id 
    LEFT JOIN akun_ustadz u ON k.ustadz_id = u.id 
    WHERE $where_hist 
    ORDER BY k.tanggal DESC, k.created_at DESC 
    LIMIT 50
");
$logs_kesehatan = [];
if ($res_logs) {
    while ($r = $res_logs->fetch_assoc()) $logs_kesehatan[] = $r;
}

$active_menu = 'cek_kesehatan_santri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cek Kesehatan Santri & Surat Izin Sakit | Ruang Musyrif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; p: 0 !important; }
            .print-area { border: none !important; shadow: none !important; p: 0 !important; width: 100% !important; }
            @page { size: A4; margin: 15mm; }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR -->
    <div class="no-print">
        <?php include 'sidebar-hr.php'; ?>
    </div>

    <!-- MAIN BODY -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-rose-100 text-rose-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-notes-medical mr-1"></i>Kesehatan Santri
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-rose-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <?php if ($surat_data): ?>
                
                <!-- SURAT IZIN SAKIT RESMI PRINTABLE SHEET -->
                <div class="mb-4 no-print flex justify-between items-center max-w-3xl mx-auto">
                    <a href="admin-cek-kesehatan-santri.php" class="bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-bold px-4 py-2 rounded-xl transition flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i> Kembali ke Form Cek Kesehatan
                    </a>
                    <button onclick="window.print()" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-md transition flex items-center gap-2">
                        <i class="fas fa-print"></i> Cetak Surat Izin Sakit PDF (A4)
                    </button>
                </div>

                <div class="print-area bg-white p-8 sm:p-12 rounded-2xl border border-slate-300 shadow-xl max-w-3xl mx-auto text-slate-900 text-xs sm:text-sm leading-relaxed">
                    <!-- KOP SURAT -->
                    <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
                        <h2 class="font-extrabold text-base sm:text-lg uppercase tracking-wider text-slate-900">PONDOK PESANTREN & SATUAN PENDIDIKAN (PKBM)</h2>
                        <h1 class="text-xl sm:text-2xl font-black uppercase text-rose-800 tracking-tight my-0.5">VILLA QUR'AN INDONESIA</h1>
                        <p class="text-xs text-slate-600 italic">Pengawasan Kesehatan Asrama & Verifikasi Kehadiran Sekolah Resmi</p>
                        <div class="mt-3 inline-block bg-rose-900 text-white font-extrabold px-5 py-1 rounded-full text-xs uppercase tracking-widest">
                            SURAT KETERANGAN IZIN SAKIT (DITERBITKAN MUSYRIF PEMBINA)
                        </div>
                    </div>

                    <div class="mb-6 space-y-4">
                        <p class="text-right text-xs">Bogor, <?= date('d F Y', strtotime($surat_data['tanggal'])) ?></p>
                        
                        <div>
                            <p class="font-bold">Kepada Yth.</p>
                            <p class="font-bold text-slate-800">Bapak/Ibu Wali Kelas & Guru Pengajar</p>
                            <p class="text-slate-600">PKBM Villa Qur'an Indonesia</p>
                        </div>

                        <p class="leading-relaxed">Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>

                        <p class="leading-relaxed">
                            Dengan ini kami sampaikan selaku Musyrif/Musyrifah Pembina Asrama, bahwa berdasarkan hasil pengecekan kesehatan dan verifikasi langsung di lokasi asrama, santri di bawah ini:
                        </p>

                        <!-- DATA SANTRI -->
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs space-y-2 my-2">
                            <div class="flex"><span class="w-36 font-bold text-slate-600">Nama Santri:</span> <span class="font-black text-slate-900 uppercase"><?= htmlspecialchars($surat_data['nama_lengkap']) ?></span></div>
                            <div class="flex"><span class="w-36 font-bold text-slate-600">NIS / Kelas:</span> <span class="font-bold"><?= htmlspecialchars($surat_data['nis'] ?? '-') ?> / <?= htmlspecialchars($surat_data['kelas_sekarang']) ?></span></div>
                            <div class="flex"><span class="w-36 font-bold text-slate-600">Kamar / Asrama:</span> <span class="font-bold"><?= htmlspecialchars($surat_data['kamar_asrama'] ?? 'Asrama') ?></span></div>
                            <div class="flex"><span class="w-36 font-bold text-slate-600">Suhu Tubuh:</span> <span class="font-extrabold text-rose-700"><?= !empty($surat_data['suhu_tubuh']) ? $surat_data['suhu_tubuh'] . ' °C' : 'Normal / Demam Ringan' ?></span></div>
                            <div class="flex"><span class="w-36 font-bold text-slate-600">Gejala & Keluhan Sakit:</span> <span class="font-bold text-slate-800"><?= htmlspecialchars($surat_data['gejala_keluhan']) ?></span></div>
                            <div class="flex"><span class="w-36 font-bold text-slate-600">Tindakan Penanganan:</span> <span class="font-semibold text-slate-800"><?= htmlspecialchars($surat_data['tindakan_musyrif']) ?></span></div>
                        </div>

                        <p class="leading-relaxed">
                            Menyatakan bahwa santri yang bersangkutan **BENAR-BENAR SAKIT** dan tidak memungkinkan untuk mengikuti kegiatan belajar mengajar di sekolah pada hari ini. Musyrif Pembina memohon izin agar santri yang bersangkutan diberikan dispensasi tidak masuk sekolah untuk menjalani penanganan medis & istirahat di UKS/Asrama.
                        </p>

                        <p class="leading-relaxed">
                            Demikian surat permohonan izin sakit ini diterbitkan secara sah oleh Musyrif Pembina untuk mencegah klaim sakit palsu. Atas perhatian dan kerja samanya kami ucapkan terima kasih.
                        </p>

                        <p class="leading-relaxed">Wassalamu'alaikum Warahmatullahi Wabarakatuh.</p>
                    </div>

                    <!-- SIGNATURE BLOCK -->
                    <div class="pt-6 border-t border-slate-300 grid grid-cols-2 gap-6 text-center text-xs">
                        <div>
                            <p class="font-bold text-slate-700">Musyrif Pembina (Yang Memeriksa),</p>
                            <div class="h-20"></div>
                            <p class="font-bold underline uppercase">( <?= htmlspecialchars($surat_data['nama_musyrif'] ?? $_SESSION['ustadz_nama']) ?> )</p>
                        </div>
                        <div>
                            <p class="font-bold text-slate-700">Mengetahui Kepala Asrama / Ma'had,</p>
                            <div class="h-20"></div>
                            <p class="font-bold underline uppercase">( Pengurus Asrama VQI )</p>
                        </div>
                    </div>
                </div>

            <?php else: ?>

                <!-- HEADER DASHBOARD -->
                <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-500 to-red-600 flex items-center justify-center text-white shadow-md shadow-rose-200">
                                <i class="fas fa-heartbeat text-lg"></i>
                            </div>
                            <span>Cek Kesehatan Santri & Surat Izin Sakit Resmi</span>
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-1">Verifikasi kondisi fisik santri binaan dan penerbitan surat izin sakit resmi ke sekolah oleh Musyrif Pembina</p>
                    </div>
                </div>

                <?= $pesan_sukses ? "<div class='no-print bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-check-circle mr-2 text-emerald-500'></i>$pesan_sukses</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>
                <?= $pesan_error ? "<div class='no-print bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-exclamation-triangle mr-2 text-rose-500'></i>$pesan_error</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>

                <!-- FORM INPUT CHEKING KESEHATAN -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8 no-print">
                    <div class="px-6 py-4 bg-gradient-to-r from-rose-50 to-pink-50 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                            <i class="fas fa-user-nurse text-rose-600"></i>
                            <span>Form Pemeriksaan Kesehatan Santri & Penerbitan Izin Sakit</span>
                        </h2>
                        <span class="text-xs font-bold text-rose-800 bg-rose-100 px-3 py-1 rounded-full">
                            Total Binaanku: <?= count($santri_binaan) ?> Santri
                        </span>
                    </div>

                    <form method="POST" class="p-6 space-y-6">
                        <input type="hidden" name="simpan_laporan_kesehatan" value="1">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Pilih Santri Binaan yang Sakit <span class="text-rose-600">*</span></label>
                                <select name="santri_id" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-bold text-slate-900 focus:ring-2 focus:ring-rose-500">
                                    <option value="">-- Pilih Santri --</option>
                                    <?php foreach ($santri_binaan as $st): ?>
                                        <option value="<?= $st['id'] ?>">
                                            <?= htmlspecialchars($st['nama_lengkap']) ?> (Kelas: <?= htmlspecialchars($st['kelas_sekarang']) ?> | Asrama: <?= htmlspecialchars($st['kamar_asrama'] ?? '-') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tanggal Pemeriksaan <span class="text-rose-600">*</span></label>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white">
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Suhu Tubuh (°C)</label>
                                <input type="number" step="0.1" name="suhu_tubuh" placeholder="misal: 38.5" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-mono font-bold bg-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Gejala & Keluhan Fisik Santri <span class="text-rose-600">*</span></label>
                                <textarea name="gejala_keluhan" rows="3" required placeholder="misal: Demam tinggi 38.5°C, pusing, batuk berdahak sejak tadi malam." class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white leading-relaxed"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tindakan Penanganan Musyrif <span class="text-rose-600">*</span></label>
                                <textarea name="tindakan_musyrif" rows="3" required placeholder="misal: Diberikan obat Paracetamol & sirup batuk, dipindahkan ke ruang UKS untuk istirahat total, dipantau Musyrif." class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white leading-relaxed"></textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center bg-rose-50/50 p-4 rounded-xl border border-rose-200">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-rose-900 mb-1">Status Kesehatan Santri</label>
                                <select name="status_kesehatan" class="w-full px-3 py-2 border border-rose-300 rounded-xl text-xs font-bold bg-white text-rose-950">
                                    <option value="Sakit (Istirahat Asrama/UKS)">🤒 Sakit (Istirahat di UKS / Kamar Asrama)</option>
                                    <option value="Dirawat (Klinik/RS)">🏥 Dirawat (Rujuk ke Klinik / Rumah Sakit)</option>
                                    <option value="Pulang (Izin Ortu)">🏡 Pulang ke Rumah (Izin Penjemputan Ortu)</option>
                                </select>
                            </div>

                            <div class="pt-2 sm:pt-0">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="minta_izin_sekolah" value="1" checked class="w-4 h-4 text-rose-600 border-slate-300 rounded focus:ring-rose-500">
                                    <span class="ml-2 text-xs font-extrabold text-slate-800">Terbitkan Surat Izin Tidak Masuk Sekolah Resmi (Disetujui Musyrif)</span>
                                </label>
                                <p class="text-[11px] text-slate-500 mt-1">Fitur ini mencegah santri berpura-pura sakit untuk membolos sekolah.</p>
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white font-extrabold px-6 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-2 ml-auto">
                                <i class="fas fa-paper-plane"></i> Simpan & Terbitkan Surat Izin Sakit
                            </button>
                        </div>
                    </form>
                </div>

                <!-- RIWAYAT CEK KESEHATAN SANTRI -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden no-print">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                        <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                            <i class="fas fa-list-alt text-rose-600"></i>
                            <span>Daftar Santri Sakit & Penanganan Musyrif</span>
                        </h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 uppercase text-[11px] font-extrabold tracking-wider">
                                    <th class="py-3.5 px-6">Tanggal & Santri</th>
                                    <th class="py-3.5 px-6">Gejala & Suhu</th>
                                    <th class="py-3.5 px-6">Tindakan Musyrif</th>
                                    <th class="py-3.5 px-6 text-center">Status Kesehatan</th>
                                    <th class="py-3.5 px-6 text-center">Surat Izin Sakit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                                <?php if (!empty($logs_kesehatan)): ?>
                                    <?php foreach ($logs_kesehatan as $lk): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3.5 px-6 align-top">
                                                <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($lk['nama_lengkap']) ?></div>
                                                <div class="text-xs text-slate-500 font-semibold mt-0.5">Kelas: <?= htmlspecialchars($lk['kelas_sekarang']) ?> | Tgl: <?= date('d F Y', strtotime($lk['tanggal'])) ?></div>
                                            </td>
                                            <td class="py-3.5 px-6 align-top">
                                                <div class="font-bold text-slate-800"><?= htmlspecialchars($lk['gejala_keluhan']) ?></div>
                                                <?php if (!empty($lk['suhu_tubuh'])): ?>
                                                    <span class="inline-block bg-rose-100 text-rose-800 text-[11px] px-2 py-0.5 rounded font-extrabold mt-1">Suhu: <?= $lk['suhu_tubuh'] ?> °C</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-6 align-top text-xs text-slate-700">
                                                <?= htmlspecialchars($lk['tindakan_musyrif']) ?>
                                            </td>
                                            <td class="py-3.5 px-6 align-top text-center">
                                                <?php if ($lk['status_kesehatan'] === 'Sehat / Sembuh'): ?>
                                                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-xs font-bold">
                                                        <i class="fas fa-check-circle mr-1"></i>Sembuh
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-rose-100 text-rose-800 px-2.5 py-1 rounded-full text-xs font-extrabold inline-block mb-1">
                                                        <?= htmlspecialchars($lk['status_kesehatan']) ?>
                                                    </span>
                                                    <div>
                                                        <a href="?action=sembuh&id=<?= $lk['id'] ?>" onclick="return confirm('Tandai santri ini sudah sehat/sembuh?')" class="text-[11px] text-emerald-700 underline font-bold hover:text-emerald-900">
                                                            Set Sembuh
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-6 align-top text-center">
                                                <a href="?print_surat_id=<?= $lk['id'] ?>" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs shadow-sm transition inline-flex items-center gap-1">
                                                    <i class="fas fa-print"></i> Cetak Surat Izin
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada catatan kesehatan santri sakit.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
