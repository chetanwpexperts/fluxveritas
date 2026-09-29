<?php

namespace App\Services\Outy\Tools;

use App\Exceptions\WorkflowException;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use App\Services\AnnouncementService;
use Illuminate\Support\Str;

class CreateAnnouncement extends ActionTool
{
    protected array $roles = ['owner', 'admin', 'team_lead'];

    protected ?string $module = 'announcements';

    public function name(): string
    {
        return 'create_announcement';
    }

    public function description(): string
    {
        return 'Prepare an announcement (confirm card; nothing is posted until the user confirms). Team leads can only post to their own team. '
            . 'Ask for the title and message if the user has not given them.';
    }

    public function parameters(): array
    {
        return [
            'type'                 => 'object',
            'properties'           => [
                'title'      => ['type' => 'string', 'description' => 'Short title (max 200 characters)'],
                'message'    => ['type' => 'string', 'description' => 'Announcement text (max 2000 characters)'],
                'audience'   => ['type' => 'string', 'enum' => ['org', 'department', 'team'], 'description' => 'Who should see it'],
                'department' => ['type' => 'string', 'description' => 'Department name, when audience is department'],
                'team'       => ['type' => 'string', 'description' => 'Team name, when audience is team'],
                'priority'   => ['type' => 'string', 'enum' => ['normal', 'urgent']],
                'pinned'     => ['type' => 'boolean', 'description' => 'Pin to the top of the list'],
            ],
            'required'             => ['title', 'message'],
            'additionalProperties' => false,
        ];
    }

    protected function prepare(User $user, array $args): array
    {
        $title   = trim((string) ($args['title'] ?? ''));
        $message = trim((string) ($args['message'] ?? ''));
        if ($title === '' || $message === '') {
            throw new WorkflowException('Please give the announcement a title and a message.');
        }
        if (mb_strlen($title) > 200 || mb_strlen($message) > 2000) {
            throw new WorkflowException('The title can be up to 200 characters and the message up to 2000.');
        }

        $priority = ($args['priority'] ?? 'normal') === 'urgent' ? 'urgent' : 'normal';
        $pinned   = (bool) ($args['pinned'] ?? false);
        $target   = app(AnnouncementService::class)->audienceFor(
            $user,
            (string) ($args['audience'] ?? 'org'),
            $this->departmentId($user, $args['department'] ?? null),
            $this->teamId($user, $args['team'] ?? null),
        );

        return [
            'title'   => 'Post announcement: ' . Str::limit($title, 60),
            'details' => [
                ['Audience', $target['label']],
                ['Priority', ucfirst($priority) . ($pinned ? ', pinned' : '')],
                ['Title', $title],
                ['Message', Str::limit($message, 300)],
            ],
            'payload' => [
                'title' => $title, 'message' => $message, 'priority' => $priority, 'is_pinned' => $pinned,
                'audience' => $target['audience'], 'department_id' => $target['department_id'], 'team_id' => $target['team_id'],
            ],
        ];
    }

    public function execute(User $user, array $payload): string
    {
        $announcement = app(AnnouncementService::class)->post($user, $payload);

        return "Announcement posted: \"{$announcement->title}\".";
    }

    private function departmentId(User $user, ?string $name): ?int
    {
        if (!$name) {
            return null;
        }
        $dept = Department::withoutGlobalScopes()->where('organization_id', $user->organization_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();

        return $dept?->id ?? throw new WorkflowException("There is no department called \"{$name}\" in your organization.", 'department_id');
    }

    private function teamId(User $user, ?string $name): ?int
    {
        if (!$name) {
            return null;
        }
        $team = Team::where('organization_id', $user->organization_id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->first();

        return $team?->id ?? throw new WorkflowException("There is no team called \"{$name}\" in your organization.", 'team_id');
    }
}
