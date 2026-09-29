<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\BillingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPlanExpirationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'org:check-expirations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check expired organization plans and automatically revert them to Free tier';

    /**
     * Execute the console command.
     */
    public function handle(BillingService $billing): int
    {
        $expiredOrgs = Organization::where('plan', '!=', 'free')
            ->whereNotNull('plan_expires_at')
            ->where('plan_expires_at', '<', now())
            ->get();

        if ($expiredOrgs->isEmpty()) {
            $this->info('No expired organization plans found.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($expiredOrgs as $org) {
            $previousPlan = $org->plan;
            $expiredAt    = $org->plan_expires_at;
            $wasScheduled = $org->downgrade_scheduled_at !== null;

            // A downgrade the owner asked for is a normal move to Free, not an expiry
            $billing->resetToFree($org, $wasScheduled ? 'free' : 'expired');

            Log::info("Organization plan expired — reverted from {$previousPlan} to free", [
                'org_id'   => $org->id,
                'org_name' => $org->name,
                'expired'  => $expiredAt,
                'scheduled_downgrade' => $wasScheduled,
            ]);

            $count++;
        }

        $this->info("Successfully reverted {$count} expired organization(s) to Free tier.");
        return Command::SUCCESS;
    }
}
