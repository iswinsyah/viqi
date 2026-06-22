<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Ambil data Leads
$leads_data = [];
$sql = "SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 200";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $leads_data[] = $row;
    }
}
$leads_json = json_encode($leads_data);

// Proses Terbitkan Langsung ke Blog
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'publish_blog') {
    $judul = $conn->real_escape_string($_POST['judul'] ?? 'Artikel Baru');
    $meta_title = $conn->real_escape_string($_POST['meta_title'] ?? '');
    $meta_description = $conn->real_escape_string($_POST['meta_description'] ?? '');
    $meta_keywords = $conn->real_escape_string($_POST['meta_keywords'] ?? '');
    $konten = $conn->real_escape_string($_POST['konten'] ?? '');
    
    // Pilih gambar cover: Prioritaskan custom input, lalu AI Pixabay
    $gambar_cover = $_POST['custom_image'] ?? '';
    if (empty($gambar_cover)) {
        $gambar_cover = $_POST['ai_image'] ?? '';
    }
    
    // Fallback jika keduanya kosong, ambil gambar cover acak dari folder Penyimpanan Media jika auto_cover true
    if (empty($gambar_cover) && isset($_POST['auto_cover']) && $_POST['auto_cover'] === 'true') {
        $upload_dir = 'uploads/';
        if (file_exists($upload_dir)) {
            $files = scandir($upload_dir);
            $images = [];
            foreach ($files as $file) {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $images[] = $upload_dir . $file;
                }
            }
            if (!empty($images)) {
                $gambar_cover = $images[array_rand($images)];
            }
        }
    }
    $gambar_cover = $conn->real_escape_string($gambar_cover);

    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    
    // Cek apakah ada perintah untuk auto-publish dari AI Hub
    $status_artikel = (isset($_POST['auto_publish']) && $_POST['auto_publish'] === 'true') ? 'publish' : 'draft';
    // Jika ada perintah penjadwalan dari Mode Borongan
    if (isset($_POST['status']) && $_POST['status'] === 'jadwalkan') {
        $status_artikel = 'jadwalkan';
    }
    $published_at = !empty($_POST['published_at']) ? "'" . $conn->real_escape_string($_POST['published_at']) . "'" : "NULL";

    $sql = "INSERT INTO artikel (judul, slug, konten, status, published_at, meta_title, meta_description, meta_keywords, gambar_cover) 
            VALUES ('$judul', '$slug', '$konten', '$status_artikel', $published_at, '$meta_title', '$meta_description', '$meta_keywords', '$gambar_cover')";
            
    if ($conn->query($sql) === TRUE) {
        echo "Sukses|" . $conn->insert_id; // Kembalikan ID artikel yang baru dibuat
    }
    else echo "Error: " . $conn->error;
    exit;
}

// Proses Simpan Hasil Analisa ke file lokal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_seo') {
    $content = $_POST['content'] ?? '';
    file_put_contents('saved_seo.txt', $content);
    echo "Sukses";
    exit;
}

$saved_seo = file_exists('saved_seo.txt') ? file_get_contents('saved_seo.txt') : '';
$today_seo_task = file_exists('today_seo_task.json') ? json_decode(file_get_contents('today_seo_task.json'), true) : null;
$ai_image_url = $today_seo_task['selected_image'] ?? '';

// --- PROMPT MANAGEMENT ---
$prompt_file = 'prompt_seo.txt';
$default_prompt = "ATURAN WAJIB: KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON MURNI TANPA MARKDOWN (TANPA ```json). FORMAT: {\"judul\":\"{{JUDUL}}\", \"meta_title\":\"...\", \"meta_description\":\"...\", \"meta_keywords\":\"{{KEYWORD}}\", \"konten\":\"(isi html artikel lengkap)\"}. Bahas topik: {{TOPIK}}. PERTIMBANGKAN JUGA insight dari laporan tren terbaru berikut: \n\n{{TREND_MIKRO}}";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_prompt') {
    file_put_contents($prompt_file, $_POST['prompt_content']);
    header("Location: admin-seo.php?prompt_saved=1");
    exit;
}

$prompt_seo = file_exists($prompt_file) ? file_get_contents($prompt_file) : $default_prompt;
$prompt_saved_notif = isset($_GET['prompt_saved']);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEO Article Generator | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #0f766e; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #115e59; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
        .markdown-body blockquote { border-left: 4px solid #14b8a6; padding-left: 1rem; color: #475569; font-style: italic; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING -->
    <?php $active_menu = 'seo'; include 'sidebar-marketing.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-pen-nib text-teal-600 mr-2"></i>Generator Artikel SEO (AI)</h1>
                    <p class="text-sm text-gray-500 mt-1">Buat artikel sesuai kaidah EEAT Google berdasarkan jadwal kalender konten.</p>
                </div>
            </div>

            <?php if($prompt_saved_notif): ?>
            <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm border border-emerald-200">
                <i class="fas fa-check-circle mr-2"></i> Prompt berhasil diperbarui! Perubahan akan diterapkan pada pekerjaan AI berikutnya.
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-xl mr-4 shadow-inner">
                        <i class="fas fa-google"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">SEO Content Writer</h3>
                        <p class="text-sm text-gray-500">Data form tersimpan otomatis. Anda tidak akan kehilangan ketikan meski pindah menu.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Topik / Ide Artikel <span class="text-red-500">*</span></label>
                        <input type="text" id="topik-artikel" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" placeholder="Contoh: Manfaat menghafal Al-Quran">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Judul Artikel <span class="text-red-500">*</span></label>
                        <input type="text" id="judul-artikel" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" placeholder="Contoh: 5 Manfaat Menghafal Al-Quran...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Keyword Target <span class="text-red-500">*</span></label>
                        <input type="text" id="keyword-artikel" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" placeholder="Contoh: tahfidz quran, asrama nyaman">
                    </div>
                </div>

                <!-- OPSI GAMBAR COVER (AI VS CUSTOM EDIT ADMIN) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Gambar Cover (AI Pixabay)</label>
                        <input type="text" id="ai-image-url" readonly class="w-full px-4 py-2 border border-gray-200 bg-gray-50 text-gray-500 rounded-lg cursor-not-allowed" value="<?= htmlspecialchars($ai_image_url) ?>" placeholder="Menunggu hasil riset AI...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Custom URL Gambar (Opsi Edit Admin)</label>
                        <input type="text" id="custom-image-url" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500" placeholder="Ketik/paste link gambar di sini untuk menimpa gambar AI...">
                    </div>
                </div>

                <div class="flex justify-end">
                    <button id="btn-generate" onclick="jalankanGenerator()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group whitespace-nowrap">
                        <i class="fas fa-magic mr-2 group-hover:rotate-12 transition transform"></i> Buat Artikel
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[500px] flex flex-col overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-file-alt mr-2 text-teal-600"></i> Hasil Draft Artikel</h3>
                    <div class="flex items-center space-x-2">
                        <button id="btn-publish" onclick="terbitkanKeBlog()" class="hidden bg-amber-100 text-amber-700 hover:bg-amber-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-amber-200"><i class="fas fa-edit mr-1"></i> Edit di Artikel & Berita</button>
                        <button id="btn-save" onclick="simpanHasil()" class="hidden bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-emerald-200"><i class="fas fa-save mr-1"></i> Simpan</button>
                        <button id="btn-save-as" onclick="simpanSebagai()" class="hidden bg-teal-100 text-teal-700 hover:bg-teal-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-teal-200"><i class="fas fa-file-download mr-1"></i> Save As</button>
                        <span id="badge-status" class="text-xs font-semibold px-2 py-1 rounded-full <?= !empty($saved_seo) ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' ?>"><?= !empty($saved_seo) ? 'Tersimpan' : 'Menunggu Perintah' ?></span>
                    </div>
                </div>
                
                <div id="result-container" class="p-6 flex-1 overflow-y-auto relative">
                    <div id="state-idle" class="<?= !empty($saved_seo) ? 'hidden' : 'flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center' ?>">
                        <i class="fas fa-keyboard text-6xl mb-4 opacity-50 text-teal-300"></i>
                        <p>Lengkapi Topik, Judul, dan Keyword lalu klik "Buat Artikel" untuk mulai.</p>
                    </div>
                    
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-teal-600 py-16 text-center">
                        <i class="fas fa-spinner fa-spin text-5xl mb-4 text-teal-500"></i>
                        <p class="font-bold animate-pulse text-lg">Menulis artikel SEO...</p>
                        <p class="text-sm text-gray-500 mt-2">Menerapkan algoritma E-E-A-T Google. Proses ini memakan waktu 10-15 detik.</p>
                    </div>

                    <div id="state-result" class="<?= !empty($saved_seo) ? 'markdown-body max-w-4xl mx-auto' : 'hidden markdown-body max-w-4xl mx-auto' ?>"></div>
                </div>
            </div>

            <!-- Prompt Editor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <details>
                    <summary class="px-6 py-4 font-bold text-gray-800 cursor-pointer flex justify-between items-center">
                        <span><i class="fas fa-cogs mr-2 text-teal-500"></i> Pengaturan Prompt AI</span>
                        <i class="fas fa-chevron-down transition-transform duration-300"></i>
                    </summary>
                    <div class="p-6 border-t border-gray-100">
                        <form action="admin-seo.php" method="POST">
                            <input type="hidden" name="action" value="save_prompt">
                            <label for="prompt_content" class="block text-sm font-medium text-gray-700 mb-2">Gunakan placeholder: <code>{{JUDUL}}</code>, <code>{{TOPIK}}</code>, <code>{{KEYWORD}}</code>, <code>{{TREND_MIKRO}}</code></label>
                            <textarea id="prompt_content" name="prompt_content" rows="8" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-xs focus:ring-teal-500 focus:border-teal-500"><?= htmlspecialchars($prompt_seo) ?></textarea>
                            <button type="submit" class="mt-4 bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-5 rounded-lg transition shadow-sm"><i class="fas fa-save mr-2"></i> Simpan Prompt</button>
                        </form>
                    </div>
                </details>
            </div>
        </main>
    </div>

    <script>
        const GAS_WEB_APP_URL = "api-gemini.php"; 
        const rawLeadsData = <?= $leads_json ?>;
        const savedSeoMarkdown = <?= json_encode($saved_seo) ?>;
        let currentMarkdown = savedSeoMarkdown;

        // Render data tersimpan saat halaman dimuat
        document.addEventListener("DOMContentLoaded", () => {
            if (savedSeoMarkdown) {
                document.getElementById('state-result').innerHTML = renderPreview(savedSeoMarkdown);
                const btnSave = document.getElementById('btn-save');
                btnSave.classList.remove('hidden');
                document.getElementById('btn-publish').classList.remove('hidden');
                btnSave.innerHTML = '<i class="fas fa-check mr-1"></i> Tersimpan';
                btnSave.classList.replace('bg-emerald-100', 'bg-gray-100');
                btnSave.classList.replace('text-emerald-700', 'text-gray-500');
                btnSave.disabled = true;
                document.getElementById('btn-save-as').classList.remove('hidden');
            }

            // Pulihkan input dari localStorage jika ada
            if (localStorage.getItem('seo_topik')) document.getElementById('topik-artikel').value = localStorage.getItem('seo_topik');
            if (localStorage.getItem('seo_judul')) document.getElementById('judul-artikel').value = localStorage.getItem('seo_judul');
            if (localStorage.getItem('seo_keyword')) document.getElementById('keyword-artikel').value = localStorage.getItem('seo_keyword');
            if (localStorage.getItem('seo_custom_image')) document.getElementById('custom-image-url').value = localStorage.getItem('seo_custom_image');

            // Ambil data hari ini dari PHP today_seo_task jika localStorage kosong
            const todayTask = <?= json_encode($today_seo_task) ?>;
            if (todayTask) {
                if (!document.getElementById('topik-artikel').value) document.getElementById('topik-artikel').value = todayTask.selected_topic || '';
                if (!document.getElementById('judul-artikel').value) document.getElementById('judul-artikel').value = todayTask.selected_title || '';
                if (!document.getElementById('keyword-artikel').value) document.getElementById('keyword-artikel').value = todayTask.selected_keyword || '';
                if (!document.getElementById('ai-image-url').value) document.getElementById('ai-image-url').value = todayTask.selected_image || '';
            }

            // Auto save input ke local storage saat admin mengetik
            ['topik-artikel', 'judul-artikel', 'keyword-artikel', 'custom-image-url'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', simpanInputSementara);
            });
        });

        function simpanInputSementara() {
            localStorage.setItem('seo_topik', document.getElementById('topik-artikel').value);
            localStorage.setItem('seo_judul', document.getElementById('judul-artikel').value);
            localStorage.setItem('seo_keyword', document.getElementById('keyword-artikel').value);
            localStorage.setItem('seo_custom_image', document.getElementById('custom-image-url').value);
        }

        function jalankanGenerator() {
            const topik = document.getElementById('topik-artikel').value.trim();
            const judul = document.getElementById('judul-artikel').value.trim();
            const keyword = document.getElementById('keyword-artikel').value.trim();

            if (!topik || !judul || !keyword) {
                alert("Mohon lengkapi Topik, Judul, dan Keyword terlebih dahulu!");
                return;
            }
            if (rawLeadsData.length === 0) {
                alert("Belum ada data prospek di Pipeline untuk acuan materi.");
                return;
            }

            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-amber-100 text-amber-600 animate-pulse";
            document.getElementById('badge-status').textContent = "Mengetik Artikel...";
            document.getElementById('btn-generate').disabled = true;
            document.getElementById('btn-generate').classList.add('opacity-50', 'cursor-not-allowed');
            
            // Reset tombol save
            const btnSave = document.getElementById('btn-save');
            btnSave.classList.add('hidden');
            document.getElementById('btn-publish').classList.add('hidden');
            btnSave.innerHTML = '<i class="fas fa-save mr-1"></i> Simpan';
            document.getElementById('btn-save-as').classList.add('hidden');
            if(btnSave.classList.contains('bg-gray-100')) {
                btnSave.classList.replace('bg-gray-100', 'bg-emerald-100');
                btnSave.classList.replace('text-gray-500', 'text-emerald-700');
            }
            btnSave.disabled = false;

            const payloadLeads = JSON.parse(JSON.stringify(rawLeadsData));
            const promptText = document.getElementById('prompt_content').value;
            const replacements = {'{{JUDUL}}': judul, '{{TOPIK}}': topik, '{{KEYWORD}}': keyword, '{{TREND_MIKRO}}': 'Tidak ada tren mikro untuk mode manual.'};
            const finalPrompt = Object.keys(replacements).reduce((acc, key) => acc.replace(new RegExp(key, 'g'), replacements[key]), promptText);

            payloadLeads.unshift({
                jenis_lead: "SYSTEM_COMMAND",
                sumber_info: finalPrompt,
                status: "URGENT"
            });

            // Tembak data ke GAS dengan TYPE = 'seo'
            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: payloadLeads, type: 'seo', topik: topik, judul: judul, keyword: keyword })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    currentMarkdown = data.result;
                    document.getElementById('state-result').innerHTML = renderPreview(currentMarkdown);
                    document.getElementById('btn-save').classList.remove('hidden');
                    document.getElementById('btn-save-as').classList.remove('hidden');
                    document.getElementById('btn-publish').classList.remove('hidden');
                    document.getElementById('badge-status').className = "text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-700";
                    document.getElementById('badge-status').textContent = "Selesai";
                } else {
                    throw new Error(data.message);
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

        function renderPreview(rawText) {
            try {
                let cleanJson = rawText.replace(/```json/gi, '').replace(/```/g, '').trim();
                let obj = JSON.parse(cleanJson);
                return `
                    <div class="bg-teal-50 p-5 rounded-xl mb-8 border border-teal-100 text-sm shadow-sm text-left">
                        <h4 class="font-bold text-teal-800 mb-3 border-b border-teal-200 pb-2"><i class="fas fa-search mr-2"></i>Data Meta SEO (Otomatis)</h4>
                        <div class="space-y-2 text-gray-700">
                            <div><span class="font-semibold text-teal-900 inline-block w-32">Judul Artikel</span>: ${obj.judul}</div>
                            <div><span class="font-semibold text-teal-900 inline-block w-32">Meta Title</span>: ${obj.meta_title}</div>
                            <div><span class="font-semibold text-teal-900 inline-block w-32">Meta Desc</span>: ${obj.meta_description}</div>
                            <div><span class="font-semibold text-teal-900 inline-block w-32">Focus Keyword</span>: ${obj.meta_keywords}</div>
                        </div>
                    </div>
                    <div class="text-left text-gray-800">${obj.konten}</div>
                `;
            } catch (e) {
                return marked.parse(rawText);
            }
        }

        function terbitkanKeBlog() {
            if (!currentMarkdown) return;
            
            let judul = "Artikel SEO Baru";
            let meta_title = "";
            let meta_desc = "";
            let meta_key = "";
            let konten = currentMarkdown;
            
            try {
                let cleanJson = currentMarkdown.replace(/```json/gi, '').replace(/```/g, '').trim();
                let obj = JSON.parse(cleanJson);
                judul = obj.judul || judul;
                meta_title = obj.meta_title || "";
                meta_desc = obj.meta_description || "";
                meta_key = obj.meta_keywords || "";
                konten = obj.konten || currentMarkdown;
            } catch (e) {
                // Fallback jika AI gagal mengirim format JSON murni
                const regex = /<!--\s*JUDUL:\s*(.*?)\s*META_TITLE:\s*(.*?)\s*META_DESC:\s*(.*?)\s*META_KEY:\s*(.*?)\s*-->/is;
                const match = currentMarkdown.match(regex);
                if (match) {
                    judul = match[1].trim();
                    meta_title = match[2].trim();
                    meta_desc = match[3].trim();
                    meta_key = match[4].trim();
                    konten = currentMarkdown.replace(match[0], '').trim();
                }
            }
            
            const aiImage = document.getElementById('ai-image-url').value.trim();
            const customImage = document.getElementById('custom-image-url').value.trim();

            const formData = new FormData();
            formData.append('action', 'publish_blog');
            formData.append('judul', judul);
            formData.append('meta_title', meta_title);
            formData.append('meta_description', meta_desc);
            formData.append('meta_keywords', meta_key);
            formData.append('konten', konten);
            formData.append('ai_image', aiImage);
            formData.append('custom_image', customImage);

            const btnPub = document.getElementById('btn-publish');
            const oldText = btnPub.innerHTML;
            btnPub.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memindahkan ke Editor...';
            btnPub.disabled = true;

            fetch('admin-seo.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(text => {
                if (text.startsWith("Sukses|")) {
                    const newId = text.split("|")[1]; // Ambil ID Artikel
                    alert("Draft artikel berhasil disiapkan! Anda akan dialihkan ke Editor untuk melengkapi gambar, menambahkan link, dan mempublikasikannya.");
                    window.location.href = "admin-artikel.php?edit_id=" + newId; // Arahkan ke mode Edit
                } else {
                    alert("Gagal memindahkan ke editor: " + text);
                    btnPub.innerHTML = oldText;
                    btnPub.disabled = false;
                }
            })
            .catch(err => { alert("Kesalahan jaringan: " + err); btnPub.innerHTML = oldText; btnPub.disabled = false; });
        }

        function simpanHasil() {
            if (!currentMarkdown) return;

            const formData = new FormData();
            formData.append('action', 'save_seo');
            formData.append('content', currentMarkdown);

            const btnSave = document.getElementById('btn-save');
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';

            fetch('admin-seo.php', {
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
            let isJson = false;
            try {
                JSON.parse(currentMarkdown.replace(/```json/gi, '').replace(/```/g, '').trim());
                isJson = true;
            } catch(e) {}
            
            const blob = new Blob([currentMarkdown], { type: isJson ? 'application/json' : 'text/markdown' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const tgl = new Date().toISOString().slice(0,10);
            a.download = `Artikel_SEO_${tgl}.${isJson ? 'json' : 'md'}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>