<?php
// Ambil info user dari session jika tersedia
$y_nama = $_SESSION['nama_lengkap'] ?? ($_SESSION['yayasan_user'] ?? 'Pengurus Yayasan');
$y_user = $_SESSION['username'] ?? 'yayasan';
$y_role = $_SESSION['yayasan_role'] ?? ($_SESSION['role'] ?? 'Pengurus Yayasan');
$y_foto = $_SESSION['foto_profil'] ?? '';
$is_super_or_ketua = in_array(strtolower($y_role), ['super_admin', 'ketua_yayasan', 'admin', 'yayasan']) || empty($y_role);
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
    <div class="p-5 border-b border-teal-700/60 flex items-center justify-between bg-teal-900/40 flex-shrink-0">
        <a href="../dashboard.php" class="flex items-center space-x-3 group">
            <div class="w-11 h-11 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0 group-hover:scale-105 transition-transform">
                <svg viewBox="0 0 100 100" class="w-full h-full">
                    <circle cx="50" cy="46" r="10" fill="#f59e0b" />
                    <path d="M50 14 C47 24, 47 28, 50 32 C53 28, 53 24, 50 14 Z" fill="#10b981" />
                    <path d="M68 20 C61 28, 59 32, 60 36 C64 33, 68 31, 74 24 Z" fill="#10b981" />
                    <path d="M80 36 C71 40, 68 43, 67 48 C72 47, 76 46, 84 41 Z" fill="#10b981" />
                    <path d="M32 20 C39 28, 41 32, 40 36 C36 33, 32 31, 26 24 Z" fill="#10b981" />
                    <path d="M20 36 C29 40, 32 43, 33 48 C28 47, 24 46, 16 41 Z" fill="#10b981" />
                    <path d="M30 62 C42 56, 48 60, 50 66 C52 60, 58 56, 70 62 C68 70, 52 74, 50 74 C48 74, 32 70, 30 62 Z" fill="#f59e0b" />
                    <path d="M22 68 C36 58, 48 64, 50 72 C52 64, 64 58, 78 68 C75 80, 52 86, 50 86 C48 86, 25 80, 22 68 Z" fill="#0b8478" />
                </svg>
            </div>
            <div>
                <h1 class="font-black text-xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[10px] text-teal-200 font-light italic tracking-tight mt-0.5">Ruang Yayasan</p>
            </div>
        </a>
        <button id="close-sidebar-yayasan2" class="md:hidden text-teal-200 hover:text-white p-1 rounded-lg focus:outline-none">
            <i class="fas fa-times text-lg"></i>
        </button>
    </div>

    <!-- SIDEBAR PROFILE CARD -->
    <div class="px-5 py-3.5 border-b border-teal-700/40 bg-teal-950/20 flex-shrink-0">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-sm shadow-sm border-2 border-white/80 overflow-hidden flex-shrink-0">
                <?php if (!empty($y_foto)): ?>
                    <img src="<?= htmlspecialchars($y_foto) ?>" alt="Avatar" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="fas fa-user text-[#0b8478]"></i>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden flex-1">
                <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($y_nama) ?></h4>
                <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                    <?= htmlspecialchars($y_role) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- SIDEBAR NAVIGATION LINKS (SCROLLABLE) -->
    <div class="flex-1 overflow-y-auto py-3 px-3 space-y-1 text-xs" style="scrollbar-width: thin; scrollbar-color: #086a60 transparent;">
        
        <!-- 1. STANDAR SUPER-APP MENU -->
        <div class="px-2 pt-1 pb-1 text-[10px] font-black uppercase tracking-wider text-teal-200">
            Navigasi Utama
        </div>
        <a href="../dashboard.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
            <i class="fas fa-house w-4 text-center"></i>
            <span>Beranda</span>
        </a>
        <a href="../kalender-akademik.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
            <i class="fas fa-calendar-alt w-4 text-center"></i>
            <span>Kalender</span>
        </a>
        <a href="../admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
            <i class="fas fa-clock w-4 text-center"></i>
            <span>Jadwal</span>
        </a>
        <a href="../artikel.php" class="flex items-center gap-3 px-3 py-2 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
            <i class="fas fa-bullhorn w-4 text-center"></i>
            <span>Info</span>
        </a>

        <!-- 2. MENU UTAMA YAYASAN -->
        <div class="pt-3 border-t border-teal-700/50">
            <p class="px-2 text-[10px] font-black uppercase tracking-wider text-teal-200 mb-1">Menu Utama Yayasan</p>
            <a href="asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'asatidz') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-users-gear w-4 text-center"></i>
                <span>Daftar Pegawai & Asatidz</span>
            </a>
            <?php if ($is_super_or_ketua): ?>
            <a href="manajemen-menu.php" class="<?= (isset($active_menu) && $active_menu == 'manajemen_menu') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-sitemap w-4 text-center"></i>
                <span>Manajemen Menu</span>
            </a>
            <?php endif; ?>
            <a href="pengumuman-update.php" class="<?= (isset($active_menu) && $active_menu == 'pengumuman_update') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-bullhorn w-4 text-center"></i>
                <span>Update Fitur Aplikasi</span>
            </a>
        </div>

        <!-- 3. MASTER DATA AKADEMIK & KURIKULUM -->
        <div class="pt-3 border-t border-teal-700/50">
            <p class="px-2 text-[10px] font-black uppercase tracking-wider text-teal-200 mb-1">Master Data</p>
            <a href="master-kelas.php" class="<?= (isset($active_menu) && $active_menu == 'master_kelas') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-school w-4 text-center"></i>
                <span>Master Kelas</span>
            </a>
            <a href="master-mapel.php" class="<?= (isset($active_menu) && $active_menu == 'master_mapel') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-book w-4 text-center"></i>
                <span>Master Mapel</span>
            </a>
            <a href="elearning-yayasan.php" class="<?= (isset($active_menu) && $active_menu == 'elearning_yayasan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-robot w-4 text-center"></i>
                <span>Kurikulum & E-Learning (AI)</span>
            </a>
            <a href="kitab-rujukan.php" class="<?= (isset($active_menu) && $active_menu == 'kitab_rujukan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-book-open w-4 text-center"></i>
                <span>Kitab Rujukan</span>
            </a>
            <a href="master-kalender.php" class="<?= (isset($active_menu) && $active_menu == 'master_kalender') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Master Kalender Akademik</span>
            </a>
            <a href="laporan-setoran-hafalan.php" class="<?= (isset($active_menu) && $active_menu == 'laporan_setoran_yayasan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-book-quran w-4 text-center"></i>
                <span>Laporan Setoran Hafalan</span>
            </a>
            <a href="ibadah-harian-santri.php" class="<?= (isset($active_menu) && $active_menu == 'ibadah_harian_yayasan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-mosque w-4 text-center"></i>
                <span>Rekap Ibadah Harian Santri</span>
            </a>
            <a href="rapot-pkbm.php" class="<?= (isset($active_menu) && $active_menu == 'rapot_pkbm_yayasan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-file-invoice w-4 text-center"></i>
                <span>Monitoring Raport PKBM</span>
            </a>
        </div>

        <!-- 4. KEUANGAN & SDM -->
        <div class="pt-3 border-t border-teal-700/50">
            <p class="px-2 text-[10px] font-black uppercase tracking-wider text-teal-200 mb-1">Keuangan & SDM</p>
            <a href="pembukuan.php" class="<?= (isset($active_menu) && $active_menu == 'pembukuan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-calculator w-4 text-center"></i>
                <span>Pembukuan Terpusat (AI)</span>
            </a>
            <a href="pembukuan.php?tab=proyeksi" class="<?= (isset($active_menu) && $active_menu == 'cashflow') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-funnel-dollar w-4 text-center"></i>
                <span>Perencanaan Kas (Cashflow)</span>
            </a>
            <a href="kpi.php" class="<?= (isset($active_menu) && $active_menu == 'kpi_yayasan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-chart-bar w-4 text-center"></i>
                <span>Monitoring AI & Kinerja Pegawai</span>
            </a>
            <a href="kpi-musyrif.php" class="<?= (isset($active_menu) && $active_menu == 'kpi_musyrif') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-chart-line w-4 text-center"></i>
                <span>KPI Musyrif Asrama</span>
            </a>
            <a href="kpi-kepala-sekolah.php" class="<?= (isset($active_menu) && $active_menu == 'kpi_kepsek') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-chart-pie w-4 text-center"></i>
                <span>KPI Kepala Sekolah</span>
            </a>
            <a href="../admin-supervisi-mengajar.php" class="<?= (isset($active_menu) && $active_menu == 'supervisi_mengajar') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-clipboard-check w-4 text-center"></i>
                <span>Supervisi Mengajar</span>
            </a>
            <a href="gaji-pegawai.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_pegawai') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-coins w-4 text-center"></i>
                <span>Rekap Gaji (Payroll)</span>
            </a>
            <a href="gaji-asatidz.php" class="<?= (isset($active_menu) && $active_menu == 'gaji_asatidz') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-sliders w-4 text-center"></i>
                <span>Pengaturan Tarif Gaji</span>
            </a>
            <a href="ai-agent-hrd.php" class="<?= (isset($active_menu) && $active_menu == 'ai_agent_hrd') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-robot w-4 text-center"></i>
                <span>AI Agent HRD & Personalia</span>
            </a>
            <a href="rekap-spp.php" class="<?= (isset($active_menu) && $active_menu == 'rekap_keuangan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-file-invoice-dollar w-4 text-center"></i>
                <span>Rekap Pembayaran SPP</span>
            </a>
            <a href="rekap-uang-saku.php" class="<?= (isset($active_menu) && $active_menu == 'rekap_uang_saku') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-wallet w-4 text-center"></i>
                <span>Rekap Uang Saku Santri</span>
            </a>
            <a href="tunjangan.php" class="<?= (isset($active_menu) && $active_menu == 'tunjangan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-award w-4 text-center"></i>
                <span>Pengaturan Tunjangan</span>
            </a>
        </div>

        <!-- 5. RENCANA & STRATEGI -->
        <div class="pt-3 border-t border-teal-700/50">
            <p class="px-2 text-[10px] font-black uppercase tracking-wider text-teal-200 mb-1">Rencana & Strategi</p>
            <a href="analisis-swot.php" class="<?= (isset($active_menu) && $active_menu == 'analisis_swot') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-chart-line w-4 text-center"></i>
                <span>Analisis SWOT & Strategi</span>
            </a>
            <a href="struktur-jobdesc.php" class="<?= (isset($active_menu) && $active_menu == 'struktur_jobdesc') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-sitemap w-4 text-center"></i>
                <span>Struktur Organisasi</span>
            </a>
            <a href="jobdesc.php" class="<?= (isset($active_menu) && $active_menu == 'jobdesc_yayasan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-id-card w-4 text-center"></i>
                <span>Job Description Pegawai</span>
            </a>
            <a href="admin-peraturan.php" class="<?= (isset($active_menu) && $active_menu == 'admin_peraturan') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-gavel w-4 text-center"></i>
                <span>SOP & Peraturan Yayasan</span>
            </a>
            <a href="kurikulum-solopreneur.php" class="<?= (isset($active_menu) && $active_menu == 'kurikulum_solopreneur') ? 'bg-white text-[#0b8478] font-black shadow-sm' : 'text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold' ?> flex items-center gap-3 px-3 py-2 rounded-xl text-xs transition">
                <i class="fas fa-rocket w-4 text-center"></i>
                <span>Kurikulum Solopreneur (AI)</span>
            </a>
        </div>

    </div>

    <!-- SIDEBAR FOOTER: KELUAR -->
    <div class="p-3.5 border-t border-teal-700/60 bg-teal-950/20 flex-shrink-0">
        <a href="../dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
            <i class="fas fa-arrow-right-from-bracket"></i> Keluar
        </a>
    </div>
</aside>

<!-- ========================================================= -->
<!-- BOTTOM NAVIGATION BAR (HANYA MUNCUL DI MOBILE / md:hidden) -->
<!-- ========================================================= -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 h-16 bg-white border-t border-teal-100 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] flex items-center justify-around z-40 max-w-[440px] mx-auto px-2">
    <a href="../dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 text-[#0b8478] font-black text-[10px]">
        <div class="w-9 h-7 rounded-full bg-teal-50 flex items-center justify-center mb-0.5">
            <i class="fas fa-house text-base text-[#0b8478]"></i>
        </div>
        <span>Beranda</span>
    </a>
    <a href="../kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-[#0b8478] font-bold text-[10px] transition">
        <i class="fas fa-calendar-alt text-lg mb-0.5"></i>
        <span>Kalender</span>
    </a>
    <a href="../admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-[#0b8478] font-bold text-[10px] transition">
        <i class="fas fa-clock text-lg mb-0.5"></i>
        <span>Jadwal</span>
    </a>
    <a href="../artikel.php" class="flex flex-col items-center justify-center flex-1 py-1 text-slate-500 hover:text-[#0b8478] font-bold text-[10px] transition">
        <i class="fas fa-bullhorn text-lg mb-0.5"></i>
        <span>Info</span>
    </a>
    <a href="../dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center flex-1 py-1 text-rose-500 hover:text-rose-700 font-bold text-[10px] transition">
        <i class="fas fa-arrow-right-from-bracket text-lg mb-0.5"></i>
        <span>Keluar</span>
    </a>
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