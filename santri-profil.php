<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$active_menu = 'edit_profil_santri';
$pesan_sukses = '';
$pesan_error = '';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];

// Handle POST request untuk update data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Daftar kolom yang boleh diubah oleh santri
    $editable_fields = [
        'nik', 'tempat_lahir', 'tanggal_lahir', 'alamat_lengkap', 'foto_santri', 'asal_sekolah',
        'nama_ayah', 'pekerjaan_ayah', 'no_whatsapp_ayah', 'alamat_ayah',
        'nama_ibu', 'pekerjaan_ibu', 'no_whatsapp_ibu', 'alamat_ibu',
        'nama_wali', 'pekerjaan_wali', 'alamat_wali', 'no_whatsapp_wali'
    ];

    $set_clause = [];
    $bind_types = '';
    $bind_values = [];

    foreach ($editable_fields as $field) {
        if (isset($_POST[$field])) {
            $set_clause[] = "$field = ?";
            $bind_types .= 's'; // Anggap semua string untuk binding
            $value = $_POST[$field];
            
            // Handle jika tanggal lahir dikosongkan
            if ($field === 'tanggal_lahir' && empty($value)) {
                $bind_values[] = null;
            } else {
                $bind_values[] = $value;
            }
        }
    }

    if (!empty($set_clause)) {
        $sql = "UPDATE buku_induk_santri SET " . implode(', ', $set_clause) . " WHERE id = ?";
        $bind_types .= 'i';
        $bind_values[] = $santri_id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($bind_types, ...$bind_values);

        if ($stmt->execute()) {
            $pesan_sukses = "Profil Anda berhasil diperbarui!";
        } else {
            $pesan_error = "Gagal memperbarui profil: " . $conn->error;
        }
        $stmt->close();
    } else {
        $pesan_error = "Tidak ada data yang dikirim untuk diubah.";
    }
}

// Ambil data santri terbaru untuk ditampilkan di form
$stmt_santri = $conn->prepare("SELECT * FROM buku_induk_santri WHERE id = ?");
$stmt_santri->bind_param("i", $santri_id);
$stmt_santri->execute();
$data_santri = $stmt_santri->get_result()->fetch_assoc();
$stmt_santri->close();

if (!$data_santri) {
    die("Error: Data santri tidak ditemukan. Silakan hubungi admin.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil | <?= htmlspecialchars($santri_nama) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-santri.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-santri" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-edit text-indigo-600 mr-2"></i>Edit Profil Diri</h1>
                <p class="text-gray-500 mt-1">Lengkapi dan perbarui data diri Anda di sini.</p>
            </div>
            
            <?php if($pesan_sukses): ?><div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?></div><?php endif; ?>
            <?php if($pesan_error): ?><div class="bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center"><i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?></div><?php endif; ?>

            <form action="santri-profil.php" method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                <!-- DATA PRIBADI (Read-only & Editable) -->
                <div class="mb-6 border-b pb-6">
                    <h3 class="font-bold text-gray-800 mb-4">I. Data Pribadi Santri</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-sm font-medium">Nama Lengkap</label><input type="text" value="<?= htmlspecialchars($data_santri['nama_lengkap']) ?>" readonly class="w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100 cursor-not-allowed"></div>
                        <div><label class="text-sm font-medium">NIS</label><input type="text" value="<?= htmlspecialchars($data_santri['nis']) ?>" readonly class="w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100 cursor-not-allowed"></div>
                        <div><label class="text-sm font-medium">NISN</label><input type="text" value="<?= htmlspecialchars($data_santri['nisn']) ?>" readonly class="w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100 cursor-not-allowed"></div>
                        <div><label class="text-sm font-medium">NIK</label><input type="text" name="nik" value="<?= htmlspecialchars($data_santri['nik']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">Tempat Lahir</label><input type="text" name="tempat_lahir" value="<?= htmlspecialchars($data_santri['tempat_lahir']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= $data_santri['tanggal_lahir'] ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Lengkap</label><input type="text" name="alamat_lengkap" value="<?= htmlspecialchars($data_santri['alamat_lengkap']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                    </div>
                </div>

                <!-- DATA AKADEMIK (Read-only) -->
                <div class="mb-6 border-b pb-6">
                    <h3 class="font-bold text-gray-800 mb-4">II. Data Akademik & Asrama</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div><label class="text-sm font-medium">Kelas Sekarang</label><input type="text" value="<?= htmlspecialchars($data_santri['kelas_sekarang']) ?>" readonly class="w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100 cursor-not-allowed"></div>
                        <div><label class="text-sm font-medium">Kamar Asrama</label><input type="text" value="<?= htmlspecialchars($data_santri['kamar_asrama']) ?>" readonly class="w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100 cursor-not-allowed"></div>
                        <div><label class="text-sm font-medium">Status Santri</label><input type="text" value="<?= htmlspecialchars($data_santri['status_santri']) ?>" readonly class="w-full mt-1 px-3 py-2 border rounded-lg text-sm bg-gray-100 cursor-not-allowed"></div>
                        <div><label class="text-sm font-medium">Asal Sekolah</label><input type="text" name="asal_sekolah" value="<?= htmlspecialchars($data_santri['asal_sekolah']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="md:col-span-2"><label class="text-sm font-medium">URL Foto Santri</label><input type="text" name="foto_santri" value="<?= htmlspecialchars($data_santri['foto_santri']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500" placeholder="https://..."></div>
                    </div>
                </div>

                <!-- DATA ORTU (Editable) -->
                <div class="mb-6">
                    <h3 class="font-bold text-gray-800 mb-4">III. Data Orang Tua / Wali</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
                        <div><label class="text-sm font-medium">Nama Ayah</label><input type="text" name="nama_ayah" value="<?= htmlspecialchars($data_santri['nama_ayah']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">Pekerjaan Ayah</label><input type="text" name="pekerjaan_ayah" value="<?= htmlspecialchars($data_santri['pekerjaan_ayah']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">No. WhatsApp Ayah</label><input type="text" name="no_whatsapp_ayah" value="<?= htmlspecialchars($data_santri['no_whatsapp_ayah']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Ayah</label><input type="text" name="alamat_ayah" value="<?= htmlspecialchars($data_santri['alamat_ayah']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="border-t col-span-3 my-2"></div>
                        <div><label class="text-sm font-medium">Nama Ibu</label><input type="text" name="nama_ibu" value="<?= htmlspecialchars($data_santri['nama_ibu']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">Pekerjaan Ibu</label><input type="text" name="pekerjaan_ibu" value="<?= htmlspecialchars($data_santri['pekerjaan_ibu']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">No. WhatsApp Ibu</label><input type="text" name="no_whatsapp_ibu" value="<?= htmlspecialchars($data_santri['no_whatsapp_ibu']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Ibu</label><input type="text" name="alamat_ibu" value="<?= htmlspecialchars($data_santri['alamat_ibu']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="border-t col-span-3 my-2"></div>
                        <div><label class="text-sm font-medium">Nama Wali (Jika ada)</label><input type="text" name="nama_wali" value="<?= htmlspecialchars($data_santri['nama_wali']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">Pekerjaan Wali</label><input type="text" name="pekerjaan_wali" value="<?= htmlspecialchars($data_santri['pekerjaan_wali']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div><label class="text-sm font-medium">No. WhatsApp Wali</label><input type="text" name="no_whatsapp_wali" value="<?= htmlspecialchars($data_santri['no_whatsapp_wali']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                        <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Wali</label><input type="text" name="alamat_wali" value="<?= htmlspecialchars($data_santri['alamat_wali']) ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-indigo-500"></div>
                    </div>
                </div>

                <div class="text-right">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> Simpan Perubahan</button>
                </div>
            </form>
        </main>
    </div>
    <script>document.addEventListener('DOMContentLoaded', function() { const sidebar = document.getElementById('sidebar-santri'); const openBtn = document.getElementById('open-sidebar-santri'); const overlay = document.getElementById('sidebar-overlay-santri'); if(openBtn) openBtn.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }); if(overlay) overlay.addEventListener('click', () => { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }); });</script>
</body>
</html>