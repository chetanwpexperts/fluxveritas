<?php

namespace App\Services;

use App\Models\PeerFeedback;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;

class PeerFeedbackService
{
    public function selectPeers(int $employeeId, int $orgId, string $period, int $year): array
    {
        $dateRange = $this->getPeriodDates($period, $year);

        // Peers from tasks where employee was assigned_to or assigned_by
        $taskPeerIds = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->whereBetween('created_at', $dateRange)
            ->where('assigned_to', '!=', $employeeId)
            ->whereNotNull('assigned_to')
            ->where(function ($q) use ($employeeId) {
                $q->where('assigned_by', $employeeId)
                  ->orWhereHas('assignee', fn($u) => $u->where('id', $employeeId));
            })
            ->pluck('assigned_to');

        $taskPeerIds2 = Task::whereHas('project', fn($q) => $q->where('organization_id', $orgId))
            ->whereBetween('created_at', $dateRange)
            ->where('assigned_to', $employeeId)
            ->whereNotNull('assigned_by')
            ->where('assigned_by', '!=', $employeeId)
            ->pluck('assigned_by');

        // Peers from shared sprints
        $sprintPeerIds = collect();
        $employeeSprints = Sprint::where('organization_id', $orgId)
            ->whereHas('tasks', fn($q) => $q->where('assigned_to', $employeeId))
            ->whereBetween('created_at', $dateRange)
            ->pluck('id');

        if ($employeeSprints->isNotEmpty()) {
            $sprintPeerIds = Task::whereIn('sprint_id', $employeeSprints)
                ->where('assigned_to', '!=', $employeeId)
                ->whereNotNull('assigned_to')
                ->pluck('assigned_to')
                ->unique();
        }

        $allPeerIds = $taskPeerIds
            ->merge($taskPeerIds2)
            ->merge($sprintPeerIds)
            ->unique()
            ->filter(fn($id) => $id !== $employeeId)
            ->take(4)
            ->values()
            ->toArray();

        // Fallback: same-department peers if not enough found
        if (count($allPeerIds) < 2) {
            $employee   = User::find($employeeId);
            $extraPeers = User::where('organization_id', $orgId)
                ->where('is_active', true)
                ->where('id', '!=', $employeeId)
                ->when($employee?->department_id, fn($q) =>
                    $q->where('department_id', $employee->department_id)
                )
                ->whereNotIn('id', $allPeerIds)
                ->inRandomOrder()
                ->limit(2 - count($allPeerIds))
                ->pluck('id')
                ->toArray();

            $allPeerIds = array_merge($allPeerIds, $extraPeers);
        }

        return $allPeerIds;
    }

    public function createRequests(int $employeeId, int $orgId, string $period, int $year): array
    {
        $peerIds = $this->selectPeers($employeeId, $orgId, $period, $year);
        $created = [];

        foreach ($peerIds as $peerId) {
            $exists = PeerFeedback::where('employee_id', $employeeId)
                ->where('reviewer_id', $peerId)
                ->where('review_period', $period)
                ->where('review_year', $year)
                ->exists();

            if ($exists) continue;

            $created[] = PeerFeedback::create([
                'employee_id'      => $employeeId,
                'reviewer_id'      => $peerId,
                'organization_id'  => $orgId,
                'review_period'    => $period,
                'review_year'      => $year,
                'token'            => Str::random(32),
                'token_expires_at' => now()->addDays(5),
                'is_submitted'     => false,
            ]);
        }

        return $created;
    }

    private function getPeriodDates(string $period, int $year): array
    {
        return match ($period) {
            'Q1'    => ["{$year}-01-01", "{$year}-03-31"],
            'Q2'    => ["{$year}-04-01", "{$year}-06-30"],
            'Q3'    => ["{$year}-07-01", "{$year}-09-30"],
            'Q4'    => ["{$year}-10-01", "{$year}-12-31"],
            default => ["{$year}-01-01", "{$year}-12-31"],
        };
    }
}
