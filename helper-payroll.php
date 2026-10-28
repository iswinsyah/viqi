<?php
/**
 * Helper Perhitungan Payroll & Evaluasi Prestasi Kerja Pegawai
 * Yayasan Villa Qur'an Baron Malang
 */

if (!function_exists('terbilang_rupiah')) {
    function terbilang_rupiah($angka) {
        $angka = abs((float)$angka);
        $baca = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
        $terbilang = "";

        if ($angka < 12) {
            $terbilang = " " . $baca[(int)$angka];
        } elseif ($angka < 20) {
            $terbilang = terbilang_rupiah($angka - 10) . " Belas";
        } elseif ($angka < 100) {
            $terbilang = terbilang_rupiah(floor($angka / 10)) . " Puluh" . terbilang_rupiah($angka % 10);
        } elseif ($angka < 200) {
            $terbilang = " Seratus" . terbilang_rupiah($angka - 100);
        } elseif ($angka < 1000) {
            $terbilang = terbilang_rupiah(floor($angka / 100)) . " Ratus" . terbilang_rupiah($angka % 100);
        } elseif ($angka < 2000) {
            $terbilang = " Seribu" . terbilang_rupiah($angka - 1000);
        } elseif ($angka < 1000000) {
            $terbilang = terbilang_rupiah(floor($angka / 1000)) . " Ribu" . terbilang_rupiah($angka % 1000);
        } elseif ($angka < 1000000000) {
            $terbilang = terbilang_rupiah(floor($angka / 1000000)) . " Juta" . terbilang_rupiah($angka % 1000000);
        } elseif ($angka < 1000000000000) {
            $terbilang = terbilang_rupiah(floor($angka / 1000000000)) . " Milyar" . terbilang_rupiah(fmod($angka, 1000000000));
        } else {
            $terbilang = " Angka terlalu besar";
        }
        return trim($terbilang);
    }
}

if (!function_exists('hitung_payroll_pegawai')) {
    function hitung_payroll_pegawai($conn, $ustadz_id, $selected_month, $selected_year) {
        // 1. Setup & Self Healing Pengaturan Gaji
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_c INT DEFAULT 20000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_b INT DEFAULT 22500");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_grade_a INT DEFAULT 25000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_pokok_muda INT DEFAULT 2500000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN gaji_pokok_utama INT DEFAULT 3500000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_kepsek_a INT DEFAULT 1500000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_kepsek_b INT DEFAULT 1000000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_kepsek_c INT DEFAULT 500000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_mahad_a INT DEFAULT 1500000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_mahad_b INT DEFAULT 1000000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_mahad_c INT DEFAULT 500000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_asrama_a INT DEFAULT 1200000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_asrama_b INT DEFAULT 800000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_asrama_c INT DEFAULT 400000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_admin_a INT DEFAULT 1000000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_admin_b INT DEFAULT 700000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_admin_c INT DEFAULT 400000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_musyrif_a INT DEFAULT 600000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_musyrif_b INT DEFAULT 400000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN tunj_musyrif_c INT DEFAULT 200000");
        @$conn->query("ALTER TABLE pengaturan_gaji ADD COLUMN denda_alpa INT DEFAULT 50000");

        $res_cfg = $conn->query("SELECT * FROM pengaturan_gaji WHERE id=1 LIMIT 1");
        $cfg = $res_cfg ? $res_cfg->fetch_assoc() : [];

        $gaji_grade_a = (int)($cfg['gaji_grade_a'] ?? 25000);
        $gaji_grade_b = (int)($cfg['gaji_grade_b'] ?? 22500);
        $gaji_grade_c = (int)($cfg['gaji_grade_c'] ?? 20000);
        $gaji_pokok_muda = (int)($cfg['gaji_pokok_muda'] ?? 2500000);
        $gaji_pokok_utama = (int)($cfg['gaji_pokok_utama'] ?? 3500000);

        $tunj_kepsek_a = (int)($cfg['tunj_kepsek_a'] ?? 1500000);
        $tunj_kepsek_b = (int)($cfg['tunj_kepsek_b'] ?? 1000000);
        $tunj_kepsek_c = (int)($cfg['tunj_kepsek_c'] ?? 500000);

        $tunj_mahad_a = (int)($cfg['tunj_mahad_a'] ?? 1500000);
        $tunj_mahad_b = (int)($cfg['tunj_mahad_b'] ?? 1000000);
        $tunj_mahad_c = (int)($cfg['tunj_mahad_c'] ?? 500000);

        $tunj_asrama_a = (int)($cfg['tunj_asrama_a'] ?? 1200000);
        $tunj_asrama_b = (int)($cfg['tunj_asrama_b'] ?? 800000);
        $tunj_asrama_c = (int)($cfg['tunj_asrama_c'] ?? 400000);

        $tunj_admin_a = (int)($cfg['tunj_admin_a'] ?? 1000000);
        $tunj_admin_b = (int)($cfg['tunj_admin_b'] ?? 700000);
        $tunj_admin_c = (int)($cfg['tunj_admin_c'] ?? 400000);

        $tunj_musyrif_a = (int)($cfg['tunj_musyrif_a'] ?? 600000);
        $tunj_musyrif_b = (int)($cfg['tunj_musyrif_b'] ?? 400000);
        $tunj_musyrif_c = (int)($cfg['tunj_musyrif_c'] ?? 200000);

        $denda_alpa = (int)($cfg['denda_alpa'] ?? 50000);

        // 2. Fetch Pegawai
        $ust_id = (int)$ustadz_id;
        $res_u = $conn->query("SELECT * FROM akun_ustadz WHERE id = $ust_id LIMIT 1");
        if (!$res_u || $res_u->num_rows === 0) {
            return null;
        }
        $pegawai = $res_u->fetch_assoc();

        $nama_pegawai = $pegawai['nama'] ?? 'Tanpa Nama';
        $status_pegawai = $pegawai['status_pegawai'] ?? 'Pengabdian';
        $role_str = $pegawai['role'] ?? '';
        $user_roles = !empty($role_str) ? array_map('trim', array_map('strtolower', explode(',', $role_str))) : [];

        // Deteksi Kategori Role Utama
        $is_teacher = in_array('ustadz', $user_roles) || in_array('ustadzah', $user_roles) || in_array('guru', $user_roles) || in_array('tutor', $user_roles);
        $is_kepsek = in_array('kepala_sekolah', $user_roles);
        $is_mahad = in_array('kepala_mahad', $user_roles);
        $is_asrama = in_array('kepala_asrama', $user_roles) || in_array('kepala_asrama_rijal', $user_roles) || in_array('kepala_asrama_nisa', $user_roles);
        $is_musyrif = in_array('musyrif', $user_roles) || in_array('musyrifah', $user_roles);
        $is_admin = in_array('admin_sekolah', $user_roles);
        $is_super_admin = in_array('super_admin', $user_roles) || ($ust_id === 9999);

        $eligible_roles_pegawai = ['super_admin', 'kepala_sekolah', 'sekretaris_sekolah', 'bendahara_sekolah', 'admin_sekolah', 'kepala_mahad', 'kepala_asrama', 'kepala_asrama_rijal', 'kepala_asrama_nisa', 'musyrif', 'musyrifah'];
        $is_daily_worker = !empty(array_intersect($eligible_roles_pegawai, $user_roles));

        // 3. Periode Penggajian (27 bln lalu s/d 26 bln ini)
        $start_date = date('Y-m-d', mktime(0, 0, 0, $selected_month - 1, 27, $selected_year));
        $end_date   = date('Y-m-d', mktime(0, 0, 0, $selected_month, 26, $selected_year));

        // 4. Perhitungan Presensi & Alfa
        $q_absen = $conn->query("
            SELECT waktu_absen, status_kehadiran, keterangan, jenis_absen 
            FROM absensi_pegawai 
            WHERE ustadz_id = $ust_id 
              AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'
            ORDER BY waktu_absen ASC
        ");

        $hadir_tepat = 0;
        $hadir_terlambat = 0;
        $izin_count = 0;
        $sakit_count = 0;
        $alpa_count = 0;
        $alpa_dates = [];

        $absen_per_hari = [];
        if ($q_absen) {
            while ($ab = $q_absen->fetch_assoc()) {
                $tgl = date('Y-m-d', strtotime($ab['waktu_absen']));
                $absen_per_hari[$tgl][] = $ab;
            }
        }

        // Cek juga kepegawaian_perizinan yang disetujui dalam rentang periode
        $approved_leaves = [];
        $res_izin = $conn->query("
            SELECT tanggal_mulai, tanggal_selesai, kategori, status 
            FROM kepegawaian_perizinan 
            WHERE ustadz_id = $ust_id 
              AND status IN ('Disetujui', 'Disetujui Sebagian')
              AND (DATE(tanggal_mulai) <= '$end_date' AND DATE(tanggal_selesai) >= '$start_date')
        ");
        if ($res_izin) {
            while ($iz = $res_izin->fetch_assoc()) {
                $cur = strtotime(max($start_date, date('Y-m-d', strtotime($iz['tanggal_mulai']))));
                $end = strtotime(min($end_date, date('Y-m-d', strtotime($iz['tanggal_selesai']))));
                while ($cur <= $end) {
                    $dt = date('Y-m-d', $cur);
                    $approved_leaves[$dt] = $iz['kategori'];
                    $cur = strtotime('+1 day', $cur);
                }
            }
        }

        if (!empty($absen_per_hari)) {
            foreach ($absen_per_hari as $tgl => $rows) {
                $primary = $rows[0];
                foreach ($rows as $r) {
                    if (($r['status_kehadiran'] ?? '') === 'Masuk' || ($r['status_kehadiran'] ?? '') === 'Hadir') {
                        $primary = $r;
                        break;
                    }
                }

                $st_kehadiran = $primary['status_kehadiran'] ?? '';
                $ket = $primary['keterangan'] ?? '';

                if ($st_kehadiran === 'Alpa' || strpos($st_kehadiran, 'Alpa') !== false || strpos($ket, 'Alpa') !== false || strpos($ket, 'Tanpa Keterangan') !== false) {
                    $alpa_count++;
                    $alpa_dates[] = date('d/m/Y', strtotime($tgl));
                } elseif ($st_kehadiran === 'Izin' || strpos($st_kehadiran, 'Izin') !== false || strpos($ket, 'Izin') !== false) {
                    if (strpos($ket, 'Ditolak') === false && strpos($ket, 'Tidak Disetujui') === false) {
                        $izin_count++;
                    } else {
                        $alpa_count++;
                        $alpa_dates[] = date('d/m/Y', strtotime($tgl));
                    }
                } elseif ($st_kehadiran === 'Sakit' || strpos($st_kehadiran, 'Sakit') !== false || strpos($ket, 'Sakit') !== false) {
                    $sakit_count++;
                } elseif (strpos($ket, 'Terlambat') !== false) {
                    $hadir_terlambat++;
                } else {
                    $hadir_tepat++;
                }
            }
        }

        // Tambahkan izin/sakit dari perizinan yang belum masuk tabel absensi_pegawai
        foreach ($approved_leaves as $tgl_iz => $kat_iz) {
            if (!isset($absen_per_hari[$tgl_iz])) {
                if (stripos($kat_iz, 'sakit') !== false) {
                    $sakit_count++;
                } else {
                    $izin_count++;
                }
            }
        }

        $total_hadir = $hadir_tepat + $hadir_terlambat;
        // Total hari kerja acuan per bulan (20 hari standar yayasan)
        $target_hari_kerja = 20;

        // 5. Perhitungan KPI Detail Sesuai Role
        $kpi_items = [];
        $total_skor_kpi = 100;

        // A. JURNAL & KBM
        $res_jurnal = $conn->query("SELECT 
            COUNT(*) as total_jurnal, 
            SUM(CASE WHEN DATE(created_at) = tanggal THEN 1 ELSE 0 END) as tepat_waktu 
            FROM jurnal_mengajar 
            WHERE ustadz_id = $ust_id AND tanggal BETWEEN '$start_date' AND '$end_date'");
        $data_jurnal = $res_jurnal ? $res_jurnal->fetch_assoc() : ['total_jurnal' => 0, 'tepat_waktu' => 0];
        $jumlah_jurnal = (int)($data_jurnal['total_jurnal'] ?? 0);
        $jurnal_tepat = (int)($data_jurnal['tepat_waktu'] ?? 0);
        $skor_jurnal = $jumlah_jurnal > 0 ? ($jurnal_tepat / $jumlah_jurnal) * 100 : 100;

        // B. ABSENSI MENGAJAR / HARIAN
        if ($is_daily_worker) {
            $skor_kehadiran = $total_hadir > 0 ? min(100, ($total_hadir / $target_hari_kerja) * 100) : 0;
            // Penalti alfa: kurangi 5 poin per alfa dari skor kehadiran
            $skor_kehadiran = max(0, $skor_kehadiran - ($alpa_count * 10));
        } else {
            $res_hadir_mengajar = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen = 'Mengajar' AND status_kehadiran = 'Masuk' AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
            $jml_hadir_mengajar = $res_hadir_mengajar ? (int)($res_hadir_mengajar->fetch_assoc()['jml'] ?? 0) : 0;
            
            $res_total_teaching_days = $conn->query("SELECT COUNT(DISTINCT tanggal) as total_days FROM jurnal_mengajar WHERE ustadz_id = $ust_id AND tanggal BETWEEN '$start_date' AND '$end_date'");
            $total_teaching_days = $res_total_teaching_days ? (int)($res_total_teaching_days->fetch_assoc()['total_days'] ?? 0) : 0;
            $skor_kehadiran = $total_teaching_days > 0 ? min(100, ($jml_hadir_mengajar / $total_teaching_days) * 100) : 100;
        }

        // C. RAPAT
        $res_rapat = $conn->query("SELECT COUNT(DISTINCT DATE(waktu_absen)) as jml FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen = 'Rapat' AND status_kehadiran = 'Masuk' AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
        $jml_rapat = $res_rapat ? (int)($res_rapat->fetch_assoc()['jml'] ?? 0) : 0;
        $res_total_rapat = $conn->query("SELECT COUNT(*) as total FROM jadwal_rapat WHERE DATE(waktu_mulai) BETWEEN '$start_date' AND '$end_date'");
        $total_rapat = $res_total_rapat ? (int)($res_total_rapat->fetch_assoc()['total'] ?? 0) : 0;
        $skor_kehadiran_rapat = $total_rapat > 0 ? min(100, ($jml_rapat / $total_rapat) * 100) : 100;

        // D. KUALITAS / AI / SUPERVISI
        $res_ai = $conn->query("SELECT COUNT(*) as pemakaian FROM log_aktivitas_ai WHERE user_id = $ust_id AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");
        $jumlah_pakai_ai = $res_ai ? (int)($res_ai->fetch_assoc()['pemakaian'] ?? 0) : 0;
        $skor_ai = $jumlah_pakai_ai >= 5 ? 100 : ($jumlah_pakai_ai > 0 ? 85 : 70);

        if ($is_teacher) {
            $res_sup = $conn->query("SELECT skor FROM supervisi_mengajar WHERE user_id = $ust_id ORDER BY tanggal_supervisi DESC LIMIT 1");
            $skor_supervisi = $res_sup && $res_sup->num_rows > 0 ? (int)($res_sup->fetch_assoc()['skor']) : 85;
        } else {
            $skor_supervisi = 100;
        }

        // E. ROLE SPECIFIC KPI GENERATION
        if ($is_kepsek) {
            // Role Kepala Sekolah
            $res_sup_kps = $conn->query("SELECT COUNT(*) as total FROM supervisi_mengajar WHERE supervisor_id = $ust_id AND DATE(tanggal_supervisi) BETWEEN '$start_date' AND '$end_date'");
            $total_sup_kps = $res_sup_kps ? (int)$res_sup_kps->fetch_assoc()['total'] : 0;
            $skor_sup_kps = min(100, ($total_sup_kps / 2) * 100);

            $kpi_items[] = ['kategori' => 'Kedisiplinan Hadir', 'detail' => "$total_hadir dari $target_hari_kerja hari kerja", 'skor' => round($skor_kehadiran, 1), 'bobot' => '25%'];
            $kpi_items[] = ['kategori' => 'Supervisi KBM Guru', 'detail' => "$total_sup_kps kali supervisi terlaksana", 'skor' => round($skor_sup_kps, 1), 'bobot' => '30%'];
            $kpi_items[] = ['kategori' => 'Partisipasi Rapat Dinas', 'detail' => "$jml_rapat dari $total_rapat agenda rapat", 'skor' => round($skor_kehadiran_rapat, 1), 'bobot' => '25%'];
            $kpi_items[] = ['kategori' => 'Pemanfaatan AI & Sistem', 'detail' => "$jumlah_pakai_ai aktivitas sistem/AI", 'skor' => round($skor_ai, 1), 'bobot' => '20%'];

            $total_skor_kpi = ($skor_kehadiran * 0.25) + ($skor_sup_kps * 0.30) + ($skor_kehadiran_rapat * 0.25) + ($skor_ai * 0.20);

        } elseif ($is_musyrif) {
            // Role Musyrif / Asrama
            $res_ibd = $conn->query("SELECT COUNT(*) as cnt FROM riwayat_validasi_ibadah WHERE validator_id = $ust_id AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");
            $cnt_ibd = $res_ibd ? (int)$res_ibd->fetch_assoc()['cnt'] : 0;
            $skor_ibd = $cnt_ibd >= 20 ? 100 : ($cnt_ibd > 0 ? ($cnt_ibd / 20) * 100 : 75);

            $kpi_items[] = ['kategori' => 'Presensi Asrama & Harian', 'detail' => "$total_hadir dari $target_hari_kerja hari", 'skor' => round($skor_kehadiran, 1), 'bobot' => '35%'];
            $kpi_items[] = ['kategori' => 'Pendampingan & Validasi Ibadah', 'detail' => "$cnt_ibd aktivitas bimbingan", 'skor' => round($skor_ibd, 1), 'bobot' => '35%'];
            $kpi_items[] = ['kategori' => 'Kehadiran Rapat Asrama/Dinas', 'detail' => "$jml_rapat dari $total_rapat rapat", 'skor' => round($skor_kehadiran_rapat, 1), 'bobot' => '20%'];
            $kpi_items[] = ['kategori' => 'Pengembangan & Mutu', 'detail' => "$jumlah_pakai_ai log aktivitas", 'skor' => round($skor_ai, 1), 'bobot' => '10%'];

            $total_skor_kpi = ($skor_kehadiran * 0.35) + ($skor_ibd * 0.35) + ($skor_kehadiran_rapat * 0.20) + ($skor_ai * 0.10);

        } elseif ($is_admin) {
            // Role Admin Sekolah / Tata Usaha
            $kpi_items[] = ['kategori' => 'Presensi Harian Kantor', 'detail' => "$total_hadir dari $target_hari_kerja hari kerja", 'skor' => round($skor_kehadiran, 1), 'bobot' => '40%'];
            $kpi_items[] = ['kategori' => 'Pelayanan & Sistem Digital', 'detail' => "$jumlah_pakai_ai aktivitas AI & data", 'skor' => round($skor_ai, 1), 'bobot' => '35%'];
            $kpi_items[] = ['kategori' => 'Kehadiran Rapat Koordinasi', 'detail' => "$jml_rapat dari $total_rapat rapat", 'skor' => round($skor_kehadiran_rapat, 1), 'bobot' => '25%'];

            $total_skor_kpi = ($skor_kehadiran * 0.40) + ($skor_ai * 0.35) + ($skor_kehadiran_rapat * 0.25);

        } else {
            // Role Guru / Ustadz
            $skor_adm = (($skor_jurnal * 0.4) + ($skor_kehadiran * 0.4) + ($skor_kehadiran_rapat * 0.2));
            $skor_kualitas = (($skor_ai * 0.4) + ($skor_supervisi * 0.6));
            
            $res_leger = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE created_by = $ust_id AND DATE(created_at) BETWEEN '$start_date' AND '$end_date'");
            $avg_nilai = $res_leger ? (float)($res_leger->fetch_assoc()['rata_rata'] ?? 0) : 0;
            if ($avg_nilai <= 0) {
                $res_fb = $conn->query("SELECT AVG(nilai) as rata_rata FROM leger_nilai WHERE created_by = $ust_id");
                $avg_nilai = $res_fb ? (float)($res_fb->fetch_assoc()['rata_rata'] ?? 0) : 0;
            }
            $skor_capaian = $avg_nilai >= 75 ? 100 : ($avg_nilai > 0 ? ($avg_nilai / 75) * 100 : 85);

            $kpi_items[] = ['kategori' => 'Kepatuhan Jurnal KBM', 'detail' => "$jurnal_tepat dari $jumlah_jurnal tepat waktu", 'skor' => round($skor_jurnal, 1), 'bobot' => '25%'];
            $kpi_items[] = ['kategori' => 'Presensi Mengajar & Rapat', 'detail' => "Disiplin KBM & $jml_rapat Rapat", 'skor' => round(($skor_kehadiran + $skor_kehadiran_rapat)/2, 1), 'bobot' => '25%'];
            $kpi_items[] = ['kategori' => 'Supervisi KBM & AI Edukasi', 'detail' => "Skor Supervisi: $skor_supervisi", 'skor' => round($skor_kualitas, 1), 'bobot' => '30%'];
            $kpi_items[] = ['kategori' => 'Capaian Nilai Santri', 'detail' => "Rata-rata: " . number_format($avg_nilai, 1), 'skor' => round($skor_capaian, 1), 'bobot' => '20%'];

            $total_skor_kpi = ($skor_adm * 0.35) + ($skor_kualitas * 0.40) + ($skor_capaian * 0.25);
        }

        // Tentukan Grade Berdasarkan Total Skor KPI
        if ($total_skor_kpi >= 90) {
            $grade = 'A';
            $rate_per_jam = $gaji_grade_a;
            $grade_badge = 'bg-emerald-100 text-emerald-800 border-emerald-300';
            $grade_desc = 'Sangat Memuaskan (Grade A - Tunjangan Maksimal 100%)';
        } elseif ($total_skor_kpi >= 80) {
            $grade = 'B';
            $rate_per_jam = $gaji_grade_b;
            $grade_badge = 'bg-blue-100 text-blue-800 border-blue-300';
            $grade_desc = 'Baik / Standar (Grade B - Tunjangan Disesuaikan)';
        } else {
            $grade = 'C';
            $rate_per_jam = $gaji_grade_c;
            $grade_badge = 'bg-amber-100 text-amber-800 border-amber-300';
            $grade_desc = 'Cukup / Perlu Evaluasi (Grade C - Penurunan Tunjangan)';
        }

        // 6. Gaji Pokok
        $gaji_pokok = 0;
        if ($status_pegawai === 'Pegawai Muda') {
            $gaji_pokok = $gaji_pokok_muda;
        } elseif ($status_pegawai === 'Pegawai Utama') {
            $gaji_pokok = $gaji_pokok_utama;
        }

        // 7. Honor Mengajar
        $res_jurnal_cnt = $conn->query("SELECT COUNT(*) as total FROM jurnal_mengajar WHERE ustadz_id = $ust_id AND tanggal BETWEEN '$start_date' AND '$end_date'");
        $cnt_jurnal = $res_jurnal_cnt ? (int)$res_jurnal_cnt->fetch_assoc()['total'] : 0;
        
        $res_abs_cnt = $conn->query("SELECT COUNT(*) as total FROM absensi_pegawai WHERE ustadz_id = $ust_id AND jenis_absen = 'Mengajar' AND status_kehadiran = 'Masuk' AND DATE(waktu_absen) BETWEEN '$start_date' AND '$end_date'");
        $cnt_abs = $res_abs_cnt ? (int)$res_abs_cnt->fetch_assoc()['total'] : 0;
        $total_pertemuan = max($cnt_jurnal, $cnt_abs);

        $has_ustadz_role = $is_teacher;
        $is_utama_or_muda = ($status_pegawai === 'Pegawai Utama' || $status_pegawai === 'Pegawai Muda');
        
        if ($is_utama_or_muda && $has_ustadz_role) {
            $kelebihan_jam = max(0, $total_pertemuan - 12);
            $honor = $kelebihan_jam * $rate_per_jam;
            $honor_note = "Kelebihan {$kelebihan_jam}x @ Rp " . number_format($rate_per_jam, 0, ',', '.') . " (Grade {$grade})";
        } else {
            $honor = $total_pertemuan * $rate_per_jam;
            $honor_note = "{$total_pertemuan}x @ Rp " . number_format($rate_per_jam, 0, ',', '.') . " (Grade {$grade})";
        }

        // 8. Rincian Tunjangan (Disesuaikan Grade KPI)
        $tunjangan_total = 0;
        $rincian_tunjangan = [];

        foreach ($user_roles as $r) {
            if ($r === 'kepala_sekolah') {
                $val = ($grade === 'A') ? $tunj_kepsek_a : (($grade === 'B') ? $tunj_kepsek_b : $tunj_kepsek_c);
                $tunjangan_total += $val;
                $rincian_tunjangan[] = [
                    'nama' => 'Tunjangan Jabatan Kepala Sekolah',
                    'grade' => $grade,
                    'nominal' => $val,
                    'keterangan' => "Sesuai KPI Grade $grade"
                ];
            } elseif ($r === 'kepala_mahad') {
                $val = ($grade === 'A') ? $tunj_mahad_a : (($grade === 'B') ? $tunj_mahad_b : $tunj_mahad_c);
                $tunjangan_total += $val;
                $rincian_tunjangan[] = [
                    'nama' => 'Tunjangan Kepala Ma\'had',
                    'grade' => $grade,
                    'nominal' => $val,
                    'keterangan' => "Sesuai KPI Grade $grade"
                ];
            } elseif ($r === 'kepala_asrama' || $r === 'kepala_asrama_rijal' || $r === 'kepala_asrama_nisa') {
                $val = ($grade === 'A') ? $tunj_asrama_a : (($grade === 'B') ? $tunj_asrama_b : $tunj_asrama_c);
                $tunjangan_total += $val;
                $rincian_tunjangan[] = [
                    'nama' => 'Tunjangan Kepala Asrama',
                    'grade' => $grade,
                    'nominal' => $val,
                    'keterangan' => "Sesuai KPI Grade $grade"
                ];
            } elseif ($r === 'admin_sekolah') {
                $val = ($grade === 'A') ? $tunj_admin_a : (($grade === 'B') ? $tunj_admin_b : $tunj_admin_c);
                $tunjangan_total += $val;
                $rincian_tunjangan[] = [
                    'nama' => 'Tunjangan Staf Administrasi / TU',
                    'grade' => $grade,
                    'nominal' => $val,
                    'keterangan' => "Sesuai KPI Grade $grade"
                ];
            } elseif ($r === 'musyrif' || $r === 'musyrifah') {
                $val = ($grade === 'A') ? $tunj_musyrif_a : (($grade === 'B') ? $tunj_musyrif_b : $tunj_musyrif_c);
                $tunjangan_total += $val;
                $rincian_tunjangan[] = [
                    'nama' => 'Tunjangan Pengasuhan Musyrif/ah',
                    'grade' => $grade,
                    'nominal' => $val,
                    'keterangan' => "Sesuai KPI Grade $grade"
                ];
            }
        }

        // 9. Potongan (Deductions)
        $potongan_alpa = $alpa_count * $denda_alpa;
        $total_potongan = $potongan_alpa;

        $rincian_potongan = [];
        if ($potongan_alpa > 0) {
            $rincian_potongan[] = [
                'nama' => "Penalti Alfa / Tanpa Keterangan ($alpa_count hari)",
                'nominal' => $potongan_alpa,
                'keterangan' => !empty($alpa_dates) ? "Tanggal: " . implode(', ', $alpa_dates) : "Tidak hadir tanpa surat izin"
            ];
        }

        // 10. Total Penerimaan Bruto & Netto (THP)
        $total_penerimaan = $gaji_pokok + $tunjangan_total + $honor;
        $total_thp = max(0, $total_penerimaan - $total_potongan);

        return [
            'ustadz_id' => $ust_id,
            'nama' => $nama_pegawai,
            'status_pegawai' => $status_pegawai,
            'role_str' => $role_str,
            'user_roles' => $user_roles,
            'is_teacher' => $is_teacher,
            'is_kepsek' => $is_kepsek,
            'is_musyrif' => $is_musyrif,
            'is_admin' => $is_admin,
            // Presensi
            'hadir_count' => $total_hadir,
            'hadir_tepat' => $hadir_tepat,
            'hadir_terlambat' => $hadir_terlambat,
            'izin_count' => $izin_count,
            'sakit_count' => $sakit_count,
            'alpa_count' => $alpa_count,
            'alpa_dates' => $alpa_dates,
            'target_hari_kerja' => $target_hari_kerja,
            // KPI
            'total_skor_kpi' => $total_skor_kpi,
            'grade' => $grade,
            'grade_badge' => $grade_badge,
            'grade_desc' => $grade_desc,
            'kpi_items' => $kpi_items,
            // Rincian Finansial
            'gaji_pokok' => $gaji_pokok,
            'tunjangan_total' => $tunjangan_total,
            'rincian_tunjangan' => $rincian_tunjangan,
            'honor' => $honor,
            'honor_note' => $honor_note,
            'total_pertemuan' => $total_pertemuan,
            'rate_per_jam' => $rate_per_jam,
            'total_penerimaan' => $total_penerimaan,
            // Potongan
            'potongan_alpa' => $potongan_alpa,
            'rincian_potongan' => $rincian_potongan,
            'total_potongan' => $total_potongan,
            // Netto
            'total_thp' => $total_thp,
            'terbilang' => terbilang_rupiah($total_thp) . " Rupiah",
            // Periode
            'bulan' => $selected_month,
            'tahun' => $selected_year,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];
    }
}

