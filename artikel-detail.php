<?php
require_once 'koneksi.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM artikel WHERE id = $id AND (status = 'publish' OR (status = 'jadwalkan' AND published_at <= NOW()))";
$result = $conn->query($sql);

if (!$result || $result->num_rows == 0) {
    die("<h2 style='text-align:center; padding: 50px; font-family:sans-serif;'>Artikel tidak ditemukan atau telah dihapus. <br><a href='artikel.php'>Kembali ke Blog</a></h2>");
}
$art = $result->fetch_assoc();
$imgUrl = !empty($art['gambar_cover']) ? $art['gambar_cover'] : 'https://images.unsplash.com/photo-1585036156171-384164a8c675?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
$tgl_rilis = !empty($art['published_at']) ? $art['published_at'] : $art['created_at'];

// SETUP SEO METADATA
$seo_title = !empty($art['meta_title']) ? $art['meta_title'] : $art['judul'] . " | Villa Quran";
$seo_desc = !empty($art['meta_description']) ? $art['meta_description'] : mb_strimwidth(strip_tags($art['konten']), 0, 155, '...');
$seo_keywords = !empty($art['meta_keywords']) ? $art['meta_keywords'] : "sekolah tahfidz, pesantren tahfidz, villa quran";
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seo_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($seo_desc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seo_keywords) ?>">
    <meta name="author" content="Villa Quran Indonesia">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Style dasar untuk konten artikel yang panjang agar enak dibaca */
        .artikel-konten p { margin-bottom: 1.5em; line-height: 1.8; color: #374151; font-size: 1.125rem; }
        .artikel-konten h2, .artikel-konten h3 { font-weight: bold; color: #111827; margin-top: 2em; margin-bottom: 1em; }
        .artikel-konten h2 { font-size: 1.5rem; }
        .artikel-konten ul, .artikel-konten ol { margin-left: 1.5em; margin-bottom: 1.5em; list-style-type: disc; line-height: 1.8; color: #374151; }
    </style>
</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50 flex flex-col min-h-screen">

    <header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="index.html" class="font-bold text-xl text-emerald-800 hover:text-emerald-600 transition"><i class="fas fa-leaf mr-2 text-emerald-500"></i>Villa Quran</a>
                <a href="artikel.php" class="text-gray-500 hover:text-emerald-600 text-sm font-medium"><i class="fas fa-arrow-left mr-1"></i> Kembali ke Blog</a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-4xl mx-auto px-4 py-10 w-full">
        <article class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <img src="<?= $imgUrl ?>" alt="Cover" class="w-full h-64 md:h-96 object-cover">
            
            <div class="p-8 md:p-12">
                <!-- Meta Data -->
                <div class="flex items-center space-x-4 mb-6">
                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"><?= htmlspecialchars($art['kategori']) ?></span>
                    <span class="text-sm text-gray-500"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($tgl_rilis)) ?></span>
                    <span class="text-sm text-gray-500"><i class="far fa-user mr-1"></i> <?= htmlspecialchars($art['penulis']) ?></span>
                </div>

                <!-- Judul -->
                <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-8 leading-tight"><?= htmlspecialchars($art['judul']) ?></h1>
                
                <!-- Konten Murni HTML dari TinyMCE -->
                <div class="artikel-konten">
                    <?= $art['konten'] ?>
                </div>
                
                <div class="mt-12 pt-8 border-t border-gray-100 text-center">
                    <p class="text-gray-600 font-medium mb-4">Bagikan artikel ini jika bermanfaat:</p>
                    <div class="flex justify-center space-x-3">
                        <a href="https://wa.me/?text=<?= urlencode($art['judul'] . ' - Baca di: http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 transition"><i class="fab fa-whatsapp"></i></a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition"><i class="fab fa-facebook-f"></i></a>
                    </div>
                </div>
            </div>
        </article>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-white text-xl font-bold mb-4">Tertarik bergabung dengan Villa Quran?</h2>
            <a href="index.html#spmb" class="bg-amber-500 hover:bg-amber-600 text-teal-950 px-6 py-2 rounded-full font-bold text-sm transition shadow-md inline-block mb-6">Informasi Pendaftaran</a>
            <p class="text-xs">&copy; 2026 Villa Quran Indonesia.</p>
        </div>
    </footer>
</body>
</html>