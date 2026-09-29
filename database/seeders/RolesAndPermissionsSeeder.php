<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ════════════════════════════════
        // CREATE ALL PERMISSIONS
        // ════════════════════════════════

        $permissions = [
            // Dashboard
            'view_dashboard',

            // Projects
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',

            // Team
            'view_team',
            'invite_members',
            'remove_members',
            'update_member_roles',

            // GitHub
            'sync_github',
            'view_activities',

            // Fairness Engine
            'view_fairness',
            'run_fairness_analysis',
            'confirm_flags',
            'dismiss_flags',

            // Blockers & Dependencies
            'view_blockers',
            'create_blockers',
            'resolve_blockers',
            'escalate_blockers',
            'manage_employee_status',

            // AI Intelligence
            'view_ai',
            'query_ai',
            'refresh_ai_summary',

            // Command Center (CEO view)
            'view_command_center',

            // Settings
            'view_settings',
            'edit_org_settings',
            'manage_github_settings',
            'manage_billing',
            'manage_modules',

            // Reports
            'view_reports',
            'export_reports',

            // Admin
            'manage_roles',
            'view_audit_logs',
            'manage_organization',

            // Super Admin
            'access_super_admin',
            'view_all_organizations',
            'impersonate_users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ════════════════════════════════
        // CREATE ROLES & ASSIGN PERMISSIONS
        // ════════════════════════════════

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions(Permission::all());

        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->syncPermissions([
            'view_dashboard',
            'view_projects', 'create_projects', 'edit_projects', 'delete_projects',
            'view_team', 'invite_members', 'remove_members', 'update_member_roles',
            'sync_github', 'view_activities',
            'view_fairness', 'run_fairness_analysis', 'confirm_flags', 'dismiss_flags',
            'view_blockers', 'create_blockers', 'resolve_blockers', 'escalate_blockers',
            'manage_employee_status',
            'view_ai', 'query_ai', 'refresh_ai_summary',
            'view_command_center',
            'view_settings', 'edit_org_settings', 'manage_github_settings',
            'manage_billing', 'manage_modules',
            'view_reports', 'export_reports',
            'manage_roles', 'view_audit_logs', 'manage_organization',
        ]);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'view_dashboard',
            'view_projects', 'create_projects', 'edit_projects',
            'view_team', 'invite_members', 'remove_members',
            'sync_github', 'view_activities',
            'view_fairness', 'run_fairness_analysis', 'confirm_flags', 'dismiss_flags',
            'view_blockers', 'create_blockers', 'resolve_blockers', 'escalate_blockers',
            'manage_employee_status',
            'view_ai', 'query_ai', 'refresh_ai_summary',
            'view_command_center',
            'view_settings', 'manage_github_settings',
            'view_reports', 'export_reports',
            'view_audit_logs',
        ]);

        $teamLead = Role::firstOrCreate(['name' => 'team_lead']);
        $teamLead->syncPermissions([
            'view_dashboard',
            'view_projects', 'create_projects', 'edit_projects',
            'view_team', 'invite_members',
            'sync_github', 'view_activities',
            'view_fairness', 'run_fairness_analysis', 'confirm_flags', 'dismiss_flags',
            'view_blockers', 'create_blockers', 'resolve_blockers', 'escalate_blockers',
            'manage_employee_status',
            'view_ai', 'query_ai',
            'view_command_center',
            'view_reports',
        ]);

        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->syncPermissions([
            'view_dashboard',
            'view_projects',
            'view_team',
            'view_activities',
            'view_blockers', 'create_blockers',
            'view_ai',
            'view_reports',
        ]);

        $viewer = Role::firstOrCreate(['name' => 'viewer']);
        $viewer->syncPermissions([
            'view_dashboard',
            'view_projects',
            'view_team',
            'view_activities',
            'view_reports',
        ]);

        // ════════════════════════════════
        // ASSIGN ROLES TO EXISTING USERS
        // ════════════════════════════════

        User::all()->each(function ($user) {
            $roleMap = [
                'owner'     => 'owner',
                'admin'     => 'admin',
                'team_lead' => 'team_lead',
                'employee'  => 'employee',
                'viewer'    => 'viewer',
            ];

            $spatieRole = $roleMap[$user->role] ?? 'employee';

            if (!$user->hasRole($spatieRole)) {
                $user->assignRole($spatieRole);
            }
        });

        $this->command->info('Roles and permissions seeded successfully!');
    }
}
