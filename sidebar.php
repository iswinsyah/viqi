<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS ADMIN (RUANG WEB - #0b8478 TEAL THEME) -->
<aside id="sidebar" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">
    
    <!-- SIDEBAR HEADER: BRAND LOGO VILLA QURAN -->
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">RUANG WEB</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </a>
        <button id="close-sidebar" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS -->
    <div class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
        <nav class="space-y-1">
            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-1">Menu Utama</p>
            <a href="admin.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-tachometer-alt w-4 text-center"></i>
                <span>Dashboard Web</span>
            </a>
            <a href="admin-hero.php" class="<?= (isset($active_menu) && $active_menu == 'hero') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-home w-4 text-center"></i>
                <span>Pengaturan Hero</span>
            </a>
            <a href="admin-tentang.php" class="<?= (isset($active_menu) && $active_menu == 'tentang') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-info-circle w-4 text-center"></i>
                <span>Tentang Kami</span>
            </a>
            <a href="admin-pengajar.php" class="<?= (isset($active_menu) && $active_menu == 'pengajar') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-chalkboard-teacher w-4 text-center"></i>
                <span>Profil Pengajar</span>
            </a>
            <a href="admin-fasilitas.php" class="<?= (isset($active_menu) && $active_menu == 'fasilitas') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-building w-4 text-center"></i>
                <span>Fasilitas Asrama</span>
            </a>
            <a href="admin-kurikulum.php" class="<?= (isset($active_menu) && $active_menu == 'kurikulum') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-book w-4 text-center"></i>
                <span>Kurikulum</span>
            </a>
            <a href="admin-galeri.php" class="<?= (isset($active_menu) && $active_menu == 'galeri') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-images w-4 text-center"></i>
                <span>Galeri Kegiatan</span>
            </a>
            <a href="admin-testimoni.php" class="<?= (isset($active_menu) && $active_menu == 'testimoni') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-comments w-4 text-center"></i>
                <span>Testimoni</span>
            </a>
            <a href="admin-biaya.php" class="<?= (isset($active_menu) && $active_menu == 'biaya') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-money-bill-wave w-4 text-center"></i>
                <span>Info Biaya</span>
            </a>
            <a href="admin-parenting.php" class="<?= (isset($active_menu) && $active_menu == 'parenting') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-calendar-check w-4 text-center"></i>
                <span>Jadwal Parenting</span>
            </a>
            <a href="admin-artikel.php" class="<?= (isset($active_menu) && $active_menu == 'artikel') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-file-alt w-4 text-center"></i>
                <span>Artikel & Blog</span>
            </a>
            <a href="admin-popup.php" class="<?= (isset($active_menu) && $active_menu == 'popup') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-bullhorn w-4 text-center"></i>
                <span>Lead Magnet</span>
            </a>
            <a href="admin-media.php" class="<?= (isset($active_menu) && $active_menu == 'media') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-folder-open w-4 text-center"></i>
                <span>Penyimpanan Media</span>
            </a>
            <a href="admin-pengaturan.php" class="<?= (isset($active_menu) && $active_menu == 'pengaturan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-cog w-4 text-center"></i>
                <span>Pengaturan Web</span>
            </a>
        </nav>
    </div>

    <!-- SIDEBAR FOOTER -->
    <div class="p-4 border-t border-teal-700/60 space-y-2 bg-teal-900/30">
        <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow transition">
            <i class="fas fa-house"></i> Dashboard Utama
        </a>
        <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>