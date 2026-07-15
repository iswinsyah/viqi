<?php
require_once 'auth.php';
require_once '../koneksi.php';

$active_menu = 'gaji_asatidz';

// --- SETUP & SELF-HEALING PENGATURAN GAJI ---
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_c INT DEFAULT 20000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_b INT DEFAULT 22500");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_a INT DEFAULT 25000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_pokok_muda INT DEFAULT 2500000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_pokok_utama INT DEFAULT 3500000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_kepsek_a INT DEFAULT 1500000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_kepsek_b INT DEFAULT 1000000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_kepsek_c INT DEFAULT 500000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_mahad_a INT DEFAULT 1500000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_mahad_b INT DEFAULT 1000000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_mahad_c INT DEFAULT 500000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_asrama_a INT DEFAULT 1200000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_asrama_b INT DEFAULT 800000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_asrama_c INT DEFAULT 400000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_admin_a INT DEFAULT 1000000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_admin_b INT DEFAULT 700000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_admin_c INT DEFAULT 400000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN insentif_kpi_muda INT DEFAULT 100000");
@$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN insentif_kpi_utama INT DEFAULT 500000");
 
$conn->query("CREATE TABLE IF NOT EXISTS pengaturan_gaji (
    id INT PRIMARY KEY DEFAULT 1, 
    gaji_grade_c INT DEFAULT 20000, 
    gaji_grade_b INT DEFAULT 22500, 
    gaji_grade_a INT DEFAULT 25000,
    gaji_pokok_muda INT DEFAULT 2500000,
    gaji_pokok_utama INT DEFAULT 3500000,
    tunj_kepsek_a INT DEFAULT 1500000,
    tunj_kepsek_b INT DEFAULT 1000000,
    tunj_kepsek_c INT DEFAULT 500000,
    tunj_mahad_a INT DEFAULT 1500000,
    tunj_mahad_b INT DEFAULT 1000000,
    tunj_mahad_c INT DEFAULT 500000,
    tunj_asrama_a INT DEFAULT 1200000,
    tunj_asrama_b INT DEFAULT 800000,
    tunj_asrama_c INT DEFAULT 400000,
    tunj_admin_a INT DEFAULT 1000000,
    tunj_admin_b INT DEFAULT 700000,
    tunj_admin_c INT DEFAULT 400000,
    insentif_kpi_muda INT DEFAULT 100000,
    insentif_kpi_utama INT DEFAULT 500000
)");
$conn->query("INSERT IGNORE INTO pengaturan_gaji (id) VALUES (1)");
 
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_gaji'])) {
    $gaji_c = (int)$_POST['gaji_grade_c'];
    $gaji_b = (int)$_POST['gaji_grade_b'];
    $gaji_a = (int)$_POST['gaji_grade_a'];
    
    $gaji_muda = (int)$_POST['gaji_pokok_muda'];
    $insentif_muda = (int)$_POST['insentif_kpi_muda'];
    $gaji_utama = (int)$_POST['gaji_pokok_utama'];
    $insentif_utama = (int)$_POST['insentif_kpi_utama'];
    
    $tunj_kepsek_a = (int)$_POST['tunj_kepsek_a'];
    $tunj_kepsek_b = (int)$_POST['tunj_kepsek_b'];
    $tunj_kepsek_c = (int)$_POST['tunj_kepsek_c'];
    
    $tunj_mahad_a = (int)$_POST['tunj_mahad_a'];
    $tunj_mahad_b = (int)$_POST['tunj_mahad_b'];
    $tunj_mahad_c = (int)$_POST['tunj_mahad_c'];
    
    $tunj_asrama_a = (int)$_POST['tunj_asrama_a'];
    $tunj_asrama_b = (int)$_POST['tunj_asrama_b'];
    $tunj_asrama_c = (int)$_POST['tunj_asrama_c'];
 
    $tunj_admin_a = (int)$_POST['tunj_admin_a'];
    $tunj_admin_b = (int)$_POST['tunj_admin_b'];
    $tunj_admin_c = (int)$_POST['tunj_admin_c'];
 
    $conn->query("UPDATE pengaturan_gaji SET 
        gaji_grade_c=$gaji_c, gaji_grade_b=$gaji_b, gaji_grade_a=$gaji_a,
        gaji_pokok_muda=$gaji_muda, insentif_kpi_muda=$insentif_muda,
        gaji_pokok_utama=$gaji_utama, insentif_kpi_utama=$insentif_utama,
        tunj_kepsek_a=$tunj_kepsek_a, tunj_kepsek_b=$tunj_kepsek_b, tunj_kepsek_c=$tunj_kepsek_c,
        tunj_mahad_a=$tunj_mahad_a, tunj_mahad_b=$tunj_mahad_b, tunj_mahad_c=$tunj_mahad_c,
        tunj_asrama_a=$tunj_asrama_a, tunj_asrama_b=$tunj_asrama_b, tunj_asrama_c=$tunj_asrama_c,
        tunj_admin_a=$tunj_admin_a, tunj_admin_b=$tunj_admin_b, tunj_admin_c=$tunj_admin_c
        WHERE id=1");
    $pesan_sukses = "Pengaturan Gaji & Tunjangan Pegawai berhasil diperbarui!";
}
 
$res_gaji = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1");
$data_gaji = $res_gaji->fetch_assoc();
 
$gaji_grade_c = $data_gaji['gaji_grade_c'] ?? 20000;
$gaji_grade_b = $data_gaji['gaji_grade_b'] ?? 22500;
$gaji_grade_a = $data_gaji['gaji_grade_a'] ?? 25000;
 
$gaji_pokok_muda = $data_gaji['gaji_pokok_muda'] ?? 2500000;
$insentif_kpi_muda = $data_gaji['insentif_kpi_muda'] ?? 100000;
$gaji_pokok_utama = $data_gaji['gaji_pokok_utama'] ?? 3500000;
$insentif_kpi_utama = $data_gaji['insentif_kpi_utama'] ?? 500000;
 
$tunj_kepsek_a = $data_gaji['tunj_kepsek_a'] ?? 1500000;
$tunj_kepsek_b = $data_gaji['tunj_kepsek_b'] ?? 1000000;
$tunj_kepsek_c = $data_gaji['tunj_kepsek_c'] ?? 500000;
 
$tunj_mahad_a = $data_gaji['tunj_mahad_a'] ?? 1500000;
$tunj_mahad_b = $data_gaji['tunj_mahad_b'] ?? 1000000;
$tunj_mahad_c = $data_gaji['tunj_mahad_c'] ?? 500000;
 
$tunj_asrama_a = $data_gaji['tunj_asrama_a'] ?? 1200000;
$tunj_asrama_b = $data_gaji['tunj_asrama_b'] ?? 800000;
$tunj_asrama_c = $data_gaji['tunj_asrama_c'] ?? 400000;
 
$tunj_admin_a = $data_gaji['tunj_admin_a'] ?? 1000000;
$tunj_admin_b = $data_gaji['tunj_admin_b'] ?? 700000;
$tunj_admin_c = $data_gaji['tunj_admin_c'] ?? 400000;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setting Gaji Pegawai | Panel Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR -->
    <?php include 'sidebar.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 text-left">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-id-card-clip text-amber-600 mr-2.5"></i>Pengaturan Gaji Pegawai</h1>
                <p class="text-xs text-gray-500 mt-1">Kelola standar honorer mengajar asatidz, gaji pokok, serta tunjangan jabatan pengurus yayasan.</p>
            </div>

            <?php if(isset($pesan_sukses)) echo "<div class='bg-emerald-100 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 shadow-sm flex items-center text-xs'><i class='fas fa-check-circle mr-2 text-base'></i> $pesan_sukses</div>"; ?>

            <form action="" method="POST" class="space-y-6 max-w-4xl">
                <input type="hidden" name="update_gaji" value="1">

                <!-- 1. INSENTIF USTADZ HONORER -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-amber-50 border-b border-amber-100 flex items-center justify-between">
                        <h2 class="font-bold text-amber-900 text-sm flex items-center"><i class="fas fa-chalkboard-teacher mr-2 text-amber-600"></i>1. Insentif Ustadz Honorer (Per Pertemuan)</h2>
                        <span class="text-[10px] bg-amber-100 text-amber-800 font-bold px-2 py-0.5 rounded">Honorer</span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Grade C (Minimal) - Rp</label>
                                <input type="number" name="gaji_grade_c" value="<?= $gaji_grade_c ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Grade B (Standar) - Rp</label>
                                <input type="number" name="gaji_grade_b" value="<?= $gaji_grade_b ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1">Grade A (Max) - Rp</label>
                                <input type="number" name="gaji_grade_a" value="<?= $gaji_grade_a ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-amber-500 text-sm" required>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-3.5"><i class="fas fa-info-circle mr-1 text-blue-500"></i> Insentif per pertemuan dihitung dinamis sesuai performa bulanan ustadz non-mukim.</p>
                    </div>
                </div>

                <!-- 2. GAJI POKOK PEGAWAI YAYASAN -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between">
                        <h2 class="font-bold text-emerald-900 text-sm flex items-center"><i class="fas fa-users-cog mr-2 text-emerald-600"></i>2. Gaji Pokok & Insentif Pegawai Yayasan</h2>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 font-bold px-2 py-0.5 rounded">Gaji & KPI</span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Pegawai Muda -->
                            <div class="p-4 bg-slate-50 rounded-xl border border-gray-200/80 space-y-3">
                                <span class="text-xs font-bold text-slate-800 flex items-center"><i class="fas fa-user-clock mr-1.5 text-slate-500 text-sm"></i>Pegawai Muda</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 mb-1">Gaji Pokok Tetap (Rp)</label>
                                        <input type="number" name="gaji_pokok_muda" value="<?= $gaji_pokok_muda ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 mb-1">Insentif KPI Max (Rp)</label>
                                        <input type="number" name="insentif_kpi_muda" value="<?= $insentif_kpi_muda ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                    </div>
                                </div>
                                <span class="text-[9px] text-gray-400 block"><i class="fas fa-info-circle mr-1 text-amber-600"></i> Rekomendasi: Pokok Rp 900.000 + Insentif KPI Rp 100.000 (Total Rp 1.000.000 jika sangat rajin).</span>
                            </div>

                            <!-- Pegawai Utama -->
                            <div class="p-4 bg-slate-50 rounded-xl border border-gray-200/80 space-y-3">
                                <span class="text-xs font-bold text-slate-800 flex items-center"><i class="fas fa-user-check mr-1.5 text-slate-500 text-sm"></i>Pegawai Utama</span>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 mb-1">Gaji Pokok Tetap (Rp)</label>
                                        <input type="number" name="gaji_pokok_utama" value="<?= $gaji_pokok_utama ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-gray-500 mb-1">Insentif KPI Max (Rp)</label>
                                        <input type="number" name="insentif_kpi_utama" value="<?= $insentif_kpi_utama ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                    </div>
                                </div>
                                <span class="text-[9px] text-gray-400 block"><i class="fas fa-info-circle mr-1 text-emerald-600"></i> Diberikan penuh jika nilai KPI sangat baik, parsial jika cukup, dan Rp 0 jika kurang.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. TUNJANGAN JABATAN PEGAWAI YAYASAN -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 bg-blue-50 border-b border-blue-100 flex items-center justify-between">
                        <h2 class="font-bold text-blue-900 text-sm flex items-center"><i class="fas fa-award mr-2 text-blue-600"></i>3. Tunjangan Jabatan Pegawai Yayasan</h2>
                        <span class="text-[10px] bg-blue-100 text-blue-800 font-bold px-2 py-0.5 rounded">Tunjangan</span>
                    </div>
                    <div class="p-6 space-y-6 divide-y divide-gray-100">
                        
                        <!-- Kepala Sekolah -->
                        <div class="space-y-3.5">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide block"><i class="fas fa-graduation-cap mr-1 text-slate-500"></i> Kepala Sekolah</span>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade A Max (Rp)</label>
                                    <input type="number" name="tunj_kepsek_a" value="<?= $tunj_kepsek_a ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade B Standar (Rp)</label>
                                    <input type="number" name="tunj_kepsek_b" value="<?= $tunj_kepsek_b ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade C Minimal (Rp)</label>
                                    <input type="number" name="tunj_kepsek_c" value="<?= $tunj_kepsek_c ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                            </div>
                        </div>

                        <!-- Kepala Ma'had -->
                        <div class="space-y-3.5 pt-4">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide block"><i class="fas fa-mosque mr-1 text-slate-500"></i> Kepala Ma'had</span>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade A Max (Rp)</label>
                                    <input type="number" name="tunj_mahad_a" value="<?= $tunj_mahad_a ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade B Standar (Rp)</label>
                                    <input type="number" name="tunj_mahad_b" value="<?= $tunj_mahad_b ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade C Minimal (Rp)</label>
                                    <input type="number" name="tunj_mahad_c" value="<?= $tunj_mahad_c ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                            </div>
                        </div>

                        <!-- Kepala Asrama -->
                        <div class="space-y-3.5 pt-4">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide block"><i class="fas fa-hotel mr-1 text-slate-500"></i> Kepala Asrama</span>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade A Max (Rp)</label>
                                    <input type="number" name="tunj_asrama_a" value="<?= $tunj_asrama_a ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade B Standar (Rp)</label>
                                    <input type="number" name="tunj_asrama_b" value="<?= $tunj_asrama_b ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade C Minimal (Rp)</label>
                                    <input type="number" name="tunj_asrama_c" value="<?= $tunj_asrama_c ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Sekolah -->
                        <div class="space-y-3.5 pt-4">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wide block"><i class="fas fa-user-shield mr-1 text-slate-500"></i> Admin Sekolah</span>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade A Max (Rp)</label>
                                    <input type="number" name="tunj_admin_a" value="<?= $tunj_admin_a ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade B Standar (Rp)</label>
                                    <input type="number" name="tunj_admin_b" value="<?= $tunj_admin_b ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 mb-1">Grade C Minimal (Rp)</label>
                                    <input type="number" name="tunj_admin_c" value="<?= $tunj_admin_c ?>" class="w-full px-3 py-1.5 border rounded-lg focus:ring-amber-500 text-xs" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="flex justify-end pt-2">
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-gray-900 font-bold py-3 px-8 rounded-xl transition shadow-md flex items-center justify-center text-sm"><i class="fas fa-save mr-2 text-base"></i> Simpan Semua Pengaturan Gaji</button>
                </div>
            </form>
        </main>
    </div>

    <!-- JS SIDEBAR COLLAPSE -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-yayasan2');
            const openBtn = document.getElementById('open-sidebar-yayasan2');
            const closeBtn = document.getElementById('close-sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');

            function toggleSidebar() {
                if(sidebar && overlay) { 
                    sidebar.classList.toggle('hidden'); 
                    overlay.classList.toggle('hidden'); 
                }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);
        });
    </script>
</body>
</html>