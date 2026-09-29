<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\IncrementAppeal;
use App\Models\IncrementCriteria;
use App\Models\IncrementPolicy;
use App\Models\IncrementReview;
use App\Models\IncrementScore;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\Activity;
use App\Services\DesignationService;
use App\Services\IncrementCalculator;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IncrementController extends Controller
{
    public function settings()
    {
        $user = auth()->user();
        abort_if(!$user->hasRole(['owner', 'super_admin']), 403);
        $org    = $user->organization;
        $policy = IncrementPolicy::where('organization_id', $org->id)->where('is_active', true)->first();

        if (!$policy) {
            $policy = IncrementPolicy::create([
                'organization_id'             => $org->id,
                'name'                        => date('Y') . ' Increment Policy',
                'max_increment_percent'       => 30,
                'review_period'               => 'annual',
                'review_month'                => 12,
                'minimum_months_required'     => 6,
                'minimum_score_for_increment' => 40,
                'anti_gaming_enabled'         => true,
                'is_active'                   => true,
                'created_by'                  => auth()->id(),
            ]);
            foreach ([
                ['criteria_name' => 'work_log_consistency', 'criteria_label' => 'Work Log Consistency', 'criteria_type' => 'automatic', 'data_source' => 'work_logs',        'weight_percent' => 10],
                ['criteria_name' => 'task_completion',      'criteria_label' => 'Task Completion',      'criteria_type' => 'automatic', 'data_source' => 'tasks_completed', 'weight_percent' => 10],
                ['criteria_name' => 'output_quality',       'criteria_label' => 'Output Quality',       'criteria_type' => 'automatic', 'data_source' => 'work_logs',        'weight_percent' => 10],
            ] as $c) {
                IncrementCriteria::create(array_merge($c, ['policy_id' => $policy->id, 'organization_id' => $org->id, 'is_active' => true]));
            }
        }

        $policy->load('criteria');
        $departments = Department::where('organization_id', $org->id)->get();
        return view('increment.settings', compact('policy', 'org', 'departments'));
    }

    public function savePolicy(Request $request)
    {
        abort_if(!auth()->user()->hasRole(['owner', 'super_admin']), 403);

        $request->validate([
            'max_increment_percent'         => 'required|numeric|min:1|max:100',
            'review_period'                 => 'required|in:annual,semi_annual,quarterly',
            'minimum_months_required'       => 'required|integer|min:3|max:12',
            'minimum_score_for_increment'   => 'required|numeric|min:0|max:100',
            'criteria'                      => 'required|array|min:1',
            'criteria.*.name'               => 'required|string',
            'criteria.*.label'              => 'required|string',
            'criteria.*.type'               => 'required|string',
            'criteria.*.source'             => 'nullable|string',
            'criteria.*.weight'             => 'required|numeric|min:0',
        ]);

        $totalWeight = collect($request->criteria)->sum('weight');
        if (abs($totalWeight - $request->max_increment_percent) > 0.01) {
            return back()->withErrors(['criteria' => "Total weight ({$totalWeight}%) must equal maximum increment ({$request->max_increment_percent}%)"]);
        }

        $org = auth()->user()->organization;
        IncrementPolicy::where('organization_id', $org->id)->update(['is_active' => false]);

        $policy = IncrementPolicy::create([
            'organization_id'               => $org->id,
            'name'                          => $request->name ?? date('Y') . ' Increment Policy',
            'max_increment_percent'         => $request->max_increment_percent,
            'review_period'                 => $request->review_period,
            'review_month'                  => $request->review_month ?? 12,
            'minimum_months_required'       => $request->minimum_months_required,
            'minimum_score_for_increment'   => $request->minimum_score_for_increment,
            'anti_gaming_enabled'           => $request->boolean('anti_gaming_enabled', true),
            'is_active'                     => true,
            'created_by'                    => auth()->id(),
        ]);

        foreach ($request->criteria as $c) {
            IncrementCriteria::create([
                'policy_id'      => $policy->id,
                'organization_id'=> $org->id,
                'department_id'  => $c['department_id'] ?? null,
                'criteria_name'  => $c['name'],
                'criteria_label' => $c['label'],
                'criteria_type'  => $c['type'],
                'data_source'    => $c['source'] ?? null,
                'weight_percent' => $c['weight'],
                'is_active'      => true,
            ]);
        }

        return redirect()->route('increment.settings')->with('success', 'Increment policy saved!');
    }

    public function reviewDashboard(Request $request)
    {
        $user = auth()->user();
        abort_if(!$user->hasRole(['owner', 'admin', 'super_admin']), 403);
        $org    = $user->organization;
        $policy = IncrementPolicy::where('organization_id', $org->id)->where('is_active', true)->first();

        if (!$policy) {
            $policy = IncrementPolicy::create([
                'organization_id'             => $org->id,
                'name'                        => date('Y') . ' Increment Policy',
                'max_increment_percent'       => 30,
                'review_period'               => 'annual',
                'review_month'                => 12,
                'minimum_months_required'     => 6,
                'minimum_score_for_increment' => 40,
                'anti_gaming_enabled'         => true,
                'is_active'                   => true,
                'created_by'                  => auth()->id(),
            ]);
            foreach ([
                ['criteria_name' => 'work_log_consistency', 'criteria_label' => 'Work Log Consistency', 'criteria_type' => 'automatic', 'data_source' => 'work_logs',        'weight_percent' => 10],
                ['criteria_name' => 'task_completion',      'criteria_label' => 'Task Completion',      'criteria_type' => 'automatic', 'data_source' => 'tasks_completed', 'weight_percent' => 10],
                ['criteria_name' => 'output_quality',       'criteria_label' => 'Output Quality',       'criteria_type' => 'automatic', 'data_source' => 'work_logs',        'weight_percent' => 10],
            ] as $c) {
                IncrementCriteria::create(array_merge($c, ['policy_id' => $policy->id, 'organization_id' => $org->id, 'is_active' => true]));
            }
        }

        $year = $request->get('year', date('Y'));

        $mapReview = fn($r) => [
            'id'                     => $r->id,
            'user_id'                => $r->user_id,
            'name'                   => $r->user->name,
            'email'                  => $r->user->email,
            'avatar'                 => strtoupper(substr($r->user->name, 0, 2)),
            'avg_score'              => round($r->avg_score, 1),
            'star_rating'            => $r->getStarRating(),
            'score_label'            => $r->getScoreLabel(),
            'recommended_increment'  => $r->recommended_increment,
            'manager_recommendation' => $r->manager_recommendation,
            'final_increment'        => $r->final_increment,
            'status'                 => $r->status,
            'is_overridden'          => $r->isOverridden(),
            'override_reason'        => $r->override_reason,
            'has_appeal'             => !is_null($r->appeal),
            'months_included'        => $r->months_included ?? [],
            'months_excluded'        => $r->months_excluded ?? [],
            'review_year'            => $r->review_year,
        ];

        if ($request->ajax()) {
            $perPage = (int) $request->get('per_page', 15);
            $reviews = IncrementReview::where('organization_id', $org->id)
                ->where('review_year', $year)
                ->with(['user', 'appeal'])
                ->paginate($perPage)
                ->through($mapReview);
            return response()->json($reviews);
        }

        $reviews = IncrementReview::where('organization_id', $org->id)
            ->where('review_year', $year)
            ->with(['user', 'appeal'])
            ->get()
            ->map($mapReview);

        $suspiciousOverrides = $reviews->filter(
            fn($r) => $r['is_overridden'] && $r['final_increment'] !== null &&
            abs($r['final_increment'] - $r['recommended_increment']) > 10
        );

        return view('increment.review-dashboard', compact('reviews', 'policy', 'year', 'suspiciousOverrides'));
    }

    public function calculate(Request $request)
    {
        abort_if(!auth()->user()->hasRole(['owner', 'super_admin']), 403);
        $org    = auth()->user()->organization;
        $policy = IncrementPolicy::where('organization_id', $org->id)->where('is_active', true)->firstOrFail();
        $calc   = new IncrementCalculator($policy);
        $year   = $request->year ?? date('Y');
        $users  = User::where('organization_id', $org->id)->where('is_active', true)->get();
        $results= [];

        foreach ($users as $user) {
            try {
                $review    = $calc->generateAnnualReview($user, $year);
                $results[] = ['user' => $user->name, 'score' => round($review->avg_score, 1), 'increment' => $review->recommended_increment, 'status' => 'calculated'];

                NotificationService::send(
                    $user->id,
                    $user->organization_id,
                    'increment_calculated',
                    '💰 Your Increment Review is Ready',
                    'Your ' . $year . ' increment review has been calculated. Your manager will review it shortly.',
                    '/increment/my',
                    'View My Score',
                    'normal',
                    ['year' => $year],
                    auth()->id()
                );
            } catch (\Exception $e) {
                $results[] = ['user' => $user->name, 'status' => 'error', 'error' => $e->getMessage()];
            }
        }

        return back()->with('success', 'Increment calculations complete!')->with('results', $results);
    }

    public function managerReview(Request $request, int $reviewId)
    {
        $request->validate([
            'manager_recommendation' => 'required|numeric|min:0|max:100',
            'manager_notes'          => 'nullable|string',
        ]);

        $review         = IncrementReview::findOrFail($reviewId);
        $recommendation = min($request->manager_recommendation, $review->policy->max_increment_percent);

        $review->update([
            'manager_recommendation' => $recommendation,
            'manager_notes'          => $request->manager_notes,
            'status'                 => 'manager_reviewed',
            'manager_reviewed_at'    => now(),
        ]);

        NotificationService::send(
            $review->user_id,
            auth()->user()->organization_id,
            'increment_manager_reviewed',
            '📋 Manager Reviewed Your Increment',
            'Your manager has submitted their review for your ' . $review->review_year . ' increment. Awaiting CEO approval.',
            '/increment/my',
            'View Status',
            'normal',
            ['review_id' => $review->id],
            auth()->id()
        );

        NotificationService::sendToManagers(
            auth()->user()->organization_id,
            'increment_needs_approval',
            '💰 Increment Review Needs CEO Approval',
            'Manager has reviewed increment for ' . ($review->user?->name ?? 'a team member') . '. Ready for CEO approval.',
            '/increment/reviews',
            'normal',
            ['review_id' => $review->id],
            auth()->id()
        );

        return back()->with('success', 'Recommendation submitted to CEO.');
    }

    public function ceoApprove(Request $request, int $reviewId)
    {
        abort_if(!auth()->user()->hasRole(['owner', 'super_admin']), 403);
        $request->validate(['final_increment' => 'required|numeric|min:0|max:100', 'ceo_notes' => 'nullable|string']);

        $review        = IncrementReview::with('policy')->findOrFail($reviewId);
        $final         = min($request->final_increment, $review->policy->max_increment_percent);
        $overrideReason= null;

        if (abs($final - $review->recommended_increment) > 5) {
            $request->validate(['override_reason' => 'required|min:20']);
            $overrideReason = $request->override_reason;
        }

        $review->update([
            'final_increment' => $final,
            'ceo_notes'       => $request->ceo_notes,
            'override_reason' => $overrideReason,
            'status'          => 'ceo_approved',
            'ceo_approved_at' => now(),
        ]);

        NotificationService::send(
            $review->user_id,
            auth()->user()->organization_id,
            'increment_approved',
            '🎉 Your Increment Has Been Approved!',
            'Your ' . $review->review_year . ' increment of ' . $final . '% has been approved. Congratulations!',
            '/increment/my',
            'View Details',
            'high',
            ['review_id' => $review->id, 'final_increment' => $final],
            auth()->id()
        );

        return back()->with('success', "Increment approved for {$review->user->name}: {$final}%");
    }

    public function myIncrement()
    {
        $user = auth()->user();

        if ($user->hasRole(['super_admin', 'owner'])) {
            return redirect()->route('increment.reviews')
                ->with('info', 'Manage increment reviews here.');
        }

        $today        = now()->toDateString();
        $startOfWeek  = now()->startOfWeek()->toDateString();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth   = now()->endOfMonth();

        // TODAY'S STATS
        $todayLogs       = WorkLog::where('user_id', $user->id)->where('log_date', $today)->get();
        $todayHours      = round($todayLogs->sum('duration_minutes') / 60, 1);
        $todayOutputAvg  = round($todayLogs->avg('output_value') ?? 0, 1);
        $todayTasksDone  = Task::where('assigned_to', $user->id)->where('status', 'done')
            ->whereDate('completed_at', $today)->count();
        $totalActiveTasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'cancelled'])->count();

        // STREAK
        $streak    = 0;
        $checkDate = now();
        while (true) {
            $hasLog = WorkLog::where('user_id', $user->id)->where('log_date', $checkDate->toDateString())->exists();
            if (!$hasLog) break;
            $streak++;
            $checkDate->subDay();
            if ($streak > 365) break;
        }

        $motivationData = $this->getMotivation($todayHours, $todayOutputAvg, $streak, $todayTasksDone);

        // THIS WEEK STATS
        $weekLogs      = WorkLog::where('user_id', $user->id)->where('log_date', '>=', $startOfWeek)->get();
        $weekHours     = round($weekLogs->sum('duration_minutes') / 60, 1);
        $weekTasksDone = Task::where('assigned_to', $user->id)->where('status', 'done')
            ->where('completed_at', '>=', $startOfWeek)->count();

        $weekDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date    = now()->subDays($i)->toDateString();
            $dayLogs = $weekLogs->where('log_date', $date);
            $weekDays[] = [
                'date'    => $date,
                'label'   => now()->subDays($i)->format('D'),
                'hours'   => round($dayLogs->sum('duration_minutes') / 60, 1),
                'output'  => round($dayLogs->avg('output_value') ?? 0, 1),
                'isToday' => $date === $today,
            ];
        }

        $loggedDaysCount = collect($weekDays)->filter(fn($d) => $d['hours'] > 0)->count();
        $weekConsistency = round(($loggedDaysCount / 5) * 100);

        $bestDayData = collect($weekDays)->sortByDesc('output')->first();
        $bestDay     = ($bestDayData && $bestDayData['hours'] > 0)
            ? Carbon::parse($bestDayData['date'])->format('D')
            : '—';

        // LIVE MONTHLY SCORE
        $orgId           = $user->organization_id;
        $monthLogs       = WorkLog::where('user_id', $user->id)->whereBetween('log_date', [$startOfMonth, $endOfMonth])->get();

        // Count actual weekdays from month start to today
        $monthWorkingDays = 0;
        $dayCheck         = now()->startOfMonth()->copy();
        while ($dayCheck->lte(now())) {
            if ($dayCheck->isWeekday()) $monthWorkingDays++;
            $dayCheck->addDay();
        }
        $monthWorkingDays = max(1, $monthWorkingDays);

        $loggedDays       = $monthLogs->groupBy('log_date')->count();
        $consistencyScore = round(min(100, ($loggedDays / $monthWorkingDays) * 100));

        // No logs = 0 quality, not 50
        $avgOutput    = $monthLogs->count() > 0 ? $monthLogs->avg('output_value') : 0;
        $qualityScore = round(($avgOutput / 10) * 100);

        $monthTasksDone = Task::where('assigned_to', $user->id)->where('status', 'done')
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth])->count();
        $taskScore = min(100, $monthTasksDone * 10);

        $isTech = DesignationService::requiresGithub($user->designation);

        if ($isTech) {
            $githubCommits = Activity::where('user_id', $user->id)
                ->where('event_type', 'commit')
                ->where('occurred_at', '>=', $startOfMonth)
                ->count();

            $orgAvgCommits = \DB::table('activities')
                ->where('organization_id', $orgId)
                ->where('event_type', 'commit')
                ->where('occurred_at', '>=', $startOfMonth)
                ->selectRaw('user_id, COUNT(*) as cnt')
                ->groupBy('user_id')
                ->get()
                ->avg('cnt') ?? 1;

            $orgAvgCommits = max(1, $orgAvgCommits);

            $githubScore = $orgAvgCommits > 0
                ? min(100, ($githubCommits / $orgAvgCommits) * 100)
                : 0;

            // Tech weights: consistency 30, quality 30, tasks 20, github 20
            $monthScore = round(
                ($consistencyScore * 0.30) +
                ($qualityScore     * 0.30) +
                ($taskScore        * 0.20) +
                ($githubScore      * 0.20)
            );
        } else {
            $githubScore = null;
            // Non-tech weights: consistency 40, quality 40, tasks 20
            $monthScore = round(
                ($consistencyScore * 0.40) +
                ($qualityScore     * 0.40) +
                ($taskScore        * 0.20)
            );
        }

        $monthScore = max(0, min(100, $monthScore));

        $criteriaBreakdown = [
            ['label' => 'Work Log Consistency', 'score' => $consistencyScore, 'icon' => '📝'],
            ['label' => 'Output Quality',        'score' => $qualityScore,     'icon' => '⭐'],
            ['label' => 'Task Completion',       'score' => $taskScore,        'icon' => '✅'],
        ];

        if ($isTech && $githubScore !== null) {
            $criteriaBreakdown[] = ['label' => 'GitHub Activity', 'score' => $githubScore, 'icon' => '⚡'];
        }

        $designationLabel   = DesignationService::getDesignationLabel($user->designation, $user->seniority_level);
        $seniorityBenchmark = match($user->seniority_level ?? 'mid') {
            'intern'    => 55,
            'junior'    => 65,
            'mid'       => 72,
            'senior'    => 78,
            'lead'      => 82,
            'principal' => 85,
            'manager'   => 78,
            'director'  => 82,
            'c_level'   => 85,
            default     => 70,
        };

        // MY TASKS WITH LOGS
        $myTasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['cancelled', 'archived'])
            ->with(['workLogs' => function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orderByDesc('log_date')
                  ->select(['id', 'task_id', 'user_id', 'log_date', 'title', 'duration_minutes', 'output_value']);
            }])
            ->orderByRaw("FIELD(status, 'in_progress', 'pending', 'todo', 'backlog', 'done')")
            ->get();

        // SCORE HISTORY (2-month delay)
        $twoMonthsAgo = now()->subMonths(2)->startOfMonth();
        $scoreHistory = IncrementScore::where('user_id', $user->id)
            ->where('score_month', '<=', $twoMonthsAgo)
            ->orderByDesc('score_month')
            ->take(6)
            ->get()
            ->map(function ($score) {
                $s     = $score->final_score;
                $stars = $s >= 90 ? 5 : ($s >= 75 ? 4 : ($s >= 60 ? 3 : ($s >= 40 ? 2 : 1)));
                $label = $s >= 90 ? 'Exceptional' : ($s >= 75 ? 'Strong' : ($s >= 60 ? 'Good' : ($s >= 40 ? 'Needs Work' : 'Low')));
                return ['month' => Carbon::parse($score->score_month)->format('M Y'), 'stars' => $stars, 'label' => $label, 'score' => round($s, 1)];
            });

        return view('increment.my-increment', compact(
            'todayHours', 'todayLogs', 'todayOutputAvg',
            'todayTasksDone', 'totalActiveTasks',
            'streak', 'motivationData',
            'weekHours', 'weekTasksDone', 'weekDays',
            'weekConsistency', 'bestDay',
            'monthScore', 'criteriaBreakdown',
            'designationLabel', 'seniorityBenchmark', 'isTech',
            'myTasks', 'scoreHistory'
        ));
    }

    private function getMotivation(float $hours, float $output, int $streak, int $tasks): array
    {
        if ($hours == 0) {
            return ['message' => 'No work logged yet today. Start logging to build your streak! 💪', 'emoji' => '😴', 'class' => 'motivation-neutral'];
        }
        if ($output >= 8 && $hours >= 4) {
            return ['message' => 'Outstanding day! High output and great hours. You\'re in the top performers today! 🔥', 'emoji' => '🔥', 'class' => 'motivation-great'];
        }
        if ($output >= 7) {
            return ['message' => 'Great work today! Your output quality is excellent. Keep this up! ⭐', 'emoji' => '⭐', 'class' => 'motivation-good'];
        }
        if ($streak >= 7) {
            return ['message' => "{$streak} day streak! Consistency is your superpower. Keep it going! 🔥", 'emoji' => '🏆', 'class' => 'motivation-great'];
        }
        if ($tasks >= 2) {
            return ['message' => "{$tasks} tasks completed today! Productive day. Log more details to boost your score! 💼", 'emoji' => '💼', 'class' => 'motivation-good'];
        }
        return ['message' => "You've logged {$hours}h today. Try to maintain 7+ output quality to strengthen your score! 📈", 'emoji' => '📈', 'class' => 'motivation-neutral'];
    }

    public function submitAppeal(Request $request, int $reviewId)
    {
        $request->validate(['appeal_reason' => 'required|min:50|max:1000', 'evidence' => 'nullable|string|max:500']);

        $review = IncrementReview::where('user_id', auth()->id())->findOrFail($reviewId);

        if (IncrementAppeal::where('review_id', $reviewId)->exists()) {
            return back()->with('error', 'You have already submitted an appeal for this review.');
        }

        IncrementAppeal::create([
            'review_id'          => $reviewId,
            'user_id'            => auth()->id(),
            'organization_id'    => auth()->user()->organization_id,
            'appeal_reason'      => $request->appeal_reason,
            'evidence'           => $request->evidence,
            'original_increment' => $review->final_increment,
            'status'             => 'pending',
        ]);

        $review->update(['status' => 'appealed']);

        NotificationService::sendToManagers(
            auth()->user()->organization_id,
            'increment_appeal',
            '📩 Increment Appeal Submitted',
            auth()->user()->name . ' has submitted an appeal for their increment review.',
            '/increment/reviews',
            'normal',
            ['review_id' => $reviewId],
            auth()->id()
        );

        return back()->with('success', 'Appeal submitted. Leadership will review your case.');
    }

    public function calculateMonth(Request $request)
    {
        abort_if(!auth()->user()->hasRole(['owner', 'super_admin']), 403);
        $org    = auth()->user()->organization;
        $policy = IncrementPolicy::where('organization_id', $org->id)->where('is_active', true)->firstOrFail();
        $calc   = new IncrementCalculator($policy);
        $month  = Carbon::parse($request->month ?? now());
        $calc->calculateOrgMonthlyScores($org->id, $month);
        return back()->with('success', 'Monthly scores calculated for ' . $month->format('M Y'));
    }
}
