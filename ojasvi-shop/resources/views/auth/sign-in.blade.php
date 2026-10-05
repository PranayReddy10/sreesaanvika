@extends('layouts.shop')

@section('title', 'Sign in — ' . \App\Support\Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-md">
    <h1 class="font-display text-4xl">Sign in</h1>
    <p class="mt-3 text-ink-muted">To see your orders and what you have saved.</p>

    <form method="post" action="{{ route('sign-in') }}" class="mt-8 space-y-4">
        @csrf

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

    <p class="mt-6 text-sm text-ink-muted">
        No account? <a href="{{ route('join') }}" class="text-gold hover:text-gold-light">Make one</a>,
        or just <a href="{{ route('shop') }}" class="text-gold hover:text-gold-light">carry on shopping</a> —
        you do not need one to order.
    </p>
</div>
@endsection
