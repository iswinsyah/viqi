<?php
// Cek menu apa yang sedang aktif dari file pemanggil
$active = isset($active_menu) ? $active_menu : '';
?>
<aside id="sidebar" class="bg-gray-900 text-white w-64 flex-shrink-0 hidden md:flex flex-col transition-all duration-300 z-20 h-full absolute md:relative">
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
            <a href="admin.php" class="flex items-center px-4 py-3 <?= $active == 'dashboard' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-tachometer-alt w-6 text-center"></i>
                <span class="ml-3 font-medium">Dashboard</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 <?= $active == 'spmb' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-user-graduate w-6 text-center"></i>
                <span class="ml-3 font-medium">Data Pendaftar SPMB</span>
                <span class="ml-auto bg-rose-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">12</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 <?= $active == 'santri' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-users w-6 text-center"></i>
                <span class="ml-3 font-medium">Data Santri</span>
            </a>
            
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Marketing & Leads</p>
            </div>
            <a href="data-pipeline.php" class="flex items-center px-4 py-3 <?= $active == 'pipeline' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-columns w-6 text-center"></i>
                <span class="ml-3 font-medium">Pipeline Prospek</span>
            </a>
            <a href="data-agen.php" class="flex items-center px-4 py-3 <?= $active == 'agen' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-handshake w-6 text-center"></i>
                <span class="ml-3 font-medium">Data Agen</span>
            </a>
            <a href="admin-analisa.php" class="flex items-center px-4 py-3 <?= $active == 'analisa' ? 'bg-purple-600 text-white shadow-md' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-brain w-6 text-center <?= $active == 'analisa' ? '' : 'text-purple-400' ?>"></i>
                <span class="ml-3 font-medium">Analisa Buyer Persona</span>
            </a>

            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Konten Web</p>
            </div>
            <a href="#" class="flex items-center px-4 py-3 <?= $active == 'artikel' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-file-alt w-6 text-center"></i>
                <span class="ml-3 font-medium">Artikel & Berita</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 <?= $active == 'pengajar' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-chalkboard-teacher w-6 text-center"></i>
                <span class="ml-3 font-medium">Profil Pengajar</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 <?= $active == 'galeri' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-images w-6 text-center"></i>
                <span class="ml-3 font-medium">Galeri Kegiatan</span>
            </a>
            
            <div class="pt-4 pb-2">
                <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Sistem</p>
            </div>
            <a href="admin-popup.php" class="flex items-center px-4 py-3 <?= $active == 'popup' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-bullhorn w-6 text-center"></i>
                <span class="ml-3 font-medium">Pengaturan Pop-up</span>
            </a>
            <a href="#" class="flex items-center px-4 py-3 <?= $active == 'pengaturan' ? 'bg-emerald-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?> rounded-lg transition group">
                <i class="fas fa-cog w-6 text-center"></i>
                <span class="ml-3 font-medium">Pengaturan Web</span>
            </a>
        </nav>
    </div>
    
    <div class="p-4 border-t border-gray-800">
        <a href="index.html" class="flex items-center w-full px-4 py-2 text-sm text-gray-400 hover:text-white transition">
            <i class="fas fa-sign-out-alt w-5"></i> Keluar
        </a>
    </div>
</aside>