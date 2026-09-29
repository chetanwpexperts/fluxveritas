<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AgentNotification;
use App\Models\Blocker;
use App\Models\FairnessFlag;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkLog;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\AI\AiEngine;
use App\Services\Outy\OutyAgent;
use App\Services\Outy\OutyRateLimiter;
use App\Services\Outy\OutyUnavailableException;
use App\Services\TeamStatusService;
use App\Services\EmailService;
use App\Services\HelpAgentService;
use App\Services\IntentMatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HelpAgentController extends Controller
{
    /** cleanResponse() returns this when the AI text is unusable. */
    private const GENERIC_REPLY = "I'm here to help! Ask me anything about your work, team, or how to use OutraqHQ. 😊";

    private HelpAgentService $helpService;

    public function __construct()
    {
        $this->helpService = new HelpAgentService();
    }

    /* ── Context endpoint (unchanged) ─────────────────────────────────────── */

    public function context(Request $request)
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;
        $page  = $request->input('page', 'dashboard');

        $context = [
            'page'            => $page,
            'role'            => $user->getRoleNames()->first(),
            'github_username' => $user->github_username,
            'team_count'      => $orgId ? User::where('organization_id', $orgId)->count() : 0,
            'project_count'   => $orgId ? Project::where('organization_id', $orgId)->count() : 0,
            'last_synced'     => $orgId
                ? (Activity::where('organization_id', $orgId)->max('occurred_at') ?? 'Never')
                : 'Never',
            'flag_count'      => 0,
        ];

        $proactive = $this->helpService->getProactiveMessage($context);
        $knowledge = $this->helpService->getPageKnowledge($page);
        $welcome   = $this->helpService->getWelcomeMessage($page, $context['role']);

        return response()->json([
            'proactive' => $proactive,
            'knowledge' => $knowledge,
            'welcome'   => $welcome,
            'context'   => $context,
        ]);
    }

    /* ── Ask endpoint ──────────────────────────────────────────────────────── */

    /**
     * Answers a chat question.
     *
     * With an OpenAI key, OutyAgent answers using tools scoped to this user's
     * role and organization. Without a key — or when the API fails — the keyword
     * pipeline answers instead: payroll/expense/asset/timesheet keywords →
     * intent match (live data) → how-to knowledge base → "I'm not sure" + quick actions.
     * Every answer is logged at debug level with its stage.
     */
    public function ask(Request $request, OutyAgent $agent, OutyRateLimiter $limiter): JsonResponse
    {
        $request->validate(['question' => 'required|min:2|max:500', 'page' => 'nullable|string']);

        $user     = auth()->user();
        $question = trim($request->question);
        $lower    = strtolower($question);
        $today    = now()->setTimezone('Asia/Kolkata')->toDateString();

        if (!$limiter->attempt($user)) {
            $this->logAnswer($user, $question, 'rate_limited');
            return response()->json([
                'answer'  => "You've reached today's limit of {$limiter->limitFor($user)} questions. It resets at midnight.",
                'source'  => 'rate_limited',
                'instant' => true,
            ], 429);
        }

        // Email sending keeps its existing flow (actions move to confirm cards in phase 2)
        foreach (['send email', 'email to', 'send to', 'notify by email', 'mail to'] as $keyword) {
            if (str_contains($lower, $keyword)) {
                $this->logAnswer($user, $question, 'email');
                return $this->handleEmailRequest($question, $user);
            }
        }

        if ($agent->enabled()) {
            try {
                $result = $agent->answer($user, $question, $this->history());
                $this->remember($question, $result->answer);
                Log::debug('Outy answered', [
                    'user_id' => $user->id, 'question' => $question, 'stage' => 'agent',
                    'tools' => $result->tools, 'intent' => null, 'score' => null,
                ]);

                return response()->json(['answer' => $result->answer, 'source' => 'outy_ai', 'instant' => false]);
            } catch (OutyUnavailableException $e) {
                Log::warning('Outy agent unavailable, using keyword fallback', ['user_id' => $user->id, 'reason' => $e->getMessage()]);
            }
        }

        return $this->keywordAnswer($user, $question, $lower, $today);
    }

    /** The no-AI pipeline: keyword intents, how-to answers, then quick actions. */
    private function keywordAnswer(User $user, string $question, string $lower, string $today): JsonResponse
    {
        if ($erp = $this->answerMiniErp($lower, $user, $today)) {
            return $this->reply($user, $question, $erp, 'direct', 'mini_erp');
        }

        $match = IntentMatcherService::match($question);
        if ($match['intent'] !== 'unknown' && ($answer = $this->answerIntent($match['intent'], $question, $user, $today))) {
            return $this->reply($user, $question, $answer, 'direct', 'intent', $match);
        }

        if ($kbAnswer = $this->helpService->answerFromKnowledge($question)) {
            return $this->reply($user, $question, $kbAnswer, 'knowledge_base', 'knowledge_base', $match);
        }

        $this->logAnswer($user, $question, 'fallback', $match);

        return response()->json([
            'answer'      => "I'm not sure. Try one of these:",
            'source'      => 'fallback',
            'instant'     => true,
            'suggestions' => $this->helpService->quickActions($user),
        ]);
    }

    /** Last chat turns for the agent, kept in the session. */
    private function history(): array
    {
        return session('outy.history', []);
    }

    private function remember(string $question, string $answer): void
    {
        $keep    = (int) config('outy.history_turns', 10) * 2;
        $history = array_merge($this->history(), [
            ['role' => 'user', 'content' => $question],
            ['role' => 'assistant', 'content' => $answer],
        ]);

        session(['outy.history' => array_slice($history, -$keep)]);
    }

    private function reply(User $user, string $question, string $answer, string $source, string $stage, ?array $match = null, bool $instant = true): JsonResponse
    {
        $this->logAnswer($user, $question, $stage, $match);

        return response()->json(['answer' => $answer, 'source' => $source, 'instant' => $instant]);
    }

    private function logAnswer(User $user, string $question, string $stage, ?array $match = null): void
    {
        Log::debug('Outy answered', [
            'user_id'   => $user->id,
            'question'  => $question,
            'stage'     => $stage,
            'intent'    => $match['intent'] ?? null,
            'score'     => $match['score'] ?? null,
            'candidate' => $match['candidate'] ?? null,
            'matched'   => $match['matched'] ?? [],
        ]);
    }

    /* ── System prompt ─────────────────────────────────────────────────────── */

    private function buildSystemPrompt(User $user): string
    {
        $userRole    = $user->getRoleNames()->first() ?? 'employee';
        $userName    = $user->name;
        $orgId       = $user->organization_id;
        $now         = now()->setTimezone('Asia/Kolkata');
        $currentTime = $now->format('h:i A');
        $currentDate = $now->format('l, F j, Y');
        $today       = $now->toDateString();
        $timezone    = 'India Standard Time (IST, UTC+5:30)';

        $contextData      = $this->buildContext($user, $orgId, $userRole, $today);
        $roleInstructions = $this->getRoleInstructions($userRole);

        return "YOU ARE OUTY — OutraqHQ's work intelligence agent.

SCOPE — you ONLY answer questions about:
- Work performance, scores, increments
- Tasks, sprints, projects, blockers
- Team members, managers, org hierarchy
- Work logs, feedback, peer reviews
- How to use OutraqHQ features

OUT OF SCOPE — redirect politely to work questions:
- Coding / programming help
- General knowledge (geography, science, trivia)
- Entertainment, food, sports, finance
- Personal / medical / legal advice
- Other AI tools (ChatGPT, Claude, etc.)

CURRENT TIME & DATE:
- Time: {$currentTime} {$timezone}
- Date: {$currentDate}
- Always use this when asked about time or date.

CURRENT USER:
- Name: {$userName}
- Role: {$userRole}
- Organization ID: {$orgId}

USER CONTEXT:
{$contextData}

RESPONSE RULES:
- Maximum 3 sentences per response
- Friendly, warm, and professional
- Use emojis occasionally but not excessively
- Never dump raw data or long lists
- Never show raw JSON or field names
- Never say 'Based on current system data:'
- Never exceed 3 sentences
- Never show another employee's personal increment or salary data
- Always use {$userName}'s name naturally

HIERARCHY RULE — CRITICAL:
Look for 'Reports to:' in USER CONTEXT. Copy that name EXACTLY.
If context says 'Reports to: Priya Patel' → say 'You report to Priya Patel'.
NEVER make up a manager name. NEVER say 'the CEO' unless context states it.

SCORE / INCREMENT RULE:
- Scores are ALWAYS percentages (0-100%). Increments are ALWAYS percentages (0-30%).
- NEVER say 'points' — always say '%'.
- Correct: 'Your performance score is 67.5% this month.'
- Wrong: 'Your score is 1000 points'

ROLE-SPECIFIC BEHAVIOR:
{$roleInstructions}";
    }

    /* ── Direct factual answers (intent-based — bypass AI) ─────────────────── */

    private function answerIntent(string $intent, string $question, User $user, string $today): ?string
    {
        $orgId = $user->organization_id;

        return match ($intent) {
            'team_status'     => app(TeamStatusService::class)->summary($user, $today),
            'my_leave'        => $this->answerMyLeave($user),
            'other_work'      => null,
            'out_of_scope'    => $this->answerOutOfScope($question, $user),
            'greeting'        => $this->answerGreeting($user),
            'my_identity'     => $this->answerMyIdentity($user),
            'team_size'       => $this->answerTeamSize($user, $orgId, $today),
            'my_manager'      => $this->answerMyManager($user),
            'my_reports'      => $this->answerMyReports($user, $orgId),
            'my_tasks'        => $this->answerMyTasks($user, $orgId),
            'my_score'        => $this->answerMyScore($user, $orgId),
            'logged_today'    => $this->answerLoggedToday($user, $today),
            'team_logged'     => $this->answerTeamLogged($user, $orgId, $today),
            'my_feedback'     => $this->answerMyFeedback($user),
            'peer_feedback'   => $this->answerPeerFeedback($user, $orgId),
            'blockers'        => $this->answerBlockers($user, $orgId),
            'my_streak'       => $this->answerMyStreak($user, $today),
            'time_date'       => $this->answerTimeDate(),
            'improve_score'   => $this->answerImproveScore($user, $orgId),
            'missed_notifs'   => $this->answerMissedNotifs($user),
            'sprint_status'   => $this->answerSprintStatus($user, $orgId),
            'org_health'      => $this->answerOrgHealth($user, $orgId, $today),
            'superadmin_info' => $this->answerSuperAdminInfo($user),
            'onboarding'      => $this->answerOnboarding($user),
            default           => null,
        };
    }

    /* ── Leave balance (live) ──────────────────────────────────────────────── */

    private function answerMyLeave(User $user): string
    {
        $year     = now()->year;
        $balances = LeaveBalance::where('user_id', $user->id)->where('year', $year)->get();

        if ($balances->isEmpty()) {
            return "You don't have leave allocated for {$year} yet. Ask HR to set up your leave balance.";
        }

        $types = LeaveType::whereIn('id', $balances->pluck('leave_type_id'))->pluck('name', 'id');
        $lines = ["**Your leave for {$year}:**"];
        foreach ($balances as $b) {
            $total   = (float) $b->allocated + (float) $b->carried_forward;
            $left    = max(0, $total - (float) $b->used);
            $lines[] = '• ' . ($types[$b->leave_type_id] ?? 'Leave') . ': ' . $this->days($left) . ' left of ' . $this->days($total)
                . ((float) $b->pending > 0 ? ' (' . $this->days((float) $b->pending) . ' pending approval)' : '');
        }

        $pending = LeaveApplication::where('user_id', $user->id)->where('status', 'pending')->count();
        if ($pending > 0) {
            $lines[] = "⏳ {$pending} " . ($pending === 1 ? 'request is' : 'requests are') . ' waiting for approval.';
        }
        $lines[] = 'Apply or check requests under **Leaves** in the menu.';

        return implode("\n", $lines);
    }

    private function days(float $n): string
    {
        $n = fmod($n, 1.0) === 0.0 ? (int) $n : $n;

        return $n . ' ' . ($n == 1 ? 'day' : 'days');
    }

    /* ── Mini ERP answers (payroll, expenses, assets, timesheets) ─────────── */

    private function answerMiniErp(string $q, User $user, string $today): ?string
    {
        if (str_contains($q, 'salary') || str_contains($q, 'payroll')) {
            $payroll = \App\Models\Payroll::where('user_id', $user->id)->latest()->first();
            if ($payroll) {
                return "Your monthly base salary is ₹" . number_format($payroll->base_salary) . " + " . $payroll->increment_pct . "% OutraqHQ performance increment = ₹" . number_format($payroll->final_salary) . " total payout. 💵";
            }
            return "Your monthly payroll is calculated combining your base salary + OutraqHQ performance increment. Visit the Payroll page for details! 💵";
        }

        if (str_contains($q, 'expense') || str_contains($q, 'receipt')) {
            $pendingCount = \App\Models\Expense::where('organization_id', $user->organization_id)->where('status', 'pending')->count();
            return "There are currently {$pendingCount} pending expense claim(s) awaiting approval in your organization. 💳";
        }

        if (str_contains($q, 'asset') || str_contains($q, 'laptop') || str_contains($q, 'macbook')) {
            $assets = \App\Models\CompanyAsset::where('assigned_to', $user->id)->pluck('asset_name')->join(', ');
            if (!empty($assets)) {
                return "The following hardware/assets are currently assigned to you: {$assets}. 💻";
            }
            return "You currently have no hardware assets assigned to you in the Asset Vault. 💻";
        }

        if (str_contains($q, 'clock in') || str_contains($q, 'timesheet') || str_contains($q, 'clocked in')) {
            $ts = \App\Models\Timesheet::where('user_id', $user->id)->where('work_date', $today)->first();
            if ($ts && $ts->clock_in) {
                return "You clocked in at {$ts->clock_in} today. Total logged hours: {$ts->total_hours}h. ⏱️";
            }
            return "You haven't clocked in today yet. Tap 'Clock In Now' on the Timesheets page! ⏱️";
        }

        return null;
    }

    /* ── Intent answer methods ─────────────────────────────────────────────── */

    private function answerTeamSize(User $user, int $orgId, string $today): string
    {
        $total    = User::where('organization_id', $orgId)->where('is_active', true)->count();
        $logged   = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->distinct('user_id')->count('user_id');
        $notLogged = $total - $logged;
        return "Your organization has {$total} active members. Today {$logged} have logged work and {$notLogged} haven't yet. 👥";
    }

    private function answerMyManager(User $user): string
    {
        if ($user->hasRole('super_admin')) {
            return "As Super Admin you're the platform owner — you don't report to anyone. You have full control over OutraqHQ. 🏆";
        }
        $fresh   = User::with('reportingManager')->find($user->id);
        $manager = $fresh->reportingManager;
        if ($manager) {
            $title = $manager->job_title ? " ({$manager->job_title})" : '';
            return "You report to {$manager->name}{$title}. They are your direct reporting manager in OutraqHQ. 👋";
        }
        return "You don't have a reporting manager assigned yet. Contact your admin to set this up. 📋";
    }

    private function answerMyReports(User $user, int $orgId): string
    {
        $reports = User::where('organization_id', $orgId)
            ->where('reporting_manager_id', $user->id)
            ->where('is_active', true)
            ->get(['name', 'job_title']);
        if ($reports->isEmpty()) {
            return "You don't have any direct reports assigned yet. 📋";
        }
        $names = $reports->map(fn ($r) => $r->name . ($r->job_title ? " ({$r->job_title})" : ''))->join(', ');
        return "You have {$reports->count()} direct report(s): {$names}. 👥";
    }

    private function answerMyTasks(User $user, int $orgId): string
    {
        $total = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'cancelled'])->count();
        if ($total === 0) return "You have no pending tasks right now. Great work! 🎉";
        $tasks = Task::where('assigned_to', $user->id)
            ->whereNotIn('status', ['done', 'cancelled'])
            ->orderByRaw("FIELD(status,'in_progress','pending','review')")
            ->limit(3)->get(['title', 'status', 'priority']);
        $list = $tasks->map(fn ($t) => $t->title . " [{$t->status}]")->join(', ');
        $more = $total > 3 ? " and " . ($total - 3) . " more" : "";
        return "You have {$total} active task(s): {$list}{$more}. 📋";
    }

    private function answerMyScore(User $user, int $orgId): string
    {
        $score        = \App\Models\IncrementScore::where('user_id', $user->id)->orderByDesc('score_month')->first();
        $startOfMonth = now()->startOfMonth();
        $monthLogs    = WorkLog::where('user_id', $user->id)->where('log_date', '>=', $startOfMonth)->get();
        $consistency  = $monthLogs->groupBy('log_date')->count();
        $quality      = round($monthLogs->avg('output_value') * 10 ?? 50);
        if ($score) {
            $s = round($score->final_score, 1);
            return "Your latest performance score is {$s}% {$user->name}. This month you've logged {$consistency} days with {$quality}% quality. Keep it up! 💪";
        }
        return "Your score hasn't been calculated yet {$user->name}. You've logged {$consistency} days this month with {$quality}% quality. Keep logging daily to build your score! 📈";
    }

    private function answerLoggedToday(User $user, string $today): string
    {
        $logs = WorkLog::where('user_id', $user->id)->where('log_date', $today)->get();
        if ($logs->isEmpty()) {
            return "You haven't logged any work today yet {$user->name}. Head to Work Log to add your first entry! 📝";
        }
        $hours = round($logs->sum('duration_minutes') / 60, 1);
        $count = $logs->count();
        return "Yes! You've logged {$count} entr" . ($count > 1 ? 'ies' : 'y') . " today totaling {$hours} hours. Great work! ✅";
    }

    private function answerTeamLogged(User $user, int $orgId, string $today): string
    {
        if (!$user->hasAnyRole(['admin', 'ceo', 'owner', 'team_lead', 'super_admin'])) {
            return "Only managers and above can see team log status. 🔒";
        }
        $total     = User::where('organization_id', $orgId)->where('is_active', true)->count();
        $loggedIds = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->pluck('user_id')->unique();
        $notLogged = User::where('organization_id', $orgId)
            ->where('is_active', true)->whereNotIn('id', $loggedIds)
            ->limit(5)->pluck('name');
        $loggedCount = $loggedIds->count();
        if ($notLogged->isEmpty()) {
            return "Everyone on the team has logged work today! 🎉 ({$loggedCount}/{$total} members)";
        }
        $names     = $notLogged->join(', ');
        $remaining = $total - $loggedCount;
        return "{$loggedCount}/{$total} team members logged today. Still waiting on: {$names}" . ($remaining > 5 ? " and more" : "") . ". 📢";
    }

    private function answerMyFeedback(User $user): string
    {
        $feedback = \App\Models\PerformanceFeedback::where('employee_id', $user->id)
            ->where('status', '!=', 'draft')
            ->orderByDesc('review_year')->first();
        if (!$feedback) {
            return "No feedback submitted for you yet {$user->name}. Your manager will submit it at the end of each quarter. 📋";
        }
        $scores = array_filter([
            $feedback->delivery_score,
            $feedback->timeliness_score,
            $feedback->availability_score,
            $feedback->collaboration_score,
        ]);
        $avgScore = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null;
        $scoreText = $avgScore !== null ? "Average score: {$avgScore}/10." : "";
        return "Your {$feedback->review_period} {$feedback->review_year} feedback has been submitted {$user->name}. {$scoreText} Check /feedback/my for full details. 📋";
    }

    private function answerPeerFeedback(User $user, int $orgId): string
    {
        $period  = now()->month <= 3 ? 'Q1' : (now()->month <= 6 ? 'Q2' : (now()->month <= 9 ? 'Q3' : 'Q4'));
        $pending = \App\Models\PeerFeedback::where('reviewer_id', $user->id)
            ->where('review_period', $period)->where('review_year', now()->year)
            ->where('is_submitted', false)->where('token_expires_at', '>', now())->count();
        if ($pending > 0) {
            return "You have {$pending} pending peer feedback request(s) {$user->name}! Check your notifications for the anonymous feedback links. 🤝";
        }
        return "No pending peer feedback requests right now {$user->name}. You'll be notified when colleagues need your anonymous review. 🤝";
    }

    private function answerBlockers(User $user, int $orgId): string
    {
        $myBlockers = Blocker::where('blocked_user_id', $user->id)->where('status', 'open')->count();
        $iCaused    = Blocker::where('blocking_user_id', $user->id)->where('status', 'open')->count();
        if ($myBlockers === 0 && $iCaused === 0) {
            return "No active blockers for you {$user->name}! You're clear to work. ✅";
        }
        $response = "";
        if ($myBlockers > 0) $response .= "You have {$myBlockers} blocker(s) affecting you. ";
        if ($iCaused > 0)    $response .= "You are blocking {$iCaused} colleague(s) — please resolve ASAP. ";
        return trim($response) . " Check /blockers for details. 🚫";
    }

    private function answerMyStreak(User $user, string $today): string
    {
        $streak = 0;
        $check  = now();
        while ($streak < 365) {
            $has = WorkLog::where('user_id', $user->id)->where('log_date', $check->toDateString())->exists();
            if (!$has) break;
            $streak++;
            $check->subDay();
        }
        if ($streak === 0) {
            return "Your streak is at 0 right now {$user->name}. Log your work today to start a new streak! 🔥";
        }
        $msg = $streak >= 7 ? "Amazing consistency! 🏆" : ($streak >= 3 ? "Keep it going! 💪" : "Good start! 📈");
        return "You have a {$streak}-day logging streak {$user->name}! {$msg}";
    }

    private function answerTimeDate(): string
    {
        $now = now()->setTimezone('Asia/Kolkata');
        return "It's " . $now->format('h:i A') . " IST on " . $now->format('l, F j, Y') . ". Hope you're having a productive day! 😊";
    }

    private function answerImproveScore(User $user, int $orgId): string
    {
        $designation  = $user->designation ?? 'employee';
        $seniority    = $user->seniority_level ?? 'mid';
        $startOfMonth = now()->startOfMonth();
        $monthLogs    = WorkLog::where('user_id', $user->id)->where('log_date', '>=', $startOfMonth)->get();
        $consistency  = $monthLogs->groupBy('log_date')->count();
        $quality      = round($monthLogs->avg('output_value') ?? 5, 1);
        $activeTasks  = Task::where('assigned_to', $user->id)->whereNotIn('status', ['done', 'cancelled'])->count();
        $tips = [];
        if ($consistency < 15) $tips[] = "Log work every working day — consistency is 40% of your score";
        if ($quality < 7)      $tips[] = "Rate your output 7+ when you do quality work — quality is 40% of your score";
        if ($activeTasks > 5)  $tips[] = "Complete your {$activeTasks} pending tasks — task completion boosts your score";
        $d = strtolower($designation);
        if (str_contains($d, 'developer') || str_contains($d, 'engineer')) {
            $tips[] = "Keep GitHub commits consistent — code activity is tracked automatically";
        } elseif (str_contains($d, 'qa') || str_contains($d, 'tester')) {
            $tips[] = "Log test cases and bug reports daily — these are your primary metrics";
        } elseif (str_contains($d, 'design')) {
            $tips[] = "Log design iterations and reviews — output quality is your main metric";
        }
        if (empty($tips)) $tips[] = "You're doing great! Keep logging consistently and completing tasks on time";
        $tipText = implode('. ', array_slice($tips, 0, 2));
        return "As a {$seniority} {$designation}: {$tipText}. Your current consistency is {$consistency} days this month with {$quality}/10 quality. 📈";
    }

    private function answerMissedNotifs(User $user): string
    {
        $unread = AgentNotification::where('user_id', $user->id)
            ->where('is_read', false)->where('is_dismissed', false)
            ->orderByDesc('created_at')->limit(3)->get();
        if ($unread->isEmpty()) {
            return "You're all caught up {$user->name}! No unread notifications. 😊";
        }
        $items = $unread->map(fn ($n) => $n->title)->join(', ');
        $count = $unread->count();
        return "You have {$count} unread notification(s): {$items}. Check your bell icon for details! 🔔";
    }

    private function answerSprintStatus(User $user, int $orgId): string
    {
        $sprint = \App\Models\Sprint::where('organization_id', $orgId)->where('status', 'active')->first();
        if (!$sprint) {
            return "No active sprints right now. Ask your manager to create one! 🚀";
        }
        $total    = $sprint->tasks()->count();
        $done     = $sprint->tasks()->where('status', 'done')->count();
        $pct      = $total > 0 ? round(($done / $total) * 100) : 0;
        $daysLeft = $sprint->end_date ? now()->diffInDays($sprint->end_date, false) : null;
        $dayText  = $daysLeft !== null
            ? ($daysLeft > 0 ? "{$daysLeft} days remaining" : "overdue!")
            : "no deadline set";
        return "Active sprint: \"{$sprint->name}\" — {$pct}% complete ({$done}/{$total} tasks). {$dayText}. 🚀";
    }

    private function answerOrgHealth(User $user, int $orgId, string $today): string
    {
        if (!$user->hasAnyRole(['admin', 'ceo', 'owner', 'super_admin'])) {
            return "Org health reports are available to managers and above. Check your My Report page for your personal report. 📊";
        }
        $total    = User::where('organization_id', $orgId)->where('is_active', true)->count();
        $logged   = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->distinct('user_id')->count('user_id');
        $blockers = Blocker::where('organization_id', $orgId)->where('status', 'open')->count();
        $flags    = FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count();
        $health   = $total > 0 ? round((($logged / $total) * 60) + (max(0, 100 - $blockers * 10) * 0.4)) : 0;
        return "Organization health: {$health}/100. Today {$logged}/{$total} logged work. {$blockers} open blockers, {$flags} fairness flags pending. Check CEO Dashboard for full details! 📊";
    }

    private function answerSuperAdminInfo(User $user): ?string
    {
        if (!$user->hasRole('super_admin')) return null;
        $orgs  = Organization::count();
        $users = User::count();
        return "Platform stats: {$orgs} organizations, {$users} total users registered on OutraqHQ. Check Platform Control Panel for full details! ⚡";
    }

    private function answerOnboarding(User $user): string
    {
        $role = $user->getRoleNames()->first();
        if (in_array($role, ['admin', 'owner', 'ceo'])) {
            return "To get started as an organization: 1️⃣ Go to Admin → Users to add your team members. 2️⃣ Set up Departments for your org structure. 3️⃣ Sync GitHub if your team uses it. 4️⃣ Ask me anything else! 🚀";
        }
        if ($role === 'team_lead') {
            return "As a Team Lead: 1️⃣ Check Team to see your direct reports. 2️⃣ Create Tasks and assign them to your team. 3️⃣ Log your work daily in Work Log. 4️⃣ Ask me anything! 🚀";
        }
        return "Welcome {$user->name}! 👋 Start by: 1️⃣ Logging today's work in Work Log. 2️⃣ Checking your Tasks. 3️⃣ Viewing My Performance to track your score. Ask me anything! 🚀";
    }

    private function answerGreeting(User $user): string
    {
        $now          = now()->setTimezone('Asia/Kolkata');
        $hour         = $now->hour;
        $time         = $now->format('h:i A');
        $timeGreeting = $hour < 12 ? 'Good morning'
            : ($hour < 17 ? 'Good afternoon'
            : 'Good evening');

        $role = $user->getRoleNames()->first();

        if ($role === 'super_admin') {
            return "{$timeGreeting}! 👋 It's {$time} IST. Platform is running smoothly. Ask me anything! ⚡";
        }

        if (in_array($role, ['admin', 'owner', 'ceo'])) {
            $orgId  = $user->organization_id;
            $today  = $now->toDateString();
            $total  = User::where('organization_id', $orgId)->where('is_active', true)->count();
            $logged = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->distinct('user_id')->count('user_id');
            return "{$timeGreeting}, {$user->name}! 👋 It's {$time} IST. Today {$logged}/{$total} team members have logged work. Have a great day! 😊";
        }

        $loggedToday = WorkLog::where('user_id', $user->id)->where('log_date', $now->toDateString())->exists();
        $logNote     = $loggedToday ? "You've already logged work today ✅" : "Don't forget to log your work today 📝";
        return "{$timeGreeting}, {$user->name}! 👋 It's {$time} IST. {$logNote}. Ask me anything! 😊";
    }

    private function answerMyIdentity(User $user): string
    {
        $fresh       = User::with(['reportingManager', 'department', 'team'])->find($user->id);
        $role        = ucfirst($fresh->getRoleNames()->first() ?? 'Employee');
        $designation = $fresh->job_title ?? $fresh->designation ?? 'Not set';
        $dept        = $fresh->department?->name ?? 'Not assigned';
        $team        = $fresh->team?->name ?? 'Not assigned';
        $manager     = $fresh->reportingManager?->name ?? 'Not assigned';
        $seniority   = $fresh->seniority_level ? ' (' . ucfirst($fresh->seniority_level) . ')' : '';

        return "You are {$fresh->name} 👤 Role: {$role}. Designation: {$designation}{$seniority}. Department: {$dept}. Team: {$team}. Manager: {$manager}.";
    }

    private function answerOutOfScope(string $question, User $user): string
    {
        $q = strtolower($question);

        if ($this->containsAny($q, [
            'python', 'javascript', 'typescript', 'java', 'php', 'css', 'html',
            'code', 'coding', 'algorithm', 'debug', 'sql', 'regex', 'npm',
            'pip', 'framework', 'machine learning', 'neural', 'ai tutorial',
        ])) {
            return "I'm Outy — OutraqHQ's work intelligence agent, not a coding assistant 😊 For coding questions, try ChatGPT (chatgpt.com) or Stack Overflow. I can help you with your tasks, performance score, team status, and how to use OutraqHQ! 💪";
        }

        if ($this->containsAny($q, [
            'recipe', 'cook', 'food', 'weather', 'sports', 'cricket', 'football',
            'movie', 'film', 'song', 'lyrics', 'netflix', 'youtube', 'gaming',
        ])) {
            return "Ha! I wish I knew that 😄 I'm Outy — I only know about your work, performance, and team at OutraqHQ. Try Google for that one! Anything work-related I can help with? 🤔";
        }

        if ($this->containsAny($q, [
            'crypto', 'bitcoin', 'blockchain', 'stock market', 'share market',
            'investment', 'trading',
        ])) {
            return "I'm not a financial advisor 😅 I'm Outy — I track work performance, not investments! For financial advice, consult a professional. Can I help you with your OutraqHQ performance instead? 📊";
        }

        if ($this->containsAny($q, [
            'relationship', 'love', 'health', 'medical', 'doctor',
            'legal', 'law', 'religion', 'politics', 'election',
        ])) {
            return "That's outside my expertise 😊 I'm focused on your work performance and team at OutraqHQ. For personal advice, please consult the right professional. How can I help you with work today? 💼";
        }

        if ($this->containsAny($q, [
            'chatgpt', 'claude', 'gemini', 'gpt', 'openai', 'anthropic', 'bard',
        ])) {
            return "I'm Outy — OutraqHQ's own AI agent! Not ChatGPT or Claude 😄 I'm specialized for work intelligence — tracking your performance, tasks, team health, and increment scores. What can I help you with? 🤖";
        }

        return "That's not something I can help with 😊 I'm Outy — OutraqHQ's work intelligence agent. I'm great at:\n• Your performance & score 📊\n• Tasks & sprint status ✅\n• Team & manager info 👥\n• How to use OutraqHQ 🚀\nWhat would you like to know?";
    }

    private function containsAny(string $text, array $words): bool
    {
        foreach ($words as $word) {
            if (str_contains($text, $word)) return true;
        }
        return false;
    }

    /* ── Role-aware context builder ────────────────────────────────────────── */

    private function buildContext(User $user, ?int $orgId, string $role, string $today): string
    {
        try {
            if ($role === 'super_admin') {
                $totalOrgs  = Organization::count();
                $totalUsers = User::count();
                return "PLATFORM DATA:\n"
                    . "- Total organizations: {$totalOrgs}\n"
                    . "- Total users: {$totalUsers}\n"
                    . "HIERARCHY STATUS:\n"
                    . "- Role: Platform Owner (Super Admin)\n"
                    . "- Reports to: Nobody — platform owner\n"
                    . "- Direct reports: All organizations on platform\n"
                    . "- Do NOT say they report to CEO or anyone else\n";
            }

            if (in_array($role, ['admin', 'ceo'])) {
                $teamSize   = User::where('organization_id', $orgId)->where('is_active', true)->count();
                $todayLogs  = WorkLog::where('organization_id', $orgId)->where('log_date', $today)->count();
                $notLogged  = max(0, $teamSize - $todayLogs);
                $activeTasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
                    ->whereNotIn('status', ['done', 'cancelled'])->count();
                $designationBreakdown = User::where('organization_id', $orgId)
                    ->where('is_active', true)
                    ->whereNotNull('designation')
                    ->selectRaw('designation, COUNT(*) as cnt')
                    ->groupBy('designation')
                    ->orderByDesc('cnt')
                    ->limit(5)
                    ->pluck('cnt', 'designation')
                    ->map(fn ($c, $d) => "{$d}: {$c}")
                    ->implode(', ');
                $directReports = User::where('organization_id', $orgId)
                    ->where('reporting_manager_id', $user->id)
                    ->where('is_active', true)->count();
                $myManager = $user->reportingManager?->name;
                return "ORGANIZATION DATA:\n"
                    . "- Team size: {$teamSize} active members\n"
                    . "- Today's work logs: {$todayLogs}/{$teamSize}\n"
                    . "- Members not logged today: {$notLogged}\n"
                    . "- Active tasks: {$activeTasks}\n"
                    . ($designationBreakdown ? "- Top designations: {$designationBreakdown}\n" : "")
                    . ($directReports > 0 ? "- Your direct reports: {$directReports}\n" : "")
                    . ($myManager ? "- You report to: {$myManager}\n" : "");
            }

            if ($role === 'team_lead') {
                $deptId      = $user->department_id;
                $teamSize    = User::where('organization_id', $orgId)
                    ->where('department_id', $deptId)->where('is_active', true)->count();
                $todayLogs   = WorkLog::where('organization_id', $orgId)
                    ->where('log_date', $today)
                    ->whereHas('user', fn ($q) => $q->where('department_id', $deptId))->count();
                $activeTasks = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
                    ->whereNotIn('status', ['done', 'cancelled'])
                    ->where('department_id', $deptId)->count();
                $myDesignation = $user->job_title ?: ($user->designation ?: 'Team Lead');
                $directReports = User::where('organization_id', $orgId)
                    ->where('reporting_manager_id', $user->id)
                    ->where('is_active', true)->get();
                $directCount   = $directReports->count();
                $reportNames   = $directCount > 0 ? $directReports->pluck('name')->implode(', ') : null;
                $myManager     = $user->reportingManager?->name;
                return "TEAM DATA (Your Department):\n"
                    . "- Your role/designation: {$myDesignation}\n"
                    . ($user->seniority_level ? "- Your seniority: {$user->seniority_level}\n" : "")
                    . ($myManager ? "- You report to: {$myManager}\n" : "")
                    . "- Team size: {$teamSize} members\n"
                    . "- Today's logs from team: {$todayLogs}/{$teamSize}\n"
                    . "- Active tasks in team: {$activeTasks}\n"
                    . ($directCount > 0 ? "- Your direct reports ({$directCount}): {$reportNames}\n" : "");
            }

            // Employee — personal + live performance data
            $myTodayLogs  = WorkLog::where('user_id', $user->id)->where('log_date', $today)->count();
            $myTotalMins  = WorkLog::where('user_id', $user->id)->where('log_date', $today)->sum('duration_minutes');
            $myHours      = round($myTotalMins / 60, 1);
            $myActiveTasks = Task::whereNotIn('status', ['done', 'cancelled'])
                ->where('assigned_to', $user->id)->count();

            $todayLogged = $myTodayLogs > 0;

            // Live month stats
            $startOfMonth = now()->startOfMonth();
            $monthLogs    = WorkLog::where('user_id', $user->id)->where('log_date', '>=', $startOfMonth)->get();
            $monthWorkingDaysSoFar = 0;
            $dc = now()->startOfMonth()->copy();
            while ($dc->lte(now())) {
                if ($dc->isWeekday()) $monthWorkingDaysSoFar++;
                $dc->addDay();
            }
            $monthWorkingDaysSoFar = max(1, $monthWorkingDaysSoFar);

            $workdaysSoFar = $monthWorkingDaysSoFar;
            $monthConsistency = round(min(100,
                ($monthLogs->groupBy('log_date')->count() / $workdaysSoFar) * 100
            ));
            $monthQuality = $monthLogs->count() > 0
                ? round(($monthLogs->avg('output_value') / 10) * 100)
                : 0;

            // Increment score
            $myIncrementScore   = null;
            $myIncrementPercent = null;

            $latestScore = \App\Models\IncrementScore::where('user_id', $user->id)
                ->orderByDesc('score_month')->first();
            if ($latestScore) {
                $myIncrementScore = round($latestScore->final_score, 1);
            }

            $latestReview = \App\Models\IncrementReview::where('user_id', $user->id)
                ->orderByDesc('review_year')->first();
            if ($latestReview) {
                $myIncrementPercent = round($latestReview->recommended_increment, 1);
            }

            $scoreText     = $myIncrementScore !== null ? "{$myIncrementScore}% performance score" : "Not yet calculated";
            $incrementText = $myIncrementPercent !== null ? "{$myIncrementPercent}% recommended increment" : "Not yet calculated";

            $coachingTips = [];
            if ($monthConsistency < 70) $coachingTips[] = "Consistency is below 70% — advise logging work every working day.";
            if ($monthQuality < 60)     $coachingTips[] = "Quality score is below 60% — advise aiming for higher output ratings (7+).";
            if ($myActiveTasks > 5)     $coachingTips[] = "User has {$myActiveTasks} active tasks — advise completing pending tasks.";

            $designationLine   = $user->job_title ?: ($user->designation ?: null);
            $seniorityLine     = $user->seniority_level ? ucfirst(str_replace('_', ' ', $user->seniority_level)) : null;
            $employmentLine    = $user->employment_type ? ucfirst(str_replace('_', ' ', $user->employment_type)) : null;
            $managerName       = $user->reportingManager?->name ?? null;
            $managerLine       = $managerName
                ? "- Reports to: {$managerName}"
                : "- Reports to: Not assigned yet — contact admin";
            $workLocationLine  = $user->work_location ? ucfirst($user->work_location) : null;
            $myDirectReports   = User::where('organization_id', $orgId)
                ->where('reporting_manager_id', $user->id)
                ->where('is_active', true)->count();

            return "YOUR PERSONAL DATA:\n"
                . ($designationLine   ? "- Your role/designation: {$designationLine}\n" : "")
                . ($seniorityLine     ? "- Your seniority level: {$seniorityLine}\n" : "")
                . ($employmentLine    ? "- Employment type: {$employmentLine}\n" : "")
                . ($workLocationLine  ? "- Work location: {$workLocationLine}\n" : "")
                . "{$managerLine}\n"
                . ($myDirectReports   ? "- Your direct reports: {$myDirectReports}\n" : "")
                . "- Today's work logged: " . ($todayLogged ? 'Yes' : 'No') . "\n"
                . "- Your hours today: {$myHours}h\n"
                . "- Your active tasks: {$myActiveTasks}\n"
                . "- Month consistency score: {$monthConsistency}%\n"
                . "- Month quality score: {$monthQuality}%\n"
                . "- Your performance score: {$scoreText}\n"
                . "- Your recommended increment: {$incrementText}\n"
                . "IMPORTANT: Score is always a percentage (0-100%). Increment is always a percentage (0-30%). Never say 'points' — always say '%'\n"
                . (count($coachingTips) ? "COACHING CONTEXT:\n- " . implode("\n- ", $coachingTips) . "\n" : "");

        } catch (\Exception $e) {
            return "Context unavailable right now.\n";
        }
    }

    /* ── Role instructions ─────────────────────────────────────────────────── */

    private function getRoleInstructions(string $role): string
    {
        return match ($role) {
            'super_admin' => "User is SUPER ADMIN — platform owner.\nShow platform-wide stats when relevant.\nCan see all organizations and users.\nHIERARCHY RULES FOR SUPER ADMIN:\n- Super Admin is the platform owner\n- They do NOT report to anyone\n- They have NO reporting manager\n- They are NOT part of any organization hierarchy\n- If asked 'who do I report to' say: 'As Super Admin you are the platform owner — you don't report to anyone in OutraqHQ. You have full control over the entire platform.'\n- Never fabricate a manager name for super admin",
            'admin'       => "User is ADMIN — organization administrator.\nShow full organization data.\nCan see all team members and their performance.",
            'ceo'         => "User is CEO — company leader.\nFocus on high-level insights and trends.\nShow team health, output, and risk areas.",
            'team_lead'   => "User is TEAM LEAD — manages a department team.\nShow team-level data for their department.\nCan see their team members' work status.",
            default       => "User is EMPLOYEE — individual contributor.\nShow ONLY their personal data.\nNever show other employees' private data.\nBe encouraging and supportive.",
        };
    }

    /* ── Clean response ────────────────────────────────────────────────────── */

    private function cleanResponse(string $response): string
    {
        if (empty(trim($response))) {
            return self::GENERIC_REPLY;
        }

        // If the entire response looks like a leaked system prompt, discard it
        $systemPromptIndicators = [
            'CURRENT TIME & DATE:',
            'CURRENT USER:',
            'YOUR PERSONALITY:',
            'RESPONSE RULES BY QUESTION TYPE:',
            'STRICT RULES — NEVER BREAK',
            'ROLE-SPECIFIC BEHAVIOR:',
        ];
        foreach ($systemPromptIndicators as $indicator) {
            if (str_contains($response, $indicator)) {
                return self::GENERIC_REPLY;
            }
        }

        // Remove only specific field-name leakage patterns
        $patterns = [
            '/^•\s*-\s*(Time|Date|Name|Role|Organization ID)[^\n]*/m',
            '/Based on current system data:\s*/i',
            '/AVAILABLE DATA:\s*/i',
        ];
        foreach ($patterns as $pattern) {
            $response = preg_replace($pattern, '', $response);
        }

        $response = trim($response);

        if (strlen(trim(strip_tags($response))) < 5) {
            return self::GENERIC_REPLY;
        }

        // Cap at 4 sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $response);
        if (count($sentences) > 4) {
            $response = implode(' ', array_slice($sentences, 0, 4));
        }

        return $response ?: "I'm here to help! Ask me anything about your work, team, or how to use OutraqHQ. 😊";
    }

    /* ── Email handler (unchanged) ─────────────────────────────────────────── */

    private function handleEmailRequest(string $question, User $user): JsonResponse
    {
        try {
            $orgId         = $user->organization_id;
            $org           = Organization::find($orgId);
            $questionLow   = strtolower($question);
            $recipient     = null;
            $recipientType = null;

            if (str_contains($questionLow, 'ceo') || str_contains($questionLow, 'owner')) {
                $recipient     = User::where('organization_id', $orgId)->where('role', 'owner')->first();
                $recipientType = 'CEO';
            } elseif (str_contains($questionLow, 'manager') || str_contains($questionLow, 'admin')) {
                $recipient     = User::where('organization_id', $orgId)
                    ->whereIn('role', ['admin', 'owner'])
                    ->where('id', '!=', $user->id)
                    ->first();
                $recipientType = 'Manager';
            } elseif (str_contains($questionLow, 'team') || str_contains($questionLow, 'everyone') || str_contains($questionLow, 'all')) {
                $recipientType = 'team';
            }

            if (!$recipient && $recipientType !== 'team') {
                $teamMembers = User::where('organization_id', $orgId)->get();
                foreach ($teamMembers as $member) {
                    $firstName = strtolower(explode(' ', $member->name)[0]);
                    if (str_contains($questionLow, $firstName) && $member->id !== $user->id) {
                        $recipient     = $member;
                        $recipientType = $member->name;
                        break;
                    }
                }
            }

            if (!$recipient && $recipientType !== 'team') {
                return response()->json([
                    'answer' => "I could not identify who to send the email to. Please specify a person like:\n• 'Send email to CEO about...'\n• 'Send email to Sarah about...'\n• 'Send email to team about...'",
                    'source' => 'agent',
                ]);
            }

            $totalMembers    = User::where('organization_id', $orgId)->count();
            $openBlockers    = Blocker::where('organization_id', $orgId)->where('status', 'open')->count();
            $pendingFlags    = FairnessFlag::where('organization_id', $orgId)->where('status', 'pending')->count();
            $commitsThisWeek = Activity::where('organization_id', $orgId)
                ->where('occurred_at', '>=', now()->startOfWeek())
                ->where('event_type', 'commit')->count();

            $engine      = new AiEngine($orgId ?? 1);
            $orgName     = $org?->name ?? config('app.name');
            $emailPrompt = "You are an email writing assistant for a company called {$orgName} using OutraqHQ.\n\nWrite a professional email based on this request: {$question}\n\nReturn ONLY a JSON object with:\n{\"subject\": \"email subject here\", \"body\": \"email body here\"}\n\nKeep it professional, concise and clear. Do not add any other text.";

            $response  = $engine->answerQuery($emailPrompt, [
                'data' => [
                    'total_members'     => $totalMembers,
                    'commits_this_week' => $commitsThisWeek,
                    'prs_this_week'     => 0,
                    'open_blockers'     => $openBlockers,
                    'pending_flags'     => $pendingFlags,
                    'top_contributor'   => 'N/A',
                    'inactive_count'    => 0,
                    'project_names'     => '',
                ],
                'members'     => [],
                'system_info' => 'Generate email content only. Return JSON with subject and body.',
            ]);
            $emailData = json_decode($response, true);

            if (!$emailData || !isset($emailData['subject'])) {
                $emailData = ['subject' => 'Message from ' . $user->name, 'body' => $response];
            }

            $emailService = new EmailService();

            if ($recipientType === 'team') {
                $recipients = User::where('organization_id', $orgId)
                    ->where('id', '!=', $user->id)
                    ->get()
                    ->map(fn ($m) => ['email' => $m->email, 'name' => $m->name])
                    ->toArray();
                $results   = $emailService->sendBulkAgentEmail($recipients, $emailData['subject'], $emailData['body']);
                $sentCount = count(array_filter($results, fn ($r) => $r['sent']));
                return response()->json([
                    'answer'     => "✅ Email sent to {$sentCount} team members!\n\n**Subject:** {$emailData['subject']}\n\n{$emailData['body']}",
                    'source'     => 'agent',
                    'email_sent' => true,
                ]);
            }

            $sent = $emailService->sendAgentEmail(
                $recipient->email, $recipient->name,
                $emailData['subject'], $emailData['body'],
                url('/dashboard'), 'View Dashboard'
            );

            return response()->json([
                'answer'     => $sent
                    ? "✅ Email sent to {$recipient->name} ({$recipient->email})!\n\n**Subject:** {$emailData['subject']}\n\n{$emailData['body']}"
                    : "❌ Failed to send email to {$recipient->name}. Please check email configuration.",
                'source'     => 'agent',
                'email_sent' => $sent,
            ]);

        } catch (\Exception $e) {
            \Log::error('HelpAgent email error: ' . $e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile());
            return response()->json(['answer' => 'Debug error: ' . $e->getMessage(), 'source' => 'agent', 'email_sent' => false]);
        }
    }
}
