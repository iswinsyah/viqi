<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'manajemen_elearning';
$pesan_sukses = '';
$pesan_error = '';

$ustadz_id = (int)($_SESSION['ustadz_id'] ?? 0);
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];

$norm_user_roles = array_map(function($r) {
    return str_replace([" ", "'"], ["_", ""], strtolower(trim($r)));
}, $user_roles);
$is_super_admin = in_array('super_admin', $norm_user_roles) || in_array('kepala_sekolah', $norm_user_roles) || in_array('admin_sekolah', $norm_user_roles);

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

// Seed initial Sosiologi data if empty
$chk_sosiologi = $conn->query("SELECT id FROM elearning_bab WHERE mapel_nama = 'Sosiologi' LIMIT 1");
if ($chk_sosiologi && $chk_sosiologi->num_rows === 0) {
    // Bab 1
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
               'https://repositori.kemdikbud.go.id/21980/1/X_Sosiologi_KD-3.1_Final.pdf', 
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

    // Bab 2
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
               VALUES ('Sosiologi', 2, 'Bab 2: Interaksi Sosial & Dinamika Kelompok', 'Syarat, Bentuk Asosiatif, dan Disosiatif Interaksi Sosial', '15 Menit', 
               'https://repositori.kemdikbud.go.id/21981/1/X_Sosiologi_KD-3.2_Final.pdf', 
               'https://www.youtube.com/embed/n33wY8GjSGo', 
               '" . $conn->real_escape_string($b2_ringkasan) . "', 
               'LKS 2: Studi Kasus Penyelesaian Perselisihan', 
               'Jelaskan perbedaan antara Mediasi (penengah netral) dan Arbitrase (penengah berwenang memutuskan) saat terjadi perbedaan pendapat!')";
    $conn->query($sql_b2);
}

// ==========================================
// 2. AMBIL LIST MATA PELAJARAN YANG DIKELOLA
// ==========================================
if ($is_super_admin) {
    $res_mapel = $conn->query("SELECT * FROM master_mapel WHERE status_aktif = 1 ORDER BY nama_mapel ASC");
} else {
    // Guru hanya melihat mapel yang diampunya
    $res_mapel = $conn->query("SELECT * FROM master_mapel WHERE pengampu_id = $ustadz_id AND status_aktif = 1 ORDER BY nama_mapel ASC");
    // Fallback jika belum di-set pengampu_id
    if (!$res_mapel || $res_mapel->num_rows === 0) {
        $res_mapel = $conn->query("SELECT * FROM master_mapel WHERE status_aktif = 1 ORDER BY nama_mapel ASC");
    }
}
$list_mapel = $res_mapel ? $res_mapel->fetch_all(MYSQLI_ASSOC) : [];

// Mapel Aktif yang Dipilih
$selected_mapel = $_GET['mapel'] ?? ($list_mapel[0]['nama_mapel'] ?? 'Sosiologi');
$selected_mapel_esc = $conn->real_escape_string($selected_mapel);

// ==========================================
// 3. ACTION POST (TAMBAH / EDIT / HAPUS BAB)
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
    <title>Kelola E-Learning (Modul & Kuis) | SADIGS 4.0</title>
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
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold shadow-md shadow-indigo-100">
                        <i class="fas fa-laptop-code"></i>
                    </div>
                    <div>
                        <h1 class="font-extrabold text-sm sm:text-base text-slate-900 leading-tight">Manajemen E-Learning & Modul Belajar</h1>
                        <p class="text-[10px] text-slate-400">Pengaturan E-Modul PDF Negara, Video YouTube, LKS, Kuis, & Ustadz AI</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <a href="santri-belajar.php?mapel=<?= urlencode($selected_mapel) ?>" target="_blank" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl shadow-sm transition flex items-center gap-1.5">
                    <i class="fas fa-external-link-alt"></i>
                    <span class="hidden sm:inline">Pratinjau di Ruang Santri</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto">
                
                <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl mb-5 shadow-sm flex items-center gap-3">
                    <i class="fas fa-check-circle text-emerald-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_sukses ?></span>
                </div>
                <?php endif; ?>

                <?php if(!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl mb-5 shadow-sm flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-rose-600 text-lg flex-shrink-0"></i>
                    <span class="text-xs sm:text-sm font-semibold"><?= $pesan_error ?></span>
                </div>
                <?php endif; ?>

                <!-- PILIH MATA PELAJARAN YANG DIKELOLA -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm mb-6">
                    <form method="GET" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 tracking-wider mb-1">Mata Pelajaran yang Dikelola</label>
                            <p class="text-xs text-slate-400">Pilih mata pelajaran untuk melihat atau mengedit daftar bab dan materi pembelajaran</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <select name="mapel" onchange="this.form.submit()" class="px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm font-bold text-slate-800 focus:ring-2 focus:ring-indigo-500 min-w-[200px]">
                                <?php foreach ($list_mapel as $m): ?>
                                    <option value="<?= htmlspecialchars($m['nama_mapel']) ?>" <?= ($selected_mapel === $m['nama_mapel']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nama_mapel']) ?> (<?= $m['kategori_mapel'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- KOLOM KIRI: DAFTAR BAB -->
                    <div class="lg:col-span-1 space-y-4">
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="flex items-center justify-between mb-4 border-b pb-3">
                                <div>
                                    <h2 class="font-black text-sm text-slate-900">Daftar Bab / Modul</h2>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($selected_mapel) ?></p>
                                </div>
                                <a href="admin-elearning.php?mapel=<?= urlencode($selected_mapel) ?>" class="bg-indigo-50 hover:bg-indigo-100 text-indigo-600 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center gap-1">
                                    <i class="fas fa-plus"></i> Tambah
                                </a>
                            </div>

                            <div class="space-y-2.5">
                                <?php if (count($list_bab) > 0): ?>
                                    <?php foreach ($list_bab as $b): ?>
                                    <div class="p-3.5 rounded-xl border transition <?= ($edit_bab_id == $b['id']) ? 'bg-indigo-50 border-indigo-300 shadow-sm' : 'bg-slate-50 border-slate-200 hover:bg-slate-100/80' ?>">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="w-5 h-5 rounded-full bg-indigo-600 text-white flex items-center justify-center text-[10px] font-bold"><?= $b['nomor_bab'] ?></span>
                                                    <h3 class="font-bold text-xs text-slate-900 leading-snug"><?= htmlspecialchars($b['judul_bab']) ?></h3>
                                                </div>
                                                <p class="text-[11px] text-slate-500 mt-1 line-clamp-1"><?= htmlspecialchars($b['subjudul']) ?></p>
                                                
                                                <div class="flex items-center gap-2 mt-2 text-[10px] font-semibold text-slate-400">
                                                    <?php if(!empty($b['pdf_url'])): ?><span class="text-rose-600"><i class="fas fa-file-pdf"></i> PDF</span><?php endif; ?>
                                                    <?php if(!empty($b['video_url'])): ?><span class="text-red-500"><i class="fab fa-youtube"></i> Video</span><?php endif; ?>
                                                    <?php if(!empty($b['lks_tugas'])): ?><span class="text-amber-600"><i class="fas fa-pencil-alt"></i> LKS</span><?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center gap-1">
                                                <a href="admin-elearning.php?mapel=<?= urlencode($selected_mapel) ?>&edit_bab=<?= $b['id'] ?>" class="w-7 h-7 rounded-lg bg-white hover:bg-indigo-600 hover:text-white text-indigo-600 border border-slate-200 flex items-center justify-center text-xs transition" title="Edit Bab">
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
                        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm">
                            <div class="border-b pb-4 mb-6 flex items-center justify-between">
                                <div>
                                    <h2 class="font-black text-base text-slate-900">
                                        <?= $edit_data ? 'Edit Modul Pembelajaran' : 'Tambah Modul Pembelajaran Baru' ?>
                                    </h2>
                                    <p class="text-xs text-slate-400">Mata Pelajaran: <b><?= htmlspecialchars($selected_mapel) ?></b></p>
                                </div>
                                <?php if($edit_data): ?>
                                <a href="admin-elearning.php?mapel=<?= urlencode($selected_mapel) ?>" class="text-xs font-bold text-slate-500 hover:text-slate-700">
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
                                        <input type="number" name="nomor_bab" value="<?= htmlspecialchars($edit_data['nomor_bab'] ?? (count($list_bab) + 1)) ?>" required class="w-full px-3 py-2 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Judul Bab</label>
                                        <input type="text" name="judul_bab" value="<?= htmlspecialchars($edit_data['judul_bab'] ?? '') ?>" placeholder="misal: Bab 1: Sosiologi Sebagai Ilmu" required class="w-full px-3 py-2 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Sub-Judul / Topik Pembahasan</label>
                                        <input type="text" name="subjudul" value="<?= htmlspecialchars($edit_data['subjudul'] ?? '') ?>" placeholder="misal: Hakikat, Ciri-ciri, dan Objek Kajian" class="w-full px-3 py-2 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Estimasi Waktu</label>
                                        <input type="text" name="durasi_menit" value="<?= htmlspecialchars($edit_data['durasi_menit'] ?? '15 Menit') ?>" placeholder="misal: 15 Menit" class="w-full px-3 py-2 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                </div>

                                <!-- 1. URL E-MODUL RESMI KEMENDIKDASMEN -->
                                <div class="bg-rose-50/50 p-4 rounded-2xl border border-rose-100">
                                    <label class="block text-xs font-extrabold uppercase text-rose-900 mb-1 flex items-center gap-1.5">
                                        <i class="fas fa-file-pdf text-rose-600"></i> Link E-Modul Resmi Pemerintah (https://emodul.kemendikdasmen.go.id/)
                                    </label>
                                    <p class="text-[11px] text-rose-700 mb-2">Tempelkan link dari portal resmi Kemendikdasmen atau link PDF modul resmi.</p>
                                    <input type="url" name="pdf_url" value="<?= htmlspecialchars($edit_data['pdf_url'] ?? '') ?>" placeholder="https://emodul.kemendikdasmen.go.id/... atau link PDF" class="w-full px-3 py-2 border border-rose-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-rose-500">
                                </div>

                                <!-- 2. URL VIDEO YOUTUBE -->
                                <div class="bg-red-50/50 p-4 rounded-2xl border border-red-100">
                                    <label class="block text-xs font-extrabold uppercase text-red-900 mb-1 flex items-center gap-1.5">
                                        <i class="fab fa-youtube text-red-600"></i> Link Video Pembelajaran YouTube
                                    </label>
                                    <p class="text-[11px] text-red-700 mb-2">Tempelkan link video YouTube materi ini (akan otomatis di-embed).</p>
                                    <input type="url" name="video_url" value="<?= htmlspecialchars($edit_data['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..." class="w-full px-3 py-2 border border-red-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-red-500">
                                </div>

                                <!-- 3. RANGKUMAN MATERI -->
                                <div>
                                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Rangkuman / Konsep Inti Pembelajaran</label>
                                    <textarea name="ringkasan_materi" rows="4" placeholder="Tuliskan poin-poin hafalan, definisi, atau ringkasan konsep penting..." class="w-full px-3 py-2 border rounded-xl text-xs bg-slate-50 focus:bg-white focus:ring-2 focus:ring-indigo-500"><?= htmlspecialchars($edit_data['ringkasan_materi'] ?? '') ?></textarea>
                                </div>

                                <!-- 4. LKS (LEMBAR KERJA SISWA) -->
                                <div class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100 space-y-3">
                                    <label class="block text-xs font-extrabold uppercase text-amber-900 flex items-center gap-1.5">
                                        <i class="fas fa-pencil-alt text-amber-600"></i> Lembar Kerja Siswa (LKS) & Penugasan Mandiri
                                    </label>
                                    <input type="text" name="lks_judul" value="<?= htmlspecialchars($edit_data['lks_judul'] ?? '') ?>" placeholder="Judul Tugas LKS (misal: LKS 1: Analisis Gejala Sosial)" class="w-full px-3 py-2 border border-amber-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-amber-500">
                                    <textarea name="lks_tugas" rows="3" placeholder="Instruksi tugas yang harus dikerjakan santri..." class="w-full px-3 py-2 border border-amber-200 rounded-xl text-xs bg-white focus:ring-2 focus:ring-amber-500"><?= htmlspecialchars($edit_data['lks_tugas'] ?? '') ?></textarea>
                                </div>

                                <!-- 5. BANK SOAL KUIS PILIHAN GANDA -->
                                <div class="bg-indigo-50/50 p-4 rounded-2xl border border-indigo-100">
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
    </script>
</body>
</html>
