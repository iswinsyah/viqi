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
     * Ambil daftar detail tanggal Alpa pada periode bulanan (Cut Off 27 - 26).
     */
    public static function getAlpaMonthlyList($conn, $ustadz_id, $startDate, $endDate) {
        $uid = (int)$ustadz_id;
        $list = [];
        $q = $conn->query("
            SELECT DATE(waktu_absen) as tgl, keterangan 
            FROM absensi_pegawai 
            WHERE ustadz_id = $uid 
              AND DATE(waktu_absen) BETWEEN '$startDate' AND '$endDate' 
              AND (status_kehadiran = 'Alpa' OR keterangan LIKE '%Alpa%' OR keterangan LIKE '%Tanpa Keterangan%')
            GROUP BY DATE(waktu_absen)
            ORDER BY waktu_absen ASC
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
            SELECT DATE(waktu_absen) as tgl, keterangan 
            FROM absensi_pegawai 
            WHERE ustadz_id = $uid 
              AND DATE(waktu_absen) BETWEEN '$semStart' AND '$semEnd' 
              AND (status_kehadiran = 'Alpa' OR keterangan LIKE '%Alpa%' OR keterangan LIKE '%Tanpa Keterangan%')
            GROUP BY DATE(waktu_absen)
            ORDER BY waktu_absen ASC
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
