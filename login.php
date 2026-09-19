<?php
require_once 'auth-unified.php';

// Jika sudah login, langsung ke dashboard utama
if (isset($_SESSION['app_user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        $username_esc = $conn->real_escape_string($username);
        
        // Cari user di tabel terpadu app_users
        $res = $conn->query("SELECT * FROM app_users WHERE username = '$username_esc' AND status_aktif = 1 LIMIT 1");
        
        $login_success = false;
        $user_data = null;

        if ($res && $res->num_rows > 0) {
            $user_data = $res->fetch_assoc();
            // Cek password (plain text atau hash)
            if ($password === $user_data['password'] || password_verify($password, $user_data['password'])) {
                $login_success = true;
            }
        }

        // Fallback akun master viqi jika belum termigrasi
        if (!$login_success && $username === 'viqi' && $password === 'Bismillah99!') {
            $login_success = true;
            $user_data = [
                'id' => 9999,
                'username' => 'viqi',
                'nama_lengkap' => 'Master Web Admin',
                'roles' => 'super_admin,tutor,musyrif,walisantri',
                'user_type' => 'pegawai',
                'ref_id' => 9999,
                'status_aktif' => 1
            ];
        }

        if ($login_success && $user_data) {
            syncLegacySessions($user_data);
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Username atau Password salah. Silakan periksa kembali!';
        }
    } else {
        $error = 'Harap isi Username dan Password!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Masuk Member Area | SADIGS 4.0 - Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gradient-to-br from-[#0d8276]/10 via-slate-50 to-teal-50 min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    
    <!-- Background Radiant Aura -->
    <div class="absolute -top-32 -right-32 w-96 h-96 bg-teal-400/20 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-emerald-400/20 rounded-full blur-3xl pointer-events-none"></div>

    <div class="bg-white/95 backdrop-blur-md p-6 sm:p-10 rounded-3xl shadow-2xl w-full max-w-md border border-teal-100 relative z-10">
        
        <!-- BRAND LOGO & TITLE -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-[#0d8276] to-teal-500 text-white flex items-center justify-center text-2xl mx-auto mb-4 shadow-lg shadow-teal-700/20">
                <i class="fas fa-cubes-stacked"></i>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">SADIGS 4.0</h1>
            <p class="text-xs text-slate-500 mt-1">Sistem Administrasi Digital Sekolah & Pesantren</p>
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-extrabold bg-teal-50 text-[#0d8276] border border-teal-200 mt-3">
                <i class="fas fa-shield-alt"></i> Satu Pintu Masuk (Universal Member Area)
            </div>
        </div>

        <?php if($error): ?>
        <div class="bg-rose-50 text-rose-700 border border-rose-200 text-xs px-4 py-3 rounded-2xl mb-5 flex items-center gap-2.5 animate-shake shadow-xs">
            <i class="fas fa-exclamation-circle text-rose-500 text-base flex-shrink-0"></i>
            <span class="font-semibold"><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-black uppercase text-slate-600 mb-1.5 tracking-wider">Username / NISN / No. HP</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-user text-xs"></i>
                    </div>
                    <input type="text" name="username" required autofocus 
                           class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-2xl text-xs sm:text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#0d8276] focus:border-transparent font-medium text-slate-800 transition shadow-2xs" 
                           placeholder="Masukkan username atau NISN">
                </div>
            </div>

            <div>
                <label class="block text-xs font-black uppercase text-slate-600 mb-1.5 tracking-wider">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <i class="fas fa-lock text-xs"></i>
                    </div>
                    <input type="password" id="password" name="password" required 
                           class="w-full pl-10 pr-12 py-3 border border-slate-200 rounded-2xl text-xs sm:text-sm bg-slate-50 focus:bg-white focus:ring-2 focus:ring-[#0d8276] focus:border-transparent font-medium text-slate-800 transition shadow-2xs" 
                           placeholder="••••••••">
                    <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-[#0d8276] focus:outline-none">
                        <i class="fas fa-eye text-xs" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full bg-[#0d8276] hover:bg-[#0b6f65] text-white font-extrabold py-3.5 rounded-2xl text-xs sm:text-sm shadow-md shadow-teal-900/10 hover:shadow-lg transition-all flex items-center justify-center gap-2 mt-6">
                <span>Masuk Sekarang</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </button>
        </form>

        <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
            <a href="index.html" class="hover:text-[#0d8276] font-bold flex items-center gap-1 transition">
                <i class="fas fa-home"></i> Beranda Web
            </a>
            <span>Villa Quran Indonesia</span>
        </div>

    </div>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        if (togglePassword && password && eyeIcon) {
            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                eyeIcon.classList.toggle('fa-eye');
                eyeIcon.classList.toggle('fa-eye-slash');
            });
        }
    </script>
</body>
</html>