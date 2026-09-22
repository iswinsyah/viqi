<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS MARKETING & AI (#0b8478 TEAL THEME - SERAGAM DENGAN DASHBOARD) -->
<aside id="sidebar" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">
    
    <!-- SIDEBAR HEADER: BRAND LOGO VILLA QURAN -->
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">MARKETING</h1>
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
            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-1">Analitik & Laporan</p>
            <a href="dashboard-marketing.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_marketing') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-tachometer-alt w-4 text-center"></i>
                <span>Dashboard Marketing</span>
            </a>
            <a href="data-pipeline.php" class="<?= (isset($active_menu) && $active_menu == 'pipeline') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-filter w-4 text-center"></i>
                <span>Pipeline Prospek</span>
            </a>
            <a href="data-agen.php" class="<?= (isset($active_menu) && $active_menu == 'agen') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-users w-4 text-center"></i>
                <span>Data Agen</span>
            </a>
            <a href="admin-spmb.php" class="<?= (isset($active_menu) && $active_menu == 'spmb') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-user-graduate w-4 text-center"></i>
                <span>Data Pendaftar SPMB</span>
            </a>

            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-6">Kecerdasan Buatan (AI)</p>
            <a href="admin-ai-hub.php" class="<?= (isset($active_menu) && $active_menu == 'ai-hub') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-robot w-4 text-center"></i>
                <span>Pusat Kendali AI</span>
            </a>
            <a href="admin-analisa.php" class="<?= (isset($active_menu) && $active_menu == 'analisa') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-brain w-4 text-center"></i>
                <span>Analisa Persona</span>
            </a>
            <a href="admin-trend-scout.php" class="<?= (isset($active_menu) && $active_menu == 'trend_scout') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-chart-line w-4 text-center"></i>
                <span>Trend Scout</span>
            </a>
            <a href="admin-community-scout.php" class="<?= (isset($active_menu) && $active_menu == 'community_scout') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-search-location w-4 text-center"></i>
                <span>Community Scout</span>
            </a>
            <a href="admin-kalender.php" class="<?= (isset($active_menu) && $active_menu == 'kalender') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-search-dollar w-4 text-center"></i>
                <span>Hook & Keyword</span>
            </a>
            <a href="admin-seo.php" class="<?= (isset($active_menu) && $active_menu == 'seo') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-pen-nib w-4 text-center"></i>
                <span>Penulis Artikel SEO</span>
            </a>
            <a href="admin-publisher.php" class="<?= (isset($active_menu) && $active_menu == 'publisher') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-paper-plane w-4 text-center"></i>
                <span>Publisher</span>
            </a>

            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-6">Sosmed Workflow</p>
            <a href="admin-sosmed-workflow.php" class="<?= (isset($active_menu) && $active_menu == 'sosmed_workflow') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-route w-4 text-center"></i>
                <span>Pusat Kendali Sosmed</span>
            </a>
        </nav>
    </div>

    <!-- SIDEBAR FOOTER -->
    <div class="p-4 border-t border-teal-700/60 space-y-2 bg-teal-900/30">
        <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow transition">
            <i class="fas fa-house"></i> Dashboard Utama
        </a>
        <a href="admin.php" class="flex items-center justify-center text-xs font-semibold text-teal-200 hover:text-white transition-all py-1">
            <i class="fas fa-globe mr-1.5"></i> Ke Ruang Web
        </a>
        <a href="logout.php" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>