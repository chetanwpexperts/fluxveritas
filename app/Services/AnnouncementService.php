<?php

namespace App\Services;

use App\Exceptions\WorkflowException;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

/** Posting announcements — shared by the Announcements screen and Outy. */
class AnnouncementService
{
    public const POSTER_ROLES = ['admin', 'owner', 'ceo', 'team_lead', 'super_admin', 'manager'];

    public function canPost(User $user): bool
    {
        return $user->hasAnyRole(self::POSTER_ROLES);
    }

    /**
     * Resolves who an announcement will reach, applying the posting rules:
     * team leads (and managers) can only post to a team; department/team must
     * belong to the poster's organization.
     *
     * @return array{audience: string, department_id: ?int, team_id: ?int, label: string}
     */
    public function audienceFor(User $user, string $audience, ?int $departmentId, ?int $teamId): array
    {
        if (!$this->canPost($user)) {
            throw new WorkflowException('You are not allowed to post announcements.');
        }

        $orgId = $user->organization_id;

        if ($user->hasAnyRole(['team_lead', 'manager']) && !$user->hasAnyRole(['admin', 'owner', 'ceo'])) {
            $audience = 'team';
            if ($user->hasRole('team_lead')) {
                $teamId = Team::where('organization_id', $orgId)->where('team_lead_id', $user->id)->value('id');
            }
        }

        if (!in_array($audience, ['org', 'department', 'team'], true)) {
            throw new WorkflowException('Choose who the announcement is for: organization, department or team.', 'audience');
        }

        if ($audience === 'department') {
            $dept = $departmentId ? Department::withoutGlobalScopes()->where('organization_id', $orgId)->find($departmentId) : null;
            if (!$dept) {
                throw new WorkflowException('Choose a department in your organization.', 'department_id');
            }
            return ['audience' => 'department', 'department_id' => $dept->id, 'team_id' => null, 'label' => "{$dept->name} department"];
        }

        if ($audience === 'team') {
            $team = $teamId ? Team::where('organization_id', $orgId)->find($teamId) : null;
            if (!$team) {
                throw new WorkflowException('Choose a team in your organization.', 'team_id');
            }
            return ['audience' => 'team', 'department_id' => null, 'team_id' => $team->id, 'label' => "{$team->name} team"];
        }

        return ['audience' => 'org', 'department_id' => null, 'team_id' => null, 'label' => 'Everyone in the organization'];
    }

    public function post(User $user, array $data): Announcement
    {
        $title   = trim((string) ($data['title'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));
        if ($title === '' || mb_strlen($title) > 200) {
            throw new WorkflowException('The title is required (up to 200 characters).', 'title');
        }
        if ($message === '' || mb_strlen($message) > 2000) {
            throw new WorkflowException('The message is required (up to 2000 characters).', 'message');
        }
        $priority = in_array($data['priority'] ?? 'normal', ['normal', 'urgent'], true) ? ($data['priority'] ?? 'normal') : 'normal';

        $target = $this->audienceFor($user, (string) ($data['audience'] ?? 'org'), $data['department_id'] ?? null, $data['team_id'] ?? null);

        $announcement = Announcement::create([
            'organization_id' => $user->organization_id,
            'posted_by'       => $user->id,
            'title'           => $title,
            'message'         => $message,
            'priority'        => $priority,
            'audience'        => $target['audience'],
            'department_id'   => $target['department_id'],
            'team_id'         => $target['team_id'],
            'is_pinned'       => (bool) ($data['is_pinned'] ?? false),
            'expires_at'      => $data['expires_at'] ?? null,
        ]);

        $this->notify($announcement, $user);

        return $announcement;
    }

    private function notify(Announcement $a, User $poster): void
    {
        $title = $a->priority === 'urgent' ? "🚨 Urgent: {$a->title}" : "📢 {$a->title}";
        $query = User::where('organization_id', $a->organization_id)->where('id', '!=', $poster->id);

        if ($a->audience === 'department' && $a->department_id) {
            $query->where('department_id', $a->department_id);
        } elseif ($a->audience === 'team' && $a->team_id) {
            $query->where('team_id', $a->team_id);
        }

        $ids = $query->pluck('id')->all();
        if ($ids) {
            NotificationService::sendToMany($ids, $a->organization_id, 'announcement', $title, Str::limit($a->message, 100), route('announcements.index'));
        }
    }
}
