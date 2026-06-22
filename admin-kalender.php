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
    
    // Simpan juga data target SEO hari ini jika dikirim
    if (isset($_POST['selected_data'])) {
        $selected_data = json_decode($_POST['selected_data'], true);
        if ($selected_data) {
            file_put_contents('today_seo_task.json', json_encode($selected_data, JSON_PRETTY_PRINT));
        }
    }
    echo "Sukses";
    exit;
}

$saved_kalender = file_exists('saved_kalender.txt') ? file_get_contents('saved_kalender.txt') : '';
$today_seo_task = file_exists('today_seo_task.json') ? json_decode(file_get_contents('today_seo_task.json'), true) : null;
$trend_scout_report = file_exists('saved_trends_macro.txt') ? file_get_contents('saved_trends_macro.txt') : 'Tidak ada laporan tren.';

// --- PROMPT MANAGEMENT ---
$prompt_file = 'prompt_hook_explorer.txt';
$default_prompt = "Anda adalah AI Agent Riset Hook & Keyword SEO. Tugas Anda adalah meriset dan memilih judul hook yang bisa viral serta keyword yang tepat sesuai algoritma Google Search terbaru, berdasarkan hasil riset Trend Scout berikut:\n\n{{TREND_SCOUT}}\n\nKetentuan:\n1. Target audiens: Orang tua dengan anak remaja usia 10-15 tahun, dalam konteks Islamic Parenting / Pendidikan Remaja Muslim.\n2. Riset 5 opsi judul hook viral yang memicu rasa penasaran/emosi (menggunakan formula hook seperti pengakuan, kontradiktif, pertanyaan retoris, dsb).\n3. Tentukan keyword utama & turunan yang memiliki potensi trafik tinggi dan relevan sesuai algoritma Google Search terbaru.\n4. Pilih 1 kombinasi terbaik yang paling berpotensi viral dan memiliki search intent yang kuat untuk ditulis hari ini.\n5. Berikan output dalam format JSON murni tanpa markdown (tanpa ```json). Format JSON harus tepat seperti ini:\n{\n  \"selected_topic\": \"Topik singkat dari judul terpilih\",\n  \"selected_title\": \"Judul Hook Terpilih yang Bisa Viral\",\n  \"selected_keyword\": \"keyword utama, keyword turunan 1, keyword turunan 2\",\n  \"report\": \"# Laporan Riset Hook & Keyword\\n\\n(Sajikan laporan lengkap riset Anda dalam format markdown di sini. Laporkan 5 opsi judul hook beserta keyword masing-masing, analisis kecocokan algoritma Google Search, serta alasan kuat pemilihan 1 judul terbaik untuk hari ini.)\"\n}";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_prompt') {
    file_put_contents($prompt_file, $_POST['prompt_content']);
    header("Location: admin-kalender.php?prompt_saved=1");
    exit;
}

$prompt_kalender = file_exists($prompt_file) ? file_get_contents($prompt_file) : $default_prompt;
$prompt_saved_notif = isset($_GET['prompt_saved']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Hook & Keyword Explorer | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* Styling untuk hasil render Markdown agar laporan terlihat rapi dan premium */
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #4f46e5; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #4338ca; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
        /* Styling khusus Tabel Laporan */
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.5rem; font-size: 0.875rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.75rem; text-align: left; vertical-align: top; }
        .markdown-body th { background-color: #f8fafc; font-weight: bold; color: #0f172a; white-space: nowrap; }
        .markdown-body tr:nth-child(even) { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING (Menunjukkan item aktif 'kalender') -->
    <?php $active_menu = 'kalender'; include 'sidebar-marketing.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center">
                        <i class="fas fa-search-dollar text-indigo-600 mr-2"></i>
                        AI Hook & Keyword Explorer
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Riset opsi judul hook viral dan kata kunci penargetan berdasarkan laporan harian Trend Scout.</p>
                </div>
            </div>

            <?php if($prompt_saved_notif): ?>
            <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm border border-emerald-200">
                <i class="fas fa-check-circle mr-2"></i> Prompt riset berhasil diperbarui!
            </div>
            <?php endif; ?>

            <!-- KARTU TARGET SEO HARI INI (PREMIUM GLASSMORPHISM STYLE) -->
            <?php if($today_seo_task): ?>
            <div class="relative overflow-hidden bg-gradient-to-r from-indigo-700 via-purple-700 to-pink-700 text-white p-6 rounded-xl shadow-lg border border-indigo-500/20 mb-6 group transition-all duration-300 hover:shadow-indigo-500/10">
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-2xl group-hover:scale-125 transition-all duration-500"></div>
                <div class="flex items-start justify-between relative z-10">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-white/20 backdrop-blur-md text-amber-300 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider border border-white/10 flex items-center">
                                <i class="fas fa-bullseye mr-1.5 animate-pulse"></i> Target SEO Hari Ini
                            </span>
                        </div>
                        <h2 class="text-xl md:text-2xl font-black mt-3 mb-1 tracking-tight"><?= htmlspecialchars($today_seo_task['selected_title']) ?></h2>
                        <p class="text-sm text-indigo-100 mb-4 font-medium">Topik: <span class="text-white font-bold"><?= htmlspecialchars($today_seo_task['selected_topic']) ?></span></p>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                            $keywords = explode(',', $today_seo_task['selected_keyword']);
                            foreach($keywords as $kw): 
                                $kw = trim($kw);
                                if (!empty($kw)):
                            ?>
                            <span class="bg-black/25 hover:bg-black/35 transition text-indigo-200 text-xs font-semibold px-3 py-1.5 rounded-lg border border-white/5 flex items-center">
                                <i class="fas fa-key mr-1.5 text-amber-400"></i><?= htmlspecialchars($kw) ?>
                            </span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-xl text-amber-300 border border-white/20 shadow-inner">
                        <i class="fas fa-rocket animate-pulse"></i>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center text-amber-800">
                <i class="fas fa-exclamation-triangle text-xl mr-3 text-amber-600"></i>
                <div>
                    <h4 class="font-bold">Belum Ada Target SEO Terpilih</h4>
                    <p class="text-xs mt-0.5">Jalankan generator riset di bawah untuk memilikinya secara otomatis atau simpan hasil riset manual.</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-center p-6 gap-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl mr-4 shadow-inner">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">Gemini Explorer Engine</h3>
                        <p class="text-sm text-gray-500">Menganalisa tren parenting remaja Muslim untuk menentukan hook artikel terbaik.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <span class="text-sm text-gray-500 font-medium hidden md:block">Tanggal Riset:</span>
                    <input type="date" id="tgl-kalender" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 flex-1 md:w-auto">
                    <button id="btn-generate" onclick="jalankanGenerator()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group whitespace-nowrap">
                        <i class="fas fa-magic mr-2 group-hover:rotate-12 transition transform"></i> Jalankan Riset
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[500px] flex flex-col overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-800"><i class="fas fa-file-alt mr-2 text-indigo-600"></i> Laporan Riset Terbaru</h3>
                        <p class="text-xs text-indigo-500 mt-1"><i class="fas fa-info-circle mr-1"></i> Berisi 5 opsi viral hooks, analisis pencarian Google Search, & rekomendasi terpilih.</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="btn-save" onclick="simpanHasil()" class="hidden bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-emerald-200"><i class="fas fa-save mr-1"></i> Simpan Hasil & Terapkan</button>
                        <button id="btn-save-as" onclick="simpanSebagai()" class="hidden bg-sky-100 text-sky-700 hover:bg-sky-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-sky-200"><i class="fas fa-file-download mr-1"></i> Ekspor MD</button>
                        <span id="badge-status" class="text-xs font-semibold px-2 py-1 rounded-full <?= !empty($saved_kalender) ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' ?>"><?= !empty($saved_kalender) ? 'Tersimpan' : 'Menunggu Perintah' ?></span>
                    </div>
                </div>
                
                <div id="result-container" class="p-6 flex-1 overflow-x-auto relative">
                    <!-- Layar Awal -->
                    <div id="state-idle" class="<?= !empty($saved_kalender) ? 'hidden' : 'flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center' ?>">
                        <i class="fas fa-search text-6xl mb-4 opacity-50 text-indigo-300 animate-pulse"></i>
                        <p>Klik tombol di atas untuk menjalankan analisis riset viral hook dan kata kunci.</p>
                    </div>
                    
                    <!-- Layar Loading -->
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-indigo-600 py-16 text-center">
                        <i class="fas fa-spinner fa-spin text-5xl mb-4 text-indigo-500"></i>
                        <p class="font-bold animate-pulse text-lg">Menganalisa Algoritma Search & Sosmed...</p>
                        <p class="text-sm text-gray-500 mt-2">Merumuskan 5 formula judul hook viral dan relevansi keyword...</p>
                    </div>

                    <!-- Layar Hasil -->
                    <div id="state-result" class="<?= !empty($saved_kalender) ? 'markdown-body min-w-[800px]' : 'hidden markdown-body min-w-[800px]' ?>">
                        <!-- Hasil render Markdown dari Gemini akan masuk ke sini -->
                    </div>
                </div>
            </div>

            <!-- Prompt Editor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <details>
                    <summary class="px-6 py-4 font-bold text-gray-800 cursor-pointer flex justify-between items-center">
                        <span><i class="fas fa-cogs mr-2 text-indigo-500"></i> Pengaturan Prompt AI Explorer</span>
                        <i class="fas fa-chevron-down transition-transform duration-300"></i>
                    </summary>
                    <div class="p-6 border-t border-gray-100">
                        <form action="admin-kalender.php" method="POST">
                            <input type="hidden" name="action" value="save_prompt">
                            <label for="prompt_content" class="block text-sm font-medium text-gray-700 mb-2">Gunakan placeholder <code>{{TREND_SCOUT}}</code> untuk menyisipkan laporan tren makro terbaru ke dalam prompt.</label>
                            <textarea id="prompt_content" name="prompt_content" rows="12" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-xs focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($prompt_kalender) ?></textarea>
                            <button type="submit" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-sm"><i class="fas fa-save mr-2"></i> Simpan Prompt</button>
                        </form>
                    </div>
                </details>
            </div>
        </main>
    </div>

    <script>
        const GAS_WEB_APP_URL = "api-gemini.php"; 
        const rawLeadsData = <?= $leads_json ?>;
        const trendScoutReport = <?= json_encode($trend_scout_report) ?>;
        const savedKalenderMarkdown = <?= json_encode($saved_kalender) ?>;
        
        let currentMarkdown = savedKalenderMarkdown;
        let currentSelected = null;

        // Render data tersimpan saat halaman dimuat
        document.addEventListener("DOMContentLoaded", () => {
            if (savedKalenderMarkdown) {
                document.getElementById('state-result').innerHTML = marked.parse(savedKalenderMarkdown);
                const btnSave = document.getElementById('btn-save');
                btnSave.classList.remove('hidden');
                btnSave.innerHTML = '<i class="fas fa-check mr-1"></i> Tersimpan & Diterapkan';
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
                alert("Silakan pilih tanggal riset terlebih dahulu!");
                return;
            }

            // Atur UI State ke Loading
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-600 animate-pulse";
            document.getElementById('badge-status').textContent = "Meriset Hook...";
            document.getElementById('btn-generate').disabled = true;
            document.getElementById('btn-generate').classList.add('opacity-50', 'cursor-not-allowed');
            
            // Reset tombol save
            const btnSave = document.getElementById('btn-save');
            btnSave.classList.add('hidden');
            btnSave.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan Hasil & Terapkan';
            document.getElementById('btn-save-as').classList.add('hidden');
            if(btnSave.classList.contains('bg-gray-100')) {
                btnSave.classList.replace('bg-gray-100', 'bg-emerald-100');
                btnSave.classList.replace('text-gray-500', 'text-emerald-700');
            }
            btnSave.disabled = false;

            const payloadLeads = JSON.parse(JSON.stringify(rawLeadsData));
            const promptText = document.getElementById('prompt_content').value;
            const finalPrompt = promptText.replace('{{TREND_SCOUT}}', trendScoutReport);

            payloadLeads.unshift({
                jenis_lead: "SYSTEM_COMMAND",
                sumber_info: finalPrompt,
                status: "URGENT"
            });

            // Tembak data ke GAS dengan type = 'hook_explorer'
            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: payloadLeads, type: 'hook_explorer', date: dateInput })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    let cleanResult = data.result.trim();
                    if (cleanResult.startsWith("```json")) {
                        cleanResult = cleanResult.replace(/^```json/, '').replace(/```$/, '').trim();
                    } else if (cleanResult.startsWith("```")) {
                        cleanResult = cleanResult.replace(/^```/, '').replace(/```$/, '').trim();
                    }
                    
                    try {
                        const parsed = JSON.parse(cleanResult);
                        if (parsed.report) {
                            currentMarkdown = parsed.report;
                            currentSelected = {
                                selected_topic: parsed.selected_topic,
                                selected_title: parsed.selected_title,
                                selected_keyword: parsed.selected_keyword
                            };
                        } else {
                            currentMarkdown = data.result;
                            currentSelected = null;
                        }
                    } catch(e) {
                        currentMarkdown = data.result;
                        currentSelected = null;
                    }

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
            if (currentSelected) {
                formData.append('selected_data', JSON.stringify(currentSelected));
            }

            const btnSave = document.getElementById('btn-save');
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

            fetch('admin-kalender.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(text => {
                btnSave.innerHTML = '<i class="fas fa-check mr-1"></i> Tersimpan & Diterapkan';
                btnSave.classList.replace('bg-emerald-100', 'bg-gray-100');
                btnSave.classList.replace('text-emerald-700', 'text-gray-500');
                btnSave.disabled = true;
                document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-blue-100 text-blue-700";
                document.getElementById('badge-status').textContent = "Tersimpan";
                
                // Muat ulang halaman untuk memunculkan kartu penargetan SEO di atas
                setTimeout(() => { window.location.reload(); }, 800);
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
            a.download = `Laporan_Riset_Hook_${tgl}.md`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>