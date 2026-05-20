<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-hr" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS KEPEGAWAIAN & AI HRD -->
<aside id="sidebar-hr" class="bg-slate-800 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-700 bg-slate-900">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-cyan-400">
            <i class="fas fa-users-cog mr-2"></i> RUANG ASATIDZ
        </h1>
        <button id="close-sidebar-hr" class="md:hidden text-slate-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
            <a href="admin-ustadz.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_pegawai') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_pegawai') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Dashboard Pegawai
            </a>
            
            <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-6">Portal Ustadz (Guru)</p>
            <a href="admin-pegawai-jurnal.php" class="<?= (isset($active_menu) && $active_menu == 'jurnal_mengajar') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-book-open w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'jurnal_mengajar') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Jurnal Mengajar
            </a>
            <a href="admin-pegawai-silabus.php" class="<?= (isset($active_menu) && $active_menu == 'master_silabus') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-book-reader w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'master_silabus') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Master Silabus & CP
            </a>
            <a href="admin-pegawai-rpp.php" class="<?= (isset($active_menu) && $active_menu == 'ai_rpp') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-magic w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'ai_rpp') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> AI Generator RPP
            </a>
            <a href="admin-pegawai-nilai.php" class="<?= (isset($active_menu) && $active_menu == 'bank_nilai') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-star-half-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'bank_nilai') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Bank Nilai Akademik
            </a>
            <a href="admin-pegawai-mutabaah.php" class="<?= (isset($active_menu) && $active_menu == 'mutabaah') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-clipboard-list w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'mutabaah') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Buku Mutaba'ah Santri
            </a>
            <a href="admin-pegawai-kpi.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_kpi') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-chart-bar w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_kpi') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Dashboard KPI
            </a>
            <a href="ganti-password-ustadz.php" class="<?= (isset($active_menu) && $active_menu == 'ganti_password') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-key w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'ganti_password') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Ganti Password
            </a>

        </nav>
    </div>

    <div class="p-4 border-t border-slate-700">
        <a href="logout-ustadz.php" class="flex items-center justify-center text-sm font-bold text-white hover:text-white transition-all bg-rose-500 hover:bg-rose-600 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
        </a>
    </div>
</aside>