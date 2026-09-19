<?php
/**
 * KURIKULUM PEKAN EFEKTIF, PROTA & PROMES GENERATOR
 * Terintegrasi dengan Kalender Akademik SADIGS 4.0
 */

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/pkbm_modul_catalog.php';

// Inisialisasi Tabel Prota & Promes jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS kurikulum_prota_promes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran VARCHAR(20) NOT NULL DEFAULT '2026/2027',
    jenjang ENUM('Paket B', 'Paket C') NOT NULL DEFAULT 'Paket C',
    kelas VARCHAR(20) NOT NULL DEFAULT 'Kelas 10',
    mapel_nama VARCHAR(100) NOT NULL,
    semester INT NOT NULL DEFAULT 1,
    bab_nomor INT NOT NULL,
    judul_materi VARCHAR(255) NOT NULL,
    alokasi_jp INT NOT NULL DEFAULT 4,
    minggu_ke_mulai INT NOT NULL,
    minggu_ke_selesai INT NOT NULL,
    keterangan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

function getAnalisisPekanEfektif($conn, $tahun = 2026) {
    // Standard Academic Calendar calculation
    // Semester Ganjil: Juli - Desember
    // Semester Genap: Januari - Juni
    
    // Periksa apakah ada data override dari tabel kalender_akademik
    $res_cal = $conn->query("SELECT tanggal, status_hari FROM kalender_akademik");
    $holidays = [];
    if ($res_cal) {
        while ($r = $res_cal->fetch_assoc()) {
            $holidays[$r['tanggal']] = $r['status_hari'];
        }
    }

    $analisis = [
        'tahun_ajaran' => $tahun . '/' . ($tahun + 1),
        'semester_ganjil' => [
            'nama' => 'Semester 1 (Ganjil)',
            'rentang' => "Juli $tahun - Desember $tahun",
            'bulan' => [
                'Juli' => ['total_minggu' => 4, 'tidak_efektif' => 1, 'efektif' => 3, 'kegiatan' => 'Awal Semester & MPLS/Taaruf'],
                'Agustus' => ['total_minggu' => 5, 'tidak_efektif' => 1, 'efektif' => 4, 'kegiatan' => 'HUT RI & KBM Efektif'],
                'September' => ['total_minggu' => 4, 'tidak_efektif' => 1, 'efektif' => 3, 'kegiatan' => 'PTS / STS Ganjil'],
                'Oktober' => ['total_minggu' => 5, 'tidak_efektif' => 0, 'efektif' => 5, 'kegiatan' => 'KBM Efektif & LKS Mandiri'],
                'November' => ['total_minggu' => 4, 'tidak_efektif' => 0, 'efektif' => 4, 'kegiatan' => 'KBM Efektif & Evaluasi Modul'],
                'Desember' => ['total_minggu' => 4, 'tidak_efektif' => 2, 'efektif' => 2, 'kegiatan' => 'PAS / SAS & Libur Semester 1']
            ],
            'total_pekan' => 26,
            'pekan_tidak_efektif' => 5,
            'pekan_efektif' => 21
        ],
        'semester_genap' => [
            'nama' => 'Semester 2 (Genap)',
            'rentang' => 'Januari ' . ($tahun + 1) . ' - Juni ' . ($tahun + 1),
            'bulan' => [
                'Januari' => ['total_minggu' => 4, 'tidak_efektif' => 1, 'efektif' => 3, 'kegiatan' => 'Awal Semester 2'],
                'Februari' => ['total_minggu' => 4, 'tidak_efektif' => 0, 'efektif' => 4, 'kegiatan' => 'KBM Efektif'],
                'Maret' => ['total_minggu' => 4, 'tidak_efektif' => 1, 'efektif' => 3, 'kegiatan' => 'PTS / STS Genap & Tarhib Ramadhan'],
                'April' => ['total_minggu' => 5, 'tidak_efektif' => 2, 'efektif' => 3, 'kegiatan' => 'Idul Fitri & Libur Hari Raya'],
                'Mei' => ['total_minggu' => 4, 'tidak_efektif' => 0, 'efektif' => 4, 'kegiatan' => 'KBM Efektif & Uji Kesetaraan (UPK)'],
                'Juni' => ['total_minggu' => 5, 'tidak_efektif' => 2, 'efektif' => 3, 'kegiatan' => 'PAT / SAS Genap, Raport & Libur Semester 2']
            ],
            'total_pekan' => 26,
            'pekan_tidak_efektif' => 6,
            'pekan_efektif' => 20
        ]
    ];

    return $analisis;
}

/**
 * Auto-Generate Distribusi Prota & Promes untuk Semua Mapel
 */
function autoGenerateProtaPromes($conn, $tahun_ajaran = '2026/2027') {
    $pekan_info = getAnalisisPekanEfektif($conn, 2026);
    $pekan_s1 = $pekan_info['semester_ganjil']['pekan_efektif']; // 21 Pekan
    $pekan_s2 = $pekan_info['semester_genap']['pekan_efektif'];  // 20 Pekan

    $res_mapel = $conn->query("SELECT DISTINCT mapel_nama FROM elearning_bab ORDER BY mapel_nama");
    $generated_count = 0;

    if ($res_mapel) {
        while ($m = $res_mapel->fetch_assoc()) {
            $mapel_nama = $m['mapel_nama'];
            $mapel_esc = $conn->real_escape_string($mapel_nama);
            $jenjang = 'Paket C';

            // Ambil seluruh bab mapel ini dari elearning_bab
            $res_bab = $conn->query("SELECT * FROM elearning_bab WHERE mapel_nama = '$mapel_esc' ORDER BY nomor_bab ASC");
            if ($res_bab && $res_bab->num_rows > 0) {
                $babs = $res_bab->fetch_all(MYSQLI_ASSOC);
                $total_bab = count($babs);
                $mid = ceil($total_bab / 2);

                // Bersihkan entri lama mapel ini di tahun ajaran aktif
                $conn->query("DELETE FROM kurikulum_prota_promes WHERE mapel_nama = '$mapel_esc' AND tahun_ajaran = '$tahun_ajaran'");

                // Semester 1 Babs
                $cur_week_s1 = 1;
                $weeks_per_bab_s1 = max(1, floor($pekan_s1 / max(1, $mid)));

                for ($i = 0; $i < $mid; $i++) {
                    $b = $babs[$i];
                    $start_w = $cur_week_s1;
                    $end_w = min($pekan_s1, $cur_week_s1 + $weeks_per_bab_s1 - 1);
                    if ($i === $mid - 1) $end_w = $pekan_s1; // Bab terakhir semester 1 sampai akhir pekan
                    $cur_week_s1 = $end_w + 1;

                    $judul = $conn->real_escape_string($b['judul_bab']);
                    $jp = ($end_w - $start_w + 1) * 2; // Asumsi 2 JP per pekan

                    $sql_ins = "INSERT INTO kurikulum_prota_promes 
                                (tahun_ajaran, jenjang, kelas, mapel_nama, semester, bab_nomor, judul_materi, alokasi_jp, minggu_ke_mulai, minggu_ke_selesai, keterangan)
                                VALUES ('$tahun_ajaran', '$jenjang', 'Kelas 10', '$mapel_esc', 1, {$b['nomor_bab']}, '$judul', $jp, $start_w, $end_w, 'Tatap Muka, E-Modul Flipbook & Mandiri')";
                    $conn->query($sql_ins);
                    $generated_count++;
                }

                // Semester 2 Babs
                $cur_week_s2 = 1;
                $s2_babs_count = $total_bab - $mid;
                if ($s2_babs_count > 0) {
                    $weeks_per_bab_s2 = max(1, floor($pekan_s2 / max(1, $s2_babs_count)));
                    for ($i = $mid; $i < $total_bab; $i++) {
                        $b = $babs[$i];
                        $start_w = $cur_week_s2;
                        $end_w = min($pekan_s2, $cur_week_s2 + $weeks_per_bab_s2 - 1);
                        if ($i === $total_bab - 1) $end_w = $pekan_s2;
                        $cur_week_s2 = $end_w + 1;

                        $judul = $conn->real_escape_string($b['judul_bab']);
                        $jp = ($end_w - $start_w + 1) * 2;

                        $sql_ins = "INSERT INTO kurikulum_prota_promes 
                                    (tahun_ajaran, jenjang, kelas, mapel_nama, semester, bab_nomor, judul_materi, alokasi_jp, minggu_ke_mulai, minggu_ke_selesai, keterangan)
                                    VALUES ('$tahun_ajaran', '$jenjang', 'Kelas 10', '$mapel_esc', 2, {$b['nomor_bab']}, '$judul', $jp, $start_w, $end_w, 'Tatap Muka, E-Modul Flipbook & Mandiri')";
                        $conn->query($sql_ins);
                        $generated_count++;
                    }
                }
            }
        }
    }

    return $generated_count;
}
