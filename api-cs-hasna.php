<?php
/**
 * api-cs-hasna.php
 * Backend Engine untuk CS AI Hasna (Pusat Informasi & SPMB Villa Quran Baron Malang)
 * Fitur:
 * - Multi-API Free Tier Rotation & Auto-Failover (API 1 -> API 2 -> API 3 -> API 4)
 * - Fonnte WhatsApp Notification ke Admin saat Lead baru masuk
 * - Integrasi Database Leads & Pipeline
 * - Mesin Pembelajar (Continuous Learning Engine dari FAQ & Tanya Jawab)
 * - Riwayat Sesi & Memori Percakapan Singkat
 */

ini_set('display_errors', 0);
error_reporting(0);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/koneksi.php';
if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

// -----------------------------------------------------------------------------
// 1. OTOMATIS INISIALISASI TABEL DATABASE JIKA BELUM ADA
// -----------------------------------------------------------------------------
$conn->query("CREATE TABLE IF NOT EXISTS cs_ai_config (
    id INT PRIMARY KEY DEFAULT 1,
    bot_name VARCHAR(100) DEFAULT 'Hasna',
    greeting_text TEXT,
    api_key_1 VARCHAR(255) NULL,
    api_key_2 VARCHAR(255) NULL,
    api_key_3 VARCHAR(255) NULL,
    api_key_4 VARCHAR(255) NULL,
    fonnte_token VARCHAR(255) NULL,
    admin_wa VARCHAR(50) DEFAULT '6285189918115',
    notify_admin TINYINT(1) DEFAULT 1,
    system_prompt_spmb LONGTEXT NULL,
    temperature DECIMAL(2,1) DEFAULT 0.5,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS cs_ai_learning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori VARCHAR(100) DEFAULT 'SPMB',
    pertanyaan VARCHAR(255) NOT NULL,
    jawaban_standar TEXT NOT NULL,
    sumber ENUM('admin', 'auto_learned', 'wali_santri') DEFAULT 'admin',
    frekuensi_ditanya INT DEFAULT 1,
    status ENUM('aktif', 'review', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS cs_chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_token VARCHAR(64) UNIQUE NOT NULL,
    nama VARCHAR(150) NOT NULL,
    whatsapp VARCHAR(50) NOT NULL,
    ip_address VARCHAR(50) NULL,
    total_pesan INT DEFAULT 0,
    ringkasan_minat TEXT NULL,
    status_lead VARCHAR(50) DEFAULT 'Level 1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS cs_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    sender ENUM('user', 'hasna') NOT NULL,
    pesan TEXT NOT NULL,
    api_key_used VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (session_id)
)");

// Pastikan baris default konfigurasi ada
$checkCfg = $conn->query("SELECT * FROM cs_ai_config WHERE id = 1 LIMIT 1");
if (!$checkCfg || $checkCfg->num_rows === 0) {
    $defKey1 = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
    $defFonnte = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : '';
    $defAdminWa = defined('YAYASAN_WA_RECIPIENT') ? YAYASAN_WA_RECIPIENT : '6285189918115';

    $defPrompt = "Anda adalah 'Hasna', Customer Service Virtual resmi dari Pesantren Villa Quran Baron Malang (SMP & SMA Tahfidz Qur'an Berasrama).

KARAKTER & SIKAP ANDA:
1. Sopan, ramah, bernuansa Islami yang teduh, dan selalu menyapa Ayah/Bunda dengan santun (misal: 'Ahlan wa sahlan Ayah/Bunda...').
2. Jawaban Anda harus SINGKAT, PADAT, JELAS, dan MUDAH DIPAHAMI. Hindari kalimat yang berbelit-belit atau terlalu panjang.
3. Gunakan formatting bullet points atau baris rapi bila memberikan rincian.
4. Di akhir jawaban, tawarkan bantuan berikutnya atau sarankan untuk konsultasi langsung / survei lokasi via WhatsApp ke Panitia SPMB.

INFORMASI DASAR PESANTREN & SPMB:
- Nama Pesantren: Villa Quran Indonesia (Kampus Putra & Putri)
- Lokasi: Baron, Malang, Jawa Timur (lingkungan sejuk, tenang, asri pegunungan, sangat kondusif untuk menghafal Al-Qur'an).
- Jenjang Pendidikan: SMP & SMA Tahfidz Al-Qur'an (Full Boarding / Asrama).
- Kurikulum: Integrasi Kurikulum Merdeka (Kemendikbudristek) + Kurikulum Kepesantrenan + Target Mutqin Tahfidz 30 Juz + Bahasa Arab & Inggris + Adab & Karakter Akhlakul Karimah.
- Fasilitas: Asrama bersih & representatif, masjid utama, ruang kelas modern ber-AC, makan bergizi 3x sehari, laundry, lapangan olahraga (panahan, futsal, dll), gazebo belajar alam.
- Estimasi Biaya SPMB 2026/2027:
  * Biaya Formulir Pendaftaran: Rp 350.000
  * Infaq Pengembangan / Uang Pangkal: Mulai Rp 12.500.000 (tersedia skema beasiswa/keringanan gelombang awal)
  * SPP Bulanan: Mulai Rp 1.650.000 (sudah meliputi asrama, makan 3x sehari, pendidikan, dan pembinaan musyrif 24 jam).
- Alur Pendaftaran: 1) Isi form online di website/SPMB, 2) Pembayaran formulir, 3) Tes Kesiapan & Observasi Wawancara, 4) Pengumuman, 5) Daftar Ulang.
- Narahubung Panitia SPMB: 0851-8991-8115.";

    $defGreeting = "Assalamu'alaikum Ayah/Bunda! Saya Hasna, asisten informasi resmi Villa Quran Baron Malang. Ada yang bisa Hasna bantu seputar program tahfidz, fasilitas asrama, atau pendaftaran santri baru (SPMB)?";

    $stmtDef = $conn->prepare("INSERT INTO cs_ai_config (id, bot_name, greeting_text, api_key_1, fonnte_token, admin_wa, system_prompt_spmb) VALUES (1, 'Hasna', ?, ?, ?, ?, ?)");
    $stmtDef->bind_param("sssss", $defGreeting, $defKey1, $defFonnte, $defAdminWa, $defPrompt);
    $stmtDef->execute();
}

// -----------------------------------------------------------------------------
// 2. HELPER FUNCTIONS
// -----------------------------------------------------------------------------
function getCsConfig($conn) {
    $res = $conn->query("SELECT * FROM cs_ai_config WHERE id = 1 LIMIT 1");
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    return [
        'bot_name' => 'Hasna',
        'greeting_text' => 'Assalamu\'alaikum! Ada yang bisa Hasna bantu?',
        'api_key_1' => '',
        'api_key_2' => '',
        'api_key_3' => '',
        'api_key_4' => '',
        'fonnte_token' => '',
        'admin_wa' => '6285189918115',
        'notify_admin' => 1,
        'system_prompt_spmb' => '',
        'temperature' => 0.5
    ];
}

function kirimNotifWaFonnte($target, $pesan, $token) {
    if (empty($token) || empty($target) || empty($pesan)) return false;
    $target = preg_replace('/[^0-9]/', '', $target);
    if (substr($target, 0, 1) === '0') {
        $target = '62' . substr($target, 1);
    }
    $ch = curl_init('https://api.fonnte.com/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'target' => $target,
            'message' => $pesan
        ]),
        CURLOPT_HTTPHEADER => ["Authorization: $token"],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

/**
 * Panggil Gemini dengan Rotasi Multi-API Key (Failover otomatis ke Key 2, 3, 4 jika Key 1 habis kuota/error)
 */
function callGeminiWithRotation($prompt, $config, $conn) {
    $keys = [
        ['label' => 'API Key 1', 'key' => trim($config['api_key_1'] ?? '')],
        ['label' => 'API Key 2', 'key' => trim($config['api_key_2'] ?? '')],
        ['label' => 'API Key 3', 'key' => trim($config['api_key_3'] ?? '')],
        ['label' => 'API Key 4', 'key' => trim($config['api_key_4'] ?? '')],
    ];

    // Filter key yang tidak kosong
    $activeKeys = array_values(array_filter($keys, function($k) {
        return !empty($k['key']);
    }));

    if (empty($activeKeys)) {
        // Cek fallback konstanta GEMINI_API_KEY jika ada
        if (defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY)) {
            $activeKeys[] = ['label' => 'Config-Key', 'key' => GEMINI_API_KEY];
        } else {
            return [
                'success' => false,
                'message' => 'Belum ada API Key Gemini yang dikonfigurasi di Ruang PSB.'
            ];
        }
    }

    $errors = [];
    foreach ($activeKeys as $idx => $item) {
        $apiKey = $item['key'];
        $label = $item['label'];

        // Endpoint Gemini 2.5 Flash
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey);

        $payload = [
            "contents" => [
                [
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "generationConfig" => [
                "temperature" => (float)($config['temperature'] ?? 0.5),
                "maxOutputTokens" => 1000
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (!empty($text)) {
                return [
                    'success' => true,
                    'text' => trim($text),
                    'api_used' => $label
                ];
            }
        }

        // Jika kena limit 429 atau error lain, catat dan beralih ke key berikutnya
        $errors[] = "$label (HTTP $httpCode: $curlErr)";
    }

    // Jika semua key habis / gagal, coba AI Lokal jika tersedia sebagai penyelamat terakhir
    $localAiUrl = defined('LOCAL_AI_URL') ? LOCAL_AI_URL : '';
    if (!empty($localAiUrl)) {
        $chLocal = curl_init(rtrim($localAiUrl, '/') . '/api/generate');
        curl_setopt_array($chLocal, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                "model" => defined('LOCAL_AI_MODEL') ? LOCAL_AI_MODEL : "qwen2.5-coder:7b",
                "prompt" => $prompt,
                "stream" => false
            ]),
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        $localResp = curl_exec($chLocal);
        $localHttp = curl_getinfo($chLocal, CURLINFO_HTTP_CODE);
        curl_close($chLocal);

        if ($localHttp === 200 && $localResp) {
            $ld = json_decode($localResp, true);
            if (!empty($ld['response'])) {
                return [
                    'success' => true,
                    'text' => trim($ld['response']),
                    'api_used' => 'AI Lokal (Ollama Emergency Fallback)'
                ];
            }
        }
    }

    return [
        'success' => false,
        'message' => 'Semua kuota API Free Tier sedang penuh atau tidak dapat dijangkau. Rincian: ' . implode(', ', $errors)
    ];
}

// -----------------------------------------------------------------------------
// 3. ACTION ROUTER
// -----------------------------------------------------------------------------
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if (empty($action)) {
    $rawInput = json_decode(file_get_contents('php://input'), true);
    if ($rawInput && !empty($rawInput['action'])) {
        $action = $rawInput['action'];
    }
}

$cfg = getCsConfig($conn);

// -----------------------------------------------------------------------------
// ACTION: START SESSION (Pengunjung Memasukkan Nama & WhatsApp di Web Widget)
// -----------------------------------------------------------------------------
if ($action === 'start_session') {
    $nama = trim($_POST['nama'] ?? $rawInput['nama'] ?? '');
    $wa = trim($_POST['whatsapp'] ?? $rawInput['whatsapp'] ?? '');

    if (empty($nama) || empty($wa)) {
        echo json_encode(['status' => 'error', 'message' => 'Nama dan Nomor WhatsApp wajib diisi agar Hasna dapat menyapa.']);
        exit;
    }

    $waClean = preg_replace('/[^0-9]/', '', $wa);
    if (strlen($waClean) < 8) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak valid.']);
        exit;
    }
    if (substr($waClean, 0, 1) === '0') {
        $waClean = '62' . substr($waClean, 1);
    }

    $sessionToken = bin2hex(random_bytes(16));
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    // Simpan ke sesi chat
    $stmt = $conn->prepare("INSERT INTO cs_chat_sessions (session_token, nama, whatsapp, ip_address, total_pesan) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $sessionToken, $nama, $waClean, $ip);
    $stmt->execute();
    $sessionId = $conn->insert_id;

    // Simpan pesan sambutan awal Hasna
    $botGreeting = !empty($cfg['greeting_text']) ? $cfg['greeting_text'] : "Assalamu'alaikum Ayah/Bunda! Ada yang bisa Hasna bantu?";
    // Sapa dengan nama pengunjung
    $botGreetingPersonal = "Assalamu'alaikum Ayah/Bunda $nama! " . ltrim($botGreeting, "Assalamu'alaikum Ayah/Bunda! ");

    $stmtMsg = $conn->prepare("INSERT INTO cs_chat_messages (session_id, sender, pesan) VALUES (?, 'hasna', ?)");
    $stmtMsg->bind_param("is", $sessionId, $botGreetingPersonal);
    $stmtMsg->execute();

    // Simpan juga ke tabel LEADS (Pipeline SPMB) agar sinkron dengan CRM
    $leadCheck = $conn->prepare("SELECT id FROM leads WHERE whatsapp = ? LIMIT 1");
    $leadCheck->bind_param("s", $waClean);
    $leadCheck->execute();
    $leadRes = $leadCheck->get_result();
    if ($leadRes && $leadRes->num_rows > 0) {
        $leadRow = $leadRes->fetch_assoc();
        $updLead = $conn->prepare("UPDATE leads SET nama = ?, sumber_info = 'CS AI Hasna (Web Chat)' WHERE id = ?");
        $updLead->bind_param("si", $nama, $leadRow['id']);
        $updLead->execute();
    } else {
        $insLead = $conn->prepare("INSERT INTO leads (nama, whatsapp, status, jenis_lead, sumber_info) VALUES (?, ?, 'Level 1', 'cs_hasna', 'CS AI Hasna (Web Chat)')");
        $insLead->bind_param("ss", $nama, $waClean);
        $insLead->execute();
    }

    // Notifikasi WhatsApp ke Admin Yayasan / Panitia PSB (jika diaktifkan)
    if (!empty($cfg['notify_admin']) && !empty($cfg['admin_wa']) && !empty($cfg['fonnte_token'])) {
        $notifMsg = "🔔 *PROSPEK BARU DARI CS AI HASNA!*\n\n" .
                    "👤 *Nama*: $nama\n" .
                    "📱 *WhatsApp*: https://wa.me/$waClean\n" .
                    "⏰ *Waktu*: " . date('d M Y - H:i') . " WIB\n" .
                    "🌐 *Saluran*: Live Web Chatbot Hasna\n\n" .
                    "_Calon wali santri saat ini sedang berkonsultasi dengan Hasna di website._";
        kirimNotifWaFonnte($cfg['admin_wa'], $notifMsg, $cfg['fonnte_token']);
    }

    echo json_encode([
        'status' => 'success',
        'session_token' => $sessionToken,
        'nama' => $nama,
        'bot_name' => $cfg['bot_name'] ?? 'Hasna',
        'initial_message' => $botGreetingPersonal
    ]);
    exit;
}

// -----------------------------------------------------------------------------
// ACTION: SEND MESSAGE (Pengunjung Mengirim Pesan ke Hasna)
// -----------------------------------------------------------------------------
if ($action === 'send_message') {
    $sessionToken = trim($_POST['session_token'] ?? $rawInput['session_token'] ?? '');
    $pesanUser = trim($_POST['pesan'] ?? $rawInput['pesan'] ?? '');

    if (empty($sessionToken) || empty($pesanUser)) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi chat atau pesan tidak boleh kosong.']);
        exit;
    }

    // Ambil data sesi
    $stmtSess = $conn->prepare("SELECT id, nama, whatsapp FROM cs_chat_sessions WHERE session_token = ? LIMIT 1");
    $stmtSess->bind_param("s", $sessionToken);
    $stmtSess->execute();
    $sessData = $stmtSess->get_result()->fetch_assoc();

    if (!$sessData) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi tidak ditemukan atau telah kedaluwarsa. Silakan refresh halaman.']);
        exit;
    }

    $sessionId = $sessData['id'];
    $namaUser = $sessData['nama'];

    // Simpan pesan user ke database
    $stmtUserMsg = $conn->prepare("INSERT INTO cs_chat_messages (session_id, sender, pesan) VALUES (?, 'user', ?)");
    $stmtUserMsg->bind_param("is", $sessionId, $pesanUser);
    $stmtUserMsg->execute();

    // Update total pesan di sesi
    $conn->query("UPDATE cs_chat_sessions SET total_pesan = total_pesan + 1 WHERE id = $sessionId");

    // Ambil 6 riwayat pesan terakhir untuk memori kontekstual (short-term memory)
    $resHist = $conn->query("SELECT sender, pesan FROM cs_chat_messages WHERE session_id = $sessionId ORDER BY id DESC LIMIT 6");
    $historyLines = [];
    if ($resHist && $resHist->num_rows > 0) {
        $rows = [];
        while ($r = $resHist->fetch_assoc()) {
            $rows[] = $r;
        }
        $rows = array_reverse($rows);
        foreach ($rows as $h) {
            $roleName = ($h['sender'] === 'user') ? "Ayah/Bunda ($namaUser)" : "Hasna";
            $historyLines[] = "$roleName: " . $h['pesan'];
        }
    }
    $historyText = implode("\n", $historyLines);

    // Ambil basis pengetahuan dari modul pembelajaran (Learning Engine)
    $learnedKb = [];
    $resLearn = $conn->query("SELECT pertanyaan, jawaban_standar FROM cs_ai_learning WHERE status = 'aktif' ORDER BY frekuensi_ditanya DESC LIMIT 15");
    if ($resLearn && $resLearn->num_rows > 0) {
        while ($lr = $resLearn->fetch_assoc()) {
            $learnedKb[] = "• Q: " . $lr['pertanyaan'] . " -> A: " . $lr['jawaban_standar'];
        }
    }
    $learnedContext = !empty($learnedKb) ? "\n\nKNOWLEDGE BASE TAMBAHAN YANG SUDAH DIPELAJARI:\n" . implode("\n", $learnedKb) : "";

    // Susun Master Prompt untuk Gemini
    $systemPrompt = $cfg['system_prompt_spmb'] ?? '';
    $finalPrompt = "SYSTEM PROMPT:\n" . $systemPrompt . $learnedContext . "\n\n" .
        "PETUNJUK JAWABAN:\n" .
        "- Calon wali santri bernama: $namaUser\n" .
        "- Jawablah dengan sopan, santun, bernuansa Islami, SINGKAT, PADAT, dan JELAS (maksimal 2-3 paragraf ringkas).\n" .
        "- Jika ditanya rincian biaya atau brosur, jelaskan estimasi dan tawarkan mengirimkan PDF lengkap via WA.\n\n" .
        "RIWAYAT PERCAKAPAN SEBELUMNYA:\n" .
        $historyText . "\n\n" .
        "JAWABAN HASNA (Langsung berikan jawaban teks yang ramah dan siap dibaca tanpa tanda kurung metadata):";

    // Panggil AI dengan rotasi multi-API
    $aiResult = callGeminiWithRotation($finalPrompt, $cfg, $conn);

    if ($aiResult['success']) {
        $jawabanHasna = $aiResult['text'];
        $apiUsed = $aiResult['api_used'];

        // Simpan jawaban Hasna ke database
        $stmtBotMsg = $conn->prepare("INSERT INTO cs_chat_messages (session_id, sender, pesan, api_key_used) VALUES (?, 'hasna', ?, ?)");
        $stmtBotMsg->bind_param("iss", $sessionId, $jawabanHasna, $apiUsed);
        $stmtBotMsg->execute();

        // Mesin Pembelajar: Deteksi apakah user menanyakan topik baru yang layak dicatat
        // Cek jika pertanyaan user mengandung kata tanya penting
        if (preg_match('/(biaya|daftar|syarat|lokasi|alamat|beasiswa|makanan|asrama|kamar|wali|kunjungan|pulang|perizinan|sakit|laundry|hp|gadget)/i', $pesanUser)) {
            // Update frekuensi jika sudah ada di knowledge
            $pesanClean = substr(strip_tags($pesanUser), 0, 100);
            $conn->query("UPDATE cs_ai_learning SET frekuensi_ditanya = frekuensi_ditanya + 1 WHERE pertanyaan LIKE '%" . $conn->real_escape_string($pesanClean) . "%'");
        }

        echo json_encode([
            'status' => 'success',
            'jawaban' => $jawabanHasna,
            'api_used' => $apiUsed
        ]);
        exit;
    } else {
        // Fallback sopan jika API limit
        $fallbackPesan = "Afwan Ayah/Bunda $namaUser, saat ini sistem jaringan server Hasna sedang menerima antrean sangat ramai. " .
            "Agar Ayah/Bunda segera mendapatkan informasi lengkap, silakan langsung hubungi Panitia SPMB kami via WhatsApp di nomor: *" . ($cfg['admin_wa'] ?? '0851-8991-8115') . "* 🙏";

        $errApi = 'Limit / Error';
        $stmtBotMsg = $conn->prepare("INSERT INTO cs_chat_messages (session_id, sender, pesan, api_key_used) VALUES (?, 'hasna', ?, ?)");
        $stmtBotMsg->bind_param("iss", $sessionId, $fallbackPesan, $errApi);
        $stmtBotMsg->execute();

        echo json_encode([
            'status' => 'success',
            'jawaban' => $fallbackPesan,
            'api_used' => 'Fallback Offline',
            'debug_error' => $aiResult['message'] ?? ''
        ]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// ACTION: GET SESSION CHAT HISTORY (Untuk Web Widget)
// -----------------------------------------------------------------------------
if ($action === 'get_history') {
    $sessionToken = trim($_GET['session_token'] ?? $rawInput['session_token'] ?? '');
    if (empty($sessionToken)) {
        echo json_encode(['status' => 'error', 'message' => 'Token sesi diperlukan.']);
        exit;
    }

    $stmtSess = $conn->prepare("SELECT id, nama FROM cs_chat_sessions WHERE session_token = ? LIMIT 1");
    $stmtSess->bind_param("s", $sessionToken);
    $stmtSess->execute();
    $sess = $stmtSess->get_result()->fetch_assoc();

    if (!$sess) {
        echo json_encode(['status' => 'error', 'message' => 'Sesi tidak ditemukan.']);
        exit;
    }

    $sessId = $sess['id'];
    $resMsg = $conn->query("SELECT sender, pesan, created_at FROM cs_chat_messages WHERE session_id = $sessId ORDER BY id ASC");
    $messages = [];
    if ($resMsg && $resMsg->num_rows > 0) {
        while ($m = $resMsg->fetch_assoc()) {
            $messages[] = [
                'sender' => $m['sender'],
                'pesan' => $m['pesan'],
                'waktu' => date('H:i', strtotime($m['created_at']))
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'nama' => $sess['nama'],
        'messages' => $messages
    ]);
    exit;
}

// -----------------------------------------------------------------------------
// ACTION: TEST API KEYS (Pengujian 4 API Key dari Admin)
// -----------------------------------------------------------------------------
if ($action === 'test_api') {
    $keyNum = (int)($_POST['key_num'] ?? $rawInput['key_num'] ?? 1);
    $testKey = trim($_POST['api_key'] ?? $rawInput['api_key'] ?? '');

    if (empty($testKey)) {
        $colName = "api_key_" . $keyNum;
        $testKey = $cfg[$colName] ?? '';
    }

    if (empty($testKey)) {
        echo json_encode(['status' => 'error', 'message' => "API Key #$keyNum masih kosong."]);
        exit;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($testKey);
    $payload = [
        "contents" => [
            ["parts" => [["text" => "Katakan 'Bismillah, API Aktif & Siap Digunakan' dalam 1 kalimat singkat."]]]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $data = json_decode($res, true);
        $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        echo json_encode([
            'status' => 'success',
            'message' => "API Key #$keyNum Terhubung & Aktif! Respon: " . trim($reply),
            'http_code' => 200
        ]);
    } else {
        $errMsg = "Gagal terhubung (HTTP $httpCode).";
        if ($httpCode === 429) {
            $errMsg = "Kuota Free Tier untuk API Key #$keyNum ini sedang habis / rate-limited (HTTP 429).";
        } elseif ($httpCode === 400) {
            $errMsg = "API Key tidak valid atau salah (HTTP 400).";
        }
        echo json_encode([
            'status' => 'error',
            'message' => $errMsg,
            'http_code' => $httpCode
        ]);
    }
    exit;
}

// -----------------------------------------------------------------------------
// ACTION: SAVE CONFIGURATION (Dari Panel Admin PSB)
// -----------------------------------------------------------------------------
if ($action === 'save_config') {
    $botName = trim($_POST['bot_name'] ?? 'Hasna');
    $greeting = trim($_POST['greeting_text'] ?? '');
    $apiKey1 = trim($_POST['api_key_1'] ?? '');
    $apiKey2 = trim($_POST['api_key_2'] ?? '');
    $apiKey3 = trim($_POST['api_key_3'] ?? '');
    $apiKey4 = trim($_POST['api_key_4'] ?? '');
    $fonnteToken = trim($_POST['fonnte_token'] ?? '');
    $adminWa = trim($_POST['admin_wa'] ?? '6285189918115');
    $notifyAdmin = isset($_POST['notify_admin']) ? 1 : 0;
    $systemPrompt = trim($_POST['system_prompt_spmb'] ?? '');

    $stmtSave = $conn->prepare("UPDATE cs_ai_config SET 
        bot_name = ?, 
        greeting_text = ?, 
        api_key_1 = ?, 
        api_key_2 = ?, 
        api_key_3 = ?, 
        api_key_4 = ?, 
        fonnte_token = ?, 
        admin_wa = ?, 
        notify_admin = ?, 
        system_prompt_spmb = ? 
        WHERE id = 1");

    $stmtSave->bind_param("ssssssssis", $botName, $greeting, $apiKey1, $apiKey2, $apiKey3, $apiKey4, $fonnteToken, $adminWa, $notifyAdmin, $systemPrompt);

    if ($stmtSave->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Konfigurasi CS AI Hasna berhasil disimpan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan konfigurasi: ' . $conn->error]);
    }
    exit;
}

// -----------------------------------------------------------------------------
// ACTION: AJARI HASNA PENGETAHUAN BARU (Learning Machine)
// -----------------------------------------------------------------------------
if ($action === 'add_knowledge') {
    $kategori = trim($_POST['kategori'] ?? 'SPMB');
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban = trim($_POST['jawaban_standar'] ?? '');

    if (empty($pertanyaan) || empty($jawaban)) {
        echo json_encode(['status' => 'error', 'message' => 'Pertanyaan dan Jawaban Standar tidak boleh kosong.']);
        exit;
    }

    $stmtAdd = $conn->prepare("INSERT INTO cs_ai_learning (kategori, pertanyaan, jawaban_standar, sumber, status) VALUES (?, ?, ?, 'admin', 'aktif')");
    $stmtAdd->bind_param("sss", $kategori, $pertanyaan, $jawaban);
    if ($stmtAdd->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Alhamdulillah, Hasna berhasil diajari pengetahuan baru ini!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan pengetahuan: ' . $conn->error]);
    }
    exit;
}

// -----------------------------------------------------------------------------
// ACTION: DELETE KNOWLEDGE
// -----------------------------------------------------------------------------
if ($action === 'delete_knowledge') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $conn->query("DELETE FROM cs_ai_learning WHERE id = $id");
        echo json_encode(['status' => 'success', 'message' => 'Pengetahuan berhasil dihapus dari memori Hasna.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenali.']);
exit;
