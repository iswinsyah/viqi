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

$message = '';

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
        $message = '<div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div class="flex items-center"><i class="fas fa-check-circle mr-3 text-lg text-emerald-500"></i><span>Setoran hafalan <b>' . htmlspecialchars($nama_santri) . '</b> berhasil disimpan!</span></div><button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button></div>';
    } else {
        $message = '<div class="bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div class="flex items-center"><i class="fas fa-exclamation-triangle mr-3 text-lg text-rose-500"></i><span>Gagal menyimpan data: ' . htmlspecialchars($conn->error) . '</span></div><button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600"><i class="fas fa-times"></i></button></div>';
    }
}

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
} elseif ($is_musyrif || $is_musyrifah) {
    // Musyrif / Musyrifah: ONLY see their assigned halaqoh group members (NO FALLBACK)
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

$active_menu = 'setoran_hafalan_santri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setoran Hafalan Santri | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
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
                        <span>Setoran Hafalan Santri</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Input Pencatatan Setoran Hafalan Al-Qur'an Santri Binaan Musyrif</p>
                </div>
                <div>
                    <a href="admin-laporan-setoran-hafalan.php" class="bg-white border border-slate-200 hover:border-slate-300 text-slate-700 font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 shadow-sm transition">
                        <i class="fas fa-clipboard-list text-emerald-600"></i>
                        <span>Lihat Rekap Setoran</span>
                    </a>
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
                    <?php if ($is_admin_or_kepala): ?>
                        <span class="text-xs bg-purple-900/80 border border-purple-400/40 px-3 py-1 rounded-full text-purple-200 font-semibold">
                            <i class="fas fa-user-shield mr-1"></i>Super Admin/Pimpinan (Seluruh Santri)
                        </span>
                    <?php elseif (!empty($nama_halaqoh_user)): ?>
                        <span class="text-xs bg-emerald-900/80 border border-emerald-400/40 px-3 py-1 rounded-full text-emerald-200 font-semibold">
                            <i class="fas fa-layer-group mr-1"></i>Halaqoh: <?= htmlspecialchars(implode(', ', $nama_halaqoh_user)) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-xs bg-rose-950 text-rose-300 border border-rose-800 px-3 py-1 rounded-full font-semibold">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Belum Memiliki Santri Binaan
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (($is_musyrif || $is_musyrifah) && empty($santri_binaan) && !$is_admin_or_kepala): ?>
                    <div class="p-8 text-center bg-slate-50/50">
                        <div class="w-16 h-16 bg-rose-50 text-rose-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl shadow-sm">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h3 class="text-slate-800 font-bold text-base">Tidak Ada Santri Binaan</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Anda belum memiliki santri binaan yang terdaftar di Manajemen Halaqoh. Silakan hubungi Kepala Asrama untuk membagi halaqoh santri binaan Anda.</p>
                    </div>
                <?php else: ?>
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
                            <!-- AYAT MULAI -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Ayat Mulai <span class="text-rose-500">*</span></label>
                                <input type="number" name="ayat_mulai" required min="1" 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition" 
                                    placeholder="Contoh: 1">
                            </div>

                            <!-- AYAT SAMPAI -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Ayat Sampai <span class="text-rose-500">*</span></label>
                                <input type="number" name="ayat_sampai" required min="1" 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition" 
                                    placeholder="Contoh: 10">
                            </div>

                            <!-- HALAMAN -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Halaman</label>
                                <input type="text" name="halaman" 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition" 
                                    placeholder="Contoh: 596 atau A / B">
                            </div>

                            <!-- JUZ -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Juz <span class="text-rose-500">*</span></label>
                                <input type="number" name="juz" required min="1" max="30" 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition" 
                                    placeholder="Juz 1 s/d 30">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- GRADE (NILAI) -->
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Grade Kelancaran (Nilai) <span class="text-rose-500">*</span></label>
                                <select name="grade" required 
                                    class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm bg-slate-50/50 transition">
                                    <option value="">-- Pilih Kelancaran --</option>
                                    <option value="A+ (Mutqin Sekali)">A+ (Mutqin Sekali)</option>
                                    <option value="A (Mutqin)">A (Mutqin)</option>
                                    <option value="B+ (Jayyid Jiddan)">B+ (Jayyid Jiddan)</option>
                                    <option value="B (Jayyid)">B (Jayyid)</option>
                                    <option value="C (Maqbul/Lancar)">C (Maqbul/Lancar)</option>
                                    <option value="D (Aslaha/Ulang)">D (Aslaha/Kurang Lancar)</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                            <button type="reset" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-xs font-extrabold hover:bg-slate-50 transition">
                                Reset Form
                            </button>
                            <button type="submit" name="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-md shadow-emerald-200 transition flex items-center gap-1.5">
                                <i class="fas fa-save"></i>
                                <span>Simpan Laporan</span>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

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
