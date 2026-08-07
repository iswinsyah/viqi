<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php';

// Check role: musyrif or super_admin
$user_roles = [];
if (isset($_SESSION['ustadz_role'])) {
    $user_roles = explode(',', $_SESSION['ustadz_role']);
}
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;

$is_authorized = false;
$is_super_admin = ($ustadz_id === 9999);

$is_musyrif = false;
$is_musyrifah = false;
$is_kepala_asrama_rijal = false;
$is_kepala_asrama_nisa = false;

if ($is_super_admin) {
    $is_authorized = true;
}
foreach ($user_roles as $role) {
    $norm_role = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
    if (in_array($norm_role, ['super_admin', 'kepala_mahad'])) {
        $is_authorized = true;
    }
    if ($norm_role === 'musyrif') {
        $is_authorized = true;
        $is_musyrif = true;
    }
    if ($norm_role === 'musyrifah') {
        $is_authorized = true;
        $is_musyrifah = true;
    }
    if ($norm_role === 'kepala_asrama' || $norm_role === 'kepala_asrama_rijal') {
        $is_authorized = true;
        $is_kepala_asrama_rijal = true;
    }
    if ($norm_role === 'kepala_asrama_nisa') {
        $is_authorized = true;
        $is_kepala_asrama_nisa = true;
    }
}

if (!$is_authorized) {
    die('Akses ditolak. Halaman ini khusus untuk Musyrif/Musyrifah dan Pengurus Asrama.');
}

$message = '';

// Handle Validation Actions (Approve / Reject / Bulk Approve)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_val'])) {
        $action = $_POST['action_val'];
        
        if ($action === 'single') {
            $id = (int)$_POST['ibadah_id'];
            $status = $conn->real_escape_string($_POST['status_validasi']);
            $catatan = $conn->real_escape_string($_POST['catatan_musyrif'] ?? '');

            $conn->query("UPDATE ibadah_harian_santri SET status_validasi = '$status', catatan_musyrif = '$catatan', validated_by = $ustadz_id, validated_at = NOW() WHERE id = $id");
            $message = '<div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div><i class="fas fa-check-circle mr-2 text-emerald-500"></i>Status validasi berhasil diperbarui!</div><button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div>';
        }
        elseif ($action === 'bulk_approve') {
            $ids = $_POST['ibadah_ids'] ?? [];
            if (!empty($ids)) {
                $clean_ids = implode(',', array_map('intval', $ids));
                $conn->query("UPDATE ibadah_harian_santri SET status_validasi = 'Disetujui', validated_by = $ustadz_id, validated_at = NOW() WHERE id IN ($clean_ids)");
                $message = '<div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex items-center justify-between"><div><i class="fas fa-check-circle mr-2 text-emerald-500"></i>Berhasil menyetujui semua laporan ibadah yang dipilih!</div><button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button></div>';
            }
        }
    }
}

// Query Santri Binaan Musyrif / Musyrifah from Manajemen Halaqoh
$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);
$is_admin_or_kepala = $is_super_admin || !empty(array_intersect($norm_roles, ['super_admin', 'kepala_mahad']));

$santri_binaan_ids = [];
$gender_filter_sql = "";

if (!$is_admin_or_kepala) {
    if ($is_kepala_asrama_rijal) {
        $gender_filter_sql = " AND (s.jenis_kelamin = 'Laki-laki' OR s.jenis_kelamin IS NULL)";
        $res_sb = $conn->query("SELECT id FROM buku_induk_santri WHERE status_santri = 'Aktif'");
        if ($res_sb) {
            while ($r = $res_sb->fetch_assoc()) $santri_binaan_ids[] = $r['id'];
        }
    } elseif ($is_kepala_asrama_nisa) {
        $gender_filter_sql = " AND s.jenis_kelamin = 'Perempuan'";
        $res_sb = $conn->query("SELECT id FROM buku_induk_santri WHERE status_santri = 'Aktif'");
        if ($res_sb) {
            while ($r = $res_sb->fetch_assoc()) $santri_binaan_ids[] = $r['id'];
        }
    } else {
        // Musyrif / Musyrifah
        $res_sb = $conn->query("
            SELECT DISTINCT s.id 
            FROM buku_induk_santri s 
            JOIN halaqoh_anggota a ON s.id = a.santri_id 
            JOIN halaqoh_grup g ON a.grup_id = g.id 
            WHERE g.musyrif_id = $ustadz_id AND s.status_santri = 'Aktif'
        ");
        if ($res_sb) {
            while ($r = $res_sb->fetch_assoc()) $santri_binaan_ids[] = $r['id'];
        }
    }
}

// Build SQL Query for Ibadah Harian Records
$where_clause = "WHERE 1=1";
if (!$is_admin_or_kepala) {
    if (!empty($santri_binaan_ids)) {
        $sb_str = implode(',', $santri_binaan_ids);
        $where_clause .= " AND i.santri_id IN ($sb_str)";
    } else {
        $where_clause .= " AND 1=0"; // Prevent unassigned students from leaking
    }
}
if (!empty($gender_filter_sql)) {
    $where_clause .= $gender_filter_sql;
}

$status_filter = $_GET['status'] ?? 'Pending';
$query_status = "";
if (in_array($status_filter, ['Pending', 'Disetujui', 'Ditolak'])) {
    $query_status = " AND i.status_validasi = '$status_filter'";
}

$query = "
    SELECT i.*, s.nama_lengkap, s.kelas_sekarang, s.jenis_kelamin 
    FROM ibadah_harian_santri i 
    JOIN buku_induk_santri s ON i.santri_id = s.id 
    $where_clause $query_status
    ORDER BY i.tanggal DESC, i.created_at DESC
";
$res_data = $conn->query($query);

// Stats
$res_p = $conn->query("SELECT COUNT(*) as total FROM ibadah_harian_santri i JOIN buku_induk_santri s ON i.santri_id = s.id $where_clause AND i.status_validasi='Pending'");
$total_pending = ($res_p) ? $res_p->fetch_assoc()['total'] : 0;

$res_a = $conn->query("SELECT COUNT(*) as total FROM ibadah_harian_santri i JOIN buku_induk_santri s ON i.santri_id = s.id $where_clause AND i.status_validasi='Disetujui'");
$total_disetujui = ($res_a) ? $res_a->fetch_assoc()['total'] : 0;

$active_menu = 'validasi_ibadah_musyrif';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Ibadah Harian Santri | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-user-check mr-1"></i>Musyrif Validasi
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-emerald-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-200">
                            <i class="fas fa-tasks text-lg"></i>
                        </div>
                        <span>Validasi Ibadah Harian Santri Binaan</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Pemantauan dan verifikasi kejujuran ibadah harian santri oleh Musyrif Pembina</p>
                </div>
            </div>

            <!-- STATISTIC & FILTER TABS -->
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div class="flex items-center gap-2">
                    <a href="?status=Pending" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= ($status_filter === 'Pending') ? 'bg-amber-500 text-white shadow-md shadow-amber-200' : 'bg-white text-slate-600 border hover:bg-slate-50' ?>">
                        <i class="fas fa-clock"></i>
                        <span>Pending Validasi (<?= number_format($total_pending) ?>)</span>
                    </a>
                    <a href="?status=Disetujui" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= ($status_filter === 'Disetujui') ? 'bg-emerald-600 text-white shadow-md shadow-emerald-200' : 'bg-white text-slate-600 border hover:bg-slate-50' ?>">
                        <i class="fas fa-check-circle"></i>
                        <span>Disetujui (<?= number_format($total_disetujui) ?>)</span>
                    </a>
                    <a href="?status=Ditolak" class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 <?= ($status_filter === 'Ditolak') ? 'bg-rose-600 text-white shadow-md shadow-rose-200' : 'bg-white text-slate-600 border hover:bg-slate-50' ?>">
                        <i class="fas fa-times-circle"></i>
                        <span>Ditolak / Catatan</span>
                    </a>
                </div>
            </div>

            <?= $message ?>

            <!-- TABLE WITH BULK ACTION -->
            <form method="POST">
                <input type="hidden" name="action_val" value="bulk_approve">

                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" id="select_all" onclick="toggleSelectAll(this)" class="rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                            <label for="select_all" class="text-xs font-bold text-slate-700 cursor-pointer">Pilih Semua</label>
                        </div>
                        <?php if ($status_filter === 'Pending'): ?>
                            <button type="submit" onclick="return confirm('Setujui semua laporan ibadah yang dipilih?')" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow transition flex items-center gap-1.5">
                                <i class="fas fa-check-double"></i> Setujui Pilihan Pending
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100/70 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-bold tracking-wider">
                                    <th class="py-3.5 px-4 text-center">#</th>
                                    <th class="py-3.5 px-6">Santri & Tanggal</th>
                                    <th class="py-3.5 px-6">Rincian Sholat Wajib</th>
                                    <th class="py-3.5 px-6">Sholat Sunnah & Puasa</th>
                                    <th class="py-3.5 px-6">Status Validasi</th>
                                    <th class="py-3.5 px-6 text-center">Aksi Musyrif</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                                <?php if ($res_data && $res_data->num_rows > 0): ?>
                                    <?php while ($row = $res_data->fetch_assoc()): ?>
                                        <tr class="hover:bg-slate-50 transition">
                                            <td class="py-3.5 px-4 text-center align-top">
                                                <input type="checkbox" name="ibadah_ids[]" value="<?= $row['id'] ?>" class="item-checkbox rounded text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                                            </td>
                                            <td class="py-3.5 px-6 align-top">
                                                <div class="font-bold text-slate-900 text-sm flex items-center gap-2 flex-wrap">
                                                    <i class="fas fa-user-circle text-emerald-600"></i>
                                                    <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                                    <?php if (isset($row['is_haid']) && $row['is_haid'] == 1): ?>
                                                        <span class="bg-pink-100 text-pink-700 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                                                            <i class="fas fa-venus text-[8px]"></i> Haid
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[11px] text-slate-500 mt-1">
                                                    <i class="far fa-calendar-alt text-emerald-600 mr-1"></i><b><?= date('d M Y', strtotime($row['tanggal'])) ?></b>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-6 align-top text-xs space-y-1">
                                                <div><b>Subuh:</b> <span class="text-emerald-700 font-medium"><?= htmlspecialchars($row['sholat_subuh']) ?></span></div>
                                                <div><b>Dhuhur:</b> <span class="text-emerald-700 font-medium"><?= htmlspecialchars($row['sholat_dhuhur']) ?></span></div>
                                                <div><b>Ashar:</b> <span class="text-emerald-700 font-medium"><?= htmlspecialchars($row['sholat_ashar']) ?></span></div>
                                                <div><b>Maghrib:</b> <span class="text-emerald-700 font-medium"><?= htmlspecialchars($row['sholat_maghrib']) ?></span></div>
                                                <div><b>Isya:</b> <span class="text-emerald-700 font-medium"><?= htmlspecialchars($row['sholat_isya']) ?></span></div>
                                            </td>
                                            <td class="py-3.5 px-6 align-top text-xs space-y-1">
                                                <div class="flex flex-wrap gap-1">
                                                    <?php if($row['sholat_tahajud']): ?><span class="bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded font-semibold text-[10px]">Tahajud</span><?php endif; ?>
                                                    <?php if($row['sholat_witir']): ?><span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded font-semibold text-[10px]">Witir</span><?php endif; ?>
                                                    <?php if($row['sholat_dhuha']): ?><span class="bg-amber-50 text-amber-700 px-2 py-0.5 rounded font-semibold text-[10px]">Dhuha</span><?php endif; ?>
                                                    <?php if($row['puasa_senin'] || $row['puasa_kamis']): ?><span class="bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-semibold text-[10px]">Puasa Sunnah</span><?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3.5 px-6 align-top">
                                                <?php
                                                $st = $row['status_validasi'];
                                                if ($st === 'Disetujui') echo '<span class="bg-emerald-100 text-emerald-800 border border-emerald-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-check-circle mr-1"></i>Disetujui</span>';
                                                elseif ($st === 'Ditolak') echo '<span class="bg-rose-100 text-rose-800 border border-rose-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-times-circle mr-1"></i>Ditolak</span>';
                                                else echo '<span class="bg-amber-100 text-amber-800 border border-amber-300 px-2.5 py-1 rounded-lg text-xs font-bold"><i class="fas fa-clock mr-1"></i>Pending</span>';
                                                ?>
                                                <?php if(!empty($row['catatan_musyrif'])): ?>
                                                    <div class="text-[11px] text-rose-600 mt-1 italic">"<?= htmlspecialchars($row['catatan_musyrif']) ?>"</div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3.5 px-6 align-top text-center">
                                                <!-- BUTTON TRIGGER VALIDATION MODAL -->
                                                <button type="button" onclick="openModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>', '<?= $row['status_validasi'] ?>', '<?= htmlspecialchars(addslashes($row['catatan_musyrif'] ?? '')) ?>')" 
                                                    class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                                    <i class="fas fa-edit mr-1"></i> Validasi
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="py-10 text-center text-slate-400 italic">Tidak ada data laporan ibadah harian dengan status <b><?= htmlspecialchars($status_filter) ?></b>.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>

        </main>
    </div>

    <!-- VALIDATION MODAL -->
    <div id="validasiModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 bg-emerald-800 text-white flex items-center justify-between">
                <h3 class="font-bold text-base flex items-center gap-2">
                    <i class="fas fa-clipboard-check text-emerald-300"></i>
                    <span>Validasi Ibadah Santri</span>
                </h3>
                <button type="button" onclick="closeModal()" class="text-emerald-200 hover:text-white"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="action_val" value="single">
                <input type="hidden" name="ibadah_id" id="modal_ibadah_id">

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Nama Santri</label>
                    <div id="modal_santri_nama" class="font-bold text-slate-900 text-sm bg-slate-100 px-3 py-2 rounded-lg"></div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Keputusan Validasi <span class="text-rose-500">*</span></label>
                    <select name="status_validasi" id="modal_status_validasi" required class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 text-sm">
                        <option value="Disetujui">Disetujui (Sesuai & Jujur)</option>
                        <option value="Ditolak">Ditolak (Tidak Sesuai / Perlu Diperbaiki)</option>
                        <option value="Pending">Pending (Menunggu Peninjauan)</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Catatan Musyrif (Bimbingan / Solusi)</label>
                    <textarea name="catatan_musyrif" id="modal_catatan_musyrif" rows="3" class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-emerald-500" placeholder="Tambahkan bimbingan, motivasi, atau solusi untuk santri..."></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 rounded-xl border text-xs font-bold text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-200">Simpan Validasi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }

        function toggleSelectAll(source) {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function openModal(id, nama, status, catatan) {
            document.getElementById('modal_ibadah_id').value = id;
            document.getElementById('modal_santri_nama').textContent = nama;
            document.getElementById('modal_status_validasi').value = status;
            document.getElementById('modal_catatan_musyrif').value = catatan;
            document.getElementById('validasiModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('validasiModal').classList.add('hidden');
        }

    </script>
</body>
</html>
