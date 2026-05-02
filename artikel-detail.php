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
    <!-- Open Graph Tags untuk memunculkan Thumbnail di WhatsApp & Facebook -->
    <meta property="og:title" content="<?= htmlspecialchars($seo_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seo_desc) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($imgUrl) ?>">
    <meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:type" content="article">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Style dasar untuk konten artikel yang panjang agar enak dibaca */
        .artikel-konten p { margin-bottom: 1.5em; line-height: 1.8; color: #374151; font-size: 1.125rem; }
        .artikel-konten h2, .artikel-konten h3 { font-weight: bold; color: #111827; margin-top: 2em; margin-bottom: 1em; }
        .artikel-konten h2 { font-size: 1.5rem; }
        .artikel-konten ul, .artikel-konten ol { margin-left: 1.5em; margin-bottom: 1.5em; list-style-type: disc; line-height: 1.8; color: #374151; }
        
        /* Styling agar gambar di dalam artikel otomatis ke tengah, besar, dan elegan */
        .artikel-konten img { 
            display: block !important; 
            margin: 2.5em auto !important; 
            max-width: 100%; 
            height: auto; 
            border-radius: 0.75rem; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); 
        }
    </style>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-M2F9GKZG');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="font-sans antialiased text-gray-800 bg-gray-50 flex flex-col min-h-screen">

    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-M2F9GKZG"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
            <div class="flex-shrink-0 flex items-center"><a href="index.html" class="font-bold text-xl text-emerald-800 hover:text-emerald-600 transition"><i class="fas fa-leaf mr-2 text-emerald-500"></i>Villa Quran</a></div>
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
            </div>
        </article>

        <!-- TOMBOL SHARE MELAYANG (FLOATING) -->
        <div class="fixed z-50 bottom-6 left-1/2 transform -translate-x-1/2 xl:top-1/2 xl:bottom-auto xl:left-8 xl:-translate-x-0 xl:-translate-y-1/2 bg-white/90 backdrop-blur shadow-2xl border border-gray-200 rounded-full px-4 py-3 xl:px-3 xl:py-4 flex flex-row xl:flex-col gap-3 items-center transition-all">
            <span class="text-[10px] font-bold text-gray-400 xl:mb-2 hidden xl:block tracking-widest" style="writing-mode: vertical-rl; transform: rotate(180deg);">BAGIKAN</span>
            <span class="text-[10px] font-bold text-gray-400 mr-1 xl:hidden">SHARE</span>
            <a id="share-wa" href="#" target="_blank" class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 transition shadow-md hover:scale-110" title="Bagikan ke WhatsApp"><i class="fab fa-whatsapp text-lg"></i></a>
            <a id="share-fb" href="#" target="_blank" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition shadow-md hover:scale-110" title="Bagikan ke Facebook"><i class="fab fa-facebook-f text-lg"></i></a>
            <a id="share-tw" href="#" target="_blank" class="w-10 h-10 rounded-full bg-gray-800 text-white flex items-center justify-center hover:bg-gray-900 transition shadow-md hover:scale-110" title="Bagikan ke X/Twitter"><i class="fab fa-twitter text-lg"></i></a>
            <button id="copy-link" class="w-10 h-10 rounded-full bg-gray-200 text-gray-700 flex items-center justify-center hover:bg-gray-300 transition shadow-md hover:scale-110 focus:outline-none" title="Salin Link"><i class="fas fa-link text-lg"></i></button>
        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-auto">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-white text-xl font-bold mb-4">Tertarik bergabung dengan Villa Quran?</h2>
            <a href="index.html#spmb" class="bg-amber-500 hover:bg-amber-600 text-teal-950 px-6 py-2 rounded-full font-bold text-sm transition shadow-md inline-block mb-6">Informasi Pendaftaran</a>
                <p class="text-xs">&copy; <span id="footer-tahun">2026</span> <span id="footer-copy-nama">Villa Quran Indonesia</span>.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Simpan/Ambil Kode Agen
            const urlParams = new URLSearchParams(window.location.search);
            const refCode = urlParams.get('ref');
            if (refCode) {
                localStorage.setItem('agen_ref', refCode);
            }
            const savedRef = localStorage.getItem('agen_ref');

            // 1.5 Auto Sinkronisasi Nama Sekolah & Tahun Copyright
            fetch('api-pengaturan.php?_=' + new Date().getTime()).then(res => res.json()).then(data => {
                if (document.getElementById('footer-tahun')) document.getElementById('footer-tahun').textContent = new Date().getFullYear();
                if (data.nama_sekolah && document.getElementById('footer-copy-nama')) document.getElementById('footer-copy-nama').textContent = data.nama_sekolah;
            if (data.logo_url) {
                document.querySelectorAll('.flex-shrink-0 a').forEach(a => {
                    a.innerHTML = `<img src="${data.logo_url}" alt="Logo" class="h-10 w-auto inline-block mr-2"><span class="hidden sm:inline-block">${data.nama_sekolah || 'Villa Quran'}</span>`;
                });
            }
            }).catch(e => console.log('Setting error'));

            // 2. Siapkan URL Artikel + Parameter Agen (jika ada)
            let currentUrl = window.location.origin + window.location.pathname + '?id=<?= $art['id'] ?>';
            if (savedRef && savedRef !== 'organik') {
                currentUrl += '&ref=' + savedRef;
                
                // Otomatis update Address Bar agar selalu tampil nomor referalnya
                const newBrowserUrl = window.location.pathname + '?id=<?= $art['id'] ?>&ref=' + savedRef;
                window.history.replaceState(null, '', newBrowserUrl);
                
                // TAMBAHAN: Otomatis tambahkan parameter referal ke semua link yang mengarah ke web kita
                document.querySelectorAll('.artikel-konten a, header a, footer a').forEach(link => {
                    let href = link.getAttribute('href');
                    // Abaikan link telepon, email, atau anchor ke id tertentu
                    if (href && !href.startsWith('#') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
                        try {
                            let urlObj = new URL(link.href); // Gunakan link.href yang sudah dibaca absolut oleh browser
                            // Jika link tersebut mengarah ke domain web kita sendiri dan belum ada parameter ref
                            if (urlObj.hostname === window.location.hostname && !urlObj.searchParams.has('ref')) {
                                urlObj.searchParams.set('ref', savedRef);
                                link.href = urlObj.toString();
                            }
                        } catch(e) {}
                    }
                });
            }
            
            const articleTitle = <?= json_encode($art['judul']) ?>;
            const articleDesc = <?= json_encode($seo_desc) ?>;
            
            // 3. Pasang URL ke Tombol Share
            const waText = encodeURIComponent("*" + articleTitle + "*\n\n" + articleDesc + "\n\n" + currentUrl);
            document.getElementById('share-wa').href = 'https://wa.me/?text=' + waText;
            document.getElementById('share-fb').href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(currentUrl);
            document.getElementById('share-tw').href = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(articleTitle) + '&url=' + encodeURIComponent(currentUrl);
            
            // 4. Tombol Copy Link
            document.getElementById('copy-link').addEventListener('click', function() {
                navigator.clipboard.writeText(currentUrl).then(() => {
                    const notif = savedRef && savedRef !== 'organik' ? '\n\n(Link sudah otomatis menyertakan nomor referal Anda: ' + savedRef + ')' : '';
                    alert('Link berhasil disalin!' + notif);
                }).catch(err => {
                    console.error('Gagal menyalin link: ', err);
                });
            });
        });
    </script>
    <!-- Mata AI Tracker -->
    <script src="tracker.js"></script>
</body>
</html>