<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';
$active_menu = 'ai_rpp';

// Ambil data silabus untuk dropdown
$daftar_silabus = [];
$res_silabus = $conn->query("SELECT * FROM master_silabus ORDER BY mata_pelajaran ASC");
if ($res_silabus) while($r = $res_silabus->fetch_assoc()) $daftar_silabus[] = $r;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Generator RPP | Portal Ustadz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        .markdown-body h1, .markdown-body h2 { font-size: 1.5rem; font-weight: bold; color: #0891b2; margin-top: 1.5rem; margin-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem; }
        .markdown-body h3 { font-size: 1.25rem; font-weight: bold; color: #0e7490; margin-top: 1rem; margin-bottom: 0.5rem; }
        .markdown-body p { margin-bottom: 1rem; line-height: 1.6; color: #334155; }
        .markdown-body ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; color: #334155; }
        .markdown-body table { width: 100%; border-collapse: collapse; margin-top: 1rem; margin-bottom: 1.5rem; }
        .markdown-body th, .markdown-body td { border: 1px solid #cbd5e1; padding: 0.75rem; text-align: left; }
        .markdown-body th { background-color: #f1f5f9; }
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-magic text-cyan-600 mr-2"></i>AI Generator RPP</h1><p class="text-sm text-gray-500">Asisten pembuat Rencana Pelaksanaan Pembelajaran dalam hitungan detik.</p></div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mata Pelajaran</label>
                        <input type="text" id="rpp-mapel" list="daftar-mapel" onchange="pilihSilabus(this)" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Pilih dari daftar atau ketik baru...">
                        <datalist id="daftar-mapel">
                            <?php foreach($daftar_silabus as $s): ?>
                                <option value="<?= htmlspecialchars($s['mata_pelajaran']) ?>" 
                                        data-kelas="<?= htmlspecialchars($s['kelas']) ?>"
                                        data-deskripsi="<?= htmlspecialchars($s['deskripsi_mapel']) ?>"
                                        data-cp="<?= htmlspecialchars($s['capaian_pembelajaran']) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kelas / Jenjang</label>
                        <input type="text" id="rpp-kelas" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Otomatis terisi jika memilih mapel">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Topik Utama</label>
                        <input type="text" id="rpp-topik" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500" placeholder="Contoh: Peristiwa Hijrah Nabi / Bilangan Cacah">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembelajaran</label>
                        <select id="rpp-metode" class="w-full px-4 py-2 border rounded-lg focus:ring-cyan-500">
                            <option value="Interaktif (Diskusi & Tanya Jawab)">Interaktif</option>
                            <option value="Berbasis Siswa (Student-Centered)">Berbasis Siswa</option>
                            <option value="Praktek / Demonstrasi">Praktek</option>
                            <option value="Konvensional (Ceramah)">Konvensional</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button id="btn-generate" onclick="generateRPP()" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-6 rounded-lg transition shadow-md flex items-center group">
                        <i class="fas fa-brain mr-2 group-hover:animate-pulse"></i> Susun RPP dengan AI
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 min-h-[400px] flex flex-col overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 flex justify-between items-center">
                    <h3 class="font-bold text-gray-800"><i class="fas fa-file-alt mr-2"></i> Hasil Modul Ajar (RPP)</h3>
                    <div class="flex gap-2">
                        <button id="btn-copy" onclick="copyRPP()" class="hidden bg-emerald-100 text-emerald-700 hover:bg-emerald-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-emerald-200"><i class="fas fa-copy mr-1"></i> Copy Teks</button>
                        <button id="btn-print" onclick="cetakRPP()" class="hidden bg-indigo-100 text-indigo-700 hover:bg-indigo-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm border border-indigo-200"><i class="fas fa-print mr-1"></i> Cetak PDF / Print</button>
                    </div>
                </div>
                <div id="result-container" class="p-6 flex-1 overflow-y-auto relative">
                    <div id="state-idle" class="flex flex-col items-center justify-center h-full text-gray-400 py-16 text-center"><i class="fas fa-file-signature text-6xl mb-4 opacity-30"></i><p>Isi form di atas lalu klik tombol untuk menyusun RPP otomatis.</p></div>
                    <div id="state-loading" class="hidden flex flex-col items-center justify-center h-full text-cyan-600 py-16 text-center"><i class="fas fa-spinner fa-spin text-5xl mb-4"></i><p class="font-bold">Menganalisa silabus dan merancang kegiatan kelas...</p></div>
                    <div id="state-result" class="hidden markdown-body max-w-4xl mx-auto"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });
        
        const GAS_URL = "api-gemini.php";

        function pilihSilabus(input) {
            const val = input.value;
            const opts = document.getElementById('daftar-mapel').options;
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value === val) {
                    document.getElementById('rpp-kelas').value = opts[i].getAttribute('data-kelas');
                    return;
                }
            }
            // Jika tidak ada di daftar, kosongkan kelas
            document.getElementById('rpp-kelas').value = '';
        }

        function generateRPP() {
            const mapelInput = document.getElementById('rpp-mapel');
            const mapel = mapelInput.value;
            const kelas = document.getElementById('rpp-kelas').value;
            const topik = document.getElementById('rpp-topik').value;
            const metode = document.getElementById('rpp-metode').value;

            if(!mapel || !kelas || !topik || !metode) { alert("Lengkapi semua isian form!"); return; }

            let prompt;
            let selectedOption = null;
            const opts = document.getElementById('daftar-mapel').options;
            for (let i = 0; i < opts.length; i++) {
                if (opts[i].value === mapel) {
                    selectedOption = opts[i];
                    break;
                }
            }

            if (selectedOption) {
                // --- MODE SILABUS (LEBIH AKURAT) ---
                const deskripsiMapel = selectedOption.getAttribute('data-deskripsi');
                const capaianPembelajaran = selectedOption.getAttribute('data-cp');
                let cpFormatted = capaianPembelajaran;
                try {
                    const parsedCP = JSON.parse(capaianPembelajaran);
                    if (Array.isArray(parsedCP)) {
                        cpFormatted = parsedCP.map(item => `- **Elemen ${item.elemen}:** ${item.cp}`).join('\n');
                    }
                } catch (e) { /* Biarkan raw string */ }

                prompt = `Anda adalah asisten ahli kurikulum untuk Pesantren Villa Quran. Buatlah Modul Ajar berformat Kurikulum Merdeka yang menarik dan modern berdasarkan konteks berikut:
- **Mata Pelajaran:** ${mapel}
- **Fase / Kelas:** ${kelas}
- **Deskripsi Umum Mapel:** ${deskripsiMapel}
- **Capaian Pembelajaran (CP):**\n${cpFormatted}
- **Topik / Materi Pokok:** ${topik}
- **Metode Pembelajaran:** ${metode}

Struktur Modul Ajar wajib dalam format Markdown yang rapi: 
1. **Informasi Umum** (Identitas, Kompetensi Awal, Profil Pelajar Pancasila / Santri, Sarana & Prasarana). 
2. **Komponen Inti**: 
   - **Tujuan Pembelajaran** (Spesifik diturunkan dari Elemen CP dan Topik). 
   - **Pemahaman Bermakna** & **Pertanyaan Pemantik**. 
   - **Kegiatan Pembelajaran**: Pendahuluan (Ice breaking dll), Inti (Eksplorasi Materi dengan metode ${metode}), Penutup (Refleksi). 
3. **Asesmen / Evaluasi** (Bentuk penilaian singkat untuk mengukur ketercapaian tujuan).
4. **Lampiran: Lembar Kerja Siswa (LKS)** (Buat LKS sederhana dan interaktif terkait topik).
5. **Lampiran: Soal Ulangan** (Buat 5 soal pilihan ganda beserta kunci jawabannya terkait topik).`;

            } else {
                // --- MODE UMUM (TANPA SILABUS) ---
                prompt = `Anda adalah asisten ahli kurikulum. Buatlah Modul Ajar berformat Kurikulum Merdeka yang menarik dan modern untuk:
- **Mata Pelajaran:** ${mapel}
- **Fase / Kelas:** ${kelas}
- **Topik / Materi Pokok:** ${topik}
- **Metode Pembelajaran:** ${metode}

Gunakan pengetahuan umum Anda tentang Kurikulum Merdeka untuk menyusunnya. Struktur Modul Ajar wajib dalam format Markdown yang rapi: 1. **Informasi Umum** (Identitas, Kompetensi Awal, Profil Pelajar Pancasila, Sarana & Prasarana). 2. **Komponen Inti**: - **Tujuan Pembelajaran**. - **Pemahaman Bermakna** & **Pertanyaan Pemantik**. - **Kegiatan Pembelajaran**: Pendahuluan, Inti (Gunakan metode ${metode}), Penutup. 3. **Asesmen / Evaluasi**. 4. **Lampiran: Lembar Kerja Siswa (LKS)** (Buat LKS sederhana dan interaktif terkait topik). 5. **Lampiran: Soal Ulangan** (Buat 5 soal pilihan ganda beserta kunci jawabannya terkait topik).`;
            }

            document.getElementById('state-idle').classList.add('hidden');
            document.getElementById('state-result').classList.add('hidden');
            document.getElementById('state-loading').classList.remove('hidden');
            document.getElementById('btn-generate').disabled = true;

            fetch(GAS_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'text/plain;charset=utf-8' },
                body: JSON.stringify({ leads: [{jenis_lead:"SYSTEM_COMMAND", sumber_info:prompt, status:"URGENT"}], type: 'rpp' })
            })
            .then(res => res.json())
            .then(data => {
                if(data.status === "success") {
                    document.getElementById('state-result').innerHTML = marked.parse(data.result);
                    document.getElementById('state-loading').classList.add('hidden');
                    document.getElementById('state-result').classList.remove('hidden');
                    document.getElementById('btn-print').classList.remove('hidden');
                    document.getElementById('btn-copy').classList.remove('hidden');

                    // Kirim log aktivitas ke server
                    const logData = new FormData();
                    logData.append('action', 'log_ai_activity');
                    logData.append('fitur', 'AI Generator RPP');
                    logData.append('detail', `Mapel: ${mapel}, Topik: ${topik}`);
                    fetch('ajax-handler.php', { method: 'POST', body: logData })
                        .catch(err => console.error('Gagal mengirim log AI', err));

                } else throw new Error(data.message);
            })
            .catch(err => { alert("Error AI: " + err.message); document.getElementById('state-loading').classList.add('hidden'); document.getElementById('state-idle').classList.remove('hidden'); })
            .finally(() => document.getElementById('btn-generate').disabled = false);
        }

        function copyRPP() {
            const text = document.getElementById('state-result').innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('btn-copy');
                const original = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Tersalin!';
                setTimeout(() => btn.innerHTML = original, 2000);
            });
        }

        function cetakRPP() {
            const content = document.getElementById('state-result').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Cetak Modul Ajar (RPP)</title>
                    <style>
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #333; line-height: 1.6; }
                        h1, h2, h3 { color: #0f172a; margin-top: 20px; margin-bottom: 10px; }
                        h1 { font-size: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; text-align: center; }
                        h2 { font-size: 20px; color: #0891b2; }
                        h3 { font-size: 16px; color: #0e7490; }
                        p { margin-bottom: 10px; }
                        ul, ol { margin-bottom: 15px; padding-left: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 20px; }
                        th, td { border: 1px solid #cbd5e1; padding: 10px; text-align: left; }
                        th { background-color: #f8fafc; font-weight: bold; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            // Jeda 0.5 detik agar browser merender HTML sebelum membuka jendela Print
            setTimeout(() => { printWindow.print(); printWindow.close(); }, 500);
        }
    </script>
</body>
</html>