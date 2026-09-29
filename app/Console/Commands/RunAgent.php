<?php

namespace App\Console\Commands;

use App\Services\AutonomousAgent;
use Illuminate\Console\Command;

class RunAgent extends Command
{
    protected $signature = 'agent:run {--org= : Run for specific org ID}';

    protected $description = 'Run the OutraqHQ autonomous AI agent + system health checks';

    public function handle(): void
    {
        $this->info('🤖 OutraqHQ System Guardian Starting...');
        $this->newLine();

        $agent = new AutonomousAgent();

        // Team checks
        $this->info('📋 Running team checks...');
        $results = $agent->run();

        // run() now calls checkSystemHealth() + clearStaleCache() internally.
        // Pull cached results for the summary.
        $health   = \Illuminate\Support\Facades\Cache::get('system_health', []);
        $security = \Illuminate\Support\Facades\Cache::get('security_scan', []);

        $teamActions = array_filter($results, fn($r) => $r['type'] !== 'system_health');
        $this->info(count($teamActions) . ' team action(s) taken.');
        foreach ($teamActions as $result) {
            $this->line("  [{$result['type']}] {$result['message']}");
        }

        $this->newLine();

        // Health summary
        $this->info('🏥 System Health:');
        $healthy  = count(array_filter($health, fn($h) => ($h['status'] ?? '') === 'healthy'));
        $warnings = count(array_filter($health, fn($h) => ($h['status'] ?? '') === 'warning'));
        $critical = count(array_filter($health, fn($h) => ($h['status'] ?? '') === 'critical'));

        $this->line("  ✅ Healthy:  {$healthy}");
        $this->line("  ⚠️  Warnings: {$warnings}");

        if ($critical > 0) {
            $this->error("  🚨 Critical: {$critical}");
            foreach ($health as $name => $check) {
                if (($check['status'] ?? '') === 'critical') {
                    $this->error("    [{$name}] " . ($check['message'] ?? ''));
                }
            }
        }

        foreach ($health as $name => $check) {
            if (($check['status'] ?? '') === 'warning') {
                $this->warn("  [{$name}] " . ($check['message'] ?? ''));
            }
        }

        // Security summary
        if (!empty($security)) {
            $critSec = count(array_filter($security, fn($i) => $i['severity'] === 'critical'));
            $this->newLine();
            $this->info('🔐 Security Scan: ' . count($security) . ' issue(s) found' . ($critSec > 0 ? " ({$critSec} critical)" : ''));
            foreach ($security as $issue) {
                $fn = $issue['severity'] === 'critical' ? 'error' : 'warn';
                $this->{$fn}("  [{$issue['severity']}] {$issue['issue']}");
            }
        }

        $this->newLine();
        $this->info('✅ Agent run complete! View details at /agent/health');
    }
}
