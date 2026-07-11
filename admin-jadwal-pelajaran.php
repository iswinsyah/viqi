<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once 'auth-ustadz.php'; // Proteksi session ustadz
require_once 'koneksi.php';

// Tentukan hak akses edit
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$can_edit = !empty(array_intersect(['kepala_sekolah', 'kepala_mahad', 'super_admin', 'admin_sekolah'], $user_roles));

// Helper to get distinct soft pastel colors for subjects
function dapatkan_warna_mapel($mapel_name) {
    if (empty($mapel_name)) {
        return ['bg' => '#ffffff', 'text' => '#1f2937'];
    }
    $colors = [
        ['bg' => '#ffe4e6', 'text' => '#9f1239'], // soft rose / red
        ['bg' => '#ffedd5', 'text' => '#9a3412'], // soft orange
        ['bg' => '#fef9c3', 'text' => '#854d0e'], // soft yellow
        ['bg' => '#dcfce7', 'text' => '#166534'], // soft green
        ['bg' => '#ccfbf1', 'text' => '#115e59'], // soft teal
        ['bg' => '#e0f2fe', 'text' => '#075985'], // soft light blue
        ['bg' => '#e0e7ff', 'text' => '#3730a3'], // soft indigo
        ['bg' => '#f3e8ff', 'text' => '#6b21a8'], // soft purple
        ['bg' => '#fae8ff', 'text' => '#86198f'], // soft fuchsia
        ['bg' => '#fce7f3', 'text' => '#9d174d'], // soft pink
        ['bg' => '#f1f5f9', 'text' => '#334155'], // soft slate
        ['bg' => '#f5f5f4', 'text' => '#44403c'], // soft stone
        ['bg' => '#ecfeff', 'text' => '#155e75'], // soft cyan
        ['bg' => '#fef2f2', 'text' => '#991b1b']  // soft light red
    ];
    $hash = crc32($mapel_name);
    $idx = abs($hash) % count($colors);
    return $colors[$idx];
}

// 1. Buat Tabel Otomatis (Self-healing)
$conn->query("CREATE TABLE IF NOT EXISTS master_kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kelas VARCHAR(100) UNIQUE NOT NULL,
    kategori_kelas ENUM('Diknas', 'Diniyah', 'Ekstrakurikuler', 'Lainnya') DEFAULT 'Lainnya',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS jadwal_pelajaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hari VARCHAR(20) NOT NULL,
    jam_ke INT NOT NULL,
    kelas_id INT NOT NULL,
    mapel_id INT NOT NULL,
    ustadz_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_slot (hari, jam_ke, kelas_id)
)");

// Pastikan 4 kelas default dari gambar selalu terdaftar
$classes = ['E4 406', 'E4 402', 'E4 157', 'E4.2'];
foreach ($classes as $cls) {
    $conn->query("INSERT IGNORE INTO master_kelas (nama_kelas, kategori_kelas, keterangan) VALUES ('$cls', 'Diniyah', 'Kelas dari gambar jadwal')");
}

// 2. Handle POST Request (AJAX/Simpan)
$pesan_sukses = "";
$pesan_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    if (!$can_edit) {
        echo "<div style='color: red; padding: 20px; font-weight: bold; text-align: center; font-family: sans-serif; margin-top: 50px;'>
                Anda tidak memiliki hak akses untuk mengedit jadwal pelajaran.
              </div>";
        exit;
    }
    
    $action = $_POST['action'];
    $hari = $conn->real_escape_string($_POST['hari']);
    $jam_ke = (int)$_POST['jam_ke'];
    $kelas_id = (int)$_POST['kelas_id'];

    if ($action === 'save') {
        $mapel_id = (int)$_POST['mapel_id'];
        $ustadz_id = !empty($_POST['ustadz_id']) ? (int)$_POST['ustadz_id'] : 'NULL';

        $sql = "INSERT INTO jadwal_pelajaran (hari, jam_ke, kelas_id, mapel_id, ustadz_id) 
                VALUES ('$hari', $jam_ke, $kelas_id, $mapel_id, $ustadz_id)
                ON DUPLICATE KEY UPDATE mapel_id = $mapel_id, ustadz_id = $ustadz_id";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Jadwal pelajaran berhasil disimpan!";
        } else {
            $pesan_error = "Gagal menyimpan jadwal: " . $conn->error;
        }
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM jadwal_pelajaran WHERE hari = '$hari' AND jam_ke = $jam_ke AND kelas_id = $kelas_id";
        if ($conn->query($sql)) {
            $pesan_sukses = "Jadwal pelajaran berhasil dihapus!";
        } else {
            $pesan_error = "Gagal menghapus jadwal: " . $conn->error;
        }
    }
    
    // Redirect untuk mereset data POST
    header("Location: admin-jadwal-pelajaran.php?sukses=" . urlencode($pesan_sukses) . "&error=" . urlencode($pesan_error));
    exit;
}

if (isset($_GET['sukses']) && !empty($_GET['sukses'])) {
    $pesan_sukses = $_GET['sukses'];
}
if (isset($_GET['error']) && !empty($_GET['error'])) {
    $pesan_error = $_GET['error'];
}

// 3. Ambil data pendukung
// List Kelas Moving Class
$kelas_list = [];
$res_kelas = $conn->query("SELECT * FROM master_kelas WHERE nama_kelas IN ('E4 406', 'E4 402', 'E4 157', 'E4.2') ORDER BY FIELD(nama_kelas, 'E4 406', 'E4 402', 'E4 157', 'E4.2')");
if ($res_kelas) {
    while ($row = $res_kelas->fetch_assoc()) {
        $kelas_list[] = $row;
    }
}

// Mapel List (Aktif)
$mapel_list = [];
$res_mapel = $conn->query("SELECT id, nama_mapel, kode_mapel, kategori_mapel FROM master_mapel WHERE status_aktif = 1 ORDER BY nama_mapel ASC");
if ($res_mapel) {
    while ($row = $res_mapel->fetch_assoc()) {
        $mapel_list[] = $row;
    }
}

// Ustadz List
$ustadz_list = [];
$res_ustadz = $conn->query("SELECT id, nama FROM akun_ustadz ORDER BY nama ASC");
if ($res_ustadz) {
    while ($row = $res_ustadz->fetch_assoc()) {
        $ustadz_list[] = $row;
    }
}

// 4. Ambil semua Jadwal Pelajaran untuk di-render di tabel
$schedules = [];
$sql_sched = "SELECT j.*, m.nama_mapel, m.kode_mapel, u.nama as nama_ustadz 
              FROM jadwal_pelajaran j 
              JOIN master_mapel m ON j.mapel_id = m.id 
              LEFT JOIN akun_ustadz u ON j.ustadz_id = u.id";
$res_sched = $conn->query($sql_sched);
if ($res_sched) {
    while ($row = $res_sched->fetch_assoc()) {
        $schedules[$row['hari']][$row['jam_ke']][$row['kelas_id']] = [
            'mapel_id' => $row['mapel_id'],
            'mapel_nama' => $row['nama_mapel'],
            'mapel_kode' => !empty($row['kode_mapel']) ? $row['kode_mapel'] : $row['nama_mapel'],
            'ustadz_id' => $row['ustadz_id'],
            'ustadz_nama' => !empty($row['nama_ustadz']) ? $row['nama_ustadz'] : 'Belum Ada'
        ];
    }
}

// Definisi Baris Jam / Pukul dan Jenis Kegiatannya
$slots_definition = [
    ['type' => 'slot', 'jam_ke' => 1, 'pukul' => '05.00 sd 05.45'],
    ['type' => 'slot', 'jam_ke' => 2, 'pukul' => '05.45 sd 06.30'],
    ['type' => 'break', 'pukul' => '06.30 sd 07.30', 'keterangan' => 'Istirahat: mandi, sholat, dhuha, sarapan'],
    ['type' => 'slot', 'jam_ke' => 3, 'pukul' => '07.30 sd 08.15'],
    ['type' => 'slot', 'jam_ke' => 4, 'pukul' => '08.15 sd 09.00'],
    ['type' => 'slot', 'jam_ke' => 5, 'pukul' => '09.00 sd 09.45'],
    ['type' => 'slot', 'jam_ke' => 6, 'pukul' => '09.45 sd 10.30'],
    ['type' => 'slot', 'jam_ke' => 7, 'pukul' => '10.30 sd 11.15'],
    ['type' => 'slot', 'jam_ke' => 8, 'pukul' => '11.15 sd 12.00'],
    ['type' => 'break', 'pukul' => '12.00 sd 16.00', 'keterangan' => 'Istirahat: sholat dhuhur makan siang, tidur siang, sholat ashar'],
    ['type' => 'slot', 'jam_ke' => 9, 'pukul' => '16.00 sd 16.45'],
    ['type' => 'slot', 'jam_ke' => 10, 'pukul' => '16.45 sd 17.30'],
    ['type' => 'break', 'pukul' => '17.30 sd 19.30', 'keterangan' => 'Istirahat: sholat maghrib, makan malam'],
    ['type' => 'slot', 'jam_ke' => 12, 'pukul' => '19.30 sd 20.15'],
    ['type' => 'slot', 'jam_ke' => 13, 'pukul' => '20.15 sd 21.00'],
    ['type' => 'break', 'pukul' => '21.00 sd 03.30', 'keterangan' => 'Istirahat: tidur malam']
];

$days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

$active_menu = 'jadwal_pelajaran';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelajaran Pondok | Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sched-header {
            background-color: #0c562e;
            color: #ffffff;
            border-color: #0a4827;
        }
        .sched-subheader {
            background-color: #0e6231;
            color: #ffffff;
            border-color: #0b4e27;
        }
        .sched-break {
            background-color: #dceddb;
            color: #0c562e;
            font-weight: bold;
        }
        .border-green-dark {
            border-color: #0c562e;
        }
        .sched-table th, .sched-table td {
            border: 1px solid #0c562e;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <!-- INCLUDE SIDEBAR ASATIDZ -->
    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Pengaturan Jadwal Pelajaran</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-calendar-alt text-emerald-600 mr-2"></i>Jadwal Pelajaran</h1>
                    <p class="text-xs text-gray-500 mt-1">Pengaturan jadwal harian santri untuk asrama dan Diniyah.</p>
                </div>
                <div class="mt-4 sm:mt-0 text-xs text-gray-400 font-semibold italic bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg border border-emerald-100 flex items-center">
                    <i class="fas fa-info-circle mr-1.5 text-sm"></i> Klik pada kotak jam pelajaran untuk menambah/mengubah jadwal.
                </div>
            </div>

            <!-- Pesan Notifikasi -->
            <?php if(!empty($pesan_sukses)): ?>
                <div class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-check-circle mr-2 text-lg"></i> <?= htmlspecialchars($pesan_sukses) ?>
                </div>
            <?php endif; ?>
            <?php if(!empty($pesan_error)): ?>
                <div class="bg-rose-100 text-rose-800 border border-rose-200 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center text-xs">
                    <i class="fas fa-exclamation-circle mr-2 text-lg"></i> <?= htmlspecialchars($pesan_error) ?>
                </div>
            <?php endif; ?>

            <!-- CONTAINER TABEL -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-x-auto max-w-full text-left">
                <div class="min-w-[1280px]">
                    <table class="sched-table w-full border-collapse border-green-dark text-xs text-center">
                        <thead>
                            <!-- BARIS 1: NAMA HARI -->
                            <tr class="sched-header font-bold">
                                <th class="px-3 py-3 w-[150px]" rowspan="2">Pukul</th>
                                <th class="px-2 py-3 w-[70px]" rowspan="2">Jam ke</th>
                                <?php foreach ($days as $day): ?>
                                    <th class="px-4 py-2" colspan="<?= count($kelas_list) ?>"><?= $day ?></th>
                                <?php endforeach; ?>
                            </tr>
                            <!-- BARIS 2: NAMA KELAS -->
                            <tr class="sched-subheader font-semibold text-[10px]">
                                <?php foreach ($days as $day): ?>
                                    <?php foreach ($kelas_list as $cls): ?>
                                        <th class="px-1.5 py-1.5" style="width: 75px;"><?= htmlspecialchars($cls['nama_kelas']) ?></th>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($slots_definition as $slot): ?>
                                <?php if ($slot['type'] === 'break'): ?>
                                    <!-- BARIS ISTIRAHAT -->
                                    <tr class="sched-break">
                                        <td class="py-2 text-center text-gray-700 font-bold"><?= $slot['pukul'] ?></td>
                                        <td class="py-2 text-center text-gray-500">-</td>
                                        <td class="py-2 text-center text-emerald-800 italic" colspan="<?= count($days) * count($kelas_list) ?>">
                                            <?= htmlspecialchars($slot['keterangan']) ?>
                                        </td>
                                    </tr>
                                <?php else: 
                                    $jk = $slot['jam_ke'];
                                ?>
                                    <!-- BARIS JAM PELAJARAN -->
                                    <tr class="hover:bg-slate-50 transition-colors duration-150">
                                        <td class="py-3 font-semibold text-gray-700 bg-slate-50"><?= $slot['pukul'] ?></td>
                                        <td class="py-3 font-black text-gray-800 bg-slate-50"><?= $jk ?></td>
                                        
                                        <?php foreach ($days as $day): ?>
                                            <?php foreach ($kelas_list as $cls): 
                                                $c_id = $cls['id'];
                                                $sched = $schedules[$day][$jk][$c_id] ?? null;
                                                
                                                $style_attr = "";
                                                if ($sched) {
                                                    $colors = dapatkan_warna_mapel($sched['mapel_nama']);
                                                    $style_attr = "style='background-color: {$colors['bg']}; color: {$colors['text']};'";
                                                }

                                                if ($can_edit) {
                                                    $click_handler = "onclick=\"openEditModal('{$day}', {$jk}, '{$slot['pukul']}', {$c_id}, '" . htmlspecialchars($cls['nama_kelas']) . "', '" . ($sched['mapel_id'] ?? '') . "', '" . ($sched['ustadz_id'] ?? '') . "')\"";
                                                    $cursor_class = "cursor-pointer hover:bg-emerald-50/20 transition duration-150 relative group";
                                                } else {
                                                    $click_handler = "";
                                                    $cursor_class = "relative";
                                                }
                                            ?>
                                                <td class="px-1 py-1.5 align-middle <?= $cursor_class ?>" <?= $style_attr ?> <?= $click_handler ?>>
                                                    
                                                    <?php if ($sched): ?>
                                                        <div class="flex flex-col items-center">
                                                            <span class="font-bold text-[10px] break-all leading-tight">
                                                                <?= htmlspecialchars($sched['mapel_kode']) ?>
                                                            </span>
                                                            <span class="text-[9px] opacity-75 mt-0.5 leading-none italic max-w-[70px] truncate" title="<?= htmlspecialchars($sched['ustadz_nama']) ?>" style="color: inherit;">
                                                                <?= htmlspecialchars($sched['ustadz_nama']) ?>
                                                            </span>
                                                        </div>
                                                        <?php if ($can_edit): ?>
                                                            <!-- Edit overlay icon -->
                                                            <div class="absolute inset-0 bg-black/5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-150 rounded">
                                                                <i class="fas fa-pen text-[10px]" style="color: inherit;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <?php if ($can_edit): ?>
                                                            <span class="text-gray-300 italic text-[9px] block py-1.5">+ Isi</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL POPUP EDIT JADWAL -->
    <div id="jadwalModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="closeEditModal()"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-gray-100">
                <div class="px-6 py-4 bg-emerald-800 text-white flex items-center justify-between">
                    <h3 class="font-bold text-sm flex items-center"><i class="fas fa-edit mr-2 text-amber-400"></i> Atur Jadwal Pelajaran</h3>
                    <button class="text-white hover:text-gray-200 focus:outline-none text-lg" onclick="closeEditModal()">&times;</button>
                </div>
                
                <form id="jadwalForm" action="admin-jadwal-pelajaran.php" method="POST" class="p-6 space-y-4">
                    <input type="hidden" name="action" id="modalAction" value="save">
                    <input type="hidden" name="hari" id="modalHari">
                    <input type="hidden" name="jam_ke" id="modalJamKe">
                    <input type="hidden" name="kelas_id" id="modalKelasId">
                    
                    <!-- Detail info slot -->
                    <div class="bg-slate-50 rounded-lg p-3 border border-slate-100 text-xs space-y-1.5 text-gray-600">
                        <div class="flex"><span class="font-bold w-16">Hari:</span> <span id="labelHari" class="text-gray-800 font-semibold">Senin</span></div>
                        <div class="flex"><span class="font-bold w-16">Kelas:</span> <span id="labelKelas" class="text-gray-800 font-semibold">E4 406</span></div>
                        <div class="flex"><span class="font-bold w-16">Pukul:</span> <span id="labelPukul" class="text-gray-850">04.30 - 05.15</span></div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Mata Pelajaran</label>
                        <select name="mapel_id" id="modalMapel" required class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                            <option value="">-- Pilih Mata Pelajaran --</option>
                            <?php foreach ($mapel_list as $mp): ?>
                                <option value="<?= $mp['id'] ?>"><?= htmlspecialchars(!empty($mp['kode_mapel']) ? '[' . $mp['kode_mapel'] . '] ' : '') ?><?= htmlspecialchars($mp['nama_mapel']) ?> (<?= htmlspecialchars($mp['kategori_mapel']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase tracking-wider mb-1.5">Ustadz Pengampu (Opsional)</label>
                        <select name="ustadz_id" id="modalUstadz" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-sm">
                            <option value="">-- Pilih Ustadz --</option>
                            <?php foreach ($ustadz_list as $ut): ?>
                                <option value="<?= $ut['id'] ?>"><?= htmlspecialchars($ut['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="text-[10px] text-gray-400 block mt-1">Mengambil dari akun Asatidz terdaftar.</span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-end gap-2 pt-4 border-t border-gray-100">
                        <button type="button" id="btnDelete" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-4 rounded-lg shadow-sm text-xs transition duration-150 hidden" onclick="deleteJadwal()">
                            <i class="fas fa-trash-alt mr-1"></i> Hapus Slot
                        </button>
                        <button type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-4 rounded-lg text-xs transition duration-150" onclick="closeEditModal()">
                            Batal
                        </button>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-5 rounded-lg shadow-sm text-xs transition duration-150">
                            <i class="fas fa-save mr-1"></i> Simpan Jadwal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- SCRIPTS -->
    <script>
        function openEditModal(hari, jamKe, pukul, kelasId, kelasNama, currentMapelId, currentUstadzId) {
            document.getElementById('modalHari').value = hari;
            document.getElementById('modalJamKe').value = jamKe;
            document.getElementById('modalKelasId').value = kelasId;
            
            document.getElementById('labelHari').innerText = hari;
            document.getElementById('labelKelas').innerText = kelasNama;
            document.getElementById('labelPukul').innerText = pukul;
            
            document.getElementById('modalMapel').value = currentMapelId;
            document.getElementById('modalUstadz').value = currentUstadzId;
            
            document.getElementById('modalAction').value = 'save';
            
            const btnDelete = document.getElementById('btnDelete');
            if (currentMapelId !== '') {
                btnDelete.classList.remove('hidden');
            } else {
                btnDelete.classList.add('hidden');
            }
            
            document.getElementById('jadwalModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('jadwalModal').classList.add('hidden');
        }

        function deleteJadwal() {
            if (confirm("Apakah Bos yakin ingin menghapus jadwal mata pelajaran di slot ini?")) {
                document.getElementById('modalAction').value = 'delete';
                document.getElementById('jadwalForm').submit();
            }
        }
    </script>
</body>
</html>
