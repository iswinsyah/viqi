<?php
// Navigasi Bar Bawah (Bottom Navigation Bar) Khusus Tampilan Mobile Santri
$current_active = $active_menu ?? '';
?>
<!-- BOTTOM NAVIGATION BAR FOR MOBILE (MD:HIDDEN) -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-indigo-100 shadow-[0_-4px_25px_rgba(0,0,0,0.07)] z-40 px-2 py-1.5 transition-all">
    <div class="max-w-md mx-auto grid grid-cols-5 gap-1 items-center">
        
        <!-- 1. BERANDA -->
        <a href="ruang-santri.php" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-xl transition-all <?= ($current_active === 'dashboard_santri') ? 'text-indigo-600 font-bold bg-indigo-50/80 scale-105' : 'text-slate-500 hover:text-indigo-600 font-medium' ?>">
            <div class="relative">
                <i class="fas fa-home text-lg"></i>
                <?php if ($current_active === 'dashboard_santri'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Beranda</span>
        </a>

        <!-- 2. IBADAH -->
        <a href="ruang-santri.php?view=ibadah_harian" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-xl transition-all <?= ($current_active === 'ibadah_harian') ? 'text-emerald-600 font-bold bg-emerald-50/80 scale-105' : 'text-slate-500 hover:text-emerald-600 font-medium' ?>">
            <div class="relative">
                <i class="fas fa-mosque text-lg"></i>
                <?php if ($current_active === 'ibadah_harian'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-emerald-600 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Ibadah</span>
        </a>

        <!-- 3. HAFALAN -->
        <a href="santri-laporan-hafalan.php" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-xl transition-all <?= ($current_active === 'santri_hafalan') ? 'text-purple-600 font-bold bg-purple-50/80 scale-105' : 'text-slate-500 hover:text-purple-600 font-medium' ?>">
            <div class="relative">
                <i class="fas fa-quran text-lg"></i>
                <?php if ($current_active === 'santri_hafalan'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-purple-600 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Hafalan</span>
        </a>

        <!-- 4. KEUANGAN -->
        <a href="ruang-santri-keuangan.php" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-xl transition-all <?= ($current_active === 'tabel_keuangan') ? 'text-amber-600 font-bold bg-amber-50/80 scale-105' : 'text-slate-500 hover:text-amber-600 font-medium' ?>">
            <div class="relative">
                <i class="fas fa-wallet text-lg"></i>
                <?php if ($current_active === 'tabel_keuangan'): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-amber-600 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Keuangan</span>
        </a>

        <!-- 5. AKUN -->
        <a href="santri-profil.php" class="flex flex-col items-center justify-center py-1.5 px-1 rounded-xl transition-all <?= (in_array($current_active, ['edit_profil_santri', 'ganti_password_santri'])) ? 'text-indigo-600 font-bold bg-indigo-50/80 scale-105' : 'text-slate-500 hover:text-indigo-600 font-medium' ?>">
            <div class="relative">
                <i class="fas fa-user-circle text-lg"></i>
                <?php if (in_array($current_active, ['edit_profil_santri', 'ganti_password_santri'])): ?>
                    <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 bg-indigo-600 rounded-full"></span>
                <?php endif; ?>
            </div>
            <span class="text-[10px] mt-1 tracking-tight">Akun</span>
        </a>

    </div>
</nav>
