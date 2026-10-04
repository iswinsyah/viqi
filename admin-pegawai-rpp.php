<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'ai_rpp';

// --- SELF-HEALING DATABASE: TABEL MODUL AJAR (RPP) ---
$conn->query("CREATE TABLE IF NOT EXISTS modul_ajar_rpp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    mapel VARCHAR(100) NOT NULL,
    kelas VARCHAR(100) NOT NULL,
    topik VARCHAR(255) NOT NULL,
    metode VARCHAR(100) NOT NULL,
    isi_rpp LONGTEXT NOT NULL,
    status ENUM('draft', 'diajukan', 'disetujui', 'revisi') DEFAULT 'diajukan',
    catatan_kepsek TEXT NULL,
    disetujui_oleh INT NULL,
    disetujui_pada DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_mapel (mapel),
    INDEX idx_kelas (kelas),
    INDEX idx_status (status)
)");

// User Session Info
$current_ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$current_user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $current_user_roles);

$is_super_admin = ($current_ustadz_id === 9999) || in_array('super_admin', $norm_roles);
$is_kepsek = $is_super_admin || in_array('kepala_sekolah', $norm_roles) || in_array('kepala_mahad', $norm_roles) || in_array('ketua_yayasan', $norm_roles);

// --- AJAX ACTION HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'save_rpp') {
        $mapel = trim($conn->real_escape_string($_POST['mapel'] ?? ''));
        $kelas = trim($conn->real_escape_string($_POST['kelas'] ?? ''));
        $topik = trim($conn->real_escape_string($_POST['topik'] ?? ''));
        $metode = trim($conn->real_escape_string($_POST['metode'] ?? ''));
        $isi_rpp = trim($conn->real_escape_string($_POST['isi_rpp'] ?? ''));

        if (!empty($mapel) && !empty($topik) && !empty($isi_rpp)) {
            $status = $is_kepsek ? 'disetujui' : 'diajukan';
            $acc_by = $is_kepsek ? $current_ustadz_id : "NULL";
            $acc_time = $is_kepsek ? "NOW()" : "NULL";

            $sql = "INSERT INTO modul_ajar_rpp (ustadz_id, mapel, kelas, topik, metode, isi_rpp, status, disetujui_oleh, disetujui_pada) 
                    VALUES ($current_ustadz_id, '$mapel', '$kelas', '$topik', '$metode', '$isi_rpp', '$status', $acc_by, $acc_time)";
            
            if ($conn->query($sql)) {
                $msg = $is_kepsek 
                    ? 'Modul Ajar berhasil disimpan dan langsung diterbitkan ke Album Resmi!' 
                    : 'Modul Ajar berhasil disimpan & diajukan ke Kepala Sekolah untuk verifikasi!';
                echo json_encode(['status' => 'success', 'message' => $msg]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data RPP belum lengkap untuk disimpan.']);
        }
        exit;
    }

    if ($action === 'review_rpp') {
        if (!$is_kepsek) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Anda tidak memiliki otoritas Kepala Sekolah.']);
            exit;
        }
        $rpp_id = (int)($_POST['rpp_id'] ?? 0);
        $status_pilihan = $_POST['status_review'] ?? ''; // 'disetujui' atau 'revisi'
        $catatan = trim($conn->real_escape_string($_POST['catatan'] ?? ''));

        if ($rpp_id > 0 && in_array($status_pilihan, ['disetujui', 'revisi'])) {
            if ($status_pilihan === 'disetujui') {
                $sql = "UPDATE modul_ajar_rpp SET status = 'disetujui', disetujui_oleh = $current_ustadz_id, disetujui_pada = NOW(), catatan_kepsek = '$catatan' WHERE id = $rpp_id";
                $msg = '✅ Modul Ajar (RPP) berhasil di-ACC dan resmi masuk ke Album RPP Sekolah!';
            } else {
                $sql = "UPDATE modul_ajar_rpp SET status = 'revisi', catatan_kepsek = '$catatan' WHERE id = $rpp_id";
                $msg = '🔄 Catatan revisi berhasil dikirim ke Tutor pembuat RPP.';
            }

            if ($conn->query($sql)) {
                echo json_encode(['status' => 'success', 'message' => $msg]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui status: ' . $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Parameter ulasan tidak valid.']);
        }
        exit;
    }

    if ($action === 'hapus_rpp') {
        $rpp_id = (int)($_POST['rpp_id'] ?? 0);
        $res = $conn->query("SELECT ustadz_id FROM modul_ajar_rpp WHERE id = $rpp_id");
        if ($res && $row = $res->fetch_assoc()) {
            if ($row['ustadz_id'] == $current_ustadz_id || $is_kepsek) {
                $conn->query("DELETE FROM modul_ajar_rpp WHERE id = $rpp_id");
                echo json_encode(['status' => 'success', 'message' => 'Modul Ajar berhasil dihapus.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak untuk menghapus RPP ini.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data RPP tidak ditemukan.']);
        }
        exit;
    }
}

// Target URL Params
$target_mapel = trim($_GET['mapel'] ?? '');
$target_kelas = ($target_mapel === 'Sosiologi') ? 'SMA (Fase E & F)' : '';
$default_tab = $_GET['tab'] ?? 'generator';

// Ambil data silabus untuk dropdown
$daftar_silabus = [];
$res_silabus = $conn->query("SELECT * FROM master_silabus ORDER BY mata_pelajaran ASC");
if ($res_silabus) {
    while($r = $res_silabus->fetch_assoc()) {
        $daftar_silabus[] = $r;
    }
}

// 1. Ambil Data Album RPP (Hanya yang berstatus 'disetujui')
$res_album = $conn->query("SELECT r.*, u.nama as nama_tutor, kep.nama as nama_kepsek 
    FROM modul_ajar_rpp r 
    JOIN akun_ustadz u ON r.ustadz_id = u.id 
    LEFT JOIN akun_ustadz kep ON r.disetujui_oleh = kep.id
    WHERE r.status = 'disetujui' 
    ORDER BY r.mapel ASC, r.kelas ASC, r.created_at DESC");
$album_rpp = [];
$filter_mapel_list = [];
$filter_kelas_list = [];

if ($res_album) {
    while ($row = $res_album->fetch_assoc()) {
        $album_rpp[] = $row;
        if (!in_array($row['mapel'], $filter_mapel_list)) $filter_mapel_list[] = $row['mapel'];
        if (!in_array($row['kelas'], $filter_kelas_list)) $filter_kelas_list[] = $row['kelas'];
    }
}
sort($filter_mapel_list);
sort($filter_kelas_list);

// 2. Ambil Data RPP Saya (Tutor)
$res_saya = $conn->query("SELECT r.*, kep.nama as nama_kepsek 
    FROM modul_ajar_rpp r 
    LEFT JOIN akun_ustadz kep ON r.disetujui_oleh = kep.id
    WHERE r.ustadz_id = $current_ustadz_id 
    ORDER BY r.created_at DESC");
$my_rpp_list = [];
if ($res_saya) {
    while ($row = $res_saya->fetch_assoc()) {
        $my_rpp_list[] = $row;
    }
}

// 3. Ambil Data Antrean Review untuk Kepala Sekolah
$pending_count = 0;
$verifikasi_list = [];
if ($is_kepsek) {
    $res_verif = $conn->query("SELECT r.*, u.nama as nama_tutor 
        FROM modul_ajar_rpp r 
        JOIN akun_ustadz u ON r.ustadz_id = u.id 
        ORDER BY CASE WHEN r.status = 'diajukan' THEN 1 ELSE 2 END, r.created_at DESC");
    if ($res_verif) {
        while ($row = $res_verif->fetch_assoc()) {
            $verifikasi_list[] = $row;
            if ($row['status'] === 'diajukan') $pending_count++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modul Ajar (RPP) & Album Terpadu | Portal Akademik</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .markdown-body h1, .markdown-body h2 { font-size: 1.4rem; font-weight: bold; color: #0891b2; margin-top: 1.25rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.4rem; }
        .markdown-body h3 { font-size: 1.15rem; font-weight: bold; color: #0e7490; margin-top: 1rem; margin-bottom: 0.4rem; }
        .markdown-body p { margin-bottom: 0.85rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 0.85rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 0.85rem; color: #334155; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.25rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.6rem 0.75rem; text-align: left; }
        .markdown-body th { background-color: #f1f5f9; font-weight: 700; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 9999px; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 hidden sm:block">Portal Kurikulum & Modul Ajar (RPP)</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                    <i class="fas fa-home text-[11px]"></i> Dashboard
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6 text-left">
            <!-- Header Title -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                        <i class="fas fa-book-bookmark text-cyan-600"></i>
                        <span>Manajemen Modul Ajar & RPP Digital</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Penyusunan RPP berbasis AI Kurikulum Merdeka, verifikasi Kepala Sekolah, dan Album Modul Ajar resmi sekolah.</p>
                </div>
            </div>

            <!-- TABS NAVIGATION -->
            <div class="flex border-b border-slate-200 mb-6 gap-2 overflow-x-auto pb-1">
                <button onclick="switchTab('generator')" id="tab-btn-generator" class="tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-cyan-600 text-white shadow-sm">
                    <i class="fas fa-magic"></i>
                    <span>AI Generator RPP</span>
                </button>
                <button onclick="switchTab('album')" id="tab-btn-album" class="tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">
                    <i class="fas fa-images text-indigo-500"></i>
                    <span>Album Modul Ajar</span>
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-2 py-0.5 rounded-full"><?= count($album_rpp) ?></span>
                </button>
                <button onclick="switchTab('saya')" id="tab-btn-saya" class="tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">
                    <i class="fas fa-user-edit text-emerald-500"></i>
                    <span>RPP Saya</span>
                    <span class="bg-emerald-100 text-emerald-700 text-[10px] font-black px-2 py-0.5 rounded-full"><?= count($my_rpp_list) ?></span>
                </button>
                <?php if ($is_kepsek): ?>
                <button onclick="switchTab('verifikasi')" id="tab-btn-verifikasi" class="tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-white text-slate-600 hover:bg-slate-100 border border-slate-200">
                    <i class="fas fa-stamp text-amber-500"></i>
                    <span>Kontrol & ACC Kepsek</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="bg-rose-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full animate-pulse"><?= $pending_count ?> Pending</span>
                    <?php endif; ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: AI GENERATOR RPP                    -->
            <!-- ========================================== -->
            <div id="tab-content-generator" class="tab-pane space-y-6">
                <!-- Form Generator -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center font-bold text-sm">
                                <i class="fas fa-brain"></i>
                            </span>
                            <div>
                                <h2 class="font-bold text-slate-800 text-sm">Form Desain Skenario Pembelajaran</h2>
                                <p class="text-[11px] text-slate-400">Pilih dari silabus atau ketik secara bebas mata pelajaran dan topik KBM.</p>
                            </div>
                        </div>
                        <?php if ($target_mapel === 'Sosiologi'): ?>
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-amber-50 text-amber-900 border border-amber-300 text-[11px] font-black">
                            <i class="fas fa-user-tie text-amber-600"></i>
                            <span>Co-Pilot AI: Ustadz Ibnu Khaldun</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Mata Pelajaran</label>
                            <input type="text" id="rpp-mapel" list="daftar-mapel" value="<?= htmlspecialchars($target_mapel) ?>" onchange="pilihSilabus(this)" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Pilih dari daftar / ketik...">
                            <datalist id="daftar-mapel">
                                <?php foreach($daftar_silabus as $s): ?>
                                    <option value="<?= htmlspecialchars($s['mata_pelajaran']) ?>" 
                                            data-kelas="<?= htmlspecialchars($s['kelas']) ?>"
                                            data-deskripsi="<?= htmlspecialchars($s['deskripsi_mapel']) ?>"
                                            data-cp="<?= htmlspecialchars($s['capaian_pembelajaran']) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Kelas / Jenjang</label>
                            <input type="text" id="rpp-kelas" value="<?= htmlspecialchars($target_kelas) ?>" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Contoh: Paket B / SMP / Fase E">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Topik / Materi Pokok</label>
                            <input type="text" id="rpp-topik" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Contoh: Interaksi Sosial & Solidaritas">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Metode Pembelajaran</label>
                            <select id="rpp-metode" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                <option value="Interaktif (Diskusi & Tanya Jawab)">Interaktif (Diskusi & Tanya Jawab)</option>
                                <option value="Problem Based Learning (PBL)">Problem Based Learning (PBL)</option>
                                <option value="Project Based Learning (PjBL)">Project Based Learning (PjBL)</option>
                                <option value="Praktek / Demonstrasi">Praktek / Demonstrasi</option>
                                <option value="Konvensional & Tadabbur">Konvensional & Tadabbur</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button id="btn-generate" onclick="generateRPP()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-xl transition text-xs flex items-center gap-2 shadow-sm">
                            <i class="fas fa-wand-magic-sparkles"></i> Susun Modul Ajar (RPP) dengan AI
                        </button>
                    </div>
                </div>

                <!-- Hasil Generator Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 min-h-[420px] flex flex-col overflow-hidden">
                    <div class="px-6 py-3.5 bg-slate-50 border-b border-slate-200 flex flex-wrap justify-between items-center gap-3">
                        <h3 class="font-bold text-slate-800 text-xs flex items-center gap-2">
                            <i class="fas fa-file-lines text-cyan-600"></i>
                            <span>Hasil Modul Ajar (RPP Kurikulum Merdeka)</span>
                        </h3>
                        <div class="flex items-center gap-2">
                            <button id="btn-save-rpp" onclick="simpanRPPHasil()" class="hidden bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-sm flex items-center gap-1.5">
                                <i class="fas fa-cloud-arrow-up"></i>
                                <span><?= $is_kepsek ? 'Simpan ke Album Resmi' : 'Simpan & Ajukan ke Kepsek' ?></span>
                            </button>
                            <button id="btn-copy" onclick="copyRPP()" class="hidden bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                <i class="fas fa-copy"></i> Copy Teks
                            </button>
                            <button id="btn-print" onclick="cetakRPP()" class="hidden bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-xl text-xs font-bold transition border border-indigo-200 flex items-center gap-1.5">
                                <i class="fas fa-print"></i> Cetak PDF
                            </button>
                        </div>
                    </div>

                    <div id="result-container" class="p-6 flex-1 overflow-y-auto relative custom-scrollbar">
                        <div id="state-idle" class="flex flex-col items-center justify-center h-full text-slate-400 py-16 text-center">
                            <i class="fas fa-file-signature text-6xl mb-4 opacity-30 text-slate-400"></i>
                            <p class="font-semibold text-xs">Isi parameter mata pelajaran dan topik di atas, lalu klik tombol susun RPP.</p>
                        </div>
                        <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-cyan-600 py-16 text-center">
                            <i class="fas fa-spinner fa-spin text-5xl mb-4 text-cyan-600"></i>
                            <p class="font-bold text-sm">Sedang menganalisa kurikulum & merumuskan tujuan pembelajaran...</p>
                            <p class="text-[11px] text-slate-400 mt-1">Menyiapkan LKS dan butir soal evaluasi KBM...</p>
                        </div>
                        <div id="state-result" class="hidden markdown-body max-w-4xl mx-auto"></div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: ALBUM MODUL AJAR (RPP) RESMI       -->
            <!-- ========================================== -->
            <div id="tab-content-album" class="tab-pane hidden space-y-6">
                <!-- Filter Bar Album -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex-1 flex flex-wrap items-center gap-3">
                        <!-- Search Box -->
                        <div class="relative min-w-[220px] flex-1">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" id="album-search" oninput="filterAlbum()" placeholder="Cari topik atau materi..." class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:bg-white">
                        </div>
                        <!-- Filter Mapel -->
                        <select id="album-filter-mapel" onchange="filterAlbum()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Semua Mapel --</option>
                            <?php foreach ($filter_mapel_list as $mp): ?>
                                <option value="<?= htmlspecialchars($mp) ?>"><?= htmlspecialchars($mp) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Filter Kelas -->
                        <select id="album-filter-kelas" onchange="filterAlbum()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500">
                            <option value="">-- Semua Kelas / Jenjang --</option>
                            <?php foreach ($filter_kelas_list as $kl): ?>
                                <option value="<?= htmlspecialchars($kl) ?>"><?= htmlspecialchars($kl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-[11px] text-slate-500 whitespace-nowrap">
                        Total RPP Resmi: <b id="album-count-text" class="text-indigo-600 font-bold"><?= count($album_rpp) ?></b> Modul
                    </div>
                </div>

                <!-- Grid Kartu Album RPP -->
                <?php if (empty($album_rpp)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-12 text-center text-slate-400">
                        <i class="fas fa-folder-open text-5xl mb-3 text-slate-300"></i>
                        <p class="font-bold text-slate-700 text-sm">Album Modul Ajar Masih Kosong</p>
                        <p class="text-xs text-slate-400 mt-1">RPP yang telah disetujui (ACC) oleh Kepala Sekolah akan otomatis diarsipkan di sini untuk digunakan kembali kapan saja.</p>
                    </div>
                <?php else: ?>
                    <div id="album-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($album_rpp as $item): ?>
                            <div class="album-item bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-md transition p-5 flex flex-col justify-between"
                                 data-mapel="<?= htmlspecialchars($item['mapel']) ?>"
                                 data-kelas="<?= htmlspecialchars($item['kelas']) ?>"
                                 data-topik="<?= htmlspecialchars(strtolower($item['topik'])) ?>">
                                <div>
                                    <!-- Header Badge -->
                                    <div class="flex items-center justify-between gap-2 mb-3">
                                        <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200 truncate">
                                            <?= htmlspecialchars($item['mapel']) ?>
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1">
                                            <i class="fas fa-check-double text-[9px]"></i> ACC Kepsek
                                        </span>
                                    </div>

                                    <!-- Topik -->
                                    <h3 class="font-bold text-slate-900 text-sm line-clamp-2 mb-2 leading-snug" title="<?= htmlspecialchars($item['topik']) ?>">
                                        <?= htmlspecialchars($item['topik']) ?>
                                    </h3>

                                    <!-- Metadata -->
                                    <div class="space-y-1 text-[11px] text-slate-500 mb-4">
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-graduation-cap text-slate-400 w-3.5"></i>
                                            <span>Kelas: <b><?= htmlspecialchars($item['kelas']) ?></b></span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-chalkboard-user text-slate-400 w-3.5"></i>
                                            <span>Penyusun: <b><?= htmlspecialchars($item['nama_tutor']) ?></b></span>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <i class="fas fa-lightbulb text-slate-400 w-3.5"></i>
                                            <span class="truncate">Metode: <?= htmlspecialchars($item['metode']) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Card -->
                                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-[10px] text-slate-400">
                                        <?= date('d M Y', strtotime($item['created_at'])) ?>
                                    </span>
                                    <div class="flex items-center gap-1.5">
                                        <button onclick="bukaModalDetail(<?= htmlspecialchars(json_encode($item)) ?>)" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1">
                                            <i class="fas fa-eye text-[10px]"></i> Buka RPP
                                        </button>
                                        <?php if ($is_kepsek || $item['ustadz_id'] == $current_ustadz_id): ?>
                                            <button onclick="hapusRPP(<?= $item['id'] ?>)" class="p-1.5 text-slate-350 hover:text-rose-600 transition" title="Hapus Modul">
                                                <i class="fas fa-trash-alt text-xs"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: RPP SAYA (STATUS TRACKER TUTOR)    -->
            <!-- ========================================== -->
            <div id="tab-content-saya" class="tab-pane hidden space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h2 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2 border-b pb-3">
                        <i class="fas fa-list-check text-emerald-600"></i>
                        <span>Riwayat Pengajuan Modul Ajar Saya</span>
                    </h2>

                    <?php if (empty($my_rpp_list)): ?>
                        <div class="py-12 text-center text-slate-400">
                            <i class="fas fa-folder-plus text-4xl mb-2 text-slate-300"></i>
                            <p class="text-xs">Anda belum pernah menyimpan/mengajukan RPP.</p>
                            <button onclick="switchTab('generator')" class="mt-3 px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white font-bold rounded-xl text-xs transition">
                                <i class="fas fa-wand-magic mr-1"></i> Buat RPP Sekarang
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                        <th class="px-4 py-3">Mata Pelajaran</th>
                                        <th class="px-4 py-3">Kelas</th>
                                        <th class="px-4 py-3">Topik Pembelajaran</th>
                                        <th class="px-4 py-3">Tanggal Dibuat</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3">Catatan Kepala Sekolah</th>
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_rpp_list as $my): ?>
                                        <tr class="border-b hover:bg-slate-50/50">
                                            <td class="px-4 py-3 font-bold text-slate-800"><?= htmlspecialchars($my['mapel']) ?></td>
                                            <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($my['kelas']) ?></td>
                                            <td class="px-4 py-3 font-medium text-slate-700 max-w-xs truncate" title="<?= htmlspecialchars($my['topik']) ?>">
                                                <?= htmlspecialchars($my['topik']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($my['created_at'])) ?></td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <?php if ($my['status'] === 'disetujui'): ?>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <i class="fas fa-check-circle mr-1"></i> Disetujui (ACC)
                                                    </span>
                                                <?php elseif ($my['status'] === 'revisi'): ?>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                        <i class="fas fa-exclamation-circle mr-1"></i> Perlu Revisi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                                        <i class="fas fa-clock mr-1"></i> Menunggu Review
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] max-w-xs truncate" title="<?= htmlspecialchars($my['catatan_kepsek'] ?? '') ?>">
                                                <?= htmlspecialchars($my['catatan_kepsek'] ?: '-') ?>
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap flex items-center justify-center gap-2">
                                                <button onclick="bukaModalDetail(<?= htmlspecialchars(json_encode($my)) ?>)" class="text-cyan-600 hover:text-cyan-800 font-bold" title="Lihat Isi">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="hapusRPP(<?= $my['id'] ?>)" class="text-rose-500 hover:text-rose-700" title="Hapus">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 4: KONTROL & VERIFIKASI KEPSEK         -->
            <!-- ========================================== -->
            <?php if ($is_kepsek): ?>
            <div id="tab-content-verifikasi" class="tab-pane hidden space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-slate-100">
                        <div>
                            <h2 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <i class="fas fa-stamp text-amber-600"></i>
                                <span>Verifikasi & Persetujuan Modul Ajar (RPP) Tutor</span>
                            </h2>
                            <p class="text-[11px] text-slate-400">Periksa modul ajar yang diajukan Tutor, berikan persetujuan resmi (ACC) atau catatan revisi.</p>
                        </div>
                        <div class="text-xs font-bold text-slate-600">
                            Menunggu Review: <span class="text-rose-600 font-black"><?= $pending_count ?></span> RPP
                        </div>
                    </div>

                    <?php if (empty($verifikasi_list)): ?>
                        <div class="py-12 text-center text-slate-400">
                            <i class="fas fa-circle-check text-4xl mb-2 text-slate-300"></i>
                            <p class="text-xs">Belum ada pengajuan RPP yang masuk ke sistem.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                        <th class="px-4 py-3">Nama Tutor</th>
                                        <th class="px-4 py-3">Mapel & Kelas</th>
                                        <th class="px-4 py-3">Topik Pembelajaran</th>
                                        <th class="px-4 py-3">Tgl Pengajuan</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                        <th class="px-4 py-3 text-center">Aksi Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($verifikasi_list as $vf): ?>
                                        <tr class="border-b hover:bg-slate-50/50">
                                            <td class="px-4 py-3 font-bold text-slate-800"><?= htmlspecialchars($vf['nama_tutor']) ?></td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <span class="font-semibold text-indigo-700"><?= htmlspecialchars($vf['mapel']) ?></span><br>
                                                <span class="text-[10px] text-slate-400"><?= htmlspecialchars($vf['kelas']) ?></span>
                                            </td>
                                            <td class="px-4 py-3 font-medium text-slate-700 max-w-xs truncate" title="<?= htmlspecialchars($vf['topik']) ?>">
                                                <?= htmlspecialchars($vf['topik']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($vf['created_at'])) ?></td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <?php if ($vf['status'] === 'disetujui'): ?>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                        <i class="fas fa-check-circle mr-1"></i> Disetujui
                                                    </span>
                                                <?php elseif ($vf['status'] === 'revisi'): ?>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                        <i class="fas fa-exclamation-circle mr-1"></i> Revisi
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 animate-pulse">
                                                        <i class="fas fa-clock mr-1"></i> Menunggu ACC
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <button onclick="bukaModalReviewKepsek(<?= htmlspecialchars(json_encode($vf)) ?>)" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-900 font-black text-xs transition shadow-xs flex items-center gap-1 mx-auto">
                                                    <i class="fas fa-clipboard-check"></i> Review & ACC
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- ========================================== -->
    <!-- MODAL DETAIL RPP (BACA / CETAK)           -->
    <!-- ========================================== -->
    <div id="modal-detail-rpp" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in duration-150">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span id="modal-mapel-badge" class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-indigo-100 text-indigo-800">Mapel</span>
                        <span id="modal-kelas-badge" class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200 text-slate-700">Kelas</span>
                    </div>
                    <h2 id="modal-topik-title" class="font-bold text-slate-900 text-base mt-1">Topik Pembelajaran</h2>
                </div>
                <button onclick="tutupModalDetail()" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">&times;</button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 flex-1 overflow-y-auto custom-scrollbar">
                <!-- ACC Official Banner -->
                <div id="modal-acc-banner" class="hidden mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div>
                            <div class="font-bold text-xs">RESMI DISETUJUI (ACC) OLEH KEPALA SEKOLAH</div>
                            <div id="modal-acc-meta" class="text-[11px] text-emerald-700 mt-0.5">Disetujui pada: -</div>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-emerald-200 text-emerald-900 text-[10px] font-black rounded-lg uppercase">Valid Document</span>
                </div>

                <div id="modal-content-markdown" class="markdown-body"></div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <button onclick="tutupModalDetail()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition">
                    Tutup
                </button>
                <div class="flex items-center gap-2">
                    <button id="modal-btn-copy" onclick="copyModalRPP()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition flex items-center gap-1.5">
                        <i class="fas fa-copy"></i> Copy Teks
                    </button>
                    <button id="modal-btn-print" onclick="cetakModalRPP()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-print"></i> Cetak / Download PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL REVIEW KEPALA SEKOLAH (ACC / REVISI) -->
    <!-- ========================================== -->
    <?php if ($is_kepsek): ?>
    <div id="modal-review-kepsek" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-amber-50 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fas fa-stamp text-amber-600"></i>
                        <span>Lembar Verifikasi Modul Ajar (Kepala Sekolah)</span>
                    </h2>
                    <p id="rev-info-tutor" class="text-[11px] text-slate-500 mt-0.5">Tutor: - | Mapel: -</p>
                </div>
                <button onclick="tutupModalReview()" class="text-slate-400 hover:text-slate-600 text-xl font-bold p-1">&times;</button>
            </div>

            <div class="p-6 flex-1 overflow-y-auto custom-scrollbar space-y-4">
                <!-- Preview RPP -->
                <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 max-h-72 overflow-y-auto custom-scrollbar">
                    <div id="rev-content-markdown" class="markdown-body text-xs"></div>
                </div>

                <!-- Input Catatan / Evaluasi -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Kepala Sekolah / Umpan Balik Supervisi (Opsional jika ACC, Wajib jika Revisi)</label>
                    <textarea id="rev-catatan" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-amber-500" placeholder="Tulis masukan atau evaluasi untuk tutor..."></textarea>
                </div>
            </div>

            <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                <button onclick="tutupModalReview()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition">
                    Batal
                </button>
                <div class="flex items-center gap-2">
                    <button id="btn-submit-revisi" onclick="kirimReviewKepsek('revisi')" class="px-4 py-2 bg-rose-100 hover:bg-rose-200 text-rose-800 font-bold rounded-xl text-xs transition border border-rose-300 flex items-center gap-1.5">
                        <i class="fas fa-rotate-left"></i> Minta Revisi
                    </button>
                    <button id="btn-submit-acc" onclick="kirimReviewKepsek('disetujui')" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                        <i class="fas fa-check-circle"></i> Setujui (ACC Resmi)
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar-hr');
            const overlay = document.getElementById('sidebar-overlay-hr');
            if(sidebar) sidebar.classList.toggle('hidden');
            if(overlay) overlay.classList.toggle('hidden');
        });

        let currentActiveRPP = null;
        let activeReviewRPPId = null;
        let generatedRawRPP = "";

        // Tab Switcher
        function switchTab(tabId) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-nav-btn').forEach(btn => {
                btn.className = "tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-white text-slate-600 hover:bg-slate-100 border border-slate-200";
            });

            const activeContent = document.getElementById('tab-content-' + tabId);
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            
            if (activeContent) activeContent.classList.remove('hidden');
            if (activeBtn) {
                if (tabId === 'generator') {
                    activeBtn.className = "tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-cyan-600 text-white shadow-sm";
                } else if (tabId === 'album') {
                    activeBtn.className = "tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-indigo-600 text-white shadow-sm";
                } else if (tabId === 'saya') {
                    activeBtn.className = "tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-emerald-600 text-white shadow-sm";
                } else if (tabId === 'verifikasi') {
                    activeBtn.className = "tab-nav-btn px-4 py-2.5 font-bold text-xs rounded-xl flex items-center gap-2 transition bg-amber-500 text-slate-900 shadow-sm";
                }
            }
        }

        // Dropdown Auto-filler Silabus
        function pilihSilabus(input) {
            const val = input.value;
            const opts = document.getElementById('daftar-mapel').options;
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value === val) {
                    document.getElementById('rpp-kelas').value = opts[i].getAttribute('data-kelas');
                    return;
                }
            }
        }

        // Generator RPP via AI
        function generateRPP() {
            const mapel = document.getElementById('rpp-mapel').value.trim();
            const kelas = document.getElementById('rpp-kelas').value.trim();
            const topik = document.getElementById('rpp-topik').value.trim();
            const metode = document.getElementById('rpp-metode').value;

            if(!mapel || !kelas || !topik || !metode) {
                Swal.fire({ icon: 'warning', title: 'Data Belum Lengkap', text: 'Mohon isi Mata Pelajaran, Kelas, Topik, dan Metode KBM!', confirmButtonColor: '#0891b2' });
                return;
            }

            let personaPrefix = "";
            if (mapel.toLowerCase().includes('sosiologi')) {
                personaPrefix = "Anda bertindak sebagai Ustadz Ibnu Khaldun (Bapak Sosiologi Dunia) yang menjadi Co-Pilot AI kurikulum Sosiologi SMA di Pesantren Villa Quran. Integrasikan ketajaman analisis sosial, konsep Ashabiyah (solidaritas masyarakat), serta nilai-nilai Al-Qur'an ke dalam modul ajar ini.\n\n";
            }

            let selectedOption = null;
            const opts = document.getElementById('daftar-mapel').options;
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value === mapel) {
                    selectedOption = opts[i];
                    break;
                }
            }

            let prompt = "";
            if (selectedOption) {
                const deskripsiMapel = selectedOption.getAttribute('data-deskripsi');
                const capaianPembelajaran = selectedOption.getAttribute('data-cp');
                let cpFormatted = capaianPembelajaran;
                try {
                    const parsedCP = JSON.parse(capaianPembelajaran);
                    if (Array.isArray(parsedCP)) {
                        cpFormatted = parsedCP.map(item => `- **Elemen ${item.elemen}:** ${item.cp}`).join('\n');
                    }
                } catch (e) { }

                prompt = personaPrefix + `Anda adalah asisten ahli kurikulum untuk Pesantren Villa Quran. Buatlah Modul Ajar berformat Kurikulum Merdeka yang menarik dan modern berdasarkan konteks berikut:
- **Mata Pelajaran:** ${mapel}
- **Fase / Kelas:** ${kelas}
- **Deskripsi Umum Mapel:** ${deskripsiMapel}
- **Capaian Pembelajaran (CP):**\n${cpFormatted}
- **Topik / Materi Pokok:** ${topik}
- **Metode Pembelajaran:** ${metode}

Struktur Modul Ajar wajib dalam format Markdown yang rapi: 
1. **Informasi Umum** (Identitas, Kompetensi Awal, Profil Pelajar Pancasila / Santri, Sarana & Prasarana). 
2. **Komponen Inti**: 
   - **Tujuan Pembelajaran** (Spesifik diturunkan dari Elemen CP dan Topik). 
   - **Pemahaman Bermakna** & **Pertanyaan Pemantik**. 
   - **Kegiatan Pembelajaran**: Pendahuluan (Ice breaking dll), Inti (Eksplorasi Materi dengan metode ${metode}), Penutup (Refleksi). 
3. **Asesmen / Evaluasi** (Bentuk penilaian singkat untuk mengukur ketercapaian tujuan).
4. **Lampiran: Lembar Kerja Siswa (LKS)** (Buat LKS sederhana dan interaktif terkait topik).
5. **Lampiran: Soal Ulangan** (Buat 5 soal pilihan ganda beserta kunci jawabannya terkait topik).`;
            } else {
                prompt = `Anda adalah asisten ahli kurikulum. Buatlah Modul Ajar berformat Kurikulum Merdeka yang menarik dan modern untuk:
- **Mata Pelajaran:** ${mapel}
- **Fase / Kelas:** ${kelas}
- **Topik / Materi Pokok:** ${topik}
- **Metode Pembelajaran:** ${metode}

Struktur Modul Ajar wajib dalam format Markdown yang rapi: 1. **Informasi Umum**. 2. **Komponen Inti** (Tujuan, Pemahaman Bermakna, Pertanyaan Pemantik, Kegiatan Pendahuluan, Inti metode ${metode}, Penutup). 3. **Asesmen / Evaluasi**. 4. **Lampiran: Lembar Kerja Siswa (LKS)**. 5. **Lampiran: 5 Soal Ulangan Pilihan Ganda & Kunci Jawaban**.`;
            }

            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('btn-generate').disabled = true;

            fetch("api-gemini.php", {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: [{jenis_lead:"SYSTEM_COMMAND", sumber_info:prompt, status:"URGENT"}], type: 'rpp' })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === "success") {
                    generatedRawRPP = data.result;
                    document.getElementById('state-result').innerHTML = marked.parse(data.result);
                    document.getElementById('state-loading').classList.add('hidden');
                    document.getElementById('state-result').classList.remove('hidden');
                    document.getElementById('btn-save-rpp').classList.remove('hidden');
                    document.getElementById('btn-print').classList.remove('hidden');
                    document.getElementById('btn-copy').classList.remove('hidden');
                } else throw new Error(data.message);
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Error AI', text: err.message });
                document.getElementById('state-loading').classList.add('hidden');
                document.getElementById('state-idle').classList.remove('hidden');
            })
            .finally(() => document.getElementById('btn-generate').disabled = false);
        }

        // Simpan RPP ke Database & Ajukan
        function simpanRPPHasil() {
            if(!generatedRawRPP) {
                Swal.fire({ icon: 'warning', title: 'Belum Ada RPP', text: 'Generate RPP terlebih dahulu sebelum menyimpan!' });
                return;
            }

            const mapel = document.getElementById('rpp-mapel').value.trim();
            const kelas = document.getElementById('rpp-kelas').value.trim();
            const topik = document.getElementById('rpp-topik').value.trim();
            const metode = document.getElementById('rpp-metode').value;

            const formData = new FormData();
            formData.append('action', 'save_rpp');
            formData.append('mapel', mapel);
            formData.append('kelas', kelas);
            formData.append('topik', topik);
            formData.append('metode', metode);
            formData.append('isi_rpp', generatedRawRPP);

            Swal.fire({
                title: 'Menyimpan...',
                text: 'Sedang menyimpan modul ajar ke database...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('admin-pegawai-rpp.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, confirmButtonColor: '#0891b2' })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                    }
                })
                .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
        }

        // Modal Detail RPP
        function bukaModalDetail(data) {
            currentActiveRPP = data;
            document.getElementById('modal-mapel-badge').innerText = data.mapel;
            document.getElementById('modal-kelas-badge').innerText = data.kelas;
            document.getElementById('modal-topik-title').innerText = data.topik;
            document.getElementById('modal-content-markdown').innerHTML = marked.parse(data.isi_rpp);

            const banner = document.getElementById('modal-acc-banner');
            if (data.status === 'disetujui') {
                banner.classList.remove('hidden');
                document.getElementById('modal-acc-meta').innerText = `Disetujui oleh: ${data.nama_kepsek || 'Kepala Sekolah'} • Tanggal: ${data.disetujui_pada || data.updated_at || '-'}`;
            } else {
                banner.classList.add('hidden');
            }

            document.getElementById('modal-detail-rpp').classList.remove('hidden');
        }

        function tutupModalDetail() {
            document.getElementById('modal-detail-rpp').classList.add('hidden');
        }

        function copyModalRPP() {
            if (!currentActiveRPP) return;
            navigator.clipboard.writeText(currentActiveRPP.isi_rpp).then(() => {
                Swal.fire({ icon: 'success', title: 'Tersalin!', text: 'Isi modul ajar berhasil disalin ke clipboard.', timer: 1500, showConfirmButton: false });
            });
        }

        function cetakModalRPP() {
            if (!currentActiveRPP) return;
            const content = document.getElementById('modal-content-markdown').innerHTML;
            const title = `${currentActiveRPP.mapel} - ${currentActiveRPP.topik}`;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>${title}</title>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #1e293b; line-height: 1.6; }
                        h1, h2, h3 { color: #0f172a; margin-top: 20px; margin-bottom: 10px; }
                        h1 { font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; text-align: center; }
                        h2 { font-size: 18px; color: #0891b2; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; }
                        h3 { font-size: 15px; color: #0e7490; }
                        p { margin-bottom: 8px; }
                        ul, ol { margin-bottom: 12px; padding-left: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
                        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
                        th { background-color: #f8fafc; font-weight: bold; }
                        .official-stamp { border: 2px solid #059669; padding: 12px; border-radius: 8px; background: #ecfdf5; margin-bottom: 20px; text-align: center; color: #065f46; font-size: 12px; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class="official-stamp">
                        MODUL AJAR RESMI • TERVERIFIKASI KEPALA SEKOLAH PESANTREN VILLA QURAN<br>
                        Mata Pelajaran: ${currentActiveRPP.mapel} | Kelas: ${currentActiveRPP.kelas} | Penyusun: ${currentActiveRPP.nama_tutor || 'Tutor'}
                    </div>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
        }

        // Modal Review Kepala Sekolah
        function bukaModalReviewKepsek(data) {
            activeReviewRPPId = data.id;
            document.getElementById('rev-info-tutor').innerText = `Tutor: ${data.nama_tutor} | Mapel: ${data.mapel} (${data.kelas}) | Topik: ${data.topik}`;
            document.getElementById('rev-content-markdown').innerHTML = marked.parse(data.isi_rpp);
            document.getElementById('rev-catatan').value = data.catatan_kepsek || '';
            document.getElementById('modal-review-kepsek').classList.remove('hidden');
        }

        function tutupModalReview() {
            document.getElementById('modal-review-kepsek').classList.add('hidden');
            activeReviewRPPId = null;
        }

        function kirimReviewKepsek(statusPilihan) {
            if (!activeReviewRPPId) return;
            const catatan = document.getElementById('rev-catatan').value.trim();

            if (statusPilihan === 'revisi' && !catatan) {
                Swal.fire({ icon: 'warning', title: 'Catatan Wajib', text: 'Mohon tuliskan catatan revisi untuk tutor pembuat modul ajar ini!' });
                return;
            }

            const formData = new FormData();
            formData.append('action', 'review_rpp');
            formData.append('rpp_id', activeReviewRPPId);
            formData.append('status_review', statusPilihan);
            formData.append('catatan', catatan);

            Swal.fire({
                title: 'Memproses...',
                text: statusPilihan === 'disetujui' ? 'Menyetujui & menerbitkan ke Album RPP...' : 'Mengirimkan catatan revisi...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            fetch('admin-pegawai-rpp.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, confirmButtonColor: '#10b981' })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                    }
                })
                .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
        }

        // Hapus RPP
        function hapusRPP(id) {
            Swal.fire({
                title: 'Hapus Modul Ajar?',
                text: 'Modul ini akan dihapus dari sistem.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', 'hapus_rpp');
                    formData.append('rpp_id', id);

                    fetch('admin-pegawai-rpp.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message, timer: 1500, showConfirmButton: false })
                                    .then(() => window.location.reload());
                            } else {
                                Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                            }
                        })
                        .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
                }
            });
        }

        // Filter Album RPP
        function filterAlbum() {
            const search = document.getElementById('album-search').value.toLowerCase();
            const mapel = document.getElementById('album-filter-mapel').value;
            const kelas = document.getElementById('album-filter-kelas').value;

            const items = document.querySelectorAll('.album-item');
            let visibleCount = 0;

            items.forEach(item => {
                const itemMapel = item.getAttribute('data-mapel');
                const itemKelas = item.getAttribute('data-kelas');
                const itemTopik = item.getAttribute('data-topik');

                let matchMapel = !mapel || itemMapel === mapel;
                let matchKelas = !kelas || itemKelas === kelas;
                let matchSearch = !search || itemTopik.includes(search) || itemMapel.toLowerCase().includes(search);

                if (matchMapel && matchKelas && matchSearch) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            const countText = document.getElementById('album-count-text');
            if (countText) countText.innerText = visibleCount;
        }

        function copyRPP() {
            const text = document.getElementById('state-result').innerText;
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({ icon: 'success', title: 'Tersalin!', text: 'Isi RPP berhasil disalin ke clipboard.', timer: 1500, showConfirmButton: false });
            });
        }

        function cetakRPP() {
            const content = document.getElementById('state-result').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Modul Ajar (RPP)</title>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #1e293b; line-height: 1.6; }
                        h1, h2, h3 { color: #0f172a; margin-top: 20px; margin-bottom: 10px; }
                        h1 { font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; text-align: center; }
                        h2 { font-size: 18px; color: #0891b2; }
                        h3 { font-size: 15px; color: #0e7490; }
                        p { margin-bottom: 8px; }
                        ul, ol { margin-bottom: 12px; padding-left: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 15px; }
                        th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
                        th { background-color: #f8fafc; font-weight: bold; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
        }

        // Set Default Tab on Load
        document.addEventListener('DOMContentLoaded', () => {
            switchTab('<?= $default_tab ?>');
        });
    </script>
</body>
</html>