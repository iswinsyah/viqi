<?php
/**
 * CsAiService.php
 * Service layer untuk konfigurasi dan fungsionalitas inti CS AI Hasna.
 * Memisahkan logika database & helper dari router API sehingga halaman admin
 * tidak terganggu oleh respons JSON API router.
 */

require_once __DIR__ . '/WaNotificationService.php';

if (!function_exists('initCsAiDatabase')) {
    function initCsAiDatabase($conn) {
        if (!$conn) return;

        $conn->query("CREATE TABLE IF NOT EXISTS cs_ai_config (
            id INT PRIMARY KEY DEFAULT 1,
            bot_name VARCHAR(100) DEFAULT 'Hasna',
            whatsapp_cs VARCHAR(50) DEFAULT '6285189918115',
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
        @$conn->query("ALTER TABLE cs_ai_config ADD COLUMN whatsapp_cs VARCHAR(50) DEFAULT '6285189918115' AFTER bot_name");

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

        // Pastikan konfigurasi default ada
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
            if ($stmtDef) {
                $stmtDef->bind_param("sssss", $defGreeting, $defKey1, $defFonnte, $defAdminWa, $defPrompt);
                $stmtDef->execute();
            }
        }
    }
}

if (!function_exists('getCsConfig')) {
    function getCsConfig($conn) {
        initCsAiDatabase($conn);
        $res = $conn->query("SELECT * FROM cs_ai_config WHERE id = 1 LIMIT 1");
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            if (empty($row['whatsapp_cs'])) {
                $row['whatsapp_cs'] = '6285189918115';
            }
            if (empty($row['bot_name'])) {
                $row['bot_name'] = 'Hasna';
            }
            return $row;
        }
        return [
            'bot_name' => 'Hasna',
            'whatsapp_cs' => '6285189918115',
            'greeting_text' => 'Assalamu\'alaikum! Ada yang bisa kami bantu?',
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
}

if (!function_exists('kirimNotifWaFonnte')) {
    function kirimNotifWaFonnte($target, $pesan, $token) {
        $res = WaNotificationService::send($target, $pesan, null, $token);
        return $res['raw'] ?? false;
    }
}
