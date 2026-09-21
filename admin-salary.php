<?php
date_default_timezone_set('Asia/Jakarta');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'koneksi.php';

// Cek Login (Unified atau Ustadz Session)
$user_id = $_SESSION['app_user_id'] ?? $_SESSION['ustadz_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$ustadz_id = $_SESSION['ustadz_id'] ?? $user_id;

// Deteksi Role Pengguna
$app_roles = [];
if (!empty($_SESSION['app_user_roles'])) {
    $app_roles = array_map('trim', explode(',', strtolower($_SESSION['app_user_roles'])));
}
if (!empty($_SESSION['ustadz_role'])) {
    $app_roles = array_merge($app_roles, array_map('trim', explode(',', strtolower($_SESSION['ustadz_role']))));
}

// Cek Data dari Database untuk kepastian
$res_u = $conn->query("SELECT id, nama, role, status_pegawai FROM akun_ustadz WHERE id = $ustadz_id LIMIT 1");
$user_db = $res_u ? $res_u->fetch_assoc() : null;
if ($user_db && !empty($user_db['role'])) {
    $app_roles = array_merge($app_roles, array_map('trim', explode(',', strtolower($user_db['role']))));
}
$all_roles = array_unique(array_map('trim', $app_roles));

$is_super_admin = ($ustadz_id == 9999) || in_array('super_admin', $all_roles) || in_array('ketua_yayasan', $all_roles);

// Simulasi Role dari Matrix Header (jika aktif)
$active_views = $_SESSION['active_role_views'] ?? ['all'];
$is_simulating = !in_array('all', $active_views) && !in_array('none', $active_views);

if (in_array('none', $active_views)) {
    $can_access = false;
} elseif ($is_simulating) {
    $can_access = in_array('admin_sekolah', $active_views) || in_array('super_admin', $active_views);
} else {
    $can_access = in_array('admin_sekolah', $all_roles) || $is_super_admin;
}

if (!$can_access) {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Akses Ditolak | Salary Admin Sekolah</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 max-w-md w-full shadow-2xl text-center border border-slate-200">
            <div class="w-20 h-20 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                <i class="fas fa-lock"></i>
            </div>
            <h2 class="text-xl font-black text-slate-800 mb-2">Akses Terbatas</h2>
            <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                Halaman <strong>Salary Admin Sekolah</strong> khusus diperuntukkan bagi pengguna dengan role <strong>Admin Sekolah</strong>.
            </p>
            <a href="dashboard.php" class="inline-flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs px-6 py-3 rounded-2xl shadow-md transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$active_menu = 'salary_admin';

// Ambil Konfigurasi Pengaturan Gaji Lembaga
$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1 LIMIT 1");
$cfg_gaji = $res_gaji ? $res_gaji->fetch_assoc() : [];

$gaji_pokok_default = (float)($cfg_gaji['gaji_pokok_muda'] ?? 2500000);
$tunjangan_admin_default = (float)($cfg_gaji['tunj_admin_a'] ?? 1000000);

// Filter Periode
$bulan_list = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$filter_bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$nama_bulan_terpilih = $bulan_list[$filter_bulan] ?? date('F');

// Rumus Dasar: Gaji Pokok + Tunjangan (Perhitungan detail menyusul)
$gaji_pokok = $gaji_pokok_default;
$tunjangan_jabatan = $tunjangan_admin_default;
$tunjangan_kinerja = 0; // Komponen dinamis yang akan menyusul
$tunjangan_kehadiran = 0; // Komponen dinamis yang akan menyusul
$total_tunjangan = $tunjangan_jabatan + $tunjangan_kinerja + $tunjangan_kehadiran;

// TOTAL TAKE HOME PAY (THP) = Gaji Pokok + Tunjangan
$total_thp = $gaji_pokok + $total_tunjangan;

$nama_pegawai = $user_db['nama'] ?? $_SESSION['app_user_nama'] ?? $_SESSION['ustadz_nama'] ?? 'Admin Sekolah';
$status_pegawai = $user_db['status_pegawai'] ?? 'Pegawai Tetap';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Admin Sekolah | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            #sidebar-hr, header, .no-print { display: none !important; }
            main { padding: 0 !important; background: white !important; }
            .print-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <?php if (file_exists('sidebar-hr.php')) include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER TOPBAR -->
        <header class="h-16 bg-white border-b border-slate-200/80 shadow-xs flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-base font-bold shadow-inner">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-sm sm:text-base text-slate-800 tracking-tight">Salary Admin Sekolah</h2>
                    <p class="text-[11px] text-slate-400 font-medium hidden sm:block">Transparansi Payroll & Slip Gaji Staf Administrasi</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="dashboard.php" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs transition flex items-center gap-1.5 no-print">
                    <i class="fas fa-house"></i>
                    <span class="hidden sm:inline">Dashboard</span>
                </a>
                <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shadow-md shadow-teal-900/15 transition flex items-center gap-1.5 no-print">
                    <i class="fas fa-print"></i>
                    <span>Cetak Slip</span>
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE AREA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50/70 p-4 sm:p-6 lg:p-8">
            <div class="max-w-5xl mx-auto space-y-6">

                <!-- 1. BARIS ATAS: KARTU PROFIL & FILTER PERIODE -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-5 sm:p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#0b8478] to-[#086a60] text-white flex items-center justify-center text-2xl shadow-md shadow-teal-900/15">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-base sm:text-lg font-black text-slate-800 tracking-tight"><?= htmlspecialchars($nama_pegawai) ?></h1>
                                <span class="bg-teal-50 text-teal-700 text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border border-teal-200">Admin Sekolah</span>
                            </div>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">
                                Status: <strong><?= htmlspecialchars($status_pegawai) ?></strong> • Periode: <span class="text-teal-700 font-bold"><?= $nama_bulan_terpilih ?> <?= $filter_tahun ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Filter Bulan & Tahun -->
                    <form method="GET" action="admin-salary.php" class="flex items-center gap-2 no-print">
                        <select name="bulan" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-teal-500">
                            <?php foreach ($bulan_list as $num => $nama): ?>
                                <option value="<?= $num ?>" <?= $num === $filter_bulan ? 'selected' : '' ?>><?= $nama ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:ring-2 focus:ring-teal-500">
                            <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= $y === $filter_tahun ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="p-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition" title="Terapkan Filter">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>

                <!-- 2. RUMUS GAJI HIGHLIGHT BOX -->
                <div class="bg-gradient-to-r from-teal-800 to-slate-900 text-white rounded-3xl p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-40 h-40 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div>
                            <div class="text-[10px] font-bold text-teal-300 uppercase tracking-wider flex items-center gap-1.5 mb-1">
                                <i class="fas fa-calculator"></i>
                                <span>Rumus Standar Penggajian</span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                                Total Salary = Gaji Pokok + Tunjangan
                            </h2>
                            <p class="text-xs text-teal-100/80 font-medium mt-1">
                                Skema perhitungan kompensasi bulanan untuk Role Admin Sekolah di Villa Quran (SADIGS 4.0).
                            </p>
                        </div>
                        <div class="bg-white/10 backdrop-blur-md px-5 py-3 rounded-2xl border border-white/20 text-right">
                            <span class="text-[10px] uppercase tracking-wider font-bold text-teal-200 block">Total Take Home Pay (THP)</span>
                            <span class="text-2xl sm:text-3xl font-black text-amber-300">Rp <?= number_format($total_thp, 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>

                <!-- 3. KARTU TIGA PILAR SALARY (SUMMARY CARDS) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Kartu 1: Gaji Pokok -->
                    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Komponen 1</span>
                            <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <h3 class="text-xs font-bold text-slate-600">Gaji Pokok</h3>
                        <p class="text-xl font-black text-slate-900 mt-1">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></p>
                        <span class="text-[10px] text-slate-400 font-medium block mt-1">Sesuai jenjang staf administrasi</span>
                    </div>

                    <!-- Kartu 2: Tunjangan -->
                    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Komponen 2</span>
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                                <i class="fas fa-gift"></i>
                            </div>
                        </div>
                        <h3 class="text-xs font-bold text-slate-600">Total Tunjangan</h3>
                        <p class="text-xl font-black text-emerald-600 mt-1">Rp <?= number_format($total_tunjangan, 0, ',', '.') ?></p>
                        <span class="text-[10px] text-slate-400 font-medium block mt-1">Tunjangan jabatan & fungsional</span>
                    </div>

                    <!-- Kartu 3: Total THP -->
                    <div class="bg-white rounded-3xl p-5 border border-amber-200 bg-amber-50/40 shadow-xs hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Total Bersih</span>
                            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                        <h3 class="text-xs font-bold text-slate-700">Take Home Pay (THP)</h3>
                        <p class="text-xl font-black text-slate-900 mt-1">Rp <?= number_format($total_thp, 0, ',', '.') ?></p>
                        <span class="text-[10px] text-emerald-600 font-bold block mt-1">Status: Siap Ditransfer</span>
                    </div>
                </div>

                <!-- 4. TABEL RINCIAN SLIP GAJI -->
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden print-card">
                    <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-800 text-sm sm:text-base">Rincian Komponen Slip Gaji</h3>
                            <p class="text-[11px] text-slate-400 font-medium">Spesifikasi penerimaan dan potongan periode <?= $nama_bulan_terpilih ?> <?= $filter_tahun ?></p>
                        </div>
                        <span class="px-3 py-1 bg-teal-50 text-teal-700 text-xs font-extrabold rounded-xl border border-teal-100">
                            SADIGS Payroll 4.0
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-700">
                            <thead class="bg-slate-50/80 text-[10px] uppercase font-black text-slate-400 tracking-wider border-b border-slate-100">
                                <tr>
                                    <th class="py-3.5 px-6">No</th>
                                    <th class="py-3.5 px-6">Nama Komponen</th>
                                    <th class="py-3.5 px-6">Tipe Komponen</th>
                                    <th class="py-3.5 px-6 text-right">Nominal (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium">
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-6 font-bold text-slate-400">1</td>
                                    <td class="py-3 px-6 font-bold text-slate-800">
                                        Gaji Pokok
                                        <span class="block text-[10px] text-slate-400 font-normal">Gaji pokok dasar staf administrasi sekolah</span>
                                    </td>
                                    <td class="py-3 px-6"><span class="px-2.5 py-0.5 rounded-lg bg-blue-50 text-blue-700 text-[10px] font-bold">Penghasilan Pokok</span></td>
                                    <td class="py-3 px-6 text-right font-black text-slate-900">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-6 font-bold text-slate-400">2</td>
                                    <td class="py-3 px-6 font-bold text-slate-800">
                                        Tunjangan Jabatan Admin Sekolah
                                        <span class="block text-[10px] text-slate-400 font-normal">Tunjangan struktural amanah administrasi</span>
                                    </td>
                                    <td class="py-3 px-6"><span class="px-2.5 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 text-[10px] font-bold">Tunjangan Tetap</span></td>
                                    <td class="py-3 px-6 text-right font-black text-slate-900">Rp <?= number_format($tunjangan_jabatan, 0, ',', '.') ?></td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-6 font-bold text-slate-400">3</td>
                                    <td class="py-3 px-6 font-bold text-slate-800">
                                        Tunjangan Kinerja (KPI Admin)
                                        <span class="block text-[10px] text-slate-400 font-normal">Bonus efektivitas penanganan jam kosong, notulensi & penagihan SPP</span>
                                    </td>
                                    <td class="py-3 px-6"><span class="px-2.5 py-0.5 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-bold">Tunjangan Kinerja (Menyusul)</span></td>
                                    <td class="py-3 px-6 text-right font-black text-slate-500">Rp <?= number_format($tunjangan_kinerja, 0, ',', '.') ?></td>
                                </tr>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="py-3 px-6 font-bold text-slate-400">4</td>
                                    <td class="py-3 px-6 font-bold text-slate-800">
                                        Tunjangan Kehadiran / Disiplin
                                        <span class="block text-[10px] text-slate-400 font-normal">Kalkulasi presensi harian tepat waktu</span>
                                    </td>
                                    <td class="py-3 px-6"><span class="px-2.5 py-0.5 rounded-lg bg-purple-50 text-purple-700 text-[10px] font-bold">Tunjangan Variabel (Menyusul)</span></td>
                                    <td class="py-3 px-6 text-right font-black text-slate-500">Rp <?= number_format($tunjangan_kehadiran, 0, ',', '.') ?></td>
                                </tr>
                                <tr class="bg-slate-50/80 font-black text-slate-900 border-t-2 border-slate-200">
                                    <td colspan="3" class="py-4 px-6 text-right text-xs uppercase tracking-wider">
                                        Total Penerimaan Bersih (THP):
                                    </td>
                                    <td class="py-4 px-6 text-right text-base text-teal-700">
                                        Rp <?= number_format($total_thp, 0, ',', '.') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5. NOTIFIKASI PENGEMBANGAN FORMULA LANJUTAN -->
                <div class="bg-amber-50 border border-amber-200 rounded-3xl p-5 flex items-start gap-3.5 text-xs text-amber-900">
                    <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm flex-shrink-0">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-amber-950 mb-0.5">Catatan Perhitungan Gaji</h4>
                        <p class="text-[11px] text-amber-800 leading-relaxed font-medium">
                            Menu <strong>Salary Admin</strong> saat ini menggunakan rumus dasar <strong>Gaji Pokok + Tunjangan</strong>. Rincian otomatisasi kalkulasi variabel (integrasi persentase absensi harian GPS dan skor pencapaian pada menu <strong>KPI Admin Sekolah</strong>) siap dihubungkan pada tahap berikutnya sesuai instruksi.
                        </p>
                        <div class="mt-3 flex items-center gap-3">
                            <a href="admin-pegawai-kpi.php?view=admin_sekolah" class="inline-flex items-center gap-1.5 font-bold text-[11px] text-amber-900 hover:text-amber-950 underline">
                                <i class="fas fa-chart-line"></i> Lihat Performa KPI Admin Sekolah
                            </a>
                        </div>
                    </div>
                </div>

                <!-- FOOTER BRANDING RINGKAS -->
                <div class="text-center text-[11px] text-slate-400 font-semibold pt-2 pb-6">
                    Villa Quran Indonesia • SADIGS 4.0 Payroll Module
                </div>

            </div>
        </main>
    </div>

</body>
</html>
