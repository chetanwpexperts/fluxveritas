<?php

namespace Database\Seeders;

use App\Models\AgentNotification;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding agent notifications...');

        $org = Organization::first();
        if (!$org) {
            $this->command->error('No organization found.');
            return;
        }

        $owner   = User::where('organization_id', $org->id)->whereIn('role', ['owner', 'admin'])->first();
        $personA = User::where('email', 'alex@techcorp.com')->first();
        $personB = User::where('email', 'sarah@techcorp.com')->first();

        if (!$owner) {
            $this->command->error('No owner/admin found.');
            return;
        }

        // Delete existing seeded notifications to allow fresh generation
        AgentNotification::where('organization_id', $org->id)->delete();

        // Critical — dispute unresolved for owner
        if ($personB) {
            AgentNotification::create([
                'organization_id'       => $org->id,
                'user_id'               => $owner->id,
                'triggered_for_user_id' => $personB->id,
                'notification_type'     => 'dispute_unresolved',
                'title'                 => '🚨 Ownership dispute unresolved 5 days',
                'message'               => "URGENT: Sarah Singh has been blocked by Alex Kumar for 5 days with an unresolved ownership dispute. Blocker: API specs needed before payment integration. This requires your immediate attention.",
                'action_url'            => '/dependencies',
                'action_label'          => 'Resolve Now',
                'priority'              => 'critical',
                'is_read'               => false,
            ]);
        }

        // High — fairness alert for owner
        AgentNotification::create([
            'organization_id' => $org->id,
            'user_id'         => $owner->id,
            'notification_type' => 'fairness_alert',
            'title'           => '⚖️ Fairness Analysis: 3 issues detected',
            'message'         => "Assignment bias detected. Sarah Singh consistently receives harder tasks (avg difficulty 9/10) while Alex Kumar receives easy tasks (avg difficulty 1.7/10). 94% confidence level.",
            'action_url'      => '/fairness',
            'action_label'    => 'Review Flags',
            'priority'        => 'high',
            'is_read'         => false,
        ]);

        // Normal — work log reminder for Alex
        if ($personA) {
            AgentNotification::create([
                'organization_id'   => $org->id,
                'user_id'           => $personA->id,
                'notification_type' => 'work_log_reminder',
                'title'             => '📝 Daily Work Log Missing',
                'message'           => "Hey Alex! You have not logged your work activities for today. Take 2 minutes to log what you have been working on.",
                'action_url'        => '/work-log/today',
                'action_label'      => "Log Today's Work",
                'priority'          => 'normal',
                'is_read'           => false,
            ]);
        }

        // High — blocker escalation for owner
        AgentNotification::create([
            'organization_id'   => $org->id,
            'user_id'           => $owner->id,
            'notification_type' => 'blocker_escalation',
            'title'             => '⏰ Blocker open for 14 days',
            'message'           => "Code review pending from Alex has been open for 14 days. Sarah Singh is blocked. Immediate action required.",
            'action_url'        => '/dependencies',
            'action_label'      => 'View Blocker',
            'priority'          => 'high',
            'is_read'           => false,
        ]);

        // Low — morning digest for owner
        AgentNotification::create([
            'organization_id'   => $org->id,
            'user_id'           => $owner->id,
            'notification_type' => 'daily_digest',
            'title'             => '🌅 Good Morning — Team Summary',
            'message'           => "Active members: 5 (1 on leave).\nOpen blockers: 3.\nPending fairness flags: 3.\nYesterday work logs: 12 entries.",
            'action_url'        => '/ceo',
            'action_label'      => 'View Command Center',
            'priority'          => 'low',
            'is_read'           => false,
        ]);

        $this->command->info('Agent notifications seeded: ' . AgentNotification::where('organization_id', $org->id)->count() . ' total.');
    }
}
