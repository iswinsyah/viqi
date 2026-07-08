<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// --- SETUP ---
$active_menu = 'kesediaan_mengajar';
$ustadz_id_login = $_SESSION['ustadz_id'];
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$is_admin_view = in_array('super_admin', $user_roles) || in_array('kepala_sekolah', $user_roles);

// 1. Buat Tabel Otomatis (Self-healing)
$conn->query("CREATE TABLE IF NOT EXISTS master_mapel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_mapel VARCHAR(150) UNIQUE NOT NULL,
    kategori_mapel ENUM('Diknas', 'Diniyah', 'Ekstrakurikuler', 'Lainnya') DEFAULT 'Lainnya',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS kesediaan_mengajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ustadz_id INT UNIQUE NOT NULL,
    mapel_1_id INT NULL,
    mapel_2_id INT NULL,
    mapel_3_id INT NULL,
    hari_1 VARCHAR(20),
    hari_2 VARCHAR(20),
    hari_3 VARCHAR(20),
    jam_1 VARCHAR(50),
    jam_2 VARCHAR(50),
    jam_3 VARCHAR(50),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ustadz_id) REFERENCES akun_ustadz(id) ON DELETE CASCADE
)");

// Self-healing: Tambahkan kolom kelas jika belum ada
@$conn->query("ALTER TABLE kesediaan_mengajar ADD COLUMN kelas_1 VARCHAR(50) NULL AFTER mapel_1_id");
@$conn->query("ALTER TABLE kesediaan_mengajar ADD COLUMN kelas_2 VARCHAR(50) NULL AFTER mapel_2_id");
@$conn->query("ALTER TABLE kesediaan_mengajar ADD COLUMN kelas_3 VARCHAR(50) NULL AFTER mapel_3_id");

// 2. Proses Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_ustadz_id = $is_admin_view ? (int)$_POST['ustadz_id'] : $ustadz_id_login;

    if ($target_ustadz_id > 0) {
        $mapel_1_id = !empty($_POST['mapel_1_id']) ? (int)$_POST['mapel_1_id'] : 'NULL';
        $mapel_2_id = !empty($_POST['mapel_2_id']) ? (int)$_POST['mapel_2_id'] : 'NULL';
        $mapel_3_id = !empty($_POST['mapel_3_id']) ? (int)$_POST['mapel_3_id'] : 'NULL';
        $kelas_1 = $conn->real_escape_string($_POST['kelas_1'] ?? '');
        $kelas_2 = $conn->real_escape_string($_POST['kelas_2'] ?? '');
        $kelas_3 = $conn->real_escape_string($_POST['kelas_3'] ?? '');
        $hari_1 = $conn->real_escape_string($_POST['hari_1']);
        $hari_2 = $conn->real_escape_string($_POST['hari_2']);
        $hari_3 = $conn->real_escape_string($_POST['hari_3']);
        $jam_1 = $conn->real_escape_string($_POST['jam_1']);
        $jam_2 = $conn->real_escape_string($_POST['jam_2']);
        $jam_3 = $conn->real_escape_string($_POST['jam_3']);

        $sql = "INSERT INTO kesediaan_mengajar (ustadz_id, mapel_1_id, kelas_1, mapel_2_id, kelas_2, mapel_3_id, kelas_3, hari_1, hari_2, hari_3, jam_1, jam_2, jam_3) 
                VALUES ($target_ustadz_id, $mapel_1_id, '$kelas_1', $mapel_2_id, '$kelas_2', $mapel_3_id, '$kelas_3', '$hari_1', '$hari_2', '$hari_3', '$jam_1', '$jam_2', '$jam_3')
                ON DUPLICATE KEY UPDATE
                mapel_1_id = VALUES(mapel_1_id), kelas_1 = VALUES(kelas_1),
                mapel_2_id = VALUES(mapel_2_id), kelas_2 = VALUES(kelas_2),
                mapel_3_id = VALUES(mapel_3_id), kelas_3 = VALUES(kelas_3),
                hari_1 = VALUES(hari_1), hari_2 = VALUES(hari_2), hari_3 = VALUES(hari_3),
                jam_1 = VALUES(jam_1), jam_2 = VALUES(jam_2), jam_3 = VALUES(jam_3)";
        
        if ($conn->query($sql)) {
            $pesan_sukses = "Data kesediaan mengajar berhasil disimpan!";
        } else {
            $pesan_error = "Gagal menyimpan data: " . $conn->error;
        }
    } else {
        $pesan_error = "Ustadz belum dipilih.";
    }
}

// --- PERSIAPAN DATA UNTUK FORM ---
$ustadz_list = [];
if ($is_admin_view) {
    $res_ustadz = $conn->query("SELECT id, nama FROM akun_ustadz ORDER BY nama ASC");
    if ($res_ustadz) while($r = $res_ustadz->fetch_assoc()) $ustadz_list[] = $r;
}

$mapel_list = [];
$res_mapel = $conn->query("SELECT id, nama_mapel, kategori_mapel FROM master_mapel WHERE status_aktif = 1 ORDER BY kategori_mapel, nama_mapel ASC");
if ($res_mapel) {
    while($row = $res_mapel->fetch_assoc()) {
        $mapel_list[$row['kategori_mapel']][] = $row;
    }
}

$hari_opsi = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

// Ambil daftar kelas dari Master Kelas (Ruang Yayasan)
$daftar_kelas = [];
$res_kelas = $conn->query("SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
if ($res_kelas && $res_kelas->num_rows > 0) {
    while($row = $res_kelas->fetch_assoc()) {
        $daftar_kelas[] = $row['nama_kelas'];
    }
}

// Opsi Pilihan Jam sesuai request
$jam_opsi = [
    '04.30 sd 06.00',
    '07.00 sd 08.30',
    '08.30 sd 10.00',
    '10.00 sd 11.30',
    '15.30 sd 17.00',
    '19.00 sd 19.45'
];

$data_kesediaan = null;
$ustadz_to_load = $is_admin_view ? ($_GET['ustadz_id'] ?? 0) : $ustadz_id_login;
if ($ustadz_to_load > 0) {
    $res_kesediaan = $conn->query("SELECT * FROM kesediaan_mengajar WHERE ustadz_id = $ustadz_to_load");
    if ($res_kesediaan) $data_kesediaan = $res_kesediaan->fetch_assoc();
}

$semua_kesediaan = [];
if ($is_admin_view) {
    $sql_all = "SELECT k.*, u.nama as nama_ustadz, 
                m1.nama_mapel as mapel1, m2.nama_mapel as mapel2, m3.nama_mapel as mapel3
                FROM kesediaan_mengajar k
                JOIN akun_ustadz u ON k.ustadz_id = u.id
                LEFT JOIN master_mapel m1 ON k.mapel_1_id = m1.id
                LEFT JOIN master_mapel m2 ON k.mapel_2_id = m2.id
                LEFT JOIN master_mapel m3 ON k.mapel_3_id = m3.id
                ORDER BY u.nama ASC";
    $res_all = $conn->query($sql_all);
    if ($res_all) while($r = $res_all->fetch_assoc()) $semua_kesediaan[] = $r;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kesediaan Mengajar | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-clock text-cyan-600 mr-2"></i>Kesediaan Mengajar</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas fa-edit mr-2"></i>Formulir Kesediaan Mengajar</h2></div>
                <form action="admin-pegawai-kesediaan.php<?= $ustadz_to_load > 0 ? '?ustadz_id='.$ustadz_to_load : '' ?>" method="POST" class="p-6">
                    <div class="mb-4"><label class="block text-sm font-medium text-gray-700 mb-1">Nama Ustadz</label>
                        <?php if ($is_admin_view): ?>
                            <select name="ustadz_id" onchange="window.location.href='admin-pegawai-kesediaan.php?ustadz_id='+this.value" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 bg-white"><option value="">-- Pilih Ustadz untuk diisi --</option><?php foreach($ustadz_list as $u): ?><option value="<?= $u['id'] ?>" <?= ($ustadz_to_load == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['nama']) ?></option><?php endforeach; ?></select>
                        <?php else: ?>
                            <input type="text" value="<?= htmlspecialchars($_SESSION['ustadz_nama']) ?>" readonly class="w-full px-4 py-2 border rounded-lg bg-gray-100 cursor-not-allowed"><input type="hidden" name="ustadz_id" value="<?= $ustadz_id_login ?>">
                        <?php endif; ?>
                    </div>

                    <?php if ($ustadz_to_load > 0 || !$is_admin_view): ?>
                    <div class="border-t pt-6 mt-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-x-6 gap-y-4">
                            <div class="font-bold text-gray-700">Mata Pelajaran</div><div class="font-bold text-gray-700">Kelas</div><div class="font-bold text-gray-700">Pilihan Hari</div><div class="font-bold text-gray-700">Pilihan Jam</div>
                            <?php for ($i = 1; $i <= 3; $i++): ?>
                                <div><select name="mapel_<?= $i ?>_id" class="w-full px-3 py-2 border rounded-lg text-sm bg-white"><option value="">-- Pilih Mapel --</option><?php foreach ($mapel_list as $kategori => $mapels): ?><optgroup label="<?= htmlspecialchars($kategori) ?>"><?php foreach ($mapels as $mapel): ?><option value="<?= $mapel['id'] ?>" <?= (isset($data_kesediaan['mapel_'.$i.'_id']) && $data_kesediaan['mapel_'.$i.'_id'] == $mapel['id']) ? 'selected' : '' ?>><?= htmlspecialchars($mapel['nama_mapel']) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select></div>
                                <div>
                                    <select name="kelas_<?= $i ?>" class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php
                                        $kelas_tersimpan = $data_kesediaan['kelas_'.$i] ?? '';
                                        foreach ($daftar_kelas as $nama_kelas) {
                                            $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                            echo "<option value=\"".htmlspecialchars($nama_kelas)."\" $sel>".htmlspecialchars($nama_kelas)."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div><select name="hari_<?= $i ?>" class="w-full px-3 py-2 border rounded-lg text-sm bg-white"><option value="">-- Pilih Hari --</option><?php foreach ($hari_opsi as $h): ?><option value="<?= $h ?>" <?= (isset($data_kesediaan['hari_'.$i]) && $data_kesediaan['hari_'.$i] == $h) ? 'selected' : '' ?>><?= $h ?></option><?php endforeach; ?></select></div>
                                <div>
                                    <select name="jam_<?= $i ?>" class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                                        <option value="">-- Pilih Jam --</option>
                                        <?php
                                        $jam_tersimpan = $data_kesediaan['jam_'.$i] ?? '';
                                        $jam_ada = false;
                                        foreach ($jam_opsi as $j) {
                                            $sel = ($jam_tersimpan == $j) ? 'selected' : '';
                                            if ($sel) $jam_ada = true;
                                            echo "<option value=\"".htmlspecialchars($j)."\" $sel>".htmlspecialchars($j)."</option>";
                                        }
                                        if (!empty($jam_tersimpan) && !$jam_ada) {
                                            echo "<option value=\"".htmlspecialchars($jam_tersimpan)."\" selected>".htmlspecialchars($jam_tersimpan)."</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endfor; ?>
                        </div>
                        <div class="text-right mt-6"><button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Kesediaan</button></div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($is_admin_view): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Rekapitulasi Kesediaan Mengajar</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white"><tr><th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Ustadz</th><th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Opsi 1 (Mapel, Hari, Jam)</th><th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Opsi 2</th><th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Opsi 3</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(empty($semua_kesediaan)): ?>
                                <tr><td colspan="4" class="text-center py-6 text-gray-500 italic">Belum ada data kesediaan mengajar.</td></tr>
                            <?php else: foreach($semua_kesediaan as $d): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 font-bold text-gray-900"><a href="?ustadz_id=<?= $d['ustadz_id'] ?>" class="text-cyan-600 hover:underline"><?= htmlspecialchars($d['nama_ustadz']) ?></a></td>
                                    <td class="px-4 py-3 text-sm"><div><b><?= htmlspecialchars($d['mapel1'] ?? '-') ?></b> <?= !empty($d['kelas_1']) ? '('.htmlspecialchars($d['kelas_1']).')' : '' ?></div><div class="text-gray-600"><?= htmlspecialchars($d['hari_1'] ?? '-') ?>, <?= htmlspecialchars($d['jam_1'] ?? '-') ?></div></td>
                                    <td class="px-4 py-3 text-sm"><div><b><?= htmlspecialchars($d['mapel2'] ?? '-') ?></b> <?= !empty($d['kelas_2']) ? '('.htmlspecialchars($d['kelas_2']).')' : '' ?></div><div class="text-gray-600"><?= htmlspecialchars($d['hari_2'] ?? '-') ?>, <?= htmlspecialchars($d['jam_2'] ?? '-') ?></div></td>
                                    <td class="px-4 py-3 text-sm"><div><b><?= htmlspecialchars($d['mapel3'] ?? '-') ?></b> <?= !empty($d['kelas_3']) ? '('.htmlspecialchars($d['kelas_3']).')' : '' ?></div><div class="text-gray-600"><?= htmlspecialchars($d['hari_3'] ?? '-') ?>, <?= htmlspecialchars($d['jam_3'] ?? '-') ?></div></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script>document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });</script>
</body>
</html>