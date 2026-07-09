<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'absensi_pegawai';
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
                <p class="text-gray-500 mt-1">Catat kehadiran masuk dan pulang Anda dengan memverifikasi lokasi GPS perangkat Anda.</p>
            </div>

            <!-- PILIHAN METODE (TAB) -->
            <div class="flex justify-center mb-6 max-w-xl mx-auto bg-gray-200 p-1 rounded-xl">
                <button id="tab-gps" class="flex-1 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all duration-200 bg-white text-cyan-700 shadow-sm">
                    <i class="fas fa-location-crosshairs mr-2"></i>Geolocation (GPS)
                </button>
                <button id="tab-qr" class="flex-1 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all duration-200 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-qrcode mr-2"></i>Scan QR Code
                </button>
            </div>

            <!-- CONTAINER ABSENSI GEOLOCATION -->
            <div id="gps-container" class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6 max-w-xl mx-auto text-center transition-all">
                <div class="mb-6">
                    <label for="jenis_absen" class="block text-left text-sm font-semibold text-gray-700 mb-2">Jenis Absensi / Kegiatan:</label>
                    <div class="relative">
                        <select id="jenis_absen" class="block w-full pl-10 pr-3 py-3 text-base border-gray-300 focus:outline-none focus:ring-cyan-500 focus:border-cyan-500 sm:text-sm rounded-lg bg-gray-50 border transition duration-200">
                            <option value="Harian">Absensi Harian (Masuk / Pulang)</option>
                            <option value="Rapat">Absensi Rapat</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-calendar-day text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Tampilan Status GPS -->
                <div id="gps-status-card" class="bg-slate-50 border border-slate-100 rounded-xl p-4 mb-6 text-sm text-gray-600 flex items-center justify-between">
                    <div class="flex items-center text-left">
                        <i class="fas fa-circle-notch fa-spin text-cyan-500 text-lg mr-3" id="gps-loading-icon"></i>
                        <i class="fas fa-location-dot text-emerald-500 text-lg mr-3 hidden" id="gps-success-icon"></i>
                        <i class="fas fa-circle-exclamation text-rose-500 text-lg mr-3 hidden" id="gps-error-icon"></i>
                        <div>
                            <p class="font-semibold text-gray-800" id="gps-status-title">Mendeteksi Lokasi Anda...</p>
                            <p class="text-xs text-gray-500" id="gps-status-desc">Izinkan akses GPS jika diminta oleh browser.</p>
                        </div>
                    </div>
                    <button id="btn-refresh-gps" class="text-xs font-semibold text-cyan-600 hover:text-cyan-800 bg-cyan-50 hover:bg-cyan-100 px-3 py-1.5 rounded-lg transition">
                        <i class="fas fa-sync-alt mr-1"></i>Segarkan
                    </button>
                </div>

                <!-- Tombol Absen GPS -->
                <button id="btn-absen-gps" disabled class="w-full py-4 px-6 bg-gray-300 text-gray-500 font-bold rounded-xl shadow-md transition-all duration-300 flex items-center justify-center cursor-not-allowed">
                    <i class="fas fa-fingerprint text-2xl mr-3"></i>
                    <span>Catat Kehadiran Saya</span>
                </button>
            </div>

            <!-- CONTAINER ABSENSI QR (HIDDEN BY DEFAULT) -->
            <div id="qr-container" class="bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6 max-w-xl mx-auto hidden">
                <p class="text-sm text-gray-500 mb-4 text-center">Arahkan kamera ke QR Code absensi resmi di lokasi gedung.</p>
                <div id="qr-reader" class="w-full rounded-lg overflow-hidden border border-gray-200"></div>
            </div>

            <!-- KOTAK HASIL/STATUS UTAMA -->
            <div id="scan-result" class="max-w-xl mx-auto mb-8 text-center">
                <!-- Status akan diisi secara dinamis -->
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
        let html5QrcodeScanner = null;

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

        function showStatus(message, isSuccess) {
            const resultDiv = document.getElementById('scan-result');
            const icon = isSuccess ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-rose-500';
            const bgColor = isSuccess ? 'bg-emerald-50' : 'bg-rose-50';
            const borderColor = isSuccess ? 'border-emerald-200' : 'border-rose-200';
            const textColor = isSuccess ? 'text-emerald-800' : 'text-rose-800';

            resultDiv.innerHTML = `
                <div class="${bgColor} ${borderColor} ${textColor} border px-4 py-3 rounded-lg shadow-sm flex items-center justify-center">
                    <i class="fas ${icon} mr-3 text-xl"></i>
                    <span class="font-medium">${message}</span>
                </div>
            `;
        }

        function enableAbsenButton() {
            const btn = document.getElementById('btn-absen-gps');
            btn.disabled = false;
            btn.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.add('bg-cyan-600', 'hover:bg-cyan-700', 'text-white', 'hover:shadow-lg', 'active:scale-95');
        }

        function disableAbsenButton() {
            const btn = document.getElementById('btn-absen-gps');
            btn.disabled = true;
            btn.classList.add('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btn.classList.remove('bg-cyan-600', 'hover:bg-cyan-700', 'text-white', 'hover:shadow-lg', 'active:scale-95');
        }

        // Ambil data lokasi user
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
            disableAbsenButton();

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
                        enableAbsenButton();
                    } else {
                        errorIcon.classList.remove('hidden');
                        title.innerText = "Di Luar Jangkauan Gedung";
                        if (closestLocation) {
                            desc.innerText = `Terdekat ke ${closestLocation.nama} (Jarak: ${Math.round(minDistance)}m). Toleransi: ${MAX_DISTANCE_METERS}m.`;
                        } else {
                            desc.innerText = "Jauh dari semua lokasi absensi.";
                        }
                        isLocationValid = false;
                        disableAbsenButton();
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
                    disableAbsenButton();
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
            );
        }

        // Event listener absen GPS
        document.getElementById('btn-absen-gps').addEventListener('click', () => {
            if (!isLocationValid || userLatitude === null || userLongitude === null) {
                showStatus('Lokasi Anda tidak berada di dalam wilayah absensi.', false);
                return;
            }

            const jenisAbsen = document.getElementById('jenis_absen').value;
            
            disableAbsenButton();
            showStatus('Mengirim data absensi...', true);

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
                    showStatus(data.message + ` Waktu: ${data.waktu}. Halaman akan disegarkan...`, true);
                    setTimeout(() => window.location.reload(), 4000);
                } else {
                    showStatus(data.message, false);
                    enableAbsenButton();
                }
            })
            .catch(error => {
                showStatus('Terjadi kesalahan koneksi ke server.', false);
                console.error('Error:', error);
                enableAbsenButton();
            });
        });

        // Event listener refresh GPS
        document.getElementById('btn-refresh-gps').addEventListener('click', (e) => {
            e.preventDefault();
            updateGPSStatus();
        });

        // Tab Toggling
        const tabGps = document.getElementById('tab-gps');
        const tabQr = document.getElementById('tab-qr');
        const gpsContainer = document.getElementById('gps-container');
        const qrContainer = document.getElementById('qr-container');

        tabGps.addEventListener('click', () => {
            tabGps.classList.add('bg-white', 'text-cyan-700', 'shadow-sm');
            tabGps.classList.remove('text-gray-600', 'hover:text-gray-900');
            tabQr.classList.remove('bg-white', 'text-cyan-700', 'shadow-sm');
            tabQr.classList.add('text-gray-600', 'hover:text-gray-900');

            gpsContainer.classList.remove('hidden');
            qrContainer.classList.add('hidden');
            
            if (html5QrcodeScanner) {
                try {
                    html5QrcodeScanner.clear();
                } catch(e) {}
            }
        });

        tabQr.addEventListener('click', () => {
            tabQr.classList.add('bg-white', 'text-cyan-700', 'shadow-sm');
            tabQr.classList.remove('text-gray-600', 'hover:text-gray-900');
            tabGps.classList.remove('bg-white', 'text-cyan-700', 'shadow-sm');
            tabGps.classList.add('text-gray-600', 'hover:text-gray-900');

            qrContainer.classList.remove('hidden');
            gpsContainer.classList.add('hidden');

            initQrScanner();
        });

        // Scan QR Success Handler
        function onScanSuccess(decodedText, decodedResult) {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.clear();
            }
            
            showStatus('QR terdeteksi. Mengambil lokasi Anda dan memproses...', true);

            if (!navigator.geolocation) {
                showStatus('Geolocation tidak didukung oleh browser Anda.', false);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;

                    const formData = new FormData();
                    formData.append('qr_data', decodedText);
                    formData.append('user_lat', userLat);
                    formData.append('user_lon', userLon);

                    fetch('proses-absen.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            showStatus(`Absensi Berhasil! Waktu: ${data.waktu}. Halaman akan disegarkan.`, true);
                            setTimeout(() => window.location.reload(), 4000);
                        } else {
                            showStatus(data.message, false);
                            setTimeout(() => initQrScanner(), 3000);
                        }
                    })
                    .catch(error => {
                        showStatus('Terjadi kesalahan koneksi ke server.', false);
                        console.error('Error:', error);
                    });
                },
                () => {
                    showStatus('Gagal mendapatkan lokasi. Pastikan GPS dan izin lokasi aktif.', false);
                    setTimeout(() => initQrScanner(), 3000);
                }
            );
        }

        function onScanFailure(error) {
            // Biarkan scanner terus memindai
        }

        function initQrScanner() {
            if (!html5QrcodeScanner) {
                html5QrcodeScanner = new Html5QrcodeScanner(
                    "qr-reader", 
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    false
                );
            }
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        // Mulai deteksi GPS saat halaman dimuat
        updateGPSStatus();
    </script>
</body>
</html>