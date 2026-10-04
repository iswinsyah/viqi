<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'kontrol_jam_kosong';
$ustadz_id = $_SESSION['ustadz_id'];
$today = date('Y-m-d');
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}
$user_roles = array_map('trim', $user_roles);
$is_admin = in_array('admin_sekolah', $user_roles) || in_array('super_admin', $user_roles);

// Database self-healing
$conn->query("CREATE TABLE IF NOT EXISTS kontrol_jam_kosong (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    kelas VARCHAR(50) NOT NULL,
    mapel VARCHAR(100) NOT NULL,
    guru_utama_id INT NOT NULL,
    guru_pengganti_id INT NULL,
    status_kontrol ENUM('Perlu Pengganti', 'Terisi', 'Batal') DEFAULT 'Perlu Pengganti',
    catatan TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pesan_sukses = '';
$pesan_error = '';

// Handle POST actions (Create / Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$is_admin) {
        $pesan_error = "Akses ditolak: Hanya Admin Sekolah yang berhak mengelola data jam kosong.";
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
            $tanggal = $conn->real_escape_string($_POST['tanggal']);
            $kelas = $conn->real_escape_string($_POST['kelas']);
            $mapel = $conn->real_escape_string($_POST['mapel']);
            $guru_utama_id = (int)$_POST['guru_utama_id'];
            $guru_pengganti_id = !empty($_POST['guru_pengganti_id']) ? (int)$_POST['guru_pengganti_id'] : 'NULL';
            $status_kontrol = $guru_pengganti_id !== 'NULL' ? 'Terisi' : 'Perlu Pengganti';
            $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');

            $sql = "INSERT INTO kontrol_jam_kosong (tanggal, kelas, mapel, guru_utama_id, guru_pengganti_id, status_kontrol, catatan, created_by)
                    VALUES ('$tanggal', '$kelas', '$mapel', $guru_utama_id, $guru_pengganti_id, '$status_kontrol', '$catatan', $ustadz_id)";
            if ($conn->query($sql)) {
                $pesan_sukses = "Log jam kosong baru berhasil ditambahkan!";
            } else {
                $pesan_error = "Gagal menyimpan data: " . $conn->error;
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update_pengganti') {
            $id = (int)$_POST['id'];
            $guru_pengganti_id = !empty($_POST['guru_pengganti_id']) ? (int)$_POST['guru_pengganti_id'] : 'NULL';
            $status_kontrol = $guru_pengganti_id !== 'NULL' ? 'Terisi' : 'Perlu Pengganti';
            $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');

            $sql = "UPDATE kontrol_jam_kosong SET 
                    guru_pengganti_id = $guru_pengganti_id, 
                    status_kontrol = '$status_kontrol', 
                    catatan = '$catatan' 
                    WHERE id = $id";
            if ($conn->query($sql)) {
                $pesan_sukses = "Data guru pengganti berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal memperbarui data: " . $conn->error;
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'batal') {
            $id = (int)$_POST['id'];
            $sql = "UPDATE kontrol_jam_kosong SET status_kontrol = 'Batal' WHERE id = $id";
            if ($conn->query($sql)) {
                $pesan_sukses = "Jurnal jam kosong dibatalkan.";
            } else {
                $pesan_error = "Gagal memperbarui status: " . $conn->error;
            }
        }
    }
}

// Fetch all Ustadz/Asatidz for dropdowns
$asatidz_list = [];
$res_ast = $conn->query("SELECT id, nama FROM akun_ustadz ORDER BY nama ASC");
if ($res_ast) {
    while ($row = $res_ast->fetch_assoc()) {
        $asatidz_list[] = $row;
    }
}

// Fetch classes from master_kelas
$kelas_list = [];
$res_kls = $conn->query("SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kls && $res_kls->num_rows > 0) {
    while ($row = $res_kls->fetch_assoc()) {
        $kelas_list[] = $row['nama_kelas'];
    }
} else {
    $kelas_list = ['Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12', 'Halaqoh Rijal', 'Halaqoh Nisa'];
}

// Fetch mapel from master_mapel
$mapel_list = [];
$res_mpl = $conn->query("SELECT nama_mapel FROM master_mapel WHERE status_aktif = 1 ORDER BY nama_mapel ASC");
if ($res_mpl && $res_mpl->num_rows > 0) {
    while ($row = $res_mpl->fetch_assoc()) {
        $mapel_list[] = $row['nama_mapel'];
    }
} else {
    $mapel_list = ['Tahfidz Al-Qur\'an', 'Aqidah Akhlak', 'Fiqih', 'Hadits', 'Bahasa Arab', 'Bahasa Inggris', 'Matematika'];
}

// Filter variables
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Fetch logs for current filter month
$sql_logs = "SELECT k.*, u1.nama as nama_guru_utama, u2.nama as nama_guru_pengganti, uc.nama as nama_creator
             FROM kontrol_jam_kosong k
             JOIN akun_ustadz u1 ON k.guru_utama_id = u1.id
             LEFT JOIN akun_ustadz u2 ON k.guru_pengganti_id = u2.id
             LEFT JOIN akun_ustadz uc ON k.created_by = uc.id
             WHERE MONTH(k.tanggal) = $filter_bulan AND YEAR(k.tanggal) = $filter_tahun
             ORDER BY k.tanggal DESC, k.created_at DESC";
$res_logs = $conn->query($sql_logs);
$logs = ($res_logs) ? $res_logs->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Jam Kosong | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800">SADIGS 4.0 (Administrasi Sekolah)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-cyan-600 flex items-center justify-center text-white font-bold shadow-sm">
                <?= strtoupper(substr($_SESSION['ustadz_nama'], 0, 1)) ?>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-500 flex items-center justify-center text-white shadow-md shadow-cyan-200">
                            <i class="fas fa-calendar-times text-lg"></i>
                        </div>
                        <span>Kontrol Jam Pelajaran Kosong</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Kelola guru pengganti/inval untuk mencegah kekosongan jam belajar mengajar.</p>
                </div>
            </div>

            <!-- MESSAGES -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-sm text-rose-600"></i> <?= $pesan_error ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
                
                <!-- FORM CARD (Only for school admins) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-cyan-800 to-cyan-700 text-white flex items-center justify-between">
                            <h2 class="font-bold text-sm flex items-center gap-2">
                                <i class="fas fa-plus-circle text-cyan-300"></i>
                                <span>Log Jam Kosong Baru</span>
                            </h2>
                        </div>
                        
                        <?php if ($is_admin): ?>
                            <form method="POST" class="p-6 space-y-4">
                                <input type="hidden" name="action" value="tambah">
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Kejadian <span class="text-rose-500">*</span></label>
                                    <input type="date" name="tanggal" value="<?= $today ?>" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Kelas <span class="text-rose-500">*</span></label>
                                    <select name="kelas" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($kelas_list as $kls): ?>
                                            <option value="<?= htmlspecialchars($kls) ?>"><?= htmlspecialchars($kls) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Mata Pelajaran <span class="text-rose-500">*</span></label>
                                    <select name="mapel" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                        <option value="">-- Pilih Mapel --</option>
                                        <?php foreach ($mapel_list as $mpl): ?>
                                            <option value="<?= htmlspecialchars($mpl) ?>"><?= htmlspecialchars($mpl) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Guru Utama (Absen) <span class="text-rose-500">*</span></label>
                                    <select name="guru_utama_id" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                        <option value="">-- Pilih Guru Utama --</option>
                                        <?php foreach ($asatidz_list as $ast): ?>
                                            <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Guru Pengganti (Inval)</label>
                                    <select name="guru_pengganti_id" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                        <option value="">-- Pilih Guru Pengganti (Opsional) --</option>
                                        <?php foreach ($asatidz_list as $ast): ?>
                                            <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-[10px] text-gray-400 mt-1">Kosongkan jika pengganti belum dikonfirmasi.</p>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Catatan</label>
                                    <textarea name="catatan" rows="3" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Contoh: Guru sakit, materi ditinggalkan halaman 10..."></textarea>
                                </div>

                                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-4 rounded-xl transition text-xs shadow-md shadow-cyan-100 flex items-center justify-center gap-1.5">
                                    <i class="fas fa-save"></i>
                                    <span>Simpan Log Jam Kosong</span>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="p-6 text-center text-xs text-gray-400 italic">
                                Anda hanya diperbolehkan membaca data. Hubungi Admin Sekolah untuk pengisian log jam kosong.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- LIST CARD -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden p-6">
                        <!-- Filters -->
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6 border-b pb-4">
                            <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wider flex items-center gap-1.5">
                                <i class="fas fa-history text-cyan-600"></i> Riwayat Jam Kosong Bulanan
                            </h3>
                            <form method="GET" class="flex gap-2">
                                <select name="bulan" class="px-2.5 py-1.5 border rounded-lg text-xs bg-gray-50 font-semibold text-gray-700 focus:outline-none">
                                    <?php for ($m=1; $m<=12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $filter_bulan === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                                    <?php endfor; ?>
                                </select>
                                <select name="tahun" class="px-2.5 py-1.5 border rounded-lg text-xs bg-gray-50 font-semibold text-gray-700 focus:outline-none">
                                    <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
                                        <option value="<?= $y ?>" <?= $filter_tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="bg-cyan-650 hover:bg-cyan-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                                    Filter
                                </button>
                            </form>
                        </div>

                        <!-- Data table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-150 text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-500">
                                        <th class="px-3 py-2 text-left font-bold">Tanggal & Kelas</th>
                                        <th class="px-3 py-2 text-left font-bold">Mata Pelajaran</th>
                                        <th class="px-3 py-2 text-left font-bold">Guru Utama</th>
                                        <th class="px-3 py-2 text-left font-bold">Guru Pengganti</th>
                                        <th class="px-3 py-2 text-center font-bold">Status</th>
                                        <?php if ($is_admin): ?>
                                            <th class="px-3 py-2 text-center font-bold">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="<?= $is_admin ? 6 : 5 ?>" class="px-3 py-6 text-center text-gray-400 italic">Belum ada data jam pelajaran kosong pada bulan ini.</td>
                                        </tr>
                                    <?php else: foreach ($logs as $l): ?>
                                        <tr class="hover:bg-slate-50/50 transition">
                                            <td class="px-3 py-3">
                                                <div class="font-bold text-gray-800"><?= date('d/m/Y', strtotime($l['tanggal'])) ?></div>
                                                <div class="text-[10px] text-gray-500 font-medium"><?= htmlspecialchars($l['kelas']) ?></div>
                                            </td>
                                            <td class="px-3 py-3 font-semibold text-gray-700"><?= htmlspecialchars($l['mapel']) ?></td>
                                            <td class="px-3 py-3 font-semibold text-rose-700"><i class="fas fa-user-times mr-1"></i><?= htmlspecialchars($l['nama_guru_utama']) ?></td>
                                            <td class="px-3 py-3">
                                                <?php if ($l['guru_pengganti_id']): ?>
                                                    <span class="font-semibold text-emerald-700"><i class="fas fa-user-check mr-1"></i><?= htmlspecialchars($l['nama_guru_pengganti']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-amber-600 font-medium italic"><i class="fas fa-spinner fa-spin mr-1 text-[10px]"></i>Mencari pengganti...</span>
                                                <?php endif; ?>
                                                <?php if(!empty($l['catatan'])): ?><div class="text-[10px] text-gray-400 italic mt-0.5"><?= htmlspecialchars($l['catatan']) ?></div><?php endif; ?>
                                            </td>
                                            <td class="px-3 py-3 text-center">
                                                <?php if ($l['status_kontrol'] === 'Terisi'): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-800">Terisi</span>
                                                <?php elseif ($l['status_kontrol'] === 'Batal'): ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-gray-100 text-gray-800">Batal</span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800 animate-pulse">Butuh Pengganti</span>
                                                <?php endif; ?>
                                            </td>
                                            <?php if ($is_admin): ?>
                                                <td class="px-3 py-3 text-center space-x-1.5 whitespace-nowrap">
                                                    <?php if ($l['status_kontrol'] !== 'Batal'): ?>
                                                        <button type="button" onclick="bukaModalEditPengganti(<?= $l['id'] ?>, <?= (int)$l['guru_pengganti_id'] ?>, '<?= htmlspecialchars(addslashes($l['catatan'] ?? '')) ?>')" class="bg-cyan-50 hover:bg-cyan-100 text-cyan-700 font-bold px-2 py-1 rounded text-[10px] border border-cyan-200 transition">
                                                            Pilih Pengganti
                                                        </button>
                                                        <a href="?batal_id=<?= $l['id'] ?>" onclick="event.preventDefault(); if(confirm('Batalkan log jam kosong ini?')) { document.getElementById('form-batal-<?= $l['id'] ?>').submit(); }" class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-2 py-1 rounded text-[10px] border border-rose-200 transition">
                                                            Batal
                                                        </a>
                                                        <form id="form-batal-<?= $l['id'] ?>" method="POST" action="admin-kontrol-jam-kosong.php" class="hidden">
                                                            <input type="hidden" name="action" value="batal">
                                                            <input type="hidden" name="id" value="<?= $l['id'] ?>">
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 italic text-[10px]">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL EDIT GURU PENGGANTI -->
            <div id="modal-edit-pengganti" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="tutupModalEditPengganti()"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                        <div class="flex justify-between items-start border-b pb-2 mb-4">
                            <h3 class="text-sm font-bold text-gray-800">Pilih Guru Pengganti (Inval)</h3>
                            <button type="button" onclick="tutupModalEditPengganti()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-base"></i></button>
                        </div>
                        <form method="POST" action="admin-kontrol-jam-kosong.php" class="space-y-4">
                            <input type="hidden" name="action" value="update_pengganti">
                            <input type="hidden" name="id" id="edit-log-id">
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Guru Pengganti</label>
                                <select name="guru_pengganti_id" id="edit-guru-pengganti-id" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500 bg-white">
                                    <option value="">-- Pilih Guru Pengganti --</option>
                                    <?php foreach ($asatidz_list as $ast): ?>
                                        <option value="<?= $ast['id'] ?>"><?= htmlspecialchars($ast['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Keterangan / Catatan Tambahan</label>
                                <textarea name="catatan" id="edit-catatan" rows="3" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500"></textarea>
                            </div>
                            
                            <div class="flex justify-end gap-2 pt-2 border-t">
                                <button type="button" onclick="tutupModalEditPengganti()" class="bg-gray-100 text-gray-700 font-bold px-4 py-2 rounded-xl text-xs transition hover:bg-gray-200">
                                    Batal
                                </button>
                                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md">
                                    Simpan Guru Pengganti
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
            document.getElementById('sidebar-hr').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
        });

        function bukaModalEditPengganti(id, penggantiId, catatan) {
            document.getElementById('edit-log-id').value = id;
            document.getElementById('edit-guru-pengganti-id').value = penggantiId || '';
            document.getElementById('edit-catatan').value = catatan || '';
            document.getElementById('modal-edit-pengganti').classList.remove('hidden');
        }

        function tutupModalEditPengganti() {
            document.getElementById('modal-edit-pengganti').classList.add('hidden');
        }
    </script>
</body>
</html>
