<?php
require_once 'auth-orangtua.php';
require_once 'koneksi.php';

$orangtua_id = $_SESSION['orangtua_id'];
$santri_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$active_menu = 'dashboard_orangtua';

// 1. Keamanan: Cek apakah santri ini milik orang tua yang login
if ($orangtua_id != 9999) {
    $check = $conn->query("SELECT id FROM buku_induk_santri WHERE id = $santri_id AND id_orangtua = $orangtua_id");
    if (!$check || $check->num_rows == 0) {
        die("Akses Ditolak: Anda tidak memiliki otoritas melihat data santri ini.");
    }
}

// 2. Ambil data santri
$res_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id");
$data_santri = $res_s->fetch_assoc();
if (!$data_santri) {
    die("Santri tidak ditemukan.");
}

// 3. Ambil data konseling karir & AI
$res_c = $conn->query("SELECT * FROM counseling_karir WHERE santri_id = $santri_id");
$counseling = $res_c->fetch_assoc();

// 4. Ambil nilai rata-rata santri untuk display ringkasan
$res_g = $conn->query("SELECT l.kategori_mapel, AVG(l.nilai) as rata_nilai
                       FROM (SELECT l.nilai, m.kategori_mapel FROM leger_nilai l JOIN master_mapel m ON l.mapel_id = m.id WHERE l.santri_id = $santri_id) l
                       GROUP BY l.kategori_mapel");
$avg_diknas = 0;
$avg_diniyah = 0;
if ($res_g) {
    while ($row = $res_g->fetch_assoc()) {
        if ($row['kategori_mapel'] === 'Diknas') {
            $avg_diknas = round($row['rata_nilai'], 1);
        } elseif ($row['kategori_mapel'] === 'Diniyah') {
            $avg_diniyah = round($row['rata_nilai'], 1);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bimbingan Karir & Analisis PTN | Ruang Orang Tua</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Font Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Markdown Parser Library for AI output -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <?php include 'sidebar-orangtua.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <a href="dashboard-orangtua.php" class="text-purple-600 hover:text-purple-800 mr-4 font-bold text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
                <h2 class="font-bold text-gray-800 hidden sm:block">Perencanaan Karir & Kelayakan PTN</h2>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-xs font-semibold px-2.5 py-1 bg-purple-100 text-purple-800 rounded-full">
                    Hasil Pemetaan Musyrif
                </span>
            </div>
        </header>

        <!-- MAIN LAYOUT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            
            <!-- Profil Singkat Anak -->
            <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center">
                    <img src="<?= !empty($data_santri['foto_santri']) ? $data_santri['foto_santri'] : 'https://via.placeholder.com/100' ?>" class="w-16 h-16 rounded-full object-cover border-2 border-purple-200 mr-4">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base leading-tight">Ananda <?= htmlspecialchars($data_santri['nama_lengkap']) ?></h3>
                        <p class="text-xs text-purple-600 font-bold uppercase tracking-wider mt-1">Kelas: <?= htmlspecialchars($data_santri['kelas_sekarang']) ?></p>
                        <p class="text-[10px] text-gray-400">NIS: <?= htmlspecialchars($data_santri['nis'] ?? '-') ?> | NISN: <?= htmlspecialchars($data_santri['nisn'] ?? '-') ?></p>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-2 text-center">
                        <span class="block text-[10px] text-blue-500 font-bold uppercase tracking-wider">Rata Rapor Diknas</span>
                        <span class="text-base font-extrabold text-blue-700"><?= $avg_diknas ?: '-' ?></span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-2 text-center">
                        <span class="block text-[10px] text-emerald-500 font-bold uppercase tracking-wider">Rata Rapor Diniyah</span>
                        <span class="text-base font-extrabold text-emerald-700"><?= $avg_diniyah ?: '-' ?></span>
                    </div>
                </div>
            </div>

            <?php if (!$counseling): ?>
                <!-- Not Generated Yet -->
                <div class="bg-white p-10 rounded-2xl shadow-sm text-center border border-gray-200">
                    <i class="fas fa-route text-6xl text-purple-200 mb-4 animate-bounce"></i>
                    <h3 class="font-bold text-gray-800 text-lg mb-1">Rencana Karir Belum Disusun</h3>
                    <p class="text-gray-500 text-sm max-w-md mx-auto">Musyrif asrama ananda belum memulai pengisian target perguruan tinggi atau evaluasi bimbingan karir. Silakan berkonsultasi langsung dengan Musyrif ananda.</p>
                </div>
            <?php else: ?>
                <!-- Content Area -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left: Targets Summary Card -->
                    <div class="lg:col-span-1 space-y-6">
                        <!-- Jalur Pilihan -->
                        <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm text-center">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Jalur Penerimaan Utama</span>
                            <span class="inline-block px-3 py-1.5 bg-purple-100 text-purple-800 rounded-full font-extrabold text-sm border border-purple-200">
                                <?= htmlspecialchars($counseling['jalur_pilihan']) ?>
                            </span>
                        </div>

                        <!-- Target Kampus & Jurusan -->
                        <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm space-y-4">
                            <h4 class="font-bold text-gray-800 text-sm border-b pb-2"><i class="fas fa-university text-purple-500 mr-1.5"></i>Pilihan Universitas Target</h4>
                            
                            <!-- Prioritas 1 -->
                            <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                                <span class="text-[9px] font-extrabold text-purple-700 uppercase tracking-wider">Pilihan 1 (Prioritas)</span>
                                <h5 class="font-bold text-gray-800 text-xs mt-1"><?= htmlspecialchars($counseling['target_jurusan_1'] ?: 'Belum ditentukan') ?></h5>
                                <p class="text-[11px] text-gray-500"><?= htmlspecialchars($counseling['target_ptn_1'] ?: 'Belum ditentukan') ?></p>
                            </div>

                            <!-- Prioritas 2 -->
                            <div class="p-3 bg-slate-50 rounded-xl border border-gray-150">
                                <span class="text-[9px] font-extrabold text-slate-500 uppercase tracking-wider">Pilihan 2 (Cadangan)</span>
                                <h5 class="font-bold text-gray-800 text-xs mt-1"><?= htmlspecialchars($counseling['target_jurusan_2'] ?: 'Belum ditentukan') ?></h5>
                                <p class="text-[11px] text-gray-500"><?= htmlspecialchars($counseling['target_ptn_2'] ?: 'Belum ditentukan') ?></p>
                            </div>
                        </div>

                        <!-- Catatan Musyrif & Karakter -->
                        <div class="bg-white rounded-2xl p-6 border border-gray-200/80 shadow-sm space-y-4">
                            <h4 class="font-bold text-gray-800 text-sm border-b pb-2"><i class="fas fa-user-shield text-emerald-500 mr-1.5"></i>Catatan & Evaluasi Musyrif</h4>
                            
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Minat & Bakat Santri</span>
                                <p class="text-xs text-gray-600 bg-gray-50 p-2.5 rounded-lg border border-gray-100 font-medium"><?= nl2br(htmlspecialchars($counseling['minat_bakat'] ?: 'Belum diisi')) ?></p>
                            </div>

                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Catatan Karakter Akhlak Asrama</span>
                                <p class="text-xs text-gray-600 bg-emerald-50/50 p-2.5 rounded-lg border border-emerald-100/50 font-medium"><?= nl2br(htmlspecialchars($counseling['catatan_konselor'] ?: 'Belum ada catatan dari Musyrif')) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: AI Evaluation Report -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm flex flex-col overflow-hidden">
                            <div class="px-6 py-4 bg-purple-900 text-white flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-lg bg-white/10 flex items-center justify-center text-amber-300 shadow">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm">Rencana Aksi & Analisis Kelayakan AI</h4>
                                    <p class="text-[9px] text-purple-200">Rekomendasi taktis untuk peluang sukses lulus PTN/PTKIN</p>
                                </div>
                            </div>

                            <div class="p-6">
                                <div id="aiOutputContent" class="text-xs text-slate-700 leading-relaxed font-medium bg-slate-50 p-5 border border-slate-200 rounded-lg shadow-inner prose prose-purple max-w-none min-h-[200px]">
                                    <?php if (!empty($counseling['analisa_ai'])): ?>
                                        <!-- Render saved markdown -->
                                        <script>
                                            document.addEventListener("DOMContentLoaded", function() {
                                                const rawMarkdown = `<?= str_replace('`', '\`', $counseling['analisa_ai']) ?>`;
                                                document.getElementById('aiOutputContent').innerHTML = marked.parse(rawMarkdown);
                                            });
                                        </script>
                                    <?php else: ?>
                                        <div class="text-center text-slate-400 py-12">
                                            <i class="fas fa-brain text-4xl text-purple-200 mb-2"></i>
                                            <p class="text-xs">Musyrif ananda belum meluncurkan pemetaan kelayakan AI untuk saat ini.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
