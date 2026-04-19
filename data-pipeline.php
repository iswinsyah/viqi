<?php
require_once 'koneksi.php';

// Otomatis tambahkan kolom status & jenis_lead jika belum ada di tabel leads
$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");

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
    'Level 1' => ['title' => '1. Download / Daftar', 'color' => 'bg-gray-50', 'border' => 'border-gray-300', 'text' => 'text-gray-700'],
    'Level 2' => ['title' => '2. Ikut Kelas/Konsultasi', 'color' => 'bg-blue-50', 'border' => 'border-blue-300', 'text' => 'text-blue-800'],
    'Level 3' => ['title' => '3. Survey Lokasi', 'color' => 'bg-indigo-50', 'border' => 'border-indigo-300', 'text' => 'text-indigo-800'],
    'Level 4' => ['title' => '4. Bayar Pendaftaran', 'color' => 'bg-amber-50', 'border' => 'border-amber-300', 'text' => 'text-amber-800'],
    'Level 5' => ['title' => '5. Test & Interview', 'color' => 'bg-orange-50', 'border' => 'border-orange-300', 'text' => 'text-orange-800'],
    'Level 6' => ['title' => '6. Daftar Ulang', 'color' => 'bg-emerald-50', 'border' => 'border-emerald-300', 'text' => 'text-emerald-800']
];
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
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <aside id="sidebar" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-20 h-full absolute md:relative">
        <div class="h-16 flex items-center justify-center border-b border-gray-800 px-4">
            <span class="text-xl font-bold text-emerald-400">
                <i class="fas fa-leaf mr-2"></i>VQ Admin
            </span>
            <button id="close-sidebar" class="md:hidden ml-auto text-gray-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto py-4">
            <nav class="space-y-1 px-2">
                <a href="admin.html" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-tachometer-alt w-6 text-center"></i>
                    <span class="ml-3 font-medium">Dashboard</span>
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-user-graduate w-6 text-center"></i>
                    <span class="ml-3 font-medium">Data Pendaftar SPMB</span>
                    <span class="ml-auto bg-rose-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">12</span>
                </a>
                <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-users w-6 text-center"></i>
                    <span class="ml-3 font-medium">Data Santri</span>
                </a>
                
                <div class="pt-4 pb-2">
                    <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Marketing & Leads</p>
                </div>
                <!-- ACTIVE MENU -->
                <a href="data-pipeline.php" class="flex items-center px-4 py-3 bg-emerald-600 text-white rounded-lg group">
                    <i class="fas fa-columns w-6 text-center"></i>
                    <span class="ml-3 font-medium">Pipeline Prospek</span>
                </a>
                <a href="data-agen.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                    <i class="fas fa-handshake w-6 text-center"></i>
                    <span class="ml-3 font-medium">Data Agen</span>
                </a>
            </nav>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-10 hidden md:hidden"></div>

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
                <button onclick="window.location.reload()" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm border border-emerald-200">
                    <i class="fas fa-sync-alt mr-2"></i> Segarkan Data
                </button>
            </div>

            <!-- KANBAN CONTAINER -->
            <div class="flex flex-nowrap gap-6 overflow-x-auto pb-4 pt-2 h-full items-start kanban-scroll">
                
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
                        <span class="bg-white px-2 py-0.5 rounded-full text-xs shadow-sm border <?= $col['border'] ?>"><?= $count ?></span>
                    </div>
                    
                    <!-- Isi Kartu (Scrollable Vertical) -->
                    <div class="p-3 overflow-y-auto flex-1 space-y-3" style="scrollbar-width: none;">
                        <?php foreach($items as $lead) { 
                            $badge_color = ($lead['jenis_lead'] == 'acara_dan_ebook') ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700';
                            $badge_text = ($lead['jenis_lead'] == 'acara_dan_ebook') ? 'Acara & Ebook' : (($lead['jenis_lead'] == 'hanya_ebook') ? 'Hanya Ebook' : 'Brosur');
                        ?>
                        <div class="bg-white p-3 rounded-lg shadow-sm border border-gray-200 hover:border-emerald-400 cursor-pointer hover:shadow-md transition group">
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
                                <span title="Kode Agen"><i class="fas fa-link mr-1"></i> <?= htmlspecialchars($lead['kode_ref']) ?></span>
                                <span title="Tanggal Masuk"><?= date('d M', strtotime($lead['created_at'])) ?></span>
                            </div>
                            
                            <!-- Action Tombol Pindah Status (Akan kita fungsikan di update selanjutnya) -->
                            <div class="mt-3 flex justify-between items-center opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="text-gray-400 hover:text-rose-600 text-[10px]"><i class="fas fa-trash"></i></button>
                                <?php if($level != 'Level 6') { ?>
                                <button class="bg-emerald-50 text-emerald-600 hover:bg-emerald-100 px-2 py-1 rounded text-[10px] font-bold border border-emerald-200">Pindah Status <i class="fas fa-arrow-right ml-1"></i></button>
                                <?php } else { ?>
                                <span class="text-emerald-600 text-[10px] font-bold"><i class="fas fa-check-circle"></i> Selesai</span>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                        
                        <?php if($count == 0) { ?>
                            <div class="text-center py-6 text-sm text-gray-400 border-2 border-dashed border-gray-300 rounded-lg">
                                Belum ada data
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
                
            </div>
        </main>
    </div>

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
        });
    </script>
</body>
</html>