<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'ai-hub';
$pesan_notif = '';

// Handle toggle autopilot
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['autopilot_status'])) {
    $status = $_POST['autopilot_status'] === 'ON' ? 'ON' : 'OFF';
    file_put_contents('autopilot_status.txt', $status);
    $pesan_notif = "Status AI Agent berhasil diubah menjadi " . ($status === 'ON' ? 'AKTIF' : 'NONAKTIF');
}

$autopilot_status = file_exists('autopilot_status.txt') ? file_get_contents('autopilot_status.txt') : 'OFF';

// Baca log untuk status
$log_content = file_exists('agent_cron_log.txt') ? file_get_contents('agent_cron_log.txt') : 'Belum ada aktivitas.';
$log_lines = explode("\n", trim($log_content));
$last_log = $log_lines[count($log_lines)-1];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Kendali AI | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .arrow { position: relative; }
        .arrow::after {
            content: '\f061';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            right: -2.5rem;
            transform: translateY(-50%);
            font-size: 2rem;
            color: #9ca3af;
        }
        @media (max-width: 1024px) {
            .arrow::after {
                content: '\f063';
                top: auto;
                bottom: -2.5rem;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-marketing.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <h2 class="font-bold text-gray-800">Pusat Kendali AI Agent</h2>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-robot text-indigo-600 mr-2"></i>Pusat Kendali AI Agent</h1>
                <p class="text-gray-500 mt-1">Aktifkan atau nonaktifkan seluruh AI Agent dari sini.</p>
            </div>

            <?php if($pesan_notif): ?>
            <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm border border-emerald-200">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($pesan_notif) ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <h3 class="font-bold text-lg text-gray-800 mb-3">Status Autopilot</h3>
                <form action="admin-ai-hub.php" method="POST" class="flex items-center space-x-4">
                    <input type="hidden" name="autopilot_status" value="<?= $autopilot_status === 'ON' ? 'OFF' : 'ON' ?>">
                    <button type="submit" class="relative inline-flex items-center h-6 rounded-full w-11 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 <?= $autopilot_status === 'ON' ? 'bg-indigo-600' : 'bg-gray-200' ?>">
                        <span class="sr-only">Enable notifications</span>
                        <span class="inline-block w-4 h-4 transform bg-white rounded-full transition-transform <?= $autopilot_status === 'ON' ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                    </button>
                    <span class="font-medium text-lg <?= $autopilot_status === 'ON' ? 'text-indigo-700' : 'text-gray-500' ?>">
                        <?= $autopilot_status === 'ON' ? 'AKTIF' : 'NONAKTIF' ?>
                    </span>
                </form>
                <p class="text-sm text-gray-500 mt-3">
                    Jika <span class="font-bold text-indigo-600">AKTIF</span>, semua AI Agent akan bekerja secara otomatis sesuai jadwal yang ditentukan. Jika <span class="font-bold text-gray-600">NONAKTIF</span>, semua agent akan berhenti bekerja.
                </p>
            </div>

            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">Visualisasi Alur Kerja AI Agent</h2>
                <p class="text-gray-500 mt-1">Beginilah cara para agent bekerja sama secara berantai.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
                <!-- Agent 1 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center arrow">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-brain"></i></div>
                    <h3 class="font-bold text-lg text-gray-800">Analisa Persona</h3>
                    <p class="text-sm text-gray-500 mt-2">Menganalisa data leads dan jejak pengunjung untuk membuat profil target audiens (TOFU, MOFU, BOFU).</p>
                    <span class="absolute top-2 right-2 text-xs font-bold bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Bulanan</span>
                </div>

                <!-- Agent 2 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center arrow">
                    <div class="w-16 h-16 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-calendar-alt"></i></div>
                    <h3 class="font-bold text-lg text-gray-800">Kalender Konten</h3>
                    <p class="text-sm text-gray-500 mt-2">Menyusun jadwal dan ide konten editorial untuk 30 hari ke depan berdasarkan hasil analisa persona.</p>
                    <span class="absolute top-2 right-2 text-xs font-bold bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Bulanan</span>
                </div>

                <!-- Agent 3 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center arrow">
                    <div class="w-16 h-16 bg-teal-100 text-teal-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-pen-nib"></i></div>
                    <h3 class="font-bold text-lg text-gray-800">Penulis Artikel SEO</h3>
                    <p class="text-sm text-gray-500 mt-2">Menulis artikel SEO setiap hari sesuai jadwal, lalu mempublikasikannya secara otomatis ke website.</p>
                    <span class="absolute top-2 right-2 text-xs font-bold bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Harian</span>
                </div>

                <!-- Agent 4 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                    <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-paper-plane"></i></div>
                    <h3 class="font-bold text-lg text-gray-800">Publisher</h3>
                    <p class="text-sm text-gray-500 mt-2">Mendistribusikan artikel baru ke semua agen via WhatsApp, lengkap dengan link afiliasi unik mereka.</p>
                    <span class="absolute top-2 right-2 text-xs font-bold bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Harian</span>
                </div>
            </div>

            <div class="bg-gray-800 text-gray-300 rounded-xl shadow-inner p-4 mt-8 font-mono text-xs">
                <p class="font-bold text-cyan-400 mb-2">> Log Aktivitas Terakhir:</p>
                <p><?= htmlspecialchars($last_log) ?></p>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('open-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>