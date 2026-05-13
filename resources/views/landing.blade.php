@php
  $defaults = [
    'site_name' => 'OUAGA CHAP',
    'site_tagline' => 'Livraison express',
    'seo_title' => 'OUAGA CHAP - Livraison Express à Ouagadougou en 30 minutes',
    'seo_description' => 'Vos colis livrés en moins de 30 minutes à Ouagadougou. Téléchargez l\'application OUAGA CHAP pour profiter du meilleur service de livraison express.',
    'hero_badge' => '🚀 #1 à Ouagadougou',
    'hero_title' => 'Livraison express à Ouagadougou',
    'hero_highlight' => 'express',
    'hero_description' => "Vos colis livrés en moins de 30 minutes par nos coursiers professionnels. Rapide, fiable, abordable.",
    'stat_deliveries' => '10000',
    'stat_deliveries_label' => 'Livraisons',
    'stat_couriers' => '500',
    'stat_couriers_label' => 'Coursiers actifs',
    'stat_rating' => '4.8',
    'stat_rating_label' => 'Note moyenne',
    'phone_greeting' => 'Bonjour! 👋',
    'phone_subtitle' => "Où livrons-nous aujourd'hui?",
    'phone_input_placeholder' => "Entrez l'adresse de livraison...",
    'features_title' => 'Pourquoi choisir OUAGA CHAP ?',
    'features_description' => 'Une expérience de livraison pensée pour Ouagadougou.',
    'how_it_works_title' => 'Comment ça marche',
    'how_it_works_description' => 'Trois étapes simples pour faire livrer vos colis.',
    'pricing_title' => 'Tarifs transparents',
    'pricing_description' => 'Des prix justes, calculés selon la distance.',
    'courier_title' => 'Devenez coursier OUAGA CHAP',
    'courier_description' => 'Roulez à votre rythme, gagnez en toute liberté.',
    'courier_commission' => '90',
    'testimonials_title' => 'Ils nous font confiance',
    'download_title' => "Téléchargez l'app maintenant",
    'download_description' => 'Disponible sur iOS et Android. Gratuit.',
    'contact_title' => 'Contactez-nous',
    'contact_description' => "Une question ? Notre équipe est à votre écoute.",
    'contact_phone' => '+226 72621765',
    'contact_whatsapp' => '+226 74240393',
    'contact_email' => 'contact@ouagachap.com',
    'contact_address' => 'Ouagadougou, Burkina Faso',
    'footer_description' => 'Le service de livraison #1 à Ouagadougou. Rapide, fiable, abordable.',
  ];
  $settingsNonVides = array_filter($settings ?? [], function($v) {
    if (is_array($v)) return !empty($v);
    if ($v === null) return false;
    return trim((string)$v) !== '';
  });
  $settings = array_merge($defaults, $settingsNonVides);
  $siteName = $settings['site_name'];
  $siteLogo = $siteLogo ?? asset('images/logo.png');
  $sameAs = array_filter([
    $settings['social_facebook'] ?? null,
    $settings['social_twitter'] ?? null,
    $settings['social_instagram'] ?? null,
  ]);
@endphp
<!-- OUAGA_FIX_V3 seo_title={{ $settings['seo_title'] }} -->
<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5">
  <meta name="theme-color" content="#0C0C14">
  <title>{{ $settings['seo_title'] }}</title>
  <meta name="description" content="{{ $settings['seo_description'] }}">
  <meta name="keywords" content="livraison Ouagadougou, coursier Burkina Faso, livraison express, OUAGA CHAP, mobile money, orange money, moov money">
  <meta name="author" content="{{ $siteName }}">
  <meta name="robots" content="index,follow">
  <meta name="facebook-domain-verification" content="x4v355djp8zz07w0g6bo7cl303fjxv" />

  {{-- Open Graph --}}
  <meta property="og:title" content="{{ $settings['seo_title'] }}">
  <meta property="og:description" content="{{ $settings['seo_description'] }}">
  <meta property="og:image" content="{{ $siteLogo }}">
  <meta property="og:url" content="{{ url('/') }}">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="fr_BF">
  <meta property="og:site_name" content="{{ $siteName }}">

  {{-- Twitter --}}
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="{{ $settings['seo_title'] }}">
  <meta name="twitter:description" content="{{ $settings['seo_description'] }}">
  <meta name="twitter:image" content="{{ $siteLogo }}">

  <link rel="icon" href="{{ $siteLogo }}">
  <link rel="canonical" href="{{ url('/') }}">

  {{-- JSON-LD --}}
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "LocalBusiness",
    "name": "{{ addslashes($siteName) }}",
    "description": "{{ addslashes($settings['seo_description']) }}",
    "url": "{{ url('/') }}",
    "logo": "{{ $siteLogo }}",
    "image": "{{ $siteLogo }}",
    "telephone": "{{ $settings['contact_phone'] }}",
    "email": "{{ $settings['contact_email'] }}",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Ouagadougou",
      "addressCountry": "BF"
    },
    "priceRange": "$$",
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": "{{ $settings['stat_rating'] }}",
      "reviewCount": "{{ preg_replace('/\D/', '', $settings['stat_deliveries']) ?: 1000 }}"
    }@if(count($sameAs)),
    "sameAs": [@foreach($sameAs as $i => $s)"{{ $s }}"@if(!$loop->last),@endif @endforeach]
    @endif
  }
  </script>

  {{-- Fonts --}}
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  {{-- Tailwind --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            base: '#0C0C14',
            surface: '#161622',
            elevated: '#1E1E2E',
            brand: { red: '#E31E24', gold: '#F5A623' },
          },
          fontFamily: {
            display: ['Syne', 'system-ui', 'sans-serif'],
            sans: ['Inter', 'system-ui', 'sans-serif'],
          },
        }
      }
    };
  </script>

  <style>
    :root {
      --bg-base: #0C0C14;
      --bg-surface: #161622;
      --bg-elevated: #1E1E2E;
      --brand-red: #E31E24;
      --brand-red-glow: rgba(227, 30, 36, 0.4);
      --brand-gold: #F5A623;
      --text-primary: #FFFFFF;
      --text-secondary: rgba(255, 255, 255, 0.6);
      --text-muted: rgba(255, 255, 255, 0.4);
      --border: rgba(255, 255, 255, 0.08);
      --border-strong: rgba(255, 255, 255, 0.16);
    }

    * { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    html, body { background: var(--bg-base); color: var(--text-primary); font-family: 'Inter', sans-serif; }
    body { overflow-x: hidden; }

    .font-display { font-family: 'Syne', sans-serif; letter-spacing: -0.02em; }
    .text-balance { text-wrap: balance; }

    /* Glassmorphism nav */
    .nav-glass {
      background: rgba(12, 12, 20, 0.6);
      backdrop-filter: saturate(180%) blur(20px);
      -webkit-backdrop-filter: saturate(180%) blur(20px);
      border-bottom: 1px solid var(--border);
    }

    /* Hero grid overlay */
    .grid-overlay {
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 56px 56px;
      mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 40%, transparent 100%);
      -webkit-mask-image: radial-gradient(ellipse 80% 60% at 50% 40%, black 40%, transparent 100%);
    }

    .red-glow {
      background: radial-gradient(ellipse 60% 50% at 50% 50%, var(--brand-red-glow) 0%, transparent 70%);
      filter: blur(40px);
    }
    .gold-glow {
      background: radial-gradient(ellipse 50% 40% at 50% 50%, rgba(245, 166, 35, 0.25) 0%, transparent 70%);
      filter: blur(60px);
    }

    /* Buttons */
    .btn-primary {
      background: var(--brand-red);
      color: white;
      box-shadow: 0 10px 30px -10px var(--brand-red-glow), inset 0 1px 0 rgba(255,255,255,0.15);
      transition: transform .25s cubic-bezier(.2,.8,.2,1), box-shadow .25s, background .25s;
    }
    .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 18px 40px -10px var(--brand-red-glow); background: #f02830; }
    .btn-ghost {
      background: rgba(255,255,255,0.06);
      border: 1px solid var(--border);
      color: white;
      transition: background .25s, border-color .25s, transform .25s;
    }
    .btn-ghost:hover { background: rgba(255,255,255,0.12); border-color: var(--border-strong); transform: translateY(-2px); }

    /* Cards */
    .card {
      background: linear-gradient(180deg, var(--bg-surface) 0%, var(--bg-elevated) 100%);
      border: 1px solid var(--border);
      transition: border-color .3s, transform .3s, background .3s;
    }
    .card:hover { border-color: rgba(227,30,36,0.4); transform: translateY(-4px); }
    .card-popular {
      background: linear-gradient(180deg, rgba(227,30,36,0.12) 0%, var(--bg-elevated) 100%);
      border: 1px solid rgba(227,30,36,0.4);
    }

    /* Phone mockup */
    .phone-frame {
      background: linear-gradient(180deg, #1c1c2a 0%, #0e0e16 100%);
      border: 1px solid var(--border-strong);
      box-shadow: 0 60px 120px -30px rgba(0,0,0,0.8), 0 0 0 1px rgba(255,255,255,0.04);
      border-radius: 44px;
    }

    .float-card {
      background: rgba(22, 22, 34, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--border-strong);
      box-shadow: 0 20px 40px -10px rgba(0,0,0,0.5);
    }

    /* Marquee */
    .marquee { overflow: hidden; mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, black 10%, black 90%, transparent); }
    .marquee-track { display: flex; gap: 4rem; animation: marquee 35s linear infinite; width: max-content; }
    @keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }

    /* Pulses */
    @keyframes pulse-dot {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.6; transform: scale(1.3); }
    }
    .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

    @keyframes float-up {
      0% { opacity: 0; transform: translateY(20px); }
      100% { opacity: 1; transform: translateY(0); }
    }
    .float-1 { animation: float-up 0.8s 0.4s ease-out both; }
    .float-2 { animation: float-up 0.8s 0.8s ease-out both; }
    .float-3 { animation: float-up 0.8s 1.2s ease-out both; }

    @keyframes float-y {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }
    .float-y { animation: float-y 4s ease-in-out infinite; }

    /* Reveal on scroll */
    .reveal { opacity: 0; transform: translateY(30px); transition: opacity .9s cubic-bezier(.2,.8,.2,1), transform .9s cubic-bezier(.2,.8,.2,1); }
    .reveal.in { opacity: 1; transform: translateY(0); }

    /* Step number */
    .step-num {
      background: linear-gradient(135deg, var(--brand-red) 0%, #ff5560 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      font-family: 'Syne', sans-serif;
      font-weight: 800;
    }

    /* Form */
    .input-dark {
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      color: white;
      transition: border-color .25s, background .25s;
    }
    .input-dark::placeholder { color: var(--text-muted); }
    .input-dark:focus { outline: none; border-color: var(--brand-red); background: var(--bg-surface); }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 10px; height: 10px; }
    ::-webkit-scrollbar-track { background: var(--bg-base); }
    ::-webkit-scrollbar-thumb { background: var(--bg-elevated); border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #2a2a3e; }

    /* Reduce motion */
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
      .marquee-track { animation: none; }
    }
  </style>
</head>
<body class="bg-base text-white font-sans antialiased">

@php
  $features = $features ?? null;
  $pricing = $pricing ?? null;
  $testimonials = $testimonials ?? collect();
  $howItWorksSteps = $howItWorksSteps ?? null;
  $courierBenefits = $courierBenefits ?? null;
  if (is_string($settings['courier_benefits'] ?? null)) {
    $courierBenefits = array_filter(array_map('trim', explode("\n", $settings['courier_benefits'])));
  }

  if (empty($features)) {
    $features = [
      ['icon' => 'bolt', 'title' => 'Livraison ultra-rapide', 'description' => "Moins de 30 minutes en moyenne dans tout Ouagadougou."],
      ['icon' => 'shield', 'title' => 'Coursiers vérifiés', 'description' => 'Tous nos coursiers sont identifiés, formés et notés par les clients.'],
      ['icon' => 'pin', 'title' => 'Suivi temps réel', 'description' => 'Suivez votre coursier en direct sur la carte, du départ à la livraison.'],
      ['icon' => 'wallet', 'title' => 'Mobile Money', 'description' => 'Payez avec Orange Money, Moov Money ou en espèces. Au choix.'],
      ['icon' => 'tag', 'title' => 'Prix transparents', 'description' => 'Tarif fixe affiché avant la course. Aucune mauvaise surprise.'],
      ['icon' => 'support', 'title' => 'Support 7j/7', 'description' => 'Une équipe locale disponible pour toute question, à tout moment.'],
    ];
  }

  if (empty($howItWorksSteps)) {
    $howItWorksSteps = [
      ['num' => '01', 'title' => 'Commandez', 'description' => "Indiquez l'adresse de récupération et de livraison dans l'app."],
      ['num' => '02', 'title' => 'Suivez', 'description' => "Un coursier accepte votre course et vous le suivez en temps réel."],
      ['num' => '03', 'title' => 'Recevez', 'description' => "Votre colis est livré en main propre. Notez votre coursier."],
    ];
  }

  if (empty($pricing)) {
    $pricing = [
      ['emoji' => '🛵', 'name' => 'Moto', 'subtitle' => 'Petits colis & documents', 'base_price' => 1000, 'price_per_km' => 150, 'is_popular' => false, 'features' => "Jusqu'à 10 kg\nLivraison en 30 min\nIdéal documents & repas"],
      ['emoji' => '🚗', 'name' => 'Voiture', 'subtitle' => 'Colis moyens', 'base_price' => 2500, 'price_per_km' => 250, 'is_popular' => true, 'features' => "Jusqu'à 50 kg\nLivraison en 45 min\nObjets fragiles acceptés"],
      ['emoji' => '🚐', 'name' => 'Camionnette', 'subtitle' => 'Gros volumes', 'base_price' => 6000, 'price_per_km' => 500, 'is_popular' => false, 'features' => "Jusqu'à 500 kg\nDéménagement possible\nÉquipe sur demande"],
    ];
  }

  if (empty($courierBenefits)) {
    $courierBenefits = [
      'Gardez 90% de chaque course',
      'Choisissez vos horaires',
      'Paiement hebdomadaire garanti',
    ];
  }
@endphp

{{-- ============ NAV ============ --}}
<nav class="fixed top-0 left-0 right-0 z-50 nav-glass">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 h-16 flex items-center justify-between">
    <a href="#" class="flex items-center gap-3">
      <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-9 w-9 rounded-lg object-cover">
      <span class="font-display text-lg font-bold tracking-tight">{{ $siteName }}</span>
    </a>
    <div class="hidden md:flex items-center gap-8 text-sm text-white/70">
      <a href="#features" class="hover:text-white transition">Fonctionnalités</a>
      <a href="#how-it-works" class="hover:text-white transition">Comment ça marche</a>
      <a href="#pricing" class="hover:text-white transition">Tarifs</a>
      <a href="#courier" class="hover:text-white transition">Coursiers</a>
      <a href="#contact" class="hover:text-white transition">Contact</a>
    </div>
    <div class="flex items-center gap-3">
      <a href="#download" class="hidden sm:inline-flex btn-primary px-5 py-2.5 rounded-full text-sm font-semibold">Télécharger</a>
      <button id="mobile-toggle" class="md:hidden p-2 rounded-lg border border-white/10" aria-label="Menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </div>
  <div id="mobile-menu" class="hidden md:hidden border-t border-white/5 px-6 py-4 space-y-3 text-sm">
    <a href="#features" class="block text-white/80 hover:text-white">Fonctionnalités</a>
    <a href="#how-it-works" class="block text-white/80 hover:text-white">Comment ça marche</a>
    <a href="#pricing" class="block text-white/80 hover:text-white">Tarifs</a>
    <a href="#courier" class="block text-white/80 hover:text-white">Coursiers</a>
    <a href="#contact" class="block text-white/80 hover:text-white">Contact</a>
  </div>
</nav>

{{-- ============ HERO ============ --}}
<section class="relative pt-28 lg:pt-36 pb-16 lg:pb-24 overflow-hidden">
  <div class="absolute inset-0 grid-overlay pointer-events-none"></div>
  <div class="absolute -top-40 -left-40 w-[600px] h-[600px] red-glow pointer-events-none"></div>
  <div class="absolute top-1/3 -right-40 w-[500px] h-[500px] gold-glow pointer-events-none"></div>

  <div class="relative max-w-7xl mx-auto px-6 lg:px-10 grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">
    {{-- Left --}}
    <div class="float-1">
      <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-medium text-white/80">
        <span class="w-2 h-2 rounded-full bg-brand-red pulse-dot"></span>
        {{ $settings['hero_badge'] }}
      </div>

      <h1 class="font-display mt-6 text-5xl sm:text-6xl lg:text-7xl font-extrabold leading-[1.05] text-balance">
        @php
          $title = $settings['hero_title'];
          $highlight = $settings['hero_highlight'];
          if ($highlight && stripos($title, $highlight) !== false) {
            $parts = preg_split('/(' . preg_quote($highlight, '/') . ')/i', $title, 2, PREG_SPLIT_DELIM_CAPTURE);
          } else { $parts = [$title]; }
        @endphp
        @foreach($parts as $part)
          @if(strcasecmp($part, $highlight) === 0)
            <span class="relative inline-block">
              <span class="bg-gradient-to-r from-brand-red via-[#ff5560] to-brand-gold bg-clip-text text-transparent">{{ $part }}</span>
            </span>
          @else
            {{ $part }}
          @endif
        @endforeach
      </h1>

      <p class="mt-6 text-lg text-white/60 max-w-xl text-balance">{{ $settings['hero_description'] }}</p>

      <div class="mt-10 flex flex-wrap gap-4">
        <a href="#download" class="btn-primary px-7 py-4 rounded-full font-semibold inline-flex items-center gap-2">
          Télécharger l'app
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
        </a>
        @if($settings['contact_whatsapp'] ?? false)
        <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['contact_whatsapp']) }}" target="_blank" rel="noopener" class="btn-ghost px-7 py-4 rounded-full font-semibold inline-flex items-center gap-2">
          <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
          WhatsApp
        </a>
        @endif
      </div>

      {{-- Stats --}}
      <div class="mt-14 grid grid-cols-3 gap-6 max-w-lg">
        @php
          $stats = [
            ['v' => $settings['stat_deliveries'], 'l' => $settings['stat_deliveries_label']],
            ['v' => $settings['stat_couriers'],   'l' => $settings['stat_couriers_label']],
            ['v' => $settings['stat_rating'],     'l' => $settings['stat_rating_label']],
          ];
        @endphp
        @foreach($stats as $s)
          @php
            $num = preg_replace('/[^\d.]/', '', $s['v']);
            $suffix = trim(str_replace($num, '', $s['v']));
          @endphp
          <div>
            <div class="font-display text-3xl sm:text-4xl font-extrabold tracking-tight">
              <span class="counter" data-target="{{ $num ?: 0 }}" data-suffix="{{ $suffix }}">0{{ $suffix }}</span>
            </div>
            <div class="mt-1 text-xs uppercase tracking-wider text-white/40">{{ $s['l'] }}</div>
          </div>
        @endforeach
      </div>
    </div>

    {{-- Right: Phone --}}
    <div class="relative float-2 lg:justify-self-end">
      <div class="relative mx-auto" style="width: 320px; height: 640px;">
        {{-- Floating notification cards --}}
        <div class="absolute -left-16 top-20 z-20 float-3 hidden sm:block">
          <div class="float-card rounded-2xl px-4 py-3 w-60">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-green-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
              </div>
              <div>
                <div class="text-xs font-semibold">Awa a reçu son colis</div>
                <div class="text-[10px] text-white/50">il y a 2 min · Tampouy</div>
              </div>
            </div>
          </div>
        </div>
        <div class="absolute -right-12 top-1/2 z-20 float-3 hidden sm:block">
          <div class="float-card rounded-2xl px-4 py-3 w-56">
            <div class="text-[10px] uppercase tracking-wider text-brand-gold font-semibold">Coursier en route</div>
            <div class="mt-1 text-sm font-semibold">Ibrahim · Pissy</div>
            <div class="text-[10px] text-white/50">⭐ 4.9 · 312 courses</div>
            <div class="mt-2 flex items-center gap-1.5">
              <span class="w-1.5 h-1.5 rounded-full bg-brand-red pulse-dot"></span>
              <span class="text-[10px] text-white/60">arrive dans 3 min</span>
            </div>
          </div>
        </div>

        {{-- Phone frame --}}
        <div class="relative phone-frame w-full h-full p-3 float-y">
          <div class="absolute top-3 left-1/2 -translate-x-1/2 w-32 h-6 bg-black rounded-b-2xl z-10"></div>
          <div class="w-full h-full rounded-[36px] overflow-hidden bg-gradient-to-br from-[#0f0f1a] via-[#161622] to-[#0a0a12] relative">
            <div class="px-6 pt-12">
              <div class="text-xs text-white/50">{{ $settings['phone_greeting'] }}</div>
              <div class="mt-1 font-display text-xl font-bold">{{ $settings['phone_subtitle'] }}</div>

              <div class="mt-5 bg-white/5 border border-white/10 rounded-2xl p-3 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-red/20 flex items-center justify-center flex-shrink-0">
                  <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="text-xs text-white/40 truncate">{{ $settings['phone_input_placeholder'] }}</div>
              </div>

              {{-- Map mock --}}
              <div class="mt-5 h-44 rounded-2xl bg-gradient-to-br from-[#1a1a28] to-[#10101a] border border-white/5 relative overflow-hidden">
                <svg class="absolute inset-0 w-full h-full opacity-40" viewBox="0 0 200 180" fill="none">
                  <path d="M0 60 L200 90" stroke="rgba(255,255,255,0.15)" stroke-width="1"/>
                  <path d="M0 100 L200 50" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
                  <path d="M50 0 L80 180" stroke="rgba(255,255,255,0.1)" stroke-width="1"/>
                  <path d="M120 0 L150 180" stroke="rgba(255,255,255,0.15)" stroke-width="1"/>
                  <path d="M30 30 Q100 80 180 60" stroke="#E31E24" stroke-width="2" stroke-dasharray="4 3" fill="none"/>
                </svg>
                <div class="absolute top-6 left-6 w-3 h-3 rounded-full bg-brand-gold ring-4 ring-brand-gold/20"></div>
                <div class="absolute bottom-6 right-6 w-3 h-3 rounded-full bg-brand-red ring-4 ring-brand-red/30 pulse-dot"></div>
              </div>

              {{-- Vehicle types --}}
              <div class="mt-4 grid grid-cols-3 gap-2">
                @foreach(['🛵' => 'Moto', '🚗' => 'Voiture', '🚐' => 'Van'] as $em => $lbl)
                  <div class="bg-white/5 border border-white/10 rounded-xl p-3 text-center">
                    <div class="text-2xl">{{ $em }}</div>
                    <div class="text-[10px] mt-1 text-white/60">{{ $lbl }}</div>
                  </div>
                @endforeach
              </div>

              <button class="mt-4 w-full btn-primary py-3 rounded-xl text-sm font-semibold">Commander</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============ MARQUEE ============ --}}
<div class="relative py-6 border-y border-white/5 bg-[#0a0a12]">
  <div class="marquee">
    <div class="marquee-track">
      @php $items = ['🚀 Livraison à Tampouy en 18 min', '🇧🇫 Fait à Ouaga, par des Ouagalais', '💳 Orange Money & Moov Money', '⭐ « Yaa sooma ! » — 4,8/5', '📍 Pissy · Dapoya · Gounghin · Ouaga 2000', '🤝 312 coursiers du quartier', '⚡ Suivi en direct sur WhatsApp', '🔥 « On s\'occupe de tout, faut pas stresser »']; @endphp
      @for($i = 0; $i < 2; $i++)
        @foreach($items as $it)
          <div class="text-sm font-medium text-white/40 whitespace-nowrap flex items-center gap-2">{{ $it }}</div>
          <div class="text-white/10">•</div>
        @endforeach
      @endfor
    </div>
  </div>
</div>

{{-- ============ PARTENAIRES PAIEMENT ============ --}}
<div class="py-10 bg-[#0a0a12] border-b border-white/5">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <p class="text-center text-xs uppercase tracking-[0.3em] text-white/30 font-semibold mb-8">Paiements acceptés</p>
    <div class="flex flex-wrap items-center justify-center gap-6 lg:gap-12">

      {{-- Orange Money --}}
      <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-white/5 border border-white/8 hover:border-orange-500/30 transition">
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#FF6600">
          <span class="font-extrabold text-white text-xs leading-none">OM</span>
        </div>
        <span class="text-sm font-semibold text-white/80">Orange Money</span>
      </div>

      {{-- Moov Money --}}
      <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-white/5 border border-white/8 hover:border-blue-500/30 transition">
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#0066CC">
          <span class="font-extrabold text-white text-xs leading-none">MM</span>
        </div>
        <span class="text-sm font-semibold text-white/80">Moov Money</span>
      </div>

      {{-- Wave --}}
      <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-white/5 border border-white/8 hover:border-cyan-400/30 transition">
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:#1EAAF1">
          <span class="font-extrabold text-white text-xs leading-none">W</span>
        </div>
        <span class="text-sm font-semibold text-white/80">Wave</span>
      </div>

      {{-- Espèces --}}
      <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-white/5 border border-white/8 hover:border-green-500/30 transition">
        <div class="w-9 h-9 rounded-full bg-green-500/20 border border-green-500/30 flex items-center justify-center">
          <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <span class="text-sm font-semibold text-white/80">Espèces à la livraison</span>
      </div>

    </div>
  </div>
</div>

{{-- ============ HOW IT WORKS ============ --}}
<section id="how-it-works" class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="max-w-2xl reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-red font-semibold">Comment ça marche</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">{{ $settings['how_it_works_title'] }}</h2>
      <p class="mt-4 text-white/60">{{ $settings['how_it_works_description'] }}</p>
    </div>

    <div class="mt-16 grid md:grid-cols-3 gap-8 relative">
      <div class="hidden md:block absolute top-12 left-[16.66%] right-[16.66%] h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
      @foreach($howItWorksSteps as $i => $step)
        <div class="reveal" style="transition-delay: {{ $i * 120 }}ms">
          <div class="relative inline-flex items-center justify-center w-24 h-24 rounded-2xl bg-surface border border-white/10">
            <span class="step-num text-5xl">{{ $step['num'] ?? str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
          </div>
          <h3 class="font-display mt-6 text-2xl font-bold">{{ $step['title'] }}</h3>
          <p class="mt-3 text-white/60">{{ $step['description'] }}</p>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ============ FEATURES ============ --}}
<section id="features" class="py-24 lg:py-32 bg-[#0a0a12] relative">
  <div class="absolute inset-0 grid-overlay opacity-50 pointer-events-none"></div>
  <div class="relative max-w-7xl mx-auto px-6 lg:px-10">
    <div class="max-w-2xl reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-gold font-semibold">Fonctionnalités</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">{{ $settings['features_title'] }}</h2>
      <p class="mt-4 text-white/60">{{ $settings['features_description'] }}</p>
    </div>

    <div class="mt-16 grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
      @forelse($features as $i => $feature)
        @php
          $iconMap = [
            'bolt' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
            'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
            'pin' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
            'wallet' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2zm12 8a1 1 0 100-2 1 1 0 000 2z"/>',
            'tag' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
            'support' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>',
          ];
          $iconKey = is_string($feature['icon'] ?? null) && isset($iconMap[$feature['icon']]) ? $feature['icon'] : null;
        @endphp
        <div class="card rounded-2xl p-7 reveal" style="transition-delay: {{ ($i % 3) * 100 }}ms">
          <div class="w-12 h-12 rounded-xl bg-brand-red/15 border border-brand-red/30 flex items-center justify-center text-brand-red">
            @if($iconKey)
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $iconMap[$iconKey] !!}</svg>
            @else
              <span class="text-2xl">{{ $feature['icon'] ?? '✦' }}</span>
            @endif
          </div>
          <h3 class="font-display mt-5 text-xl font-bold">{{ $feature['title'] }}</h3>
          <p class="mt-3 text-sm text-white/60 leading-relaxed">{{ $feature['description'] }}</p>
        </div>
      @empty
        <div class="col-span-full text-center text-white/40 py-12">Aucune fonctionnalité disponible.</div>
      @endforelse
    </div>
  </div>
</section>

{{-- ============ PRICING ============ --}}
<section id="pricing" class="py-24 lg:py-32 relative">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="max-w-2xl reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-red font-semibold">Tarifs</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">{{ $settings['pricing_title'] }}</h2>
      <p class="mt-4 text-white/60">{{ $settings['pricing_description'] }}</p>
    </div>

    <div class="mt-16 grid md:grid-cols-3 gap-6">
      @forelse($pricing as $i => $plan)
        @php $featList = is_string($plan['features'] ?? null) ? array_filter(array_map('trim', explode("\n", $plan['features']))) : ($plan['features'] ?? []); @endphp
        <div class="relative rounded-3xl p-8 reveal {{ ($plan['is_popular'] ?? false) ? 'card-popular' : 'card' }}" style="transition-delay: {{ $i * 100 }}ms">
          @if($plan['is_popular'] ?? false)
            <div class="absolute -top-3 left-8 px-3 py-1 rounded-full bg-brand-red text-white text-[10px] font-bold uppercase tracking-wider">Le plus choisi</div>
          @endif
          <div class="text-4xl">{{ $plan['emoji'] ?? '📦' }}</div>
          <h3 class="font-display mt-4 text-2xl font-bold">{{ $plan['name'] }}</h3>
          <p class="text-sm text-white/50 mt-1">{{ $plan['subtitle'] ?? '' }}</p>
          <div class="mt-6 pb-6 border-b border-white/5">
            <div class="flex items-baseline gap-1">
              <span class="font-display text-4xl font-extrabold">{{ number_format($plan['base_price'] ?? 0, 0, ',', ' ') }}</span>
              <span class="text-white/50 text-sm">FCFA</span>
            </div>
            <div class="text-xs text-white/40 mt-1">+ {{ $plan['price_per_km'] ?? 0 }} FCFA/km · tarif de départ</div>
            <div class="mt-2 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-brand-gold/10 border border-brand-gold/20">
              <svg class="w-3 h-3 text-brand-gold" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v3.586L7.707 9.293a1 1 0 00-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 10.586V7z" clip-rule="evenodd"/></svg>
              <span class="text-[10px] text-brand-gold font-semibold">Prix affiché avant commande</span>
            </div>
          </div>
          <ul class="mt-6 space-y-3">
            @foreach($featList as $f)
              <li class="flex items-start gap-3 text-sm text-white/70">
                <svg class="w-4 h-4 mt-0.5 text-brand-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ $f }}
              </li>
            @endforeach
          </ul>
          <a href="#download" class="mt-8 block text-center py-3 rounded-xl font-semibold {{ ($plan['is_popular'] ?? false) ? 'btn-primary' : 'btn-ghost' }}">
            Je commande maintenant
          </a>
        </div>
      @empty
        <div class="col-span-full text-center text-white/40 py-12">Aucun tarif disponible.</div>
      @endforelse
    </div>
  </div>
</section>

{{-- ============ COURIER CTA ============ --}}
<section id="courier" class="py-24 lg:py-32 relative overflow-hidden">
  <div class="absolute inset-0 bg-gradient-to-br from-brand-red/10 via-transparent to-brand-gold/10 pointer-events-none"></div>
  <div class="absolute -top-40 right-0 w-[500px] h-[500px] red-glow pointer-events-none"></div>

  <div class="relative max-w-7xl mx-auto px-6 lg:px-10 grid lg:grid-cols-2 gap-16 items-center">
    <div class="reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-gold font-semibold">Devenir coursier</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">{{ $settings['courier_title'] }}</h2>
      <p class="mt-4 text-white/60 max-w-xl">{{ $settings['courier_description'] }}</p>

      <ul class="mt-10 space-y-4">
        @forelse($courierBenefits as $b)
          <li class="flex items-start gap-4">
            <div class="w-8 h-8 rounded-full bg-brand-red/20 border border-brand-red/40 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <span class="text-white/80">{{ $b }}</span>
          </li>
        @empty
          <li class="text-white/40">Aucun avantage configuré.</li>
        @endforelse
      </ul>

      <div class="mt-10 flex flex-wrap gap-4">
        @if(config('app.store_links.coursier.google_play'))
          <a href="{{ config('app.store_links.coursier.google_play') }}" target="_blank" rel="noopener"
             class="btn-primary px-6 py-3.5 rounded-full text-sm font-semibold inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M3 20.5V3.5c0-.5.3-.9.7-1.1l10.6 9.6L3.7 21.6c-.4-.2-.7-.6-.7-1.1zm12.8-7.4l2.7 2.7-3.5 2L4.7 22l11.1-8.9zm4.7-2.7c.5.3.5 1 0 1.3l-2.7 1.5-2.9-2.9 2.9-2.9 2.7 1zM4.7 2L15 9.6l-3.5 3.5L4 1.1c.2 0 .5 0 .7.9z"/></svg>
            App Coursier
          </a>
        @elseif($settings['apk_courier_url'] ?? false)
          <a href="{{ $settings['apk_courier_url'] }}" target="_blank" rel="noopener"
             class="btn-primary px-6 py-3.5 rounded-full text-sm font-semibold inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Télécharger l'app
          </a>
        @else
          <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['contact_whatsapp'] ?? '') }}?text={{ urlencode('Bonjour, je veux devenir coursier OUAGA CHAP !') }}"
             target="_blank" rel="noopener"
             class="btn-primary px-6 py-3.5 rounded-full text-sm font-semibold inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
            Postuler via WhatsApp
          </a>
        @endif
        @if(config('app.store_links.coursier.app_store'))
          <a href="{{ config('app.store_links.coursier.app_store') }}" target="_blank" rel="noopener"
             class="btn-ghost px-6 py-3.5 rounded-full text-sm font-semibold inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/></svg>
            App Store
          </a>
        @endif
      </div>
    </div>

    {{-- Earnings card --}}
    <div class="reveal">
      <div class="card rounded-3xl p-8 lg:p-10">
        <div class="flex items-center justify-between">
          <div class="text-xs uppercase tracking-wider text-white/40">Ce que gagne Issa · Pissy</div>
          <div class="px-2 py-1 rounded-full bg-green-500/15 text-green-400 text-[10px] font-bold">+{{ $settings['courier_commission'] }}%</div>
        </div>
        <div class="mt-4 font-display text-5xl lg:text-6xl font-extrabold">
          150 000 <span class="text-2xl text-white/40">FCFA</span>
        </div>
        <div class="text-sm text-white/50">par mois · il roule 6 jours sur 7</div>

        <div class="mt-8 grid grid-cols-2 gap-4">
          <div class="bg-elevated rounded-2xl p-4 border border-white/5">
            <div class="text-xs text-white/40">Coursiers du quartier</div>
            <div class="font-display text-2xl font-bold mt-1">{{ $settings['stat_couriers'] }}+</div>
          </div>
          <div class="bg-elevated rounded-2xl p-4 border border-white/5">
            <div class="text-xs text-white/40">Pour toi</div>
            <div class="font-display text-2xl font-bold mt-1">{{ $settings['courier_commission'] }}%</div>
          </div>
        </div>

        <div class="mt-8 pt-6 border-t border-white/5 flex items-center justify-between">
          <div class="flex -space-x-2">
            @foreach(['#E31E24', '#F5A623', '#10B981', '#3B82F6'] as $c)
              <div class="w-8 h-8 rounded-full border-2 border-base" style="background: {{ $c }}"></div>
            @endforeach
          </div>
          <div class="text-xs text-white/50">Rejoins l'équipe 🇧🇫</div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============ TESTIMONIALS ============ --}}
<section class="py-24 lg:py-32 bg-[#0a0a12]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10">
    <div class="max-w-2xl reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-red font-semibold">Témoignages</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">{{ $settings['testimonials_title'] }}</h2>
    </div>

    <div class="mt-16 grid md:grid-cols-3 gap-6">
      @forelse($testimonials as $i => $t)
        <div class="card rounded-2xl p-7 reveal" style="transition-delay: {{ $i * 100 }}ms">
          <div class="flex gap-0.5 text-brand-gold">
            @for($s = 0; $s < (int)($t['rating'] ?? 5); $s++)
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.539 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            @endfor
          </div>
          <p class="mt-5 text-white/80 leading-relaxed">"{{ $t['content'] }}"</p>
          <div class="mt-6 flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-red to-brand-gold flex items-center justify-center font-bold text-sm">{{ $t['initials'] ?? 'OC' }}</div>
            <div>
              <div class="text-sm font-semibold">{{ $t['author'] }}</div>
              <div class="text-xs text-white/40">{{ $t['role'] ?? '' }}</div>
            </div>
          </div>
        </div>
      @empty
        @php
          $fallbackTestimonials = [
            [
              'rating' => 5,
              'content' => "J'ai un petit resto à Tampouy. Avant, je perdais 2h chaque jour à courir derrière les livraisons. Avec OUAGA CHAP, je clique, le coursier vient, basta. J'ai gagné 45 000 FCFA en plus ce mois-ci juste parce que je peux livrer plus loin.",
              'initials' => 'AO',
              'author' => 'Awa Ouédraogo',
              'role' => 'Restauratrice · Tampouy',
            ],
            [
              'rating' => 5,
              'content' => "On dit quoi ! Moi je roule depuis Pissy. Les courses tombent direct sur l'app, pas besoin d'attendre au carrefour. En bonne semaine je fais 38 000 FCFA, payé en Mobile Money. Faut juste être sérieux.",
              'initials' => 'IS',
              'author' => 'Issa Sawadogo',
              'role' => 'Coursier · Pissy',
            ],
            [
              'rating' => 5,
              'content' => "J'habite Ouaga 2000 mais ma famille est à Cissin. Envoyer des affaires là-bas c'était toujours la galère. Maintenant je commande sur l'app à 7h, à 7h30 c'est déjà arrivé. Yaa sooma vraiment !",
              'initials' => 'MK',
              'author' => 'Mariam Kaboré',
              'role' => 'Cliente · Ouaga 2000',
            ],
          ];
        @endphp
        @foreach($fallbackTestimonials as $i => $t)
          <div class="card rounded-2xl p-7 reveal" style="transition-delay: {{ $i * 100 }}ms">
            <div class="flex gap-0.5 text-brand-gold">
              @for($s = 0; $s < $t['rating']; $s++)
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.539 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.783.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
              @endfor
            </div>
            <p class="mt-5 text-white/80 leading-relaxed">« {{ $t['content'] }} »</p>
            <div class="mt-6 flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brand-red to-brand-gold flex items-center justify-center font-bold text-sm">{{ $t['initials'] }}</div>
              <div>
                <div class="text-sm font-semibold">{{ $t['author'] }}</div>
                <div class="text-xs text-white/40">{{ $t['role'] }}</div>
              </div>
            </div>
          </div>
        @endforeach
      @endforelse
    </div>
  </div>
</section>

{{-- ============ HISTOIRE / FONDATEUR ============ --}}
<section class="py-24 lg:py-32 relative overflow-hidden">
  <div class="absolute inset-0 grid-overlay pointer-events-none opacity-40"></div>
  <div class="max-w-5xl mx-auto px-6 lg:px-10 relative">
    <div class="grid lg:grid-cols-[1fr,2fr] gap-10 lg:gap-16 items-center">
      <div class="reveal">
        <div class="aspect-square rounded-3xl bg-gradient-to-br from-brand-red/30 via-brand-gold/20 to-transparent border border-white/10 flex items-center justify-center relative overflow-hidden">
          <div class="absolute inset-0 grid-overlay opacity-50"></div>
          <div class="font-display text-7xl lg:text-8xl font-extrabold relative z-10">🇧🇫</div>
        </div>
        <div class="mt-4 text-center">
          <div class="text-sm font-semibold">Une équipe d'ici</div>
          <div class="text-xs text-white/40">Made in Ouagadougou</div>
        </div>
      </div>
      <div class="reveal" style="transition-delay: 100ms">
        <div class="text-xs uppercase tracking-[0.2em] text-brand-gold font-semibold">Notre histoire</div>
        <h2 class="font-display mt-4 text-3xl lg:text-5xl font-extrabold text-balance leading-tight">
          On a créé OUAGA CHAP parce qu'on en avait <span class="text-brand-red">marre d'attendre</span>.
        </h2>
        <div class="mt-6 space-y-4 text-white/70 leading-relaxed">
          <p>
            Tu connais le truc : tu commandes un truc à Pissy, le coursier dit « j'arrive », et 2 heures après il est toujours « en route ». Pas de suivi, pas de prix clair, paiement en cash dans une enveloppe.
          </p>
          <p>
            On s'est dit : <strong class="text-white">ça suffit</strong>. Ouaga mérite mieux. On a réuni des développeurs ouagalais, des coursiers vétérans des grands carrefours, et on a construit l'app qu'on aurait voulu utiliser nous-mêmes.
          </p>
          <p>
            Pas une copie d'Uber. Pas un machin pensé à Lagos ou Dakar. Une app <strong class="text-white">faite à Ouaga, pour Ouaga</strong>. Avec Mobile Money, WhatsApp, le mooré dans l'équipe support, et des tarifs que ta tante du marché peut comprendre.
          </p>
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-6 pt-6 border-t border-white/10">
          <div>
            <div class="font-display text-3xl font-extrabold">{{ $settings['stat_couriers'] ?? 312 }}+</div>
            <div class="text-xs text-white/50 uppercase tracking-wider">Coursiers du quartier</div>
          </div>
          <div class="w-px h-10 bg-white/10"></div>
          <div>
            <div class="font-display text-3xl font-extrabold">100%</div>
            <div class="text-xs text-white/50 uppercase tracking-wider">Burkinabè</div>
          </div>
          <div class="w-px h-10 bg-white/10"></div>
          <div>
            <div class="font-display text-3xl font-extrabold">0</div>
            <div class="text-xs text-white/50 uppercase tracking-wider">Frais cachés</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============ DOWNLOAD ============ --}}
<section id="download" class="py-24 lg:py-32 relative overflow-hidden">
  <div class="absolute inset-0 grid-overlay pointer-events-none opacity-60"></div>
  <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] red-glow pointer-events-none"></div>

  <div class="relative max-w-5xl mx-auto px-6 lg:px-10 text-center">
    <div class="reveal">
      <h2 class="font-display text-4xl lg:text-6xl font-extrabold text-balance">{{ $settings['download_title'] }}</h2>
      <p class="mt-5 text-white/60 max-w-2xl mx-auto">{{ $settings['download_description'] }}</p>
    </div>

    <div class="mt-12 grid sm:grid-cols-2 gap-6 max-w-3xl mx-auto reveal">
      {{-- App Client --}}
      <div class="card rounded-2xl p-7 text-left">
        <div class="text-xs uppercase tracking-wider text-brand-red font-semibold">Pour les clients</div>
        <h3 class="font-display mt-2 text-2xl font-bold">App {{ $siteName }}</h3>
        <p class="text-sm text-white/50 mt-2">Tapez l'adresse, choisissez moto ou voiture, payez en Mobile Money. Le coursier sonne à votre porte.</p>
        <div class="mt-6 flex flex-wrap gap-3">
          @if(config('app.store_links.client.google_play'))
            <a href="{{ config('app.store_links.client.google_play') }}" target="_blank" rel="noopener"
               class="btn-primary px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 20.5V3.5c0-.5.3-.9.7-1.1l10.6 9.6L3.7 21.6c-.4-.2-.7-.6-.7-1.1zm12.8-7.4l2.7 2.7-3.5 2L4.7 22l11.1-8.9zm4.7-2.7c.5.3.5 1 0 1.3l-2.7 1.5-2.9-2.9 2.9-2.9 2.7 1zM4.7 2L15 9.6l-3.5 3.5L4 1.1c.2 0 .5 0 .7.9z"/></svg>
              Google Play
            </a>
          @elseif($settings['apk_client_url'] ?? false)
            <a href="{{ $settings['apk_client_url'] }}" target="_blank" rel="noopener"
               class="btn-primary px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Télécharger l'APK
            </a>
          @else
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['contact_whatsapp'] ?? '') }}?text={{ urlencode('Bonjour, je souhaite télécharger l\'app OUAGA CHAP client.') }}"
               target="_blank" rel="noopener"
               class="btn-primary px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
              Recevoir le lien WhatsApp
            </a>
          @endif
          @if(config('app.store_links.client.app_store'))
            <a href="{{ config('app.store_links.client.app_store') }}" target="_blank" rel="noopener"
               class="btn-ghost px-5 py-2.5 rounded-full text-xs font-semibold">App Store</a>
          @endif
        </div>
      </div>

      {{-- App Coursier --}}
      <div class="card rounded-2xl p-7 text-left">
        <div class="text-xs uppercase tracking-wider text-brand-gold font-semibold">Pour les coursiers</div>
        <h3 class="font-display mt-2 text-2xl font-bold">App Coursier</h3>
        <p class="text-sm text-white/50 mt-2">Connectez-vous, prenez les courses qui passent près de vous. Paiement direct en Mobile Money chaque semaine.</p>
        <div class="mt-6 flex flex-wrap gap-3">
          @if(config('app.store_links.coursier.google_play'))
            <a href="{{ config('app.store_links.coursier.google_play') }}" target="_blank" rel="noopener"
               class="btn-primary px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M3 20.5V3.5c0-.5.3-.9.7-1.1l10.6 9.6L3.7 21.6c-.4-.2-.7-.6-.7-1.1zm12.8-7.4l2.7 2.7-3.5 2L4.7 22l11.1-8.9zm4.7-2.7c.5.3.5 1 0 1.3l-2.7 1.5-2.9-2.9 2.9-2.9 2.7 1zM4.7 2L15 9.6l-3.5 3.5L4 1.1c.2 0 .5 0 .7.9z"/></svg>
              Google Play
            </a>
          @elseif($settings['apk_courier_url'] ?? false)
            <a href="{{ $settings['apk_courier_url'] }}" target="_blank" rel="noopener"
               class="btn-primary px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Télécharger l'APK
            </a>
          @else
            <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['contact_whatsapp'] ?? '') }}?text={{ urlencode('Bonjour, je veux devenir coursier OUAGA CHAP. Comment télécharger l\'app ?') }}"
               target="_blank" rel="noopener"
               class="btn-primary px-5 py-2.5 rounded-full text-xs font-semibold inline-flex items-center gap-2">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
              Rejoindre via WhatsApp
            </a>
          @endif
          @if(config('app.store_links.coursier.app_store'))
            <a href="{{ config('app.store_links.coursier.app_store') }}" target="_blank" rel="noopener"
               class="btn-ghost px-5 py-2.5 rounded-full text-xs font-semibold">App Store</a>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>

{{-- ============ CONTACT ============ --}}
<section id="contact" class="py-24 lg:py-32 bg-[#0a0a12]">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 grid lg:grid-cols-2 gap-16">
    <div class="reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-red font-semibold">Contact</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">{{ $settings['contact_title'] }}</h2>
      <p class="mt-4 text-white/60">{{ $settings['contact_description'] }}</p>

      <div class="mt-10 space-y-5">
        @if($settings['contact_phone'] ?? false)
          <a href="tel:{{ $settings['contact_phone'] }}" class="group flex items-center gap-4 p-4 rounded-2xl border border-white/5 hover:border-white/15 hover:bg-white/5 transition">
            <div class="w-11 h-11 rounded-xl bg-brand-red/15 border border-brand-red/30 flex items-center justify-center text-brand-red">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <div>
              <div class="text-xs text-white/40 uppercase tracking-wider">Téléphone</div>
              <div class="font-semibold">{{ $settings['contact_phone'] }}</div>
            </div>
          </a>
        @endif

        @if($settings['contact_whatsapp'] ?? false)
          <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['contact_whatsapp']) }}" target="_blank" rel="noopener" class="group flex items-center gap-4 p-4 rounded-2xl border border-white/5 hover:border-green-500/30 hover:bg-green-500/5 transition">
            <div class="w-11 h-11 rounded-xl bg-green-500/15 border border-green-500/30 flex items-center justify-center text-green-400">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347"/></svg>
            </div>
            <div>
              <div class="text-xs text-white/40 uppercase tracking-wider">WhatsApp</div>
              <div class="font-semibold">{{ $settings['contact_whatsapp'] }}</div>
            </div>
          </a>
        @endif

        @if($settings['contact_email'] ?? false)
          <a href="mailto:{{ $settings['contact_email'] }}" class="group flex items-center gap-4 p-4 rounded-2xl border border-white/5 hover:border-white/15 hover:bg-white/5 transition">
            <div class="w-11 h-11 rounded-xl bg-brand-gold/15 border border-brand-gold/30 flex items-center justify-center text-brand-gold">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <div class="text-xs text-white/40 uppercase tracking-wider">Email</div>
              <div class="font-semibold">{{ $settings['contact_email'] }}</div>
            </div>
          </a>
        @endif

        @if($settings['contact_address'] ?? false)
          <div class="flex items-center gap-4 p-4 rounded-2xl border border-white/5">
            <div class="w-11 h-11 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-white/70">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
              <div class="text-xs text-white/40 uppercase tracking-wider">Adresse</div>
              <div class="font-semibold">{{ $settings['contact_address'] }}</div>
            </div>
          </div>
        @endif

        {{-- Horaires --}}
        <div class="flex items-center gap-4 p-4 rounded-2xl border border-white/5 bg-brand-gold/5">
          <div class="w-11 h-11 rounded-xl bg-brand-gold/15 border border-brand-gold/30 flex items-center justify-center text-brand-gold flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xs text-white/40 uppercase tracking-wider">Disponibilité support</div>
            <div class="font-semibold">7j/7 · 6h00 – 22h00</div>
            <div class="text-xs text-white/40 mt-0.5">Réponse en moins de 2h</div>
          </div>
        </div>
      </div>
    </div>

    {{-- Form --}}
    <div class="reveal">
      <div class="card rounded-3xl p-8 lg:p-10">
        @if (session('success'))
          <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/30 text-green-300 text-sm">
            {{ session('success') }}
          </div>
        @endif
        @if ($errors->any())
          <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30 text-red-300 text-sm">
            <ul class="list-disc list-inside space-y-1">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('contact') }}" method="POST" class="space-y-5">
          @csrf
          <div class="grid sm:grid-cols-2 gap-5">
            <div>
              <label for="contact-name" class="block text-xs font-semibold uppercase tracking-wider text-white/50 mb-2">Nom complet</label>
              <input id="contact-name" type="text" name="name" value="{{ old('name') }}" required class="input-dark w-full px-4 py-3 rounded-xl text-sm" placeholder="Votre nom">
            </div>
            <div>
              <label for="contact-email" class="block text-xs font-semibold uppercase tracking-wider text-white/50 mb-2">Email</label>
              <input id="contact-email" type="email" name="email" value="{{ old('email') }}" required class="input-dark w-full px-4 py-3 rounded-xl text-sm" placeholder="vous@exemple.com">
            </div>
          </div>
          <div>
            <label for="contact-subject" class="block text-xs font-semibold uppercase tracking-wider text-white/50 mb-2">Sujet</label>
            <select id="contact-subject" name="subject" required class="input-dark w-full px-4 py-3 rounded-xl text-sm">
              <option value="support" {{ old('subject') === 'support' ? 'selected' : '' }}>Support</option>
              <option value="partnership" {{ old('subject') === 'partnership' ? 'selected' : '' }}>Partenariat</option>
              <option value="courier" {{ old('subject') === 'courier' ? 'selected' : '' }}>Devenir coursier</option>
              <option value="other" {{ old('subject') === 'other' ? 'selected' : '' }}>Autre</option>
            </select>
          </div>
          <div>
            <label for="contact-message" class="block text-xs font-semibold uppercase tracking-wider text-white/50 mb-2">Message</label>
            <textarea id="contact-message" name="message" rows="5" required class="input-dark w-full px-4 py-3 rounded-xl text-sm resize-none" placeholder="Votre message...">{{ old('message') }}</textarea>
          </div>
          <button type="submit" class="btn-primary w-full py-4 rounded-xl font-semibold inline-flex items-center justify-center gap-2">
            Envoyez, on vous répond aujourd'hui
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

{{-- ============ FAQ ============ --}}
<section class="py-24 lg:py-32 bg-[#0a0a12]">
  <div class="max-w-3xl mx-auto px-6 lg:px-10">
    <div class="text-center reveal">
      <div class="text-xs uppercase tracking-[0.3em] text-brand-gold font-semibold">FAQ</div>
      <h2 class="font-display mt-4 text-4xl lg:text-5xl font-extrabold text-balance">Questions fréquentes</h2>
      <p class="mt-4 text-white/50">Tout ce qu'il faut savoir avant de commander.</p>
    </div>

    @php
      $faqs = [
        [
          'q' => 'Quel est le tarif minimum pour une livraison ?',
          'a' => 'Le tarif de départ pour une livraison à moto est de 1 000 FCFA. Le prix final dépend de la distance. Il est affiché dans l\'app avant que vous confirmiez — aucune surprise.'
        ],
        [
          'q' => 'Comment payer ma livraison ?',
          'a' => 'Vous pouvez payer par Orange Money, Moov Money, Wave, ou en espèces directement au coursier à la livraison. Tous les modes de paiement sont acceptés.'
        ],
        [
          'q' => 'Que faire si le coursier est en retard ?',
          'a' => 'Vous suivez le coursier en temps réel sur la carte. Si vous avez un problème, contactez notre support via WhatsApp au ' . ($settings['contact_whatsapp'] ?? '+226 70 00 00 00') . '. Nous répondons en moins de 2h, 7j/7.'
        ],
        [
          'q' => 'Puis-je annuler une commande après confirmation ?',
          'a' => 'Oui, vous pouvez annuler avant que le coursier ait accepté la course, sans frais. Une fois acceptée, l\'annulation peut entraîner des frais selon l\'état de la livraison.'
        ],
        [
          'q' => 'Comment devenir coursier OUAGA CHAP ?',
          'a' => 'Téléchargez l\'app Coursier, créez votre compte, soumettez vos documents d\'identité et d\'immatriculation du véhicule. Notre équipe valide le dossier sous 24h. Ensuite, vous pouvez commencer à prendre des courses.'
        ],
        [
          'q' => 'Quelles zones sont couvertes à Ouagadougou ?',
          'a' => 'Nous couvrons toutes les grandes zones : Pissy, Tampouy, Dapoya, Gounghin, Ouaga 2000, Cissin, Ouaga Inter, Patte d\'Oie et bien d\'autres. La couverture s\'étend chaque semaine.'
        ],
      ];
    @endphp

    <div class="mt-14 space-y-3">
      @foreach($faqs as $i => $faq)
        <div class="card rounded-2xl overflow-hidden reveal" style="transition-delay: {{ $i * 60 }}ms">
          <button
            onclick="this.parentElement.classList.toggle('faq-open')"
            class="w-full flex items-center justify-between gap-4 p-6 text-left">
            <span class="font-semibold text-white/90">{{ $faq['q'] }}</span>
            <svg class="w-5 h-5 text-white/40 flex-shrink-0 transition-transform duration-300 faq-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
          <div class="faq-body px-6 pb-0 max-h-0 overflow-hidden transition-all duration-300">
            <p class="text-white/60 text-sm leading-relaxed pb-6">{{ $faq['a'] }}</p>
          </div>
        </div>
      @endforeach
    </div>

    <div class="mt-10 text-center reveal">
      <p class="text-white/40 text-sm">Vous avez une autre question ?</p>
      <a href="{{ route('faq') }}" class="mt-3 inline-flex items-center gap-2 text-brand-gold hover:text-brand-gold/80 text-sm font-semibold transition">
        Voir toutes les FAQ
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
      </a>
    </div>
  </div>
</section>

{{-- ============ FOOTER ============ --}}
<footer class="border-t border-white/5 bg-base">
  <div class="max-w-7xl mx-auto px-6 lg:px-10 py-16">
    <div class="grid md:grid-cols-4 gap-12">
      <div class="md:col-span-2">
        <div class="flex items-center gap-3">
          <img src="{{ $siteLogo }}" alt="{{ $siteName }}" class="h-9 w-9 rounded-lg object-cover">
          <span class="font-display text-lg font-bold">{{ $siteName }}</span>
        </div>
        <p class="mt-5 text-sm text-white/50 max-w-md leading-relaxed">{{ $settings['footer_description'] }}</p>
        <div class="mt-6 flex items-center gap-3">
          @if($settings['social_facebook'] ?? false)
            <a href="{{ $settings['social_facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"/></svg>
            </a>
          @endif
          @if($settings['social_twitter'] ?? false)
            <a href="{{ $settings['social_twitter'] }}" target="_blank" rel="noopener" aria-label="Twitter" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
            </a>
          @endif
          @if($settings['social_instagram'] ?? false)
            <a href="{{ $settings['social_instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-white/60 hover:text-white hover:bg-white/10 transition">
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
            </a>
          @endif
        </div>
      </div>

      <div>
        <h4 class="text-xs uppercase tracking-wider text-white/40 font-semibold">Liens utiles</h4>
        <ul class="mt-5 space-y-3 text-sm">
          <li><a href="#features" class="text-white/60 hover:text-white transition">Fonctionnalités</a></li>
          <li><a href="#how-it-works" class="text-white/60 hover:text-white transition">Comment ça marche</a></li>
          <li><a href="#pricing" class="text-white/60 hover:text-white transition">Tarifs</a></li>
          <li><a href="#download" class="text-white/60 hover:text-white transition">Télécharger</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-xs uppercase tracking-wider text-white/40 font-semibold">Légal</h4>
        <ul class="mt-5 space-y-3 text-sm">
          <li><a href="{{ route('legal.show', 'conditions-utilisation') }}" class="text-white/60 hover:text-white transition">Conditions d'utilisation</a></li>
          <li><a href="{{ route('legal.show', 'politique-confidentialite') }}" class="text-white/60 hover:text-white transition">Politique de confidentialité</a></li>
          <li><a href="{{ route('legal.show', 'mentions-legales') }}" class="text-white/60 hover:text-white transition">Mentions légales</a></li>
          <li><a href="{{ route('faq') }}" class="text-white/60 hover:text-white transition">FAQ</a></li>
        </ul>
      </div>
    </div>

    <div class="mt-14 pt-8 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="text-xs text-white/40">© {{ date('Y') }} {{ $siteName }}. Tous droits réservés.</div>
      <div class="text-xs text-white/30">Conçu avec ❤️ à Ouagadougou</div>
    </div>
  </div>
</footer>

{{-- ============ FLOATING WHATSAPP ============ --}}
@if ($settings['contact_whatsapp'] ?? false)
  <a href="https://wa.me/{{ preg_replace('/\D/', '', $settings['contact_whatsapp']) }}?text={{ urlencode('Bonjour ' . $siteName . ', je souhaite avoir des informations.') }}"
     target="_blank" rel="noopener"
     aria-label="WhatsApp"
     class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-green-500 hover:bg-green-600 flex items-center justify-center shadow-2xl shadow-green-500/30 transition transform hover:scale-110">
    <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884"/></svg>
    <span class="absolute top-0 right-0 w-3 h-3 rounded-full bg-green-300 pulse-dot ring-2 ring-base"></span>
  </a>
@endif

{{-- ============ BACK TO TOP ============ --}}
<button id="back-to-top" aria-label="Retour en haut" class="fixed bottom-6 left-6 z-40 w-12 h-12 rounded-full bg-white/10 backdrop-blur border border-white/15 hover:bg-white/20 hidden items-center justify-center transition">
  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
</button>

{{-- ============ SCRIPTS ============ --}}
<script>
  // Mobile nav toggle
  document.getElementById('mobile-toggle')?.addEventListener('click', () => {
    document.getElementById('mobile-menu')?.classList.toggle('hidden');
  });
  document.querySelectorAll('#mobile-menu a').forEach(a => {
    a.addEventListener('click', () => document.getElementById('mobile-menu')?.classList.add('hidden'));
  });

  // Reveal on scroll
  const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('in');
        revealObserver.unobserve(e.target);
      }
    });
  }, { threshold: 0.12, rootMargin: '0px 0px -60px 0px' });
  document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

  // Counters
  const counterObserver = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el = e.target;
      const target = parseFloat(el.dataset.target) || 0;
      const suffix = el.dataset.suffix || '';
      const isFloat = !Number.isInteger(target);
      const duration = 1600;
      const start = performance.now();
      const animate = (t) => {
        const p = Math.min((t - start) / duration, 1);
        const eased = 1 - Math.pow(1 - p, 3);
        const val = target * eased;
        let display;
        if (target >= 1000) {
          display = Math.floor(val).toLocaleString('fr-FR').replace(/,/g, ' ');
          if (target >= 1000) display = Math.floor(val / 1000) + (val >= 1000 ? 'K' : '');
        } else if (isFloat) {
          display = val.toFixed(1);
        } else {
          display = Math.floor(val).toString();
        }
        el.textContent = display + suffix;
        if (p < 1) requestAnimationFrame(animate);
        else el.textContent = (target >= 1000 ? Math.floor(target/1000) + 'K' : (isFloat ? target.toFixed(1) : target)) + suffix;
      };
      requestAnimationFrame(animate);
      counterObserver.unobserve(el);
    });
  }, { threshold: 0.4 });
  document.querySelectorAll('.counter').forEach(el => counterObserver.observe(el));

  // FAQ accordion
  document.querySelectorAll('.faq-open .faq-body').forEach(el => { el.style.maxHeight = el.scrollHeight + 'px'; });
  const faqObserver = new MutationObserver(() => {
    document.querySelectorAll('.card').forEach(card => {
      const body = card.querySelector('.faq-body');
      const icon = card.querySelector('.faq-icon');
      if (!body) return;
      if (card.classList.contains('faq-open')) {
        body.style.maxHeight = body.scrollHeight + 'px';
        body.style.paddingBottom = '';
        if (icon) icon.style.transform = 'rotate(180deg)';
      } else {
        body.style.maxHeight = '0';
        if (icon) icon.style.transform = '';
      }
    });
  });
  document.querySelectorAll('.card').forEach(card => {
    if (card.querySelector('.faq-body')) {
      faqObserver.observe(card, { attributes: true, attributeFilter: ['class'] });
    }
  });

  // Back to top
  const backTop = document.getElementById('back-to-top');
  if (backTop) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 500) { backTop.classList.remove('hidden'); backTop.classList.add('flex'); }
      else { backTop.classList.add('hidden'); backTop.classList.remove('flex'); }
    }, { passive: true });
    backTop.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
  }

  // Magnetic effect on primary CTAs (subtle)
  document.querySelectorAll('.btn-primary').forEach(btn => {
    btn.addEventListener('mousemove', (e) => {
      const r = btn.getBoundingClientRect();
      const x = e.clientX - r.left - r.width / 2;
      const y = e.clientY - r.top - r.height / 2;
      btn.style.transform = `translate(${x * 0.12}px, ${y * 0.18}px)`;
    });
    btn.addEventListener('mouseleave', () => { btn.style.transform = ''; });
  });
</script>

</body>
</html>
