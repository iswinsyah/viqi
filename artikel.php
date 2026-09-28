<?php
require_once 'koneksi.php';
$sql = "SELECT * FROM artikel WHERE status = 'publish' OR (status = 'jadwalkan' AND published_at <= NOW()) ORDER BY COALESCE(published_at, created_at) DESC";
$result = $conn->query($sql);
$articles = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) $articles[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kumpulan Artikel & Berita | Villa Quran Baron Malang</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Oswald:wght@400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @font-face {
            font-family: 'Marlin Condensed';
            src: local('Marlin Condensed Bold'),
                 local('Marlin Condensed'),
                 local('MarlinCondensed'),
                 local('Marlin-Condensed'),
                 local('Marlin'),
                 local('MarlinBold'),
                 url('fonts/MarlinCondensed-Bold.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }
        .font-marlin {
            font-family: 'Marlin Condensed', 'Barlow Condensed', 'Oswald', sans-serif;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50 flex flex-col min-h-screen">

    <!-- HEADER / NAVIGASI (2-ROW CLEAN & SPACIOUS LAYOUT) -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <!-- BARIS 1: BRANDING LOGO/TITLE & CTA / MEMBER -->
        <div class="border-b border-gray-100 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16 sm:h-20">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="index.html" class="flex items-center space-x-2.5 sm:space-x-3 text-emerald-900 hover:text-emerald-700 transition">
                            <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran Baron Malang" class="h-10 sm:h-12 w-auto inline-block">
                            <span class="inline-block font-marlin font-bold text-2xl sm:text-3xl lg:text-4xl text-emerald-900 tracking-tight uppercase leading-none">Villa Quran Baron Malang</span>
                        </a>
                    </div>
                    <div class="flex items-center space-x-2.5 sm:space-x-3">
                        <a href="login.php" class="hidden sm:inline-flex items-center px-3.5 py-2 text-xs sm:text-sm font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition border border-emerald-200">
                            <i class="fas fa-user-circle mr-1.5 text-emerald-600"></i> Member Area
                        </a>
                        <a href="index.html#spmb" class="hidden sm:inline-flex items-center bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white px-4 sm:px-5 py-2 rounded-xl text-xs sm:text-sm font-bold transition duration-200 shadow-sm shadow-emerald-500/30">
                            <i class="fas fa-user-plus mr-1.5"></i> Daftar SPMB
                        </a>
                        <div class="flex items-center md:hidden">
                            <button id="mobile-menu-btn" class="text-gray-700 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 rounded-lg p-2">
                                <i class="fas fa-bars text-2xl"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- BARIS 2: MENU NAVIGASI HORIZONTAL (DESKTOP) -->
        <div class="hidden md:block bg-slate-50/90 backdrop-blur border-b border-gray-200/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center justify-between py-2">
                    <div class="flex items-center space-x-1 lg:space-x-2">
                        <a href="index.html" class="px-3 py-1.5 text-sm font-semibold text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">Beranda</a>
                        <a href="index.html#profil" class="px-3 py-1.5 text-sm font-semibold text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">Profil</a>
                        <a href="profil-pengajar.html" class="px-3 py-1.5 text-sm font-semibold text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">Pengajar</a>
                        <a href="program.html" class="px-3 py-1.5 text-sm font-semibold text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">Kurikulum</a>
                        <a href="asrama.html" class="px-3 py-1.5 text-sm font-semibold text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">Fasilitas</a>
                        <a href="biaya.html" class="px-3 py-1.5 text-sm font-semibold text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg transition">Informasi Biaya</a>
                        <a href="artikel.php" class="px-3 py-1.5 text-sm font-bold text-emerald-700 bg-emerald-100/70 rounded-lg transition">Parenting & Artikel</a>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="brosur.php" class="inline-flex items-center px-3.5 py-1.5 text-xs sm:text-sm font-bold text-amber-700 bg-amber-50 hover:bg-amber-100 rounded-lg transition border border-amber-200/80">
                            <i class="fas fa-envelope-open-text mr-1.5 text-amber-600"></i> Brosur Digital
                        </a>
                    </div>
                </nav>
            </div>
        </div>

        <!-- Mobile Menu (Dropdown) -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 shadow-xl">
            <div class="px-4 pt-3 pb-6 space-y-2">
                <a href="index.html" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-md">Beranda</a>
                <a href="index.html#profil" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-md">Profil</a>
                <a href="profil-pengajar.html" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-md">Pengajar</a>
                <a href="program.html" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-md">Kurikulum</a>
                <a href="asrama.html" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-md">Fasilitas</a>
                <a href="biaya.html" class="block px-3 py-2 text-base font-medium text-gray-700 hover:text-emerald-700 hover:bg-emerald-50 rounded-md">Informasi Biaya</a>
                <a href="artikel.php" class="block px-3 py-2 text-base font-semibold text-emerald-700 bg-emerald-50 rounded-md">Parenting & Artikel</a>
                <a href="brosur.php" class="block px-3 py-2 text-base font-bold text-amber-600 hover:text-amber-700 hover:bg-amber-50 rounded-md"><i class="fas fa-envelope-open-text mr-2 text-amber-500"></i> Brosur Digital</a>
                <div class="pt-2 pb-1 border-t border-gray-100 mt-2 space-y-2">
                    <a href="login.php" class="block px-3 py-2.5 text-base font-bold text-emerald-700 bg-emerald-50 rounded-xl"><i class="fas fa-user-circle mr-2 text-emerald-600"></i> Member Area (Login)</a>
                    <a href="index.html#spmb" class="block w-full text-center bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white px-6 py-3 rounded-xl font-bold transition duration-300 shadow-md">Daftar SPMB</a>
                </div>
            </div>
        </div>
    </header>

    <!-- PAGE HEADER -->
    <section class="bg-teal-700 text-white py-16 text-center px-4">
        <h1 class="text-4xl font-extrabold mb-4">Blog & Inspirasi Tahfidz</h1>
        <p class="text-lg text-teal-100 max-w-2xl mx-auto">Kabar terbaru, tips menghafal Al-Quran, dan materi parenting Islami.</p>
    </section>

    <main class="flex-grow max-w-7xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if(count($articles) > 0): foreach($articles as $art): 
                $imgUrl = !empty($art['gambar_cover']) ? $art['gambar_cover'] : 'https://images.unsplash.com/photo-1585036156171-384164a8c675?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80';
                $cuplikan = mb_strimwidth(strip_tags($art['konten']), 0, 120, '...');
                $tgl_rilis = !empty($art['published_at']) ? $art['published_at'] : $art['created_at'];
            ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition duration-300 border border-gray-100 flex flex-col">
                <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($art['judul']) ?>" class="w-full h-48 object-cover">
                <div class="p-6 flex flex-col flex-grow">
                    <div class="flex items-center text-sm text-gray-500 mb-3">
                        <span class="text-emerald-600 font-semibold"><?= htmlspecialchars($art['kategori']) ?></span>
                        <span class="mx-2">&bull;</span>
                        <span><?= date('d M Y', strtotime($tgl_rilis)) ?></span>
                    </div>
                    <h3 class="font-bold text-xl text-gray-900 mb-2 hover:text-emerald-600 transition"><a href="artikel-detail.php?id=<?= $art['id'] ?>"><?= htmlspecialchars($art['judul']) ?></a></h3>
                    <p class="text-gray-600 text-sm mb-4 flex-grow"><?= $cuplikan ?></p>
                    <a href="artikel-detail.php?id=<?= $art['id'] ?>" class="text-emerald-600 font-bold hover:text-emerald-700 transition inline-flex items-center text-sm">Baca Selengkapnya <i class="fas fa-arrow-right ml-1"></i></a>
                </div>
            </div>
            <?php endforeach; else: ?>
                <div class="col-span-full text-center py-16 text-gray-500">
                    <i class="fas fa-book-open text-5xl mb-4 opacity-20"></i>
                    <p>Belum ada artikel yang dipublikasikan saat ini.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-12 text-center mt-auto">
        <div class="max-w-7xl mx-auto px-4"><p class="text-sm">&copy; <span id="footer-tahun">2026</span> <span id="footer-copy-nama">Villa Quran Baron Malang</span>. All rights reserved.</p></div>
    </footer>
</body>
</html>
<script>
// Mobile Menu Toggle
const btn = document.getElementById('mobile-menu-btn');
const menu = document.getElementById('mobile-menu');
if (btn && menu) {
    const icon = btn.querySelector('i');
    btn.addEventListener('click', () => {
        menu.classList.toggle('hidden');
        if (icon) {
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        }
    });
}

fetch('api-pengaturan.php?_=' + new Date().getTime()).then(res => res.json()).then(data => {
    if (document.getElementById('footer-tahun')) document.getElementById('footer-tahun').textContent = new Date().getFullYear();
    if (data.nama_sekolah && document.getElementById('footer-copy-nama')) document.getElementById('footer-copy-nama').textContent = data.nama_sekolah;
    if (data.logo_url) {
        document.querySelectorAll('.flex-shrink-0 a').forEach(a => {
            a.innerHTML = `<img src="${data.logo_url}" alt="Logo Villa Quran Baron Malang" class="h-10 sm:h-12 w-auto inline-block mr-2.5 sm:mr-3"><span class="inline-block font-marlin font-bold text-2xl sm:text-3xl lg:text-4xl text-emerald-900 tracking-tight uppercase leading-none">${data.nama_sekolah || 'Villa Quran Baron Malang'}</span>`;
        });
    }
}).catch(e => console.log('Setting error'));

// Ambil data agen dari memori dan tempelkan ke semua link artikel
const savedRef = localStorage.getItem('agen_ref');
if (savedRef && savedRef !== 'organik') {
    document.querySelectorAll('a[href^="artikel-detail.php"]').forEach(a => {
        if (!a.href.includes('ref=')) {
            a.href += '&ref=' + savedRef;
        }
    });
}
</script>