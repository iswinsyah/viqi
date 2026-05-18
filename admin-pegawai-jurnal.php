<?php
require_once 'auth.php';
require_once 'koneksi.php';

// 1. Buat Tabel Otomatis
$conn->query("CREATE TABLE IF NOT EXISTS jurnal_mengajar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE,
    kelas VARCHAR(50),
    mata_pelajaran VARCHAR(100),
    materi TEXT,
    absensi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM jurnal_mengajar WHERE id = $id");
    header("Location: admin-pegawai-jurnal.php");
    exit;
}

// 3. Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $tanggal = $conn->real_escape_string($_POST['tanggal']);
    $kelas = $conn->real_escape_string($_POST['kelas']);
    $mata_pelajaran = $conn->real_escape_string($_POST['mata_pelajaran']);
    $materi = $conn->real_escape_string($_POST['materi']);
    $absensi = $conn->real_escape_string($_POST['absensi']);

    if ($id > 0) {
        $sql = "UPDATE jurnal_mengajar SET tanggal='$tanggal', kelas='$kelas', mata_pelajaran='$mata_pelajaran', materi='$materi', absensi='$absensi' WHERE id=$id";
        $pesan_sukses = "Jurnal berhasil diupdate!";
    } else {
        $sql = "INSERT INTO jurnal_mengajar (tanggal, kelas, mata_pelajaran, materi, absensi) VALUES ('$tanggal', '$kelas', '$mata_pelajaran', '$materi', '$absensi')";
        $pesan_sukses = "Jurnal baru berhasil disimpan!";
    }
    $conn->query($sql);
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM jurnal_mengajar WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

$active_menu = 'jurnal_mengajar';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Mengajar | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Tambahkan Library HTML5 QR Code Scanner -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book-open text-cyan-600 mr-2"></i>Jurnal Mengajar Harian</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Jurnal' : 'Isi Jurnal Baru' ?></h2></div>
                <form action="admin-pegawai-jurnal.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" value="<?= $edit_mode ? $data_edit['tanggal'] : date('Y-m-d') ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kelas / Rombel</label>
                            <div class="flex gap-2">
                                <select name="kelas" id="input-kelas" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                                <option value="">-- Pilih Kelas --</option>
                                <?php
                                $daftar_kelas = [
                                    'Kelas 7', 'Kelas 8', 'Kelas 9', 'Kelas 10', 'Kelas 11', 'Kelas 12',
                                    'Kelas A', 'Kelas B', 'Kelas C',
                                    'Kelas Rijal', 'Kelas Nisa'
                                ];
                                $kelas_tersimpan = $edit_mode ? $data_edit['kelas'] : '';
                                $ada_di_list = false;

                                foreach ($daftar_kelas as $nama_kelas) {
                                    $sel = ($kelas_tersimpan == $nama_kelas) ? 'selected' : '';
                                    if ($sel) $ada_di_list = true;
                                    echo "<option value=\"$nama_kelas\" $sel>$nama_kelas</option>";
                                }
                                
                                // Jaga-jaga jika data lama diketik manual dan tidak ada di daftar kombinasi baru
                                if ($edit_mode && !$ada_di_list && !empty($kelas_tersimpan)) {
                                    echo "<option value=\"".htmlspecialchars($kelas_tersimpan)."\" selected>".htmlspecialchars($kelas_tersimpan)." (Data Lama)</option>";
                                }
                                ?>
                            </select>
                                <button type="button" onclick="bukaScanner()" class="bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-4 py-2 rounded-lg font-bold transition shadow-sm border border-emerald-200 flex items-center justify-center" title="Scan QR Kelas">
                                    <i class="fas fa-qrcode text-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                            <input type="text" name="mata_pelajaran" value="<?= $edit_mode ? htmlspecialchars($data_edit['mata_pelajaran']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Fiqih Ibadah">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Materi / Bahasan Hari Ini</label>
                            <textarea name="materi" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Tuliskan ringkasan materi..."><?= $edit_mode ? htmlspecialchars($data_edit['materi']) : '' ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Absensi / Kendala Kelas</label>
                            <textarea name="absensi" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Ahmad sakit, Budi izin..."><?= $edit_mode ? htmlspecialchars($data_edit['absensi']) : '' ?></textarea>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-pegawai-jurnal.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Jurnal' : 'Simpan Jurnal' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Riwayat Jurnal Mengajar</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Tanggal</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kelas & Mapel</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Materi Pokok</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT * FROM jurnal_mengajar ORDER BY tanggal DESC, id DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700 font-medium whitespace-nowrap"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="font-bold text-cyan-700"><?= htmlspecialchars($row['kelas']) ?></div>
                                        <div class="text-sm text-gray-600"><?= htmlspecialchars($row['mata_pelajaran']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div class="font-medium"><?= htmlspecialchars($row['materi']) ?></div>
                                        <div class="text-xs text-rose-500 mt-1"><i class="fas fa-user-times"></i> Absen/Kendala: <?= empty($row['absensi']) ? 'Nihil' : htmlspecialchars($row['absensi']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-2" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus jurnal ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='4' class='text-center py-6 text-gray-500 italic'>Belum ada catatan jurnal mengajar.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- MODAL SCANNER QR -->
    <div id="qr-modal" class="fixed inset-0 bg-black bg-opacity-75 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 border-b flex justify-between items-center bg-gray-50">
                <h3 class="font-bold text-gray-800"><i class="fas fa-qrcode mr-2 text-emerald-600"></i> Scan QR Ruang Kelas</h3>
                <button type="button" onclick="tutupScanner()" class="text-gray-400 hover:text-red-500 text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div class="p-4 text-center">
                <p class="text-sm text-gray-500 mb-4">Arahkan kamera HP Anda ke stiker QR Code yang tertempel di dinding kelas.</p>
                <div id="reader" class="w-full bg-black rounded-lg overflow-hidden min-h-[300px]"></div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });

        // ==========================================
        // FUNGSI SCANNER QR CODE (HTML5)
        // ==========================================
        let html5QrcodeScanner = null;

        function bukaScanner() {
            document.getElementById('qr-modal').classList.remove('hidden');
            html5QrcodeScanner = new Html5QrcodeScanner(
                "reader", { fps: 10, qrbox: {width: 250, height: 250} }, false
            );
            html5QrcodeScanner.render(onScanSuccess);
        }

        function tutupScanner() {
            document.getElementById('qr-modal').classList.add('hidden');
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear().catch(error => console.error("Gagal mematikan scanner.", error));
            }
        }

        function onScanSuccess(decodedText, decodedResult) {
            const selectKelas = document.getElementById('input-kelas');
            let found = false;
            
            for (let i = 0; i < selectKelas.options.length; i++) {
                if (selectKelas.options[i].value === decodedText) {
                    selectKelas.selectedIndex = i;
                    found = true; break;
                }
            }
            tutupScanner();
            if (found) alert("Berhasil! Kamera mendeteksi Anda berada di " + decodedText);
            else alert("QR Code (" + decodedText + ") tidak dikenali oleh sistem. Pastikan Anda men-scan QR yang benar.");
        }
    </script>
</body>
</html>