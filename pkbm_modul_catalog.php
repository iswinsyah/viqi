<?php
/**
 * PKBM E-MODUL OFFICIAL CATALOG (Kemdikbudristek Pendidikan Kesetaraan)
 * Source: https://modul.pkbm.id/
 * Direct official repository for Paket A (SD), Paket B (SMP), Paket C (SMA)
 */

function getPkbmModulPdfUrl($mapel, $bab_no = 1, $jenjang = 'SMA') {
    $mapel_key = strtolower(trim($mapel));
    $bab_no = (int)$bab_no;
    if ($bab_no <= 0) $bab_no = 1;

    // Normalisasi Nama Mapel
    if (strpos($mapel_key, 'sosiologi') !== false) $m = 'sosiologi';
    elseif (strpos($mapel_key, 'indo') !== false) $m = 'bahasa_indonesia';
    elseif (strpos($mapel_key, 'inggris') !== false || strpos($mapel_key, 'english') !== false) $m = 'bahasa_inggris';
    elseif (strpos($mapel_key, 'ekonomi') !== false) $m = 'ekonomi';
    elseif (strpos($mapel_key, 'geografi') !== false) $m = 'geografi';
    elseif (strpos($mapel_key, 'sejarah') !== false) $m = 'sejarah';
    elseif (strpos($mapel_key, 'matematika') !== false || strpos($mapel_key, 'mtk') !== false) $m = 'matematika';
    elseif (strpos($mapel_key, 'biologi') !== false) $m = 'biologi';
    elseif (strpos($mapel_key, 'fisika') !== false) $m = 'fisika';
    elseif (strpos($mapel_key, 'kimia') !== false) $m = 'kimia';
    elseif (strpos($mapel_key, 'pkn') !== false || strpos($mapel_key, 'ppkn') !== false || strpos($mapel_key, 'pancasila') !== false) $m = 'ppkn';
    elseif (strpos($mapel_key, 'seni') !== false) $m = 'seni_budaya';
    elseif (strpos($mapel_key, 'ipa') !== false) $m = 'ipa';
    elseif (strpos($mapel_key, 'ips') !== false) $m = 'ips';
    elseif (strpos($mapel_key, 'prakarya') !== false) $m = 'prakarya';
    else $m = $mapel_key;

    // KATALOG MODUL PAKET C (SMA / MA)
    $paket_c = [
        'sosiologi' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Sosiologi Paket C Ada Apa dengan Sosiologi.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Sosiologi Paket C Budaya Musik.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Sosiologi Paket C Menjauhkan yang Dekat Mendekatkan yang Jauh.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Sosiologi Paket C Indahnya Pelangi Masyarakat Indonesia.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Sosiologi Paket C Meneliti itu Mudah.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Sosiologi Paket C Keunikan Mencari Teman.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Sosiologi Paket C Menjadi Dokter Sosiologi.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Sosiologi Paket C Orkestra Kehidupan Sosial.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Sosiologi Paket C Badai Pasti Berlalu.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Sosiologi Paket C Bersatu Kita Teguh Bercerai Kita Runtuh.pdf',
            11 => 'https://modul.pkbm.id/paket-c/Modul 11 Sosiologi Paket C Warna Warni Kehidupan.pdf',
            12 => 'https://modul.pkbm.id/paket-c/Modul 12 Sosiologi Paket C Antara Harapan dan Kenyataan.pdf',
            13 => 'https://modul.pkbm.id/paket-c/Modul 13 Sosiologi Paket C Bertahan Atau Hancur.pdf',
            14 => 'https://modul.pkbm.id/paket-c/Modul 14 Sosiologi Paket C Kenali Dirimu.pdf',
        ],
        'bahasa_indonesia' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Bahasa Indonesia Paket C Menyingkap Ilmu Pengetahuan di Sekitar Kita.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Bahasa Indonesia Paket C Memberi Gagasan Cerdas Terhadap Permasalahan di Sekitar.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Bahasa Indonesia Paket C Keteladanan Sang Tokoh.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Bahasa Indonesia Paket C Teks Negosiasi.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Bahasa Indonesia Paket C Anekdot.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Bahasa Indonesia Paket C Niat Menuju Sukses.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Bahasa Indonesia Paket C Menyibak Peristiwa di Sekitar.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Bahasa Indonesia Paket C Membuat Usulan Yang Jitu.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Bahasa Indonesia Paket C Ungkap Tuntas Idemu Secara Ilmiyah.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Bahasa Indonesia Paket C Membedah Kehidupan Sang Tokoh.pdf',
            11 => 'https://modul.pkbm.id/paket-c/Modul 11 Bahasa Indonesia Paket C Mengupas Tuntas Karya-karya Fiksi dan Nonfiksi.pdf',
            12 => 'https://modul.pkbm.id/paket-c/Modul 12 Bahasa Indonesia Paket C Promosi Diri.pdf',
            13 => 'https://modul.pkbm.id/paket-c/Modul 13 Bahasa Indonesia Paket C Belajar dari Sejarah.pdf',
            14 => 'https://modul.pkbm.id/paket-c/Modul 14 Bahasa Indonesia Paket C Menjadi Penulis itu Asyik.pdf',
            15 => 'https://modul.pkbm.id/paket-c/Modul 15 Bahasa Indonesia Paket C Berani Menyampaikan Pendapat.pdf',
            16 => 'https://modul.pkbm.id/paket-c/Modul 16 Bahasa Indonesia Paket C Cerdik Membuat Kritik Piawai Membuat Esai.pdf',
        ],
        'bahasa_inggris' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Bahasa Inggris Paket C Who I Am.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Bahasa Inggris Paket C Thank You I\'m Flattered.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Bahasa Inggris Paket C Having Fun at Historical Places.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Bahasa Inggris Paket C Announcement.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Bahasa Inggris Paket C Let\'s Sing A Song.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Bahasa Inggris Paket C Thank It\'s Helpful.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Bahasa Inggris Paket C I Think You\'re Right!.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Bahasa Inggris Paket C Don\'t Worry I\'ll Come.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Bahasa Inggris Paket C Let Me Know!.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Bahasa Inggris Paket C My World.pdf',
            11 => 'https://modul.pkbm.id/paket-c/Modul 11 Bahasa Inggris Paket C With My Pleasure.pdf',
            12 => 'https://modul.pkbm.id/paket-c/Modul 12 Bahasa Inggris Paket C It\'s A Good Job.pdf',
            13 => 'https://modul.pkbm.id/paket-c/Modul 13 Bahasa Inggris Paket C A Picture Speaks Louder Than a Word.pdf',
            14 => 'https://modul.pkbm.id/paket-c/Modul 14 Bahasa Inggris Paket C Bad News Is a Good News.pdf',
            15 => 'https://modul.pkbm.id/paket-c/Modul 15 Bahasa Inggris Paket C Manual Tips.pdf',
        ],
        'ekonomi' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Ekonomi Paket C Memahami Ekonomi.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Ekonomi Paket C Menjadi Konsumen Cerdas.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Ekonomi Paket C Sejarah Pasca Pensiun.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Ekonomi Paket C Penggerak Ekonomi Negeriku.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Ekonomi Paket C Kreatif Mengelola Sumber Daya.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Ekonomi Paket C Pendapatan Nasional dan Kesejahteraan Ekonomi.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Ekonomi Paket C Membangun Ekonomi Nasional.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Ekonomi Paket C Inflasi Yang Merenggut Kemakmuran.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Ekonomi Paket C Anggaran Belanja Antara Perencanaan dan Realisasi.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Ekonomi Paket C Menembus Pasar Dunia.pdf',
            11 => 'https://modul.pkbm.id/paket-c/Modul 11 Ekonomi Paket C Pentingnya Pencatatan Keuangan.pdf',
            12 => 'https://modul.pkbm.id/paket-c/Modul 12 Ekonomi Paket C Catat dan Laporkan Transaksi Jasa 1.pdf',
            13 => 'https://modul.pkbm.id/paket-c/Modul 13 Ekonomi Paket C Catat dan Laporkan Transaksi Jasa 2.pdf',
            14 => 'https://modul.pkbm.id/paket-c/Modul 14 Ekonomi Paket C Catat dan Laporkan Transaksi Dagang 1.pdf',
            15 => 'https://modul.pkbm.id/paket-c/Modul 15 Ekonomi Paket C Catat dan Laporkan Transaksi Dagang 2.pdf',
        ],
        'geografi' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Geografi Paket C Menenal Geografi untuk Kehidupan.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Geografi Paket C Menjadi Peneliti Geografi.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Geografi Paket C Bumi Tempat Kita Hidup.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Geografi Paket C Ramah Dengan Alam.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Geografi Paket C Udara Dan Air Sumber Kehidupan.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Geografi Paket C Negriku Subur dan Makmur.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Geografi Paket C Uniknya Flora Fauna Indonesia.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Geografi Paket C Alamku Berlimpah.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Geografi Paket C Padat Tidak Merata.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Geografi Paket C Bangsa Indonesia Bangsa Yang Berbudaya.pdf',
            11 => 'https://modul.pkbm.id/paket-c/Modul 11 Geografi Paket C Tata Ruang Kehidupan.pdf',
            12 => 'https://modul.pkbm.id/paket-c/Modul 12 Geografi Paket C Menata Wilayah.pdf',
            13 => 'https://modul.pkbm.id/paket-c/Modul 13 Geografi Paket C Interaksi Desa Kota.pdf',
            14 => 'https://modul.pkbm.id/paket-c/Modul 14 Geografi Paket C Memotret Wilayah Sekitar.pdf',
            15 => 'https://modul.pkbm.id/paket-c/Modul 15 Geografi Paket C Menyongsong Indonesia Maju.pdf',
        ],
        'matematika' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Matematika Paket C Belanja Cerdas.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Matematika Paket C Memulai Bisnis.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Matematika Paket C e-KTP.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Matematika Paket C Bertani.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Matematika Paket C Penerapan Trigonometri dalam Pengembangan Ilmu dan Teknologi dalam Kehidupan Sehari-hari.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Matematika Paket C Berpikir Logis.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Matematika Paket C Mengatur Kebutuhan Sehari-hari Dengan Menggunakan Program Linier.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Matematika Paket C Keteraturan Barisan dan Penyajian Data dalam Bentuk Matriks.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Matematika Paket C Penerapan Limit dan Turunan dalam Kehidupan Masyarakat Sehari-hari.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Matematika Paket C Penerapan Integral dalam Kehidupan Masyarakat Sehari-hari.pdf',
            11 => 'https://modul.pkbm.id/paket-c/Modul 11 Matematika Paket C Jauh Dekat Bisa Didapat.pdf',
            12 => 'https://modul.pkbm.id/paket-c/Modul 12 Matematika Paket C Mengolah Data.pdf',
            13 => 'https://modul.pkbm.id/paket-c/Modul 13 Matematika Paket C Berjabat Tangan.pdf',
            14 => 'https://modul.pkbm.id/paket-c/Modul 14 Matematika Paket C Kapan Kesempatan.pdf',
        ],
        'biologi' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Biologi Paket C Biologi dan Peranannya dalam Kehidupan Manusia.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Biologi Paket C Mengenal Kekayaan Hayati Indonesia.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Biologi Paket C Mikroorganisme bagi Kehidupan Manusia.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Biologi Paket C Menelusuri Keanekaragaman Hayati sebagai Penyokong Kehidupan Manusia.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Biologi Paket C Harmoni Alam Semesta.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Biologi Paket C Kecil Tapi Sungguh Mengagumkan.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Biologi Paket C Sistem Gerak dan Sirkulasi.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Biologi Paket C Badan Sehat Jiwa Kuat.pdf',
            9 => 'https://modul.pkbm.id/paket-c/Modul 9 Biologi Paket C Tetap Sehat dan Menjaga Kesehatan Sistem Koordinasi.pdf',
            10 => 'https://modul.pkbm.id/paket-c/Modul 10 Biologi Paket C Reproduksi dan Hidup Sehat.pdf',
        ],
        'fisika' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Fisika Paket C Pengukuran dan Besaran.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Fisika Paket C Gerak Lurus dan Hukum Newton.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Fisika Paket C Usaha dan Energi.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Fisika Paket C Momentum dan Impuls.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Fisika Paket C Fluida Statis dan Dinamis.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Fisika Paket C Suhu Kalor dan Termodinamika.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Fisika Paket C Gelombang dan Optik.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Fisika Paket C Listrik Dinamis dan Magnet.pdf',
        ],
        'kimia' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Kimia Paket C Hakikat Ilmu Kimia dan Metode Ilmiah.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Kimia Paket C Struktur Atom dan Tabel Periodik.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Kimia Paket C Ikatan Kimia dan Bentuk Molekul.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Kimia Paket C Larutan Elektrolit dan Non-Elektrolit.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Kimia Paket C Reaksi Redoks dan Tata Nama Senyawa.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Kimia Paket C Stoikiometri dan Perhitungan Kimia.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Kimia Paket C Termokimia dan Laju Reaksi.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Kimia Paket C Asam Basa dan Kesetimbangan.pdf',
        ],
        'sejarah' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Sejarah Peminatan Paket C Menelusuri Peradaban Awal Manusia.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Sejarah Peminatan Paket C Membangun Jembatan Ingatan.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Sejarah Peminatan Paket C Jejak Peradaban Dunia.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Sejarah Peminatan Paket C Fajar Peradaban Dunia.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Sejarah Peminatan Paket C Indonesia Menatap Dunia.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 Sejarah Indonesia Paket C Menggapai Kemerdekaan.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 Sejarah Indonesia Paket C Mempertahankan Eksistensi Bangsa.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 Sejarah Indonesia Paket C Perjuangan Mengisi Kemerdekaan.pdf',
        ],
        'ppkn' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 PPKn Paket C Nilai-Nilai Pancasila dalam Praktik Penyelenggaraan Negara.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 PPKn Paket C Ketentuan UUD NRI Tahun 1945 dalam Kehidupan Berbangsa.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 PPKn Paket C Kewenangan Lembaga-Lembaga Negara.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 PPKn Paket C Harmonisasi Hak dan Kewajiban Asasi Manusia.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 PPKn Paket C Sistem Hukum dan Peradilan di Indonesia.pdf',
            6 => 'https://modul.pkbm.id/paket-c/Modul 6 PPKn Paket C Dinamika Peran Indonesia dalam Perdamaian Dunia.pdf',
            7 => 'https://modul.pkbm.id/paket-c/Modul 7 PPKn Paket C Kasus-Kasus Pelanggaran Hak dan Pengingkaran Kewajiban.pdf',
            8 => 'https://modul.pkbm.id/paket-c/Modul 8 PPKn Paket C Pengaruh Kemajuan IPTEK terhadap NKRI.pdf',
        ],
        'seni_budaya' => [
            1 => 'https://modul.pkbm.id/paket-c/Modul 1 Seni Budaya Paket C Keragaman Musik.pdf',
            2 => 'https://modul.pkbm.id/paket-c/Modul 2 Seni Budaya Paket C Kehidupan Sosial Mendayu melalui Musik Tradisional.pdf',
            3 => 'https://modul.pkbm.id/paket-c/Modul 3 Seni Budaya Paket C Musik adalah Hidupku.pdf',
            4 => 'https://modul.pkbm.id/paket-c/Modul 4 Seni Budaya Paket C Harmoni dalam Musik Tradisi.pdf',
            5 => 'https://modul.pkbm.id/paket-c/Modul 5 Seni Budaya Paket C Kolaborasi Pertunjukkan Seni Musik Tradisi.pdf',
        ]
    ];

    // KATALOG MODUL PAKET B (SMP / MTs)
    $paket_b = [
        'ipa' => [
            1 => 'https://modul.pkbm.id/paket-b/Modul 1 IPA Paket B Objek IPA dan Pengamatannya.pdf',
            2 => 'https://modul.pkbm.id/paket-b/Modul 2 IPA Paket B Klasifikasi Makhluk Hidup.pdf',
            3 => 'https://modul.pkbm.id/paket-b/Modul 3 IPA Paket B Zat dan Karakteristiknya.pdf',
            4 => 'https://modul.pkbm.id/paket-b/Modul 4 IPA Paket B Suhu dan Perubahannya.pdf',
            5 => 'https://modul.pkbm.id/paket-b/Modul 5 IPA Paket B Energi dalam Sistem Kehidupan.pdf',
            6 => 'https://modul.pkbm.id/paket-b/Modul 6 IPA Paket B Gerak dan Gaya.pdf',
            7 => 'https://modul.pkbm.id/paket-b/Modul 7 IPA Paket B Pesawat Sederhana.pdf',
            8 => 'https://modul.pkbm.id/paket-b/Modul 8 IPA Paket B Struktur Tumbuhan dan Fotosintesis.pdf',
            9 => 'https://modul.pkbm.id/paket-b/Modul 9 IPA Paket B Sistem Pencernaan dan Zat Aditif.pdf',
            10 => 'https://modul.pkbm.id/paket-b/Modul 10 IPA Paket B Sistem Peredaran Darah.pdf',
        ],
        'ips' => [
            1 => 'https://modul.pkbm.id/paket-b/Modul 1 IPS Paket B Manusia Tempat dan Lingkungan.pdf',
            2 => 'https://modul.pkbm.id/paket-b/Modul 2 IPS Paket B Interaksi Sosial dan Lembaga Sosial.pdf',
            3 => 'https://modul.pkbm.id/paket-b/Modul 3 IPS Paket B Aktivitas Manusia dalam Memenuhi Kebutuhan.pdf',
            4 => 'https://modul.pkbm.id/paket-b/Modul 4 IPS Paket B Kehidupan Masyarakat Praaksara Hindu-Buddha dan Islam.pdf',
            5 => 'https://modul.pkbm.id/paket-b/Modul 5 IPS Paket B Pengaruh Interaksi Sosial terhadap Kehidupan Sosial dan Kebangsaan.pdf',
            6 => 'https://modul.pkbm.id/paket-b/Modul 6 IPS Paket B Keunggulan dan Keterbatasan Antarruang serta Pengaruhnya.pdf',
            7 => 'https://modul.pkbm.id/paket-b/Modul 7 IPS Paket B Perubahan Keruangan dan Interaksi Antarruang Negara Asia.pdf',
            8 => 'https://modul.pkbm.id/paket-b/Modul 8 IPS Paket B Globalisasi dan Perubahan Sosial Budaya.pdf',
        ],
        'matematika' => [
            1 => 'https://modul.pkbm.id/paket-b/Modul 1 Matematika Paket B Bilangan Bulat dan Pecahan.pdf',
            2 => 'https://modul.pkbm.id/paket-b/Modul 2 Matematika Paket B Himpunan.pdf',
            3 => 'https://modul.pkbm.id/paket-b/Modul 3 Matematika Paket B Bentuk Aljabar.pdf',
            4 => 'https://modul.pkbm.id/paket-b/Modul 4 Matematika Paket B Persamaan dan Pertidaksamaan Linear Satu Variabel.pdf',
            5 => 'https://modul.pkbm.id/paket-b/Modul 5 Matematika Paket B Perbandingan dan Skala.pdf',
            6 => 'https://modul.pkbm.id/paket-b/Modul 6 Matematika Paket B Aritmatika Sosial.pdf',
            7 => 'https://modul.pkbm.id/paket-b/Modul 7 Matematika Paket B Garis dan Sudut.pdf',
            8 => 'https://modul.pkbm.id/paket-b/Modul 8 Matematika Paket B Segiempat dan Segitiga.pdf',
            9 => 'https://modul.pkbm.id/paket-b/Modul 9 Matematika Paket B Penyajian Data dan Statistika Dasar.pdf',
            10 => 'https://modul.pkbm.id/paket-b/Modul 10 Matematika Paket B Teorema Pythagoras dan Lingkaran.pdf',
        ],
        'bahasa_indonesia' => [
            1 => 'https://modul.pkbm.id/paket-b/Modul 1 Bahasa Indonesia Paket B Belajar Mendeskripsikan.pdf',
            2 => 'https://modul.pkbm.id/paket-b/Modul 2 Bahasa Indonesia Paket B Memahami dan Mencipta Cerita Fantasi.pdf',
            3 => 'https://modul.pkbm.id/paket-b/Modul 3 Bahasa Indonesia Paket B Mewariskan Budaya Melalui Teks Prosedur.pdf',
            4 => 'https://modul.pkbm.id/paket-b/Modul 4 Bahasa Indonesia Paket B Menyibak Ilmu dalam Laporan Hasil Observasi.pdf',
            5 => 'https://modul.pkbm.id/paket-b/Modul 5 Bahasa Indonesia Paket B Mewarisi Nilai Luhur dan Berkreasi Puisi Rakyat.pdf',
            6 => 'https://modul.pkbm.id/paket-b/Modul 6 Bahasa Indonesia Paket B Mengapresiasi dan Mengkreasikan Fabel.pdf',
            7 => 'https://modul.pkbm.id/paket-b/Modul 7 Bahasa Indonesia Paket B Berkorespondensi dengan Surat Pribadi dan Dinas.pdf',
            8 => 'https://modul.pkbm.id/paket-b/Modul 8 Bahasa Indonesia Paket B Menjadi Pembaca Efektif.pdf',
        ],
        'bahasa_inggris' => [
            1 => 'https://modul.pkbm.id/paket-b/Modul 1 Bahasa Inggris Paket B Good Morning How Are You.pdf',
            2 => 'https://modul.pkbm.id/paket-b/Modul 2 Bahasa Inggris Paket B It\'s This Me.pdf',
            3 => 'https://modul.pkbm.id/paket-b/Modul 3 Bahasa Inggris Paket B What Time Is It.pdf',
            4 => 'https://modul.pkbm.id/paket-b/Modul 4 Bahasa Inggris Paket B This Is My World.pdf',
            5 => 'https://modul.pkbm.id/paket-b/Modul 5 Bahasa Inggris Paket B It\'s A Beautiful Day.pdf',
            6 => 'https://modul.pkbm.id/paket-b/Modul 6 Bahasa Inggris Paket B We Love What We Do.pdf',
            7 => 'https://modul.pkbm.id/paket-b/Modul 7 Bahasa Inggris Paket B I\'m Proud of Indonesia.pdf',
            8 => 'https://modul.pkbm.id/paket-b/Modul 8 Bahasa Inggris Paket B That\'s What Friends Are For.pdf',
        ],
        'ppkn' => [
            1 => 'https://modul.pkbm.id/paket-b/Modul 1 PPKn Paket B Perumusan dan Penetapan Pancasila sebagai Dasar Negara.pdf',
            2 => 'https://modul.pkbm.id/paket-b/Modul 2 PPKn Paket B Norma dan Keadilan.pdf',
            3 => 'https://modul.pkbm.id/paket-b/Modul 3 PPKn Paket B Perumusan dan Pengesahan UUD NRI Tahun 1945.pdf',
            4 => 'https://modul.pkbm.id/paket-b/Modul 4 PPKn Paket B Keberagaman Suku Agama Ras dan Antargolongan dalam Bingkai Bhinneka Tunggal Ika.pdf',
            5 => 'https://modul.pkbm.id/paket-b/Modul 5 PPKn Paket B Kerjasama dalam Berbagai Bidang Kehidupan.pdf',
            6 => 'https://modul.pkbm.id/paket-b/Modul 6 PPKn Paket B Karakteristik Daerah dalam Kerangka NKRI.pdf',
        ]
    ];

    $jenjang_upper = strtoupper(trim($jenjang));
    if ($jenjang_upper === 'SMP' || $jenjang_upper === 'PAKET B' || $jenjang_upper === 'PAKET_B') {
        if (isset($paket_b[$m][$bab_no])) {
            return $paket_b[$m][$bab_no];
        }
        if (isset($paket_b[$m][1])) {
            return $paket_b[$m][1];
        }
    }

    if (isset($paket_c[$m][$bab_no])) {
        return $paket_c[$m][$bab_no];
    }
    if (isset($paket_b[$m][$bab_no])) {
        return $paket_b[$m][$bab_no];
    }
    if (isset($paket_c[$m][1])) {
        return $paket_c[$m][1];
    }
    if (isset($paket_b[$m][1])) {
        return $paket_b[$m][1];
    }

    return "https://modul.pkbm.id/modul-paket-c.html";
}
