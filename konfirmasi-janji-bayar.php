<?php
// konfirmasi-janji-bayar.php
// Halaman umpan balik publik bagi wali santri untuk mencatat komitmen janji pembayaran

require_once __DIR__ . '/koneksi.php';

// Atur zona waktu
date_default_timezone_set('Asia/Jakarta');

$bulan_indo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$bulan_sekarang = $bulan_indo[(int)date('n')];
$tahun_sekarang = date('Y');

// 1. Validasi Token Keamanan URL
$santri_id = isset($_GET['s']) ? (int)$_GET['s'] : 0;
$url_token = isset($_GET['t']) ? trim($_GET['t']) : '';
$secret = 'viqi_billing_secret';
$valid_token = md5($santri_id . $secret);

$is_valid = (!empty($url_token) && $url_token === $valid_token);

$santri = null;
if ($is_valid && $santri_id > 0) {
    // Ambil data santri
    $stmt = $conn->prepare("SELECT id, nama_lengkap, kelas_sekarang, sisa_uang_masuk FROM buku_induk_santri WHERE id = ? AND status_santri = 'Aktif'");
    $stmt->bind_param("i", $santri_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $santri = $res->fetch_assoc();
    }
    $stmt->close();
}

$pesan_sukses = '';
$pesan_error = '';

// 2. Handler POST Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_valid && $santri) {
    $tanggal_janji = $conn->real_escape_string($_POST['tanggal_janji']);
    $catatan = $conn->real_escape_string(trim($_POST['catatan']));
    
    if (empty($tanggal_janji)) {
        $pesan_error = "Harap masukkan perkiraan tanggal pembayaran!";
    } elseif (strtotime($tanggal_janji) < strtotime(date('Y-m-d'))) {
        $pesan_error = "Tanggal janji bayar tidak boleh di masa lalu!";
    } else {
        // Cek apakah sudah ada janji pembayaran bulan ini
        $stmt_check = $conn->prepare("SELECT id FROM keuangan_janji_bayar WHERE santri_id = ? AND bulan = ? AND tahun = ?");
        $stmt_check->bind_param("iss", $santri['id'], $bulan_sekarang, $tahun_sekarang);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check && $res_check->num_rows > 0) {
            // Update jika sudah ada
            $stmt_up = $conn->prepare("UPDATE keuangan_janji_bayar SET tanggal_janji = ?, catatan = ? WHERE santri_id = ? AND bulan = ? AND tahun = ?");
            $stmt_up->bind_param("ssiss", $tanggal_janji, $catatan, $santri['id'], $bulan_sekarang, $tahun_sekarang);
            if ($stmt_up->execute()) {
                $pesan_sukses = "Jazaakumullahu Khairan. Perubahan komitmen pembayaran Anda berhasil disimpan.";
            } else {
                $pesan_error = "Gagal memperbarui janji pembayaran: " . $conn->error;
            }
            $stmt_up->close();
        } else {
            // Insert baru
            $stmt_in = $conn->prepare("INSERT INTO keuangan_janji_bayar (santri_id, bulan, tahun, tanggal_janji, catatan) VALUES (?, ?, ?, ?, ?)");
            $stmt_in->bind_param("issss", $santri['id'], $bulan_sekarang, $tahun_sekarang, $tanggal_janji, $catatan);
            if ($stmt_in->execute()) {
                $pesan_sukses = "Jazaakumullahu Khairan. Komitmen janji pembayaran Anda telah berhasil kami catat.";
            } else {
                $pesan_error = "Gagal menyimpan janji pembayaran: " . $conn->error;
            }
            $stmt_in->close();
        }
        $stmt_check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Komitmen Pembayaran | Yayasan Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        .glass {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="bg-gradient-to-tr from-amber-50 via-slate-50 to-emerald-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md glass rounded-3xl shadow-2xl border border-white/40 overflow-hidden transition-all duration-300">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-amber-700 to-amber-800 text-white p-6 text-center relative">
            <div class="absolute -right-8 -top-8 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
            <i class="fas fa-hand-holding-heart text-4xl mb-2 text-amber-200"></i>
            <h1 class="text-xl font-bold tracking-wide">Yayasan Villa Quran</h1>
            <p class="text-xs text-amber-100/90 mt-1">Formulir Komitmen & Janji Pembayaran Wali Santri</p>
        </div>

        <div class="p-6">
            <?php if (!$is_valid || !$santri): ?>
                <!-- TAMPILAN ERROR TOKEN / SANTRI TIDAK VALID -->
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-triangle text-rose-500 text-5xl mb-4 animate-bounce"></i>
                    <h3 class="font-bold text-gray-800 text-lg mb-2">Tautan Tidak Valid</h3>
                    <p class="text-sm text-gray-500 leading-relaxed mb-6">
                        Maaf, tautan konfirmasi ini sudah kedaluwarsa atau tidak valid. Silakan gunakan tautan terbaru dari pesan WhatsApp resmi Yayasan, atau hubungi Bendahara kami.
                    </p>
                    <a href="https://wa.me/628123456789" class="inline-flex items-center justify-center bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm px-6 py-2.5 rounded-xl shadow transition">
                        <i class="fab fa-whatsapp mr-2 text-base"></i> Hubungi Bendahara
                    </a>
                </div>
            <?php elseif (!empty($pesan_sukses)): ?>
                <!-- TAMPILAN SUKSES SUBMIT -->
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4 border border-emerald-200">
                        <i class="fas fa-check text-emerald-600 text-2xl"></i>
                    </div>
                    <h3 class="font-bold text-emerald-800 text-lg mb-2">Penyimpanan Berhasil</h3>
                    <p class="text-sm text-gray-600 leading-relaxed mb-6">
                        <?= $pesan_sukses ?>
                    </p>
                    <div class="bg-emerald-50/50 border border-emerald-100 rounded-xl p-4 text-xs text-emerald-800 text-left space-y-1 mb-6">
                        <div><strong>Santri:</strong> <?= htmlspecialchars($santri['nama_lengkap']) ?></div>
                        <div><strong>Periode:</strong> <?= $bulan_sekarang ?> <?= $tahun_sekarang ?></div>
                        <div><strong>Tanggal Janji:</strong> <?= date('d/m/Y', strtotime($tanggal_janji)) ?></div>
                    </div>
                    <p class="text-xs text-gray-400 italic">Terima kasih atas bantuan Anda untuk mendukung kelancaran operasional pendidikan Qur'an para santri.</p>
                </div>
            <?php else: ?>
                <!-- TAMPILAN FORM UTAMA -->
                <?php if(!empty($pesan_error)): ?>
                    <div class="bg-rose-50 text-rose-700 text-xs px-4 py-3 rounded-xl mb-4 border border-rose-100 flex items-center">
                        <i class="fas fa-exclamation-circle mr-2 text-sm"></i> <?= $pesan_error ?>
                    </div>
                <?php endif; ?>

                <div class="bg-amber-50/50 border border-amber-100 rounded-2xl p-4 mb-6">
                    <h4 class="font-bold text-gray-800 text-sm mb-2"><i class="fas fa-user-graduate text-amber-700 mr-1.5"></i>Data Santri</h4>
                    <table class="w-full text-xs text-gray-600 space-y-1.5">
                        <tr>
                            <td class="py-1 font-medium w-24">Nama Santri</td>
                            <td class="py-1">: <span class="font-bold text-gray-800"><?= htmlspecialchars($santri['nama_lengkap']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="py-1 font-medium">Kelas</td>
                            <td>: <span class="font-semibold text-gray-800"><?= htmlspecialchars($santri['kelas_sekarang']) ?></span></td>
                        </tr>
                        <tr>
                            <td class="py-1 font-medium">Tagihan Aktif</td>
                            <td>: <span class="font-bold text-rose-700">SPP <?= $bulan_sekarang ?> <?= $tahun_sekarang ?></span></td>
                        </tr>
                        <?php if ($santri['sisa_uang_masuk'] > 0): ?>
                        <tr>
                            <td class="py-1 font-medium">Sisa Uang Masuk</td>
                            <td>: <span class="font-bold text-rose-700">Rp <?= number_format($santri['sisa_uang_masuk'], 0, ',', '.') ?></span></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <form action="" method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="far fa-calendar-alt text-amber-700 mr-1"></i> Perkiraan Tanggal Pembayaran</label>
                        <input type="date" name="tanggal_janji" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition">
                        <p class="text-[10px] text-gray-400 mt-1">Pilih tanggal di mana Bapak/Ibu berencana/memperkirakan dapat melunasi tagihan tersebut.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5"><i class="far fa-comment-dots text-amber-700 mr-1"></i> Catatan / Keterangan Tambahan</label>
                        <textarea name="catatan" rows="3" class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:outline-none transition" placeholder="Tulis kendala atau rencana penyaluran di sini jika ada..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-gradient-to-r from-amber-700 to-amber-800 hover:from-amber-800 hover:to-amber-900 text-white font-bold py-3.5 rounded-xl shadow-lg transition-all flex items-center justify-center">
                        <i class="far fa-paper-plane mr-2"></i> Kirim Komitmen
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Footer watermark -->
        <div class="bg-gray-50 border-t border-gray-100 py-3 text-center text-[10px] text-gray-400">
            &copy; <?= date('Y') ?> SIM Keuangan - Yayasan Villa Quran Indonesia
        </div>
    </div>

</body>
</html>
