<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManagerRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view_dashboard',
            'view_projects', 'create_projects', 'edit_projects',
            'view_team', 'invite_members', 'remove_members',
            'sync_github', 'view_activities',
            'view_fairness', 'run_fairness_analysis', 'confirm_flags', 'dismiss_flags',
            'view_blockers', 'create_blockers', 'resolve_blockers', 'escalate_blockers',
            'manage_employee_status',
            'view_ai', 'query_ai',
            'view_command_center',
            'view_reports', 'export_reports',
        ]);

        $rahul = User::where('email', 'manager@techcorp.com')->first();
        if ($rahul) {
            $rahul->syncRoles(['manager']);
            $this->command->info('Assigned manager role to: ' . $rahul->name . ' (' . $rahul->email . ')');
        } else {
            $this->command->warn('User manager@techcorp.com not found — run TestDataSeeder first, then re-run this seeder');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('');
        $this->command->info('Manager role seeded successfully!');
        $this->command->info('Hierarchy: super_admin → owner/admin → manager → team_lead → hr → employee');
        $this->command->info('');
        $this->command->info('Test account: manager@techcorp.com (password: password)');
    }
}
