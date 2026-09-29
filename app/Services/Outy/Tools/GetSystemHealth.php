<?php

namespace App\Services\Outy\Tools;

use App\Models\User;
use App\Services\Outy\SystemHealthService;

/**
 * Platform health. super_admin sees error and failed-job messages; an org
 * owner sees counts only, because platform-wide log lines can mention other
 * organizations.
 */
class GetSystemHealth extends OutyTool
{
    protected array $roles = ['super_admin', 'owner'];

    protected bool $requiresOrganization = false;

    public function name(): string
    {
        return 'get_system_health';
    }

    public function description(): string
    {
        return 'Platform health: database reachable, queue size, failed jobs, errors in the application log in the last 24 hours, and free disk space.';
    }

    protected function handle(User $user, array $args): array
    {
        return app(SystemHealthService::class)->snapshot(includeMessages: $user->hasRole('super_admin'));
    }
}
