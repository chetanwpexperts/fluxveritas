<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function getUserPermissions(User $user): array
    {
        return $user->getAllPermissions()->pluck('name')->toArray();
    }

    public function getRolesWithPermissions(): array
    {
        return Role::with('permissions')->get()->map(function ($role) {
            return [
                'name'        => $role->name,
                'permissions' => $role->permissions->pluck('name')->toArray(),
            ];
        })->toArray();
    }

    public function assignRole(User $user, string $role): void
    {
        $user->syncRoles([$role]);

        $legacyMap = [
            'super_admin' => 'admin',
            'owner'       => 'owner',
            'admin'       => 'admin',
            'team_lead'   => 'employee',
            'employee'    => 'employee',
            'viewer'      => 'employee',
        ];

        $user->update(['role' => $legacyMap[$role] ?? 'employee']);
    }

    public function canAccessModule(User $user, string $module): bool
    {
        $modulePermissions = [
            'github'          => 'sync_github',
            'fairness'        => 'view_fairness',
            'ai'              => 'view_ai',
            'blockers'        => 'view_blockers',
            'command_center'  => 'view_command_center',
            'reports'         => 'view_reports',
            'settings'        => 'view_settings',
        ];

        $permission = $modulePermissions[$module] ?? null;
        if (!$permission) {
            return false;
        }

        return $user->can($permission);
    }
}
