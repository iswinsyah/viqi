<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'trend_scout';

// --- PROMPT MANAGEMENT ---
$prompt_file_macro = 'prompt_trend_macro.txt';
$default_prompt_macro = "Anda adalah seorang SEO & Market Trend Analyst. Tugas Anda adalah memberikan analisis trend konten parenting Islam untuk anak remaja usia 10 sampai dengan 15 tahun dari satu hari terakhir disemua plaform sosmed dan google search. Analisis harus meliputi: 1. Tema yang paling trending, 2. Angel/sudut pandang konten, 4. Hashtag yang digunakan, 5. Keyword Google Search yang sedang tren, serta hal penting lainnya yang relevan. Sajikan dalam format Markdown.";
$prompt_file_micro = 'prompt_trend_micro.txt';
$default_prompt_micro = "Anda adalah seorang SEO & Content Strategist. Tema besar hari ini adalah: \n\n{{THEME}}\n\n Tugas Anda adalah mencari 1 SUDUT PANDANG (angle) atau topik spesifik yang sedang hangat dibicarakan dalam 24 jam terakhir terkait tema tersebut. Berikan 1 rekomendasi judul artikel yang viral dan 3 keyword turunan yang relevan. Sajikan dalam format Markdown.";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_prompt') {
    if (isset($_POST['prompt_macro'])) {
        file_put_contents($prompt_file_macro, $_POST['prompt_macro']);
    }
    if (isset($_POST['prompt_micro'])) {
        file_put_contents($prompt_file_micro, $_POST['prompt_micro']);
    }
    header("Location: admin-trend-scout.php?prompt_saved=1");
    exit;
}

$prompt_macro = file_exists($prompt_file_macro) ? file_get_contents($prompt_file_macro) : $default_prompt_macro;
$prompt_micro = file_exists($prompt_file_micro) ? file_get_contents($prompt_file_micro) : $default_prompt_micro;
$prompt_saved_notif = isset($_GET['prompt_saved']);

$saved_macro = file_exists('saved_trends_macro.txt') ? file_get_contents('saved_trends_macro.txt') : '';
$saved_micro = file_exists('saved_trends_micro.txt') ? file_get_contents('saved_trends_micro.txt') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Trend Scout | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        /* Common markdown styles */
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body li { margin-bottom: 0.25rem; }
        .markdown-body strong { color: #0f172a; }
        /* Macro styles */
        .macro-body h2 { color: #4f46e5; }
        .macro-body h3 { color: #4338ca; }
        /* Micro styles */
        .micro-body h2 { color: #059669; }
        .micro-body h3 { color: #047857; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-marketing.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-line text-indigo-600 mr-2"></i>Trend & Keyword Scout</h1>
                <p class="text-gray-500 mt-1">Laporan otomatis dari AI tentang tren pasar dan kata kunci potensial.</p>
            </div>

            <?php if($prompt_saved_notif): ?>
            <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm border border-emerald-200"><i class="fas fa-check-circle mr-2"></i> Prompt berhasil diperbarui! Perubahan akan diterapkan pada pekerjaan AI berikutnya.</div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Laporan Makro (Bulanan) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[400px] flex flex-col overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-day mr-2 text-indigo-500"></i>Laporan Tren Harian (Trend Scout)</h3>
                        <p class="text-xs text-gray-500">Dijalankan otomatis setiap hari jam 07:00 untuk menganalisa tren konten parenting Islam remaja.</p>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <?php if (empty($saved_macro)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-gray-400 text-center">
                                <i class="fas fa-moon text-5xl mb-4 opacity-50"></i>
                                <p>Belum ada laporan tren harian untuk hari ini.</p>
                                <p class="text-xs">Agent akan bekerja pada jam 07:00 setiap hari.</p>
                            </div>
                        <?php else: ?>
                            <div class="markdown-body macro-body" id="macro-result"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Laporan Mikro (Harian) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 h-full min-h-[400px] flex flex-col overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100">
                        <h3 class="font-bold text-gray-800"><i class="fas fa-calendar-week mr-2 text-emerald-500"></i>Ide & Sudut Pandang Konten (Mikro)</h3>
                        <p class="text-xs text-gray-500">Dijalankan otomatis setiap hari jam 07:00 setelah tren harian dirumuskan.</p>
                    </div>
                    <div class="p-6 flex-1 overflow-y-auto">
                        <?php if (empty($saved_micro)): ?>
                            <div class="flex flex-col items-center justify-center h-full text-gray-400 text-center">
                                <i class="fas fa-hourglass-half text-5xl mb-4 opacity-50"></i>
                                <p>Belum ada laporan tren mikro untuk hari ini.</p>
                                <p class="text-xs">Agent akan bekerja pada jam 07:00 setiap hari.</p>
                            </div>
                        <?php else: ?>
                            <div class="markdown-body micro-body" id="micro-result"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Prompt Editor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <details>
                    <summary class="px-6 py-4 font-bold text-gray-800 cursor-pointer flex justify-between items-center">
                        <span><i class="fas fa-cogs mr-2"></i> Pengaturan Prompt AI</span>
                        <i class="fas fa-chevron-down transition-transform duration-300"></i>
                    </summary>
                    <div class="p-6 border-t border-gray-100">
                        <form action="admin-trend-scout.php" method="POST">
                            <input type="hidden" name="action" value="save_prompt">
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <div>
                                    <label for="prompt_macro" class="block text-sm font-medium text-gray-700 mb-2">Prompt Tren Harian (Trend Scout)</label>
                                    <textarea id="prompt_macro" name="prompt_macro" rows="10" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-xs focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($prompt_macro) ?></textarea>
                                </div>
                                <div>
                                    <label for="prompt_micro" class="block text-sm font-medium text-gray-700 mb-2">Prompt Tren Mikro (Harian)</label>
                                    <p class="text-xs text-gray-500 mb-2">Gunakan placeholder <code>{{THEME}}</code> untuk menyisipkan tema besar hari ini.</p>
                                    <textarea id="prompt_micro" name="prompt_micro" rows="10" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-xs focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($prompt_micro) ?></textarea>
                                </div>
                            </div>
                            <button type="submit" class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded-lg transition shadow-sm"><i class="fas fa-save mr-2"></i> Simpan Semua Prompt</button>
                        </form>
                    </div>
                </details>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const macroContent = <?= json_encode($saved_macro) ?>;
            const microContent = <?= json_encode($saved_micro) ?>;
            if (macroContent) { document.getElementById('macro-result').innerHTML = marked.parse(macroContent); }
            if (microContent) { document.getElementById('micro-result').innerHTML = marked.parse(microContent); }
        });
    </script>
</body>
</html>