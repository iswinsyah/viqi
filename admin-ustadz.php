<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Penanda menu aktif
$active_menu = 'dashboard_pegawai';

$ustadz_nama = $_SESSION['ustadz_nama'] ?? 'Ustadz';

// --- AMBIL DATA STATISTIK UNTUK WIDGET ---
$q_jurnal = $conn->query("SELECT COUNT(id) AS tot FROM jurnal_mengajar");
$total_jurnal = $q_jurnal ? ($q_jurnal->fetch_assoc()['tot'] ?? 0) : 0;

$q_nilai = $conn->query("SELECT COUNT(id) AS tot FROM bank_nilai");
$total_nilai = $q_nilai ? ($q_nilai->fetch_assoc()['tot'] ?? 0) : 0;

// Ambil jurnal terbaru
$jurnal_terbaru = [];
$res_jurnal = $conn->query("SELECT * FROM jurnal_mengajar ORDER BY id DESC LIMIT 5");
if ($res_jurnal) { while($r = $res_jurnal->fetch_assoc()) { $jurnal_terbaru[] = $r; } }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Staf | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR KEPEGAWAIAN -->
    <?php include 'sidebar-hr.php'; ?>

    <!-- AREA KONTEN UTAMA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <!-- Tombol Hamburger untuk Mobile -->
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            
            <div class="flex items-center space-x-4">
                <a href="index.html" target="_blank" class="text-sm text-cyan-600 hover:text-cyan-800 font-medium hidden sm:flex items-center">
                    <i class="fas fa-external-link-alt mr-2"></i> Lihat Website
                </a>
                <div class="h-8 w-8 rounded-full bg-cyan-500 flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($ustadz_nama, 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- MAIN DASHBOARD CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-users-cog text-cyan-600 mr-2"></i>Ahlan Wa Sahlan, <?= htmlspecialchars($ustadz_nama) ?>!</h1>
                <p class="text-gray-500 mt-1">Selamat datang di Ruang Staf. Gunakan menu di samping untuk mengelola tugas Anda.</p>
            </div>

            <!-- WIDGET SHORTCUT -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <a href="admin-pegawai-jurnal.php" class="bg-white hover:bg-cyan-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-book-open text-cyan-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Isi Jurnal</span>
                </a>
                <a href="admin-pegawai-mutabaah.php" class="bg-white hover:bg-emerald-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-clipboard-list text-emerald-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">Buku Mutaba'ah</span>
                </a>
                <a href="admin-pegawai-rpp.php" class="bg-white hover:bg-blue-50 border border-gray-100 rounded-xl p-4 flex flex-col items-center justify-center shadow-sm transition group">
                    <i class="fas fa-magic text-blue-500 text-3xl mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-sm font-bold text-gray-700 mt-1 text-center">AI RPP</span>
                </a>
            </div>

            <!-- WIDGET STATISTIK -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-4 rounded-full bg-cyan-100 text-cyan-600 mr-4"><i class="fas fa-book-open text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Jurnal Anda</p><p class="text-2xl font-bold text-gray-900"><?= $total_jurnal ?></p></div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center">
                    <div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4"><i class="fas fa-star-half-alt text-2xl"></i></div>
                    <div><p class="text-sm font-medium text-gray-500">Total Nilai Diinput</p><p class="text-2xl font-bold text-gray-900"><?= $total_nilai ?></p></div>
                </div>
            </div>

            <!-- TABEL JURNAL TERBARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800">Jurnal Mengajar Terakhir</h2>
                    <a href="admin-pegawai-jurnal.php" class="text-sm text-cyan-600 hover:text-cyan-800 font-medium">Lihat Semua</a>
                </div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kelas & Mapel</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Materi Pokok</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (count($jurnal_terbaru) > 0): ?>
                                <?php foreach($jurnal_terbaru as $row): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700 font-medium whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-cyan-700"><?= htmlspecialchars($row['kelas']) ?></div>
                                        <div class="text-sm text-gray-600"><?= htmlspecialchars($row['mata_pelajaran']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium"><?= htmlspecialchars($row['materi']) ?></div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan='3' class='text-center py-6 text-gray-500 italic'>Belum ada catatan jurnal mengajar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- SCRIPT UNTUK TOGGLE SIDEBAR DI MOBILE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-hr');
            const openBtn = document.getElementById('open-sidebar-hr');
            const overlay = document.getElementById('sidebar-overlay-hr');
            const closeBtn = document.getElementById('close-sidebar-hr');

            function toggleSidebar() {
                if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>