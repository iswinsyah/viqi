<?php
require_once 'auth.php';
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

// Proses Simpan Hasil Analisa ke file lokal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_kalender') {
    $content = $_POST['content'] ?? '';
    file_put_contents('saved_kalender.txt', $content);
    echo "Sukses";
    exit;
}

$saved_kalender = file_exists('saved_kalender.txt') ? file_get_contents('saved_kalender.txt') : '';
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
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <span class="text-sm text-gray-500 font-medium hidden md:block">Mulai:</span>
                    <input type="date" id="tgl-kalender" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-sky-500 focus:border-sky-500 flex-1 md:w-auto">
                    <button id="btn-generate" onclick="jalankanGenerator()" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group whitespace-nowrap">
                        <i class="fas fa-magic mr-2 group-hover:rotate-12 transition transform"></i> Buat Kalender
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[500px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-table mr-2"></i> Tabel Jadwal Konten</h3>
                    <div class="flex items-center space-x-2">
                        <button id="btn-save" onclick="simpanHasil()" class="hidden bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-emerald-200"><i class="fas fa-save mr-1"></i> Simpan</button>
                        <button id="btn-save-as" onclick="simpanSebagai()" class="hidden bg-sky-100 text-sky-700 hover:bg-sky-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-sky-200"><i class="fas fa-file-download mr-1"></i> Save As</button>
                        <span id="badge-status" class="text-xs font-semibold px-2 py-1 rounded-full <?= !empty($saved_kalender) ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' ?>"><?= !empty($saved_kalender) ? 'Tersimpan' : 'Menunggu Perintah' ?></span>
                    </div>
                </div>
                
                <div id="result-container" class="p-6 flex-1 overflow-x-auto relative">
                    <!-- Layar Awal -->
                    <div id="state-idle" class="<?= !empty($saved_kalender) ? 'hidden' : 'flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center' ?>">
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
                    <div id="state-result" class="<?= !empty($saved_kalender) ? 'markdown-body min-w-[800px]' : 'hidden markdown-body min-w-[800px]' ?>">
                        <!-- Hasil render Markdown dari Gemini akan masuk ke sini -->
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const GAS_WEB_APP_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec"; 
        const rawLeadsData = <?= $leads_json ?>;
        const savedKalenderMarkdown = <?= json_encode($saved_kalender) ?>;
        let currentMarkdown = savedKalenderMarkdown;

        // Render data tersimpan saat halaman dimuat
        document.addEventListener("DOMContentLoaded", () => {
            if (savedKalenderMarkdown) {
                document.getElementById('state-result').innerHTML = marked.parse(savedKalenderMarkdown);
                const btnSave = document.getElementById('btn-save');
                btnSave.classList.remove('hidden');
                btnSave.innerHTML = '<i class="fas fa-check mr-1"></i> Tersimpan';
                btnSave.classList.replace('bg-emerald-100', 'bg-gray-100');
                btnSave.classList.replace('text-emerald-700', 'text-gray-500');
                btnSave.disabled = true;
                document.getElementById('btn-save-as').classList.remove('hidden');
            }

            // Set tanggal hari ini sebagai default
            const tglInput = document.getElementById('tgl-kalender');
            if(tglInput) tglInput.valueAsDate = new Date();
        });

        function jalankanGenerator() {
            const dateInput = document.getElementById('tgl-kalender').value;
            if (!dateInput) {
                alert("Silakan pilih tanggal mulai kalender terlebih dahulu!");
                return;
            }
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
            
            // Reset tombol save
            const btnSave = document.getElementById('btn-save');
            btnSave.classList.add('hidden');
            btnSave.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
            document.getElementById('btn-save-as').classList.add('hidden');
            if(btnSave.classList.contains('bg-gray-100')) {
                btnSave.classList.replace('bg-gray-100', 'bg-emerald-100');
                btnSave.classList.replace('text-gray-500', 'text-emerald-700');
            }
            btnSave.disabled = false;

            // INJEKSI PROMPT KETAT KE DALAM PAYLOAD (Teknik Prompt Injection agar AI patuh membuat tabel)
            const payloadLeads = JSON.parse(JSON.stringify(rawLeadsData));
            payloadLeads.unshift({
                jenis_lead: "SYSTEM_COMMAND",
                sumber_info: `PENTING: WAJIB BUAT DALAM BENTUK TABEL MARKDOWN. TANGGAL MULAI HARI 1: ${dateInput}. BUAT FULL SAMPAI HARI KE-30. KOLOM TABEL: | Hari/Tanggal | Platform | Format | Topik/Ide Konten | Copywriting Singkat |. DILARANG memberikan teks pendahuluan yang panjang, LANGSUNG TAMPILKAN TABEL MARKDOWN!`,
                status: "URGENT"
            });

            // Tembak data ke GAS, ditambahkan TYPE = 'kalender'
            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: payloadLeads, type: 'kalender', date: dateInput })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    currentMarkdown = data.result;
                    document.getElementById('state-result').innerHTML = marked.parse(currentMarkdown);
                    document.getElementById('btn-save').classList.remove('hidden');
                document.getElementById('btn-save-as').classList.remove('hidden');
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

        function simpanHasil() {
            if (!currentMarkdown) return;

            const formData = new FormData();
            formData.append('action', 'save_kalender');
            formData.append('content', currentMarkdown);

            const btnSave = document.getElementById('btn-save');
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

            fetch('admin-kalender.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                btnSave.innerHTML = '<i class="fas fa-check mr-1"></i> Tersimpan';
                btnSave.classList.replace('bg-emerald-100', 'bg-gray-100');
                btnSave.classList.replace('text-emerald-700', 'text-gray-500');
                btnSave.disabled = true;
                document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700";
                document.getElementById('badge-status').textContent = "Tersimpan";
            })
            .catch(err => alert("Gagal menyimpan hasil: " + err));
        }

        function simpanSebagai() {
            if (!currentMarkdown) return;
            const blob = new Blob([currentMarkdown], { type: 'text/markdown' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const tgl = new Date().toISOString().slice(0,10);
            a.download = `Kalender_Konten_${tgl}.md`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>