<?php
/**
 * AttendanceService.php
 * Single Source of Truth untuk perhitungan Absensi, Cut-off Periode Kerja (27 s/d 26),
 * serta akumulasi Alfa Bulanan dan Semesteran bagi Ustadz & Pegawai Villa Quran.
 */

class AttendanceService {

    /**
     * Dapatkan rentang tanggal cut off periode kerja (27 bulan lalu s/d 26 bulan ini).
     * Contoh untuk bulan 11 (November) 2026:
     * - Start: 2026-10-27
     * - End  : 2026-11-26
     */
    public static function getCutoffRange($month = null, $year = null) {
        $m = $month ? (int)$month : (int)date('n');
        $y = $year ? (int)$year : (int)date('Y');

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

        return [
            'month' => $m,
            'year' => $y,
            'month_name' => $namaBulan[$m] ?? '',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'label' => $label,
            'label_lengkap' => $labelLengkap
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
                'start_date' => "$y-07-01",
                'end_date' => "$y-12-31"
            ];
        } else {
            return [
                'name' => "Semester Genap " . ($y - 1) . "/$y",
                'short' => "Genap $y",
                'start_date' => "$y-01-01",
                'end_date' => "$y-06-30"
            ];
        }
    }

    /**
     * Hitung total hari Alpa pada periode cut off bulanan (27 - 26).
     */
    public static function getAlpaMonthly($conn, $ustadz_id, $startDate, $endDate) {
        $uid = (int)$ustadz_id;
        $q = $conn->query("SELECT COUNT(id) as c FROM absensi_pegawai 
                           WHERE ustadz_id = $uid 
                           AND tanggal BETWEEN '$startDate' AND '$endDate' 
                           AND (status = 'Alpa' OR status = 'A')");
        if ($q && $r = $q->fetch_assoc()) {
            return (int)$r['c'];
        }
        return 0;
    }

    /**
     * Hitung total hari Alpa pada satu semester penuh.
     */
    public static function getAlpaSemester($conn, $ustadz_id, $semStart, $semEnd) {
        $uid = (int)$ustadz_id;
        $q = $conn->query("SELECT COUNT(id) as c FROM absensi_pegawai 
                           WHERE ustadz_id = $uid 
                           AND tanggal BETWEEN '$semStart' AND '$semEnd' 
                           AND (status = 'Alpa' OR status = 'A')");
        if ($q && $r = $q->fetch_assoc()) {
            return (int)$r['c'];
        }
        return 0;
    }

    /**
     * Status disiplin berdasarkan akumulasi alpa dalam 1 semester.
     * Threshold: >= 3 alpa memicu status Peringatan SP-1.
     */
    public static function getDisciplineStatus($alpaSemester) {
        $alpa = (int)$alpaSemester;
        if ($alpa >= 3) {
            return [
                'status' => 'Peringatan SP-1',
                'badge' => 'bg-rose-100 text-rose-700 border-rose-300 font-bold',
                'is_sp' => true,
                'sp_level' => 'SP-1'
            ];
        } elseif ($alpa >= 1) {
            return [
                'status' => 'Perhatian Khusus',
                'badge' => 'bg-amber-100 text-amber-700 border-amber-300',
                'is_sp' => false,
                'sp_level' => null
            ];
        } else {
            return [
                'status' => 'Disiplin Sangat Baik',
                'badge' => 'bg-emerald-100 text-emerald-700 border-emerald-300',
                'is_sp' => false,
                'sp_level' => null
            ];
        }
    }
}
