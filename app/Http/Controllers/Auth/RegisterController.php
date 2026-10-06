<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View|RedirectResponse
    {
        return auth()->check() ? redirect()->route('account') : view('auth.join');
    }

    public function store(Request $request, CartService $bag): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:140'],
            'email'    => ['required', 'email', 'max:190', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'regex:/^[6-9]\d{9}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'phone.regex' => 'A ten-digit Indian mobile number, please.',
        ]);

        $user = User::create($data + ['is_admin' => false]);

        Auth::login($user, true);
        $request->session()->regenerate();

        // The bag they filled before signing up comes with them.
        $bag->forget();
        $bag->current(false);

        return redirect()->intended($this->next($request) ?? route('account'));
    }

    /**
     * Where she was before she was asked to make an account.
     *
     * Only ever back into this shop; an address somewhere else is how a form
     * like this becomes a way of sending people to a copy of it.
     */
    private function next(Request $request): ?string
    {
        $next = trim((string) $request->input('next'));

        if ($next === '' || str_starts_with($next, '//')) {
            return null;
        }

        $host = parse_url($next, PHP_URL_HOST);

        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        return str_starts_with($next, '/') || $host !== null ? $next : null;
    }
}
