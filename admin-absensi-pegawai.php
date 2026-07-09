<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'absensi_pegawai';

$ustadz_id = $_SESSION['ustadz_id'];
$today = date('Y-m-d');

// Cek absensi harian hari ini
$res_harian = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Harian' ORDER BY waktu_absen ASC");
$harian_status = 'belum_absen';
if ($res_harian) {
    $num = $res_harian->num_rows;
    if ($num >= 2) {
        $harian_status = 'selesai';
    } elseif ($num == 1) {
        $harian_status = 'datang';
    }
}

// Cek absensi rapat hari ini
$res_rapat = $conn->query("SELECT status_kehadiran FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today' AND jenis_absen = 'Rapat' ORDER BY waktu_absen ASC");
$rapat_status = 'belum_absen';
if ($res_rapat) {
    $num = $res_rapat->num_rows;
    if ($num >= 2) {
        $rapat_status = 'selesai';
    } elseif ($num == 1) {
        $rapat_status = 'hadir';
    }
}

// Cek Otoritas Role untuk Absensi Harian
$user_roles = isset($_SESSION['ustadz_role']) ? explode(',', $_SESSION['ustadz_role']) : [];
$eligible_roles = ['kepala_sekolah', 'kepala_mahad', 'admin_sekolah', 'musyrif'];
$is_eligible_harian = false;
foreach ($user_roles as $role) {
    if (in_array(trim($role), $eligible_roles)) {
        $is_eligible_harian = true;
        break;
    }
}

// Persiapan Teks, Icon, & Class Tombol Harian
$harian_btn_text = '';
$harian_btn_icon = '';
if ($harian_status === 'belum_absen') {
    $harian_btn_text = 'Absen Datang';
    $harian_btn_icon = 'fa-sign-in-alt';
} elseif ($harian_status === 'datang') {
    $harian_btn_text = 'Absen Pulang';
    $harian_btn_icon = 'fa-sign-out-alt';
} else {
    $harian_btn_text = 'Absensi Harian Selesai';
    $harian_btn_icon = 'fa-check-double';
}

// Persiapan Teks, Icon, & Class Tombol Rapat
$rapat_btn_text = '';
$rapat_btn_icon = '';
if ($rapat_status === 'belum_absen') {
    $rapat_btn_text = 'Hadir Rapat';
    $rapat_btn_icon = 'fa-handshake';
} elseif ($rapat_status === 'hadir') {
    $rapat_btn_text = 'Selesai Rapat';
    $rapat_btn_icon = 'fa-door-open';
} else {
    $rapat_btn_text = 'Absensi Rapat Selesai';
    $rapat_btn_icon = 'fa-calendar-check';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Kehadiran | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Library untuk scan QR Code -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-map-marker-alt text-cyan-600 mr-2"></i>Absensi Kehadiran Pegawai</h1>
                <p class="text-gray-500 mt-1">Verifikasi lokasi GPS Anda untuk melakukan absensi harian maupun kehadiran rapat.</p>
            </div>

            <!-- Tampilan Status GPS (Global) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 max-w-4xl mx-auto">
                <div id="gps-status-card" class="bg-slate-50 border border-slate-100 rounded-xl p-4 text-sm text-gray-600 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center text-left">
                        <i class="fas fa-circle-notch fa-spin text-cyan-500 text-2xl mr-3 flex-shrink-0" id="gps-loading-icon"></i>
                        <i class="fas fa-location-dot text-emerald-500 text-2xl mr-3 flex-shrink-0 hidden" id="gps-success-icon"></i>
                        <i class="fas fa-circle-exclamation text-rose-500 text-2xl mr-3 flex-shrink-0 hidden" id="gps-error-icon"></i>
                        <div>
                            <p class="font-semibold text-gray-800" id="gps-status-title">Mendeteksi Lokasi Anda...</p>
                            <p class="text-xs text-gray-500" id="gps-status-desc">Izinkan akses GPS jika diminta oleh browser.</p>
                        </div>
                    </div>
                    <button id="btn-refresh-gps" class="w-full sm:w-auto text-xs font-semibold text-cyan-600 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 px-4 py-2 rounded-lg transition-all duration-200">
                        <i class="fas fa-sync-alt mr-2"></i>Segarkan Lokasi
                    </button>
                </div>
            </div>

            <!-- GRID DUA KARTU ABSENSI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto mb-8">
                
                <?php if ($is_eligible_harian): ?>
                    <!-- KARTU 1: ABSENSI HARIAN -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg">
                        <div>
                            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Harian</h2>
                            <p class="text-sm text-gray-500 mb-6 font-medium">
                                <?php if ($harian_status === 'belum_absen'): ?>
                                    Belum absen masuk hari ini.
                                <?php elseif ($harian_status === 'datang'): ?>
                                    Sudah absen masuk. Klik untuk absen pulang.
                                <?php else: ?>
                                    Selesai absen masuk dan pulang hari ini.
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <button id="btn-absen-harian" 
                                data-status="<?= $harian_status ?>" 
                                data-jenis="Harian"
                                <?= ($harian_status === 'selesai') ? 'disabled' : '' ?>
                                class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($harian_status === 'selesai') ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : (($harian_status === 'belum_absen') ? 'bg-emerald-600 hover:bg-emerald-700 text-white hover:shadow-lg active:scale-95' : 'bg-rose-600 hover:bg-rose-700 text-white hover:shadow-lg active:scale-95') ?>">
                            <i class="fas <?= $harian_btn_icon ?> text-xl"></i>
                            <span><?= $harian_btn_text ?></span>
                        </button>
                    </div>
                <?php else: ?>
                    <!-- KARTU 1: INFO ABSENSI HARIAN KHUSUS USTADZ -->
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg border-l-4 border-l-amber-500">
                        <div>
                            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                                <i class="fas fa-chalkboard-user"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Harian Ustadz</h2>
                            <p class="text-sm text-gray-600 mb-6 font-medium leading-relaxed">
                                Absensi harian untuk pengajar/ustadz dicatat secara otomatis ketika Anda mengisi **Jurnal Mengajar** saat kelas berlangsung.
                            </p>
                        </div>
                        
                        <a href="admin-pegawai-jurnal.php" 
                           class="w-full py-4 px-6 font-bold rounded-xl shadow-md bg-amber-500 hover:bg-amber-600 text-white shadow-amber-200 hover:shadow-lg transition-all duration-300 flex items-center justify-center gap-3 active:scale-95">
                            <i class="fas fa-book-open text-xl"></i>
                            <span>Isi Jurnal Mengajar</span>
                        </a>
                    </div>
                <?php endif; ?>

                <!-- KARTU 2: ABSENSI RAPAT -->
                <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 flex flex-col justify-between text-center transition-all duration-300 hover:shadow-lg">
                    <div>
                        <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
                            <i class="fas fa-users-rectangle"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Absensi Rapat</h2>
                        <p class="text-sm text-gray-500 mb-6 font-medium">
                            <?php if ($rapat_status === 'belum_absen'): ?>
                                Belum hadir rapat hari ini.
                            <?php elseif ($rapat_status === 'hadir'): ?>
                                Sudah hadir rapat. Klik jika rapat telah selesai.
                            <?php else: ?>
                                Selesai absensi hadir dan selesai rapat hari ini.
                            <?php endif; ?>
                        </p>
                    </div>
                    
                    <button id="btn-absen-rapat" 
                            data-status="<?= $rapat_status ?>" 
                            data-jenis="Rapat"
                            <?= ($rapat_status === 'selesai') ? 'disabled' : '' ?>
                            class="w-full py-4 px-6 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center gap-3 <?= ($rapat_status === 'selesai') ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : (($rapat_status === 'belum_absen') ? 'bg-indigo-600 hover:bg-indigo-700 text-white hover:shadow-lg active:scale-95' : 'bg-amber-500 hover:bg-amber-600 text-white hover:shadow-lg active:scale-95') ?>">
                        <i class="fas <?= $rapat_btn_icon ?> text-xl"></i>
                        <span><?= $rapat_btn_text ?></span>
                    </button>
                </div>

            </div>

            <!-- KOTAK HASIL/STATUS UTAMA -->
            <div id="scan-result" class="max-w-4xl mx-auto mb-8 text-center">
                <!-- Status akan diisi secara dinamis -->
            </div>

            <!-- Custom Alert Modal -->
            <div id="alert-modal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div id="modal-overlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    
                    <div id="modal-card" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6">
                        <div class="sm:flex sm:items-start">
                            <div id="modal-icon-bg" class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10">
                                <i id="modal-icon" class="fas text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Judul Notifikasi</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 leading-relaxed" id="modal-message">Isi pesan peringatan.</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-6 sm:flex sm:flex-row-reverse">
                            <button type="button" id="btn-close-modal" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-md px-4 py-2.5 text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition duration-200">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Data Lokasi
        const locations = {
            'kantor_utama': {
                nama: 'Gedung B (Kantor Villa Quran)',
                lat: -6.595038,
                lon: 106.800247
            },
            'asrama_rijal': {
                nama: 'Gedung A (Asrama Rijal)',
                lat: -6.597638,
                lon: 106.79955
            },
            'asrama_nisa': {
                nama: 'Gedung C (Asrama Nisa)',
                lat: -6.598333,
                lon: 106.801111
            }
        };

        const MAX_DISTANCE_METERS = 50;

        let userLatitude = null;
        let userLongitude = null;
        let isLocationValid = false;

        document.getElementById('open-sidebar-hr').addEventListener('click', () => {
            document.getElementById('sidebar-hr').classList.toggle('hidden');
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
        });

        // Hitung jarak Haversine
        function haversineDistance(lat1, lon1, lat2, lon2) {
            const R = 6371000; // Radius bumi dalam meter
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon / 2) * Math.sin(dLon / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
            return R * c;
        }

        // Tampilkan Modal Peringatan Premium
        function showAlertModal(title, message, type = 'success') {
            const modal = document.getElementById('alert-modal');
            const modalTitle = document.getElementById('modal-title');
            const modalMessage = document.getElementById('modal-message');
            const modalIcon = document.getElementById('modal-icon');
            const modalIconBg = document.getElementById('modal-icon-bg');
            const closeBtn = document.getElementById('btn-close-modal');

            modalTitle.innerText = title;
            modalMessage.innerText = message;

            // Reset Class
            modalIconBg.className = "mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10 ";
            closeBtn.className = "w-full inline-flex justify-center rounded-xl border border-transparent shadow-md px-4 py-2.5 text-base font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm transition duration-200 ";

            if (type === 'success') {
                modalIcon.className = "fas fa-check-circle text-emerald-600 text-lg";
                modalIconBg.classList.add("bg-emerald-100");
                closeBtn.classList.add("bg-emerald-600", "hover:bg-emerald-700", "focus:ring-emerald-500");
            } else if (type === 'warning') {
                modalIcon.className = "fas fa-exclamation-triangle text-amber-600 text-lg";
                modalIconBg.classList.add("bg-amber-100");
                closeBtn.classList.add("bg-amber-500", "hover:bg-amber-600", "focus:ring-amber-500");
            } else { // error / rejected
                modalIcon.className = "fas fa-circle-xmark text-rose-600 text-lg";
                modalIconBg.classList.add("bg-rose-100");
                closeBtn.classList.add("bg-rose-600", "hover:bg-rose-700", "focus:ring-rose-500");
            }

            modal.classList.remove('hidden');
        }

        // Tutup Modal
        document.getElementById('btn-close-modal').addEventListener('click', () => {
            document.getElementById('alert-modal').classList.add('hidden');
        });
        document.getElementById('modal-overlay').addEventListener('click', () => {
            document.getElementById('alert-modal').classList.add('hidden');
        });

        // Ambil data lokasi user untuk visualisasi status GPS
        function updateGPSStatus() {
            const title = document.getElementById('gps-status-title');
            const desc = document.getElementById('gps-status-desc');
            const loadingIcon = document.getElementById('gps-loading-icon');
            const successIcon = document.getElementById('gps-success-icon');
            const errorIcon = document.getElementById('gps-error-icon');

            // Reset UI
            loadingIcon.classList.remove('hidden');
            successIcon.classList.add('hidden');
            errorIcon.classList.add('hidden');

            if (!navigator.geolocation) {
                loadingIcon.classList.add('hidden');
                errorIcon.classList.remove('hidden');
                title.innerText = "GPS Tidak Didukung";
                desc.innerText = "Browser Anda tidak mendukung deteksi lokasi.";
                return;
            }

            title.innerText = "Mendeteksi Lokasi Anda...";
            desc.innerText = "Mengambil koordinat GPS Anda...";

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLatitude = position.coords.latitude;
                    userLongitude = position.coords.longitude;

                    let closestLocation = null;
                    let minDistance = null;

                    for (const key in locations) {
                        const loc = locations[key];
                        const dist = haversineDistance(userLatitude, userLongitude, loc.lat, loc.lon);
                        if (minDistance === null || dist < minDistance) {
                            minDistance = dist;
                            closestLocation = loc;
                        }
                    }

                    loadingIcon.classList.add('hidden');
                    if (minDistance !== null && minDistance <= MAX_DISTANCE_METERS) {
                        successIcon.classList.remove('hidden');
                        title.innerText = `Terdeteksi di dekat ${closestLocation.nama}`;
                        desc.innerText = `Akurasi baik. Jarak Anda: ${Math.round(minDistance)} meter.`;
                        isLocationValid = true;
                    } else {
                        errorIcon.classList.remove('hidden');
                        title.innerText = "Di Luar Jangkauan Gedung";
                        if (closestLocation) {
                            desc.innerText = `Terdekat ke ${closestLocation.nama} (Jarak: ${Math.round(minDistance)}m). Toleransi: ${MAX_DISTANCE_METERS}m.`;
                        } else {
                            desc.innerText = "Jauh dari semua lokasi absensi.";
                        }
                        isLocationValid = false;
                    }
                },
                (error) => {
                    loadingIcon.classList.add('hidden');
                    errorIcon.classList.remove('hidden');
                    title.innerText = "Gagal Mendeteksi Lokasi";
                    
                    let errMsg = "Pastikan GPS aktif dan izin lokasi diberikan.";
                    if (error.code === error.PERMISSION_DENIED) {
                        errMsg = "Izin lokasi ditolak browser. Harap izinkan akses lokasi.";
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        errMsg = "Informasi lokasi tidak tersedia di perangkat Anda.";
                    } else if (error.code === error.TIMEOUT) {
                        errMsg = "Permintaan lokasi melebihi batas waktu.";
                    }
                    desc.innerText = errMsg;
                    isLocationValid = false;
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // Jalankan absensi via AJAX
        function doAbsensi(jenisAbsen) {
            const btnId = jenisAbsen === 'Harian' ? 'btn-absen-harian' : 'btn-absen-rapat';
            const btn = document.getElementById(btnId);

            if (userLatitude === null || userLongitude === null) {
                // GPS belum terdeteksi, coba ambil paksa sekali lagi
                if (navigator.geolocation) {
                    showAlertModal('Mendeteksi GPS...', 'Sistem sedang meminta koordinat GPS perangkat Anda. Harap tunggu...', 'warning');
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            userLatitude = position.coords.latitude;
                            userLongitude = position.coords.longitude;
                            document.getElementById('alert-modal').classList.add('hidden'); // Tutup modal
                            sendAbsensiRequest(jenisAbsen, btn);
                        },
                        (error) => {
                            showAlertModal('Akses GPS Dibutuhkan', 'Gagal mendapatkan lokasi Anda. Pastikan GPS aktif dan izinkan akses lokasi di pengaturan browser.', 'error');
                        },
                        { enableHighAccuracy: true, timeout: 5000 }
                    );
                } else {
                    showAlertModal('GPS Tidak Didukung', 'Browser Anda tidak mendukung deteksi lokasi.', 'error');
                }
                return;
            }

            sendAbsensiRequest(jenisAbsen, btn);
        }

        function sendAbsensiRequest(jenisAbsen, btnElement) {
            // Nonaktifkan tombol sementara untuk mencegah double click
            btnElement.disabled = true;
            const originalHTML = btnElement.innerHTML;
            btnElement.innerHTML = `<i class="fas fa-spinner fa-spin text-xl"></i> <span>Memproses...</span>`;

            const formData = new FormData();
            formData.append('user_lat', userLatitude);
            formData.append('user_lon', userLongitude);
            formData.append('jenis_absen', jenisAbsen);

            fetch('proses-absen.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlertModal('Absensi Berhasil', data.message, 'success');
                    setTimeout(() => window.location.reload(), 4000);
                } else if (data.status === 'rejected') {
                    showAlertModal('Absensi Ditolak', data.message, 'rejected');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalHTML;
                } else {
                    showAlertModal('Gagal Absensi', data.message, 'error');
                    btnElement.disabled = false;
                    btnElement.innerHTML = originalHTML;
                }
            })
            .catch(error => {
                showAlertModal('Kesalahan Koneksi', 'Terjadi kesalahan saat menghubungi server.', 'error');
                console.error('Error:', error);
                btnElement.disabled = false;
                btnElement.innerHTML = originalHTML;
            });
        }

        // Event listener click tombol absensi (dengan check existence)
        const btnHarian = document.getElementById('btn-absen-harian');
        if (btnHarian) {
            btnHarian.addEventListener('click', () => {
                doAbsensi('Harian');
            });
        }

        const btnRapat = document.getElementById('btn-absen-rapat');
        if (btnRapat) {
            btnRapat.addEventListener('click', () => {
                doAbsensi('Rapat');
            });
        }

        // Event listener refresh GPS
        document.getElementById('btn-refresh-gps').addEventListener('click', (e) => {
            e.preventDefault();
            updateGPSStatus();
        });

        // Mulai deteksi GPS saat halaman dimuat
        updateGPSStatus();
    </script>
</body>
</html>