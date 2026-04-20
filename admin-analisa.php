<?php
require_once 'koneksi.php';

// Ambil data perilaku Leads dari Pipeline untuk dianalisa (Maksimal 200 data terbaru agar tidak membebani token)
$leads_data = [];
// Kita hanya mengambil jenis_lead, sumber_info, dan status untuk dianalisa (Nama dan WA tidak dikirim demi privasi)
$sql = "SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 200";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $leads_data[] = $row;
    }
}
$leads_json = json_encode($leads_data);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisa Buyer Persona (AI) | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Gunakan Marked.js untuk mem-parsing Markdown dari Gemini menjadi HTML yang cantik -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* Styling untuk hasil render Markdown */
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #064e3b; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #047857; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-20 h-full absolute md:relative">
        <div class="h-16 flex items-center justify-center border-b border-gray-800 px-4">
            <span class="text-xl font-bold text-emerald-400"><i class="fas fa-leaf mr-2"></i>VQ Admin</span>
        </div>
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="space-y-1 px-2">
                <a href="admin.html" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-tachometer-alt w-6 text-center"></i><span class="ml-3 font-medium">Dashboard</span>
                </a>
                <div class="pt-4 pb-2"><p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Marketing & Leads</p></div>
                <a href="data-pipeline.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-columns w-6 text-center"></i><span class="ml-3 font-medium">Pipeline Prospek</span>
                </a>
                <a href="data-agen.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-handshake w-6 text-center"></i><span class="ml-3 font-medium">Data Agen</span>
                </a>
                <a href="admin-analisa.php" class="flex items-center px-4 py-3 bg-purple-600 text-white rounded-lg transition group shadow-md">
                    <i class="fas fa-brain w-6 text-center"></i><span class="ml-3 font-medium">Analisa Buyer Persona</span>
                </a>
                <div class="pt-4 pb-2"><p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sistem</p></div>
                <a href="admin-popup.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-bullhorn w-6 text-center"></i><span class="ml-3 font-medium">Pengaturan Pop-up</span>
                </a>
            </nav>
        </div>
        <div class="p-4 border-t border-gray-800">
            <a href="index.html" class="flex items-center w-full px-4 py-2 text-sm text-gray-400 hover:text-white transition">
                <i class="fas fa-sign-out-alt w-5"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-robot text-purple-600 mr-2"></i>AI Analisa Buyer Persona</h1>
                    <p class="text-sm text-gray-500 mt-1">Ditenagai oleh Google Gemini AI. Membaca data pendaftar dari Pipeline secara otomatis.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Panel Kiri: Kontrol & Status -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Status Data Tersedia</h3>
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-gray-600">Total Sampel Data:</span>
                            <span class="font-bold text-emerald-600 bg-emerald-100 px-3 py-1 rounded-full"><?= count($leads_data) ?> Prospek</span>
                        </div>
                        <p class="text-xs text-gray-500 mb-6 italic">Data yang dikirim ke AI dienkripsi secara anonim (Nama dan Nomor WA disembunyikan demi privasi).</p>
                        
                        <button id="btn-analisa" onclick="jalankanAnalisa()" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md flex items-center justify-center group">
                            <i class="fas fa-magic mr-2 group-hover:rotate-12 transition transform"></i> Mulai Analisa Data
                        </button>
                    </div>

                    <div class="bg-purple-50 rounded-xl shadow-sm border border-purple-100 p-6">
                        <h4 class="font-bold text-purple-900 mb-2"><i class="fas fa-lightbulb text-amber-500 mr-2"></i> Mengapa Fitur Ini Penting?</h4>
                        <p class="text-sm text-purple-800 leading-relaxed">AI akan mempelajari darimana prospek Anda berasal (Iklan, Brosur, dll) dan melihat interaksi mereka. Ini membantu Anda menyusun materi promosi sekaligus merekomendasikan platform media (Ads/Organik) yang paling efektif!</p>
                    </div>
                </div>

                <!-- Panel Kanan: Hasil Analisa -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[400px] flex flex-col overflow-hidden">
                        <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800"><i class="fas fa-chart-pie mr-2"></i> Laporan Analisa</h3>
                            <span id="badge-status" class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-200 text-gray-600">Menunggu Perintah</span>
                        </div>
                        
                        <div id="result-container" class="p-6 flex-1 overflow-y-auto relative">
                            <!-- Layar Awal -->
                            <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-12">
                                <i class="fas fa-brain text-6xl mb-4 opacity-50"></i>
                                <p>Klik "Mulai Analisa Data" untuk merancang Buyer Persona Anda.</p>
                            </div>
                            
                            <!-- Layar Loading -->
                            <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-purple-600 py-12">
                                <i class="fas fa-spinner fa-spin text-5xl mb-4"></i>
                                <p class="font-bold animate-pulse">AI Gemini sedang berpikir & meracik strategi...</p>
                                <p class="text-sm text-gray-500 mt-2">Ini membutuhkan waktu sekitar 5 - 10 detik.</p>
                            </div>

                            <!-- Layar Hasil -->
                            <div id="state-result" class="hidden markdown-body">
                                <!-- Hasil render Markdown dari Gemini akan masuk ke sini -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // ==========================================
        // SETTING: PASTE URL WEB APP GAS DI BAWAH INI
        // ==========================================
        const GAS_WEB_APP_URL = "URL_GAS_BOS_DI_SINI"; 
        
        const rawLeadsData = <?= $leads_json ?>;

        function jalankanAnalisa() {
            if (rawLeadsData.length === 0) {
                alert("Belum ada data pendaftar/prospek di Pipeline untuk dianalisa!");
                return;
            }
            
            if (GAS_WEB_APP_URL === "URL_GAS_BOS_DI_SINI") {
                alert("Mohon masukkan URL Google Apps Script Anda terlebih dahulu di source code (baris bawah)!");
                return;
            }

            // Atur UI State ke Loading
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-600 animate-pulse";
            document.getElementById('badge-status').textContent = "Menganalisa...";
            document.getElementById('btn-analisa').disabled = true;
            document.getElementById('btn-analisa').classList.add('opacity-50', 'cursor-not-allowed');

            // Tembak data ke Google Apps Script (GAS) menggunakan POST
            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'text/plain;charset=utf-8' // GAS Web App lebih stabil menerima plain text
                },
                body: JSON.stringify({ leads: rawLeadsData })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    // Ubah Markdown dari Gemini menjadi format HTML menggunakan Marked.js
                    document.getElementById('state-result').innerHTML = marked.parse(data.result);
                    
                    document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700";
                    document.getElementById('badge-status').textContent = "Selesai";
                } else {
                    throw new Error(data.message || "Gagal memproses data AI");
                }
            })
            .catch(error => {
                console.error("Error AI:", error);
                document.getElementById('state-result').innerHTML = `<div class="text-red-500 bg-red-50 p-4 rounded-lg border border-red-200"><i class="fas fa-exclamation-triangle mr-2"></i> Terjadi kesalahan: ${error.message}</div>`;
                document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700";
                document.getElementById('badge-status').textContent = "Error";
            })
            .finally(() => {
                // Kembalikan UI State dari Loading ke Selesai
                document.getElementById('state-loading').classList.add('hidden');
                document.getElementById('state-result').classList.remove('hidden');
                
                // Aktifkan tombol kembali
                document.getElementById('btn-analisa').disabled = false;
                document.getElementById('btn-analisa').classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>
</body>
</html>