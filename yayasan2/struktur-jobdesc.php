<?php
require_once 'auth.php'; // Menggunakan sistem keamanan Ruang Yayasan
require_once '../koneksi.php';

// 1. Inisialisasi Database (Self-Healing)
$conn->query("CREATE TABLE IF NOT EXISTS struktur_sekolah (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nomor INT NOT NULL,
    nama_jabatan VARCHAR(255) NOT NULL,
    ada TINYINT(1) DEFAULT 0,
    quota INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
@$conn->query("ALTER TABLE struktur_sekolah ADD COLUMN amanah_global TEXT NULL AFTER nama_jabatan");

// Cek apakah tabel kosong, jika iya masukkan data bawaan
$res_check = $conn->query("SELECT COUNT(*) as total FROM struktur_sekolah");
$row_check = $res_check->fetch_assoc();
if ($row_check['total'] == 0) {
    $default_positions = [
        [1, 'Kepala Sekolah'],
        [2, 'Wakil Kepala Sekolah'],
        [3, 'Sekretaris Sekolah'],
        [4, 'Bendahara Sekolah'],
        [5, 'Kepala Administrasi'],
        [7, 'Staff Administrasi'],
        [8, 'Kepala Keuangan'],
        [9, 'Staff Keuangan'],
        [10, 'Ustadz/ah'],
        [11, 'Kepala Ma\'had'],
        [12, 'Sekretaris Ma\'had'],
        [13, 'Bendahara Ma\'had'],
        [14, 'Kepala Asrama'],
        [15, 'Musyrif/ah'],
        [16, 'Kepala Dapur'],
        [17, 'Staff Dapur']
    ];
    foreach ($default_positions as $pos) {
        $nomor = $pos[0];
        $nama = $conn->real_escape_string($pos[1]);
        $conn->query("INSERT INTO struktur_sekolah (nomor, nama_jabatan, ada, quota) VALUES ($nomor, '$nama', 0, 0)");
    }
}

// 2. Handling AJAX Save Request
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_GET['action']) && $_GET['action'] === 'save') {
    header('Content-Type: application/json');
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, true);
    $data = $input['data'] ?? [];

    if (empty($data) || !is_array($data)) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
        exit;
    }

    $conn->begin_transaction();
    try {
        foreach ($data as $item) {
            $id = (int)$item['id'];
            $ada = (int)$item['ada'];
            $quota = (int)$item['quota'];
            $amanah_global = $conn->real_escape_string($item['amanah_global'] ?? '');
            $conn->query("UPDATE struktur_sekolah SET ada = $ada, quota = $quota, amanah_global = '$amanah_global' WHERE id = $id");
        }
        $conn->commit();
        echo json_encode(['status' => 'success']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 3. Ambil data struktur dari database
$result = $conn->query("SELECT * FROM struktur_sekolah ORDER BY nomor ASC");
$struktur_list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $struktur_list[] = $row;
    }
}

$active_menu = 'struktur_jobdesc';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Struktur Sekolah | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
        }
        /* Glassmorphism effects */
        .glass-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(251, 191, 36, 0.2);
        }
        /* Custom styled switch/checkbox wrapper */
        .checkbox-custom:checked + .checkbox-label {
            background-color: #f59e0b;
            border-color: #d97706;
        }
    </style>
</head>
<body class="bg-gray-50 antialiased text-gray-800 flex h-screen overflow-hidden">
    
    <!-- INCLUDE SIDEBAR YAYASAN -->
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <!-- HEADER -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0 border-b border-gray-100">
            <div class="flex items-center">
                <button id="open-sidebar-yayasan2" class="text-gray-500 hover:text-gray-700 md:hidden mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h2 class="font-bold text-gray-800 hidden sm:block">Panel Eksekutif Yayasan</h2>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-3 py-1 rounded-full flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Mode Manajemen Struktur
                </span>
            </div>
        </header>

        <!-- MAIN LAYOUT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50 p-6">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <!-- HEADER TITLE & INTRO -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 flex items-center">
                            <i class="fas fa-sitemap text-amber-600 mr-3"></i>
                            Struktur Organisasi Sekolah
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Penyusunan jabatan aktif dan alokasi quota SDM pengelola pesantren/sekolah.
                        </p>
                    </div>
                </div>

                <!-- STATS SUMMARY BOARD (DYNAMIC) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-2xl shadow-inner">
                            <i class="fas fa-id-badge"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Jabatan Aktif</p>
                            <h3 id="stat-active-roles" class="text-2xl font-bold text-gray-800 mt-0.5">0</h3>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 flex items-center gap-4 transition hover:shadow-md">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-2xl shadow-inner">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Total Quota Personil</p>
                            <h3 id="stat-total-quota" class="text-2xl font-bold text-gray-800 mt-0.5 font-mono">0 Orang</h3>
                        </div>
                    </div>
                </div>

                <!-- MAIN TABLE CARD -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-200/60 overflow-hidden">
                    <div class="px-6 py-4 bg-gradient-to-r from-amber-50 to-orange-50/30 border-b border-gray-200/80 flex justify-between items-center">
                        <span class="font-bold text-gray-800 flex items-center text-sm md:text-base">
                            <i class="fas fa-list-ol text-amber-600 mr-2"></i>
                            Daftar Jabatan & Alokasi Quota
                        </span>
                        <div id="save-status" class="hidden text-xs font-semibold text-emerald-600 flex items-center gap-1">
                            <i class="fas fa-check-circle animate-bounce"></i> Perubahan Tersimpan!
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/75 border-b border-gray-200 text-xs font-bold text-gray-500 uppercase tracking-wider">
                                    <th class="py-4 px-6 text-center w-16">No</th>
                                    <th class="py-4 px-6 w-1/4">Nama Jabatan</th>
                                    <th class="py-4 px-6">Amanah Global</th>
                                    <th class="py-4 px-6 text-center w-36">Ada / Tidak</th>
                                    <th class="py-4 px-6 text-center w-40">Quota Personil</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($struktur_list as $row): ?>
                                    <tr data-id="<?= $row['id'] ?>" class="table-row transition-all duration-200 hover:bg-gray-50/50">
                                        <td class="py-4 px-6 text-center font-bold text-gray-400 font-mono text-sm"><?= $row['nomor'] ?></td>
                                        <td class="py-4 px-6">
                                            <span class="role-name font-semibold text-gray-700 text-sm md:text-base transition-colors"><?= htmlspecialchars($row['nama_jabatan']) ?></span>
                                        </td>
                                        <td class="py-4 px-6">
                                            <input type="text" value="<?= htmlspecialchars($row['amanah_global'] ?? '') ?>" class="input-amanah w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm transition focus:outline-none focus:ring-2 focus:ring-amber-200 focus:border-amber-400 bg-white disabled:bg-gray-50 disabled:text-gray-400" placeholder="Masukkan konsep amanah global..." <?= $row['ada'] ? '' : 'disabled' ?>>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                                <input type="checkbox" class="sr-only peer checkbox-ada" onchange="handleRowState(this)" <?= $row['ada'] ? 'checked' : '' ?>>
                                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-focus:ring-2 peer-focus:ring-amber-300 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                            </label>
                                        </td>
                                        <td class="py-4 px-6 text-center">
                                            <div class="inline-flex items-center border border-gray-300 rounded-lg overflow-hidden shadow-sm bg-white transition hover:border-amber-400 focus-within:ring-2 focus-within:ring-amber-200">
                                                <button type="button" onclick="adjustQuota(this, -1)" class="px-2 py-1 bg-gray-50 hover:bg-gray-100 border-r border-gray-200 text-gray-500 hover:text-gray-700 transition">
                                                    <i class="fas fa-minus text-xs"></i>
                                                </button>
                                                <input type="number" min="0" max="999" value="<?= $row['quota'] ?>" oninput="calculateStats()" class="input-quota w-16 text-center py-1.5 px-2 font-mono text-sm font-bold text-gray-800 bg-white focus:outline-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" <?= $row['ada'] ? '' : 'disabled' ?>>
                                                <button type="button" onclick="adjustQuota(this, 1)" class="px-2 py-1 bg-gray-50 hover:bg-gray-100 border-l border-gray-200 text-gray-500 hover:text-gray-700 transition">
                                                    <i class="fas fa-plus text-xs"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- FOOTER ACTIONS -->
                    <div class="px-6 py-5 bg-gray-50 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-xs text-gray-400 flex items-center gap-1.5">
                            <i class="fas fa-info-circle text-amber-500 text-sm"></i>
                            Nonaktifkan jabatan untuk mereset quota menjadi 0 otomatis.
                        </div>
                        <button id="btn-save" onclick="saveStrukturData()" class="w-full sm:w-auto bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-gray-900 font-bold px-8 py-3 rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 group transform active:scale-95">
                            <i class="fas fa-save group-hover:rotate-12 transition-transform"></i>
                            Simpan Struktur
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- TOAST NOTIFICATION -->
    <div id="toast" class="fixed bottom-6 right-6 z-50 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none">
        <div class="bg-gray-900 text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 border border-gray-800">
            <span class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-sm shadow-inner">
                <i class="fas fa-check-circle"></i>
            </span>
            <div>
                <p class="font-bold text-sm text-white">Sukses!</p>
                <p class="text-xs text-gray-400 mt-0.5">Struktur organisasi berhasil diperbarui.</p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sidebar trigger
            const sidebar = document.getElementById('sidebar-yayasan2');
            const openBtn = document.getElementById('open-sidebar-yayasan2');
            const overlay = document.getElementById('sidebar-overlay-yayasan2');
            const closeBtn = document.getElementById('close-sidebar-yayasan2');

            function toggleSidebar() {
                if(sidebar && overlay) { sidebar.classList.toggle('hidden'); overlay.classList.toggle('hidden'); }
            }
            if(openBtn) openBtn.addEventListener('click', toggleSidebar);
            if(closeBtn) closeBtn.addEventListener('click', toggleSidebar);
            if(overlay) overlay.addEventListener('click', toggleSidebar);

            // Initial row state highlight and statistic calculations
            document.querySelectorAll('.table-row').forEach(row => {
                const checkbox = row.querySelector('.checkbox-ada');
                if (checkbox.checked) {
                    row.classList.add('bg-amber-50/45');
                    row.querySelector('.role-name').classList.add('text-amber-800');
                } else {
                    row.classList.add('opacity-60');
                }
            });

            calculateStats();
        });

        // Function to adjust quota input value using helper buttons (+ / -)
        function adjustQuota(btn, amount) {
            const input = btn.parentElement.querySelector('.input-quota');
            if (input.disabled) return;
            
            let currentVal = parseInt(input.value) || 0;
            let newVal = currentVal + amount;
            if (newVal < 0) newVal = 0;
            if (newVal > 999) newVal = 999;
            input.value = newVal;
            calculateStats();
        }

        // Handle when checkbox state changes
        function handleRowState(checkbox) {
            const row = checkbox.closest('.table-row');
            const input = row.querySelector('.input-quota');
            const inputAmanah = row.querySelector('.input-amanah');
            const roleName = row.querySelector('.role-name');

            if (checkbox.checked) {
                row.classList.remove('opacity-60');
                row.classList.add('bg-amber-50/45');
                roleName.classList.add('text-amber-800');
                input.disabled = false;
                if (inputAmanah) inputAmanah.disabled = false;
                if (parseInt(input.value) === 0) {
                    input.value = 1; // Default to 1 if checked
                }
            } else {
                row.classList.add('opacity-60');
                row.classList.remove('bg-amber-50/45');
                roleName.classList.remove('text-amber-800');
                input.disabled = true;
                if (inputAmanah) inputAmanah.disabled = true;
                input.value = 0; // Reset quota to 0 when disabled
            }
            calculateStats();
        }

        // Dynamically compute and display stats
        function calculateStats() {
            let activeRolesCount = 0;
            let totalQuotaCount = 0;

            document.querySelectorAll('.table-row').forEach(row => {
                const checked = row.querySelector('.checkbox-ada').checked;
                const quotaVal = parseInt(row.querySelector('.input-quota').value) || 0;

                if (checked) {
                    activeRolesCount++;
                    totalQuotaCount += quotaVal;
                }
            });

            document.getElementById('stat-active-roles').textContent = activeRolesCount;
            document.getElementById('stat-total-quota').textContent = totalQuotaCount + " Orang";
        }

        // AJAX Save to Database
        function saveStrukturData() {
            const btnSave = document.getElementById('btn-save');
            const originalContent = btnSave.innerHTML;
            
            // Show loading state
            btnSave.disabled = true;
            btnSave.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i> Menyimpan...';

            const payloadData = [];
            document.querySelectorAll('.table-row').forEach(row => {
                const id = row.getAttribute('data-id');
                const ada = row.querySelector('.checkbox-ada').checked ? 1 : 0;
                const quota = parseInt(row.querySelector('.input-quota').value) || 0;
                const amanah_global = row.querySelector('.input-amanah').value || '';
                
                payloadData.push({
                    id: id,
                    ada: ada,
                    quota: quota,
                    amanah_global: amanah_global
                });
            });

            fetch('struktur-jobdesc.php?action=save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ data: payloadData })
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    showToast();
                } else {
                    alert('Gagal menyimpan data: ' + (res.message || 'Error tidak diketahui'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan koneksi ke server.');
            })
            .finally(() => {
                btnSave.disabled = false;
                btnSave.innerHTML = originalContent;
            });
        }

        // Show elegant sliding toast
        function showToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('translate-y-24', 'opacity-0');
            toast.classList.add('translate-y-0', 'opacity-100');
            
            const saveStatusHeader = document.getElementById('save-status');
            if (saveStatusHeader) {
                saveStatusHeader.classList.remove('hidden');
                setTimeout(() => {
                    saveStatusHeader.classList.add('hidden');
                }, 3000);
            }

            setTimeout(() => {
                toast.classList.remove('translate-y-0', 'opacity-100');
                toast.classList.add('translate-y-24', 'opacity-0');
            }, 3000);
        }
    </script>
</body>
</html>
