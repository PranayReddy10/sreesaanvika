<!DOCTYPE html>
<html lang="en" class="scroll-pt-24">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', \App\Support\Shop::name() . ' — ' . \App\Support\Shop::tagline())</title>
    <meta name="description" content="@yield('description', \App\Support\Shop::tagline())">

    {{-- The page's own canonical, so a filtered shop listing does not compete
         with the plain one in search results. --}}
    <link rel="canonical" href="@yield('canonical', url()->current())">

    <meta property="og:site_name" content="{{ \App\Support\Shop::name() }}">
    <meta property="og:title" content="@yield('title', \App\Support\Shop::name())">
    <meta property="og:description" content="@yield('description', \App\Support\Shop::tagline())">
    <meta property="og:image" content="@yield('image', asset('brand/icon-512.png'))">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('brand/favicon.png') }}" sizes="32x32">
    <link rel="apple-touch-icon" href="{{ asset('brand/apple-touch-icon.png') }}">
    <meta name="theme-color" content="#140a12">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
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
</body>
</html>
