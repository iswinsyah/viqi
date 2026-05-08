<?php
require_once 'auth.php';
require_once 'koneksi.php';

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

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <h2 class="font-bold text-gray-800 text-xl"><i class="fas fa-robot text-indigo-600 mr-2"></i> Pusat Kendali AI Agent (Workflow)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-8 bg-indigo-700 text-white rounded-xl p-8 shadow-lg flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold mb-2">Automated Marketing AI</h1>
                    <p class="text-indigo-200">Biarkan AI bekerja dari riset audiens hingga pembuatan postingan sosmed secara otomatis.</p>
                </div>
                <button id="btn-start-workflow" onclick="startWorkflow()" class="bg-amber-400 hover:bg-amber-500 text-indigo-900 font-bold py-4 px-8 rounded-full shadow-xl transition-all flex items-center transform hover:scale-105">
                    <i class="fas fa-play-circle text-2xl mr-3"></i> Jalankan Semua Agent
                </button>
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
                        sumber_info: `ATURAN WAJIB:\n1. KEMBALIKAN OUTPUT FORMAT JSON.\n- TOPIK: ${topic}\n- JUDUL: ${judul}\n- KEYWORD: ${keyword}\nGunakan format: {"judul":"","meta_title":"","meta_description":"","meta_keywords":"","konten":""}`,
                        status: "URGENT"
                    });

                    let resSEO = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: payloadSEO, type: 'seo', topik: topic, judul: judul, keyword: keyword }) });
                    let dataSEO = await resSEO.json();
                    if(dataSEO.status !== 'success') throw new Error(dataSEO.message);
                    
                    // Parsing JSON Artikel dan Simpan sebagai Draft di Database
                    let cleanJson = dataSEO.result.replace(/```json/gi, '').replace(/```/g, '').trim();
                    let objSEO = JSON.parse(cleanJson);

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
                        let link = appUrl + "/artikel-detail.php?id=" + newArticleId + "&ref=" + agen.kode_ref;
                        pesan = pesan.replace('[NAMA_AGEN]', agen.nama).replace('[LINK_ARTIKEL]', link);

                        addLog(`> Mengirim pesan ke ${agen.nama} (${agen.whatsapp})...`);

                        // === EKSEKUSI KIRIM WA VIA FONNTE ===
                        if(WA_GATEWAY_TOKEN !== 'TOKEN_API_FONNTE_ANDA') {
                            let waFd = new FormData(); waFd.append('target', agen.whatsapp); waFd.append('message', pesan);
                            fetch('https://api.fonnte.com/send', { method: 'POST', headers: { 'Authorization': WA_GATEWAY_TOKEN }, body: waFd });
                        }
                        
                        // Tunggu Jeda Alami (8 sampai 15 detik) untuk mencegah blokir WA
                        if (i < rawAgenData.length - 1) {
                            let delay = Math.floor(Math.random() * (15000 - 8000 + 1)) + 8000;
                            addLog(`(Menunggu ${Math.round(delay/1000)} detik jeda alami anti-banned...)`);
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
    </script>
</body>
</html>