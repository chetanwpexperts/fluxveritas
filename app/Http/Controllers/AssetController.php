<?php

namespace App\Http\Controllers;

use App\Models\CompanyAsset;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(): View
    {
        $orgId = auth()->user()->organization_id;
        $assets = CompanyAsset::where('organization_id', $orgId)->with('assignedUser')->latest()->get();
        $members = User::where('organization_id', $orgId)->where('is_active', true)->get();

        return view('assets.index', compact('assets', 'members'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_name'    => 'required|string|max:255',
            'category'      => 'required|string',
            'serial_number' => 'nullable|string',
            'assigned_to'   => 'nullable|exists:users,id',
        ]);

        $orgId = auth()->user()->organization_id;

        CompanyAsset::create([
            'organization_id' => $orgId,
            'asset_name'      => $validated['asset_name'],
            'category'        => $validated['category'],
            'serial_number'   => $validated['serial_number'],
            'assigned_to'     => $validated['assigned_to'],
            'status'          => $validated['assigned_to'] ? 'assigned' : 'available',
            'assigned_at'     => $validated['assigned_to'] ? now() : null,
        ]);

        return back()->with('success', 'Company asset added to vault successfully!');
    }
}
