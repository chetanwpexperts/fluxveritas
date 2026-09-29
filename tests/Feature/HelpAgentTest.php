<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpAgentTest extends TestCase
{
    use RefreshDatabase;

    private function member(): User
    {
        $org = Organization::create(['name' => 'Acme', 'slug' => 'acme', 'status' => 'active', 'plan' => 'free']);

        return User::factory()->create(['organization_id' => $org->id]);
    }

    public function test_payroll_question_is_answered_directly(): void
    {
        $this->actingAs($this->member())
            ->postJson('/help-agent/ask', ['question' => 'what is my salary this month'])
            ->assertOk()
            ->assertJsonPath('source', 'direct')
            ->assertJsonPath('instant', true);
    }

    public function test_clock_in_question_goes_to_timesheets_not_the_time_intent(): void
    {
        $this->actingAs($this->member())
            ->postJson('/help-agent/ask', ['question' => 'have I clocked in today'])
            ->assertOk()
            ->assertJsonPath('source', 'direct')
            ->assertSee("haven't clocked in", false);
    }
}
