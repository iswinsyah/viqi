<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$view = $_GET['view'] ?? 'default';
$ustadz_nama = $_SESSION['ustadz_nama'] ?? 'Ustadz';

// --- LOGIC & DATA FETCHING BERDASARKAN VIEW ---
if ($view === 'dashboard_asrama') {
    $active_menu = 'dashboard_asrama';
    // --- LOGIC UNTUK DASHBOARD ASRAMA ---
    $total_santri_aktif = $conn->query("SELECT COUNT(id) as total FROM buku_induk_santri WHERE status_santri = 'Aktif'")->fetch_assoc()['total'] ?? 0;
    $total_musyrif = $conn->query("SELECT COUNT(id) as total FROM akun_ustadz WHERE role LIKE '%musyrif%'")->fetch_assoc()['total'] ?? 0;
    $total_laporan_minggu_ini = $conn->query("SELECT COUNT(id) as total FROM laporan_adab WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['total'] ?? 0;

    $laporan_adab_data = []; $days = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $days[] = date('d M', strtotime($date));
        $laporan_adab_data['labels'][] = date('d M', strtotime($date));
        $laporan_adab_data['pelanggaran'][] = 0;
        $laporan_adab_data['apresiasi'][] = 0;
    }
    $res_adab = $conn->query("SELECT DATE(tanggal) as tgl, SUM(CASE WHEN jenis_laporan = 'Pelanggaran' THEN 1 ELSE 0 END) as total_pelanggaran, SUM(CASE WHEN jenis_laporan = 'Apresiasi' THEN 1 ELSE 0 END) as total_apresiasi FROM laporan_adab WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY tgl ORDER BY tgl ASC");
    if ($res_adab) { while ($row = $res_adab->fetch_assoc()) { $index = array_search(date('d M', strtotime($row['tgl'])), $laporan_adab_data['labels']); if ($index !== false) { $laporan_adab_data['pelanggaran'][$index] = (int)$row['total_pelanggaran']; $laporan_adab_data['apresiasi'][$index] = (int)$row['total_apresiasi']; } } }

    $jurnal_musyrif_data = ['labels' => $days, 'data' => array_fill(0, 7, 0)];
    $res_jurnal = $conn->query("SELECT DATE(tanggal) as tgl, COUNT(id) as total FROM jurnal_kegiatan_musyrif WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY tgl ORDER BY tgl ASC");
    if ($res_jurnal) { while ($row = $res_jurnal->fetch_assoc()) { $index = array_search(date('d M', strtotime($row['tgl'])), $jurnal_musyrif_data['labels']); if ($index !== false) { $jurnal_musyrif_data['data'][$index] = (int)$row['total']; } } }

} elseif ($view === 'halaqoh') {
    $active_menu = 'manajemen_halaqoh';
    // --- LOGIC UNTUK MANAJEMEN HALAQOH ---
    $conn->query("CREATE TABLE IF NOT EXISTS halaqoh_grup (id INT AUTO_INCREMENT PRIMARY KEY, nama_grup VARCHAR(150) NOT NULL, musyrif_id INT NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conn->query("CREATE TABLE IF NOT EXISTS halaqoh_anggota (id INT AUTO_INCREMENT PRIMARY KEY, grup_id INT NOT NULL, santri_id INT NOT NULL, UNIQUE KEY (grup_id, santri_id), FOREIGN KEY (grup_id) REFERENCES halaqoh_grup(id) ON DELETE CASCADE)");
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['simpan_grup'])) {
            $id = (int)($_POST['id'] ?? 0); $nama_grup = $conn->real_escape_string($_POST['nama_grup']); $musyrif_id = (int)$_POST['musyrif_id'];
            if ($id > 0) { $conn->query("UPDATE halaqoh_grup SET nama_grup='$nama_grup', musyrif_id=$musyrif_id WHERE id=$id"); } else { $conn->query("INSERT INTO halaqoh_grup (nama_grup, musyrif_id) VALUES ('$nama_grup', $musyrif_id)"); }
            header("Location: admin-ustadz.php?view=halaqoh"); exit;
        }
        if (isset($_POST['simpan_anggota'])) {
            $grup_id = (int)$_POST['grup_id']; $anggota_ids = $_POST['anggota'] ?? [];
            $conn->query("DELETE FROM halaqoh_anggota WHERE grup_id = $grup_id");
            if (!empty($anggota_ids)) {
                $stmt = $conn->prepare("INSERT INTO halaqoh_anggota (grup_id, santri_id) VALUES (?, ?)");
                foreach ($anggota_ids as $santri_id) { $s_id = (int)$santri_id; $stmt->bind_param("ii", $grup_id, $s_id); $stmt->execute(); }
            }
            header("Location: admin-ustadz.php?view=halaqoh&grup_id=$grup_id"); exit;
        }
    }
    if (isset($_GET['hapus_grup_id'])) { $id = (int)$_GET['hapus_grup_id']; $conn->query("DELETE FROM halaqoh_grup WHERE id = $id"); header("Location: admin-ustadz.php?view=halaqoh"); exit; }
    $musyrif_list = []; $res_m = $conn->query("SELECT id, nama FROM akun_ustadz WHERE role LIKE '%musyrif%' OR role LIKE '%kepala_asrama%' ORDER BY nama ASC"); if($res_m) while($r = $res_m->fetch_assoc()) $musyrif_list[] = $r;
    $grup_list = []; $res_g = $conn->query("SELECT g.*, u.nama as nama_musyrif, COUNT(a.id) as jumlah_anggota FROM halaqoh_grup g JOIN akun_ustadz u ON g.musyrif_id = u.id LEFT JOIN halaqoh_anggota a ON g.id = a.grup_id GROUP BY g.id ORDER BY g.nama_grup ASC"); if($res_g) while($r = $res_g->fetch_assoc()) $grup_list[] = $r;
    $active_grup_id = $_GET['grup_id'] ?? ($grup_list[0]['id'] ?? 0);
    $active_grup = null; if ($active_grup_id > 0) { foreach($grup_list as $g) { if ($g['id'] == $active_grup_id) $active_grup = $g; } }
    $santri_tersedia = []; $anggota_sekarang_ids = [];
    if ($active_grup_id > 0) { $res_a = $conn->query("SELECT santri_id FROM halaqoh_anggota WHERE grup_id = $active_grup_id"); if($res_a) while($r = $res_a->fetch_assoc()) $anggota_sekarang_ids[] = $r['santri_id']; }
    $res_s = $conn->query("SELECT id, nama_lengkap, kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC"); if($res_s) while($r = $res_s->fetch_assoc()) $santri_tersedia[] = $r;

} else { // default view
    $active_menu = 'dashboard_pegawai';
    // --- LOGIC UNTUK DASHBOARD PEGAWAI ---
    $q_jurnal = $conn->query("SELECT COUNT(id) AS tot FROM jurnal_mengajar");
    $total_jurnal = $q_jurnal ? ($q_jurnal->fetch_assoc()['tot'] ?? 0) : 0;
    $q_nilai = $conn->query("SELECT COUNT(id) AS tot FROM bank_nilai");
    $total_nilai = $q_nilai ? ($q_nilai->fetch_assoc()['tot'] ?? 0) : 0;
    $jurnal_terbaru = [];
    $res_jurnal = $conn->query("SELECT * FROM jurnal_mengajar ORDER BY id DESC LIMIT 5");
    if ($res_jurnal) { while($r = $res_jurnal->fetch_assoc()) { $jurnal_terbaru[] = $r; } }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Asatidz | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <?php if ($view === 'dashboard_asrama'): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
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
            <?php if ($view === 'dashboard_asrama'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN DASHBOARD KEPALA ASRAMA                   -->
            <!-- ================================================== -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-home-user text-cyan-600 mr-2"></i>Dashboard Kepala Asrama</h1>
                <p class="text-gray-500 mt-1">Grafik pemantauan aktivitas musyrif dan santri secara visual.</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center"><div class="p-4 rounded-full bg-emerald-100 text-emerald-600 mr-4"><i class="fas fa-users text-2xl"></i></div><div><p class="text-sm font-medium text-gray-500">Total Santri Aktif</p><p class="text-3xl font-bold text-gray-900"><?= $total_santri_aktif ?></p></div></div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center"><div class="p-4 rounded-full bg-cyan-100 text-cyan-600 mr-4"><i class="fas fa-user-shield text-2xl"></i></div><div><p class="text-sm font-medium text-gray-500">Total Musyrif</p><p class="text-3xl font-bold text-gray-900"><?= $total_musyrif ?></p></div></div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center"><div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4"><i class="fas fa-balance-scale text-2xl"></i></div><div><p class="text-sm font-medium text-gray-500">Laporan Adab (7 Hari)</p><p class="text-3xl font-bold text-gray-900"><?= $total_laporan_minggu_ini ?></p></div></div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Grafik Laporan Kedisiplinan (7 Hari)</h3>
                    <canvas id="grafikLaporanAdab"></canvas>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Grafik Aktivitas Jurnal Musyrif (7 Hari)</h3>
                    <canvas id="grafikJurnalMusyrif"></canvas>
                </div>
            </div>

            <?php elseif ($view === 'halaqoh'): ?>
            <!-- ================================================== -->
            <!-- TAMPILAN MANAJEMEN HALAQOH                        -->
            <!-- ================================================== -->
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-layer-group text-cyan-600 mr-2"></i>Manajemen Halaqoh Santri</h1></div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-4 border-b bg-slate-50"><h3 class="font-bold text-slate-800">Daftar Grup Halaqoh</h3></div>
                        <div class="p-2 space-y-1">
                            <?php foreach($grup_list as $g): ?>
                            <a href="?view=halaqoh&grup_id=<?= $g['id'] ?>" class="<?= $g['id'] == $active_grup_id ? 'bg-cyan-100 text-cyan-800' : 'hover:bg-gray-100' ?> block p-3 rounded-lg transition">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold"><?= htmlspecialchars($g['nama_grup']) ?></span>
                                    <span class="text-xs font-bold bg-white px-2 py-1 rounded-full border"><?= $g['jumlah_anggota'] ?> Santri</span>
                                </div>
                                <div class="text-xs mt-1 opacity-70"><i class="fas fa-user-shield mr-1"></i> Musyrif: <?= htmlspecialchars($g['nama_musyrif']) ?></div>
                            </a>
                            <?php endforeach; ?>
                            <?php if(empty($grup_list)): ?><p class="p-4 text-center text-sm text-gray-500 italic">Belum ada grup.</p><?php endif; ?>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                        <div class="p-4 border-b bg-slate-50"><h3 class="font-bold text-slate-800"><i class="fas fa-plus-circle mr-2 text-cyan-600"></i>Buat Grup Baru</h3></div>
                        <form action="admin-ustadz.php?view=halaqoh" method="POST" class="p-4 space-y-3">
                            <input type="hidden" name="simpan_grup" value="1">
                            <div><label class="text-sm font-medium">Nama Grup</label><input type="text" name="nama_grup" required class="w-full mt-1 px-3 py-2 border rounded-lg" placeholder="Cth: Halaqoh Abu Bakar"></div>
                            <div><label class="text-sm font-medium">Pilih Musyrif</label><select name="musyrif_id" required class="w-full mt-1 px-3 py-2 border rounded-lg bg-white"><option value="">-- Pilih --</option><?php foreach($musyrif_list as $m) echo "<option value='{$m['id']}'>".htmlspecialchars($m['nama'])."</option>"; ?></select></div>
                            <div class="text-right"><button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2 px-5 rounded-lg shadow-md transition">Simpan Grup</button></div>
                        </form>
                    </div>
                </div>
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
                    <?php if($active_grup): ?>
                    <div class="p-4 border-b bg-slate-50 flex justify-between items-center">
                        <div>
                            <h3 class="font-bold text-slate-800">Kelola Anggota: <span class="text-cyan-600"><?= htmlspecialchars($active_grup['nama_grup']) ?></span></h3>
                            <p class="text-xs text-gray-500 mt-1">Pilih santri dari daftar di bawah untuk dimasukkan ke dalam grup ini.</p>
                        </div>
                        <a href="?view=halaqoh&hapus_grup_id=<?= $active_grup['id'] ?>" onclick="return confirm('Yakin ingin menghapus grup ini?')" class="text-red-500 hover:text-red-700 text-xs font-bold"><i class="fas fa-trash mr-1"></i> Hapus Grup</a>
                    </div>
                    <form action="admin-ustadz.php?view=halaqoh" method="POST">
                        <input type="hidden" name="simpan_anggota" value="1">
                        <input type="hidden" name="grup_id" value="<?= $active_grup_id ?>">
                        <div class="p-4 h-[60vh] overflow-y-auto">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach($santri_tersedia as $s): 
                                    $checked = in_array($s['id'], $anggota_sekarang_ids) ? 'checked' : '';
                                ?>
                                <label class="flex items-center space-x-3 p-3 rounded-lg border hover:bg-gray-50 cursor-pointer <?= $checked ? 'bg-cyan-50 border-cyan-200' : '' ?>">
                                    <input type="checkbox" name="anggota[]" value="<?= $s['id'] ?>" <?= $checked ?> class="w-5 h-5 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500">
                                    <div>
                                        <span class="font-medium text-gray-800"><?= htmlspecialchars($s['nama_lengkap']) ?></span>
                                        <span class="text-xs text-gray-500 block">Kelas: <?= htmlspecialchars($s['kelas_sekarang']) ?></span>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="p-4 border-t bg-slate-50 text-right">
                            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Perubahan Anggota</button>
                        </div>
                    </form>
                    <?php else: ?>
                    <div class="p-4 h-full flex flex-col items-center justify-center text-center text-gray-500">
                        <i class="fas fa-mouse-pointer text-5xl mb-4 text-gray-300"></i>
                        <h3 class="font-bold text-lg">Pilih Grup Halaqoh</h3>
                        <p>Silakan pilih grup di sebelah kiri untuk mulai mengelola anggotanya, atau buat grup baru.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- ================================================== -->
            <!-- TAMPILAN DEFAULT (DASHBOARD PEGAWAI)              -->
            <!-- ================================================== -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chalkboard-teacher text-cyan-600 mr-2"></i>Ahlan Wa Sahlan, <?= htmlspecialchars($ustadz_nama) ?>!</h1>
                <p class="text-gray-500 mt-1">Selamat datang di Ruang Asatidz. Gunakan menu-menu di bawah ini untuk mengelola kegiatan akademik.</p>
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

            <?php endif; ?>
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

            <?php if ($view === 'dashboard_asrama'): ?>
            // Grafik Laporan Adab
            const ctxAdab = document.getElementById('grafikLaporanAdab').getContext('2d');
            new Chart(ctxAdab, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($laporan_adab_data['labels']) ?>,
                    datasets: [{
                        label: 'Pelanggaran', data: <?= json_encode($laporan_adab_data['pelanggaran']) ?>,
                        backgroundColor: 'rgba(239, 68, 68, 0.6)', borderColor: 'rgba(239, 68, 68, 1)', borderWidth: 1
                    }, {
                        label: 'Apresiasi', data: <?= json_encode($laporan_adab_data['apresiasi']) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)', borderColor: 'rgba(16, 185, 129, 1)', borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });

            // Grafik Jurnal Musyrif
            const ctxJurnal = document.getElementById('grafikJurnalMusyrif').getContext('2d');
            new Chart(ctxJurnal, {
                type: 'line',
                data: {
                    labels: <?= json_encode($jurnal_musyrif_data['labels']) ?>,
                    datasets: [{
                        label: 'Jumlah Jurnal Masuk', data: <?= json_encode($jurnal_musyrif_data['data']) ?>,
                        fill: true, backgroundColor: 'rgba(6, 182, 212, 0.2)',
                        borderColor: 'rgba(6, 182, 212, 1)', tension: 0.3
                    }]
                },
                options: { scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>