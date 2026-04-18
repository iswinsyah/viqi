<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Villa Quran Baron Malang - Pusat Pendidikan Al-Quran Terbaik</title>
    <meta name="description" content="Pusat Pendidikan Al-Quran Terbaik di Malang. Mewujudkan generasi penghafal Al-Quran yang berkarakter, cerdas, dan mandiri.">
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#064E3B', // Hijau gelap
                        secondary: '#D97706', // Kuning emas
                        urgent: '#DC2626'
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="font-sans text-gray-800 bg-gray-50 antialiased">

    <!-- NAVBAR -->
    <nav class="fixed w-full bg-white shadow-md z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <!-- Logo Area -->
                <div class="flex-shrink-0 flex items-center gap-3 cursor-pointer" onclick="window.scrollTo(0,0)">
                    <div class="w-10 h-10 bg-primary text-white rounded-full flex items-center justify-center font-bold text-xl">
                        VQ
                    </div>
                    <span class="font-bold text-xl text-primary hidden md:block">Villa Quran Baron</span>
                </div>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="#" class="text-gray-600 hover:text-primary font-medium transition">Beranda</a>
                    <a href="#profil" class="text-gray-600 hover:text-primary font-medium transition">Profil</a>
                    <a href="#program" class="text-gray-600 hover:text-primary font-medium transition">Program</a>
                    <a href="#testimoni" class="text-gray-600 hover:text-primary font-medium transition">Testimoni</a>
                    <a href="#daftar" class="bg-secondary hover:bg-yellow-600 text-white px-6 py-2 rounded-full font-bold transition shadow-lg transform hover:-translate-y-0.5">
                        Daftar Sekarang
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button class="text-gray-600 hover:text-primary focus:outline-none p-2">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
    </nav>