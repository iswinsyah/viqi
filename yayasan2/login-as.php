<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'login_as';

// Fetch all ustadz/pegawai accounts
$query = "SELECT * FROM akun_ustadz ORDER BY status_pegawai != 'Nonaktif' DESC, nama ASC";
$result = $conn->query($query);
$accounts = [];
$total_akun = 0;
$total_aktif = 0;
$total_kepala = 0;
$total_hrd_keuangan = 0;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $accounts[] = $row;
        $total_akun++;
        
        $status = $row['status_pegawai'] ?? 'Aktif';
        if ($status !== 'Nonaktif') {
            $total_aktif++;
        }
        
        $role_lower = strtolower($row['role'] ?? '');
        if (strpos($role_lower, 'kepala') !== false) {
            $total_kepala++;
        }
        if (strpos($role_lower, 'hrd') !== false || strpos($role_lower, 'bendahara') !== false || strpos($role_lower, 'admin') !== false) {
            $total_hrd_keuangan++;
        }
    }
}

// Fungsi helper untuk merender badge role yang indah
function renderRoleBadges($role_str) {
    if (empty(trim($role_str))) {
        return '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600 border border-gray-200">User Biasa</span>';
    }
    $roles = explode(',', $role_str);
    $html = '<div class="flex flex-wrap gap-1.5">';
    foreach ($roles as $r) {
        $r_clean = trim($r);
        $r_lower = strtolower($r_clean);
        
        if ($r_lower === 'super_admin') {
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-crown text-rose-600 text-[10px]"></i> Super Admin</span>';
        } elseif (strpos($r_lower, 'kepala') !== false) {
            $label = ucwords(str_replace('_', ' ', $r_clean));
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-user-tie text-indigo-600 text-[10px]"></i> '.$label.'</span>';
        } elseif ($r_lower === 'hrd') {
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-id-badge text-purple-600 text-[10px]"></i> HRD</span>';
        } elseif (strpos($r_lower, 'bendahara') !== false) {
            $label = ucwords(str_replace('_', ' ', $r_clean));
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-wallet text-emerald-600 text-[10px]"></i> '.$label.'</span>';
        } elseif (strpos($r_lower, 'musyrif') !== false || strpos($r_lower, 'asrama') !== false) {
            $label = ucwords(str_replace('_', ' ', $r_clean));
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-home text-amber-600 text-[10px]"></i> '.$label.'</span>';
        } elseif ($r_lower === 'ustadz' || strpos($r_lower, 'pengajar') !== false) {
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-chalkboard-teacher text-blue-600 text-[10px]"></i> Ustadz</span>';
        } else {
            $label = ucwords(str_replace('_', ' ', $r_clean));
            $html .= '<span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200 inline-flex items-center gap-1 shadow-2xs"><i class="fas fa-user text-slate-600 text-[10px]"></i> '.$label.'</span>';
        }
    }
    $html .= '</div>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login As (Perbaikan & Inspeksi) | Yayasan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-gray-200">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <span class="bg-purple-100 text-purple-700 p-2 rounded-lg"><i class="fas fa-user-secret"></i></span>
                    <h2 class="font-bold text-gray-800 text-base md:text-lg">Panel Inspeksi & Login As (Perbaikan Sistem)</h2>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-xs font-semibold bg-amber-100 text-amber-800 px-3 py-1 rounded-full border border-amber-300 hidden sm:inline-block">
                    <i class="fas fa-shield-alt mr-1 text-amber-600"></i> Mode Otoritas Tinggi
                </span>
                <div class="h-9 w-9 rounded-full bg-gradient-to-tr from-amber-600 to-amber-400 flex items-center justify-center text-white font-bold shadow-md">Y2</div>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-6">
            
            <!-- HERO BANNER -->
            <div class="mb-6 bg-gradient-to-r from-slate-900 via-purple-950 to-slate-900 text-white rounded-2xl p-6 shadow-xl border border-purple-800/50 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-1/3 top-0 w-48 h-48 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="max-w-3xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-500/20 border border-purple-400/30 text-purple-200 text-xs font-bold mb-3">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <i class="fas fa-user-secret"></i> SUPER ADMIN IMPERSONATION TOOL
                        </div>
                        <h1 class="text-2xl md:text-3xl font-extrabold text-white tracking-tight">Login As untuk Keperluan Perbaikan</h1>
                        <p class="text-slate-300 mt-2 text-sm md:text-base leading-relaxed">
                            Masuk ke dalam portal operasional sebagai <span class="text-amber-300 font-semibold">Ustadz, Kepala Sekolah, HRD, Bendahara, atau Musyrif</span> tanpa perlu mengetahui kata sandi mereka. Sangat efektif untuk <span class="underline decoration-amber-400 decoration-2 font-semibold">mengecek laporan error, investigasi kendala presensi/gaji, serta uji coba fitur sistem</span> secara real-time.
                        </p>
                    </div>
                    <div class="bg-purple-900/60 border border-purple-700/50 rounded-xl p-4 text-xs text-purple-200 max-w-xs flex-shrink-0 shadow-lg">
                        <div class="font-bold text-amber-300 mb-1 flex items-center gap-1.5">
                            <i class="fas fa-lightbulb"></i> Cara Kembali ke Sesi Ini:
                        </div>
                        <p>Saat Anda dalam mode impersonasi, klik tombol <span class="bg-amber-400 text-slate-950 font-bold px-1.5 py-0.5 rounded inline-block">Kembali ke Super Admin</span> pada banner ungu di atas layar untuk kembali ke halaman ini.</p>
                    </div>
                </div>
            </div>

            <!-- STATS WIDGETS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center space-x-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Akun</p>
                        <p class="text-2xl font-extrabold text-slate-800"><?= number_format($total_akun) ?></p>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center space-x-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Akun Aktif</p>
                        <p class="text-2xl font-extrabold text-slate-800"><?= number_format($total_aktif) ?></p>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center space-x-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Kepala Lembaga</p>
                        <p class="text-2xl font-extrabold text-slate-800"><?= number_format($total_kepala) ?></p>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex items-center space-x-4 hover:shadow-md transition">
                    <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-2xl flex-shrink-0">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">HRD & Keuangan</p>
                        <p class="text-2xl font-extrabold text-slate-800"><?= number_format($total_hrd_keuangan) ?></p>
                    </div>
                </div>
            </div>

            <!-- SEARCH & FILTERS -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 mb-6">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Search Input -->
                    <div class="relative w-full md:w-80">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fas fa-search"></i>
                        </div>
                        <input type="text" id="searchInput" placeholder="Cari nama, username, atau role..." class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:bg-white transition font-medium">
                        <button type="button" id="clearSearch" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 hidden">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>

                    <!-- Role Filter Buttons -->
                    <div class="flex flex-wrap items-center gap-1.5 w-full md:w-auto overflow-x-auto pb-1 md:pb-0">
                        <button type="button" onclick="filterByRole('all')" class="role-filter-btn active bg-slate-800 text-white px-3.5 py-2 rounded-lg text-xs font-bold transition shadow-xs">
                            <i class="fas fa-layer-group mr-1"></i> Semua Role
                        </button>
                        <button type="button" onclick="filterByRole('kepala')" class="role-filter-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-bold transition">
                            <i class="fas fa-user-tie mr-1 text-indigo-600"></i> Kepala
                        </button>
                        <button type="button" onclick="filterByRole('hrd')" class="role-filter-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-bold transition">
                            <i class="fas fa-id-badge mr-1 text-purple-600"></i> HRD
                        </button>
                        <button type="button" onclick="filterByRole('bendahara')" class="role-filter-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-bold transition">
                            <i class="fas fa-wallet mr-1 text-emerald-600"></i> Bendahara
                        </button>
                        <button type="button" onclick="filterByRole('musyrif')" class="role-filter-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-bold transition">
                            <i class="fas fa-home mr-1 text-amber-600"></i> Musyrif
                        </button>
                        <button type="button" onclick="filterByRole('ustadz')" class="role-filter-btn bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2 rounded-lg text-xs font-bold transition">
                            <i class="fas fa-chalkboard-teacher mr-1 text-blue-600"></i> Ustadz
                        </button>
                    </div>
                </div>
            </div>

            <!-- TABLE CONTAINER -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-slate-50/50">
                    <h3 class="font-bold text-slate-800 text-base flex items-center">
                        <i class="fas fa-list-ul mr-2 text-purple-600"></i> Daftar Akun Siap Inspeksi
                    </h3>
                    <span id="rowCountDisplay" class="text-xs font-semibold text-slate-500 bg-slate-200/80 px-2.5 py-1 rounded-md">
                        Menampilkan <?= count($accounts) ?> akun
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse" id="accountsTable">
                        <thead>
                            <tr class="bg-slate-100/80 text-slate-600 uppercase text-[11px] font-extrabold tracking-wider border-b border-slate-200">
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-5">Informasi Pengguna</th>
                                <th class="py-3.5 px-5">Username Login</th>
                                <th class="py-3.5 px-5">Hak Akses / Jabatan</th>
                                <th class="py-3.5 px-5 text-center">Status Akun</th>
                                <th class="py-3.5 px-5 text-center w-48">Aksi Perbaikan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <?php if (!empty($accounts)): ?>
                                <?php $no = 1; foreach ($accounts as $row): 
                                    $status = $row['status_pegawai'] ?? 'Aktif';
                                    $status_badge = '<span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 inline-flex items-center gap-1"><i class="fas fa-check-circle text-emerald-500 text-[10px]"></i> Aktif</span>';
                                    if ($status === 'Nonaktif') {
                                        $status_badge = '<span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200 inline-flex items-center gap-1"><i class="fas fa-ban text-rose-500 text-[10px]"></i> Nonaktif</span>';
                                    } elseif ($status === 'Pengabdian') {
                                        $status_badge = '<span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200 inline-flex items-center gap-1"><i class="fas fa-hands-helping text-blue-500 text-[10px]"></i> Pengabdian</span>';
                                    }

                                    // Initials for avatar
                                    $words = explode(" ", trim($row['nama'] ?? 'User'));
                                    $initials = "";
                                    foreach ($words as $w) {
                                        if (mb_strlen($initials) < 2 && !empty($w)) {
                                            $initials .= mb_substr($w, 0, 1);
                                        }
                                    }
                                    $initials = strtoupper(empty($initials) ? "U" : $initials);
                                    
                                    // Avatar color hash
                                    $colors = ['from-purple-500 to-indigo-600', 'from-blue-500 to-cyan-600', 'from-emerald-500 to-teal-600', 'from-amber-500 to-orange-600', 'from-rose-500 to-pink-600'];
                                    $color_idx = abs(crc32($row['nama'] ?? '')) % count($colors);
                                    $avatar_gradient = $colors[$color_idx];

                                    // Role string for filter search
                                    $role_str = strtolower($row['role'] ?? '');
                                ?>
                                <tr class="hover:bg-purple-50/40 transition-colors group account-row" 
                                    data-name="<?= htmlspecialchars(strtolower($row['nama'] ?? '')) ?>" 
                                    data-username="<?= htmlspecialchars(strtolower($row['username'] ?? '')) ?>"
                                    data-role="<?= htmlspecialchars($role_str) ?>">
                                    <td class="py-4 px-4 text-center font-bold text-slate-400 group-hover:text-purple-600 transition"><?= $no++ ?></td>
                                    <td class="py-4 px-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $avatar_gradient ?> text-white font-extrabold flex items-center justify-center text-sm shadow-sm flex-shrink-0">
                                                <?= $initials ?>
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-800 text-sm group-hover:text-purple-900 transition">
                                                    <?= htmlspecialchars($row['nama'] ?? '-') ?>
                                                </div>
                                                <?php if (!empty($row['whatsapp'])): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $row['whatsapp']) ?>" target="_blank" class="text-[11px] text-emerald-600 hover:text-emerald-700 font-medium inline-flex items-center gap-1 mt-0.5">
                                                    <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($row['whatsapp']) ?>
                                                </a>
                                                <?php else: ?>
                                                <span class="text-[11px] text-slate-400 italic">No WhatsApp belum diisi</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-5">
                                        <span class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">
                                            <i class="fas fa-terminal text-[10px] text-slate-400 mr-1"></i><?= htmlspecialchars($row['username'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-5">
                                        <?= renderRoleBadges($row['role'] ?? '') ?>
                                    </td>
                                    <td class="py-4 px-5 text-center">
                                        <?= $status_badge ?>
                                    </td>
                                    <td class="py-4 px-5 text-center">
                                        <a href="../login-as.php?id=<?= $row['id'] ?>" 
                                           onclick="return confirm('Mulai inspeksi sistem sebagai: <?= htmlspecialchars(addslashes($row['nama'] ?? '')) ?>?\n\nAnda akan otomatis dialihkan ke halaman dashboard user ini.');"
                                           class="bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold px-3.5 py-2 rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 inline-flex items-center justify-center gap-1.5 text-xs w-full max-w-[170px] group/btn">
                                            <i class="fas fa-user-secret text-purple-200 group-hover/btn:scale-110 transition-transform"></i>
                                            <span>Login As (Inspeksi)</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center text-slate-300 text-3xl mb-3">
                                                <i class="fas fa-user-slash"></i>
                                            </div>
                                            <p class="font-semibold text-slate-600">Belum ada data akun yang ditemukan</p>
                                            <p class="text-xs text-slate-400 mt-1">Silakan tambahkan akun pengguna terlebih dahulu melalui menu Daftar Asatidz.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <!-- Empty Search Result Indicator -->
                            <tr id="noSearchResult" class="hidden">
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center text-purple-400 text-3xl mb-3">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <p class="font-semibold text-slate-600">Tidak ada akun yang cocok dengan pencarian</p>
                                        <p class="text-xs text-slate-400 mt-1">Coba gunakan kata kunci atau filter role lain.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- FOOTER INFO -->
            <div class="mt-6 text-center text-xs text-slate-400 pb-4">
                <p><i class="fas fa-lock mr-1"></i> Keamanan Sistem Terjamin &bull; Seluruh aktivitas dalam mode inspeksi akan otomatis dicatat untuk audit sistem.</p>
            </div>

        </main>
    </div>

    <!-- SCRIPT FOR FILTERING & INTERACTIVITY -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const clearSearchBtn = document.getElementById('clearSearch');
            const rows = document.querySelectorAll('.account-row');
            const noSearchResult = document.getElementById('noSearchResult');
            const rowCountDisplay = document.getElementById('rowCountDisplay');
            const roleButtons = document.querySelectorAll('.role-filter-btn');
            
            let currentRoleFilter = 'all';

            function filterRows() {
                const query = searchInput.value.toLowerCase().trim();
                let visibleCount = 0;

                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const username = row.getAttribute('data-username') || '';
                    const role = row.getAttribute('data-role') || '';
                    
                    const matchQuery = !query || name.includes(query) || username.includes(query) || role.includes(query);
                    
                    let matchRole = true;
                    if (currentRoleFilter !== 'all') {
                        if (currentRoleFilter === 'kepala') {
                            matchRole = role.includes('kepala');
                        } else {
                            matchRole = role.includes(currentRoleFilter);
                        }
                    }

                    if (matchQuery && matchRole) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (visibleCount === 0 && rows.length > 0) {
                    noSearchResult.classList.remove('hidden');
                } else {
                    noSearchResult.classList.add('hidden');
                }

                rowCountDisplay.textContent = `Menampilkan ${visibleCount} akun`;

                // Show/hide clear button
                if (query.length > 0) {
                    clearSearchBtn.classList.remove('hidden');
                } else {
                    clearSearchBtn.classList.add('hidden');
                }
            }

            searchInput.addEventListener('input', filterRows);

            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterRows();
                searchInput.focus();
            });

            window.filterByRole = function(role) {
                currentRoleFilter = role;
                
                // Update active button classes
                roleButtons.forEach(btn => {
                    btn.classList.remove('bg-slate-800', 'text-white', 'shadow-xs', 'active');
                    btn.classList.add('bg-slate-100', 'hover:bg-slate-200', 'text-slate-700');
                });
                
                const activeBtn = event.currentTarget || event.target.closest('.role-filter-btn');
                if (activeBtn) {
                    activeBtn.classList.remove('bg-slate-100', 'hover:bg-slate-200', 'text-slate-700');
                    activeBtn.classList.add('bg-slate-800', 'text-white', 'shadow-xs', 'active');
                }
                
                filterRows();
            };

            // Sidebar toggle logic
            const openSidebarBtn = document.getElementById('open-sidebar-yayasan2');
            const sidebar = document.getElementById('sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');
            const closeSidebarBtn = document.getElementById('close-sidebar-yayasan2');

            if (openSidebarBtn && sidebar && overlay) {
                openSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('hidden');
                    overlay.classList.toggle('hidden');
                });
            }
            if (closeSidebarBtn && sidebar && overlay) {
                closeSidebarBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('hidden');
                    overlay.classList.toggle('hidden');
                });
            }
            if (overlay && sidebar) {
                overlay.addEventListener('click', () => {
                    sidebar.classList.toggle('hidden');
                    overlay.classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>
