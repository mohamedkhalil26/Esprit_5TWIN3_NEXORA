<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class MailConfigurationTest extends TestCase
{
    public function test_gmail_settings_build_a_starttls_transport_with_tls_required(): void
    {
        config(['mail.mailers.smtp' => array_merge(config('mail.mailers.smtp'), [
            'scheme' => 'smtp', 'host' => 'smtp.gmail.com', 'port' => 587, 'require_tls' => true,
            'username' => 'demo@gmail.com', 'password' => 'app-password',
        ])]);
        Mail::purge('smtp');

        $transport = Mail::mailer('smtp')->getSymfonyTransport();

        $this->assertInstanceOf(EsmtpTransport::class, $transport);
        $this->assertFalse($transport->getStream()->isTLS(), 'Le port 587 doit démarrer en clair puis passer en STARTTLS.');
        $this->assertTrue((new \ReflectionProperty($transport, 'requireTls'))->getValue($transport));
        $this->assertTrue((new \ReflectionProperty($transport, 'autoTls'))->getValue($transport));
    }

    public function test_mail_test_command_sends_a_message(): void
    {
        config([
            'mail.default' => 'array',
            'mail.from.address' => 'solarshare@example.com',
        ]);
        Mail::purge('array');

        $this->artisan('mail:test', ['to' => 'destinataire@example.com'])->assertSuccessful();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertSame('destinataire@example.com', $messages[0]->getEnvelope()->getRecipients()[0]->getAddress());
    }

    public function test_mail_test_command_rejects_an_invalid_address(): void
    {
        $this->artisan('mail:test', ['to' => 'pas-une-adresse'])->assertFailed();
    }

    public function test_mail_test_command_rejects_incomplete_gmail_credentials_before_connecting(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.from.address' => 'solarshare@gmail.com',
            'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => 'solarshare@gmail.com',
            'mail.mailers.smtp.password' => null,
        ]);

        $this->artisan('mail:test', ['to' => 'destinataire@example.com', '--connection-only' => true])
            ->expectsOutputToContain('Identifiants Gmail incomplets')
            ->assertFailed();
    }
}
