<?php
require_once 'auth.php';
require_once '../koneksi.php';

// Inisialisasi Database (Self-Healing)
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

    if ($action === 'get_list') {
        $res = $conn->query("SELECT id, judul, target_jenjang, model_pelaksanaan, status_publish, DATE_FORMAT(updated_at, '%d %b %Y %H:%i') as tgl FROM kurikulum_solopreneur ORDER BY id DESC");
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
        $res = $conn->query("SELECT * FROM kurikulum_solopreneur WHERE id = $id");
        if ($res && $res->num_rows > 0) {
            echo json_encode(['status' => 'success', 'data' => $res->fetch_assoc()]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data kurikulum tidak ditemukan.']);
        }
        exit;
    }

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $judul = $conn->real_escape_string($_POST['judul'] ?? 'Kurikulum Solopreneur AI');
        $model_pelaksanaan = $conn->real_escape_string($_POST['model_pelaksanaan'] ?? 'Bootcamp Gabungan');
        $target_jenjang = $conn->real_escape_string($_POST['target_jenjang'] ?? 'SMP dan SMA');
        $durasi_target = $conn->real_escape_string($_POST['durasi_target'] ?? '1 Tahun');
        $total_santri = (int)($_POST['total_santri'] ?? 32);
        $fokus_bisnis = $conn->real_escape_string($_POST['fokus_bisnis'] ?? 'Digital Product & Agent Automation');
        $konten_kurikulum = $conn->real_escape_string($_POST['konten_kurikulum'] ?? '');
        $status_publish = $conn->real_escape_string($_POST['status_publish'] ?? 'Draft');

        if (empty($konten_kurikulum)) {
            echo json_encode(['status' => 'error', 'message' => 'Konten kurikulum tidak boleh kosong.']);
            exit;
        }

        if ($id > 0) {
            $sql = "UPDATE kurikulum_solopreneur SET 
                    judul = '$judul', 
                    model_pelaksanaan = '$model_pelaksanaan', 
                    target_jenjang = '$target_jenjang', 
                    durasi_target = '$durasi_target', 
                    total_santri = $total_santri, 
                    fokus_bisnis = '$fokus_bisnis', 
                    konten_kurikulum = '$konten_kurikulum', 
                    status_publish = '$status_publish' 
                    WHERE id = $id";
            if ($conn->query($sql)) {
                echo json_encode(['status' => 'success', 'id' => $id, 'message' => 'Kurikulum berhasil diperbarui!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate database: ' . $conn->error]);
            }
        } else {
            $sql = "INSERT INTO kurikulum_solopreneur (judul, model_pelaksanaan, target_jenjang, durasi_target, total_santri, fokus_bisnis, prinsip_utama, konten_kurikulum, status_publish) 
                    VALUES ('$judul', '$model_pelaksanaan', '$target_jenjang', '$durasi_target', $total_santri, '$fokus_bisnis', 'Membangun Bisnis Selama Sekolah', '$konten_kurikulum', '$status_publish')";
            if ($conn->query($sql)) {
                $new_id = $conn->insert_id;
                echo json_encode(['status' => 'success', 'id' => $new_id, 'message' => 'Kurikulum baru berhasil disimpan!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $conn->error]);
            }
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM kurikulum_solopreneur WHERE id = $id");
            $conn->query("DELETE FROM solopreneur_milestone_log WHERE kurikulum_id = $id");
            echo json_encode(['status' => 'success', 'message' => 'Kurikulum berhasil dihapus.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
        }
        exit;
    }

    if ($action === 'generate_ai') {
        // Build prompt dan tembak ke api-gemini.php
        $model = $_POST['model_pelaksanaan'] ?? 'Bootcamp Gabungan';
        $jenjang = $_POST['target_jenjang'] ?? 'SMP dan SMA';
        $durasi = $_POST['durasi_target'] ?? '1 Tahun (4 Kuartal / Sprint)';
        $santri = $_POST['total_santri'] ?? 32;
        $fokus = $_POST['fokus_bisnis'] ?? 'Produk Digital (E-Book, Template Canva, Affiliate) & AI Agent Automation';
        $fasilitator = $_POST['fasilitator'] ?? 'Guru Sekolah dibantu AI Mentor';

        $prompt = "Tindaklanjuti sebagai Pakar Kurikulum Sekolah Pesantren Modern & Praktisi Bisnis Solopreneur AI Agentic.\n\n" .
                  "Kami adalah Sekolah Tahfidz yang ingin memiliki nilai beda dan keunggulan kompetitif mutlak. " .
                  "Kami ingin merancang kurikulum praktis dengan prinsip utama: **\"MEMBANGUN BISNIS SELAMA SEKOLAH\"** (Bukan sekadar teori ekonomi atau koding biasa, tapi output nyata berpenghasilan / auto-pilot system).\n\n" .
                  "### KONDISI & PARAMETER SEKOLAH SAAT INI:\n" .
                  "- **Model Pelaksanaan:** $model\n" .
                  "- **Target Jenjang:** $jenjang\n" .
                  "- **Durasi Target:** $durasi\n" .
                  "- **Jumlah Santri:** $santri orang santri\n" .
                  "- **Fokus Model Bisnis:** $fokus\n" .
                  "- **Fasilitator/Pengajar:** $fasilitator\n" .
                  "- **Prinsip Utama:** Membangun Bisnis Selama Sekolah (Saat lulus santri bawa bisnis mandiri otomatis, tetap fokus hafal Al-Qur'an karena diurus AI Agent).\n\n" .
                  "### INSTRUKSI PENYUSUNAN KURIKULUM:\n" .
                  "Buatlah dokumen kurikulum yang sangat lengkap, inspiratif, profesional, dan langsung aplikatif dengan format Markdown (#, ##, ###, tabel, list, bold):\n" .
                  "1. **Visi & Strategi Eksekusi:** Mengapa kurikulum ini cocok untuk santri Tahfidz (pemisahan daya nalar SMP sebagai Creator vs SMA sebagai Agent/System Builder).\n" .
                  "2. **Peta Jalan Bisnis (Business Roadmap / Sprint):** Bagi durasi $durasi menjadi fase-fase (misal Kuartal 1 sampai 4) dengan target output karya digital & otomatisasi.\n" .
                  "3. **Silabus Operasional & Action Items:** Rincikan materi inti per fase/kuartal, aktivitas lab hands-on, dan standar kelulusan.\n" .
                  "4. **AI Toolkit & Prompt Cheat-Sheet untuk Trainer/Guru:** Kumpulan prompt siap pakai dan rekomendasi tools AI (No-code / Low-code / ChatGPT / Claude / Make / Zapier / Chatbot WA) yang harus diajarkan Trainer.\n" .
                  "5. **Skema Evaluasi & Demo Day (PSB Booster):** Panduan penyelenggaraan 'Solopreneur Demo Day' di akhir tahun sebagai pameran bisnis santri di depan Orang Tua Wali Murid untuk mendongkrak pendaftaran siswa baru (PSB).";

        // Panggil api-gemini.php secara internal untuk menghindari masalah loopback/CORS di server hosting
        $_POST['prompt'] = $prompt;
        include __DIR__ . '/../api-gemini.php';
        exit;
    }
}

$active_menu = 'kurikulum_solopreneur';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Solopreneur Curriculum Generator | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 2px solid #f59e0b; padding-bottom: 0.35rem; display: flex; align-items: center; gap: 0.5rem; }
        .markdown-body h1::before { content: "\f135"; font-family: "Font Awesome 6 Free"; font-weight: 900; color: #d97706; }
        .markdown-body h2 { font-size: 1.25rem; font-weight: 700; color: #b45309; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.1rem; font-weight: 600; color: #334155; margin-top: 1rem; margin-bottom: 0.25rem; }
        .markdown-body p { margin-bottom: 0.75rem; line-height: 1.625; color: #475569; text-align: justify; }
        .markdown-body ul, .markdown-body ol { margin-left: 1.5rem; margin-bottom: 0.75rem; }
        .markdown-body ul { list-style-type: disc; }
        .markdown-body ol { list-style-type: decimal; }
        .markdown-body li { margin-bottom: 0.25rem; color: #475569; }
        .markdown-body strong { color: #0f172a; font-weight: 700; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.85rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.6rem; text-align: left; }
        .markdown-body th { background-color: #fef3c7; color: #92400e; font-weight: 700; }
        .markdown-body tr:nth-child(even) { background-color: #f8fafc; }
        .markdown-body blockquote { border-left: 4px solid #f59e0b; padding-left: 1rem; color: #64748b; font-style: italic; margin: 1rem 0; background: #fffbeb; padding-top: 0.5rem; padding-bottom: 0.5rem; }
        @media print {
            aside, header, #form-container, .no-print { display: none !important; }
            body, main, #print-area { width: 100% !important; margin: 0 !important; padding: 0 !important; background: white !important; }
            .markdown-body { font-size: 12pt; color: #000; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-gray-200">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    Master Yayasan — AI Solopreneur Curriculum Generator
                </h2>
            </div>
            <div class="flex items-center space-x-2 md:space-x-4">
                <select id="curriculum-list" onchange="loadCurriculum(this.value)" class="bg-amber-50 border border-amber-300 text-amber-900 rounded-lg px-3 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-sm">
                    <option value="">-- 📚 Pilih Tersimpan --</option>
                </select>
                <button onclick="resetForm()" class="bg-amber-500 hover:bg-amber-600 text-gray-950 font-black px-3.5 py-1.5 rounded-lg text-xs transition shadow-sm flex items-center gap-1.5">
                    <i class="fas fa-plus"></i> Buat Baru
                </button>
            </div>
        </header>

        <!-- MAIN LAYOUT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-6 flex flex-col lg:flex-row gap-6">
            
            <!-- LEFT COLUMN: SETTINGS FORM -->
            <div id="form-container" class="flex-1 flex flex-col gap-5 lg:max-w-md flex-shrink-0">
                <div class="bg-gradient-to-br from-amber-900 via-slate-900 to-amber-950 p-5 rounded-2xl text-white shadow-xl relative overflow-hidden">
                    <div class="absolute right-0 top-0 -mr-8 -mt-8 w-32 h-32 bg-amber-500/20 rounded-full blur-2xl pointer-events-none"></div>
                    <span class="text-[10px] uppercase font-bold tracking-widest bg-amber-500/20 text-amber-300 px-2.5 py-1 rounded-full border border-amber-500/30">
                        <i class="fas fa-microchip mr-1"></i> Core Principle Locked
                    </span>
                    <h1 class="text-xl font-extrabold mt-2 tracking-tight flex items-center gap-2 text-white">
                        Membangun Bisnis Selama Sekolah
                    </h1>
                    <p class="text-xs text-slate-300 mt-1 leading-relaxed">
                        Setting parameter kondisi sekolah Anda dan biarkan AI merancang silabus, peta jalan, dan prompt instruktur untuk santri.
                    </p>
                </div>

                <form id="form-settings" onsubmit="return false;" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200/80 space-y-4">
                    <input type="hidden" id="curr-id" value="0">
                    
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Judul Kurikulum</label>
                        <input type="text" id="curr-judul" value="Kurikulum Inkubator Solopreneur AI 1 Tahun" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Model Pelaksanaan</label>
                            <select id="curr-model" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="Bootcamp Gabungan (SMP + SMA)">Bootcamp Gabungan (32 Santri)</option>
                                <option value="Kelas Terpisah (SMP & SMA)">Kelas Terpisah (SMP & SMA)</option>
                                <option value="Ekstrakurikuler Wajib">Ekstrakurikuler Wajib</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Target Jenjang</label>
                            <select id="curr-jenjang" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="SMP dan SMA (Pembedaan Target)">SMP & SMA (Pembedaan Target)</option>
                                <option value="Khusus SMP (Digital Creator)">Khusus SMP (Digital Creator)</option>
                                <option value="Khusus SMA (Agent Automator)">Khusus SMA (Agent Automator)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Durasi Target</label>
                            <select id="curr-durasi" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-2 text-xs font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                                <option value="1 Tahun (4 Kuartal / Sprint)">Akselerasi 1 Tahun (4 Sprint)</option>
                                <option value="2 Tahun (Per Semester)">Reguler 2 Tahun (4 Semester)</option>
                                <option value="3 Tahun Penuh">3 Tahun Penuh (Intensif)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Total Santri</label>
                            <input type="number" id="curr-santri" value="32" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-amber-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Fokus Model Bisnis Santri</label>
                        <select id="curr-fokus" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="Produk Digital (E-Book, Template, Affiliate) & AI Agent Automation">Produk Digital Tanpa Modal & AI Automation</option>
                            <option value="AI Content Creator, Micro-Media Network & AdSense">AI Content Creator & Media Network</option>
                            <option value="SaaS Reseller, AI Customer Service & Agency">SaaS Reseller & AI Chatbot Agency</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ketersediaan Fasilitator / Trainer</label>
                        <select id="curr-fasilitator" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-amber-500 focus:outline-none">
                            <option value="Guru Internal dibantu AI Mentor (Fasilitator Kelas)">1-2 Guru Internal (Sebagai Fasilitator) + AI Mentor</option>
                            <option value="Praktisi Eksternal & Mentor Khusus">Praktisi IT Eksternal & Mentor Khusus</option>
                        </select>
                    </div>

                    <div class="pt-3 border-t border-slate-200 flex flex-col gap-2.5">
                        <button onclick="generateAI()" class="w-full bg-gradient-to-r from-amber-500 via-amber-600 to-amber-500 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-black py-3 px-4 rounded-xl shadow-lg transition flex items-center justify-center gap-2 transform active:scale-[0.98]">
                            <i class="fas fa-magic text-base animate-spin" id="icon-magic"></i>
                            <span>✨ GENERATE KURIKULUM AI</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN: PREVIEW & PUBLISH ACTIONS -->
            <div id="print-area" class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden min-h-[600px]">
                <div class="px-6 py-4 bg-slate-900 text-white flex flex-wrap justify-between items-center gap-4 flex-shrink-0 no-print">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-file-alt text-amber-400 text-lg"></i>
                        <div>
                            <h3 class="font-bold text-sm tracking-wide text-white">Dokumen Kurikulum & Silabus</h3>
                            <p class="text-[10px] text-slate-400" id="status-label">Status: <span class="text-amber-300 font-bold">Draft Belum Disimpan</span></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="printCurriculum()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 px-3 py-1.5 rounded-lg text-xs font-semibold border border-slate-700 transition flex items-center gap-1.5" title="Cetak untuk PSB & Akreditasi">
                            <i class="fas fa-print text-amber-400"></i> Cetak / PDF
                        </button>
                        <button onclick="saveCurriculum('Draft')" class="bg-slate-700 hover:bg-slate-600 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                            <i class="fas fa-save text-slate-300"></i> Simpan Draft
                        </button>
                        <button onclick="saveCurriculum('Published')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-1.5 rounded-lg text-xs font-black shadow-md transition flex items-center gap-1.5">
                            <i class="fas fa-paper-plane"></i> PUBLISH KE TRAINER
                        </button>
                    </div>
                </div>

                <div id="result-container" class="p-6 md:p-8 flex-1 overflow-y-auto relative flex flex-col">
                    <!-- IDLE STATE -->
                    <div id="state-idle" class="flex flex-col items-center justify-center h-full text-slate-400 py-20 text-center my-auto">
                        <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center text-amber-500 mb-4 shadow-inner">
                            <i class="fas fa-rocket text-3xl animate-bounce"></i>
                        </div>
                        <h4 class="font-bold text-slate-700 text-base">Kurikulum Siap Digenerate!</h4>
                        <p class="text-xs text-slate-500 max-w-sm mt-1">
                            Atur parameter di panel kiri dan klik tombol <strong>"Generate Kurikulum AI"</strong> untuk meracik silabus berpenghasilan nyata.
                        </p>
                    </div>

                    <!-- LOADING STATE -->
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-amber-600 py-20 text-center my-auto">
                        <div class="relative w-16 h-16 mb-4 mx-auto">
                            <div class="absolute inset-0 rounded-full border-4 border-amber-200"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-amber-500 border-t-transparent animate-spin"></div>
                        </div>
                        <h4 class="font-bold text-slate-800 text-sm">AI Sedang Merancang Kurikulum...</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-xs animate-pulse">
                            Menyusun milestone bisnis, pembagian kognitif SMP/SMA, dan prompt cheat-sheet untuk asatidz trainer.
                        </p>
                    </div>

                    <!-- RESULT STATE -->
                    <div id="state-result" class="hidden markdown-body flex-1"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        let currentContent = "";

        document.addEventListener("DOMContentLoaded", function() {
            loadList();
            const sidebarBtn = document.getElementById('open-sidebar-yayasan2');
            const sidebar = document.getElementById('sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');
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

        function loadList(selectId = 0) {
            fetch('kurikulum-solopreneur.php?action=get_list')
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const sel = document.getElementById('curriculum-list');
                        sel.innerHTML = '<option value="">-- 📚 Pilih Tersimpan --</option>';
                        res.data.forEach(item => {
                            const opt = document.createElement('option');
                            opt.value = item.id;
                            opt.textContent = `[${item.status_publish}] ${item.judul} (${item.tgl})`;
                            if (selectId && selectId == item.id) opt.selected = true;
                            sel.appendChild(opt);
                        });
                    }
                })
                .catch(err => console.error("Error loading list:", err));
        }

        function loadCurriculum(id) {
            if (!id) return;
            fetch(`kurikulum-solopreneur.php?action=get_detail&id=${id}`)
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        const d = res.data;
                        document.getElementById('curr-id').value = d.id;
                        document.getElementById('curr-judul').value = d.judul;
                        document.getElementById('curr-model').value = d.model_pelaksanaan;
                        document.getElementById('curr-jenjang').value = d.target_jenjang;
                        document.getElementById('curr-durasi').value = d.durasi_target;
                        document.getElementById('curr-santri').value = d.total_santri;
                        document.getElementById('curr-fokus').value = d.fokus_bisnis;
                        
                        currentContent = d.konten_kurikulum;
                        document.getElementById('status-label').innerHTML = `Status: <span class="font-bold ${d.status_publish === 'Published' ? 'text-emerald-400' : 'text-amber-300'}">${d.status_publish}</span> (Diupdate: ${d.updated_at})`;
                        
                        document.getElementById('state-idle').classList.add('hidden');
                        document.getElementById('state-loading').classList.add('hidden');
                        document.getElementById('state-result').innerHTML = marked.parse(currentContent);
                        document.getElementById('state-result').classList.remove('hidden');
                    }
                });
        }

        function resetForm() {
            document.getElementById('curr-id').value = "0";
            document.getElementById('curr-judul').value = "Kurikulum Inkubator Solopreneur AI 1 Tahun";
            document.getElementById('curriculum-list').value = "";
            currentContent = "";
            document.getElementById('status-label').innerHTML = `Status: <span class="text-amber-300 font-bold">Draft Belum Disimpan</span>`;
            document.getElementById('state-idle').classList.remove('hidden');
            document.getElementById('state-loading').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
        }

        function generateAI() {
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('icon-magic').classList.add('animate-spin');

            const formData = new FormData();
            formData.append('model_pelaksanaan', document.getElementById('curr-model').value);
            formData.append('target_jenjang', document.getElementById('curr-jenjang').value);
            formData.append('durasi_target', document.getElementById('curr-durasi').value);
            formData.append('total_santri', document.getElementById('curr-santri').value);
            formData.append('fokus_bisnis', document.getElementById('curr-fokus').value);
            formData.append('fasilitator', document.getElementById('curr-fasilitator').value);

            fetch('kurikulum-solopreneur.php?action=generate_ai', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                document.getElementById('state-loading').classList.add('hidden');
                document.getElementById('icon-magic').classList.remove('animate-spin');
                if (res.status === 'success') {
                    currentContent = res.result;
                    document.getElementById('state-result').innerHTML = marked.parse(currentContent);
                    document.getElementById('state-result').classList.remove('hidden');
                    alert("✨ Kurikulum berhasil dirancang oleh AI! Silakan klik 'Simpan Draft' atau langsung 'Publish ke Trainer'.");
                } else {
                    alert("Gagal: " + res.message);
                    document.getElementById('state-idle').classList.remove('hidden');
                }
            })
            .catch(err => {
                document.getElementById('state-loading').classList.add('hidden');
                document.getElementById('icon-magic').classList.remove('animate-spin');
                document.getElementById('state-idle').classList.remove('hidden');
                alert("Terjadi kesalahan koneksi ke server AI.");
            });
        }

        function saveCurriculum(status) {
            if (!currentContent) {
                alert("Belum ada konten kurikulum yang digenerate!");
                return;
            }
            const formData = new FormData();
            formData.append('id', document.getElementById('curr-id').value);
            formData.append('judul', document.getElementById('curr-judul').value);
            formData.append('model_pelaksanaan', document.getElementById('curr-model').value);
            formData.append('target_jenjang', document.getElementById('curr-jenjang').value);
            formData.append('durasi_target', document.getElementById('curr-durasi').value);
            formData.append('total_santri', document.getElementById('curr-santri').value);
            formData.append('fokus_bisnis', document.getElementById('curr-fokus').value);
            formData.append('konten_kurikulum', currentContent);
            formData.append('status_publish', status);

            fetch('kurikulum-solopreneur.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    document.getElementById('curr-id').value = res.id;
                    document.getElementById('status-label').innerHTML = `Status: <span class="font-bold ${status === 'Published' ? 'text-emerald-400' : 'text-amber-300'}">${status}</span>`;
                    alert(res.message + (status === 'Published' ? " Kurikulum sekarang bisa diakses oleh Asatidz dengan role Trainer!" : ""));
                    loadList(res.id);
                } else {
                    alert("Error: " + res.message);
                }
            });
        }

        function printCurriculum() {
            if (!currentContent) {
                alert("Silakan generate atau pilih kurikulum terlebih dahulu sebelum mencetak!");
                return;
            }
            window.print();
        }
    </script>
</body>
</html>
