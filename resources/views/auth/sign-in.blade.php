@extends('layouts.shop')

@section('title', 'Sign in — ' . \App\Support\Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-md">
    <h1 class="font-display text-4xl">Sign in</h1>
    <p class="mt-3 text-ink-muted">To see your orders and what you have saved.</p>

    <form method="post" action="{{ route('sign-in') }}" class="mt-8 space-y-4">
        @csrf
        @if (request('next'))
            {{-- Where she was when the shop asked her to sign in. --}}
            <input type="hidden" name="next" value="{{ request('next') }}">
        @endif

        <div>
            <label for="email" class="od-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required
                   autocomplete="email" autofocus class="od-input">
            @error('email')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="od-label">Password</label>
            <input id="password" name="password" type="password" required
                   autocomplete="current-password" class="od-input">
            @error('password')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <label class="flex items-center gap-2.5 text-sm text-ink-muted cursor-pointer">
            <input type="checkbox" name="remember" class="w-4 h-4 accent-[color:var(--color-gold)]">
            Keep me signed in
        </label>

        <button type="submit" class="od-btn od-btn-gold w-full">Sign in</button>
    </form>

    {{-- A button, not a word in a sentence.

         Somebody who has never bought here before arrives on this page and has
         to see straight away that the shop is not asking her to remember a
         password she never made. Written as a line of small print under the
         form, that is read by almost nobody. --}}
    <div class="mt-8 flex items-center gap-4">
        <span class="h-px flex-1 bg-[color:var(--color-line-soft)]"></span>
        <span class="text-xs uppercase tracking-[0.18em] text-ink-faint">New here?</span>
        <span class="h-px flex-1 bg-[color:var(--color-line-soft)]"></span>
    </div>

    <a href="{{ route('join', request('next') ? ['next' => request('next')] : []) }}"
       class="od-btn od-btn-ghost w-full mt-5">
        Create an account
    </a>

    <p class="mt-5 text-sm text-ink-muted">
        You do not need one to order — you can
        <a href="{{ route('shop') }}" class="text-gold hover:text-gold-light">carry on shopping</a>
        and check out as a guest. An account keeps your orders and what you have saved in one place.
    </p>
</div>
@endsection
