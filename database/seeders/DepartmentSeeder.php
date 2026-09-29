<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentMetric;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('slug', 'fees-admin')->first();
        if (!$org) {
            $this->command->warn('Organization fees-admin not found. Skipping.');
            return;
        }

        $departments = [
            [
                'name'        => 'Engineering',
                'slug'        => 'engineering',
                'type'        => 'tech',
                'work_mode'   => 'hybrid',
                'color'       => '#3b82f6',
                'description' => 'Software development team',
            ],
            [
                'name'        => 'Sales',
                'slug'        => 'sales',
                'type'        => 'sales',
                'work_mode'   => 'manual',
                'color'       => '#10b981',
                'description' => 'Sales and business development',
            ],
            [
                'name'        => 'Human Resources',
                'slug'        => 'hr',
                'type'        => 'hr',
                'work_mode'   => 'manual',
                'color'       => '#f59e0b',
                'description' => 'People and culture team',
            ],
            [
                'name'        => 'Finance',
                'slug'        => 'finance',
                'type'        => 'finance',
                'work_mode'   => 'manual',
                'color'       => '#8b5cf6',
                'description' => 'Finance and accounting team',
            ],
            [
                'name'        => 'Operations',
                'slug'        => 'operations',
                'type'        => 'operations',
                'work_mode'   => 'manual',
                'color'       => '#ef4444',
                'description' => 'Operations and logistics team',
            ],
        ];

        $createdDepts = [];
        foreach ($departments as $data) {
            $dept = Department::firstOrCreate(
                ['organization_id' => $org->id, 'slug' => $data['slug']],
                array_merge($data, ['organization_id' => $org->id])
            );
            $createdDepts[$data['slug']] = $dept;
        }

        $this->seedMetrics($org->id, $createdDepts);

        $engineeringDept = $createdDepts['engineering'];
        User::whereIn('email', [
            'manager@techcorp.com',
            'teamlead@techcorp.com',
            'alex@techcorp.com',
            'sarah@techcorp.com',
            'dev.marcus@techcorp.com',
            'neha@techcorp.com',
        ])->update(['department_id' => $engineeringDept->id]);

        $this->command->info('Departments seeded: ' . count($createdDepts));
        $this->command->info('Test users assigned to Engineering department.');
    }

    private function seedMetrics(int $orgId, array $depts): void
    {
        $metrics = [
            'engineering' => [
                ['metric_name' => 'bugs_fixed',          'metric_label' => 'Bugs Fixed',          'metric_type' => 'count'],
                ['metric_name' => 'features_completed',  'metric_label' => 'Features Completed',  'metric_type' => 'count'],
                ['metric_name' => 'code_reviews',        'metric_label' => 'Code Reviews Done',   'metric_type' => 'count'],
            ],
            'sales' => [
                ['metric_name' => 'calls_made',          'metric_label' => 'Calls Made',          'metric_type' => 'count'],
                ['metric_name' => 'meetings_held',       'metric_label' => 'Meetings Held',       'metric_type' => 'count'],
                ['metric_name' => 'deals_closed',        'metric_label' => 'Deals Closed',        'metric_type' => 'count'],
                ['metric_name' => 'revenue_generated',   'metric_label' => 'Revenue Generated',   'metric_type' => 'currency'],
            ],
            'hr' => [
                ['metric_name' => 'interviews_conducted','metric_label' => 'Interviews Done',     'metric_type' => 'count'],
                ['metric_name' => 'positions_filled',    'metric_label' => 'Positions Filled',    'metric_type' => 'count'],
                ['metric_name' => 'training_sessions',   'metric_label' => 'Training Sessions',   'metric_type' => 'count'],
                ['metric_name' => 'policies_updated',    'metric_label' => 'Policies Updated',    'metric_type' => 'count'],
            ],
            'finance' => [
                ['metric_name' => 'reports_prepared',    'metric_label' => 'Reports Prepared',    'metric_type' => 'count'],
                ['metric_name' => 'invoices_processed',  'metric_label' => 'Invoices Processed',  'metric_type' => 'count'],
                ['metric_name' => 'audits_completed',    'metric_label' => 'Audits Completed',    'metric_type' => 'count'],
                ['metric_name' => 'reconciliations',     'metric_label' => 'Reconciliations Done','metric_type' => 'count'],
            ],
            'operations' => [
                ['metric_name' => 'site_visits',         'metric_label' => 'Site Visits',         'metric_type' => 'count'],
                ['metric_name' => 'issues_resolved',     'metric_label' => 'Issues Resolved',     'metric_type' => 'count'],
                ['metric_name' => 'vendor_meetings',     'metric_label' => 'Vendor Meetings',     'metric_type' => 'count'],
            ],
        ];

        foreach ($metrics as $slug => $deptMetrics) {
            if (!isset($depts[$slug])) continue;
            $dept = $depts[$slug];
            foreach ($deptMetrics as $m) {
                DepartmentMetric::firstOrCreate(
                    ['department_id' => $dept->id, 'metric_name' => $m['metric_name']],
                    array_merge($m, ['department_id' => $dept->id, 'organization_id' => $orgId])
                );
            }
        }
    }
}
