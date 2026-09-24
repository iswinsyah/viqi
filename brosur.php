<?php
// brosur.php
// Halaman Khusus Brosur Sekolah Model Undangan Digital Interaktif
// Villa Quran Indonesia - Mobile-First Luxury Edition

require_once 'koneksi.php';

// Ambil parameter personalisasi & afiliasi
$to_param   = isset($_GET['to']) ? trim($_GET['to']) : '';
$nama_tamu  = !empty($to_param) ? htmlspecialchars($to_param) : 'Bapak / Ibu Calon Wali Santri & Keluarga';
$kode_ref   = isset($_GET['ref']) ? trim(htmlspecialchars($_GET['ref'])) : 'organik';

// Cari nama agen jika ref cocok di tabel agen
$nama_agen_pengundang = '';
if (!empty($kode_ref) && $kode_ref !== 'organik') {
    $q_ag = $conn->query("SELECT nama FROM agen WHERE kode_ref = '$kode_ref' OR whatsapp = '$kode_ref' LIMIT 1");
    if ($q_ag && $q_ag->num_rows > 0) {
        $row_ag = $q_ag->fetch_assoc();
        $nama_agen_pengundang = $row_ag['nama'];
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Undangan Silaturahmi & Brosur Digital | Villa Quran Indonesia</title>

    <!-- Meta Tags & OpenGraph untuk Thumbnail WhatsApp -->
    <meta name="description" content="Undangan Khusus Silaturahmi & Brosur Pendidikan Generasi Qur'ani. Tahfidz Mutqin 15-30 Juz Bersanad, Berijazah Resmi SMP-SMA, Digital Marketing & Solopreneur di Villa Quran Indonesia.">
    <meta property="og:title" content="Undangan Khusus untuk <?= htmlspecialchars($nama_tamu) ?> - Villa Quran Indonesia">
    <meta property="og:description" content="Mencetak Hafidz Bersanad di Lingkungan Asri ala Villa. Tahfidz Mutqin, Ijazah Resmi Negara & Skill Solopreneur Masa Depan.">
    <meta property="og:image" content="https://villaquranindonesia.com/upload/logo-villa-quran.png">
    <meta property="og:type" content="website">

    <!-- Google Fonts: Plus Jakarta Sans & Amiri Calligraphy -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            850: '#064e45',
                            950: '#022d27',
                        },
                        gold: {
                            100: '#fef3c7',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        arabic: ['"Amiri"', 'serif'],
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome 6 Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f6f7f5;
            color: #1f2937;
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }

        /* Background Islamic Geometric Pattern */
        .bg-pattern {
            background-image: radial-gradient(rgba(11, 132, 120, 0.08) 1.5px, transparent 1.5px), radial-gradient(rgba(217, 119, 6, 0.08) 1.5px, #f6f7f5 1.5px);
            background-size: 36px 36px;
            background-position: 0 0, 18px 18px;
        }

        /* Gold Gradient Elements */
        .text-gold-gradient {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .border-gold-gradient {
            border-image: linear-gradient(135deg, #fbbf24, #d97706, #78350f) 1;
        }

        .gold-border {
            border: 1px solid rgba(217, 119, 6, 0.35);
        }

        .gold-glow {
            box-shadow: 0 0 25px rgba(245, 158, 11, 0.25);
        }

        /* Cover Envelope Animation */
        #envelope-cover {
            transition: transform 0.8s cubic-bezier(0.77, 0, 0.175, 1), opacity 0.8s ease;
        }

        .cover-hidden {
            transform: translateY(-100%);
            opacity: 0;
            pointer-events: none;
        }

        /* Floating Audio Vinyl Rotation */
        @keyframes spinSlow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spinSlow 8s linear infinite;
        }

        /* Glassmorphism Card */
        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(11, 132, 120, 0.12);
        }

        .glass-dark {
            background: rgba(4, 51, 44, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
    </style>
</head>
<body class="bg-pattern antialiased">

    <!-- ============================================================ -->
    <!-- 1. FULLSCREEN COVER AMPLOP DIGITAL (OPENING EXPERIENCE)      -->
    <!-- ============================================================ -->
    <div id="envelope-cover" class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-[#032621] via-[#064e45] to-[#021d19] text-white p-4 overflow-y-auto">
        
        <!-- Ornamen Latar & Lingkaran Emas -->
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: url('https://www.transparenttextures.com/patterns/arabesque.png');"></div>
        <div class="absolute -top-24 -left-24 w-80 h-80 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-80 h-80 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Kartu Amplop Mewah -->
        <div class="relative w-full max-w-md my-auto rounded-3xl p-6 sm:p-8 text-center border border-amber-500/30 shadow-2xl glass-dark backdrop-blur-2xl">
            
            <!-- Ornamen Bismillah -->
            <p class="font-arabic text-xl sm:text-2xl text-amber-300/90 mb-3 tracking-wide">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
            
            <!-- Logo Villa Quran -->
            <div class="relative inline-block mb-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto rounded-2xl p-2 bg-gradient-to-b from-amber-400/20 to-transparent border border-amber-400/40 flex items-center justify-center shadow-lg">
                    <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-16 h-16 sm:w-20 sm:h-20 object-contain drop-shadow-md">
                </div>
                <span class="absolute -bottom-2 bg-gradient-to-r from-amber-500 to-amber-600 text-[10px] font-extrabold uppercase px-3 py-0.5 rounded-full text-emerald-950 tracking-wider shadow">Resmi SPMB</span>
            </div>

            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Villa Quran Indonesia</h1>
            <p class="text-xs text-amber-200/80 font-medium mb-6">Sekolah Tahfidz Berasrama Nyaman Ala Villa</p>

            <!-- Segel Nama Tamu Calon Wali Santri -->
            <div class="bg-black/30 border border-amber-500/30 rounded-2xl p-5 mb-6 shadow-inner relative overflow-hidden">
                <div class="absolute top-0 right-0 transform translate-x-3 -translate-y-3 w-12 h-12 bg-amber-400/10 rounded-full blur-lg"></div>
                <p class="text-[11px] uppercase tracking-widest text-amber-300 font-semibold mb-1">Kepada Yth. Calon Wali Santri:</p>
                <div class="text-lg sm:text-xl font-extrabold text-white leading-snug py-1">
                    <?= $nama_tamu ?>
                </div>
                <?php if (!empty($nama_agen_pengundang)): ?>
                    <p class="text-[11px] text-emerald-300/90 mt-1 italic"><i class="fas fa-hand-holding-heart mr-1"></i> Rekomendasi: <?= htmlspecialchars($nama_agen_pengundang) ?></p>
                <?php endif; ?>
                <div class="mt-3 pt-3 border-t border-white/10 text-[11px] text-gray-300 leading-relaxed">
                    Undangan Mahabbah Silaturahmi & Brosur Informasi Pendidikan Putra-Putri Generasi Qur'ani.
                </div>
            </div>

            <!-- Tombol Buka Undangan -->
            <button onclick="bukaUndangan()" class="w-full bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-emerald-950 font-black text-base sm:text-lg py-3.5 px-6 rounded-2xl shadow-xl transition-all transform active:scale-95 flex items-center justify-center gap-3 gold-glow group">
                <span class="w-7 h-7 rounded-full bg-emerald-950 text-amber-400 flex items-center justify-center text-xs group-hover:rotate-12 transition"><i class="fas fa-envelope-open-text"></i></span>
                <span>Buka Brosur & Undangan</span>
            </button>
            
            <p class="text-[11px] text-gray-400 mt-4 flex items-center justify-center gap-1.5">
                <i class="fas fa-volume-up text-amber-400 text-xs"></i> <span>Dilengkapi alunan backsound syahdu</span>
            </p>
        </div>
    </div>

    <!-- Audio Element untuk Backsound (Local Hostinger, 0 Buffering!) -->
    <audio id="audio-backsound" src="upload/backsound.mp3" loop preload="auto"></audio>

    <!-- Floating Audio Control Button -->
    <div id="audio-control" class="fixed top-4 right-4 z-40 hidden">
        <button onclick="toggleAudio()" class="flex items-center gap-2 bg-emerald-900/90 text-amber-300 px-3.5 py-2 rounded-full border border-amber-500/40 shadow-xl backdrop-blur-md text-xs font-bold transition hover:bg-emerald-800">
            <span id="audio-icon" class="w-5 h-5 rounded-full bg-amber-400 text-emerald-950 flex items-center justify-center animate-spin-slow">
                <i class="fas fa-music text-[10px]"></i>
            </span>
            <span id="audio-label" class="hidden sm:inline">Audio On</span>
        </button>
    </div>

    <!-- ============================================================ -->
    <!-- MAIN CONTENT CONTAINER (MOBILE FIRST LAYOUT)                  -->
    <!-- ============================================================ -->
    <div id="main-content" class="max-w-xl mx-auto px-4 pt-6 pb-28 min-h-screen">

        <!-- HEADER BRANDING -->
        <header class="text-center mb-8">
            <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-bold mb-3 shadow-sm">
                <i class="fas fa-star text-amber-600"></i>
                <span>Tahun Ajaran Baru &bull; Kuota Terbatas</span>
            </div>
            <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-16 h-16 mx-auto mb-2 drop-shadow">
            <h1 class="text-2xl sm:text-3xl font-black text-emerald-950 tracking-tight">Villa Quran Indonesia</h1>
            <p class="text-sm text-emerald-700 font-semibold">Pesantren Tahfidz Berasrama Nyaman Ala Villa</p>
            <div class="w-16 h-1 bg-gradient-to-r from-amber-400 to-amber-600 mx-auto rounded-full mt-3"></div>
        </header>

        <!-- MUQADDIMAH & SALAM HORMAT -->
        <section class="glass-card rounded-3xl p-6 sm:p-7 shadow-lg mb-8 border border-emerald-100 relative overflow-hidden">
            <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-amber-400/10 rounded-full blur-2xl"></div>
            
            <p class="text-center font-arabic text-2xl text-emerald-900 font-bold mb-2 leading-loose">السَّلاَمُ عَلَيْكُمْ وَرَحْمَةُ اللهِ وَبَرَكَاتُهُ</p>
            
            <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-100 mb-4 text-center">
                <p class="text-xs uppercase tracking-wider font-bold text-emerald-800">Spesial Untuk:</p>
                <p class="text-lg font-black text-emerald-950 mt-0.5"><?= $nama_tamu ?></p>
            </div>

            <p class="text-sm text-gray-700 leading-relaxed mb-4 text-justify">
                Segala puji bagi Allah Subhanahu wa Ta'ala yang telah mengkaruniakan amanah putra-putri tercinta kepada kita. Merupakan impian terindah setiap orang tua muslim kelak di yaumil akhir disematkan <strong>Mahkota Kehormatan yang cahayanya lebih terang dari matahari</strong> karena keberkahan ananda yang menghafal Al-Qur'an.
            </p>

            <div class="border-l-4 border-amber-500 pl-4 py-2 bg-amber-50/60 rounded-r-xl italic text-xs text-amber-950 leading-relaxed mb-4">
                "Siapa yang membaca Al-Qur'an, mempelajarinya, dan mengamalkannya, maka pada hari kiamat dipakaikan kepada kedua orang tuanya mahkota dari cahaya..." 
                <span class="block font-bold mt-1 text-amber-800">— HR. Al-Hakim</span>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                Kami hadir untuk membersamai ikhtiar mulia Bapak/Ibu melalui kurikulum terpadu: <strong>Ketinggian Al-Qur'an, Legalitas Ijazah Resmi Negara, serta Penguasaan Teknologi & Wirausaha Modern.</strong>
            </p>
        </section>

        <!-- ============================================================ -->
        <!-- 2. TARGET KOMPETENSI LULUSAN (USER PROMPT NO 2)             -->
        <!-- ============================================================ -->
        <section class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-400 flex items-center justify-center font-bold text-sm shadow">01</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Target Kompetensi Lulusan</h2>
                    <p class="text-xs text-gray-500">Standar mutu & profil lulusan santri Villa Quran</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <!-- Kompetensi 1 -->
                <div class="glass-card rounded-2xl p-4.5 p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-quran"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Mutqin 15 s/d 30 Juz Bersanad</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Hafal kuat dengan standar tahsin fashahah, tajwid mutqin, dan berhak mengantongi sanad qira'ah bersambung.</p>
                </div>

                <!-- Kompetensi 2 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Ijazah Resmi Setara SMP/SMA</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Lulus mengantongi legalitas ijazah negara terakreditasi, bebas tembus PTN, PTKIN, kedinasan & kampus luar negeri.</p>
                </div>

                <!-- Kompetensi 3 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Kecakapan Solopreneur & AI</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Menguasai keterampilan digital marketing, content creator dakwah, serta AI terapan untuk produktivitas masa depan.</p>
                </div>

                <!-- Kompetensi 4 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 hover:shadow-md transition">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-800 flex items-center justify-center text-lg mb-3">
                        <i class="fas fa-hands-holding-child"></i>
                    </div>
                    <h3 class="font-bold text-sm text-emerald-950 mb-1">Adab Luhur & Mandiri</h3>
                    <p class="text-xs text-gray-600 leading-relaxed">Pribadi sholeh berkarakter mandiri, disiplin ibadah harian tanpa disuruh, serta santun berbakti pada orang tua.</p>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 3. TIGA PILAR UTAMA VILLA QURAN (USER PROMPT NO 3 KOREKSI)  -->
        <!-- ============================================================ -->
        <section class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center font-bold text-sm shadow">02</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Tiga Pilar Utama Kurikulum</h2>
                    <p class="text-xs text-gray-500">Pondasi keunggulan pembelajaran komprehensif</p>
                </div>
            </div>

            <div class="space-y-4">
                <!-- Pilar 1 -->
                <div class="rounded-3xl p-5 bg-gradient-to-br from-emerald-900 to-emerald-950 text-white shadow-xl relative overflow-hidden border border-emerald-700/50">
                    <div class="absolute -right-4 -bottom-4 text-emerald-800/30 text-8xl font-black pointer-events-none select-none">1</div>
                    <div class="flex items-start gap-3.5 relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-amber-400 text-emerald-950 flex-shrink-0 flex items-center justify-center text-xl font-extrabold shadow-md">
                            <i class="fas fa-book-quran"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-extrabold px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30">Pilar 1</span>
                            <h3 class="text-base sm:text-lg font-black text-amber-300 mt-1 mb-1.5 leading-snug">Tahfidz Mutqin 15 s/d 30 Juz Bersanad</h3>
                            <p class="text-xs text-emerald-100 leading-relaxed">
                                Pendampingan talaqqi intensif dari asatidz hafizh mutqin. Menggunakan metode sima'an berjenjang, karantina tahfidz, dan ujian tasmi' terbuka sehingga hafalan benar-benar menancap kuat di dada ananda.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pilar 2 -->
                <div class="rounded-3xl p-5 bg-gradient-to-br from-teal-900 to-emerald-900 text-white shadow-xl relative overflow-hidden border border-teal-700/50">
                    <div class="absolute -right-4 -bottom-4 text-teal-800/30 text-8xl font-black pointer-events-none select-none">2</div>
                    <div class="flex items-start gap-3.5 relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-teal-400 text-emerald-950 flex-shrink-0 flex items-center justify-center text-xl font-extrabold shadow-md">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-extrabold px-2.5 py-0.5 rounded-full bg-teal-400/20 text-teal-300 border border-teal-400/30">Pilar 2</span>
                            <h3 class="text-base sm:text-lg font-black text-teal-300 mt-1 mb-1.5 leading-snug">Berijazah Resmi Negara Setara SMP dan SMA</h3>
                            <p class="text-xs text-teal-100 leading-relaxed">
                                Memadukan kurikulum nasional resmi Kemendikbudristek. Santri memiliki hak akademik penuh yang setara dengan sekolah negeri umum, sehingga masa depan pendidikan formal ananda tetap terjamin aman dan legal.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Pilar 3 -->
                <div class="rounded-3xl p-5 bg-gradient-to-br from-amber-900 to-amber-950 text-white shadow-xl relative overflow-hidden border border-amber-700/50">
                    <div class="absolute -right-4 -bottom-4 text-amber-800/30 text-8xl font-black pointer-events-none select-none">3</div>
                    <div class="flex items-start gap-3.5 relative z-10">
                        <div class="w-12 h-12 rounded-2xl bg-amber-400 text-amber-950 flex-shrink-0 flex items-center justify-center text-xl font-extrabold shadow-md">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-extrabold px-2.5 py-0.5 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30">Pilar 3</span>
                            <h3 class="text-base sm:text-lg font-black text-amber-300 mt-1 mb-1.5 leading-snug">Digital Marketing (Content Creator, AI Terapan, Solopreneur)</h3>
                            <p class="text-xs text-amber-100 leading-relaxed">
                                Membekali santri dengan keahlian abad 21: pembuatan konten dakwah multimedia, pemanfaatan AI cerdas untuk riset materi, serta mentalitas wirausaha (solopreneur) agar santri mandiri finansial di era digital.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 4. FASILITAS & SUASANA VILLA (USER PROMPT NO 4)             -->
        <!-- ============================================================ -->
        <section class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-400 flex items-center justify-center font-bold text-sm shadow">03</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Fasilitas & Suasana Nyaman</h2>
                    <p class="text-xs text-gray-500">Lingkungan sejuk & asri untuk konsentrasi menghafal</p>
                </div>
            </div>

            <!-- Kartu Sorotan Suasana Villa -->
            <div class="glass-card rounded-3xl p-5 shadow-lg border border-emerald-100 mb-4">
                <div class="flex items-center gap-3 mb-3 text-emerald-800">
                    <i class="fas fa-mountain-sun text-2xl text-amber-500"></i>
                    <h3 class="font-extrabold text-sm text-emerald-950">Kenapa Konsep Ala Villa Sangat Efektif?</h3>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed mb-4">
                    Menghafal Al-Qur'an memerlukan ketenangan batin dan kejernihan pikiran. Berbeda dari pesantren padat perkotaan, Villa Quran berlokasi di lingkungan sejuk perbukitan bebas polusi dan kebisingan, sehingga akselerasi hafalan santri meningkat hingga <strong>200% lebih cepat dan betah</strong>.
                </p>

                <!-- Grid Fasilitas -->
                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="bg-emerald-50/70 p-3 rounded-2xl border border-emerald-100/80 flex items-center gap-2.5">
                        <i class="fas fa-bed text-emerald-700 text-base"></i>
                        <span class="font-semibold text-emerald-950">Asrama Nyaman & Bersih</span>
                    </div>
                    <div class="bg-emerald-50/70 p-3 rounded-2xl border border-emerald-100/80 flex items-center gap-2.5">
                        <i class="fas fa-mosque text-emerald-700 text-base"></i>
                        <span class="font-semibold text-emerald-950">Masjid Jami' 24 Jam</span>
                    </div>
                    <div class="bg-emerald-50/70 p-3 rounded-2xl border border-emerald-100/80 flex items-center gap-2.5">
                        <i class="fas fa-utensils text-emerald-700 text-base"></i>
                        <span class="font-semibold text-emerald-950">Makan Bergizi 3x Sehari</span>
                    </div>
                    <div class="bg-emerald-50/70 p-3 rounded-2xl border border-emerald-100/80 flex items-center gap-2.5">
                        <i class="fas fa-microchip text-emerald-700 text-base"></i>
                        <span class="font-semibold text-emerald-950">Studio AI & Multimedia</span>
                    </div>
                    <div class="bg-emerald-50/70 p-3 rounded-2xl border border-emerald-100/80 flex items-center gap-2.5">
                        <i class="fas fa-tshirt text-emerald-700 text-base"></i>
                        <span class="font-semibold text-emerald-950">Layanan Laundry Santri</span>
                    </div>
                    <div class="bg-emerald-50/70 p-3 rounded-2xl border border-emerald-100/80 flex items-center gap-2.5">
                        <i class="fas fa-futbol text-emerald-700 text-base"></i>
                        <span class="font-semibold text-emerald-950">Olahraga & Outbound</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 5. DEWAN PENGASUH & ASATIDZ (USER PROMPT NO 5)               -->
        <!-- ============================================================ -->
        <section class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-400 flex items-center justify-center font-bold text-sm shadow">04</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Dewan Pengasuh & Asatidz</h2>
                    <p class="text-xs text-gray-500">Membimbing santri dengan ketulusan & keilmuan</p>
                </div>
            </div>

            <!-- Card Profil Pengasuh Manusia -->
            <div class="glass-card rounded-3xl p-5 shadow-lg border border-emerald-100 mb-3.5">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-emerald-100 border-2 border-emerald-600 flex items-center justify-center text-emerald-800 text-2xl flex-shrink-0 shadow">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Talaqqi Bersanad</span>
                        <h3 class="font-black text-base text-emerald-950 mt-1">Tim Asatidz Hafizh Mukim</h3>
                        <p class="text-xs text-gray-600 leading-relaxed mt-1">
                            Didampingi oleh para asatidz mukim bersanad Al-Qur'an 30 Juz yang menginap bersama santri 24 jam untuk membina kedisiplinan adab, qiyamul lail, dan kesehatan mental santri.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Inovasi: Kolaborasi Dewan Tutor AI -->
            <div class="rounded-3xl p-5 bg-gradient-to-br from-emerald-950 via-[#064e45] to-teal-950 text-white shadow-xl border border-amber-400/40 relative overflow-hidden">
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-400 text-emerald-950 text-[10px] font-extrabold uppercase shadow">Inovasi Pesantren</span>
                    <span class="text-xs text-amber-300 font-bold"><i class="fas fa-robot mr-1"></i> Dewan Tutor AI Diknas</span>
                </div>
                <h3 class="text-sm sm:text-base font-extrabold text-white mb-1.5">Sinergi Asatidz Manusia & AI Interaktif</h3>
                <p class="text-xs text-emerald-100 leading-relaxed">
                    Untuk mata pelajaran umum (Diknas), santri didampingi Dewan Tutor AI berkepribadian ilmuwan Muslim ternama (seperti <em>Ustadz Ibnu Khaldun</em> untuk Sosiologi SMA). Santri dapat berdiskusi verbal dan tanya-jawab materi pelajaran 24/7 kapan pun dibutuhkan.
                </p>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 6. TABEL INVESTASI PENDIDIKAN (BIAYA) (USER PROMPT NO 6)    -->
        <!-- ============================================================ -->
        <section class="mb-8" id="biaya-section">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-amber-600 text-white flex items-center justify-center font-bold text-sm shadow">05</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Investasi Pendidikan (Biaya)</h2>
                    <p class="text-xs text-gray-500">Transparan, terjangkau, dan sepadan dengan mutu</p>
                </div>
            </div>

            <div class="glass-card rounded-3xl p-5 shadow-lg border border-emerald-100">
                <div class="divide-y divide-gray-100 text-xs sm:text-sm">
                    <!-- Komponen 1: Pendaftaran -->
                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-900 block">1. Biaya Pendaftaran & Observasi</span>
                            <span class="text-[11px] text-gray-500">Pemeriksaan kesehatan, tes minat & bakat</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-sm">Rp 350.000</span>
                    </div>

                    <!-- Komponen 2: Uang Pangkal -->
                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-900 block">2. Uang Pangkal / Sarana Masuk</span>
                            <span class="text-[11px] text-gray-500">Lemari, ranjang kasur, seragam, modul</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-sm">Rp 12.500.000</span>
                    </div>

                    <!-- Komponen 3: Biaya Tahunan -->
                    <div class="py-3 flex justify-between items-center">
                        <div>
                            <span class="font-bold text-gray-900 block">3. Biaya Pengembangan Tahunan</span>
                            <span class="text-[11px] text-gray-500">Karantina tahfidz, ekstrakurikuler & rihlah</span>
                        </div>
                        <span class="font-extrabold text-emerald-700 text-sm">Rp 2.500.000</span>
                    </div>

                    <!-- Komponen 4: SPP Bulanan -->
                    <div class="py-3.5 bg-emerald-50/80 -mx-5 px-5 rounded-2xl border border-emerald-200/60 flex justify-between items-center mt-2">
                        <div>
                            <span class="font-extrabold text-emerald-950 block text-sm">4. SPP All-in Per Bulan</span>
                            <span class="text-[11px] text-emerald-800">Makan 3x/hari bergizi, asrama, laundry & bimbingan</span>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-emerald-800 text-base">Rp 1.650.000</span>
                            <span class="block text-[10px] text-emerald-600">/ bulan</span>
                        </div>
                    </div>
                </div>

                <!-- Info Keringanan & Diskon -->
                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-amber-800 bg-amber-50/70 p-3 rounded-xl border border-amber-200">
                    <span class="flex items-center gap-1.5"><i class="fas fa-gift text-amber-600"></i> <strong>Diskon Gelombang 1</strong> potongan Rp 2.000.000 Uang Pangkal</span>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 7. FORM PENDAFTARAN & AUTO-NOTIF WA (USER PROMPT NO 7)      -->
        <!-- ============================================================ -->
        <section class="mb-10" id="form-daftar-section">
            <div class="rounded-3xl p-6 sm:p-7 bg-gradient-to-br from-[#064e45] via-[#043d36] to-[#022823] text-white shadow-2xl border border-amber-500/40 relative overflow-hidden">
                
                <!-- Background Accent -->
                <div class="absolute -top-12 -right-12 w-44 h-44 bg-amber-400/10 rounded-full blur-2xl"></div>

                <div class="text-center mb-6">
                    <span class="inline-block px-3 py-1 rounded-full bg-amber-400/20 text-amber-300 border border-amber-400/30 text-[11px] font-extrabold uppercase tracking-wider mb-2">
                        Formulir Silaturahmi & Reservasi
                    </span>
                    <h2 class="text-xl sm:text-2xl font-black text-white">Amankan Kuota Santri Ananda</h2>
                    <p class="text-xs text-emerald-100/90 mt-1 max-w-sm mx-auto">
                        Kuota santri dibatasi maksimal 20 santri per angkatan untuk menjaga kualitas talaqqi intensif.
                    </p>
                </div>

                <!-- Formulir Pendaftaran Cepat -->
                <form id="form-reservasi" onsubmit="submitReservasi(event)" class="space-y-4 text-left">
                    <input type="hidden" name="kode_ref" id="input-ref" value="<?= $kode_ref ?>">

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Nama Ayah / Ibu (Wali) <span class="text-red-400">*</span></label>
                        <input type="text" id="reg-nama-wali" name="nama_wali" required placeholder="Contoh: Bpk. Hendy Pratama" value="<?= ($nama_tamu !== 'Bapak / Ibu Calon Wali Santri & Keluarga') ? $nama_tamu : '' ?>" class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Nomor WhatsApp Aktif <span class="text-red-400">*</span></label>
                        <input type="tel" id="reg-wa" name="whatsapp" required placeholder="Contoh: 081234567890" class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 text-sm">
                        <span class="text-[10px] text-gray-300 mt-0.5 block"><i class="fab fa-whatsapp text-emerald-400 mr-1"></i> Notifikasi otomatis & E-Brosur resmi dikirim ke nomor ini</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Nama Calon Santri (Ananda) <span class="text-red-400">*</span></label>
                        <input type="text" id="reg-nama-santri" name="nama_santri" required placeholder="Contoh: Muhammad Al-Fatih" class="w-full px-4 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Pilihan Jenjang <span class="text-red-400">*</span></label>
                            <select id="reg-jenjang" name="jenjang" required class="w-full px-3 py-2.5 rounded-xl bg-emerald-950 border border-white/20 text-white text-xs sm:text-sm focus:outline-none focus:border-amber-400">
                                <option value="SMP Tahfidz">SMP (Setara)</option>
                                <option value="SMA Tahfidz & Solopreneur">SMA (Setara)</option>
                                <option value="Takhassus 30 Juz">Takhassus 30 Juz</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-amber-200 mb-1">Kota Asal / Domisili <span class="text-red-400">*</span></label>
                            <input type="text" id="reg-kota" name="kota" required placeholder="Contoh: Surabaya" class="w-full px-3 py-2.5 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-amber-200 mb-1">Catatan / Rencana Silaturahmi (Opsional)</label>
                        <textarea id="reg-catatan" name="catatan" rows="2" placeholder="Contoh: Ingin menjadwalkan kunjungan survei pesantren hari Ahad ini..." class="w-full px-4 py-2 rounded-xl bg-white/10 border border-white/20 text-white placeholder-gray-400 focus:outline-none focus:border-amber-400 text-xs"></textarea>
                    </div>

                    <button type="submit" id="btn-submit-reservasi" class="w-full bg-gradient-to-r from-amber-400 via-amber-500 to-amber-600 hover:from-amber-300 hover:to-amber-500 text-emerald-950 font-black py-3.5 px-6 rounded-2xl shadow-xl transition-all transform active:scale-95 flex items-center justify-center gap-2 text-sm sm:text-base mt-2 gold-glow">
                        <i class="fas fa-paper-plane"></i>
                        <span>Kirim Reservasi & Dapatkan E-Brosur</span>
                    </button>
                    
                    <p class="text-[10px] text-gray-300 text-center mt-2 flex items-center justify-center gap-1">
                        <i class="fas fa-lock text-amber-400"></i> Data terlindungi & otomatis tersinkron ke WA Panitia SPMB
                    </p>
                </form>

                <!-- Pop-up Sukses Setelah Submit -->
                <div id="sukses-reservasi-modal" class="hidden bg-emerald-900 border border-amber-400/50 rounded-2xl p-5 text-center mt-4 animate-fade-in">
                    <div class="w-14 h-14 rounded-full bg-amber-400 text-emerald-950 flex items-center justify-center mx-auto mb-3 text-2xl shadow-lg">
                        <i class="fas fa-check"></i>
                    </div>
                    <h3 class="text-base font-extrabold text-amber-300 mb-1">Alhamdulillah, Data Diterima!</h3>
                    <p class="text-xs text-emerald-100 leading-relaxed mb-4" id="sukses-modal-pesan">
                        Terima kasih Bapak/Ibu. Notifikasi konfirmasi dan link e-brosur resmi telah kami kirimkan ke nomor WhatsApp Anda.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-2.5">
                        <a id="btn-wa-direct" href="#" target="_blank" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 shadow">
                            <i class="fab fa-whatsapp text-sm"></i> Buka WhatsApp Sekarang
                        </a>
                        <a href="upload/logo-villa-quran.png" download class="flex-1 bg-white/10 hover:bg-white/20 text-amber-300 py-2.5 px-4 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 border border-white/20">
                            <i class="fas fa-file-download"></i> Unduh E-Brosur
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 8. TESTIMONI WALISANTRI (USER PROMPT NO 8)                  -->
        <!-- ============================================================ -->
        <section class="mb-8">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-400 flex items-center justify-center font-bold text-sm shadow">06</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Testimoni Walisantri</h2>
                    <p class="text-xs text-gray-500">Kisah nyata transformasi putra-putri di Villa Quran</p>
                </div>
            </div>

            <div class="space-y-3.5">
                <!-- Testimoni 1 -->
                <div class="glass-card rounded-2xl p-4.5 p-4 border border-emerald-100 shadow-sm relative">
                    <div class="flex items-center gap-3 mb-2.5">
                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold text-xs">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-xs sm:text-sm text-emerald-950">Bpk. Hendra Gunawan</h4>
                            <p class="text-[10px] text-gray-500">Wali Santri SMP — Asal Surabaya</p>
                        </div>
                        <div class="ml-auto flex text-amber-400 text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-700 italic leading-relaxed">
                        "Alhamdulillah baru 8 bulan di Villa Quran, anak saya sudah menyelesaikan 6 juz mutqin dengan tajwid yang sangat rapi. Yang paling membuat saya terharu, saat pulang liburan dia selalu bangun qiyamul lail sendiri tanpa perlu dibangunkan."
                    </p>
                </div>

                <!-- Testimoni 2 -->
                <div class="glass-card rounded-2xl p-4 border border-emerald-100 shadow-sm relative">
                    <div class="flex items-center gap-3 mb-2.5">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold text-xs">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div>
                            <h4 class="font-extrabold text-xs sm:text-sm text-emerald-950">Ibu dr. Nurul Aini</h4>
                            <p class="text-[10px] text-gray-500">Wali Santri SMA — Asal Jakarta</p>
                        </div>
                        <div class="ml-auto flex text-amber-400 text-xs">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-700 italic leading-relaxed">
                        "Konsepnya sangat solutif. Anak kami tidak hanya hafal Al-Qur'an dan mengantongi ijazah resmi negara, tapi juga diajari AI dan digital marketing. Karakternya mandiri dan siap menghadapi tantangan zaman modern."
                    </p>
                </div>
            </div>
        </section>

        <!-- ============================================================ -->
        <!-- 9. PETA LOKASI & ALAMAT (USER PROMPT NO 9)                  -->
        <!-- ============================================================ -->
        <section class="mb-12">
            <div class="flex items-center gap-2 mb-4">
                <span class="w-8 h-8 rounded-xl bg-emerald-800 text-amber-400 flex items-center justify-center font-bold text-sm shadow">07</span>
                <div>
                    <h2 class="text-lg sm:text-xl font-extrabold text-emerald-950">Lokasi & Kunjungan Silaturahmi</h2>
                    <p class="text-xs text-gray-500">Rute mudah menuju kampus Villa Quran Indonesia</p>
                </div>
            </div>

            <div class="glass-card rounded-3xl p-5 shadow-lg border border-emerald-100">
                <div class="flex items-start gap-3 mb-4">
                    <i class="fas fa-map-marker-alt text-amber-600 text-xl flex-shrink-0 mt-0.5"></i>
                    <div>
                        <h4 class="font-bold text-xs sm:text-sm text-emerald-950">Kampus Villa Quran Indonesia</h4>
                        <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                            Kawasan Asri Pegunungan, Suasana Sejuk & Nyaman ala Villa (Akses kendaraan roda 2 dan roda 4 mudah dijangkau).
                        </p>
                    </div>
                </div>

                <!-- Google Maps Frame Interaktif -->
                <div class="w-full h-44 rounded-2xl overflow-hidden border border-gray-200 mb-3 shadow-inner">
                    <iframe src="https://maps.google.com/maps?q=Villa+Quran+Indonesia&t=&z=14&ie=UTF8&iwloc=&output=embed" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>

                <a href="https://maps.google.com/maps?q=Villa+Quran+Indonesia" target="_blank" class="w-full bg-emerald-100 hover:bg-emerald-200 text-emerald-900 font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition">
                    <i class="fas fa-location-arrow text-emerald-700"></i> Buka Rute di Google Maps
                </a>
            </div>
        </section>

        <!-- FOOTER & UNDUH PDF -->
        <footer class="text-center text-xs text-gray-500 pt-6 border-t border-gray-200 space-y-3">
            <p>&copy; <?= date('Y') ?> <strong>Villa Quran Indonesia</strong>. All Rights Reserved.</p>
            <p class="text-[11px] text-gray-400">Mencetak Generasi Hafidz Mutqin Bersanad, Berijazah Resmi Negara & Berjiwa Solopreneur.</p>
        </footer>

    </div>

    <!-- ============================================================ -->
    <!-- STICKY MOBILE BOTTOM BAR (QUICK ACTIONS)                     -->
    <!-- ============================================================ -->
    <div class="fixed bottom-0 left-0 right-0 z-40 bg-white/95 backdrop-blur-md border-t border-emerald-100 py-2.5 px-4 shadow-2xl">
        <div class="max-w-xl mx-auto flex items-center justify-between gap-2 text-center">
            
            <!-- Tombol 1: Chat CS WA -->
            <a href="https://wa.me/6285189918115?text=Assalamu%27alaikum%20Panitia%20SPMB%20Villa%20Quran,%20saya%20<?= urlencode($nama_tamu) ?>%20ingin%20bertanya%20informasi%20pendaftaran" target="_blank" class="flex-1 py-2 px-1 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[11px] font-bold flex flex-col items-center justify-center transition">
                <i class="fab fa-whatsapp text-emerald-600 text-base mb-0.5"></i>
                <span>Tanya CS</span>
            </a>

            <!-- Tombol 2: Daftar Sekarang (Scroll ke Form) -->
            <button onclick="scrollToForm()" class="flex-[1.8] py-2.5 px-3 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 text-emerald-950 font-black text-xs sm:text-sm flex items-center justify-center gap-1.5 shadow-md active:scale-95 transition">
                <i class="fas fa-edit"></i>
                <span>Daftar Sekarang</span>
            </button>

            <!-- Tombol 3: Viral Bagikan Link Personal -->
            <button onclick="bukaModalShare()" class="flex-1 py-2 px-1 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-900 border border-emerald-200 text-[11px] font-bold flex flex-col items-center justify-center transition">
                <i class="fas fa-share-alt text-amber-600 text-base mb-0.5"></i>
                <span>Bagikan</span>
            </button>

        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL SHARE UNDANGAN PERSONAL (VIRAL MARKETING LOOP)         -->
    <!-- ============================================================ -->
    <div id="modal-share" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-sm w-full p-6 text-center shadow-2xl border border-emerald-100 relative">
            <button onclick="tutupModalShare()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                <i class="fas fa-times"></i>
            </button>
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="fas fa-envelope-open-text"></i>
            </div>
            <h3 class="text-base font-extrabold text-emerald-950 mb-1">Buat Undangan Personal</h3>
            <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                Tulis nama keluarga / kerabat Anda untuk membuat link brosur undangan khusus atas nama beliau.
            </p>

            <div class="text-left mb-4">
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Penerima Undangan:</label>
                <input type="text" id="share-nama-tamu" placeholder="Misal: Bpk. Surya / Keluarga dr. Ahmad" class="w-full px-4 py-2 border border-gray-300 rounded-xl text-xs sm:text-sm focus:ring-1 focus:ring-emerald-500 focus:outline-none">
            </div>

            <button onclick="kirimShareWhatsApp()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-xl text-xs sm:text-sm flex items-center justify-center gap-2 shadow-lg mb-2">
                <i class="fab fa-whatsapp text-lg"></i> Kirim Lewat WhatsApp
            </button>

            <button onclick="salinLinkPersonal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2">
                <i class="fas fa-copy"></i> Salin Link Undangan
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- JAVASCRIPT LOGIC                                             -->
    <!-- ============================================================ -->
    <script>
        const audio = document.getElementById('audio-backsound');
        const audioControl = document.getElementById('audio-control');
        const audioIcon = document.getElementById('audio-icon');
        const audioLabel = document.getElementById('audio-label');
        let isAudioPlaying = false;

        // Cek kode referral dari URL dan simpan ke localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const refFromUrl = urlParams.get('ref');
            if (refFromUrl) {
                localStorage.setItem('agen_ref', refFromUrl);
                const inputRef = document.getElementById('input-ref');
                if (inputRef) inputRef.value = refFromUrl;
            } else {
                const storedRef = localStorage.getItem('agen_ref');
                if (storedRef && document.getElementById('input-ref')) {
                    document.getElementById('input-ref').value = storedRef;
                }
            }
        });

        // 1. Membuka amplop undangan
        function bukaUndangan() {
            const cover = document.getElementById('envelope-cover');
            cover.classList.add('cover-hidden');

            // Mulai putar audio setelah interaksi user
            audio.play().then(() => {
                isAudioPlaying = true;
                audioControl.classList.remove('hidden');
                updateAudioUI();
            }).catch(err => {
                console.log('Autoplay dicegah browser:', err);
                audioControl.classList.remove('hidden');
            });
        }

        // 2. Kontrol On/Off Audio
        function toggleAudio() {
            if (isAudioPlaying) {
                audio.pause();
                isAudioPlaying = false;
            } else {
                audio.play();
                isAudioPlaying = true;
            }
            updateAudioUI();
        }

        function updateAudioUI() {
            if (isAudioPlaying) {
                audioIcon.classList.add('animate-spin-slow');
                audioIcon.innerHTML = '<i class="fas fa-music text-[10px]"></i>';
                audioLabel.innerText = 'Audio On';
            } else {
                audioIcon.classList.remove('animate-spin-slow');
                audioIcon.innerHTML = '<i class="fas fa-volume-mute text-[10px]"></i>';
                audioLabel.innerText = 'Audio Off';
            }
        }

        // 3. Scroll ke Form Pendaftaran
        function scrollToForm() {
            const el = document.getElementById('form-daftar-section');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth' });
                document.getElementById('reg-nama-wali').focus();
            }
        }

        // 4. Submit Formulir Pendaftaran Cepat (AJAX ke simpan-brosur.php)
        function submitReservasi(e) {
            e.preventDefault();
            const btn = document.getElementById('btn-submit-reservasi');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Mengirim data & menyiapkan WA...';
            btn.disabled = true;

            const form = document.getElementById('form-reservasi');
            const fd = new FormData(form);

            fetch('simpan-brosur.php', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;

                if (data.status === 'success') {
                    form.classList.add('hidden');
                    const modal = document.getElementById('sukses-reservasi-modal');
                    modal.classList.remove('hidden');
                    document.getElementById('sukses-modal-pesan').innerText = data.message;
                    
                    // Siapkan tombol direct WA
                    const noWaTarget = data.wa || '';
                    const directBtn = document.getElementById('btn-wa-direct');
                    directBtn.href = `https://wa.me/6285189918115?text=Assalamu%27alaikum%20Panitia%20SPMB,%20saya%20${encodeURIComponent(data.nama_wali)}%20baru%20saja%20mengisi%20reservasi%20untuk%20ananda%20${encodeURIComponent(data.nama_santri)}%20(${encodeURIComponent(data.jenjang)}).%20Mohon%20informasi%20selanjutnya.`;
                } else {
                    alert(data.message || 'Terjadi kendala saat mengirim. Silakan coba lagi.');
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Terjadi kesalahan koneksi. Silakan periksa jaringan internet Anda.');
                console.error(err);
            });
        }

        // 5. Modal Share Undangan Personal
        function bukaModalShare() {
            document.getElementById('modal-share').classList.remove('hidden');
        }

        function tutupModalShare() {
            document.getElementById('modal-share').classList.add('hidden');
        }

        function kirimShareWhatsApp() {
            const nama = document.getElementById('share-nama-tamu').value.trim() || 'Bapak/Ibu Calon Wali Santri';
            const refCode = localStorage.getItem('agen_ref') || 'organik';
            const currentUrlBase = window.location.origin + window.location.pathname;
            const inviteUrl = `${currentUrlBase}?to=${encodeURIComponent(nama)}&ref=${encodeURIComponent(refCode)}`;
            
            const waText = `🌸 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\n`
                         + `Kepada Yth. *${nama}* & Segenap Keluarga,\n\n`
                         + `Dengan rasa hormat dan mahabbah, kami membagikan *Undangan Silaturahmi & Brosur Digital Villa Quran Indonesia*:\n`
                         + `🌿 *Sekolah Tahfidz Berasrama Nyaman Ala Villa*\n`
                         + `• Tahfidz Mutqin 15–30 Juz Bersanad\n`
                         + `• Berijazah Resmi Negara Setara SMP & SMA\n`
                         + `• Digital Marketing, AI Terapan & Solopreneur\n\n`
                         + `Buka tautan undangan khusus untuk Bapak/Ibu di sini:\n`
                         + `${inviteUrl}\n\n`
                         + `_Jazakumullahu Khairan Katsiran._`;

            window.open(`https://wa.me/?text=${encodeURIComponent(waText)}`, '_blank');
            tutupModalShare();
        }

        function salinLinkPersonal() {
            const nama = document.getElementById('share-nama-tamu').value.trim() || 'Bapak/Ibu Calon Wali Santri';
            const refCode = localStorage.getItem('agen_ref') || 'organik';
            const currentUrlBase = window.location.origin + window.location.pathname;
            const inviteUrl = `${currentUrlBase}?to=${encodeURIComponent(nama)}&ref=${encodeURIComponent(refCode)}`;

            navigator.clipboard.writeText(inviteUrl).then(() => {
                alert('Link undangan personal berhasil disalin ke clipboard!');
                tutupModalShare();
            }).catch(err => {
                prompt('Salin link undangan ini:', inviteUrl);
            });
        }
    </script>
</body>
</html>
