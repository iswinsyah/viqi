<?php
require_once 'auth.php';
require_once 'koneksi.php';

$active_menu = 'community_scout';

// Self-healing: Pastikan tabel grup_komunitas ada
$conn->query("CREATE TABLE IF NOT EXISTS grup_komunitas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_grup VARCHAR(255) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    link_gabung VARCHAR(255) NOT NULL UNIQUE,
    analisa_relevansi TEXT,
    skor_kualitas INT DEFAULT 5,
    saran_pembuka TEXT,
    status VARCHAR(50) DEFAULT 'Belum Dihubungi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Auto-migration dari saved_communities.txt ke database jika tabel kosong
$check_empty = $conn->query("SELECT id FROM grup_komunitas LIMIT 1");
if ($check_empty && $check_empty->num_rows === 0 && file_exists('saved_communities.txt')) {
    $txt = file_get_contents('saved_communities.txt');
    $lines = explode("\n", $txt);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] !== '|') continue;
        
        $cols = explode('|', $line);
        if (count($cols) < 7) continue;
        
        $nama = trim($cols[1]);
        $plat = trim($cols[2]);
        $link_raw = trim($cols[3]);
        $analisa = trim($cols[4]);
        $skor_raw = trim($cols[5]);
        $saran = trim($cols[6]);
        
        // Skip header
        if (strpos($nama, 'Nama Grup') !== false || strpos($nama, '---') !== false) continue;
        
        // Extract URL dari markdown [Text](URL) jika ada
        $link = $link_raw;
        if (preg_match('/\[.*?\]\((.*?)\)/', $link_raw, $matches)) {
            $link = $matches[1];
        }
        
        if (empty($link) || strpos($link, 'http') === false) continue;
        
        $skor = (int)preg_replace('/[^0-9]/', '', $skor_raw);
        if ($skor <= 0) $skor = 5;
        
        $nama_esc = $conn->real_escape_string($nama);
        $plat_esc = $conn->real_escape_string($plat);
        $link_esc = $conn->real_escape_string($link);
        $analisa_esc = $conn->real_escape_string($analisa);
        $saran_esc = $conn->real_escape_string($saran);
        
        $conn->query("INSERT IGNORE INTO grup_komunitas (nama_grup, platform, link_gabung, analisa_relevansi, skor_kualitas, saran_pembuka)
                      VALUES ('$nama_esc', '$plat_esc', '$link_esc', '$analisa_esc', $skor, '$saran_esc')");
    }
}

// --- POST ACTIONS ---
$pesan_sukses = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] === 'save_prompt') {
        file_put_contents('prompt_community_scout.txt', $_POST['prompt_content']);
        header("Location: admin-community-scout.php?prompt_saved=1");
        exit;
    }
    if ($_POST['action'] === 'update_status') {
        $id = (int)$_POST['grup_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $conn->query("UPDATE grup_komunitas SET status = '$status' WHERE id = $id");
        header("Location: admin-community-scout.php?updated=1");
        exit;
    }
    if ($_POST['action'] === 'hapus') {
        $id = (int)$_POST['grup_id'];
        $conn->query("DELETE FROM grup_komunitas WHERE id = $id");
        header("Location: admin-community-scout.php?deleted=1");
        exit;
    }
}

// --- NOTIFIKASI ---
if (isset($_GET['prompt_saved'])) $pesan_sukses = "Prompt AI berhasil disimpan!";
if (isset($_GET['updated'])) $pesan_sukses = "Status grup berhasil diperbarui!";
if (isset($_GET['deleted'])) $pesan_sukses = "Grup berhasil dihapus!";

$prompt_file = 'prompt_community_scout.txt';
$default_prompt = "Anda adalah seorang Digital Community Specialist. Target audiens kita adalah: \n\n{{PERSONA}}\n\n Tugas Anda adalah mencari link grup WhatsApp, Telegram, dan Facebook yang relevan dengan target audiens tersebut.\nAturan Wajib:\n1. Grup yang dicari harus bersifat terbuka/publik (siapa saja boleh bergabung).\n2. Grup tersebut BUKAN merupakan grup kolam marketing milik sekolah/pesantren kompetitor lain.\n3. Kembalikan output HANYA dalam format JSON array murni tanpa markdown (tanpa ```json dan tanpa penjelasan lain). Format JSON harus tepat seperti ini:\n[\n  {\n    \"nama_grup\": \"Nama Grup\",\n    \"platform\": \"WhatsApp/Telegram/Facebook\",\n    \"link_gabung\": \"URL\",\n    \"analisa_relevansi\": \"...\",\n    \"skor_kualitas\": 8,\n    \"saran_pembuka\": \"...\"\n  }\n]\nCari minimal 5 grup baru.";
$prompt_content = file_exists($prompt_file) ? file_get_contents($prompt_file) : $default_prompt;

// --- FILTERS ---
$where = "1=1";
$filter_platform = $_GET['platform'] ?? '';
if (!empty($filter_platform)) {
    $where .= " AND platform = '" . $conn->real_escape_string($filter_platform) . "'";
}
$filter_status = $_GET['status'] ?? '';
if (!empty($filter_status)) {
    $where .= " AND status = '" . $conn->real_escape_string($filter_status) . "'";
}
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $where .= " AND (nama_grup LIKE '%" . $conn->real_escape_string($search) . "%' OR platform LIKE '%" . $conn->real_escape_string($search) . "%' OR analisa_relevansi LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$query_grup = "SELECT * FROM grup_komunitas WHERE $where ORDER BY id DESC";
$res_grup = $conn->query($query_grup);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Community Scout | Admin Villa Quran</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-marketing.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <button id="open-sidebar" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800">Sistem Informasi Manajemen (SIM)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-rose-500 flex items-center justify-center text-white font-bold shadow-sm">C</div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 flex items-center"><i class="fas fa-search-location text-rose-600 mr-2"></i>Community Scout</h1>
                <p class="text-gray-500 text-sm mt-1">Kelola grup komunitas potensial yang ditemukan oleh AI Agent untuk penjangkauan organik.</p>
            </div>

            <?php if($pesan_sukses): ?>
            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 p-4 rounded-lg mb-6 shadow-sm flex items-center">
                <i class="fas fa-check-circle mr-2 text-lg"></i> <?= htmlspecialchars($pesan_sukses) ?>
            </div>
            <?php endif; ?>

            <!-- Penjelasan Strategi -->
            <div class="bg-gradient-to-r from-rose-50 to-indigo-50 rounded-xl shadow-sm border border-rose-100 p-6 mb-6 flex items-start">
                <div class="bg-white p-3 rounded-full shadow-sm mr-4 flex-shrink-0">
                    <i class="fas fa-bullhorn text-2xl text-rose-500 animate-bounce"></i>
                </div>
                <div>
                    <h3 class="font-bold text-rose-900 mb-1">Penting: Penjajakan Grup Secara Humanis</h3>
                    <p class="text-xs text-rose-800 leading-relaxed">
                        AI bertugas mengintai dan mengumpulkan link grup publik/terbuka (dan menghindari kompetitor). Sebagai staf marketing, gunakan link gabung untuk masuk, bagikan konten bermanfaat, diskusikan topik secara alami, dan jangan lakukan spam promosi secara kasar agar tidak di-kick oleh admin grup.
                    </p>
                </div>
            </div>

            <!-- FILTERS & SEARCH -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
                <form action="" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Platform</label>
                        <select name="platform" class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 bg-white focus:ring-rose-500 focus:border-rose-500">
                            <option value="">Semua Platform</option>
                            <option value="WhatsApp" <?= $filter_platform == 'WhatsApp' ? 'selected' : '' ?>>WhatsApp</option>
                            <option value="Telegram" <?= $filter_platform == 'Telegram' ? 'selected' : '' ?>>Telegram</option>
                            <option value="Facebook" <?= $filter_platform == 'Facebook' ? 'selected' : '' ?>>Facebook</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 bg-white focus:ring-rose-500 focus:border-rose-500">
                            <option value="">Semua Status</option>
                            <option value="Belum Dihubungi" <?= $filter_status == 'Belum Dihubungi' ? 'selected' : '' ?>>Belum Dihubungi</option>
                            <option value="Sudah Gabung" <?= $filter_status == 'Sudah Gabung' ? 'selected' : '' ?>>Sudah Gabung</option>
                            <option value="Diabaikan" <?= $filter_status == 'Diabaikan' ? 'selected' : '' ?>>Diabaikan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Cari Nama / Deskripsi</label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Masukkan kata kunci..." class="w-full text-xs rounded-lg border border-gray-300 px-3 py-2 focus:ring-rose-500 focus:border-rose-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 rounded-lg text-xs transition shadow-sm"><i class="fas fa-filter mr-1"></i> Filter</button>
                        <a href="admin-community-scout.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-3 rounded-lg text-xs transition text-center flex items-center justify-center">Reset</a>
                    </div>
                </form>
            </div>

            <!-- TABLE OF GROUPS -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-users mr-2 text-rose-600"></i> Database Komunitas & Grup Terbuka</h3>
                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded font-mono">Total: <?= $res_grup ? $res_grup->num_rows : 0 ?> grup</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase">Grup & Platform</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase">Link Gabung</th>
                                <th class="px-6 py-3 text-left font-semibold text-gray-500 uppercase">Analisa & Pembuka</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase">Kualitas</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-center font-semibold text-gray-500 uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($res_grup && $res_grup->num_rows > 0): ?>
                                <?php while($row = $res_grup->fetch_assoc()): 
                                    // Custom colors for platforms
                                    $plat_badge = 'bg-gray-100 text-gray-800';
                                    $plat_icon = 'fa-users';
                                    if (strtolower($row['platform']) === 'whatsapp') {
                                        $plat_badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                        $plat_icon = 'fab fa-whatsapp';
                                    } elseif (strtolower($row['platform']) === 'telegram') {
                                        $plat_badge = 'bg-sky-50 text-sky-700 border-sky-200';
                                        $plat_icon = 'fab fa-telegram';
                                    } elseif (strtolower($row['platform']) === 'facebook') {
                                        $plat_badge = 'bg-indigo-50 text-indigo-700 border-indigo-200';
                                        $plat_icon = 'fab fa-facebook';
                                    }

                                    // Score badge colors
                                    $score = (int)$row['skor_kualitas'];
                                    if ($score >= 8) {
                                        $score_badge = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                    } elseif ($score >= 5) {
                                        $score_badge = 'bg-amber-100 text-amber-800 border-amber-200';
                                    } else {
                                        $score_badge = 'bg-rose-100 text-rose-800 border-rose-200';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-gray-900 text-sm"><?= htmlspecialchars($row['nama_grup']) ?></div>
                                        <div class="mt-1"><span class="px-2 py-0.5 inline-flex items-center text-[10px] font-bold rounded border <?= $plat_badge ?>"><i class="<?= $plat_icon ?> mr-1"></i><?= htmlspecialchars($row['platform']) ?></span></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?= htmlspecialchars($row['link_gabung']) ?>" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded font-bold text-[11px] shadow-sm transition">
                                            Gabung Grup <i class="fas fa-external-link-alt ml-1.5"></i>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 max-w-sm">
                                        <details class="text-xs text-gray-600 bg-gray-50 rounded-lg p-2 border border-gray-200 cursor-pointer hover:bg-gray-100 transition">
                                            <summary class="font-bold text-gray-700 select-none outline-none">Lihat Hasil Riset & Pembuka</summary>
                                            <div class="mt-2 space-y-2 pt-2 border-t border-gray-200">
                                                <div><strong>Analisa Relevansi:</strong><br><p class="text-gray-600 mt-0.5"><?= htmlspecialchars($row['analisa_relevansi']) ?></p></div>
                                                <div><strong>Saran Pembuka Percakapan:</strong><br><p class="italic text-rose-900 bg-rose-50 border border-rose-100 rounded p-1.5 mt-0.5"><?= htmlspecialchars($row['saran_pembuka']) ?></p></div>
                                            </div>
                                        </details>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $score_badge ?>"><?= $score ?>/10</span>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <form action="admin-community-scout.php" method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="grup_id" value="<?= $row['id'] ?>">
                                            <select name="status" onchange="this.form.submit()" class="text-xs font-semibold rounded border border-gray-300 px-2 py-1 bg-white focus:ring-rose-500 focus:border-rose-500 cursor-pointer">
                                                <option value="Belum Dihubungi" <?= $row['status'] == 'Belum Dihubungi' ? 'selected' : '' ?>>Belum Dihubungi</option>
                                                <option value="Sudah Gabung" <?= $row['status'] == 'Sudah Gabung' ? 'selected' : '' ?>>Sudah Gabung</option>
                                                <option value="Diabaikan" <?= $row['status'] == 'Diabaikan' ? 'selected' : '' ?>>Diabaikan</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-center whitespace-nowrap">
                                        <form action="admin-community-scout.php" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus grup ini?')">
                                            <input type="hidden" name="action" value="hapus">
                                            <input type="hidden" name="grup_id" value="<?= $row['id'] ?>">
                                            <button type="submit" class="text-rose-600 hover:text-rose-950 p-1 font-bold text-xs" title="Hapus"><i class="fas fa-trash text-sm"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-12 text-gray-500">
                                        <i class="fas fa-satellite-dish text-4xl mb-3 opacity-30 text-rose-500"></i>
                                        <p class="font-bold">Belum ada data grup.</p>
                                        <p class="text-xs mt-1">Grup baru akan terisi otomatis setiap pagi jam 07:00 via Cron Job, atau Anda bisa paksakan manual di AI Hub.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Prompt Editor -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
                <details>
                    <summary class="px-6 py-4 font-bold text-gray-800 cursor-pointer flex justify-between items-center select-none outline-none">
                        <span><i class="fas fa-cogs mr-2 text-rose-600"></i> Pengaturan Prompt AI</span>
                        <i class="fas fa-chevron-down transition-transform duration-300"></i>
                    </summary>
                    <div class="p-6 border-t border-gray-100">
                        <form action="admin-community-scout.php" method="POST">
                            <input type="hidden" name="action" value="save_prompt">
                            <label for="prompt_content" class="block text-sm font-medium text-gray-700 mb-2">Gunakan placeholder <code>{{PERSONA}}</code> untuk menyisipkan hasil analisa persona ke dalam prompt.</label>
                            <textarea id="prompt_content" name="prompt_content" rows="12" class="w-full p-3 border border-gray-300 rounded-lg font-mono text-xs focus:ring-rose-500 focus:border-rose-500"><?= htmlspecialchars($prompt_content) ?></textarea>
                            <button type="submit" class="mt-4 bg-rose-600 hover:bg-rose-700 text-white font-bold py-2 px-5 rounded-lg transition shadow-sm"><i class="fas fa-save mr-2"></i> Simpan Prompt</button>
                        </form>
                    </div>
                </details>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar').addEventListener('click', () => { 
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if(sidebar && overlay) {
                sidebar.classList.toggle('hidden'); 
                overlay.classList.toggle('hidden'); 
            }
        });
    </script>
</body>
</html>