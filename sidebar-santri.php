<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-santri" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS SANTRI (#0b8478 TEAL THEME - SERAGAM DENGAN DASHBOARD) -->
<aside id="sidebar-santri" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">
    
    <!-- SIDEBAR HEADER: BRAND LOGO VILLA QURAN -->
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">RUANG SANTRI</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </a>
        <button id="close-sidebar-santri" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS -->
    <div class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
        <nav class="space-y-1">
            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-1">Menu Utama</p>
            <a href="dashboard.php" class="<?= (isset($active_menu) && ($active_menu == 'dashboard_santri' || $active_menu == 'dashboard')) ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-house w-4 text-center"></i>
                <span>Dashboard Utama</span>
            </a>
            <a href="kalender-akademik.php" class="<?= (isset($active_menu) && $active_menu == 'kalender_akademik') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender Akademik</span>
            </a>
            <a href="ruang-santri.php?view=ibadah_harian" class="<?= (isset($active_menu) && $active_menu == 'ibadah_harian') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-mosque w-4 text-center"></i>
                <span>Ibadah Harian</span>
            </a>
            <a href="santri-laporan-hafalan.php" class="<?= (isset($active_menu) && $active_menu == 'santri_hafalan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-book-quran w-4 text-center"></i>
                <span>Setoran Hafalan Saya</span>
            </a>
            <a href="santri-rapot.php?tab=pkbm" class="<?= (isset($active_menu) && ($active_menu == 'rapot_pkbm_santri' || ($active_menu == 'rapot_santri' && ($tab ?? '') == 'pkbm'))) ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-file-invoice w-4 text-center"></i>
                <span>Raport PKBM (Diknas)</span>
            </a>
            <a href="santri-rapot.php?tab=diniyah" class="<?= (isset($active_menu) && ($active_menu == 'rapot_diniyah' || ($active_menu == 'rapot_santri' && ($tab ?? '') == 'diniyah'))) ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-graduation-cap w-4 text-center"></i>
                <span>Rapor Diniyah</span>
            </a>
            <a href="ruang-santri-keuangan.php" class="<?= (isset($active_menu) && $active_menu == 'tabel_keuangan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-wallet w-4 text-center"></i>
                <span>Tabel Keuangan</span>
            </a>

            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-6">Pengaturan Akun</p>
            <a href="santri-ganti-password.php" class="<?= (isset($active_menu) && $active_menu == 'ganti_password_santri') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-key w-4 text-center"></i>
                <span>Ganti Password</span>
            </a>
            <a href="santri-profil.php" class="<?= (isset($active_menu) && $active_menu == 'edit_profil_santri') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-user-edit w-4 text-center"></i>
                <span>Edit Profil</span>
            </a>
        </nav>
    </div>

    <!-- SIDEBAR FOOTER -->
    <div class="p-4 border-t border-teal-700/60 space-y-2 bg-teal-900/30">
        <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow transition">
            <i class="fas fa-house"></i> Beranda Utama
        </a>
        <a href="logout-santri.php" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>

<?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true): ?>
<div class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-900 text-white px-4 py-2 text-xs shadow-2xl flex items-center justify-between border-b border-purple-400">
    <div class="flex items-center space-x-2">
        <span class="animate-pulse text-amber-300 font-extrabold text-sm"><i class="fas fa-user-secret"></i> MODE IMPERSONASI</span>
        <span class="hidden sm:inline text-purple-200">|</span>
        <span class="text-purple-100">Anda sedang mengakses sistem sebagai: <strong class="text-amber-200 underline font-bold"><?= htmlspecialchars($_SESSION['santri_nama'] ?? '') ?></strong></span>
    </div>
    <a href="switch-back-admin.php" class="bg-amber-400 hover:bg-amber-300 text-purple-950 font-extrabold px-3.5 py-1 rounded-full text-[11px] shadow transition flex items-center gap-1.5 whitespace-nowrap">
        <i class="fas fa-undo"></i> Kembali ke Super Admin
    </a>
</div>
<style>
/* Geser layout sedikit jika banner impersonasi aktif */
body { padding-top: 36px !important; }
</style>
<?php endif; ?>