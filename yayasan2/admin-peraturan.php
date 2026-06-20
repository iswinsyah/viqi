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

$active_menu = 'admin_peraturan';
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js"></script>
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

    <?php include 'sidebar.php'; ?>

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
                    <div id="action-buttons" class="flex flex-wrap gap-2 opacity-40 pointer-events-none">
                        <button type="button" onclick="toggleEdit()" id="btn-edit" disabled class="bg-amber-100 text-amber-800 hover:bg-amber-200 border border-amber-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-edit mr-1"></i> Edit Teks</button>
                        <button type="button" onclick="copyDoc()" id="btn-copy" disabled class="bg-gray-100 text-gray-800 hover:bg-gray-200 border border-gray-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-copy mr-1"></i> Salin</button>
                        <button type="button" onclick="printDoc()" id="btn-print" disabled class="bg-indigo-100 text-indigo-800 hover:bg-indigo-200 border border-indigo-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-file-pdf mr-1"></i> Cetak PDF</button>
                        <button type="button" onclick="downloadPDF()" id="btn-download" disabled class="bg-sky-100 text-sky-800 hover:bg-sky-200 border border-sky-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-download mr-1"></i> Download PDF</button>
                        <button type="button" onclick="resetDoc()" id="btn-reset" disabled class="bg-white text-gray-800 hover:bg-gray-100 border border-gray-300 px-3 py-2 rounded-lg text-sm font-bold transition shadow-sm"><i class="fas fa-undo mr-1"></i> Reset</button>
                        <form action="" method="POST" class="inline">
                            <input type="hidden" name="simpan_peraturan" value="1">
                            <input type="hidden" name="jabatan" id="form_jabatan_input">
                            <textarea name="konten" id="form_konten_input" class="hidden"></textarea>
                            <button type="submit" id="btn-save" disabled onclick="return sinkronisasiForm()" class="bg-emerald-600 text-white hover:bg-emerald-700 px-4 py-2 rounded-lg text-sm font-bold transition shadow-md"><i class="fas fa-save mr-1"></i> Save (Sahkan)</button>
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
        const GAS_WEB_APP_URL = "../api-ai.php"; 
        
        // Menampung data dari database jika mau melihat yang sudah ada
        const savedRules = <?= json_encode($saved_rules) ?>;
        let currentMarkdown = '';
        
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

            // Tampilan Loading (tombol aksi tetap terlihat namun dinonaktifkan)
            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('view-mode').classList.add('hidden');
            document.getElementById('edit-mode').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            // pastikan tombol tetap non-aktif selama proses
            document.getElementById('btn-edit').disabled = true;
            document.getElementById('btn-copy').disabled = true;
            document.getElementById('btn-print').disabled = true;
            document.getElementById('btn-download').disabled = true;
            document.getElementById('btn-reset').disabled = true;
            document.getElementById('btn-save').disabled = true;
            document.getElementById('btn-generate').disabled = true;

            const promptText = `Anda adalah HRD & Legal Konsultan Pesantren tingkat senior. Susun dokumen SOP & Peraturan Pegawai (format Markdown) yang SPECIFIC dan SESUAI dengan salah satu dari 3 model akad pegawai di sekolah ini, lalu sesuaikan isinya berdasarkan jabatan yang dimasukkan: \n\nModel akad dan contoh jabatan (pilih sesuai jabatan yang diketik):\n1) Yayasan Mukim — pegawai tinggal di asrama, ada jam kerja dan jam siaga. Jam kerja: 07:00–13:00 (tanpa jeda istirahat). Jam siaga: 13:00–06:59 esok hari; selama jam siaga tetap melayani santri namun boleh mengurus kepentingan pribadi (keluar asrama, istirahat, dsb). Contoh: Musyrif, Musyrifah, Kepala Asrama.\n2) Yayasan Tidak Mukim — pegawai pulang setelah jam kerja, TIDAK ada jam siaga. Contoh: Kepala Sekolah, Sekretaris Sekolah, Bendahara Sekolah, Admin Sekolah. (Pegawai digaji penuh).\n3) Honorer / Ustadz (tamu) — jam kerja hanya saat mengajar; setelah mengajar pulang. Bayaran berdasarkan jam hadir (sistem per-jam). Contoh: Ustadz, Ustadzah.\n\nInstruksi output (WAJIB ditaati):\n- Awali dengan ringkasan kebijakan 1 paragraf yang menjelaskan model akad yang relevan untuk jabatan tersebut.\n- Susun Bab terpisah: Definisi & Ruang Lingkup; Jam Kerja & Jam Siaga (jika ada); Tugas Utama; Hak & Kewajiban; Mekanisme Absensi; Sistem Penggajian / Kompensasi; Hak Cuti & Izin; Prosedur Penggantian Tugas; Skema Sanksi (tabel jika perlu); Lampiran: Form Tanda Tangan Persetujuan.\n- Untuk model Yayasan Mukim: sertakan klausul detail tentang pola jam siaga, tanggung jawab pada jam siaga, tata cara izinnya (keluar asrama), dan hak istirahat serta kompensasi jika ada lembur/penugasan malam.\n- Untuk model Yayasan Tidak Mukim: sebutkan bahwa tidak ada jam siaga, aturan pulang, kewajiban laporan, dan mekanisme cuti/penggantian tugas.\n- Untuk model Honorer/Ustadz: jelaskan perhitungan honor per-jam, absensi per-kuliah, dan syarat pembayaran (mis. billing bulanan).\n- Gunakan bahasa formal, jelas, dan menjaga adab serta akhlak syariah. Beri contoh konkret (mis. tabel jadwal kerja + tabel sanksi) bila relevan.\n- Akhiri dengan bagian Form Tanda Tangan (Yayasan kiri, Pegawai kanan) dan ringkasan tindakan bila terjadi pelanggaran berat.\n\nKeluaran harus berupa satu string Markdown siap render (tanpa metadata tambahan). Fokus utamanya: menyesuaikan setiap pasal dengan model akad yang sesuai untuk jabatan yang dimasukkan: **${jabatan}**.`;

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
            document.getElementById('action-buttons').classList.remove('opacity-40', 'pointer-events-none');
            document.getElementById('btn-edit').disabled = false;
            document.getElementById('btn-copy').disabled = false;
            document.getElementById('btn-print').disabled = false;
            document.getElementById('btn-download').disabled = false;
            document.getElementById('btn-reset').disabled = false;
            document.getElementById('btn-save').disabled = false;
            currentMarkdown = markdownText;
        }

        function downloadPDF() {
            if (!document.getElementById('edit-mode').classList.contains('hidden')) toggleEdit();
            const jabatan = document.getElementById('jabatan_input').value.trim() || 'peraturan';
            const filename = jabatan.replace(/[^a-zA-Z0-9-_]/g, '_').toLowerCase() + '_peraturan.pdf';
            const element = document.createElement('div');
            element.style.padding = '24px';
            element.style.fontFamily = 'Arial, sans-serif';
            element.innerHTML = `
                <h1 style="text-align:center; font-size:20px; margin-bottom:16px;">SOP & Peraturan - ${jabatan}</h1>
                ${document.getElementById('view-mode').innerHTML}
            `;

            const opt = {
                margin: 0.5,
                filename: filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }

        function resetDoc() {
            if (!currentMarkdown) return;
            document.getElementById('edit-mode').value = currentMarkdown;
            document.getElementById('view-mode').innerHTML = marked.parse(currentMarkdown);
            document.getElementById('view-mode').classList.remove('hidden');
            document.getElementById('edit-mode').classList.add('hidden');
            const btn = document.getElementById('btn-edit');
            btn.innerHTML = '<i class="fas fa-edit mr-1"></i> Edit Teks';
            btn.classList.remove('bg-emerald-100', 'text-emerald-800');
            btn.classList.add('bg-amber-100', 'text-amber-800');
            showToast('Dokumen berhasil di-reset ke versi AI terakhir.');
        }

        function showToast(message) {
            let toast = document.getElementById('toast-message');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast-message';
                toast.className = 'fixed bottom-6 right-6 bg-slate-900 text-white px-4 py-3 rounded-xl shadow-xl ring-1 ring-slate-700/30 opacity-0 transition duration-300';
                document.body.appendChild(toast);
            }
            toast.textContent = message;
            toast.classList.remove('opacity-0');
            toast.classList.add('opacity-100');
            clearTimeout(window.toastTimer);
            window.toastTimer = setTimeout(() => {
                toast.classList.remove('opacity-100');
                toast.classList.add('opacity-0');
            }, 2400);
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