<?php
// tes-kesiapan-anak.php
// Halaman Khusus Lead Magnet PSB: Tes Asesmen Kesiapan Anak Masuk Pondok Pesantren
// Villa Quran Baron Malang
require_once 'koneksi.php';

// Ambil info kontak dan pengaturan web
$q_set = $conn->query("SELECT * FROM pengaturan_web WHERE id = 1 LIMIT 1");
$web_config = ($q_set && $q_set->num_rows > 0) ? $q_set->fetch_assoc() : [];
$cs_phone = !empty($web_config['nomor_wa']) ? $web_config['nomor_wa'] : '6285189918115';
$nama_pesantren = !empty($web_config['nama_sekolah']) ? $web_config['nama_sekolah'] : 'Villa Quran Baron Malang';

// Handle Referral Agen
$kode_ref = isset($_GET['ref']) ? trim(htmlspecialchars($_GET['ref'])) : 'organik';
$nama_agen = '';
if (!empty($kode_ref) && $kode_ref !== 'organik') {
    $q_ag = $conn->query("SELECT nama FROM agen WHERE kode_ref = '$kode_ref' OR whatsapp = '$kode_ref' LIMIT 1");
    if ($q_ag && $q_ag->num_rows > 0) {
        $row_ag = $q_ag->fetch_assoc();
        $nama_agen = $row_ag['nama'];
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tes Kesiapan Anak Masuk Pondok Pesantren | <?= htmlspecialchars($nama_pesantren) ?></title>
    <meta name="description"
        content="Ketahui sejauh mana kesiapan mandiri, emosional, spiritual, dan sosial ananda masuk pondok pesantren dalam 3 menit. Dapatkan skor akurat & rekomendasi pengasuhan dari ahli!">

    <!-- Open Graph / Meta Sosmed -->
    <meta property="og:title"
        content="Tes Kesiapan Anak Masuk Pondok Pesantren - <?= htmlspecialchars($nama_pesantren) ?>">
    <meta property="og:description"
        content="Asesmen psikologis & kemandirian 15 indikator untuk mengetahui kesiapan ananda dan panduan orang tua. Gratis & instan!">
    <meta property="og:image" content="upload/logo-villa-quran.png">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#022d27',
                            950: '#011c18'
                        },
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    },
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        marlin: ['"Marlin Condensed"', '"Barlow Condensed"', 'sans-serif']
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        @font-face {
            font-family: 'Marlin Condensed';
            src: local('Marlin Condensed Bold'), local('Marlin Condensed'), url('fonts/MarlinCondensed-Bold.ttf') format('truetype');
            font-weight: bold;
            font-display: swap;
        }

        .font-marlin {
            font-family: 'Marlin Condensed', 'Barlow Condensed', sans-serif;
        }

        /* Custom Radio Card */
        .option-card {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .option-card:hover {
            border-color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -5px rgba(5, 150, 105, 0.12);
        }

        .option-card.selected {
            border-color: #059669;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            box-shadow: 0 0 0 2px #059669, 0 8px 16px -4px rgba(5, 150, 105, 0.2);
        }

        .animate-bounce-slow {
            animation: bounce 2.5s infinite;
        }

        @keyframes pulse-subtle {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.94;
                transform: scale(1.02);
            }
        }

        .pulse-subtle {
            animation: pulse-subtle 3s ease-in-out infinite;
        }

        /* Print Styles */
        @media print {

            header,
            footer,
            .no-print,
            #cta-action-box {
                display: none !important;
            }

            body {
                background: white !important;
                color: black !important;
            }

            .print-full {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>

<body
    class="bg-gradient-to-b from-slate-50 via-emerald-50/30 to-slate-100 font-sans text-gray-800 min-h-screen flex flex-col selection:bg-emerald-600 selection:text-white">

    <!-- HEADER / TOP NAV -->
    <header class="bg-white/95 backdrop-blur-md sticky top-0 z-40 border-b border-emerald-100 shadow-sm">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 sm:h-20 flex items-center justify-between">
            <a href="index.html" class="flex items-center space-x-2.5 sm:space-x-3 group">
                <img src="upload/logo-villa-quran.png" alt="Logo"
                    class="h-10 sm:h-12 w-auto object-contain transition group-hover:scale-105">
                <div>
                    <span
                        class="block font-marlin font-bold text-xl sm:text-2xl text-emerald-950 tracking-tight leading-none uppercase">
                        <?= htmlspecialchars($nama_pesantren) ?>
                    </span>
                    <span class="block text-[10px] sm:text-xs font-semibold text-emerald-700 tracking-wider uppercase">
                        Asesmen & Parenting Centre
                    </span>
                </div>
            </a>
            <div class="flex items-center space-x-3">
                <a href="index.html"
                    class="text-xs sm:text-sm font-semibold text-gray-600 hover:text-emerald-700 transition hidden sm:inline-flex items-center">
                    <i class="fas fa-home mr-1.5 text-gray-400"></i> Beranda
                </a>
                <a href="daftar-spmb.html"
                    class="inline-flex items-center px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-xl text-xs sm:text-sm font-bold bg-emerald-700 text-white hover:bg-emerald-800 transition shadow-sm shadow-emerald-700/20">
                    <i class="fas fa-user-plus mr-1.5"></i> SPMB Online
                </a>
            </div>
        </div>
    </header>

    <!-- MAIN CONTAINER -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-10">

        <!-- STEP 1: HERO & REGISTRATION FORM (LEAD CAPTURE) -->
        <section id="section-intro" class="space-y-6 sm:space-y-8">
            <!-- Hero Card -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-900 via-teal-900 to-emerald-950 text-white p-6 sm:p-10 shadow-2xl border border-emerald-800">
                <div
                    class="absolute -right-16 -bottom-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none">
                </div>
                <div
                    class="absolute -left-16 -top-16 w-64 h-64 bg-amber-400/10 rounded-full blur-3xl pointer-events-none">
                </div>

                <div class="relative z-10 text-center max-w-2xl mx-auto space-y-4">
                    <h1
                        class="font-marlin text-3xl sm:text-4xl md:text-5xl font-bold tracking-tight text-white leading-tight pt-2">
                        Apakah Ananda Sudah Benar-Benar Siap Masuk Pondok?
                    </h1>

                    <p class="text-sm sm:text-base text-emerald-100 font-normal leading-relaxed">
                        Cari tahu kesiapan <span class="text-amber-300 font-semibold">Kemandirian, Emosional, Spiritual
                            & Sosial</span> ananda dalam 3 menit. Dapatkan skor akurat, peta diagnosis psikologis, serta
                        E-Book Panduan Eksklusif!
                    </p>

                    <!-- Feature Badges -->
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 pt-2 text-center text-xs sm:text-sm">
                        <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur border border-white/10">
                            <i class="fas fa-clipboard-check text-amber-400 text-base sm:text-lg mb-1 block"></i>
                            <span class="font-bold text-white block">15 Soal Praktis</span>
                            <span class="text-[10px] sm:text-xs text-emerald-200">5 Aspek Kesiapan</span>
                        </div>
                        <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur border border-white/10">
                            <i class="fas fa-chart-pie text-emerald-300 text-base sm:text-lg mb-1 block"></i>
                            <span class="font-bold text-white block">Hasil Instan</span>
                            <span class="text-[10px] sm:text-xs text-emerald-200">Skor & Diagnosis</span>
                        </div>
                        <div class="p-2.5 sm:p-3 rounded-2xl bg-white/10 backdrop-blur border border-white/10">
                            <i class="fas fa-gift text-amber-400 text-base sm:text-lg mb-1 block"></i>
                            <span class="font-bold text-white block">Bonus E-Book</span>
                            <span class="text-[10px] sm:text-xs text-emerald-200">Free PDF Hafidz</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Data Awal Calon Wali Santri -->
            <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-xl border border-gray-100 max-w-2xl mx-auto">
                <div class="border-b border-gray-100 pb-4 mb-6 text-center">
                    <p class="text-sm sm:text-base text-black font-semibold">
                        Masukkan Nama & WhatsApp Anda untuk memulai tes dan menerima resume diagnosis hasil asesmen.
                    </p>
                </div>

                <form id="form-data-awal" class="space-y-5">
                    <input type="hidden" id="kode_ref" name="kode_ref" value="<?= htmlspecialchars($kode_ref) ?>">

                    <?php if (!empty($nama_agen)): ?>
                        <div
                            class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 flex items-center">
                            <i class="fas fa-user-tag text-emerald-600 mr-2 text-base"></i>
                            <span>Direkomendasikan oleh Konsultan Pendidikan:
                                <strong><?= htmlspecialchars($nama_agen) ?></strong></span>
                        </div>
                    <?php endif; ?>

                    <!-- Nama Wali -->
                    <div>
                        <label class="block text-xs sm:text-sm font-bold text-gray-800 mb-1.5">
                            Nama Lengkap / Panggilan Bapak / Ibu <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-500">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input type="text" id="input_nama_wali" required
                                placeholder="Contoh: Bpk. Muhammad Ilham / Ibu Rahma"
                                style="border: 2px solid #94a3b8 !important;"
                                class="w-full pl-10 pr-4 py-3.5 bg-slate-50/50 hover:bg-white focus:bg-white rounded-xl text-gray-900 font-semibold text-sm transition shadow-sm outline-none focus:ring-4 focus:ring-emerald-500/20">
                        </div>
                    </div>

                    <!-- WhatsApp -->
                    <div>
                        <label class="block text-xs sm:text-sm font-bold text-gray-800 mb-1.5">
                            Nomor WhatsApp Aktif <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-500 font-semibold text-xs">
                                <i class="fab fa-whatsapp text-emerald-600 text-base"></i>
                            </span>
                            <input type="tel" id="input_whatsapp" required placeholder="Contoh: 081234567890"
                                style="border: 2px solid #94a3b8 !important;"
                                class="w-full pl-10 pr-4 py-3.5 bg-slate-50/50 hover:bg-white focus:bg-white rounded-xl text-gray-900 font-semibold text-sm transition shadow-sm outline-none focus:ring-4 focus:ring-emerald-500/20">
                        </div>
                        <span class="text-[11px] text-gray-500 mt-1 block">Untuk menerima salinan resume analisis
                            kesiapan dan E-Book gratis.</span>
                    </div>

                    <!-- Tombol Mulai Tes -->
                    <div class="pt-4 text-center">
                        <button type="submit" id="btn-start-test"
                            class="w-full inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-700 hover:to-teal-800 text-white font-bold text-base sm:text-lg rounded-2xl shadow-lg shadow-emerald-700/30 transition transform hover:-translate-y-0.5">
                            <span>Mulai Tes Kesiapan Sekarang (15 Soal)</span>
                            <i class="fas fa-arrow-right ml-3 text-amber-300"></i>
                        </button>
                        <p class="text-xs text-gray-400 mt-2.5 flex items-center justify-center">
                            <i class="fas fa-lock mr-1.5 text-gray-400"></i> Data Anda 100% aman & langsung terhubung ke
                            sistem.
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <!-- STEP 2: 15 PERTANYAAN ASESMEN (INTERACTIVE QUESTIONNAIRE) -->
        <section id="section-quiz" class="hidden space-y-6">
            <!-- Progress Bar Sticky Header -->
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-md border border-gray-100 sticky top-20 z-30">
                <div class="flex items-center justify-between text-xs sm:text-sm font-bold mb-2">
                    <span class="text-emerald-800 flex items-center">
                        <i class="fas fa-tasks mr-1.5 text-emerald-600"></i>
                        <span id="label-santri-target">Evaluasi Ananda</span>
                    </span>
                    <span id="quiz-progress-text" class="text-gray-500">Soal 1 dari 15</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div id="quiz-progress-bar"
                        class="bg-gradient-to-r from-emerald-500 to-teal-600 h-full rounded-full transition-all duration-300"
                        style="width: 6.66%;"></div>
                </div>
            </div>

            <!-- Quiz Questions Container -->
            <div id="quiz-questions-wrapper" class="space-y-6">
                <!-- Javascript will render the active question here -->
            </div>

            <!-- Navigation Buttons -->
            <div class="flex items-center justify-between pt-2">
                <button type="button" id="btn-prev-q"
                    class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-100 text-sm font-bold transition disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fas fa-chevron-left mr-1.5"></i> Sebelumnya
                </button>
                <button type="button" id="btn-next-q"
                    class="px-6 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold transition shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                    Selanjutnya <i class="fas fa-chevron-right ml-1.5"></i>
                </button>
            </div>
        </section>

        <!-- STEP 3: HASIL ASESMEN & DIAGNOSIS LENGKAP (RESULT DASHBOARD) -->
        <section id="section-result" class="hidden space-y-8">
            <!-- Summary Banner -->
            <div class="bg-white rounded-3xl overflow-hidden shadow-2xl border border-emerald-100">
                <!-- Header Banner -->
                <div
                    class="bg-gradient-to-r from-emerald-900 via-teal-900 to-emerald-950 text-white p-6 sm:p-10 text-center relative">
                    <div
                        class="inline-flex items-center space-x-2 px-3.5 py-1 rounded-full bg-emerald-800/90 text-amber-300 text-xs font-bold mb-3 border border-emerald-600/40">
                        <i class="fas fa-award text-amber-400"></i>
                        <span>LAPORAN RESMI DIAGNOSIS KESIAPAN SANTRI</span>
                    </div>
                    <h2 class="font-marlin text-2xl sm:text-4xl font-bold tracking-tight text-white mb-1">
                        Hasil Evaluasi Kesiapan <span id="res-nama-santri" class="text-amber-300">-</span>
                    </h2>
                    <p class="text-xs sm:text-sm text-emerald-100">
                        Diproses secara otomatis berdasarkan 5 pilar utama adaptasi pesantren Villa Quran Baron Malang.
                    </p>

                    <!-- Score Radial / Circle Display -->
                    <div class="mt-6 flex flex-col items-center justify-center">
                        <div
                            class="relative w-36 h-36 sm:w-44 sm:h-44 rounded-full bg-white/10 backdrop-blur-md border-4 border-amber-400/80 flex flex-col items-center justify-center shadow-xl">
                            <span id="res-score-number"
                                class="text-4xl sm:text-5xl font-black text-white font-marlin tracking-tight">0%</span>
                            <span
                                class="text-[10px] sm:text-xs text-amber-300 font-bold uppercase tracking-wider mt-0.5">Indeks
                                Kesiapan</span>
                        </div>
                        <div class="mt-4">
                            <span id="res-kategori-badge"
                                class="inline-block px-4 py-1.5 rounded-full text-xs sm:text-sm font-extrabold uppercase tracking-wide bg-amber-400 text-emerald-950 shadow-md">
                                Menghitung Kategori...
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Detail Diagnosis Body -->
                <div class="p-6 sm:p-10 space-y-8 bg-white">
                    <!-- Narasi Analisa -->
                    <div class="p-5 sm:p-6 rounded-2xl bg-emerald-50/80 border border-emerald-200">
                        <h3 class="text-base sm:text-lg font-bold text-emerald-950 flex items-center mb-2">
                            <i class="fas fa-comment-medical text-emerald-700 mr-2 text-xl"></i>
                            Kesimpulan & Analisa Ahli Parenting:
                        </h3>
                        <p id="res-narasi" class="text-sm text-gray-700 leading-relaxed">
                            Sedang memproses narasi hasil...
                        </p>
                    </div>

                    <!-- 5 Pilar Breakdown Progress -->
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-layer-group text-emerald-700 mr-2"></i>
                            Rincian Skor Per Dimensi Kesiapan:
                        </h3>
                        <div id="res-breakdown-container" class="space-y-4">
                            <!-- Injected by JS -->
                        </div>
                    </div>

                    <!-- Kekuatan & Aspek Penguatan (2 Kolom) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Super Power -->
                        <div class="p-5 rounded-2xl bg-teal-50 border border-teal-200">
                            <h4 class="text-sm font-bold text-teal-900 flex items-center mb-2.5">
                                <i class="fas fa-star text-amber-500 mr-2 text-base"></i>
                                Potensi & Kekuatan Utama Ananda:
                            </h4>
                            <p id="res-strength" class="text-xs sm:text-sm text-teal-950 leading-relaxed">
                                -
                            </p>
                        </div>

                        <!-- Growth Area -->
                        <div class="p-5 rounded-2xl bg-amber-50 border border-amber-200">
                            <h4 class="text-sm font-bold text-amber-900 flex items-center mb-2.5">
                                <i class="fas fa-seedling text-amber-600 mr-2 text-base"></i>
                                Aspek Perlu Penguatan (Growth Area):
                            </h4>
                            <p id="res-growth" class="text-xs sm:text-sm text-amber-950 leading-relaxed">
                                -
                            </p>
                        </div>
                    </div>

                    <!-- Action Plan 30 Hari -->
                    <div class="p-5 sm:p-6 rounded-2xl bg-gray-50 border border-gray-200">
                        <h4 class="text-base font-bold text-gray-900 flex items-center mb-3">
                            <i class="fas fa-calendar-check text-emerald-700 mr-2"></i>
                            Langkah Tindakan (Action Plan) Pra-Pondok di Rumah:
                        </h4>
                        <ul id="res-action-plan"
                            class="space-y-2 text-xs sm:text-sm text-gray-700 list-disc list-inside">
                            <li>Kurangi durasi screen time / gadget harian secara bertahap 30-60 menit setiap pekan.
                            </li>
                            <li>Latih ananda mencuci pakaian dalam, merapikan tempat tidur, dan menyiapkan perlengkapan
                                sholat mandiri.</li>
                            <li>Tingkatkan tilawah bersama Ayah & Bunda 1 lembar ba'da Maghrib/Subuh untuk menumbuhkan
                                rasa cinta Al-Quran.</li>
                            <li>Perkuat komunikasi positif: ceritakan keindahan asrama villa, sejuknya hawa pegunungan,
                                dan serunya teman baru.</li>
                        </ul>
                    </div>

                    <!-- ACTION CTA BUTTONS -->
                    <div id="cta-action-box" class="pt-4 space-y-4 no-print">
                        <div
                            class="p-6 rounded-3xl bg-gradient-to-br from-emerald-800 to-teal-900 text-white text-center space-y-4 shadow-xl">
                            <h3 class="font-marlin text-2xl sm:text-3xl font-bold text-amber-300">
                                Mau Konsultasi Lebih Dalam atau Amankan Kuota SPMB?
                            </h3>
                            <p class="text-xs sm:text-sm text-emerald-100 max-w-xl mx-auto">
                                Tim Konselor Pendidikan & Pengasuh Asrama Villa Quran Baron Malang siap berdiskusi
                                mengenai pemetaan karakter dan program bimbingan ananda.
                            </p>

                            <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
                                <!-- WA CTA -->
                                <a id="btn-wa-consult" href="#" target="_blank"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-emerald-500 hover:bg-emerald-400 text-emerald-950 font-bold text-sm sm:text-base rounded-2xl shadow-lg transition transform hover:-translate-y-0.5">
                                    <i class="fab fa-whatsapp text-lg mr-2"></i>
                                    Konsultasi Hasil Tes via WA
                                </a>

                                <!-- E-Book Download -->
                                <a href="RAHASIA%20MENYIAPKAN%20ANAK%20REMAJA%20MENJADI%20HAFIDZ%20QURAN.pdf" download
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-amber-400 hover:bg-amber-300 text-gray-900 font-bold text-sm sm:text-base rounded-2xl shadow-lg transition transform hover:-translate-y-0.5">
                                    <i class="fas fa-file-pdf mr-2 text-red-600"></i>
                                    Download E-Book Gratis (PDF)
                                </a>

                                <!-- SPMB Link -->
                                <a href="daftar-spmb.html"
                                    class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-3.5 bg-white/20 hover:bg-white/30 text-white font-bold text-sm sm:text-base rounded-2xl border border-white/30 transition">
                                    <i class="fas fa-edit mr-2"></i>
                                    Daftar SPMB Online
                                </a>
                            </div>
                        </div>

                        <!-- Print / Share CTA -->
                        <div class="flex items-center justify-center space-x-4 pt-2">
                            <button onclick="window.print()"
                                class="text-xs text-gray-600 hover:text-emerald-700 font-bold inline-flex items-center transition">
                                <i class="fas fa-print mr-1.5"></i> Cetak / Simpan PDF
                            </button>
                            <span class="text-gray-300">•</span>
                            <button onclick="restartQuiz()"
                                class="text-xs text-gray-600 hover:text-emerald-700 font-bold inline-flex items-center transition">
                                <i class="fas fa-redo mr-1.5"></i> Ulangi Tes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-slate-400 py-8 border-t border-slate-800 text-xs text-center mt-12 no-print">
        <div class="max-w-4xl mx-auto px-4 space-y-2">
            <p class="font-bold text-white text-sm">Villa Quran Baron Malang</p>
            <p>Pondok Pesantren Berbasis Villa Alam • Tahfidz Al-Quran Mutqin • Kurikulum Akademik Unggul & Life-Skills
                Solopreneur</p>
            <p class="text-slate-500 pt-2">&copy; <?= date('Y') ?> Villa Quran Baron Malang. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- LOGIC SCRIPT -->
    <script>
        // DATA INSTRUMEN TES: 15 PERTANYAAN DALAM 5 PILAR
        const quizData = [
            // PILAR 1: KEMANDIRIAN & KESEHARIAN
            {
                pilarId: 1,
                pilarName: "Kemandirian Fisik & Keseharian",
                icon: "fa-tshirt",
                question: "Bagaimana kebiasaan Ananda dalam merapikan tempat tidur, baju, dan barang pribadinya sehari-hari?",
                options: [
                    { text: "Sudah mandiri dan terbiasa merapikan sendiri tanpa perlu disuruh.", score: 4 },
                    { text: "Bisa merapikan sendiri, namun sesekali masih perlu diingatkan.", score: 3 },
                    { text: "Mau merapikan jika disuruh berulang kali atau didampingi.", score: 2 },
                    { text: "Hampir selalu dirapikan oleh orang tua / asisten rumah tangga.", score: 1 }
                ]
            },
            {
                pilarId: 1,
                pilarName: "Kemandirian Fisik & Keseharian",
                icon: "fa-shower",
                question: "Sejauh mana kemampuan Ananda merawat kebersihan diri (mandi, cuci pakaian dalam, memotong kuku, bangun pagi)?",
                options: [
                    { text: "Sangat mandiri, bangun pagi sendiri dan menjaga kebersihan dengan baik.", score: 4 },
                    { text: "Cukup baik, hanya perlu dibangunkan saat sholat Subuh.", score: 3 },
                    { text: "Masih bergantung, sering terlambat bangun dan malas mandi.", score: 2 },
                    { text: "Semua urusan kebersihan masih harus disiapkan dan diawasi ketat.", score: 1 }
                ]
            },
            {
                pilarId: 1,
                pilarName: "Kemandirian Fisik & Keseharian",
                icon: "fa-utensils",
                question: "Bagaimana pola makan Ananda ketika dihadapkan pada menu makanan sederhana atau menu bersama?",
                options: [
                    { text: "Tidak pilih-pilih makanan (fleksibel), makan apa saja yang halal & bergizi.", score: 4 },
                    { text: "Ada sedikit makanan yang kurang disuka, tapi tetap bisa menyesuaikan diri.", score: 3 },
                    { text: "Cukup pemilih (picky eater), sering sulit makan jika menunya asing.", score: 2 },
                    { text: "Sangat rewel dan hanya mau menu tertentu saja.", score: 1 }
                ]
            },

            // PILAR 2: KEMATANGAN EMOSI & KETAHANAN MENTAL
            {
                pilarId: 2,
                pilarName: "Kematangan Emosi & Ketahanan Mental",
                icon: "fa-heart",
                question: "Bagaimana reaksi Ananda ketika harus berpisah sementara dari orang tua (misal menginap di rumah kakek/acara sekolah)?",
                options: [
                    { text: "Tenang, percaya diri, menikmati suasana baru tanpa rasa cemas berlebih.", score: 4 },
                    { text: "Sempat rindu di hari awal, tapi cepat terbiasa dan ceria kembali.", score: 3 },
                    { text: "Sering menelepon sambil menangis dan ingin lekas dijemput pulang.", score: 2 },
                    { text: "Menolak keras, tantrum, atau menangis histeris jika ditinggal.", score: 1 }
                ]
            },
            {
                pilarId: 2,
                pilarName: "Kematangan Emosi & Ketahanan Mental",
                icon: "fa-shield-alt",
                question: "Bagaimana sikap Ananda saat menghadapi teguran, masalah, atau rasa kecewa?",
                options: [
                    { text: "Mampu mengendalikan diri, mendengarkan nasehat, dan mencari solusi.", score: 4 },
                    { text: "Sempat cemberut/diam sesaat, namun lekas sadar dan meminta maaf.", score: 3 },
                    { text: "Mudah ngambek lama, membanting barang, atau menyalahkan orang lain.", score: 2 },
                    { text: "Sangat rapuh, mudah putus asa, atau meledak-ledak marahnya.", score: 1 }
                ]
            },
            {
                pilarId: 2,
                pilarName: "Kematangan Emosi & Ketahanan Mental",
                icon: "fa-mobile-alt",
                question: "Bagaimana kesiapan Ananda terhadap aturan pembatasan gadget / smartphone selama di pondok?",
                options: [
                    { text: "Siap dan sadar bahwa tanpa gadget membuatnya lebih fokus belajar & tahfidz.", score: 4 },
                    { text: "Agak berat di awal, tapi bersedia menaati aturan asrama demi masa depan.", score: 3 },
                    { text: "Sering protes jika gadget dibatasi, masih ketergantungan game/medsos.", score: 2 },
                    { text: "Menolak mutlak masuk pesantren jika tidak diperbolehkan membawa HP bebas.", score: 1 }
                ]
            },

            // PILAR 3: MOTIVASI SPIRITUAL & KECINTAAN AL-QURAN
            {
                pilarId: 3,
                pilarName: "Motivasi Spiritual & Niat Al-Quran",
                icon: "fa-quran",
                question: "Dari mana dorongan utama keinginan Ananda untuk menuntut ilmu di pondok pesantren?",
                options: [
                    { text: "Keinginan kuat dari diri sendiri yang didukung penuh oleh orang tua.", score: 4 },
                    { text: "Tertarik setelah diajak diskusi dan melihat profil lingkungan pondok.", score: 3 },
                    { text: "Lebih karena mengikuti keinginan orang tua (belum dari hati sendiri).", score: 2 },
                    { text: "Merasa dipaksa atau dihukum oleh orang tua.", score: 1 }
                ]
            },
            {
                pilarId: 3,
                pilarName: "Motivasi Spiritual & Niat Al-Quran",
                icon: "fa-pray",
                question: "Bagaimana kedisiplinan Ananda dalam menjalankan ibadah sholat 5 waktu saat di rumah?",
                options: [
                    { text: "Sudah terbiasa sholat tepat waktu atas kesadaran sendiri.", score: 4 },
                    { text: "Sholat lengkap 5 waktu, walau kadang harus diingatkan saat adzan.", score: 3 },
                    { text: "Masih sering bolong atau menunda-nunda sholat hingga akhir waktu.", score: 2 },
                    { text: "Sangat sulit disuruh sholat dan sering mengabaikan panggilan ibadah.", score: 1 }
                ]
            },
            {
                pilarId: 3,
                pilarName: "Motivasi Spiritual & Niat Al-Quran",
                icon: "fa-book-open",
                question: "Bagaimana antusiasme dan daya tahan Ananda saat mengaji, tilawah, atau menghafal Al-Quran?",
                options: [
                    { text: "Senang membaca Al-Quran, fokus, dan memiliki target hafalan pribadi.", score: 4 },
                    { text: "Mau mengaji dengan tertib bila ada guru / ustadz yang mendampingi.", score: 3 },
                    { text: "Cepat bosan, mengantuk, atau gelisah jika mengaji lebih dari 15 menit.", score: 2 },
                    { text: "Enggan mengaji dan sangat sulit diajak tadarus Al-Quran.", score: 1 }
                ]
            },

            // PILAR 4: ADAPTASI SOSIAL & ADAB SEBAYA
            {
                pilarId: 4,
                pilarName: "Adaptasi Sosial & Adab Sebaya",
                icon: "fa-users",
                question: "Bagaimana kemampuan Ananda dalam bergaul dan menjalin pertemanan baru di lingkungan baru?",
                options: [
                    { text: "Ramah, mudah akrab, percaya diri, dan senang bekerja sama dalam tim.", score: 4 },
                    { text: "Awalnya butuh waktu mengamati, setelah itu bisa berbaur akrab.", score: 3 },
                    { text: "Cenderung pemalu, menyendiri, dan sulit membuka obrolan dengan kawan baru.", score: 2 },
                    { text: "Sering bertengkar, dominan/bossy, atau sulit menerima kehadiran orang lain.", score: 1 }
                ]
            },
            {
                pilarId: 4,
                pilarName: "Adaptasi Sosial & Adab Sebaya",
                icon: "fa-handshake",
                question: "Bagaimana sikap Ananda saat harus berbagi kamar, fasilitas, dan barang dengan teman lain?",
                options: [
                    { text: "Murah hati, toleran, dan senang berbagi dengan sesama teman.", score: 4 },
                    { text: "Bisa berbagi, asalkan teman meminta izin terlebih dahulu dengan sopan.", score: 3 },
                    { text: "Cukup posesif terhadap barangnya, sering tersinggung jika disentuh kawan.", score: 2 },
                    { text: "Sangat egois, tidak mau berbagi ruang maupun barang sama sekali.", score: 1 }
                ]
            },
            {
                pilarId: 4,
                pilarName: "Adaptasi Sosial & Adab Sebaya",
                icon: "fa-user-graduate",
                question: "Bagaimana adab dan rasa hormat Ananda kepada orang yang lebih tua (guru/ustadz/orang tua teman)?",
                options: [
                    { text: "Sopan santun, mencium tangan, bertutur kata lembut, dan patuh pada guru.", score: 4 },
                    { text: "Sopan, walau kadang gaya bahasanya agak terbawa bahasa gaul sebaya.", score: 3 },
                    { text: "Kurang peka adab, kadang suka memotong pembicaraan orang tua.", score: 2 },
                    { text: "Sering membantah dan kurang menunjukkan rasa hormat pada guru.", score: 1 }
                ]
            },

            // PILAR 5: KESIAPAN MENTAL & KOMITMEN ORANG TUA
            {
                pilarId: 5,
                pilarName: "Kesiapan & Dukungan Orang Tua",
                icon: "fa-home",
                question: "Seberapa ikhlas dan teguh hati Ayah & Bunda untuk melepas Ananda belajar mandiri di asrama pesantren?",
                options: [
                    { text: "Sangat ikhlas, ridho lillahi ta'ala, dan siap mendoakan siang-malam demi masa depan anak.", score: 4 },
                    { text: "Ikhlas dan siap, walau di awal mungkin akan terasa sepi di rumah.", score: 3 },
                    { text: "Masih diliputi rasa ragu, khawatir berlebihan, dan belum sepenuhnya tega.", score: 2 },
                    { text: "Salah satu orang tua (Ayah/Ibu) masih belum setuju anak masuk pondok.", score: 1 }
                ]
            },
            {
                pilarId: 5,
                pilarName: "Kesiapan & Dukungan Orang Tua",
                icon: "fa-hands-helping",
                question: "Bagaimana komitmen orang tua saat Ananda mengalami masa adaptasi (misal curhat homesick/rindu di bulan pertama)?",
                options: [
                    { text: "Siap menguatkan mental anak, tidak lekas panik, dan bekerja sama dengan ustadz musyrif.", score: 4 },
                    { text: "Berusaha menenangkan anak dan berkonsultasi dengan pengurus asrama.", score: 3 },
                    { text: "Cenderung mudah kasihan, baper, dan tergoda langsung menjemput pulang.", score: 2 },
                    { text: "Pasti akan langsung memindahkan anak jika anak menangis ingin pulang.", score: 1 }
                ]
            },
            {
                pilarId: 5,
                pilarName: "Kesiapan & Dukungan Orang Tua",
                icon: "fa-balance-scale",
                question: "Apakah Ayah dan Bunda sudah satu visi dan kompak dalam memilih jalan pendidikan pondok pesantren untuk Ananda?",
                options: [
                    { text: "Sangat kompak, satu visi, dan saling menguatkan dalam ikhtiar ini.", score: 4 },
                    { text: "Kompak, sudah ada kesepakatan keluarga besar.", score: 3 },
                    { text: "Masih ada sedikit perbedaan pendapat, tapi sedang diselaraskan.", score: 2 },
                    { text: "Belum ada kesepakatan, salah satu pihak masih menentang.", score: 1 }
                ]
            }
        ];

        // Global State
        let currentQuestionIndex = 0;
        let answers = {};
        let leadData = {};
        const CS_PHONE = "<?= $cs_phone ?>";

        // DOM Elements
        const formAwal = document.getElementById('form-data-awal');
        const sectionIntro = document.getElementById('section-intro');
        const sectionQuiz = document.getElementById('section-quiz');
        const sectionResult = document.getElementById('section-result');
        const questionsWrapper = document.getElementById('quiz-questions-wrapper');
        const btnPrevQ = document.getElementById('btn-prev-q');
        const btnNextQ = document.getElementById('btn-next-q');
        const progressBar = document.getElementById('quiz-progress-bar');
        const progressText = document.getElementById('quiz-progress-text');
        const labelSantriTarget = document.getElementById('label-santri-target');

        // Step 1: Submit Form Data Awal
        formAwal.addEventListener('submit', function (e) {
            e.preventDefault();
            const namaWali = document.getElementById('input_nama_wali').value.trim();
            const whatsapp = document.getElementById('input_whatsapp').value.trim();
            const kodeRef = document.getElementById('kode_ref').value;

            if (!namaWali || !whatsapp) {
                alert('Mohon lengkapi Nama Anda dan Nomor WhatsApp.');
                return;
            }

            leadData = {
                nama_wali: namaWali,
                whatsapp: whatsapp,
                kode_ref: kodeRef
            };

            labelSantriTarget.innerHTML = `Evaluasi Ananda: <strong>Keluarga ${namaWali}</strong>`;

            // Transition to Quiz
            sectionIntro.classList.add('hidden');
            sectionQuiz.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });

            currentQuestionIndex = 0;
            renderQuestion(currentQuestionIndex);
        });

        // Step 2: Render Single Question
        function renderQuestion(index) {
            const q = quizData[index];
            const currentAnswer = answers[index];

            // Update Progress
            const progressPercent = ((index + 1) / quizData.length) * 100;
            progressBar.style.width = `${progressPercent}%`;
            progressText.innerText = `Soal ${index + 1} dari ${quizData.length}`;

            // Buttons state
            btnPrevQ.disabled = (index === 0);
            btnNextQ.disabled = (currentAnswer === undefined);
            if (index === quizData.length - 1) {
                btnNextQ.innerHTML = 'Dapatkan Analisa Lengkap Sekarang <i class="fas fa-arrow-right ml-2 text-amber-300"></i>';
            } else {
                btnNextQ.innerHTML = 'Selanjutnya <i class="fas fa-chevron-right ml-1.5"></i>';
            }

            // Build Question HTML
            let html = `
                <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl border border-gray-100 transition duration-300">
                    <!-- Category Badge -->
                    <div class="flex items-center space-x-2 text-xs font-bold text-emerald-800 bg-emerald-50 px-3.5 py-1.5 rounded-xl w-fit mb-4 border border-emerald-100">
                        <i class="fas ${q.icon} text-emerald-600"></i>
                        <span>Pilar: ${q.pilarName}</span>
                    </div>

                    <!-- Question Title -->
                    <h3 class="text-base sm:text-lg md:text-xl font-bold text-gray-900 leading-snug mb-6">
                        ${index + 1}. ${q.question}
                    </h3>

                    <!-- Options -->
                    <div class="space-y-3">
            `;

            q.options.forEach((opt, optIndex) => {
                const isSelected = (currentAnswer === optIndex);
                html += `
                    <div onclick="selectOption(${index}, ${optIndex})" 
                         class="option-card cursor-pointer p-4 sm:p-5 rounded-2xl border-2 ${isSelected ? 'selected' : 'border-gray-200 bg-gray-50/50'} flex items-start space-x-3.5">
                        <div class="w-6 h-6 rounded-full border-2 ${isSelected ? 'border-emerald-600 bg-emerald-600 text-white' : 'border-gray-300 bg-white'} flex items-center justify-center flex-shrink-0 mt-0.5 text-xs">
                            ${isSelected ? '<i class="fas fa-check"></i>' : String.fromCharCode(65 + optIndex)}
                        </div>
                        <div class="flex-1 text-xs sm:text-sm font-medium ${isSelected ? 'text-emerald-950 font-semibold' : 'text-gray-700'}">
                            ${opt.text}
                        </div>
                    </div>
                `;
            });

            html += `
                    </div>
                </div>
            `;

            questionsWrapper.innerHTML = html;
        }

        // Handle Option Select
        function selectOption(qIndex, optIndex) {
            answers[qIndex] = optIndex;
            renderQuestion(qIndex);

            // Auto advance after slight delay for smooth mobile UX
            setTimeout(() => {
                if (currentQuestionIndex < quizData.length - 1) {
                    currentQuestionIndex++;
                    renderQuestion(currentQuestionIndex);
                } else {
                    // Di Soal Terakhir: Aktifkan tombol dan otomatis proses analisa
                    if (btnNextQ) {
                        btnNextQ.disabled = false;
                        btnNextQ.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-amber-300"></i> Menghitung Analisa Kesiapan...';
                    }
                    setTimeout(() => {
                        calculateAndShowResult();
                    }, 350);
                }
            }, 250);
        }

        // Prev & Next Buttons
        btnPrevQ.addEventListener('click', () => {
            if (currentQuestionIndex > 0) {
                currentQuestionIndex--;
                renderQuestion(currentQuestionIndex);
            }
        });

        btnNextQ.addEventListener('click', () => {
            if (answers[currentQuestionIndex] === undefined) {
                alert('Silakan pilih salah satu opsi jawaban terlebih dahulu.');
                return;
            }

            if (currentQuestionIndex < quizData.length - 1) {
                currentQuestionIndex++;
                renderQuestion(currentQuestionIndex);
            } else {
                // Selesai -> Proses Penilaian
                btnNextQ.disabled = true;
                btnNextQ.innerHTML = '<i class="fas fa-spinner fa-spin mr-2 text-amber-300"></i> Memproses Hasil Analisa...';
                calculateAndShowResult();
            }
        });

        // Step 3: Calculation & Result Generation
        function calculateAndShowResult() {
            try {
                // Hitung Skor Total & Per Pilar
                let totalScore = 0;
                const maxScore = quizData.length * 4; // 15 * 4 = 60
                const pilarScores = {
                    1: { name: "Kemandirian Fisik & Keseharian", earned: 0, max: 12, icon: "fa-tshirt" },
                    2: { name: "Kematangan Emosi & Ketahanan Mental", earned: 0, max: 12, icon: "fa-heart" },
                    3: { name: "Motivasi Spiritual & Niat Al-Quran", earned: 0, max: 12, icon: "fa-quran" },
                    4: { name: "Adaptasi Sosial & Adab Sebaya", earned: 0, max: 12, icon: "fa-users" },
                    5: { name: "Kesiapan & Dukungan Orang Tua", earned: 0, max: 12, icon: "fa-home" }
                };

                quizData.forEach((q, idx) => {
                    const chosenOptIndex = (answers[idx] !== undefined) ? answers[idx] : 0;
                    const score = (q.options[chosenOptIndex] && q.options[chosenOptIndex].score) ? q.options[chosenOptIndex].score : 3;
                    totalScore += score;
                    if (pilarScores[q.pilarId]) {
                        pilarScores[q.pilarId].earned += score;
                    }
                });

                // Persentase Kesiapan: (totalScore / maxScore) * 100
                const percentage = Math.round((totalScore / maxScore) * 100);
                const namaWaliDisplay = (leadData && leadData.nama_wali) ? leadData.nama_wali : 'Orang Tua';

                // Kategori & Diagnosis
                let kategori = "";
                let kategoriBadgeClass = "";
                let narasi = "";
                let kekuatan = "";
                let growth = "";

                if (percentage >= 85) {
                    kategori = "SANGAT SIAP & POTENSIAL BERPRESTASI";
                    kategoriBadgeClass = "bg-emerald-500 text-white";
                    narasi = `Alhamdulillah! Ananda di keluarga Bapak/Ibu <strong>${namaWaliDisplay}</strong> memiliki modal kemandirian, kematangan emosi, dan motivasi spiritual yang sangat prima. Ananda siap menjalani pola hidup asrama mandiri dengan daya adaptasi tinggi dan berpeluang besar melesat dalam hafalan Al-Quran serta prestasi akademik di Villa Quran Baron Malang.`;
                    kekuatan = `Ananda memiliki pondasi niat ibadah yang kuat, kemandirian self-care yang baik, serta ketahanan mental yang tangguh saat jauh dari rumah.`;
                    growth = `Pertahankan ritme muroja'ah hafalan dan berikan apresiasi positif atas kemandirian yang sudah terbentuk.`;
                } else if (percentage >= 65) {
                    kategori = "SIAP DENGAN PENDAMPINGAN ADAPTASI";
                    kategoriBadgeClass = "bg-amber-400 text-emerald-950";
                    narasi = `Masya Allah! Ananda di keluarga Bapak/Ibu <strong>${namaWaliDisplay}</strong> memiliki potensi dan kesiapan dasar yang baik untuk masuk pesantren. Ananda membutuhkan sedikit pembiasaan bertahap di 1-2 aspek (seperti adaptasi perpisahan di pekan awal atau manajemen disiplin gadget). Dengan bimbingan musyrif asrama yang penuh kasih sayang di Villa Quran, ananda insya Allah akan cepat nyaman.`;
                    kekuatan = `Kecerdasan sosial yang baik, mau bekerja sama dengan teman sebaya, dan memiliki rasa hormat pada ustadz/pembimbing.`;
                    growth = `Tingkatkan latihan mandiri merapikan barang dan kurangi ketergantungan pada gawai 30 hari sebelum masuk pondok.`;
                } else {
                    kategori = "PERLU PROGRAM PEMBIASAAN PRA-PESANTREN";
                    kategoriBadgeClass = "bg-rose-500 text-white";
                    narasi = `Ananda di keluarga Bapak/Ibu <strong>${namaWaliDisplay}</strong> memerlukan masa transisi dan pembiasaan pra-pondok terlebih dahulu bersama Ayah & Bunda di rumah. Fokuskan pada penyelarasan niat belajar agama tanpa paksaan, membangun rasa percaya diri, dan melatih kemandirian dasar agar saat masuk asrama ananda tidak mengalami kejutan budaya (culture shock).`;
                    kekuatan = `Ananda memiliki rasa ingin tahu yang tinggi dan sangat membutuhkan figur teladan/sahabat yang hangat dalam mengarahkan potensinya.`;
                    growth = `Perlu penguatan ketahanan emosi saat berpisah, pembiasaan sholat 5 waktu berjamaah, dan latihan tanggung jawab harian di rumah.`;
                }

                // Tampilkan ke View
                const elNamaSantri = document.getElementById('res-nama-santri');
                if (elNamaSantri) elNamaSantri.innerText = `(Keluarga ${namaWaliDisplay})`;
                
                const elScoreNum = document.getElementById('res-score-number');
                if (elScoreNum) elScoreNum.innerText = `${percentage}%`;

                const badgeEl = document.getElementById('res-kategori-badge');
                if (badgeEl) {
                    badgeEl.className = `inline-block px-4 py-1.5 rounded-full text-xs sm:text-sm font-extrabold uppercase tracking-wide shadow-md ${kategoriBadgeClass}`;
                    badgeEl.innerText = kategori;
                }

                const elNarasi = document.getElementById('res-narasi');
                if (elNarasi) elNarasi.innerHTML = narasi;

                const elStrength = document.getElementById('res-strength');
                if (elStrength) elStrength.innerHTML = kekuatan;

                const elGrowth = document.getElementById('res-growth');
                if (elGrowth) elGrowth.innerHTML = growth;

                // Render 5 Pilar Breakdown Progress
                let breakdownHtml = "";
                for (let pid in pilarScores) {
                    const p = pilarScores[pid];
                    const pPct = Math.round((p.earned / p.max) * 100);
                    let pColor = "bg-emerald-500";
                    if (pPct < 65) pColor = "bg-rose-500";
                    else if (pPct < 85) pColor = "bg-amber-400";

                    breakdownHtml += `
                        <div class="bg-gray-50/80 p-3.5 sm:p-4 rounded-2xl border border-gray-100">
                            <div class="flex items-center justify-between text-xs sm:text-sm font-bold text-gray-800 mb-1.5">
                                <span class="flex items-center">
                                    <i class="fas ${p.icon} text-emerald-700 mr-2 text-sm sm:text-base"></i>
                                    ${p.name}
                                </span>
                                <span class="text-emerald-900">${pPct}% (${p.earned}/${p.max})</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                <div class="${pColor} h-full rounded-full transition-all duration-500" style="width: ${pPct}%"></div>
                            </div>
                        </div>
                    `;
                }
                const elBreakdown = document.getElementById('res-breakdown-container');
                if (elBreakdown) elBreakdown.innerHTML = breakdownHtml;

                // Setup WhatsApp Consultation Link
                const cleanPhone = (typeof CS_PHONE !== 'undefined' && CS_PHONE) ? CS_PHONE.replace(/[^0-9]/g, '') : '6285189918115';
                const waMsg = encodeURIComponent("Mohon dikirim analisa asesmen nya");
                const waUrl = `https://wa.me/${cleanPhone}?text=${waMsg}`;
                const btnWa = document.getElementById('btn-wa-consult');
                if (btnWa) {
                    btnWa.href = waUrl;
                    btnWa.innerHTML = '<i class="fab fa-whatsapp text-lg mr-2"></i> Buka WhatsApp & Terima Analisa';
                }

                // Simpan ke Database via AJAX
                saveLeadToDatabase(percentage, kategori, {
                    pilarScores: pilarScores,
                    strength: kekuatan,
                    growth: growth
                });

                // Transition UI
                sectionQuiz.classList.add('hidden');
                sectionResult.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // Otomatis Redirect ke WhatsApp setelah jeda 1.2 detik
                setTimeout(() => {
                    window.location.href = waUrl;
                }, 1200);

                // Trigger Confetti
                if (typeof confetti === 'function') {
                    confetti({
                        particleCount: 80,
                        spread: 70,
                        origin: { y: 0.6 }
                    });
                }
            } catch (err) {
                console.error('Error saat memproses analisa:', err);
                // Fallback UI transition jika terjadi error tak terduga
                sectionQuiz.classList.add('hidden');
                sectionResult.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // AJAX Saver
        function saveLeadToDatabase(skor, kategori, details) {
            const formData = new FormData();
            formData.append('nama_wali', leadData.nama_wali);
            formData.append('whatsapp', leadData.whatsapp);
            formData.append('nama_santri', 'Ananda (Keluarga ' + leadData.nama_wali + ')');
            formData.append('gender', '-');
            formData.append('jenjang', '-');
            formData.append('kota', '-');
            formData.append('kode_ref', leadData.kode_ref || 'organik');
            formData.append('skor_persen', skor);
            formData.append('kategori', kategori);
            formData.append('detail_evaluasi', JSON.stringify(details));

            fetch('simpan-test-kesiapan.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    console.log('Lead test saved:', data);
                })
                .catch(err => {
                    console.warn('Gagal menyimpan lead:', err);
                });
        }

        function restartQuiz() {
            if (confirm('Ulangi tes kesiapan dari awal?')) {
                answers = {};
                currentQuestionIndex = 0;
                sectionResult.classList.add('hidden');
                sectionIntro.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }
    </script>
</body>

</html>