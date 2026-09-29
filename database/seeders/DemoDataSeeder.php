<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Blocker;
use App\Models\Department;
use App\Models\Designation;
use App\Models\EmployeeStatus;
use App\Models\IncrementCriteria;
use App\Models\IncrementPolicy;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\Demo\DemoDataCleaner;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

/**
 * QA demo data: three is_demo organizations with one login per role.
 *
 * Not called from DatabaseSeeder. Run explicitly:
 *   php artisan db:seed --class=DemoDataSeeder --force
 *
 * Safe to run again: organizations and logins are matched by slug/email and
 * kept; everything inside the demo orgs is rebuilt fresh (dates are relative
 * to today). It refuses to run if a demo slug or email belongs to real data.
 * Remove everything with: php artisan demo:purge
 *
 * Accounts are documented in docs/TEST_ACCOUNTS.md.
 */
class DemoDataSeeder extends Seeder
{
    public const PASSWORD = 'Test@12345';

    /** One login per role in every org: {role}@{slug}.test */
    public const ROLES = ['owner', 'admin', 'hr', 'team_lead', 'employee', 'viewer'];

    public const ORGS = [
        'demo-startup' => [
            'name' => 'Demo Startup', 'label' => 'Startup', 'users' => 8,
            'plan' => 'free', 'status' => 'active', 'pending' => 1,
        ],
        'demo-agency' => [
            'name' => 'Demo Agency', 'label' => 'Agency', 'users' => 20,
            'plan' => 'pro', 'status' => 'active', 'pending' => 2,
        ],
        'demo-suspended-co' => [
            // 5 were requested, but one login per role (6 roles) needs at least 6
            'name' => 'Demo Suspended Co', 'label' => 'Suspended Co', 'users' => 6,
            'plan' => 'free', 'status' => 'suspended', 'pending' => 0,
        ],
    ];

    private DemoDataCleaner $cleaner;

    public function run(): void
    {
        $this->cleaner = app(DemoDataCleaner::class);

        $this->ensureRolesExist();
        $this->ensureNoRealDataCollides();

        foreach (self::ORGS as $slug => $cfg) {
            DB::transaction(fn () => $this->seedOrganization($slug, $cfg));
        }

        $this->command?->info('Demo data ready. Password for every account: ' . self::PASSWORD);
        $this->command?->table(['Organization', 'Role', 'Email'], $this->accountRows());
        $this->command?->warn('Remove it all with: php artisan demo:purge');
    }

    // ── Safety checks ────────────────────────────────────────────────────────

    private function ensureRolesExist(): void
    {
        $missing = collect(self::ROLES)->reject(fn ($r) => Role::where('name', $r)->where('guard_name', 'web')->exists());
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Missing roles: ' . $missing->implode(', ')
                . '. Run RolesAndPermissionsSeeder and HrRoleSeeder first — this seeder does not create platform roles.');
        }
    }

    private function ensureNoRealDataCollides(): void
    {
        $realOrg = Organization::whereIn('slug', array_keys(self::ORGS))->where('is_demo', false)->pluck('slug');
        if ($realOrg->isNotEmpty()) {
            throw new RuntimeException('These slugs belong to real (non-demo) organizations, not touching them: ' . $realOrg->implode(', '));
        }

        $demoOrgIds = Organization::demo()->pluck('id');
        $emails     = collect(self::ORGS)->keys()->flatMap(fn ($slug) => $this->plannedEmails($slug));

        $realUsers = User::whereIn('email', $emails)
            ->where(fn ($q) => $q->whereNull('organization_id')->orWhereNotIn('organization_id', $demoOrgIds))
            ->pluck('email');
        if ($realUsers->isNotEmpty()) {
            throw new RuntimeException('These emails belong to real accounts, not touching them: ' . $realUsers->implode(', '));
        }
    }

    // ── Organization ─────────────────────────────────────────────────────────

    private function seedOrganization(string $slug, array $cfg): void
    {
        mt_srand(crc32($slug)); // same "random" data on every run

        $org = Organization::updateOrCreate(['slug' => $slug], [
            'name'           => $cfg['name'],
            'is_demo'        => true,
            'status'         => Organization::STATUS_ACTIVE,
            'plan'           => $cfg['plan'],
            'billing_status' => $cfg['plan'] === 'free' ? 'free' : 'active',
            'approved_at'    => now(),
            'notes'          => 'Demo organization for QA — created by DemoDataSeeder.',
        ]);

        $this->cleaner->clearContent([$org->id]);

        $people = $this->seedUsers($org, $slug, $cfg);
        $org->update(['owner_id' => $people['owner']->id]);

        if ($cfg['status'] === 'suspended') {
            $org->update([
                'status'            => Organization::STATUS_SUSPENDED,
                'suspended_at'      => now()->subDays(2),
                'suspended_by'      => null,
                'suspension_reason' => 'Demo: suspended so QA can test the suspension block.',
            ]);
            return;
        }

        $structure = $this->seedStructure($org, $slug, $people);
        $this->seedLeave($org, $people);
        $this->seedAnnouncements($org, $people, $structure);
        $work = $this->seedWork($org, $slug, $people, $structure);
        $this->seedBlockers($org, $people, $work['project']);
        $this->seedPendingApprovals($org, $slug, $cfg);

        if ($cfg['plan'] === 'pro') {
            $this->seedIncrementPolicy($org, $people);
            $this->seedBilling($org, $people, $cfg);
        }
    }

    // ── Users ────────────────────────────────────────────────────────────────

    /** Emails for every login in an org, in creation order. */
    private function plannedEmails(string $slug): Collection
    {
        $cfg     = self::ORGS[$slug];
        $locals  = collect(self::ROLES);
        $extras  = $cfg['users'] - count(self::ROLES);

        if ($slug === 'demo-agency' && $extras > 0) {
            $locals->push('team_lead2');
            $extras--;
        }
        for ($i = 2; $i < 2 + $extras; $i++) {
            $locals->push('employee' . $i);
        }
        for ($i = 1; $i <= $cfg['pending']; $i++) {
            $locals->push('pending' . $i);
        }

        return $locals->map(fn ($local) => "{$local}@{$slug}.test");
    }

    /** @return array<string, User> keyed by email local part */
    private function seedUsers(Organization $org, string $slug, array $cfg): array
    {
        $people = [];

        foreach ($this->plannedEmails($slug) as $email) {
            $local = Str::before($email, '@');
            if (str_starts_with($local, 'pending')) {
                continue; // created in seedPendingApprovals
            }

            $role = match (true) {
                str_starts_with($local, 'team_lead') => 'team_lead',
                str_starts_with($local, 'employee')  => 'employee',
                default                              => $local,
            };

            $user = User::updateOrCreate(['email' => $email], [
                'name'              => $this->displayName($cfg['label'], $local),
                'password'          => self::PASSWORD,
                'organization_id'   => $org->id,
                'role'              => $role,
                'email_verified_at' => now(),
                'is_active'         => true,
                'onboarding_status' => 'active',
                'onboarding_type'   => $role === 'owner' ? 'org_creator' : 'team_member',
                'approved_at'       => now(),
                'join_date'         => now()->subMonths(mt_rand(4, 30))->toDateString(),
                'employment_type'   => 'full_time',
                'work_location'     => 'hybrid',
            ]);
            $user->syncRoles([$role]);

            $people[$local] = $user;
        }

        return $people;
    }

    private function displayName(string $label, string $local): string
    {
        $pretty = [
            'owner' => 'Owner', 'admin' => 'Admin', 'hr' => 'HR', 'team_lead' => 'Team Lead',
            'team_lead2' => 'Team Lead 2', 'employee' => 'Employee', 'viewer' => 'Viewer',
        ][$local] ?? Str::of($local)->replaceMatches('/(\d+)$/', ' $1')->title();

        return "{$label} {$pretty}";
    }

    // ── Departments, designations, teams ─────────────────────────────────────

    private function seedStructure(Organization $org, string $slug, array $people): array
    {
        $isAgency = $slug === 'demo-agency';

        $deptDefs = $isAgency
            ? ['engineering' => ['Engineering', 'tech'], 'client-services' => ['Client Services', 'sales'],
               'people' => ['People & HR', 'hr'], 'finance' => ['Finance', 'finance']]
            : ['engineering' => ['Engineering', 'tech'], 'operations' => ['Operations', 'operations']];

        $depts = [];
        foreach ($deptDefs as $key => [$name, $type]) {
            $depts[$key] = Department::create([
                'organization_id' => $org->id, 'name' => $name, 'slug' => $key,
                'type' => $type, 'is_active' => true,
                'description' => "{$name} department (demo)",
            ]);
        }

        $designations = [];
        foreach ([
            ['software-developer', 'Software Developer', 'engineering', 'tech', 'mid', true],
            ['senior-software-developer', 'Senior Software Developer', 'engineering', 'tech', 'senior', true],
            ['engineering-lead', 'Engineering Lead', 'engineering', 'tech', 'lead', true],
            ['account-manager', 'Account Manager', 'marketing', 'sales', 'mid', false],
            ['hr-executive', 'HR Executive', 'hr', 'hr', 'mid', false],
            ['operations-executive', 'Operations Executive', 'product', 'operations', 'mid', false],
            ['finance-analyst', 'Finance Analyst', 'product', 'finance', 'mid', false],
        ] as $i => [$dSlug, $title, $category, $deptType, $level, $github]) {
            $designations[$dSlug] = Designation::create([
                'organization_id' => $org->id, 'slug' => $dSlug, 'title' => $title,
                'category' => $category, 'department_type' => $deptType, 'seniority_level' => $level,
                'requires_github' => $github, 'is_active' => true, 'is_template' => false, 'sort_order' => $i,
            ]);
        }

        // Teams with leads
        $teams = ['main' => Team::create([
            'organization_id' => $org->id, 'department_id' => $depts['engineering']->id,
            'team_lead_id' => $people['team_lead']->id,
            'name' => $isAgency ? 'Web Delivery' : 'Product Team', 'slug' => $isAgency ? 'web-delivery' : 'product-team',
            'description' => 'Demo team', 'is_active' => true,
        ])];
        if ($isAgency) {
            $teams['clients'] = Team::create([
                'organization_id' => $org->id, 'department_id' => $depts['client-services']->id,
                'team_lead_id' => $people['team_lead2']->id,
                'name' => 'Client Success', 'slug' => 'client-success',
                'description' => 'Demo team', 'is_active' => true,
            ]);
        }

        // Placement: [department, team, manager, designation, job title, seniority]
        $owner = $people['owner'];
        $admin = $people['admin'];
        $place = function (User $u, string $dept, ?string $team, ?User $manager, ?string $desig, string $title, string $level) use ($depts, $teams) {
            $u->update([
                'department_id'        => $depts[$dept]->id,
                'department'           => $depts[$dept]->name,
                'team_id'              => $team ? $teams[$team]->id : null,
                'reporting_manager_id' => $manager?->id,
                'designation'          => $desig,
                'job_title'            => $title,
                'seniority_level'      => $level,
            ]);
        };

        $opsDept = $isAgency ? 'finance' : 'operations';
        $hrDept  = $isAgency ? 'people' : 'operations';

        $place($owner, $opsDept, null, null, null, 'Founder & CEO', 'c_level');
        $place($admin, $opsDept, null, $owner, 'operations-executive', 'Operations Manager', 'manager');
        $place($people['hr'], $hrDept, null, $owner, 'hr-executive', 'HR Executive', 'mid');
        $place($people['team_lead'], 'engineering', 'main', $admin, 'engineering-lead', 'Engineering Lead', 'lead');
        $place($people['viewer'], $opsDept, null, $admin, 'operations-executive', 'Operations Intern', 'junior');
        $place($people['employee'], 'engineering', 'main', $people['team_lead'], 'senior-software-developer', 'Senior Software Developer', 'senior');

        if ($isAgency) {
            $place($people['team_lead2'], 'client-services', 'clients', $admin, 'account-manager', 'Client Success Lead', 'lead');
        }

        foreach ($people as $local => $u) {
            if (!preg_match('/^employee(\d+)$/', $local, $m)) {
                continue;
            }
            $n = (int) $m[1];
            if (!$isAgency || $n <= 7) {
                $place($u, 'engineering', 'main', $people['team_lead'], 'software-developer', 'Software Developer', $n % 2 ? 'mid' : 'junior');
            } elseif ($n <= 12) {
                $place($u, 'client-services', 'clients', $people['team_lead2'], 'account-manager', 'Account Manager', 'mid');
            } else {
                $place($u, 'finance', null, $admin, 'finance-analyst', 'Finance Analyst', 'mid');
            }
        }

        $depts['engineering']->update(['head_user_id' => $people['team_lead']->id]);

        return ['depts' => $depts, 'teams' => $teams, 'designations' => $designations];
    }

    // ── Leave ────────────────────────────────────────────────────────────────

    private function seedLeave(Organization $org, array $people): void
    {
        $types = collect([
            ['CL', 'Casual Leave', 12, true],
            ['SL', 'Sick Leave', 8, true],
            ['EL', 'Earned Leave', 15, true],
        ])->mapWithKeys(fn ($t) => [$t[0] => LeaveType::create([
            'organization_id' => $org->id, 'code' => $t[0], 'name' => $t[1], 'days_per_year' => $t[2],
            'is_paid' => $t[3], 'carry_forward' => $t[0] === 'EL', 'max_carry_forward' => $t[0] === 'EL' ? 5 : 0,
            'requires_approval' => true, 'is_active' => true,
        ])]);

        $reviewer = $people['hr'];
        $year     = now()->year;

        // [person, type, from offset days, length, status, reason, reviewer note]
        $apps = [
            ['employee',  'EL', -1, 3, 'approved', 'Family function out of town', 'Approved — enjoy!'],
            ['team_lead', 'CL',  6, 1, 'pending',  'Personal errand',              null],
            ['employee2', 'SL',  2, 2, 'pending',  'Medical appointment and rest', null],
            ['employee3', 'CL', -20, 1, 'rejected', 'Short notice day off',        'Release week — please pick another day.'],
            ['viewer',    'CL', -35, 1, 'approved', 'Personal work',               'Approved'],
        ];

        foreach ($apps as [$who, $code, $offset, $len, $status, $reason, $note]) {
            if (!isset($people[$who])) {
                continue;
            }
            $from = now()->addDays($offset)->startOfDay();
            LeaveApplication::create([
                'user_id' => $people[$who]->id, 'leave_type_id' => $types[$code]->id, 'organization_id' => $org->id,
                'from_date' => $from->toDateString(), 'to_date' => $from->copy()->addDays($len - 1)->toDateString(),
                'days' => $len, 'reason' => $reason, 'status' => $status,
                'reviewed_by' => $status === 'pending' ? null : $reviewer->id,
                'reviewer_note' => $note, 'reviewed_at' => $status === 'pending' ? null : now()->subDays(mt_rand(1, 5)),
                'is_half_day' => false,
            ]);

            // Someone on approved leave right now — Fairness checks skip people on leave
            if ($status === 'approved' && $offset <= 0 && $offset + $len > 0) {
                EmployeeStatus::create([
                    'user_id' => $people[$who]->id, 'organization_id' => $org->id, 'status' => 'on_leave',
                    'reason' => $reason, 'starts_at' => $from, 'ends_at' => $from->copy()->addDays($len)->endOfDay(),
                    'created_by' => $reviewer->id,
                ]);
            }
        }

        // Balances for everyone, consistent with the applications above
        foreach ($people as $u) {
            foreach ($types as $code => $type) {
                $mine = LeaveApplication::where('user_id', $u->id)->where('leave_type_id', $type->id);
                LeaveBalance::create([
                    'user_id' => $u->id, 'leave_type_id' => $type->id, 'organization_id' => $org->id, 'year' => $year,
                    'allocated' => $type->days_per_year,
                    'used' => (clone $mine)->where('status', 'approved')->sum('days'),
                    'pending' => (clone $mine)->where('status', 'pending')->sum('days'),
                    'carried_forward' => 0,
                ]);
            }
        }
    }

    // ── Announcements ────────────────────────────────────────────────────────

    private function seedAnnouncements(Organization $org, array $people, array $structure): void
    {
        $items = [
            ['Welcome to the demo workspace', 'This organization is demo data for testing. Feel free to create, edit and approve anything.', 'normal', 'org', null, null, true],
            ['Office closed on Friday', 'The office will be closed this Friday for maintenance. Please plan remote work.', 'urgent', 'org', null, null, false],
            ['Sprint demo on Thursday', 'Engineering sprint demo at 4 PM Thursday. All team members please join.', 'normal', 'department', $structure['depts']['engineering']->id, null, false],
            ['Code freeze next week', 'Web Delivery code freeze starts Monday for the client release.', 'normal', 'team', null, $structure['teams']['main']->id, false],
        ];

        foreach ($items as $i => [$title, $message, $priority, $audience, $deptId, $teamId, $pinned]) {
            $a = Announcement::create([
                'organization_id' => $org->id, 'posted_by' => ($i === 0 ? $people['owner'] : $people['hr'])->id,
                'title' => $title, 'message' => $message, 'priority' => $priority, 'audience' => $audience,
                'department_id' => $deptId, 'team_id' => $teamId, 'is_pinned' => $pinned,
                'expires_at' => $i === 1 ? now()->addDays(5) : null,
            ]);
            $a->forceFill(['created_at' => now()->subDays(8 - $i * 2)])->saveQuietly();
        }
    }

    // ── Projects, sprint, tasks, work logs, GitHub activity ──────────────────

    private function seedWork(Organization $org, string $slug, array $people, array $structure): array
    {
        $prefix = strtoupper($slug); // e.g. DEMO-AGENCY-001 — never clashes with real 3-letter prefixes

        $project = Project::create([
            'organization_id' => $org->id, 'name' => $slug === 'demo-agency' ? 'Client Portal Revamp' : 'Mobile App MVP',
            'description' => 'Demo project', 'status' => 'active',
            'start_date' => now()->subMonths(4)->toDateString(), 'end_date' => now()->addMonths(2)->toDateString(),
        ]);
        $internal = Project::create([
            'organization_id' => $org->id, 'name' => 'Internal Tools', 'description' => 'Demo project',
            'status' => 'active', 'start_date' => now()->subMonths(6)->toDateString(),
        ]);

        $sprint = Sprint::create([
            'organization_id' => $org->id, 'project_id' => $project->id, 'name' => 'Sprint ' . now()->format('W'),
            'goal' => 'Ship the client dashboard', 'status' => 'active',
            'start_date' => now()->startOfWeek()->toDateString(), 'end_date' => now()->startOfWeek()->addDays(13)->toDateString(),
            'created_by' => $people['team_lead']->id,
        ]);

        $workers = collect($people)->except(['owner', 'viewer']);
        $titles  = ['Build login screen', 'Fix invoice rounding bug', 'Client onboarding call', 'Write API docs',
                    'Refactor payment module', 'Prepare monthly report', 'Update CRM records', 'Security review',
                    'Design review feedback', 'Migrate old records', 'Performance tuning', 'Quarterly budget draft'];

        $ticket = 0;
        foreach ($workers as $local => $u) {
            // Deliberately uneven so Fairness checks have something to find:
            // "employee" is overloaded with hard open work, "employee2" only gets easy tasks.
            $count = match ($local) { 'employee' => 12, 'employee2' => 4, default => mt_rand(5, 8) };

            for ($i = 0; $i < $count; $i++) {
                $status = $this->pick(['done' => 45, 'in_progress' => 20, 'todo' => 15, 'in_review' => 10, 'blocked' => 5, 'backlog' => 5]);
                if ($local === 'employee' && $i >= 6) {
                    $status = $this->pick(['in_progress' => 50, 'todo' => 30, 'blocked' => 20]);
                }
                $difficulty = match ($local) { 'employee' => mt_rand(7, 10), 'employee2' => mt_rand(1, 3), default => mt_rand(2, 8) };
                $completed  = $status === 'done' ? now()->subDays(mt_rand(1, 88))->setTime(mt_rand(10, 18), 0) : null;

                Task::create([
                    'project_id' => $i % 4 === 3 ? $internal->id : $project->id,
                    'assigned_to' => $u->id, 'assigned_by' => ($u->reporting_manager_id ?: $people['admin']->id),
                    'reporter_id' => $people['team_lead']->id,
                    'ticket_number' => sprintf('%s-%03d', $prefix, ++$ticket),
                    'title' => $titles[array_rand($titles)], 'description' => 'Demo task.',
                    'type' => 'task', 'priority' => $this->pick(['low' => 20, 'medium' => 50, 'high' => 25, 'critical' => 5]),
                    'difficulty' => $difficulty, 'visibility_score' => mt_rand(3, 9), 'status' => $status,
                    'department_id' => $u->department_id,
                    'sprint_id' => $status !== 'done' && $status !== 'backlog' ? $sprint->id : null,
                    'started_at' => $status === 'todo' || $status === 'backlog' ? null : ($completed ?? now())->copy()->subDays(mt_rand(1, 6)),
                    'completed_at' => $completed, 'completed_by' => $completed ? $u->id : null,
                    'blocked_reason' => $status === 'blocked' ? 'Waiting on client credentials' : null,
                    'due_date' => now()->addDays(mt_rand(-5, 20))->toDateString(),
                    'estimated_hours' => mt_rand(2, 16),
                ]);
            }
        }

        $this->seedWorkLogs($org, $people, $project, $workers);
        $this->seedGithubActivity($org, $people, $project, $slug);

        return ['project' => $project, 'sprint' => $sprint];
    }

    private function seedWorkLogs(Organization $org, array $people, Project $project, Collection $workers): void
    {
        $onLeave = EmployeeStatus::where('organization_id', $org->id)->where('status', 'on_leave')->get();
        $rows    = [];
        $now     = now();

        foreach ($workers as $local => $u) {
            // "employee3" logs rarely — surfaces the work-log imbalance check
            $rate = $local === 'employee3' ? 25 : mt_rand(80, 95);
            $cats = match (true) {
                str_contains((string) $u->designation, 'developer') || str_contains((string) $u->designation, 'engineering') => ['Development', 'Code Review', 'Testing', 'Meeting'],
                str_contains((string) $u->designation, 'account') => ['Client Call', 'Proposal', 'Follow-up', 'Meeting'],
                str_contains((string) $u->designation, 'hr') => ['Recruitment', 'Onboarding', 'Payroll Prep', 'Meeting'],
                default => ['Reporting', 'Planning', 'Documentation', 'Meeting'],
            };

            for ($d = 90; $d >= 1; $d--) {
                $date = now()->subDays($d);
                if ($date->isWeekend() || mt_rand(1, 100) > $rate) {
                    continue;
                }
                $away = $onLeave->first(fn ($s) => $s->user_id === $u->id && $date->between($s->starts_at, $s->ends_at));
                if ($away) {
                    continue;
                }
                $category = $cats[array_rand($cats)];
                $rows[] = [
                    'user_id' => $u->id, 'organization_id' => $org->id, 'department_id' => $u->department_id,
                    'project_id' => $category === 'Development' ? $project->id : null,
                    'log_date' => $date->toDateString(), 'category' => $category,
                    'title' => $category . ' — ' . $date->format('D j M'), 'description' => 'Demo work log entry.',
                    'duration_minutes' => mt_rand(4, 16) * 30, 'is_billable' => $category === 'Client Call',
                    'created_at' => $date->copy()->setTime(18, 0), 'updated_at' => $date->copy()->setTime(18, 0),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            WorkLog::insert($chunk);
        }
    }

    private function seedGithubActivity(Organization $org, array $people, Project $project, string $slug): void
    {
        $devs = collect($people)->filter(fn (User $u) => str_contains((string) $u->designation, 'developer')
            || $u->designation === 'engineering-lead');
        $rows = [];
        $n    = 0;

        foreach ($devs as $u) {
            for ($d = 90; $d >= 1; $d--) {
                $date = now()->subDays($d);
                if ($date->isWeekend() || mt_rand(1, 100) > 60) {
                    continue;
                }
                $event = $this->pick(['commit' => 75, 'pr_opened' => 15, 'pr_merged' => 10]);
                $rows[] = [
                    'user_id' => $u->id, 'project_id' => $project->id, 'organization_id' => $org->id,
                    'source' => 'github', 'event_type' => $event, 'external_id' => "demo-{$slug}-" . (++$n),
                    'metadata' => json_encode(['message' => 'Demo ' . str_replace('_', ' ', $event), 'repo' => 'demo/' . $slug]),
                    'complexity_score' => mt_rand(3, 9) / 10, 'impact_score' => mt_rand(3, 9) / 10, 'quality_score' => mt_rand(5, 10) / 10,
                    'occurred_at' => $date->copy()->setTime(mt_rand(10, 19), mt_rand(0, 59)),
                    'created_at' => $date, 'updated_at' => $date,
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Activity::insert($chunk);
        }
    }

    // ── Blockers (drive the Fairness Engine flags) ───────────────────────────

    private function seedBlockers(Organization $org, array $people, Project $project): void
    {
        $make = function (array $attrs, Carbon $createdAt) use ($org, $project) {
            $b = Blocker::create(array_merge([
                'organization_id' => $org->id, 'project_id' => $project->id, 'priority' => 'medium',
                'description' => 'Demo blocker.', 'status' => 'open',
            ], $attrs));
            $b->forceFill(['created_at' => $createdAt])->saveQuietly();
        };

        $lead = $people['team_lead'];
        $dev  = $people['employee2'] ?? $people['employee'];

        // Open > 3 days → "unresolved_blockers" flag
        $make(['reported_by' => $dev->id, 'blocked_user_id' => $dev->id, 'blocking_user_id' => $lead->id,
               'blocker_type' => 'internal_person', 'title' => 'Waiting for API keys from team lead', 'priority' => 'high'],
              now()->subDays(9));

        // 3 open meeting-required blockers on one person → "meeting_overload" flag
        foreach (['Scope sign-off meeting', 'Design review meeting', 'Budget approval meeting'] as $i => $title) {
            $make(['reported_by' => $dev->id, 'blocked_user_id' => $dev->id, 'blocking_user_id' => $people['admin']->id,
                   'blocker_type' => 'meeting_required', 'title' => $title], now()->subDays(2 + $i));
        }

        // Resolved quickly — good for the increment "blocker resolution" score
        $make(['reported_by' => $people['employee']->id, 'blocked_user_id' => $people['employee']->id,
               'blocking_user_id' => $people['hr']->id, 'blocker_type' => 'waiting_approval',
               'title' => 'Laptop purchase approval', 'status' => 'resolved', 'resolved_at' => now()->subDays(18),
               'resolved_by' => $people['hr']->id, 'resolution_notes' => 'Approved', 'days_to_resolve' => 1],
              now()->subDays(19));

        // External vendor, disputed ownership
        $make(['reported_by' => $lead->id, 'blocked_user_id' => $lead->id, 'blocker_type' => 'external_vendor',
               'title' => 'Payment gateway sandbox access', 'external_person_name' => 'Vendor support desk',
               'external_person_company' => 'Demo Vendor Pvt Ltd', 'ownership_disputed' => true,
               'dispute_reason' => 'Vendor says the request never reached them', 'dispute_raised_at' => now()->subDays(1)],
              now()->subDays(4));
    }

    // ── Pending approvals ────────────────────────────────────────────────────

    private function seedPendingApprovals(Organization $org, string $slug, array $cfg): void
    {
        for ($i = 1; $i <= $cfg['pending']; $i++) {
            $user = User::updateOrCreate(['email' => "pending{$i}@{$slug}.test"], [
                'name'              => "{$cfg['label']} Pending Signup {$i}",
                'password'          => self::PASSWORD,
                'organization_id'   => $org->id,
                'role'              => 'viewer',
                'email_verified_at' => now(),
                'is_active'         => true,
                'onboarding_status' => 'pending',
                'onboarding_type'   => 'team_member',
                'approved_at'       => null,
            ]);
            $user->syncRoles(['viewer']);
        }
    }

    // ── Increment policy (Pro) ───────────────────────────────────────────────

    private function seedIncrementPolicy(Organization $org, array $people): void
    {
        $policy = IncrementPolicy::create([
            'organization_id' => $org->id, 'name' => 'Demo Annual Increment Policy',
            'max_increment_percent' => 20, 'review_period' => 'Annual', 'review_month' => 12,
            'minimum_months_required' => 3, 'minimum_score_for_increment' => 40,
            'anti_gaming_enabled' => true, 'is_active' => true, 'created_by' => $people['owner']->id,
        ]);

        foreach ([
            ['tasks_completed', 'Tasks Completed', 30],
            ['task_complexity', 'Task Complexity', 20],
            ['work_logs', 'Work Log Consistency', 25],
            ['blocker_resolution', 'Blocker Resolution', 15],
            ['attendance', 'Attendance', 10],
        ] as [$name, $label, $weight]) {
            IncrementCriteria::create([
                'policy_id' => $policy->id, 'organization_id' => $org->id, 'criteria_name' => $name,
                'criteria_label' => $label, 'criteria_type' => 'automatic', 'data_source' => $name,
                'weight_percent' => $weight, 'is_active' => true,
            ]);
        }
    }

    // ── Billing (Pro) ────────────────────────────────────────────────────────

    private function seedBilling(Organization $org, array $people, array $cfg): void
    {
        $seats = $cfg['users'];
        $unit  = (int) config('plans.pro.periods.monthly.price_per_user');
        $sub   = $unit * $seats;
        $tax   = (int) round($sub * (float) config('plans.gst_percent') / 100);

        // Both successful payments are older than the 7-day refund window, so no
        // refund button appears; fake gateway ids can't be refunded anyway.
        $periods = [[now()->subDays(40), 'OLD'], [now()->subDays(10), 'CUR']];
        $end     = null;

        foreach ($periods as $i => [$paidAt, $tag]) {
            $end = $paidAt->copy()->addMonthNoOverflow();
            Payment::create([
                'organization_id' => $org->id, 'paid_by' => $people['owner']->id, 'plan' => 'pro',
                'billing_period' => 'monthly', 'seats' => $seats, 'unit_price' => $unit, 'subtotal' => $sub,
                'tax_amount' => $tax, 'amount' => $sub + $tax, 'currency' => 'INR',
                'razorpay_order_id' => "order_DEMO{$tag}{$org->id}", 'razorpay_payment_id' => "pay_DEMO{$tag}{$org->id}",
                'status' => 'paid', 'paid_at' => $paidAt, 'period_start' => $paidAt, 'period_end' => $end,
                'receipt_number' => sprintf('DEMO-%d-%04d', $org->id, $i + 1),
                'meta' => ['demo' => true],
            ]);
        }

        Payment::create([
            'organization_id' => $org->id, 'paid_by' => $people['admin']->id, 'plan' => 'pro',
            'billing_period' => 'yearly', 'seats' => $seats, 'unit_price' => (int) config('plans.pro.periods.yearly.price_per_user'),
            'subtotal' => $sub * 9, 'tax_amount' => $tax * 9, 'amount' => ($sub + $tax) * 9, 'currency' => 'INR',
            'razorpay_order_id' => "order_DEMOFAIL{$org->id}", 'status' => 'failed',
            'failure_reason' => 'Card declined by the bank (demo)', 'meta' => ['demo' => true],
        ]);

        $org->update([
            'plan' => 'pro', 'billing_status' => 'active', 'billing_period' => 'monthly',
            'seats' => $seats, 'plan_expires_at' => $end, 'downgrade_scheduled_at' => null,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Weighted pick, e.g. ['done' => 45, 'todo' => 15] */
    private function pick(array $weights): string
    {
        $roll = mt_rand(1, array_sum($weights));
        foreach ($weights as $value => $weight) {
            if (($roll -= $weight) <= 0) {
                return $value;
            }
        }
        return array_key_first($weights);
    }

    private function accountRows(): array
    {
        return collect(self::ORGS)->flatMap(fn ($cfg, $slug) => $this->plannedEmails($slug)
            ->map(function ($email) use ($cfg) {
                $local = Str::before($email, '@');
                $role  = match (true) {
                    str_starts_with($local, 'team_lead') => 'team_lead',
                    str_starts_with($local, 'employee')  => 'employee',
                    str_starts_with($local, 'pending')   => 'viewer (pending approval)',
                    default                              => $local,
                };
                return [$cfg['name'], $role, $email];
            }))->all();
    }
}
