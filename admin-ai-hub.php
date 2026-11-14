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

$autopilot_status = file_exists('autopilot_status.txt') ? trim(file_get_contents('autopilot_status.txt')) : 'OFF';

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
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden p-1.5 rounded-lg hover:bg-gray-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-teal-100 text-[#0b8478] flex items-center justify-center font-bold text-sm">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800 text-sm sm:text-base leading-tight">Crew AI (Pusat Kendali AI)</h2>
                        <p class="text-[10px] text-gray-500 font-medium">Autonomous Digital Marketing Workforce</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="dashboard-marketing.php" class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-chart-line text-[#0b8478]"></i> <span class="hidden sm:inline">Statistik Pengunjung</span>
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-4 sm:p-6 pb-20 md:pb-6">
            <div class="mb-6">
                <h1 class="text-2xl font-black text-gray-900 flex items-center gap-2">
                    <i class="fas fa-robot text-[#0b8478]"></i> Pusat Kendali Crew AI
                </h1>
                <p class="text-xs text-gray-500 mt-1">Pusat komando dan daftar tim AI Agent otonom untuk konten, riset, dan pemasaran digital.</p>
            </div>

            <!-- CREW AI AGENT TOOLS GRID -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center gap-2">
                        <i class="fas fa-users-gear text-[#0b8478]"></i> Modul & Pasukan Agent AI
                    </h2>
                    <span class="text-[11px] text-gray-400">Pilih modul agent di bawah ini</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    <a href="admin-seo.php" class="bg-white hover:bg-emerald-50/50 border border-gray-200 hover:border-emerald-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-pen-nib"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-emerald-700">Penulis Artikel SEO</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Generator Artikel Otomatis</span>
                    </a>

                    <a href="admin-kalender.php" class="bg-white hover:bg-sky-50/50 border border-gray-200 hover:border-sky-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-sky-100 text-sky-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-sky-700">Kalender Konten AI</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Penjadwalan 30 Hari</span>
                    </a>

                    <a href="admin-analisa.php" class="bg-white hover:bg-purple-50/50 border border-gray-200 hover:border-purple-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-brain"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-purple-700">Analisa Persona</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Segmentasi Audiens</span>
                    </a>

                    <a href="admin-community-scout.php" class="bg-white hover:bg-rose-50/50 border border-gray-200 hover:border-rose-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-search-location"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-rose-700">Community Scout</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Riset Komunitas & Grup</span>
                    </a>

                    <a href="admin-publisher.php" class="bg-white hover:bg-teal-50/50 border border-gray-200 hover:border-teal-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-teal-100 text-[#0b8478] flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-[#0b8478]">Publisher</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Distribusi ke Mitra & Web</span>
                    </a>

                    <a href="admin-trend-scout.php" class="bg-white hover:bg-violet-50/50 border border-gray-200 hover:border-violet-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-violet-100 text-violet-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-violet-700">Trend Scout</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Riset Tren & Hook Viral</span>
                    </a>

                    <a href="data-pipeline.php" class="bg-white hover:bg-amber-50/50 border border-gray-200 hover:border-amber-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-columns"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-amber-700">Pipeline Prospek</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Kanban Board Leads</span>
                    </a>

                    <a href="admin-sosmed-workflow.php" class="bg-white hover:bg-indigo-50/50 border border-gray-200 hover:border-indigo-400 rounded-2xl p-4 flex flex-col items-center justify-center text-center shadow-xs hover:shadow-md transition group">
                        <div class="w-12 h-12 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-xl mb-2.5 group-hover:scale-110 transition-transform">
                            <i class="fas fa-diagram-project"></i>
                        </div>
                        <span class="text-xs font-black text-gray-900 group-hover:text-indigo-700">Multi-Agent Flow</span>
                        <span class="text-[10px] text-gray-500 mt-0.5">Alur Kerja Otonom</span>
                    </a>
                </div>
            </div>

            <?php if($pesan_notif): ?>
            <div class="bg-emerald-100 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm border border-emerald-200">
                <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($pesan_notif) ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Status Autopilot Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-lg text-gray-800 mb-3"><i class="fas fa-toggle-on text-indigo-600 mr-2"></i>Status Autopilot</h3>
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
                    <p class="text-xs text-gray-500 mt-3">
                        Jika <span class="font-bold text-indigo-600">AKTIF</span>, semua AI Agent bekerja otomatis via cron job. Jika <span class="font-bold text-gray-600">NONAKTIF</span>, agent hanya dapat dijalankan manual.
                    </p>
                </div>

                <!-- Manual Trigger Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-lg text-gray-800 mb-3"><i class="fas fa-play text-emerald-600 mr-2"></i>Pusat Jalankan Manual</h3>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="jalankanAgentManual('seo')" id="btn-run-seo" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow transition flex items-center">
                            <i class="fas fa-pen-nib mr-1.5 animate-pulse"></i> Tulis & Publish Artikel (SEO)
                        </button>
                        <button onclick="jalankanAgentManual('billing')" id="btn-run-billing" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow transition flex items-center">
                            <i class="fas fa-bell mr-1.5 animate-pulse"></i> Kirim Tagihan WA Santri
                        </button>
                        <button onclick="jalankanAgentManual('community')" id="btn-run-community" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow transition flex items-center">
                            <i class="fas fa-search-location mr-1.5 animate-pulse"></i> Cari Grup Komunitas
                        </button>
                        <button onclick="jalankanAgentManual('hrd_laporan')" id="btn-run-hrd" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-4 rounded-lg text-xs shadow transition flex items-center">
                            <i class="fas fa-file-invoice mr-1.5 animate-pulse"></i> Kirim Laporan Harian (HRD)
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-3">
                        Klik tombol di atas untuk memaksa AI Agent memproses data langsung hari ini tanpa menunggu jadwal.
                    </p>
                </div>
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
                    <div class="w-16 h-16 bg-sky-100 text-sky-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-search-dollar"></i></div>
                    <h3 class="font-bold text-lg text-gray-800">Hook & Keyword Explorer</h3>
                    <p class="text-sm text-gray-500 mt-2">Meriset judul hook viral dan kata kunci target penulisan artikel berdasarkan hasil harian Trend Scout.</p>
                    <span class="absolute top-2 right-2 text-xs font-bold bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full">Harian</span>
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
                <p class="font-bold text-cyan-400 mb-2">> Log Aktivitas & Hasil Eksekusi Manual:</p>
                <div id="manual-run-log" class="max-h-60 overflow-y-auto whitespace-pre-wrap leading-relaxed"><?= htmlspecialchars($last_log) ?></div>
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

        // Jalankan AI Agent secara manual via AJAX dengan Log Streaming Real-Time
        function jalankanAgentManual(type) {
            const btnSeo = document.getElementById('btn-run-seo');
            const btnBilling = document.getElementById('btn-run-billing');
            const btnCommunity = document.getElementById('btn-run-community');
            const btnHrd = document.getElementById('btn-run-hrd');
            const logBox = document.getElementById('manual-run-log');
            
            if (btnSeo) btnSeo.disabled = true;
            if (btnBilling) btnBilling.disabled = true;
            if (btnCommunity) btnCommunity.disabled = true;
            if (btnHrd) btnHrd.disabled = true;
            
            let originalText = '';
            let targetBtn = null;
            
            if (type === 'seo') {
                originalText = btnSeo.innerHTML;
                targetBtn = btnSeo;
            } else if (type === 'billing') {
                originalText = btnBilling.innerHTML;
                targetBtn = btnBilling;
            } else if (type === 'community') {
                originalText = btnCommunity.innerHTML;
                targetBtn = btnCommunity;
            } else if (type === 'hrd_laporan') {
                originalText = btnHrd.innerHTML;
                targetBtn = btnHrd;
            }
            
            if (targetBtn) {
                targetBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Memproses...';
            }
            logBox.innerHTML = "> Menghubungi AI Agent untuk memulai proses secara manual...\n";
            logBox.scrollTop = logBox.scrollHeight;
            
            fetch('cron-agent.php?force=' + type)
            .then(response => {
                if (!response.ok) {
                    throw new Error("HTTP error! Status: " + response.status);
                }
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                
                function readChunk() {
                    return reader.read().then(({ done, value }) => {
                        if (value) {
                            const chunkText = decoder.decode(value, { stream: !done });
                            // Ubah <br> menjadi \n agar rapi di textarea/log console
                            const cleanText = chunkText.replace(/<br>/gi, '\n');
                            logBox.innerHTML += cleanText;
                            logBox.scrollTop = logBox.scrollHeight;
                        }
                        if (done) {
                            logBox.innerHTML += "\n> Proses selesai!";
                            logBox.scrollTop = logBox.scrollHeight;
                            if (btnSeo) btnSeo.disabled = false;
                            if (btnBilling) btnBilling.disabled = false;
                            if (btnCommunity) btnCommunity.disabled = false;
                            if (btnHrd) btnHrd.disabled = false;
                            if (targetBtn) targetBtn.innerHTML = originalText;
                            return;
                        }
                        return readChunk();
                    });
                }
                return readChunk();
            })
            .catch(err => {
                logBox.innerHTML += "\n[Error] Gagal mengeksekusi agent: " + err.message + "\n";
                logBox.scrollTop = logBox.scrollHeight;
                if (btnSeo) btnSeo.disabled = false;
                if (btnBilling) btnBilling.disabled = false;
                if (btnCommunity) btnCommunity.disabled = false;
                if (btnHrd) btnHrd.disabled = false;
                if (targetBtn) targetBtn.innerHTML = originalText;
            });
        }
    </script>
</body>
</html>