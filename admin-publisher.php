<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'publisher';

// 1. Ambil semua data agen dari database
$all_agents = [];
$res_agents = $conn->query("SELECT nama, whatsapp FROM agen ORDER BY nama ASC");
if ($res_agents) {
    while ($row = $res_agents->fetch_assoc()) {
        $all_agents[] = $row;
    }
}

// 2. Baca dan proses log untuk menemukan broadcast terakhir
$sent_agents = [];
$last_article_title = 'Belum ada artikel yang pernah dibagikan.';
$log_file = 'agent_cron_log.txt';

// Ambil judul artikel terakhir yang berhasil dikirim dari DB
$res_last_article = $conn->query("SELECT judul FROM artikel WHERE status_broadcast = 'terkirim' ORDER BY updated_at DESC LIMIT 1");
if ($res_last_article && $res_last_article->num_rows > 0) {
    $last_article_title = $res_last_article->fetch_assoc()['judul'];
}

// Ambil daftar agen yang sudah dikirimi pesan dari log (untuk hari ini)
if (file_exists($log_file)) {
    $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($log_lines as $line) {
        // Hanya ambil log hari ini
        if (strpos($line, date('Y-m-d')) !== false && strpos($line, '-> Pesan WA') !== false) {
            preg_match('/dilesatkan ke: (.*?)\./', $line, $matches);
            if (isset($matches[1])) $sent_agents[] = trim($matches[1]);
        }
    }
}

// --- PROMPT MANAGEMENT ---
$prompt_file = 'prompt_publisher.txt';
$default_prompt = "Assalamu'alaikum Kak {{NAMA_AGEN}}, artikel terbaru Villa Quran udah rilis pagi ini lho. \n\nJudul: *{{JUDUL_ARTIKEL}}* \n\nMonggo di-share pake link afiliasi khusus Kakak di bawah ini ya, biar komisinya kecatat otomatis: \n{{LINK_AFILIASI}} \n\nSemoga hari ini closing banyak, Aamiin!";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'save_prompt') {
    file_put_contents($prompt_file, $_POST['prompt_content']);
    header("Location: admin-publisher.php?prompt_saved=1");
    exit;
}

$prompt_publisher = file_exists($prompt_file) ? file_get_contents($prompt_file) : $default_prompt;
$prompt_saved_notif = isset($_GET['prompt_saved']);


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Publisher | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-marketing.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10"><h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2><div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div></header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-paper-plane text-emerald-600 mr-2"></i>Publisher</h1><p class="text-gray-500 mt-1">Monitor status pengiriman artikel terbaru ke semua agen via WhatsApp.</p></div>

            <?php if($prompt_saved_notif): ?>
            <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm border border-emerald-200"><i class="fas fa-check-circle mr-2"></i> Prompt berhasil diperbarui! Perubahan akan diterapkan pada pekerjaan AI berikutnya.</div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 p-6">
                <h3 class="font-bold text-gray-800 mb-2">Artikel Terakhir Disebarkan</h3>
                <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg flex items-center"><i class="fas fa-file-alt text-2xl text-gray-400 mr-4"></i><div><p class="font-semibold text-gray-700"><?= htmlspecialchars($last_article_title) ?></p><p class="text-xs text-gray-500">Disebarkan sekitar pukul 07:15 WIB</p></div></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100"><h3 class="font-bold text-gray-800">Checklist Pengiriman Pesan WhatsApp</h3></div>
                <div class="p-4"><ul class="space-y-3">
                    <?php if (empty($all_agents)): ?><li class="text-center text-gray-500 py-8">Belum ada data agen di sistem.</li>
                    <?php else: foreach ($all_agents as $agent): $is_sent = in_array($agent['nama'], $sent_agents); ?>
                        <li class="flex items-center justify-between bg-gray-50 hover:bg-gray-100 p-3 rounded-lg border border-gray-200 transition">
                            <div class="flex items-center"><div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center mr-3"><i class="fas fa-user text-gray-500"></i></div><div><p class="font-bold text-gray-800"><?= htmlspecialchars($agent['nama']) ?></p><p class="text-xs text-gray-500 font-mono"><?= htmlspecialchars($agent['whatsapp']) ?></p></div></div>
                            <?php if ($is_sent): ?><div class="flex items-center text-green-600"><i class="fas fa-check-circle mr-2"></i><span class="text-xs font-bold">Terkirim</span></div>
                            <?php else: ?><div class="flex items-center text-gray-400"><i class="fas fa-clock mr-2"></i><span class="text-xs font-bold">Menunggu Jadwal</span></div><?php endif; ?>
                        </li>
                    <?php endforeach; endif; ?>
                </ul></div>
            </div>

            <!-- Prompt Editor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <details>
                    <summary class="px-6 py-4 font-bold text-gray-800 cursor-pointer flex justify-between items-center">
                        <span><i class="fas fa-cogs mr-2"></i> Pengaturan Prompt AI (Template Pesan WA)</span>
                        <i class="fas fa-chevron-down transition-transform duration-300"></i>
                    </summary>
                    <div class="p-6 border-t border-gray-100">
                        <form action="admin-publisher.php" method="POST">
                            <input type="hidden" name="action" value="save_prompt">
                            <label for="prompt_content" class="block text-sm font-medium text-gray-700 mb-2">Gunakan placeholder: <code>{{NAMA_AGEN}}</code>, <code>{{JUDUL_ARTIKEL}}</code>, <code>{{LINK_AFILIASI}}</code></label>
                            <textarea id="prompt_content" name="prompt_content" rows="8" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-xs focus:ring-emerald-500 focus:border-emerald-500"><?= htmlspecialchars($prompt_publisher) ?></textarea>
                            <button type="submit" class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-5 rounded-lg transition shadow-sm"><i class="fas fa-save mr-2"></i> Simpan Template</button>
                        </form>
                    </div>
                </details>
            </div>
        </main>
    </div>
</body>
</html>