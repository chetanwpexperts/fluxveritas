<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Project;
use App\Models\Team;

class MasterDemoSeeder extends Seeder
{
    public function run(): void
    {
        $orgId = 1;
        $year  = now()->year;

        // ── People ─────────────────────────────────────────────────────────────
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

        // ── Project + sprint (reuse existing) ──────────────────────────────────
        $project = Project::where('organization_id', $orgId)->first();
        if (!$project) {
            $this->command->error('No project found for org 1. Create one first.');
            return;
        }
        $projectId = $project->id;

        $sprint = DB::table('sprints')
            ->where('organization_id', $orgId)
            ->where('status', 'active')
            ->first()
            ?? DB::table('sprints')->where('organization_id', $orgId)->first();

        if (!$sprint) {
            $sid = DB::table('sprints')->insertGetId([
                'organization_id' => $orgId,
                'project_id'      => $projectId,
                'name'            => 'Sprint 1 — Demo',
                'goal'            => 'Ship core features',
                'status'          => 'active',
                'start_date'      => now()->subDays(7)->toDateString(),
                'end_date'        => now()->addDays(7)->toDateString(),
                'created_by'      => $priya->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $sprint = DB::table('sprints')->find($sid);
        }
        $sprintId = $sprint->id;

        // ── Leave types ────────────────────────────────────────────────────────
        $leaveTypes = DB::table('leave_types')
            ->where('organization_id', $orgId)
            ->where('is_active', 1)
            ->get();

        if ($leaveTypes->isEmpty()) {
            $this->command->warn('No leave types found — go to Leaves → Settings to create them first.');
        }

        $casual = $leaveTypes->firstWhere('code', 'CL') ?? $leaveTypes->first();
        $sick   = $leaveTypes->firstWhere('code', 'SL') ?? $leaveTypes->first();

        // ── Increment policy ───────────────────────────────────────────────────
        $policyId = DB::table('increment_policies')
            ->where('organization_id', $orgId)
            ->value('id');

        // ── Team XYZ id ────────────────────────────────────────────────────────
        $teamXyz = Team::where('organization_id', $orgId)->where('slug', 'team-xyz')->first();
        $teamId  = $teamXyz?->id;

        // ── Helper: row template with all keys explicit ─────────────────────────
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

        // ══════════════════════════════════════════════════════════════════
        // 1. LEAVE BALANCES
        // ══════════════════════════════════════════════════════════════════
        $allMembers = $members->push($priya);
        foreach ($allMembers as $u) {
            foreach ($leaveTypes as $lt) {
                DB::table('leave_balances')->updateOrInsert(
                    ['user_id' => $u->id, 'leave_type_id' => $lt->id, 'year' => $year],
                    [
                        'organization_id' => $orgId,
                        'allocated'       => $lt->days_per_year,
                        'used'            => rand(0, 3),
                        'pending'         => 0,
                        'carried_forward' => 0,
                        'updated_at'      => now(),
                        'created_at'      => now(),
                    ]
                );
            }
        }
        $this->command->info('1. Leave balances: ' . ($allMembers->count() * $leaveTypes->count()) . ' rows');

        // ══════════════════════════════════════════════════════════════════
        // 2. LEAVE APPLICATIONS
        // ══════════════════════════════════════════════════════════════════
        // Clear recent demo applications to avoid duplicates on re-run
        DB::table('leave_applications')
            ->whereIn('user_id', $allMembers->pluck('id'))
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(30))
            ->delete();

        $leaves = [];

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
        $this->command->info('2. Leave applications: ' . count($leaves) . ' (2 pending, 1 approved, 1 rejected)');

        // ══════════════════════════════════════════════════════════════════
        // 3. WORK LOGS
        // ══════════════════════════════════════════════════════════════════
        // Clear today's logs for these members
        DB::table('work_logs')
            ->whereIn('user_id', $allMembers->pluck('id'))
            ->where('log_date', now()->toDateString())
            ->delete();

        // Alex + Marcus logged today; Sarah + Devansh did NOT
        $logCount = 0;
        foreach (collect([$alex, $marcus])->filter() as $u) {
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
            $logCount++;
        }

        $categories = ['development', 'review', 'meetings', 'planning', 'testing'];
        $catTitles  = [
            'development' => 'Feature development and testing',
            'review'      => 'Code review and PR feedback',
            'meetings'    => 'Sprint planning and standup',
            'planning'    => 'Technical design and estimation',
            'testing'     => 'QA and bug investigation',
        ];

        foreach ($members as $u) {
            for ($d = 1; $d <= 14; $d++) {
                $date = now()->subDays($d);
                if (in_array($date->dayOfWeek, [0, 6])) {
                    continue;
                }
                if (rand(1, 10) > 8) {
                    continue;
                }
                $cat = $categories[array_rand($categories)];
                DB::table('work_logs')->insert([
                    'user_id'          => $u->id,
                    'organization_id'  => $orgId,
                    'department_id'    => $deptId,
                    'project_id'       => $projectId,
                    'log_date'         => $date->toDateString(),
                    'category'         => $cat,
                    'title'            => $catTitles[$cat],
                    'description'      => 'Daily work log entry.',
                    'duration_minutes' => rand(240, 480),
                    'output_value'     => rand(3, 9),
                    'is_billable'      => true,
                    'created_at'       => $date,
                    'updated_at'       => $date,
                ]);
                $logCount++;
            }
        }
        $this->command->info('3. Work logs: ' . $logCount . ' entries (Alex+Marcus logged today)');

        // ══════════════════════════════════════════════════════════════════
        // 4. TASKS (16 tasks, 4 per member)
        // ══════════════════════════════════════════════════════════════════
        DB::table('tasks')
            ->where('assigned_by', $priya->id)
            ->whereIn('assigned_to', $members->pluck('id')->toArray())
            ->where('ticket_number', 'like', 'TXY-%')
            ->delete();

        $taskPool = [
            ['title' => 'Build user authentication API',       'type' => 'feature',     'difficulty' => 7],
            ['title' => 'Fix dashboard pagination bug',        'type' => 'bug',         'difficulty' => 4],
            ['title' => 'Write unit tests for payment module', 'type' => 'task',        'difficulty' => 5],
            ['title' => 'Refactor notification service',       'type' => 'improvement', 'difficulty' => 6],
            ['title' => 'Design report export schema',         'type' => 'task',        'difficulty' => 5],
            ['title' => 'Optimize slow database queries',      'type' => 'improvement', 'difficulty' => 8],
            ['title' => 'Update API documentation',            'type' => 'task',        'difficulty' => 2],
            ['title' => 'Code review for PR #58',              'type' => 'task',        'difficulty' => 3],
            ['title' => 'Implement file upload feature',       'type' => 'feature',     'difficulty' => 7],
            ['title' => 'Fix mobile responsive layout',        'type' => 'bug',         'difficulty' => 4],
            ['title' => 'Add email notification triggers',     'type' => 'feature',     'difficulty' => 6],
            ['title' => 'Migrate config to environment vars',  'type' => 'improvement', 'difficulty' => 3],
            ['title' => 'Set up CI/CD pipeline for staging',   'type' => 'task',        'difficulty' => 8],
            ['title' => 'Audit and fix XSS vulnerabilities',   'type' => 'bug',         'difficulty' => 9],
            ['title' => 'Implement rate limiting middleware',   'type' => 'feature',     'difficulty' => 6],
            ['title' => 'Review and close stale issues',       'type' => 'task',        'difficulty' => 2],
        ];
        $taskStatuses = ['todo', 'in_progress', 'in_review', 'done', 'blocked'];
        $priorities   = ['low', 'medium', 'medium', 'high', 'critical'];

        $tasks    = [];
        $taskIdx  = 0;
        foreach ($members as $u) {
            for ($t = 0; $t < 4; $t++) {
                $pool   = $taskPool[$taskIdx % count($taskPool)];
                $status = $taskStatuses[array_rand($taskStatuses)];
                $prio   = $priorities[array_rand($priorities)];
                $taskIdx++;
                $tasks[] = [
                    'ticket_number'    => 'TXY-' . str_pad($taskIdx, 3, '0', STR_PAD_LEFT),
                    'project_id'       => $projectId,
                    'sprint_id'        => $sprintId,
                    'assigned_to'      => $u->id,
                    'assigned_by'      => $priya->id,
                    'reporter_id'      => $priya->id,
                    'title'            => $pool['title'],
                    'type'             => $pool['type'],
                    'description'      => 'Demo task for Team XYZ role-view testing.',
                    'difficulty'       => $pool['difficulty'],
                    'visibility_score' => rand(3, 9),
                    'status'           => $status,
                    'priority'         => $prio,
                    'department_id'    => $deptId,
                    'started_at'       => $status !== 'todo'  ? now()->subDays(rand(2, 7)) : null,
                    'completed_at'     => $status === 'done'  ? now()->subDays(rand(0, 3)) : null,
                    'completed_by'     => $status === 'done'  ? $u->id : null,
                    'due_date'         => now()->addDays(rand(1, 14))->toDateString(),
                    'estimated_hours'  => rand(2, 16),
                    'actual_hours'     => $status === 'done'  ? rand(2, 20) : null,
                    'order_index'      => $taskIdx,
                    'created_at'       => now()->subDays(rand(5, 15)),
                    'updated_at'       => now(),
                ];
            }
        }
        DB::table('tasks')->insert($tasks);
        $this->command->info('4. Tasks: ' . count($tasks) . ' (mixed statuses across 4 members)');

        // ══════════════════════════════════════════════════════════════════
        // 5. BLOCKERS
        // ══════════════════════════════════════════════════════════════════
        DB::table('blockers')
            ->whereIn('reported_by', $allMembers->pluck('id'))
            ->where('organization_id', $orgId)
            ->where('created_at', '>=', now()->subDays(7))
            ->delete();

        $blockers = [];
        if ($alex && $sarah) {
            $blockers[] = $blockerRow([
                'reported_by'      => $alex->id,
                'blocked_user_id'  => $alex->id,
                'blocking_user_id' => $sarah->id,
                'blocker_type'     => 'internal_person',
                'title'            => 'Waiting on API contract from Sarah',
                'description'      => 'Cannot finish the integration module until Sarah finalises the API spec. Raised in standup twice.',
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
                'description'          => 'Client IT has not granted deployment credentials for the staging environment.',
                'priority'             => 'medium',
                'impact_level'         => 'just_me',
                'created_at'           => now()->subDays(1),
                'updated_at'           => now()->subDays(1),
            ]);
        }
        if (!empty($blockers)) {
            DB::table('blockers')->insert($blockers);
        }
        $this->command->info('5. Blockers: ' . count($blockers) . ' open');

        // ══════════════════════════════════════════════════════════════════
        // 6. ANNOUNCEMENTS
        //    priority enum: 'normal' | 'urgent'  (NOT 'high')
        //    audience enum: 'org' | 'department' | 'team'
        // ══════════════════════════════════════════════════════════════════
        // Don't duplicate if already seeded
        $existingCount = DB::table('announcements')->where('organization_id', $orgId)->count();
        if ($existingCount === 0) {
            DB::table('announcements')->insert([
                'organization_id' => $orgId,
                'posted_by'       => $rahul->id,
                'title'           => 'Welcome to Team XYZ',
                'message'         => 'Excited to have everyone on board. Let us ship great work this quarter!',
                'priority'        => 'normal',
                'audience'        => 'org',
                'department_id'   => null,
                'team_id'         => null,
                'is_pinned'       => true,
                'created_at'      => now()->subDays(3),
                'updated_at'      => now()->subDays(3),
            ]);

            DB::table('announcements')->insert([
                'organization_id' => $orgId,
                'posted_by'       => $priya->id,
                'title'           => 'Sprint 1 kickoff — check your tasks',
                'message'         => 'Sprint 1 has started. Please review your assigned tasks and update status by EOD.',
                'priority'        => 'urgent',
                'audience'        => 'team',
                'department_id'   => null,
                'team_id'         => $teamId,
                'is_pinned'       => false,
                'created_at'      => now()->subDays(2),
                'updated_at'      => now()->subDays(2),
            ]);

            $this->command->info('6. Announcements: 2 created');
        } else {
            $this->command->info('6. Announcements: skipped (' . $existingCount . ' already exist)');
        }

        // ══════════════════════════════════════════════════════════════════
        // 7. INCREMENT SCORES  (policy_id required)
        // ══════════════════════════════════════════════════════════════════
        $scoreCount = 0;
        if ($policyId) {
            foreach ($allMembers as $u) {
                for ($m = 1; $m <= 3; $m++) {
                    $raw = rand(60, 95);
                    DB::table('increment_scores')->updateOrInsert(
                        [
                            'user_id'     => $u->id,
                            'score_month' => now()->subMonths($m)->format('Y-m-01'),
                            'policy_id'   => $policyId,
                        ],
                        [
                            'organization_id'    => $orgId,
                            'raw_score'          => $raw,
                            'weighted_score'     => $raw,
                            'criteria_breakdown' => json_encode([
                                'worklog'    => rand(15, 25),
                                'tasks'      => rand(15, 25),
                                'quality'    => rand(10, 20),
                                'attendance' => rand(10, 15),
                            ]),
                            'anti_gaming_penalty' => 0,
                            'final_score'         => $raw,
                            'is_adjusted'         => false,
                            'calculated_at'       => now()->subMonths($m),
                            'created_at'          => now()->subMonths($m),
                            'updated_at'          => now()->subMonths($m),
                        ]
                    );
                    $scoreCount++;
                }
            }
            $this->command->info('7. Increment scores: ' . $scoreCount . ' rows (3 months × ' . $allMembers->count() . ' users)');
        } else {
            $this->command->warn('7. Increment scores: skipped (no active policy found)');
        }

        // ══════════════════════════════════════════════════════════════════
        // 8. EMPLOYEE PROFILES
        // ══════════════════════════════════════════════════════════════════
        $profileData = [
            $alex->id    => ['designation' => 'Mid Frontend Developer',   'skills' => ['React', 'CSS', 'TypeScript'],       'bio' => 'Frontend dev who loves clean UI.'],
            $sarah->id   => ['designation' => 'Senior Backend Developer',  'skills' => ['PHP', 'MySQL', 'Redis'],            'bio' => 'Backend specialist focused on reliability.'],
            $marcus->id  => ['designation' => 'Full Stack Developer',      'skills' => ['Laravel', 'Vue', 'Docker'],         'bio' => 'Full stack generalist comfortable end-to-end.'],
            $devansh->id => ['designation' => 'Junior Developer',          'skills' => ['HTML', 'JavaScript', 'PHP'],        'bio' => 'Eager to learn and grow every day.'],
            $priya->id   => ['designation' => 'Team Lead',                 'skills' => ['React', 'Leadership', 'Architecture'], 'bio' => 'Leading Team XYZ with focus on quality delivery.'],
        ];

        foreach ($profileData as $uid => $p) {
            DB::table('employee_profiles')->updateOrInsert(
                ['user_id' => $uid],
                [
                    'designation'          => $p['designation'],
                    'skills'               => json_encode($p['skills']),
                    'bio'                  => $p['bio'],
                    'date_of_joining'      => now()->subMonths(rand(6, 30))->toDateString(),
                    'is_directory_visible' => true,
                    'updated_at'           => now(),
                    'created_at'           => now(),
                ]
            );
        }
        $this->command->info('8. Employee profiles: ' . count($profileData) . ' upserted');

        $this->command->info('');
        $this->command->info('MasterDemoSeeder complete for Team XYZ.');
    }
}
