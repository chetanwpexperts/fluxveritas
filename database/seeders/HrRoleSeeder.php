<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class HrRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        foreach ($hrPermissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->givePermissionTo($hrPermissions);

        $admin = Role::findByName('admin');
        $admin->givePermissionTo([
            'view_all_employees',
            'manage_all_leaves',
            'view_hr_reports',
            'create_announcements',
        ]);

        $owner = Role::findByName('owner');
        $owner->givePermissionTo($hrPermissions);

        $this->command->info('HR role and permissions seeded.');
    }
}
