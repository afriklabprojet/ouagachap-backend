<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $page->seo_description }}">
    <meta property="og:title" content="{{ $page->seo_title }}">
    <meta property="og:description" content="{{ $page->seo_description }}">
    <meta property="og:image"
        content="{{ !empty($settings['site_logo']) ? Storage::url($settings['site_logo']) : asset('images/logo-ouagachap.png') }}">
    <meta property="og:type" content="website">

    <title>{{ $page->seo_title }}</title>

    <link rel="icon" type="image/png"
        href="{{ !empty($settings['site_logo']) ? Storage::url($settings['site_logo']) : asset('images/logo-ouagachap.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#E31E24',
                            600: '#c41a1f',
                            700: '#a3151a',
                            800: '#821114',
                            900: '#610d0f',
                        },
                        secondary: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#F9A825',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        .prose h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .prose h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #374151;
        }

        .prose p {
            margin-bottom: 1rem;
            line-height: 1.75;
            color: #4b5563;
        }

        .prose ul,
        .prose ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }

        .prose li {
            margin-bottom: 0.5rem;
            color: #4b5563;
        }

        .prose ul li {
            list-style-type: disc;
        }

        .prose ol li {
            list-style-type: decimal;
        }

        .prose blockquote {
            border-left: 4px solid #E31E24;
            padding-left: 1rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #6b7280;
        }

        .prose a {
            color: #E31E24;
            text-decoration: underline;
        }

        .prose a:hover {
            color: #c41a1f;
        }

        .prose strong {
            font-weight: 600;
            color: #1f2937;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">
    @php
        $siteName = $settings['site_name'] ?? 'OUAGA CHAP';
        $siteLogo = !empty($settings['site_logo'])
            ? Storage::url($settings['site_logo'])
            : asset('images/logo-ouagachap.png');
    @endphp

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 md:h-20">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-12 md:h-14">
                </a>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('home') }}#features"
                        class="text-gray-600 hover:text-primary-600 transition">Fonctionnalités</a>
                    <a href="{{ route('home') }}#how-it-works"
                        class="text-gray-600 hover:text-primary-600 transition">Comment ça marche</a>
                    <a href="{{ route('home') }}#pricing"
                        class="text-gray-600 hover:text-primary-600 transition">Tarifs</a>
                    <a href="{{ route('home') }}#contact"
                        class="text-gray-600 hover:text-primary-600 transition">Contact</a>
                    <a href="{{ route('home') }}#download"
                        class="bg-primary-500 text-white px-6 py-2.5 rounded-full font-semibold hover:bg-primary-600 transition">
                        Télécharger
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-4 py-4 space-y-3">
                <a href="{{ route('home') }}#features"
                    class="block py-2 text-gray-600 hover:text-primary-600">Fonctionnalités</a>
                <a href="{{ route('home') }}#how-it-works"
                    class="block py-2 text-gray-600 hover:text-primary-600">Comment ça marche</a>
                <a href="{{ route('home') }}#pricing"
                    class="block py-2 text-gray-600 hover:text-primary-600">Tarifs</a>
                <a href="{{ route('home') }}#contact"
                    class="block py-2 text-gray-600 hover:text-primary-600">Contact</a>
                <a href="{{ route('home') }}#download"
                    class="block bg-primary-500 text-white text-center px-6 py-3 rounded-full font-semibold">
                    Télécharger l'app
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow pt-20 md:pt-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li>
                        <a href="{{ route('home') }}" class="hover:text-primary-500">Accueil</a>
                    </li>
                    <li>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </li>
                    <li class="text-gray-900 font-medium">{{ $page->title }}</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <header class="mb-10">
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>
                <p class="text-gray-500 text-sm">
                    Dernière mise à jour : {{ $page->updated_at->format('d/m/Y') }}
                </p>
            </header>

            <!-- Page Content -->
            <article class="bg-white rounded-2xl shadow-sm p-8 md:p-12 prose max-w-none">
                {!! $page->content !!}
            </article>

            <!-- Related Pages -->
            @if (count($legalPages) > 1)
                <aside class="mt-12">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Autres pages légales</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($legalPages as $legalPage)
                            @if ($legalPage['slug'] !== $page->slug)
                                <a href="{{ route('legal.show', $legalPage['slug']) }}"
                                    class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md transition flex items-center">
                                    <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span class="text-gray-700 hover:text-primary-500">{{ $legalPage['title'] }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </aside>
            @endif
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <!-- Logo & Description -->
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-14">
                    </div>
                    <p class="mb-6 max-w-md">
                        Le service de livraison #1 à Ouagadougou. Rapide, fiable et abordable.
                    </p>
                    <div class="flex gap-4">
                        @if ($settings['social_facebook'] ?? false)
                            <a href="{{ $settings['social_facebook'] }}"
                                class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary-500 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                </svg>
                            </a>
                        @endif
                        @if ($settings['social_twitter'] ?? false)
                            <a href="{{ $settings['social_twitter'] }}"
                                class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary-500 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
                                </svg>
                            </a>
                        @endif
                        @if ($settings['social_instagram'] ?? false)
                            <a href="{{ $settings['social_instagram'] }}"
                                class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center hover:bg-primary-500 transition">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.757-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z" />
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Links -->
                <div>
                    <h4 class="text-white font-semibold mb-6">Liens utiles</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('home') }}#features"
                                class="hover:text-white transition">Fonctionnalités</a></li>
                        <li><a href="{{ route('home') }}#how-it-works" class="hover:text-white transition">Comment ça
                                marche</a></li>
                        <li><a href="{{ route('home') }}#pricing" class="hover:text-white transition">Tarifs</a></li>
                        <li><a href="{{ route('home') }}#download"
                                class="hover:text-white transition">Télécharger</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="text-white font-semibold mb-6">Légal</h4>
                    <ul class="space-y-3">
                        @foreach ($legalPages as $legalPage)
                            <li>
                                <a href="{{ $legalPage['slug'] === 'faq' ? route('faq') : route('legal.show', $legalPage['slug']) }}"
                                    class="hover:text-white transition {{ $legalPage['slug'] === $page->slug ? 'text-white font-medium' : '' }}">
                                    {{ $legalPage['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm">
                    © {{ date('Y') }} {{ $siteName }}. Tous droits réservés.
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });
    </script>
</body>

</html>
