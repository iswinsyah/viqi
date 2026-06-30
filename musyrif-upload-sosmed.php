<?php
session_start();
require_once 'koneksi.php';

// Self-healing: Table sosmed_campaign
$conn->query("CREATE TABLE IF NOT EXISTS sosmed_campaign (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL,
    santri_id INT NOT NULL,
    pilar_konten VARCHAR(100) NOT NULL,
    tema_foto VARCHAR(255) NOT NULL,
    foto_mentah VARCHAR(255) NULL,
    foto_jadi VARCHAR(255) NULL,
    quote_text TEXT NULL,
    status_proses ENUM('menunggu_foto', 'siap_desain', 'selesai') DEFAULT 'menunggu_foto',
    status_kirim_ortu ENUM('menunggu', 'terkirim') DEFAULT 'menunggu',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (santri_id) REFERENCES buku_induk_santri(id) ON DELETE CASCADE
)");

// --- AJAX PWA LOGIN HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pwa_login') {
    header('Content-Type: application/json');
    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Master Key
    if ($username === 'winsyah' && $password === 'Khilafet@1924') {
        $_SESSION['ustadz_logged_in'] = true;
        $_SESSION['ustadz_id'] = 9999;
        $_SESSION['ustadz_nama'] = 'Super Admin (Bos)';
        $_SESSION['ustadz_role'] = 'super_admin';
        echo json_encode(['status' => 'success']);
        exit;
    }

    $res = $conn->query("SELECT id, nama, password, role FROM akun_ustadz WHERE username = '$username'");
    if ($res && $res->num_rows > 0) {
        $user = $res->fetch_assoc();
        if ($password === $user['password']) {
            $_SESSION['ustadz_logged_in'] = true;
            $_SESSION['ustadz_id'] = $user['id'];
            $_SESSION['ustadz_nama'] = $user['nama'];
            $_SESSION['ustadz_role'] = $user['role'];
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Username tidak terdaftar!']);
    }
    exit;
}

// --- LOGOUT HANDLER ---
if (isset($_GET['logout'])) {
    unset($_SESSION['ustadz_logged_in']);
    unset($_SESSION['ustadz_id']);
    unset($_SESSION['ustadz_nama']);
    unset($_SESSION['ustadz_role']);
    header("Location: musyrif-upload-sosmed.php");
    exit;
}

// Check authorization
$is_logged_in = isset($_SESSION['ustadz_logged_in']) && $_SESSION['ustadz_logged_in'] === true;

// --- UPLOAD HANDLER ---
$upload_success = false;
$upload_error = '';
if ($is_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_mentah'])) {
    $campaign_id = (int)($_POST['campaign_id'] ?? 0);
    
    if ($campaign_id > 0 && $_FILES['foto_mentah']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['foto_mentah']['tmp_name'];
        $file_name = $_FILES['foto_mentah']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        if (in_array($file_ext, $allowed_exts)) {
            // Buat folder uploads/sosmed jika belum ada
            $upload_dir = 'uploads/sosmed/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $new_filename = 'mentah_' . date('Ymd') . '_' . uniqid() . '.' . $file_ext;
            $dest_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($file_tmp, $dest_path)) {
                // Update database status
                $sql = "UPDATE sosmed_campaign SET foto_mentah = '$dest_path', status_proses = 'siap_desain' WHERE id = $campaign_id";
                if ($conn->query($sql)) {
                    $upload_success = true;
                } else {
                    $upload_error = 'Gagal memperbarui status di database: ' . $conn->error;
                }
            } else {
                $upload_error = 'Gagal menyimpan file ke server.';
            }
        } else {
            $upload_error = 'Format file tidak diperbolehkan. Hanya JPG, JPEG, PNG, WEBP.';
        }
    } else {
        $upload_error = 'Silakan pilih foto terlebih dahulu.';
    }
}

// --- FETCH TODAY'S TASK ---
$today = date('Y-m-d');
$campaign_today = null;
$santri_name = '';

if ($is_logged_in) {
    $res_c = $conn->query("SELECT c.*, s.nama_lengkap 
                           FROM sosmed_campaign c 
                           JOIN buku_induk_santri s ON c.santri_id = s.id 
                           WHERE c.tanggal = '$today' LIMIT 1");
    if ($res_c && $res_c->num_rows > 0) {
        $campaign_today = $res_c->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jepret VQ | Dokumentasi Harian Musyrif</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- PWA Manifest link -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#312e81">
    <style>
        body { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="bg-indigo-950/20 min-h-screen text-slate-800 flex flex-col justify-between">

    <!-- TOP BAR -->
    <header class="bg-indigo-900 text-white h-14 flex items-center justify-between px-5 shadow-md sticky top-0 z-20">
        <h1 class="font-extrabold text-sm tracking-wider flex items-center text-amber-400">
            <i class="fas fa-camera mr-2"></i> JEPRET VQ
        </h1>
        <?php if ($is_logged_in): ?>
            <a href="?logout=1" class="text-xs font-semibold px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg transition">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        <?php endif; ?>
    </header>

    <!-- CONTENT WRAPPER -->
    <main class="flex-grow p-4 flex flex-col items-center justify-center max-w-md mx-auto w-full">
        
        <?php if (!$is_logged_in): ?>
            <!-- LOGIN PANEL -->
            <div class="w-full bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xl space-y-4">
                <div class="text-center space-y-1">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-2 text-xl shadow">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h3 class="font-extrabold text-indigo-900 text-base">Portal Dokumentasi Musyrif</h3>
                    <p class="text-xs text-slate-400">Silakan login menggunakan akun Ustadz / Musyrif Anda</p>
                </div>
                
                <div id="login-error" class="hidden text-xs font-semibold p-3 bg-rose-100 text-rose-800 rounded-lg text-center"></div>

                <form id="loginForm" class="space-y-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Username</label>
                        <input type="text" name="username" placeholder="Masukkan username..." required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Password</label>
                        <input type="password" name="password" placeholder="Masukkan password..." required class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-3 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <button type="button" onclick="handlePwaLogin()" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 text-xs rounded-xl shadow-md transition flex items-center justify-center space-x-2">
                        <span>Masuk Aplikasi</span> <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
            
        <?php else: ?>
            <!-- TASKS AREA -->
            
            <?php if ($upload_success): ?>
                <!-- UPLOAD SUCCESS SCREEN -->
                <div class="w-full bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xl text-center space-y-4">
                    <div class="w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl shadow-md animate-bounce">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="font-extrabold text-slate-800 text-base">Alhamdulillah! Berhasil</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Foto harian ananda berhasil diunggah. Tim marketing akan memproses desain poster dan membagikannya ke orang tua.</p>
                    <a href="musyrif-upload-sosmed.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-6 py-2.5 rounded-xl shadow transition">
                        Kembali Ke Beranda
                    </a>
                </div>
                
            <?php elseif ($campaign_today): ?>
                
                <?php if ($campaign_today['status_proses'] !== 'menunggu_foto'): ?>
                    <!-- PHOTO ALREADY UPLOADED TODAY -->
                    <div class="w-full bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xl text-center space-y-4">
                        <div class="w-16 h-16 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto text-3xl shadow-md">
                            <i class="fas fa-images"></i>
                        </div>
                        <h3 class="font-extrabold text-slate-800 text-base">Tugas Selesai!</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">Anda sudah mengambil dan mengunggah foto tugas hari ini untuk ananda <b><?= htmlspecialchars($campaign_today['nama_lengkap']) ?></b>.</p>
                        <p class="text-[10px] text-indigo-500 font-bold bg-indigo-50 py-1.5 px-3 rounded-full inline-block">Status: Siap Desain AI</p>
                    </div>
                <?php else: ?>
                    <!-- UPLOAD FORM -->
                    <div class="w-full bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xl space-y-4">
                        <div class="border-b pb-3 text-center">
                            <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded-full uppercase tracking-wider">Tugas Foto Hari Ini</span>
                            <h3 class="font-extrabold text-indigo-900 text-base mt-1.5">Ananda <?= htmlspecialchars($campaign_today['nama_lengkap']) ?></h3>
                            <p class="text-xs text-slate-500 font-semibold mt-1">Pilar: <span class="text-indigo-600"><?= htmlspecialchars($campaign_today['pilar_konten']) ?></span></p>
                        </div>
                        
                        <?php if (!empty($upload_error)): ?>
                            <div class="text-xs font-semibold p-3 bg-rose-100 text-rose-800 rounded-lg text-center"><?= $upload_error ?></div>
                        <?php endif; ?>

                        <!-- Target Description / Hint -->
                        <div class="bg-indigo-50/50 p-3.5 rounded-xl border border-indigo-100/50 text-[11px] leading-relaxed text-slate-600">
                            <i class="fas fa-info-circle text-indigo-500 mr-1.5"></i>
                            Tolong ambil foto ananda: <b>"<?= htmlspecialchars($campaign_today['tema_foto']) ?>"</b>
                        </div>

                        <!-- Image Form -->
                        <form id="uploadForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="campaign_id" value="<?= $campaign_today['id'] ?>">
                            
                            <!-- Hidden File Input targeting mobile camera -->
                            <input type="file" name="foto_mentah" id="foto_mentah" accept="image/*" capture="camera" class="hidden" onchange="previewImage(this)">
                            
                            <!-- Visual Selector -->
                            <div onclick="triggerFileSelect()" id="uploadPlaceholder" class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center bg-slate-50 hover:bg-slate-100 transition cursor-pointer flex flex-col items-center justify-center gap-2">
                                <i class="fas fa-camera text-4xl text-slate-400"></i>
                                <span class="text-xs font-bold text-slate-500">Klik Untuk Memotret / Pilih Foto</span>
                                <span class="text-[10px] text-slate-400">Pastikan pencahayaan cukup dan wajah santri jelas</span>
                            </div>

                            <!-- Preview Element -->
                            <div id="previewContainer" class="hidden relative border border-slate-200 rounded-xl overflow-hidden aspect-video">
                                <img id="imagePreview" src="#" class="w-full h-full object-cover">
                                <button type="button" onclick="clearPreview()" class="absolute right-2 top-2 w-8 h-8 rounded-full bg-black/60 text-white flex items-center justify-center hover:bg-black/80 transition text-xs">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <button type="submit" id="btnSubmit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 text-xs rounded-xl shadow-md transition flex items-center justify-center space-x-1.5">
                                <i class="fas fa-cloud-upload-alt"></i> <span>Kirim Foto Tugas</span>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- NO CAMPAIGN YET -->
                <div class="w-full bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xl text-center space-y-4">
                    <div class="w-16 h-16 bg-slate-50 text-slate-400 rounded-full flex items-center justify-center mx-auto text-3xl shadow-md">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <h3 class="font-extrabold text-slate-800 text-base">Belum Ada Tugas</h3>
                    <p class="text-xs text-slate-400 leading-relaxed">Admin marketing belum menjadwalkan tugas pengambilan foto untuk hari ini. Silakan hubungi admin marketing.</p>
                </div>
            <?php endif; ?>
            
        <?php endif; ?>

    </main>

    <!-- FOOTER PWA PROMPT -->
    <footer class="p-4 text-center">
        <button id="btnInstall" class="hidden mx-auto bg-indigo-900 text-amber-400 border border-indigo-800 text-[10px] font-extrabold px-3 py-2 rounded-lg shadow-md transition hover:bg-indigo-850 flex items-center space-x-1.5">
            <i class="fab fa-android"></i> <span>Instal di Layar Utama HP</span>
        </button>
        <p class="text-[9px] text-slate-400 mt-2">© Villa Quran Indonesia. PWA Mobile Edition.</p>
    </footer>

    <!-- Scripts -->
    <script>
        function handlePwaLogin() {
            const form = document.getElementById('loginForm');
            const formData = new FormData(form);
            formData.append('action', 'pwa_login');
            
            const errorDiv = document.getElementById('login-error');
            errorDiv.classList.add('hidden');

            fetch('musyrif-upload-sosmed.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    errorDiv.innerText = data.message;
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                console.error(err);
                errorDiv.innerText = 'Koneksi gagal. Silakan coba lagi.';
                errorDiv.classList.remove('hidden');
            });
        }

        function triggerFileSelect() {
            document.getElementById('foto_mentah').click();
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('uploadPlaceholder').classList.add('hidden');
                    document.getElementById('previewContainer').classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearPreview() {
            document.getElementById('foto_mentah').value = '';
            document.getElementById('uploadPlaceholder').classList.remove('hidden');
            document.getElementById('previewContainer').classList.add('hidden');
        }

        // PWA REGISTRATION & SERVICE WORKER
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js')
            .then(reg => console.log('Service Worker Registered!', reg))
            .catch(err => console.error('Service Worker Registration Failed!', err));
        }

        // Handle PWA install prompt
        let deferredPrompt;
        const btnInstall = document.getElementById('btnInstall');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            btnInstall.classList.remove('hidden');
        });

        btnInstall.addEventListener('click', () => {
            btnInstall.classList.add('hidden');
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                }
                deferredPrompt = null;
            });
        });
    </script>
</body>
</html>
