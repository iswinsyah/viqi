<?php
// =========================================================
// SISTEM SIARAN WHATSAPP MITRA / AGEN (SEMI-AUTO BLAST)
// SADIGS 4.0 - RUANG MARKETING & AI VILLA QURAN BARON MALANG
// =========================================================

require_once 'auth.php';
require_once 'koneksi.php';

// Pastikan folder upload siaran tersedia
$upload_dir = __DIR__ . '/upload/siaran/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// 1. AUTO-MIGRASI TABEL DATABASE SIARAN WA
$conn->query("CREATE TABLE IF NOT EXISTS siaran_wa_template (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    pesan LONGTEXT NOT NULL,
    gambar_url VARCHAR(500) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS siaran_wa_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT DEFAULT 1,
    agen_id INT NOT NULL,
    whatsapp VARCHAR(50) NOT NULL,
    status VARCHAR(20) DEFAULT 'terkirim',
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_template_agen (template_id, agen_id)
)");

// Insert default template jika masih kosong
$cek_tpl = $conn->query("SELECT COUNT(id) as tot FROM siaran_wa_template");
$tot_tpl = ($cek_tpl && $row = $cek_tpl->fetch_assoc()) ? (int)$row['tot'] : 0;
if ($tot_tpl === 0) {
    $default_pesan = "✨ *UNDANGAN KHUSUS & INFORMASI PENDIDIKAN SANTRI TAHFIDZ* ✨\n\n"
        . "Assalamu'alaikum Warahmatullahi Wabarakatuh,\n"
        . "Bapak/Ibu/Sahabat yang dirahmati Allah,\n\n"
        . "Pendaftaran Santri Baru *Sekolah Tahfidz Berasrama Villa Quran Baron Malang* telah dibuka.\n\n"
        . "🌿 *Keunggulan Pendidikan:* \n"
        . "1. Tahfidz Mutqin 15–30 Juz Bersanad\n"
        . "2. Lingkungan Asri, Bersih & Nyaman ala Villa (Anti-Stres & Bahagia)\n"
        . "3. Kurikulum Terpadu: Adab Qur'ani, Ijazah Formal SMP/SMA & Inkubator Solopreneur AI\n"
        . "4. Nutrisi Optimal & Brain Food untuk Daya Ingat Kuat\n\n"
        . "📱 *Buka Brosur Digital Interaktif Lengkap:* \n"
        . "{link_brosur}\n\n"
        . "📝 *Formulir Pendaftaran SPMB Online:* \n"
        . "{link_spmb}\n\n"
        . "💰 *Informasi Biaya Transparan:* \n"
        . "{link_biaya}\n\n"
        . "Silakan dibagikan kepada keluarga, kerabat, atau grup WA yang membutuhkan referensi pendidikan Qur'ani terbaik.\n\n"
        . "Jazakumullahu khairan.\n"
        . "_Rekomendasi dari Mitra Resmi: {nama}_";

    $stmt_def = $conn->prepare("INSERT INTO siaran_wa_template (judul, pesan, gambar_url) VALUES (?, ?, ?)");
    $def_judul = "Siaran Brosur Digital & SPMB Santri Baru";
    $def_img = "upload/brosur_flyer_default.jpg";
    $stmt_def->bind_param("sss", $def_judul, $default_pesan, $def_img);
    $stmt_def->execute();
}

// 2. TANGKAP AJAX REQUEST (LOG PENGIRIMAN & RESET)
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    $tid = (int)($_POST['template_id'] ?? 1);
    
    if ($action === 'mark_sent') {
        $aid = (int)($_POST['agen_id'] ?? 0);
        $wa = $conn->real_escape_string($_POST['whatsapp'] ?? '');
        if ($aid > 0) {
            $conn->query("INSERT INTO siaran_wa_log (template_id, agen_id, whatsapp, status, sent_at) 
                          VALUES ($tid, $aid, '$wa', 'terkirim', NOW()) 
                          ON DUPLICATE KEY UPDATE status='terkirim', sent_at=NOW()");
            echo json_encode(['success' => true, 'message' => 'Status kirim tercatat']);
            exit;
        }
    } elseif ($action === 'reset_log') {
        $conn->query("DELETE FROM siaran_wa_log WHERE template_id = $tid");
        echo json_encode(['success' => true, 'message' => 'Riwayat kirim berhasil di-reset']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
    exit;
}

// 3. PROSES SIMPAN / UPDATE TEMPLATE SIARAN
$pesan_sukses = null;
$pesan_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_template'])) {
    $template_id = (int)($_POST['template_id'] ?? 0);
    $judul = trim($_POST['judul'] ?? '');
    $pesan = trim($_POST['pesan'] ?? '');
    $gambar_url_lama = $_POST['gambar_url_lama'] ?? '';
    $gambar_url = $gambar_url_lama;

    // Handle Upload Gambar Baru
    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gambar_file']['tmp_name'];
        $file_name = $_FILES['gambar_file']['name'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (in_array($ext, $allowed)) {
            $new_filename = 'siaran_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $destination = $upload_dir . $new_filename;
            if (move_uploaded_file($file_tmp, $destination)) {
                $gambar_url = 'upload/siaran/' . $new_filename;
            } else {
                $pesan_error = "Gagal mengunggah file gambar ke server.";
            }
        } else {
            $pesan_error = "Format file gambar harus JPG, PNG, WEBP, atau GIF.";
        }
    }

    // Jika user memilih hapus gambar
    if (isset($_POST['hapus_gambar']) && $_POST['hapus_gambar'] == '1') {
        $gambar_url = '';
    }

    if (!$pesan_error) {
        if ($template_id > 0) {
            $stmt = $conn->prepare("UPDATE siaran_wa_template SET judul=?, pesan=?, gambar_url=? WHERE id=?");
            $stmt->bind_param("sssi", $judul, $pesan, $gambar_url, $template_id);
            $stmt->execute();
            $pesan_sukses = "Template siaran berhasil diperbarui!";
        } else {
            $stmt = $conn->prepare("INSERT INTO siaran_wa_template (judul, pesan, gambar_url) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $judul, $pesan, $gambar_url);
            $stmt->execute();
            $template_id = $stmt->insert_id;
            $pesan_sukses = "Template siaran baru berhasil disimpan!";
        }
    }
}

// 4. PROSES HAPUS TEMPLATE
if (isset($_GET['hapus_template'])) {
    $del_id = (int)$_GET['hapus_template'];
    if ($del_id > 1) { // Proteksi template ID 1
        $conn->query("DELETE FROM siaran_wa_template WHERE id = $del_id");
        $conn->query("DELETE FROM siaran_wa_log WHERE template_id = $del_id");
        header("Location: admin-siaran-wa.php?msg=deleted");
        exit;
    }
}

// 5. AMBIL DAFTAR TEMPLATE & TEMPLATE AKTIF
$all_templates = [];
$res_tpl = $conn->query("SELECT * FROM siaran_wa_template ORDER BY id ASC");
if ($res_tpl) {
    while ($r = $res_tpl->fetch_assoc()) {
        $all_templates[] = $r;
    }
}

$active_template_id = (int)($_GET['template_id'] ?? ($all_templates[0]['id'] ?? 1));
$active_template = null;
foreach ($all_templates as $t) {
    if ($t['id'] == $active_template_id) {
        $active_template = $t;
        break;
    }
}
if (!$active_template && !empty($all_templates)) {
    $active_template = $all_templates[0];
    $active_template_id = $active_template['id'];
}

// 6. AMBIL DATA SELURUH AGEN / MITRA BESERTA STATUS LOG SIARAN
$active_agen_list = [];
$sql_agen = "SELECT a.*, 
             CASE WHEN l.status = 'terkirim' THEN 1 ELSE 0 END AS is_terkirim,
             l.sent_at 
             FROM agen a 
             LEFT JOIN siaran_wa_log l ON (l.agen_id = a.id AND l.template_id = $active_template_id)
             ORDER BY is_terkirim ASC, a.id DESC";
$res_agen = $conn->query($sql_agen);
if ($res_agen) {
    while ($row = $res_agen->fetch_assoc()) {
        $active_agen_list[] = $row;
    }
}

// Hitung Statistik Siaran
$total_agen = count($active_agen_list);
$total_terkirim = 0;
foreach ($active_agen_list as $ag) {
    if (!empty($ag['is_terkirim'])) $total_terkirim++;
}
$total_belum = $total_agen - $total_terkirim;
$persen_kirim = $total_agen > 0 ? round(($total_terkirim / $total_agen) * 100) : 0;

// Host URL dinamis untuk link referral
$http_host = $_SERVER['HTTP_HOST'] ?? 'villaquranindonesia.com';
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$base_domain_url = $protocol . $http_host;

$active_menu = 'siaran_wa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siaran WhatsApp Mitra | Ruang Marketing Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .wa-bubble-bg {
            background-color: #e6f7ec;
            background-image: radial-gradient(#d1e7dd 1px, transparent 1px);
            background-size: 16px 16px;
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR MARKETING -->
    <?php include 'sidebar-marketing.php'; ?>

    <!-- MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER TOP BAR -->
        <header class="h-16 bg-white shadow-xs border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-20 flex-shrink-0">
            <div class="flex items-center gap-3">
                <button id="open-sidebar" class="text-slate-500 hover:text-slate-700 focus:outline-none md:hidden p-1.5 rounded-lg hover:bg-slate-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-100 text-[#0b8478] flex items-center justify-center font-bold text-sm">
                        <i class="fas fa-broadcast-tower"></i>
                    </div>
                    <div>
                        <h1 class="text-base sm:text-lg font-black text-slate-900 leading-tight">Siaran WhatsApp Mitra</h1>
                        <p class="text-[10px] text-slate-500 font-medium">Sistem Blast Semi-Otomatis Aman Anti-Banned</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="dashboard-marketing.php" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-arrow-left"></i> <span class="hidden sm:inline">Ruang Marketing</span>
                </a>
                <a href="data-agen.php" class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fas fa-users"></i> <span class="hidden sm:inline">Kelola Data Agen</span>
                </a>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 custom-scrollbar space-y-6">
            
            <!-- ALERT NOTIFIKASI -->
            <?php if (isset($pesan_sukses) || (isset($_GET['msg']) && $_GET['msg'] === 'deleted')): ?>
                <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-bold flex items-center justify-between shadow-xs">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-circle-check text-emerald-600 text-lg"></i>
                        <?= $pesan_sukses ?? 'Template siaran berhasil dihapus.' ?>
                    </span>
                    <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <?php if (isset($pesan_error)): ?>
                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-bold flex items-center justify-between shadow-xs">
                    <span class="flex items-center gap-2">
                        <i class="fas fa-triangle-exclamation text-rose-600 text-lg"></i>
                        <?= $pesan_error ?>
                    </span>
                    <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700"><i class="fas fa-times"></i></button>
                </div>
            <?php endif; ?>

            <!-- STATISTIK & HIGHLIGHT PROGRESS SIARAN -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Mitra</p>
                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 leading-none mt-0.5"><?= $total_agen ?></h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sudah Terkirim</p>
                        <h3 id="stat-terkirim" class="text-xl sm:text-2xl font-black text-emerald-600 leading-none mt-0.5"><?= $total_terkirim ?></h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Belum Dikirim</p>
                        <h3 id="stat-belum" class="text-xl sm:text-2xl font-black text-amber-600 leading-none mt-0.5"><?= $total_belum ?></h3>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl font-bold flex-shrink-0">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Progress</p>
                            <span id="stat-persen-text" class="text-xs font-black text-indigo-700"><?= $persen_kirim ?>%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 mt-1.5 overflow-hidden">
                            <div id="stat-persen-bar" class="bg-gradient-to-r from-emerald-500 to-[#0b8478] h-2 rounded-full transition-all duration-300" style="width: <?= $persen_kirim ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION ATAS: DUA KOLOM (KIRI: EDITOR TEMPLATE & GAMBAR, KANAN: PREVIEW CHAT WHATSAPP) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- KOLOM KIRI: FORM EDITOR SIARAN & UPLOAD GAMBAR (7/12) -->
                <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
                    <div class="p-5 bg-gradient-to-r from-slate-900 to-slate-800 text-white flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <i class="fas fa-pen-to-square text-amber-400 text-lg"></i>
                            <div>
                                <h2 class="font-extrabold text-sm sm:text-base leading-tight">1. Susun Pesan & Lampiran Brosur</h2>
                                <p class="text-[11px] text-slate-300 font-normal">Gunakan tag dinamis agar otomatis menyisipkan nama & link mitra</p>
                            </div>
                        </div>

                        <!-- Dropdown Pilihan Template -->
                        <div class="flex items-center gap-2">
                            <form method="GET" action="admin-siaran-wa.php" class="flex items-center gap-1.5">
                                <select name="template_id" onchange="this.form.submit()" class="bg-slate-700 text-white border border-slate-600 rounded-xl px-2.5 py-1.5 text-xs font-bold focus:outline-none focus:ring-2 focus:ring-amber-400">
                                    <?php foreach ($all_templates as $tpl_item): ?>
                                        <option value="<?= $tpl_item['id'] ?>" <?= $tpl_item['id'] == $active_template_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tpl_item['judul']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="admin-siaran-wa.php" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-4 flex-1 flex flex-col justify-between">
                        <input type="hidden" name="template_id" value="<?= $active_template['id'] ?? 0 ?>">
                        <input type="hidden" name="gambar_url_lama" value="<?= htmlspecialchars($active_template['gambar_url'] ?? '') ?>">

                        <div class="space-y-4">
                            <!-- Judul Template -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Judul / Kategori Siaran</label>
                                <input type="text" name="judul" required value="<?= htmlspecialchars($active_template['judul'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0b8478] bg-slate-50/50" placeholder="Contoh: Undangan Brosur Digital & SPMB Santri Baru">
                            </div>

                            <!-- Upload Gambar Lampiran / Flyer -->
                            <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200">
                                <label class="block text-xs font-bold text-slate-700 mb-1.5 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5"><i class="fas fa-image text-[#0b8478]"></i> Lampiran Gambar Poster / Flyer (Opsional)</span>
                                    <span class="text-[10px] text-slate-400 font-normal">Format: JPG, PNG, WEBP</span>
                                </label>
                                
                                <div class="flex flex-col sm:flex-row items-center gap-3">
                                    <input type="file" name="gambar_file" id="gambar_file" accept="image/*" onchange="previewUploadImage(this)" class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0b8478] file:text-white hover:file:bg-[#086a60] file:cursor-pointer border border-slate-200 rounded-xl bg-white p-1">
                                    
                                    <?php if (!empty($active_template['gambar_url'])): ?>
                                        <div id="img-preview-box" class="flex items-center gap-2 p-1.5 bg-white border border-slate-200 rounded-xl flex-shrink-0">
                                            <img id="img-thumb" src="<?= htmlspecialchars($active_template['gambar_url']) ?>" alt="Lampiran" class="w-10 h-10 object-cover rounded-lg">
                                            <label class="text-[11px] font-bold text-rose-600 flex items-center gap-1 cursor-pointer pr-2">
                                                <input type="checkbox" name="hapus_gambar" value="1" onchange="toggleHapusGambar(this)" class="rounded text-rose-600"> Hapus
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div id="img-preview-box" class="hidden flex items-center gap-2 p-1.5 bg-white border border-slate-200 rounded-xl flex-shrink-0">
                                            <img id="img-thumb" src="" alt="Lampiran" class="w-10 h-10 object-cover rounded-lg">
                                            <span class="text-[10px] text-emerald-600 font-bold pr-2">Gambar Dipilih</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Tombol Cepat Sisipkan Tag -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700">Format Isi Pesan WhatsApp</label>
                                    <span class="text-[10px] text-slate-400 italic">Klik tombol tag untuk menyisipkan otomatis</span>
                                </div>
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <button type="button" onclick="insertTag('{nama}')" class="px-2.5 py-1 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-[11px] font-bold transition">
                                        + {nama}
                                    </button>
                                    <button type="button" onclick="insertTag('{link_brosur}')" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-[11px] font-bold transition">
                                        + {link_brosur}
                                    </button>
                                    <button type="button" onclick="insertTag('{link_spmb}')" class="px-2.5 py-1 bg-teal-50 hover:bg-teal-100 text-teal-800 border border-teal-200 rounded-lg text-[11px] font-bold transition">
                                        + {link_spmb}
                                    </button>
                                    <button type="button" onclick="insertTag('{link_biaya}')" class="px-2.5 py-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 rounded-lg text-[11px] font-bold transition">
                                        + {link_biaya}
                                    </button>
                                    <button type="button" onclick="insertTag('{link_beranda}')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 rounded-lg text-[11px] font-bold transition">
                                        + {link_beranda}
                                    </button>
                                    <button type="button" onclick="insertTag('{kode_ref}')" class="px-2.5 py-1 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 rounded-lg text-[11px] font-bold transition">
                                        + {kode_ref}
                                    </button>
                                </div>

                                <textarea name="pesan" id="template_pesan" rows="11" required oninput="renderLivePreview()" class="w-full px-3.5 py-3 rounded-2xl border border-slate-200 text-xs font-mono text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#0b8478] bg-slate-50/50 leading-relaxed custom-scrollbar"><?= htmlspecialchars($active_template['pesan'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Tombol Aksi Simpan -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <?php if ($active_template_id > 1): ?>
                                <a href="admin-siaran-wa.php?hapus_template=<?= $active_template_id ?>" onclick="return confirm('Hapus template siaran ini?');" class="text-xs font-bold text-rose-600 hover:underline">
                                    <i class="fas fa-trash mr-1"></i> Hapus Template Ini
                                </a>
                            <?php else: ?>
                                <div></div>
                            <?php endif; ?>

                            <div class="flex items-center gap-2">
                                <button type="submit" name="simpan_template" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-[#0b8478] hover:from-emerald-700 hover:to-[#086a60] text-white text-xs font-black shadow-md shadow-teal-900/10 transition flex items-center gap-2">
                                    <i class="fas fa-save"></i> Simpan Perubahan Template
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- KOLOM KANAN: LIVE SIMULATOR PREVIEW WHATSAPP (5/12) -->
                <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden flex flex-col">
                    <div class="p-4 bg-[#075e54] text-white flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                                <i class="fab fa-whatsapp text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-xs leading-tight">Live Simulator Chat WA</h3>
                                <p class="text-[10px] text-emerald-200">Tampilan pesan yang diterima agen</p>
                            </div>
                        </div>

                        <!-- Dropdown Pilih Agen Simulasi -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] text-emerald-100 font-bold hidden sm:inline">Tes Agen:</span>
                            <select id="simulasi_agen_select" onchange="renderLivePreview()" class="bg-[#054c44] text-white border border-emerald-600/60 rounded-xl px-2.5 py-1 text-[11px] font-bold focus:outline-none">
                                <?php if (empty($active_agen_list)): ?>
                                    <option value="default" data-nama="Ustadz Mitra (Contoh)" data-wa="081234567890" data-ref="081234567890">Ustadz Mitra (Contoh)</option>
                                <?php else: ?>
                                    <?php foreach ($active_agen_list as $ag_opt): ?>
                                        <option value="<?= $ag_opt['id'] ?>" data-nama="<?= htmlspecialchars($ag_opt['nama']) ?>" data-wa="<?= htmlspecialchars($ag_opt['whatsapp']) ?>" data-ref="<?= htmlspecialchars($ag_opt['kode_ref'] ?: $ag_opt['whatsapp']) ?>">
                                            <?= htmlspecialchars($ag_opt['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- AREA SIMULASI CHAT BUBBLE -->
                    <div class="flex-1 p-4 wa-bubble-bg overflow-y-auto max-h-[500px] custom-scrollbar flex flex-col justify-start">
                        
                        <!-- CHAT BUBBLE CONTAINER -->
                        <div class="bg-white rounded-2xl rounded-tl-xs p-3.5 shadow-sm border border-slate-200/80 max-w-[95%] text-slate-800 text-xs leading-relaxed space-y-2.5">
                            
                            <!-- Thumbnail Gambar Flyer jika ada -->
                            <div id="preview-image-container" class="<?= empty($active_template['gambar_url']) ? 'hidden' : '' ?>">
                                <img id="preview-img-display" src="<?= htmlspecialchars($active_template['gambar_url'] ?? '') ?>" alt="Flyer Preview" class="w-full h-44 sm:h-52 object-cover rounded-xl shadow-xs border border-slate-100">
                                <div class="mt-1 flex items-center justify-between text-[10px] text-slate-500 font-medium px-1">
                                    <span><i class="fas fa-paperclip text-emerald-600 mr-1"></i> Lampiran Flyer Promo</span>
                                    <span class="text-emerald-700 font-bold">Siap Disalin / Paste</span>
                                </div>
                            </div>

                            <!-- Pesan Teks Terformat -->
                            <div id="preview-text-display" class="whitespace-pre-wrap break-words text-[11px] sm:text-xs text-slate-800">
                                <!-- Diisi secara dinamis oleh JavaScript -->
                            </div>

                            <!-- Waktu & Centang Biru WA -->
                            <div class="flex items-center justify-end gap-1 text-[10px] text-slate-400 pt-1">
                                <span><?= date('H:i') ?></span>
                                <i class="fas fa-check-double text-sky-500 text-[11px]"></i>
                            </div>
                        </div>

                        <div class="mt-3 p-2.5 rounded-xl bg-amber-50 border border-amber-200/80 text-[11px] text-amber-800 flex items-start gap-2 shadow-xs">
                            <i class="fas fa-lightbulb text-amber-500 mt-0.5"></i>
                            <p class="leading-tight">
                                <strong>Tips Bebas Banned:</strong> Sistem ini membuka WhatsApp resmi per agen. Tidak ada robot pihak ketiga yang memicu pemblokiran nomor oleh WhatsApp.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION BAWAH: TABEL DAFTAR AGEN & SISTEM BLAST SEMI-OTOMATIS (1-PER-1 SAFE BLAST) -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
                
                <!-- HEADER TABEL DENGAN FILTER & CONTROLLER -->
                <div class="p-5 sm:p-6 bg-slate-900 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base sm:text-lg font-black tracking-tight flex items-center gap-2">
                            <span>2. Daftar Mitra & Kirim Siaran 1-per-1</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold"><?= $total_agen ?> Mitra</span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">Klik tombol kirim per nomor, atau gunakan asisten <span class="text-amber-400 font-bold">"Kirim ke Agen Berikutnya"</span> untuk alur cepat.</p>
                    </div>

                    <!-- KONTROL PINTAR & AKSI CEPAT -->
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" onclick="startSemiAutoWalker()" class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 text-xs font-black shadow-md transition flex items-center gap-2 active:scale-95">
                            <i class="fas fa-play"></i>
                            <span>Kirim ke Agen Berikutnya</span>
                            <span id="walker-badge" class="px-1.5 py-0.5 bg-slate-900/30 text-slate-950 rounded-md text-[10px] font-extrabold"><?= $total_belum ?> Sisa</span>
                        </button>

                        <button type="button" onclick="resetBroadcastLog()" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700 text-xs font-bold transition flex items-center gap-1.5" title="Reset status kirim jika ingin siaran ulang">
                            <i class="fas fa-rotate-left"></i> <span class="hidden sm:inline">Reset Status</span>
                        </button>
                    </div>
                </div>

                <!-- FILTER BAR (PENCARIAN & STATUS FILTER) -->
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <span class="text-xs font-bold text-slate-500">Filter:</span>
                        <button type="button" onclick="filterTable('all')" class="btn-filter px-3 py-1.5 rounded-lg text-xs font-bold bg-[#0b8478] text-white shadow-xs" data-filter="all">Semua (<?= $total_agen ?>)</button>
                        <button type="button" onclick="filterTable('belum')" class="btn-filter px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100" data-filter="belum">Belum Dikirim (<?= $total_belum ?>)</button>
                        <button type="button" onclick="filterTable('terkirim')" class="btn-filter px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100" data-filter="terkirim">Terkirim (<?= $total_terkirim ?>)</button>
                    </div>

                    <div class="relative w-full sm:w-72">
                        <input type="text" id="searchAgentInput" onkeyup="searchAgentTable()" placeholder="Cari nama / nomor WA..." class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-xs font-medium focus:outline-none focus:ring-2 focus:ring-[#0b8478]">
                        <i class="fas fa-search absolute left-3 top-2.5 text-slate-400 text-xs"></i>
                    </div>
                </div>

                <!-- TABEL AGEN & TOMBOL BLAST -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs" id="table-agen-siaran">
                        <thead class="bg-slate-100 text-slate-600 uppercase font-black tracking-wider text-[10px]">
                            <tr>
                                <th class="px-5 py-3.5">Nama Mitra (Agen)</th>
                                <th class="px-5 py-3.5">Nomor WhatsApp</th>
                                <th class="px-5 py-3.5">Link Referral Mitra</th>
                                <th class="px-5 py-3.5 text-center">Status Siaran</th>
                                <th class="px-5 py-3.5 text-right">Aksi Kirim</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <?php if (empty($active_agen_list)): ?>
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-slate-400 italic">
                                        <i class="fas fa-users-slash text-3xl mb-2"></i>
                                        <p>Belum ada data agen terdaftar. Silakan tambahkan mitra di menu <a href="data-agen.php" class="text-emerald-600 underline font-bold">Data Agen</a>.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($active_agen_list as $idx => $agen): 
                                    $is_sent = !empty($agen['is_terkirim']);
                                    $wa_clean = preg_replace('/[^0-9]/', '', $agen['whatsapp']);
                                    if (substr($wa_clean, 0, 1) === '0') {
                                        $wa_clean = '62' . substr($wa_clean, 1);
                                    }
                                    $ref_code = !empty($agen['kode_ref']) ? $agen['kode_ref'] : $agen['whatsapp'];
                                ?>
                                <tr id="row-agen-<?= $agen['id'] ?>" class="hover:bg-slate-50 transition row-agen <?= $is_sent ? 'status-terkirim' : 'status-belum' ?>" data-id="<?= $agen['id'] ?>" data-nama="<?= htmlspecialchars($agen['nama']) ?>" data-wa="<?= htmlspecialchars($agen['whatsapp']) ?>" data-waclean="<?= $wa_clean ?>" data-ref="<?= htmlspecialchars($ref_code) ?>" data-sent="<?= $is_sent ? '1' : '0' ?>">
                                    <td class="px-5 py-3.5">
                                        <div class="font-extrabold text-slate-900 text-xs sm:text-sm flex items-center gap-2">
                                            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-700 font-black text-[10px] flex items-center justify-center flex-shrink-0">
                                                <?= $idx + 1 ?>
                                            </span>
                                            <?= htmlspecialchars($agen['nama']) ?>
                                        </div>
                                        <div class="text-[10px] text-slate-400 pl-8"><?= htmlspecialchars($agen['bank'] ?? 'Bank') ?> - <?= htmlspecialchars($agen['rekening'] ?? '-') ?></div>
                                    </td>

                                    <td class="px-5 py-3.5 whitespace-nowrap font-mono font-bold text-slate-700">
                                        <i class="fab fa-whatsapp text-emerald-500 mr-1.5"></i> <?= htmlspecialchars($agen['whatsapp']) ?>
                                    </td>

                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200 text-slate-600 font-mono text-[10px]">
                                                ?ref=<?= htmlspecialchars($ref_code) ?>
                                            </span>
                                            <button type="button" onclick="copyReferralLink('<?= htmlspecialchars($ref_code) ?>')" class="text-slate-400 hover:text-slate-700 text-xs p-1" title="Salin Link Brosur Lengkap">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span id="badge-status-<?= $agen['id'] ?>" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold <?= $is_sent ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200' ?>">
                                            <i class="fas <?= $is_sent ? 'fa-check-circle text-emerald-600' : 'fa-clock text-amber-600' ?>"></i>
                                            <span class="status-label"><?= $is_sent ? 'Terkirim ✓' : 'Belum Dikirim' ?></span>
                                        </span>
                                    </td>

                                    <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-1.5">
                                            <button type="button" onclick="copyFlyerImage()" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center gap-1" title="Salin Gambar ke Clipboard (Ctrl+V di WA)">
                                                <i class="fas fa-clone"></i>
                                            </button>

                                            <button type="button" onclick="kirimWhatsAppSatuAgen(<?= $agen['id'] ?>)" class="btn-kirim-wa px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-xs hover:shadow-md transition flex items-center gap-1.5 active:scale-95">
                                                <i class="fab fa-whatsapp text-sm"></i> Kirim WA
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- TOAST NOTIFIKASI -->
    <div id="toast-notif" class="fixed bottom-6 right-6 z-50 transform translate-y-20 opacity-0 transition-all duration-300 bg-slate-900 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-700 text-xs font-bold pointer-events-none">
        <i id="toast-icon" class="fas fa-check-circle text-emerald-400 text-lg"></i>
        <span id="toast-msg">Notifikasi</span>
    </div>

    <!-- SCRIPT CLIENT ENGINE UNTUK LOGIKA BROADCAST SEMI-OTOMATIS -->
    <script>
        const BASE_DOMAIN = "<?= $base_domain_url ?>";
        const ACTIVE_TEMPLATE_ID = <?= $active_template_id ?>;
        const GAMBAR_URL = "<?= !empty($active_template['gambar_url']) ? $base_domain_url . '/' . ltrim($active_template['gambar_url'], '/') : '' ?>";

        // 1. Live Preview Generator
        function renderLivePreview() {
            const textarea = document.getElementById('template_pesan');
            const selectAgen = document.getElementById('simulasi_agen_select');
            const previewDisplay = document.getElementById('preview-text-display');
            if (!textarea || !previewDisplay) return;

            const selectedOpt = selectAgen ? selectAgen.options[selectAgen.selectedIndex] : null;
            const namaAgen = selectedOpt ? selectedOpt.getAttribute('data-nama') : 'Ustadz Mitra';
            const waAgen = selectedOpt ? selectedOpt.getAttribute('data-wa') : '081234567890';
            const refAgen = selectedOpt ? selectedOpt.getAttribute('data-ref') : '081234567890';

            let text = textarea.value;
            text = text.replace(/\{nama\}/g, namaAgen);
            text = text.replace(/\{whatsapp\}/g, waAgen);
            text = text.replace(/\{kode_ref\}/g, refAgen);
            text = text.replace(/\{link_beranda\}/g, BASE_DOMAIN + "/?ref=" + encodeURIComponent(refAgen));
            text = text.replace(/\{link_brosur\}/g, BASE_DOMAIN + "/brosur.php?ref=" + encodeURIComponent(refAgen));
            text = text.replace(/\{link_spmb\}/g, BASE_DOMAIN + "/daftar-spmb.html?ref=" + encodeURIComponent(refAgen));
            text = text.replace(/\{link_biaya\}/g, BASE_DOMAIN + "/biaya.html?ref=" + encodeURIComponent(refAgen));
            text = text.replace(/\{link_gambar\}/g, GAMBAR_URL);

            previewDisplay.textContent = text;
        }

        // 2. Format Pesan Spesifik untuk Satu Agen
        function formatPesanUntukAgen(nama, wa, ref) {
            const textarea = document.getElementById('template_pesan');
            let text = textarea.value;
            text = text.replace(/\{nama\}/g, nama);
            text = text.replace(/\{whatsapp\}/g, wa);
            text = text.replace(/\{kode_ref\}/g, ref);
            text = text.replace(/\{link_beranda\}/g, BASE_DOMAIN + "/?ref=" + encodeURIComponent(ref));
            text = text.replace(/\{link_brosur\}/g, BASE_DOMAIN + "/brosur.php?ref=" + encodeURIComponent(ref));
            text = text.replace(/\{link_spmb\}/g, BASE_DOMAIN + "/daftar-spmb.html?ref=" + encodeURIComponent(ref));
            text = text.replace(/\{link_biaya\}/g, BASE_DOMAIN + "/biaya.html?ref=" + encodeURIComponent(ref));
            text = text.replace(/\{link_gambar\}/g, GAMBAR_URL);
            return text;
        }

        // 3. Kirim WhatsApp Satu Agen & Catat Status
        async function kirimWhatsAppSatuAgen(agenId) {
            const row = document.getElementById('row-agen-' + agenId);
            if (!row) return;

            const nama = row.getAttribute('data-nama');
            const wa = row.getAttribute('data-wa');
            const waClean = row.getAttribute('data-waclean');
            const ref = row.getAttribute('data-ref');

            const formattedMsg = formatPesanUntukAgen(nama, wa, ref);

            // Buka WhatsApp Web / App
            const waUrl = "https://api.whatsapp.com/send?phone=" + waClean + "&text=" + encodeURIComponent(formattedMsg);
            window.open(waUrl, '_blank');

            // Salin Teks ke Clipboard secara otomatis agar jika user mau paste ulang mudah
            try {
                navigator.clipboard.writeText(formattedMsg);
            } catch (err) {}

            // Tandai terkirim di DOM secara langsung
            markRowAsSent(agenId);

            // Kirim log ke backend via AJAX
            const fd = new FormData();
            fd.append('ajax_action', 'mark_sent');
            fd.append('template_id', ACTIVE_TEMPLATE_ID);
            fd.append('agen_id', agenId);
            fd.append('whatsapp', wa);

            fetch('admin-siaran-wa.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    updateStatsDisplay();
                })
                .catch(e => console.log('Log save error:', e));

            showToast("Pesan & link untuk " + nama + " dibuka di WhatsApp!");
        }

        // 4. Update Tampilan Row Menjadi Terkirim
        function markRowAsSent(agenId) {
            const row = document.getElementById('row-agen-' + agenId);
            if (!row) return;

            row.setAttribute('data-sent', '1');
            row.classList.remove('status-belum');
            row.classList.add('status-terkirim');

            const badge = document.getElementById('badge-status-' + agenId);
            if (badge) {
                badge.className = "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200";
                badge.innerHTML = '<i class="fas fa-check-circle text-emerald-600"></i> <span class="status-label">Terkirim ✓</span>';
            }
        }

        // 5. Asisten Blast Bertahap (Semi-Auto Walker)
        function startSemiAutoWalker() {
            // Cari baris pertama yang belum terkirim
            const pendingRows = document.querySelectorAll('.row-agen[data-sent="0"]');
            if (pendingRows.length === 0) {
                alert("Alhamdulillah! Seluruh agen dalam daftar ini sudah terkirim pesan siaran.");
                return;
            }

            const nextRow = pendingRows[0];
            const nextId = nextRow.getAttribute('data-id');
            const nextNama = nextRow.getAttribute('data-nama');

            // Highlight baris
            nextRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            nextRow.classList.add('bg-amber-100');
            setTimeout(() => {
                nextRow.classList.remove('bg-amber-100');
            }, 2500);

            // Trigger kirim
            kirimWhatsAppSatuAgen(nextId);
        }

        // 6. Reset Log Status Siaran
        function resetBroadcastLog() {
            if (!confirm('Apakah Anda yakin ingin me-reset status kirim seluruh agen untuk template ini?')) {
                return;
            }

            const fd = new FormData();
            fd.append('ajax_action', 'reset_log');
            fd.append('template_id', ACTIVE_TEMPLATE_ID);

            fetch('admin-siaran-wa.php', { method: 'POST', body: fd })
                .then(res => res.json())
                .then(data => {
                    document.querySelectorAll('.row-agen').forEach(row => {
                        row.setAttribute('data-sent', '0');
                        row.classList.remove('status-terkirim');
                        row.classList.add('status-belum');
                        const aid = row.getAttribute('data-id');
                        const badge = document.getElementById('badge-status-' + aid);
                        if (badge) {
                            badge.className = "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200";
                            badge.innerHTML = '<i class="fas fa-clock text-amber-600"></i> <span class="status-label">Belum Dikirim</span>';
                        }
                    });
                    updateStatsDisplay();
                    showToast("Status siaran berhasil di-reset!");
                })
                .catch(e => alert("Gagal me-reset status kirim."));
        }

        // 7. Update Widget Statistik
        function updateStatsDisplay() {
            const allRows = document.querySelectorAll('.row-agen');
            const sentRows = document.querySelectorAll('.row-agen[data-sent="1"]');
            const total = allRows.length;
            const terkirim = sentRows.length;
            const belum = total - terkirim;
            const persen = total > 0 ? Math.round((terkirim / total) * 100) : 0;

            const elTerkirim = document.getElementById('stat-terkirim');
            const elBelum = document.getElementById('stat-belum');
            const elPersenText = document.getElementById('stat-persen-text');
            const elPersenBar = document.getElementById('stat-persen-bar');
            const elWalkerBadge = document.getElementById('walker-badge');

            if (elTerkirim) elTerkirim.textContent = terkirim;
            if (elBelum) elBelum.textContent = belum;
            if (elPersenText) elPersenText.textContent = persen + '%';
            if (elPersenBar) elPersenBar.style.width = persen + '%';
            if (elWalkerBadge) elWalkerBadge.textContent = belum + ' Sisa';
        }

        // 8. Salin Gambar Flyer ke Clipboard
        async function copyFlyerImage() {
            if (!GAMBAR_URL) {
                alert("Belum ada gambar yang diunggah untuk template ini.");
                return;
            }
            try {
                const response = await fetch(GAMBAR_URL);
                const blob = await response.blob();
                await navigator.clipboard.write([
                    new ClipboardItem({ [blob.type]: blob })
                ]);
                showToast("Gambar flyer berhasil disalin! Tinggal Ctrl+V di WhatsApp.");
            } catch (err) {
                // Fallback: download gambar
                const a = document.createElement('a');
                a.href = GAMBAR_URL;
                a.download = 'flyer_villa_quran.jpg';
                a.target = '_blank';
                a.click();
                showToast("Gambar diunduh. Silakan lampirkan di WhatsApp.");
            }
        }

        // 9. Salin Link Referral Khusus
        function copyReferralLink(refCode) {
            const url = BASE_DOMAIN + "/brosur.php?ref=" + encodeURIComponent(refCode);
            navigator.clipboard.writeText(url);
            showToast("Link brosur ?ref=" + refCode + " disalin!");
        }

        // 10. Sisipkan Tag ke Textarea
        function insertTag(tag) {
            const textarea = document.getElementById('template_pesan');
            if (!textarea) return;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const val = textarea.value;
            textarea.value = val.substring(0, start) + tag + val.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
            textarea.focus();
            renderLivePreview();
        }

        // 11. Preview File Upload Gambar Baru
        function previewUploadImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const thumb = document.getElementById('img-thumb');
                    const box = document.getElementById('img-preview-box');
                    const disp = document.getElementById('preview-img-display');
                    const cont = document.getElementById('preview-image-container');
                    
                    if (thumb) thumb.src = e.target.result;
                    if (box) box.classList.remove('hidden');
                    if (disp) disp.src = e.target.result;
                    if (cont) cont.classList.remove('hidden');
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function toggleHapusGambar(cb) {
            const cont = document.getElementById('preview-image-container');
            if (cont) {
                if (cb.checked) {
                    cont.classList.add('hidden');
                } else {
                    cont.classList.remove('hidden');
                }
            }
        }

        // 12. Filter Tabel Berdasarkan Status
        function filterTable(type) {
            document.querySelectorAll('.btn-filter').forEach(b => {
                b.className = "btn-filter px-3 py-1.5 rounded-lg text-xs font-bold bg-white text-slate-700 border border-slate-200 hover:bg-slate-100";
            });
            const activeBtn = document.querySelector(`.btn-filter[data-filter="${type}"]`);
            if (activeBtn) {
                activeBtn.className = "btn-filter px-3 py-1.5 rounded-lg text-xs font-bold bg-[#0b8478] text-white shadow-xs";
            }

            const rows = document.querySelectorAll('.row-agen');
            rows.forEach(r => {
                const isSent = r.getAttribute('data-sent');
                if (type === 'all') {
                    r.style.display = '';
                } else if (type === 'terkirim') {
                    r.style.display = (isSent === '1') ? '' : 'none';
                } else if (type === 'belum') {
                    r.style.display = (isSent === '0') ? '' : 'none';
                }
            });
        }

        // 13. Search Table Filter
        function searchAgentTable() {
            const input = document.getElementById('searchAgentInput');
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll('.row-agen');
            rows.forEach(r => {
                const nama = r.getAttribute('data-nama').toLowerCase();
                const wa = r.getAttribute('data-wa').toLowerCase();
                if (nama.includes(filter) || wa.includes(filter)) {
                    r.style.display = '';
                } else {
                    r.style.display = 'none';
                }
            });
        }

        // 14. Toast Notification
        function showToast(msg) {
            const toast = document.getElementById('toast-notif');
            const msgEl = document.getElementById('toast-msg');
            if (toast && msgEl) {
                msgEl.textContent = msg;
                toast.classList.remove('translate-y-20', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
                setTimeout(() => {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('translate-y-20', 'opacity-0');
                }, 3000);
            }
        }

        // Init on load
        document.addEventListener('DOMContentLoaded', () => {
            renderLivePreview();
        });
    </script>
</body>
</html>
