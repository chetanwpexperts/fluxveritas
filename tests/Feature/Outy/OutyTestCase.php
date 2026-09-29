<?php

namespace Tests\Feature\Outy;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\AgentIntentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Shared setup and OpenAI response fakes for Outy tests. */
abstract class OutyTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AgentIntentSeeder::class); // keyword fallback
        config([
            'services.openai.key' => 'sk-test',
            'outy.model'          => 'gpt-test',
            'services.ollama.url' => '',
        ]);
        Http::preventStrayRequests();

        foreach (['super_admin', 'owner', 'admin', 'hr', 'team_lead', 'employee', 'viewer'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $this->org = $this->organization('Acme', 'pro');
    }

    protected function organization(string $name, string $plan = 'pro'): Organization
    {
        return Organization::create([
            'name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4), 'status' => 'active',
            'plan' => $plan, 'plan_expires_at' => $plan === 'free' ? null : now()->addMonth(),
        ]);
    }

    protected function person(string $name, string $role, ?Organization $org = null, array $attrs = []): User
    {
        $user = User::factory()->create(array_merge(['name' => $name, 'organization_id' => ($org ?? $this->org)->id], $attrs));
        $user->assignRole($role);

        return $user;
    }

    protected function team(User $lead, string $name = 'Web Delivery'): Team
    {
        $dept = Department::create(['organization_id' => $lead->organization_id, 'name' => 'Engineering', 'slug' => 'eng-' . Str::random(4), 'type' => 'tech', 'is_active' => true]);

        return Team::create(['organization_id' => $lead->organization_id, 'department_id' => $dept->id, 'team_lead_id' => $lead->id,
            'name' => $name, 'slug' => Str::slug($name) . '-' . Str::random(4), 'is_active' => true]);
    }

    protected function ask(User $user, string $question)
    {
        return $this->actingAs($user)->postJson('/help-agent/ask', ['question' => $question]);
    }

    // ── OpenAI fakes ─────────────────────────────────────────────────────────

    protected static function text(string $content): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $content]]]];
    }

    protected static function toolCall(string $name, array $args = [], string $id = 'call_1'): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => null, 'tool_calls' => [[
            'id' => $id, 'type' => 'function',
            'function' => ['name' => $name, 'arguments' => json_encode((object) $args)],
        ]]]]]];
    }

    /** Queue OpenAI responses in order. */
    protected function fakeOpenAi(array ...$responses): void
    {
        $sequence = Http::sequence();
        foreach ($responses as $response) {
            $sequence->push($response);
        }
        Http::fake(['api.openai.com/*' => $sequence]);
    }

    /** @return array<int, array> JSON bodies sent to OpenAI, in order */
    protected function openAiRequests(): array
    {
        return collect(Http::recorded())
            ->map(fn ($pair) => $pair[0])
            ->filter(fn (Request $r) => str_contains($r->url(), 'api.openai.com'))
            ->map(fn (Request $r) => $r->data())
            ->values()->all();
    }

    /** Decoded tool results the agent sent back to OpenAI. */
    protected function toolResults(): array
    {
        return collect($this->openAiRequests())
            ->flatMap(fn ($body) => $body['messages'])
            ->where('role', 'tool')
            ->map(fn ($m) => json_decode($m['content'], true))
            ->values()->all();
    }

    protected function offeredTools(User $user): array
    {
        return app(\App\Services\Outy\ToolRegistry::class)->forUser($user)->keys()->sort()->values()->all();
    }
}
