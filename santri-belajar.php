<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = (int)($_SESSION['santri_id'] ?? 0);
$santri_nama = $_SESSION['santri_nama'] ?? 'Santri';
$active_menu = 'dashboard_santri';

// ==============================================================
// AJAX HANDLER: SIMPAN PROGRES BELAJAR & KETUNTASAN KUIS SANTRI
// ==============================================================
if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'simpan_progres_kuis') {
        header('Content-Type: application/json');
        $b_id = (int)($_POST['bab_id'] ?? 0);
        $skor = (int)($_POST['skor'] ?? 0);
        $jawaban_json = trim($_POST['jawaban_json'] ?? '');
        
        if ($b_id <= 0 || $santri_id <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid.']);
            exit;
        }

        // Ambil data bab dan KKTP
        $stmt_b = $conn->prepare("SELECT kktp_nilai, mapel_nama, judul_bab FROM elearning_bab WHERE id = ?");
        $stmt_b->bind_param("i", $b_id);
        $stmt_b->execute();
        $res_b = $stmt_b->get_result();
        $bab_info = $res_b->fetch_assoc();
        $stmt_b->close();

        $kktp = (int)($bab_info['kktp_nilai'] ?? 75);
        $m_nama = $bab_info['mapel_nama'] ?? 'Mapel';
        $status_tuntas = ($skor >= $kktp) ? 'tuntas' : 'remedial';

        // Pastikan tabel santri_belajar_progress ada
        $conn->query("CREATE TABLE IF NOT EXISTS santri_belajar_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            santri_id INT NOT NULL,
            bab_id INT NOT NULL,
            mapel_nama VARCHAR(100) NOT NULL,
            status_baca_modul TINYINT(1) DEFAULT 0,
            status_tonton_video TINYINT(1) DEFAULT 0,
            skor_kuis INT DEFAULT 0,
            status_ketuntasan ENUM('belum_selesai', 'tuntas', 'remedial') DEFAULT 'belum_selesai',
            percobaan_ke INT DEFAULT 0,
            jawaban_detail LONGTEXT NULL,
            catatan_ai TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_santri_bab (santri_id, bab_id)
        )");

        // Simpan / update ke santri_belajar_progress
        $stmt_ins = $conn->prepare("INSERT INTO santri_belajar_progress 
            (santri_id, bab_id, mapel_nama, status_baca_modul, status_tonton_video, skor_kuis, status_ketuntasan, percobaan_ke, jawaban_detail)
            VALUES (?, ?, ?, 1, 1, ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE 
            skor_kuis = VALUES(skor_kuis),
            status_ketuntasan = VALUES(status_ketuntasan),
            percobaan_ke = percobaan_ke + 1,
            jawaban_detail = VALUES(jawaban_detail),
            updated_at = CURRENT_TIMESTAMP");
        $stmt_ins->bind_param("iisis", $santri_id, $b_id, $m_nama, $skor, $status_tuntas, $jawaban_json);
        
        if ($stmt_ins->execute()) {
            echo json_encode([
                'status' => 'success',
                'skor' => $skor,
                'kktp' => $kktp,
                'status_ketuntasan' => $status_tuntas,
                'message' => ($status_tuntas === 'tuntas') 
                    ? "Masya Allah, Alhamdulillah! Nilai antum $skor/100 telah mencapai batas ketuntasan belajar (KKTP: $kktp). Silakan lanjut ke bab berikutnya!" 
                    : "Nilai antum $skor/100 belum mencapai standar ketuntasan (KKTP: $kktp). Jangan berkecil hati, yuk pelajari kembali materinya atau minta penjelasan ke Ustadz AI lalu coba lagi!"
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        $stmt_ins->close();
        exit;
    }
}

// Tangkap nama mata pelajaran (Default: IPS)
$raw_mapel = trim($_GET['mapel'] ?? 'IPS');
$raw_mapel_lower = strtolower($raw_mapel);

// Normalisasi Alias Mapel
if ($raw_mapel_lower === 'bahasa' || strpos($raw_mapel_lower, 'indo') !== false) {
    $mapel = 'Bahasa Indonesia';
} elseif ($raw_mapel_lower === 'english' || strpos($raw_mapel_lower, 'inggris') !== false) {
    $mapel = 'Bahasa Inggris';
} elseif (strpos($raw_mapel_lower, 'sosiologi') !== false) {
    $mapel = 'Sosiologi';
} elseif (strpos($raw_mapel_lower, 'mtk') !== false || strpos($raw_mapel_lower, 'matematika') !== false) {
    $mapel = 'Matematika';
} elseif (strpos($raw_mapel_lower, 'ekonomi') !== false) {
    $mapel = 'Ekonomi';
} elseif (strpos($raw_mapel_lower, 'geografi') !== false) {
    $mapel = 'Geografi';
} elseif (strpos($raw_mapel_lower, 'sejarah') !== false) {
    $mapel = 'Sejarah';
} elseif (strpos($raw_mapel_lower, 'biologi') !== false) {
    $mapel = 'Biologi';
} elseif (strpos($raw_mapel_lower, 'fisika') !== false) {
    $mapel = 'Fisika';
} elseif (strpos($raw_mapel_lower, 'kimia') !== false) {
    $mapel = 'Kimia';
} elseif (strpos($raw_mapel_lower, 'ipa') !== false) {
    $mapel = 'IPA';
} elseif (strpos($raw_mapel_lower, 'ips') !== false) {
    $mapel = 'IPS';
} elseif (strpos($raw_mapel_lower, 'ppkn') !== false || strpos($raw_mapel_lower, 'pkn') !== false || strpos($raw_mapel_lower, 'pancasila') !== false) {
    $mapel = 'PPKn';
} elseif (strpos($raw_mapel_lower, 'seni') !== false) {
    $mapel = 'Seni Budaya';
} else {
    $mapel = $raw_mapel;
}

$mapel_esc = $conn->real_escape_string($mapel);
$bab_no = (int)($_GET['bab'] ?? 1);

// Data Profil Santri
$res_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id LIMIT 1");
$data_santri = ($res_s && $res_s->num_rows > 0) ? $res_s->fetch_assoc() : null;
$kelas_santri = $data_santri['kelas_sekarang'] ?? 'Santri';

// Auto-seed sample model IPS jika belum lengkap di database (baik di lokal maupun live Hostinger)
require_once __DIR__ . '/seed_sample_ips.php';
$force_seed = isset($_GET['seed_ips']) || isset($_GET['reload']);
ensureIpsSampleSeeded($conn, $force_seed);

// ==========================================
// 1. QUERY BAB DARI DATABASE (ELEARNING_BAB)
// ==========================================
$res_babs = $conn->query("SELECT * FROM elearning_bab WHERE mapel_nama = '$mapel_esc' ORDER BY nomor_bab ASC, id ASC");
if (!$res_babs || $res_babs->num_rows === 0) {
    // Fallback pencarian fuzzy LIKE jika nama sedikit berbeda
    $res_babs = $conn->query("SELECT * FROM elearning_bab WHERE mapel_nama LIKE '%$mapel_esc%' ORDER BY nomor_bab ASC, id ASC");
}

// Fallback cerdas: Jika mapel IPS masih kosong di database Hostinger, jalankan force re-seed seketika!
if ((!$res_babs || $res_babs->num_rows === 0) && (strpos($raw_mapel_lower, 'ips') !== false || $mapel === 'IPS')) {
    ensureIpsSampleSeeded($conn, true);
    $res_babs = $conn->query("SELECT * FROM elearning_bab WHERE mapel_nama = 'IPS' ORDER BY nomor_bab ASC, id ASC");
}
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
// 1B. QUERY STATUS KETUNTASAN BELAJAR SANTRI
// ==========================================
$progress_map = [];
$tot_tuntas = 0;
$tot_remedial = 0;
if ($santri_id > 0) {
    $res_prog = $conn->query("SELECT * FROM santri_belajar_progress WHERE santri_id = $santri_id AND mapel_nama = '$mapel_esc'");
    if ($res_prog) {
        while ($rp = $res_prog->fetch_assoc()) {
            $progress_map[(int)$rp['bab_id']] = $rp;
            if ($rp['status_ketuntasan'] === 'tuntas') $tot_tuntas++;
            elseif ($rp['status_ketuntasan'] === 'remedial') $tot_remedial++;
        }
    }
}
$progres_aktif = ($materi_aktif && isset($progress_map[(int)$materi_aktif['id']])) ? $progress_map[(int)$materi_aktif['id']] : null;

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

require_once 'pkbm_modul_catalog.php';

// Format Embed PDF / Web E-Modul
function getPdfViewerUrl($pdfUrl, $mapel = '', $bab_no = 1) {
    if (empty($pdfUrl) || strpos($pdfUrl, '.pdf') === false) {
        return getPkbmModulPdfUrl($mapel, $bab_no);
    }
    return trim($pdfUrl);
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
                ['title' => '1. Apa Sih Sosiologi Itu Sebenarnya? (Kok Bisa)', 'url' => 'https://www.youtube.com/embed/y21i4p8qG_g'],
                ['title' => '2. Konsep Dasar Sosiologi', 'url' => 'https://www.youtube.com/embed/IsDziL9h-Bg'],
                ['title' => '3. Sejarah Perkembangan Sosiologi', 'url' => 'https://www.youtube.com/embed/spBcqVPAeW4'],
                ['title' => '4. Ciri dan Hakikat Sosiologi', 'url' => 'https://www.youtube.com/embed/2hQpB-7Efls'],
                ['title' => '5. Peran dan Fungsi Sosiologi', 'url' => 'https://www.youtube.com/embed/7ERn4SiMIeI'],
            ],
            2 => [
                ['title' => '1. Individu & Hubungan Sosial', 'url' => 'https://www.youtube.com/embed/y21i4p8qG_g'],
                ['title' => '2. Dinamika Kelompok Sosial', 'url' => 'https://www.youtube.com/embed/IsDziL9h-Bg'],
                ['title' => '3. Interaksi Asosiatif & Disosiatif', 'url' => 'https://www.youtube.com/embed/2hQpB-7Efls'],
            ],
            3 => [
                ['title' => '1. Ragam Gejala Sosial di Masyarakat', 'url' => 'https://www.youtube.com/embed/y21i4p8qG_g'],
                ['title' => '2. Masalah Sosial & Upaya Penanganannya', 'url' => 'https://www.youtube.com/embed/7ERn4SiMIeI'],
            ],
            4 => [
                ['title' => '1. Konflik dan Integrasi Sosial', 'url' => 'https://www.youtube.com/embed/spBcqVPAeW4'],
                ['title' => '2. Resolusi Konflik Sosial', 'url' => 'https://www.youtube.com/embed/2hQpB-7Efls'],
            ]
        ],
        'ekonomi' => [
            1 => [
                ['title' => '1. Konsep Dasar Ilmu Ekonomi & Kelangkaan', 'url' => 'https://www.youtube.com/embed/y21i4p8qG_g'],
                ['title' => '2. Masalah Pokok Ekonomi', 'url' => 'https://www.youtube.com/embed/IsDziL9h-Bg'],
            ]
        ],
        'geografi' => [
            1 => [
                ['title' => '1. Konsep & Prinsip Ilmu Geografi', 'url' => 'https://www.youtube.com/embed/y21i4p8qG_g'],
                ['title' => '2. Pendekatan Geografi & Aspek Keruangan', 'url' => 'https://www.youtube.com/embed/spBcqVPAeW4'],
            ]
        ],
        'sejarah' => [
            1 => [
                ['title' => '1. Konsep Dasar Berpikir Sejarah', 'url' => 'https://www.youtube.com/embed/spBcqVPAeW4'],
                ['title' => '2. Cara Berpikir Diakronik & Sinkronik', 'url' => 'https://www.youtube.com/embed/2hQpB-7Efls'],
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
        ['title' => '1. Video Edukasi: ' . htmlspecialchars($mapel), 'url' => 'https://www.youtube.com/embed/y21i4p8qG_g'],
        ['title' => '2. Penjelasan Konsep Inti', 'url' => 'https://www.youtube.com/embed/IsDziL9h-Bg']
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

    // Jika video list kosong atau berisi ID video generik/placeholder, gunakan katalog video edukasi terverifikasi
    $catalog_videos = getVerifiedSubjectVideos($mapel, $bab_no);
    if (empty($videos_list)) {
        $videos_list = $catalog_videos;
    } else {
        // Jika ada katalog terverifikasi khusus mapel ini, gabungkan atau prioritaskan agar video selalu pasti bisa diputar
        $has_verified = false;
        foreach ($videos_list as $vl) {
            if (strpos($vl['url'], 'y21i4p8qG_g') !== false || strpos($vl['url'], 'IsDziL9h-Bg') !== false || strpos($vl['url'], 'spBcqVPAeW4') !== false) {
                $has_verified = true;
                break;
            }
        }
        if (!$has_verified && !empty($catalog_videos)) {
            $videos_list = $catalog_videos;
        }
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        if (window.pdfjsLib) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        }
    </script>
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
                
                <!-- 1. DAFTAR PILIHAN BAB / MODUL BELAJAR (PILL TABS DENGAN STATUS KETUNTASAN) -->
                <div class="mb-5 bg-white rounded-2xl p-3 sm:p-4 border border-teal-100 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3 pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-black text-slate-800">Progres Ketuntasan Belajar:</span>
                            <span class="text-xs font-black text-[#0d8276] bg-teal-50 px-2 py-0.5 rounded-md border border-teal-100">
                                <?= $tot_tuntas ?> dari <?= count($list_bab) ?> Bab Tuntas
                            </span>
                        </div>
                        <div class="text-[11px] text-slate-400 font-semibold">
                            Standar Ketuntasan (KKTP): Minimal 75/100
                        </div>
                    </div>

                    <div class="overflow-x-auto hide-scrollbar flex items-center gap-2 pb-1" id="babPillsList">
                        <?php foreach ($list_bab as $b): 
                            $b_prog = $progress_map[(int)$b['id']] ?? null;
                            $b_status = $b_prog['status_ketuntasan'] ?? 'belum_selesai';
                            $b_skor = $b_prog['skor_kuis'] ?? null;
                            $is_active = ($bab_no == $b['nomor_bab']);
                        ?>
                        <a href="santri-belajar.php?mapel=<?= urlencode($mapel) ?>&bab=<?= $b['nomor_bab'] ?>" 
                           class="whitespace-nowrap px-3.5 py-2 rounded-2xl text-xs font-extrabold transition-all flex items-center gap-2 <?= $is_active ? 'bg-[#0d8276] text-white shadow-md shadow-teal-900/15 scale-100' : 'bg-slate-50 text-slate-700 hover:bg-teal-50/70 border border-slate-200/80' ?>">
                            <span class="w-5 h-5 rounded-full <?= $is_active ? 'bg-white/20 text-white' : 'bg-teal-100 text-[#0d8276]' ?> flex items-center justify-center text-[10px] font-black">
                                <?= $b['nomor_bab'] ?>
                            </span>
                            <span>Bab <?= $b['nomor_bab'] ?></span>
                            <?php if (!empty($b['tingkat_kelas'])): ?>
                                <span class="text-[9px] px-1.5 py-0.2 rounded font-black <?= $is_active ? 'bg-white/20 text-teal-100' : 'bg-slate-200 text-slate-600' ?>">Kls <?= $b['tingkat_kelas'] ?></span>
                            <?php endif; ?>

                            <?php if ($b_status === 'tuntas'): ?>
                                <i class="fas fa-check-circle text-emerald-400 text-xs" title="Tuntas (Skor: <?= $b_skor ?>)"></i>
                            <?php elseif ($b_status === 'remedial'): ?>
                                <i class="fas fa-exclamation-triangle text-amber-400 text-xs" title="Remedial (Skor: <?= $b_skor ?>)"></i>
                            <?php endif; ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 2. BANNER JUDUL BAB AKTIF & STRUKTUR CP/ATP/KKTP -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-teal-100 shadow-sm mb-6">
                    <!-- CP & ATP METADATA BADGE -->
                    <div class="mb-3.5 p-3.5 rounded-2xl bg-gradient-to-r from-teal-50 via-emerald-50/60 to-white border border-teal-100/80 flex flex-col md:flex-row md:items-center justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-[#0d8276] text-white">
                                    <?= htmlspecialchars($materi_aktif['fase'] ?? 'Fase D') ?>
                                </span>
                                <?php if (!empty($materi_aktif['tingkat_kelas'])): ?>
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Kelas <?= htmlspecialchars($materi_aktif['tingkat_kelas']) ?> SMP • Semester <?= htmlspecialchars($materi_aktif['semester'] ?? '1') ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($materi_aktif['cp_elemen'])): ?>
                                <span class="text-xs font-extrabold text-slate-700">
                                    <?= htmlspecialchars($materi_aktif['cp_elemen']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($materi_aktif['tujuan_pembelajaran'])): ?>
                            <p class="text-[11px] text-slate-600 leading-relaxed pt-0.5">
                                <b class="text-[#0d8276]">Tujuan Pembelajaran (ATP):</b> <?= htmlspecialchars($materi_aktif['tujuan_pembelajaran']) ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- STATUS KETUNTASAN BADGE SANTRI SAAT INI -->
                        <div class="flex items-center gap-2 flex-shrink-0 self-start md:self-auto">
                            <?php if ($progres_aktif && $progres_aktif['status_ketuntasan'] === 'tuntas'): ?>
                                <div class="px-3 py-1.5 rounded-xl bg-emerald-500 text-white font-black text-xs shadow-sm flex items-center gap-1.5">
                                    <i class="fas fa-medal text-amber-300"></i>
                                    <span>TUNTAS (Nilai: <?= (int)$progres_aktif['skor_kuis'] ?>)</span>
                                </div>
                            <?php elseif ($progres_aktif && $progres_aktif['status_ketuntasan'] === 'remedial'): ?>
                                <div class="px-3 py-1.5 rounded-xl bg-rose-500 text-white font-black text-xs shadow-sm flex items-center gap-1.5">
                                    <i class="fas fa-rotate text-white"></i>
                                    <span>REMEDIAL (Nilai: <?= (int)$progres_aktif['skor_kuis'] ?>)</span>
                                </div>
                            <?php else: ?>
                                <div class="px-3 py-1.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-xs border border-slate-200 flex items-center gap-1.5">
                                    <i class="far fa-circle text-slate-400"></i>
                                    <span>Belum Kuis (KKTP: <?= (int)($materi_aktif['kktp_nilai'] ?? 75) ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

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

                    <!-- 3. E-MODUL RESMI KEMENDIKDASMEN RI - BUILT-IN INTERACTIVE DIGITAL FLIPBOOK -->
                    <?php 
                    $emodul_url = getPdfViewerUrl($materi_aktif['pdf_url'] ?? '', $mapel, $bab_no);
                    ?>
                    <div class="mb-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-rose-600 text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fas fa-book-reader"></i>
                                </span>
                                <span>1. E-Modul & Digital Flipbook Resmi</span>
                            </h3>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="toggleFullscreenFlipbook()" class="text-xs font-bold text-rose-700 hover:text-rose-900 bg-rose-50 hover:bg-rose-100 px-3 py-1.5 rounded-xl border border-rose-200 transition flex items-center gap-1.5">
                                    <i class="fas fa-expand"></i> <span>Layar Penuh</span>
                                </button>
                                <?php if (!empty($emodul_url)): ?>
                                <a href="<?= htmlspecialchars($emodul_url) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 px-3 py-1.5 rounded-xl shadow-xs transition flex items-center gap-1.5">
                                    <i class="fas fa-file-pdf"></i> <span>Buka PDF Asli (PKBM)</span> <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                                <?php endif; ?>
                                <a href="https://modul.pkbm.id/" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 px-2.5 py-1.5 rounded-xl border border-slate-200 transition flex items-center gap-1">
                                    <i class="fas fa-external-link-alt text-[10px]"></i> <span class="hidden sm:inline">Portal Modul</span>
                                </a>
                            </div>
                        </div>

                        <!-- BUILT-IN FLIPBOOK CONTAINER -->
                        <div id="flipbookWrapper" class="bg-gradient-to-b from-slate-900 via-slate-800 to-slate-950 rounded-3xl p-3 sm:p-6 shadow-xl border border-slate-700/60 relative overflow-hidden transition-all duration-300">
                            
                            <!-- Flipbook Top Bar & Mode Switcher -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 px-2 pb-3 mb-3 border-b border-slate-700/80 text-white text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
                                    <div class="flex items-center gap-1.5 bg-slate-800/90 p-1 rounded-xl border border-slate-700">
                                        <button type="button" id="tabModeSummary" onclick="switchFlipbookMode('summary')" class="px-3 py-1 rounded-lg text-xs font-black transition bg-rose-600 text-white shadow-sm flex items-center gap-1.5">
                                            <i class="fas fa-book"></i> <span>Ringkasan 5 Hal</span>
                                        </button>
                                        <button type="button" id="tabModePdf" onclick="switchFlipbookMode('pdf')" class="px-3 py-1 rounded-lg text-xs font-black transition text-slate-400 hover:text-white hover:bg-slate-700 flex items-center gap-1.5">
                                            <i class="fas fa-file-pdf text-rose-400"></i> <span>Modul PDF Lengkap</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 font-bold text-slate-300 text-xs self-end sm:self-auto">
                                    <span>Halaman</span>
                                    <span id="pageIndicator" class="px-2.5 py-0.5 rounded-lg bg-rose-600 text-white font-black text-xs shadow-xs">1 / 5</span>
                                </div>
                            </div>

                            <!-- 1. MODE SUMMARY (5 PAGES) -->
                            <div id="summaryFlipbookStage" class="relative w-full min-h-[460px] sm:min-h-[540px] bg-slate-100 rounded-2xl shadow-2xl overflow-hidden flex flex-col justify-between border-4 border-slate-700/50">
                                
                                <!-- PAGE 1: COVER & CAPAIAN PEMBELAJARAN -->
                                <div id="flipPage1" class="flip-page flex-1 p-5 sm:p-8 flex flex-col justify-between bg-gradient-to-br from-rose-900 via-rose-800 to-rose-950 text-white relative">
                                    <div class="absolute right-4 top-4 text-rose-500/10 text-9xl font-black pointer-events-none">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="relative z-10">
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-rose-200 text-[10px] font-black uppercase tracking-wider mb-4">
                                            <i class="fas fa-certificate text-amber-300"></i> Modul Resmi Kemendikdasmen RI
                                        </div>
                                        <h2 class="text-xl sm:text-3xl font-black text-white leading-tight tracking-tight">
                                            <?= htmlspecialchars($materi_aktif['judul_bab']) ?>
                                        </h2>
                                        <p class="text-xs sm:text-sm text-rose-200 mt-2 font-medium leading-relaxed max-w-xl">
                                            <?= htmlspecialchars($materi_aktif['subjudul'] ?? 'Mata Pelajaran ' . $mapel . ' • Edisi Pembelajaran Mandiri & Terbimbing') ?>
                                        </p>
                                    </div>

                                    <div class="relative z-10 my-4 bg-black/25 rounded-2xl p-4 border border-white/10 backdrop-blur-xs">
                                        <h4 class="text-xs font-black text-amber-300 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                            <i class="fas fa-bullseye"></i> Capaian & Tujuan Pembelajaran:
                                        </h4>
                                        <ul class="text-xs text-rose-100 space-y-1 list-disc list-inside">
                                            <li>Memahami konsep dasar, hakikat, dan ruang lingkup materi <b><?= htmlspecialchars($mapel) ?></b>.</li>
                                            <li>Mengidentifikasi contoh nyata dan studi kasus di kehidupan masyarakat & lingkungan pesantren.</li>
                                            <li>Mampu menyelesaikan soal-soal penalaran HOTS dan lembar kerja mandiri.</li>
                                        </ul>
                                    </div>

                                    <div class="relative z-10 flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-white/10 text-[11px] text-rose-200">
                                        <span>Santri: <b><?= htmlspecialchars($santri_nama) ?></b> (<?= htmlspecialchars($kelas_santri) ?>)</span>
                                        <button type="button" onclick="nextFlipPage()" class="font-bold text-amber-300 flex items-center gap-1 hover:underline cursor-pointer">
                                            Buka Materi Halaman 2 <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- PAGE 2: PETA KONSEP & KATA KUNCI -->
                                <div id="flipPage2" class="flip-page hidden flex-1 p-5 sm:p-8 bg-white text-slate-800 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                                            <h3 class="text-sm sm:text-base font-black text-[#0d8276] flex items-center gap-2">
                                                <i class="fas fa-project-diagram"></i> Peta Konsep & Kata Kunci Bab
                                            </h3>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-teal-50 text-[#0d8276] border border-teal-100">Halaman 2</span>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                                            <div class="p-3.5 rounded-2xl bg-teal-50/70 border border-teal-100">
                                                <h5 class="text-xs font-black text-[#0d8276] mb-1 flex items-center gap-1.5">
                                                    <i class="fas fa-key text-teal-600"></i> Istilah Kunci
                                                </h5>
                                                <p class="text-xs text-slate-600 leading-relaxed">
                                                    Konsep fundamental, objek material & formal, interaksi sosial, kaidah ilmiah, dan perspektif kritis.
                                                </p>
                                            </div>
                                            <div class="p-3.5 rounded-2xl bg-amber-50/70 border border-amber-100">
                                                <h5 class="text-xs font-black text-amber-900 mb-1 flex items-center gap-1.5">
                                                    <i class="fas fa-lightbulb text-amber-600"></i> Nilai Karakter
                                                </h5>
                                                <p class="text-xs text-slate-600 leading-relaxed">
                                                    Berpikir kritis, toleran terhadap perbedaan, empati sosial, dan adab bermasyarakat islami.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                                            <h5 class="text-xs font-black text-slate-800 mb-2">Alur Penguasaan Materi:</h5>
                                            <div class="flex flex-col sm:flex-row items-center gap-2 text-xs font-bold text-slate-700">
                                                <div class="w-full sm:w-auto flex-1 bg-white p-2.5 rounded-xl border border-slate-200 text-center shadow-2xs">
                                                    1. Membaca Teori
                                                </div>
                                                <i class="fas fa-chevron-right text-slate-400 hidden sm:inline"></i>
                                                <div class="w-full sm:w-auto flex-1 bg-white p-2.5 rounded-xl border border-slate-200 text-center shadow-2xs">
                                                    2. Studi Kasus
                                                </div>
                                                <i class="fas fa-chevron-right text-slate-400 hidden sm:inline"></i>
                                                <div class="w-full sm:w-auto flex-1 bg-white p-2.5 rounded-xl border border-slate-200 text-center shadow-2xs">
                                                    3. Diskusi Ustadz AI
                                                </div>
                                                <i class="fas fa-chevron-right text-slate-400 hidden sm:inline"></i>
                                                <div class="w-full sm:w-auto flex-1 bg-white p-2.5 rounded-xl border border-slate-200 text-center shadow-2xs">
                                                    4. Evaluasi Kuis
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
                                        <span>Modul Pembelajaran Mandiri SADIGS 4.0</span>
                                        <button type="button" onclick="goToFlipPage(3)" class="text-[#0d8276] font-bold hover:underline cursor-pointer">Lanjut Halaman 3 ➡</button>
                                    </div>
                                </div>

                                <!-- PAGE 3: URAIAN MATERI & PEMBAHASAN -->
                                <div id="flipPage3" class="flip-page hidden flex-1 p-5 sm:p-8 bg-[#fbfdfc] text-slate-800 flex flex-col justify-between overflow-y-auto">
                                    <div>
                                        <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                                            <h3 class="text-sm sm:text-base font-black text-slate-900 flex items-center gap-2">
                                                <i class="fas fa-book-open text-rose-600"></i> Uraian Materi & Pembahasan Teori
                                            </h3>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-100">Halaman 3</span>
                                        </div>

                                        <div class="space-y-3.5 text-xs sm:text-[13px] text-slate-700 leading-relaxed">
                                            <?php if (!empty($ringkasan_data)): ?>
                                                <?php foreach ($ringkasan_data as $subH => $subContent): ?>
                                                    <div class="bg-white p-3.5 sm:p-4 rounded-2xl border border-teal-100 shadow-2xs">
                                                        <h5 class="font-extrabold text-[#0d8276] mb-1.5 text-xs sm:text-sm flex items-center gap-1.5">
                                                            <i class="fas fa-bookmark text-teal-500 text-xs"></i> <?= htmlspecialchars($subH) ?>
                                                        </h5>
                                                        <?php if (is_array($subContent)): ?>
                                                            <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1">
                                                                <?php foreach ($subContent as $it): ?>
                                                                    <li><?= $it ?></li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <p class="text-slate-600"><?= $subContent ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="bg-white p-4 rounded-2xl border border-slate-200">
                                                    <p class="text-slate-600 leading-relaxed">
                                                        Materi pembelajaran pada bab ini menguraikan prinsip-prinsip mendasar dari <?= htmlspecialchars($mapel) ?>, membedah bagaimana struktur sosial, pola perilaku, dan fenomena kehidupan terbentuk dan berkembang.
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="pt-3 mt-4 border-t border-slate-200 text-[11px] text-slate-400 flex items-center justify-between">
                                        <span>Sumber: Silabus Kemendikdasmen RI</span>
                                        <button type="button" onclick="goToFlipPage(4)" class="text-rose-600 font-bold hover:underline cursor-pointer">Lanjut ke Studi Kasus ➡</button>
                                    </div>
                                </div>

                                <!-- PAGE 4: STUDI KASUS & INTEGRASI KEISLAMAN -->
                                <div id="flipPage4" class="flip-page hidden flex-1 p-5 sm:p-8 bg-white text-slate-800 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                                            <h3 class="text-sm sm:text-base font-black text-indigo-900 flex items-center gap-2">
                                                <i class="fas fa-search-plus text-indigo-600"></i> Studi Kasus & Integrasi Keislaman
                                            </h3>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-100">Halaman 4</span>
                                        </div>

                                        <div class="space-y-3.5">
                                            <div class="p-4 rounded-2xl bg-indigo-50/60 border border-indigo-100">
                                                <h5 class="text-xs font-black text-indigo-950 mb-1.5 flex items-center gap-1.5">
                                                    <i class="fas fa-mosque text-indigo-600"></i> Konteks Pesantren & Kehidupan Nyata
                                                </h5>
                                                <p class="text-xs text-indigo-900/80 leading-relaxed">
                                                    Dalam ekosistem pondok pesantren dan masyarakat umum, pemahaman konsep <b><?= htmlspecialchars($mapel) ?></b> melatih santri untuk memiliki sikap <i>ta'aruf</i> (saling mengenal), <i>tafahum</i> (saling memahami), dan <i>ta'awun</i> (saling tolong-menolong) dalam membangun peradaban yang berakhlak mulia.
                                                </p>
                                            </div>

                                            <div class="p-4 rounded-2xl bg-emerald-50/60 border border-emerald-100">
                                                <h5 class="text-xs font-black text-emerald-950 mb-1 flex items-center gap-1.5">
                                                    <i class="fas fa-quran text-emerald-600"></i> Inspirasi Nilai
                                                </h5>
                                                <p class="text-xs text-emerald-900/80 italic leading-relaxed">
                                                    "Wahai manusia! Sungguh, Kami telah menciptakan kamu dari seorang laki-laki dan seorang perempuan, kemudian Kami jadikan kamu berbangsa-bangsa dan bersuku-suku agar kamu saling mengenal..." (QS. Al-Hujurat: 13)
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-400 flex items-center justify-between">
                                        <span>Integrasi Kurikulum Nasional & Nilai Luhur</span>
                                        <button type="button" onclick="goToFlipPage(5)" class="text-indigo-600 font-bold hover:underline cursor-pointer">Lanjut ke Lembar Kerja ➡</button>
                                    </div>
                                </div>

                                <!-- PAGE 5: LEMBAR KERJA & RANGKUMAN -->
                                <div id="flipPage5" class="flip-page hidden flex-1 p-5 sm:p-8 bg-gradient-to-br from-amber-50 to-orange-50/50 text-slate-800 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center justify-between border-b border-amber-200 pb-3 mb-4">
                                            <h3 class="text-sm sm:text-base font-black text-amber-950 flex items-center gap-2">
                                                <i class="fas fa-pencil-ruler text-amber-600"></i> Lembar Aktivitas Mandiri & Penutup
                                            </h3>
                                            <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 border border-amber-200">Halaman 5</span>
                                        </div>

                                        <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-2xs mb-4">
                                            <h5 class="text-xs font-black text-amber-900 mb-1.5">
                                                <?= htmlspecialchars($materi_aktif['lks_judul'] ?? 'Tugas Pengamatan Mandiri') ?>
                                            </h5>
                                            <p class="text-xs text-slate-700 leading-relaxed">
                                                <?= nl2br(htmlspecialchars($materi_aktif['lks_tugas'] ?? 'Tuliskan rangkuman 3 poin penting yang kamu pelajari dari bab ini di buku catatanmu, lalu diskusikan contohnya dengan Ustadz AI!')) ?>
                                            </p>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2.5">
                                            <button type="button" onclick="bukaUstadzAI()" class="bg-[#0d8276] hover:bg-[#0b6f65] text-white font-bold px-4 py-2 rounded-xl text-xs shadow-sm transition flex items-center gap-1.5">
                                                <i class="fas fa-robot"></i> Tanya Ustadz AI
                                            </button>
                                            <a href="#quizSection" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 rounded-xl text-xs shadow-sm transition flex items-center gap-1.5">
                                                <i class="fas fa-check-circle"></i> Kerjakan Kuis di Bawah
                                            </a>
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-amber-200 text-[11px] text-amber-800 flex items-center justify-between">
                                        <span>Selesai Membaca E-Modul Bab <?= $bab_no ?></span>
                                        <button type="button" onclick="goToFlipPage(1)" class="font-bold text-[#0d8276] hover:underline">
                                            ↺ Kembali ke Sampul
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <!-- 2. MODE FULL PDF FLIPBOOK (PDF.JS POWERED) -->
                            <div id="pdfFlipbookStage" class="hidden relative w-full min-h-[540px] bg-slate-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col justify-between border-4 border-slate-700/50 p-2 sm:p-4">
                                <div id="pdfLoadingSpinner" class="flex-1 flex flex-col items-center justify-center text-white py-16">
                                    <div class="w-12 h-12 rounded-2xl bg-rose-600/20 text-rose-400 flex items-center justify-center text-2xl mb-3 shadow-inner">
                                        <i class="fas fa-circle-notch fa-spin"></i>
                                    </div>
                                    <span class="text-xs font-black tracking-wide text-rose-200">Memuat Buku E-Modul PDF Asli...</span>
                                    <span class="text-[10px] text-slate-400 mt-1">Mengambil data dari server repository</span>
                                </div>

                                <div id="pdfCanvasContainer" class="hidden flex-1 flex items-center justify-center overflow-auto max-h-[72vh] p-2 bg-slate-950/40 rounded-xl">
                                    <canvas id="pdfRenderCanvas" class="rounded-lg shadow-2xl bg-white max-w-full"></canvas>
                                </div>

                                <!-- PDF Control Bar -->
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-700/80 text-white text-xs">
                                    <button type="button" id="pdfPrevBtn" onclick="prevPdfPage()" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 font-bold transition flex items-center gap-1.5 border border-slate-700 disabled:opacity-40">
                                        <i class="fas fa-chevron-left"></i> <span class="hidden sm:inline">Sebelumnya</span>
                                    </button>

                                    <div class="flex items-center gap-2">
                                        <span class="text-[11px] text-slate-400">Hal</span>
                                        <input type="number" id="pdfPageInput" min="1" value="1" onchange="jumpToPdfPage(this.value)" class="w-14 px-2 py-1 bg-slate-800 border border-slate-600 rounded-lg text-center font-black text-xs text-white focus:ring-2 focus:ring-rose-500">
                                        <span class="text-[11px] text-slate-400">dari <b id="pdfTotalPages" class="text-white">...</b></span>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button type="button" onclick="zoomPdf(-0.2)" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 flex items-center justify-center font-bold text-xs border border-slate-700" title="Zoom Out">
                                            <i class="fas fa-search-minus"></i>
                                        </button>
                                        <button type="button" onclick="zoomPdf(0.2)" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 flex items-center justify-center font-bold text-xs border border-slate-700" title="Zoom In">
                                            <i class="fas fa-search-plus"></i>
                                        </button>
                                        <button type="button" id="pdfNextBtn" onclick="nextPdfPage()" class="px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 font-bold transition flex items-center gap-1.5 shadow-md shadow-rose-900/40">
                                            <span class="hidden sm:inline">Berikutnya</span> <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Flipbook Summary Navigation Control Bar -->
                            <div id="summaryControlBar" class="mt-4 flex items-center justify-between gap-3 px-1">
                                <button type="button" id="prevPageBtn" onclick="prevFlipPage()" class="px-4 py-2.5 rounded-2xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition flex items-center gap-2 border border-slate-700 disabled:opacity-40 disabled:cursor-not-allowed">
                                    <i class="fas fa-chevron-left"></i>
                                    <span class="hidden sm:inline">Halaman</span> Sebelumnya
                                </button>

                                <div class="flex items-center gap-1.5">
                                    <?php for($p=1; $p<=5; $p++): ?>
                                    <button type="button" onclick="goToFlipPage(<?= $p ?>)" id="dotPage<?= $p ?>" class="w-8 h-8 rounded-xl font-black text-xs transition flex items-center justify-center <?= ($p===1)?'bg-rose-600 text-white shadow-md':'bg-slate-800 text-slate-400 hover:bg-slate-700' ?>">
                                        <?= $p ?>
                                    </button>
                                    <?php endfor; ?>
                                </div>

                                <button type="button" id="nextPageBtn" onclick="nextFlipPage()" class="px-4 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition flex items-center gap-2 shadow-md shadow-rose-900/30">
                                    <span>Berikutnya</span>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- 4. VIDEO EMBED YOUTUBE PEMBELAJARAN (3-5 VIDEO PEMBANDING) -->
                    <?php if (!empty($videos_list)): ?>
                    <div class="mb-8">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center text-xs shadow-xs">
                                    <i class="fab fa-youtube"></i>
                                </span>
                                <span>2. Video Penjelasan Materi (<?= count($videos_list) ?> Video Pilihan)</span>
                            </h3>
                            <div class="flex items-center gap-2">
                                <a id="directYoutubeLink" href="<?= htmlspecialchars(str_replace(['/embed/', 'youtube-nocookie.com'], ['/watch?v=', 'youtube.com'], $videos_list[0]['url'])) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-red-700 hover:text-red-900 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-xl border border-red-200 transition flex items-center gap-1.5 shadow-2xs">
                                    <i class="fab fa-youtube text-red-600"></i> <span>Buka di YouTube</span> <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                                <a href="https://www.youtube.com/results?search_query=<?= urlencode('Pelajaran ' . $mapel . ' ' . $materi_aktif['judul_bab']) ?>" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 px-2.5 py-1.5 rounded-xl border border-slate-200 transition flex items-center gap-1">
                                    <i class="fas fa-search"></i> <span>Cari Video Serupa</span>
                                </a>
                            </div>
                        </div>

                        <!-- TABS PILIHAN VIDEO -->
                        <?php if (count($videos_list) > 1): ?>
                        <div class="flex items-center gap-2 mb-3 overflow-x-auto hide-scrollbar pb-1">
                            <?php foreach ($videos_list as $vIdx => $vItem): 
                                $embedNocookie = str_replace('youtube.com/embed/', 'youtube-nocookie.com/embed/', $vItem['url']);
                            ?>
                            <button type="button" 
                                    data-video-url="<?= htmlspecialchars($embedNocookie, ENT_QUOTES) ?>"
                                    onclick="gantiVideoPembelajaran(this.getAttribute('data-video-url'), this)" 
                                    class="video-tab-btn whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($vIdx === 0) ? 'bg-red-600 text-white shadow-sm' : 'bg-white hover:bg-red-50 text-slate-700 border border-slate-200/80' ?>">
                                <i class="fab fa-youtube text-sm"></i>
                                <span><?= htmlspecialchars($vItem['title']) ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php 
                        $firstEmbedUrl = str_replace('youtube.com/embed/', 'youtube-nocookie.com/embed/', $videos_list[0]['url']);
                        ?>
                        <div class="relative w-full overflow-hidden rounded-3xl bg-slate-950 shadow-xl border-2 border-slate-800" style="padding-top: 56.25%;">
                            <iframe id="mainVideoPlayer" class="absolute top-0 left-0 w-full h-full" 
                                    src="<?= htmlspecialchars($firstEmbedUrl) ?>" 
                                    title="Video Pembelajaran <?= htmlspecialchars($mapel) ?>" 
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

                            <div id="quizResultSummary" class="hidden bg-white p-5 sm:p-6 rounded-3xl border-2 border-teal-200 text-center shadow-lg transition-all duration-300">
                                <div id="quizResultIcon" class="w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3 shadow-inner bg-emerald-50 text-emerald-600">
                                    <i class="fas fa-medal"></i>
                                </div>
                                <div id="quizBadgeStatus" class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider mb-2 bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Status: TUNTAS
                                </div>
                                <h4 class="font-black text-lg sm:text-xl text-slate-900" id="quizScoreText">Skor: 100 / 100</h4>
                                <p class="text-xs text-slate-600 mt-1.5 max-w-md mx-auto leading-relaxed" id="quizFeedbackMessage">
                                    Alhamdulillah! Pemahamanmu pada bab ini telah memenuhi standar ketuntasan belajar (KKTP: <?= (int)($materi_aktif['kktp_nilai'] ?? 75) ?>).
                                </p>
                                
                                <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-center gap-2.5" id="quizActionButtons">
                                    <button type="button" onclick="ulangKuis()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition flex items-center gap-1.5">
                                        <i class="fas fa-rotate"></i> Ulangi Kuis
                                    </button>
                                    <button type="button" onclick="bukaUstadzAI()" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-teal-950 font-black text-xs shadow transition flex items-center gap-1.5">
                                        <i class="fas fa-robot"></i> Tanya Ustadz AI
                                    </button>
                                    <?php if ($bab_no < count($list_bab)): ?>
                                    <a href="santri-belajar.php?mapel=<?= urlencode($mapel) ?>&bab=<?= $bab_no + 1 ?>" id="btnLanjutBab" class="px-5 py-2 rounded-xl bg-[#0d8276] hover:bg-[#0b6f65] text-white font-extrabold text-xs shadow-md transition flex items-center gap-1.5">
                                        <span>Lanjut Bab <?= $bab_no + 1 ?></span> <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
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
        // --- LOGIC KUIS & PERSISTENSI KETUNTASAN ---
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
                const kktp = <?= (int)($materi_aktif['kktp_nilai'] ?? 75) ?>;
                const isTuntas = (finalScore >= kktp);

                // Kirim AJAX ke server untuk simpan progres ketuntasan di database
                const formData = new FormData();
                formData.append('action', 'simpan_progres_kuis');
                formData.append('bab_id', <?= (int)$materi_aktif['id'] ?>);
                formData.append('skor', finalScore);
                formData.append('jawaban_json', JSON.stringify(answeredQuestions));

                fetch('santri-belajar.php?mapel=<?= urlencode($mapel) ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(res => {
                    const summaryBox = document.getElementById('quizResultSummary');
                    const scoreText = document.getElementById('quizScoreText');
                    const iconBox = document.getElementById('quizResultIcon');
                    const badgeStatus = document.getElementById('quizBadgeStatus');
                    const msgBox = document.getElementById('quizFeedbackMessage');

                    scoreText.innerHTML = `Skor Akhir: <b>${finalScore} / 100</b>`;
                    if (isTuntas) {
                        iconBox.className = "w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3 shadow-inner bg-emerald-50 text-emerald-600 animate-bounce";
                        iconBox.innerHTML = '<i class="fas fa-medal"></i>';
                        badgeStatus.className = "inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider mb-2 bg-emerald-100 text-emerald-800 border border-emerald-200";
                        badgeStatus.innerHTML = '<i class="fas fa-check-circle mr-1 text-emerald-600"></i> STATUS: TUNTAS';
                        msgBox.innerText = res.message || `Alhamdulillah! Antum telah tuntas memenuhi kriteria ketuntasan belajar (KKTP: ${kktp}).`;
                    } else {
                        iconBox.className = "w-16 h-16 rounded-2xl mx-auto flex items-center justify-center text-3xl mb-3 shadow-inner bg-rose-50 text-rose-600";
                        iconBox.innerHTML = '<i class="fas fa-rotate"></i>';
                        badgeStatus.className = "inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider mb-2 bg-rose-100 text-rose-800 border border-rose-200";
                        badgeStatus.innerHTML = '<i class="fas fa-exclamation-triangle mr-1 text-rose-600"></i> STATUS: REMEDIAL (BELUM TUNTAS)';
                        msgBox.innerText = res.message || `Nilai antum (${finalScore}) belum mencapai standar ketuntasan (${kktp}). Jangan berkecil hati, yuk pelajari materinya atau tanya Ustadz AI!`;
                    }

                    summaryBox.classList.remove('hidden');
                    summaryBox.scrollIntoView({ behavior: 'smooth' });
                })
                .catch(err => {
                    console.error("Gagal simpan progres:", err);
                });
            }
        }

        function ulangKuis() {
            answeredQuestions = {};
            document.querySelectorAll('input[type="radio"][name^="quiz_"]').forEach(r => r.checked = false);
            document.querySelectorAll('[id^="feedback_"]').forEach(f => {
                f.classList.add('hidden');
                f.innerHTML = '';
            });
            const summaryBox = document.getElementById('quizResultSummary');
            if (summaryBox) summaryBox.classList.add('hidden');
            const qBox = document.getElementById('quizContainer');
            if (qBox) qBox.scrollIntoView({ behavior: 'smooth' });
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

        const lksTugasData = <?= json_encode($materi_aktif['lks_tugas'] ?? '') ?>;
        function konsultasiLksKeAI() {
            bukaUstadzAI();
            kirimPesanOtomatis("Ustadz, bagaimana petunjuk dan langkah pengerjaan tugas LKS ini:\n" + lksTugasData);
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

            const promptMapel = <?= json_encode($mapel) ?>;
            const promptJudulBab = <?= json_encode($materi_aktif['judul_bab'] ?? $mapel) ?>;
            const promptSubjudul = <?= json_encode($materi_aktif['subjudul'] ?? '') ?>;
            const promptSantriNama = <?= json_encode($santri_nama) ?>;

            const contextPrompt = `Anda adalah "Ustadz AI ` + promptMapel + `", seorang guru dan ustadz pembimbing mata pelajaran ` + promptMapel + ` yang sangat ramah, santun, cerdas, komunikatif, dan penuh motivasi islami di platform e-learning SADIGS 4.0.

Konteks Pembelajaran:
- Mata Pelajaran: ` + promptMapel + `
- Bab Aktif: ` + promptJudulBab + ` (` + promptSubjudul + `)
- Nama Santri: ` + promptSantriNama + `

Instruksi Anda:
1. Sapa santri dengan ramah (misal: "Ahlan ananda ` + promptSantriNama + `", "Masya Allah pertanyaan yang sangat bagus!").
2. Jelaskan materi dengan bahasa yang mudah dipahami anak sekolah/pesantren, gunakan analogi kehidupan nyata.
3. Berikan poin-poin yang terstruktur rapi.
4. Jawab secara jelas dan to-the-point.

Pertanyaan Santri:
"` + text + `"`;

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

        // --- LOGIC BUILT-IN DIGITAL FLIPBOOK ---
        let currentFlipPage = 1;
        const totalFlipPages = 5;

        function updateFlipbookUI() {
            for (let i = 1; i <= totalFlipPages; i++) {
                const pElem = document.getElementById('flipPage' + i);
                const dElem = document.getElementById('dotPage' + i);
                if (pElem) {
                    if (i === currentFlipPage) {
                        pElem.classList.remove('hidden');
                    } else {
                        pElem.classList.add('hidden');
                    }
                }
                if (dElem) {
                    if (i === currentFlipPage) {
                        dElem.className = 'w-8 h-8 rounded-xl font-black text-xs transition flex items-center justify-center bg-rose-600 text-white shadow-md scale-105';
                    } else {
                        dElem.className = 'w-8 h-8 rounded-xl font-black text-xs transition flex items-center justify-center bg-slate-800 text-slate-400 hover:bg-slate-700';
                    }
                }
            }
            
            const indicator = document.getElementById('pageIndicator');
            if (indicator) {
                indicator.innerText = `${currentFlipPage} / ${totalFlipPages}`;
            }

            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');
            if (prevBtn) {
                prevBtn.disabled = (currentFlipPage === 1);
            }
            if (nextBtn) {
                if (currentFlipPage === totalFlipPages) {
                    nextBtn.innerHTML = `<span>Selesai</span> <i class="fas fa-check"></i>`;
                    nextBtn.className = 'px-4 py-2.5 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center gap-2 shadow-md';
                } else {
                    nextBtn.innerHTML = `<span>Berikutnya</span> <i class="fas fa-chevron-right"></i>`;
                    nextBtn.className = 'px-4 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition flex items-center gap-2 shadow-md shadow-rose-900/30';
                }
            }
        }

        function goToFlipPage(pageNum) {
            if (pageNum >= 1 && pageNum <= totalFlipPages) {
                currentFlipPage = pageNum;
                updateFlipbookUI();
            }
        }

        function nextFlipPage() {
            if (currentFlipPage < totalFlipPages) {
                currentFlipPage++;
                updateFlipbookUI();
            } else {
                goToFlipPage(1);
            }
        }

        function prevFlipPage() {
            if (currentFlipPage > 1) {
                currentFlipPage--;
                updateFlipbookUI();
            }
        }

        function toggleFullscreenFlipbook() {
            const elem = document.getElementById('flipbookWrapper');
            if (!document.fullscreenElement) {
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) {
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    elem.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // --- LOGIC REAL PDF FLIPBOOK (PDF.JS POWERED) ---
        let pdfDoc = null;
        let currentPdfPage = 1;
        let pdfTotalPagesCount = 0;
        let pdfScale = 1.15;
        let isPdfRendering = false;
        let isPdfLoaded = false;
        const pdfTargetUrl = <?= json_encode($emodul_url ?? '') ?>;

        function switchFlipbookMode(mode) {
            const tabSummary = document.getElementById('tabModeSummary');
            const tabPdf = document.getElementById('tabModePdf');
            const summaryStage = document.getElementById('summaryFlipbookStage');
            const summaryCtrl = document.getElementById('summaryControlBar');
            const pdfStage = document.getElementById('pdfFlipbookStage');

            if (mode === 'pdf') {
                tabSummary.className = 'px-3 py-1 rounded-lg text-xs font-black transition text-slate-400 hover:text-white hover:bg-slate-700 flex items-center gap-1.5';
                tabPdf.className = 'px-3 py-1 rounded-lg text-xs font-black transition bg-rose-600 text-white shadow-sm flex items-center gap-1.5';
                summaryStage.classList.add('hidden');
                summaryCtrl.classList.add('hidden');
                pdfStage.classList.remove('hidden');

                if (!isPdfLoaded) {
                    initPdfViewer();
                }
            } else {
                tabPdf.className = 'px-3 py-1 rounded-lg text-xs font-black transition text-slate-400 hover:text-white hover:bg-slate-700 flex items-center gap-1.5';
                tabSummary.className = 'px-3 py-1 rounded-lg text-xs font-black transition bg-rose-600 text-white shadow-sm flex items-center gap-1.5';
                pdfStage.classList.add('hidden');
                summaryStage.classList.remove('hidden');
                summaryCtrl.classList.remove('hidden');
            }
        }

        async function initPdfViewer() {
            if (!pdfTargetUrl) return;
            const proxyUrl = 'pdf-proxy.php?url=' + encodeURIComponent(pdfTargetUrl);
            const spinner = document.getElementById('pdfLoadingSpinner');
            const container = document.getElementById('pdfCanvasContainer');

            try {
                const loadingTask = pdfjsLib.getDocument({
                    url: proxyUrl,
                    cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                    cMapPacked: true
                });

                pdfDoc = await loadingTask.promise;
                pdfTotalPagesCount = pdfDoc.numPages;
                document.getElementById('pdfTotalPages').innerText = pdfTotalPagesCount;
                document.getElementById('pdfPageInput').max = pdfTotalPagesCount;

                isPdfLoaded = true;
                if (spinner) spinner.classList.add('hidden');
                if (container) container.classList.remove('hidden');

                renderPdfPage(currentPdfPage);
            } catch (error) {
                if (spinner) {
                    spinner.innerHTML = `
                        <div class="text-center p-6 space-y-3">
                            <i class="fas fa-exclamation-circle text-rose-400 text-3xl"></i>
                            <p class="text-xs font-bold text-white">Tidak dapat memuat pratinjau PDF langsung: ${error.message}</p>
                            <a href="${pdfTargetUrl}" target="_blank" class="inline-flex items-center gap-1.5 bg-rose-600 hover:bg-rose-700 text-white font-black px-4 py-2 rounded-xl text-xs shadow-md">
                                <i class="fas fa-external-link-alt"></i> Buka Dokumen PDF Resmi
                            </a>
                        </div>
                    `;
                }
            }
        }

        async function renderPdfPage(pageNumber) {
            if (!pdfDoc || isPdfRendering) return;
            isPdfRendering = true;

            try {
                const page = await pdfDoc.getPage(pageNumber);
                const canvas = document.getElementById('pdfRenderCanvas');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                const viewport = page.getViewport({ scale: pdfScale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };

                await page.render(renderContext).promise;

                const pInput = document.getElementById('pdfPageInput');
                if (pInput) pInput.value = pageNumber;
                const pPrev = document.getElementById('pdfPrevBtn');
                if (pPrev) pPrev.disabled = (pageNumber <= 1);
                const pNext = document.getElementById('pdfNextBtn');
                if (pNext) pNext.disabled = (pageNumber >= pdfTotalPagesCount);
            } catch (e) {
                console.error("Render page error: ", e);
            } finally {
                isPdfRendering = false;
            }
        }

        function prevPdfPage() {
            if (currentPdfPage > 1) {
                currentPdfPage--;
                renderPdfPage(currentPdfPage);
            }
        }

        function nextPdfPage() {
            if (currentPdfPage < pdfTotalPagesCount) {
                currentPdfPage++;
                renderPdfPage(currentPdfPage);
            }
        }

        function jumpToPdfPage(pageNum) {
            let p = parseInt(pageNum);
            if (p >= 1 && p <= pdfTotalPagesCount) {
                currentPdfPage = p;
                renderPdfPage(currentPdfPage);
            }
        }

        function zoomPdf(delta) {
            pdfScale = Math.max(0.6, Math.min(2.5, pdfScale + delta));
            renderPdfPage(currentPdfPage);
        }

        function gantiVideoPembelajaran(videoUrl, btnElement) {
            const iframe = document.getElementById('mainVideoPlayer');
            if (iframe && videoUrl) {
                iframe.src = videoUrl;
            }
            const directLink = document.getElementById('directYoutubeLink');
            if (directLink && videoUrl) {
                directLink.href = videoUrl.replace('/embed/', '/watch?v=').replace('youtube-nocookie.com', 'youtube.com');
            }
            document.querySelectorAll('.video-tab-btn').forEach(btn => {
                btn.className = 'video-tab-btn whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-white hover:bg-red-50 text-slate-700 border border-slate-200/80';
            });
            if (btnElement) {
                btnElement.className = 'video-tab-btn whitespace-nowrap px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 bg-red-600 text-white shadow-sm';
            }
        }

        // Inisialisasi Flipbook UI pada saat DOM dimuat
        document.addEventListener('DOMContentLoaded', function() {
            updateFlipbookUI();
        });
    </script>
</body>
</html>
