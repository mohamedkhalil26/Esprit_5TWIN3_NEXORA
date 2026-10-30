<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_email_limits_do_not_block_login_and_remain_enforced(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        }
        $this->post(route('password.email'), ['email' => $user->email])->assertStatus(429);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_totp_activation_generates_qr_confirms_and_can_be_disabled(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('two-factor.setup'), ['password' => 'password'])->assertOk()->assertSee('data:image/svg+xml;base64,', false);
        $secret = session('two_factor_setup.secret');
        $code = (new Google2FA)->getCurrentOtp($secret);
        $this->post(route('two-factor.confirm'), ['code' => $code])->assertOk()->assertSee('affichés une seule fois');
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
        $this->assertNotSame($secret, $user->fresh()->getRawOriginal('two_factor_secret'));
        $this->delete(route('two-factor.disable'), ['password' => 'password', 'code' => $code])->assertSessionHasErrors('code');
        $totp = new Google2FA;
        $next = $totp->oathTotp($secret, $totp->getTimestamp() + 1);
        $this->delete(route('two-factor.disable'), ['password' => 'password', 'code' => $next])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_expired_reset_and_challenge_are_rejected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->travel(61)->minutes();
        $this->post(route('password.update'), ['email' => $user->email, 'token' => $token, 'password' => 'Changed123', 'password_confirmation' => 'Changed123'])->assertSessionHasErrors('email');
        $this->withSession(['two_factor_login' => ['id' => $user->id, 'expires' => time() - 1, 'remember' => false, 'password_hash' => hash('sha256', $user->password)]])->post(route('two-factor.authenticate'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_registration_ignores_role_and_sends_verification(): void
    {
        Notification::fake();
        $this->post(route('register.store'), [
            'name' => 'Client Test', 'email' => 'client@example.test', 'role' => 'admin',
            'password' => 'Password123', 'password_confirmation' => 'Password123',
        ])->assertRedirect();
        $user = User::firstOrFail();
        $this->assertSame('user', $user->role);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->get(route('front.reservations.index'))->assertRedirect(route('verification.notice'));
    }

    public function test_signed_email_verification_and_admin_permissions(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(5), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect(route('home'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk();
    }

    public function test_password_reset_uses_one_time_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'Changed1234', 'password_confirmation' => 'Changed1234'];
        $this->post(route('password.update'), $payload)->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('Changed1234', $user->fresh()->password));
        $this->post(route('password.update'), $payload)->assertSessionHasErrors('email');
    }

    public function test_totp_challenge_and_recovery_are_required_and_not_reusable(): void
    {
        $totp = new Google2FA;
        $secret = $totp->generateSecretKey(32);
        $user = User::factory()->create();
        $user->forceFill(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => [Hash::make('recovery-test')]])->save();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $code = $totp->getCurrentOtp($secret);
        $this->post(route('two-factor.authenticate'), ['code' => $code])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertFalse(app(TwoFactorService::class)->verify($user, $code));
        $this->assertTrue(app(TwoFactorService::class)->verify($user, 'recovery-test'));
        $this->assertFalse(app(TwoFactorService::class)->verify($user, 'recovery-test'));
    }

    public function test_profile_cannot_change_role_and_delete_requires_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->patch(route('profile.update'), ['name' => 'Autre Nom', 'email' => $user->email, 'role' => 'admin', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('user', $user->fresh()->role);
        $this->delete(route('profile.destroy'), ['password' => 'wrong', 'confirmation' => 'SUPPRIMER'])->assertSessionHasErrors();
        $this->assertModelExists($user);
        $this->delete(route('profile.destroy'), ['password' => 'password', 'confirmation' => 'SUPPRIMER'])->assertRedirect(route('home'));
        $this->assertModelMissing($user);
    }
}
