<?php
/**
 * WaNotificationService.php
 * Single Source of Truth untuk Notifikasi WhatsApp (Fonnte API) & Alert Leads SPMB
 * Mengelola token terpusat (DB/Konstanta), formatting nomor telepon, dan pengiriman pesan otomatis.
 */

class WaNotificationService {

    const DEFAULT_TOKEN = "XFVpt4dRboQJ6GgKhGqw";
    const DEFAULT_ADMIN_WA = "6285189918115";

    /**
     * Standarisasi format nomor WhatsApp ke format internasional (misal 628123456789)
     */
    public static function formatNumber($number) {
        $clean = preg_replace('/[^0-9]/', '', (string)$number);
        if (empty($clean)) return '';

        if (substr($clean, 0, 2) === '62') {
            return $clean;
        } elseif (substr($clean, 0, 1) === '0') {
            return '62' . substr($clean, 1);
        } elseif (substr($clean, 0, 1) === '8') {
            return '62' . $clean;
        }
        return $clean;
    }

    /**
     * Dapatkan Token Fonnte yang aktif
     * Prioritas: Database cs_ai_config -> Konstanta FONNTE_TOKEN -> Default Token
     */
    public static function getToken($conn = null) {
        if ($conn) {
            $q = $conn->query("SELECT fonnte_token FROM cs_ai_config WHERE id = 1 LIMIT 1");
            if ($q && $r = $q->fetch_assoc()) {
                if (!empty($r['fonnte_token'])) {
                    return trim($r['fonnte_token']);
                }
            }
        }

        if (defined('FONNTE_TOKEN') && !empty(FONNTE_TOKEN)) {
            return FONNTE_TOKEN;
        }

        return self::DEFAULT_TOKEN;
    }

    /**
     * Dapatkan Nomor WhatsApp Admin/Panitia SPMB
     * Prioritas: Database cs_ai_config -> Konstanta YAYASAN_WA / YAYASAN_WA_RECIPIENT -> Default Admin
     */
    public static function getAdminNumber($conn = null) {
        if ($conn) {
            $q = $conn->query("SELECT admin_wa, whatsapp_cs FROM cs_ai_config WHERE id = 1 LIMIT 1");
            if ($q && $r = $q->fetch_assoc()) {
                $num = !empty($r['admin_wa']) ? $r['admin_wa'] : $r['whatsapp_cs'];
                if (!empty($num)) {
                    return self::formatNumber($num);
                }
            }
        }

        if (defined('YAYASAN_WA_RECIPIENT') && !empty(YAYASAN_WA_RECIPIENT)) {
            return self::formatNumber(YAYASAN_WA_RECIPIENT);
        }

        if (defined('YAYASAN_WA') && !empty(YAYASAN_WA)) {
            return self::formatNumber(YAYASAN_WA);
        }

        return self::DEFAULT_ADMIN_WA;
    }

    /**
     * Kirim Pesan WhatsApp via Fonnte API
     */
    public static function send($target, $message, $conn = null, $customToken = null) {
        $targetClean = self::formatNumber($target);
        if (empty($targetClean) || empty($message)) {
            return ['success' => false, 'error' => 'Target atau pesan kosong.'];
        }

        $token = !empty($customToken) ? $customToken : self::getToken($conn);
        if (empty($token) || $token === 'TOKEN_API_FONNTE_ANDA') {
            return ['success' => false, 'error' => 'Token Fonnte tidak valid.'];
        }

        $ch = curl_init('https://api.fonnte.com/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'target' => $targetClean,
                'message' => $message,
                'countryCode' => '62'
            ]),
            CURLOPT_HTTPHEADER => ["Authorization: $token"],
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => $curlErr];
        }

        $data = json_decode($response, true);
        $isSuccess = ($httpCode === 200 && isset($data['status']) && $data['status'] === true);

        return [
            'success' => $isSuccess,
            'http_code' => $httpCode,
            'response' => $data,
            'raw' => $response
        ];
    }

    /**
     * Otomasi Notifikasi: Lead Baru dari Brosur Digital
     */
    public static function notifyLeadBrosur($data, $conn = null) {
        $namaWali   = $data['nama_wali'] ?? 'Ayah/Bunda';
        $wa         = $data['whatsapp'] ?? '';
        $namaSantri = $data['nama_santri'] ?? '-';
        $jenjang    = $data['jenjang'] ?? 'SMP/SMA';
        $kota       = $data['kota'] ?? '-';
        $kodeRef    = $data['kode_ref'] ?? '-';
        $catatan    = $data['catatan'] ?? '';

        $waFormatted = self::formatNumber($wa);

        // 1. Pesan Ramah ke Calon Wali Santri
        $pesanWali = "🌿 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\n"
            . "Ahlan wa Sahlan, Ayah/Bunda *{$namaWali}*.\n\n"
            . "Terima kasih telah mengajukan reservasi kuota dan mengunduh *Brosur Resmi Pondok Pesantren Villa Quran Baron Malang* untuk Ananda tercinta *{$namaSantri}* (Jenjang {$jenjang}).\n\n"
            . "📌 *Keunggulan Utama Pesantren Villa Quran:*\n"
            . "1️⃣ Tahfidz Mutqin 15–30 Juz Bersanad\n"
            . "2️⃣ Ijazah Resmi Negara Setara SMP & SMA\n"
            . "3️⃣ Digital Marketing, AI Terapan & Solopreneur\n"
            . "4️⃣ Lingkungan Nyaman, Sejuk & Asri ala Villa Pegunungan\n\n"
            . "Tim Humas & Panitia SPMB kami akan segera menghubungi Ayah/Bunda untuk jadwal konsultasi / silaturahmi ke kampus.\n\n"
            . "🔗 *Akses Brosur & Profil Lengkap:*\n"
            . "https://villaquranindonesia.com/brosur.php\n\n"
            . "Semoga Allah SWT meridhoi ikhtiar kita dalam mencetak generasi Qur'ani yang berkarakter mandiri. Aamiin.\n\n"
            . "_Wassalamu'alaikum Warahmatullahi Wabarakatuh_\n"
            . "*Panitia SPMB — Villa Quran Baron Malang*";

        $resWali = self::send($waFormatted, $pesanWali, $conn);

        // 2. Alert Notifikasi ke Admin / Panitia SPMB
        $adminWa = self::getAdminNumber($conn);
        $pesanAdmin = "🚨 *LEAD BARU DARI BROSUR DIGITAL!* 🚨\n\n"
            . "Calon wali santri baru saja reservasi kuota di Brosur Digital:\n\n"
            . "👤 *Wali Santri:* {$namaWali}\n"
            . "📱 *WhatsApp:* https://wa.me/{$waFormatted}\n"
            . "🧒 *Calon Santri:* {$namaSantri}\n"
            . "🎓 *Jenjang:* {$jenjang}\n"
            . "📍 *Asal Kota:* {$kota}\n"
            . "🤝 *Kode Afiliasi/Ref:* {$kodeRef}\n"
            . (!empty($catatan) ? "📝 *Catatan:* {$catatan}\n" : "")
            . "⏰ *Waktu:* " . date('d-m-Y H:i') . " WIB\n\n"
            . "👉 Segera tindaklanjuti untuk konfirmasi silaturahmi & follow up.";

        $resAdmin = self::send($adminWa, $pesanAdmin, $conn);

        return ['wali' => $resWali, 'admin' => $resAdmin];
    }

    /**
     * Otomasi Notifikasi: Hasil Tes Kesiapan Masuk Pondok
     */
    public static function notifyTestKesiapan($data, $conn = null) {
        $namaWali   = $data['nama_wali'] ?? 'Ayah/Bunda';
        $wa         = $data['whatsapp'] ?? '';
        $namaSantri = $data['nama_santri'] ?? '-';
        $gender     = $data['gender'] ?? 'Laki-laki';
        $jenjang    = $data['jenjang'] ?? 'SMP/SMA';
        $kota       = $data['kota'] ?? '-';
        $skor       = $data['skor'] ?? 0;
        $kategori   = $data['kategori'] ?? 'Siap';
        $pdfUrl     = $data['pdf_url'] ?? 'https://villaquranindonesia.com';

        $waFormatted = self::formatNumber($wa);

        // 1. Pesan ke Calon Wali Santri + Bonus Ebook
        $pesanWali = "🌿 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\n"
            . "Yth. *Bapak/Ibu {$namaWali}*,\n\n"
            . "Alhamdulillah, terima kasih telah menyelesaikan *Tes Kesiapan Masuk Pondok Pesantren* untuk Ananda tercinta:\n\n"
            . "📋 *HASIL ASESMEN KESIAPAN:*\n"
            . "• *Nama Ananda:* {$namaSantri} ({$gender})\n"
            . "• *Jenjang Target:* {$jenjang}\n"
            . "• *Kota Domisili:* {$kota}\n"
            . "• *Skor Kesiapan:* *{$skor}%*\n"
            . "• *Kategori:* *{$kategori}*\n\n"
            . "🎁 *BONUS E-BOOK EKSKLUSIF ORANG TUA:*\n"
            . "Silakan download E-Book Panduan: *'Rahasia Menyiapkan Anak Remaja Menjadi Hafidz Quran'*\n"
            . "Link Download: {$pdfUrl}\n\n"
            . "💬 Jika Bapak/Ibu ingin berkonsultasi lebih lanjut mengenai program pembiasaan mandiri dan pendaftaran SPMB Villa Quran Baron Malang, silakan balas pesan ini.\n\n"
            . "Jazakumullahu Khairan Katsiran.\n"
            . "_Panitia Penerimaan Santri Baru (PSB)_\n"
            . "*Villa Quran Baron Malang*";

        $resWali = self::send($waFormatted, $pesanWali, $conn);

        // 2. Alert ke Admin Panitia SPMB
        $adminWa = self::getAdminNumber($conn);
        $pesanAdmin = "🎯 *LEAD BARU: TES KESIAPAN ANAK!* 🎯\n\n"
            . "Ada wali santri yang baru menyelesaikan asesmen kesiapan:\n\n"
            . "👤 *Wali Santri:* {$namaWali}\n"
            . "📱 *WhatsApp:* https://wa.me/{$waFormatted}\n"
            . "🧒 *Ananda:* {$namaSantri} ({$gender})\n"
            . "🎓 *Jenjang:* {$jenjang}\n"
            . "📍 *Kota:* {$kota}\n"
            . "📊 *Skor:* {$skor}% ({$kategori})\n"
            . "⏰ *Waktu:* " . date('d-m-Y H:i') . " WIB\n\n"
            . "👉 Prospek potensial, siap difollow-up silaturahmi.";

        $resAdmin = self::send($adminWa, $pesanAdmin, $conn);

        return ['wali' => $resWali, 'admin' => $resAdmin];
    }

    /**
     * Otomasi Notifikasi: Pendaftaran Form Online SPMB
     */
    public static function notifySpmbRegistration($data, $conn = null) {
        $namaSantri  = $data['nama_santri'] ?? '-';
        $wa          = $data['whatsapp'] ?? '';
        $jenjang     = $data['jenjang'] ?? 'SMP/SMA';
        $asalSekolah = $data['asal_sekolah'] ?? '-';
        $kodeRef     = $data['kode_ref'] ?? '-';

        $waFormatted = self::formatNumber($wa);

        // 1. Pesan Konfirmasi Pendaftaran ke Orang Tua
        $pesanWali = "🌿 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\n"
            . "Alhamdulillah, formulir pendaftaran SPMB Ananda *{$namaSantri}* (Jenjang {$jenjang}) telah kami terima di sistem Villa Quran Baron Malang.\n\n"
            . "📋 *Status:* Menunggu Verifikasi Berkas & Jadwal Tes Observasi.\n"
            . "🏫 *Asal Sekolah:* {$asalSekolah}\n\n"
            . "Panitia SPMB akan segera memvalidasi berkas dan menghubungi nomor ini untuk konfirmasi tahapan observasi/wawancara.\n\n"
            . "Terima kasih atas kepercayaan Ayah/Bunda menitipkan amanah pendidikan Ananda bersama kami.\n\n"
            . "_Wassalamu'alaikum Warahmatullahi Wabarakatuh_\n"
            . "*Panitia SPMB — Villa Quran Baron Malang*";

        $resWali = self::send($waFormatted, $pesanWali, $conn);

        // 2. Alert ke Panitia SPMB
        $adminWa = self::getAdminNumber($conn);
        $pesanAdmin = "⭐ *PENDAFTARAN SPMB BARU MASUK!* ⭐\n\n"
            . "Formulir pendaftaran online baru telah diisi:\n\n"
            . "🧒 *Nama Santri:* {$namaSantri}\n"
            . "🎓 *Jenjang:* {$jenjang}\n"
            . "📱 *WhatsApp Ortu:* https://wa.me/{$waFormatted}\n"
            . "🏫 *Asal Sekolah:* {$asalSekolah}\n"
            . "🤝 *Kode Afiliasi:* {$kodeRef}\n"
            . "⏰ *Waktu:* " . date('d-m-Y H:i') . " WIB\n\n"
            . "👉 Silakan cek menu Ruang PSB > Data Santri Baru untuk verifikasi berkas.";

        $resAdmin = self::send($adminWa, $pesanAdmin, $conn);

        return ['wali' => $resWali, 'admin' => $resAdmin];
    }
}
