@extends('layouts.shop')

@php use App\Support\Shop; @endphp

@php
    use App\Support\Seo;

    // Anything beyond a page number makes this a narrowed listing.
    $narrowed = $chosen !== [] || $onOffer || $q !== '' || request('min') || request('max') || $sort;
@endphp

@section('title', ($q ? '“' . $q . '” — ' : '') . 'Every saree — ' . Seo::titleSuffix())
@section('description', 'Handwoven silk and cotton sarees from ' . Shop::name() . '. '
    . $products->total() . ' designs, each in one or two shades, posted across India.')

{{-- The canonical is the plain listing, and a narrowed one is kept out of the
     index: the same sarees under every combination of filters is thousands of
     pages competing with each other for the same words. Followed, though —
     the sarees behind a filter still want to be found. --}}
@section('canonical', $narrowed ? route('shop') : ($products->currentPage() > 1 ? $products->url($products->currentPage()) : route('shop')))
@section('robots', Seo::robotsFor('shop', $narrowed))

@push('head')
    @php
        $crumbs = Seo::breadcrumbs([
            ['name' => 'Home', 'url' => route('home')],
            ['name' => 'Sarees', 'url' => route('shop')],
        ]);

        $list = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Sarees',
            'numberOfItems' => $products->total(),
            'itemListElement' => $products->getCollection()->values()->map(fn ($p, $i) => [
                '@type' => 'ListItem',
                'position' => $products->firstItem() + $i,
                'url' => route('product', $p->slug),
                'name' => $p->name,
            ])->all(),
        ];
    @endphp
    <script type="application/ld+json">{!! Seo::json($crumbs) !!}</script>
    <script type="application/ld+json">{!! Seo::json($list) !!}</script>
@endpush

@if ($q !== '')
    @push('tracking')
        <script>odTrack('search', {!! \App\Support\Seo::json(['search' => $q]) !!});</script>
    @endpush
@endif

@section('content')
<div class="od-wrap py-10 md:py-14">
    <header class="mb-8">
        <h1 class="font-display text-3xl md:text-5xl">
            @if ($q)
                “{{ $q }}”
            @elseif ($onOffer)
                On offer
            @else
                Every saree
            @endif
        </h1>
        <p class="mt-3 text-ink-muted">
            {{ $products->total() }} {{ \Illuminate\Support\Str::plural('design', $products->total()) }}
            @if ($q) matching what you typed @endif
        </p>
    </header>

    <div x-data="{ panel: false }" class="grid gap-8 lg:grid-cols-[250px_1fr]">
        {{-- On a phone the filters are a sheet; on a desktop they sit beside
             the sarees. Same markup either way, so there is one set of
             controls to keep correct. --}}
        <div class="lg:hidden flex items-center gap-3">
            <button type="button" @click="panel = true" class="od-btn od-btn-ghost flex-1 py-3">
                Narrow it down
                @if ($chosen || $onOffer || request('min') || request('max'))
                    <span class="ml-1 w-1.5 h-1.5 rounded-full bg-gold inline-block"></span>
                @endif
            </button>

            <form method="get" class="flex-1">
                @foreach (request()->except(['sort', 'page']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ is_array($v) ? implode(',', $v) : $v }}">
                @endforeach
                <select name="sort" onchange="this.form.submit()" class="od-input py-3">
                    <option value="">Sort</option>
                    @foreach ($sorts as $key => $label)
                        <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <aside
            class="lg:block"
            :class="panel
                ? 'fixed inset-0 z-50 overflow-y-auto od-scroll bg-[color:var(--color-page)] p-6'
                : 'hidden'"
        >
            <div class="flex items-center justify-between lg:hidden mb-6">
                <h2 class="font-head text-xl">Narrow it down</h2>
                <button type="button" @click="panel = false" class="p-2 text-ink-muted" aria-label="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @include('shop.partials.filters')
        </aside>

        <div>
            <div class="hidden lg:flex items-center justify-between mb-6">
                <p class="text-sm text-ink-muted">
                    Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }}
                </p>

                <form method="get" class="flex items-center gap-3">
                    @foreach (request()->except(['sort', 'page']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ is_array($v) ? implode(',', $v) : $v }}">
                    @endforeach
                    <label for="sort" class="text-sm text-ink-muted">Sort by</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="od-input py-2 w-56">
                        <option value="">What we would show you</option>
                        @foreach ($sorts as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($products->isEmpty())
                <div class="od-card p-10 text-center">
                    <h2 class="font-head text-2xl">Nothing matches that</h2>
                    <p class="mt-3 text-ink-muted">Try fewer filters, or a different word.</p>
                    <a href="{{ route('shop') }}" class="od-btn od-btn-gold mt-6">Show every saree</a>
                </div>
            @else
                <div class="grid grid-cols-2 md:grid-cols-3 gap-x-5 gap-y-10">
                    @foreach ($products as $product)
                        <x-shop.product-card :product="$product" :eager="$loop->index < 4" />
                    @endforeach
                </div>

                <div class="mt-12">{{ $products->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
