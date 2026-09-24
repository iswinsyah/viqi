<?php
// admin-brosur-settings.php
// Panel Pengaturan Brosur PSB Digital & Ganti Background Pinterest
// Villa Quran Indonesia

require_once 'auth.php';
require_once 'koneksi.php';

// Inisialisasi self-healing tabel pengaturan_brosur
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_brosur (
    id INT PRIMARY KEY DEFAULT 1,
    tahun_ajaran VARCHAR(100) DEFAULT '2026/2027',
    periode_gelombang VARCHAR(100) DEFAULT 'Gelombang 1 — Kuota Terbatas',
    kuota_santri INT DEFAULT 20,
    cover_bg_url TEXT,
    cover_overlay_opacity DECIMAL(3,2) DEFAULT 0.85,
    theme_preset VARCHAR(50) DEFAULT 'madinah',
    music_url TEXT,
    biaya_pendaftaran INT DEFAULT 350000,
    biaya_pangkal INT DEFAULT 12500000,
    biaya_tahunan INT DEFAULT 2500000,
    biaya_spp INT DEFAULT 1650000,
    diskon_gelombang INT DEFAULT 2000000,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("INSERT IGNORE INTO pengaturan_brosur (id, tahun_ajaran, periode_gelombang, kuota_santri, cover_bg_url, cover_overlay_opacity, theme_preset, music_url, biaya_pendaftaran, biaya_pangkal, biaya_tahunan, biaya_spp, diskon_gelombang)
VALUES (1, '2026/2027', 'Gelombang 1 — Kuota Terbatas', 20, 'https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80', 0.85, 'madinah', 'upload/backsound.mp3', 350000, 12500000, 2500000, 1650000, 2000000)");

$pesan_sukses = '';
$pesan_error  = '';

// Proses Simpan Pengaturan
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tahun_ajaran      = $conn->real_escape_string($_POST['tahun_ajaran'] ?? '2026/2027');
    $periode_gelombang = $conn->real_escape_string($_POST['periode_gelombang'] ?? 'Gelombang 1');
    $kuota_santri      = (int)($_POST['kuota_santri'] ?? 20);
    $cover_bg_url      = $conn->real_escape_string(trim($_POST['cover_bg_url'] ?? ''));
    $opacity           = (float)($_POST['cover_overlay_opacity'] ?? 0.85);
    $theme_preset      = $conn->real_escape_string($_POST['theme_preset'] ?? 'madinah');
    $music_url         = $conn->real_escape_string(trim($_POST['music_url'] ?? 'upload/backsound.mp3'));
    $biaya_pendaftaran = (int)str_replace(['.', ','], '', $_POST['biaya_pendaftaran'] ?? 350000);
    $biaya_pangkal     = (int)str_replace(['.', ','], '', $_POST['biaya_pangkal'] ?? 12500000);
    $biaya_tahunan     = (int)str_replace(['.', ','], '', $_POST['biaya_tahunan'] ?? 2500000);
    $biaya_spp         = (int)str_replace(['.', ','], '', $_POST['biaya_spp'] ?? 1650000);
    $diskon_gelombang  = (int)str_replace(['.', ','], '', $_POST['diskon_gelombang'] ?? 2000000);

    $sql_update = "UPDATE pengaturan_brosur SET 
                    tahun_ajaran = '$tahun_ajaran',
                    periode_gelombang = '$periode_gelombang',
                    kuota_santri = $kuota_santri,
                    cover_bg_url = '$cover_bg_url',
                    cover_overlay_opacity = $opacity,
                    theme_preset = '$theme_preset',
                    music_url = '$music_url',
                    biaya_pendaftaran = $biaya_pendaftaran,
                    biaya_pangkal = $biaya_pangkal,
                    biaya_tahunan = $biaya_tahunan,
                    biaya_spp = $biaya_spp,
                    diskon_gelombang = $diskon_gelombang
                   WHERE id = 1";

    if ($conn->query($sql_update)) {
        $pesan_sukses = "Alhamdulillah! Pengaturan Brosur PSB & Background Gambar berhasil disimpan.";
    } else {
        $pesan_error = "Gagal menyimpan: " . $conn->error;
    }
}

// Ambil Data Terkini
$q = $conn->query("SELECT * FROM pengaturan_brosur WHERE id = 1 LIMIT 1");
$cfg = $q ? $q->fetch_assoc() : [];

$active_menu = 'brosur_settings';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Brosur PSB Digital & Background | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Amiri:wght@700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .font-arabic { font-family: 'Amiri', serif; }
        .phone-mockup {
            width: 320px;
            height: 640px;
            border: 12px solid #1e293b;
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
        }
        .phone-speaker {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 5px;
            background: #334155;
            border-radius: 10px;
            z-index: 50;
        }
    </style>
</head>
<body class="flex min-h-screen bg-slate-50 text-slate-800">

    <!-- SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto overflow-y-auto">
        
        <!-- HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 border border-amber-300">
                        <i class="fas fa-sliders mr-1"></i> Brosur PSB Digital Engine
                    </span>
                    <span class="text-xs text-slate-400">Model Undangan Digital</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mt-1">Pengaturan Brosur & Background Gambar</h1>
                <p class="text-xs sm:text-sm text-slate-500">Ganti gambar background (cukup tempel URL dari Pinterest), tahun ajaran, dan rincian biaya kapan saja tanpa beban storage hosting.</p>
            </div>

            <div class="flex items-center gap-2">
                <a href="brosur.php" target="_blank" class="bg-[#0b8478] hover:bg-[#086a60] text-white px-4 py-2.5 rounded-xl font-bold text-xs flex items-center gap-2 shadow-sm transition">
                    <i class="fas fa-external-link-alt"></i> Buka Brosur Live
                </a>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (!empty($pesan_sukses)): ?>
            <div class="p-4 mb-6 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs sm:text-sm flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas fa-check-circle text-emerald-600 text-xl"></i>
                <div class="font-bold"><?= $pesan_sukses ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($pesan_error)): ?>
            <div class="p-4 mb-6 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 text-xs sm:text-sm flex items-center gap-3 shadow-sm">
                <i class="fas fa-exclamation-triangle text-rose-600 text-xl"></i>
                <div class="font-bold"><?= $pesan_error ?></div>
            </div>
        <?php endif; ?>

        <!-- GRID UTAMA: FORM PENGATURAN (KIRI) + LIVE SMARTPHONE PREVIEW (KANAN) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- FORM PENGATURAN (8 KOLOM) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                
                <form method="POST" id="form-pengaturan-brosur" class="space-y-6">
                    
                    <!-- KARTU 1: BACKGROUND GAMBAR DARI PINTEREST / UNSPLASH -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                                    <i class="fab fa-pinterest"></i>
                                </div>
                                <div>
                                    <h2 class="font-bold text-base text-slate-900">Background Cover Amplop (URL Gambar)</h2>
                                    <p class="text-xs text-slate-500">Cukup tempel alamat gambar dari Pinterest, Unsplash, atau link gambar web</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="fas fa-feather-pointed mr-1"></i> Ringan & 0 Buffer
                            </span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Direct URL Gambar Background:</label>
                            <div class="flex gap-2">
                                <input type="url" name="cover_bg_url" id="input-bg-url" value="<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>" placeholder="https://i.pinimg.com/... atau https://images.unsplash.com/..." oninput="updateLivePreview()" required class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:outline-none focus:border-[#0b8478] focus:ring-1 focus:ring-[#0b8478] bg-slate-50 font-mono">
                                <button type="button" onclick="testPreviewLive()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs flex items-center gap-1.5">
                                    <i class="fas fa-eye text-amber-600"></i> Tes
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                <i class="fas fa-info-circle text-amber-500 mr-1"></i> Cara ambil link Pinterest: Buka gambar di Pinterest &rarr; Klik Kanan &rarr; <strong>"Salin Alamat Gambar" (Copy Image Address)</strong> lalu tempel di sini.
                            </p>
                        </div>

                        <!-- PRESET CEPAT PILIHAN (ONE-CLICK PICKER) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-2">Pilihan Cepat Gambar Siap Pakai (1-Klik):</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                                
                                <button type="button" onclick="pilihPresetBg('https://images.unsplash.com/photo-1542838132-92c53300491e?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Masjid Kubah</span>
                                        <span class="text-[9px] text-slate-400">Emerald Syahdu</span>
                                    </div>
                                </button>

                                <button type="button" onclick="pilihPresetBg('https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Alam Villa</span>
                                        <span class="text-[9px] text-slate-400">Pegunungan Pinus</span>
                                    </div>
                                </button>

                                <button type="button" onclick="pilihPresetBg('https://images.unsplash.com/photo-1564769625905-50e93615e769?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1564769625905-50e93615e769?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Madinah Emas</span>
                                        <span class="text-[9px] text-slate-400">Arabesque Luxury</span>
                                    </div>
                                </button>

                                <button type="button" onclick="pilihPresetBg('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Puncak Asri</span>
                                        <span class="text-[9px] text-slate-400">Udara Sejuk Villa</span>
                                    </div>
                                </button>

                                <button type="button" onclick="pilihPresetBg('https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Nabawi Twilight</span>
                                        <span class="text-[9px] text-slate-400">Senja Madinah</span>
                                    </div>
                                </button>

                                <button type="button" onclick="pilihPresetBg('https://images.unsplash.com/photo-1519741497674-611481863552?w=1200&auto=format&fit=crop&q=80')" class="text-left p-2 rounded-xl border border-slate-200 hover:border-emerald-600 bg-slate-50 text-[11px] transition flex items-center gap-2 group">
                                    <img src="https://images.unsplash.com/photo-1519741497674-611481863552?w=120&auto=format&fit=crop&q=80" class="w-10 h-10 rounded-lg object-cover">
                                    <div>
                                        <span class="font-bold block text-slate-800 group-hover:text-emerald-700">Sage Botanical</span>
                                        <span class="text-[9px] text-slate-400">Eucalyptus Alami</span>
                                    </div>
                                </button>

                            </div>
                        </div>

                        <!-- SLIDER KEGELAPAN OVERLAY -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-xs font-bold text-slate-700">Tingkat Kegelapan Latar Belakang (Overlay Opacity):</label>
                                <span id="opacity-val" class="text-xs font-extrabold text-emerald-800"><?= (float)($cfg['cover_overlay_opacity'] ?? 0.85) * 100 ?>%</span>
                            </div>
                            <input type="range" name="cover_overlay_opacity" id="input-opacity" min="0.30" max="0.95" step="0.05" value="<?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>" oninput="updateLiveOpacity(this.value)" class="w-full accent-[#0b8478] cursor-pointer">
                            <p class="text-[10px] text-slate-400 mt-1">Geser ke kanan agar teks amplop tetap terbaca jelas dan kontras di atas gambar apapun.</p>
                        </div>
                    </div>

                    <!-- KARTU 2: INFORMASI TAHUN AJARAN & KUOTA SANTRI -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                            <div class="w-9 h-9 rounded-xl bg-teal-50 text-teal-700 flex items-center justify-center text-lg">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-base text-slate-900">Periode SPMB & Tahun Ajaran</h2>
                                <p class="text-xs text-slate-500">Ubah tahun ajaran dan nama gelombang yang aktif</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Tahun Ajaran:</label>
                                <input type="text" name="tahun_ajaran" id="input-tahun" value="<?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>" oninput="updateLiveText()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Nama Gelombang:</label>
                                <input type="text" name="periode_gelombang" id="input-gelombang" value="<?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?>" oninput="updateLiveText()" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Kuota Santri Dibuka:</label>
                                <input type="number" name="kuota_santri" value="<?= $cfg['kuota_santri'] ?? 20 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- KARTU 3: RINCIAN INVESTASI PENDIDIKAN (BIAYA) -->
                    <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200 shadow-sm space-y-4">
                        <div class="flex items-center gap-2.5 border-b border-slate-100 pb-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center text-lg">
                                <i class="fas fa-hand-holding-dollar"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-base text-slate-900">Rincian Investasi Pendidikan (Biaya)</h2>
                                <p class="text-xs text-slate-500">Angka ini otomatis tampil di tabel biaya brosur digital</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">1. Biaya Pendaftaran & Observasi (Rp):</label>
                                <input type="number" name="biaya_pendaftaran" value="<?= $cfg['biaya_pendaftaran'] ?? 350000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">2. Uang Pangkal / Sarana Masuk (Rp):</label>
                                <input type="number" name="biaya_pangkal" value="<?= $cfg['biaya_pangkal'] ?? 12500000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">3. Biaya Tahunan (Rp):</label>
                                <input type="number" name="biaya_tahunan" value="<?= $cfg['biaya_tahunan'] ?? 2500000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">4. SPP All-in Bulanan (Rp):</label>
                                <input type="number" name="biaya_spp" value="<?= $cfg['biaya_spp'] ?? 1650000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm focus:border-[#0b8478] focus:outline-none">
                            </div>
                        </div>

                        <div class="pt-2">
                            <label class="block text-xs font-bold text-amber-800 mb-1">Potongan / Diskon Khusus Gelombang (Rp):</label>
                            <input type="number" name="diskon_gelombang" value="<?= $cfg['diskon_gelombang'] ?? 2000000 ?>" required class="w-full px-4 py-2.5 rounded-xl border border-amber-300 bg-amber-50 text-xs sm:text-sm focus:border-amber-500 focus:outline-none font-bold text-amber-900">
                        </div>
                    </div>

                    <!-- TOMBOL SIMPAN -->
                    <div class="sticky bottom-4 z-20">
                        <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-[#0b8478] hover:bg-[#075f56] text-white font-black text-sm sm:text-base shadow-xl flex items-center justify-center gap-2 transform active:scale-95 transition">
                            <i class="fas fa-save text-lg"></i>
                            <span>Simpan Seluruh Pengaturan Brosur</span>
                        </button>
                    </div>

                </form>

            </div>

            <!-- SIMULASI SMARTPHONE LIVE PREVIEW (4 KOLOM) -->
            <div class="lg:col-span-5 xl:col-span-4 sticky top-6 flex flex-col items-center">
                <div class="text-center mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center justify-center gap-1.5">
                        <i class="fas fa-mobile-screen text-amber-600"></i> Live Smartphone Preview
                    </span>
                    <p class="text-[11px] text-slate-400">Tampilan langsung di layar HP saat calon wali membuka link</p>
                </div>

                <!-- PHONE MOCKUP -->
                <div class="phone-mockup bg-slate-900 text-white flex flex-col">
                    <div class="phone-speaker"></div>
                    
                    <!-- SCREEN CONTENT (SIMULASI COVER AMPLOP) -->
                    <div id="preview-screen" class="relative w-full h-full flex flex-col justify-between p-6 text-center bg-cover bg-center overflow-hidden transition-all duration-500" style="background-image: url('<?= htmlspecialchars($cfg['cover_bg_url'] ?? '') ?>');">
                        
                        <!-- OVERLAY DINAMIS -->
                        <div id="preview-overlay" class="absolute inset-0 bg-gradient-to-b from-[#022c22] via-[#043d35] to-[#021d19] transition-all duration-300" style="opacity: <?= $cfg['cover_overlay_opacity'] ?? 0.85 ?>;"></div>

                        <!-- KONTEN PREVIEW ATAS -->
                        <div class="relative z-10 pt-4">
                            <p class="font-arabic text-amber-300 text-sm">بِسْمِ اللَّهِ الرَّحْمَٰنِ الرَّحِيمِ</p>
                            <img src="upload/logo-villa-quran.png" class="w-12 h-12 mx-auto mt-2 drop-shadow">
                            <h3 class="font-extrabold text-sm text-white mt-1">Villa Quran Indonesia</h3>
                            <p id="preview-sub" class="text-[9px] text-amber-300 font-medium"><?= htmlspecialchars($cfg['periode_gelombang'] ?? 'Gelombang 1 — Kuota Terbatas') ?></p>
                        </div>

                        <!-- KONTEN PREVIEW TENGAH (KARTU TAMU) -->
                        <div class="relative z-10 bg-black/40 border border-amber-400/40 rounded-2xl p-4 my-auto backdrop-blur-md shadow-lg">
                            <span class="text-[9px] uppercase tracking-wider text-amber-300 font-bold block">Kepada Yth. Calon Wali:</span>
                            <div class="text-sm font-black text-white mt-0.5">Bpk. Hendy Pratama</div>
                            <p class="text-[9px] text-slate-300 mt-1 leading-relaxed">Undangan Silaturahmi Mahabbah & Brosur Pendidikan Generasi Qur'ani.</p>
                            <div class="mt-2 pt-2 border-t border-white/10 text-[9px] text-emerald-300 font-semibold" id="preview-tahun-txt">
                                Tahun Ajaran <?= htmlspecialchars($cfg['tahun_ajaran'] ?? '2026/2027') ?>
                            </div>
                        </div>

                        <!-- KONTEN PREVIEW BAWAH (TOMBOL BUKA) -->
                        <div class="relative z-10 pb-2">
                            <div class="w-full py-2.5 px-3 rounded-xl bg-gradient-to-r from-amber-400 to-amber-600 text-emerald-950 font-black text-xs flex items-center justify-center gap-1.5 shadow-lg">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Buka Brosur & Undangan</span>
                            </div>
                            <p class="text-[8px] text-slate-400 mt-2"><i class="fas fa-music mr-1 text-amber-400"></i> Alunan Backsound Syahdu</p>
                        </div>

                    </div>
                </div>

                <p class="text-[11px] text-slate-400 mt-3 text-center">
                    <i class="fas fa-check-circle text-emerald-500 mr-1"></i> Preview otomatis ter-update saat URL atau angka diubah.
                </p>
            </div>

        </div>

    </main>

    <!-- SCRIPT REALTIME PREVIEW -->
    <script>
        function pilihPresetBg(url) {
            document.getElementById('input-bg-url').value = url;
            updateLivePreview();
        }

        function updateLivePreview() {
            const url = document.getElementById('input-bg-url').value.trim();
            const screen = document.getElementById('preview-screen');
            if (url) {
                screen.style.backgroundImage = `url('${url}')`;
            }
        }

        function updateLiveOpacity(val) {
            const pct = Math.round(val * 100);
            document.getElementById('opacity-val').innerText = pct + '%';
            document.getElementById('preview-overlay').style.opacity = val;
        }

        function updateLiveText() {
            const gelombang = document.getElementById('input-gelombang').value;
            const tahun = document.getElementById('input-tahun').value;
            
            document.getElementById('preview-sub').innerText = gelombang;
            document.getElementById('preview-tahun-txt').innerText = 'Tahun Ajaran ' + tahun;
        }

        function testPreviewLive() {
            updateLivePreview();
            alert('Preview latar belakang berhasil diperbarui pada layar simulasi HP di samping!');
        }
    </script>
</body>
</html>
