<?php
require_once 'auth.php';
require_once '../koneksi.php';

// Pastikan kolom 'role' ada di tabel akun_ustadz (sudah ditambahkan via login-ustadz.php, tapi jaga-jaga)
$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji ? $res_gaji->fetch_assoc() : [];
$gaji_grade_b = $data_gaji['gaji_grade_b'] ?? 22500;
$gaji_pokok_muda = $data_gaji['gaji_pokok_muda'] ?? 2500000;
$gaji_pokok_utama = $data_gaji['gaji_pokok_utama'] ?? 3500000;
$tunj_kepsek_a = $data_gaji['tunj_kepsek_a'] ?? 1500000;
$tunj_mahad_a = $data_gaji['tunj_mahad_a'] ?? 1500000;
$tunj_asrama_a = $data_gaji['tunj_asrama_a'] ?? 1200000;
$tunj_admin_a = $data_gaji['tunj_admin_a'] ?? 1000000;

@$conn->query("ALTER TABLE akun_ustadz MODIFY COLUMN role VARCHAR(255)");

$conn->query("CREATE TABLE IF NOT EXISTS akun_ustadz (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(150), username VARCHAR(50) UNIQUE, password VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

// Self-healing: Tambahkan kolom status_pegawai jika belum ada
$res_status_col = $conn->query("SHOW COLUMNS FROM akun_ustadz LIKE 'status_pegawai'");
if ($res_status_col && $res_status_col->num_rows == 0) {
    $conn->query("ALTER TABLE akun_ustadz ADD COLUMN status_pegawai VARCHAR(50) DEFAULT 'Pengabdian' AFTER role");
}
// Self-healing: Tambahkan kolom whatsapp jika belum ada
$res_wa_col = $conn->query("SHOW COLUMNS FROM akun_ustadz LIKE 'whatsapp'");
if ($res_wa_col && $res_wa_col->num_rows == 0) {
    $conn->query("ALTER TABLE akun_ustadz ADD COLUMN whatsapp VARCHAR(20) DEFAULT NULL AFTER status_pegawai");
}
$pesan_sukses = isset($_GET['sukses']) ? $_GET['sukses'] : null;
$pesan_error = isset($_GET['error']) ? $_GET['error'] : null;

if (isset($_GET['hapus_id'])) {
    $id = (int)$_GET['hapus_id'];
    $conn->query("DELETE FROM akun_ustadz WHERE id = $id");
    header("Location: asatidz.php?sukses=" . urlencode("Akun ustadz berhasil dihapus!"));
    exit;
}

if (isset($_GET['aktifkan_id'])) {
    $id = (int)$_GET['aktifkan_id'];
    $conn->query("UPDATE akun_ustadz SET status_pegawai = 'Aktif' WHERE id = $id");
    header("Location: asatidz.php?sukses=" . urlencode("Akun berhasil diaktifkan kembali! Blokir dan akses presensi telah dipulihkan oleh Super Admin."));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = !empty($_POST['id']) ? (int)$_POST['id'] : 0;
    $nama = $conn->real_escape_string($_POST['nama']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); // Password disimpan plain text sesuai struktur saat ini
    $roles_array = $_POST['roles'] ?? [];
    $role = implode(',', $roles_array);
    $status_pegawai = $conn->real_escape_string($_POST['status_pegawai']);
    $whatsapp = $conn->real_escape_string(trim($_POST['whatsapp']));

    $cek = $conn->query("SELECT id FROM akun_ustadz WHERE username = '$username' AND id != $id");
    if ($cek && $cek->num_rows > 0) {
        $pesan_error = "Username '$username' sudah terpakai!";
    } else {
        if ($id > 0) {
            $sql = "UPDATE akun_ustadz SET nama='$nama', username='$username', password='$password', role='$role', status_pegawai='$status_pegawai', whatsapp='$whatsapp' WHERE id=$id";
            $pesan_sukses = "Akun ustadz berhasil diupdate!";
        } else {
            $sql = "INSERT INTO akun_ustadz (nama, username, password, role, status_pegawai, whatsapp) VALUES ('$nama', '$username', '$password', '$role', '$status_pegawai', '$whatsapp')";
            $pesan_sukses = "Akun ustadz baru berhasil ditambahkan!";
        }
        if ($conn->query($sql)) {
            header("Location: asatidz.php?sukses=" . urlencode($pesan_sukses));
            exit;
        } else {
            $pesan_error = "Gagal menyimpan data: " . $conn->error;
        }
    }
}

$edit_mode = false; $data_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $id = (int)$_GET['edit_id'];
    $res = $conn->query("SELECT * FROM akun_ustadz WHERE id = $id");
    if ($res) $data_edit = $res->fetch_assoc();
}
$active_menu = 'asatidz';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Asatidz | Yayasan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center"><button id="open-sidebar-yayasan2" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2></div>
        </header>
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-users-cog text-amber-500 mr-2"></i>Hak Akses Ruang Asatidz</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <?php if(isset($pesan_error)) echo "<div class='bg-rose-100 text-rose-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> $pesan_error</div>"; ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-8 overflow-hidden">
                <div class="px-6 py-4 bg-amber-50 border-b border-amber-100"><h2 class="font-bold text-amber-800"><i class="fas <?= $edit_mode ? 'fa-edit' : 'fa-user-plus' ?> mr-2"></i><?= $edit_mode ? 'Edit Akun' : 'Buat Akun Baru' ?></h2></div>
                <form action="asatidz.php" method="POST" class="p-6">
                    <input type="hidden" name="id" value="<?= $edit_mode ? $data_edit['id'] : '' ?>">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                        <div class="md:col-span-1"><label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label><input type="text" name="nama" value="<?= $edit_mode ? htmlspecialchars($data_edit['nama']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500" placeholder="Contoh: Ust. Ahmad"></div>
                        <div class="md:col-span-1"><label class="block text-sm font-medium text-gray-700 mb-1">Username Login</label><input type="text" name="username" value="<?= $edit_mode ? htmlspecialchars($data_edit['username']) : '' ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500" placeholder="Contoh: ahmad123"></div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                            <div class="relative font-mono">
                                <input type="password" id="input-password" name="password" value="<?= $edit_mode ? htmlspecialchars($data_edit['password']) : '12345678' ?>" required class="w-full pl-4 pr-10 py-2 border rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm" placeholder="Kata sandi...">
                                <button type="button" onclick="togglePasswordVisibility('input-password', 'eye-icon-input')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-amber-500">
                                    <i id="eye-icon-input" class="fas fa-eye-slash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="md:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status_pegawai" required class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500">
                                <?php
                                $statuses = ['Pengurus Yayasan', 'Staff LDU', 'Pengabdian', 'Honorer', 'Pegawai Muda', 'Pegawai Utama'];
                                foreach ($statuses as $s) {
                                    $sel = ($edit_mode && ($data_edit['status_pegawai'] ?? 'Pengabdian') === $s) ? 'selected' : '';
                                    echo "<option value='$s' $sel>$s</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="md:col-span-1"><label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label><input type="text" name="whatsapp" value="<?= $edit_mode ? htmlspecialchars($data_edit['whatsapp'] ?? '') : '' ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500" placeholder="Contoh: 62851xxxxxx"></div>
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Peran (Bisa pilih lebih dari satu)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 border p-4 rounded-lg bg-gray-50">
                                <?php
                                $all_roles = [
                                    'sekretaris_yayasan' => 'Sekretaris Yayasan',
                                    'bendahara_yayasan' => 'Bendahara Yayasan',
                                    'kepala_sekolah' => 'Kepala Sekolah', 
                                    'sekretaris_sekolah' => 'Sekretaris Sekolah',
                                    'bendahara_sekolah' => 'Bendahara Sekolah', 
                                    'admin_sekolah' => 'Admin Sekolah',
                                    'kepala_mahad' => "Kepala Ma'had",
                                    'kepala_asrama' => 'Kepala Asrama', 
                                    'musyrif' => 'Musyrif', 
                                    'ustadz' => 'Ustadz'
                                ];
                                $user_roles = $edit_mode && !empty($data_edit['role']) ? explode(',', $data_edit['role']) : [];
                                foreach ($all_roles as $value => $label) {
                                    $checked = in_array($value, $user_roles) ? 'checked' : '';
                                    echo "<label class='flex items-center space-x-2 cursor-pointer'><input type='checkbox' name='roles[]' value='$value' $checked class='w-4 h-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500'><span>$label</span></label>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <?php if($edit_mode) echo '<a href="asatidz.php" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg font-bold mr-2">Batal</a>'; ?>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2.5 px-6 rounded-lg shadow-md transition"><i class="fas fa-save mr-2"></i> <?= $edit_mode ? 'Update Akun' : 'Simpan Akun' ?></button>
                    </div>
                </form>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50"><h2 class="font-bold text-gray-800">Daftar Akun Terdaftar</h2></div>
                <div class="overflow-x-auto p-4">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Nama Ustadz</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Username</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Password</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">WhatsApp</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Peran</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Gaji Pokok</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Tunjangan</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Honor</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php 
                            $res = $conn->query("SELECT * FROM akun_ustadz ORDER BY nama ASC"); 
                            if ($res && $res->num_rows > 0) { 
                                while($row = $res->fetch_assoc()) { 
                                    $roles_display_html = '';
                                    if (!empty($row['role'])) {
                                        $role_list = explode(',', $row['role']);
                                        foreach ($role_list as $r) {
                                            $label = ucwords(str_replace('_', ' ', $r));
                                            $roles_display_html .= "<span class='inline-block bg-amber-100 text-amber-800 rounded-full px-2 py-0.5 text-[10px] font-semibold mr-1 mb-1'>$label</span>";
                                        }
                                    } else {
                                        $roles_display_html = "<span class='text-gray-400 italic'>Belum diatur</span>";
                                    }

                                    $status_display = htmlspecialchars($row['status_pegawai'] ?? 'Pengabdian');
                                    $status_color = 'bg-gray-100 text-gray-800 border-gray-200';
                                    if ($status_display === 'Pengurus Yayasan') $status_color = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                                    if ($status_display === 'Staff LDU') $status_color = 'bg-cyan-50 text-cyan-700 border-cyan-200';
                                    if ($status_display === 'Pengabdian') $status_color = 'bg-blue-50 text-blue-700 border-blue-200';
                                    if ($status_display === 'Honorer') $status_color = 'bg-amber-50 text-amber-700 border-amber-200';
                                    if ($status_display === 'Pegawai Muda') $status_color = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    if ($status_display === 'Pegawai Utama') $status_color = 'bg-purple-50 text-purple-700 border-purple-200';

                                    // 1. Gaji Pokok
                                    $gaji_pokok = 0;
                                    if ($status_display === 'Pegawai Muda') {
                                        $gaji_pokok = $gaji_pokok_muda;
                                    } elseif ($status_display === 'Pegawai Utama') {
                                        $gaji_pokok = $gaji_pokok_utama;
                                    }

                                    // 2. Tunjangan (Dihitung dari Grade A Maksimal)
                                    $tunjangan = 0;
                                    if (!empty($row['role'])) {
                                        $role_list = explode(',', $row['role']);
                                        foreach ($role_list as $r) {
                                            if ($r === 'kepala_sekolah') $tunjangan += $tunj_kepsek_a;
                                            elseif ($r === 'kepala_mahad') $tunjangan += $tunj_mahad_a;
                                            elseif ($r === 'kepala_asrama') $tunjangan += $tunj_asrama_a;
                                            elseif ($r === 'admin_sekolah') $tunjangan += $tunj_admin_a;
                                        }
                                    }

                                    // 3. Honor Mengajar (Jurnal bulan berjalan * tarif Grade B)
                                    $ust_id = (int)$row['id'];
                                    $res_jurnal = $conn->query("SELECT COUNT(*) as total FROM jurnal_mengajar WHERE ustadz_id = $ust_id AND MONTH(tanggal) = MONTH(CURRENT_DATE()) AND YEAR(tanggal) = YEAR(CURRENT_DATE())");
                                    $total_pertemuan = $res_jurnal ? (int)$res_jurnal->fetch_assoc()['total'] : 0;
                                    $honor = $total_pertemuan * $gaji_grade_b;

                                    // 4. Total Gaji
                                    $total_gaji = $gaji_pokok + $tunjangan + $honor;

                                    $btn_aktifkan = "";
                                    if (($row['status_pegawai'] ?? '') === 'Nonaktif') {
                                        $btn_aktifkan = "<a href='?aktifkan_id={$row['id']}' onclick=\"return confirm('Buka blokir dan aktifkan kembali akun ini?')\" class='bg-emerald-100 hover:bg-emerald-200 text-emerald-800 font-bold px-2 py-1 rounded text-[10px] mr-2 inline-flex items-center gap-1 border border-emerald-300 shadow-sm'><i class='fas fa-unlock'></i> Aktifkan</a>";
                                    }

                                    echo "<tr class='hover:bg-gray-50 text-xs'>
                                        <td class='px-4 py-3 font-bold text-gray-900'>".htmlspecialchars($row['nama'])."</td>
                                        <td class='px-4 py-3'><span class='px-2 py-1 bg-gray-100 rounded font-mono text-gray-700'>".htmlspecialchars($row['username'])."</span></td>
                                        <td class='px-4 py-3 font-mono relative'>
                                            <span id='pwd-text-{$row['id']}' style='display:none;'>".htmlspecialchars($row['password'])."</span>
                                            <span id='pwd-masked-{$row['id']}'>••••••••</span>
                                            <button type='button' onclick='toggleRowPassword({$row['id']})' class='text-gray-400 hover:text-amber-500 ml-1.5 focus:outline-none'>
                                                <i id='pwd-icon-{$row['id']}' class='fas fa-eye text-[10px]'></i>
                                            </button>
                                        </td>
                                        <td class='px-4 py-3 font-mono font-bold text-gray-700'>".htmlspecialchars($row['whatsapp'] ?? '-')."</td>
                                        <td class='px-4 py-3'>$roles_display_html</td>
                                        <td class='px-4 py-3'><span class='inline-block px-2.5 py-0.5 rounded text-[10px] font-bold border $status_color'>$status_display</span></td>
                                        <td class='px-4 py-3 text-right font-semibold text-slate-700'>Rp ".number_format($gaji_pokok, 0, ',', '.')."</td>
                                        <td class='px-4 py-3 text-right font-semibold text-slate-700'>Rp ".number_format($tunjangan, 0, ',', '.')."</td>
                                        <td class='px-4 py-3 text-right font-semibold text-slate-700'>Rp ".number_format($honor, 0, ',', '.')." <span class='text-[9px] text-gray-400 block'>($total_pertemuan x)</span></td>
                                        <td class='px-4 py-3 text-right font-bold text-amber-600 bg-amber-50/20'>Rp ".number_format($total_gaji, 0, ',', '.')."</td>
                                        <td class='px-4 py-3 text-center'>
                                            {$btn_aktifkan}
                                            <a href='?edit_id={$row['id']}' class='text-blue-500 hover:text-blue-700 mr-3'><i class='fas fa-edit'></i></a>
                                            <a href='?hapus_id={$row['id']}' onclick=\"return confirm('Hapus akses login untuk ustadz ini?')\" class='text-red-500 hover:text-red-700'><i class='fas fa-trash'></i></a>
                                        </td>
                                    </tr>"; 
                                } 
                            } else { 
                                echo "<tr><td colspan='11' class='text-center py-6 text-gray-500 italic'>Belum ada akun Asatidz yang didaftarkan.</td></tr>"; 
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        }); 
        document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { 
            document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); 
        });

        function togglePasswordVisibility(inputId, iconId) {
            var input = document.getElementById(inputId);
            var icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye-slash';
            }
        }
        function toggleRowPassword(id) {
            var txt = document.getElementById('pwd-text-' + id);
            var msk = document.getElementById('pwd-masked-' + id);
            var icon = document.getElementById('pwd-icon-' + id);
            if (txt.style.display === 'none') {
                txt.style.display = 'inline';
                msk.style.display = 'none';
                icon.className = 'fas fa-eye-slash text-[10px]';
            } else {
                txt.style.display = 'none';
                msk.style.display = 'inline';
                icon.className = 'fas fa-eye text-[10px]';
            }
        }
    </script>
</body>
</html>