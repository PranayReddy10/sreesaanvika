<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Signing in and out.
 *
 * An account is optional here — a shopper can buy without one — so this is
 * short on purpose. What it is not short on is rate limiting: an unlimited
 * sign-in form is somebody else's password list being tried against the shop.
 */
class SessionController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return auth()->check() ? redirect()->route('account') : view('auth.sign-in');
    }

    public function store(Request $request, CartService $bag): RedirectResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->stopBruteForce($request);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'email' => 'That email and password do not go together.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));
        $request->session()->regenerate();

        // Whatever they were carrying as a guest comes with them.
        $bag->forget();
        $bag->current(false);

        auth()->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('account'));
    }

    public function destroy(Request $request, CartService $bag): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // The bag belonged to the account, not to the browser.
        $bag->forget();

        return redirect()->route('home');
    }

    private function stopBruteForce(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Too many tries. Wait {$seconds} seconds and try again.",
        ]);
    }

    private function throttleKey(Request $request): string
    {
        // Per email and per address, so one person guessing cannot lock out
        // everybody else trying to sign in from the same office.
        return str($request->string('email')->lower() . '|' . $request->ip())->transliterate()->toString();
    }
}
