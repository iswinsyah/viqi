<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'dashboard_kpi';

// Asumsi kita punya ID user yang login, untuk sementara kita hardcode ID = 1
$user_id = $_SESSION['user_id'] ?? 1; 

// --- SETUP & AMBIL PENGATURAN GAJI ---
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_gaji (
    id INT PRIMARY KEY DEFAULT 1,
    tarif_dasar INT,
    bonus_grade_b INT,
    bonus_grade_a INT
)");
$conn->query("INSERT IGNORE INTO pengaturan_gaji (id, tarif_dasar, bonus_grade_b, bonus_grade_a) VALUES (1, 25000, 10, 20)");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_gaji'])) {
    $tarif = (int)$_POST['tarif_dasar'];
    $bonus_b = (int)$_POST['bonus_grade_b'];
    $bonus_a = (int)$_POST['bonus_grade_a'];
    $conn->query("UPDATE pengaturan_gaji SET tarif_dasar=$tarif, bonus_grade_b=$bonus_b, bonus_grade_a=$bonus_a WHERE id=1");
    $pesan_sukses = "Pengaturan Gaji & Persentase Bonus berhasil diperbarui!";
}

$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji->fetch_assoc();

$tarif_dasar_per_pertemuan = $data_gaji['tarif_dasar'] ?? 25000;
$persentase_bonus_grade_b = $data_gaji['bonus_grade_b'] ?? 10;
$persentase_bonus_grade_a = $data_gaji['bonus_grade_a'] ?? 20;

// --- LOGIC PERHITUNGAN KPI (CONTOH SEDERHANA) ---
// Di aplikasi nyata, ini akan jadi fungsi yang kompleks dan mungkin dijalankan oleh CRON JOB bulanan

// 1. Administrasi & Disiplin (Bobot 20%)
$skor_jurnal = 85; // Placeholder. Logic: Hitung persentase jurnal yg diisi tepat waktu dari tabel jurnal_mengajar
$skor_kehadiran = 98; // Placeholder. Logic: Hitung dari log QR Code
$skor_kehadiran_rapat = 100; // Placeholder. Logic: Cek apakah ada record di log_kehadiran_rapat bulan ini.
$skor_administrasi = (($skor_jurnal * 0.4) + ($skor_kehadiran * 0.4) + ($skor_kehadiran_rapat * 0.2));

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

$jumlah_pertemuan = 24; // Dummy: Nanti dihitung otomatis dari tabel jurnal_mengajar

$gaji_pokok = $jumlah_pertemuan * $tarif_dasar_per_pertemuan;
$bonus_kinerja = 0;

if ($total_skor_kpi >= 85) { // Grade A
    $bonus_kinerja = $gaji_pokok * ($persentase_bonus_grade_a / 100);
    $predikat = "Sangat Baik (Grade A)";
} elseif ($total_skor_kpi >= 70) { // Grade B
    $bonus_kinerja = $gaji_pokok * ($persentase_bonus_grade_b / 100);
    $predikat = "Baik (Grade B)";
} else { // Grade C
    $bonus_kinerja = 0;
    $predikat = "Cukup (Grade C)";
}

$gaji_total = $gaji_pokok + $bonus_kinerja;

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

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <!-- FORM PENGATURAN GAJI & BONUS -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="font-bold text-gray-800 mb-4 border-b pb-2"><i class="fas fa-cog text-gray-500 mr-2"></i>Template Setting Gaji & Bonus</h2>
                <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="update_gaji" value="1">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Gaji Pokok / Pertemuan (Rp)</label>
                        <input type="number" name="tarif_dasar" value="<?= $tarif_dasar_per_pertemuan ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Bonus Grade B (%)</label>
                        <input type="number" name="bonus_grade_b" value="<?= $persentase_bonus_grade_b ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">Bonus Grade A (%)</label>
                        <input type="number" name="bonus_grade_a" value="<?= $persentase_bonus_grade_a ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" required>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-4 rounded-lg transition shadow-sm flex justify-center items-center"><i class="fas fa-save mr-2"></i> Simpan Setting</button>
                    </div>
                </form>
            </div>

            <!-- WIDGET UTAMA SKOR & INSENTIF -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="md:col-span-1 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-xl shadow-lg p-6 flex flex-col justify-center items-center text-center">
                    <h3 class="font-semibold opacity-80">Total Skor Kinerja Anda</h3>
                    <p class="text-6xl font-bold my-2"><?= number_format($total_skor_kpi, 2) ?></p>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium border border-white/30 mb-3"><?= $predikat ?></span>
                    <div class="w-full h-1 bg-white/30 rounded-full mt-2"><div class="h-1 bg-white rounded-full" style="width: <?= $total_skor_kpi ?>%;"></div></div>
                </div>
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-center">
                    <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Simulasi Gaji & Bonus Kinerja</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center mb-4">
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 mb-1">Gaji Pokok</p>
                            <p class="text-lg font-bold text-gray-800">Rp <?= number_format($gaji_pokok, 0, ',', '.') ?></p>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <p class="text-xs text-gray-500 mb-1">Predikat Kinerja</p>
                            <p class="text-lg font-bold text-gray-800"><?= $predikat ?></p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <p class="text-xs text-blue-600 mb-1">Persentase Bonus</p>
                            <p class="text-lg font-bold text-blue-700">
                                <?php 
                                    if ($total_skor_kpi >= 85) echo $persentase_bonus_grade_a . '%';
                                    elseif ($total_skor_kpi >= 70) echo $persentase_bonus_grade_b . '%';
                                    else echo '0%';
                                ?>
                            </p>
                        </div>
                        <div class="bg-emerald-50 p-3 rounded-lg border border-emerald-100">
                            <p class="text-xs text-emerald-600 mb-1">Bonus Kinerja</p>
                            <p class="text-lg font-bold text-emerald-700">+ Rp <?= number_format($bonus_kinerja, 0, ',', '.') ?></p>
                        </div>
                    </div>
                    <div class="flex justify-between items-center bg-gray-900 text-white p-4 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-300">Total Gaji Bulan Ini</p>
                            <p class="text-xs text-gray-400 mt-1">Gaji Pokok + Bonus</p>
                        </div>
                        <p class="text-3xl font-bold text-amber-400">Rp <?= number_format($gaji_total, 0, ',', '.') ?></p>
                    </div>
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
                        <li class="flex justify-between"><span>Kehadiran Rapat</span> <span class="font-bold"><?= $skor_kehadiran_rapat ?></span></li>
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