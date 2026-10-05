<?php
/**
 * BOTTOM NAVIGATION BAR FOR MOBILE (MD:HIDDEN)
 * Standar Antarmuka Mobile SADIGS 4.0 (#0b8478 Teal Theme)
 */
$curr_active = $active_menu ?? '';
$is_santri_role = false;
if (isset($is_santri_only) && $is_santri_only) {
    $is_santri_role = true;
} elseif (isset($_SESSION['santri_logged_in']) && $_SESSION['santri_logged_in'] === true && !isset($_SESSION['app_user_id'])) {
    $is_santri_role = true;
}
?>

<nav class="md:hidden fixed bottom-0 inset-x-0 left-0 right-0 w-full m-0 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] z-40 px-2 py-1.5 transition-all" style="left:0; right:0; width:100vw; max-width:100%;">
    <div class="max-w-md mx-auto grid grid-cols-5 gap-1 items-center">
        <!-- 1. BERANDA -->
        <a href="dashboard.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'dashboard' || empty($curr_active) || $curr_active === 'dashboard_santri') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
            <div class="relative">
                <i class="fas fa-house text-base"></i>
                <?php if ($curr_active === 'dashboard' || empty($curr_active) || $curr_active === 'dashboard_santri'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight text-white">Beranda</span>
        </a>

        <!-- 2. KALENDER -->
        <a href="kalender-akademik.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'kalender_akademik') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
            <div class="relative">
                <i class="fas fa-calendar-alt text-base"></i>
                <?php if ($curr_active === 'kalender_akademik'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight text-white">Kalender</span>
        </a>

        <!-- 3. JADWAL -->
        <a href="admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'jadwal_pelajaran') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
            <div class="relative">
                <i class="fas fa-clock text-base"></i>
                <?php if ($curr_active === 'jadwal_pelajaran'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight text-white">Jadwal</span>
        </a>

        <!-- 4. PENGUMUMAN / INFO -->
        <a href="pengumuman.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'pengumuman') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
            <div class="relative">
                <i class="fas fa-bullhorn text-base"></i>
                <?php if ($curr_active === 'pengumuman'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight text-white">Info</span>
        </a>

        <!-- 5. AKUN / KELUAR -->
        <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all text-teal-100 hover:text-rose-200 hover:bg-rose-500/20 font-medium">
            <div class="relative">
                <i class="fas fa-arrow-right-from-bracket text-base"></i>
            </div>
            <span class="text-[10px] mt-1 tracking-tight text-white">Keluar</span>
        </a>
    </div>
</nav>
