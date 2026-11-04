<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        VerifyEmail::toMailUsing(function ($user, $url) {
            return (new MailMessage)
                ->subject('Vérifiez votre adresse e-mail — SolarShare')
                ->greeting('Bonjour '.$user->name.',')
                ->line('Confirmez votre adresse e-mail pour utiliser votre compte SolarShare.')
                ->action('Vérifier mon adresse e-mail', $url)
                ->line('Si vous n’avez pas créé ce compte, ignorez cet e-mail.')
                ->salutation('L’équipe SolarShare');
        });
        ResetPassword::toMailUsing(function ($user, $token) {
            return (new MailMessage)
                ->subject('Réinitialisez votre mot de passe — SolarShare')
                ->greeting('Bonjour '.$user->name.',')
                ->line('Une réinitialisation du mot de passe a été demandée pour votre compte.')
                ->action('Réinitialiser mon mot de passe', route('password.reset', ['token' => $token, 'email' => $user->email]))
                ->line('Ce lien expire après '.config('auth.passwords.users.expire').' minutes. Si vous n’êtes pas à l’origine de cette demande, ignorez cet e-mail.')
                ->salutation('L’équipe SolarShare');
        });
        RateLimiter::for('ai', fn ($request) => [
            Limit::perMinute(5)->by('ai-user:'.$request->user()->id),
            Limit::perDay(100)->by('ai-day:'.$request->user()->id),
        ]);
        // Les deux templates sont basés sur Bootstrap 5
        Paginator::useBootstrapFive();

        // Dates en français (ex. "4 octobre 2026", "il y a 2 jours")
        Carbon::setLocale(config('app.locale'));
    }
}
