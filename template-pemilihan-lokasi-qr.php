<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Lokasi Absensi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen font-sans">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-full max-w-md text-center">
        <i class="fas fa-map-marked-alt text-5xl text-cyan-500 mb-4"></i>
        <h1 class="text-2xl font-bold text-gray-800">Pilih Lokasi Penampil QR</h1>
        <p class="text-gray-500 mt-2 mb-6">Pilih di gedung mana komputer ini berada untuk menampilkan QR Code absensi yang sesuai.</p>
        
        <div class="space-y-3">
            <?php foreach ($locations as $key => $location): ?>
            <a href="?lokasi=<?= $key ?>" 
               class="block w-full text-left p-4 bg-gray-50 hover:bg-cyan-50 border border-gray-200 hover:border-cyan-300 rounded-lg transition-all duration-200">
                <div class="flex items-center">
                    <i class="fas fa-building text-xl text-cyan-600 mr-4"></i>
                    <div>
                        <p class="font-bold text-gray-900"><?= htmlspecialchars($location['nama']) ?></p>
                        <p class="text-xs text-gray-500">Klik untuk menampilkan QR Code lokasi ini</p>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>