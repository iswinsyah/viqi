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

            <!-- WORKFLOW PIPELINE UI -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 relative">
                
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
        
        const rawLeadsData = <?= json_encode($leads_data) ?>;
        const rawFootprintsData = <?= json_encode($footprints_data) ?>;

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

            try {
                if(rawLeadsData.length === 0) throw new Error("Data prospek kosong, AI butuh data untuk belajar.");

                // ----------------------------------------------------
                // TAHAP 1: AGENT PERSONA
                // ----------------------------------------------------
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

                // ----------------------------------------------------
                // TAHAP 2: AGENT KALENDER KONTEN
                // ----------------------------------------------------
                setAgentStatus(2, 'loading');
                addLog("Agent 2 mulai menyusun kalender konten 30 hari...");
                
                const today = new Date().toISOString().slice(0,10);
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
                
                addLog("Agent 2 Selesai. Kalender Konten siap.");
                setAgentStatus(2, 'success');

                // ----------------------------------------------------
                // Ekstrak Topik & Judul Hari Ini dari Kalender (Trik Otomatisasi)
                // ----------------------------------------------------
                let tableRows = dataKalender.result.split('\n').filter(l => l.trim().startsWith('|'));
                let topic = "Manfaat Menghafal Al-Quran di Lingkungan Asri";
                let judul = "Manfaat Menghafal Al-Quran bagi Anak";
                let keyword = "tahfidz anak, pesantren asri";
                
                // Ambil baris ke-3 (Header, Pemisah, Data Hari 1)
                if (tableRows.length >= 3) {
                    let cols = tableRows[2].split('|').map(s => s.trim());
                    if (cols.length >= 8) {
                        topic = cols[4]; judul = cols[6]; keyword = cols[7];
                    }
                }
                addLog(`Info: Topik hari ini yang didapat otomatis -> "${topic}"`);

                // ----------------------------------------------------
                // TAHAP 3: AGENT ARTIKEL SEO (Menggunakan topik ekstrak)
                // ----------------------------------------------------
                setAgentStatus(3, 'loading');
                addLog("Agent 3 menulis artikel SEO berdasarkan topik hari ini...");
                
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
                await fetch('admin-seo.php', { method: 'POST', body: fdSEO });
                
                addLog("Agent 3 Selesai. Artikel telah disimpan sebagai Draft di menu Artikel.");
                setAgentStatus(3, 'success');

                // ----------------------------------------------------
                // TAHAP 4: AGENT SOSIAL MEDIA
                // ----------------------------------------------------
                setAgentStatus(4, 'loading');
                addLog("Agent 4 menyiapkan skrip konten sosmed untuk hari ini...");
                
                let resSosmed = await fetch(GAS_URL, { method: 'POST', headers:{'Content-Type':'text/plain;charset=utf-8'}, body: JSON.stringify({ leads: rawLeadsData, type: 'sosmed', date: today }) });
                let dataSosmed = await resSosmed.json();
                if(dataSosmed.status !== 'success') throw new Error(dataSosmed.message);
                
                let fdSosmed = new FormData(); fdSosmed.append('action', 'save_sosmed'); fdSosmed.append('content', dataSosmed.result);
                await fetch('admin-sosmed.php', { method: 'POST', body: fdSosmed });
                
                addLog("Agent 4 Selesai. Konten sosmed hari ini berhasil dibuat.");
                setAgentStatus(4, 'success');

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