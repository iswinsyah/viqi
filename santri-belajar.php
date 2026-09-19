<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$active_menu = 'dashboard_santri';

// Tangkap nama mata pelajaran (Default: Sosiologi)
$mapel = $_GET['mapel'] ?? 'Sosiologi';
$mapel_esc = $conn->real_escape_string($mapel);
$bab_no = (int)($_GET['bab'] ?? 1);

// Data Profil Santri
$res_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id LIMIT 1");
$data_santri = ($res_s && $res_s->num_rows > 0) ? $res_s->fetch_assoc() : null;
$kelas_santri = $data_santri['kelas_sekarang'] ?? 'Santri';

// ==========================================
// 1. QUERY BAB DARI DATABASE (ELEARNING_BAB)
// ==========================================
$res_babs = $conn->query("SELECT * FROM elearning_bab WHERE mapel_nama = '$mapel_esc' ORDER BY nomor_bab ASC, id ASC");
$list_bab = ($res_babs && $res_babs->num_rows > 0) ? $res_babs->fetch_all(MYSQLI_ASSOC) : [];

$materi_aktif = null;
if (count($list_bab) > 0) {
    // Cari bab berdasarkan nomor_bab
    foreach ($list_bab as $b) {
        if ((int)$b['nomor_bab'] === $bab_no) {
            $materi_aktif = $b;
            break;
        }
    }
    // Jika tidak ketemu, pakai bab pertama
    if (!$materi_aktif) {
        $materi_aktif = $list_bab[0];
        $bab_no = (int)$materi_aktif['nomor_bab'];
    }
}

// ==========================================
// 2. QUERY KUIS DARI DATABASE (ELEARNING_KUIS)
// ==========================================
$list_kuis = [];
if ($materi_aktif && isset($materi_aktif['id'])) {
    $b_id = (int)$materi_aktif['id'];
    $res_k = $conn->query("SELECT * FROM elearning_kuis WHERE bab_id = $b_id ORDER BY id ASC");
    if ($res_k) {
        $list_kuis = $res_k->fetch_all(MYSQLI_ASSOC);
    }
}

// Decode ringkasan materi jika format JSON
$ringkasan_data = [];
if ($materi_aktif && !empty($materi_aktif['ringkasan_materi'])) {
    $decoded = json_decode($materi_aktif['ringkasan_materi'], true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $ringkasan_data = $decoded;
    } else {
        $ringkasan_data = ['Intisari Pembelajaran' => $materi_aktif['ringkasan_materi']];
    }
}

// Format Embed PDF / Web E-Modul
function getPdfViewerUrl($pdfUrl) {
    if (empty($pdfUrl)) return '';
    $pdfUrl = trim($pdfUrl);
    // Jika link adalah portal emodul / flipbook web
    if (strpos($pdfUrl, 'emodul.kemendikdasmen.go.id') !== false || strpos($pdfUrl, 'buku.kemdikbud.go.id') !== false) {
        return $pdfUrl;
    }
    // Jika link direct PDF dan bukan google viewer
    if (strpos($pdfUrl, '.pdf') !== false && strpos($pdfUrl, 'docs.google.com') === false) {
        return $pdfUrl;
    }
    return $pdfUrl;
}

// Format Embed YouTube
function getYoutubeEmbedUrl($url) {
    if (empty($url)) return '';
    $url = trim($url);
    if (strpos($url, 'youtube.com/embed/') !== false) return $url;
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $match)) {
        return 'https://www.youtube.com/embed/' . $match[1];
    }
    return $url;
}

// Kamus Video Pembelajaran Edukasi Terverifikasi Aktif
function getVerifiedSubjectVideos($mapel, $bab_no) {
    $m = strtolower(trim($mapel));
    $catalog = [
        'sosiologi' => [
            1 => [
                ['title' => '1. Konsep Dasar & Ciri Sosiologi', 'url' => 'https://www.youtube.com/embed/5v6kS6uHkPQ'],
                ['title' => '2. Sosiologi Sebagai Ilmu (Quipper)', 'url' => 'https://www.youtube.com/embed/n33QxUf_T6k'],
                ['title' => '3. Tokoh & Objek Sosiologi', 'url' => 'https://www.youtube.com/embed/tE5_6gKxZ20'],
            ],
            2 => [
                ['title' => '1. Individu & Interaksi Sosial', 'url' => 'https://www.youtube.com/embed/S2pE8vjQj2M'],
                ['title' => '2. Dinamika Kelompok Sosial', 'url' => 'https://www.youtube.com/embed/7V8kZ9mYq1s'],
                ['title' => '3. Bentuk Interaksi Asosiatif & Disosiatif', 'url' => 'https://www.youtube.com/embed/Z0oYvK5r0d4'],
            ],
            3 => [
                ['title' => '1. Ragam Gejala Sosial', 'url' => 'https://www.youtube.com/embed/T09MskjGz_Q'],
                ['title' => '2. Masalah Sosial & Penanganannya', 'url' => 'https://www.youtube.com/embed/V6sK3l0w9zA'],
            ],
            4 => [
                ['title' => '1. Konflik & Integrasi Sosial', 'url' => 'https://www.youtube.com/embed/P4rW8tX5z2k'],
                ['title' => '2. Resolusi Konflik Sosial', 'url' => 'https://www.youtube.com/embed/K9qL2vM8x7s'],
            ]
        ],
        'ekonomi' => [
            1 => [
                ['title' => '1. Konsep Ilmu Ekonomi & Kelangkaan', 'url' => 'https://www.youtube.com/embed/1v0T29r0Q5E'],
                ['title' => '2. Masalah Pokok Ekonomi', 'url' => 'https://www.youtube.com/embed/8v6L0zN8m4Q'],
            ]
        ],
        'geografi' => [
            1 => [
                ['title' => '1. Konsep & Prinsip Geografi', 'url' => 'https://www.youtube.com/embed/X5pQ8wR2z9k'],
                ['title' => '2. Pendekatan Ilmu Geografi', 'url' => 'https://www.youtube.com/embed/L7zK3vM9x2w'],
            ]
        ],
        'sejarah' => [
            1 => [
                ['title' => '1. Konsep Berpikir Sejarah', 'url' => 'https://www.youtube.com/embed/P6sK8vM2z1Q'],
                ['title' => '2. Cara Berpikir Diakronik & Sinkronik', 'url' => 'https://www.youtube.com/embed/J8wR3tY7u9k'],
            ]
        ]
    ];

    if (isset($catalog[$m][$bab_no])) {
        return $catalog[$m][$bab_no];
    }
    if (isset($catalog[$m][1])) {
        return $catalog[$m][1];
    }
    return [
        ['title' => '1. Video Materi ' . htmlspecialchars($mapel), 'url' => 'https://www.youtube.com/embed/5v6kS6uHkPQ']
    ];
}

// Helper parsing multi-videos
$videos_list = [];
if ($materi_aktif) {
    if (!empty($materi_aktif['video_urls'])) {
        $decoded_v = json_decode($materi_aktif['video_urls'], true);
        if (is_array($decoded_v)) {
            $labels = ['1. Konsep Inti', '2. Pendalaman & Kasus', '3. Contoh & Pembahasan', '4. Wawasan Pembanding', '5. Praktik Lapangan'];
            foreach ($decoded_v as $idx => $v) {
                if (is_string($v) && !empty(trim($v))) {
                    $lbl = $labels[$idx] ?? ('Video ' . ($idx + 1));
                    $embedUrl = getYoutubeEmbedUrl(trim($v));
                    $videos_list[] = [
                        'title' => $lbl,
                        'url' => $embedUrl,
                        'raw' => trim($v)
                    ];
                }
            }
        }
    }
    if (empty($videos_list) && !empty($materi_aktif['video_url'])) {
        $videos_list[] = [
            'title' => 'Video Pembelajaran Utama',
            'url' => getYoutubeEmbedUrl($materi_aktif['video_url']),
            'raw' => $materi_aktif['video_url']
        ];
    }

    // Jika video list masih kosong, gunakan katalog video edukasi terverifikasi
    if (empty($videos_list)) {
        $videos_list = getVerifiedSubjectVideos($mapel, $bab_no);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Learning <?= htmlspecialchars($mapel) ?> | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .theme-teal { background-color: #0d8276; }
        .theme-bg-mint { background-color: #e1f5f2; }
    </style>
</head>
<body class="bg-[#e1f5f2] font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER KELAS E-LEARNING -->
        <header class="h-16 bg-[#0d8276] text-white shadow-md flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center space-x-3">
                <a href="ruang-santri.php" class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex items-center space-x-2">
                    <div class="w-9 h-9 rounded-xl bg-white text-[#0d8276] flex items-center justify-center text-lg font-black shadow-inner">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h1 class="font-black text-sm sm:text-base leading-tight">E-Learning <?= htmlspecialchars($mapel) ?></h1>
                        <p class="text-[10px] text-teal-100"><?= htmlspecialchars($kelas_santri) ?> • Mandiri & Terbimbing AI</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <button onclick="bukaUstadzAI()" class="bg-amber-400 hover:bg-amber-300 text-teal-950 font-black px-3.5 py-1.5 rounded-full text-xs shadow-md transition flex items-center gap-1.5 animate-pulse">
                    <i class="fas fa-robot"></i>
                    <span class="hidden sm:inline">Tanya</span> Ustadz AI
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#e1f5f2] p-3.5 sm:p-6 pb-24 md:pb-8">
            <div class="max-w-4xl mx-auto">
                
                <?php if (count($list_bab) > 0 && $materi_aktif): ?>
                
                <!-- 1. DAFTAR PILIHAN BAB / MODUL BELAJAR (PILL TABS) -->
                <div class="mb-5 overflow-x-auto hide-scrollbar flex items-center gap-2 pb-1">
                    <?php foreach ($list_bab as $b): ?>
                    <a href="santri-belajar.php?mapel=<?= urlencode($mapel) ?>&bab=<?= $b['nomor_bab'] ?>" 
                       class="whitespace-nowrap px-4 py-2.5 rounded-2xl text-xs font-extrabold transition-all flex items-center gap-2 <?= ($bab_no == $b['nomor_bab']) ? 'bg-[#0d8276] text-white shadow-md shadow-teal-900/10 scale-100' : 'bg-white text-slate-700 hover:bg-teal-50 border border-teal-100/80' ?>">
                        <span class="w-5 h-5 rounded-full <?= ($bab_no == $b['nomor_bab']) ? 'bg-white/20 text-white' : 'bg-teal-100 text-[#0d8276]' ?> flex items-center justify-center text-[10px]">
                            <?= $b['nomor_bab'] ?>
                        </span>
                        <span><?= explode(':', $b['judul_bab'])[0] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- 2. BANNER JUDUL BAB AKTIF -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-teal-100 shadow-sm mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
                        <div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-teal-50 text-[#0d8276] border border-teal-100">Modul Pembelajaran Aktif</span>
                            <h2 class="text-base sm:text-xl font-black text-slate-900 mt-1.5"><?= htmlspecialchars($materi_aktif['judul_bab']) ?></h2>
                            <?php if(!empty($materi_aktif['subjudul'])): ?>
                                <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($materi_aktif['subjudul']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <span class="text-xs text-slate-500 font-semibold bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100 flex items-center gap-1.5">
                                <i class="far fa-clock text-[#0d8276]"></i> <?= htmlspecialchars($materi_aktif['durasi_menit'] ?? '15 Menit') ?>
                            </span>
                        </div>
                    </div>

                    <!-- 3. E-MODUL RESMI KEMENDIKDASMEN RI - EMBED SEBELUM VIDEO YOUTUBE -->
                    <?php if (!empty($materi_aktif['pdf_url'])): ?>
                    <div class="mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <i class="fas fa-book-reader text-rose-600 text-sm"></i> 1. E-Modul Resmi Kemendikdasmen RI
                            </h3>
                            <div class="flex items-center gap-2">
                                <a href="<?= htmlspecialchars($materi_aktif['pdf_url']) ?>" target="_blank" class="text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 px-3 py-1.5 rounded-xl shadow-sm transition flex items-center gap-1.5">
                                    <i class="fas fa-external-link-alt"></i> <span>Buka Flipbook / PDF</span>
                                </a>
                                <a href="https://emodul.kemendikdasmen.go.id/" target="_blank" class="text-xs font-bold text-rose-700 hover:text-rose-900 bg-rose-50 px-2.5 py-1.5 rounded-xl border border-rose-200 transition flex items-center gap-1">
                                    <i class="fas fa-globe"></i> <span class="hidden sm:inline">Portal Negara</span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="w-full h-[480px] sm:h-[580px] rounded-2xl overflow-hidden border-2 border-rose-200/80 shadow-md bg-white relative">
                            <iframe src="<?= getPdfViewerUrl($materi_aktif['pdf_url']) ?>" class="w-full h-full border-0" title="E-Modul Resmi Kemendikdasmen"></iframe>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 4. VIDEO EMBED YOUTUBE PEMBELAJARAN (3-5 VIDEO PEMBANDING) -->
                    <?php if (!empty($videos_list)): ?>
                    <div class="mb-6">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 flex items-center gap-2">
                                <i class="fas fa-play-circle text-red-500 text-sm"></i> 2. Video Penjelasan Materi (<?= count($videos_list) ?> Video Pembanding)
                            </h3>
                            <div class="flex items-center gap-2">
                                <a href="https://www.youtube.com/results?search_query=<?= urlencode('Pelajaran ' . $mapel . ' ' . $materi_aktif['judul_bab']) ?>" target="_blank" class="text-[11px] font-bold text-red-700 hover:text-red-900 bg-red-50 px-2.5 py-1 rounded-lg border border-red-200 transition flex items-center gap-1">
                                    <i class="fab fa-youtube"></i> <span>Cari Video Serupa</span> <i class="fas fa-external-link-alt text-[9px]"></i>
                                </a>
                            </div>
                        </div>

                        <!-- TABS PILIHAN VIDEO -->
                        <?php if (count($videos_list) > 1): ?>
                        <div class="flex items-center gap-2 mb-3 overflow-x-auto hide-scrollbar pb-1">
                            <?php foreach ($videos_list as $vIdx => $vItem): ?>
                            <button type="button" 
                                    onclick="gantiVideoPembelajaran('<?= htmlspecialchars($vItem['url']) ?>', this)" 
                                    class="video-tab-btn whitespace-nowrap px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($vIdx === 0) ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' ?>">
                                <i class="fab fa-youtube"></i>
                                <span><?= htmlspecialchars($vItem['title']) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="relative w-full overflow-hidden rounded-2xl bg-slate-900 shadow-lg" style="padding-top: 56.25%;">
                            <iframe id="mainVideoPlayer" class="absolute top-0 left-0 w-full h-full" 
                                    src="<?= htmlspecialchars($videos_list[0]['url']) ?>" 
                                    title="Video Pembelajaran" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 5. MODUL RANGKUMAN BACAAN MATERI -->
                    <?php if (!empty($ringkasan_data)): ?>
                    <div class="mb-6">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-book-open text-[#0d8276] text-sm"></i> 3. Rangkuman Konsep Penting
                        </h3>
                        <div class="bg-[#e1f5f2]/40 rounded-2xl p-4 sm:p-5 border border-teal-100/80 space-y-3.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                            <?php foreach ($ringkasan_data as $header => $isi): ?>
                                <div class="bg-white p-3.5 rounded-xl border border-teal-50 shadow-2xs">
                                    <h4 class="font-extrabold text-[#0d8276] mb-1.5 text-xs sm:text-sm flex items-center gap-1.5">
                                        <i class="fas fa-check-circle text-teal-500 text-xs"></i> <?= htmlspecialchars($header) ?>
                                    </h4>
                                    <?php if (is_array($isi)): ?>
                                        <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1 text-xs sm:text-[13px]">
                                            <?php foreach ($isi as $item): ?>
                                                <li><?= $item ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-slate-600 text-xs sm:text-[13px]"><?= $isi ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 6. LEMBAR KERJA SISWA (LKS & TUGAS MANDIRI) -->
                    <?php if (!empty($materi_aktif['lks_tugas'])): ?>
                    <div class="mb-6">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-pencil-alt text-amber-500 text-sm"></i> 4. Lembar Kerja Siswa (LKS)
                        </h3>
                        <div class="bg-amber-50/60 rounded-2xl p-4 sm:p-5 border border-amber-100">
                            <h4 class="font-extrabold text-amber-900 text-xs sm:text-sm mb-1"><?= htmlspecialchars($materi_aktif['lks_judul'] ?? 'Tugas Mandiri') ?></h4>
                            <p class="text-xs text-amber-800 leading-relaxed"><?= nl2br(htmlspecialchars($materi_aktif['lks_tugas'])) ?></p>
                            
                            <div class="mt-4 pt-3 border-t border-amber-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <span class="text-[11px] text-amber-700 italic">Kerjakan di buku catatan atau diskusikan langsung dengan Ustadz AI!</span>
                                <button onclick="konsultasiLksKeAI()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow-sm self-start sm:self-auto flex items-center gap-1.5">
                                    <i class="fas fa-magic"></i> Diskusikan Jawaban ke AI
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 7. KUIS LATIHAN SOAL INTERAKTIF DENGAN AUTO-SCORE -->
                    <?php if (count($list_kuis) > 0): ?>
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-question-circle text-indigo-500 text-sm"></i> 5. Kuis Latihan Pemahaman
                        </h3>
                        
                        <div class="bg-indigo-50/40 rounded-2xl p-4 sm:p-5 border border-indigo-100 space-y-4" id="quizContainer">
                            <?php foreach ($list_kuis as $qIdx => $q): ?>
                            <div class="bg-white p-4 rounded-xl border border-indigo-100/80 shadow-2xs" id="question_box_<?= $qIdx ?>">
                                <p class="font-bold text-slate-900 text-xs sm:text-sm mb-3">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 inline-flex items-center justify-center text-xs mr-1.5"><?= $qIdx + 1 ?></span>
                                    <?= htmlspecialchars($q['soal']) ?>
                                </p>
                                <div class="space-y-2">
                                    <?php 
                                    $opsi_arr = [
                                        'A' => $q['opsi_a'],
                                        'B' => $q['opsi_b'],
                                        'C' => $q['opsi_c'],
                                        'D' => $q['opsi_d']
                                    ];
                                    foreach ($opsi_arr as $optKey => $opsiText): ?>
                                    <label class="flex items-center p-2.5 rounded-xl border border-slate-200 hover:bg-indigo-50/50 cursor-pointer transition text-xs text-slate-700 font-medium">
                                        <input type="radio" name="quiz_<?= $qIdx ?>" value="<?= $optKey ?>" class="w-4 h-4 text-[#0d8276] focus:ring-teal-500 mr-2.5" onchange="cekJawaban(<?= $qIdx ?>, '<?= $optKey ?>', '<?= $q['kunci_jawaban'] ?>', '<?= addslashes($q['pembahasan'] ?? 'Jawaban yang tepat adalah pilihan ' . $q['kunci_jawaban']) ?>')">
                                        <span><b><?= $optKey ?>.</b> <?= htmlspecialchars($opsiText) ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <div id="feedback_<?= $qIdx ?>" class="hidden mt-3 p-3 rounded-xl text-xs font-semibold"></div>
                            </div>
                            <?php endforeach; ?>

                            <div id="quizResultSummary" class="hidden bg-white p-4 rounded-xl border border-teal-200 text-center">
                                <h4 class="font-black text-sm text-slate-900">Hasil Latihan Kuis Selesai! 🎉</h4>
                                <p class="text-xs text-slate-500 mt-1" id="quizScoreText">Skor: 100/100</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

                <?php else: ?>
                <!-- JIKA BELUM ADA MATERI BAB -->
                <div class="bg-white rounded-3xl p-8 sm:p-12 text-center border border-teal-100 shadow-sm max-w-xl mx-auto my-12">
                    <div class="w-16 h-16 rounded-2xl bg-teal-50 text-[#0d8276] flex items-center justify-center text-3xl mx-auto mb-4">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <h2 class="font-black text-lg text-slate-900">Modul Belajar Segera Hadir</h2>
                    <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                        Materi pembelajaran untuk mata pelajaran <b><?= htmlspecialchars($mapel) ?></b> sedang dipersiapkan oleh Ustadz Pengampu / Tim Kurikulum.
                    </p>
                    <a href="ruang-santri.php" class="mt-6 inline-flex items-center gap-2 bg-[#0d8276] hover:bg-[#0b6f65] text-white text-xs font-bold px-6 py-2.5 rounded-xl shadow transition">
                        <i class="fas fa-arrow-left"></i> Kembali ke Beranda
                    </a>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <!-- ================================================== -->
    <!-- MODAL USTADZ AI TUTOR (INTERAKTIF CHAT + SUARA)    -->
    <!-- ================================================== -->
    <div id="aiModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4 transition-opacity">
        <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl border border-teal-100 flex flex-col h-[85vh] sm:h-[600px] overflow-hidden animate-in slide-in-from-bottom duration-300">
            
            <!-- Modal Header -->
            <div class="bg-[#0d8276] text-white p-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-400 text-teal-950 flex items-center justify-center text-lg font-black shadow-md">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm leading-tight flex items-center gap-1.5">
                            Ustadz AI <?= htmlspecialchars($mapel) ?>
                            <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        </h3>
                        <p class="text-[10px] text-teal-100"><?= htmlspecialchars($materi_aktif['judul_bab'] ?? $mapel) ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-1">
                    <button onclick="toggleSound()" id="soundToggleBtn" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition" title="Suara AI Aktif">
                        <i class="fas fa-volume-up text-xs"></i>
                    </button>
                    <button onclick="tutupUstadzAI()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Chat Messages Body -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#e1f5f2]/30" id="chatContainer">
                <div class="flex items-start gap-2.5">
                    <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-teal-100 shadow-2xs text-xs text-slate-800 max-w-[85%] leading-relaxed">
                        <p class="font-bold text-[#0d8276] mb-1">Ahlan Wa Sahlan, <?= htmlspecialchars($santri_nama) ?>! 🌸</p>
                        <p>Saya <b>Ustadz AI Pembimbing <?= htmlspecialchars($mapel) ?></b>. Saya siap membantumu memahami materi <b><?= htmlspecialchars($materi_aktif['judul_bab'] ?? $mapel) ?></b>.</p>
                        <p class="mt-2 text-slate-600">Silakan tanyakan materi yang belum jelas, minta penjelasan rumus/konsep, atau bimbingan LKS!</p>
                    </div>
                </div>
            </div>

            <!-- Quick Suggestion Prompts -->
            <div class="px-3 py-2 bg-white border-t border-slate-100 overflow-x-auto hide-scrollbar flex items-center gap-1.5 flex-shrink-0 text-[11px]">
                <button onclick="kirimPesanOtomatis('Ustadz, tolong jelaskan konsep utama bab ini dengan contoh sehari-hari!')" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-teal-50 text-[#0d8276] hover:bg-teal-100 border border-teal-100 font-bold transition">
                    💡 Konsep Utama
                </button>
                <button onclick="kirimPesanOtomatis('Bagaimana tips mudah menghafal materi ini?')" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-teal-50 text-[#0d8276] hover:bg-teal-100 border border-teal-100 font-bold transition">
                    📖 Tips Mudah
                </button>
                <button onclick="kirimPesanOtomatis('Beri contoh kasus nyata di lingkungan pesantren!')" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-teal-50 text-[#0d8276] hover:bg-teal-100 border border-teal-100 font-bold transition">
                    🕌 Contoh Nyata
                </button>
            </div>

            <!-- Chat Input Bar -->
            <div class="p-3 bg-white border-t border-slate-200 flex items-center gap-2 flex-shrink-0">
                <button id="micBtn" onclick="toggleVoiceInput()" class="w-10 h-10 rounded-2xl bg-teal-50 hover:bg-teal-100 text-[#0d8276] flex items-center justify-center transition flex-shrink-0" title="Bicara dengan Suara">
                    <i class="fas fa-microphone text-base"></i>
                </button>

                <input type="text" id="userInput" placeholder="Tanya Ustadz AI tentang <?= htmlspecialchars($mapel) ?>..." class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white" onkeydown="if(event.key==='Enter') kirimPesan()">

                <button onclick="kirimPesan()" id="sendBtn" class="w-10 h-10 rounded-2xl bg-[#0d8276] hover:bg-[#0b6f65] text-white flex items-center justify-center shadow-md transition flex-shrink-0">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- BOTTOM NAVBAR MOBILE -->
    <?php include 'bottombar-santri.php'; ?>

    <script>
        // --- LOGIC KUIS ---
        let totalScore = 0;
        let answeredQuestions = {};

        function cekJawaban(qIdx, selectedKey, correctKey, pembahasan) {
            const feedbackBox = document.getElementById('feedback_' + qIdx);
            feedbackBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200', 'bg-rose-50', 'text-rose-800', 'border-rose-200');
            
            if (selectedKey === correctKey) {
                feedbackBox.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
                feedbackBox.innerHTML = `<i class="fas fa-check-circle text-emerald-600 mr-1"></i> <b>Tepat Sekali!</b> ${pembahasan}`;
                answeredQuestions[qIdx] = 100;
            } else {
                feedbackBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
                feedbackBox.innerHTML = `<i class="fas fa-times-circle text-rose-600 mr-1"></i> <b>Kurang Tepat.</b> ${pembahasan}`;
                answeredQuestions[qIdx] = 0;
            }

            const totalQ = <?= count($list_kuis) ?>;
            if (totalQ > 0 && Object.keys(answeredQuestions).length === totalQ) {
                let sum = Object.values(answeredQuestions).reduce((a, b) => a + b, 0);
                let finalScore = Math.round(sum / totalQ);
                const summaryBox = document.getElementById('quizResultSummary');
                const scoreText = document.getElementById('quizScoreText');
                scoreText.innerHTML = `Nilai Latihan Anda: <b>${finalScore}/100</b> • ${finalScore >= 75 ? 'Alhamdulillah Tuntas!' : 'Yuk baca lagi materinya dan tanya Ustadz AI!'}`;
                summaryBox.classList.remove('hidden');
            }
        }

        // --- LOGIC USTADZ AI ---
        let isVoiceActive = true;
        let recognition = null;
        let isListening = false;

        function bukaUstadzAI() {
            document.getElementById('aiModal').classList.remove('hidden');
            document.getElementById('userInput').focus();
        }

        function tutupUstadzAI() {
            document.getElementById('aiModal').classList.add('hidden');
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        }

        function toggleSound() {
            isVoiceActive = !isVoiceActive;
            const btn = document.getElementById('soundToggleBtn');
            btn.innerHTML = isVoiceActive ? '<i class="fas fa-volume-up text-xs"></i>' : '<i class="fas fa-volume-mute text-xs"></i>';
            if (!isVoiceActive && window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        }

        function konsultasiLksKeAI() {
            bukaUstadzAI();
            kirimPesanOtomatis("Ustadz, bagaimana petunjuk dan langkah pengerjaan tugas LKS ini: '<?= addslashes($materi_aktif['lks_tugas'] ?? '') ?>'?");
        }

        function kirimPesanOtomatis(text) {
            document.getElementById('userInput').value = text;
            kirimPesan();
        }

        async function kirimPesan() {
            const input = document.getElementById('userInput');
            const text = input.value.trim();
            if (!text) return;

            input.value = '';
            const container = document.getElementById('chatContainer');

            container.innerHTML += `
                <div class="flex items-start justify-end gap-2.5">
                    <div class="bg-[#0d8276] text-white p-3.5 rounded-2xl rounded-tr-none shadow-2xs text-xs max-w-[85%] leading-relaxed font-medium">
                        ${escapeHtml(text)}
                    </div>
                </div>
            `;
            container.scrollTop = container.scrollHeight;

            const typingId = 'typing_' + Date.now();
            container.innerHTML += `
                <div class="flex items-start gap-2.5" id="${typingId}">
                    <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-teal-100 text-xs text-slate-500 italic">
                        <i class="fas fa-circle-notch fa-spin text-[#0d8276] mr-1"></i> Ustadz AI sedang menyiapkan penjelasan...
                    </div>
                </div>
            `;
            container.scrollTop = container.scrollHeight;

            const contextPrompt = `
Anda adalah "Ustadz AI <?= addslashes($mapel) ?>", seorang guru dan ustadz pembimbing mata pelajaran <?= addslashes($mapel) ?> yang sangat ramah, santun, cerdas, komunikatif, dan penuh motivasi islami di platform e-learning SADIGS 4.0.

Konteks Pembelajaran:
- Mata Pelajaran: <?= addslashes($mapel) ?>
- Bab Aktif: <?= addslashes($materi_aktif['judul_bab'] ?? $mapel) ?> (<?= addslashes($materi_aktif['subjudul'] ?? '') ?>)
- Nama Santri: <?= addslashes($santri_nama) ?>

Instruksi Anda:
1. Sapa santri dengan ramah (misal: "Ahlan ananda <?= addslashes($santri_nama) ?>", "Masya Allah pertanyaan yang sangat bagus!").
2. Jelaskan materi dengan bahasa yang mudah dipahami anak sekolah/pesantren, gunakan analogi kehidupan nyata.
3. Berikan poin-poin yang terstruktur rapi.
4. Jawab secara jelas dan to-the-point.

Pertanyaan Santri:
"${text}"
            `;

            try {
                const response = await fetch('api-gemini.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: contextPrompt })
                });

                const data = await response.json();
                const typingElem = document.getElementById(typingId);
                if (typingElem) typingElem.remove();

                let aiText = "Mohon maaf ananda, Ustadz sedang mengalami sedikit kendala jaringan. Coba tanyakan sekali lagi ya!";
                if (data && data.status === 'success' && data.result) {
                    aiText = data.result;
                }

                let formattedHtml = formatAiMarkdown(aiText);

                container.innerHTML += `
                    <div class="flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-teal-100 shadow-2xs text-xs text-slate-800 max-w-[85%] leading-relaxed space-y-2">
                            ${formattedHtml}
                        </div>
                    </div>
                `;
                container.scrollTop = container.scrollHeight;

                if (isVoiceActive) {
                    speakText(aiText);
                }

            } catch (err) {
                const typingElem = document.getElementById(typingId);
                if (typingElem) typingElem.remove();
                container.innerHTML += `
                    <div class="flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-teal-100 text-xs text-slate-800 max-w-[85%]">
                            Afwan ananda, terjadi kendala saat menghubungkan ke server. Silakan coba lagi.
                        </div>
                    </div>
                `;
                container.scrollTop = container.scrollHeight;
            }
        }

        // Web Speech Recognition
        function toggleVoiceInput() {
            const micBtn = document.getElementById('micBtn');
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                alert("Browser Anda belum mendukung input suara. Silakan gunakan Google Chrome di HP.");
                return;
            }

            if (!recognition) {
                recognition = new SpeechRecognition();
                recognition.lang = 'id-ID';
                recognition.continuous = false;
                recognition.interimResults = false;

                recognition.onstart = function() {
                    isListening = true;
                    micBtn.classList.add('bg-rose-500', 'text-white', 'animate-pulse');
                    document.getElementById('userInput').placeholder = "Mendengarkan suara Anda...";
                };

                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    document.getElementById('userInput').value = transcript;
                    kirimPesan();
                };

                recognition.onerror = function() {
                    isListening = false;
                    micBtn.classList.remove('bg-rose-500', 'text-white', 'animate-pulse');
                    document.getElementById('userInput').placeholder = "Tanya Ustadz AI tentang <?= htmlspecialchars($mapel) ?>...";
                };

                recognition.onend = function() {
                    isListening = false;
                    micBtn.classList.remove('bg-rose-500', 'text-white', 'animate-pulse');
                    document.getElementById('userInput').placeholder = "Tanya Ustadz AI tentang <?= htmlspecialchars($mapel) ?>...";
                };
            }

            if (isListening) {
                recognition.stop();
            } else {
                recognition.start();
            }
        }

        // Web Speech Synthesis
        function speakText(text) {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();

            const cleanText = text.replace(/[*_#`]/g, '').replace(/<[^>]*>?/gm, '');
            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'id-ID';
            utterance.rate = 1.05;
            utterance.pitch = 1.0;
            window.speechSynthesis.speak(utterance);
        }

        function formatAiMarkdown(text) {
            let html = text
                .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
                .replace(/\*(.*?)\*/g, '<i>$1</i>')
                .replace(/\n\n/g, '<br><br>')
                .replace(/\n/g, '<br>');
            return html;
        }

        function gantiVideoPembelajaran(videoUrl, btnElement) {
            const iframe = document.getElementById('mainVideoPlayer');
            if (iframe) {
                iframe.src = videoUrl;
            }
            document.querySelectorAll('.video-tab-btn').forEach(btn => {
                btn.className = 'video-tab-btn whitespace-nowrap px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700';
            });
            if (btnElement) {
                btnElement.className = 'video-tab-btn whitespace-nowrap px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-red-600 text-white shadow-sm';
            }
        }
    </script>
</body>
</html>
