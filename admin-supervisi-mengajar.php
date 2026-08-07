<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'supervisi_mengajar';

// Get current user details from session
$current_ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$current_user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_super_admin = ($current_ustadz_id === 9999);

$allowed_roles = ['kepala_sekolah', 'kepala_mahad'];
$has_access = $is_super_admin || !empty(array_intersect($allowed_roles, array_map('trim', $current_user_roles)));

if (!$has_access) {
    die("Akses ditolak. Halaman ini hanya dapat diakses oleh Kepala Sekolah dan Pengurus Yayasan.");
}

$pesan_sukses = '';
$pesan_error = '';

// Set selected month and year
$selected_period = $_GET['periode'] ?? date('Y-m');
list($selected_year, $selected_month) = explode('-', $selected_period);
$selected_month = (int)$selected_month;
$selected_year = (int)$selected_year;

// --- HANDLE SUPERVISI LOG FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_supervisi_log'])) {
    $supervised_ustadz_id = (int)$_POST['supervised_ustadz_id'];
    $tanggal_supervisi = $conn->real_escape_string($_POST['tanggal_supervisi']);
    $skor = (int)$_POST['skor'];
    $catatan = $conn->real_escape_string($_POST['catatan'] ?? '');
    
    if ($supervised_ustadz_id > 0 && !empty($tanggal_supervisi) && $skor >= 0 && $skor <= 100) {
        $sql = "INSERT INTO supervisi_mengajar (user_id, tanggal_supervisi, skor, catatan, supervisor_id) 
                VALUES ($supervised_ustadz_id, '$tanggal_supervisi', $skor, '$catatan', $current_ustadz_id)";
        if ($conn->query($sql)) {
            $pesan_sukses = "Log supervisi pengajaran berhasil disimpan!";
        } else {
            $pesan_error = "Gagal menyimpan log supervisi: " . $conn->error;
        }
    } else {
        $pesan_error = "Semua field supervisi wajib diisi dengan benar!";
    }
}

// --- HANDLE DELETING SUPERVISI LOG ---
if (isset($_GET['action']) && $_GET['action'] === 'delete_supervisi' && isset($_GET['sup_id'])) {
    $sup_id = (int)$_GET['sup_id'];
    $sql_del = "DELETE FROM supervisi_mengajar WHERE id = $sup_id AND (supervisor_id = $current_ustadz_id OR $is_super_admin)";
    if ($conn->query($sql_del)) {
        $pesan_sukses = "Log supervisi berhasil dihapus.";
        header("Location: admin-supervisi-mengajar.php?periode=$selected_period");
        exit;
    }
}

// Fetch all staff members that have the 'ustadz' or 'ustadzah' role
$res_guru = $conn->query("SELECT id, nama FROM akun_ustadz WHERE role LIKE '%ustadz%' OR role LIKE '%ustadzah%' OR role LIKE '%guru%' ORDER BY nama ASC");
$guru_list = [];
if ($res_guru) {
    while ($row = $res_guru->fetch_assoc()) {
        $guru_list[] = $row;
    }
}

// Fetch supervisions in selected month/year
$res_sup_list = $conn->query("SELECT s.*, u.nama as nama_guru, sv.nama as nama_supervisor 
    FROM supervisi_mengajar s 
    JOIN akun_ustadz u ON s.user_id = u.id 
    LEFT JOIN akun_ustadz sv ON s.supervisor_id = sv.id
    WHERE MONTH(s.tanggal_supervisi) = $selected_month AND YEAR(s.tanggal_supervisi) = $selected_year 
    ORDER BY s.tanggal_supervisi DESC");
$supervisi_history = [];
if ($res_sup_list) {
    while ($row = $res_sup_list->fetch_assoc()) {
        $supervisi_history[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisi Mengajar | Portal Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-750 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 hidden sm:block">Portal Kepegawaian Asatidz</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6 text-left">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-clipboard-check text-indigo-600"></i>
                        <span>Supervisi Mengajar Ustadz</span>
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">Formulir pencatatan hasil supervisi klinis KBM kelas dan riwayat log supervisi pengajaran bulanan.</p>
                </div>
                <!-- Month Filter -->
                <form method="GET" class="flex items-center gap-2">
                    <input type="month" name="periode" value="<?= $selected_period ?>" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-slate-350 rounded-xl text-xs font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </form>
            </div>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-sm text-emerald-600"></i> <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-850 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-sm text-rose-600"></i> <?= $pesan_error ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Form Input Log Supervisi -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 xl:col-span-1 h-fit">
                    <h2 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-1.5 border-b pb-2">
                        <i class="fas fa-plus-circle text-indigo-600"></i>
                        <span>Input Supervisi Pengajaran</span>
                    </h2>
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Ustadz/Ustadzah yang Disupervisi</label>
                            <select name="supervised_ustadz_id" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" required>
                                <option value="">-- Pilih Guru --</option>
                                <?php foreach ($guru_list as $g): ?>
                                    <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Tanggal Supervisi</label>
                            <input type="date" name="tanggal_supervisi" value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Skor Kinerja KBM (0 - 100)</label>
                            <input type="number" name="skor" min="0" max="100" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" required placeholder="Contoh: 85">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-1">Catatan Evaluasi / Rekomendasi</label>
                            <textarea name="catatan" rows="4" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500" placeholder="Tulis masukan untuk guru di sini..."></textarea>
                        </div>
                        <button type="submit" name="save_supervisi_log" class="w-full bg-indigo-650 hover:bg-indigo-700 text-white font-bold py-2.5 rounded-xl transition text-xs flex items-center justify-center gap-1.5 shadow-sm">
                            <i class="fas fa-plus"></i> Simpan Hasil Supervisi
                        </button>
                    </form>
                </div>

                <!-- Tabel Riwayat Supervisi Bulan Ini -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 xl:col-span-2">
                    <h2 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-1.5 border-b pb-2">
                        <i class="fas fa-history text-indigo-600"></i>
                        <span>Daftar Supervisi yang Dilakukan Bulan Ini</span>
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b text-[10px] font-bold text-slate-400 uppercase tracking-wider bg-slate-50/50">
                                    <th class="px-4 py-3">Nama Ustadz/ah</th>
                                    <th class="px-4 py-3">Tanggal</th>
                                    <th class="px-4 py-3 text-center">Skor</th>
                                    <th class="px-4 py-3">Supervisor</th>
                                    <th class="px-4 py-3">Catatan/Evaluasi</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($supervisi_history)): ?>
                                    <?php foreach ($supervisi_history as $sh): ?>
                                        <tr class="border-b hover:bg-slate-50/50">
                                            <td class="px-4 py-3 font-semibold text-slate-700"><?= htmlspecialchars($sh['nama_guru']) ?></td>
                                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap"><?= date('d M Y', strtotime($sh['tanggal_supervisi'])) ?></td>
                                            <td class="px-4 py-3 text-center font-bold text-emerald-600"><?= $sh['skor'] ?></td>
                                            <td class="px-4 py-3 text-slate-600 font-medium"><?= htmlspecialchars($sh['nama_supervisor'] ?? 'System') ?></td>
                                            <td class="px-4 py-3 text-slate-500 text-[11px] max-w-[200px] truncate" title="<?= htmlspecialchars($sh['catatan']) ?>"><?= htmlspecialchars($sh['catatan'] ?: '-') ?></td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <?php if ($sh['supervisor_id'] == $current_ustadz_id || $is_super_admin): ?>
                                                    <a href="?periode=<?= $selected_period ?>&action=delete_supervisi&sup_id=<?= $sh['id'] ?>" onclick="return confirm('Hapus log supervisi ini?')" class="text-rose-500 hover:text-rose-700 font-bold" title="Hapus Log"><i class="fas fa-trash"></i></a>
                                                <?php else: ?>
                                                    <span class="text-slate-300">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-6 text-slate-400 italic">Belum ada supervisi yang dilakukan pada periode ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JS SIDEBAR COLLAPSE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-hr');
            const openBtn = document.getElementById('open-sidebar-hr');
            const closeBtn = document.getElementById('close-sidebar-hr');
            const overlay = document.getElementById('sidebar-overlay-hr');

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
    </script>
</body>
</html>
