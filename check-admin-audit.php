<?php
header('Content-Type: text/html; charset=utf-8');
require_once 'koneksi.php';

echo "<!DOCTYPE html><html><head><title>Audit Database Administrasi Tutor / Guru</title>";
echo "<style>
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; padding: 24px; background: #0f172a; color: #f8fafc; }
h1 { color: #38bdf8; font-size: 22px; margin-bottom: 8px; }
h2 { color: #93c5fd; font-size: 16px; margin-top: 24px; border-bottom: 1px solid #334155; padding-bottom: 6px; }
.card { background: #1e293b; border-radius: 12px; padding: 16px; margin-bottom: 16px; border: 1px solid #334155; }
.badge { display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; margin-right: 6px; }
.badge-success { background: #065f46; color: #6ee7b7; }
.badge-warning { background: #854d0e; color: #fde047; }
.badge-danger { background: #991b1b; color: #fca5a5; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #334155; }
th { background: #0f172a; color: #94a3b8; }
.text-slate { color: #94a3b8; font-size: 12px; }
</style></head><body>";

echo "<h1>📊 Audit Database: Administrasi Tutor & Guru Mapel Diknas (PKBM)</h1>";
echo "<p class='text-slate'>Waktu Audit: " . date('Y-m-d H:i:s') . "</p>";

// 1. MASTER MAPEL & PENGAMPU
echo "<h2>1. Master Mata Pelajaran & Penugasan Tutor / Guru</h2>";
$res_mapel = $conn->query("SELECT m.*, u.nama_lengkap as nama_pengampu FROM master_mapel m LEFT JOIN akun_ustadz u ON m.pengampu_id = u.id ORDER BY m.kategori_mapel ASC, m.nama_mapel ASC");
if ($res_mapel && $res_mapel->num_rows > 0) {
    echo "<div class='card'>";
    echo "<table><tr><th>ID</th><th>Mapel</th><th>Kategori</th><th>Metode</th><th>Status</th><th>Guru Pengampu</th></tr>";
    $assigned = 0; $unassigned = 0; $diknas = 0;
    while ($m = $res_mapel->fetch_assoc()) {
        $has_p = !empty($m['pengampu_id']);
        if ($has_p) $assigned++; else $unassigned++;
        if (strtolower($m['kategori_mapel'] ?? '') === 'diknas') $diknas++;
        
        $status_pengampu = $has_p ? "<span class='badge badge-success'>✔ " . htmlspecialchars($m['nama_pengampu']) . "</span>" : "<span class='badge badge-danger'>❌ BELUM ADA PENGAMPU</span>";
        echo "<tr>";
        echo "<td>" . $m['id'] . "</td>";
        echo "<td><b>" . htmlspecialchars($m['nama_mapel']) . "</b></td>";
        echo "<td>" . htmlspecialchars($m['kategori_mapel'] ?? '-') . "</td>";
        echo "<td>" . htmlspecialchars($m['metode_belajar'] ?? '-') . "</td>";
        echo "<td>" . ($m['status_aktif'] ? 'Aktif' : 'Non-Aktif') . "</td>";
        echo "<td>" . $status_pengampu . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='text-slate' style='margin-top:10px;'>Total Mapel: " . $res_mapel->num_rows . " (Diknas: $diknas) | Sudah ada Pengampu: <b style='color:#6ee7b7;'>$assigned</b> | Belum ada Pengampu: <b style='color:#fca5a5;'>$unassigned</b></p>";
    echo "</div>";
} else {
    echo "<div class='card'><span class='badge badge-danger'>Tabel master_mapel kosong atau tidak ada.</span></div>";
}

// Helper to audit any table
function auditTable($conn, $tableName, $title, $description, $customQuery = null, $columns = []) {
    echo "<h2>$title</h2>";
    echo "<p class='text-slate'>$description</p>";
    
    // Check if table exists
    $chk = $conn->query("SHOW TABLES LIKE '$tableName'");
    if (!$chk || $chk->num_rows === 0) {
        echo "<div class='card'><span class='badge badge-danger'>❌ TIDAK JALAN (Tabel <code>$tableName</code> belum dibuat / belum ada data)</span></div>";
        return;
    }
    
    $cntRes = $conn->query("SELECT COUNT(*) as total FROM $tableName");
    $total = $cntRes ? (int)$cntRes->fetch_assoc()['total'] : 0;
    
    if ($total === 0) {
        echo "<div class='card'>";
        echo "<span class='badge badge-warning'>⚠️ BELUM JALAN (Tabel <code>$tableName</code> ada, tapi 0 record / belum pernah diisi guru)</span>";
        echo "</div>";
        return;
    }
    
    echo "<div class='card'>";
    echo "<span class='badge badge-success'>✔ BERJALAN ($total Record ditemukan)</span>";
    
    if ($customQuery) {
        $sampleRes = $conn->query($customQuery);
        if ($sampleRes && $sampleRes->num_rows > 0) {
            echo "<table><tr>";
            foreach ($columns as $c) echo "<th>$c</th>";
            echo "</tr>";
            while ($row = $sampleRes->fetch_assoc()) {
                echo "<tr>";
                foreach ($columns as $k => $c) {
                    $val = $row[$k] ?? '-';
                    if (strlen($val) > 60) $val = substr($val, 0, 60) . '...';
                    echo "<td>" . htmlspecialchars($val) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    echo "</div>";
}

// 2. PERENCANAAN
auditTable($conn, 'master_silabus', '2.1. Master Silabus & Capaian Pembelajaran (CP)', 'Dokumen acuan Silabus, CP, dan ATP per mata pelajaran.', "SELECT id, mata_pelajaran, kelas, semester, updated_at FROM master_silabus ORDER BY id DESC LIMIT 5", ['mata_pelajaran' => 'Mapel', 'kelas' => 'Kelas', 'semester' => 'Semester', 'updated_at' => 'Update Terakhir']);

auditTable($conn, 'elearning_bab', '2.2. E-Modul Pembelajaran & Flipbook (elearning_bab)', 'Modul belajar, link PDF flipbook, video pembelajaran, dan materi ringkas.', "SELECT id, mapel_nama, nomor_bab, judul_bab, pdf_url, video_url, created_at FROM elearning_bab ORDER BY id DESC LIMIT 10", ['mapel_nama' => 'Mapel', 'nomor_bab' => 'Bab', 'judul_bab' => 'Judul Bab', 'pdf_url' => 'URL PDF', 'video_url' => 'Video YouTube', 'created_at' => 'Dibuat']);

auditTable($conn, 'kesediaan_mengajar', '2.3. Kesediaan Mengajar & Jadwal Tutor', 'Data ketersediaan jam mengajar dan pemetaan jadwal asatidz.', "SELECT * FROM kesediaan_mengajar ORDER BY id DESC LIMIT 5", ['ustadz_id' => 'Ustadz ID', 'hari' => 'Hari', 'jam_tersedia' => 'Jam']);

auditTable($conn, 'jadwal_pelajaran', '2.4. Jadwal Pelajaran Aktif (jadwal_pelajaran)', 'Jadwal tatap muka/online mingguan di kelas.', "SELECT * FROM jadwal_pelajaran ORDER BY id DESC LIMIT 5", ['hari' => 'Hari', 'kelas_id' => 'Kelas', 'mapel_id' => 'Mapel', 'ustadz_id' => 'Ustadz']);

auditTable($conn, 'kalender_akademik', '2.5. Kalender Akademik', 'Agenda semester, UTS/UAS, libur, dan kegiatan belajar.', "SELECT id, judul_kegiatan, tanggal_mulai, tanggal_selesai, kategori FROM kalender_akademik ORDER BY tanggal_mulai DESC LIMIT 5", ['judul_kegiatan' => 'Kegiatan', 'tanggal_mulai' => 'Mulai', 'tanggal_selesai' => 'Selesai', 'kategori' => 'Kategori']);

// 3. PELAKSANAAN KBM
auditTable($conn, 'jurnal_mengajar', '3.1. Jurnal Mengajar Harian (jurnal_mengajar)', 'Catatan materi yang diajarkan, tanggal pertemuan, dan absensi per pertemuan.', "SELECT j.*, u.nama_lengkap as nama_guru FROM jurnal_mengajar j LEFT JOIN akun_ustadz u ON j.ustadz_id = u.id ORDER BY j.tanggal DESC, j.id DESC LIMIT 10", ['tanggal' => 'Tanggal', 'nama_guru' => 'Guru', 'kelas' => 'Kelas', 'mata_pelajaran' => 'Mapel', 'materi' => 'Materi Diajarkan']);

auditTable($conn, 'santri_tidak_masuk', '3.2. Kontrol Santri Tidak Masuk / Izin Kelas', 'Laporan santri yang tidak hadir saat KBM berlangsung.', "SELECT * FROM santri_tidak_masuk ORDER BY tanggal DESC LIMIT 5", ['tanggal' => 'Tanggal', 'santri_id' => 'Santri', 'keterangan' => 'Keterangan', 'alasan' => 'Alasan']);

// 4. ASESMEN & PENILAIAN
auditTable($conn, 'elearning_kuis', '4.1. Bank Soal Kuis E-Learning (elearning_kuis)', 'Soal latihan objektif / kuis interaktif formatif.', "SELECT k.id, b.mapel_nama, b.judul_bab, k.soal, k.kunci_jawaban FROM elearning_kuis k JOIN elearning_bab b ON k.bab_id = b.id ORDER BY k.id DESC LIMIT 5", ['mapel_nama' => 'Mapel', 'judul_bab' => 'Bab', 'soal' => 'Pertanyaan', 'kunci_jawaban' => 'Kunci']);

auditTable($conn, 'bank_nilai', '4.2. Bank Nilai Harian / Tugas / Formatif (bank_nilai / nilai_santri)', 'Rekap nilai ulangan harian, tugas, dan LKS santri.', "SELECT * FROM bank_nilai ORDER BY id DESC LIMIT 5", ['santri_id' => 'Santri', 'mapel_id' => 'Mapel', 'nilai' => 'Nilai', 'jenis_nilai' => 'Jenis']);

auditTable($conn, 'leger_nilai', '4.3. Leger Nilai Santri (leger_nilai)', 'Kumpulan nilai seluruh mata pelajaran untuk rapor semester.', "SELECT l.*, m.nama_mapel FROM leger_nilai l LEFT JOIN master_mapel m ON l.mapel_id = m.id ORDER BY l.id DESC LIMIT 5", ['santri_id' => 'Santri', 'nama_mapel' => 'Mapel', 'nilai_pengetahuan' => 'Nilai Angka', 'nilai_keterampilan' => 'Keterampilan']);

auditTable($conn, 'rapor_santri', '4.4. Raport PKBM Diknas & Diniyah', 'Pencetakan rapor digital semester dan deskripsi capaian.', "SELECT * FROM rapor_santri ORDER BY id DESC LIMIT 5", ['santri_id' => 'Santri', 'semester' => 'Semester', 'tahun_ajaran' => 'Tahun Ajaran']);

echo "</body></html>";
?>
