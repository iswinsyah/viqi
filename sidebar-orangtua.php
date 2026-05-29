<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-orangtua" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS ORANG TUA -->
<aside id="sidebar-orangtua" class="bg-purple-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col z-30 transition-all duration-300 absolute md:relative h-full shadow-2xl">
    <div class="h-16 flex items-center justify-between px-6 border-b border-purple-800 bg-purple-950">
        <h1 class="font-extrabold text-lg tracking-wider flex items-center text-amber-400">
            <i class="fas fa-user-shield mr-2"></i> RUANG ORTU
        </h1>
        <button id="close-sidebar-orangtua" class="md:hidden text-purple-200 hover:text-white focus:outline-none">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto py-4">
        <nav class="px-4 space-y-1">
            <p class="px-2 text-[10px] font-bold text-purple-300 uppercase tracking-wider mb-2 mt-2">Menu Utama</p>
            <a href="dashboard-orangtua.php" class="<?= (isset($active_menu) && $active_menu == 'dashboard_orangtua') ? 'bg-purple-800 text-white' : 'text-purple-100 hover:bg-purple-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-home w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'dashboard_orangtua') ? 'text-amber-400' : 'text-purple-300 group-hover:text-white' ?>"></i> Dashboard
            </a>
            <a href="pembayaran-spp.php" class="<?= (isset($active_menu) && $active_menu == 'pembayaran_spp') ? 'bg-purple-800 text-white' : 'text-purple-100 hover:bg-purple-800 hover:text-white' ?> group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-all">
                <i class="fas fa-money-bill-wave w-6 text-center mr-2 <?= (isset($active_menu) && $active_menu == 'pembayaran_spp') ? 'text-amber-400' : 'text-purple-300 group-hover:text-white' ?>"></i> Pembayaran SPP
            </a>
        </nav>
    </div>

    <div class="p-4 border-t border-purple-800">
        <a href="logout.php" class="flex items-center justify-center text-sm font-bold text-white transition-all bg-rose-600 hover:bg-rose-700 px-4 py-2.5 rounded-lg shadow-sm">
            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
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