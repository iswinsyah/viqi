<?php
require_once 'auth-santri.php';
require_once 'koneksi.php';

$santri_id = $_SESSION['santri_id'];
$santri_nama = $_SESSION['santri_nama'];
$active_menu = 'dashboard_santri';

// Tangkap nama mata pelajaran (Default: Sosiologi)
$mapel = $_GET['mapel'] ?? 'Sosiologi';
$bab_id = (int)($_GET['bab'] ?? 1);

// Data Profil Santri
$res_s = $conn->query("SELECT * FROM buku_induk_santri WHERE id = $santri_id LIMIT 1");
$data_santri = ($res_s && $res_s->num_rows > 0) ? $res_s->fetch_assoc() : null;
$kelas_santri = $data_santri['kelas_sekarang'] ?? 'Kelas 10 SMA IPS';

// Kurikulum & Modul Sosiologi (Prototype E-Learning SADIGS)
$kurikulum_sosiologi = [
    1 => [
        'id' => 1,
        'judul' => 'Bab 1: Sosiologi Sebagai Ilmu & Objek Kajian',
        'subjudul' => 'Hakikat, Objek, dan Ciri-Ciri Utama Ilmu Sosiologi',
        'video_url' => 'https://www.youtube.com/embed/5v6kS6uHkPQ', // Video Edukasi Sosiologi Dasar
        'durasi' => '12 Menit',
        'ringkasan' => [
            'Pengertian Dasar' => 'Sosiologi berasal dari bahasa Latin <i>Socius</i> (kawan/masyarakat) dan bahasa Yunani <i>Logos</i> (ilmu/bicara). Pertama kali dicetuskan oleh <b>Auguste Comte</b> (Bapak Sosiologi Dunia).',
            '4 Ciri Utama Sosiologi' => [
                '<b>Empiris:</b> Didasarkan pada observasi kenyataan dan akal sehat, bukan spekulasi.',
                '<b>Teoritis:</b> Menyusun abstraksi dari hasil pengamatan untuk menjelaskan hubungan sebab-akibat.',
                '<b>Kumulatif:</b> Teori dibangun atas dasar teori yang sudah ada sebelumnya, kemudian diperbaiki dan diperluas.',
                '<b>Non-Etis:</b> Tidak mempersoalkan baik atau buruknya suatu fakta sosial, melainkan menjelaskan fakta tersebut secara analitis dan objektif.'
            ],
            'Objek Kajian' => 'Masyarakat yang mencakup hubungan antarmanusia, proses interaksi sosial, serta gejala dan perubahan sosial dalam kehidupan bersama.'
        ],
        'lks' => [
            'judul' => 'LKS 1: Analisis Gejala Sosial di Lingkungan Pondok/Sekolah',
            'tugas' => 'Amati satu fenomena sosial di sekitarmu (misal: tradisi gotong royong/ro\'an, antrean makan, atau interaksi santri baru). Jelaskan mengapa fenomena tersebut memenuhi ciri <b>Empiris</b> dan <b>Non-Etis</b> dalam Sosiologi!'
        ],
        'kuis' => [
            [
                'soal' => 'Siapakah tokoh yang pertama kali memperkenalkan istilah Sosiologi dan dikenal sebagai Bapak Sosiologi Dunia?',
                'opsi' => ['Emile Durkheim', 'Auguste Comte', 'Max Weber', 'Karl Marx'],
                'jawaban' => 1,
                'pembahasan' => 'Auguste Comte adalah tokoh asal Prancis yang pertama kali menggunakan istilah Sosiologi pada bukunya Cours de Philosophie Positive (1838).'
            ],
            [
                'soal' => 'Sosiologi tidak menilai apakah suatu tindakan kriminal itu secara moral baik atau buruk, melainkan menjelaskan penyebab dan dampak sosialnya. Hal ini mencerminkan ciri...',
                'opsi' => ['Empiris', 'Teoritis', 'Kumulatif', 'Non-Etis'],
                'jawaban' => 3,
                'pembahasan' => 'Ciri Non-Etis berarti sosiologi bertugas mengkaji fakta apa adanya tanpa menghakimi status moral baik atau buruk.'
            ],
            [
                'soal' => 'Teori sosiologi saat ini menyempurnakan teori-teori klasik terdahulu sesuai perkembangan zaman modern. Karakteristik ini disebut...',
                'opsi' => ['Kumulatif', 'Spekulatif', 'Normatif', 'Empiris'],
                'jawaban' => 0,
                'pembahasan' => 'Kumulatif berarti teori sosiologi saling melengkapi, memperluas, dan memperbaiki teori yang sudah ada sebelumnya.'
            ]
        ]
    ],
    2 => [
        'id' => 2,
        'judul' => 'Bab 2: Interaksi Sosial & Dinamika Kelompok',
        'subjudul' => 'Syarat, Bentuk Asosiatif, dan Disosiatif Interaksi Sosial',
        'video_url' => 'https://www.youtube.com/embed/n33wY8GjSGo',
        'durasi' => '15 Menit',
        'ringkasan' => [
            '2 Syarat Interaksi Sosial' => [
                '<b>Kontak Sosial:</b> Hubungan awal antar individu/kelompok (Primer: tatap muka; Sekunder: melalui perantara HP/surat).',
                '<b>Komunikasi:</b> Proses penyampaian pesan dari komunikator ke komunikan disertai penafsiran makna.'
            ],
            'Bentuk Interaksi Asosiatif (Menyatukan)' => [
                '<b>Kerjasama (Cooperation):</b> Usaha bersama mencapai tujuan bersama.',
                '<b>Akomodasi:</b> Upaya meredakan konflik (Mediasi, Kompromi, Arbitrase, Konsiliasi).',
                '<b>Asimilasi:</b> Peleburan dua kebudayaan menjadi kebudayaan baru tanpa sisa kebudayaan lama.',
                '<b>Akulturasi:</b> Perpaduan dua kebudayaan tanpa menghilangkan identitas kebudayaan asli.'
            ],
            'Bentuk Interaksi Disosiatif (Memisahkan)' => [
                '<b>Persaingan (Kompetisi):</b> Berlomba mencapai tujuan yang terbatas tanpa kekerasan.',
                '<b>Kontravensi:</b> Sikap tersembunyi seperti rasa ragu, dengki, atau penolakan terselubung.',
                '<b>Pertentangan (Konflik):</b> Usaha mencapai tujuan dengan cara menentang pihak lawan disertai ancaman/kekerasan.'
            ]
        ],
        'lks' => [
            'judul' => 'LKS 2: Studi Kasus Penyelesaian Perselisihan Santri',
            'tugas' => 'Jelaskan perbedaan antara <b>Mediasi</b> (dengan bantuan penengah yang netral) dan <b>Arbitrase</b> (penengah memiliki wewenang memutuskan) saat terjadi perbedaan pendapat di asrama!'
        ],
        'kuis' => [
            [
                'soal' => 'Dua syarat mutlak terjadinya interaksi sosial menurut Sosiologi adalah...',
                'opsi' => ['Kontak sosial dan komunikasi', 'Kerjasama dan simpati', 'Imitasi dan identifikasi', 'Status dan peranan'],
                'jawaban' => 0,
                'pembahasan' => 'Tanpa kontak sosial dan proses komunikasi timbal balik, interaksi sosial tidak dapat terwujud.'
            ],
            [
                'soal' => 'Perpaduan antara musik gambus Arab dengan instrumen modern Indonesia tanpa menghilangkan ciri khas aslinya merupakan contoh dari...',
                'opsi' => ['Asimilasi', 'Akulturasi', 'Kontravensi', 'Akomodasi'],
                'jawaban' => 1,
                'pembahasan' => 'Akulturasi adalah percampuran budaya yang tidak menghilangkan unsur kebudayaan aslinya.'
            ]
        ]
    ],
    3 => [
        'id' => 3,
        'judul' => 'Bab 3: Nilai, Norma, & Keteraturan Sosial',
        'subjudul' => 'Tingkatan Norma: Cara, Kebiasaan, Tata Kelakuan, dan Adat Istiadat',
        'video_url' => 'https://www.youtube.com/embed/6XvM28P4K8o',
        'durasi' => '10 Menit',
        'ringkasan' => [
            'Pengertian Nilai & Norma' => 'Nilai adalah konsepsi tentang apa yang dianggap baik dan berharga oleh masyarakat. Norma adalah aturan konkret atau pedoman bertingkah laku yang disertai sanksi.',
            '4 Tingkatan Norma Berdasarkan Kekuatan Mengikatnya' => [
                '<b>1. Cara (Usage):</b> Penyimpangan hanya mendapat celaan ringan (misal: bersendawa saat makan).',
                '<b>2. Kebiasaan (Folkways):</b> Perbuatan yang diulang-ulang karena disukai (misal: mencium tangan orang tua/guru).',
                '<b>3. Tata Kelakuan (Mores):</b> Norma yang menjadi pengatur perbuatan moral anggota masyarakat (misal: larangan berbohong/mencuri).',
                '<b>4. Adat Istiadat (Custom):</b> Aturan turun-temurun dengan sanksi adat yang sangat tegas dan berat.'
            ]
        ],
        'lks' => [
            'judul' => 'LKS 3: Klasifikasi Tata Tertib Pesantren',
            'tugas' => 'Tuliskan 3 contoh aturan di lingkungan pesantren dan klasifikasikan ke dalam tingkatan norma: Usage, Folkways, atau Mores!'
        ],
        'kuis' => [
            [
                'soal' => 'Sanksi terhadap pelanggaran norma cara (usage) umumnya berupa...',
                'opsi' => ['Hukuman penjara', 'Teguran atau celaan ringan', 'Pengusiran dari masyarakat', 'Denda materiil'],
                'jawaban' => 1,
                'pembahasan' => 'Usage memiliki daya ikat paling lemah sehingga sanksinya hanya berupa teguran, cemoohan, atau celaan ringan.'
            ]
        ]
    ]
];

// Pastikan bab yang dipilih valid
if (!isset($kurikulum_sosiologi[$bab_id])) {
    $bab_id = 1;
}
$materi_aktif = $kurikulum_sosiologi[$bab_id];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-Learning Sosiologi | SADIGS 4.0</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .hide-scrollbar::-webkit-scrollbar { display: none; }
        .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .theme-teal { background-color: #0d8276; }
        .theme-bg-mint { background-color: #e1f5f2; }
    </style>
</head>
<body class="bg-[#e1f5f2] font-sans antialiased text-slate-800 flex h-screen overflow-hidden">
    
    <?php include 'sidebar-santri.php'; ?>

    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER KELAS SOSIOLOGI -->
        <header class="h-16 bg-[#0d8276] text-white shadow-md flex items-center justify-between px-4 sm:px-6 z-10 flex-shrink-0 no-print">
            <div class="flex items-center space-x-3">
                <a href="ruang-santri.php" class="w-9 h-9 rounded-xl bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex items-center space-x-2">
                    <div class="w-9 h-9 rounded-xl bg-white text-[#0d8276] flex items-center justify-center text-lg font-black shadow-inner">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h1 class="font-black text-sm sm:text-base leading-tight">E-Learning Sosiologi</h1>
                        <p class="text-[10px] text-teal-100"><?= htmlspecialchars($kelas_santri) ?> • Mandiri & Terbimbing AI</p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <button onclick="bukaUstadzAI()" class="bg-amber-400 hover:bg-amber-300 text-teal-950 font-black px-3.5 py-1.5 rounded-full text-xs shadow-md transition flex items-center gap-1.5 animate-pulse">
                    <i class="fas fa-robot"></i>
                    <span class="hidden sm:inline">Tanya</span> Ustadz AI
                </button>
            </div>
        </header>

        <!-- MAIN SCROLLABLE CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-[#e1f5f2] p-3.5 sm:p-6 pb-24 md:pb-8">
            <div class="max-w-4xl mx-auto">
                
                <!-- 1. DAFTAR PILIHAN BAB / MODUL BELAJAR (PILL TABS) -->
                <div class="mb-5 overflow-x-auto hide-scrollbar flex items-center gap-2 pb-1">
                    <?php foreach ($kurikulum_sosiologi as $b): ?>
                    <a href="santri-belajar.php?mapel=Sosiologi&bab=<?= $b['id'] ?>" 
                       class="whitespace-nowrap px-4 py-2.5 rounded-2xl text-xs font-extrabold transition-all flex items-center gap-2 <?= ($bab_id == $b['id']) ? 'bg-[#0d8276] text-white shadow-md shadow-teal-900/10 scale-100' : 'bg-white text-slate-700 hover:bg-teal-50 border border-teal-100/80' ?>">
                        <span class="w-5 h-5 rounded-full <?= ($bab_id == $b['id']) ? 'bg-white/20 text-white' : 'bg-teal-100 text-[#0d8276]' ?> flex items-center justify-center text-[10px]">
                            <?= $b['id'] ?>
                        </span>
                        <span><?= explode(':', $b['judul'])[0] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- 2. BANNER JUDUL BAB AKTIF -->
                <div class="bg-white rounded-3xl p-5 sm:p-6 border border-teal-100 shadow-sm mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4 mb-4">
                        <div>
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider bg-teal-50 text-[#0d8276] border border-teal-100">Modul Pembelajaran Aktif</span>
                            <h2 class="text-base sm:text-xl font-black text-slate-900 mt-1.5"><?= htmlspecialchars($materi_aktif['judul']) ?></h2>
                            <p class="text-xs text-slate-500 mt-0.5"><?= htmlspecialchars($materi_aktif['subjudul']) ?></p>
                        </div>
                        <div class="flex items-center gap-2 self-start sm:self-auto">
                            <span class="text-xs text-slate-500 font-semibold bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100 flex items-center gap-1.5">
                                <i class="far fa-clock text-[#0d8276]"></i> <?= $materi_aktif['durasi'] ?>
                            </span>
                        </div>
                    </div>

                    <!-- 3. VIDEO EMBED YOUTUBE PEMBELAJARAN -->
                    <div class="mb-6">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-play-circle text-rose-500 text-sm"></i> 1. Video Penjelasan Materi
                        </h3>
                        <div class="relative w-full overflow-hidden rounded-2xl bg-slate-900 shadow-lg" style="padding-top: 56.25%;">
                            <iframe class="absolute top-0 left-0 w-full h-full" 
                                    src="<?= $materi_aktif['video_url'] ?>" 
                                    title="Video Pembelajaran Sosiologi" 
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                                    allowfullscreen>
                            </iframe>
                        </div>
                    </div>

                    <!-- 4. MODUL RANGKUMAN BACAAN MATERI -->
                    <div class="mb-6">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-book-open text-[#0d8276] text-sm"></i> 2. Rangkuman Konsep Penting
                        </h3>
                        <div class="bg-[#e1f5f2]/40 rounded-2xl p-4 sm:p-5 border border-teal-100/80 space-y-3.5 text-xs sm:text-sm text-slate-700 leading-relaxed">
                            <?php foreach ($materi_aktif['ringkasan'] as $header => $isi): ?>
                                <div class="bg-white p-3.5 rounded-xl border border-teal-50 shadow-2xs">
                                    <h4 class="font-extrabold text-[#0d8276] mb-1.5 text-xs sm:text-sm flex items-center gap-1.5">
                                        <i class="fas fa-check-circle text-teal-500 text-xs"></i> <?= $header ?>
                                    </h4>
                                    <?php if (is_array($isi)): ?>
                                        <ul class="list-disc list-inside space-y-1 text-slate-600 pl-1 text-xs sm:text-[13px]">
                                            <?php foreach ($isi as $item): ?>
                                                <li><?= $item ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-slate-600 text-xs sm:text-[13px]"><?= $isi ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- 5. LEMBAR KERJA SISWA (LKS & TUGAS MANDIRI) -->
                    <div class="mb-6">
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-pencil-alt text-amber-500 text-sm"></i> 3. Lembar Kerja Siswa (LKS)
                        </h3>
                        <div class="bg-amber-50/60 rounded-2xl p-4 sm:p-5 border border-amber-100">
                            <h4 class="font-extrabold text-amber-900 text-xs sm:text-sm mb-1"><?= $materi_aktif['lks']['judul'] ?></h4>
                            <p class="text-xs text-amber-800 leading-relaxed"><?= $materi_aktif['lks']['tugas'] ?></p>
                            
                            <div class="mt-4 pt-3 border-t border-amber-200/60 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <span class="text-[11px] text-amber-700 italic">Kerjakan di buku tulis sosiologi atau ketik langsung di chat Ustadz AI untuk dikoreksi!</span>
                                <button onclick="konsultasiLksKeAI()" class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 rounded-xl text-xs transition shadow-sm self-start sm:self-auto flex items-center gap-1.5">
                                    <i class="fas fa-magic"></i> Diskusikan Jawaban ke AI
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 6. KUIS LATIHAN SOAL INTERAKTIF DENGAN AUTO-SCORE -->
                    <div>
                        <h3 class="text-xs font-black uppercase tracking-wider text-slate-700 mb-2 flex items-center gap-2">
                            <i class="fas fa-question-circle text-indigo-500 text-sm"></i> 4. Kuis Latihan Pemahaman
                        </h3>
                        
                        <div class="bg-indigo-50/40 rounded-2xl p-4 sm:p-5 border border-indigo-100 space-y-4" id="quizContainer">
                            <?php foreach ($materi_aktif['kuis'] as $qIdx => $q): ?>
                            <div class="bg-white p-4 rounded-xl border border-indigo-100/80 shadow-2xs" id="question_box_<?= $qIdx ?>">
                                <p class="font-bold text-slate-900 text-xs sm:text-sm mb-3">
                                    <span class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 inline-flex items-center justify-center text-xs mr-1.5"><?= $qIdx + 1 ?></span>
                                    <?= htmlspecialchars($q['soal']) ?>
                                </p>
                                <div class="space-y-2">
                                    <?php foreach ($q['opsi'] as $oIdx => $opsi): ?>
                                    <label class="flex items-center p-2.5 rounded-xl border border-slate-200 hover:bg-indigo-50/50 cursor-pointer transition text-xs text-slate-700 font-medium">
                                        <input type="radio" name="quiz_<?= $qIdx ?>" value="<?= $oIdx ?>" class="w-4 h-4 text-[#0d8276] focus:ring-teal-500 mr-2.5" onchange="cekJawaban(<?= $qIdx ?>, <?= $oIdx ?>, <?= $q['jawaban'] ?>, '<?= addslashes($q['pembahasan']) ?>')">
                                        <span><?= htmlspecialchars($opsi) ?></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <div id="feedback_<?= $qIdx ?>" class="hidden mt-3 p-3 rounded-xl text-xs font-semibold"></div>
                            </div>
                            <?php endforeach; ?>

                            <div id="quizResultSummary" class="hidden bg-white p-4 rounded-xl border border-teal-200 text-center">
                                <h4 class="font-black text-sm text-slate-900">Hasil Latihan Kuis Selesai! 🎉</h4>
                                <p class="text-xs text-slate-500 mt-1" id="quizScoreText">Skor: 100/100</p>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    <!-- ================================================== -->
    <!-- MODAL USTADZ AI SOSIOLOGI (INTERAKTIF CHAT + SUARA)-->
    <!-- ================================================== -->
    <div id="aiModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-end sm:items-center justify-center p-0 sm:p-4 transition-opacity">
        <div class="bg-white w-full max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl border border-teal-100 flex flex-col h-[85vh] sm:h-[600px] overflow-hidden animate-in slide-in-from-bottom duration-300">
            
            <!-- Modal Header -->
            <div class="bg-[#0d8276] text-white p-4 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-400 text-teal-950 flex items-center justify-center text-lg font-black shadow-md">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-sm leading-tight flex items-center gap-1.5">
                            Ustadz AI Sosiologi
                            <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        </h3>
                        <p class="text-[10px] text-teal-100"><?= $materi_aktif['judul'] ?></p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-1">
                    <button onclick="toggleSound()" id="soundToggleBtn" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition" title="Suara AI Aktif">
                        <i class="fas fa-volume-up text-xs"></i>
                    </button>
                    <button onclick="tutupUstadzAI()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            </div>

            <!-- Chat Messages Body -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-[#e1f5f2]/30" id="chatContainer">
                <!-- Welcome Message from AI -->
                <div class="flex items-start gap-2.5">
                    <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-teal-100 shadow-2xs text-xs text-slate-800 max-w-[85%] leading-relaxed">
                        <p class="font-bold text-[#0d8276] mb-1">Ahlan Wa Sahlan, <?= htmlspecialchars($santri_nama) ?>! 🌸</p>
                        <p>Saya <b>Ustadz AI Pembimbing Sosiologi</b>. Saya siap membantumu memahami materi <b><?= htmlspecialchars($materi_aktif['judul']) ?></b>.</p>
                        <p class="mt-2 text-slate-600">Silakan tanyakan materi yang belum jelas, minta contoh kasus, atau bimbingan LKS!</p>
                    </div>
                </div>
            </div>

            <!-- Quick Suggestion Prompts -->
            <div class="px-3 py-2 bg-white border-t border-slate-100 overflow-x-auto hide-scrollbar flex items-center gap-1.5 flex-shrink-0 text-[11px]">
                <button onclick="kirimPesanOtomatis('Ustadz, tolong jelaskan 4 ciri sosiologi dengan contoh sehari-hari!')" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-teal-50 text-[#0d8276] hover:bg-teal-100 border border-teal-100 font-bold transition">
                    💡 4 Ciri Sosiologi
                </button>
                <button onclick="kirimPesanOtomatis('Apa perbedaan Auguste Comte dan Emile Durkheim?')" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-teal-50 text-[#0d8276] hover:bg-teal-100 border border-teal-100 font-bold transition">
                    📖 Tokoh Sosiologi
                </button>
                <button onclick="kirimPesanOtomatis('Beri contoh fenomena sosial non-etis di pesantren!')" class="whitespace-nowrap px-2.5 py-1 rounded-full bg-teal-50 text-[#0d8276] hover:bg-teal-100 border border-teal-100 font-bold transition">
                    🕌 Contoh Non-Etis
                </button>
            </div>

            <!-- Chat Input Bar (Text + Mic Button) -->
            <div class="p-3 bg-white border-t border-slate-200 flex items-center gap-2 flex-shrink-0">
                <button id="micBtn" onclick="toggleVoiceInput()" class="w-10 h-10 rounded-2xl bg-teal-50 hover:bg-teal-100 text-[#0d8276] flex items-center justify-center transition flex-shrink-0" title="Bicara dengan Suara">
                    <i class="fas fa-microphone text-base"></i>
                </button>

                <input type="text" id="userInput" placeholder="Tanya Ustadz AI tentang Sosiologi..." class="flex-1 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-2.5 text-xs focus:ring-2 focus:ring-teal-500 focus:bg-white" onkeydown="if(event.key==='Enter') kirimPesan()">

                <button onclick="kirimPesan()" id="sendBtn" class="w-10 h-10 rounded-2xl bg-[#0d8276] hover:bg-[#0b6f65] text-white flex items-center justify-center shadow-md transition flex-shrink-0">
                    <i class="fas fa-paper-plane text-sm"></i>
                </button>
            </div>

        </div>
    </div>

    <!-- BOTTOM NAVBAR MOBILE -->
    <?php include 'bottombar-santri.php'; ?>

    <script>
        // --- LOGIC KUIS ---
        let totalScore = 0;
        let answeredQuestions = {};

        function cekJawaban(qIdx, selectedOpsi, correctOpsi, pembahasan) {
            const feedbackBox = document.getElementById('feedback_' + qIdx);
            feedbackBox.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200', 'bg-rose-50', 'text-rose-800', 'border-rose-200');
            
            if (selectedOpsi === correctOpsi) {
                feedbackBox.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
                feedbackBox.innerHTML = `<i class="fas fa-check-circle text-emerald-600 mr-1"></i> <b>Tepat Sekali!</b> ${pembahasan}`;
                answeredQuestions[qIdx] = 100;
            } else {
                feedbackBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
                feedbackBox.innerHTML = `<i class="fas fa-times-circle text-rose-600 mr-1"></i> <b>Kurang Tepat.</b> ${pembahasan}`;
                answeredQuestions[qIdx] = 0;
            }

            // Hitung skor total
            const totalQ = <?= count($materi_aktif['kuis']) ?>;
            if (Object.keys(answeredQuestions).length === totalQ) {
                let sum = Object.values(answeredQuestions).reduce((a, b) => a + b, 0);
                let finalScore = Math.round(sum / totalQ);
                const summaryBox = document.getElementById('quizResultSummary');
                const scoreText = document.getElementById('quizScoreText');
                scoreText.innerHTML = `Nilai Latihan Anda: <b>${finalScore}/100</b> • ${finalScore >= 75 ? 'Alhamdulillah Tuntas!' : 'Yuk baca lagi materinya dan tanya Ustadz AI!'}`;
                summaryBox.classList.remove('hidden');
            }
        }

        // --- LOGIC USTADZ AI ---
        let isVoiceActive = true;
        let recognition = null;
        let isListening = false;

        function bukaUstadzAI() {
            document.getElementById('aiModal').classList.remove('hidden');
            document.getElementById('userInput').focus();
        }

        function tutupUstadzAI() {
            document.getElementById('aiModal').classList.add('hidden');
            if (window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        }

        function toggleSound() {
            isVoiceActive = !isVoiceActive;
            const btn = document.getElementById('soundToggleBtn');
            btn.innerHTML = isVoiceActive ? '<i class="fas fa-volume-up text-xs"></i>' : '<i class="fas fa-volume-mute text-xs"></i>';
            if (!isVoiceActive && window.speechSynthesis) {
                window.speechSynthesis.cancel();
            }
        }

        function konsultasiLksKeAI() {
            bukaUstadzAI();
            kirimPesanOtomatis("Ustadz, bagaimana cara menjawab tugas LKS ini: '<?= addslashes($materi_aktif['lks']['tugas']) ?>'?");
        }

        function kirimPesanOtomatis(text) {
            document.getElementById('userInput').value = text;
            kirimPesan();
        }

        async function kirimPesan() {
            const input = document.getElementById('userInput');
            const text = input.value.trim();
            if (!text) return;

            input.value = '';
            const container = document.getElementById('chatContainer');

            // Tambah chat user
            container.innerHTML += `
                <div class="flex items-start justify-end gap-2.5">
                    <div class="bg-[#0d8276] text-white p-3.5 rounded-2xl rounded-tr-none shadow-2xs text-xs max-w-[85%] leading-relaxed font-medium">
                        ${escapeHtml(text)}
                    </div>
                </div>
            `;
            container.scrollTop = container.scrollHeight;

            // Indikator AI Mengetik
            const typingId = 'typing_' + Date.now();
            container.innerHTML += `
                <div class="flex items-start gap-2.5" id="${typingId}">
                    <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="bg-white p-3 rounded-2xl rounded-tl-none border border-teal-100 text-xs text-slate-500 italic">
                        <i class="fas fa-circle-notch fa-spin text-[#0d8276] mr-1"></i> Ustadz AI sedang menyiapkan penjelasan...
                    </div>
                </div>
            `;
            container.scrollTop = container.scrollHeight;

            // Prompt untuk Gemini
            const contextPrompt = `
Anda adalah "Ustadz AI Sosiologi", seorang guru dan ustadz pembimbing mata pelajaran Sosiologi SMA yang sangat ramah, santun, cerdas, komunikatif, dan penuh motivasi islami di sekolah/pesantren digital SADIGS 4.0.

Konteks Pembelajaran:
- Mata Pelajaran: Sosiologi
- Bab Aktif: <?= addslashes($materi_aktif['judul']) ?> (<?= addslashes($materi_aktif['subjudul']) ?>)
- Nama Santri: <?= addslashes($santri_nama) ?>

Instruksi Anda:
1. Sapa santri dengan ramah (misal: "Ahlan ananda ${escapeHtml('<?= addslashes($santri_nama) ?>')}", "Masya Allah pertanyaan yang bagus!").
2. Jelaskan konsep sosiologi dengan bahasa yang mudah dimengerti, analogi kehidupan sehari-hari, dan contoh lingkungan pondok/sekolah.
3. Berikan poin-poin yang terstruktur rapi.
4. Jangan terlalu panjang bertele-tele, fokus pada pemahaman konsep dan jawaban langsung.

Pertanyaan Santri:
"${text}"
            `;

            try {
                const response = await fetch('api-gemini.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ prompt: contextPrompt })
                });

                const data = await response.json();
                const typingElem = document.getElementById(typingId);
                if (typingElem) typingElem.remove();

                let aiText = "Mohon maaf ananda, Ustadz sedang mengalami sedikit kendala jaringan. Coba tanyakan sekali lagi ya!";
                if (data && data.status === 'success' && data.result) {
                    aiText = data.result;
                }

                // Render Markdown sederhana ke HTML
                let formattedHtml = formatAiMarkdown(aiText);

                container.innerHTML += `
                    <div class="flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-teal-100 shadow-2xs text-xs text-slate-800 max-w-[85%] leading-relaxed space-y-2">
                            ${formattedHtml}
                        </div>
                    </div>
                `;
                container.scrollTop = container.scrollHeight;

                // Bacakan Suara AI jika suara aktif
                if (isVoiceActive) {
                    speakText(aiText);
                }

            } catch (err) {
                const typingElem = document.getElementById(typingId);
                if (typingElem) typingElem.remove();
                container.innerHTML += `
                    <div class="flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-xl bg-[#0d8276] text-white flex items-center justify-center text-xs flex-shrink-0">
                            <i class="fas fa-robot"></i>
                        </div>
                        <div class="bg-white p-3.5 rounded-2xl rounded-tl-none border border-teal-100 text-xs text-slate-800 max-w-[85%]">
                            Afwan ananda, terjadi kendala saat menghubungkan ke server. Silakan coba lagi.
                        </div>
                    </div>
                `;
                container.scrollTop = container.scrollHeight;
            }
        }

        // Web Speech Recognition (Mic Voice Input)
        function toggleVoiceInput() {
            const micBtn = document.getElementById('micBtn');
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                alert("Browser Anda belum mendukung input suara. Silakan gunakan Google Chrome di HP.");
                return;
            }

            if (!recognition) {
                recognition = new SpeechRecognition();
                recognition.lang = 'id-ID';
                recognition.continuous = false;
                recognition.interimResults = false;

                recognition.onstart = function() {
                    isListening = true;
                    micBtn.classList.add('bg-rose-500', 'text-white', 'animate-pulse');
                    document.getElementById('userInput').placeholder = "Mendengarkan suara Anda...";
                };

                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    document.getElementById('userInput').value = transcript;
                    kirimPesan();
                };

                recognition.onerror = function() {
                    isListening = false;
                    micBtn.classList.remove('bg-rose-500', 'text-white', 'animate-pulse');
                    document.getElementById('userInput').placeholder = "Tanya Ustadz AI tentang Sosiologi...";
                };

                recognition.onend = function() {
                    isListening = false;
                    micBtn.classList.remove('bg-rose-500', 'text-white', 'animate-pulse');
                    document.getElementById('userInput').placeholder = "Tanya Ustadz AI tentang Sosiologi...";
                };
            }

            if (isListening) {
                recognition.stop();
            } else {
                recognition.start();
            }
        }

        // Web Speech Synthesis (Text to Speech Suara Ustadz AI)
        function speakText(text) {
            if (!window.speechSynthesis) return;
            window.speechSynthesis.cancel();

            // Bersihkan format markdown sebelum dibaca
            const cleanText = text.replace(/[*_#`]/g, '').replace(/<[^>]*>?/gm, '');
            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'id-ID';
            utterance.rate = 1.05;
            utterance.pitch = 1.0;
            window.speechSynthesis.speak(utterance);
        }

        function formatAiMarkdown(text) {
            let html = text
                .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
                .replace(/\*(.*?)\*/g, '<i>$1</i>')
                .replace(/\n\n/g, '<br><br>')
                .replace(/\n/g, '<br>');
            return html;
        }

        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>
