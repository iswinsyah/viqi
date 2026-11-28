<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';
require_once __DIR__ . '/includes/services/WaNotificationService.php';

$orangtua_id = $_SESSION['orangtua_id'];
$is_super_admin = ($orangtua_id == 9999 || (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi'])));
$active_menu = 'perizinan_santri';

// 1. Buat Tabel Perizinan Santri jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS perizinan_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    orangtua_id INT NULL,
    nama_pemohon VARCHAR(150) NOT NULL,
    no_whatsapp_pemohon VARCHAR(25) NOT NULL,
    hubungan_pemohon ENUM('Ayah', 'Ibu', 'Wali', 'Keluarga') DEFAULT 'Ayah',
    jenis_izin ENUM('Menjenguk / Sambangan', 'Mengajak Keluar Sementara', 'Menjemput Pulang ke Rumah', 'Izin Sakit / Berobat') NOT NULL,
    tanggal_mulai DATE NOT NULL,
    jam_mulai TIME NOT NULL,
    tanggal_selesai DATE NOT NULL,
    jam_selesai TIME NOT NULL,
    penjemput_nama VARCHAR(150) NOT NULL,
    penjemput_kontak VARCHAR(25) NULL,
    alasan_keperluan TEXT NOT NULL,
    status ENUM('Menunggu Persetujuan', 'Disetujui', 'Ditolak', 'Sedang Berjalan', 'Selesai / Kembali') DEFAULT 'Menunggu Persetujuan',
    catatan_musyrif TEXT NULL,
    disetujui_oleh VARCHAR(150) NULL,
    disetujui_pada DATETIME NULL,
    waktu_keluar_aktual DATETIME NULL,
    waktu_kembali_aktual DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_santri (santri_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 2. Ambil daftar santri yang terhubung & santri aktif persisten
$santri_list = getOrangtuaSantriList($conn, $orangtua_id);
$active_santri = getOrangtuaActiveSantri($conn, $orangtua_id, $santri_list);
$selected_santri_id = $active_santri ? (int)$active_santri['id'] : 0;

$pesan_sukses = "";
$pesan_error = "";

// 3. Ambil data orang tua login untuk prefill form
$ortu_profil = null;
if ((int)$orangtua_id > 0 && (int)$orangtua_id !== 9999) {
    $res_p = $conn->query("SELECT * FROM akun_orangtua WHERE id = " . (int)$orangtua_id . " LIMIT 1");
    if ($res_p) $ortu_profil = $res_p->fetch_assoc();
}

// 4. Proses Pengajuan Perizinan Baru
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'ajukan_izin') {
    $santri_id_post = (int)($_POST['santri_id'] ?? $selected_santri_id);
    $nama_pemohon = trim($conn->real_escape_string($_POST['nama_pemohon'] ?? ''));
    $no_whatsapp_pemohon = trim($conn->real_escape_string($_POST['no_whatsapp_pemohon'] ?? ''));
    $hubungan_pemohon = $conn->real_escape_string($_POST['hubungan_pemohon'] ?? 'Ayah');
    $jenis_izin = $conn->real_escape_string($_POST['jenis_izin'] ?? 'Menjenguk / Sambangan');
    $tanggal_mulai = $conn->real_escape_string($_POST['tanggal_mulai'] ?? date('Y-m-d'));
    $jam_mulai = $conn->real_escape_string($_POST['jam_mulai'] ?? '08:00');
    $tanggal_selesai = $conn->real_escape_string($_POST['tanggal_selesai'] ?? date('Y-m-d'));
    $jam_selesai = $conn->real_escape_string($_POST['jam_selesai'] ?? '17:00');
    $penjemput_nama = trim($conn->real_escape_string($_POST['penjemput_nama'] ?? $nama_pemohon));
    $penjemput_kontak = trim($conn->real_escape_string($_POST['penjemput_kontak'] ?? $no_whatsapp_pemohon));
    $alasan_keperluan = trim($conn->real_escape_string($_POST['alasan_keperluan'] ?? ''));

    if ($santri_id_post <= 0) {
        $pesan_error = "Harap pilih ananda terlebih dahulu!";
    } elseif (empty($nama_pemohon) || empty($no_whatsapp_pemohon) || empty($alasan_keperluan)) {
        $pesan_error = "Semua bidang wajib (Nama, No WA, Alasan) harus diisi!";
    } else {
        $sql_insert = "INSERT INTO perizinan_santri (
            santri_id, orangtua_id, nama_pemohon, no_whatsapp_pemohon, hubungan_pemohon,
            jenis_izin, tanggal_mulai, jam_mulai, tanggal_selesai, jam_selesai,
            penjemput_nama, penjemput_kontak, alasan_keperluan, status
        ) VALUES (
            $santri_id_post, " . (int)$orangtua_id . ", '$nama_pemohon', '$no_whatsapp_pemohon', '$hubungan_pemohon',
            '$jenis_izin', '$tanggal_mulai', '$jam_mulai', '$tanggal_selesai', '$jam_selesai',
            '$penjemput_nama', '$penjemput_kontak', '$alasan_keperluan', 'Menunggu Persetujuan'
        )";

        if ($conn->query($sql_insert)) {
            $pesan_sukses = "Pengajuan izin berhasil dikirimkan! Pengasuhan/Musyrif akan segera meninjau perizinan ananda.";

            // Kirim Notifikasi WhatsApp ke Pengasuhan / Admin
            $nama_santri_text = $active_santri ? $active_santri['nama_lengkap'] : "Santri (ID: $santri_id_post)";
            $kelas_santri_text = $active_santri ? ($active_santri['kelas_sekarang'] ?? 'Santri') : '';
            $tgl_fmt_mulai = date('d/m/Y', strtotime($tanggal_mulai));
            $tgl_fmt_selesai = date('d/m/Y', strtotime($tanggal_selesai));

            $wa_msg = "🕌 *PENGAJUAN PERIZINAN SANTRI BARU*\n"
                    . "Villa Quran Baron Malang\n\n"
                    . "👤 *Ananda*: {$nama_santri_text} ({$kelas_santri_text})\n"
                    . "📋 *Jenis Izin*: {$jenis_izin}\n"
                    . "👨‍👩‍👧 *Pemohon*: {$nama_pemohon} ({$hubungan_pemohon})\n"
                    . "📞 *No. WA*: {$no_whatsapp_pemohon}\n"
                    . "📅 *Waktu*: {$tgl_fmt_mulai} {$jam_mulai} s/d {$tgl_fmt_selesai} {$jam_selesai}\n"
                    . "🚗 *Penjemput*: {$penjemput_nama}\n"
                    . "📝 *Keperluan*: {$alasan_keperluan}\n\n"
                    . "Mohon Ustadz/Musyrif pengasuhan meninjau dan memberikan persetujuan melalui dashboard admin perizinan:\n"
                    . "https://villaquranbaronmalang.com/admin-perizinan-santri.php";

            // Kirim via WaNotificationService
            WaNotificationService::sendMessage(WaNotificationService::DEFAULT_ADMIN_WA, $wa_msg, $conn);
        } else {
            $pesan_error = "Gagal mengirim pengajuan izin: " . $conn->error;
        }
    }
}

// 5. Query Riwayat Perizinan Ananda Ini
$riwayat_izin = [];
if ($selected_santri_id > 0) {
    $res_riwayat = $conn->query("
        SELECT * FROM perizinan_santri 
        WHERE santri_id = $selected_santri_id 
        ORDER BY id DESC
    ");
    if ($res_riwayat) {
        while ($row = $res_riwayat->fetch_assoc()) {
            $riwayat_izin[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Perizinan Ananda | Ruang Orang Tua</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-orangtua.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- TOP HEADER -->
        <header class="bg-[#0b8478] text-white shadow-md z-20 flex-shrink-0 border-b border-teal-700/60">
            <div class="h-16 flex items-center justify-between px-4 sm:px-6">
                <div class="flex items-center space-x-3">
                    <button id="open-sidebar-orangtua" class="md:hidden text-white hover:text-teal-200">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="flex items-center gap-2">
                        <span class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center text-sm shadow-xs">
                            <i class="fas fa-door-open"></i>
                        </span>
                        <div>
                            <h2 class="font-extrabold text-white text-sm sm:text-base tracking-tight leading-tight">Perizinan Ananda</h2>
                            <p class="text-[10px] text-teal-100 font-medium">Izin Menjenguk, Menjemput, & Keluar Asrama</p>
                        </div>
                    </div>
                </div>
                <div>
                    <a href="dashboard-orangtua.php" class="text-xs text-white hover:text-teal-100 font-bold bg-white/15 hover:bg-white/25 border border-white/20 px-3 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-xs">
                        <i class="fas fa-house"></i>
                        <span class="hidden sm:inline">Beranda</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-5xl mx-auto space-y-6">

                <!-- NOTIFIKASI SUKSES / ERROR -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
                        <div class="flex items-center gap-2.5">
                            <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                            <span><?= htmlspecialchars($pesan_sukses) ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
                        <div class="flex items-center gap-2.5">
                            <i class="fas fa-exclamation-triangle text-rose-600 text-base"></i>
                            <span><?= htmlspecialchars($pesan_error) ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <!-- SELECTOR ANANDA JIKA BERSAUDARA KANDUNG -->
                <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-sm border border-slate-200/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-200 flex items-center justify-center text-[#0b8478] shadow-xs flex-shrink-0">
                            <i class="fas fa-user-graduate text-lg"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Ananda yang Diajukan Izin</p>
                            <h3 class="text-base font-black text-slate-900 leading-tight">
                                <?= $active_santri ? htmlspecialchars($active_santri['nama_lengkap']) : 'Belum Ada Santri Terhubung' ?>
                            </h3>
                            <?php if ($active_santri): ?>
                                <p class="text-[11px] text-[#0b8478] font-bold mt-0.5">
                                    <?= htmlspecialchars($active_santri['kelas_sekarang'] ?? 'Santri') ?> • Kamar <?= htmlspecialchars($active_santri['kamar_asrama'] ?? '-') ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (count($santri_list) > 1): ?>
                    <div class="flex items-center gap-2">
                        <label for="santriSwitcher" class="text-xs font-bold text-slate-700 whitespace-nowrap">Ganti Ananda:</label>
                        <select id="santriSwitcher" onchange="window.location.href='orangtua-perizinan.php?santri_id=' + this.value;" class="bg-teal-50/60 border border-teal-300 text-teal-950 font-bold text-xs rounded-xl px-3 py-2 focus:ring-2 focus:ring-[#0b8478] focus:outline-none shadow-xs cursor-pointer">
                            <?php foreach ($santri_list as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= ($s['id'] == $selected_santri_id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nama_lengkap']) ?> (<?= htmlspecialchars($s['kelas_sekarang'] ?? 'Santri') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- DUA KOLOM: FORM PENGAJUAN (KIRI) & RIWAYAT PERIZINAN (KANAN) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- KOLOM FORM PENGAJUAN (5/12) -->
                    <div class="lg:col-span-5 space-y-4">
                        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80">
                            <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
                                <span class="w-8 h-8 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-sm">
                                    <i class="fas fa-file-pen"></i>
                                </span>
                                <div>
                                    <h3 class="font-extrabold text-sm text-slate-800">Form Pengajuan Izin</h3>
                                    <p class="text-[11px] text-slate-500">Isi formulir untuk mengajukan izin ananda</p>
                                </div>
                            </div>

                            <form action="" method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="ajukan_izin">
                                <input type="hidden" name="santri_id" value="<?= $selected_santri_id ?>">

                                <!-- JENIS IZIN -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Jenis Perizinan <span class="text-rose-500">*</span></label>
                                    <select name="jenis_izin" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                                        <option value="Menjenguk / Sambangan">🏠 Menjenguk / Sambangan (Bertemu di Pesantren)</option>
                                        <option value="Mengajak Keluar Sementara">🚗 Mengajak Keluar Sementara (Kembali Hari Ini)</option>
                                        <option value="Menjemput Pulang ke Rumah">🏡 Menjemput Pulang ke Rumah (Bermalam)</option>
                                        <option value="Izin Sakit / Berobat">🏥 Izin Sakit / Berobat ke Dokter atau RS</option>
                                    </select>
                                </div>

                                <!-- DATA PEMOHON -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Pemohon <span class="text-rose-500">*</span></label>
                                        <input type="text" name="nama_pemohon" required value="<?= htmlspecialchars($_SESSION['orangtua_nama'] ?? ($ortu_profil['nama_orangtua'] ?? '')) ?>" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition" placeholder="Nama Ayah/Ibu">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Hubungan <span class="text-rose-500">*</span></label>
                                        <select name="hubungan_pemohon" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                                            <option value="Ayah">Ayah Kandung</option>
                                            <option value="Ibu">Ibu Kandung</option>
                                            <option value="Wali">Wali Santri</option>
                                            <option value="Keluarga">Keluarga Diberi Kuasa</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp Pemohon (Aktif) <span class="text-rose-500">*</span></label>
                                    <input type="text" name="no_whatsapp_pemohon" required value="<?= htmlspecialchars($ortu_profil['no_whatsapp'] ?? '') ?>" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition" placeholder="Contoh: 08123456789">
                                    <p class="text-[10px] text-slate-400 mt-0.5">Notifikasi status persetujuan akan dikirim ke nomor ini</p>
                                </div>

                                <!-- WAKTU MULAI & RENCANA KEMBALI -->
                                <div class="p-3.5 bg-teal-50/50 rounded-2xl border border-teal-100 space-y-3">
                                    <div class="text-[11px] font-black text-[#0b8478] flex items-center gap-1.5">
                                        <i class="fas fa-clock"></i> Jadwal Izin
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Tgl Mulai / Berangkat</label>
                                            <input type="date" name="tanggal_mulai" required value="<?= date('Y-m-d') ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Jam Berangkat</label>
                                            <input type="time" name="jam_mulai" required value="08:00" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Tgl Rencana Kembali</label>
                                            <input type="date" name="tanggal_selesai" required value="<?= date('Y-m-d') ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-slate-600 mb-1">Jam Kembali</label>
                                            <input type="time" name="jam_selesai" required value="17:00" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-semibold">
                                        </div>
                                    </div>
                                </div>

                                <!-- PENJEMPUT -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Penjemput / Pendamping</label>
                                    <input type="text" name="penjemput_nama" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition" placeholder="Kosongkan jika sama dengan nama pemohon">
                                </div>

                                <!-- ALASAN KEPERLUAN -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-1">Alasan / Keperluan Izin <span class="text-rose-500">*</span></label>
                                    <textarea name="alasan_keperluan" rows="3" required class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition" placeholder="Tuliskan tujuan menjenguk / keperluan mengajak ananda keluar asrama..."></textarea>
                                </div>

                                <button type="submit" class="w-full py-3 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-black shadow-md shadow-teal-900/10 flex items-center justify-center gap-2 transition">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Kirim Pengajuan Izin</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- KOLOM RIWAYAT & STATUS PERIZINAN (7/12) -->
                    <div class="lg:col-span-7 space-y-4">
                        <div class="bg-white rounded-3xl p-5 sm:p-6 shadow-sm border border-slate-200/80">
                            <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center text-sm">
                                        <i class="fas fa-clock-rotate-left"></i>
                                    </span>
                                    <div>
                                        <h3 class="font-extrabold text-sm text-slate-800">Riwayat Perizinan Ananda</h3>
                                        <p class="text-[11px] text-slate-500">Pantau status izin & akses E-Pass Izin Resmi</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-bold">
                                    <?= count($riwayat_izin) ?> Riwayat
                                </span>
                            </div>

                            <?php if (empty($riwayat_izin)): ?>
                                <div class="text-center py-12 px-4">
                                    <div class="w-16 h-16 rounded-3xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                                        <i class="fas fa-file-circle-check"></i>
                                    </div>
                                    <h4 class="font-bold text-sm text-slate-800">Belum Ada Pengajuan Izin</h4>
                                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Jika ingin menjenguk, menjemput, atau mengajak ananda keluar asrama, silakan isi formulir di samping.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($riwayat_izin as $iz): 
                                        $st = $iz['status'];
                                        $badge_bg = 'bg-amber-50 text-amber-800 border-amber-200';
                                        $icon_st = 'fa-hourglass-half';
                                        if ($st === 'Disetujui') {
                                            $badge_bg = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                            $icon_st = 'fa-circle-check';
                                        } elseif ($st === 'Ditolak') {
                                            $badge_bg = 'bg-rose-50 text-rose-800 border-rose-200';
                                            $icon_st = 'fa-circle-xmark';
                                        } elseif ($st === 'Sedang Berjalan') {
                                            $badge_bg = 'bg-blue-50 text-blue-800 border-blue-200';
                                            $icon_st = 'fa-person-walking-arrow-right';
                                        } elseif ($st === 'Selesai / Kembali') {
                                            $badge_bg = 'bg-slate-100 text-slate-700 border-slate-300';
                                            $icon_st = 'fa-house-circle-check';
                                        }
                                    ?>
                                    <div class="p-4 rounded-2xl border border-slate-200/80 bg-white hover:border-teal-200 transition space-y-2.5">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2 flex-wrap">
                                                    <span class="font-black text-sm text-slate-900"><?= htmlspecialchars($iz['jenis_izin']) ?></span>
                                                    <span class="px-2.5 py-0.5 rounded-full border text-[10px] font-black flex items-center gap-1 <?= $badge_bg ?>">
                                                        <i class="fas <?= $icon_st ?>"></i> <?= htmlspecialchars($st) ?>
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 mt-0.5">
                                                    Diajukan: <?= date('d M Y, H:i', strtotime($iz['created_at'])) ?> WIB
                                                </p>
                                            </div>

                                            <?php if ($st === 'Disetujui' || $st === 'Sedang Berjalan'): ?>
                                                <button type="button" 
                                                        onclick="openModalEPass(<?= htmlspecialchars(json_encode($iz)) ?>, '<?= addslashes(htmlspecialchars($active_santri['nama_lengkap'] ?? 'Santri')) ?>', '<?= addslashes(htmlspecialchars($active_santri['kelas_sekarang'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($active_santri['kamar_asrama'] ?? '')) ?>')"
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                                                    <i class="fas fa-qrcode text-[10px]"></i> E-Pass Izin
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
                                            <div class="space-y-0.5">
                                                <span class="text-[10px] font-bold text-slate-400 block uppercase">Jadwal Izin</span>
                                                <span class="font-bold text-slate-700"><?= date('d/m/Y', strtotime($iz['tanggal_mulai'])) ?> (<?= substr($iz['jam_mulai'], 0, 5) ?>)</span>
                                                <span class="text-slate-400 block text-[11px]">s/d <?= date('d/m/Y', strtotime($iz['tanggal_selesai'])) ?> (<?= substr($iz['jam_selesai'], 0, 5) ?>)</span>
                                            </div>
                                            <div class="space-y-0.5">
                                                <span class="text-[10px] font-bold text-slate-400 block uppercase">Penjemput / Pendamping</span>
                                                <span class="font-bold text-slate-700"><?= htmlspecialchars($iz['penjemput_nama'] ?: $iz['nama_pemohon']) ?></span>
                                                <span class="text-slate-400 block text-[11px]"><?= htmlspecialchars($iz['hubungan_pemohon']) ?></span>
                                            </div>
                                        </div>

                                        <div class="text-xs bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                                            <span class="font-bold text-slate-600">Keperluan:</span>
                                            <span class="text-slate-700"><?= htmlspecialchars($iz['alasan_keperluan']) ?></span>
                                        </div>

                                        <?php if (!empty($iz['catatan_musyrif'])): ?>
                                            <div class="text-xs bg-teal-50/70 p-2.5 rounded-xl border border-teal-200 text-teal-900">
                                                <span class="font-bold"><i class="fas fa-comment-dots mr-1"></i>Catatan Pengasuhan:</span>
                                                <span><?= htmlspecialchars($iz['catatan_musyrif']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- ======================================================== -->
    <!-- MODAL E-PASS / SURAT IZIN RESMI PESANTREN (DIGITAL PASS) -->
    <!-- ======================================================== -->
    <div id="modalEPass" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-emerald-200 w-full max-w-md overflow-hidden flex flex-col animate-in fade-in zoom-in duration-150">
            <!-- HEADER TIKET RESMI -->
            <div class="px-6 py-5 bg-gradient-to-r from-teal-700 to-emerald-800 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-white/20 flex items-center justify-center text-lg">
                        <i class="fas fa-id-badge"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm uppercase tracking-wider">E-Pass Izin Resmi Santri</h3>
                        <p class="text-[10px] text-teal-200">Villa Quran Baron Malang</p>
                    </div>
                </div>
                <button type="button" onclick="closeModalEPass()" class="text-teal-200 hover:text-white p-1">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- BODY TIKET -->
            <div class="p-6 space-y-4 text-xs">
                <div class="text-center pb-3 border-b border-dashed border-slate-200">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-xs font-black inline-flex items-center gap-1.5 shadow-xs mb-2">
                        <i class="fas fa-circle-check text-emerald-600"></i> PERIZINAN TELAH DISETUJUI
                    </span>
                    <h4 class="font-black text-base text-slate-900 mt-1" id="epassSantriNama">-</h4>
                    <p class="text-[11px] text-[#0b8478] font-bold" id="epassSantriKelasKamar">-</p>
                </div>

                <div class="space-y-2.5 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Jenis Izin:</span>
                        <span class="font-bold text-slate-800" id="epassJenisIzin">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Penjemput:</span>
                        <span class="font-bold text-slate-800" id="epassPenjemput">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Mulai:</span>
                        <span class="font-bold text-slate-800" id="epassWaktuMulai">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Batas Kembali:</span>
                        <span class="font-black text-rose-700" id="epassWaktuKembali">-</span>
                    </div>
                </div>

                <!-- KODE VERIFIKASI UNIK -->
                <div class="text-center pt-2">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Kode Verifikasi Gerbang</p>
                    <div class="p-3 bg-teal-50 border border-teal-200 rounded-2xl font-mono text-sm font-black text-teal-900 tracking-wider inline-block" id="epassKode">
                        VQB-IZIN-0000
                    </div>
                    <p class="text-[10px] text-slate-400 mt-2">Tunjukkan layar ini kepada Satpam / Musyrif di gerbang pesantren.</p>
                </div>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 text-center">
                <button type="button" onclick="closeModalEPass()" class="w-full py-2.5 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition">
                    Tutup E-Pass
                </button>
            </div>
        </div>
    </div>

    <script>
        function openModalEPass(izin, namaSantri, kelasSantri, kamarSantri) {
            document.getElementById('epassSantriNama').innerText = namaSantri;
            document.getElementById('epassSantriKelasKamar').innerText = (kelasSantri || 'Santri') + (kamarSantri ? ' • Kamar ' + kamarSantri : '');
            document.getElementById('epassJenisIzin').innerText = izin.jenis_izin;
            document.getElementById('epassPenjemput').innerText = (izin.penjemput_nama || izin.nama_pemohon) + ' (' + izin.hubungan_pemohon + ')';
            document.getElementById('epassWaktuMulai').innerText = izin.tanggal_mulai + ' ' + (izin.jam_mulai ? izin.jam_mulai.substring(0, 5) : '');
            document.getElementById('epassWaktuKembali').innerText = izin.tanggal_selesai + ' ' + (izin.jam_selesai ? izin.jam_selesai.substring(0, 5) : '');
            document.getElementById('epassKode').innerText = 'VQB-IZIN-' + String(izin.id).padStart(4, '0') + '-' + izin.santri_id;

            document.getElementById('modalEPass').classList.remove('hidden');
        }

        function closeModalEPass() {
            document.getElementById('modalEPass').classList.add('hidden');
        }
    </script>
</body>
</html>