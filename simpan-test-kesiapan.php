<?php
// simpan-test-kesiapan.php
// Endpoint AJAX untuk menyimpan hasil tes kesiapan anak masuk pondok pesantren (Lead Magnet PSB)
header('Content-Type: application/json; charset=utf-8');
require_once 'koneksi.php';

if (file_exists(__DIR__ . '/config-key.php')) {
    require_once __DIR__ . '/config-key.php';
}

$FONNTE_TOKEN = defined('FONNTE_TOKEN') ? FONNTE_TOKEN : "XFVpt4dRboQJ6GgKhGqw";
$YAYASAN_WA   = defined('YAYASAN_WA_RECIPIENT') ? YAYASAN_WA_RECIPIENT : "6285189918115";

// Self-healing database schema: Pastikan kolom pendukung tersedia di tabel leads
@$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
@$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'test_kesiapan_pondok' AFTER status");
@$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(255) DEFAULT '' AFTER jenis_lead");
@$conn->query("ALTER TABLE leads ADD COLUMN nama_santri VARCHAR(100) DEFAULT '' AFTER nama");
@$conn->query("ALTER TABLE leads ADD COLUMN jenjang VARCHAR(50) DEFAULT '' AFTER nama_santri");
@$conn->query("ALTER TABLE leads ADD COLUMN kota VARCHAR(100) DEFAULT '' AFTER jenjang");
@$conn->query("ALTER TABLE leads ADD COLUMN skor_kesiapan INT(11) DEFAULT 0 AFTER kota");
@$conn->query("ALTER TABLE leads ADD COLUMN kategori_kesiapan VARCHAR(100) DEFAULT '' AFTER skor_kesiapan");
@$conn->query("ALTER TABLE leads ADD COLUMN detail_evaluasi TEXT NULL AFTER kategori_kesiapan");
@$conn->query("ALTER TABLE leads ADD COLUMN catatan TEXT NULL AFTER detail_evaluasi");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['status' => 'error', 'message' => 'Metode request tidak valid.']);
    exit;
}

$nama_wali    = isset($_POST['nama_wali']) ? trim($conn->real_escape_string($_POST['nama_wali'])) : (isset($_POST['nama']) ? trim($conn->real_escape_string($_POST['nama'])) : '');
$whatsapp     = isset($_POST['whatsapp']) ? trim($conn->real_escape_string($_POST['whatsapp'])) : '';
$nama_santri  = isset($_POST['nama_santri']) ? trim($conn->real_escape_string($_POST['nama_santri'])) : '-';
$gender       = isset($_POST['gender']) ? trim($conn->real_escape_string($_POST['gender'])) : 'Putra';
$jenjang      = isset($_POST['jenjang']) ? trim($conn->real_escape_string($_POST['jenjang'])) : 'SMP/SMA';
$kota         = isset($_POST['kota']) ? trim($conn->real_escape_string($_POST['kota'])) : '-';
$skor_persen  = isset($_POST['skor_persen']) ? (int)$_POST['skor_persen'] : 0;
$kategori     = isset($_POST['kategori']) ? trim($conn->real_escape_string($_POST['kategori'])) : 'Siap';
$detail_eval  = isset($_POST['detail_evaluasi']) ? trim($conn->real_escape_string($_POST['detail_evaluasi'])) : '';
$kode_ref     = isset($_POST['kode_ref']) && !empty($_POST['kode_ref']) ? trim($conn->real_escape_string($_POST['kode_ref'])) : 'organik';

if (empty($nama_wali) || empty($whatsapp)) {
    echo json_encode(['status' => 'error', 'message' => 'Mohon lengkapi Nama Orang Tua dan Nomor WhatsApp.']);
    exit;
}

// Normalisasi nomor HP WhatsApp ke format 628...
$wa_clean = preg_replace('/[^0-9]/', '', $whatsapp);
if (substr($wa_clean, 0, 2) === '08') {
    $wa_formatted = '628' . substr($wa_clean, 2);
} elseif (substr($wa_clean, 0, 1) === '8') {
    $wa_formatted = '628' . substr($wa_clean, 1);
} else {
    $wa_formatted = $wa_clean;
}

$sumber_info = "Lead Magnet Test Kesiapan ($skor_persen% - $kategori | $jenjang - $gender: $nama_santri, Asal: $kota)";
$catatan = "Hasil Tes Kesiapan: Skor $skor_persen% ($kategori). Gender: $gender, Jenjang Target: $jenjang. Ref: $kode_ref.";

// Cek apakah nomor WA ini sudah pernah tercatat
$cek_sql = "SELECT id, kode_ref FROM leads WHERE whatsapp = '$whatsapp' OR whatsapp = '$wa_formatted' LIMIT 1";
$cek_res = $conn->query($cek_sql);

$lead_id = 0;
if ($cek_res && $cek_res->num_rows > 0) {
    $row_lead = $cek_res->fetch_assoc();
    $lead_id = $row_lead['id'];
    
    $update_sql = "UPDATE leads SET 
                    nama = '$nama_wali', 
                    nama_santri = '$nama_santri', 
                    jenjang = '$jenjang', 
                    kota = '$kota', 
                    skor_kesiapan = $skor_persen,
                    kategori_kesiapan = '$kategori',
                    detail_evaluasi = '$detail_eval',
                    catatan = '$catatan', 
                    sumber_info = '$sumber_info' 
                   WHERE id = $lead_id";
    $conn->query($update_sql);
} else {
    $insert_sql = "INSERT INTO leads (nama, whatsapp, status, jenis_lead, sumber_info, kode_ref, nama_santri, jenjang, kota, skor_kesiapan, kategori_kesiapan, detail_evaluasi, catatan) 
                   VALUES ('$nama_wali', '$wa_formatted', 'Level 1', 'test_kesiapan_pondok', '$sumber_info', '$kode_ref', '$nama_santri', '$jenjang', '$kota', $skor_persen, '$kategori', '$detail_eval', '$catatan')";
    $conn->query($insert_sql);
    $lead_id = $conn->insert_id;
}

// -------------------------------------------------------------
// KIRIM NOTIFIKASI WHATSAPP KE WALI SANTRI VIA FONNTE (Optional)
// -------------------------------------------------------------
$wa_sent = false;
if (!empty($FONNTE_TOKEN) && $FONNTE_TOKEN !== 'YOUR_FONNTE_TOKEN_HERE') {
    $pesan_wali = "🌿 *Assalamu'alaikum Warahmatullahi Wabarakatuh*\n\n"
                . "Yth. *Bapak/Ibu {$nama_wali}*,\n\n"
                . "Alhamdulillah, terima kasih telah menyelesaikan *Tes Kesiapan Masuk Pondok Pesantren* untuk Ananda tercinta:\n\n"
                . "📋 *HASIL ASESMEN KESIAPAN:*\n"
                . "• *Nama Ananda:* {$nama_santri} ({$gender})\n"
                . "• *Jenjang Target:* {$jenjang}\n"
                . "• *Kota Domisili:* {$kota}\n"
                . "• *Skor Kesiapan:* *{$skor_persen}%*\n"
                . "• *Kategori:* *{$kategori}*\n\n"
                . "🎁 *BONUS E-BOOK EKSKLUSIF ORANG TUA:*\n"
                . "Silakan download E-Book Panduan: *'Rahasia Menyiapkan Anak Remaja Menjadi Hafidz Quran'*\n"
                . "Link Download: https://pesantren.villakeluargaislami.com/RAHASIA%20MENYIAPKAN%20ANAK%20REMAJA%20MENJADI%20HAFIDZ%20QURAN.pdf\n\n"
                . "💬 Jika Bapak/Ibu ingin berkonsultasi lebih lanjut mengenai program pembiasaan mandiri, asrama villa bernuansa alam, atau informasi pendaftaran SPMB Villa Quran Baron Malang, silakan balas pesan ini.\n\n"
                . "Jazakumullahu Khairan Katsiran.\n"
                . "_Panitia Penerimaan Santri Baru (PSB)_\n"
                . "*Villa Quran Baron Malang*";

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'target' => $wa_formatted,
            'message' => $pesan_wali,
            'countryCode' => '62'
        ],
        CURLOPT_HTTPHEADER => [
            "Authorization: $FONNTE_TOKEN"
        ],
    ]);
    $res_fonnte = curl_exec($curl);
    curl_close($curl);
    $wa_sent = true;
}

echo json_encode([
    'status' => 'success',
    'message' => 'Hasil tes kesiapan berhasil tersimpan.',
    'lead_id' => $lead_id,
    'skor' => $skor_persen,
    'kategori' => $kategori,
    'wa_sent' => $wa_sent
]);
?>
