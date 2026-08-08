<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'akunku';
$pesan_sukses = '';
$pesan_error = '';

$ustadz_id = $_SESSION['ustadz_id'];

// --- DATABASE SELF-HEALING (Tambah kolom profil jika belum ada) ---
$cols = [];
$res_cols = $conn->query("SHOW COLUMNS FROM akun_ustadz");
if ($res_cols) {
    while ($r = $res_cols->fetch_assoc()) {
        $cols[] = $r['Field'];
    }
}
if (!in_array('email', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN email VARCHAR(100) NULL");
if (!in_array('jenis_kelamin', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN jenis_kelamin VARCHAR(20) NULL");
if (!in_array('alamat', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN alamat TEXT NULL");
if (!in_array('foto', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN foto VARCHAR(255) NULL");
if (!in_array('tempat_lahir', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN tempat_lahir VARCHAR(100) NULL");
if (!in_array('tanggal_lahir', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN tanggal_lahir DATE NULL");
if (!in_array('nik', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN nik VARCHAR(30) NULL");
if (!in_array('pendidikan_terakhir', $cols)) $conn->query("ALTER TABLE akun_ustadz ADD COLUMN pendidikan_terakhir VARCHAR(50) NULL");

// Ambil data ustadz yang sedang login
$res_ustadz = $conn->query("SELECT * FROM akun_ustadz WHERE id = $ustadz_id");
$data_ustadz = $res_ustadz ? $res_ustadz->fetch_assoc() : [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Data Diri
    $nama = $conn->real_escape_string(trim($_POST['nama']));
    $whatsapp = $conn->real_escape_string(trim($_POST['whatsapp']));
    $email = $conn->real_escape_string(trim($_POST['email']));
    $jenis_kelamin = $conn->real_escape_string($_POST['jenis_kelamin']);
    $tempat_lahir = $conn->real_escape_string(trim($_POST['tempat_lahir']));
    $tanggal_lahir = $conn->real_escape_string($_POST['tanggal_lahir']);
    $nik = $conn->real_escape_string(trim($_POST['nik']));
    $pendidikan_terakhir = $conn->real_escape_string($_POST['pendidikan_terakhir']);
    $alamat = $conn->real_escape_string(trim($_POST['alamat']));
    
    // 2. Akun
    $username = $conn->real_escape_string(trim($_POST['username']));
    
    // Validasi input wajib
    if (empty($nama) || empty($username)) {
        $pesan_error = "Nama Lengkap dan Username wajib diisi!";
    } else {
        // Cek keunikan username
        $chk = $conn->query("SELECT id FROM akun_ustadz WHERE username = '$username' AND id != $ustadz_id");
        if ($chk && $chk->num_rows > 0) {
            $pesan_error = "Username sudah digunakan oleh pegawai lain!";
        } else {
            // 3. Proses Upload Foto (jika ada)
            $foto_path = $data_ustadz['foto']; // Default foto lama
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto']['tmp_name'];
                $file_name = $_FILES['foto']['name'];
                $file_size = $_FILES['foto']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_exts = ['jpg', 'jpeg', 'png'];
                if (!in_array($file_ext, $allowed_exts)) {
                    $pesan_error = "Format foto harus JPG, JPEG, atau PNG!";
                } elseif ($file_size > 2 * 1024 * 1024) { // Max 2MB
                    $pesan_error = "Ukuran foto maksimal adalah 2MB!";
                } else {
                    // Buat folder uploads jika belum ada
                    if (!file_exists('uploads/foto_pegawai')) {
                        mkdir('uploads/foto_pegawai', 0777, true);
                    }
                    // Generate nama file unik
                    $new_file_name = 'pegawai_' . $ustadz_id . '_' . time() . '.' . $file_ext;
                    $dest_path = 'uploads/foto_pegawai/' . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        // Hapus foto lama jika ada
                        if (!empty($foto_path) && file_exists($foto_path)) {
                            unlink($foto_path);
                        }
                        $foto_path = $dest_path;
                    } else {
                        $pesan_error = "Gagal mengunggah foto profil!";
                    }
                }
            }
            
            // 4. Update Password (jika diisi)
            $update_password_sql = "";
            if (!empty($_POST['password_lama']) || !empty($_POST['password_baru']) || !empty($_POST['konfirmasi_password'])) {
                $password_lama = $_POST['password_lama'] ?? '';
                $password_baru = $_POST['password_baru'] ?? '';
                $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';
                
                if ($password_lama !== $data_ustadz['password']) {
                    $pesan_error = "Password lama tidak sesuai!";
                } elseif ($password_baru !== $konfirmasi_password) {
                    $pesan_error = "Password baru dan konfirmasi password tidak cocok!";
                } elseif (strlen($password_baru) < 6) {
                    $pesan_error = "Password baru minimal 6 karakter!";
                } else {
                    $update_password_sql = ", password = '$password_baru'";
                }
            }
            
            // Jika tidak ada error sejauh ini, simpan perubahan
            if (empty($pesan_error)) {
                $tgl_lahir_val = empty($tanggal_lahir) ? "NULL" : "'$tanggal_lahir'";
                
                $sql = "UPDATE akun_ustadz SET 
                            nama = '$nama',
                            whatsapp = '$whatsapp',
                            email = '$email',
                            jenis_kelamin = '$jenis_kelamin',
                            tempat_lahir = '$tempat_lahir',
                            tanggal_lahir = $tgl_lahir_val,
                            nik = '$nik',
                            pendidikan_terakhir = '$pendidikan_terakhir',
                            alamat = '$alamat',
                            username = '$username',
                            foto = '$foto_path'
                            $update_password_sql
                        WHERE id = $ustadz_id";
                
                if ($conn->query($sql) === TRUE) {
                    $pesan_sukses = "Data akun dan profil berhasil diperbarui!";
                    // Reload data terbaru
                    $res_ustadz = $conn->query("SELECT * FROM akun_ustadz WHERE id = $ustadz_id");
                    $data_ustadz = $res_ustadz ? $res_ustadz->fetch_assoc() : [];
                } else {
                    $pesan_error = "Gagal memperbarui database: " . $conn->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akunku | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Sistem Administrasi Digital Sekolah (SADIGS 4.0)</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-user-cog text-cyan-600 mr-2"></i>Pengaturan Akunku</h1>
                <p class="text-gray-500 mt-1 font-outfit">Lengkapi data diri Anda sebagai database kepegawaian Yayasan serta kelola keamanan akun Anda di sini.</p>
            </div>
            
            <?php if($pesan_sukses): ?>
                <div class="bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-check-circle mr-2"></i> <?= $pesan_sukses ?>
                </div>
            <?php endif; ?>
            <?php if($pesan_error): ?>
                <div class="bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= $pesan_error ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                    
                    <!-- KARTU UTAMA FOTO PROFIL & USERNAME -->
                    <div class="xl:col-span-1 bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 flex flex-col items-center justify-center text-center">
                        <div class="relative w-32 h-32 rounded-full overflow-hidden border-2 border-cyan-500 shadow-md mb-4 bg-gray-150 flex items-center justify-center">
                            <?php if (!empty($data_ustadz['foto']) && file_exists($data_ustadz['foto'])): ?>
                                <img src="<?= $data_ustadz['foto'] ?>" alt="Foto Profil" class="w-full h-full object-cover">
                            <?php else: ?>
                                <i class="fas fa-user text-5xl text-gray-300"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h2 class="font-bold text-slate-800 text-base mb-1"><?= htmlspecialchars($data_ustadz['nama'] ?? '') ?></h2>
                        <span class="text-[9px] bg-slate-100 text-slate-500 font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider mb-4 border border-slate-200">
                            <?= htmlspecialchars(str_replace('_', ' ', $data_ustadz['role'] ?? '')) ?>
                        </span>

                        <div class="w-full pt-4 border-t border-slate-100 text-left">
                            <label class="block text-xs font-bold text-slate-650 mb-1">Unggah Foto Baru</label>
                            <input type="file" name="foto" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 cursor-pointer">
                            <span class="text-[9px] text-gray-400 block mt-1">Format: JPG, JPEG, PNG. Maksimal: 2MB.</span>
                        </div>
                    </div>

                    <!-- FORM DATA DIRI & KEAMANAN -->
                    <div class="xl:col-span-2 space-y-6">
                        
                        <!-- PANEL 1: DATA DIRI -->
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                            <h3 class="font-bold text-slate-800 text-sm mb-4 border-b pb-2 uppercase tracking-wider flex items-center gap-1.5"><i class="fas fa-id-card text-cyan-600"></i> Database Kepegawaian</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap *</label>
                                    <input type="text" name="nama" value="<?= htmlspecialchars($data_ustadz['nama'] ?? '') ?>" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Nama Lengkap">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">NIK (Nomor Induk Kependudukan)</label>
                                    <input type="text" name="nik" value="<?= htmlspecialchars($data_ustadz['nik'] ?? '') ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="NIK 16 Digit">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">No. WhatsApp *</label>
                                    <input type="text" name="whatsapp" value="<?= htmlspecialchars($data_ustadz['whatsapp'] ?? '') ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Contoh: 08123456789">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Email</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($data_ustadz['email'] ?? '') ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="alamat@domain.com">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                        <option value="" <?= empty($data_ustadz['jenis_kelamin']) ? 'selected' : '' ?>>-- Pilih --</option>
                                        <option value="Laki-laki" <?= ($data_ustadz['jenis_kelamin'] === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="Perempuan" <?= ($data_ustadz['jenis_kelamin'] === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Pendidikan Terakhir</label>
                                    <select name="pendidikan_terakhir" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                        <option value="" <?= empty($data_ustadz['pendidikan_terakhir']) ? 'selected' : '' ?>>-- Pilih --</option>
                                        <option value="SMA/MA/Sederajat" <?= ($data_ustadz['pendidikan_terakhir'] === 'SMA/MA/Sederajat') ? 'selected' : '' ?>>SMA/MA/Sederajat</option>
                                        <option value="D3" <?= ($data_ustadz['pendidikan_terakhir'] === 'D3') ? 'selected' : '' ?>>D3</option>
                                        <option value="S1/D4" <?= ($data_ustadz['pendidikan_terakhir'] === 'S1/D4') ? 'selected' : '' ?>>S1/D4</option>
                                        <option value="S2" <?= ($data_ustadz['pendidikan_terakhir'] === 'S2') ? 'selected' : '' ?>>S2</option>
                                        <option value="S3" <?= ($data_ustadz['pendidikan_terakhir'] === 'S3') ? 'selected' : '' ?>>S3</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tempat Lahir</label>
                                    <input type="text" name="tempat_lahir" value="<?= htmlspecialchars($data_ustadz['tempat_lahir'] ?? '') ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Kota / Kabupaten">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Tanggal Lahir</label>
                                    <input type="date" name="tanggal_lahir" value="<?= htmlspecialchars($data_ustadz['tanggal_lahir'] ?? '') ?>" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Alamat Lengkap</label>
                                    <textarea name="alamat" rows="2" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Alamat tinggal saat ini"><?= htmlspecialchars($data_ustadz['alamat'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- PANEL 2: KREDENSIAL AKUN & KEAMANAN -->
                        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-6 text-left">
                            <h3 class="font-bold text-slate-800 text-sm mb-4 border-b pb-2 uppercase tracking-wider flex items-center gap-1.5"><i class="fas fa-shield-alt text-cyan-600"></i> Kredensial & Keamanan Akun</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Username *</label>
                                    <input type="text" name="username" value="<?= htmlspecialchars($data_ustadz['username'] ?? '') ?>" required class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Username login">
                                </div>
                                
                                <div class="md:col-span-2 border-t pt-4 mt-2">
                                    <p class="text-xs text-slate-450 font-bold mb-3"><i class="fas fa-lock mr-1"></i> Ganti Password (Kosongkan jika tidak ingin mengubah password)</p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Password Lama</label>
                                    <input type="password" name="password_lama" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Password saat ini">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Password Baru</label>
                                    <input type="password" name="password_baru" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Minimal 6 karakter">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-700 mb-1">Konfirmasi Password Baru</label>
                                    <input type="password" name="konfirmasi_password" class="w-full px-3 py-2 border rounded-xl text-xs focus:ring-2 focus:ring-cyan-500" placeholder="Ulangi password baru">
                                </div>
                            </div>
                        </div>

                        <!-- PANEL 3: BUTTON SAVE -->
                        <div class="flex justify-end">
                            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-8 rounded-xl shadow-md transition-all flex items-center gap-1.5 text-xs">
                                <i class="fas fa-save text-sm"></i> Simpan Seluruh Perubahan
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
            document.getElementById('sidebar-hr').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
        });
    </script>
</body>
</html>
