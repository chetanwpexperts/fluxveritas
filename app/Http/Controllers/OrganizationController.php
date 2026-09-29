<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->organization_id) {
            return redirect()->route('dashboard')
                ->with('warning', 'You already belong to an organization.');
        }

        return view('organization.create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->organization_id) {
            return redirect()->route('dashboard')
                ->with('warning', 'You already belong to an organization.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'unique:organizations,slug'],
        ]);

        $isSuperAdmin = $request->user()->hasRole('super_admin');
        $orgStatus    = $isSuperAdmin ? 'active' : 'pending';

        DB::transaction(function () use ($request, $validated, $orgStatus, $isSuperAdmin) {
            $organization = Organization::create(array_merge($validated, [
                'status'      => $orgStatus,
                'approved_at' => $isSuperAdmin ? now() : null,
                'approved_by' => $isSuperAdmin ? $request->user()->id : null,
            ]));

            $request->user()->update([
                'organization_id'   => $organization->id,
                'role'              => 'owner',
                'onboarding_status' => $isSuperAdmin ? 'active' : 'pending',
                'approved_at'       => $isSuperAdmin ? now() : null,
            ]);
        });

        if ($isSuperAdmin) {
            return redirect()->route('dashboard')
                ->with('success', 'Organization created and immediately activated.');
        }

        return redirect()->route('pending-approval')
            ->with('info', 'Your organization has been submitted for review. You\'ll be notified once approved.');
    }
}
