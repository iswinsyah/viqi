<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
require_once 'pkbm_modul_catalog.php';

$active_menu = 'manajemen_elearning';
$pesan_sukses = '';
$pesan_error = '';

$ustadz_id = (int)($_SESSION['ustadz_id'] ?? 0);
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

$norm_user_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);
$is_super_admin = in_array('super_admin', $norm_user_roles) || in_array('kepala_sekolah', $norm_user_roles) || in_array('admin_sekolah', $norm_user_roles) || $ustadz_id === 9999;

// ==========================================
// 1. SELF-HEALING DATABASE MIGRATIONS
// ==========================================
$conn->query("CREATE TABLE IF NOT EXISTS elearning_bab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mapel_id INT NULL,
    mapel_nama VARCHAR(100) NOT NULL,
    nomor_bab INT NOT NULL DEFAULT 1,
    judul_bab VARCHAR(255) NOT NULL,
    subjudul VARCHAR(255) NULL,
    durasi_menit VARCHAR(50) DEFAULT '15 Menit',
    pdf_url TEXT NULL,
    video_url TEXT NULL,
    ringkasan_materi LONGTEXT NULL,
    lks_judul VARCHAR(255) NULL,
    lks_tugas TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS elearning_kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bab_id INT NOT NULL,
    soal TEXT NOT NULL,
    opsi_a TEXT NOT NULL,
    opsi_b TEXT NOT NULL,
    opsi_c TEXT NOT NULL,
    opsi_d TEXT NOT NULL,
    kunci_jawaban ENUM('A', 'B', 'C', 'D') NOT NULL DEFAULT 'A',
    pembahasan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bab_id) REFERENCES elearning_bab(id) ON DELETE CASCADE
)");

// ==========================================
// 2. AJAX FAST ACTION HANDLERS
// ==========================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_single_pdf_url') {
        header('Content-Type: application/json');
        $bab_id = (int)($_POST['bab_id'] ?? 0);
        $pdf_url = trim($_POST['pdf_url'] ?? '');
        if ($bab_id > 0) {
            $pdf_url_esc = $conn->real_escape_string($pdf_url);
            $res_up = $conn->query("UPDATE elearning_bab SET pdf_url = '$pdf_url_esc' WHERE id = $bab_id");
            if ($res_up) {
                echo json_encode(['status' => 'success', 'message' => 'URL Modul berhasil disimpan!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $conn->error]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Bab ID tidak valid']);
        }
        exit;
    }

    if ($_POST['action'] === 'apply_pkbm_catalog') {
        header('Content-Type: application/json');
        $mapel_target = trim($_POST['mapel_nama'] ?? '');
        $mapel_esc = $conn->real_escape_string($mapel_target);
        
        $res_b = $conn->query("SELECT id, nomor_bab FROM elearning_bab WHERE mapel_nama = '$mapel_esc'");
        $updated_count = 0;
        if ($res_b && $res_b->num_rows > 0) {
            while ($b = $res_b->fetch_assoc()) {
                $catalog_url = getPkbmModulPdfUrl($mapel_target, $b['nomor_bab']);
                if (!empty($catalog_url)) {
                    $cat_esc = $conn->real_escape_string($catalog_url);
                    $conn->query("UPDATE elearning_bab SET pdf_url = '$cat_esc' WHERE id = " . $b['id']);
                    $updated_count++;
                }
            }
        }
        echo json_encode(['status' => 'success', 'message' => "Berhasil menerapkan $updated_count URL resmi dari katalog PKBM!"]);
        exit;
    }
}

// Seed initial Sosiologi data if empty
$chk_sosiologi = $conn->query("SELECT id FROM elearning_bab WHERE mapel_nama = 'Sosiologi' LIMIT 1");
if ($chk_sosiologi && $chk_sosiologi->num_rows === 0) {
    $b1_ringkasan = json_encode([
        'Pengertian Dasar' => 'Sosiologi berasal dari bahasa Latin <i>Socius</i> (kawan/masyarakat) dan bahasa Yunani <i>Logos</i> (ilmu/bicara). Pertama kali dicetuskan oleh <b>Auguste Comte</b> (Bapak Sosiologi Dunia).',
        '4 Ciri Utama Sosiologi' => [
            '<b>Empiris:</b> Didasarkan pada observasi kenyataan dan akal sehat, bukan spekulasi.',
            '<b>Teoritis:</b> Menyusun abstraksi dari hasil pengamatan untuk menjelaskan hubungan sebab-akibat.',
            '<b>Kumulatif:</b> Teori dibangun atas dasar teori yang sudah ada sebelumnya, kemudian diperbaiki dan diperluas.',
            '<b>Non-Etis:</b> Tidak mempersoalkan baik atau buruknya suatu fakta sosial, melainkan menjelaskan fakta tersebut secara analitis dan objektif.'
        ],
        'Objek Kajian' => 'Masyarakat yang mencakup hubungan antarmanusia, proses interaksi sosial, serta gejala dan perubahan sosial dalam kehidupan bersama.'
    ]);
    
    $sql_b1 = "INSERT INTO elearning_bab (mapel_nama, nomor_bab, judul_bab, subjudul, durasi_menit, pdf_url, video_url, ringkasan_materi, lks_judul, lks_tugas) 
               VALUES ('Sosiologi', 1, 'Bab 1: Sosiologi Sebagai Ilmu & Objek Kajian', 'Hakikat, Objek, dan Ciri-Ciri Utama Ilmu Sosiologi', '15 Menit', 
               'https://modul.pkbm.id/paket-c/Modul%201%20Sosiologi.pdf', 
               'https://www.youtube.com/embed/5v6kS6uHkPQ', 
               '" . $conn->real_escape_string($b1_ringkasan) . "', 
               'LKS 1: Analisis Gejala Sosial di Lingkungan Pondok/Sekolah', 
               'Amati satu fenomena sosial di sekitarmu (misal: tradisi gotong royong/ro\'an atau interaksi santri baru). Jelaskan mengapa fenomena tersebut memenuhi ciri Empiris dan Non-Etis dalam Sosiologi!')";
    $conn->query($sql_b1);
    $b1_id = $conn->insert_id;

    if ($b1_id) {
        $conn->query("INSERT INTO elearning_kuis (bab_id, soal, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, pembahasan) VALUES 
        ($b1_id, 'Siapakah tokoh yang pertama kali memperkenalkan istilah Sosiologi dan dikenal sebagai Bapak Sosiologi Dunia?', 'Emile Durkheim', 'Auguste Comte', 'Max Weber', 'Karl Marx', 'B', 'Auguste Comte adalah tokoh asal Prancis yang pertama kali menggunakan istilah Sosiologi.'),
        ($b1_id, 'Sosiologi tidak menilai apakah suatu tindakan secara moral baik atau buruk, melainkan menjelaskan penyebab sosialnya. Karakteristik ini adalah...', 'Empiris', 'Teoritis', 'Kumulatif', 'Non-Etis', 'D', 'Ciri Non-Etis mengkaji fakta apa adanya tanpa menghakimi moral baik atau buruk.'),
        ($b1_id, 'Teori sosiologi saat ini menyempurnakan teori terdahulu sesuai perkembangan zaman modern. Karakteristik ini disebut...', 'Kumulatif', 'Spekulatif', 'Normatif', 'Empiris', 'A', 'Kumulatif berarti teori sosiologi saling melengkapi dan memperluas teori sebelumnya.')");
    }

    $b2_ringkasan = json_encode([
        '2 Syarat Interaksi Sosial' => [
            '<b>Kontak Sosial:</b> Hubungan awal antar individu/kelompok (Primer: tatap muka; Sekunder: perantara HP/surat).',
            '<b>Komunikasi:</b> Proses penyampaian pesan disertai penafsiran makna.'
        ],
        'Bentuk Interaksi Asosiatif (Menyatukan)' => [
            '<b>Kerjasama (Cooperation):</b> Usaha bersama mencapai tujuan.',
            '<b>Akomodasi:</b> Upaya meredakan konflik (Mediasi, Kompromi, Arbitrase).',
            '<b>Asimilasi:</b> Peleburan kebudayaan menjadi kebudayaan baru.',
            '<b>Akulturasi:</b> Perpaduan kebudayaan tanpa menghilangkan ciri asli.'
        ]
    ]);
    $sql_b2 = "INSERT INTO elearning_bab (mapel_nama, nomor_bab, judul_bab, subjudul, durasi_menit, pdf_url, video_url, ringkasan_materi, lks_judul, lks_tugas) 
               VALUES ('Sosiologi', 2, 'Bab 2: Individu, Kelompok dan Hubungan Sosial', 'Syarat, Bentuk Asosiatif, dan Disosiatif Interaksi Sosial', '15 Menit', 
               'https://modul.pkbm.id/paket-c/Modul%202%20Sosiologi.pdf', 
               'https://www.youtube.com/embed/n33wY8GjSGo', 
               '" . $conn->real_escape_string($b2_ringkasan) . "', 
               'LKS 2: Studi Kasus Penyelesaian Perselisihan', 
               'Jelaskan perbedaan antara Mediasi (penengah netral) dan Arbitrase (penengah berwenang memutuskan) saat terjadi perbedaan pendapat!')";
    $conn->query($sql_b2);
}

// ==========================================
// 3. AMBIL LIST MATA PELAJARAN YANG DIKELOLA
// ==========================================
// Query semua mapel beserta nama ustadz pengampu (jika ada)
$sql_mapel_all = "SELECT m.*, u.nama_lengkap as nama_pengampu 
                  FROM master_mapel m 
                  LEFT JOIN akun_ustadz u ON m.pengampu_id = u.id 
                  WHERE m.status_aktif = 1 
                  ORDER BY m.kategori_mapel ASC, m.nama_mapel ASC";
$res_mapel_all = $conn->query($sql_mapel_all);
$all_mapel = $res_mapel_all ? $res_mapel_all->fetch_all(MYSQLI_ASSOC) : [];

// Filter tab aktif: 'all', 'diknas', 'unassigned', 'diniyah', 'my_mapel'
$filter_kategori = $_GET['filter_kat'] ?? ($is_super_admin ? 'all' : 'my_mapel');

$list_mapel = [];
$my_mapel_count = 0;
$unassigned_count = 0;
$diknas_count = 0;
$diniyah_count = 0;

foreach ($all_mapel as $m) {
    $is_my = ($m['pengampu_id'] == $ustadz_id);
    $is_unassigned = empty($m['pengampu_id']);
    $is_dik = (strtolower($m['kategori_mapel'] ?? '') === 'diknas');
    $is_din = (strtolower($m['kategori_mapel'] ?? '') === 'diniyah' || strtolower($m['kategori_mapel'] ?? '') === 'kepesantrenan');

    if ($is_my) $my_mapel_count++;
    if ($is_unassigned) $unassigned_count++;
    if ($is_dik) $diknas_count++;
    if ($is_din) $diniyah_count++;

    // Terapkan filter tampilan
    if ($filter_kategori === 'my_mapel' && !$is_my && !$is_super_admin) continue;
    if ($filter_kategori === 'unassigned' && !$is_unassigned) continue;
    if ($filter_kategori === 'diknas' && !$is_dik) continue;
    if ($filter_kategori === 'diniyah' && !$is_din) continue;

    $list_mapel[] = $m;
}

// Jika daftar mapel terfilter kosong, fallback ke semua mapel
if (empty($list_mapel)) {
    $list_mapel = $all_mapel;
}

// Mapel Aktif yang Dipilih
$selected_mapel = $_GET['mapel'] ?? ($list_mapel[0]['nama_mapel'] ?? 'Sosiologi');
$selected_mapel_esc = $conn->real_escape_string($selected_mapel);

// Dapatkan info detail mapel terpilih
$selected_mapel_info = null;
foreach ($all_mapel as $m) {
    if ($m['nama_mapel'] === $selected_mapel) {
        $selected_mapel_info = $m;
        break;
    }
}

// ==========================================
// 4. ACTION POST (TAMBAH / EDIT / HAPUS BAB)
// ==========================================
$action = $_POST['action'] ?? '';

// Format YouTube URL to Embed format
function formatYoutubeEmbed($url) {
    if (empty($url)) return '';
    if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    } elseif (preg_match('/(?:v=|\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
        return "https://www.youtube.com/embed/" . $matches[1];
    }
    return $url;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($action === 'simpan_bab') {
        $bab_id = (int)($_POST['bab_id'] ?? 0);
        $nomor_bab = (int)$_POST['nomor_bab'];
        $judul_bab = $conn->real_escape_string($_POST['judul_bab']);
        $subjudul = $conn->real_escape_string($_POST['subjudul']);
        $durasi_menit = $conn->real_escape_string($_POST['durasi_menit']);
        $pdf_url = $conn->real_escape_string(trim($_POST['pdf_url']));
        $video_url = $conn->real_escape_string(formatYoutubeEmbed(trim($_POST['video_url'])));
        $ringkasan_materi = $conn->real_escape_string($_POST['ringkasan_materi']);
        $lks_judul = $conn->real_escape_string($_POST['lks_judul']);
        $lks_tugas = $conn->real_escape_string($_POST['lks_tugas']);

        if ($bab_id > 0) {
            $sql = "UPDATE elearning_bab SET 
                    nomor_bab = $nomor_bab, judul_bab = '$judul_bab', subjudul = '$subjudul', 
                    durasi_menit = '$durasi_menit', pdf_url = '$pdf_url', video_url = '$video_url', 
                    ringkasan_materi = '$ringkasan_materi', lks_judul = '$lks_judul', lks_tugas = '$lks_tugas'
                    WHERE id = $bab_id";
            if ($conn->query($sql)) {
                $pesan_sukses = "Modul $judul_bab berhasil diperbarui!";
            } else {
                $pesan_error = "Gagal memperbarui bab: " . $conn->error;
            }
        } else {
            $sql = "INSERT INTO elearning_bab (mapel_nama, nomor_bab, judul_bab, subjudul, durasi_menit, pdf_url, video_url, ringkasan_materi, lks_judul, lks_tugas, created_by)
                    VALUES ('$selected_mapel_esc', $nomor_bab, '$judul_bab', '$subjudul', '$durasi_menit', '$pdf_url', '$video_url', '$ringkasan_materi', '$lks_judul', '$lks_tugas', $ustadz_id)";
            if ($conn->query($sql)) {
                $bab_id = $conn->insert_id;
                $pesan_sukses = "Modul $judul_bab berhasil ditambahkan!";
            } else {
                $pesan_error = "Gagal menambahkan bab: " . $conn->error;
            }
        }

        // Simpan Soal Kuis (jika ada)
        if ($bab_id > 0 && isset($_POST['soal_teks']) && is_array($_POST['soal_teks'])) {
            $conn->query("DELETE FROM elearning_kuis WHERE bab_id = $bab_id");
            foreach ($_POST['soal_teks'] as $idx => $soal) {
                $soal_clean = $conn->real_escape_string(trim($soal));
                if (!empty($soal_clean)) {
                    $opsi_a = $conn->real_escape_string($_POST['opsi_a'][$idx] ?? '');
                    $opsi_b = $conn->real_escape_string($_POST['opsi_b'][$idx] ?? '');
                    $opsi_c = $conn->real_escape_string($_POST['opsi_c'][$idx] ?? '');
                    $opsi_d = $conn->real_escape_string($_POST['opsi_d'][$idx] ?? '');
                    $kunci = $conn->real_escape_string($_POST['kunci'][$idx] ?? 'A');
                    $pembahasan = $conn->real_escape_string($_POST['pembahasan'][$idx] ?? '');

                    $conn->query("INSERT INTO elearning_kuis (bab_id, soal, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, pembahasan)
                                  VALUES ($bab_id, '$soal_clean', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$kunci', '$pembahasan')");
                }
            }
        }
    } elseif ($action === 'hapus_bab') {
        $del_id = (int)$_POST['bab_id'];
        if ($conn->query("DELETE FROM elearning_bab WHERE id = $del_id")) {
            $pesan_sukses = "Bab berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus bab: " . $conn->error;
        }
    }
}

// Ambil data Bab untuk Mapel yang dipilih
$res_babs = $conn->query("SELECT * FROM elearning_bab WHERE mapel_nama = '$selected_mapel_esc' ORDER BY nomor_bab ASC, id ASC");
$list_bab = $res_babs ? $res_babs->fetch_all(MYSQLI_ASSOC) : [];

// Mode Edit Bab
$edit_bab_id = (int)($_GET['edit_bab'] ?? 0);
$edit_data = null;
$edit_kuis = [];
if ($edit_bab_id > 0) {
    $res_eb = $conn->query("SELECT * FROM elearning_bab WHERE id = $edit_bab_id LIMIT 1");
    if ($res_eb && $res_eb->num_rows > 0) {
        $edit_data = $res_eb->fetch_assoc();
        $res_ek = $conn->query("SELECT * FROM elearning_kuis WHERE bab_id = $edit_bab_id ORDER BY id ASC");
        if ($res_ek) $edit_kuis = $res_ek->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola E-Modul & E-Learning | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER -->
        <header class="h-16 bg-white border-b border-slate-200 shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center space-x-3">
                <button id="open-sidebar-hr" class="text-slate-600 hover:text-indigo-600 md:hidden p-2 rounded-xl focus:outline-none">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-rose-600 to-indigo-600 text-white flex items-center justify-center font-bold shadow-md shadow-rose-100">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <h1 class="font-extrabold text-sm sm:text-base text-slate-900 leading-tight flex items-center gap-2">
                            Penyematan E-Modul & E-Learning
                            <?php if ($is_super_admin): ?>
                                <span class="bg-rose-100 text-rose-800 text-[10px] font-black px-2 py-0.5 rounded-full border border-rose-200">Super Admin Mode</span>
                            <?php else: ?>
                                <span class="bg-indigo-100 text-indigo-800 text-[10px] font-black px-2 py-0.5 rounded-full border border-indigo-200">Ruang Asatidz</span>
                            <?php endif; ?>
                        </h1>
                        <p class="text-[10px] text-slate-400">Sematkan Link PDF Modul (Auto Flipbook), Video Pembelajaran YouTube, Rangkuman & Kuis</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a href="santri-belajar.php?mapel=<?= urlencode($selected_mapel) ?>&bab=1" target="_blank" class="bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <i class="fas fa-book-reader"></i>
                    <span class="hidden sm:inline">Uji Layar Flipbook Santri</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto space-y-6">
                
                <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl shadow-sm flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_sukses ?></span>
                </div>
                <?php endif; ?>

                <?php if(!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl shadow-sm flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-rose-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_error ?></span>
                </div>
                <?php endif; ?>

                <!-- FILTER KATEGORI & PEMILIH MATA PELAJARAN -->
                <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                        
                        <!-- TAB FILTER CEPAT -->
                        <div class="flex flex-wrap items-center gap-2">
                            <?php if (!$is_super_admin): ?>
                            <a href="admin-elearning.php?filter_kat=my_mapel&mapel=<?= urlencode($selected_mapel) ?>" 
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($filter_kategori === 'my_mapel') ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                <i class="fas fa-user-check"></i>
                                <span>Mapel Ampuan Saya</span>
                                <span class="bg-white/20 px-1.5 py-0.2 rounded-md text-[10px]"><?= $my_mapel_count ?></span>
                            </a>
                            <?php endif; ?>

                            <a href="admin-elearning.php?filter_kat=all&mapel=<?= urlencode($selected_mapel) ?>" 
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($filter_kategori === 'all') ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                <i class="fas fa-layer-group"></i>
                                <span>Semua Mapel</span>
                                <span class="bg-slate-200 text-slate-700 px-1.5 py-0.2 rounded-md text-[10px]"><?= count($all_mapel) ?></span>
                            </a>

                            <a href="admin-elearning.php?filter_kat=diknas&mapel=<?= urlencode($selected_mapel) ?>" 
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($filter_kategori === 'diknas') ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                <i class="fas fa-school"></i>
                                <span>Mapel Diknas (PKBM)</span>
                                <span class="bg-slate-200 text-slate-700 px-1.5 py-0.2 rounded-md text-[10px]"><?= $diknas_count ?></span>
                            </a>

                            <?php if ($is_super_admin || $unassigned_count > 0): ?>
                            <a href="admin-elearning.php?filter_kat=unassigned&mapel=<?= urlencode($selected_mapel) ?>" 
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($filter_kategori === 'unassigned') ? 'bg-amber-500 text-white shadow-sm' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' ?>"
                               title="Mapel yang belum ada guru pengampunya sehingga Super Admin / Pengganti dapat membantu menyematkan link E-Modul">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Belum Ada Guru</span>
                                <span class="bg-amber-200 text-amber-900 px-1.5 py-0.2 rounded-md text-[10px] font-black"><?= $unassigned_count ?></span>
                            </a>
                            <?php endif; ?>

                            <a href="admin-elearning.php?filter_kat=diniyah&mapel=<?= urlencode($selected_mapel) ?>" 
                               class="px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 <?= ($filter_kategori === 'diniyah') ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                <i class="fas fa-mosque"></i>
                                <span>Diniyah / Kepesantrenan</span>
                                <span class="bg-slate-200 text-slate-700 px-1.5 py-0.2 rounded-md text-[10px]"><?= $diniyah_count ?></span>
                            </a>
                        </div>

                        <!-- DROPDOWN PEMILIH MAPEL -->
                        <form method="GET" class="flex items-center gap-2 flex-shrink-0">
                            <input type="hidden" name="filter_kat" value="<?= htmlspecialchars($filter_kategori) ?>">
                            <label class="text-xs font-bold text-slate-500 whitespace-nowrap"><i class="fas fa-book mr-1"></i> Pilih Mapel:</label>
                            <select name="mapel" onchange="this.form.submit()" class="px-3.5 py-2 bg-slate-50 border-2 border-indigo-200 rounded-xl text-xs sm:text-sm font-extrabold text-slate-900 focus:bg-white focus:ring-2 focus:ring-indigo-500 min-w-[220px]">
                                <?php foreach ($list_mapel as $m): 
                                    $pengampu_text = !empty($m['nama_pengampu']) ? $m['nama_pengampu'] : '⚠️ Belum Ada Guru';
                                    $is_my = ($m['pengampu_id'] == $ustadz_id);
                                ?>
                                    <option value="<?= htmlspecialchars($m['nama_mapel']) ?>" <?= ($selected_mapel === $m['nama_mapel']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nama_mapel']) ?> <?= $is_my ? '⭐ (Ampuan Saya)' : '' ?> — [<?= $pengampu_text ?>]
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                    <!-- STATUS BADGE MAPEL AKTIF -->
                    <?php if ($selected_mapel_info): ?>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3">
                            <span class="font-extrabold text-slate-800 text-sm">Pelajaran: <?= htmlspecialchars($selected_mapel) ?></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                Kategori: <?= htmlspecialchars($selected_mapel_info['kategori_mapel'] ?? 'Diknas') ?>
                            </span>
                            <?php if (!empty($selected_mapel_info['nama_pengampu'])): ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-1">
                                    <i class="fas fa-chalkboard-teacher"></i> Guru Pengampu: <b><?= htmlspecialchars($selected_mapel_info['nama_pengampu']) ?></b>
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-300 flex items-center gap-1 animate-pulse">
                                    <i class="fas fa-exclamation-triangle"></i> Belum ada pengampu — Diisi oleh Super Admin / Pengganti
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="text-[11px] text-slate-400">
                            Total Bab Terdaftar: <b class="text-slate-700"><?= count($list_bab) ?> Bab</b>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ========================================================================= -->
                <!-- KARTU PENYEMATAN LINK E-MODUL PDF (TEMPAT TEMPEL URL PER BAB & AUTO-FLIPBOOK) -->
                <!-- ========================================================================= -->
                <div class="bg-gradient-to-br from-rose-50 via-white to-amber-50 border-2 border-rose-200/80 rounded-3xl p-5 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-4 border-b border-rose-100">
                        <div class="flex items-center gap-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-rose-600 text-white flex items-center justify-center text-xl shadow-md shadow-rose-200">
                                <i class="fas fa-link"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-slate-900 flex items-center gap-2">
                                    Sematkan URL E-Modul PDF (<?= htmlspecialchars($selected_mapel) ?>)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-200">Auto-Flipbook 3D</span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Tempelkan link file PDF modul (dari <a href="https://modul.pkbm.id/modul-paket-c.html" target="_blank" class="text-rose-600 font-bold hover:underline">modul.pkbm.id</a>, Google Drive, atau repositori lainnya). Sistem akan otomatis merendernya menjadi <b>Flipbook Interaktif</b> di layar santri.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <button type="button" onclick="terapkanKatalogPkbm('<?= htmlspecialchars($selected_mapel) ?>')" class="text-xs font-bold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                                <i class="fas fa-magic text-amber-500"></i> <span>Gunakan Link Standar PKBM</span>
                            </button>
                            <a href="https://modul.pkbm.id/modul-paket-c.html" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-rose-700 bg-white hover:bg-rose-50 border border-rose-200 px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                                <i class="fas fa-search"></i> <span>Buka Portal modul.pkbm.id</span> <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                            </a>
                        </div>
                    </div>

                    <!-- DAFTAR INPUT CEPAT PER BAB -->
                    <?php if (count($list_bab) > 0): ?>
                    <div class="space-y-3">
                        <?php foreach ($list_bab as $bItem): 
                            $default_pkbm = getPkbmModulPdfUrl($bItem['mapel_nama'], $bItem['nomor_bab']);
                            $cur_pdf = !empty($bItem['pdf_url']) ? $bItem['pdf_url'] : $default_pkbm;
                        ?>
                        <div class="bg-white p-4 rounded-2xl border border-rose-100 shadow-2xs flex flex-col md:flex-row md:items-center gap-3 justify-between hover:border-rose-300 transition">
                            <div class="min-w-[220px]">
                                <div class="flex items-center gap-2">
                                    <span class="w-7 h-7 rounded-xl bg-rose-100 text-rose-800 font-black text-xs flex items-center justify-center shadow-2xs"><?= $bItem['nomor_bab'] ?></span>
                                    <div>
                                        <h4 class="text-xs font-black text-slate-900 leading-snug"><?= htmlspecialchars(mb_strimwidth($bItem['judul_bab'], 0, 35, '...')) ?></h4>
                                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars(mb_strimwidth($bItem['subjudul'] ?? 'Materi Pembelajaran', 0, 40, '...')) ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                <div class="relative flex-1">
                                    <i class="fas fa-file-pdf absolute left-3.5 top-3 text-rose-500 text-xs"></i>
                                    <input type="url" id="pdf_input_<?= $bItem['id'] ?>" value="<?= htmlspecialchars($cur_pdf) ?>" placeholder="https://modul.pkbm.id/paket-c/Modul...pdf" class="w-full pl-9 pr-3 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-rose-500 font-mono text-slate-700">
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="simpanSinglePdfUrl(<?= $bItem['id'] ?>)" id="btn_save_pdf_<?= $bItem['id'] ?>" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition shadow-2xs flex items-center gap-1.5 whitespace-nowrap">
                                        <i class="fas fa-save"></i> <span>Simpan URL</span>
                                    </button>
                                    <a href="santri-belajar.php?mapel=<?= urlencode($bItem['mapel_nama']) ?>&bab=<?= $bItem['nomor_bab'] ?>" target="_blank" class="bg-teal-50 hover:bg-teal-100 text-[#0d8276] border border-teal-200 font-bold px-3.5 py-2.5 rounded-xl text-xs transition flex items-center gap-1.5 whitespace-nowrap" title="Buka Flipbook di Layar Santri">
                                        <i class="fas fa-book-open"></i> <span>Uji Flipbook</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-8 bg-white/70 rounded-2xl border border-dashed border-rose-200">
                        <i class="fas fa-folder-plus text-3xl text-rose-300 mb-2"></i>
                        <h4 class="text-xs font-bold text-slate-700">Belum ada Bab/Modul untuk Mata Pelajaran <?= htmlspecialchars($selected_mapel) ?></h4>
                        <p class="text-[11px] text-slate-400 mt-1">Tambahkan bab baru pada formulir di bawah ini untuk mulai menyematkan link E-Modul.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- DUA KOLOM: DAFTAR BAB (KIRI) & FORM EDIT/TAMBAH BAB (KANAN) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- KOLOM KIRI: DAFTAR BAB -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-sm">
                            <div class="flex items-center justify-between mb-4 border-b pb-3">
                                <div>
                                    <h2 class="font-black text-sm text-slate-900">Struktur Bab / Modul</h2>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($selected_mapel) ?></p>
                                </div>
                                <a href="admin-elearning.php?filter_kat=<?= urlencode($filter_kategori) ?>&mapel=<?= urlencode($selected_mapel) ?>" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1">
                                    <i class="fas fa-plus"></i> Tambah Bab
                                </a>
                            </div>

                            <div class="space-y-2.5">
                                <?php if (count($list_bab) > 0): ?>
                                    <?php foreach ($list_bab as $b): ?>
                                    <div class="p-3.5 rounded-2xl border transition <?= ($edit_bab_id == $b['id']) ? 'bg-indigo-50 border-indigo-300 shadow-sm' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/80' ?>">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold"><?= $b['nomor_bab'] ?></span>
                                                    <h3 class="font-bold text-xs text-slate-900 leading-snug"><?= htmlspecialchars($b['judul_bab']) ?></h3>
                                                </div>
                                                <p class="text-[11px] text-slate-500 mt-1 line-clamp-1"><?= htmlspecialchars($b['subjudul'] ?? '') ?></p>
                                                
                                                <div class="flex items-center gap-2 mt-2 text-[10px] font-semibold text-slate-400">
                                                    <?php if(!empty($b['pdf_url'])): ?><span class="text-rose-600"><i class="fas fa-file-pdf"></i> PDF Flip</span><?php endif; ?>
                                                    <?php if(!empty($b['video_url'])): ?><span class="text-red-500"><i class="fab fa-youtube"></i> Video</span><?php endif; ?>
                                                    <?php if(!empty($b['lks_tugas'])): ?><span class="text-amber-600"><i class="fas fa-pencil-alt"></i> LKS</span><?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-1">
                                                <a href="admin-elearning.php?filter_kat=<?= urlencode($filter_kategori) ?>&mapel=<?= urlencode($selected_mapel) ?>&edit_bab=<?= $b['id'] ?>" class="w-7 h-7 rounded-lg bg-white hover:bg-indigo-600 hover:text-white text-indigo-600 border border-slate-200 flex items-center justify-center text-xs transition" title="Edit Rincian Bab">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus Bab ini?');" class="inline">
                                                    <input type="hidden" name="action" value="hapus_bab">
                                                    <input type="hidden" name="bab_id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="w-7 h-7 rounded-lg bg-white hover:bg-rose-600 hover:text-white text-rose-600 border border-slate-200 flex items-center justify-center text-xs transition" title="Hapus Bab">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-8 text-slate-400">
                                        <i class="fas fa-folder-open text-3xl mb-2 opacity-40"></i>
                                        <p class="text-xs">Belum ada modul bab untuk mapel ini.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: FORM INPUT / EDIT BAB & SOAL -->
                    <div class="lg:col-span-2">
                        <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm">
                            <div class="border-b pb-4 mb-6 flex items-center justify-between">
                                <div>
                                    <h2 class="font-black text-base text-slate-900">
                                        <?= $edit_data ? 'Edit Rincian Modul Pembelajaran' : 'Tambah Modul / Bab Baru' ?>
                                    </h2>
                                    <p class="text-xs text-slate-400">Mata Pelajaran: <b><?= htmlspecialchars($selected_mapel) ?></b></p>
                                </div>
                                <?php if($edit_data): ?>
                                <a href="admin-elearning.php?filter_kat=<?= urlencode($filter_kategori) ?>&mapel=<?= urlencode($selected_mapel) ?>" class="text-xs font-bold text-slate-500 hover:text-slate-700">
                                    <i class="fas fa-times mr-1"></i> Batal Edit
                                </a>
                                <?php endif; ?>
                            </div>

                            <form method="POST" class="space-y-6">
                                <input type="hidden" name="action" value="simpan_bab">
                                <input type="hidden" name="bab_id" value="<?= $edit_data['id'] ?? 0 ?>">

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nomor Bab</label>
                                        <input type="number" name="nomor_bab" value="<?= htmlspecialchars($edit_data['nomor_bab'] ?? (count($list_bab) + 1)) ?>" required class="w-full px-3.5 py-2.5 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Judul Bab</label>
                                        <input type="text" name="judul_bab" value="<?= htmlspecialchars($edit_data['judul_bab'] ?? '') ?>" placeholder="misal: Bab 1: Sosiologi Sebagai Ilmu" required class="w-full px-3.5 py-2.5 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Sub-Judul / Topik Pembahasan</label>
                                        <input type="text" name="subjudul" value="<?= htmlspecialchars($edit_data['subjudul'] ?? '') ?>" placeholder="misal: Hakikat, Ciri-ciri, dan Objek Kajian" class="w-full px-3.5 py-2.5 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Estimasi Waktu</label>
                                        <input type="text" name="durasi_menit" value="<?= htmlspecialchars($edit_data['durasi_menit'] ?? '15 Menit') ?>" placeholder="misal: 15 Menit" class="w-full px-3.5 py-2.5 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <!-- 1. URL E-MODUL RESMI PDF -->
                                <div class="bg-rose-50/60 p-4 rounded-2xl border border-rose-100">
                                    <label class="block text-xs font-extrabold uppercase text-rose-900 mb-1 flex items-center gap-1.5">
                                        <i class="fas fa-file-pdf text-rose-600"></i> Link File E-Modul PDF (Auto Flipbook Engine)
                                    </label>
                                    <p class="text-[11px] text-rose-700 mb-2">Tempelkan link file PDF modul (misal dari repositori https://modul.pkbm.id/ atau server Anda).</p>
                                    <input type="url" name="pdf_url" value="<?= htmlspecialchars($edit_data['pdf_url'] ?? '') ?>" placeholder="https://modul.pkbm.id/paket-c/Modul...pdf" class="w-full px-3.5 py-2.5 border border-rose-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-rose-500 font-mono">
                                </div>

                                <!-- 2. URL VIDEO YOUTUBE -->
                                <div class="bg-red-50/60 p-4 rounded-2xl border border-red-100">
                                    <label class="block text-xs font-extrabold uppercase text-red-900 mb-1 flex items-center gap-1.5">
                                        <i class="fab fa-youtube text-red-600"></i> Link Video Pembelajaran YouTube
                                    </label>
                                    <p class="text-[11px] text-red-700 mb-2">Tempelkan link video YouTube materi ini (otomatis ditampilkan dengan privacy player bebas iklan pelacak).</p>
                                    <input type="url" name="video_url" value="<?= htmlspecialchars($edit_data['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3.5 py-2.5 border border-red-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-red-500 font-mono">
                                </div>

                                <!-- 3. RANGKUMAN MATERI -->
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Rangkuman / Konsep Inti Pembelajaran (Flipbook 5 Halaman)</label>
                                    <textarea name="ringkasan_materi" rows="4" placeholder="Tuliskan poin-poin hafalan, definisi, atau ringkasan konsep penting..." class="w-full px-3.5 py-2.5 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($edit_data['ringkasan_materi'] ?? '') ?></textarea>
                                </div>

                                <!-- 4. LKS (LEMBAR KERJA SISWA) -->
                                <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-100 space-y-3">
                                    <label class="block text-xs font-extrabold uppercase text-amber-900 flex items-center gap-1.5">
                                        <i class="fas fa-pencil-alt text-amber-600"></i> Lembar Kerja Siswa (LKS) & Penugasan Mandiri
                                    </label>
                                    <input type="text" name="lks_judul" value="<?= htmlspecialchars($edit_data['lks_judul'] ?? '') ?>" placeholder="Judul Tugas LKS (misal: LKS 1: Analisis Gejala Sosial)" class="w-full px-3.5 py-2.5 border border-amber-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-amber-500">
                                    <textarea name="lks_tugas" rows="3" placeholder="Instruksi tugas yang harus dikerjakan santri..." class="w-full px-3.5 py-2.5 border border-amber-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-amber-500"><?= htmlspecialchars($edit_data['lks_tugas'] ?? '') ?></textarea>
                                </div>

                                <!-- 5. BANK SOAL KUIS PILIHAN GANDA -->
                                <div class="bg-indigo-50/60 p-4 rounded-2xl border border-indigo-100">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <label class="block text-xs font-extrabold uppercase text-indigo-900 flex items-center gap-1.5">
                                                <i class="fas fa-question-circle text-indigo-600"></i> Latihan Soal Kuis Pilihan Ganda (Auto-Scoring)
                                            </label>
                                            <p class="text-[11px] text-indigo-700">Soal latihan interaktif untuk menguji pemahaman santri.</p>
                                        </div>
                                        <button type="button" onclick="tambahSoalRow()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold px-3 py-1.5 rounded-xl transition flex items-center gap-1">
                                            <i class="fas fa-plus"></i> Tambah Soal
                                        </button>
                                    </div>

                                    <div id="quizRowsContainer" class="space-y-4">
                                        <?php if (count($edit_kuis) > 0): ?>
                                            <?php foreach ($edit_kuis as $qIdx => $qk): ?>
                                            <div class="bg-white p-4 rounded-xl border border-indigo-100 shadow-2xs quiz-item">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-bold text-indigo-800">Soal #<?= $qIdx + 1 ?></span>
                                                    <button type="button" onclick="this.closest('.quiz-item').remove()" class="text-rose-500 hover:text-rose-700 text-xs font-bold"><i class="fas fa-trash"></i> Hapus</button>
                                                </div>
                                                <textarea name="soal_teks[]" rows="2" placeholder="Pertanyaan soal..." class="w-full px-3 py-2 border rounded-xl text-xs mb-2 bg-slate-50 focus:bg-white" required><?= htmlspecialchars($qk['soal']) ?></textarea>
                                                <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                                                    <div><span class="text-[10px] font-bold text-slate-500">A.</span> <input type="text" name="opsi_a[]" value="<?= htmlspecialchars($qk['opsi_a']) ?>" placeholder="Pilihan A" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                                                    <div><span class="text-[10px] font-bold text-slate-500">B.</span> <input type="text" name="opsi_b[]" value="<?= htmlspecialchars($qk['opsi_b']) ?>" placeholder="Pilihan B" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                                                    <div><span class="text-[10px] font-bold text-slate-500">C.</span> <input type="text" name="opsi_c[]" value="<?= htmlspecialchars($qk['opsi_c']) ?>" placeholder="Pilihan C" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                                                    <div><span class="text-[10px] font-bold text-slate-500">D.</span> <input type="text" name="opsi_d[]" value="<?= htmlspecialchars($qk['opsi_d']) ?>" placeholder="Pilihan D" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                                                </div>
                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-slate-600 uppercase">Kunci Jawaban</label>
                                                        <select name="kunci[]" class="w-full px-2.5 py-1.5 border rounded-lg text-xs bg-slate-50 font-bold text-indigo-700">
                                                            <option value="A" <?= $qk['kunci_jawaban'] === 'A' ? 'selected' : '' ?>>Pilihan A</option>
                                                            <option value="B" <?= $qk['kunci_jawaban'] === 'B' ? 'selected' : '' ?>>Pilihan B</option>
                                                            <option value="C" <?= $qk['kunci_jawaban'] === 'C' ? 'selected' : '' ?>>Pilihan C</option>
                                                            <option value="D" <?= $qk['kunci_jawaban'] === 'D' ? 'selected' : '' ?>>Pilihan D</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-slate-600 uppercase">Pembahasan Jawaban</label>
                                                        <input type="text" name="pembahasan[]" value="<?= htmlspecialchars($qk['pembahasan'] ?? '') ?>" placeholder="Penjelasan jawaban benar..." class="w-full px-2.5 py-1.5 border rounded-lg text-xs">
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="pt-4 border-t flex justify-end">
                                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold px-8 py-3 rounded-xl shadow-md transition flex items-center gap-2 text-xs sm:text-sm">
                                        <i class="fas fa-save"></i> Simpan Modul Pembelajaran
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <script>
        function tambahSoalRow() {
            const container = document.getElementById('quizRowsContainer');
            const newIndex = container.children.length + 1;
            const rowHtml = `
                <div class="bg-white p-4 rounded-xl border border-indigo-100 shadow-2xs quiz-item">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-indigo-800">Soal #${newIndex}</span>
                        <button type="button" onclick="this.closest('.quiz-item').remove()" class="text-rose-500 hover:text-rose-700 text-xs font-bold"><i class="fas fa-trash"></i> Hapus</button>
                    </div>
                    <textarea name="soal_teks[]" rows="2" placeholder="Pertanyaan soal..." class="w-full px-3 py-2 border rounded-xl text-xs mb-2 bg-slate-50 focus:bg-white" required></textarea>
                    <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                        <div><span class="text-[10px] font-bold text-slate-500">A.</span> <input type="text" name="opsi_a[]" placeholder="Pilihan A" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                        <div><span class="text-[10px] font-bold text-slate-500">B.</span> <input type="text" name="opsi_b[]" placeholder="Pilihan B" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                        <div><span class="text-[10px] font-bold text-slate-500">C.</span> <input type="text" name="opsi_c[]" placeholder="Pilihan C" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                        <div><span class="text-[10px] font-bold text-slate-500">D.</span> <input type="text" name="opsi_d[]" placeholder="Pilihan D" class="w-full px-2.5 py-1.5 border rounded-lg text-xs" required></div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase">Kunci Jawaban</label>
                            <select name="kunci[]" class="w-full px-2.5 py-1.5 border rounded-lg text-xs bg-slate-50 font-bold text-indigo-700">
                                <option value="A">Pilihan A</option>
                                <option value="B">Pilihan B</option>
                                <option value="C">Pilihan C</option>
                                <option value="D">Pilihan D</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase">Pembahasan Jawaban</label>
                            <input type="text" name="pembahasan[]" placeholder="Penjelasan jawaban benar..." class="w-full px-2.5 py-1.5 border rounded-lg text-xs">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }

        // Simpan Link E-Modul Tunggal Cepat via AJAX
        async function simpanSinglePdfUrl(babId) {
            const input = document.getElementById('pdf_input_' + babId);
            const btn = document.getElementById('btn_save_pdf_' + babId);
            if (!input || !btn) return;

            const pdfUrl = input.value.trim();
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Menyimpan...`;
            btn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('action', 'update_single_pdf_url');
                fd.append('bab_id', babId);
                fd.append('pdf_url', pdfUrl);

                const res = await fetch('admin-elearning.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.status === 'success') {
                    btn.innerHTML = `<i class="fas fa-check text-emerald-200"></i> Tersimpan!`;
                    btn.className = 'bg-emerald-600 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition shadow-2xs flex items-center gap-1.5 whitespace-nowrap';
                    setTimeout(() => {
                        btn.innerHTML = originalBtnHtml;
                        btn.className = 'bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2.5 rounded-xl text-xs transition shadow-2xs flex items-center gap-1.5 whitespace-nowrap';
                        btn.disabled = false;
                    }, 2000);
                } else {
                    alert('Gagal menyimpan: ' + (data.message || 'Terjadi kesalahan'));
                    btn.innerHTML = originalBtnHtml;
                    btn.disabled = false;
                }
            } catch (err) {
                alert('Error koneksi: ' + err.message);
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        }

        // Terapkan Link Standar PKBM Otomatis
        async function terapkanKatalogPkbm(mapelNama) {
            if (!confirm(`Terapkan URL resmi modul.pkbm.id secara otomatis untuk semua bab di mapel ${mapelNama}?`)) {
                return;
            }

            try {
                const fd = new FormData();
                fd.append('action', 'apply_pkbm_catalog');
                fd.append('mapel_nama', mapelNama);

                const res = await fetch('admin-elearning.php', {
                    method: 'POST',
                    body: fd
                });
                const data = await res.json();

                if (data.status === 'success') {
                    alert(data.message);
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
                }
            } catch (err) {
                alert('Error koneksi: ' + err.message);
            }
        }
    </script>
</body>
</html>
