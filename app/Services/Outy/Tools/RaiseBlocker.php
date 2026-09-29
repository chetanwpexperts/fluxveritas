<?php

namespace App\Services\Outy\Tools;

use App\Exceptions\WorkflowException;
use App\Models\Project;
use App\Models\User;
use App\Services\BlockerService;
use Illuminate\Support\Str;

class RaiseBlocker extends ActionTool
{
    protected ?string $permission = 'create_blockers';

    protected ?string $module = 'blockers';

    private const TYPE_LABELS = [
        'internal_person' => 'Waiting on a colleague', 'external_vendor' => 'Waiting on a vendor',
        'meeting_required' => 'Meeting needed', 'waiting_approval' => 'Waiting for approval',
        'dependency_task' => 'Depends on another task', 'other' => 'Other',
    ];

    public function name(): string
    {
        return 'raise_blocker';
    }

    public function description(): string
    {
        return 'Prepare a blocker report for the current user (confirm card; nothing is reported until they confirm). '
            . 'Ask what is blocking them if unclear. The blocking person is optional and must be in their organization.';
    }

    public function parameters(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'title'           => ['type' => 'string', 'description' => 'Short summary of what is blocked'],
                'description'     => ['type' => 'string', 'description' => 'Details'],
                'blocker_type'    => ['type' => 'string', 'enum' => BlockerService::TYPES],
                'priority'        => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'critical']],
                'project'         => ['type' => 'string', 'description' => 'Project name (optional if the organization has one project)'],
                'blocking_person' => ['type' => 'string', 'description' => 'Name or email of the colleague blocking them (optional)'],
            ],
            'required'             => ['title', 'description', 'blocker_type'],
            'additionalProperties' => false,
        ];
    }

    protected function prepare(User $user, array $args): array
    {
        $title       = trim((string) ($args['title'] ?? ''));
        $description = trim((string) ($args['description'] ?? ''));
        $type        = (string) ($args['blocker_type'] ?? 'other');
        $priority    = in_array($args['priority'] ?? 'medium', ['low', 'medium', 'high', 'critical'], true) ? ($args['priority'] ?? 'medium') : 'medium';

        if ($title === '' || $description === '') {
            throw new WorkflowException('Please describe the blocker (a short title and some detail).');
        }
        if (!in_array($type, BlockerService::TYPES, true)) {
            $type = 'other';
        }

        $project  = $this->project($user, $args['project'] ?? null);
        $blocking = $this->person($user, $args['blocking_person'] ?? null);
        app(BlockerService::class)->resolveReferences($user, $project->id, $blocking?->id);

        return [
            'title'   => 'Report blocker: ' . Str::limit($title, 60),
            'details' => array_values(array_filter([
                ['Type', self::TYPE_LABELS[$type]],
                ['Priority', ucfirst($priority)],
                ['Project', $project->name],
                $blocking ? ['Blocked by', $blocking->name] : null,
                ['Details', Str::limit($description, 300)],
            ])),
            'payload' => [
                'title' => $title, 'description' => $description, 'blocker_type' => $type, 'priority' => $priority,
                'project_id' => $project->id, 'blocking_user_id' => $blocking?->id, 'impact_level' => 'just_me',
            ],
        ];
    }

    public function execute(User $user, array $payload): string
    {
        $blocker = app(BlockerService::class)->report($user, $payload);

        return "Blocker reported: \"{$blocker->title}\"." . ($blocker->blocking_user_id ? ' The person blocking you has been notified.' : '');
    }

    private function project(User $user, ?string $name): Project
    {
        $projects = Project::withoutGlobalScopes()->where('organization_id', $user->organization_id)->orderBy('name')->get(['id', 'name']);

        if ($name) {
            $match = $projects->first(fn ($p) => mb_strtolower($p->name) === mb_strtolower(trim($name)))
                ?? $projects->first(fn ($p) => str_contains(mb_strtolower($p->name), mb_strtolower(trim($name))));
            if ($match) {
                return $match;
            }
        } elseif ($projects->count() === 1) {
            return $projects->first();
        }

        if ($projects->isEmpty()) {
            throw new WorkflowException('Your organization has no projects yet, and a blocker needs one.');
        }

        throw new WorkflowException('Which project is this for? Projects: ' . $projects->pluck('name')->implode(', ') . '.', 'project_id');
    }

    private function person(User $user, ?string $who): ?User
    {
        $who = trim((string) $who);
        if ($who === '') {
            return null;
        }

        $people  = User::where('organization_id', $user->organization_id)->where('is_active', true)->where('id', '!=', $user->id);
        $matches = (clone $people)->where('email', $who)->get(['id', 'name']);
        if ($matches->isEmpty()) {
            $matches = (clone $people)->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($who) . '%'])->take(6)->get(['id', 'name']);
        }

        if ($matches->count() === 1) {
            return $matches->first();
        }

        throw new WorkflowException($matches->isEmpty()
            ? "I can't find an active colleague called \"{$who}\"."
            : 'Several people match — which one? ' . $matches->pluck('name')->implode(', ') . '.', 'blocking_user_id');
    }
}
