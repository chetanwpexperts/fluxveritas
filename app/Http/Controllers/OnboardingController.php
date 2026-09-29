<?php

namespace App\Http\Controllers;

use App\Models\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function pending(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->onboarding_status === 'active') {
            return redirect()->route('dashboard');
        }

        return view('onboarding.pending', compact('user'));
    }

    public function activateWithToken(Request $request): RedirectResponse
    {
        $request->validate(['invite_token' => 'required|string']);

        $user = auth()->user();

        $invitation = TeamInvitation::where('token', $request->invite_token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return back()->withErrors(['invite_token' => 'Invalid or expired invite token.']);
        }

        // Accept invitation
        $invitation->accepted_at = now();
        $invitation->save();

        // Activate user
        $user->update([
            'organization_id'   => $invitation->organization_id,
            'role'              => $invitation->role,
            'onboarding_status' => 'active',
            'approved_at'       => now(),
        ]);

        $user->syncRoles(['employee']);

        return redirect()->route('dashboard')
            ->with('success', 'Welcome to the team! Your account is now active.');
    }
}
