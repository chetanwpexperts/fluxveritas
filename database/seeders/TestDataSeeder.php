<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Activity;
use App\Models\Task;
use App\Models\Blocker;
use App\Models\BlockerResponse;
use App\Models\FairnessFlag;
use App\Models\EmployeeStatus;
use App\Models\Sprint;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use App\Models\IncrementPolicy;
use App\Models\IncrementCriteria;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding test data...');

        // ════════════════════════════════════════
        // ORGANIZATION — use existing org
        // ════════════════════════════════════════

        $org = Organization::first();

        if (!$org) {
            $this->command->error('No organization found. Create an org first via the UI.');
            return;
        }

        $org->update(['plan' => 'pro']);
        $this->command->info('Organization: ' . $org->name . ' (plan → pro)');

        // ════════════════════════════════════════
        // USERS
        // ════════════════════════════════════════

        $manager = User::firstOrCreate(
            ['email' => 'manager@techcorp.com'],
            [
                'name'               => 'Rahul Sharma',
                'password'           => Hash::make('password'),
                'organization_id'    => $org->id,
                'role'               => 'admin',
                'github_username'    => 'rahul-mgr',
                'department'         => 'Engineering',
                'onboarding_status'  => 'active',
                'onboarding_type'    => 'team_member',
                'is_active'          => true,
            ]
        );
        $manager->update(['organization_id' => $org->id]);
        if (!$manager->hasRole('admin')) {
            $manager->syncRoles(['admin']);
        }

        $teamLead = User::firstOrCreate(
            ['email' => 'teamlead@techcorp.com'],
            [
                'name'               => 'Priya Patel',
                'password'           => Hash::make('password'),
                'organization_id'    => $org->id,
                'role'               => 'employee',
                'github_username'    => 'priya-lead',
                'department'         => 'Engineering',
                'onboarding_status'  => 'active',
                'onboarding_type'    => 'team_member',
                'is_active'          => true,
            ]
        );
        $teamLead->update(['organization_id' => $org->id]);
        if (!$teamLead->hasRole('team_lead')) {
            $teamLead->syncRoles(['team_lead']);
        }

        // Person A — manager's favourite (low output, high visibility)
        $personA = User::firstOrCreate(
            ['email' => 'alex@techcorp.com'],
            [
                'name'               => 'Alex Kumar',
                'password'           => Hash::make('password'),
                'organization_id'    => $org->id,
                'role'               => 'employee',
                'github_username'    => 'alex-kumar',
                'department'         => 'Engineering',
                'onboarding_status'  => 'active',
                'onboarding_type'    => 'team_member',
                'is_active'          => true,
            ]
        );
        $personA->update(['organization_id' => $org->id]);
        if (!$personA->hasRole('employee')) {
            $personA->syncRoles(['employee']);
        }

        // Person B — hard worker, not favourite
        $personB = User::firstOrCreate(
            ['email' => 'sarah@techcorp.com'],
            [
                'name'               => 'Sarah Singh',
                'password'           => Hash::make('password'),
                'organization_id'    => $org->id,
                'role'               => 'employee',
                'github_username'    => 'sarah-singh',
                'department'         => 'Engineering',
                'onboarding_status'  => 'active',
                'onboarding_type'    => 'team_member',
                'is_active'          => true,
            ]
        );
        $personB->update(['organization_id' => $org->id]);
        if (!$personB->hasRole('employee')) {
            $personB->syncRoles(['employee']);
        }

        // Person C — mid-level, blocked by cross-team
        $personC = User::firstOrCreate(
            ['email' => 'dev.marcus@techcorp.com'],
            [
                'name'               => 'Marcus Dev',
                'password'           => Hash::make('password'),
                'organization_id'    => $org->id,
                'role'               => 'employee',
                'github_username'    => 'marcus-dev',
                'department'         => 'Engineering',
                'onboarding_status'  => 'active',
                'onboarding_type'    => 'team_member',
                'is_active'          => true,
            ]
        );
        $personC->update(['organization_id' => $org->id]);
        if (!$personC->hasRole('employee')) {
            $personC->syncRoles(['employee']);
        }

        // Person D — on medical leave
        $personD = User::firstOrCreate(
            ['email' => 'neha@techcorp.com'],
            [
                'name'               => 'Neha Verma',
                'password'           => Hash::make('password'),
                'organization_id'    => $org->id,
                'role'               => 'employee',
                'github_username'    => 'neha-verma',
                'department'         => 'Engineering',
                'onboarding_status'  => 'active',
                'onboarding_type'    => 'team_member',
                'is_active'          => true,
            ]
        );
        $personD->update(['organization_id' => $org->id]);
        if (!$personD->hasRole('employee')) {
            $personD->syncRoles(['employee']);
        }

        $this->command->info('Created/verified 5 team members');

        // ════════════════════════════════════════
        // PROJECTS
        // ════════════════════════════════════════

        $project1 = Project::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'FluxVeritas Platform'],
            [
                'description'  => 'Main platform development',
                'github_repo'  => 'fluxveritas',
                'github_owner' => 'chetanwpexperts',
                'status'       => 'active',
                'start_date'   => now()->subMonths(2)->toDateString(),
            ]
        );

        $project2 = Project::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'Customer Portal'],
            [
                'description'  => 'Client-facing dashboard',
                'github_repo'  => 'customer-portal',
                'github_owner' => 'chetanwpexperts',
                'status'       => 'active',
                'start_date'   => now()->subMonth()->toDateString(),
            ]
        );

        $project3 = Project::firstOrCreate(
            ['organization_id' => $org->id, 'name' => 'API Gateway'],
            [
                'description'  => 'REST API service layer',
                'github_repo'  => 'api-gateway',
                'github_owner' => 'chetanwpexperts',
                'status'       => 'active',
                'start_date'   => now()->subWeeks(3)->toDateString(),
            ]
        );

        $this->command->info('Created 3 projects');

        // ════════════════════════════════════════
        // GITHUB ACTIVITIES
        // Pattern: Sarah 3x output, Alex coasts
        // ════════════════════════════════════════

        $commitMessages = [
            'feat: implement payment gateway',
            'fix: resolve authentication bug',
            'refactor: optimize database queries',
            'feat: add user dashboard',
            'fix: security vulnerability patch',
            'perf: improve API response time',
            'test: add integration tests',
            'feat: user notification system',
        ];

        // Sarah — 20 commits (hard work, high complexity)
        for ($i = 0; $i < 20; $i++) {
            Activity::firstOrCreate(
                ['user_id' => $personB->id, 'external_id' => 'sarah-commit-' . $i, 'event_type' => 'commit'],
                [
                    'project_id'       => $i % 2 === 0 ? $project1->id : $project2->id,
                    'organization_id'  => $org->id,
                    'source'           => 'github',
                    'metadata'         => ['message' => $commitMessages[$i % count($commitMessages)], 'sha' => Str::random(40)],
                    'complexity_score' => rand(15, 25) / 10,
                    'impact_score'     => rand(15, 25) / 10,
                    'quality_score'    => rand(15, 25) / 10,
                    'occurred_at'      => now()->subDays(rand(1, 28)),
                ]
            );
        }

        // Sarah — 5 merged PRs
        $prTitles = ['Payment Integration', 'Auth System Overhaul', 'Dashboard v2', 'API Optimization', 'Security Hardening'];
        for ($i = 0; $i < 5; $i++) {
            Activity::firstOrCreate(
                ['user_id' => $personB->id, 'external_id' => 'sarah-pr-' . $i, 'event_type' => 'pr_merged'],
                [
                    'project_id'       => $project1->id,
                    'organization_id'  => $org->id,
                    'source'           => 'github',
                    'metadata'         => ['title' => 'Feature: ' . $prTitles[$i], 'number' => 100 + $i],
                    'complexity_score' => 2.5,
                    'impact_score'     => 2.8,
                    'quality_score'    => 2.6,
                    'occurred_at'      => now()->subDays(rand(1, 20)),
                ]
            );
        }

        // Alex — 6 minor commits (low complexity)
        $alexMessages = ['update readme', 'fix typo', 'minor UI changes', 'update config', 'style: formatting', 'chore: cleanup'];
        for ($i = 0; $i < 6; $i++) {
            Activity::firstOrCreate(
                ['user_id' => $personA->id, 'external_id' => 'alex-commit-' . $i, 'event_type' => 'commit'],
                [
                    'project_id'       => $project1->id,
                    'organization_id'  => $org->id,
                    'source'           => 'github',
                    'metadata'         => ['message' => $alexMessages[$i], 'sha' => Str::random(40)],
                    'complexity_score' => rand(5, 10) / 10,
                    'impact_score'     => rand(5, 10) / 10,
                    'quality_score'    => rand(5, 10) / 10,
                    'occurred_at'      => now()->subDays(rand(1, 28)),
                ]
            );
        }

        // Marcus — 10 solid commits
        $marcusMessages = ['feat: add API endpoints', 'fix: rate limiting bug', 'feat: auth middleware', 'test: unit tests', 'docs: API documentation'];
        for ($i = 0; $i < 10; $i++) {
            Activity::firstOrCreate(
                ['user_id' => $personC->id, 'external_id' => 'marcus-commit-' . $i, 'event_type' => 'commit'],
                [
                    'project_id'       => $project3->id,
                    'organization_id'  => $org->id,
                    'source'           => 'github',
                    'metadata'         => ['message' => $marcusMessages[$i % count($marcusMessages)], 'sha' => Str::random(40)],
                    'complexity_score' => rand(12, 20) / 10,
                    'impact_score'     => rand(12, 18) / 10,
                    'quality_score'    => rand(10, 18) / 10,
                    'occurred_at'      => now()->subDays(rand(1, 25)),
                ]
            );
        }

        // Manager — 3 token commits
        for ($i = 0; $i < 3; $i++) {
            Activity::firstOrCreate(
                ['user_id' => $manager->id, 'external_id' => 'manager-commit-' . $i, 'event_type' => 'commit'],
                [
                    'project_id'       => $project1->id,
                    'organization_id'  => $org->id,
                    'source'           => 'github',
                    'metadata'         => ['message' => 'review changes', 'sha' => Str::random(40)],
                    'complexity_score' => 0.5,
                    'impact_score'     => 0.5,
                    'quality_score'    => 0.5,
                    'occurred_at'      => now()->subDays(rand(5, 25)),
                ]
            );
        }

        $this->command->info('Created 44 GitHub activities');

        // ════════════════════════════════════════
        // SPRINTS
        // ════════════════════════════════════════

        $sprint1 = Sprint::firstOrCreate(
            ['organization_id' => $org->id, 'project_id' => $project1->id, 'name' => 'Sprint 1'],
            [
                'goal'       => 'Complete payment gateway and security fixes',
                'status'     => 'active',
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date'   => now()->addDays(7)->toDateString(),
                'created_by' => $manager->id,
            ]
        );

        $sprint2 = Sprint::firstOrCreate(
            ['organization_id' => $org->id, 'project_id' => $project1->id, 'name' => 'Sprint 2'],
            [
                'goal'       => 'API integration and testing',
                'status'     => 'planning',
                'start_date' => now()->addDays(8)->toDateString(),
                'end_date'   => now()->addDays(22)->toDateString(),
                'created_by' => $manager->id,
            ]
        );

        $this->command->info('Created 2 sprints');

        // ════════════════════════════════════════
        // TASKS — enhanced with new columns
        // ════════════════════════════════════════

        // Clear old tasks for idempotency on new columns
        Task::whereHas('project', fn($q) => $q->where('organization_id', $org->id))->delete();

        // Epic
        $epic = Task::create([
            'project_id'       => $project1->id,
            'title'            => 'Payment System v2.0',
            'type'             => 'epic',
            'status'           => 'in_progress',
            'priority'         => 'critical',
            'assigned_to'      => $personB->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $manager->id,
            'difficulty'       => 10,
            'visibility_score' => 8,
            'label'            => 'backend',
            'estimated_hours'  => 40,
            'due_date'         => now()->addDays(14)->toDateString(),
        ]);

        Task::create([
            'project_id'       => $project1->id,
            'title'            => 'Implement Stripe payment gateway',
            'type'             => 'feature',
            'status'           => 'in_progress',
            'priority'         => 'critical',
            'assigned_to'      => $personB->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $manager->id,
            'parent_task_id'   => $epic->id,
            'difficulty'       => 9,
            'visibility_score' => 3,
            'label'            => 'backend',
            'estimated_hours'  => 12,
            'due_date'         => now()->addDays(5)->toDateString(),
        ]);

        Task::create([
            'project_id'       => $project1->id,
            'title'            => 'Update CEO presentation slides',
            'type'             => 'task',
            'status'           => 'done',
            'priority'         => 'high',
            'assigned_to'      => $personA->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $manager->id,
            'difficulty'       => 2,
            'visibility_score' => 10,
            'label'            => 'other',
            'estimated_hours'  => 2,
            'due_date'         => now()->subDays(2)->toDateString(),
            'completed_at'     => now()->subDays(1),
        ]);

        Task::create([
            'project_id'       => $project1->id,
            'title'            => 'Fix critical security vulnerability in auth',
            'type'             => 'bug',
            'status'           => 'done',
            'priority'         => 'critical',
            'assigned_to'      => $personB->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $personC->id,
            'difficulty'       => 10,
            'visibility_score' => 1,
            'label'            => 'backend',
            'estimated_hours'  => 8,
            'due_date'         => now()->subDays(5)->toDateString(),
            'completed_at'     => now()->subDays(3),
        ]);

        Task::create([
            'project_id'       => $project1->id,
            'title'            => 'Prepare sprint demo for stakeholders',
            'type'             => 'task',
            'status'           => 'in_progress',
            'priority'         => 'high',
            'assigned_to'      => $personA->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $manager->id,
            'difficulty'       => 1,
            'visibility_score' => 10,
            'label'            => 'other',
            'estimated_hours'  => 3,
            'due_date'         => now()->addDays(2)->toDateString(),
            'started_at'       => now()->subDay(),
        ]);

        Task::create([
            'project_id'       => $project2->id,
            'title'            => 'Build customer dashboard API endpoints',
            'type'             => 'feature',
            'status'           => 'in_review',
            'priority'         => 'high',
            'assigned_to'      => $personC->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $personC->id,
            'difficulty'       => 7,
            'visibility_score' => 5,
            'label'            => 'backend',
            'estimated_hours'  => 10,
            'due_date'         => now()->addDays(3)->toDateString(),
        ]);

        Task::create([
            'project_id'       => $project1->id,
            'title'            => 'Database optimization for report queries',
            'type'             => 'improvement',
            'status'           => 'blocked',
            'priority'         => 'high',
            'assigned_to'      => $personB->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $personB->id,
            'difficulty'       => 8,
            'visibility_score' => 2,
            'label'            => 'database',
            'estimated_hours'  => 6,
            'due_date'         => now()->subDays(1)->toDateString(),
            'blocked_reason'   => 'Waiting for DBA access from ops team',
        ]);

        Task::create([
            'project_id'       => $project3->id,
            'title'            => 'Write API documentation',
            'type'             => 'task',
            'status'           => 'todo',
            'priority'         => 'medium',
            'assigned_to'      => $personC->id,
            'assigned_by'      => $manager->id,
            'reporter_id'      => $manager->id,
            'difficulty'       => 4,
            'visibility_score' => 4,
            'label'            => 'docs',
            'estimated_hours'  => 4,
            'due_date'         => now()->addDays(7)->toDateString(),
        ]);

        // Task comments
        TaskComment::firstOrCreate(
            ['task_id' => $epic->id, 'user_id' => $manager->id],
            ['comment' => 'This is our top priority for Q2. Sarah please make sure payment gateway is done by end of sprint.']
        );
        TaskComment::firstOrCreate(
            ['task_id' => $epic->id, 'user_id' => $personB->id],
            ['comment' => 'Blocked on API specs from Alex. Cannot proceed without them. Already reported as blocker.']
        );

        // Task history
        TaskHistory::firstOrCreate(
            ['task_id' => $epic->id, 'user_id' => $manager->id, 'action' => 'created'],
            ['new_value' => 'Payment System v2.0']
        );
        TaskHistory::firstOrCreate(
            ['task_id' => $epic->id, 'user_id' => $manager->id, 'action' => 'assigned'],
            ['old_value' => 'Unassigned', 'new_value' => 'Sarah Singh']
        );

        $this->command->info('Created 8 enhanced tasks with comments and history');

        // ════════════════════════════════════════
        // BLOCKERS
        // ════════════════════════════════════════

        // Blocker 1: Alex blocking Sarah — disputed, escalated
        $b1Exists = Blocker::where('organization_id', $org->id)
            ->where('title', 'API specs needed before payment integration can start')
            ->first();

        if (!$b1Exists) {
            $b1Exists = Blocker::create([
                'organization_id'    => $org->id,
                'project_id'         => $project1->id,
                'reported_by'        => $personB->id,
                'blocked_user_id'    => $personB->id,
                'blocking_user_id'   => $personA->id,
                'blocker_type'       => 'internal_person',
                'title'              => 'API specs needed before payment integration can start',
                'description'        => 'Cannot start payment integration without API specs from Alex. He was assigned to write them 2 weeks ago but they are still not done.',
                'status'             => 'escalated',
                'priority'           => 'critical',
                'ownership_disputed' => true,
                'dispute_reason'     => 'Alex says it is not his responsibility — claims it belongs to the architect team.',
                'dispute_raised_at'  => now()->subDays(5),
                'evidence_notes'     => "Slack message from manager May 1st assigning specs to Alex.\nJira ticket #234.\nEmail thread CC: manager@techcorp.com",
                'due_date'           => now()->subDays(3),
            ]);
            DB::table('blockers')->where('id', $b1Exists->id)->update(['created_at' => now()->subDays(10)]);
        }

        BlockerResponse::firstOrCreate(
            ['blocker_id' => $b1Exists->id, 'user_id' => $personB->id, 'response_type' => 'commented'],
            ['message' => 'Reported this 10 days ago. Still waiting. My work is completely blocked.']
        );
        BlockerResponse::firstOrCreate(
            ['blocker_id' => $b1Exists->id, 'user_id' => $personA->id, 'response_type' => 'disputed'],
            ['message' => 'This is not my task. The architect should handle API specifications.']
        );
        BlockerResponse::firstOrCreate(
            ['blocker_id' => $b1Exists->id, 'user_id' => $personB->id, 'response_type' => 'escalated'],
            ['message' => 'Escalating to management. Week 2 of being blocked. Evidence: Jira #234 shows Alex was assigned this task.']
        );

        // Blocker 2: External vendor
        $b2Exists = Blocker::where('organization_id', $org->id)
            ->where('title', 'Payment gateway credentials not provided by vendor')
            ->first();

        if (!$b2Exists) {
            $b2Exists = Blocker::create([
                'organization_id'         => $org->id,
                'project_id'              => $project1->id,
                'reported_by'             => $personB->id,
                'blocked_user_id'         => $personB->id,
                'blocking_user_id'        => null,
                'blocker_type'            => 'external_vendor',
                'external_person_name'    => 'Rajesh Gupta',
                'external_person_company' => 'PaySecure India Pvt Ltd',
                'external_person_contact' => 'rajesh@paysecure.in',
                'title'                   => 'Payment gateway credentials not provided by vendor',
                'description'             => 'Waiting for Stripe-compatible credentials from PaySecure. Cannot test payment flow without them.',
                'status'                  => 'open',
                'priority'                => 'high',
                'evidence_notes'          => "Email sent May 3rd. Follow-up May 7th. No response.\nContract ref: PS-2026-0892",
                'due_date'                => now()->addDays(2),
            ]);
            DB::table('blockers')->where('id', $b2Exists->id)->update(['created_at' => now()->subDays(7)]);
        }

        // Blocker 3: Same company, Finance blocking
        $b3Exists = Blocker::where('organization_id', $org->id)
            ->where('title', 'Finance approval needed for cloud infrastructure spend')
            ->first();

        if (!$b3Exists) {
            $b3Exists = Blocker::create([
                'organization_id'         => $org->id,
                'project_id'              => $project3->id,
                'reported_by'             => $personC->id,
                'blocked_user_id'         => $personC->id,
                'blocking_user_id'        => null,
                'blocker_type'            => 'internal_other_team',
                'external_person_name'    => 'Amit Joshi',
                'external_person_company' => 'Finance Department',
                'external_person_contact' => 'amit.joshi@company.com',
                'title'                   => 'Finance approval needed for cloud infrastructure spend',
                'description'             => 'Need Finance to approve $500/month AWS budget increase for API Gateway scaling. Request submitted 8 days ago.',
                'status'                  => 'open',
                'priority'                => 'high',
                'evidence_notes'          => "Budget request BF-2026-112 submitted May 2nd.\nReminder sent May 6th to amit.joshi@company.com.\nProject cannot scale without this approval.",
                'due_date'                => now()->addDay(),
            ]);
            DB::table('blockers')->where('id', $b3Exists->id)->update(['created_at' => now()->subDays(8)]);
        }

        // Blocker 4: Same company, Legal blocking (second Finance/Legal pattern)
        $b4Exists = Blocker::where('organization_id', $org->id)
            ->where('title', 'Legal sign-off required on user data privacy policy')
            ->first();

        if (!$b4Exists) {
            $b4Exists = Blocker::create([
                'organization_id'         => $org->id,
                'project_id'              => $project1->id,
                'reported_by'             => $personB->id,
                'blocked_user_id'         => $personB->id,
                'blocking_user_id'        => null,
                'blocker_type'            => 'internal_other_team',
                'external_person_name'    => 'Sunita Rao',
                'external_person_company' => 'Legal Department',
                'external_person_contact' => 'sunita.rao@company.com',
                'title'                   => 'Legal sign-off required on user data privacy policy',
                'description'             => 'Cannot launch payment feature without legal sign-off on the GDPR data privacy policy update.',
                'status'                  => 'open',
                'priority'                => 'critical',
                'evidence_notes'          => "Legal review requested April 28th.\nGDPR compliance check needed.\nOriginal launch date was May 10th — now overdue.",
                'due_date'                => now()->subDays(5),
            ]);
            DB::table('blockers')->where('id', $b4Exists->id)->update(['created_at' => now()->subDays(12)]);
        }

        // Blocker 5: Second Alex dispute — establishes repeat pattern
        $b5Exists = Blocker::where('organization_id', $org->id)
            ->where('title', 'Code review from Alex pending for 2 weeks on PR #45')
            ->first();

        if (!$b5Exists) {
            $b5Exists = Blocker::create([
                'organization_id'    => $org->id,
                'project_id'         => $project2->id,
                'reported_by'        => $personB->id,
                'blocked_user_id'    => $personB->id,
                'blocking_user_id'   => $personA->id,
                'blocker_type'       => 'internal_person',
                'title'              => 'Code review from Alex pending for 2 weeks on PR #45',
                'description'        => 'PR #45 has been waiting for Alex to review for 2 weeks. Cannot merge to main without his approval.',
                'status'             => 'open',
                'priority'           => 'high',
                'ownership_disputed' => true,
                'dispute_reason'     => 'Alex says he is too busy with presentation prep and reviewing others code is not his priority.',
                'dispute_raised_at'  => now()->subDays(3),
                'evidence_notes'     => "PR #45 opened April 28th.\nReview requested same day.\n2 follow-up comments posted in PR.\nTeam lead also asked Alex on May 5th.",
                'due_date'           => now()->subDays(7),
            ]);
            DB::table('blockers')->where('id', $b5Exists->id)->update(['created_at' => now()->subDays(14)]);
        }

        BlockerResponse::firstOrCreate(
            ['blocker_id' => $b5Exists->id, 'user_id' => $personB->id, 'response_type' => 'commented'],
            ['message' => 'Second time Alex has blocked my work — first API specs, now code review. Building a pattern here.']
        );
        BlockerResponse::firstOrCreate(
            ['blocker_id' => $b5Exists->id, 'user_id' => $personA->id, 'response_type' => 'disputed'],
            ['message' => 'I have my own sprint tasks. Code review is not my primary responsibility.']
        );

        $this->command->info('Created 5 blockers with responses');

        // ════════════════════════════════════════
        // EMPLOYEE STATUS — Neha on medical leave
        // ════════════════════════════════════════

        EmployeeStatus::firstOrCreate(
            ['user_id' => $personD->id, 'organization_id' => $org->id],
            [
                'status'     => 'on_leave',
                'reason'     => 'Medical leave — knee surgery recovery',
                'starts_at'  => now()->subDays(5),
                'ends_at'    => now()->addDays(10),
                'created_by' => $manager->id,
            ]
        );

        $this->command->info('Set Neha on medical leave');

        // ════════════════════════════════════════
        // FAIRNESS FLAGS
        // Pre-generated flags showing bias
        // ════════════════════════════════════════

        // Flag 1: Assignment bias against Sarah
        FairnessFlag::firstOrCreate(
            ['organization_id' => $org->id, 'flagged_user_id' => $manager->id, 'flag_type' => 'assignment_bias'],
            [
                'flagged_by_user_id' => null,
                'confidence_score'   => 0.9200,
                'layer'              => 2,
                'status'             => 'pending',
                'evidence'           => [
                    'description'             => 'Manager consistently assigns high-difficulty low-visibility tasks to Sarah Singh while Alex Kumar receives easy high-visibility tasks.',
                    'pattern_period'          => '30 days',
                    'alex_avg_difficulty'     => 1.7,
                    'alex_avg_visibility'     => 9.7,
                    'sarah_avg_difficulty'    => 9.0,
                    'sarah_avg_visibility'    => 2.0,
                    'statistical_significance' => '94% confidence',
                    'tasks_analyzed'          => 7,
                ],
            ]
        );

        // Flag 2: Workload imbalance (Sarah 3.3x Alex)
        FairnessFlag::firstOrCreate(
            ['organization_id' => $org->id, 'flagged_user_id' => $personB->id, 'flag_type' => 'workload_imbalance'],
            [
                'flagged_by_user_id' => null,
                'confidence_score'   => 0.8800,
                'layer'              => 1,
                'status'             => 'pending',
                'evidence'           => [
                    'description'          => 'Significant workload imbalance detected. Sarah Singh has 3.3x more commits than Alex Kumar over the last 30 days with 3x higher complexity.',
                    'sarah_commits'        => 20,
                    'alex_commits'         => 6,
                    'ratio'                => '3.3x',
                    'period'               => '30 days',
                    'sarah_complexity_avg' => 2.1,
                    'alex_complexity_avg'  => 0.7,
                ],
            ]
        );

        // Flag 3: Repeated ownership avoidance pattern (Alex)
        FairnessFlag::firstOrCreate(
            ['organization_id' => $org->id, 'flagged_user_id' => $personA->id, 'flag_type' => 'ownership_dispute_pattern'],
            [
                'flagged_by_user_id' => null,
                'confidence_score'   => 0.9500,
                'layer'              => 3,
                'status'             => 'pending',
                'evidence'           => [
                    'description'   => 'Alex Kumar has 2 ownership disputes in 14 days. Pattern suggests repeated avoidance of assigned responsibilities.',
                    'dispute_count' => 2,
                    'period_days'   => 14,
                    'disputes'      => [
                        'API specs assignment — disputed on Day 5',
                        'Code review responsibility — disputed on Day 11',
                    ],
                    'impact' => 'Sarah Singh blocked for a combined 24 developer-days.',
                ],
            ]
        );

        $this->command->info('Created 3 fairness flags');

        // ════════════════════════════════════════
        // SUMMARY
        // ════════════════════════════════════════

        $this->command->info('');
        $this->command->info('✅ Test data seeded successfully!');
        $this->command->info('');
        $this->command->info('Test accounts (password: password):');
        $this->command->info('  Manager (admin)     : manager@techcorp.com');
        $this->command->info('  Team Lead           : teamlead@techcorp.com');
        $this->command->info('  Alex (favourite)    : alex@techcorp.com');
        $this->command->info('  Sarah (hard worker) : sarah@techcorp.com');
        $this->command->info('  Marcus              : dev.marcus@techcorp.com');
        $this->command->info('  Neha (on leave)     : neha@techcorp.com');
        $this->command->info('');
        $this->command->info('Data seeded:');
        $this->command->info('  5 team members with Spatie roles');
        $this->command->info('  3 GitHub projects');
        $this->command->info('  44 GitHub activities (3.3x Sarah vs Alex)');
        $this->command->info('  8 enhanced tasks with comments and history');
        $this->command->info('  5 blockers (2 disputed, 1 external vendor, 2 cross-team)');
        $this->command->info('  3 fairness flags (assignment bias, workload imbalance, dispute pattern)');
        $this->command->info('  1 employee on medical leave');

        // ════════════════════════════════════════
        // INCREMENT POLICY
        // ════════════════════════════════════════

        $creator = User::where('organization_id', $org->id)->first();

        $policy = IncrementPolicy::firstOrCreate(
            ['organization_id' => $org->id, 'is_active' => true],
            [
                'name'                          => '2026 Annual Increment Policy',
                'max_increment_percent'         => 30.00,
                'review_period'                 => 'annual',
                'review_month'                  => 12,
                'minimum_months_required'       => 3,
                'minimum_score_for_increment'   => 40.00,
                'anti_gaming_enabled'           => true,
                'created_by'                    => $creator->id,
            ]
        );

        $criteriaData = [
            ['name'=>'github_commits','label'=>'GitHub Commits','type'=>'automatic','source'=>'github_commits','weight'=>8.00],
            ['name'=>'github_prs','label'=>'Pull Requests','type'=>'automatic','source'=>'github_prs','weight'=>5.00],
            ['name'=>'task_complexity','label'=>'Task Complexity','type'=>'automatic','source'=>'task_complexity','weight'=>7.00],
            ['name'=>'work_logs','label'=>'Work Log Consistency','type'=>'automatic','source'=>'work_logs','weight'=>5.00],
            ['name'=>'blocker_resolution','label'=>'Blocker Resolution','type'=>'automatic','source'=>'blocker_resolution','weight'=>3.00],
            ['name'=>'attendance','label'=>'Attendance','type'=>'automatic','source'=>'attendance','weight'=>2.00],
        ];

        foreach ($criteriaData as $c) {
            IncrementCriteria::firstOrCreate(
                ['policy_id' => $policy->id, 'criteria_name' => $c['name']],
                [
                    'policy_id'       => $policy->id,
                    'organization_id' => $org->id,
                    'criteria_name'   => $c['name'],
                    'criteria_label'  => $c['label'],
                    'criteria_type'   => $c['type'],
                    'data_source'     => $c['source'],
                    'weight_percent'  => $c['weight'],
                    'is_active'       => true,
                ]
            );
        }

        $this->command->info('  1 increment policy with 6 criteria');
    }
}
