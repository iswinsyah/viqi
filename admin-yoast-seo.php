<?php
/**
 * PUSAT KENDALI AGENT AI YOAST SEO AUDITOR & EDITOR
 * Villa Quran Baron Malang - SADIGS 4.0
 * 
 * Mengaudit & memoles seluruh artikel lama dan baru agar berstatus Double Green (🟢 SEO & 🟢 Readability)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'auth.php';
require_once 'koneksi.php';

// Pastikan kolom Yoast tersedia
$conn->query("ALTER TABLE artikel ADD COLUMN IF NOT EXISTS yoast_seo_score VARCHAR(20) DEFAULT 'untested'");
$conn->query("ALTER TABLE artikel ADD COLUMN IF NOT EXISTS yoast_readability_score VARCHAR(20) DEFAULT 'untested'");
$conn->query("ALTER TABLE artikel ADD COLUMN IF NOT EXISTS yoast_audit_log TEXT NULL");

// Ambil Statistik Artikel
$tot_artikel = 0;
$q1 = $conn->query("SELECT COUNT(id) as c FROM artikel");
if ($q1) $tot_artikel = (int)$q1->fetch_assoc()['c'];

$tot_hijau = 0;
$q2 = $conn->query("SELECT COUNT(id) as c FROM artikel WHERE yoast_seo_score = 'green' AND yoast_readability_score = 'green'");
if ($q2) $tot_hijau = (int)$q2->fetch_assoc()['c'];

$tot_perlu_poles = $tot_artikel - $tot_hijau;

// Ambil Daftar Seluruh Artikel
$semua_artikel = [];
$res = $conn->query("SELECT id, judul, slug, kategori, status, created_at, yoast_seo_score, yoast_readability_score, yoast_audit_log, LENGTH(konten) as len FROM artikel ORDER BY id DESC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $semua_artikel[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoast SEO Auditor & Editor AI | Ruang Web Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR RUANG WEB -->
    <?php include 'sidebar-web.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOPBAR -->
        <header class="h-16 bg-white shadow-xs border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-slate-500 hover:text-slate-700 focus:outline-none md:hidden p-1.5 rounded-lg hover:bg-slate-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                        <i class="fas fa-magnifying-glass-chart"></i>
                    </div>
                    <div>
                        <h2 class="font-black text-slate-800 text-sm sm:text-base leading-tight">Agent AI Yoast SEO Editor</h2>
                        <p class="text-[10px] text-slate-500 font-medium">Automatic On-Page SEO & Readability Auditor</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2.5">
                <a href="admin-seo.php" class="px-3 py-1.5 rounded-xl bg-teal-50 hover:bg-teal-100 text-[#0b8478] border border-teal-200 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-pen-nib"></i> <span class="hidden sm:inline">Penulis Artikel</span>
                </a>
                <a href="dashboard-marketing.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-chart-line text-[#0b8478]"></i> <span class="hidden sm:inline">Statistik Web</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 pb-20 md:pb-6 custom-scrollbar space-y-6">
            
            <!-- BANNER HERO -->
            <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-slate-900 rounded-3xl p-5 sm:p-7 text-white shadow-lg relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-44 h-44 bg-purple-500/20 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="px-3 py-1 bg-white/20 text-purple-200 rounded-full text-[10px] font-black uppercase tracking-wider inline-flex items-center gap-1.5 mb-2">
                            <i class="fas fa-shield-halved text-emerald-400"></i> Quality Control & Optimization Gatekeeper
                        </span>
                        <h1 class="text-xl sm:text-2xl font-black text-white leading-tight">
                            Agent AI Yoast SEO Editor & Auditor
                        </h1>
                        <p class="text-xs sm:text-sm text-purple-200/90 mt-1 max-w-xl leading-relaxed">
                            Audit dan poles artikel lama maupun baru secara otomatis hingga berstatus <b>Semua Hijau (🟢 SEO & 🟢 Readability)</b>: menyisipkan 2 link keluar pemerintah/wiki di awal, 2 link dalam di penutup, kata transisi, dan pemecahan paragraf.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button id="btn-batch-optimize" onclick="jalankanBatchOptimize()" class="px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white rounded-xl text-xs font-black shadow-md transition flex items-center gap-2 transform active:scale-95">
                            <i class="fas fa-wand-magic-sparkles"></i> <span>Poles Semua Artikel Lama</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- STATISTIK WIDGETS -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                <!-- 1. Total Artikel -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Artikel di Web</p>
                        <h3 class="text-2xl font-black text-slate-900 leading-none mt-0.5"><?= $tot_artikel ?></h3>
                    </div>
                </div>

                <!-- 2. Hijau Sempurna -->
                <div class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-xs flex items-center gap-3.5 bg-emerald-50/30">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-circle-check"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Hijau Sempurna (🟢🟢)</p>
                        <h3 id="stat-tot-hijau" class="text-2xl font-black text-emerald-700 leading-none mt-0.5"><?= $tot_hijau ?></h3>
                    </div>
                </div>

                <!-- 3. Perlu Dioptimalkan -->
                <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-xs flex items-center gap-3.5 bg-amber-50/30">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Perlu Dipoles (🟡 / 🔴)</p>
                        <h3 id="stat-tot-perlu" class="text-2xl font-black text-amber-700 leading-none mt-0.5"><?= $tot_perlu_poles ?></h3>
                    </div>
                </div>
            </div>

            <!-- BATCH PROGRESS CONTAINER (HIDDEN SAAT IDLE) -->
            <div id="batch-progress-box" class="hidden bg-white p-5 rounded-2xl border border-purple-200 shadow-md space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-spinner fa-spin text-purple-600 text-lg"></i>
                        <span id="batch-progress-title" class="text-xs sm:text-sm font-black text-slate-800">Sedang Memoles Artikel Lama...</span>
                    </div>
                    <span id="batch-progress-percent" class="text-xs font-black text-purple-700">0%</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                    <div id="batch-progress-bar" class="bg-gradient-to-r from-purple-600 to-emerald-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="batch-progress-detail" class="text-[11px] text-slate-500 font-mono">Memulai antrean optimasi...</p>
            </div>

            <!-- TABEL DAFTAR ARTIKEL -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-50">
                    <div>
                        <h3 class="font-black text-slate-800 text-sm sm:text-base flex items-center gap-2">
                            <i class="fas fa-list-check text-purple-600"></i> Status Audit & Optimasi Seluruh Artikel
                        </h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Klik tombol poles pada artikel yang belum hijau untuk menyempurnakan otomatis.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="text" id="searchInput" onkeyup="filterTabelArtikel()" placeholder="Cari judul artikel..." class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 w-48 sm:w-64">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50/75">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold text-slate-500 uppercase">Artikel</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">Lampu SEO</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">Readability</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-slate-500 uppercase">Tanggal</th>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500 uppercase">Tindakan AI</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-artikel-body" class="divide-y divide-slate-100 bg-white">
                            <?php if (count($semua_artikel) > 0): ?>
                                <?php foreach ($semua_artikel as $art): ?>
                                <?php 
                                    $is_green = ($art['yoast_seo_score'] === 'green' && $art['yoast_readability_score'] === 'green');
                                    $seo_badge = ($art['yoast_seo_score'] === 'green') ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : (($art['yoast_seo_score'] === 'amber') ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-rose-100 text-rose-800 border-rose-200');
                                    $read_badge = ($art['yoast_readability_score'] === 'green') ? 'bg-emerald-100 text-emerald-800 border-emerald-200' : (($art['yoast_readability_score'] === 'amber') ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-rose-100 text-rose-800 border-rose-200');
                                    $seo_label = ($art['yoast_seo_score'] === 'green') ? '🟢 Good' : (($art['yoast_seo_score'] === 'amber') ? '🟡 OK' : '🔴 Perlu Edit');
                                    $read_label = ($art['yoast_readability_score'] === 'green') ? '🟢 Good' : (($art['yoast_readability_score'] === 'amber') ? '🟡 OK' : '🔴 Perlu Edit');
                                ?>
                                <tr id="row-art-<?= $art['id'] ?>" class="hover:bg-slate-50/80 transition row-artikel" data-title="<?= strtolower(htmlspecialchars($art['judul'])) ?>">
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900 text-sm max-w-md truncate"><?= htmlspecialchars($art['judul']) ?></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-mono">
                                                ID #<?= $art['id'] ?>
                                            </span>
                                            <a href="artikel.php?slug=<?= htmlspecialchars($art['slug']) ?>" target="_blank" class="text-[10px] text-teal-700 font-bold hover:underline">
                                                Lihat di Web <i class="fas fa-external-link-alt text-[8px]"></i>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                        <span id="badge-seo-<?= $art['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border <?= $seo_badge ?>">
                                            <?= $seo_label ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-center">
                                        <span id="badge-read-<?= $art['id'] ?>" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border <?= $read_badge ?>">
                                            <?= $read_label ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 whitespace-nowrap text-center text-xs text-slate-500 font-medium">
                                        <?= date('d M Y', strtotime($art['created_at'])) ?>
                                    </td>
                                    <td class="px-5 py-3.5 whitespace-nowrap text-right">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="bukaModalAudit(<?= $art['id'] ?>)" class="px-2.5 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition" title="Lihat Checklist Audit Yoast">
                                                <i class="fas fa-clipboard-check"></i>
                                            </button>
                                            <button id="btn-poles-<?= $art['id'] ?>" type="button" onclick="polesSatuArtikel(<?= $art['id'] ?>)" class="px-3.5 py-1.5 rounded-xl <?= $is_green ? 'bg-slate-100 text-slate-400 cursor-default' : 'bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white shadow-xs hover:shadow-md active:scale-95' ?> text-xs font-black transition flex items-center gap-1.5">
                                                <i class="fas <?= $is_green ? 'fa-check text-emerald-500' : 'fa-wand-magic-sparkles' ?>"></i>
                                                <span><?= $is_green ? 'Sudah Hijau' : 'Poles Jadi Hijau 🟢' ?></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-slate-400 text-xs font-medium">Belum ada artikel terdaftar di database.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL DETAIL AUDIT CHECKLIST YOAST -->
    <div id="modal-audit" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[85vh]">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h3 id="modal-audit-title" class="font-black text-slate-900 text-sm sm:text-base leading-tight">Detail Audit Yoast SEO</h3>
                        <p class="text-[10px] text-slate-500">Hasil diagnosa On-Page SEO & Keterbacaan</p>
                    </div>
                </div>
                <button onclick="tutupModalAudit()" class="text-slate-400 hover:text-slate-600 p-1"><i class="fas fa-times text-lg"></i></button>
            </div>
            
            <div id="modal-audit-content" class="p-5 overflow-y-auto custom-scrollbar space-y-4 flex-1 text-xs sm:text-sm">
                <div class="flex items-center justify-center py-10 text-purple-600">
                    <i class="fas fa-spinner fa-spin text-2xl mr-2"></i> Menganalisa parameter artikel...
                </div>
            </div>

            <div class="p-4 border-t border-slate-100 flex justify-end gap-2 bg-slate-50">
                <button onclick="tutupModalAudit()" class="px-4 py-2 rounded-xl bg-slate-200 text-slate-700 text-xs font-bold">Tutup</button>
            </div>
        </div>
    </div>

    <!-- SCRIPT CLIENT ENGINE -->
    <script>
        // 1. Filter Cari Artikel
        function filterTabelArtikel() {
            const val = document.getElementById('searchInput').value.toLowerCase();
            document.querySelectorAll('.row-artikel').forEach(r => {
                const title = r.getAttribute('data-title');
                r.style.display = title.includes(val) ? '' : 'none';
            });
        }

        // 2. Poles 1 Artikel via AJAX
        function polesSatuArtikel(articleId) {
            const btn = document.getElementById('btn-poles-' + articleId);
            if (!btn || btn.disabled) return;

            const oriHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memoles...';

            fetch('api-yoast-ai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'optimize', article_id: articleId, save_to_db: true })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update Badge Lampu di Tabel
                    const badgeSeo = document.getElementById('badge-seo-' + articleId);
                    const badgeRead = document.getElementById('badge-read-' + articleId);
                    if (badgeSeo) {
                        badgeSeo.className = "inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-200";
                        badgeSeo.textContent = "🟢 Good";
                    }
                    if (badgeRead) {
                        badgeRead.className = "inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-200";
                        badgeRead.textContent = "🟢 Good";
                    }
                    btn.className = "px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-400 cursor-default text-xs font-black transition flex items-center gap-1.5";
                    btn.innerHTML = '<i class="fas fa-check text-emerald-500"></i> <span>Sudah Hijau</span>';
                    
                    // Update counter
                    updateStatCounter();
                    alert("Berhasil! Artikel ID #" + articleId + " telah dipoles dan berstatus All Green 🟢🟢:\n- " + (data.perubahan || 'Tautan dan keterbacaan telah disempurnakan.'));
                } else {
                    alert("Gagal memoles artikel: " + (data.message || 'Terjadi kesalahan sistem'));
                    btn.disabled = false;
                    btn.innerHTML = oriHTML;
                }
            })
            .catch(err => {
                alert("Koneksi gagal: " + err.message);
                btn.disabled = false;
                btn.innerHTML = oriHTML;
            });
        }

        // 3. Batch Optimizer Borongan
        let batchQueue = [];
        let batchCurrentIndex = 0;

        function jalankanBatchOptimize() {
            // Ambil semua ID artikel yang belum hijau
            batchQueue = [];
            document.querySelectorAll('.row-artikel').forEach(r => {
                const id = r.id.replace('row-art-', '');
                const btn = document.getElementById('btn-poles-' + id);
                if (btn && !btn.textContent.includes('Sudah Hijau')) {
                    batchQueue.push(parseInt(id));
                }
            });

            if (batchQueue.length === 0) {
                alert("Hebat! Semua artikel di website sudah berstatus Hijau (All Green 🟢🟢). Tidak ada yang perlu dipoles!");
                return;
            }

            if (!confirm("Agent AI Yoast SEO akan memproses " + batchQueue.length + " artikel lama secara bertahap sampai semuanya berstatus Hijau 🟢🟢. Lanjutkan?")) {
                return;
            }

            batchCurrentIndex = 0;
            document.getElementById('batch-progress-box').classList.remove('hidden');
            prosesAntreanBerikutnya();
        }

        function prosesAntreanBerikutnya() {
            if (batchCurrentIndex >= batchQueue.length) {
                document.getElementById('batch-progress-title').innerHTML = '<i class="fas fa-circle-check text-emerald-500"></i> Seluruh Artikel Berhasil Dipoles Sempurna!';
                document.getElementById('batch-progress-percent').textContent = '100%';
                document.getElementById('batch-progress-bar').style.width = '100%';
                document.getElementById('batch-progress-detail').textContent = 'Semua artikel lama kini lulus standar Google E-E-A-T & Yoast SEO.';
                updateStatCounter();
                setTimeout(() => { alert("Selesai! Seluruh artikel lama telah sukses dipoles menjadi All Green 🟢🟢!"); }, 500);
                return;
            }

            const currentId = batchQueue[batchCurrentIndex];
            const percent = Math.round((batchCurrentIndex / batchQueue.length) * 100);
            document.getElementById('batch-progress-bar').style.width = percent + '%';
            document.getElementById('batch-progress-percent').textContent = percent + '%';
            document.getElementById('batch-progress-detail').textContent = "Memoles artikel ID #" + currentId + " (" + (batchCurrentIndex + 1) + " dari " + batchQueue.length + ")...";

            fetch('api-yoast-ai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'optimize', article_id: currentId, save_to_db: true })
            })
            .then(res => res.json())
            .then(data => {
                const badgeSeo = document.getElementById('badge-seo-' + currentId);
                const badgeRead = document.getElementById('badge-read-' + currentId);
                const btn = document.getElementById('btn-poles-' + currentId);
                if (badgeSeo) {
                    badgeSeo.className = "inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-200";
                    badgeSeo.textContent = "🟢 Good";
                }
                if (badgeRead) {
                    badgeRead.className = "inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold border bg-emerald-100 text-emerald-800 border-emerald-200";
                    badgeRead.textContent = "🟢 Good";
                }
                if (btn) {
                    btn.className = "px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-400 cursor-default text-xs font-black transition flex items-center gap-1.5";
                    btn.innerHTML = '<i class="fas fa-check text-emerald-500"></i> <span>Sudah Hijau</span>';
                }
                batchCurrentIndex++;
                setTimeout(prosesAntreanBerikutnya, 2000); // jeda 2 detik per artikel agar aman rate limit
            })
            .catch(err => {
                console.error("Gagal ID #" + currentId, err);
                batchCurrentIndex++;
                setTimeout(prosesAntreanBerikutnya, 2000);
            });
        }

        // 4. Modal Checklist Detail
        function bukaModalAudit(articleId) {
            const modal = document.getElementById('modal-audit');
            const content = document.getElementById('modal-audit-content');
            modal.classList.remove('hidden');
            content.innerHTML = '<div class="flex items-center justify-center py-10 text-purple-600"><i class="fas fa-spinner fa-spin text-2xl mr-2"></i> Menganalisa parameter artikel...</div>';

            fetch('api-yoast-ai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'audit', article_id: articleId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const d = data.data;
                    let html = `
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <div class="p-3.5 rounded-2xl border ${d.seo_status === 'green' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800'}">
                                <span class="text-[10px] font-black uppercase tracking-wider block">Skor SEO</span>
                                <span class="text-base font-black">${d.seo_status === 'green' ? '🟢 Sempurna (Good)' : '🟡 Perlu Pemolesan'}</span>
                            </div>
                            <div class="p-3.5 rounded-2xl border ${d.readability_status === 'green' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-amber-50 border-amber-200 text-amber-800'}">
                                <span class="text-[10px] font-black uppercase tracking-wider block">Keterbacaan (Readability)</span>
                                <span class="text-base font-black">${d.readability_status === 'green' ? '🟢 Sangat Baik (Good)' : '🟡 Perlu Pemolesan'}</span>
                            </div>
                        </div>

                        <div>
                            <h4 class="font-black text-slate-800 mb-2 flex items-center gap-1.5"><i class="fas fa-search text-purple-600"></i> Checklist Parameter SEO:</h4>
                            <div class="space-y-1.5">
                    `;

                    d.checklist_seo.forEach(c => {
                        const icon = c.status === 'pass' ? 'fa-circle-check text-emerald-500' : (c.status === 'warning' ? 'fa-triangle-exclamation text-amber-500' : 'fa-circle-xmark text-rose-500');
                        html += `
                            <div class="flex items-start gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <i class="fas ${icon} mt-0.5"></i>
                                <div><b class="text-slate-800">${c.name}</b>: <span class="text-slate-600">${c.text}</span></div>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                        </div>

                        <div class="pt-2">
                            <h4 class="font-black text-slate-800 mb-2 flex items-center gap-1.5"><i class="fas fa-book-open text-purple-600"></i> Checklist Keterbacaan (Readability):</h4>
                            <div class="space-y-1.5">
                    `;

                    d.checklist_readability.forEach(c => {
                        const icon = c.status === 'pass' ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-rose-500';
                        html += `
                            <div class="flex items-start gap-2 p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                                <i class="fas ${icon} mt-0.5"></i>
                                <div><b class="text-slate-800">${c.name}</b>: <span class="text-slate-600">${c.text}</span></div>
                            </div>
                        `;
                    });

                    html += `
                            </div>
                        </div>
                    `;

                    content.innerHTML = html;
                } else {
                    content.innerHTML = `<div class="p-4 text-rose-600">${data.message || 'Gagal memuat diagnosa'}</div>`;
                }
            })
            .catch(err => {
                content.innerHTML = `<div class="p-4 text-rose-600">Gagal terhubung: ${err.message}</div>`;
            });
        }

        function tutupModalAudit() {
            document.getElementById('modal-audit').classList.add('hidden');
        }

        function updateStatCounter() {
            let totalHijau = 0;
            let totalAll = 0;
            document.querySelectorAll('.row-artikel').forEach(r => {
                totalAll++;
                const id = r.id.replace('row-art-', '');
                const btn = document.getElementById('btn-poles-' + id);
                if (btn && btn.textContent.includes('Sudah Hijau')) totalHijau++;
            });
            document.getElementById('stat-tot-hijau').textContent = totalHijau;
            document.getElementById('stat-tot-perlu').textContent = totalAll - totalHijau;
        }
    </script>
</body>
</html>
