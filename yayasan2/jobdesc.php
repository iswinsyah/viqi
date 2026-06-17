<?php
require_once 'auth.php'; // Menggunakan sistem keamanan Ruang Yayasan
require_once '../koneksi.php';

// 1. Inisialisasi Database (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS job_descriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    swot_id INT NOT NULL,
    hasil_jobdesc TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (swot_id) REFERENCES swot_analysis(id) ON DELETE CASCADE
)");

// Ambil daftar SDM/jabatan yang aktif (ada = 1) beserta Amanah Globalnya untuk disuntikkan ke prompt AI
$res_sdm = $conn->query("SELECT nama_jabatan, quota, amanah_global FROM struktur_sekolah WHERE ada = 1 ORDER BY nomor ASC");
$active_sdm = [];
if ($res_sdm) {
    while ($row = $res_sdm->fetch_assoc()) {
        $amanah = !empty($row['amanah_global']) ? " [Panduan Amanah Global: " . $row['amanah_global'] . "]" : '';
        $active_sdm[] = $row['nama_jabatan'] . " (Quota: " . $row['quota'] . " orang)" . $amanah;
    }
}
$active_sdm_string = !empty($active_sdm) ? implode(', ', $active_sdm) : 'Tidak ada jabatan aktif yang terdefinisi di menu Struktur.';

// AJAX GET Requests
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_history') {
        $result = $conn->query("SELECT id, created_at, (program_rekomendasi IS NOT NULL AND TRIM(program_rekomendasi) != '') as has_recommendation FROM swot_analysis ORDER BY id DESC");
        $history = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $history[] = [
                    'id' => $row['id'],
                    'tanggal' => date('d M Y H:i', strtotime($row['created_at'])),
                    'has_recommendation' => (bool)$row['has_recommendation']
                ];
            }
        }
        echo json_encode($history);
        exit;
    }

    if ($action === 'get_swot_preview') {
        $swot_id = isset($_GET['swot_id']) ? (int)$_GET['swot_id'] : 0;
        
        $res_swot = $conn->query("SELECT program_rekomendasi FROM swot_analysis WHERE id = $swot_id");
        $swot = $res_swot ? $res_swot->fetch_assoc() : null;
        
        $res_jd = $conn->query("SELECT hasil_jobdesc FROM job_descriptions WHERE swot_id = $swot_id");
        $jd = $res_jd ? $res_jd->fetch_assoc() : null;
        
        if ($swot) {
            echo json_encode([
                'status' => 'success',
                'program_rekomendasi' => $swot['program_rekomendasi'] ?? '',
                'hasil_jobdesc' => $jd ? $jd['hasil_jobdesc'] : ''
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Data SWOT tidak ditemukan']);
        }
        exit;
    }
}

// AJAX POST Requests
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'save_jobdesc') {
        $swot_id = isset($_POST['swot_id']) ? (int)$_POST['swot_id'] : 0;
        $hasil_jobdesc = $conn->real_escape_string($_POST['hasil_jobdesc'] ?? '');

        if ($swot_id > 0) {
            $check = $conn->query("SELECT id FROM job_descriptions WHERE swot_id = $swot_id");
            if ($check && $check->num_rows > 0) {
                $sql = "UPDATE job_descriptions SET hasil_jobdesc='$hasil_jobdesc' WHERE swot_id=$swot_id";
            } else {
                $sql = "INSERT INTO job_descriptions (swot_id, hasil_jobdesc) VALUES ($swot_id, '$hasil_jobdesc')";
            }

            if ($conn->query($sql)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SWOT ID tidak valid']);
        }
        exit;
    }

    if ($action === 'delete') {
        $swot_id = isset($_POST['swot_id']) ? (int)$_POST['swot_id'] : 0;
        if ($swot_id > 0) {
            if ($conn->query("DELETE FROM job_descriptions WHERE swot_id = $swot_id")) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'SWOT ID tidak valid']);
        }
        exit;
    }
}

$active_menu = 'jobdesc_yayasan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Description SWOT | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Custom scrollbars for scrollable columns */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Premium markdown body styling */
        .markdown-body h1 { 
            font-size: 1.5rem; 
            font-weight: 800; 
            color: #1e293b; 
            margin-top: 1.75rem; 
            margin-bottom: 0.75rem; 
            border-bottom: 2px solid #f1f5f9; 
            padding-bottom: 0.35rem; 
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .markdown-body h1::before {
            content: "\f2c2";
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #d97706;
            font-size: 1.25rem;
        }
        .markdown-body h2 { 
            font-size: 1.2rem; 
            font-weight: 700; 
            color: #d97706; 
            margin-top: 1.25rem; 
            margin-bottom: 0.5rem; 
        }
        .markdown-body h3 { 
            font-size: 1.05rem; 
            font-weight: 600; 
            color: #475569; 
            margin-top: 1rem; 
            margin-bottom: 0.25rem; 
        }
        .markdown-body p { 
            margin-bottom: 0.75rem; 
            line-height: 1.625; 
            color: #475569; 
            text-align: justify;
        }
        .markdown-body ul, .markdown-body ol { 
            margin-left: 1.5rem; 
            margin-bottom: 0.75rem; 
        }
        .markdown-body ul { 
            list-style-type: disc; 
        }
        .markdown-body ol { 
            list-style-type: decimal; 
        }
        .markdown-body li { 
            margin-bottom: 0.35rem; 
            color: #475569; 
        }
        .markdown-body strong { 
            color: #0f172a; 
            font-weight: 700; 
        }
        .markdown-body blockquote { 
            border-left: 4px solid #f59e0b; 
            padding-left: 1rem; 
            color: #64748b; 
            font-style: italic; 
            margin: 1rem 0; 
            background: #fffbeb; 
            padding-top: 0.5rem; 
            padding-bottom: 0.5rem; 
        }
        .markdown-body hr {
            border: 0;
            border-top: 2px dashed #e2e8f0;
            margin: 2rem 0;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-gray-100">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            
            <div class="flex items-center space-x-3">
                <!-- Dropdown Pilih Riwayat SWOT -->
                <select id="history-list" onchange="loadSwotPreview(this.value)" class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 shadow-sm transition hover:bg-amber-100/50 cursor-pointer">
                    <option value="">-- Pilih Riwayat SWOT --</option>
                </select>
                
                <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Menu Jobdesc
                </span>
            </div>
        </header>

        <!-- MAIN LAYOUT (2 Columns side-by-side) -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50 p-6 flex flex-col xl:flex-row gap-6">
            
            <!-- LEFT COLUMN: SWOT Work Program Reference -->
            <div class="flex-1 flex flex-col gap-6 xl:max-w-md h-full min-h-[400px]">
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight text-gray-900 flex items-center">
                        <i class="fas fa-file-contract text-amber-600 mr-2.5"></i>
                        Program Kerja SWOT
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">
                        Acuan program kerja & penanggung jawab hasil generate SWOT terpilih.
                    </p>
                </div>

                <!-- CARD PREVIEW SWOT PROGRAM -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col flex-1">
                    <div class="px-5 py-3.5 bg-gradient-to-r from-amber-50/70 to-orange-50/20 border-b border-gray-100 flex items-center justify-between">
                        <span class="font-bold text-gray-700 text-sm flex items-center">
                            <i class="fas fa-clipboard-list text-amber-600 mr-2"></i>
                            Rekomendasi SWOT
                        </span>
                    </div>

                    <!-- SWOT Preview Content Area -->
                    <div id="swot-preview-container" class="flex-1 p-5 overflow-y-auto custom-scrollbar text-sm markdown-body">
                        <!-- Default Idle State -->
                        <div id="swot-idle" class="h-full flex flex-col items-center justify-center text-center p-6">
                            <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shadow-inner mb-4 animate-bounce">
                                <i class="fas fa-arrow-up"></i>
                            </div>
                            <h4 class="font-bold text-gray-800 text-base">Riwayat SWOT Belum Dipilih</h4>
                            <p class="text-xs text-gray-400 max-w-[250px] mt-1.5">
                                Silakan pilih salah satu riwayat SWOT di bagian atas untuk memuat referensi program kerja.
                            </p>
                        </div>

                        <!-- SWOT Warning Empty Recommendations -->
                        <div id="swot-empty" class="hidden h-full flex flex-col items-center justify-center text-center p-6">
                            <div class="w-16 h-16 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center text-2xl shadow-inner mb-4">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <h4 class="font-bold text-rose-800 text-base">Tidak Ada Program Kerja</h4>
                            <p class="text-xs text-gray-500 max-w-[250px] mt-1.5">
                                SWOT terpilih tidak memiliki rekomendasi program kerja. Silakan buat di menu <a href="analisis-swot.php" class="text-amber-600 font-bold hover:underline">Analisis SWOT</a>.
                            </p>
                        </div>

                        <!-- Real Rendered SWOT Program -->
                        <div id="swot-content" class="hidden text-gray-700"></div>
                    </div>

                    <!-- Generate Actions Footer -->
                    <div id="generate-footer" class="hidden p-4 bg-gray-50 border-t border-gray-100">
                        <button id="btn-generate" onclick="generateJobdesc()" class="w-full bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-gray-900 font-bold py-3 px-6 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 transform active:scale-95">
                            <i class="fas fa-robot text-lg"></i>
                            Generate Job Description
                        </button>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Generated Job Description Display / Editor -->
            <div class="flex-[2] bg-white rounded-2xl border border-gray-200/60 shadow-md flex flex-col overflow-hidden h-full min-h-[500px]">
                
                <!-- Card Header with action buttons -->
                <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-orange-50/30 border-b border-gray-200/80 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <span class="font-bold text-gray-800 flex items-center text-sm md:text-base">
                        <i class="fas fa-id-card text-amber-600 mr-2"></i>
                        Hasil Rancangan Job Description
                    </span>
                    
                    <div id="jd-actions" class="hidden flex items-center gap-2 w-full sm:w-auto justify-end">
                        <button id="btn-save-header" onclick="manualSaveHeader()" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm" title="Simpan ke Database">
                            <i class="fas fa-save"></i>
                            <span>Simpan</span>
                        </button>

                        <button id="btn-copy" onclick="copyJdResult()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm" title="Salin ke Clipboard">
                            <i class="fas fa-copy"></i>
                            <span>Salin</span>
                        </button>
                        
                        <button id="btn-edit" onclick="toggleEditJd()" class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                            <i id="edit-icon" class="fas fa-edit"></i>
                            <span id="edit-text">Edit Manual</span>
                        </button>

                        <button id="btn-delete" onclick="deleteJd()" class="bg-rose-50 hover:bg-rose-100 text-rose-600 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm" title="Hapus Jobdesc">
                            <i class="fas fa-trash-alt"></i>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>

                <!-- Card Body content -->
                <div class="flex-1 p-6 overflow-y-auto custom-scrollbar flex flex-col">
                    
                    <!-- IDLE STATE -->
                    <div id="jd-idle" class="my-auto flex flex-col items-center justify-center text-center p-6">
                        <div class="w-20 h-20 rounded-full bg-amber-50 text-amber-500 flex items-center justify-center text-3xl shadow-inner mb-4">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <h3 class="font-extrabold text-gray-800 text-lg">Job Description Belum Siap</h3>
                        <p class="text-sm text-gray-400 max-w-sm mt-2">
                            Pilih riwayat SWOT di kiri, tinjau programnya, lalu klik tombol <b>Generate Job Description</b> untuk menyusun deskripsi peran dan KPI.
                        </p>
                    </div>

                    <!-- LOADING STATE -->
                    <div id="jd-loading" class="hidden my-auto flex flex-col items-center justify-center text-center p-6">
                        <div class="w-16 h-16 border-4 border-amber-200 border-t-amber-600 rounded-full animate-spin mb-6"></div>
                        <h4 class="font-bold text-amber-900 text-base">AI Sedang Menyusun Job Description...</h4>
                        <p class="text-xs text-gray-500 mt-2 max-w-xs leading-relaxed">
                            AI sedang membaca program kerja, mencari para penanggung jawab, lalu merumuskan rincian tugas berkala dan KPI terukur. Mohon tunggu sekitar 15-30 detik.
                        </p>
                    </div>

                    <!-- RESULT STATE (PREVIEW MODE) -->
                    <div id="jd-result-container" class="hidden flex-1 markdown-body text-sm">
                        <div id="jd-result-text"></div>
                    </div>

                    <!-- EDIT STATE (EDIT MODE) -->
                    <div id="jd-edit-container" class="hidden flex-1 flex flex-col h-full min-h-[350px]">
                        <textarea id="jd-textarea" class="w-full flex-1 p-4 border border-gray-300 rounded-xl text-sm bg-gray-50 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none font-mono resize-none shadow-inner" style="min-height: 350px;" placeholder="Tuliskan rancangan Job Description di sini..."></textarea>
                        
                        <div class="mt-4 flex justify-between items-center">
                            <div class="text-xs text-gray-400 flex items-center gap-1">
                                <i class="fas fa-info-circle"></i>
                                Format teks mendukung penulisan Markdown.
                            </div>
                            <button id="btn-save-jd" onclick="saveManualJd()" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2.5 px-6 rounded-lg text-sm shadow-md hover:shadow-lg transition-all flex items-center gap-2 transform active:scale-95">
                                <i class="fas fa-save"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none">
        <div class="bg-gray-900 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 border border-gray-800">
            <span id="toast-icon-wrapper" class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm shadow-inner">
                <i id="toast-icon" class="fas fa-check-circle"></i>
            </span>
            <div>
                <p id="toast-title" class="font-bold text-sm text-white">Sukses!</p>
                <p id="toast-message" class="text-xs text-gray-400 mt-0.5">Operasi berhasil dilaksanakan.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar trigger
            const sidebar = document.getElementById('sidebar-yayasan2');
            const openBtn = document.getElementById('open-sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');
            const closeBtn = document.getElementById('close-sidebar-yayasan2');

            function toggleSidebar() {
                if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);

            // Load initial history list, restoring last selection if present in localStorage
            const savedSwotId = localStorage.getItem('selected_swot_id');
            loadHistoryList(savedSwotId);
            if (savedSwotId) {
                loadSwotPreview(savedSwotId);
            }
        });

        let currentSwotId = null;
        let rawJdText = "";
        let isEditMode = false;
        const activeSdm = <?= json_encode($active_sdm_string) ?>;

        // Fetch SWOT history lists
        function loadHistoryList(selectIdAfterLoad = null) {
            fetch('jobdesc.php?action=get_history')
                .then(res => res.json())
                .then(data => {
                    const historyList = document.getElementById('history-list');
                    historyList.innerHTML = '<option value="">-- Pilih Riwayat SWOT --</option>';
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = `Analisis SWOT #${item.id} (${item.tanggal})${item.has_recommendation ? '' : ' [Belum ada Program]'}`;
                        if (selectIdAfterLoad && selectIdAfterLoad == item.id) {
                            opt.selected = true;
                        }
                        historyList.appendChild(opt);
                    });
                })
                .catch(err => console.error("Error loading history list:", err));
        }

        // Load specific SWOT preview and existing JD if available
        function loadSwotPreview(id) {
            currentSwotId = id;
            if (!id) {
                localStorage.removeItem('selected_swot_id');
                resetAllViews();
                return;
            }
            localStorage.setItem('selected_swot_id', id);

            // Reset view state
            if (isEditMode) toggleEditJd();
            document.getElementById('swot-idle').classList.add('hidden');
            document.getElementById('swot-empty').classList.add('hidden');
            document.getElementById('swot-content').classList.add('hidden');
            document.getElementById('generate-footer').classList.add('hidden');

            document.getElementById('jd-idle').classList.add('hidden');
            document.getElementById('jd-loading').classList.add('hidden');
            document.getElementById('jd-result-container').classList.add('hidden');
            document.getElementById('jd-actions').classList.add('hidden');

            fetch(`jobdesc.php?action=get_swot_preview&swot_id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const rec = data.program_rekomendasi;
                        const jd = data.hasil_jobdesc;

                        // 1. Handle SWOT Preview Column
                        if (rec && rec.trim() !== '') {
                            document.getElementById('swot-content').innerHTML = marked.parse(rec);
                            document.getElementById('swot-content').classList.remove('hidden');
                            document.getElementById('generate-footer').classList.remove('hidden');
                        } else {
                            document.getElementById('swot-empty').classList.remove('hidden');
                        }

                        // 2. Handle Jobdesc Output Column
                        if (jd && jd.trim() !== '') {
                            rawJdText = jd;
                            document.getElementById('jd-result-text').innerHTML = marked.parse(jd);
                            document.getElementById('jd-result-container').classList.remove('hidden');
                            document.getElementById('jd-actions').classList.remove('hidden');
                        } else {
                            rawJdText = "";
                            document.getElementById('jd-idle').classList.remove('hidden');
                        }
                    } else {
                        alert(data.message || "Gagal memuat preview");
                        resetAllViews();
                    }
                })
                .catch(err => {
                    console.error("Error fetching SWOT details:", err);
                    alert("Terjadi kesalahan memuat data.");
                    resetAllViews();
                });
        }

        // Reset all columns
        function resetAllViews() {
            currentSwotId = null;
            rawJdText = "";
            if (isEditMode) toggleEditJd();

            document.getElementById('swot-idle').classList.remove('hidden');
            document.getElementById('swot-empty').classList.add('hidden');
            document.getElementById('swot-content').classList.add('hidden');
            document.getElementById('generate-footer').classList.add('hidden');

            document.getElementById('jd-idle').classList.remove('hidden');
            document.getElementById('jd-loading').classList.add('hidden');
            document.getElementById('jd-result-container').classList.add('hidden');
            document.getElementById('jd-actions').classList.add('hidden');
        }

        // Generate Job description using Gemini via api-gemini.php
        function generateJobdesc() {
            if (!currentSwotId) return;

            const btnGenerate = document.getElementById('btn-generate');
            const jdIdle = document.getElementById('jd-idle');
            const jdLoading = document.getElementById('jd-loading');
            const jdResultContainer = document.getElementById('jd-result-container');
            const jdActions = document.getElementById('jd-actions');
            const swotContentElement = document.getElementById('swot-content');

            // Set loading views
            if (isEditMode) toggleEditJd();
            btnGenerate.disabled = true;
            btnGenerate.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses AI...';
            jdIdle.classList.add('hidden');
            jdResultContainer.classList.add('hidden');
            jdActions.classList.add('hidden');
            jdLoading.classList.remove('hidden');

            // Extract prompt
            const programRekomendasi = swotContentElement.innerText || swotContentElement.textContent;
            
            const prompt = `<<< SYSTEM INSTRUCTION OVERRIDE: RESET PERSONA >>>
Abaikan peran Anda sebagai pakar marketing, promosi, iklan, atau pencari leads. Anda dilarang keras merumuskan strategi pemasaran atau akuisisi santri baru. 
Bertindaklah murni sebagai Konsultan Manajemen Operasional Internal Sekolah/Pesantren Islam. 

Tugas Anda adalah menyusun deskripsi pekerjaan (Job Description) terperinci untuk setiap Penanggung Jawab (PJ) yang tercantum dalam rekomendasi program kerja hasil analisis SWOT berikut:

Program Kerja Rekomendasi SWOT:
${programRekomendasi}

Daftar Jabatan Aktif di Sekolah (sebagai referensi tambahan jika diperlukan):
${activeSdm}

Pedoman Konsep Dasar/Global untuk masing-masing Peran/Jabatan yang WAJIB diintegrasikan ke dalam deskripsi peran, tugas, wewenang, dan tanggung jawab utama mereka:
1. Kepala Sekolah: Penanggungjawab dan wakil lembaga dalam urusan prestasi akademik santri secara umum.
2. Sekretaris Sekolah: Wakil kepala sekolah dalam urusan administrasi dan kurikulum sekolah.
3. Bendahara Sekolah: Wakil kepala sekolah dalam urusan administrasi keuangan.
4. Administrator atau Admin Sekolah: Wakil kepala sekolah dalam urusan administrasi dan keuangan sekolah jika peran Sekretaris sekolah dan Bendahara sekolah tidak ada.
5. Ustadz dan Ustadzah: Tenaga pendidik yang mengampu mata pelajaran umum sesuai kurikulum negara yang berlaku saat itu.
6. Kepala Ma'had: Penanggungjawab pembentukan karakter santri sebagai seorang pengemban dakwah, penjaga Quran yang mandiri.
7. Kepala Asrama: Wakil kepala ma'had dalam ruang lingkup asrama yang dipimpinnya.
8. Musyrif dan Musyrifah: Wakil kepala asrama atas santri binaan masing-masing.

Untuk setiap peran/Penanggung Jawab (PJ) unik yang diidentifikasi dari Program Kerja di atas, buatlah Job Description dengan struktur sebagai berikut:

# Nama Jabatan / Peran PJ
1. **Peran & Tanggung Jawab Utama**: Penjelasan singkat mengenai fungsi utama jabatan ini dalam menyukseskan program kerja yang menjadi tanggung jawabnya (selaraskan dengan pedoman konsep dasar peran di atas).
2. **Rincian Tugas Berkala**:
   - **Tugas Harian**: Rutinitas sehari-hari yang harus dilakukan.
   - **Tugas Mingguan**: Tugas yang dievaluasi atau dilakukan setiap pekan.
   - **Tugas Bulanan / Berkala**: Tugas berkala bulanan atau insidental terkait program kerja.
3. **Wewenang / Otoritas Jabatan**: Hak mengambil keputusan atau tindakan yang dimiliki oleh pemangku jabatan.
4. **Key Performance Indicators (KPI)**: Minimal 3-4 indikator kinerja utama yang terukur (misal: persentase pencapaian, ketepatan waktu, frekuensi pelaporan) untuk mengukur keberhasilan pelaksanaan program kerja mereka.

Sajikan seluruh analisis ini dalam format Markdown yang indah, profesional, terstruktur dengan rapi, dan mudah dibaca oleh Yayasan dan personil terkait. Gunakan elemen visual markdown seperti bold, list, dan pembatas horizontal (---) antar jabatan.`;

            const GAS_URL = "../api-gemini.php";

            fetch(GAS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({
                    leads: [{
                        jenis_lead: "SYSTEM_COMMAND",
                        sumber_info: prompt,
                        status: "URGENT"
                    }],
                    type: 'jobdesc'
                })
            })
            .then(res => res.json())
            .then(aiData => {
                if (aiData.status === "success") {
                    const resultText = aiData.result;
                    rawJdText = resultText;
                    document.getElementById('jd-result-text').innerHTML = marked.parse(resultText);
                    
                    jdLoading.classList.add('hidden');
                    jdResultContainer.classList.remove('hidden');
                    jdActions.classList.remove('hidden');

                    // Save directly to DB
                    saveJobdescToDb(resultText);
                } else {
                    throw new Error(aiData.message || "Gagal mendapatkan respons AI");
                }
            })
            .catch(err => {
                console.error("Error generating Jobdesc:", err);
                alert("Terjadi kesalahan proses AI: " + err.message);
                jdLoading.classList.add('hidden');
                jdIdle.classList.remove('hidden');
            })
            .finally(() => {
                btnGenerate.disabled = false;
                btnGenerate.innerHTML = '<i class="fas fa-robot text-lg"></i> Generate Job Description';
            });
        }

        // Save generated/edited Jobdesc to DB
        function saveJobdescToDb(text, showToastSuccess = true) {
            const formData = new FormData();
            formData.append('swot_id', currentSwotId);
            formData.append('hasil_jobdesc', text);

            fetch('jobdesc.php?action=save_jobdesc', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    if (showToastSuccess) {
                        triggerToast("Sukses!", "Job Description berhasil disimpan.", "success");
                    }
                } else {
                    alert("Gagal menyimpan ke database: " + data.message);
                }
            })
            .catch(err => {
                console.error("Error saving jobdesc:", err);
                alert("Terjadi kesalahan menyimpan ke database.");
            });
        }

        // Manual save triggered from header button
        function manualSaveHeader() {
            if (!currentSwotId || !rawJdText) {
                alert("Tidak ada Job Description yang bisa disimpan.");
                return;
            }
            const btnSave = document.getElementById('btn-save-header');
            const originalContent = btnSave.innerHTML;
            
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...';

            const formData = new FormData();
            formData.append('swot_id', currentSwotId);
            formData.append('hasil_jobdesc', rawJdText);

            fetch('jobdesc.php?action=save_jobdesc', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    triggerToast("Sukses!", "Job Description berhasil disimpan ke database.", "success");
                } else {
                    alert("Gagal menyimpan: " + data.message);
                }
            })
            .catch(err => {
                console.error("Error saving jobdesc:", err);
                alert("Terjadi kesalahan koneksi.");
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = originalContent;
            });
        }

        // Toggle edit mode
        function toggleEditJd() {
            const resultDiv = document.getElementById('jd-result-container');
            const editDiv = document.getElementById('jd-edit-container');
            const textarea = document.getElementById('jd-textarea');
            const editText = document.getElementById('edit-text');
            const editIcon = document.getElementById('edit-icon');

            if (!isEditMode) {
                // To Edit Mode
                textarea.value = rawJdText;
                resultDiv.classList.add('hidden');
                editDiv.classList.remove('hidden');
                editText.textContent = "Batal Edit";
                editIcon.className = "fas fa-times";
                isEditMode = true;
            } else {
                // To Preview Mode
                editDiv.classList.add('hidden');
                resultDiv.classList.remove('hidden');
                editText.textContent = "Edit Manual";
                editIcon.className = "fas fa-edit";
                isEditMode = false;
            }
        }

        // Save manual edit
        function saveManualJd() {
            const updatedText = document.getElementById('jd-textarea').value;
            const btnSave = document.getElementById('btn-save-jd');

            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...';

            const formData = new FormData();
            formData.append('swot_id', currentSwotId);
            formData.append('hasil_jobdesc', updatedText);

            fetch('jobdesc.php?action=save_jobdesc', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    rawJdText = updatedText;
                    document.getElementById('jd-result-text').innerHTML = marked.parse(updatedText);
                    toggleEditJd();
                    triggerToast("Sukses!", "Perubahan manual berhasil disimpan.", "success");
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(err => {
                console.error("Error: ", err);
                alert("Terjadi kesalahan koneksi.");
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save mr-1.5"></i> Simpan Perubahan';
            });
        }

        // Delete saved job description
        function deleteJd() {
            if (!currentSwotId) return;

            if (confirm("Apakah Anda yakin ingin menghapus hasil rancangan Job Description ini secara permanen?")) {
                const formData = new FormData();
                formData.append('swot_id', currentSwotId);

                fetch('jobdesc.php?action=delete', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        rawJdText = "";
                        document.getElementById('jd-result-container').classList.add('hidden');
                        document.getElementById('jd-actions').classList.add('hidden');
                        document.getElementById('jd-idle').classList.remove('hidden');
                        triggerToast("Dihapus!", "Job Description berhasil dihapus.", "warning");
                    } else {
                        alert("Gagal menghapus: " + data.message);
                    }
                })
                .catch(err => {
                    console.error("Error deleting jobdesc:", err);
                    alert("Terjadi kesalahan koneksi.");
                });
            }
        }

        // Copy markdown output to clipboard
        function copyJdResult() {
            if (!rawJdText) return;
            navigator.clipboard.writeText(rawJdText).then(() => {
                const btnCopy = document.getElementById('btn-copy');
                const originalContent = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="fas fa-check"></i> <span>Tersalin!</span>';
                btnCopy.disabled = true;
                setTimeout(() => {
                    btnCopy.innerHTML = originalContent;
                    btnCopy.disabled = false;
                }, 2000);
            }).catch(err => {
                console.error("Gagal menyalin text: ", err);
            });
        }

        // Trigger premium sliding toast notification
        function triggerToast(title, message, type = "success") {
            const toast = document.getElementById('toast');
            const iconWrapper = document.getElementById('toast-icon-wrapper');
            const icon = document.getElementById('toast-icon');
            const titleElem = document.getElementById('toast-title');
            const msgElem = document.getElementById('toast-message');

            titleElem.textContent = title;
            msgElem.textContent = message;

            if (type === "success") {
                iconWrapper.className = "w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm shadow-inner";
                icon.className = "fas fa-check-circle";
            } else if (type === "warning") {
                iconWrapper.className = "w-8 h-8 rounded-lg bg-amber-500/10 text-amber-500 flex items-center justify-center text-sm shadow-inner";
                icon.className = "fas fa-exclamation-triangle";
            } else {
                iconWrapper.className = "w-8 h-8 rounded-lg bg-rose-500/10 text-rose-500 flex items-center justify-center text-sm shadow-inner";
                icon.className = "fas fa-times-circle";
            }

            toast.classList.remove('translate-y-24', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-24', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
