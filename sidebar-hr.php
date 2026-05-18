<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-hr" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS KEPEGAWAIAN & AI HRD -->
<aside id="sidebar-hr" class="bg-slate-800 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-slate-700 bg-slate-900">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-cyan-400">
            <i class="fas fa-users-cog mr-2"></i> KEPEGAWAIAN
        </h1>
        <button id="close-sidebar-hr" class="md:hidden text-slate-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
            <a href="admin-pegawai.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_pegawai') ? 'bg-slate-700 text-white' : 'text-slate-100 hover:bg-slate-700 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_pegawai') ? 'text-cyan-400' : 'text-slate-300 group-hover:text-white' ?>"></i> Dashboard Pegawai
            </a>
            
            <!-- Nanti menu AI Agent pegawai di sini -->
            <p class="px-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2 mt-6">AI Agent HRD</p>
             <a href="#" class="text-slate-100 opacity-50 group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all cursor-not-allowed">
                <i class="fas fa-robot w-6 text-center mr-2 text-slate-300"></i> Kontrol Agent (Segera)
            </a>

        </nav>
    </div>

    <div class="p-4 border-t border-slate-700">
        <a href="admin.php" class="flex items-center justify-center text-sm font-bold text-slate-900 hover:text-slate-900 transition-all bg-cyan-400 hover:bg-cyan-500 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke SIM
        </a>
    </div>
</aside>