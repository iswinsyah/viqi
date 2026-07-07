<?php
require_once 'auth.php'; // Menggunakan sistem keamanan Ruang Yayasan
require_once '../koneksi.php';

// 1. Buat Tabel & Kolom Otomatis (Self-healing)
$conn->query("CREATE TABLE IF NOT EXISTS master_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_mapel VARCHAR(150) UNIQUE NOT NULL,
    kategori_mapel ENUM('Diknas', 'Diniyah', 'Ekstrakurikuler', 'Lainnya') DEFAULT 'Lainnya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Tambahkan kolom baru jika belum ada
$res = $conn->query("SHOW COLUMNS FROM master_mapel LIKE 'metode_belajar'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE master_mapel ADD COLUMN metode_belajar ENUM('offline', 'online') DEFAULT 'offline' AFTER kategori_mapel");
}

$res = $conn->query("SHOW COLUMNS FROM master_mapel LIKE 'status_aktif'");
if ($res->num_rows == 0) {
    $conn->query("ALTER TABLE master_mapel ADD COLUMN status_aktif TINYINT(1) DEFAULT 1 AFTER metode_belajar");
}

// Inisialisasi tabel target kelas mapel
$conn->query("CREATE TABLE IF NOT EXISTS mapel_kelas_target (
    mapel_id INT NOT NULL,
    kelas_id INT NOT NULL,
    PRIMARY KEY (mapel_id, kelas_id)
)");

$pesan_sukses = "";
$pesan_error = "";

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM master_mapel WHERE id = $id");
    $conn->query("DELETE FROM mapel_kelas_target WHERE mapel_id = $id");
    header("Location: master-mapel.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_mapel = $conn->real_escape_string($_POST['nama_mapel']);
    $kategori_mapel = $conn->real_escape_string($_POST['kategori_mapel']);
    $metode_belajar = $conn->real_escape_string($_POST['metode_belajar']);
    $status_aktif = isset($_POST['status_aktif']) ? 1 : 0;
    $kelas_ids = isset($_POST['target_kelas']) && is_array($_POST['target_kelas']) ? $_POST['target_kelas'] : [];

    // Validasi duplikasi nama mapel saat tambah baru
    $cek_sql = $id > 0 ? "SELECT id FROM master_mapel WHERE nama_mapel='$nama_mapel' AND id != $id" : "SELECT id FROM master_mapel WHERE nama_mapel='$nama_mapel'";
    $cek_res = $conn->query($cek_sql);
    
    if ($cek_res && $cek_res->num_rows > 0) {
        $pesan_error = "Mata pelajaran '$nama_mapel' sudah terdaftar!";
    } else {
        if ($id > 0) {
            $sql = "UPDATE master_mapel SET nama_mapel='$nama_mapel', kategori_mapel='$kategori_mapel', metode_belajar='$metode_belajar', status_aktif=$status_aktif WHERE id=$id";
            $pesan_sukses = "Data mata pelajaran berhasil diupdate!";
        } else {
            $sql = "INSERT INTO master_mapel (nama_mapel, kategori_mapel, metode_belajar, status_aktif) VALUES ('$nama_mapel', '$kategori_mapel', '$metode_belajar', $status_aktif)";
            $pesan_sukses = "Mata pelajaran baru berhasil ditambahkan!";
        }

        if ($conn->query($sql)) {
            $mapel_id = ($id > 0) ? $id : $conn->insert_id;
            
            // Simpan relasi kelas
            $conn->query("DELETE FROM mapel_kelas_target WHERE mapel_id = $mapel_id");
            if (!empty($kelas_ids)) {
                $values = [];
                foreach ($kelas_ids as $k_id) {
                    $k_id = (int)$k_id;
                    $values[] = "($mapel_id, $k_id)";
                }
                $conn->query("INSERT INTO mapel_kelas_target (mapel_id, kelas_id) VALUES " . implode(", ", $values));
            }
            
            // Reset input redirect jika sukses untuk mereset $_POST
            header("Location: master-mapel.php?sukses=" . urlencode($pesan_sukses));
            exit;
        } else {
            $pesan_error = "Gagal menyimpan: " . $conn->error;
        }
    }
}

if (isset($_GET['sukses'])) {
    $pesan_sukses = $_GET['sukses'];
}

// 4. Ambil data untuk mode edit
$edit_mode = false;
$data_edit = null;
$edit_target_kelas = [];
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM master_mapel WHERE id = $id");
    if ($res) {
        $data_edit = $res->fetch_assoc();
        // Ambil target kelas
        $res_k = $conn->query("SELECT kelas_id FROM mapel_kelas_target WHERE mapel_id = $id");
        if ($res_k) {
            while ($rk = $res_k->fetch_row()) {
                $edit_target_kelas[] = $rk[0];
            }
        }
    }
}

// 5. Ambil Statistik Ringkasan
$tot_mapel = $conn->query("SELECT COUNT(id) as total FROM master_mapel")->fetch_assoc()['total'] ?? 0;
$tot_aktif = $conn->query("SELECT COUNT(id) as total FROM master_mapel WHERE status_aktif = 1")->fetch_assoc()['total'] ?? 0;
$tot_offline = $conn->query("SELECT COUNT(id) as total FROM master_mapel WHERE metode_belajar = 'offline'")->fetch_assoc()['total'] ?? 0;
$tot_online = $conn->query("SELECT COUNT(id) as total FROM master_mapel WHERE metode_belajar = 'online'")->fetch_assoc()['total'] ?? 0;

// Ambil list semua kelas untuk checkbox
$kelas_list = [];
$res_kelas = $conn->query("SELECT * FROM master_kelas ORDER BY kategori_kelas, nama_kelas ASC");
if ($res_kelas) {
    while ($row = $res_kelas->fetch_assoc()) {
        $kelas_list[$row['kategori_kelas']][] = $row;
    }
}

$active_menu = 'master_mapel';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Mata Pelajaran | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <!-- INCLUDE SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-4">
                <div class="h-8 w-8 rounded-full bg-amber-600 flex items-center justify-center text-white font-bold shadow-sm">Y</div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book text-amber-600 mr-2"></i>Master Mata Pelajaran</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola mode belajar, tahun ajaran aktif, dan kelas sasaran untuk masing-masing mata pelajaran.</p>
            </div>

            <!-- Pesan Notifikasi -->
            <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-check-circle mr-2 text-lg"></i> <?= htmlspecialchars($pesan_sukses) ?>
                </div>
            <?php endif; ?>
            <?php if(!empty($pesan_error)): ?>
                <div class="bg-rose-100 text-rose-800 border border-rose-200 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-lg"></i> <?= htmlspecialchars($pesan_error) ?>
                </div>
            <?php endif; ?>

            <!-- STATISTIK WIDGET -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Mapel</span>
                        <span class="text-2xl font-black text-slate-800"><?= $tot_mapel ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-lg"><i class="fas fa-atlas"></i></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Aktif Tahun Ini</span>
                        <span class="text-2xl font-black text-emerald-600"><?= $tot_aktif ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Mapel Offline</span>
                        <span class="text-2xl font-black text-indigo-600"><?= $tot_offline ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg"><i class="fas fa-users"></i></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Mapel Online</span>
                        <span class="text-2xl font-black text-cyan-600"><?= $tot_online ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg"><i class="fas fa-laptop-house"></i></div>
                </div>
            </div>

            <!-- GRID FORMS & TABLE -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                
                <!-- KIRI: FORM TAMBAH/EDIT -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 overflow-hidden flex flex-col h-fit">
                    <div class="px-6 py-4 bg-amber-900 text-white flex items-center space-x-2">
                        <i class="fas <?= $edit_mode ? 'fa-edit text-amber-300' : 'fa-plus-circle text-amber-300' ?> text-lg"></i>
                        <h2 class="font-bold text-sm"><?= $edit_mode ? 'Edit Pengaturan Mapel' : 'Tambah Mata Pelajaran Baru' ?></h2>
                    </div>

                    <form action="master-mapel.php" method="POST" class="p-6 space-y-5">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Nama Mata Pelajaran</label>
                            <input type="text" name="nama_mapel" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_mapel']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm" placeholder="Contoh: Fiqih Ibadah">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Kategori</label>
                                <select name="kategori_mapel" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                                    <?php 
                                    $kats = ['Diknas', 'Diniyah', 'Ekstrakurikuler', 'Lainnya'];
                                    foreach($kats as $k) {
                                        $sel = ($edit_mode && $data_edit['kategori_mapel'] === $k) ? 'selected' : '';
                                        echo "<option value='$k' $sel>$k</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Metode Belajar</label>
                                <select name="metode_belajar" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm">
                                    <option value="offline" <?= ($edit_mode && $data_edit['metode_belajar'] === 'offline') ? 'selected' : '' ?>>Offline (Luring)</option>
                                    <option value="online" <?= ($edit_mode && $data_edit['metode_belajar'] === 'online') ? 'selected' : '' ?>>Online (Daring)</option>
                                </select>
                            </div>
                        </div>

                        <!-- TOGGLE STATUS AKTIF -->
                        <div class="flex items-center space-x-3 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                            <input type="checkbox" id="status_aktif" name="status_aktif" value="1" <?= (!$edit_mode || (isset($data_edit['status_aktif']) && $data_edit['status_aktif'] == 1)) ? 'checked' : '' ?> class="w-4 h-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                            <div>
                                <label for="status_aktif" class="text-sm font-bold text-slate-700 block cursor-pointer">Aktif untuk Tahun Ajaran Ini</label>
                                <span class="text-[10px] text-gray-400 block">Matikan jika mapel ini ditiadakan sementara tahun ini.</span>
                            </div>
                        </div>

                        <!-- MULTI-SELECT KELAS SASARAN -->
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-2">Kelas Sasaran (Diberikan untuk Kelas:)</label>
                            
                            <?php if (empty($kelas_list)): ?>
                                <p class="text-xs text-rose-500"><i class="fas fa-exclamation-triangle mr-1"></i> Data kelas belum tersedia. Buat kelas terlebih dahulu di menu Master Kelas.</p>
                            <?php else: ?>
                                <div class="max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-4 bg-gray-50">
                                    <?php foreach ($kelas_list as $kat_kelas => $kelases): ?>
                                        <div>
                                            <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider block border-b pb-1 mb-2"><?= $kat_kelas ?></span>
                                            <div class="grid grid-cols-2 gap-2">
                                                <?php foreach ($kelases as $kls): 
                                                    $checked = ($edit_mode && in_array($kls['id'], $edit_target_kelas)) ? 'checked' : '';
                                                ?>
                                                    <label class="flex items-center space-x-2 text-xs font-semibold text-gray-700 bg-white p-2 rounded border border-gray-150 hover:bg-amber-50/30 cursor-pointer">
                                                        <input type="checkbox" name="target_kelas[]" value="<?= $kls['id'] ?>" <?= $checked ?> class="text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                                                        <span><?= htmlspecialchars($kls['nama_kelas']) ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1.5"><i class="fas fa-info-circle mr-1"></i> Centang kelas yang mendapatkan mata pelajaran ini. Kosongkan jika mapel ini umum/tidak terikat kelas.</p>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-end space-x-2 pt-2">
                            <?php if($edit_mode): ?>
                                <a href="master-mapel.php" class="bg-gray-250 hover:bg-gray-300 text-gray-700 font-bold py-2 px-5 rounded-lg text-xs shadow-sm transition">Batal</a>
                            <?php endif; ?>
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-6 rounded-lg text-xs shadow transition flex items-center">
                                <i class="fas fa-save mr-1.5"></i> <?= $edit_mode ? 'Update Mapel' : 'Simpan Mapel' ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- KANAN: TABEL DAFTAR MAPEL -->
                <div class="xl:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200/80 overflow-hidden flex flex-col">
                    <div class="px-6 py-4 border-b border-gray-150 bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h2 class="font-bold text-gray-800 text-sm">Daftar Mata Pelajaran Terdaftar</h2>
                        <div class="relative max-w-xs w-full">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 text-xs">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="mapelSearchInput" onkeyup="filterMapelTable()" class="pl-9 pr-4 py-1.5 w-full border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-amber-500 focus:outline-none" placeholder="Cari mapel...">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Mata Pelajaran</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Kategori</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Mode Belajar</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Status Ajaran</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider">Target Kelas</th>
                                    <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="mapelTableBody" class="divide-y divide-gray-100 text-sm">
                                <?php
                                $sql_list = "SELECT m.*, 
                                             (SELECT GROUP_CONCAT(k.nama_kelas ORDER BY k.nama_kelas ASC SEPARATOR ', ') 
                                              FROM mapel_kelas_target t 
                                              JOIN master_kelas k ON t.kelas_id = k.id 
                                              WHERE t.mapel_id = m.id) as target_kelas
                                             FROM master_mapel m 
                                             ORDER BY m.kategori_mapel, m.nama_mapel ASC";
                                $res_list = $conn->query($sql_list);
                                
                                if ($res_list && $res_list->num_rows > 0):
                                    while($row = $res_list->fetch_assoc()):
                                ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-5 py-3.5 font-bold text-gray-800">
                                                <?= htmlspecialchars($row['nama_mapel']) ?>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-amber-100 text-amber-800 border border-amber-200">
                                                    <?= htmlspecialchars($row['kategori_mapel']) ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <?php if ($row['metode_belajar'] === 'offline'): ?>
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-emerald-100 text-emerald-800 border border-emerald-200 flex items-center w-fit">
                                                        <i class="fas fa-users mr-1"></i> Luring (Offline)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-sky-100 text-sky-800 border border-sky-200 flex items-center w-fit">
                                                        <i class="fas fa-laptop-house mr-1"></i> Daring (Online)
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-5 py-3.5">
                                                <?php if ($row['status_aktif'] == 1): ?>
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-teal-50 text-teal-700 border border-teal-200 flex items-center w-fit">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-teal-500 mr-1.5 animate-pulse"></span> Aktif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-rose-50 text-rose-700 border border-rose-200 flex items-center w-fit">
                                                        <span class="h-1.5 w-1.5 rounded-full bg-rose-450 mr-1.5"></span> Nonaktif
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-5 py-3.5 max-w-[200px]">
                                                <?php if (!empty($row['target_kelas'])): ?>
                                                    <div class="flex flex-wrap gap-1">
                                                        <?php 
                                                        $arr_kls = explode(', ', $row['target_kelas']);
                                                        foreach ($arr_kls as $ak) {
                                                            echo "<span class='px-1.5 py-0.5 text-[9px] font-bold bg-slate-100 border border-slate-200 rounded text-slate-600'>$ak</span>";
                                                        }
                                                        ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-400 italic">Semua Kelas / Umum</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <div class="flex items-center justify-center space-x-1.5">
                                                    <a href="master-mapel.php?edit_id=<?= $row['id'] ?>" class="text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 p-1.5 rounded transition text-xs" title="Edit Mapel">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_mapel']) ?>')" class="text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 p-1.5 rounded transition text-xs" title="Hapus Mapel">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                <?php 
                                    endwhile;
                                else: 
                                ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                            <i class="fas fa-book-open text-4xl mb-2 block text-gray-200"></i>
                                            Belum ada mata pelajaran terdaftar.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </main>
    </div>

    <!-- SCRIPT RESPONSIVE & DELETION CONFIRMATION -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar trigger
            const sidebar = document.getElementById('sidebar-yayasan2');
            const openBtn = document.getElementById('open-sidebar-yayasan2');
            const closeBtn = document.getElementById('close-sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');

            function toggleSidebar() {
                if(sidebar && overlay) { 
                    sidebar.classList.toggle('hidden'); 
                    overlay.classList.toggle('hidden'); 
                }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });

        // Konfirmasi Hapus Data
        function confirmDelete(id, nama) {
            if (confirm("Apakah Bos yakin ingin menghapus mata pelajaran '" + nama + "'? Semua relasi kelas dan data terkait akan ikut terhapus secara permanen.")) {
                window.location.href = "master-mapel.php?hapus_id=" + id;
            }
        }

        // Live search filter table client-side
        function filterMapelTable() {
            const input = document.getElementById("mapelSearchInput");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("mapelTableBody");
            const tr = table.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName("td")[0];
                const tdCat = tr[i].getElementsByTagName("td")[1];
                const tdClasses = tr[i].getElementsByTagName("td")[4];
                
                if (tdName || tdCat || tdClasses) {
                    const textName = (tdName.textContent || tdName.innerText).toUpperCase();
                    const textCat = (tdCat.textContent || tdCat.innerText).toUpperCase();
                    const textClasses = tdClasses ? (tdClasses.textContent || tdClasses.innerText).toUpperCase() : "";
                    
                    if (textName.indexOf(filter) > -1 || textCat.indexOf(filter) > -1 || textClasses.indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>
