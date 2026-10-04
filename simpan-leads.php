<?php
require_once 'koneksi.php';

// Pastikan kolom status & jenis_lead ada di database (bisa error silent kalau sudah ada)
$conn->query("ALTER TABLE leads ADD COLUMN status VARCHAR(50) DEFAULT 'Level 1' AFTER whatsapp");
$conn->query("ALTER TABLE leads ADD COLUMN jenis_lead VARCHAR(50) DEFAULT 'brosur' AFTER status");
$conn->query("ALTER TABLE leads ADD COLUMN sumber_info VARCHAR(100) DEFAULT '' AFTER jenis_lead");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = isset($_POST['nama']) ? $conn->real_escape_string($_POST['nama']) : '';
    $whatsapp = isset($_POST['whatsapp']) ? $conn->real_escape_string($_POST['whatsapp']) : '';
    $jenis_lead = isset($_POST['jenis_lead']) ? $conn->real_escape_string($_POST['jenis_lead']) : '';
    $sumber_info = isset($_POST['sumber_info']) ? $conn->real_escape_string($_POST['sumber_info']) : '';
    $kode_ref = isset($_POST['kode_ref']) ? $conn->real_escape_string($_POST['kode_ref']) : 'organik';

    // CEK DUPLIKASI: Apakah nomor WhatsApp ini sudah pernah mendaftar/download sebelumnya?
    $cek_sql = "SELECT id FROM leads WHERE whatsapp = '$whatsapp'";
    $cek_result = $conn->query($cek_sql);
    
    if ($cek_result && $cek_result->num_rows > 0) {
        // Jika sudah ada, JANGAN simpan lagi agar klaim agen pertama tetap aman (First-Click Wins).
        // Tetap balas "Sukses" agar pengunjung tetap bisa mendownload file di layar mereka.
        echo "Sukses";
    } else {
        // Jika belum ada, masukkan data prospek baru ini
        $sql = "INSERT INTO leads (nama, whatsapp, status, jenis_lead, sumber_info, kode_ref) 
                VALUES ('$nama', '$whatsapp', 'Level 1', '$jenis_lead', '$sumber_info', '$kode_ref')";

        if ($conn->query($sql) === TRUE) {
            echo "Sukses";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}
?>