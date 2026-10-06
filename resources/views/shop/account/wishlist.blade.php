@extends('layouts.shop')

@section('title', 'Saved sarees — ' . \App\Support\Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20">
    <h1 class="font-display text-4xl">Saved sarees</h1>

    @if ($items->isEmpty())
        <p class="mt-4 text-ink-muted">
            Nothing saved yet.
            <a href="{{ route('shop') }}" class="text-gold hover:text-gold-light">Go and find one.</a>
        </p>
    @else
        <div class="mt-10 grid grid-cols-2 md:grid-cols-4 gap-x-5 gap-y-10">
            @foreach ($items as $item)
                @if ($item->product)
                    <x-shop.product-card :product="$item->product" />
                @endif
            @endforeach
        </div>
    @endif
</div>
@endsection
