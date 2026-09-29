<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\Blocker;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/** Reporting blockers — shared by the Blockers screen and Outy. */
class BlockerService
{
    public const TYPES = ['internal_person', 'external_vendor', 'meeting_required', 'waiting_approval', 'dependency_task', 'other'];

    /**
     * Checks that the project, task and blocking person all belong to the
     * reporter's organization.
     *
     * @return array{project: Project, blocking: ?User, task_id: ?int}
     */
    public function resolveReferences(User $reporter, int $projectId, ?int $blockingUserId = null, ?int $taskId = null): array
    {
        $orgId   = $reporter->organization_id;
        $project = Project::withoutGlobalScopes()->where('organization_id', $orgId)->find($projectId);
        if (!$project) {
            throw new WorkflowException('Choose a project in your organization.', 'project_id');
        }

        $blocking = null;
        if ($blockingUserId) {
            $blocking = User::where('organization_id', $orgId)->where('is_active', true)->find($blockingUserId);
            if (!$blocking) {
                throw new WorkflowException('The person blocking you must be an active member of your organization.', 'blocking_user_id');
            }
            if ($blocking->id === $reporter->id) {
                throw new WorkflowException('You can\'t report yourself as the blocker.', 'blocking_user_id');
            }
        }

        if ($taskId && !Task::whereKey($taskId)->where('project_id', $project->id)->exists()) {
            throw new WorkflowException('That task is not part of the chosen project.', 'task_id');
        }

        return ['project' => $project, 'blocking' => $blocking, 'task_id' => $taskId];
    }

    public function report(User $reporter, array $data): Blocker
    {
        $type = (string) ($data['blocker_type'] ?? '');
        if (!in_array($type, self::TYPES, true)) {
            throw new WorkflowException('Choose a valid blocker type.', 'blocker_type');
        }
        if (trim((string) ($data['title'] ?? '')) === '' || trim((string) ($data['description'] ?? '')) === '') {
            throw new WorkflowException('A title and description are required.', 'title');
        }

        $refs = $this->resolveReferences(
            $reporter,
            (int) ($data['project_id'] ?? 0),
            isset($data['blocking_user_id']) ? (int) $data['blocking_user_id'] : null,
            isset($data['task_id']) ? (int) $data['task_id'] : null,
        );

        $blocker = Blocker::create([
            'organization_id'         => $reporter->organization_id,
            'project_id'              => $refs['project']->id,
            'reported_by'             => $reporter->id,
            'blocked_user_id'         => $reporter->id,
            'blocking_user_id'        => $refs['blocking']?->id,
            'task_id'                 => $refs['task_id'],
            'impact_level'            => $data['impact_level'] ?? 'just_me',
            'blocker_type'            => $type,
            'title'                   => mb_substr(trim($data['title']), 0, 255),
            'description'             => trim($data['description']),
            'priority'                => in_array($data['priority'] ?? 'medium', ['low', 'medium', 'high', 'critical'], true) ? ($data['priority'] ?? 'medium') : 'medium',
            'due_date'                => $data['due_date'] ?? null,
            'status'                  => 'open',
            'external_person_name'    => $data['external_person_name'] ?? null,
            'external_person_company' => $data['external_person_company'] ?? null,
            'external_person_contact' => $data['external_person_contact'] ?? null,
            'evidence_notes'          => $data['evidence_notes'] ?? null,
        ]);

        if ($blocker->blocking_user_id) {
            NotificationService::send(
                $blocker->blocking_user_id,
                $reporter->organization_id,
                'blocker_reported',
                '⚠️ You Have a Blocker Reported Against You',
                $reporter->name . ' reported that you are blocking them: "' . $blocker->title . '". Please acknowledge and resolve.',
                '/dependencies/blocker/' . $blocker->id,
                'View & Acknowledge',
                'high',
                ['blocker_id' => $blocker->id],
                $reporter->id
            );
        }

        NotificationService::sendToManagers(
            $reporter->organization_id,
            'blocker_reported',
            '🚨 New Blocker Reported',
            $reporter->name . ' reported a blocker: "' . $blocker->title . '"',
            '/dependencies/blocker/' . $blocker->id,
            'normal',
            ['blocker_id' => $blocker->id],
            $reporter->id
        );

        return $blocker;
    }
}
