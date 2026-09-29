<?php

namespace App\Services;

use App\Models\AgentNotification;
use App\Models\User;

class NotificationService
{
    public static function send(
        int $userId,
        int $organizationId,
        string $type,
        string $title,
        string $message,
        string $actionUrl = '',
        string $actionLabel = 'View',
        string $priority = 'normal',
        array $metadata = [],
        ?int $triggeredByUserId = null
    ): AgentNotification {
        return AgentNotification::create([
            'user_id'               => $userId,
            'organization_id'       => $organizationId,
            'triggered_for_user_id' => $triggeredByUserId,
            'notification_type'     => $type,
            'title'                 => $title,
            'message'               => $message,
            'action_url'            => $actionUrl,
            'action_label'          => $actionLabel,
            'priority'              => $priority,
            'is_read'               => false,
            'is_dismissed'          => false,
            'metadata'              => $metadata,
        ]);
    }

    public static function sendToMany(
        array $userIds,
        int $organizationId,
        string $type,
        string $title,
        string $message,
        string $actionUrl = '',
        string $actionLabel = 'View',
        string $priority = 'normal',
        array $metadata = [],
        ?int $triggeredByUserId = null
    ): void {
        foreach ($userIds as $userId) {
            if ($userId === $triggeredByUserId) continue;

            static::send(
                $userId, $organizationId, $type,
                $title, $message, $actionUrl,
                $actionLabel, $priority,
                $metadata, $triggeredByUserId
            );
        }
    }

    public static function sendToManagers(
        int $organizationId,
        string $type,
        string $title,
        string $message,
        string $actionUrl = '',
        string $priority = 'normal',
        array $metadata = [],
        ?int $excludeUserId = null
    ): void {
        $managers = User::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['admin', 'owner', 'ceo', 'team_lead', 'super_admin']);
            })
            ->pluck('id')
            ->toArray();

        static::sendToMany(
            $managers, $organizationId, $type,
            $title, $message, $actionUrl,
            'View', $priority, $metadata,
            $excludeUserId
        );
    }

    public static function sendToManager(
        User $employee,
        string $type,
        string $title,
        string $message,
        string $actionUrl = '',
        string $priority = 'normal',
        array $metadata = []
    ): void {
        $managerId = $employee->reporting_manager_id;

        if (!$managerId) {
            static::sendToManagers(
                $employee->organization_id,
                $type, $title, $message,
                $actionUrl, $priority, $metadata,
                $employee->id
            );
            return;
        }

        static::send(
            $managerId,
            $employee->organization_id,
            $type, $title, $message,
            $actionUrl, 'View', $priority,
            $metadata, $employee->id
        );
    }
}
