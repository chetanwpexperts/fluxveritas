<?php

namespace App\Http\Controllers;

use App\Models\FairnessFlag;
use App\Services\FairnessEngine;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FairnessController extends Controller
{
    public function __construct(private FairnessEngine $engine) {}

    public function index(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 15);
            $flags   = FairnessFlag::where('organization_id', $orgId)
                ->with(['flaggedUser', 'reviewer'])
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->through(fn($f) => [
                    'id'            => $f->id,
                    'flag_type'     => $f->flag_type,
                    'severity'      => $f->severity,
                    'status'        => $f->status,
                    'description'   => $f->description,
                    'flagged_user'  => $f->flaggedUser?->name,
                    'reviewer'      => $f->reviewer?->name,
                    'created_at'    => $f->created_at?->diffForHumans(),
                ]);
            return response()->json($flags);
        }

        $flags   = FairnessFlag::where('organization_id', $orgId)
            ->with(['flaggedUser', 'reviewer'])
            ->orderByDesc('created_at')
            ->get();
        $context = $this->engine->buildOrgContext($orgId);

        return view('fairness.index', compact('flags', 'context'));
    }

    public function runAnalysis(Request $request): RedirectResponse
    {
        $orgId = auth()->user()->organization_id;

        $flags = $this->engine->analyzeOrganization($orgId);

        foreach ($flags as $flag) {
            $stored = $this->engine->storeFlag($flag, $orgId);

            if ($stored->flagged_user_id) {
                NotificationService::send(
                    $stored->flagged_user_id,
                    $orgId,
                    'fairness_flag',
                    '⚖️ Fairness Review Triggered',
                    'The AI Fairness Engine has detected a potential fairness issue involving you. Your manager and HR have been notified.',
                    '/fairness',
                    'View Details',
                    'normal',
                    ['flag_id' => $stored->id]
                );
            }

            NotificationService::sendToManagers(
                $orgId,
                'fairness_flag',
                '⚖️ Fairness Flag — Action Required',
                'Fairness Engine flagged a potential issue' . ($stored->flaggedUser?->name ? ' for ' . $stored->flaggedUser->name : '') . '. Review required.',
                '/fairness',
                'high',
                ['flag_id' => $stored->id]
            );
        }

        return redirect()->route('fairness.index')
            ->with('success', count($flags) . ' fairness check(s) completed.');
    }

    public function dismissFlag(Request $request, int $flagId): RedirectResponse
    {
        $flag = FairnessFlag::where('organization_id', auth()->user()->organization_id)
            ->findOrFail($flagId);

        $flag->update(['status' => 'dismissed']);

        if ($flag->flagged_user_id) {
            NotificationService::send(
                $flag->flagged_user_id,
                auth()->user()->organization_id,
                'fairness_dismissed',
                '✅ Fairness Review Closed',
                'The fairness review involving you has been reviewed and closed. No action required.',
                '/fairness',
                'View Details',
                'low',
                ['flag_id' => $flag->id],
                auth()->id()
            );
        }

        return redirect()->route('fairness.index')->with('success', 'Flag dismissed.');
    }
}
