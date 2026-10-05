<!DOCTYPE html>
<html lang="en" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', \App\Support\Seo::homeTitle())</title>
    <meta name="description" content="@yield('description', \App\Support\Seo::homeDescription())">

    {{-- A page says whether it wants to be found. A bag, a checkout, somebody's
         account and a narrowed listing are all real pages and none of them is
         one the shop wants anybody arriving on from Google. --}}
    <meta name="robots" content="@yield('robots', \App\Support\Seo::robotsFor('page'))">

    {{-- The page's own canonical, so a filtered listing does not compete with
         the listing itself for the same words. --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">

    @if (\App\Support\Seo::searchConsoleTag())
        <meta name="google-site-verification" content="{{ \App\Support\Seo::searchConsoleTag() }}">
    @endif
    @if (\App\Support\Seo::bingVerification())
        <meta name="msvalidate.01" content="{{ \App\Support\Seo::bingVerification() }}">
    @endif

    <meta property="og:site_name" content="{{ \App\Support\Shop::name() }}">
    <meta property="og:title" content="@yield('title', \App\Support\Seo::homeTitle())">
    <meta property="og:description" content="@yield('description', \App\Support\Seo::homeDescription())">
    <meta property="og:image" content="@yield('image', asset('brand/icon-512.png'))">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="en_IN">
    <meta name="twitter:card" content="summary_large_image">

    {{-- Who the shop is, said once on every page, so Google can show its
         telephone number and Instagram beside its name rather than guess. --}}
    <script type="application/ld+json">{!! \App\Support\Seo::json(\App\Support\Seo::organisation()) !!}</script>
    <script type="application/ld+json">{!! \App\Support\Seo::json(\App\Support\Seo::website()) !!}</script>

    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">

    <link rel="icon" href="{{ asset('brand/favicon.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#140a12">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')

    @include('partials.analytics')
</head>
<body class="min-h-dvh flex flex-col">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:od-btn focus:od-btn-gold">
        Skip to the sarees
    </a>

    @include('partials.header')

    @if (session('bag') || session('bag_error'))
        <div class="od-wrap pt-4">
            <p class="rounded-[var(--radius-card)] border px-4 py-3 text-sm
                      {{ session('bag_error')
                          ? 'border-[color:var(--color-maroon)] text-ink'
                          : 'border-[color:var(--color-line)] text-gold-light' }}">
                {{ session('bag_error') ?: session('bag') }}
            </p>
        </div>
    @endif

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @stack('scripts')
    @stack('tracking')
</body>
</html>
