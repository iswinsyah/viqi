<!-- SIDEBAR YAYASAN 2 -->
<div id="sidebar-overlay-yayasan2" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>
<aside id="sidebar-yayasan2" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl border-r border-gray-800">
    <div class="h-16 flex items-center justify-between px-6 border-b border-gray-800 bg-black/20">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-400">
            <i class="fas fa-building mr-2"></i> YAYASAN 2
        </h1>
        <button id="close-sidebar-yayasan2" class="md:hidden text-gray-400 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 mt-2">Menu Pengurus</p>
            <a href="index.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'asatidz') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-users-cog w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'asatidz') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Akun & Role Asatidz
            </a>
            <a href="manajemen-menu.php" class="<?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-sitemap w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Manajemen Menu
            </a>

            <p class="px-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-2 mt-6">Penggajian & KPI</p>
            <a href="gaji-asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_asatidz') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-chalkboard-teacher w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'gaji_asatidz') ? 'text-emerald-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Setting Gaji Asatidz
            </a>
            <a href="gaji-musyrif.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_musyrif') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-user-shield w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'gaji_musyrif') ? 'text-emerald-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Setting Gaji Musyrif
            </a>
            <a href="gaji-pegawai.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_pegawai') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-user-tie w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'gaji_pegawai') ? 'text-emerald-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Setting Gaji Pegawai
            </a>
            <a href="tunjangan.php" class="<?= (isset($active_menu) && $active_menu == 'tunjangan') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-award w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'tunjangan') ? 'text-emerald-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Setting Tunjangan
            </a>
        </nav>
    </div>
    <div class="p-4 border-t border-gray-800"><a href="logout.php" class="flex items-center justify-center text-sm font-bold text-gray-900 transition-all bg-amber-500 hover:bg-amber-600 px-4 py-2.5 rounded-lg shadow-sm"><i class="fas fa-sign-out-alt mr-2"></i> Kunci Ruangan</a></div>
</aside>