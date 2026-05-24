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
$last_article_title = 'Belum ada artikel yang dibagikan hari ini.';
$log_file = 'agent_cron_log.txt';

if (file_exists($log_file)) {
    $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $log_lines = array_reverse($log_lines); // Baca dari bawah ke atas

    $in_daily_task_block = false;
    foreach ($log_lines as $line) {
        // Jika menemukan awal blok tugas harian, mulai rekam
        if (strpos($line, '======= MEMULAI TUGAS HARIAN') !== false) {
            $in_daily_task_block = true;
        }

        if ($in_daily_task_block) {
            // Ekstrak nama agen yang sudah dikirimi pesan
            if (strpos($line, '-> Pesan WA dilesatkan ke:') !== false) {
                $parts = explode('ke:', $line);
                if (isset($parts[1])) {
                    $name_part = explode('.', $parts[1])[0];
                    $sent_agents[] = trim($name_part);
                }
            }
            
            // Ekstrak judul artikel
            if (strpos($line, 'Mulai menulis draf artikel:') !== false) {
                 $parts = explode('artikel:', $line);
                 if(isset($parts[1])) {
                    $last_article_title = trim($parts[1]);
                 }
            }

            // Jika menemukan blok tugas harian sebelumnya atau penanda selesai, hentikan pencarian
            if (strpos($line, 'Tugas Harian Tuntas!') !== false || ($in_daily_task_block && strpos($line, '======= MEMULAI TUGAS HARIAN') !== false && count($sent_agents) > 0)) {
                break;
            }
        }
    }
}

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

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-6">
                <h3 class="font-bold text-gray-800 mb-2">Artikel Terakhir Disebarkan</h3>
                <div class="bg-gray-50 border border-gray-200 p-4 rounded-lg flex items-center"><i class="fas fa-file-alt text-2xl text-gray-400 mr-4"></i><div><p class="font-semibold text-gray-700"><?= htmlspecialchars($last_article_title) ?></p><p class="text-xs text-gray-500">Disebarkan sekitar pukul 07:15 WIB</p></div></div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
        </main>
    </div>
</body>
</html>