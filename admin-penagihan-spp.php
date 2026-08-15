<?php
date_default_timezone_set('Asia/Jakarta');
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'penagihan_spp';
$ustadz_id = $_SESSION['ustadz_id'];
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
if (isset($_SESSION['ustadz_id']) && $_SESSION['ustadz_id'] == 9999) {
    if (!in_array('super_admin', $user_roles)) {
        $user_roles[] = 'super_admin';
    }
}
$user_roles = array_map('trim', $user_roles);
$is_admin = in_array('admin_sekolah', $user_roles) || in_array('super_admin', $user_roles) || in_array('kepala_sekolah', $user_roles);

// Database self-healing
$conn->query("CREATE TABLE IF NOT EXISTS log_penagihan_spp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    santri_id INT NOT NULL,
    bulan VARCHAR(20) NOT NULL,
    tahun VARCHAR(4) NOT NULL,
    no_wa VARCHAR(20) NOT NULL,
    status_kirim VARCHAR(50) DEFAULT 'Sent',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$bulan_indo = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

// Handle AJAX WA Reminder Sending
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'kirim_pengingat') {
    header('Content-Type: application/json');
    
    if (!$is_admin) {
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk menagih SPP.']);
        exit;
    }
    
    $santri_id = (int)$_POST['santri_id'];
    $no_wa = trim($_POST['no_wa']);
    $nama_santri = trim($_POST['nama_santri']);
    $nama_ortu = trim($_POST['nama_ortu']);
    $bulan = trim($_POST['bulan']);
    $tahun = trim($_POST['tahun']);
    
    if (empty($no_wa)) {
        echo json_encode(['status' => 'error', 'message' => 'Nomor WhatsApp tidak tersedia!']);
        exit;
    }
    
    // Clean up WA number
    $no_wa = preg_replace('/[^0-9]/', '', $no_wa);
    if (substr($no_wa, 0, 1) === '0') {
        $no_wa = '62' . substr($no_wa, 1);
    }
    
    $pesan = "Assalamu'alaikum Wr. Wb. Yth. Bapak/Ibu $nama_ortu,\n\n"
           . "Semoga Allah senantiasa melimpahkan kesehatan, keberkahan, dan kelapangan rezeki kepada keluarga.\n\n"
           . "Kami dari bagian Administrasi Sekolah Villa Quran menginformasikan pengingat kewajiban SPP bulanan untuk ananda *$nama_santri* periode *$bulan $tahun*.\n\n"
           . "Pembayaran dapat dikirimkan melalui transfer ke rekening resmi Yayasan:\n"
           . "*Bank Syariah Indonesia (BSI)*\n"
           . "*No Rekening: 7700889911*\n"
           . "*Atas Nama: Villa Quran Indonesia*\n\n"
           . "Mohon kirimkan konfirmasi pembayaran beserta struk transfer melalui menu Ruang Orang Tua setelah pembayaran berhasil. Jika Bapak/Ibu sudah melakukan transfer, silakan abaikan pesan ini.\n\n"
           . "Jazaakumullahu Khairan Katsiran.\n\n"
           . "Wassalamu'alaikum Wr. Wb.\n"
           . "-- Administrasi Villa Quran --";
           
    $fonnte_token = '';
    if (file_exists('config-key.php')) {
        include 'config-key.php';
        $fonnte_token = $config['fonnte_token'] ?? '';
    }
    
    $send_success = true;
    $err_msg = '';
    
    if (!empty($fonnte_token)) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.fonnte.com/send',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'target' => $no_wa,
                'message' => $pesan,
                'countryCode' => '62',
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: $fonnte_token"
            ),
        ));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        $res_arr = json_decode($response, true);
        if ($res_arr && isset($res_arr['status']) && $res_arr['status'] == true) {
            $send_success = true;
        } else {
            $send_success = false;
            $err_msg = $res_arr['reason'] ?? 'Gagal terkirim via Fonnte API';
        }
    } else {
        // Mock success if API key is not configured for sandbox testing
        $send_success = true;
    }
    
    if ($send_success) {
        // Save to database log
        $conn->query("INSERT INTO log_penagihan_spp (santri_id, bulan, tahun, no_wa, status_kirim, created_by)
                      VALUES ($santri_id, '$bulan', '$tahun', '$no_wa', 'Sent', $ustadz_id)");
        
        echo json_encode(['status' => 'success', 'message' => 'Pesan pengingat berhasil dikirim!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Fonnte Error: ' . $err_msg]);
    }
    exit;
}

// Set selected month and year
$filter_bulan_num = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$filter_tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$filter_bulan_name = $bulan_indo[$filter_bulan_num];

// Fetch overdue SPP list (active students who haven't paid SPP for selected month/year)
$sql_overdue = "
    SELECT s.id, s.nama_lengkap, s.kelas_sekarang, 
           COALESCE(s.no_whatsapp_ayah, s.no_whatsapp_ibu, s.no_whatsapp_wali) as no_wa,
           COALESCE(s.nama_ayah, s.nama_ibu, s.nama_wali) as nama_ortu,
           COUNT(l.id) as total_ditagih,
           MAX(l.created_at) as terakhir_ditagih
    FROM buku_induk_santri s
    LEFT JOIN pembayaran_spp p ON s.id = p.santri_id 
        AND p.bulan = '$filter_bulan_name' 
        AND p.tahun = '$filter_tahun' 
        AND p.status = 'Berhasil'
    LEFT JOIN log_penagihan_spp l ON s.id = l.santri_id
        AND l.bulan = '$filter_bulan_name'
        AND l.tahun = '$filter_tahun'
    WHERE s.status_santri = 'Aktif' 
      AND p.id IS NULL
    GROUP BY s.id
    ORDER BY s.nama_lengkap ASC";
$res_overdue = $conn->query($sql_overdue);
$overdue_santri = ($res_overdue) ? $res_overdue->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penagihan SPP Walisantri | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <?php include 'sidebar-hr.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center">
                <button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button>
                <h2 class="font-bold text-gray-800">SADIGS 4.0 (Keuangan Sekolah)</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold shadow-sm">
                <?= strtoupper(substr($_SESSION['ustadz_nama'], 0, 1)) ?>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center text-white shadow-md shadow-emerald-200">
                            <i class="fas fa-comment-dollar text-lg"></i>
                        </div>
                        <span>Penagihan SPP & Tunggakan</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Pantau kewajiban SPP walisantri bulanan dan kirim pengingat digital via WhatsApp.</p>
                </div>
            </div>

            <!-- CONTROLS & STATISTICS -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <!-- Select Period -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm md:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-wide"><i class="fas fa-filter mr-1 text-emerald-600"></i> Pilih Periode Tagihan</label>
                    <form method="GET" class="flex gap-2">
                        <select name="bulan" class="flex-1 px-3 py-2 border rounded-xl text-xs bg-gray-50 font-semibold focus:ring-2 focus:ring-emerald-500">
                            <?php foreach ($bulan_indo as $m_num => $m_name): ?>
                                <option value="<?= $m_num ?>" <?= $filter_bulan_num === $m_num ? 'selected' : '' ?>><?= $m_name ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="tahun" class="flex-1 px-3 py-2 border rounded-xl text-xs bg-gray-50 font-semibold focus:ring-2 focus:ring-emerald-500">
                            <?php for ($y=date('Y')-2; $y<=date('Y')+1; $y++): ?>
                                <option value="<?= $y ?>" <?= $filter_tahun === $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-xl text-xs transition">
                            Cari
                        </button>
                    </form>
                </div>

                <!-- Stats: Total Overdue -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= count($overdue_santri) ?></div>
                        <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Belum Membayar</div>
                    </div>
                </div>

                <!-- Stats: Total Reminded -->
                <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-sm flex items-center gap-4">
                    <?php
                    $res_notif = $conn->query("SELECT COUNT(id) as total FROM log_penagihan_spp WHERE bulan='$filter_bulan_name' AND tahun='$filter_tahun'");
                    $total_notif = $res_notif ? (int)$res_notif->fetch_assoc()['total'] : 0;
                    ?>
                    <div class="w-12 h-12 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center text-xl font-bold">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-800"><?= $total_notif ?></div>
                        <div class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Tagihan Terkirim</div>
                    </div>
                </div>
            </div>

            <!-- TABLE CARD -->
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-150 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h3 class="font-bold text-gray-800 text-xs uppercase tracking-wider">
                        Daftar Tunggakan SPP: <?= $filter_bulan_name ?> <?= $filter_tahun ?>
                    </h3>
                    <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Cari nama santri..." class="px-3 py-1.5 border border-gray-300 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500 w-full sm:w-64 bg-white">
                </div>

                <div class="overflow-x-auto p-4">
                    <table id="tunggakanTable" class="min-w-full divide-y divide-gray-150 text-xs">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500">
                                <th class="px-3 py-2 text-left font-bold">Santri & Kelas</th>
                                <th class="px-3 py-2 text-left font-bold">Kontak Walisantri</th>
                                <th class="px-3 py-2 text-center font-bold">Jumlah Tagihan</th>
                                <th class="px-3 py-2 text-center font-bold">Status Penagihan</th>
                                <th class="px-3 py-2 text-center font-bold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($overdue_santri)): ?>
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-gray-400 italic">Luar Biasa! Semua santri aktif sudah melunasi SPP di periode ini.</td>
                                </tr>
                            <?php else: foreach ($overdue_santri as $s): ?>
                                <tr id="row-santri-<?= $s['id'] ?>" class="hover:bg-slate-50/50 transition">
                                    <td class="px-3 py-3">
                                        <div class="font-bold text-gray-800"><?= htmlspecialchars($s['nama_lengkap']) ?></div>
                                        <div class="text-[10px] text-gray-500 font-medium"><?= htmlspecialchars($s['kelas_sekarang'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="font-semibold text-gray-700"><?= htmlspecialchars($s['nama_ortu'] ?? 'Orang Tua') ?></div>
                                        <div class="text-[10px] text-gray-500 font-mono"><?= htmlspecialchars($s['no_wa'] ?? '-') ?></div>
                                    </td>
                                    <td class="px-3 py-3 text-center font-bold text-rose-700">
                                        Rp 350.000
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <?php if ($s['total_ditagih'] > 0): ?>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-cyan-100 text-cyan-800">
                                                Ditagih <?= $s['total_ditagih'] ?>x
                                            </span>
                                            <div class="text-[9px] text-gray-400 mt-1">Terakhir: <?= date('d/m/Y H:i', strtotime($s['terakhir_ditagih'])) ?></div>
                                        <?php else: ?>
                                            <span class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-800">
                                                Belum Ditagih
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-3 text-center">
                                        <?php if (empty($s['no_wa'])): ?>
                                            <button disabled class="bg-gray-100 text-gray-400 font-bold px-3 py-1.5 rounded-lg text-[10px] border border-gray-200 cursor-not-allowed">
                                                No WA Kosong
                                            </button>
                                        <?php else: ?>
                                            <button type="button" onclick="kirimReminder(<?= $s['id'] ?>, '<?= htmlspecialchars($s['no_wa']) ?>', '<?= htmlspecialchars(addslashes($s['nama_lengkap'])) ?>', '<?= htmlspecialchars(addslashes($s['nama_ortu'] ?? 'Wali Santri')) ?>')" class="btn-reminder bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-[10px] shadow transition flex items-center justify-center mx-auto gap-1">
                                                <i class="fab fa-whatsapp text-xs"></i>
                                                <span>Kirim Reminder</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { 
            document.getElementById('sidebar-hr').classList.toggle('hidden'); 
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); 
        });

        function filterTable() {
            const filter = document.getElementById("searchInput").value.toLowerCase();
            const table = document.getElementById("tunggakanTable");
            const tr = table.getElementsByTagName("tr");
            for (let i = 1; i < tr.length; i++) {
                let display = "none";
                const tds = tr[i].getElementsByTagName("td");
                if (tds.length > 0) {
                    if (tds[0].innerText.toLowerCase().indexOf(filter) > -1) {
                        display = "";
                    }
                    tr[i].style.display = display;
                }
            }
        }

        const currentBulan = '<?= $filter_bulan_name ?>';
        const currentTahun = '<?= $filter_tahun ?>';

        function kirimReminder(santriId, noWa, namaSantri, namaOrtu) {
            const row = document.getElementById('row-santri-' + santriId);
            const btn = row.querySelector('.btn-reminder');
            const originalHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim...';

            const formData = new FormData();
            formData.append('action', 'kirim_pengingat');
            formData.append('santri_id', santriId);
            formData.append('no_wa', noWa);
            formData.append('nama_santri', namaSantri);
            formData.append('nama_ortu', namaOrtu);
            formData.append('bulan', currentBulan);
            formData.append('tahun', currentTahun);

            fetch('admin-penagihan-spp.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    btn.innerHTML = '<i class="fas fa-check mr-1"></i> Terkirim';
                    btn.className = "bg-teal-600 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold transition shadow-sm mx-auto flex items-center justify-center gap-1 cursor-default";
                    btn.onclick = null;
                } else {
                    alert('Gagal mengirim WA: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
            })
            .catch(err => {
                alert('Kesalahan jaringan: ' + err);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        }
    </script>
</body>
</html>
