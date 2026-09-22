<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-orangtua" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS ORANG TUA (#0b8478 TEAL THEME - SERAGAM DENGAN DASHBOARD) -->
<aside id="sidebar-orangtua" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">
    
    <!-- SIDEBAR HEADER: BRAND LOGO VILLA QURAN -->
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">RUANG ORTU</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </a>
        <button id="close-sidebar-orangtua" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS -->
    <div class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
        <nav class="space-y-1">
            <p class="px-2 text-[10px] font-bold text-teal-300 uppercase tracking-wider mb-2 mt-1">Menu Utama</p>
            <a href="dashboard-orangtua.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_orangtua') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-house w-4 text-center"></i>
                <span>Dashboard Ortu</span>
            </a>
            <a href="kalender-akademik.php" class="<?= (isset($active_menu) && $active_menu == 'kalender_akademik') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender Akademik</span>
            </a>
            <a href="orangtua-hafalan.php" class="<?= (isset($active_menu) && $active_menu == 'orangtua_hafalan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-book-quran w-4 text-center"></i>
                <span>Setoran Hafalan Ananda</span>
            </a>
            <a href="orangtua-ibadah-harian.php" class="<?= (isset($active_menu) && $active_menu == 'orangtua_ibadah_harian') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-mosque w-4 text-center"></i>
                <span>Ibadah Harian Ananda</span>
            </a>
            <a href="orangtua-rapot.php" class="<?= (isset($active_menu) && $active_menu == 'orangtua_rapot') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-graduation-cap w-4 text-center"></i>
                <span>Rapor Akademik Ananda</span>
            </a>
            <a href="orangtua-rapot-pkbm.php" class="<?= (isset($active_menu) && $active_menu == 'orangtua_rapot_pkbm') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-file-invoice w-4 text-center"></i>
                <span>Raport Diknas PKBM</span>
            </a>
            <a href="pembayaran-spp.php" class="<?= (isset($active_menu) && $active_menu == 'pembayaran_keuangan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-money-bill-wave w-4 text-center"></i>
                <span>Pembayaran SPP</span>
            </a>
            <a href="kirim-uang-saku.php" class="<?= (isset($active_menu) && $active_menu == 'kirim_uang_saku') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition">
                <i class="fas fa-wallet w-4 text-center"></i>
                <span>Kirim Uang Saku</span>
            </a>
        </nav>
    </div>

    <!-- SIDEBAR FOOTER -->
    <div class="p-4 border-t border-teal-700/60 space-y-2 bg-teal-900/30">
        <a href="dashboard.php" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow transition">
            <i class="fas fa-house"></i> Beranda Utama
        </a>
        <a href="logout.php" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar-orangtua');
    const openBtn = document.getElementById('open-sidebar-orangtua');
    const closeBtn = document.getElementById('close-sidebar-orangtua');
    const overlay = document.getElementById('sidebar-overlay-orangtua');

    function toggleSidebar() {
        if(sidebar && overlay) {
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
        }
    }

    if(openBtn) openBtn.addEventListener('click', toggleSidebar);
    if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if(overlay) overlay.addEventListener('click', toggleSidebar);
});
</script>