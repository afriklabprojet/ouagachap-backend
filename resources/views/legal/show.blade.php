<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $page->seo_description }}">
    <meta property="og:title" content="{{ $page->seo_title }}">
    <meta property="og:description" content="{{ $page->seo_description }}">
    <meta property="og:image" content="{{ !empty($settings['site_logo']) ? Storage::url($settings['site_logo']) : asset('images/logo-ouagachap.png') }}">
    <meta property="og:type" content="website">
    
    <title>{{ $page->seo_title }}</title>
    
    <link rel="icon" type="image/png" href="{{ !empty($settings['site_logo']) ? Storage::url($settings['site_logo']) : asset('images/logo-ouagachap.png') }}">
    
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
        .prose ul, .prose ol {
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
        $siteLogo = !empty($settings['site_logo']) ? Storage::url($settings['site_logo']) : asset('images/logo-ouagachap.png');
    @endphp

    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-10">
                </a>
                
                <!-- Back to home -->
                <a href="{{ route('home') }}" class="flex items-center text-gray-600 hover:text-primary-500 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Retour à l'accueil
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Breadcrumb -->
            <nav class="mb-8">
                <ol class="flex items-center space-x-2 text-sm text-gray-500">
                    <li>
                        <a href="{{ route('home') }}" class="hover:text-primary-500">Accueil</a>
                    </li>
                    <li>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
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
            @if(count($legalPages) > 1)
            <aside class="mt-12">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Autres pages légales</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($legalPages as $legalPage)
                        @if($legalPage['slug'] !== $page->slug)
                        <a href="{{ route('legal.show', $legalPage['slug']) }}" 
                           class="bg-white rounded-lg shadow-sm p-4 hover:shadow-md transition flex items-center">
                            <svg class="w-5 h-5 text-primary-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
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
    <footer class="bg-gray-900 text-white py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-8 mr-3">
                    <span class="text-gray-400">© {{ date('Y') }} {{ $siteName }}. Tous droits réservés.</span>
                </div>
                
                <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                    @foreach($legalPages as $legalPage)
                    <a href="{{ route('legal.show', $legalPage['slug']) }}" 
                       class="text-gray-400 hover:text-white text-sm transition">
                        {{ $legalPage['title'] }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
