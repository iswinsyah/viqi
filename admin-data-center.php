<?php
/**
 * DATA CENTER & MANAJEMEN PENGGUNA TERPADU
 * SADIGS 4.0 - Villa Quran Baron Malang
 * 
 * Pengelolaan Data Center Pengguna & Fitur Khusus Jamaah (Nusuk & Saudi Travel)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/auth-unified.php';

// Validasi Login (Super Admin, Yayasan, Admin, Marketing, dll.)
$user = getCurrentUser();
if (!$user) {
    if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['yayasan_logged_in']) && !isset($_SESSION['yayasan_user']) && !isset($_SESSION['app_user_id']) && !isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }
}

// 1. SELF-HEALING TABEL data_center_users
$conn->query("CREATE TABLE IF NOT EXISTS data_center_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    nik VARCHAR(50) NULL,
    nomor_kk VARCHAR(50) NULL,
    nomor_paspor VARCHAR(50) NULL,
    nomor_nusuk VARCHAR(50) NULL,
    no_whatsapp VARCHAR(50) NOT NULL,
    email VARCHAR(150) NULL,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan') DEFAULT 'Laki-laki',
    alamat TEXT NULL,
    status ENUM('Umum', 'Santri', 'Walisantri', 'Pegawai', 'Prospek', 'Jamaah') DEFAULT 'Umum',
    
    -- 7 Berkas Tambahan Khusus Jamaah
    foto_ktp VARCHAR(255) NULL,
    foto_kk VARCHAR(255) NULL,
    foto_buku_nikah VARCHAR(255) NULL,
    evisa_saudi VARCHAR(255) NULL,
    foto_tiket_hotel VARCHAR(255) NULL,
    paspor_digital VARCHAR(255) NULL,
    kartu_nusuk VARCHAR(255) NULL,
    
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Alter kolom jika tabel sudah ada dari skema sebelumnya
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS foto_ktp VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS foto_kk VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS foto_buku_nikah VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS evisa_saudi VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS foto_tiket_hotel VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS paspor_digital VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS kartu_nusuk VARCHAR(255) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS nomor_kk VARCHAR(50) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS nomor_paspor VARCHAR(50) NULL");
@$conn->query("ALTER TABLE data_center_users ADD COLUMN IF NOT EXISTS nomor_nusuk VARCHAR(50) NULL");

// 2. FOLDER UPLOADS JAMAAH
$upload_dir = __DIR__ . '/uploads/jamaah/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Helper Upload Berkas
function uploadBerkasJamaah($inputName, $targetDir) {
    if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES[$inputName]['tmp_name'];
        $fileName = $_FILES[$inputName]['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        
        if (in_array($ext, $allowed)) {
            $newName = $inputName . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($fileTmp, $targetDir . $newName)) {
                return $newName;
            }
        }
    }
    return null;
}

$pesan_sukses = '';
$pesan_error = '';

// 3. PROSES AKSI POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // A. TAMBAH / EDIT DATA PENGGUNA
    if ($action === 'simpan_user') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = trim($conn->real_escape_string($_POST['nama_lengkap'] ?? ''));
        $nik = trim($conn->real_escape_string($_POST['nik'] ?? ''));
        $nomor_kk = trim($conn->real_escape_string($_POST['nomor_kk'] ?? ''));
        $nomor_paspor = trim($conn->real_escape_string($_POST['nomor_paspor'] ?? ''));
        $nomor_nusuk = trim($conn->real_escape_string($_POST['nomor_nusuk'] ?? ''));
        $wa = trim($conn->real_escape_string($_POST['no_whatsapp'] ?? ''));
        $email = trim($conn->real_escape_string($_POST['email'] ?? ''));
        $jk = in_array($_POST['jenis_kelamin'] ?? '', ['Laki-laki', 'Perempuan']) ? $_POST['jenis_kelamin'] : 'Laki-laki';
        $alamat = trim($conn->real_escape_string($_POST['alamat'] ?? ''));
        $status = in_array($_POST['status'] ?? '', ['Umum', 'Santri', 'Walisantri', 'Pegawai', 'Prospek', 'Jamaah']) ? $_POST['status'] : 'Umum';
        $catatan = trim($conn->real_escape_string($_POST['catatan'] ?? ''));

        if (empty($nama) || empty($wa)) {
            $pesan_error = "Nama Lengkap dan Nomor WhatsApp wajib diisi!";
        } else {
            // Upload 7 Berkas jika status = Jamaah (atau jika ada berkas yang diunggah)
            $foto_ktp = uploadBerkasJamaah('foto_ktp', $upload_dir);
            $foto_kk = uploadBerkasJamaah('foto_kk', $upload_dir);
            $foto_buku_nikah = uploadBerkasJamaah('foto_buku_nikah', $upload_dir);
            $evisa_saudi = uploadBerkasJamaah('evisa_saudi', $upload_dir);
            $foto_tiket_hotel = uploadBerkasJamaah('foto_tiket_hotel', $upload_dir);
            $paspor_digital = uploadBerkasJamaah('paspor_digital', $upload_dir);
            $kartu_nusuk = uploadBerkasJamaah('kartu_nusuk', $upload_dir);

            if ($id > 0) {
                // Update Data
                $updates = [
                    "nama_lengkap = '$nama'",
                    "nik = '$nik'",
                    "nomor_kk = '$nomor_kk'",
                    "nomor_paspor = '$nomor_paspor'",
                    "nomor_nusuk = '$nomor_nusuk'",
                    "no_whatsapp = '$wa'",
                    "email = '$email'",
                    "jenis_kelamin = '$jk'",
                    "alamat = '$alamat'",
                    "status = '$status'",
                    "catatan = '$catatan'"
                ];
                if ($foto_ktp) $updates[] = "foto_ktp = '$foto_ktp'";
                if ($foto_kk) $updates[] = "foto_kk = '$foto_kk'";
                if ($foto_buku_nikah) $updates[] = "foto_buku_nikah = '$foto_buku_nikah'";
                if ($evisa_saudi) $updates[] = "evisa_saudi = '$evisa_saudi'";
                if ($foto_tiket_hotel) $updates[] = "foto_tiket_hotel = '$foto_tiket_hotel'";
                if ($paspor_digital) $updates[] = "paspor_digital = '$paspor_digital'";
                if ($kartu_nusuk) $updates[] = "kartu_nusuk = '$kartu_nusuk'";

                $sql = "UPDATE data_center_users SET " . implode(', ', $updates) . " WHERE id = $id";
                if ($conn->query($sql)) {
                    $pesan_sukses = "Data pengguna '<strong>$nama</strong>' berhasil diperbarui.";
                } else {
                    $pesan_error = "Gagal memperbarui data: " . $conn->error;
                }
            } else {
                // Insert Baru
                $f_ktp_val = $foto_ktp ? "'$foto_ktp'" : "NULL";
                $f_kk_val = $foto_kk ? "'$foto_kk'" : "NULL";
                $f_nikah_val = $foto_buku_nikah ? "'$foto_buku_nikah'" : "NULL";
                $f_visa_val = $evisa_saudi ? "'$evisa_saudi'" : "NULL";
                $f_tiket_val = $foto_tiket_hotel ? "'$foto_tiket_hotel'" : "NULL";
                $f_paspor_val = $paspor_digital ? "'$paspor_digital'" : "NULL";
                $f_nusuk_val = $kartu_nusuk ? "'$kartu_nusuk'" : "NULL";

                $sql = "INSERT INTO data_center_users 
                    (nama_lengkap, nik, nomor_kk, nomor_paspor, nomor_nusuk, no_whatsapp, email, jenis_kelamin, alamat, status, foto_ktp, foto_kk, foto_buku_nikah, evisa_saudi, foto_tiket_hotel, paspor_digital, kartu_nusuk, catatan)
                    VALUES 
                    ('$nama', '$nik', '$nomor_kk', '$nomor_paspor', '$nomor_nusuk', '$wa', '$email', '$jk', '$alamat', '$status', $f_ktp_val, $f_kk_val, $f_nikah_val, $f_visa_val, $f_tiket_val, $f_paspor_val, $f_nusuk_val, '$catatan')";
                if ($conn->query($sql)) {
                    $pesan_sukses = "Pengguna baru '<strong>$nama</strong>' berhasil ditambahkan ke Data Center.";
                } else {
                    $pesan_error = "Gagal menyimpan pengguna baru: " . $conn->error;
                }
            }
        }
    }

    // B. UPLOAD DOKUMEN KHUSUS JAMAAH (MODAL CEPAT)
    elseif ($action === 'upload_dokumen_jamaah') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $updates = ["status = 'Jamaah'"]; // Pastikan status menjadi Jamaah
            
            if (!empty($_POST['nomor_paspor'])) {
                $p_no = $conn->real_escape_string($_POST['nomor_paspor']);
                $updates[] = "nomor_paspor = '$p_no'";
            }
            if (!empty($_POST['nomor_nusuk'])) {
                $n_no = $conn->real_escape_string($_POST['nomor_nusuk']);
                $updates[] = "nomor_nusuk = '$n_no'";
            }
            if (!empty($_POST['nomor_kk'])) {
                $kk_no = $conn->real_escape_string($_POST['nomor_kk']);
                $updates[] = "nomor_kk = '$kk_no'";
            }
            if (!empty($_POST['nik'])) {
                $nik_no = $conn->real_escape_string($_POST['nik']);
                $updates[] = "nik = '$nik_no'";
            }

            $fields_file = ['foto_ktp', 'foto_kk', 'foto_buku_nikah', 'evisa_saudi', 'foto_tiket_hotel', 'paspor_digital', 'kartu_nusuk'];
            foreach ($fields_file as $ff) {
                $f_up = uploadBerkasJamaah($ff, $upload_dir);
                if ($f_up) {
                    $updates[] = "$ff = '$f_up'";
                }
            }

            $sql = "UPDATE data_center_users SET " . implode(', ', $updates) . " WHERE id = $id";
            if ($conn->query($sql)) {
                $pesan_sukses = "Dokumen Nusuk Jamaah berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal menyimpan dokumen: " . $conn->error;
            }
        }
    }

    // C. UBAH STATUS INLINE CEPAT
    elseif ($action === 'ubah_status_cepat') {
        $id = (int)($_POST['id'] ?? 0);
        $status_baru = in_array($_POST['status'] ?? '', ['Umum', 'Santri', 'Walisantri', 'Pegawai', 'Prospek', 'Jamaah']) ? $_POST['status'] : 'Umum';
        if ($id > 0) {
            $conn->query("UPDATE data_center_users SET status = '$status_baru' WHERE id = $id");
            if ($status_baru === 'Jamaah') {
                $pesan_sukses = "Status user diubah menjadi <strong>Jamaah</strong>. Silakan upload berkas Nusuk & Saudi.";
            } else {
                $pesan_sukses = "Status berhasil diubah menjadi <strong>$status_baru</strong>.";
            }
        }
    }

    // D. HAPUS PENGGUNA
    elseif ($action === 'hapus_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM data_center_users WHERE id = $id");
            $pesan_sukses = "Data pengguna berhasil dihapus dari Data Center.";
        }
    }

    // E. SINKRONISASI PENGGUNA DARI APP_USERS & BUKU INDUK
    elseif ($action === 'sinkron_pengguna') {
        $synced = 0;
        // Dari Buku Induk Santri
        $q_bi = $conn->query("SELECT nama_lengkap, nik, no_whatsapp_ayah, no_whatsapp_ibu, alamat_lengkap FROM buku_induk_santri");
        if ($q_bi && $q_bi->num_rows > 0) {
            while ($bi = $q_bi->fetch_assoc()) {
                $nm = $conn->real_escape_string($bi['nama_lengkap']);
                $nik = $conn->real_escape_string($bi['nik'] ?? '');
                $wa = $conn->real_escape_string($bi['no_whatsapp_ayah'] ?: ($bi['no_whatsapp_ibu'] ?: '08123456789'));
                $al = $conn->real_escape_string($bi['alamat_lengkap'] ?? '');
                $chk = $conn->query("SELECT id FROM data_center_users WHERE nama_lengkap = '$nm' OR (no_whatsapp = '$wa' AND no_whatsapp != '') LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    $conn->query("INSERT INTO data_center_users (nama_lengkap, nik, no_whatsapp, alamat, status) VALUES ('$nm', '$nik', '$wa', '$al', 'Santri')");
                    $synced++;
                }
            }
        }
        // Dari Akun Ustadz / Pegawai
        $q_ust = $conn->query("SELECT nama_lengkap, no_hp FROM akun_ustadz");
        if ($q_ust && $q_ust->num_rows > 0) {
            while ($u = $q_ust->fetch_assoc()) {
                $nm = $conn->real_escape_string($u['nama_lengkap']);
                $wa = $conn->real_escape_string($u['no_hp'] ?: '08123456789');
                $chk = $conn->query("SELECT id FROM data_center_users WHERE nama_lengkap = '$nm' LIMIT 1");
                if (!$chk || $chk->num_rows === 0) {
                    $conn->query("INSERT INTO data_center_users (nama_lengkap, no_whatsapp, status) VALUES ('$nm', '$wa', 'Pegawai')");
                    $synced++;
                }
            }
        }
        $pesan_sukses = "Sinkronisasi selesai. Sebanyak $synced data pengguna baru berhasil ditambahkan.";
    }
}

// 4. FILTER & QUERY DATA CENTER
$filter_status = $_GET['status'] ?? 'semua';
$search = trim($_GET['search'] ?? '');

$where_clauses = ["1=1"];
if ($filter_status !== 'semua' && in_array($filter_status, ['Umum', 'Santri', 'Walisantri', 'Pegawai', 'Prospek', 'Jamaah'])) {
    $where_clauses[] = "status = '$filter_status'";
}
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where_clauses[] = "(nama_lengkap LIKE '%$search_esc%' OR no_whatsapp LIKE '%$search_esc%' OR nik LIKE '%$search_esc%' OR nomor_paspor LIKE '%$search_esc%' OR nomor_nusuk LIKE '%$search_esc%')";
}

$where_sql = implode(' AND ', $where_clauses);
$result = $conn->query("SELECT * FROM data_center_users WHERE $where_sql ORDER BY (status = 'Jamaah') DESC, id DESC");

// 5. STATISTIK
$stat_total = 0;
$stat_jamaah = 0;
$stat_lengkap = 0;
$stat_kurang = 0;

$q_stat = $conn->query("SELECT status, foto_ktp, foto_kk, foto_buku_nikah, evisa_saudi, foto_tiket_hotel, paspor_digital, kartu_nusuk FROM data_center_users");
if ($q_stat) {
    while ($row_s = $q_stat->fetch_assoc()) {
        $stat_total++;
        if ($row_s['status'] === 'Jamaah') {
            $stat_jamaah++;
            $docs_count = (!empty($row_s['foto_ktp']) ? 1 : 0) +
                          (!empty($row_s['foto_kk']) ? 1 : 0) +
                          (!empty($row_s['foto_buku_nikah']) ? 1 : 0) +
                          (!empty($row_s['evisa_saudi']) ? 1 : 0) +
                          (!empty($row_s['foto_tiket_hotel']) ? 1 : 0) +
                          (!empty($row_s['paspor_digital']) ? 1 : 0) +
                          (!empty($row_s['kartu_nusuk']) ? 1 : 0);
            if ($docs_count === 7) {
                $stat_lengkap++;
            } else {
                $stat_kurang++;
            }
        }
    }
}

$active_menu = 'data_center';
$user_display_name = $user['nama_lengkap'] ?? ($_SESSION['nama_lengkap'] ?? ($_SESSION['username'] ?? 'Administrator'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Data Center & Kelola Jamaah Nusuk | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden antialiased">

    <!-- SIDEBAR -->
    <?php 
    if (file_exists(__DIR__ . '/sidebar.php')) {
        include __DIR__ . '/sidebar.php';
    } elseif (file_exists(__DIR__ . '/sidebar-marketing.php')) {
        include __DIR__ . '/sidebar-marketing.php';
    }
    ?>

    <!-- MAIN CONTENT WRAPPER -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP BAR -->
        <header class="h-16 bg-white border-b border-slate-200/80 flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-slate-500 hover:text-slate-700 md:hidden p-1.5 rounded-lg focus:outline-none">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h1 class="font-extrabold text-slate-900 text-sm sm:text-base flex items-center gap-2">
                        <i class="fas fa-database text-emerald-600"></i> Menu Data Center
                    </h1>
                </div>
            </div>
            
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Short link ke Dompet Nusuk -->
                <a href="admin-dompet-nusuk.php" class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition transform hover:-translate-y-0.5">
                    <i class="fas fa-wallet text-amber-200"></i>
                    <span>Menu Dompet Nusuk</span>
                    <span class="bg-amber-900/40 text-amber-100 text-[10px] px-1.5 py-0.2 rounded-full"><?= $stat_jamaah ?></span>
                </a>
                
                <a href="dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                    <i class="fas fa-house text-slate-500"></i> <span class="hidden sm:inline">Beranda</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE AREA -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 bg-slate-50">
            
            <!-- NOTIFIKASI -->
            <?php if (!empty($pesan_sukses)): ?>
                <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-xs animate-fadeIn">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
                        <span><?= $pesan_sukses ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
                <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center justify-between shadow-xs">
                    <div class="flex items-center gap-2.5">
                        <i class="fas fa-exclamation-triangle text-rose-600 text-lg"></i>
                        <span><?= $pesan_error ?></span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                <!-- Total Pengguna Data Center -->
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Data Center</p>
                        <h3 class="text-2xl font-black text-slate-900 mt-0.5"><?= number_format($stat_total) ?></h3>
                        <p class="text-[10px] text-slate-500 mt-0.5">Semua pengguna terdata</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 text-xl flex-shrink-0">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <!-- Total Jamaah -->
                <div class="bg-white p-4 rounded-2xl border border-amber-200/90 bg-gradient-to-br from-white to-amber-50/30 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Jamaah Terdaftar</p>
                        <h3 class="text-2xl font-black text-amber-600 mt-0.5"><?= number_format($stat_jamaah) ?></h3>
                        <a href="admin-dompet-nusuk.php" class="text-[10px] text-amber-700 hover:underline font-bold mt-0.5 inline-block">
                            Lihat di Dompet Nusuk &rarr;
                        </a>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center text-amber-600 text-xl flex-shrink-0">
                        <i class="fas fa-kaaba"></i>
                    </div>
                </div>

                <!-- Berkas Lengkap (7/7) -->
                <div class="bg-white p-4 rounded-2xl border border-emerald-200/90 bg-gradient-to-br from-white to-emerald-50/30 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Berkas 7/7 Lengkap</p>
                        <h3 class="text-2xl font-black text-emerald-600 mt-0.5"><?= number_format($stat_lengkap) ?></h3>
                        <p class="text-[10px] text-emerald-600 font-medium mt-0.5">Siap diterbitkan tiket & visa</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/15 flex items-center justify-center text-emerald-600 text-xl flex-shrink-0">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                </div>

                <!-- Berkas Belum Lengkap -->
                <div class="bg-white p-4 rounded-2xl border border-rose-200/90 bg-gradient-to-br from-white to-rose-50/30 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-[11px] font-bold text-rose-700 uppercase tracking-wider">Berkas Belum Lengkap</p>
                        <h3 class="text-2xl font-black text-rose-600 mt-0.5"><?= number_format($stat_kurang) ?></h3>
                        <p class="text-[10px] text-rose-600 font-medium mt-0.5">Perlu upload data tambahan</p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-rose-500/15 flex items-center justify-center text-rose-600 text-xl flex-shrink-0">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR & FILTER -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs mb-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                    
                    <!-- Search & Filter Status -->
                    <form method="GET" class="flex flex-wrap items-center gap-2.5 flex-1">
                        <div class="relative flex-1 min-w-[200px] max-w-md">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, WA, NIK, No Paspor..." class="w-full pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                        </div>

                        <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-slate-700 text-xs rounded-xl px-3 py-2 font-bold focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                            <option value="semua" <?= $filter_status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                            <option value="Jamaah" <?= $filter_status === 'Jamaah' ? 'selected' : '' ?>>🕋 Khusus Jamaah</option>
                            <option value="Santri" <?= $filter_status === 'Santri' ? 'selected' : '' ?>>Santri</option>
                            <option value="Walisantri" <?= $filter_status === 'Walisantri' ? 'selected' : '' ?>>Wali Santri</option>
                            <option value="Pegawai" <?= $filter_status === 'Pegawai' ? 'selected' : '' ?>>Pegawai / Asatidz</option>
                            <option value="Umum" <?= $filter_status === 'Umum' ? 'selected' : '' ?>>Umum</option>
                            <option value="Prospek" <?= $filter_status === 'Prospek' ? 'selected' : '' ?>>Prospek</option>
                        </select>

                        <button type="submit" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                            Filter
                        </button>

                        <?php if (!empty($search) || $filter_status !== 'semua'): ?>
                            <a href="admin-data-center.php" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition">
                                Reset
                            </a>
                        <?php endif; ?>
                    </form>

                    <!-- Tombol Aksi Kanan -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <!-- Sinkronisasi Data -->
                        <form method="POST" onsubmit="return confirm('Sinkronkan data dari akun santri dan pegawai terdaftar?');">
                            <input type="hidden" name="action" value="sinkron_pengguna">
                            <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5" title="Tarik data santri dan pegawai yang belum tercatat">
                                <i class="fas fa-sync text-slate-500"></i> <span class="hidden sm:inline">Sinkron Data</span>
                            </button>
                        </form>

                        <!-- Tambah User Baru -->
                        <button onclick="bukaModalUser()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition flex items-center gap-2">
                            <i class="fas fa-user-plus"></i>
                            <span>Tambah Data Pengguna</span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- TABEL DATA CENTER -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-extrabold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Info Pengguna</th>
                                <th class="py-3.5 px-4">Kontak & NIK</th>
                                <th class="py-3.5 px-4 text-center">Status User</th>
                                <th class="py-3.5 px-4">Dokumen Tambahan (Nusuk & Umrah)</th>
                                <th class="py-3.5 px-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php 
                            if ($result && $result->num_rows > 0): 
                                $no = 1;
                                while ($row = $result->fetch_assoc()):
                                    $is_jamaah = ($row['status'] === 'Jamaah');
                                    
                                    // Hitung kelengkapan dokumen
                                    $docs = [
                                        'ktp' => !empty($row['foto_ktp']),
                                        'kk'  => !empty($row['foto_kk']),
                                        'nikah' => !empty($row['foto_buku_nikah']),
                                        'visa'  => !empty($row['evisa_saudi']),
                                        'tiket' => !empty($row['foto_tiket_hotel']),
                                        'paspor' => !empty($row['paspor_digital']),
                                        'nusuk'  => !empty($row['kartu_nusuk'])
                                    ];
                                    $completed_count = count(array_filter($docs));
                            ?>
                            <tr class="hover:bg-slate-50/70 transition <?= $is_jamaah ? 'bg-amber-50/20' : '' ?>">
                                <!-- No -->
                                <td class="py-3.5 px-4 text-center font-bold text-slate-400">
                                    <?= $no++ ?>
                                </td>

                                <!-- Info Pengguna -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full <?= $is_jamaah ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600' ?> flex items-center justify-center font-black text-xs flex-shrink-0 shadow-xs">
                                            <?= strtoupper(substr($row['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-extrabold text-slate-900 text-sm flex items-center gap-1.5">
                                                <span><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                                <?php if ($is_jamaah): ?>
                                                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full bg-amber-100 text-amber-800 text-[9px] font-black border border-amber-300">
                                                        <i class="fas fa-kaaba text-[8px]"></i> Jamaah
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                <?= $row['jenis_kelamin'] ?: '-' ?> 
                                                <?php if (!empty($row['alamat'])): ?>
                                                    &bull; <span class="truncate max-w-[180px] inline-block align-bottom"><?= htmlspecialchars($row['alamat']) ?></span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Kontak & NIK -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-0.5">
                                        <a href="https://api.whatsapp.com/send?phone=<?= preg_replace('/[^0-9]/', '', (substr($row['no_whatsapp'], 0, 1) === '0' ? '62'.substr($row['no_whatsapp'], 1) : $row['no_whatsapp'])) ?>" target="_blank" class="inline-flex items-center gap-1.5 text-emerald-600 hover:text-emerald-700 font-bold hover:underline">
                                            <i class="fab fa-whatsapp"></i> <?= htmlspecialchars($row['no_whatsapp']) ?>
                                        </a>
                                        <?php if (!empty($row['nik'])): ?>
                                            <p class="text-[11px] text-slate-500 font-mono">NIK: <?= htmlspecialchars($row['nik']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($row['email'])): ?>
                                            <p class="text-[10px] text-slate-400"><?= htmlspecialchars($row['email']) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Status User (Dropdown Cepat) -->
                                <td class="py-3.5 px-4 text-center">
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="action" value="ubah_status_cepat">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <select name="status" onchange="cekUbahStatus(this, <?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>')" class="text-[11px] font-bold rounded-xl px-2.5 py-1 border transition cursor-pointer focus:outline-none <?= $is_jamaah ? 'bg-amber-100 text-amber-900 border-amber-300 font-black' : 'bg-slate-100 text-slate-700 border-slate-200' ?>">
                                            <option value="Umum" <?= $row['status'] === 'Umum' ? 'selected' : '' ?>>Umum</option>
                                            <option value="Santri" <?= $row['status'] === 'Santri' ? 'selected' : '' ?>>Santri</option>
                                            <option value="Walisantri" <?= $row['status'] === 'Walisantri' ? 'selected' : '' ?>>Wali Santri</option>
                                            <option value="Pegawai" <?= $row['status'] === 'Pegawai' ? 'selected' : '' ?>>Pegawai</option>
                                            <option value="Prospek" <?= $row['status'] === 'Prospek' ? 'selected' : '' ?>>Prospek</option>
                                            <option value="Jamaah" <?= $row['status'] === 'Jamaah' ? 'selected' : '' ?>>🕋 Jamaah</option>
                                        </select>
                                    </form>
                                </td>

                                <!-- Dokumen Tambahan Jamaah -->
                                <td class="py-3.5 px-4">
                                    <?php if ($is_jamaah): ?>
                                        <div class="space-y-1.5">
                                            <!-- Badge Kelengkapan -->
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold <?= $completed_count === 7 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' ?>">
                                                    <i class="fas <?= $completed_count === 7 ? 'fa-check-circle' : 'fa-clock' ?>"></i>
                                                    <span><?= $completed_count ?>/7 Dokumen</span>
                                                </span>
                                                <button onclick="bukaModalUploadDokumen(<?= htmlspecialchars(json_encode($row)) ?>)" class="text-[10px] font-extrabold text-blue-600 hover:text-blue-800 hover:underline">
                                                    <i class="fas fa-upload mr-0.5"></i> Upload / Edit
                                                </button>
                                            </div>

                                            <!-- Indikator 7 Berkas Ringkas -->
                                            <div class="flex items-center gap-1 text-[10px]">
                                                <span title="Foto KTP" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['ktp'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">K</span>
                                                <span title="Kartu Keluarga" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['kk'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">KK</span>
                                                <span title="Buku Nikah" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['nikah'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">N</span>
                                                <span title="e-Visa Saudi" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['visa'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">V</span>
                                                <span title="Tiket & Hotel" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['tiket'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">T</span>
                                                <span title="Paspor Digital" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['paspor'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">P</span>
                                                <span title="Kartu Nusuk" class="w-5 h-5 rounded flex items-center justify-center font-bold <?= $docs['nusuk'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-300' ?>">NS</span>
                                            </div>

                                            <!-- Tombol Buka Dompet Nusuk Jamaah Ini -->
                                            <a href="admin-dompet-nusuk.php?jamaah_id=<?= $row['id'] ?>" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-700 hover:text-amber-800 hover:underline">
                                                <i class="fas fa-wallet text-[10px]"></i> Buka Dompet Nusuk &rarr;
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-slate-400 text-[11px] italic">
                                            <span>Bukan Jamaah (Tidak memerlukan berkas Nusuk)</span>
                                            <button onclick="ubahKeJamaah(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>')" class="block mt-1 text-[10px] text-emerald-600 hover:underline font-bold not-italic">
                                                + Ubah Jadi Jamaah & Upload
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <!-- Edit Data -->
                                        <button onclick="editUser(<?= htmlspecialchars(json_encode($row)) ?>)" class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" title="Edit Data Pengguna">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Hapus Data -->
                                        <form method="POST" onsubmit="return confirm('Hapus data pengguna ini dari Data Center?');" class="inline">
                                            <input type="hidden" name="action" value="hapus_user">
                                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition" title="Hapus Pengguna">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            else: 
                            ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-3">
                                        <i class="fas fa-database"></i>
                                    </div>
                                    <h4 class="font-bold text-slate-700 text-sm">Tidak ada data pengguna ditemukan</h4>
                                    <p class="text-xs text-slate-400 mt-1">Coba gunakan filter lain atau klik tombol "Tambah Data Pengguna".</p>
                                    <button onclick="bukaModalUser()" class="mt-4 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                                        + Tambah Data Pengguna Sekarang
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 1: FORM TAMBAH / EDIT PENGGUNA DATA CENTER           -->
    <!-- ========================================================= -->
    <div id="modalUser" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8 max-h-[90vh] flex flex-col">
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-sm">
                        <i class="fas fa-database"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="font-extrabold text-slate-900 text-base leading-tight">Tambah Data Pengguna</h3>
                        <p class="text-xs text-slate-500">Pusat master data pengguna SADIGS 4.0</p>
                    </div>
                </div>
                <button onclick="tutupModalUser()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <form id="formUser" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto flex-1 space-y-4">
                <input type="hidden" name="action" value="simpan_user">
                <input type="hidden" name="id" id="user_id" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Nama Lengkap -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                        <input type="text" name="nama_lengkap" id="u_nama" required placeholder="Contoh: Bpk. H. Ahmad Fauzi" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                    </div>

                    <!-- No WhatsApp -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">No. WhatsApp <span class="text-rose-500">*</span></label>
                        <input type="text" name="no_whatsapp" id="u_wa" required placeholder="Contoh: 081234567890" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                    </div>

                    <!-- NIK KTP -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nomor Induk Kependudukan (NIK)</label>
                        <input type="text" name="nik" id="u_nik" placeholder="16 Digit NIK KTP" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                    </div>

                    <!-- Jenis Kelamin -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="u_jk" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                            <option value="Laki-laki">Laki-laki</option>
                            <option value="Perempuan">Perempuan</option>
                        </select>
                    </div>

                    <!-- STATUS PENGGUNA (DYNAMIC TRIGGER) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Status Pengguna <span class="text-rose-500">*</span></label>
                        <select name="status" id="u_status" onchange="toggleJamaahFields(this.value)" class="w-full px-3.5 py-2 bg-amber-50/80 border border-amber-300 text-amber-900 font-extrabold rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="Umum">Umum</option>
                            <option value="Santri">Santri</option>
                            <option value="Walisantri">Wali Santri</option>
                            <option value="Pegawai">Pegawai / Asatidz</option>
                            <option value="Prospek">Prospek</option>
                            <option value="Jamaah">🕋 Jamaah (Wajib Upload Dokumen Nusuk)</option>
                        </select>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email</label>
                        <input type="email" name="email" id="u_email" placeholder="contoh@gmail.com" class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                    </div>

                    <!-- Alamat -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Asal / Domisili</label>
                        <input type="text" name="alamat" id="u_alamat" placeholder="Kota / Alamat lengkap..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition">
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- KOLOM DOKUMEN TAMBAHAN KHUSUS JAMAAH (MUNCUL OTOMATIS)    -->
                <!-- ========================================================= -->
                <div id="kolom_dokumen_jamaah" class="hidden pt-4 border-t border-amber-200/80 space-y-4">
                    <div class="p-3.5 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent rounded-xl border border-amber-300/80 flex items-start gap-2.5">
                        <i class="fas fa-kaaba text-amber-600 text-lg mt-0.5"></i>
                        <div>
                            <h4 class="font-extrabold text-amber-900 text-xs">Kolom Data Tambahan Khusus Jamaah (Nusuk & Saudi Travel)</h4>
                            <p class="text-[11px] text-amber-800 leading-relaxed mt-0.5">
                                User ini berstatus <strong>Jamaah</strong>. Lengkapi 7 berkas persyaratan resmi di bawah ini untuk ditampilkan di <strong>Menu Dompet Nusuk</strong>:
                            </p>
                        </div>
                    </div>

                    <!-- Data Pendukung Jamaah -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Paspor</label>
                            <input type="text" name="nomor_paspor" id="u_paspor" placeholder="Contoh: A 1234567" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Nusuk ID</label>
                            <input type="text" name="nomor_nusuk" id="u_nusuk" placeholder="ID Akun Nusuk Saudi" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Kartu Keluarga (KK)</label>
                            <input type="text" name="nomor_kk" id="u_kk" placeholder="16 Digit No. KK" class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                        </div>
                    </div>

                    <!-- Grid 7 Dokumen Upload -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- a. Foto KTP -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-id-card text-emerald-600 mr-1"></i> a. Foto KTP
                            </label>
                            <input type="file" name="foto_ktp" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                            <span id="preview_ktp" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>

                        <!-- b. Foto Kartu Keluarga -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-users text-blue-600 mr-1"></i> b. Foto Kartu Keluarga (KK)
                            </label>
                            <input type="file" name="foto_kk" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <span id="preview_kk" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>

                        <!-- c. Foto Buku Nikah -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-ring text-pink-600 mr-1"></i> c. Foto Buku Nikah
                            </label>
                            <input type="file" name="foto_buku_nikah" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-pink-50 file:text-pink-700 hover:file:bg-pink-100">
                            <span id="preview_nikah" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>

                        <!-- d. e-Visa Saudi -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-passport text-amber-600 mr-1"></i> d. e-Visa Saudi
                            </label>
                            <input type="file" name="evisa_saudi" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                            <span id="preview_visa" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>

                        <!-- f. Foto Ticket & Hotel -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-plane-departure text-indigo-600 mr-1"></i> f. Foto Ticket & Hotel
                            </label>
                            <input type="file" name="foto_tiket_hotel" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                            <span id="preview_tiket" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>

                        <!-- g. Paspor Digital -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-book-atlas text-teal-600 mr-1"></i> g. Paspor Digital
                            </label>
                            <input type="file" name="paspor_digital" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                            <span id="preview_paspor" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>

                        <!-- h. Kartu Nusuk -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 sm:col-span-2">
                            <label class="block text-[11px] font-extrabold text-slate-800">
                                <i class="fas fa-kaaba text-amber-600 mr-1"></i> h. Kartu Nusuk
                            </label>
                            <input type="file" name="kartu_nusuk" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[10px] file:font-bold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                            <span id="preview_nusuk" class="text-[10px] text-slate-400 block truncate"></span>
                        </div>
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea name="catatan" id="u_catatan" rows="2" placeholder="Informasi tambahan pengguna / jamaah..." class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 transition"></textarea>
                </div>

                <!-- Tombol Submit Modal -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="tutupModalUser()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition">
                        <i class="fas fa-save mr-1"></i> Simpan Data Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- MODAL 2: UPLOAD KHUSUS DOKUMEN JAMAAH (7 BERKAS NUSUK)    -->
    <!-- ========================================================= -->
    <div id="modalDokumen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden my-8 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-amber-200/80 bg-amber-50/70 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-black text-sm">
                        <i class="fas fa-kaaba"></i>
                    </div>
                    <div>
                        <h3 class="font-extrabold text-amber-950 text-base leading-tight">Kelola Dokumen Nusuk Jamaah</h3>
                        <p id="doc_nama_display" class="text-xs text-amber-800 font-bold">Nama Jamaah</p>
                    </div>
                </div>
                <button onclick="tutupModalDokumen()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto flex-1 space-y-4">
                <input type="hidden" name="action" value="upload_dokumen_jamaah">
                <input type="hidden" name="id" id="doc_user_id" value="0">

                <div class="grid grid-cols-2 gap-3 mb-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Paspor</label>
                        <input type="text" name="nomor_paspor" id="doc_paspor" placeholder="No. Paspor..." class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-700 mb-1">Nomor Nusuk ID</label>
                        <input type="text" name="nomor_nusuk" id="doc_nusuk" placeholder="Nusuk ID..." class="w-full px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-mono">
                    </div>
                </div>

                <div class="space-y-3">
                    <!-- a. KTP -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-id-card text-emerald-600 mr-1"></i> a. Foto KTP</label>
                            <input type="file" name="foto_ktp" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-emerald-50 file:text-emerald-700">
                        </div>
                        <div id="status_file_ktp" class="flex-shrink-0"></div>
                    </div>

                    <!-- b. KK -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-users text-blue-600 mr-1"></i> b. Foto Kartu Keluarga</label>
                            <input type="file" name="foto_kk" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-blue-50 file:text-blue-700">
                        </div>
                        <div id="status_file_kk" class="flex-shrink-0"></div>
                    </div>

                    <!-- c. Buku Nikah -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-ring text-pink-600 mr-1"></i> c. Foto Buku Nikah</label>
                            <input type="file" name="foto_buku_nikah" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-pink-50 file:text-pink-700">
                        </div>
                        <div id="status_file_nikah" class="flex-shrink-0"></div>
                    </div>

                    <!-- d. e-Visa Saudi -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-passport text-amber-600 mr-1"></i> d. e-Visa Saudi</label>
                            <input type="file" name="evisa_saudi" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-50 file:text-amber-700">
                        </div>
                        <div id="status_file_visa" class="flex-shrink-0"></div>
                    </div>

                    <!-- f. Ticket & Hotel -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-plane-departure text-indigo-600 mr-1"></i> f. Foto Ticket & Hotel</label>
                            <input type="file" name="foto_tiket_hotel" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700">
                        </div>
                        <div id="status_file_tiket" class="flex-shrink-0"></div>
                    </div>

                    <!-- g. Paspor Digital -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-book-atlas text-teal-600 mr-1"></i> g. Paspor Digital</label>
                            <input type="file" name="paspor_digital" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-teal-50 file:text-teal-700">
                        </div>
                        <div id="status_file_paspor" class="flex-shrink-0"></div>
                    </div>

                    <!-- h. Kartu Nusuk -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-bold text-slate-800 mb-0.5"><i class="fas fa-kaaba text-amber-600 mr-1"></i> h. Kartu Nusuk</label>
                            <input type="file" name="kartu_nusuk" accept="image/*,application/pdf" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-[10px] file:font-bold file:bg-amber-50 file:text-amber-700">
                        </div>
                        <div id="status_file_nusuk" class="flex-shrink-0"></div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="tutupModalDokumen()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition">
                        Tutup
                    </button>
                    <button type="submit" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition">
                        <i class="fas fa-cloud-upload-alt mr-1"></i> Simpan & Upload Berkas
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        // Buka Modal Tambah User
        function bukaModalUser() {
            document.getElementById('formUser').reset();
            document.getElementById('user_id').value = '0';
            document.getElementById('modalTitle').textContent = 'Tambah Data Pengguna Baru';
            document.getElementById('u_status').value = 'Umum';
            toggleJamaahFields('Umum');
            document.getElementById('modalUser').classList.remove('hidden');
        }

        function tutupModalUser() {
            document.getElementById('modalUser').classList.add('hidden');
        }

        // Edit User
        function editUser(data) {
            document.getElementById('user_id').value = data.id || '0';
            document.getElementById('modalTitle').textContent = 'Edit Data: ' + (data.nama_lengkap || '');
            document.getElementById('u_nama').value = data.nama_lengkap || '';
            document.getElementById('u_wa').value = data.no_whatsapp || '';
            document.getElementById('u_nik').value = data.nik || '';
            document.getElementById('u_jk').value = data.jenis_kelamin || 'Laki-laki';
            document.getElementById('u_status').value = data.status || 'Umum';
            document.getElementById('u_email').value = data.email || '';
            document.getElementById('u_alamat').value = data.alamat || '';
            document.getElementById('u_catatan').value = data.catatan || '';

            document.getElementById('u_paspor').value = data.nomor_paspor || '';
            document.getElementById('u_nusuk').value = data.nomor_nusuk || '';
            document.getElementById('u_kk').value = data.nomor_kk || '';

            // Tampilkan preview label jika berkas sudah ada
            setFilePreview('preview_ktp', data.foto_ktp);
            setFilePreview('preview_kk', data.foto_kk);
            setFilePreview('preview_nikah', data.foto_buku_nikah);
            setFilePreview('preview_visa', data.evisa_saudi);
            setFilePreview('preview_tiket', data.foto_tiket_hotel);
            setFilePreview('preview_paspor', data.paspor_digital);
            setFilePreview('preview_nusuk', data.kartu_nusuk);

            toggleJamaahFields(data.status);
            document.getElementById('modalUser').classList.remove('hidden');
        }

        function setFilePreview(elemId, fileVal) {
            const el = document.getElementById(elemId);
            if (!el) return;
            if (fileVal) {
                el.innerHTML = `<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle"></i> Berkas tersedia:</span> <a href="uploads/jamaah/${fileVal}" target="_blank" class="underline text-blue-600">${fileVal}</a>`;
            } else {
                el.innerHTML = `<span class="text-slate-400">Belum ada berkas terunggah.</span>`;
            }
        }

        // TOGGLE DOKUMEN TAMBAHAN JAMAAH (KETIKA STATUS = JAMAAH)
        function toggleJamaahFields(statusValue) {
            const kolom = document.getElementById('kolom_dokumen_jamaah');
            if (!kolom) return;
            if (statusValue === 'Jamaah') {
                kolom.classList.remove('hidden');
            } else {
                kolom.classList.add('hidden');
            }
        }

        // Cek jika status diubah dari select tabel
        function cekUbahStatus(selectElem, id, nama) {
            if (selectElem.value === 'Jamaah') {
                if (confirm(`Ubah status ${nama} menjadi Jamaah? Anda akan diarahkan untuk mengunggah dokumen tambahan (KTP, KK, Visa, Paspor, Nusuk, dll).`)) {
                    selectElem.form.submit();
                } else {
                    window.location.reload();
                }
            } else {
                selectElem.form.submit();
            }
        }

        // Cepat ubah ke Jamaah dari baris tabel
        function ubahKeJamaah(id, nama) {
            const rowData = { id: id, nama_lengkap: nama, status: 'Jamaah' };
            bukaModalUploadDokumen(rowData);
        }

        // Modal Khusus Upload Dokumen Jamaah
        function bukaModalUploadDokumen(data) {
            document.getElementById('doc_user_id').value = data.id || '0';
            document.getElementById('doc_nama_display').textContent = data.nama_lengkap || 'Jamaah';
            document.getElementById('doc_paspor').value = data.nomor_paspor || '';
            document.getElementById('doc_nusuk').value = data.nomor_nusuk || '';

            renderDocBadge('status_file_ktp', data.foto_ktp);
            renderDocBadge('status_file_kk', data.foto_kk);
            renderDocBadge('status_file_nikah', data.foto_buku_nikah);
            renderDocBadge('status_file_visa', data.evisa_saudi);
            renderDocBadge('status_file_tiket', data.foto_tiket_hotel);
            renderDocBadge('status_file_paspor', data.paspor_digital);
            renderDocBadge('status_file_nusuk', data.kartu_nusuk);

            document.getElementById('modalDokumen').classList.remove('hidden');
        }

        function renderDocBadge(elemId, fileVal) {
            const el = document.getElementById(elemId);
            if (!el) return;
            if (fileVal) {
                el.innerHTML = `<a href="uploads/jamaah/${fileVal}" target="_blank" class="px-2 py-1 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold hover:underline inline-flex items-center gap-1"><i class="fas fa-check"></i> Lihat</a>`;
            } else {
                el.innerHTML = `<span class="px-2 py-1 rounded bg-slate-100 text-slate-400 text-[10px] font-bold">Kosong</span>`;
            }
        }

        function tutupModalDokumen() {
            document.getElementById('modalDokumen').classList.add('hidden');
        }

        // Toggle Sidebar Mobile
        document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.getElementById('open-sidebar');
            const closeBtn = document.getElementById('close-sidebar');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');

            if (openBtn && sidebar) {
                openBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    if (overlay) overlay.classList.toggle('hidden');
                });
            }
            if (closeBtn && sidebar) {
                closeBtn.addEventListener('click', function() {
                    sidebar.classList.add('hidden');
                    if (overlay) overlay.classList.add('hidden');
                });
            }
            if (overlay) {
                overlay.addEventListener('click', function() {
                    if (sidebar) sidebar.classList.add('hidden');
                    overlay.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>

