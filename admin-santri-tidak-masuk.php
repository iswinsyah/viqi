<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Check role permissions for menu
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$is_super_admin = ($ustadz_id === 9999);

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$allowed_roles = ['ustadz', 'ustadzah', 'tutor', 'trainer', 'admin_sekolah', 'kepala_sekolah', 'kepala_mahad', 'kepala_asrama', 'kepala_asrama_rijal', 'kepala_asrama_nisa', 'musyrif', 'musyrifah', 'super_admin'];

if (!$is_super_admin && empty(array_intersect($norm_roles, $allowed_roles))) {
    die("Akses ditolak. Halaman ini khusus untuk Pengajar, Musyrif, dan Pengurus Sekolah/Ma'had.");
}

// Filter parameters
$tgl_filter = $_GET['tanggal'] ?? date('Y-m-d');
$kelas_filter = $_GET['kelas'] ?? '';
$gender_filter = $_GET['gender'] ?? '';

// Build Query
$where_clauses = ["k.tanggal = '$tgl_filter'"];

if (!empty($kelas_filter)) {
    $k_esc = $conn->real_escape_string($kelas_filter);
    $where_clauses[] = "s.kelas_sekarang = '$k_esc'";
}
if (!empty($gender_filter)) {
    $g_esc = $conn->real_escape_string($gender_filter);
    $where_clauses[] = "s.jenis_kelamin = '$g_esc'";
}

$where_sql = implode(' AND ', $where_clauses);

// Fetch Absent / Sick Students
$query = "
    SELECT k.*, s.nama_lengkap, s.nis, s.kelas_sekarang, s.kamar_asrama, s.jenis_kelamin, u.nama as nama_musyrif, u.no_whatsapp 
    FROM jurnal_kesehatan_santri k 
    JOIN buku_induk_santri s ON k.santri_id = s.id 
    LEFT JOIN akun_ustadz u ON k.ustadz_id = u.id 
    WHERE $where_sql 
    ORDER BY s.kelas_sekarang ASC, s.nama_lengkap ASC
";
$res = $conn->query($query);
$daftar_santri_tidak_masuk = [];
if ($res) {
    while ($r = $res->fetch_assoc()) $daftar_santri_tidak_masuk[] = $r;
}

// Fetch Stats for Today
$stat_tgl = $conn->real_escape_string($tgl_filter);
$total_sakit = $conn->query("SELECT COUNT(*) as total FROM jurnal_kesehatan_santri WHERE tanggal = '$stat_tgl' AND status_kesehatan LIKE '%Sakit%'")->fetch_assoc()['total'] ?? 0;
$total_dirawat = $conn->query("SELECT COUNT(*) as total FROM jurnal_kesehatan_santri WHERE tanggal = '$stat_tgl' AND (status_kesehatan LIKE '%Dirawat%' OR status_kesehatan LIKE '%Pulang%')")->fetch_assoc()['total'] ?? 0;
$total_rijal = $conn->query("SELECT COUNT(*) as total FROM jurnal_kesehatan_santri k JOIN buku_induk_santri s ON k.santri_id = s.id WHERE k.tanggal = '$stat_tgl' AND s.jenis_kelamin = 'Laki-laki'")->fetch_assoc()['total'] ?? 0;
$total_nisa = $conn->query("SELECT COUNT(*) as total FROM jurnal_kesehatan_santri k JOIN buku_induk_santri s ON k.santri_id = s.id WHERE k.tanggal = '$stat_tgl' AND s.jenis_kelamin = 'Perempuan'")->fetch_assoc()['total'] ?? 0;

// Fetch Available Classes
$opsi_kelas = [];
$res_k = $conn->query("SELECT DISTINCT kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' AND kelas_sekarang IS NOT NULL AND kelas_sekarang != '' ORDER BY kelas_sekarang ASC");
if ($res_k) {
    while ($r = $res_k->fetch_assoc()) $opsi_kelas[] = $r['kelas_sekarang'];
}

$active_menu = 'santri_tidak_masuk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Santri Tidak Masuk Sekolah | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; p: 0 !important; }
            .print-area { border: none !important; shadow: none !important; p: 0 !important; width: 100% !important; }
            @page { size: A4; margin: 15mm; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR -->
    <div class="no-print">
        <?php include 'sidebar-hr.php'; ?>
    </div>

    <!-- MAIN BODY -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-rose-100 text-rose-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fas fa-user-slash mr-1"></i>Presensi & Izin Sakit
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="window.print()" class="bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-md transition flex items-center gap-2">
                    <i class="fas fa-print"></i> Cetak Daftar Izin (A4)
                </button>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 no-print">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-rose-600 to-red-700 flex items-center justify-center text-white shadow-md shadow-rose-200">
                            <i class="fas fa-user-clock text-lg"></i>
                        </div>
                        <span>Daftar Santri Tidak Masuk Sekolah Hari Ini</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Tembusan verifikasi resmi izin sakit santri yang diterbitkan oleh Musyrif Pembina untuk Ustadz, Pengajar, & Pengurus Sekolah</p>
                </div>
            </div>

            <!-- STATS SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6 no-print">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-100 text-rose-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($total_sakit) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Santri Sakit (UKS/Asrama)</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-hospital"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($total_dirawat) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Dirawat / Pulang Izin Ortu</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-mars"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($total_rijal) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Santri Putra (Rijal) Sakit</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-pink-100 text-pink-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-venus"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= number_format($total_nisa) ?></div>
                        <div class="text-xs font-semibold text-slate-500">Santri Putri (Nisa) Sakit</div>
                    </div>
                </div>
            </div>

            <!-- FILTER BAR -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm mb-6 no-print">
                <form method="GET" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Tanggal Izin Sakit</label>
                        <input type="date" name="tanggal" value="<?= htmlspecialchars($tgl_filter) ?>" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Filter Kelas</label>
                        <select name="kelas" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <option value="">-- Semua Kelas --</option>
                            <?php foreach ($opsi_kelas as $k): ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= ($kelas_filter === $k) ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Filter Gender</label>
                        <select name="gender" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white font-semibold">
                            <option value="">-- Semua (Rijal & Nisa) --</option>
                            <option value="Laki-laki" <?= ($gender_filter === 'Laki-laki') ? 'selected' : '' ?>>♂️ Laki-laki (Putra)</option>
                            <option value="Perempuan" <?= ($gender_filter === 'Perempuan') ? 'selected' : '' ?>>♀️ Perempuan (Putri)</option>
                        </select>
                    </div>

                    <div>
                        <a href="admin-santri-tidak-masuk.php" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-2 px-4 rounded-xl text-xs text-center transition flex items-center justify-center gap-1.5">
                            <i class="fas fa-undo"></i> Reset Filter Hari Ini
                        </a>
                    </div>
                </form>
            </div>

            <!-- PRINTABLE TABLE SHEET -->
            <div class="print-area bg-white rounded-2xl border border-slate-200/90 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 class="font-extrabold text-slate-900 text-base flex items-center gap-2">
                            <i class="fas fa-list-alt text-rose-600"></i>
                            <span>Daftar Resmi Santri Tidak Masuk Sekolah (Tembusan Musyrif)</span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">Tanggal: <b><?= date('d F Y', strtotime($tgl_filter)) ?></b> | Total: <b><?= count($daftar_santri_tidak_masuk) ?> Santri</b></p>
                    </div>
                    <input type="text" id="search_input" onkeyup="filterTable()" placeholder="Cari santri/kelas..." class="no-print px-3.5 py-1.5 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 w-full sm:w-60">
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="santri_table">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-300 text-slate-700 uppercase text-[11px] font-extrabold tracking-wider">
                                <th class="py-3.5 px-4 text-center w-10">No</th>
                                <th class="py-3.5 px-4">Nama Santri</th>
                                <th class="py-3.5 px-4">Kelas & Kamar</th>
                                <th class="py-3.5 px-4">Gejala & Keluhan Sakit</th>
                                <th class="py-3.5 px-4">Tindakan Penanganan</th>
                                <th class="py-3.5 px-4">Musyrif Penanggung Jawab</th>
                                <th class="py-3.5 px-4 text-center no-print">Surat Resmi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 text-xs sm:text-sm">
                            <?php if (!empty($daftar_santri_tidak_masuk)): ?>
                                <?php $no = 1; foreach ($daftar_santri_tidak_masuk as $st): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-4 text-center font-bold text-slate-500"><?= $no++ ?></td>
                                        <td class="py-3.5 px-4 align-top">
                                            <div class="font-bold text-slate-900 flex items-center gap-2">
                                                <span><?= htmlspecialchars($st['nama_lengkap']) ?></span>
                                                <?php if ($st['jenis_kelamin'] === 'Laki-laki'): ?>
                                                    <span class="bg-blue-100 text-blue-800 text-[10px] px-2 py-0.5 rounded-full font-bold">Laki-laki</span>
                                                <?php else: ?>
                                                    <span class="bg-pink-100 text-pink-800 text-[10px] px-2 py-0.5 rounded-full font-bold">Perempuan</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-[11px] text-slate-500 font-mono mt-0.5">NIS: <?= htmlspecialchars($st['nis'] ?? '-') ?></div>
                                        </td>
                                        <td class="py-3.5 px-4 align-top font-semibold text-slate-800">
                                            <div>Kelas: <span class="text-rose-800 font-bold"><?= htmlspecialchars($st['kelas_sekarang']) ?></span></div>
                                            <div class="text-xs text-slate-500">Kamar: <?= htmlspecialchars($st['kamar_asrama'] ?? 'Asrama') ?></div>
                                        </td>
                                        <td class="py-3.5 px-4 align-top">
                                            <div class="font-bold text-rose-900"><?= htmlspecialchars($st['gejala_keluhan']) ?></div>
                                            <?php if (!empty($st['suhu_tubuh'])): ?>
                                                <span class="inline-block bg-rose-100 text-rose-800 text-[10px] px-2 py-0.5 rounded font-extrabold mt-1">Suhu: <?= $st['suhu_tubuh'] ?> °C</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 align-top text-xs text-slate-700">
                                            <div class="font-semibold text-slate-800"><?= htmlspecialchars($st['tindakan_musyrif']) ?></div>
                                            <div class="mt-1">
                                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300">
                                                    <?= htmlspecialchars($st['status_kesehatan']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 align-top font-semibold text-slate-800">
                                            <div><i class="fas fa-user-shield text-slate-400 mr-1"></i><?= htmlspecialchars($st['nama_musyrif'] ?? 'Musyrif Pembina') ?></div>
                                            <?php if (!empty($st['no_whatsapp'])): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $st['no_whatsapp']) ?>" target="_blank" class="no-print text-[11px] text-emerald-600 hover:underline font-bold flex items-center gap-1 mt-0.5">
                                                    <i class="fab fa-whatsapp"></i> WA Musyrif
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4 align-top text-center no-print">
                                            <a href="admin-cek-kesehatan-santri.php?print_surat_id=<?= $st['id'] ?>" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-xl text-xs shadow-sm transition inline-flex items-center gap-1">
                                                <i class="fas fa-file-alt"></i> Surat Izin Sakit
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 italic">
                                        Alhamdulillah, tidak ada catatan santri sakit/izin tidak masuk sekolah untuk tanggal <?= date('d F Y', strtotime($tgl_filter)) ?>.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }
        function filterTable() {
            const filter = document.getElementById("search_input").value.toLowerCase();
            const trs = document.getElementById("santri_table").getElementsByTagName("tr");
            for (let i = 1; i < trs.length; i++) {
                const text = trs[i].textContent || trs[i].innerText;
                trs[i].style.display = (text.toLowerCase().indexOf(filter) > -1) ? "" : "none";
            }
        }
    </script>
</body>
</html>
