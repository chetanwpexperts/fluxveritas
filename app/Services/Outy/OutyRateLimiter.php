<?php

namespace App\Services\Outy;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Daily question limit per user, by the organization's plan (config/outy.php).
 * The count resets at midnight IST.
 */
class OutyRateLimiter
{
    public function limitFor(User $user): int
    {
        $plan = $user->organization?->effectivePlan() ?? 'platform';

        return (int) (config("outy.daily_limit.{$plan}") ?? config('outy.daily_limit.free', 50));
    }

    /** Counts one question; false when today's limit is already used up. */
    public function attempt(User $user): bool
    {
        $key = $this->key($user);

        if (RateLimiter::tooManyAttempts($key, $this->limitFor($user))) {
            return false;
        }

        RateLimiter::hit($key, $this->secondsUntilMidnight());

        return true;
    }

    public function remaining(User $user): int
    {
        return RateLimiter::remaining($this->key($user), $this->limitFor($user));
    }

    private function key(User $user): string
    {
        return 'outy:' . $user->id . ':' . now()->setTimezone('Asia/Kolkata')->toDateString();
    }

    private function secondsUntilMidnight(): int
    {
        $now = now()->setTimezone('Asia/Kolkata');

        return max(60, (int) $now->diffInSeconds($now->copy()->endOfDay()));
    }
}
