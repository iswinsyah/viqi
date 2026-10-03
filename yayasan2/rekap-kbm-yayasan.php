<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'rekap_kbm_yayasan';

// User Session Info
$y_nama = $_SESSION['nama_lengkap'] ?? ($_SESSION['yayasan_user'] ?? 'Ketua Yayasan');
$current_ustadz_id = isset($_SESSION['ustadz_id']) ? (int)$_SESSION['ustadz_id'] : (isset($_SESSION['app_user_id']) ? (int)$_SESSION['app_user_id'] : 0);

// Period Selection
$selected_period = $_GET['periode'] ?? date('Y-m');
list($selected_year, $selected_month) = explode('-', $selected_period);
$selected_month = (int)$selected_month;
$selected_year = (int)$selected_year;

$start_date = "$selected_year-" . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "-01";
$last_day = date('t', strtotime($start_date));
$end_date = "$selected_year-" . str_pad($selected_month, 2, '0', STR_PAD_LEFT) . "-$last_day";

// --- AJAX HANDLER FOR YAYASAN REVIEW ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'approve_yayasan') {
        $periode = $conn->real_escape_string($_POST['periode'] ?? $selected_period);
        $catatan_yayasan = $conn->real_escape_string($_POST['catatan_yayasan'] ?? '');

        $sql = "UPDATE laporan_kbm_bulanan 
                SET status = 'disetujui_yayasan', catatan_yayasan = '$catatan_yayasan', tanggal_review_yayasan = NOW() 
                WHERE periode = '$periode'";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => '✅ Laporan Rekap KBM berhasil disetujui dan disahkan oleh Yayasan!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengesahkan laporan: ' . $conn->error]);
        }
        exit;
    }

    if ($action === 'kembalikan_draf') {
        $periode = $conn->real_escape_string($_POST['periode'] ?? $selected_period);
        $catatan_yayasan = $conn->real_escape_string($_POST['catatan_yayasan'] ?? '');

        $sql = "UPDATE laporan_kbm_bulanan 
                SET status = 'draft', catatan_yayasan = '$catatan_yayasan' 
                WHERE periode = '$periode'";
        
        if ($conn->query($sql)) {
            echo json_encode(['status' => 'success', 'message' => 'Laporan KBM dikembalikan ke Kepala Sekolah untuk diperbaiki.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengembalikan laporan: ' . $conn->error]);
        }
        exit;
    }
}

// Fetch Master Header Laporan
$res_laporan = $conn->query("SELECT l.*, u.nama as nama_kepsek 
    FROM laporan_kbm_bulanan l 
    LEFT JOIN akun_ustadz u ON l.kepala_sekolah_id = u.id 
    WHERE l.periode = '$selected_period'");
$laporan_header = $res_laporan ? $res_laporan->fetch_assoc() : null;

// Fetch Detail Laporan
$rekap_data = [];
if ($laporan_header) {
    $lap_id = $laporan_header['id'];
    $res_det = $conn->query("SELECT d.*, u.nama as nama_tutor 
        FROM laporan_kbm_detail d 
        JOIN akun_ustadz u ON d.ustadz_id = u.id 
        WHERE d.laporan_id = $lap_id 
        ORDER BY u.nama ASC, d.mapel_nama ASC");
    if ($res_det) {
        while ($r = $res_det->fetch_assoc()) {
            $rekap_data[] = $r;
        }
    }
}

// Summary Metrics
$total_jam_terjadwal = 0;
$total_jam_sistem = 0;
$total_jam_penyesuaian = 0;
$total_jam_final = 0;

foreach ($rekap_data as $row) {
    $total_jam_terjadwal += (int)$row['jam_terjadwal'];
    $total_jam_sistem += (int)$row['jam_sistem'];
    $total_jam_penyesuaian += (int)$row['jam_penyesuaian'];
    $total_jam_final += (int)$row['jam_final'];
}
$overall_persen = $total_jam_terjadwal > 0 ? min(100, round(($total_jam_final / $total_jam_terjadwal) * 100, 2)) : 100.00;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi KBM Sekolah | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif & Pengawasan Yayasan</h2>
            </div>
            <div class="flex items-center gap-2">
                <a href="../dashboard.php" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    <i class="fas fa-house text-[11px]"></i> Dashboard
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 text-left">
            <!-- Header Title & Month Filter -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2.5">
                        <i class="fas fa-clipboard-check text-teal-600"></i>
                        <span>Laporan Rekapitulasi KBM Sekolah</span>
                    </h1>
                    <p class="text-xs text-gray-500 mt-1">Laporan resmi hasil verifikasi Kepala Sekolah mengenai keterlaksanaan jam mengajar, absensi, dan materi KBM bulanan.</p>
                </div>
                <!-- Period Filter -->
                <form method="GET" class="flex items-center gap-2">
                    <input type="month" name="periode" value="<?= $selected_period ?>" onchange="this.form.submit()" class="px-4 py-2 bg-white border border-gray-300 rounded-xl text-xs font-bold text-gray-700 focus:outline-none focus:ring-2 focus:ring-teal-500 shadow-xs">
                </form>
            </div>

            <!-- STATUS BANNER -->
            <?php if (!$laporan_header || $laporan_header['status'] === 'draft'): ?>
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-xl shadow-xs">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-amber-900">MENUNGGU VALIDASI KEPALA SEKOLAH</h3>
                            <p class="text-xs text-amber-700 mt-0.5">Laporan periode <b><?= date('F Y', strtotime($start_date)) ?></b> saat ini masih dalam proses penyesuaian/draf oleh Kepala Sekolah dan belum dikirimkan ke Yayasan.</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-amber-200 text-amber-900 text-[10px] font-black rounded-lg uppercase">Status: Draf</span>
                </div>
            <?php elseif ($laporan_header['status'] === 'dikirim'): ?>
                <div class="bg-teal-50 border border-teal-200 rounded-2xl p-5 mb-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-teal-600 text-white flex items-center justify-center font-bold text-xl shadow-xs">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-teal-900">LAPORAN KBM RESMI TELAH MASUK DARI KEPALA SEKOLAH</h3>
                            <p class="text-xs text-teal-700 mt-0.5">
                                Divalidasi oleh: <b><?= htmlspecialchars($laporan_header['nama_kepsek'] ?? 'Kepala Sekolah') ?></b> • Waktu Kirim: <b><?= date('d M Y H:i', strtotime($laporan_header['tanggal_validasi_kepsek'])) ?></b>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="bukaModalAccYayasan()" class="px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-check-circle"></i> Sahkan & Setujui Laporan
                        </button>
                    </div>
                </div>
            <?php elseif ($laporan_header['status'] === 'disetujui_yayasan'): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 mb-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-bold text-xl shadow-xs">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-sm text-emerald-900">LAPORAN TELAH DISAHKAN OLEH YAYASAN</h3>
                                <span class="px-2 py-0.5 bg-emerald-200 text-emerald-900 text-[10px] font-black rounded-md uppercase">Resmi ACC</span>
                            </div>
                            <p class="text-xs text-emerald-700 mt-0.5">
                                Kepala Sekolah: <b><?= htmlspecialchars($laporan_header['nama_kepsek'] ?? 'Kepala Sekolah') ?></b> • Tanggal Pengesahan: <b><?= date('d M Y H:i', strtotime($laporan_header['tanggal_review_yayasan'] ?? $laporan_header['updated_at'])) ?></b>
                            </p>
                            <?php if (!empty($laporan_header['catatan_yayasan'])): ?>
                                <p class="text-xs text-emerald-800 bg-emerald-100/70 rounded-xl p-2.5 mt-2 italic border border-emerald-200">
                                    <b>Catatan Yayasan:</b> "<?= htmlspecialchars($laporan_header['catatan_yayasan']) ?>"
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="cetakLaporanYayasan()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                            <i class="fas fa-print"></i> Cetak Dokumen Resmi
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- STATISTIC SUMMARY CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Target Jam Terjadwal</span>
                    <div class="text-2xl font-black text-gray-800 mt-1"><?= $total_jam_terjadwal ?> <span class="text-xs font-normal text-gray-400">JP</span></div>
                    <span class="text-[10px] text-gray-400">Total jam kurikulum</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Realisasi Sistem GPS</span>
                    <div class="text-2xl font-black text-teal-600 mt-1"><?= $total_jam_sistem ?> <span class="text-xs font-normal text-gray-400">JP</span></div>
                    <span class="text-[10px] text-gray-400">Data mentah absensi tutor</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Penyesuaian Kepsek</span>
                    <div class="text-2xl font-black <?= $total_jam_penyesuaian >= 0 ? 'text-indigo-600' : 'text-rose-600' ?> mt-1">
                        <?= ($total_jam_penyesuaian > 0 ? '+' : '') . $total_jam_penyesuaian ?> <span class="text-xs font-normal text-gray-400">JP</span>
                    </div>
                    <span class="text-[10px] text-gray-400">Adjustment berita acara</span>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Tingkat Keaktifan KBM</span>
                    <div class="text-2xl font-black <?= $overall_persen >= 90 ? 'text-emerald-600' : 'text-amber-600' ?> mt-1">
                        <?= $overall_persen ?>%
                    </div>
                    <span class="text-[10px] text-gray-400">Total Final: <b><?= $total_jam_final ?> JP</b></span>
                </div>
            </div>

            <!-- TABLE REKAP DATA -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                    <div>
                        <h2 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                            <i class="fas fa-list-check text-teal-600"></i>
                            <span>Tabel Rekapitulasi Mengajar & Pertemuan KBM</span>
                        </h2>
                        <p class="text-[11px] text-gray-400">Periode: <b><?= date('F Y', strtotime($start_date)) ?></b></p>
                    </div>
                    <button onclick="cetakLaporanYayasan()" class="px-3.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition flex items-center gap-1">
                        <i class="fas fa-print"></i> Cetak Rekap
                    </button>
                </div>

                <?php if (empty($rekap_data)): ?>
                    <div class="py-12 text-center text-gray-400">
                        <i class="fas fa-calendar-xmark text-4xl mb-2 text-gray-300"></i>
                        <p class="text-xs">Belum ada rincian data KBM yang tersimpan pada periode ini.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-gray-50/50">
                                    <th class="px-3 py-3">No</th>
                                    <th class="px-4 py-3">Nama Tutor / Guru</th>
                                    <th class="px-4 py-3">Mata Pelajaran & Kelas</th>
                                    <th class="px-3 py-3 text-center">Target (JP)</th>
                                    <th class="px-3 py-3 text-center bg-teal-50/50 text-teal-800">Sistem (GPS)</th>
                                    <th class="px-3 py-3 text-center bg-indigo-50/50 text-indigo-800">Penyesuaian</th>
                                    <th class="px-3 py-3 text-center bg-emerald-50/50 text-emerald-800">Final (JP)</th>
                                    <th class="px-3 py-3 text-center">Kehadiran</th>
                                    <th class="px-4 py-3">Alasan Penyesuaian Kepsek</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                foreach ($rekap_data as $r): 
                                ?>
                                    <tr class="border-b hover:bg-gray-50/50">
                                        <td class="px-3 py-3 text-gray-400 font-bold"><?= $no++ ?></td>
                                        <td class="px-4 py-3 font-bold text-gray-800"><?= htmlspecialchars($r['nama_tutor']) ?></td>
                                        <td class="px-4 py-3 text-gray-600">
                                            <span class="font-bold text-teal-700"><?= htmlspecialchars($r['mapel_nama']) ?></span>
                                            <?php if (!empty($r['kelas_nama'])): ?>
                                                <br><span class="text-[10px] text-gray-400"><?= htmlspecialchars($r['kelas_nama']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-semibold text-gray-600"><?= (int)$r['jam_terjadwal'] ?> JP</td>
                                        <td class="px-3 py-3 text-center font-bold text-teal-700 bg-teal-50/30"><?= (int)$r['jam_sistem'] ?> JP</td>
                                        <td class="px-3 py-3 text-center font-bold bg-indigo-50/30 <?= $r['jam_penyesuaian'] >= 0 ? 'text-indigo-600' : 'text-rose-600' ?>">
                                            <?= ($r['jam_penyesuaian'] > 0 ? '+' : '') . (int)$r['jam_penyesuaian'] ?>
                                        </td>
                                        <td class="px-3 py-3 text-center font-black text-emerald-700 bg-emerald-50/30"><?= (int)$r['jam_final'] ?> JP</td>
                                        <td class="px-3 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $r['persen_kehadiran'] >= 90 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                                <?= $r['persen_kehadiran'] ?>%
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 text-[11px] italic">
                                            <?= htmlspecialchars($r['alasan_penyesuaian'] ?: '-') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- CATATAN DARI KEPALA SEKOLAH -->
                    <?php if (!empty($laporan_header['catatan_umum_kepsek'])): ?>
                        <div class="mt-6 p-4 bg-slate-50 border border-slate-200 rounded-2xl">
                            <h4 class="font-bold text-xs text-slate-800 mb-1 flex items-center gap-1.5">
                                <i class="fas fa-comment-dots text-indigo-600"></i> Catatan & Rekomendasi Kepala Sekolah:
                            </h4>
                            <p class="text-xs text-slate-600 italic leading-relaxed whitespace-pre-wrap">"<?= htmlspecialchars($laporan_header['catatan_umum_kepsek']) ?>"</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL PENGESAHAN YAYASAN -->
    <div id="modal-acc-yayasan" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
                <h3 class="font-bold text-gray-900 text-sm flex items-center gap-2">
                    <i class="fas fa-stamp text-teal-600"></i>
                    <span>Pengesahan Laporan KBM (Yayasan)</span>
                </h3>
                <button onclick="tutupModalAccYayasan()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>
            
            <div class="space-y-3">
                <p class="text-xs text-gray-600">
                    Dengan menyetujui laporan ini, Yayasan mengesahkan bahwa data kehadiran KBM bulan <b><?= date('F Y', strtotime($start_date)) ?></b> telah diverifikasi dan siap dijadikan rujukan operasional serta penggajian.
                </p>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Catatan / Arahan dari Ketua Yayasan (Opsional)</label>
                    <textarea id="catatan-yayasan-inp" rows="3" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-teal-500" placeholder="Tuliskan arahan atau apresiasi untuk tim sekolah..."></textarea>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between pt-3 border-t border-gray-100">
                <button onclick="kembalikanDraf()" class="px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold rounded-xl text-xs transition border border-rose-200">
                    <i class="fas fa-undo mr-1"></i> Minta Revisi Kepsek
                </button>
                <button onclick="submitAccYayasan()" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white font-black rounded-xl text-xs transition shadow-sm flex items-center gap-1.5">
                    <i class="fas fa-check-double"></i> Sahkan Laporan Resmi
                </button>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => {
            const sidebar = document.getElementById('sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');
            if(sidebar) sidebar.classList.toggle('hidden');
            if(overlay) overlay.classList.toggle('hidden');
        });

        function bukaModalAccYayasan() {
            document.getElementById('modal-acc-yayasan').classList.remove('hidden');
        }

        function tutupModalAccYayasan() {
            document.getElementById('modal-acc-yayasan').classList.add('hidden');
        }

        function submitAccYayasan() {
            const catatan = document.getElementById('catatan-yayasan-inp').value.trim();
            const formData = new FormData();
            formData.append('action', 'approve_yayasan');
            formData.append('periode', '<?= $selected_period ?>');
            formData.append('catatan_yayasan', catatan);

            Swal.fire({ title: 'Mengesahkan Laporan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('rekap-kbm-yayasan.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil Disahkan!', text: res.message, confirmButtonColor: '#0d9488' })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                    }
                })
                .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
        }

        function kembalikanDraf() {
            const catatan = document.getElementById('catatan-yayasan-inp').value.trim();
            if (!catatan) {
                Swal.fire({ icon: 'warning', title: 'Catatan Wajib', text: 'Mohon tulis alasan / poin yang perlu direvisi oleh Kepala Sekolah!' });
                return;
            }

            const formData = new FormData();
            formData.append('action', 'kembalikan_draf');
            formData.append('periode', '<?= $selected_period ?>');
            formData.append('catatan_yayasan', catatan);

            Swal.fire({ title: 'Mengembalikan Laporan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            fetch('rekap-kbm-yayasan.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Dikembalikan ke Draf', text: res.message, confirmButtonColor: '#f59e0b' })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Gagal', text: res.message });
                    }
                })
                .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err.message }));
        }

        function cetakLaporanYayasan() {
            window.print();
        }
    </script>
</body>
</html>
