<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../koneksi.php';

// Pastikan skema tabel tutor_ai_mapel terpasang otomatis (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS tutor_ai_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mapel_nama VARCHAR(150) NOT NULL,
    kategori_mapel VARCHAR(50) DEFAULT 'Diknas',
    tingkat VARCHAR(50) DEFAULT 'SMA',
    fase VARCHAR(50) DEFAULT 'Fase E & F',
    nama_tutor VARCHAR(150) NOT NULL,
    tokoh_rujukan VARCHAR(150) NOT NULL,
    bidang_keahlian VARCHAR(150) NOT NULL,
    deskripsi_singkat TEXT NULL,
    gender ENUM('pria', 'wanita') DEFAULT 'pria',
    suara_pitch DECIMAL(3,2) DEFAULT 0.70,
    suara_rate DECIMAL(3,2) DEFAULT 0.95,
    avatar_url VARCHAR(255) NULL,
    mode_ai ENUM('agentic', 'generator') DEFAULT 'agentic',
    pengampu_id INT NULL,
    sapaan_verbal TEXT NULL,
    status_aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_mapel (mapel_nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Auto-seed model percontohan pertama: Sosiologi SMA (Ustadz Ibnu Khaldun)
$cek_pilot = $conn->query("SELECT id FROM tutor_ai_mapel WHERE mapel_nama = 'Sosiologi' LIMIT 1");
if (!$cek_pilot || $cek_pilot->num_rows === 0) {
    $stmt = $conn->prepare("INSERT INTO tutor_ai_mapel 
        (mapel_nama, kategori_mapel, tingkat, fase, nama_tutor, tokoh_rujukan, bidang_keahlian, deskripsi_singkat, gender, suara_pitch, suara_rate, avatar_url, mode_ai, pengampu_id, sapaan_verbal) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?)");
    $m_nama = 'Sosiologi';
    $k_mapel = 'Diknas';
    $t_tingkat = 'SMA';
    $f_fase = 'Fase E & F';
    $n_tutor = 'Ustadz Ibnu Khaldun';
    $t_rujukan = 'Waliuddin Abdurrahman bin Muhammad Ibnu Khaldun Al-Hadhrami';
    $b_keahlian = 'Bapak Sosiologi & Sejarah Peradaban Dunia';
    $d_singkat = 'Membimbing santri memahami dinamika masyarakat, interaksi sosial, kelompok sosial, dan pembangunan peradaban manusia dengan konsep Ashabiyah (solidaritas sosial) dan nilai-nilai Islam.';
    $gender = 'pria';
    $pitch = 0.70;
    $rate = 0.95;
    $avatar = 'upload/logo-villa-quran.png';
    $mode = 'agentic';
    $sapaan = "Assalamu'alaikum warahmatullahi wabarakatuh. Ahlan wa sahlan! Saya Ustadz Ibnu Khaldun, tutor AI pendamping belajarmu di mata pelajaran Sosiologi SMA. Mari kita pelajari bersama dinamika masyarakat, interaksi sosial, dan rahasia kejayaan peradaban manusia.";
    $stmt->bind_param("ssssssssddssss", $m_nama, $k_mapel, $t_tingkat, $f_fase, $n_tutor, $t_rujukan, $b_keahlian, $d_singkat, $gender, $pitch, $rate, $avatar, $mode, $sapaan);
    $stmt->execute();
    $stmt->close();
}

// Handler AJAX: Update Mode AI (Agentic <-> Generator)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'toggle_mode') {
        $id = (int)($_POST['id'] ?? 0);
        $new_mode = ($_POST['mode'] === 'generator') ? 'generator' : 'agentic';
        $stmt_u = $conn->prepare("UPDATE tutor_ai_mapel SET mode_ai = ? WHERE id = ?");
        $stmt_u->bind_param("si", $new_mode, $id);
        $ok = $stmt_u->execute();
        $stmt_u->close();
        echo json_encode(['status' => $ok ? 'success' : 'error', 'mode' => $new_mode]);
        exit;
    }
    if ($_POST['action'] === 'update_detail') {
        $id = (int)($_POST['id'] ?? 0);
        $sapaan = trim($_POST['sapaan'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $stmt_u = $conn->prepare("UPDATE tutor_ai_mapel SET sapaan_verbal = ?, deskripsi_singkat = ? WHERE id = ?");
        $stmt_u->bind_param("ssi", $sapaan, $deskripsi, $id);
        $ok = $stmt_u->execute();
        $stmt_u->close();
        echo json_encode(['status' => $ok ? 'success' : 'error']);
        exit;
    }
}

$active_menu = 'team_pengajar_ai';

// Ambil data tutor aktif
$res_tutors = $conn->query("SELECT * FROM tutor_ai_mapel ORDER BY id ASC");
$pilot_tutor = ($res_tutors && $res_tutors->num_rows > 0) ? $res_tutors->fetch_assoc() : null;

// Road map 13 mapel Diknas berikutnya
$roadmap_mapel = [
    ['mapel' => 'Matematika', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadz Al-Khawarizmi', 'tokoh' => 'Bapak Aljabar & Algoritma', 'gender' => 'pria', 'icon' => 'fa-calculator', 'bg' => 'from-blue-600 to-indigo-800'],
    ['mapel' => 'Informatika', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadz Al-Jazari', 'tokoh' => 'Bapak Robotika & Mekanika Otomasi', 'gender' => 'pria', 'icon' => 'fa-laptop-code', 'bg' => 'from-cyan-600 to-teal-800'],
    ['mapel' => 'Fisika', 'tingkat' => 'SMA', 'tutor' => 'Ustadz Ibnu Al-Haitsam', 'tokoh' => 'Bapak Optika Modern (Alhazen)', 'gender' => 'pria', 'icon' => 'fa-atom', 'bg' => 'from-violet-600 to-purple-800'],
    ['mapel' => 'Kimia', 'tingkat' => 'SMA', 'tutor' => 'Ustadz Jabir Ibnu Hayyan', 'tokoh' => 'Bapak Kimia Modern (Geber)', 'gender' => 'pria', 'icon' => 'fa-flask-vial', 'bg' => 'from-rose-600 to-pink-800'],
    ['mapel' => 'Biologi / IPA', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadzah Rufaidah Al-Aslamiyah', 'tokoh' => 'Pelopor Medis & Sains Hayati', 'gender' => 'wanita', 'icon' => 'fa-dna', 'bg' => 'from-emerald-600 to-teal-800'],
    ['mapel' => 'Geografi', 'tingkat' => 'SMA', 'tutor' => 'Ustadzah Maryam Al-Asturlabiya', 'tokoh' => 'Pakar Astrolab & Astronomi', 'gender' => 'wanita', 'icon' => 'fa-earth-asia', 'bg' => 'from-teal-600 to-emerald-800'],
    ['mapel' => 'Bahasa Indonesia', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadz Hamzah Fansuri', 'tokoh' => 'Pelopor Sastra & Bahasa Melayu', 'gender' => 'pria', 'icon' => 'fa-book', 'bg' => 'from-amber-600 to-orange-800'],
    ['mapel' => 'Bahasa Inggris', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadzah Fatimah Al-Fihri', 'tokoh' => 'Pendiri Univ. Al-Qarawiyyin', 'gender' => 'wanita', 'icon' => 'fa-language', 'bg' => 'from-sky-600 to-blue-800'],
    ['mapel' => 'Ekonomi', 'tingkat' => 'SMA', 'tutor' => 'Ustadzah Asy-Syifa binti Abdullah', 'tokoh' => 'Pengawas Pasar & Niaga Madinah', 'gender' => 'wanita', 'icon' => 'fa-chart-pie', 'bg' => 'from-lime-600 to-emerald-800'],
    ['mapel' => 'Pendidikan Pancasila', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadz Al-Mawardi', 'tokoh' => 'Pakar Konstitusi & Tata Negara', 'gender' => 'pria', 'icon' => 'fa-landmark', 'bg' => 'from-red-600 to-rose-800'],
    ['mapel' => 'Sejarah', 'tingkat' => 'SMA', 'tutor' => 'Ustadz Ibnu Battuta', 'tokoh' => 'Penjelajah Dunia & Wawasan Bangsa', 'gender' => 'pria', 'icon' => 'fa-monument', 'bg' => 'from-yellow-600 to-amber-800'],
    ['mapel' => 'Seni Budaya', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadz Al-Farabi', 'tokoh' => 'Filsuf & Teori Musik-Seni', 'gender' => 'pria', 'icon' => 'fa-palette', 'bg' => 'from-fuchsia-600 to-purple-800'],
    ['mapel' => 'PJOK', 'tingkat' => 'SMP & SMA', 'tutor' => 'Ustadz Ibnu Qayyim', 'tokoh' => 'Pakar Thibbun Nabawi & Jasmani', 'gender' => 'pria', 'icon' => 'fa-person-running', 'bg' => 'from-orange-600 to-red-800']
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Pengajar AI Diknas • Dewan Tutor Ilmuwan Muslim</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-xs flex items-center justify-between px-4 sm:px-6 z-20 flex-shrink-0 border-b border-slate-200">
            <div class="flex items-center gap-3">
                <button id="open-sidebar-yayasan2" class="text-slate-600 hover:text-[#0b8478] md:hidden p-1 rounded-lg">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        <h2 class="font-black text-slate-900 text-sm sm:text-base tracking-tight leading-tight">
                            Team Pengajar AI Diknas (Dewan Tutor Ilmuwan Muslim)
                        </h2>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-900 border border-amber-300">
                            <i class="fas fa-crown text-amber-500 mr-1 text-[9px]"></i> Khusus Yayasan & Kepala Sekolah
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-500 hidden sm:block">Solusi Cerdas Krisis Guru SMA IPS & Co-Pilot Guru Berbasis Karakter Ilmuwan Emas Islam</p>
                </div>
            </div>

            <!-- TOP ACTIONS -->
            <div class="flex items-center gap-2">
                <a href="../dashboard.php" class="px-3.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 transition flex items-center gap-1.5">
                    <i class="fas fa-house"></i>
                    <span class="hidden sm:inline">Dashboard Utama</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">

            <!-- BANNER SOLUSI KRISIS GURU SMA IPS -->
            <div class="relative overflow-hidden bg-gradient-to-r from-[#0b8478] via-[#097368] to-slate-900 text-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-teal-950/10 border border-teal-600/30">
                <div class="absolute -right-8 -bottom-8 text-white/5 text-9xl pointer-events-none">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
                <div class="relative z-10 max-w-3xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400 text-slate-950 text-xs font-black uppercase tracking-wider mb-3 shadow-md">
                        <i class="fas fa-shield-halved"></i> Krisis Guru SMA IPS Teratasi
                    </div>
                    <h1 class="text-xl sm:text-2xl lg:text-3xl font-black text-white tracking-tight leading-snug">
                        Model Percontohan Pertama: Sosiologi SMA Diampu Mandiri oleh Ustadz Ibnu Khaldun
                    </h1>
                    <p class="text-xs sm:text-sm text-teal-100/90 font-medium mt-2 leading-relaxed">
                        Mata pelajaran Sosiologi SMA (Fase E & F) kini memiliki penanggung jawab otonom. Tidak ada lagi jam kosong bagi santri SMA IPS. Tutor AI bekerja mandiri merumuskan Tujuan Pembelajaran, menyiapkan Modul Ajar, dan siap berdiskusi secara teks maupun verbal dengan santri.
                    </p>
                </div>
            </div>

            <!-- PILOT MODEL SPOTLIGHT: USTADZ IBNU KHALDUN (SOSIOLOGI SMA) -->
            <?php if ($pilot_tutor): ?>
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-900/5 border border-slate-200 relative overflow-hidden">
                <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 pb-6 border-b border-slate-100">
                    
                    <!-- KIRI: AVATAR & PROFIL TUTOR -->
                    <div class="flex items-start sm:items-center gap-4 sm:gap-6">
                        <div class="relative">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-3xl bg-gradient-to-br from-amber-500 via-amber-600 to-teal-800 p-1 shadow-lg shadow-amber-600/20 flex-shrink-0">
                                <div class="w-full h-full bg-slate-900 rounded-[20px] flex items-center justify-center text-3xl sm:text-4xl text-amber-300 overflow-hidden">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                            <span class="absolute -bottom-2 -right-2 px-2 py-0.5 rounded-full text-[9px] font-black bg-emerald-500 text-white shadow-md uppercase tracking-wider flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-white animate-ping"></span> Live Aktif
                            </span>
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-teal-100 text-teal-900 border border-teal-200 uppercase tracking-wider">
                                    Mata Pelajaran <?= htmlspecialchars($pilot_tutor['mapel_nama']) ?> (<?= htmlspecialchars($pilot_tutor['tingkat']) ?>)
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-900 border border-purple-200">
                                    <?= htmlspecialchars($pilot_tutor['fase']) ?>
                                </span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">
                                <?= htmlspecialchars($pilot_tutor['nama_tutor']) ?>
                            </h3>
                            <p class="text-xs sm:text-sm font-bold text-amber-700 mt-0.5">
                                <?= htmlspecialchars($pilot_tutor['bidang_keahlian']) ?>
                            </p>
                            <p class="text-[11px] text-slate-400 font-medium italic mt-0.5">
                                Rujukan: <?= htmlspecialchars($pilot_tutor['tokoh_rujukan']) ?>
                            </p>
                        </div>
                    </div>

                    <!-- KANAN: STATUS GURU FISIK & SWITCH MODE AI -->
                    <div class="w-full lg:w-auto bg-slate-50 p-4 rounded-2xl border border-slate-200/80 flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                        <div class="text-left sm:text-right">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Guru Fisik:</span>
                            <span class="inline-flex items-center gap-1.5 text-xs font-black text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 mt-1">
                                <i class="fas fa-circle-exclamation text-rose-500"></i> Belum Ada Guru (Krisis SMA IPS)
                            </span>
                        </div>

                        <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                        <div class="flex flex-col">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Mode Kerja AI:</span>
                            <div class="inline-flex rounded-xl p-1 bg-slate-200/80">
                                <button type="button" 
                                        id="btn-mode-agentic"
                                        onclick="ubahModeAi(<?= $pilot_tutor['id'] ?>, 'agentic')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 <?= $pilot_tutor['mode_ai'] === 'agentic' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                                    <i class="fas fa-robot"></i> 🟢 Full Agentic (Otonom)
                                </button>
                                <button type="button" 
                                        id="btn-mode-generator"
                                        onclick="ubahModeAi(<?= $pilot_tutor['id'] ?>, 'generator')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 <?= $pilot_tutor['mode_ai'] === 'generator' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                                    <i class="fas fa-wand-magic-sparkles"></i> 🟡 Generator (Co-Pilot)
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DESKRIPSI & FITUR SAPAAN VERBAL -->
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 space-y-4">
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 mb-1.5">Fokus Keilmuan & Bimbingan Santri:</h4>
                            <p class="text-xs sm:text-sm text-slate-700 leading-relaxed font-medium bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <?= nl2br(htmlspecialchars($pilot_tutor['deskripsi_singkat'])) ?>
                            </p>
                        </div>

                        <!-- KOTAK SAPAAN VERBAL -->
                        <div class="bg-amber-50/70 p-4 rounded-2xl border border-amber-200/80">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black text-amber-900 flex items-center gap-1.5">
                                    <i class="fas fa-quote-left text-amber-500"></i> Teks Sapaan Verbal Pertama ke Santri
                                </span>
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-100/80 px-2 py-0.5 rounded-md">
                                    Pitch: <?= $pilot_tutor['suara_pitch'] ?> (Bariton Pria) • Rate: <?= $pilot_tutor['suara_rate'] ?>
                                </span>
                            </div>
                            <p id="sapaan-teks" class="text-xs text-slate-800 italic leading-relaxed">
                                "<?= htmlspecialchars($pilot_tutor['sapaan_verbal']) ?>"
                            </p>
                        </div>
                    </div>

                    <!-- TOMBOL AKSI & UJI COBA SUARA VERBAL -->
                    <div class="bg-gradient-to-br from-slate-900 to-teal-950 text-white p-5 rounded-2xl flex flex-col justify-between space-y-4 shadow-lg">
                        <div>
                            <span class="text-[10px] font-black text-amber-400 uppercase tracking-wider block mb-1">Simulasi Verbal Suara AI:</span>
                            <h5 class="text-sm font-bold text-white">Uji Coba Suara Ustadz Ibnu Khaldun</h5>
                            <p class="text-[11px] text-teal-200/80 font-medium mt-1 leading-snug">
                                Suara otomatis disesuaikan dengan nada bariton khas pria, sehingga tetap bersuara pria berwibawa di semua HP santri.
                            </p>
                        </div>

                        <!-- TOMBOL SUARA VERBAL INTERAKTIF -->
                        <button type="button" 
                                id="btn-play-voice"
                                onclick="putarSuaraUstadz()" 
                                class="w-full py-3 px-4 rounded-xl font-black text-xs bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-md active:scale-95 transition flex items-center justify-center gap-2">
                            <i class="fas fa-volume-high text-sm"></i>
                            <span id="btn-voice-label">🔊 Dengarkan Sapaan Ustadz Ibnu Khaldun</span>
                        </button>

                        <div class="pt-3 border-t border-teal-800/60 grid grid-cols-2 gap-2 text-center text-xs">
                            <a href="../santri-belajar.php?mapel=Sosiologi" target="_blank" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold transition flex items-center justify-center gap-1.5 text-[11px]">
                                <i class="fas fa-graduation-cap"></i> Ruang Santri
                            </a>
                            <a href="../admin-pegawai-rpp.php?mapel=Sosiologi" target="_blank" class="p-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold transition flex items-center justify-center gap-1.5 text-[11px]">
                                <i class="fas fa-book-bookmark"></i> Modul Ajar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- ROADMAP 13 MAPEL DIKNAS BERIKUTNYA -->
            <div class="mt-8">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <i class="fas fa-map-location-dot text-[#0b8478]"></i>
                            Peta Jalan Dewan Tutor AI Diknas (13 Mapel Berikutnya)
                        </h3>
                        <p class="text-xs text-slate-500 font-medium">Setelah model percontohan Sosiologi selesai, dewan tutor ini siap direplikasi ke seluruh mata pelajaran</p>
                    </div>
                    <span class="text-xs font-bold px-3 py-1 rounded-xl bg-slate-200/80 text-slate-700">
                        1 Model Live • 13 Siap Replikasi
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    <?php foreach ($roadmap_mapel as $rm): ?>
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-xs hover:shadow-md transition flex flex-col justify-between group">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-2 py-0.5 rounded-md text-[9px] font-black bg-slate-100 text-slate-600 uppercase">
                                    <?= htmlspecialchars($rm['tingkat']) ?>
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    Tahap 2
                                </span>
                            </div>

                            <div class="flex items-center gap-3 my-2">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br <?= $rm['bg'] ?> text-white flex items-center justify-center text-lg shadow-sm flex-shrink-0">
                                    <i class="fas <?= $rm['icon'] ?>"></i>
                                </div>
                                <div>
                                    <h4 class="font-extrabold text-xs text-slate-800 group-hover:text-[#0b8478] transition-colors leading-tight">
                                        <?= htmlspecialchars($rm['mapel']) ?>
                                    </h4>
                                    <p class="text-[11px] font-bold text-amber-700 mt-0.5 leading-tight">
                                        <?= htmlspecialchars($rm['tutor']) ?>
                                    </p>
                                </div>
                            </div>

                            <p class="text-[10px] text-slate-400 font-medium italic line-clamp-1 mt-1">
                                <?= htmlspecialchars($rm['tokoh']) ?>
                            </p>
                        </div>

                        <div class="mt-3 pt-2.5 border-t border-slate-100 flex items-center justify-between text-[10px] font-bold text-slate-400">
                            <span>Gender: <?= $rm['gender'] === 'wanita' ? '👩 Ustadzah' : '👨 Ustadz' ?></span>
                            <span class="text-teal-600 font-extrabold">Siap Diaktifkan</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </main>
    </div>

    <!-- JAVASCRIPT: UJI COBA SUARA TTS BER-PITCH BARITON & TOGGLE MODE -->
    <script>
        let isSpeaking = false;

        function putarSuaraUstadz() {
            if (!('speechSynthesis' in window)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Browser Tidak Mendukung TTS',
                    text: 'Fitur Text-to-Speech tidak didukung oleh browser ini. Silakan gunakan Google Chrome, Edge, atau Safari.',
                    confirmButtonColor: '#0b8478'
                });
                return;
            }

            const btn = document.getElementById('btn-play-voice');
            const lbl = document.getElementById('btn-voice-label');

            if (isSpeaking) {
                window.speechSynthesis.cancel();
                isSpeaking = false;
                lbl.innerText = '🔊 Dengarkan Sapaan Ustadz Ibnu Khaldun';
                btn.className = 'w-full py-3 px-4 rounded-xl font-black text-xs bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-md active:scale-95 transition flex items-center justify-center gap-2';
                return;
            }

            const teks = <?= json_encode($pilot_tutor['sapaan_verbal'] ?? '') ?>;
            const pitch = <?= (float)($pilot_tutor['suara_pitch'] ?? 0.70) ?>;
            const rate = <?= (float)($pilot_tutor['suara_rate'] ?? 0.95) ?>;

            const utterance = new SpeechSynthesisUtterance(teks);
            utterance.lang = 'id-ID';
            utterance.pitch = pitch; // Mengubah nada suara jadi bariton pria
            utterance.rate = rate;

            // Cari suara id-ID jika tersedia di sistem
            const voices = window.speechSynthesis.getVoices();
            const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
            if (idVoice) {
                utterance.voice = idVoice;
            }

            utterance.onstart = function() {
                isSpeaking = true;
                lbl.innerText = '⏹️ Hentikan Suara Ustadz';
                btn.className = 'w-full py-3 px-4 rounded-xl font-black text-xs bg-rose-600 hover:bg-rose-500 text-white shadow-md active:scale-95 transition flex items-center justify-center gap-2 animate-pulse';
            };

            utterance.onend = function() {
                isSpeaking = false;
                lbl.innerText = '🔊 Dengarkan Sapaan Ustadz Ibnu Khaldun';
                btn.className = 'w-full py-3 px-4 rounded-xl font-black text-xs bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-md active:scale-95 transition flex items-center justify-center gap-2';
            };

            utterance.onerror = function(e) {
                isSpeaking = false;
                lbl.innerText = '🔊 Dengarkan Sapaan Ustadz Ibnu Khaldun';
                btn.className = 'w-full py-3 px-4 rounded-xl font-black text-xs bg-amber-400 hover:bg-amber-300 text-slate-950 shadow-md active:scale-95 transition flex items-center justify-center gap-2';
            };

            window.speechSynthesis.speak(utterance);
        }

        // Toggle Mode AI via AJAX
        function ubahModeAi(id, mode) {
            const formData = new FormData();
            formData.append('action', 'toggle_mode');
            formData.append('id', id);
            formData.append('mode', mode);

            fetch('team-pengajar-ai.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    const btnAg = document.getElementById('btn-mode-agentic');
                    const btnGen = document.getElementById('btn-mode-generator');

                    if (mode === 'agentic') {
                        btnAg.className = 'px-3 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 bg-emerald-600 text-white shadow-sm';
                        btnGen.className = 'px-3 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 text-slate-600 hover:text-slate-900';
                    } else {
                        btnGen.className = 'px-3 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 bg-amber-600 text-white shadow-sm';
                        btnAg.className = 'px-3 py-1.5 rounded-lg text-xs font-black transition flex items-center gap-1.5 text-slate-600 hover:text-slate-900';
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Mode AI Diperbarui',
                        text: 'Sosiologi SMA sekarang beroperasi dalam ' + (mode === 'agentic' ? 'Mode Full Agentic (Otonom)' : 'Mode Generator (Co-Pilot Asisten Guru)'),
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            })
            .catch(err => console.error(err));
        }

        // Pastikan daftar suara termuat sempurna di Chrome/Safari
        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = function() {
                window.speechSynthesis.getVoices();
            };
        }
    </script>
</body>
</html>
