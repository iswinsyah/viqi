<?php
require_once 'auth.php';
require_once 'koneksi.php';

// Sesuaikan tabel SPMB untuk mencatat status daftar ulang
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN status_daftar_ulang VARCHAR(50) DEFAULT 'Belum' AFTER status");

// Fitur Toggle Daftar Ulang
if (isset($_GET['toggle_id']) && isset($_GET['val'])) {
    $id = (int)$_GET['toggle_id'];
    $val = $conn->real_escape_string($_GET['val']);
    $conn->query("UPDATE pendaftar_spmb SET status_daftar_ulang = '$val' WHERE id = $id");
    header("Location: admin-santri.php");
    exit;
}

// Fitur Export Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header("Content-Type: application/vnd-ms-excel");
    header("Content-Disposition: attachment; filename=Data_Santri_Baru_Diterima_" . date('Y-m-d') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    $res = $conn->query("SELECT * FROM pendaftar_spmb WHERE status = 'Lulus Seleksi' ORDER BY nama_lengkap ASC");
    echo "<table border='1'><thead><tr><th>No</th><th>Jenjang</th><th>Nama Lengkap</th><th>NIK</th><th>NISN</th><th>No. WA Ortu</th><th>Asal Sekolah</th><th>Status Daftar Ulang</th></tr></thead><tbody>";
    $no = 1;
    if($res) {
        while($row = $res->fetch_assoc()) {
            echo "<tr><td>".$no++."</td><td>".strtoupper($row['jenjang'])."</td><td>".$row['nama_lengkap']."</td><td style='mso-number-format:\"\\@\";'>".$row['nik']."</td><td style='mso-number-format:\"\\@\";'>".$row['nisn']."</td><td style='mso-number-format:\"\\@\";'>".$row['whatsapp_ortu']."</td><td>".$row['asal_sekolah']."</td><td>".$row['status_daftar_ulang']."</td></tr>";
        }
    }
    echo "</tbody></table>";
    exit;
}

$active_menu = 'santri';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Santri Baru | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>
    
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
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-check text-emerald-600 mr-2"></i>Data Santri Baru (Diterima)</h1>
                    <p class="text-sm text-gray-500 mt-1">Daftar santri yang lulus SPMB dan siap dipindahkan ke Aplikasi Sekolah.</p>
                </div>
                <a href="?export=excel" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center">
                    <i class="fas fa-file-excel mr-2"></i> Export Excel
                </a>
            </div>

            <div class="bg-blue-50 border border-blue-100 text-blue-800 p-4 rounded-xl mb-6 shadow-sm flex items-start">
                <i class="fas fa-info-circle mt-1 mr-3 text-blue-500"></i>
                <p class="text-sm">Data di halaman ini <b>otomatis terisi</b> dari menu Data Pendaftar SPMB yang statusnya telah Anda ubah menjadi <b>"Lulus Seleksi"</b>. Anda bisa memantau siapa saja yang sudah menyelesaikan Daftar Ulang di sini sebelum mengekspor datanya.</p>
            </div>

            <!-- FITUR PENCARIAN & FILTER TABEL SANTRI BARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <div class="relative w-full sm:w-1/2 md:w-1/3">
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari Nama atau Asal Sekolah..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
                <div class="w-full sm:w-auto flex items-center gap-3">
                    <label class="text-sm font-bold text-gray-700 whitespace-nowrap"><i class="fas fa-filter mr-1"></i> Daftar Ulang:</label>
                    <select id="statusFilter" onchange="filterTable()" class="bg-gray-50 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 font-medium text-sm w-full sm:w-auto cursor-pointer">
                        <option value="semua">Semua Status</option>
                        <option value="Sudah">Sudah Daftar Ulang</option>
                        <option value="Belum">Belum Daftar Ulang</option>
                    </select>
                </div>
            </div>

            <!-- TABEL DATA SANTRI BARU -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h2 class="font-bold text-gray-800">Daftar Santri Diterima</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Identitas Santri</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Jenjang</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Asal Sekolah</th>
                                <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Status Daftar Ulang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            <?php
                            $res = $conn->query("SELECT * FROM pendaftar_spmb WHERE status = 'Lulus Seleksi' ORDER BY nama_lengkap ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $is_sudah = ($row['status_daftar_ulang'] == 'Sudah');
                                    $bg_badge = $is_sudah ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600';
                            ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_lengkap']) ?></div>
                                        <div class="text-sm font-mono text-gray-500 mt-1">NISN: <?= htmlspecialchars($row['nisn']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-indigo-700 uppercase"><?= htmlspecialchars($row['jenjang']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($row['asal_sekolah']) ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex flex-col items-center justify-center space-y-2">
                                            <span class="px-3 py-1 text-xs font-bold rounded-full <?= $bg_badge ?>"><?= $row['status_daftar_ulang'] ?? 'Belum' ?></span>
                                            <?php if($is_sudah): ?>
                                                <a href="?toggle_id=<?= $row['id'] ?>&val=Belum" class="text-[10px] text-gray-400 hover:text-rose-500 transition underline">Batalkan</a>
                                            <?php else: ?>
                                                <a href="?toggle_id=<?= $row['id'] ?>&val=Sudah" class="text-[10px] text-emerald-600 hover:text-emerald-800 transition border border-emerald-200 bg-emerald-50 px-2 py-0.5 rounded">Tandai Sudah</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-10 text-gray-500 italic'>Belum ada santri yang berstatus Lulus Seleksi di SPMB.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.getElementById('open-sidebar');
            if(openBtn) openBtn.addEventListener('click', () => { 
                document.getElementById('sidebar').classList.toggle('hidden'); 
                document.getElementById('sidebar-overlay').classList.toggle('hidden'); 
            });
        });

    // FUNGSI PENCARIAN & FILTER TABEL
    function filterTable() {
        const searchInput = document.getElementById("searchInput").value.toLowerCase();
        const statusFilter = document.getElementById("statusFilter").value;
        const table = document.querySelector("table tbody");
        const trs = table.getElementsByTagName("tr");

        for (let i = 0; i < trs.length; i++) {
            if (trs[i].getElementsByTagName("td").length === 1) continue; 
            
            const tdNama = trs[i].getElementsByTagName("td")[0].textContent.toLowerCase();
            const tdSekolah = trs[i].getElementsByTagName("td")[2].textContent.toLowerCase();
            const tdStatus = trs[i].getElementsByTagName("td")[3].textContent.trim().split('\n')[0]; // Ambil teks badge pertama saja
            
            const matchSearch = tdNama.includes(searchInput) || tdSekolah.includes(searchInput);
            const matchStatus = (statusFilter === "semua") || (tdStatus.includes(statusFilter));
            
            trs[i].style.display = (matchSearch && matchStatus) ? "" : "none";
        }
    }
    </script>
</body>
</html>