@extends('layouts.shop')

@section('title', 'Make an account — ' . \App\Support\Shop::name())

@section('content')
<div class="od-wrap py-14 md:py-20 max-w-md">
    <h1 class="font-display text-4xl">Make an account</h1>
    <p class="mt-3 text-ink-muted">So your orders and saved sarees are waiting next time.</p>

    <form method="post" action="{{ route('join') }}" class="mt-8 space-y-4">
        @csrf
        @if (request('next'))
            {{-- Where she was when the shop asked her to make an account. --}}
            <input type="hidden" name="next" value="{{ request('next') }}">
        @endif

        <div>
            <label for="name" class="od-label">Your name</label>
            <input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus class="od-input">
            @error('name')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="od-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="od-input">
            @error('email')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="phone" class="od-label">Mobile <span class="normal-case text-ink-faint">(optional)</span></label>
            <input id="phone" name="phone" value="{{ old('phone') }}" inputmode="numeric" maxlength="10"
                   autocomplete="tel-national" class="od-input">
            @error('phone')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="od-label">Password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" class="od-input">
            <p class="mt-1.5 text-xs text-ink-faint">At least eight characters.</p>
            @error('password')<p class="mt-1.5 text-sm text-[color:var(--color-marigold)]">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="od-label">And again</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                   autocomplete="new-password" class="od-input">
        </div>

        <button type="submit" class="od-btn od-btn-gold w-full">Make my account</button>
    </form>

    <p class="mt-6 text-sm text-ink-muted">
        Already have one? <a href="{{ route('sign-in', request('next') ? ['next' => request('next')] : []) }}" class="text-gold hover:text-gold-light">Sign in</a>.
    </p>
</div>
@endsection
