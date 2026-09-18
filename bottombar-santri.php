<?php
// Navigasi Bar Bawah (Bottom Navigation Bar) Khusus Tampilan Mobile Santri
$current_active = $active_menu ?? '';
$is_rapot_active = in_array($current_active, ['rapot_santri', 'rapot_akademik', 'rapot_pkbm_santri', 'rapot_diniyah']);
?>
<!-- BOTTOM NAVIGATION BAR FOR MOBILE (MD:HIDDEN) -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-teal-100 shadow-[0_-4px_25px_rgba(13,130,118,0.08)] z-40 px-2 py-1.5 transition-all">
    <div class="max-w-md mx-auto grid grid-cols-5 gap-1 items-center">
        
        <!-- 1. BERANDA -->
        <a href="ruang-santri.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($current_active === 'dashboard_santri') ? 'text-[#0d8276] font-extrabold bg-teal-50/90 scale-105' : 'text-slate-500 hover:text-[#0d8276] font-medium' ?>">
            <div class="relative">
                <i class="fas fa-home text-base"></i>
                <?php if ($current_active === 'dashboard_santri'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-[#0d8276] rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Beranda</span>
        </a>

        <!-- 2. IBADAH -->
        <a href="ruang-santri.php?view=ibadah_harian" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($current_active === 'ibadah_harian') ? 'text-[#0d8276] font-extrabold bg-teal-50/90 scale-105' : 'text-slate-500 hover:text-[#0d8276] font-medium' ?>">
            <div class="relative">
                <i class="fas fa-mosque text-base"></i>
                <?php if ($current_active === 'ibadah_harian'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-[#0d8276] rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Ibadah</span>
        </a>

        <!-- 3. HAFALAN -->
        <a href="santri-laporan-hafalan.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($current_active === 'santri_hafalan') ? 'text-[#0d8276] font-extrabold bg-teal-50/90 scale-105' : 'text-slate-500 hover:text-[#0d8276] font-medium' ?>">
            <div class="relative">
                <i class="fas fa-book-quran text-base"></i>
                <?php if ($current_active === 'santri_hafalan'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-[#0d8276] rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Hafalan</span>
        </a>

        <!-- 4. KEUANGAN -->
        <a href="ruang-santri-keuangan.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($current_active === 'tabel_keuangan') ? 'text-[#0d8276] font-extrabold bg-teal-50/90 scale-105' : 'text-slate-500 hover:text-[#0d8276] font-medium' ?>">
            <div class="relative">
                <i class="fas fa-wallet text-base"></i>
                <?php if ($current_active === 'tabel_keuangan'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-[#0d8276] rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Keuangan</span>
        </a>

        <!-- 5. RAPOT -->
        <a href="santri-rapot.php" class="flex flex-col items-center justify-center py-1.5 px-0.5 rounded-2xl transition-all <?= ($is_rapot_active) ? 'text-[#0d8276] font-extrabold bg-teal-50/90 scale-105' : 'text-slate-500 hover:text-[#0d8276] font-medium' ?>">
            <div class="relative">
                <i class="fas fa-graduation-cap text-base"></i>
                <?php if ($is_rapot_active): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-[#0d8276] rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Rapot</span>
        </a>

    </div>
</nav>
