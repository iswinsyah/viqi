<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-santri" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS SANTRI -->
<aside id="sidebar-santri" class="bg-indigo-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-indigo-800 bg-indigo-950">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-400">
            <i class="fas fa-user-graduate mr-2"></i> RUANG SANTRI
        </h1>
        <button id="close-sidebar-santri" class="md:hidden text-indigo-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-indigo-300 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
            <a href="ruang-santri.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_santri') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-home w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_santri') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="ruang-santri.php?view=ibadah_harian" class="<?= (isset($active_menu) && $active_menu == 'ibadah_harian') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-mosque w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'ibadah_harian') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Ibadah Harian
            </a>
            <a href="santri-rapot.php" class="<?= (isset($active_menu) && $active_menu == 'rapot_santri') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-book-reader w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'rapot_santri') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Rapor Digital
            </a>
            <!-- Menu-menu santri lainnya akan ditambahkan di sini -->
        </nav>
    </div>

    <div class="p-4 border-t border-indigo-800">
        <a href="logout-santri.php" class="flex items-center justify-center text-sm font-bold text-white hover:text-white transition-all bg-rose-500 hover:bg-rose-600 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
        </a>
    </div>
</aside>