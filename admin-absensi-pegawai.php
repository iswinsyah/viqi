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
                <h1 class="text-2xl font-bold text-gray-900"><i class="fas fa-qrcode text-cyan-600 mr-2"></i>Absensi Kehadiran Pegawai</h1>
                <p class="text-gray-500 mt-1">Arahkan kamera ke QR Code yang tampil di layar absensi.</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8 max-w-xl mx-auto">
                <div id="qr-reader" class="w-full rounded-lg overflow-hidden border-2 border-dashed border-gray-300"></div>
                
                <div id="scan-result" class="mt-6 text-center">
                    <!-- Status akan ditampilkan di sini -->
                </div>
            </div>
        </main>
    </div>
    <script>
        document.getElementById('open-sidebar-hr').addEventListener('click', () => {
            document.getElementById('sidebar-hr').classList.toggle('hidden');
            document.getElementById('sidebar-overlay-hr').classList.toggle('hidden');
        });

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

        function onScanSuccess(decodedText, decodedResult) {
            // Hentikan scan agar tidak berulang
            html5QrcodeScanner.clear();
            
            showStatus('QR terdeteksi. Mengambil lokasi Anda dan memproses...', true);

            // 1. Ambil Geolocation dari HP Pegawai
            if (!navigator.geolocation) {
                showStatus('Geolocation tidak didukung oleh browser Anda.', false);
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const userLat = position.coords.latitude;
                    const userLon = position.coords.longitude;

                    // 2. Kirim data ke server
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
                            const successMsg = `Absensi Berhasil! Waktu: ${data.waktu}. Halaman akan disegarkan.`;
                            showStatus(successMsg, true);
                            setTimeout(() => window.location.reload(), 5000);
                        } else {
                            showStatus(data.message, false);
                            // Mungkin mulai scan lagi setelah gagal
                            setTimeout(() => html5QrcodeScanner.render(onScanSuccess, onScanFailure), 3000);
                        }
                    })
                    .catch(error => {
                        showStatus('Terjadi kesalahan koneksi ke server.', false);
                        console.error('Error:', error);
                    });
                },
                () => {
                    showStatus('Gagal mendapatkan lokasi. Pastikan GPS dan izin lokasi aktif.', false);
                }
            );
        }

        function onScanFailure(error) {
            // Tidak melakukan apa-apa, biarkan scanner terus berjalan
        }

        // Inisialisasi scanner
        let html5QrcodeScanner = new Html5QrcodeScanner(
            "qr-reader", 
            { fps: 10, qrbox: { width: 250, height: 250 } },
            /* verbose= */ false
        );
        
        // Tampilkan status awal
        showStatus('Arahkan kamera ke QR Code untuk absen.', true);
        
        // Mulai scanning
        html5QrcodeScanner.render(onScanSuccess, onScanFailure);

    </script>
</body>
</html>