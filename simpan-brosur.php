<?php
// simpan-brosur.php
// Endpoint khusus penerimaan pendaftaran dari Brosur Digital Model Undangan
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

$FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "XFVpt4dRboQJ6GgKhGqw";
$YAYASAN_WA   = defined('YAYASAN_WA_RECIPIENT') ? YAYASAN_WA_RECIPIENT : "6285189918115";

// Self-healing: Pastikan kolom pendukung tersedia di tabel leads
@$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
@$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");
@$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(255) DEFAULT '' AFTER jenis_lead");
@$conn->query("ALTER TABLE leads ADD COLUMN nama_santri VARCHAR(100) DEFAULT '' AFTER nama");
@$conn->query("ALTER TABLE leads ADD COLUMN jenjang VARCHAR(50) DEFAULT '' AFTER nama_santri");
@$conn->query("ALTER TABLE leads ADD COLUMN kota VARCHAR(100) DEFAULT '' AFTER jenjang");
@$conn->query("ALTER TABLE leads ADD COLUMN catatan TEXT NULL AFTER kota");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$nama_wali    = isset($_POST['nama_wali']) ? trim($conn->real_escape_string($_POST['nama_wali'])) : (isset($_POST['nama']) ? trim($conn->real_escape_string($_POST['nama'])) : '');
$whatsapp     = isset($_POST['whatsapp']) ? trim($conn->real_escape_string($_POST['whatsapp'])) : '';
$nama_santri  = isset($_POST['nama_santri']) ? trim($conn->real_escape_string($_POST['nama_santri'])) : '-';
$jenjang      = isset($_POST['jenjang']) ? trim($conn->real_escape_string($_POST['jenjang'])) : 'SMP/SMA';
$kota         = isset($_POST['kota']) ? trim($conn->real_escape_string($_POST['kota'])) : '-';
$catatan      = isset($_POST['catatan']) ? trim($conn->real_escape_string($_POST['catatan'])) : '';
$kode_ref     = isset($_POST['kode_ref']) && !empty($_POST['kode_ref']) ? trim($conn->real_escape_string($_POST['kode_ref'])) : 'organik';

if (empty($nama_wali) || empty($whatsapp)) {
    echo json_encode(['status' => 'error', 'message' => 'Mohon lengkapi Nama Orang Tua dan Nomor WhatsApp.']);
    exit;
}

// Normalisasi nomor HP WhatsApp ke format internasional 628...
$wa_clean = preg_replace('/[^0-9]/', '', $whatsapp);
if (substr($wa_clean, 0, 2) === '08') {
    $wa_formatted = '628' . substr($wa_clean, 2);
} elseif (substr($wa_clean, 0, 1) === '8') {
    $wa_formatted = '628' . substr($wa_clean, 1);
} else {
    $wa_formatted = $wa_clean;
}

$sumber_info = "Brosur Digital ($jenjang - Ananda: $nama_santri, Asal: $kota)";

// Cek apakah nomor WA ini sudah pernah tercatat
$cek_sql = "SELECT id, kode_ref FROM leads WHERE whatsapp = '$whatsapp' OR whatsapp = '$wa_formatted' LIMIT 1";
$cek_res = $conn->query($cek_sql);

$is_new = true;
if ($cek_res && $cek_res->num_rows > 0) {
    $is_new = false;
    $row_lead = $cek_res->fetch_assoc();
    $lead_id = $row_lead['id'];
    // Update data terbaru dengan tetap mempertahankan kode_ref pertama jika organik
    $update_sql = "UPDATE leads SET 
                    nama = '$nama_wali', 
                    nama_santri = '$nama_santri', 
                    jenjang = '$jenjang', 
                    kota = '$kota', 
                    catatan = '$catatan', 
                    sumber_info = '$sumber_info' 
                   WHERE id = $lead_id";
    $conn->query($update_sql);
} else {
    $insert_sql = "INSERT INTO leads (nama, whatsapp, status, jenis_lead, sumber_info, kode_ref, nama_santri, jenjang, kota, catatan) 
                   VALUES ('$nama_wali', '$wa_formatted', 'Level 1', 'brosur_digital', '$sumber_info', '$kode_ref', '$nama_santri', '$jenjang', '$kota', '$catatan')";
    $conn->query($insert_sql);
}

// ==========================================
// 1. KIRIM NOTIFIKASI WHATSAPP KE WALI SANTRI VIA FONNTE
// ==========================================
$pesan_wali = "🌸 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\n"
            . "Yth. *Bapak/Ibu {$nama_wali}*,\n\n"
            . "Alhamdulillah, terima kasih atas kepercayaan dan silaturahmi Bapak/Ibu. Formulir minat & reservasi kuota calon santri baru di *Villa Quran Indonesia* telah berhasil kami terima dengan rincian:\n\n"
            . "📋 *Data Calon Santri:*\n"
            . "• *Nama Ananda:* {$nama_santri}\n"
            . "• *Pilihan Jenjang:* {$jenjang}\n"
            . "• *Domisili/Kota:* {$kota}\n"
            . "• *Status Reservasi:* Terdata di Sistem SPMB\n\n"
            . "🌟 *Keunggulan Utama Villa Quran:*\n"
            . "1️⃣ Tahfidz Mutqin 15–30 Juz Bersanad\n"
            . "2️⃣ Ijazah Resmi Negara Setara SMP & SMA\n"
            . "3️⃣ Digital Marketing, AI Terapan & Solopreneur\n"
            . "4️⃣ Lingkungan Nyaman, Sejuk & Asri ala Villa\n\n"
            . "Tim Humas & Ustadz SPMB kami akan segera menghubungi Bapak/Ibu untuk jadwal silaturahmi/observasi kampus.\n\n"
            . "🔗 *Akses Brosur & Profil Lengkap:*\n"
            . "https://villaquranindonesia.com/brosur.php\n\n"
            . "Semoga Allah SWT senantiasa meridhoi ikhtiar kita dalam mendidik generasi Qur'ani masa depan. Aamiin.\n\n"
            . "_Wassalamu'alaikum Warahmatullahi Wabarakatuh_\n"
            . "*Panitia SPMB — Villa Quran Indonesia*";

// Helper kirim Fonnte
function kirimFonnte($target, $pesan, $token) {
    if (empty($token) || $token === 'TOKEN_API_FONNTE_ANDA') return false;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.fonnte.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'target' => $target,
            'message' => $pesan
        ]),
        CURLOPT_HTTPHEADER => ["Authorization: $token"],
        CURLOPT_TIMEOUT => 15
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Kirim ke Calon Wali Santri
kirimFonnte($wa_formatted, $pesan_wali, $FONNTE_TOKEN);

// ==========================================
// 2. KIRIM NOTIFIKASI WHATSAPP KE YAYASAN / ADMIN MARKETING
// ==========================================
$pesan_admin = "🚨 *LEAD BARU DARI BROSUR DIGITAL!* 🚨\n\n"
             . "Terdapat calon wali santri yang baru saja mengisi formulir reservasi kuota di Brosur Digital:\n\n"
             . "👤 *Wali Santri:* {$nama_wali}\n"
             . "📱 *WhatsApp:* https://wa.me/{$wa_formatted}\n"
             . "🧒 *Calon Santri:* {$nama_santri}\n"
             . "🎓 *Jenjang:* {$jenjang}\n"
             . "📍 *Asal Kota:* {$kota}\n"
             . "🤝 *Kode Afiliasi/Ref:* {$kode_ref}\n"
             . (!empty($catatan) ? "📝 *Catatan:* {$catatan}\n" : "")
             . "⏰ *Waktu:* " . date('d-m-Y H:i') . " WIB\n\n"
             . "👉 Segera tindaklanjuti untuk konfirmasi silaturahmi & follow up prospek.";

// Kirim ke Admin Yayasan
if (!empty($YAYASAN_WA)) {
    kirimFonnte($YAYASAN_WA, $pesan_admin, $FONNTE_TOKEN);
}

// Respon sukses ke browser
echo json_encode([
    'status' => 'success',
    'message' => 'Alhamdulillah, data reservasi berhasil diterima! Konfirmasi resmi telah dikirim ke WhatsApp Bapak/Ibu.',
    'nama_wali' => $nama_wali,
    'nama_santri' => $nama_santri,
    'jenjang' => $jenjang,
    'wa' => $wa_formatted
]);
