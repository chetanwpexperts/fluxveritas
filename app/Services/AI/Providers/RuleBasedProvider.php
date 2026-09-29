<?php

namespace App\Services\AI\Providers;

use App\Services\AI\AiProviderInterface;

class RuleBasedProvider implements AiProviderInterface
{
    public function getProviderName(): string
    {
        return 'rule_based';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function generateSummary(array $data): string
    {
        $health = $this->assessHealth($data);
        $concerns = $this->identifyConcerns($data);
        $recommendation = $this->generateRecommendation($data);

        $summary = "Team Health: {$health['status']}. ";
        $summary .= "Over the past week, the team completed ";
        $summary .= "{$data['commits_this_week']} commits and ";
        $summary .= "{$data['prs_this_week']} pull requests across ";
        $summary .= "{$data['total_members']} members. ";

        if ($data['inactive_count'] > 0) {
            $summary .= "{$data['inactive_count']} member(s) have shown ";
            $summary .= "no recent activity. ";
        }

        if ($data['open_blockers'] > 0) {
            $summary .= "There are {$data['open_blockers']} open blockers ";
            $summary .= "that need attention. ";
        }

        if ($data['pending_flags'] > 0) {
            $summary .= "{$data['pending_flags']} fairness flag(s) are ";
            $summary .= "pending review. ";
        }

        $summary .= "\n\n";

        if (!empty($concerns)) {
            $summary .= "Key concerns: " . implode('. ', $concerns) . ". ";
        } else {
            $summary .= "No major concerns detected at this time. ";
            $summary .= "The team appears to be functioning well. ";
        }

        $summary .= "\n\nRecommendation: " . $recommendation;

        return $summary;
    }

    public function answerQuery(string $question, array $context): string
    {
        $q       = strtolower($question);
        $data    = $context['data'] ?? [];
        $members = $context['members'] ?? [];

        if (str_contains($q, 'most') && (str_contains($q, 'contribut') || str_contains($q, 'commit'))) {
            $top = collect($members)->sortByDesc('commits')->first();
            if ($top) {
                $name    = is_array($top) ? ($top['name'] ?? 'Unknown') : ($top->name ?? 'Unknown');
                $commits = is_array($top) ? ($top['commits'] ?? 0) : 0;
                return "Based on the last 30 days, {$name} has contributed the most with {$commits} commits.";
            }
        }

        if (str_contains($q, 'inactive') || str_contains($q, 'no activity') || str_contains($q, 'not working')) {
            $inactive = collect($members)->filter(fn($m) => (is_array($m) ? ($m['commits'] ?? 0) : 0) === 0);
            if ($inactive->isEmpty()) {
                return "All team members have shown activity in the last 30 days. No inactive members detected.";
            }
            $names = $inactive->map(fn($m) => is_array($m) ? ($m['name'] ?? '') : ($m->name ?? ''))->join(', ');
            return "The following member(s) have no recorded activity in the last 30 days: {$names}.";
        }

        if (str_contains($q, 'block') || str_contains($q, 'stuck')) {
            $count = $data['open_blockers'] ?? 0;
            if ($count === 0) {
                return "There are no open blockers at the moment. The team is currently unblocked.";
            }
            return "There are {$count} open blocker(s) affecting the team. Visit the Blockers page to see details.";
        }

        if (str_contains($q, 'fair') || str_contains($q, 'flag') || str_contains($q, 'bias')) {
            $count = $data['pending_flags'] ?? 0;
            if ($count === 0) {
                return "No fairness flags are currently pending. Run a Fairness Analysis to check for workload imbalances.";
            }
            return "There are {$count} pending fairness flag(s) that need your review. Visit the Fairness page to investigate.";
        }

        $members   = $data['total_members'] ?? 0;
        $commits   = $data['commits_this_week'] ?? 0;
        $blockers  = $data['open_blockers'] ?? 0;
        $flags     = $data['pending_flags'] ?? 0;
        return "Team overview: {$members} members, {$commits} commits this week, {$blockers} open blockers, {$flags} pending flags. Try asking something specific like 'who contributed most' or 'are there any blockers'.";
    }

    public function generateResponse(string $prompt): string
    {
        $promptLower = strtolower($prompt);

        if (str_contains($promptLower, '=== system health ===')) {
            return $this->extractHealthSummary($prompt);
        }

        if (str_contains($promptLower, '=== work logs today ===')) {
            return $this->extractWorkLogSummary($prompt);
        }

        if (str_contains($promptLower, '=== tasks ===') && str_contains($promptLower, 'blocked:')) {
            return $this->extractTaskSummary($prompt);
        }

        if (str_contains($promptLower, '=== blockers ===')) {
            return $this->extractBlockerSummary($prompt);
        }

        if (str_contains($promptLower, '=== fairness ===')) {
            return $this->extractFairnessSummary($prompt);
        }

        if (str_contains($promptLower, '=== github activity ===')) {
            return $this->extractGithubSummary($prompt);
        }

        if (str_contains($promptLower, 'increment') || str_contains($promptLower, 'score') || str_contains($promptLower, 'salary')) {
            return "Your monthly performance score and recommended increment are calculated based on your daily work log consistency, task completions, and attendance. Visit **My Increment** in the navigation bar to see your itemized breakdown!";
        }

        if (str_contains($promptLower, 'digest') || str_contains($promptLower, 'morning digest') || str_contains($promptLower, 'ceo report')) {
            return "The 30-Second Morning AI Executive Digest compiles your Org Health Score (0-100%), top daily contributors, open blockers, and 1-click action links. It is sent daily to company leaders!";
        }

        return $this->extractGeneralSummary($prompt);
    }

    private function extractHealthSummary(string $prompt): string
    {
        $lines      = explode("\n", $prompt);
        $healthLines = [];
        $inHealth   = false;

        foreach ($lines as $line) {
            if (str_contains($line, '=== SYSTEM HEALTH ===')) { $inHealth = true; continue; }
            if ($inHealth && str_contains($line, '===')) break;
            if ($inHealth && trim($line)) $healthLines[] = trim($line);
        }

        if (empty($healthLines)) {
            return "System health data is being collected. Run `php artisan agent:run` first to populate health checks.";
        }

        $healthy  = count(array_filter($healthLines, fn($l) => str_contains($l, 'healthy')));
        $warnings = count(array_filter($healthLines, fn($l) => str_contains($l, 'warning')));
        $critical = count(array_filter($healthLines, fn($l) => str_contains($l, 'critical')));

        $status   = $critical > 0 ? "⚠️ CRITICAL ISSUES FOUND" : ($warnings > 0 ? "⚠️ Some warnings detected" : "✅ All systems healthy");
        $response = "**System Health Report:**\n\n{$status}\n\n";

        foreach ($healthLines as $line) {
            $icon     = str_contains($line, 'healthy') ? '✅' : (str_contains($line, 'warning') ? '⚠️' : (str_contains($line, 'critical') ? '🔴' : 'ℹ️'));
            $response .= "{$icon} {$line}\n";
        }

        $response .= "\n{$healthy} healthy, {$warnings} warnings, {$critical} critical";
        return $response;
    }

    private function extractWorkLogSummary(string $prompt): string
    {
        preg_match('/logged_today: (.+)/i', $prompt, $logged);
        preg_match('/not_logged_today: (.+)/i', $prompt, $notLogged);
        preg_match('/total_hours_today: (.+)/i', $prompt, $hours);
        preg_match('/today_count: (\d+)/i', $prompt, $count);

        $loggedNames    = trim($logged[1] ?? 'Nobody yet');
        $notLoggedNames = trim($notLogged[1] ?? 'Everyone has logged');
        $totalHours     = trim($hours[1] ?? '0');
        $totalCount     = trim($count[1] ?? '0');

        $response  = "**Work Log Status Today:**\n\n";
        $response .= "📊 Total entries: {$totalCount}\n";
        $response .= "⏱️ Hours tracked: {$totalHours}h\n\n";
        $response .= "✅ **Logged today:** {$loggedNames}\n\n";

        if ($notLoggedNames !== 'Everyone has logged') {
            $response .= "⚠️ **Not logged yet:** {$notLoggedNames}\n\nThe AI agent will send them a reminder.";
        } else {
            $response .= "✅ Everyone has logged their work today!";
        }

        return $response;
    }

    private function extractTaskSummary(string $prompt): string
    {
        preg_match('/Total tasks: (\d+)/i', $prompt, $total);
        preg_match('/In progress: (\d+)/i', $prompt, $inProgress);
        preg_match('/Blocked: (\d+)/i', $prompt, $blocked);
        preg_match('/Done: (\d+)/i', $prompt, $done);
        preg_match('/Overdue: (\d+)/i', $prompt, $overdue);

        return "**Task Summary:**\n\n"
            . "📋 Total: " . ($total[1] ?? 0) . "\n"
            . "🔄 In Progress: " . ($inProgress[1] ?? 0) . "\n"
            . "🚧 Blocked: " . ($blocked[1] ?? 0) . "\n"
            . "✅ Done: " . ($done[1] ?? 0) . "\n"
            . "⚠️ Overdue: " . ($overdue[1] ?? 0);
    }

    private function extractBlockerSummary(string $prompt): string
    {
        preg_match('/Open blockers: (\d+)/i', $prompt, $open);
        preg_match('/Disputed: (\d+)/i', $prompt, $disputed);
        preg_match('/Critical \(7\+ days\): (\d+)/i', $prompt, $critical);

        return "**Blocker Summary:**\n\n"
            . "🚧 Open: " . ($open[1] ?? 0) . "\n"
            . "⚠️ Disputed: " . ($disputed[1] ?? 0) . "\n"
            . "🔴 Critical (7+ days): " . ($critical[1] ?? 0);
    }

    private function extractFairnessSummary(string $prompt): string
    {
        preg_match('/Pending flags: (\d+)/i', $prompt, $pending);
        preg_match('/Confirmed flags: (\d+)/i', $prompt, $confirmed);

        $pendingCount   = (int) ($pending[1] ?? 0);
        $confirmedCount = (int) ($confirmed[1] ?? 0);

        if ($pendingCount === 0 && $confirmedCount === 0) {
            return "✅ **No fairness issues detected.** The team workload appears balanced. Run an analysis to get a fresh check.";
        }

        return "**Fairness Summary:**\n\n"
            . "⚖️ Pending flags: {$pendingCount}\n"
            . "🔴 Confirmed flags: {$confirmedCount}\n\n"
            . ($pendingCount > 0 ? "Review pending flags in the Fairness section." : "");
    }

    private function extractGithubSummary(string $prompt): string
    {
        preg_match('/Commits this week: (\d+)/i', $prompt, $commits);
        preg_match('/PRs this week: (\d+)/i', $prompt, $prs);
        preg_match('/Last synced: (.+)/i', $prompt, $synced);

        return "**GitHub Activity:**\n\n"
            . "📦 Commits this week: " . ($commits[1] ?? 0) . "\n"
            . "🔀 PRs this week: " . ($prs[1] ?? 0) . "\n"
            . "🕐 Last synced: " . trim($synced[1] ?? 'Unknown');
    }

    private function extractGeneralSummary(string $prompt): string
    {
        $lines     = explode("\n", $prompt);
        $dataLines = array_filter($lines, fn($l) => str_contains($l, ':') && !str_contains($l, '===') && trim($l) !== '');

        if (count($dataLines) > 0) {
            $summary = "Based on current system data:\n\n";
            foreach (array_slice(array_values($dataLines), 0, 8) as $line) {
                $summary .= "• " . trim($line) . "\n";
            }
            return $summary;
        }

        return "I have access to your system data but need a more specific question. Try asking about:\n• System health\n• Team performance\n• Work logs today\n• Open blockers\n• Tasks status\n• Fairness issues\n• GitHub activity";
    }

    public function explainFlag(array $evidence, string $flagType): string
    {
        $description = $evidence['description'] ?? 'Anomaly detected';

        $explanations = [
            'workload_imbalance' =>
                "The AI detected an uneven distribution of work. " .
                "{$description}. Review task assignments to ensure " .
                "the workload is distributed fairly across the team.",

            'member_inactive' =>
                "A team member has shown no GitHub activity for 14+ days. " .
                "{$description}. Check if they have blockers, are on leave, " .
                "or need support to get back on track.",

            'quality_imbalance' =>
                "There is a significant gap in contribution quality scores. " .
                "{$description}. This may indicate some members are handling " .
                "more complex work — review task assignments.",

            'unresolved_blocker' =>
                "A team member has been blocked for more than 3 days. " .
                "{$description}. This needs immediate attention to " .
                "prevent delays in project delivery.",

            'meeting_overload' =>
                "A team member has multiple open meeting-type blockers. " .
                "{$description}. Consider reducing meeting overhead " .
                "to allow more productive work time.",
        ];

        return $explanations[$flagType]
            ?? "The AI detected: {$description}. Please review the evidence and take appropriate action.";
    }

    private function assessHealth(array $data): array
    {
        $score = 100;
        $score -= $data['open_blockers'] * 10;
        $score -= $data['pending_flags'] * 15;
        $score -= $data['inactive_count'] * 20;

        if ($score >= 80) return ['status' => 'Good', 'score' => $score];
        if ($score >= 60) return ['status' => 'Fair', 'score' => $score];
        if ($score >= 40) return ['status' => 'Needs Attention', 'score' => $score];
        return ['status' => 'Critical', 'score' => $score];
    }

    private function identifyConcerns(array $data): array
    {
        $concerns = [];

        if ($data['open_blockers'] > 0) {
            $concerns[] = "{$data['open_blockers']} unresolved blocker(s) may be slowing the team down";
        }
        if ($data['pending_flags'] > 0) {
            $concerns[] = "{$data['pending_flags']} fairness issue(s) need leadership review";
        }
        if ($data['inactive_count'] > 0) {
            $concerns[] = "{$data['inactive_count']} member(s) show no recent activity";
        }
        if ($data['commits_this_week'] === 0) {
            $concerns[] = "No commits recorded this week — check if GitHub sync is up to date";
        }

        return $concerns;
    }

    private function generateRecommendation(array $data): string
    {
        if ($data['open_blockers'] > 2) {
            return "Priority: Resolve open blockers immediately. Multiple team members are being slowed down. Schedule a blocker review meeting today.";
        }
        if ($data['pending_flags'] > 0) {
            return "Review pending fairness flags before they escalate. Early intervention prevents team morale issues.";
        }
        if ($data['inactive_count'] > 0) {
            return "Check in with inactive team members directly. They may need support, have blockers, or require clearer task assignments.";
        }
        if ($data['commits_this_week'] > 0) {
            return "Team is active. Consider syncing GitHub more frequently to get real-time insights and run a fairness analysis to ensure balanced workload distribution.";
        }
        return "Connect more GitHub repositories and invite team members to get meaningful AI insights about your team's output.";
    }
}
