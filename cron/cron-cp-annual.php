<?php
/**
 * cron-cp-annual.php
 * Runner Otonom Agentic AI: Riset & Standardisasi Capaian Pembelajaran (CP) Pemerintah
 * Dijadwalkan otomatis setiap Tahun Ajaran Baru per tanggal 1 Juli.
 */

// Konfigurasi Environment & Waktu
date_default_timezone_set('Asia/Jakarta');
set_time_limit(300);
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/api-cp-ai.php';

// Pastikan tabel log tahunan tersedia
$conn->query("CREATE TABLE IF NOT EXISTS log_cp_agent_annual (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun_ajaran VARCHAR(20) NOT NULL,
    tanggal_eksekusi DATETIME NOT NULL,
    jenjang VARCHAR(50) NOT NULL,
    total_mapel INT NOT NULL,
    keterangan TEXT NULL,
    executed_by VARCHAR(50) DEFAULT 'Agentic AI Scheduler',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$current_month_num = date('m');
$current_day_num = date('d');
$current_year_num = (int)date('Y');
$tahun_ajaran_aktif = ((int)$current_month_num >= 7) ? $current_year_num . '/' . ($current_year_num + 1) : ($current_year_num - 1) . '/' . $current_year_num;

$is_cli = (php_sapi_name() === 'cli');
$is_force = (isset($_GET['force']) && ($_GET['force'] === '1' || $_GET['force'] === 'true'))
    || ($is_cli && isset($argv[1]) && in_array(strtolower($argv[1]), ['force', '1', '--force', '-f']));
$is_query_status = isset($_GET['action']) && $_GET['action'] === 'get_annual_status';

// Jika dipanggil via AJAX untuk cek status di UI
if ($is_query_status) {
    header('Content-Type: application/json');
    $res_log = $conn->query("SELECT * FROM log_cp_agent_annual ORDER BY id DESC LIMIT 5");
    $history = [];
    if ($res_log) {
        while ($r = $res_log->fetch_assoc()) {
            $history[] = $r;
        }
    }
    
    // Cek apakah tahun ajaran aktif sudah pernah dijalankan
    $res_cur = $conn->query("SELECT COUNT(*) as cnt FROM log_cp_agent_annual WHERE tahun_ajaran = '$tahun_ajaran_aktif'");
    $already_run = ($res_cur && $res_cur->fetch_assoc()['cnt'] > 0);

    echo json_encode([
        'status' => 'success',
        'tahun_ajaran_aktif' => $tahun_ajaran_aktif,
        'current_date' => date('Y-m-d H:i:s'),
        'next_run_date' => ($current_month_num >= '07' ? ($current_year_num + 1) : $current_year_num) . '-07-01 00:00:00',
        'already_run_this_year' => $already_run,
        'history' => $history
    ]);
    exit;
}

// Cek jumlah data CP di database
$res_count_cp = $conn->query("SELECT COUNT(*) as cnt FROM master_cp_kurikulum WHERE status_verifikasi = 'terverifikasi'");
$total_cp_in_db = $res_count_cp ? (int)$res_count_cp->fetch_assoc()['cnt'] : 0;
$need_initial_populate = ($total_cp_in_db < 20);

// Cek apakah hari ini 1 Juli atau jika force run
$is_july_first = ($current_month_num === '07' && $current_day_num === '01');

// Cek log apakah tahun ajaran ini sudah pernah dieksekusi
$chk_run = $conn->query("SELECT COUNT(*) as cnt FROM log_cp_agent_annual WHERE tahun_ajaran = '$tahun_ajaran_aktif'");
$already_executed = ($chk_run && $chk_run->fetch_assoc()['cnt'] > 0);

// Jika bukan 1 Juli, bukan force, sudah pernah dieksekusi, dan data CP sudah lengkap terisi di menu CP -> skip
if (!$is_july_first && !$is_force && $already_executed && !$need_initial_populate) {
    if (!$is_cli) header('Content-Type: application/json');
    echo json_encode([
        'status' => 'skipped',
        'message' => "Tugas tahunan untuk Tahun Ajaran {$tahun_ajaran_aktif} sudah selesai dieksekusi. Jadwal berikutnya: 1 Juli mendatang.",
        'tahun_ajaran' => $tahun_ajaran_aktif,
        'total_cp_ready' => $total_cp_in_db
    ]);
    exit;
}

// EKSEKUSI PENUH: RISET & STANDARISASI CP
$jenjang_list = ['SMP', 'SMA'];
$grand_total = 0;
$execution_report = [];

foreach ($jenjang_list as $jjg) {
    $fase = ($jjg === 'SMP') ? 'Fase D' : 'Fase E & F';
    $res_m = $conn->query("SELECT DISTINCT nama_mapel, kode_mapel FROM master_mapel WHERE (kategori_mapel = 'Diknas' OR kategori_mapel = 'Nasional') AND status_aktif = 1 ORDER BY nama_mapel ASC");
    
    $jjg_count = 0;
    if ($res_m) {
        while ($rm = $res_m->fetch_assoc()) {
            $nm = $rm['nama_mapel'];
            $kd = $rm['kode_mapel'] ?? '';
            $is_sma_only = in_array(strtolower($nm), ['fisika', 'kimia', 'biologi', 'sosiologi', 'ekonomi', 'geografi', 'sejarah']);
            $is_smp_only = in_array(strtolower($nm), ['ipa (ilmu pengetahuan alam)', 'ips (ilmu pengetahuan sosial)', 'ipa', 'ips']);

            if ($jjg === 'SMP' && $is_sma_only) continue;
            if ($jjg === 'SMA' && $is_smp_only) continue;

            $kb = getOfficialCPKnowledgeBase($nm, $jjg, $fase);
            $nm_esc = $conn->real_escape_string($nm);
            $kd_esc = $conn->real_escape_string($kd);
            $rasional = $conn->real_escape_string($kb['rasional_mapel']);
            $tujuan = $conn->real_escape_string($kb['tujuan_mapel']);
            $karakteristik = $conn->real_escape_string($kb['karakteristik_mapel']);
            $elemen_json = $conn->real_escape_string(json_encode($kb['elemen_cp'], JSON_UNESCAPED_UNICODE));
            $sumber = $conn->real_escape_string($kb['sumber_rujukan']);

            // Simpan / update master_cp_kurikulum
            $conn->query("INSERT INTO master_cp_kurikulum 
                (jenjang, fase, nama_mapel, kode_mapel, rasional_mapel, tujuan_mapel, karakteristik_mapel, elemen_cp, sumber_rujukan, status_verifikasi, last_generated_at)
                VALUES ('$jjg', '$fase', '$nm_esc', '$kd_esc', '$rasional', '$tujuan', '$karakteristik', '$elemen_json', '$sumber', 'terverifikasi', NOW())
                ON DUPLICATE KEY UPDATE 
                    kode_mapel = IF('$kd_esc' != '', '$kd_esc', kode_mapel),
                    rasional_mapel = VALUES(rasional_mapel),
                    tujuan_mapel = VALUES(tujuan_mapel),
                    karakteristik_mapel = VALUES(karakteristik_mapel),
                    elemen_cp = VALUES(elemen_cp),
                    sumber_rujukan = VALUES(sumber_rujukan),
                    status_verifikasi = 'terverifikasi',
                    last_generated_at = NOW()");

            // Auto-sync ke master_silabus
            $kelas_silabus = "{$fase} ({$jjg})";
            $silabus_cp = [];
            foreach ($kb['elemen_cp'] as $el) {
                $silabus_cp[] = [
                    'elemen' => $el['elemen'],
                    'cp' => $el['deskripsi'] ?? ($el['cp'] ?? '')
                ];
            }
            $silabus_json = $conn->real_escape_string(json_encode($silabus_cp, JSON_UNESCAPED_UNICODE));

            $chk_s = $conn->query("SELECT id FROM master_silabus WHERE mata_pelajaran = '$nm_esc' AND kelas LIKE '%$jjg%'");
            if ($chk_s && $chk_s->num_rows > 0) {
                $s_id = $chk_s->fetch_assoc()['id'];
                $conn->query("UPDATE master_silabus SET deskripsi_mapel='$rasional', capaian_pembelajaran='$silabus_json', kelas='$kelas_silabus' WHERE id=$s_id");
            } else {
                $conn->query("INSERT INTO master_silabus (mata_pelajaran, kelas, deskripsi_mapel, capaian_pembelajaran) VALUES ('$nm_esc', '$kelas_silabus', '$rasional', '$silabus_json')");
            }

            $jjg_count++;
        }
    }

    $executed_by = $is_force ? 'Manual Trigger (Yayasan)' : ($need_initial_populate ? 'Initial Direct Execution' : 'Scheduler 1 Juli');
    $conn->query("INSERT INTO log_cp_agent_annual (tahun_ajaran, tanggal_eksekusi, jenjang, total_mapel, keterangan, executed_by) 
        VALUES ('$tahun_ajaran_aktif', NOW(), '$jjg', $jjg_count, 'Penyelarasan Baku CP BSKAP 032/H/KR/2024', '$executed_by')");

    $execution_report[$jjg] = $jjg_count;
    $grand_total += $jjg_count;
}

// Log status ke file
$annual_log_txt = __DIR__ . '/agent_annual_cp_log.txt';
@file_put_contents($annual_log_txt, "SUCCESS_{$tahun_ajaran_aktif}_" . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

if (!$is_cli) header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => "Agent AI sukses mengeksekusi tugas tahunan 1 Juli untuk Tahun Ajaran {$tahun_ajaran_aktif}!",
    'tahun_ajaran' => $tahun_ajaran_aktif,
    'total_mapel_diperbarui' => $grand_total,
    'details' => $execution_report,
    'executed_at' => date('Y-m-d H:i:s')
]);
