<?php
require_once 'auth.php';
require_once 'koneksi.php';
$active_menu = 'ai_rpp';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Generator RPP | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #0891b2; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #0e7490; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.5rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.75rem; text-align: left; }
        .markdown-body th { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-magic text-cyan-600 mr-2"></i>AI Generator RPP</h1><p class="text-sm text-gray-500">Asisten pembuat Rencana Pelaksanaan Pembelajaran dalam hitungan detik.</p></div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                        <input type="text" id="rpp-mapel" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Sejarah Kebudayaan Islam">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas / Jenjang</label>
                        <input type="text" id="rpp-kelas" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Kelas 7 SMP">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Topik Utama</label>
                        <input type="text" id="rpp-topik" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Peristiwa Hijrah Nabi">
                    </div>
                </div>
                <div class="flex justify-end">
                    <button id="btn-generate" onclick="generateRPP()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group">
                        <i class="fas fa-brain mr-2 group-hover:animate-pulse"></i> Susun RPP dengan AI
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 min-h-[400px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h3 class="font-bold text-gray-800"><i class="fas fa-file-alt mr-2"></i> Hasil Draft RPP</h3></div>
                <div id="result-container" class="p-6 flex-1 overflow-y-auto relative">
                    <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center"><i class="fas fa-file-signature text-6xl mb-4 opacity-30"></i><p>Isi form di atas lalu klik tombol untuk menyusun RPP otomatis.</p></div>
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-cyan-600 py-16 text-center"><i class="fas fa-spinner fa-spin text-5xl mb-4"></i><p class="font-bold">Menganalisa silabus dan merancang kegiatan kelas...</p></div>
                    <div id="state-result" class="hidden markdown-body max-w-4xl mx-auto"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
        
        const GAS_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec";

        function generateRPP() {
            const mapel = document.getElementById('rpp-mapel').value;
            const kelas = document.getElementById('rpp-kelas').value;
            const topik = document.getElementById('rpp-topik').value;

            if(!mapel || !kelas || !topik) { alert("Lengkapi semua isian form!"); return; }

            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('btn-generate').disabled = true;

            const prompt = `Buat Rencana Pelaksanaan Pembelajaran (RPP) 1 lembar yang menarik, modern, dan tidak membosankan untuk Mata Pelajaran: ${mapel}, Kelas: ${kelas}, Topik: ${topik}. Buat dalam format Markdown. Struktur wajib: 1. Tujuan Pembelajaran, 2. Kegiatan Pendahuluan (Ice breaking), 3. Kegiatan Inti (Materi & Metode Interaktif/Games), 4. Kegiatan Penutup & Evaluasi.`;

            fetch(GAS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: [{jenis_lead:"SYSTEM_COMMAND", sumber_info:prompt, status:"URGENT"}], type: 'rpp' })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === "success") {
                    document.getElementById('state-result').innerHTML = marked.parse(data.result);
                    document.getElementById('state-loading').classList.add('hidden');
                    document.getElementById('state-result').classList.remove('hidden');
                } else throw new Error(data.message);
            })
            .catch(err => { alert("Error AI: " + err.message); document.getElementById('state-loading').classList.add('hidden'); document.getElementById('state-idle').classList.remove('hidden'); })
            .finally(() => document.getElementById('btn-generate').disabled = false);
        }
    </script>
</body>
</html>