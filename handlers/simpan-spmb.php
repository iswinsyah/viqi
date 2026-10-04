<?php
require_once 'koneksi.php';

// Update otomatis struktur tabel jika ada kolom yang kurang 
// (Tidak akan error berkat fitur MySQL jika dijalankan berkali-kali)
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN asal_sekolah VARCHAR(150) AFTER whatsapp_ortu");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN berkas_foto VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN berkas_akta VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN berkas_kk VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN berkas_ktp VARCHAR(255)");
$conn->query("ALTER TABLE pendaftar_spmb ADD COLUMN berkas_transfer VARCHAR(255)");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap Input Teks
    $jenjang = $conn->real_escape_string($_POST['jenjang'] ?? '');
    $nama = $conn->real_escape_string($_POST['nama_lengkap'] ?? '');
    $nik = $conn->real_escape_string($_POST['nik'] ?? '');
    $nisn = $conn->real_escape_string($_POST['nisn'] ?? '');
    $wa = $conn->real_escape_string($_POST['whatsapp_ortu'] ?? '');
    $asal_sekolah = $conn->real_escape_string($_POST['asal_sekolah'] ?? '');
    
    // Otomatis masukkan ke Leads Marketing juga agar muncul di Pipeline
    $kode_ref = $conn->real_escape_string($_POST['kode_ref'] ?? 'organik');
    $conn->query("INSERT IGNORE INTO leads (nama, whatsapp, status, jenis_lead, kode_ref) VALUES ('$nama', '$wa', 'Level 4', 'daftar_spmb', '$kode_ref')");

    // Penanganan Upload Berkas
    $upload_dir = 'uploads/spmb/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    function uploadBerkas($fileInputName, $upload_dir) {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == 0) {
            $ext = pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION);
            // Buat nama unik agar tidak tertimpa
            $newName = uniqid($fileInputName . '_') . '.' . strtolower($ext);
            if(move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $upload_dir . $newName)){
                return $newName;
            }
        }
        return '';
    }

    $f_foto = uploadBerkas('foto_santri', $upload_dir);
    $f_akta = uploadBerkas('foto_akta', $upload_dir);
    $f_kk = uploadBerkas('foto_kk', $upload_dir);
    $f_ktp = uploadBerkas('foto_ktp', $upload_dir);
    $f_transfer = uploadBerkas('bukti_transfer', $upload_dir);

    // Simpan Pendaftar ke Tabel SPMB
    $sql = "INSERT INTO pendaftar_spmb (jenjang, nama_lengkap, nik, nisn, whatsapp_ortu, asal_sekolah, berkas_foto, berkas_akta, berkas_kk, berkas_ktp, berkas_transfer, status)
            VALUES ('$jenjang', '$nama', '$nik', '$nisn', '$wa', '$asal_sekolah', '$f_foto', '$f_akta', '$f_kk', '$f_ktp', '$f_transfer', 'Menunggu Tes')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil dikirim! Admin kami akan segera menghubungi Anda melalui WhatsApp.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan database: ' . $conn->error]);
    }
}
?>