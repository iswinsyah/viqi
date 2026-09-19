<?php
/**
 * MIGRASI & INISIALISASI TABEL TERPADU APP_USERS
 * SADIGS 4.0 - Universal Single Sign-On
 */

require_once __DIR__ . '/koneksi.php';

// 1. Buat Tabel app_users
$conn->query("CREATE TABLE IF NOT EXISTS app_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(255) NOT NULL,
    roles VARCHAR(255) NOT NULL DEFAULT 'santri',
    email VARCHAR(150) NULL,
    no_hp VARCHAR(50) NULL,
    foto_profil VARCHAR(255) NULL,
    user_type ENUM('pegawai', 'santri', 'walisantri') NOT NULL DEFAULT 'pegawai',
    ref_id INT NULL,
    status_aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$migrated_pegawai = 0;
$migrated_santri = 0;
$migrated_orangtua = 0;

// 2. Migrasikan Akun Pegawai / Ustadz / Yayasan / Super Admin dari akun_ustadz
$res_u = $conn->query("SELECT * FROM akun_ustadz");
if ($res_u && $res_u->num_rows > 0) {
    while ($u = $res_u->fetch_assoc()) {
        $username = trim($u['username'] ?? '');
        if (empty($username)) continue;
        $username_esc = $conn->real_escape_string($username);
        $password = $conn->real_escape_string($u['password'] ?? '');
        $nama = $conn->real_escape_string($u['nama_lengkap'] ?? $username);
        $role = $conn->real_escape_string($u['role'] ?? 'ustadz');
        $email = $conn->real_escape_string($u['email'] ?? '');
        $no_hp = $conn->real_escape_string($u['no_hp'] ?? '');
        $foto = $conn->real_escape_string($u['foto'] ?? '');
        $ref_id = (int)$u['id'];

        // Khusus akun winsyah / super admin beri multi-role lengkap
        if ($username === 'winsyah' || $ref_id === 9999 || strpos(strtolower($role), 'super_admin') !== false) {
            $role = 'super_admin,tutor,musyrif,walisantri,kepala_sekolah';
        }

        $sql_u = "INSERT INTO app_users (username, password, nama_lengkap, roles, email, no_hp, foto_profil, user_type, ref_id, status_aktif)
                  VALUES ('$username_esc', '$password', '$nama', '$role', '$email', '$no_hp', '$foto', 'pegawai', $ref_id, 1)
                  ON DUPLICATE KEY UPDATE 
                  password = VALUES(password),
                  nama_lengkap = VALUES(nama_lengkap),
                  roles = VALUES(roles),
                  email = VALUES(email),
                  no_hp = VALUES(no_hp),
                  foto_profil = VALUES(foto_profil),
                  ref_id = VALUES(ref_id)";
        if ($conn->query($sql_u)) {
            $migrated_pegawai++;
        }
    }
}

// 3. Pastikan Akun Super Admin Utama winsyah terdaftar kuat
$chk_winsyah = $conn->query("SELECT id FROM app_users WHERE username = 'winsyah' LIMIT 1");
if (!$chk_winsyah || $chk_winsyah->num_rows === 0) {
    $conn->query("INSERT INTO app_users (username, password, nama_lengkap, roles, user_type, ref_id, status_aktif)
                  VALUES ('winsyah', 'Khilafet@1924', 'Ustadz Winsyah (Super Admin)', 'super_admin,tutor,musyrif,walisantri,kepala_sekolah', 'pegawai', 9999, 1)");
    $migrated_pegawai++;
} else {
    $conn->query("UPDATE app_users SET roles = 'super_admin,tutor,musyrif,walisantri,kepala_sekolah' WHERE username = 'winsyah'");
}

// 4. Migrasikan Akun Santri dari buku_induk_santri
$res_s = $conn->query("SELECT * FROM buku_induk_santri");
if ($res_s && $res_s->num_rows > 0) {
    while ($s = $res_s->fetch_assoc()) {
        // Username santri bisa dari nisn, no_induk, atau nama panggil
        $u_santri = trim($s['nisn'] ?? '');
        if (empty($u_santri)) {
            $u_santri = trim($s['no_induk'] ?? '');
        }
        if (empty($u_santri)) {
            $u_santri = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $s['nama_lengkap'] ?? 'santri_' . $s['id']));
        }
        
        $u_santri_esc = $conn->real_escape_string($u_santri);
        $pass_santri = $conn->real_escape_string($s['password'] ?? '123456');
        $nama_santri = $conn->real_escape_string($s['nama_lengkap'] ?? $u_santri);
        $foto_santri = $conn->real_escape_string($s['foto_santri'] ?? '');
        $ref_s_id = (int)$s['id'];

        $sql_s = "INSERT INTO app_users (username, password, nama_lengkap, roles, foto_profil, user_type, ref_id, status_aktif)
                  VALUES ('$u_santri_esc', '$pass_santri', '$nama_santri', 'santri', '$foto_santri', 'santri', $ref_s_id, 1)
                  ON DUPLICATE KEY UPDATE 
                  nama_lengkap = VALUES(nama_lengkap),
                  foto_profil = VALUES(foto_profil),
                  ref_id = VALUES(ref_id)";
        if ($conn->query($sql_s)) {
            $migrated_santri++;
        }
    }
}

// 5. Migrasikan Akun Orang Tua dari akun_orangtua (jika tabel ada)
$res_tbl_o = $conn->query("SHOW TABLES LIKE 'akun_orangtua'");
if ($res_tbl_o && $res_tbl_o->num_rows > 0) {
    $res_o = $conn->query("SELECT * FROM akun_orangtua");
    if ($res_o && $res_o->num_rows > 0) {
        while ($o = $res_o->fetch_assoc()) {
            $u_ortu = trim($o['username'] ?? '');
            if (empty($u_ortu)) continue;
            $u_ortu_esc = $conn->real_escape_string($u_ortu);
            $pass_ortu = $conn->real_escape_string($o['password'] ?? '123456');
            $nama_ortu = $conn->real_escape_string($o['nama_lengkap'] ?? $o['nama_wali'] ?? 'Wali Santri');
            $no_hp_ortu = $conn->real_escape_string($o['no_hp'] ?? '');
            $ref_o_id = (int)$o['id'];

            $sql_o = "INSERT INTO app_users (username, password, nama_lengkap, roles, no_hp, user_type, ref_id, status_aktif)
                      VALUES ('$u_ortu_esc', '$pass_ortu', '$nama_ortu', 'walisantri', '$no_hp_ortu', 'walisantri', $ref_o_id, 1)
                      ON DUPLICATE KEY UPDATE 
                      password = VALUES(password),
                      nama_lengkap = VALUES(nama_lengkap),
                      roles = VALUES(roles),
                      no_hp = VALUES(no_hp),
                      ref_id = VALUES(ref_id)";
            if ($conn->query($sql_o)) {
                $migrated_orangtua++;
            }
        }
    }
}

$res_all = $conn->query("SELECT COUNT(*) as total FROM app_users");
$total_users = $res_all ? (int)$res_all->fetch_assoc()['total'] : 0;

$report = [
    'status' => 'success',
    'message' => 'Tabel terpadu app_users berhasil dibuat dan disinkronkan!',
    'total_users' => $total_users,
    'migrated_pegawai' => $migrated_pegawai,
    'migrated_santri' => $migrated_santri,
    'migrated_orangtua' => $migrated_orangtua,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
