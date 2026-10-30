<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function verify(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            if (! $locked->two_factor_confirmed_at) {
                return false;
            }
            if (preg_match('/^\d{6}$/D', $code)) {
                $step = app(Google2FA::class)->verifyKeyNewer($locked->two_factor_secret, $code, $locked->two_factor_last_step ?? 0);
                if ($step !== false) {
                    $locked->forceFill(['two_factor_last_step' => $step])->save();

                    return true;
                }
            }
            $codes = $locked->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($codes[$index]);
                    $locked->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                    return true;
                }
            }

            return false;
        });
    }
}
