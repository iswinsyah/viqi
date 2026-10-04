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
        <?php if ($is_santri_role): ?>
            <!-- 1. BERANDA SANTRI -->
            <a href="dashboard.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'dashboard_santri' || $curr_active === 'dashboard') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
                <div class="relative">
                    <i class="fas fa-home text-base"></i>
                    <?php if ($curr_active === 'dashboard_santri' || $curr_active === 'dashboard'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] mt-1 tracking-tight text-white">Beranda</span>
            </a>

            <!-- 2. IBADAH -->
            <a href="ruang-santri.php?view=ibadah_harian" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'ibadah_harian') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
                <div class="relative">
                    <i class="fas fa-mosque text-base"></i>
                    <?php if ($curr_active === 'ibadah_harian'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] mt-1 tracking-tight text-white">Ibadah</span>
            </a>

            <!-- 3. HAFALAN -->
            <a href="santri-laporan-hafalan.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'santri_hafalan') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
                <div class="relative">
                    <i class="fas fa-book-quran text-base"></i>
                    <?php if ($curr_active === 'santri_hafalan'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] mt-1 tracking-tight text-white">Hafalan</span>
            </a>

            <!-- 4. KEUANGAN -->
            <a href="ruang-santri-keuangan.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'tabel_keuangan') ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
                <div class="relative">
                    <i class="fas fa-wallet text-base"></i>
                    <?php if ($curr_active === 'tabel_keuangan'): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] mt-1 tracking-tight text-white">Keuangan</span>
            </a>

            <!-- 5. RAPOT -->
            <a href="santri-rapot.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= (in_array($curr_active, ['rapot_santri', 'rapot_akademik', 'rapot_pkbm_santri', 'rapot_diniyah'])) ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
                <div class="relative">
                    <i class="fas fa-graduation-cap text-base"></i>
                    <?php if (in_array($curr_active, ['rapot_santri', 'rapot_akademik', 'rapot_pkbm_santri', 'rapot_diniyah'])): ?>
                        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-300 rounded-full"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] mt-1 tracking-tight text-white">Rapor</span>
            </a>

        <?php else: ?>
            <!-- 1. BERANDA UMUM / SADIGS -->
            <a href="dashboard.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($curr_active === 'dashboard' || empty($curr_active)) ? 'text-white font-black bg-white/20 shadow-sm scale-105' : 'text-teal-100 hover:text-white font-medium' ?>">
                <div class="relative">
                    <i class="fas fa-house text-base"></i>
                    <?php if ($curr_active === 'dashboard' || empty($curr_active)): ?>
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
        <?php endif; ?>
    </div>
</nav>
