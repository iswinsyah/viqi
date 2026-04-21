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
    <title>Kumpulan Artikel & Berita | Villa Quran Indonesia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50 flex flex-col min-h-screen">

    <!-- HEADER / NAVIGASI -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.html" class="font-extrabold text-2xl text-emerald-800 hover:text-emerald-600 transition"><i class="fas fa-leaf mr-2 text-emerald-500"></i>Villa Quran</a>
                </div>
                <nav class="hidden md:flex space-x-6 lg:space-x-8">
                    <a href="index.html" class="text-gray-600 hover:text-emerald-700 font-semibold transition">Beranda</a>
                    <a href="profil-pengajar.html" class="text-gray-600 hover:text-emerald-700 font-semibold transition">Profil Pengajar</a>
                    <a href="program.html" class="text-gray-600 hover:text-emerald-700 font-semibold transition">Program</a>
                    <a href="asrama.html" class="text-gray-600 hover:text-emerald-700 font-semibold transition">Asrama</a>
                    <a href="artikel.php" class="text-emerald-700 font-bold transition border-b-2 border-emerald-600">Artikel</a>
                </nav>
                <div class="hidden md:flex"><a href="index.html#spmb" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-full font-bold transition shadow-md">Daftar SPMB</a></div>
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
        <div class="max-w-7xl mx-auto px-4"><p class="text-sm">&copy; 2026 Villa Quran Indonesia. All rights reserved.</p></div>
    </footer>
</body>
</html>