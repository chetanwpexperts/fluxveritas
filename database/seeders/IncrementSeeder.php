<?php
namespace Database\Seeders;

use App\Models\IncrementCriteria;
use App\Models\IncrementPolicy;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncrementSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::first();
        if (!$org) return;

        $creator = User::where('organization_id', $org->id)->first();
        if (!$creator) return;

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

        $this->command->info('Increment policy seeded.');
    }
}
