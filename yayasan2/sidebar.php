<?php
// Ambil info user dari session jika tersedia
$y_nama = $_SESSION['nama_lengkap'] ?? ($_SESSION['yayasan_user'] ?? 'Pengurus Yayasan');
$y_user = $_SESSION['username'] ?? 'yayasan';
$y_role = $_SESSION['yayasan_role'] ?? ($_SESSION['role'] ?? 'Pengurus Yayasan');
$y_foto = $_SESSION['foto_profil'] ?? '';
$curr_file = basename($_SERVER['PHP_SELF']);
?>
<!-- ========================================================= -->
<!-- SIDEBAR OVERLAY UNTUK MOBILE                              -->
<!-- ========================================================= -->
<div id="sidebar-overlay-yayasan2" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 hidden md:hidden transition-opacity"></div>

<!-- ========================================================= -->
<!-- DESKTOP & MOBILE DRAWER SIDEBAR (#0b8478 TEAL THEME)       -->
<!-- ========================================================= -->
<aside id="sidebar-yayasan2" class="bg-[#0b8478] text-white w-64 lg:w-72 flex-shrink-0 hidden md:flex flex-col z-50 transition-all duration-300 fixed md:sticky top-0 h-screen shadow-2xl border-r border-teal-700/50">
    
    <!-- SIDEBAR HEADER: BRAND LOGO SADIGS -->
    <div class="p-6 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="index.php" class="flex items-center space-x-3.5 group">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <img src="../upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Aplikasi Ruang Yayasan</p>
            </div>
        </a>
        <button id="close-sidebar-yayasan2" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR PROFILE CARD -->
    <div class="px-5 py-4 border-b border-teal-700/40 bg-teal-900/30 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-11 h-11 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-base shadow-sm border-2 border-white/80 overflow-hidden flex-shrink-0">
                <?php if (!empty($y_foto)): ?>
                    <img src="<?= htmlspecialchars($y_foto) ?>" alt="Avatar" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="fas fa-user text-[#0b8478]"></i>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden flex-1">
                <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($y_nama) ?></h4>
                <p class="text-[10px] text-teal-200 truncate mt-0.5">@<?= htmlspecialchars($y_user) ?></p>
                <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                    <?= htmlspecialchars($y_role) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS: RUANG-RUANG APLIKASI -->
    <nav class="flex-1 overflow-y-auto p-4 space-y-1.5 text-xs no-scrollbar">
        <a href="index.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= ($curr_file === 'index.php') ? 'bg-white text-[#0b8478] font-black shadow-md' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-home w-4 text-center text-sm"></i>
            <span>1. Home (Yayasan)</span>
        </a>
        <a href="../admin-absensi-pegawai.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= ($curr_file === 'admin-absensi-pegawai.php' || $curr_file === 'admin-ustadz.php') ? 'bg-white text-[#0b8478] font-black shadow-md' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-user-tie w-4 text-center text-sm"></i>
            <span>2. Ruang Pegawai</span>
        </a>
        <a href="../ruang-santri.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= ($curr_file === 'ruang-santri.php') ? 'bg-white text-[#0b8478] font-black shadow-md' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-book-quran w-4 text-center text-sm"></i>
            <span>3. Ruang Santri</span>
        </a>
        <a href="../dashboard-orangtua.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= ($curr_file === 'dashboard-orangtua.php') ? 'bg-white text-[#0b8478] font-black shadow-md' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-users w-4 text-center text-sm"></i>
            <span>4. Ruang Orangtua</span>
        </a>
        <a href="../dashboard-marketing.php" class="flex items-center gap-3 px-3.5 py-3 rounded-xl <?= ($curr_file === 'dashboard-marketing.php' || $curr_file === 'admin-ai-hub.php') ? 'bg-white text-[#0b8478] font-black shadow-md' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> transition">
            <i class="fas fa-bullhorn w-4 text-center text-sm"></i>
            <span>5. Ruang Marketing</span>
        </a>
    </nav>

    <!-- SIDEBAR FOOTER: KELUAR -->
    <div class="p-4 border-t border-teal-700/60 flex-shrink-0">
        <a href="../logout-ustadz.php" onclick="return confirm('Yakin ingin keluar dari sistem?');" class="flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl bg-rose-600/90 hover:bg-rose-600 text-white font-black text-xs shadow transition">
            <i class="fas fa-arrow-right-from-bracket"></i> 6. Keluar
        </a>
    </div>
</aside>

<!-- ========================================================= -->
<!-- BOTTOM NAVIGATION BAR (HANYA MUNCUL DI MOBILE / md:hidden) -->
<!-- ========================================================= -->
<nav class="md:hidden fixed bottom-0 inset-x-0 left-0 right-0 w-full m-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] z-40 px-2 flex items-center justify-center" style="left:0; right:0; width:100vw; max-width:100%;">
    <div class="w-full max-w-md mx-auto flex items-center justify-around">
        <a href="index.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'index.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($curr_file === 'index.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-home text-base <?= ($curr_file === 'index.php') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Home</span>
        </a>
        <a href="../admin-absensi-pegawai.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'admin-absensi-pegawai.php' || $curr_file === 'admin-ustadz.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($curr_file === 'admin-absensi-pegawai.php' || $curr_file === 'admin-ustadz.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-user-tie text-base <?= ($curr_file === 'admin-absensi-pegawai.php' || $curr_file === 'admin-ustadz.php') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Pegawai</span>
        </a>
        <a href="../ruang-santri.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'ruang-santri.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($curr_file === 'ruang-santri.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-book-quran text-base <?= ($curr_file === 'ruang-santri.php') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Santri</span>
        </a>
        <a href="../dashboard-orangtua.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'dashboard-orangtua.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($curr_file === 'dashboard-orangtua.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-users text-base <?= ($curr_file === 'dashboard-orangtua.php') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Orangtua</span>
        </a>
        <a href="../dashboard-marketing.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($curr_file === 'dashboard-marketing.php' || $curr_file === 'admin-ai-hub.php') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($curr_file === 'dashboard-marketing.php' || $curr_file === 'admin-ai-hub.php') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-bullhorn text-base <?= ($curr_file === 'dashboard-marketing.php' || $curr_file === 'admin-ai-hub.php') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Marketing</span>
        </a>
        <a href="../logout-ustadz.php" onclick="return confirm('Yakin ingin keluar dari sistem?');" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-rose-200 font-bold text-[10px] transition">
            <div class="w-9 h-7 rounded-full flex items-center justify-center mb-0.5">
                <i class="fas fa-arrow-right-from-bracket text-base text-teal-100 hover:text-rose-200"></i>
            </div>
            <span class="text-white">Keluar</span>
        </a>
    </div>
</nav>

<script>
// Toggle Drawer Sidebar untuk Mobile
document.addEventListener('DOMContentLoaded', function() {
    const openBtn = document.getElementById('open-sidebar-yayasan2');
    const closeBtn = document.getElementById('close-sidebar-yayasan2');
    const sidebar = document.getElementById('sidebar-yayasan2');
    const overlay = document.getElementById('sidebar-overlay-yayasan2');

    function openSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.remove('hidden');
            overlay.classList.remove('hidden');
        }
    }

    function closeSidebar() {
        if (sidebar && overlay) {
            sidebar.classList.add('hidden');
            overlay.classList.add('hidden');
        }
    }

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);
});
</script>