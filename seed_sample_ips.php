<?php
// Function ensureIpsSampleSeeded - Menjamin kurikulum model sample IPS Fase D selalu tersedia di database (lokal & Hostinger)
function ensureIpsSampleSeeded($conn) {
    if (!$conn) return;

    // Cek apakah bab IPS sudah terisi minimal 8 bab
    $chk = $conn->query("SELECT COUNT(*) AS total FROM elearning_bab WHERE mapel_nama = 'IPS'");
    if ($chk) {
        $r = $chk->fetch_assoc();
        if ((int)($r['total'] ?? 0) >= 8) {
            return; // Sudah ada dan lengkap
        }
    }

    // Bersihkan bab IPS lama jika tidak lengkap agar ter-upgrade
    $old_ips = $conn->query("SELECT id FROM elearning_bab WHERE mapel_nama = 'IPS'");
    if ($old_ips) {
        while ($ro = $old_ips->fetch_assoc()) {
            $old_id = (int)$ro['id'];
            $conn->query("DELETE FROM elearning_kuis WHERE bab_id = $old_id");
        }
        $conn->query("DELETE FROM elearning_bab WHERE mapel_nama = 'IPS'");
    }

$sample_ips_data = [
    // --- KELAS 7 (SEMESTER 1) ---
    [
        'nomor_bab' => 1,
        'tingkat_kelas' => '7',
        'semester' => '1',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 1: Manusia, Tempat, dan Lingkungan',
        'subjudul' => 'Konektivitas Antarruang, Letak Geografis, dan Potensi Sumber Daya Alam Indonesia',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Keruangan dan Konektivitas Antarruang',
        'tujuan_pembelajaran' => 'Santri mampu menganalisis letak geografis dan astronomis Indonesia, mengidentifikasi potensi sumber daya alam kemaritiman, serta menyimpulkan implikasi keruangan bagi peradaban masyarakat.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 1 IPS Paket B Manusia Tempat dan Lingkungan.pdf',
        'video_url' => 'https://www.youtube.com/embed/kYJjZqVnOis',
        'video_urls' => json_encode([
            ['title' => 'Letak & Luas Wilayah Indonesia (Kemdikbud)', 'url' => 'https://www.youtube.com/embed/kYJjZqVnOis'],
            ['title' => 'Kenapa Indonesia Sangat Kaya Sumber Daya Alam? (Kok Bisa)', 'url' => 'https://www.youtube.com/embed/FqE8E_cRz-g'],
            ['title' => 'Interaksi Antarruang dan Dampaknya bagi Kehidupan', 'url' => 'https://www.youtube.com/embed/aGk7xGvO6vI']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Konsep Ruang dan Interaksi Antarruang' => 'Ruang adalah tempat di permukaan bumi yang mencakup udara, lapisan tanah, dan perairan. Interaksi terjadi karena perbedaan potensi sumber daya alam antardaerah (saling melengkapi / regional complementary).',
            '2. Letak Geografis & Astronomis Indonesia' => 'Secara astronomis Indonesia terletak di antara 6°LU - 11°LS dan 95°BT - 141°BT (beriklim tropis). Secara geografis diapit 2 samudra (Pasifik & Hindia) dan 2 benua (Asia & Australia) menjadikannya jalur perdagangan dunia strategis.',
            '3. Potensi Kemaritiman & Sumber Daya Hutan' => 'Laut Indonesia mencakup 70% luas wilayah dengan kekayaan terumbu karang, perikanan tangkap, dan mangrove. Hutan hujan tropis Indonesia menyimpan keanekaragaman hayati nomor dua terbesar di dunia.',
            '4. Integrasi Nilai Keislaman & Syukur' => 'Kekayaan tanah dan air nusantara adalah amanah dari Allah SWT (QS. Al-Mulk: 15) yang wajib dikelola dengan prinsip keadilan, kelestarian lingkungan, dan tanpa keserakahan (ishraf).'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 1: Observasi Sumber Daya Alam di Sekitar Pondok / Rumah',
        'lks_tugas' => "1. Amati 3 jenis bahan makanan di dapur atau kantin pondokmu (misal: beras, ikan laut, sayuran).\n2. Telusuri dari daerah mana komoditas tersebut didatangkan dan mengapa daerah tersebut menghasilkannya.\n3. Jelaskan bagaimana interaksi antarruang terjadi dalam memenuhi kebutuhan makan sehari-hari.",
        'kuis' => [
            [
                'soal' => 'Wilayah pegunungan menghasilkan aneka sayuran segar, sedangkan wilayah pesisir menghasilkan ikan laut. Terjadinya pertukaran barang antarkedua wilayah tersebut membuktikan adanya konsep...',
                'opsi_a' => 'Interaksi antarruang karena saling melengkapi (complementary)',
                'opsi_b' => 'Persaingan ekonomi tertutup antardaerah',
                'opsi_c' => 'Kemunduran teknologi pertanian lokal',
                'opsi_d' => 'Ketergantungan pasif tanpa adanya keuntungan timbal balik',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Perbedaan komoditas akibat kondisi geografis memicu terjadinya interaksi antarruang dengan prinsip saling melengkapi (regional complementary) untuk memenuhi kebutuhan kedua belah pihak.'
            ],
            [
                'soal' => 'Secara astronomis Indonesia terletak di antara 6°LU – 11°LS dan 95°BT – 141°BT. Salah satu pengaruh utama dari letak lintang tersebut adalah...',
                'opsi_a' => 'Indonesia memiliki iklim tropis dengan curah hujan dan sinar matahari sepanjang tahun',
                'opsi_b' => 'Indonesia terbagi menjadi empat musim yang berbeda secara ekstrem',
                'opsi_c' => 'Indonesia sering mengalami musim dingin dan salju lebat di dataran rendah',
                'opsi_d' => 'Waktu di Indonesia bagian barat sama persis dengan waktu di bagian timur',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Letak astronomis di sekitar garis khatulistiwa (antara 6°LU - 11°LS) menyebabkan Indonesia beriklim tropis dengan suhu hangat dan penyinaran matahari sepanjang tahun.'
            ],
            [
                'soal' => 'Indonesia berada pada posisi silang antara Benua Asia dan Benua Australia serta Samudra Pasifik dan Samudra Hindia. Dampak positif posisi geografis ini bagi perekonomian bangsa adalah...',
                'opsi_a' => 'Menjadi jalur lalu lintas pelayaran dan perdagangan internasional yang ramai',
                'opsi_b' => 'Terisolasinya pasar domestik dari pengaruh luar negeri',
                'opsi_c' => 'Menurunnya minat investasi asing di sektor pelabuhan',
                'opsi_d' => 'Sulitnya menjalin hubungan diplomasi dengan negara tetangga',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Posisi silang dunia menjadikan selat-selat strategis di Indonesia (seperti Selat Malaka, Sunda, dan Lombok) sebagai arteri perdagangan dan transportasi internasional.'
            ],
            [
                'soal' => 'Hutan mangrove memiliki peran ekologis yang sangat penting bagi wilayah pesisir pantai di Indonesia, yaitu untuk...',
                'opsi_a' => 'Mencegah abrasi air laut dan menjadi habitat biota laut',
                'opsi_b' => 'Dijadikan bahan bakar utama kapal-kapal niaga modern',
                'opsi_c' => 'Mengurangi kadar oksigen di sekitar pantai',
                'opsi_d' => 'Mempercepat pengikisan tanah oleh gelombang badai',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Akar mangrove mengikat lumpur pesisir pantai sehingga menahan hantaman ombak (mencegah abrasi) dan menjadi tempat pemijahan alami ikan serta kepiting.'
            ],
            [
                'soal' => 'Sikap yang paling selaras dengan prinsip adab islami dalam mensyukuri kekayaan alam nusantara adalah...',
                'opsi_a' => 'Memanfaatkan sumber daya alam secara bijak, melestarikan alam, dan tidak merusak lingkungan',
                'opsi_b' => 'Mengeksploitasi seluruh hutan secepat mungkin demi keuntungan pribadi sesaat',
                'opsi_c' => 'Membuang limbah industri ke sungai tanpa pengolahan karena sungai luas',
                'opsi_d' => 'Mengabaikan kepunahan flora fauna langka karena tidak menguntungkan finansial',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Islam mengajarkan bahwa manusia adalah khalifah di bumi yang bertugas menjaga kelestarian dan dilarang membuat kerusakan (fasad fi al-ardh).'
            ]
        ]
    ],

    // --- KELAS 7 (SEMESTER 1) ---
    [
        'nomor_bab' => 2,
        'tingkat_kelas' => '7',
        'semester' => '1',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 2: Interaksi Sosial dan Lembaga Sosial',
        'subjudul' => 'Syarat dan Bentuk Interaksi Sosial, Asosiatif vs Disosiatif, dan Peran Lembaga Sosial',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Dinamika Sosial Kemasyarakatan',
        'tujuan_pembelajaran' => 'Santri dapat menjelaskan hakikat interaksi sosial, membedakan proses asosiatif dan disosiatif, serta menguraikan fungsi lembaga keluarga, agama, ekonomi, pendidikan, dan politik.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 2 IPS Paket B Interaksi Sosial dan Lembaga Sosial.pdf',
        'video_url' => 'https://www.youtube.com/embed/Y7B2Pj8sQ6A',
        'video_urls' => json_encode([
            ['title' => 'Bentuk-Bentuk Interaksi Sosial Lengkap (Rumah Belajar)', 'url' => 'https://www.youtube.com/embed/Y7B2Pj8sQ6A'],
            ['title' => 'Lembaga Sosial & Peranannya dalam Menjaga Keteraturan', 'url' => 'https://www.youtube.com/embed/hGvj1J3g7qI']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Syarat Terjadinya Interaksi Sosial' => 'Interaksi sosial mensyaratkan adanya kontak sosial (langsung/tidak langsung) dan komunikasi (penyampaian dan penafsiran pesan).',
            '2. Bentuk Interaksi Asosiatif' => 'Proses yang mengarah pada persatuan dan harmoni: Kerja sama (Gotong royong), Akomodasi (Penyelesaian konflik), dan Asimilasi/Akulturasi budaya.',
            '3. Bentuk Interaksi Disosiatif' => 'Proses yang mengarah pada perpecahan atau perebutan: Persaingan (Kompetisi sehat), Kontravensi (Ketidaksukaan tersembunyi), dan Pertentangan/Konflik.',
            '4. Peran Lembaga Sosial' => 'Lembaga keluarga adalah pondasi sosialisasi pertama; lembaga agama menanamkan pedoman akhlak mulia; lembaga pendidikan membekali ilmu dan keterampilan.'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 2: Analisis Bentuk Interaksi Sosial di Asrama Santri',
        'lks_tugas' => "Catatlah 2 contoh interaksi sosial asosiatif yang kamu lakukan bersama teman asrama pekan ini (misal: piket bersama, belajar kelompok). Jelaskan bagaimana interaksi tersebut mempererat ukhuwah islamiyah!",
        'kuis' => [
            [
                'soal' => 'Dua orang santri saling bertegur sapa, berjabat tangan, dan bertukar kabar saat bertemu di koridor asrama. Peristiwa ini menunjukkan telah terpenuhinya dua syarat interaksi sosial, yaitu...',
                'opsi_a' => 'Kontak sosial dan komunikasi',
                'opsi_b' => 'Status sosial dan jabatan',
                'opsi_c' => 'Perbedaan suku dan daerah asal',
                'opsi_d' => 'Kompetisi dan pertentangan',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Syarat mutlak terjadinya interaksi sosial menurut sosiologi adalah adanya kontak sosial dan komunikasi timbal balik antarpihak.'
            ],
            [
                'soal' => 'Santri dari berbagai kamar bekerja sama membersihkan masjid dan lingkungan pondok dalam rangka kerja bakti Jumat Bersih. Bentuk interaksi sosial ini tergolong ke dalam...',
                'opsi_a' => 'Proses asosiatif dalam bentuk kerja sama (kooperasi)',
                'opsi_b' => 'Proses disosiatif dalam bentuk persaingan tersembunyi',
                'opsi_c' => 'Pertentangan terbuka antarkelompok asrama',
                'opsi_d' => 'Kontravensi sosial yang merugikan',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Kerja sama (gotong royong) adalah proses asosiatif utama yang menyatukan anggota kelompok untuk mencapai tujuan bersama.'
            ],
            [
                'soal' => 'Lembaga sosial yang memiliki fungsi utama menanamkan nilai-nilai moral, keimanan, dan pedoman hidup bagi manusia dalam berhubungan dengan Tuhan dan sesama adalah...',
                'opsi_a' => 'Lembaga agama',
                'opsi_b' => 'Lembaga ekonomi pasar modal',
                'opsi_c' => 'Lembaga politik parlemen',
                'opsi_d' => 'Lembaga perbankan syariah',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Lembaga agama berfungsi mengatur hubungan vertikal (dengan Allah SWT) dan horizontal (dengan sesama makhluk hidup).'
            ],
            [
                'soal' => 'Ketika dua orang santri berselisih paham mengenai giliran tugas piket, ketua kamar hadir sebagai penengah yang adil untuk mendamaikan keduanya. Upaya akomodasi ini disebut...',
                'opsi_a' => 'Mediasi',
                'opsi_b' => 'Koersi',
                'opsi_c' => 'Konflik terbuka',
                'opsi_d' => 'Ajudikasi pengadilan negeri',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Mediasi adalah penyelesaian sengketa dengan bantuan pihak ketiga yang netral sebagai penasihat tanpa memaksakan keputusan final secara hukum.'
            ],
            [
                'soal' => 'Nilai islami manakah yang paling mendasari hubungan interaksi sosial yang harmonis dalam masyarakat majemuk?',
                'opsi_a' => 'Ukhuwah, tasamuh (toleransi), dan ta\'awun (tolong-menolong)',
                'opsi_b' => 'Ashabiyah (fanatisme golongan yang sempit)',
                'opsi_c' => 'Takabur dan merasa diri paling unggul dibanding orang lain',
                'opsi_d' => 'Ghibah dan namimah (mengadu domba sesama saudara)',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Islam menjunjung tinggi ukhuwah (persaudaraan), tasamuh (saling menghormati perbedaan), dan ta\'awun (tolong-menolong dalam kebaikan).'
            ]
        ]
    ],

    // --- KELAS 7 (SEMESTER 2) ---
    [
        'nomor_bab' => 3,
        'tingkat_kelas' => '7',
        'semester' => '2',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 3: Aktivitas Manusia dalam Memenuhi Kebutuhan',
        'subjudul' => 'Kelangkaan, Kebutuhan Hidup, Motif dan Prinsip Ekonomi, serta Mekanisme Pasar',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Perilaku Ekonomi dan Pasar',
        'tujuan_pembelajaran' => 'Santri mampu mengkaji faktor penyebab kelangkaan, menyusun skala prioritas kebutuhan, menerapkan prinsip ekonomi yang syar\'i, serta memahami pembentukan harga pasar.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 3 IPS Paket B Aktivitas Manusia dalam Memenuhi Kebutuhan.pdf',
        'video_url' => 'https://www.youtube.com/embed/0k5FkUf4Xrk',
        'video_urls' => json_encode([
            ['title' => 'Konsep Kelangkaan dan Kebutuhan Manusia (Rumah Belajar)', 'url' => 'https://www.youtube.com/embed/0k5FkUf4Xrk'],
            ['title' => 'Permintaan, Penawaran, dan Harga Keseimbangan Pasar', 'url' => 'https://www.youtube.com/embed/2rQp6rQjGjY']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Hakikat Kelangkaan (Scarcity)' => 'Kebutuhan manusia tidak terbatas, sementara alat pemuas kebutuhan (barang dan jasa) jumlahnya terbatas. Solusinya adalah menentukan skala prioritas.',
            '2. Jenis-Jenis Kebutuhan' => 'Berdasarkan intensitasnya: Primer (pokok seperti sandang, pangan, papan), Sekunder (pelengkap seperti meja belajar), dan Tersier (kemewahan).',
            '3. Tindakan, Motif, dan Prinsip Ekonomi' => 'Prinsip ekonomi: dengan pengorbanan tertentu untuk memperoleh hasil yang maksimal secara halal dan tidak merugikan orang lain.',
            '4. Etika Bisnis Islam' => 'Aktivitas ekonomi harus dilandasi kejujuran (shiddiq), amanah, bebas dari riba, gharar (ketidakjelasan), dan maysir (judi).'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 3: Menyusun Tabel Skala Prioritas Uang Saku Santri',
        'lks_tugas' => "Buatlah daftar 5 kebutuhan pribadimu selama sebulan di pondok. Urutkan berdasarkan tingkat kepentingannya (Primer, Sekunder, Tersier), dan hitung alokasi tabungan untuk sedekah!",
        'kuis' => [
            [
                'soal' => 'Inti masalah ekonomi yang dihadapi oleh seluruh umat manusia di dunia adalah...',
                'opsi_a' => 'Kebutuhan manusia yang tidak terbatas sedangkan alat pemuas kebutuhan sifatnya terbatas',
                'opsi_b' => 'Terlalu banyaknya uang beredar di masyarakat luas',
                'opsi_c' => 'Semua barang pemuas kebutuhan dapat diperoleh secara gratis di alam',
                'opsi_d' => 'Kebutuhan manusia selalu sama dari zaman dahulu hingga sekarang',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Masalah pokok ekonomi adalah kelangkaan (scarcity), yaitu kesenjangan antara kebutuhan yang tanpa batas dengan ketersediaan sumber daya yang terbatas.'
            ],
            [
                'soal' => 'Seorang santri memutuskan untuk membeli kitab rujukan pelajaran daripada membeli camilan ekstra di kantin. Tindakan santri tersebut didasarkan pada penerapan...',
                'opsi_a' => 'Penyusunan skala prioritas kebutuhan primer atas kebutuhan pelengkap',
                'opsi_b' => 'Sikap kikir yang berlebihan dalam membelanjakan uang',
                'opsi_c' => 'Keinginan menimbun uang saku tanpa tujuan yang jelas',
                'opsi_d' => 'Pengabaian terhadap kesehatan fisik pribadi',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Mendahulukan kebutuhan pokok belajar di atas keinginan konsumtif adalah cerminan kecerdasan mengelola skala prioritas kebutuhan.'
            ],
            [
                'soal' => 'Bunyi hukum permintaan dalam ilmu ekonomi konvensional menyatakan bahwa...',
                'opsi_a' => 'Jika harga suatu barang naik, maka jumlah barang yang diminta akan turun (ceteris paribus)',
                'opsi_b' => 'Jika harga barang naik, maka jumlah barang yang diminta juga ikut melonjak naik',
                'opsi_c' => 'Harga barang sama sekali tidak memengaruhi keputusan konsumen dalam membeli',
                'opsi_d' => 'Produsen akan selalu menurunkan harga saat persediaan barang di gudang habis',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Hukum permintaan berbanding terbalik: saat harga naik, permintaan pembeli menurun; saat harga turun, permintaan pembeli meningkat (ceteris paribus).'
            ],
            [
                'soal' => 'Dalam perspektif ekonomi syariah, pedagang dilarang keras melakukan praktik penimbunan barang pokok (ikhtikar) karena...',
                'opsi_a' => 'Menimbulkan kelangkaan buatan dan mencekik konsumen dengan harga yang sangat mahal',
                'opsi_b' => 'Membuat gudang pedagang menjadi terlalu bersih dan rapi',
                'opsi_c' => 'Membantu masyarakat miskin mendapatkan barang secara adil',
                'opsi_d' => 'Dapat meningkatkan berkah keuntungan pedagang secara halal',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Ikhtikar (menimbun barang agar harga melonjak) diharamkan dalam Islam karena menimbulkan kemudaratan sosial yang zalim.'
            ],
            [
                'soal' => 'Pasar tempat bertemunya penjual dan pembeli secara tidak langsung melalui aplikasi digital modern di internet disebut...',
                'opsi_a' => 'Pasar daring (online market / e-commerce)',
                'opsi_b' => 'Pasar tradisional barter zaman batu',
                'opsi_c' => 'Pasar monopsoni tertutup',
                'opsi_d' => 'Pasar gelap komoditas terlarang',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'E-commerce memfasilitasi transaksi jual beli digital tanpa mengharuskan tatap muka fisik di lokasi yang sama.'
            ]
        ]
    ],

    // --- KELAS 7 (SEMESTER 2) ---
    [
        'nomor_bab' => 4,
        'tingkat_kelas' => '7',
        'semester' => '2',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 4: Kehidupan Masyarakat Praaksara, Hindu-Buddha, dan Islam',
        'subjudul' => 'Periodisasi Praaksara, Jalur Rempah, dan Masuknya Peradaban Islam ke Nusantara',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Perkembangan Peradaban Sejarah Nusantara',
        'tujuan_pembelajaran' => 'Santri mampu merekonstruksi corak kehidupan praaksara, menganalisis akulturasi kebudayaan Hindu-Buddha, dan membuktikan saluran islamisasi yang damai di Nusantara.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 4 IPS Paket B Kehidupan Masyarakat Praaksara Hindu-Buddha dan Islam.pdf',
        'video_url' => 'https://www.youtube.com/embed/Xh0gJqLqY0o',
        'video_urls' => json_encode([
            ['title' => 'Periodisasi Zaman Praaksara Indonesia (Rumah Belajar)', 'url' => 'https://www.youtube.com/embed/Xh0gJqLqY0o'],
            ['title' => 'Masuk dan Berkembangnya Islam di Nusantara (Sejarah Lengkap)', 'url' => 'https://www.youtube.com/embed/x7K9j9kPqY8']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Zaman Praaksara di Indonesia' => 'Masa sebelum manusia mengenal tulisan. Terbagi dalam masa berburu-meramu (Paleolitikum/Mesolitikum), bercocok tanam (Neolitikum), dan perundagian (zaman logam).',
            '2. Masa Pengaruh Hindu-Buddha' => 'Masuk melalui jalur perdagangan rempah maritim. Melahirkan kerajaan seperti Kutai, Sriwijaya, Tarumanegara, dan Majapahit.',
            '3. Saluran Islamisasi di Nusantara' => 'Islam masuk dan berkembang secara damai tanpa pedang melalui: Saluran Perdagangan (saudagar Arab, Gujarat, Persia), Perkawinan, Pendidikan Pesantren, Kesenian/Budaya (Wali Songo), dan Tasawuf.',
            '4. Kerajaan-Kerajaan Islam Nusantara' => 'Samudera Pasai (kerajaan Islam pertama di Indonesia), Demak di Jawa, Aceh Darussalam, Mataram Islam, Gowa-Tallo di Sulawesi, dan Ternate-Tidore di Maluku.'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 4: Telaah Saluran Masuknya Islam di Daerah Asalmu',
        'lks_tugas' => "Tuliskan jejak peninggalan sejarah Islam di daerah asalmu (masjid kuno, makam ulama, naskah kuno, atau tradisi keislaman). Ceritakan bagaimana saluran dakwah dilakukan para ulama terdahulu!",
        'kuis' => [
            [
                'soal' => 'Masa sebelum manusia di suatu peradaban mengenal tulisan disebut sebagai masa...',
                'opsi_a' => 'Praaksara (Nirleka)',
                'opsi_b' => 'Masa aksara modern',
                'opsi_c' => 'Masa kolonialisme eropa',
                'opsi_d' => 'Masa reformasi digital',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Praaksara berasal dari kata pra (sebelum) dan aksara (tulisan), yaitu era ketika manusia purba belum mengenal sistem tulisan tertulis.'
            ],
            [
                'soal' => 'Candi Borobudur dan Candi Prambanan merupakan bukti nyata peninggalan peradaban masa Hindu-Buddha di Nusantara yang mencerminkan...',
                'opsi_a' => 'Tingginya keahlian arsitektur dan akulturasi budaya dengan kearifan lokal',
                'opsi_b' => 'Bangunan benteng pertahanan militer dari serangan luar angkasa',
                'opsi_c' => 'Tempat pemakaman seluruh raja dari benua Afrika',
                'opsi_d' => 'Gudang penyimpanan gandum dan rempah impor',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Pembangunan candi di Nusantara memadukan konsep seni India dengan punden berundak khas budaya asli Nusantara (akulturasi arsitektur).'
            ],
            [
                'soal' => 'Metode dakwah para Wali Songo dalam menyebarkan ajaran Islam di tanah Jawa sangat diterima luas oleh masyarakat karena...',
                'opsi_a' => 'Menggunakan pendekatan budaya, wayang, seni tembang, dan akhlak yang luhur serta penuh hikmah',
                'opsi_b' => 'Melakukan peperangan dan pemaksaan keyakinan kepada rakyat jelata',
                'opsi_c' => 'Menolak seluruh adat istiadat setempat tanpa dialog yang baik',
                'opsi_d' => 'Menarik upeti emas yang sangat besar bagi orang yang ingin belajar Islam',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Dakwah Wali Songo menerapkan prinsip bil hikmah wal mau\'idhatil hasanah melalui pendekatan kultural yang santun dan membumi.'
            ],
            [
                'soal' => 'Kerajaan Islam pertama yang tercatat dalam sejarah Nusantara dan terletak di pesisir utara pulau Sumatra adalah...',
                'opsi_a' => 'Kesultanan Samudera Pasai',
                'opsi_b' => 'Kerajaan Singasari',
                'opsi_c' => 'Kesultanan Pajang',
                'opsi_d' => 'Kerajaan Kutai Martapura',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Samudera Pasai yang dipimpin Sultan Malik as-Saleh (abad ke-13) tercatat sebagai kesultanan Islam tertua di Nusantara.'
            ],
            [
                'soal' => 'Lembaga pendidikan tradisional khas Nusantara yang didirikan oleh para ulama untuk mendidik generasi santri dan menjadi benteng moral bangsa adalah...',
                'opsi_a' => 'Pondok Pesantren',
                'opsi_b' => 'Sekolah kolonial Belanda STOVIA',
                'opsi_c' => 'Akademi militer VOC',
                'opsi_d' => 'Universitas industri eropa',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Pesantren adalah institusi pendidikan tertua di Indonesia yang diwariskan para wali dan ulama untuk mentransfer ilmu agama dan kebangsaan.'
            ]
        ]
    ],

    // --- KELAS 8 (SEMESTER 1) ---
    [
        'nomor_bab' => 5,
        'tingkat_kelas' => '8',
        'semester' => '1',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 5: Pengaruh Interaksi Sosial terhadap Kehidupan Sosial dan Kebangsaan',
        'subjudul' => 'Mobilitas Sosial, Pluralitas Masyarakat Indonesia, Konflik, dan Integrasi Bangsa',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Mobilitas dan Integrasi Sosial',
        'tujuan_pembelajaran' => 'Santri dapat menganalisis bentuk-bentuk mobilitas sosial, menghargai pluralitas agama dan suku, serta memecahkan potensi konflik menjadi integrasi sosial.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 5 IPS Paket B Pengaruh Interaksi Sosial terhadap Kehidupan Sosial dan Kebangsaan.pdf',
        'video_url' => 'https://www.youtube.com/embed/gT8jL2kPqYo',
        'video_urls' => json_encode([
            ['title' => 'Mobilitas Sosial: Vertikal dan Horizontal (Kemdikbud)', 'url' => 'https://www.youtube.com/embed/gT8jL2kPqYo'],
            ['title' => 'Pluralitas dan Menjaga Integrasi Bangsa Indonesia', 'url' => 'https://www.youtube.com/embed/mK0jL8qYpRo']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Mobilitas Sosial' => 'Perpindahan posisi seseorang atau sekelompok orang dari lapisan sosial satu ke lapisan sosial lain. Terbagi menjadi mobilitas sosial vertikal (naik/turun) dan horizontal (sejajar).',
            '2. Saluran Mobilitas Sosial' => 'Pendidikan adalah saluran paling utama bagi seseorang untuk meningkatkan derajat hidup dan kehormatan keluarganya.',
            '3. Pluralitas Masyarakat Indonesia' => 'Keragaman suku bangsa, bahasa daerah, dan budaya diikat oleh semboyan Bhinneka Tunggal Ika.',
            '4. Resolusi Konflik Menuju Integrasi' => 'Konflik dapat diselesaikan melalui konsiliasi, mediasi, dan arbitrase untuk mencapai integrasi nasional yang kokoh.'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 5: Kisah Inspiratif Mobilitas Sosial Santri',
        'lks_tugas' => "Tuliskan biografi singkat seorang tokoh ulama atau ilmuwan Muslim yang berasal dari keluarga sederhana namun berhasil mencapai posisi terhormat di masyarakat berkat ketekunan menuntut ilmu!",
        'kuis' => [
            [
                'soal' => 'Seorang anak petani desa yang tekun belajar di pondok pesantren berhasil menyelesaikan pendidikan tinggi hingga menjadi dokter spesialis yang dermawan. Fenomena ini merupakan contoh dari...',
                'opsi_a' => 'Mobilitas sosial vertikal ke atas (social climbing)',
                'opsi_b' => 'Mobilitas sosial vertikal ke bawah (social sinking)',
                'opsi_c' => 'Mobilitas sosial horizontal antardaerah',
                'opsi_d' => 'Stagnasi kedudukan sosial tanpa perubahan',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Social climbing adalah naiknya derajat atau kedudukan seseorang ke tingkat lapisan sosial yang lebih tinggi di masyarakat.'
            ],
            [
                'soal' => 'Saluran mobilitas sosial yang sering disebut sebagai jembatan emas (social elevator) bagi masyarakat miskin untuk merubah nasib hidupnya adalah...',
                'opsi_a' => 'Lembaga pendidikan formal dan pondok pesantren',
                'opsi_b' => 'Organisasi kejahatan terorganisir',
                'opsi_c' => 'Perjudian nasib di kasino internasional',
                'opsi_d' => 'Pasar gelap komoditas ilegal',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Pendidikan memberikan bekal keilmuan, adab, dan keahlian sehingga seseorang mampu bersaing dan meningkatkan harkat hidupnya secara terhormat.'
            ],
            [
                'soal' => 'Sikap yang benar dari seorang muslim dalam memandang keberagaman suku dan bahasa daerah di Indonesia adalah...',
                'opsi_a' => 'Memandang keberagaman sebagai tanda kekuasaan Allah (ayatullah) yang patut disyukuri dan dihormati',
                'opsi_b' => 'Meremehkan suku lain yang memiliki adat berbeda dari dirinya',
                'opsi_c' => 'Memaksa semua orang menggunakan satu adat budaya daerah tertentu',
                'opsi_d' => 'Menghindari bergaul dengan orang yang berbeda suku',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Perbedaan bahasa dan warna kulit manusia adalah salah satu tanda kebesaran Allah SWT sebagaimana firman-Nya dalam QS. Ar-Rum: 22.'
            ],
            [
                'soal' => 'Proses penyesuaian unsur-unsur yang berbeda dalam masyarakat sehingga menghasilkan pola kehidupan yang serasi dan harmonis disebut...',
                'opsi_a' => 'Integrasi sosial',
                'opsi_b' => 'Disintegrasi bangsa',
                'opsi_c' => 'Segregasi rasial tertutup',
                'opsi_d' => 'Polarisasi permusuhan',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Integrasi sosial terwujud ketika kelompok-kelompok yang berbeda sepakat memelihara keteraturan dan nilai-nilai bersama.'
            ],
            [
                'soal' => 'Faktor pendorong utama yang memudahkan seseorang melakukan mobilitas sosial vertikal di era modern adalah...',
                'opsi_a' => 'Keterbukaan akses pendidikan dan penguasaan teknologi ilmu pengetahuan',
                'opsi_b' => 'Sistem kasta tertutup yang melarang perkawinan antarkelas',
                'opsi_c' => 'Ketergantungan nasib pada garis keturunan bangsawan',
                'opsi_d' => 'Larangan bagi rakyat jelata untuk membaca buku',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Masyarakat modern yang terbuka memungkinkan siapa saja yang cakap berilmu untuk naik kedudukan sosialnya.'
            ]
        ]
    ],

    // --- KELAS 8 (SEMESTER 2) ---
    [
        'nomor_bab' => 6,
        'tingkat_kelas' => '8',
        'semester' => '2',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 6: Keunggulan dan Keterbatasan Antarruang serta Pengaruhnya',
        'subjudul' => 'Perdagangan Antardaerah dan Antarnegara, Ekonomi Maritim, dan Agrikultur Indonesia',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Ekonomi Maritim dan Antarwilayah',
        'tujuan_pembelajaran' => 'Santri dapat membedakan ekonomi maritim dan kelautan, menganalisis peluang ekspor-impor Indonesia, serta merumuskan strategi penguatan sektor agrikultur pangan nasional.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 6 IPS Paket B Keunggulan dan Keterbatasan Antarruang serta Pengaruhnya.pdf',
        'video_url' => 'https://www.youtube.com/embed/vK7jL9qPqYo',
        'video_urls' => json_encode([
            ['title' => 'Potensi Ekonomi Maritim Indonesia (Kemdikbud)', 'url' => 'https://www.youtube.com/embed/vK7jL9qPqYo'],
            ['title' => 'Perdagangan Antarpulau dan Perdagangan Internasional', 'url' => 'https://www.youtube.com/embed/zR7jL3kPqYo']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Keunggulan Geografis Ekonomi Antarruang' => 'Indonesia memiliki kekayaan laut (maritim) dan daratan yang subur (agrikultur) yang saling melengkapi antarpulau.',
            '2. Perdagangan Antarpulau vs Antarnegara' => 'Perdagangan antarpulau menggunakan mata uang rupiah dan tanpa bea cukai; perdagangan antarnegara (ekspor-impor) melibatkan valuta asing dan regulasi kepabeanan.',
            '3. Ekonomi Kelautan vs Ekonomi Maritim' => 'Ekonomi kelautan (marine economy) berfokus pada pemanfaatan sumber daya laut (perikanan, tambang laut). Ekonomi maritim (maritime economy) berfokus pada transportasi laut, galangan kapal, dan pelabuhan.',
            '4. Penguatan Ketahanan Pangan Agrikultur' => 'Pertanian dan perkebunan adalah pilar kedaulatan bangsa untuk menjamin kemandirian pangan tanpa ketergantungan impor.'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 6: Analisis Potensi Ekspor Komoditas Nusantara',
        'lks_tugas' => "Pilihlah salah satu komoditas rempah atau perkebunan unggulan Indonesia (misal: kopi, cengkeh, kelapa sawit, kakao). Jelaskan ke negara mana saja komoditas tersebut diekspor dan bagaimana dampaknya bagi devisa negara!",
        'kuis' => [
            [
                'soal' => 'Kegiatan mengirimkan atau menjual barang hasil produksi dari dalam negeri ke pasar luar negeri disebut...',
                'opsi_a' => 'Ekspor',
                'opsi_b' => 'Impor',
                'opsi_c' => 'Barter lokal',
                'opsi_d' => 'Konsumsi domestik',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Ekspor adalah perdagangan internasional dengan menjual produk dalam negeri ke mancanegara yang menghasilkan devisa.'
            ],
            [
                'soal' => 'Manakah di bawah ini yang merupakan contoh kegiatan ekonomi maritim (maritime economy)?',
                'opsi_a' => 'Industri galangan dan perawatan kapal serta pengelolaan pelabuhan peti kemas',
                'opsi_b' => 'Petani yang menanam padi di sawah tadah hujan dataran tinggi',
                'opsi_c' => 'Penambangan batu bara di hutan pedalaman pulau Kalimantan',
                'opsi_d' => 'Penebangan kayu jati di perkebunan daratan Jawa',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Ekonomi maritim mencakup industri perkapalan, jasa navigasi, dan logistik kepelabuhanan.'
            ],
            [
                'soal' => 'Salah satu kendala utama dalam pengembangan sektor agrikultur pertanian tradisional di Indonesia adalah...',
                'opsi_a' => 'Skala kepemilikan lahan petani yang sempit dan keterbatasan teknologi pascapanen',
                'opsi_b' => 'Tanah Indonesia yang terlalu gersang dan tidak bisa ditumbuhi tanaman',
                'opsi_c' => 'Tidak adanya sinar matahari yang menyinari kepulauan Nusantara',
                'opsi_d' => 'Masyarakat Indonesia yang sama sekali tidak membutuhkan makanan pokok',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Mayoritas petani kecil memiliki lahan kurang dari 0,5 hektar dan minim modal modernisasi teknologi panen.'
            ],
            [
                'soal' => 'Faktor pendorong terjadinya perdagangan antarpulau di wilayah Negara Kesatuan Republik Indonesia adalah...',
                'opsi_a' => 'Perbedaan potensi sumber daya alam dan tingkat harga komoditas antardaerah',
                'opsi_b' => 'Larangan warga pulau Jawa untuk makan ikan laut',
                'opsi_c' => 'Kesamaan mutlak seluruh tanah dan iklim di setiap pulau',
                'opsi_d' => 'Penutupan seluruh akses dermaga pelabuhan lokal',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Perbedaan komoditas pulau satu dengan pulau lain mendorong terjadinya arus niaga antardaerah.'
            ],
            [
                'soal' => 'Kebijakan yang dapat dilakukan pemerintah untuk melindungi petani lokal dari anjloknya harga hasil panen adalah...',
                'opsi_a' => 'Menetapkan harga pembelian pemerintah (HPP) yang layak dan membatasi impor saat panen raya',
                'opsi_b' => 'Mengimpor beras besar-besaran saat petani sedang memanen padinya',
                'opsi_c' => 'Menaikkan pajak pupuk bersubsidi hingga petani tidak mampu bertani',
                'opsi_d' => 'Menutup seluruh pasar induk tradisional di perkotaan',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Perlindungan petani melalui regulasi HPP dan pengendalian impor menjaga ketahanan pangan dan kesejahteraan petani.'
            ]
        ]
    ],

    // --- KELAS 9 (SEMESTER 1) ---
    [
        'nomor_bab' => 7,
        'tingkat_kelas' => '9',
        'semester' => '1',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 7: Perubahan Keruangan dan Interaksi Antarruang Negara Asia dan Benua Lainnya',
        'subjudul' => 'Karakteristik Benua Asia, Amerika, Eropa, Afrika, Australia, dan Pengaruh Interaksi Global',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Karakteristik Keruangan Global dan Geopolitik',
        'tujuan_pembelajaran' => 'Santri mampu membandingkan letak dan karakteristik benua-benua di dunia, mengidentifikasi potensi sumber daya alamnya, serta menganalisis dampak interaksi global bagi Indonesia.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 7 IPS Paket B Perubahan Keruangan dan Interaksi Antarruang Negara Asia.pdf',
        'video_url' => 'https://www.youtube.com/embed/dF9kL2mPqYo',
        'video_urls' => json_encode([
            ['title' => 'Mengenal Karakteristik 5 Benua di Dunia (Kemdikbud)', 'url' => 'https://www.youtube.com/embed/dF9kL2mPqYo'],
            ['title' => 'Interaksi Keruangan Antarnegara di Era Globalisasi', 'url' => 'https://www.youtube.com/embed/jK7mQ8lPqYo']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Benua Terbesar di Dunia (Benua Asia)' => 'Benua Asia adalah benua terluas dengan populasi terbesar di dunia. Menjadi tempat lahirnya agama-agama besar dunia (Islam, Kristen, Hindu, Buddha).',
            '2. Karakteristik Benua Lain' => 'Benua Amerika (Benua Merah, membentang dari kutub utara ke selatan), Benua Eropa (Benua Biru), Benua Afrika (Benua Hitam dengan gurun terluas Sahara), dan Benua Australia (benua terkecil).',
            '3. Pengaruh Interaksi Antarnegara' => 'Perdagangan internasional memicu alih teknologi, perpindahan tenaga kerja terampil, dan konvergensi kebudayaan.',
            '4. Posisi Geopolitik Umat Islam Global' => 'Mayoritas populasi muslim dunia berada di kawasan Asia (termasuk Indonesia sebagai negara berpenduduk muslim terbesar), Asia Selatan, dan Timur Tengah.'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 7: Perbandingan Profil Dua Negara di Benua Asia',
        'lks_tugas' => "Pilihlah dua negara di Benua Asia (misal: Indonesia dan Jepang, atau Indonesia dan Arab Saudi). Bandingkan sumber daya alam, mata pencaharian penduduk, dan bentuk kerja sama bilateral keduanya!",
        'kuis' => [
            [
                'soal' => 'Benua terluas di permukaan bumi yang juga memiliki jumlah populasi penduduk terbesar adalah Benua...',
                'opsi_a' => 'Asia',
                'opsi_b' => 'Australia',
                'opsi_c' => 'Eropa',
                'opsi_d' => 'Antarktika',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Benua Asia mencakup luas sekitar 44,58 juta km² dan dihuni oleh lebih dari 60% populasi umat manusia di dunia.'
            ],
            [
                'soal' => 'Alasan benua Eropa sering dijuluki sebagai "Benua Biru" oleh para pengamat geografi adalah karena...',
                'opsi_a' => 'Mayoritas penduduk aslinya memiliki bola mata berwarna biru dan adanya keturunan darah biru (bangsawan)',
                'opsi_b' => 'Seluruh daratan benua Eropa selalu tergenang banjir air laut berwarna biru',
                'opsi_c' => 'Semua rumah di benua Eropa diwajibkan mengecat atap dengan warna biru',
                'opsi_d' => 'Benua Eropa tidak memiliki tanaman hijau dan hanya memiliki lumut biru',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Istilah benua biru merujuk pada ciri fisik iris mata bangsa Eropa serta sejarah aristokrasi kerajaan monarki bangsawan (darah biru).'
            ],
            [
                'soal' => 'Salah satu dampak negatif yang dapat timbul akibat interaksi keruangan antarnegara dalam bidang ekonomi adalah...',
                'opsi_a' => 'Ketergantungan ekonomi terhadap produk impor yang mematikan industri kecil dalam negeri',
                'opsi_b' => 'Meningkatnya devisa negara dari komoditas ekspor unggulan',
                'opsi_c' => 'Terbukanya lapangan kerja baru di sektor manufaktur internasional',
                'opsi_d' => 'Tercapainya swasembada pangan yang mandiri tanpa bantuan negara lain',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Jika pasar domestik dibanjiri barang impor murah tanpa proteksi, produsen lokal UMKM bisa gulung tikar.'
            ],
            [
                'soal' => 'Terusan buatan yang sangat penting bagi jalur pelayaran dunia karena memotong jarak antara Benua Asia dan Eropa tanpa harus memutari Benua Afrika adalah...',
                'opsi_a' => 'Terusan Suez di Mesir',
                'opsi_b' => 'Terusan Panama di Amerika Tengah',
                'opsi_c' => 'Selat Bering di dekat kutub utara',
                'opsi_d' => 'Selat Gibraltar di Spanyol',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Terusan Suez menghubungkan Laut Merah dengan Laut Mediterania (Laut Tengah), memotong rute kapal laut Asia-Eropa secara masif.'
            ],
            [
                'soal' => 'Sikap yang harus dimiliki generasi muda santri dalam menghadapi persaingan global antarbangsa adalah...',
                'opsi_a' => 'Memperkuat akidah akhlak, menguasai sains dan teknologi, serta aktif belajar bahasa internasional',
                'opsi_b' => 'Menolak seluruh kemajuan ilmu pengetahuan modern dari luar negeri',
                'opsi_c' => 'Meniru gaya hidup hedonisme dan pergaulan bebas tanpa filter moral',
                'opsi_d' => 'Merasa rendah diri (insecure) dan menyerah sebelum berkompetisi',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Santri unggul harus mengintegrasikan IMTAK (iman dan takwa) yang kokoh dengan IPTEK (ilmu pengetahuan dan teknologi) yang mutakhir.'
            ]
        ]
    ],

    // --- KELAS 9 (SEMESTER 2) ---
    [
        'nomor_bab' => 8,
        'tingkat_kelas' => '9',
        'semester' => '2',
        'fase' => 'Fase D',
        'kktp_nilai' => 75,
        'judul_bab' => 'Bab 8: Globalisasi dan Perubahan Sosial Budaya',
        'subjudul' => 'Dampak Modernisasi, Arus Informasi Digital, Tantangan Moralitas, dan Pelestarian Kearifan Lokal',
        'durasi_menit' => '20 Menit',
        'cp_elemen' => 'Pemahaman Konsep: Perubahan Sosial dan Ketahanan Budaya',
        'tujuan_pembelajaran' => 'Santri mampu mengevaluasi dampak positif dan negatif globalisasi, membentengi diri dari dekadensi moral, serta merancang aksi nyata pelestarian kearifan lokal berakhlakul karimah.',
        'pdf_url' => 'https://modul.pkbm.id/paket-b/Modul 8 IPS Paket B Globalisasi dan Perubahan Sosial Budaya.pdf',
        'video_url' => 'https://www.youtube.com/embed/lK9mQ7jPqYo',
        'video_urls' => json_encode([
            ['title' => 'Globalisasi dan Dampaknya bagi Perubahan Sosial (Kemdikbud)', 'url' => 'https://www.youtube.com/embed/lK9mQ7jPqYo'],
            ['title' => 'Menjaga Karakter Bangsa dan Kearifan Lokal di Era Digital', 'url' => 'https://www.youtube.com/embed/bF8kL2mPqYo']
        ], JSON_UNESCAPED_SLASHES),
        'ringkasan_materi' => json_encode([
            '1. Hakikat Globalisasi' => 'Proses mendunianya suatu hal sehingga batas-batas antarnegara menjadi memudar (borderless world), didorong revolusi teknologi informasi dan komunikasi.',
            '2. Bidang-Bidang Globalisasi' => 'Globalisasi IPTEK (internet, AI), ekonomi (pasar bebas), komunikasi (media sosial), transportasi (kendaraan cepat), dan budaya.',
            '3. Tantangan Dampak Negatif' => 'Westernisasi yang menyimpang dari adab ketimuran, konsumerisme (gaya hidup boros), individualisme, dan penurunan kepedulian sosial.',
            '4. Ketahanan Budaya Santri (Filter Islami)' => 'Santri menerapkan kaidah: "Al-muhafazhatu \'alal qadimish-shalih wal akhdzu bil jadidil ashlah" (memelihara tradisi lama yang baik dan mengambil hal baru yang lebih maslahat).'
        ], JSON_UNESCAPED_UNICODE),
        'lks_judul' => 'LKS 8: Rencana Aksi Santri Menghadapi Era Digital & AI',
        'lks_tugas' => "Tuliskan 3 cara bijak yang kamu lakukan sehari-hari dalam memanfaatkan internet/gadget agar terhindar dari fitnah medsos dan justru menjadi media dakwah kebaikan!",
        'kuis' => [
            [
                'soal' => 'Pernyataan yang paling tepat untuk mendeskripsikan pengertian globalisasi adalah...',
                'opsi_a' => 'Proses integrasi internasional yang terjadi karena pertukaran pandangan dunia, produk, pemikiran, dan aspek-aspek kebudayaan lainnya tanpa batas ruang',
                'opsi_b' => 'Penutupan seluruh perbatasan antarnegara agar tidak ada orang asing yang masuk',
                'opsi_c' => 'Pemusnahan seluruh alat komunikasi digital dan kembali ke surat menyurat burung merpati',
                'opsi_d' => 'Penghentian seluruh perdagangan luar negeri untuk kembali ke era berburu',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Globalisasi adalah proses mendunianya peradaban manusia yang mempercepat arus informasi, modal, barang, dan gagasan melintasi batas negara.'
            ],
            [
                'soal' => 'Kaidah fiqhiyah yang sangat relevan dijadikan pedoman bagi santri dalam menyikapi arus perkembangan zaman dan teknologi modern adalah...',
                'opsi_a' => 'Al-muhafazhatu \'alal qadimish-shalih wal akhdzu bil jadidil ashlah (Menjaga tradisi lama yang baik dan mengambil hal baru yang lebih maslahat)',
                'opsi_b' => 'Menerima seluruh hal asing tanpa memedulikan halal atau haram',
                'opsi_c' => 'Menolak seluruh inovasi sains modern karena berasal dari era teknologi',
                'opsi_d' => 'Menganggap semua teknologi modern adalah perbuatan sia-sia',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Kaidah ini mengajarkan sikap selektif, adaptif, dan berprinsip kokoh dalam menyaring perkembangan zaman.'
            ],
            [
                'soal' => 'Sikap mementingkan diri sendiri dan tidak peduli terhadap penderitaan sesama anggota masyarakat merupakan dampak negatif globalisasi yang disebut...',
                'opsi_a' => 'Individualisme',
                'opsi_b' => 'Gotong royong',
                'opsi_c' => 'Kolektivisme sosial',
                'opsi_d' => 'Filantropi kemanusiaan',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Individualisme adalah pandangan yang menempatkan kepentingan diri di atas segalanya dan mengikis kepekaan sosial.'
            ],
            [
                'soal' => 'Contoh pemanfaatan teknologi digital dan kecerdasan buatan (AI) yang bernilai maslahat bagi dakwah Islam adalah...',
                'opsi_a' => 'Mengembangkan aplikasi pembelajaran Al-Qur\'an, kamus bahasa Arab, dan konten edukasi nilai akhlak di media sosial',
                'opsi_b' => 'Menggunakan internet untuk menyebarkan hoaks dan adu domba sesama umat',
                'opsi_c' => 'Melakukan penipuan daring (online scam) demi meraup uang haram',
                'opsi_d' => 'Menghabiskan waktu 24 jam bermain game online tanpa salat dan belajar',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Teknologi adalah wasilah (sarana). Di tangan generasi beriman, teknologi menjadi alat dakwah dan pemberdayaan umat yang dahsyat.'
            ],
            [
                'soal' => 'Upaya yang dapat dilakukan untuk mempertahankan kearifan lokal di tengah serbuan budaya asing adalah...',
                'opsi_a' => 'Mempelajari, mencintai, dan memodifikasi budaya lokal agar tetap relevan tanpa meninggalkan nilai syariat',
                'opsi_b' => 'Merasa malu mengenakan busana khas daerah atau identitas santri',
                'opsi_c' => 'Menganggap kesenian tradisional sebagai hal kuno yang harus dimusnahkan',
                'opsi_d' => 'Mengganti seluruh bahasa daerah dengan bahasa gaul asing tanpa etika',
                'kunci_jawaban' => 'A',
                'pembahasan' => 'Pelestarian kearifan lokal membutuhkan rasa bangga terhadap identitas luhur bangsa dengan tetap adaptif terhadap kemajuan zaman.'
            ]
        ]
    ]
];

// Lakukan Insert ke elearning_bab dan elearning_kuis
$total_inserted = 0;
foreach ($sample_ips_data as $data) {
    $nomor_bab = (int)$data['nomor_bab'];
    $tingkat_kelas = $conn->real_escape_string($data['tingkat_kelas']);
    $semester = $conn->real_escape_string($data['semester']);
    $fase = $conn->real_escape_string($data['fase']);
    $kktp_nilai = (int)$data['kktp_nilai'];
    $judul_bab = $conn->real_escape_string($data['judul_bab']);
    $subjudul = $conn->real_escape_string($data['subjudul']);
    $durasi_menit = $conn->real_escape_string($data['durasi_menit']);
    $cp_elemen = $conn->real_escape_string($data['cp_elemen']);
    $tujuan_pembelajaran = $conn->real_escape_string($data['tujuan_pembelajaran']);
    $pdf_url = $conn->real_escape_string($data['pdf_url']);
    $video_url = $conn->real_escape_string($data['video_url']);
    $video_urls = $conn->real_escape_string($data['video_urls']);
    $ringkasan_materi = $conn->real_escape_string($data['ringkasan_materi']);
    $lks_judul = $conn->real_escape_string($data['lks_judul']);
    $lks_tugas = $conn->real_escape_string($data['lks_tugas']);

    $sql_b = "INSERT INTO elearning_bab 
        (mapel_id, mapel_nama, nomor_bab, tingkat_kelas, semester, fase, kktp_nilai, judul_bab, subjudul, durasi_menit, cp_elemen, tujuan_pembelajaran, pdf_url, video_url, video_urls, ringkasan_materi, lks_judul, lks_tugas)
        VALUES 
        (0, 'IPS', $nomor_bab, '$tingkat_kelas', '$semester', '$fase', $kktp_nilai, '$judul_bab', '$subjudul', '$durasi_menit', '$cp_elemen', '$tujuan_pembelajaran', '$pdf_url', '$video_url', '$video_urls', '$ringkasan_materi', '$lks_judul', '$lks_tugas')";
    
    if ($conn->query($sql_b)) {
        $bab_id = $conn->insert_id;
        $total_inserted++;

        // Insert Soal Kuis
        foreach ($data['kuis'] as $qIdx => $qk) {
            $soal = $conn->real_escape_string($qk['soal']);
            $opsi_a = $conn->real_escape_string($qk['opsi_a']);
            $opsi_b = $conn->real_escape_string($qk['opsi_b']);
            $opsi_c = $conn->real_escape_string($qk['opsi_c']);
            $opsi_d = $conn->real_escape_string($qk['opsi_d']);
            $kunci = $conn->real_escape_string($qk['kunci_jawaban']);
            $pembahasan = $conn->real_escape_string($qk['pembahasan']);

            $sql_q = "INSERT INTO elearning_kuis (bab_id, soal, opsi_a, opsi_b, opsi_c, opsi_d, kunci_jawaban, pembahasan)
                      VALUES ($bab_id, '$soal', '$opsi_a', '$opsi_b', '$opsi_c', '$opsi_d', '$kunci', '$pembahasan')";
            $conn->query($sql_q);
        }
    }
}
}
?>
