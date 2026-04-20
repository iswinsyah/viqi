<?php
require_once 'koneksi.php';

// Otomatis tambahkan kolom status & jenis_lead jika belum ada di tabel leads
$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");
$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(100) DEFAULT '' AFTER jenis_lead");

// Ambil semua data leads
$sql = "SELECT * FROM leads ORDER BY id DESC";
$result = $conn->query($sql);
$leads = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }
}

// Struktur 6 Level Funnel
$board = [
    'Level 1' => [],
    'Level 2' => [],
    'Level 3' => [],
    'Level 4' => [],
    'Level 5' => [],
    'Level 6' => []
];

// Kelompokkan data ke masing-masing level
foreach ($leads as $lead) {
    $status = $lead['status'] ?? 'Level 1';
    if (isset($board[$status])) {
        $board[$status][] = $lead;
    } else {
        $board['Level 1'][] = $lead;
    }
}

// Tambahkan dummy data hanya untuk pratinjau jika tabel masih kosong
if (empty($leads)) {
    $board['Level 1'][] = ['id'=>1, 'nama' => 'Bpk. Fulan (Dummy)', 'whatsapp' => '081234567890', 'jenis_lead' => 'acara_dan_ebook', 'kode_ref' => '081299998888', 'created_at' => '2026-04-19 10:00:00'];
    $board['Level 2'][] = ['id'=>2, 'nama' => 'Ibu Aisyah (Dummy)', 'whatsapp' => '085678901234', 'jenis_lead' => 'hanya_ebook', 'kode_ref' => 'organik', 'created_at' => '2026-04-18 14:30:00'];
    $board['Level 3'][] = ['id'=>3, 'nama' => 'Bpk. Budi (Dummy)', 'whatsapp' => '082133334444', 'jenis_lead' => 'acara_dan_ebook', 'kode_ref' => 'organik', 'created_at' => '2026-04-15 09:15:00'];
}

// Konfigurasi Tampilan Kolom Kanban
$columns = [
    'Level 1' => ['title' => '1. Download / Daftar', 'color' => 'bg-gray-100', 'border' => 'border-gray-300', 'text' => 'text-gray-700'],
    'Level 2' => ['title' => '2. Ikut Kelas/Konsultasi', 'color' => 'bg-blue-100', 'border' => 'border-blue-300', 'text' => 'text-blue-800'],
    'Level 3' => ['title' => '3. Survey Lokasi', 'color' => 'bg-indigo-100', 'border' => 'border-indigo-300', 'text' => 'text-indigo-800'],
    'Level 4' => ['title' => '4. Bayar Pendaftaran', 'color' => 'bg-amber-100', 'border' => 'border-amber-300', 'text' => 'text-amber-800'],
    'Level 5' => ['title' => '5. Test & Interview', 'color' => 'bg-orange-100', 'border' => 'border-orange-300', 'text' => 'text-orange-800'],
    'Level 6' => ['title' => '6. Daftar Ulang', 'color' => 'bg-emerald-100', 'border' => 'border-emerald-300', 'text' => 'text-emerald-800']
];

$active_menu = 'pipeline';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pipeline Leads | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Kustom scrollbar untuk kanban board */
        .kanban-scroll::-webkit-scrollbar {
            height: 8px;
        }
        .kanban-scroll::-webkit-scrollbar-track {
            background: #f1f1f1; 
            border-radius: 10px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1; 
            border-radius: 10px;
        }
        .kanban-scroll::-webkit-scrollbar-thumb:hover {
            background: #94a3b8; 
        }
        
        /* Area Drop Kosong */
        .kanban-cards { min-height: 120px; }
        .kanban-cards:empty::after {
            content: 'Tarik prospek ke sini';
            display: block;
            text-align: center;
            padding: 1.5rem 0;
            font-size: 0.875rem;
            color: #9ca3af;
            border: 2px dashed #cbd5e1;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <a href="index.html" target="_blank" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
                </a>
                <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT (KANBAN BOARD) -->
        <main class="flex-1 overflow-x-hidden overflow-y-hidden bg-white p-4 sm:p-6 flex flex-col">
            <div class="flex justify-between items-center mb-4 flex-shrink-0">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Pipeline Marketing (Kanban)</h1>
                    <p class="text-sm text-gray-500">Geser prospek ke kanan saat tahapan mereka meningkat.</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Tombol Navigasi Scroll -->
                    <div class="hidden md:flex items-center bg-white rounded-lg border border-gray-200 shadow-sm p-1">
                        <button id="btn-scroll-left" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-gray-100 rounded transition focus:outline-none disabled:opacity-30"><i class="fas fa-chevron-left"></i></button>
                        <div class="w-px h-5 bg-gray-200 mx-1"></div>
                        <button id="btn-scroll-right" class="p-2 text-gray-500 hover:text-emerald-600 hover:bg-gray-100 rounded transition focus:outline-none disabled:opacity-30"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    
                    <button onclick="window.location.reload()" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm border border-emerald-200">
                        <i class="fas fa-sync-alt mr-2"></i> Segarkan Data
                    </button>
                </div>
            </div>

            <!-- KANBAN CONTAINER -->
            <div id="kanban-container" class="flex flex-nowrap gap-6 overflow-x-auto pb-4 pt-2 h-full items-start kanban-scroll scroll-smooth">
                
                <?php
                foreach ($columns as $level => $col) {
                    $items = $board[$level];
                    $count = count($items);
                ?>
                <!-- KOLOM: <?= $level ?> -->
                <div class="rounded-xl w-80 flex-shrink-0 flex flex-col max-h-full border <?= $col['border'] ?> <?= $col['color'] ?>">
                    <!-- Header Kolom -->
                    <div class="p-3 border-b <?= $col['border'] ?> font-bold <?= $col['text'] ?> flex justify-between items-center bg-white/60 rounded-t-xl">
                        <span><?= $col['title'] ?></span>
                        <span class="bg-white px-2 py-0.5 rounded-full text-xs shadow-sm border <?= $col['border'] ?> badge-count"><?= $count ?></span>
                    </div>
                    
                    <!-- Isi Kartu (Scrollable Vertical) -->
                    <div class="p-3 overflow-y-auto flex-1 space-y-3 kanban-cards" data-level="<?= $level ?>" style="scrollbar-width: none;">
                        <?php foreach($items as $lead) { 
                            $badge_color = ($lead['jenis_lead'] == 'acara_dan_ebook') ? 'bg-purple-100 text-purple-700' : (($lead['jenis_lead'] == 'biaya') ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700');
                            $badge_text = ($lead['jenis_lead'] == 'acara_dan_ebook') ? 'Acara & Ebook' : (($lead['jenis_lead'] == 'hanya_ebook') ? 'Hanya Ebook' : (($lead['jenis_lead'] == 'biaya') ? 'Cek Biaya' : 'Brosur'));
                        ?>
                        <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200 hover:border-emerald-400 cursor-grab active:cursor-grabbing hover:shadow-md transition group" data-id="<?= $lead['id'] ?>">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($lead['nama']) ?></span>
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium <?= $badge_color ?> whitespace-nowrap"><?= $badge_text ?></span>
                            </div>
                            
                            <div class="text-xs text-gray-600 mb-3">
                                <i class="fab fa-whatsapp text-green-500 mr-1"></i> 
                                <?php
                                    // Bersihkan nomor WA (ganti awalan 0 dengan 62 agar link WA jalan)
                                    $wa_link = $lead['whatsapp'];
                                    if(substr($wa_link, 0, 1) == '0') {
                                        $wa_link = '62' . substr($wa_link, 1);
                                    }
                                ?>
                                <a href="https://wa.me/<?= $wa_link ?>" target="_blank" class="hover:text-green-600 hover:underline"><?= htmlspecialchars($lead['whatsapp']) ?></a>
                            </div>
                            
                            <div class="flex justify-between items-center text-[10px] text-gray-400 border-t border-gray-100 pt-2">
                                <span title="Kode Agen"><i class="fas fa-link mr-1"></i> <?= htmlspecialchars($lead['kode_ref']) ?>
                                    <?php if(!empty($lead['sumber_info'])) { echo " | <i class='fas fa-info-circle ml-1 text-emerald-500'></i> " . htmlspecialchars($lead['sumber_info']); } ?>
                                </span>
                                <span title="Tanggal Masuk"><?= date('d M', strtotime($lead['created_at'])) ?></span>
                            </div>
                            
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
                
            </div>
        </main>
    </div>

    <!-- SortableJS untuk Drag and Drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const openBtn = document.getElementById('open-sidebar');
            const closeBtn = document.getElementById('close-sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            function toggleSidebar() {
                sidebar.classList.toggle('hidden');
                overlay.classList.toggle('hidden');
            }

            openBtn.addEventListener('click', toggleSidebar);
            closeBtn.addEventListener('click', toggleSidebar);
            overlay.addEventListener('click', toggleSidebar);

            // INISIALISASI TOMBOL SCROLL KANBAN
            const kanbanContainer = document.getElementById('kanban-container');
            const btnLeft = document.getElementById('btn-scroll-left');
            const btnRight = document.getElementById('btn-scroll-right');
            const scrollAmount = 340; // Kurang lebih selebar 1 kolom + gap

            if(btnLeft && btnRight && kanbanContainer) {
                btnRight.addEventListener('click', () => {
                    kanbanContainer.scrollBy({ left: scrollAmount, behavior: 'smooth' });
                });
                btnLeft.addEventListener('click', () => {
                    kanbanContainer.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
                });
                
                // Cek status mentok untuk efek pudar (disabled) pada tombol
                kanbanContainer.addEventListener('scroll', () => {
                    btnLeft.disabled = kanbanContainer.scrollLeft <= 0;
                    btnRight.disabled = kanbanContainer.scrollLeft + kanbanContainer.clientWidth >= kanbanContainer.scrollWidth - 5;
                });
                btnLeft.disabled = true; // Kondisi awal mentok kiri
            }

            // INISIALISASI DRAG AND DROP KANBAN
            const kanbanColumns = document.querySelectorAll('.kanban-cards');
            
            kanbanColumns.forEach(col => {
                new Sortable(col, {
                    group: 'pipeline', // Memungkinkan kartu pindah antar kolom
                    animation: 150,
                    ghostClass: 'opacity-50', // Efek transparan saat ditarik
                    
                    // Event saat kartu selesai dilepas (dropped)
                    onEnd: function (evt) {
                        const itemEl = evt.item;
                        const toCol = evt.to;
                        const fromCol = evt.from;
                        
                        if(toCol === fromCol) return; // Batal jika dilepas di kolom yang sama

                        const leadId = itemEl.getAttribute('data-id');
                        const newLevel = toCol.getAttribute('data-level');

                        // Update angka badge jumlah secara instan (UI)
                        const fromBadge = fromCol.parentElement.querySelector('.badge-count');
                        const toBadge = toCol.parentElement.querySelector('.badge-count');
                        fromBadge.textContent = parseInt(fromBadge.textContent) - 1;
                        toBadge.textContent = parseInt(toBadge.textContent) + 1;

                        // Simpan ke Database via AJAX/Fetch
                        const formData = new FormData();
                        formData.append('id', leadId);
                        formData.append('status', newLevel);

                        fetch('update-lead-status.php', {
                            method: 'POST',
                            body: formData
                        }).catch(err => console.error('Gagal update database:', err));
                    }
                });
            });
        });
    </script>
</body>
</html>