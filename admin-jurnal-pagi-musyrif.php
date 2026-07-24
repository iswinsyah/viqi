<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Validasi role musyrif
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (!in_array('musyrif', $user_roles) && !in_array('super_admin', $user_roles) && !in_array('kepala_asrama', $user_roles)) {
    die("Akses ditolak. Halaman ini khusus untuk Musyrif/Kepala Asrama.");
}

$ustadz_id = $_SESSION['ustadz_id'];
$hari_ini = date('Y-m-d');
$waktu_sekarang = date('H:i:s');
$pesan_sukses = "";
$pesan_error = "";

// Buat Tabel Jurnal Piket Pagi jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_piket_pagi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu_sterilisasi TIME,
    foto_kamar VARCHAR(255),
    status_keamanan VARCHAR(50),
    keterangan_keamanan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (ustadz_id, tanggal)
)");

// Cek apakah jurnal hari ini sudah ada, jika belum buat
$res_jurnal = $conn->query("SELECT * FROM jurnal_piket_pagi WHERE ustadz_id = $ustadz_id AND tanggal = '$hari_ini'");
if ($res_jurnal->num_rows == 0) {
    $conn->query("INSERT IGNORE INTO jurnal_piket_pagi (ustadz_id, tanggal) VALUES ($ustadz_id, '$hari_ini')");
    $jurnal = ['id' => $conn->insert_id, 'waktu_sterilisasi' => null, 'foto_kamar' => null, 'status_keamanan' => null, 'keterangan_keamanan' => null];
} else {
    $jurnal = $res_jurnal->fetch_assoc();
}

// Blok akses POST (Submit form) jika sudah lewat jam 13:00 (Kecuali Super Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($waktu_sekarang > '13:00:00' && !in_array('super_admin', $user_roles)) {
        $pesan_error = "Batas waktu pengisian Jurnal Piket Pagi (13.00 WIB) telah habis. Anda tidak dapat mengisi laporan lagi untuk hari ini.";
    } else {
        $action = $_POST['action'] ?? '';
        
        // 1. Submit Sterilisasi & Keamanan
        if ($action === 'submit_sterilisasi') {
            $status_keamanan = $conn->real_escape_string($_POST['status_keamanan']);
            $keterangan_keamanan = $conn->real_escape_string($_POST['keterangan_keamanan']);
            
            // Proses Upload Foto (hanya menerima file gambar)
            $foto_kamar = $jurnal['foto_kamar'];
            if (isset($_FILES['foto_kamar']) && $_FILES['foto_kamar']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto_kamar']['tmp_name'];
                $file_name = time() . '_' . rand(100, 999) . '_' . $_FILES['foto_kamar']['name'];
                $upload_dir = 'uploads/jurnal/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                    $foto_kamar = $upload_dir . $file_name;
                }
            }
            
            $sql = "UPDATE jurnal_piket_pagi SET waktu_sterilisasi = '$waktu_sekarang', foto_kamar = '$foto_kamar', status_keamanan = '$status_keamanan', keterangan_keamanan = '$keterangan_keamanan' WHERE ustadz_id = $ustadz_id AND tanggal = '$hari_ini'";
            if ($conn->query($sql)) {
                $pesan_sukses = "Laporan Sterilisasi dan Keamanan berhasil disimpan!";
                $jurnal['waktu_sterilisasi'] = $waktu_sekarang;
                $jurnal['foto_kamar'] = $foto_kamar;
                $jurnal['status_keamanan'] = $status_keamanan;
                $jurnal['keterangan_keamanan'] = $keterangan_keamanan;
            } else {
                $pesan_error = "Gagal menyimpan laporan keamanan.";
            }
        }
        
        // 2. Submit Laporan 1 Hari 1 Santri
        if ($action === 'submit_kabar_santri') {
            $santri_id = (int)$_POST['santri_id'];
            $quote_text = $conn->real_escape_string($_POST['quote_text']);
            
            // Proses Upload Foto
            $foto_mentah = null;
            if (isset($_FILES['foto_santri']) && $_FILES['foto_santri']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto_santri']['tmp_name'];
                $file_name = time() . '_santri_' . rand(100, 999) . '.jpg';
                $upload_dir = 'uploads/sosmed/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($file_tmp, $upload_dir . $file_name)) {
                    $foto_mentah = $upload_dir . $file_name;
                }
            }
            
            if ($foto_mentah) {
                $sql = "INSERT INTO sosmed_campaign (tanggal, santri_id, pilar_konten, tema_foto, foto_mentah, quote_text, status_proses) VALUES ('$hari_ini', $santri_id, 'Kabar Santri', 'Kabar Harian', '$foto_mentah', '$quote_text', 'menunggu_foto')";
                if ($conn->query($sql)) {
                    $pesan_sukses = "Kabar 1 Hari 1 Santri berhasil dikirim!";
                } else {
                    $pesan_error = "Gagal menyimpan kabar santri.";
                }
            } else {
                $pesan_error = "Harap ambil foto/upload foto santri.";
            }
        }
        
        // 3. Submit Setoran Hafalan
        if ($action === 'submit_setoran') {
            $nama_santri = $conn->real_escape_string($_POST['nama_santri']);
            $jenis_setoran = $conn->real_escape_string($_POST['jenis_setoran']);
            $surat_id = (int)$_POST['surat_id'];
            $ayat_dari = (int)$_POST['ayat_dari'];
            $ayat_sampai = (int)$_POST['ayat_sampai'];
            $penilaian = $conn->real_escape_string($_POST['penilaian']);
            
            $sql = "INSERT INTO setoran_hafalan (ustadz_id, nama_santri, tanggal, jenis_setoran, surat_id, ayat_dari, ayat_sampai, penilaian) VALUES ($ustadz_id, '$nama_santri', '$hari_ini', '$jenis_setoran', $surat_id, $ayat_dari, $ayat_sampai, '$penilaian')";
            if ($conn->query($sql)) {
                $pesan_sukses = "Setoran hafalan berhasil dicatat! (Waktu: $waktu_sekarang)";
            } else {
                $pesan_error = "Gagal menyimpan setoran hafalan.";
            }
        }
    }
}

$surah_list = [
    1 => "Al-Fatihah", 2 => "Al-Baqarah", 3 => "Ali 'Imran", 4 => "An-Nisa'", 5 => "Al-Ma'idah", 6 => "Al-An'am", 7 => "Al-A'raf", 8 => "Al-Anfal", 9 => "At-Taubah", 10 => "Yunus", 11 => "Hud", 12 => "Yusuf", 13 => "Ar-Ra'd", 14 => "Ibrahim", 15 => "Al-Hijr",
    16 => "An-Nahl", 17 => "Al-Isra'", 18 => "Al-Kahf", 19 => "Maryam", 20 => "Taha", 21 => "Al-Anbiya'", 22 => "Al-Hajj", 23 => "Al-Mu'minun", 24 => "An-Nur", 25 => "Al-Furqan", 26 => "Asy-Syu'ara'", 27 => "An-Naml", 28 => "Al-Qasas", 29 => "Al-'Ankabut", 30 => "Ar-Rum",
    31 => "Luqman", 32 => "As-Sajdah", 33 => "Al-Ahzab", 34 => "Saba'", 35 => "Fatir", 36 => "Ya-Sin", 37 => "As-Saffat", 38 => "Sad", 39 => "Az-Zumar", 40 => "Ghafir", 41 => "Fussilat", 42 => "Asy-Syura", 43 => "Az-Zukhruf", 44 => "Ad-Dukhan", 45 => "Al-Jathiyah",
    46 => "Al-Ahqaf", 47 => "Muhammad", 48 => "Al-Fath", 49 => "Al-Hujurat", 50 => "Qaf", 51 => "Az-Zariyat", 52 => "At-Tur", 53 => "An-Najm", 54 => "Al-Qamar", 55 => "Ar-Rahman", 56 => "Al-Waqi'ah", 57 => "Al-Hadid", 58 => "Al-Mujadilah", 59 => "Al-Hasyr", 60 => "Al-Mumtahanah",
    61 => "As-Saff", 62 => "Al-Jumu'ah", 63 => "Al-Munafiqun", 64 => "At-Taghabun", 65 => "At-Talaq", 66 => "At-Tahrim", 67 => "Al-Mulk", 68 => "Al-Qalam", 69 => "Al-Haqqah", 70 => "Al-Ma'arij", 71 => "Nuh", 72 => "Al-Jinn", 73 => "Al-Muzzammil", 74 => "Al-Muddaththir", 75 => "Al-Qiyamah",
    76 => "Al-Insan", 77 => "Al-Mursalat", 78 => "An-Naba'", 79 => "An-Nazi'at", 80 => "'Abasa", 81 => "At-Takwir", 82 => "Al-Infitar", 83 => "Al-Mutaffifin", 84 => "Al-Insyiqaq", 85 => "Al-Buruj", 86 => "At-Tariq", 87 => "Al-A'la", 88 => "Al-Ghasyiyah", 89 => "Al-Fajr", 90 => "Al-Balad",
    91 => "Asy-Syams", 92 => "Al-Layl", 93 => "Ad-Duha", 94 => "Al-Insyirah", 95 => "At-Tin", 96 => "Al-'Alaq", 97 => "Al-Qadr", 98 => "Al-Bayyinah", 99 => "Az-Zalzalah", 100 => "Al-'Adiyat", 101 => "Al-Qari'ah", 102 => "At-Takasur", 103 => "Al-'Asr", 104 => "Al-Humazah", 105 => "Al-Fil",
    106 => "Quraisy", 107 => "Al-Ma'un", 108 => "Al-Kausar", 109 => "Al-Kafirun", 110 => "An-Nasr", 111 => "Al-Masad", 112 => "Al-Ikhlas", 113 => "Al-Falaq", 114 => "An-Nas"
];

$res_santri = $conn->query("SELECT id, nis, nama_lengkap FROM buku_induk_santri ORDER BY nama_lengkap ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Piket Pagi Musyrif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background-color: #f8fafc; }
        .glass-panel { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="text-slate-800">
    <div class="flex h-screen overflow-hidden">
        <!-- INCLUDE SIDEBAR -->
        <?php include 'sidebar-hr.php'; ?>

        <div class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
            <main class="w-full grow p-6">
                <!-- Header -->
                <div class="mb-6 flex flex-col gap-2">
                    <h1 class="text-3xl font-extrabold text-indigo-900 tracking-tight">Jurnal Piket Pagi <span class="text-indigo-500">(07.00 - 13.00)</span></h1>
                    <p class="text-sm text-slate-500">Sentral pelaporan harian Musyrif Asrama. Semua pengisian akan tercatat secara real-time.</p>
                </div>

                <?php if ($pesan_error): ?>
                    <div class="bg-rose-50 border-l-4 border-rose-500 p-4 mb-6 rounded-r-lg shadow-sm">
                        <div class="flex"><div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-rose-500"></i></div>
                        <div class="ml-3"><p class="text-sm text-rose-700 font-medium"><?= htmlspecialchars($pesan_error) ?></p></div></div>
                    </div>
                <?php endif; ?>
                <?php if ($pesan_sukses): ?>
                    <div class="bg-emerald-50 border-l-4 border-emerald-500 p-4 mb-6 rounded-r-lg shadow-sm">
                        <div class="flex"><div class="flex-shrink-0"><i class="fas fa-check-circle text-emerald-500"></i></div>
                        <div class="ml-3"><p class="text-sm text-emerald-700 font-medium"><?= htmlspecialchars($pesan_sukses) ?></p></div></div>
                    </div>
                <?php endif; ?>

                <?php if ($waktu_sekarang > '13:00:00' && !in_array('super_admin', $user_roles)): ?>
                    <div class="bg-rose-100 border border-rose-200 text-rose-700 px-6 py-8 rounded-2xl text-center shadow-sm">
                        <i class="fas fa-clock text-4xl mb-3 text-rose-400"></i>
                        <h2 class="text-xl font-bold mb-2">Batas Waktu Pengisian Habis</h2>
                        <p class="text-sm">Saat ini pukul <?= date('H:i') ?> WIB. Form jurnal piket pagi otomatis terkunci setelah pukul 13.00 WIB setiap harinya.</p>
                    </div>
                <?php else: ?>

                <!-- Grid 3 Blok -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- BLOK 1: Sterilisasi & Keamanan -->
                    <div class="glass-panel p-6 rounded-2xl shadow-sm border border-slate-200 h-full flex flex-col">
                        <div class="flex items-center gap-3 mb-4 border-b pb-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-shield-halved"></i></div>
                            <div>
                                <h2 class="font-bold text-slate-800">1. Keamanan & Sterilisasi</h2>
                                <p class="text-[11px] text-slate-500">Laporan kunci kamar & keamanan area</p>
                            </div>
                        </div>

                        <?php if ($jurnal['waktu_sterilisasi']): ?>
                            <div class="flex-1 flex flex-col justify-center items-center text-center p-4 bg-emerald-50 rounded-xl border border-emerald-100">
                                <i class="fas fa-check-circle text-3xl text-emerald-500 mb-2"></i>
                                <h3 class="font-bold text-emerald-700 text-sm">Sudah Dilaporkan</h3>
                                <p class="text-xs text-emerald-600 mt-1">Pada: <?= date('H:i', strtotime($jurnal['waktu_sterilisasi'])) ?> WIB</p>
                                <?php if ($jurnal['foto_kamar']): ?>
                                    <img src="<?= htmlspecialchars($jurnal['foto_kamar']) ?>" class="mt-3 w-32 h-24 object-cover rounded-lg border shadow-sm">
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col">
                                <input type="hidden" name="action" value="submit_sterilisasi">
                                
                                <div class="mb-3">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Bukti Foto Kamar (Live Kamera) <span class="text-rose-500">*</span></label>
                                    <input type="file" name="foto_kamar" accept="image/*" capture="environment" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Status Keamanan Area <span class="text-rose-500">*</span></label>
                                    <select name="status_keamanan" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="Aman Terkendali">Aman Terkendali (Tidak ada santri berkeliaran)</option>
                                        <option value="Ada Pelanggaran">Ada Pelanggaran (Perlu ditindak)</option>
                                    </select>
                                </div>
                                <div class="mb-4 flex-1">
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Keterangan / Catatan Singkat</label>
                                    <textarea name="keterangan_keamanan" rows="2" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Kondisi asrama, dll..."></textarea>
                                </div>
                                <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-sm transition shadow-sm"><i class="fas fa-paper-plane mr-2"></i>Kirim Laporan Keamanan</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <!-- BLOK 2: Laporan 1 Hari 1 Santri -->
                    <div class="glass-panel p-6 rounded-2xl shadow-sm border border-slate-200 h-full flex flex-col">
                        <div class="flex items-center gap-3 mb-4 border-b pb-3">
                            <div class="w-10 h-10 rounded-full bg-fuchsia-100 text-fuchsia-600 flex items-center justify-center text-lg"><i class="fas fa-camera-retro"></i></div>
                            <div>
                                <h2 class="font-bold text-slate-800">2. Kabar 1 Santri</h2>
                                <p class="text-[11px] text-slate-500">Foto candid/natural untuk Sosmed/Wali</p>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col">
                            <input type="hidden" name="action" value="submit_kabar_santri">
                            
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Santri <span class="text-rose-500">*</span></label>
                                <select name="santri_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-fuchsia-500">
                                    <option value="">-- Pilih Santri --</option>
                                    <?php 
                                    if ($res_santri) {
                                        $res_santri->data_seek(0);
                                        while ($s = $res_santri->fetch_assoc()) {
                                            echo "<option value='{$s['id']}'>{$s['nis']} - {$s['nama_lengkap']}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Foto Santri (Natural Angle) <span class="text-rose-500">*</span></label>
                                <input type="file" name="foto_santri" accept="image/*" capture="environment" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-fuchsia-50 file:text-fuchsia-700 hover:file:bg-fuchsia-100">
                            </div>
                            <div class="mb-4 flex-1">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Caption / Kabar Baik <span class="text-rose-500">*</span></label>
                                <textarea name="quote_text" rows="3" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-fuchsia-500" placeholder="Ceritakan aktivitas baik yang sedang ia lakukan..."></textarea>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-bold rounded-xl text-sm transition shadow-sm"><i class="fas fa-upload mr-2"></i>Kirim Foto Kabar Santri</button>
                        </form>
                    </div>

                    <!-- BLOK 3: Input Setoran Hafalan -->
                    <div class="glass-panel p-6 rounded-2xl shadow-sm border border-slate-200 h-full flex flex-col">
                        <div class="flex items-center gap-3 mb-4 border-b pb-3">
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg"><i class="fas fa-book-quran"></i></div>
                            <div>
                                <h2 class="font-bold text-slate-800">3. Setoran Hafalan Mandiri</h2>
                                <p class="text-[11px] text-slate-500">Mencatat setoran santri free class</p>
                            </div>
                        </div>

                        <form method="POST" class="flex-1 flex flex-col">
                            <input type="hidden" name="action" value="submit_setoran">
                            
                            <div class="mb-3">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Santri <span class="text-rose-500">*</span></label>
                                <input type="text" name="nama_santri" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Ketik nama santri...">
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Setoran</label>
                                    <select name="jenis_setoran" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <option value="Ziyadah">Ziyadah</option>
                                        <option value="Murajaah">Muroja'ah</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Surat</label>
                                    <select name="surat_id" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                        <?php foreach ($surah_list as $id_surat => $nama_surat): ?>
                                            <option value="<?= $id_surat ?>"><?= $nama_surat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Dari Ayat</label>
                                    <input type="number" name="ayat_dari" min="1" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-600 mb-1">Sampai Ayat</label>
                                    <input type="number" name="ayat_sampai" min="1" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                </div>
                            </div>
                            <div class="mb-4 flex-1">
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Kualitas Penilaian <span class="text-rose-500">*</span></label>
                                <select name="penilaian" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                    <option value="Lancar">Lancar</option>
                                    <option value="Kurang Lancar">Kurang Lancar</option>
                                    <option value="Perlu Diulang">Perlu Diulang</option>
                                </select>
                            </div>
                            <button type="submit" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-sm transition shadow-sm"><i class="fas fa-save mr-2"></i>Simpan Setoran</button>
                        </form>
                    </div>

                </div> <!-- End Grid -->
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
