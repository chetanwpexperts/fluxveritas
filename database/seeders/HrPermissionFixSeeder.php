<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class HrPermissionFixSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permissions every role must have to use the app
        $basePermissions = [
            'view_dashboard',
            'view_profile',
        ];

        foreach ($basePermissions as $permName) {
            Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);
        }

        // Give base permissions to ALL existing roles
        // This ensures no existing role breaks
        $allRoles = [
            'super_admin',
            'owner',
            'admin',
            'hr',
            'team_lead',
            'employee',
        ];

        foreach ($allRoles as $roleName) {
            $role = Role::where('name', $roleName)
                        ->where('guard_name', 'web')
                        ->first();
            if ($role) {
                $role->givePermissionTo($basePermissions);
            }
        }

        // HR-specific permissions (add on top of base)
        $hrPermissions = [
            'manage_hr_roles',
            'view_all_employees',
            'edit_employee_profiles',
            'manage_all_leaves',
            'create_announcements',
            'manage_documents',
            'view_hr_reports',
            'invite_employees',
            'offboard_employees',
        ];

        foreach ($hrPermissions as $permName) {
            Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);
        }

        $hrRole = Role::where('name', 'hr')
                      ->where('guard_name', 'web')
                      ->first();
        if ($hrRole) {
            $hrRole->givePermissionTo($hrPermissions);
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command->info('HR permissions fixed. All existing roles untouched.');
    }
}
