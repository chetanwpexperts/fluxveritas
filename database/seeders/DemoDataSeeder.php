<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = 1;
        $year  = now()->year;

        // ── People ──────────────────────────────────────────────────────────────
        $rahul   = User::where('email', 'manager@techcorp.com')->first();
        $priya   = User::where('email', 'teamlead@techcorp.com')->first();
        $alex    = User::where('email', 'alex@techcorp.com')->first();
        $sarah   = User::where('email', 'sarah@techcorp.com')->first();
        $marcus  = User::where('email', 'dev.marcus@techcorp.com')->first();
        $devansh = User::where('email', 'chetanwpexperts@gmail.com')->first();

        if (!$rahul || !$priya) {
            $this->command->error('Run DemoTeamSeeder first.');
            return;
        }

        $members = collect([$alex, $sarah, $marcus, $devansh])->filter();
        $deptId  = $priya->department_id ?? 1;

        // First project in org (required FK for tasks + blockers)
        $project = Project::where('organization_id', $orgId)->first();
        if (!$project) {
            $this->command->error('No project found for org 1. Create a project first.');
            return;
        }
        $projectId = $project->id;

        // ── Leave types ─────────────────────────────────────────────────────────
        $leaveTypes = DB::table('leave_types')
            ->where('organization_id', $orgId)
            ->where('is_active', 1)
            ->get();

        if ($leaveTypes->isEmpty()) {
            $this->command->warn('No leave types found — skipping leave data. Go to Leaves → Settings to create types first.');
        }

        $casual = $leaveTypes->firstWhere('code', 'CL') ?? $leaveTypes->first();
        $sick   = $leaveTypes->firstWhere('code', 'SL') ?? $leaveTypes->first();
        $annual = $leaveTypes->firstWhere('code', 'AL') ?? $leaveTypes->first();

        // ── 1. LEAVE BALANCES ───────────────────────────────────────────────────
        $allMembers = $members->push($priya);
        foreach ($allMembers as $u) {
            foreach ($leaveTypes as $lt) {
                DB::table('leave_balances')->updateOrInsert(
                    [
                        'user_id'       => $u->id,
                        'leave_type_id' => $lt->id,
                        'year'          => $year,
                    ],
                    [
                        'organization_id' => $orgId,
                        'allocated'       => $lt->days_per_year,
                        'used'            => 0,
                        'pending'         => 0,
                        'carried_forward' => 0,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ]
                );
            }
        }
        $this->command->info('Leave balances seeded.');

        // ── 2. LEAVE APPLICATIONS ───────────────────────────────────────────────
        // Clear old demo leave applications for these members to avoid duplicates
        $memberIds = $allMembers->pluck('id');
        DB::table('leave_applications')
            ->whereIn('user_id', $memberIds)
            ->where('organization_id', $orgId)
            ->whereIn('status', ['pending', 'approved', 'rejected'])
            ->where('created_at', '>=', now()->subDays(30))
            ->delete();

        $leaves = [];

        // Helper to build a fully-keyed leave row (all optional columns explicit)
        $leaveRow = fn(array $data) => array_merge([
            'user_id'         => null,
            'leave_type_id'   => null,
            'organization_id' => $orgId,
            'from_date'       => null,
            'to_date'         => null,
            'days'            => 1,
            'reason'          => '',
            'status'          => 'pending',
            'reviewed_by'     => null,
            'reviewer_note'   => null,
            'reviewed_at'     => null,
            'is_half_day'     => false,
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $data);

        // Pending — Alex, casual leave
        if ($casual && $alex) {
            $leaves[] = $leaveRow([
                'user_id'       => $alex->id,
                'leave_type_id' => $casual->id,
                'from_date'     => now()->addDays(3)->toDateString(),
                'to_date'       => now()->addDays(4)->toDateString(),
                'days'          => 2,
                'reason'        => 'Family function out of town',
                'created_at'    => now()->subDay(),
                'updated_at'    => now()->subDay(),
            ]);
        }

        // Pending — Sarah, sick leave
        if ($sick && $sarah) {
            $leaves[] = $leaveRow([
                'user_id'       => $sarah->id,
                'leave_type_id' => $sick->id,
                'from_date'     => now()->addDays(1)->toDateString(),
                'to_date'       => now()->addDays(1)->toDateString(),
                'days'          => 1,
                'reason'        => 'Feeling unwell, will rest at home',
            ]);
        }

        // Approved — Marcus, sick leave 10 days ago
        if ($sick && $marcus) {
            $leaves[] = $leaveRow([
                'user_id'       => $marcus->id,
                'leave_type_id' => $sick->id,
                'from_date'     => now()->subDays(10)->toDateString(),
                'to_date'       => now()->subDays(10)->toDateString(),
                'days'          => 1,
                'reason'        => 'Fever',
                'status'        => 'approved',
                'reviewed_by'   => $priya->id,
                'reviewer_note' => 'Get well soon',
                'reviewed_at'   => now()->subDays(11),
                'created_at'    => now()->subDays(12),
                'updated_at'    => now()->subDays(11),
            ]);
        }

        // Rejected — Devansh, casual leave during sprint
        if ($casual && $devansh) {
            $leaves[] = $leaveRow([
                'user_id'       => $devansh->id,
                'leave_type_id' => $casual->id,
                'from_date'     => now()->subDays(5)->toDateString(),
                'to_date'       => now()->subDays(3)->toDateString(),
                'days'          => 3,
                'reason'        => 'Personal work',
                'status'        => 'rejected',
                'reviewed_by'   => $priya->id,
                'reviewer_note' => 'Critical sprint week — please reschedule',
                'reviewed_at'   => now()->subDays(6),
                'created_at'    => now()->subDays(7),
                'updated_at'    => now()->subDays(6),
            ]);
        }

        if (!empty($leaves)) {
            DB::table('leave_applications')->insert($leaves);
        }
        $this->command->info('Leave applications seeded (' . count($leaves) . ').');

        // ── 3. WORK LOGS ────────────────────────────────────────────────────────
        // Clear today's logs for these members first
        DB::table('work_logs')
            ->whereIn('user_id', $memberIds)
            ->where('log_date', now()->toDateString())
            ->delete();

        // Alex + Marcus logged today; Sarah + Devansh did NOT
        $loggedToday = collect([$alex, $marcus])->filter();
        foreach ($loggedToday as $u) {
            DB::table('work_logs')->insert([
                'user_id'          => $u->id,
                'organization_id'  => $orgId,
                'department_id'    => $deptId,
                'project_id'       => $projectId,
                'log_date'         => now()->toDateString(),
                'category'         => 'development',
                'title'            => 'Feature implementation and code review',
                'description'      => 'Built and tested new module endpoints. Reviewed 2 PRs.',
                'duration_minutes' => rand(300, 420),
                'output_value'     => rand(5, 8),
                'is_billable'      => true,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // Historical logs — last 14 weekdays, 80 % chance each day
        $categories = ['development', 'review', 'meetings', 'planning', 'testing'];
        $titles     = [
            'development' => 'Feature development and testing',
            'review'      => 'Code review and PR feedback',
            'meetings'    => 'Sprint planning and standup',
            'planning'    => 'Technical design and estimation',
            'testing'     => 'QA and bug investigation',
        ];

        $historyLogs = [];
        foreach ($members as $u) {
            for ($d = 1; $d <= 14; $d++) {
                $date = now()->subDays($d);
                if (in_array($date->dayOfWeek, [0, 6])) {
                    continue; // skip weekends
                }
                if (rand(1, 10) > 8) {
                    continue; // 20 % absent
                }
                $cat = $categories[array_rand($categories)];
                $historyLogs[] = [
                    'user_id'          => $u->id,
                    'organization_id'  => $orgId,
                    'department_id'    => $deptId,
                    'project_id'       => $projectId,
                    'log_date'         => $date->toDateString(),
                    'category'         => $cat,
                    'title'            => $titles[$cat],
                    'description'      => 'Daily work log entry.',
                    'duration_minutes' => rand(240, 480),
                    'output_value'     => rand(3, 9),
                    'is_billable'      => true,
                    'created_at'       => $date,
                    'updated_at'       => $date,
                ];
            }
        }

        if (!empty($historyLogs)) {
            DB::table('work_logs')->insert($historyLogs);
        }
        $this->command->info('Work logs seeded (' . (count($historyLogs) + $loggedToday->count()) . ' entries).');

        // ── 4. TASKS ────────────────────────────────────────────────────────────
        // Remove previous demo tasks assigned by Priya to avoid duplicates
        DB::table('tasks')
            ->where('assigned_by', $priya->id)
            ->whereIn('assigned_to', $members->pluck('id')->toArray())
            ->where('created_at', '>=', now()->subDays(30))
            ->delete();

        $taskPool = [
            ['title' => 'Build user authentication API',          'type' => 'feature',     'difficulty' => 7],
            ['title' => 'Fix dashboard pagination bug',           'type' => 'bug',         'difficulty' => 4],
            ['title' => 'Write unit tests for payment module',    'type' => 'task',        'difficulty' => 5],
            ['title' => 'Refactor notification service',          'type' => 'improvement', 'difficulty' => 6],
            ['title' => 'Design report export schema',            'type' => 'task',        'difficulty' => 5],
            ['title' => 'Optimize slow database queries',         'type' => 'improvement', 'difficulty' => 8],
            ['title' => 'Update API documentation',               'type' => 'task',        'difficulty' => 2],
            ['title' => 'Code review for PR #58',                 'type' => 'task',        'difficulty' => 3],
            ['title' => 'Implement file upload feature',          'type' => 'feature',     'difficulty' => 7],
            ['title' => 'Fix mobile responsive layout issues',    'type' => 'bug',         'difficulty' => 4],
            ['title' => 'Add email notification triggers',        'type' => 'feature',     'difficulty' => 6],
            ['title' => 'Migrate legacy config to env vars',      'type' => 'improvement', 'difficulty' => 3],
            ['title' => 'Set up CI/CD pipeline for staging',      'type' => 'task',        'difficulty' => 8],
            ['title' => 'Audit and fix XSS vulnerabilities',      'type' => 'bug',         'difficulty' => 9],
            ['title' => 'Implement rate limiting middleware',      'type' => 'feature',     'difficulty' => 6],
            ['title' => 'Review and close stale issues',          'type' => 'task',        'difficulty' => 2],
        ];

        $statuses   = ['todo', 'in_progress', 'in_progress', 'done', 'done'];
        $priorities = ['low', 'medium', 'medium', 'high', 'critical'];
        $tasks      = [];
        $taskIdx    = 0;

        foreach ($members as $u) {
            for ($t = 0; $t < 4; $t++) {
                $pool   = $taskPool[$taskIdx % count($taskPool)];
                $status = $statuses[array_rand($statuses)];
                $prio   = $priorities[array_rand($priorities)];
                $taskIdx++;
                $tasks[] = [
                    'ticket_number'   => 'TXY-' . str_pad($taskIdx, 3, '0', STR_PAD_LEFT),
                    'project_id'      => $projectId,
                    'assigned_to'     => $u->id,
                    'assigned_by'     => $priya->id,
                    'reporter_id'     => $priya->id,
                    'title'           => $pool['title'],
                    'type'            => $pool['type'],
                    'description'     => 'Demo task for Team XYZ.',
                    'difficulty'      => $pool['difficulty'],
                    'visibility_score'=> rand(3, 9),
                    'status'          => $status,
                    'priority'        => $prio,
                    'department_id'   => $deptId,
                    'started_at'      => $status !== 'todo'  ? now()->subDays(rand(2, 7)) : null,
                    'completed_at'    => $status === 'done'  ? now()->subDays(rand(0, 3)) : null,
                    'completed_by'    => $status === 'done'  ? $u->id : null,
                    'due_date'        => now()->addDays(rand(1, 14))->toDateString(),
                    'estimated_hours' => rand(2, 16),
                    'actual_hours'    => $status === 'done' ? rand(2, 20) : null,
                    'created_at'      => now()->subDays(rand(5, 15)),
                    'updated_at'      => now(),
                ];
            }
        }

        DB::table('tasks')->insert($tasks);
        $this->command->info('Tasks seeded (' . count($tasks) . ').');

        // ── 5. BLOCKERS ─────────────────────────────────────────────────────────
        // Remove previous demo blockers to avoid duplicates
        DB::table('blockers')
            ->whereIn('reported_by', $memberIds)
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(7))
            ->delete();

        $blockers = [];

        $blockerRow = fn(array $data) => array_merge([
            'organization_id'      => $orgId,
            'project_id'           => $projectId,
            'reported_by'          => null,
            'blocked_user_id'      => null,
            'blocking_user_id'     => null,
            'external_person_name' => null,
            'blocker_type'         => 'internal_person',
            'title'                => '',
            'description'          => '',
            'status'               => 'open',
            'priority'             => 'medium',
            'impact_level'         => 'just_me',
            'created_at'           => now(),
            'updated_at'           => now(),
        ], $data);

        if ($alex && $sarah) {
            $blockers[] = $blockerRow([
                'reported_by'      => $alex->id,
                'blocked_user_id'  => $alex->id,
                'blocking_user_id' => $sarah->id,
                'blocker_type'     => 'internal_person',
                'title'            => 'Waiting on API contract from Sarah',
                'description'      => 'Cannot finish the integration module until Sarah finalises the API spec. Dependency raised in standup twice.',
                'priority'         => 'high',
                'impact_level'     => 'team',
                'created_at'       => now()->subDays(2),
                'updated_at'       => now()->subDays(2),
            ]);
        }

        if ($marcus) {
            $blockers[] = $blockerRow([
                'reported_by'          => $marcus->id,
                'blocked_user_id'      => $marcus->id,
                'external_person_name' => 'Client IT Team',
                'blocker_type'         => 'external_vendor',
                'title'                => 'Waiting on production server access from client',
                'description'          => 'Client IT has not granted deployment credentials for the staging environment. Deployment is blocked.',
                'priority'             => 'medium',
                'impact_level'         => 'just_me',
                'created_at'           => now()->subDays(1),
                'updated_at'           => now()->subDays(1),
            ]);
        }

        if (!empty($blockers)) {
            DB::table('blockers')->insert($blockers);
        }
        $this->command->info('Blockers seeded (' . count($blockers) . ').');

        $this->command->info('');
        $this->command->info('DemoDataSeeder complete:');
        $this->command->info('  Leave balances : ' . ($allMembers->count() * $leaveTypes->count()) . ' rows');
        $this->command->info('  Leave apps     : ' . count($leaves) . ' (2 pending, 1 approved, 1 rejected)');
        $this->command->info('  Work logs      : Alex + Marcus logged today; Sarah + Devansh did not');
        $this->command->info('  Tasks          : ' . count($tasks) . ' across 4 members');
        $this->command->info('  Blockers       : ' . count($blockers) . ' open');
    }
}
