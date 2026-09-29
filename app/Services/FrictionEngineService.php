<?php

namespace App\Services;

use App\Models\AgentNotification;
use App\Models\Blocker;
use App\Models\Organization;
use App\Models\User;

class FrictionEngineService
{
    /**
     * Audit cross-department dependencies and detect silent friction / ghosting
     */
    public function auditCrossDepartmentFriction(Organization $org): array
    {
        $orgId = $org->id;
        $openBlockers = Blocker::where('organization_id', $orgId)
            ->open()
            ->with(['blockedUser', 'blockingUser'])
            ->get();

        $frictionEvents = [];

        foreach ($openBlockers as $blocker) {
            $blockedUser  = $blocker->blockedUser;
            $blockingUser = $blocker->blockingUser;

            if (!$blockedUser || !$blockingUser) {
                continue;
            }

            // Check if users belong to different departments
            $deptA = $blockedUser->department ?? 'General';
            $deptB = $blockingUser->department ?? 'General';

            if (strtolower($deptA) !== strtolower($deptB)) {
                $created = $blocker->created_at;
                $hoursDelayed = max(1, (int) now()->diffInHours($created));

                if ($hoursDelayed >= 24) {
                    // Estimated financial delay cost: hoursDelayed * average hourly rate (e.g. ₹500/hr)
                    $hourlyRate = 500.00;
                    $estimatedCost = round($hoursDelayed * $hourlyRate, 2);

                    $blocker->update([
                        'delay_hours'          => $hoursDelayed,
                        'estimated_delay_cost' => $estimatedCost,
                    ]);

                    $frictionEvents[] = [
                        'blocker_id'      => $blocker->id,
                        'title'           => $blocker->title,
                        'dept_blocked'    => $deptA,
                        'dept_blocking'   => $deptB,
                        'blocked_user'    => $blockedUser->name,
                        'blocking_user'   => $blockingUser->name,
                        'hours_delayed'   => $hoursDelayed,
                        'estimated_cost'  => $estimatedCost,
                    ];

                    // Send resolution alert to both team leads/managers
                    $this->notifyTeamLeads($orgId, $blockedUser, $blockingUser, $blocker, $hoursDelayed, $estimatedCost);
                }
            }
        }

        return $frictionEvents;
    }

    private function notifyTeamLeads(int $orgId, User $blockedUser, User $blockingUser, Blocker $blocker, int $hours, float $cost): void
    {
        $managerA = $blockedUser->reportingManager;
        $managerB = $blockingUser->reportingManager;

        $message = "⚠️ Silent Cross-Department Friction: Department {$blockedUser->department} is waiting on {$blockingUser->department} ({$hours}h delay). Estimated cost: ₹" . number_format($cost);

        if ($managerA) {
            AgentNotification::firstOrCreate([
                'user_id'         => $managerA->id,
                'organization_id' => $orgId,
                'type'            => 'friction_alert',
                'title'           => 'Cross-Dept Friction Alert 👻',
                'message'         => $message,
            ]);
        }

        if ($managerB) {
            AgentNotification::firstOrCreate([
                'user_id'         => $managerB->id,
                'organization_id' => $orgId,
                'type'            => 'friction_alert',
                'title'           => 'Cross-Dept Friction Alert 👻',
                'message'         => $message,
            ]);
        }
    }
}
