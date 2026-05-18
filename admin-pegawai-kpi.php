<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'dashboard_kpi';

// Asumsi kita punya ID user yang login, untuk sementara kita hardcode ID = 1
$user_id = $_SESSION['user_id'] ?? 1; 

// --- LOGIC PERHITUNGAN KPI (CONTOH SEDERHANA) ---
// Di aplikasi nyata, ini akan jadi fungsi yang kompleks dan mungkin dijalankan oleh CRON JOB bulanan

// 1. Administrasi & Disiplin (Bobot 20%)
$skor_jurnal = 85; // Placeholder. Logic: Hitung persentase jurnal yg diisi tepat waktu dari tabel jurnal_mengajar
$skor_kehadiran = 98; // Placeholder. Logic: Hitung dari log QR Code
$skor_administrasi = (($skor_jurnal * 0.5) + ($skor_kehadiran * 0.5));

// 2. Kualitas Pengajaran (Bobot 40%)
$skor_penggunaan_ai = 70; // Placeholder. Logic: Hitung jumlah penggunaan AI dari tabel log_aktivitas_ai
$skor_supervisi = 92; // Placeholder. Logic: Ambil skor terakhir dari tabel supervisi_mengajar
$skor_kualitas_pengajaran = (($skor_penggunaan_ai * 0.4) + ($skor_supervisi * 0.6));

// 3. Capaian Santri (Bobot 30%)
$skor_rata_nilai = 88; // Placeholder. Logic: Hitung AVG(nilai) dari bank_nilai
$skor_pertumbuhan = 90; // Placeholder. Logic: Hitung selisih AVG(nilai) UTS vs UAS
$skor_capaian_santri = (($skor_rata_nilai * 0.6) + ($skor_pertumbuhan * 0.4));

// 4. Pengembangan Diri (Bobot 10%)
$skor_kontribusi_silabus = 100; // Placeholder. Logic: Cek apakah user pernah input/update di master_silabus
$skor_pengembangan_diri = $skor_kontribusi_silabus;

// Total Skor KPI
$total_skor_kpi = ($skor_administrasi * 0.20) + ($skor_kualitas_pengajaran * 0.40) + ($skor_capaian_santri * 0.30) + ($skor_pengembangan_diri * 0.10);

// Tunjangan Kinerja (Contoh)
$tunjangan_maksimal = 1000000;
$insentif_diterima = ($total_skor_kpi / 100) * $tunjangan_maksimal;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard KPI | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chart-bar text-cyan-600 mr-2"></i>Dashboard Key Performance Indicator (KPI)</h1>
                <select class="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium">
                    <option>Periode: Mei 2026</option>
                    <option>Periode: April 2026</option>
                </select>
            </div>

            <!-- WIDGET UTAMA SKOR & INSENTIF -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="md:col-span-1 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-center items-center text-center">
                    <h3 class="font-semibold opacity-80">Total Skor Kinerja Anda</h3>
                    <p class="text-6xl font-bold my-2"><?= number_format($total_skor_kpi, 2) ?></p>
                    <div class="w-24 h-1 bg-white/30 rounded-full"></div>
                </div>
                <div class="md:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-2">Perkiraan Insentif Kinerja Bulan Ini</h3>
                    <p class="text-4xl font-bold text-emerald-600">Rp <?= number_format($insentif_diterima, 0, ',', '.') ?></p>
                    <p class="text-sm text-gray-500 mt-2">Perhitungan: (<span class="font-bold"><?= number_format($total_skor_kpi, 2) ?></span> / 100) x Tunjangan Maksimal (Rp <?= number_format($tunjangan_maksimal, 0, ',', '.') ?>). Angka ini akan ditambahkan di luar gaji pokok kehadiran Anda.</p>
                </div>
            </div>

            <!-- DETAIL SKOR PER PILAR -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pilar 1 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-clipboard-check text-blue-500 mr-2"></i> Administrasi (20%)</h4>
                    <p class="text-3xl font-bold text-blue-600 my-3"><?= number_format($skor_administrasi, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Ketepatan Jurnal</span> <span class="font-bold"><?= $skor_jurnal ?></span></li>
                        <li class="flex justify-between"><span>Kehadiran (QR)</span> <span class="font-bold"><?= $skor_kehadiran ?></span></li>
                    </ul>
                </div>
                <!-- Pilar 2 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-chalkboard-teacher text-purple-500 mr-2"></i> Kualitas Ajar (40%)</h4>
                    <p class="text-3xl font-bold text-purple-600 my-3"><?= number_format($skor_kualitas_pengajaran, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Inovasi (Pakai AI)</span> <span class="font-bold"><?= $skor_penggunaan_ai ?></span></li>
                        <li class="flex justify-between"><span>Supervisi Kepsek</span> <span class="font-bold"><?= $skor_supervisi ?></span></li>
                    </ul>
                </div>
                <!-- Pilar 3 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-graduation-cap text-emerald-500 mr-2"></i> Capaian Santri (30%)</h4>
                    <p class="text-3xl font-bold text-emerald-600 my-3"><?= number_format($skor_capaian_santri, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Rata-rata Nilai</span> <span class="font-bold"><?= $skor_rata_nilai ?></span></li>
                        <li class="flex justify-between"><span>Pertumbuhan Nilai</span> <span class="font-bold"><?= $skor_pertumbuhan ?></span></li>
                    </ul>
                </div>
                <!-- Pilar 4 -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h4 class="font-bold text-gray-800 flex items-center"><i class="fas fa-rocket text-amber-500 mr-2"></i> Pengembangan Diri (10%)</h4>
                    <p class="text-3xl font-bold text-amber-600 my-3"><?= number_format($skor_pengembangan_diri, 2) ?></p>
                    <ul class="text-xs space-y-2 text-gray-600">
                        <li class="flex justify-between"><span>Kontribusi Silabus</span> <span class="font-bold"><?= $skor_kontribusi_silabus ?></span></li>
                        <li class="flex justify-between"><span>Upload Sertifikat</span> <span class="font-bold">0</span></li>
                    </ul>
                </div>
            </div>

        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>