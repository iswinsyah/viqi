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
    
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $judul)));
    
    // Simpan sebagai DRAFT agar tidak langsung tampil di web
    $sql = "INSERT INTO artikel (judul, slug, konten, status, meta_title, meta_description, meta_keywords) 
            VALUES ('$judul', '$slug', '$konten', 'draft', '$meta_title', '$meta_description', '$meta_keywords')";
            
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

    <!-- INCLUDE SIDEBAR -->
    <?php $active_menu = 'seo'; include 'sidebar.php'; ?>

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

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 flex flex-col md:flex-row justify-between items-center p-6 gap-4">
                <div class="flex items-center">
                    <div class="w-12 h-12 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center text-xl mr-4">
                        <i class="fas fa-google"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900">SEO Content Writer</h3>
                        <p class="text-sm text-gray-500">Pilih tanggal untuk menyesuaikan dengan tema dari Kalender.</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <input type="date" id="tgl-seo" class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-teal-500 focus:border-teal-500 flex-1 md:w-auto">
                    <button id="btn-generate" onclick="jalankanGenerator()" class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group whitespace-nowrap">
                        <i class="fas fa-magic mr-2 group-hover:rotate-12 transition transform"></i> Buat Artikel
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[500px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-file-alt mr-2"></i> Hasil Draft Artikel</h3>
                    <div class="flex items-center space-x-2">
                        <button id="btn-publish" onclick="terbitkanKeBlog()" class="hidden bg-amber-100 text-amber-700 hover:bg-amber-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-amber-200"><i class="fas fa-edit mr-1"></i> Edit di Artikel & Berita</button>
                        <button id="btn-save" onclick="simpanHasil()" class="hidden bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-emerald-200"><i class="fas fa-save mr-1"></i> Simpan</button>
                        <button id="btn-save-as" onclick="simpanSebagai()" class="hidden bg-teal-100 text-teal-700 hover:bg-teal-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-teal-200"><i class="fas fa-file-download mr-1"></i> Save As</button>
                        <span id="badge-status" class="text-xs font-semibold px-2 py-1 rounded-full <?= !empty($saved_seo) ? 'bg-blue-100 text-blue-700' : 'bg-gray-200 text-gray-600' ?>"><?= !empty($saved_seo) ? 'Tersimpan' : 'Menunggu Perintah' ?></span>
                    </div>
                </div>
                
                <div id="result-container" class="p-6 flex-1 overflow-y-auto relative">
                    <div id="state-idle" class="<?= !empty($saved_seo) ? 'hidden' : 'flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center' ?>">
                        <i class="fas fa-keyboard text-6xl mb-4 opacity-50"></i>
                        <p>Pilih tanggal dan klik "Buat Artikel" untuk mulai.</p>
                    </div>
                    
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-teal-600 py-16 text-center">
                        <i class="fas fa-spinner fa-spin text-5xl mb-4"></i>
                        <p class="font-bold animate-pulse">Menulis artikel SEO...</p>
                        <p class="text-sm text-gray-500 mt-2">Menerapkan algoritma E-E-A-T Google. Proses ini memakan waktu 10-15 detik.</p>
                    </div>

                    <div id="state-result" class="<?= !empty($saved_seo) ? 'markdown-body max-w-4xl mx-auto' : 'hidden markdown-body max-w-4xl mx-auto' ?>"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const GAS_WEB_APP_URL = "https://script.google.com/macros/s/AKfycbyU1T58tS5e1GqxNz_n8lHuRrE5lBJZ6uLEqXCDcXqYC6wsMkRF48FLdIcqpt93ffg/exec"; 
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
        });

        // Otomatis set tanggal hari ini di input
        document.getElementById('tgl-seo').valueAsDate = new Date();

        function jalankanGenerator() {
            const dateInput = document.getElementById('tgl-seo').value;
            if (!dateInput) {
                alert("Silakan pilih tanggal rilis artikel terlebih dahulu!");
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

            // INJEKSI PROMPT KETAT KE DALAM PAYLOAD (Mencegah basa-basi AI dan simbol Markdown)
            const payloadLeads = JSON.parse(JSON.stringify(rawLeadsData));
            payloadLeads.unshift({
                jenis_lead: "SYSTEM_COMMAND",
                sumber_info: `ATURAN WAJIB & SANGAT KETAT:
1. KEMBALIKAN OUTPUT HANYA DALAM FORMAT JSON YANG VALID.
2. DILARANG ADA TEKS LAIN DI LUAR JSON (jangan ada sapaan AI).
3. Gunakan struktur JSON persis seperti berikut:
{
  "judul": "Judul Artikel Menarik",
  "meta_title": "SEO Title yang Menarik",
  "meta_description": "Meta description maksimal 150 karakter",
  "meta_keywords": "keyword1, keyword2, keyword3",
  "konten": "Isi artikel lengkap dengan tag HTML dasar seperti <p>, <h2>, <ul>, <li>, <strong>. JANGAN gunakan format markdown (* atau #) di dalam konten."
}`,
                status: "URGENT"
            });

            // Tembak data ke GAS dengan TYPE = 'seo'
            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: payloadLeads, type: 'seo', date: dateInput })
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
            
            const formData = new FormData();
            formData.append('action', 'publish_blog');
            formData.append('judul', judul);
            formData.append('meta_title', meta_title);
            formData.append('meta_description', meta_desc);
            formData.append('meta_keywords', meta_key);
            formData.append('konten', konten);

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
            const tgl = document.getElementById('tgl-seo').value || new Date().toISOString().slice(0,10);
            a.download = `Artikel_SEO_${tgl}.${isJson ? 'json' : 'md'}`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>