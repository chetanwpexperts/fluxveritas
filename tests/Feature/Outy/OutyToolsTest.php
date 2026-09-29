<?php

namespace Tests\Feature\Outy;

use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Project;
use App\Models\Task;
use App\Services\Outy\ToolDeniedException;
use App\Services\Outy\ToolRegistry;
use App\Services\Outy\Tools\ExplainFeature;
use Illuminate\Support\Facades\File;

class OutyToolsTest extends OutyTestCase
{
    private function useTool(string $tool, $user, array $args = []): array
    {
        return app(ToolRegistry::class)->all()->get($tool)->run($user, $args);
    }

    public function test_my_tasks_returns_only_the_users_own_open_tasks(): void
    {
        $me      = $this->person('Me', 'employee');
        $other   = $this->person('Other', 'employee');
        $project = Project::create(['organization_id' => $this->org->id, 'name' => 'P']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'assigned_by' => $me->id, 'title' => 'Mine open', 'status' => 'todo', 'ticket_number' => 'T-1']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $me->id, 'assigned_by' => $me->id, 'title' => 'Mine done', 'status' => 'done', 'ticket_number' => 'T-2']);
        Task::create(['project_id' => $project->id, 'assigned_to' => $other->id, 'assigned_by' => $me->id, 'title' => 'Theirs', 'status' => 'todo', 'ticket_number' => 'T-3']);

        $result = $this->useTool('get_my_tasks', $me);

        $this->assertSame(['Mine open'], array_column($result['tasks'], 'title'));
    }

    public function test_leave_balance_is_the_users_own(): void
    {
        $me    = $this->person('Me', 'employee');
        $other = $this->person('Other', 'employee');
        $type  = LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual', 'code' => 'CL', 'days_per_year' => 12, 'is_active' => true]);
        LeaveBalance::create(['user_id' => $me->id, 'leave_type_id' => $type->id, 'organization_id' => $this->org->id, 'year' => now()->year, 'allocated' => 12, 'used' => 2, 'pending' => 0, 'carried_forward' => 0]);
        LeaveBalance::create(['user_id' => $other->id, 'leave_type_id' => $type->id, 'organization_id' => $this->org->id, 'year' => now()->year, 'allocated' => 12, 'used' => 9, 'pending' => 0, 'carried_forward' => 0]);

        $this->assertSame(10.0, $this->useTool('get_my_leave_balance', $me)['balances'][0]['left']);
    }

    public function test_run_rechecks_permission_even_when_called_directly(): void
    {
        $this->expectException(ToolDeniedException::class);
        $this->useTool('get_org_stats', $this->person('Me', 'employee'));
    }

    public function test_org_stats_for_owner(): void
    {
        $owner = $this->person('Boss', 'owner');
        $this->person('Staff', 'employee');
        $this->person('Newbie', 'viewer', null, ['onboarding_status' => 'pending']);

        $stats = $this->useTool('get_org_stats', $owner);

        $this->assertSame(2, $stats['headcount_active']);
        $this->assertSame(1, $stats['pending_approvals']);
        $this->assertSame('pro', $stats['plan']);
        $this->assertContains('Fairness Engine', $stats['enabled_modules']);
    }

    public function test_system_health_shows_messages_to_super_admin_only(): void
    {
        $dir = storage_path('framework/testing/outy-health-' . uniqid());
        File::ensureDirectoryExists("{$dir}/logs");
        File::put("{$dir}/logs/laravel.log", implode("\n", [
            '[' . now()->subHours(2)->format('Y-m-d H:i:s') . '] production.ERROR: Payment webhook failed for order 42 {"exception":"[object] (RuntimeException(code: 0): x at /app/Foo.php:10)"}',
            '#0 /app/vendor/stack/trace.php(12): something()',
            '[' . now()->subHour()->format('Y-m-d H:i:s') . '] production.ERROR: Payment webhook failed for order 42 {"exception":"..."}',
            '[' . now()->subDays(3)->format('Y-m-d H:i:s') . '] production.ERROR: Old error',
            '[' . now()->subMinutes(5)->format('Y-m-d H:i:s') . '] production.INFO: Not an error',
        ]));
        $this->app->useStoragePath($dir);

        $owner = $this->person('Boss', 'owner');
        $sa    = $this->person('Platform', 'super_admin', null, ['organization_id' => null]);

        $forOwner = $this->useTool('get_system_health', $owner);
        $this->assertSame(2, $forOwner['errors_last_24h']);
        $this->assertTrue($forOwner['database_ok']);
        $this->assertArrayNotHasKey('top_errors', $forOwner, 'Owners must not see platform log messages');

        $forSa = $this->useTool('get_system_health', $sa);
        $this->assertSame([['message' => 'Payment webhook failed for order 42', 'count' => 2]], $forSa['top_errors']);
        $this->assertStringNotContainsString('stack', json_encode($forSa));

        File::deleteDirectory($dir);
    }

    public function test_explain_feature_returns_the_guide(): void
    {
        $result = $this->useTool('explain_feature', $this->person('Me', 'employee'), ['name' => 'leave']);

        $this->assertSame('leave', $result['topic']);
        $this->assertStringContainsString('Apply for leave', $result['guide']);
    }

    public function test_explain_feature_lists_topics_for_unknown_names(): void
    {
        $result = $this->useTool('explain_feature', $this->person('Me', 'employee'), ['name' => '../../.env']);

        $this->assertSame('Unknown topic.', $result['error']);
        $this->assertContains('billing', $result['available_topics']);
    }

    public function test_every_module_has_a_knowledge_file(): void
    {
        $expected = [
            'ai-intel', 'announcements', 'billing', 'blockers', 'command-center', 'dashboard', 'directory', 'documents',
            'fairness', 'feedback', 'getting-started', 'github-sync', 'hr-management', 'increments', 'inviting-people',
            'leave', 'notifications', 'onboarding-checklists', 'org-chart', 'outy', 'platform-admin', 'projects',
            'reports', 'roles-and-permissions', 'settings', 'tasks-and-sprints', 'teams-and-departments',
            'timesheets-expenses-assets-payroll', 'work-log',
        ];

        $this->assertSame($expected, ExplainFeature::topics());
        foreach ($expected as $topic) {
            $this->assertGreaterThan(200, strlen(file_get_contents(base_path("docs/outy/{$topic}.md"))), "{$topic}.md is too short");
        }
    }
}
