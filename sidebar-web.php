<?php
/**
 * SIDEBAR KHUSUS RUANG WEB (CMS, MEDIA DIGITAL & CREW AI)
 * Villa Quran Baron Malang - SADIGS 4.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$curr_file = basename($_SERVER['PHP_SELF']);
$is_stat_web = ($curr_file === 'dashboard-marketing.php' || $curr_file === 'dashboard-web.php');
$is_ai_hub = ($curr_file === 'admin-ai-hub.php');
$is_seo = ($curr_file === 'admin-seo.php');
$is_kalender = ($curr_file === 'admin-kalender.php');
$is_persona = ($curr_file === 'admin-analisa.php');
$is_community = ($curr_file === 'admin-community-scout.php');
$is_publisher = ($curr_file === 'admin-publisher.php');
$is_trend = ($curr_file === 'admin-trend-scout.php');
$is_workflow = ($curr_file === 'admin-sosmed-workflow.php' || $curr_file === 'admin-sosmed.php');

$act_cls = 'bg-white text-[#0b8478] font-black shadow-md';
$inact_cls = 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold';
?>

<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS RUANG WEB (#0b8478 TEAL THEME) -->
<aside id="sidebar" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">

    <!-- SIDEBAR HEADER: BRAND LOGO VILLA QURAN -->
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard-marketing.php" class="flex items-center space-x-3 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">RUANG WEB</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">CMS, Media & Crew AI</p>
            </div>
        </a>
        <button id="close-sidebar" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS -->
    <div class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
        <nav class="space-y-1">
            <?php if (isset($_SESSION['yayasan2_logged_in']) || isset($_SESSION['yayasan_logged_in']) || (isset($_SESSION['app_user_role']) && in_array($_SESSION['app_user_role'], ['super_admin', 'ketua_yayasan']))): ?>
                <a href="yayasan2/index.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                    <i class="fas fa-landmark w-4 text-center"></i>
                    <span>Ruang Yayasan</span>
                </a>
            <?php endif; ?>

            <a href="dashboard-marketing.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_stat_web ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-chart-line w-4 text-center"></i>
                <span>Statistik Pengunjung</span>
            </a>

            <a href="admin-ai-hub.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_ai_hub ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-robot w-4 text-center"></i>
                <span>Crew AI Hub</span>
            </a>

            <a href="admin-seo.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_seo ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-pen-nib w-4 text-center"></i>
                <span>Penulis Artikel SEO</span>
            </a>

            <a href="admin-kalender.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_kalender ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender Konten AI</span>
            </a>

            <a href="admin-analisa.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_persona ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-brain w-4 text-center"></i>
                <span>Analisa Persona</span>
            </a>

            <a href="admin-community-scout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_community ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-search-location w-4 text-center"></i>
                <span>Community Scout</span>
            </a>

            <a href="admin-publisher.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_publisher ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-paper-plane w-4 text-center"></i>
                <span>Publisher AI</span>
            </a>

            <a href="admin-trend-scout.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_trend ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-chart-pie w-4 text-center"></i>
                <span>Trend & Hook Scout</span>
            </a>

            <a href="admin-sosmed-workflow.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_workflow ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-diagram-project w-4 text-center"></i>
                <span>Workflow Multi-Agent</span>
            </a>

            <div class="pt-3 pb-1 border-t border-teal-700/50 mt-2">
                <span class="text-[10px] uppercase tracking-wider text-teal-200/80 font-bold px-3.5">Ruang Lain</span>
            </div>

            <a href="dashboard-psb.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-user-graduate w-4 text-center"></i>
                <span>Ruang PSB (Penerimaan)</span>
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

<!-- ========================================================= -->
<!-- BOTTOM NAVIGATION BAR FOR MOBILE (MD:HIDDEN)              -->
<!-- 5 MENU UTAMA WEB: STATISTIK, CREW AI, ARTIKEL, KALENDER, PUBLISHER -->
<!-- ========================================================= -->
<nav class="md:hidden fixed bottom-0 inset-x-0 left-0 right-0 w-full m-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] z-40 px-1 flex items-center justify-center" style="left:0; right:0; width:100vw; max-width:100%;">
    <div class="w-full max-w-md mx-auto grid grid-cols-5 items-center justify-around">
        <!-- 1. STATISTIK WEB -->
        <a href="dashboard-marketing.php" class="flex flex-col items-center justify-center py-1 <?= $is_stat_web ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_stat_web ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-chart-line text-base <?= $is_stat_web ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Statistik</span>
        </a>

        <!-- 2. CREW AI -->
        <a href="admin-ai-hub.php" class="flex flex-col items-center justify-center py-1 <?= $is_ai_hub ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_ai_hub ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-robot text-base <?= $is_ai_hub ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Crew AI</span>
        </a>

        <!-- 3. ARTIKEL SEO -->
        <a href="admin-seo.php" class="flex flex-col items-center justify-center py-1 <?= $is_seo ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_seo ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-pen-nib text-base <?= $is_seo ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Artikel</span>
        </a>

        <!-- 4. KALENDER -->
        <a href="admin-kalender.php" class="flex flex-col items-center justify-center py-1 <?= $is_kalender ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_kalender ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-calendar-alt text-base <?= $is_kalender ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Kalender</span>
        </a>

        <!-- 5. PUBLISHER -->
        <a href="admin-publisher.php" class="flex flex-col items-center justify-center py-1 <?= $is_publisher ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_publisher ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-paper-plane text-base <?= $is_publisher ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Publisher</span>
        </a>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const openBtn = document.getElementById('open-sidebar');
    const closeBtn = document.getElementById('close-sidebar');
    const overlay = document.getElementById('sidebar-overlay');

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
