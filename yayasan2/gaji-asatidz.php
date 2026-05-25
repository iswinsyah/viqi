<?php
require_once '../auth.php';
require_once '../koneksi.php';

$active_menu = 'gaji_asatidz';

// --- SETUP & AMBIL PENGATURAN GAJI ---
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_c INT DEFAULT 20000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_b INT DEFAULT 22500");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_a INT DEFAULT 25000");

$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_gaji (id INT PRIMARY KEY DEFAULT 1, gaji_grade_c INT DEFAULT 20000, gaji_grade_b INT DEFAULT 22500, gaji_grade_a INT DEFAULT 25000)");
$conn->query("INSERT IGNORE INTO pengaturan_gaji (id, gaji_grade_c, gaji_grade_b, gaji_grade_a) VALUES (1, 20000, 22500, 25000)");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_gaji'])) {
    $gaji_c = (int)$_POST['gaji_grade_c'];
    $gaji_b = (int)$_POST['gaji_grade_b'];
    $gaji_a = (int)$_POST['gaji_grade_a'];
    $conn->query("UPDATE pengaturan_gaji SET gaji_grade_c=$gaji_c, gaji_grade_b=$gaji_b, gaji_grade_a=$gaji_a WHERE id=1");
    $pesan_sukses = "Pengaturan Tarif Gaji Asatidz berhasil diperbarui!";
}

$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji->fetch_assoc();
$gaji_grade_c = $data_gaji['gaji_grade_c'] ?? 20000;
$gaji_grade_b = $data_gaji['gaji_grade_b'] ?? 22500;
$gaji_grade_a = $data_gaji['gaji_grade_a'] ?? 25000;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Gaji Asatidz | Yayasan 2</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan 2</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-chalkboard-teacher text-emerald-500 mr-2"></i>Pengaturan Gaji Asatidz</h1></div>
            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 text-emerald-700 px-4 py-3 rounded-lg mb-6 shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> $pesan_sukses</div>"; ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <h2 class="font-bold text-gray-800 mb-4 border-b pb-2"><i class="fas fa-file-invoice-dollar text-amber-500 mr-2"></i>Tarif Gaji Asatidz (Per Pertemuan)</h2>
                <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <input type="hidden" name="update_gaji" value="1">
                    <div><label class="block text-xs font-bold text-gray-700 mb-1">Grade C (Rp)</label><input type="number" name="gaji_grade_c" value="<?= $gaji_grade_c ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500" required></div>
                    <div><label class="block text-xs font-bold text-gray-700 mb-1">Grade B (Rp)</label><input type="number" name="gaji_grade_b" value="<?= $gaji_grade_b ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500" required></div>
                    <div><label class="block text-xs font-bold text-gray-700 mb-1">Grade A (Rp)</label><input type="number" name="gaji_grade_a" value="<?= $gaji_grade_a ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500" required></div>
                    <div><button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-2.5 px-4 rounded-lg transition shadow-sm flex justify-center items-center"><i class="fas fa-save mr-2"></i> Simpan Setting</button></div>
                </form>
                <p class="text-xs text-gray-500 mt-4"><i class="fas fa-info-circle text-blue-500"></i> Grade C adalah standar performa biasa. Grade B dan A diberikan sebagai bonus untuk performa di atas rata-rata.</p>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); }); document.getElementById('sidebar-overlay-yayasan2').addEventListener('click', () => { document.getElementById('sidebar-yayasan2').classList.toggle('hidden'); document.getElementById('sidebar-overlay-yayasan2').classList.toggle('hidden'); });
    </script>
</body>
</html>