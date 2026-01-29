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
        .faq-item details[open] summary {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        .faq-item details[open] .faq-content {
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .faq-item summary::-webkit-details-marker {
            display: none;
        }
        .prose p {
            margin-bottom: 0.75rem;
            color: #4b5563;
        }
        .prose ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .prose a {
            color: #E31E24;
            text-decoration: underline;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    @php
        $siteName = $settings['site_name'] ?? 'OUAGA CHAP';
        $siteLogo = !empty($settings['site_logo']) ? Storage::url($settings['site_logo']) : asset('images/logo-ouagachap.png');
        
        // Parser le contenu FAQ en questions/réponses
        // Format attendu: <h3>Question</h3><p>Réponse</p>
        $content = $page->content;
        preg_match_all('/<h3[^>]*>(.*?)<\/h3>\s*(.*?)(?=<h3|$)/is', $content, $matches, PREG_SET_ORDER);
        
        $faqItems = [];
        foreach ($matches as $match) {
            $faqItems[] = [
                'question' => strip_tags($match[1]),
                'answer' => trim($match[2])
            ];
        }
        
        // Si pas de format h3, utiliser le contenu brut
        if (empty($faqItems)) {
            $faqItems = [['question' => '', 'answer' => $content]];
        }
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
            <header class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-100 rounded-full mb-6">
                    <svg class="w-8 h-8 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">{{ $page->title }}</h1>
                <p class="text-gray-500 max-w-2xl mx-auto">
                    Trouvez rapidement les réponses à vos questions les plus fréquentes concernant OUAGA CHAP.
                </p>
            </header>

            <!-- FAQ Items -->
            @if(!empty($faqItems) && !empty($faqItems[0]['question']))
            <div class="space-y-4">
                @foreach($faqItems as $index => $item)
                <div class="faq-item">
                    <details class="bg-white rounded-xl shadow-sm overflow-hidden group" {{ $index === 0 ? 'open' : '' }}>
                        <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 pr-4">{{ $item['question'] }}</span>
                            <span class="flex-shrink-0 ml-2">
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </span>
                        </summary>
                        <div class="faq-content px-6 pb-6 prose">
                            {!! $item['answer'] !!}
                        </div>
                    </details>
                </div>
                @endforeach
            </div>
            @else
            <!-- Fallback: afficher le contenu brut -->
            <article class="bg-white rounded-2xl shadow-sm p-8 md:p-12 prose max-w-none">
                {!! $page->content !!}
            </article>
            @endif

            <!-- Contact CTA -->
            <div class="mt-12 bg-gradient-to-r from-primary-500 to-primary-600 rounded-2xl p-8 text-center text-white">
                <h2 class="text-2xl font-bold mb-4">Vous n'avez pas trouvé votre réponse ?</h2>
                <p class="mb-6 text-primary-100">
                    Notre équipe est disponible pour répondre à toutes vos questions.
                </p>
                <a href="{{ route('home') }}#contact" 
                   class="inline-flex items-center bg-white text-primary-600 px-6 py-3 rounded-full font-semibold hover:bg-gray-100 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Contactez-nous
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-8 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-8 mr-3">
                    <span class="text-gray-400">© {{ date('Y') }} {{ $siteName }}. Tous droits réservés.</span>
                </div>
                
                <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                    @foreach($legalPages as $legalPage)
                    <a href="{{ route('legal.show', $legalPage['slug']) }}" 
                       class="text-gray-400 hover:text-white text-sm transition {{ $legalPage['slug'] === $page->slug ? 'text-white font-medium' : '' }}">
                        {{ $legalPage['title'] }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
