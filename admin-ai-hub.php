<?php
require_once 'auth.php';
require_once 'koneksi.php';

// --- SAKLAR OTORITAS AGENT OTONOM ---
if (isset($_POST['toggle_autopilot'])) {
    $status = $_POST['toggle_autopilot'] === 'ON' ? 'ON' : 'OFF';
    file_put_contents('autopilot_status.txt', $status);
    echo "OK";
    exit;
}

// Ambil data Leads untuk bahan bakar AI
$leads_data = [];
$sql = "SELECT jenis_lead, sumber_info, status FROM leads ORDER BY id DESC LIMIT 100";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) $leads_data[] = $row;
}

// Ambil data Jejak Pengunjung (Footprint)
$footprints_data = [];
$sql_fp = "SELECT device, location, source, campaign FROM visitor_footprints ORDER BY id DESC LIMIT 100";
$result_fp = $conn->query($sql_fp);
if ($result_fp && $result_fp->num_rows > 0) {
    while($row = $result_fp->fetch_assoc()) $footprints_data[] = $row;
}

// Ambil data Agen untuk Broadcast WA
$agen_data = [];
$sql_agen = "SELECT nama, whatsapp, kode_ref FROM agen ORDER BY id ASC";
$result_agen = $conn->query($sql_agen);
if ($result_agen && $result_agen->num_rows > 0) {
    while($row = $result_agen->fetch_assoc()) $agen_data[] = $row;
}

// Cek waktu terakhir Agent 1 (Persona) berjalan (Mingguan)
$last_persona_time = file_exists('saved_persona.txt') ? filemtime('saved_persona.txt') : 0;
$days_since_persona = floor((time() - $last_persona_time) / (60 * 60 * 24));
$is_time_for_weekly = ($days_since_persona >= 7 || $last_persona_time == 0) ? true : false;

$autopilot = file_exists('autopilot_status.txt') ? file_get_contents('autopilot_status.txt') : 'OFF';
$saved_kalender = file_exists('saved_kalender.txt') ? file_get_contents('saved_kalender.txt') : '';

$active_menu = 'ai-hub';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent Hub | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING -->
    <?php include 'sidebar-marketing.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <h2 class="font-bold text-gray-800 text-xl"><i class="fas fa-robot text-indigo-600 mr-2"></i> Pusat Kendali AI Agent (Workflow)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            
            <!-- PANEL SAKLAR AI AGENT OTONOM -->
            <div class="mb-6 bg-gray-900 rounded-xl shadow-lg border border-gray-800 p-6 flex flex-col md:flex-row justify-between items-center relative overflow-hidden">
                <!-- Background Ornamen -->
                <div class="absolute top-0 right-0 -mt-4 -mr-4 w-32 h-32 bg-emerald-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
                <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
                
                <div class="relative z-10 mb-4 md:mb-0">
                    <h2 class="text-2xl font-extrabold text-white flex items-center">
                        <i class="fas fa-satellite-dish text-emerald-400 mr-3 animate-pulse"></i> Mode Agen Otonom (Auto-Pilot)
                    </h2>
                    <p class="text-gray-400 text-sm mt-1 max-w-xl">Saat aktif, AI Agent akan bekerja sendiri (otonom) di server setiap pagi tanpa perlu Anda klik apapun. Layaknya karyawan digital yang mandiri.</p>
                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle text-blue-400 mr-1"></i> <b>Cara Kerja:</b> Daftarkan URL <code>https://<?= $_SERVER['HTTP_HOST'] ?? 'domain.com' ?>/cron-agent.php</code> ke fitur <b>Cron Jobs</b> di Hostinger untuk dipanggil setiap jam 07:00 pagi.</p>
                </div>
                <div class="relative z-10 flex items-center bg-gray-800 p-3 rounded-xl border border-gray-700">
                    <span class="text-xs font-bold text-gray-400 mr-3 px-2">IZIN KERJA AGENT:</span>
                    <label class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox" id="autopilot-toggle" class="sr-only peer" <?= $autopilot == 'ON' ? 'checked' : '' ?>>
                        <div class="w-14 h-7 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-emerald-500 shadow-inner"></div>
                    </label>
                </div>
            </div>

            <!-- WORKFLOW PIPELINE UI -->
            <div class="mb-8 grid grid-cols-1 md:grid-cols-4 gap-4 relative">
                
                <!-- Garis Penghubung (Hanya hiasan UI) -->
                <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-gray-200 -z-10 transform -translate-y-1/2"></div>

                <!-- AGENT 1: BUYER PERSONA -->
                <div class="bg-white rounded-xl shadow border-2 border-emerald-200 p-6 relative">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">1. Analis & Perencana</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Setiap Tgl 1 (06:00) menganalisa prospek & menyusun kalender 30 hari.</p>
                </div>

                <!-- AGENT 3: GENERATOR ARTIKEL -->
                <div class="bg-white rounded-xl shadow border-2 border-blue-200 p-6 relative">
                    <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white">
                        <i class="fas fa-pen-nib"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">2. Agen Penulis (SEO)</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Setiap Hari (07:00) memposting artikel otomatis dari kalender.</p>
                </div>

                <!-- AGENT 5: WA BROADCASTER -->
                <div class="bg-white rounded-xl shadow border-2 border-green-200 p-6 relative">
                    <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">3. Agen Kurir (WA)</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Setelah artikel rilis, otomatis broadcast URL ke semua tim agen.</p>
                </div>

                <!-- HASIL: CUAN -->
                <div class="bg-white rounded-xl shadow border-2 border-amber-200 p-6 relative">
                    <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-xl mx-auto mb-4 border-4 border-white">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3 class="font-bold text-center text-gray-800">4. Lead Masuk</h3>
                    <p class="text-xs text-center text-gray-500 mt-2">Tim Sales/CS tinggal fokus mem-follow up prospek yang masuk.</p>
                </div>
            </div>

            <!-- LOG HASIL KERJA (TERMINAL) -->
            <div class="bg-gray-900 rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4 border-b border-gray-700 pb-2">
                    <h3 class="text-gray-100 font-bold"><i class="fas fa-terminal mr-2"></i> Live Log Otonom (Cron)</h3>
                    <button onclick="fetchLogs()" class="text-xs bg-gray-800 text-gray-300 hover:text-white px-3 py-1 rounded border border-gray-600 transition"><i class="fas fa-sync-alt mr-1"></i> Refresh Log</button>
                </div>
                <div id="console-log" class="h-80 overflow-y-auto font-mono text-sm text-green-400 space-y-2 whitespace-pre-wrap leading-relaxed">
                    Memuat status agent...
                </div>
            </div>
        </main>
    </div>

    <script>
        function addLog(message, isError = false) {
            const logContainer = document.getElementById('console-log');
            const colorClass = isError ? 'text-red-400' : 'text-green-400';
            logContainer.innerHTML += `<div class="${colorClass}">> ${message}</div>`;
            logContainer.scrollTop = logContainer.scrollHeight;
        }

        // ==========================================
        // TOGGLE AGENT OTONOM
        // ==========================================
        const autopilotToggle = document.getElementById('autopilot-toggle');
        if (autopilotToggle) {
            autopilotToggle.addEventListener('change', function(e) {
                const status = e.target.checked ? 'ON' : 'OFF';
                const formData = new FormData();
                formData.append('toggle_autopilot', status);
                fetch('admin-ai-hub.php', { method: 'POST', body: formData })
                .then(res => res.text()).then(text => {
                    if(text.trim() === 'OK') {
                        addLog("⚙️ Status Otoritas AI Agent Otonom berhasil diubah menjadi: " + status);
                    }
                });
            });
        }

        function fetchLogs() {
            fetch('agent_cron_log.txt?t=' + new Date().getTime())
                .then(response => response.ok ? response.text() : 'Belum ada catatan tugas. Agent mungkin masih tertidur lelap...')
                .then(text => {
                    const logContainer = document.getElementById('console-log');
                    logContainer.textContent = text;
                    logContainer.scrollTop = logContainer.scrollHeight;
                }).catch(err => console.log('Gagal menarik log'));
        }
        
        // Otomatis tarik data log setiap 10 detik agar Terminal seperti "Live"
        setInterval(fetchLogs, 10000);
        fetchLogs();
    </script>
</body>
</html>