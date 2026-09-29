<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name'        => 'github_sync',
                'label'       => 'GitHub Sync',
                'description' => 'Connect GitHub repositories and track commits, PRs and code reviews',
                'icon'        => 'github',
                'is_active'   => true,
            ],
            [
                'name'        => 'fairness_engine',
                'label'       => 'Fairness Engine',
                'description' => 'AI-powered bias detection, workload analysis and fairness flags',
                'icon'        => 'shield',
                'is_active'   => true,
            ],
            [
                'name'        => 'ai_intelligence',
                'label'       => 'AI Intelligence',
                'description' => 'Executive summaries, natural language queries and team insights',
                'icon'        => 'cpu',
                'is_active'   => true,
            ],
            [
                'name'        => 'blockers',
                'label'       => 'Blockers & Dependencies',
                'description' => 'Track what is blocking your team and manage dependencies',
                'icon'        => 'alert',
                'is_active'   => true,
            ],
            [
                'name'        => 'command_center',
                'label'       => 'Command Center',
                'description' => 'CEO/Leader view with full team output and performance data',
                'icon'        => 'monitor',
                'is_active'   => true,
            ],
            [
                'name'        => 'reports',
                'label'       => 'Reports & Analytics',
                'description' => 'Export team performance reports and analytics',
                'icon'        => 'chart',
                'is_active'   => true,
            ],
            [
                'name'        => 'api_access',
                'label'       => 'API Access',
                'description' => 'REST API access for integrations and custom tools',
                'icon'        => 'code',
                'is_active'   => true,
            ],
        ];

        foreach ($modules as $data) {
            Module::firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
