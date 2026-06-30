<?php
session_start();
$is_authorized = false;
$user_role = '';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $is_authorized = true;
    $user_role = 'admin';
} elseif (isset($_SESSION['ustadz_logged_in']) && $_SESSION['ustadz_logged_in'] === true) {
    $is_authorized = true;
    $user_role = 'ustadz';
}

if (!$is_authorized) {
    header("Location: login-ustadz.php");
    exit;
}

require_once 'koneksi.php';

// Self-healing: Table counseling_karir
$conn->query("CREATE TABLE IF NOT EXISTS counseling_karir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT UNIQUE NOT NULL,
    target_ptn_1 VARCHAR(150),
    target_jurusan_1 VARCHAR(150),
    target_ptn_2 VARCHAR(150),
    target_jurusan_2 VARCHAR(150),
    jalur_pilihan ENUM('SNBP', 'SNBT', 'SPAN-PTKIN', 'Prestasi', 'Mandiri') DEFAULT 'SNBP',
    minat_bakat TEXT,
    catatan_konselor TEXT,
    analisa_ai TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE
)");

// --- CRUD OPERATIONS via AJAX ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save_profile') {
        $santri_id = (int)$_POST['santri_id'];
        $target_ptn_1 = $conn->real_escape_string($_POST['target_ptn_1']);
        $target_jurusan_1 = $conn->real_escape_string($_POST['target_jurusan_1']);
        $target_ptn_2 = $conn->real_escape_string($_POST['target_ptn_2']);
        $target_jurusan_2 = $conn->real_escape_string($_POST['target_jurusan_2']);
        $jalur_pilihan = $conn->real_escape_string($_POST['jalur_pilihan']);
        $minat_bakat = $conn->real_escape_string($_POST['minat_bakat']);
        $catatan_konselor = $conn->real_escape_string($_POST['catatan_konselor']);
        
        $sql = "INSERT INTO counseling_karir (santri_id, target_ptn_1, target_jurusan_1, target_ptn_2, target_jurusan_2, jalur_pilihan, minat_bakat, catatan_konselor)
                VALUES ($santri_id, '$target_ptn_1', '$target_jurusan_1', '$target_ptn_2', '$target_jurusan_2', '$jalur_pilihan', '$minat_bakat', '$catatan_konselor')
                ON DUPLICATE KEY UPDATE 
                    target_ptn_1 = '$target_ptn_1',
                    target_jurusan_1 = '$target_jurusan_1',
                    target_ptn_2 = '$target_ptn_2',
                    target_jurusan_2 = '$target_jurusan_2',
                    jalur_pilihan = '$jalur_pilihan',
                    minat_bakat = '$minat_bakat',
                    catatan_konselor = '$catatan_konselor'";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Profil karir berhasil disimpan.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $conn->error]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'get_ai_analysis') {
        $santri_id = (int)$_POST['santri_id'];
        
        // Load API Keys
        if (file_exists('config-key.php')) {
            require_once 'config-key.php';
        }
        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        $gasUrl = defined('GEMINI_GAS_URL') ? GEMINI_GAS_URL : '';
        
        if (empty($apiKey)) {
            echo json_encode(['status' => 'error', 'message' => 'API Key Gemini belum dikonfigurasi.']);
            exit;
        }
        
        // Ambil Data Santri
        $res_s = $conn->query("SELECT nama_lengkap, kelas_sekarang, asal_sekolah FROM buku_induk_santri WHERE id = $santri_id");
        $santri = $res_s->fetch_assoc();
        if (!$santri) {
            echo json_encode(['status' => 'error', 'message' => 'Santri tidak ditemukan.']);
            exit;
        }
        
        // Ambil Data Konseling
        $res_c = $conn->query("SELECT * FROM counseling_karir WHERE santri_id = $santri_id");
        $counseling = $res_c->fetch_assoc() ?? [];
        
        // Ambil Riwayat Nilai Leger
        $grades = [];
        $res_g = $conn->query("SELECT l.kelas, l.tahun_ajaran, l.semester, l.jenis_ujian, m.nama_mapel, m.kategori_mapel, l.nilai
                               FROM leger_nilai l
                               JOIN master_mapel m ON l.mapel_id = m.id
                               WHERE l.santri_id = $santri_id
                               ORDER BY l.tahun_ajaran ASC, l.semester ASC, m.nama_mapel ASC");
        if ($res_g) {
            while ($row = $res_g->fetch_assoc()) {
                $grades[] = $row;
            }
        }
        
        // Format prompt untuk Gemini
        $prompt = "Anda adalah Konselor AI Karir & Pendidikan Tinggi. Tugas Anda adalah membantu **Musyrif** (asrama mentor/wali asuh asrama yang menggantikan peran orang tua di pondok pesantren) untuk melakukan pemetaan kelayakan masuk Perguruan Tinggi Negeri (PTN) / PTKIN bagi santri berikut, terinspirasi oleh sukses bimbingan 100% PTN SMAN 14 Bandar Lampung.\n\n";
        $prompt .= "--- PROFIL SANTRI ---\n";
        $prompt .= "Nama Lengkap: " . $santri['nama_lengkap'] . "\n";
        $prompt .= "Kelas Saat Ini: " . $santri['kelas_sekarang'] . "\n";
        $prompt .= "Sekolah Asal: " . $santri['asal_sekolah'] . "\n";
        $prompt .= "Minat & Bakat (Hobi): " . ($counseling['minat_bakat'] ?? 'Belum diisi') . "\n";
        $prompt .= "Catatan Karakter & Akademik Musyrif: " . ($counseling['catatan_konselor'] ?? 'Tidak ada') . "\n\n";
        
        $prompt .= "--- TARGET PTN & JURUSAN ---\n";
        $prompt .= "Jalur Pilihan: " . ($counseling['jalur_pilihan'] ?? 'SNBP') . "\n";
        $prompt .= "Target Pilihan 1: " . ($counseling['target_jurusan_1'] ?? 'Belum ditentukan') . " - " . ($counseling['target_ptn_1'] ?? 'Belum ditentukan') . "\n";
        $prompt .= "Target Pilihan 2: " . ($counseling['target_jurusan_2'] ?? 'Belum ditentukan') . " - " . ($counseling['target_ptn_2'] ?? 'Belum ditentukan') . "\n\n";
        
        $prompt .= "--- DATA NILAI LEGER AKADEMIK ---\n";
        if (empty($grades)) {
            $prompt .= "Siswa belum memiliki data nilai Leger di database.\n";
        } else {
            $prompt .= "| Kelas | Tahun Ajaran | Semester | Ujian | Mata Pelajaran | Kategori | Nilai |\n";
            $prompt .= "|---|---|---|---|---|---|---|\n";
            foreach ($grades as $g) {
                $prompt .= "| {$g['kelas']} | {$g['tahun_ajaran']} | {$g['semester']} | {$g['jenis_ujian']} | {$g['nama_mapel']} | {$g['kategori_mapel']} | {$g['nilai']} |\n";
            }
        }
        
        $prompt .= "\n--- INSTRUKSI ANALISIS ---\n";
        $prompt .= "Lakukan analisis akademis dan berikan laporan dalam format Markdown yang mencakup:\n";
        $prompt .= "1. **Analisis Tren & Kekuatan Nilai**: Jelaskan tren akademis siswa (meningkat/menurun), dan sorot kekuatan mata pelajaran utama (bedakan antara kurikulum Diknas vs Diniyah/Agama).\n";
        $prompt .= "2. **Kelayakan Realistis Pilihan PTN**: Berikan estimasi kelayakan yang realistis untuk Pilihan 1 dan Pilihan 2 berdasarkan nilai rapor yang ada (terutama jika memilih jalur seleksi rapor SNBP).\n";
        $prompt .= "3. **Rekomendasi Alternatif (Jurusan & Kampus)**: Tuliskan rekomendasi jurusan & PTN/PTKIN alternatif yang dinilai lebih aman atau cocok dengan nilai & minat bakatnya, terutama jika pilihan awal terlalu berisiko.\n";
        $prompt .= "4. **Rencana Aksi Strategis (Class 10-12 Prep)**: Berikan langkah konkret untuk meningkatkan peluang kelulusan (misalnya fokus peningkatan nilai mapel tertentu, persiapan tryout UTBK-SNBT, atau penyusunan portofolio prestasi).\n\n";
        $prompt .= "Sajikan laporan dengan bahasa yang memotivasi, profesional, dan mudah dipahami oleh Musyrif, santri, maupun orang tua.";
        
        // Kirim request ke Gemini
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        $useTunnel = !empty($gasUrl);
        $ch = curl_init();
        
        if ($useTunnel) {
            curl_setopt($ch, CURLOPT_URL, $gasUrl);
            $payload = ["prompt" => $prompt, "apiKey" => $apiKey];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            $payload = [
                "contents" => [
                    ["parts" => [["text" => $prompt]]]
                ]
            ];
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            echo json_encode(['status' => 'error', 'message' => 'Koneksi ke AI gagal: ' . $curlError]);
            exit;
        }
        
        $ai_text = '';
        if ($useTunnel) {
            $res_json = json_decode($response, true);
            if (isset($res_json['status']) && $res_json['status'] === 'success') {
                $ai_text = $res_json['result'];
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tunnel Error: ' . ($res_json['message'] ?? $response)]);
                exit;
            }
        } else {
            if ($httpCode !== 200) {
                echo json_encode(['status' => 'error', 'message' => 'Gemini API Error (HTTP ' . $httpCode . '): ' . $response]);
                exit;
            }
            $result = json_decode($response, true);
            $ai_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
        
        if (empty($ai_text)) {
            echo json_encode(['status' => 'error', 'message' => 'Hasil analisis AI tidak dapat digenerate.']);
            exit;
        }
        
        // Simpan Hasil Analisis ke DB
        $ai_text_esc = $conn->real_escape_string($ai_text);
        $conn->query("INSERT INTO counseling_karir (santri_id, analisa_ai) VALUES ($santri_id, '$ai_text_esc')
                      ON DUPLICATE KEY UPDATE analisa_ai = '$ai_text_esc'");
        
        echo json_encode(['status' => 'success', 'result' => $ai_text]);
        exit;
    }
}

// --- PREPARE FRONTEND DATA ---
$active_menu = 'counseling_karir';

// 1. Dashboard Stats
$total_santri = $conn->query("SELECT COUNT(id) as total FROM buku_induk_santri WHERE status_santri = 'Aktif'")->fetch_assoc()['total'] ?? 0;
$total_counseled = $conn->query("SELECT COUNT(DISTINCT santri_id) as total FROM counseling_karir")->fetch_assoc()['total'] ?? 0;
$avg_grade_school = $conn->query("SELECT AVG(nilai) as avg FROM leger_nilai")->fetch_assoc()['avg'] ?? 0;
$avg_grade_school = round($avg_grade_school, 2);

// Jalur Terpopuler
$popular_path = $conn->query("SELECT jalur_pilihan, COUNT(*) as cnt FROM counseling_karir GROUP BY jalur_pilihan ORDER BY cnt DESC LIMIT 1")->fetch_assoc();
$popular_path_name = $popular_path['jalur_pilihan'] ?? 'Belum ada';

// 2. Ambil List Santri & Filter
$filter_kelas = $_GET['kelas'] ?? '';
$search_query = $_GET['search'] ?? '';

$sql_list = "SELECT s.id, s.nama_lengkap, s.kelas_sekarang, c.target_ptn_1, c.target_jurusan_1, c.jalur_pilihan,
                    (SELECT AVG(nilai) FROM leger_nilai WHERE santri_id = s.id) as rata_rata
             FROM buku_induk_santri s
             LEFT JOIN counseling_karir c ON s.id = c.santri_id
             WHERE s.status_santri = 'Aktif'";

if (!empty($filter_kelas)) {
    $sql_list .= " AND s.kelas_sekarang = '" . $conn->real_escape_string($filter_kelas) . "'";
}
if (!empty($search_query)) {
    $sql_list .= " AND s.nama_lengkap LIKE '%" . $conn->real_escape_string($search_query) . "%'";
}
$sql_list .= " ORDER BY s.kelas_sekarang DESC, s.nama_lengkap ASC";
$santri_list_res = $conn->query($sql_list);

$active_santri_id = isset($_GET['santri_id']) ? (int)$_GET['santri_id'] : 0;
$active_santri = null;
$active_counseling = null;
$active_grades = [];
$diknas_avg = 0;
$diniyah_avg = 0;

if ($active_santri_id > 0) {
    // Info Santri Aktif
    $res_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $active_santri_id");
    $active_santri = $res_s->fetch_assoc();
    
    if ($active_santri) {
        // Info Counseling
        $res_c = $conn->query("SELECT * FROM counseling_karir WHERE santri_id = $active_santri_id");
        $active_counseling = $res_c->fetch_assoc();
        
        // Info Nilai & Pengelompokan Kategori
        $res_g = $conn->query("SELECT l.kelas, l.tahun_ajaran, l.semester, l.jenis_ujian, m.nama_mapel, m.kategori_mapel, l.nilai
                               FROM leger_nilai l
                               JOIN master_mapel m ON l.mapel_id = m.id
                               WHERE l.santri_id = $active_santri_id
                               ORDER BY l.tahun_ajaran DESC, l.semester DESC, m.kategori_mapel ASC, m.nama_mapel ASC");
        if ($res_g) {
            $total_diknas = 0; $count_diknas = 0;
            $total_diniyah = 0; $count_diniyah = 0;
            
            while ($row = $res_g->fetch_assoc()) {
                $active_grades[] = $row;
                if ($row['kategori_mapel'] === 'Diknas') {
                    $total_diknas += $row['nilai'];
                    $count_diknas++;
                } elseif ($row['kategori_mapel'] === 'Diniyah') {
                    $total_diniyah += $row['nilai'];
                    $count_diniyah++;
                }
            }
            $diknas_avg = $count_diknas > 0 ? round($total_diknas / $count_diknas, 2) : 0;
            $diniyah_avg = $count_diniyah > 0 ? round($total_diniyah / $count_diniyah, 2) : 0;
        }
    }
}

// Ambil semua daftar opsi kelas untuk filter
$opsi_kelas_res = $conn->query("SELECT DISTINCT kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY kelas_sekarang ASC");
$daftar_kelas = [];
if ($opsi_kelas_res) {
    while ($row = $opsi_kelas_res->fetch_assoc()) {
        if (!empty($row['kelas_sekarang'])) $daftar_kelas[] = $row['kelas_sekarang'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Career Prep & College Mapping | Villa Quran</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Markdown Parser Library for AI output -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
        }
        .ai-glow {
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.15);
            border: 1px solid rgba(6, 182, 212, 0.3);
        }
        .glow-active {
            box-shadow: 0 0 25px rgba(6, 182, 212, 0.3);
            border: 1px solid rgba(6, 182, 212, 0.5);
        }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 border-b border-slate-200">
            <div class="flex items-center space-x-3">
                <i class="fas fa-graduation-cap text-2xl text-cyan-600"></i>
                <h2 class="font-bold text-slate-800 text-lg">AI Counseling Karir & Pemetaan PTN/PTKIN</h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-xs font-semibold px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full">
                    Model: SMAN 14 Bandar Lampung 100% Sukses PTN
                </span>
            </div>
        </header>

        <!-- MAIN LAYOUT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-6">
            
            <!-- 1. TOP ANALYTICS DASHBOARD -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- Stat 1 -->
                <div class="glass-card p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition duration-300">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Santri Aktif</p>
                        <h3 class="text-2xl font-extrabold text-slate-700 mt-1"><?= $total_santri ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 flex items-center justify-center text-cyan-600">
                        <i class="fas fa-user-friends text-xl"></i>
                    </div>
                </div>
                <!-- Stat 2 -->
                <div class="glass-card p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition duration-300">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Terpetakan Karir</p>
                        <h3 class="text-2xl font-extrabold text-slate-700 mt-1">
                            <?= $total_counseled ?> 
                            <span class="text-xs font-medium text-emerald-600 ml-1">(<?= $total_santri > 0 ? round(($total_counseled / $total_santri)*100, 1) : 0 ?>%)</span>
                        </h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                        <i class="fas fa-route text-xl"></i>
                    </div>
                </div>
                <!-- Stat 3 -->
                <div class="glass-card p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition duration-300">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rata-rata Nilai Rapor</p>
                        <h3 class="text-2xl font-extrabold text-slate-700 mt-1"><?= $avg_grade_school ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                        <i class="fas fa-star text-xl"></i>
                    </div>
                </div>
                <!-- Stat 4 -->
                <div class="glass-card p-5 rounded-2xl shadow-sm flex items-center justify-between hover:shadow-md transition duration-300">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Jalur Terpopuler</p>
                        <h3 class="text-2xl font-extrabold text-slate-700 mt-1"><?= htmlspecialchars($popular_path_name) ?></h3>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <i class="fas fa-university text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 h-[calc(100vh-290px)]">
                
                <!-- 2. LEFT PANEL: STUDENT LIST & FILTER -->
                <div class="lg:col-span-1 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                    <!-- Filters -->
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50 space-y-3">
                        <form method="GET" action="admin-counseling-karir.php" class="flex gap-2">
                            <input type="text" name="search" placeholder="Cari nama santri..." value="<?= htmlspecialchars($search_query) ?>" class="flex-1 text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                            <button type="submit" class="bg-slate-700 text-white text-sm font-semibold px-3 py-2 rounded-lg hover:bg-slate-800 transition">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Filter Kelas</span>
                            <div class="flex space-x-1">
                                <a href="admin-counseling-karir.php?kelas=<?= urlencode($search_query) ?>" class="text-[11px] px-2.5 py-1 rounded-full font-medium transition <?= empty($filter_kelas) ? 'bg-slate-700 text-white' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' ?>">Semua</a>
                                <?php foreach($daftar_kelas as $k): ?>
                                    <a href="admin-counseling-karir.php?kelas=<?= urlencode($k) ?>&search=<?= urlencode($search_query) ?>" class="text-[11px] px-2.5 py-1 rounded-full font-medium transition <?= $filter_kelas == $k ? 'bg-slate-700 text-white' : 'bg-slate-200 text-slate-600 hover:bg-slate-300' ?>">Kls <?= htmlspecialchars($k) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- List container -->
                    <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                        <?php if ($santri_list_res && $santri_list_res->num_rows > 0): ?>
                            <?php while ($s = $santri_list_res->fetch_assoc()): ?>
                                <a href="admin-counseling-karir.php?santri_id=<?= $s['id'] ?>&kelas=<?= urlencode($filter_kelas) ?>&search=<?= urlencode($search_query) ?>" 
                                   class="block p-4 hover:bg-slate-50 transition duration-150 <?= $active_santri_id == $s['id'] ? 'bg-cyan-50/70 border-l-4 border-cyan-500' : '' ?>">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-bold text-slate-700 text-sm"><?= htmlspecialchars($s['nama_lengkap']) ?></h4>
                                            <p class="text-xs text-slate-400 mt-1">Kelas: <?= htmlspecialchars($s['kelas_sekarang']) ?></p>
                                            
                                            <?php if(!empty($s['target_ptn_1'])): ?>
                                                <div class="flex items-center space-x-1 mt-2">
                                                    <span class="text-[10px] bg-cyan-100 text-cyan-800 font-semibold px-2 py-0.5 rounded">
                                                        <?= htmlspecialchars($s['jalur_pilihan']) ?>: <?= htmlspecialchars($s['target_jurusan_1']) ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="inline-block text-[10px] text-slate-400 italic mt-2"><i class="fas fa-times-circle mr-1"></i>Belum terpetakan</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Rata-rata Nilai -->
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-slate-400">Rata-rata</span>
                                            <div class="text-sm font-extrabold text-slate-700">
                                                <?= $s['rata_rata'] ? round($s['rata_rata'], 1) : '-' ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="p-8 text-center text-slate-400">
                                <i class="fas fa-users-slash text-3xl mb-2 text-slate-300"></i>
                                <p class="text-sm">Tidak ada santri aktif ditemukan.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. RIGHT PANEL: DETAILED WORKSPACE -->
                <div class="lg:col-span-2 flex flex-col h-full overflow-hidden">
                    <?php if ($active_santri): ?>
                        <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col overflow-hidden">
                            <!-- Nav Header -->
                            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                <div>
                                    <h3 class="font-extrabold text-slate-700 text-base"><?= htmlspecialchars($active_santri['nama_lengkap']) ?></h3>
                                    <p class="text-xs text-slate-400 mt-1">NIS: <?= htmlspecialchars($active_santri['nis'] ?? '-') ?> | NISN: <?= htmlspecialchars($active_santri['nisn'] ?? '-') ?> | Asal: <?= htmlspecialchars($active_santri['asal_sekolah'] ?? '-') ?></p>
                                </div>
                                <div class="flex space-x-2">
                                    <span class="text-xs font-bold bg-cyan-50 text-cyan-600 px-3 py-1.5 rounded-lg border border-cyan-100">
                                        Rata Diknas: <b><?= $diknas_avg ?></b>
                                    </span>
                                    <span class="text-xs font-bold bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg border border-emerald-100">
                                        Rata Diniyah: <b><?= $diniyah_avg ?></b>
                                    </span>
                                </div>
                            </div>

                            <!-- Form & Analysis Panels (Scrollable) -->
                            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                                
                                <!-- SECTION 1: MAP TARGETS -->
                                <div class="bg-slate-50/70 border border-slate-200/60 rounded-xl p-5">
                                    <div class="flex items-center space-x-2 mb-4">
                                        <i class="fas fa-bullseye text-cyan-600"></i>
                                        <h4 class="font-bold text-slate-700 text-sm">Target Pendidikan Tinggi (PTN/PTKIN)</h4>
                                    </div>
                                    <form id="careerForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <input type="hidden" name="santri_id" value="<?= $active_santri['id'] ?>">
                                        
                                        <!-- Jalur Pilihan -->
                                        <div class="md:col-span-2">
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Jalur Penerimaan Utama</label>
                                            <select name="jalur_pilihan" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2.5 focus:ring-2 focus:ring-cyan-500 focus:outline-none font-medium">
                                                <option value="SNBP" <?= ($active_counseling['jalur_pilihan'] ?? 'SNBP') == 'SNBP' ? 'selected' : '' ?>>SNBP (Seleksi Nasional Berdasarkan Prestasi - Nilai Rapor)</option>
                                                <option value="SNBT" <?= ($active_counseling['jalur_pilihan'] ?? '') == 'SNBT' ? 'selected' : '' ?>>SNBT (Seleksi Nasional Berdasarkan Tes - UTBK)</option>
                                                <option value="SPAN-PTKIN" <?= ($active_counseling['jalur_pilihan'] ?? '') == 'SPAN-PTKIN' ? 'selected' : '' ?>>SPAN-PTKIN (Prestasi Akademik UIN/IAIN/STAIN)</option>
                                                <option value="Prestasi" <?= ($active_counseling['jalur_pilihan'] ?? '') == 'Prestasi' ? 'selected' : '' ?>>Prestasi (Tahfidz/Olimpiade/Olahraga)</option>
                                                <option value="Mandiri" <?= ($active_counseling['jalur_pilihan'] ?? '') == 'Mandiri' ? 'selected' : '' ?>>Mandiri / Ujian Mandiri Kampus</option>
                                            </select>
                                        </div>

                                        <!-- Target 1 -->
                                        <div class="space-y-3 p-3 bg-white border border-slate-200/60 rounded-lg">
                                            <span class="text-[11px] font-extrabold text-cyan-600 uppercase tracking-wider">Pilihan Prioritas 1</span>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">Kampus Target</label>
                                                <input type="text" name="target_ptn_1" placeholder="Contoh: Universitas Indonesia" value="<?= htmlspecialchars($active_counseling['target_ptn_1'] ?? '') ?>" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">Program Studi / Jurusan</label>
                                                <input type="text" name="target_jurusan_1" placeholder="Contoh: Teknik Informatika" value="<?= htmlspecialchars($active_counseling['target_jurusan_1'] ?? '') ?>" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                                            </div>
                                        </div>

                                        <!-- Target 2 -->
                                        <div class="space-y-3 p-3 bg-white border border-slate-200/60 rounded-lg">
                                            <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider">Pilihan Cadangan 2</span>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">Kampus Target</label>
                                                <input type="text" name="target_ptn_2" placeholder="Contoh: UIN Raden Intan Lampung" value="<?= htmlspecialchars($active_counseling['target_ptn_2'] ?? '') ?>" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                                            </div>
                                            <div>
                                                <label class="block text-[10px] font-semibold text-slate-400 mb-1">Program Studi / Jurusan</label>
                                                <input type="text" name="target_jurusan_2" placeholder="Contoh: Pendidikan Agama Islam" value="<?= htmlspecialchars($active_counseling['target_jurusan_2'] ?? '') ?>" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none">
                                            </div>
                                        </div>

                                        <!-- Minat Bakat -->
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Minat & Bakat (Hobi / Karakter)</label>
                                            <textarea name="minat_bakat" rows="2" placeholder="Minat karir, kemampuan kepemimpinan, kepribadian santri..." class="w-full text-xs bg-white border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none"><?= htmlspecialchars($active_counseling['minat_bakat'] ?? '') ?></textarea>
                                        </div>

                                        <!-- Catatan Musyrif -->
                                        <div>
                                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Catatan & Evaluasi Musyrif (Perkembangan Akhlak & Akademik)</label>
                                            <textarea name="catatan_konselor" rows="2" placeholder="Tulis catatan pendampingan asrama, kedisiplinan, target tahfidz, atau arahan minat karir..." class="w-full text-xs bg-white border border-slate-200 rounded-lg px-3 py-2 focus:ring-2 focus:ring-cyan-500 focus:outline-none"><?= htmlspecialchars($active_counseling['catatan_konselor'] ?? '') ?></textarea>
                                        </div>

                                        <!-- Simpan Button -->
                                        <div class="md:col-span-2 flex justify-end">
                                            <button type="button" onclick="saveCareerProfile()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm hover:shadow transition flex items-center space-x-1.5">
                                                <i class="fas fa-save"></i> <span>Simpan Profil Karir</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <!-- SECTION 2: GRADE HISTORY TABLE -->
                                <div>
                                    <div class="flex items-center space-x-2 mb-3">
                                        <i class="fas fa-chart-line text-slate-600"></i>
                                        <h4 class="font-bold text-slate-700 text-sm">Riwayat & Trajektori Nilai Akademik Leger</h4>
                                    </div>
                                    
                                    <?php if (!empty($active_grades)): ?>
                                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm bg-white">
                                            <table class="w-full text-left text-xs divide-y divide-slate-200">
                                                <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                                    <tr>
                                                        <th class="px-4 py-3">Kelas</th>
                                                        <th class="px-4 py-3">Semester</th>
                                                        <th class="px-4 py-3">Jenis Ujian</th>
                                                        <th class="px-4 py-3">Mata Pelajaran</th>
                                                        <th class="px-4 py-3">Kategori</th>
                                                        <th class="px-4 py-3 text-center">Nilai</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100 text-slate-600 font-medium">
                                                    <?php foreach ($active_grades as $g): ?>
                                                        <tr class="hover:bg-slate-50/50">
                                                            <td class="px-4 py-2.5 font-bold">Kls <?= htmlspecialchars($g['kelas']) ?></td>
                                                            <td class="px-4 py-2.5">Smt <?= htmlspecialchars($g['semester']) ?></td>
                                                            <td class="px-4 py-2.5 text-slate-400"><?= htmlspecialchars($g['jenis_ujian']) ?></td>
                                                            <td class="px-4 py-2.5 font-semibold text-slate-700"><?= htmlspecialchars($g['nama_mapel']) ?></td>
                                                            <td class="px-4 py-2.5">
                                                                <span class="px-2 py-0.5 rounded-full text-[10px] <?= $g['kategori_mapel'] == 'Diknas' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' ?>">
                                                                    <?= htmlspecialchars($g['kategori_mapel']) ?>
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-2.5 text-center font-bold <?= $g['nilai'] >= 75 ? 'text-emerald-600' : 'text-rose-500' ?>"><?= htmlspecialchars($g['nilai']) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="border border-dashed border-slate-300 rounded-xl p-6 text-center text-slate-400 bg-white">
                                            <i class="fas fa-file-invoice text-2xl text-slate-300 mb-2"></i>
                                            <p class="text-xs">Siswa ini belum memiliki entri nilai akademik di sistem Leger.</p>
                                            <p class="text-[10px] text-slate-400/80 mt-1">Gunakan modul "Bank Nilai" untuk mengisi nilai siswa.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- SECTION 3: AI COUNSELING ANALYSIS -->
                                <div class="bg-cyan-50/20 rounded-xl p-6 border border-cyan-500/20 shadow-sm relative overflow-hidden ai-glow" id="aiContainer">
                                    <div class="absolute right-0 top-0 w-24 h-24 -mt-4 -mr-4 bg-cyan-500/5 rounded-full blur-xl pointer-events-none"></div>
                                    
                                    <div class="flex items-center justify-between border-b border-cyan-500/10 pb-4 mb-4">
                                        <div class="flex items-center space-x-2">
                                            <div class="w-8 h-8 rounded-lg bg-cyan-500 flex items-center justify-center text-white shadow-md animate-pulse">
                                                <i class="fas fa-robot"></i>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-cyan-800 text-sm">Konselor AI - Analisa Kelayakan PTN</h4>
                                                <p class="text-[10px] text-cyan-600/80">Analisis presisi bimbingan karir berbasis data & AI Gemini</p>
                                            </div>
                                        </div>
                                        <button type="button" onclick="generateAIAnalysis()" class="bg-cyan-500 hover:bg-cyan-600 text-white font-bold text-xs px-4 py-2 rounded-lg transition duration-200 shadow flex items-center space-x-1.5">
                                            <i class="fas fa-sync-alt" id="aiSyncIcon"></i> <span id="aiBtnText">Proses Analisis AI</span>
                                        </button>
                                    </div>

                                    <!-- AI Output -->
                                    <div id="aiOutputContent" class="text-xs text-slate-700 leading-relaxed font-medium bg-white p-5 border border-slate-200/80 rounded-lg shadow-inner min-h-[120px] prose prose-slate max-w-none">
                                        <?php if (!empty($active_counseling['analisa_ai'])): ?>
                                            <!-- Render saved markdown -->
                                            <script>
                                                document.addEventListener("DOMContentLoaded", function() {
                                                    const rawMarkdown = `<?= str_replace('`', '\`',$active_counseling['analisa_ai']) ?>`;
                                                    document.getElementById('aiOutputContent').innerHTML = marked.parse(rawMarkdown);
                                                });
                                            </script>
                                        <?php else: ?>
                                            <div class="text-center text-slate-400 py-8">
                                                <i class="fas fa-brain text-3xl text-cyan-200 mb-2"></i>
                                                <p class="text-xs">Rekomendasi belum dibuat. Klik <b>"Proses Analisis AI"</b> di atas untuk membantu Musyrif memetakan minat dan kelayakan akademik santri ke PTN/PTKIN.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Greeting Workspace -->
                        <div class="flex-1 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col justify-center items-center p-8 text-center">
                            <div class="w-16 h-16 bg-cyan-50 text-cyan-600 rounded-full flex items-center justify-center shadow mb-4">
                                <i class="fas fa-graduation-cap text-3xl"></i>
                            </div>
                            <h3 class="font-extrabold text-slate-700 text-lg">Pilih Santri Untuk Mulai Pemetaan Karir</h3>
                            <p class="text-xs text-slate-400 max-w-md mt-2">Pilih salah satu siswa di panel sebelah kiri untuk memetakan minat bakat, target PTN/PTKIN, dan meluncurkan analisis kelayakan AI untuk meniru sukses 100% lulusan PTN.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </main>
    </div>

    <!-- JavaScript Actions -->
    <script>
        function saveCareerProfile() {
            const form = document.getElementById('careerForm');
            const formData = new FormData(form);
            formData.append('action', 'save_profile');

            fetch('admin-counseling-karir.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('Sukses: ' + data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error(err);
                alert('Gagal menghubungi server.');
            });
        }

        function generateAIAnalysis() {
            const syncIcon = document.getElementById('aiSyncIcon');
            const btnText = document.getElementById('aiBtnText');
            const aiContainer = document.getElementById('aiContainer');
            const outputDiv = document.getElementById('aiOutputContent');
            const santriId = <?= $active_santri_id ?>;

            if (santriId === 0) return;

            // Loading state
            syncIcon.classList.add('fa-spin');
            btnText.innerText = "Menganalisis (Mohon Tunggu)...";
            aiContainer.classList.add('glow-active');
            outputDiv.innerHTML = `<div class="text-center text-slate-400 py-8"><i class="fas fa-circle-notch fa-spin text-3xl text-cyan-400 mb-2"></i><p class="text-xs font-semibold">Memproses data nilai & preferensi karir santri...</p><p class="text-[10px] text-slate-400/80 mt-1">Menghubungi Konselor AI Gemini (Dibutuhkan sekitar 10-15 detik)...</p></div>`;

            const formData = new FormData();
            formData.append('action', 'get_ai_analysis');
            formData.append('santri_id', santriId);

            fetch('admin-counseling-karir.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                syncIcon.classList.remove('fa-spin');
                btnText.innerText = "Proses Analisis AI";
                aiContainer.classList.remove('glow-active');

                if (data.status === 'success') {
                    outputDiv.innerHTML = marked.parse(data.result);
                } else {
                    outputDiv.innerHTML = `<div class="text-center text-rose-500 py-8"><i class="fas fa-exclamation-triangle text-3xl mb-2"></i><p class="text-xs font-bold">Proses Gagal</p><p class="text-[10px] text-rose-400 mt-1">${data.message}</p></div>`;
                }
            })
            .catch(err => {
                console.error(err);
                syncIcon.classList.remove('fa-spin');
                btnText.innerText = "Proses Analisis AI";
                aiContainer.classList.remove('glow-active');
                outputDiv.innerHTML = `<div class="text-center text-rose-500 py-8"><i class="fas fa-exclamation-triangle text-3xl mb-2"></i><p class="text-xs font-bold">Proses Gagal</p><p class="text-[10px] text-rose-400 mt-1">Gagal menghubungi server untuk analisis AI.</p></div>`;
            });
        }
    </script>
</body>
</html>
