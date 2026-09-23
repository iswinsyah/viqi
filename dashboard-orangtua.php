<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$is_super_admin = ($orangtua_id == 9999 || (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi'])));
$active_menu = 'dashboard_orangtua';

$locked_id = getLockedSantriId($conn, $orangtua_id);
$santri_list = getOrangtuaSantriList($conn, $orangtua_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Orang Tua | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-orangtua" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Administrasi Digital Sekolah (SADIGS 4.0)</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-600 hidden md:block">Selamat Datang, <b><?= htmlspecialchars($_SESSION['orangtua_nama']) ?></b></span>
                <div class="h-8 w-8 rounded-full bg-[#0b8478] flex items-center justify-center text-white font-bold shadow-sm">
                    <?= strtoupper(substr($_SESSION['orangtua_nama'], 0, 1)) ?>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-gray-900">Data Ananda Tercinta</h2>
                <?php if($locked_id > 0 && !empty($santri_list)): ?>
                    <p class="text-[#0b8478] font-bold text-xs mt-1 flex items-center gap-1.5 flex-wrap">
                        <i class="fas fa-lock"></i> Ananda Terpilih & Terkunci Permanen: 
                        <span class="bg-teal-100 text-teal-900 px-2 py-0.5 rounded-full"><?= htmlspecialchars($santri_list[0]['nama_lengkap']) ?></span>
                        <?php if ($is_super_admin): ?>
                            <a href="?action=unlock_ananda" onclick="return confirm('Reset kunci ananda? Anda akan bisa memilih santri lain kembali (Khusus Super Admin).')" class="text-[11px] text-teal-700 hover:text-rose-600 underline font-semibold ml-2">
                                <i class="fas fa-key text-[9px]"></i> Ganti Ananda
                            </a>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="text-amber-800 font-bold text-xs mt-1 flex items-center gap-1.5">
                        <i class="fas fa-hand-pointer text-amber-600"></i> Silakan pilih menu ananda di bawah untuk pertama kali (Pilihan akan dikunci permanen selamanya).
                    </p>
                <?php endif; ?>
            </div>
            <a href="dashboard.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 hover:text-[#0b8478] hover:border-teal-300 rounded-xl text-xs font-bold shadow-xs transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda Utama
            </a>
        </div>

        <?php if(empty($santri_list)): ?>
            <div class="bg-white p-12 rounded-3xl shadow-sm text-center border border-gray-100 max-w-lg mx-auto">
                <div class="w-20 h-20 bg-teal-50 text-[#0b8478] rounded-3xl flex items-center justify-center mx-auto mb-4 text-3xl shadow-inner">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 mb-1">Belum Ada Data Ananda Terhubung</h3>
                <p class="text-xs text-gray-500 leading-relaxed">Akun Anda belum terhubung dengan santri manapun di buku induk.<br>Silakan hubungi admin kesantrian pesantren untuk verifikasi data wali.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($santri_list as $s): ?>
                    <div class="bg-white rounded-3xl shadow-sm border border-teal-100/80 overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col justify-between">
                        <div class="p-6">
                            <div class="flex items-center mb-5">
                                <img src="<?= !empty($s['foto_santri']) ? htmlspecialchars($s['foto_santri']) : 'upload/default-avatar.png' ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($s['nama_lengkap']) ?>&background=0b8478&color=fff'" class="w-16 h-16 rounded-2xl object-cover border-2 border-teal-100 shadow-sm mr-4 flex-shrink-0">
                                <div class="overflow-hidden">
                                    <h3 class="font-black text-gray-900 leading-tight truncate text-base"><?= htmlspecialchars($s['nama_lengkap']) ?></h3>
                                    <p class="text-[11px] text-[#0b8478] font-black uppercase tracking-wider mt-1"><?= htmlspecialchars($s['kelas_sekarang'] ?? 'Santri') ?></p>
                                    <div class="flex items-center gap-1.5 mt-1.5">
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-bold"><?= htmlspecialchars($s['status_santri'] ?? 'Aktif') ?></span>
                                        <span class="px-2 py-0.5 bg-teal-50 text-[#0b8478] border border-teal-200 rounded-full text-[10px] font-bold truncate">Kamar <?= htmlspecialchars($s['kamar_asrama'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2 text-xs text-gray-600 border-t border-gray-100 pt-4 bg-slate-50/50 -mx-6 px-6 pb-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 font-medium">NIS / NISN</span>
                                    <span class="font-mono font-bold text-gray-800"><?= htmlspecialchars($s['nis'] ?? '-') ?> / <?= htmlspecialchars($s['nisn'] ?? '-') ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 font-medium">Target Hafalan</span>
                                    <span class="font-bold text-teal-800"><?= htmlspecialchars($s['target_tahfidz'] ?? '30 Juz') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- GRID MENU ORANG TUA UNTUK ANANDA INI -->
                        <div class="bg-teal-50/40 p-4 border-t border-teal-100">
                            <p class="text-[10px] font-bold text-teal-800 uppercase tracking-wider mb-2.5 flex items-center gap-1">
                                <i class="fas fa-arrow-pointer text-[#0b8478]"></i> Akses Menu Ananda Ini:
                            </p>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <a href="orangtua-hafalan.php?santri_id=<?= $s['id'] ?>" class="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-book-quran text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Hafalan</span>
                                </a>
                                <a href="orangtua-ibadah-harian.php?santri_id=<?= $s['id'] ?>" class="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-mosque text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Ibadah</span>
                                </a>
                                <a href="orangtua-rapot-diniyah.php?id=<?= $s['id'] ?>" class="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-book-open-reader text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Rapor Diniyah</span>
                                </a>
                                <a href="orangtua-rapot-pkbm.php?santri_id=<?= $s['id'] ?>" class="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-file-invoice text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Raport PKBM</span>
                                </a>
                                <a href="orangtua-karir.php?id=<?= $s['id'] ?>" class="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-route text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Karir AI</span>
                                </a>
                                <a href="pembayaran-spp.php?santri_id=<?= $s['id'] ?>" class="flex items-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-money-bill-wave text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Bayar SPP</span>
                                </a>
                                <a href="kirim-uang-saku.php?santri_id=<?= $s['id'] ?>" class="col-span-2 flex items-center justify-center gap-2 p-2 rounded-xl bg-white hover:bg-teal-600 hover:text-white border border-teal-100 font-bold text-gray-700 transition shadow-2xs group">
                                    <i class="fas fa-wallet text-[#0b8478] group-hover:text-white"></i>
                                    <span class="truncate">Kirim Uang Saku</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    </div>
</body>
</html>