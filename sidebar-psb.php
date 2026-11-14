<?php
/**
 * SIDEBAR KHUSUS RUANG PSB (PENERIMAAN SANTRI BARU)
 * Villa Quran Baron Malang - SADIGS 4.0
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$curr_file = basename($_SERVER['PHP_SELF']);
$is_psb_dash = ($curr_file === 'dashboard-psb.php');
$is_spmb = ($curr_file === 'admin-spmb.php');
$is_pipeline = ($curr_file === 'data-pipeline.php');
$is_brosur = ($curr_file === 'admin-brosur-settings.php');
$is_wa = ($curr_file === 'admin-siaran-wa.php');
$is_agen = ($curr_file === 'data-agen.php');

$act_cls = 'bg-white text-[#0b8478] font-black shadow-md';
$inact_cls = 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold';
?>

<!-- SIDEBAR OVERLAY -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR KHUSUS PSB (#0b8478 TEAL THEME) -->
<aside id="sidebar" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50 left-0">

    <!-- SIDEBAR HEADER: BRAND LOGO VILLA QURAN -->
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="dashboard-psb.php" class="flex items-center space-x-3 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">RUANG PSB</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Penerimaan Santri Baru</p>
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

            <a href="dashboard-psb.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_psb_dash ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-tachometer-alt w-4 text-center"></i>
                <span>Dashboard PSB</span>
            </a>

            <a href="admin-spmb.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_spmb ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-user-graduate w-4 text-center"></i>
                <span>Database PSB Online</span>
            </a>

            <a href="data-pipeline.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_pipeline ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-columns w-4 text-center"></i>
                <span>Pipeline Prospek</span>
            </a>

            <a href="admin-brosur-settings.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_brosur ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-envelope-open-text w-4 text-center"></i>
                <span>Brosur Digital</span>
            </a>

            <a href="admin-siaran-wa.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_wa ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-broadcast-tower w-4 text-center"></i>
                <span>Siaran WA Mitra</span>
            </a>

            <a href="data-agen.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl <?= $is_agen ? $act_cls : $inact_cls ?> transition">
                <i class="fas fa-users w-4 text-center"></i>
                <span>Data Agen / Mitra</span>
            </a>

            <a href="tes-kesiapan-anak.php" target="_blank" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-clipboard-check w-4 text-center"></i>
                <span>Tes Kesiapan Santri <i class="fas fa-external-link-alt text-[10px] ml-auto"></i></span>
            </a>

            <div class="pt-3 pb-1 border-t border-teal-700/50 mt-2">
                <span class="text-[10px] uppercase tracking-wider text-teal-200/80 font-bold px-3.5">Ruang Lain</span>
            </div>

            <a href="dashboard-marketing.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-globe w-4 text-center"></i>
                <span>Ruang Web & AI</span>
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
<!-- 5 MENU UTAMA PSB: DASHBOARD, DATABASE, PIPELINE, BROSUR, WA -->
<!-- ========================================================= -->
<nav class="md:hidden fixed bottom-0 inset-x-0 left-0 right-0 w-full m-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] z-40 px-1 flex items-center justify-center" style="left:0; right:0; width:100vw; max-width:100%;">
    <div class="w-full max-w-md mx-auto grid grid-cols-5 items-center justify-around">
        <!-- 1. DASHBOARD PSB -->
        <a href="dashboard-psb.php" class="flex flex-col items-center justify-center py-1 <?= $is_psb_dash ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_psb_dash ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-tachometer-alt text-base <?= $is_psb_dash ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">PSB</span>
        </a>

        <!-- 2. DATABASE SPMB -->
        <a href="admin-spmb.php" class="flex flex-col items-center justify-center py-1 <?= $is_spmb ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_spmb ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-user-graduate text-base <?= $is_spmb ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Data PSB</span>
        </a>

        <!-- 3. PIPELINE -->
        <a href="data-pipeline.php" class="flex flex-col items-center justify-center py-1 <?= $is_pipeline ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_pipeline ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-columns text-base <?= $is_pipeline ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Pipeline</span>
        </a>

        <!-- 4. BROSUR -->
        <a href="admin-brosur-settings.php" class="flex flex-col items-center justify-center py-1 <?= $is_brosur ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_brosur ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-envelope-open-text text-base <?= $is_brosur ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Brosur</span>
        </a>

        <!-- 5. SIARAN WA -->
        <a href="admin-siaran-wa.php" class="flex flex-col items-center justify-center py-1 <?= $is_wa ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-xs transition">
            <div class="w-10 h-8 rounded-full <?= $is_wa ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-broadcast-tower text-base <?= $is_wa ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white text-[10px] leading-tight truncate">Siaran WA</span>
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
