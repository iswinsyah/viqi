<?php
require_once 'koneksi.php';

// Ambil data perilaku Leads dari Pipeline untuk dianalisa
$leads_data = [];
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
    <title>Kalender Konten AI | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* Styling untuk hasil render Markdown agar tabel terlihat rapi */
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #0284c7; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #0369a1; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
        /* Styling khusus Tabel Kalender */
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.75rem; text-align: left; vertical-align: top; }
        .markdown-body th { background-color: #f8fafc; font-weight: bold; color: #0f172a; white-space: nowrap; }
        .markdown-body tr:nth-child(even) { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php $active_menu = 'kalender'; include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-calendar-alt text-sky-600 mr-2"></i>AI Kalender Konten (30 Hari)</h1>
                    <p class="text-sm text-gray-500 mt-1">Otomatis buat ide postingan sosmed setiap hari berdasarkan tren prospek Anda.</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-center p-6 gap-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center text-xl mr-4">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Gemini Content Generator</h3>
                        <p class="text-sm text-gray-500">Membaca <?= count($leads_data) ?> data prospek untuk menemukan ide konten paling relevan.</p>
                    </div>
                </div>
                <button id="btn-generate" onclick="jalankanGenerator()" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group whitespace-nowrap">
                    <i class="fas fa-magic mr-2 group-hover:rotate-12 transition transform"></i> Buat Kalender Sekarang
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[500px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-table mr-2"></i> Tabel Jadwal Konten</h3>
                    <span id="badge-status" class="text-xs font-semibold px-2 py-1 rounded-full bg-gray-200 text-gray-600">Menunggu Perintah</span>
                </div>
                
                <div id="result-container" class="p-6 flex-1 overflow-x-auto relative">
                    <!-- Layar Awal -->
                    <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center">
                        <i class="fas fa-calendar-plus text-6xl mb-4 opacity-50"></i>
                        <p>Klik tombol di atas untuk merancang kalender 30 hari Anda.</p>
                    </div>
                    
                    <!-- Layar Loading -->
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-sky-600 py-16 text-center">
                        <i class="fas fa-spinner fa-spin text-5xl mb-4"></i>
                        <p class="font-bold animate-pulse">Menyusun jadwal 30 hari...</p>
                        <p class="text-sm text-gray-500 mt-2">Menyesuaikan copywriting dengan persona pembeli Anda.</p>
                    </div>

                    <!-- Layar Hasil -->
                    <div id="state-result" class="hidden markdown-body min-w-[800px]">
                        <!-- Hasil render Markdown dari Gemini akan masuk ke sini -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const GAS_WEB_APP_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec"; 
        const rawLeadsData = <?= $leads_json ?>;

        function jalankanGenerator() {
            if (rawLeadsData.length === 0) {
                alert("Belum ada data prospek di Pipeline untuk dijadikan acuan!");
                return;
            }

            // Atur UI State ke Loading
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-600 animate-pulse";
            document.getElementById('badge-status').textContent = "Membuat Konten...";
            document.getElementById('btn-generate').disabled = true;
            document.getElementById('btn-generate').classList.add('opacity-50', 'cursor-not-allowed');

            // Tembak data ke GAS, ditambahkan TYPE = 'kalender'
            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: rawLeadsData, type: 'kalender' })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    document.getElementById('state-result').innerHTML = marked.parse(data.result);
                    document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700";
                    document.getElementById('badge-status').textContent = "Selesai";
                } else {
                    throw new Error(data.message || "Gagal memproses data AI");
                }
            })
            .catch(error => {
                console.error("Error AI:", error);
                document.getElementById('state-result').innerHTML = `<div class="text-red-500 bg-red-50 p-4 rounded-lg border border-red-200"><i class="fas fa-exclamation-triangle mr-2"></i> ${error.message}</div>`;
                document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-red-100 text-red-700";
                document.getElementById('badge-status').textContent = "Error";
            })
            .finally(() => {
                document.getElementById('state-loading').classList.add('hidden');
                document.getElementById('state-result').classList.remove('hidden');
                document.getElementById('btn-generate').disabled = false;
                document.getElementById('btn-generate').classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    </script>
</body>
</html>