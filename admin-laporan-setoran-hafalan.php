<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php'; // DB connection

// Allow musyrif, musyrifah, kepala_asrama and super_admin role
$user_roles = [];
if (isset($_SESSION['ustadz_role'])) {
    $user_roles = explode(',', $_SESSION['ustadz_role']);
}
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_admin_or_kepala = ($ustadz_id === 9999) || !empty(array_intersect($norm_roles, ['super_admin', 'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah']));
$is_ka_rijal = !empty(array_intersect($norm_roles, ['kepala_asrama', 'kepala_asrama_rijal']));
$is_ka_nisa = !empty(array_intersect($norm_roles, ['kepala_asrama_nisa']));
$is_musyrif = in_array('musyrif', $norm_roles);
$is_musyrifah = in_array('musyrifah', $norm_roles);

$is_authorized = $is_admin_or_kepala || $is_ka_rijal || $is_ka_nisa || $is_musyrif || $is_musyrifah;

if (!$is_authorized) {
    die('Akses ditolak. Hanya Musyrif/Musyrifah, Pengurus Asrama, dan Admin/Yayasan yang dapat mengakses halaman ini.');
}

// Create table if not exists (Self-Healing)
$create_sql = "CREATE TABLE IF NOT EXISTS laporan_setoran_hafalan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NULL,
    nama_santri VARCHAR(150) NOT NULL,
    nama_surat VARCHAR(150) NOT NULL,
    ayat_mulai INT NOT NULL,
    ayat_sampai INT NOT NULL,
    halaman VARCHAR(50),
    juz INT,
    grade VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($create_sql);
@$conn->query("ALTER TABLE laporan_setoran_hafalan ADD COLUMN santri_id INT NULL AFTER id");

// Hapus Data
if (isset($_GET['action']) && $_GET['action'] === 'hapus' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    $conn->query("DELETE FROM laporan_setoran_hafalan WHERE id = $id_hapus");
    header("Location: admin-laporan-setoran-hafalan.php?status=deleted");
    exit;
}

$message = '';
if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
    $message = '<div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div class="flex items-center"><i class="fas fa-trash-alt mr-3 text-lg text-rose-500"></i><span>Data setoran hafalan berhasil dihapus.</span></div><button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600"><i class="fas fa-times"></i></button></div>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $nama_santri = $conn->real_escape_string($_POST['nama_santri'] ?? '');
    $nama_surat = $conn->real_escape_string($_POST['nama_surat'] ?? '');
    $ayat_mulai = (int)($_POST['ayat_mulai'] ?? 0);
    $ayat_sampai = (int)($_POST['ayat_sampai'] ?? 0);
    $halaman = $conn->real_escape_string($_POST['halaman'] ?? '');
    $juz = (int)($_POST['juz'] ?? 0);
    $grade = $conn->real_escape_string($_POST['grade'] ?? '');

    // Lookup santri_id from buku_induk_santri
    $res_sid = $conn->query("SELECT id FROM buku_induk_santri WHERE nama_lengkap = '$nama_santri' LIMIT 1");
    $santri_id_val = ($res_sid && $res_sid->num_rows > 0) ? (int)$res_sid->fetch_assoc()['id'] : "NULL";

    $sql = "INSERT INTO laporan_setoran_hafalan (santri_id, nama_santri, nama_surat, ayat_mulai, ayat_sampai, halaman, juz, grade)
            VALUES ($santri_id_val, '$nama_santri', '$nama_surat', $ayat_mulai, $ayat_sampai, '$halaman', $juz, '$grade')";
    if ($conn->query($sql) === TRUE) {
        $message = '<div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div class="flex items-center"><i class="fas fa-check-circle mr-3 text-lg text-emerald-500"></i><span>Laporan setoran hafalan <b>' . htmlspecialchars($nama_santri) . '</b> berhasil disimpan!</span></div><button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button></div>';
    } else {
        $message = '<div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div class="flex items-center"><i class="fas fa-exclamation-triangle mr-3 text-lg text-rose-500"></i><span>Gagal menyimpan data: ' . htmlspecialchars($conn->error) . '</span></div><button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600"><i class="fas fa-times"></i></button></div>';
    }
}

// Fetch stats
$total_setoran = 0;
$total_today = 0;
$total_mumtaz = 0;

$res_stat1 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan");
if ($res_stat1) $total_setoran = $res_stat1->fetch_assoc()['total'];

$res_stat2 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan WHERE DATE(created_at) = CURDATE()");
if ($res_stat2) $total_today = $res_stat2->fetch_assoc()['total'];

$res_stat3 = $conn->query("SELECT COUNT(*) as total FROM laporan_setoran_hafalan WHERE grade LIKE '%Mutqin%'");
if ($res_stat3) $total_mumtaz = $res_stat3->fetch_assoc()['total'];

// FETCH SANTRI BINAAN MUSYRIF FROM MANAJEMEN HALAQOH
$santri_binaan = [];
$nama_halaqoh_user = [];

if ($is_admin_or_kepala) {
    // Admin / Super Admin can see all active santri
    $res_santri = $conn->query("SELECT s.id, s.nama_lengkap, g.nama_grup 
        FROM buku_induk_santri s 
        LEFT JOIN halaqoh_anggota a ON s.id = a.santri_id 
        LEFT JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE s.status_santri = 'Aktif'
        ORDER BY s.nama_lengkap ASC");
    if ($res_santri && $res_santri->num_rows > 0) {
        while ($r = $res_santri->fetch_assoc()) {
            $santri_binaan[] = $r;
        }
    }
} elseif ($is_ka_rijal) {
    // Kepala Asrama Rijal: can see all active male santri
    $res_santri = $conn->query("SELECT s.id, s.nama_lengkap, g.nama_grup 
        FROM buku_induk_santri s 
        LEFT JOIN halaqoh_anggota a ON s.id = a.santri_id 
        LEFT JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE s.status_santri = 'Aktif' AND (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL)
        ORDER BY s.nama_lengkap ASC");
    if ($res_santri && $res_santri->num_rows > 0) {
        while ($r = $res_santri->fetch_assoc()) {
            $santri_binaan[] = $r;
        }
    }
} elseif ($is_ka_nisa) {
    // Kepala Asrama Nisa: can see all active female santri
    $res_santri = $conn->query("SELECT s.id, s.nama_lengkap, g.nama_grup 
        FROM buku_induk_santri s 
        LEFT JOIN halaqoh_anggota a ON s.id = a.santri_id 
        LEFT JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE s.status_santri = 'Aktif' AND s.jenis_kelamin = 'Perempuan'
        ORDER BY s.nama_lengkap ASC");
    if ($res_santri && $res_santri->num_rows > 0) {
        while ($r = $res_santri->fetch_assoc()) {
            $santri_binaan[] = $r;
        }
    }
} else {
    // Musyrif / Musyrifah: see only their assigned halaqoh group members
    $res_santri = $conn->query("SELECT DISTINCT s.id, s.nama_lengkap, g.nama_grup 
        FROM buku_induk_santri s 
        JOIN halaqoh_anggota a ON s.id = a.santri_id 
        JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE g.musyrif_id = $ustadz_id AND s.status_santri = 'Aktif'
        ORDER BY s.nama_lengkap ASC");
    
    if ($res_santri && $res_santri->num_rows > 0) {
        while ($r = $res_santri->fetch_assoc()) {
            $santri_binaan[] = $r;
            if (!empty($r['nama_grup']) && !in_array($r['nama_grup'], $nama_halaqoh_user)) {
                $nama_halaqoh_user[] = $r['nama_grup'];
            }
        }
    } else {
        // Fallback if no halaqoh assignment yet for this musyrif
        $res_all = $conn->query("SELECT id, nama_lengkap, NULL as nama_grup FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
        if ($res_all && $res_all->num_rows > 0) {
            while ($r = $res_all->fetch_assoc()) {
                $santri_binaan[] = $r;
            }
        }
    }
}

// Surah list for datalist
$surah_list = [
    "Al-Fatihah", "Al-Baqarah", "Ali 'Imran", "An-Nisa'", "Al-Ma'idah", "Al-An'am", "Al-A'raf", "Al-Anfal", "At-Taubah", "Yunus",
    "Hud", "Yusuf", "Ar-Ra'd", "Ibrahim", "Al-Hijr", "An-Nahl", "Al-Isra'", "Al-Kahf", "Maryam", "Tha-Ha",
    "Al-Anbiya'", "Al-Hajj", "Al-Mu'minun", "An-Nur", "Al-Furqan", "Asy-Syu'ara'", "An-Naml", "Al-Qashash", "Al-'Ankabut", "Ar-Rum",
    "Luqman", "As-Sajdah", "Al-Ahzab", "Saba'", "Fathir", "Yasin", "As-Saffat", "Shad", "Az-Zumar", "Ghafir",
    "Fushshilat", "Asy-Syura", "Az-Zukhruf", "Ad-Dukhan", "Al-Jatsiyah", "Al-Ahqaf", "Muhammad", "Al-Fath", "Al-Hujurat", "Qaf",
    "Adz-Dzariyat", "Ath-Thur", "An-Najm", "Al-Qamar", "Ar-Rahman", "Al-Waqi'ah", "Al-Hadid", "Al-Mujadilah", "Al-Hasyr", "Al-Mumtahanah",
    "Asf-Saff", "Al-Jumu'ah", "Al-Munafiqun", "At-Taghabun", "Ath-Thalaq", "At-Tahrim", "Al-Mulk", "Al-Qalam", "Al-Haqqah", "Al-Ma'arij",
    "Nuh", "Al-Jinn", "Al-Muzzammil", "Al-Muddatsir", "Al-Qiyamah", "Al-Insan", "Al-Mursalat", "An-Naba'", "An-Nazi'at", "'Abasa",
    "At-Takwir", "Al-Infitar", "Al-Muthaffifin", "Al-Insyiqaq", "Al-Buruj", "Ath-Thariq", "Al-A'la", "Al-Ghasyiyah", "Al-Fajr", "Al-Balad",
    "Asy-Syams", "Al-Lail", "Adh-Dhuha", "Asy-Syarh", "At-Tin", "Al-'Alaq", "Al-Qadr", "Al-Bayyinah", "Az-Zalzalah", "Al-'Adiyat",
    "Al-Qari'ah", "At-Takatsur", "Al-'Asr", "Al-Humazah", "Al-Fil", "Quraisy", "Al-Ma'un", "Al-Kautsar", "Al-Kafirun", "An-Nasr",
    "Al-Lahab", "Al-Ikhlas", "Al-Falaq", "An-Nas"
];

$active_menu = 'laporan_setoran_hafalan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Setoran Hafalan Santri | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-emerald-100 text-emerald-700 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">Musyrif</span>
                    <span>Sistem Administrasi Digital Sekolah (SADIGS 4.0)</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-emerald-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <!-- MAIN BODY SCROLLABLE -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <!-- PAGE TITLE BAR -->
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-emerald-200">
                            <i class="fas fa-quran text-lg"></i>
                        </div>
                        <span>Laporan Setoran Hafalan Santri</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Pencatatan dan Pemantauan Kinerja Setoran Hafalan Al-Qur'an Santri Binaan Musyrif</p>
                </div>
            </div>

            <!-- STATISTIC CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_setoran) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Total Setoran Ter-catat</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_today) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Setoran Hari Ini</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-award"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= number_format($total_mumtaz) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Total Capaian Mutqin</div>
                    </div>
                </div>
            </div>

            <!-- NOTIFICATION MESSAGE -->
            <?= $message ?>

            <!-- FORM CARD -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-800 to-teal-700 text-white flex flex-wrap items-center justify-between gap-2">
                    <h2 class="font-bold text-base flex items-center gap-2">
                        <i class="fas fa-plus-circle text-emerald-300"></i>
                        <span>Input Setoran Hafalan Baru</span>
                    </h2>
                    <?php if ($is_super_admin): ?>
                        <span class="text-xs bg-purple-900/80 border border-purple-400/40 px-3 py-1 rounded-full text-purple-200 font-semibold">
                            <i class="fas fa-user-shield mr-1"></i>Super Admin (Seluruh Santri)
                        </span>
                    <?php elseif (!empty($nama_halaqoh_user)): ?>
                        <span class="text-xs bg-emerald-900/80 border border-emerald-400/40 px-3 py-1 rounded-full text-emerald-200 font-semibold">
                            <i class="fas fa-layer-group mr-1"></i>Halaqoh: <?= htmlspecialchars(implode(', ', $nama_halaqoh_user)) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-xs bg-amber-900/80 border border-amber-400/40 px-3 py-1 rounded-full text-amber-200 font-semibold">
                            <i class="fas fa-info-circle mr-1"></i>Menampilkan Seluruh Santri (Belum Ada Halaqoh)
                        </span>
                    <?php endif; ?>
                </div>

                <form method="POST" class="p-6">

                    <datalist id="surah_list">
                        <?php foreach($surah_list as $s): ?>
                            <option value="<?= htmlspecialchars($s) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- NAMA SANTRI (BINAAN MUSYRIF HALAQOH) -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                <i class="fas fa-user-graduate text-emerald-600 mr-1.5"></i>Nama Santri Binaan <span class="text-rose-500">*</span>
                            </label>
                            <select name="nama_santri" required 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition">
                                <option value="">-- Pilih Santri Binaan --</option>
                                <?php if (!empty($santri_binaan)): ?>
                                    <?php foreach ($santri_binaan as $sb): ?>
                                        <option value="<?= htmlspecialchars($sb['nama_lengkap']) ?>">
                                            <?= htmlspecialchars($sb['nama_lengkap']) ?> <?= !empty($sb['nama_grup']) ? '('.htmlspecialchars($sb['nama_grup']).')' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">Daftar santri sesuai pembagian Manajemen Halaqoh oleh Kepala Asrama.</p>
                        </div>

                        <!-- NAMA SURAT -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                <i class="fas fa-book-quran text-emerald-600 mr-1.5"></i>Nama Surat <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nama_surat" list="surah_list" required 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition placeholder-slate-400" 
                                placeholder="Contoh: Al-Baqarah, An-Naba', dll...">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 mb-6">
                        <!-- MULAI AYAT -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Mulai Ayat <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="ayat_mulai" min="1" required 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition" 
                                    placeholder="1">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400">Ayat</div>
                            </div>
                        </div>

                        <!-- SAMPAI AYAT -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Sampai Ayat <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="ayat_sampai" min="1" required 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition" 
                                    placeholder="10">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs text-slate-400">Ayat</div>
                            </div>
                        </div>

                        <!-- HALAMAN -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Halaman
                            </label>
                            <input type="text" name="halaman" 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition placeholder-slate-400" 
                                placeholder="Contoh: 12 atau Hlm 1-2">
                        </div>

                        <!-- JUZ KE -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                                Juz ke-
                            </label>
                            <input type="number" name="juz" min="1" max="30" 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition placeholder-slate-400" 
                                placeholder="1 - 30">
                        </div>
                    </div>

                    <div class="mb-6">
                        <!-- GRADE -->
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                            <i class="fas fa-star text-amber-500 mr-1.5"></i>Grade / Kualitas Hafalan
                        </label>
                        <select name="grade" required class="w-full md:w-1/2 px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition">
                            <option value="Mutqin">1. Mutqin (Benar-benar Hafal)</option>
                            <option value="Ziyadah">2. Ziyadah (Hafal)</option>
                            <option value="Aslaha">3. Aslaha (Wajib Diperbaiki)</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-slate-100 text-right">
                        <button type="submit" name="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-200 transition-all duration-200 transform hover:-translate-y-0.5">
                            <i class="fas fa-paper-plane"></i>
                            <span>Simpan Laporan Setoran</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- HISTORY DATA TABLE -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="font-bold text-slate-800 text-base flex items-center gap-2">
                            <i class="fas fa-history text-emerald-600"></i>
                            <span>Riwayat Setoran Hafalan Santri</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Daftar rekaman hafalan yang disetorkan ke Musyrif</p>
                    </div>
                    <div>
                        <input type="text" id="search_input" onkeyup="filterTable()" placeholder="Cari nama santri/surat..." 
                            class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 w-full sm:w-64">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="setoran_table">
                        <thead>
                            <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
                                <th class="py-3.5 px-6">Santri & Tanggal</th>
                                <th class="py-3.5 px-6">Surat & Ayat</th>
                                <th class="py-3.5 px-6">Halaman & Juz</th>
                                <th class="py-3.5 px-6">Grade</th>
                                <th class="py-3.5 px-6 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <?php
                            $res_data = $conn->query("SELECT * FROM laporan_setoran_hafalan ORDER BY created_at DESC, id DESC");
                            if ($res_data && $res_data->num_rows > 0):
                                while ($row = $res_data->fetch_assoc()):
                                    $g = htmlspecialchars($row['grade']);
                                    $badge_class = "bg-slate-100 text-slate-700 border-slate-300";
                                    if (str_contains($g, 'Mutqin')) {
                                        $badge_class = "bg-emerald-100 text-emerald-800 border-emerald-300 font-bold";
                                    } else if (str_contains($g, 'Ziyadah') || str_contains($g, 'Jayid') || str_contains($g, 'Jayyid')) {
                                        $badge_class = "bg-teal-100 text-teal-800 border-teal-300 font-semibold";
                                    } else if (str_contains($g, 'Aslaha')) {
                                        $badge_class = "bg-rose-100 text-rose-800 border-rose-300 font-bold";
                                    }
                            ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900 text-sm flex items-center gap-2">
                                                <i class="fas fa-user-circle text-emerald-600"></i>
                                                <span><?= htmlspecialchars($row['nama_santri']) ?></span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                                                <i class="far fa-clock"></i>
                                                <span><?= date('d M Y - H:i', strtotime($row['created_at'])) ?> WIB</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-semibold text-emerald-700 text-sm">
                                                <i class="fas fa-book-open mr-1"></i>Surat <?= htmlspecialchars($row['nama_surat']) ?>
                                            </div>
                                            <div class="text-xs text-slate-600 mt-1">
                                                Ayat <b><?= (int)$row['ayat_mulai'] ?></b> s/d <b><?= (int)$row['ayat_sampai'] ?></b>
                                                <span class="text-slate-400 font-normal"> (<?= ((int)$row['ayat_sampai'] - (int)$row['ayat_mulai'] + 1) ?> ayat)</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <?php if(!empty($row['juz'])): ?>
                                                    <span class="bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded text-[11px] font-semibold">
                                                        Juz <?= (int)$row['juz'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if(!empty($row['halaman'])): ?>
                                                    <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 rounded text-[11px] font-medium">
                                                        Hlm <?= htmlspecialchars($row['halaman']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <span class="inline-block px-2.5 py-1 rounded-lg border text-xs <?= $badge_class ?>">
                                                <?= $g ?: '-' ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-6 align-top text-center">
                                            <a href="?action=hapus&id=<?= $row['id'] ?>" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus data setoran <?= htmlspecialchars(addslashes($row['nama_santri'])) ?>?')" 
                                               class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white transition shadow-sm" title="Hapus Data">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </a>
                                        </td>
                                    </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400 italic">
                                        <i class="fas fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                        Belum ada data setoran hafalan yang tercatat.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- SCRIPTS -->
    <script>
        // Sidebar toggle for mobile
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                const sidebar = document.getElementById('sidebar-hr');
                const overlay = document.getElementById('sidebar-overlay-hr');
                if (sidebar) sidebar.classList.toggle('hidden');
                if (overlay) overlay.classList.toggle('hidden');
            });
        }

        // Live Table Search Filter
        function filterTable() {
            const input = document.getElementById("search_input");
            const filter = input.value.toLowerCase();
            const table = document.getElementById("setoran_table");
            const trs = table.getElementsByTagName("tr");

            for (let i = 1; i < trs.length; i++) {
                const tr = trs[i];
                if (!tr.getElementsByTagName("td").length) continue;
                const text = tr.textContent || tr.innerText;
                if (text.toLowerCase().indexOf(filter) > -1) {
                    tr.style.display = "";
                } else {
                    tr.style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
