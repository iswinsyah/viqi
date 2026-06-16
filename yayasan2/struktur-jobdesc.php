<?php
require_once 'auth.php'; // Menggunakan sistem keamanan Ruang Yayasan
require_once '../koneksi.php';

// 1. Inisialisasi Database (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS struktur_jobdesc (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_program VARCHAR(255) NOT NULL,
    deskripsi TEXT NULL,
    kategori VARCHAR(100) NULL,
    jumlah_sdm INT DEFAULT 1,
    catatan_tambahan TEXT NULL,
    hasil_struktur TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Handling AJAX API Requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'get_history') {
        $result = $conn->query("SELECT id, nama_program, created_at FROM struktur_jobdesc ORDER BY id DESC");
        $history = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $history[] = [
                    'id' => $row['id'],
                    'nama_program' => $row['nama_program'],
                    'tanggal' => date('d M Y H:i', strtotime($row['created_at']))
                ];
            }
        }
        echo json_encode($history);
        exit;
    }

    if ($action === 'get_jobdesc') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $res = $conn->query("SELECT * FROM struktur_jobdesc WHERE id = $id");
        if ($res && $res->num_rows > 0) {
            echo json_encode($res->fetch_assoc());
        } else {
            echo json_encode(['error' => 'Data tidak ditemukan']);
        }
        exit;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'save') {
        $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
        $nama_program = $conn->real_escape_string($_POST['nama_program'] ?? '');
        $deskripsi = $conn->real_escape_string($_POST['deskripsi'] ?? '');
        $kategori = $conn->real_escape_string($_POST['kategori'] ?? '');
        $jumlah_sdm = isset($_POST['jumlah_sdm']) ? (int)$_POST['jumlah_sdm'] : 1;
        $catatan_tambahan = $conn->real_escape_string($_POST['catatan_tambahan'] ?? '');

        if ($id > 0) {
            $sql = "UPDATE struktur_jobdesc SET nama_program='$nama_program', deskripsi='$deskripsi', kategori='$kategori', jumlah_sdm=$jumlah_sdm, catatan_tambahan='$catatan_tambahan' WHERE id=$id";
        } else {
            $sql = "INSERT INTO struktur_jobdesc (nama_program, deskripsi, kategori, jumlah_sdm, catatan_tambahan) VALUES ('$nama_program', '$deskripsi', '$kategori', $jumlah_sdm, '$catatan_tambahan')";
        }

        if ($conn->query($sql)) {
            $saved_id = ($id > 0) ? $id : $conn->insert_id;
            echo json_encode(['status' => 'success', 'id' => $saved_id]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        exit;
    }

    if ($action === 'save_recommendation') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $hasil_struktur = $conn->real_escape_string($_POST['hasil_struktur'] ?? '');

        if ($id > 0) {
            $sql = "UPDATE struktur_jobdesc SET hasil_struktur='$hasil_struktur' WHERE id=$id";
            if ($conn->query($sql)) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ID tidak valid']);
        }
        exit;
    }
}

$active_menu = 'struktur_jobdesc';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator Struktur & Jobdesc | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1 { font-size: 1.5rem; font-weight: 800; color: #1e293b; margin-top: 1.5rem; margin-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.25rem; }
        .markdown-body h2 { font-size: 1.25rem; font-weight: 700; color: #334155; margin-top: 1.25rem; margin-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.1rem; font-weight: 600; color: #475569; margin-top: 1rem; margin-bottom: 0.25rem; }
        .markdown-body p { margin-bottom: 0.75rem; line-height: 1.625; color: #475569; }
        .markdown-body ul, .markdown-body ol { margin-left: 1.5rem; margin-bottom: 0.75rem; }
        .markdown-body ul { list-style-type: disc; }
        .markdown-body ol { list-style-type: decimal; }
        .markdown-body li { margin-bottom: 0.25rem; color: #475569; }
        .markdown-body strong { color: #0f172a; font-weight: 700; }
        .markdown-body blockquote { border-left: 4px solid #f59e0b; padding-left: 1rem; color: #64748b; font-style: italic; margin: 1rem 0; background: #fffbeb; padding-top: 0.5rem; padding-bottom: 0.5rem; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-4">
                <select id="history-list" onchange="loadJobdesc(this.value)" class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg px-4 py-2 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <option value="">-- Pilih Riwayat Program --</option>
                </select>
                <button onclick="resetForm()" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold px-4 py-2 rounded-lg text-sm transition shadow-sm flex items-center">
                    <i class="fas fa-plus mr-1.5"></i> Baru
                </button>
            </div>
        </header>

        <!-- MAIN LAYOUT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 flex flex-col lg:flex-row gap-6">
            
            <!-- LEFT COLUMN: INPUTS & ACTIONS -->
            <div class="flex-1 flex flex-col gap-6 lg:max-w-xl">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-sitemap text-amber-600 mr-2"></i>Rancangan Struktur & Jobdesc</h1>
                    <p class="text-sm text-gray-500 mt-1">Buat struktur kepanitiaan/organisasi dan deskripsi tugas umum untuk staf sekolah menggunakan bantuan AI.</p>
                </div>

                <input type="hidden" id="program-id" value="">

                <!-- FORM CARDS -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                    <!-- NAMA PROGRAM -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Program / Kegiatan</label>
                        <input type="text" id="program-nama" class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none" placeholder="Contoh: Panitia SPMB 2026, Pesantren Kilat Ramadhan">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- BIDANG KATEGORI -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Bidang / Kategori</label>
                            <select id="program-kategori" class="w-full px-4 py-2.5 border rounded-lg text-sm bg-white focus:ring-2 focus:ring-amber-400 focus:outline-none">
                                <option value="Kurikulum / Akademik">Kurikulum / Akademik</option>
                                <option value="Asrama / Kepengasuhan">Asrama / Kepengasuhan</option>
                                <option value="Humas & Pemasaran">Humas & Pemasaran</option>
                                <option value="Sarana & Prasarana">Sarana & Prasarana</option>
                                <option value="Keuangan & Administrasi">Keuangan & Administrasi</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <!-- ESTIMASI JUMLAH SDM -->
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Estimasi Jumlah Anggota Tim</label>
                            <input type="number" id="program-sdm" min="1" max="100" value="5" class="w-full px-4 py-2.5 border rounded-lg text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none">
                        </div>
                    </div>

                    <!-- DESKRIPSI & TUJUAN -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi & Tujuan Program</label>
                        <textarea id="program-deskripsi" rows="4" class="w-full p-3 border rounded-lg text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none" placeholder="Tuliskan tujuan dan apa saja yang ingin dicapai dari program ini..."></textarea>
                    </div>

                    <!-- CATATAN TAMBAHAN / KRITERIA SDM -->
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Kriteria Khusus / Kebutuhan Peran (Opsional)</label>
                        <textarea id="program-catatan" rows="3" class="w-full p-3 border rounded-lg text-sm focus:ring-2 focus:ring-amber-400 focus:outline-none" placeholder="Contoh: Butuh penanggung jawab utama, sekretaris, dan tim lapangan yang bersertifikat."></textarea>
                    </div>
                </div>

                <!-- ACTIONS BUTTONS -->
                <div class="flex flex-col sm:flex-row gap-3">
                    <button onclick="saveJobdesc('draft').then(() => alert('Draft program berhasil disimpan!'))" class="flex-1 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg shadow-sm transition flex items-center justify-center">
                        <i class="fas fa-save mr-2 text-gray-500"></i> Simpan Draf
                    </button>
                    <button id="btn-generate" onclick="generateJobdesc()" class="flex-[2] bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-gray-900 font-black py-3 px-4 rounded-lg shadow-md transition flex items-center justify-center">
                        <i class="fas fa-magic mr-2"></i> Simpan & Generate Struktur (AI)
                    </button>
                </div>
            </div>

            <!-- RIGHT COLUMN: AI RECOMMENDATION -->
            <div class="flex-1 flex flex-col bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-[500px]">
                <div class="px-6 py-4 bg-amber-50 border-b border-amber-100 flex justify-between items-center flex-shrink-0">
                    <h3 class="font-bold text-amber-800 flex items-center">
                        <i class="fas fa-sitemap mr-2"></i> Struktur Organisasi & Jobdesc (AI)
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button id="btn-edit-rec" onclick="toggleEditRec()" class="hidden text-xs bg-white text-amber-900 border border-amber-200 px-3 py-1.5 rounded-lg hover:bg-amber-100 font-bold transition flex items-center">
                            <i id="edit-icon" class="fas fa-edit mr-1.5"></i> <span id="edit-text">Edit Manual</span>
                        </button>
                        <button id="btn-copy" onclick="copyResult()" class="hidden text-xs bg-white text-amber-900 border border-amber-200 px-3 py-1.5 rounded-lg hover:bg-amber-100 font-medium transition flex items-center">
                            <i class="fas fa-copy mr-1.5"></i> Salin
                        </button>
                    </div>
                </div>
                
                <div id="result-container" class="p-6 flex-1 overflow-y-auto relative flex flex-col">
                    <!-- IDLE STATE -->
                    <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center my-auto">
                        <i class="fas fa-users-cog text-6xl mb-4 opacity-20"></i>
                        <p class="font-medium text-sm">Rancangan struktur kepanitiaan dan detail jobdesc tim akan ditampilkan di sini setelah Anda mengisi form dan melakukan generate.</p>
                    </div>

                    <!-- LOADING STATE -->
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-amber-600 py-16 text-center my-auto">
                        <div class="relative w-16 h-16 mb-4 mx-auto">
                            <div class="absolute inset-0 rounded-full border-4 border-amber-200"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-amber-500 border-t-transparent animate-spin"></div>
                        </div>
                        <p class="font-bold text-amber-900">AI sedang menganalisis kebutuhan program & menyusun struktur organisasi...</p>
                        <p class="text-xs text-gray-500 mt-1">Ini memerlukan waktu sekitar 10-20 detik.</p>
                    </div>

                    <!-- RESULT STATE (PREVIEW MODE) -->
                    <div id="state-result" class="hidden markdown-body text-sm flex-1"></div>

                    <!-- EDIT STATE (EDIT MODE) -->
                    <div id="state-edit" class="hidden h-full flex flex-col flex-1">
                        <textarea id="rec-textarea" class="w-full flex-1 p-3 border rounded-lg text-sm bg-gray-50 focus:ring-2 focus:ring-amber-500 focus:outline-none font-mono resize-y" style="min-height: 350px;" placeholder="Tuliskan rancangan struktur kepanitiaan dan jobdesc di sini..."></textarea>
                        <button id="btn-save-rec" onclick="saveManualRec()" class="mt-3 bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2.5 px-4 rounded-lg text-sm shadow-sm transition flex items-center justify-center self-end">
                            <i class="fas fa-save mr-1.5"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>

        </main>
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

            // Load initial history list
            loadHistoryList();
        });

        // Current raw text of the recommendations for clipboard copying and editing
        let rawRecommendationText = "";
        let isEditMode = false;

        // Load history list
        function loadHistoryList(selectIdAfterLoad = null) {
            fetch('struktur-jobdesc.php?action=get_history')
                .then(res => res.json())
                .then(data => {
                    const historyList = document.getElementById('history-list');
                    historyList.innerHTML = '<option value="">-- Pilih Riwayat Program --</option>';
                    data.forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.id;
                        opt.textContent = `${item.nama_program} (${item.tanggal})`;
                        if (selectIdAfterLoad && selectIdAfterLoad == item.id) {
                            opt.selected = true;
                        }
                        historyList.appendChild(opt);
                    });
                })
                .catch(err => console.error("Error loading history list:", err));
        }

        // Load specific SWOT details
        function loadJobdesc(id) {
            if (!id) {
                resetForm();
                return;
            }
            fetch(`struktur-jobdesc.php?action=get_jobdesc&id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        return;
                    }
                    document.getElementById('program-id').value = data.id;
                    document.getElementById('program-nama').value = data.nama_program || '';
                    document.getElementById('program-deskripsi').value = data.deskripsi || '';
                    document.getElementById('program-kategori').value = data.kategori || 'Kurikulum / Akademik';
                    document.getElementById('program-sdm').value = data.jumlah_sdm || 5;
                    document.getElementById('program-catatan').value = data.catatan_tambahan || '';
                    
                    // Render recommendation if exists
                    const resultDiv = document.getElementById('state-result');
                    const idleDiv = document.getElementById('state-idle');
                    const recommendation = data.hasil_struktur;
                    
                    // Turn off edit mode if active when loading new program
                    if (isEditMode) {
                        toggleEditRec();
                    }
                    
                    if (recommendation && recommendation.trim() !== '') {
                        rawRecommendationText = recommendation;
                        resultDiv.innerHTML = marked.parse(recommendation);
                        resultDiv.classList.remove('hidden');
                        idleDiv.classList.add('hidden');
                        document.getElementById('btn-copy').classList.remove('hidden');
                        document.getElementById('btn-edit-rec').classList.remove('hidden');
                    } else {
                        rawRecommendationText = "";
                        resultDiv.classList.add('hidden');
                        idleDiv.classList.remove('hidden');
                        document.getElementById('btn-copy').classList.add('hidden');
                        document.getElementById('btn-edit-rec').classList.add('hidden');
                    }
                })
                .catch(err => console.error("Error loading jobdesc details:", err));
        }

        // Save SWOT
        function saveJobdesc(actionType) {
            const programId = document.getElementById('program-id').value;
            const nama_program = document.getElementById('program-nama').value;
            const deskripsi = document.getElementById('program-deskripsi').value;
            const kategori = document.getElementById('program-kategori').value;
            const jumlah_sdm = document.getElementById('program-sdm').value;
            const catatan_tambahan = document.getElementById('program-catatan').value;

            if (!nama_program) {
                alert("Harap masukkan Nama Program / Kegiatan!");
                return Promise.reject("Nama program kosong");
            }

            const formData = new FormData();
            formData.append('id', programId);
            formData.append('nama_program', nama_program);
            formData.append('deskripsi', deskripsi);
            formData.append('kategori', kategori);
            formData.append('jumlah_sdm', jumlah_sdm);
            formData.append('catatan_tambahan', catatan_tambahan);

            return fetch('struktur-jobdesc.php?action=save', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    document.getElementById('program-id').value = data.id;
                    loadHistoryList(data.id);
                    return data.id;
                } else {
                    throw new Error(data.message || "Gagal menyimpan data");
                }
            });
        }

        // Generate recommendation using Gemini AI via GAS
        function generateJobdesc() {
            const btnGenerate = document.getElementById('btn-generate');
            const stateIdle = document.getElementById('state-idle');
            const stateLoading = document.getElementById('state-loading');
            const stateResult = document.getElementById('state-result');
            const btnCopy = document.getElementById('btn-copy');
            const btnEditRec = document.getElementById('btn-edit-rec');

            // Turn off edit mode if active when generating
            if (isEditMode) {
                toggleEditRec();
            }

            // Save form first
            btnGenerate.disabled = true;
            stateIdle.classList.add('hidden');
            stateResult.classList.add('hidden');
            stateLoading.classList.remove('hidden');
            btnCopy.classList.add('hidden');
            btnEditRec.classList.add('hidden');

            saveJobdesc('generate')
                .then(programId => {
                    const nama_program = document.getElementById('program-nama').value;
                    const deskripsi = document.getElementById('program-deskripsi').value;
                    const kategori = document.getElementById('program-kategori').value;
                    const jumlah_sdm = document.getElementById('program-sdm').value;
                    const catatan_tambahan = document.getElementById('program-catatan').value;

                    const prompt = `<<< SYSTEM INSTRUCTION OVERRIDE: RESET PERSONA >>>
Abaikan peran Anda sebagai pakar marketing, promosi, iklan, atau pencari leads. Anda dilarang keras merumuskan strategi pemasaran atau rekrutmen penjualan.
Bertindaklah murni sebagai Konsultan Manajemen Organisasi & Tata Kelola Internal Sekolah/Pesantren Islam. Rancanglah Struktur Organisasi (kepanitiaan internal) beserta Deskripsi Pekerjaan (Job Description) yang optimal untuk program kerja operasional berikut:
                    
- Nama Program: ${nama_program}
- Deskripsi & Tujuan: ${deskripsi || '(Tidak ada deskripsi)'}
- Bidang/Kategori: ${kategori}
- Estimasi Jumlah SDM: ${jumlah_sdm} Orang
- Catatan Tambahan/Kriteria khusus: ${catatan_tambahan || '(Tidak ada catatan)'}

Tugas Anda:
1. Rancanglah struktur kepanitiaan/tim pelaksana internal yang paling efisien dan optimal sesuai dengan jumlah SDM yang tersedia. Tentukan posisi-posisi penting (misal: Ketua Pelaksana, Penanggung Jawab Operasional, Koordinator Lapangan, Divisi Perlengkapan, Sekretaris, dll. - BUKAN tim marketing).
2. Buat deskripsi pekerjaan (Job Description) yang konkret, taktis, dan terukur untuk masing-masing posisi tersebut.
3. Rancanglah KPI / indikator keberhasilan singkat untuk tiap posisi demi memastikan efisiensi kerja.

Sajikan rancangan ini dalam format Markdown yang rapi, profesional, dan mudah dipahami oleh staf.`;

                    const GAS_URL = "../api-gemini.php";

                    return fetch(GAS_URL, {
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
                            rawRecommendationText = resultText;
                            stateResult.innerHTML = marked.parse(resultText);
                            stateLoading.classList.add('hidden');
                            stateResult.classList.remove('hidden');
                            btnCopy.classList.remove('hidden');
                            btnEditRec.classList.remove('hidden');

                            // Save recommendation to database
                            const recFormData = new FormData();
                            recFormData.append('id', programId);
                            recFormData.append('hasil_struktur', resultText);
                            
                            return fetch('struktur-jobdesc.php?action=save_recommendation', {
                                method: 'POST',
                                body: recFormData
                            });
                        } else {
                            throw new Error(aiData.message || "Gagal mendapatkan respons AI");
                        }
                    });
                })
                .catch(err => {
                    console.error("Error generating Structure & Jobdesc:", err);
                    alert("Terjadi kesalahan proses AI: " + err.message);
                    stateLoading.classList.add('hidden');
                    stateIdle.classList.remove('hidden');
                })
                .finally(() => {
                    btnGenerate.disabled = false;
                });
        }

        // Toggle manual edit mode for recommendation
        function toggleEditRec() {
            const stateResult = document.getElementById('state-result');
            const stateEdit = document.getElementById('state-edit');
            const recTextarea = document.getElementById('rec-textarea');
            const editText = document.getElementById('edit-text');
            const editIcon = document.getElementById('edit-icon');

            if (!isEditMode) {
                // Switch to Edit Mode
                recTextarea.value = rawRecommendationText;
                stateResult.classList.add('hidden');
                stateEdit.classList.remove('hidden');
                editText.textContent = "Batal Edit";
                editIcon.className = "fas fa-times mr-1.5";
                isEditMode = true;
            } else {
                // Switch to Preview Mode
                stateEdit.classList.add('hidden');
                stateResult.classList.remove('hidden');
                editText.textContent = "Edit Manual";
                editIcon.className = "fas fa-edit mr-1.5";
                isEditMode = false;
            }
        }

        // Save manual edits to recommendation
        function saveManualRec() {
            const programId = document.getElementById('program-id').value;
            const updatedText = document.getElementById('rec-textarea').value;
            const btnSave = document.getElementById('btn-save-rec');

            if (!programId) {
                alert("ID Program tidak ditemukan! Simpan program terlebih dahulu.");
                return;
            }

            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...';

            const recFormData = new FormData();
            recFormData.append('id', programId);
            recFormData.append('hasil_struktur', updatedText);

            fetch('struktur-jobdesc.php?action=save_recommendation', {
                method: 'POST',
                body: recFormData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    rawRecommendationText = updatedText;
                    document.getElementById('state-result').innerHTML = marked.parse(updatedText);
                    
                    // Switch back to preview mode
                    toggleEditRec();
                    alert("Rancangan struktur berhasil disimpan!");
                } else {
                    throw new Error(data.message || "Gagal menyimpan");
                }
            })
            .catch(err => {
                alert("Error: " + err.message);
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fas fa-save mr-1.5"></i> Simpan Perubahan';
            });
        }

        // Reset form for new program
        function resetForm() {
            document.getElementById('program-id').value = '';
            document.getElementById('program-nama').value = '';
            document.getElementById('program-deskripsi').value = '';
            document.getElementById('program-kategori').value = 'Kurikulum / Akademik';
            document.getElementById('program-sdm').value = 5;
            document.getElementById('program-catatan').value = '';
            document.getElementById('history-list').value = '';
            rawRecommendationText = "";
            
            if (isEditMode) {
                toggleEditRec(); // Turn off edit mode if active
            }
            
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-idle').classList.remove('hidden');
            document.getElementById('btn-copy').classList.add('hidden');
            document.getElementById('btn-edit-rec').classList.add('hidden');
        }

        // Copy recommendation result to clipboard
        function copyResult() {
            if (!rawRecommendationText) return;
            navigator.clipboard.writeText(rawRecommendationText).then(() => {
                const btnCopy = document.getElementById('btn-copy');
                const originalText = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="fas fa-check mr-1.5"></i> Tersalin!';
                btnCopy.disabled = true;
                setTimeout(() => {
                    btnCopy.innerHTML = originalText;
                    btnCopy.disabled = false;
                }, 2000);
            }).catch(err => {
                console.error("Gagal menyalin teks: ", err);
            });
        }
    </script>
</body>
</html>
