<?php

namespace Tests\Feature\Outy;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Blocker;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\OutyPendingAction;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class OutyActionsTest extends OutyTestCase
{
    private LeaveType $casual;
    private string $monday;
    private string $tuesday;

    protected function setUp(): void
    {
        parent::setUp();

        $this->casual  = LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual Leave', 'code' => 'CL',
            'days_per_year' => 12, 'is_active' => true, 'requires_approval' => true]);
        $this->monday  = Carbon::now()->next(Carbon::MONDAY)->toDateString();
        $this->tuesday = Carbon::parse($this->monday)->addDay()->toDateString();
    }

    private function balance(User $user, float $allocated = 12): LeaveBalance
    {
        return LeaveBalance::create(['user_id' => $user->id, 'leave_type_id' => $this->casual->id, 'organization_id' => $user->organization_id,
            'year' => now()->year, 'allocated' => $allocated, 'used' => 0, 'pending' => 0, 'carried_forward' => 0]);
    }

    private function leaveArgs(array $overrides = []): array
    {
        return array_merge(['leave_type' => 'casual', 'from_date' => $this->monday, 'to_date' => $this->tuesday, 'reason' => 'Family function'], $overrides);
    }

    private function requestLeave(User $user, array $args = []): array
    {
        $this->fakeOpenAi(self::toolCall('apply_leave', $this->leaveArgs($args)), self::text('Please check the card and press Confirm.'));

        return $this->ask($user, 'Apply casual leave next Monday and Tuesday for a family function')->assertOk()->json();
    }

    private function confirm(User $user, string $token)
    {
        return $this->actingAs($user)->postJson("/help-agent/actions/{$token}/confirm");
    }

    // ── Apply leave: card first, then confirm ────────────────────────────────

    public function test_apply_leave_shows_a_card_and_only_applies_after_confirm(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->balance($me);

        $response = $this->requestLeave($me);

        $this->assertCount(1, $response['cards']);
        $card = $response['cards'][0];
        $this->assertSame('Apply for Casual Leave', $card['title']);
        $this->assertContains(['Working days', '2'], $card['details']);
        $this->assertContains(['Balance after', '10 days (once approved)'], $card['details']);
        $this->assertSame(0, LeaveApplication::count(), 'Nothing is saved before Confirm');

        // The model sees the summary but never the card id
        $toolResult = $this->toolResults()[0];
        $this->assertSame('awaiting_confirmation', $toolResult['status']);
        $this->assertArrayNotHasKey('_card', $toolResult);
        $this->assertStringNotContainsString($card['id'], json_encode($this->openAiRequests()));

        $this->confirm($me, $card['id'])->assertOk()->assertJsonPath('ok', true)
            ->assertJsonPath('answer', 'Leave applied: Casual Leave, ' . Carbon::parse($this->monday)->format('D j M') . ' – '
                . Carbon::parse($this->tuesday)->format('D j M Y') . ' (2 days). It is waiting for approval.');

        $application = LeaveApplication::sole();
        $this->assertSame('pending', $application->status);
        $this->assertSame($me->id, $application->user_id);
        $this->assertEquals(2, LeaveBalance::where('user_id', $me->id)->value('pending'));
        $this->assertSame(1, AuditLog::where('action', 'outy.action_confirmed')->count());

        // Single use
        $this->confirm($me, $card['id'])->assertStatus(409);
        $this->assertSame(1, LeaveApplication::count());
    }

    public function test_cancel_changes_nothing(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->balance($me);
        $card = $this->requestLeave($me)['cards'][0];

        $this->actingAs($me)->postJson("/help-agent/actions/{$card['id']}/cancel")
            ->assertOk()->assertJsonPath('answer', 'Cancelled — nothing was changed.');

        $this->assertSame(0, LeaveApplication::count());
        $this->confirm($me, $card['id'])->assertStatus(409);
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_expired_card_cannot_be_confirmed(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->balance($me);
        $card = $this->requestLeave($me)['cards'][0];

        $this->travel(OutyPendingAction::TTL_MINUTES + 1)->minutes();

        $this->confirm($me, $card['id'])->assertStatus(410);
        $this->assertSame(0, LeaveApplication::count());
        $this->assertSame('expired', OutyPendingAction::sole()->status);
    }

    public function test_someone_else_cannot_confirm_my_card(): void
    {
        $me    = $this->person('Staff Person', 'employee');
        $other = $this->person('Other Person', 'owner');
        $this->balance($me);
        $card = $this->requestLeave($me)['cards'][0];

        $this->confirm($other, $card['id'])->assertNotFound();
        $this->assertSame(0, LeaveApplication::count());
    }

    public function test_insufficient_balance_is_refused_before_any_card(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->balance($me, 1);

        $response = $this->requestLeave($me);

        $this->assertSame([], $response['cards']);
        $this->assertSame('Insufficient leave balance. Available: 1 days.', $this->toolResults()[0]['error']);
        $this->assertSame(0, OutyPendingAction::count());
    }

    public function test_unknown_leave_type_lists_the_real_ones(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->requestLeave($me, ['leave_type' => 'sabbatical']);

        $this->assertSame('Unknown leave type. Available types: Casual Leave (CL).', $this->toolResults()[0]['error']);
    }

    public function test_rules_are_checked_again_when_confirming(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $balance = $this->balance($me);
        $card = $this->requestLeave($me)['cards'][0];

        // Balance used up elsewhere before the user presses Confirm
        $balance->update(['used' => 12]);

        $this->confirm($me, $card['id'])->assertStatus(422)->assertJsonPath('answer', 'Insufficient leave balance. Available: 0 days.');
        $this->assertSame(0, LeaveApplication::count());
        $this->assertSame('failed', OutyPendingAction::sole()->status);
    }

    // ── Approve / reject ─────────────────────────────────────────────────────

    public function test_team_lead_approves_their_teams_leave_after_confirm(): void
    {
        $lead   = $this->person('Lead Person', 'team_lead');
        $team   = $this->team($lead);
        $member = $this->person('Team Member', 'employee', null, ['team_id' => $team->id]);
        $this->balance($member);
        $leave = app(\App\Services\LeaveService::class)->apply($member, $this->casual->id, $this->monday, $this->tuesday, 'Family function');

        $this->fakeOpenAi(
            self::toolCall('get_pending_leave_requests'),
            self::toolCall('approve_leave', ['request_id' => $leave->id, 'note' => 'Enjoy'], 'call_2'),
            self::text('Check the card to approve.')
        );
        $card = $this->ask($lead, "Approve Team Member's leave")->json('cards.0');

        $this->assertSame($leave->id, $this->toolResults()[0]['requests'][0]['request_id']);
        $this->assertSame("Approve Team Member's Casual Leave", $card['title']);
        $this->assertSame('pending', $leave->fresh()->status);

        $this->confirm($lead, $card['id'])->assertOk();

        $this->assertSame('approved', $leave->fresh()->status);
        $this->assertSame('Enjoy', $leave->fresh()->reviewer_note);
        $this->assertEquals(2, LeaveBalance::where('user_id', $member->id)->value('used'));
    }

    public function test_team_lead_cannot_review_another_teams_leave(): void
    {
        $lead     = $this->person('Lead Person', 'team_lead');
        $this->team($lead);
        $outsider = $this->person('Other Team Person', 'employee');
        $this->balance($outsider);
        $leave = app(\App\Services\LeaveService::class)->apply($outsider, $this->casual->id, $this->monday, $this->tuesday, 'Family function');

        $this->fakeOpenAi(self::toolCall('approve_leave', ['request_id' => $leave->id]), self::text('Not possible.'));
        $response = $this->ask($lead, 'Approve that leave');

        $this->assertSame([], $response->json('cards'));
        $this->assertStringContainsString("can't find a leave request", $this->toolResults()[0]['error']);
    }

    public function test_nobody_can_approve_their_own_leave(): void
    {
        $hr = $this->person('HR Person', 'hr');
        $this->balance($hr);
        $leave = app(\App\Services\LeaveService::class)->apply($hr, $this->casual->id, $this->monday, $this->tuesday, 'Own trip planned');

        $this->fakeOpenAi(self::toolCall('approve_leave', ['request_id' => $leave->id]), self::text('No.'));
        $this->ask($hr, 'Approve my own leave');
        $this->assertArrayHasKey('error', $this->toolResults()[0]);

        // Same rule on the Leaves screen
        $this->actingAs($hr)->post(route('leaves.approve', $leave->id))->assertSessionHasErrors('error');
        $this->assertSame('pending', $leave->fresh()->status);
    }

    public function test_losing_the_role_before_confirm_blocks_the_action(): void
    {
        $hr     = $this->person('HR Person', 'hr');
        $member = $this->person('Staff Person', 'employee');
        $this->balance($member);
        $leave = app(\App\Services\LeaveService::class)->apply($member, $this->casual->id, $this->monday, $this->tuesday, 'Family function');

        $this->fakeOpenAi(self::toolCall('reject_leave', ['request_id' => $leave->id, 'note' => 'Busy week']), self::text('Check the card.'));
        $card = $this->ask($hr, 'Reject that leave')->json('cards.0');

        $hr->syncRoles(['employee']);
        $this->confirm($hr->fresh(), $card['id'])->assertStatus(403);
        $this->assertSame('pending', $leave->fresh()->status);
    }

    // ── Announcements ────────────────────────────────────────────────────────

    public function test_team_lead_announcement_goes_to_their_team_only(): void
    {
        $lead = $this->person('Lead Person', 'team_lead');
        $team = $this->team($lead);

        $this->fakeOpenAi(
            self::toolCall('create_announcement', ['title' => 'Code freeze', 'message' => 'Freeze starts Monday.', 'audience' => 'org', 'priority' => 'urgent']),
            self::text('Check the card.')
        );
        $card = $this->ask($lead, 'Announce a code freeze to everyone')->json('cards.0');

        $this->assertContains(['Audience', "{$team->name} team"], $card['details']);
        $this->assertSame(0, Announcement::count());

        $this->confirm($lead, $card['id'])->assertOk();
        $announcement = Announcement::sole();
        $this->assertSame('team', $announcement->audience);
        $this->assertSame($team->id, $announcement->team_id);
        $this->assertSame('urgent', $announcement->priority);
    }

    public function test_employee_cannot_post_announcements_through_outy(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->fakeOpenAi(self::toolCall('create_announcement', ['title' => 'Hi', 'message' => 'Hello all']), self::text('No.'));

        $this->ask($me, 'Post an announcement to everyone');

        $this->assertSame(['error' => 'That information is not available for your role or plan.'], $this->toolResults()[0]);
        $this->assertSame(0, OutyPendingAction::count());
    }

    // ── Blockers ─────────────────────────────────────────────────────────────

    public function test_raise_blocker_against_a_named_colleague(): void
    {
        Permission::findOrCreate('create_blockers', 'web');
        $me = $this->person('Staff Person', 'employee');
        $me->givePermissionTo('create_blockers');
        $colleague = $this->person('Code Reviewer', 'employee');
        $project = Project::create(['organization_id' => $this->org->id, 'name' => 'Client Portal']);

        $this->fakeOpenAi(
            self::toolCall('raise_blocker', ['title' => 'Waiting for API keys', 'description' => 'Need production keys', 'blocker_type' => 'internal_person',
                'priority' => 'high', 'blocking_person' => 'reviewer']),
            self::text('Check the card.')
        );
        $card = $this->ask($me, "I'm blocked by the code reviewer on API keys")->json('cards.0');

        $this->assertContains(['Blocked by', 'Code Reviewer'], $card['details']);
        $this->assertContains(['Project', 'Client Portal'], $card['details']);

        $this->confirm($me, $card['id'])->assertOk();
        $blocker = Blocker::sole();
        $this->assertSame($colleague->id, $blocker->blocking_user_id);
        $this->assertSame($project->id, $blocker->project_id);
        $this->assertSame($this->org->id, $blocker->organization_id);
    }

    public function test_blocker_cannot_name_someone_from_another_organization(): void
    {
        Permission::findOrCreate('create_blockers', 'web');
        $me = $this->person('Staff Person', 'employee');
        $me->givePermissionTo('create_blockers');
        $this->person('Outside Person', 'employee', $this->organization('Rival Co'));
        Project::create(['organization_id' => $this->org->id, 'name' => 'Client Portal']);

        $this->fakeOpenAi(
            self::toolCall('raise_blocker', ['title' => 'x waiting', 'description' => 'y', 'blocker_type' => 'internal_person', 'blocking_person' => 'Outside Person']),
            self::text('No.')
        );
        $this->ask($me, 'Blocked by Outside Person');

        $this->assertSame("I can't find an active colleague called \"Outside Person\".", $this->toolResults()[0]['error']);
        $this->assertSame(0, OutyPendingAction::count());
    }

    // ── Screens use the same rules (org-isolation fixes) ─────────────────────

    public function test_blocker_screen_rejects_another_organizations_project(): void
    {
        Permission::findOrCreate('create_blockers', 'web');
        $me = $this->person('Staff Person', 'employee');
        $me->givePermissionTo('create_blockers');
        $foreign = Project::create(['organization_id' => $this->organization('Rival Co')->id, 'name' => 'Their project']);

        $this->actingAs($me)->post(route('dependency.reportBlocker'), [
            'project_id' => $foreign->id, 'blocker_type' => 'other', 'title' => 'x', 'description' => 'y',
            'priority' => 'low', 'impact_level' => 'just_me',
        ])->assertSessionHasErrors('project_id');

        $this->assertSame(0, Blocker::count());
    }

    public function test_announcement_screen_rejects_another_organizations_department(): void
    {
        $owner   = $this->person('Boss', 'owner');
        $foreign = Department::create(['organization_id' => $this->organization('Rival Co')->id, 'name' => 'Theirs', 'slug' => 'theirs', 'type' => 'tech', 'is_active' => true]);

        $this->actingAs($owner)->post(route('announcements.store'), [
            'title' => 'Hello', 'message' => 'World', 'priority' => 'normal', 'audience' => 'department', 'department_id' => $foreign->id,
        ])->assertSessionHasErrors('department_id');

        $this->assertSame(0, Announcement::count());
    }

    public function test_leave_screen_still_applies_normally(): void
    {
        $me = $this->person('Staff Person', 'employee');
        $this->balance($me);

        $this->actingAs($me)->post(route('leaves.apply'), [
            'leave_type_id' => $this->casual->id, 'from_date' => $this->monday, 'to_date' => $this->tuesday, 'reason' => 'Family function',
        ])->assertSessionHas('success');

        $this->assertSame('pending', LeaveApplication::sole()->status);
    }
}
