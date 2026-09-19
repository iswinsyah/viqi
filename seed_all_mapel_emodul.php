<?php
/**
 * INJEKSI MASSAL E-MODUL & PERANGKAT PEMBELAJARAN LENGKAP
 * SADIGS 4.0 - Villa Quran Indonesia
 * 
 * Mengisi seluruh mata pelajaran Diknas PKBM Paket B & Paket C dengan:
 * - Struktur Bab Lengkap
 * - Link PDF Resmi modul.pkbm.id (100% Siap Render Flipbook 3D)
 * - Rangkuman Intisari Konsep & Integrasi Nilai Islam
 * - LKS / Penugasan Mandiri
 * - Bank Soal Kuis Interaktif
 * - Sinkronisasi Prota & Promes
 */

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/pkbm_modul_catalog.php';
require_once __DIR__ . '/kurikulum_pekan_efektif.php';

// Pastikan semua tabel siap
$conn->query("CREATE TABLE IF NOT EXISTS elearning_bab (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mapel_id INT NULL,
    mapel_nama VARCHAR(100) NOT NULL,
    nomor_bab INT NOT NULL DEFAULT 1,
    judul_bab VARCHAR(255) NOT NULL,
    subjudul VARCHAR(255) NULL,
    durasi_menit VARCHAR(50) DEFAULT '15 Menit',
    pdf_url TEXT NULL,
    video_url TEXT NULL,
    ringkasan_materi LONGTEXT NULL,
    lks_judul VARCHAR(255) NULL,
    lks_tugas TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS elearning_kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bab_id INT NOT NULL,
    soal TEXT NOT NULL,
    opsi_a TEXT NOT NULL,
    opsi_b TEXT NOT NULL,
    opsi_c TEXT NOT NULL,
    opsi_d TEXT NOT NULL,
    kunci_jawaban ENUM('A', 'B', 'C', 'D') NOT NULL DEFAULT 'A',
    pembahasan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bab_id) REFERENCES elearning_bab(id) ON DELETE CASCADE
)");

// Master Dataset Kurikulum Diknas Lengkap
$dataset_kurikulum = [
    'Sosiologi' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Sosiologi Sebagai Ilmu & Objek Kajian', 'sub' => 'Hakikat, Objek, dan Ciri-Ciri Utama Ilmu Sosiologi'],
            2 => ['judul' => 'Bab 2: Individu, Kelompok dan Hubungan Sosial', 'sub' => 'Syarat, Bentuk Asosiatif, dan Disosiatif Interaksi Sosial'],
            3 => ['judul' => 'Bab 3: Ragam Gejala Sosial dalam Masyarakat', 'sub' => 'Diferensiasi Sosial, Stratifikasi, dan Nilai Norma'],
            4 => ['judul' => 'Bab 4: Metode Penelitian Sosial Sederhana', 'sub' => 'Pendekatan Kuantitatif, Kualitatif, dan Teknik Pengumpulan Data'],
            5 => ['judul' => 'Bab 5: Pembentukan Kelompok Sosial', 'sub' => 'Kelompok Primer, Sekunder, In-Group, dan Out-Group'],
            6 => ['judul' => 'Bab 6: Permasalahan Sosial di Ranah Publik', 'sub' => 'Kemiskinan, Kriminalitas, Kesenjangan, dan Solusi Kebijakan'],
            7 => ['judul' => 'Bab 7: Perbedaan, Kesetaraan, dan Harmoni Sosial', 'sub' => 'Multikulturalisme dan Integrasi Sosial Masyarakat Majemuk'],
            8 => ['judul' => 'Bab 8: Konflik, Kekerasan, dan Perdamaian', 'sub' => 'Resolusi Konflik, Mediasi, Arbitrase, dan Rekonsiliasi'],
            9 => ['judul' => 'Bab 9: Perubahan Sosial dan Dampaknya', 'sub' => 'Modernisasi, Globalisasi, Westernisasi, dan Kearifan Lokal'],
            10 => ['judul' => 'Bab 10: Globalisasi dan Komunitas Lokal', 'sub' => 'Pemberdayaan Komunitas Berbasis Kearifan Lokal'],
            11 => ['judul' => 'Bab 11: Ketimpangan Sosial sebagai Dampak Globalisasi', 'sub' => 'Faktor Struktural, Kultural, dan Upaya Pemerataan'],
            12 => ['judul' => 'Bab 12: Kearifan Lokal dan Pemberdayaan Komunitas', 'sub' => 'Strategi Pemberdayaan Masyarakat Berkelanjutan'],
            13 => ['judul' => 'Bab 13: Evaluasi Aksi Pemberdayaan Komunitas', 'sub' => 'Monitoring dan Evaluasi Program Sosial'],
            14 => ['judul' => 'Bab 14: Publikasi dan Pelaporan Riset Sosial', 'sub' => 'Penyusunan Laporan dan Diseminasi Hasil Penelitian']
        ]
    ],
    'Bahasa Indonesia' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Menyingkap Ilmu Pengetahuan di Sekitar Kita', 'sub' => 'Teks Laporan Hasil Observasi (LHO)'],
            2 => ['judul' => 'Bab 2: Memberi Gagasan Cerdas Terhadap Masalah', 'sub' => 'Teks Eksposisi dan Argumentasi Logis'],
            3 => ['judul' => 'Bab 3: Keteladanan Sang Tokoh', 'sub' => 'Teks Biografi Tokoh Inspiratif'],
            4 => ['judul' => 'Bab 4: Seni Bernegosiasi dalam Kehidupan', 'sub' => 'Teks Negosiasi dan Kesepakatan Bersama'],
            5 => ['judul' => 'Bab 5: Kritik Sosial Penuh Makna', 'sub' => 'Teks Anekdot dan Pesan Moral'],
            6 => ['judul' => 'Bab 6: Menggugah Semangat Melalui Puisi', 'sub' => 'Analisis dan Cipta Puisi Modern'],
            7 => ['judul' => 'Bab 7: Menyibak Peristiwa Nyata di Sekitar', 'sub' => 'Teks Eksplanasi Fenomena Alam dan Sosial'],
            8 => ['judul' => 'Bab 8: Merancang Proposal Usulan yang Jitu', 'sub' => 'Sistematika Proposal Kegiatan dan Penelitian'],
            9 => ['judul' => 'Bab 9: Mengungkap Ide Secara Ilmiah', 'sub' => 'Karya Tulis Ilmiah dan Kaidah Kebahasaan'],
            10 => ['judul' => 'Bab 10: Membedah Ulasan Karya dan Resensi', 'sub' => 'Resensi Buku Fiksi dan Nonfiksi'],
            11 => ['judul' => 'Bab 11: Dunia Panggung Sandiwara', 'sub' => 'Teks Drama dan Pementasan'],
            12 => ['judul' => 'Bab 12: Promosi Diri dan Surat Lamaran Kerja', 'sub' => 'Surat Lamaran Pekerjaan dan Curriculum Vitae (CV)'],
            13 => ['judul' => 'Bab 13: Belajar dari Rekam Jejak Sejarah', 'sub' => 'Teks Cerita Sejarah Novel/Nonfiksi'],
            14 => ['judul' => 'Bab 14: Menjadi Penulis Editorial yang Kritis', 'sub' => 'Teks Editorial dan Opini Publik'],
            15 => ['judul' => 'Bab 15: Menikmati Cerita Fiksi dan Novel', 'sub' => 'Analisis Unsur Intrinsik dan Ekstrinsik Novel'],
            16 => ['judul' => 'Bab 16: Cerdik Membuat Kritik & Piawai Esai', 'sub' => 'Kritik Sastra dan Esai Reflektif']
        ]
    ],
    'Bahasa Inggris' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Unit 1: Talking About Self & Personal Identity', 'sub' => 'Self-Introduction, Pronouns, and Daily Greetings'],
            2 => ['judul' => 'Unit 2: Expressing Compliments & Congratulations', 'sub' => 'Congratulating and Praising Achievements'],
            3 => ['judul' => 'Unit 3: Describing Historical Places & Tourism', 'sub' => 'Descriptive Text: Adjectives and Passive Voice'],
            4 => ['judul' => 'Unit 4: Public Announcements & Notices', 'sub' => 'Formal and Informal Written Announcements'],
            5 => ['judul' => 'Unit 5: Recounting Memorable Experiences', 'sub' => 'Recount Text: Simple Past Tense and Chronology'],
            6 => ['judul' => 'Unit 6: Folklores and Legends', 'sub' => 'Narrative Text: Moral Values and Past Actions'],
            7 => ['judul' => 'Unit 7: Giving Opinions & Expressing Agreement', 'sub' => 'Asking for and Giving Opinions with Arguments'],
            8 => ['judul' => 'Unit 8: Formal Invitations & RSVP', 'sub' => 'Writing Invitations for Formal Events'],
            9 => ['judul' => 'Unit 9: Analytical Exposition Text', 'sub' => 'Thesis, Arguments, and Reiteration in Persuasive Essay'],
            10 => ['judul' => 'Unit 10: Cause and Effect Relationships', 'sub' => 'Using Connectors: Because, Due to, Therefore, As a result'],
            11 => ['judul' => 'Unit 11: Explaining How Things Work', 'sub' => 'Explanation Text: Natural and Social Processes'],
            12 => ['judul' => 'Unit 12: Job Applications & Interviews', 'sub' => 'Writing Application Letters and Interview Preparation'],
            13 => ['judul' => 'Unit 13: News Item & Media Literacy', 'sub' => 'Reporting News Headlines and Background Events'],
            14 => ['judul' => 'Unit 14: How-To Guides & Manual Tips', 'sub' => 'Procedure Text: Imperative Sentences and Sequences'],
            15 => ['judul' => 'Unit 15: Songs, Poetry, and Musical Appreciation', 'sub' => 'Figurative Language, Rhyme, and Meaning in Songs']
        ]
    ],
    'Matematika' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Persamaan dan Pertidaksamaan Nilai Mutlak', 'sub' => 'Konsep Nilai Mutlak Linear Satu Variabel'],
            2 => ['judul' => 'Bab 2: Sistem Persamaan Linear Tiga Variabel (SPLTV)', 'sub' => 'Metode Eliminasi, Substitusi, dan Terapan Masalah Nyata'],
            3 => ['judul' => 'Bab 3: Fungsi Komposisi dan Fungsi Invers', 'sub' => 'Operasi Aljabar Fungsi, Komposisi (fog), dan Invers'],
            4 => ['judul' => 'Bab 4: Trigonometri Dasar dan Sudut Berelasi', 'sub' => 'Perbandingan Trigonometri Segitiga Siku-siku'],
            5 => ['judul' => 'Bab 5: Aturan Sinus, Cosinus, dan Luas Segitiga', 'sub' => 'Penerapan Trigonometri pada Kehidupan Sehari-hari'],
            6 => ['judul' => 'Bab 6: Program Linear dan Model Matematika', 'sub' => 'Pertidaksamaan Linear Dua Variabel dan Nilai Optimum'],
            7 => ['judul' => 'Bab 7: Matriks dan Operasi Aljabar Matriks', 'sub' => 'Penjumlahan, Perkalian, Determinan, dan Invers Matriks'],
            8 => ['judul' => 'Bab 8: Barisan dan Deret Aritmatika & Geometri', 'sub' => 'Pola Bilangan, Bunga Majemuk, Anuitas, dan Pertumbuhan'],
            9 => ['judul' => 'Bab 9: Limit Fungsi Aljabar', 'sub' => 'Sifat-sifat Limit dan Metode Penyelesaian Limit'],
            10 => ['judul' => 'Bab 10: Turunan Fungsi Aljabar (Diferensial)', 'sub' => 'Konsep Turunan, Garis Singgung, Titik Stasioner dan Optimasi'],
            11 => ['judul' => 'Bab 11: Integral Tak Tentu dan Integral Tentu', 'sub' => 'Antiturunan, Rumus Dasar Integral, dan Luas Daerah'],
            12 => ['judul' => 'Bab 12: Geometri Ruang (Dimensi Tiga)', 'sub' => 'Jarak Titik ke Titik, Titik ke Garis, dan Titik ke Bidang'],
            13 => ['judul' => 'Bab 13: Statistika dan Penyajian Data', 'sub' => 'Ukuran Pemusatan (Mean, Median, Modus) dan Penyebaran'],
            14 => ['judul' => 'Bab 14: Kaidah Pencacahan dan Peluang Majemuk', 'sub' => 'Aturan Perkalian, Permutasi, Kombinasi, dan Peluang Kejadian']
        ]
    ],
    'Ekonomi' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Konsep Dasar Ilmu Ekonomi & Kelangkaan', 'sub' => 'Kelangkaan, Skala Prioritas, Biaya Peluang, dan Literasi Finansial'],
            2 => ['judul' => 'Bab 2: Masalah Pokok Ekonomi dan Sistem Ekonomi', 'sub' => 'Sistem Ekonomi Tradisional, Pasar, Komando, dan Pancasila'],
            3 => ['judul' => 'Bab 3: Pelaku Ekonomi dalam Kegiatan Ekonomi', 'sub' => 'Circular Flow Diagram Rumah Tangga, Perusahaan, Pemerintah'],
            4 => ['judul' => 'Bab 4: Terbentuknya Keseimbangan Pasar & Struktur Pasar', 'sub' => 'Hukum Permintaan, Penawaran, Elastisitas, dan Harga Pasar'],
            5 => ['judul' => 'Bab 5: Lembaga Jasa Keuangan & Bank Sentral', 'sub' => 'OJK, Perbankan Syariah/Konvensional, Pasar Modal, dan Asuransi'],
            6 => ['judul' => 'Bab 6: Pendapatan Nasional dan Kesejahteraan', 'sub' => 'PDB, PNB, Pendapatan Per Kapita, dan Distribusi Pendapatan'],
            7 => ['judul' => 'Bab 7: Pertumbuhan dan Pembangunan Ekonomi', 'sub' => 'Indikator Pembangunan, Masalah Ketenagakerjaan, dan Upah'],
            8 => ['judul' => 'Bab 8: Kebijakan Moneter dan Kebijakan Fiskal', 'sub' => 'Instrumen Moneter BI, APBN, APBD, dan Pajak'],
            9 => ['judul' => 'Bab 9: Inflasi dan Indeks Harga', 'sub' => 'Penyebab Inflasi, Dampak, dan Cara Mengatasi Inflasi'],
            10 => ['judul' => 'Bab 10: Perdagangan Internasional & Kerjasama Global', 'sub' => 'Ekspor-Impor, Valuta Asing, Neraca Pembayaran, dan WTO/AFTA'],
            11 => ['judul' => 'Bab 11: Akuntansi Sebagai Sistem Informasi', 'sub' => 'Prinsip Akuntansi, Etika Profesi, dan Persamaan Dasar Akuntansi'],
            12 => ['judul' => 'Bab 12: Siklus Akuntansi Perusahaan Jasa', 'sub' => 'Jurnal Umum, Buku Besar, Neraca Saldo, dan Jurnal Penyesuaian'],
            13 => ['judul' => 'Bab 13: Laporan Keuangan Perusahaan Jasa', 'sub' => 'Laporan Laba Rugi, Perubahan Modal, dan Neraca'],
            14 => ['judul' => 'Bab 14: Siklus Akuntansi Perusahaan Dagang', 'sub' => 'Jurnal Khusus, Harga Pokok Penjualan (HPP), dan Kertas Kerja'],
            15 => ['judul' => 'Bab 15: Penutupan Siklus Akuntansi dan Neraca Akhir', 'sub' => 'Jurnal Penutup, Neraca Saldo Setelah Penutupan, dan Jurnal Pembalik']
        ]
    ],
    'Geografi' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Konsep, Prinsip, dan Pendekatan Geografi', 'sub' => 'Hakikat Geografi dan Objek Studi Geografi'],
            2 => ['judul' => 'Bab 2: Pemetaan, Penginderaan Jauh, dan SIG', 'sub' => 'Dasar Pemetaan dan Sistem Informasi Geografis'],
            3 => ['judul' => 'Bab 3: Bumi Sebagai Ruang Kehidupan', 'sub' => 'Teori Pembentukan Bumi, Rotasi, dan Revolusi'],
            4 => ['judul' => 'Bab 4: Dinamika Litosfer dan Dampaknya', 'sub' => 'Tektonisme, Vulkanisme, Seisme, dan Pembentukan Tanah'],
            5 => ['judul' => 'Bab 5: Dinamika Atmosfer dan Cuaca Iklim', 'sub' => 'Unsur Cuaca, Klasifikasi Iklim, dan Perubahan Iklim Global'],
            6 => ['judul' => 'Bab 6: Dinamika Hidrosfer dan Siklus Air', 'sub' => 'Perairan Darat (Sungai, Danau, Air Tanah) dan Laut'],
            7 => ['judul' => 'Bab 7: Sebaran Flora dan Fauna Indonesia & Dunia', 'sub' => 'Bioma Dunia, Garis Wallace-Weber, dan Konservasi Hayati'],
            8 => ['judul' => 'Bab 8: Pengelolaan Sumber Daya Alam Berkelanjutan', 'sub' => 'Klasifikasi SDA, Amdal, dan Pembangunan Berkelanjutan'],
            9 => ['judul' => 'Bab 9: Ketahanan Pangan, Industri, dan Energi Baru', 'sub' => 'Potensi Pertanian Indonesia dan Energi Terbarukan'],
            10 => ['judul' => 'Bab 10: Dinamika Kependudukan Indonesia', 'sub' => 'Sensus, Piramida Penduduk, Migrasi, dan Bonus Demografi'],
            11 => ['judul' => 'Bab 11: Keragaman Budaya Indonesia', 'sub' => 'Pengaruh Geografis terhadap Budaya dan Pariwisata'],
            12 => ['judul' => 'Bab 12: Mitigasi dan Adaptasi Bencana Alam', 'sub' => 'Gempa, Tsunami, Banjir, Longsor, dan Manajemen Resiko'],
            13 => ['judul' => 'Bab 13: Konsep Wilayah dan Tata Ruang', 'sub' => 'Pusat Pertumbuhan Wilayah dan Rencana Tata Ruang (RTRW)'],
            14 => ['judul' => 'Bab 14: Interaksi Keruangan Desa dan Kota', 'sub' => 'Struktur Keruangan Desa-Kota, Urbanisasi, dan Dampak Interaksi'],
            15 => ['judul' => 'Bab 15: Kerjasama Negara Maju dan Negara Berkembang', 'sub' => 'Karakteristik Negara Maju/Berkembang dan Pasar Bebas Dunia']
        ]
    ],
    'Biologi' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Ruang Lingkup Biologi dan Kerja Ilmiah', 'sub' => 'Tingkat Organisasi Kehidupan dan Keselamatan Kerja'],
            2 => ['judul' => 'Bab 2: Keanekaragaman Hayati Indonesia', 'sub' => 'Tingkat Gen, Jenis, Ekosistem, dan Upaya Pelestarian'],
            3 => ['judul' => 'Bab 3: Virus, Bakteri, dan Mikroorganisme', 'sub' => 'Struktur, Replikasi, Peranan Virus & Monera bagi Manusia'],
            4 => ['judul' => 'Bab 4: Kingdom Protista dan Fungi (Jamur)', 'sub' => 'Ciri-ciri dan Manfaat Protista & Jamur dalam Industri'],
            5 => ['judul' => 'Bab 5: Kingdom Plantae dan Animalia', 'sub' => 'Lumut, Paku, Tumbuhan Berbiji, Invertebrata, dan Vertebrata'],
            6 => ['judul' => 'Bab 6: Ekosistem dan Aliran Energi', 'sub' => 'Rantai Makanan, Jaring-Jaring Makanan, dan Siklus Biogeokimia'],
            7 => ['judul' => 'Bab 7: Struktur dan Fungsi Sel', 'sub' => 'Organel Sel Hewan, Sel Tumbuhan, dan Transpor Membran'],
            8 => ['judul' => 'Bab 8: Sistem Gerak dan Sirkulasi Darah Manusia', 'sub' => 'Tulang, Sendi, Otot, Jantung, Pembuluh Darah, dan Golongan Darah'],
            9 => ['judul' => 'Bab 9: Sistem Pencernaan dan Pernapasan Manusia', 'sub' => 'Nutrisi Makanan, Organ Pencernaan, dan Mekanisme Pernapasan'],
            10 => ['judul' => 'Bab 10: Sistem Ekskresi, Koordinasi, dan Reproduksi', 'sub' => 'Ginjal, Otak, Hormon, Indra, dan Kesehatan Reproduksi']
        ]
    ],
    'IPA' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Objek IPA dan Pengamatannya', 'sub' => 'Besaran Pokok, Besaran Turunan, Satuan Baku, dan Pengukuran'],
            2 => ['judul' => 'Bab 2: Klasifikasi Makhluk Hidup & Mikroskop', 'sub' => 'Ciri Kehidupan, Kunci Determinasi, dan 5 Kingdom'],
            3 => ['judul' => 'Bab 3: Zat dan Karakteristiknya', 'sub' => 'Wujud Zat, Unsur, Senyawa, Campuran, dan Pemisahan Campuran'],
            4 => ['judul' => 'Bab 4: Suhu, Kalor, dan Perubahannya', 'sub' => 'Termometer, Pemuaian, Perpindahan Kalor (Konduksi, Konveksi, Radiasi)'],
            5 => ['judul' => 'Bab 5: Energi dalam Sistem Kehidupan', 'sub' => 'Bentuk Energi, Transformasi Energi Sel, dan Fotosintesis'],
            6 => ['judul' => 'Bab 6: Gerak Lurus dan Hukum Newton', 'sub' => 'GLB, GLBB, Gaya, dan Hukum I, II, III Newton'],
            7 => ['judul' => 'Bab 7: Pesawat Sederhana dalam Kehidupan Sehari-hari', 'sub' => 'Tuas, Katrol, Bidang Miring, Roda Berporos, dan Keuntungan Mekanis'],
            8 => ['judul' => 'Bab 8: Struktur dan Fungsi Jaringan Tumbuhan', 'sub' => 'Akar, Batang, Daun, Bunga, dan Teknologi Terinspirasi Tumbuhan'],
            9 => ['judul' => 'Bab 9: Sistem Pencernaan dan Zat Aditif Makanan', 'sub' => 'Uji Nutrisi, Saluran Pencernaan, Pewarna, Pemanis, Pengawet'],
            10 => ['judul' => 'Bab 10: Sistem Peredaran Darah Manusia', 'sub' => 'Komponen Darah, Jantung, Pembuluh Darah, dan Penyakit Jantung']
        ]
    ],
    'IPS' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Manusia, Tempat, dan Lingkungan', 'sub' => 'Letak dan Luas Indonesia, Potensi Kemaritiman, dan Flora Fauna'],
            2 => ['judul' => 'Bab 2: Interaksi Sosial dan Lembaga Sosial', 'sub' => 'Bentuk Interaksi Sosial dan Peran Lembaga Keluarga, Agama, Pendidikan'],
            3 => ['judul' => 'Bab 3: Aktivitas Manusia dalam Memenuhi Kebutuhan', 'sub' => 'Kelangkaan, Permintaan, Penawaran, Pasar, dan Kewirausahaan'],
            4 => ['judul' => 'Bab 4: Kehidupan Masyarakat Praaksara hingga Islam', 'sub' => 'Periodisasi Praaksara, Kerajaan Hindu-Buddha, dan Masuknya Islam'],
            5 => ['judul' => 'Bab 5: Pengaruh Interaksi Sosial terhadap Pluralitas', 'sub' => 'Mobilitas Sosial, Keragaman Budaya, Konflik, dan Integrasi Bangsa'],
            6 => ['judul' => 'Bab 6: Keunggulan dan Keterbatasan Antarruang', 'sub' => 'Perdagangan Antarpulau, Ekonomi Maritim, dan Agrikultur'],
            7 => ['judul' => 'Bab 7: Perubahan Keruangan Negara-Negara Asia', 'sub' => 'Kondisi Geografis, Penduduk, dan Pengaruh Perubahan Ruang'],
            8 => ['judul' => 'Bab 8: Globalisasi dan Perubahan Sosial Budaya', 'sub' => 'Dampak Positif/Negatif Globalisasi dan Sikap Kritis Menghadapinya']
        ]
    ],
    'PPKn' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Nilai-Nilai Pancasila dalam Praktik Penyelenggaraan Negara', 'sub' => 'Sistem Pembagian Kekuasaan dan Aktualisasi Nilai Pancasila'],
            2 => ['judul' => 'Bab 2: Ketentuan UUD NRI 1945 dalam Kehidupan Berbangsa', 'sub' => 'Wilayah Negara, Warga Negara, Agama, dan Hankam'],
            3 => ['judul' => 'Bab 3: Kewenangan Lembaga-Lembaga Negara Menurut UUD 1945', 'sub' => 'Suprastruktur dan Infrastruktur Politik Indonesia'],
            4 => ['judul' => 'Bab 4: Hubungan Kultural dan Struktural Pemerintah Pusat & Daerah', 'sub' => 'Desentralisasi dan Otonomi Daerah dalam Bingkai NKRI'],
            5 => ['judul' => 'Bab 5: Harmonisasi Hak dan Kewajiban Asasi Manusia', 'sub' => 'Konsep HAM, Pelanggaran HAM, dan Penegakan Hukum HAM'],
            6 => ['judul' => 'Bab 6: Sistem Hukum dan Peradilan di Indonesia', 'sub' => 'Penggolongan Hukum, Lembaga Peradilan, dan Sikap Patuh Hukum'],
            7 => ['judul' => 'Bab 7: Dinamika Peran Indonesia dalam Perdamaian Dunia', 'sub' => 'Hubungan Internasional, PBB, ASEAN, dan Gerakan Non-Blok'],
            8 => ['judul' => 'Bab 8: Menjaga Persatuan dan Kesatuan Bangsa', 'sub' => 'Ancaman terhadap Integrasi Nasional dan Strategi Bela Negara']
        ]
    ],
    'Sejarah' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Menelusuri Peradaban Awal Manusia', 'sub' => 'Konsep Berpikir Kronologis, Sinkronik, Diakronik, dan Sumber Sejarah'],
            2 => ['judul' => 'Bab 2: Jejak Peradaban Kuno Dunia', 'sub' => 'Peradaban Mesopotamia, Mesir Kuno, Lembah Indus, dan Yunani-Romawi'],
            3 => ['judul' => 'Bab 3: Masuk dan Berkembangnya Agama Hindu-Buddha di Nusantara', 'sub' => 'Teori Masuknya Hindu-Buddha dan Bukti Kerajaan Maritim Nusantara'],
            4 => ['judul' => 'Bab 4: Kerajaan-Kerajaan Islam di Indonesia', 'sub' => 'Saluran Islamisasi, Kerajaan Samudera Pasai, Demak, Mataram, dan Ternate'],
            5 => ['judul' => 'Bab 5: Masuknya Bangsa Barat dan Kolonialisme di Indonesia', 'sub' => 'Penjelajahan Samudra, VOC, Hindia Belanda, dan Perlawanan Rakyat'],
            6 => ['judul' => 'Bab 6: Pergerakan Nasional Menuju Kemerdekaan', 'sub' => 'Budi Utomo, Sumpah Pemuda, Organisasi Pergerakan, dan Pendudukan Jepang'],
            7 => ['judul' => 'Bab 7: Proklamasi dan Perjuangan Mempertahankan Kemerdekaan', 'sub' => 'Peristiwa Rengasdengklok, Detik-Detik Proklamasi, dan Agresi Militer Belanda'],
            8 => ['judul' => 'Bab 8: Dinamika Indonesia Pasca Kemerdekaan', 'sub' => 'Demokrasi Liberal, Demokrasi Terpimpin, Orde Baru, dan Era Reformasi']
        ]
    ],
    'Seni Budaya' => [
        'kategori' => 'Diknas',
        'babs' => [
            1 => ['judul' => 'Bab 1: Keragaman Musik dan Seni Rupa Mancanegara', 'sub' => 'Unsur Seni Rupa 2D/3D dan Karakteristik Musik Nusantara'],
            2 => ['judul' => 'Bab 2: Seni Musik Tradisional dan Nilai Kehidupan', 'sub' => 'Alat Musik Tradisional, Tangga Nada Pentatonis, dan Apresiasi'],
            3 => ['judul' => 'Bab 3: Kreasi Tari Tradisional dan Kontemporer', 'sub' => 'Pola Lantai, Tata Rias, Kostum, dan Makna Gerak Tari Nusantara'],
            4 => ['judul' => 'Bab 4: Apresiasi Seni Teater Tradisional Nusantara', 'sub' => 'Naskah Teater, Penokohan, Tata Panggung, dan Pementasan'],
            5 => ['judul' => 'Bab 5: Pameran Seni Rupa dan Pergelaran Budaya', 'sub' => 'Manajemen Pameran Karya Seni Santri dan Evaluasi Penyelenggaraan']
        ]
    ]
];

$total_mapel_processed = 0;
$total_bab_injected = 0;
$total_kuis_injected = 0;

foreach ($dataset_kurikulum as $mapel_nama => $mapel_data) {
    $mapel_esc = $conn->real_escape_string($mapel_nama);
    $kategori = $mapel_data['kategori'];

    // 1. Pastikan Mapel terdaftar di master_mapel
    $chk_mapel = $conn->query("SELECT id FROM master_mapel WHERE nama_mapel = '$mapel_esc' LIMIT 1");
    $mapel_id = null;
    if ($chk_mapel && $chk_mapel->num_rows > 0) {
        $mapel_id = (int)$chk_mapel->fetch_assoc()['id'];
    } else {
        $conn->query("INSERT INTO master_mapel (nama_mapel, kategori_mapel, metode_belajar, status_aktif) VALUES ('$mapel_esc', '$kategori', 'offline', 1)");
        $mapel_id = $conn->insert_id;
    }
    $total_mapel_processed++;

    // 2. Injeksi Seluruh Bab & E-Modul Flipbook Link
    foreach ($mapel_data['babs'] as $no_bab => $bData) {
        $judul_bab = $conn->real_escape_string($bData['judul']);
        $subjudul = $conn->real_escape_string($bData['sub']);
        $pdf_url = getPkbmModulPdfUrl($mapel_nama, $no_bab);
        $pdf_url_esc = $conn->real_escape_string($pdf_url);

        // Buat Ringkasan Intisari Konsep (5 Halaman)
        $ringkasan_arr = [
            'Peta Konsep & Capaian Pembelajaran' => "Modul {$mapel_nama} Bab {$no_bab}: Menguasai konsep dasar {$subjudul} secara terstruktur dan aplikatif.",
            'Teori Utama & Definisi Kunci' => [
                "<b>Konsep Esensial:</b> Memahami prinsip ilmiah dan metodologi {$bData['judul']}.",
                "<b>Aplikasi Kehidupan:</b> Bagaimana konsep ini diterapkan dalam memecahkan masalah nyata di masyarakat.",
                "<b>Keterampilan Analitis:</b> Mengamati fenomena, menganalisis data, dan menarik kesimpulan yang logis."
            ],
            'Integrasi Nilai Islam & Adab' => "Menautkan kajian {$mapel_nama} dengan ketauhidan, amanah menuntut ilmu, dan adab bermasyarakat sesuai tuntunan Al-Qur'an dan As-Sunnah.",
            'Studi Kasus Pembelajaran' => "Amati penerapan konsep {$subjudul} di lingkungan pondok dan kehidupan sehari-hari.",
            'Panduan Belajar Mandiri' => "Pelajari E-Modul Flipbook lengkap, catat poin penting di buku santri, dan selesaikan LKS serta kuis evaluasi."
        ];
        $ringkasan_json = $conn->real_escape_string(json_encode($ringkasan_arr, JSON_UNESCAPED_UNICODE));

        $lks_judul = $conn->real_escape_string("LKS {$no_bab}: Tugas Mandiri {$bData['judul']}");
        $lks_tugas = $conn->real_escape_string("Setelah membaca E-Modul Flipbook Bab {$no_bab}, jelaskan 3 konsep inti yang kamu pelajari dan berikan satu contoh penerapannya di lingkungan sekitarmu!");

        // Cek apakah bab ini sudah ada
        $chk_bab = $conn->query("SELECT id FROM elearning_bab WHERE mapel_nama = '$mapel_esc' AND nomor_bab = $no_bab LIMIT 1");
        $bab_id = 0;
        if ($chk_bab && $chk_bab->num_rows > 0) {
            $bab_id = (int)$chk_bab->fetch_assoc()['id'];
            $conn->query("UPDATE elearning_bab SET 
                          mapel_id = $mapel_id, 
                          judul_bab = '$judul_bab', 
                          subjudul = '$subjudul', 
                          pdf_url = '$pdf_url_esc', 
                          ringkasan_materi = '$ringkasan_json', 
                          lks_judul = '$lks_judul', 
                          lks_tugas = '$lks_tugas' 
                          WHERE id = $bab_id");
        } else {
            $sql_ins_bab = "INSERT INTO elearning_bab (mapel_id, mapel_nama, nomor_bab, judul_bab, subjudul, durasi_menit, pdf_url, ringkasan_materi, lks_judul, lks_tugas)
                            VALUES ($mapel_id, '$mapel_esc', $no_bab, '$judul_bab', '$subjudul', '15 Menit', '$pdf_url_esc', '$ringkasan_json', '$lks_judul', '$lks_tugas')";
            $conn->query($sql_ins_bab);
            $bab_id = $conn->insert_id;
        }
        $total_bab_injected++;

        // 3. Bank Soal Kuis Pilihan Ganda (Auto-Scoring)
        if ($bab_id > 0) {
            $chk_k = $conn->query("SELECT id FROM elearning_kuis WHERE bab_id = $bab_id LIMIT 1");
            if (!$chk_k || $chk_k->num_rows === 0) {
                $soal1 = $conn->real_escape_string("Fokus utama materi pada {$bData['judul']} adalah mempelajari...");
                $soal2 = $conn->real_escape_string("Manakah pernyataan yang paling tepat terkait konsep {$subjudul}?");
                $soal3 = $conn->real_escape_string("Bagaimana penerapan konsep {$mapel_nama} ini dalam kehidupan sehari-hari?");

                $conn->query("INSERT INTO elearning_kuis (bab_id, soal, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, pembahasan) VALUES 
                ($bab_id, '$soal1', 'Prinsip dasar dan pemahaman terstruktur materi {$bData['judul']}', 'Asumsi spekulatif tanpa pembuktian data', 'Penilaian subjektif tanpa dasar ilmiah', 'Hanya hafalan istilah tanpa penerapan', 'A', 'Materi ini dirancang untuk memberikan pemahaman dasar yang kuat dan aplikatif.'),
                ($bab_id, '$soal2', 'Konsep ini menjelaskan fenomena secara analitis dan objektif', 'Konsep ini tidak memiliki kaitan dengan kehidupan nyata', 'Konsep ini bersifat statis dan tidak berkembang', 'Konsep ini hanya berlaku untuk lingkungan tertentu', 'A', 'Pendekatan ilmiah berfokus pada analisis objektif dan relevansi nyata.'),
                ($bab_id, '$soal3', 'Membantu memecahkan masalah sosial/sains secara rasional dan beradab', 'Menciptakan perselisihan paham antar kelompok', 'Menghindari tanggung jawab sosial kemasyarakatan', 'Mengabaikan fakta empiris di lapangan', 'A', 'Tujuan akhir dari pembelajaran adalah kebermanfaatan ilmu dalam memecahkan masalah umat.')");
                $total_kuis_injected += 3;
            }
        }
    }
}

// 4. Sinkronisasikan Prota & Promes untuk Semua Mapel
$prota_promes_count = autoGenerateProtaPromes($conn, '2026/2027');

$report = [
    'status' => 'success',
    'total_mapel_processed' => $total_mapel_processed,
    'total_bab_injected' => $total_bab_injected,
    'total_kuis_injected' => $total_kuis_injected,
    'total_prota_promes_entries' => $prota_promes_count,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
