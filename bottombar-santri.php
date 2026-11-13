<?php
// Navigasi Bar Bawah (Bottom Navigation Bar) Khusus Tampilan Mobile Santri (4 Menu Utama)
$current_active = $active_menu ?? '';
?>
<!-- BOTTOM NAVIGATION BAR FOR MOBILE (MD:HIDDEN) -->
<nav class="md:hidden fixed bottom-0 inset-x-0 left-0 right-0 w-full m-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] z-40 px-2 flex items-center justify-center" style="left:0; right:0; width:100vw; max-width:100%;">
    <div class="w-full max-w-md mx-auto flex items-center justify-around">
        <!-- 1. BERANDA -->
        <a href="ruang-santri.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($current_active === 'dashboard_santri' || $current_active === 'dashboard' || empty($current_active)) ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($current_active === 'dashboard_santri' || $current_active === 'dashboard' || empty($current_active)) ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-house text-base <?= ($current_active === 'dashboard_santri' || $current_active === 'dashboard' || empty($current_active)) ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Beranda</span>
        </a>

        <!-- 2. IBADAH HARIAN -->
        <a href="ruang-santri.php?view=ibadah_harian" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($current_active === 'ibadah_harian') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($current_active === 'ibadah_harian') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-mosque text-base <?= ($current_active === 'ibadah_harian') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Ibadah</span>
        </a>

        <!-- 3. KALENDER AKADEMIK -->
        <a href="kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 <?= ($current_active === 'kalender_akademik') ? 'text-white font-black' : 'text-teal-100 hover:text-white font-bold' ?> text-[10px] transition">
            <div class="w-9 h-7 rounded-full <?= ($current_active === 'kalender_akademik') ? 'bg-white/20' : '' ?> flex items-center justify-center mb-0.5">
                <i class="fas fa-calendar-alt text-base <?= ($current_active === 'kalender_akademik') ? 'text-white' : 'text-teal-100' ?>"></i>
            </div>
            <span class="text-white">Kalender</span>
        </a>

        <!-- 4. KELUAR -->
        <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar dari sistem?');" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
            <div class="w-9 h-7 rounded-full flex items-center justify-center mb-0.5">
                <i class="fas fa-arrow-right-from-bracket text-base text-rose-300"></i>
            </div>
            <span class="text-white">Keluar</span>
        </a>
    </div>
</nav>
