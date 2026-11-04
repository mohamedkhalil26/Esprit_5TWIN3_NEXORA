<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorService;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

class SecurityController extends Controller
{
    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Si ce compte existe, un lien de réinitialisation a été envoyé.');
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email', 'token' => 'required|string',
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->letters()->numbers()],
        ]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Votre mot de passe a été réinitialisé.')
            : back()->withErrors(['email' => 'Ce lien est invalide, expiré ou déjà utilisé.']);
    }

    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return redirect()->route('home')->with('success', 'Votre adresse e-mail est vérifiée.');
    }

    public function resend(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('success', 'Un lien de vérification a été envoyé.');
    }

    public function challenge()
    {
        return view('auth.security', ['mode' => 'challenge']);
    }

    public function authenticate(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate(['code' => 'required|string|max:100']);
        $pending = $request->session()->get('two_factor_login');
        $user = $pending ? User::find($pending['id']) : null;
        if (! $user || $pending['expires'] < time() || ! hash_equals($pending['password_hash'], hash('sha256', $user->password))) {
            $request->session()->forget('two_factor_login');

            return redirect()->route('login')->withErrors(['email' => 'La demande a expiré. Reconnectez-vous.']);
        }
        if (! $twoFactor->verify($user, $request->string('code')->toString())) {
            throw ValidationException::withMessages(['code' => 'Code invalide ou déjà utilisé.']);
        }
        Auth::login($user, $pending['remember']);
        $request->session()->forget('two_factor_login');
        $request->session()->put('two_factor_verified', hash('sha256', $user->two_factor_secret));
        $request->session()->regenerate();

        return redirect()->intended($user->isAdmin() ? route('admin.dashboard') : route('home'));
    }

    public function showSetup(Request $request, Google2FA $totp)
    {
        $pending = $request->session()->get('two_factor_setup');
        if ($request->user()->two_factor_confirmed_at || ! $pending || $pending['expires'] < time()) {
            $request->session()->forget('two_factor_setup');

            return redirect()->route('profile.edit');
        }

        return $this->setupResponse($request, $totp, $pending['secret']);
    }

    public function setup(Request $request, Google2FA $totp)
    {
        $request->validate(['password' => 'required|current_password']);
        abort_if($request->user()->two_factor_confirmed_at, 409);
        $secret = $totp->generateSecretKey(32);
        $request->session()->put('two_factor_setup', ['secret' => $secret, 'expires' => time() + 600]);

        return $this->setupResponse($request, $totp, $secret);
    }

    private function setupResponse(Request $request, Google2FA $totp, string $secret)
    {
        $uri = $totp->getQRCodeUrl('SolarShare', $request->user()->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle(240), new SvgImageBackEnd));

        return response()->view('auth.security', [
            'mode' => 'setup', 'secret' => $secret,
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($uri)),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function confirm(Request $request, Google2FA $totp)
    {
        $request->validate(['code' => 'required|digits:6']);
        $pending = $request->session()->get('two_factor_setup');
        $step = $pending && $pending['expires'] >= time()
            ? $totp->verifyKeyNewer($pending['secret'], $request->input('code'), 0) : false;
        if ($step === false || $request->user()->two_factor_confirmed_at) {
            throw ValidationException::withMessages(['code' => 'Code invalide ou activation expirée.']);
        }
        $codes = collect(range(1, 8))->map(fn () => Str::random(20))->all();
        $request->user()->forceFill([
            'two_factor_secret' => $pending['secret'],
            'two_factor_confirmed_at' => now(), 'two_factor_last_step' => $step,
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $codes),
        ])->save();
        $request->session()->forget('two_factor_setup');
        $request->session()->put('two_factor_verified', hash('sha256', $pending['secret']));

        return response()->view('auth.security', ['mode' => 'recovery', 'codes' => $codes])
            ->header('Cache-Control', 'no-store, private');
    }

    public function disable(Request $request, TwoFactorService $twoFactor)
    {
        $request->validate(['password' => 'required|current_password', 'code' => 'required|string|max:100']);
        if (! $twoFactor->verify($request->user(), $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Code invalide ou déjà utilisé.']);
        }
        $request->user()->forceFill([
            'two_factor_secret' => null, 'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null, 'two_factor_recovery_codes' => null,
        ])->save();

        return back()->with('success', 'La double authentification est désactivée.');
    }
}
