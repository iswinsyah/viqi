<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// 1. Buat Tabel Otomatis & Self-healing Schema
$conn->query("CREATE TABLE IF NOT EXISTS buku_mutabaah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    musyrif_id INT NULL,
    nama_santri VARCHAR(150),
    kelas VARCHAR(50),
    tanggal DATE,
    kondisi_mental VARCHAR(100) NULL,
    permasalahan TEXT NULL,
    solusi TEXT NULL,
    rekomendasi_ai TEXT NULL,
    catatan_musyrif TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Self-healing columns:
$res_mental = $conn->query("SHOW COLUMNS FROM buku_mutabaah LIKE 'kondisi_mental'");
if ($res_mental && $res_mental->num_rows == 0) {
    $conn->query("ALTER TABLE buku_mutabaah ADD COLUMN kondisi_mental VARCHAR(100) NULL AFTER tanggal");
}
$res_problem = $conn->query("SHOW COLUMNS FROM buku_mutabaah LIKE 'permasalahan'");
if ($res_problem && $res_problem->num_rows == 0) {
    $conn->query("ALTER TABLE buku_mutabaah ADD COLUMN permasalahan TEXT NULL AFTER kondisi_mental");
}
$res_sol = $conn->query("SHOW COLUMNS FROM buku_mutabaah LIKE 'solusi'");
if ($res_sol && $res_sol->num_rows == 0) {
    $conn->query("ALTER TABLE buku_mutabaah ADD COLUMN solusi TEXT NULL AFTER permasalahan");
}
$res_ai = $conn->query("SHOW COLUMNS FROM buku_mutabaah LIKE 'rekomendasi_ai'");
if ($res_ai && $res_ai->num_rows == 0) {
    $conn->query("ALTER TABLE buku_mutabaah ADD COLUMN rekomendasi_ai TEXT NULL AFTER solusi");
}

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM buku_mutabaah WHERE id = $id");
    header("Location: admin-pegawai-mutabaah.php?sukses=" . urlencode("Laporan Mutaba'ah berhasil dihapus!"));
    exit;
}

$pesan_sukses = isset($_GET['sukses']) ? $_GET['sukses'] : null;

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $musyrif_id = (int)$_SESSION['ustadz_id'];
    $nama_santri = $conn->real_escape_string($_POST['nama_santri']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $kondisi_mental = $conn->real_escape_string($_POST['kondisi_mental']);
    $permasalahan = $conn->real_escape_string($_POST['permasalahan']);
    $solusi = $conn->real_escape_string($_POST['solusi']);
    $rekomendasi_ai = $conn->real_escape_string($_POST['rekomendasi_ai']);

    if ($id > 0) {
        $sql = "UPDATE buku_mutabaah SET 
                musyrif_id=$musyrif_id, 
                nama_santri='$nama_santri', 
                kelas='$kelas', 
                tanggal='$tanggal', 
                kondisi_mental='$kondisi_mental', 
                permasalahan='$permasalahan', 
                solusi='$solusi', 
                rekomendasi_ai='$rekomendasi_ai' 
                WHERE id=$id";
        $pesan_sukses = "Laporan Mutaba'ah Santri berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO buku_mutabaah 
                (musyrif_id, nama_santri, kelas, tanggal, kondisi_mental, permasalahan, solusi, rekomendasi_ai) 
                VALUES 
                ($musyrif_id, '$nama_santri', '$kelas', '$tanggal', '$kondisi_mental', '$permasalahan', '$solusi', '$rekomendasi_ai')";
        $pesan_sukses = "Laporan Mutaba'ah Santri berhasil disimpan!";
    }
    
    if ($conn->query($sql)) {
        header("Location: admin-pegawai-mutabaah.php?sukses=" . urlencode($pesan_sukses));
        exit;
    }
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM buku_mutabaah WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

// --- FITUR PENCARIAN & FILTER ---
$search_nama = $_GET['search'] ?? '';
$filter_kelas = $_GET['filter_kelas'] ?? '';

$where_clauses = [];
if (!empty($search_nama)) {
    $where_clauses[] = "nama_santri LIKE '%" . $conn->real_escape_string($search_nama) . "%'";
}
if (!empty($filter_kelas)) {
    $where_clauses[] = "kelas = '" . $conn->real_escape_string($filter_kelas) . "'";
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

$daftar_kelas_opsi = [];
$res_kelas = $conn->query("SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kelas && $res_kelas->num_rows > 0) {
    while($row = $res_kelas->fetch_assoc()) {
        $daftar_kelas_opsi[] = $row['nama_kelas'];
    }
} else {
    $daftar_kelas_opsi = ['Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12', 'Kelas Rijal', 'Kelas Nisa'];
}

function getMentalBadge($state) {
    switch ($state) {
        case 'Stabil':
        case 'Semangat':
            return 'bg-emerald-50 text-emerald-700 border-emerald-200';
        case 'Cemas / Khawatir':
            return 'bg-amber-50 text-amber-700 border-amber-200';
        case 'Sedih / Murung':
            return 'bg-blue-50 text-blue-700 border-blue-200';
        case 'Stres / Tertekan':
            return 'bg-orange-50 text-orange-700 border-orange-200';
        case 'Malas / Kurang Motivasi':
            return 'bg-purple-50 text-purple-700 border-purple-200';
        case 'Marah / Sensitif':
            return 'bg-rose-50 text-rose-700 border-rose-200';
        default:
            return 'bg-gray-50 text-gray-700 border-gray-200';
    }
}

$active_menu = 'mutabaah';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mutaba'ah & Konseling Mental Santri | Ruang Musyrif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; }
        .markdown-body {
            font-size: 0.85rem;
            line-height: 1.6;
        }
        .markdown-body h1, .markdown-body h2, .markdown-body h3 {
            font-weight: 700;
            color: #1e1b4b;
            margin-top: 1rem;
            margin-bottom: 0.5rem;
        }
        .markdown-body h1 { font-size: 1.2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem; }
        .markdown-body h2 { font-size: 1.05rem; }
        .markdown-body h3 { font-size: 0.95rem; }
        .markdown-body p { margin-bottom: 0.75rem; text-align: justify; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.25rem; margin-bottom: 0.75rem; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
        .markdown-body blockquote {
            border-left: 4px solid #6366f1;
            padding-left: 0.75rem;
            color: #4b5563;
            font-style: italic;
            background: #f8fafc;
            margin: 0.75rem 0;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-slate-100">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 hidden sm:block">Laporan Mutaba'ah & Konseling Mental Santri</h2>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs bg-indigo-50 text-indigo-700 font-bold px-3 py-1.5 rounded-full border border-indigo-150">
                    <i class="fas fa-user-shield mr-1"></i> <?= htmlspecialchars($_SESSION['ustadz_nama']) ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-6">
            
            <div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <i class="fas fa-heartbeat text-indigo-600"></i> Mutaba'ah Perkembangan & Mental Santri
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Laporan kondisi mental, pendataan permasalahan santri, dan konsultasi AI Konselor Syariah.</p>
                </div>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 px-4 py-3.5 rounded-r-lg mb-6 shadow-sm flex items-center gap-2 text-xs font-semibold'><i class='fas fa-check-circle text-emerald-500 text-base'></i> $pesan_sukses</div>"; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- FORM LAPORAN -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 lg:col-span-2 overflow-hidden h-fit">
                    <div class="px-6 py-4 bg-gradient-to-r from-slate-900 to-indigo-950 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="font-bold text-white text-sm"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Laporan Perkembangan' : 'Input Mutaba\'ah & Konseling Baru' ?></h2>
                        <span class="text-[10px] bg-indigo-500/30 text-indigo-200 font-bold px-2 py-0.5 rounded-full">Format Baru</span>
                    </div>
                    
                    <form action="admin-pegawai-mutabaah.php" method="POST" id="form-mutabaah" class="p-6 space-y-4">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                        <input type="hidden" name="rekomendasi_ai" id="rekomendasi_ai_val" value="<?= $edit_mode ? htmlspecialchars($data_edit['rekomendasi_ai'] ?? '') : '' ?>">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tanggal</label>
                                <input type="date" name="tanggal" value="<?= $edit_mode ? $data_edit['tanggal'] : date('Y-m-d') ?>" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nama Santri</label>
                                <input type="text" name="nama_santri" id="nama_santri" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_santri']) : '' ?>" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white" placeholder="Nama lengkap santri...">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kelas Asrama</label>
                                <select name="kelas" id="kelas" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white">
                                    <option value="">-- Pilih Kelas --</option>
                                    <?php
                                    $kelas_tersimpan = $edit_mode ? $data_edit['kelas'] : '';
                                    foreach ($daftar_kelas_opsi as $nama_kelas) {
                                        $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                        echo "<option value=\"".htmlspecialchars($nama_kelas)."\" $sel>".htmlspecialchars($nama_kelas)."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Kondisi Mental Santri</label>
                            <select name="kondisi_mental" id="kondisi_mental" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white font-semibold">
                                <?php
                                $mentals = [
                                    'Stabil' => '🟢 Stabil / Normal',
                                    'Semangat' => '🔥 Semangat / Aktif',
                                    'Cemas / Khawatir' => '🟡 Cemas / Gelisah / Khawatir',
                                    'Sedih / Murung' => '🔵 Sedih / Murung / Menyendiri',
                                    'Stres / Tertekan' => '🔴 Stres / Tertekan / Homesick',
                                    'Malas / Kurang Motivasi' => '🟣 Malas / Demotivasi / Mengantuk',
                                    'Marah / Sensitif' => '🔴 Marah / Sensitif / Sulit Diatur',
                                    'Lain-lain' => '⚪ Kondisi Lainnya'
                                ];
                                $mental_val = $edit_mode ? $data_edit['kondisi_mental'] : 'Stabil';
                                foreach ($mentals as $val => $lbl) {
                                    $sel = ($mental_val === $val) ? 'selected' : '';
                                    echo "<option value=\"$val\" $sel>$lbl</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Permasalahan Santri</label>
                                <button type="button" id="btn-tanya-ai" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold px-3 py-1 rounded-xl text-[10px] border border-indigo-200 transition flex items-center gap-1">
                                    <i class="fas fa-magic"></i> Hubungi Konselor AI Syariah
                                </button>
                            </div>
                            <textarea name="permasalahan" id="permasalahan" rows="3" required class="w-full px-3.5 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Jelaskan permasalahan santri secara detail (misal: sering melamun, malas menyetor hafalan, berselisih dengan teman kamar, rindu rumah/homesick)..."><?= $edit_mode ? htmlspecialchars($data_edit['permasalahan'] ?? '') : '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tindakan / Solusi dari Musyrif</label>
                            <textarea name="solusi" id="solusi" rows="3" required class="w-full px-3.5 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Tuliskan tindakan awal yang Anda lakukan atau solusi yang diberikan..."><?= $edit_mode ? htmlspecialchars($data_edit['solusi'] ?? '') : '' ?></textarea>
                        </div>

                        <div class="text-right pt-2 border-t border-slate-100">
                            <?php if($edit_mode) echo '<a href="admin-pegawai-mutabaah.php" class="bg-slate-200 text-slate-700 px-5 py-2 rounded-xl text-xs font-bold mr-2 inline-block">Batal</a>'; ?>
                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-xl text-xs shadow transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Laporan' : 'Simpan Laporan' ?></button>
                        </div>
                    </form>
                </div>

                <!-- CONSOLE ASISTEN AI KONSELOOR SYARIAH -->
                <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 p-6 flex flex-col h-full min-h-[400px]">
                    <div class="pb-3 border-b border-slate-100 mb-4">
                        <h3 class="font-bold text-slate-900 text-sm flex items-center gap-1.5">
                            <i class="fas fa-robot text-indigo-600"></i> AI Konselor Syariah
                        </h3>
                        <p class="text-[10px] text-slate-400 mt-0.5">Rekomendasi solusi konseling santri berbasis dalil syar'i & kitab rujukan.</p>
                    </div>

                    <div class="flex-1 overflow-y-auto max-h-[360px] pr-2 space-y-3" id="ai-chat-output">
                        <?php if ($edit_mode && !empty($data_edit['rekomendasi_ai'])): ?>
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-slate-700 text-xs markdown-body" id="ai-static-content">
                                <!-- Will be filled via JS marked library -->
                            </div>
                        <?php else: ?>
                            <div class="text-center text-slate-400 py-12" id="ai-placeholder">
                                <i class="fas fa-scroll text-4xl text-slate-200 mb-3 block"></i>
                                <span class="text-xs block font-medium">Belum ada konsultasi.</span>
                                <span class="text-[10px] text-slate-400/80 block mt-1">Ketik masalah santri lalu klik tombol "Hubungi Konselor AI Syariah" di form kiri untuk meminta saran bimbingan.</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div id="ai-loading" class="hidden text-center text-slate-500 py-10 flex-1 flex flex-col items-center justify-center">
                        <i class="fas fa-circle-notch fa-spin text-3xl text-indigo-600 mb-3"></i>
                        <span class="text-xs font-bold text-slate-700 block">AI Sedang Meracik Rekomendasi Syar'i...</span>
                        <span class="text-[9px] text-slate-400 block mt-1">Mencari referensi dalil & metode konseling...</span>
                    </div>
                </div>
            </div>

            <!-- FILTER & PENCARIAN -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4 mb-6">
                <form action="admin-pegawai-mutabaah.php" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Cari Nama Santri</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search_nama) ?>" class="w-full px-4 py-2.5 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Ketik nama santri...">
                    </div>
                    <div class="w-full sm:w-1/3">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Filter Kelas</label>
                        <select name="filter_kelas" class="w-full px-4 py-2.5 border rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 bg-white">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($daftar_kelas_opsi as $k) { $sel = ($filter_kelas == $k) ? 'selected' : ''; echo "<option value=\"$k\" $sel>$k</option>"; } ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-slate-900 hover:bg-black text-white font-bold py-2.5 px-6 rounded-xl text-xs transition shadow-sm w-full sm:w-auto"><i class="fas fa-search mr-2"></i> Cari</button>
                    <?php if(!empty($search_nama) || !empty($filter_kelas)): ?><a href="admin-pegawai-mutabaah.php" class="bg-rose-50 text-rose-700 hover:bg-rose-100 py-2.5 px-4 rounded-xl text-xs font-bold transition text-center w-full sm:w-auto" title="Reset Filter"><i class="fas fa-times"></i></a><?php endif; ?>
                </form>
            </div>

            <!-- RIWAYAT MUTABA'AH -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50"><h2 class="font-bold text-slate-800">Riwayat Mutaba'ah & Perkembangan Santri</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-slate-150">
                        <thead class="bg-slate-50/50">
                            <tr>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Santri & Tanggal</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Kondisi Mental</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Permasalahan</th>
                                <th class="px-4 py-3.5 text-left text-xs font-bold text-slate-500 uppercase">Tindakan / Solusi</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase">Saran AI</th>
                                <th class="px-4 py-3.5 text-center text-xs font-bold text-slate-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <?php
                            $res = $conn->query("SELECT * FROM buku_mutabaah $where_sql ORDER BY tanggal DESC, id DESC LIMIT 50");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $mental = $row['kondisi_mental'] ?? 'Stabil';
                                    $has_ai = !empty($row['rekomendasi_ai']);
                                ?>
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($row['nama_santri']) ?></div>
                                        <div class="text-[10px] text-slate-400 mt-0.5"><i class="fas fa-calendar-alt mr-1"></i><?= date('d M Y', strtotime($row['tanggal'])) ?> &bull; <strong class="text-slate-500"><?= htmlspecialchars($row['kelas']) ?></strong></div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border <?= getMentalBadge($mental) ?>">
                                            <?= htmlspecialchars($mentals[$mental] ?? $mental) ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        <div class="truncate text-slate-700" title="<?= htmlspecialchars($row['permasalahan'] ?? '-') ?>"><?= htmlspecialchars($row['permasalahan'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        <div class="truncate text-slate-700" title="<?= htmlspecialchars($row['solusi'] ?? '-') ?>"><?= htmlspecialchars($row['solusi'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap">
                                        <?php if ($has_ai): ?>
                                            <button onclick="openModalAI('<?= htmlspecialchars($row['nama_santri'], ENT_QUOTES) ?>', `<?= addslashes($row['rekomendasi_ai']) ?>`)" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 font-bold px-3 py-1 rounded-xl text-[10px] transition inline-flex items-center gap-1 shadow-sm">
                                                <i class="fas fa-comment-medical text-indigo-500"></i> Lihat Saran AI
                                            </button>
                                        <?php else: ?>
                                            <span class="text-slate-400 italic text-[10px]">Tidak ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 text-center whitespace-nowrap space-x-1.5">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-indigo-600 hover:text-indigo-800 font-bold text-xs"><i class="fas fa-edit"></i> Edit</a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus data mutabaah ini?')" class="text-rose-600 hover:text-rose-800 font-bold text-xs"><i class="fas fa-trash"></i> Hapus</a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='6' class='text-center py-10 text-slate-400 italic'>Belum ada rekapan laporan Mutaba'ah santri.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL PREVIEW SARAN AI SYARIAH -->
    <div id="modalAI" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 flex flex-col max-h-[85vh]">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100 mb-4 flex-shrink-0">
                <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                    <i class="fas fa-robot text-indigo-600"></i> Rekomendasi Solusi AI: <span id="modal-santri-name" class="text-indigo-800"></span>
                </h3>
                <button onclick="closeModalAI()" class="text-slate-400 hover:text-slate-600 focus:outline-none"><i class="fas fa-times text-lg"></i></button>
            </div>
            
            <div class="flex-1 overflow-y-auto pr-2 text-slate-700 text-xs markdown-body" id="modal-ai-content">
                <!-- Rendered Markdown will go here -->
            </div>

            <div class="flex justify-end pt-4 border-t border-slate-100 mt-4 flex-shrink-0">
                <button onclick="closeModalAI()" class="px-5 py-2 bg-slate-900 hover:bg-black text-white font-bold rounded-xl text-xs shadow-sm transition">Tutup Laporan</button>
            </div>
        </div>
    </div>

    <script>
        // Toggle Sidebar Mobile
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
            document.getElementById('sidebar-hr').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
        });

        // Parse static markdown if edit mode with existing AI data
        document.addEventListener('DOMContentLoaded', () => {
            const staticEl = document.getElementById('ai-static-content');
            const hiddenAiVal = document.getElementById('rekomendasi_ai_val').value;
            if (staticEl && hiddenAiVal) {
                staticEl.innerHTML = marked.parse(hiddenAiVal);
            }
        });

        // Fitur Konsultasi AI
        document.getElementById('btn-tanya-ai').addEventListener('click', function() {
            const nama = document.getElementById('nama_santri').value.trim();
            const kelas = document.getElementById('kelas').value;
            const mental = document.getElementById('kondisi_mental').value;
            const permasalahan = document.getElementById('permasalahan').value.trim();

            if (!nama || !kelas || !permasalahan) {
                alert("Harap lengkapi Nama Santri, Kelas, dan rincian Permasalahan terlebih dahulu sebelum konsultasi AI!");
                return;
            }

            // Atur UI Loading
            const outputArea = document.getElementById('ai-chat-output');
            const placeholder = document.getElementById('ai-placeholder');
            const loading = document.getElementById('ai-loading');
            
            if (placeholder) placeholder.classList.add('hidden');
            loading.classList.remove('hidden');
            outputArea.classList.add('hidden');

            const promptText = `Anda adalah seorang Ustadz Konselor Senior dan Ahli Psikologi Islam di Pondok Pesantren Tahfidz Villa Quran.
Tugas Anda adalah menganalisis kondisi mental dan permasalahan santri berikut, kemudian memberikan solusi bimbingan konseling yang bijak sesuai syariat Islam, lengkap dengan referensi dalil (Al-Qur'an/Hadits) atau kitab rujukan (kitab salaf/kontemporer) jika relevan.

Berikut data santri:
- Nama: ${nama}
- Kelas: ${kelas}
- Kondisi Mental: ${mental}
- Permasalahan: ${permasalahan}

Tolong berikan respon yang terstruktur dengan format Markdown yang cantik dan premium:
1. **Analisis Masalah**: (tinjauan psikologis singkat)
2. **Solusi Syar'i & Bimbingan**: (langkah praktis bagi Musyrif untuk membimbing santri)
3. **Referensi & Rujukan Dalil / Kitab**: (sebutkan dalil Al-Qur'an, Hadits, atau kitab ulama klasik/kontemporer beserta penjelasannya)

Tulis solusi dengan bahasa yang sopan, mendalam, membakar semangat (motivatif), dan langsung bisa dipraktikkan oleh Musyrif.`;

            // Kirim request ke api-gemini.php
            fetch('api-gemini.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ prompt: promptText })
            })
            .then(res => res.json())
            .then(data => {
                loading.classList.add('hidden');
                outputArea.classList.remove('hidden');
                
                if (data.status === 'success') {
                    const mdResult = data.result;
                    // Simpan ke hidden input
                    document.getElementById('rekomendasi_ai_val').value = mdResult;
                    
                    // Render HTML
                    outputArea.innerHTML = `<div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 text-slate-700 text-xs markdown-body">${marked.parse(mdResult)}</div>`;
                } else {
                    outputArea.innerHTML = `<div class="text-rose-600 bg-rose-50 border border-rose-100 rounded-xl p-4 text-xs font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>Gagal berkonsultasi: ${data.message}</div>`;
                }
            })
            .catch(err => {
                loading.classList.add('hidden');
                outputArea.classList.remove('hidden');
                outputArea.innerHTML = `<div class="text-rose-600 bg-rose-50 border border-rose-100 rounded-xl p-4 text-xs font-semibold"><i class="fas fa-exclamation-triangle mr-2"></i>Terjadi kesalahan jaringan saat menghubungi AI: ${err.message}</div>`;
            });
        });

        // Modal AI
        function openModalAI(santriName, mdContent) {
            document.getElementById('modal-santri-name').innerText = santriName;
            document.getElementById('modal-ai-content').innerHTML = marked.parse(mdContent);
            document.getElementById('modalAI').classList.remove('hidden');
        }

        function closeModalAI() {
            document.getElementById('modalAI').classList.add('hidden');
        }
    </script>
</body>
</html>