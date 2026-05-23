<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

// ROBUST TABLE CREATION
// 1. Pastikan tabel akun_orangtua ada SEBELUM membuat tabel buku_induk_santri
$conn->query("CREATE TABLE IF NOT EXISTS akun_orangtua (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_orangtua VARCHAR(150) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    no_whatsapp VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// 2. Buat tabel buku_induk_santri
$conn->query("CREATE TABLE IF NOT EXISTS buku_induk_santri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(150) NOT NULL,
    nis VARCHAR(50) UNIQUE,
    nisn VARCHAR(50) UNIQUE,
    username VARCHAR(50) UNIQUE,
    id_orangtua INT NULL,
    password VARCHAR(255),
    nik VARCHAR(50),
    tempat_lahir VARCHAR(100),
    tanggal_lahir DATE,
    jenis_kelamin ENUM('Laki-laki', 'Perempuan'),
    alamat_lengkap TEXT,
    foto_santri VARCHAR(255),
    tanggal_masuk DATE,
    asal_sekolah VARCHAR(150),
    status_santri ENUM('Aktif', 'Lulus', 'Pindah', 'Dikeluarkan', 'Mengundurkan Diri') DEFAULT 'Aktif',
    kelas_sekarang VARCHAR(50),
    kamar_asrama VARCHAR(50),
    nama_ayah VARCHAR(150),
    pekerjaan_ayah VARCHAR(100),
    no_whatsapp_ayah VARCHAR(20),
    alamat_ayah TEXT,
    nama_ibu VARCHAR(150),
    pekerjaan_ibu VARCHAR(100),
    no_whatsapp_ibu VARCHAR(20),
    alamat_ibu TEXT,
    nama_wali VARCHAR(150),
    pekerjaan_wali VARCHAR(100),
    alamat_wali TEXT,
    no_whatsapp_wali VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// 3. Tambahkan foreign key (gunakan @ untuk silent error jika sudah ada)
@$conn->query("ALTER TABLE buku_induk_santri ADD CONSTRAINT fk_id_orangtua FOREIGN KEY (id_orangtua) REFERENCES akun_orangtua(id) ON DELETE SET NULL ON UPDATE CASCADE");

// 4. Pastikan semua kolom ada (self-healing untuk update struktur tabel lama)
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN username VARCHAR(50) UNIQUE AFTER nisn");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN password VARCHAR(255) AFTER username");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN id_orangtua INT NULL AFTER password");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN nik VARCHAR(50) AFTER id_orangtua");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN tempat_lahir VARCHAR(100) AFTER nik");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN tanggal_lahir DATE AFTER tempat_lahir");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN jenis_kelamin ENUM('Laki-laki', 'Perempuan') AFTER tanggal_lahir");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN alamat_lengkap TEXT AFTER jenis_kelamin");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN foto_santri VARCHAR(255) AFTER alamat_lengkap");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN tanggal_masuk DATE AFTER foto_santri");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN asal_sekolah VARCHAR(150) AFTER tanggal_masuk");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN status_santri ENUM('Aktif', 'Lulus', 'Pindah', 'Dikeluarkan', 'Mengundurkan Diri') DEFAULT 'Aktif' AFTER asal_sekolah");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN kelas_sekarang VARCHAR(50) AFTER status_santri");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN kamar_asrama VARCHAR(50) AFTER kelas_sekarang");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN nama_ayah VARCHAR(150) AFTER kamar_asrama");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN pekerjaan_ayah VARCHAR(100) AFTER nama_ayah");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN no_whatsapp_ayah VARCHAR(20) AFTER pekerjaan_ayah");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN alamat_ayah TEXT AFTER no_whatsapp_ayah");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN nama_ibu VARCHAR(150) AFTER alamat_ayah");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN pekerjaan_ibu VARCHAR(100) AFTER nama_ibu");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN no_whatsapp_ibu VARCHAR(20) AFTER pekerjaan_ibu");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN alamat_ibu TEXT AFTER no_whatsapp_ibu");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN nama_wali VARCHAR(150) AFTER alamat_ibu");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN pekerjaan_wali VARCHAR(100) AFTER nama_wali");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN alamat_wali TEXT AFTER pekerjaan_wali");
@$conn->query("ALTER TABLE buku_induk_santri ADD COLUMN no_whatsapp_wali VARCHAR(20) AFTER alamat_wali");

// CRUD LOGIC
// Hapus Data
if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM buku_induk_santri WHERE id = $id");
    header("Location: admin-buku-induk.php");
    exit;
}

// Simpan / Update Data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $fields = [
            'nama_lengkap', 'nis', 'nisn', 'nik', 'tempat_lahir', 'tanggal_lahir', 
            'username', 'password', 'jenis_kelamin', 'alamat_lengkap', 'foto_santri', 'tanggal_masuk', 
            'id_orangtua',
            'asal_sekolah', 'status_santri', 'kelas_sekarang', 'kamar_asrama', 
            'nama_ayah', 'pekerjaan_ayah', 'nama_ibu', 'pekerjaan_ibu', 
            'no_whatsapp_ayah', 'alamat_ayah', 'no_whatsapp_ibu', 'alamat_ibu',
            'nama_wali', 'pekerjaan_wali', 'alamat_wali', 'no_whatsapp_wali'
        ];
    $pesan_error = '';

    // VALIDASI WAJIB DIISI (Hanya untuk 3 kolom utama)
    if (empty($_POST['nama_lengkap']) || empty($_POST['username']) || ($id == 0 && empty($_POST['password']))) {
        $pesan_error = "Untuk input cepat, minimal Nama Lengkap, Username, dan Password wajib diisi!";
    }

    // VALIDASI USERNAME DUPLIKAT
    if (empty($pesan_error) && !empty($_POST['username'])) {
        $check_username = $conn->query("SELECT id FROM buku_induk_santri WHERE username = '" . $conn->real_escape_string($_POST['username']) . "' AND id != $id");
        if ($check_username && $check_username->num_rows > 0) {
            $pesan_error = "Username santri '" . htmlspecialchars($_POST['username']) . "' sudah terpakai!";
        }
    }

    if (empty($pesan_error)) {
        $set_clause = [];
        foreach ($fields as $field) {
            $value = $_POST[$field] ?? '';
            
            // Jangan update password jika kosong saat edit
            if ($id > 0 && $field === 'password' && empty($value)) {
                continue;
            }

            // Handle kolom unik (seperti NIS/NISN) dan kolom tanggal/foreign key yang boleh kosong
            if (in_array($field, ['nis', 'nisn', 'tanggal_lahir', 'tanggal_masuk', 'id_orangtua']) && trim($value) === '') {
                $set_clause[] = "$field = NULL";
            } else {
                $set_clause[] = "$field = '" . $conn->real_escape_string($value) . "'";
            }
        }

        if ($id > 0) {
            $sql = "UPDATE buku_induk_santri SET " . implode(', ', $set_clause) . " WHERE id=$id";
            $pesan_sukses = "Data santri berhasil diupdate!";
        } else {
            $sql = "INSERT INTO buku_induk_santri SET " . implode(', ', $set_clause);
            $pesan_sukses = "Data santri baru berhasil ditambahkan!";
        }
        if (!$conn->query($sql)) {
            $pesan_error = "Gagal menyimpan data: " . $conn->error;
        }
    }
}

// Ambil data untuk mode edit
$edit_mode = false;
$data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}

// Ambil data akun orang tua untuk dropdown
$akun_orangtua = [];
$res_ortu = $conn->query("SELECT id, nama_orangtua, username FROM akun_orangtua ORDER BY nama_orangtua ASC");
if ($res_ortu) {
    while($row_ortu = $res_ortu->fetch_assoc()) {
        $akun_orangtua[] = $row_ortu;
    }
}

$active_menu = 'buku_induk';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Induk Santri | Ruang Staf</title>
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
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-book-user text-cyan-600 mr-2"></i>Buku Induk Santri</h1></div>
            
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-slate-50 border-b border-gray-100"><h2 class="font-bold text-slate-800"><i class="fas <?= $edit_mode ? 'fa-user-edit' : 'fa-user-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Data Santri' : 'Input Santri Baru' ?></h2></div>
                <form action="admin-buku-induk.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    
                    <!-- DATA PRIBADI -->
                    <div class="mb-6 border border-gray-200 rounded-lg p-5 bg-gray-50/50">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">I. Data Pribadi Santri</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-sm font-medium">Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_lengkap']) : '' ?>" required class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">NIS</label><input type="text" name="nis" value="<?= $edit_mode ? htmlspecialchars($data_edit['nis']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">NISN</label><input type="text" name="nisn" value="<?= $edit_mode ? htmlspecialchars($data_edit['nisn']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">NIK</label><input type="text" name="nik" value="<?= $edit_mode ? htmlspecialchars($data_edit['nik']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Username Login <span class="text-red-500">*</span></label><input type="text" name="username" value="<?= $edit_mode ? htmlspecialchars($data_edit['username']) : '' ?>" required class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" placeholder="Untuk login santri"></div>
                            <div><label class="text-sm font-medium">Password Login <span class="text-red-500">*</span></label><input type="text" name="password" value="" <?= !$edit_mode ? 'required' : '' ?> class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" placeholder="<?= $edit_mode ? 'Isi untuk ganti password' : 'Wajib diisi saat buat baru' ?>"></div>
                            <div><label class="text-sm font-medium">Tempat Lahir</label><input type="text" name="tempat_lahir" value="<?= $edit_mode ? htmlspecialchars($data_edit['tempat_lahir']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Tanggal Lahir</label><input type="date" name="tanggal_lahir" value="<?= $edit_mode ? $data_edit['tanggal_lahir'] : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Jenis Kelamin</label><select name="jenis_kelamin" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"><option value="Laki-laki" <?= ($edit_mode && $data_edit['jenis_kelamin'] == 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option><option value="Perempuan" <?= ($edit_mode && $data_edit['jenis_kelamin'] == 'Perempuan') ? 'selected' : '' ?>>Perempuan</option></select></div>
                            <div class="md:col-span-2"><label class="text-sm font-medium">Alamat Lengkap</label><input type="text" name="alamat_lengkap" value="<?= $edit_mode ? htmlspecialchars($data_edit['alamat_lengkap']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                        </div>
                    </div>

                    <!-- DATA AKADEMIK -->
                    <div class="mb-6 border border-gray-200 rounded-lg p-5 bg-gray-50/50">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">II. Data Akademik & Status</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div><label class="text-sm font-medium">Tanggal Masuk</label><input type="date" name="tanggal_masuk" value="<?= $edit_mode ? $data_edit['tanggal_masuk'] : date('Y-m-d') ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Asal Sekolah</label><input type="text" name="asal_sekolah" value="<?= $edit_mode ? htmlspecialchars($data_edit['asal_sekolah']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Status Santri</label><select name="status_santri" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"><?php $opts = ['Aktif', 'Lulus', 'Pindah', 'Dikeluarkan', 'Mengundurkan Diri']; foreach($opts as $o) { $sel = ($edit_mode && $data_edit['status_santri'] == $o) ? 'selected' : ''; echo "<option value='$o' $sel>$o</option>"; } ?></select></div>
                            <div><label class="text-sm font-medium">Kelas Sekarang</label><input type="text" name="kelas_sekarang" value="<?= $edit_mode ? htmlspecialchars($data_edit['kelas_sekarang']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Kamar Asrama</label><input type="text" name="kamar_asrama" value="<?= $edit_mode ? htmlspecialchars($data_edit['kamar_asrama']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">URL Foto Santri</label><input type="text" name="foto_santri" value="<?= $edit_mode ? htmlspecialchars($data_edit['foto_santri']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm" placeholder="https://..."></div>
                        </div>
                    </div>

                    <!-- DATA ORTU -->
                    <div class="mb-6 border border-gray-200 rounded-lg p-5 bg-gray-50/50">
                        <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">III. Data Orang Tua / Wali</h3>
                        <div class="mb-4 bg-blue-50 border border-blue-200 p-3 rounded-lg">
                            <label class="text-sm font-medium text-blue-800">Hubungkan ke Akun Orang Tua</label>
                            <select name="id_orangtua" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm focus:ring-cyan-500">
                                <option value="">-- Tidak Dihubungkan --</option>
                                <?php foreach($akun_orangtua as $ortu): ?>
                                    <option value="<?= $ortu['id'] ?>" <?= ($edit_mode && isset($data_edit['id_orangtua']) && $data_edit['id_orangtua'] == $ortu['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ortu['nama_orangtua']) ?> (<?= htmlspecialchars($ortu['username']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih akun orang tua yang sudah dibuat di menu "Akun Orang Tua". Ini akan menghubungkan santri ini ke Ruang Orang Tua.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t">
                            <div><label class="text-sm font-medium">Nama Ayah</label><input type="text" name="nama_ayah" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_ayah']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Pekerjaan Ayah</label><input type="text" name="pekerjaan_ayah" value="<?= $edit_mode ? htmlspecialchars($data_edit['pekerjaan_ayah']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">No. WhatsApp Ayah</label><input type="text" name="no_whatsapp_ayah" value="<?= $edit_mode ? htmlspecialchars($data_edit['no_whatsapp_ayah']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Ayah</label><input type="text" name="alamat_ayah" value="<?= $edit_mode ? htmlspecialchars($data_edit['alamat_ayah']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Nama Ibu</label><input type="text" name="nama_ibu" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_ibu']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Pekerjaan Ibu</label><input type="text" name="pekerjaan_ibu" value="<?= $edit_mode ? htmlspecialchars($data_edit['pekerjaan_ibu']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">No. WhatsApp Ibu</label><input type="text" name="no_whatsapp_ibu" value="<?= $edit_mode ? htmlspecialchars($data_edit['no_whatsapp_ibu']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Ibu</label><input type="text" name="alamat_ibu" value="<?= $edit_mode ? htmlspecialchars($data_edit['alamat_ibu']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Nama Wali (Jika ada)</label><input type="text" name="nama_wali" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama_wali']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">Pekerjaan Wali</label><input type="text" name="pekerjaan_wali" value="<?= $edit_mode ? htmlspecialchars($data_edit['pekerjaan_wali']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div><label class="text-sm font-medium">No. WhatsApp Wali</label><input type="text" name="no_whatsapp_wali" value="<?= $edit_mode ? htmlspecialchars($data_edit['no_whatsapp_wali']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                            <div class="md:col-span-3"><label class="text-sm font-medium">Alamat Wali</label><input type="text" name="alamat_wali" value="<?= $edit_mode ? htmlspecialchars($data_edit['alamat_wali']) : '' ?>" class="w-full mt-1 px-3 py-2 border rounded-lg text-sm"></div>
                        </div>
                    </div>

                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="admin-buku-induk.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Data' : 'Simpan Data' ?></button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Santri Aktif</h2></div>
                <div class="p-4">
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama santri, NIS, atau kelas..." class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500 mb-4">
                </div>
                <div class="overflow-x-auto">
                    <table id="santriTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Santri</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">NIS / NISN</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Kelas & Kamar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $res = $conn->query("SELECT b.*, o.nama_orangtua FROM buku_induk_santri b LEFT JOIN akun_orangtua o ON b.id_orangtua = o.id ORDER BY b.nama_lengkap ASC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) { 
                                    $badge_color = 'bg-green-100 text-green-800';
                                    if ($row['status_santri'] !== 'Aktif') $badge_color = 'bg-gray-200 text-gray-700';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <img src="<?= !empty($row['foto_santri']) ? htmlspecialchars($row['foto_santri']) : 'https://via.placeholder.com/100' ?>" class="w-10 h-10 rounded-full object-cover mr-3 shadow-sm flex-shrink-0" alt="Foto">
                                            <div>
                                                <span class="font-bold text-gray-900"><?= htmlspecialchars($row['nama_lengkap']) ?></span>
                                                <?php if(!empty($row['nama_orangtua'])): ?><div class="text-xs text-gray-500 mt-1"><i class="fas fa-user-shield text-cyan-600 mr-1"></i> Wali: <?= htmlspecialchars($row['nama_orangtua']) ?></div><?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 font-mono">
                                        <div>NIS: <?= htmlspecialchars($row['nis']) ?></div>
                                        <div>NISN: <?= htmlspecialchars($row['nisn']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        <div>Kelas: <span class="font-semibold"><?= htmlspecialchars($row['kelas_sekarang']) ?></span></div>
                                        <div>Kamar: <span class="font-semibold"><?= htmlspecialchars($row['kamar_asrama']) ?></span></div>
                                    </td>
                                    <td class="px-4 py-3"><span class="px-2 py-1 text-xs font-bold rounded-full <?= $badge_color ?>"><?= htmlspecialchars($row['status_santri']) ?></span></td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="?edit_id=<?= $row['id'] ?>" class="text-blue-500 hover:text-blue-700 mr-3" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="?hapus_id=<?= $row['id'] ?>" onclick="return confirm('Hapus data santri ini?')" class="text-red-500 hover:text-red-700" title="Hapus"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                            <?php } } else { echo "<tr><td colspan='5' class='text-center py-6 text-gray-500 italic'>Belum ada data santri di buku induk.</td></tr>"; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
        function filterTable() {
            const filter = document.getElementById("searchInput").value.toLowerCase();
            const table = document.getElementById("santriTable");
            const tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let display = "none";
                const tds = tr[i].getElementsByTagName("td");
                for (let j = 0; j < tds.length; j++) {
                    if (tds[j]) {
                        if (tds[j].innerText.toLowerCase().indexOf(filter) > -1) {
                            display = "";
                            break;
                        }
                    }
                }
                tr[i].style.display = display;
            }
        }
    </script>
</body>
</html>