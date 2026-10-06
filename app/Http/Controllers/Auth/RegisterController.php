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

        return redirect()->intended(route('account'));
    }
}
