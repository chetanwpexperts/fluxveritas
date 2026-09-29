<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password'         => ['required', 'confirmed', Rules\Password::defaults()],
            'onboarding_type'  => ['required', 'in:org_creator,team_member'],
            'invite_token'     => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'onboarding_type'   => $request->onboarding_type,
            'onboarding_status' => 'pending',
            'email_verified_at' => now(),
        ]);

        event(new Registered($user));
        Auth::login($user);

        // ── Org creator ──────────────────────────────────────────────────────
        if ($request->onboarding_type === 'org_creator') {
            $user->update([
                'onboarding_status' => 'active',
                'approved_at'       => now(),
            ]);
            $user->assignRole('owner');

            return redirect()->route('organization.create')
                ->with('success', 'Welcome! First, set up your organization.');
        }

        // ── Team member with valid invite token ──────────────────────────────
        if ($request->filled('invite_token')) {
            $invitation = TeamInvitation::where('token', $request->invite_token)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->first();

            if ($invitation) {
                $invitation->accepted_at = now();
                $invitation->save();

                $user->update([
                    'organization_id'   => $invitation->organization_id,
                    'role'              => $invitation->role,
                    'onboarding_status' => 'active',
                    'approved_at'       => now(),
                ]);
                $user->assignRole('employee');

                $org = Organization::find($invitation->organization_id);
                if ($org) {
                    (new EmailService())->sendWelcome($user, $org);
                }

                return redirect()->route('dashboard')
                    ->with('success', 'Welcome to the team!');
            }
        }

        // ── Team member with no valid token — pending approval ───────────────
        $user->assignRole('viewer');

        return redirect()->route('pending-approval')
            ->with('success', 'Your account is pending approval from an administrator.');
    }
}
