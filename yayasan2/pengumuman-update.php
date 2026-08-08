<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'pengumuman_update';

// 1. Inisialisasi Tabel
$conn->query("CREATE TABLE IF NOT EXISTS app_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    konten TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// 2. Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM app_updates WHERE id = $id");
    header("Location: pengumuman-update.php?sukses=deleted");
    exit;
}

// 3. Simpan / Update Data
$pesan_sukses = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $judul = $conn->real_escape_string($_POST['judul']);
    $konten = $conn->real_escape_string($_POST['konten']);

    if ($id > 0) {
        $sql = "UPDATE app_updates SET judul='$judul', konten='$konten' WHERE id=$id";
        $conn->query($sql);
        header("Location: pengumuman-update.php?sukses=updated");
        exit;
    } else {
        $sql = "INSERT INTO app_updates (judul, konten) VALUES ('$judul', '$konten')";
        $conn->query($sql);
        header("Location: pengumuman-update.php?sukses=created");
        exit;
    }
}

$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM app_updates WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

if (isset($_GET['sukses'])) {
    if ($_GET['sukses'] === 'created') $pesan_sukses = "Pengumuman update berhasil dipublikasikan!";
    elseif ($_GET['sukses'] === 'updated') $pesan_sukses = "Pengumuman update berhasil diperbarui!";
    elseif ($_GET['sukses'] === 'deleted') $pesan_sukses = "Pengumuman update berhasil dihapus!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Update Fitur | Ruang Yayasan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2>
            </div>
            <div class="flex items-center space-x-4">
                <div class="h-8 w-8 rounded-full bg-amber-500 flex items-center justify-center text-gray-900 font-bold shadow-sm">Y2</div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <i class="fas fa-bullhorn text-amber-600"></i>
                        <span>Kelola Update Fitur Aplikasi</span>
                    </h1>
                    <p class="text-gray-500 mt-1">Publikasikan informasi fitur baru atau pembaruan aplikasi langsung kepada seluruh Pegawai & Asatidz.</p>
                </div>
            </div>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-emerald-50 border border-emerald-250 text-emerald-800 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center">
                    <i class="fas fa-check-circle mr-2 text-emerald-600"></i>
                    <span><?= htmlspecialchars($pesan_sukses) ?></span>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- FORM PEMBUATAN / EDIT -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-150 p-6 h-fit">
                    <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wider mb-4 pb-2 border-b flex items-center gap-1.5">
                        <i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-plus' ?> text-amber-600"></i>
                        <span><?= $edit_mode ? 'Edit Pengumuman' : 'Buat Pengumuman Baru' ?></span>
                    </h2>
                    
                    <form action="pengumuman-update.php" method="POST" class="space-y-4">
                        <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Judul / Fitur Baru</label>
                            <input type="text" name="judul" value="<?= $edit_mode ? htmlspecialchars($data_edit['judul']) : '' ?>" required placeholder="Contoh: Pemisahan Menu Rapat" class="w-full px-3.5 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-amber-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase">Deskripsi / Detail Pembaruan</label>
                            <textarea name="konten" rows="5" required placeholder="Jelaskan pembaruan atau cara kerja fitur baru..." class="w-full px-3.5 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-amber-500"><?= $edit_mode ? htmlspecialchars($data_edit['konten']) : '' ?></textarea>
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <?php if ($edit_mode): ?>
                                <a href="pengumuman-update.php" class="bg-gray-100 text-gray-700 font-bold px-4 py-2 rounded-xl text-xs hover:bg-gray-200 transition">
                                    Batal
                                </a>
                            <?php endif; ?>
                            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-5 py-2.5 rounded-xl transition text-xs shadow-md flex items-center gap-1.5">
                                <i class="fas fa-paper-plane text-xs"></i>
                                <span><?= $edit_mode ? 'Simpan Perubahan' : 'Publikasikan Info' ?></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- RIWAYAT PENGUMUMAN -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-150 p-6">
                    <h2 class="font-bold text-slate-800 text-sm uppercase tracking-wider mb-4 pb-2 border-b flex items-center gap-1.5">
                        <i class="fas fa-history text-amber-600"></i>
                        <span>Daftar Pengumuman Terbit</span>
                    </h2>

                    <div class="space-y-4">
                        <?php
                        $res_updates = $conn->query("SELECT * FROM app_updates ORDER BY created_at DESC");
                        if ($res_updates && $res_updates->num_rows > 0):
                            while ($row = $res_updates->fetch_assoc()):
                        ?>
                                <div class="p-4 rounded-xl border border-gray-150 bg-slate-50/50 hover:bg-slate-50 transition flex justify-between items-start gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="bg-red-100 text-red-800 text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-full flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>Update
                                            </span>
                                            <h3 class="font-bold text-sm text-gray-800"><?= htmlspecialchars($row['judul']) ?></h3>
                                        </div>
                                        <p class="text-xs text-gray-500 font-mono"><?= date('d F Y H:i', strtotime($row['created_at'])) ?> WIB</p>
                                        <p class="text-xs text-gray-600 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($row['konten']) ?></p>
                                    </div>
                                    <div class="flex items-center gap-1.5 whitespace-nowrap">
                                        <a href="pengumuman-update.php?edit_id=<?= $row['id'] ?>" class="p-1 text-blue-500 hover:text-blue-700" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="pengumuman-update.php?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus pengumuman ini?')" class="p-1 text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </div>
                                </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <div class="text-center py-12 text-gray-400 italic text-xs">
                                <i class="fas fa-bullhorn text-2xl mb-2 text-gray-300 block"></i>
                                Belum ada pengumuman pembaruan fitur yang dipublikasikan.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });
    </script>
</body>
</html>
