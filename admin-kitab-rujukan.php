<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Cek Otoritas Akses Menu
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

// Ambil permission allowed_roles dari db
$res_perm = $conn->query("SELECT allowed_roles FROM menu_permissions WHERE menu_key = 'kitab_rujukan' LIMIT 1");
$allowed_roles = [];
if ($res_perm && $res_perm->num_rows > 0) {
    $row_p = $res_perm->fetch_assoc();
    $allowed_roles = !empty($row_p['allowed_roles']) ? explode(',', $row_p['allowed_roles']) : [];
}

// Normalisasi untuk pencocokan tangguh
$norm_user_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);
$norm_allowed_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $allowed_roles);
$is_super_admin = in_array('super_admin', $norm_user_roles);

if (!$is_super_admin && empty(array_intersect($norm_allowed_roles, $norm_user_roles))) {
    echo "<div style='color: red; padding: 20px; font-weight: bold; text-align: center; font-family: sans-serif; margin-top: 50px;'>
            Anda tidak memiliki hak akses untuk membuka halaman ini.
          </div>";
    exit;
}

// Self-healing database: Buat tabel master_kitab jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS master_kitab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kitab VARCHAR(255) NOT NULL,
    pengarang VARCHAR(255) NULL,
    penerbit VARCHAR(255) NULL,
    harga DECIMAL(15,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pesan_sukses = "";
$pesan_error = "";

// 1. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    if ($conn->query("DELETE FROM master_kitab WHERE id = $id")) {
        header("Location: admin-kitab-rujukan.php?sukses=" . urlencode("Kitab rujukan berhasil dihapus!"));
    } else {
        header("Location: admin-kitab-rujukan.php?error=" . urlencode("Gagal menghapus data: " . $conn->error));
    }
    exit;
}

// 2. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama_kitab = $conn->real_escape_string($_POST['nama_kitab']);
    $pengarang = $conn->real_escape_string($_POST['pengarang']);
    $penerbit = $conn->real_escape_string($_POST['penerbit']);
    $harga = (double)$_POST['harga'];

    if ($id > 0) {
        $sql = "UPDATE master_kitab SET nama_kitab='$nama_kitab', pengarang='$pengarang', penerbit='$penerbit', harga=$harga WHERE id=$id";
        $pesan_sukses = "Data kitab rujukan berhasil diperbarui!";
    } else {
        $sql = "INSERT INTO master_kitab (nama_kitab, pengarang, penerbit, harga) VALUES ('$nama_kitab', '$pengarang', '$penerbit', $harga)";
        $pesan_sukses = "Kitab rujukan baru berhasil ditambahkan!";
    }

    if ($conn->query($sql)) {
        header("Location: admin-kitab-rujukan.php?sukses=" . urlencode($pesan_sukses));
        exit;
    } else {
        $pesan_error = "Gagal menyimpan data: " . $conn->error;
    }
}

if (isset($_GET['sukses'])) {
    $pesan_sukses = $_GET['sukses'];
}
if (isset($_GET['error'])) {
    $pesan_error = $_GET['error'];
}

// 3. Ambil data untuk mode edit
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res_edit = $conn->query("SELECT * FROM master_kitab WHERE id = $id");
    if ($res_edit && $res_edit->num_rows > 0) {
        $data_edit = $res_edit->fetch_assoc();
    }
}

// 4. Ambil Statistik Ringkasan
$tot_kitab = $conn->query("SELECT COUNT(id) as total FROM master_kitab")->fetch_assoc()['total'] ?? 0;
$tot_nilai = $conn->query("SELECT SUM(harga) as total FROM master_kitab")->fetch_assoc()['total'] ?? 0.0;
$avg_harga = $conn->query("SELECT AVG(harga) as total FROM master_kitab")->fetch_assoc()['total'] ?? 0.0;
$uniq_penerbit = $conn->query("SELECT COUNT(DISTINCT penerbit) as total FROM master_kitab WHERE penerbit != ''")->fetch_assoc()['total'] ?? 0;

$active_menu = 'kitab_rujukan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kitab Rujukan | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-semibold bg-cyan-100 text-cyan-800 px-3 py-1 rounded-full"><i class="fas fa-user-tie mr-1"></i> Admin Asatidz</span>
            </div>
        </header>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/50 p-6">
            <!-- TITLE BAR -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book-open text-cyan-600 mr-2.5"></i>Daftar Kitab Rujukan</h1>
                <p class="text-xs text-gray-500 mt-1">Mengelola inventaris buku dan kitab rujukan kurikulum sekolah & asrama.</p>
            </div>

            <!-- NOTIFIKASI -->
            <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 text-emerald-800 border border-emerald-250 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-check-circle mr-2 text-lg text-emerald-600"></i> <?= htmlspecialchars($pesan_sukses) ?>
                </div>
            <?php endif; ?>

            <?php if(!empty($pesan_error)): ?>
                <div class="bg-rose-100 text-rose-800 border border-rose-200 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2 text-lg text-rose-600"></i> <?= htmlspecialchars($pesan_error) ?>
                </div>
            <?php endif; ?>

            <!-- STATISTIK WIDGET -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Judul Kitab</span>
                        <span class="text-2xl font-black text-slate-800"><?= $tot_kitab ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg"><i class="fas fa-atlas"></i></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Total Nilai Buku</span>
                        <span class="text-2xl font-black text-emerald-600">Rp <?= number_format($tot_nilai, 0, ',', '.') ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg"><i class="fas fa-tags"></i></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Rata-rata Harga</span>
                        <span class="text-2xl font-black text-indigo-600">Rp <?= number_format($avg_harga, 0, ',', '.') ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg"><i class="fas fa-calculator"></i></div>
                </div>
                <div class="bg-white p-4 rounded-xl border border-gray-150 shadow-sm flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1">Penerbit Terdaftar</span>
                        <span class="text-2xl font-black text-cyan-600"><?= $uniq_penerbit ?></span>
                    </div>
                    <div class="w-10 h-10 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-lg"><i class="fas fa-print"></i></div>
                </div>
            </div>

            <!-- GRID FORMS & TABLE -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                
                <!-- KIRI: FORM TAMBAH/EDIT -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 overflow-hidden flex flex-col h-fit">
                    <div class="px-6 py-4 bg-slate-800 text-white flex items-center space-x-2">
                        <i class="fas <?= $edit_mode ? 'fa-edit text-cyan-300' : 'fa-plus-circle text-cyan-300' ?> text-lg"></i>
                        <h2 class="font-bold text-sm"><?= $edit_mode ? 'Edit Data Kitab Rujukan' : 'Tambah Kitab Rujukan Baru' ?></h2>
                    </div>

                    <form action="admin-kitab-rujukan.php" method="POST" class="p-6 space-y-5">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Nama Kitab</label>
                            <input type="text" name="nama_kitab" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_kitab']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm" placeholder="Contoh: Tafsir Jalalain">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Pengarang / Penulis</label>
                            <input type="text" name="pengarang" value="<?= $edit_mode ? htmlspecialchars($data_edit['pengarang']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm" placeholder="Contoh: Jalaluddin al-Mahalli & Jalaluddin as-Suyuthi">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Penerbit</label>
                            <input type="text" name="penerbit" value="<?= $edit_mode ? htmlspecialchars($data_edit['penerbit']) : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm" placeholder="Contoh: Darul Hadits Kairo">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Harga Beli / Satuan (Rp)</label>
                            <input type="number" step="1" name="harga" value="<?= $edit_mode ? htmlspecialchars($data_edit['harga']) : '0' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm" placeholder="Contoh: 125000">
                        </div>

                        <div class="text-right pt-2">
                            <?php if ($edit_mode): ?>
                                <a href="admin-kitab-rujukan.php" class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-4 rounded-lg text-sm transition-colors mr-1">Batal</a>
                            <?php endif; ?>
                            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-6 rounded-lg text-sm shadow transition-colors">
                                <i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Perbarui Data' : 'Simpan Kitab' ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- KANAN: TABEL DAFTAR KITAB -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200/80 overflow-hidden xl:col-span-2 flex flex-col">
                    <div class="px-6 py-4 bg-slate-50 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <h2 class="font-bold text-gray-800 text-sm flex items-center">
                            <i class="fas fa-list mr-2 text-cyan-600"></i>Daftar Kitab Terdaftar
                        </h2>
                        <!-- SEARCH BAR -->
                        <div class="relative w-full sm:w-64">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-search text-gray-400 text-xs"></i>
                            </span>
                            <input type="text" id="searchKitab" onkeyup="filterKitabTable()" class="w-full pl-9 pr-4 py-1.5 bg-white border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500" placeholder="Cari nama kitab, pengarang...">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Nama Kitab</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Pengarang</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Penerbit</th>
                                    <th class="px-5 py-3 text-xs font-bold text-gray-500 uppercase tracking-wider text-left">Harga</th>
                                    <th class="px-5 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="kitabTableBody" class="divide-y divide-gray-100 text-sm">
                                <?php
                                $res_list = $conn->query("SELECT * FROM master_kitab ORDER BY nama_kitab ASC");
                                if ($res_list && $res_list->num_rows > 0):
                                    while($row = $res_list->fetch_assoc()):
                                ?>
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-5 py-3.5 font-bold text-gray-800">
                                                <i class="fas fa-book text-cyan-700/70 mr-1.5"></i> <?= htmlspecialchars($row['nama_kitab']) ?>
                                            </td>
                                            <td class="px-5 py-3.5 text-gray-600 text-xs">
                                                <?= !empty($row['pengarang']) ? htmlspecialchars($row['pengarang']) : '<span class="text-gray-400 italic">Tidak ada data</span>' ?>
                                            </td>
                                            <td class="px-5 py-3.5 text-gray-600 text-xs">
                                                <?= !empty($row['penerbit']) ? htmlspecialchars($row['penerbit']) : '<span class="text-gray-400 italic">Tidak ada data</span>' ?>
                                            </td>
                                            <td class="px-5 py-3.5 font-bold text-emerald-700 text-xs">
                                                Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                                            </td>
                                            <td class="px-5 py-3.5 text-center">
                                                <div class="flex items-center justify-center space-x-1.5">
                                                    <a href="admin-kitab-rujukan.php?edit_id=<?= $row['id'] ?>" class="text-cyan-600 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 p-1.5 rounded transition text-xs" title="Edit Kitab">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="javascript:void(0)" onclick="confirmDelete(<?= $row['id'] ?>, '<?= htmlspecialchars($row['nama_kitab']) ?>')" class="text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 p-1.5 rounded transition text-xs" title="Hapus Kitab">
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
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                            <i class="fas fa-book text-4xl mb-2 block text-gray-200"></i>
                                            Belum ada kitab rujukan terdaftar.
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

    <!-- SCRIPT RESPONSIVE & DELETION CONFIRMATION & LIVE FILTER -->
    <script>
        // Konfirmasi Hapus
        // Konfirmasi Hapus Data
        function confirmDelete(id, name) {
            if (confirm(`Apakah Anda yakin ingin menghapus kitab rujukan "${name}"?`)) {
                window.location.href = `admin-kitab-rujukan.php?hapus_id=${id}`;
            }
        }

        // Live search filter table
        function filterKitabTable() {
            const input = document.getElementById('searchKitab');
            const filter = input.value.toLowerCase();
            const tableBody = document.getElementById('kitabTableBody');
            const trs = tableBody.getElementsByTagName('tr');

            for (let i = 0; i < trs.length; i++) {
                const tr = trs[i];
                // Skip row jika itu adalah fallback "Belum ada kitab"
                if (tr.getElementsByTagName('td').length < 5) continue;
                
                const tdName = tr.getElementsByTagName('td')[0];
                const tdAuthor = tr.getElementsByTagName('td')[1];
                const tdPublisher = tr.getElementsByTagName('td')[2];

                if (tdName && tdAuthor && tdPublisher) {
                    const textName = tdName.textContent || tdName.innerText;
                    const textAuthor = tdAuthor.textContent || tdAuthor.innerText;
                    const textPublisher = tdPublisher.textContent || tdPublisher.innerText;

                    if (textName.toLowerCase().indexOf(filter) > -1 || 
                        textAuthor.toLowerCase().indexOf(filter) > -1 || 
                        textPublisher.toLowerCase().indexOf(filter) > -1) {
                        tr.style.display = "";
                    } else {
                        tr.style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>
