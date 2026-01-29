<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OUAGA CHAP - Service de livraison rapide à Ouagadougou, Burkina Faso. Livraison de colis, courses et repas en moins de 30 minutes.">
    <meta name="keywords" content="livraison, Ouagadougou, Burkina Faso, coursier, colis, rapide, OUAGA CHAP">
    <meta name="author" content="OUAGA CHAP">
    
    <!-- Open Graph -->
    <meta property="og:title" content="OUAGA CHAP - Livraison rapide à Ouagadougou">
    <meta property="og:description" content="Service de livraison express à Ouagadougou. Commandez et recevez vos colis en moins de 30 minutes.">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:type" content="website">
    
    <title>OUAGA CHAP - Livraison rapide à Ouagadougou</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#FEF3F3',
                            100: '#FDE8E8',
                            200: '#FBD0D0',
                            300: '#F8A8A8',
                            400: '#F47070',
                            500: '#E31E24',
                            600: '#C91A1F',
                            700: '#A5151A',
                            800: '#871215',
                            900: '#6E0F12',
                        },
                        secondary: {
                            50: '#FFFBEB',
                            100: '#FEF3C7',
                            200: '#FDE68A',
                            300: '#FCD34D',
                            400: '#FBBF24',
                            500: '#F9A825',
                            600: '#D97706',
                            700: '#B45309',
                            800: '#92400E',
                            900: '#78350F',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        .hero-gradient {
            background: linear-gradient(135deg, #E31E24 0%, #C91A1F 50%, #A5151A 100%);
        }
        .blob {
            animation: blob 7s infinite;
        }
        @keyframes blob {
            0% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(30px, -50px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
            100% { transform: translate(0px, 0px) scale(1); }
        }
        .phone-mockup {
            transform: perspective(1000px) rotateY(-5deg);
            transition: transform 0.5s ease;
        }
        .phone-mockup:hover {
            transform: perspective(1000px) rotateY(0deg);
        }
        .download-btn {
            transition: all 0.3s ease;
        }
        .download-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 40px -10px rgba(227, 30, 36, 0.5);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            background: linear-gradient(135deg, #E31E24, #F9A825);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        html {
            scroll-behavior: smooth;
        }
        .brand-red {
            background-color: #E31E24;
        }
        .brand-yellow {
            background-color: #F9A825;
        }
    </style>
</head>
<body class="font-sans antialiased bg-white text-gray-900">
    
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <!-- Logo -->
                <a href="#" class="flex items-center">
                    <img src="{{ asset('images/logo-ouagachap.png') }}" alt="OUAGA CHAP" class="h-12 md:h-14">
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-primary-600 transition">Fonctionnalités</a>
                    <a href="#how-it-works" class="text-gray-600 hover:text-primary-600 transition">Comment ça marche</a>
                    <a href="#pricing" class="text-gray-600 hover:text-primary-600 transition">Tarifs</a>
                    <a href="#contact" class="text-gray-600 hover:text-primary-600 transition">Contact</a>
                    <a href="#download" class="bg-primary-500 text-white px-6 py-2.5 rounded-full font-semibold hover:bg-primary-600 transition download-btn">
                        Télécharger
                    </a>
                </div>
                
                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-4 space-y-3">
                <a href="#features" class="block py-2 text-gray-600 hover:text-primary-600">Fonctionnalités</a>
                <a href="#how-it-works" class="block py-2 text-gray-600 hover:text-primary-600">Comment ça marche</a>
                <a href="#pricing" class="block py-2 text-gray-600 hover:text-primary-600">Tarifs</a>
                <a href="#contact" class="block py-2 text-gray-600 hover:text-primary-600">Contact</a>
                <a href="#download" class="block bg-primary-500 text-white text-center px-6 py-3 rounded-full font-semibold">
                    Télécharger l'app
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center pt-20 overflow-hidden">
        <!-- Background Blobs -->
        <div class="absolute top-20 right-0 w-72 h-72 bg-primary-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 blob"></div>
        <div class="absolute bottom-20 left-0 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 blob" style="animation-delay: 2s;"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Content -->
                <div data-aos="fade-right">
                    <span class="inline-block bg-primary-100 text-primary-700 px-4 py-2 rounded-full text-sm font-semibold mb-6">
                        🚀 #1 à Ouagadougou
                    </span>
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                        Livraison <span class="text-primary-500">express</span> à Ouagadougou
                    </h1>
                    <p class="text-lg md:text-xl text-gray-600 mb-8 leading-relaxed">
                        Vos colis livrés en moins de <strong>30 minutes</strong>. 
                        Courses, documents, repas... Nous livrons tout ce dont vous avez besoin, 
                        partout dans la ville.
                    </p>
                    
                    <!-- Download Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="{{ asset('downloads/ouaga-chap-client.apk') }}" 
                           class="flex items-center justify-center gap-3 bg-secondary-500 text-white px-6 py-4 rounded-xl hover:bg-secondary-600 transition download-btn">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-xs opacity-80">Télécharger</div>
                                <div class="text-lg font-semibold">App Client</div>
                            </div>
                        </a>
                        <a href="{{ asset('downloads/ouaga-chap-coursier.apk') }}" 
                           class="flex items-center justify-center gap-3 bg-primary-500 text-white px-6 py-4 rounded-xl hover:bg-primary-600 transition download-btn">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            <div class="text-left">
                                <div class="text-xs opacity-80">Devenir coursier</div>
                                <div class="text-lg font-semibold">App Coursier</div>
                            </div>
                        </a>
                    </div>
                    
                    <!-- Stats -->
                    <div class="flex flex-wrap gap-8">
                        <div>
                            <div class="text-3xl font-bold stat-number">10K+</div>
                            <div class="text-gray-500 text-sm">Livraisons</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold stat-number">500+</div>
                            <div class="text-gray-500 text-sm">Coursiers actifs</div>
                        </div>
                        <div>
                            <div class="text-3xl font-bold stat-number">4.8★</div>
                            <div class="text-gray-500 text-sm">Note moyenne</div>
                        </div>
                    </div>
                </div>
                
                <!-- Phone Mockup -->
                <div class="relative" data-aos="fade-left">
                    <div class="relative z-10 flex justify-center">
                        <!-- Phone Frame -->
                        <div class="phone-mockup relative w-64 md:w-80">
                            <div class="bg-gray-900 rounded-[3rem] p-3 shadow-2xl">
                                <div class="bg-white rounded-[2.5rem] overflow-hidden">
                                    <!-- Phone Screen Content -->
                                    <div class="h-[500px] md:h-[600px] bg-gradient-to-b from-primary-500 to-primary-600 p-6 flex flex-col">
                                        <!-- Status Bar -->
                                        <div class="flex justify-between text-white text-xs mb-8">
                                            <span>9:41</span>
                                            <div class="flex gap-1">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.18L12 21z"/></svg>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9zm8 8l3 3 3-3c-1.65-1.66-4.34-1.66-6 0zm-4-4l2 2c2.76-2.76 7.24-2.76 10 0l2-2C15.14 9.14 8.87 9.14 5 13z"/></svg>
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M15.67 4H14V2h-4v2H8.33C7.6 4 7 4.6 7 5.33v15.33C7 21.4 7.6 22 8.33 22h7.33c.74 0 1.34-.6 1.34-1.33V5.33C17 4.6 16.4 4 15.67 4z"/></svg>
                                            </div>
                                        </div>
                                        
                                        <!-- App Content -->
                                        <div class="text-white text-center mb-6">
                                            <div class="bg-white rounded-2xl p-3 w-24 h-24 mx-auto mb-4 flex items-center justify-center">
                                                <img src="{{ asset('images/logo-ouagachap.png') }}" alt="Logo" class="h-20 object-contain">
                                            </div>
                                            <h3 class="text-2xl font-bold">Bonjour! 👋</h3>
                                            <p class="text-white/80 text-sm mt-2">Où livrons-nous aujourd'hui?</p>
                                        </div>
                                        
                                        <!-- Search Bar -->
                                        <div class="bg-white rounded-xl p-4 shadow-lg mb-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                                <span class="text-gray-400 text-sm">Entrez l'adresse de livraison...</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        <div class="grid grid-cols-3 gap-3 mt-auto">
                                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                                <div class="text-2xl mb-1">📦</div>
                                                <div class="text-xs text-white">Colis</div>
                                            </div>
                                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                                <div class="text-2xl mb-1">🍔</div>
                                                <div class="text-xs text-white">Repas</div>
                                            </div>
                                            <div class="bg-white/20 rounded-xl p-3 text-center">
                                                <div class="text-2xl mb-1">📄</div>
                                                <div class="text-xs text-white">Documents</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Phone Notch -->
                            <div class="absolute top-3 left-1/2 transform -translate-x-1/2 w-24 h-6 bg-gray-900 rounded-b-xl"></div>
                        </div>
                    </div>
                    
                    <!-- Floating Elements -->
                    <div class="absolute top-10 -left-10 bg-white rounded-xl shadow-xl p-4 hidden lg:flex items-center gap-3" data-aos="fade-up" data-aos-delay="200">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-sm font-semibold">Livraison confirmée!</div>
                            <div class="text-xs text-gray-500">Il y a 2 min</div>
                        </div>
                    </div>
                    
                    <div class="absolute bottom-20 -right-5 bg-white rounded-xl shadow-xl p-4 hidden lg:flex items-center gap-3" data-aos="fade-up" data-aos-delay="400">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-lg">🛵</span>
                        </div>
                        <div>
                            <div class="text-sm font-semibold">Coursier en route</div>
                            <div class="text-xs text-gray-500">5 min restantes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce hidden md:block">
            <a href="#features" class="text-gray-400 hover:text-primary-500 transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </a>
        </div>
    </section>
    
    <!-- Features Section -->
    <section id="features" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-primary-500 font-semibold text-sm uppercase tracking-wider">Fonctionnalités</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Pourquoi choisir OUAGA CHAP?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Une application conçue pour faciliter votre quotidien avec des fonctionnalités pensées pour vous.
                </p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl" data-aos="fade-up">
                    <div class="w-14 h-14 bg-primary-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Livraison Ultra-Rapide</h3>
                    <p class="text-gray-600">
                        Vos colis livrés en moins de 30 minutes partout à Ouagadougou. Notre réseau de coursiers est prêt 24h/24.
                    </p>
                </div>
                
                <!-- Feature 2 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Suivi en Temps Réel</h3>
                    <p class="text-gray-600">
                        Suivez votre coursier sur la carte en temps réel. Vous savez exactement où se trouve votre colis.
                    </p>
                </div>
                
                <!-- Feature 3 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Paiement Sécurisé</h3>
                    <p class="text-gray-600">
                        Payez par Mobile Money (Orange Money, Moov Money) ou en espèces à la livraison. Simple et sécurisé.
                    </p>
                </div>
                
                <!-- Feature 4 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Support 24/7</h3>
                    <p class="text-gray-600">
                        Notre équipe est disponible 24h/24 pour répondre à vos questions et résoudre vos problèmes.
                    </p>
                </div>
                
                <!-- Feature 5 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl" data-aos="fade-up" data-aos-delay="400">
                    <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Coursiers Vérifiés</h3>
                    <p class="text-gray-600">
                        Tous nos coursiers sont vérifiés et notés. Consultez les avis avant de commander.
                    </p>
                </div>
                
                <!-- Feature 6 -->
                <div class="feature-card bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl" data-aos="fade-up" data-aos-delay="500">
                    <div class="w-14 h-14 bg-red-100 rounded-xl flex items-center justify-center mb-6">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Assurance Colis</h3>
                    <p class="text-gray-600">
                        Vos colis sont assurés contre la perte et les dommages. Livraison garantie ou remboursement.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- How It Works -->
    <section id="how-it-works" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-primary-500 font-semibold text-sm uppercase tracking-wider">Simple & Rapide</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Comment ça marche?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    En 3 étapes simples, faites livrer vos colis partout à Ouagadougou.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center" data-aos="fade-up">
                    <div class="relative mb-8">
                        <div class="w-20 h-20 bg-primary-500 rounded-full flex items-center justify-center mx-auto text-white text-3xl font-bold">
                            1
                        </div>
                        <div class="hidden md:block absolute top-1/2 left-full w-full h-0.5 bg-primary-200 -translate-y-1/2"></div>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Commandez</h3>
                    <p class="text-gray-600">
                        Ouvrez l'app, entrez les adresses de récupération et de livraison, puis décrivez votre colis.
                    </p>
                </div>
                
                <!-- Step 2 -->
                <div class="text-center" data-aos="fade-up" data-aos-delay="200">
                    <div class="relative mb-8">
                        <div class="w-20 h-20 bg-primary-500 rounded-full flex items-center justify-center mx-auto text-white text-3xl font-bold">
                            2
                        </div>
                        <div class="hidden md:block absolute top-1/2 left-full w-full h-0.5 bg-primary-200 -translate-y-1/2"></div>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Suivez</h3>
                    <p class="text-gray-600">
                        Un coursier accepte votre commande. Suivez-le en temps réel sur la carte jusqu'à la livraison.
                    </p>
                </div>
                
                <!-- Step 3 -->
                <div class="text-center" data-aos="fade-up" data-aos-delay="400">
                    <div class="mb-8">
                        <div class="w-20 h-20 bg-primary-500 rounded-full flex items-center justify-center mx-auto text-white text-3xl font-bold">
                            3
                        </div>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Recevez</h3>
                    <p class="text-gray-600">
                        Votre colis est livré! Payez par Mobile Money ou en espèces et notez votre coursier.
                    </p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Pricing Section -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-primary-500 font-semibold text-sm uppercase tracking-wider">Tarifs</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Des prix transparents</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">
                    Pas de frais cachés. Le prix affiché est le prix payé.
                </p>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
                <!-- Basic -->
                <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition" data-aos="fade-up">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">🛵</span>
                        </div>
                        <h3 class="text-xl font-bold">Moto</h3>
                        <p class="text-gray-500 text-sm">Petits colis</p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold">500</span>
                        <span class="text-gray-500">FCFA</span>
                        <p class="text-gray-500 text-sm mt-1">+ 100 FCFA/km</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Jusqu'à 10 kg
                        </li>
                        <li class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Livraison en 30 min
                        </li>
                        <li class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Suivi en temps réel
                        </li>
                    </ul>
                    <a href="#download" class="block text-center bg-gray-100 text-gray-800 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">
                        Commander
                    </a>
                </div>
                
                <!-- Popular -->
                <div class="bg-primary-500 rounded-2xl p-8 shadow-xl relative transform md:-translate-y-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="absolute top-0 right-0 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl">
                        POPULAIRE
                    </div>
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">🚗</span>
                        </div>
                        <h3 class="text-xl font-bold text-white">Voiture</h3>
                        <p class="text-white/70 text-sm">Colis moyens</p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold text-white">1500</span>
                        <span class="text-white/70">FCFA</span>
                        <p class="text-white/70 text-sm mt-1">+ 150 FCFA/km</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Jusqu'à 50 kg
                        </li>
                        <li class="flex items-center gap-2 text-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Livraison en 45 min
                        </li>
                        <li class="flex items-center gap-2 text-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Climatisé
                        </li>
                        <li class="flex items-center gap-2 text-white">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Fragile accepté
                        </li>
                    </ul>
                    <a href="#download" class="block text-center bg-white text-primary-600 py-3 rounded-xl font-semibold hover:bg-gray-100 transition">
                        Commander
                    </a>
                </div>
                
                <!-- Premium -->
                <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition" data-aos="fade-up" data-aos-delay="200">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-3xl">🚚</span>
                        </div>
                        <h3 class="text-xl font-bold">Camionnette</h3>
                        <p class="text-gray-500 text-sm">Gros colis</p>
                    </div>
                    <div class="text-center mb-6">
                        <span class="text-4xl font-bold">5000</span>
                        <span class="text-gray-500">FCFA</span>
                        <p class="text-gray-500 text-sm mt-1">+ 200 FCFA/km</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Jusqu'à 500 kg
                        </li>
                        <li class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Livraison en 1h
                        </li>
                        <li class="flex items-center gap-2 text-gray-600">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Aide au chargement
                        </li>
                    </ul>
                    <a href="#download" class="block text-center bg-gray-100 text-gray-800 py-3 rounded-xl font-semibold hover:bg-gray-200 transition">
                        Commander
                    </a>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Become a Courier CTA -->
    <section class="py-20 hero-gradient">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div data-aos="fade-right">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                        Devenez coursier et gagnez de l'argent
                    </h2>
                    <p class="text-white/80 text-lg mb-8">
                        Rejoignez notre équipe de coursiers et travaillez à votre rythme. 
                        Gagnez jusqu'à <strong>150,000 FCFA</strong> par mois en effectuant des livraisons.
                    </p>
                    
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center gap-3 text-white">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            Horaires flexibles - Travaillez quand vous voulez
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            Paiements quotidiens - Retirez vos gains chaque jour
                        </li>
                        <li class="flex items-center gap-3 text-white">
                            <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            Bonus et primes - Gagnez plus avec les défis
                        </li>
                    </ul>
                    
                    <a href="{{ asset('downloads/ouaga-chap-coursier.apk') }}" 
                       class="inline-flex items-center gap-3 bg-white text-primary-600 px-8 py-4 rounded-xl font-semibold hover:bg-gray-100 transition download-btn">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Télécharger l'app Coursier
                    </a>
                </div>
                
                <div class="relative" data-aos="fade-left">
                    <div class="bg-white/10 rounded-2xl p-8 backdrop-blur-sm">
                        <div class="text-center text-white">
                            <div class="text-6xl mb-4">🛵</div>
                            <h3 class="text-2xl font-bold mb-2">Rejoignez-nous</h3>
                            <p class="text-white/80 mb-6">Plus de 500 coursiers nous font confiance</p>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-3xl font-bold">85%</div>
                                    <div class="text-sm text-white/70">Commission coursier</div>
                                </div>
                                <div class="bg-white/10 rounded-xl p-4">
                                    <div class="text-3xl font-bold">24/7</div>
                                    <div class="text-sm text-white/70">Support disponible</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Testimonials -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16" data-aos="fade-up">
                <span class="text-primary-500 font-semibold text-sm uppercase tracking-wider">Témoignages</span>
                <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-4">Ce que disent nos utilisateurs</h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition" data-aos="fade-up">
                    <div class="flex items-center gap-1 mb-4">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-gray-600 mb-6">
                        "Service excellent! J'ai fait livrer des documents urgents en 20 minutes. Le coursier était très professionnel. Je recommande!"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-primary-600 font-bold">AK</span>
                        </div>
                        <div>
                            <div class="font-semibold">Aminata Konaté</div>
                            <div class="text-gray-500 text-sm">Entrepreneuse</div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial 2 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition" data-aos="fade-up" data-aos-delay="100">
                    <div class="flex items-center gap-1 mb-4">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-gray-600 mb-6">
                        "En tant que coursier, je gagne bien ma vie avec OUAGA CHAP. Les paiements sont rapides et l'application est facile à utiliser."
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-green-600 font-bold">OS</span>
                        </div>
                        <div>
                            <div class="font-semibold">Oumar Sanou</div>
                            <div class="text-gray-500 text-sm">Coursier</div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial 3 -->
                <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition" data-aos="fade-up" data-aos-delay="200">
                    <div class="flex items-center gap-1 mb-4">
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </div>
                    <p class="text-gray-600 mb-6">
                        "J'utilise OUAGA CHAP pour mon restaurant. Mes clients reçoivent leurs commandes encore chaudes. C'est génial!"
                    </p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 font-bold">FD</span>
                        </div>
                        <div>
                            <div class="font-semibold">Fatou Diallo</div>
                            <div class="text-gray-500 text-sm">Restauratrice</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Download Section -->
    <section id="download" class="py-20 bg-gray-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center" data-aos="fade-up">
                <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">
                    Téléchargez l'application maintenant
                </h2>
                <p class="text-gray-400 max-w-2xl mx-auto mb-12">
                    Disponible sur Android. Téléchargez l'APK directement et commencez à utiliser OUAGA CHAP.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-6 justify-center">
                    <!-- Client App -->
                    <a href="{{ asset('downloads/ouaga-chap-client.apk') }}" 
                       class="flex items-center gap-4 bg-secondary-500 text-white px-8 py-5 rounded-2xl hover:bg-secondary-600 transition download-btn group">
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <div class="text-sm text-white/80">Application Client</div>
                            <div class="text-xl font-bold">Télécharger APK</div>
                            <div class="text-xs text-white/60 mt-1">Version 1.0.0 • 25 MB</div>
                        </div>
                        <svg class="w-6 h-6 text-white/70 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                    
                    <!-- Courier App -->
                    <a href="{{ asset('downloads/ouaga-chap-coursier.apk') }}" 
                       class="flex items-center gap-4 bg-primary-500 text-white px-8 py-5 rounded-2xl hover:bg-primary-600 transition download-btn group">
                        <div class="w-14 h-14 bg-white/20 rounded-xl flex items-center justify-center group-hover:bg-white/30 transition">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <div class="text-sm text-white/70">Application Coursier</div>
                            <div class="text-xl font-bold">Télécharger APK</div>
                            <div class="text-xs text-white/50 mt-1">Version 1.0.0 • 28 MB</div>
                        </div>
                        <svg class="w-6 h-6 text-white/70 group-hover:text-white transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                    </a>
                </div>
                
                <!-- Installation Instructions -->
                <div class="mt-12 p-6 bg-gray-800 rounded-xl max-w-2xl mx-auto text-left" data-aos="fade-up" data-aos-delay="200">
                    <h4 class="text-white font-semibold mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Comment installer l'APK
                    </h4>
                    <ol class="text-gray-400 text-sm space-y-2">
                        <li>1. Téléchargez le fichier APK sur votre téléphone Android</li>
                        <li>2. Allez dans Paramètres → Sécurité → Autoriser les sources inconnues</li>
                        <li>3. Ouvrez le fichier APK téléchargé et cliquez sur "Installer"</li>
                        <li>4. Une fois installé, ouvrez l'application et créez votre compte</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Contact Section -->
    <section id="contact" class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12">
                <div data-aos="fade-right">
                    <span class="text-primary-500 font-semibold text-sm uppercase tracking-wider">Contact</span>
                    <h2 class="text-3xl md:text-4xl font-bold mt-2 mb-6">Besoin d'aide?</h2>
                    <p class="text-gray-600 mb-8">
                        Notre équipe est disponible 24h/24 pour répondre à vos questions. 
                        N'hésitez pas à nous contacter.
                    </p>
                    
                    <div class="space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Téléphone</div>
                                <div class="font-semibold">+226 70 00 00 00</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">WhatsApp</div>
                                <div class="font-semibold">+226 70 00 00 00</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Email</div>
                                <div class="font-semibold">contact@ouagachap.com</div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <div class="text-sm text-gray-500">Adresse</div>
                                <div class="font-semibold">Ouagadougou, Burkina Faso</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="bg-gray-50 rounded-2xl p-8" data-aos="fade-left">
                    <form action="#" method="POST" class="space-y-6">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nom complet</label>
                            <input type="text" name="name" required
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sujet</label>
                            <select name="subject" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition">
                                <option value="">Sélectionnez un sujet</option>
                                <option value="support">Support technique</option>
                                <option value="partnership">Partenariat</option>
                                <option value="courier">Devenir coursier</option>
                                <option value="other">Autre</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea name="message" rows="4" required
                                      class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-200 outline-none transition resize-none"></textarea>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-primary-500 text-white py-4 rounded-xl font-semibold hover:bg-primary-600 transition download-btn">
                            Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <!-- Logo & Description -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ asset('images/logo-ouagachap.png') }}" alt="OUAGA CHAP" class="h-14">
                    </div>
                    <p class="mb-6 max-w-md">
                        Le service de livraison #1 à Ouagadougou. Rapide, fiable et abordable. 
                        Nous connectons clients et coursiers pour des livraisons express.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary-500 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary-500 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary-500 transition">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.757-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/></svg>
                        </a>
                    </div>
                </div>
                
                <!-- Links -->
                <div>
                    <h4 class="text-white font-semibold mb-6">Liens utiles</h4>
                    <ul class="space-y-3">
                        <li><a href="#features" class="hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="#how-it-works" class="hover:text-white transition">Comment ça marche</a></li>
                        <li><a href="#pricing" class="hover:text-white transition">Tarifs</a></li>
                        <li><a href="#download" class="hover:text-white transition">Télécharger</a></li>
                        <li><a href="{{ url('/admin') }}" class="hover:text-white transition">Espace Admin</a></li>
                    </ul>
                </div>
                
                <!-- Legal -->
                <div>
                    <h4 class="text-white font-semibold mb-6">Légal</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="hover:text-white transition">Conditions d'utilisation</a></li>
                        <li><a href="#" class="hover:text-white transition">Politique de confidentialité</a></li>
                        <li><a href="#" class="hover:text-white transition">Mentions légales</a></li>
                        <li><a href="#" class="hover:text-white transition">FAQ</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm">
                    © {{ date('Y') }} OUAGA CHAP. Tous droits réservés.
                </p>
                <p class="text-sm">
                    Fait avec ❤️ à Ouagadougou, Burkina Faso
                </p>
            </div>
        </div>
    </footer>
    
    <!-- AOS Animation Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });
        
        // Mobile menu toggle
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
        
        // Close mobile menu when clicking a link
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                document.getElementById('mobile-menu').classList.add('hidden');
            });
        });
        
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('shadow-md');
            } else {
                nav.classList.remove('shadow-md');
            }
        });
    </script>
</body>
</html>
