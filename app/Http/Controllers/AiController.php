<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\User;
use App\Services\AI\AiEngine;
use App\Services\AiOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AiController extends Controller
{
    private function getOrgId(): int
    {
        return auth()->user()->organization_id;
    }

    private function gatherOrgData(int $orgId): array
    {
        $orchestrator = new AiOrchestrator();
        return $orchestrator->gatherOrgData($orgId);
    }

    private function gatherMemberStats(int $orgId): array
    {
        $members = User::where('organization_id', $orgId)
            ->where('is_active', true)
            ->get();

        return $members->map(function ($user) {
            $commits = Activity::where('user_id', $user->id)
                ->where('event_type', 'commit')
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count();

            $prs = Activity::where('user_id', $user->id)
                ->whereIn('event_type', ['pull_request', 'pr_opened', 'pr_merged'])
                ->where('occurred_at', '>=', now()->subDays(30))
                ->count();

            $avgScore = Activity::where('user_id', $user->id)
                ->where('occurred_at', '>=', now()->subDays(30))
                ->avg('complexity_score') ?? 0;

            return [
                'name'      => $user->name,
                'commits'   => $commits,
                'prs'       => $prs,
                'avg_score' => round($avgScore, 2),
            ];
        })->sortByDesc('commits')->values()->toArray();
    }

    public function dashboard(): View
    {
        $orgId  = $this->getOrgId();
        $engine = new AiEngine($orgId);

        $cacheKey = "ai_summary_{$orgId}_" . now()->format('Y-m-d-H');

        $summaryData = Cache::remember($cacheKey, 3600, function () use ($orgId, $engine) {
            $data    = $this->gatherOrgData($orgId);
            $summary = $engine->generateSummary($data);
            return [
                'summary'      => $summary,
                'data'         => $data,
                'generated_at' => now()->toDateTimeString(),
            ];
        });

        $memberStats   = $this->gatherMemberStats($orgId);
        $providerBadge = $engine->getProviderBadge();
        $providerName  = $engine->getProviderName();

        return view('ai.dashboard', compact(
            'summaryData', 'memberStats', 'providerBadge', 'providerName'
        ));
    }

    public function query(Request $request): JsonResponse
    {
        $request->validate(['question' => 'required|min:5|max:500']);

        $orgId  = $this->getOrgId();
        $engine = new AiEngine($orgId);

        $context = [
            'data'    => $this->gatherOrgData($orgId),
            'members' => $this->gatherMemberStats($orgId),
        ];

        $answer = $engine->answerQuery($request->question, $context);

        return response()->json([
            'question' => $request->question,
            'answer'   => $answer,
            'provider' => $engine->getProviderName(),
        ]);
    }

    public function refresh(Request $request): RedirectResponse
    {
        $orgId    = $this->getOrgId();
        $cacheKey = "ai_summary_{$orgId}_" . now()->format('Y-m-d-H');
        Cache::forget($cacheKey);

        return redirect()->route('ai.dashboard')->with('success', 'AI summary refreshed.');
    }
}
