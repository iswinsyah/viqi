<?php
require_once 'auth-unified.php';
requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();
$is_pengurus = $is_admin || !empty(array_intersect(['super_admin', 'ketua_yayasan', 'sekretaris_yayasan', 'bendahara_yayasan', 'kepala_sekolah', 'kepala_mahad', 'admin_sekolah'], $roles));

// 1. Inisialisasi Tabel Self-Healing
$conn->query("CREATE TABLE IF NOT EXISTS pengumuman_resmi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('Yayasan', 'Sekolah', 'Ma\'had', 'Akademik', 'Asrama') NOT NULL DEFAULT 'Yayasan',
    judul VARCHAR(255) NOT NULL,
    konten LONGTEXT NOT NULL,
    tingkat_urgensi ENUM('Biasa', 'Penting', 'Mendesak') NOT NULL DEFAULT 'Biasa',
    target_role VARCHAR(100) NOT NULL DEFAULT 'Semua',
    penulis VARCHAR(150) NOT NULL DEFAULT 'Pengurus Yayasan',
    lampiran VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed data awal jika masih kosong
$chk_cnt = $conn->query("SELECT COUNT(*) as cnt FROM pengumuman_resmi");
if ($chk_cnt && (int)$chk_cnt->fetch_assoc()['cnt'] === 0) {
    $seeds = [
        [
            'kategori' => 'Yayasan',
            'judul' => 'Edaran Resmi: Kalender Kegiatan Semester & Rencana Strategis TP 2026/2027',
            'konten' => 'Assalamu\'alaikum Wr. Wb. Diberitahukan kepada seluruh civitas akademika, asatidz, staf, serta walisantri bahwa rapat pleno pengurus yayasan telah mengesahkan agenda dan kalender pendidikan untuk tahun ajaran baru. Mohon seluruh unit mempersiapkan program kerja masing-masing.',
            'tingkat_urgensi' => 'Penting',
            'target_role' => 'Semua',
            'penulis' => 'Ketua Yayasan'
        ],
        [
            'kategori' => 'Sekolah',
            'judul' => 'Pemberitahuan Pelaksanaan Ujian Penilaian Tengah Semester (PTS) PKBM',
            'konten' => 'Kepada seluruh Guru dan Tutor PKBM Paket B dan C, jadwal pelaksanaan PTS akan dimulai sesuai kalender akademik. Pengumpulan naskah soal paling lambat H-3 melalui portal e-learning/guru.',
            'tingkat_urgensi' => 'Biasa',
            'target_role' => 'Asatidz',
            'penulis' => 'Kepala Sekolah'
        ],
        [
            'kategori' => 'Ma\'had',
            'judul' => 'Disiplin Ibadah & Evaluasi Program Tasmi\' Al-Qur\'an Santri',
            'konten' => 'Disampaikan kepada Musyrif dan Musyrifah asrama agar meningkatkan pendampingan sholat berjamaah, tilawah ba\'da Shubuh, serta rekapitulasi setoran hafalan harian santri setiap pekan.',
            'tingkat_urgensi' => 'Penting',
            'target_role' => 'Musyrif',
            'penulis' => 'Kepala Ma\'had'
        ]
    ];
    foreach ($seeds as $s) {
        $stmt = $conn->prepare("INSERT INTO pengumuman_resmi (kategori, judul, konten, tingkat_urgensi, target_role, penulis) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $s['kategori'], $s['judul'], $s['konten'], $s['tingkat_urgensi'], $s['target_role'], $s['penulis']);
        $stmt->execute();
    }
}

// 2. Aksi Tambah Pengumuman (Khusus Pimpinan / Pengurus)
$pesan_sukses = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengumuman']) && $is_pengurus) {
    $judul = trim($_POST['judul'] ?? '');
    $kategori = $_POST['kategori'] ?? 'Yayasan';
    $urgensi = $_POST['tingkat_urgensi'] ?? 'Biasa';
    $target = $_POST['target_role'] ?? 'Semua';
    $konten = trim($_POST['konten'] ?? '');
    $penulis = $user['nama_lengkap'] ?? 'Pengurus';

    if (!empty($judul) && !empty($konten)) {
        $stmt = $conn->prepare("INSERT INTO pengumuman_resmi (kategori, judul, konten, tingkat_urgensi, target_role, penulis) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $kategori, $judul, $konten, $urgensi, $target, $penulis);
        $stmt->execute();
        $pesan_sukses = "Pengumuman resmi berhasil diterbitkan!";
    }
}

// 3. Aksi Hapus Pengumuman
if (isset($_GET['hapus_id']) && $is_pengurus) {
    $hid = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM pengumuman_resmi WHERE id = $hid");
    header("Location: pengumuman.php?sukses=deleted");
    exit;
}
if (isset($_GET['sukses']) && $_GET['sukses'] === 'deleted') {
    $pesan_sukses = "Pengumuman berhasil dihapus.";
}

// 4. Filter & Ambil Data Pengumuman
$filter_kat = $_GET['kat'] ?? 'semua';
$search_q = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM pengumuman_resmi WHERE 1=1";
$params = [];
$types = "";

if ($filter_kat !== 'semua' && in_array($filter_kat, ['Yayasan', 'Sekolah', 'Ma\'had', 'Akademik', 'Asrama'])) {
    $sql .= " AND kategori = ?";
    $params[] = $filter_kat;
    $types .= "s";
}
if (!empty($search_q)) {
    $sql .= " AND (judul LIKE ? OR konten LIKE ?)";
    $q_like = "%$search_q%";
    $params[] = $q_like;
    $params[] = $q_like;
    $types .= "ss";
}
$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res_pengumuman = $stmt->get_result();
$list_pengumuman = [];
while ($row = $res_pengumuman->fetch_assoc()) {
    $list_pengumuman[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pusat Pengumuman & Informasi Resmi | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#dcf3ee] min-h-screen text-slate-800 flex flex-col md:flex-row antialiased selection:bg-[#0b8478] selection:text-white">

    <!-- ========================================================= -->
    <!-- DESKTOP SIDEBAR (PC)                                      -->
    <!-- ========================================================= -->
    <aside class="hidden md:flex flex-col w-64 lg:w-72 bg-[#0b8478] text-white min-h-screen sticky top-0 h-screen shadow-2xl z-30 flex-shrink-0 border-r border-teal-700/50">
        
        <!-- SIDEBAR TOP: BRAND LOGO SADIGS -->
        <div class="p-6 border-b border-teal-700/60 flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </div>

        <!-- SIDEBAR USER PROFILE CARD -->
        <div class="px-5 py-4 border-b border-teal-700/40 bg-teal-900/30">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-base shadow-sm border-2 border-white/80 overflow-hidden flex-shrink-0">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-[#0b8478]"></i>
                    <?php endif; ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                    <p class="text-[10px] text-teal-200 truncate mt-0.5">@<?= htmlspecialchars($user['username']) ?></p>
                    <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                        <?= htmlspecialchars($user['roles']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- SIDEBAR NAVIGATION LINKS -->
        <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
            <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-house w-4 text-center"></i>
                <span>Beranda</span>
            </a>
            <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-clock w-4 text-center"></i>
                <span>Jadwal</span>
            </a>
            <a href="pengumuman.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-white text-[#0b8478] font-black shadow-sm transition">
                <i class="fas fa-bullhorn w-4 text-center"></i>
                <span>Info</span>
            </a>
        </nav>

        <!-- SIDEBAR FOOTER: LOGOUT -->
        <div class="p-4 border-t border-teal-700/60">
            <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
                <i class="fas fa-arrow-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- ========================================================= -->
    <!-- MAIN CONTENT AREA                                         -->
    <!-- ========================================================= -->
    <div class="flex-1 min-h-screen flex flex-col relative bg-[#dcf3ee]">
        
        <!-- TOP HERO BANNER -->
        <div class="bg-[#0b8478] text-white pt-6 pb-20 px-6 relative rounded-b-[28px] md:rounded-b-[36px] shadow-md flex-shrink-0">
            <div class="max-w-4xl mx-auto flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white text-2xl border border-white/20 shadow-md">
                        <i class="fas fa-bullhorn text-amber-300"></i>
                    </div>
                    <div>
                        <h1 class="font-black text-2xl text-white leading-tight">Pengumuman & Informasi Resmi</h1>
                        <p class="text-xs text-teal-100 mt-0.5">Pusat Siaran Resmi Yayasan, Sekolah, dan Ma'had Villa Quran</p>
                    </div>
                </div>

                <?php if ($is_pengurus): ?>
                <button onclick="toggleModalTambah()" class="px-4 py-2.5 rounded-xl bg-white text-[#0b8478] hover:bg-teal-50 font-extrabold text-xs shadow-md transition flex items-center gap-2 cursor-pointer">
                    <i class="fas fa-plus-circle text-sm"></i> Buat Pengumuman Baru
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="flex-1 px-4 sm:px-8 pt-0 pb-24 md:pb-12 w-full max-w-4xl mx-auto -mt-12 z-20">
            
            <?php if (!empty($pesan_sukses)): ?>
            <div class="mb-4 bg-teal-50 border border-teal-200 text-[#0b8478] px-4 py-3 rounded-2xl shadow-sm flex items-center font-bold text-xs">
                <i class="fas fa-check-circle text-base mr-2"></i> <?= htmlspecialchars($pesan_sukses) ?>
            </div>
            <?php endif; ?>

            <!-- FILTER PILLS & SEARCH BAR -->
            <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-lg shadow-teal-950/5 border border-teal-50 mb-6">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                    
                    <!-- Kategori Pills -->
                    <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-1 sm:pb-0">
                        <?php 
                        $kats = [
                            'semua'   => 'Semua',
                            'Yayasan' => '🏛️ Yayasan',
                            'Sekolah' => '🏫 Sekolah',
                            'Ma\'had' => '🕌 Ma\'had'
                        ];
                        foreach ($kats as $k_val => $k_label):
                            $active = ($filter_kat === $k_val);
                        ?>
                        <a href="pengumuman.php?kat=<?= urlencode($k_val) ?><?= !empty($search_q) ? '&q='.urlencode($search_q) : '' ?>" class="px-3 py-1.5 rounded-xl font-bold text-xs whitespace-nowrap transition <?= $active ? 'bg-[#0b8478] text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-teal-50 hover:text-[#0b8478]' ?>">
                            <?= $k_label ?>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Search Input -->
                    <form method="GET" action="pengumuman.php" class="relative flex-1 sm:max-w-xs">
                        <?php if ($filter_kat !== 'semua'): ?>
                            <input type="hidden" name="kat" value="<?= htmlspecialchars($filter_kat) ?>">
                        <?php endif; ?>
                        <input type="text" name="q" value="<?= htmlspecialchars($search_q) ?>" placeholder="Cari pengumuman..." class="w-full pl-9 pr-3 py-1.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </form>

                </div>
            </div>

            <!-- LIST DAFTAR PENGUMUMAN -->
            <div class="space-y-4">
                <?php if (empty($list_pengumuman)): ?>
                <div class="bg-white rounded-3xl p-10 text-center border border-teal-50 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-teal-50 text-[#0b8478] flex items-center justify-center text-2xl mx-auto mb-3">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm">Belum Ada Pengumuman</h3>
                    <p class="text-xs text-slate-400 mt-1">Tidak ada pengumuman yang sesuai dengan filter saat ini.</p>
                </div>
                <?php else: ?>
                    <?php foreach ($list_pengumuman as $p): 
                        $badge_color = 'bg-teal-50 text-[#0b8478] border-teal-200';
                        if ($p['kategori'] === 'Sekolah') $badge_color = 'bg-blue-50 text-blue-700 border-blue-200';
                        if ($p['kategori'] === 'Ma\'had') $badge_color = 'bg-emerald-50 text-emerald-700 border-emerald-200';

                        $urgensi_badge = '';
                        if ($p['tingkat_urgensi'] === 'Mendesak') {
                            $urgensi_badge = '<span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-extrabold flex items-center gap-1"><i class="fas fa-triangle-exclamation"></i> Mendesak</span>';
                        } elseif ($p['tingkat_urgensi'] === 'Penting') {
                            $urgensi_badge = '<span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold flex items-center gap-1"><i class="fas fa-star text-amber-500"></i> Penting</span>';
                        }
                    ?>
                    <article class="bg-white rounded-3xl p-5 sm:p-6 shadow-md shadow-teal-950/5 border border-teal-50 transition-all hover:shadow-lg hover:border-teal-100">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100 text-xs">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-0.5 rounded-full font-bold text-[10px] border <?= $badge_color ?>">
                                    <?= htmlspecialchars($p['kategori']) ?>
                                </span>
                                <?= $urgensi_badge ?>
                                <span class="text-[11px] text-slate-400">
                                    <i class="far fa-clock mr-1"></i> <?= date('d M Y, H:i', strtotime($p['created_at'])) ?>
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <span class="text-[10px] text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md font-medium">
                                    Oleh: <b><?= htmlspecialchars($p['penulis']) ?></b>
                                </span>
                                <?php if ($is_pengurus): ?>
                                <a href="pengumuman.php?hapus_id=<?= $p['id'] ?>" onclick="return confirm('Hapus pengumuman ini?');" class="text-rose-400 hover:text-rose-600 p-1 rounded transition text-xs" title="Hapus">
                                    <i class="fas fa-trash-can"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3.5">
                            <h2 class="font-extrabold text-base sm:text-lg text-slate-900 leading-snug">
                                <?= htmlspecialchars($p['judul']) ?>
                            </h2>
                            <div class="mt-2 text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line font-normal">
                                <?= nl2br(htmlspecialchars($p['konten'])) ?>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- FOOTER -->
            <div class="mt-8 text-center text-[11px] text-teal-800 font-semibold opacity-70">
                Pusat Informasi & Pengumuman Resmi • SADIGS 4.0
            </div>

        </main>

        <!-- ========================================================= -->
        <!-- BOTTOM NAVIGATION BAR (HP)                                -->
        <!-- ========================================================= -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] flex items-center justify-around z-40 max-w-[440px] mx-auto px-2">
            <a href="dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                <i class="fas fa-house text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                <span class="text-white">Beranda</span>
            </a>
            <a href="kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                <i class="fas fa-calendar-alt text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                <span class="text-white">Kalender</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                <i class="fas fa-clock text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                <span class="text-white">Jadwal</span>
            </a>
            <a href="pengumuman.php" class="flex flex-col items-center justify-center flex-1 py-1 text-white font-black text-[10px]">
                <div class="w-9 h-7 rounded-full bg-white/20 flex items-center justify-center mb-0.5">
                    <i class="fas fa-bullhorn text-base text-white"></i>
                </div>
                <span class="text-white">Info</span>
            </a>
            <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-rose-200 font-bold text-[10px] transition">
                <i class="fas fa-arrow-right-from-bracket text-lg mb-0.5 text-teal-100 hover:text-rose-200"></i>
                <span class="text-white">Keluar</span>
            </a>
        </nav>

    </div>

    <!-- ========================================================= -->
    <!-- MODAL BUAT PENGUMUMAN (KHUSUS PENGURUS / PIMPINAN)        -->
    <!-- ========================================================= -->
    <?php if ($is_pengurus): ?>
    <div id="modalTambahPengumuman" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-teal-100 relative animate-in fade-in zoom-in duration-150">
            <button onclick="toggleModalTambah()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition">
                <i class="fas fa-times text-xs"></i>
            </button>

            <h3 class="font-extrabold text-base text-slate-900 mb-1 flex items-center gap-2">
                <i class="fas fa-bullhorn text-[#0b8478]"></i> Terbitkan Pengumuman Resmi
            </h3>
            <p class="text-xs text-slate-500 mb-4">Siaran informasi ini akan langsung tampil di menu Info seluruh pengguna.</p>

            <form action="pengumuman.php" method="POST" class="space-y-3 text-xs">
                <input type="hidden" name="tambah_pengumuman" value="1">
                
                <div>
                    <label class="font-bold text-slate-700 block mb-1">Judul Pengumuman:</label>
                    <input type="text" name="judul" required placeholder="Contoh: Edaran Libur Awal Ramadhan 1447 H" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none">
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Kategori Lembaga:</label>
                        <select name="kategori" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none">
                            <option value="Yayasan">🏛️ Yayasan</option>
                            <option value="Sekolah">🏫 Sekolah</option>
                            <option value="Ma'had">🕌 Ma'had</option>
                            <option value="Akademik">📚 Akademik</option>
                            <option value="Asrama">🏢 Asrama</option>
                        </select>
                    </div>
                    <div>
                        <label class="font-bold text-slate-700 block mb-1">Tingkat Urgensi:</label>
                        <select name="tingkat_urgensi" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none">
                            <option value="Biasa">Biasa (Informasi Umum)</option>
                            <option value="Penting">⭐ Penting</option>
                            <option value="Mendesak">🚨 Mendesak / Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="font-bold text-slate-700 block mb-1">Isi Pengumuman / Pesan:</label>
                    <textarea name="konten" required rows="4" placeholder="Tuliskan isi pengumuman atau instruksi resmi di sini..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="toggleModalTambah()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#0b8478] hover:bg-[#086a60] text-white font-bold transition shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-paper-plane"></i> Publikasikan
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    function toggleModalTambah() {
        const modal = document.getElementById('modalTambahPengumuman');
        if (modal) modal.classList.toggle('hidden');
    }
    </script>
</body>
</html>
