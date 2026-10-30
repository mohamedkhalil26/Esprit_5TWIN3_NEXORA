<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::validate($request->only('email', 'password'))) {
            return back()
                ->withErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.'])
                ->onlyInput('email');
        }

        $user = User::where('email', $request->input('email'))->firstOrFail();
        if ($user->two_factor_confirmed_at) {
            $request->session()->regenerate();
            $request->session()->put('two_factor_login', [
                'id' => $user->id, 'remember' => $request->boolean('remember'), 'expires' => time() + 300,
                'password_hash' => hash('sha256', $user->password),
            ]);

            return redirect()->route('two-factor.challenge');
        }
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        return $this->redirectAfterLogin();
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = User::create([
            ...$request->safe()->only(['name', 'email', 'telephone', 'ville', 'password']),
            'role' => User::ROLE_USER,
        ]);

        Auth::login($user);
        event(new Registered($user));
        $request->session()->regenerate();

        return redirect()->route('home')->with('success', 'Bienvenue sur SolarShare, '.$user->name.' !');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Vous êtes déconnecté.');
    }

    private function redirectAfterLogin(): RedirectResponse
    {
        if (Auth::user()->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'))->with('success', 'Connexion réussie.');
        }

        return redirect()->intended(route('home'))->with('success', 'Connexion réussie.');
    }
}
