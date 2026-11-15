<?php
/**
 * admin-cs-ai.php
 * Panel Kendali CS AI Hasna (Penerimaan Santri Baru & Layanan Informasi 24 Jam)
 * Ruang PSB - Villa Quran Baron Malang
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';
require_once 'auth-unified.php';

// Validasi Akses
$user = getCurrentUser();
if (!$user) {
    if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['yayasan_logged_in']) && !isset($_SESSION['yayasan_user']) && !isset($_SESSION['app_user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Ambil Konfigurasi CS AI Hasna
require_once __DIR__ . '/api-cs-hasna.php'; // Otomatis menginisialisasi tabel jika belum ada
$cfg = getCsConfig($conn);

// Hitung Statistik
$statLeads = 0;
$resL = $conn->query("SELECT COUNT(id) as c FROM cs_chat_sessions");
if ($resL) { $statLeads = (int)$resL->fetch_assoc()['c']; }

$statPesan = 0;
$resP = $conn->query("SELECT COUNT(id) as c FROM cs_chat_messages");
if ($resP) { $statPesan = (int)$resP->fetch_assoc()['c']; }

$statKnowledge = 0;
$resK = $conn->query("SELECT COUNT(id) as c FROM cs_ai_learning WHERE status = 'aktif'");
if ($resK) { $statKnowledge = (int)$resK->fetch_assoc()['c']; }

// Ambil Daftar Pengetahuan (Learning Engine)
$knowledgeList = [];
$resKb = $conn->query("SELECT * FROM cs_ai_learning ORDER BY frekuensi_ditanya DESC, id DESC");
if ($resKb && $resKb->num_rows > 0) {
    while ($row = $resKb->fetch_assoc()) {
        $knowledgeList[] = $row;
    }
}

// Ambil Daftar Sesi Chat (Leads)
$sessionsList = [];
$resSess = $conn->query("SELECT * FROM cs_chat_sessions ORDER BY id DESC LIMIT 50");
if ($resSess && $resSess->num_rows > 0) {
    while ($row = $resSess->fetch_assoc()) {
        $sessionsList[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CS AI Hasna • Pusat Kendali SPMB — Villa Quran</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Oswald:wght@500;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-header { font-family: 'Oswald', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex">

    <!-- SIDEBAR RUANG PSB -->
    <?php include 'sidebar-psb.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 min-w-0 flex flex-col h-screen overflow-y-auto">
        <!-- TOP HEADER -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <button id="mobile-toggle" class="md:hidden text-slate-600 hover:text-slate-900 focus:outline-none p-1">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-black text-slate-800 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center text-sm shadow-sm">
                                <i class="fas fa-headset"></i>
                            </span>
                            CS AI Hasna • Pusat Kendali SPMB
                        </h2>
                        <span class="bg-teal-100 text-teal-800 text-[11px] font-black px-2.5 py-0.5 rounded-full border border-teal-200">
                            Rotasi Multi-API 4x
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Layanan Informasi & Pendaftaran Santri Baru 24 Jam dengan Mesin Pembelajar Mandiri</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="index.html" target="_blank" class="hidden sm:inline-flex items-center gap-2 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 px-3 py-2 rounded-xl border border-teal-200 transition">
                    <i class="fas fa-external-link-alt"></i> Buka Website Publik
                </a>
            </div>
        </header>

        <!-- DASHBOARD BODY -->
        <div class="p-6 space-y-6 max-w-7xl w-full mx-auto">

            <!-- STATISTIC CARDS ROW -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Status CS Virtual</p>
                        <h3 class="text-xl font-black text-emerald-600 mt-1 flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span> Hasna Aktif
                        </h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Siaga 24 Jam Non-Stop</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                        🧕
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Leads Masuk (WA)</p>
                        <h3 class="text-2xl font-black text-slate-800 mt-1"><?= number_format($statLeads) ?></h3>
                        <p class="text-[11px] text-teal-600 font-bold mt-0.5">Kontak Calon Wali Santri</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Pesan Chat</p>
                        <h3 class="text-2xl font-black text-slate-800 mt-1"><?= number_format($statPesan) ?></h3>
                        <p class="text-[11px] text-slate-400 mt-0.5">Tanya Jawab Tersimpan</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                        <i class="fas fa-comments"></i>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Memori Pengetahuan</p>
                        <h3 class="text-2xl font-black text-amber-600 mt-1"><?= number_format($statKnowledge) ?></h3>
                        <p class="text-[11px] text-amber-600 font-bold mt-0.5">Topik Dipelajari Hasna</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                        <i class="fas fa-brain"></i>
                    </div>
                </div>
            </div>

            <!-- TAB NAVIGATION -->
            <div class="flex items-center border-b border-slate-200 space-x-2 overflow-x-auto no-scrollbar">
                <button onclick="switchTab('tab-config')" id="btn-tab-config" class="tab-btn px-4 py-3 text-xs font-extrabold text-teal-700 border-b-2 border-teal-600 flex items-center gap-2 whitespace-nowrap transition">
                    <i class="fas fa-sliders-h"></i> 1. Pengaturan Otak & Multi-API Free Tier
                </button>
                <button onclick="switchTab('tab-learning')" id="btn-tab-learning" class="tab-btn px-4 py-3 text-xs font-bold text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-2 whitespace-nowrap transition">
                    <i class="fas fa-graduation-cap"></i> 2. Mesin Pembelajaran Hasna (Learning Machine)
                    <span class="bg-amber-100 text-amber-800 text-[10px] px-1.5 py-0.5 rounded-full font-black"><?= $statKnowledge ?></span>
                </button>
                <button onclick="switchTab('tab-leads')" id="btn-tab-leads" class="tab-btn px-4 py-3 text-xs font-bold text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-2 whitespace-nowrap transition">
                    <i class="fas fa-address-book"></i> 3. Calon Wali Santri & Riwayat Chat
                    <span class="bg-teal-100 text-teal-800 text-[10px] px-1.5 py-0.5 rounded-full font-black"><?= $statLeads ?></span>
                </button>
                <button onclick="switchTab('tab-simulator')" id="btn-tab-simulator" class="tab-btn px-4 py-3 text-xs font-bold text-slate-500 hover:text-slate-800 border-b-2 border-transparent flex items-center gap-2 whitespace-nowrap transition">
                    <i class="fas fa-robot"></i> 4. Uji Coba Chat (Simulator) & Kode Widget
                </button>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 1: PENGATURAN OTAK & MULTI-API FREE TIER -->
            <!-- ========================================================================= -->
            <div id="tab-config" class="tab-content space-y-6">
                <form id="form-cs-config" class="space-y-6">
                    <input type="hidden" name="action" value="save_config">

                    <!-- CARD 0: IDENTITAS PETUGAS & NOMOR WHATSAPP CS AI -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <span class="text-xl">🧕</span>
                                Identitas Petugas & Nomor WhatsApp CS AI
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Bos dapat mengganti nama CS AI kapan saja sesuai nama petugas yang ditunjuk sekolah, serta mengubah nomor WhatsApp CS agar langsung tersambung ke nomor petugas yang aktif.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    <i class="fas fa-user-tag text-teal-600 mr-1"></i> Nama CS AI / Petugas yang Ditunjuk
                                </label>
                                <input type="text" name="bot_name" id="bot_name" value="<?= htmlspecialchars($cfg['bot_name'] ?? 'Hasna') ?>" placeholder="Contoh: Hasna / Ustadzah Aisyah / Kak Nisa" required class="w-full text-xs font-bold px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white shadow-2xs">
                                <p class="text-[11px] text-slate-400 mt-1">Nama ini yang akan menyapa calon wali santri di website dan panel chat.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    <i class="fab fa-whatsapp text-emerald-600 mr-1"></i> Nomor WhatsApp Petugas CS (Konsultasi Wali Santri)
                                </label>
                                <input type="text" name="whatsapp_cs" id="whatsapp_cs" value="<?= htmlspecialchars($cfg['whatsapp_cs'] ?? '6285189918115') ?>" placeholder="Contoh: 085189918115 / 6285189918115" required class="w-full text-xs font-mono font-bold px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white shadow-2xs">
                                <p class="text-[11px] text-slate-400 mt-1">Tombol "Chat WhatsApp Petugas" di widget web akan otomatis membuka chat ke nomor ini.</p>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 1: ROTASI MULTI-API KEY FREE TIER (3 s/d 4 API) -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                    <i class="fas fa-microchip text-teal-600"></i>
                                    Rotasi Otak Multi-API Free Tier (Auto-Failover)
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Masukkan 3 s/d 4 Google Gemini API Key gratis. Jika kuota API 1 habis (HTTP 429), Hasna akan <b>otomatis beralih ke API 2, 3, dan 4</b> secara mulus tanpa membuat percakapan terhenti.
                                </p>
                            </div>
                            <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 px-3 py-1.5 rounded-lg border border-teal-200 transition">
                                <i class="fas fa-key"></i> Buat API Key Gratis di Google AI Studio
                            </a>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- API Key 1 (Utama) -->
                            <div class="p-4 rounded-xl border border-teal-200 bg-teal-50/40 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-extrabold text-teal-900 flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full bg-teal-600 text-white text-[10px] flex items-center justify-center">1</span>
                                        Gemini API Key 1 (Utama)
                                    </label>
                                    <button type="button" onclick="testApiKey(1)" class="text-[11px] font-bold text-teal-700 hover:text-teal-900 bg-white px-2 py-0.5 rounded border border-teal-300 shadow-2xs">
                                        <i class="fas fa-bolt"></i> Uji Key
                                    </button>
                                </div>
                                <input type="password" name="api_key_1" id="api_key_1" value="<?= htmlspecialchars($cfg['api_key_1'] ?? '') ?>" placeholder="AIzaSy..." class="w-full text-xs font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                <div id="status-key-1" class="text-[11px] text-slate-500">Status: Belum diuji</div>
                            </div>

                            <!-- API Key 2 (Cadangan 1) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full bg-slate-600 text-white text-[10px] flex items-center justify-center">2</span>
                                        Gemini API Key 2 (Cadangan 1)
                                    </label>
                                    <button type="button" onclick="testApiKey(2)" class="text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-300 shadow-2xs">
                                        <i class="fas fa-bolt"></i> Uji Key
                                    </button>
                                </div>
                                <input type="password" name="api_key_2" id="api_key_2" value="<?= htmlspecialchars($cfg['api_key_2'] ?? '') ?>" placeholder="AIzaSy..." class="w-full text-xs font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                <div id="status-key-2" class="text-[11px] text-slate-500">Status: Belum diuji</div>
                            </div>

                            <!-- API Key 3 (Cadangan 2) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full bg-slate-600 text-white text-[10px] flex items-center justify-center">3</span>
                                        Gemini API Key 3 (Cadangan 2)
                                    </label>
                                    <button type="button" onclick="testApiKey(3)" class="text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-300 shadow-2xs">
                                        <i class="fas fa-bolt"></i> Uji Key
                                    </button>
                                </div>
                                <input type="password" name="api_key_3" id="api_key_3" value="<?= htmlspecialchars($cfg['api_key_3'] ?? '') ?>" placeholder="AIzaSy..." class="w-full text-xs font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                <div id="status-key-3" class="text-[11px] text-slate-500">Status: Belum diuji</div>
                            </div>

                            <!-- API Key 4 (Cadangan 3) -->
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                                        <span class="w-5 h-5 rounded-full bg-slate-600 text-white text-[10px] flex items-center justify-center">4</span>
                                        Gemini API Key 4 (Cadangan 3)
                                    </label>
                                    <button type="button" onclick="testApiKey(4)" class="text-[11px] font-bold text-slate-700 hover:text-slate-900 bg-white px-2 py-0.5 rounded border border-slate-300 shadow-2xs">
                                        <i class="fas fa-bolt"></i> Uji Key
                                    </button>
                                </div>
                                <input type="password" name="api_key_4" id="api_key_4" value="<?= htmlspecialchars($cfg['api_key_4'] ?? '') ?>" placeholder="AIzaSy..." class="w-full text-xs font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                <div id="status-key-4" class="text-[11px] text-slate-500">Status: Belum diuji</div>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: INTEGRASI WHATSAPP FONNTE & NOTIFIKASI -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <i class="fab fa-whatsapp text-emerald-600"></i>
                                Notifikasi WhatsApp Otomatis (Fonnte API)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Setiap kali ada calon wali santri baru yang memasukkan Nama & Nomor WhatsApp di widget Hasna, sistem langsung mengirimkan notifikasi WA ke nomor Admin/Panitia SPMB.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Token API Fonnte</label>
                                <input type="text" name="fonnte_token" id="fonnte_token" value="<?= htmlspecialchars($cfg['fonnte_token'] ?? '') ?>" placeholder="XFVpt4dRbo..." class="w-full text-xs font-mono px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                <p class="text-[11px] text-slate-400 mt-1">Token didapat dari dashboard <a href="https://fonnte.com" target="_blank" class="text-teal-600 underline">Fonnte.com</a></p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp Penerima Notifikasi (Admin/Yayasan)</label>
                                <input type="text" name="admin_wa" id="admin_wa" value="<?= htmlspecialchars($cfg['admin_wa'] ?? '6285189918115') ?>" placeholder="6285189918115" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                <p class="text-[11px] text-slate-400 mt-1">Format: 628xxxxxxxxxx (diawali 62)</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input type="checkbox" name="notify_admin" id="notify_admin" value="1" <?= (!empty($cfg['notify_admin']) ? 'checked' : '') ?> class="w-4 h-4 text-teal-600 rounded focus:ring-teal-500">
                            <label for="notify_admin" class="text-xs font-bold text-slate-700">Kirim Notifikasi WA Instan ke Admin saat ada Calon Wali Santri Chat di Web</label>
                        </div>
                    </div>

                    <!-- CARD 3: SYSTEM PROMPT SPMB & KNOWLEDGE SEKOLAH -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                        <div class="border-b border-slate-100 pb-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                    <i class="fas fa-book-open text-teal-600"></i>
                                    Prompt SPMB & Basis Pengetahuan Utama Sekolah
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Ini adalah pedoman dasar Hasna. Hasna akan selalu menjawab calon wali santri berlandaskan data ini secara sopan, Islami, singkat, padat, dan jelas.
                                </p>
                            </div>
                            <button type="button" onclick="loadDefaultPromptTemplate()" class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200 transition">
                                <i class="fas fa-undo"></i> Muat Template Standar SPMB
                            </button>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Teks Sapaan Awal (Greeting)</label>
                            <input type="text" name="greeting_text" id="greeting_text" value="<?= htmlspecialchars($cfg['greeting_text'] ?? '') ?>" placeholder="Assalamu'alaikum Ayah/Bunda! Ada yang bisa kami bantu seputar program tahfidz atau SPMB?" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Master Prompt & Basis Data SPMB (Profil, Biaya, Kurikulum, Fasilitas)</label>
                            <textarea name="system_prompt_spmb" id="system_prompt_spmb" rows="12" class="w-full text-xs font-mono leading-relaxed p-4 border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600 bg-slate-50/50"><?= htmlspecialchars($cfg['system_prompt_spmb'] ?? '') ?></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Tips: Masukkan info biaya pendaftaran, uang pangkal, syarat usia, nomor rekening, dan kontak resmi secara akurat.</p>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                            <button type="submit" id="btn-save-cfg" class="bg-teal-600 hover:bg-teal-700 text-white font-extrabold text-xs px-6 py-3 rounded-xl shadow-md transition flex items-center gap-2">
                                <i class="fas fa-save"></i> Simpan Konfigurasi Otak Hasna
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 2: MESIN PEMBELAJARAN HASNA (LEARNING MACHINE) -->
            <!-- ========================================================================= -->
            <div id="tab-learning" class="tab-content hidden space-y-6">
                <!-- FORM AJARI HASNA PENGETAHUAN BARU -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <i class="fas fa-brain text-amber-500"></i>
                                Ajari Hasna Pengetahuan Baru
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tambahkan tanya-jawab spesifik yang sering ditanyakan wali santri. Hasna akan langsung menyerapnya ke memorinya dan semakin cerdas menjawab.
                            </p>
                        </div>
                    </div>

                    <form id="form-add-knowledge" class="space-y-4">
                        <input type="hidden" name="action" value="add_knowledge">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Topik</label>
                                <select name="kategori" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-white">
                                    <option value="SPMB & Pendaftaran">SPMB & Pendaftaran</option>
                                    <option value="Biaya & SPP">Biaya & SPP</option>
                                    <option value="Kurikulum & Tahfidz">Kurikulum & Tahfidz</option>
                                    <option value="Fasilitas & Asrama">Fasilitas & Asrama</option>
                                    <option value="Perizinan & Kepulangan">Perizinan & Kepulangan</option>
                                    <option value="Kesehatan & Makanan">Kesehatan & Makanan</option>
                                    <option value="Umum">Umum</option>
                                </select>
                            </div>
                            <div class="md:col-span-3">
                                <label class="block text-xs font-bold text-slate-700 mb-1">Pertanyaan / Kata Kunci yang Ditanyakan Wali Santri</label>
                                <input type="text" name="pertanyaan" placeholder="Contoh: Apakah ada beasiswa bagi santri yatim atau berprestasi?" required class="w-full text-xs px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jawaban Standar Hasna (Sopan, Singkat, Padat, dan Jelas)</label>
                            <textarea name="jawaban_standar" rows="3" placeholder="Contoh: Ya, Villa Quran menyediakan beasiswa khusus tahfidz dan beasiswa dhuafa/yatim berprestasi melalui program seleksi berkas dan tes hafalan Al-Qur'an. Silakan hubungi Panitia SPMB di WA 0851-8991-8115 untuk kuota dan syarat lengkapnya." required class="w-full text-xs p-3 border border-slate-300 rounded-lg focus:outline-none focus:border-teal-600 bg-slate-50/50"></textarea>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs px-5 py-2.5 rounded-xl shadow-sm transition flex items-center gap-2">
                                <i class="fas fa-plus-circle"></i> Ajarkan ke Hasna Sekarang
                            </button>
                        </div>
                    </form>
                </div>

                <!-- DAFTAR PENGETAHUAN YANG SUDAH DIKUASAI HASNA -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                            <i class="fas fa-list-check text-teal-600"></i>
                            Daftar Pengetahuan yang Tersimpan di Memori Hasna (<?= count($knowledgeList) ?>)
                        </h3>
                    </div>

                    <?php if (empty($knowledgeList)): ?>
                        <div class="text-center py-10 text-slate-400 text-xs">
                            <i class="fas fa-brain text-4xl text-slate-200 mb-2"></i>
                            <p>Belum ada data pembelajaran tambahan. Tambahkan tanya-jawab di form atas untuk memperkaya memori Hasna.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider font-bold">
                                        <th class="py-3 px-3">Kategori</th>
                                        <th class="py-3 px-3">Pertanyaan Wali Santri</th>
                                        <th class="py-3 px-3">Jawaban Standar Hasna</th>
                                        <th class="py-3 px-3 text-center">Frekuensi Ditanya</th>
                                        <th class="py-3 px-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($knowledgeList as $kb): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <span class="bg-slate-100 text-slate-700 font-bold px-2 py-0.5 rounded text-[10px]">
                                                    <?= htmlspecialchars($kb['kategori']) ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 font-bold text-slate-800 max-w-xs">
                                                <?= htmlspecialchars($kb['pertanyaan']) ?>
                                            </td>
                                            <td class="py-3 px-3 text-slate-600 max-w-md">
                                                <?= nl2br(htmlspecialchars($kb['jawaban_standar'])) ?>
                                            </td>
                                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                                <span class="bg-teal-50 text-teal-700 font-extrabold px-2 py-0.5 rounded-full border border-teal-200 text-[10px]">
                                                    <?= (int)$kb['frekuensi_ditanya'] ?>x
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-right whitespace-nowrap">
                                                <button onclick="hapusKnowledge(<?= $kb['id'] ?>)" class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 transition" title="Hapus dari memori">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 3: CALON WALI SANTRI (LEADS) & RIWAYAT CHAT -->
            <!-- ========================================================================= -->
            <div id="tab-leads" class="tab-content hidden space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <i class="fas fa-user-friends text-teal-600"></i>
                                Kontak Calon Wali Santri Terjaring (<?= count($sessionsList) ?> Sesi)
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Daftar calon wali santri yang memulai chat dengan Hasna di website beserta nomor WhatsApp aktifnya.</p>
                        </div>
                    </div>

                    <?php if (empty($sessionsList)): ?>
                        <div class="text-center py-10 text-slate-400 text-xs">
                            <i class="fas fa-comments text-4xl text-slate-200 mb-2"></i>
                            <p>Belum ada sesi percakapan dari calon wali santri.</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-xs">
                                <thead>
                                    <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider font-bold">
                                        <th class="py-3 px-3">Waktu</th>
                                        <th class="py-3 px-3">Nama Wali Santri</th>
                                        <th class="py-3 px-3">WhatsApp</th>
                                        <th class="py-3 px-3 text-center">Total Pesan</th>
                                        <th class="py-3 px-3 text-right">Tindakan Admin</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($sessionsList as $sess): ?>
                                        <tr class="hover:bg-slate-50/70 transition">
                                            <td class="py-3 px-3 text-slate-400 whitespace-nowrap">
                                                <?= date('d M Y - H:i', strtotime($sess['created_at'])) ?>
                                            </td>
                                            <td class="py-3 px-3 font-extrabold text-slate-800">
                                                <?= htmlspecialchars($sess['nama']) ?>
                                            </td>
                                            <td class="py-3 px-3 font-mono text-teal-700">
                                                <?= htmlspecialchars($sess['whatsapp']) ?>
                                            </td>
                                            <td class="py-3 px-3 text-center whitespace-nowrap">
                                                <span class="bg-blue-50 text-blue-700 font-extrabold px-2 py-0.5 rounded-full border border-blue-200 text-[10px]">
                                                    <?= (int)$sess['total_pesan'] ?> pesan
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-right whitespace-nowrap space-x-2">
                                                <button onclick="bukaRiwayatSesi('<?= $sess['session_token'] ?>', '<?= htmlspecialchars(addslashes($sess['nama'])) ?>')" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                                    <i class="fas fa-eye"></i> Baca Chat
                                                </button>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $sess['whatsapp']) ?>?text=Assalamu%27alaikum%20Ayah%2FBunda%20<?= urlencode($sess['nama']) ?>%2C%20kami%20dari%20Panitia%20SPMB%20Villa%20Quran%20Baron%20Malang..." target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition inline-flex items-center gap-1 shadow-2xs">
                                                    <i class="fab fa-whatsapp"></i> Follow-up WA
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ========================================================================= -->
            <!-- TAB 4: UJI COBA CHAT (SIMULATOR) & KODE WIDGET -->
            <!-- ========================================================================= -->
            <div id="tab-simulator" class="tab-content hidden space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- KIRI: KODE PASANG WIDGET -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 space-y-4">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="font-black text-slate-800 text-base flex items-center gap-2">
                                <i class="fas fa-code text-teal-600"></i>
                                Cara Memasang Widget Hasna di Website
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Cukup tempelkan baris script berikut sebelum tag penutup <code>&lt;/body&gt;</code> di halaman web publik Bos.
                            </p>
                        </div>

                        <div class="relative">
                            <pre id="widget-code" class="bg-slate-900 text-teal-300 p-4 rounded-xl text-xs font-mono overflow-x-auto leading-relaxed">&lt;!-- CS AI Hasna Virtual Assistant Widget --&gt;
&lt;script src="cs-hasna-widget.js"&gt;&lt;/script&gt;</pre>
                            <button onclick="salinKodeWidget()" class="absolute top-2 right-2 bg-slate-800 hover:bg-slate-700 text-white text-xs px-3 py-1.5 rounded-lg border border-slate-700 transition">
                                <i class="fas fa-copy"></i> Salin Kode
                            </button>
                        </div>

                        <div class="p-4 rounded-xl bg-teal-50 border border-teal-200 text-xs text-teal-900 space-y-2">
                            <h4 class="font-bold flex items-center gap-1.5">
                                <i class="fas fa-check-circle text-teal-600"></i> Widget Sudah Otomatis Terhubung ke:
                            </h4>
                            <ul class="list-disc list-inside space-y-1 text-teal-800">
                                <li>Form Nama & WhatsApp di awal percakapan</li>
                                <li>Rotasi Multi-API Free Tier otomatis</li>
                                <li>Penyimpanan ke Tabel Leads & Pipeline SPMB</li>
                                <li>Notifikasi WhatsApp Fonnte instan ke nomor Yayasan</li>
                            </ul>
                        </div>
                    </div>

                    <!-- KANAN: LIVE SIMULATOR DI DALAM ADMIN -->
                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col h-[520px]">
                        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">🧕</span>
                                <div>
                                    <h4 class="font-black text-slate-800 text-sm">Simulator Live Chat Hasna</h4>
                                    <p class="text-[11px] text-slate-400">Coba tanyakan seputar SPMB atau biaya</p>
                                </div>
                            </div>
                            <button onclick="resetSimulator()" class="text-xs text-slate-500 hover:text-slate-800">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>

                        <!-- Chat Messages Container -->
                        <div id="sim-chat-box" class="flex-1 overflow-y-auto p-3 space-y-3 bg-slate-50 rounded-xl my-3 text-xs">
                            <div class="bg-white p-3 rounded-xl border border-slate-200 text-slate-800 max-w-[85%] shadow-2xs">
                                Assalamu'alaikum Ayah/Bunda! Saya Hasna, siap membantu menjawab pertanyaan seputar SPMB Villa Quran Baron Malang. Silakan coba tanyakan sesuatu!
                            </div>
                        </div>

                        <!-- Chat Input Container -->
                        <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
                            <input type="text" id="sim-input" placeholder="Ketik pertanyaan untuk Hasna..." class="flex-1 text-xs px-3.5 py-2.5 border border-slate-300 rounded-xl focus:outline-none focus:border-teal-600" onkeydown="if(event.key==='Enter') kirimSimulasiChat()">
                            <button onclick="kirimSimulasiChat()" id="sim-btn-send" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                                <i class="fas fa-paper-plane"></i> Kirim
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- MODAL BACA RIWAYAT PERCAKAPAN -->
    <div id="modal-chat-history" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[85vh] flex flex-col overflow-hidden border border-slate-200">
            <div class="p-4 bg-teal-800 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-extrabold text-sm" id="modal-chat-title">Riwayat Percakapan</h3>
                    <p class="text-[11px] text-teal-200">Transkrip lengkap tanya-jawab dengan Hasna</p>
                </div>
                <button onclick="tutupModalChat()" class="text-teal-200 hover:text-white p-1 rounded-lg">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div id="modal-chat-body" class="p-4 overflow-y-auto space-y-3 bg-slate-50 flex-1 text-xs">
                Memuat percakapan...
            </div>
            <div class="p-3 bg-white border-t border-slate-100 flex justify-end">
                <button onclick="tutupModalChat()" class="bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold px-4 py-2 rounded-xl">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Tab Switcher
        function switchTab(tabId) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('text-teal-700', 'border-teal-600', 'font-extrabold');
                btn.classList.add('text-slate-500', 'border-transparent', 'font-bold');
            });

            document.getElementById(tabId).classList.remove('hidden');
            const activeBtn = document.getElementById('btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.remove('text-slate-500', 'border-transparent', 'font-bold');
                activeBtn.classList.add('text-teal-700', 'border-teal-600', 'font-extrabold');
            }
        }

        // Test API Key
        function testApiKey(num) {
            const statusEl = document.getElementById('status-key-' + num);
            const keyVal = document.getElementById('api_key_' + num).value.trim();

            statusEl.innerHTML = '<span class="text-teal-600"><i class="fas fa-spinner fa-spin"></i> Menguji koneksi...</span>';

            fetch('api-cs-hasna.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'test_api',
                    key_num: num,
                    api_key: keyVal
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    statusEl.innerHTML = '<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle"></i> ' + data.message + '</span>';
                } else {
                    statusEl.innerHTML = '<span class="text-red-500 font-bold"><i class="fas fa-exclamation-triangle"></i> ' + data.message + '</span>';
                }
            })
            .catch(err => {
                statusEl.innerHTML = '<span class="text-red-500 font-bold"><i class="fas fa-times-circle"></i> Kendala koneksi jaringan.</span>';
            });
        }

        // Save CS Config
        document.getElementById('form-cs-config').addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-save-cfg');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            const formData = new FormData(this);

            fetch('api-cs-hasna.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Simpan Konfigurasi Otak Hasna';
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
                    });
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save"></i> Simpan Konfigurasi Otak Hasna';
                Swal.fire({
                    icon: 'error',
                    title: 'Kesalahan Jaringan',
                    text: 'Tidak dapat menyimpan konfigurasi ke server.'
                });
            });
        });

        // Add Knowledge (Learning Machine)
        document.getElementById('form-add-knowledge').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('api-cs-hasna.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Alhamdulillah!',
                        text: data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message
                    });
                }
            });
        });

        // Hapus Knowledge
        function hapusKnowledge(id) {
            Swal.fire({
                title: 'Hapus Pengetahuan Ini?',
                text: 'Hasna tidak akan lagi mengingat tanya-jawab ini.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api-cs-hasna.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ action: 'delete_knowledge', id: id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        location.reload();
                    });
                }
            });
        }

        // Buka Riwayat Sesi
        function bukaRiwayatSesi(token, nama) {
            const modal = document.getElementById('modal-chat-history');
            const title = document.getElementById('modal-chat-title');
            const body = document.getElementById('modal-chat-body');

            title.textContent = 'Riwayat Chat: ' + nama;
            body.innerHTML = '<div class="text-center py-6 text-slate-400"><i class="fas fa-spinner fa-spin text-xl"></i> Memuat percakapan...</div>';
            modal.classList.remove('hidden');

            fetch('api-cs-hasna.php?action=get_history&session_token=' + encodeURIComponent(token))
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.messages) {
                    body.innerHTML = '';
                    data.messages.forEach(m => {
                        const isUser = (m.sender === 'user');
                        const div = document.createElement('div');
                        div.className = isUser ? 'text-right' : 'text-left';
                        div.innerHTML = `
                            <div class="inline-block p-3 rounded-xl max-w-[85%] text-left ${isUser ? 'bg-teal-600 text-white' : 'bg-white text-slate-800 border border-slate-200'}">
                                <div class="font-extrabold text-[10px] mb-1 ${isUser ? 'text-teal-200' : 'text-slate-500'}">${isUser ? nama : '🧕 Hasna (CS AI)'}</div>
                                <div>${m.pesan.replace(/\\n/g, '<br>')}</div>
                                <div class="text-[9px] mt-1 opacity-70 text-right">${m.waktu}</div>
                            </div>
                        `;
                        body.appendChild(div);
                    });
                } else {
                    body.innerHTML = '<div class="text-center py-6 text-slate-400">Belum ada riwayat pesan.</div>';
                }
            });
        }

        function tutupModalChat() {
            document.getElementById('modal-chat-history').classList.add('hidden');
        }

        // Salin Kode Widget
        function salinKodeWidget() {
            const text = document.getElementById('widget-code').innerText;
            navigator.clipboard.writeText(text).then(() => {
                Swal.fire({
                    icon: 'success',
                    title: 'Tersalin!',
                    text: 'Kode widget telah disalin ke clipboard.',
                    timer: 1500,
                    showConfirmButton: false
                });
            });
        }

        // Live Simulator
        let simSessionToken = null;
        function kirimSimulasiChat() {
            const input = document.getElementById('sim-input');
            const q = input.value.trim();
            if (!q) return;

            const chatBox = document.getElementById('sim-chat-box');
            input.value = '';

            // User Message
            const userMsg = document.createElement('div');
            userMsg.className = 'text-right';
            userMsg.innerHTML = `<div class="inline-block p-3 rounded-xl max-w-[85%] text-left bg-teal-600 text-white">${q}</div>`;
            chatBox.appendChild(userMsg);
            chatBox.scrollTop = chatBox.scrollHeight;

            // Typing indicator
            const typing = document.createElement('div');
            typing.id = 'sim-typing';
            typing.className = 'text-left text-slate-400 text-xs italic';
            typing.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Hasna sedang berpikir...';
            chatBox.appendChild(typing);
            chatBox.scrollTop = chatBox.scrollHeight;

            // Start session jika belum ada
            if (!simSessionToken) {
                fetch('api-cs-hasna.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'start_session',
                        nama: 'Admin (Simulasi)',
                        whatsapp: '08123456789'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    simSessionToken = data.session_token;
                    kirimPesanSimulasiKeBackend(q);
                });
            } else {
                kirimPesanSimulasiKeBackend(q);
            }
        }

        function kirimPesanSimulasiKeBackend(pesan) {
            const chatBox = document.getElementById('sim-chat-box');
            fetch('api-cs-hasna.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'send_message',
                    session_token: simSessionToken,
                    pesan: pesan
                })
            })
            .then(res => res.json())
            .then(data => {
                const typing = document.getElementById('sim-typing');
                if (typing) typing.remove();

                const botMsg = document.createElement('div');
                botMsg.className = 'text-left';
                botMsg.innerHTML = `
                    <div class="inline-block p-3 rounded-xl max-w-[85%] text-left bg-white text-slate-800 border border-slate-200 shadow-2xs">
                        <div class="font-extrabold text-[10px] text-teal-700 mb-1">🧕 Hasna [Otak: ${data.api_used || 'Gemini'}]</div>
                        <div>${data.jawaban.replace(/\\n/g, '<br>')}</div>
                    </div>
                `;
                chatBox.appendChild(botMsg);
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(err => {
                const typing = document.getElementById('sim-typing');
                if (typing) typing.remove();
            });
        }

        function resetSimulator() {
            simSessionToken = null;
            const chatBox = document.getElementById('sim-chat-box');
            chatBox.innerHTML = `
                <div class="bg-white p-3 rounded-xl border border-slate-200 text-slate-800 max-w-[85%] shadow-2xs">
                    Assalamu'alaikum Ayah/Bunda! Saya Hasna, siap membantu menjawab pertanyaan seputar SPMB Villa Quran Baron Malang. Silakan coba tanyakan sesuatu!
                </div>
            `;
        }

        // Template Default
        function loadDefaultPromptTemplate() {
            document.getElementById('system_prompt_spmb').value = `Anda adalah 'Hasna', Customer Service Virtual resmi dari Pesantren Villa Quran Baron Malang (SMP & SMA Tahfidz Qur'an Berasrama).

KARAKTER & SIKAP ANDA:
1. Sopan, ramah, bernuansa Islami yang teduh, dan selalu menyapa Ayah/Bunda dengan santun (misal: 'Ahlan wa sahlan Ayah/Bunda...').
2. Jawaban Anda harus SINGKAT, PADAT, JELAS, dan MUDAH DIPAHAMI. Hindari kalimat yang berbelit-belit atau terlalu panjang.
3. Gunakan formatting bullet points atau baris rapi bila memberikan rincian.
4. Di akhir jawaban, tawarkan bantuan berikutnya atau sarankan untuk konsultasi langsung / survei lokasi via WhatsApp ke Panitia SPMB.

INFORMASI DASAR PESANTREN & SPMB:
- Nama Pesantren: Villa Quran Indonesia (Kampus Putra & Putri)
- Lokasi: Baron, Malang, Jawa Timur (lingkungan sejuk, tenang, asri pegunungan, sangat kondusif untuk menghafal Al-Qur'an).
- Jenjang Pendidikan: SMP & SMA Tahfidz Al-Qur'an (Full Boarding / Asrama).
- Kurikulum: Integrasi Kurikulum Merdeka (Kemendikbudristek) + Kurikulum Kepesantrenan + Target Mutqin Tahfidz 30 Juz + Bahasa Arab & Inggris + Adab & Karakter Akhlakul Karimah.
- Fasilitas: Asrama bersih & representatif, masjid utama, ruang kelas modern ber-AC, makan bergizi 3x sehari, laundry, lapangan olahraga (panahan, futsal, dll), gazebo belajar alam.
- Estimasi Biaya SPMB 2026/2027:
  * Biaya Formulir Pendaftaran: Rp 350.000
  * Infaq Pengembangan / Uang Pangkal: Mulai Rp 12.500.000 (tersedia skema beasiswa/keringanan gelombang awal)
  * SPP Bulanan: Mulai Rp 1.650.000 (sudah meliputi asrama, makan 3x sehari, pendidikan, dan pembinaan musyrif 24 jam).
- Alur Pendaftaran: 1) Isi form online di website/SPMB, 2) Pembayaran formulir, 3) Tes Kesiapan & Observasi Wawancara, 4) Pengumuman, 5) Daftar Ulang.
- Narahubung Panitia SPMB: 0851-8991-8115.`;
        }
    </script>
</body>
</html>
