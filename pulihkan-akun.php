<?php
/**
 * Script Pemulihan Darurat Akun Pegawai / Asatidz (SADIGS 4.0)
 * Memulihkan seluruh akun yang terkena false-positive deteksi Fake GPS.
 */
require_once 'koneksi.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$recovered_ustadz = 0;
$recovered_users = 0;
$deleted_bad_logs = 0;

if (isset($conn) && $conn) {
    // 1. Pulihkan status akun ustadz
    $conn->query("UPDATE akun_ustadz SET status_pegawai = 'Pengabdian', is_active = 1 WHERE status_pegawai = 'Nonaktif' AND (id IN (SELECT DISTINCT ustadz_id FROM absensi_pegawai WHERE status_kehadiran LIKE '%Ditolak (Akun Dimatikan)%' OR keterangan LIKE '%Terdeteksi Fake GPS%') OR nama LIKE '%Imaduddien%' OR username LIKE '%imaduddien%')");
    $recovered_ustadz = max(0, $conn->affected_rows);

    // 2. Pulihkan status app_users
    $conn->query("UPDATE app_users SET status_aktif = 1 WHERE status_aktif = 0 AND (ref_id IN (SELECT DISTINCT ustadz_id FROM absensi_pegawai WHERE status_kehadiran LIKE '%Ditolak (Akun Dimatikan)%' OR keterangan LIKE '%Terdeteksi Fake GPS%') OR nama_lengkap LIKE '%Imaduddien%' OR username LIKE '%imaduddien%')");
    $recovered_users = max(0, $conn->affected_rows);

    // 3. Hapus log penolakan salah hari ini agar ustadz bisa langsung melakukan absensi ulang
    $conn->query("DELETE FROM absensi_pegawai WHERE DATE(waktu_absen) = CURDATE() AND (status_kehadiran LIKE '%Ditolak (Akun Dimatikan)%' OR keterangan LIKE '%Terdeteksi Fake GPS%')");
    $deleted_bad_logs = max(0, $conn->affected_rows);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pemulihan Akun Pegawai | SADIGS Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center">
        <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-5 text-3xl shadow-inner">
            <i class="fas fa-user-check"></i>
        </div>
        <h1 class="text-2xl font-black text-slate-800 tracking-tight">Akun Berhasil Dipulihkan!</h1>
        <p class="text-xs text-slate-500 mt-2 leading-relaxed">
            Sistem telah menonaktifkan aturan false-positive Fake GPS dan memulihkan akses login untuk seluruh akun pegawai & asatidz yang terdampak.
        </p>

        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 my-6 text-left text-xs space-y-2">
            <div class="flex justify-between items-center text-slate-700">
                <span><i class="fas fa-check text-emerald-500 mr-1.5"></i> Akun Asatidz Dipulihkan:</span>
                <span class="font-bold text-slate-900"><?= $recovered_ustadz ?> akun</span>
            </div>
            <div class="flex justify-between items-center text-slate-700">
                <span><i class="fas fa-check text-emerald-500 mr-1.5"></i> User App Aktif:</span>
                <span class="font-bold text-slate-900"><?= $recovered_users ?> user</span>
            </div>
            <div class="flex justify-between items-center text-slate-700">
                <span><i class="fas fa-trash-alt text-amber-500 mr-1.5"></i> Log Penolakan Direset:</span>
                <span class="font-bold text-slate-900"><?= $deleted_bad_logs ?> data</span>
            </div>
        </div>

        <div class="space-y-3">
            <a href="login.php" class="block w-full py-3.5 px-4 bg-[#0b8478] hover:bg-[#086a60] text-white font-bold text-xs rounded-xl shadow-md transition shadow-teal-900/10">
                <i class="fas fa-sign-in-alt mr-2"></i> Ke Halaman Login Sekarang
            </a>
            <a href="dashboard.php" class="block w-full py-3 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                <i class="fas fa-house mr-2"></i> Buka Beranda
            </a>
        </div>
    </div>
</body>
</html>
