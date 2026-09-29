<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

/**
 * "Set your password" for people added by an employee import. The signed link
 * is valid for VALID_DAYS and contains a fingerprint of the current password
 * hash, so it stops working as soon as a password has been set (single use).
 */
class ImportInviteController extends Controller
{
    public const VALID_DAYS = 7;

    public static function urlFor(User $user): string
    {
        return URL::temporarySignedRoute('import.invite.accept', now()->addDays(self::VALID_DAYS), [
            'user' => $user->id,
            'fp'   => self::fingerprint($user),
        ]);
    }

    public function show(Request $request, User $user)
    {
        $this->check($request, $user);

        return view('import.set-password', ['user' => $user]);
    }

    public function store(Request $request, User $user)
    {
        $this->check($request, $user);

        $request->validate(['password' => ['required', 'confirmed', Password::defaults()]]);

        $user->forceFill([
            'password'          => $request->password,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Welcome! Your password is set.');
    }

    private function check(Request $request, User $user): void
    {
        abort_unless($request->hasValidSignature(), 403, 'This link has expired. Use "Forgot password" on the sign-in page.');
        abort_unless(hash_equals(self::fingerprint($user), (string) $request->query('fp')), 403, 'This link has already been used. Sign in with your password, or use "Forgot password".');
        abort_unless($user->is_active && $user->organization_id && !$user->organization?->isSuspended(), 403, 'This account is not active.');
    }

    private static function fingerprint(User $user): string
    {
        return substr(hash_hmac('sha256', (string) $user->getAuthPassword(), config('app.key')), 0, 20);
    }
}
