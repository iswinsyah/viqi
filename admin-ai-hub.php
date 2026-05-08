<?php
require_once 'auth.php'; // Menggunakan sistem login admin Anda
require_once 'koneksi.php';
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
        // === KONFIGURASI URL GOOGLE APPS SCRIPT (GAS) ANDA ===
        // Nanti silakan ganti URL di bawah dengan URL Deploy Web App GAS milik Anda
        const GAS_URL_PERSONA = "URL_GAS_ANDA_UNTUK_PERSONA";
        const GAS_URL_CALENDAR = "URL_GAS_ANDA_UNTUK_KALENDER";
        const GAS_URL_ARTICLE = "URL_GAS_ANDA_UNTUK_ARTIKEL";
        const GAS_URL_SOSMED = "URL_GAS_ANDA_UNTUK_SOSMED";

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
            addLog("Mulai mengeksekusi Workflow Berantai...");

            try {
                // ----------------------------------------------------
                // TAHAP 1: AGENT PERSONA
                // ----------------------------------------------------
                setAgentStatus(1, 'loading');
                addLog("Agent 1 mengumpulkan data analitik dari Database MySQL...");
                
                // Di dunia nyata, Anda mungkin nge-fetch data dari file PHP lokal dulu, lalu kirim ke GAS
                // Simulasi memanggil GAS:
                // let responsePersona = await fetch(GAS_URL_PERSONA, { method: 'POST', body: JSON.stringify({ action: 'generate_persona' }) });
                // let personaData = await responsePersona.json();
                
                await new Promise(r => setTimeout(r, 2000)); // Simulasi jeda 2 detik
                let personaResult = "Profil: Ibu 35 Tahun, Jawa Timur, Suka Kajian Parenting"; 
                
                addLog("Agent 1 Selesai. Hasil: " + personaResult);
                setAgentStatus(1, 'success');

                // ----------------------------------------------------
                // TAHAP 2: AGENT KALENDER KONTEN
                // ----------------------------------------------------
                setAgentStatus(2, 'loading');
                addLog("Agent 2 menerima data Persona. Mulai menyusun ide konten mingguan...");
                
                // Simulasi melempar hasil Agent 1 ke Agent 2 via GAS:
                // let responseCal = await fetch(GAS_URL_CALENDAR, { method: 'POST', body: JSON.stringify({ persona: personaResult }) });
                
                await new Promise(r => setTimeout(r, 2500));
                let topicResult = "Topik Hari Ini: Pentingnya Sanad dalam Menghafal Quran";
                
                addLog("Agent 2 Selesai. " + topicResult);
                setAgentStatus(2, 'success');

                // ----------------------------------------------------
                // TAHAP 3: AGENT ARTIKEL SEO
                // ----------------------------------------------------
                setAgentStatus(3, 'loading');
                addLog("Agent 3 menerima Topik. Menulis artikel SEO 1000 kata...");
                
                // Simulasi melempar hasil Agent 2 ke Agent 3:
                // let responseArt = await fetch(GAS_URL_ARTICLE, { method: 'POST', body: JSON.stringify({ topik: topicResult }) });
                
                await new Promise(r => setTimeout(r, 4000));
                let articleResult = "Artikel berhasil dibuat dan disimpan di draft MySQL.";
                
                addLog("Agent 3 Selesai. " + articleResult);
                setAgentStatus(3, 'success');

                // ----------------------------------------------------
                // TAHAP 4: AGENT SOSIAL MEDIA
                // ----------------------------------------------------
                setAgentStatus(4, 'loading');
                addLog("Agent 4 membaca artikel. Meringkas menjadi Caption Instagram...");
                
                // Simulasi melempar hasil Agent 3 ke Agent 4:
                // let responseSoc = await fetch(GAS_URL_SOSMED, { method: 'POST', body: JSON.stringify({ artikel: articleResult }) });
                
                await new Promise(r => setTimeout(r, 2000));
                let sosmedResult = "Caption siap: 'Tahukah bunda pentingnya sanad? 🤔✨ #Tahfidz #Parenting'";
                
                addLog("Agent 4 Selesai. " + sosmedResult);
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