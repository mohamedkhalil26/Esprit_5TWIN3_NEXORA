<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mail:test {to : Adresse qui doit recevoir l\'email de test} {--connection-only : Vérifier uniquement la connexion et l\'authentification SMTP}', function (string $to) {
    if (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $this->error('Adresse email invalide.');

        return 1;
    }

    $mailer = config('mail.default');
    $smtp = config('mail.mailers.smtp');
    $this->table(['Paramètre', 'Valeur'], [
        ['Mailer', $mailer],
        ['Hôte', $smtp['host'] ?? ''],
        ['Port', $smtp['port'] ?? ''],
        ['Schéma', $smtp['scheme'] ?: 'auto'],
        ['STARTTLS obligatoire', ($smtp['require_tls'] ?? false) ? 'oui' : 'non'],
        ['Identifiant SMTP', filled($smtp['username'] ?? null) ? 'renseigné' : 'vide'],
        ['Mot de passe SMTP', filled($smtp['password'] ?? null) ? 'renseigné (masqué)' : 'vide'],
        ['Expéditeur', filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL) ? 'renseigné' : 'invalide ou vide'],
    ]);

    if (! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)) {
        $this->error('Adresse d\'expéditeur invalide : renseignez MAIL_FROM_ADDRESS (ou MAIL_USERNAME) dans .env.');

        return 1;
    }

    $transport = Mail::mailer()->getSymfonyTransport();
    if ($transport instanceof SmtpTransport) {
        if (($smtp['host'] ?? null) === 'smtp.gmail.com'
            && (! filled($smtp['username'] ?? null) || ! filled($smtp['password'] ?? null))) {
            $this->error('Identifiants Gmail incomplets : renseignez MAIL_USERNAME et MAIL_PASSWORD dans .env.');

            return 1;
        }

        try {
            $transport->start();
            $transport->stop();
        } catch (Throwable $e) {
            report($e);
            $this->error('Connexion ou authentification SMTP échouée. Consultez les erreurs courantes dans docs/CONFIGURATION_EMAIL_GMAIL.md.');

            return 1;
        }
        $this->info(filled($smtp['username'] ?? null)
            ? 'Connexion, chiffrement et authentification SMTP : OK'
            : 'Connexion et chiffrement SMTP : OK (authentification non testée : identifiants vides)');
    } else {
        $this->warn("Le mailer « {$mailer} » n'utilise pas SMTP : test de connexion ignoré.");
    }

    if ($this->option('connection-only')) {
        return 0;
    }

    try {
        Mail::raw(
            'Cet email confirme que '.config('app.name').' peut envoyer des emails via '.($smtp['host'] ?? $mailer).'. Envoyé le '.now()->format('d/m/Y H:i:s').'.',
            fn ($message) => $message->to($to)->subject('Test SMTP — '.config('app.name'))
        );
    } catch (Throwable $e) {
        report($e);
        $this->error('Envoi refusé par le serveur SMTP. Consultez les erreurs courantes dans docs/CONFIGURATION_EMAIL_GMAIL.md.');

        return 1;
    }

    $this->info("Email de test accepté par le serveur pour {$to}.");
    $this->warn('Accepté ne veut pas dire reçu : confirmez la réception dans la boîte (et le dossier Spam).');

    return 0;
})->purpose('Tester la connexion SMTP et envoyer un email de test');
