<?php

namespace Tests\Feature\Outy;

use App\Models\AuditLog;
use App\Models\WorkLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OutyAgentTest extends OutyTestCase
{
    public function test_agent_answers_from_live_tool_data(): void
    {
        $lead   = $this->person('Lead Person', 'team_lead');
        $team   = $this->team($lead);
        $member = $this->person('Member Logged', 'employee', null, ['team_id' => $team->id]);
        WorkLog::create(['user_id' => $member->id, 'organization_id' => $this->org->id,
            'log_date' => now()->setTimezone('Asia/Kolkata')->toDateString(), 'category' => 'Dev', 'title' => 'API']);

        $this->fakeOpenAi(self::toolCall('get_team_status'), self::text('**1 of 1** logged today: Member Logged.'));

        $this->ask($lead, 'How is my team doing?')
            ->assertOk()
            ->assertJsonPath('source', 'outy_ai')
            ->assertJsonPath('answer', '**1 of 1** logged today: Member Logged.');

        [$first] = $this->openAiRequests();
        $this->assertSame('gpt-test', $first['model']);
        $this->assertStringContainsString('Lead Person', $first['messages'][0]['content']);
        $this->assertStringContainsString('Acme', $first['messages'][0]['content']);
        $this->assertContains('get_team_status', collect($first['tools'])->pluck('function.name')->all());

        $result = $this->toolResults()[0];
        $this->assertSame('Web Delivery', $result['team']);
        $this->assertSame(['Member Logged'], $result['logged_today']);

        $audit = AuditLog::where('action', 'outy.tool_call')->sole();
        $this->assertSame($lead->id, $audit->user_id);
        $this->assertSame($this->org->id, $audit->organization_id);
        $this->assertSame('get_team_status', $audit->new_values['tool']);
        $this->assertSame('ok', $audit->new_values['status']);
        $this->assertGreaterThan(0, $audit->new_values['result_bytes']);
    }

    public function test_follow_up_questions_include_recent_history(): void
    {
        $user = $this->person('Staff Person', 'employee');
        $this->fakeOpenAi(self::text('You have 2 open tasks.'), self::text('The first is due Friday.'));

        $this->ask($user, 'How many tasks do I have?');
        $this->ask($user, 'When is the first one due?');

        $second = $this->openAiRequests()[1]['messages'];
        $this->assertSame(['system', 'user', 'assistant', 'user'], array_column($second, 'role'));
        $this->assertSame('How many tasks do I have?', $second[1]['content']);
        $this->assertSame('You have 2 open tasks.', $second[2]['content']);
    }

    public function test_history_is_capped_to_configured_turns(): void
    {
        config(['outy.history_turns' => 1]);
        $user = $this->person('Staff Person', 'employee');
        $this->fakeOpenAi(self::text('A1'), self::text('A2'), self::text('A3'));

        $this->ask($user, 'Q1');
        $this->ask($user, 'Q2');
        $this->ask($user, 'Q3');

        $third = $this->openAiRequests()[2]['messages'];
        $this->assertSame(['system', 'user', 'assistant', 'user'], array_column($third, 'role'));
        $this->assertSame('Q2', $third[1]['content']);
    }

    public function test_at_most_five_tool_calls_then_answer_without_tools(): void
    {
        $user = $this->person('Staff Person', 'employee');
        $calls = array_map(fn ($i) => self::toolCall('get_my_tasks', [], "call_{$i}"), range(1, 5));
        $this->fakeOpenAi(...[...$calls, self::text('Here is what I found.')]);

        $this->ask($user, 'Tell me everything about my tasks')->assertOk()->assertJsonPath('answer', 'Here is what I found.');

        $this->assertSame(5, AuditLog::where('action', 'outy.tool_call')->count());
        $requests = $this->openAiRequests();
        $this->assertCount(6, $requests);
        $this->assertArrayHasKey('tools', $requests[4]);
        $this->assertArrayNotHasKey('tools', $requests[5], 'After 5 calls the model must answer without tools');
    }

    public function test_api_error_falls_back_to_keyword_answers(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'Rate limit']], 429)]);

        $this->ask($this->person('Staff Person', 'employee'), 'what time is it')
            ->assertOk()
            ->assertJsonPath('source', 'direct')
            ->assertSee(' IST', false);
    }

    public function test_connection_failure_falls_back_to_keyword_answers(): void
    {
        Http::fake(fn () => throw new ConnectionException('timed out'));

        $this->ask($this->person('Staff Person', 'employee'), 'Tell me a joke')
            ->assertOk()
            ->assertJsonPath('source', 'fallback')
            ->assertJsonStructure(['suggestions']);
    }

    public function test_without_api_key_no_request_is_made(): void
    {
        config(['services.openai.key' => '']);
        Http::fake();

        $this->ask($this->person('Staff Person', 'employee'), 'what time is it')->assertJsonPath('source', 'direct');

        Http::assertNothingSent();
    }

    public function test_daily_limit_per_plan(): void
    {
        config(['outy.daily_limit.pro' => 2, 'outy.daily_limit.free' => 1]);
        $user     = $this->person('Staff Person', 'employee');
        $freeUser = $this->person('Free Person', 'employee', $this->organization('Tiny', 'free'));
        $this->fakeOpenAi(self::text('one'), self::text('two'), self::text('ok'));

        $this->ask($user, 'first')->assertOk();
        $this->ask($user, 'second')->assertOk();
        $this->ask($user, 'third')
            ->assertStatus(429)
            ->assertJsonPath('source', 'rate_limited')
            ->assertJsonPath('answer', "You've reached today's limit of 2 questions. It resets at midnight.");

        $this->assertCount(2, $this->openAiRequests());

        // Another plan has its own limit
        $this->ask($freeUser, 'first')->assertOk();
        $this->ask($freeUser, 'second')->assertStatus(429);
    }

    public function test_tools_offered_depend_on_role_and_plan(): void
    {
        $this->assertSame(
            ['explain_feature', 'get_my_increment', 'get_my_leave_balance', 'get_my_tasks', 'get_team_status'],
            $this->offeredTools($this->person('Staff', 'employee'))
        );
        $this->assertSame(
            ['explain_feature', 'get_my_increment', 'get_my_leave_balance', 'get_my_tasks', 'get_team_status', 'who_not_logged_in'],
            $this->offeredTools($this->person('Lead', 'team_lead'))
        );
        $this->assertSame(
            ['explain_feature', 'get_my_increment', 'get_my_leave_balance', 'get_my_tasks', 'get_org_stats', 'get_system_health', 'get_team_status', 'who_not_logged_in'],
            $this->offeredTools($this->person('Boss', 'owner'))
        );
        // Free plan: no increment tool (Pro module)
        $this->assertNotContains('get_my_increment', $this->offeredTools($this->person('Free', 'employee', $this->organization('Tiny', 'free'))));
        // Super admin outside any organization: platform tools only
        $this->assertSame(['explain_feature', 'get_system_health'], $this->offeredTools($this->person('Platform', 'super_admin', null, ['organization_id' => null])));
    }
}
