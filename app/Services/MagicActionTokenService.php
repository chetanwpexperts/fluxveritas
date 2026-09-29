<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Crypt;

class MagicActionTokenService
{
    /**
     * Generate a signed, encrypted token for 1-click email actions
     */
    public function generateToken(User $user, string $actionType, array $params, int $expiryMinutes = 1440): string
    {
        $payload = [
            'user_id'     => $user->id,
            'org_id'      => $user->organization_id,
            'action_type' => $actionType,
            'params'      => $params,
            'expires_at'  => now()->addMinutes($expiryMinutes)->timestamp,
        ];

        return Crypt::encrypt(json_encode($payload));
    }

    /**
     * Decrypt and validate a magic action token
     */
    public function validateToken(string $encryptedToken): ?array
    {
        try {
            $payload = json_decode(Crypt::decrypt($encryptedToken), true);

            if (!isset($payload['expires_at']) || $payload['expires_at'] < now()->timestamp) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }
}
