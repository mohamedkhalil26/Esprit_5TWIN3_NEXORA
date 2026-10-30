<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTwoFactorAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user?->two_factor_confirmed_at && ! hash_equals(hash('sha256', $user->two_factor_secret), (string) $request->session()->get('two_factor_verified', ''))) {
            $request->session()->put('two_factor_login', [
                'id' => $user->id, 'remember' => false, 'expires' => time() + 300,
                'password_hash' => hash('sha256', $user->password),
            ]);
            $request->session()->put('url.intended', $request->isMethod('GET') ? $request->fullUrl() : route('home'));
            Auth::guard()->logoutCurrentDevice();
            $request->session()->regenerate();

            return redirect()->route('two-factor.challenge');
        }

        return $next($request);
    }
}
