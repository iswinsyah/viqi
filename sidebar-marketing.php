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
            <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-house w-4 text-center"></i>
                <span>Beranda</span>
            </a>
            <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-clock w-4 text-center"></i>
                <span>Jadwal</span>
            </a>
            <a href="pengumuman.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-bullhorn w-4 text-center"></i>
                <span>Info</span>
            </a>
        </nav>
    </div>

    <!-- SIDEBAR FOOTER -->
    <div class="p-4 border-t border-teal-700/60 bg-teal-900/30">
        <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>