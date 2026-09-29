<?php

namespace Tests\Unit;

use App\Models\Organization;
use App\Models\User;
use App\Services\FairnessEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FairnessEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_org_context_building(): void
    {
        $org = Organization::create([
            'name'           => 'Fairness Test Corp',
            'slug'           => 'fairness-corp',
            'status'         => 'active',
            'plan'           => 'pro',
            'billing_status' => 'active',
        ]);

        $user = User::factory()->create([
            'organization_id' => $org->id,
            'name'            => 'Alice Dev',
            'email'           => 'alice@fairness.com',
            'is_active'       => true,
        ]);

        $engine = new FairnessEngine();
        $context = $engine->buildOrgContext($org->id);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('users_on_leave', $context);
        $this->assertArrayHasKey('open_blockers', $context);
        $this->assertArrayHasKey('users_in_disputed_blockers', $context);
    }
}
