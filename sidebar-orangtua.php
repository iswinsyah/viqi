<?php
$curr_active = $active_menu ?? '';
?>
<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay-orangtua" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS ORANG TUA (#0b8478 TEAL THEME - SERAGAM 100% DENGAN DASHBOARD.PHP) -->
<aside id="sidebar-orangtua" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">
    
    <!-- SIDEBAR HEADER: BRAND LOGO SADIGS (SERAGAM DENGAN DASHBOARD) -->
    <div class="p-6 border-b border-teal-700/60 flex items-center justify-between flex-shrink-0">
        <a href="dashboard.php" class="flex items-center space-x-3.5 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </a>
        <button id="close-sidebar-orangtua" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS (PERSIS SEPERTI DASHBOARD.PHP) -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
        <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_active === 'dashboard') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-house w-4 text-center"></i>
            <span>Beranda</span>
        </a>
        <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_active === 'kalender_akademik') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-calendar-alt w-4 text-center"></i>
            <span>Kalender</span>
        </a>
        <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_active === 'jadwal_pelajaran') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-clock w-4 text-center"></i>
            <span>Jadwal</span>
        </a>
        <a href="pengumuman.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= ($curr_active === 'pengumuman') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-bullhorn w-4 text-center"></i>
            <span>Info</span>
        </a>
    </nav>

    <!-- SIDEBAR FOOTER: LOGOUT (PERSIS SEPERTI DASHBOARD.PHP) -->
    <div class="p-4 border-t border-teal-700/60">
        <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>

<!-- SCRIPT TOGGLE DRAWER SIDEBAR MOBILE -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('open-sidebar-orangtua');
    const closeBtn = document.getElementById('close-sidebar-orangtua');
    const sidebar = document.getElementById('sidebar-orangtua');
    const overlay = document.getElementById('sidebar-overlay-orangtua');

    function toggleSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.toggle('hidden');
            overlay.classList.toggle('hidden');
        }
    }

    if (openBtn) openBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);
});
</script>

<!-- BOTTOMBAR UNIVERSAL (MOBILE) -->
<?php include_once __DIR__ . '/bottombar-universal.php'; ?>

<?php if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true): ?>
<div class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-purple-900 via-indigo-900 to-purple-900 text-white px-4 py-2 text-xs shadow-2xl flex items-center justify-between border-b border-purple-400">
    <div class="flex items-center space-x-2">
        <span class="animate-pulse text-amber-300 font-extrabold text-sm"><i class="fas fa-user-secret"></i> MODE IMPERSONASI</span>
        <span class="hidden sm:inline text-purple-200">|</span>
        <span class="text-purple-100">Anda sedang mengakses sistem sebagai: <strong class="text-amber-200 underline font-bold"><?= htmlspecialchars($_SESSION['orangtua_nama'] ?? '') ?></strong></span>
    </div>
    <a href="switch-back-admin.php" class="bg-amber-400 hover:bg-amber-300 text-purple-950 font-extrabold px-3.5 py-1 rounded-full text-[11px] shadow transition flex items-center gap-1.5 whitespace-nowrap">
        <i class="fas fa-undo"></i> Kembali ke Super Admin
    </a>
</div>
<style>
/* Geser layout sedikit jika banner impersonasi aktif */
body { padding-top: 36px !important; }
</style>
<?php endif; ?>