<?php
require_once 'auth.php';
require_once '../koneksi.php';

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
    status_verifikasi ENUM('draft', 'terverifikasi') DEFAULT 'draft',
    last_generated_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_jenjang_fase_mapel (jenjang, fase, nama_mapel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$active_page = 'yayasan_cp';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Capaian Pembelajaran (CP) AI • Ruang Yayasan SADIGS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @media print {
            aside, header, #left-navigator, #action-bar, .no-print { display: none !important; }
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
                            Pusat Capaian Pembelajaran (CP) AI
                        </h2>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-50 text-[#0b8478] border border-teal-200">
                            BSKAP 032/H/KR/2024
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 hidden sm:block">Standarisasi Kurikulum Merdeka Terintegrasi Silabus & AI RPP Asatidz</p>
                </div>
            </div>

            <!-- TOP ACTIONS -->
            <div class="flex items-center gap-2 sm:gap-3">
                <button onclick="batchGenerateAll()" class="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 shadow-sm transition transform active:scale-95">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <span class="hidden sm:inline">Standarisasi Semua Mapel (AI)</span>
                    <span class="sm:hidden">Batch AI</span>
                </button>
                <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 shadow-xs transition">
                    <i class="fas fa-print"></i>
                    <span class="hidden md:inline">Cetak CP</span>
                </button>
            </div>
        </header>

        <!-- SUB HEADER: JENJANG SWITCHER TABS -->
        <div class="bg-white border-b border-slate-200 px-4 sm:px-6 py-2.5 flex-shrink-0 flex items-center justify-between gap-4">
            <!-- JENJANG SWITCHER -->
            <div class="flex items-center gap-1 p-1 bg-slate-100 rounded-xl max-w-md w-full sm:w-auto">
                <button id="tab-btn-smp" onclick="switchJenjang('SMP')" class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition shadow-xs bg-[#0b8478] text-white">
                    <i class="fas fa-school text-xs"></i>
                    <span>SMP (Fase D)</span>
                </button>
                <button id="tab-btn-sma" onclick="switchJenjang('SMA')" class="flex-1 sm:flex-initial flex items-center justify-center gap-2 px-4 py-1.5 rounded-lg text-xs font-bold transition text-slate-600 hover:text-slate-900">
                    <i class="fas fa-graduation-cap text-xs"></i>
                    <span>SMA (Fase E & F)</span>
                </button>
            </div>

            <div class="hidden lg:flex items-center gap-2 text-xs text-slate-500">
                <i class="fas fa-circle-info text-teal-600"></i>
                <span>Fokus Mata Pelajaran: <b>Diknas (Kurikulum Nasional)</b></span>
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

            <!-- RIGHT COLUMN: CP DETAIL & EDITOR -->
            <div id="workstation" class="flex-1 overflow-y-auto p-4 sm:p-6 flex flex-col space-y-5">
                
                <!-- PRINT HEADER (ONLY VISIBLE ON PRINT) -->
                <div class="print-only border-b-2 border-slate-800 pb-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-xl font-black tracking-tight text-slate-900 uppercase">Villa Quran - SADIGS</h1>
                            <h2 class="text-base font-bold text-slate-700">DOKUMEN CAPAIAN PEMBELAJARAN (CP) KURIKULUM MERDEKA</h2>
                            <p class="text-xs text-slate-500">Rujukan Resmi: BSKAP Kemendikbudristek No. 032/H/KR/2024</p>
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
                            Rujukan: BSKAP Kemendikbudristek No. 032/H/KR/2024
                        </p>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div id="action-bar" class="flex flex-wrap items-center gap-2">
                        <button id="btn-generate-ai" onclick="generateCPCurrent()" class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-xs transition">
                            <i class="fas fa-robot"></i>
                            <span>Tarik / Generate AI</span>
                        </button>
                        <button id="btn-save-cp" onclick="saveCurrentCP()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold bg-[#0b8478] hover:bg-teal-700 text-white shadow-xs transition">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <span>Simpan & Sahkan Yayasan</span>
                        </button>
                    </div>
                </div>

                <!-- INFO SYNC BANNER -->
                <div class="bg-gradient-to-r from-teal-900 via-teal-800 to-slate-900 rounded-2xl p-4 text-white shadow-sm flex items-start gap-3.5 border border-teal-700/50">
                    <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center flex-shrink-0 text-base mt-0.5">
                        <i class="fas fa-diagram-project"></i>
                    </div>
                    <div class="flex-1 text-xs">
                        <h4 class="font-bold text-teal-100 text-sm mb-0.5">Otomatisasi Ekosistem Akademik & RPP AI</h4>
                        <p class="text-slate-200 leading-relaxed">
                            Setiap Capaian Pembelajaran yang disahkan Yayasan di menu ini akan <b>langsung tersinkronisasi otomatis</b> ke form Silabus guru (<code class="bg-black/30 px-1 py-0.5 rounded text-teal-200">admin-pegawai-silabus.php</code>) dan modul RPP AI (<code class="bg-black/30 px-1 py-0.5 rounded text-teal-200">admin-pegawai-rpp.php</code>). Asatidz tidak perlu lagi mengisi CP manual dari awal.
                        </p>
                    </div>
                </div>

                <!-- SECTIONS ACCORDION / CARDS -->
                <div class="space-y-4">

                    <!-- CARD 1: RASIONAL MAPEL -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-lightbulb text-amber-500"></i>
                                Rasional Mata Pelajaran
                            </h3>
                            <span class="text-[11px] text-slate-400">Latar belakang & urgensi</span>
                        </div>
                        <textarea id="field-rasional" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478]" placeholder="Rasional mata pelajaran memuat alasan pentingnya peserta didik mempelajari disiplin ilmu ini..."></textarea>
                    </div>

                    <!-- CARD 2: TUJUAN MAPEL -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-bullseye text-rose-500"></i>
                                Tujuan Mata Pelajaran
                            </h3>
                            <span class="text-[11px] text-slate-400">Target kompetensi peserta didik</span>
                        </div>
                        <textarea id="field-tujuan" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478]" placeholder="Tujuan mata pelajaran menjabarkan kemampuan yang diharapkan dicapai..."></textarea>
                    </div>

                    <!-- CARD 3: KARAKTERISTIK MAPEL -->
                    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <i class="fas fa-shapes text-indigo-500"></i>
                                Karakteristik Mata Pelajaran
                            </h3>
                            <span class="text-[11px] text-slate-400">Ruang lingkup & fokus materi</span>
                        </div>
                        <textarea id="field-karakteristik" rows="3" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs leading-relaxed text-slate-700 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478]" placeholder="Karakteristik mata pelajaran mencakup pendekatan pembelajaran dan fokus elemen..."></textarea>
                    </div>

                    <!-- CARD 4: ELEMEN CAPAIAN PEMBELAJARAN (TABLE) -->
                    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                        <div class="px-5 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <i class="fas fa-list-check text-[#0b8478]"></i>
                                    Capaian Pembelajaran (CP) per Elemen
                                </h3>
                                <p class="text-[11px] text-slate-500 mt-0.5">Teks resmi CP per elemen fase yang akan digunakan sebagai landasan RPP</p>
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
                    <i class="fas fa-wand-magic-sparkles animate-pulse"></i>
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-1">Standarisasi AI Sedang Berjalan</h3>
                <p class="text-xs text-slate-500 mb-5">
                    AI Agentic sedang mengompilasi dan menstandarisasi seluruh Capaian Pembelajaran dari BSKAP Kemendikbudristek untuk jenjang <b id="batch-jenjang-label">SMP</b>...
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
            const resp = await fetch(`../api-cp-ai.php?action=get_mapel_list&jenjang=${encodeURIComponent(currentJenjang)}`);
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
                ? `<span class="px-2 py-0.5 rounded-full text-[9px] font-extrabold bg-emerald-100 text-emerald-800 flex items-center gap-1"><i class="fas fa-check"></i> Disahkan</span>`
                : `<span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-700 border border-amber-200">Belum Disahkan</span>`;

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

        document.getElementById('verified-summary-badge').textContent = `${verifiedCount}/${items.length} Disahkan`;
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
        renderMapelList(mapelList); // Re-render to update selected highlight

        document.getElementById('active-mapel-title').textContent = mapel.nama_mapel;
        document.getElementById('print-mapel').textContent = mapel.nama_mapel;
        document.getElementById('print-jenjang').textContent = currentJenjang;
        document.getElementById('print-fase').textContent = currentFase;

        const statusBadge = document.getElementById('active-status-badge');
        statusBadge.innerHTML = `<i class="fas fa-spinner fa-spin mr-1"></i> Memuat detail CP...`;
        statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200';

        try {
            const resp = await fetch(`../api-cp-ai.php?action=get_cp_detail&jenjang=${encodeURIComponent(currentJenjang)}&nama_mapel=${encodeURIComponent(mapel.nama_mapel)}`);
            const res = await resp.json();

            if (res.status === 'success') {
                currentCPData = res.data;
                populateWorkstation(currentCPData, res.is_saved);

                if (res.is_saved) {
                    statusBadge.innerHTML = `<i class="fas fa-circle-check text-emerald-600 mr-1"></i> Terverifikasi & Disahkan Yayasan`;
                    statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';
                } else {
                    statusBadge.innerHTML = `<i class="fas fa-circle-exclamation text-amber-600 mr-1"></i> Draft / Rujukan Siap Disahkan`;
                    statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200';
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Memuat CP', text: res.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error Koneksi', text: e.message });
        }
    }

    // 5. Populate Form Fields
    function populateWorkstation(data, isSaved) {
        document.getElementById('field-rasional').value = data.rasional_mapel || '';
        document.getElementById('field-tujuan').value = data.tujuan_mapel || '';
        document.getElementById('field-karakteristik').value = data.karakteristik_mapel || '';
        document.getElementById('active-rujukan-info').textContent = 'Rujukan: ' + (data.sumber_rujukan || 'BSKAP Kemendikbudristek No. 032/H/KR/2024');

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
            appendElemenRow(item.elemen || '', item.cp || '');
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

    // 7. Generate CP Current Mapel via AI
    async function generateCPCurrent() {
        if (!currentSelectedMapel) return;

        const btn = document.getElementById('btn-generate-ai');
        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Menganalisis BSKAP...</span>`;

        try {
            const formData = new FormData();
            formData.append('jenjang', currentJenjang);
            formData.append('nama_mapel', currentSelectedMapel.nama_mapel);
            if (currentSelectedMapel.kode_mapel) formData.append('kode_mapel', currentSelectedMapel.kode_mapel);

            const resp = await fetch('../api-cp-ai.php?action=generate_ai_cp', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            if (res.status === 'success') {
                populateWorkstation(res.data, false);
                
                const statusBadge = document.getElementById('active-status-badge');
                statusBadge.innerHTML = `<i class="fas fa-wand-magic-sparkles text-amber-600 mr-1"></i> Hasil AI Siap Disahkan`;
                statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200';

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Generate CP!',
                    text: `Capaian Pembelajaran ${currentSelectedMapel.nama_mapel} berhasil disusun sesuai BSKAP Kemendikbudristek 032/H/KR/2024. Silakan tinjau dan klik "Simpan & Sahkan Yayasan".`,
                    timer: 2500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Gagal Generate AI', text: res.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error Koneksi AI', text: e.message });
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }

    // 8. Save & Synchronize Current CP
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
        btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> <span>Menyimpan & Sinkron...</span>`;

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
                statusBadge.innerHTML = `<i class="fas fa-circle-check text-emerald-600 mr-1"></i> Terverifikasi & Disahkan Yayasan`;
                statusBadge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200';

                // Update current mapel has_cp state
                currentSelectedMapel.has_cp = true;
                renderMapelList(mapelList);

                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil Disahkan & Tersinkron!',
                    html: `CP <b>${escapeHtml(currentSelectedMapel.nama_mapel)}</b> telah disahkan oleh Yayasan dan <b>otomatis tersinkronisasi</b> ke Silabus Asatidz & AI RPP Generator.`,
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

    // 9. Batch Generate All Subjects
    async function batchGenerateAll() {
        const confirmRes = await Swal.fire({
            title: `Standarisasi Semua Mapel ${currentJenjang}?`,
            text: `AI akan mengompilasi rujukan kurikulum Kemendikbudristek untuk seluruh mata pelajaran Diknas jenjang ${currentJenjang} dan langsung menyimpannya ke database dan silabus.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Jalankan AI!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#f59e0b'
        });

        if (!confirmRes.isConfirmed) return;

        const modal = document.getElementById('batch-modal');
        const progressBar = document.getElementById('batch-progress-bar');
        const statusText = document.getElementById('batch-status-text');
        document.getElementById('batch-jenjang-label').textContent = currentJenjang;

        modal.classList.remove('hidden');
        progressBar.style.width = '15%';
        statusText.textContent = `Menghubungkan ke API AI & Knowledge Base BSKAP...`;

        try {
            progressBar.style.width = '45%';
            statusText.textContent = `Mengompilasi capaian per elemen ${currentJenjang}...`;

            const formData = new FormData();
            formData.append('jenjang', currentJenjang);

            const resp = await fetch('../api-cp-ai.php?action=batch_generate_all', {
                method: 'POST',
                body: formData
            });
            const res = await resp.json();

            progressBar.style.width = '100%';

            if (res.status === 'success') {
                statusText.textContent = `Selesai! Menyinkronkan database...`;
                setTimeout(() => {
                    modal.classList.add('hidden');
                    Swal.fire({
                        icon: 'success',
                        title: 'Standarisasi Selesai!',
                        text: `Berhasil menstandarisasi ${res.total_processed} mata pelajaran Diknas jenjang ${currentJenjang}. Seluruh silabus siap digunakan!`,
                        confirmButtonColor: '#0b8478'
                    });
                    loadMapelList();
                }, 800);
            } else {
                modal.classList.add('hidden');
                Swal.fire({ icon: 'error', title: 'Batch Gagal', text: res.message });
            }
        } catch (e) {
            modal.classList.add('hidden');
            Swal.fire({ icon: 'error', title: 'Error Koneksi Batch', text: e.message });
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
