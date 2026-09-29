<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class EmailDigestService
{
    private ReportService $reportService;
    private EmailService  $emailService;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->emailService  = new EmailService();
    }

    public function sendCeoDailyDigests(): void
    {
        $orgs = Organization::where('status', 'active')->get();

        foreach ($orgs as $org) {
            $ceo = User::where('organization_id', $org->id)
                ->where('is_active', true)
                ->whereHas('roles', fn($q) => $q->whereIn('name', ['owner', 'ceo', 'admin']))
                ->first();

            if (!$ceo) continue;

            try {
                $data    = $this->reportService->getCeoDailyDigest($org->id);
                $subject = $this->getCeoSubject($data);
                $body    = $this->buildCeoEmailBody($ceo, $org, $data);

                $this->emailService->sendAgentEmail(
                    $ceo->email, $ceo->name, $subject, $body,
                    url('/reports/ceo'), 'View Dashboard'
                );
            } catch (\Exception $e) {
                Log::error("CEO digest failed for org {$org->id}: " . $e->getMessage());
            }
        }
    }

    public function sendManagerDailyReports(): void
    {
        $managers = User::where('is_active', true)
            ->whereHas('roles', fn($q) => $q->whereIn('name', ['admin', 'team_lead', 'manager']))
            ->whereHas('directReports')
            ->get();

        foreach ($managers as $manager) {
            try {
                $data = $this->reportService->getManagerDailyReport($manager);
                if (empty($data)) continue;

                $subject = "☀️ Your Team Report — " . now()->format('M j, Y');
                $body    = $this->buildManagerEmailBody($manager, $data);

                $this->emailService->sendAgentEmail(
                    $manager->email, $manager->name, $subject, $body,
                    url('/team'), 'View Team'
                );
            } catch (\Exception $e) {
                Log::error("Manager report failed for {$manager->id}: " . $e->getMessage());
            }
        }
    }

    private function getCeoSubject(array $data): string
    {
        $icon       = match($data['status']) { 'healthy' => '🟢', 'attention' => '🟡', 'critical' => '🔴', default => '📊' };
        $alertCount = count($data['alerts']);
        return $alertCount > 0
            ? "{$icon} OutraqHQ Daily — {$alertCount} alert(s) need attention"
            : "{$icon} OutraqHQ Daily — Team looking good today!";
    }

    private function buildCeoEmailBody(User $ceo, Organization $org, array $data): string
    {
        $alertsText   = '';
        foreach ($data['alerts'] as $alert) {
            $alertsText .= "• {$alert['icon']} {$alert['message']}\n";
        }
        $notLoggedStr = !empty($data['not_logged_names'])
            ? implode(', ', $data['not_logged_names']) . ($data['not_logged_count'] > 5 ? ' and more' : '')
            : 'Everyone logged in!';
        $healthEmoji  = match($data['status']) { 'healthy' => '💚', 'attention' => '💛', 'critical' => '❤️', default => '📊' };

        return "Good morning {$ceo->name}! 👋\n\n"
            . "Here's your OutraqHQ daily summary for {$data['date']}:\n\n"
            . "TEAM HEALTH {$healthEmoji}\n" . str_repeat('─', 32) . "\n"
            . "Health Score:     {$data['health_score']}/100\n"
            . "Team Size:        {$data['total_employees']} employees\n"
            . "Logged Today:     {$data['logged_today']}/{$data['total_employees']}\n"
            . "Tasks Completed:  {$data['tasks_completed']} today\n"
            . "Active Sprints:   {$data['active_sprints']}\n\n"
            . "ATTENTION NEEDED\n" . str_repeat('─', 32) . "\n"
            . "Open Blockers:    {$data['open_blockers']}\n"
            . "Critical Alerts:  {$data['critical_blockers']}\n"
            . "Fairness Flags:   {$data['fairness_flags']}\n"
            . "Bias Reports:     {$data['bias_reports']} unreviewed\n"
            . "Overloaded:       {$data['overloaded_count']} employees\n\n"
            . "NOT LOGGED TODAY\n" . str_repeat('─', 32) . "\n"
            . $notLoggedStr . "\n\n"
            . ($alertsText ? "ALERTS\n" . str_repeat('─', 32) . "\n{$alertsText}\n" : "✅ No critical alerts today!\n\n")
            . "This is an automated daily digest from OutraqHQ.\nPowered by AI — Building fair workplaces.";
    }

    private function buildManagerEmailBody(User $manager, array $data): string
    {
        $notLoggedStr    = !empty($data['not_logged_names']) ? implode(', ', $data['not_logged_names']) : '✅ Everyone logged!';
        $overloadedStr   = !empty($data['overloaded_names']) ? '⚠️ ' . implode(', ', $data['overloaded_names']) : '✅ No overloaded members';
        $pendingFeedback = !empty($data['pending_feedback']) ? implode(', ', $data['pending_feedback']) : '✅ All feedback submitted';

        return "Good morning {$manager->name}! ☀️\n\n"
            . "Your team report for {$data['date']}:\n\n"
            . "YOUR TEAM TODAY\n" . str_repeat('─', 32) . "\n"
            . "Team Size:        {$data['total_reports']} members\n"
            . "Logged Today:     {$data['logged_today']}/{$data['total_reports']}\n"
            . "Tasks Completed:  {$data['tasks_done_today']} today\n"
            . "Open Blockers:    {$data['open_blockers']}\n\n"
            . "NOT LOGGED TODAY\n" . str_repeat('─', 32) . "\n"
            . $notLoggedStr . "\n\n"
            . "WORKLOAD\n" . str_repeat('─', 32) . "\n"
            . $overloadedStr . "\n\n"
            . "PENDING FEEDBACK ({$data['period']})\n" . str_repeat('─', 32) . "\n"
            . $pendingFeedback . "\n\n"
            . "YOUR ACCOUNTABILITY SCORE\n" . str_repeat('─', 32) . "\n"
            . "{$data['accountability_score']}/100\n"
            . ($data['accountability_score'] < 80
                ? "⚠️ Submit pending feedback to improve your score."
                : "✅ Great accountability this quarter!") . "\n\n"
            . "OutraqHQ — AI-Powered Human Intelligence Platform";
    }
}
