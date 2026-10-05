@extends('layouts.shop')

@section('title', 'Checkout — ' . \App\Support\Shop::name())

@section('content')
<div class="od-wrap py-14 max-w-xl text-center">
    <h1 class="font-display text-4xl">Checkout</h1>
    <p class="mt-4 text-ink-muted">Payment is being wired up next.</p>
    <a href="{{ route('bag') }}" class="od-btn od-btn-ghost mt-8">Back to your bag</a>
</div>
@endsection
