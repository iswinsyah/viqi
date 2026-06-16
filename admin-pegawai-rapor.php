<?php
require_once 'auth.php';
require_once 'koneksi.php';
$active_menu = 'ai_rapor';

// Ambil data anak dari Bank Nilai untuk dropdown autocomplete
$santri = [];
$res = $conn->query("SELECT id, nama_santri, mata_pelajaran, nilai FROM bank_nilai ORDER BY id DESC LIMIT 50");
if($res) while($r = $res->fetch_assoc()) $santri[] = $r;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Narasi Rapor | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-comment-dots text-indigo-500 mr-2"></i>AI Penulis Narasi Rapor</h1><p class="text-sm text-gray-500">Ubah nilai angka menjadi kalimat deskripsi yang memotivasi dan menyentuh hati orang tua.</p></div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- FORM KIRI -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 border-b pb-2 mb-4">Input Data Capaian</h3>
                    
                    <!-- Dropdown data cepat -->
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-emerald-600 mb-1"><i class="fas fa-bolt mr-1"></i> Isi Cepat dari Bank Nilai (Opsional)</label>
                        <select onchange="isiOtomatis(this)" class="w-full px-3 py-1.5 border border-emerald-200 bg-emerald-50 rounded text-sm text-emerald-800 focus:outline-none">
                            <option value="">-- Pilih Data Terbaru --</option>
                            <?php foreach($santri as $s): ?>
                                <option value="<?= htmlspecialchars(json_encode($s)) ?>"><?= htmlspecialchars($s['nama_santri']) ?> - <?= htmlspecialchars($s['mata_pelajaran']) ?> (Nilai: <?= $s['nilai'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label><input type="text" id="rapor-nama" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500"></div>
                            <div><label class="block text-sm font-medium text-gray-700 mb-1">Nilai Angka</label><input type="number" id="rapor-nilai" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500 text-lg font-bold"></div>
                        </div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label><input type="text" id="rapor-mapel" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500"></div>
                        <div><label class="block text-sm font-medium text-gray-700 mb-1">Catatan Kasar Guru (Kelebihan/Kekurangan Anak)</label><textarea id="rapor-catatan" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-indigo-500" placeholder="Contoh: Anaknya pintar tapi sering ngobrol saat jam pelajaran..."></textarea></div>
                    </div>
                    <button id="btn-generate" onclick="generateRapor()" class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg shadow-md transition flex items-center justify-center">
                        <i class="fas fa-magic mr-2"></i> Hasilkan Narasi Rapor
                    </button>
                </div>

                <!-- HASIL KANAN -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col h-full min-h-[400px]">
                    <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100"><h3 class="font-bold text-indigo-800"><i class="fas fa-quote-left mr-2"></i>Hasil Narasi (Siap Copy)</h3></div>
                    <div id="result-container" class="p-6 flex-1 overflow-y-auto relative">
                        <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center"><i class="fas fa-pen-alt text-6xl mb-4 opacity-30"></i><p>Teks narasi yang indah dan profesional akan muncul di sini.</p></div>
                        <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-indigo-600 py-16 text-center"><i class="fas fa-spinner fa-spin text-5xl mb-4"></i><p class="font-bold">Merangkai kata-kata motivasi...</p></div>
                        <div id="state-result" class="hidden markdown-body text-gray-700 text-sm leading-relaxed space-y-4"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
        
        function isiOtomatis(sel) {
            if(sel.value) {
                const data = JSON.parse(sel.value);
                document.getElementById('rapor-nama').value = data.nama_santri;
                document.getElementById('rapor-mapel').value = data.mata_pelajaran;
                document.getElementById('rapor-nilai').value = data.nilai;
            }
        }

        const GAS_URL = "api-gemini.php";

        function generateRapor() {
            const nama = document.getElementById('rapor-nama').value;
            const mapel = document.getElementById('rapor-mapel').value;
            const nilai = document.getElementById('rapor-nilai').value;
            const catatan = document.getElementById('rapor-catatan').value;

            if(!nama || !mapel || !nilai) { alert("Lengkapi data Nama, Mapel, dan Nilai!"); return; }

            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('btn-generate').disabled = true;

            const prompt = `PENTING: Bertindaklah sebagai Wali Kelas/Ustadz yang bijaksana di Pesantren. Buat 3 variasi narasi catatan rapor untuk santri bernama ${nama} pada Mata Pelajaran ${mapel}. Ia mendapatkan nilai angka: ${nilai}. Catatan asli dari gurunya: "${catatan}". Ubah catatan kasar tersebut menjadi kalimat paragraf yang memotivasi, sangat profesional, menyentuh hati orang tuanya, serta memberikan masukan yang membangun tanpa menyinggung. Beri label Variasi 1, Variasi 2, dan Variasi 3.`;

            fetch(GAS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: [{jenis_lead:"SYSTEM_COMMAND", sumber_info:prompt, status:"URGENT"}], type: 'rapor' })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === "success") { document.getElementById('state-result').innerHTML = marked.parse(data.result); document.getElementById('state-loading').classList.add('hidden'); document.getElementById('state-result').classList.remove('hidden'); } else throw new Error(data.message);
            })
            .catch(err => { alert("Error AI: " + err.message); document.getElementById('state-loading').classList.add('hidden'); document.getElementById('state-idle').classList.remove('hidden'); })
            .finally(() => document.getElementById('btn-generate').disabled = false);
        }
    </script>
</body>
</html>