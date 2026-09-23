<?php
require_once 'auth-unified.php';
requireLogin();

$user = getCurrentUser();
$roles = getUserRoles();
$is_admin = isSuperAdmin();

$pesan_sukses = '';
$pesan_error = '';

$user_id = $user['id'] ?? 0;
$user_type = $user['type'] ?? 'admin';

// Ambil data user dari tabel masing-masing
$db_user = [];
if ($user_type === 'ustadz') {
    $res = $conn->query("SELECT * FROM akun_ustadz WHERE id = " . (int)$user_id);
    if ($res) $db_user = $res->fetch_assoc();
} elseif ($user_type === 'santri') {
    $res = $conn->query("SELECT * FROM santri WHERE id = " . (int)$user_id);
    if ($res) $db_user = $res->fetch_assoc();
} elseif ($user_type === 'orangtua') {
    $res = $conn->query("SELECT * FROM akun_orangtua WHERE id = " . (int)$user_id);
    if ($res) $db_user = $res->fetch_assoc();
} elseif ($user_type === 'yayasan2') {
    $res = $conn->query("SELECT * FROM akun_yayasan WHERE id = " . (int)$user_id);
    if ($res && $res->num_rows > 0) {
        $db_user = $res->fetch_assoc();
    } else {
        $db_user = ['nama' => $_SESSION['nama_lengkap'] ?? 'Pengurus Yayasan', 'username' => $_SESSION['username'] ?? 'yayasan', 'foto' => ''];
    }
} else {
    // Admin utama / Super admin
    $db_user = [
        'nama' => $_SESSION['nama_lengkap'] ?? ($user['nama_lengkap'] ?? 'Super Admin'),
        'username' => $_SESSION['username'] ?? ($user['username'] ?? 'admin'),
        'foto' => $user['foto_profil'] ?? '',
        'email' => 'admin@vqi.or.id',
        'whatsapp' => '-'
    ];
}

// Proses Form Update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tab_active = $_POST['tab_active'] ?? 'profil';

    if ($tab_active === 'password') {
        $password_lama = $_POST['password_lama'] ?? '';
        $password_baru = $_POST['password_baru'] ?? '';
        $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

        if (empty($password_baru) || empty($konfirmasi_password)) {
            $pesan_error = "Password baru dan konfirmasi password tidak boleh kosong!";
        } elseif ($password_baru !== $konfirmasi_password) {
            $pesan_error = "Password baru dan konfirmasi password tidak cocok!";
        } elseif (strlen($password_baru) < 6) {
            $pesan_error = "Password baru minimal 6 karakter!";
        } else {
            $pass_esc = $conn->real_escape_string($password_baru);
            if ($user_type === 'ustadz') {
                $conn->query("UPDATE akun_ustadz SET password = '$pass_esc' WHERE id = " . (int)$user_id);
                $pesan_sukses = "Password berhasil diperbarui!";
            } elseif ($user_type === 'santri') {
                $conn->query("UPDATE santri SET password = '$pass_esc' WHERE id = " . (int)$user_id);
                $pesan_sukses = "Password berhasil diperbarui!";
            } elseif ($user_type === 'orangtua') {
                $conn->query("UPDATE akun_orangtua SET password = '$pass_esc' WHERE id = " . (int)$user_id);
                $pesan_sukses = "Password berhasil diperbarui!";
            } elseif ($user_type === 'admin') {
                // Admin session
                $_SESSION['admin_password'] = $password_baru;
                $pesan_sukses = "Password admin berhasil diperbarui!";
            }
        }
    } else {
        // Update Biodata
        $nama = $conn->real_escape_string(trim($_POST['nama'] ?? ''));
        $whatsapp = $conn->real_escape_string(trim($_POST['whatsapp'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $alamat = $conn->real_escape_string(trim($_POST['alamat'] ?? ''));

        if (empty($nama)) {
            $pesan_error = "Nama Lengkap wajib diisi!";
        } else {
            // Upload Foto
            $foto_path = $db_user['foto'] ?? '';
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto']['tmp_name'];
                $file_name = $_FILES['foto']['name'];
                $file_size = $_FILES['foto']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (in_array($file_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    if (!file_exists('uploads/profil')) {
                        mkdir('uploads/profil', 0777, true);
                    }
                    $new_name = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
                    $dest = 'uploads/profil/' . $new_name;
                    if (move_uploaded_file($file_tmp, $dest)) {
                        $foto_path = $dest;
                        $_SESSION['foto_profil'] = $dest;
                    }
                }
            }

            if ($user_type === 'ustadz') {
                $conn->query("UPDATE akun_ustadz SET nama = '$nama', whatsapp = '$whatsapp', email = '$email', alamat = '$alamat', foto = '$foto_path' WHERE id = " . (int)$user_id);
                $pesan_sukses = "Profil berhasil diperbarui!";
            } elseif ($user_type === 'santri') {
                $conn->query("UPDATE santri SET nama_lengkap = '$nama', whatsapp = '$whatsapp', alamat = '$alamat', foto = '$foto_path' WHERE id = " . (int)$user_id);
                $pesan_sukses = "Profil berhasil diperbarui!";
            } elseif ($user_type === 'orangtua') {
                $conn->query("UPDATE akun_orangtua SET nama_ayah = '$nama', no_wa = '$whatsapp', alamat = '$alamat' WHERE id = " . (int)$user_id);
                $pesan_sukses = "Profil berhasil diperbarui!";
            } else {
                $_SESSION['nama_lengkap'] = $nama;
                $pesan_sukses = "Profil berhasil diperbarui!";
            }

            // Refresh data
            $user = getCurrentUser();
        }
    }
}

$tab = $_GET['tab'] ?? 'profil';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Profil & Pengaturan Akun | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-[#dcf3ee] min-h-screen text-slate-800 flex flex-col md:flex-row antialiased selection:bg-[#0b8478] selection:text-white">

    <!-- DESKTOP SIDEBAR (PC) -->
    <aside class="hidden md:flex flex-col w-64 lg:w-72 bg-[#0b8478] text-white min-h-screen sticky top-0 h-screen shadow-2xl z-30 flex-shrink-0 border-r border-teal-700/50">
        
        <div class="p-6 border-b border-teal-700/60 flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-full bg-white flex items-center justify-center p-1.5 shadow-md flex-shrink-0">
                <img src="upload/logo-villa-quran.png" alt="Logo Villa Quran" class="w-9 h-9 object-contain">
            </div>
            <div>
                <h1 class="font-black text-2xl tracking-wide text-white leading-none">SADIGS</h1>
                <p class="text-[11px] text-teal-100 font-light italic tracking-tight mt-0.5">Sistem Administrasi Digital</p>
            </div>
        </div>

        <div class="px-5 py-4 border-b border-teal-700/40 bg-teal-900/30">
            <div class="flex items-center space-x-3">
                <div class="w-11 h-11 rounded-full bg-white text-[#0b8478] flex items-center justify-center font-black text-base shadow-sm border-2 border-white/80 overflow-hidden flex-shrink-0">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                    <?php else: ?>
                        <i class="fas fa-user text-[#0b8478]"></i>
                    <?php endif; ?>
                </div>
                <div class="overflow-hidden flex-1">
                    <h4 class="font-bold text-xs text-white truncate leading-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                    <p class="text-[10px] text-teal-200 truncate mt-0.5">@<?= htmlspecialchars($user['username']) ?></p>
                    <span class="inline-block px-2 py-0.5 bg-teal-800/80 rounded text-[9px] font-bold text-teal-100 border border-teal-600/50 mt-1 truncate max-w-full">
                        <?= htmlspecialchars($user['roles']) ?>
                    </span>
                </div>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto p-4 space-y-1 text-xs no-scrollbar">
            <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-house w-4 text-center"></i>
                <span>Beranda</span>
            </a>
            <a href="kalender-akademik.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-calendar-alt w-4 text-center"></i>
                <span>Kalender</span>
            </a>
            <a href="admin-jadwal-pelajaran.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-clock w-4 text-center"></i>
                <span>Jadwal</span>
            </a>
            <a href="pengumuman.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-teal-100 hover:bg-teal-800/60 hover:text-white font-bold transition">
                <i class="fas fa-bullhorn w-4 text-center"></i>
                <span>Info</span>
            </a>
        </nav>

        <div class="p-4 border-t border-teal-700/60">
            <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex items-center justify-center gap-2 w-full py-2.5 px-3 rounded-xl bg-rose-600/80 hover:bg-rose-600 text-white font-bold text-xs transition">
                <i class="fas fa-arrow-right-from-bracket"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN CANVAS -->
    <div class="flex-1 min-h-screen flex flex-col relative bg-[#dcf3ee]">
        
        <!-- TOP HERO BANNER -->
        <div class="bg-[#0b8478] text-white pt-6 pb-20 px-6 relative rounded-b-[28px] md:rounded-b-[36px] shadow-md flex-shrink-0">
            <div class="max-w-3xl mx-auto flex items-center justify-between">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-white/15 backdrop-blur-xs flex items-center justify-center text-white text-2xl border border-white/20 shadow-md">
                        <i class="fas fa-user-gear text-amber-300"></i>
                    </div>
                    <div>
                        <h1 class="font-black text-2xl text-white leading-tight">Profil & Pengaturan Akun</h1>
                        <p class="text-xs text-teal-100 mt-0.5">Kelola biodata, foto profil, dan keamanan password Anda</p>
                    </div>
                </div>

                <a href="dashboard.php" class="px-3.5 py-2 rounded-xl bg-white/15 hover:bg-white/25 text-white font-bold text-xs transition border border-white/20 flex items-center gap-1.5">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>
        </div>

        <!-- MAIN CONTENT CONTAINER -->
        <main class="flex-1 px-4 sm:px-8 pt-0 pb-24 md:pb-12 w-full max-w-3xl mx-auto -mt-12 z-20">
            
            <?php if (!empty($pesan_sukses)): ?>
            <div class="mb-4 bg-teal-50 border border-teal-200 text-[#0b8478] px-4 py-3 rounded-2xl shadow-sm flex items-center font-bold text-xs">
                <i class="fas fa-check-circle text-base mr-2"></i> <?= htmlspecialchars($pesan_sukses) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($pesan_error)): ?>
            <div class="mb-4 bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-2xl shadow-sm flex items-center font-bold text-xs">
                <i class="fas fa-circle-exclamation text-base mr-2"></i> <?= htmlspecialchars($pesan_error) ?>
            </div>
            <?php endif; ?>

            <!-- KARTU UTAMA DENGAN TAB -->
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xl shadow-teal-950/5 border border-teal-50">
                
                <!-- HEADER PROFIL RINGKAS -->
                <div class="flex items-center space-x-4 pb-6 border-b border-slate-100">
                    <div class="w-18 h-18 rounded-2xl bg-[#0b8478] text-white flex items-center justify-center text-3xl font-black shadow-md border-2 border-teal-100 overflow-hidden flex-shrink-0">
                        <?php if (!empty($user['foto_profil'])): ?>
                            <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Avatar" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-user text-white"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h2 class="font-black text-lg text-slate-900 leading-tight"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                        <p class="text-xs text-slate-500 mt-0.5">@<?= htmlspecialchars($user['username']) ?></p>
                        <span class="inline-block mt-1.5 px-2.5 py-0.5 bg-teal-50 text-[#0b8478] rounded-full text-[10px] font-extrabold border border-teal-200">
                            Role: <?= htmlspecialchars($user['roles']) ?>
                        </span>
                    </div>
                </div>

                <!-- TAB SWITCHER -->
                <div class="mt-6 flex border-b border-slate-100 gap-4">
                    <a href="akunku.php?tab=profil" class="pb-3 text-xs font-bold transition flex items-center gap-1.5 border-b-2 <?= $tab === 'profil' ? 'text-[#0b8478] border-[#0b8478]' : 'text-slate-400 border-transparent hover:text-slate-600' ?>">
                        <i class="fas fa-user-pen"></i> Edit Biodata & Foto
                    </a>
                    <a href="akunku.php?tab=password" class="pb-3 text-xs font-bold transition flex items-center gap-1.5 border-b-2 <?= $tab === 'password' ? 'text-[#0b8478] border-[#0b8478]' : 'text-slate-400 border-transparent hover:text-slate-600' ?>">
                        <i class="fas fa-key"></i> Ganti Password
                    </a>
                </div>

                <!-- FORM BODY -->
                <form action="akunku.php?tab=<?= htmlspecialchars($tab) ?>" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4 text-xs">
                    <input type="hidden" name="tab_active" value="<?= htmlspecialchars($tab) ?>">

                    <?php if ($tab === 'password'): ?>
                    <!-- TAB GANTI PASSWORD -->
                    <div class="space-y-4 max-w-md">
                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Password Baru:</label>
                            <input type="password" name="password_baru" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none font-semibold">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Konfirmasi Password Baru:</label>
                            <input type="password" name="konfirmasi_password" required placeholder="Ulangi password baru" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none font-semibold">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#0b8478] hover:bg-[#086a60] text-white font-bold transition shadow-md flex items-center gap-2 cursor-pointer">
                                <i class="fas fa-save"></i> Simpan Password Baru
                            </button>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- TAB EDIT BIODATA & FOTO -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="font-bold text-slate-700 block mb-1">Nama Lengkap:</label>
                            <input type="text" name="nama" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none font-semibold">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Nomor WhatsApp:</label>
                            <input type="text" name="whatsapp" value="<?= htmlspecialchars($db_user['whatsapp'] ?? ($db_user['no_wa'] ?? '')) ?>" placeholder="08xxxxxxxxxx" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none font-semibold">
                        </div>

                        <div>
                            <label class="font-bold text-slate-700 block mb-1">Email:</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($db_user['email'] ?? '') ?>" placeholder="email@contoh.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none font-semibold">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="font-bold text-slate-700 block mb-1">Alamat Domisili:</label>
                            <textarea name="alamat" rows="2" placeholder="Alamat lengkap..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs focus:ring-2 focus:ring-[#0b8478] focus:outline-none font-semibold"><?= htmlspecialchars($db_user['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="font-bold text-slate-700 block mb-1">Ganti Foto Profil:</label>
                            <input type="file" name="foto" accept="image/*" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-xs file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-teal-50 file:text-[#0b8478] hover:file:bg-teal-100">
                            <p class="text-[10px] text-slate-400 mt-1">Format: JPG, PNG, atau WEBP. Maksimal 2MB.</p>
                        </div>

                        <div class="sm:col-span-2 pt-3 flex justify-end">
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#0b8478] hover:bg-[#086a60] text-white font-bold transition shadow-md flex items-center gap-2 cursor-pointer">
                                <i class="fas fa-save"></i> Simpan Perubahan Biodata
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                </form>

            </div>

        </main>

        <!-- BOTTOM NAVIGATION BAR (HP) -->
        <nav class="md:hidden fixed bottom-0 inset-x-0 left-0 right-0 w-full m-0 h-16 bg-[#0b8478] border-t border-teal-700/60 shadow-[0_-4px_25px_rgba(0,0,0,0.25)] z-40 px-2 flex items-center justify-center" style="left:0; right:0; width:100vw; max-width:100%;">
            <div class="w-full max-w-md mx-auto flex items-center justify-around">
                <a href="dashboard.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                    <i class="fas fa-house text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                    <span class="text-white">Beranda</span>
                </a>
                <a href="kalender-akademik.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                    <i class="fas fa-calendar-alt text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                    <span class="text-white">Kalender</span>
                </a>
                <a href="admin-jadwal-pelajaran.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                    <i class="fas fa-clock text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                    <span class="text-white">Jadwal</span>
                </a>
                <a href="pengumuman.php" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-white font-bold text-[10px] transition">
                    <i class="fas fa-bullhorn text-lg mb-0.5 text-teal-100 group-hover:text-white"></i>
                    <span class="text-white">Info</span>
                </a>
                <a href="dashboard.php?action=logout" onclick="return confirm('Yakin ingin keluar?');" class="flex flex-col items-center justify-center flex-1 py-1 text-teal-100 hover:text-rose-200 font-bold text-[10px] transition">
                    <i class="fas fa-arrow-right-from-bracket text-lg mb-0.5 text-teal-100 hover:text-rose-200"></i>
                    <span class="text-white">Keluar</span>
                </a>
            </div>
        </nav>

    </div>

</body>
</html>
