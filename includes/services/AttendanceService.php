<?php
/**
 * AttendanceService.php
 * Single Source of Truth untuk perhitungan Absensi, Cut-off Periode Kerja (27 s/d 26),
 * serta akumulasi Alfa Bulanan dan Semesteran bagi Ustadz & Pegawai Villa Quran.
 */

class AttendanceService {

    /**
     * Dapatkan rentang tanggal cut off periode kerja (27 bulan lalu s/d 26 bulan ini).
     * Jika $month dan $year tidak diisi (null), otomatis mendeteksi periode berjalan:
     * - Jika hari ini >= tgl 27, masuk ke siklus periode bulan depan.
     * - Jika hari ini < tgl 27, masuk ke siklus periode bulan ini.
     * 
     * Contoh untuk bulan 11 (November) 2026:
     * - Start: 2026-10-27
     * - End  : 2026-11-26
     */
    public static function getCutoffRange($month = null, $year = null) {
        $curD = (int)date('d');
        $curM = (int)date('n');
        $curY = (int)date('Y');

        if ($month === null && $year === null) {
            if ($curD >= 27) {
                $m = ($curM === 12) ? 1 : $curM + 1;
                $y = ($curM === 12) ? $curY + 1 : $curY;
            } else {
                $m = $curM;
                $y = $curY;
            }
        } else {
            $m = (int)$month;
            $y = $year ? (int)$year : $curY;
        }

        $currentRef = sprintf('%04d-%02d-01', $y, $m);
        $prevMonthTs = strtotime('-1 month', strtotime($currentRef));
        
        $startDate = date('Y-m-27', $prevMonthTs);
        $endDate = sprintf('%04d-%02d-26', $y, $m);

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $namaBulanSingkat = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $prevM = (int)date('n', $prevMonthTs);
        $prevY = (int)date('Y', $prevMonthTs);

        $label = sprintf('27 %s %s – 26 %s %s', 
            $namaBulanSingkat[$prevM] ?? '',
            $prevY,
            $namaBulanSingkat[$m] ?? '',
            $y
        );

        $labelLengkap = sprintf('27 %s %s s/d 26 %s %s',
            $namaBulan[$prevM] ?? '',
            $prevY,
            $namaBulan[$m] ?? '',
            $y
        );

        $periodeLabel = sprintf('Periode %s %s', $namaBulan[$m] ?? '', $y);

        return [
            'month' => $m,
            'year' => $y,
            'month_name' => $namaBulan[$m] ?? '',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label,
            'label_lengkap' => $labelLengkap,
            'periode_label' => $periodeLabel
        ];
    }

    /**
     * Dapatkan rentang semester berjalan.
     * Ganjil: 1 Juli – 31 Desember
     * Genap : 1 Januari – 30 Juni
     */
    public static function getCurrentSemester($month = null, $year = null) {
        $m = $month ? (int)$month : (int)date('n');
        $y = $year ? (int)$year : (int)date('Y');

        if ($m >= 7) {
            return [
                'name' => "Semester Ganjil $y/" . ($y + 1),
                'short' => "Ganjil $y",
                'label' => "$y/" . ($y + 1) . " Ganjil",
                'semester_str' => $y . '/' . ($y + 1) . '-Ganjil',
                'start_date' => "$y-07-01",
                'end_date' => "$y-12-31",
                'range_label' => sprintf('01 Jul %s s/d 31 Des %s', $y, $y)
            ];
        } else {
            return [
                'name' => "Semester Genap " . ($y - 1) . "/$y",
                'short' => "Genap $y",
                'label' => ($y - 1) . "/$y Genap",
                'semester_str' => ($y - 1) . '/' . $y . '-Genap',
                'start_date' => "$y-01-01",
                'end_date' => "$y-06-30",
                'range_label' => sprintf('01 Jan %s s/d 30 Jun %s', $y, $y)
            ];
        }
    }

    /**
     * Cek apakah sebuah tanggal merupakan Hari Libur / Non-Efektif
     * berdasarkan hari Ahad dan Kalender Akademik.
     */
    public static function isHoliday($conn, $dateString) {
        $ts = strtotime($dateString);
        if (!$ts) return false;

        // 1. Hari Ahad (Minggu) selalu libur
        if ((int)date('N', $ts) === 7) {
            return [
                'is_holiday' => true,
                'code' => 'AHD',
                'name' => 'Hari Ahad (Libur Mingguan)'
            ];
        }

        // 2. Cek di tabel kalender_akademik
        $d = date('Y-m-d', $ts);
        $q = $conn->query("SELECT status_hari, keterangan FROM kalender_akademik WHERE tanggal = '$d' LIMIT 1");
        if ($q && $q->num_rows > 0) {
            $row = $q->fetch_assoc();
            $status = trim($row['status_hari'] ?? '');
            if ($status !== '' && strtolower($status) !== 'efektif') {
                return [
                    'is_holiday' => true,
                    'code' => $status,
                    'name' => !empty($row['keterangan']) ? $row['keterangan'] : $status
                ];
            }
        }

        return false;
    }

    /**
     * Dapatkan peta semua hari libur dalam rentang tanggal
     */
    public static function getHolidaysMap($conn, $startDate, $endDate) {
        $map = [];
        $q = $conn->query("SELECT tanggal, status_hari, keterangan FROM kalender_akademik WHERE tanggal BETWEEN '$startDate' AND '$endDate' AND status_hari != 'efektif'");
        if ($q) {
            while ($r = $q->fetch_assoc()) {
                $map[$r['tanggal']] = !empty($r['keterangan']) ? $r['keterangan'] : $r['status_hari'];
            }
        }
        return $map;
    }

    /**
     * Pemutihan dan Pembersihan Data Alpa Fiktif Masa Lalu & Hari Libur.
     * Mengembalikan status akun ke Aktif dan menghapus SP palsu akibat data fiktif.
     */
    public static function cleanupInvalidAlpa($conn) {
        // Tanggal resmi dimulainya sistem absensi baru (Cut off berjalan 27 November 2026)
        $tgl_efektif = '2026-11-27';

        // 1. Hapus alpa otomatis masa lalu sebelum kebijakan absensi baru berjalan
        @$conn->query("DELETE FROM absensi_pegawai 
                       WHERE keterangan LIKE '%Alpa (Tanpa Keterangan)%' 
                         AND DATE(waktu_absen) < '$tgl_efektif'");

        // 2. Hapus alpa yang jatuh pada hari Ahad (Minggu)
        @$conn->query("DELETE FROM absensi_pegawai 
                       WHERE (status_kehadiran = 'Alpa' OR keterangan LIKE '%Alpa%') 
                         AND DAYOFWEEK(waktu_absen) = 1");

        // 3. Hapus alpa yang jatuh pada hari libur resmi Kalender Akademik (misal 17 Agustus HUT RI, dll)
        @$conn->query("DELETE a FROM absensi_pegawai a
                       INNER JOIN kalender_akademik k ON DATE(a.waktu_absen) = k.tanggal
                       WHERE (a.status_kehadiran = 'Alpa' OR a.keterangan LIKE '%Alpa%')
                         AND k.status_hari != 'efektif'");

        // 4. Bersihkan Surat Peringatan (SP-1, SP-2, BLOKIR) yang terbit karena alpa fiktif masa lalu
        @$conn->query("DELETE FROM surat_peringatan_pegawai 
                       WHERE alasan LIKE '%3 hari dalam semester%' 
                          OR alasan LIKE '%Total%Alpa%' 
                          OR alasan LIKE '%PEMBLOKIRAN AKUN%'");

        // 5. Pulihkan status pegawai yang sempat terblokir otomatis secara keliru
        @$conn->query("UPDATE akun_ustadz SET status_pegawai = 'Pegawai Utama' WHERE status_pegawai = 'Nonaktif' AND role LIKE '%kepala_sekolah%'");
    }

    /**
     * Ambil daftar detail tanggal Alpa pada periode bulanan (Cut Off 27 - 26).
     */
    public static function getAlpaMonthlyList($conn, $ustadz_id, $startDate, $endDate) {
        $uid = (int)$ustadz_id;
        $list = [];
        $q = $conn->query("
            SELECT DATE(a.waktu_absen) as tgl, a.keterangan 
            FROM absensi_pegawai a
            LEFT JOIN kalender_akademik k ON DATE(a.waktu_absen) = k.tanggal AND k.status_hari != 'efektif'
            WHERE a.ustadz_id = $uid 
              AND DATE(a.waktu_absen) BETWEEN '$startDate' AND '$endDate' 
              AND (a.status_kehadiran = 'Alpa' OR a.keterangan LIKE '%Alpa%' OR a.keterangan LIKE '%Tanpa Keterangan%')
              AND DAYOFWEEK(a.waktu_absen) != 1
              AND k.tanggal IS NULL
            GROUP BY DATE(a.waktu_absen)
            ORDER BY a.waktu_absen ASC
        ");
        if ($q) {
            while ($r = $q->fetch_assoc()) {
                $list[] = $r;
            }
        }
        return $list;
    }

    /**
     * Ambil daftar detail tanggal Alpa pada satu semester penuh.
     */
    public static function getAlpaSemesterList($conn, $ustadz_id, $semStart, $semEnd) {
        $uid = (int)$ustadz_id;
        $list = [];
        $q = $conn->query("
            SELECT DATE(a.waktu_absen) as tgl, a.keterangan 
            FROM absensi_pegawai a
            LEFT JOIN kalender_akademik k ON DATE(a.waktu_absen) = k.tanggal AND k.status_hari != 'efektif'
            WHERE a.ustadz_id = $uid 
              AND DATE(a.waktu_absen) BETWEEN '$semStart' AND '$semEnd' 
              AND (a.status_kehadiran = 'Alpa' OR a.keterangan LIKE '%Alpa%' OR a.keterangan LIKE '%Tanpa Keterangan%')
              AND DAYOFWEEK(a.waktu_absen) != 1
              AND k.tanggal IS NULL
            GROUP BY DATE(a.waktu_absen)
            ORDER BY a.waktu_absen ASC
        ");
        if ($q) {
            while ($r = $q->fetch_assoc()) {
                $list[] = $r;
            }
        }
        return $list;
    }

    /**
     * Status disiplin berdasarkan akumulasi alpa dalam 1 semester.
     * Threshold: >= 3 alpa memicu status Peringatan SP-1.
     */
    public static function getDisciplineStatus($alpaSemester) {
        $alpa = (int)$alpaSemester;
        if ($alpa >= 6) {
            return [
                'status' => 'Sanksi Berat: Akun Dibekukan',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-300 font-bold',
                'icon' => 'fa-ban text-rose-600',
                'is_sp' => true,
                'sp_level' => 'Dibekukan'
            ];
        } elseif ($alpa >= 5) {
            return [
                'status' => 'Peringatan Keras: Terbit SP-2',
                'badge' => 'bg-rose-100 text-rose-800 border-rose-300 font-bold',
                'icon' => 'fa-triangle-exclamation text-rose-600',
                'is_sp' => true,
                'sp_level' => 'SP-2'
            ];
        } elseif ($alpa >= 3) {
            return [
                'status' => 'Peringatan Disiplin: Terbit SP-1',
                'badge' => 'bg-amber-100 text-amber-800 border-amber-300 font-bold',
                'icon' => 'fa-triangle-exclamation text-amber-600',
                'is_sp' => true,
                'sp_level' => 'SP-1'
            ];
        } elseif ($alpa >= 1) {
            return [
                'status' => 'Perhatian Khusus',
                'badge' => 'bg-blue-50 text-blue-800 border-blue-200',
                'icon' => 'fa-info-circle text-blue-600',
                'is_sp' => false,
                'sp_level' => null
            ];
        } else {
            return [
                'status' => 'Disiplin Sangat Baik (0 Alpa)',
                'badge' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'icon' => 'fa-circle-check text-emerald-600',
                'is_sp' => false,
                'sp_level' => null
            ];
        }
    }
}
