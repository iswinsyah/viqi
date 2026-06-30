<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS ADMIN (SIM) -->
<aside id="sidebar" class="bg-emerald-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-emerald-800 bg-emerald-950">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-400">
            <i class="fas fa-shield-alt mr-2"></i> RUANG WEB
        </h1>
        <button id="close-sidebar" class="md:hidden text-emerald-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-emerald-300 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
            <a href="admin.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="admin-hero.php" class="<?= (isset($active_menu) && $active_menu == 'hero') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-home w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'hero') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Pengaturan Hero
            </a>
            <a href="admin-tentang.php" class="<?= (isset($active_menu) && $active_menu == 'tentang') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-info-circle w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'tentang') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Tentang Kami
            </a>
            <a href="admin-pengajar.php" class="<?= (isset($active_menu) && $active_menu == 'pengajar') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-chalkboard-teacher w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'pengajar') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Profil Pengajar
            </a>
            <a href="admin-fasilitas.php" class="<?= (isset($active_menu) && $active_menu == 'fasilitas') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-building w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'fasilitas') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Fasilitas Asrama
            </a>
            <a href="admin-kurikulum.php" class="<?= (isset($active_menu) && $active_menu == 'kurikulum') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-book w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'kurikulum') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Kurikulum
            </a>
            <a href="admin-galeri.php" class="<?= (isset($active_menu) && $active_menu == 'galeri') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-images w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'galeri') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Galeri Kegiatan
            </a>
            <a href="admin-testimoni.php" class="<?= (isset($active_menu) && $active_menu == 'testimoni') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-comments w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'testimoni') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Testimoni
            </a>
            <a href="admin-biaya.php" class="<?= (isset($active_menu) && $active_menu == 'biaya') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-money-bill-wave w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'biaya') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Info Biaya
            </a>
            <a href="admin-parenting.php" class="<?= (isset($active_menu) && $active_menu == 'parenting') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-calendar-check w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'parenting') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Jadwal Parenting
            </a>
            <a href="admin-counseling-karir.php" class="<?= (isset($active_menu) && $active_menu == 'counseling_karir') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-graduation-cap w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'counseling_karir') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Pemetaan Karir & PTN (AI)
            </a>
            <a href="admin-artikel.php" class="<?= (isset($active_menu) && $active_menu == 'artikel') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-file-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'artikel') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Artikel & Blog
            </a>
            <a href="admin-popup.php" class="<?= (isset($active_menu) && $active_menu == 'popup') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-bullhorn w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'popup') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Lead Magnet
            </a>
            <a href="admin-media.php" class="<?= (isset($active_menu) && $active_menu == 'media') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-folder-open w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'media') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Penyimpanan Media
            </a>
            <a href="admin-pengaturan.php" class="<?= (isset($active_menu) && $active_menu == 'pengaturan') ? 'bg-emerald-800 text-white' : 'text-emerald-100 hover:bg-emerald-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-cog w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'pengaturan') ? 'text-amber-400' : 'text-emerald-300 group-hover:text-white' ?>"></i> Pengaturan Web
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-emerald-800">
        <a href="logout.php" class="flex items-center justify-center text-sm font-bold text-white transition-all bg-rose-600 hover:bg-rose-700 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Keluar (Logout)
        </a>
    </div>
</aside>