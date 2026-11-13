<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$is_super_admin = ($orangtua_id == 9999 || (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi'])));
$active_menu = 'dashboard_orangtua';

$locked_ids = getLockedSantriIds($conn, $orangtua_id);
$santri_list = getOrangtuaSantriList($conn, $orangtua_id);
$ortu_nama = $_SESSION['orangtua_nama'] ?? 'Orang Tua / Wali';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Ruang Orang Tua | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tap-highlight-transparent { -webkit-tap-highlight-color: transparent; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Android Icon Interaction Style */
        .android-icon {
            transition: transform 0.18s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.18s ease;
            -webkit-tap-highlight-color: transparent;
        }
        .android-icon:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 10px 20px -3px rgba(0, 0, 0, 0.15);
        }
        .android-icon:active {
            transform: scale(0.92);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER RUANG ORANG TUA (HIJAU SAMPAI DI BAWAH SEARCH BAR) -->
        <header class="bg-[#0b8478] text-white shadow-md z-20 flex-shrink-0 border-b border-teal-700/60">
            <!-- BARIS ATAS: TITLE RUANG ORANG TUA & PROFIL -->
            <div class="h-16 flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center space-x-2">
                    <h2 class="font-extrabold text-white text-base sm:text-lg tracking-tight flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-300 animate-pulse"></span>
                        Ruang Orang Tua
                    </h2>
                    <span class="hidden md:inline-block text-[11px] font-medium bg-teal-800/80 text-teal-200 px-2 py-0.5 rounded-full border border-teal-600/40">SADIGS 4.0</span>
                </div>
                <div class="flex items-center space-x-3">
                    <?php if (isset($_SESSION['is_impersonating']) || isset($_SESSION['yayasan_logged_in']) || isset($_SESSION['yayasan2_logged_in'])): ?>
                    <a href="yayasan2/index.php" class="text-xs text-white hover:text-teal-100 font-bold bg-white/15 hover:bg-white/25 border border-white/20 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-xs">
                        <i class="fas fa-landmark text-teal-200"></i>
                        <span class="hidden sm:inline">Ruang Yayasan</span>
                    </a>
                    <?php endif; ?>
                    <div title="Wali Santri (<?= htmlspecialchars($ortu_nama) ?>)" class="flex items-center group">
                        <div class="w-10 h-10 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-sm shadow-md border-2 border-white/90 overflow-hidden flex-shrink-0">
                            <?= strtoupper(substr($ortu_nama, 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BARIS BAWAH: SEARCH BAR LENGKAP -->
            <div class="pb-4 px-4 sm:px-6">
                <div class="relative w-full max-w-xl">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                    <input type="text" id="appSearchInput" placeholder="Cari ananda / layanan wali santri..." class="w-full pl-10 pr-4 py-2 bg-white text-slate-800 placeholder-slate-400 text-xs font-semibold rounded-xl border border-slate-200 focus:outline-none focus:ring-2 focus:ring-teal-300 shadow-sm transition">
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE BODY -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-100 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- STATUS KUNCI ANANDA & AKSI CEPAT -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="w-8 h-8 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-sm flex-shrink-0">
                            <i class="fas fa-children"></i>
                        </span>
                        <div>
                            <h3 class="font-extrabold text-slate-800 text-sm">Data Ananda Tercinta</h3>
                            <?php if(!empty($locked_ids) && !empty($santri_list)): ?>
                                <p class="text-[11px] text-[#0b8478] font-bold mt-0.5 flex items-center gap-1.5 flex-wrap">
                                    <i class="fas fa-lock text-[10px]"></i> 
                                    <?php if (count($santri_list) > 1): ?>
                                        <span><?= count($santri_list) ?> Ananda Terhubung:</span>
                                        <?php foreach ($santri_list as $sl): ?>
                                            <span class="bg-teal-100 text-teal-900 px-2 py-0.5 rounded-full font-semibold"><?= htmlspecialchars($sl['nama_lengkap']) ?></span>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span>Ananda Terpilih:</span>
                                        <span class="bg-teal-100 text-teal-900 px-2 py-0.5 rounded-full font-semibold"><?= htmlspecialchars($santri_list[0]['nama_lengkap']) ?></span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        <button type="button" onclick="openPilihAnandaModal()" class="px-3.5 py-2 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-xs flex items-center gap-1.5 transition">
                            <i class="fas fa-user-plus"></i> Kelola / Pilih Ananda
                        </button>
                        <?php if ($is_super_admin && !empty($locked_ids)): ?>
                            <a href="?action=unlock_ananda" onclick="return confirm('Reset kunci ananda? Anda akan bisa memilih santri lain kembali (Khusus Super Admin).')" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition flex items-center gap-1">
                                <i class="fas fa-key text-[10px]"></i> Reset Kunci
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if(empty($santri_list)): ?>
                    <div class="bg-white p-12 rounded-3xl shadow-sm text-center border border-slate-200 max-w-lg mx-auto">
                        <div class="w-20 h-20 bg-teal-50 text-[#0b8478] rounded-3xl flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-1">Belum Ada Data Ananda Terhubung</h3>
                        <p class="text-xs text-gray-500 leading-relaxed mb-5">Akun Anda belum terhubung dengan data santri di buku induk. Silakan klik tombol di bawah untuk memilih ananda.</p>
                        <button type="button" onclick="openPilihAnandaModal()" class="px-5 py-2.5 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-sm transition inline-flex items-center gap-2">
                            <i class="fas fa-child"></i> Pilih Ananda Sekarang
                        </button>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="anandaGrid">
                        <?php foreach($santri_list as $s): ?>
                            <div class="ananda-card bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden flex flex-col justify-between" data-title="<?= strtolower(htmlspecialchars($s['nama_lengkap'])) ?>">
                                <!-- PROFIL ANANDA HEADER -->
                                <div class="p-5 sm:p-6 border-b border-slate-100">
                                    <div class="flex items-center gap-4">
                                        <img src="<?= !empty($s['foto_santri']) ? htmlspecialchars($s['foto_santri']) : 'upload/default-avatar.png' ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($s['nama_lengkap']) ?>&background=0b8478&color=fff'" class="w-16 h-16 rounded-2xl object-cover border-2 border-teal-100 shadow-sm flex-shrink-0">
                                        <div class="overflow-hidden flex-1">
                                            <h3 class="font-black text-gray-900 leading-tight truncate text-base"><?= htmlspecialchars($s['nama_lengkap']) ?></h3>
                                            <p class="text-[11px] text-[#0b8478] font-black uppercase tracking-wider mt-1"><?= htmlspecialchars($s['kelas_sekarang'] ?? 'Santri') ?></p>
                                            <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold"><?= htmlspecialchars($s['status_santri'] ?? 'Aktif') ?></span>
                                                <span class="px-2 py-0.5 bg-teal-50 text-[#0b8478] border border-teal-200 rounded-full text-[10px] font-bold truncate">Kamar <?= htmlspecialchars($s['kamar_asrama'] ?? '-') ?></span>
                                                <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-full text-[10px] font-bold font-mono">NIS: <?= htmlspecialchars($s['nis'] ?? '-') ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CARD GRID MENU LAYANAN ANANDA (ANDROID SQUIRCLE) -->
                                <div class="p-5 sm:p-6 bg-slate-50/50 flex-1">
                                    <div class="flex items-center gap-2 mb-4 px-1">
                                        <i class="fas fa-shapes text-xs text-[#0b8478]"></i>
                                        <h4 class="font-extrabold text-xs text-slate-700 uppercase tracking-wider">
                                            Akses Layanan Ananda
                                        </h4>
                                    </div>

                                    <?php
                                    $menus_ananda = [
                                        ['title' => 'Hafalan', 'href' => 'orangtua-hafalan.php?santri_id=' . $s['id'], 'icon' => 'fas fa-book-quran', 'bg' => 'from-emerald-500 to-teal-700'],
                                        ['title' => 'Ibadah', 'href' => 'orangtua-ibadah-harian.php?santri_id=' . $s['id'], 'icon' => 'fas fa-mosque', 'bg' => 'from-green-600 to-emerald-800'],
                                        ['title' => 'Rapor Diniyah', 'href' => 'orangtua-rapot-diniyah.php?id=' . $s['id'], 'icon' => 'fas fa-book-open-reader', 'bg' => 'from-purple-600 to-indigo-700'],
                                        ['title' => 'Rapor PKBM', 'href' => 'orangtua-rapot-pkbm.php?santri_id=' . $s['id'], 'icon' => 'fas fa-file-invoice', 'bg' => 'from-blue-600 to-cyan-700'],
                                        ['title' => 'Karir AI', 'href' => 'orangtua-karir.php?id=' . $s['id'], 'icon' => 'fas fa-route', 'bg' => 'from-violet-600 to-purple-800'],
                                        ['title' => 'Bayar SPP', 'href' => 'pembayaran-spp.php?santri_id=' . $s['id'], 'icon' => 'fas fa-file-invoice-dollar', 'bg' => 'from-amber-500 to-orange-600'],
                                        ['title' => 'Uang Saku', 'href' => 'kirim-uang-saku.php?santri_id=' . $s['id'], 'icon' => 'fas fa-wallet', 'bg' => 'from-cyan-600 to-teal-700'],
                                        ['title' => 'Mutaba\'ah', 'href' => 'orangtua-mutabaah.php?santri_id=' . $s['id'], 'icon' => 'fas fa-clipboard-list', 'bg' => 'from-indigo-600 to-slate-700']
                                    ];
                                    ?>

                                    <div class="grid grid-cols-4 gap-y-4 gap-x-2 sm:gap-x-3 items-start justify-items-center">
                                        <?php foreach ($menus_ananda as $m): ?>
                                        <div class="flex flex-col items-center w-full text-center select-none">
                                            <a href="<?= htmlspecialchars($m['href']) ?>" class="flex flex-col items-center w-full focus:outline-none group">
                                                <div class="android-icon relative w-13 h-13 sm:w-14 sm:h-14 rounded-[20px] bg-gradient-to-br <?= $m['bg'] ?> text-white shadow-sm flex items-center justify-center text-lg sm:text-xl border border-white/25">
                                                    <i class="<?= $m['icon'] ?>"></i>
                                                </div>
                                                <span class="text-[11px] font-semibold text-slate-700 group-hover:text-[#0b8478] mt-1.5 tracking-tight text-center leading-tight line-clamp-2 max-w-[80px] min-h-[26px] transition-colors">
                                                    <?= htmlspecialchars($m['title']) ?>
                                                </span>
                                            </a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- EMPTY STATE PENCARIAN -->
                <div id="noAppFound" class="hidden text-center py-12 bg-white rounded-3xl border border-slate-200">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xl mx-auto mb-2">
                        <i class="fas fa-search"></i>
                    </div>
                    <p class="font-bold text-slate-600 text-xs">Data ananda atau layanan tidak ditemukan</p>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Live Search Filtering
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('appSearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const q = this.value.toLowerCase().trim();
                    const cards = document.querySelectorAll('.ananda-card');
                    let visibleCount = 0;

                    cards.forEach(card => {
                        const title = card.getAttribute('data-title') || '';
                        if (title.includes(q)) {
                            card.style.display = '';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    const noAppFound = document.getElementById('noAppFound');
                    if (noAppFound) {
                        noAppFound.classList.toggle('hidden', visibleCount > 0);
                    }
                });
            }
        });
    </script>
</body>
</html>
