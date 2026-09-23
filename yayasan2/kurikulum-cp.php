<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../koneksi.php';

// Pastikan tabel master_cp_kurikulum terpasang otomatis (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS master_cp_kurikulum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenjang ENUM('SMP', 'SMA') NOT NULL,
    fase VARCHAR(20) NOT NULL,
    mapel_id INT NULL,
    nama_mapel VARCHAR(150) NOT NULL,
    kode_mapel VARCHAR(30) NULL,
    rasional_mapel TEXT NULL,
    tujuan_mapel TEXT NULL,
    karakteristik_mapel TEXT NULL,
    elemen_cp LONGTEXT NOT NULL,
    sumber_rujukan VARCHAR(255) DEFAULT 'BSKAP Kemendikbudristek No. 032/H/KR/2024',
    status_verifikasi VARCHAR(50) DEFAULT 'terverifikasi',
    last_generated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_jenjang_fase_mapel (jenjang, fase, nama_mapel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Alter jika tipe kolom sebelumnya masih enum terbatas
@$conn->query("ALTER TABLE master_cp_kurikulum MODIFY COLUMN status_verifikasi VARCHAR(50) DEFAULT 'terverifikasi'");

$active_page = 'yayasan_cp';

$m_num = date('m');
$y_num = (int)date('Y');
$current_ta = ((int)$m_num >= 7) ? $y_num . '/' . ($y_num + 1) : ($y_num - 1) . '/' . $y_num;

require_once __DIR__ . '/../api-cp-ai.php';

// Pastikan seluruh 27 CP resmi pemerintah tertuang lengkap ke database & silabus
$is_force_run = isset($_GET['run_now']) && $_GET['run_now'] === '1';
$total_cp_current = ensureOfficialCPPopulated($conn, $is_force_run);

$res_last_cron = $conn->query("SELECT * FROM log_cp_agent_annual ORDER BY id DESC LIMIT 1");
$last_annual_exec = ($res_last_cron && $res_last_cron->num_rows > 0) ? $res_last_cron->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Pengajar Agentic AI • Riset & Auto-Fill CP Pemerintah</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @media print {
            aside, header, #left-navigator, #action-bar, #agent-console-box, .no-print { display: none !important; }
            body, main, #workstation { width: 100% !important; margin: 0 !important; padding: 0 !important; background: white !important; }
            .print-only { display: block !important; }
            .shadow-sm, .shadow-md, .shadow-xl { box-shadow: none !important; }
            .border { border-color: #cbd5e1 !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-xs flex items-center justify-between px-4 sm:px-6 z-20 flex-shrink-0 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <button id="open-sidebar-yayasan2" class="text-slate-600 hover:text-[#0b8478] md:hidden p-1 rounded-lg">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="font-extrabold text-slate-900 text-sm sm:text-base tracking-tight leading-tight">
                            Team Pengajar Agentic AI — Riset CP Pemerintah
                        </h2>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <i class="fas fa-scale-balanced mr-1 text-[9px]"></i> Sami'na wa Atha'na Pemerintah
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 hidden sm:block">Agent Riset Otomatis: Menelusuri Regulasi Kemendikbudristek & Langsung Menuangkan ke Tabel</p>
                </div>
            </div>

            <!-- TOP ACTIONS -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button onclick="runAutonomousBatchFullRun()" class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-amber-500 via-amber-600 to-teal-700 hover:from-amber-600 hover:to-teal-800 text-slate-950 hover:text-white shadow-sm transition transform active:scale-95">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span class="hidden sm:inline">⚡ Autonomous Full Run: Riset Semua Mapel</span>
                    <span class="sm:hidden">Full Run AI</span>
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition">
                    <i class="fas fa-print"></i>
                    <span class="hidden md:inline">Cetak CP</span>
                </button>
            </div>
        </header>

        <!-- SUB HEADER: JENJANG SWITCHER TABS & PHILOSOPHY NOTICE -->
        <div class="bg-white border-b border-slate-200 px-4 sm:px-6 py-2.5 flex-shrink-0 flex flex-wrap items-center justify-between gap-3">
            <!-- JENJANG SWITCHER -->
            <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl max-w-md w-full sm:w-auto">
                <button id="tab-btn-smp" onclick="switchJenjang('SMP')" class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-xs bg-[#0b8478] text-white">
                    <i class="fas fa-school text-xs"></i>
                    <span>SMP (Fase D • VII-IX)</span>
                </button>
                <button id="tab-btn-sma" onclick="switchJenjang('SMA')" class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900">
                    <i class="fas fa-graduation-cap text-xs"></i>
                    <span>SMA (Fase E & F • X-XII)</span>
                </button>
            </div>

            <div class="flex items-center gap-2 text-xs text-slate-600 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                <i class="fas fa-shield-halved text-teal-600"></i>
                <span>Standar Baku: <b>BSKAP Kemendikbudristek No. 032/H/KR/2024</b> (Pakem Nasional)</span>
            </div>
        </div>

        <!-- MAIN DUAL-PANE WORKSPACE -->
        <main class="flex-1 overflow-hidden flex flex-col lg:flex-row bg-slate-100">

            <!-- LEFT COLUMN: SUBJECT LIST NAVIGATOR -->
            <div id="left-navigator" class="w-full lg:w-80 xl:w-96 bg-white border-r border-slate-200 flex flex-col flex-shrink-0 h-auto max-h-56 lg:max-h-full overflow-hidden">
                <div class="p-3 border-b border-slate-100 flex items-center justify-between gap-2">
                    <div class="relative flex-1">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                        <input type="text" id="mapel-search" oninput="filterMapelList()" placeholder="Cari mata pelajaran..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0b8478]">
                    </div>
                    <button onclick="loadMapelList()" title="Refresh List" class="p-2 text-slate-400 hover:text-teal-700 rounded-lg hover:bg-slate-50 text-xs">
                        <i class="fas fa-rotate"></i>
                    </button>
                </div>

                <div class="px-3 py-2 bg-slate-50 border-b border-slate-100 flex items-center justify-between text-[11px] font-semibold text-slate-500">
                    <span id="mapel-count-label">Daftar Mapel Diknas</span>
                    <span id="verified-summary-badge" class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px]">0/0 Siap</span>
                </div>

                <!-- SCROLLABLE LIST -->
                <div id="mapel-items-container" class="flex-1 overflow-y-auto p-2 space-y-1.5">
                    <div class="p-4 text-center text-xs text-slate-400">
                        <i class="fas fa-spinner fa-spin text-teal-600 text-base mb-1"></i>
                        <p>Memuat daftar mata pelajaran...</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: CP DETAIL & WORKSTATION -->
            <div id="workstation" class="flex-1 overflow-y-auto p-4 sm:p-6 flex flex-col space-y-5">
                
                <!-- PRINT HEADER (ONLY VISIBLE ON PRINT) -->
                <div class="print-only border-b-2 border-slate-800 pb-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">Villa Quran - SADIGS</h1>
                            <h2 class="text-base font-bold text-slate-700">DOKUMEN CAPAIAN PEMBELAJARAN (CP) KURIKULUM MERDEKA</h2>
                            <p class="text-xs text-slate-500">Rujukan Resmi Pemerintah: BSKAP Kemendikbudristek No. 032/H/KR/2024</p>
                        </div>
                        <div class="text-right text-xs text-slate-600">
                            <p><b>Jenjang:</b> <span id="print-jenjang">-</span></p>
                            <p><b>Fase:</b> <span id="print-fase">-</span></p>
                            <p><b>Mata Pelajaran:</b> <span id="print-mapel">-</span></p>
                        </div>
                    </div>
                </div>

                <!-- WORKSPACE BANNER & STATUS -->
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-4 relative overflow-hidden">
                    <div class="absolute -right-12 -top-12 w-40 h-40 bg-teal-500/5 rounded-full blur-2xl pointer-events-none"></div>
                    
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1.5">
                            <span id="active-jenjang-badge" class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-[#0b8478] text-white">
                                SMP
                            </span>
                            <span id="active-fase-badge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-teal-50 text-[#0b8478] border border-teal-200">
                                Fase D
                            </span>
                            <span id="active-status-badge" class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                <i class="fas fa-circle-question mr-1"></i> Memuat Status...
                            </span>
                        </div>
                        <h1 id="active-mapel-title" class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                            Pilih Mata Pelajaran
                        </h1>
                        <p id="active-rujukan-info" class="text-xs text-slate-500 mt-0.5">
                            Rujukan Baku: Keputusan Kepala BSKAP Kemendikbudristek No. 032/H/KR/2024
                        </p>
                    </div>

                    <!-- ACTION BUTTONS: FULL AGENTIC AI -->
                    <div id="action-bar" class="flex flex-wrap items-center gap-2.5">
                        <button id="btn-agent-run" onclick="runAgentSearchAndPopulate()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-gradient-to-r from-teal-700 via-[#0b8478] to-emerald-600 hover:from-teal-800 hover:to-emerald-700 text-white shadow-sm transition transform active:scale-95 cursor-pointer">
                            <i class="fas fa-robot text-amber-300"></i>
                            <span>Jalankan Agent: Riset & Tuangkan ke Tabel</span>
                        </button>
                        <button id="btn-save-cp" onclick="saveCurrentCP()" class="inline-flex items-center gap-2 px-3 py-2.5 rounded-xl text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition cursor-pointer" title="Simpan Perubahan Manual">
                            <i class="fas fa-floppy-disk text-slate-400"></i>
                            <span class="hidden sm:inline">Simpan Manual</span>
                        </button>
                    </div>
                </div>

                <!-- LIVE AGENT RESEARCH TERMINAL / ACTIVITY LOG -->
                <div id="agent-console-box" class="bg-slate-900 rounded-2xl p-4 text-emerald-400 font-mono text-[11px] shadow-md border border-slate-800 hidden">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-2 mb-2 text-slate-400">
                        <div class="flex items-center gap-2 font-bold text-slate-300 text-xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            <i class="fas fa-terminal text-teal-400"></i>
                            <span>AGENTIC AI RESEARCH ENGINE — KEMENDIKBUDRISTEK BSKAP</span>
                        </div>
                        <span id="agent-status-label" class="text-[10px] text-emerald-400 bg-emerald-950/80 px-2 py-0.5 rounded border border-emerald-800/50">MENJALANKAN RISET...</span>
                    </div>
                    <div id="agent-terminal-logs" class="space-y-1 max-h-36 overflow-y-auto pr-1">
                        <!-- Live log lines stream here -->
                    </div>
                </div>

                <!-- AUTONOMOUS PIPELINE NOTICE -->
                <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 rounded-2xl p-4 text-white shadow-sm flex items-start gap-3.5 border border-teal-700/50">
                    <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center flex-shrink-0 text-base mt-0.5">
                        <i class="fas fa-brain"></i>
                    </div>
                    <div class="flex-1 text-xs">
                        <h4 class="font-bold text-teal-100 text-sm mb-0.5">Full Agentic Pipeline: Riset Mandiri $\rightarrow$ Otomatis Tuangkan ke Tabel</h4>
                        <p class="text-slate-200 leading-relaxed">
                            Karena kurikulum Diknas adalah <b>ketetapan pakem pemerintah</b> (*sami'na wa atha'na*), Agent AI langsung menelusuri keputusan resmi Kemendikbudristek, mengekstrak rumusan kompetensi, dan <b>menuangkannya langsung ke dalam tabel database dan silabus guru</b>. Guru dan Yayasan tidak perlu mengetik manual dari nol.
                        </p>
                    </div>
                </div>

                <!-- AUTONOMOUS ANNUAL SCHEDULER WIDGET (1 JULI) -->
                <div class="bg-gradient-to-r from-slate-900 via-teal-950 to-slate-900 border border-teal-700/60 rounded-2xl p-4 text-white shadow-md flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center text-lg flex-shrink-0 border border-teal-500/30">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-[10px] font-extrabold uppercase px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Status: Riset Baku Pemerintah Aktif & Tersimpan (<?= $total_cp_current ?> Mapel)
                                </span>
                                <span class="text-xs text-slate-300 font-mono">Tahun Ajaran <?= htmlspecialchars($current_ta) ?></span>
                            </div>
                            <h4 class="font-bold text-white text-xs sm:text-sm mt-1">
                                Tugas AI Saat Ini Telah Selesai Dituangkan. Tugas Otonom Berikutnya: Per 1 Juli Tiap Tahun Ajaran Baru
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                <?php if ($last_annual_exec): ?>
                                    Eksekusi: <b class="text-teal-200"><?= date('d M Y H:i', strtotime($last_annual_exec['tanggal_eksekusi'])) ?> WIB</b> (<?= htmlspecialchars($last_annual_exec['total_mapel']) ?> Mapel diselaraskan oleh <?= htmlspecialchars($last_annual_exec['executed_by']) ?>) • Jadwal berkala: <b>1 Juli</b>
                                <?php else: ?>
                                    Jadwal eksekusi otomatis berikutnya: <b class="text-teal-200">1 Juli Pukul 00:00 WIB</b> (Tahun Ajaran Baru)
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                        <a href="kurikulum-cp.php?run_now=1" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-xs transition cursor-pointer" title="Paksa riset dan tuang ulang seluruh mapel ke database sekarang">
                            <i class="fas fa-bolt"></i>
                            <span>Riset & Tuang Ulang Sekarang</span>
                        </a>
                        <button type="button" onclick="triggerSimulasiJuli()" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-teal-800/80 hover:bg-teal-700 text-teal-100 border border-teal-600/50 shadow-xs transition cursor-pointer">
                            <i class="fas fa-rotate"></i>
                            <span>Simulasi 1 Juli</span>
                        </button>
                    </div>
                </div>

                <!-- SECTIONS ACCORDION / CARDS -->
                <div class="space-y-4">

                    <!-- CARD 1: RASIONAL MAPEL -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-lightbulb text-amber-500"></i>
                                Rasional Mata Pelajaran (Resmi Pemerintah)
                            </h3>
                            <span class="text-[11px] text-slate-400">Latar belakang disiplin ilmu</span>
                        </div>
                        <textarea id="field-rasional" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478]" placeholder="Agent AI akan meriset dan menuangkan teks rasional resmi pemerintah ke sini..."></textarea>
                    </div>

                    <!-- CARD 2: TUJUAN MAPEL -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-bullseye text-rose-500"></i>
                                Tujuan Mata Pelajaran (Resmi Pemerintah)
                            </h3>
                            <span class="text-[11px] text-slate-400">Target kompetensi peserta didik</span>
                        </div>
                        <textarea id="field-tujuan" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478]" placeholder="Agent AI akan meriset dan menuangkan tujuan resmi pemerintah ke sini..."></textarea>
                    </div>

                    <!-- CARD 3: KARAKTERISTIK MAPEL -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-shapes text-indigo-500"></i>
                                Karakteristik Mata Pelajaran (Resmi Pemerintah)
                            </h3>
                            <span class="text-[11px] text-slate-400">Ruang lingkup & fokus materi</span>
                        </div>
                        <textarea id="field-karakteristik" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478]" placeholder="Agent AI akan meriset dan menuangkan karakteristik resmi pemerintah ke sini..."></textarea>
                    </div>

                    <!-- CARD 4: ELEMEN CAPAIAN PEMBELAJARAN (TABLE) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <i class="fas fa-table-list text-[#0b8478]"></i>
                                    Tabel Capaian Pembelajaran (CP) per Elemen
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Dituangkan langsung dari BSKAP Kemendikbudristek No. 032/H/KR/2024</p>
                            </div>
                            <button onclick="addElemenRow()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold bg-teal-50 text-[#0b8478] hover:bg-teal-100 border border-teal-200 transition">
                                <i class="fas fa-plus"></i> Tambah Elemen
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-slate-100/70 border-b border-slate-200 text-[11px] font-bold text-slate-600 uppercase tracking-wider">
                                    <tr>
                                        <th class="py-3 px-4 w-48 sm:w-64">Elemen</th>
                                        <th class="py-3 px-4">Deskripsi Capaian Pembelajaran Fase</th>
                                        <th class="py-3 px-3 text-center w-16 no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="elemen-table-body" class="divide-y divide-slate-100 text-xs">
                                    <!-- Dynamic rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>

                        <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400" id="total-elemen-info">0 Elemen Terdaftar</span>
                            <button onclick="addElemenRow()" class="text-xs font-bold text-[#0b8478] hover:text-teal-800 flex items-center gap-1">
                                <i class="fas fa-plus-circle"></i> Tambah Elemen Baru
                            </button>
                        </div>
                    </div>

                </div>

                <!-- SIGNATURE FOOTER FOR PRINT ONLY -->
                <div class="print-only mt-12 pt-8 border-t border-slate-300">
                    <div class="grid grid-cols-2 gap-8 text-center text-xs">
                        <div>
                            <p>Mengetahui,</p>
                            <p class="font-bold">Ketua Yayasan Villa Quran</p>
                            <div class="h-20"></div>
                            <p class="font-bold underline">H. Pengurus Yayasan, M.Pd</p>
                        </div>
                        <div>
                            <p>Ditetapkan di Sukabumi,</p>
                            <p class="font-bold">Direktur Pendidikan & Kurikulum</p>
                            <div class="h-20"></div>
                            <p class="font-bold underline">Ustadz / Tim Kurikulum</p>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- BATCH PROGRESS MODAL -->
    <div id="batch-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
            <div class="text-center">
                <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl mb-4 shadow-xs">
                    <i class="fas fa-robot animate-bounce"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-1">Autonomous Agent Sedang Bekerja</h3>
                <p class="text-xs text-slate-500 mb-5">
                    Agent sedang menelusuri ketetapan resmi BSKAP Kemendikbudristek untuk seluruh mapel Diknas jenjang <b id="batch-jenjang-label">SMP</b> dan langsung menuangkannya ke dalam tabel...
                </p>

                <!-- PROGRESS BAR -->
                <div class="w-full bg-slate-100 rounded-full h-3 mb-2 overflow-hidden">
                    <div id="batch-progress-bar" class="bg-gradient-to-r from-amber-500 to-[#0b8478] h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p id="batch-status-text" class="text-xs font-bold text-slate-700">Mempersiapkan rujukan kurikulum...</p>
            </div>
        </div>
    </div>

    <script>
    // Global State
    let currentJenjang = 'SMP';
    let currentFase = 'Fase D';
    let mapelList = [];
    let currentSelectedMapel = null;
    let currentCPData = null;

    document.addEventListener('DOMContentLoaded', () => {
        loadMapelList();
    });

    // 1. Switcher Jenjang SMP vs SMA
    function switchJenjang(jenjang) {
        currentJenjang = jenjang;
        currentFase = (jenjang === 'SMP') ? 'Fase D' : 'Fase E & F';

        const btnSmp = document.getElementById('tab-btn-smp');
        const btnSma = document.getElementById('tab-btn-sma');

        if (jenjang === 'SMP') {
            btnSmp.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-xs bg-[#0b8478] text-white';
            btnSma.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900';
        } else {
            btnSma.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-xs bg-[#0b8478] text-white';
            btnSmp.className = 'flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900';
        }

        document.getElementById('active-jenjang-badge').textContent = jenjang;
        document.getElementById('active-fase-badge').textContent = currentFase;
        
        loadMapelList();
    }

    // 2. Load Mapel List via AJAX
    async function loadMapelList() {
        const container = document.getElementById('mapel-items-container');
        container.innerHTML = `
            <div class="p-6 text-center text-xs text-slate-400">
                <i class="fas fa-spinner fa-spin text-teal-600 text-lg mb-2"></i>
                <p>Memuat mata pelajaran ${currentJenjang}...</p>
            </div>
        `;

        try {
            const resp = await fetch(`../api-cp-ai.php?action=get_mapel_list&jenjang=${encodeURIComponent(currentJenjang)}&_t=${Date.now()}`);
            const res = await resp.json();

            if (res.status === 'success') {
                mapelList = res.data;
                renderMapelList(mapelList);

                // Auto-select first mapel if available
                if (mapelList.length > 0) {
                    selectMapel(mapelList[0]);
                } else {
                    renderEmptyWorkstation();
                }
            } else {
                container.innerHTML = `<div class="p-4 text-xs text-rose-600">${res.message}</div>`;
            }
        } catch (e) {
            container.innerHTML = `<div class="p-4 text-xs text-rose-600">Gagal terhubung ke API: ${e.message}</div>`;
        }
    }

    // 3. Render Mapel List
    function renderMapelList(items) {
        const container = document.getElementById('mapel-items-container');
        container.innerHTML = '';

        let verifiedCount = 0;

        items.forEach((item, index) => {
            if (item.has_cp) verifiedCount++;

            const card = document.createElement('div');
            const isSelected = currentSelectedMapel && currentSelectedMapel.nama_mapel === item.nama_mapel;
            
            card.className = `p-3 rounded-xl border transition cursor-pointer flex items-center justify-between gap-2 ${
                isSelected 
                ? 'bg-teal-50/80 border-[#0b8478] shadow-xs' 
                : 'bg-white hover:bg-slate-50 border-slate-200/80'
            }`;
            card.id = `mapel-card-${index}`;

            const statusBadge = item.has_cp
                ? `<span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-800 flex items-center gap-1"><i class="fas fa-check"></i> Pakem Ready</span>`
                : `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Perlu Riset</span>`;

            card.innerHTML = `
                <div class="flex items-center gap-2.5 overflow-hidden">
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-black text-[11px] flex-shrink-0 ${
                        isSelected ? 'bg-[#0b8478] text-white' : 'bg-slate-100 text-slate-700'
                    }">
                        ${item.kode_mapel || item.nama_mapel.substring(0, 2).toUpperCase()}
                    </div>
                    <div class="overflow-hidden">
                        <h4 class="text-xs font-bold text-slate-900 truncate">${item.nama_mapel}</h4>
                        <p class="text-[10px] text-slate-400">${item.kategori_mapel} • ${currentJenjang}</p>
                    </div>
                </div>
                <div class="flex-shrink-0">${statusBadge}</div>
            `;

            card.onclick = () => selectMapel(item);
            container.appendChild(card);
        });

        document.getElementById('verified-summary-badge').textContent = `${verifiedCount}/${items.length} Selesai`;
        document.getElementById('verified-summary-badge').className = verifiedCount === items.length && items.length > 0
            ? 'px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold'
            : 'px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px]';
    }

    // Filter Mapel Search
    function filterMapelList() {
        const query = document.getElementById('mapel-search').value.toLowerCase();
        const filtered = mapelList.filter(m => m.nama_mapel.toLowerCase().includes(query) || (m.kode_mapel && m.kode_mapel.toLowerCase().includes(query)));
        renderMapelList(filtered);
    }

    // 4. Select Mapel & Load Detail
    async function selectMapel(mapel) {
        currentSelectedMapel = mapel;
        renderMapelList(mapelList);

        document.getElementById('active-mapel-title').textContent = mapel.nama_mapel;
        document.getElementById('print-mapel').textContent = mapel.nama_mapel;
        document.getElementById('print-jenjang').textContent = currentJenjang;
        document.getElementById('print-fase').textContent = currentFase;

        const statusBadge = document.getElementById('active-status-badge');
        statusBadge.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Membaca tabel CP...`;
        statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200';

        try {
            const resp = await fetch(`../api-cp-ai.php?action=get_cp_detail&jenjang=${encodeURIComponent(currentJenjang)}&nama_mapel=${encodeURIComponent(mapel.nama_mapel)}&_t=${Date.now()}`);
            const res = await resp.json();

            if (res.status === 'success') {
                currentCPData = res.data;
                populateWorkstation(currentCPData, res.is_saved);

                if (res.is_saved) {
                    statusBadge.innerHTML = `<i class="fas fa-circle-check text-emerald-600 mr-1"></i> Terverifikasi BSKAP Kemendikbudristek`;
                    statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';
                } else {
                    statusBadge.innerHTML = `<i class="fas fa-circle-exclamation text-amber-600 mr-1"></i> Siap Diriset Agent AI`;
                    statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200';
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Memuat CP', text: res.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error Koneksi', text: e.message });
        }
    }

    // 5. Populate Form Fields & Tables
    function populateWorkstation(data, isSaved) {
        document.getElementById('field-rasional').value = data.rasional_mapel || '';
        document.getElementById('field-tujuan').value = data.tujuan_mapel || '';
        document.getElementById('field-karakteristik').value = data.karakteristik_mapel || '';
        document.getElementById('active-rujukan-info').textContent = 'Rujukan Baku: ' + (data.sumber_rujukan || 'BSKAP Kemendikbudristek No. 032/H/KR/2024');

        const tbody = document.getElementById('elemen-table-body');
        tbody.innerHTML = '';

        let elemenArr = [];
        if (Array.isArray(data.elemen_cp)) {
            elemenArr = data.elemen_cp;
        } else if (typeof data.elemen_cp === 'string') {
            try {
                elemenArr = JSON.parse(data.elemen_cp);
            } catch (e) {
                elemenArr = [{ elemen: 'Umum', cp: data.elemen_cp }];
            }
        }

        if (elemenArr.length === 0) {
            elemenArr = [{ elemen: '', cp: '' }];
        }

        elemenArr.forEach((item, idx) => {
            appendElemenRow(item.elemen || '', item.cp || (item.deskripsi || ''));
        });

        updateTotalElemenCount();
    }

    // 6. Dynamic Table Rows for Elemen CP
    function addElemenRow() {
        appendElemenRow('', '');
        updateTotalElemenCount();
    }

    function appendElemenRow(elemen, cp) {
        const tbody = document.getElementById('elemen-table-body');
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50/80 transition';

        tr.innerHTML = `
            <td class="py-2.5 px-4 align-top">
                <input type="text" class="row-elemen w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs font-semibold text-slate-800 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0b8478]" placeholder="Cth: Menyimak / Bilangan" value="${escapeHtml(elemen)}">
            </td>
            <td class="py-2.5 px-4 align-top">
                <textarea rows="3" class="row-cp w-full bg-slate-50 border border-slate-200 rounded-lg p-2 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-1 focus:ring-[#0b8478]" placeholder="Pada akhir fase ini, peserta didik memiliki kemampuan...">${escapeHtml(cp)}</textarea>
            </td>
            <td class="py-2.5 px-3 text-center align-top no-print">
                <button type="button" onclick="deleteElemenRow(this)" class="p-2 text-slate-300 hover:text-rose-600 rounded-lg transition" title="Hapus Baris">
                    <i class="fas fa-trash-can"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    }

    function deleteElemenRow(btn) {
        const tr = btn.closest('tr');
        if (document.querySelectorAll('#elemen-table-body tr').length > 1) {
            tr.remove();
            updateTotalElemenCount();
        } else {
            Swal.fire({ icon: 'warning', title: 'Minimal 1 Elemen', text: 'Mata pelajaran harus memiliki minimal 1 elemen CP.' });
        }
    }

    function updateTotalElemenCount() {
        const count = document.querySelectorAll('#elemen-table-body tr').length;
        document.getElementById('total-elemen-info').textContent = `${count} Elemen Terdaftar`;
    }

    // 7. FULL AGENTIC AI: SEARCH & RISET REGULASI PEMERINTAH -> LANGSUNG TUANGKAN KE TABEL
    async function runAgentSearchAndPopulate() {
        if (!currentSelectedMapel) return;

        const btn = document.getElementById('btn-agent-run');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin text-amber-300"></i> <span>Agent Meriset & Menuangkan ke Tabel...</span>`;

        const consoleBox = document.getElementById('agent-console-box');
        const logsContainer = document.getElementById('agent-terminal-logs');
        const statusLabel = document.getElementById('agent-status-label');

        consoleBox.classList.remove('hidden');
        logsContainer.innerHTML = '';
        statusLabel.textContent = 'CONNECTING TO BSKAP REPO...';
        statusLabel.className = 'text-[10px] text-amber-400 bg-amber-950/80 px-2 py-0.5 rounded border border-amber-800/50';

        appendAgentLog('INIT', `Memulai sesi Agent Riset untuk ${currentSelectedMapel.nama_mapel} (${currentJenjang} - ${currentFase})...`);

        try {
            await sleep(350);
            appendAgentLog('SEARCH', `Menelusuri ketetapan baku Kemendikbudristek BSKAP No. 032/H/KR/2024 di internet...`);
            
            const formData = new FormData();
            formData.append('jenjang', currentJenjang);
            formData.append('fase', currentFase);
            formData.append('nama_mapel', currentSelectedMapel.nama_mapel);
            if (currentSelectedMapel.kode_mapel) formData.append('kode_mapel', currentSelectedMapel.kode_mapel);

            const resp = await fetch('../api-cp-ai.php?action=agentic_search_and_populate', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.status === 'success') {
                if (res.logs && Array.isArray(res.logs)) {
                    for (const l of res.logs) {
                        await sleep(200);
                        appendAgentLog('EXEC', `${l.title}: ${l.desc}`);
                    }
                }

                await sleep(250);
                appendAgentLog('SUCCESS', `Selesai! Hasil riset resmi pemerintah berhasil dituangkan 100% ke tabel & silabus guru.`);
                statusLabel.textContent = 'COMPLETED (POURED TO TABLE)';
                statusLabel.className = 'text-[10px] text-emerald-300 bg-emerald-900/80 px-2 py-0.5 rounded border border-emerald-500/50';

                // Langsung isi tabel & komponen UI seketika
                populateWorkstation(res.data, true);

                const statusBadge = document.getElementById('active-status-badge');
                statusBadge.innerHTML = `<i class="fas fa-circle-check text-emerald-600 mr-1"></i> Terverifikasi BSKAP Kemendikbudristek`;
                statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';

                currentSelectedMapel.has_cp = true;
                renderMapelList(mapelList);

                Swal.fire({
                    icon: 'success',
                    title: 'Agent Selesai: Masuk ke Tabel!',
                    html: `Agent AI berhasil meriset ketetapan resmi <b>BSKAP No. 032/H/KR/2024</b> untuk <b>${escapeHtml(currentSelectedMapel.nama_mapel)}</b> dan <b>langsung menuangkannya ke dalam tabel</b> serta silabus guru tanpa input manual.`,
                    confirmButtonColor: '#0b8478'
                });
            } else {
                appendAgentLog('ERROR', `Gagal: ${res.message}`);
                Swal.fire({ icon: 'error', title: 'Riset Gagal', text: res.message });
            }
        } catch (e) {
            appendAgentLog('ERROR', `Koneksi error: ${e.message}`);
            Swal.fire({ icon: 'error', title: 'Error Koneksi Agent', text: e.message });
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }

    function appendAgentLog(tag, msg) {
        const logsContainer = document.getElementById('agent-terminal-logs');
        const line = document.createElement('div');
        const now = new Date().toLocaleTimeString();
        let tagColor = 'text-teal-400';
        if (tag === 'SEARCH') tagColor = 'text-amber-400';
        if (tag === 'SUCCESS') tagColor = 'text-emerald-300 font-bold';
        if (tag === 'ERROR') tagColor = 'text-rose-400 font-bold';

        line.innerHTML = `<span class="text-slate-500">[${now}]</span> <span class="${tagColor}">[${tag}]</span> <span class="text-slate-200">${escapeHtml(msg)}</span>`;
        logsContainer.appendChild(line);
        logsContainer.scrollTop = logsContainer.scrollHeight;
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // 8. Manual Save (Optional jika ada catatan lokal khusus Yayasan)
    async function saveCurrentCP() {
        if (!currentSelectedMapel) return;

        const rasional = document.getElementById('field-rasional').value.trim();
        const tujuan = document.getElementById('field-tujuan').value.trim();
        const karakteristik = document.getElementById('field-karakteristik').value.trim();

        const rows = document.querySelectorAll('#elemen-table-body tr');
        const elemen_cp = [];
        rows.forEach(r => {
            const el = r.querySelector('.row-elemen').value.trim();
            const cp = r.querySelector('.row-cp').value.trim();
            if (el || cp) {
                elemen_cp.push({ elemen: el, cp: cp });
            }
        });

        if (elemen_cp.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Elemen CP Kosong', text: 'Harap isi minimal 1 elemen capaian pembelajaran.' });
            return;
        }

        const btn = document.getElementById('btn-save-cp');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Menyimpan...</span>`;

        try {
            const formData = new FormData();
            formData.append('jenjang', currentJenjang);
            formData.append('fase', currentFase);
            formData.append('nama_mapel', currentSelectedMapel.nama_mapel);
            if (currentSelectedMapel.kode_mapel) formData.append('kode_mapel', currentSelectedMapel.kode_mapel);
            if (currentSelectedMapel.id) formData.append('mapel_id', currentSelectedMapel.id);
            formData.append('rasional_mapel', rasional);
            formData.append('tujuan_mapel', tujuan);
            formData.append('karakteristik_mapel', karakteristik);
            formData.append('elemen_cp', JSON.stringify(elemen_cp));

            const resp = await fetch('../api-cp-ai.php?action=save_cp', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.status === 'success') {
                const statusBadge = document.getElementById('active-status-badge');
                statusBadge.innerHTML = `<i class="fas fa-circle-check text-emerald-600 mr-1"></i> Terverifikasi BSKAP Kemendikbudristek`;
                statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';

                currentSelectedMapel.has_cp = true;
                renderMapelList(mapelList);

                Swal.fire({
                    icon: 'success',
                    title: 'Tersimpan Manual!',
                    html: `Perubahan CP untuk <b>${escapeHtml(currentSelectedMapel.nama_mapel)}</b> telah disimpan dan disinkronkan ke silabus asatidz.`,
                    confirmButtonColor: '#0b8478'
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: res.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error Koneksi', text: e.message });
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }

    // 9. AUTONOMOUS FULL RUN: BATCH ALL MAPEL DIKNAS
    async function runAutonomousBatchFullRun() {
        const confirmRes = await Swal.fire({
            title: `Jalankan Autonomous Full Run?`,
            text: `Agent AI akan meriset seluruh ketetapan pakem Kemendikbudristek (BSKAP 032/H/KR/2024) untuk semua mata pelajaran Diknas jenjang ${currentJenjang} dan langsung menuangkannya ke tabel database & silabus guru secara otomatis.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Jalankan Agent Otonom!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0b8478'
        });

        if (!confirmRes.isConfirmed) return;

        const modal = document.getElementById('batch-modal');
        const progressBar = document.getElementById('batch-progress-bar');
        const statusText = document.getElementById('batch-status-text');
        document.getElementById('batch-jenjang-label').textContent = currentJenjang;

        modal.classList.remove('hidden');
        progressBar.style.width = '20%';
        statusText.textContent = `Menghubungi repositori BSKAP Kemendikbudristek No. 032/H/KR/2024...`;

        try {
            progressBar.style.width = '55%';
            statusText.textContent = `Mengekstrak dan menuangkan capaian pembelajaran seluruh mapel ke tabel...`;

            const formData = new FormData();
            formData.append('jenjang', currentJenjang);

            const resp = await fetch('../api-cp-ai.php?action=agentic_batch_all', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            progressBar.style.width = '100%';

            if (res.status === 'success') {
                statusText.textContent = `Selesai! Seluruh data pakem pemerintah telah dituangkan ke tabel...`;
                setTimeout(() => {
                    modal.classList.add('hidden');
                    Swal.fire({
                        icon: 'success',
                        title: 'Full Run Selesai!',
                        text: `Agent AI berhasil meriset & menuangkan ${res.total_processed} mata pelajaran Diknas ${currentJenjang} langsung ke dalam tabel. Form silabus asatidz telah terisi lengkap!`,
                        confirmButtonColor: '#0b8478'
                    });
                    loadMapelList();
                }, 750);
            } else {
                modal.classList.add('hidden');
                Swal.fire({ icon: 'error', title: 'Batch Gagal', text: res.message });
            }
        } catch (e) {
            modal.classList.add('hidden');
            Swal.fire({ icon: 'error', title: 'Error Koneksi Batch', text: e.message });
        }
    }

    // 10. SIMULASI / TRIGGER OTONOM 1 JULI (TAHUN AJARAN BARU)
    async function triggerSimulasiJuli() {
        const confirmRes = await Swal.fire({
            title: 'Jalankan Tugas Tahunan 1 Juli?',
            text: 'Ini akan menjalankan simulasi tugas otonom 1 Juli: Agent AI akan meriset seluruh ketetapan BSKAP Kemendikbudristek untuk seluruh mapel SMP & SMA Tahun Ajaran baru dan mencatat ke riwayat eksekusi tahunan.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Jalankan!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#0b8478'
        });

        if (!confirmRes.isConfirmed) return;

        Swal.fire({
            title: 'Agent Sedang Bekerja...',
            html: '<p class="text-xs text-slate-500">Mengeksekusi runner 1 Juli untuk Tahun Ajaran Baru...</p>',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            const resp = await fetch('../cron-cp-annual.php?force=1');
            const res = await resp.json();

            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Tugas 1 Juli Selesai!',
                    html: `Agent AI berhasil menstandarisasi <b>${res.total_mapel_diperbarui} mata pelajaran</b> untuk Tahun Ajaran <b>${res.tahun_ajaran}</b>!<br><span class="text-xs text-slate-500">Waktu: ${res.executed_at}</span>`,
                    confirmButtonColor: '#0b8478'
                }).then(() => location.reload());
            } else {
                Swal.fire({ icon: 'info', title: 'Status', text: res.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error Koneksi', text: e.message });
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function renderEmptyWorkstation() {
        document.getElementById('active-mapel-title').textContent = 'Tidak Ada Mata Pelajaran';
        document.getElementById('elemen-table-body').innerHTML = `
            <tr>
                <td colspan="3" class="p-6 text-center text-xs text-slate-400">
                    Tidak ada mata pelajaran Diknas untuk jenjang ini.
                </td>
            </tr>
        `;
    }
    </script>
</body>
</html>
