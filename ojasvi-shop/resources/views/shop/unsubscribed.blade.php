@extends('layouts.shop')

@section('title', 'Unsubscribed — ' . \App\Support\Shop::name())
@section('robots', 'noindex, nofollow')

@section('content')
<div class="od-wrap py-20 max-w-lg text-center">
    @if ($ok)
        <h1 class="font-display text-4xl">Done</h1>
        <p class="mt-4 text-ink-muted">
            {{ $email }} is off the list. We will not write again unless you ask us to.
        </p>
        <p class="mt-2 text-sm text-ink-faint">
            You will still get emails about an order you place — those are not the list.
        </p>
    @else
        <h1 class="font-display text-4xl">That link did not work</h1>
        <p class="mt-4 text-ink-muted">
            Use the link at the bottom of the email itself, or write to us and we will take you off by hand.
        </p>
        <a href="{{ route('page', 'contact') }}" class="od-btn od-btn-ghost mt-7">Get in touch</a>
    @endif

    <a href="{{ route('home') }}" class="block mt-6 text-sm text-gold hover:text-gold-light">Back to the shop</a>
</div>
@endsection
