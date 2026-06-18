<?php
session_start();
require_once '../koneksi.php';

// 1. Buat Tabel Otomatis jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS peraturan_pegawai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jabatan VARCHAR(100) UNIQUE NOT NULL,
    konten TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// 2. Proses Simpan ke Database
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_peraturan'])) {
    $jabatan = $conn->real_escape_string($_POST['jabatan']);
    $konten = $conn->real_escape_string($_POST['konten']);
    
    $sql = "INSERT INTO peraturan_pegawai (jabatan, konten) VALUES ('$jabatan', '$konten')
            ON DUPLICATE KEY UPDATE konten = VALUES(konten)";
            
    if ($conn->query($sql) === TRUE) {
        $pesan_sukses = "SOP & Peraturan untuk jabatan <b>$jabatan</b> berhasil disahkan dan disimpan!";
    } else {
        $pesan_error = "Gagal menyimpan peraturan: " . $conn->error;
    }
}

// Ambil data peraturan yang sudah ada untuk dropdown
$saved_rules = [];
$res = $conn->query("SELECT jabatan, konten FROM peraturan_pegawai ORDER BY jabatan ASC");
if ($res) {
    while($row = $res->fetch_assoc()) {
        $saved_rules[$row['jabatan']] = $row['konten'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOP & Peraturan Pegawai | Ruang Yayasan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Library Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #1e3a8a; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #1e40af; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; text-align: justify; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.5rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.75rem; text-align: left; }
        .markdown-body th { background-color: #f1f5f9; }
        .markdown-body strong { color: #0f172a; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">

    <!-- Anda bisa menyesuaikan include sidebar yayasan Anda di sini -->
    <!-- <?php // include 'sidebar-yayasan.php'; ?> -->
    
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative w-full">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center">
                <a href="../admin.php" class="text-gray-500 hover:text-blue-600 mr-4" title="Kembali"><i class="fas fa-arrow-left text-xl"></i></a>
                <h2 class="font-bold text-gray-800">Ruang Yayasan | SOP & Peraturan</h2>
            </div>
            <div class="h-8 w-8 rounded-full bg-blue-800 flex items-center justify-center text-white font-bold shadow-sm"><i class="fas fa-building"></i></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-gavel text-blue-800 mr-2"></i>SOP & Tata Tertib Pegawai</h1>
                <p class="text-gray-500 mt-1">Buat, revisi, dan cetak peraturan standar operasional untuk setiap jabatan menggunakan AI.</p>
            </div>

            <?php if(isset($pesan_sukses)): ?>
                <div class="bg-emerald-100 text-emerald-800 px-4 py-3 rounded-lg mb-6 shadow-sm border border-emerald-200 flex items-center"><i class="fas fa-check-circle mr-2 text-xl"></i> <div><?= $pesan_sukses ?></div></div>
            <?php endif; ?>
            <?php if(isset($pesan_error)): ?>
                <div class="bg-rose-100 text-rose-800 px-4 py-3 rounded-lg mb-6 shadow-sm border border-rose-200 flex items-center"><i class="fas fa-exclamation-triangle mr-2 text-xl"></i> <div><?= $pesan_error ?></div></div>
            <?php endif; ?>

            <!-- KOTAK KENDALI GENERATOR -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Jabatan / Peran</label>
                        <input type="text" id="jabatan_input" list="daftar_jabatan" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Ketik atau pilih jabatan (Contoh: Kepala Asrama, Musyrif, Guru Umum...)">
                        <datalist id="daftar_jabatan">
                            <option value="Kepala Sekolah">
                            <option value="Kepala Asrama (Mudir)">
                            <option value="Ustadz / Guru Pengampu">
                            <option value="Musyrif Asrama">
                            <option value="Staf Administrasi & Keuangan">
                            <option value="Staf Dapur & Gizi">
                            <option value="Petugas Kebersihan (Cleaning Service)">
                            <option value="Satpam / Security">
                        </datalist>
                    </div>
                    <div>
                        <button onclick="generateAI()" id="btn-generate" class="w-full bg-blue-800 hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center justify-center">
                            <i class="fas fa-robot mr-2"></i> Rumuskan dengan AI
                        </button>
                    </div>
                </div>
            </div>

            <!-- DOKUMEN HASIL -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 flex flex-col min-h-[600px]">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-file-contract mr-2 text-blue-800"></i> Dokumen Peraturan</h3>
                    
                    <!-- 4 TOMBOL AKSI -->
                    <div id="action-buttons" class="hidden flex flex-wrap gap-2">
                        <button onclick="toggleEdit()" id="btn-edit" class="bg-amber-100 text-amber-800 hover:bg-amber-200 border border-amber-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-edit mr-1"></i> Edit Teks</button>
                        <button onclick="copyDoc()" id="btn-copy" class="bg-gray-100 text-gray-800 hover:bg-gray-200 border border-gray-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-copy mr-1"></i> Salin</button>
                        <button onclick="printDoc()" class="bg-indigo-100 text-indigo-800 hover:bg-indigo-200 border border-indigo-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-file-pdf mr-1"></i> Cetak PDF</button>
                        <form action="" method="POST" class="inline">
                            <input type="hidden" name="simpan_peraturan" value="1">
                            <input type="hidden" name="jabatan" id="form_jabatan_input">
                            <textarea name="konten" id="form_konten_input" class="hidden"></textarea>
                            <button type="submit" onclick="return sinkronisasiForm()" class="bg-emerald-600 text-white hover:bg-emerald-700 px-4 py-2 rounded-lg text-sm font-bold transition shadow-md"><i class="fas fa-save mr-1"></i> Save (Sahkan)</button>
                        </form>
                    </div>
                </div>
                
                <div class="p-8 flex-1 relative bg-white rounded-b-xl overflow-y-auto">
                    <!-- State Idle -->
                    <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-20 text-center">
                        <i class="fas fa-file-signature text-7xl mb-4 opacity-30 text-blue-800"></i>
                        <p class="text-lg">Ketik jabatan dan klik tombol "Rumuskan dengan AI"</p>
                        <p class="text-sm mt-2">AI akan menyusun aturan jam kerja, cuti, larangan, hingga sanksi secara otomatis.</p>
                    </div>
                    
                    <!-- State Loading -->
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-blue-800 py-20 text-center">
                        <i class="fas fa-spinner fa-spin text-5xl mb-4"></i>
                        <p class="font-bold text-lg animate-pulse">Menulis SOP & Tata Tertib Berdasarkan Hukum Ketenagakerjaan & Syariat...</p>
                    </div>

                    <!-- View Mode (Markdown Rendered) -->
                    <div id="view-mode" class="hidden markdown-body max-w-4xl mx-auto"></div>

                    <!-- Edit Mode (Raw Textarea) -->
                    <textarea id="edit-mode" class="hidden w-full h-[600px] p-4 border border-gray-300 rounded-lg font-mono text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Edit markdown di sini..."></textarea>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Mengambil referensi URL API di root directory
        const GAS_WEB_APP_URL = "../api-gemini.php"; 
        
        // Menampung data dari database jika mau melihat yang sudah ada
        const savedRules = <?= json_encode($saved_rules) ?>;
        
        // Auto-load jika jabatan dipilih dan sudah ada di database
        document.getElementById('jabatan_input').addEventListener('change', function() {
            const jabatan = this.value;
            if (savedRules[jabatan]) {
                tampilkanHasil(savedRules[jabatan]);
            }
        });

        function generateAI() {
            const jabatan = document.getElementById('jabatan_input').value.trim();
            if (!jabatan) { alert("Tentukan jabatan terlebih dahulu!"); return; }

            // Tampilan Loading
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('view-mode').classList.add('hidden');
            document.getElementById('edit-mode').classList.add('hidden');
            document.getElementById('action-buttons').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('btn-generate').disabled = true;

            const promptText = `Anda adalah HRD & Legal Konsultan Pesantren tingkat senior. Buatkan dokumen Peraturan Pegawai (SOP), Kewajiban, Larangan, dan Skema Sanksi yang tegas namun Islami khusus untuk posisi: **${jabatan}**. \n\nInstruksi Spesifik:\n1. Gunakan bahasa formal, rapi, dan mudah dipahami.\n2. Wajib mencakup Bab: Aturan Jam Kerja, Hak Libur Mingguan, Hak Cuti Tahunan, Izin Sakit, dan Cuti Keperluan Khusus.\n3. Buat dalam format Markdown yang sangat rapi (gunakan tabel jika perlu untuk skema sanksi).\n4. Di bagian paling akhir, WAJIB sertakan format Form Tanda Tangan Persetujuan (Pihak Yayasan di kiri & Pegawai yang bersangkutan di kanan).`;

            fetch(GAS_WEB_APP_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    leads: [{jenis_lead:"SYSTEM_COMMAND", sumber_info:promptText, status:"URGENT"}],
                    type: 'sop'
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === "success") {
                    tampilkanHasil(data.result);
                } else { throw new Error(data.message); }
            })
            .catch(error => {
                alert("Gagal merumuskan AI: " + error.message);
                document.getElementById('state-loading').classList.add('hidden');
                document.getElementById('state-idle').classList.remove('hidden');
            })
            .finally(() => { document.getElementById('btn-generate').disabled = false; });
        }

        function tampilkanHasil(markdownText) {
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-loading').classList.add('hidden');
            
            document.getElementById('edit-mode').value = markdownText;
            document.getElementById('view-mode').innerHTML = marked.parse(markdownText);
            
            document.getElementById('view-mode').classList.remove('hidden');
            document.getElementById('action-buttons').classList.remove('hidden');
            document.getElementById('action-buttons').classList.add('flex');
        }

        function toggleEdit() {
            const viewMode = document.getElementById('view-mode');
            const editMode = document.getElementById('edit-mode');
            const btn = document.getElementById('btn-edit');
            
            if (viewMode.classList.contains('hidden')) {
                // Switch to View Mode
                viewMode.innerHTML = marked.parse(editMode.value);
                editMode.classList.add('hidden');
                viewMode.classList.remove('hidden');
                btn.innerHTML = '<i class="fas fa-edit mr-1"></i> Edit Teks';
                btn.classList.replace('bg-emerald-100', 'bg-amber-100');
                btn.classList.replace('text-emerald-800', 'text-amber-800');
            } else {
                // Switch to Edit Mode
                viewMode.classList.add('hidden');
                editMode.classList.remove('hidden');
                btn.innerHTML = '<i class="fas fa-eye mr-1"></i> Preview';
                btn.classList.replace('bg-amber-100', 'bg-emerald-100');
                btn.classList.replace('text-amber-800', 'text-emerald-800');
            }
        }

        function copyDoc() {
            const text = document.getElementById('edit-mode').value;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('btn-copy');
                const ori = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-1 text-emerald-500"></i> Tersalin';
                setTimeout(() => btn.innerHTML = ori, 2000);
            });
        }

        function printDoc() {
            // Pastikan sedang di mode view agar yang dicetak HTML renderannya
            if (!document.getElementById('edit-mode').classList.contains('hidden')) toggleEdit();
            
            const content = document.getElementById('view-mode').innerHTML;
            const jabatan = document.getElementById('jabatan_input').value;
            const printWindow = window.open('', '_blank');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>SOP & Peraturan - ${jabatan}</title>
                    <style>
                        body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; padding: 40px; color: #000; }
                        h1 { text-align: center; text-transform: uppercase; font-size: 22px; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 30px; }
                        h2 { font-size: 18px; margin-top: 25px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
                        h3 { font-size: 16px; margin-top: 20px; }
                        p, li { font-size: 14px; text-align: justify; }
                        ul, ol { padding-left: 20px; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 13px; }
                        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .signatures { display: flex; justify-content: space-between; margin-top: 60px; page-break-inside: avoid; }
                        .sign-box { width: 250px; text-align: center; font-size: 14px; }
                        .sign-line { border-bottom: 1px solid #000; margin-top: 80px; margin-bottom: 5px; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `);
            printWindow.document.close();
            // Tunda print sebentar agar browser selesai merender HTML dan gaya CSS
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
        }

        function sinkronisasiForm() {
            document.getElementById('form_jabatan_input').value = document.getElementById('jabatan_input').value;
            document.getElementById('form_konten_input').value = document.getElementById('edit-mode').value;
            return true;
        }
    </script>
</body>
</html>