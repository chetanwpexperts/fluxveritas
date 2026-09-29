<?php

namespace Tests\Feature;

use App\Models\Blocker;
use App\Models\Department;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkLog;
use App\Services\IntentMatcherService;
use Database\Seeders\AgentIntentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HelpAgentIntentTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $lead;

    /** @var array<int, array{message: string, context: array}> debug log lines */
    private array $debugLogs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AgentIntentSeeder::class);

        Event::listen(MessageLogged::class, function (MessageLogged $e) {
            if ($e->level === 'debug') {
                $this->debugLogs[] = ['message' => $e->message, 'context' => $e->context];
            }
        });

        // No real AI calls from tests; each test opts in where needed
        config(['services.openai.key' => '', 'services.ollama.url' => '']);
        Http::preventStrayRequests();

        foreach (['team_lead', 'employee'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->org  = Organization::create(['name' => 'Acme', 'slug' => 'acme', 'status' => 'active', 'plan' => 'free']);
        $this->lead = $this->person('Team Lead Person', 'team_lead');
    }

    private function person(string $name, string $role, ?Team $team = null): User
    {
        $user = User::factory()->create(['name' => $name, 'organization_id' => $this->org->id, 'team_id' => $team?->id]);
        $user->assignRole($role);

        return $user;
    }

    /** Lead's team: one member logged today, one blocked, one on approved leave today. */
    private function teamWithActivity(): array
    {
        $dept    = Department::create(['organization_id' => $this->org->id, 'name' => 'Engineering', 'slug' => 'engineering', 'type' => 'tech', 'is_active' => true]);
        $team    = Team::create(['organization_id' => $this->org->id, 'department_id' => $dept->id, 'team_lead_id' => $this->lead->id, 'name' => 'Web Delivery', 'slug' => 'web-delivery', 'is_active' => true]);
        $logged  = $this->person('Member Logged', 'employee', $team);
        $blocked = $this->person('Member Blocked', 'employee', $team);
        $away    = $this->person('Member Away', 'employee', $team);
        $today   = now()->setTimezone('Asia/Kolkata')->toDateString();

        WorkLog::create(['user_id' => $logged->id, 'organization_id' => $this->org->id, 'log_date' => $today, 'category' => 'Development', 'title' => 'API work']);

        $project = Project::create(['organization_id' => $this->org->id, 'name' => 'Portal']);
        $blocker = Blocker::create([
            'organization_id' => $this->org->id, 'project_id' => $project->id, 'reported_by' => $blocked->id,
            'blocked_user_id' => $blocked->id, 'blocker_type' => 'internal_person', 'title' => 'Waiting for API keys',
            'description' => 'Need keys', 'status' => 'open',
        ]);
        $blocker->forceFill(['created_at' => now()->subDays(4)])->saveQuietly();

        $type = LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 12, 'is_active' => true]);
        LeaveApplication::create([
            'user_id' => $away->id, 'leave_type_id' => $type->id, 'organization_id' => $this->org->id,
            'from_date' => $today, 'to_date' => now()->addDay()->toDateString(), 'days' => 2,
            'reason' => 'Family', 'status' => 'approved',
        ]);

        return compact('team', 'logged', 'blocked', 'away');
    }

    private function ask(string $question, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->lead)->postJson('/help-agent/ask', ['question' => $question]);
    }

    private function assertLogged(string $stage, ?string $intent = null): void
    {
        $hit = collect($this->debugLogs)->first(fn ($log) => $log['message'] === 'Outy answered'
            && $log['context']['stage'] === $stage
            && ($intent === null || $log['context']['intent'] === $intent)
            && array_key_exists('question', $log['context'])
            && array_key_exists('score', $log['context']));

        $this->assertNotNull($hit, "No debug log for stage {$stage}" . ($intent ? " / intent {$intent}" : '') . ': ' . json_encode($this->debugLogs));
    }

    // ── The staging examples ─────────────────────────────────────────────────

    public function test_how_is_my_team_doing_today_returns_live_team_status_not_time(): void
    {
        $this->teamWithActivity();

        $answer = $this->ask('How is my team doing today?')->assertOk()->assertJsonPath('source', 'direct')->json('answer');

        $this->assertStringContainsString('Web Delivery', $answer);
        $this->assertStringContainsString('Logged work: 1 of 2', $answer);          // Member Away is excluded
        $this->assertStringContainsString('Not logged yet: Member Blocked', $answer);
        $this->assertStringContainsString('Member Blocked — Waiting for API keys (4 days)', $answer);
        $this->assertStringContainsString('On leave: Member Away', $answer);
        $this->assertStringNotContainsString(' IST', $answer, 'Must not be the time answer');
        $this->assertLogged('intent', 'team_status');
    }

    public function test_who_needs_help_on_my_team_names_blocked_people_not_increments(): void
    {
        $this->teamWithActivity();

        $answer = $this->ask('Who needs help on my team?')->assertOk()->json('answer');

        $this->assertStringContainsString('Needs help: Member Blocked', $answer);
        $this->assertStringNotContainsStringIgnoringCase('increment', $answer);
        $this->assertLogged('intent', 'team_status');
    }

    public function test_unrelated_question_is_not_answered_with_welcome_or_greeting(): void
    {

        $response = $this->ask('Which projects are late this week?')->assertOk();

        $this->assertStringNotContainsString('Welcome', $response->json('answer'));
        $response->assertJsonPath('source', 'fallback')
                 ->assertJsonPath('answer', "I'm not sure. Try one of these:")
                 ->assertJsonCount(3, 'suggestions')
                 ->assertJsonStructure(['suggestions' => [['label', 'question']]]);
        $this->assertLogged('fallback');
    }

    public function test_what_time_is_it_returns_the_time_intent(): void
    {

        $answer = $this->ask('what time is it')->assertOk()->assertJsonPath('source', 'direct')->json('answer');

        $this->assertStringContainsString(' IST', $answer);
        $this->assertLogged('intent', 'time_date');
    }

    // ── Team data edge cases ─────────────────────────────────────────────────

    public function test_person_without_a_team_gets_a_clear_message(): void
    {
        $loner = $this->person('No Team Person', 'employee');

        $this->ask('How is my team doing today?', $loner)
            ->assertOk()
            ->assertJsonPath('answer', "You're not part of a team yet, so there's no team status to show. Ask your admin to add you to a team.");
    }

    public function test_team_member_sees_their_own_team(): void
    {
        ['logged' => $member] = $this->teamWithActivity();

        $answer = $this->ask('How is my team doing today?', $member)->json('answer');

        $this->assertStringContainsString('Web Delivery', $answer);
        $this->assertStringContainsString('Member Blocked', $answer);
    }

    public function test_other_organizations_are_never_included(): void
    {
        $this->teamWithActivity();
        $other = Organization::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active', 'plan' => 'free']);
        $stranger = User::factory()->create(['name' => 'Stranger Elsewhere', 'organization_id' => $other->id, 'reporting_manager_id' => $this->lead->id]);

        $this->assertStringNotContainsString('Stranger Elsewhere', $this->ask('How is my team doing today?')->json('answer'));
    }

    public function test_leave_balance_is_answered_from_live_data(): void
    {
        $type = LeaveType::create(['organization_id' => $this->org->id, 'name' => 'Casual Leave', 'code' => 'CL', 'days_per_year' => 12, 'is_active' => true]);
        LeaveBalance::create(['user_id' => $this->lead->id, 'leave_type_id' => $type->id, 'organization_id' => $this->org->id,
            'year' => now()->year, 'allocated' => 12, 'used' => 3, 'pending' => 1, 'carried_forward' => 0]);

        $this->ask('What is my leave balance?')->assertOk()
            ->assertJsonPath('source', 'direct')
            ->assertSee('Casual Leave: 9 days left of 12 days (1 day pending approval)', false);
    }

    // ── AI fallback ──────────────────────────────────────────────────────────

    public function test_unmatched_question_uses_openai_when_configured(): void
    {
        config(['services.openai.key' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response(['choices' => [['message' => ['content' => 'Two projects are behind schedule.']]]])]);

        $this->ask('Which projects are late this week?')
            ->assertOk()
            ->assertJsonPath('source', 'outy_ai')
            ->assertJsonPath('answer', 'Two projects are behind schedule.');

        $this->assertLogged('ai:openai');
    }

    public function test_ollama_is_skipped_when_url_is_empty(): void
    {
        Http::fake();

        $this->ask('Which projects are late this week?')->assertJsonPath('source', 'fallback');

        Http::assertNothingSent();
    }

    public function test_unreachable_ollama_is_skipped_after_a_quick_ping(): void
    {
        config(['services.ollama.url' => 'http://ollama.test']);
        Http::fake(['ollama.test/*' => Http::response('', 503)]);

        $this->ask('Which projects are late this week?')->assertJsonPath('source', 'fallback');

        Http::assertSentCount(1); // the 2s ping only — no generate call
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/tags'));
    }

    public function test_failed_openai_call_falls_back_to_suggestions_not_a_canned_error(): void
    {
        config(['services.openai.key' => 'sk-test']);
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'rate limited'], 429)]);

        $this->ask('Which projects are late this week?')
            ->assertJsonPath('source', 'fallback')
            ->assertJsonMissing(['answer' => 'OpenAI temporarily unavailable.']);
    }

    // ── Matcher rules ────────────────────────────────────────────────────────

    public static function routing(): array
    {
        return [
            'staging: team today'      => ['How is my team doing today?', 'team_status'],
            'staging: needs help'      => ['Who needs help on my team?', 'team_status'],
            'explicit time'            => ['what time is it', 'time_date'],
            'explicit date'            => ["What's the date today?", 'time_date'],
            'who is blocked'           => ['who is blocked', 'team_status'],
            'team blockers'            => ['Any blockers in my team?', 'team_status'],
            'leave balance phrase'     => ['What is my leave balance?', 'my_leave'],
            'own blockers'             => ['am I blocked?', 'blockers'],
            'tasks chip'               => ['What tasks do I have pending?', 'my_tasks'],
            'score chip'               => ['What is my current increment score?', 'my_score'],
            'missing logs chip'        => ["Who hasn't logged work today?", 'team_logged'],
            'greeting only'            => ['hi', 'greeting'],
            'today alone is not time'  => ['today', 'unknown'],
            'timer is not time'        => ['How do I restart my timer?', 'unknown'],
            'hi inside which'          => ['Which projects are late this week?', 'unknown'],
            'greeting + question'      => ['hi, how many leaves do I have?', 'my_leave'],
            'stopwords only'           => ['how is it going today?', 'unknown'],
        ];
    }

    #[DataProvider('routing')]
    public function test_intent_routing(string $question, string $intent): void
    {
        $this->assertSame($intent, IntentMatcherService::classify($question));
    }

    public function test_phrase_outscores_single_words(): void
    {
        $phrase = IntentMatcherService::match('any blockers in my team');
        $single = IntentMatcherService::match('any blockers');

        $this->assertSame('team_status', $phrase['intent']);
        $this->assertSame('blockers', $single['intent']);
        $this->assertGreaterThan($single['score'], $phrase['score']);
    }
}
