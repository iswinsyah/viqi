<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Cek hak akses role
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', strtolower($_SESSION['ustadz_role'])) : [];
$ustadz_id = (int)$_SESSION['ustadz_id'];
$ustadz_nama = $_SESSION['ustadz_nama'] ?? 'Trainer';

// Self-healing tabel jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS kurikulum_solopreneur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    model_pelaksanaan VARCHAR(100) DEFAULT 'Bootcamp Gabungan (SMP + SMA)',
    target_jenjang VARCHAR(50) DEFAULT 'SMP dan SMA',
    durasi_target VARCHAR(100) DEFAULT '1 Tahun (4 Kuartal / Sprint)',
    total_santri INT DEFAULT 32,
    fokus_bisnis VARCHAR(150) DEFAULT 'Digital Product & Agentic Automation',
    prinsip_utama VARCHAR(255) DEFAULT 'Membangun Bisnis Selama Sekolah',
    konten_kurikulum LONGTEXT NOT NULL,
    status_publish VARCHAR(50) DEFAULT 'Draft',
    created_by VARCHAR(100) DEFAULT 'Yayasan',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS solopreneur_milestone_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kurikulum_id INT NOT NULL,
    nama_kelompok VARCHAR(150) NOT NULL,
    jenjang VARCHAR(50) NOT NULL,
    milestone_ke VARCHAR(100) NOT NULL,
    status_capaian VARCHAR(50) DEFAULT 'Belum Selesai',
    catatan_mentor TEXT,
    tanggal_update DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handling AJAX Requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_published') {
        $res = $conn->query("SELECT id, judul, target_jenjang, durasi_target, DATE_FORMAT(updated_at, '%d %b %Y') as tgl FROM kurikulum_solopreneur WHERE status_publish = 'Published' ORDER BY id DESC");
        $list = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $list]);
        exit;
    }

    if ($action === 'get_detail') {
        $id = (int)($_GET['id'] ?? 0);
        $res = $conn->query("SELECT * FROM kurikulum_solopreneur WHERE id = $id AND status_publish = 'Published'");
        if ($res && $res->num_rows > 0) {
            echo json_encode(['status' => 'success', 'data' => $res->fetch_assoc()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Kurikulum belum di-publish atau tidak ditemukan.']);
        }
        exit;
    }

    if ($action === 'get_milestones') {
        $kurikulum_id = (int)($_GET['kurikulum_id'] ?? 0);
        $res = $conn->query("SELECT * FROM solopreneur_milestone_log WHERE kurikulum_id = $kurikulum_id ORDER BY jenjang ASC, id ASC");
        $list = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $list[] = $row;
            }
        }
        echo json_encode(['status' => 'success', 'data' => $list]);
        exit;
    }

    if ($action === 'save_milestone') {
        $id = (int)($_POST['id'] ?? 0);
        $kurikulum_id = (int)($_POST['kurikulum_id'] ?? 0);
        $nama_kelompok = $conn->real_escape_string($_POST['nama_kelompok'] ?? '');
        $jenjang = $conn->real_escape_string($_POST['jenjang'] ?? 'SMP');
        $milestone_ke = $conn->real_escape_string($_POST['milestone_ke'] ?? '');
        $status_capaian = $conn->real_escape_string($_POST['status_capaian'] ?? 'Selesai');
        $catatan_mentor = $conn->real_escape_string($_POST['catatan_mentor'] ?? '');
        $tgl = date('Y-m-d');

        if (empty($nama_kelompok) || empty($milestone_ke)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama kelompok/santri dan milestone harus diisi!']);
            exit;
        }

        if ($id > 0) {
            $sql = "UPDATE solopreneur_milestone_log SET 
                    nama_kelompok = '$nama_kelompok', 
                    jenjang = '$jenjang', 
                    milestone_ke = '$milestone_ke', 
                    status_capaian = '$status_capaian', 
                    catatan_mentor = '$catatan_mentor', 
                    tanggal_update = '$tgl' 
                    WHERE id = $id";
        } else {
            $sql = "INSERT INTO solopreneur_milestone_log (kurikulum_id, nama_kelompok, jenjang, milestone_ke, status_capaian, catatan_mentor, tanggal_update) 
                    VALUES ($kurikulum_id, '$nama_kelompok', '$jenjang', '$milestone_ke', '$status_capaian', '$catatan_mentor', '$tgl')";
        }

        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Progress pencapaian santri berhasil disimpan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan: ' . $conn->error]);
        }
        exit;
    }

    if ($action === 'delete_milestone') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM solopreneur_milestone_log WHERE id = $id");
        echo json_encode(['status' => 'success', 'message' => 'Catatan milestone dihapus.']);
        exit;
    }
}

$active_menu = 'kurikulum_solopreneur_trainer';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inkubator Solopreneur (AI) | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1 { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 2px solid #06b6d4; padding-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem; }
        .markdown-body h1::before { content: "\f542"; font-family: "Font Awesome 6 Free"; font-weight: 900; color: #0891b2; }
        .markdown-body h2 { font-size: 1.2rem; font-weight: 700; color: #0284c7; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.05rem; font-weight: 600; color: #334155; margin-top: 1rem; margin-bottom: 0.25rem; }
        .markdown-body p { margin-bottom: 0.75rem; line-height: 1.625; color: #475569; text-align: justify; }
        .markdown-body ul, .markdown-body ol { margin-left: 1.5rem; margin-bottom: 0.75rem; }
        .markdown-body ul { list-style-type: disc; }
        .markdown-body ol { list-style-type: decimal; }
        .markdown-body li { margin-bottom: 0.25rem; color: #475569; }
        .markdown-body strong { color: #0f172a; font-weight: 700; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.85rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.6rem; text-align: left; }
        .markdown-body th { background-color: #e0f2fe; color: #0369a1; font-weight: 700; }
        .markdown-body tr:nth-child(even) { background-color: #f8fafc; }
        .markdown-body blockquote { border-left: 4px solid #06b6d4; padding-left: 1rem; color: #64748b; font-style: italic; margin: 1rem 0; background: #ecfeff; padding-top: 0.5rem; padding-bottom: 0.5rem; }
        @media print {
            aside, header, #sidebar-hr, .no-print { display: none !important; }
            body, main, #content-area { width: 100% !important; margin: 0 !important; padding: 0 !important; background: white !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-slate-200">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 hidden sm:flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-cyan-500 animate-pulse"></span>
                    Ruang Asatidz — Portal Trainer Inkubator Solopreneur (AI)
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-semibold px-3 py-1 bg-cyan-50 text-cyan-800 rounded-full border border-cyan-200 hidden md:inline-flex items-center gap-1.5">
                    <i class="fas fa-user-tie text-cyan-600"></i> Role: Trainer / Instruktur
                </span>
                <select id="curriculum-select" onchange="switchCurriculum(this.value)" class="bg-slate-900 text-white rounded-lg px-3 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-cyan-500 shadow-sm">
                    <option value="">-- Memuat Kurikulum --</option>
                </select>
            </div>
        </header>

        <!-- SUB NAV / TABS -->
        <div class="bg-white border-b border-slate-200 px-6 pt-3 flex gap-4 text-xs font-bold no-print flex-shrink-0">
            <button id="tab-btn-silabus" onclick="switchTab('silabus')" class="pb-3 border-b-2 border-cyan-600 text-cyan-700 flex items-center gap-1.5 transition">
                <i class="fas fa-book-open text-cyan-500"></i> 📖 Silabus & Prompt Cheat-Sheet
            </button>
            <button id="tab-btn-tracker" onclick="switchTab('tracker')" class="pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-1.5 transition">
                <i class="fas fa-tasks text-emerald-500"></i> 🎯 Milestone & Progress Tracker Santri
            </button>
            <div class="ml-auto pb-3 text-slate-400 text-[11px] hidden sm:block">
                Prinsip: <strong class="text-slate-700">"Membangun Bisnis Selama Sekolah"</strong>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6" id="content-area">
            
            <!-- TAB 1: SILABUS & PROMPT CHEAT-SHEET -->
            <div id="tab-silabus" class="space-y-6">
                <div class="bg-gradient-to-r from-slate-900 via-cyan-950 to-slate-900 p-6 rounded-2xl text-white shadow-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span class="text-[10px] uppercase font-bold tracking-widest bg-cyan-500/20 text-cyan-300 px-2.5 py-1 rounded-full border border-cyan-500/30">
                            <i class="fas fa-robot mr-1"></i> Official Published Curriculum
                        </span>
                        <h1 class="text-xl font-extrabold mt-2 text-white" id="judul-kurikulum">Memuat Kurikulum...</h1>
                        <p class="text-xs text-slate-300 mt-1" id="meta-kurikulum">Fokus: Membangun Bisnis Solopreneur Otomatis Berbasis AI Agent</p>
                    </div>
                    <button onclick="window.print()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl text-xs font-bold border border-white/20 transition flex items-center gap-2 no-print shadow">
                        <i class="fas fa-print text-cyan-300"></i> Cetak Panduan Trainer
                    </button>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-slate-200/80 min-h-[400px]">
                    <div id="silabus-loading" class="text-center py-20 text-slate-400">
                        <i class="fas fa-circle-notch animate-spin text-3xl text-cyan-500 mb-3"></i>
                        <p class="text-xs font-semibold">Mengambil silabus dari database Yayasan...</p>
                    </div>
                    <div id="silabus-content" class="hidden markdown-body"></div>
                </div>
            </div>

            <!-- TAB 2: MILESTONE & PROGRESS TRACKER -->
            <div id="tab-tracker" class="hidden space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200/80 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <i class="fas fa-chart-line text-emerald-600"></i> Log Capaian Bisnis Santri (Milestone Tracker)
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pantau dan centang pencapaian kelompok santri (Misal: Rilis E-Book, Landing Page Online, AI Chatbot Aktif, Omzet Pertama).
                        </p>
                    </div>
                    <button onclick="openModalMilestone()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-4 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-2">
                        <i class="fas fa-plus"></i> Catat Progress Baru
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- KOLOM SMP (CREATOR) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="bg-sky-50 px-5 py-3 border-b border-sky-100 flex justify-between items-center">
                            <h3 class="font-bold text-sky-900 text-sm flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                                Tingkat SMP (AI Digital Creator & Asset Builder)
                            </h3>
                            <span class="text-[11px] font-bold bg-sky-200 text-sky-800 px-2 py-0.5 rounded-full">Fokus Karya Digital</span>
                        </div>
                        <div class="p-5 space-y-3" id="list-milestone-smp">
                            <p class="text-center text-xs text-slate-400 py-6">Belum ada catatan progress untuk kelompok SMP.</p>
                        </div>
                    </div>

                    <!-- KOLOM SMA (AUTOMATOR / SOLOPRENEUR) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
                        <div class="bg-indigo-50 px-5 py-3 border-b border-indigo-100 flex justify-between items-center">
                            <h3 class="font-bold text-indigo-900 text-sm flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                                Tingkat SMA (AI Agent Architect & System Automator)
                            </h3>
                            <span class="text-[11px] font-bold bg-indigo-200 text-indigo-800 px-2 py-0.5 rounded-full">Fokus Autopilot & Revenue</span>
                        </div>
                        <div class="p-5 space-y-3" id="list-milestone-sma">
                            <p class="text-center text-xs text-slate-400 py-6">Belum ada catatan progress untuk kelompok SMA.</p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM MILESTONE -->
    <div id="modal-milestone" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-200">
            <div class="flex justify-between items-center pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                    <i class="fas fa-check-circle text-emerald-500"></i> Update Capaian Bisnis Santri
                </h3>
                <button onclick="closeModalMilestone()" class="text-slate-400 hover:text-slate-600"><i class="fas fa-times"></i></button>
            </div>

            <form id="form-milestone" onsubmit="return false;" class="space-y-4 mt-4">
                <input type="hidden" id="mile-id" value="0">
                <input type="hidden" id="mile-kurikulum-id" value="0">

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Jenjang / Kelompok</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select id="mile-jenjang" class="bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-cyan-500">
                            <option value="SMP">SMP (Creator)</option>
                            <option value="SMA">SMA (Automator)</option>
                        </select>
                        <input type="text" id="mile-kelompok" placeholder="Nama Kelompok / Santri (Misal: Tim Al-Fatih)" class="bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-cyan-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Tahapan Milestone (Target Output)</label>
                    <select id="mile-tahap" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-cyan-500">
                        <option value="Sprint 1: Ideation & Prompt Creation (Produk Digital Jadi)">Sprint 1: Ideation & Prompt Creation (Produk Digital Jadi)</option>
                        <option value="Sprint 2: Branding & Landing Page Online">Sprint 2: Branding & Landing Page Online</option>
                        <option value="Sprint 3: Integrasi AI Agent (CS Bot & Automasi)">Sprint 3: Integrasi AI Agent (CS Bot & Automasi)</option>
                        <option value="Sprint 4: Transaksi Nyata Pertama (First Revenue)">Sprint 4: Transaksi Nyata Pertama (First Revenue)</option>
                        <option value="Syarat Kelulusan: Siap Presentasi di Demo Day">Syarat Kelulusan: Siap Presentasi di Demo Day</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status Pencapaian</label>
                    <select id="mile-status" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-cyan-500">
                        <option value="Selesai (Tuntas)">✔ Selesai (Tuntas)</option>
                        <option value="Dalam Proses / Eksperimen">⏳ Dalam Proses / Eksperimen</option>
                        <option value="Perlu Bimbingan Tambahan">💡 Perlu Bimbingan Tambahan</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Trainer / Link Hasil Karya</label>
                    <textarea id="mile-catatan" rows="3" placeholder="Masukkan catatan, masukan, atau link produk/toko online santri..." class="w-full bg-slate-50 border border-slate-300 rounded-lg p-2.5 text-xs focus:ring-2 focus:ring-cyan-500"></textarea>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" onclick="closeModalMilestone()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2 rounded-xl text-xs">Batal</button>
                    <button type="button" onclick="saveMilestone()" class="bg-emerald-600 hover:bg-emerald-700 text-white font-black px-5 py-2 rounded-xl text-xs shadow-md">Simpan Capaian</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentCurriculums = [];
        let activeCurriculumId = 0;

        document.addEventListener("DOMContentLoaded", function() {
            loadPublishedCurriculums();
            const sidebarBtn = document.getElementById('open-sidebar-hr');
            const sidebar = document.getElementById('sidebar-hr');
            const overlay = document.getElementById('sidebar-overlay-hr');
            if (sidebarBtn && sidebar && overlay) {
                sidebarBtn.addEventListener('click', () => {
                    sidebar.classList.remove('hidden');
                    overlay.classList.remove('hidden');
                });
                overlay.addEventListener('click', () => {
                    sidebar.classList.add('hidden');
                    overlay.classList.add('hidden');
                });
            }
        });

        function switchTab(tab) {
            const btnSilabus = document.getElementById('tab-btn-silabus');
            const btnTracker = document.getElementById('tab-btn-tracker');
            const divSilabus = document.getElementById('tab-silabus');
            const divTracker = document.getElementById('tab-tracker');

            if (tab === 'silabus') {
                btnSilabus.className = "pb-3 border-b-2 border-cyan-600 text-cyan-700 flex items-center gap-1.5 transition";
                btnTracker.className = "pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-1.5 transition";
                divSilabus.classList.remove('hidden');
                divTracker.classList.add('hidden');
            } else {
                btnTracker.className = "pb-3 border-b-2 border-emerald-600 text-emerald-700 flex items-center gap-1.5 transition";
                btnSilabus.className = "pb-3 border-b-2 border-transparent text-slate-500 hover:text-slate-800 flex items-center gap-1.5 transition";
                divTracker.classList.remove('hidden');
                divSilabus.classList.add('hidden');
                loadMilestones(activeCurriculumId);
            }
        }

        function loadPublishedCurriculums() {
            fetch('trainer-kurikulum-solopreneur.php?action=get_published')
                .then(res => res.json())
                .then(res => {
                    const sel = document.getElementById('curriculum-select');
                    sel.innerHTML = '';
                    if (res.status === 'success' && res.data.length > 0) {
                        currentCurriculums = res.data;
                        res.data.forEach((item, idx) => {
                            const opt = document.createElement('option');
                            opt.value = item.id;
                            opt.textContent = `🚀 ${item.judul} (${item.durasi_target})`;
                            sel.appendChild(opt);
                        });
                        switchCurriculum(res.data[0].id);
                    } else {
                        sel.innerHTML = '<option value="">⚠️ Belum ada kurikulum di-publish</option>';
                        document.getElementById('silabus-loading').innerHTML = `
                            <div class="py-12">
                                <i class="fas fa-exclamation-triangle text-amber-500 text-4xl mb-3"></i>
                                <h4 class="font-bold text-slate-700 text-base">Belum Ada Kurikulum yang Disahkan Yayasan</h4>
                                <p class="text-xs text-slate-500 mt-1">Silakan minta Admin Yayasan untuk melakukan Generate & Publish di menu Master Kurikulum Solopreneur.</p>
                            </div>
                        `;
                    }
                })
                .catch(err => console.error("Error loading curriculums:", err));
        }

        function switchCurriculum(id) {
            if (!id) return;
            activeCurriculumId = id;
            document.getElementById('silabus-loading').classList.remove('hidden');
            document.getElementById('silabus-content').classList.add('hidden');

            fetch(`trainer-kurikulum-solopreneur.php?action=get_detail&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    document.getElementById('silabus-loading').classList.add('hidden');
                    if (res.status === 'success') {
                        const d = res.data;
                        document.getElementById('judul-kurikulum').textContent = d.judul;
                        document.getElementById('meta-kurikulum').textContent = `Target: ${d.target_jenjang} | Durasi: ${d.durasi_target} | Prinsip: ${d.prinsip_utama}`;
                        
                        document.getElementById('silabus-content').innerHTML = marked.parse(d.konten_kurikulum);
                        document.getElementById('silabus-content').classList.remove('hidden');
                        loadMilestones(id);
                    }
                });
        }

        function loadMilestones(kurikulumId) {
            if (!kurikulumId) return;
            fetch(`trainer-kurikulum-solopreneur.php?action=get_milestones&kurikulum_id=${kurikulumId}`)
                .then(res => res.json())
                .then(res => {
                    const smpDiv = document.getElementById('list-milestone-smp');
                    const smaDiv = document.getElementById('list-milestone-sma');
                    smpDiv.innerHTML = '';
                    smaDiv.innerHTML = '';

                    let smpCount = 0;
                    let smaCount = 0;

                    if (res.status === 'success' && res.data.length > 0) {
                        res.data.forEach(item => {
                            const card = document.createElement('div');
                            card.className = "p-3.5 bg-slate-50 rounded-xl border border-slate-200/80 flex flex-col gap-1.5 hover:shadow-sm transition relative group";
                            
                            let badgeColor = "bg-amber-100 text-amber-800";
                            if (item.status_capaian.includes('Selesai')) badgeColor = "bg-emerald-100 text-emerald-800 font-bold";
                            if (item.status_capaian.includes('Bimbingan')) badgeColor = "bg-rose-100 text-rose-800";

                            card.innerHTML = `
                                <div class="flex justify-between items-start gap-2">
                                    <h4 class="font-bold text-slate-800 text-xs flex items-center gap-1.5">
                                        <i class="fas fa-users text-slate-400"></i> ${item.nama_kelompok}
                                    </h4>
                                    <span class="text-[10px] px-2 py-0.5 rounded-md ${badgeColor}">${item.status_capaian}</span>
                                </div>
                                <p class="text-xs font-semibold text-slate-700">${item.milestone_ke}</p>
                                ${item.catatan_mentor ? `<p class="text-[11px] text-slate-500 italic bg-white p-2 rounded-lg border border-slate-100">💡 "${item.catatan_mentor}"</p>` : ''}
                                <div class="flex justify-between items-center pt-1 text-[10px] text-slate-400">
                                    <span>Diupdate: ${item.tanggal_update}</span>
                                    <div class="space-x-2">
                                        <button onclick='editMilestone(${JSON.stringify(item)})' class="text-cyan-600 hover:text-cyan-800 font-bold">Edit</button>
                                        <button onclick="deleteMilestone(${item.id})" class="text-rose-500 hover:text-rose-700 font-bold">Hapus</button>
                                    </div>
                                </div>
                            `;

                            if (item.jenjang === 'SMP') {
                                smpDiv.appendChild(card);
                                smpCount++;
                            } else {
                                smaDiv.appendChild(card);
                                smaCount++;
                            }
                        });
                    }

                    if (smpCount === 0) smpDiv.innerHTML = '<p class="text-center text-xs text-slate-400 py-6">Belum ada catatan progress untuk kelompok SMP.</p>';
                    if (smaCount === 0) smaDiv.innerHTML = '<p class="text-center text-xs text-slate-400 py-6">Belum ada catatan progress untuk kelompok SMA.</p>';
                });
        }

        function openModalMilestone() {
            document.getElementById('mile-id').value = "0";
            document.getElementById('mile-kurikulum-id').value = activeCurriculumId;
            document.getElementById('mile-kelompok').value = "";
            document.getElementById('mile-catatan').value = "";
            document.getElementById('modal-milestone').classList.remove('hidden');
            document.getElementById('modal-milestone').classList.add('flex');
        }

        function editMilestone(item) {
            document.getElementById('mile-id').value = item.id;
            document.getElementById('mile-kurikulum-id').value = item.kurikulum_id;
            document.getElementById('mile-jenjang').value = item.jenjang;
            document.getElementById('mile-kelompok').value = item.nama_kelompok;
            document.getElementById('mile-tahap').value = item.milestone_ke;
            document.getElementById('mile-status').value = item.status_capaian;
            document.getElementById('mile-catatan').value = item.catatan_mentor;
            document.getElementById('modal-milestone').classList.remove('hidden');
            document.getElementById('modal-milestone').classList.add('flex');
        }

        function closeModalMilestone() {
            document.getElementById('modal-milestone').classList.add('hidden');
            document.getElementById('modal-milestone').classList.remove('flex');
        }

        function saveMilestone() {
            const formData = new FormData();
            formData.append('id', document.getElementById('mile-id').value);
            formData.append('kurikulum_id', document.getElementById('mile-kurikulum-id').value || activeCurriculumId);
            formData.append('jenjang', document.getElementById('mile-jenjang').value);
            formData.append('nama_kelompok', document.getElementById('mile-kelompok').value);
            formData.append('milestone_ke', document.getElementById('mile-tahap').value);
            formData.append('status_capaian', document.getElementById('mile-status').value);
            formData.append('catatan_mentor', document.getElementById('mile-catatan').value);

            fetch('trainer-kurikulum-solopreneur.php?action=save_milestone', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    closeModalMilestone();
                    loadMilestones(activeCurriculumId);
                } else {
                    alert("Error: " + res.message);
                }
            });
        }

        function deleteMilestone(id) {
            if (!confirm("Yakin ingin menghapus catatan capaian ini?")) return;
            const formData = new FormData();
            formData.append('id', id);
            fetch('trainer-kurikulum-solopreneur.php?action=delete_milestone', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                loadMilestones(activeCurriculumId);
            });
        }
    </script>
</body>
</html>
