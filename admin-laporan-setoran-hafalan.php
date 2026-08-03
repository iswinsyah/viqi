<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php'; // DB connection

// Allow musyrif and super_admin role
$user_roles = [];
if (isset($_SESSION['ustadz_role'])) {
    $user_roles = explode(',', $_SESSION['ustadz_role']);
}
$is_authorized = false;
if (isset($_SESSION['ustadz_id']) && (int)$_SESSION['ustadz_id'] === 9999) {
    $is_authorized = true;
}
foreach ($user_roles as $role) {
    $norm_role = str_replace([" ", "'"], ["_", ""], strtolower(trim($role)));
    if ($norm_role === 'musyrif' || $norm_role === 'super_admin') {
        $is_authorized = true;
        break;
    }
}
if (!$is_authorized) {
    die('Akses ditolak. Hanya Musyrif dan Super Admin yang dapat mengakses halaman ini.');
}

// Create table if not exists
$create_sql = "CREATE TABLE IF NOT EXISTS laporan_setoran_hafalan (\n    id INT AUTO_INCREMENT PRIMARY KEY,\n    nama_santri VARCHAR(150) NOT NULL,\n    nama_surat VARCHAR(150) NOT NULL,\n    ayat_mulai INT NOT NULL,\n    ayat_sampai INT NOT NULL,\n    halaman VARCHAR(50),\n    juz INT,\n    grade VARCHAR(20),\n    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($create_sql);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $nama_santri = $conn->real_escape_string($_POST['nama_santri'] ?? '');
    $nama_surat = $conn->real_escape_string($_POST['nama_surat'] ?? '');
    $ayat_mulai = (int)($_POST['ayat_mulai'] ?? 0);
    $ayat_sampai = (int)($_POST['ayat_sampai'] ?? 0);
    $halaman = $conn->real_escape_string($_POST['halaman'] ?? '');
    $juz = (int)($_POST['juz'] ?? 0);
    $grade = $conn->real_escape_string($_POST['grade'] ?? '');

    $sql = "INSERT INTO laporan_setoran_hafalan (nama_santri, nama_surat, ayat_mulai, ayat_sampai, halaman, juz, grade)\n            VALUES ('$nama_santri', '$nama_surat', $ayat_mulai, $ayat_sampai, '$halaman', $juz, '$grade')";
    if ($conn->query($sql) === TRUE) {
        $message = '<div class="bg-emerald-100 text-emerald-800 p-3 rounded mb-4">Laporan berhasil disimpan.</div>';
    } else {
        $message = '<div class="bg-rose-100 text-rose-800 p-3 rounded mb-4">Gagal menyimpan: ' . $conn->error . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Setoran Hafalan Santri</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
<div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Laporan Setoran Hafalan Santri</h1>
    <?php echo $message; ?>
    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Santri</label>
            <input type="text" name="nama_santri" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Ahmad Fauzi">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Nama Surat</label>
            <input type="text" name="nama_surat" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: Al‑Fatiha">
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Mulai Ayat</label>
                <input type="number" name="ayat_mulai" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Sampai Ayat</label>
                <input type="number" name="ayat_sampai" min="1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Halaman</label>
                <input type="text" name="halaman" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: 12">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Juz ke</label>
                <input type="number" name="juz" min="1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: 1">
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Grade</label>
            <input type="text" name="grade" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="Contoh: A, B+">
        </div>
        <div class="text-center">
            <button type="submit" name="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-6 rounded-lg transition">
                Simpan Laporan
            </button>
        </div>
    </form>
</div>
</body>
</html>
