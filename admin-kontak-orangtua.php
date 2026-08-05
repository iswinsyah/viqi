<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Self-healing database table for Log Kontak Orang Tua / Wali Santri
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_kontak_orangtua (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    ustadz_id INT NOT NULL,
    tanggal DATETIME NOT NULL,
    topik_komunikasi VARCHAR(150) NOT NULL,
    ringkasan_kabar TEXT NOT NULL,
    respon_orangtua VARCHAR(150) DEFAULT 'Positif & Menerima Kabar',
    catatan_ortu TEXT NULL,
    bukti_chat_foto VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_santri (santri_id),
    KEY idx_ustadz (ustadz_id),
    KEY idx_tanggal (tanggal)
)");

// Check user role
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : 0;
$is_super_admin = ($ustadz_id === 9999);

$norm_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);

$is_admin_or_kepala = $is_super_admin || !empty(array_intersect($norm_roles, ['super_admin', 'kepala_sekolah', 'admin_sekolah', 'kepala_mahad', 'sekretaris_sekolah']));
$is_ka_rijal = !empty(array_intersect($norm_roles, ['kepala_asrama', 'kepala_asrama_rijal']));
$is_ka_nisa = !empty(array_intersect($norm_roles, ['kepala_asrama_nisa']));

// Fetch santri binaan with parents contact details
$santri_binaan = [];
if ($is_admin_or_kepala) {
    // Admin / Super Admin can see all active santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, jenis_kelamin, nama_ayah, nama_ibu, no_hp_ortu FROM buku_induk_santri WHERE status_santri = 'Aktif' ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} elseif ($is_ka_rijal) {
    // Kepala Asrama Rijal: can see all active male santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, jenis_kelamin, nama_ayah, nama_ibu, no_hp_ortu FROM buku_induk_santri WHERE status_santri = 'Aktif' AND (jenis_kelamin = 'Laki-laki' OR jenis_kelamin IS NULL) ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} elseif ($is_ka_nisa) {
    // Kepala Asrama Nisa: can see all active female santri
    $res_sb = $conn->query("SELECT id, nama_lengkap, nis, kelas_sekarang, jenis_kelamin, nama_ayah, nama_ibu, no_hp_ortu FROM buku_induk_santri WHERE status_santri = 'Aktif' AND jenis_kelamin = 'Perempuan' ORDER BY nama_lengkap ASC");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
} else {
    // Musyrif / Musyrifah: see only their assigned halaqoh group members
    $res_sb = $conn->query("
        SELECT DISTINCT s.id, s.nama_lengkap, s.nis, s.kelas_sekarang, s.jenis_kelamin, s.nama_ayah, s.nama_ibu, s.no_hp_ortu 
        FROM buku_induk_santri s 
        JOIN halaqoh_anggota a ON s.id = a.santri_id 
        JOIN halaqoh_grup g ON a.grup_id = g.id 
        WHERE g.musyrif_id = $ustadz_id AND s.status_santri = 'Aktif'
        ORDER BY s.nama_lengkap ASC
    ");
    if ($res_sb) {
        while ($r = $res_sb->fetch_assoc()) $santri_binaan[] = $r;
    }
}

$pesan_sukses = '';
$pesan_error = '';

// Handle Form Submission (Simpan Log Kontak Orang Tua)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_log_kontak'])) {
    $santri_id = (int)$_POST['santri_id'];
    $waktu_kontak = $conn->real_escape_string($_POST['waktu_kontak']);
    $topik_komunikasi = $conn->real_escape_string($_POST['topik_komunikasi']);
    $ringkasan_kabar = $conn->real_escape_string($_POST['ringkasan_kabar']);
    $respon_orangtua = $conn->real_escape_string($_POST['respon_orangtua']);
    $catatan_ortu = $conn->real_escape_string($_POST['catatan_ortu'] ?? '');
    $bukti_chat_foto = $conn->real_escape_string($_POST['bukti_chat_foto'] ?? '');

    if ($santri_id <= 0 || empty($topik_komunikasi) || empty($ringkasan_kabar)) {
        $pesan_error = "Nama Santri, Topik Komunikasi, dan Ringkasan Kabar wajib diisi!";
    } else {
        $waktu_val = !empty($waktu_kontak) ? $waktu_kontak : date('Y-m-d H:i:s');
        $sql = "INSERT INTO jurnal_kontak_orangtua (santri_id, ustadz_id, tanggal, topik_komunikasi, ringkasan_kabar, respon_orangtua, catatan_ortu, bukti_chat_foto)
                VALUES ($santri_id, $ustadz_id, '$waktu_val', '$topik_komunikasi', '$ringkasan_kabar', '$respon_orangtua', '$catatan_ortu', '$bukti_chat_foto')";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Laporan Kontak Orang Tua / Wali Santri berhasil tersimpan ke database & terhitung di KPI Musyrif!";
        } else {
            $pesan_error = "Gagal menyimpan log komunikasi: " . $conn->error;
        }
    }
}

// Fetch KPI Stats for Current Month
$bulan_ini = date('Y-m');
$where_kpi = $is_admin_or_kepala ? "1=1" : "j.ustadz_id = $ustadz_id";

$total_binaan_cnt = count($santri_binaan);
$santri_dikontak_cnt = 0;

if ($total_binaan_cnt > 0) {
    $sb_ids = array_column($santri_binaan, 'id');
    $sb_str = implode(',', $sb_ids);
    $res_dk = $conn->query("SELECT COUNT(DISTINCT santri_id) as total FROM jurnal_kontak_orangtua WHERE santri_id IN ($sb_str) AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'");
    if ($res_dk) $santri_dikontak_cnt = (int)$res_dk->fetch_assoc()['total'];
}

$persen_kpi = ($total_binaan_cnt > 0) ? round(($santri_dikontak_cnt / $total_binaan_cnt) * 100, 1) : 0;

// Fetch Recent Logs
$res_logs = $conn->query("
    SELECT j.*, s.nama_lengkap, s.nis, s.kelas_sekarang, s.nama_ayah, s.nama_ibu, s.no_hp_ortu, u.nama as nama_musyrif 
    FROM jurnal_kontak_orangtua j 
    JOIN buku_induk_santri s ON j.santri_id = s.id 
    LEFT JOIN akun_ustadz u ON j.ustadz_id = u.id 
    WHERE $where_kpi 
    ORDER BY j.tanggal DESC, j.created_at DESC 
    LIMIT 50
");

$logs_kontak = [];
if ($res_logs) {
    while ($r = $res_logs->fetch_assoc()) $logs_kontak[] = $r;
}

$active_menu = 'kontak_orangtua';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak Orang Tua / Wali Santri | Ruang Musyrif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <!-- SIDEBAR -->
    <?php include 'sidebar-hr.php'; ?>

    <!-- MAIN BODY -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-slate-500 hover:text-slate-700 md:hidden mr-4 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-slate-800 text-base sm:text-lg flex items-center gap-2">
                    <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-lg text-xs font-extrabold uppercase tracking-wide">
                        <i class="fab fa-whatsapp mr-1"></i>Kontak Orang Tua
                    </span>
                    <span>SADIGS 4.0</span>
                </h2>
            </div>
            <div class="flex items-center space-x-3">
                <span class="hidden sm:inline-block text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-full">
                    <i class="fas fa-calendar-day mr-1.5 text-emerald-600"></i><?= date('d F Y') ?>
                </span>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
            
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-600 flex items-center justify-center text-white shadow-md shadow-emerald-200">
                            <i class="fab fa-whatsapp text-xl"></i>
                        </div>
                        <span>Log Komunikasi & Kontak Orang Tua Santri</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Pencatatan aktivitas komunikasi berkala Musyrif ke wali santri dan penilaian kinerja (KPI) pengawasan asrama</p>
                </div>
            </div>

            <!-- STATS KPI CARD FOR MUSYRIF -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= $total_binaan_cnt ?> Santri</div>
                        <div class="text-xs font-semibold text-slate-500">Total Santri Binaanku</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900"><?= $santri_dikontak_cnt ?> / <?= $total_binaan_cnt ?></div>
                        <div class="text-xs font-semibold text-slate-500">Dikontak Bulan Ini (<?= date('F Y') ?>)</div>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-100 text-teal-800 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-extrabold text-slate-700">KPI Keaktifan Musyrif</span>
                            <span class="text-xs font-black text-emerald-700"><?= $persen_kpi ?>%</span>
                        </div>
                        <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 h-2 rounded-full transition-all duration-500" style="width: <?= min($persen_kpi, 100) ?>%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <?= $pesan_sukses ? "<div class='bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-check-circle mr-2 text-emerald-500'></i>$pesan_sukses</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>
            <?= $pesan_error ? "<div class='bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-r-xl shadow-sm mb-6 flex justify-between items-center'><div><i class='fas fa-exclamation-triangle mr-2 text-rose-500'></i>$pesan_error</div><button onclick='this.parentElement.remove()'><i class='fas fa-times'></i></button></div>" : '' ?>

            <!-- FORMULIR INPUT KONTAK ORANG TUA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <i class="fas fa-edit text-emerald-600"></i>
                        <span>Input Laporan Komunikasi & Kontak Orang Tua</span>
                    </h2>
                    <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full">
                        Form Resmi Musyrif Pembina
                    </span>
                </div>

                <form method="POST" class="p-6 space-y-6">
                    <input type="hidden" name="simpan_log_kontak" value="1">

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Santri Binaan <span class="text-rose-600">*</span></label>
                            <select name="santri_id" id="santri_select" onchange="updateWAButton()" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white text-slate-900 focus:ring-2 focus:ring-emerald-500">
                                <option value="">-- Pilih Santri --</option>
                                <?php foreach ($santri_binaan as $st): ?>
                                    <option value="<?= $st['id'] ?>" data-nama="<?= htmlspecialchars($st['nama_lengkap']) ?>" data-kelas="<?= htmlspecialchars($st['kelas_sekarang']) ?>" data-hp="<?= preg_replace('/[^0-9]/', '', $st['no_hp_ortu'] ?? '') ?>" data-ortu="<?= htmlspecialchars(!empty($st['nama_ayah']) ? $st['nama_ayah'] : (!empty($st['nama_ibu']) ? $st['nama_ibu'] : 'Wali Santri')) ?>">
                                        <?= htmlspecialchars($st['nama_lengkap']) ?> (Ortu: <?= htmlspecialchars(!empty($st['nama_ayah']) ? $st['nama_ayah'] : (!empty($st['nama_ibu']) ? $st['nama_ibu'] : '-')) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Waktu Komunikasi</label>
                            <input type="datetime-local" name="waktu_kontak" value="<?= date('Y-m-d\TH:i') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Kategori / Topik Komunikasi <span class="text-rose-600">*</span></label>
                            <select name="topik_komunikasi" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white text-slate-900">
                                <option value="📖 Perkembangan Hafalan & Ibadah">📖 Perkembangan Hafalan & Ibadah</option>
                                <option value="🩺 Kondisi Kesehatan & Fisik Santri">🩺 Kondisi Kesehatan & Fisik Santri</option>
                                <option value="⚖️ Kedisiplinan & Adab di Asrama">⚖️ Kedisiplinan & Adab di Asrama</option>
                                <option value="💳 Informasi Keuangan & Uang Saku">💳 Informasi Keuangan & Uang Saku</option>
                                <option value="📣 Informasi Umum & Undangan Pesantren">📣 Informasi Umum & Undangan Pesantren</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Ringkasan Kabar ke Ortu <span class="text-rose-600">*</span></label>
                            <textarea name="ringkasan_kabar" id="ringkasan_kabar" rows="3" required placeholder="Tuliskan ringkasan perkembangan santri yang disampaikan ke orang tua..." class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white leading-relaxed"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Respon & Tanggapan Orang Tua</label>
                            <select name="respon_orangtua" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs font-bold bg-white mb-2">
                                <option value="🟢 Sangat Positif & Apresiatif">🟢 Sangat Positif & Memberi Apresiasi</option>
                                <option value="🔵 Positif & Menerima Kabar" selected>🔵 Positif & Menerima Kabar Baik</option>
                                <option value="🟡 Ada Titipan Pesan Khusus dari Ortu">🟡 Ada Titipan Pesan Khusus dari Ortu</option>
                                <option value="🔴 Perlu Tindakan Lanjutan Kepala Asrama">🔴 Perlu Tindakan Lanjutan Kepala Asrama</option>
                            </select>

                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Titipan Pesan Ortu (Opsional)</label>
                            <input type="text" name="catatan_ortu" placeholder="contoh: Mohon dibantu ingatkan minum obat jam 8 malam" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs bg-white">
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t">
                        <div>
                            <a id="btn_kirim_wa" href="#" target="_blank" onclick="kirimWAAutoLog(event)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-5 py-2.5 rounded-xl text-xs shadow-md transition inline-flex items-center gap-2 opacity-50 cursor-not-allowed">
                                <i class="fab fa-whatsapp text-lg"></i>
                                <span>1-Click Buka Chat WA & Auto-Log</span>
                            </a>
                        </div>
                        <div>
                            <button type="submit" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold px-6 py-2.5 rounded-xl text-xs shadow-md transition flex items-center gap-2">
                                <i class="fas fa-save"></i> Simpan Laporan Kontak
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- RIWAYAT LOG KOMUNIKASI ORANG TUA -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        <i class="fas fa-history text-emerald-600"></i>
                        <span>Riwayat Log Komunikasi Orang Tua Santri</span>
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-100 border-b border-slate-200 text-slate-700 uppercase text-[11px] font-extrabold tracking-wider">
                                <th class="py-3.5 px-6">Waktu & Santri</th>
                                <th class="py-3.5 px-6">Orang Tua / Wali</th>
                                <th class="py-3.5 px-6">Topik Kabar</th>
                                <th class="py-3.5 px-6">Ringkasan Kabar & Respon</th>
                                <th class="py-3.5 px-6">Musyrif Pembina</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
                            <?php if (!empty($logs_kontak)): ?>
                                <?php foreach ($logs_kontak as $lk): ?>
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($lk['nama_lengkap']) ?></div>
                                            <div class="text-xs text-slate-500 font-semibold mt-0.5"><?= date('d F Y (H:i)', strtotime($lk['tanggal'])) ?></div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="font-bold text-slate-800"><?= htmlspecialchars(!empty($lk['nama_ayah']) ? $lk['nama_ayah'] : (!empty($lk['nama_ibu']) ? $lk['nama_ibu'] : 'Wali Santri')) ?></div>
                                            <?php if (!empty($lk['no_hp_ortu'])): ?>
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lk['no_hp_ortu']) ?>" target="_blank" class="text-[11px] text-emerald-600 hover:underline font-bold flex items-center gap-1 mt-0.5">
                                                    <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($lk['no_hp_ortu']) ?>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <span class="inline-block bg-slate-100 text-slate-800 text-xs px-2.5 py-1 rounded-lg font-extrabold border">
                                                <?= htmlspecialchars($lk['topik_komunikasi']) ?>
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-6 align-top">
                                            <div class="text-xs text-slate-800 leading-relaxed">
                                                "<?= htmlspecialchars($lk['ringkasan_kabar']) ?>"
                                            </div>
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-300">
                                                    <?= htmlspecialchars($lk['respon_orangtua']) ?>
                                                </span>
                                                <?php if (!empty($lk['catatan_ortu'])): ?>
                                                    <span class="text-[11px] text-slate-600 italic">Pesan Ortu: "<?= htmlspecialchars($lk['catatan_ortu']) ?>"</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-6 align-top font-semibold text-slate-800">
                                            <?= htmlspecialchars($lk['nama_musyrif'] ?? 'Musyrif Pembina') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="py-8 text-center text-slate-400 italic">Belum ada riwayat kontak orang tua santri.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        const openBtn = document.getElementById('open-sidebar-hr');
        if (openBtn) {
            openBtn.addEventListener('click', () => {
                document.getElementById('sidebar-hr').classList.toggle('hidden');
                document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
            });
        }

        function updateWAButton() {
            const select = document.getElementById('santri_select');
            const btn = document.getElementById('btn_kirim_wa');
            const selectedOpt = select.options[select.selectedIndex];

            if (!selectedOpt.value) {
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.removeAttribute('href');
                return;
            }

            const nama = selectedOpt.getAttribute('data-nama');
            const kelas = selectedOpt.getAttribute('data-kelas');
            const hp = selectedOpt.getAttribute('data-hp');
            const ortu = selectedOpt.getAttribute('data-ortu');

            if (!hp) {
                alert('Nomor HP Orang Tua belum terdaftar di Buku Induk Santri.');
                btn.classList.add('opacity-50', 'cursor-not-allowed');
                btn.removeAttribute('href');
                return;
            }

            const pesan = `Assalamu'alaikum Bapak/Ibu ${ortu}, wali dari ananda ${nama} (Kelas ${kelas}). Saya Musyrif Pembina di Pesantren Villa Qur'an Indonesia. Izin menyampaikan kabar perkembangan ananda...`;
            const waUrl = `https://wa.me/${hp}?text=${encodeURIComponent(pesan)}`;

            btn.setAttribute('href', waUrl);
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }

        function kirimWAAutoLog(e) {
            const select = document.getElementById('santri_select');
            if (!select.value) {
                e.preventDefault();
                alert('Pilih santri terlebih dahulu.');
            }
        }
    </script>
</body>
</html>
