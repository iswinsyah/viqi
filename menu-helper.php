<?php
// menu-helper.php
// Sinkronisasi Penamaan Menu di Semua Ruang dengan Menu Manajemen Menu di Ruang Yayasan

if (!function_exists('get_all_menu_labels')) {
    function get_all_menu_labels($conn = null) {
        static $cached_labels = null;
        if ($cached_labels !== null) {
            return $cached_labels;
        }

        global $conn;
        $db = ($conn && $conn instanceof mysqli) ? $conn : ($GLOBALS['conn'] ?? null);
        if (!$db || !($db instanceof mysqli)) {
            if (isset($GLOBALS['koneksi']) && $GLOBALS['koneksi'] instanceof mysqli) {
                $db = $GLOBALS['koneksi'];
            }
        }
        if (!$db || !($db instanceof mysqli)) {
            $koneksi_path = __DIR__ . '/koneksi.php';
            if (file_exists($koneksi_path)) {
                @include_once $koneksi_path;
                $db = ($conn && $conn instanceof mysqli) ? $conn : ($GLOBALS['conn'] ?? null);
            }
        }

        // Default Titles persis sesuai yang terdaftar di yayasan2/manajemen-menu.php
        $titles = [
            // Menu Utama & Pegawai
            'absensi_pegawai'               => 'Absensi & Jurnal KBM',
            'perizinan_pegawai'             => 'Pengajuan Izin / Cuti',
            'peraturan_role'                => 'Peraturan Pegawai',
            'kpi_ustadz'                    => 'KPI Pegawai',
            'kpi_kepsek'                    => 'KPI Kepala Sekolah',
            'supervisi_mengajar'            => 'Supervisi Mengajar',
            'jadwal_rapat'                  => 'Jadwal Rapat',
            'akunku'                        => 'Akunku',
            'slip_gaji'                     => 'Slip Gaji',
            'kpi_admin_sekolah'             => 'KPI Admin Sekolah',
            'salary_admin'                  => 'Salary Admin Sekolah',

            // Administrasi & Nilai
            'buku_induk'                    => 'Buku Induk Santri',
            'akun_orangtua'                 => 'Akun Orang Tua',
            'leger_nilai'                   => 'Leger Nilai Digital',
            'rapot_pkbm'                    => 'Raport Diknas PKBM (B & C)',
            'counseling_karir'              => 'Pemetaan Karir & PTN (AI)',
            'rekap_keuangan'                => 'Rekap Pembayaran Keuangan',
            'penagihan_spp'                 => 'Penagihan SPP',
            'yayasan_saku'                  => 'Validasi Uang Saku',
            'uangsaku'                      => 'Saldo Uang Saku Santri',
            'rekap_uang_saku'               => 'Validasi Uang Saku',
            'sekolah_pembukuan'             => 'Buku Kas Sekolah',
            'kontrol_jam_kosong'            => 'Rekap Ajar',

            // Asatidz & KBM
            'kesediaan_mengajar'            => 'Kesediaan Mengajar',
            'kalender_akademik'             => 'Kalender Akademik',
            'jadwal_pelajaran'              => 'Jadwal Pelajaran',
            'master_silabus'                => 'Master Silabus & CP',
            'ai_rpp'                        => 'AI Generator RPP',
            'bank_nilai'                    => 'Bank Nilai (Input)',
            'manajemen_elearning'           => 'E-Modul & Flipbook Belajar',
            'prota_promes'                  => 'Pekan Efektif, Prota & Promes',
            'master_kelas'                  => 'Master Kelas',
            'master_mapel'                  => 'Master Mapel',
            'kitab_rujukan'                 => 'Master Kitab Rujukan',

            // Asrama & Musyrif
            'dashboard_asrama'              => 'Dashboard Asrama',
            'manajemen_halaqoh'             => 'Manajemen Halaqoh',
            'yayasan_ibadah'                => 'Rekap Ibadah Yayasan',
            'rekap_ibadah_santri'           => 'Rekap Ibadah Asrama',
            'ibadah'                        => 'Validasi Ibadah Santri',
            'setoran_hafalan_santri'        => 'Setoran Hafalan Santri',
            'rekap_setoran_santri'          => 'Rekap Setoran Santri',
            'validasi_ibadah_musyrif'       => 'Validasi Ibadah',
            'kontak_orangtua'               => 'Kontak Walisantri',
            'cek_belajar_mandiri'           => 'Chek Belajar Mandiri',
            'cek_kesehatan_santri'          => 'Chek Kesehatan',
            'rapot_pkbm_musyrif'            => 'Raport Diknas Santri Binaan',
            'mutabaah'                      => 'Buku Mutaba\'ah Santri',
            'laporan_adab'                  => 'Laporan Kedisiplinan',
            'kpi_musyrif'                   => 'KPI Musyrif',
            'rekap_uang_saku_musyrif'       => 'Rekap Uang Saku Santri',

            // Solopreneur & Kurikulum
            'kurikulum_solopreneur_trainer' => 'Inkubator Solopreneur (AI)',
            'yayasan_cp'                    => 'Capaian Pembelajaran (CP) AI',

            // Web & Marketing
            'ruang_web'                     => 'Ruang Web (CMS & Website)',
            'ruang_marketing'               => 'Ruang Marketing & AI',
            'pengaturan_brosur'             => 'Pengaturan Brosur Digital',
            'database_psb'                  => 'Database PSB Online',
            'yayasan_psb'                   => 'Database PSB Online',
            'data_center'                   => 'Menu Data Center',
            'artikel_ai'                    => 'Artikel AI',
            'siaran_wa'                     => 'Siaran WA Marketing',

            // Yayasan Tata Kelola
            'pembukuan_yayasan'             => 'Buku Kas Lembaga',
            'cashflow_yayasan'              => 'Cashflow & Proyeksi',
            'payroll_yayasan'               => 'Payroll Pegawai',
            'tarif_gaji_yayasan'            => 'Tarif Gaji Asatidz',
            'tunjangan_yayasan'             => 'Tunjangan Pegawai',
            'data_guru_yayasan'             => 'Data Guru & Asatidz',
            'ai_hrd_yayasan'                => 'AI Agent HRD',
            'crew_ai'                       => 'Crew AI Hub',
            'manajemen_menu'                => 'Manajemen Menu Akses',
            'struktur_jobdesc'              => 'Struktur & Jobdesc',
            'jobdesc_yayasan'               => 'Jobdesc Pegawai',
            'sop_peraturan'                 => 'SOP & Peraturan',
            'analisis_swot'                 => 'Analisis SWOT'
        ];

        // Sinkronisasi realtime dari tabel menu_custom_labels (yang diupdate dari yayasan2/manajemen-menu.php)
        if ($db && $db instanceof mysqli) {
            $res = @$db->query("SELECT menu_key, custom_label, short_label FROM menu_custom_labels");
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $key = $row['menu_key'];
                    $custom = trim($row['custom_label'] ?? '');
                    if ($custom !== '') {
                        $titles[$key] = $custom;
                    }
                }
            }
        }

        $cached_labels = $titles;
        return $cached_labels;
    }
}

if (!function_exists('get_menu_label')) {
    function get_menu_label($menu_key, $fallback = '', $conn = null) {
        $labels = get_all_menu_labels($conn);
        if (isset($labels[$menu_key]) && !empty($labels[$menu_key])) {
            return $labels[$menu_key];
        }
        return !empty($fallback) ? $fallback : ucwords(str_replace('_', ' ', $menu_key));
    }
}
