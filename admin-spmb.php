<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Fitur Hapus Data Pendaftar
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM pendaftar_spmb WHERE id = $id");
    header("Location: admin-spmb.php");
    exit;
}

// Fitur Ubah Status Lulus/Tidak Lulus
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE pendaftar_spmb SET status = '$status' WHERE id = $id");
    header("Location: admin-spmb.php");
    exit;
}

// Ambil Semua Data
$pendaftar = [];
$res = $conn->query("SELECT * FROM pendaftar_spmb ORDER BY id DESC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $pendaftar[] = $row;
    }
}

$active_menu = 'spmb';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pendaftar SPMB | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- INCLUDE SIDEBAR MARKETING -->
    <?php include 'sidebar-marketing.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-emerald-500 flex items-center justify-center text-white font-bold shadow-sm">A</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-graduate text-emerald-600 mr-2"></i>Data Pendaftar SPMB</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola data calon santri baru yang mengisi formulir secara online.</p>
                </div>
                <div>
                    <a href="export-spmb.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-md flex items-center">
                        <i class="fas fa-file-excel mr-2"></i> Export ke Excel
                    </a>
                </div>
            </div>

            <!-- FITUR PENCARIAN & FILTER -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama santri atau asal sekolah..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
                <div class="w-full sm:w-auto flex items-center gap-3">
                    <label class="text-sm font-bold text-gray-700 whitespace-nowrap"><i class="fas fa-filter mr-1"></i> Filter Status:</label>
                    <select id="statusFilter" onchange="filterTable()" class="bg-gray-50 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-sm w-full sm:w-auto cursor-pointer">
                        <option value="semua">Semua Pendaftar</option>
                        <option value="Menunggu Tes">Menunggu Tes</option>
                        <option value="Lulus Seleksi">Lulus Seleksi</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800">Tabel Antrean Pendaftar</h2>
                    <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full"><?= count($pendaftar) ?> Total Data</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Identitas Santri</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Jenjang & Asal</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Berkas/Bukti</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Seleksi</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi Cepat</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (count($pendaftar) > 0): ?>
                                <?php foreach($pendaftar as $p): 
                                    // Tentukan Warna Badge Status
                                    $bg_badge = 'bg-amber-100 text-amber-800';
                                    if($p['status'] == 'Lulus Seleksi') $bg_badge = 'bg-emerald-100 text-emerald-800';
                                    if($p['status'] == 'Ditolak') $bg_badge = 'bg-red-100 text-red-800';
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($p['nama_lengkap']) ?></div>
                                        <div class="text-sm text-gray-500"><i class="fab fa-whatsapp text-green-500"></i> <?= htmlspecialchars($p['whatsapp_ortu']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-bold text-indigo-700 uppercase"><?= htmlspecialchars($p['jenjang']) ?></div>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($p['asal_sekolah']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <?php if(!empty($p['berkas_transfer'])): ?>
                                            <a href="uploads/spmb/<?= $p['berkas_transfer'] ?>" target="_blank" class="text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 px-2 py-1 rounded border border-blue-200" title="Lihat Bukti TF"><i class="fas fa-receipt"></i> TF</a>
                                        <?php endif; ?>
                                        <?php if(!empty($p['berkas_kk'])): ?>
                                            <a href="uploads/spmb/<?= $p['berkas_kk'] ?>" target="_blank" class="text-xs bg-gray-50 text-gray-600 hover:bg-gray-100 px-2 py-1 rounded border border-gray-200" title="Lihat KK"><i class="fas fa-file-alt"></i> KK</a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full <?= $bg_badge ?>"><?= $p['status'] ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center space-x-2">
                                            <a href="?status=Lulus Seleksi&id=<?= $p['id'] ?>" class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white flex items-center justify-center transition" title="Luluskan"><i class="fas fa-check"></i></a>
                                            <a href="?status=Ditolak&id=<?= $p['id'] ?>" class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-500 hover:text-white flex items-center justify-center transition" title="Tolak"><i class="fas fa-times"></i></a>
                                            <a href="?hapus_id=<?= $p['id'] ?>" onclick="return confirm('Yakin ingin menghapus data ini?')" class="w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-800 hover:text-white flex items-center justify-center transition" title="Hapus"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">Belum ada pendaftar SPMB.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        document.getElementById('open-sidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar-overlay').classList.toggle('hidden');
        });
        document.getElementById('sidebar-overlay').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('hidden');
            document.getElementById('sidebar-overlay').classList.toggle('hidden');
        });

        // FUNGSI PENCARIAN & FILTER TABEL
        function filterTable() {
            const searchInput = document.getElementById("searchInput").value.toLowerCase();
            const statusFilter = document.getElementById("statusFilter").value;
            const table = document.querySelector("table tbody");
            const trs = table.getElementsByTagName("tr");

            for (let i = 0; i < trs.length; i++) {
                if (trs[i].getElementsByTagName("td").length === 1) continue; // Skip baris kosong
                
                const tdNama = trs[i].getElementsByTagName("td")[0].textContent.toLowerCase();
                const tdSekolah = trs[i].getElementsByTagName("td")[1].textContent.toLowerCase();
                const tdStatus = trs[i].getElementsByTagName("td")[3].textContent.trim();
                
                const matchSearch = tdNama.includes(searchInput) || tdSekolah.includes(searchInput);
                const matchStatus = (statusFilter === "semua") || (tdStatus === statusFilter);
                
                trs[i].style.display = (matchSearch && matchStatus) ? "" : "none";
            }
        }
    </script>
</body>
</html>