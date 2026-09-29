<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\User;
use App\Services\IncrementCalculator;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateMonthlyIncrementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $organizationId, public string $monthDate) {}

    public function handle(IncrementCalculator $calculator): void
    {
        $org = Organization::find($this->organizationId);
        if (!$org) {
            return;
        }

        $month = Carbon::parse($this->monthDate);
        $users = User::where('organization_id', $this->organizationId)
            ->where('is_active', true)
            ->get();

        foreach ($users as $user) {
            $calculator->calculateMonthlyScore($user, $month);
        }
    }
}
