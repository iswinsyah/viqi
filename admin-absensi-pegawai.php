<?php
require_once 'auth-ustadz.php';
require_once 'koneksi.php';

$active_menu = 'absensi_pegawai';
$ustadz_id = $_SESSION['ustadz_id'];

// Cek apakah hari ini sudah absen atau belum
$today = date('Y-m-d');
$res_absen = $conn->query("SELECT waktu_absen FROM absensi_pegawai WHERE ustadz_id = $ustadz_id AND DATE(waktu_absen) = '$today'");
$sudah_absen = ($res_absen && $res_absen->num_rows > 0);
$waktu_absen_hari_ini = $sudah_absen ? date('H:i:s', strtotime($res_absen->fetch_assoc()['waktu_absen'])) : null;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Kehadiran | Ruang Asatidz</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800 flex h-screen overflow-hidden">
    <?php include 'sidebar-hr.php'; ?>
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-6 z-10 flex-shrink-0">
            <div class="flex items-center"><button id="open-sidebar-hr" class="text-gray-500 hover:text-gray-700 md:hidden mr-4"><i class="fas fa-bars text-xl"></i></button><h2 class="font-bold text-gray-800 hidden sm:block">Sistem Informasi Manajemen (SIM)</h2></div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6">
            <div class="mb-6"><h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-qrcode text-cyan-600 mr-2"></i>Absensi Kehadiran Pegawai</h1></div>
            
            <div class="max-w-md mx-auto">
                <?php if ($sudah_absen): ?>
                    <div class="bg-emerald-100 text-emerald-800 p-6 rounded-xl shadow-sm border border-emerald-200 text-center">
                        <i class="fas fa-check-circle text-5xl mb-4 text-emerald-500"></i>
                        <h3 class="font-bold text-lg">Anda Sudah Absen Hari Ini</h3>
                        <p class="mt-1">Kehadiran Anda tercatat pada pukul <strong><?= $waktu_absen_hari_ini ?></strong>.</p>
                    </div>
                <?php else: ?>
                    <div id="initial-view" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                        <i class="fas fa-map-marker-alt text-5xl mb-4 text-gray-300"></i>
                        <h3 class="font-bold text-lg">Siap untuk Absen?</h3>
                        <p class="text-sm text-gray-500 mt-1 mb-4">Pastikan Anda berada di lokasi absensi yang telah ditentukan.</p>
                        <button onclick="mulaiAbsen()" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md flex items-center justify-center group">
                            <i class="fas fa-camera mr-2"></i> Buka Kamera & Scan QR
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Tampilan Scanner -->
                <div id="scanner-view" class="hidden bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-4 bg-gray-800 text-white text-center">
                        <p class="text-sm">Arahkan kamera ke QR Code di layar komputer.</p>
                    </div>
                    <div id="reader" class="w-full"></div>
                    <div class="p-4 text-center border-t">
                        <button onclick="batalAbsen()" class="text-sm text-red-600 hover:underline">Batalkan</button>
                    </div>
                </div>

                <!-- Tampilan Hasil -->
                <div id="result-view" class="hidden mt-6 p-6 rounded-xl shadow-sm text-center"></div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => { document.getElementById('sidebar-hr').classList.toggle('hidden'); document.getElementById('sidebar-overlay-hr').classList.toggle('hidden'); });

        const initialView = document.getElementById('initial-view');
        const scannerView = document.getElementById('scanner-view');
        const resultView = document.getElementById('result-view');
        let html5QrcodeScanner;

        function mulaiAbsen() {
            initialView.classList.add('hidden');
            scannerView.classList.remove('hidden');

            html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: {width: 250, height: 250} }, false);
            html5QrcodeScanner.render(onScanSuccess, onScanFailure);
        }

        function batalAbsen() {
            scannerView.classList.add('hidden');
            initialView.classList.remove('hidden');
            if (html5QrcodeScanner) html5QrcodeScanner.clear().catch(err => console.error("Gagal stop scanner", err));
        }

        function onScanSuccess(decodedText, decodedResult) {
            html5QrcodeScanner.clear();
            scannerView.classList.add('hidden');
            resultView.classList.remove('hidden');
            resultView.innerHTML = `<i class="fas fa-spinner fa-spin text-3xl text-cyan-500"></i><p class="mt-2 text-sm font-medium">Memvalidasi lokasi & waktu...</p>`;

            // 1. Dapatkan Lokasi GPS Pegawai
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;

                    // 2. Kirim data ke server untuk divalidasi
                    const formData = new FormData();
                    formData.append('qr_data', decodedText);
                    formData.append('user_lat', userLat);
                    formData.append('user_lon', userLon);

                    fetch('proses-absen.php', { method: 'POST', body: formData })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'success') {
                                resultView.className = 'mt-6 p-6 rounded-xl shadow-sm text-center bg-emerald-100 text-emerald-800 border border-emerald-200';
                                resultView.innerHTML = `<i class="fas fa-check-circle text-5xl mb-4 text-emerald-500"></i><h3 class="font-bold text-lg">Absensi Berhasil!</h3><p class="mt-1">Kehadiran Anda tercatat pada pukul <strong>${data.waktu}</strong>.</p>`;
                            } else {
                                throw new Error(data.message);
                            }
                        })
                        .catch(err => {
                            resultView.className = 'mt-6 p-6 rounded-xl shadow-sm text-center bg-rose-100 text-rose-800 border border-rose-200';
                            resultView.innerHTML = `<i class="fas fa-times-circle text-5xl mb-4 text-rose-500"></i><h3 class="font-bold text-lg">Absensi Gagal</h3><p class="mt-1">${err.message}</p>`;
                        });
                },
                (error) => {
                    // Error saat mengambil GPS
                    resultView.className = 'mt-6 p-6 rounded-xl shadow-sm text-center bg-rose-100 text-rose-800 border border-rose-200';
                    resultView.innerHTML = `<i class="fas fa-map-marker-slash text-5xl mb-4 text-rose-500"></i><h3 class="font-bold text-lg">GPS Error</h3><p class="mt-1">Gagal mendapatkan lokasi Anda. Pastikan izin lokasi untuk browser ini sudah diaktifkan.</p>`;
                },
                { enableHighAccuracy: true }
            );
        }

        function onScanFailure(error) {
            // Tidak melakukan apa-apa, biarkan scanner terus mencoba
        }
    </script>
</body>
</html>