<?php
// admin/admin-ai-cs-training.php
// Panel Manajemen Knowledge Base & Training CS AI Konsultan Villa Quran
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../includes/koneksi.php';
require_once __DIR__ . '/../includes/config-key.php';

$active_menu = 'ai-cs-training';
$pesan_sukses = '';
$pesan_error = '';

// Self-healing: Buat tabel ai_cs_knowledge jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS ai_cs_knowledge (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori VARCHAR(50) NOT NULL DEFAULT 'umum',
    topik_pertanyaan VARCHAR(255) NOT NULL,
    kata_kunci TEXT NOT NULL,
    instruksi_jawaban TEXT NOT NULL,
    ajakan_open_house TEXT NULL,
    prioritas INT DEFAULT 1,
    status ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed default data jika tabel masih kosong
$cek_data = $conn->query("SELECT COUNT(id) as total FROM ai_cs_knowledge");
$tot_data = $cek_data ? ($cek_data->fetch_assoc()['total'] ?? 0) : 0;
if ($tot_data == 0) {
    $seed_data = [
        [
            'kategori' => 'open_house',
            'topik_pertanyaan' => 'Jadwal & Agenda Open House / Survey Pesantren',
            'kata_kunci' => 'open house, jadwal, survey, kapan, kunjungan, silaturahmi, lokasi, datang, alamat',
            'instruksi_jawaban' => "Jelaskan bahwa Villa Quran mengadakan Open House & Sesi Silaturahmi Calon Santri setiap hari Sabtu & Ahad pukul 08.30 - 12.00 WIB di kampus Villa Quran Baron Malang. Agendanya mencakup Tour Asrama Villa Nuansa Alam, Konsultasi Parenting dengan Pimpinan/Ustadz, dan Pemetaan Minat Bakat Anak secara gratis.",
            'ajakan_open_house' => "Apakah Ayah/Bunda berkenan kami reservasikan 1 seat undangan gratis untuk hadir di Open House Sabtu atau Ahad ini?",
            'prioritas' => 5
        ],
        [
            'kategori' => 'biaya_pendidikan',
            'topik_pertanyaan' => 'Rincian Biaya Masuk, SPP, dan Keringanan',
            'kata_kunci' => 'biaya, uang gedung, spp, mahal, diskon, beasiswa, bayar, rincian, dana',
            'instruksi_jawaban' => "Jelaskan bahwa investasi pendidikan di Villa Quran sangat terjangkau & transparan (sudah include asrama villa, makan bergizi 3x sehari, bimbingan tahfidz intensif, dan sekolah formal). Jangan mendikte angka kaku di awal, melainkan tekankan bahwa ada Voucher Keringanan Infaq & Skema Beasiswa khusus yang diberikan bagi orang tua yang hadir langsung saat Open House.",
            'ajakan_open_house' => "Untuk mendapatkan simulasi rincian biaya & voucher keringanan khusus, Ayah/Bunda sangat kami sarankan hadir saat Open House. Kami jadwalkan di sesi Sabtu atau Ahad?",
            'prioritas' => 4
        ],
        [
            'kategori' => 'program_tahfidz',
            'topik_pertanyaan' => 'Metode Tahfidz Quran & Penanganan Santri Belum Lancar Mengaji',
            'kata_kunci' => 'tahfidz, hafalan, quran, iqro, target hafalan, belum lancar, metode, juz',
            'instruksi_jawaban' => "Jelaskan bahwa Villa Quran membimbing dari nol dengan metode 'Joyful Tahfidz' (tanpa tekanan mental). Setiap santri dikelompokkan dalam halaqoh kecil (1 musyrif mendampingi 8-10 santri). Target bertahap mulai 5 juz, 10 juz, hingga 30 juz disesuaikan dengan kemampuan alami ananda.",
            'ajakan_open_house' => "Ayah/Bunda bisa melihat langsung bagaimana santri kami belajar menghafal dengan riang saat Open House nanti. Kami pesankan slot kunjungan akhir pekan ini?",
            'prioritas' => 3
        ],
        [
            'kategori' => 'keberatan_wali',
            'topik_pertanyaan' => 'Anak Manja / Takut Tidak Betah / Menangis di Pondok',
            'kata_kunci' => 'takut, tidak betah, manja, nangis, belum mandiri, gak mau mondok, ragu, kangen',
            'instruksi_jawaban' => "Berikan empati penuh: ini adalah hal wajar bagi calon santri. Jelaskan bahwa Villa Quran didesain bukan seperti barak militer, melainkan hunian villa asri di lereng pegunungan Malang yang homey. Ada masa adaptasi bertahap, kegiatan outbound, dan pendekatan kekeluargaan.",
            'ajakan_open_house' => "Kuncinya adalah mengajak Ananda melihat langsung suasananya tanpa paksaan. Biasanya setelah ikut tour Open House dan merasakan udaranya yang sejuk, anak yang justru minta mondok sendiri. Bersedia kami undang akhir pekan ini?",
            'prioritas' => 5
        ],
        [
            'kategori' => 'asesmen_kesiapan',
            'topik_pertanyaan' => 'Permintaan Hasil Analisa Asesmen / Tes Kesiapan Anak',
            'kata_kunci' => 'mohon dikirim analisa asesmen nya, hasil tes, asesmen, analisa, skor kesiapan',
            'instruksi_jawaban' => "Ucapkan terima kasih dan apresiasi kepada Ayah/Bunda karena telah meluangkan waktu tes kesiapan ananda. Paparkan bahwa ananda memiliki potensi besar untuk tumbuh mandiri dan berakhlak mulia jika dibimbing dengan lingkungan yang mendukung.",
            'ajakan_open_house' => "Laporan lengkap dan konsultasi personal bersama ustadz/psikolog kami sediakan secara gratis dalam sesi Open House. Apakah Ayah/Bunda siap hadir bersama Ananda akhir pekan ini?",
            'prioritas' => 5
        ]
    ];
    foreach ($seed_data as $s) {
        $stmt = $conn->prepare("INSERT INTO ai_cs_knowledge (kategori, topik_pertanyaan, kata_kunci, instruksi_jawaban, ajakan_open_house, prioritas) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $s['kategori'], $s['topik_pertanyaan'], $s['kata_kunci'], $s['instruksi_jawaban'], $s['ajakan_open_house'], $s['prioritas']);
        $stmt->execute();
    }
}

// Handle Form Aksi CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan') {
        $id = (int)($_POST['id'] ?? 0);
        $kategori = trim($_POST['kategori'] ?? 'umum');
        $topik = trim($_POST['topik_pertanyaan'] ?? '');
        $kata_kunci = trim($_POST['kata_kunci'] ?? '');
        $instruksi = trim($_POST['instruksi_jawaban'] ?? '');
        $ajakan = trim($_POST['ajakan_open_house'] ?? '');
        $prioritas = (int)($_POST['prioritas'] ?? 1);
        $status = $_POST['status'] === 'nonaktif' ? 'nonaktif' : 'aktif';

        if (empty($topik) || empty($instruksi)) {
            $pesan_error = "Topik pertanyaan dan instruksi jawaban wajib diisi!";
        } else {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE ai_cs_knowledge SET kategori=?, topik_pertanyaan=?, kata_kunci=?, instruksi_jawaban=?, ajakan_open_house=?, prioritas=?, status=? WHERE id=?");
                $stmt->bind_param("sssssisi", $kategori, $topik, $kata_kunci, $instruksi, $ajakan, $prioritas, $status, $id);
                $stmt->execute();
                $pesan_sukses = "Data training CS AI berhasil diperbarui!";
            } else {
                $stmt = $conn->prepare("INSERT INTO ai_cs_knowledge (kategori, topik_pertanyaan, kata_kunci, instruksi_jawaban, ajakan_open_house, prioritas, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssis", $kategori, $topik, $kata_kunci, $instruksi, $ajakan, $prioritas, $status);
                $stmt->execute();
                $pesan_sukses = "Materi training baru CS AI berhasil ditambahkan!";
            }
        }
    } elseif ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM ai_cs_knowledge WHERE id = $id");
            $pesan_sukses = "Materi training berhasil dihapus!";
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE ai_cs_knowledge SET status = IF(status='aktif', 'nonaktif', 'aktif') WHERE id = $id");
            $pesan_sukses = "Status materi berhasil diubah!";
        }
    }
}

// Ambil semua knowledge training
$res_knowledge = $conn->query("SELECT * FROM ai_cs_knowledge ORDER BY prioritas DESC, id ASC");
$list_knowledge = [];
if ($res_knowledge) {
    while ($row = $res_knowledge->fetch_assoc()) {
        $list_knowledge[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Center CS AI & Open House | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../components/sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden">
        <!-- TOPBAR -->
        <header class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-[#0b8478] flex items-center justify-center text-xl">
                    <i class="fas fa-brain"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-slate-900 leading-tight">Training Center & Knowledge Base CS AI</h1>
                    <p class="text-xs text-slate-500">Ajari CS AI cara berdialog santun, solutif, dan mengarahkan orang tua ke Open House</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="openModalTambah()" class="px-4 py-2 bg-[#0b8478] hover:bg-[#086a60] text-white text-xs font-bold rounded-xl shadow-sm hover:shadow transition flex items-center gap-2">
                    <i class="fas fa-plus"></i> Tambah Materi Training
                </button>
            </div>
        </header>

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-y-auto p-6 space-y-6">

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-sm flex items-center gap-3 shadow-xs">
                    <i class="fas fa-check-circle text-emerald-500 text-lg"></i>
                    <span><?= htmlspecialchars($pesan_sukses) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-sm flex items-center gap-3 shadow-xs">
                    <i class="fas fa-exclamation-circle text-rose-500 text-lg"></i>
                    <span><?= htmlspecialchars($pesan_error) ?></span>
                </div>
            <?php endif; ?>

            <!-- STATS CARDS & LIVE TEST SIMULATOR -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Stat 1: Total Materi Training -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center text-2xl font-bold">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Knowledge Base</p>
                        <h3 class="text-2xl font-black text-slate-900"><?= count($list_knowledge) ?> Materi</h3>
                    </div>
                </div>

                <!-- Stat 2: Target Closing Funnel -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl font-bold">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tujuan Utama AI</p>
                        <h3 class="text-lg font-black text-slate-900">Reservasi Open House</h3>
                    </div>
                </div>

                <!-- Stat 3: Status Engine AI -->
                <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-2xl font-bold">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">AI Engine Model</p>
                        <h3 class="text-lg font-black text-slate-900">Gemini 1.5 + Fonnte</h3>
                    </div>
                </div>
            </div>

            <!-- LIVE TEST SIMULATOR PLAYGROUND -->
            <div class="bg-gradient-to-br from-slate-900 via-teal-950 to-slate-900 text-white p-6 rounded-3xl shadow-xl border border-teal-800/40">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-teal-700/40 pb-4 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-300 flex items-center justify-center text-xl">
                            <i class="fas fa-vial"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-white">Live AI Simulator Playground</h2>
                            <p class="text-xs text-teal-200/70">Uji langsung bagaimana CS AI merespon pertanyaan calon wali santri & mengarahkan ke Open House</p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-teal-500/20 text-teal-300 border border-teal-400/30">
                        <span class="w-2 h-2 rounded-full bg-teal-400 animate-pulse"></span> Mode Training Aktif
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-8 flex flex-col gap-2">
                        <label class="text-xs font-bold text-teal-200">Simulasikan Pesan Masuk dari Wali Santri:</label>
                        <div class="flex gap-2">
                            <input type="text" id="sim-input" placeholder="Contoh: 'Mohon dikirim analisa asesmen nya' atau 'Berapa biaya masuknya?'" class="flex-1 bg-slate-800/90 border border-teal-700/60 rounded-xl px-4 py-3 text-sm text-white placeholder-slate-400 focus:outline-none focus:border-teal-400 focus:ring-1 focus:ring-teal-400">
                            <button type="button" onclick="runSimulation()" id="sim-btn" class="px-5 py-3 bg-[#0b8478] hover:bg-teal-500 text-white text-xs font-bold rounded-xl transition flex items-center gap-2 shrink-0">
                                <i class="fas fa-paper-plane"></i> Tes Respon
                            </button>
                        </div>
                    </div>

                    <div class="md:col-span-4 flex flex-col justify-end">
                        <div class="text-[11px] text-teal-200/80 bg-teal-900/30 border border-teal-700/40 rounded-xl p-3">
                            <p class="font-bold text-teal-300 mb-1">Tips Pertanyaan Cepat:</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button onclick="setSimText('Mohon dikirim analisa asesmen nya')" class="bg-slate-800 hover:bg-teal-800 px-2 py-1 rounded text-[10px] text-teal-100 border border-teal-700/50">Hasil Asesmen</button>
                                <button onclick="setSimText('Berapa biaya masuk dan SPP nya?')" class="bg-slate-800 hover:bg-teal-800 px-2 py-1 rounded text-[10px] text-teal-100 border border-teal-700/50">Tanya Biaya</button>
                                <button onclick="setSimText('Anak saya belum mandiri dan takut gak betah')" class="bg-slate-800 hover:bg-teal-800 px-2 py-1 rounded text-[10px] text-teal-100 border border-teal-700/50">Anak Takut</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Simulation Output Box -->
                <div id="sim-result-box" class="mt-4 hidden">
                    <p class="text-xs font-bold text-teal-300 mb-2 flex items-center gap-2">
                        <i class="fas fa-comment-dots"></i> Respon CS AI Villa Quran:
                    </p>
                    <div id="sim-result-content" class="bg-slate-800/90 border border-teal-700/50 rounded-2xl p-4 text-xs leading-relaxed text-slate-100 whitespace-pre-line font-mono">
                        <!-- Loaded via JS -->
                    </div>
                </div>
            </div>

            <!-- TABEL DAFTAR KNOWLEDGE BASE CS AI -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Materi Pengetahuan & SOP Jawaban CS AI</h2>
                        <p class="text-xs text-slate-500">Semua instruksi di bawah ini otomatis dirangkum menjadi otak pengetahuan CS AI saat membalas WhatsApp</p>
                    </div>
                    <span class="text-xs font-bold text-teal-700 bg-teal-50 px-3 py-1.5 rounded-full border border-teal-200">
                        <?= count($list_knowledge) ?> Materi Terpasang
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-4 w-12 text-center">No</th>
                                <th class="p-4 w-32">Kategori</th>
                                <th class="p-4">Topik & Kata Kunci (Triggers)</th>
                                <th class="p-4">Instruksi Fakta / SOP Jawaban</th>
                                <th class="p-4">Closing / Arahkan Open House</th>
                                <th class="p-4 w-20 text-center">Status</th>
                                <th class="p-4 w-28 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-normal text-slate-700">
                            <?php if (empty($list_knowledge)): ?>
                                <tr>
                                    <td colspan="7" class="p-8 text-center text-slate-400 italic">Belum ada materi training. Silakan klik tombol "Tambah Materi Training".</td>
                                </tr>
                            <?php else: ?>
                                <?php $no = 1; foreach ($list_knowledge as $k): ?>
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="p-4 text-center font-bold text-slate-400"><?= $no++ ?></td>
                                        <td class="p-4">
                                            <span class="inline-block px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider
                                                <?= $k['kategori'] === 'open_house' ? 'bg-amber-100 text-amber-800' :
                                                   ($k['kategori'] === 'biaya_pendidikan' ? 'bg-emerald-100 text-emerald-800' :
                                                   ($k['kategori'] === 'keberatan_wali' ? 'bg-rose-100 text-rose-800' : 'bg-teal-100 text-[#0b8478]')) ?>">
                                                <?= str_replace('_', ' ', $k['kategori']) ?>
                                            </span>
                                        </td>
                                        <td class="p-4">
                                            <p class="font-bold text-slate-900 mb-1"><?= htmlspecialchars($k['topik_pertanyaan']) ?></p>
                                            <p class="text-[11px] text-slate-500 flex items-center gap-1">
                                                <i class="fas fa-tags text-teal-600"></i> <?= htmlspecialchars($k['kata_kunci']) ?>
                                            </p>
                                        </td>
                                        <td class="p-4 max-w-xs">
                                            <p class="line-clamp-3 text-slate-600 leading-relaxed"><?= htmlspecialchars($k['instruksi_jawaban']) ?></p>
                                        </td>
                                        <td class="p-4 max-w-xs">
                                            <p class="line-clamp-3 text-teal-800 font-medium italic bg-teal-50/70 p-2 rounded-lg border border-teal-100"><?= htmlspecialchars($k['ajakan_open_house'] ?: '-') ?></p>
                                        </td>
                                        <td class="p-4 text-center">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                <button type="submit" class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $k['status'] === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>">
                                                    <?= strtoupper($k['status']) ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="p-4 text-center">
                                            <div class="flex items-center justify-center gap-1.5">
                                                <button onclick="openModalEdit(<?= htmlspecialchars(json_encode($k), ENT_QUOTES, 'UTF-8') ?>)" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-teal-50 text-slate-600 hover:text-[#0b8478] flex items-center justify-center transition" title="Edit Materi">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi training ini?');" class="inline">
                                                    <input type="hidden" name="action" value="hapus">
                                                    <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                                    <button type="submit" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-600 flex items-center justify-center transition" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
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

    <!-- MODAL TAMBAH / EDIT MATERI TRAINING -->
    <div id="modal-form" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 overflow-y-auto max-h-[90vh]">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-teal-50 text-[#0b8478] flex items-center justify-center font-bold">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3 id="modal-title" class="text-base font-bold text-slate-900">Tambah Materi Training CS AI</h3>
                </div>
                <button onclick="closeModalForm()" class="text-slate-400 hover:text-slate-600 p-1">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="simpan">
                <input type="hidden" id="form-id" name="id" value="0">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Topik:</label>
                        <select name="kategori" id="form-kategori" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:border-teal-500">
                            <option value="open_house">🏠 Open House & Kunjungan</option>
                            <option value="biaya_pendidikan">💰 Biaya, SPP & Beasiswa</option>
                            <option value="program_tahfidz">📖 Program Tahfidz & Kurikulum</option>
                            <option value="keberatan_wali">🛡️ Atasi Keraguan / Takut Gak Betah</option>
                            <option value="asesmen_kesiapan">📋 Asesmen & Diagnosa Kesiapan</option>
                            <option value="fasilitas_villa">🌿 Fasilitas & Asrama Villa</option>
                            <option value="umum">💬 Tanya Jawab Umum</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Prioritas Pengetahuan:</label>
                        <select name="prioritas" id="form-prioritas" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:border-teal-500">
                            <option value="5">⭐⭐⭐⭐⭐ Sangat Tinggi (Utama)</option>
                            <option value="4">⭐⭐⭐⭐ Tinggi</option>
                            <option value="3">⭐⭐⭐ Sedang</option>
                            <option value="2">⭐⭐ Rendah</option>
                            <option value="1">⭐ Standar</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Topik / Pertanyaan Induk:</label>
                    <input type="text" name="topik_pertanyaan" id="form-topik" placeholder="Contoh: Rincian Jadwal Open House & Tes Minat Bakat" required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:border-teal-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kata Kunci Pemicu (Keywords - Pisahkan koma):</label>
                    <input type="text" name="kata_kunci" id="form-keywords" placeholder="Contoh: open house, jadwal, survey, kapan, kunjungan, datang" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:border-teal-500">
                    <p class="text-[10px] text-slate-400 mt-1">Jika pesan WhatsApp orang tua mengandung salah satu kata kunci di atas, CS AI akan mengacu pada SOP materi ini.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Instruksi Fakta / SOP Jawaban CS AI:</label>
                    <textarea name="instruksi_jawaban" id="form-instruksi" rows="4" placeholder="Tuliskan poin fakta yang harus disampaikan AI secara santun, empatik, dan berbasis nilai Villa Quran..." required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:border-teal-500"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Kalimat Closing / Arahkan ke Open House:</label>
                    <textarea name="ajakan_open_house" id="form-ajakan" rows="2" placeholder="Contoh: Apakah Ayah/Bunda berkenan kami reservasikan 1 seat undangan gratis untuk hadir di Open House Sabtu ini?" class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 focus:bg-white focus:outline-none focus:border-teal-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModalForm()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-[#0b8478] hover:bg-[#086a60] text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center gap-2">
                        <i class="fas fa-save"></i> Simpan Materi Training
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIC -->
    <script>
        function openModalTambah() {
            document.getElementById('modal-title').innerText = "Tambah Materi Training CS AI";
            document.getElementById('form-id').value = "0";
            document.getElementById('form-kategori').value = "open_house";
            document.getElementById('form-prioritas').value = "3";
            document.getElementById('form-topik').value = "";
            document.getElementById('form-keywords').value = "";
            document.getElementById('form-instruksi').value = "";
            document.getElementById('form-ajakan').value = "";
            document.getElementById('modal-form').classList.remove('hidden');
        }

        function openModalEdit(data) {
            document.getElementById('modal-title').innerText = "Edit Materi Training CS AI";
            document.getElementById('form-id').value = data.id;
            document.getElementById('form-kategori').value = data.kategori;
            document.getElementById('form-prioritas').value = data.prioritas || "3";
            document.getElementById('form-topik').value = data.topik_pertanyaan;
            document.getElementById('form-keywords').value = data.kata_kunci;
            document.getElementById('form-instruksi').value = data.instruksi_jawaban;
            document.getElementById('form-ajakan').value = data.ajakan_open_house || "";
            document.getElementById('modal-form').classList.remove('hidden');
        }

        function closeModalForm() {
            document.getElementById('modal-form').classList.add('hidden');
        }

        function setSimText(txt) {
            document.getElementById('sim-input').value = txt;
            runSimulation();
        }

        async function runSimulation() {
            const inputVal = document.getElementById('sim-input').value.trim();
            if (!inputVal) {
                alert('Silakan masukkan contoh pesan wali santri terlebih dahulu.');
                return;
            }

            const btn = document.getElementById('sim-btn');
            const resBox = document.getElementById('sim-result-box');
            const resContent = document.getElementById('sim-result-content');

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses AI...';
            resBox.classList.remove('hidden');
            resContent.innerHTML = 'Sedang memanggil Gemini AI dengan Knowledge Base terbaru...';

            try {
                const response = await fetch('../api/wa-webhook.php?test_mode=1', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: inputVal,
                        sender: '628123456789',
                        name: 'Ayah Fulan (Simulasi Test)'
                    })
                });

                const data = await response.json();
                if (data.status === 'success' || data.reply) {
                    resContent.innerText = data.reply || data.message || JSON.stringify(data);
                } else {
                    resContent.innerText = "Respon AI: " + (data.message || JSON.stringify(data));
                }
            } catch (err) {
                resContent.innerText = "Terjadi kesalahan koneksi simulator: " + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Tes Respon';
            }
        }
    </script>
</body>
</html>
