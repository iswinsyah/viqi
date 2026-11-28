<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// Hak akses khusus Super Admin, Admin Sekolah, Kepala Sekolah, Sekretaris, Kepala Mahad
$user_roles_check = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_super_admin = (
    (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) ||
    in_array('super_admin', $user_roles_check) ||
    (isset($_SESSION['app_username']) && in_array(strtolower($_SESSION['app_username']), ['winsyah', 'viqi']))
);

$active_menu = 'data_center';
$pesan_sukses = '';
$pesan_error = '';

// ==========================================
// 1. HANDLER AKSI CEPAT DATA CENTER
// ==========================================

// A. Update Relasi Santri & Orang Tua (Bisa 1 atau Banyak Ortu/Ayah-Ibu)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_relasi') {
    $santri_id = (int)($_POST['santri_id'] ?? 0);
    $ortu_ids = isset($_POST['ortu_ids']) && is_array($_POST['ortu_ids']) ? array_filter(array_map('intval', $_POST['ortu_ids'])) : [];

    if ($santri_id > 0) {
        $conn->query("DELETE FROM santri_orangtua_link WHERE santri_id = $santri_id");
        if (!empty($ortu_ids)) {
            $first_id = $ortu_ids[0];
            $conn->query("UPDATE buku_induk_santri SET id_orangtua = $first_id WHERE id = $santri_id");
            foreach ($ortu_ids as $oid) {
                $conn->query("INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES ($santri_id, $oid)");
            }
            $pesan_sukses = "Keterhubungan akun orang tua berhasil diperbarui!";
        } else {
            $conn->query("UPDATE buku_induk_santri SET id_orangtua = NULL WHERE id = $santri_id");
            $pesan_sukses = "Relasi orang tua untuk santri ini telah dilepas.";
        }
    }
}

// B. Tombol Sakti: Auto-Match Semua Relasi via No. WhatsApp
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'automatch_all_wa') {
    $matched_count = 0;
    
    // Ambil semua akun orang tua yang memiliki no_whatsapp
    $res_all_ortu = $conn->query("SELECT id, nama_orangtua, username, no_whatsapp FROM akun_orangtua WHERE no_whatsapp IS NOT NULL AND TRIM(no_whatsapp) != ''");
    if ($res_all_ortu && $res_all_ortu->num_rows > 0) {
        while ($ortu = $res_all_ortu->fetch_assoc()) {
            $oid = (int)$ortu['id'];
            $clean_phone = preg_replace('/\D/', '', $ortu['no_whatsapp']);
            if (strlen($clean_phone) >= 8) {
                $suffix = substr($clean_phone, -8);
                $stmt_match = $conn->prepare("
                    SELECT id FROM buku_induk_santri 
                    WHERE (no_whatsapp_ayah LIKE ? OR no_whatsapp_ibu LIKE ? OR no_whatsapp_wali LIKE ?) 
                      AND status_santri = 'Aktif'
                ");
                if ($stmt_match) {
                    $like_param = '%' . $suffix . '%';
                    $stmt_match->bind_param("sss", $like_param, $like_param, $like_param);
                    $stmt_match->execute();
                    $res_m = $stmt_match->get_result();
                    while ($m = $res_m->fetch_assoc()) {
                        $sid = (int)$m['id'];
                        // Cek apakah relasi sudah ada
                        $cek_rel = $conn->query("SELECT id FROM santri_orangtua_link WHERE santri_id = $sid AND orangtua_id = $oid LIMIT 1");
                        if (!$cek_rel || $cek_rel->num_rows === 0) {
                            $conn->query("INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES ($sid, $oid)");
                            $conn->query("UPDATE buku_induk_santri SET id_orangtua = $oid WHERE id = $sid AND (id_orangtua IS NULL OR id_orangtua = 0)");
                            $matched_count++;
                        }
                    }
                }
            }
        }
    }
    
    if ($matched_count > 0) {
        $pesan_sukses = "Alhamdulillah! Berhasil menghubungkan otomatis $matched_count relasi santri dan orang tua berdasarkan kesamaan No. WhatsApp.";
    } else {
        $pesan_sukses = "Pemeriksaan selesai. Semua data yang cocok nomor WhatsApp-nya sudah terhubung sebelumnya.";
    }
}

// C. Quick Create Akun Orang Tua & Langsung Hubungkan
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'quick_create_ortu') {
    $santri_id = (int)($_POST['santri_id'] ?? 0);
    $nama_ortu = $conn->real_escape_string($_POST['nama_ortu'] ?? '');
    $username_ortu = $conn->real_escape_string($_POST['username_ortu'] ?? '');
    $password_ortu = $conn->real_escape_string($_POST['password_ortu'] ?? '12345678');
    $no_wa = $conn->real_escape_string($_POST['no_wa'] ?? '');

    if (!empty($nama_ortu) && !empty($username_ortu)) {
        $cek_u = $conn->query("SELECT id FROM akun_orangtua WHERE username = '$username_ortu'");
        if ($cek_u && $cek_u->num_rows > 0) {
            $pesan_error = "Username '$username_ortu' sudah digunakan oleh akun orang tua lain!";
        } else {
            $conn->query("INSERT INTO akun_orangtua (nama_orangtua, username, password, no_whatsapp) VALUES ('$nama_ortu', '$username_ortu', '$password_ortu', '$no_wa')");
            $new_ortu_id = $conn->insert_id;
            if ($santri_id > 0 && $new_ortu_id > 0) {
                $conn->query("INSERT IGNORE INTO santri_orangtua_link (santri_id, orangtua_id) VALUES ($santri_id, $new_ortu_id)");
                $conn->query("UPDATE buku_induk_santri SET id_orangtua = $new_ortu_id WHERE id = $santri_id");
            }
            $pesan_sukses = "Akun orang tua baru untuk '$nama_ortu' berhasil dibuat dan langsung dihubungkan!";
        }
    }
}

// ==========================================
// 2. QUERY STATISTIK DATA CENTER
// ==========================================
$q_total_santri = $conn->query("SELECT COUNT(*) as total FROM buku_induk_santri WHERE status_santri = 'Aktif'");
$total_santri_aktif = $q_total_santri ? (int)$q_total_santri->fetch_assoc()['total'] : 0;

$q_santri_terhubung = $conn->query("
    SELECT COUNT(DISTINCT s.id) as total 
    FROM buku_induk_santri s 
    INNER JOIN santri_orangtua_link sol ON s.id = sol.santri_id 
    WHERE s.status_santri = 'Aktif'
");
$total_santri_terhubung = $q_santri_terhubung ? (int)$q_santri_terhubung->fetch_assoc()['total'] : 0;
$total_santri_belum_terhubung = max(0, $total_santri_aktif - $total_santri_terhubung);
$persen_terhubung = $total_santri_aktif > 0 ? round(($total_santri_terhubung / $total_santri_aktif) * 100) : 0;

$q_total_ortu = $conn->query("SELECT COUNT(*) as total FROM akun_orangtua");
$total_akun_ortu = $q_total_ortu ? (int)$q_total_ortu->fetch_assoc()['total'] : 0;

$q_total_pegawai = $conn->query("SELECT COUNT(*) as total FROM akun_ustadz");
$total_pegawai = $q_total_pegawai ? (int)$q_total_pegawai->fetch_assoc()['total'] : 0;

// Daftar Pilihan Semua Akun Orang Tua (untuk modal relasi)
$all_akun_ortu = [];
$res_ao = $conn->query("SELECT id, nama_orangtua, username, no_whatsapp FROM akun_orangtua ORDER BY nama_orangtua ASC");
if ($res_ao) {
    while ($r = $res_ao->fetch_assoc()) $all_akun_ortu[] = $r;
}

// Query Matriks Santri & Relasi Orang Tua
$query_matriks = "
    SELECT 
        s.id as santri_id, s.nama_lengkap, s.nis, s.kelas_sekarang, s.kamar_asrama, s.foto_santri,
        s.nama_ayah, s.no_whatsapp_ayah,
        s.nama_ibu, s.no_whatsapp_ibu,
        s.nama_wali, s.no_whatsapp_wali,
        GROUP_CONCAT(DISTINCT o.id SEPARATOR ',') as linked_ortu_ids,
        GROUP_CONCAT(DISTINCT CONCAT(o.id, '::', o.nama_orangtua, '::', COALESCE(o.no_whatsapp, '-'), '::', o.username) SEPARATOR '||') as linked_ortu_details
    FROM buku_induk_santri s
    LEFT JOIN santri_orangtua_link sol ON s.id = sol.santri_id
    LEFT JOIN akun_orangtua o ON sol.orangtua_id = o.id
    WHERE s.status_santri = 'Aktif'
    GROUP BY s.id
    ORDER BY s.nama_lengkap ASC
";
$res_matriks = $conn->query($query_matriks);
$matriks_list = [];
if ($res_matriks) {
    while ($rm = $res_matriks->fetch_assoc()) {
        $matriks_list[] = $rm;
    }
}

// Query Daftar Kelas untuk Filter
$kelas_filter_list = [];
$res_kf = $conn->query("SELECT DISTINCT kelas_sekarang FROM buku_induk_santri WHERE status_santri = 'Aktif' AND kelas_sekarang IS NOT NULL AND kelas_sekarang != '' ORDER BY kelas_sekarang ASC");
if ($res_kf) {
    while ($rk = $res_kf->fetch_assoc()) $kelas_filter_list[] = $rk['kelas_sekarang'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Center (Pusat Data Akun & Relasi) | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-100 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER TOP -->
        <header class="h-16 bg-[#0b8478] text-white shadow-md flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-teal-700/60">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-hr" class="text-teal-200 hover:text-white md:hidden mr-2">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-white/20 text-white flex items-center justify-center text-sm shadow-xs">
                        <i class="fas fa-database"></i>
                    </span>
                    <div>
                        <h2 class="font-black text-sm sm:text-base leading-tight">Data Center SADIGS 4.0</h2>
                        <p class="text-[10px] text-teal-100 font-medium">Standar Kebenaran Akun & Keterhubungan Keluarga</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <form action="" method="POST" onsubmit="return confirm('Mulai auto-match? Sistem akan menghubungkan akun orang tua yang nomor WhatsApp-nya cocok dengan data santri.');">
                    <input type="hidden" name="action" value="automatch_all_wa">
                    <button type="submit" class="px-3.5 py-2 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5">
                        <i class="fas fa-wand-magic-sparkles text-xs"></i>
                        <span class="hidden sm:inline">Auto-Match No. WA</span>
                    </button>
                </form>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8 pb-24 md:pb-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- NOTIFIKASI -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                            <span><?= htmlspecialchars($pesan_sukses) ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-in fade-in">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-rose-600 text-base"></i>
                            <span><?= htmlspecialchars($pesan_error) ?></span>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times"></i></button>
                    </div>
                <?php endif; ?>

                <!-- STATISTIK KARTU DATA CENTER -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Santri Aktif</p>
                            <h3 class="text-2xl font-black text-slate-900 mt-1"><?= $total_santri_aktif ?></h3>
                            <p class="text-[10px] text-teal-700 font-bold mt-1">Buku Induk</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-xl shadow-xs">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Santri Terhubung</p>
                            <h3 class="text-2xl font-black text-emerald-700 mt-1"><?= $total_santri_terhubung ?> <span class="text-xs font-bold text-slate-400">/ <?= $total_santri_aktif ?></span></h3>
                            <p class="text-[10px] text-emerald-600 font-bold mt-1"><?= $persen_terhubung ?>% Keterhubungan</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shadow-xs">
                            <i class="fas fa-link"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Belum Terhubung</p>
                            <h3 class="text-2xl font-black <?= $total_santri_belum_terhubung > 0 ? 'text-amber-600' : 'text-slate-400' ?> mt-1"><?= $total_santri_belum_terhubung ?></h3>
                            <p class="text-[10px] text-amber-700 font-bold mt-1">Perlu Dihubungkan</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-xs">
                            <i class="fas fa-link-slash"></i>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Akun Ortu</p>
                            <h3 class="text-2xl font-black text-indigo-700 mt-1"><?= $total_akun_ortu ?></h3>
                            <p class="text-[10px] text-slate-500 font-bold mt-1">& <?= $total_pegawai ?> Akun Pegawai</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl shadow-xs">
                            <i class="fas fa-users-viewfinder"></i>
                        </div>
                    </div>
                </div>

                <!-- TABS PILIHAN -->
                <div class="bg-white rounded-3xl p-2 border border-slate-200/80 shadow-xs flex items-center gap-2 overflow-x-auto no-scrollbar">
                    <button type="button" onclick="switchDataTab('tab-relasi')" id="btn-tab-relasi" class="data-tab-btn px-5 py-2.5 rounded-2xl text-xs font-black transition flex items-center gap-2 bg-[#0b8478] text-white shadow-sm">
                        <i class="fas fa-people-roof"></i>
                        <span>1. Matriks Relasi Keluarga (Santri ↔ Orang Tua)</span>
                    </button>
                    <button type="button" onclick="switchDataTab('tab-direktori')" id="btn-tab-direktori" class="data-tab-btn px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-2">
                        <i class="fas fa-address-book"></i>
                        <span>2. Direktori Akun Terpadu</span>
                    </button>
                    <button type="button" onclick="switchDataTab('tab-audit')" id="btn-tab-audit" class="data-tab-btn px-5 py-2.5 rounded-2xl text-xs font-bold text-slate-600 hover:text-slate-900 hover:bg-slate-100 transition flex items-center gap-2">
                        <i class="fas fa-shield-halved"></i>
                        <span>3. Audit Kesehatan Data</span>
                        <?php if ($total_santri_belum_terhubung > 0): ?>
                            <span class="px-2 py-0.5 rounded-full bg-amber-500 text-white text-[10px] font-black"><?= $total_santri_belum_terhubung ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 1: MATRIKS KETERHUBUNGAN KELUARGA (SANTRI ↔ ORANG TUA) -->
                <!-- ======================================================== -->
                <div id="tab-relasi" class="data-tab-content space-y-4">
                    <!-- FILTER & PENCARIAN -->
                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                            <input type="text" id="searchSantriRelasi" oninput="filterMatriksTable()" placeholder="Cari nama santri, NIS, atau nama orang tua..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <select id="filterStatusRelasi" onchange="filterMatriksTable()" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-3 py-2 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478] transition">
                                <option value="">Semua Status Hubungan</option>
                                <option value="terhubung">Hanya Yang Terhubung</option>
                                <option value="belum">Belum Ada Akun Terhubung</option>
                            </select>
                            <select id="filterKelasRelasi" onchange="filterMatriksTable()" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-3 py-2 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478] transition">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_filter_list as $kf): ?>
                                    <option value="<?= htmlspecialchars($kf) ?>"><?= htmlspecialchars($kf) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- TABEL MATRIKS RELASI -->
                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse" id="tableMatriksRelasi">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black text-slate-600 uppercase tracking-wider">
                                        <th class="py-3.5 px-4">Santri</th>
                                        <th class="py-3.5 px-4">Data Ayah & Ibu (Buku Induk)</th>
                                        <th class="py-3.5 px-4">Akun Login Terhubung (Ruang Ortu)</th>
                                        <th class="py-3.5 px-4 text-center">Status</th>
                                        <th class="py-3.5 px-4 text-center">Aksi Cepat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <?php if (empty($matriks_list)): ?>
                                        <tr>
                                            <td colspan="5" class="py-8 text-center text-slate-400 font-bold">Tidak ada data santri aktif.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($matriks_list as $m): 
                                            $is_linked = !empty($m['linked_ortu_ids']);
                                            $ortu_items = [];
                                            if (!empty($m['linked_ortu_details'])) {
                                                $raw_items = explode('||', $m['linked_ortu_details']);
                                                foreach ($raw_items as $ri) {
                                                    $parts = explode('::', $ri);
                                                    if (count($parts) >= 4) {
                                                        $ortu_items[] = [
                                                            'id' => $parts[0],
                                                            'nama' => $parts[1],
                                                            'wa' => $parts[2],
                                                            'username' => $parts[3]
                                                        ];
                                                    }
                                                }
                                            }
                                        ?>
                                        <tr class="matriks-row hover:bg-teal-50/20 transition" 
                                            data-santri="<?= strtolower(htmlspecialchars($m['nama_lengkap'])) ?>"
                                            data-kelas="<?= htmlspecialchars($m['kelas_sekarang'] ?? '') ?>"
                                            data-status="<?= $is_linked ? 'terhubung' : 'belum' ?>"
                                            data-ortu="<?= strtolower(htmlspecialchars(($m['nama_ayah'] ?? '') . ' ' . ($m['nama_ibu'] ?? '') . ' ' . ($m['linked_ortu_details'] ?? ''))) ?>">
                                            
                                            <!-- KOLOM 1: SANTRI -->
                                            <td class="py-3.5 px-4 align-top">
                                                <div class="flex items-center gap-3">
                                                    <img src="<?= !empty($m['foto_santri']) ? htmlspecialchars($m['foto_santri']) : 'upload/default-avatar.png' ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($m['nama_lengkap']) ?>&background=0b8478&color=fff'" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-xs flex-shrink-0">
                                                    <div>
                                                        <h4 class="font-black text-slate-900 leading-tight"><?= htmlspecialchars($m['nama_lengkap']) ?></h4>
                                                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                                            <span class="px-2 py-0.5 bg-teal-50 text-teal-800 border border-teal-200 rounded-md text-[10px] font-bold"><?= htmlspecialchars($m['kelas_sekarang'] ?? 'Santri') ?></span>
                                                            <?php if (!empty($m['nis'])): ?>
                                                                <span class="text-[10px] font-mono text-slate-500">NIS: <?= htmlspecialchars($m['nis']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- KOLOM 2: DATA AYAH & IBU DI BUKU INDUK -->
                                            <td class="py-3.5 px-4 align-top space-y-1">
                                                <div class="text-[11px]">
                                                    <span class="font-bold text-slate-700">Ayah:</span> 
                                                    <?= !empty($m['nama_ayah']) ? htmlspecialchars($m['nama_ayah']) : '<span class="text-slate-400 italic">Belum diinput</span>' ?>
                                                    <?php if (!empty($m['no_whatsapp_ayah'])): ?>
                                                        <span class="inline-block text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded font-mono"><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($m['no_whatsapp_ayah']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-[11px]">
                                                    <span class="font-bold text-slate-700">Ibu:</span> 
                                                    <?= !empty($m['nama_ibu']) ? htmlspecialchars($m['nama_ibu']) : '<span class="text-slate-400 italic">Belum diinput</span>' ?>
                                                    <?php if (!empty($m['no_whatsapp_ibu'])): ?>
                                                        <span class="inline-block text-[10px] text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded font-mono"><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($m['no_whatsapp_ibu']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>

                                            <!-- KOLOM 3: AKUN LOGIN TERHUBUNG -->
                                            <td class="py-3.5 px-4 align-top">
                                                <?php if (empty($ortu_items)): ?>
                                                    <span class="text-rose-600 bg-rose-50 border border-rose-200 px-2.5 py-1 rounded-xl text-[11px] font-bold inline-flex items-center gap-1">
                                                        <i class="fas fa-circle-exclamation text-[10px]"></i> Belum Terhubung
                                                    </span>
                                                <?php else: ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($ortu_items as $oi): ?>
                                                            <div class="flex items-center gap-1.5 bg-teal-50/70 border border-teal-200 text-teal-900 px-2 py-1 rounded-xl text-[11px] font-bold flex-wrap">
                                                                <i class="fas fa-user-check text-[#0b8478] text-[10px]"></i>
                                                                <span><?= htmlspecialchars($oi['nama']) ?></span>
                                                                <span class="text-[10px] text-slate-500 font-mono font-normal">(@<?= htmlspecialchars($oi['username']) ?>)</span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- KOLOM 4: STATUS -->
                                            <td class="py-3.5 px-4 align-top text-center">
                                                <?php if ($is_linked): ?>
                                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-[10px] font-black inline-flex items-center gap-1">
                                                        <i class="fas fa-check"></i> Aktif
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-900 border border-amber-300 rounded-full text-[10px] font-black inline-flex items-center gap-1">
                                                        <i class="fas fa-triangle-exclamation"></i> Terputus
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- KOLOM 5: AKSI CEPAT -->
                                            <td class="py-3.5 px-4 align-top text-center">
                                                <button type="button" 
                                                        onclick="openModalEditRelasi(<?= $m['santri_id'] ?>, '<?= addslashes(htmlspecialchars($m['nama_lengkap'])) ?>', '<?= $m['linked_ortu_ids'] ?? '' ?>', '<?= addslashes(htmlspecialchars($m['nama_ayah'] ?? '')) ?>', '<?= addslashes(htmlspecialchars($m['nama_ibu'] ?? '')) ?>', '<?= htmlspecialchars($m['no_whatsapp_ayah'] ?? $m['no_whatsapp_ibu'] ?? '') ?>')"
                                                        class="px-3 py-1.5 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5 cursor-pointer">
                                                    <i class="fas fa-pen-to-square text-[10px]"></i> Hubungkan
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 2: DIREKTORI AKUN TERPADU (GLOBAL DIRECTORY) -->
                <!-- ======================================================== -->
                <div id="tab-direktori" class="data-tab-content space-y-4 hidden">
                    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="relative flex-1 max-w-md">
                            <i class="fas fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                            <input type="text" id="searchDirektoriInput" oninput="filterDirektoriTable()" placeholder="Ketik nama, username, atau no HP untuk mencari akun..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-[#0b8478] focus:outline-none transition">
                        </div>
                        <div class="flex items-center gap-2">
                            <select id="filterTipeAkun" onchange="filterDirektoriTable()" class="bg-slate-50 border border-slate-200 text-slate-800 text-xs font-bold rounded-xl px-3 py-2 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#0b8478] transition">
                                <option value="">Semua Tipe Akun</option>
                                <option value="pegawai">Pegawai / Ustadz</option>
                                <option value="walisantri">Wali Santri / Orang Tua</option>
                            </select>
                        </div>
                    </div>

                    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse" id="tableDirektori">
                                <thead>
                                    <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-black text-slate-600 uppercase tracking-wider">
                                        <th class="py-3.5 px-4">Nama & Identitas</th>
                                        <th class="py-3.5 px-4">Tipe Akun</th>
                                        <th class="py-3.5 px-4">Username Login</th>
                                        <th class="py-3.5 px-4">Password</th>
                                        <th class="py-3.5 px-4">No. WhatsApp</th>
                                        <th class="py-3.5 px-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 text-xs">
                                    <!-- 1. BARIS PEGAWAI / USTADZ -->
                                    <?php 
                                    $res_pegawai_list = $conn->query("SELECT id, nama_lengkap, username, password, role, no_hp, foto FROM akun_ustadz ORDER BY nama_lengkap ASC");
                                    if ($res_pegawai_list) {
                                        while ($p = $res_pegawai_list->fetch_assoc()):
                                    ?>
                                    <tr class="direktori-row hover:bg-slate-50/80 transition" data-tipe="pegawai" data-search="<?= strtolower(htmlspecialchars($p['nama_lengkap'] . ' ' . $p['username'] . ' ' . ($p['no_hp'] ?? ''))) ?>">
                                        <td class="py-3 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-teal-100 text-[#0b8478] flex items-center justify-center font-black text-xs flex-shrink-0">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <div><?= htmlspecialchars($p['nama_lengkap']) ?></div>
                                                <div class="text-[10px] text-slate-400 font-normal">Role: <?= htmlspecialchars($p['role'] ?? 'Pegawai') ?></div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-800 border border-blue-200 rounded-md text-[10px] font-bold">Pegawai / Staf</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold text-slate-800">@<?= htmlspecialchars($p['username']) ?></td>
                                        <td class="py-3 px-4 font-mono text-slate-500 text-[11px]"><?= htmlspecialchars(substr($p['password'], 0, 16)) ?></td>
                                        <td class="py-3 px-4 font-mono text-slate-700"><?= !empty($p['no_hp']) ? htmlspecialchars($p['no_hp']) : '<span class="text-slate-300">-</span>' ?></td>
                                        <td class="py-3 px-4 text-center"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-bold">Aktif</span></td>
                                    </tr>
                                    <?php endwhile; } ?>

                                    <!-- 2. BARIS ORANG TUA -->
                                    <?php 
                                    $res_ortu_list = $conn->query("SELECT id, nama_orangtua, username, password, no_whatsapp FROM akun_orangtua ORDER BY nama_orangtua ASC");
                                    if ($res_ortu_list) {
                                        while ($o = $res_ortu_list->fetch_assoc()):
                                    ?>
                                    <tr class="direktori-row hover:bg-slate-50/80 transition" data-tipe="walisantri" data-search="<?= strtolower(htmlspecialchars($o['nama_orangtua'] . ' ' . $o['username'] . ' ' . ($o['no_whatsapp'] ?? ''))) ?>">
                                        <td class="py-3 px-4 font-bold text-slate-900 flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center font-black text-xs flex-shrink-0">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div>
                                                <div><?= htmlspecialchars($o['nama_orangtua']) ?></div>
                                                <div class="text-[10px] text-slate-400 font-normal">Wali Santri</div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4">
                                            <span class="px-2 py-0.5 bg-purple-50 text-purple-800 border border-purple-200 rounded-md text-[10px] font-bold">Orang Tua / Wali</span>
                                        </td>
                                        <td class="py-3 px-4 font-mono font-bold text-slate-800">@<?= htmlspecialchars($o['username']) ?></td>
                                        <td class="py-3 px-4 font-mono text-slate-500 text-[11px]"><?= htmlspecialchars(substr($o['password'], 0, 16)) ?></td>
                                        <td class="py-3 px-4 font-mono text-slate-700"><?= !empty($o['no_whatsapp']) ? htmlspecialchars($o['no_whatsapp']) : '<span class="text-slate-300">-</span>' ?></td>
                                        <td class="py-3 px-4 text-center"><span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded-full text-[10px] font-bold">Aktif</span></td>
                                    </tr>
                                    <?php endwhile; } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TAB 3: AUDIT KESEHATAN DATA (HEALTH CHECK) -->
                <!-- ======================================================== -->
                <div id="tab-audit" class="data-tab-content space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- ANOMALI 1: SANTRI BELUM TERHUBUNG -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-base">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </span>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Santri Belum Terhubung ke Akun Ortu</h4>
                                    <p class="text-[11px] text-slate-500">Santri aktif yang belum dapat dipantau oleh wali santri</p>
                                </div>
                            </div>

                            <?php 
                            $unlinked_list = [];
                            foreach ($matriks_list as $m_item) {
                                if (empty($m_item['linked_ortu_ids'])) {
                                    $unlinked_list[] = $m_item;
                                }
                            }
                            ?>

                            <?php if (empty($unlinked_list)): ?>
                                <div class="p-4 bg-emerald-50 rounded-2xl text-emerald-800 text-xs font-bold text-center border border-emerald-200">
                                    <i class="fas fa-check-circle mr-1 text-emerald-600"></i> Alhamdulillah! Semua santri aktif sudah terhubung ke akun orang tua.
                                </div>
                            <?php else: ?>
                                <p class="text-xs font-bold text-amber-800 mb-2">Ditemukan <?= count($unlinked_list) ?> santri belum terhubung:</p>
                                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 no-scrollbar">
                                    <?php foreach ($unlinked_list as $u): ?>
                                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                        <div>
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($u['nama_lengkap']) ?></div>
                                            <div class="text-[10px] text-slate-500"><?= htmlspecialchars($u['kelas_sekarang'] ?? 'Santri') ?> • WA Ayah: <?= htmlspecialchars($u['no_whatsapp_ayah'] ?? '-') ?></div>
                                        </div>
                                        <button type="button" onclick="switchDataTab('tab-relasi'); filterMatriksKhusus('<?= addslashes(htmlspecialchars($u['nama_lengkap'])) ?>')" class="px-2.5 py-1 bg-[#0b8478] hover:bg-teal-700 text-white rounded-lg text-[10px] font-bold">
                                            Hubungkan
                                        </button>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ANOMALI 2: AKUN ORANG TUA TANPA SANTRI -->
                        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-xs">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-base">
                                    <i class="fas fa-user-xmark"></i>
                                </span>
                                <div>
                                    <h4 class="font-bold text-slate-900 text-sm">Akun Orang Tua Tanpa Santri</h4>
                                    <p class="text-[11px] text-slate-500">Akun orang tua yang tidak memiliki relasi ke santri mana pun</p>
                                </div>
                            </div>

                            <?php 
                            $q_ortu_alone = $conn->query("
                                SELECT o.id, o.nama_orangtua, o.username, o.no_whatsapp 
                                FROM akun_orangtua o 
                                LEFT JOIN santri_orangtua_link sol ON o.id = sol.orangtua_id 
                                WHERE sol.santri_id IS NULL 
                                ORDER BY o.nama_orangtua ASC
                            ");
                            $alone_list = [];
                            if ($q_ortu_alone) {
                                while ($ro = $q_ortu_alone->fetch_assoc()) $alone_list[] = $ro;
                            }
                            ?>

                            <?php if (empty($alone_list)): ?>
                                <div class="p-4 bg-emerald-50 rounded-2xl text-emerald-800 text-xs font-bold text-center border border-emerald-200">
                                    <i class="fas fa-check-circle mr-1 text-emerald-600"></i> Semua akun orang tua sudah memiliki anak santri terhubung.
                                </div>
                            <?php else: ?>
                                <p class="text-xs font-bold text-rose-800 mb-2">Ditemukan <?= count($alone_list) ?> akun orang tua belum terhubung ke santri:</p>
                                <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 no-scrollbar">
                                    <?php foreach ($alone_list as $al): ?>
                                    <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-xs">
                                        <div>
                                            <div class="font-bold text-slate-900"><?= htmlspecialchars($al['nama_orangtua']) ?></div>
                                            <div class="text-[10px] text-slate-500">@<?= htmlspecialchars($al['username']) ?> • WA: <?= htmlspecialchars($al['no_whatsapp'] ?? '-') ?></div>
                                        </div>
                                        <a href="admin-akun-orangtua.php?edit_id=<?= $al['id'] ?>" class="px-2.5 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-lg text-[10px] font-bold">
                                            Edit Akun
                                        </a>
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
    <!-- MODAL EDIT RELASI KELUARGA (MULTI-AKUN AYAH & IBU) -->
    <!-- ======================================================== -->
    <div id="modalEditRelasi" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-teal-100 w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-150">
            <!-- HEADER MODAL -->
            <div class="px-6 py-4 bg-gradient-to-r from-teal-50 to-emerald-50 border-b border-teal-100 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-9 h-9 rounded-xl bg-[#0b8478] text-white flex items-center justify-center text-sm shadow-sm">
                        <i class="fas fa-people-roof"></i>
                    </span>
                    <div>
                        <h3 class="font-black text-slate-900 text-sm">Hubungkan Orang Tua ke Santri</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5" id="modalSantriTitle">Memuat nama santri...</p>
                    </div>
                </div>
                <button type="button" onclick="closeModalEditRelasi()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg">
                    <i class="fas fa-times text-base"></i>
                </button>
            </div>

            <!-- FORM UPDATE RELASI -->
            <form action="" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1 no-scrollbar">
                <input type="hidden" name="action" value="update_relasi">
                <input type="hidden" name="santri_id" id="modalSantriId" value="">

                <div>
                    <label class="block text-xs font-black uppercase text-slate-700 mb-1 tracking-wider">
                        Pilih Akun Orang Tua (Bisa Centang Lebih dari 1: Ayah & Ibu)
                    </label>
                    <p class="text-[11px] text-slate-500 mb-2">Centang akun yang merupakan orang tua sah santri ini:</p>

                    <div class="border border-slate-200 rounded-2xl p-2 max-h-56 overflow-y-auto space-y-1 bg-slate-50/50" id="modalOrtuListContainer">
                        <?php if (empty($all_akun_ortu)): ?>
                            <p class="text-center text-xs text-slate-400 py-4">Belum ada akun orang tua yang terdaftar.</p>
                        <?php else: ?>
                            <?php foreach ($all_akun_ortu as $ao): ?>
                                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-slate-200/80 bg-white hover:bg-teal-50/40 cursor-pointer select-none transition">
                                    <input type="checkbox" name="ortu_ids[]" value="<?= $ao['id'] ?>" class="ortu-cb w-4 h-4 rounded text-[#0b8478] focus:ring-[#0b8478] border-slate-300">
                                    <div class="overflow-hidden flex-1">
                                        <div class="font-bold text-xs text-slate-900 leading-tight truncate"><?= htmlspecialchars($ao['nama_orangtua']) ?></div>
                                        <div class="text-[10px] text-slate-500 mt-0.5">
                                            <span>@<?= htmlspecialchars($ao['username']) ?></span>
                                            <?php if (!empty($ao['no_whatsapp'])): ?>
                                                <span class="ml-1 text-emerald-700 font-mono">• WA: <?= htmlspecialchars($ao['no_whatsapp']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-2 flex items-center justify-between border-t border-slate-100">
                    <button type="button" onclick="closeModalEditRelasi()" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-800">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-[#0b8478] hover:bg-teal-700 text-white rounded-xl text-xs font-black shadow-md transition flex items-center gap-2">
                        <i class="fas fa-save"></i>
                        <span>Simpan Keterhubungan</span>
                    </button>
                </div>
            </form>

            <!-- OPSIONAL: BUAT AKUN CEPAT JIKA BELUM ADA DI DAFTAR -->
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                <span class="text-[11px] text-slate-500">Akun orang tua belum ada di daftar?</span>
                <button type="button" onclick="toggleQuickCreate()" class="text-xs font-bold text-[#0b8478] hover:underline">
                    + Buat Akun Baru
                </button>
            </div>

            <!-- PANEL QUICK CREATE AKUN BARU -->
            <div id="quickCreatePanel" class="hidden p-6 bg-teal-50/50 border-t border-teal-100 space-y-3">
                <h4 class="font-bold text-xs text-teal-900">Buat Akun Orang Tua Baru Cepat</h4>
                <form action="" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="quick_create_ortu">
                    <input type="hidden" name="santri_id" id="quickSantriId" value="">
                    <div>
                        <input type="text" name="nama_ortu" id="quickNamaOrtu" required placeholder="Nama Lengkap Orang Tua (Bpk/Ibu...)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="username_ortu" id="quickUsernameOrtu" required placeholder="Username login" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold">
                        <input type="text" name="password_ortu" value="12345678" required placeholder="Password" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                    <div>
                        <input type="text" name="no_wa" id="quickNoWa" placeholder="No. WhatsApp (Contoh: 08123456789)" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold">
                    </div>
                    <button type="submit" class="w-full py-2 bg-teal-700 hover:bg-teal-800 text-white rounded-xl text-xs font-bold shadow-xs">
                        Simpan & Hubungkan Langsung
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPT CLIENT DATA CENTER -->
    <script>
        // Tab Switcher
        function switchDataTab(tabId) {
            document.querySelectorAll('.data-tab-content').forEach(c => c.classList.add('hidden'));
            document.querySelectorAll('.data-tab-btn').forEach(b => {
                b.classList.remove('bg-[#0b8478]', 'text-white', 'shadow-sm');
                b.classList.add('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
            });

            const activeContent = document.getElementById(tabId);
            if (activeContent) activeContent.classList.remove('hidden');

            const activeBtn = document.getElementById('btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.add('bg-[#0b8478]', 'text-white', 'shadow-sm');
                activeBtn.classList.remove('text-slate-600', 'hover:text-slate-900', 'hover:bg-slate-100');
            }
        }

        // Live Filter Tabel Matriks Relasi
        function filterMatriksTable() {
            const query = (document.getElementById('searchSantriRelasi')?.value || '').toLowerCase().trim();
            const statusFilter = document.getElementById('filterStatusRelasi')?.value || '';
            const kelasFilter = document.getElementById('filterKelasRelasi')?.value || '';

            const rows = document.querySelectorAll('.matriks-row');
            rows.forEach(r => {
                const sName = r.getAttribute('data-santri') || '';
                const oName = r.getAttribute('data-ortu') || '';
                const rKelas = r.getAttribute('data-kelas') || '';
                const rStatus = r.getAttribute('data-status') || '';

                const matchQuery = !query || sName.includes(query) || oName.includes(query);
                const matchStatus = !statusFilter || rStatus === statusFilter;
                const matchKelas = !kelasFilter || rKelas === kelasFilter;

                r.style.display = (matchQuery && matchStatus && matchKelas) ? '' : 'none';
            });
        }

        function filterMatriksKhusus(namaSantri) {
            const input = document.getElementById('searchSantriRelasi');
            if (input) {
                input.value = namaSantri;
                filterMatriksTable();
            }
        }

        // Live Filter Direktori
        function filterDirektoriTable() {
            const query = (document.getElementById('searchDirektoriInput')?.value || '').toLowerCase().trim();
            const tipe = document.getElementById('filterTipeAkun')?.value || '';

            const rows = document.querySelectorAll('.direktori-row');
            rows.forEach(r => {
                const rSearch = r.getAttribute('data-search') || '';
                const rTipe = r.getAttribute('data-tipe') || '';

                const matchQuery = !query || rSearch.includes(query);
                const matchTipe = !tipe || rTipe === tipe;

                r.style.display = (matchQuery && matchTipe) ? '' : 'none';
            });
        }

        // Modal Edit Relasi
        function openModalEditRelasi(santriId, santriName, linkedIdsStr, ayahName, ibuName, phoneNum) {
            document.getElementById('modalSantriId').value = santriId;
            document.getElementById('quickSantriId').value = santriId;
            document.getElementById('modalSantriTitle').innerText = 'Santri: ' + santriName;

            // Pre-fill quick create jika perlu
            const suggestNama = ayahName || ibuName || ('Wali ' + santriName);
            document.getElementById('quickNamaOrtu').value = suggestNama;
            document.getElementById('quickUsernameOrtu').value = suggestNama.toLowerCase().replace(/[^a-z0-9]/g, '') + '_ortu';
            document.getElementById('quickNoWa').value = phoneNum || '';

            // Checkbox check
            const linkedArr = (linkedIdsStr || '').split(',').map(s => s.trim()).filter(Boolean);
            document.querySelectorAll('.ortu-cb').forEach(cb => {
                cb.checked = linkedArr.includes(cb.value);
            });

            document.getElementById('quickCreatePanel').classList.add('hidden');
            document.getElementById('modalEditRelasi').classList.remove('hidden');
        }

        function closeModalEditRelasi() {
            document.getElementById('modalEditRelasi').classList.add('hidden');
        }

        function toggleQuickCreate() {
            const p = document.getElementById('quickCreatePanel');
            if (p) p.classList.toggle('hidden');
        }
    </script>
</body>
</html>