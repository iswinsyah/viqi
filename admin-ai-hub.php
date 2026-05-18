<?php
require_once 'auth.php';
require_once 'koneksi.php';

// --- SAKLAR OTORITAS AGENT OTONOM ---
if (isset($_POST['toggle_autopilot'])) {
    $status = $_POST['toggle_autopilot'] === 'ON' ? 'ON' : 'OFF';
    file_put_contents('autopilot_status.txt', $status);
    echo "OK";
    exit;
}

// Ambil data Leads untuk bahan bakar AI
$leads_data = [];
$sql = "SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 100";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) $leads_data[] = $row;
}

// Ambil data Jejak Pengunjung (Footprint)
$footprints_data = [];
$sql_fp = "SELECT device, location, source, campaign FROM visitor_footprints ORDER BY id DESC LIMIT 100";
$result_fp = $conn->query($sql_fp);
if ($result_fp && $result_fp->num_rows > 0) {
    while($row = $result_fp->fetch_assoc()) $footprints_data[] = $row;
}

// Ambil data Agen untuk Broadcast WA
$agen_data = [];
$sql_agen = "SELECT nama, whatsapp, kode_ref FROM agen ORDER BY id ASC";
$result_agen = $conn->query($sql_agen);
if ($result_agen && $result_agen->num_rows > 0) {
    while($row = $result_agen->fetch_assoc()) $agen_data[] = $row;
}

// Cek waktu terakhir Agent 1 (Persona) berjalan (Mingguan)
$last_persona_time = file_exists('saved_persona.txt') ? filemtime('saved_persona.txt') : 0;
$days_since_persona = floor((time() - $last_persona_time) / (60 * 60 * 24));
$is_time_for_weekly = ($days_since_persona >= 7 || $last_persona_time == 0) ? true : false;

$autopilot = file_exists('autopilot_status.txt') ? file_get_contents('autopilot_status.txt') : 'OFF';
$saved_kalender = file_exists('saved_kalender.txt') ? file_get_contents('saved_kalender.txt') : '';

$active_menu = 'ai-hub';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent Hub | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING -->
    <?php include 'sidebar-marketing.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <h2 class="font-bold text-gray-800 text-xl"><i class="fas fa-robot text-indigo-600 mr-2"></i> Pusat Kendali AI Agent (Workflow)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            
            <!-- PANEL SAKLAR AI AGENT OTONOM -->
            <div class="mb-6 bg-gray-900 rounded-xl shadow-lg border border-gray-800 p-6 flex flex-col md:flex-row justify-between items-center relative overflow-hidden">
                <!-- Background Ornamen -->
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
                
                <div class="relative z-10 mb-4 md:mb-0">
                    <h2 class="text-2xl font-extrabold text-white flex items-center">
                        <i class="fas fa-satellite-dish text-emerald-400 mr-3 animate-pulse"></i> Mode Agen Otonom (Auto-Pilot)
                    </h2>
                    <p class="text-gray-400 text-sm mt-1 max-w-xl">Saat aktif, AI Agent akan bekerja sendiri (otonom) di server setiap pagi tanpa perlu Anda klik apapun. Layaknya karyawan digital yang mandiri.</p>
                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle text-blue-400 mr-1"></i> <b>Cara Kerja:</b> Daftarkan URL <code>https://<?= $_SERVER['HTTP_HOST'] ?? 'domain.com' ?>/cron-agent.php</code> ke fitur <b>Cron Jobs</b> di Hostinger untuk dipanggil setiap jam 07:00 pagi.</p>
                </div>
                <div class="relative z-10 flex items-center bg-gray-800 p-3 rounded-xl border border-gray-700">
                    <span class="text-xs font-bold text-gray-400 mr-3 px-2">IZIN KERJA AGENT:</span>
                    <label class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox" id="autopilot-toggle" class="sr-only peer" <?= $autopilot == 'ON' ? 'checked' : '' ?>>
                        <div class="w-14 h-7 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500 shadow-inner"></div>
                    </label>
                </div>
            </div>

            <!-- PANEL AUTOMATION KLASIK -->
            <div class="mb-8 bg-indigo-700 text-white rounded-xl p-8 shadow-lg flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Automated Marketing AI</h1>
                    <p class="text-indigo-200">Biarkan AI bekerja dari riset audiens hingga pembuatan postingan sosmed secara otomatis.</p>
                </div>
                <div class="flex flex-col space-y-3">
                    <button id="btn-start-workflow" onclick="startWorkflow()" class="bg-amber-400 hover:bg-amber-500 text-indigo-900 font-bold py-3 px-6 rounded-full shadow-xl transition-all flex items-center justify-center transform hover:scale-105">
                        <i class="fas fa-play-circle text-xl mr-2"></i> Mode Harian (Sekarang)
                    </button>
                    <button id="btn-batch-workflow" onclick="startBatchWorkflow()" class="bg-indigo-900 hover:bg-indigo-800 text-amber-400 border border-amber-400 font-bold py-3 px-6 rounded-full shadow-xl transition-all flex items-center justify-center transform hover:scale-105" title="Generate konten untuk 1 Bulan penuh">
                        <i class="fas fa-calendar-check text-xl mr-2"></i> Mode Borongan (1 Bulan)
                    </button>
                </div>
            </div>

            <!-- PENGATURAN WORKFLOW (TOGGLE AGENT) -->
            <div class="mb-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2"><i class="fas fa-sliders-h mr-2"></i> Pengaturan Otomatisasi Hari Ini</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-4">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="run-agent-1" class="form-checkbox h-5 w-5 text-indigo-600 rounded" <?= $is_time_for_weekly ? 'checked' : '' ?>>
                        <span class="text-sm font-bold text-gray-700">1. Analis (Mingguan)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="run-agent-2" class="form-checkbox h-5 w-5 text-indigo-600 rounded" <?= $is_time_for_weekly ? 'checked' : '' ?>>
                        <span class="text-sm font-bold text-gray-700">2. Perencana (Mingguan)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="run-agent-3" class="form-checkbox h-5 w-5 text-emerald-600 rounded" checked>
                        <span class="text-sm font-bold text-gray-700">3. Penulis (Harian)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer opacity-60">
                        <input type="checkbox" id="run-agent-4" class="form-checkbox h-5 w-5 text-pink-600 rounded">
                        <span class="text-sm font-bold text-gray-500">4. Sosmed (Off)</span>
                    </label>
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" id="run-agent-5" class="form-checkbox h-5 w-5 text-green-600 rounded" checked>
                        <span class="text-sm font-bold text-gray-700">5. WA Kurir (Harian)</span>
                    </label>
                </div>
                <div class="flex items-center">
                    <input id="auto-publish-checkbox" type="checkbox" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded" checked>
                    <label for="auto-publish-checkbox" class="ml-2 block text-sm text-gray-600">Langsung terbitkan tulisan tanpa perlu di-review (Auto-Publish)</label>
                </div>
            </div>

            <!-- WORKFLOW PIPELINE UI -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 relative">
                
                <!-- Garis Penghubung (Hanya hiasan UI) -->
                <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-gray-200 -z-10 transform -translate-y-1/2"></div>

                <!-- AGENT 1: BUYER PERSONA -->
                <div id="agent-1" class="bg-white rounded-xl shadow border-2 border-transparent p-6 relative transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white status-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">1. Agen Analis (Persona)</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Membaca data jejak untuk membuat profil target market.</p>
                    <div class="mt-4 text-center status-text text-sm font-semibold text-gray-400">Menunggu...</div>
                </div>

                <!-- AGENT 2: KALENDER KONTEN -->
                <div id="agent-2" class="bg-white rounded-xl shadow border-2 border-transparent p-6 relative transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white status-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">2. Agen Perencana</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Membuat ide konten berdasarkan Buyer Persona.</p>
                    <div class="mt-4 text-center status-text text-sm font-semibold text-gray-400">Menunggu...</div>
                </div>

                <!-- AGENT 3: GENERATOR ARTIKEL -->
                <div id="agent-3" class="bg-white rounded-xl shadow border-2 border-transparent p-6 relative transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white status-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">3. Agen Penulis (SEO)</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Menulis artikel panjang dari ide kalender konten.</p>
                    <div class="mt-4 text-center status-text text-sm font-semibold text-gray-400">Menunggu...</div>
                </div>

                <!-- AGENT 4: GENERATOR SOSMED -->
                <div id="agent-4" class="bg-white rounded-xl shadow border-2 border-transparent p-6 relative transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white status-icon">
                        <i class="fab fa-instagram"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">4. Agen Sosmed</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Meringkas artikel menjadi caption sosmed & hashtag.</p>
                    <div class="mt-4 text-center status-text text-sm font-semibold text-gray-400">Menunggu...</div>
                </div>

                <!-- AGENT 5: WA BROADCASTER -->
                <div id="agent-5" class="bg-white rounded-xl shadow border-2 border-transparent p-6 relative transition-all duration-300">
                    <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white status-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">5. Agen Kurir (WA)</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Membagikan link artikel baru secara personal ke tim agen.</p>
                    <div class="mt-4 text-center status-text text-sm font-semibold text-gray-400">Menunggu...</div>
                </div>
            </div>

            <!-- LOG HASIL KERJA (TERMINAL) -->
            <div class="mt-8 bg-gray-900 rounded-xl shadow-lg p-6">
                <h3 class="text-gray-100 font-bold mb-4 border-b border-gray-700 pb-2"><i class="fas fa-terminal mr-2"></i> Log Aktivitas AI</h3>
                <div id="console-log" class="h-64 overflow-y-auto font-mono text-sm text-green-400 space-y-2">
                    <div>> Sistem AI Agent Siap. Silakan tekan tombol "Jalankan Semua Agent".</div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // URL GAS Utama Anda yang digunakan di semua menu
        const GAS_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec";
        // URL Webhook dari Make.com atau Zapier untuk Auto-Publish Sosmed
        const MAKE_WEBHOOK_URL = "https://hook.us1.make.com/xxxxxxxxxxxxxxxxxxxxxxxxxxxxx"; // Nanti ganti dengan URL Make.com Anda
        // URL WA Gateway (Contoh Fonnte)
        const WA_GATEWAY_TOKEN = "Dtw72oRiQr8FympzpMHL"; 
        
        // Data
        const rawLeadsData = <?= json_encode($leads_data) ?>;
        const rawFootprintsData = <?= json_encode($footprints_data) ?>;
        const rawAgenData = <?= json_encode($agen_data) ?>;
        let dataKalenderResult = <?= json_encode($saved_kalender) ?>;

        function addLog(message, isError = false) {
            const logContainer = document.getElementById('console-log');
            const time = new Date().toLocaleTimeString();
            const colorClass = isError ? 'text-red-400' : 'text-green-400';
            logContainer.innerHTML += `<div class="${colorClass}">[${time}] > ${message}</div>`;
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function setAgentStatus(agentNumber, status) {
            const agentCard = document.getElementById(`agent-${agentNumber}`);
            const icon = agentCard.querySelector('.status-icon');
            const text = agentCard.querySelector('.status-text');

            // Reset classes
            agentCard.className = "bg-white rounded-xl shadow border-2 p-6 relative transition-all duration-300";
            icon.className = `w-12 h-12 rounded-full flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white status-icon ${icon.querySelector('i').className}`;
            
            if (status === 'loading') {
                agentCard.classList.add('border-indigo-400', 'animate-pulse');
                icon.classList.add('bg-indigo-100', 'text-indigo-600');
                icon.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                text.className = "mt-4 text-center status-text text-sm font-bold text-indigo-600";
                text.innerText = "Sedang Berpikir...";
            } else if (status === 'success') {
                agentCard.classList.add('border-emerald-500');
                icon.classList.add('bg-emerald-100', 'text-emerald-600');
                icon.innerHTML = '<i class="fas fa-check"></i>';
                text.className = "mt-4 text-center status-text text-sm font-bold text-emerald-600";
                text.innerText = "Selesai!";
            } else if (status === 'error') {
                agentCard.classList.add('border-red-500');
                icon.classList.add('bg-red-100', 'text-red-600');
                icon.innerHTML = '<i class="fas fa-times"></i>';
                text.className = "mt-4 text-center status-text text-sm font-bold text-red-600";
                text.innerText = "Gagal!";
            }
        }

        // ==========================================
        // TOGGLE AGENT OTONOM
        // ==========================================
        const autopilotToggle = document.getElementById('autopilot-toggle');
        if (autopilotToggle) {
            autopilotToggle.addEventListener('change', function(e) {
                const status = e.target.checked ? 'ON' : 'OFF';
                const formData = new FormData();
                formData.append('toggle_autopilot', status);
                fetch('admin-ai-hub.php', { method: 'POST', body: formData })
                .then(res => res.text()).then(text => {
                    if(text.trim() === 'OK') {
                        addLog("⚙️ Status Otoritas AI Agent Otonom berhasil diubah menjadi: " + status);
                    }
                });
            });
        }

        // ==========================================
        // OTAK UTAMA: AI WORKFLOW ORCHESTRATOR
        // ==========================================
        async function startWorkflow() {
            const btn = document.getElementById('btn-start-workflow');
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            document.getElementById('console-log').innerHTML = ''; // Clear log
            addLog("Mulai mengeksekusi Workflow Berantai...");

            const runAgent1 = document.getElementById('run-agent-1').checked;
            const runAgent2 = document.getElementById('run-agent-2').checked;
            const runAgent3 = document.getElementById('run-agent-3').checked;
            const runAgent4 = document.getElementById('run-agent-4').checked;
            const runAgent5 = document.getElementById('run-agent-5').checked;

            try {
                if(rawLeadsData.length === 0) throw new Error("Data prospek kosong, AI butuh data untuk belajar.");

                // ----------------------------------------------------
                // TAHAP 1: AGENT PERSONA
                // ----------------------------------------------------
                if (runAgent1) {
                    setAgentStatus(1, 'loading');
                    addLog("Agent 1 menganalisa sumber trafik & perilaku leads...");
                    
                    let payloadPersona = JSON.parse(JSON.stringify(rawLeadsData));
                    if (rawFootprintsData.length > 0) {
                        payloadPersona.unshift({ jenis_lead: "DATA_JEJAK", sumber_info: JSON.stringify(rawFootprintsData), status: "ANALISA_LOKASI" });
                    }
                    payloadPersona.unshift({
                        jenis_lead: "SYSTEM_COMMAND",
                        sumber_info: "PENTING: Lakukan analisa mendalam dari data yang diberikan. Buat laporan analisa Buyer Persona yang terstruktur tegas berdasarkan 3 level Funnel Marketing: TOFU, MOFU, BOFU.",
                        status: "URGENT"
                    });

                    let resPersona = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadPersona, type: 'persona' }) });
                    let dataPersona = await resPersona.json();
                    if(dataPersona.status !== 'success') throw new Error(dataPersona.message);
                    
                    // Simpan ke file lokal via PHP
                    let fdPersona = new FormData(); fdPersona.append('action', 'save_persona'); fdPersona.append('content', dataPersona.result);
                    await fetch('admin-analisa.php', { method: 'POST', body: fdPersona });
                    
                    addLog("Agent 1 Selesai. Persona berhasil disimpan.");
                    setAgentStatus(1, 'success');
                } else {
                    setAgentStatus(1, 'success');
                    addLog("Agent 1 dilewati (Sudah ada data persona minggu ini).");
                }

                // ----------------------------------------------------
                // TAHAP 2: AGENT KALENDER KONTEN
                // ----------------------------------------------------
                const today = new Date().toISOString().slice(0,10);
                if (runAgent2) {
                    setAgentStatus(2, 'loading');
                    addLog("Agent 2 mulai menyusun kalender konten 30 hari...");
                    
                    let payloadKalender = JSON.parse(JSON.stringify(rawLeadsData));
                    payloadKalender.unshift({
                        jenis_lead: "SYSTEM_COMMAND",
                        sumber_info: `PENTING: WAJIB BUAT DALAM BENTUK TABEL MARKDOWN. TANGGAL MULAI HARI 1: ${today}. BUAT FULL SAMPAI HARI KE-30. KOLOM TABEL: | Hari/Tanggal | Platform | Format | Topik/Ide Konten | Copywriting Singkat | Judul Artikel SEO | Keyword yang Disasar |. DILARANG memberikan teks pendahuluan!`,
                        status: "URGENT"
                    });

                    let resKalender = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadKalender, type: 'kalender', date: today }) });
                    let dataKalender = await resKalender.json();
                    if(dataKalender.status !== 'success') throw new Error(dataKalender.message);
                    
                    let fdKalender = new FormData(); fdKalender.append('action', 'save_kalender'); fdKalender.append('content', dataKalender.result);
                    await fetch('admin-kalender.php', { method: 'POST', body: fdKalender });
                    
                    dataKalenderResult = dataKalender.result;
                    addLog("Agent 2 Selesai. Kalender Konten siap.");
                    setAgentStatus(2, 'success');
                } else {
                    setAgentStatus(2, 'success');
                    addLog("Agent 2 dilewati (Menggunakan kalender yang sudah ada).");
                }

                // ----------------------------------------------------
                // Ekstrak Topik & Judul Hari Ini dari Kalender (Trik Otomatisasi)
                // ----------------------------------------------------
                let topic = "Manfaat Menghafal Al-Quran di Lingkungan Asri";
                let judul = "Manfaat Menghafal Al-Quran bagi Anak";
                let keyword = "tahfidz anak, pesantren asri";
                
                if (dataKalenderResult) {
                    let tableRows = dataKalenderResult.split('\n').filter(l => l.trim().startsWith('|'));
                    // Ambil baris ke-3 (Header, Pemisah, Data Hari 1)
                    if (tableRows.length >= 3) {
                        let foundRow = tableRows.find(row => row.includes(today));
                        
                        if (!foundRow) {
                            let randomIdx = Math.floor(Math.random() * (tableRows.length - 2)) + 2;
                            foundRow = tableRows[randomIdx];
                        }
                        
                        if (foundRow) {
                            let cols = foundRow.split('|').map(s => s.trim());
                            if (cols.length >= 8) {
                                topic = cols[4]; judul = cols[6]; keyword = cols[7];
                            }
                        }
                    }
                }
                addLog(`Info: Topik hari ini yang didapat otomatis -> "${topic}"`);

                let newArticleId = '';
                // ----------------------------------------------------
                // TAHAP 3: AGENT ARTIKEL SEO (Menggunakan topik ekstrak)
                // ----------------------------------------------------
                if (runAgent3) {
                    setAgentStatus(3, 'loading');
                    addLog("Agent 3 menulis tulisan berdasarkan topik hari ini...");
                    
                    let payloadSEO = JSON.parse(JSON.stringify(rawLeadsData));
                    payloadSEO.unshift({
                        jenis_lead: "SYSTEM_COMMAND",
                        sumber_info: `ATURAN WAJIB & KETAT:\n1. KEMBALIKAN OUTPUT DENGAN FORMAT TAG PEMISAH (BUKAN JSON). Ini untuk mencegah error sistem.\n\nGunakan format persis seperti ini:\n\n[JUDUL]\n${judul}\n[/JUDUL]\n\n[META_TITLE]\n...isi meta title yang menarik...\n[/META_TITLE]\n\n[META_DESC]\n...isi meta deskripsi maksimal 150 karakter...\n[/META_DESC]\n\n[META_KEY]\n${keyword}\n[/META_KEY]\n\n[KONTEN]\n...Isi artikel lengkap dengan tag HTML dasar (<p>, <h2>, <ul>, <strong>). DILARANG menggunakan markdown (* atau #) di dalam sini. Bahas tuntas mengenai topik di bawah ini...\n[/KONTEN]\n\n- TOPIK: ${topic}`,
                        status: "URGENT"
                    });

                    let resSEO = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadSEO, type: 'seo', topik: topic, judul: judul, keyword: keyword }) });
                    let dataSEO = await resSEO.json();
                    if(dataSEO.status !== 'success') throw new Error(dataSEO.message);
                    
                    // Parsing Format Custom Tag
                    let rawText = dataSEO.result;
                    let objSEO = {
                        judul: judul,
                        meta_title: judul,
                        meta_description: '',
                        meta_keywords: keyword,
                        konten: rawText
                    };
                    
                    const extractTag = (tag, text) => {
                        const regex = new RegExp(`\\[${tag}\\]([\\s\\S]*?)\\[\\/${tag}\\]`, 'i');
                        const match = text.match(regex);
                        return match ? match[1].trim() : null;
                    };

                    let extKonten = extractTag('KONTEN', rawText);
                    if (extKonten) {
                        objSEO.judul = extractTag('JUDUL', rawText) || objSEO.judul;
                        objSEO.meta_title = extractTag('META_TITLE', rawText) || objSEO.meta_title;
                        objSEO.meta_description = extractTag('META_DESC', rawText) || objSEO.meta_description;
                        objSEO.meta_keywords = extractTag('META_KEY', rawText) || objSEO.meta_keywords;
                        objSEO.konten = extKonten;
                    } else {
                        // Fallback jika AI masih ngeyel kirim JSON
                        try {
                            let cleanJson = rawText.replace(/```json/gi, '').replace(/```/g, '').trim();
                            let parsed = JSON.parse(cleanJson);
                            objSEO = Object.assign(objSEO, parsed);
                        } catch(e) {
                            addLog("Peringatan: Gagal mem-parsing format struktur AI. Menggunakan teks raw.", true);
                        }
                    }

                    let fdSEO = new FormData();
                    fdSEO.append('action', 'publish_blog');
                    fdSEO.append('judul', objSEO.judul || judul);
                    fdSEO.append('meta_title', objSEO.meta_title || judul);
                    fdSEO.append('meta_description', objSEO.meta_description || '');
                    fdSEO.append('meta_keywords', objSEO.meta_keywords || keyword);
                    fdSEO.append('konten', objSEO.konten || dataSEO.result);
                    fdSEO.append('auto_cover', 'true'); // Perintahkan sistem memilih gambar acak

                    const autoPublish = document.getElementById('auto-publish-checkbox').checked;
                    if (autoPublish) {
                        fdSEO.append('auto_publish', 'true');
                    }
                    let resSaveSeo = await fetch('admin-seo.php', { method: 'POST', body: fdSEO });
                    let textSaveSeo = await resSaveSeo.text();
                    newArticleId = textSaveSeo.split('|')[1] || ''; // Mengambil ID artikel baru
                    
                    addLog("Agent 3 Selesai. Tulisan telah diterbitkan.");
                    setAgentStatus(3, 'success');
                } else {
                    setAgentStatus(3, 'success');
                    addLog("Agent 3 dilewati.");
                }

                // ----------------------------------------------------
                // TAHAP 4: AGENT SOSIAL MEDIA
                // ----------------------------------------------------
                if (runAgent4) {
                    setAgentStatus(4, 'loading');
                    addLog("Agent 4 menyiapkan skrip konten sosmed untuk hari ini...");
                    
                    let resSosmed = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: rawLeadsData, type: 'sosmed', date: today }) });
                    let dataSosmed = await resSosmed.json();
                    if(dataSosmed.status !== 'success') throw new Error(dataSosmed.message);
                    
                    let fdSosmed = new FormData(); fdSosmed.append('action', 'save_sosmed'); fdSosmed.append('content', dataSosmed.result);
                    await fetch('admin-sosmed.php', { method: 'POST', body: fdSosmed });
                    
                    addLog("Agent 4 Selesai. Konten sosmed hari ini berhasil dibuat.");
                    setAgentStatus(4, 'success');
                } else {
                    setAgentStatus(4, 'success');
                    addLog("Agent 4 dilewati (Dinonaktifkan sementara).");
                }

                // ----------------------------------------------------
                // TAHAP 5: AGENT WHATSAPP BROADCASTER
                // ----------------------------------------------------
                if (runAgent5 && rawAgenData.length > 0 && newArticleId !== '') {
                    setAgentStatus(5, 'loading');
                    addLog("Agent 5 menyusun teks WA natural & mengirim ke " + rawAgenData.length + " agen...");

                    // Variasi Pesan Fallback (Jika API AI ngadat)
                    let variations = [
                        "Assalamu'alaikum [NAMA_AGEN], kita ada artikel baru nih. Minta tolong di-share ke grup atau story WA ya biar tambah ramai: [LINK_ARTIKEL] Terima kasih banyak!",
                        "Halo [NAMA_AGEN], artikel terbaru Villa Quran udah rilis hari ini: [LINK_ARTIKEL] Jangan lupa di-share pake link ini ya, biar leadnya masuk ke sampeyan.",
                        "Baru aja tayang nih artikelnya: [LINK_ARTIKEL] Monggo di-share [NAMA_AGEN], semoga jadi jalan pahala buat kita semua."
                    ];

                    // Minta AI membuat variasi pesan santai
                    let payloadWa = JSON.parse(JSON.stringify(rawLeadsData));
                    payloadWa.unshift({
                        jenis_lead: "SYSTEM_COMMAND",
                        sumber_info: `Tugas: Buat 3 variasi pesan WhatsApp natural/santai untuk agen promosi. Beritahu mereka ada artikel baru: "${judul}". Minta mereka share. WAJIB sisipkan placeholder [NAMA_AGEN] dan [LINK_ARTIKEL]. Kembalikan HANYA format JSON Array of strings: ["pesan1","pesan2","pesan3"]`,
                        status: "URGENT"
                    });

                    try {
                        let resWa = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadWa, type: 'wa_variasi' }) });
                        let dataWa = await resWa.json();
                        if (dataWa.status === 'success') {
                            let parsed = JSON.parse(dataWa.result.replace(/```json/gi, '').replace(/```/g, '').trim());
                            if (Array.isArray(parsed) && parsed.length > 0) variations = parsed;
                        }
                    } catch(e) { addLog("AI Gagal meracik WA, menggunakan template default.", true); }

                    // Loop Kirim ke Agen dengan JEDA ACAK (Random Delay)
                    const appUrl = window.location.origin + window.location.pathname.replace('/admin-ai-hub.php', '');
                    
                    for (let i = 0; i < rawAgenData.length; i++) {
                        let agen = rawAgenData[i];
                        let pesan = variations[Math.floor(Math.random() * variations.length)];
                        let link = appUrl + "/artikel-detail.php?id=" + newArticleId + "&ref=" + agen.whatsapp;
                        pesan = pesan.replace('[NAMA_AGEN]', agen.nama).replace('[LINK_ARTIKEL]', link);

                        addLog(`> Mengirim pesan ke ${agen.nama} (${agen.whatsapp})...`);

                        // === EKSEKUSI KIRIM WA VIA FONNTE ===
                        if(WA_GATEWAY_TOKEN !== 'TOKEN_API_FONNTE_ANDA') {
                            try {
                                let waFd = new FormData(); waFd.append('target', agen.whatsapp); waFd.append('message', pesan);
                                let resFonnte = await fetch('https://api.fonnte.com/send', { method: 'POST', headers: { 'Authorization': WA_GATEWAY_TOKEN }, body: waFd });
                                let jsonFonnte = await resFonnte.json();
                                
                                if(jsonFonnte.status) addLog(`  ✅ Berhasil dilesatkan ke jaringan WA!`);
                                else addLog(`  ❌ Gagal dikirim: ${jsonFonnte.reason || 'Sistem WA Gateway menolak'}`, true);
                            } catch(e) {
                                addLog(`  ❌ Terjadi kesalahan jaringan saat menghubungi Fonnte`, true);
                            }
                        }
                        
                        // Tunggu Jeda Alami yang disebar dalam rentang 10 jam (07:00 - 17:00)
                        if (i < rawAgenData.length - 1) {
                            // Total waktu tersedia: 10 jam = 36.000.000 ms
                            let totalWaktuMs = 10 * 60 * 60 * 1000;
                            // Rata-rata jeda per agen
                            let avgDelay = totalWaktuMs / rawAgenData.length;
                            
                            // Acak jeda antara 50% sampai 150% dari rata-rata agar natural
                            let minDelay = Math.floor(avgDelay * 0.5);
                            let maxDelay = Math.floor(avgDelay * 1.5);
                            
                            // Pastikan minimal jeda tidak kurang dari 1 menit demi keamanan
                            if (minDelay < 60000) minDelay = 60000;
                            if (maxDelay < 120000) maxDelay = 120000;

                            let delay = Math.floor(Math.random() * (maxDelay - minDelay + 1)) + minDelay;
                            let delayInMinutes = Math.round(delay / 60000);
                            addLog(`(Menunggu ${delayInMinutes} menit jeda aman sebelum pesan berikutnya ke agen lain...)`);
                            await new Promise(r => setTimeout(r, delay));
                        }
                    }

                    addLog("Agent 5 Selesai. Semua agen telah dihubungi.");
                    setAgentStatus(5, 'success');
                } else if (runAgent5 && newArticleId === '') {
                    setAgentStatus(5, 'success');
                    addLog("Agent 5 dilewati karena tidak ada artikel baru yang diterbitkan.");
                } else {
                    setAgentStatus(5, 'success');
                    addLog("Agent 5 dilewati.");
                }

                addLog("🎉 SEMUA WORKFLOW SELESAI DENGAN SUKSES!");
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');

            } catch (error) {
                addLog("Terjadi Kesalahan di tengah jalan: " + error.message, true);
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }

        // ==========================================
        // OTAK KEDUA: AI WORKFLOW BORONGAN (1 BULAN)
        // ==========================================
        async function startBatchWorkflow() {
            const btnBatch = document.getElementById('btn-batch-workflow');
            const btnHarian = document.getElementById('btn-start-workflow');
            
            if(!confirm("ANDA AKAN MASUK KE MODE DEWA (Borongan 1 Bulan).\nAI akan menulis 30 Artikel dan menjadwalkan ratusan WA ke Fonnte untuk 30 hari ke depan secara otomatis.\n\nEstimasi Waktu: 15 Menit.\n⚠️ Komputer dan halaman ini TIDAK BOLEH DITUTUP selama proses loading berjalan (sampai ada notifikasi selesai).\n\nLanjutkan?")) return;

            btnBatch.disabled = true; btnHarian.disabled = true;
            btnBatch.classList.add('opacity-50', 'cursor-not-allowed');
            btnHarian.classList.add('opacity-50', 'cursor-not-allowed');
            
            document.getElementById('console-log').innerHTML = ''; 
            addLog("🚀 MEMULAI MODE BORONGAN (BATCH) UNTUK 30 HARI KE DEPAN!");
            
            try {
                if(rawLeadsData.length === 0) throw new Error("Data prospek kosong.");

                // 1. KALENDER 30 HARI
                setAgentStatus(2, 'loading');
                addLog("Agent 2: Menyusun Kalender 30 Hari mulai besok...");
                
                let tomorrow = new Date(); tomorrow.setDate(tomorrow.getDate() + 1);
                let tomorrowStr = tomorrow.toISOString().slice(0,10);
                
                let payloadKalender = JSON.parse(JSON.stringify(rawLeadsData));
                payloadKalender.unshift({
                    jenis_lead: "SYSTEM_COMMAND",
                    sumber_info: `WAJIB BUAT TABEL MARKDOWN. TANGGAL MULAI: ${tomorrowStr}. BUAT FULL SAMPAI HARI KE-30. KOLOM TABEL: | Hari/Tanggal | Platform | Format | Topik | Copywriting | Judul SEO | Keyword |. DILARANG memberikan teks pendahuluan!`,
                    status: "URGENT"
                });
                
                let resKalender = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadKalender, type: 'kalender', date: tomorrowStr }) });
                let dataKalender = await resKalender.json();
                if(dataKalender.status !== 'success') throw new Error("Gagal membuat kalender");
                
                let tableRows = dataKalender.result.split('\n').filter(l => l.trim().startsWith('|'));
                let dataRows = tableRows.slice(2); 
                let maxDays = Math.min(30, dataRows.length);
                
                setAgentStatus(2, 'success');
                addLog(`💡 Ditemukan ${maxDays} ide konten. Memulai produksi massal...\n`);

                // 2. LOOPING EKSEKUSI HARIAN (30x PUTARAN)
                for (let d = 0; d < maxDays; d++) {
                    let cols = dataRows[d].split('|').map(s => s.trim());
                    if (cols.length < 8) continue;
                    
                    let targetDate = new Date(); targetDate.setDate(targetDate.getDate() + d + 1); 
                    let dateStr = targetDate.toISOString().slice(0,10);
                    let topic = cols[4]; let judul = cols[6]; let keyword = cols[7];
                    
                    addLog(`\n--- MENGKERJAKAN HARI KE-${d+1} (${dateStr}) ---`);
                    await new Promise(r => setTimeout(r, 6000)); // Delay cegah Limit API Google (429 Error)

                    // ARTIKEL SEO (Jadwal Terbit)
                    setAgentStatus(3, 'loading');
                    addLog(`Agent 3: Menulis "${judul}"...`);
                    let payloadSEO = JSON.parse(JSON.stringify(rawLeadsData.slice(0, 5)));
                    payloadSEO.unshift({
                        jenis_lead: "SYSTEM_COMMAND",
                        sumber_info: `KEMBALIKAN DGN TAG PEMISAH (BUKAN JSON): [JUDUL]${judul}[/JUDUL] [META_TITLE]..[/META_TITLE] [META_DESC]..[/META_DESC] [META_KEY]${keyword}[/META_KEY] [KONTEN]...isi HTML...[/KONTEN]. TOPIK: ${topic}`,
                        status: "URGENT"
                    });
                    let resSEO = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadSEO, type: 'seo', topik: topic, judul: judul, keyword: keyword }) });
                    let dataSEO = await resSEO.json();
                    
                    const extractTag = (tag, text) => { const match = text.match(new RegExp(`\\[${tag}\\]([\\s\\S]*?)\\[\\/${tag}\\]`, 'i')); return match ? match[1].trim() : null; };
                    let kontenHtml = extractTag('KONTEN', dataSEO.result) || dataSEO.result;

                    let fdSEO = new FormData();
                    fdSEO.append('action', 'publish_blog');
                    fdSEO.append('judul', extractTag('JUDUL', dataSEO.result) || judul);
                    fdSEO.append('meta_title', extractTag('META_TITLE', dataSEO.result) || judul);
                    fdSEO.append('meta_description', extractTag('META_DESC', dataSEO.result) || '');
                    fdSEO.append('meta_keywords', extractTag('META_KEY', dataSEO.result) || keyword);
                    fdSEO.append('konten', kontenHtml);
                    fdSEO.append('auto_cover', 'true');
                    fdSEO.append('status', 'jadwalkan');
                    fdSEO.append('published_at', `${dateStr} 07:00:00`); 

                    let resSaveSeo = await fetch('admin-seo.php', { method: 'POST', body: fdSEO });
                    let newArticleId = (await resSaveSeo.text()).split('|')[1] || ''; 
                    setAgentStatus(3, 'success');
                    
                    await new Promise(r => setTimeout(r, 6000)); // Delay

                    // WA FONNTE (Titip Jadwal)
                    if (rawAgenData.length > 0 && newArticleId !== '') {
                        setAgentStatus(5, 'loading');
                        let payloadWa = JSON.parse(JSON.stringify(rawLeadsData.slice(0,2)));
                        payloadWa.unshift({ jenis_lead: "SYSTEM_COMMAND", sumber_info: `Buat 2 variasi pesan WA santai untuk agen membagikan artikel: "${judul}". Sisipkan [NAMA_AGEN] dan [LINK_ARTIKEL]. Kembalikan HANYA Array JSON ["a","b"]`, status: "URGENT" });

                        let variations = ["Assalamu'alaikum [NAMA_AGEN], artikel baru rilis nih: [LINK_ARTIKEL] Minta tolong dishare ya!"];
                        try {
                            let resWa = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadWa, type: 'wa_variasi' }) });
                            let dataWa = await resWa.json();
                            let parsed = JSON.parse(dataWa.result.replace(/```json/gi, '').replace(/```/g, '').trim());
                            if (Array.isArray(parsed) && parsed.length > 0) variations = parsed;
                        } catch(e) {}

                        const appUrl = window.location.origin + window.location.pathname.replace('/admin-ai-hub.php', '');
                        
                        for (let i = 0; i < rawAgenData.length; i++) {
                            let agen = rawAgenData[i];
                            let pesan = variations[Math.floor(Math.random() * variations.length)];
                            let link = appUrl + "/artikel-detail.php?id=" + newArticleId + "&ref=" + agen.whatsapp;
                            pesan = pesan.replace('[NAMA_AGEN]', agen.nama).replace('[LINK_ARTIKEL]', link);

                            // Mengacak jam 07:00 sd 17:00 PADA TANGGAL TERBIT ARTIKEL
                            let hour = Math.floor(Math.random() * (17 - 7 + 1)) + 7;
                            let minute = Math.floor(Math.random() * 60);
                            let scheduleStr = `${dateStr} ${hour.toString().padStart(2,'0')}:${minute.toString().padStart(2,'0')}:00`;

                            addLog(`   > Menitipkan jadwal WA untuk ${agen.nama} pada ${scheduleStr}`);

                            if(WA_GATEWAY_TOKEN !== 'TOKEN_API_FONNTE_ANDA') {
                                try {
                                    let waFd = new FormData(); 
                                    waFd.append('target', agen.whatsapp); 
                                    waFd.append('message', pesan);
                                    waFd.append('schedule', scheduleStr); // FITUR AJAIB FONNTE
                                    await fetch('https://api.fonnte.com/send', { method: 'POST', headers: { 'Authorization': WA_GATEWAY_TOKEN }, body: waFd });
                                } catch(e) {}
                            }
                            await new Promise(r => setTimeout(r, 400)); 
                        }
                        setAgentStatus(5, 'success');
                    }
                }

                addLog("\n🎉 ALHAMDULILLAH! 1 BULAN SELESAI! Bos bisa menutup laptop sekarang dan pergi liburan.");
                btnBatch.disabled = false; btnHarian.disabled = false;
                btnBatch.classList.remove('opacity-50', 'cursor-not-allowed');
                btnHarian.classList.remove('opacity-50', 'cursor-not-allowed');

            } catch (error) {
                addLog("\n❌ Terjadi Kesalahan: " + error.message, true);
                btnBatch.disabled = false; btnHarian.disabled = false;
                btnBatch.classList.remove('opacity-50', 'cursor-not-allowed');
                btnHarian.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    </script>
</body>
</html>