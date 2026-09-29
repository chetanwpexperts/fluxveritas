<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Encrypted links for acting from an email without logging in. Each link has a
 * unique id (jti) and can be used once; opening it shows a confirmation page —
 * nothing happens on a plain GET, so link scanners can't trigger actions.
 */
class MagicActionTokenService
{
    public function generateToken(User $user, string $actionType, array $params, int $expiryMinutes = 1440): string
    {
        return Crypt::encrypt(json_encode([
            'jti'         => Str::random(40),
            'user_id'     => $user->id,
            'org_id'      => $user->organization_id,
            'action_type' => $actionType,
            'params'      => $params,
            'expires_at'  => now()->addMinutes($expiryMinutes)->timestamp,
        ]));
    }

    /** The payload if the link is valid, unexpired and unused; otherwise null. */
    public function validateToken(string $encryptedToken): ?array
    {
        try {
            $payload = json_decode(Crypt::decrypt($encryptedToken), true);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($payload) || empty($payload['jti']) || ($payload['expires_at'] ?? 0) < now()->timestamp) {
            return null; // links issued before single-use existed have no jti
        }

        if (DB::table('used_action_tokens')->where('jti', $payload['jti'])->exists()) {
            return null;
        }

        return $payload;
    }

    /** Marks the link used. False if it was already used (e.g. a double click). */
    public function consume(array $payload): bool
    {
        try {
            DB::table('used_action_tokens')->insert([
                'jti' => $payload['jti'], 'user_id' => $payload['user_id'] ?? null,
                'action_type' => $payload['action_type'] ?? '', 'used_at' => now(),
            ]);
            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
