<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\MetricEntry;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Database\Seeder;

class WorkLogSeeder extends Seeder
{
    public function run(): void
    {
        $org  = Organization::where('slug', 'fees-admin')->first();
        if (!$org) {
            $this->command->warn('Organization not found.');
            return;
        }

        $engineering = Department::where('organization_id', $org->id)->where('slug', 'engineering')->first();
        if (!$engineering) {
            $this->command->warn('Engineering dept not found. Run DepartmentSeeder first.');
            return;
        }

        $projects = Project::where('organization_id', $org->id)->pluck('id')->toArray();
        $projectId = $projects[0] ?? null;

        $users = [
            'sarah'   => User::where('email', 'sarah@techcorp.com')->first(),
            'alex'    => User::where('email', 'alex@techcorp.com')->first(),
            'marcus'  => User::where('email', 'dev.marcus@techcorp.com')->first(),
            'manager' => User::where('email', 'manager@techcorp.com')->first(),
        ];

        // Remove nulls
        $users = array_filter($users);

        $this->seedSarahLogs($users['sarah'] ?? null, $org, $engineering, $projectId);
        $this->seedAlexLogs($users['alex'] ?? null, $org, $engineering, $projectId);
        $this->seedMarcusLogs($users['marcus'] ?? null, $org, $engineering, $projectId);
        $this->seedManagerLogs($users['manager'] ?? null, $org, $engineering, $projectId);

        $this->seedMetricEntries($org, $engineering, $users);

        $count = WorkLog::where('organization_id', $org->id)->count();
        $this->command->info("Work logs seeded: {$count} total entries");
    }

    private function seedSarahLogs(?User $sarah, $org, $dept, ?int $projectId): void
    {
        if (!$sarah) return;

        $logs = [
            // Day 1 (6 days ago)
            ['days_ago' => 6, 'category' => 'development',   'title' => 'Payment gateway integration',      'duration' => 180, 'output' => 9],
            ['days_ago' => 6, 'category' => 'meeting',        'title' => 'Sprint planning session',          'duration' => 60,  'output' => 7],
            ['days_ago' => 6, 'category' => 'code_review',    'title' => 'Reviewed PR #42 — auth refactor',  'duration' => 60,  'output' => 8],
            ['days_ago' => 6, 'category' => 'development',    'title' => 'Security fix implementation',      'duration' => 120, 'output' => 10],
            // Day 2
            ['days_ago' => 5, 'category' => 'development',    'title' => 'API rate limiting implementation', 'duration' => 150, 'output' => 9],
            ['days_ago' => 5, 'category' => 'testing',        'title' => 'Unit tests for payment module',    'duration' => 90,  'output' => 8],
            ['days_ago' => 5, 'category' => 'documentation',  'title' => 'API docs for payment endpoints',   'duration' => 60,  'output' => 7],
            ['days_ago' => 5, 'category' => 'code_review',    'title' => 'Code review for DB migration',     'duration' => 45,  'output' => 8],
            // Day 3
            ['days_ago' => 4, 'category' => 'development',    'title' => 'Notification service refactor',    'duration' => 200, 'output' => 9],
            ['days_ago' => 4, 'category' => 'meeting',        'title' => 'Architecture review with team',    'duration' => 60,  'output' => 8],
            ['days_ago' => 4, 'category' => 'research',       'title' => 'Evaluated caching solutions',      'duration' => 90,  'output' => 7],
            // Day 4
            ['days_ago' => 3, 'category' => 'development',    'title' => 'Redis cache integration',          'duration' => 180, 'output' => 10],
            ['days_ago' => 3, 'category' => 'testing',        'title' => 'Integration tests for cache layer','duration' => 75,  'output' => 9],
            ['days_ago' => 3, 'category' => 'code_review',    'title' => 'Reviewed Alex PR — trivial fixes', 'duration' => 30,  'output' => 5],
            ['days_ago' => 3, 'category' => 'documentation',  'title' => 'Updated deployment runbook',       'duration' => 45,  'output' => 7],
            // Day 5
            ['days_ago' => 2, 'category' => 'development',    'title' => 'Webhook system implementation',    'duration' => 240, 'output' => 9],
            ['days_ago' => 2, 'category' => 'meeting',        'title' => 'Client demo preparation',          'duration' => 60,  'output' => 7],
            ['days_ago' => 2, 'category' => 'code_review',    'title' => 'Reviewed Marcus PR #47',           'duration' => 60,  'output' => 8],
            // Day 6 (yesterday)
            ['days_ago' => 1, 'category' => 'development',    'title' => 'Bug fixes from QA round',          'duration' => 150, 'output' => 8],
            ['days_ago' => 1, 'category' => 'testing',        'title' => 'QA regression testing',            'duration' => 90,  'output' => 9],
            ['days_ago' => 1, 'category' => 'planning',       'title' => 'Sprint 12 planning notes',         'duration' => 45,  'output' => 7],
            ['days_ago' => 1, 'category' => 'code_review',    'title' => 'Final review before release',      'duration' => 60,  'output' => 9],
        ];

        $this->createLogs($sarah, $org, $dept, $projectId, $logs);
    }

    private function seedAlexLogs(?User $alex, $org, $dept, ?int $projectId): void
    {
        if (!$alex) return;

        $logs = [
            // Day 1
            ['days_ago' => 6, 'category' => 'meeting',     'title' => 'Demo preparation with CEO',         'duration' => 120, 'output' => 6],
            ['days_ago' => 6, 'category' => 'planning',    'title' => 'Sprint planning attendance',         'duration' => 60,  'output' => 5],
            ['days_ago' => 6, 'category' => 'development', 'title' => 'Update readme files',               'duration' => 30,  'output' => 3],
            // Day 2
            ['days_ago' => 5, 'category' => 'meeting',     'title' => 'Weekly sync with manager',          'duration' => 60,  'output' => 5],
            ['days_ago' => 5, 'category' => 'development', 'title' => 'Minor UI text changes',             'duration' => 45,  'output' => 3],
            // Day 3
            ['days_ago' => 4, 'category' => 'meeting',     'title' => 'Stakeholder presentation prep',     'duration' => 90,  'output' => 6],
            ['days_ago' => 4, 'category' => 'planning',    'title' => 'Quarterly goal review doc',         'duration' => 60,  'output' => 5],
            // Day 4
            ['days_ago' => 3, 'category' => 'meeting',     'title' => 'All-hands meeting attendance',      'duration' => 60,  'output' => 4],
            ['days_ago' => 3, 'category' => 'development', 'title' => 'Fix typo in dashboard label',       'duration' => 15,  'output' => 2],
            // Day 5
            ['days_ago' => 2, 'category' => 'meeting',     'title' => 'One-on-one with manager',           'duration' => 45,  'output' => 6],
            ['days_ago' => 2, 'category' => 'planning',    'title' => 'Sprint retrospective notes',        'duration' => 60,  'output' => 5],
            // Day 6
            ['days_ago' => 1, 'category' => 'meeting',     'title' => 'Team lunch coordination',           'duration' => 30,  'output' => 3],
            ['days_ago' => 1, 'category' => 'development', 'title' => 'Update package.json version',       'duration' => 20,  'output' => 2],
        ];

        $this->createLogs($alex, $org, $dept, $projectId, $logs);
    }

    private function seedMarcusLogs(?User $marcus, $org, $dept, ?int $projectId): void
    {
        if (!$marcus) return;

        $logs = [
            ['days_ago' => 6, 'category' => 'development',   'title' => 'REST API endpoints for user module',   'duration' => 150, 'output' => 8],
            ['days_ago' => 6, 'category' => 'testing',       'title' => 'API endpoint unit tests',              'duration' => 60,  'output' => 7],
            ['days_ago' => 5, 'category' => 'development',   'title' => 'Database query optimization',          'duration' => 120, 'output' => 8],
            ['days_ago' => 5, 'category' => 'documentation', 'title' => 'Swagger docs for new endpoints',       'duration' => 60,  'output' => 7],
            ['days_ago' => 4, 'category' => 'development',   'title' => 'Authentication middleware update',     'duration' => 90,  'output' => 8],
            ['days_ago' => 4, 'category' => 'meeting',       'title' => 'Technical review session',             'duration' => 60,  'output' => 7],
            ['days_ago' => 3, 'category' => 'development',   'title' => 'File upload service implementation',   'duration' => 180, 'output' => 9],
            ['days_ago' => 3, 'category' => 'testing',       'title' => 'End-to-end file upload tests',         'duration' => 60,  'output' => 8],
            ['days_ago' => 2, 'category' => 'development',   'title' => 'Error handling improvements',          'duration' => 90,  'output' => 7],
            ['days_ago' => 2, 'category' => 'code_review',   'title' => 'Reviewed Sarah PR #52',                'duration' => 45,  'output' => 7],
            ['days_ago' => 1, 'category' => 'development',   'title' => 'Role-based access control fixes',      'duration' => 120, 'output' => 8],
            ['days_ago' => 1, 'category' => 'documentation', 'title' => 'Updated architecture diagram',        'duration' => 45,  'output' => 6],
        ];

        $this->createLogs($marcus, $org, $dept, $projectId, $logs);
    }

    private function seedManagerLogs(?User $manager, $org, $dept, ?int $projectId): void
    {
        if (!$manager) return;

        $logs = [
            ['days_ago' => 6, 'category' => 'meeting',   'title' => 'Sprint planning facilitation',         'duration' => 90,  'output' => 8],
            ['days_ago' => 6, 'category' => 'planning',  'title' => 'Q2 roadmap review',                    'duration' => 120, 'output' => 9],
            ['days_ago' => 5, 'category' => 'meeting',   'title' => 'Stakeholder status update call',       'duration' => 60,  'output' => 8],
            ['days_ago' => 5, 'category' => 'reporting', 'title' => 'Weekly performance report',            'duration' => 90,  'output' => 8],
            ['days_ago' => 4, 'category' => 'meeting',   'title' => 'One-on-ones with team members',        'duration' => 120, 'output' => 9],
            ['days_ago' => 4, 'category' => 'planning',  'title' => 'Hiring plan for Q3',                   'duration' => 90,  'output' => 8],
            ['days_ago' => 3, 'category' => 'meeting',   'title' => 'Cross-team dependency resolution',     'duration' => 60,  'output' => 7],
            ['days_ago' => 3, 'category' => 'reporting', 'title' => 'Fairness analysis review',             'duration' => 60,  'output' => 8],
            ['days_ago' => 2, 'category' => 'meeting',   'title' => 'Board update preparation',             'duration' => 90,  'output' => 9],
            ['days_ago' => 2, 'category' => 'planning',  'title' => 'Team capacity planning',               'duration' => 60,  'output' => 8],
            ['days_ago' => 1, 'category' => 'meeting',   'title' => 'Sprint retrospective',                 'duration' => 60,  'output' => 8],
            ['days_ago' => 1, 'category' => 'planning',  'title' => 'Next sprint goal setting',             'duration' => 45,  'output' => 9],
        ];

        $this->createLogs($manager, $org, $dept, $projectId, $logs);
    }

    private function createLogs(User $user, $org, $dept, ?int $projectId, array $logs): void
    {
        foreach ($logs as $log) {
            $date = now()->subDays($log['days_ago'])->toDateString();
            $exists = WorkLog::where('user_id', $user->id)
                ->where('log_date', $date)
                ->where('title', $log['title'])
                ->exists();

            if (!$exists) {
                WorkLog::create([
                    'user_id'          => $user->id,
                    'organization_id'  => $org->id,
                    'department_id'    => $dept->id,
                    'project_id'       => $projectId,
                    'log_date'         => $date,
                    'category'         => $log['category'],
                    'title'            => $log['title'],
                    'duration_minutes' => $log['duration'],
                    'output_value'     => $log['output'],
                    'is_billable'      => false,
                ]);
            }
        }
    }

    private function seedMetricEntries($org, $dept, array $users): void
    {
        $metrics = DepartmentMetric::where('department_id', $dept->id)->get()->keyBy('metric_name');

        $entries = [];

        if (isset($users['sarah']) && isset($metrics['bugs_fixed'])) {
            for ($i = 5; $i >= 1; $i--) {
                $entries[] = [
                    'user'       => $users['sarah'],
                    'metric'     => $metrics['bugs_fixed'],
                    'value'      => rand(3, 6),
                    'days_ago'   => $i,
                    'notes'      => 'Bugs fixed during development',
                ];
            }
        }

        if (isset($users['sarah']) && isset($metrics['features_completed'])) {
            for ($i = 5; $i >= 1; $i--) {
                $entries[] = [
                    'user'       => $users['sarah'],
                    'metric'     => $metrics['features_completed'],
                    'value'      => rand(1, 2),
                    'days_ago'   => $i,
                    'notes'      => 'Features delivered this day',
                ];
            }
        }

        if (isset($users['marcus']) && isset($metrics['code_reviews'])) {
            for ($i = 5; $i >= 1; $i--) {
                $entries[] = [
                    'user'       => $users['marcus'],
                    'metric'     => $metrics['code_reviews'],
                    'value'      => rand(1, 3),
                    'days_ago'   => $i,
                    'notes'      => 'PRs reviewed',
                ];
            }
        }

        foreach ($entries as $e) {
            $date = now()->subDays($e['days_ago'])->toDateString();
            $exists = MetricEntry::where('user_id', $e['user']->id)
                ->where('metric_id', $e['metric']->id)
                ->where('entry_date', $date)
                ->exists();

            if (!$exists) {
                MetricEntry::create([
                    'user_id'         => $e['user']->id,
                    'organization_id' => $org->id,
                    'department_id'   => $dept->id,
                    'metric_id'       => $e['metric']->id,
                    'value'           => $e['value'],
                    'entry_date'      => $date,
                    'notes'           => $e['notes'],
                ]);
            }
        }
    }
}
