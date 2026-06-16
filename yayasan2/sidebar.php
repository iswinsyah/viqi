<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-yayasan2" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS YAYASAN -->
<aside id="sidebar-yayasan2" class="bg-amber-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-amber-800 bg-amber-950">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-300">
            <i class="fas fa-building mr-2"></i> RUANG YAYASAN
        </h1>
        <button id="close-sidebar-yayasan2" class="md:hidden text-amber-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-amber-300 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
            <a href="index.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_yayasan') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_yayasan') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'asatidz') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-users-cog w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'asatidz') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Daftar Asatidz
            </a>
            <a href="manajemen-menu.php" class="<?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-sitemap w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Manajemen Menu
            </a>

            <p class="px-2 text-[10px] font-bold text-amber-300 uppercase tracking-wider mb-2 mt-6">Master Data</p>
            <a href="master-kelas.php" class="<?= (isset($active_menu) && $active_menu == 'master_kelas') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-school w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'master_kelas') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Master Kelas
            </a>

            <p class="px-2 text-[10px] font-bold text-amber-300 uppercase tracking-wider mb-2 mt-6">Keuangan & SDM</p>
            <a href="gaji-asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_asatidz') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-chalkboard-teacher w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'gaji_asatidz') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Gaji Asatidz
            </a>
            <a href="gaji-musyrif.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_musyrif') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-user-shield w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'gaji_musyrif') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Gaji Musyrif
            </a>
            <a href="rekap-spp.php" class="<?= (isset($active_menu) && $active_menu == 'rekap_keuangan') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-file-invoice-dollar w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'rekap_keuangan') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Rekap Pembayaran Keuangan
            </a>
            <a href="rekap-uang-saku.php" class="<?= (isset($active_menu) && $active_menu == 'rekap_uang_saku') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-wallet w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'rekap_uang_saku') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Rekap Uang Saku
            </a>
            <a href="tunjangan.php" class="<?= (isset($active_menu) && $active_menu == 'tunjangan') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-award w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'tunjangan') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Tunjangan
            </a>

            <p class="px-2 text-[10px] font-bold text-amber-300 uppercase tracking-wider mb-2 mt-6">Rencana & Strategi</p>
            <a href="analisis-swot.php" class="<?= (isset($active_menu) && $active_menu == 'analisis_swot') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-chart-line w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'analisis_swot') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Analisis SWOT
            </a>
            <a href="struktur-jobdesc.php" class="<?= (isset($active_menu) && $active_menu == 'struktur_jobdesc') ? 'bg-amber-800 text-white' : 'text-amber-100 hover:bg-amber-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-sitemap w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'struktur_jobdesc') ? 'text-amber-400' : 'text-amber-300 group-hover:text-white' ?>"></i> Struktur & Jobdesc
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-amber-800">
        <a href="../logout.php" class="flex items-center justify-center text-sm font-bold text-white transition-all bg-rose-600 hover:bg-rose-700 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
        </a>
    </div>
</aside>