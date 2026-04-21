<!-- Overlay Sidebar untuk Mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 hidden md:hidden"></div>

<aside id="sidebar" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-50 h-full absolute md:relative">
    <div class="h-16 flex items-center justify-center border-b border-gray-800 px-4">
        <span class="text-xl font-bold text-emerald-400">
            <i class="fas fa-leaf mr-2"></i>VQ Admin
        </span>
        <button id="close-sidebar" class="md:hidden ml-auto text-gray-400 hover:text-white">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>
    
    <div class="flex-1 overflow-y-auto py-4">
        <nav class="space-y-1 px-2">
            <a href="admin.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-tachometer-alt w-6 text-center"></i>
                <span class="ml-3 font-medium">Dashboard</span>
            </a>
            <a href="admin-spmb.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-user-graduate w-6 text-center"></i>
                <span class="ml-3 font-medium">Data Pendaftar SPMB</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-users w-6 text-center"></i>
                <span class="ml-3 font-medium">Data Santri</span>
            </a>
            
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Marketing & Leads</p>
            </div>
            <a href="data-pipeline.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-columns w-6 text-center"></i>
                <span class="ml-3 font-medium">Pipeline Prospek</span>
            </a>
            <a href="data-agen.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-handshake w-6 text-center"></i>
                <span class="ml-3 font-medium">Data Agen</span>
            </a>
            <a href="admin-analisa.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-brain w-6 text-center text-purple-400"></i>
                <span class="ml-3 font-medium">Analisa Buyer Persona</span>
            </a>
            <a href="admin-kalender.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-calendar-alt w-6 text-center text-sky-400"></i>
                <span class="ml-3 font-medium">Kalender Konten AI</span>
            </a>
            <a href="admin-seo.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-pen-nib w-6 text-center text-teal-400"></i>
                <span class="ml-3 font-medium">Generator Artikel SEO</span>
            </a>
            <a href="admin-sosmed.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-mobile-alt w-6 text-center text-pink-400"></i>
                <span class="ml-3 font-medium">Generator Konten Sosmed</span>
            </a>

            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Konten Web</p>
            </div>
            <a href="admin-artikel.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group <?= $active_menu == 'artikel' ? 'bg-gray-800 text-white' : '' ?>">
                <i class="fas fa-file-alt w-6 text-center"></i>
                <span class="ml-3 font-medium">Artikel & Berita</span>
            </a>
            <a href="admin-tentang.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group <?= $active_menu == 'tentang' ? 'bg-gray-800 text-white' : '' ?>">
                <i class="fas fa-info-circle w-6 text-center text-blue-400"></i>
                <span class="ml-3 font-medium">Tentang Kami</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-chalkboard-teacher w-6 text-center"></i>
                <span class="ml-3 font-medium">Profil Pengajar</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-images w-6 text-center"></i>
                <span class="ml-3 font-medium">Galeri Kegiatan</span>
            </a>
            <a href="admin-media.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-folder-open w-6 text-center text-indigo-400"></i>
                <span class="ml-3 font-medium">Penyimpanan Media</span>
            </a>
            
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sistem</p>
            </div>
            <a href="admin-popup.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-bullhorn w-6 text-center"></i>
                <span class="ml-3 font-medium">Pengaturan Pop-up</span>
            </a>
            <a href="admin-hero.php" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group <?= $active_menu == 'hero' ? 'bg-gray-800 text-white' : '' ?>">
                <i class="fas fa-home w-6 text-center text-amber-400"></i>
                <span class="ml-3 font-medium">Pengaturan Hero & USP</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-800 hover:text-white rounded-lg transition group">
                <i class="fas fa-cog w-6 text-center"></i>
                <span class="ml-3 font-medium">Pengaturan Web</span>
            </a>
        </nav>
    </div>
    
    <div class="p-4 border-t border-gray-800">
        <a href="logout.php" class="flex items-center w-full px-4 py-2 text-sm text-gray-400 hover:text-white transition">
            <i class="fas fa-sign-out-alt w-5"></i> Keluar
        </a>
    </div>
</aside>