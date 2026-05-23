<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS MARKETING & AI -->
<aside id="sidebar" class="bg-indigo-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-indigo-800 bg-indigo-950">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-400">
            <i class="fas fa-bullseye mr-2"></i> MARKETING
        </h1>
        <button id="close-sidebar" class="md:hidden text-indigo-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-indigo-300 uppercase tracking-wider mb-2 mt-2">Analitik & Laporan</p>
            <a href="dashboard-marketing.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_marketing') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-tachometer-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_marketing') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="data-pipeline.php" class="<?= (isset($active_menu) && $active_menu == 'pipeline') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-filter w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'pipeline') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Pipeline Prospek
            </a>
            <a href="data-agen.php" class="<?= (isset($active_menu) && $active_menu == 'agen') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-users w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'agen') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Data Agen
            </a>
            <a href="admin-spmb.php" class="<?= (isset($active_menu) && $active_menu == 'spmb') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-user-graduate w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'spmb') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Data Pendaftar SPMB
            </a>

            <p class="px-2 text-[10px] font-bold text-indigo-300 uppercase tracking-wider mb-2 mt-6">Kecerdasan Buatan (AI)</p>
            <a href="admin-ai-hub.php" class="<?= (isset($active_menu) && $active_menu == 'ai-hub') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-robot w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'ai-hub') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Pusat Kendali AI
            </a>
            <a href="admin-analisa.php" class="<?= (isset($active_menu) && $active_menu == 'analisa') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-brain w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'analisa') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Analisa Persona
            </a>
            <a href="admin-kalender.php" class="<?= (isset($active_menu) && $active_menu == 'kalender') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-calendar-alt w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'kalender') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Kalender Konten
            </a>
            <a href="admin-seo.php" class="<?= (isset($active_menu) && $active_menu == 'seo') ? 'bg-indigo-800 text-white' : 'text-indigo-100 hover:bg-indigo-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-pen-nib w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'seo') ? 'text-amber-400' : 'text-indigo-300 group-hover:text-white' ?>"></i> Penulis Artikel SEO
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-indigo-800">
        <a href="admin.php" class="flex items-center justify-center text-sm font-bold text-indigo-900 hover:text-indigo-900 transition-all bg-amber-400 hover:bg-amber-500 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-arrow-left mr-2"></i> Kembali ke SIM
        </a>
    </div>
</aside>