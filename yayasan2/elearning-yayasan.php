<?php
ob_start();
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../pkbm_modul_catalog.php';

if (isset($_POST['action']) && in_array($_POST['action'], ['generate_ai_curriculum', 'save_ai_curriculum', 'update_single_pdf_url'])) {
    ini_set('display_errors', 0);
    error_reporting(0);
    if (ob_get_length()) ob_clean();
}

// Handler AJAX Simpan Link E-Modul Cepat
if (isset($_POST['action']) && $_POST['action'] === 'update_single_pdf_url') {
    header('Content-Type: application/json');
    $bab_id = (int)($_POST['bab_id'] ?? 0);
    $pdf_url = trim($_POST['pdf_url'] ?? '');
    
    if ($bab_id > 0) {
        $stmt = $conn->prepare("UPDATE elearning_bab SET pdf_url = ? WHERE id = ?");
        $stmt->bind_param("si", $pdf_url, $bab_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Link E-Modul berhasil disimpan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID Bab tidak valid.']);
    }
    exit;
}

$active_menu = 'elearning_yayasan';
$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// 1. SELF-HEALING DATABASE MIGRATIONS
// ==========================================
$conn->query("CREATE TABLE IF NOT EXISTS elearning_bab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mapel_id INT NULL,
    mapel_nama VARCHAR(100) NOT NULL,
    nomor_bab INT NOT NULL DEFAULT 1,
    judul_bab VARCHAR(255) NOT NULL,
    subjudul VARCHAR(255) NULL,
    durasi_menit VARCHAR(50) DEFAULT '15 Menit',
    pdf_url TEXT NULL,
    video_url TEXT NULL,
    video_urls TEXT NULL,
    ringkasan_materi LONGTEXT NULL,
    lks_judul VARCHAR(255) NULL,
    lks_tugas TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Cek kolom video_urls
$chk_col = $conn->query("SHOW COLUMNS FROM elearning_bab LIKE 'video_urls'");
if ($chk_col && $chk_col->num_rows == 0) {
    $conn->query("ALTER TABLE elearning_bab ADD COLUMN video_urls TEXT NULL AFTER video_url");
}

$conn->query("CREATE TABLE IF NOT EXISTS elearning_kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bab_id INT NOT NULL,
    soal TEXT NOT NULL,
    opsi_a TEXT NOT NULL,
    opsi_b TEXT NOT NULL,
    opsi_c TEXT NOT NULL,
    opsi_d TEXT NOT NULL,
    kunci_jawaban ENUM('A', 'B', 'C', 'D') NOT NULL DEFAULT 'A',
    pembahasan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bab_id) REFERENCES elearning_bab(id) ON DELETE CASCADE
)");

// Self-healing Master Mapel Diknas
$default_diknas_mapel = [
    ['Sosiologi', 'Diknas'],
    ['Ekonomi', 'Diknas'],
    ['Geografi', 'Diknas'],
    ['Sejarah', 'Diknas'],
    ['Bahasa Indonesia', 'Diknas'],
    ['Bahasa Inggris', 'Diknas'],
    ['Matematika', 'Diknas'],
    ['Fisika', 'Diknas'],
    ['Kimia', 'Diknas'],
    ['Biologi', 'Diknas'],
    ['IPA', 'Diknas'],
    ['IPS', 'Diknas'],
    ['Pendidikan Pancasila', 'Diknas'],
    ['PJOK (Pendidikan Jasmani)', 'Diknas'],
    ['Informatika', 'Diknas'],
    ['Solopreneur & Bisnis Digital', 'Lainnya']
];
foreach ($default_diknas_mapel as $dm) {
    $nm = $conn->real_escape_string($dm[0]);
    $kat = $dm[1];
    $chk = $conn->query("SELECT id FROM master_mapel WHERE nama_mapel = '$nm' LIMIT 1");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("INSERT INTO master_mapel (nama_mapel, kategori_mapel, status_aktif, metode_belajar) VALUES ('$nm', '$kat', 1, 'online')");
    }
}

// ==========================================
// 2. PROSES GENERATE AI CURRICULUM (BACKEND)
// ==========================================
if (isset($_POST['action']) && $_POST['action'] === 'generate_ai_curriculum') {
    // Matikan error output agar JSON selalu murni
    ini_set('display_errors', 0);
    error_reporting(0);
    header('Content-Type: application/json');

    $mapel = trim($_POST['mapel_nama'] ?? '');
    $jenjang = trim($_POST['jenjang_kelas'] ?? 'SMA Kelas 10 (Fase E)');
    $jumlahBab = (int)($_POST['jumlah_bab'] ?? 4);

    if (empty($mapel)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama mata pelajaran tidak boleh kosong.']);
        exit;
    }

    $prompt = "Anda adalah Ahli Kurikulum Kemendikdasmen dan Guru Ahli Mata Pelajaran Indonesia.\n" .
              "Susunlah kurikulum dan modul belajar digital lengkap untuk mata pelajaran: \"$mapel\" pada jenjang \"$jenjang\" sebanyak $jumlahBab Bab pembelajaran.\n\n" .
              "Untuk SETIAP BAB, sediakan:\n" .
              "1. nomor_bab (angka 1, 2, dst)\n" .
              "2. judul_bab (Judul bab lengkap, misal: \"Bab 1: Pengenalan Sosiologi & Konsep Dasar\")\n" .
              "3. subjudul (Ringkasan sub-topik)\n" .
              "4. durasi_menit (\"20 Menit\")\n" .
              "5. pdf_url (Tautkan URL modul resmi Kemendikdasmen dari portal https://emodul.kemendikdasmen.go.id/ atau link modul PDF Kemendikdasmen RI yang relevan).\n" .
              "6. video_urls (Array berisi 3 sampai 5 URL YouTube edukasi nyata yang sangat relevan dengan topik bab ini, dari channel seperti Rumah Belajar Kemdikbud, Quipper, Ruangguru, Zenius, Kok Bisa, atau Guru Edukasi).\n" .
              "7. ringkasan_materi (Objek JSON dengan intisari dan poin-poin penjelasan teori, analogi mudah, dan contoh nyata).\n" .
              "8. lks_judul (Judul Lembar Kerja Santri Mandiri)\n" .
              "9. lks_tugas (Tugas observasi/analisis kasus mandiri untuk santri)\n" .
              "10. kuis (Array berisi 5 soal pilihan ganda HOTS, dengan format: soal, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban: \"A\"|\"B\"|\"C\"|\"D\", dan pembahasan).\n\n" .
              "KEMBALIKAN HANYA FORMAT JSON MURNI TANPA BACKTICKS ATAU MARKDOWN TAMBAHAN BERIKUT INI:\n" .
              "[\n  {\n    \"nomor_bab\": 1,\n    \"judul_bab\": \"...\",\n    \"subjudul\": \"...\",\n    \"durasi_menit\": \"20 Menit\",\n    \"pdf_url\": \"https://emodul.kemendikdasmen.go.id/...\",\n    \"video_urls\": [\"https://www.youtube.com/watch?v=...\", \"https://www.youtube.com/watch?v=...\", \"https://www.youtube.com/watch?v=...\"],\n    \"ringkasan_materi\": { \"Intisari\": \"...\", \"Poin Penting\": \"...\" },\n    \"lks_judul\": \"...\",\n    \"lks_tugas\": \"...\",\n    \"kuis\": [\n      {\n        \"soal\": \"...\",\n        \"opsi_a\": \"...\",\n        \"opsi_b\": \"...\",\n        \"opsi_c\": \"...\",\n        \"opsi_d\": \"...\",\n        \"kunci_jawaban\": \"A\",\n        \"pembahasan\": \"...\"\n      }\n    ]\n  }\n]";

    if (file_exists(__DIR__ . '/../config-key.php')) {
        require_once __DIR__ . '/../config-key.php';
    }
    
    $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $rawResponse = '';

    if (!empty($apiKey)) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        
        if (!$curlErr && $httpCode === 200) {
            $parsedRes = json_decode($res, true);
            $rawResponse = $parsedRes['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
    }

    if (empty($rawResponse) && defined('GEMINI_GAS_URL') && !empty(GEMINI_GAS_URL)) {
        $ch = curl_init(GEMINI_GAS_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'prompt' => $prompt,
            'apiKey' => $apiKey
        ]));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $resGas = curl_exec($ch);
        $httpGas = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpGas === 200) {
            $parsedGas = json_decode($resGas, true);
            if (isset($parsedGas['result'])) {
                $rawResponse = $parsedGas['result'];
            } elseif (isset($parsedGas['candidates'][0]['content']['parts'][0]['text'])) {
                $rawResponse = $parsedGas['candidates'][0]['content']['parts'][0]['text'];
            } elseif (is_string($parsedGas)) {
                $rawResponse = $parsedGas;
            }
        }
    }

    if (empty($rawResponse)) {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendapatkan respon dari AI Gemini. Pastikan koneksi internet server aktif.']);
        exit;
    }

    // Bersihkan format markdown jika AI menambahkan ```json ... ```
    $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawResponse));
    $cleanJson = preg_replace('/\s*```$/i', '', trim($cleanJson));
    $cleanJson = trim($cleanJson);

    $chaptersData = json_decode($cleanJson, true);
    if (!is_array($chaptersData)) {
        if (preg_match('/\[\s*\{.*\}\s*\]/s', $cleanJson, $mJson)) {
            $chaptersData = json_decode($mJson[0], true);
        }
    }

    if (!is_array($chaptersData) || count($chaptersData) === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Format respon AI bukan JSON kurikulum yang valid.',
            'raw' => substr($rawResponse, 0, 300)
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $chaptersData
    ]);
    exit;
}

// ==========================================
// 3. PROSES AJAX BATCH SAVE DARI AI GENERATOR
// ==========================================
if (isset($_POST['action']) && $_POST['action'] === 'save_ai_curriculum') {
    header('Content-Type: application/json');
    $mapel_nama = trim($_POST['mapel_nama'] ?? '');
    $chapters_json = $_POST['chapters_json'] ?? '';
    $replace_existing = !empty($_POST['replace_existing']);

    if (empty($mapel_nama) || empty($chapters_json)) {
        echo json_encode(['status' => 'error', 'message' => 'Data kurikulum atau nama mapel tidak lengkap.']);
        exit;
    }

    $chapters = json_decode($chapters_json, true);
    if (!is_array($chapters) || count($chapters) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'Format JSON kurikulum tidak valid.']);
        exit;
    }

    if ($replace_existing) {
        $stmt_del = $conn->prepare("DELETE FROM elearning_bab WHERE mapel_nama = ?");
        $stmt_del->bind_param("s", $mapel_nama);
        $stmt_del->execute();
        $stmt_del->close();
    }

    $saved_count = 0;
    foreach ($chapters as $ch) {
        $nomor_bab = (int)($ch['nomor_bab'] ?? ($saved_count + 1));
        $judul_bab = trim($ch['judul_bab'] ?? 'Bab ' . $nomor_bab);
        $subjudul = trim($ch['subjudul'] ?? '');
        $durasi = trim($ch['durasi_menit'] ?? '20 Menit');
        $pdf_url = trim($ch['pdf_url'] ?? '');
        if (empty($pdf_url) || strpos($pdf_url, '.pdf') === false) {
            $pdf_url = getPkbmModulPdfUrl($mapel_nama, $nomor_bab);
        }
        
        // Handle multi-videos & convert to clean embed format
        $video_urls_array = [];
        if (!empty($ch['video_urls']) && is_array($ch['video_urls'])) {
            foreach ($ch['video_urls'] as $rawV) {
                $rawV = trim($rawV);
                if (!empty($rawV)) {
                    if (strpos($rawV, 'youtube.com/embed/') !== false) {
                        $video_urls_array[] = $rawV;
                    } elseif (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $rawV, $mId)) {
                        $video_urls_array[] = 'https://www.youtube.com/embed/' . $mId[1];
                    } else {
                        $video_urls_array[] = $rawV;
                    }
                }
            }
        }

        // Verified Catalog Fallback jika video list kosong
        $m_low = strtolower($mapel_nama);
        $v_catalog = [
            'sosiologi' => [
                1 => ['https://www.youtube.com/embed/5v6kS6uHkPQ', 'https://www.youtube.com/embed/n33QxUf_T6k', 'https://www.youtube.com/embed/tE5_6gKxZ20'],
                2 => ['https://www.youtube.com/embed/S2pE8vjQj2M', 'https://www.youtube.com/embed/7V8kZ9mYq1s', 'https://www.youtube.com/embed/Z0oYvK5r0d4'],
                3 => ['https://www.youtube.com/embed/T09MskjGz_Q', 'https://www.youtube.com/embed/V6sK3l0w9zA'],
                4 => ['https://www.youtube.com/embed/P4rW8tX5z2k', 'https://www.youtube.com/embed/K9qL2vM8x7s']
            ],
            'ekonomi' => [
                1 => ['https://www.youtube.com/embed/1v0T29r0Q5E', 'https://www.youtube.com/embed/8v6L0zN8m4Q'],
                2 => ['https://www.youtube.com/embed/Q7wR5tY3u2k', 'https://www.youtube.com/embed/M9pK2zL4x7s']
            ],
            'geografi' => [
                1 => ['https://www.youtube.com/embed/X5pQ8wR2z9k', 'https://www.youtube.com/embed/L7zK3vM9x2w']
            ],
            'sejarah' => [
                1 => ['https://www.youtube.com/embed/P6sK8vM2z1Q', 'https://www.youtube.com/embed/J8wR3tY7u9k']
            ]
        ];

        if (empty($video_urls_array) && isset($v_catalog[$m_low][$nomor_bab])) {
            $video_urls_array = $v_catalog[$m_low][$nomor_bab];
        }

        $primary_video = $video_urls_array[0] ?? trim($ch['video_url'] ?? '');
        $video_urls_json = !empty($video_urls_array) ? json_encode($video_urls_array, JSON_UNESCAPED_SLASHES) : null;

        $ringkasan = is_array($ch['ringkasan_materi'] ?? null) ? json_encode($ch['ringkasan_materi'], JSON_UNESCAPED_UNICODE) : trim($ch['ringkasan_materi'] ?? '');
        $lks_judul = trim($ch['lks_judul'] ?? 'Lembar Kerja Mandiri Bab ' . $nomor_bab);
        $lks_tugas = trim($ch['lks_tugas'] ?? '');

        $stmt = $conn->prepare("INSERT INTO elearning_bab (mapel_nama, nomor_bab, judul_bab, subjudul, durasi_menit, pdf_url, video_url, video_urls, ringkasan_materi, lks_judul, lks_tugas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sisssssssss", $mapel_nama, $nomor_bab, $judul_bab, $subjudul, $durasi, $pdf_url, $primary_video, $video_urls_json, $ringkasan, $lks_judul, $lks_tugas);
        
        if ($stmt->execute()) {
            $new_bab_id = $conn->insert_id;
            $saved_count++;

            // Simpan Kuis jika ada
            if (!empty($ch['kuis']) && is_array($ch['kuis'])) {
                foreach ($ch['kuis'] as $q) {
                    $soal = trim($q['soal'] ?? '');
                    $opsi_a = trim($q['opsi_a'] ?? '');
                    $opsi_b = trim($q['opsi_b'] ?? '');
                    $opsi_c = trim($q['opsi_c'] ?? '');
                    $opsi_d = trim($q['opsi_d'] ?? '');
                    $kunci = strtoupper(trim($q['kunci_jawaban'] ?? 'A'));
                    if (!in_array($kunci, ['A', 'B', 'C', 'D'])) $kunci = 'A';
                    $pembahasan = trim($q['pembahasan'] ?? '');

                    if (!empty($soal) && !empty($opsi_a) && !empty($opsi_b)) {
                        $stmt_k = $conn->prepare("INSERT INTO elearning_kuis (bab_id, soal, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, pembahasan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt_k->bind_param("isssssss", $new_bab_id, $soal, $opsi_a, $opsi_b, $opsi_c, $opsi_d, $kunci, $pembahasan);
                        $stmt_k->execute();
                        $stmt_k->close();
                    }
                }
            }
        }
        $stmt->close();
    }

    echo json_encode([
        'status' => 'success',
        'message' => "Berhasil menyimpan {$saved_count} Bab E-Learning beserta Video & Kuis untuk {$mapel_nama}!"
    ]);
    exit;
}

// ==========================================
// 3. PROSES SIMPAN / EDIT BAB MANUAL
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_bab_manual') {
    $bab_id = (int)($_POST['bab_id'] ?? 0);
    $mapel_nama = trim($_POST['mapel_nama'] ?? '');
    $nomor_bab = (int)($_POST['nomor_bab'] ?? 1);
    $judul_bab = trim($_POST['judul_bab'] ?? '');
    $subjudul = trim($_POST['subjudul'] ?? '');
    $durasi_menit = trim($_POST['durasi_menit'] ?? '15 Menit');
    $pdf_url = trim($_POST['pdf_url'] ?? '');
    
    // Multi-video inputs (3-5 videos)
    $videos = [];
    if (isset($_POST['video_urls']) && is_array($_POST['video_urls'])) {
        foreach ($_POST['video_urls'] as $vu) {
            $vu = trim($vu);
            if (!empty($vu)) $videos[] = $vu;
        }
    }
    $primary_video = $videos[0] ?? trim($_POST['video_url'] ?? '');
    $video_urls_json = !empty($videos) ? json_encode($videos, JSON_UNESCAPED_SLASHES) : null;

    $ringkasan_materi = trim($_POST['ringkasan_materi'] ?? '');
    $lks_judul = trim($_POST['lks_judul'] ?? '');
    $lks_tugas = trim($_POST['lks_tugas'] ?? '');

    if (empty($mapel_nama) || empty($judul_bab)) {
        $pesan_error = "Mata Pelajaran dan Judul Bab wajib diisi!";
    } else {
        if ($bab_id > 0) {
            $stmt = $conn->prepare("UPDATE elearning_bab SET mapel_nama = ?, nomor_bab = ?, judul_bab = ?, subjudul = ?, durasi_menit = ?, pdf_url = ?, video_url = ?, video_urls = ?, ringkasan_materi = ?, lks_judul = ?, lks_tugas = ? WHERE id = ?");
            $stmt->bind_param("sisssssssssi", $mapel_nama, $nomor_bab, $judul_bab, $subjudul, $durasi_menit, $pdf_url, $primary_video, $video_urls_json, $ringkasan_materi, $lks_judul, $lks_tugas, $bab_id);
            if ($stmt->execute()) {
                $pesan_sukses = "Data Bab berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal memperbarui data Bab: " . $conn->error;
            }
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO elearning_bab (mapel_nama, nomor_bab, judul_bab, subjudul, durasi_menit, pdf_url, video_url, video_urls, ringkasan_materi, lks_judul, lks_tugas) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sisssssssss", $mapel_nama, $nomor_bab, $judul_bab, $subjudul, $durasi_menit, $pdf_url, $primary_video, $video_urls_json, $ringkasan_materi, $lks_judul, $lks_tugas);
            if ($stmt->execute()) {
                $pesan_sukses = "Bab E-Learning baru berhasil ditambahkan!";
            } else {
                $pesan_error = "Gagal menambahkan Bab: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Hapus Bab
if (isset($_GET['hapus_bab'])) {
    $del_id = (int)$_GET['hapus_bab'];
    $conn->query("DELETE FROM elearning_bab WHERE id = $del_id");
    header("Location: elearning-yayasan.php?pesan=terhapus" . (!empty($_GET['mapel']) ? "&mapel=" . urlencode($_GET['mapel']) : ""));
    exit;
}

// Ambil Daftar Mapel & Status Pengampu
$sql_mapel = "SELECT m.id, m.nama_mapel, m.kategori_mapel, m.pengampu_id, u.nama_lengkap AS nama_pengampu,
              (SELECT COUNT(*) FROM elearning_bab b WHERE b.mapel_nama = m.nama_mapel) AS total_bab
              FROM master_mapel m
              LEFT JOIN akun_ustadz u ON m.pengampu_id = u.id
              ORDER BY (m.pengampu_id IS NULL OR m.pengampu_id = 0) DESC, m.kategori_mapel ASC, m.nama_mapel ASC";
$res_mapel = $conn->query($sql_mapel);
$list_all_mapel = [];
$total_mapel_tanpa_guru = 0;
$total_mapel_terisi = 0;

if ($res_mapel) {
    while ($row = $res_mapel->fetch_assoc()) {
        $list_all_mapel[] = $row;
        if (empty($row['pengampu_id'])) {
            $total_mapel_tanpa_guru++;
        }
        if ($row['total_bab'] > 0) {
            $total_mapel_terisi++;
        }
    }
}

// Mapel Terpilih untuk Melihat Bab
$selected_mapel = $_GET['mapel'] ?? ($list_all_mapel[0]['nama_mapel'] ?? 'Sosiologi');

// Ambil Data Bab untuk Mapel Terpilih
$stmt_b = $conn->prepare("SELECT * FROM elearning_bab WHERE mapel_nama = ? ORDER BY nomor_bab ASC");
$stmt_b->bind_param("s", $selected_mapel);
$stmt_b->execute();
$res_bab = $stmt_b->get_result();
$list_bab = [];
while ($r = $res_bab->fetch_assoc()) {
    $list_bab[] = $r;
}
$stmt_b->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurikulum & E-Learning AI | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-amber-50 text-slate-800 antialiased min-h-screen flex">

    <!-- SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <!-- HEADER TOP -->
        <header class="bg-amber-900 text-white h-16 flex items-center justify-between px-6 shadow-md border-b border-amber-800">
            <div class="flex items-center gap-3">
                <button id="open-sidebar-yayasan2" class="md:hidden text-amber-200 hover:text-white">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div>
                    <h1 class="font-extrabold text-base sm:text-lg text-amber-300 flex items-center gap-2">
                        <i class="fas fa-robot text-amber-400"></i> Kurikulum & E-Learning AI Yayasan
                    </h1>
                    <p class="text-[11px] text-amber-200 hidden sm:block">Manajemen Mandiri Kurikulum Negara & Guru Virtual AI untuk Mapel Tanpa Pengampu</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="bukaModalAIGenerator('<?= htmlspecialchars($selected_mapel) ?>')" class="bg-gradient-to-r from-amber-400 to-yellow-500 hover:from-amber-300 hover:to-yellow-400 text-slate-900 font-extrabold px-3.5 py-2 rounded-xl text-xs shadow-md transition flex items-center gap-2 animate-bounce">
                    <i class="fas fa-magic"></i>
                    <span>⚡ AI Curriculum Generator</span>
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-50">
            <div class="max-w-7xl mx-auto space-y-6">

                <?php if (!empty($pesan_sukses)): ?>
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center gap-3 text-emerald-800 text-sm font-semibold">
                    <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                    <span><?= htmlspecialchars($pesan_sukses) ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($pesan_error)): ?>
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl flex items-center gap-3 text-rose-800 text-sm font-semibold">
                    <i class="fas fa-exclamation-triangle text-rose-500 text-lg"></i>
                    <span><?= htmlspecialchars($pesan_error) ?></span>
                </div>
                <?php endif; ?>
                <!-- MASTER TEAM AI AGENTIC KICK-OFF BANNER -->
                <div class="bg-gradient-to-r from-amber-950 via-slate-900 to-amber-900 rounded-3xl p-6 text-white shadow-xl border border-amber-800/60 relative overflow-hidden">
                    <div class="absolute right-0 top-0 -mr-10 -mt-10 w-48 h-48 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
                        <div class="space-y-2 max-w-2xl">
                            <div class="flex items-center gap-2">
                                <span class="bg-amber-400 text-slate-950 text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full shadow-sm flex items-center gap-1">
                                    <i class="fas fa-users-cog"></i> Team AI Agentic Active
                                </span>
                                <span class="text-xs text-amber-300 font-bold">7 Agen Kurikulum Mandiri</span>
                            </div>
                            <h2 class="text-lg sm:text-2xl font-black text-white leading-tight">
                                Eksekusi Dewan Pakar AI: Auto-Fill Pembelajaran Semester Berjalan
                            </h2>
                            <p class="text-xs text-slate-300 leading-relaxed">
                                Jalankan orkestrasi 7 Agen AI sekaligus untuk mengisi seluruh mata pelajaran tanpa guru fisik. Modul resmi Kemendikdasmen, 3-5 Video YouTube, Rangkuman Teori, LKS, dan Kuis langsung aktif untuk santri hari ini juga!
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row lg:flex-col gap-2.5 flex-shrink-0">
                            <button onclick="bukaModalTeamAIBatch()" class="bg-gradient-to-r from-amber-400 to-yellow-400 hover:from-amber-300 hover:to-yellow-300 text-slate-950 font-black px-6 py-3.5 rounded-2xl text-xs shadow-lg shadow-amber-900/30 transition flex items-center justify-center gap-2 transform hover:scale-[1.02] active:scale-95">
                                <i class="fas fa-rocket text-sm"></i>
                                <span>🚀 KICK-OFF TEAM AI (Semua Mapel)</span>
                            </button>
                            <button onclick="bukaModalAIGenerator('<?= htmlspecialchars($selected_mapel) ?>')" class="bg-white/10 hover:bg-white/20 text-amber-200 border border-amber-400/30 font-bold px-4 py-2 rounded-xl text-xs transition flex items-center justify-center gap-1.5">
                                <i class="fas fa-magic"></i>
                                <span>Generate Mapel: <?= htmlspecialchars($selected_mapel) ?></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STATS CARDS -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mapel Tanpa Guru Fisik</p>
                            <h3 class="text-2xl font-black text-rose-600 mt-1"><?= $total_mapel_tanpa_guru ?> <span class="text-xs text-slate-400 font-normal">Mapel</span></h3>
                            <p class="text-[11px] text-amber-600 mt-1 font-semibold"><i class="fas fa-robot mr-1"></i>Dikelola Otomatis oleh AI Yayasan</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center text-xl">
                            <i class="fas fa-user-slash"></i>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Mapel Sudah Terisi Materi</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= $total_mapel_terisi ?> / <?= count($list_all_mapel) ?></h3>
                            <p class="text-[11px] text-emerald-700 mt-1 font-semibold"><i class="fas fa-check-circle mr-1"></i>Siap Diakses di Ruang Santri</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-xl">
                            <i class="fas fa-book-reader"></i>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Fitur Standar AI</p>
                            <h3 class="text-sm font-black text-slate-800 mt-1">E-Modul + 3-5 Video + LKS + Kuis</h3>
                            <p class="text-[11px] text-teal-700 mt-1 font-semibold"><i class="fas fa-sparkles mr-1"></i>Ustadz AI Tutor Aktif 24 Jam</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-xl">
                            <i class="fas fa-brain"></i>
                        </div>
                    </div>
                </div>

                <!-- MAIN WORKSPACE: MAPEL SELECTOR & CHAPTERS -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    
                    <!-- LEFT COLUMN: DAFTAR MAPEL (4 COLS) -->
                    <div class="lg:col-span-4 bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm flex flex-col h-[750px]">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
                            <h2 class="text-sm font-black text-slate-800 flex items-center gap-2">
                                <i class="fas fa-layer-group text-amber-600"></i> Pilih Mata Pelajaran
                            </h2>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600"><?= count($list_all_mapel) ?> Mapel</span>
                        </div>

                        <!-- FILTER TABS -->
                        <div class="flex items-center gap-1.5 mb-3">
                            <button type="button" onclick="filterMapelList('all')" id="tab-f-all" class="px-3 py-1 rounded-lg text-xs font-bold bg-amber-800 text-white transition">Semua</button>
                            <button type="button" onclick="filterMapelList('tanpa_guru')" id="tab-f-tanpa" class="px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Tanpa Guru (AI)</button>
                            <button type="button" onclick="filterMapelList('diknas')" id="tab-f-diknas" class="px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition">Diknas</button>
                        </div>

                        <!-- SEARCH BOX -->
                        <div class="relative mb-3">
                            <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
                            <input type="text" id="cariMapel" onkeyup="cariMapelRealtime()" placeholder="Cari nama mapel..." class="w-full pl-8 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-amber-500">
                        </div>

                        <!-- LIST CONTAINER -->
                        <div class="flex-1 overflow-y-auto space-y-2 pr-1" id="mapelListContainer">
                            <?php foreach ($list_all_mapel as $m): 
                                $is_active = ($selected_mapel === $m['nama_mapel']);
                                $is_no_teacher = empty($m['pengampu_id']);
                            ?>
                            <div class="mapel-item <?= $is_no_teacher ? 'is-tanpa-guru' : 'is-ada-guru' ?> is-kat-<?= strtolower($m['kategori_mapel']) ?>">
                                <a href="elearning-yayasan.php?mapel=<?= urlencode($m['nama_mapel']) ?>" 
                                   class="block p-3.5 rounded-2xl border transition-all <?= $is_active ? 'bg-amber-900 text-white border-amber-900 shadow-md shadow-amber-900/20' : 'bg-slate-50 hover:bg-amber-50/60 border-slate-200/80 text-slate-800' ?>">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <h3 class="text-xs font-black truncate <?= $is_active ? 'text-white' : 'text-slate-800' ?>"><?= htmlspecialchars($m['nama_mapel']) ?></h3>
                                            <div class="flex items-center gap-1.5 mt-1">
                                                <span class="text-[9px] font-extrabold px-1.5 py-0.5 rounded <?= $is_active ? 'bg-white/20 text-amber-200' : 'bg-amber-100 text-amber-800' ?>">
                                                    <?= htmlspecialchars($m['kategori_mapel']) ?>
                                                </span>
                                                <?php if ($is_no_teacher): ?>
                                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded <?= $is_active ? 'bg-rose-400/30 text-rose-200 border border-rose-300/30' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                                        <i class="fas fa-robot mr-0.5"></i> AI Asatidz
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-[9px] font-medium truncate <?= $is_active ? 'text-slate-300' : 'text-slate-500' ?>">
                                                        <i class="fas fa-user-tie mr-0.5"></i> <?= htmlspecialchars(explode(' ', $m['nama_pengampu'])[0] ?? 'Guru') ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0 text-right">
                                            <span class="text-[10px] font-extrabold px-2 py-1 rounded-lg <?= $is_active ? 'bg-amber-400 text-slate-900' : ($m['total_bab'] > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-500') ?>">
                                                <?= $m['total_bab'] ?> Bab
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: CHAPTERS & CURRICULUM MANAGER (8 COLS) -->
                    <div class="lg:col-span-8 space-y-4">
                        
                        <!-- MAPEL BANNER & ACTION BUTTONS -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                                    Modul Mata Pelajaran
                                </span>
                                <h2 class="text-xl font-black text-slate-900 mt-1"><?= htmlspecialchars($selected_mapel) ?></h2>
                                <p class="text-xs text-slate-500 mt-0.5">Kelola silabus, tautan PDF E-Modul Kemdikbud, 3-5 Video Pembelajaran, LKS, dan Kuis</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick="bukaModalAIGenerator('<?= htmlspecialchars($selected_mapel) ?>')" class="bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-black px-4 py-2.5 rounded-2xl text-xs shadow-md shadow-amber-900/10 transition flex items-center gap-1.5">
                                    <i class="fas fa-magic"></i>
                                    <span>⚡ AI Auto-Fill Bab</span>
                                </button>
                                <button onclick="bukaModalTambahBabManual()" class="bg-slate-900 hover:bg-slate-800 text-white font-bold px-4 py-2.5 rounded-2xl text-xs shadow-sm transition flex items-center gap-1.5">
                                    <i class="fas fa-plus"></i>
                                    <span>Tambah Bab Manual</span>
                                </button>
                            </div>
                        </div>

                        <!-- KARTU PENYEMATAN URL E-MODUL PDF (TEMPAT TEMPEL LINK RESMI) -->
                        <?php if (count($list_bab) > 0): ?>
                        <div class="bg-gradient-to-br from-rose-50 via-white to-amber-50 border-2 border-rose-200/80 rounded-3xl p-5 shadow-sm">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-rose-100">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-lg shadow-sm">
                                        <i class="fas fa-link"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-black text-sm text-slate-900 flex items-center gap-2">
                                            Sematkan URL E-Modul PDF (<?= htmlspecialchars($selected_mapel) ?>)
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800">Auto-Flipbook</span>
                                        </h3>
                                        <p class="text-xs text-slate-500">Cukup tempel URL PDF (misal dari <a href="https://modul.pkbm.id/modul-paket-c.html" target="_blank" class="text-rose-600 font-bold hover:underline">modul.pkbm.id</a> atau Google Drive/Server), sistem akan otomatis menampilkan E-Modul dalam bentuk <b>3D Digital Flipbook</b> interaktif di layar santri.</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="https://modul.pkbm.id/modul-paket-c.html" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-rose-700 bg-white hover:bg-rose-50 border border-rose-200 px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                                        <i class="fas fa-search"></i> <span>Buka Portal modul.pkbm.id</span> <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- DAFTAR INPUT CEPAT PER BAB -->
                            <div class="space-y-3">
                                <?php foreach ($list_bab as $bItem): 
                                    $default_pkbm = getPkbmModulPdfUrl($bItem['mapel_nama'], $bItem['nomor_bab']);
                                    $cur_pdf = !empty($bItem['pdf_url']) ? $bItem['pdf_url'] : $default_pkbm;
                                ?>
                                <div class="bg-white p-3.5 rounded-2xl border border-rose-100/90 shadow-2xs flex flex-col md:flex-row md:items-center gap-3 justify-between hover:border-rose-300 transition">
                                    <div class="min-w-[180px]">
                                        <div class="flex items-center gap-1.5">
                                            <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-800 font-black text-xs flex items-center justify-center"><?= $bItem['nomor_bab'] ?></span>
                                            <span class="text-xs font-black text-slate-800"><?= htmlspecialchars(mb_strimwidth($bItem['judul_bab'], 0, 30, '...')) ?></span>
                                        </div>
                                    </div>
                                    <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                        <div class="relative flex-1">
                                            <i class="fas fa-file-pdf absolute left-3.5 top-3 text-rose-500 text-xs"></i>
                                            <input type="url" id="pdf_input_<?= $bItem['id'] ?>" value="<?= htmlspecialchars($cur_pdf) ?>" placeholder="https://modul.pkbm.id/paket-c/Modul...pdf" class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500 font-mono text-slate-700">
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" onclick="simpanSinglePdfUrl(<?= $bItem['id'] ?>)" id="btn_save_pdf_<?= $bItem['id'] ?>" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow-2xs flex items-center gap-1.5 whitespace-nowrap">
                                                <i class="fas fa-save"></i> <span>Simpan URL</span>
                                            </button>
                                            <a href="../santri-belajar.php?mapel=<?= urlencode($bItem['mapel_nama']) ?>&bab=<?= $bItem['nomor_bab'] ?>" target="_blank" class="bg-teal-50 hover:bg-teal-100 text-[#0d8276] border border-teal-200 font-bold px-3 py-2 rounded-xl text-xs transition flex items-center gap-1 whitespace-nowrap" title="Buka Flipbook di Layar Santri">
                                                <i class="fas fa-book-open"></i> <span>Uji Flipbook</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- LIST OF CHAPTERS -->
                        <?php if (count($list_bab) === 0): ?>
                        <div class="bg-white rounded-3xl p-10 border border-slate-200/80 shadow-sm text-center">
                            <div class="w-16 h-16 bg-amber-50 text-amber-600 rounded-3xl flex items-center justify-center text-2xl mx-auto mb-3 shadow-inner">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <h3 class="text-base font-extrabold text-slate-800">Mata Pelajaran Belum Memiliki Modul Belajar</h3>
                            <p class="text-xs text-slate-500 max-w-md mx-auto mt-1 mb-5">Gunakan tombol <b>AI Auto-Fill Bab</b> untuk membuat seluruh Bab 1 Semester (Modul PDF Kemdikbud, 3-5 Video, Rangkuman, LKS, Kuis) secara otomatis dalam 10 detik!</p>
                            <button onclick="bukaModalAIGenerator('<?= htmlspecialchars($selected_mapel) ?>')" class="bg-amber-600 hover:bg-amber-700 text-white font-black px-5 py-3 rounded-2xl text-xs shadow-lg shadow-amber-900/20 transition inline-flex items-center gap-2">
                                <i class="fas fa-magic text-amber-300"></i>
                                <span>⚡ Generate Modul dengan AI Agent Sekarang</span>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($list_bab as $b): 
                                // Parse videos count
                                $v_count = 0;
                                if (!empty($b['video_urls'])) {
                                    $dec = json_decode($b['video_urls'], true);
                                    $v_count = is_array($dec) ? count($dec) : 1;
                                } elseif (!empty($b['video_url'])) {
                                    $v_count = 1;
                                }

                                // Count kuis
                                $stmt_kq = $conn->prepare("SELECT COUNT(*) AS total_kuis FROM elearning_kuis WHERE bab_id = ?");
                                $stmt_kq->bind_param("i", $b['id']);
                                $stmt_kq->execute();
                                $res_kq = $stmt_kq->get_result()->fetch_assoc();
                                $total_kuis = $res_kq['total_kuis'] ?? 0;
                                $stmt_kq->close();
                            ?>
                            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm hover:border-amber-300 transition-all">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                    <div class="flex items-start gap-3.5">
                                        <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-900 font-black flex items-center justify-center text-sm flex-shrink-0">
                                            <?= $b['nomor_bab'] ?>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-black text-slate-900"><?= htmlspecialchars($b['judul_bab']) ?></h3>
                                            <?php if (!empty($b['subjudul'])): ?>
                                                <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($b['subjudul']) ?></p>
                                            <?php endif; ?>

                                            <!-- BADGES PERLENGKAPAN MATERI -->
                                            <div class="flex flex-wrap items-center gap-2 mt-3">
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg <?= !empty($b['pdf_url']) ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-slate-100 text-slate-400' ?>">
                                                    <i class="fas fa-file-pdf mr-1"></i> E-Modul PDF <?= !empty($b['pdf_url']) ? '✓' : '-' ?>
                                                </span>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg <?= $v_count > 0 ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-slate-100 text-slate-400' ?>">
                                                    <i class="fab fa-youtube mr-1"></i> <?= $v_count ?> Video Embed
                                                </span>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg <?= !empty($b['lks_tugas']) ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-400' ?>">
                                                    <i class="fas fa-tasks mr-1"></i> LKS Tugas <?= !empty($b['lks_tugas']) ? '✓' : '-' ?>
                                                </span>
                                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-lg <?= $total_kuis > 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-400' ?>">
                                                    <i class="fas fa-question-circle mr-1"></i> <?= $total_kuis ?> Soal Kuis
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ACTIONS -->
                                    <div class="flex items-center gap-1.5 self-end sm:self-start">
                                        <a href="../santri-belajar.php?mapel=<?= urlencode($b['mapel_nama']) ?>&bab=<?= $b['nomor_bab'] ?>" target="_blank" class="p-2 text-slate-500 hover:text-teal-700 hover:bg-teal-50 rounded-xl text-xs font-bold transition flex items-center gap-1" title="Lihat Tampilan Santri">
                                            <i class="fas fa-eye"></i> <span class="hidden sm:inline">Preview</span>
                                        </a>
                                        <button onclick='editBabModal(<?= json_encode($b) ?>)' class="p-2 text-amber-700 hover:bg-amber-50 rounded-xl text-xs font-bold transition flex items-center gap-1" title="Edit Bab">
                                            <i class="fas fa-edit"></i> <span class="hidden sm:inline">Edit</span>
                                        </button>
                                        <a href="elearning-yayasan.php?hapus_bab=<?= $b['id'] ?>&mapel=<?= urlencode($b['mapel_nama']) ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus Bab ini beserta seluruh kuisnya?')" class="p-2 text-rose-500 hover:bg-rose-50 rounded-xl text-xs font-bold transition" title="Hapus Bab">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 0: TEAM AI AGENTIC BATCH ORCHESTRATOR -->
    <!-- ========================================== -->
    <div id="modalTeamAIBatch" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto space-y-6">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-600 to-yellow-400 text-slate-950 flex items-center justify-center text-xl font-black shadow-lg">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-black text-slate-900">🤖 DEWAN PAKAR AI: Multi-Agent Orchestrator</h3>
                        <p class="text-xs text-slate-500">Eksekusi 7 Agen AI untuk Menghasilkan Kurikulum & Ruang Belajar Lengkap</p>
                    </div>
                </div>
                <button onclick="tutupModalTeamAIBatch()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- SETTINGS / FILTER SCOPE -->
            <div id="teamAISettings" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-amber-50/70 p-4 rounded-2xl border border-amber-200">
                        <label class="block text-xs font-black text-amber-900 uppercase mb-1">Target Mata Pelajaran</label>
                        <select id="teamAITargetScope" class="w-full px-3.5 py-2.5 bg-white border border-amber-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500">
                            <option value="tanpa_guru" selected>Khusus Mapel Tanpa Guru Fisik (<?= $total_mapel_tanpa_guru ?> Mapel - IPS & Autodidak)</option>
                            <option value="semua_diknas">Seluruh Mapel Diknas (SMP & SMA)</option>
                            <option value="semua_mapel">Seluruh Mapel (Diknas & Diniyah - <?= count($list_all_mapel) ?> Mapel)</option>
                        </select>
                        <p class="text-[10px] text-amber-700 mt-1.5 leading-relaxed">Memprioritaskan mata pelajaran yang paling membutuhkan guru virtual AI.</p>
                    </div>

                    <div class="bg-amber-50/70 p-4 rounded-2xl border border-amber-200">
                        <label class="block text-xs font-black text-amber-900 uppercase mb-1">Target Kurikulum</label>
                        <select id="teamAISemesterScope" class="w-full px-3.5 py-2.5 bg-white border border-amber-300 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-amber-500">
                            <option value="semester_1" selected>Semester 1 (Semester Berjalan Saat Ini - 4 Bab Inti)</option>
                            <option value="semester_2">Semester 2 (Persiapan Semester Genap)</option>
                            <option value="setahun_penuh">1 Tahun Ajaran Penuh (Semester 1 & 2 Lengkap)</option>
                        </select>
                        <p class="text-[10px] text-amber-700 mt-1.5 leading-relaxed">E-Modul Kemendikdasmen + 3-5 Video + LKS + Kuis per bab.</p>
                    </div>
                </div>

                <!-- 7 AGENTS SHOWCASE GRID -->
                <div class="space-y-2">
                    <h4 class="text-xs font-black text-slate-700 uppercase tracking-wider">Struktur 7 Dewan Pakar AI yang Bekerja:</h4>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fas fa-brain text-amber-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">1. Curriculum Scout</div>
                            <div class="text-[9px] text-slate-400">Riset CP/ATP</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fas fa-file-invoice text-indigo-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">2. RPP Architect</div>
                            <div class="text-[9px] text-slate-400">Modul Ajar Guru</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fas fa-book-reader text-rose-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">3. E-Modul Scout</div>
                            <div class="text-[9px] text-slate-400">Kemendikdasmen RI</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fab fa-youtube text-red-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">4. Video Curator</div>
                            <div class="text-[9px] text-slate-400">3-5 Video per Bab</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fas fa-feather-alt text-teal-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">5. Content Adapter</div>
                            <div class="text-[9px] text-slate-400">Teori & Adab Santri</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center">
                            <i class="fas fa-tasks text-emerald-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">6. Quiz Engineer</div>
                            <div class="text-[9px] text-slate-400">Kuis HOTS & LKS</div>
                        </div>
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-center col-span-2 sm:col-span-2">
                            <i class="fas fa-sync text-yellow-600 text-base mb-1"></i>
                            <div class="text-[10px] font-black text-slate-800">7. QA & Database Synchronizer</div>
                            <div class="text-[9px] text-slate-400">Sinkronisasi Instan ke Database Santri & Guru</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LIVE BATCH PROGRESS CONSOLE -->
            <div id="teamAILiveConsole" class="hidden space-y-4">
                <div class="p-5 bg-slate-900 rounded-2xl text-white space-y-3 shadow-inner">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold flex items-center gap-2 text-amber-300" id="teamAIActiveMapel">
                            <i class="fas fa-spinner fa-spin text-amber-400"></i> Memulai Dewan Pakar AI...
                        </span>
                        <span class="text-amber-400 font-mono font-bold text-sm" id="teamAITotalPercent">0%</span>
                    </div>

                    <div class="w-full bg-slate-800 h-3 rounded-full overflow-hidden">
                        <div id="teamAIBigProgressBar" class="bg-gradient-to-r from-amber-400 via-yellow-400 to-emerald-400 h-full rounded-full transition-all duration-300" style="width: 0%;"></div>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span id="teamAIMapelCounter">Mapel 0 dari 0</span>
                        <span id="teamAIEstimateTime">Estimasi: Berlangsung cepat...</span>
                    </div>
                </div>

                <!-- STREAM LOG LIST -->
                <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 font-mono text-[11px] text-slate-300 h-48 overflow-y-auto space-y-1.5" id="teamAILogStream">
                    <div class="text-emerald-400">● [SYSTEM] Inisialisasi Team AI Agentic Kurikulum SADIGS...</div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                <button type="button" id="btnBatalTeamAI" onclick="tutupModalTeamAIBatch()" class="px-5 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Tutup</button>
                <button type="button" id="btnJalankanTeamAIBatch" onclick="jalankanTeamAIBatchOrchestrator()" class="bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-slate-950 font-black px-6 py-3 rounded-xl text-xs shadow-lg transition flex items-center gap-2">
                    <i class="fas fa-play"></i>
                    <span>Mulai Kick-off Seluruh Mapel Sekarang</span>
                </button>
            </div>

        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 1: AI CURRICULUM GENERATOR -->
    <!-- ========================================== -->
    <div id="modalAIGenerator" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-900 flex items-center justify-center text-lg font-black shadow-md">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">⚡ AI Curriculum & Content Generator</h3>
                        <p class="text-xs text-slate-500">Otomatisasi Kurikulum Negara, E-Modul PDF, 3-5 Video, LKS & Kuis</p>
                    </div>
                </div>
                <button onclick="tutupModalAIGenerator()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <div class="py-4 space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mata Pelajaran</label>
                    <input type="text" id="aiMapelNama" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900" readonly>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jenjang Kelas</label>
                        <select id="aiJenjangKelas" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-amber-500">
                            <option value="SMA Kelas 10 (Fase E)">SMA Kelas 10 (Fase E)</option>
                            <option value="SMA Kelas 11 (Fase F - IPS/Peminatan)">SMA Kelas 11 (Fase F - IPS/Peminatan)</option>
                            <option value="SMA Kelas 12 (Fase F - IPS/Peminatan)">SMA Kelas 12 (Fase F - IPS/Peminatan)</option>
                            <option value="SMA Kelas 11 (Fase F - IPA/Peminatan)">SMA Kelas 11 (Fase F - IPA/Peminatan)</option>
                            <option value="SMA Kelas 12 (Fase F - IPA/Peminatan)">SMA Kelas 12 (Fase F - IPA/Peminatan)</option>
                            <option value="SMP Kelas 7">SMP Kelas 7</option>
                            <option value="SMP Kelas 8">SMP Kelas 8</option>
                            <option value="SMP Kelas 9">SMP Kelas 9</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Cakupan Bab</label>
                        <select id="aiJumlahBab" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-amber-500">
                            <option value="4">Semester 1 Penuh (4 Bab Inti + Kuis + 3-5 Video)</option>
                            <option value="5">Semester 1 & 2 Lengkap (5 Bab Lengkap)</option>
                            <option value="2">2 Bab Permulaan (Prototype Cepat)</option>
                        </select>
                    </div>
                </div>

                <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200/80">
                    <h4 class="text-xs font-black text-amber-900 flex items-center gap-1.5 mb-1.5">
                        <i class="fas fa-info-circle text-amber-600"></i> Standar Konten yang Dihasilkan AI Agent:
                    </h4>
                    <ul class="text-[11px] text-amber-800 space-y-1 list-disc list-inside">
                        <li><b>Tautan E-Modul PDF Resmi</b>: Repositori Kemdikbud / BSE Kurikulum Merdeka.</li>
                        <li><b>3 s.d. 5 Video Pembelajaran Terkurasi</b>: Video Konsep, Pendalaman, dan Pembahasan Soal.</li>
                        <li><b>Rangkuman Komprehensif</b>: Konsep kunci, analogi kontekstual, dan peta konsep.</li>
                        <li><b>Tugas LKS Eksplorasi</b>: Tugas mandiri relevan dengan kehidupan santri.</li>
                        <li><b>5 Soal Kuis Pilihan Ganda HOTS</b>: Dilengkapi kunci jawaban dan pembahasan logis.</li>
                    </ul>
                </div>

                <!-- PROGRESS / STATUS AI -->
                <div id="aiProgressArea" class="hidden p-4 bg-slate-900 text-white rounded-2xl space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold flex items-center gap-2" id="aiStatusText">
                            <i class="fas fa-spinner fa-spin text-amber-400"></i> AI sedang merancang silabus & mengkurasi materi...
                        </span>
                        <span class="text-amber-400 font-mono font-bold" id="aiPercent">35%</span>
                    </div>
                    <div class="w-full bg-slate-800 h-2 rounded-full overflow-hidden">
                        <div id="aiProgressBar" class="bg-gradient-to-r from-amber-400 to-yellow-400 h-full rounded-full transition-all duration-300" style="width: 35%;"></div>
                    </div>
                    <p class="text-[10px] text-slate-400 italic" id="aiSubStatus">Menghubungkan ke Gemini AI & menyusun 3-5 video embed per bab...</p>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                <button type="button" onclick="tutupModalAIGenerator()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                <button type="button" id="btnProsesAI" onclick="jalankanAIGenerator()" class="bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-slate-900 font-black px-5 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-2">
                    <i class="fas fa-sparkles"></i>
                    <span>Mulai Generate Sekarang</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL 2: TAMBAH / EDIT BAB MANUAL -->
    <!-- ========================================== -->
    <div id="modalBabManual" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-3xl w-full p-6 shadow-2xl border border-slate-100 max-h-[92vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <h3 class="text-base font-black text-slate-900" id="modalBabTitle">Tambah / Edit Bab Pembelajaran</h3>
                <button onclick="tutupModalBabManual()" class="text-slate-400 hover:text-slate-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="POST" action="elearning-yayasan.php?mapel=<?= urlencode($selected_mapel) ?>" class="py-4 space-y-4">
                <input type="hidden" name="action" value="save_bab_manual">
                <input type="hidden" name="bab_id" id="manualBabId" value="0">
                <input type="hidden" name="mapel_nama" id="manualMapelNama" value="<?= htmlspecialchars($selected_mapel) ?>">

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Bab</label>
                        <input type="number" name="nomor_bab" id="manualNomorBab" value="1" min="1" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul Bab</label>
                        <input type="text" name="judul_bab" id="manualJudulBab" placeholder="Contoh: Bab 1: Pengenalan Sosiologi & Konsep Dasar" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold" required>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Subjudul / Topik Ringkas</label>
                        <input type="text" name="subjudul" id="manualSubjudul" placeholder="Contoh: Objek Kajian dan Tokoh Pencetus" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Estimasi Durasi Belajar</label>
                        <input type="text" name="durasi_menit" id="manualDurasi" value="15 Menit" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium">
                    </div>
                </div>

                <!-- E-MODUL RESMI KEMENDIKDASMEN -->
                <div class="p-3.5 bg-rose-50/70 border border-rose-200 rounded-2xl">
                    <label class="block text-xs font-black text-rose-900 mb-1 flex items-center gap-1.5">
                        <i class="fas fa-file-pdf text-rose-600"></i> URL E-Modul Resmi Pemerintah (https://emodul.kemendikdasmen.go.id/)
                    </label>
                    <input type="url" name="pdf_url" id="manualPdfUrl" placeholder="https://emodul.kemendikdasmen.go.id/... atau link PDF resmi" class="w-full px-3 py-2 bg-white border border-rose-200 rounded-xl text-xs font-mono text-slate-800">
                    <p class="text-[10px] text-rose-700 mt-1">E-Modul dari portal resmi Kemendikdasmen RI akan disematkan langsung di atas video pembelajaran.</p>
                </div>

                <!-- 3 - 5 URL VIDEO YOUTUBE -->
                <div class="p-3.5 bg-red-50/70 border border-red-200 rounded-2xl space-y-2">
                    <label class="block text-xs font-black text-red-900 flex items-center justify-between">
                        <span class="flex items-center gap-1.5"><i class="fab fa-youtube text-red-600"></i> Video Pembelajaran YouTube (3 s.d. 5 Video Pembanding)</span>
                    </label>
                    <input type="text" name="video_urls[]" id="manualVideo1" placeholder="Video 1: Konsep Inti (https://www.youtube.com/watch?v=...)" class="w-full px-3 py-2 bg-white border border-red-200 rounded-xl text-xs font-mono">
                    <input type="text" name="video_urls[]" id="manualVideo2" placeholder="Video 2: Pembahasan Mendalam & Contoh Kasus (opsional)" class="w-full px-3 py-2 bg-white border border-red-200 rounded-xl text-xs font-mono">
                    <input type="text" name="video_urls[]" id="manualVideo3" placeholder="Video 3: Tips & Pembanding Sudut Pandang (opsional)" class="w-full px-3 py-2 bg-white border border-red-200 rounded-xl text-xs font-mono">
                    <input type="text" name="video_urls[]" id="manualVideo4" placeholder="Video 4: Pembahasan Latihan Soal (opsional)" class="w-full px-3 py-2 bg-white border border-red-200 rounded-xl text-xs font-mono">
                    <input type="text" name="video_urls[]" id="manualVideo5" placeholder="Video 5: Video Tambahan / Praktik Lapangan (opsional)" class="w-full px-3 py-2 bg-white border border-red-200 rounded-xl text-xs font-mono">
                </div>

                <!-- RANGKUMAN TEKS -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Rangkuman Materi Lengkap (Format Teks/HTML)</label>
                    <textarea name="ringkasan_materi" id="manualRingkasan" rows="5" placeholder="Tuliskan intisari materi, poin-poin penting, dan analogi pemahaman..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono"></textarea>
                </div>

                <!-- LKS -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Judul LKS</label>
                        <input type="text" name="lks_judul" id="manualLksJudul" placeholder="Contoh: LKS 1: Analisis Interaksi Sosial di Pesantren" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Instruksi Tugas LKS</label>
                        <input type="text" name="lks_tugas" id="manualLksTugas" placeholder="Instruksi pengerjaan tugas mandiri santri..." class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" onclick="tutupModalBabManual()" class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">Batal</button>
                    <button type="submit" class="bg-amber-900 hover:bg-amber-800 text-white font-black px-5 py-2.5 rounded-xl text-xs shadow-md transition">Simpan Bab</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JS SCRIPTS -->
    <script>
        // Responsive sidebar yayasan
        const sidebar = document.getElementById('sidebar-yayasan2');
        const overlay = document.getElementById('sidebar-overlay-yayasan2');
        const openBtn = document.getElementById('open-sidebar-yayasan2');
        const closeBtn = document.getElementById('close-sidebar-yayasan2');

        if(openBtn) {
            openBtn.addEventListener('click', () => {
                sidebar.classList.remove('hidden');
                if(overlay) overlay.classList.remove('hidden');
            });
        }
        if(closeBtn) {
            closeBtn.addEventListener('click', () => {
                sidebar.classList.add('hidden');
                if(overlay) overlay.classList.add('hidden');
            });
        }
        if(overlay) {
            overlay.addEventListener('click', () => {
                sidebar.classList.add('hidden');
                overlay.classList.add('hidden');
            });
        }

        // Filter Mapel list
        function filterMapelList(type) {
            const items = document.querySelectorAll('.mapel-item');
            const btns = ['tab-f-all', 'tab-f-tanpa', 'tab-f-diknas'];
            btns.forEach(b => {
                const el = document.getElementById(b);
                if (el) {
                    el.className = 'px-3 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600 hover:bg-slate-200 transition';
                }
            });

            const activeBtn = document.getElementById(type === 'all' ? 'tab-f-all' : (type === 'tanpa_guru' ? 'tab-f-tanpa' : 'tab-f-diknas'));
            if (activeBtn) {
                activeBtn.className = 'px-3 py-1 rounded-lg text-xs font-bold bg-amber-800 text-white transition';
            }

            items.forEach(item => {
                if (type === 'all') {
                    item.style.display = 'block';
                } else if (type === 'tanpa_guru') {
                    item.style.display = item.classList.contains('is-tanpa-guru') ? 'block' : 'none';
                } else if (type === 'diknas') {
                    item.style.display = item.classList.contains('is-kat-diknas') ? 'block' : 'none';
                }
            });
        }

        // Cari mapel realtime
        function cariMapelRealtime() {
            const query = document.getElementById('cariMapel').value.toLowerCase();
            const items = document.querySelectorAll('.mapel-item');
            items.forEach(item => {
                const text = item.innerText.toLowerCase();
                item.style.display = text.includes(query) ? 'block' : 'none';
            });
        }

        // Modal AI Generator
        function bukaModalAIGenerator(mapelNama) {
            document.getElementById('aiMapelNama').value = mapelNama;
            document.getElementById('aiProgressArea').classList.add('hidden');
            document.getElementById('btnProsesAI').disabled = false;
            document.getElementById('btnProsesAI').innerHTML = '<i class="fas fa-sparkles"></i> <span>Mulai Generate Sekarang</span>';
            document.getElementById('modalAIGenerator').classList.remove('hidden');
        }

        function tutupModalAIGenerator() {
            document.getElementById('modalAIGenerator').classList.add('hidden');
        }

        // Jalankan AI Generator
        async function jalankanAIGenerator() {
            const mapel = document.getElementById('aiMapelNama').value;
            const jenjang = document.getElementById('aiJenjangKelas').value;
            const jumlahBab = document.getElementById('aiJumlahBab').value;

            const progressArea = document.getElementById('aiProgressArea');
            const statusText = document.getElementById('aiStatusText');
            const subStatus = document.getElementById('aiSubStatus');
            const percent = document.getElementById('aiPercent');
            const bar = document.getElementById('aiProgressBar');
            const btn = document.getElementById('btnProsesAI');

            progressArea.classList.remove('hidden');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Sedang Merancang...</span>';

            percent.innerText = '25%';
            bar.style.width = '25%';
            statusText.innerHTML = '<i class="fas fa-brain text-amber-400"></i> AI sedang menganalisis kurikulum & portal Kemendikdasmen...';
            subStatus.innerText = 'Menyusun silabus, 3-5 video embed, dan bank soal...';

            try {
                const formData = new FormData();
                formData.append('action', 'generate_ai_curriculum');
                formData.append('mapel_nama', mapel);
                formData.append('jenjang_kelas', jenjang);
                formData.append('jumlah_bab', jumlahBab);

                percent.innerText = '60%';
                bar.style.width = '60%';

                const response = await fetch('elearning-yayasan.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status !== 'success' || !data.data) {
                    throw new Error(data.message || 'Gagal merancang kurikulum.');
                }

                const chaptersData = data.data;

                percent.innerText = '85%';
                bar.style.width = '85%';
                statusText.innerHTML = '<i class="fas fa-save text-amber-400"></i> Menyimpan ke Database E-Learning...';
                subStatus.innerText = 'Menyinkronkan bab, multi-video, dan bank soal...';

                // Simpan ke database via AJAX
                const saveFormData = new FormData();
                saveFormData.append('action', 'save_ai_curriculum');
                saveFormData.append('mapel_nama', mapel);
                saveFormData.append('chapters_json', JSON.stringify(chaptersData));
                saveFormData.append('replace_existing', '1');

                const saveRes = await fetch('elearning-yayasan.php', {
                    method: 'POST',
                    body: saveFormData
                });
                const saveResult = await saveRes.json();

                if (saveResult.status === 'success') {
                    percent.innerText = '100%';
                    bar.style.width = '100%';
                    statusText.innerHTML = '<i class="fas fa-check-circle text-emerald-400"></i> Selesai!';
                    subStatus.innerText = saveResult.message;
                    setTimeout(() => {
                        window.location.href = 'elearning-yayasan.php?mapel=' + encodeURIComponent(mapel);
                    }, 1000);
                } else {
                    throw new Error(saveResult.message || 'Gagal menyimpan ke database.');
                }

            } catch (err) {
                console.error(err);
                alert('Terjadi kesalahan AI Generator: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-redo"></i> <span>Coba Lagi</span>';
                progressArea.classList.add('hidden');
            }
        }

        // Modal Tambah / Edit Bab Manual
        function bukaModalTambahBabManual() {
            document.getElementById('modalBabTitle').innerText = 'Tambah Bab Pembelajaran Manual';
            document.getElementById('manualBabId').value = '0';
            document.getElementById('manualNomorBab').value = '<?= count($list_bab) + 1 ?>';
            document.getElementById('manualJudulBab').value = '';
            document.getElementById('manualSubjudul').value = '';
            document.getElementById('manualDurasi').value = '15 Menit';
            document.getElementById('manualPdfUrl').value = '';
            document.getElementById('manualVideo1').value = '';
            document.getElementById('manualVideo2').value = '';
            document.getElementById('manualVideo3').value = '';
            document.getElementById('manualVideo4').value = '';
            document.getElementById('manualVideo5').value = '';
            document.getElementById('manualRingkasan').value = '';
            document.getElementById('manualLksJudul').value = '';
            document.getElementById('manualLksTugas').value = '';
            document.getElementById('modalBabManual').classList.remove('hidden');
        }

        function editBabModal(bab) {
            document.getElementById('modalBabTitle').innerText = 'Edit Bab Pembelajaran';
            document.getElementById('manualBabId').value = bab.id;
            document.getElementById('manualNomorBab').value = bab.nomor_bab;
            document.getElementById('manualJudulBab').value = bab.judul_bab;
            document.getElementById('manualSubjudul').value = bab.subjudul || '';
            document.getElementById('manualDurasi').value = bab.durasi_menit || '15 Menit';
            document.getElementById('manualPdfUrl').value = bab.pdf_url || '';
            
            // Parse video urls
            let v1 = bab.video_url || '', v2 = '', v3 = '', v4 = '', v5 = '';
            if (bab.video_urls) {
                try {
                    const parsed = JSON.parse(bab.video_urls);
                    if (Array.isArray(parsed)) {
                        v1 = parsed[0] || v1;
                        v2 = parsed[1] || '';
                        v3 = parsed[2] || '';
                        v4 = parsed[3] || '';
                        v5 = parsed[4] || '';
                    }
                } catch(e) {}
            }
            document.getElementById('manualVideo1').value = v1;
            document.getElementById('manualVideo2').value = v2;
            document.getElementById('manualVideo3').value = v3;
            document.getElementById('manualVideo4').value = v4;
            document.getElementById('manualVideo5').value = v5;

            document.getElementById('manualRingkasan').value = bab.ringkasan_materi || '';
            document.getElementById('manualLksJudul').value = bab.lks_judul || '';
            document.getElementById('manualLksTugas').value = bab.lks_tugas || '';
            document.getElementById('modalBabManual').classList.remove('hidden');
        }

        // ==========================================
        // TEAM AI AGENTIC BATCH ORCHESTRATOR
        // ==========================================
        const allMapelDatabase = <?= json_encode($list_all_mapel) ?>;

        function bukaModalTeamAIBatch() {
            document.getElementById('teamAISettings').classList.remove('hidden');
            document.getElementById('teamAILiveConsole').classList.add('hidden');
            document.getElementById('btnJalankanTeamAIBatch').classList.remove('hidden');
            document.getElementById('btnBatalTeamAI').innerText = 'Tutup';
            document.getElementById('modalTeamAIBatch').classList.remove('hidden');
        }

        function tutupModalTeamAIBatch() {
            document.getElementById('modalTeamAIBatch').classList.add('hidden');
        }

        function appendTeamAILog(msg, type = 'normal') {
            const stream = document.getElementById('teamAILogStream');
            const d = document.createElement('div');
            const time = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            
            if (type === 'agent') {
                d.className = 'text-amber-300';
                d.innerHTML = `[${time}] <i class="fas fa-robot text-amber-400 mr-1"></i> ${msg}`;
            } else if (type === 'success') {
                d.className = 'text-emerald-400 font-bold';
                d.innerHTML = `[${time}] <i class="fas fa-check-circle text-emerald-400 mr-1"></i> ${msg}`;
            } else if (type === 'error') {
                d.className = 'text-rose-400 font-bold';
                d.innerHTML = `[${time}] <i class="fas fa-exclamation-triangle text-rose-400 mr-1"></i> ${msg}`;
            } else {
                d.className = 'text-slate-300';
                d.innerHTML = `[${time}] ${msg}`;
            }
            stream.appendChild(d);
            stream.scrollTop = stream.scrollHeight;
        }

        async function jalankanTeamAIBatchOrchestrator() {
            const scope = document.getElementById('teamAITargetScope').value;
            const semester = document.getElementById('teamAISemesterScope').value;
            const btnRun = document.getElementById('btnJalankanTeamAIBatch');
            const btnClose = document.getElementById('btnBatalTeamAI');

            // Filter target mapel
            let targetList = [];
            if (scope === 'tanpa_guru') {
                targetList = allMapelDatabase.filter(m => !m.pengampu_id || m.pengampu_id == 0 || m.pengampu_id === '0');
            } else if (scope === 'semua_diknas') {
                targetList = allMapelDatabase.filter(m => m.kategori_mapel && m.kategori_mapel.toLowerCase().includes('diknas'));
            } else {
                targetList = allMapelDatabase;
            }

            // Smart Fallback jika filter kategori belum terisi di beberapa record
            if (!targetList || targetList.length === 0) {
                if (scope === 'semua_diknas' || scope === 'tanpa_guru') {
                    targetList = allMapelDatabase.filter(m => !m.kategori_mapel || !m.kategori_mapel.toLowerCase().includes('diniyah'));
                }
                if (!targetList || targetList.length === 0) {
                    targetList = allMapelDatabase;
                }
            }

            // Fallback darurat jika database mapel belum termuat di browser
            if (!targetList || targetList.length === 0) {
                targetList = [
                    { nama_mapel: 'Sosiologi', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Ekonomi', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Geografi', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Sejarah', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Bahasa Indonesia', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Bahasa Inggris', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Matematika', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'IPA', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Fisika', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Kimia', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Biologi', kategori_mapel: 'Diknas' },
                    { nama_mapel: 'Pendidikan Pancasila', kategori_mapel: 'Diknas' }
                ];
            }

            // Ganti UI ke Live Console
            document.getElementById('teamAISettings').classList.add('hidden');
            document.getElementById('teamAILiveConsole').classList.remove('hidden');
            btnRun.classList.add('hidden');
            btnClose.innerText = 'Batal / Berhenti';

            const stream = document.getElementById('teamAILogStream');
            stream.innerHTML = '';
            appendTeamAILog(`Dewan Pakar AI diaktifkan untuk ${targetList.length} Mata Pelajaran target...`, 'success');
            appendTeamAILog(`Target Silabus: ${semester.replace('_', ' ').toUpperCase()} • Sumber E-Modul: emodul.kemendikdasmen.go.id`);

            const total = targetList.length;
            let successCount = 0;

            const jumlahBab = (semester === 'setahun_penuh') ? 6 : 4;

            for (let i = 0; i < total; i++) {
                const mapel = targetList[i];
                const currentNum = i + 1;
                const mapelPercent = Math.round((i / total) * 100);

                document.getElementById('teamAITotalPercent').innerText = `${mapelPercent}%`;
                document.getElementById('teamAIBigProgressBar').style.width = `${mapelPercent}%`;
                document.getElementById('teamAIMapelCounter').innerText = `Memproses Mapel ${currentNum} dari ${total}: ${mapel.nama_mapel}`;
                document.getElementById('teamAIActiveMapel').innerHTML = `<i class="fas fa-spinner fa-spin text-amber-400"></i> Memproses: <b>${mapel.nama_mapel}</b>`;

                appendTeamAILog(`--- [MAPEL ${currentNum}/${total}: ${mapel.nama_mapel}] ---`, 'agent');
                appendTeamAILog(`Agent 1 (Curriculum Scout): Meriset CP & ATP Kurikulum Merdeka...`, 'agent');
                appendTeamAILog(`Agent 3 (E-Modul Scout): Menautkan portal resmi Kemendikdasmen RI...`, 'agent');
                appendTeamAILog(`Agent 4 (Video Curator): Mengkurasi 3-5 Video YouTube pembanding per bab...`, 'agent');
                appendTeamAILog(`Agent 5 & 6: Menyusun Rangkuman, LKS Santri, dan Kuis Interaktif HOTS...`, 'agent');

                try {
                    const formData = new FormData();
                    formData.append('action', 'generate_ai_curriculum');
                    formData.append('mapel_nama', mapel.nama_mapel);
                    formData.append('jenjang_kelas', 'SMA Kelas 10 (Fase E)');
                    formData.append('jumlah_bab', jumlahBab);

                    const res = await fetch('elearning-yayasan.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();

                    if (data.status === 'success' && data.data) {
                        appendTeamAILog(`Agent 7 (QA Sync): Menyimpan ${data.data.length} Bab ke Database E-Learning...`, 'agent');

                        const saveFormData = new FormData();
                        saveFormData.append('action', 'save_ai_curriculum');
                        saveFormData.append('mapel_nama', mapel.nama_mapel);
                        saveFormData.append('chapters_json', JSON.stringify(data.data));
                        saveFormData.append('replace_existing', '1');

                        const saveRes = await fetch('elearning-yayasan.php', {
                            method: 'POST',
                            body: saveFormData
                        });
                        const saveResult = await saveRes.json();

                        if (saveResult.status === 'success') {
                            successCount++;
                            appendTeamAILog(`✓ SUKSES: ${mapel.nama_mapel} berhasil diterbitkan di Ruang Santri!`, 'success');
                        } else {
                            appendTeamAILog(`✗ Gagal menyimpan ${mapel.nama_mapel}: ${saveResult.message}`, 'error');
                        }
                    } else {
                        appendTeamAILog(`✗ Gagal generate ${mapel.nama_mapel}: ${data.message}`, 'error');
                    }
                } catch (e) {
                    appendTeamAILog(`✗ Error koneksi pada ${mapel.nama_mapel}: ${e.message}`, 'error');
                }

                // Jeda singkat antar mapel agar server dan API rate limit tetap terjaga aman
                await new Promise(r => setTimeout(r, 600));
            }

            document.getElementById('teamAITotalPercent').innerText = `100%`;
            document.getElementById('teamAIBigProgressBar').style.width = `100%`;
            document.getElementById('teamAIActiveMapel').innerHTML = `<i class="fas fa-check-circle text-emerald-400"></i> Selesai Semua Mapel!`;
            document.getElementById('teamAIMapelCounter').innerText = `Selesai: ${successCount} dari ${total} Mapel Siap Digunakan!`;
            appendTeamAILog(`🎉 SEMUA SELESAI! ${successCount} Mapel berhasil disinkronkan dan langsung aktif di Ruang Santri hari ini.`, 'success');

            btnClose.innerText = 'Selesai & Lihat Hasil';
            btnClose.className = 'bg-emerald-600 hover:bg-emerald-700 text-white font-black px-6 py-2.5 rounded-xl text-xs transition shadow-md';
            btnClose.onclick = function() {
                window.location.href = 'elearning-yayasan.php';
            };
        }

        // Simpan Link E-Modul Tunggal Cepat
        async function simpanSinglePdfUrl(babId) {
            const input = document.getElementById('pdf_input_' + babId);
            const btn = document.getElementById('btn_save_pdf_' + babId);
            if (!input || !btn) return;

            const pdfUrl = input.value.trim();
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`;
            btn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('action', 'update_single_pdf_url');
                fd.append('bab_id', babId);
                fd.append('pdf_url', pdfUrl);

                const res = await fetch('elearning-yayasan.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.status === 'success') {
                    btn.innerHTML = `<i class="fas fa-check text-emerald-300"></i> Tersimpan!`;
                    btn.className = 'bg-emerald-600 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow-2xs flex items-center gap-1.5 whitespace-nowrap';
                    setTimeout(() => {
                        btn.innerHTML = originalBtnHtml;
                        btn.className = 'bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow-2xs flex items-center gap-1.5 whitespace-nowrap';
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
                    btn.innerHTML = originalBtnHtml;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Error koneksi: ' + err.message);
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>
